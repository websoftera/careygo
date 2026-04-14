<?php
/**
 * POST /auth/login.php
 * Accepts JSON: { email, password }
 * Returns JSON with JWT cookie set
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);

$email    = trim(strtolower($body['email']    ?? ''));
$password = $body['password'] ?? '';

// Basic input validation
if (empty($email) || empty($password)) {
    json_response(['success' => false, 'message' => 'Email and password are required.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address.'], 422);
}

// Rate limit: 10 attempts per 10 minutes per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimit("login_{$ip}", 10, 600)) {
    json_response(['success' => false, 'message' => 'Too many login attempts. Please try again in 10 minutes.'], 429);
}

// Fetch user
$stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
}

// Issue token
auth_issue_token($user);

// Determine redirect
$redirect = SITE_URL . '/customer/pending.php';
if ($user['role'] === 'admin') {
    $redirect = SITE_URL . '/admin/dashboard.php';
} elseif ($user['status'] === 'approved') {
    $redirect = SITE_URL . '/customer/dashboard.php';
} elseif ($user['status'] === 'rejected') {
    $redirect = SITE_URL . '/customer/pending.php';
}

json_response([
    'success'  => true,
    'message'  => 'Login successful.',
    'redirect' => $redirect,
    'user'     => [
        'id'     => (int) $user['id'],
        'name'   => $user['full_name'],
        'role'   => $user['role'],
        'status' => $user['status'],
    ],
]);
