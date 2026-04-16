<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/dtdc.php';

// Get tracking number from query string
$tracking = trim($_GET['tracking'] ?? '');

if (!$tracking) {
    http_response_code(400);
    header('Location: index.php');
    exit;
}

// Fetch shipment by tracking number (public access — no auth required)
$stmt = $pdo->prepare("
    SELECT id, tracking_no, status, pickup_city, delivery_city, weight,
           service_type, estimated_delivery, created_at, delivery_name, delivery_phone, delivery_address
    FROM shipments
    WHERE tracking_no = ?
    LIMIT 1
");
$stmt->execute([$tracking]);
$shipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipment) {
    http_response_code(404);
    die('<h2>Tracking number not found</h2><p><a href="index.php">← Back home</a></p>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Shipment - Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #001A93;
            --bg: #f0f2f9;
            --border: #e4e7f0;
            --muted: #6b7280;
            --text: #1a1a2e;
        }
        body { background: var(--bg); font-family: 'Poppins', sans-serif; }
        .navbar { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .navbar-brand { color: var(--primary) !important; font-weight: 700; font-size: 20px; }
        .tracking-container { max-width: 700px; margin: 40px auto; padding: 0 15px; }
        .tracking-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,26,147,0.08); margin-bottom: 24px; }
        .tracking-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid var(--border); }
        .track-info h5 { font-family: 'Montserrat', sans-serif; font-weight: 700; color: var(--text); margin-bottom: 4px; font-size: 18px; }
        .track-info p { font-size: 13px; color: var(--muted); margin: 2px 0; }
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
        .no-events { text-align: center; padding: 40px 20px; color: var(--muted); }
        .no-events i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
        .loading { text-align: center; padding: 40px; }
        .shipment-info { background: var(--bg); padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        .info-label { font-weight: 600; color: var(--muted); }
        .info-value { color: var(--text); }
        .footer { text-align: center; padding: 30px 20px; color: var(--muted); font-size: 12px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-box-seam"></i> CAREYGO
            </a>
            <span class="navbar-text ms-auto">
                <i class="bi bi-truck me-2"></i> Live Package Tracking
            </span>
        </div>
    </nav>

    <div class="tracking-container">
        <div class="tracking-card">
            <div class="tracking-header">
                <div class="track-info">
                    <h5><?= htmlspecialchars($shipment['tracking_no']) ?></h5>
                    <p><i class="bi bi-geo-alt me-1"></i> Track your shipment in real-time</p>
                </div>
            </div>

            <div id="statusBadge" style="margin-bottom: 20px;"></div>

            <div class="shipment-info">
                <div class="info-row">
                    <span class="info-label">From:</span>
                    <span class="info-value"><?= htmlspecialchars($shipment['pickup_city']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">To:</span>
                    <span class="info-value"><?= htmlspecialchars($shipment['delivery_city']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Weight:</span>
                    <span class="info-value"><?= number_format((float)$shipment['weight'], 3) ?> kg</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Expected Delivery:</span>
                    <span class="info-value"><?= date('d M Y', strtotime($shipment['estimated_delivery'])) ?></span>
                </div>
            </div>

            <div id="trackingContent">
                <div class="loading"><span class="spinner-border spinner-border-sm text-primary"></span> Loading tracking details...</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Careygo Logistics - Fast & Reliable Courier Service</p>
        <p>For support: <a href="mailto:support@careygo.in">support@careygo.in</a> | <a href="tel:+919850296178">+91-98502-96178</a></p>
    </div>

    <script>
        const TRACKING_NO = '<?= htmlspecialchars($shipment['tracking_no']) ?>';
        const SITE_URL = '<?= rtrim(SITE_URL, '/') ?>';

        function loadTracking() {
            fetch(`${SITE_URL}/api/tracking.php?tracking=${encodeURIComponent(TRACKING_NO)}`)
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
                    const isCompleted = i > 0;
                    const dtdcLabel = e.source === 'dtdc' ? '<span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:3px;font-size:9px;margin-left:6px;font-weight:600;">DTDC</span>' : '';
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
                html += '<div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px;border-radius:8px;margin-top:20px;font-size:13px;"><i class="bi bi-info-circle me-2"></i> <strong>Live tracking</strong> powered by DTDC</div>';
            }
            if (data.dtdc_error) {
                html += `<div style="background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;padding:12px;border-radius:8px;margin-top:20px;font-size:13px;"><i class="bi bi-exclamation-triangle me-2"></i> ${esc(data.dtdc_error)}</div>`;
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
