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

// Use a database-specific reader so no other application helper can override
// the database credentials selected from this deployment's .env file.
function cgo_database_env(string $key, string $default): string
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST', cgo_database_env('CGO_DB_HOST', 'localhost'));
define('DB_USER', cgo_database_env('CGO_DB_USER', 'root'));
define('DB_PASS', cgo_database_env('CGO_DB_PASS', ''));
define('DB_NAME', cgo_database_env('CGO_DB_NAME', 'careygo'));

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
