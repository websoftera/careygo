<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$authUser = auth_require('customer');

$stmt = $pdo->prepare('SELECT id, full_name, email, phone, company_name, status, created_at FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status'] !== 'approved') { header('Location: pending.php'); exit; }

// Total shipments
$r = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id=?"); $r->execute([$user['id']]); $totalShipments = (int)$r->fetchColumn();
$memberSince = date('M Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — Careygo</title>
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
            <li><a href="dashboard.php" class="cust-nav-link"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
            <li><a href="new-booking.php" class="cust-nav-link"><i class="bi bi-plus-circle"></i> New Booking</a></li>
            <li><a href="#" class="cust-nav-link" onclick="openRateCalc();return false;"><i class="bi bi-calculator"></i> Rate Calculator</a></li>
            <li class="cust-nav-label mt-2">Account</li>
            <li><a href="profile.php" class="cust-nav-link active"><i class="bi bi-person-circle"></i> My Profile</a></li>
            <li><a href="../auth/logout.php" class="cust-nav-link logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay" id="custOverlay"></div>

<!-- ===== MAIN ===== -->
<div class="cust-content-wrap">
    <header class="cust-topbar">
        <button class="cust-toggle-btn d-lg-none" id="custToggle"><i class="bi bi-list"></i></button>
        <div class="cust-topbar-title">My Profile</div>
        <div class="cust-topbar-actions">
            <button class="btn-rate-calc" onclick="openRateCalc()">
                <i class="bi bi-calculator"></i> <span class="d-none d-sm-inline">Rate Calculator</span>
            </button>
            <a href="new-booking.php" class="btn-new-delivery" style="font-size:12px;padding:7px 14px;">
                <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">New Booking</span>
            </a>
        </div>
    </header>

    <main class="cust-main">
        <div class="row g-4">

            <!-- Left: Profile Card -->
            <div class="col-lg-4">
                <div class="cust-card text-center" style="padding:32px 24px;">
                    <div class="profile-avatar mx-auto mb-3"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                    <h5 style="font-family:'Montserrat',sans-serif;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($user['full_name']) ?></h5>
                    <p style="color:var(--muted);font-size:13px;margin-bottom:16px;"><?= htmlspecialchars($user['email']) ?></p>
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <div class="text-center">
                            <div style="font-size:22px;font-weight:700;color:var(--primary);font-family:'Montserrat',sans-serif;"><?= $totalShipments ?></div>
                            <div style="font-size:11px;color:var(--muted);">Shipments</div>
                        </div>
                        <div style="width:1px;background:var(--border);"></div>
                        <div class="text-center">
                            <div style="font-size:22px;font-weight:700;color:var(--primary);font-family:'Montserrat',sans-serif;"><?= $memberSince ?></div>
                            <div style="font-size:11px;color:var(--muted);">Member Since</div>
                        </div>
                    </div>
                    <div style="background:var(--bg-card);border-radius:12px;padding:14px;text-align:left;">
                        <div style="font-size:12px;color:var(--muted);margin-bottom:4px;">Account Status</div>
                        <span class="badge-status badge-<?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Right: Edit Forms -->
            <div class="col-lg-8">

                <!-- Alert container -->
                <div id="profileAlert" style="display:none;"></div>

                <!-- Profile Info Form -->
                <div class="cust-card mb-4">
                    <div class="cust-card-header">
                        <h6 class="cust-card-title"><i class="bi bi-person me-2"></i>Personal Information</h6>
                    </div>
                    <div style="padding:24px;">
                        <form id="profileForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="wiz-label">Full Name <span style="color:#e74c3c;">*</span></label>
                                    <input type="text" class="wiz-input" id="pFullName" name="full_name"
                                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="wiz-label">Email Address</label>
                                    <input type="email" class="wiz-input" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                           style="opacity:0.6;cursor:not-allowed;" title="Email cannot be changed">
                                </div>
                                <div class="col-md-6">
                                    <label class="wiz-label">Phone Number <span style="color:#e74c3c;">*</span></label>
                                    <input type="tel" class="wiz-input" id="pPhone" name="phone"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="wiz-label">Company Name <span style="color:var(--muted);font-size:11px;">(optional)</span></label>
                                    <input type="text" class="wiz-input" id="pCompany" name="company_name"
                                           value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn-new-delivery" id="profileSaveBtn">
                                    <i class="bi bi-check-lg me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div class="cust-card">
                    <div class="cust-card-header">
                        <h6 class="cust-card-title"><i class="bi bi-shield-lock me-2"></i>Change Password</h6>
                    </div>
                    <div style="padding:24px;">
                        <div id="pwAlert" style="display:none;"></div>
                        <form id="passwordForm">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="wiz-label">Current Password <span style="color:#e74c3c;">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" class="wiz-input" id="currentPw" name="current_password"
                                               placeholder="Enter your current password" autocomplete="current-password">
                                        <button type="button" class="pw-toggle-btn" onclick="togglePw('currentPw',this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="wiz-label">New Password <span style="color:#e74c3c;">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" class="wiz-input" id="newPw" name="new_password"
                                               placeholder="Min. 8 characters" autocomplete="new-password">
                                        <button type="button" class="pw-toggle-btn" onclick="togglePw('newPw',this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div id="pwStrength" style="margin-top:6px;"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="wiz-label">Confirm New Password <span style="color:#e74c3c;">*</span></label>
                                    <div style="position:relative;">
                                        <input type="password" class="wiz-input" id="confirmPw" name="confirm_password"
                                               placeholder="Repeat new password" autocomplete="new-password">
                                        <button type="button" class="pw-toggle-btn" onclick="togglePw('confirmPw',this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn-new-delivery" id="pwSaveBtn">
                                    <i class="bi bi-shield-check me-1"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div><!-- /col-lg-8 -->
        </div><!-- /row -->
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const SITE_URL = '<?= rtrim(SITE_URL, '/') ?>';
// ── Sidebar ────────────────────────────────────────────────────
const custSidebar = document.getElementById('custSidebar');
const custOverlay = document.getElementById('custOverlay');
document.getElementById('custToggle')?.addEventListener('click', () => { custSidebar.classList.add('open'); custOverlay.classList.add('show'); });
document.getElementById('custSidebarClose')?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
custOverlay?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });

