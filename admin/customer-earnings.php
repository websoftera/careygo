<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle = 'Customer Earnings';
$activePage = 'customer-earnings';

function ensureCustomerEarningPlanTables(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS customer_earning_slabs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id INT UNSIGNED NOT NULL,
            pricing_slab_id INT UNSIGNED NOT NULL,
            earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_customer_slab (customer_id, pricing_slab_id),
            INDEX idx_customer_id (customer_id),
            INDEX idx_pricing_slab_id (pricing_slab_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}
ensureCustomerEarningPlanTables($pdo);

$selectedCustomerId = (int)($_GET['customer_id'] ?? 0);

$customers = [];
$slabs = [];
$earningMap = [];
try {
    $customers = $pdo->query("SELECT id, full_name, email, company_name FROM users WHERE role='customer' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    if (!$selectedCustomerId && !empty($customers)) {
        $selectedCustomerId = (int)$customers[0]['id'];
    }

    $slabs = $pdo->query(
        "SELECT * FROM pricing_slabs
         ORDER BY FIELD(zone,'within_city','within_state','metro','rest_of_india',NULL),
                  service_type, sort_order, weight_from"
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedCustomerId) {
        $stmt = $pdo->prepare("SELECT pricing_slab_id, earning_pct FROM customer_earning_slabs WHERE customer_id = ?");
        $stmt->execute([$selectedCustomerId]);
        $earningMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
} catch (Exception $e) {}

$grouped = [];
foreach ($slabs as $s) {
    $z = $s['zone'] ?? 'rest_of_india';
    $grouped[$z][$s['service_type']][] = $s;
}

$zoneKeys = ['within_city','within_state','metro','rest_of_india'];
$zoneLabels = [
    'within_city' => 'Within City',
    'within_state' => 'Within State',
    'metro' => 'Metro',
    'rest_of_india' => 'Rest of India',
];
$zoneIcons = [
    'within_city' => 'bi-geo-alt-fill',
    'within_state' => 'bi-map',
    'metro' => 'bi-buildings',
    'rest_of_india' => 'bi-globe-asia-australia',
];
$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
$serviceIcons = ['standard'=>'bi-truck','premium'=>'bi-lightning-fill','air_cargo'=>'bi-airplane','surface'=>'bi-boxes'];

function fmtWeightLabel($weight): string
{
    if ($weight === null || $weight === '') return '∞';
    $w = (float)$weight;
    return $w < 1 ? number_format($w * 1000, 0) . ' g' : number_format($w, 2) . ' kg';
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Customer Earnings</h4>
        <p>Manage customer-wise earning percentage by zone, service and weight slab</p>
    </div>
    <button class="btn-primary-admin" onclick="saveCustomerEarnings()">
        <i class="bi bi-check-lg"></i> Save Earnings
    </button>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="get" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">
            <div style="min-width:280px;flex:1;">
                <label class="admin-form-label">Customer</label>
                <select class="admin-select" name="customer_id" onchange="this.form.submit()">
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $selectedCustomerId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?><?= $c['company_name'] ? ' - ' . htmlspecialchars($c['company_name']) : '' ?> (<?= htmlspecialchars($c['email']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="font-size:12px;color:var(--muted);max-width:520px;">
                Blank boxes are treated as 0%. New bookings will snapshot the matching earning percentage at booking time.
            </div>
        </form>
    </div>
</div>

<ul class="nav pricing-zone-tabs mb-4" id="earningZoneTabs" role="tablist">
    <?php foreach ($zoneKeys as $i => $zk): ?>
    <li class="nav-item" role="presentation">
        <button class="pricing-zone-tab <?= $i === 0 ? 'active' : '' ?>"
                data-bs-toggle="tab"
                data-bs-target="#earning-zone-<?= $zk ?>"
                type="button" role="tab">
            <i class="bi <?= $zoneIcons[$zk] ?>"></i>
            <?= $zoneLabels[$zk] ?>
            <?php
            $cnt = 0;
            foreach ($serviceLabels as $st => $_) $cnt += count($grouped[$zk][$st] ?? []);
            ?>
            <span class="pricing-zone-count"><?= $cnt ?></span>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
<?php foreach ($zoneKeys as $i => $zk): ?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="earning-zone-<?= $zk ?>" role="tabpanel">
    <?php foreach (['standard','premium','air_cargo','surface'] as $svcType): ?>
    <div class="admin-card mb-3">
        <div class="service-group-header d-flex align-items-center gap-2">
            <i class="<?= $serviceIcons[$svcType] ?>"></i>
            <?= $serviceLabels[$svcType] ?>
            <span class="pricing-slab-count"><?= count($grouped[$zk][$svcType] ?? []) ?> slab<?= count($grouped[$zk][$svcType] ?? []) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Weight From</th>
                        <th>Weight To</th>
                        <th>Base Price</th>
                        <th>Increment Rs.</th>
                        <th>Per (kg)</th>
                        <th>Type</th>
                        <th>Earning %</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($grouped[$zk][$svcType])): ?>
                    <tr><td colspan="7"><div class="empty-state" style="padding:16px;"><p>No pricing slabs configured</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($grouped[$zk][$svcType] as $slab): ?>
                    <tr>
                        <td><?= htmlspecialchars(fmtWeightLabel($slab['weight_from'])) ?></td>
                        <td><?= htmlspecialchars(fmtWeightLabel($slab['weight_to'])) ?></td>
                        <td style="font-weight:700;color:var(--primary);">Rs.<?= number_format((float)$slab['base_price'], 2) ?></td>
                        <td><?= $slab['increment_price'] !== null ? 'Rs.' . number_format((float)$slab['increment_price'], 2) : '<span style="color:var(--muted)">-</span>' ?></td>
                        <td><?= number_format((float)$slab['increment_per_kg'], 3) ?> kg</td>
                        <td>
                            <?= $slab['weight_to'] === null
                                ? '<span class="badge-status badge-in_transit" style="font-size:10px;">Incremental</span>'
                                : '<span class="badge-status badge-booked" style="font-size:10px;">Fixed</span>' ?>
                        </td>
                        <td style="max-width:150px;">
                            <div class="input-group input-group-sm" style="max-width:130px;">
                                <input type="number" class="form-control earning-input"
                                       data-slab-id="<?= (int)$slab['id'] ?>"
                                       min="0" max="100" step="0.01"
                                       value="<?= htmlspecialchars(number_format((float)($earningMap[$slab['id']] ?? 0), 2, '.', '')) ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>

<style>
.pricing-zone-tabs { display:flex; gap:8px; flex-wrap:wrap; list-style:none; padding:0; margin:0; }
.pricing-zone-tab {
    display:inline-flex; align-items:center; gap:7px;
    background:#fff; border:1.5px solid var(--border);
    border-radius:12px; padding:9px 18px;
    font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; color:var(--text);
    cursor:pointer; transition:all .2s;
}
.pricing-zone-tab:hover { border-color:var(--primary); color:var(--primary); }
.pricing-zone-tab.active {
    background:var(--primary); color:#fff; border-color:var(--primary);
    box-shadow:0 4px 14px rgba(0,26,147,.2);
}
.pricing-zone-count {
    background:rgba(255,255,255,.25); color:inherit;
    border-radius:20px; padding:1px 8px; font-size:11px; font-weight:700;
}
.pricing-zone-tab:not(.active) .pricing-zone-count { background:rgba(0,26,147,.08); color:var(--primary); }
.pricing-slab-count {
    background:rgba(0,26,147,.08); color:var(--primary);
    border-radius:20px; padding:1px 8px; font-size:11px; font-weight:600;
}
.earning-input { text-align:right; font-weight:700; }
</style>

<script>
function saveCustomerEarnings() {
    const customerId = <?= (int)$selectedCustomerId ?>;
    if (!customerId) { showToast('Select a customer first', 'warning'); return; }
    const entries = Array.from(document.querySelectorAll('.earning-input')).map(input => ({
        pricing_slab_id: Number(input.dataset.slabId),
        earning_pct: Number(input.value || 0)
    }));
    if (entries.some(e => Number.isNaN(e.earning_pct) || e.earning_pct < 0 || e.earning_pct > 100)) {
        showToast('Earning percentage must be between 0 and 100', 'warning');
        return;
    }

    fetch('<?= SITE_URL ?>/api/admin/customer-earnings.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({customer_id: customerId, entries}),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) showToast('Customer earnings saved', 'success');
        else showToast(data.message || 'Save failed', 'error');
    })
    .catch(() => showToast('Network error', 'error'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
