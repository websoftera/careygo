<?php
require_once __DIR__ . '/includes/middleware.php';
$pageTitle = 'International Tracking';
$activePage = 'international-tracking';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div class="page-header-left"><h4>International Tracking</h4><p>Manage international shipments separately from domestic deliveries.</p></div>
    <a class="btn-outline-admin" href="<?= htmlspecialchars(rtrim(SITE_URL, '/')) ?>/public-tracking.php" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Customer tracking page</a>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-header"><h6 class="admin-card-title"><i class="bi bi-globe2 me-2"></i>Create International Tracking</h6></div>
    <form id="createInternationalForm" class="p-3">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Customer name *</label><input class="form-control" name="customer_name" required maxlength="160"></div>
            <div class="col-md-6"><label class="form-label">Customer mobile</label><input class="form-control" name="customer_phone" maxlength="30"></div>
            <div class="col-12"><strong>Origin</strong></div>
            <div class="col-md-3"><label class="form-label">City *</label><input class="form-control" name="origin_city" required></div>
            <div class="col-md-3"><label class="form-label">State / Region</label><input class="form-control" name="origin_region"></div>
            <div class="col-md-2"><label class="form-label">Postal code</label><input class="form-control" name="origin_postal_code"></div>
            <div class="col-md-4"><label class="form-label">Country *</label><input class="form-control" name="origin_country" value="India" required></div>
            <div class="col-12"><label class="form-label">Origin address</label><textarea class="form-control" name="origin_address" rows="2"></textarea></div>
            <div class="col-12"><strong>Destination</strong></div>
            <div class="col-md-3"><label class="form-label">City *</label><input class="form-control" name="destination_city" required></div>
            <div class="col-md-3"><label class="form-label">State / Region</label><input class="form-control" name="destination_region"></div>
            <div class="col-md-2"><label class="form-label">Postal code</label><input class="form-control" name="destination_postal_code"></div>
            <div class="col-md-4"><label class="form-label">Country *</label><input class="form-control" name="destination_country" required></div>
            <div class="col-12"><label class="form-label">Destination address</label><textarea class="form-control" name="destination_address" rows="2"></textarea></div>
            <div class="col-12"><button class="btn-primary-admin" type="submit"><i class="bi bi-plus-lg me-1"></i>Create INT tracking</button></div>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header"><h6 class="admin-card-title"><i class="bi bi-list-ul me-2"></i>International Shipments</h6><button class="btn-action" type="button" onclick="loadInternationalShipments()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tracking No</th><th>Customer</th><th>Route</th><th>Status</th><th>Customer Link</th><th>Actions</th></tr></thead><tbody id="internationalRows"><tr><td colspan="6" class="text-center">Loading...</td></tr></tbody></table></div>
</div>

<div class="modal fade admin-modal" id="internationalManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h6 class="modal-title">Manage International Tracking</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="internationalManageBody"></div>
    </div></div>
</div>

<script>
const intApi = '<?= htmlspecialchars(rtrim(SITE_URL, '/')) ?>/api/admin/international-tracking.php';
const publicTrackingUrl = '<?= htmlspecialchars(rtrim(SITE_URL, '/')) ?>/public-tracking.php?tracking=';
let activeInternationalId = 0;
let editingInternationalEventId = 0;
const intEsc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));

async function intRequest(url, options = {}) {
    const response = await fetch(url, {credentials:'same-origin', ...options});
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Request failed.');
    return data;
}

