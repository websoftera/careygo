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

if ($method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) json_response(['success' => false, 'message' => 'Missing ID.'], 422);

    $stmt = $pdo->prepare("SELECT u.*, COALESCE(s.cnt,0) AS total_shipments
        FROM users u
        LEFT JOIN (SELECT customer_id, COUNT(*) as cnt FROM shipments GROUP BY customer_id) s ON s.customer_id = u.id
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

    if (!$id || !in_array($status, ['approved','rejected','pending'])) {
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