// ── Password visibility toggle ────────────────────────────────
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}

// ── Password strength meter ───────────────────────────────────
document.getElementById('newPw')?.addEventListener('input', function() {
    const pw = this.value;
    const el = document.getElementById('pwStrength');
    let score = 0;
    if (pw.length >= 8)                      score++;
    if (/[A-Z]/.test(pw))                    score++;
    if (/[0-9]/.test(pw))                    score++;
    if (/[^A-Za-z0-9]/.test(pw))            score++;

    const labels  = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors  = ['', '#e74c3c', '#f39c12', '#3498db', '#27ae60'];
    const widths  = ['0%', '25%', '50%', '75%', '100%'];

    if (!pw) { el.innerHTML = ''; return; }
    el.innerHTML = `
        <div style="height:4px;background:#eee;border-radius:4px;overflow:hidden;">
            <div style="height:100%;width:${widths[score]};background:${colors[score]};transition:width .3s,background .3s;"></div>
        </div>
        <span style="font-size:11px;color:${colors[score]};">${labels[score]}</span>
    `;
});

// ── Alert helper ──────────────────────────────────────────────
function showAlert(containerId, msg, type = 'success') {
    const el = document.getElementById(containerId);
    el.innerHTML = `<div class="cust-alert-${type}" style="margin-bottom:16px;">${msg}</div>`;
    el.style.display = 'block';
    setTimeout(() => { el.innerHTML = ''; el.style.display = 'none'; }, 5000);
}

// ── Save profile ──────────────────────────────────────────────
document.getElementById('profileForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('profileSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

    try {
        const res = await fetch(`${SITE_URL}/api/profile.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                full_name:    document.getElementById('pFullName').value.trim(),
                phone:        document.getElementById('pPhone').value.trim(),
                company_name: document.getElementById('pCompany').value.trim(),
            })
        });
        const data = await res.json();
        showAlert('profileAlert', data.message || (data.success ? 'Profile updated.' : 'Update failed.'), data.success ? 'success' : 'error');
    } catch {
        showAlert('profileAlert', 'Network error. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Changes';
    }
});

// ── Change password ───────────────────────────────────────────
document.getElementById('passwordForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('pwSaveBtn');

    const newPw     = document.getElementById('newPw').value;
    const confirmPw = document.getElementById('confirmPw').value;
    if (newPw !== confirmPw) {
        showAlert('pwAlert', 'New passwords do not match.', 'error');
        return;
    }
    if (newPw.length < 8) {
        showAlert('pwAlert', 'Password must be at least 8 characters.', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating…';

    try {
        const res = await fetch(`${SITE_URL}/api/profile.php?action=password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: document.getElementById('currentPw').value,
                new_password:     newPw,
                confirm_password: confirmPw,
            })
        });
        const data = await res.json();
        showAlert('pwAlert', data.message || (data.success ? 'Password changed.' : 'Failed.'), data.success ? 'success' : 'error');
        if (data.success) {
            document.getElementById('passwordForm').reset();
            document.getElementById('pwStrength').innerHTML = '';
        }
    } catch {
        showAlert('pwAlert', 'Network error. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Change Password';
    }
});
</script>
<?php include __DIR__ . '/includes/rate-calc-modal.php'; ?>
<script>
function openRateCalc() { new bootstrap.Modal(document.getElementById('rateCalcModal')).show(); }
</script>
</body>
</html>
