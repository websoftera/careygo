<?php
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($envLines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // The deployment .env file is authoritative. This prevents inherited
        // hosting variables with stale credentials from overriding it.
        if ($key !== '') {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
/**
 * Database Configuration
 * Credentials can be overridden via environment variables for production.
 */

// ── Helper: read env var or fall back to a local-development default ──────
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
    // Never expose connection details (hostnames, users, or driver errors) to visitors.
    error_log('Careygo database connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('Service temporarily unavailable. Please try again later.');
}
