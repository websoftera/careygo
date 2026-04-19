<?php
/**
 * Check Multiple AWBs for Tracking Data
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/dtdc.php';
require_once __DIR__ . '/../lib/helpers.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    die('Access Denied');
}

$awbs = [
    'P79187925',
    'P79187948',
    'I25512964',
    'D1010809969',
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check AWBs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 40px; }
        .card { margin-bottom: 20px; }
        .success { border-left: 5px solid green; }
        .error { border-left: 5px solid red; }
        .warning { border-left: 5px solid orange; }
        pre { background: #000; color: #0f0; padding: 10px; border-radius: 5px; font-size: 11px; max-height: 250px; overflow-y: auto; }
    </style>
</head>
<body>

<h2>📦 Checking AWBs for Tracking Events</h2>

<?php
$dtdc = new DtdcClient([
    'username'     => env('DTDC_USERNAME'),
    'password'     => env('DTDC_PASSWORD'),
    'api_key'      => env('DTDC_API_KEY'),
    'customer_code' => env('DTDC_CUSTOMER_CODE'),
]);

foreach ($awbs as $awb) {
    $result = $dtdc->track($awb);

    if ($result['success']) {
        $eventCount = count($result['events']);
        $cardClass = $eventCount > 0 ? 'success' : 'warning';
        $icon = $eventCount > 0 ? '✅' : '⚠️';

        ?>
        <div class="card <?= $cardClass ?>">
            <div class="card-header">
                <h5><?= $icon ?> <strong><?= htmlspecialchars($awb) ?></strong> - <?= $eventCount ?> events</h5>
            </div>
            <div class="card-body">
                <?php if ($eventCount > 0): ?>
                    <h6>Tracking Events:</h6>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['events'] as $event): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars($event['event_time']) ?></small></td>
                                    <td><small><?= htmlspecialchars($event['location']) ?></small></td>
                                    <td><small><strong><?= htmlspecialchars($event['status']) ?></strong></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-warning">⚠️ No tracking events found yet. Package may be pending pickup.</p>
                <?php endif; ?>

                <hr>
                <h6>Raw Response:</h6>
                <pre><?= json_encode($result['raw'], JSON_PRETTY_PRINT) ?></pre>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="card error">
            <div class="card-header">
                <h5>❌ <strong><?= htmlspecialchars($awb) ?></strong> - Error</h5>
            </div>
            <div class="card-body">
                <p><strong>Error:</strong> <?= htmlspecialchars($result['error']) ?></p>
            </div>
        </div>
        <?php
    }
}
?>

<div class="card">
    <div class="card-body">
        <h6>Summary:</h6>
        <ul>
            <li><strong>✅ Green card = AWB found with tracking events</strong> - Can be displayed to customer</li>
            <li><strong>⚠️ Orange card = AWB found but no events yet</strong> - Not picked up yet</li>
            <li><strong>❌ Red card = AWB not found or not authorized</strong> - AWB doesn't exist in your account</li>
        </ul>
    </div>
</div>

</body>
</html>
