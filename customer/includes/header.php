<?php
/**
 * Customer Dashboard Header
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

$authUser = auth_require('customer');

// Redirect if not approved
$stmt = $pdo->prepare('SELECT id, full_name, email, phone, company_name, status FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'approved') {
    header('Location: pending.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Customer') ?> — Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
</head>
<body class="customer-body">

<aside class="cust-sidebar" id="custSidebar">
    <div class="cust-sidebar-header">
        <a href="../index.php"><img src="../assets/images/Main-Careygo-logo-white.png" alt="Careygo" class="cust-sidebar-logo"></a>
        <button class="cust-sidebar-close" id="custSidebarClose"><i class="bi bi-x-lg"></i></button>
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
            <li><a href="dashboard.php" class="cust-nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
            <li><a href="earnings.php" class="cust-nav-link <?= ($activePage ?? '') === 'earnings' ? 'active' : '' ?>"><i class="bi bi-cash-coin"></i> My Earnings</a></li>
            <li><a href="new-booking.php" class="cust-nav-link <?= ($activePage ?? '') === 'new-booking' ? 'active' : '' ?>"><i class="bi bi-plus-circle"></i> New Booking</a></li>
            <li><a href="#" class="cust-nav-link" onclick="openRateCalc();return false;"><i class="bi bi-calculator"></i> Rate Calculator</a></li>
            <li class="cust-nav-label mt-2">Account</li>
            <li><a href="profile.php" class="cust-nav-link <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>"><i class="bi bi-person-circle"></i> My Profile</a></li>
            <li><a href="../auth/logout.php" class="cust-nav-link logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay" id="custOverlay"></div>

<div class="cust-content-wrap">
    <header class="cust-topbar">
        <button class="cust-toggle-btn" id="custToggle"><i class="bi bi-list"></i></button>
        <div class="cust-topbar-title"><?= htmlspecialchars($pageTitle ?? 'Customer Panel') ?></div>
        <div class="cust-topbar-actions">
            <?php if (isset($topbarBtn)): echo $topbarBtn; else: ?>
            <button class="btn-rate-calc" onclick="openRateCalc()">
                <i class="bi bi-calculator"></i> <span class="d-none d-sm-inline">Rate Calculator</span>
            </button>
            <a href="new-booking.php" class="btn-new-delivery">
                <i class="bi bi-plus-lg"></i> <span>New Booking</span>
            </a>
            <?php endif; ?>
        </div>
    </header>
    <main class="cust-main">
