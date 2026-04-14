<?php
/**
 * JWT & Application Configuration
 * Values can be overridden via environment variables for production.
 */

// ── Helper: read env var or fall back to a default ───────────
if (!function_exists('_cfg')) {
    function _cfg(string $key, string $default): string {
        $v = $_ENV[$key] ?? getenv($key);
        return ($v !== false && $v !== '') ? $v : $default;
    }
}

// CHANGE THIS on production — set CGO_JWT_SECRET in server env
define('JWT_SECRET',  _cfg('CGO_JWT_SECRET',  'CGo_S3cr3t_K3y_2026_!ChangeInProd'));
define('JWT_ALGO',    'HS256');
define('JWT_EXPIRY',  (int) _cfg('CGO_JWT_EXPIRY',  (string)(60 * 60 * 8)));  // 8 hours
define('JWT_COOKIE',  'cg_token');
define('JWT_ISSUER',  _cfg('CGO_SITE_HOST',   'careygo.in'));

// Detect HTTPS automatically; override via CGO_SITE_URL env var
$_defaultSiteUrl = (
    ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443)
        ? 'https' : 'http'
) . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/careygo';

define('SITE_URL',    _cfg('CGO_SITE_URL', $_defaultSiteUrl));
define('ADMIN_EMAIL', _cfg('CGO_ADMIN_EMAIL', 'admin@careygo.in'));

// HTTPS flag — used for secure cookies
define('IS_HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443);
