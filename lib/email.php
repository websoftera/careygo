<?php
/**
 * Email Service — sends booking confirmations and AWB receipts
 * Supports both PHP mail() and SMTP (via PHPMailer)
 */

// Try to load PHPMailer (optional for SMTP support)
$usePhpMailer = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $usePhpMailer = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

require_once __DIR__ . '/receipt_pdf.php';

class EmailService
{
    private $from;
    private $fromName;
    private $replyTo;
    private $adminEmail;
    private $usePhpMailer;

    public function __construct()
    {
        global $usePhpMailer;

        $this->fromName     = 'Careygo Logistics';
        $this->from         = env('CGO_EMAIL_FROM', 'noreply@careygo.in');
        $this->replyTo      = env('CGO_EMAIL_REPLY', 'support@careygo.in');
        $this->adminEmail   = ADMIN_EMAIL;
        $this->usePhpMailer = $usePhpMailer;
    }

    /**
     * Send booking confirmation to sender (customer)
     */
    public function sendSenderConfirmation(array $customer, array $shipment): bool
    {
        $to      = $customer['email'];
        $subject = "✅ Your Booking Confirmed - Tracking: {$shipment['tracking_no']}";
        $body    = $this->buildSenderEmail($customer, $shipment);

        return $this->send($to, $customer['full_name'], $subject, $body);
    }

    /**
     * Send AWB notification to receiver
     */
    public function sendReceiverNotification(array $receiver, array $shipment, array $customer): bool
    {
        // Receiver email might not be in system, use delivery_email if provided
        $to = $shipment['delivery_email'] ?? null;
        if (!$to) {
            return false; // Can't send without receiver email
        }

        $subject = "📦 Incoming Courier - {$customer['full_name']} has sent you a package";
        $body    = $this->buildReceiverEmail($receiver, $shipment, $customer);

        return $this->send($to, $receiver['name'], $subject, $body);
    }

    /**
     * Send AWB receipt as attachment (sender)
     */
    public function sendAWBReceipt(array $customer, array $shipment): bool
    {
        $to      = $customer['email'];
        $subject = "📄 Your AWB Receipt - {$shipment['tracking_no']}";
        $body    = $this->buildReceiptEmail($customer, $shipment);

        // Generate PDF
        $pdf = generateReceiptPDF($shipment);
        $pdfContent = $pdf->Output('S');

        $attachment = [
            'name' => 'Careygo_Receipt_' . $shipment['tracking_no'] . '.pdf',
            'data' => $pdfContent
        ];

        return $this->send($to, $customer['full_name'], $subject, $body, $attachment);
    }

