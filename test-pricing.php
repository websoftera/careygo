<?php
/**
 * Test Pricing API
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/helpers.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Pricing API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">💰 Test Pricing API</h5>
    </div>
    <div class="card-body">

        <h6>Database Checks:</h6>
        <ul class="list-unstyled">
            <?php
            // Check pricing_slabs table
            try {
                $count = $pdo->query("SELECT COUNT(*) as c FROM pricing_slabs")->fetchColumn();
                $status = $count > 0 ? '✅' : '⚠️';
                echo "<li>$status Pricing Slabs: $count records</li>";
            } catch (Exception $e) {
                echo "<li>❌ Pricing Slabs: " . htmlspecialchars($e->getMessage()) . "</li>";
            }

            // Check pincode_tat table
            try {
                $count = $pdo->query("SELECT COUNT(*) as c FROM pincode_tat")->fetchColumn();
                $status = $count > 0 ? '✅' : '⚠️';
                echo "<li>$status Pincode TAT: $count records</li>";
            } catch (Exception $e) {
                echo "<li>❌ Pincode TAT: " . htmlspecialchars($e->getMessage()) . "</li>";
            }

            // Check specific pincodes from your test
            try {
                $p1 = $pdo->query("SELECT * FROM pincode_tat WHERE pincode = '110001' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                $p2 = $pdo->query("SELECT * FROM pincode_tat WHERE pincode = '226001' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

                echo "<li>✅ Pincode 110001: " . ($p1 ? $p1['city'] . ", " . $p1['state'] : "❌ NOT FOUND") . "</li>";
                echo "<li>✅ Pincode 226001: " . ($p2 ? $p2['city'] . ", " . $p2['state'] : "❌ NOT FOUND") . "</li>";
            } catch (Exception $e) {
                echo "<li>❌ Pincode lookup: " . htmlspecialchars($e->getMessage()) . "</li>";
            }
            ?>
        </ul>

        <hr>

        <h6>API Test:</h6>
        <p style="font-size: 13px; color: #666;">Testing: weight=2, pickup=110001, delivery=226001</p>

        <?php
        // Test the pricing API directly
        $weight = 2;
        $pickup = '110001';
        $delivery = '226001';

        try {
            require_once __DIR__ . '/lib/auth.php';

            // Manually run through the pricing logic
            $validZones = ['within_city', 'within_state', 'metro', 'rest_of_india'];

            // Get pincode data
            $pickupRow = $pdo->query("SELECT * FROM pincode_tat WHERE pincode = '$pickup' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $tatRow = $pdo->query("SELECT * FROM pincode_tat WHERE pincode = '$delivery' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

            if (!$pickupRow) throw new Exception("Pickup pincode $pickup not found");
            if (!$tatRow) throw new Exception("Delivery pincode $delivery not found");

            // Determine zone
            $zone = determineZone($pickupRow['city'], $pickupRow['state'], $tatRow['city'], $tatRow['state']);

            echo "<div class='alert alert-info'>";
            echo "<strong>Zone Detection:</strong><br>";
            echo "Pickup: " . $pickupRow['city'] . ", " . $pickupRow['state'] . "<br>";
            echo "Delivery: " . $tatRow['city'] . ", " . $tatRow['state'] . "<br>";
            echo "Zone: <strong>$zone</strong>";
            echo "</div>";

            // Check pricing slabs for this zone
            $slabs = $pdo->query("SELECT * FROM pricing_slabs WHERE zone = '$zone' OR zone IS NULL LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

            echo "<div class='alert alert-success'>";
            echo "<strong>✅ Test Passed!</strong><br>";
            echo "Found " . count($slabs) . " pricing slabs for zone '$zone'<br>";
            echo "Weight: " . $weight . " kg<br>";
            echo "Can calculate pricing.";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>";
            echo "<strong>❌ Error:</strong><br>";
            echo htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        ?>

        <hr>

        <h6>Fetch API Test:</h6>
        <p style="font-size: 13px; color: #666;">Click button to test the actual API endpoint:</p>

        <button class="btn btn-primary btn-sm" onclick="testAPI()">Test /api/pricing.php</button>

        <pre id="result" style="margin-top: 15px; background: #f0f0f0; padding: 10px; border-radius: 5px; display: none;"></pre>

    </div>
</div>

<script>
function testAPI() {
    const url = '/careygo/api/pricing.php?weight=2&pickup=110001&delivery=226001';

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const result = document.getElementById('result');
            result.textContent = JSON.stringify(data, null, 2);
            result.style.display = 'block';
        })
        .catch(err => {
            document.getElementById('result').textContent = 'Error: ' + err.message;
            document.getElementById('result').style.display = 'block';
        });
}
</script>

</body>
</html>
