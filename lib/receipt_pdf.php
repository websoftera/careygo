<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fpdf/fpdf.php';

class ReceiptPDF extends FPDF
{
    function Header()
    {
        $logoPath = __DIR__ . '/../assets/images/Main-Careygo-logo-blue.png';
        if (is_file($logoPath)) {
            $this->Image($logoPath, 10, 5, 55);
        } else {
            $this->SetTextColor(0, 26, 147);
            $this->SetFont('Arial', 'B', 24);
            $this->SetXY(10, 7);
            $this->Cell(43, 10, 'CAREYGO', 0, 0, 'L');
            $this->SetFont('Arial', 'B', 6);
            $this->SetXY(51, 6);
            $this->Cell(8, 4, 'TM', 0, 0, 'L');
        }
        $this->SetTextColor(0, 0, 0);

        // Date Box
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.3);
        $this->Rect(152, 15, 48, 6);
        $this->Line(167, 15, 167, 21); // separator

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(152, 15);
        $this->Cell(15, 6, 'DATE', 0, 0, 'C');
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
            $this->Cell(35, $size, $label, 0, 0, 'L');
        }
    }

    function valueCell($w, $h, $text, $border = 'B', $align = 'L')
    {
        $oldMargin = $this->cMargin;
        $this->cMargin = 0;
        $this->Cell($w, $h, $text, $border, 0, $align);
        $this->cMargin = $oldMargin;
    }

    function rupeeSymbol($x, $y, $size = 3)
    {
        $oldLineWidth = $this->LineWidth;
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(max(0.16, $size * 0.07));
        $top = $y + ($size * 0.23);
        $mid = $y + ($size * 0.43);
        $left = $x + ($size * 0.15);
        $right = $x + ($size * 0.88);
        $stem = $x + ($size * 0.38);
        $bottom = $y + ($size * 0.95);

        $this->Line($left, $top, $right, $top);
        $this->Line($left, $mid, $right, $mid);
        $this->Line($stem, $top, $stem, $bottom);
        $this->Line($stem, $mid, $right, $bottom);
        $this->SetLineWidth($oldLineWidth);
    }

    function currencyCell($w, $h, $amount, $border = 0, $align = 'L', $decimals = 0)
    {
        $x = $this->GetX();
        $y = $this->GetY();
        if ($border) {
            $this->Cell($w, $h, '', $border, 0, 'L');
            $this->SetXY($x, $y);
        }

        $text = number_format((float)$amount, (int)$decimals);
        $symbolSize = min(3.4, max(2.7, $h * 0.62));
        $gap = 1.8;
        $contentWidth = $symbolSize + $gap + $this->GetStringWidth($text);
        if ($align === 'C') {
            $startX = $x + max(0, ($w - $contentWidth) / 2);
        } elseif ($align === 'R') {
            $startX = $x + max(0, $w - $contentWidth);
        } else {
            $startX = $x;
        }

        $symbolY = $y + max(0, ($h - $symbolSize) / 2);
        $this->rupeeSymbol($startX, $symbolY, $symbolSize);
        $this->SetXY($startX + $symbolSize + $gap, $y);
        $this->Cell(max(0, $w - ($startX - $x) - $symbolSize - $gap), $h, $text, 0, 0, 'L');
        $this->SetXY($x + $w, $y);
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

function receipt_weight_display($weight): string
{
    $w = (float)$weight;
    if ($w > 0 && $w < 1) return number_format($w * 1000, 0) . ' g';
    if (abs($w - round($w)) < 0.0001) return number_format($w, 0) . ' kg';
    return rtrim(rtrim(number_format($w, 3, '.', ''), '0'), '.') . ' kg';
}

function receipt_value_cell(ReceiptPDF $pdf, float $w, float $h, string $text, string $border = 'B', string $align = 'L'): void
{
    $pdf->valueCell($w, $h, $text, $border, $align);
}

function receipt_has_gst_details(array $shipment): bool
{
    return !empty($shipment['gst_invoice'])
        || !empty($shipment['gstin'])
        || !empty($shipment['pickup_gstin'])
        || !empty($shipment['delivery_gstin']);
}

function receipt_payment_label(?string $method): string
{
    return ['prepaid' => 'Prepaid', 'cod' => 'POD', 'credit' => 'Credit'][$method ?: 'prepaid'] ?? ucfirst((string)$method);
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
    $pdf->SetXY(167, 15);
    $createdIst = receipt_ist_timestamp($shipment['created_at'] ?? null);
    $pdf->Cell(33, 6, strtoupper($createdIst->format('d M Y')), 0, 0, 'C');

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
    $pdf->Cell(94, 6, 'Non Negotiable Consignment Note / Subject to Vadodara Jurisdiction Only.', 0, 0, 'L');
    
    $pdf->SetDrawColor(155, 155, 155);
    $pdf->SetLineWidth(0.2);

    // Row 2: Addresses (y: 38 to 103) -> Height 65
    $pdf->Line(10, 103, 200, 103);
    
    // ----------- BOX 1 (Pickup) -----------
    $y = 42;
    $x = 12;
    $lineH = 6.5;
    
    // Sender Name
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(42, 5, "Sender's [Consigner] Name:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 50, 5, htmlspecialchars(substr(receipt_upper($shipment['pickup_name']),0,28)));

    // Company Name
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 67, 5, htmlspecialchars(substr(receipt_upper($shipment['pickup_company_name'] ?? ''),0,35)));

    // Phone
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(12, 5, "Phone:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 35, 5, htmlspecialchars($shipment['pickup_phone']));

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(12, 5, "Email:");
    $pdf->SetFont('Arial', '', 6);
    receipt_value_cell($pdf, 32, 5, htmlspecialchars(substr($shipment['pickup_email'] ?? $shipment['customer_email'] ?? '', 0, 28)));

    // Address
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(15, 5, "Address:");
    $pdf->SetFont('Arial', 'B', 8);
    $addr1 = htmlspecialchars(receipt_upper($shipment['pickup_address']));
    receipt_value_cell($pdf, 77, 5, substr($addr1, 0, 42));
    
    $addr1_line2 = strlen($addr1)>42 ? substr($addr1, 42, 42) : '';
    $addr1_line3 = strlen($addr1)>84 ? substr($addr1, 84, 42) : '';
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    receipt_value_cell($pdf, 77, 5, $addr1_line2, $addr1_line2 !== '' ? 'B' : '');

    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    receipt_value_cell($pdf, 77, 5, $addr1_line3, $addr1_line3 !== '' ? 'B' : '');

    // City/State/PIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 25, 5, htmlspecialchars(receipt_upper($shipment['pickup_city'])));
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(10, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 20, 5, htmlspecialchars(receipt_upper($shipment['pickup_state'])));
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(18, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(0, 26, 147);
    receipt_value_cell($pdf, 30, 5, htmlspecialchars($shipment['pickup_pincode']));
    $pdf->SetTextColor(0, 0, 0);

    // GSTIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Sender's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $senderGstin = !empty($shipment['pickup_gstin']) ? $shipment['pickup_gstin'] : ($shipment['gstin'] ?? '');
    receipt_value_cell($pdf, 35, 5, htmlspecialchars($senderGstin));
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
    receipt_value_cell($pdf, 46, 5, htmlspecialchars(substr(receipt_upper($shipment['delivery_name']),0,25)));

    // Company Name
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(25, 5, "Company Name:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 66, 5, htmlspecialchars(substr(receipt_upper($shipment['delivery_company_name'] ?? ''),0,35)));

    // Phone
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(12, 5, "Phone:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 35, 5, htmlspecialchars($shipment['delivery_phone']));

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(12, 5, "Email:");
    $pdf->SetFont('Arial', '', 6);
    receipt_value_cell($pdf, 32, 5, htmlspecialchars(substr($shipment['delivery_email'] ?? '', 0, 28)));

    // Address
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(15, 5, "Address:");
    $pdf->SetFont('Arial', 'B', 8);
    $addr2 = htmlspecialchars(receipt_upper($shipment['delivery_address']));
    receipt_value_cell($pdf, 76, 5, substr($addr2, 0, 42));
    
    $addr2_line2 = strlen($addr2)>42 ? substr($addr2, 42, 42) : '';
    $addr2_line3 = strlen($addr2)>84 ? substr($addr2, 84, 42) : '';
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    receipt_value_cell($pdf, 76, 5, $addr2_line2, $addr2_line2 !== '' ? 'B' : '');

    $pdf->SetXY($x, $y+=$lineH);
    $pdf->Cell(15, 5, "");
    receipt_value_cell($pdf, 76, 5, $addr2_line3, $addr2_line3 !== '' ? 'B' : '');

    // City/State/PIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(8, 5, "City:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 25, 5, htmlspecialchars(receipt_upper($shipment['delivery_city'])));
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(10, 5, "State:");
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 20, 5, htmlspecialchars(receipt_upper($shipment['delivery_state'])));
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(18, 5, "PIN Code:");
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(0, 26, 147);
    receipt_value_cell($pdf, 30, 5, htmlspecialchars($shipment['delivery_pincode']));
    $pdf->SetTextColor(0, 0, 0);

    // GSTIN
    $pdf->SetXY($x, $y+=$lineH);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(28, 5, "Recipient's GSTIN*:");
    $pdf->SetFont('Arial', 'B', 8);
    $recipientGstin = $shipment['delivery_gstin'] ?? '';
    receipt_value_cell($pdf, 32, 5, htmlspecialchars($recipientGstin));
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(31, 5, "*Where Applicable", 0, 0, 'R');

    // Row 3: Pcs/Wt (Left) & Desc (Right) - y: 98 to 113
    $pdf->Line(10, 113, 200, 113);
    
    // ----------- BOX 3 -----------
    $receiptWeight = (float)($shipment['chargeable_weight'] ?? 0) > 0 ? (float)$shipment['chargeable_weight'] : (float)$shipment['weight'];
    $box3Top = 103;
    $box3Height = 10;
    $box3CellHeight = 5;
    $pdf->SetXY(12, $box3Top + (($box3Height - $box3CellHeight) / 2));
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->Cell(6, 5, 'Pcs:');
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 8, 5, (string)$shipment['pieces']);
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->Cell(5, 5, ' | ', 0, 0, 'C');
    $pdf->Cell(17, 5, 'Actual Weight:');
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 13, 5, receipt_weight_display($shipment['weight']));
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->Cell(5, 5, ' | ', 0, 0, 'C');
$pdf->Cell(21, 5, 'Chargeable Weight:');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(2, 5, '');
receipt_value_cell($pdf, 16, 5, receipt_weight_display($receiptWeight));

    // ----------- BOX 4 Top -----------
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(107, 105);
    $pdf->Cell(32, 5, 'Description of Content:');
    $pdf->SetFont('Arial', 'B', 8);
    receipt_value_cell($pdf, 58, 5, htmlspecialchars(substr(receipt_upper($shipment['description']?:'NO DESCRIPTION'), 0, 35)));

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
    
    $pdf->checkbox(12, 122, $isNA, 'NA');
    $pdf->checkbox(27, 122, $hasInvoice, 'Invoice');
    $pdf->checkbox(50, 122, $hasEway, 'E-Way bill');
    $pdf->checkbox(77, 122, $hasDeliveryNote, 'Delivery Note');

    // ----------- BOX 4 Bottom -----------
    // Value text
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(107, 118);
    $pdf->MultiCell(62, 4, 'Total Value of Consignment for carriage / E-Way bill:', 0, 'L');
    
    // Amount box separator
    $pdf->Line(172, 113, 172, 128); 
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY(172, 117);
$pdf->currencyCell(26, 8, (float)$shipment['declared_value'], 0, 'C');

    // Row 5: Total/Mode (Left) & Mode (Right) - y: 128 to 158
    $pdf->Line(10, 158, 200, 158);
    $pdf->Line(10, 143, 105, 143); // Split Left Box 7
    
    // ----------- BOX 7 -----------
    // Box y=128 to y=143 (15mm). Center content vertically.
    $pdf->SetFont('Arial', '', 8);
$pdf->SetXY(15, 130);
$pdf->Cell(22, 5, 'Total Amount:');
$pdf->SetFont('Arial', 'B', 9);
$pdf->currencyCell(50, 5, (float)$shipment['final_price']);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(15, 136);
    $pdf->Cell(85, 4, receipt_amount_words((float)$shipment['final_price']), 0, 0, 'L');
    if (!empty($shipment['tempo_charge']) && (float)$shipment['tempo_charge'] > 0) {
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetXY(15, 140);
        $pdf->Cell(22, 3, 'Tempo Charges:', 0, 0, 'L');
        $pdf->currencyCell(30, 3, (float)$shipment['tempo_charge']);
    }
    // Box 7 (Payment Mode)
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, 145);
    $pdf->Cell(40, 5, 'Mode of Payment');

    $isCash = ($shipment['payment_method'] === 'cod');
    $isUpi  = ($shipment['payment_method'] === 'prepaid');
    $isCredit = ($shipment['payment_method'] === 'credit');
    $pdf->checkbox(18, 152, $isCash, 'POD');
    $pdf->checkbox(40, 152, $isUpi, 'Prepaid');
    $pdf->checkbox(70, 152, $isCredit, 'Credit');

    // Credit account details
    if ($isCredit && !empty($shipment['credit_client_name'])) {
        $pdf->SetFont('Arial', '', 5.5);
        $pdf->SetXY(15, 154);
        $pdf->Cell(85, 2.5, 'Client: ' . $shipment['credit_client_name'], 0, 0, 'L');
        $pdf->SetXY(15, 156.5);
        $pdf->Cell(85, 2.5, 'Requestor: ' . ($shipment['credit_requestor_name'] ?? ''), 0, 0, 'L');
    }

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

    // AWB Number
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(110, 148);
    $pdf->Cell(15, 5, 'AWB No:');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(75, 5, $shipment['tracking_no'] ?? '', 0, 0, 'L');

    // Row 6: Risk (Left) & Sign (Right) - y: 158 to 220
    
    // ----------- BOX 8 -----------
    
    // Column grid: keep a compact Accepted area and give Owner Risk more text width.
    $pdf->Line(90, 158, 90, 209);
    $pdf->Line(90, 189, 105, 189);

    // Left label
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(10, 162);
    $pdf->Cell(50, 5, 'Owner Risk', 0, 0, 'C');
    $pdf->SetFont('Arial', '', 6.2);
    $pdf->SetXY(13, 168);
    $ownerRiskNote = "All consignments are accepted for carriage at the owner's risk. The Company shall not be liable for any loss, damage, deterioration, leakage, or breakage, howsoever caused, whether in transit or otherwise. Carriage is subject to the terms, conditions, and limitations of the respective freight forwarder, carrier, or airline, as applicable. The Company's liability, if any, is limited to Rupees 100 per kg or the actual value of the consignment, whichever is lower, unless the shipment is declared and insured at the time of booking and expressly accepted by the Company in writing.";
    $pdf->MultiCell(72, 3.6, $ownerRiskNote, 0, 'L');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->SetXY(91, 174);
    $pdf->Cell(13, 4, 'Accepted', 0, 0, 'C');
    $pdf->checkbox(95, 181, true, '', 6);

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
    receipt_value_cell($pdf, 22, 5, $dateCreated->format('d-m-Y'));
    $pdf->Cell(4, 5, ''); // pure space
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(10, 5, 'Time:');
    $pdf->SetFont('Arial', '', 9);
    receipt_value_cell($pdf, 16, 5, $dateCreated->format('h:i'));
    $pdf->Cell(8, 5, $dateCreated->format('A'), 0, 0, 'R');

    $pdf->SetXY(110, 204);
    $pdf->SetFont('Arial', '', 6);
    $termsTxt = "I have read and understood terms & conditions printed overleaf of this\nconsignment note and I agree to the same.";
    $pdf->MultiCell(85, 3, $termsTxt, 0, 'L');

    $pdf->SetXY(12, 212);
    $pdf->SetFont('Arial', 'B', 6.5);
    $pdf->Cell(185, 4, 'BOOKING FRANCHISEE: SAMRUDDHI ENTERPRISES', 0, 0, 'L');
    $pdf->SetXY(12, 216);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(185, 4, 'D/1, Airport Road, Kasturba Housing Society, Vishrantwadi, Pune - 411015  |  Mobile: 93215 95882', 0, 0, 'L');

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
