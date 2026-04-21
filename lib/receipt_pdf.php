<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fpdf/fpdf.php';

class ReceiptPDF extends FPDF
{
    function Header()
    {
        // Logo
        $logoPath = __DIR__ . '/../assets/images/Main-Careygo-logo-blue.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 8, 45); // Insert logo
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->SetTextColor(0, 26, 147);
            $this->SetXY(10, 8);
            $this->Cell(0, 10, 'CAREYGO LOGISTICS', 0, 1, 'L');
        }

        // Date Box
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.3);
        $this->Rect(140, 15, 60, 8);
        $this->Line(155, 15, 155, 23); // separator
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(140, 15);
        $this->Cell(15, 8, 'DATE', 0, 0, 'C');
        
        // Let date be filled later from shipment data
    }
    
    function redBox($x, $y, $num)
    {
        $this->SetFillColor(220, 53, 69); // #dc3545
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Rect($x, $y, 5, 5, 'F');
        $this->SetXY($x, $y);
        $this->Cell(5, 5, $num, 0, 0, 'C');
        $this->SetTextColor(0, 0, 0); // reset
    }

    function checkbox($x, $y, $checked=false, $label='', $size=4)
    {
        $this->SetDrawColor(100, 100, 100);
        $this->Rect($x, $y, $size, $size);
        if ($checked) {
            $this->SetFont('Arial', 'B', $size*1.5);
            $this->SetTextColor(0,0,0);
            $this->SetXY($x, $y - ($size * 0.1));
            $this->Cell($size, $size, 'x', 0, 0, 'C');
        }
        if ($label) {
            $this->SetFont('Arial', '', 8);
            $this->SetXY($x + $size + 2, $y);
            $this->Cell(20, $size, $label, 0, 0, 'L');
        }
    }
}

