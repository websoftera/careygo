<?php
require_once __DIR__ . '/../config/database.php';
$newCols = [
    'chargeable_weight' => 'DECIMAL(8,3) DEFAULT 0',
    'packing_charge'    => 'DECIMAL(10,2) DEFAULT 0',
    'photo_address'     => 'VARCHAR(255) DEFAULT NULL',
    'photo_parcel'      => 'VARCHAR(255) DEFAULT NULL',
];
foreach ($newCols as $col => $def) {
    try {
        echo "Adding $col... ";
        $pdo->exec("ALTER TABLE shipments ADD COLUMN `$col` $def");
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}
