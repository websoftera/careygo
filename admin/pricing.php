<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Pricing Management';
$activePage = 'pricing';

$slabs = [];
try {
    $slabs = $pdo->query(
        "SELECT * FROM pricing_slabs
         ORDER BY FIELD(zone,'within_city','within_state','metro','rest_of_india',NULL),
                  service_type, sort_order, weight_from"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Group: zone → service_type → slabs
$grouped = [];
foreach ($slabs as $s) {
    $z = $s['zone'] ?? 'rest_of_india';
    $grouped[$z][$s['service_type']][] = $s;
}

$zoneKeys   = ['within_city','within_state','metro','rest_of_india'];
$zoneLabels = [
    'within_city'   => 'Within City',
    'within_state'  => 'Within State',
    'metro'         => 'Metro',
    'rest_of_india' => 'Rest of India',
];
$zoneIcons  = [
    'within_city'   => 'bi-geo-alt-fill',
    'within_state'  => 'bi-map',
    'metro'         => 'bi-buildings',
    'rest_of_india' => 'bi-globe-asia-australia',
];

$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
$serviceIcons  = ['standard'=>'bi-truck','premium'=>'bi-lightning-fill','air_cargo'=>'bi-airplane','surface'=>'bi-boxes'];

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Pricing Management</h4>
        <p>Configure zone &amp; weight-based pricing slabs for each service type</p>
    </div>
    <button class="btn-primary-admin" onclick="openAddSlab()">
        <i class="bi bi-plus-lg"></i> Add Slab
    </button>
</div>

<!-- Packing Material Charge Card -->
<div class="admin-card mb-4" id="packing-material-charge">
    <div class="admin-card-header">
        <h6 class="admin-card-title"><i class="bi bi-box2-heart me-2"></i>Packing Material Charge</h6>
    </div>
    <div class="admin-card-body">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label class="admin-form-label">Charge per Shipment (₹)</label>
                <input type="number" class="admin-form-control" id="packing_charge_input"
                       step="0.01" min="0" placeholder="e.g. 50.00" style="max-width:200px;">
                <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                    This charge is shown to customers when they select "Packing Material" during booking.
                </div>
            </div>
            <div>
                <button class="btn-primary-admin" onclick="savePackingCharge()" id="savePackingBtn">
                    <i class="bi bi-check-lg me-1"></i> Save Charge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Info box -->
<div class="alert d-flex gap-2 mb-4" style="font-size:13px;border-radius:12px;border:none;background:rgba(59,130,246,0.08);color:#1d4ed8;padding:14px 18px;">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
        <strong>How pricing works:</strong>
        Rates are split by delivery zone (Within City / Within State / Metro / Rest of India) and service type.
        Fixed slabs: flat price up to the weight limit.
        Incremental slabs: <code>Base Price + ⌈(weight − from) ÷ per_kg⌉ × increment</code>.
    </div>
</div>

<!-- Zone Tabs -->
<ul class="nav pricing-zone-tabs mb-4" id="zoneTabs" role="tablist">
    <?php foreach ($zoneKeys as $i => $zk): ?>
    <li class="nav-item" role="presentation">
        <button class="pricing-zone-tab <?= $i === 0 ? 'active' : '' ?>"
                id="tab-<?= $zk ?>"
                data-bs-toggle="tab"
                data-bs-target="#zone-<?= $zk ?>"
                type="button" role="tab"
                onclick="setActiveZone('<?= $zk ?>')">
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

<!-- Zone Tab Content -->
<div class="tab-content" id="zoneTabContent">
<?php foreach ($zoneKeys as $i => $zk): ?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
     id="zone-<?= $zk ?>" role="tabpanel">

    <?php foreach (['standard','premium','air_cargo','surface'] as $svcType): ?>
    <div class="admin-card mb-3">
        <div class="service-group-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="<?= $serviceIcons[$svcType] ?>"></i>
                <?= $serviceLabels[$svcType] ?>
                <span class="pricing-slab-count">
                    <?= count($grouped[$zk][$svcType] ?? []) ?> slab<?= count($grouped[$zk][$svcType] ?? []) !== 1 ? 's' : '' ?>
                </span>
            </div>
            <button class="btn-action" style="font-size:11px;padding:4px 10px;gap:4px;display:inline-flex;align-items:center;"
                    onclick="openAddSlab('<?= $zk ?>','<?= $svcType ?>')">
                <i class="bi bi-plus-lg"></i> Add Slab
            </button>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Weight From</th>
                        <th>Weight To</th>
                        <th>Base Price</th>
                        <th>Increment ₹</th>
                        <th>Per (kg)</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tbody_<?= $zk ?>_<?= $svcType ?>">
                    <?php if (empty($grouped[$zk][$svcType])): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state" style="padding:16px;">
                                <i class="bi bi-tags" style="font-size:20px;"></i>
                                <p style="font-size:12px;margin:6px 0 0;">No slabs — click Add Slab to configure</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($grouped[$zk][$svcType] as $slab): ?>
                    <tr class="slab-row" id="slab_<?= $slab['id'] ?>">
                        <td style="font-size:13px;">
                            <?php
                            $wFrom = (float)$slab['weight_from'];
                            echo $wFrom < 1
                                ? number_format($wFrom * 1000, 0) . ' g'
                                : number_format($wFrom, 2) . ' kg';
                            ?>
                        </td>
                        <td style="font-size:13px;">
                            <?php if ($slab['weight_to'] === null): ?>
                            <span style="color:var(--muted);">∞</span>
                            <?php else: ?>
                            <?php
                            $wTo = (float)$slab['weight_to'];
                            echo $wTo < 1
                                ? number_format($wTo * 1000, 0) . ' g'
                                : number_format($wTo, 2) . ' kg';
                            ?>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;font-weight:700;color:var(--primary);">
                            ₹<?= number_format((float)$slab['base_price'], 2) ?>
                        </td>
                        <td style="font-size:13px;">
                            <?= $slab['increment_price'] !== null
                                ? '₹' . number_format((float)$slab['increment_price'], 2)
                                : '<span style="color:var(--muted)">—</span>' ?>
                        </td>
                        <td style="font-size:13px;">
                            <?= number_format((float)$slab['increment_per_kg'], 3) ?> kg
                        </td>
                        <td>
                            <?php if ($slab['weight_to'] === null): ?>
                            <span class="badge-status badge-in_transit" style="font-size:10px;">Incremental</span>
                            <?php else: ?>
                            <span class="badge-status badge-booked" style="font-size:10px;">Fixed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn-action"
                                        onclick="editSlab(<?= htmlspecialchars(json_encode($slab)) ?>)"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-action danger"
                                        onclick="confirmAction('Delete this pricing slab?', ()=>deleteSlab(<?= $slab['id'] ?>, '<?= $zk ?>', '<?= $svcType ?>'))"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
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

</div><!-- /tab-pane -->
<?php endforeach; ?>
</div><!-- /tab-content -->


<!-- ── Add / Edit Slab Modal ── -->
<div class="modal fade admin-modal" id="slabModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="slabModalTitle">
                    <i class="bi bi-tags me-2"></i>Add Pricing Slab
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="slab_id" value="">
                <div class="row g-3">
                    <!-- Row 1: Zone + Service -->
                    <div class="col-md-6">
                        <label class="admin-form-label">Zone <span style="color:red">*</span></label>
                        <select class="admin-select" id="slab_zone">
                            <option value="within_city">Within City</option>
                            <option value="within_state">Within State</option>
                            <option value="metro">Metro</option>
                            <option value="rest_of_india">Rest of India</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Service Type <span style="color:red">*</span></label>
                        <select class="admin-select" id="slab_service_type">
                            <option value="standard">Standard Express</option>
                            <option value="premium">Premium Express</option>
                            <option value="air_cargo">Air Cargo</option>
                            <option value="surface">Surface Cargo</option>
                        </select>
                    </div>
                    <!-- Row 2: Slab type + Weight from -->
                    <div class="col-md-6">
                        <label class="admin-form-label">Slab Type <span style="color:red">*</span></label>
                        <select class="admin-select" id="slab_type_sel" onchange="toggleSlabType(this.value)">
                            <option value="fixed">Fixed Price</option>
                            <option value="incremental">Incremental</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Weight From (kg) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_weight_from"
                               step="0.001" min="0" placeholder="e.g. 0.000">
                    </div>
                    <!-- Row 3: Weight To (hidden for incremental) + Base Price -->
                    <div class="col-md-6" id="weight_to_wrap">
                        <label class="admin-form-label">Weight To (kg) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_weight_to"
                               step="0.001" min="0" placeholder="e.g. 0.500">
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Base Price (₹) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_base_price"
                               step="0.01" min="0" placeholder="e.g. 100.00">
                    </div>
                    <!-- Row 4: Increment price + per kg (hidden for fixed) -->
                    <div class="col-md-6" id="increment_wrap" style="display:none;">
                        <label class="admin-form-label">Increment Price (₹/block) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_increment_price"
                               step="0.01" min="0" placeholder="e.g. 60.00">
                    </div>
                    <div class="col-md-6" id="increment_per_wrap" style="display:none;">
                        <label class="admin-form-label">Increment Block (kg)</label>
                        <input type="number" class="admin-form-control" id="slab_increment_per_kg"
                               step="0.001" min="0.001" value="0.500">
                    </div>
                    <!-- Row 5: Sort order -->
                    <div class="col-md-6">
                        <label class="admin-form-label">Sort Order</label>
                        <input type="number" class="admin-form-control" id="slab_sort_order"
                               min="0" value="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-admin" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-primary-admin" id="saveSlabBtn" onclick="saveSlab()">
                    <i class="bi bi-check-lg me-1"></i> Save Slab
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Zone tabs ── */
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
.pricing-zone-tab i { font-size:15px; }
.pricing-zone-count {
    background:rgba(255,255,255,.25); color:inherit;
    border-radius:20px; padding:1px 8px; font-size:11px; font-weight:700;
}
.pricing-zone-tab:not(.active) .pricing-zone-count {
    background:rgba(0,26,147,.08); color:var(--primary);
}
.pricing-slab-count {
    background:rgba(0,26,147,.08); color:var(--primary);
    border-radius:20px; padding:1px 8px; font-size:11px; font-weight:600;
    margin-left:4px;
}
</style>

<script>
// ── Packing charge ──
(function loadPackingCharge() {
    fetch('<?= SITE_URL ?>/api/admin/settings.php?key=packing_charge', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const inp = document.getElementById('packing_charge_input');
            if (inp && data.value !== null && data.value !== undefined) inp.value = data.value;
        })
        .catch(() => {});
})();

function savePackingCharge() {
    const btn = document.getElementById('savePackingBtn');
    const val = document.getElementById('packing_charge_input').value;
    const amount = parseFloat(val);
    if (val === '' || isNaN(amount) || amount < 0) { showToast('Enter a valid charge amount', 'warning'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

    fetch('<?= SITE_URL ?>/api/admin/settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: 'packing_charge', value: amount.toFixed(2) }),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Charge';
        if (data.success) showToast(`Packing charge set to ₹${parseFloat(val).toFixed(2)}`, 'success');
        else showToast(data.message || 'Save failed', 'error');
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Charge';
        showToast('Network error', 'error');
    });
}

let _activeZone = '<?= $zoneKeys[0] ?>';
function setActiveZone(z) { _activeZone = z; }

function toggleSlabType(val) {
    const isInc = val === 'incremental';
    document.getElementById('weight_to_wrap').style.display    = isInc ? 'none'  : 'block';
    document.getElementById('increment_wrap').style.display    = isInc ? 'block' : 'none';
    document.getElementById('increment_per_wrap').style.display = isInc ? 'block' : 'none';
}

function openAddSlab(zone, svcType) {
    document.getElementById('slab_id').value = '';
    document.getElementById('slabModalTitle').innerHTML = '<i class="bi bi-tags me-2"></i>Add Pricing Slab';
    document.getElementById('slab_zone').value         = zone || _activeZone;
    document.getElementById('slab_service_type').value = svcType || 'standard';
    document.getElementById('slab_weight_from').value  = '';
    document.getElementById('slab_weight_to').value    = '';
    document.getElementById('slab_base_price').value   = '';
    document.getElementById('slab_increment_price').value  = '';
    document.getElementById('slab_increment_per_kg').value = '0.500';
    document.getElementById('slab_sort_order').value   = '1';
    document.getElementById('slab_type_sel').value     = 'fixed';
    toggleSlabType('fixed');
    new bootstrap.Modal(document.getElementById('slabModal')).show();
}

function editSlab(slab) {
    document.getElementById('slab_id').value    = slab.id;
    document.getElementById('slabModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pricing Slab';
    document.getElementById('slab_zone').value         = slab.zone || 'rest_of_india';
    document.getElementById('slab_service_type').value = slab.service_type;
    document.getElementById('slab_weight_from').value  = slab.weight_from;
    document.getElementById('slab_weight_to').value    = slab.weight_to || '';
    document.getElementById('slab_base_price').value   = slab.base_price;
    document.getElementById('slab_increment_price').value  = slab.increment_price || '';
    document.getElementById('slab_increment_per_kg').value = slab.increment_per_kg || '0.500';
    document.getElementById('slab_sort_order').value   = slab.sort_order || 1;
    const isInc = slab.weight_to === null || slab.weight_to === '';
    document.getElementById('slab_type_sel').value = isInc ? 'incremental' : 'fixed';
    toggleSlabType(isInc ? 'incremental' : 'fixed');
    new bootstrap.Modal(document.getElementById('slabModal')).show();
}

function saveSlab() {
    const btn   = document.getElementById('saveSlabBtn');
    const isInc = document.getElementById('slab_type_sel').value === 'incremental';
    const payload = {
        id:            document.getElementById('slab_id').value || null,
        zone:          document.getElementById('slab_zone').value,
        service_type:  document.getElementById('slab_service_type').value,
        weight_from:   parseFloat(document.getElementById('slab_weight_from').value) || 0,
        weight_to:     isInc ? null : (parseFloat(document.getElementById('slab_weight_to').value) || null),
        base_price:    parseFloat(document.getElementById('slab_base_price').value) || 0,
        increment_price:  isInc ? (parseFloat(document.getElementById('slab_increment_price').value) || null) : null,
        increment_per_kg: parseFloat(document.getElementById('slab_increment_per_kg').value) || 0.5,
        sort_order:    parseInt(document.getElementById('slab_sort_order').value) || 1,
    };

    if (!payload.service_type || !payload.zone) {
        showToast('Please select a zone and service type', 'warning'); return;
    }
    if (payload.base_price < 0) {
        showToast('Base price cannot be negative', 'warning'); return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

    fetch('<?= SITE_URL ?>/api/admin/pricing.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Slab';
        if (data.success) {
            showToast('Pricing slab saved', 'success');
            bootstrap.Modal.getInstance(document.getElementById('slabModal')).hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Slab';
        showToast('Network error', 'error');
    });
}

function deleteSlab(id, zone, svcType) {
    fetch('<?= SITE_URL ?>/api/admin/pricing.php', {
        method: 'DELETE', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id}), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Slab deleted', 'success');
            const row = document.getElementById(`slab_${id}`);
            if (row) row.remove();
        } else {
            showToast(data.message || 'Delete failed', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
