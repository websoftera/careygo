<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$user = auth_user();
if ($user) {
    if ($user['role'] === 'admin') { header('Location: admin/dashboard.php'); exit; }
    if ($user['status'] === 'approved') { header('Location: customer/dashboard.php'); exit; }
    header('Location: customer/pending.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Careygo Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-body">

    <div class="auth-wrapper">
        <!-- Left Panel -->
        <div class="auth-panel-left d-none d-lg-flex">
            <div class="auth-panel-overlay"></div>
            <div class="auth-panel-content">
                <a href="index.php">
                    <img src="assets/images/Main-Careygo-logo-white.png" alt="Careygo Logo" class="auth-logo">
                </a>
                <h2 class="auth-panel-title">Join Careygo</h2>
                <p class="auth-panel-subtitle">Create an account to unlock fast, reliable, and transparent logistics solutions for your business.</p>
                <div class="auth-panel-features">
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Fast onboarding process</div>
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Dedicated account manager</div>
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Competitive shipping rates</div>
                    <div class="auth-feature-item"><i class="bi bi-check-circle-fill"></i> Real-time tracking & alerts</div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Form -->
        <div class="auth-panel-right">
            <div class="auth-form-wrap">
                <!-- Mobile Logo -->
                <div class="text-center d-lg-none mb-4">
                    <a href="index.php">
                        <img src="assets/images/Main-Careygo-logo-blue.png" alt="Careygo" class="auth-logo-mobile">
                    </a>
                </div>

                <h3 class="auth-form-title">Create Account</h3>
                <p class="auth-form-subtitle">Fill in the details below to get started.</p>

                <div id="alert-box" class="alert d-none" role="alert"></div>

                <form id="registerForm" novalidate>
                    <div class="auth-row mb-4">
                        <div>
                            <label for="full_name" class="form-label auth-label">Full Name</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control auth-input" id="full_name" name="full_name"
                                    placeholder="John Doe" required autocomplete="name">
                            </div>
                            <div class="invalid-feedback" id="full_name-error"></div>
                        </div>
                        <div>
                            <label for="phone" class="form-label auth-label">Phone Number</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control auth-input" id="phone" name="phone"
                                    placeholder="+91 98000 00000" required autocomplete="tel">
                            </div>
                            <div class="invalid-feedback" id="phone-error"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="company_name" class="form-label auth-label">Company Name <span class="text-muted fw-normal">(optional)</span></label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <input type="text" class="form-control auth-input" id="company_name" name="company_name"
                                placeholder="Acme Pvt. Ltd." autocomplete="organization">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label auth-label">Email Address</label>
                        <div class="input-group auth-input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control auth-input" id="email" name="email"
                                placeholder="you@company.com" required autocomplete="email">
                        </div>
                        <div class="invalid-feedback" id="email-error"></div>
                    </div>

                    <div class="auth-row mb-4">
                        <div>
                            <label for="password" class="form-label auth-label">Password</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control auth-input" id="password" name="password"
                                    placeholder="••••••••" required autocomplete="new-password">
                                <button class="btn auth-eye-btn" type="button" id="togglePassword" tabindex="-1">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <div class="password-strength-bar mt-2"><div class="strength-fill" id="strengthFill"></div></div>
                            <div class="password-strength-text text-muted" id="strengthText"></div>
                            <div class="invalid-feedback" id="password-error"></div>
                        </div>
                        <div>
                            <label for="confirm_password" class="form-label auth-label">Confirm Password</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" class="form-control auth-input" id="confirm_password"
                                    name="confirm_password" placeholder="••••••••" required autocomplete="new-password">
                            </div>
                            <div class="invalid-feedback" id="confirm_password-error"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 auth-submit-btn" id="submitBtn">
                        <span id="submitText">Create Account</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                    </button>
                </form>

                <div class="auth-divider"><span>Already have an account?</span></div>
                <a href="login.php" class="btn auth-alt-btn w-100">Sign In</a>

                <p class="auth-back-link text-center mt-4">
                    <a href="index.php"><i class="bi bi-arrow-left me-1"></i>Back to Homepage</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/register.js"></script>
</body>
</html>
