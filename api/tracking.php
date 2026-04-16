<?php
/**
 * GET /api/tracking.php?tracking=CGO-XXXXXXXX   — public, by our tracking number
 * GET /api/tracking.php?id=N                    — customer must own shipment OR admin
 *
 * Returns unified tracking data: shipment info + merged event timeline
 * (DTDC live events if dtdc_awb set, otherwise manual events from DB)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/dtdc.php';

header('Content-Type: application/json');

// ── Resolve shipment ─────────────────────────────────────────
$tracking = trim($_GET['tracking'] ?? '');
$id       = (int) ($_GET['id'] ?? 0);
$shipment = null;

try {
    if ($id) {
        // Requires auth — customer can only see own shipments; admin sees all
        $authUser = auth_user();
        if (!$authUser) json_response(['success' => false, 'message' => 'Login required.'], 401);

        if ($authUser['role'] === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ? AND customer_id = ?");
            $stmt->execute([$id, $authUser['sub']]);
        }
        $shipment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    } elseif ($tracking !== '') {
        // Public — anyone with the tracking number can see status
        $stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_no = ?");
        $stmt->execute([$tracking]);
        $shipment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Database error.'], 500);
}

if (!$shipment) {
    json_response(['success' => false, 'message' => 'Shipment not found.'], 404);
}

// ── Build timeline ────────────────────────────────────────────
$events     = [];
$dtdcError  = null;
$dtdcLive   = false;
$dtdcAwb    = trim($shipment['dtdc_awb'] ?? '');

// 1. Fetch manual / cached events from DB
try {
    $stmt = $pdo->prepare(
        "SELECT id, event_time, location, status, description, source
         FROM shipment_tracking_events
         WHERE shipment_id = ?
         ORDER BY event_time DESC"
    );
    $stmt->execute([$shipment['id']]);
    $dbEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dbEvents as $e) {
        $events[] = [
            'event_time'  => $e['event_time'],
            'location'    => $e['location'] ?? '',
            'status'      => $e['status'],
            'description' => $e['description'] ?? '',
            'source'      => $e['source'],
        ];
    }
} catch (Exception $e) { /* table may not exist yet */ }

// 2. If DTDC AWB is set, fetch live from DTDC API
if ($dtdcAwb !== '') {
    try {
        $dtdc   = new DtdcClient();
        $result = $dtdc->track($dtdcAwb);
        if ($result['success'] && !empty($result['events'])) {
            // Replace DB events with DTDC live events (DTDC is source of truth)
            $events   = $result['events'];
            $dtdcLive = true;
        } else {
            $dtdcError = $result['error'] ?? 'DTDC tracking unavailable';
            // Fall back to DB events already loaded above
        }
    } catch (Exception $e) {
        $dtdcError = 'DTDC service temporarily unavailable.';
        // DB events are still available as fallback
    }
}

// 3. If no events at all, synthesise one from shipment status
if (empty($events)) {
    $statusDescriptions = [
        'booked'            => 'Your shipment has been booked and is awaiting pickup.',
        'picked_up'         => 'Your shipment has been picked up and is being processed.',
        'in_transit'        => 'Your shipment is on its way to the destination.',
        'out_for_delivery'  => 'Your shipment is out for delivery.',
        'delivered'         => 'Your shipment has been delivered successfully.',
        'cancelled'         => 'This shipment has been cancelled.',
    ];
    $events[] = [
        'event_time'  => $shipment['updated_at'] ?? $shipment['created_at'],
        'location'    => $shipment['pickup_city'] ?? '',
        'status'      => $shipment['status'],
        'description' => $statusDescriptions[$shipment['status']] ?? ucwords(str_replace('_', ' ', $shipment['status'])),
        'source'      => 'manual',
    ];
}

// ── Build clean shipment summary (no PII beyond what's needed) ──
$serviceLabels = [
    'standard'  => 'Standard Express',
    'premium'   => 'Premium Express',
    'air_cargo' => 'Air Cargo',
    'surface'   => 'Surface Cargo',
];

$summary = [
    'id'                => (int) $shipment['id'],
    'tracking_no'       => $shipment['tracking_no'],
    'dtdc_awb'          => $dtdcAwb ?: null,
    'status'            => $shipment['status'],
    'service_type'      => $shipment['service_type'],
    'service_label'     => $serviceLabels[$shipment['service_type']] ?? $shipment['service_type'],
    'weight'            => (float) $shipment['weight'],
    'pickup_city'       => $shipment['pickup_city'],
    'pickup_state'      => $shipment['pickup_state'],
    'pickup_pincode'    => $shipment['pickup_pincode'],
    'delivery_city'     => $shipment['delivery_city'],
    'delivery_state'    => $shipment['delivery_state'],
    'delivery_pincode'  => $shipment['delivery_pincode'],
    'estimated_delivery'=> $shipment['estimated_delivery'],
    'created_at'        => $shipment['created_at'],
];

json_response([
    'success'    => true,
    'shipment'   => $summary,
    'events'     => $events,
    'dtdc_live'  => $dtdcLive,
    'dtdc_error' => $dtdcError,
]);
