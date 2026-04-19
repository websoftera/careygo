<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// ── Stats ──
$stats = [];

$stats['total_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$stats['pending_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='pending'")->fetchColumn();
$stats['approved_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='approved'")->fetchColumn();

// Shipment stats (graceful if table not yet created)
try {
    $stats['total_shipments'] = (int) $pdo->query("SELECT COUNT(*) FROM shipments")->fetchColumn();
    $stats['booked']          = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE status='booked'")->fetchColumn();
    $stats['in_transit']      = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE status='in_transit'")->fetchColumn();
    $stats['delivered']       = (int) $pdo->query("SELECT COUNT(*) FROM shipments WHERE status='delivered'")->fetchColumn();
    $stats['revenue']         = (float) $pdo->query("SELECT COALESCE(SUM(final_price),0) FROM shipments WHERE status != 'cancelled'")->fetchColumn();
    $shipmentsExist = true;
} catch (Exception $e) {
    $stats['total_shipments'] = $stats['booked'] = $stats['in_transit'] = $stats['delivered'] = 0;
    $stats['revenue'] = 0;
    $shipmentsExist = false;
}

// Recent customers
$recentCustomers = $pdo->query("SELECT id, full_name, email, phone, company_name, status, created_at FROM users WHERE role='customer' ORDER BY created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

// Recent shipments
$recentShipments = [];
if ($shipmentsExist) {
    $recentShipments = $pdo->query("
        SELECT s.id, s.tracking_no, s.pickup_city, s.delivery_city, s.service_type,
               s.final_price, s.status, s.created_at, u.full_name AS customer_name
        FROM shipments s JOIN users u ON u.id = s.customer_id
        ORDER BY s.created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['pending_customers']) ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-truck"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['total_shipments']) ?></div>
                <div class="stat-label">Total Shipments</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-info">
                <div class="stat-value">₹<?= number_format($stats['revenue'], 0) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['booked'] ?></div>
                <div class="stat-label">Booked</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['in_transit'] ?></div>
                <div class="stat-label">In Transit</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['delivered'] ?></div>
                <div class="stat-label">Delivered</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-person-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['approved_customers'] ?></div>
                <div class="stat-label">Active Customers</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Customers -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-people me-2 text-primary"></i>Recent Customers</h6>
                <a href="customers.php" class="btn-outline-admin" style="font-size:12px;padding:5px 12px;">View All</a>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentCustomers)): ?>
                        <tr><td colspan="3"><div class="empty-state"><i class="bi bi-people"></i><p>No customers yet</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($recentCustomers as $c): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-sm"><?= strtoupper(substr($c['full_name'], 0, 1)) ?></div>
                                    <div class="user-cell-info">
                                        <div class="user-cell-name"><?= htmlspecialchars($c['full_name']) ?></div>
                                        <div class="user-cell-sub"><?= htmlspecialchars($c['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-status badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                            <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Shipments -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-truck me-2 text-primary"></i>Recent Deliveries</h6>
                <a href="deliveries.php" class="btn-outline-admin" style="font-size:12px;padding:5px 12px;">View All</a>
            </div>
            <div class="admin-table-wrap">
                <?php if (!$shipmentsExist || empty($recentShipments)): ?>
                <div class="empty-state"><i class="bi bi-truck"></i><p>No deliveries yet</p></div>
                <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tracking</th>
                            <th>Route</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentShipments as $s): ?>
                        <tr>
                            <td>
                                <div style="font-size:12px;font-weight:700;color:var(--primary)"><?= htmlspecialchars($s['tracking_no']) ?></div>
                                <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($s['customer_name']) ?></div>
                            </td>
                            <td style="font-size:12px"><?= htmlspecialchars($s['pickup_city']) ?> → <?= htmlspecialchars($s['delivery_city']) ?></td>
                            <td><span class="badge-status badge-<?= $s['status'] ?>"><?= ucwords(str_replace('_', ' ', $s['status'])) ?></span></td>
                            <td style="font-size:13px;font-weight:600">₹<?= number_format($s['final_price'], 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== DIAGNOSTIC SECTION ===== -->
<div class="row g-3 mt-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-tools me-2"></i>System Diagnostics</h6>
            </div>
            <div class="admin-card-body">
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                    Check if all systems are ready for booking operations
                </p>

                <?php
                // Run diagnostic checks
                $diagnostics = [];

                // Check 1: Database
                try {
                    $test = $pdo->query("SELECT 1");
                    $diagnostics[] = ['icon' => '✅', 'status' => 'OK', 'check' => 'Database Connection', 'message' => 'Connected successfully'];
                } catch (Exception $e) {
                    $diagnostics[] = ['icon' => '❌', 'status' => 'FAILED', 'check' => 'Database Connection', 'message' => $e->getMessage()];
                }

                // Check 2: Shipments table
                try {
                    $count = $pdo->query("SELECT COUNT(*) as cnt FROM shipments")->fetchColumn();
                    $diagnostics[] = ['icon' => '✅', 'status' => 'OK', 'check' => 'Shipments Table', 'message' => "Exists with $count records"];
                } catch (Exception $e) {
                    $diagnostics[] = ['icon' => '❌', 'status' => 'FAILED', 'check' => 'Shipments Table', 'message' => 'Run /setup.php'];
                }

                // Check 3: Pricing slabs
                try {
                    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs")->fetchColumn();
                    if ($count > 0) {
                        $diagnostics[] = ['icon' => '✅', 'status' => 'OK', 'check' => 'Pricing Slabs', 'message' => "$count slabs found"];
                    } else {
                        $diagnostics[] = ['icon' => '⚠️', 'status' => 'WARNING', 'check' => 'Pricing Slabs', 'message' => 'No slabs - run /import_pricing.php'];
                    }
                } catch (Exception $e) {
                    $diagnostics[] = ['icon' => '❌', 'status' => 'FAILED', 'check' => 'Pricing Slabs', 'message' => $e->getMessage()];
                }

                // Check 4: Pincodes
                try {
                    $count = $pdo->query("SELECT COUNT(*) as cnt FROM pincode_tat")->fetchColumn();
                    if ($count > 1000) {
                        $diagnostics[] = ['icon' => '✅', 'status' => 'OK', 'check' => 'Pincode Data', 'message' => "$count pincodes loaded"];
                    } else {
                        $diagnostics[] = ['icon' => '⚠️', 'status' => 'WARNING', 'check' => 'Pincode Data', 'message' => "Only $count - run /setup.php"];
                    }
                } catch (Exception $e) {
                    $diagnostics[] = ['icon' => '❌', 'status' => 'FAILED', 'check' => 'Pincode Data', 'message' => $e->getMessage()];
                }

                // Check 5: Helper functions
                try {
                    require_once __DIR__ . '/../lib/helpers.php';
                    $date = addBusinessDays(3);
                    if (strtotime($date) > time()) {
                        $diagnostics[] = ['icon' => '✅', 'status' => 'OK', 'check' => 'Helper Functions', 'message' => 'All helpers working'];
                    }
                } catch (Exception $e) {
                    $diagnostics[] = ['icon' => '❌', 'status' => 'FAILED', 'check' => 'Helper Functions', 'message' => $e->getMessage()];
                }

                // Display diagnostics table
                ?>
                <table class="admin-table" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="width: 40px;">Status</th>
                            <th>Check</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnostics as $d): ?>
                        <tr>
                            <td style="text-align: center; font-size: 16px;"><?= $d['icon'] ?></td>
                            <td><strong><?= $d['check'] ?></strong></td>
                            <td style="color: <?= $d['status'] === 'FAILED' ? '#dc2626' : ($d['status'] === 'WARNING' ? '#f59e0b' : '#16a34a') ?>;">
                                <?= htmlspecialchars($d['message']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e4e7f0;">
                    <form method="POST" action="<?= SITE_URL ?>/admin/dashboard.php" style="display: inline;">
                        <button type="submit" name="test_booking" value="1" class="btn btn-sm btn-primary">
                            <i class="bi bi-play-circle me-1"></i> Test Booking Insert
                        </button>
                    </form>
                    <small style="display: block; margin-top: 8px; color: #666;">
                        Tests if the database can actually create a booking record. Will show error if it fails.
                    </small>
                </div>

                <?php
                // Handle test booking insert
                if (isset($_POST['test_booking']) && $_POST['test_booking'] == '1') {
                    try {
                        require_once __DIR__ . '/../lib/helpers.php';
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

                        echo '<div class="alert alert-success mt-3">';
                        echo '✅ <strong>Test Successful!</strong> Booking insert works. Tracking: <code>' . $tracking . '</code><br>';
                        echo '<small>Your booking system is ready. If you are still getting 500 errors, the issue is in the API request.</small>';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger mt-3">';
                        echo '❌ <strong>Test Failed!</strong><br>';
                        echo '<code>' . htmlspecialchars($e->getMessage()) . '</code><br>';
                        echo '<small style="color: #666;">Error Code: ' . $e->getCode() . '</small>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
