<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$user = auth_user();
if ($user) {
    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php'); exit;
    }
    if ($user['status'] === 'approved') {
        header('Location: customer/dashboard.php'); exit;
    }
    header('Location: customer/pending.php'); exit;
}
$token = trim($_GET['token'] ?? '');
$isAuthModal = isset($_GET['modal']) && $_GET['modal'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="assets/images/favicon_LOGO.webp">
    <title>Reset Password - Careygo Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css?v=<?= @filemtime(__DIR__ . '/css/auth.css') ?: time() ?>">
</head>
<body class="auth-body<?= $isAuthModal ? ' auth-modal-page' : '' ?>">

    <div class="auth-wrapper">
        <div class="auth-panel-left d-none d-lg-flex">
            <div class="auth-panel-overlay"></div>
            <div class="auth-panel-content">
                <a href="index.php">
                    <img src="assets/images/Main-Careygo-logo-white.png" alt="Careygo Logo" class="auth-logo">
                </a>
                <h2 class="auth-panel-title">Create New Password</h2>
                <p class="auth-panel-subtitle">Choose a new password to restore secure access to your Careygo account.</p>
                <div class="auth-panel-features">
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Minimum 8 characters</div>
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Old reset links are invalidated</div>
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Works for every account role</div>
                </div>
            </div>
        </div>

        <div class="auth-panel-right">
            <div class="auth-form-wrap">
                <div class="text-center d-lg-none mb-4">
                    <a href="index.php">
                        <img src="assets/images/Main-Careygo-logo-blue.png" alt="Careygo" class="auth-logo-mobile">
                    </a>
                </div>

                <h3 class="auth-form-title">Reset Password</h3>
                <p class="auth-form-subtitle">Enter and confirm your new password.</p>

                <div id="alert-box" class="alert d-none" role="alert"></div>

                <form id="resetPasswordForm" novalidate>
                    <input type="hidden" id="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-4">
                        <label for="password" class="form-label auth-label">New Password</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control auth-input" id="password" name="password"
                                placeholder="Minimum 8 characters" required autocomplete="new-password">
                        </div>
                        <div class="invalid-feedback" id="password-error"></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label auth-label">Confirm Password</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control auth-input" id="confirm_password" name="confirm_password"
                                placeholder="Repeat new password" required autocomplete="new-password">
                        </div>
                        <div class="invalid-feedback" id="confirm-password-error"></div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 auth-submit-btn" id="submitBtn">
                        <span id="submitText">Reset Password</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                    </button>
                </form>

                <div class="auth-divider"><span>Back to account</span></div>

                <a href="login.php<?= $isAuthModal ? '?modal=1' : '' ?>" class="btn auth-alt-btn w-100">Sign In</a>

                <p class="auth-back-link text-center mt-4">
                    <a href="index.php"><i class="bi bi-arrow-left me-1"></i>Back to Homepage</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/reset-password.js"></script>
    <?php if ($isAuthModal): ?>
    <script>
        (function () {
            function sendHeight() {
                window.parent.postMessage({
                    careygoAuthHeight: document.querySelector('.auth-form-wrap').getBoundingClientRect().height + 14
                }, window.location.origin);
            }
            window.addEventListener('load', sendHeight);
            window.addEventListener('resize', sendHeight);
            new MutationObserver(sendHeight).observe(document.body, {
                attributes: true,
                childList: true,
                subtree: true
            });
            setTimeout(sendHeight, 150);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
