<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit; }

// Use includes and temporarily capture $user for title logic
require_once __DIR__ . '/../lib/auth.php';
$authUser = auth_user();
$stmt = $pdo->prepare('SELECT id, full_name, status FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ? AND customer_id = ?");
    $stmt->execute([$id, $user['id']]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $shipment = null; }

if (!$shipment) { header('Location: dashboard.php'); exit; }

$pageTitle = 'Shipment Details';
$activePage = 'dashboard';
$topbarBtn = '
    <a href="dashboard.php" class="btn-outline-admin" style="font-size:12px;padding:7px 14px;text-decoration:none;">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>';

require_once 'includes/header.php';

$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
$statusOrder   = ['booked','picked_up','in_transit','out_for_delivery','delivered'];
$currentIdx    = array_search($shipment['status'], $statusOrder);
$statusLabels  = ['booked'=>'Booked','picked_up'=>'Picked Up','in_transit'=>'In Transit','out_for_delivery'=>'Out for Delivery','delivered'=>'Delivered','cancelled'=>'Cancelled'];
?>

<div style="max-width:800px;margin:0 auto;">

    <!-- Header card -->
    <div class="shipment-detail-card mb-3">
        <div class="shipment-detail-header">
            <div>
                <div style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Tracking Number</div>
                <div style="font-size:20px;font-weight:800;color:var(--primary);font-family:'Montserrat',sans-serif;letter-spacing:1px;">
                    <?= htmlspecialchars($shipment['tracking_no']) ?>
                </div>
            </div>
            <div style="text-align:right;">
                <span class="badge-status badge-<?= $shipment['status'] ?>" style="font-size:13px;padding:6px 14px;">
                    <?= $statusLabels[$shipment['status']] ?? ucfirst($shipment['status']) ?>
                </span>
                <div style="font-size:11px;color:var(--muted);margin-top:6px;">
                    Booked: <?= date('d M Y, h:i A', strtotime($shipment['created_at'])) ?>
                </div>
                <div style="margin-top:10px;">
                    <a href="../api/download_receipt.php?tracking_no=<?= urlencode($shipment['tracking_no']) ?>" target="_blank" class="btn-outline-admin" style="font-size:12px;padding:6px 12px;text-decoration:none;display:inline-block;">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download Receipt
                    </a>
                    <a href="gst-invoice.php?id=<?= $shipment['id'] ?>" target="_blank" class="btn-outline-admin" style="font-size:12px;padding:6px 12px;text-decoration:none;display:inline-block;margin-left:6px;">
                        <i class="bi bi-receipt me-1"></i> GST Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking timeline -->
    <?php if ($shipment['status'] !== 'cancelled'): ?>
    <div class="shipment-detail-card mb-3">
        <div class="shipment-detail-section">
            <div class="shipment-detail-section-title">Tracking Status</div>
            <div class="timeline">
                <?php foreach ($statusOrder as $idx => $st): ?>
                <?php
                    $isDone   = $currentIdx !== false && $idx <= $currentIdx;
                    $isActive = $idx === $currentIdx;
                    $cls = $isDone ? ($isActive ? 'active' : 'done') : '';
                ?>
                <div class="timeline-item <?= $cls ?>">
                    <div class="timeline-dot"></div>
                    <div class="timeline-status"><?= $statusLabels[$st] ?></div>
                    <div class="timeline-date">
                        <?php if ($isDone && !$isActive): ?>
                            Completed
                        <?php elseif ($isActive): ?>
                            <span style="color:var(--primary);font-weight:600;">Current Status</span>
                        <?php else: ?>
                            Pending
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Pickup -->
        <div class="col-md-6">
            <div class="shipment-detail-card" style="height:100%;">
                <div class="shipment-detail-section">
                    <div class="shipment-detail-section-title"><i class="bi bi-geo-alt-fill text-primary me-1"></i> Pickup Address</div>
                    <div style="font-size:13px;">
                        <div style="font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($shipment['pickup_name']) ?></div>
                        <div style="color:var(--muted);margin-bottom:4px;"><?= htmlspecialchars($shipment['pickup_phone']) ?></div>
                        <div><?= htmlspecialchars($shipment['pickup_address']) ?></div>
                        <div><?= htmlspecialchars($shipment['pickup_city']) ?>, <?= htmlspecialchars($shipment['pickup_state']) ?> — <?= htmlspecialchars($shipment['pickup_pincode']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delivery -->
        <div class="col-md-6">
            <div class="shipment-detail-card" style="height:100%;">
                <div class="shipment-detail-section">
                    <div class="shipment-detail-section-title"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Delivery Address</div>
                    <div style="font-size:13px;">
                        <div style="font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($shipment['delivery_name']) ?></div>
                        <div style="color:var(--muted);margin-bottom:4px;"><?= htmlspecialchars($shipment['delivery_phone']) ?></div>
                        <div><?= htmlspecialchars($shipment['delivery_address']) ?></div>
                        <div><?= htmlspecialchars($shipment['delivery_city']) ?>, <?= htmlspecialchars($shipment['delivery_state']) ?> — <?= htmlspecialchars($shipment['delivery_pincode']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipment + Pricing -->
    <div class="row g-3 mt-0">
        <div class="col-md-6">
            <div class="shipment-detail-card mt-3">
                <div class="shipment-detail-section">
                    <div class="shipment-detail-section-title">Shipment Details</div>
                    <div class="detail-row"><span class="detail-label">Service</span><span class="detail-value" style="font-weight:600;"><?= htmlspecialchars($serviceLabels[$shipment['service_type']] ?? $shipment['service_type']) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Actual Weight</span><span class="detail-value"><?= number_format((float)$shipment['weight'], 3) ?> kg</span></div>
                    <div class="detail-row"><span class="detail-label">Chargeable Weight</span><span class="detail-value"><?= number_format((float)($shipment['chargeable_weight'] ?: $shipment['weight']), 3) ?> kg</span></div>
                    <div class="detail-row"><span class="detail-label">Pieces</span><span class="detail-value"><?= $shipment['pieces'] ?></span></div>
                    <div class="detail-row"><span class="detail-label">Declared Value</span><span class="detail-value">₹<?= number_format((float)($shipment['declared_value'] ?? 0), 2) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value"><?= htmlspecialchars($shipment['description'] ?: '—') ?></span></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="shipment-detail-card mt-3">
                <div class="shipment-detail-section">
                    <div class="shipment-detail-section-title">Payment Details</div>
                    <div class="summary-pricing">
                        <div class="pricing-row"><span>Base Price</span><span>₹<?= number_format((float)$shipment['base_price'], 2) ?></span></div>
                        <div class="pricing-row total"><span>Total Amount</span><span>₹<?= number_format((float)$shipment['final_price'], 2) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
