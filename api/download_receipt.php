<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/receipt_pdf.php';

// Check auth (can be either admin or customer)
$user = auth_user();
if (!$user) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

$tracking_no = $_GET['tracking_no'] ?? '';
if (!$tracking_no) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Tracking number is required';
    exit;
}

try {
    // Fetch shipment
    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare('SELECT s.*, u.email AS customer_email FROM shipments s JOIN users u ON u.id = s.customer_id WHERE s.tracking_no = ?');
        $stmt->execute([$tracking_no]);
    } else {
        $stmt = $pdo->prepare('SELECT s.*, u.email AS customer_email FROM shipments s JOIN users u ON u.id = s.customer_id WHERE s.tracking_no = ? AND s.customer_id = ?');
        $stmt->execute([$tracking_no, $user['sub']]);
    }
    
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$shipment) {
        header('HTTP/1.1 404 Not Found');
        echo 'Shipment not found';
        exit;
    }
    
    // Generate PDF
    $pdf = generateReceiptPDF($shipment);
    
    // Output inline so it opens in the browser
    $filename = 'Careygo_Receipt_' . $tracking_no . '.pdf';
    $pdf->Output('I', $filename);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error generating PDF: ' . $e->getMessage();
}
