<?php
/**
 * POST /api/admin/settings.php — save a setting
 * GET  /api/admin/settings.php?key=packing_charge — get a setting
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

// Ensure settings table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $key = trim($_GET['key'] ?? '');
    if (!$key) { json_response(['success' => false, 'message' => 'Key required.'], 422); }
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        json_response(['success' => true, 'key' => $key, 'value' => $value !== false ? $value : null]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Database error.'], 500);
    }
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $key  = trim($body['key'] ?? '');
    $val  = $body['value'] ?? '';

    if (!$key) { json_response(['success' => false, 'message' => 'Key required.'], 422); }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $val]);
        json_response(['success' => true, 'key' => $key, 'value' => $val]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
