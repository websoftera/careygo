<?php
/**
 * Admin API — Pricing Slabs
 * POST   — create or update a slab
 * DELETE — delete a slab
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $id             = !empty($body['id']) ? (int)$body['id'] : null;
    $serviceType    = trim($body['service_type']    ?? '');
    $weightFrom     = (float) ($body['weight_from']     ?? 0);
    $weightTo       = isset($body['weight_to']) && $body['weight_to'] !== null && $body['weight_to'] !== '' ? (float)$body['weight_to'] : null;
    $basePrice      = (float) ($body['base_price']       ?? 0);
    $incrementPrice = isset($body['increment_price']) && $body['increment_price'] !== null && $body['increment_price'] !== '' ? (float)$body['increment_price'] : null;
    $incrementPerKg = (float) ($body['increment_per_kg'] ?? 0.5);
    $sortOrder      = (int)   ($body['sort_order']       ?? 1);

    $allowed = ['standard','premium','air_cargo','surface'];
    if (!in_array($serviceType, $allowed)) {
        json_response(['success' => false, 'message' => 'Invalid service type.'], 422);
    }
    if ($basePrice < 0) {
        json_response(['success' => false, 'message' => 'Base price cannot be negative.'], 422);
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE pricing_slabs SET
                    service_type = ?, weight_from = ?, weight_to = ?,
                    base_price = ?, increment_price = ?, increment_per_kg = ?, sort_order = ?
                WHERE id = ?");
            $stmt->execute([$serviceType, $weightFrom, $weightTo, $basePrice, $incrementPrice, $incrementPerKg, $sortOrder, $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO pricing_slabs (service_type, weight_from, weight_to, base_price, increment_price, increment_per_kg, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$serviceType, $weightFrom, $weightTo, $basePrice, $incrementPrice, $incrementPerKg, $sortOrder]);
            $id = (int) $pdo->lastInsertId();
        }
        json_response(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);
    if (!$id) json_response(['success' => false, 'message' => 'Invalid ID.'], 422);

    try {
        $pdo->prepare("DELETE FROM pricing_slabs WHERE id = ?")->execute([$id]);
        json_response(['success' => true]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Delete failed.'], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
