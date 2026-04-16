<?php
/**
 * Import Pricing Slabs from Excel Data
 *
 * Adds pricing data for:
 * - Standard Express
 * - Premium Express
 * - Air Cargo
 * - Surface Cargo
 *
 * Zones: Within City, Within State, Metro, Rest of India
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

// Check authentication (admin only)
$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die('Access Denied. Admin only.');
}

// Pricing data from Excel sheet
$pricingData = [
    'standard' => [
        // Standard Express pricing
        ['weight_from' => 0,     'weight_to' => 0.25,  'base_price' => 100,  'increment_price' => null, 'increment_per_kg' => null],  // 0-250gm: fixed 100
        ['weight_from' => 0.25,  'weight_to' => 0.5,   'base_price' => 110,  'increment_price' => null, 'increment_per_kg' => null],  // 250-500gm: fixed 110
        ['weight_from' => 0.5,   'weight_to' => null,  'base_price' => 60,   'increment_price' => 60,   'increment_per_kg' => 1],     // 500gm+: +60 per kg
    ],
    'premium' => [
        // Premium Express pricing
        ['weight_from' => 0,     'weight_to' => 0.25,  'base_price' => 245,  'increment_price' => null, 'increment_per_kg' => null],  // 0-250gm: fixed 245
        ['weight_from' => 0.25,  'weight_to' => 0.5,   'base_price' => 260,  'increment_price' => null, 'increment_per_kg' => null],  // 250-500gm: fixed 260
        ['weight_from' => 0.5,   'weight_to' => null,  'base_price' => 100,  'increment_price' => 100,  'increment_per_kg' => 1],     // 500gm+: +100 per kg
    ],
    'air_cargo' => [
        // Air Cargo pricing (not provided in sheet, add as needed)
    ],
    'surface' => [
        // Surface Cargo pricing
        ['weight_from' => 2.0,   'weight_to' => 2.5,   'base_price' => 225,  'increment_price' => null, 'increment_per_kg' => null],  // 2.0-2.5kg: fixed 225
        ['weight_from' => 3.0,   'weight_to' => null,  'base_price' => 0,    'increment_price' => 75,   'increment_per_kg' => 1],     // 3kg+: +75 per kg
    ],
];

$zones = ['within_city', 'within_state', 'metro', 'rest_of_india'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Pricing Slabs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f9; font-family: 'Poppins', sans-serif; padding: 40px 20px; }
        .container { max-width: 800px; }
        .card { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #001A93 0%, #3B5BDB 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 20px; }
        .card-body { padding: 30px; }
        .btn-import { background: #001A93; color: #fff; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .btn-import:hover { background: #0d1f5c; }
        .alert { border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .error { background: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }
        .info-box { background: #eff6ff; color: #1d4ed8; border-left: 4px solid #1d4ed8; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
        table { font-size: 12px; }
        .status { padding: 8px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .status-ok { background: #d1fae5; color: #065f46; }
        .status-error { background: #fee2e2; color: #7f1d1d; }
        .pricing-table { margin-top: 20px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4 style="margin: 0;"><i class="bi bi-currency-rupee me-2"></i> Import Pricing Slabs</h4>
        </div>
        <div class="card-body">
            <div class="info-box">
                <i class="bi bi-info-circle me-2"></i>
                <strong>This tool will import pricing data for all zones:</strong> Within City, Within State, Metro, Rest of India
            </div>

            <form method="POST">
                <button type="submit" name="import" value="1" class="btn-import">
                    <i class="bi bi-download me-2"></i> Import Pricing Data
                </button>
            </form>

            <?php
            // Process import
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
                try {
                    $inserted = 0;
                    $errors = 0;

                    // Clear existing slabs (optional - comment out to keep existing data)
                    // $pdo->exec("DELETE FROM pricing_slabs");

                    foreach ($zones as $zone) {
                        foreach ($pricingData as $serviceType => $slabs) {
                            foreach ($slabs as $index => $slab) {
                                try {
                                    $stmt = $pdo->prepare("
                                        INSERT INTO pricing_slabs
                                        (zone, service_type, weight_from, weight_to, base_price, increment_price, increment_per_kg, sort_order)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                    ");

                                    $stmt->execute([
                                        $zone,
                                        $serviceType,
                                        $slab['weight_from'],
                                        $slab['weight_to'],
                                        $slab['base_price'],
                                        $slab['increment_price'],
                                        $slab['increment_per_kg'],
                                        $index
                                    ]);

                                    $inserted++;
                                } catch (Exception $e) {
                                    $errors++;
                                }
                            }
                        }
                    }

                    echo '<div class="alert success">';
                    echo '<i class="bi bi-check-circle me-2"></i>';
                    echo '<strong>✅ Import Complete!</strong><br>';
                    echo "Inserted: <strong>$inserted</strong> pricing slabs<br>";
                    if ($errors > 0) {
                        echo "Errors: <strong>$errors</strong><br>";
                    }
                    echo 'All zones (Within City, Within State, Metro, Rest of India) have been configured.';
                    echo '</div>';

                    // Show summary table
                    echo '<div class="pricing-table">';
                    echo '<h5 style="margin-top: 30px;">📊 Pricing Summary</h5>';
                    echo '<table class="table table-bordered" style="font-size: 12px;">';
                    echo '<thead style="background: #f0f2f9;">';
                    echo '<tr><th>Service Type</th><th>Zone</th><th>Weight Range</th><th>Base Price</th><th>Type</th></tr>';
                    echo '</thead><tbody>';

                    foreach ($pricingData as $serviceType => $slabs) {
                        foreach ($slabs as $slab) {
                            $weightFrom = $slab['weight_from'];
                            $weightTo = $slab['weight_to'] ? $slab['weight_to'] . 'kg' : '∞';
                            $type = $slab['increment_price'] ? 'Incremental' : 'Fixed';
                            $basePrice = $slab['base_price'];

                            echo "<tr>";
                            echo "<td><strong>$serviceType</strong></td>";
                            echo "<td>All Zones</td>";
                            echo "<td>{$weightFrom}kg - {$weightTo}</td>";
                            echo "<td>₹{$basePrice}</td>";
                            echo "<td><span class='status status-ok'>$type</span></td>";
                            echo "</tr>";
                        }
                    }

                    echo '</tbody></table>';
                    echo '</div>';

                    // Verification
                    echo '<div class="alert alert-info" style="margin-top: 30px;">';
                    echo '<i class="bi bi-info-circle me-2"></i>';
                    echo '<strong>Next Steps:</strong><br>';
                    echo '1. Go to <a href="admin/pricing.php">Admin → Pricing Management</a><br>';
                    echo '2. Verify slabs are showing in each zone<br>';
                    echo '3. Test Rate Calculator with pincodes<br>';
                    echo '4. Delete this file (import_pricing.php) for security<br>';
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="alert error">';
                    echo '<i class="bi bi-exclamation-circle me-2"></i>';
                    echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }
            ?>

            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e4e7f0; font-size: 12px; color: #666;">
                <h6>📋 Pricing Data Summary</h6>
                <div style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 10px;">
                    <strong>Standard Express:</strong><br>
                    • 0-250gm: ₹100 (fixed)<br>
                    • 250-500gm: ₹110 (fixed)<br>
                    • 500gm+: ₹60 + ₹60/kg<br>
                    <br>
                    <strong>Premium Express:</strong><br>
                    • 0-250gm: ₹245 (fixed)<br>
                    • 250-500gm: ₹260 (fixed)<br>
                    • 500gm+: ₹100 + ₹100/kg<br>
                    <br>
                    <strong>Air Cargo:</strong><br>
                    • No pricing configured (add as needed)<br>
                    <br>
                    <strong>Surface Cargo:</strong><br>
                    • 2.0-2.5kg: ₹225 (fixed)<br>
                    • 3.0kg+: ₹75/kg (incremental)<br>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; color: #666; font-size: 12px;">
        <p>After import, delete this file: <code>import_pricing.php</code></p>
        <p><a href="admin/pricing.php" style="color: #001A93; text-decoration: none; font-weight: 600;">Go to Pricing Management →</a></p>
    </div>
</div>
</body>
</html>
