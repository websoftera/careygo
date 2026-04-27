<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fpdf/fpdf.php';

class ReceiptPDF extends FPDF
{
    function Header()
    {
        $this->SetTextColor(0, 26, 147);
        $this->SetFont('Arial', 'B', 24);
        $this->SetXY(10, 7);
        $this->Cell(43, 10, 'CAREYGO', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 6);
        $this->SetXY(51, 6);
        $this->Cell(8, 4, 'TM', 0, 0, 'L');
        $this->SetTextColor(0, 0, 0);

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
        $this->SetDrawColor(130, 130, 130);
        $this->SetLineWidth(0.2);
        $this->Rect($x, $y, $size, $size);
        if ($checked) {
            $this->SetDrawColor(0, 26, 147);
            $this->SetLineWidth(0.45);
            $this->Line($x + 0.8, $y + ($size * 0.55), $x + ($size * 0.38), $y + $size - 0.8);
            $this->Line($x + ($size * 0.38), $y + $size - 0.8, $x + $size - 0.6, $y + 0.8);
            $this->SetDrawColor(130, 130, 130);
            $this->SetLineWidth(0.2);
        }
        if ($label) {
            $this->SetFont('Arial', '', 8);
            $this->SetXY($x + $size + 2, $y);
            $this->Cell(20, $size, $label, 0, 0, 'L');
        }
    }
}

function receipt_upper(?string $value): string
{
    return strtoupper(trim((string)$value));
}

function receipt_words_under_1000(int $n): string
{
    $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
    $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
    $words = [];
    if ($n >= 100) {
        $words[] = $ones[intdiv($n, 100)] . ' HUNDRED';
        $n %= 100;
    }
    if ($n >= 20) {
        $words[] = $tens[intdiv($n, 10)];
        $n %= 10;
    }
    if ($n > 0) $words[] = $ones[$n];
    return implode(' ', $words);
}

function receipt_amount_words(float $amount): string
{
    $n = (int)round($amount);
    if ($n <= 0) return 'RUPEES ZERO ONLY';
    $parts = [];
    foreach ([10000000 => 'CRORE', 100000 => 'LAKH', 1000 => 'THOUSAND', 1 => ''] as $div => $label) {
        $chunk = intdiv($n, $div);
        if ($chunk > 0) {
            $parts[] = receipt_words_under_1000($chunk) . ($label ? ' ' . $label : '');
            $n %= $div;
        }
    }
    return 'RUPEES ' . trim(implode(' ', $parts)) . ' ONLY';
}

function receipt_service_label(string $serviceType): string
{
    return [
        'standard' => 'STANDARD EXPRESS',
        'premium' => 'PREMIUM EXPRESS',
        'air_cargo' => 'AIR CARGO',
        'surface' => 'SURFACE CARGO',
    ][$serviceType] ?? strtoupper(str_replace('_', ' ', $serviceType));
}

function receipt_ist_timestamp(?string $createdAt): DateTimeImmutable
{
    $tz = new DateTimeZone('Asia/Kolkata');
    return new DateTimeImmutable($createdAt ?: 'now', $tz);
}

function receipt_has_gst_details(array $shipment): bool
{
    return !empty($shipment['gst_invoice'])
        || !empty($shipment['gstin'])
        || !empty($shipment['pickup_gstin'])
        || !empty($shipment['delivery_gstin']);
}

function receipt_gst_summary(array $shipment): array
{
    $total = (float)($shipment['final_price'] ?? 0);
    $gstRate = 0.18;
    $taxable = round($total / (1 + $gstRate), 2);
    $gst = round($total - $taxable, 2);
    $sameState = strtolower(trim($shipment['pickup_state'] ?? '')) === strtolower(trim($shipment['delivery_state'] ?? ''));

    return [
        'taxable' => $taxable,
        'gst' => $gst,
        'cgst' => $sameState ? round($gst / 2, 2) : 0,
        'sgst' => $sameState ? round($gst / 2, 2) : 0,
        'igst' => $sameState ? 0 : $gst,
        'same_state' => $sameState,
    ];
}

