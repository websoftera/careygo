<?php
/**
 * GET  /api/profile.php        — return current user's profile
 * POST /api/profile.php        — update name / phone / company
 * POST /api/profile.php?action=password — change password
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

$authUser = auth_user();
if (!$authUser || $authUser['role'] !== 'customer') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}
$userId = (int) $authUser['sub'];

$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? '');

// ── GET — return profile ──────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, company_name, status, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        json_response(['success' => false, 'message' => 'User not found.'], 404);
    }
    json_response(['success' => true, 'user' => $user]);
}

if ($method !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── POST ?action=password — change password ───────────────────
if ($action === 'password') {
    $currentPw  = $body['current_password'] ?? '';
    $newPw      = $body['new_password']     ?? '';
    $confirmPw  = $body['confirm_password'] ?? '';

    if (empty($currentPw) || empty($newPw) || empty($confirmPw)) {
        json_response(['success' => false, 'message' => 'All password fields are required.'], 422);
    }
    if (strlen($newPw) < 8) {
        json_response(['success' => false, 'message' => 'New password must be at least 8 characters.'], 422);
    }
    if ($newPw !== $confirmPw) {
        json_response(['success' => false, 'message' => 'New passwords do not match.'], 422);
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($currentPw, $hash)) {
        json_response(['success' => false, 'message' => 'Current password is incorrect.'], 401);
    }

    $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $userId]);

    json_response(['success' => true, 'message' => 'Password changed successfully.']);
}

// ── POST — update profile ─────────────────────────────────────
$fullName    = trim($body['full_name']    ?? '');
$phone       = trim($body['phone']       ?? '');
$companyName = trim($body['company_name'] ?? '');

$errors = [];
if (strlen($fullName) < 2) {
    $errors[] = 'Full name must be at least 2 characters.';
}
if (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
    $errors[] = 'Invalid phone number.';
}
if (!empty($errors)) {
    json_response(['success' => false, 'message' => implode(' ', $errors)], 422);
}

$pdo->prepare('UPDATE users SET full_name = ?, phone = ?, company_name = ? WHERE id = ?')
    ->execute([$fullName, $phone, $companyName ?: null, $userId]);

json_response(['success' => true, 'message' => 'Profile updated successfully.']);
