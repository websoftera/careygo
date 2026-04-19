<?php
/**
 * Test the shipments API directly
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: application/json');

// Test payload (similar to what the frontend would send)
$testPayload = [
    'pickup' => [
        'name' => 'John Doe',
        'phone' => '9876543210',
        'addr1' => '123 Main Street',
        'addr2' => 'Apt 4B',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'pincode' => '110001',
    ],
    'delivery' => [
        'name' => 'Jane Smith',
        'phone' => '8765432109',
        'addr1' => '456 Second Avenue',
        'addr2' => 'Suite 200',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ],
    'service_type' => 'standard',
    'weight' => 0.5,
    'pieces' => 1,
    'declared_value' => 1000.00,
    'description' => 'Test Package',
    'base_price' => 100.00,
    'discount_pct' => 0,
    'final_price' => 100.00,
    'payment_method' => 'prepaid',
    'delivery_email' => 'receiver@example.com',
];

echo "{\n";
echo "  \"test\": \"API Booking Test\",\n";
echo "  \"timestamp\": \"" . date('Y-m-d H:i:s') . "\",\n";

// Step 1: Check helpers
echo "  \"step_1_helpers\": {\n";
try {
    $tatDays = 3;
    $etaDate = addBusinessDays($tatDays);
    echo "    \"addBusinessDays(3)\": \"" . $etaDate . "\",\n";
    echo "    \"success\": true\n";
} catch (Exception $e) {
    echo "    \"error\": \"" . $e->getMessage() . "\",\n";
    echo "    \"success\": false\n";
}
echo "  },\n";

// Step 2: Check database connection
echo "  \"step_2_database\": {\n";
try {
    $test = $pdo->query("SELECT 1");
    echo "    \"connection\": \"ok\",\n";

    // Check users table has test user
    $userCount = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetchColumn();
    echo "    \"users_count\": " . $userCount . ",\n";
    echo "    \"success\": true\n";
} catch (Exception $e) {
    echo "    \"error\": \"" . $e->getMessage() . "\",\n";
    echo "    \"success\": false\n";
}
echo "  },\n";

// Step 3: Simulate the insert
echo "  \"step_3_insert_simulation\": {\n";
try {
    $tracking = 'CGO' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $tatDays = 3;
    $etaDate = addBusinessDays($tatDays);

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
        $testPayload['pickup']['name'],
        $testPayload['pickup']['phone'],
        $testPayload['pickup']['addr1'] . ', ' . $testPayload['pickup']['addr2'],
        $testPayload['pickup']['city'],
        $testPayload['pickup']['state'],
        $testPayload['pickup']['pincode'],
        $testPayload['delivery']['name'],
        $testPayload['delivery']['phone'],
        $testPayload['delivery']['addr1'] . ', ' . $testPayload['delivery']['addr2'],
        $testPayload['delivery']['city'],
        $testPayload['delivery']['state'],
        $testPayload['delivery']['pincode'],
        'standard', 0.5, 1000.00, 1, 'Test Package', '',
        '', 0,
        100.00, 0, 0, 100.00,
        'prepaid', 0, '', '',
        $etaDate,
    ]);

    $shipmentId = $pdo->lastInsertId();
    echo "    \"tracking_no\": \"" . $tracking . "\",\n";
    echo "    \"shipment_id\": " . $shipmentId . ",\n";
    echo "    \"eta_date\": \"" . $etaDate . "\",\n";
    echo "    \"success\": true\n";
} catch (Exception $e) {
    echo "    \"error\": \"" . $e->getMessage() . "\",\n";
    echo "    \"code\": " . $e->getCode() . ",\n";
    echo "    \"success\": false\n";
}
echo "  }\n";

echo "}\n";
