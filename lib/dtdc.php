<?php
/**
 * DTDC API Client — Official Implementation
 * Based on official DTDC API documentation
 *
 * Authentication: GET request returns plain text token
 * Tracking: Uses X-Access-Token header with token
 */
class DtdcClient
{
    private const BASE_URL       = 'https://blktracksvc.dtdc.com/dtdc-api';
    private const EP_AUTH        = '/api/dtdc/authenticate';
    private const EP_TRACK_JSON  = '/rest/JSONCnTrk/getTrackDetails';

    private string $username;
    private string $password;
    private string $apiKey;
    private string $customerCode;
    private string $customerPassword;
    private int    $timeout;
    private ?string $cachedToken = null;
    private int $tokenExpiry = 0;
    private const TOKEN_TTL = 3000; // 50 minutes (tokens expire in 1 hour)

    public function __construct(array $cfg = [])
    {
        $this->username         = $cfg['username']
            ?? ($_ENV['DTDC_USERNAME']      ?? null)
            ?? 'PL3537_trk_json';

        $this->password         = $cfg['password']
            ?? ($_ENV['DTDC_PASSWORD']      ?? null)
            ?? 'wafBo';

        $this->apiKey           = $cfg['api_key']
            ?? ($_ENV['DTDC_API_KEY']       ?? null)
            ?? 'bbb8196c734d8487983936199e880072';

        $this->customerCode     = $cfg['customer_code']
            ?? ($_ENV['DTDC_CUSTOMER_CODE'] ?? null)
            ?? 'PL3537';

        $this->customerPassword = $cfg['customer_password']
            ?? ($_ENV['DTDC_CUSTOMER_PASSWORD'] ?? null)
            ?? 'Abc@123456';

        $this->timeout          = $cfg['timeout'] ?? 30;
    }

    // ────────────────────────────────────────────────────────
    // Public: Track shipment by AWB
    // ────────────────────────────────────────────────────────
    public function track(string $awb): array
    {
        $awb = trim($awb);
        if ($awb === '') {
            return $this->fail('AWB number is required.');
        }

        // Get authentication token
        $token = $this->getAuthToken();
        if (!$token) {
            return $this->fail('DTDC authentication failed — check credentials.');
        }

        // Make tracking request with token
        $resp = $this->postWithToken(self::EP_TRACK_JSON, [
            'trkType'    => 'cnno',
            'strcnno'    => $awb,
            'addtnlDtl'  => 'Y',
        ], $token);

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
    // Get authentication token via GET request
    // ────────────────────────────────────────────────────────
    private function getAuthToken(): ?string
    {
        // Return cached token if still valid
        if ($this->cachedToken && $this->tokenExpiry > time()) {
            return $this->cachedToken;
        }

        // Build GET URL with query parameters
        $url = self::BASE_URL . self::EP_AUTH
            . '?username=' . urlencode($this->username)
            . '&password=' . urlencode($this->password);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/plain',
                'User-Agent: CareyGo/1.0',
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('DTDC Auth cURL error: ' . $curlError);
            return null;
        }

        if ($httpCode === 200 && !empty(trim($response))) {
            $token = trim($response);
            // Cache token for 50 minutes
            $this->cachedToken = $token;
            $this->tokenExpiry = time() + self::TOKEN_TTL;
            error_log('DTDC Auth token obtained successfully');
            return $token;
        }

        error_log('DTDC Auth failed - HTTP ' . $httpCode . ': ' . $response);
        return null;
    }

    // ────────────────────────────────────────────────────────
    // POST with X-Access-Token header
    // ────────────────────────────────────────────────────────
    private function postWithToken(string $endpoint, array $payload, string $token): array
    {
        $url = self::BASE_URL . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: CareyGo/1.0',
                'X-Access-Token: ' . $token,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('DTDC Request cURL error: ' . $curlError);
            return ['success' => false, 'error' => 'Network error'];
        }

        $body = json_decode($response, true);
        if ($body === null) {
            error_log('DTDC Response JSON decode error: ' . $response);
            return ['success' => false, 'error' => 'Invalid JSON response'];
        }

        // Check DTDC status code in response
        $statusCode = $body['statusCode'] ?? 0;

        if ($statusCode !== 200) {
            $errorMsg = 'HTTP ' . $httpCode;

            // Extract error message from errorDetails
            if (isset($body['errorDetails']) && is_array($body['errorDetails'])) {
                foreach ($body['errorDetails'] as $err) {
                    if (isset($err['name']) && $err['name'] === 'strError' && isset($err['value'])) {
                        $errorMsg = $err['value'];
                        break;
                    }
                }
            }

            error_log('DTDC API error (statusCode ' . $statusCode . '): ' . $errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }

        return ['success' => true, 'body' => $body];
    }

    // ────────────────────────────────────────────────────────
    // Normalise DTDC response → unified event array
    // ────────────────────────────────────────────────────────
    private function normaliseEvents(array $data): array
    {
        $events = [];

        // Parse tracking events from DTDC response
        if (isset($data['trackDetails']) && is_array($data['trackDetails'])) {
            foreach ($data['trackDetails'] as $event) {
                // Parse date and time (DDMMYY and HHMM format)
                $date = $event['strActionDate'] ?? '';
                $time = $event['strActionTime'] ?? '';

                $eventTime = '';
                if (strlen($date) === 8 && strlen($time) === 4) {
                    // Convert DDMMYY to YYYY-MM-DD
                    $day   = substr($date, 0, 2);
                    $month = substr($date, 2, 2);
                    $year  = '20' . substr($date, 4, 2);
                    // Convert HHMM to HH:MM:SS
                    $hour = substr($time, 0, 2);
                    $min  = substr($time, 2, 2);

                    $eventTime = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $min . ':00';
                }

                $events[] = [
                    'event_time'  => $eventTime,
                    'location'    => trim($event['strOrigin'] ?? ''),
                    'status'      => trim($event['strAction'] ?? ''),
                    'description' => trim($event['strAction'] ?? ''),
                    'source'      => 'dtdc',
                ];
            }
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
