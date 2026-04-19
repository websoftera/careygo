<?php
/**
 * Comprehensive diagnostic tool for booking issues
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/email.php';

header('Content-Type: application/json');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check 1: Database Connection
$results['checks']['database_connection'] = ['status' => 'pending'];
try {
    $test = $pdo->query("SELECT 1");
    $results['checks']['database_connection'] = ['status' => 'OK'];
} catch (Exception $e) {
    $results['checks']['database_connection'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 2: Users table
$results['checks']['users_table'] = ['status' => 'pending'];
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetchColumn();
    $results['checks']['users_table'] = ['status' => 'OK', 'rows' => $count];
} catch (Exception $e) {
    $results['checks']['users_table'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 3: Shipments table structure
$results['checks']['shipments_table'] = ['status' => 'pending'];
try {
    $columns = $pdo->query("DESCRIBE shipments")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    $results['checks']['shipments_table'] = ['status' => 'OK', 'columns' => count($columnNames), 'column_list' => $columnNames];
} catch (Exception $e) {
    $results['checks']['shipments_table'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 4: Pricing slabs
$results['checks']['pricing_slabs'] = ['status' => 'pending'];
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs")->fetchColumn();
    $results['checks']['pricing_slabs'] = ['status' => 'OK', 'rows' => $count];
} catch (Exception $e) {
    $results['checks']['pricing_slabs'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 5: Pincode TAT
$results['checks']['pincode_tat'] = ['status' => 'pending'];
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pincode_tat")->fetchColumn();
    $results['checks']['pincode_tat'] = ['status' => 'OK', 'rows' => $count];
} catch (Exception $e) {
    $results['checks']['pincode_tat'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 6: Helper functions
$results['checks']['addBusinessDays'] = ['status' => 'pending'];
try {
    $date = addBusinessDays(3);
    $results['checks']['addBusinessDays'] = ['status' => 'OK', 'test_value' => $date, 'format' => 'Y-m-d'];
} catch (Exception $e) {
    $results['checks']['addBusinessDays'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 7: EmailService
$results['checks']['email_service'] = ['status' => 'pending'];
try {
    $email = new EmailService();
    $results['checks']['email_service'] = ['status' => 'OK', 'from' => $email->from ?? 'unknown'];
} catch (Exception $e) {
    $results['checks']['email_service'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 8: Test insert (without committing)
$results['checks']['test_insert'] = ['status' => 'pending'];
try {
    $tracking = 'TEST' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $etaDate = addBusinessDays(3);

    // Get a real user ID
    $userId = 1;
    $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $userCheck->execute([$userId]);
    if (!$userCheck->fetch()) {
        throw new Exception("No user found with id=$userId");
    }

    // Try the exact insert that api/shipments.php does
    $stmt = $pdo->prepare("
        INSERT INTO shipments (
            tracking_no, customer_id,
            pickup_name, pickup_phone, pickup_address, pickup_city, pickup_state, pickup_pincode,
            delivery_name, delivery_phone, delivery_address, delivery_city, delivery_state, delivery_pincode,
            service_type, weight, declared_value, pieces, description, customer_ref,
            ewaybill_no, packing_material,
            base_price, discount_pct, discount_amount, final_price,
            payment_method, gst_invoice, gstin, pan_number,
            status, estimated_delivery
        ) VALUES (
            ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            'booked', ?
        )
    ");

    $result = $stmt->execute([
        $tracking, $userId,
        'Test Name', '9876543210', 'Test Address Line 1, Test Line 2', 'Delhi', 'Delhi', '110001',
        'Receiver Name', '8765432109', 'Delivery Address Line 1, Delivery Line 2', 'Mumbai', 'Maharashtra', '400001',
        'standard', 0.5, 1000.00, 1, 'Test', '',
        '', 0,
        100.00, 0, 0, 100.00,
        'prepaid', 0, '', '',
        $etaDate,
    ]);

    $shipmentId = $pdo->lastInsertId();

    // Clean up - delete the test record
    $pdo->prepare("DELETE FROM shipments WHERE id = ?")->execute([$shipmentId]);

    $results['checks']['test_insert'] = [
        'status' => 'OK',
        'tracking_no' => $tracking,
        'shipment_id' => $shipmentId,
        'eta_date' => $etaDate,
        'note' => 'Test record was inserted and then deleted successfully'
    ];
} catch (Exception $e) {
    $results['checks']['test_insert'] = ['status' => 'FAILED', 'error' => $e->getMessage(), 'code' => $e->getCode()];
}

// Summary
$failedChecks = array_filter($results['checks'], fn($c) => $c['status'] === 'FAILED');
$results['summary'] = [
    'total_checks' => count($results['checks']),
    'passed' => count($results['checks']) - count($failedChecks),
    'failed' => count($failedChecks),
    'status' => empty($failedChecks) ? 'ALL_OK' : 'ISSUES_FOUND',
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
