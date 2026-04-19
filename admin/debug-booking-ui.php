<?php
/**
 * Debug booking/shipment creation issues - HTML UI version
 * Admin panel tool
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Debug Booking Issues';
$activePage = 'debug';

// Run diagnostic checks
$checks = [];

// Check 1: Database
try {
    $test = $pdo->query("SELECT 1");
    $checks['database'] = ['status' => 'OK', 'icon' => '✅', 'message' => 'Database connection working'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => $e->getMessage()];
}

// Check 2: Shipments table
try {
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM shipments");
    $count = $result->fetchColumn();
    $checks['shipments_table'] = ['status' => 'OK', 'icon' => '✅', 'message' => "Table exists with $count records"];
} catch (Exception $e) {
    $checks['shipments_table'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => $e->getMessage()];
}

// Check 3: Columns
try {
    $cols = $pdo->query("DESCRIBE shipments")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($cols, 'Field');
    $required = ['tracking_no', 'customer_id', 'pickup_name', 'delivery_name', 'service_type', 'weight', 'base_price', 'final_price', 'status', 'estimated_delivery'];
    $missing = array_diff($required, $columnNames);

    if (empty($missing)) {
        $checks['columns'] = ['status' => 'OK', 'icon' => '✅', 'message' => 'All required columns exist (' . count($columnNames) . ' total)'];
    } else {
        $checks['columns'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => 'Missing: ' . implode(', ', $missing)];
    }
} catch (Exception $e) {
    $checks['columns'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => $e->getMessage()];
}

// Check 4: Pricing
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs")->fetchColumn();
    if ($count > 0) {
        $checks['pricing'] = ['status' => 'OK', 'icon' => '✅', 'message' => "$count pricing slabs found"];
    } else {
        $checks['pricing'] = ['status' => 'WARNING', 'icon' => '⚠️', 'message' => 'No pricing slabs - run import_pricing.php'];
    }
} catch (Exception $e) {
    $checks['pricing'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => $e->getMessage()];
}

// Check 5: Pincodes
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pincode_tat")->fetchColumn();
    if ($count > 1000) {
        $checks['pincodes'] = ['status' => 'OK', 'icon' => '✅', 'message' => "$count pincodes imported"];
    } else {
        $checks['pincodes'] = ['status' => 'WARNING', 'icon' => '⚠️', 'message' => "Only $count pincodes - run setup.php"];
    }
} catch (Exception $e) {
    $checks['pincodes'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => $e->getMessage()];
}

// Check 6: Helpers
try {
    $date = addBusinessDays(3);
    if (strtotime($date) > time()) {
        $checks['helpers'] = ['status' => 'OK', 'icon' => '✅', 'message' => "Helper functions working. Test: addBusinessDays(3) = $date"];
    } else {
        $checks['helpers'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => "addBusinessDays returned past date: $date"];
    }
} catch (Exception $e) {
    $checks['helpers'] = ['status' => 'FAILED', 'icon' => '❌', 'message' => $e->getMessage()];
}

// Test insert if requested
$testResult = null;
if (isset($_POST['test_insert'])) {
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

        $testResult = ['success' => true, 'tracking' => $tracking, 'eta' => $etaDate];
    } catch (Exception $e) {
        $testResult = ['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()];
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h4>🔍 Debug Booking Issues</h4>
    <p>Run diagnostic checks to identify booking creation problems</p>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title">System Checks</h6>
    </div>
    <div class="admin-card-body">

        <?php if ($testResult): ?>
            <div class="alert <?= $testResult['success'] ? 'alert-success' : 'alert-danger' ?>">
                <?php if ($testResult['success']): ?>
                    <strong>✅ Test Insert Successful!</strong><br>
                    Tracking: <code><?= $testResult['tracking'] ?></code><br>
                    ETA Date: <code><?= $testResult['eta'] ?></code><br>
                    <small style="color: #666;">Test record was automatically deleted after verification.</small>
                <?php else: ?>
                    <strong>❌ Test Insert Failed</strong><br>
                    Error: <code><?= htmlspecialchars($testResult['error']) ?></code><br>
                    Code: <?= $testResult['code'] ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <table class="table table-sm table-bordered">
            <tr style="background: #f0f0f0;">
                <th style="width: 30px;">Status</th>
                <th>Check</th>
                <th>Result</th>
            </tr>
            <?php foreach ($checks as $key => $check): ?>
                <tr>
                    <td style="text-align: center; font-size: 18px;"><?= $check['icon'] ?></td>
                    <td>
                        <strong><?= ucwords(str_replace('_', ' ', $key)) ?></strong>
                    </td>
                    <td>
                        <span style="color: <?= $check['status'] === 'OK' ? '#22863a' : ($check['status'] === 'WARNING' ? '#b08500' : '#cb2431') ?>;">
                            <?= htmlspecialchars($check['message']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <form method="POST" style="margin-top: 20px;">
            <button type="submit" name="test_insert" value="1" class="btn btn-primary">
                <i class="bi bi-play-circle me-2"></i> Run Test Insert
            </button>
            <small style="display: block; margin-top: 10px; color: #666;">
                This will try to create a test shipment and automatically delete it.
            </small>
        </form>

    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title">What to Do Next</h6>
    </div>
    <div class="admin-card-body">

        <?php
        $hasFailed = array_filter($checks, fn($c) => $c['status'] === 'FAILED');
        $hasWarnings = array_filter($checks, fn($c) => $c['status'] === 'WARNING');
        ?>

        <?php if (!empty($hasFailed)): ?>
            <div class="alert alert-danger mb-3">
                <strong>❌ Issues Found:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($hasFailed as $key => $check): ?>
                        <li><?= ucwords(str_replace('_', ' ', $key)) ?>: <?= htmlspecialchars($check['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p style="margin-top: 10px;"><strong>Fix:</strong> Run <code>/setup.php?key=careygo_setup_2026</code> to apply database migrations</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($hasWarnings)): ?>
            <div class="alert alert-warning mb-3">
                <strong>⚠️ Warnings:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($hasWarnings as $key => $check): ?>
                        <li><?= ucwords(str_replace('_', ' ', $key)) ?>: <?= htmlspecialchars($check['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($hasFailed) && empty($hasWarnings)): ?>
            <div class="alert alert-success">
                <strong>✅ All Systems Ready!</strong><br>
                Your booking system is properly configured. If you're still getting 500 errors when creating bookings, click "Run Test Insert" above to diagnose further.
            </div>
        <?php endif; ?>

        <h6 style="margin-top: 30px;">Quick Reference</h6>
        <table class="table table-sm" style="font-size: 13px;">
            <tr style="background: #f0f0f0;">
                <th>Issue</th>
                <th>Solution</th>
            </tr>
            <tr>
                <td>Database connection failed</td>
                <td>Check database credentials in .env</td>
            </tr>
            <tr>
                <td>Table or column missing</td>
                <td>Visit <code>/setup.php?key=careygo_setup_2026</code></td>
            </tr>
            <tr>
                <td>No pricing slabs</td>
                <td>Visit <code>/import_pricing.php</code> and click Import</td>
            </tr>
            <tr>
                <td>No pincodes</td>
                <td>Visit <code>/setup.php?key=careygo_setup_2026&pincodes=1</code></td>
            </tr>
            <tr>
                <td>Helper functions failed</td>
                <td>Check lib/helpers.php for syntax errors</td>
            </tr>
        </table>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
