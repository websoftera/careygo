<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Deliveries';
$activePage = 'deliveries';

$filter  = $_GET['status'] ?? '';
$service = $_GET['service'] ?? '';
$search  = trim($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($filter && in_array($filter, ['booked','picked_up','in_transit','out_for_delivery','delivered','cancelled'])) {
    $where .= " AND s.status = ?"; $params[] = $filter;
}
if ($service && in_array($service, ['standard','premium','surface','air_cargo'])) {
    $where .= " AND s.service_type = ?"; $params[] = $service;
}
if ($search) {
    $where .= " AND (s.tracking_no LIKE ? OR s.pickup_city LIKE ? OR s.delivery_city LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $like = "%$search%"; $params = array_merge($params, [$like,$like,$like,$like,$like]);
}

$shipments = [];
$counts    = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name AS customer_name, u.email AS customer_email
        FROM shipments s JOIN users u ON u.id = s.customer_id
        $where ORDER BY s.created_at DESC LIMIT 200");
    $stmt->execute($params);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countRows = $pdo->query("SELECT status, COUNT(*) as cnt FROM shipments GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($countRows as $r) $counts[$r['status']] = $r['cnt'];
} catch (Exception $e) { /* table may not exist yet */ }

$statusList = ['booked','picked_up','in_transit','out_for_delivery','delivered','cancelled'];

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Deliveries</h4>
        <p>Track and manage all shipments</p>
    </div>
</div>

<!-- Status filter pills -->
<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="deliveries.php" class="btn <?= !$filter ? 'btn-primary-admin' : 'btn-outline-admin' ?>" style="font-size:12px;padding:6px 14px;">All</a>
    <?php foreach ($statusList as $st): ?>
    <a href="deliveries.php?status=<?= $st ?>" class="btn <?= $filter===$st ? 'btn-primary-admin' : 'btn-outline-admin' ?>" style="font-size:12px;padding:6px 14px;">
        <?= ucwords(str_replace('_',' ',$st)) ?>
        <?php if (!empty($counts[$st])): ?><span class="ms-1 badge bg-secondary" style="font-size:10px;"><?= $counts[$st] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title"><i class="bi bi-truck me-2"></i>Shipment List</h6>
        <div class="filter-bar">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search tracking, city, customer…" data-search-table="shipmentsTable" value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="filter-select" onchange="location.href='deliveries.php?service='+this.value+(<?= json_encode($filter) ?> ? '&status=<?= $filter ?>' : '')">
                <option value="">All Services</option>
                <option value="standard"  <?= $service==='standard'  ? 'selected' : '' ?>>Standard</option>
                <option value="premium"   <?= $service==='premium'   ? 'selected' : '' ?>>Premium</option>
                <option value="air_cargo" <?= $service==='air_cargo' ? 'selected' : '' ?>>Air Cargo</option>
                <option value="surface"   <?= $service==='surface'   ? 'selected' : '' ?>>Surface</option>
            </select>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table" id="shipmentsTable">
            <thead>
                <tr>
                    <th>Tracking No</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Service</th>
                    <th>Weight</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                <tr><td colspan="9"><div class="empty-state"><i class="bi bi-truck"></i><p>No shipments found</p></div></td></tr>
                <?php else: ?>
                <?php
                $serviceLabels = ['standard'=>'Standard','premium'=>'Premium','air_cargo'=>'Air Cargo','surface'=>'Surface'];
                foreach ($shipments as $s):
                ?>
                <tr id="srow_<?= $s['id'] ?>">
                    <td>
                        <div style="font-size:12px;font-weight:700;color:var(--primary);font-family:'Montserrat',sans-serif;"><?= htmlspecialchars($s['tracking_no']) ?></div>
                        <button class="btn-action mt-1" style="width:auto;padding:2px 8px;font-size:11px;" data-copy="<?= htmlspecialchars($s['tracking_no']) ?>" title="Copy"><i class="bi bi-copy"></i></button>
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm" style="width:28px;height:28px;font-size:11px;"><?= strtoupper(substr($s['customer_name'], 0, 1)) ?></div>
                            <div class="user-cell-info">
                                <div class="user-cell-name" style="font-size:12px;"><?= htmlspecialchars($s['customer_name']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px;">
                        <div><?= htmlspecialchars($s['pickup_city']) ?></div>
                        <div style="color:var(--muted)">→ <?= htmlspecialchars($s['delivery_city']) ?></div>
                    </td>
                    <td>
                        <span class="badge-status badge-<?= $s['service_type'] === 'air_cargo' ? 'in_transit' : 'booked' ?>" style="font-size:10px;">
                            <?= $serviceLabels[$s['service_type']] ?? $s['service_type'] ?>
                        </span>
                    </td>
                    <td style="font-size:12px;"><?= number_format((float)$s['weight'], 3) ?> kg</td>
                    <td style="font-size:13px;font-weight:600;">₹<?= number_format($s['final_price'], 0) ?></td>
                    <td>
                        <select class="filter-select" style="min-width:130px;font-size:11px;padding:4px 8px;border-radius:8px;" onchange="updateShipmentStatus(<?= $s['id'] ?>, this.value, this)">
                            <?php foreach ($statusList as $st): ?>
                            <option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="font-size:11px;color:var(--muted);"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                    <td>
                        <button class="btn-action" onclick="viewShipment(<?= $s['id'] ?>)" title="View Details"><i class="bi bi-eye"></i></button>
                        <button class="btn-action" onclick="trackingModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['tracking_no']) ?>')" title="Tracking"><i class="bi bi-geo-alt"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Shipment Detail Modal -->
<div class="modal fade admin-modal" id="shipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-truck me-2"></i>Shipment Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="shipmentModalBody">
                <div class="text-center py-4"><span class="spinner-border text-primary"></span></div>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Management Modal -->
<div class="modal fade admin-modal" id="trackingManagementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-geo-alt me-2"></i>Tracking Management</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="trackingModalBody">
                <div class="text-center py-4"><span class="spinner-border text-primary"></span></div>
            </div>
        </div>
    </div>
</div>

<script>
function updateShipmentStatus(id, status, selectEl) {
    fetch('<?= SITE_URL ?>/api/admin/shipments.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id, status}),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) showToast('Status updated', 'success');
        else { showToast(data.message || 'Failed', 'error'); }
    })
    .catch(() => showToast('Network error', 'error'));
}

function viewShipment(id) {
    const modal = new bootstrap.Modal(document.getElementById('shipmentModal'));
    document.getElementById('shipmentModalBody').innerHTML = '<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>';
    modal.show();
    fetch(`<?= SITE_URL ?>/api/admin/shipments.php?id=${id}`, {credentials:'same-origin'})
    .then(r => r.json())
    .then(data => {
        if (!data.success) { document.getElementById('shipmentModalBody').innerHTML = '<p class="text-danger p-3">Failed to load</p>'; return; }
        const s = data.shipment;
        const svcMap = {standard:'Standard Express',premium:'Premium Express',air_cargo:'Air Cargo',surface:'Surface Cargo'};
        document.getElementById('shipmentModalBody').innerHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <p class="mb-1" style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--muted);">Pickup Address</p>
                <div style="font-size:13px;"><strong>${escH(s.pickup_name)}</strong> · ${escH(s.pickup_phone)}<br>${escH(s.pickup_address)}<br>${escH(s.pickup_city)}, ${escH(s.pickup_state)} - ${escH(s.pickup_pincode)}</div>
            </div>
            <div class="col-md-6">
                <p class="mb-1" style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--muted);">Delivery Address</p>
                <div style="font-size:13px;"><strong>${escH(s.delivery_name)}</strong> · ${escH(s.delivery_phone)}<br>${escH(s.delivery_address)}<br>${escH(s.delivery_city)}, ${escH(s.delivery_state)} - ${escH(s.delivery_pincode)}</div>
            </div>
            <div class="col-12"><hr class="my-1"></div>
            <div class="col-md-6">
                <div class="detail-row"><span class="detail-label">Tracking No</span><span class="detail-value" style="font-weight:700;color:var(--primary)">${escH(s.tracking_no)}</span></div>
                <div class="mb-2"><a href="../api/download_receipt.php?tracking_no=${encodeURIComponent(s.tracking_no)}" target="_blank" class="btn-outline-admin" style="font-size:11px;padding:4px 8px;text-decoration:none;display:inline-block;"><i class="bi bi-file-earmark-pdf me-1"></i> Download Receipt</a></div>
                <div class="detail-row"><span class="detail-label">Service</span><span class="detail-value">${escH(svcMap[s.service_type]||s.service_type)}</span></div>
                <div class="detail-row"><span class="detail-label">Weight</span><span class="detail-value">${parseFloat(s.weight).toFixed(3)} kg</span></div>
                <div class="detail-row"><span class="detail-label">Pieces</span><span class="detail-value">${s.pieces}</span></div>
                <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${escH(s.description||'—')}</span></div>
            </div>
            <div class="col-md-6">
                <div class="detail-row"><span class="detail-label">Declared Value</span><span class="detail-value">₹${parseFloat(s.declared_value||0).toLocaleString('en-IN')}</span></div>
                <div class="detail-row"><span class="detail-label">Base Price</span><span class="detail-value">₹${parseFloat(s.base_price).toLocaleString('en-IN')}</span></div>
                <div class="detail-row"><span class="detail-label">Discount</span><span class="detail-value">${s.discount_pct}% (₹${parseFloat(s.discount_amount).toLocaleString('en-IN')})</span></div>
                <div class="detail-row"><span class="detail-label">Final Price</span><span class="detail-value" style="font-weight:700;">₹${parseFloat(s.final_price).toLocaleString('en-IN')}</span></div>
                <div class="detail-row"><span class="detail-label">Payment</span><span class="detail-value">${escH(s.payment_method)}</span></div>
                <div class="detail-row"><span class="detail-label">E-Waybill</span><span class="detail-value">${escH(s.ewaybill_no||'—')}</span></div>
            </div>
        </div>`;
    });
}
function escH(s){ return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : '—'; }

function trackingModal(id, trackingNo) {
    const modal = new bootstrap.Modal(document.getElementById('trackingManagementModal'));
    document.getElementById('trackingModalBody').innerHTML = '<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>';
    modal.show();

    fetch(`<?= SITE_URL ?>/api/admin/tracking.php?shipment_id=${id}`, {credentials:'same-origin'})
    .then(r => r.json())
    .then(data => {
        if (!data.success) { document.getElementById('trackingModalBody').innerHTML = '<p class="text-danger">Failed to load tracking data</p>'; return; }

        const ship = data.shipment;
        const events = data.events || [];

        let html = `
        <div class="mb-3">
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px;">DTDC AWB Number</label>
            <div style="display:flex;gap:8px;">
                <input type="text" id="dtdcAwbInput" value="${escH(ship.dtdc_awb||'')}" placeholder="e.g., ABC123456" style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;">
                <button class="btn-primary-admin" style="padding:8px 16px;font-size:12px;" onclick="saveDtdcAwb(${id})"><i class="bi bi-check-lg me-1"></i> Save</button>
            </div>
        </div>

        <hr style="margin:16px 0;">

        <div class="mb-3">
            <h6 style="font-size:12px;font-weight:700;margin-bottom:12px;">Tracking Events</h6>
            <div id="trackingEvents" style="max-height:300px;overflow-y:auto;">`;

        if (events.length === 0) {
            html += '<p style="font-size:12px;color:var(--muted);text-align:center;padding:20px;">No tracking events yet</p>';
        } else {
            for (let e of events) {
                const isMgmt = e.source === 'manual';
                html += `
                <div style="padding:10px;background:#f9fafb;border-radius:6px;margin-bottom:8px;border-left:3px solid ${isMgmt ? '#3B5BDB' : '#f59e0b'};">
                    <div style="display:flex;justify-content:space-between;align-items:start;">
                        <div style="flex:1;font-size:11px;">
                            <strong>${escH(e.status)}</strong> <span style="color:var(--muted);">${e.event_time}</span>
                            ${e.source === 'dtdc' ? '<span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:3px;font-size:9px;margin-left:6px;font-weight:600;">DTDC</span>' : ''}
                            <div style="color:var(--muted);margin-top:2px;">${escH(e.location||'')}</div>
                            <div style="color:var(--muted);margin-top:2px;font-size:10px;">${escH(e.description||'')}</div>
                        </div>
                        ${isMgmt ? `<button class="btn-action danger" style="margin-left:8px;" onclick="deleteEvent(${e.id}, ${id})"><i class="bi bi-trash"></i></button>` : ''}
                    </div>
                </div>`;
            }
        }

        html += `</div>
        </div>

        <hr style="margin:16px 0;">

        <div>
            <h6 style="font-size:12px;font-weight:700;margin-bottom:12px;">Add Manual Update</h6>
            <div style="display:grid;gap:10px;">
                <input type="datetime-local" id="eventTime" placeholder="Date &amp; Time" style="padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;">
                <input type="text" id="eventLocation" placeholder="Location" style="padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;">
                <select id="eventStatus" style="padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;">
                    <option value="">— Select Status —</option>
                    <option value="Booked">Booked</option>
                    <option value="Picked Up">Picked Up</option>
                    <option value="In Transit">In Transit</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                </select>
                <textarea id="eventDesc" placeholder="Description (optional)" style="padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;min-height:60px;"></textarea>
                <button class="btn-primary-admin" onclick="addTrackingEvent(${id})"><i class="bi bi-plus-lg me-1"></i> Add Event</button>
            </div>
        </div>`;

        document.getElementById('trackingModalBody').innerHTML = html;
    })
    .catch(() => document.getElementById('trackingModalBody').innerHTML = '<p class="text-danger">Network error</p>');
}

function saveDtdcAwb(id) {
    const awb = document.getElementById('dtdcAwbInput').value.trim();
    fetch('<?= SITE_URL ?>/api/admin/tracking.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'save_awb', id, dtdc_awb: awb}),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message || (data.success ? 'Saved' : 'Error'), data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => trackingModal(id, ''), 800);
    })
    .catch(() => showToast('Network error', 'error'));
}

function addTrackingEvent(id) {
    const eventTime = document.getElementById('eventTime').value;
    const location = document.getElementById('eventLocation').value.trim();
    const status = document.getElementById('eventStatus').value;
    const desc = document.getElementById('eventDesc').value.trim();

    if (!eventTime || !status) { showToast('Date/Time and Status are required', 'warning'); return; }

    fetch('<?= SITE_URL ?>/api/admin/tracking.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'add_event', shipment_id: id, event_time: eventTime.replace('T',' '), location, status, description: desc}),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.success ? 'Event added' : 'Error', data.success ? 'success' : 'error');
        if (data.success) {
            document.getElementById('eventTime').value = '';
            document.getElementById('eventLocation').value = '';
            document.getElementById('eventStatus').value = '';
            document.getElementById('eventDesc').value = '';
            trackingModal(id, '');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function deleteEvent(eventId, shipmentId) {
    if (!confirm('Delete this tracking event?')) return;
    fetch('<?= SITE_URL ?>/api/admin/tracking.php', {
        method: 'DELETE',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({event_id: eventId}),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.success ? 'Deleted' : 'Error', data.success ? 'success' : 'error');
        if (data.success) trackingModal(shipmentId, '');
    })
    .catch(() => showToast('Network error', 'error'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
