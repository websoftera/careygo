<?php
/**
 * Audit and reorganize weight slabs to standard logistics progression
 *
 * Standard weight slab progression:
 * - 0.000-0.250 kg
 * - 0.250-0.500 kg
 * - 0.500-1.000 kg
 * - 1.000-2.000 kg
 * - 2.000-5.000 kg
 * - 5.000+ kg (open-ended with increment_per_kg)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Weight Slab Audit & Reorganization\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ══════════════════════════════════════════════════════════════════════════════
// STEP 1: Audit current slabs
// ══════════════════════════════════════════════════════════════════════════════

echo "[1/4] Auditing current weight slabs...\n";

$stmt = $pdo->query("
    SELECT
        service_type,
        zone,
        COUNT(*) as slab_count,
        MIN(weight_from) as min_weight,
        MAX(weight_to) as max_weight,
        GROUP_CONCAT(CONCAT(weight_from, '-', weight_to) ORDER BY weight_from) as ranges
    FROM pricing_slabs
    GROUP BY service_type, zone
    ORDER BY service_type, zone
");

$slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($slabs) . " service+zone combinations:\n\n";

$issues = [];

foreach ($slabs as $slab) {
    $status = '✓';
    $issue = '';

    // Check for gaps in weight ranges
    if ($slab['min_weight'] > 0.001) {
        $status = '⚠';
        $issue = "Starts at {$slab['min_weight']}kg, should start at 0.000kg";
        $issues[] = $issue;
    }

    echo "{$status} {$slab['service_type']} / {$slab['zone']}: {$slab['slab_count']} slabs\n";
    if ($issue) echo "   ⚠ {$issue}\n";
}

echo "\n";

// ══════════════════════════════════════════════════════════════════════════════
// STEP 2: Check for missing ranges
// ══════════════════════════════════════════════════════════════════════════════

echo "[2/4] Checking for missing standard ranges...\n\n";

$standardRanges = [
    [0.000, 0.250, 'Tier 1: 0-250g'],
    [0.250, 0.500, 'Tier 2: 250-500g'],
    [0.500, 1.000, 'Tier 3: 500g-1kg'],
    [1.000, 2.000, 'Tier 4: 1-2kg'],
    [2.000, 5.000, 'Tier 5: 2-5kg'],
    [5.000, NULL,  'Tier 6: 5kg+'],
];

$missingRanges = [];

foreach ($slabs as $slab) {
    $key = "{$slab['service_type']}-{$slab['zone']}";

    echo "{$slab['service_type']} / {$slab['zone']}:\n";
    foreach ($standardRanges as [$from, $to, $label]) {
        // Check if this range exists in the slabs for this service+zone
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM pricing_slabs
            WHERE service_type = ? AND zone = ?
            AND weight_from = ? AND weight_to " . ($to === NULL ? "IS NULL" : "= ?")
        );

        $params = [$slab['service_type'], $slab['zone'], $from];
        if ($to !== NULL) $params[] = $to;

        $stmt->execute($params);
        $exists = $stmt->fetchColumn() > 0;

        $status = $exists ? '✓' : '✗';
        echo "  {$status} {$label}\n";

        if (!$exists) {
            $missingRanges[] = [
                'service_type' => $slab['service_type'],
                'zone' => $slab['zone'],
                'weight_from' => $from,
                'weight_to' => $to,
                'label' => $label
            ];
        }
    }
    echo "\n";
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 3: Show samples and detailed slab info
// ══════════════════════════════════════════════════════════════════════════════

echo "[3/4] Current slab details (first service+zone)...\n\n";

$stmt = $pdo->query("
    SELECT *
    FROM pricing_slabs
    LIMIT 1
");
$firstSlab = $stmt->fetch(PDO::FETCH_ASSOC);

if ($firstSlab) {
    $key = "{$firstSlab['service_type']}-{$firstSlab['zone']}";
    echo "Sample slabs for {$key}:\n";

    $stmt = $pdo->prepare("
        SELECT
            weight_from,
            weight_to,
            base_price,
            increment_price,
            increment_per_kg,
            sort_order
        FROM pricing_slabs
        WHERE service_type = ? AND zone = ?
        ORDER BY weight_from ASC
    ");
    $stmt->execute([$firstSlab['service_type'], $firstSlab['zone']]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($details as $detail) {
        $from = number_format($detail['weight_from'], 3);
        $to = $detail['weight_to'] === NULL ? '∞' : number_format($detail['weight_to'], 3);
        echo "  {$from} - {$to} kg: Base=₹{$detail['base_price']}, Incr=₹{$detail['increment_price']}/{$detail['increment_per_kg']}kg\n";
    }
}

echo "\n";

// ══════════════════════════════════════════════════════════════════════════════
// STEP 4: Summary
// ══════════════════════════════════════════════════════════════════════════════

echo "[4/4] Summary\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$totalSlabs = $pdo->query("SELECT COUNT(*) FROM pricing_slabs")->fetchColumn();
echo "Total weight slabs: {$totalSlabs}\n";
echo "Service+Zone combinations: " . count($slabs) . "\n";
echo "Missing standard ranges: " . count($missingRanges) . "\n";

if (count($missingRanges) > 0) {
    echo "\n⚠ MISSING RANGES (need to be added):\n";
    foreach ($missingRanges as $missing) {
        $to_str = $missing['weight_to'] === NULL ? '∞' : number_format($missing['weight_to'], 3);
        echo "  • {$missing['service_type']} / {$missing['zone']}: " .
             number_format($missing['weight_from'], 3) . " - {$to_str} kg\n";
    }
}

echo "\n";

if (count($missingRanges) === 0) {
    echo "✓ All standard weight ranges are present!\n";
} else {
    echo "⚠ Action required: Add missing weight ranges to comply with standard logistics progression\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
