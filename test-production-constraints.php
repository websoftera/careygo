<?php
/**
 * Test production-ready pricing constraints
 * - Service weight limits
 * - Air Cargo availability
 * - Weight decimal precision
 * - Zone determination
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/helpers.php';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Production-Ready Pricing Constraints Test\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test cases
$testCases = [
    // Test 1: Standard Express weight limit (2kg max)
    [
        'name' => 'Test 1: Standard Express at 1.5kg (should work)',
        'weight' => 1.5,
        'pickup' => '110001',  // Delhi
        'delivery' => '110002',  // Delhi
        'expectedServices' => ['standard', 'premium', 'air_cargo', 'surface'],
    ],
    [
        'name' => 'Test 2: Standard Express at 2.0kg (max, should work)',
        'weight' => 2.0,
        'pickup' => '110001',
        'delivery' => '110002',
        'expectedServices' => ['standard', 'premium', 'air_cargo', 'surface'],
    ],
    [
        'name' => 'Test 3: Standard Express at 2.1kg (exceeds 2kg limit)',
        'weight' => 2.1,
        'pickup' => '110001',
        'delivery' => '110002',
        'expectedServices' => ['premium', 'air_cargo', 'surface'],  // standard excluded
    ],
    [
        'name' => 'Test 4: Premium Express at 5.0kg (max)',
        'weight' => 5.0,
        'pickup' => '110001',
        'delivery' => '110002',
        'expectedServices' => ['premium', 'air_cargo', 'surface'],  // standard excluded (>2kg)
    ],
    [
        'name' => 'Test 5: Premium Express at 5.1kg (exceeds)',
        'weight' => 5.1,
        'pickup' => '110001',
        'delivery' => '110002',
        'expectedServices' => ['air_cargo', 'surface'],  // standard, premium excluded
    ],
    [
        'name' => 'Test 6: Weight at 0.250kg (minimum of tier 2)',
        'weight' => 0.250,
        'pickup' => '110001',
        'delivery' => '110002',
        'expectedServices' => ['standard', 'premium', 'air_cargo', 'surface'],
    ],
    [
        'name' => 'Test 7: Weight at 0.001kg (minimum weight)',
        'weight' => 0.001,
        'pickup' => '110001',
        'delivery' => '110002',
        'expectedServices' => ['standard', 'premium', 'air_cargo', 'surface'],
    ],
];

$testsPassed = 0;
$testsFailed = 0;

foreach ($testCases as $test) {
    echo "▸ {$test['name']}\n";

    // Call pricing API
    $url = "http://localhost/careygo/api/pricing.php?";
    $url .= "weight=" . urlencode($test['weight']);
    $url .= "&pickup=" . urlencode($test['pickup']);
    $url .= "&delivery=" . urlencode($test['delivery']);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!$response) {
        echo "  ✗ FAILED: Could not reach API\n\n";
        $testsFailed++;
        continue;
    }

    $data = json_decode($response, true);
    if (!$data || !$data['success']) {
        echo "  ✗ FAILED: API error - " . ($data['message'] ?? 'Unknown error') . "\n\n";
        $testsFailed++;
        continue;
    }

    $returnedServices = array_column($data['services'], 'type');
    $returnedServices = array_unique($returnedServices);
    sort($returnedServices);
    $expected = $test['expectedServices'];
    sort($expected);

    if ($returnedServices === $expected) {
        echo "  ✓ PASSED: " . implode(', ', $returnedServices) . "\n";
        echo "    Weight: {$test['weight']}kg, Zone: {$data['zone']}\n";
        echo "    Prices: ";
        foreach ($data['services'] as $svc) {
            echo "{$svc['type']}=₹" . number_format($svc['price'], 2) . " ";
        }
        echo "\n\n";
        $testsPassed++;
    } else {
        echo "  ✗ FAILED: Expected [" . implode(', ', $expected) . "]\n";
        echo "    Got [" . implode(', ', $returnedServices) . "]\n\n";
        $testsFailed++;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// Zone Determination Tests
// ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Zone Determination Tests\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$zoneTests = [
    ['Delhi', 'DELHI', 'Delhi', 'DELHI', 'within_city'],
    ['Mumbai', 'MAHARASHTRA', 'Thane', 'MAHARASHTRA', 'within_state'],
    ['Delhi', 'DELHI', 'Mumbai', 'MAHARASHTRA', 'metro'],
    ['Pune', 'MAHARASHTRA', 'Nagpur', 'MAHARASHTRA', 'within_state'],
];

foreach ($zoneTests as [$pCity, $pState, $dCity, $dState, $expectedZone]) {
    $zone = determineZone($pCity, $pState, $dCity, $dState);
    $status = ($zone === $expectedZone) ? '✓' : '✗';
    echo "{$status} {$pCity}, {$pState} → {$dCity}, {$dState}: {$zone}\n";
    if ($zone !== $expectedZone) {
        echo "   Expected: {$expectedZone}\n";
        $testsFailed++;
    } else {
        $testsPassed++;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// Weight Slab Verification
// ══════════════════════════════════════════════════════════════════════════════

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Weight Slab Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$stmt = $pdo->query("
    SELECT service_type, COUNT(*) as count
    FROM pricing_slabs
    GROUP BY service_type
");
$slabCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($slabCounts as $count) {
    $status = ($count['count'] === 6) ? '✓' : '✗';
    echo "{$status} {$count['service_type']}: {$count['count']} tiers (expected 6)\n";
    if ($count['count'] === 6) {
        $testsPassed++;
    } else {
        $testsFailed++;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// Final Summary
// ══════════════════════════════════════════════════════════════════════════════

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Test Summary\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$total = $testsPassed + $testsFailed;
$percentage = $total > 0 ? ($testsPassed / $total) * 100 : 0;

echo "Tests Passed:  ✓ {$testsPassed}\n";
echo "Tests Failed:  ✗ {$testsFailed}\n";
echo "Total Tests:   {$total}\n";
echo "Success Rate:  " . number_format($percentage, 1) . "%\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests PASSED! Production-ready constraints are working correctly.\n";
} else {
    echo "\n✗ Some tests FAILED. Review the errors above.\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
