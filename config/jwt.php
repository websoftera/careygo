<?php
/**
 * JWT & Application Configuration
 * Values can be overridden via environment variables for production.
 */

// ── Load .env file if it exists ───────────────────────────
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue; // Skip invalid lines
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty($key) && !isset($_ENV[$key]) && !getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ── Helper: read env var or fall back to a default ───────────
if (!function_exists('_cfg')) {
    function _cfg(string $key, string $default): string {
        $v = $_ENV[$key] ?? getenv($key);
        return ($v !== false && $v !== '') ? $v : $default;
    }
}

// HTTPS flag — used for secure cookies and URL scheme
define('IS_HTTPS',
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? 80) == 443
);

// ── Auto-detect base URL (works in subfolder on localhost AND at domain root on production) ──
// Logic: compare the app's real filesystem path against the web server's document root.
// e.g.  local:  docRoot=/xampp/htdocs  appRoot=/xampp/htdocs/careygo  → subPath=/careygo
//       prod:   docRoot=/home/.../careygo  appRoot=/home/.../careygo  → subPath=''
$_docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');   // parent of config/ = app root
$_subPath = ($_docRoot !== '' && str_starts_with($_appRoot, $_docRoot))
    ? substr($_appRoot, strlen($_docRoot))   // e.g. "/careygo" or ""
    : '';
$_scheme  = IS_HTTPS ? 'https' : 'http';
$_host    = rtrim($_SERVER['HTTP_HOST'] ?? 'localhost', '/');
$_autoUrl = $_scheme . '://' . $_host . $_subPath;

// Override via CGO_SITE_URL environment variable (recommended on production)
// e.g. in .htaccess:  SetEnv CGO_SITE_URL https://careygo.everythingb2c.in
define('SITE_URL',    rtrim(_cfg('CGO_SITE_URL', $_autoUrl), '/'));

define('JWT_SECRET',  _cfg('CGO_JWT_SECRET',  'CGo_S3cr3t_K3y_2026_!ChangeInProd'));
define('JWT_ALGO',    'HS256');
define('JWT_EXPIRY',  (int) _cfg('CGO_JWT_EXPIRY', (string)(60 * 60 * 8)));  // 8 hours
define('JWT_COOKIE',  'cg_token');
define('JWT_ISSUER',  _cfg('CGO_SITE_HOST',   'careygo.in'));
define('ADMIN_EMAIL', _cfg('CGO_ADMIN_EMAIL', 'admin@careygo.in'));
