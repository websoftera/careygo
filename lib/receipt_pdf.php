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
    }
    
    function checkbox($x, $y, $checked=false, $label='', $size=4)
    {
        $this->SetDrawColor(100, 100, 100);
        $this->Rect($x, $y, $size, $size);
        if ($checked) {
            $this->SetFont('Arial', 'B', $size*1.2);
            $this->SetTextColor(0,0,0);
            $this->SetXY($x, $y);
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
    $pdf->SetAutoPageBreak(false);

    // Header Date
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(155, 15);
    $pdf->Cell(45, 8, date('d-M-Y', strtotime($shipment['created_at'])), 0, 0, 'C');

    // Outer Border
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(10, 30, 190, 230); // Width 190, Height 230
    
    // Middle vertical split
    $pdf->Line(105, 38, 105, 220); // x=105

    // Row 1: Disclaimers (y: 30 to 38)
    $pdf->Line(10, 38, 200, 38);
    $pdf->SetXY(11, 31);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(94, 6, 'Non Negotiable Consignment Note / Subject to Bengaluru Jurisdiction.', 0, 0, 'L');
    
    $pdf->SetXY(106, 31);
    $pdf->SetFont('Arial', '', 6);
    $disclaimer2 = "The Consignment note is not a tax invoice. A tax invoice will be made eligible for Careygo or its\nchannel partner as the case may be urgent request.";
    $pdf->MultiCell(93, 3, $disclaimer2, 0, 'L');

    // Row 2: Addresses (y: 38 to 98) -> Height 60
    $pdf->Line(10, 98, 200, 98);
    
    // ----------- BOX 1 (Pickup) -----------
    $y = 45;
    $x = 12;
    $lineH = 8;
    
    // Sender Name
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(42, 5, "Sender's [Consigner] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(50, 5, ' ' . htmlspecialchars(substr($shipment['pickup_name'],0,28)), 'B', 0, 'L');

    // Company Name
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(67, 5, ' ' . htmlspecialchars(substr($shipment['pickup_company_name'] ?? '',0,35)), 'B', 0, 'L');

    // Phone
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(12, 5, "Phone:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(35, 5, ' ' . htmlspecialchars($shipment['pickup_phone']), 'B', 0, 'L');

    // Address
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(15, 5, "Address:");
    $pdf->SetFont('Arial', 'B', 8);
    $addr1 = htmlspecialchars($shipment['pickup_address']);
    $pdf->Cell(77, 5, ' ' . substr($addr1, 0, 48), 'B', 0, 'L');
    
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    $pdf->Cell(77, 5, ' ' . (strlen($addr1)>48 ? substr($addr1, 48, 48) : ''), 'B', 0, 'L');

    // City/State/PIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, ' ' . htmlspecialchars($shipment['pickup_city']), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(10, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, ' ' . htmlspecialchars($shipment['pickup_state']), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(14, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(15, 5, ' ' . htmlspecialchars($shipment['pickup_pincode']), 'B', 0, 'C');

    // GSTIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Sender's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(35, 5, ' ' . htmlspecialchars($shipment['pickup_gstin'] ?? ''), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(32, 5, "*Where Applicable", 0, 0, 'R');

    // ----------- BOX 2 (Delivery) -----------
    $y = 45;
    $x = 107;
    
    // Recipient Name
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(45, 5, "Recipient's [Consignee] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(46, 5, ' ' . htmlspecialchars(substr($shipment['delivery_name'],0,25)), 'B', 0, 'L');

    // Company Name
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(66, 5, ' ' . htmlspecialchars(substr($shipment['delivery_company_name'] ?? '',0,35)), 'B', 0, 'L');

    // Phone
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(12, 5, "Phone:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(35, 5, ' ' . htmlspecialchars($shipment['delivery_phone']), 'B', 0, 'L');

    // Address
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(15, 5, "Address:");
    $pdf->SetFont('Arial', 'B', 8);
    $addr2 = htmlspecialchars($shipment['delivery_address']);
    $pdf->Cell(76, 5, ' ' . substr($addr2, 0, 48), 'B', 0, 'L');
    
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    $pdf->Cell(76, 5, ' ' . (strlen($addr2)>48 ? substr($addr2, 48, 48) : ''), 'B', 0, 'L');

    // City/State/PIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, ' ' . htmlspecialchars($shipment['delivery_city']), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(10, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, ' ' . htmlspecialchars($shipment['delivery_state']), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(14, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(14, 5, ' ' . htmlspecialchars($shipment['delivery_pincode']), 'B', 0, 'C');

    // GSTIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(28, 5, "Recipient's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(32, 5, ' ' . htmlspecialchars($shipment['delivery_gstin'] ?? ''), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(31, 5, "*Where Applicable", 0, 0, 'R');

    // Row 3: Pcs/Wt (Left) & Desc (Right) - y: 98 to 113
    $pdf->Line(10, 113, 200, 113);
    
    // ----------- BOX 3 -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(15, 105);
    $pdf->Cell(22, 5, 'Total Num Pcs:');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(18, 5, $shipment['pieces'], 'B', 0, 'C'); // Underlined & Centered
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(22, 5, 'Total Weight:');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(22, 5, number_format($shipment['weight'], 3) . ' kg', 'B', 0, 'C');

    // ----------- BOX 4 Top -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(107, 105);
    $pdf->Cell(32, 5, 'Description of Content:');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(58, 5, ' ' . htmlspecialchars(substr($shipment['description']?:'NO DESCRIPTION', 0, 35)), 'B', 0, 'L');

    // Row 4: Enclosures (Left) & Total Value (Right) - y: 113 to 128
    $pdf->Line(10, 128, 200, 128);
    
    // ----------- BOX 5 -----------
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, 115);
    $pdf->Cell(40, 5, 'Paper Work Enclosures');
    
    $hasInvoice = $shipment['gst_invoice'] == 1;
    $hasEway = !empty($shipment['ewaybill_no']);
    $isNA = (!$hasInvoice && !$hasEway);
    
    $pdf->checkbox(22, 122, $isNA, 'NA');
    $pdf->checkbox(45, 122, $hasInvoice, 'Invoice');
    $pdf->checkbox(75, 122, $hasEway, 'E-Way bill');

    // ----------- BOX 4 Bottom -----------
    // Value text
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(107, 118);
    $pdf->Cell(65, 5, 'Total Value of Consignment for carriage/E-Way bill:');
    
    // Rs Box Separator
    $pdf->Line(172, 113, 172, 128); 
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(175, 117);
    $pdf->Cell(20, 8, 'Rs. ' . number_format($shipment['declared_value'], 2), 0, 0, 'C');

    // Row 5: Total/Mode (Left) & Mode/Barcode (Right) - y: 128 to 158
    $pdf->Line(10, 158, 200, 158);
    $pdf->Line(10, 143, 105, 143); // Split Left Box 7
    
    // ----------- BOX 7 -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(15, 134);
    $pdf->Cell(22, 5, 'Total Amount:');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(50, 5, 'Rs. ' . number_format($shipment['final_price'], 2), 0, 0, 'L');
    
    // Box 7 (Payment Mode)
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, 145);
    $pdf->Cell(40, 5, 'Mode of Payment');
    
    $isCash = ($shipment['payment_method'] === 'cod');
    $isUpi  = ($shipment['payment_method'] === 'prepaid'); 
    $pdf->checkbox(22, 152, $isCash, 'Cash');
    $pdf->checkbox(45, 152, $isUpi, 'UPI');

    // ----------- BOX 6 -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(110, 131);
    $pdf->Cell(18, 5, 'Mode: [   ]');
    
    $isSurf = strpos($shipment['service_type'], 'surface') !== false;
    $isAir = strpos($shipment['service_type'], 'air') !== false;
    $isExp = strpos($shipment['service_type'], 'standard') !== false || strpos($shipment['service_type'], 'premium') !== false;
    
    $pdf->checkbox(135, 132, $isSurf, 'Surface');
    $pdf->checkbox(160, 132, $isAir, 'Air Cargo');
    $pdf->checkbox(180, 132, $isExp, 'Express');

    $pdf->SetXY(110, 140);
    $pdf->Cell(30, 5, 'Consignment Number:');
    
    // Mock Barcode Render
    $pdf->SetFont('Courier', 'B', 14);
    $pdf->SetXY(110, 145);
    $pdf->Cell(85, 6, '| | ' . $shipment['tracking_no'] . ' | |', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(110, 151);
    $pdf->Cell(85, 4, $shipment['tracking_no'], 0, 0, 'C');

    // Row 6: Risk (Left) & Sign (Right) - y: 158 to 220
    
    // ----------- BOX 8 -----------
    
    // Column grid
    $pdf->Line(60, 158, 60, 220); // vertical 1
    $pdf->Line(85, 158, 85, 220); // vertical 2
    $pdf->Line(60, 189, 105, 189); // horizontal crossing columns

    // Left label
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 185);
    $pdf->Cell(50, 5, 'Risk Surcharge', 0, 0, 'C'); // perfectly centered in Left section
    
    // Options
    $isOwner = (!isset($shipment['risk_surcharge']) || $shipment['risk_surcharge'] === 'owner');
    $isCarrier = (isset($shipment['risk_surcharge']) && $shipment['risk_surcharge'] === 'carrier');

    $pdf->SetXY(60, 172);
    $pdf->Cell(25, 5, 'Owner', 0, 0, 'C'); // centered 
    $pdf->checkbox(92, 171, $isOwner, '', 6);
    
    $pdf->SetXY(60, 203);
    $pdf->Cell(25, 5, 'Carrier', 0, 0, 'C');
    $pdf->checkbox(92, 202, $isCarrier, '', 6);

    // ----------- BOX 9 -----------
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(145, 162);
    $pdf->Cell(50, 5, "Sender's Signature & Seal:", 0, 0, 'C');
    
    // Signature Border
    $pdf->SetDrawColor(0,0,0);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(145, 168, 50, 22, 'D');

    // OTP
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(110, 180);
    $pdf->Cell(10, 5, 'OTP: ');
    $pdf->Cell(20, 5, '', 'B', 0, 'L'); // Pure clean line
    
    // Date & Time
    $dateCreated = strtotime($shipment['created_at']);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(110, 195);
    $pdf->Cell(10, 5, 'Date:');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(22, 5, date('d-m-Y', $dateCreated), 'B', 0, 'C');
    $pdf->Cell(4, 5, ''); // pure space
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(10, 5, 'Time:');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(16, 5, date('h:i', $dateCreated), 'B', 0, 'C');
    $pdf->Cell(8, 5, date('A', $dateCreated), 0, 0, 'R');

    $pdf->SetXY(110, 207);
    $pdf->SetFont('Arial', '', 6);
    $termsTxt = "I have read and understood terms & conditions printed overleaf of this\nconsignment note and I agree to the same.";
    $pdf->MultiCell(85, 3, $termsTxt, 0, 'L');

    // ----------- FOOTER BAND -----------
    $pdf->SetFillColor(0, 26, 147); // #001A93
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Rect(10, 220, 190, 10, 'F');
    $pdf->SetXY(15, 222);
    $pdf->Cell(60, 6, 'www.careygo.in', 0, 0, 'L');
    $pdf->Cell(60, 6, 'customersupport@careygo.in', 0, 0, 'C');
    $pdf->Cell(50, 6, '+91 87804 06230', 0, 0, 'R');

    // Images Section (Before T&C)
    $imgY = 232;
    $hasImages = false;
    $uploadDir = __DIR__ . '/../uploads/booking-photos/';
    
    // Parcel Photo
    if (!empty($shipment['photo_parcel'])) {
        $pPath = $uploadDir . $shipment['photo_parcel'];
        if (file_exists($pPath)) {
            $pdf->SetXY(12, $imgY);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(40, 4, 'PARCEL PHOTO', 0, 1, 'L');
            $pdf->Image($pPath, 12, $imgY + 4, 35, 25);
            $hasImages = true;
        }
    }
    
    // Address Photo
    if (!empty($shipment['photo_address'])) {
        $aPath = $uploadDir . $shipment['photo_address'];
        if (file_exists($aPath)) {
            $pdf->SetXY(55, $imgY);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(40, 4, 'ADDRESS PHOTO', 0, 1, 'L');
            $pdf->Image($aPath, 55, $imgY + 4, 35, 25);
            $hasImages = true;
        }
    }

    // Terms & Conditions Block
    $tcY = $hasImages ? 268 : 235;
    $pdf->SetXY(10, $tcY);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 6, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 8);
    $tc = "1. I/We declare that this consignment does not contain personal mail, cash, jewellery, contraband, illegal drugs, any prohibited items and commodities which can cause safety hazards while transporting.";
    $pdf->MultiCell(190, 4, $tc, 0, 'L');

    return $pdf;
}
