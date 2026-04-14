<?php
/**
 * GET  /api/addresses.php          — list saved addresses for logged-in customer
 * POST /api/addresses.php          — save new address
 * DELETE /api/addresses.php?id=X   — delete address
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'customer') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$userId = (int) $user['sub'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'addresses' => $addresses]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Failed to fetch addresses.'], 500);
    }
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $fullName  = trim($body['full_name']     ?? '');
    $phone     = trim($body['phone']         ?? '');
    $addr1     = trim($body['address_line1'] ?? '');
    $addr2     = trim($body['address_line2'] ?? '');
    $city      = trim($body['city']          ?? '');
    $state     = trim($body['state']         ?? '');
    $pincode   = trim($body['pincode']        ?? '');
    $label     = trim($body['label']         ?? '');
    $isDefault = (int) ($body['is_default']  ?? 0);

    if (!$fullName || !$phone || !$addr1 || !$city || !$state || !$pincode) {
        json_response(['success' => false, 'message' => 'All required fields must be filled.'], 422);
    }

    try {
        // If setting as default, unset others
        if ($isDefault) {
            $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO addresses (user_id, label, full_name, phone, address_line1, address_line2, city, state, pincode, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $label, $fullName, $phone, $addr1, $addr2, $city, $state, $pincode, $isDefault]);

        json_response(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Failed to save address.'], 500);
    }
}

if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? $_GET['id'] ?? 0);

    if (!$id) json_response(['success' => false, 'message' => 'Invalid ID.'], 422);

    try {
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        json_response(['success' => true]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Delete failed.'], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
