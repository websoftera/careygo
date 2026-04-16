<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$authUser = auth_require('customer');
$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit; }

// Verify ownership
try {
    $stmt = $pdo->prepare("SELECT id, tracking_no, status FROM shipments WHERE id = ? AND customer_id = ?");
    $stmt->execute([$id, $authUser['sub']]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $shipment = null; }

if (!$shipment) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Shipment <?= htmlspecialchars($shipment['tracking_no']) ?> — Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
    <style>
        :root { --primary: #001A93; --bg: #f0f2f9; --border: #e4e7f0; --muted: #6b7280; --text: #1a1a2e; }
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
        .timeline-status { font-size: 14px; font-weight: 600; color: var(--text); margin-top: 2px; }
        .timeline-location { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .timeline-desc { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.5; }
        .dtdc-badge { display: inline-block; background: rgba(0,26,147,0.1); color: var(--primary); padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-left: 8px; }
        .no-events { text-align: center; padding: 40px 20px; color: var(--muted); }
        .no-events i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
        .loading { text-align: center; padding: 40px; }
        .alert { border-radius: 12px; padding: 14px 16px; font-size: 13px; margin-bottom: 16px; }
        .alert-warning { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); color: #92400e; }
    </style>
</head>
<body class="customer-body">
    <aside class="cust-sidebar" id="custSidebar"></aside>
    <div class="sidebar-overlay" id="custOverlay"></div>
    <div class="cust-content-wrap">
        <header class="cust-topbar">
            <button class="cust-toggle-btn d-lg-none" id="custToggle"><i class="bi bi-list"></i></button>
            <div class="cust-topbar-title">Track Shipment</div>
            <div class="cust-topbar-actions">
                <a href="dashboard.php" class="btn-outline-admin" style="font-size:12px;padding:7px 14px;text-decoration:none;">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </header>
        <main class="cust-main">
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
                <div id="statusBadge" style="margin-bottom:16px;"></div>
                <div id="trackingContent">
                    <div class="loading"><span class="spinner-border spinner-border-sm text-primary"></span></div>
                </div>
            </div>
        </main>
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

            // Status badge
            const statusMap = { booked: 'Booked', picked_up: 'Picked Up', in_transit: 'In Transit', out_for_delivery: 'Out for Delivery', delivered: 'Delivered', cancelled: 'Cancelled' };
            const statusColors = { booked: '#3B5BDB', picked_up: '#f59e0b', in_transit: '#6366f1', out_for_delivery: '#f59e0b', delivered: '#22c55e', cancelled: '#ef4444' };
            const color = statusColors[s.status] || '#001A93';
            document.getElementById('statusBadge').innerHTML = `<span class="status-badge" style="background: ${color}"><i class="bi bi-circle-fill me-1" style="font-size:8px"></i> ${statusMap[s.status] || s.status}</span>`;

            // Timeline
            let html = '<div class="timeline">';
            if (events.length === 0) {
                html = '<div class="no-events"><i class="bi bi-inbox"></i><p>No tracking updates yet</p></div>';
            } else {
                for (let i = 0; i < events.length; i++) {
                    const e = events[i];
                    const isCompleted = i > 0; // First item is current
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

            document.getElementById('trackingContent').innerHTML = html;
        }

        function formatTime(dt) {
            const d = new Date(dt);
            return d.toLocaleDateString('en-IN') + ' ' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
        }

        function esc(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        loadTracking();
    </script>
</body>
</html>
