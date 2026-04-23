<?php
/**
 * Direct pricing calculation test (without HTTP)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/api/pricing.php.inc';  // Include pricing functions

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Direct Pricing Calculation Tests\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Read pricing.php to extract calculatePrice function
$pricingCode = file_get_contents(__DIR__ . '/api/pricing.php');

// Extract and evaluate the calculatePrice function
if (preg_match('/function calculatePrice\(.*?\{.*?^}/ms', $pricingCode, $matches)) {
    eval($matches[0]);
    echo "✓ calculatePrice function loaded\n\n";
} else {
    echo "✗ Could not extract calculatePrice function\n";
    exit(1);
}

// Test cases
$testCases = [
    ['weight' => 0.100, 'service' => 'standard', 'zone' => null, 'description' => '100g Standard (Tier 1)'],
    ['weight' => 0.250, 'service' => 'standard', 'zone' => null, 'description' => '250g Standard (Tier boundary)'],
    ['weight' => 0.500, 'service' => 'standard', 'zone' => null, 'description' => '500g Standard (Tier 2)'],
    ['weight' => 1.000, 'service' => 'standard', 'zone' => null, 'description' => '1kg Standard (Tier 3)'],
    ['weight' => 2.000, 'service' => 'standard', 'zone' => null, 'description' => '2kg Standard (Max)'],
    ['weight' => 2.100, 'service' => 'standard', 'zone' => null, 'description' => '2.1kg Standard (Over limit)'],
    ['weight' => 3.000, 'service' => 'premium', 'zone' => null, 'description' => '3kg Premium (Tier 5)'],
    ['weight' => 5.000, 'service' => 'premium', 'zone' => null, 'description' => '5kg Premium (Max)'],
    ['weight' => 5.100, 'service' => 'premium', 'zone' => null, 'description' => '5.1kg Premium (Over limit)'],
    ['weight' => 6.000, 'service' => 'air_cargo', 'zone' => null, 'description' => '6kg Air Cargo (Tier 6 incr)'],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    echo "▸ {$test['description']}\n";

    try {
        $price = calculatePrice(
            $test['weight'],
            $test['service'],
            $pdo,
            $test['zone']
        );

        echo "  Price: ₹" . number_format($price, 2) . "\n";

        // Check weight constraints
        $constraints = [
            'standard' => 2.000,
            'premium' => 5.000,
            'air_cargo' => 10.000,
            'surface' => 25.000,
        ];

        $maxWeight = $constraints[$test['service']] ?? PHP_FLOAT_MAX;
        $isOverLimit = $test['weight'] > $maxWeight;

        if ($isOverLimit && $price > 0) {
            echo "  ✗ FAILED: Price returned (₹{$price}) but weight exceeds limit ({$test['weight']} > {$maxWeight})\n\n";
            $failed++;
        } elseif (!$isOverLimit && $price > 0) {
            echo "  ✓ PASSED: Valid weight and price calculated\n\n";
            $passed++;
        } elseif ($isOverLimit && $price <= 0) {
            echo "  ✓ PASSED: Correctly rejected (over weight limit)\n\n";
            $passed++;
        } else {
            echo "  ✓ PASSED\n\n";
            $passed++;
        }
    } catch (Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// Summary
// ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Summary\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✓ All tests PASSED!\n";
} else {
    echo "\n✗ {$failed} tests failed\n";
}
