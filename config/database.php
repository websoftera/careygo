<?php
/**
 * Database Configuration
 * Credentials can be overridden via environment variables for production.
 */

// Load .env before reading database settings. Most entry points include this
// file before config/jwt.php, which is otherwise responsible for loading it.
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key !== '' && !isset($_ENV[$key]) && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// ── Helper: read env var or fall back to a default ───────────
if (!function_exists('_cfg')) {
    function _cfg(string $key, string $default): string
    {
        $v = $_ENV[$key] ?? getenv($key);
        return ($v !== false && $v !== '') ? $v : $default;
    }
}

define('DB_HOST', _cfg('CGO_DB_HOST', 'localhost'));
define('DB_USER', _cfg('CGO_DB_USER', 'root'));
define('DB_PASS', _cfg('CGO_DB_PASS', ''));
define('DB_NAME', _cfg('CGO_DB_NAME', 'careygo'));

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
    require_once __DIR__ . '/../lib/migrations.php';
    cgo_run_auto_migrations($pdo);
} catch (PDOException $e) {
    // Don't leak DB details in production
    $msg = defined('IS_HTTPS') && IS_HTTPS
        ? 'Service temporarily unavailable. Please try again later.'
        : 'Database connection failed: ' . $e->getMessage();
    http_response_code(503);
    die($msg);
}
