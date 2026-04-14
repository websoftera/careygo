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

<?php require_once 'includes/footer.php'; ?>
