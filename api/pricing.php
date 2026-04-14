<?php
/**
 * GET /api/pricing.php?weight=1.5&pickup=411001&delivery=600001
 * Returns pricing for all service types with TAT info
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

$weight   = (float) ($_GET['weight']   ?? 0);
$pickup   = trim($_GET['pickup']   ?? '');
$delivery = trim($_GET['delivery'] ?? '');

if ($weight <= 0) {
    json_response(['success' => false, 'message' => 'Invalid weight.'], 422);
}

/**
 * Calculate price for a service type using pricing_slabs table
 * Slab logic: iterate by weight_to ASC (NULL last).
 *   - Fixed slab (weight_to IS NOT NULL): if weight <= weight_to → base_price
 *   - Open slab (weight_to IS NULL): base_price + ceil((weight−weight_from) / increment_per_kg) × increment_price
 */
function calculatePrice(float $weight, string $serviceType, PDO $pdo): float
{
    $stmt = $pdo->prepare(
        "SELECT * FROM pricing_slabs
         WHERE service_type = ?
         ORDER BY CASE WHEN weight_to IS NULL THEN 1 ELSE 0 END ASC, weight_to ASC, weight_from ASC"
    );
    $stmt->execute([$serviceType]);
    $slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($slabs as $slab) {
        $from = (float) $slab['weight_from'];
        $to   = $slab['weight_to'];

        if ($to !== null) {
            // Fixed price slab
            if ($weight <= (float) $to) {
                return (float) $slab['base_price'];
            }
        } else {
            // Open-ended incremental slab
            $incPer = max(0.001, (float) $slab['increment_per_kg']);
            $extra  = max(0, $weight - $from);
            $blocks = (int) ceil($extra / $incPer);
            $inc    = ($slab['increment_price'] !== null) ? (float) $slab['increment_price'] : 0;
            return round((float) $slab['base_price'] + ($blocks * $inc), 2);
        }
    }

    return 0.0; // No matching slab
}

// Get TAT info for delivery pincode
$tatData = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM pincode_tat WHERE pincode = ? LIMIT 1");
    $stmt->execute([$delivery]);
    $tatRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tatRow) {
        $tatData = [
            'standard'  => (int) $tatRow['tat_standard'],
            'premium'   => (int) $tatRow['tat_premium'],
            'air_cargo' => (int) $tatRow['tat_air'],
            'surface'   => (int) $tatRow['tat_surface'],
        ];
    }
} catch (Exception $e) {}

// Default TAT if pincode not found
$defaultTat = ['standard' => 3, 'premium' => 1, 'air_cargo' => 2, 'surface' => 5];

$serviceTypes = ['standard', 'premium', 'air_cargo', 'surface'];
$services = [];

try {
    foreach ($serviceTypes as $type) {
        $price = calculatePrice($weight, $type, $pdo);
        if ($price <= 0) continue; // Skip if no pricing defined

        $tat   = $tatData[$type] ?? $defaultTat[$type];
        $eta   = addBusinessDays($tat, 'd M Y');

        $tatLabel = $tat === 1 ? '1 day' : "$tat days";

        $services[] = [
            'type'      => $type,
            'price'     => $price,
            'tat'       => $tat,
            'tat_label' => $tatLabel,
            'eta'       => $eta,
        ];
    }

    json_response(['success' => true, 'services' => $services, 'weight' => $weight]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Pricing calculation failed.'], 500);
}
