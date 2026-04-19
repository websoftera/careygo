<?php
/**
 * DTDC API Debug - Returns JSON with full request/response data
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$awb = trim($_GET['awb'] ?? 'P79187948');
$username = env('DTDC_USERNAME');
$password = env('DTDC_PASSWORD');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'awb' => $awb,
    'credentials' => [
        'username' => $username,
        'password_masked' => str_repeat('*', strlen($password)),
    ],
    'auth' => [],
    'track' => [],
];

// ── Step 1: Authentication ──
$authUrl = 'https://blktracksvc.dtdc.com/dtdc-api/api/dtdc/authenticate'
    . '?username=' . urlencode($username)
    . '&password=' . urlencode($password);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $authUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Accept: text/plain',
        'User-Agent: CareyGo/1.0',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$authResponse = curl_exec($ch);
$authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$authError = curl_error($ch);
curl_close($ch);

$token = trim($authResponse);

$debug['auth'] = [
    'url' => $authUrl,
    'method' => 'GET',
    'headers' => [
        'Accept' => 'text/plain',
        'User-Agent' => 'CareyGo/1.0',
    ],
    'statusCode' => $authHttpCode,
    'response' => $authResponse,
    'token' => $token ? substr($token, 0, 50) . '...' : 'FAILED',
    'error' => $authError ?: null,
];

// ── Step 2: Tracking (only if auth succeeded) ──
if ($token) {
    $trackUrl = 'https://blktracksvc.dtdc.com/dtdc-api/rest/JSONCnTrk/getTrackDetails';

    $trackPayload = [
        'trkType'   => 'cnno',
        'strcnno'   => $awb,
        'addtnlDtl' => 'Y',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $trackUrl,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($trackPayload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: CareyGo/1.0',
            'X-Access-Token: ' . $token,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $trackResponse = curl_exec($ch);
    $trackHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $trackError = curl_error($ch);
    curl_close($ch);

    $trackDecoded = json_decode($trackResponse, true);

    $debug['track'] = [
        'url' => $trackUrl,
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'CareyGo/1.0',
            'X-Access-Token' => substr($token, 0, 50) . '...',
        ],
        'request' => $trackPayload,
        'statusCode' => $trackHttpCode,
        'response' => $trackDecoded ?: $trackResponse,
        'error' => $trackError ?: null,
    ];
} else {
    $debug['track'] = [
        'error' => 'Authentication failed - unable to proceed with tracking request',
    ];
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
