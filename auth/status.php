<?php
/**
 * GET /auth/status.php
 * Returns current user's approval status as JSON
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');

$authUser = auth_user();
if (!$authUser) {
    json_response(['success' => false, 'status' => 'unauthenticated'], 401);
}

$stmt = $pdo->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(['success' => false, 'status' => 'not_found'], 404);
}

json_response(['success' => true, 'status' => $user['status']]);
