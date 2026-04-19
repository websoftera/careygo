<?php
/**
 * Admin API — Shipment Tracking Management
 *
 * GET  ?shipment_id=N               — load tracking info + events for a shipment
 * POST {action:'save_awb',  id, dtdc_awb}       — update DTDC AWB
 * POST {action:'add_event', shipment_id, event_time, location, status, description} — add manual event
 * DELETE {event_id}                 — remove a manual tracking event
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/dtdc.php';
require_once __DIR__ . '/../../lib/helpers.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: load tracking data ───────────────────────────────────
if ($method === 'GET') {
    $sid = (int) ($_GET['shipment_id'] ?? 0);
    if (!$sid) json_response(['success' => false, 'message' => 'shipment_id required.'], 422);

    try {
        $stmt = $pdo->prepare("SELECT id, tracking_no, dtdc_awb, status FROM shipments WHERE id = ?");
        $stmt->execute([$sid]);
        $ship = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ship) json_response(['success' => false, 'message' => 'Shipment not found.'], 404);

        $events = [];

        // ── Fetch DTDC tracking if AWB is assigned ──────────────────────
        if (!empty($ship['dtdc_awb'])) {
            try {
                $dtdc = new DtdcClient([
                    'username'     => env('DTDC_USERNAME'),
                    'password'     => env('DTDC_PASSWORD'),
                    'apiKey'       => env('DTDC_API_KEY'),
                    'customerCode' => env('DTDC_CUSTOMER_CODE'),
                ]);

                $trackResult = $dtdc->track($ship['dtdc_awb']);

                if ($trackResult['success']) {
                    // Convert DTDC events to our format and store in database
                    foreach ($trackResult['events'] as $evt) {
                        // Check if this event already exists (by event_time and location)
                        $chk = $pdo->prepare(
                            "SELECT id FROM shipment_tracking_events
                             WHERE shipment_id = ? AND source = 'dtdc'
                             AND event_time = ? AND location = ? LIMIT 1"
                        );
                        $chk->execute([$sid, $evt['event_time'], $evt['location']]);

                        if (!$chk->fetch()) {
                            // New event - store it
                            $pdo->prepare(
                                "INSERT INTO shipment_tracking_events
                                 (shipment_id, event_time, location, status, description, source)
                                 VALUES (?, ?, ?, ?, ?, 'dtdc')"
                            )->execute([
                                $sid,
                                $evt['event_time'],
                                $evt['location'],
                                $evt['status'],
                                $evt['description'],
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                // Log DTDC error but continue with database events
                error_log('DTDC tracking error for AWB ' . $ship['dtdc_awb'] . ': ' . $e->getMessage());
            }
        }

        // ── Fetch all events from database (including cached DTDC events) ──
        $eStmt = $pdo->prepare(
            "SELECT id, event_time, location, status, description, source, created_at
             FROM shipment_tracking_events
             WHERE shipment_id = ?
             ORDER BY event_time DESC"
        );
        $eStmt->execute([$sid]);
        $events = $eStmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'shipment' => $ship, 'events' => $events]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'DB error: ' . $e->getMessage()], 500);
    }
}

// ── POST: save AWB or add manual event ───────────────────────
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = trim($body['action'] ?? '');

    // ── Save / clear DTDC AWB ─────────────────────────────
    if ($action === 'save_awb') {
        $id      = (int)   ($body['id']       ?? 0);
        $dtdcAwb = trim($body['dtdc_awb'] ?? '');

        if (!$id) json_response(['success' => false, 'message' => 'id required.'], 422);

        // Basic AWB sanity — letters + digits only, 5–20 chars (or empty to clear)
        if ($dtdcAwb !== '' && !preg_match('/^[A-Za-z0-9\-]{3,30}$/', $dtdcAwb)) {
            json_response(['success' => false, 'message' => 'Invalid AWB format.'], 422);
        }

        try {
            $pdo->prepare("UPDATE shipments SET dtdc_awb = ? WHERE id = ?")
                ->execute([$dtdcAwb ?: null, $id]);
            json_response(['success' => true, 'message' => $dtdcAwb ? 'DTDC AWB saved.' : 'AWB cleared.']);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'DB error.'], 500);
        }
    }

    // ── Add manual tracking event ─────────────────────────
    if ($action === 'add_event') {
        $sid         = (int)   ($body['shipment_id'] ?? 0);
        $eventTime   = trim($body['event_time']   ?? '');
        $location    = trim($body['location']     ?? '');
        $status      = trim($body['status']       ?? '');
        $description = trim($body['description']  ?? '');

        if (!$sid || !$eventTime || !$status) {
            json_response(['success' => false, 'message' => 'shipment_id, event_time and status are required.'], 422);
        }

        // Validate datetime
        $ts = strtotime($eventTime);
        if (!$ts) json_response(['success' => false, 'message' => 'Invalid event_time.'], 422);
        $eventTime = date('Y-m-d H:i:s', $ts);

        // Verify shipment exists
        $chk = $pdo->prepare("SELECT id FROM shipments WHERE id = ?");
        $chk->execute([$sid]);
        if (!$chk->fetch()) json_response(['success' => false, 'message' => 'Shipment not found.'], 404);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO shipment_tracking_events
                    (shipment_id, event_time, location, status, description, source)
                 VALUES (?, ?, ?, ?, ?, 'manual')"
            );
            $stmt->execute([$sid, $eventTime, $location ?: null, $status, $description ?: null]);
            $newId = (int) $pdo->lastInsertId();

            json_response([
                'success' => true,
                'event'   => [
                    'id'          => $newId,
                    'shipment_id' => $sid,
                    'event_time'  => $eventTime,
                    'location'    => $location,
                    'status'      => $status,
                    'description' => $description,
                    'source'      => 'manual',
                ],
            ]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'DB error: ' . $e->getMessage()], 500);
        }
    }

    json_response(['success' => false, 'message' => 'Unknown action.'], 422);
}

// ── DELETE: remove manual event ───────────────────────────────
if ($method === 'DELETE') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $eventId = (int) ($body['event_id'] ?? 0);

    if (!$eventId) json_response(['success' => false, 'message' => 'event_id required.'], 422);

    try {
        // Only manual events can be deleted; dtdc events are fetched live
        $stmt = $pdo->prepare("DELETE FROM shipment_tracking_events WHERE id = ? AND source = 'manual'");
        $stmt->execute([$eventId]);

        if ($stmt->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Event not found or is a DTDC event.'], 404);
        }
        json_response(['success' => true]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'DB error.'], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
