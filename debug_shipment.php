<?php
/**
 * Debug shipment creation issues
 * Access as: /debug_shipment.php?test=1
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: application/json');

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [],
    'test_booking' => null,
];

// Check 1: Database
$response['checks']['database'] = ['status' => 'pending'];
try {
    $test = $pdo->query("SELECT 1");
    $response['checks']['database'] = ['status' => 'OK'];
} catch (Exception $e) {
    $response['checks']['database'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 2: User authentication
$response['checks']['auth'] = ['status' => 'pending'];
try {
    $user = auth_user();
    if (!$user) {
        $response['checks']['auth'] = ['status' => 'FAILED', 'error' => 'Not authenticated'];
    } else {
        $response['checks']['auth'] = ['status' => 'OK', 'user_id' => $user['sub']];
    }
} catch (Exception $e) {
    $response['checks']['auth'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 3: Helper functions
$response['checks']['helpers'] = ['status' => 'pending'];
try {
    $date = addBusinessDays(3);
    $tatCol = tatColumn('standard');
    $response['checks']['helpers'] = ['status' => 'OK', 'test_date' => $date, 'tat_col' => $tatCol];
} catch (Exception $e) {
    $response['checks']['helpers'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 4: Tables exist
$response['checks']['tables'] = ['status' => 'pending'];
try {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    $required = ['shipments', 'pricing_slabs', 'pincode_tat', 'users'];
    $missing = array_diff($required, $tables);
    if (empty($missing)) {
        $response['checks']['tables'] = ['status' => 'OK', 'found' => count($tables)];
    } else {
        $response['checks']['tables'] = ['status' => 'FAILED', 'missing_tables' => $missing];
    }
} catch (Exception $e) {
    $response['checks']['tables'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 5: Shipments table columns
$response['checks']['shipments_columns'] = ['status' => 'pending'];
try {
    $cols = $pdo->query("DESCRIBE shipments")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($cols, 'Field');

    $required_cols = [
        'id', 'tracking_no', 'customer_id', 'pickup_name', 'pickup_phone',
        'pickup_address', 'pickup_city', 'pickup_state', 'pickup_pincode',
        'delivery_name', 'delivery_phone', 'delivery_address', 'delivery_city',
        'delivery_state', 'delivery_pincode', 'service_type', 'weight', 'pieces',
        'base_price', 'final_price', 'payment_method', 'status', 'estimated_delivery'
    ];

    $missing = array_diff($required_cols, $columnNames);
    if (empty($missing)) {
        $response['checks']['shipments_columns'] = ['status' => 'OK', 'columns' => count($columnNames)];
    } else {
        $response['checks']['shipments_columns'] = ['status' => 'FAILED', 'missing_columns' => $missing];
    }
} catch (Exception $e) {
    $response['checks']['shipments_columns'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 6: Pricing data
$response['checks']['pricing_data'] = ['status' => 'pending'];
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs")->fetchColumn();
    $response['checks']['pricing_data'] = ['status' => 'OK', 'slab_count' => $count];
} catch (Exception $e) {
    $response['checks']['pricing_data'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 7: Pincode data
$response['checks']['pincode_data'] = ['status' => 'pending'];
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pincode_tat")->fetchColumn();
    $response['checks']['pincode_data'] = ['status' => 'OK', 'pincode_count' => $count];
} catch (Exception $e) {
    $response['checks']['pincode_data'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
}

// Check 8: Test actual insert
if (isset($_GET['test']) && $_GET['test'] == '1') {
    $response['test_booking'] = ['status' => 'pending'];
    try {
        $tracking = 'TEST' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $etaDate = addBusinessDays(3);

        // Check user exists
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([1]);
        $userRow = $userStmt->fetch();

        if (!$userRow) {
            throw new Exception('User with id=1 not found. Get a real user ID from database.');
        }

        // Test the insert query
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
            $tracking, 1,
            'Test Sender', '9876543210', 'Test Addr 1, Test Addr 2', 'Delhi', 'Delhi', '110001',
            'Test Receiver', '9876543210', 'Test Addr 1, Test Addr 2', 'Mumbai', 'Maharashtra', '400001',
            'standard', 0.5, 1000, 1, 'Test Pkg', '',
            '', 0,
            100, 0, 0, 100,
            'prepaid', 0, '', '',
            $etaDate,
        ]);

        $shipmentId = $pdo->lastInsertId();

        // Delete test record
        $pdo->prepare("DELETE FROM shipments WHERE id = ?")->execute([$shipmentId]);

        $response['test_booking'] = [
            'status' => 'OK',
            'tracking_no' => $tracking,
            'shipment_id' => $shipmentId,
            'eta_date' => $etaDate,
            'note' => 'Test insert successful (record deleted)'
        ];
    } catch (Exception $e) {
        $response['test_booking'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }
}

// Summary
$failed = array_filter($response['checks'], fn($c) => $c['status'] === 'FAILED');
$response['summary'] = [
    'total' => count($response['checks']),
    'passed' => count($response['checks']) - count($failed),
    'failed' => count($failed),
    'overall' => empty($failed) ? 'OK' : 'ISSUES_FOUND',
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
