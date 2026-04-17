<?php
/**
 * DTDC API Client
 * Handles authentication, token caching, and shipment tracking.
 */
class DtdcClient
{
    // ── API endpoints ─────────────────────────────────────────
    private const BASE_URL       = 'https://blktracksvc.dtdc.com/dtdc-api';
    private const EP_AUTH        = '/api/dtdc/authenticate';
    private const EP_TRACK_JSON  = '/rest/JSONCnTrk/getTrackDetails';

    // ── Credentials ──────────────────────────────────────────
    private string $username;
    private string $password;
    private string $apiKey;
    private string $customerCode;
    private int    $timeout;

    // ── Cached token ─────────────────────────────────────────
    private const TOKEN_TTL = 3300; // 55 min (tokens typically last 1 h)

    public function __construct(array $cfg = [])
    {
        // Try .env first, then config array, then defaults
        $this->username     = $cfg['username']
            ?? ($_ENV['DTDC_USERNAME']      ?? null)
            ?? 'PL3537_trk_json';

        $this->password     = $cfg['password']
            ?? ($_ENV['DTDC_PASSWORD']      ?? null)
            ?? 'wafBo';

        $this->apiKey       = $cfg['api_key']
            ?? ($_ENV['DTDC_API_KEY']       ?? null)
            ?? 'bbb8196c734d8487983936199e880072';

        $this->customerCode = $cfg['customer_code']
            ?? ($_ENV['DTDC_CUSTOMER_CODE'] ?? null)
            ?? 'PL3537';

        $this->timeout      = $cfg['timeout']       ?? 30;
    }

    // ────────────────────────────────────────────────────────
    // Public: get tracking events for an AWB
    // Returns ['success'=>bool, 'events'=>[], 'raw'=>[], 'error'=>string]
    // ────────────────────────────────────────────────────────
    public function track(string $awb): array
    {
        $awb = trim($awb);
        if ($awb === '') {
            return $this->fail('AWB number is required.');
        }

        $token = $this->resolveToken();
        if ($token === null) {
            return $this->fail('DTDC authentication failed — check credentials.');
        }

        $resp = $this->post(self::EP_TRACK_JSON, [
            'cnno'         => $awb,
            'customerCode' => $this->customerCode,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        if (!$resp['success']) {
            return $this->fail($resp['error'] ?? 'Tracking request failed.');
        }

        $events = $this->normaliseEvents($resp['body']);
        return [
            'success' => true,
            'awb'     => $awb,
            'events'  => $events,
            'raw'     => $resp['body'],
        ];
    }

    // ────────────────────────────────────────────────────────
    // Token management
    // ────────────────────────────────────────────────────────
    private function resolveToken(): ?string
    {
        // Check file cache
        $cache = $this->tokenCacheRead();
        if ($cache !== null) {
            return $cache;
        }

        // Authenticate
        $resp = $this->post(self::EP_AUTH, [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if (!$resp['success']) {
            return null;
        }

        $body  = $resp['body'];
        $token = $body['token']        // most common
            ?? $body['access_token']
            ?? $body['authToken']
            ?? $body['data']['token']
            ?? null;

        if (!$token) {
            return null;
        }

        $this->tokenCacheWrite($token);
        return $token;
    }

    private function tokenCacheRead(): ?string
    {
        $file = $this->tokenCacheFile();
        if (!file_exists($file)) return null;

        $data = @json_decode(file_get_contents($file), true);
        if (!$data || ($data['exp'] ?? 0) <= time()) {
            @unlink($file);
            return null;
        }
        return $data['tok'];
    }

    private function tokenCacheWrite(string $token): void
    {
        @file_put_contents(
            $this->tokenCacheFile(),
            json_encode(['tok' => $token, 'exp' => time() + self::TOKEN_TTL]),
            LOCK_EX
        );
    }

    private function tokenCacheFile(): string
    {
        return sys_get_temp_dir() . '/dtdc_tok_' . md5($this->username) . '.json';
    }

    // ────────────────────────────────────────────────────────
    // HTTP helper
    // ────────────────────────────────────────────────────────
    private function post(string $endpoint, array $payload, array $extraHeaders = []): array
    {
        $url = self::BASE_URL . $endpoint;

        $headers = array_merge([
            'Content-Type' => 'application/json',
            'X-API-Key'    => $this->apiKey,
            'Accept'       => 'application/json',
        ], $extraHeaders);

        $headerStr = implode("\r\n", array_map(
            fn($k, $v) => "$k: $v",
            array_keys($headers), array_values($headers)
        ));

        $ctx = stream_context_create([
            'http' => [
                'method'           => 'POST',
                'header'           => $headerStr,
                'content'          => json_encode($payload),
                'timeout'          => $this->timeout,
                'ignore_errors'    => true,
            ],
        ]);

        $raw  = @file_get_contents($url, false, $ctx);
        $code = 0;

        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
            $code = (int)($m[1] ?? 0);
        }

        if ($raw === false) {
            return ['success' => false, 'error' => 'Network error — could not reach DTDC API.'];
        }

        $body = json_decode($raw, true);
        if ($body === null) {
            return ['success' => false, 'error' => 'Invalid JSON from DTDC API.', 'raw' => $raw];
        }

        if ($code >= 400) {
            $msg = $body['message'] ?? $body['error'] ?? $body['errorMessage'] ?? ("HTTP $code");
            // Clear cached token on 401 so next call re-authenticates
            if ($code === 401) @unlink($this->tokenCacheFile());
            return ['success' => false, 'error' => $msg, 'body' => $body, 'code' => $code];
        }

        return ['success' => true, 'body' => $body, 'code' => $code];
    }

    // ────────────────────────────────────────────────────────
    // Normalise DTDC response → unified event array
    // ────────────────────────────────────────────────────────
    private function normaliseEvents(array $data): array
    {
        // DTDC wraps events in different keys depending on API version
        $list = $data['trackDetailsList']
            ?? $data['cnTrackList']
            ?? $data['shipmentTrackingDetails']
            ?? $data['trackingDetails']
            ?? [];

        // Some versions return a flat array
        if (empty($list) && isset($data[0]) && is_array($data[0])) {
            $list = $data;
        }

        $events = [];
        foreach ($list as $item) {
            if (!is_array($item)) continue;

            // Date + time may come combined or separate
            $date = $item['activityDate'] ?? $item['date'] ?? $item['eventDate'] ?? '';
            $time = $item['activityTime'] ?? $item['time'] ?? $item['eventTime'] ?? '';

            // Normalise date format — DTDC uses d/m/Y or Y-m-d
            $dt = $date;
            if ($time) $dt = trim($date . ' ' . $time);

            // Try to parse for uniform storage
            $ts = strtotime($dt);
            $eventTime = $ts ? date('Y-m-d H:i:s', $ts) : $dt;

            $status = $item['status']
                ?? $item['statusCode']
                ?? $item['activity']
                ?? $item['scanType']
                ?? '';

            $description = $item['remarks']
                ?? $item['description']
                ?? $item['statusDesc']
                ?? $item['activity']
                ?? '';

            $location = $item['location']
                ?? $item['city']
                ?? $item['origin']
                ?? '';

            $events[] = [
                'event_time'  => $eventTime,
                'location'    => trim((string)$location),
                'status'      => trim((string)$status),
                'description' => trim((string)$description),
                'source'      => 'dtdc',
            ];
        }

        // Newest first
        usort($events, fn($a, $b) => strcmp($b['event_time'], $a['event_time']));

        return $events;
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'error' => $msg, 'events' => [], 'raw' => []];
    }
}
