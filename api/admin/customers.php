<?php
/**
 * Admin API — Customers
 * GET  ?id=X   — fetch single customer detail
 * POST          — update customer status {id, status}
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

function ensureCustomerEarningColumns(PDO $pdo): void
{
    static $done = false;
    if ($done) return;

    try {
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_earning_pct', $userCols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN customer_earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        }

        $shipmentCols = $pdo->query("SHOW COLUMNS FROM shipments")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_earning_pct', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN customer_earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        }
        if (!in_array('customer_earning_amount', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN customer_earning_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (Exception $e) {
        @error_log('Customer earning column check failed: ' . $e->getMessage());
    }

    $done = true;
}

ensureCustomerEarningColumns($pdo);

if ($method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) json_response(['success' => false, 'message' => 'Missing ID.'], 422);

    $stmt = $pdo->prepare("SELECT u.*, COALESCE(s.cnt,0) AS total_shipments, COALESCE(s.total_earnings,0) AS total_earnings
        FROM users u
        LEFT JOIN (
            SELECT customer_id, COUNT(*) as cnt, SUM(customer_earning_amount) AS total_earnings
            FROM shipments
            GROUP BY customer_id
        ) s ON s.customer_id = u.id
        WHERE u.id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) json_response(['success' => false, 'message' => 'Customer not found.'], 404);

    unset($customer['password_hash']);
    json_response(['success' => true, 'customer' => $customer]);
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int) ($body['id']     ?? 0);
    $status = trim($body['status'] ?? '');

    if (!$id) {
        json_response(['success' => false, 'message' => 'Invalid parameters.'], 422);
    }

    if (array_key_exists('earning_pct', $body)) {
        if (!is_numeric($body['earning_pct'])) {
            json_response(['success' => false, 'message' => 'Earning percentage must be a number.'], 422);
        }
        $earningPct = round((float)$body['earning_pct'], 2);
        if ($earningPct < 0 || $earningPct > 100) {
            json_response(['success' => false, 'message' => 'Earning percentage must be between 0 and 100.'], 422);
        }

        $stmt = $pdo->prepare("UPDATE users SET customer_earning_pct = ? WHERE id = ? AND role = 'customer'");
        $stmt->execute([$earningPct, $id]);

        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer'");
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                json_response(['success' => false, 'message' => 'Customer not found.'], 404);
            }
        }

        json_response(['success' => true, 'message' => 'Customer earning percentage updated.', 'earning_pct' => $earningPct]);
    }

    if (!in_array($status, ['approved','rejected','pending'])) {
        json_response(['success' => false, 'message' => 'Invalid parameters.'], 422);
    }

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'customer'");
    $stmt->execute([$status, $id]);

    if ($stmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Customer not found.'], 404);
    }

    json_response(['success' => true, 'message' => "Customer $status successfully."]);
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
