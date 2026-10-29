<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

header('Content-Type: application/json');
$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$statuses = ['Pickup Requested', 'Booked', 'In Transit', 'Out for Delivery', 'Delivered', 'On Hold', 'Return to Origin', 'Damage', 'Exception'];

function int_tracking_body(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function int_tracking_get_shipment(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM international_shipments WHERE id = ?');
    $stmt->execute([$id]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$shipment) json_response(['success' => false, 'message' => 'International shipment not found.'], 404);
    return $shipment;
}

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $shipment = int_tracking_get_shipment($pdo, $id);
        $stmt = $pdo->prepare('SELECT id, event_time, location, status, description, created_at FROM international_tracking_events WHERE shipment_id = ? ORDER BY event_time DESC, id DESC');
        $stmt->execute([$id]);
        json_response(['success' => true, 'shipment' => $shipment, 'events' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    $rows = $pdo->query('SELECT id, tracking_no, customer_name, customer_phone, origin_city, origin_country, destination_city, destination_country, status, created_at FROM international_shipments ORDER BY created_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success' => true, 'shipments' => $rows]);
}

if ($method === 'POST') {
    $body = int_tracking_body();
    $action = trim((string)($body['action'] ?? ''));

    if ($action === 'create') {
        $name = trim((string)($body['customer_name'] ?? ''));
        $phone = trim((string)($body['customer_phone'] ?? ''));
        $originCity = trim((string)($body['origin_city'] ?? ''));
        $originCountry = trim((string)($body['origin_country'] ?? ''));
        $destCity = trim((string)($body['destination_city'] ?? ''));
        $destCountry = trim((string)($body['destination_country'] ?? ''));
        if (!$name || !$originCity || !$originCountry || !$destCity || !$destCountry) {
            json_response(['success' => false, 'message' => 'Customer, origin and destination city/country are required.'], 422);
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO international_shipments
                (customer_name, customer_phone, origin_city, origin_region, origin_postal_code, origin_country, origin_address,
                 destination_city, destination_region, destination_postal_code, destination_country, destination_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, $phone ?: null, $originCity, trim((string)($body['origin_region'] ?? '')) ?: null,
                trim((string)($body['origin_postal_code'] ?? '')) ?: null, $originCountry,
                trim((string)($body['origin_address'] ?? '')) ?: null, $destCity,
                trim((string)($body['destination_region'] ?? '')) ?: null,
                trim((string)($body['destination_postal_code'] ?? '')) ?: null, $destCountry,
                trim((string)($body['destination_address'] ?? '')) ?: null,
            ]);
            $id = (int)$pdo->lastInsertId();
            $trackingNo = 'INT PNQ01 ' . date('dmy') . ' ' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE international_shipments SET tracking_no = ? WHERE id = ?')->execute([$trackingNo, $id]);
            $pdo->commit();
            json_response(['success' => true, 'message' => 'International tracking created.', 'id' => $id, 'tracking_no' => $trackingNo]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('International tracking create failed: ' . $e->getMessage());
            json_response(['success' => false, 'message' => 'Could not create international tracking.'], 500);
        }
    }

    $id = (int)($body['shipment_id'] ?? 0);
    if (!$id) json_response(['success' => false, 'message' => 'Shipment is required.'], 422);
    int_tracking_get_shipment($pdo, $id);

    if ($action === 'update_route') {
        $fields = ['origin_city','origin_region','origin_postal_code','origin_country','origin_address','destination_city','destination_region','destination_postal_code','destination_country','destination_address'];
        $values = [];
        foreach ($fields as $field) $values[] = trim((string)($body[$field] ?? '')) ?: null;
        foreach (['origin_city','origin_country','destination_city','destination_country'] as $required) {
            if (trim((string)($body[$required] ?? '')) === '') json_response(['success' => false, 'message' => 'Origin and destination city/country are required.'], 422);
        }
        $sql = 'UPDATE international_shipments SET ' . implode(', ', array_map(static fn($field) => "$field = ?", $fields)) . ' WHERE id = ?';
        $values[] = $id;
        $pdo->prepare($sql)->execute($values);
        json_response(['success' => true, 'message' => 'International origin and destination updated.']);
    }

    if ($action === 'add_event' || $action === 'edit_event') {
        $eventId = (int)($body['event_id'] ?? 0);
        $eventTime = trim((string)($body['event_time'] ?? ''));
        $status = trim((string)($body['status'] ?? ''));
        $location = trim((string)($body['location'] ?? ''));
        $description = trim((string)($body['description'] ?? ''));
        $timestamp = strtotime($eventTime);
        if (!$timestamp || !$status || !in_array($status, $statuses, true) || ($action === 'edit_event' && !$eventId)) {
            json_response(['success' => false, 'message' => 'Choose a valid status and date/time.'], 422);
        }
        $eventTime = date('Y-m-d H:i:s', $timestamp);
        if ($action === 'edit_event') {
            $stmt = $pdo->prepare('UPDATE international_tracking_events SET event_time = ?, location = ?, status = ?, description = ? WHERE id = ? AND shipment_id = ?');
            $stmt->execute([$eventTime, $location ?: null, $status, $description ?: null, $eventId, $id]);
            if (!$stmt->rowCount()) {
                $check = $pdo->prepare('SELECT id FROM international_tracking_events WHERE id = ? AND shipment_id = ?');
                $check->execute([$eventId, $id]);
                if (!$check->fetch()) json_response(['success' => false, 'message' => 'Tracking event not found.'], 404);
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO international_tracking_events (shipment_id, event_time, location, status, description) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$id, $eventTime, $location ?: null, $status, $description ?: null]);
            $eventId = (int)$pdo->lastInsertId();
        }
        $latest = $pdo->prepare('SELECT status FROM international_tracking_events WHERE shipment_id = ? ORDER BY event_time DESC, id DESC LIMIT 1');
        $latest->execute([$id]);
        $pdo->prepare('UPDATE international_shipments SET status = ? WHERE id = ?')->execute([$latest->fetchColumn() ?: $status, $id]);
        json_response(['success' => true, 'message' => $action === 'edit_event' ? 'Tracking event updated.' : 'Tracking event added.', 'event_id' => $eventId]);
    }

    json_response(['success' => false, 'message' => 'Unknown action.'], 422);
}

if ($method === 'DELETE') {
    $body = int_tracking_body();
    if (!empty($body['event_id'])) {
        $stmt = $pdo->prepare('DELETE FROM international_tracking_events WHERE id = ?');
        $stmt->execute([(int)$body['event_id']]);
        if (!$stmt->rowCount()) json_response(['success' => false, 'message' => 'Tracking event not found.'], 404);
        $shipmentId = (int)($body['shipment_id'] ?? 0);
        if ($shipmentId) {
            $latest = $pdo->prepare('SELECT status FROM international_tracking_events WHERE shipment_id = ? ORDER BY event_time DESC, id DESC LIMIT 1');
            $latest->execute([$shipmentId]);
            $status = $latest->fetchColumn() ?: 'Booked';
            $pdo->prepare('UPDATE international_shipments SET status = ? WHERE id = ?')->execute([$status, $shipmentId]);
        }
        json_response(['success' => true, 'message' => 'Tracking event deleted.']);
    }
    $id = (int)($body['shipment_id'] ?? 0);
    if (!$id) json_response(['success' => false, 'message' => 'Shipment is required.'], 422);
    $stmt = $pdo->prepare('DELETE FROM international_shipments WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? 'International tracking deleted.' : 'Shipment not found.']);
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
