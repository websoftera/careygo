<?php
/**
 * Admin middleware — include at top of every admin page.
 * Ensures: authenticated + role=admin
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

$authUser = auth_require('admin');

// Re-fetch fresh data
$stmt = $pdo->prepare('SELECT id, full_name, email, role, status FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$authUser['sub']]);
$adminData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminData) {
    auth_logout();
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}
