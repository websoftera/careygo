<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$pageTitle = 'Track Shipment - Careygo';
$metaDescription = 'Track your Careygo shipment using your tracking number.';
$metaKeywords = 'Careygo tracking, track shipment, courier tracking, package tracking, delivery status';
$canonicalUrl = SITE_URL . '/public-tracking.php';
$initialTracking = strtoupper(trim($_GET['tracking'] ?? ''));
$metaRobots = $initialTracking !== '' ? 'noindex, follow' : 'index, follow';

require_once __DIR__ . '/includes/header.php';
?>

<main class="public-tool-page">
    <section class="public-tool-band">
        <div class="container">
            <div class="public-tool-shell">
                <div class="public-tool-heading">
                    <span class="public-tool-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <div>
                        <h1>Track Shipment</h1>
                        <p>Enter your Careygo tracking number to view the latest shipment status.</p>
                    </div>
                </div>

                <form class="tracking-search-card" id="trackingSearchForm" autocomplete="off">
                    <label for="trackingInput">Tracking Number</label>
                    <div class="tracking-search-row">
                        <input type="text" id="trackingInput" name="tracking" value="<?= htmlspecialchars($initialTracking) ?>" placeholder="Enter tracking number">
                        <button type="submit" class="btn-new-delivery justify-content-center">
                            <i class="bi bi-search"></i> Track
                        </button>
                    </div>
                    <div id="trackingSearchError" class="tracking-search-error" role="status"></div>
                </form>

                <div class="tracking-card" id="trackingCard" style="display:none;">
                    <div class="tracking-header">
                        <div class="track-info">
                            <h5 id="trackingNumberLabel"></h5>
                            <p><i class="bi bi-geo-alt me-1"></i> Tracking your shipment</p>
                        </div>
                        <div class="track-actions">
                            <button type="button" class="btn-share" onclick="copyTracking()"><i class="bi bi-copy me-1"></i> Copy</button>
                            <button type="button" class="btn-share" onclick="shareTracking()"><i class="bi bi-share me-1"></i> Share</button>
                        </div>
                    </div>

                    <div id="statusBadge" style="margin-bottom:16px;"></div>
                    <div id="shipmentInfo" class="shipment-info"></div>
                    <div id="trackingContent">
                        <div class="loading"><span class="spinner-border spinner-border-sm text-primary"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.public-tool-page {
    background: #f0f2f9;
    font-family: 'Poppins', sans-serif;
}
.public-tool-band {
    padding: 48px 0 64px;
}
.public-tool-shell {
    max-width: 760px;
    margin: 0 auto;
}
.public-tool-heading {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
}
.public-tool-heading h1 {
    color: #1a1a2e;
    font-family: 'Montserrat', sans-serif;
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 4px;
}
.public-tool-heading p {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}
.public-tool-icon {
    align-items: center;
    background: #001A93;
    border-radius: 14px;
    color: #fff;
    display: inline-flex;
    flex: 0 0 52px;
    font-size: 22px;
    height: 52px;
    justify-content: center;
    width: 52px;
}
.tracking-search-card,
.tracking-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,26,147,0.08);
    margin-bottom: 24px;
    padding: 28px;
}
.tracking-search-card label {
    color: #1a1a2e;
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
}
.tracking-search-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
}
.tracking-search-row input {
    border: 1.5px solid #e4e7f0;
    border-radius: 10px;
    color: #1a1a2e;
    font: inherit;
    min-width: 0;
    outline: none;
    padding: 11px 14px;
    text-transform: uppercase;
}
.tracking-search-row input:focus {
    border-color: #001A93;
    box-shadow: 0 0 0 3px rgba(0,26,147,0.08);
}
.tracking-search-error {
    color: #b91c1c;
    font-size: 13px;
    margin-top: 10px;
    min-height: 18px;
}
.tracking-header {
    align-items: start;
    border-bottom: 2px solid #e4e7f0;
    display: flex;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 20px;
    gap: 16px;
}
.track-info h5 {
    color: #1a1a2e;
    font-family: 'Montserrat', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}
