<?php
/**
 * Debug booking/shipment creation issues
 * Admin panel tool
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/includes/middleware.php';

header('Content-Type: application/json');

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [],
];

// Check 1: Database
try {
    $test = $pdo->query("SELECT 1");
    $response['checks']['1_database'] = ['status' => '✅ OK', 'description' => 'Database connection working'];
} catch (Exception $e) {
    $response['checks']['1_database'] = ['status' => '❌ FAILED', 'error' => $e->getMessage()];
}

// Check 2: Shipments table
try {
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM shipments");
    $count = $result->fetchColumn();
    $response['checks']['2_shipments_table'] = ['status' => '✅ OK', 'description' => 'Table exists with ' . $count . ' records'];
} catch (Exception $e) {
    $response['checks']['2_shipments_table'] = ['status' => '❌ FAILED', 'error' => $e->getMessage()];
}

// Check 3: Check required columns
try {
    $cols = $pdo->query("DESCRIBE shipments")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($cols, 'Field');

    $required = ['tracking_no', 'customer_id', 'pickup_name', 'delivery_name', 'service_type', 'weight', 'base_price', 'final_price', 'status', 'estimated_delivery'];
    $missing = array_diff($required, $columnNames);

    if (empty($missing)) {
        $response['checks']['3_columns'] = ['status' => '✅ OK', 'description' => 'All required columns exist (' . count($columnNames) . ' total)'];
    } else {
        $response['checks']['3_columns'] = ['status' => '❌ FAILED', 'missing' => $missing];
    }
} catch (Exception $e) {
    $response['checks']['3_columns'] = ['status' => '❌ FAILED', 'error' => $e->getMessage()];
}

// Check 4: Pricing slabs
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs")->fetchColumn();
    if ($count > 0) {
        $response['checks']['4_pricing'] = ['status' => '✅ OK', 'description' => $count . ' pricing slabs found'];
    } else {
        $response['checks']['4_pricing'] = ['status' => '⚠️ WARNING', 'description' => 'No pricing slabs imported yet - run import_pricing.php'];
    }
} catch (Exception $e) {
    $response['checks']['4_pricing'] = ['status' => '❌ FAILED', 'error' => $e->getMessage()];
}

// Check 5: Pincodes
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pincode_tat")->fetchColumn();
    if ($count > 1000) {
        $response['checks']['5_pincodes'] = ['status' => '✅ OK', 'description' => $count . ' pincodes imported'];
    } else {
        $response['checks']['5_pincodes'] = ['status' => '⚠️ WARNING', 'description' => 'Only ' . $count . ' pincodes - should be 15000+. Run setup.php pincodes import'];
    }
} catch (Exception $e) {
    $response['checks']['5_pincodes'] = ['status' => '❌ FAILED', 'error' => $e->getMessage()];
}

// Check 6: Helper functions
try {
    $date = addBusinessDays(3);
    if (strtotime($date) > time()) {
        $response['checks']['6_helpers'] = ['status' => '✅ OK', 'description' => 'Helper functions working. Test: addBusinessDays(3) = ' . $date];
    } else {
        $response['checks']['6_helpers'] = ['status' => '❌ FAILED', 'error' => 'addBusinessDays returned past date: ' . $date];
    }
} catch (Exception $e) {
    $response['checks']['6_helpers'] = ['status' => '❌ FAILED', 'error' => $e->getMessage()];
}

// Check 7: Test insert (if requested)
if (isset($_GET['test']) && $_GET['test'] == '1') {
    try {
        $tracking = 'TEST' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $etaDate = addBusinessDays(3);

        $stmt = $pdo->prepare("
            INSERT INTO shipments (
                tracking_no, customer_id, pickup_name, pickup_phone, pickup_address,
                pickup_city, pickup_state, pickup_pincode, delivery_name, delivery_phone,
                delivery_address, delivery_city, delivery_state, delivery_pincode,
                service_type, weight, pieces, base_price, discount_pct, discount_amount,
                final_price, payment_method, status, estimated_delivery
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tracking, 1, 'Test Sender', '9876543210', 'Test Address',
            'Delhi', 'Delhi', '110001', 'Test Receiver', '9876543210',
            'Test Address', 'Mumbai', 'Maharashtra', '400001',
            'standard', 0.5, 1, 100, 0, 0, 100, 'prepaid', 'booked', $etaDate
        ]);

        $shipmentId = $pdo->lastInsertId();
        $pdo->prepare("DELETE FROM shipments WHERE id = ?")->execute([$shipmentId]);

        $response['checks']['7_test_insert'] = [
            'status' => '✅ OK',
            'description' => 'Test booking insert successful',
            'tracking_no' => $tracking,
            'eta_date' => $etaDate
        ];
    } catch (Exception $e) {
        $response['checks']['7_test_insert'] = [
            'status' => '❌ FAILED',
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }
}

// Summary
$failed = array_filter($response['checks'], fn($c) => strpos($c['status'], 'FAILED') !== false);
$warnings = array_filter($response['checks'], fn($c) => strpos($c['status'], 'WARNING') !== false);

$response['summary'] = [
    'total_checks' => count($response['checks']),
    'passed' => count($response['checks']) - count($failed) - count($warnings),
    'warnings' => count($warnings),
    'failed' => count($failed),
];

// Pretty print
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
