<?php
/**
 * POST /api/shipments.php  — create new shipment (customer)
 * GET  /api/shipments.php  — list customer's own shipments
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/email.php';

header('Content-Type: application/json');

// Debug mode - log all errors
define('DEBUG_MODE', false); // Set to true to see detailed errors

$user = auth_user();
if (!$user || $user['role'] !== 'customer') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}
$userId = (int) $user['sub'];

function awbModeCode(string $serviceType): string
{
    return [
        'standard'  => 'EE',
        'premium'   => 'PE',
        'air_cargo' => 'AC',
        'surface'   => 'SC',
    ][$serviceType] ?? 'EE';
}

function generateAwbNumber(PDO $pdo, string $serviceType, string $franchiseeId, DateTimeInterface $createdAt): string
{
    $mode = awbModeCode($serviceType);
    $datePart = $createdAt->format('dmy');
    $prefix = $mode . ' ' . $franchiseeId . ' ' . $datePart;

    $stmt = $pdo->prepare("SELECT tracking_no FROM shipments WHERE tracking_no LIKE ? ORDER BY tracking_no DESC LIMIT 1");
    $stmt->execute([$prefix . ' %']);
    $last = (string)($stmt->fetchColumn() ?: '');
    $sequence = 1;
    if (preg_match('/(\d{5})$/', $last, $m)) {
        $sequence = ((int)$m[1]) + 1;
    }

    return $prefix . ' ' . str_pad((string)$sequence, 5, '0', STR_PAD_LEFT);
}

function ensureEarningColumns(PDO $pdo): void
{
    static $done = false;
    if ($done) return;

    try {
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_earning_pct', $userCols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN customer_earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        }

        $shipmentCols = $pdo->query("SHOW COLUMNS FROM shipments")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_earning_pct', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN customer_earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00");
        }
        if (!in_array('customer_earning_amount', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN customer_earning_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (Exception $e) {
        @error_log('Earning column check failed: ' . $e->getMessage());
    }

    $done = true;
}

ensureEarningColumns($pdo);

// Verify customer is approved
$stmt = $pdo->prepare('SELECT status, customer_earning_pct FROM users WHERE id = ?');
$stmt->execute([$userId]);
$customerRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customerRow || $customerRow['status'] !== 'approved') {
    json_response(['success' => false, 'message' => 'Account not approved.'], 403);
}
$customerEarningPct = max(0.0, min(100.0, (float)($customerRow['customer_earning_pct'] ?? 0)));

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $filter = $_GET['status'] ?? '';
    $where  = "WHERE customer_id = ?";
    $params = [$userId];
    if ($filter) { $where .= " AND status = ?"; $params[] = $filter; }
    try {
        $stmt = $pdo->prepare("SELECT * FROM shipments $where ORDER BY created_at DESC");
        $stmt->execute($params);
        json_response(['success' => true, 'shipments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Failed to fetch shipments.'], 500);
    }
}

if ($method === 'POST') {
    try {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // ── Extract and sanitize ──
        $pickup   = $body['pickup']   ?? [];
        $delivery = $body['delivery'] ?? [];

        $pickupName    = trim($pickup['name']  ?? '');
        $pickupCompany = trim($pickup['company'] ?? '');
        $pickupPhone   = trim($pickup['phone'] ?? '');
        $pickupAddr1   = trim($pickup['addr1'] ?? '');
        $pickupAddr2   = trim($pickup['addr2'] ?? '');
        $pickupCity    = trim($pickup['city']  ?? '');
        $pickupState   = trim($pickup['state'] ?? '');
        $pickupPincode = trim($pickup['pincode'] ?? '');
        $pickupGstin   = trim($pickup['gstin'] ?? '');

        $delivName    = trim($delivery['name']  ?? '');
        $delivCompany = trim($delivery['company'] ?? '');
        $delivPhone   = trim($delivery['phone'] ?? '');
        $delivAddr1   = trim($delivery['addr1'] ?? '');
        $delivAddr2   = trim($delivery['addr2'] ?? '');
        $delivCity    = trim($delivery['city']  ?? '');
        $delivState   = trim($delivery['state'] ?? '');
        $delivPincode = trim($delivery['pincode'] ?? '');
        $delivGstin   = trim($delivery['gstin'] ?? '');

        $serviceType      = trim($body['service_type']      ?? '');
        $weight           = (float)  ($body['weight']           ?? 0);
        $chargeableWeight = (float)  ($body['chargeable_weight'] ?? $weight);
        $pieces           = (int)    ($body['pieces']           ?? 1);
        $declaredValue    = (float)  ($body['declared_value']   ?? 0);
        $description      = trim($body['description']      ?? '');
        $customerRef      = trim($body['customer_ref']     ?? '');
        $ewaybillNo       = trim($body['ewaybill_no']      ?? '');
        $packingMaterial  = (int)    ($body['packing_material']  ?? 0);
        $packingCharge    = (float)  ($body['packing_charge']   ?? 0);
        $photoAddress     = trim($body['photo_address']    ?? '');
        $photoParcel      = trim($body['photo_parcel']     ?? '');

        // Dimensions
        $length           = (float)  ($body['length']           ?? 0);
        $width            = (float)  ($body['width']            ?? 0);
        $height           = (float)  ($body['height']           ?? 0);
        $volWeight        = (float)  ($body['volumetric_weight'] ?? 0);
        $basePrice        = (float)  ($body['base_price']       ?? 0);
        $finalPrice       = (float)  ($body['final_price']      ?? $basePrice);
        $discountPct      = 0;  // Discount removed
        $discountAmt      = 0;
        $paymentMethod    = trim($body['payment_method']   ?? 'prepaid');
        $gstInvoice       = (int)    ($body['gst_invoice']      ?? 0);
        $gstin            = trim($body['gstin']            ?? '');
        $panNumber        = trim($body['pan_number']       ?? '');
        $riskSurcharge    = 'owner';
        $franchiseeId     = 'PNQ01';
        $createdAtIst     = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
        $createdAtSql     = $createdAtIst->format('Y-m-d H:i:s');

        // Columns chargeable_weight, packing_charge, photo_address, photo_parcel were added via migration.
        if ($gstin === '') {
            $gstin = $pickupGstin ?: $delivGstin;
        }
        if ($gstin !== '' || $pickupGstin !== '' || $delivGstin !== '') {
            $gstInvoice = 1;
        }

        if ($packingMaterial) {
            $packingCharge = 50.0;
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT NOT NULL DEFAULT '',
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                $settingStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $settingStmt->execute(['packing_charge']);
                $settingValue = $settingStmt->fetchColumn();
                if ($settingValue !== false && is_numeric($settingValue)) {
                    $packingCharge = max(0.0, (float)$settingValue);
                }
            } catch (Exception $e) {}
        } else {
            $packingCharge = 0.0;
        }
        $finalPrice = round($basePrice + $packingCharge, 2);
        $customerEarningAmount = round($finalPrice * $customerEarningPct / 100);

        // Validation
        $allowed = ['standard','premium','air_cargo','surface'];
        if (!in_array($serviceType, $allowed)) {
            json_response(['success' => false, 'message' => 'Invalid service type.'], 422);
        }
        if ($weight <= 0) json_response(['success' => false, 'message' => 'Invalid weight.'], 422);
        if ($chargeableWeight <= 0) $chargeableWeight = $weight;
        if ($declaredValue <= 0) {
            json_response(['success' => false, 'message' => 'Total value of consignment is required.'], 422);
        }

        // Service weight constraints (production-ready)
        $serviceConstraints = [
            'standard'  => 60.000,
            'premium'   => 60.000,
            'air_cargo' => 60.000,
            'surface'   => 60.000,
        ];
        $maxWeight = $serviceConstraints[$serviceType] ?? PHP_FLOAT_MAX;
        if ($chargeableWeight > $maxWeight) {
            json_response([
                'success' => false,
                'message' => "Chargeable weight exceeds limit for $serviceType service. Maximum: {$maxWeight} kg"
            ], 422);
        }
        if (!$pickupName || !$pickupPhone || !$pickupAddr1 || !$pickupCity || !$pickupPincode) {
            json_response(['success' => false, 'message' => 'Pickup address is incomplete.'], 422);
        }
        if (!$delivName || !$delivPhone || !$delivAddr1 || !$delivCity || !$delivPincode) {
            json_response(['success' => false, 'message' => 'Delivery address is incomplete.'], 422);
        }
        if (!in_array($paymentMethod, ['prepaid','cod','credit'])) $paymentMethod = 'prepaid';

        // Generate AWB: [Mode]-[FranchiseeID]-[Date]-[Sequence], displayed with spaces.
        $tracking = generateAwbNumber($pdo, $serviceType, $franchiseeId, $createdAtIst);

        // Estimate delivery date — use tatColumn() helper to fix air_cargo → tat_air mapping
        $tatCol  = tatColumn($serviceType);
        $tatStmt = $pdo->prepare("SELECT `{$tatCol}` FROM pincode_tat WHERE pincode = ? LIMIT 1");
        $tatStmt->execute([$delivPincode]);
        $tat     = $tatStmt->fetchColumn();
        $tatDays = ($tat !== false && $tat > 0) ? (int)$tat : 3;
        $etaDate = addBusinessDays($tatDays);

        $pickupFull   = $pickupAddr1 . ($pickupAddr2 ? ', ' . $pickupAddr2 : '');
        $delivFull    = $delivAddr1  . ($delivAddr2  ? ', ' . $delivAddr2  : '');

        $stmt = $pdo->prepare("
            INSERT INTO shipments (
                tracking_no, customer_id,
                pickup_name, pickup_company_name, pickup_phone, pickup_address, pickup_city, pickup_state, pickup_pincode, pickup_gstin,
                delivery_name, delivery_company_name, delivery_phone, delivery_address, delivery_city, delivery_state, delivery_pincode, delivery_gstin,
                service_type, weight, chargeable_weight, volumetric_weight, length, width, height, declared_value, pieces, description, customer_ref,
                ewaybill_no, packing_material, packing_charge, photo_address, photo_parcel,
                base_price, discount_pct, discount_amount, final_price,
                customer_earning_pct, customer_earning_amount,
                payment_method, risk_surcharge, gst_invoice, gstin, pan_number,
                status, estimated_delivery, created_at
            ) VALUES (
                ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, ?,
                'booked', ?, ?
            )
        ");
        $stmt->execute([
            $tracking, $userId,
            $pickupName, $pickupCompany, $pickupPhone, $pickupFull, $pickupCity, $pickupState, $pickupPincode, $pickupGstin,
            $delivName, $delivCompany, $delivPhone, $delivFull, $delivCity, $delivState, $delivPincode, $delivGstin,
            $serviceType, $weight, $chargeableWeight, $volWeight, $length, $width, $height, $declaredValue, $pieces, $description, $customerRef,
            $ewaybillNo, $packingMaterial, $packingCharge, $photoAddress ?: null, $photoParcel ?: null,
            $basePrice, $discountPct, $discountAmt, $finalPrice,
            $customerEarningPct, $customerEarningAmount,
            $paymentMethod, $riskSurcharge, $gstInvoice, $gstin, $panNumber,
            $etaDate, $createdAtSql,
        ]);

        $shipmentId = (int) $pdo->lastInsertId();

        // Save new addresses if flagged
        if (!empty($body['save_pickup_address'])) {
            $pdo->prepare("INSERT IGNORE INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, pincode) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$userId, $pickupName, $pickupPhone, $pickupAddr1, $pickupAddr2, $pickupCity, $pickupState, $pickupPincode]);
        }
        if (!empty($body['save_delivery_address'])) {
            $pdo->prepare("INSERT IGNORE INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, pincode) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$userId, $delivName, $delivPhone, $delivAddr1, $delivAddr2, $delivCity, $delivState, $delivPincode]);
        }

        // ── Send notification emails ──────────────────────────────
        try {
            // Fetch customer details
            $custStmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
            $custStmt->execute([$userId]);
            $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

            // Build shipment array for email functions
            $shipmentData = [
                'id'                  => $shipmentId,
                'tracking_no'         => $tracking,
                'pickup_name'         => $pickupName,
                'pickup_phone'        => $pickupPhone,
                'pickup_address'      => $pickupFull,
                'pickup_city'         => $pickupCity,
                'pickup_state'        => $pickupState,
                'pickup_pincode'      => $pickupPincode,
                'delivery_name'       => $delivName,
                'delivery_phone'      => $delivPhone,
                'delivery_address'    => $delivFull,
                'delivery_city'       => $delivCity,
                'delivery_state'      => $delivState,
                'delivery_pincode'    => $delivPincode,
                'service_type'        => $serviceType,
                'weight'              => $weight,
                'pieces'              => $pieces,
                'description'         => $description,
                'declared_value'      => $declaredValue,
                'base_price'          => $basePrice,
                'discount_pct'        => $discountPct,
                'discount_amount'     => $discountAmt,
                'final_price'         => $finalPrice,
                'customer_earning_pct' => $customerEarningPct,
                'customer_earning_amount' => $customerEarningAmount,
                'chargeable_weight'   => $chargeableWeight,
                'packing_material'    => $packingMaterial,
                'packing_charge'      => $packingCharge,
                'gst_invoice'         => $gstInvoice,
                'gstin'               => $gstin,
                'pan_number'          => $panNumber,
                'pickup_gstin'        => $pickupGstin,
                'delivery_gstin'      => $delivGstin,
                'volumetric_weight'   => $volWeight,
                'length'              => $length,
                'width'               => $width,
                'height'              => $height,
                'payment_method'      => $paymentMethod,
                'created_at'          => $createdAtSql,
                'estimated_delivery'  => $etaDate,
                'delivery_email'      => $body['delivery_email'] ?? null,
                'franchisee_id'       => $franchiseeId,
            ];

            $emailService = new EmailService();

            // 1. Send booking confirmation to sender (customer)
            if ($customer && $customer['email']) {
                $emailService->sendSenderConfirmation($customer, $shipmentData);
            }

            // 2. Send AWB receipt to sender
            if ($customer && $customer['email']) {
                $emailService->sendAWBReceipt($customer, $shipmentData);
            }

            // 3. Send notification to receiver (if email provided in body)
            if (!empty($body['delivery_email'])) {
                $receiver = [
                    'name'  => $delivName,
                    'phone' => $delivPhone,
                ];
                $emailService->sendReceiverNotification($receiver, $shipmentData, $customer);
            }
        } catch (Exception $e) {
            // Log email errors but don't fail the booking
            @error_log('Email sending failed for booking ' . $tracking . ': ' . $e->getMessage());
        }

        json_response([
            'success'     => true,
            'tracking_no' => $tracking,
            'id'          => $shipmentId,
            'eta'         => $etaDate,
            'gst_invoice' => (bool) $gstInvoice,
        ]);
    } catch (Exception $e) {
        // Log full error details
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        error_log('SHIPMENT_ERROR: ' . json_encode($errorLog));

        // Duplicate tracking — retry once
        if ($e->getCode() == 23000) {
            json_response(['success' => false, 'message' => 'Booking already exists, please try again.'], 500);
        }

        // Return appropriate error message
        $message = DEBUG_MODE ? $e->getMessage() : 'Failed to create shipment. Please contact support.';
        json_response(['success' => false, 'message' => $message, 'error_code' => $e->getCode()], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
