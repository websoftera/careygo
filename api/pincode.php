<?php
/**
 * GET /api/pincode.php?pincode=411001
 * Returns city, state, TAT details for a pincode
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$pincode = trim($_GET['pincode'] ?? '');

if (!$pincode || !preg_match('/^\d{6}$/', $pincode)) {
    json_response(['success' => false, 'message' => 'Invalid pincode format.'], 422);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM pincode_tat WHERE pincode = ? LIMIT 1");
    $stmt->execute([$pincode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_response(['success' => false, 'message' => 'Pincode not found in our database.'], 404);
    }

    if (!$row['serviceable']) {
        json_response(['success' => false, 'message' => 'This pincode is currently not serviceable.'], 422);
    }

    json_response([
        'success' => true,
        'data' => [
            'pincode'      => $row['pincode'],
            'city'         => $row['city'],
            'state'        => $row['state'],
            'zone'         => $row['zone'],
            'tat_standard' => (int) $row['tat_standard'],
            'tat_premium'  => (int) $row['tat_premium'],
            'tat_air'      => (int) $row['tat_air'],
            'tat_surface'  => (int) $row['tat_surface'],
            'serviceable'  => (bool) $row['serviceable'],
        ],
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Database error.'], 500);
}