    /**
     * Build sender confirmation email
     */
    private function buildSenderEmail(array $customer, array $shipment): string
    {
        $trackingNo   = htmlspecialchars($shipment['tracking_no']);
        $deliveryName = htmlspecialchars($shipment['delivery_name']);
        $deliveryAddr = htmlspecialchars($shipment['delivery_address']);
        $deliveryCity = htmlspecialchars($shipment['delivery_city']);
        $deliveryPin  = htmlspecialchars($shipment['delivery_pincode']);
        $weight       = number_format($shipment['weight'], 3);
        $price        = number_format($shipment['final_price'], 2);
        $eta          = date('d M Y', strtotime($shipment['estimated_delivery']));
        $serviceLabels = [
            'standard'   => 'Standard Express',
            'premium'    => 'Premium Express',
            'air_cargo'  => 'Air Cargo',
            'surface'    => 'Surface Cargo',
        ];
        $service = $serviceLabels[$shipment['service_type']] ?? ucwords(str_replace('_', ' ', $shipment['service_type']));

        $siteUrl = rtrim(SITE_URL, '/');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,26,147,0.1); }
        .header { background: linear-gradient(135deg, #001A93 0%, #3B5BDB 100%); color: #fff; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0; font-size: 14px; opacity: 0.9; }
        .logo { height: 40px; margin-bottom: 15px; }
        .content { padding: 30px 20px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #001A93; margin-bottom: 12px; border-bottom: 2px solid #001A93; padding-bottom: 8px; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .detail-label { font-weight: 600; color: #666; }
        .detail-value { color: #333; text-align: right; }
        .highlight { background: #f0f2f9; padding: 15px; border-radius: 8px; border-left: 4px solid #001A93; margin: 15px 0; }
        .tracking-box { font-size: 18px; font-weight: 700; color: #001A93; font-family: 'Courier New', monospace; }
        .button { display: inline-block; background: #001A93; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 15px; font-weight: 600; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e4e7f0; }
        .footer a { color: #001A93; text-decoration: none; }
        .success-icon { font-size: 48px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>Booking Confirmed!</h1>
            <p>Your shipment has been successfully registered</p>
        </div>

        <div class="content">
            <div class="highlight">
                <p style="margin: 0; font-size: 12px; color: #666;">Your Tracking Number</p>
                <p class="tracking-box">{$trackingNo}</p>
                <p style="margin: 8px 0 0; font-size: 12px; color: #666;">Keep this for your records</p>
            </div>

            <div class="section">
                <div class="section-title">📦 Shipment Details</div>
                <div class="detail-row">
                    <span class="detail-label">Service Type:</span>
                    <span class="detail-value">{$service}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Weight:</span>
                    <span class="detail-value">{$weight} kg</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value" style="font-weight: 700; color: #001A93;">₹{$price}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estimated Delivery:</span>
                    <span class="detail-value">{$eta}</span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">🎯 Delivery Address</div>
                <p style="margin: 0; font-size: 14px; line-height: 1.6;">
                    <strong>{$deliveryName}</strong><br>
                    {$deliveryAddr}<br>
                    {$deliveryCity}, {$deliveryPin}
                </p>
            </div>

            <div class="section">
                <p style="font-size: 13px; color: #666; margin: 0;">
                    Your shipment is now in our system and will be picked up shortly. You can track your shipment anytime using your tracking number.
                </p>
                <a href="{$siteUrl}/customer/tracking.php?id={$shipment['id']}" class="button">Track Your Shipment →</a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 10px;">Need help? <a href="mailto:support@careygo.in">Contact our support team</a></p>
            <p style="margin: 0;">© 2026 Careygo Logistics. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Build receiver notification email
     */
    private function buildReceiverEmail(array $receiver, array $shipment, array $customer): string
    {
        $senderName   = htmlspecialchars($customer['full_name']);
        $trackingNo   = htmlspecialchars($shipment['tracking_no']);
        $pickupCity   = htmlspecialchars($shipment['pickup_city']);
        $deliveryCity = htmlspecialchars($shipment['delivery_city']);
        $weight       = number_format($shipment['weight'], 3);
        $eta          = date('d M Y', strtotime($shipment['estimated_delivery']));
        $description  = htmlspecialchars($shipment['description'] ?? 'Not specified');

        $siteUrl = rtrim(SITE_URL, '/');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incoming Courier</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,26,147,0.1); }
        .header { background: linear-gradient(135deg, #001A93 0%, #3B5BDB 100%); color: #fff; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 26px; }
        .header p { margin: 10px 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #001A93; margin-bottom: 12px; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
        .detail-label { font-weight: 600; color: #666; }
        .detail-value { color: #333; text-align: right; }
        .highlight { background: #fff8e1; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 15px 0; }
        .tracking-box { font-size: 16px; font-weight: 700; color: #001A93; font-family: 'Courier New', monospace; }
        .button { display: inline-block; background: #001A93; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 15px; font-weight: 600; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e4e7f0; }
        .footer a { color: #001A93; text-decoration: none; }
        .package-icon { font-size: 48px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="package-icon">📦</div>
            <h1>Incoming Courier!</h1>
            <p>{$senderName} has sent you a package</p>
        </div>

        <div class="content">
            <div class="section">
                <p style="font-size: 14px; margin: 0;">
                    Hello {$receiver['name']},<br><br>
                    {$senderName} has sent you a shipment via <strong>Careygo Logistics</strong>. Please find the details below:
                </p>
            </div>

            <div class="highlight">
                <p style="margin: 0; font-size: 12px; color: #856404;">Tracking Number</p>
                <p class="tracking-box">{$trackingNo}</p>
                <p style="margin: 8px 0 0; font-size: 12px; color: #856404;">Use this to track your package</p>
            </div>

            <div class="section">
                <div class="section-title">📋 Shipment Information</div>
                <div class="detail-row">
                    <span class="detail-label">Sender:</span>
                    <span class="detail-value">{$senderName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">From:</span>
                    <span class="detail-value">{$pickupCity}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">To:</span>
                    <span class="detail-value">{$deliveryCity}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Weight:</span>
                    <span class="detail-value">{$weight} kg</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contents:</span>
                    <span class="detail-value">{$description}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expected Delivery:</span>
                    <span class="detail-value"><strong>{$eta}</strong></span>
                </div>
            </div>

            <div class="section">
                <p style="font-size: 13px; color: #666; margin: 0;">
                    Your package is on its way! You can track its progress in real-time using the tracking number above.
                </p>
                <a href="{$siteUrl}/public-tracking.php?tracking={$trackingNo}" class="button">Track Package →</a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 10px;">Questions? <a href="mailto:support@careygo.in">Contact Careygo Support</a></p>
            <p style="margin: 0;">© 2026 Careygo Logistics. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Build AWB receipt email (detailed invoice/receipt)
     */
    private function buildReceiptEmail(array $customer, array $shipment): string
    {
        $trackingNo      = htmlspecialchars($shipment['tracking_no']);
        $pickupName      = htmlspecialchars($shipment['pickup_name']);
        $pickupPhone     = htmlspecialchars($shipment['pickup_phone']);
        $pickupAddr      = htmlspecialchars($shipment['pickup_address']);
        $pickupCity      = htmlspecialchars($shipment['pickup_city']);
        $pickupState     = htmlspecialchars($shipment['pickup_state']);
        $pickupPin       = htmlspecialchars($shipment['pickup_pincode']);

        $delivName       = htmlspecialchars($shipment['delivery_name']);
        $delivPhone      = htmlspecialchars($shipment['delivery_phone']);
        $delivAddr       = htmlspecialchars($shipment['delivery_address']);
        $delivCity       = htmlspecialchars($shipment['delivery_city']);
        $delivState      = htmlspecialchars($shipment['delivery_state']);
        $delivPin        = htmlspecialchars($shipment['delivery_pincode']);

        $weight          = number_format($shipment['weight'], 3);
        $pieces          = (int)$shipment['pieces'];
        $description     = htmlspecialchars($shipment['description'] ?? '—');
        $declaredValue   = number_format($shipment['declared_value'], 2);
        $basePrice       = number_format($shipment['base_price'], 2);
        $discount        = $shipment['discount_pct'] > 0 ? $shipment['discount_pct'] . '%' : '0%';
        $discountAmt     = number_format($shipment['discount_amount'], 2);
        $finalPrice      = number_format($shipment['final_price'], 2);
        $bookingDate     = date('d M Y H:i', strtotime($shipment['created_at']));
        $eta             = date('d M Y', strtotime($shipment['estimated_delivery']));

        $serviceLabels = [
            'standard'   => 'Standard Express',
            'premium'    => 'Premium Express',
            'air_cargo'  => 'Air Cargo',
            'surface'    => 'Surface Cargo',
        ];
        $service = $serviceLabels[$shipment['service_type']] ?? ucwords(str_replace('_', ' ', $shipment['service_type']));

        $qrCode = urlencode($shipment['tracking_no']);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWB Receipt - {$trackingNo}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; }
        .awb-container { max-width: 900px; margin: 0 auto; background: #fff; }
        .awb-header { background: linear-gradient(135deg, #001A93 0%, #3B5BDB 100%); color: #fff; padding: 30px; text-align: center; }
        .awb-header h1 { font-size: 32px; margin-bottom: 5px; }
        .awb-header p { font-size: 14px; opacity: 0.9; }
        .awb-logo { font-size: 48px; margin-bottom: 10px; }
        .awb-content { padding: 30px; }
        .section { margin-bottom: 30px; border: 1px solid #e4e7f0; border-radius: 8px; overflow: hidden; }
        .section-header { background: #f0f2f9; padding: 15px 20px; border-bottom: 2px solid #001A93; font-weight: 700; color: #001A93; font-size: 13px; text-transform: uppercase; }
        .section-body { padding: 20px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .row.full { grid-template-columns: 1fr; }
        .field { }
        .field-label { font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .field-value { font-size: 14px; color: #333; }
        .tracking-number { font-size: 24px; font-weight: 700; font-family: 'Courier New', monospace; color: #001A93; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th { background: #f0f2f9; padding: 10px; text-align: left; font-size: 12px; font-weight: 700; color: #001A93; border-bottom: 2px solid #001A93; }
        .table td { padding: 12px 10px; border-bottom: 1px solid #e4e7f0; font-size: 13px; }
        .table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-bold { font-weight: 700; }
        .amount { font-weight: 700; color: #001A93; }
        .divider { border-bottom: 2px solid #e4e7f0; margin: 15px 0; }
        .total-row { background: #f0f2f9; font-weight: 700; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 11px; color: #666; border-top: 1px solid #e4e7f0; }
        .qr-code { text-align: center; margin: 20px 0; }
        .qr-code img { max-width: 150px; }
        .status-badge { display: inline-block; background: #4caf50; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .print-btn { display: block; width: 200px; margin: 20px auto; padding: 12px; background: #001A93; color: #fff; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; }
        @media print {
            body { background: #fff; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="awb-container">
        <div class="awb-header">
            <div class="awb-logo">📦</div>
            <h1>CAREYGO LOGISTICS</h1>
            <p>Air Waybill Receipt</p>
        </div>

        <div class="awb-content">
            <!-- Tracking Number -->
            <div class="section">
                <div class="section-header">📌 Tracking Information</div>
                <div class="section-body">
                    <div style="text-align: center; padding: 20px; background: #f0f2f9; border-radius: 8px;">
                        <div class="field-label">Your Tracking Number</div>
                        <div class="tracking-number">{$trackingNo}</div>
                        <div class="qr-code">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={$qrCode}" alt="QR Code">
                        </div>
                        <div style="margin-top: 10px;">
                            <span class="status-badge">✓ BOOKED</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sender Details -->
            <div class="section">
                <div class="section-header">📤 Sender Details</div>
                <div class="section-body">
                    <div class="row">
                        <div class="field">
                            <div class="field-label">Name</div>
                            <div class="field-value">{$pickupName}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">Phone</div>
                            <div class="field-value">{$pickupPhone}</div>
                        </div>
                    </div>
                    <div class="row full">
                        <div class="field">
                            <div class="field-label">Address</div>
                            <div class="field-value">{$pickupAddr}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <div class="field-label">City</div>
                            <div class="field-value">{$pickupCity}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">State</div>
                            <div class="field-value">{$pickupState}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <div class="field-label">Pincode</div>
                            <div class="field-value">{$pickupPin}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receiver Details -->
            <div class="section">
                <div class="section-header">📥 Receiver Details</div>
                <div class="section-body">
                    <div class="row">
                        <div class="field">
                            <div class="field-label">Name</div>
                            <div class="field-value">{$delivName}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">Phone</div>
                            <div class="field-value">{$delivPhone}</div>
                        </div>
                    </div>
                    <div class="row full">
                        <div class="field">
                            <div class="field-label">Address</div>
                            <div class="field-value">{$delivAddr}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <div class="field-label">City</div>
                            <div class="field-value">{$delivCity}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">State</div>
                            <div class="field-value">{$delivState}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <div class="field-label">Pincode</div>
                            <div class="field-value">{$delivPin}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipment Details -->
            <div class="section">
                <div class="section-header">📦 Shipment Details</div>
                <div class="section-body">
                    <table class="table">
                        <tr>
                            <td><strong>Service Type</strong></td>
                            <td class="text-right">{$service}</td>
                        </tr>
                        <tr>
                            <td><strong>Weight</strong></td>
                            <td class="text-right">{$weight} kg</td>
                        </tr>
                        <tr>
                            <td><strong>Pieces</strong></td>
                            <td class="text-right">{$pieces}</td>
                        </tr>
                        <tr>
                            <td><strong>Description</strong></td>
                            <td class="text-right">{$description}</td>
                        </tr>
                        <tr>
                            <td><strong>Declared Value</strong></td>
                            <td class="text-right amount">₹{$declaredValue}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Pricing -->
            <div class="section">
                <div class="section-header">💰 Billing Information</div>
                <div class="section-body">
                    <table class="table">
                        <tr>
                            <td><strong>Base Price</strong></td>
                            <td class="text-right">₹{$basePrice}</td>
                        </tr>
                        <tr>
                            <td><strong>Discount ({$discount})</strong></td>
                            <td class="text-right">- ₹{$discountAmt}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total Amount</strong></td>
                            <td class="text-right amount">₹{$finalPrice}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="section">
                <div class="section-header">ℹ️ Booking Information</div>
                <div class="section-body">
                    <div class="row">
                        <div class="field">
                            <div class="field-label">Booking Date & Time</div>
                            <div class="field-value">{$bookingDate}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">Estimated Delivery</div>
                            <div class="field-value">{$eta}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms -->
            <div style="background: #f9fafb; padding: 20px; margin-top: 30px; border-radius: 8px; font-size: 11px; color: #666; line-height: 1.6;">
                <strong>Terms & Conditions:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>This is a binding receipt for the shipment. Please retain for your records.</li>
                    <li>Goods are accepted at owner's risk unless insurance is specifically purchased.</li>
                    <li>Careygo Logistics is not responsible for any delays due to unforeseen circumstances.</li>
                    <li>For any queries or claims, contact support@careygo.in within 7 days of delivery.</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p><strong>Careygo Logistics</strong> | Email: support@careygo.in | Phone: +91-98502-96178</p>
            <p>© 2026 Careygo Logistics. All rights reserved.</p>
            <p style="margin-top: 15px;">This document was generated on {$bookingDate}</p>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
</body>
</html>
HTML;
    }

    /**
     * Send email via SMTP (PHPMailer) or PHP mail()
     */
    private function send(string $to, string $toName, string $subject, string $htmlBody, array $attachment = null): bool
    {
        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Try SMTP first if available
        if ($this->usePhpMailer && env('SMTP_ENABLED', '') === '1') {
            return $this->sendViaSMTP($to, $toName, $subject, $htmlBody, $attachment);
        }

        // Fall back to PHP mail()
        return $this->sendViaPhpMail($to, $toName, $subject, $htmlBody, $attachment);
    }

    /**
     * Send via SMTP (PHPMailer)
     */
    private function sendViaSMTP(string $to, string $toName, string $subject, string $htmlBody, array $attachment = null): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('SMTP_USER', '');
            $mail->Password   = env('SMTP_PASS', '');
            $mail->SMTPSecure = env('SMTP_SECURE', 'tls'); // tls or ssl
            $mail->Port       = (int) env('SMTP_PORT', '587');
            $mail->Timeout    = 30;

            // Email details
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to, $toName);
            $mail->addReplyTo($this->replyTo);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            if ($attachment) {
                $mail->addStringAttachment($attachment['data'], $attachment['name'], 'base64', 'application/pdf');
            }

            // Send
            $result = $mail->send();

            // Log attempt
            $this->logEmail($to, $subject, $htmlBody, 'SMTP', true);

            return true;
        } catch (\Exception $e) {
            // Log error
            @error_log('SMTP Email failed to ' . $to . ': ' . $e->getMessage());
            $this->logEmail($to, $subject, $htmlBody, 'SMTP', false, $e->getMessage());

            return false;
        }
    }

    /**
     * Send via PHP mail()
     */
    private function sendViaPhpMail(string $to, string $toName, string $subject, string $htmlBody, array $attachment = null): bool
    {
        $boundary = md5(time());
        
        $headers = [
            "MIME-Version: 1.0",
            "From: {$this->fromName} <{$this->from}>",
            "Reply-To: {$this->replyTo}",
            "X-Mailer: Careygo/1.0",
        ];

        if ($attachment) {
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: application/pdf; name=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($attachment['data'])) . "\r\n";
            $message .= "--{$boundary}--";
        } else {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $message = $htmlBody;
        }

        $headerStr = implode("\r\n", $headers);

        // Attempt to send via mail()
        $result = @mail($to, $subject, $message, $headerStr);

        // Log attempt
        $this->logEmail($to, $subject, $htmlBody, 'PHP-Mail', $result);

        return $result;
    }

    /**
     * Log email for debugging
     */
    private function logEmail(string $to, string $subject, string $body, string $method, bool $success, string $error = ''): void
    {
        $logDir = sys_get_temp_dir() . '/cgo_emails/';
        if (!is_dir($logDir)) @mkdir($logDir, 0700, true);

        $logFile = $logDir . date('Y-m-d_H-i-s') . '_' . md5($to . $subject) . '.txt';
        $logContent = sprintf(
            "Method: %s\nTo: %s\nSubject: %s\nStatus: %s\n%s\n\nBody:\n%s\n\n---\nLogged at: %s\n",
            $method,
            $to,
            $subject,
            $success ? 'SUCCESS' : 'FAILED',
            $error ? 'Error: ' . $error : '',
            substr($body, 0, 500),
            date('Y-m-d H:i:s')
        );

        @file_put_contents($logFile, $logContent, FILE_APPEND);
    }
}
