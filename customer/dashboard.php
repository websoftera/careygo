<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$pageTitle = 'My Bookings';
$activePage = 'dashboard';

require_once 'includes/header.php';

// Stats
$stats = ['total' => 0, 'in_transit' => 0, 'delivered' => 0, 'booked' => 0];
$shipments = [];
$filter  = $_GET['status'] ?? '';
$search  = trim($_GET['q'] ?? '');

try {
    // Corrected stats query
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id = ?");
    $stmtCount->execute([$user['id']]);
    $stats['total'] = (int) $stmtCount->fetchColumn();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id = ? AND status IN('booked','picked_up')");
    $stmtCount->execute([$user['id']]);
    $stats['booked'] = (int) $stmtCount->fetchColumn();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id = ? AND status = 'in_transit'");
    $stmtCount->execute([$user['id']]);
    $stats['in_transit'] = (int) $stmtCount->fetchColumn();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id = ? AND status = 'delivered'");
    $stmtCount->execute([$user['id']]);
    $stats['delivered'] = (int) $stmtCount->fetchColumn();

    $where  = "WHERE customer_id = ?";
    $params = [$user['id']];
    if ($filter && in_array($filter, ['booked','picked_up','in_transit','out_for_delivery','delivered','cancelled'])) {
        $where .= " AND status = ?"; 
        $params[] = $filter;
    }
    if ($search) {
        $where .= " AND (tracking_no LIKE ? OR pickup_city LIKE ? OR delivery_city LIKE ?)";
        $like = "%$search%"; 
        $params = array_merge($params, [$like, $like, $like]);
    }
    $stmt = $pdo->prepare("SELECT * FROM shipments $where ORDER BY created_at DESC LIMIT 100");
    $stmt->execute($params);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div><div class="cust-stat-value"><?= $stats['total'] ?></div><div class="cust-stat-label">Total Orders</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon orange"><i class="bi bi-clock-history"></i></div>
            <div><div class="cust-stat-value"><?= $stats['booked'] ?></div><div class="cust-stat-label">Booked / Pickup</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon purple"><i class="bi bi-truck"></i></div>
            <div><div class="cust-stat-value"><?= $stats['in_transit'] ?></div><div class="cust-stat-label">In Transit</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cust-stat-card">
            <div class="cust-stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div><div class="cust-stat-value"><?= $stats['delivered'] ?></div><div class="cust-stat-label">Delivered</div></div>
        </div>
    </div>
</div>

<!-- Filter tabs -->
<div class="d-flex gap-2 flex-wrap mb-3">
    <?php $tabs = ['' => 'All', 'booked' => 'Booked', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']; ?>
    <?php foreach ($tabs as $val => $label): ?>
    <a href="dashboard.php?status=<?= $val ?>" class="btn <?= $filter === $val ? 'btn-new-delivery' : '' ?>" style="font-size:12px;padding:6px 14px;border:1.5px solid var(--border);border-radius:10px;color:var(--text);text-decoration:none;<?= $filter === $val ? 'background:var(--primary);color:#fff;border-color:var(--primary)' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Shipments Table -->
<div class="cust-card">
    <div class="cust-card-header">
        <h6 class="cust-card-title"><i class="bi bi-truck me-2"></i>Shipments</h6>
        <div class="cust-filter-bar">
            <div class="cust-filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="shipSearch" placeholder="Tracking no, city…" value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
    </div>

    <?php if (empty($shipments)): ?>
    <div class="cust-empty">
        <i class="bi bi-box-seam"></i>
        <h5>No bookings yet</h5>
        <p>Create your first booking to get started</p>
        <a href="new-booking.php" class="btn-new-delivery">
            <i class="bi bi-plus-lg me-1"></i> New Booking
        </a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="cust-table" id="shipTable">
            <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Route</th>
                    <th>Service</th>
                    <th>Weight</th>
                    <th>Amount</th>
                    <th>Earning</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($shipments as $s): ?>
            <tr>
                <td>
                    <div style="font-size:12px;font-weight:700;color:var(--primary);font-family:'Montserrat',sans-serif;"><?= htmlspecialchars($s['tracking_no']) ?></div>
                </td>
                <td style="font-size:12px;">
                    <div style="font-weight:600"><?= htmlspecialchars($s['pickup_city']) ?></div>
                    <div style="color:var(--muted)">→ <?= htmlspecialchars($s['delivery_city']) ?></div>
                </td>
                <td style="font-size:12px;"><?= htmlspecialchars($serviceLabels[$s['service_type']] ?? $s['service_type']) ?></td>
                <td style="font-size:12px;"><?= number_format((float)($s['chargeable_weight'] ?: $s['weight']), 3) ?> kg</td>
                <td style="font-size:13px;font-weight:700;">₹<?= number_format($s['final_price'], 0) ?></td>
                <td style="font-size:12px;">
                    <div style="font-weight:700;color:var(--success);">Rs.<?= number_format((float)($s['customer_earning_amount'] ?? 0), 0) ?></div>
                    <div style="color:var(--muted);"><?= number_format((float)($s['customer_earning_pct'] ?? 0), 2) ?>%</div>
                </td>
                <td><span class="badge-status badge-<?= $s['status'] ?>"><?= ucwords(str_replace('_', ' ', $s['status'])) ?></span></td>
                <td style="font-size:11px;color:var(--muted)"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="tracking.php?id=<?= $s['id'] ?>" class="btn-new-delivery" style="font-size:11px;padding:5px 10px;" title="Track"><i class="bi bi-geo-alt"></i></a>
                        <a href="../api/download_receipt.php?tracking_no=<?= urlencode($s['tracking_no']) ?>" target="_blank" class="btn-outline-admin" style="font-size:11px;padding:5px 10px;" title="Download Receipt"><i class="bi bi-file-earmark-pdf"></i></a>
                        <?php if (!empty($s['gst_invoice']) || !empty($s['gstin']) || !empty($s['pickup_gstin']) || !empty($s['delivery_gstin'])): ?>
                        <a href="gst-invoice.php?id=<?= $s['id'] ?>" target="_blank" class="btn-outline-admin" style="font-size:11px;padding:5px 10px;" title="GST Invoice"><i class="bi bi-receipt"></i></a>
                        <?php endif; ?>
                        <a href="shipment.php?id=<?= $s['id'] ?>" class="btn-outline-admin" style="font-size:11px;padding:5px 10px;" title="View Details"><i class="bi bi-eye"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    window.filterTable('shipSearch', 'shipTable');
});
</script>

<?php require_once 'includes/footer.php'; ?>
