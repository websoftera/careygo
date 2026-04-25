<?php
require_once __DIR__ . '/../config/database.php';
try {
    $stmt = $pdo->query("DESCRIBE addresses");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} catch (Exception $e) { echo $e->getMessage(); }
