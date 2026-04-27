<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — Careygo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $adminRoot ?? '../' ?>css/admin.css">
    <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= $adminRoot ?? '../' ?>css/<?= htmlspecialchars($css) ?>">
    <?php endforeach; endif; ?>
</head>
<body class="admin-body">

<!-- ===== SIDEBAR ===== -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="<?= $adminRoot ?? '../' ?>admin/dashboard.php" class="sidebar-brand">
            <img src="<?= $adminRoot ?? '../' ?>assets/images/Main-Careygo-logo-white.png" alt="Careygo" class="sidebar-logo">
        </a>
        <button class="sidebar-close d-lg-none" id="sidebarClose" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="nav-section-label">Main</li>
            <li>
                <a href="dashboard.php" class="sidebar-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-section-label mt-2">Management</li>
            <li>
                <a href="customers.php" class="sidebar-link <?= ($activePage ?? '') === 'customers' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Customers</span>
                    <?php
                    try {
                        $badgeStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='pending'");
                        $pendingCount = (int) $badgeStmt->fetchColumn();
                        if ($pendingCount > 0): ?>
                        <span class="sidebar-badge"><?= $pendingCount ?></span>
                        <?php endif;
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>
            <li>
                <a href="deliveries.php" class="sidebar-link <?= ($activePage ?? '') === 'deliveries' ? 'active' : '' ?>">
                    <i class="bi bi-truck"></i>
                    <span>Deliveries</span>
                </a>
            </li>
            <li>
                <a href="pricing.php" class="sidebar-link <?= ($activePage ?? '') === 'pricing' ? 'active' : '' ?>">
                    <i class="bi bi-tags"></i>
                    <span>Pricing</span>
                </a>
            </li>
            <li>
                <a href="pricing.php#packing-material-charge" class="sidebar-link <?= ($activePage ?? '') === 'packing-material' ? 'active' : '' ?>">
                    <i class="bi bi-box2-heart"></i>
                    <span>Packing Material</span>
                </a>
            </li>
            <li>
                <a href="pincodes.php" class="sidebar-link <?= ($activePage ?? '') === 'pincodes' ? 'active' : '' ?>">
                    <i class="bi bi-geo-alt"></i>
                    <span>Pincodes / TAT</span>
                </a>
            </li>

            <li class="nav-section-label mt-2">Account</li>
            <li>
                <a href="<?= $adminRoot ?? '../' ?>auth/logout.php" class="sidebar-link sidebar-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== MAIN CONTENT ===== -->
<div class="admin-content-wrap">

    <!-- Top Navbar -->
    <header class="admin-topbar">
        <button class="admin-toggle-btn d-lg-none" id="sidebarToggle" aria-label="Open sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h5><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?></h5>
        </div>
        <div class="topbar-right">
            <div class="admin-avatar-wrap dropdown">
                <button class="admin-avatar-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="admin-avatar">
                        <?= strtoupper(substr($adminData['full_name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span class="admin-name d-none d-md-inline"><?= htmlspecialchars($adminData['full_name'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end admin-dropdown">
                    <li><span class="dropdown-item-text text-muted" style="font-size:12px;"><?= htmlspecialchars($adminData['email'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $adminRoot ?? '../' ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Confirm Dialog Modal -->
    <div class="modal fade admin-modal" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-question-circle me-2 text-warning"></i>Confirm Action</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage" class="mb-0" style="font-size:14px;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-danger-admin btn-sm" id="confirmOkBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <main class="admin-main">
