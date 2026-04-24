<?php
/**
 * GET /api/settings.php?key=packing_charge
 * Returns public-readable settings (non-sensitive only).
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$key = trim($_GET['key'] ?? '');

// Allowed public keys
$allowedKeys = ['packing_charge', 'site_name'];

if (!$key || !in_array($key, $allowedKeys, true)) {
    json_response(['success' => false, 'message' => 'Invalid or missing key.'], 422);
}

try {
    // Check if settings table exists, create if not
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();

    // Defaults for missing keys
    $defaults = ['packing_charge' => '50', 'site_name' => 'Careygo'];
    if ($value === false) {
        $value = $defaults[$key] ?? '';
    }

    json_response(['success' => true, 'key' => $key, 'value' => $value]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Could not load setting.'], 500);
}
