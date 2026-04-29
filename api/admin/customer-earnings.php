<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

function ensureCustomerEarningPlanTables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_earning_slabs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        customer_id INT UNSIGNED NOT NULL,
        pricing_slab_id INT UNSIGNED NOT NULL,
        earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_customer_slab (customer_id, pricing_slab_id),
        INDEX idx_customer_id (customer_id),
        INDEX idx_pricing_slab_id (pricing_slab_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

try {
    ensureCustomerEarningPlanTables($pdo);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Unable to prepare earning table.'], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$customerId = (int)($body['customer_id'] ?? 0);
$entries = $body['entries'] ?? [];

if (!$customerId || !is_array($entries)) {
    json_response(['success' => false, 'message' => 'Invalid parameters.'], 422);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$customerId]);
if (!$stmt->fetchColumn()) {
    json_response(['success' => false, 'message' => 'Customer not found.'], 404);
}

try {
    $pdo->beginTransaction();
    $save = $pdo->prepare("
        INSERT INTO customer_earning_slabs (customer_id, pricing_slab_id, earning_pct)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE earning_pct = VALUES(earning_pct)
    ");

    foreach ($entries as $entry) {
        $slabId = (int)($entry['pricing_slab_id'] ?? 0);
        $pct = round((float)($entry['earning_pct'] ?? 0), 2);
        if (!$slabId || $pct < 0 || $pct > 100) {
            throw new RuntimeException('Earning percentage must be between 0 and 100.');
        }
        $save->execute([$customerId, $slabId, $pct]);
    }

    $pdo->commit();
    json_response(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $e->getMessage()], 422);
}
