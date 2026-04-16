<?php
/**
 * Shared site header — detects logged-in state and shows correct nav CTA
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$_navUser = auth_user();   // null = guest, array = logged-in payload
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careygo Logistics</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ===== TOP BAR ===== -->
    <div class="top-bar py-2 d-none d-lg-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex gap-4 top-info">
                    <a href="mailto:info@careygo.in"><i class="bi bi-envelope-fill"></i> info@careygo.in</a>
                    <span><i class="bi bi-geo-alt-fill"></i> 250 Main Street, 2nd Floor, USA</span>
                    <a href="tel:+919850296178"><i class="bi bi-telephone-fill"></i> +91 98502 96178</a>
                </div>
                <div class="col-md-4 d-flex justify-content-end gap-2 social-icons">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MAIN NAVIGATION ===== -->
    <nav class="navbar navbar-expand-xl main-navbar py-3 sticky-top">
        <div class="container align-items-center">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/Main-Careygo-logo-blue.png" alt="CAREYGO Logo" class="brand-logo">
            </a>

            <!-- Mobile Toggle -->
            <div class="d-flex align-items-center gap-2 d-xl-none">
                <!-- Mobile: show compact auth button before toggler -->
                <?php if ($_navUser): ?>
                <a href="customer/dashboard.php" class="nav-user-avatar-sm" title="My Dashboard">
                    <?= strtoupper(substr($_navUser['name'] ?? 'U', 0, 1)) ?>
                </a>
                <?php else: ?>
                <a href="login.php" class="nav-login-btn-sm">
                    Connect With Us
                </a>
                <?php endif; ?>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Navbar Links & Actions -->
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto mb-3 mb-xl-0 nav-links-wrapper px-4 py-2 mt-3 mt-xl-0">
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link active" href="#">HOME</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="#">ABOUT US</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="#">SERVICES</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="#">OUR NETWORK</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="#">BLOG</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="#">CONTACT US</a>
                    </li>
                </ul>

                <div class="d-flex justify-content-center align-items-center gap-3 nav-actions">
                    <button class="btn search-btn rounded-circle shadow-none" type="button" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($_navUser): ?>
                    <!-- ── Logged-in user dropdown ── -->
                    <div class="dropdown nav-user-dropdown">
                        <button class="nav-user-pill dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-user-pill-avatar">
                                <?= strtoupper(substr($_navUser['name'] ?? 'U', 0, 1)) ?>
                            </span>
                            <span class="nav-user-pill-name d-none d-xl-inline">
                                <?= htmlspecialchars(explode(' ', $_navUser['name'] ?? 'User')[0]) ?>
                            </span>
                            <i class="bi bi-chevron-down nav-user-pill-chevron"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end nav-user-menu shadow-sm">
                            <li class="nav-user-menu-header">
                                <div class="nav-user-menu-avatar">
                                    <?= strtoupper(substr($_navUser['name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="nav-user-menu-name"><?= htmlspecialchars($_navUser['name'] ?? '') ?></div>
                                    <div class="nav-user-menu-role">Customer</div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php
                            $dashLink = ($_navUser['role'] === 'admin')
                                ? 'admin/dashboard.php'
                                : (($_navUser['status'] === 'approved') ? 'customer/dashboard.php' : 'customer/pending.php');
                            ?>
                            <li>
                                <a class="dropdown-item nav-user-menu-item" href="<?= $dashLink ?>">
                                    <i class="bi bi-grid-1x2"></i> My Dashboard
                                </a>
                            </li>
                            <?php if ($_navUser['role'] === 'customer' && $_navUser['status'] === 'approved'): ?>
                            <li>
                                <a class="dropdown-item nav-user-menu-item" href="customer/new-booking.php">
                                    <i class="bi bi-plus-circle"></i> New Booking
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-user-menu-item" href="customer/profile.php">
                                    <i class="bi bi-person-circle"></i> My Profile
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item nav-user-menu-item nav-user-menu-logout" href="auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>

                    <?php else: ?>
                    <!-- ── Guest: Connect With Us ── -->
                    <a href="login.php"
                        class="btn btn-primary-custom rounded-pill text-white d-flex align-items-center gap-2 fw-semibold nav-login-btn">
                        Connect With Us
                        <span class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-up-right btn-arrow"></i>
                        </span>
                    </a>
                    <?php endif; ?>

                </div><!-- /nav-actions -->
            </div><!-- /collapse -->
        </div>
    </nav>
