<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Pricing Management';
$activePage = 'pricing';

$slabs = [];
try {
    $slabs = $pdo->query("SELECT * FROM pricing_slabs ORDER BY service_type, sort_order, weight_from")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$grouped = [];
foreach ($slabs as $s) $grouped[$s['service_type']][] = $s;

$serviceLabels = ['standard'=>'Standard Express','premium'=>'Premium Express','air_cargo'=>'Air Cargo','surface'=>'Surface Cargo'];
$serviceIcons  = ['standard'=>'bi-truck','premium'=>'bi-lightning-fill','air_cargo'=>'bi-airplane','surface'=>'bi-boxes'];

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Pricing Management</h4>
        <p>Configure weight-based pricing slabs for each service type</p>
    </div>
    <button class="btn-primary-admin" onclick="openAddSlab()">
        <i class="bi bi-plus-lg"></i> Add Slab
    </button>
</div>

<!-- Info box -->
<div class="alert alert-info d-flex gap-2 mb-4" style="font-size:13px;border-radius:12px;border:none;background:rgba(59,130,246,0.08);color:#1d4ed8;">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
        <strong>How pricing works:</strong> Each service type has weight slabs. Fixed slabs have a set price. Open-ended slabs (no upper limit) use <code>Base Price + ⌈(weight − from) ÷ increment_per_kg⌉ × increment_price</code>.
    </div>
</div>

<?php foreach (['standard','premium','air_cargo','surface'] as $svcType): ?>
<div class="admin-card mb-4">
    <div class="service-group-header d-flex align-items-center gap-2">
        <i class="<?= $serviceIcons[$svcType] ?>"></i>
        <?= $serviceLabels[$svcType] ?>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Weight From (kg)</th>
                    <th>Weight To (kg)</th>
                    <th>Base Price (₹)</th>
                    <th>Increment Price (₹)</th>
                    <th>Increment Per (kg)</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbody_<?= $svcType ?>">
                <?php if (empty($grouped[$svcType])): ?>
                <tr><td colspan="7"><div class="empty-state" style="padding:20px;"><i class="bi bi-tags"></i><p>No slabs defined for this service</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($grouped[$svcType] as $slab): ?>
                <tr class="slab-row" id="slab_<?= $slab['id'] ?>">
                    <td style="font-size:13px;"><?= number_format((float)$slab['weight_from'], 3) ?> kg <?= (float)$slab['weight_from'] * 1000 >= 1000 ? '(' . number_format((float)$slab['weight_from'], 2) . 'kg)' : '(' . number_format((float)$slab['weight_from'] * 1000, 0) . 'g)' ?></td>
                    <td style="font-size:13px;">
                        <?php if ($slab['weight_to'] === null): ?>
                        <span style="color:var(--muted);">∞ (open-ended)</span>
                        <?php else: ?>
                        <?= number_format((float)$slab['weight_to'], 3) ?> kg
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;font-weight:600;">₹<?= number_format((float)$slab['base_price'], 2) ?></td>
                    <td style="font-size:13px;">
                        <?= $slab['increment_price'] !== null ? '₹' . number_format((float)$slab['increment_price'], 2) : '<span style="color:var(--muted)">—</span>' ?>
                    </td>
                    <td style="font-size:13px;"><?= number_format((float)$slab['increment_per_kg'], 3) ?> kg</td>
                    <td>
                        <?php if ($slab['weight_to'] === null): ?>
                        <span class="badge-status badge-in_transit" style="font-size:10px;">Incremental</span>
                        <?php else: ?>
                        <span class="badge-status badge-booked" style="font-size:10px;">Fixed</span>
                        <?php endif; ?>
                    </td>
                    <td class="slab-actions">
                        <div class="d-flex gap-1">
                            <button class="btn-action" onclick="editSlab(<?= htmlspecialchars(json_encode($slab)) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                            <button class="btn-action danger" onclick="confirmAction('Delete this pricing slab?', ()=>deleteSlab(<?= $slab['id'] ?>))" title="Delete"><i class="bi bi-trash"></i></button>
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

<!-- Add/Edit Slab Modal -->
<div class="modal fade admin-modal" id="slabModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="slabModalTitle"><i class="bi bi-tags me-2"></i>Add Pricing Slab</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="slab_id" value="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="admin-form-label">Service Type <span style="color:red">*</span></label>
                        <select class="admin-select" id="slab_service_type">
                            <option value="standard">Standard Express</option>
                            <option value="premium">Premium Express</option>
                            <option value="air_cargo">Air Cargo</option>
                            <option value="surface">Surface Cargo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Slab Type <span style="color:red">*</span></label>
                        <select class="admin-select" id="slab_type_sel" onchange="toggleSlabType(this.value)">
                            <option value="fixed">Fixed Price</option>
                            <option value="incremental">Incremental</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Weight From (kg) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_weight_from" step="0.001" min="0" placeholder="e.g. 0.000">
                    </div>
                    <div class="col-md-6" id="weight_to_wrap">
                        <label class="admin-form-label">Weight To (kg) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_weight_to" step="0.001" min="0" placeholder="e.g. 0.250">
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Base Price (₹) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_base_price" step="0.01" min="0" placeholder="e.g. 100.00">
                    </div>
                    <div class="col-md-6" id="increment_wrap">
                        <label class="admin-form-label">Increment Price (₹) <span style="color:red">*</span></label>
                        <input type="number" class="admin-form-control" id="slab_increment_price" step="0.01" min="0" placeholder="e.g. 60.00">
                    </div>
                    <div class="col-md-6" id="increment_per_wrap">
                        <label class="admin-form-label">Increment Per (kg)</label>
                        <input type="number" class="admin-form-control" id="slab_increment_per_kg" step="0.001" min="0.001" value="0.500" placeholder="0.500">
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Sort Order</label>
                        <input type="number" class="admin-form-control" id="slab_sort_order" min="0" value="1">
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

<script>
function toggleSlabType(val) {
    const isInc = val === 'incremental';
    document.getElementById('weight_to_wrap').style.display   = isInc ? 'none' : 'block';
    document.getElementById('increment_wrap').style.display   = isInc ? 'block' : 'none';
    document.getElementById('increment_per_wrap').style.display = isInc ? 'block' : 'none';
}

function openAddSlab() {
    document.getElementById('slab_id').value = '';
    document.getElementById('slabModalTitle').innerHTML = '<i class="bi bi-tags me-2"></i>Add Pricing Slab';
    document.getElementById('slab_weight_from').value = '';
    document.getElementById('slab_weight_to').value = '';
    document.getElementById('slab_base_price').value = '';
    document.getElementById('slab_increment_price').value = '';
    document.getElementById('slab_increment_per_kg').value = '0.500';
    document.getElementById('slab_sort_order').value = '1';
    document.getElementById('slab_type_sel').value = 'fixed';
    toggleSlabType('fixed');
    new bootstrap.Modal(document.getElementById('slabModal')).show();
}

function editSlab(slab) {
    document.getElementById('slab_id').value = slab.id;
    document.getElementById('slabModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pricing Slab';
    document.getElementById('slab_service_type').value = slab.service_type;
    document.getElementById('slab_weight_from').value = slab.weight_from;
    document.getElementById('slab_weight_to').value = slab.weight_to || '';
    document.getElementById('slab_base_price').value = slab.base_price;
    document.getElementById('slab_increment_price').value = slab.increment_price || '';
    document.getElementById('slab_increment_per_kg').value = slab.increment_per_kg || '0.500';
    document.getElementById('slab_sort_order').value = slab.sort_order || 1;
    const isInc = slab.weight_to === null || slab.weight_to === '';
    document.getElementById('slab_type_sel').value = isInc ? 'incremental' : 'fixed';
    toggleSlabType(isInc ? 'incremental' : 'fixed');
    new bootstrap.Modal(document.getElementById('slabModal')).show();
}

function saveSlab() {
    const btn = document.getElementById('saveSlabBtn');
    const isInc = document.getElementById('slab_type_sel').value === 'incremental';
    const payload = {
        id: document.getElementById('slab_id').value || null,
        service_type: document.getElementById('slab_service_type').value,
        weight_from: parseFloat(document.getElementById('slab_weight_from').value) || 0,
        weight_to: isInc ? null : (parseFloat(document.getElementById('slab_weight_to').value) || null),
        base_price: parseFloat(document.getElementById('slab_base_price').value) || 0,
        increment_price: isInc ? (parseFloat(document.getElementById('slab_increment_price').value) || null) : null,
        increment_per_kg: parseFloat(document.getElementById('slab_increment_per_kg').value) || 0.5,
        sort_order: parseInt(document.getElementById('slab_sort_order').value) || 1,
    };

    if (!payload.service_type || payload.base_price < 0) { showToast('Fill all required fields', 'warning'); return; }

    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    fetch('<?= SITE_URL ?>/api/admin/pricing.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Slab';
        if (data.success) {
            showToast('Pricing slab saved successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('slabModal')).hide();
            setTimeout(() => location.reload(), 800);
        } else { showToast(data.message || 'Save failed', 'error'); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Slab'; showToast('Network error', 'error'); });
}

function deleteSlab(id) {
    fetch('<?= SITE_URL ?>/api/admin/pricing.php', {
        method: 'DELETE', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id}), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast('Slab deleted', 'success'); document.getElementById(`slab_${id}`).remove(); }
        else showToast(data.message || 'Delete failed', 'error');
    })
    .catch(() => showToast('Network error', 'error'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