async function loadInternationalShipments() {
    const tbody = document.getElementById('internationalRows');
    try {
        const data = await intRequest(intApi);
        if (!data.shipments.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No international shipments yet.</td></tr>'; return; }
        tbody.innerHTML = data.shipments.map(s => {
            const url = publicTrackingUrl + encodeURIComponent(s.tracking_no);
            return `<tr>
                <td><strong>${intEsc(s.tracking_no)}</strong></td><td>${intEsc(s.customer_name)}<div class="text-muted small">${intEsc(s.customer_phone || '')}</div></td>
                <td>${intEsc(s.origin_city)}, ${intEsc(s.origin_country)} <span class="text-muted">to</span> ${intEsc(s.destination_city)}, ${intEsc(s.destination_country)}</td>
                <td>${intEsc(s.status)}</td><td><a href="${url}" target="_blank" rel="noopener">Open</a> <button class="btn-action" type="button" title="Copy customer link" onclick="copyInternationalLink('${Number(s.id)}')"><i class="bi bi-copy"></i></button></td>
                <td><button class="btn-action" type="button" title="Manage shipment" onclick="manageInternational(${Number(s.id)})"><i class="bi bi-pencil-square"></i></button>
                <button class="btn-action danger" type="button" title="Delete shipment" onclick="deleteInternational(${Number(s.id)}, '${intEsc(s.tracking_no)}')"><i class="bi bi-trash"></i></button></td>
            </tr>`;
        }).join('');
    } catch (error) { tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${intEsc(error.message)}</td></tr>`; }
}

document.getElementById('createInternationalForm').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const body = Object.fromEntries(new FormData(form).entries());
    body.action = 'create';
    try {
        const result = await intRequest(intApi, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
        form.reset();
        form.elements.origin_country.value = 'India';
        alert(`Created ${result.tracking_no}. Customer link: ${publicTrackingUrl}${encodeURIComponent(result.tracking_no)}`);
        loadInternationalShipments();
    } catch (error) { alert(error.message); }
});

async function manageInternational(id) {
    activeInternationalId = id;
    editingInternationalEventId = 0;
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('internationalManageModal'));
    document.getElementById('internationalManageBody').innerHTML = '<div class="text-center p-4"><span class="spinner-border"></span></div>';
    modal.show();
    try {
        const data = await intRequest(`${intApi}?id=${id}`);
        const s = data.shipment;
        const fields = [
            ['origin_city','Origin city'],['origin_region','Origin state / region'],['origin_postal_code','Origin postal code'],['origin_country','Origin country'],['origin_address','Origin address'],
            ['destination_city','Destination city'],['destination_region','Destination state / region'],['destination_postal_code','Destination postal code'],['destination_country','Destination country'],['destination_address','Destination address']
        ];
        const input = (key, label) => `<div class="col-md-${key.endsWith('address') ? '12' : '6'}"><label class="form-label">${label}</label>${key.endsWith('address') ? `<textarea class="form-control" name="${key}" rows="2">${intEsc(s[key] || '')}</textarea>` : `<input class="form-control" name="${key}" value="${intEsc(s[key] || '')}" ${['origin_city','origin_country','destination_city','destination_country'].includes(key) ? 'required' : ''}>`}</div>`;
        document.getElementById('internationalManageBody').innerHTML = `
            <div class="mb-3"><strong>${intEsc(s.tracking_no)}</strong> · ${intEsc(s.customer_name)}<div><a href="${publicTrackingUrl}${encodeURIComponent(s.tracking_no)}" target="_blank" rel="noopener">Open customer tracking link</a></div></div>
            <form id="internationalRouteForm" class="row g-3"><input type="hidden" name="action" value="update_route"><input type="hidden" name="shipment_id" value="${id}"><h6 class="col-12 mb-0">Temporary route editor</h6>${fields.map(([key,label])=>input(key,label)).join('')}<div class="col-12"><button class="btn-primary-admin" type="submit"><i class="bi bi-check-lg me-1"></i>Save Origin and Destination</button></div></form>
            <hr><h6>Tracking events</h6><div id="internationalEvents" class="mb-3">${renderInternationalEvents(data.events)}</div>
            <form id="internationalEventForm" class="row g-2"><input type="hidden" name="event_id" value=""><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status" required><option value="">Select status</option>${['Pickup Requested','Booked','In Transit','Out for Delivery','Delivered','On Hold','Return to Origin','Damage','Exception'].map(st=>`<option>${st}</option>`).join('')}</select></div><div class="col-md-4"><label class="form-label">Date and time</label><input class="form-control" type="datetime-local" name="event_time" required></div><div class="col-md-5"><label class="form-label">Location</label><input class="form-control" name="location" placeholder="City, airport, branch or facility"></div><div class="col-12"><label class="form-label">Update details for customer</label><textarea class="form-control" name="description" rows="2" placeholder="Brief shipment update"></textarea></div><div class="col-12 d-flex gap-2"><button class="btn-primary-admin" type="submit" id="internationalEventSubmit">Add Event</button><button class="btn-outline-admin" type="button" id="internationalEventCancel" style="display:none" onclick="resetInternationalEventForm()">Cancel edit</button></div></form>`;
        document.getElementById('internationalRouteForm').addEventListener('submit', saveInternationalRoute);
        document.getElementById('internationalEventForm').addEventListener('submit', saveInternationalEvent);
    } catch (error) { document.getElementById('internationalManageBody').innerHTML = `<p class="text-danger">${intEsc(error.message)}</p>`; }
}

function renderInternationalEvents(events) {
    if (!events.length) return '<p class="text-muted">No tracking events yet.</p>';
    return events.map(e => `<div class="d-flex justify-content-between gap-3 border rounded p-2 mb-2"><div><strong>${intEsc(e.status)}</strong> <span class="text-muted">${intEsc(e.event_time)}</span><div>${intEsc(e.location || '')}</div><small>${intEsc(e.description || '')}</small></div><div class="d-flex gap-1"><button class="btn-action" type="button" title="Edit event" data-event="${encodeURIComponent(JSON.stringify(e))}" onclick="editInternationalEventFromButton(this)"><i class="bi bi-pencil-square"></i></button><button class="btn-action danger" type="button" title="Delete event" onclick="deleteInternationalEvent(${Number(e.id)})"><i class="bi bi-trash"></i></button></div></div>`).join('');
}

async function reloadInternationalEvents() {
    const data = await intRequest(`${intApi}?id=${activeInternationalId}`);
    document.getElementById('internationalEvents').innerHTML = renderInternationalEvents(data.events);
}
async function saveInternationalRoute(event) {
    event.preventDefault();
    const body = Object.fromEntries(new FormData(event.currentTarget).entries());
    try { await intRequest(intApi, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}); alert('International route saved.'); loadInternationalShipments(); }
    catch (error) { alert(error.message); }
}
async function saveInternationalEvent(event) {
    event.preventDefault();
    const body = Object.fromEntries(new FormData(event.currentTarget).entries());
    body.action = body.event_id ? 'edit_event' : 'add_event';
    body.shipment_id = activeInternationalId;
    try { await intRequest(intApi, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}); resetInternationalEventForm(); await reloadInternationalEvents(); loadInternationalShipments(); }
    catch (error) { alert(error.message); }
}
function editInternationalEventFromButton(button) {
    const event = JSON.parse(decodeURIComponent(button.dataset.event));
    const form = document.getElementById('internationalEventForm');
    form.elements.event_id.value = event.id;
    form.elements.event_time.value = event.event_time.replace(' ', 'T').slice(0,16);
    form.elements.status.value = event.status;
    form.elements.location.value = event.location || '';
    form.elements.description.value = event.description || '';
    document.getElementById('internationalEventSubmit').textContent = 'Update Event';
    document.getElementById('internationalEventCancel').style.display = 'inline-flex';
}
function resetInternationalEventForm() {
    const form = document.getElementById('internationalEventForm');
    if (!form) return;
    form.reset(); form.elements.event_id.value = '';
    document.getElementById('internationalEventSubmit').textContent = 'Add Event';
    document.getElementById('internationalEventCancel').style.display = 'none';
}
async function deleteInternationalEvent(eventId) {
    if (!confirm('Delete this international tracking event?')) return;
    try { await intRequest(intApi, {method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({event_id:eventId,shipment_id:activeInternationalId})}); await reloadInternationalEvents(); loadInternationalShipments(); }
    catch (error) { alert(error.message); }
}
async function deleteInternational(id, trackingNo) {
    if (!confirm(`Delete international shipment ${trackingNo} and its events?`)) return;
    try { await intRequest(intApi, {method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({shipment_id:id})}); loadInternationalShipments(); }
    catch (error) { alert(error.message); }
}
async function copyInternationalLink(id) {
    try {
        const data = await intRequest(`${intApi}?id=${id}`);
        const link = publicTrackingUrl + encodeURIComponent(data.shipment.tracking_no);
        await navigator.clipboard.writeText(link);
        alert('Customer tracking link copied.');
    } catch (error) { alert(error.message || 'Could not copy the link.'); }
}
loadInternationalShipments();
</script>
<?php require_once 'includes/footer.php'; ?>
