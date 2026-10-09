<?php
/**
 * POST /auth/reset-password.php
 * Accepts JSON: { token, password, confirm_password }
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

function ensurePasswordResetTable(PDO $pdo): void
{
    static $done = false;
    if ($done) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        email VARCHAR(191) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        request_ip VARCHAR(64) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_token_hash (token_hash),
        KEY idx_user_id (user_id),
        KEY idx_email (email),
        KEY idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $done = true;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim($body['token'] ?? '');
$password = (string)($body['password'] ?? '');
$confirmPassword = (string)($body['confirm_password'] ?? '');

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    json_response(['success' => false, 'message' => 'Reset link is invalid or expired.'], 422);
}
if (strlen($password) < 8) {
    json_response(['success' => false, 'message' => 'Password must be at least 8 characters.'], 422);
}
if ($password !== $confirmPassword) {
    json_response(['success' => false, 'message' => 'Passwords do not match.'], 422);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimit('reset_password_ip_' . $ip, 10, 900)) {
    json_response(['success' => false, 'message' => 'Too many reset attempts. Please try again later.'], 429);
}

ensurePasswordResetTable($pdo);

$tokenHash = hash('sha256', $token);

$stmt = $pdo->prepare("
    SELECT pr.id, pr.user_id, u.role
    FROM password_resets pr
    INNER JOIN users u ON u.id = pr.user_id
    WHERE pr.token_hash = ?
      AND pr.used_at IS NULL
      AND pr.expires_at > NOW()
      AND u.role IN ('admin', 'customer')
    LIMIT 1
");
$stmt->execute([$tokenHash]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    json_response(['success' => false, 'message' => 'Reset link is invalid or expired. Please request a new link.'], 422);
}

$newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo->beginTransaction();
try {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([$newHash, (int)$reset['user_id']]);
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
        ->execute([(int)$reset['user_id']]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('PASSWORD_RESET_FAILED: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Unable to reset password. Please try again.'], 500);
}

json_response(['success' => true, 'message' => 'Password reset successfully. You can now sign in.']);
