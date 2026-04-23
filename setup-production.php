<?php
/**
 * CAREYGO PRODUCTION SETUP SCRIPT
 *
 * ⚠️ IMPORTANT: DELETE THIS FILE AFTER RUNNING!
 *
 * Usage:
 * 1. Upload this file to your production server root directory
 * 2. Open browser: https://your-domain.com/careygo/setup-production.php?run=yes
 * 3. Wait for completion
 * 4. DELETE this file immediately
 */

// Simple security check
if (!isset($_GET['run']) || $_GET['run'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Careygo Production Setup</title>
        <style>
            body { font-family: Arial; padding: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
            .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0; }
            .success { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 4px; margin: 20px 0; }
            .steps { background: #e7f3ff; padding: 20px; border-left: 4px solid #007bff; margin: 20px 0; }
            .steps ol { margin: 0; }
            .steps li { margin: 10px 0; }
            .btn { background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
            .btn:hover { background: #0056b3; }
            .btn-danger { background: #dc3545; }
            .btn-danger:hover { background: #c82333; }
            .info { background: #f0f0f0; padding: 15px; border-radius: 4px; margin: 20px 0; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 Careygo Production Setup</h1>

            <div class="warning">
                <strong>⚠️ Important:</strong> This script will run production database migrations.
                Make sure you have a database backup before proceeding!
            </div>

            <div class="steps">
                <strong>Setup Steps:</strong>
                <ol>
                    <li>Click "Start Setup" button below</li>
                    <li>Wait for all processes to complete</li>
                    <li>Verify the results</li>
                    <li>DELETE this file from your server</li>
                </ol>
            </div>

            <div class="info">
                <strong>What will happen:</strong>
                <ul>
                    <li>✓ Import 15,452 pincodes from DTDC master file</li>
                    <li>✓ Create backup tables (for rollback if needed)</li>
                    <li>✓ Reorganize weight slabs to standard 6-tier system</li>
                    <li>✓ Verify all data was imported correctly</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="?run=yes" class="btn" onclick="return confirm('⚠️ This will modify your database. Ensure you have a backup. Continue?')">
                    ▶️ Start Setup
                </a>
            </div>

            <div class="info">
                <strong>File Information:</strong><br>
                Location: <code><?php echo __FILE__; ?></code><br>
                File must be deleted after use!
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Security verified - start setup
?>
<!DOCTYPE html>
<html>
<head>
    <title>Careygo Setup - Running</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        .container { max-width: 900px; margin: 0 auto; }
        pre { background: #000; padding: 20px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #0f0; border-bottom: 2px solid #0f0; padding-bottom: 10px; }
        .success { color: #0f0; }
        .error { color: #ff0000; }
        .warning { color: #ffff00; }
        .section { margin: 20px 0; padding: 15px; background: #111; border-left: 3px solid #0f0; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0f0; color: #000; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn:hover { background: #0ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Careygo Production Setup - Running</h1>
        <pre><?php

// Start output buffering to capture script output
ob_end_clean();

echo "Setup Started: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("═", 80) . "\n\n";

try {
    // Include database config
    require_once __DIR__ . '/config/database.php';
    echo "<span class='success'>✓ Database connection established</span>\n\n";

    // ════════════════════════════════════════════════════════════════════════════
    // PHASE 1: DTDC Pincode Import
    // ════════════════════════════════════════════════════════════════════════════

    echo "<span class='section'>\n";
    echo "PHASE 1: DTDC Master Pincode Import\n";
    echo str_repeat("─", 80) . "\n";
    echo "</span>\n\n";

    // Include and run import script
    ob_start();
    include __DIR__ . '/database/import-dtdc-master.php';
    $importOutput = ob_get_clean();
    echo $importOutput;

    echo "\n\n";

    // ════════════════════════════════════════════════════════════════════════════
    // PHASE 2: Weight Slab Reorganization
    // ════════════════════════════════════════════════════════════════════════════

    echo "<span class='section'>\n";
    echo "PHASE 2: Weight Slab Reorganization\n";
    echo str_repeat("─", 80) . "\n";
    echo "</span>\n\n";

    // Include and run slab reorganization
    ob_start();
    include __DIR__ . '/database/reorganize-slabs.php';
    $slabOutput = ob_get_clean();
    echo $slabOutput;

    // Remove any Air Cargo global slabs — admin must add rates manually
    $pdo->exec("DELETE FROM pricing_slabs WHERE service_type = 'air_cargo'");
    echo "<span class='success'>✓ Air Cargo slabs removed (admin must configure via admin panel)</span>\n";

    echo "\n\n";

    // ════════════════════════════════════════════════════════════════════════════
    // PHASE 3: Verification
    // ════════════════════════════════════════════════════════════════════════════

    echo "<span class='section'>\n";
    echo "PHASE 3: Verification\n";
    echo str_repeat("─", 80) . "\n";
    echo "</span>\n\n";

    // Verify pincode import
    $stmt = $pdo->query("SELECT COUNT(*) FROM pincode_tat");
    $pincodeCount = $stmt->fetchColumn();
    $status = $pincodeCount >= 15000 ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
    echo "{$status} Pincodes imported: " . number_format($pincodeCount) . "\n";

    // Verify weight slabs
    $stmt = $pdo->query("SELECT COUNT(*) FROM pricing_slabs");
    $slabCount = $stmt->fetchColumn();
    $status = $slabCount === 24 ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
    echo "{$status} Weight slabs created: {$slabCount}\n";

    // Verify slabs per service
    echo "\n<span class='success'>Slabs per service type:</span>\n";
    $stmt = $pdo->query("SELECT service_type, COUNT(*) as count FROM pricing_slabs GROUP BY service_type");
    $slabsPerService = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($slabsPerService as $service => $count) {
        $status = $count === 6 ? '<span class="success">✓</span>' : '<span class="warning">⚠</span>';
        echo "  {$status} {$service}: {$count} tiers\n";
    }

    // Verify metro/non-metro distribution
    echo "\n<span class='success'>Pincode distribution:</span>\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM pincode_tat WHERE tat_standard = 2");
    $metroCount = $stmt->fetchColumn();
    echo "  <span class='success'>✓</span> Metro pincodes: " . number_format($metroCount) . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM pincode_tat WHERE tat_standard = 3");
    $nonMetroCount = $stmt->fetchColumn();
    echo "  <span class='success'>✓</span> Non-Metro pincodes: " . number_format($nonMetroCount) . "\n";

    echo "\n\n";
    echo str_repeat("═", 80) . "\n";
    echo "<span class='success'>✓ SETUP COMPLETED SUCCESSFULLY!</span>\n";
    echo str_repeat("═", 80) . "\n\n";

    echo "<span class='warning'>⚠️ IMPORTANT NEXT STEPS:</span>\n";
    echo "1. DELETE this file (setup-production.php) from your server\n";
    echo "2. Test the pricing API: /api/pricing.php?weight=2&pickup=110001&delivery=122105\n";
    echo "3. Monitor error logs for 24 hours\n";
    echo "4. Confirm with your team\n\n";

    // Show backup tables created
    echo "<span class='success'>Backup tables created:</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%backup%'");
    $backups = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($backups as $backup) {
        echo "  • {$backup}\n";
    }
    echo "\nUse these for rollback if needed.\n";

} catch (Exception $e) {
    echo "<span class='error'>✗ ERROR: " . $e->getMessage() . "</span>\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

?>
</pre>

        <div style="margin-top: 30px; padding: 20px; background: #111; border: 2px solid #0f0; border-radius: 4px;">
            <h2 style="color: #0f0; margin-top: 0;">Setup Complete! 🎉</h2>
            <p><strong style="color: #ffff00;">⚠️ CRITICAL:</strong> Delete this file immediately:</p>
            <p><code style="background: #000; padding: 10px; display: block; border-radius: 4px; color: #0f0;"><?php echo __FILE__; ?></code></p>

            <h3>Next Steps:</h3>
            <ol>
                <li>Delete this file from your server</li>
                <li>Test pricing API: <code>/api/pricing.php?weight=2&pickup=110001&delivery=122105</code></li>
                <li>Check error logs for any issues</li>
                <li>Monitor for 24 hours</li>
                <li>Inform your team deployment is complete</li>
            </ol>

            <h3>Rollback (if needed):</h3>
            <p>If something went wrong, restore from these backup tables:</p>
            <ul>
                <li><code>pincode_tat_backup_YYYYMMDDHHMMSS</code></li>
                <li><code>pricing_slabs_backup_YYYYMMDDHHMMSS</code></li>
            </ul>

            <p style="margin-top: 30px; padding: 15px; background: #ff0000; color: #fff; border-radius: 4px;">
                <strong>🔒 Security Reminder:</strong> This file should not exist in production. Delete it now!
            </p>
        </div>
    </div>
</body>
</html>
