<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Pincodes / TAT';
$activePage = 'pincodes';

$search = trim($_GET['q'] ?? '');
$params = [];
$where  = "WHERE 1=1";
if ($search) {
    $where .= " AND (pincode LIKE ? OR city LIKE ? OR state LIKE ?)";
    $like = "%$search%"; $params = [$like, $like, $like];
}

$pincodes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM pincode_tat $where ORDER BY pincode ASC LIMIT 500");
    $stmt->execute($params);
    $pincodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = (int) $pdo->query("SELECT COUNT(*) FROM pincode_tat")->fetchColumn();
} catch (Exception $e) { $total = 0; }

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Pincodes / TAT Details</h4>
        <p><?= number_format($total) ?> pincodes in database</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-outline-admin" onclick="document.getElementById('csvUpload').click()">
            <i class="bi bi-upload me-1"></i> Import CSV
        </button>
        <input type="file" id="csvUpload" accept=".csv" style="display:none" onchange="importCsv(this)">
        <button class="btn-primary-admin" onclick="openAddPincode()">
            <i class="bi bi-plus-lg"></i> Add Pincode
        </button>
    </div>
</div>

<div class="alert d-flex gap-2 mb-4" style="font-size:12px;border-radius:12px;border:none;background:rgba(245,158,11,0.08);color:#92400e;">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>Import a CSV with columns: <code>pincode, city, state, zone, tat_standard, tat_premium, tat_air, tat_surface, serviceable</code>. Existing pincodes will be updated.</div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title"><i class="bi bi-geo-alt me-2"></i>Pincode List</h6>
        <div class="filter-bar">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search pincode, city, state…" data-search-table="pincodesTable" value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table" id="pincodesTable">
            <thead>
                <tr>
                    <th>Pincode</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zone</th>
                    <th>Std TAT</th>
                    <th>Prm TAT</th>
                    <th>Air TAT</th>
                    <th>Srf TAT</th>
                    <th>Serviceable</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pincodes)): ?>
                <tr><td colspan="10"><div class="empty-state"><i class="bi bi-geo-alt"></i><p>No pincodes yet. Import a CSV or add manually.</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($pincodes as $p): ?>
                <tr id="pc_<?= $p['id'] ?>">
                    <td style="font-size:13px;font-weight:700;"><?= htmlspecialchars($p['pincode']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($p['city']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($p['state']) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($p['zone'] ?: '—') ?></td>
                    <td style="text-align:center;font-size:13px;"><?= $p['tat_standard'] ?> day<?= $p['tat_standard'] > 1 ? 's' : '' ?></td>
                    <td style="text-align:center;font-size:13px;"><?= $p['tat_premium'] ?> day<?= $p['tat_premium'] > 1 ? 's' : '' ?></td>
                    <td style="text-align:center;font-size:13px;"><?= $p['tat_air'] ?> day<?= $p['tat_air'] > 1 ? 's' : '' ?></td>
                    <td style="text-align:center;font-size:13px;"><?= $p['tat_surface'] ?> day<?= $p['tat_surface'] > 1 ? 's' : '' ?></td>
                    <td style="text-align:center;">
                        <span class="badge-status <?= $p['serviceable'] ? 'badge-approved' : 'badge-rejected' ?>" style="font-size:10px;">
                            <?= $p['serviceable'] ? 'Yes' : 'No' ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn-action" onclick="editPincode(<?= htmlspecialchars(json_encode($p)) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                            <button class="btn-action danger" onclick="confirmAction('Delete this pincode?', ()=>deletePincode(<?= $p['id'] ?>))" title="Delete"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Pincode Modal -->
<div class="modal fade admin-modal" id="pincodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="pincodeModalTitle"><i class="bi bi-geo-alt me-2"></i>Add Pincode</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pc_id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="admin-form-label">Pincode *</label><input type="text" class="admin-form-control" id="pc_pincode" maxlength="10" placeholder="411001"></div>
                    <div class="col-md-4"><label class="admin-form-label">City *</label><input type="text" class="admin-form-control" id="pc_city" placeholder="Pune"></div>
                    <div class="col-md-4"><label class="admin-form-label">State *</label><input type="text" class="admin-form-control" id="pc_state" placeholder="Maharashtra"></div>
                    <div class="col-md-4"><label class="admin-form-label">Zone</label><input type="text" class="admin-form-control" id="pc_zone" placeholder="Metro / Tier1 / Tier2"></div>
                    <div class="col-md-2"><label class="admin-form-label">Std TAT (days)</label><input type="number" class="admin-form-control" id="pc_tat_standard" min="1" value="3"></div>
                    <div class="col-md-2"><label class="admin-form-label">Premium TAT</label><input type="number" class="admin-form-control" id="pc_tat_premium" min="1" value="1"></div>
                    <div class="col-md-2"><label class="admin-form-label">Air TAT</label><input type="number" class="admin-form-control" id="pc_tat_air" min="1" value="2"></div>
                    <div class="col-md-2"><label class="admin-form-label">Surface TAT</label><input type="number" class="admin-form-control" id="pc_tat_surface" min="1" value="5"></div>
                    <div class="col-md-4">
                        <label class="admin-form-label">Serviceable</label>
                        <select class="admin-select" id="pc_serviceable">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-admin" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-primary-admin" onclick="savePincode()"><i class="bi bi-check-lg me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function openAddPincode() {
    document.getElementById('pc_id').value = '';
    document.getElementById('pincodeModalTitle').innerHTML = '<i class="bi bi-geo-alt me-2"></i>Add Pincode';
    ['pc_pincode','pc_city','pc_state','pc_zone'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('pc_tat_standard').value = 3;
    document.getElementById('pc_tat_premium').value  = 1;
    document.getElementById('pc_tat_air').value      = 2;
    document.getElementById('pc_tat_surface').value  = 5;
    document.getElementById('pc_serviceable').value  = '1';
    new bootstrap.Modal(document.getElementById('pincodeModal')).show();
}

function editPincode(p) {
    document.getElementById('pc_id').value = p.id;
    document.getElementById('pincodeModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pincode';
    document.getElementById('pc_pincode').value = p.pincode;
    document.getElementById('pc_city').value    = p.city;
    document.getElementById('pc_state').value   = p.state;
    document.getElementById('pc_zone').value    = p.zone || '';
    document.getElementById('pc_tat_standard').value = p.tat_standard;
    document.getElementById('pc_tat_premium').value  = p.tat_premium;
    document.getElementById('pc_tat_air').value      = p.tat_air;
    document.getElementById('pc_tat_surface').value  = p.tat_surface;
    document.getElementById('pc_serviceable').value  = p.serviceable;
    new bootstrap.Modal(document.getElementById('pincodeModal')).show();
}

function savePincode() {
    const payload = {
        id: document.getElementById('pc_id').value || null,
        pincode: document.getElementById('pc_pincode').value.trim(),
        city: document.getElementById('pc_city').value.trim(),
        state: document.getElementById('pc_state').value.trim(),
        zone: document.getElementById('pc_zone').value.trim(),
        tat_standard: parseInt(document.getElementById('pc_tat_standard').value) || 3,
        tat_premium: parseInt(document.getElementById('pc_tat_premium').value) || 1,
        tat_air: parseInt(document.getElementById('pc_tat_air').value) || 2,
        tat_surface: parseInt(document.getElementById('pc_tat_surface').value) || 5,
        serviceable: parseInt(document.getElementById('pc_serviceable').value),
    };
    if (!payload.pincode || !payload.city || !payload.state) { showToast('Fill required fields', 'warning'); return; }
    fetch('<?= SITE_URL ?>/api/admin/pincodes.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast('Pincode saved', 'success'); bootstrap.Modal.getInstance(document.getElementById('pincodeModal')).hide(); setTimeout(() => location.reload(), 700); }
        else showToast(data.message || 'Save failed', 'error');
    })
    .catch(() => showToast('Network error', 'error'));
}

function deletePincode(id) {
    fetch('<?= SITE_URL ?>/api/admin/pincodes.php', {
        method: 'DELETE', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id}), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast('Deleted', 'success'); document.getElementById(`pc_${id}`).remove(); }
        else showToast(data.message || 'Delete failed', 'error');
    });
}

function importCsv(input) {
    const file = input.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('csv', file);
    showToast('Importing CSV…', 'default', 60000);
    fetch('<?= SITE_URL ?>/api/admin/pincodes.php?action=import', {
        method: 'POST', body: formData, credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast(`Imported ${data.count} pincodes successfully`, 'success', 4000); setTimeout(() => location.reload(), 1200); }
        else showToast(data.message || 'Import failed', 'error');
    })
    .catch(() => showToast('Import failed', 'error'));
    input.value = '';
}
</script>

<?php require_once 'includes/footer.php'; ?>
