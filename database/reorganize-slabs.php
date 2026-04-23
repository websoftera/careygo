<?php
/**
 * Reorganize weight slabs to standard logistics progression
 *
 * Standard weight slab tiers:
 * 1. 0.000-0.250 kg (first 250g)
 * 2. 0.250-0.500 kg (next 250g)
 * 3. 0.500-1.000 kg (next 500g)
 * 4. 1.000-2.000 kg (next 1kg)
 * 5. 2.000-5.000 kg (next 3kg)
 * 6. 5.000+ kg (open-ended with increment_per_kg for additional weight)
 *
 * Pricing strategy:
 * - Tiers 1-5: Fixed base_price (each tier may have different pricing)
 * - Tier 6: increment_price + increment_per_kg for weights above 5kg
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Weight Slab Reorganization - Standard 6-Tier System\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // ══════════════════════════════════════════════════════════════════════════════
    // STEP 1: Backup current slabs
    // ══════════════════════════════════════════════════════════════════════════════

    echo "[1/5] Creating backup...\n";
    $backupTable = "pricing_slabs_backup_" . date('YmdHis');
    $pdo->exec("CREATE TABLE {$backupTable} LIKE pricing_slabs");
    $pdo->exec("INSERT INTO {$backupTable} SELECT * FROM pricing_slabs");
    echo "✓ Backup created as '{$backupTable}'\n\n";

    // ══════════════════════════════════════════════════════════════════════════════
    // STEP 2: Extract current pricing from existing slabs
    // ══════════════════════════════════════════════════════════════════════════════

    echo "[2/5] Analyzing current pricing structure...\n";

    $stmt = $pdo->query("
        SELECT DISTINCT service_type, weight_from, base_price, increment_price, increment_per_kg
        FROM pricing_slabs
        ORDER BY service_type, weight_from
    ");
    $current = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Current slabs by service type:\n";
    $servicePricing = [];
    foreach ($current as $slab) {
        $stype = $slab['service_type'];
        if (!isset($servicePricing[$stype])) {
            $servicePricing[$stype] = [];
        }
        $servicePricing[$stype][] = [
            'weight_from' => (float)$slab['weight_from'],
            'base_price' => (float)$slab['base_price'],
            'increment_price' => (float)($slab['increment_price'] ?? 0),
            'increment_per_kg' => (float)($slab['increment_per_kg'] ?? 0.5)
        ];
    }

    foreach ($servicePricing as $stype => $prices) {
        echo "  • {$stype}: " . count($prices) . " unique price points\n";
    }
    echo "\n";

    // ══════════════════════════════════════════════════════════════════════════════
    // STEP 3: Define standard 6-tier structure with reasonable defaults
    // ══════════════════════════════════════════════════════════════════════════════

    echo "[3/5] Creating standard 6-tier structure...\n";

    // Get sample pricing for each service to use as basis
    $basePricing = [];
    foreach ($servicePricing as $stype => $prices) {
        // Use the lowest price point as the base for tier 1
        $basePricing[$stype] = [
            'tier1' => $prices[0]['base_price'],  // 0-0.250kg
            'increment' => isset($prices[count($prices)-1]) ? $prices[count($prices)-1]['increment_price'] : 60
        ];
    }

    // Standard 6-tier configuration
    $standardTiers = [
        ['weight_from' => 0.000, 'weight_to' => 0.250, 'name' => 'Tier 1: 0-250g'],
        ['weight_from' => 0.250, 'weight_to' => 0.500, 'name' => 'Tier 2: 250-500g'],
        ['weight_from' => 0.500, 'weight_to' => 1.000, 'name' => 'Tier 3: 500g-1kg'],
        ['weight_from' => 1.000, 'weight_to' => 2.000, 'name' => 'Tier 4: 1-2kg'],
        ['weight_from' => 2.000, 'weight_to' => 5.000, 'name' => 'Tier 5: 2-5kg'],
        ['weight_from' => 5.000, 'weight_to' => NULL,  'name' => 'Tier 6: 5kg+'],
    ];

    // ══════════════════════════════════════════════════════════════════════════════
    // STEP 4: Clear and rebuild slabs
    // ══════════════════════════════════════════════════════════════════════════════

    echo "[4/5] Rebuilding slabs with standard tiers...\n";

    $pdo->exec("DELETE FROM pricing_slabs");

    $insertStmt = $pdo->prepare("
        INSERT INTO pricing_slabs (
            service_type, zone, weight_from, weight_to,
            base_price, increment_price, increment_per_kg, sort_order
        ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
    ");

    $inserted = 0;
    // NOTE: air_cargo excluded intentionally — admin must add rates manually in the admin panel
    $services = ['standard', 'premium', 'surface'];

    foreach ($services as $serviceIndex => $stype) {
        foreach ($standardTiers as $tierIndex => $tier) {
            // Calculate pricing for this tier
            // Tier 1 uses base_price, subsequent tiers increase slightly
            $basePrice = $basePricing[$stype]['tier1'] ?? 100;
            $priceForTier = $basePrice + ($tierIndex * 10);  // Increment by ₹10 per tier
            $incrementPrice = $basePricing[$stype]['increment'] ?? 60;

            // For tier 6 (open-ended), set increment_per_kg
            $incrementPerKg = ($tier['weight_to'] === NULL) ? 0.250 : 0.000;

            $insertStmt->execute([
                $stype,
                $tier['weight_from'],
                $tier['weight_to'],
                $priceForTier,
                $tier['weight_to'] === NULL ? $incrementPrice : 0,
                $incrementPerKg,
                $tierIndex + 1  // sort_order
            ]);

            $inserted++;
            echo "✓ {$stype} - {$tier['name']}: ₹{$priceForTier}\n";
        }
        echo "\n";
    }

    echo "Inserted: {$inserted} slabs\n\n";

    // ══════════════════════════════════════════════════════════════════════════════
    // STEP 5: Verify new structure
    // ══════════════════════════════════════════════════════════════════════════════

    echo "[5/5] Verifying new structure...\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM pricing_slabs");
    $totalSlabs = $stmt->fetchColumn();
    echo "✓ Total slabs: {$totalSlabs}\n";

    $stmt = $pdo->query("
        SELECT service_type, COUNT(*) as count
        FROM pricing_slabs
        GROUP BY service_type
    ");
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nSlabs per service:\n";
    foreach ($counts as $count) {
        echo "  • {$count['service_type']}: {$count['count']} tiers\n";
    }

    // Show sample prices
    echo "\nSample pricing (Standard service):\n";
    $stmt = $pdo->query("
        SELECT weight_from, weight_to, base_price, increment_price, increment_per_kg
        FROM pricing_slabs
        WHERE service_type = 'standard'
        ORDER BY weight_from
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $to_str = $row['weight_to'] === NULL ? '∞' : number_format($row['weight_to'], 3);
        echo "  • " . number_format($row['weight_from'], 3) . "-{$to_str}kg: ₹" .
             number_format($row['base_price'], 2);
        if ($row['weight_to'] === NULL) {
            echo " + ₹" . number_format($row['increment_price'], 2) . "/" .
                 number_format($row['increment_per_kg'], 3) . "kg";
        }
        echo "\n";
    }

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✓ Weight slab reorganization completed successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "⚠ Rolling back to backup...\n";

    try {
        $pdo->exec("DROP TABLE IF EXISTS pricing_slabs");
        $pdo->exec("ALTER TABLE {$backupTable} RENAME TO pricing_slabs");
        echo "✓ Rolled back successfully\n";
    } catch (Exception $rb) {
        echo "✗ Rollback failed: " . $rb->getMessage() . "\n";
    }
    exit(1);
}
