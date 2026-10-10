<?php
/**
 * Admin UI — Manually add tracking events to test the system
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/tracking.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    die('Access Denied');
}

tracking_ensure_schema($pdo);

$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
    $eventTime  = trim($_POST['event_time'] ?? '');
    $status     = trim($_POST['status'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($description === '') {
        $messages = [
            'pickup requested' => 'Pickup has been requested for this shipment.',
            'booked' => 'Shipment booking has been created and is waiting for pickup.',
            'in transit' => 'Shipment is moving through the courier network.',
            'out for delivery' => 'Shipment is out for delivery.',
            'delivered' => 'Shipment has been delivered successfully.',
            'on hold' => 'Shipment is temporarily on hold. Our team is checking the next steps.',
            'return to origin' => 'Shipment is being returned to the origin.',
            'damage' => 'A damage issue was reported for this shipment. Our team is reviewing it.',
            'exception' => 'Shipment needs attention. Our team is checking the issue.',
        ];
        $description = $messages[strtolower($status)] ?? 'Shipment status updated.';
    }

    if (!$shipmentId || !$eventTime || !$status) {
        $message = 'Shipment ID, Event Time, and Status are required.';
    } else {
        try {
            // Verify shipment exists
            $stmt = $pdo->prepare("SELECT id, tracking_no FROM shipments WHERE id = ?");
            $stmt->execute([$shipmentId]);
            $ship = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ship) {
                $message = 'Shipment not found.';
            } else {
                // Insert tracking event
                $pdo->prepare(
                    "INSERT INTO shipment_tracking_events
                     (shipment_id, event_time, location, next_destination, expected_at, status, description, destination_details, source)
                     VALUES (?, ?, NULL, NULL, NULL, ?, ?, NULL, 'manual')"
                )->execute([
                    $shipmentId,
                    date('Y-m-d H:i:s', strtotime($eventTime)),
                    $status,
                    $description ?: null,
                ]);

                $success = true;
                $message = "✅ Event added for shipment {$ship['tracking_no']}";
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// Get recent shipments
$shipments = [];
try {
    $stmt = $pdo->query(
        "SELECT id, tracking_no, status, created_at FROM shipments
         ORDER BY created_at DESC LIMIT 10"
    );
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Tracking Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 40px; }
        .card { max-width: 700px; margin: 0 auto; }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📍 Add Tracking Event (Manual)</h5>
    </div>
    <div class="card-body">

        <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><strong>Shipment</strong></label>
                <select class="form-control" name="shipment_id" required onchange="updateTracking()">
                    <option value="">Select a shipment...</option>
                    <?php foreach ($shipments as $ship): ?>
                        <option value="<?= $ship['id'] ?>" data-tracking="<?= htmlspecialchars($ship['tracking_no']) ?>">
                            <?= htmlspecialchars($ship['tracking_no']) ?> (<?= $ship['status'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Status</strong></label>
                    <select class="form-control" name="status" required>
                        <option value="">Select status...</option>
                        <option value="Pickup Requested">Pickup Requested</option>
                        <option value="Booked">Booked</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Return to Origin">Return to Origin</option>
                        <option value="Damage">Damage</option>
                        <option value="Exception">Exception</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>Date &amp; Time</strong></label>
                    <input type="datetime-local" class="form-control" name="event_time" required
                           value="<?= date('Y-m-d\TH:i') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><strong>Destination / Transit Details</strong></label>
                <textarea class="form-control" name="description" rows="3" placeholder="Vehicle, route, branch note, delay reason, contact point, etc."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Add Event</button>
        </form>

    </div>
</div>

<script>
function updateTracking() {
    const select = document.querySelector('select[name="shipment_id"]');
    const option = select.options[select.selectedIndex];
    const tracking = option.dataset.tracking;
    // Just for UX feedback
}
</script>

</body>
</html>
