<?php
/**
 * GST Tax Invoice — printable page for a single shipment
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

$authUser = auth_require('customer');

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: dashboard.php'); exit; }

// Fetch shipment — must belong to this customer
$stmt = $pdo->prepare("SELECT s.*, u.full_name AS cust_name, u.email AS cust_email
                        FROM shipments s
                        JOIN users u ON u.id = s.customer_id
                        WHERE s.id = ? AND s.customer_id = ?");
$stmt->execute([$id, $authUser['sub']]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) { header('Location: dashboard.php'); exit; }

// Company info (can be configured via settings)
$companyName    = 'Careygo Logistics Pvt. Ltd.';
$companyGstin   = '27XXXXX0000X1ZX';   // replace with actual GSTIN
$companyAddr    = '123, Logistics Park, Mumbai, Maharashtra - 400001';
$companyEmail   = 'billing@careygo.in';
$companyPhone   = '+91-XXXXX-XXXXX';

// Invoice number: INV-<shipment_id>-<year>
$invoiceNo   = 'INV-' . str_pad($s['id'], 6, '0', STR_PAD_LEFT) . '-' . date('Y', strtotime($s['created_at']));
$invoiceDate = date('d M Y', strtotime($s['created_at']));

// Tax calculation (18% GST — 9% CGST + 9% SGST for same-state; 18% IGST for inter-state)
$baseAmt  = (float) $s['final_price'];
// Reverse calculate: assume price is inclusive of GST
$gstRate  = 0.18;
$taxable  = round($baseAmt / (1 + $gstRate), 2);
$gstAmt   = round($baseAmt - $taxable, 2);
$sameState = strtolower(trim($s['pickup_state'] ?? '')) === strtolower(trim($s['delivery_state'] ?? ''));
$cgst = $sgst = $igst = 0;
if ($sameState) {
    $cgst = $sgst = round($gstAmt / 2, 2);
} else {
    $igst = $gstAmt;
}

$serviceLabels = [
    'standard'  => 'Standard Express Delivery',
    'premium'   => 'Premium Express Delivery',
    'air_cargo' => 'Air Cargo Delivery',
    'surface'   => 'Surface Cargo Delivery',
];
$serviceDesc = $serviceLabels[$s['service_type']] ?? ucwords(str_replace('_',' ',$s['service_type']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Invoice <?= h($invoiceNo) ?> — Careygo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', Arial, sans-serif; font-size: 13px; color: #1f2937; background: #f9fafb; }
        .invoice-wrap { max-width: 860px; margin: 24px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }

        /* Header */
        .inv-header { background: #001a93; color: #fff; padding: 28px 32px; display: flex; justify-content: space-between; align-items: flex-start; }
        .inv-company-name { font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
        .inv-company-sub  { font-size: 11px; opacity: .8; margin-top: 4px; line-height: 1.6; }
        .inv-title-block  { text-align: right; }
        .inv-title        { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .inv-no           { font-size: 13px; opacity: .85; margin-top: 4px; }

        /* Body */
        .inv-body { padding: 28px 32px; }
        .inv-meta-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
        .inv-meta-block label { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 4px; }
        .inv-meta-block p  { font-size: 13px; color: #111827; line-height: 1.6; }
        .inv-meta-block strong { font-weight: 700; }

        /* Bill-to / Ship-to */
        .inv-address-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .inv-addr-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; }
        .inv-addr-title { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: .5px; margin-bottom: 8px; }

        /* Table */
        .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .inv-table th { background: #f3f4f6; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6b7280; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
        .inv-table td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .inv-table .text-right { text-align: right; }

        /* Tax summary */
        .inv-totals { float: right; width: 320px; }
        .inv-totals table { width: 100%; border-collapse: collapse; }
        .inv-totals td { padding: 7px 12px; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
        .inv-totals .total-row td { font-weight: 700; font-size: 15px; background: #001a93; color: #fff; border-radius: 0; }
        .inv-totals .td-right { text-align: right; }
        .clearfix::after { content: ''; display: table; clear: both; }

        /* Footer */
        .inv-footer { padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; }
        .inv-note { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; padding: 10px 14px; font-size: 12px; color: #92400e; margin-bottom: 16px; }

        /* Print */
        @media print {
            body { background: #fff; }
            .invoice-wrap { border: none; border-radius: 0; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Print / Back toolbar -->
<div class="no-print" style="max-width:860px;margin:16px auto 0;display:flex;gap:10px;justify-content:flex-end;padding:0 4px;">
    <a href="dashboard.php" style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:8px;padding:8px 16px;text-decoration:none;font-size:13px;font-weight:600;">
        ← Back
    </a>
    <button onclick="window.print()"
            style="background:#001a93;color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:600;cursor:pointer;">
        🖨 Print / Save PDF
    </button>
</div>

<div class="invoice-wrap">

    <!-- Header -->
    <div class="inv-header">
        <div>
            <div class="inv-company-name"><?= h($companyName) ?></div>
            <div class="inv-company-sub">
                <?= h($companyAddr) ?><br>
                GSTIN: <?= h($companyGstin) ?> &nbsp;|&nbsp; <?= h($companyEmail) ?><br>
                <?= h($companyPhone) ?>
            </div>
        </div>
        <div class="inv-title-block">
            <div class="inv-title">TAX INVOICE</div>
            <div class="inv-no"><?= h($invoiceNo) ?></div>
            <div class="inv-no" style="margin-top:4px;">Date: <?= h($invoiceDate) ?></div>
        </div>
    </div>

    <div class="inv-body">

        <!-- Invoice meta -->
        <div class="inv-meta-grid">
            <div class="inv-meta-block">
                <label>Invoice No.</label>
                <p><strong><?= h($invoiceNo) ?></strong></p>
            </div>
            <div class="inv-meta-block">
                <label>Invoice Date</label>
                <p><?= h($invoiceDate) ?></p>
            </div>
            <div class="inv-meta-block">
                <label>AWB / Tracking No.</label>
                <p><strong><?= h($s['tracking_no']) ?></strong></p>
            </div>
            <?php if ($s['gstin']): ?>
            <div class="inv-meta-block">
                <label>Customer GSTIN</label>
                <p><?= h($s['gstin']) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($s['pan_number']): ?>
            <div class="inv-meta-block">
                <label>PAN Number</label>
                <p><?= h($s['pan_number']) ?></p>
            </div>
            <?php endif; ?>
            <div class="inv-meta-block">
                <label>Payment Method</label>
                <p><?= h(ucfirst($s['payment_method'] ?? 'Prepaid')) ?></p>
            </div>
        </div>

        <!-- Bill-to / Ship-to -->
        <div class="inv-address-grid">
            <div class="inv-addr-card">
                <div class="inv-addr-title">Bill To (Sender)</div>
                <p><strong><?= h($s['pickup_name']) ?></strong><?php if ($s['pickup_company_name']): ?><br><?= h($s['pickup_company_name']) ?><?php endif; ?></p>
                <p style="margin-top:6px;"><?= h($s['pickup_address']) ?><br>
                <?= h($s['pickup_city']) ?>, <?= h($s['pickup_state']) ?> - <?= h($s['pickup_pincode']) ?></p>
                <p style="margin-top:4px;">📞 <?= h($s['pickup_phone']) ?></p>
                <?php if ($s['pickup_gstin']): ?><p style="margin-top:4px;">GSTIN: <?= h($s['pickup_gstin']) ?></p><?php endif; ?>
            </div>
            <div class="inv-addr-card">
                <div class="inv-addr-title">Ship To (Receiver)</div>
                <p><strong><?= h($s['delivery_name']) ?></strong><?php if ($s['delivery_company_name']): ?><br><?= h($s['delivery_company_name']) ?><?php endif; ?></p>
                <p style="margin-top:6px;"><?= h($s['delivery_address']) ?><br>
                <?= h($s['delivery_city']) ?>, <?= h($s['delivery_state']) ?> - <?= h($s['delivery_pincode']) ?></p>
                <p style="margin-top:4px;">📞 <?= h($s['delivery_phone']) ?></p>
                <?php if ($s['delivery_gstin']): ?><p style="margin-top:4px;">GSTIN: <?= h($s['delivery_gstin']) ?></p><?php endif; ?>
            </div>
        </div>

        <!-- Line items -->
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Description of Service</th>
                    <th class="text-right">HSN / SAC</th>
                    <th class="text-right">Weight</th>
                    <th class="text-right">Taxable Amt</th>
                    <?php if ($sameState): ?>
                    <th class="text-right">CGST 9%</th>
                    <th class="text-right">SGST 9%</th>
                    <?php else: ?>
                    <th class="text-right">IGST 18%</th>
                    <?php endif; ?>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <?= h($serviceDesc) ?><br>
                        <span style="font-size:11px;color:#6b7280;">
                            From: <?= h($s['pickup_city']) ?> → To: <?= h($s['delivery_city']) ?>
                            <?php if ($s['pieces'] > 1): ?> | <?= (int)$s['pieces'] ?> pcs<?php endif; ?>
                        </span>
                    </td>
                    <td class="text-right">996812</td>
                    <td class="text-right"><?= number_format((float)($s['chargeable_weight'] ?: $s['weight']), 3) ?> kg</td>
                    <td class="text-right">₹<?= number_format($taxable, 2) ?></td>
                    <?php if ($sameState): ?>
                    <td class="text-right">₹<?= number_format($cgst, 2) ?></td>
                    <td class="text-right">₹<?= number_format($sgst, 2) ?></td>
                    <?php else: ?>
                    <td class="text-right">₹<?= number_format($igst, 2) ?></td>
                    <?php endif; ?>
                    <td class="text-right"><strong>₹<?= number_format($baseAmt, 2) ?></strong></td>
                </tr>
                <?php if ($s['packing_charge'] > 0): ?>
                <tr>
                    <td>2</td>
                    <td>Packing Material & Labour</td>
                    <td class="text-right">996812</td>
                    <td class="text-right">—</td>
                    <?php
                        $packTaxable = round((float)$s['packing_charge'] / 1.18, 2);
                        $packTax     = round((float)$s['packing_charge'] - $packTaxable, 2);
                    ?>
                    <td class="text-right">₹<?= number_format($packTaxable, 2) ?></td>
                    <?php if ($sameState): ?>
                    <td class="text-right">₹<?= number_format($packTax/2, 2) ?></td>
                    <td class="text-right">₹<?= number_format($packTax/2, 2) ?></td>
                    <?php else: ?>
                    <td class="text-right">₹<?= number_format($packTax, 2) ?></td>
                    <?php endif; ?>
                    <td class="text-right"><strong>₹<?= number_format((float)$s['packing_charge'], 2) ?></strong></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="clearfix">
            <div class="inv-note" style="float:left;max-width:460px;margin-bottom:0;">
                <strong>Note:</strong> All prices are inclusive of GST @ 18%.<br>
                SAC Code 996812 — Courier services.<br>
                <?php if ($s['ewaybill_no']): ?>E-Waybill No: <?= h($s['ewaybill_no']) ?><br><?php endif; ?>
                Subject to jurisdiction of courts in Mumbai, Maharashtra.
            </div>
            <div class="inv-totals">
                <table>
                    <tr><td>Taxable Amount</td><td class="td-right">₹<?= number_format($taxable + ($s['packing_charge'] > 0 ? $packTaxable : 0), 2) ?></td></tr>
                    <?php if ($sameState): ?>
                    <tr><td>CGST @ 9%</td><td class="td-right">₹<?= number_format($cgst + ($s['packing_charge'] > 0 ? $packTax/2 : 0), 2) ?></td></tr>
                    <tr><td>SGST @ 9%</td><td class="td-right">₹<?= number_format($sgst + ($s['packing_charge'] > 0 ? $packTax/2 : 0), 2) ?></td></tr>
                    <?php else: ?>
                    <tr><td>IGST @ 18%</td><td class="td-right">₹<?= number_format($igst + ($s['packing_charge'] > 0 ? $packTax : 0), 2) ?></td></tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td style="padding:10px 12px;">TOTAL AMOUNT</td>
                        <td class="td-right" style="padding:10px 12px;">₹<?= number_format($baseAmt + (float)($s['packing_charge'] ?? 0), 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

    </div><!-- /inv-body -->

    <!-- Footer -->
    <div class="inv-footer">
        <p style="margin-bottom:8px;">This is a computer-generated invoice and does not require a physical signature.</p>
        <p>For queries: <?= h($companyEmail) ?> | <?= h($companyPhone) ?></p>
        <p style="margin-top:8px;font-weight:600;color:#374151;">Thank you for choosing Careygo!</p>
    </div>

</div><!-- /invoice-wrap -->

</body>
</html>