.track-info p {
    color: #6b7280;
    font-size: 13px;
    margin: 2px 0;
}
.track-actions {
    display: flex;
    gap: 8px;
}
.btn-share {
    background: #f0f2f9;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    padding: 8px 16px;
    transition: all .2s;
}
.btn-share:hover {
    background: #001A93;
    color: #fff;
}
.status-badge {
    color: #fff;
    display: inline-block;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 14px;
}
.shipment-info {
    background: #f0f2f9;
    border-radius: 10px;
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-bottom: 20px;
    padding: 16px;
}
.info-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 13px;
}
.info-label {
    color: #6b7280;
    font-weight: 600;
}
.info-value {
    color: #1a1a2e;
    font-weight: 600;
}
.timeline {
    position: relative;
    padding: 20px 0;
}
.timeline-item {
    margin-bottom: 24px;
    padding-left: 40px;
    position: relative;
}
.timeline-dot {
    background: #001A93;
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 0 0 2px #001A93;
    height: 18px;
    left: 0;
    position: absolute;
    top: 2px;
    width: 18px;
}
.timeline-item:first-child .timeline-dot {
    box-shadow: 0 0 0 4px #001A93;
    height: 22px;
    left: -2px;
    top: 0;
    width: 22px;
}
.timeline-item.completed .timeline-dot {
    background: #22c55e;
    box-shadow: 0 0 0 2px #22c55e;
}
.timeline-line {
    background: #e4e7f0;
    height: calc(100% + 20px);
    left: 8px;
    position: absolute;
    top: 28px;
    width: 2px;
}
.timeline-item:last-child .timeline-line {
    display: none;
}
.timeline-time {
    color: #001A93;
    font-family: 'Montserrat', sans-serif;
    font-size: 12px;
    font-weight: 600;
}
.timeline-status {
    color: #1a1a2e;
    font-size: 14px;
    font-weight: 600;
    margin-top: 2px;
}
.timeline-location,
.timeline-desc {
    color: #6b7280;
    font-size: 12px;
    margin-top: 2px;
}
.timeline-desc {
    line-height: 1.5;
    margin-top: 4px;
}
.dtdc-badge {
    background: rgba(0,26,147,0.1);
    border-radius: 4px;
    color: #001A93;
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    margin-left: 8px;
    padding: 3px 8px;
}
.no-events,
.loading {
    color: #6b7280;
    padding: 40px 20px;
    text-align: center;
}
.no-events i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}
.btn-new-delivery {
    align-items: center;
    background: #001A93;
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    font-size: 13px;
    font-weight: 600;
    gap: 6px;
    padding: 10px 18px;
    text-decoration: none;
}
.btn-new-delivery:hover {
    background: #001270;
    color: #fff;
}
@media (max-width: 575.98px) {
    .public-tool-band {
        padding: 28px 0 44px;
    }
    .public-tool-heading {
        align-items: flex-start;
    }
    .public-tool-heading h1 {
        font-size: 25px;
    }
    .tracking-search-card,
    .tracking-card {
        padding: 20px;
    }
    .tracking-search-row {
        grid-template-columns: 1fr;
    }
    .tracking-header {
        flex-direction: column;
    }
    .track-actions {
        width: 100%;
    }
    .btn-share {
        flex: 1;
    }
    .shipment-info {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const SITE_URL = '<?= rtrim(SITE_URL, '/') ?>';
let currentTrackingNo = '';

const trackingForm = document.getElementById('trackingSearchForm');
const trackingInput = document.getElementById('trackingInput');
const trackingError = document.getElementById('trackingSearchError');
const trackingCard = document.getElementById('trackingCard');
const trackingContent = document.getElementById('trackingContent');

trackingForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const trackingNo = trackingInput.value.trim().toUpperCase();
    if (!trackingNo) {
        trackingError.textContent = 'Please enter your tracking number.';
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set('tracking', trackingNo);
    window.history.replaceState({}, '', url.toString());
    loadTracking(trackingNo);
});

trackingInput.addEventListener('input', function () {
    trackingInput.value = trackingInput.value.toUpperCase();
    trackingError.textContent = '';
});

function copyTracking() {
    if (!currentTrackingNo) return;
    navigator.clipboard.writeText(currentTrackingNo);
    alert('Tracking number copied!');
}

function shareTracking() {
    if (!currentTrackingNo) return;
    const url = new URL(window.location.href);
    url.searchParams.set('tracking', currentTrackingNo);
    if (navigator.share) {
        navigator.share({ title: 'Track Shipment', text: 'Tracking: ' + currentTrackingNo, url: url.toString() });
    } else {
        alert('Share this link: ' + url.toString());
    }
}

function loadTracking(trackingNo) {
    currentTrackingNo = trackingNo;
    trackingError.textContent = '';
    trackingCard.style.display = 'block';
    document.getElementById('trackingNumberLabel').textContent = trackingNo;
    document.getElementById('statusBadge').innerHTML = '';
    document.getElementById('shipmentInfo').innerHTML = '';
    trackingContent.innerHTML = '<div class="loading"><span class="spinner-border spinner-border-sm text-primary"></span> Loading tracking details...</div>';

    fetch(`${SITE_URL}/api/tracking.php?tracking=${encodeURIComponent(trackingNo)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderTracking(data);
            } else {
                renderTrackingError(data.message || 'Tracking number not found.');
            }
        })
        .catch(() => renderTrackingError('Network error. Please try again.'));
}

function renderTrackingError(message) {
    document.getElementById('statusBadge').innerHTML = '';
    document.getElementById('shipmentInfo').innerHTML = '';
    trackingContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i> ${esc(message)}</div>`;
}

function renderTracking(data) {
    const s = data.shipment;
    const events = data.events || [];
    currentTrackingNo = s.tracking_no || currentTrackingNo;
    document.getElementById('trackingNumberLabel').textContent = currentTrackingNo;

    const statusMap = { booked: 'Booked', picked_up: 'Picked Up', in_transit: 'In Transit', out_for_delivery: 'Out for Delivery', delivered: 'Delivered', cancelled: 'Cancelled' };
    const statusColors = { booked: '#3B5BDB', picked_up: '#f59e0b', in_transit: '#6366f1', out_for_delivery: '#f59e0b', delivered: '#22c55e', cancelled: '#ef4444' };
    const color = statusColors[s.status] || '#001A93';
    document.getElementById('statusBadge').innerHTML = `<span class="status-badge" style="background:${color}"><i class="bi bi-circle-fill me-1" style="font-size:8px"></i> ${statusMap[s.status] || esc(s.status)}</span>`;

    document.getElementById('shipmentInfo').innerHTML = `
        <div class="info-row"><span class="info-label">From</span><span class="info-value">${esc(s.pickup_city || '')}</span></div>
        <div class="info-row"><span class="info-label">To</span><span class="info-value">${esc(s.delivery_city || '')}</span></div>
        <div class="info-row"><span class="info-label">Service</span><span class="info-value">${esc(s.service_label || s.service_type || '')}</span></div>
        <div class="info-row"><span class="info-label">Weight</span><span class="info-value">${formatWeight(s.weight)}</span></div>
        <div class="info-row"><span class="info-label">Booked On</span><span class="info-value">${formatDate(s.created_at)}</span></div>
        <div class="info-row"><span class="info-label">Expected Delivery</span><span class="info-value">${formatDate(s.estimated_delivery)}</span></div>
    `;

    let html = '<div class="timeline">';
    if (events.length === 0) {
        html = '<div class="no-events"><i class="bi bi-inbox"></i><p>No tracking updates yet</p></div>';
    } else {
        for (let i = 0; i < events.length; i++) {
            const e = events[i];
            const isCompleted = i > 0;
            const dtdcLabel = e.source === 'dtdc' ? '<span class="dtdc-badge">DTDC</span>' : '';
            html += `
            <div class="timeline-item ${isCompleted ? 'completed' : ''}">
                <div class="timeline-line"></div>
                <div class="timeline-dot"></div>
                <div class="timeline-time">${formatTime(e.event_time)}</div>
                <div class="timeline-status">${esc(e.status)} ${dtdcLabel}</div>
                ${e.location ? `<div class="timeline-location"><i class="bi bi-geo-alt me-1"></i>${esc(e.location)}</div>` : ''}
                ${e.description ? `<div class="timeline-desc">${esc(e.description)}</div>` : ''}
            </div>`;
        }
    }
    html += '</div>';

    if (data.dtdc_live) {
        html += '<div class="alert alert-info mt-3"><i class="bi bi-info-circle me-2"></i> <strong>Live tracking</strong> powered by DTDC</div>';
    }
    if (data.dtdc_error) {
        html += `<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle me-2"></i> ${esc(data.dtdc_error)}</div>`;
    }

    trackingContent.innerHTML = html;
}

function formatTime(dt) {
    const d = new Date(dt);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-IN') + ' ' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dt) {
    const d = new Date(dt);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatWeight(kg) {
    const value = parseFloat(kg) || 0;
    return value.toFixed(3).replace(/\.?0+$/, '') + ' kg';
}

function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

if (trackingInput.value.trim()) {
    loadTracking(trackingInput.value.trim().toUpperCase());
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
