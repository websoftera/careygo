<?php
/**
 * Database Configuration
 * Credentials can be overridden via environment variables for production.
 */

// ── Helper: read env var or fall back to a default ───────────
if (!function_exists('_cfg')) {
    function _cfg(string $key, string $default): string
    {
        $v = $_ENV[$key] ?? getenv($key);
        return ($v !== false && $v !== '') ? $v : $default;
    }
}

// define('DB_HOST', _cfg('CGO_DB_HOST', 'localhost'));
// define('DB_USER', _cfg('CGO_DB_USER', 'root'));
// define('DB_PASS', _cfg('CGO_DB_PASS', ''));
// define('DB_NAME', _cfg('CGO_DB_NAME', 'careygo'));

define('DB_HOST', _cfg('CGO_DB_HOST', 'localhost'));
define('DB_USER', _cfg('CGO_DB_USER', 'u141519101_careygo'));
define('DB_PASS', _cfg('CGO_DB_PASS', '+DgrP256'));
define('DB_NAME', _cfg('CGO_DB_NAME', 'u141519101_careygo'));

// ── Production credentials (set via env vars instead) ────────
CGO_DB_HOST=localhost
CGO_DB_USER=u141519101_careygo
CGO_DB_PASS=+DgrP256
CGO_DB_NAME=u141519101_careygo

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Don't leak DB details in production
    $msg = defined('IS_HTTPS') && IS_HTTPS
        ? 'Service temporarily unavailable. Please try again later.'
        : 'Database connection failed: ' . $e->getMessage();
    http_response_code(503);
    die($msg);
}
