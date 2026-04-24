<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$authUser = auth_require('customer');
$stmt = $pdo->prepare('SELECT id, full_name, status FROM users WHERE id = ?');
$stmt->execute([$authUser['sub']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status'] !== 'approved') { header('Location: pending.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking — Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer.css">
</head>
<body class="customer-body">

<!-- Sidebar -->
<aside class="cust-sidebar" id="custSidebar">
    <div class="cust-sidebar-header">
        <a href="../index.php"><img src="../assets/images/Main-Careygo-logo-white.png" alt="Careygo" class="cust-sidebar-logo"></a>
        <button class="cust-sidebar-close d-lg-none" id="custSidebarClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cust-sidebar-user">
        <div class="cust-user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div>
            <div class="cust-user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="cust-user-role">Customer</div>
        </div>
    </div>
    <nav class="cust-nav">
        <ul>
            <li class="cust-nav-label">Navigation</li>
            <li><a href="dashboard.php" class="cust-nav-link"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
            <li><a href="new-booking.php" class="cust-nav-link active"><i class="bi bi-plus-circle"></i> New Booking</a></li>
            <li><a href="#" class="cust-nav-link" onclick="openRateCalc();return false;"><i class="bi bi-calculator"></i> Rate Calculator</a></li>
            <li class="cust-nav-label mt-2">Account</li>
            <li><a href="profile.php" class="cust-nav-link"><i class="bi bi-person-circle"></i> My Profile</a></li>
            <li><a href="../auth/logout.php" class="cust-nav-link logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay" id="custOverlay"></div>

<div class="cust-content-wrap">
    <header class="cust-topbar">
        <button class="cust-toggle-btn d-lg-none" id="custToggle"><i class="bi bi-list"></i></button>
        <div class="cust-topbar-title">New Booking</div>
        <div class="cust-topbar-actions">
            <button class="btn-rate-calc" onclick="openRateCalc()">
                <i class="bi bi-calculator"></i> <span class="d-none d-sm-inline">Rate Calculator</span>
            </button>
            <a href="dashboard.php" class="btn-outline-admin" style="font-size:12px;padding:7px 14px;text-decoration:none;">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </header>

    <main class="cust-main">
        <div class="delivery-wizard-wrap">

            <!-- Progress bar -->
            <div class="mb-3">
                <div style="height:4px;background:var(--border);border-radius:4px;overflow:hidden;">
                    <div id="wizardProgress" style="height:100%;background:var(--primary);width:0%;transition:width 0.4s ease;"></div>
                </div>
            </div>

            <!-- Step indicators -->
            <div class="wizard-steps mb-4">
                <?php
                $steps = ['Primary Details','Shipment Details','Select Service','Additional','Summary','Payment'];
                foreach ($steps as $i => $label):
                ?>
                <div class="wizard-step-item" data-step="<?= $i+1 ?>">
                    <div class="step-circle"><?= $i === 0 ? '<i class="bi bi-check-lg"></i>' : ($i+1) ?></div>
                    <div class="step-label"><?= htmlspecialchars($label) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ===== STEP 1: Primary Details ===== -->
            <div class="wizard-panel active" id="panel_1">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-geo-alt me-2 text-primary"></i>Step 1 — Primary Details</h6>
                    </div>
                    <div class="admin-card-body">

                        <!-- PICKUP -->
                        <div class="wizard-section-label"><i class="bi bi-geo-alt-fill text-primary me-1"></i> Pickup Address</div>

                        <div class="pincode-row">
                            <div class="form-group">
                                <label class="wizard-label">Pickup Pincode <span class="req">*</span></label>
                                <input type="text" class="wizard-input" id="pickup_pincode" data-pincode-input="pickup" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit pincode">
                                <div class="wizard-error" id="err_pickup_pincode"></div>
                            </div>
                            <button class="btn-lookup" id="pickup_lookup_btn" data-pincode-lookup data-pincode-type="pickup">
                                <i class="bi bi-search"></i> Check
                            </button>
                        </div>

                        <div class="pincode-result" id="pickup_pincode_result"></div>

                        <!-- Saved addresses -->
                        <div class="saved-addresses-wrap" id="pickup_saved_wrap">
                            <div class="wizard-label mb-2">Select from saved addresses or add new:</div>
                            <div class="saved-addr-list" id="pickup_saved_list"></div>
                            <button class="btn-add-new-addr" data-add-new-addr="pickup">
                                <i class="bi bi-plus-lg"></i> Add New Address
                            </button>
                        </div>

                        <!-- New address form -->
                        <div class="new-addr-form" id="pickup_new_form">
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Full Name <span class="req">*</span></label>
                                    <input type="text" class="wizard-input" id="pickup_name" placeholder="Sender full name">
                                    <div class="wizard-error" id="err_pickup_name"></div>
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Company Name <span class="opt">(optional)</span></label>
                                    <input type="text" class="wizard-input" id="pickup_company" placeholder="Company Name">
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Mobile Number <span class="req">*</span></label>
                                    <input type="tel" class="wizard-input" id="pickup_phone" placeholder="+91 XXXXX XXXXX">
                                    <div class="wizard-error" id="err_pickup_phone"></div>
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Sender's GSTIN <span class="opt">(optional)</span></label>
                                    <input type="text" class="wizard-input" id="pickup_gstin" placeholder="GSTIN No.">
                                </div>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">Address Line 1 <span class="req">*</span></label>
                                <input type="text" class="wizard-input" id="pickup_addr1" placeholder="House/Flat no., Street, Area">
                                <div class="wizard-error" id="err_pickup_addr1"></div>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">Address Line 2</label>
                                <input type="text" class="wizard-input" id="pickup_addr2" placeholder="Landmark (optional)">
                            </div>
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">City <span class="req">*</span></label>
                                    <input type="text" class="wizard-input" id="pickup_city" placeholder="City">
                                    <div class="wizard-error" id="err_pickup_city"></div>
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">State <span class="req">*</span></label>
                                    <input type="text" class="wizard-input" id="pickup_state" placeholder="State">
                                    <div class="wizard-error" id="err_pickup_state"></div>
                                </div>
                            </div>
                        </div>

                        <hr style="margin:24px 0;">

                        <!-- DELIVERY -->
                        <div class="wizard-section-label"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Delivery Address</div>

                        <div class="pincode-row">
                            <div class="form-group">
                                <label class="wizard-label">Delivery Pincode <span class="req">*</span></label>
                                <input type="text" class="wizard-input" id="delivery_pincode" data-pincode-input="delivery" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit pincode">
                                <div class="wizard-error" id="err_delivery_pincode"></div>
                            </div>
                            <button class="btn-lookup" id="delivery_lookup_btn" data-pincode-lookup data-pincode-type="delivery">
                                <i class="bi bi-search"></i> Check
                            </button>
                        </div>

                        <div class="pincode-result" id="delivery_pincode_result"></div>

                        <div class="saved-addresses-wrap" id="delivery_saved_wrap">
                            <div class="wizard-label mb-2">Select from saved addresses or add new:</div>
                            <div class="saved-addr-list" id="delivery_saved_list"></div>
                            <button class="btn-add-new-addr" data-add-new-addr="delivery">
                                <i class="bi bi-plus-lg"></i> Add New Address
                            </button>
                        </div>

                        <div class="new-addr-form" id="delivery_new_form">
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Full Name <span class="req">*</span></label>
                                    <input type="text" class="wizard-input" id="delivery_name" placeholder="Receiver full name">
                                    <div class="wizard-error" id="err_delivery_name"></div>
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Company Name <span class="opt">(optional)</span></label>
                                    <input type="text" class="wizard-input" id="delivery_company" placeholder="Company Name">
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Mobile Number <span class="req">*</span></label>
                                    <input type="tel" class="wizard-input" id="delivery_phone" placeholder="+91 XXXXX XXXXX">
                                    <div class="wizard-error" id="err_delivery_phone"></div>
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">Recipient's GSTIN <span class="opt">(optional)</span></label>
                                    <input type="text" class="wizard-input" id="delivery_gstin" placeholder="GSTIN No.">
                                </div>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">Email Address (for AWB notification) <span class="opt">(optional)</span></label>
                                <input type="email" class="wizard-input" id="delivery_email" placeholder="receiver@example.com">
                                <div style="font-size:11px;color:#666;margin-top:4px;">📧 Receiver will get a notification with tracking details</div>
                                <div class="wizard-error" id="err_delivery_email"></div>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">Address Line 1 <span class="req">*</span></label>
                                <input type="text" class="wizard-input" id="delivery_addr1" placeholder="House/Flat no., Street, Area">
                                <div class="wizard-error" id="err_delivery_addr1"></div>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">Address Line 2</label>
                                <input type="text" class="wizard-input" id="delivery_addr2" placeholder="Landmark (optional)">
                            </div>
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">City <span class="req">*</span></label>
                                    <input type="text" class="wizard-input" id="delivery_city" placeholder="City">
                                    <div class="wizard-error" id="err_delivery_city"></div>
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">State <span class="req">*</span></label>
                                    <input type="text" class="wizard-input" id="delivery_state" placeholder="State">
                                    <div class="wizard-error" id="err_delivery_state"></div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-nav">
                            <span></span>
                            <button class="btn-wizard-next" data-wizard-next>Next <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 2: Shipment Details ===== -->
            <div class="wizard-panel" id="panel_2">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-box-seam me-2 text-primary"></i>Step 2 — Shipment Details</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-grid-2">
                            <div class="wizard-form-group">
                                <label class="wizard-label">Shipment Weight <span class="req">*</span></label>
                                <div class="weight-input-wrap">
                                    <input type="number" id="weight" step="0.001" min="0.001" placeholder="0.000">
                                    <button class="weight-unit-btn active" id="unit_kg">KG</button>
                                    <button class="weight-unit-btn" id="unit_gm">GM</button>
                                </div>
                                <div class="wizard-error" id="err_weight"></div>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">No. of Pieces <span class="req">*</span></label>
                                <input type="number" class="wizard-input" id="pieces" min="1" value="1" placeholder="1">
                                <div class="wizard-error" id="err_pieces"></div>
                            </div>
                        </div>

                        <div class="wizard-section-label">Dimensions (in CM) <span class="opt">(optional — max: L 130 × W 60 × H 60)</span></div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap:12px; margin-bottom:8px;">
                            <div class="wizard-form-group mb-0">
                                <label class="wizard-label">Length</label>
                                <input type="number" class="wizard-input" id="dim_l" placeholder="L" min="0" max="130">
                                <div class="wizard-error" id="err_dim_l"></div>
                            </div>
                            <div class="wizard-form-group mb-0">
                                <label class="wizard-label">Width</label>
                                <input type="number" class="wizard-input" id="dim_w" placeholder="W" min="0" max="60">
                                <div class="wizard-error" id="err_dim_w"></div>
                            </div>
                            <div class="wizard-form-group mb-0">
                                <label class="wizard-label">Height</label>
                                <input type="number" class="wizard-input" id="dim_h" placeholder="H" min="0" max="60">
                                <div class="wizard-error" id="err_dim_h"></div>
                            </div>
                            <div class="wizard-form-group mb-0">
                                <label class="wizard-label">Vol. Weight</label>
                                <input type="text" class="wizard-input" id="vol_weight_display" placeholder="0.000 kg" readonly style="background:#f8f9fa;">
                            </div>
                            <div class="wizard-form-group mb-0">
                                <label class="wizard-label" style="color:#d97706;font-weight:600;">Chargeable Wt.</label>
                                <input type="text" class="wizard-input" id="chargeable_weight_display" placeholder="0.000 kg" readonly style="background:#fef3c7;font-weight:600;">
                            </div>
                        </div>
                        <p style="font-size:11px;color:#6b7280;margin-bottom:16px;">Chargeable weight = max(Actual weight, Volumetric weight). Pricing is based on chargeable weight.</p>

                        <div class="form-grid-2">
                            <div class="wizard-form-group">
                                <label class="wizard-label">Declared Value (₹)</label>
                                <input type="number" class="wizard-input" id="declared_value" min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label">Customer Reference No.</label>
                                <input type="text" class="wizard-input" id="customer_ref" placeholder="Your internal reference">
                            </div>
                        </div>
                        <div class="wizard-form-group">
                            <label class="wizard-label">Description of Contents</label>
                            <textarea class="wizard-textarea" id="description" rows="3" placeholder="E.g. Electronics, Documents, Clothing…"></textarea>
                        </div>
                        <div class="wizard-nav">
                            <button class="btn-wizard-back" data-wizard-back><i class="bi bi-arrow-left me-1"></i> Back</button>
                            <button class="btn-wizard-next" data-step2-next>Next <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 3: Service Selection ===== -->
            <div class="wizard-panel" id="panel_3">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-lightning me-2 text-primary"></i>Step 3 — Select Service</h6>
                    </div>
                    <div class="admin-card-body">
                        <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">Prices calculated based on your shipment weight. Select a service to continue.</p>
                        <div id="services_container">
                            <div class="price-loading"><span class="spinner-border spinner-border-sm me-2 text-primary"></span> Loading services...</div>
                        </div>
                        <div class="wizard-error" id="err_service_error"></div>
                        <div class="wizard-nav">
                            <button class="btn-wizard-back" data-wizard-back><i class="bi bi-arrow-left me-1"></i> Back</button>
                            <button class="btn-wizard-next" data-wizard-next>Next <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 4: Additional Details ===== -->
            <div class="wizard-panel" id="panel_4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-clipboard-check me-2 text-primary"></i>Step 4 — Additional Details</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="wizard-section-label">E-Waybill</div>
                        <div class="ewaybill-options mb-3">
                            <div class="ewaybill-opt selected" data-ewaybill-opt="skip">
                                <div class="ewaybill-opt-radio"></div>
                                <div>
                                    <div class="ewaybill-opt-label">Continue without E-waybill</div>
                                    <div class="ewaybill-opt-sub">Both parties unregistered in GSTN</div>
                                </div>
                            </div>
                            <div class="ewaybill-opt" data-ewaybill-opt="enter">
                                <div class="ewaybill-opt-radio"></div>
                                <div>
                                    <div class="ewaybill-opt-label">Enter E-waybill Number</div>
                                    <div class="ewaybill-opt-sub">I have a valid E-waybill number</div>
                                </div>
                            </div>
                        </div>
                        <div id="ewaybill_input_row" style="display:none;" class="mb-4">
                            <label class="wizard-label">E-waybill Number <span class="req">*</span></label>
                            <input type="text" class="wizard-input" id="ewaybill_no" placeholder="Enter E-waybill number">
                            <div class="wizard-error" id="err_ewaybill_no"></div>
                        </div>

                        <div class="wizard-section-label">Risk Surcharge</div>
                        <div class="ewaybill-options mb-3">
                            <div class="ewaybill-opt selected" data-risk-opt="owner">
                                <div class="ewaybill-opt-radio"></div>
                                <div>
                                    <div class="ewaybill-opt-label">Owner's Risk</div>
                                    <div class="ewaybill-opt-sub">Goods are accepted at sender's risk</div>
                                </div>
                            </div>
                            <div class="ewaybill-opt" data-risk-opt="carrier">
                                <div class="ewaybill-opt-radio"></div>
                                <div>
                                    <div class="ewaybill-opt-label">Carrier's Risk</div>
                                    <div class="ewaybill-opt-sub">Careygo handles risk coverage (surcharge applies)</div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-section-label">Value Added Services</div>
                        <label class="cust-checkbox-wrap" id="packing_material_wrap" style="cursor:pointer;">
                            <input type="checkbox" style="display:none;">
                            <div class="cust-checkbox-box"><i class="bi bi-check-lg"></i></div>
                            <div>
                                <div style="font-size:13px;font-weight:600;">Packing Material <span style="font-size:11px;color:#6b7280;">(click to see charges)</span></div>
                                <div style="font-size:12px;color:var(--muted);">Include professional packing material for your shipment</div>
                            </div>
                        </label>

                        <hr style="margin:20px 0;">

                        <!-- Photo Uploads — MANDATORY -->
                        <div class="wizard-section-label">
                            <i class="bi bi-camera me-1"></i> Photo Uploads <span class="req">*</span>
                            <span style="font-size:11px;font-weight:400;color:#6b7280;margin-left:8px;">Both photos are mandatory before booking</span>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <!-- Address Photo -->
                            <div style="border:2px dashed var(--border);border-radius:12px;padding:16px;text-align:center;transition:border-color .2s;" id="photo_address_box">
                                <i class="bi bi-house-check" style="font-size:28px;color:#4338ca;margin-bottom:8px;display:block;"></i>
                                <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Address Photo</div>
                                <div style="font-size:11px;color:#6b7280;margin-bottom:12px;">Photo of pickup/delivery address label or door</div>
                                <label for="photo_address_input" style="display:inline-block;background:#e0e7ff;color:#4338ca;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;cursor:pointer;">
                                    <i class="bi bi-upload me-1"></i> Choose Photo
                                </label>
                                <input type="file" id="photo_address_input" accept="image/*" capture="environment" style="display:none;">
                                <div id="photo_address_status" style="margin-top:8px;font-size:12px;display:none;align-items:center;justify-content:center;gap:4px;"></div>
                                <div id="photo_address_error" style="display:none;color:#dc2626;font-size:12px;margin-top:4px;"></div>
                            </div>

                            <!-- Parcel Photo -->
                            <div style="border:2px dashed var(--border);border-radius:12px;padding:16px;text-align:center;transition:border-color .2s;" id="photo_parcel_box">
                                <i class="bi bi-box-seam" style="font-size:28px;color:#0891b2;margin-bottom:8px;display:block;"></i>
                                <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Parcel Photo</div>
                                <div style="font-size:11px;color:#6b7280;margin-bottom:12px;">Photo of the packed parcel/item to be shipped</div>
                                <label for="photo_parcel_input" style="display:inline-block;background:#e0f2fe;color:#0369a1;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;cursor:pointer;">
                                    <i class="bi bi-upload me-1"></i> Choose Photo
                                </label>
                                <input type="file" id="photo_parcel_input" accept="image/*" capture="environment" style="display:none;">
                                <div id="photo_parcel_status" style="margin-top:8px;font-size:12px;display:none;align-items:center;justify-content:center;gap:4px;"></div>
                                <div id="photo_parcel_error" style="display:none;color:#dc2626;font-size:12px;margin-top:4px;"></div>
                            </div>
                        </div>

                        <div class="wizard-nav">
                            <button class="btn-wizard-back" data-wizard-back><i class="bi bi-arrow-left me-1"></i> Back</button>
                            <button class="btn-wizard-next" data-wizard-next>Next <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 5: Booking Summary ===== -->
            <div class="wizard-panel" id="panel_5">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-receipt me-2 text-primary"></i>Step 5 — Booking Summary</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="summary-section-title">Pickup Address</div>
                                <div id="summary_pickup"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="summary-section-title">Delivery Address</div>
                                <div id="summary_delivery"></div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div style="background:var(--bg);border-radius:12px;padding:14px;">
                                    <div class="summary-section-title mb-2">Shipment Details</div>
                                    <div class="detail-row"><span class="detail-label" style="min-width:130px;">Service</span><span class="detail-value" id="summary_service" style="font-weight:600;"></span></div>
                                    <div class="detail-row"><span class="detail-label" style="min-width:130px;">Actual Weight</span><span class="detail-value" id="summary_weight"></span></div>
                                    <div class="detail-row" style="background:#fef3c7;border-radius:6px;padding:4px 6px;">
                                        <span class="detail-label" style="min-width:130px;color:#d97706;font-weight:600;">Chargeable Weight</span>
                                        <span class="detail-value" id="summary_chargeable_weight" style="font-weight:700;"></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label" style="min-width:130px;">Pieces</span><span class="detail-value" id="summary_pieces"></span></div>
                                    <div class="detail-row"><span class="detail-label" style="min-width:130px;">Dimensions</span><span class="detail-value" id="summary_dimensions"></span></div>
                                    <div class="detail-row"><span class="detail-label" style="min-width:130px;">Volumetric Wt.</span><span class="detail-value" id="summary_volumetric"></span></div>
                                    <div class="detail-row"><span class="detail-label" style="min-width:130px;">Packing Material</span><span class="detail-value" id="summary_packing"></span></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="summary-section-title mb-2">Pricing</div>
                                <div class="summary-pricing">
                                    <div class="pricing-row"><span>Base Freight</span><span id="summary_base_price">—</span></div>
                                    <div class="pricing-row"><span>Packing Charges</span><span id="summary_packing_price">—</span></div>
                                    <div class="pricing-row total"><span>Total Amount</span><span id="summary_final_price">—</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-nav">
                            <button class="btn-wizard-back" data-wizard-back><i class="bi bi-arrow-left me-1"></i> Back</button>
                            <button class="btn-wizard-next" data-wizard-next>Proceed to Payment <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 6: Payment ===== -->
            <div class="wizard-panel" id="panel_6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title"><i class="bi bi-credit-card me-2 text-primary"></i>Step 6 — Payment</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="wizard-section-label">Mode of Payment</div>

                        <div class="payment-option selected" data-payment="prepaid">
                            <div class="payment-option-radio"></div>
                            <div class="payment-option-info">
                                <strong>Prepaid</strong>
                                <p>Pay online now — secure and instant</p>
                            </div>
                            <i class="bi bi-credit-card-2-front payment-option-icon"></i>
                        </div>
                        <div class="payment-option" data-payment="cod">
                            <div class="payment-option-radio"></div>
                            <div class="payment-option-info">
                                <strong>Cash on Delivery (COD)</strong>
                                <p>Pay when shipment is picked up</p>
                            </div>
                            <i class="bi bi-cash-coin payment-option-icon"></i>
                        </div>
                        <div class="payment-option" data-payment="credit">
                            <div class="payment-option-radio"></div>
                            <div class="payment-option-info">
                                <strong>Credit Account</strong>
                                <p>Charge to your Careygo credit account</p>
                            </div>
                            <i class="bi bi-building-check payment-option-icon"></i>
                        </div>

                        <hr style="margin:20px 0;">

                        <div class="wizard-section-label">GST Invoice</div>
                        <label class="cust-checkbox-wrap" id="gst_invoice_wrap">
                            <input type="checkbox">
                            <div class="cust-checkbox-box"><i class="bi bi-check-lg"></i></div>
                            <div>
                                <div style="font-size:13px;font-weight:600;">Require GST Compliant Tax Invoice</div>
                                <div style="font-size:12px;color:var(--muted);">Invoice will be sent to your registered email after shipment</div>
                            </div>
                        </label>

                        <div id="gst_fields" style="display:none;margin-top:16px;">
                            <div class="form-grid-2">
                                <div class="wizard-form-group">
                                    <label class="wizard-label">GSTIN</label>
                                    <input type="text" class="wizard-input" id="gstin" placeholder="22AAAAA0000A1Z5" maxlength="15">
                                </div>
                                <div class="wizard-form-group">
                                    <label class="wizard-label">PAN Number</label>
                                    <input type="text" class="wizard-input" id="pan_number" placeholder="AAAAA0000A" maxlength="10">
                                </div>
                            </div>
                        </div>

                        <div id="form_error" class="alert alert-danger mt-3" style="display:none;font-size:13px;border-radius:10px;"></div>
                        <div class="wizard-error" id="err_payment_error"></div>

                        <div class="wizard-nav">
                            <button class="btn-wizard-back" data-wizard-back><i class="bi bi-arrow-left me-1"></i> Back</button>
                            <button class="btn-wizard-next" id="submit_booking" style="background:#22c55e;border-color:#22c55e;">
                                <i class="bi bi-check-circle me-2"></i> Confirm Booking
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SUCCESS SCREEN ===== -->
            <div class="wizard-panel" id="panel_7">
                <div class="admin-card">
                    <div class="admin-card-body">
                        <div class="booking-success">
                            <div class="success-icon-wrap"><i class="bi bi-check-lg"></i></div>
                            <h3>Booking Confirmed!</h3>
                            <p style="font-size:14px;">Your shipment has been booked successfully.</p>
                            <p style="font-size:13px;color:var(--muted);">Tracking Number:</p>
                            <div class="tracking-badge" id="final_tracking_no">—</div>
                            <p style="font-size:13px;color:var(--muted);">Save this tracking number for future reference. Our team will pick up your shipment shortly.</p>
                            <div class="d-flex gap-3 justify-content-center flex-wrap mt-2">
                                <a href="dashboard.php" class="btn-new-delivery">
                                    <i class="bi bi-grid-1x2 me-1"></i> View All Bookings
                                </a>
                                <a href="new-booking.php" class="btn-outline-admin">
                                    <i class="bi bi-plus-lg me-1"></i> New Booking
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /wizard-wrap -->
    </main>
</div>

<?php include __DIR__ . '/includes/rate-calc-modal.php'; ?>
<script>window.SITE_URL = '<?= rtrim(SITE_URL, '/') ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/delivery.js"></script>
<script>
// Sidebar toggle
const custSidebar = document.getElementById('custSidebar');
const custOverlay = document.getElementById('custOverlay');
document.getElementById('custToggle')?.addEventListener('click', () => { custSidebar.classList.add('open'); custOverlay.classList.add('show'); });
document.getElementById('custSidebarClose')?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
custOverlay?.addEventListener('click', () => { custSidebar.classList.remove('open'); custOverlay.classList.remove('show'); });
function openRateCalc() { new bootstrap.Modal(document.getElementById('rateCalcModal')).show(); }
</script>
</body>
</html>