function generateReceiptPDF($shipment)
{
    $pdf = new ReceiptPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false); // We draw manually

    // Fill the Date in the header
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(155, 15);
    $pdf->Cell(45, 8, date('d-M-Y', strtotime($shipment['created_at'])), 0, 0, 'C');

    // Outer Border
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(10, 30, 190, 230); // 10 to 200 (W), 30 to 260 (H)
    
    // Middle vertical line (excluding top and bottom sections)
    $pdf->Line(105, 38, 105, 220); // x=105

    // Row 1: Disclaimers (y: 30 to 38)
    $pdf->Line(10, 38, 200, 38);
    $pdf->SetXY(11, 31);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(94, 6, 'Non Negotiable Consignment Note / Subject to Bengaluru Jurisdiction.', 0, 0, 'L');
    
    $pdf->SetXY(106, 31);
    $pdf->SetFont('Arial', '', 6);
    $disclaimer2 = "The Consignment note is not a tax invoice. A tax invoice will be made eligible for Careygo or its channel partner as the case may be urgent request.";
    $pdf->MultiCell(93, 3, $disclaimer2, 0, 'L');

    // Row 2: Addresses (y: 38 to 98) = height 60
    $pdf->Line(10, 98, 200, 98);
    
    // Box 1 (Pickup)
    $pdf->redBox(10, 38, '1');
    $pdf->SetFont('Arial', '', 8);
    
    $y = 45;
    $pdf->SetXY(12, $y);
    $pdf->Cell(35, 5, "Sender's [Consigner] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(55, 5, htmlspecialchars($shipment['pickup_name']), 'B'); // Name line
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, $y+=8);
    $pdf->Cell(35, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(55, 5, htmlspecialchars($shipment['pickup_company_name'] ?? '-'), 'B');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, $y+=8);
    $pdf->Cell(35, 5, "Phone:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(55, 5, htmlspecialchars($shipment['pickup_phone']), 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, $y+=8);
    $pdf->Cell(15, 5, "Address:");
    $pdf->SetFont('Arial', 'B', 8);
    $addr1 = htmlspecialchars($shipment['pickup_address']);
    $pdf->Cell(75, 5, substr($addr1, 0, 50), 'B');
    if (strlen($addr1) > 50) {
        $pdf->SetXY(12, $y+=6);
        $pdf->Cell(15, 5, "");
        $pdf->Cell(75, 5, substr($addr1, 50, 50), 'B');
    }

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, $y+=8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, htmlspecialchars($shipment['pickup_city']), 'B');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(9, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, htmlspecialchars($shipment['pickup_state']), 'B');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(13, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(15, 5, htmlspecialchars($shipment['pickup_pincode']), 'B');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, $y+=8);
    $pdf->Cell(25, 5, "Sender's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(35, 5, htmlspecialchars($shipment['pickup_gstin'] ?? '-'), 'B');
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(30, 5, "*Where Applicable", 0, 0, 'R');

    // Box 2 (Delivery)
    $pdf->redBox(105, 38, '2');
    $pdf->SetFont('Arial', '', 8);
    
    $y = 45;
    $x = 107;
    $pdf->SetXY($x, $y);
    $pdf->Cell(38, 5, "Recipient's [Consignee] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(52, 5, htmlspecialchars($shipment['delivery_name']), 'B');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=8);
    $pdf->Cell(38, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(52, 5, htmlspecialchars($shipment['delivery_company_name'] ?? '-'), 'B');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=8);
    $pdf->Cell(38, 5, "Phone:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(52, 5, htmlspecialchars($shipment['delivery_phone']), 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=8);
    $pdf->Cell(15, 5, "Address:");
    $pdf->SetFont('Arial', 'B', 8);
    $addr2 = htmlspecialchars($shipment['delivery_address']);
    $pdf->Cell(75, 5, substr($addr2, 0, 50), 'B');
    if (strlen($addr2) > 50) {
        $pdf->SetXY($x, $y+=6);
        $pdf->Cell(15, 5, "");
        $pdf->Cell(75, 5, substr($addr2, 50, 50), 'B');
    }

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, htmlspecialchars($shipment['delivery_city']), 'B');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(9, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, htmlspecialchars($shipment['delivery_state']), 'B');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(13, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(15, 5, htmlspecialchars($shipment['delivery_pincode']), 'B');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=8);
    $pdf->Cell(28, 5, "Recipient's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(32, 5, htmlspecialchars($shipment['delivery_gstin'] ?? '-'), 'B');
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(30, 5, "*Where Applicable", 0, 0, 'R');

    // Row 3: Pcs / Weight (Left) & Description (Right) - y: 98 to 113
    $pdf->Line(10, 113, 200, 113);
    
    // Box 3
    $pdf->redBox(10, 98, '3');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, 105);
    $pdf->Cell(25, 5, 'Total Num Pcs:');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(20, 5, $shipment['pieces'], 'B');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(20, 5, 'Total Weight:');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(20, 5, number_format($shipment['weight'], 3) . ' kg', 'B');

    // Box 4 Top (Description)
    $pdf->redBox(105, 98, '4');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(107, 105);
    $pdf->Cell(30, 5, 'Description of Content:');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(60, 5, htmlspecialchars(substr($shipment['description']?:'NO DESCRIPTION', 0, 45)), 'B');

    // Row 4: Enclosures (Left) & Total Value (Right) - y: 113 to 128
    $pdf->Line(10, 128, 200, 128);
    
    // Box 5
    $pdf->redBox(10, 113, '5');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, 115);
    $pdf->Cell(40, 5, 'Paper Work Enclosures');
    
    $hasInvoice = $shipment['gst_invoice'] == 1;
    $hasEway = !empty($shipment['ewaybill_no']);
    $isNA = (!$hasInvoice && !$hasEway);
    
    $pdf->SetXY(16, 122);
    $pdf->checkbox(25, 122, $isNA, 'NA');
    $pdf->checkbox(45, 122, $hasInvoice, 'Invoice');
    $pdf->checkbox(75, 122, $hasEway, 'E-Way bill');

    // Box 4 Bottom
    // Value text
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(107, 118);
    $pdf->Cell(65, 5, 'Total Value of Consignment for carriage/E-Way bill:');
    
    // Rs Box
    $pdf->Line(172, 113, 172, 128); // Vertical line separating the box
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(175, 116);
    $pdf->Cell(20, 10, 'Rs. ' . number_format($shipment['declared_value'], 2), 0, 0, 'C');

    // Row 5: Total/Mode (Left) & Mode/Barcode (Right) - y: 128 to 158
    $pdf->Line(10, 158, 200, 158);
    $pdf->Line(10, 143, 105, 143); // Split Left Box 7
    
    // Box 7 (Total Amount)
    $pdf->redBox(10, 128, '7');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(15, 134);
    $pdf->Cell(25, 5, 'Total Amount:');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(50, 5, 'Rs. ' . number_format($shipment['final_price'], 2), 0, 0, 'L');
    
    // Box 7 (Payment Mode)
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, 145);
    $pdf->Cell(40, 5, 'Mode of Payment');
    
    $isCash = ($shipment['payment_method'] === 'cod');
    $isUpi  = ($shipment['payment_method'] === 'prepaid'); // Assume UPI for prepaid on receipt
    $pdf->checkbox(22, 152, $isCash, 'Cash');
    $pdf->checkbox(45, 152, $isUpi, 'UPI');

    // Box 6 (Delivery Mode / Consignment Barcode)
    $pdf->redBox(105, 128, '6');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(110, 131);
    $pdf->Cell(12, 5, 'Mode: [   ]');
    
    $isSurf = strpos($shipment['service_type'], 'surface') !== false;
    $isAir = strpos($shipment['service_type'], 'air') !== false;
    $isExp = strpos($shipment['service_type'], 'standard') !== false || strpos($shipment['service_type'], 'premium') !== false;
    
    $pdf->checkbox(140, 131, $isSurf, 'Surface');
    $pdf->checkbox(165, 131, $isAir, 'Air Cargo');
    $pdf->checkbox(190, 131, $isExp, 'Express');

    $pdf->SetXY(110, 140);
    $pdf->Cell(30, 5, 'Consignment Number:');
    
    // Mock Barcode
    $pdf->SetFont('Courier', 'B', 16);
    $pdf->SetXY(140, 145);
    $pdf->Cell(50, 6, '| |' . $shipment['tracking_no'] . '|', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(140, 151);
    $pdf->Cell(50, 4, $shipment['tracking_no'], 0, 0, 'C');

    // Row 6: Risk (Left) & Sign (Right) - y: 158 to 220
    // Box 8 (Risk Surcharge)
    $pdf->redBox(10, 158, '8');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(15, 185);
    $pdf->Cell(40, 5, 'Risk Surcharge', 0, 0, 'L');
    
    $pdf->Line(55, 158, 55, 220); // vertical split inside Left box
    $pdf->Line(80, 158, 80, 220); // vertical split inside Left box
    $pdf->Line(55, 189, 105, 189); // horizontal split for owner/carrier

    // Owner row
    $pdf->SetXY(55, 168);
    $pdf->Cell(25, 10, 'Owner', 0, 0, 'C');
    // Checkbox inside
    $isOwner = (!isset($shipment['risk_surcharge']) || $shipment['risk_surcharge'] === 'owner');
    $pdf->checkbox(85, 171, $isOwner, '', 6);
    
    // Carrier row
    $pdf->SetXY(55, 199);
    $pdf->Cell(25, 10, 'Carrier', 0, 0, 'C');
    $isCarrier = (isset($shipment['risk_surcharge']) && $shipment['risk_surcharge'] === 'carrier');
    $pdf->checkbox(85, 202, $isCarrier, '', 6);

    // Box 9 (Signature & OTP)
    $pdf->redBox(105, 158, '9');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(145, 162);
    $pdf->Cell(50, 5, "Sender's Signature & Seal:", 0, 0, 'C');
    
    // Signature Box
    $pdf->SetDrawColor(0,0,0);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(145, 168, 50, 22, 'D'); // Signature box with border radius not supported easily, use rect

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(110, 180);
    $pdf->Cell(10, 5, 'OTP: _________________');
    
    // Date & Time
    $dateCreated = strtotime($shipment['created_at']);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(110, 195);
    $pdf->Cell(15, 5, 'Date:');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(25, 5, date('d-m-Y', $dateCreated), 'B');
    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(15, 5, 'Time:');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(20, 5, date('h:i', $dateCreated), 'B');
    $pdf->Cell(10, 5, date('A', $dateCreated));

    $pdf->SetXY(110, 205);
    $pdf->SetFont('Arial', '', 7);
    $termsTxt = "I have read and understood terms & conditions printed overleaf of this consignment note and I agree to the same.";
    $pdf->MultiCell(85, 3.5, $termsTxt, 0, 'L');

    // Bottom Footer area
    // Blue band
    $pdf->SetFillColor(0, 26, 147); // #001A93
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Rect(10, 220, 190, 10, 'F');
    $pdf->SetXY(15, 222);
    $pdf->Cell(60, 6, 'www.careygo.in', 0, 0, 'L');
    $pdf->Cell(60, 6, 'customersupport@careygo.in', 0, 0, 'C');
    $pdf->Cell(50, 6, '+91 87804 06230', 0, 0, 'R');

    // Terms
    $pdf->SetXY(10, 235);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 6, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 8);
    $tc = "1. I/We declare that this consignment does not contain personal mail, cash, jewellery, contraband, illegal drugs, any prohibited items and commodities which can cause safety hazards while transporting.";
    $pdf->MultiCell(190, 4, $tc, 0, 'L');

    return $pdf;
}
