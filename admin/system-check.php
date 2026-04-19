<?php
/**
 * System Check - Standalone diagnostic page
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    die('Access Denied');
}

$checks = [];

// 1. Database
try {
    $pdo->query("SELECT 1");
    $checks[] = ['✅', 'Database', 'Connection OK'];
} catch (Exception $e) {
    $checks[] = ['❌', 'Database', $e->getMessage()];
}

// 2. Shipments table
try {
    $cnt = $pdo->query("SELECT COUNT(*) as c FROM shipments")->fetchColumn();
    $checks[] = ['✅', 'Shipments Table', "$cnt records"];
} catch (Exception $e) {
    $checks[] = ['❌', 'Shipments Table', 'Missing - run /setup.php'];
}

// 3. Pricing
try {
    $cnt = $pdo->query("SELECT COUNT(*) as c FROM pricing_slabs")->fetchColumn();
    $status = $cnt > 0 ? '✅' : '⚠️';
    $msg = $cnt > 0 ? "$cnt slabs" : "No slabs - run /import_pricing.php";
    $checks[] = [$status, 'Pricing Data', $msg];
} catch (Exception $e) {
    $checks[] = ['❌', 'Pricing Data', 'Run /setup.php'];
}

// 4. Pincodes
try {
    $cnt = $pdo->query("SELECT COUNT(*) as c FROM pincode_tat")->fetchColumn();
    $status = $cnt > 1000 ? '✅' : '⚠️';
    $msg = $cnt > 1000 ? "$cnt pincodes" : "Only $cnt - run /setup.php";
    $checks[] = [$status, 'Pincode Data', $msg];
} catch (Exception $e) {
    $checks[] = ['❌', 'Pincode Data', 'Run /setup.php'];
}

// 5. Helpers
try {
    require_once __DIR__ . '/../lib/helpers.php';
    $date = addBusinessDays(3);
    $checks[] = ['✅', 'Helper Functions', 'Working'];
} catch (Exception $e) {
    $checks[] = ['❌', 'Helper Functions', 'Error: ' . $e->getMessage()];
}

// Test insert
$testResult = null;
if (isset($_POST['test'])) {
    try {
        require_once __DIR__ . '/../lib/helpers.php';
        $tracking = 'TST' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $eta = addBusinessDays(3);

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
            $tracking, 1, 'Test', '9876543210', 'Test', 'Delhi', 'Delhi', '110001',
            'Test', '9876543210', 'Test', 'Mumbai', 'Maharashtra', '400001',
            'standard', 0.5, 1, 100, 0, 0, 100, 'prepaid', 'booked', $eta
        ]);

        $id = $pdo->lastInsertId();
        $pdo->prepare("DELETE FROM shipments WHERE id = ?")->execute([$id]);
        $testResult = ['ok' => true, 'tracking' => $tracking];
    } catch (Exception $e) {
        $testResult = ['ok' => false, 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Check</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f9; padding: 40px 20px; font-family: 'Poppins', sans-serif; }
        .check-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin: 20px auto; max-width: 600px; }
        .check-card h3 { color: #001A93; margin-bottom: 20px; font-weight: 700; }
        .check-row { display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .check-row:last-child { border-bottom: none; }
        .check-icon { font-size: 24px; width: 30px; text-align: center; }
        .check-info { flex: 1; }
        .check-label { font-weight: 600; color: #1a1a1a; }
        .check-msg { font-size: 13px; color: #666; margin-top: 2px; }
        .alert { margin-top: 20px; border-radius: 10px; }
        .btn { margin-top: 20px; }
    </style>
</head>
<body>

<div class="check-card">
    <h3>🔍 System Diagnostics</h3>

    <?php if ($testResult): ?>
        <?php if ($testResult['ok']): ?>
            <div class="alert alert-success">
                <strong>✅ Test Successful!</strong><br>
                Booking system is working. Tracking: <code><?= $testResult['tracking'] ?></code>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>❌ Test Failed!</strong><br>
                <code><?= htmlspecialchars($testResult['error']) ?></code>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php foreach ($checks as $check): ?>
    <div class="check-row">
        <div class="check-icon"><?= $check[0] ?></div>
        <div class="check-info">
            <div class="check-label"><?= $check[1] ?></div>
            <div class="check-msg"><?= $check[2] ?></div>
        </div>
    </div>
    <?php endforeach; ?>

    <form method="POST" style="margin-top: 20px;">
        <button type="submit" name="test" value="1" class="btn btn-primary btn-sm">
            Test Booking Insert
        </button>
    </form>
</div>

</body>
</html>