function generateReceiptPDF($shipment)
{
    $pdf = new ReceiptPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);
    $hasGstDetails = receipt_has_gst_details($shipment);
    $gstSummary = $hasGstDetails ? receipt_gst_summary($shipment) : null;

    // Header Date
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(155, 15);
    $createdIst = receipt_ist_timestamp($shipment['created_at'] ?? null);
    $pdf->Cell(45, 8, $createdIst->format('d-M-Y'), 0, 0, 'C');

    // Outer Border (Adjusted to end at Footer Band y=230)
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(10, 30, 190, 200); // Height 200 starts at 30, ends at 230
    
    // Middle vertical split
    $pdf->Line(105, 38, 105, 220); // x=105

    // Row 1: Disclaimers (y: 30 to 38)
    $pdf->Line(10, 38, 200, 38);
    $pdf->SetXY(11, 31);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(94, 6, 'Non Negotiable Consignment Note / Subject to Vadodara jurisdiction only.', 0, 0, 'L');
    
    $pdf->SetXY(106, 31);
    $pdf->SetFont('Arial', '', 6);
    $disclaimer2 = $hasGstDetails
        ? "GST details captured on this receipt. Tax invoice details are shown where applicable for\nCareygo or its channel partner as the case may be."
        : "The Consignment note is not a tax invoice. A tax invoice will be made eligible for Careygo or its\nchannel partner as the case may be urgent request.";
    $pdf->MultiCell(93, 3, $disclaimer2, 0, 'L');

    $pdf->SetDrawColor(155, 155, 155);
    $pdf->SetLineWidth(0.2);

    // Row 2: Addresses (y: 38 to 98) -> Height 60
    $pdf->Line(10, 98, 200, 98);
    
    // ----------- BOX 1 (Pickup) -----------
    $y = 42;
    $x = 12;
    $lineH = 6.5;
    
    // Sender Name
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(42, 5, "Sender's [Consigner] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(50, 5, ' ' . htmlspecialchars(substr(receipt_upper($shipment['pickup_name']),0,28)), 'B', 0, 'L');

    // Company Name
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(67, 5, ' ' . htmlspecialchars(substr(receipt_upper($shipment['pickup_company_name'] ?? ''),0,35)), 'B', 0, 'L');

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
    $addr1 = htmlspecialchars(receipt_upper($shipment['pickup_address']));
    $pdf->Cell(77, 5, ' ' . substr($addr1, 0, 42), 'B', 0, 'L');
    
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    $pdf->Cell(77, 5, ' ' . (strlen($addr1)>42 ? substr($addr1, 42, 42) : ''), 'B', 0, 'L');

    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    $pdf->Cell(77, 5, ' ' . (strlen($addr1)>84 ? substr($addr1, 84, 42) : ''), 'B', 0, 'L');

    // City/State/PIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, ' ' . htmlspecialchars(receipt_upper($shipment['pickup_city'])), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(10, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, ' ' . htmlspecialchars(receipt_upper($shipment['pickup_state'])), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(14, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 26, 147);
    $pdf->Cell(15, 5, ' ' . htmlspecialchars($shipment['pickup_pincode']), 'B', 0, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // GSTIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Sender's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $senderGstin = !empty($shipment['pickup_gstin']) ? $shipment['pickup_gstin'] : ($shipment['gstin'] ?? '');
    $pdf->Cell(35, 5, ' ' . htmlspecialchars($senderGstin), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(32, 5, "*Where Applicable", 0, 0, 'R');

    // ----------- BOX 2 (Delivery) -----------
    $y = 42;
    $x = 107;
    
    // Recipient Name
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(45, 5, "Recipient's [Consignee] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(46, 5, ' ' . htmlspecialchars(substr(receipt_upper($shipment['delivery_name']),0,25)), 'B', 0, 'L');

    // Company Name
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(66, 5, ' ' . htmlspecialchars(substr(receipt_upper($shipment['delivery_company_name'] ?? ''),0,35)), 'B', 0, 'L');

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
    $addr2 = htmlspecialchars(receipt_upper($shipment['delivery_address']));
    $pdf->Cell(76, 5, ' ' . substr($addr2, 0, 42), 'B', 0, 'L');
    
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    $pdf->Cell(76, 5, ' ' . (strlen($addr2)>42 ? substr($addr2, 42, 42) : ''), 'B', 0, 'L');

    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    $pdf->Cell(76, 5, ' ' . (strlen($addr2)>84 ? substr($addr2, 84, 42) : ''), 'B', 0, 'L');

    // City/State/PIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, ' ' . htmlspecialchars(receipt_upper($shipment['delivery_city'])), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(10, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, ' ' . htmlspecialchars(receipt_upper($shipment['delivery_state'])), 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(14, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 26, 147);
    $pdf->Cell(14, 5, ' ' . htmlspecialchars($shipment['delivery_pincode']), 'B', 0, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // GSTIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(28, 5, "Recipient's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $recipientGstin = $shipment['delivery_gstin'] ?? '';
    $pdf->Cell(32, 5, ' ' . htmlspecialchars($recipientGstin), 'B', 0, 'C');
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
    $pdf->Cell(20, 5, 'Actual Wt:');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(18, 5, number_format((float)$shipment['weight'], 3) . ' kg', 'B', 0, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(15, 5, 'Chg Wt:');
    $pdf->SetFont('Arial', 'B', 8);
    $receiptWeight = (float)($shipment['chargeable_weight'] ?? 0) > 0 ? (float)$shipment['chargeable_weight'] : (float)$shipment['weight'];
    $pdf->Cell(18, 5, number_format($receiptWeight, 3) . ' kg', 'B', 0, 'C');

    // ----------- BOX 4 Top -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(107, 105);
    $pdf->Cell(32, 5, 'Description of Content:');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(58, 5, ' ' . htmlspecialchars(substr(receipt_upper($shipment['description']?:'NO DESCRIPTION'), 0, 35)), 'B', 0, 'L');

    // Row 4: Enclosures (Left) & Total Value (Right) - y: 113 to 128
    $pdf->Line(10, 128, 200, 128);
    
    // ----------- BOX 5 -----------
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, 115);
    $pdf->Cell(40, 5, 'Paper Work Enclosures');
    
    $hasInvoice = !empty($shipment['gst_invoice']);
    $hasEway = !empty($shipment['ewaybill_no']);
    $hasDeliveryNote = !empty($shipment['customer_ref']);
    $isNA = (!$hasInvoice && !$hasEway);
    
    $pdf->checkbox(16, 122, $isNA, 'NA');
    $pdf->checkbox(36, 122, $hasInvoice, 'Invoice');
    $pdf->checkbox(66, 122, $hasEway, 'E-Way bill');
    $pdf->checkbox(92, 122, $hasDeliveryNote, 'Delivery Note');

    // ----------- BOX 4 Bottom -----------
    // Value text
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(107, 118);
    $pdf->Cell(65, 5, 'Total Value of Consignment for carriage/E-Way bill:');
    
    // Rs Box Separator
    $pdf->Line(172, 113, 172, 128); 
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetXY(175, 117);
    $pdf->Cell(20, 8, 'Rs. ' . number_format((float)$shipment['declared_value'], 0), 0, 0, 'C');

    // Row 5: Total/Mode (Left) & Mode/Barcode (Right) - y: 128 to 158
    $pdf->Line(10, 158, 200, 158);
    $pdf->Line(10, 143, 105, 143); // Split Left Box 7
    
    // ----------- BOX 7 -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(15, 134);
    $pdf->Cell(22, 5, 'Total Amount:');
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(50, 5, 'Rs. ' . number_format((float)$shipment['final_price'], 0), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetXY(15, 139);
    $pdf->Cell(85, 3, receipt_amount_words((float)$shipment['final_price']), 0, 0, 'L');
    if ($hasGstDetails && $gstSummary) {
        $pdf->SetFont('Arial', '', 6.5);
        $pdf->SetXY(15, 142);
        $taxText = 'Taxable Rs. ' . number_format($gstSummary['taxable'], 2) . ' | ';
        $taxText .= $gstSummary['same_state']
            ? 'CGST Rs. ' . number_format($gstSummary['cgst'], 2) . ' + SGST Rs. ' . number_format($gstSummary['sgst'], 2)
            : 'IGST Rs. ' . number_format($gstSummary['igst'], 2);
        $pdf->Cell(85, 3, $taxText, 0, 0, 'L');
    }
    
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
    $pdf->Cell(18, 5, 'Mode:');
    
    $isSurf = strpos($shipment['service_type'], 'surface') !== false;
    $isAir = strpos($shipment['service_type'], 'air') !== false;
    $isStd = strpos($shipment['service_type'], 'standard') !== false;
    $isPrem = strpos($shipment['service_type'], 'premium') !== false;
    
    $pdf->checkbox(126, 132, $isStd, 'Standard Express', 3.5);
    $pdf->checkbox(126, 138, $isPrem, 'Premium Express', 3.5);
    $pdf->checkbox(164, 132, $isAir, 'Air Cargo', 3.5);
    $pdf->checkbox(164, 138, $isSurf, 'Surface Cargo', 3.5);

    $pdf->SetXY(110, 144);
    $pdf->Cell(30, 5, 'Consignment Number:');
    
    // Mock Barcode Render
    $pdf->SetFont('Courier', 'B', 14);
    $pdf->SetXY(110, 149);
    $pdf->Cell(85, 6, '| | ' . $shipment['tracking_no'] . ' | |', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(110, 154);
    $pdf->Cell(85, 4, $shipment['tracking_no'], 0, 0, 'C');

    // Row 6: Risk (Left) & Sign (Right) - y: 158 to 220
    
    // ----------- BOX 8 -----------
    
    // Column grid
    $pdf->Line(60, 158, 60, 220); // vertical 1
    $pdf->Line(85, 158, 85, 220); // vertical 2
    $pdf->Line(60, 189, 105, 189); // horizontal crossing columns

    // Left label
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 180);
    $pdf->Cell(50, 5, 'Owner Risk', 0, 0, 'C');
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(15, 190);
    $ownerRiskNote = "Goods to be sent at owner's risk only. The company assumes no liability for breakage, leakage, or damage.";
    $pdf->MultiCell(42, 4, $ownerRiskNote, 0, 'C');
    $pdf->SetXY(75, 185);
    $pdf->Cell(22, 5, 'Accepted', 0, 0, 'C');
    $pdf->checkbox(94, 184, true, '', 6);

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
    $dateCreated = $createdIst;
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(110, 195);
    $pdf->Cell(10, 5, 'Date:');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(22, 5, $dateCreated->format('d-m-Y'), 'B', 0, 'C');
    $pdf->Cell(4, 5, ''); // pure space
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(10, 5, 'Time:');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(16, 5, $dateCreated->format('h:i'), 'B', 0, 'C');
    $pdf->Cell(8, 5, $dateCreated->format('A'), 0, 0, 'R');

    $pdf->SetXY(110, 204);
    $pdf->SetFont('Arial', '', 6);
    $termsTxt = "I have read and understood terms & conditions printed overleaf of this\nconsignment note and I agree to the same.";
    $pdf->MultiCell(85, 3, $termsTxt, 0, 'L');

    $pdf->SetXY(12, 211);
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(185, 4, "Note: Goods to be sent at owner's risk only. The company assumes no liability for breakage, leakage, or damage.", 0, 0, 'L');

    $pdf->SetXY(12, 215);
    $pdf->SetFont('Arial', 'B', 6.5);
    $pdf->Cell(185, 4, 'BOOKING FRANCHISEE: PNQ01 - VADODARA, GUJARAT', 0, 0, 'L');

    // ----------- FOOTER BAND -----------
    $pdf->SetFillColor(0, 26, 147); // #001A93
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Rect(10, 220, 190, 10, 'F');
    $pdf->SetXY(15, 222);
    $pdf->Cell(55, 6, 'www.careygo.in', 0, 0, 'L');
    $pdf->Cell(120, 6, '+91 87804 06230 | customersupport@careygo.in', 0, 0, 'R');

    // Images Section (Outside the Border, Before T&C)
    $imgY = 232;
    $hasImages = false;
    $uploadDir = __DIR__ . '/../uploads/booking-photos/';
    
    // Parcel Photo
    if (!empty($shipment['photo_parcel'])) {
        $pPath = $uploadDir . $shipment['photo_parcel'];
        if (file_exists($pPath)) {
            $pdf->SetXY(12, $imgY);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 26, 147); // Blue label
            $pdf->Cell(60, 5, 'PARCEL PHOTO', 0, 1, 'L');
            $pdf->Image($pPath, 12, $imgY + 5, 55, 38);
            $hasImages = true;
        }
    }
    
    // Address Photo
    if (!empty($shipment['photo_address'])) {
        $aPath = $uploadDir . $shipment['photo_address'];
        if (file_exists($aPath)) {
            $pdf->SetXY(75, $imgY); // Placed next to parcel photo
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 26, 147);
            $pdf->Cell(60, 5, 'ADDRESS PHOTO', 0, 1, 'L');
            $pdf->Image($aPath, 75, $imgY + 5, 55, 38);
            $hasImages = true;
        }
    }

    // Terms & Conditions Block
    $tcY = $hasImages ? 280 : 235;
    $pdf->SetXY(10, $tcY);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 6, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 8);
    $tc = "1. I/We declare that this consignment does not contain personal mail, cash, jewellery, contraband, illegal drugs, any prohibited items and commodities which can cause safety hazards while transporting.";
    $pdf->MultiCell(190, 4, $tc, 0, 'L');

    return $pdf;
}
