<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$pageTitle = 'My Earnings';
$activePage = 'earnings';

require_once 'includes/header.php';

function ensureCustomerEarningColumns(PDO $pdo): void
{
    static $done = false;
    if ($done) return;

    try {
        $shipmentCols = $pdo->query("SHOW COLUMNS FROM shipments")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_earning_pct', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN customer_earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        }
        if (!in_array('customer_earning_amount', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN customer_earning_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (Exception $e) {}

    $done = true;
}

ensureCustomerEarningColumns($pdo);

$kpis = [
    'total' => 0,
    'pending' => 0,
    'completed' => 0,
    'earnings' => 0.0,
];
$deliveries = [];
$search = trim($_GET['q'] ?? '');
$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];

try {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status NOT IN ('delivered','cancelled') THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status <> 'cancelled' THEN customer_earning_amount ELSE 0 END) AS earnings
        FROM shipments
        WHERE customer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $kpis['total'] = (int)($row['total'] ?? 0);
    $kpis['pending'] = (int)($row['pending'] ?? 0);
    $kpis['completed'] = (int)($row['completed'] ?? 0);
    $kpis['earnings'] = (float)($row['earnings'] ?? 0);

    $where = "WHERE customer_id = ?";
    $params = [$user['id']];
    if ($search !== '') {
        $where .= " AND (tracking_no LIKE ? OR pickup_city LIKE ? OR delivery_city LIKE ? OR service_type LIKE ?)";
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like, $like]);
    }

    $stmt = $pdo->prepare("SELECT * FROM shipments $where ORDER BY created_at DESC LIMIT 200");
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div><div class="cust-stat-value"><?= $kpis['total'] ?></div><div class="cust-stat-label">Total Deliveries</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="cust-stat-value"><?= $kpis['pending'] ?></div><div class="cust-stat-label">Pending Deliveries</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon green"><i class="bi bi-check2-circle"></i></div>
            <div><div class="cust-stat-value"><?= $kpis['completed'] ?></div><div class="cust-stat-label">Completed Deliveries</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon purple"><i class="bi bi-cash-coin"></i></div>
            <div><div class="cust-stat-value">Rs.<?= number_format($kpis['earnings'], 0) ?></div><div class="cust-stat-label">Total Earnings</div></div>
        </div>
    </div>
</div>

<div class="cust-card">
    <div class="cust-card-header">
        <h6 class="cust-card-title"><i class="bi bi-cash-coin me-2"></i>Delivery Earnings</h6>
        <div class="cust-filter-bar">
            <form method="get" class="cust-filter-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" id="earningSearch" placeholder="Tracking no, city, service..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
    </div>

    <?php if (empty($deliveries)): ?>
    <div class="cust-empty">
        <i class="bi bi-cash-coin"></i>
        <h5>No earnings yet</h5>
        <p>Earnings will appear here after bookings are created.</p>
        <a href="new-booking.php" class="btn-new-delivery"><i class="bi bi-plus-lg me-1"></i> New Booking</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="cust-table" id="earningTable">
            <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Route</th>
                    <th>Service</th>
                    <th>Booking Amount</th>
                    <th>Earning %</th>
                    <th>Earning</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td>
                        <div style="font-size:12px;font-weight:700;color:var(--primary);font-family:'Montserrat',sans-serif;"><?= htmlspecialchars($d['tracking_no']) ?></div>
                        <div style="font-size:11px;color:var(--muted);"><?= number_format((float)($d['chargeable_weight'] ?: $d['weight']), 3) ?> kg</div>
                    </td>
                    <td style="font-size:12px;">
                        <div style="font-weight:600"><?= htmlspecialchars($d['pickup_city']) ?></div>
                        <div style="color:var(--muted)">to <?= htmlspecialchars($d['delivery_city']) ?></div>
                    </td>
                    <td style="font-size:12px;"><?= htmlspecialchars($serviceLabels[$d['service_type']] ?? $d['service_type']) ?></td>
                    <td style="font-size:13px;font-weight:700;">Rs.<?= number_format((float)$d['final_price'], 0) ?></td>
                    <td style="font-size:12px;font-weight:700;"><?= number_format((float)($d['customer_earning_pct'] ?? 0), 2) ?>%</td>
                    <td style="font-size:13px;font-weight:800;color:var(--success);">Rs.<?= number_format((float)($d['customer_earning_amount'] ?? 0), 0) ?></td>
                    <td><span class="badge-status badge-<?= $d['status'] ?>"><?= ucwords(str_replace('_', ' ', $d['status'])) ?></span></td>
                    <td style="font-size:11px;color:var(--muted)"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    window.filterTable('earningSearch', 'earningTable');
});
</script>

<?php require_once 'includes/footer.php'; ?>
