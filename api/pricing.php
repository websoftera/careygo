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
 *   - If $zone is set, first look for slabs WHERE zone = $zone.
 *     If none found, fall back to slabs WHERE zone IS NULL (global).
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

    $slabs = [];

    // ── 1. Strict Zone Check ────────────────────────────────────────────────
    // If a zone is resolved, we ONLY look for pricing in that specific zone.
    // We NO LONGER fall back to NULL zones if the user has specified a location.
    if ($zone) {
        $stmt = $pdo->prepare(
            "SELECT * FROM pricing_slabs
             WHERE service_type = ? AND zone = ?
             $order"
        );
        $stmt->execute([$serviceType, $zone]);
        $slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    // ── 2. Fallback only for unknown locations ─────────────────────────────
    else {
        // If pincodes are missing or zone is unknown, we check for 'rest_of_india' or NULL slabs
        $stmt = $pdo->prepare(
            "SELECT * FROM pricing_slabs
             WHERE service_type = ? AND (zone IS NULL OR zone = 'rest_of_india')
             $order"
        );
        $stmt->execute([$serviceType]);
        $slabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── 3. Apply slab logic ──────────────────────────────────────────────────
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
    $zone = null;                                       
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

try {
    $serviceConstraints = [
        'standard'  => 2.000,
        'premium'   => 5.000,
        'air_cargo' => 10.000,
        'surface'   => 25.000,
    ];

    foreach ($serviceTypes as $type) {
        // 1. Weight constraint check
        $maxWeight = $serviceConstraints[$type] ?? PHP_FLOAT_MAX;
        if ($weight > $maxWeight) continue;

        // 2. Strict existence check: Does this service have ANY rates for the resolved zone?
        // If a zone is resolved, we only show it if a rate exists for that zone specifically.
        $checkStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM pricing_slabs 
             WHERE service_type = ? AND " . ($zone ? "zone = ?" : "(zone IS NULL OR zone = 'rest_of_india')")
        );
        $checkParams = $zone ? [$type, $zone] : [$type];
        $checkStmt->execute($checkParams);
        if (($checkStmt->fetchColumn() ?: 0) == 0) {
            continue; // No pricing added for this service in this zone
        }

        // 3. Calculate actual price
        $price = calculatePrice($weight, $type, $pdo, $zone);
        if ($price <= 0) continue;

        $tat      = $tatData[$type] ?? $defaultTat[$type];
        $eta      = addBusinessDays($tat, 'd M Y');
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
        'debug'    => [
            'resolved_zone' => $zone,
            'service_count' => count($services)
        ]
    ]);
} catch (Exception $e) {
    error_log('PRICING_ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    json_response([
        'success' => false,
        'message' => 'Pricing calculation failed: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ], 500);
}
