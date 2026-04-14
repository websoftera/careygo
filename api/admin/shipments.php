<?php
/**
 * Admin API — Shipments
 * GET  ?id=X   — fetch single shipment detail
 * POST          — update shipment status {id, status}
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

    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
        FROM shipments s JOIN users u ON u.id = s.customer_id
        WHERE s.id = ?");
    $stmt->execute([$id]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shipment) json_response(['success' => false, 'message' => 'Shipment not found.'], 404);
    json_response(['success' => true, 'shipment' => $shipment]);
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int) ($body['id']     ?? 0);
    $status = trim($body['status'] ?? '');

    $allowed = ['booked','picked_up','in_transit','out_for_delivery','delivered','cancelled'];
    if (!$id || !in_array($status, $allowed)) {
        json_response(['success' => false, 'message' => 'Invalid parameters.'], 422);
    }

    $stmt = $pdo->prepare("UPDATE shipments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    if ($stmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Shipment not found.'], 404);
    }

    json_response(['success' => true, 'message' => 'Status updated.']);
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
