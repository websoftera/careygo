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

    $stmt = $pdo->prepare("SELECT tracking_no FROM shipments WHERE tracking_no LIKE ? ORDER BY CAST(RIGHT(tracking_no, 5) AS UNSIGNED) DESC LIMIT 1");
    $stmt->execute([$mode . ' ' . $franchiseeId . ' %']);
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
        if (!in_array('pickup_email', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN pickup_email VARCHAR(191) DEFAULT NULL");
        }
        if (!in_array('delivery_email', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN delivery_email VARCHAR(191) DEFAULT NULL");
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS customer_earning_slabs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id INT UNSIGNED NOT NULL,
            pricing_slab_id INT UNSIGNED NOT NULL,
            earning_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_customer_slab (customer_id, pricing_slab_id),
            INDEX idx_customer_id (customer_id),
            INDEX idx_pricing_slab_id (pricing_slab_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {
        @error_log('Earning column check failed: ' . $e->getMessage());
    }

    $done = true;
}

function resolveShipmentZone(PDO $pdo, string $pickupPincode, string $deliveryPincode, string $pickupCity, string $pickupState, string $deliveryCity, string $deliveryState): string
{
    $pickupRow = null;
    $deliveryRow = null;
    try {
        $stmt = $pdo->prepare("SELECT city, state FROM pincode_tat WHERE pincode = ? LIMIT 1");
        $stmt->execute([$pickupPincode]);
        $pickupRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt->execute([$deliveryPincode]);
        $deliveryRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {}

    $pCity = trim($pickupRow['city'] ?? $pickupCity);
    $pState = trim($pickupRow['state'] ?? $pickupState);
    $dCity = trim($deliveryRow['city'] ?? $deliveryCity);
    $dState = trim($deliveryRow['state'] ?? $deliveryState);

    if ($pickupPincode !== '' && $pickupPincode === $deliveryPincode) return 'within_city';
    if ($pCity !== '' && $dCity !== '' && $pState !== '' && $dState !== '') {
        return determineZone($pCity, $pState, $dCity, $dState);
    }
    return 'rest_of_india';
}

function findCustomerEarningPct(PDO $pdo, int $customerId, string $serviceType, string $zone, float $weight, float $fallbackPct): float
{
    $sql = "
        SELECT ces.earning_pct
        FROM pricing_slabs ps
        INNER JOIN customer_earning_slabs ces ON ces.pricing_slab_id = ps.id AND ces.customer_id = ?
        WHERE ps.service_type = ? AND ps.zone = ?
          AND (
              (ps.weight_to IS NOT NULL AND ? <= ps.weight_to)
              OR ps.weight_to IS NULL
          )
        ORDER BY CASE WHEN ps.weight_to IS NULL THEN 1 ELSE 0 END ASC,
                 ps.weight_to ASC, ps.weight_from ASC
        LIMIT 1";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId, $serviceType, $zone, $weight]);
        $pct = $stmt->fetchColumn();
        if ($pct !== false && is_numeric($pct)) {
            return max(0.0, min(100.0, (float)$pct));
        }
        if ($zone !== 'rest_of_india') {
            $stmt->execute([$customerId, $serviceType, 'rest_of_india', $weight]);
            $pct = $stmt->fetchColumn();
            if ($pct !== false && is_numeric($pct)) {
                return max(0.0, min(100.0, (float)$pct));
            }
        }
    } catch (Exception $e) {}

    return $fallbackPct;
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
        $pickupEmail   = trim($pickup['email'] ?? '');
        $pickupAddr1   = trim($pickup['addr1'] ?? '');
        $pickupAddr2   = trim($pickup['addr2'] ?? '');
        $pickupCity    = trim($pickup['city']  ?? '');
        $pickupState   = trim($pickup['state'] ?? '');
        $pickupPincode = trim($pickup['pincode'] ?? '');
        $pickupGstin   = trim($pickup['gstin'] ?? '');

        $delivName    = trim($delivery['name']  ?? '');
        $delivCompany = trim($delivery['company'] ?? '');
        $delivPhone   = trim($delivery['phone'] ?? '');
        $delivEmail   = trim($body['delivery_email'] ?? ($delivery['email'] ?? ''));
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
            $packingCharge = round(max(0.0, $packingCharge), 2);
            if ($packingCharge <= 0) {
                json_response(['success' => false, 'message' => 'Packing material charge is required.'], 422);
            }
        } else {
            $packingCharge = 0.0;
        }
        $finalPrice = round($basePrice + $packingCharge, 2);

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
        if ($declaredValue > 1000) {
            json_response(['success' => false, 'message' => 'Total value of consignment cannot exceed Rs. 1000.'], 422);
        }

        // Service weight constraints (production-ready)
        $serviceConstraints = [
            'standard'  => 2.000,
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
        $pickupPhone = preg_replace('/\D+/', '', $pickupPhone);
        $delivPhone = preg_replace('/\D+/', '', $delivPhone);
        if (!$pickupName || !$pickupPhone || !$pickupEmail || !$pickupAddr1 || !$pickupCity || !$pickupPincode) {
            json_response(['success' => false, 'message' => 'Pickup address is incomplete.'], 422);
        }
        if (!preg_match('/^\d{10}$/', $pickupPhone)) {
            json_response(['success' => false, 'message' => 'Please enter 10 digit pickup mobile number.'], 422);
        }
        if (!filter_var($pickupEmail, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Please enter valid pickup email address.'], 422);
        }
        if (!$delivName || !$delivPhone || !$delivEmail || !$delivAddr1 || !$delivCity || !$delivPincode) {
            json_response(['success' => false, 'message' => 'Delivery address is incomplete.'], 422);
        }
        if (!preg_match('/^\d{10}$/', $delivPhone)) {
            json_response(['success' => false, 'message' => 'Please enter 10 digit delivery mobile number.'], 422);
        }
        if (!filter_var($delivEmail, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Please enter valid delivery email address.'], 422);
        }
        if (!in_array($paymentMethod, ['prepaid','cod','credit'])) $paymentMethod = 'prepaid';

        $shipmentZone = resolveShipmentZone($pdo, $pickupPincode, $delivPincode, $pickupCity, $pickupState, $delivCity, $delivState);
        $customerEarningPct = findCustomerEarningPct($pdo, $userId, $serviceType, $shipmentZone, $chargeableWeight, $customerEarningPct);
        $customerEarningAmount = round($finalPrice * $customerEarningPct / 100);

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
                pickup_name, pickup_company_name, pickup_phone, pickup_email, pickup_address, pickup_city, pickup_state, pickup_pincode, pickup_gstin,
                delivery_name, delivery_company_name, delivery_phone, delivery_email, delivery_address, delivery_city, delivery_state, delivery_pincode, delivery_gstin,
                service_type, weight, chargeable_weight, volumetric_weight, length, width, height, declared_value, pieces, description, customer_ref,
                ewaybill_no, packing_material, packing_charge, photo_address, photo_parcel,
                base_price, discount_pct, discount_amount, final_price,
                customer_earning_pct, customer_earning_amount,
                payment_method, risk_surcharge, gst_invoice, gstin, pan_number,
                status, estimated_delivery, created_at
            ) VALUES (
                ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
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
            $pickupName, $pickupCompany, $pickupPhone, $pickupEmail, $pickupFull, $pickupCity, $pickupState, $pickupPincode, $pickupGstin,
            $delivName, $delivCompany, $delivPhone, $delivEmail, $delivFull, $delivCity, $delivState, $delivPincode, $delivGstin,
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
                'delivery_email'      => $delivEmail,
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
            if (!empty($delivEmail)) {
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
