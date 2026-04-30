<?php
/**
 * GET /api/pricing.php?weight=1.5&pickup=411001&delivery=600001
 * Returns pricing for all service types with TAT info and zone.
 *
 * Zone resolution order:
 *   1. Use ?zone= GET param if provided and valid
 *   2. Auto-detect from pickup + delivery pincode city/state data
 *   3. Fall back to NULL-zone (global) slabs if neither resolves
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

$validZones = ['within_city', 'within_state', 'metro', 'rest_of_india'];

/**
 * Calculate price for a service type using pricing_slabs table.
 *
 * Zone resolution:
 *   - If $zone is set, use only slabs WHERE zone = $zone.
 *   - If $zone is null, use slabs WHERE zone IS NULL only.
 *
 * Slab logic:
 *   - Fixed slab  (weight_to IS NOT NULL): if weight ≤ weight_to → base_price
 *   - Open slab   (weight_to IS NULL):     base_price + ceil((w − weight_from) / increment_per_kg) × increment_price
 */
function calculatePrice(float $weight, string $serviceType, PDO $pdo, ?string $zone = null): float
{
    $order = "ORDER BY CASE WHEN weight_to IS NULL THEN 1 ELSE 0 END ASC,
                       weight_to ASC, weight_from ASC";

    $targetZone = $zone ?: 'rest_of_india';

    if ($zone === null) {
        $stmt = $pdo->prepare(
            "SELECT * FROM pricing_slabs
                WHERE service_type = ? AND zone IS NULL
                $order"
        );
        $stmt->execute([$serviceType]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM pricing_slabs
                WHERE service_type = ? AND zone = ?
                $order"
        );
        $stmt->execute([$serviceType, $targetZone]);
    }
    $slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Apply slab logic ──────────────────────────────────────────────────
    foreach ($slabs as $slab) {
        $from = (float) $slab['weight_from'];
        $to   = $slab['weight_to'];

        if ($to !== null) {
            // Fixed-price slab
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

// ── Look up both pincodes ────────────────────────────────────────────────────
$pickupRow = null;
$tatRow    = null;

try {
    if ($pickup) {
        $stmt = $pdo->prepare("SELECT * FROM pincode_tat WHERE pincode = ? LIMIT 1");
        $stmt->execute([$pickup]);
        $pickupRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if ($delivery) {
        $stmt = $pdo->prepare("SELECT * FROM pincode_tat WHERE pincode = ? LIMIT 1");
        $stmt->execute([$delivery]);
        $tatRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Exception $e) {}

$frontendPickupCity = trim($_GET['pickup_city'] ?? '');
$frontendPickupState = trim($_GET['pickup_state'] ?? '');
$frontendDeliveryCity = trim($_GET['delivery_city'] ?? '');
$frontendDeliveryState = trim($_GET['delivery_state'] ?? '');

$pCity = $pickupRow['city'] ?? $frontendPickupCity;
$pState = $pickupRow['state'] ?? $frontendPickupState;
$dCity = $tatRow['city'] ?? $frontendDeliveryCity;
$dState = $tatRow['state'] ?? $frontendDeliveryState;

// ── Resolve zone ─────────────────────────────────────────────────────────────
$zoneParam = trim($_GET['zone'] ?? '');
if ($zoneParam && in_array($zoneParam, $validZones, true)) {
    $zone = $zoneParam;                                 
} elseif ($pickup !== '' && $pickup === $delivery) {
    $zone = 'within_city';                              
} elseif ($pCity !== '' && $dCity !== '' && $pState !== '' && $dState !== '') {
    $zone = determineZone($pCity, $pState, $dCity, $dState);
} else {
    $zone = 'rest_of_india'; // Fallback to rest_of_india instead of NULL
}

// ── TAT data ─────────────────────────────────────────────────────────────────
$tatData = [];
if ($tatRow) {
    $tatData = [
        'standard'  => (int) $tatRow['tat_standard'],
        'premium'   => (int) $tatRow['tat_premium'],
        'air_cargo' => (int) $tatRow['tat_air'],
        'surface'   => (int) $tatRow['tat_surface'],
    ];
}
$defaultTat = ['standard' => 3, 'premium' => 1, 'air_cargo' => 2, 'surface' => 5];

// ── Calculate prices ──────────────────────────────────────────────────────────
$serviceTypes = ['standard', 'premium', 'air_cargo', 'surface'];
$services = [];
$packingCharge = 0.0;

if (false) try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $settingStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $settingStmt->execute(['packing_charge']);
    $settingValue = $settingStmt->fetchColumn();
    if ($settingValue !== false && is_numeric($settingValue)) {
        $packingCharge = max(0.0, (float)$settingValue);
    }
} catch (Exception $e) {}

try {
    $serviceConstraints = [
        'standard'  => 2.000,
        'premium'   => 60.000,
        'air_cargo' => 60.000,
        'surface'   => 60.000,
    ];

    foreach ($serviceTypes as $type) {
        // 1. Weight constraint check
        $maxWeight = $serviceConstraints[$type] ?? PHP_FLOAT_MAX;
        if ($weight > $maxWeight) continue;

        // Calculate price only for the resolved zone. Missing zone pricing hides the service.
        $price = calculatePrice($weight, $type, $pdo, $zone);

        if ($price <= 0) continue;

        $tat      = $tatData[$type] ?? $defaultTat[$type];
        $eta      = addBusinessDays($tat, 'l, d M Y');
        $tatLabel = $tat === 1 ? '1 day' : "$tat days";

        $services[] = [
            'type'      => $type,
            'price'     => $price,
            'tat'       => $tat,
            'tat_label' => $tatLabel,
            'eta'       => $eta,
        ];
    }

    json_response([
        'success'  => true,
        'services' => $services,
        'weight'   => $weight,
        'zone'     => $zone,
        'packing_charge' => $packingCharge,
    ]);
} catch (Exception $e) {
    error_log('PRICING_ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    json_response([
        'success' => false,
        'message' => 'Pricing calculation failed: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ], 500);
}
