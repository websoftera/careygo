<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM pricing_slabs WHERE service_type = 'air_cargo'");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
