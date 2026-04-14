<?php
/**
 * Authentication helper functions.
 * Requires: config/jwt.php, config/database.php, lib/JWT.php
 */

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../lib/JWT.php';

// ---------------------------------------------------------------
// Issue a token and set HttpOnly cookie
// ---------------------------------------------------------------
function auth_issue_token(array $user): string
{
    $jti = bin2hex(random_bytes(16));
    $payload = [
        'iss'  => JWT_ISSUER,
        'iat'  => time(),
        'exp'  => time() + JWT_EXPIRY,
        'jti'  => $jti,
        'sub'  => (int) $user['id'],
        'role' => $user['role'],
        'name' => $user['full_name'],
        'status' => $user['status'],
    ];

    $token = JWT::encode($payload, JWT_SECRET, JWT_ALGO);

    // Secure, HttpOnly cookie — secure flag auto-set on HTTPS
    setcookie(
        JWT_COOKIE,
        $token,
        [
            'expires'  => time() + JWT_EXPIRY,
            'path'     => '/',
            'secure'   => defined('IS_HTTPS') ? IS_HTTPS : false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]
    );

    return $token;
}

// ---------------------------------------------------------------
// Get currently authenticated payload (or null)
// ---------------------------------------------------------------
function auth_user(): ?array
{
    $token = $_COOKIE[JWT_COOKIE] ?? null;
    if (!$token) {
        return null;
    }

    try {
        $payload = JWT::decode($token, JWT_SECRET, JWT_ALGO);

        // Check blacklist
        global $pdo;
        if (isset($pdo)) {
            $stmt = $pdo->prepare('SELECT id FROM token_blacklist WHERE jti = ? AND expires_at > NOW()');
            $stmt->execute([$payload['jti']]);
            if ($stmt->fetch()) {
                return null; // revoked
            }
        }

        return $payload;
    } catch (RuntimeException $e) {
        return null;
    }
}

// ---------------------------------------------------------------
// Require a logged-in user of a given role; redirect otherwise
// ---------------------------------------------------------------
function auth_require(string $role = 'customer'): array
{
    $user = auth_user();
    if (!$user) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    if ($user['role'] !== $role) {
        if ($user['role'] === 'admin') {
            header('Location: ' . SITE_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . SITE_URL . '/customer/dashboard.php');
        }
        exit;
    }
    return $user;
}

// ---------------------------------------------------------------
// Logout — blacklist current token and clear cookie
// ---------------------------------------------------------------
function auth_logout(): void
{
    $token = $_COOKIE[JWT_COOKIE] ?? null;
    if ($token) {
        try {
            $payload = JWT::decode($token, JWT_SECRET, JWT_ALGO);
            global $pdo;
            if (isset($pdo) && isset($payload['jti'])) {
                $stmt = $pdo->prepare(
                    'INSERT IGNORE INTO token_blacklist (jti, expires_at) VALUES (?, FROM_UNIXTIME(?))'
                );
                $stmt->execute([$payload['jti'], $payload['exp']]);
            }
        } catch (RuntimeException $e) {
            // ignore decode errors on logout
        }
    }

    // Clear cookie
    setcookie(JWT_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => defined('IS_HTTPS') ? IS_HTTPS : false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

// ---------------------------------------------------------------
// JSON response helper
// ---------------------------------------------------------------
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
