<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$authUser = auth_require('customer');

// Redirect if not approved
$stmt = $pdo->prepare('SELECT id, full_name, email, phone, company_name, status FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status'] !== 'approved') { header('Location: pending.php'); exit; }

// Stats
$stats = ['total' => 0, 'in_transit' => 0, 'delivered' => 0, 'booked' => 0];
$shipments = [];
$filter  = $_GET['status'] ?? '';
$search  = trim($_GET['q'] ?? '');

try {
    $stats['total']      = (int) $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=?")->execute([$user['id']]) ? $pdo->query("SELECT COUNT(*) FROM shipments WHERE customer_id={$user['id']}")->fetchColumn() : 0;

    // Use prepared statements properly
    $r = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=?"); $r->execute([$user['id']]); $stats['total'] = (int)$r->fetchColumn();
    $r = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=? AND status IN('booked','picked_up')"); $r->execute([$user['id']]); $stats['booked'] = (int)$r->fetchColumn();
    $r = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=? AND status='in_transit'"); $r->execute([$user['id']]); $stats['in_transit'] = (int)$r->fetchColumn();
    $r = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=? AND status='delivered'"); $r->execute([$user['id']]); $stats['delivered'] = (int)$r->fetchColumn();

    $where  = "WHERE customer_id = ?";
    $params = [$user['id']];
    if ($filter && in_array($filter, ['booked','picked_up','in_transit','out_for_delivery','delivered','cancelled'])) {
        $where .= " AND status = ?"; $params[] = $filter;
    }
    if ($search) {
        $where .= " AND (tracking_no LIKE ? OR pickup_city LIKE ? OR delivery_city LIKE ?)";
        $like = "%$search%"; $params = array_merge($params, [$like, $like, $like]);
    }
    $stmt = $pdo->prepare("SELECT * FROM shipments $where ORDER BY created_at DESC LIMIT 100");
    $stmt->execute($params);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Deliveries — Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
</head>
<body class="customer-body">

<!-- ===== SIDEBAR ===== -->
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

<!-- ===== MAIN ===== -->
<div class="cust-content-wrap">
    <header class="cust-topbar">
        <button class="cust-toggle-btn d-lg-none" id="custToggle"><i class="bi bi-list"></i></button>
        <div class="cust-topbar-title">My Deliveries</div>
        <div class="cust-topbar-actions">
            <a href="new-delivery.php" class="btn-new-delivery">
                <i class="bi bi-plus-lg"></i> <span>New Delivery</span>
            </a>
        </div>
    </header>

    <main class="cust-main">
        <!-- Stats -->
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

        <!-- Shipments -->
        <div class="cust-card">
            <div class="cust-card-header">
                <h6 class="cust-card-title"><i class="bi bi-truck me-2"></i>Shipments</h6>
                <div class="cust-filter-bar">
                    <div class="cust-filter-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="shipSearch" placeholder="Tracking no, city…">
                    </div>
                </div>
            </div>

            <?php if (empty($shipments)): ?>
            <div class="cust-empty">
                <i class="bi bi-box-seam"></i>
                <h5>No deliveries yet</h5>
                <p>Book your first delivery to get started</p>
                <a href="new-delivery.php" class="btn-new-delivery">
                    <i class="bi bi-plus-lg me-1"></i> New Delivery
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
                        <td style="font-size:12px;"><?= number_format((float)$s['weight'], 3) ?> kg</td>
                        <td style="font-size:13px;font-weight:700;">₹<?= number_format($s['final_price'], 0) ?></td>
                        <td><span class="badge-status badge-<?= $s['status'] ?>"><?= ucwords(str_replace('_', ' ', $s['status'])) ?></span></td>
                        <td style="font-size:11px;color:var(--muted)"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td><a href="shipment.php?id=<?= $s['id'] ?>" class="btn-new-delivery" style="font-size:11px;padding:5px 10px;"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar
const custSidebar = document.getElementById('custSidebar');
const custOverlay = document.getElementById('custOverlay');
document.getElementById('custToggle')?.addEventListener('click', () => { custSidebar.classList.add('open'); custOverlay.classList.add('show'); });
document.getElementById('custSidebarClose')?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
custOverlay?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
// Search
document.getElementById('shipSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#shipTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
