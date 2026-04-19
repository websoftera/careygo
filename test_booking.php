<?php
/**
 * Test booking endpoint to debug the 500 error
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: application/json');

echo json_encode([
    'test' => 'Starting debug test',
    'php_version' => PHP_VERSION,
    'error_reporting' => error_reporting(),
], JSON_PRETTY_PRINT);

// Check if database connection works
try {
    $test = $pdo->query("SELECT 1");
    echo "\n✓ Database connection OK\n";
} catch (Exception $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
}

// Check if required functions exist
echo "\n✓ Function addBusinessDays exists: " . (function_exists('addBusinessDays') ? 'YES' : 'NO') . "\n";
echo "✓ Function tatColumn exists: " . (function_exists('tatColumn') ? 'YES' : 'NO') . "\n";

// Test addBusinessDays function
try {
    $result = addBusinessDays(3);
    echo "\n✓ addBusinessDays(3) = " . $result . "\n";
} catch (Exception $e) {
    echo "\n✗ addBusinessDays Error: " . $e->getMessage() . "\n";
}

// Check if shipments table exists
try {
    $result = $pdo->query("DESCRIBE shipments LIMIT 1");
    echo "✓ Shipments table exists\n";
} catch (Exception $e) {
    echo "✗ Shipments table error: " . $e->getMessage() . "\n";
}

// Check pricing_slabs table
try {
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM pricing_slabs");
    $count = $result->fetchColumn();
    echo "✓ Pricing slabs table exists, contains " . $count . " rows\n";
} catch (Exception $e) {
    echo "✗ Pricing slabs error: " . $e->getMessage() . "\n";
}

echo "\nAll checks complete.\n";
