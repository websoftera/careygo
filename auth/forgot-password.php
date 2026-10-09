<?php
/**
 * POST /auth/forgot-password.php
 * Accepts JSON: { email }
 * Sends a password reset link for admin or customer accounts.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/email.php';

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

function resetEmailBody(string $name, string $resetUrl): string
{
    $safeName = htmlspecialchars($name ?: 'Careygo User', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset your Careygo password</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:28px 16px;">
        <div style="background:#ffffff;border-radius:14px;padding:28px;box-shadow:0 8px 28px rgba(0,26,147,.08);">
            <h2 style="margin:0 0 12px;color:#001A93;">Reset your password</h2>
            <p style="font-size:14px;line-height:1.6;margin:0 0 14px;">Hello {$safeName},</p>
            <p style="font-size:14px;line-height:1.6;margin:0 0 20px;">We received a request to reset your Careygo account password. Use the button below to create a new password.</p>
            <p style="margin:0 0 22px;">
                <a href="{$safeUrl}" style="display:inline-block;background:#001A93;color:#ffffff;text-decoration:none;border-radius:10px;padding:12px 18px;font-weight:700;font-size:14px;">Reset Password</a>
            </p>
            <p style="font-size:13px;line-height:1.6;color:#6b7280;margin:0 0 12px;">This link expires in 60 minutes. If you did not request this, you can safely ignore this email.</p>
            <p style="font-size:12px;line-height:1.5;color:#6b7280;margin:18px 0 0;">If the button does not work, open this link:<br><span style="word-break:break-all;">{$safeUrl}</span></p>
        </div>
        <p style="text-align:center;color:#9ca3af;font-size:12px;margin:18px 0 0;">&copy; {$year} Careygo Logistics</p>
    </div>
</body>
</html>
HTML;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim(strtolower($body['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimit('forgot_password_ip_' . $ip, 5, 900) || !rateLimit('forgot_password_email_' . $email, 3, 900)) {
    json_response(['success' => false, 'message' => 'Too many reset requests. Please try again later.'], 429);
}

ensurePasswordResetTable($pdo);

$genericMessage = 'If this email is registered, a password reset link has been sent.';

$stmt = $pdo->prepare("SELECT id, full_name, email, role, status FROM users WHERE email = ? AND role IN ('admin', 'customer') LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(['success' => true, 'message' => $genericMessage]);
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expiresAt = (new DateTimeImmutable('+60 minutes'))->format('Y-m-d H:i:s');

$pdo->beginTransaction();
try {
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
        ->execute([(int)$user['id']]);
    $pdo->prepare('INSERT INTO password_resets (user_id, email, token_hash, expires_at, request_ip) VALUES (?, ?, ?, ?, ?)')
        ->execute([(int)$user['id'], $user['email'], $tokenHash, $expiresAt, $ip]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('PASSWORD_RESET_CREATE_FAILED: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Unable to process reset request. Please try again.'], 500);
}

$resetUrl = rtrim(SITE_URL, '/') . '/reset-password.php?token=' . urlencode($token);
$mailer = new EmailService();
$sent = $mailer->sendFormNotification(
    $user['email'],
    $user['full_name'] ?: 'Careygo User',
    'Reset your Careygo password',
    resetEmailBody($user['full_name'] ?: 'Careygo User', $resetUrl)
);

if (!$sent) {
    error_log('PASSWORD_RESET_EMAIL_FAILED: ' . $user['email']);
}

json_response(['success' => true, 'message' => $genericMessage]);
