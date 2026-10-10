<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit; }

$pageTitle  = 'Track Shipment';
$activePage = 'dashboard';
$topbarBtn  = '<a href="dashboard.php" class="btn-outline-admin" style="font-size:12px;padding:7px 14px;text-decoration:none;"><i class="bi bi-arrow-left me-1"></i> Back</a>';

require_once 'includes/header.php';

// Verify ownership (after header sets $user)
try {
    $stmt = $pdo->prepare("SELECT id, tracking_no, status FROM shipments WHERE id = ? AND customer_id = ?");
    $stmt->execute([$id, $user['id']]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $shipment = null; }

if (!$shipment) { header('Location: dashboard.php'); exit; }
?>
<style>
    .tracking-card { background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 4px 20px rgba(0,26,147,0.08); margin-bottom: 24px; }
    .tracking-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid var(--border); }
    .track-info h5 { font-family: 'Montserrat', sans-serif; font-weight: 700; color: var(--text); margin-bottom: 4px; font-size: 18px; }
    .track-info p { font-size: 13px; color: var(--muted); margin: 2px 0; }
    .track-actions { display: flex; gap: 8px; }
    .btn-share { background: #f0f2f9; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; cursor: pointer; transition: all .2s; }
    .btn-share:hover { background: var(--primary); color: #fff; }
    .status-badge { display: inline-block; background: var(--primary); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .timeline { position: relative; padding: 20px 0; }
    .timeline-item { position: relative; padding-left: 40px; margin-bottom: 24px; }
    .timeline-dot { position: absolute; left: 0; top: 2px; width: 18px; height: 18px; background: var(--primary); border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 0 2px var(--primary); }
    .timeline-item:first-child .timeline-dot { width: 22px; height: 22px; top: 0; left: -2px; box-shadow: 0 0 0 4px var(--primary); }
    .timeline-item.completed .timeline-dot { background: #22c55e; box-shadow: 0 0 0 2px #22c55e; }
    .timeline-line { position: absolute; left: 8px; top: 28px; width: 2px; height: calc(100% + 20px); background: var(--border); }
    .timeline-item:last-child .timeline-line { display: none; }
    .timeline-time { font-size: 12px; font-weight: 600; color: var(--primary); font-family: 'Montserrat', sans-serif; }
    .timeline-heading { align-items: baseline; display: flex; flex-wrap: wrap; gap: 4px 14px; }
    .timeline-status { font-size: 14px; font-weight: 600; color: var(--text); }
    .timeline-status i { color: var(--muted); margin-right: 4px; }
    .timeline-location { font-size: 12px; color: var(--muted); margin-top: 2px; }
    .timeline-next { font-size: 12px; color: var(--text); font-weight: 600; margin-top: 2px; }
    .timeline-desc { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.5; }
    .tracking-progress { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); margin: 28px 4px 0; }
    .tracking-stage { border-top: 3px solid #9ca3af; color: #555; font-size: 11px; min-width: 0; padding: 14px 6px 0; position: relative; text-align: center; }
    .tracking-stage::before { background: #9ca3af; border: 2px solid #fff; border-radius: 50%; content: ''; height: 15px; left: 0; position: absolute; top: -9px; width: 15px; }
    .tracking-stage.done, .tracking-stage.active { border-color: #16a34a; }
    .tracking-stage.done::before, .tracking-stage.active::before { background: #16a34a; }
    .tracking-stage-label { display: inline-flex; align-items: center; gap: 5px; }
    .tracking-stage-label i { font-size: 14px; }
    .tracking-route { align-items: center; background: var(--bg); border-radius: 28px; color: var(--muted); display: flex; font-size: 12px; font-weight: 600; justify-content: space-between; margin: 20px 0 30px; padding: 13px 18px; gap: 12px; }
    .tracking-section-title { border-bottom: 1px solid var(--border); color: var(--text); font-size: 16px; font-weight: 600; margin: 0; padding-bottom: 10px; }
    .dtdc-badge { display: inline-block; background: rgba(0,26,147,0.1); color: var(--primary); padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-left: 8px; }
    .no-events { text-align: center; padding: 40px 20px; color: var(--muted); }
    .no-events i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
    .loading { text-align: center; padding: 40px; }
    @media (max-width: 575.98px) {
        .tracking-route { align-items: flex-start; border-radius: 14px; flex-direction: column; }
        .tracking-progress { margin-left: 0; margin-right: 0; }
        .tracking-stage { font-size: 9px; padding-left: 2px; padding-right: 2px; }
        .tracking-stage-label { flex-direction: column; gap: 3px; }
        .tracking-stage-label i { font-size: 16px; }
    }
</style>

<div class="tracking-card">
    <div class="tracking-header">
        <div class="track-info">
            <h5><?= htmlspecialchars($shipment['tracking_no']) ?></h5>
            <p><i class="bi bi-geo-alt me-1"></i> Tracking your shipment</p>
        </div>
        <div class="track-actions">
            <button class="btn-share" onclick="copyTracking()"><i class="bi bi-copy me-1"></i> Copy</button>
            <button class="btn-share" onclick="shareTracking()"><i class="bi bi-share me-1"></i> Share</button>
        </div>
    </div>
    <div id="trackingProgress" class="tracking-progress"></div>
    <div id="trackingRoute" class="tracking-route"></div>
    <h6 class="tracking-section-title">Shipment Progress</h6>
    <div id="trackingContent">
        <div class="loading"><span class="spinner-border spinner-border-sm text-primary"></span></div>
    </div>
</div>

<script>
const TRACK_NO = '<?= htmlspecialchars($shipment['tracking_no']) ?>';
const SID = <?= $id ?>;
const SITE_URL = '<?= rtrim(SITE_URL, '/') ?>';

function copyTracking() {
    navigator.clipboard.writeText(TRACK_NO);
    alert('Tracking number copied!');
}

function shareTracking() {
    if (navigator.share) {
        navigator.share({ title: 'Track Shipment', text: 'Tracking: ' + TRACK_NO, url: window.location.href });
    } else {
        alert('Share this link: ' + window.location.href);
    }
}

function loadTracking() {
    fetch(`${SITE_URL}/api/tracking.php?id=${SID}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderTracking(data);
            else document.getElementById('trackingContent').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i> Failed to load tracking data.</div>';
        })
        .catch(() => document.getElementById('trackingContent').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i> Network error.</div>');
}

function renderTracking(data) {
    const s = data.shipment;
    const events = data.events || [];

    document.getElementById('trackingRoute').innerHTML = `
        <span>Origin: ${esc(s.pickup_city || '')}, ${esc(s.pickup_pincode || '')}, India</span>
        <span>Destination: ${esc(s.delivery_city || '')}, ${esc(s.delivery_pincode || '')}, India</span>`;
    renderTrackingProgress(s.status, events);

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
                <div class="timeline-heading">
                    <span class="timeline-time">${formatTime(e.event_time)}</span>
                    <span class="timeline-status"><i class="bi ${trackingStatusIcon(e.status)}"></i>${esc(e.status)} ${dtdcLabel}</span>
                </div>
                ${e.location ? `<div class="timeline-location"><i class="bi bi-geo-alt me-1"></i>${esc(e.location)}</div>` : ''}
                ${e.next_destination ? `<div class="timeline-next"><i class="bi bi-arrow-right-circle me-1"></i>Next destination: ${esc(e.next_destination)}</div>` : ''}
                ${e.description ? `<div class="timeline-desc">${esc(e.description)}</div>` : ''}
                ${e.destination_details ? `<div class="timeline-desc">${esc(e.destination_details)}</div>` : ''}
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

    document.getElementById('trackingContent').innerHTML = html;
}

function renderTrackingProgress(shipmentStatus, events) {
    const stages = [
        ['Pickup Requested', 'bi-inbox'],
        ['Booked', 'bi-box-arrow-in-down'],
        ['In Transit', 'bi-truck'],
        ['Out for Delivery', 'bi-box-seam'],
        ['Delivered', 'bi-box2-heart'],
    ];
    const latestStatus = events.length ? events[0].status : shipmentStatus;
    const normalized = String(latestStatus || shipmentStatus || '').toLowerCase().replace(/[_-]+/g, ' ').trim();
    const stageByStatus = {
        'pickup requested': 0,
        booked: 1,
        'picked up': 2,
        'in transit': 2,
        'on hold': 2,
        damage: 2,
        exception: 2,
        'return to origin': 2,
        returned: 2,
        'out for delivery': 3,
        delivered: 4,
    };
    const stageIndex = stageByStatus[normalized] ?? stageByStatus[String(shipmentStatus || '').toLowerCase().replace(/[_-]+/g, ' ').trim()] ?? 0;
    document.getElementById('trackingProgress').innerHTML = stages.map((stage, index) => `
        <div class="tracking-stage ${index < stageIndex ? 'done' : ''} ${index === stageIndex ? 'active' : ''}">
            <span class="tracking-stage-label"><i class="bi ${stage[1]}"></i>${stage[0]}</span>
        </div>`).join('');
}

function trackingStatusIcon(status) {
    const normalized = String(status || '').toLowerCase().replace(/[_-]+/g, ' ').trim();
    const icons = {
        'pickup requested': 'bi-inbox',
        booked: 'bi-box-arrow-in-down',
        'picked up': 'bi-box-seam',
        'in transit': 'bi-truck',
        'out for delivery': 'bi-bicycle',
        delivered: 'bi-box2-heart',
        'on hold': 'bi-pause-circle',
        'return to origin': 'bi-arrow-return-left',
        damage: 'bi-exclamation-triangle',
        exception: 'bi-exclamation-octagon',
    };
    return icons[normalized] || 'bi-box-seam';
}

function formatTime(dt) {
    const d = new Date(dt);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-IN') + ' ' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}

function esc(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

loadTracking();
</script>

<?php require_once 'includes/footer.php'; ?>
