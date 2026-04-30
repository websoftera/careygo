<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$customerId = (int)($_GET['id'] ?? 0);
$pageTitle = 'Customer Dashboard';
$activePage = 'customers';

$customer = null;
$stats = ['total'=>0,'booked'=>0,'in_transit'=>0,'delivered'=>0];
$shipments = [];
$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];

try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, company_name FROM users WHERE id=? AND role='customer'");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=?");
        $stmt->execute([$customerId]); $stats['total'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=? AND status IN('booked','picked_up')");
        $stmt->execute([$customerId]); $stats['booked'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=? AND status='in_transit'");
        $stmt->execute([$customerId]); $stats['in_transit'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=? AND status='delivered'");
        $stmt->execute([$customerId]); $stats['delivered'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM shipments WHERE customer_id=? ORDER BY created_at DESC LIMIT 100");
        $stmt->execute([$customerId]); $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Customer Dashboard</h4>
        <p><?= $customer ? htmlspecialchars($customer['full_name'] . ' - ' . $customer['email']) : 'Customer not found' ?></p>
    </div>
    <a href="customers.php" class="btn-outline-admin"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (!$customer): ?>
<div class="admin-card"><div class="empty-state"><p>Customer not found</p></div></div>
<?php else: ?>
<div class="row g-3 mb-4">
    <?php foreach ([['total','Total Orders','blue','bi-box-seam'],['booked','Booked / Pickup','orange','bi-clock-history'],['in_transit','In Transit','purple','bi-truck'],['delivered','Delivered','green','bi-check-circle']] as $card): ?>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon <?= $card[2] ?>"><i class="bi <?= $card[3] ?>"></i></div><div><div class="stat-value"><?= $stats[$card[0]] ?></div><div class="stat-label"><?= $card[1] ?></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="admin-card">
    <div class="admin-card-header"><h6 class="admin-card-title"><i class="bi bi-truck me-2"></i>Shipments</h6></div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Tracking</th><th>Route</th><th>Service</th><th>Weight</th><th>Amount</th><th>Earning</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($shipments)): ?>
            <tr><td colspan="9"><div class="empty-state"><i class="bi bi-truck"></i><p>No shipments found</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($shipments as $s): ?>
            <tr>
                <td style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($s['tracking_no']) ?></td>
                <td><?= htmlspecialchars($s['pickup_city']) ?> -> <?= htmlspecialchars($s['delivery_city']) ?></td>
                <td><?= htmlspecialchars($serviceLabels[$s['service_type']] ?? $s['service_type']) ?></td>
                <td><?= number_format((float)($s['chargeable_weight'] ?: $s['weight']), 0) ?> kg</td>
                <td>Rs.<?= number_format((float)$s['final_price'], 0) ?></td>
                <td>
                    <div style="font-weight:700;color:var(--success);">Rs.<?= number_format((float)($s['customer_earning_amount'] ?? 0), 0) ?></div>
                    <div style="font-size:11px;color:var(--muted);"><?= number_format((float)($s['customer_earning_pct'] ?? 0), 2) ?>%</div>
                </td>
                <td><span class="badge-status badge-<?= $s['status'] ?>"><?= ucwords(str_replace('_',' ', $s['status'])) ?></span></td>
                <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="../api/download_receipt.php?tracking_no=<?= urlencode($s['tracking_no']) ?>" target="_blank" class="btn-outline-admin" style="font-size:11px;padding:5px 10px;" title="View Receipt"><i class="bi bi-file-earmark-pdf"></i></a>
                        <a href="../customer/gst-invoice.php?id=<?= (int)$s['id'] ?>" target="_blank" class="btn-outline-admin" style="font-size:11px;padding:5px 10px;" title="GST Invoice"><i class="bi bi-receipt"></i></a>
                        <a href="deliveries.php?q=<?= urlencode($s['tracking_no']) ?>" class="btn-outline-admin" style="font-size:11px;padding:5px 10px;" title="Open in Deliveries"><i class="bi bi-eye"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
