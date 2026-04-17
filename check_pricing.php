<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die('Access Denied. Admin only.');
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Check</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body style='padding: 20px;'>";

try {
    // Check pricing_slabs table
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs")->fetch(PDO::FETCH_ASSOC);
    $count = $result['cnt'];

    echo "<div class='alert alert-info'>";
    echo "<h4>✅ Database Check Results</h4>";
    echo "<p><strong>Total pricing slabs in database:</strong> $count</p>";

    // Check zones
    $zones = $pdo->query("SELECT DISTINCT zone FROM pricing_slabs WHERE zone IS NOT NULL ORDER BY zone")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Zones populated:</strong> " . count($zones) . "</p>";
    echo "<ul>";
    foreach($zones as $z) {
        $zoneName = $z['zone'];
        $zoneCount = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs WHERE zone = ?")->fetch(PDO::FETCH_ASSOC, [$zoneName]);
        echo "<li>$zoneName: " . $zoneCount['cnt'] . " slabs</li>";
    }
    echo "</ul>";

    // Check service types
    $services = $pdo->query("SELECT DISTINCT service_type FROM pricing_slabs ORDER BY service_type")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Service types:</strong> " . count($services) . "</p>";
    echo "<ul>";
    foreach($services as $s) {
        echo "<li>" . $s['service_type'] . "</li>";
    }
    echo "</ul>";

    echo "<hr>";
    echo "<p style='color: #666; font-size: 12px;'>";
    if ($count === 0) {
        echo "ℹ️ No pricing data found. Run <strong>import_pricing.php</strong> to import data.";
    } else if ($count < 20) {
        echo "⚠️ Pricing data may be incomplete. Expected ~48 slabs (4 zones × 3 service types). Current: $count";
    } else {
        echo "✅ Pricing data appears complete.";
    }
    echo "</p>";

    echo "</div>";

} catch(Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>ERROR:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</body></html>";
