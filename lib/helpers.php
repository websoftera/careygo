<?php
/**
 * Careygo — Shared Helper Functions
 */

/**
 * Add N business days (Mon–Sat) to a date and return a formatted string.
 */
function addBusinessDays(int $days, string $format = 'Y-m-d', string $from = 'now'): string
{
    $date  = new DateTime($from === 'now' ? 'now' : $from);
    $added = 0;
    while ($added < $days) {
        $date->modify('+1 day');
        $dow = (int) $date->format('N'); // 1=Mon … 7=Sun
        if ($dow < 7) $added++;          // Mon–Sat only (skip Sunday)
    }
    return $date->format($format);
}

/**
 * Map service_type to pincode_tat column name.
 */
function tatColumn(string $serviceType): string
{
    $map = [
        'standard'  => 'tat_standard',
        'premium'   => 'tat_premium',
        'air_cargo' => 'tat_air',
        'surface'   => 'tat_surface',
    ];
    return $map[$serviceType] ?? 'tat_standard';
}

/**
 * Read a value from $_ENV / getenv() with a fallback.
 */
function env(string $key, string $default = ''): string
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Generate a cryptographically secure CSRF token and store it in session.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token against the session token.
 * Returns true on success, false on failure.
 */
function csrf_verify(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $stored = $_SESSION['csrf_token'] ?? '';
    return $stored && hash_equals($stored, $token);
}

/**
 * Enforce rate limiting using file-based counters.
 * Returns true if the request should be allowed, false if rate-limited.
 *
 * @param string $key     Unique key (e.g. "login_1.2.3.4")
 * @param int    $limit   Max attempts
 * @param int    $window  Time window in seconds
 */
function rateLimit(string $key, int $limit = 10, int $window = 60): bool
{
    $dir  = sys_get_temp_dir() . '/cgo_rl/';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);

    $file = $dir . md5($key) . '.json';
    $now  = time();

    $data = ['count' => 0, 'reset' => $now + $window];
    if (file_exists($file)) {
        $raw = @json_decode(file_get_contents($file), true);
        if ($raw && $raw['reset'] > $now) {
            $data = $raw;
        }
    }

    if ($data['count'] >= $limit) return false;

    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

/**
 * Sanitize a string for safe HTML output (alias for readability).
 */
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Format currency in Indian locale.
 */
function formatINR(float $amount, bool $symbol = true): string
{
    $formatted = number_format($amount, 2, '.', ',');
    return $symbol ? '₹' . $formatted : $formatted;
}

/**
 * Return a safe service type label.
 */
function serviceLabel(string $type): string
{
    $labels = [
        'standard'  => 'Standard Express',
        'premium'   => 'Premium Express',
        'air_cargo' => 'Air Cargo',
        'surface'   => 'Surface Cargo',
    ];
    return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
}

/**
 * Return badge HTML for a shipment/user status.
 */
function statusBadge(string $status): string
{
    return '<span class="badge-status badge-' . h($status) . '">'
        . h(ucwords(str_replace('_', ' ', $status)))
        . '</span>';
}

/**
 * Determine the shipping zone from pickup and delivery city + state strings.
 *
 * Priority:
 *   1. Same city  → within_city
 *   2. Same state → within_state
 *   3. Either city is a recognised Metro → metro
 *   4. Anything else → rest_of_india
 *
 * @return string  One of: within_city | within_state | metro | rest_of_india
 */
function determineZone(string $pickupCity, string $pickupState, string $deliveryCity, string $deliveryState): string
{
    $nc = static fn(string $s): string => strtolower(trim(preg_replace('/\s+/', ' ', $s)));

    $pCity  = $nc($pickupCity);
    $dCity  = $nc($deliveryCity);
    $pState = $nc($pickupState);
    $dState = $nc($deliveryState);

    if ($pCity === $dCity && $pState === $dState) {
        return 'within_city';
    }

    if ($pState === $dState) {
        return 'within_state';
    }

    // Metro zone: BOTH pickup AND delivery must be metro cities
    static $metros = [
        'delhi', 'new delhi', 'mumbai', 'bombay', 'bangalore', 'bengaluru',
        'chennai', 'madras', 'kolkata', 'calcutta', 'hyderabad', 'pune',
        'ahmedabad', 'noida', 'gurugram', 'gurgaon', 'thane', 'navi mumbai',
    ];

    if (in_array($pCity, $metros, true) && in_array($dCity, $metros, true)) {
        return 'metro';  // Metro-to-Metro: both cities must be metro
    }

    return 'rest_of_india';
}
