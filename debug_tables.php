<?php
/**
 * Debug script to check database table structure
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die('Access Denied. Admin only.');
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Debug</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
    body { padding: 20px; background: #f0f2f9; font-family: monospace; }
    .container { max-width: 1200px; }
    .card { margin-bottom: 20px; }
    .error { color: #d32f2f; }
    .success { color: #388e3c; }
    table { font-size: 12px; }
</style>";
echo "</head><body>";
echo "<div class='container'>";

echo "<h2>Database Table Structure Debug</h2>";
echo "<hr>";

// Check shipments table
echo "<h4>SHIPMENTS TABLE</h4>";
try {
    $columns = $pdo->query("DESCRIBE shipments")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='success'>✓ Shipments table OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check pricing_slabs table
echo "<h4>PRICING_SLABS TABLE</h4>";
try {
    $columns = $pdo->query("DESCRIBE pricing_slabs")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='success'>✓ Pricing slabs table OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check pincode_tat table
echo "<h4>PINCODE_TAT TABLE</h4>";
try {
    $columns = $pdo->query("DESCRIBE pincode_tat")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pincode_tat")->fetchColumn();
    echo "<p class='success'>✓ Pincode TAT table OK (contains " . $count . " pincodes)</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test the actual problematic query
echo "<h4>TEST: Create Sample Shipment</h4>";
try {
    // Test data
    $tracking = 'CGO' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $userId = 1;

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
        'Test Sender', '9876543210', 'Test Address', 'Delhi', 'Delhi', '110001',
        'Test Receiver', '9876543210', 'Test Address', 'Mumbai', 'Maharashtra', '400001',
        'standard', 0.5, 100.00, 1, 'Test Package', '',
        '', 0,
        100.00, 0, 0, 100.00,
        'prepaid', 0, '', '',
        date('Y-m-d'),
    ]);

    echo "<p class='success'>✓ Test insert successful - Tracking: " . $tracking . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Insert failed: " . $e->getMessage() . "</p>";
    echo "<p style='color: #666; font-size: 11px;'>Code: " . $e->getCode() . "</p>";
}

echo "</div>";
echo "</body></html>";
