<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$authUser = auth_require('customer');
$stmt = $pdo->prepare('SELECT id, full_name, status FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status'] !== 'approved') { header('Location: pending.php'); exit; }

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ? AND customer_id = ?");
    $stmt->execute([$id, $user['id']]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $shipment = null; }

if (!$shipment) { header('Location: dashboard.php'); exit; }

$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
$statusOrder   = ['booked','picked_up','in_transit','out_for_delivery','delivered'];
$currentIdx    = array_search($shipment['status'], $statusOrder);

$statusColors  = ['booked'=>'booked','picked_up'=>'picked_up','in_transit'=>'in_transit','out_for_delivery'=>'out_for_delivery','delivered'=>'delivered','cancelled'=>'cancelled'];
$statusLabels  = ['booked'=>'Booked','picked_up'=>'Picked Up','in_transit'=>'In Transit','out_for_delivery'=>'Out for Delivery','delivered'=>'Delivered','cancelled'=>'Cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment <?= htmlspecialchars($shipment['tracking_no']) ?> — Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
</head>
<body class="customer-body">

<!-- Sidebar -->
<aside class="cust-sidebar" id="custSidebar">
    <div class="cust-sidebar-header">
        <a href="../index.php"><img src="../assets/images/Main-Careygo-logo-white.png" alt="Careygo" class="cust-sidebar-logo"></a>
        <button class="cust-sidebar-close d-lg-none" id="custSidebarClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cust-sidebar-user">
        <div class="cust-user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div>
            <div class="cust-user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="cust-user-role">Customer</div>
        </div>
    </div>
    <nav class="cust-nav">
        <ul>
            <li class="cust-nav-label">Navigation</li>
            <li><a href="dashboard.php" class="cust-nav-link active"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
            <li><a href="new-delivery.php" class="cust-nav-link"><i class="bi bi-plus-circle"></i> New Delivery</a></li>
            <li class="cust-nav-label mt-2">Account</li>
            <li><a href="profile.php" class="cust-nav-link"><i class="bi bi-person-circle"></i> My Profile</a></li>
            <li><a href="../auth/logout.php" class="cust-nav-link logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay" id="custOverlay"></div>

<div class="cust-content-wrap">
    <header class="cust-topbar">
        <button class="cust-toggle-btn d-lg-none" id="custToggle"><i class="bi bi-list"></i></button>
        <div class="cust-topbar-title" style="font-size:13px;">
            <a href="dashboard.php" style="color:var(--muted);text-decoration:none;">My Deliveries</a>
            <span style="margin:0 6px;color:var(--muted);">/</span>
            <span style="font-family:'Montserrat',sans-serif;"><?= htmlspecialchars($shipment['tracking_no']) ?></span>
        </div>
        <div class="cust-topbar-actions">
            <a href="dashboard.php" class="btn-outline-admin" style="font-size:12px;padding:7px 14px;text-decoration:none;">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </header>

    <main class="cust-main">
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
                            <div class="detail-row"><span class="detail-label">Weight</span><span class="detail-value"><?= number_format((float)$shipment['weight'], 3) ?> kg</span></div>
                            <div class="detail-row"><span class="detail-label">Pieces</span><span class="detail-value"><?= $shipment['pieces'] ?></span></div>
                            <div class="detail-row"><span class="detail-label">Declared Value</span><span class="detail-value">₹<?= number_format((float)($shipment['declared_value'] ?? 0), 2) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value"><?= htmlspecialchars($shipment['description'] ?: '—') ?></span></div>
                            <?php if ($shipment['customer_ref']): ?>
                            <div class="detail-row"><span class="detail-label">Reference</span><span class="detail-value"><?= htmlspecialchars($shipment['customer_ref']) ?></span></div>
                            <?php endif; ?>
                            <?php if ($shipment['ewaybill_no']): ?>
                            <div class="detail-row"><span class="detail-label">E-Waybill</span><span class="detail-value"><?= htmlspecialchars($shipment['ewaybill_no']) ?></span></div>
                            <?php endif; ?>
                            <div class="detail-row"><span class="detail-label">Packing Material</span><span class="detail-value"><?= $shipment['packing_material'] ? 'Yes' : 'No' ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="shipment-detail-card mt-3">
                        <div class="shipment-detail-section">
                            <div class="shipment-detail-section-title">Payment Details</div>
                            <div class="summary-pricing">
                                <div class="pricing-row"><span>Base Price</span><span>₹<?= number_format((float)$shipment['base_price'], 2) ?></span></div>
                                <?php if ((float)$shipment['discount_amount'] > 0): ?>
                                <div class="pricing-row discount"><span>Discount (<?= $shipment['discount_pct'] ?>%)</span><span>−₹<?= number_format((float)$shipment['discount_amount'], 2) ?></span></div>
                                <?php endif; ?>
                                <div class="pricing-row total"><span>Total Amount</span><span>₹<?= number_format((float)$shipment['final_price'], 2) ?></span></div>
                            </div>
                            <div class="detail-row mt-3"><span class="detail-label">Payment Method</span><span class="detail-value" style="font-weight:600;text-transform:capitalize;"><?= htmlspecialchars($shipment['payment_method']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">GST Invoice</span><span class="detail-value"><?= $shipment['gst_invoice'] ? 'Requested' : 'Not required' ?></span></div>
                            <?php if ($shipment['gstin']): ?>
                            <div class="detail-row"><span class="detail-label">GSTIN</span><span class="detail-value"><?= htmlspecialchars($shipment['gstin']) ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const custSidebar = document.getElementById('custSidebar');
const custOverlay = document.getElementById('custOverlay');
document.getElementById('custToggle')?.addEventListener('click', () => { custSidebar.classList.add('open'); custOverlay.classList.add('show'); });
document.getElementById('custSidebarClose')?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
custOverlay?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
</script>
</body>
</html>
