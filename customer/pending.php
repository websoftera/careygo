<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$authUser = auth_require('customer');

// Re-fetch fresh status from DB
$stmt = $pdo->prepare('SELECT full_name, status, created_at FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { auth_logout(); header('Location: ../login.php'); exit; }

// If approved, redirect to dashboard
if ($user['status'] === 'approved') {
    header('Location: dashboard.php'); exit;
}

$statusLabel = $user['status'] === 'rejected' ? 'Rejected' : 'Pending Approval';
$isRejected  = $user['status'] === 'rejected';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Status — Careygo Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/pending.css">
</head>
<body class="pending-body">

    <div class="pending-wrapper">
        <!-- Header -->
        <header class="pending-header">
            <a href="../index.php">
                <img src="../assets/images/Main-Careygo-logo-blue.png" alt="Careygo" class="pending-logo">
            </a>
            <a href="../auth/logout.php" class="btn pending-logout-btn">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </header>

        <!-- Card -->
        <main class="pending-main">
            <div class="pending-card <?= $isRejected ? 'rejected' : '' ?>">
                <div class="pending-icon-wrap <?= $isRejected ? 'rejected' : '' ?>">
                    <?php if ($isRejected): ?>
                        <i class="bi bi-x-circle-fill"></i>
                    <?php else: ?>
                        <i class="bi bi-hourglass-split"></i>
                    <?php endif; ?>
                </div>

                <h2 class="pending-title">
                    <?= $isRejected ? 'Application Rejected' : 'Awaiting Approval' ?>
                </h2>

                <p class="pending-name">Hello, <strong><?= htmlspecialchars($user['full_name']) ?></strong></p>

                <?php if ($isRejected): ?>
                    <p class="pending-message">
                        We're sorry, your account application has been <strong>rejected</strong> by our admin team.
                        If you believe this is a mistake, please contact us at
                        <a href="mailto:info@careygo.in">info@careygo.in</a>.
                    </p>
                <?php else: ?>
                    <p class="pending-message">
                        Thank you for registering with <strong>Careygo Logistics</strong>.<br>
                        Your account is currently under review. Our admin team will approve your access shortly.
                    </p>
                    <div class="pending-steps">
                        <div class="pending-step completed">
                            <div class="step-dot"><i class="bi bi-check-lg"></i></div>
                            <div class="step-label">Account Registered</div>
                        </div>
                        <div class="pending-step-line"></div>
                        <div class="pending-step active">
                            <div class="step-dot"><i class="bi bi-clock"></i></div>
                            <div class="step-label">Admin Review</div>
                        </div>
                        <div class="pending-step-line"></div>
                        <div class="pending-step">
                            <div class="step-dot"><i class="bi bi-unlock"></i></div>
                            <div class="step-label">Access Granted</div>
                        </div>
                    </div>
                    <p class="pending-note">
                        <i class="bi bi-info-circle me-1"></i>
                        This page refreshes automatically every 30 seconds. Need help?
                        <a href="mailto:info@careygo.in">Contact support</a>.
                    </p>
                <?php endif; ?>

                <div class="pending-actions">
                    <?php if (!$isRejected): ?>
                        <button onclick="checkStatus()" class="btn pending-refresh-btn" id="refreshBtn">
                            <i class="bi bi-arrow-clockwise me-1"></i> Check Status
                        </button>
                    <?php endif; ?>
                    <a href="../auth/logout.php" class="btn pending-logout-card-btn">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>
        </main>

        <footer class="pending-footer">
            <p>© <?= date('Y') ?> Careygo Logistics. All rights reserved.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/pending.js"></script>
</body>
</html>
