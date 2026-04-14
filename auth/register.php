<?php
/**
 * POST /auth/register.php
 * Accepts JSON: { full_name, email, phone, company_name, password, confirm_password }
 * Returns JSON — creates user with status=pending
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

// Rate limit: 5 registrations per hour per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimit("register_{$ip}", 5, 3600)) {
    json_response(['success' => false, 'message' => 'Too many registration attempts. Please try again later.'], 429);
}

$body = json_decode(file_get_contents('php://input'), true);

$full_name        = trim($body['full_name']        ?? '');
$email            = trim(strtolower($body['email'] ?? ''));
$phone            = trim($body['phone']            ?? '');
$company_name     = trim($body['company_name']     ?? '');
$password         = $body['password']              ?? '';
$confirm_password = $body['confirm_password']      ?? '';

// Validation
$errors = [];

if (strlen($full_name) < 2) {
    $errors[] = 'Full name must be at least 2 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}

if (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
    $errors[] = 'Invalid phone number.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    json_response(['success' => false, 'message' => implode(' ', $errors)], 422);
}

// Check duplicate email
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'An account with this email already exists.'], 409);
}

// Hash password
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Insert user
$stmt = $pdo->prepare(
    'INSERT INTO users (full_name, email, phone, company_name, password_hash, role, status)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $full_name,
    $email,
    $phone,
    $company_name ?: null,
    $hash,
    'customer',
    'pending',
]);

$userId = (int) $pdo->lastInsertId();

// Issue token so they are logged in immediately (pending)
$newUser = [
    'id'        => $userId,
    'full_name' => $full_name,
    'email'     => $email,
    'role'      => 'customer',
    'status'    => 'pending',
];
auth_issue_token($newUser);

json_response([
    'success'  => true,
    'message'  => 'Account created! Your account is awaiting admin approval.',
    'redirect' => SITE_URL . '/customer/pending.php',
]);
