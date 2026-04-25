/* ============================================================
   delivery.js — Careygo Multi-Step Delivery Wizard
   ============================================================ */
(function () {
    'use strict';

    const SITE_URL = window.SITE_URL || '';
    const STORAGE_KEY = 'careygo_booking_draft';
    const STORAGE_EXPIRY = 24 * 60 * 60 * 1000; // 24 hours

    // Weight / dimension limits
    const MAX_PIECE_WEIGHT_KG = 60;
    const MAX_DIM_L = 130; // cm
    const MAX_DIM_W = 60;  // cm
    const MAX_DIM_H = 60;  // cm

    /* ── State ── */
    const defaultState = {
        step: 1,
        totalSteps: 6,
        pickup: { pincode: '', city: '', state: '', name: '', company: '', phone: '', gstin: '', addr1: '', addr2: '' },
        delivery: { pincode: '', city: '', state: '', name: '', company: '', phone: '', email: '', gstin: '', addr1: '', addr2: '' },
        pickupPincodeVerified: false,
        deliveryPincodeVerified: false,
        weight: 0,
        unit: 'kg',
        pieces: 1,
        declaredValue: 0,
        description: '',
        customerRef: '',
        serviceType: '',
        servicePrice: 0,
        serviceLabel: '',
        serviceTat: '',
        ewaybillOpt: 'skip',
        ewaybillNo: '',
        riskSurcharge: 'owner',
        packingMaterial: false,
        packingCharge: 0,
        paymentMethod: 'prepaid',
        gstInvoice: false,
        gstin: '',
        pan: '',
        dim_l: 0,
        dim_w: 0,
        dim_h: 0,
        volumetricWeight: 0,
        chargeableWeight: 0,
        photoAddressId: '',
        photoParcelId: '',
        savedPickupAddresses: [],
        savedDeliveryAddresses: [],
        selectedPickupAddrId: null,
        selectedDeliveryAddrId: null,
        useNewPickupAddr: false,
        useNewDeliveryAddr: false,
    };

    /* ── LocalStorage Helper Functions ── */
    function loadDraftState() {
        try {
            // If we just cleared the form (fresh param), ignore and purge the draft
            if (window.location.search.includes('fresh=')) {
                clearDraft();
                return JSON.parse(JSON.stringify(defaultState));
            }

            const stored = localStorage.getItem(STORAGE_KEY);
            if (!stored) return JSON.parse(JSON.stringify(defaultState));

            const data = JSON.parse(stored);
            const timestamp = data._timestamp || 0;
            const now = Date.now();

            if (now - timestamp > STORAGE_EXPIRY) {
                clearDraft();
                return JSON.parse(JSON.stringify(defaultState));
            }

            const restored = { ...defaultState, ...data };
            restored.step = 1;
            return restored;
        } catch (e) {
            console.warn('Could not load draft:', e);
            return JSON.parse(JSON.stringify(defaultState));
        }
    }

    let isClearing = false;
    function clearDraft() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    }

    function saveDraftState() {
        if (isClearing) return;
        try {
            const toSave = { ...state, _timestamp: Date.now() };
            const hasData = (state.pickup && state.pickup.pincode) || (state.delivery && state.delivery.pincode) || state.weight > 0 || state.serviceType;
            if (hasData) localStorage.setItem(STORAGE_KEY, JSON.stringify(toSave));
        } catch (e) {
            console.warn('Could not save draft:', e);
        }
    }

    let autoSaveInterval = setInterval(saveDraftState, 5000);
    document.addEventListener('visibilitychange', () => { if (document.hidden) saveDraftState(); });
    window.addEventListener('beforeunload', saveDraftState);

    const state = loadDraftState();

    /* ── DOM refs ── */
    const stepItems   = document.querySelectorAll('.wizard-step-item');
    const panels      = document.querySelectorAll('.wizard-panel');
    const progressBar = document.getElementById('wizardProgress');

    /* ── Navigate to step ── */
    function goToStep(n) {
        state.step = n;
        panels.forEach((p, i) => p.classList.toggle('active', i + 1 === n));
        stepItems.forEach((s, i) => {
            s.classList.remove('active', 'done');
            if (i + 1 < n) s.classList.add('done');
            if (i + 1 === n) s.classList.add('active');
        });
        if (progressBar) progressBar.style.width = `${((n - 1) / (state.totalSteps - 1)) * 100}%`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (n === 5) buildSummary();
    }

    /* ── Validate step ── */
    function validateStep(n) {
        clearErrors();
        let ok = true;

        if (n === 1) {
            // Sync ALL fields from DOM to state before validating (handles browser autofill)
            syncStep1FromDOM();

            // Pickup pincode verified
            if (!state.pickup.pincode) {
                showErr('pickup_pincode', 'Enter pickup pincode'); ok = false;
            } else if (state.pickup.pincode.length < 6) {
                showErr('pickup_pincode', 'Please enter 6 digit pincode'); ok = false;
            } else if (!state.pickupPincodeVerified) {
                showErr('pickup_pincode', 'Please verify pincode by clicking Check'); ok = false;
            }

            if (!state.pickup.name)    { showErr('pickup_name',    'Enter sender full name');    ok = false; }
            if (!state.pickup.phone)   { showErr('pickup_phone',   'Enter sender mobile number'); ok = false; }
            if (!state.pickup.addr1)   { showErr('pickup_addr1',   'Enter pickup address');      ok = false; }
            if (!state.pickup.city)    { showErr('pickup_city',    'Enter city');                ok = false; }
            if (!state.pickup.state)   { showErr('pickup_state',   'Enter state');               ok = false; }

            // Delivery pincode verified
            if (!state.delivery.pincode) {
                showErr('delivery_pincode', 'Enter delivery pincode'); ok = false;
            } else if (state.delivery.pincode.length < 6) {
                showErr('delivery_pincode', 'Please enter 6 digit pincode'); ok = false;
            } else if (!state.deliveryPincodeVerified) {
                showErr('delivery_pincode', 'Please verify pincode by clicking Check'); ok = false;
            }

            if (!state.delivery.name)    { showErr('delivery_name',    'Enter receiver full name');  ok = false; }
            if (!state.delivery.phone)   { showErr('delivery_phone',   'Enter receiver mobile number'); ok = false; }
            if (!state.delivery.addr1)   { showErr('delivery_addr1',   'Enter delivery address');    ok = false; }
            if (!state.delivery.city)    { showErr('delivery_city',    'Enter city');               ok = false; }
            if (!state.delivery.state)   { showErr('delivery_state',   'Enter state');              ok = false; }
        }

        if (n === 2) {
            const wt = getWeightInKg();
            const absoluteMax = 25.0; // Current maximum limit across all services
            if (!wt || wt <= 0) {
                showErr('weight', 'Enter a valid weight'); ok = false;
            } else if (wt > absoluteMax) {
                showWeightAlert(wt, absoluteMax);
                showErr('weight', `Maximum ${absoluteMax} kg allowed per shipment`); ok = false;
            }

            if (!state.pieces || state.pieces < 1) { showErr('pieces', 'Min 1 piece required'); ok = false; }

            // Dimension limits (only if dimensions are entered)
            if (state.dim_l > 0 || state.dim_w > 0 || state.dim_h > 0) {
                if (state.dim_l > MAX_DIM_L) { showErr('dim_l', `Max length is ${MAX_DIM_L} cm`); ok = false; }
                if (state.dim_w > MAX_DIM_W) { showErr('dim_w', `Max width is ${MAX_DIM_W} cm`);  ok = false; }
                if (state.dim_h > MAX_DIM_H) { showErr('dim_h', `Max height is ${MAX_DIM_H} cm`); ok = false; }
            }
        }

        if (n === 3) {
            if (!state.serviceType) { showErr('service_error', 'Please select a service type'); ok = false; }
        }

        if (n === 4) {
            // Sync ewaybill from DOM
            const ewbEl = document.getElementById('ewaybill_no');
            if (ewbEl) state.ewaybillNo = ewbEl.value.trim();

            // E-waybill number required if "Enter E-waybill" is selected
            if (state.ewaybillOpt === 'enter' && !state.ewaybillNo.trim()) {
                showErr('ewaybill_no', 'E-waybill number is required');
                const inputRow = document.getElementById('ewaybill_input_row');
                if (inputRow) inputRow.style.display = 'block';
                ok = false;
            }
            // Photo uploads mandatory
            if (!state.photoAddressId) {
                showPhotoError('photo_address_error', 'Address photo is required before proceeding');
                ok = false;
            }
            if (!state.photoParcelId) {
                showPhotoError('photo_parcel_error', 'Parcel photo is required before proceeding');
                ok = false;
            }
        }

        if (n === 6) {
            if (!state.paymentMethod) { showErr('payment_error', 'Select a payment method'); ok = false; }
        }

        return ok;
    }

    function showErr(id, msg) {
        const el = document.getElementById(`err_${id}`);
        if (el) { el.textContent = msg; el.classList.add('show'); }
        const inp = document.getElementById(id);
        if (inp) inp.classList.add('is-error');
    }

    function showPhotoError(id, msg) {
        const el = document.getElementById(id);
        if (el) { el.textContent = msg; el.style.display = 'block'; el.style.color = '#dc2626'; el.style.fontSize = '12px'; el.style.marginTop = '4px'; }
    }

    function clearErrors() {
        document.querySelectorAll('.wizard-error').forEach(e => { e.classList.remove('show'); e.textContent = ''; });
        document.querySelectorAll('.wizard-input.is-error, .wizard-select.is-error').forEach(e => e.classList.remove('is-error'));
        document.querySelectorAll('[id$="_error"]').forEach(e => { e.textContent = ''; e.style.display = 'none'; });
    }

    /* ── Weight alert popup ── */
    function showWeightAlert(wt, max = 25) {
        // Create or reuse alert modal
        let modal = document.getElementById('weightAlertModal');
        if (modal) modal.remove();
        
        modal = document.createElement('div');
        modal.id = 'weightAlertModal';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
        modal.innerHTML = `
            <div style="background:#fff;border-radius:16px;padding:28px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="font-size:48px;margin-bottom:12px;">⚠️</div>
                <h5 style="color:#dc2626;margin-bottom:8px;">Weight Limit Exceeded</h5>
                <p style="font-size:14px;color:#555;margin-bottom:6px;">
                    Maximum allowable weight for online booking is <strong>${max} kg</strong>.
                </p>
                <p style="font-size:13px;color:#777;margin-bottom:20px;">
                    Your entered weight is <strong>${wt.toFixed(3)} kg</strong>. For heavier shipments, please contact our support team for specialized cargo rates.
                </p>
                <button onclick="document.getElementById('weightAlertModal').remove()"
                        style="background:#001a93;color:#fff;border:none;border-radius:8px;padding:10px 28px;font-size:14px;font-weight:600;cursor:pointer;">
                    OK, I'll Fix It
                </button>
            </div>`;
        document.body.appendChild(modal);
    }

    /* ── Next / Back ── */
    document.querySelectorAll('[data-wizard-next]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (validateStep(state.step)) goToStep(state.step + 1);
        });
    });

    document.querySelectorAll('[data-wizard-back]').forEach(btn => {
        btn.addEventListener('click', () => { if (state.step > 1) goToStep(state.step - 1); });
    });

    /* ── Pincode lookup ── */
    function lookupPincode(pincode, type) {
        const resultEl = document.getElementById(`${type}_pincode_result`);
        const btn = document.getElementById(`${type}_lookup_btn`);

        // Validate 6 digits
        if (!pincode) {
            showErr(`${type}_pincode`, 'Enter pickup pincode');
            return;
        }
        if (pincode.length < 6) {
            showErr(`${type}_pincode`, 'Please enter 6 digit pincode');
            if (resultEl) {
                resultEl.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> Please enter a valid 6 digit pincode`;
                resultEl.classList.add('show');
            }
            return;
        }

        btn && (btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>');
        fetch(`${SITE_URL}/api/pincode.php?pincode=${encodeURIComponent(pincode)}`)
            .then(r => r.json())
            .then(data => {
                btn && (btn.innerHTML = '<i class="bi bi-search"></i> Check');
                if (data.success) {
                    const info = data.data;
                    state[type].city  = info.city;
                    state[type].state = info.state;
                    state[`${type}PincodeVerified`] = true;  // ✓ Mark as verified

                    if (resultEl) {
                        resultEl.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> <strong>${info.city}</strong>, ${info.state} — Serviceable`;
                        resultEl.classList.add('show');
                    }
                    const cityEl  = document.getElementById(`${type}_city`);
                    const stateEl = document.getElementById(`${type}_state`);
                    if (cityEl)  { cityEl.value  = info.city;  state[type].city  = info.city; }
                    if (stateEl) { stateEl.value = info.state; state[type].state = info.state; }
                    loadSavedAddresses(type, pincode);
                } else {
                    state[`${type}PincodeVerified`] = false;
                    if (resultEl) {
                        resultEl.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> Pincode not found or not serviceable`;
                        resultEl.classList.add('show');
                    }
                }
            })
            .catch(() => { btn && (btn.innerHTML = '<i class="bi bi-search"></i> Check'); });
    }

    document.querySelectorAll('[data-pincode-lookup]').forEach(btn => {
        const type = btn.dataset.pincodeType;
        btn.addEventListener('click', () => {
            const inp = document.getElementById(`${type}_pincode`);
            if (inp) { state[type].pincode = inp.value.trim(); lookupPincode(inp.value.trim(), type); }
        });
    });

    document.querySelectorAll('[data-pincode-input]').forEach(inp => {
        // Enforce max 6 digits
        inp.addEventListener('keypress', e => {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });
        inp.addEventListener('input', () => {
            // Clamp to 6 digits
            if (inp.value.length > 6) inp.value = inp.value.slice(0, 6);
            const type = inp.dataset.pincodeInput;
            state[type].pincode = inp.value.trim();
            // Reset verification when user changes the pincode
            state[`${type}PincodeVerified`] = false;
            // Auto-lookup when 6 digits entered
            if (inp.value.length === 6) lookupPincode(inp.value.trim(), type);
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const type = inp.dataset.pincodeInput;
                state[type].pincode = inp.value.trim();
                lookupPincode(inp.value.trim(), type);
            }
        });
    });

    /* ── Load saved addresses ── */
    function loadSavedAddresses(type, pincode) {
        fetch(`${SITE_URL}/api/addresses.php`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.addresses.length > 0) {
                    state[`saved${type.charAt(0).toUpperCase() + type.slice(1)}Addresses`] = data.addresses;
                    renderSavedAddresses(type, data.addresses);
                } else {
                    showNewAddressForm(type);
                }
            })
            .catch(() => showNewAddressForm(type));
    }

    function renderSavedAddresses(type, addresses) {
        const wrap = document.getElementById(`${type}_saved_wrap`);
        const list = document.getElementById(`${type}_saved_list`);
        if (!wrap || !list) return;

        list.innerHTML = addresses.map(a => `
            <div class="saved-addr-item" data-addr-id="${a.id}" onclick="selectSavedAddr('${type}', ${a.id})">
                <div class="saved-addr-radio"></div>
                <div class="saved-addr-info">
                    <div class="saved-addr-name">${escHtml(a.full_name)} · ${escHtml(a.phone)}</div>
                    <div class="saved-addr-text">${escHtml(a.address_line1)}${a.address_line2 ? ', ' + escHtml(a.address_line2) : ''}, ${escHtml(a.city)}, ${escHtml(a.state)} - ${escHtml(a.pincode)}</div>
                </div>
            </div>`).join('');

        wrap.classList.add('show');
        if (addresses.length > 0) selectSavedAddr(type, addresses[0].id);
    }

    window.selectSavedAddr = function (type, id) {
        const addresses = state[`saved${type.charAt(0).toUpperCase() + type.slice(1)}Addresses`];
        const addr = addresses.find(a => a.id == id);
        if (!addr) return;

        document.querySelectorAll(`#${type}_saved_list .saved-addr-item`).forEach(el => {
            el.classList.toggle('selected', el.dataset.addrId == id);
        });

        state[`selected${type.charAt(0).toUpperCase() + type.slice(1)}AddrId`] = id;
        state[type].name  = addr.full_name;
        state[type].phone = addr.phone;
        state[type].addr1 = addr.address_line1;
        state[type].addr2 = addr.address_line2 || '';
        state[type].city  = addr.city;
        state[type].state = addr.state;
        state[type].pincode = addr.pincode;
        state[`${type}PincodeVerified`] = true; // Saved address = already verified
        state[`useNew${type.charAt(0).toUpperCase() + type.slice(1)}Addr`] = false;

        const newForm = document.getElementById(`${type}_new_form`);
        if (newForm) newForm.classList.remove('show');
    };

    function showNewAddressForm(type) {
        const newForm = document.getElementById(`${type}_new_form`);
        const wrap    = document.getElementById(`${type}_saved_wrap`);
        if (newForm) newForm.classList.add('show');
        if (wrap) wrap.classList.remove('show');
        state[`useNew${type.charAt(0).toUpperCase() + type.slice(1)}Addr`] = true;
    }

    document.querySelectorAll('[data-add-new-addr]').forEach(btn => {
        const type = btn.dataset.addNewAddr;
        btn.addEventListener('click', () => showNewAddressForm(type));
    });

    /* ── Sync address form fields to state ── */
    ['pickup', 'delivery'].forEach(type => {
        const fields = type === 'delivery'
            ? ['name', 'company', 'phone', 'email', 'gstin', 'addr1', 'addr2', 'city', 'state']
            : ['name', 'company', 'phone', 'gstin', 'addr1', 'addr2', 'city', 'state'];
        fields.forEach(field => {
            const el = document.getElementById(`${type}_${field}`);
            if (el) el.addEventListener('input', () => { state[type][field] = el.value.trim(); });
        });
    });

    /* ── Step 2: Weight & pieces ── */
    const weightInput = document.getElementById('weight');
    const unitKg = document.getElementById('unit_kg');
    const unitGm = document.getElementById('unit_gm');

    function getWeightInKg() {
        const val = parseFloat(weightInput ? weightInput.value : 0) || 0;
        return state.unit === 'gm' ? val / 1000 : val;
    }

    unitKg && unitKg.addEventListener('click', () => { state.unit = 'kg'; unitKg.classList.add('active'); unitGm && unitGm.classList.remove('active'); });
    unitGm && unitGm.addEventListener('click', () => { state.unit = 'gm'; unitGm.classList.add('active'); unitKg && unitKg.classList.remove('active'); });
    weightInput && weightInput.addEventListener('input', () => {
        state.weight = getWeightInKg();
        updateChargeableWeight();
    });
    weightInput && weightInput.addEventListener('blur', () => {
        const wt = getWeightInKg();
        if (wt > MAX_PIECE_WEIGHT_KG) showWeightAlert(wt);
    });

    const piecesInput = document.getElementById('pieces');
    piecesInput && piecesInput.addEventListener('input', () => { state.pieces = parseInt(piecesInput.value) || 1; });

    const declaredValueInput = document.getElementById('declared_value');
    declaredValueInput && declaredValueInput.addEventListener('input', () => { state.declaredValue = parseFloat(declaredValueInput.value) || 0; });

    const descriptionInput = document.getElementById('description');
    descriptionInput && descriptionInput.addEventListener('input', () => { state.description = descriptionInput.value.trim(); });

    const customerRefInput = document.getElementById('customer_ref');
    customerRefInput && customerRefInput.addEventListener('input', () => { state.customerRef = customerRefInput.value.trim(); });

    /* ── Dimensions & Volumetric Weight ── */
    const dimL = document.getElementById('dim_l');
    const dimW = document.getElementById('dim_w');
    const dimH = document.getElementById('dim_h');
    const volDisplay = document.getElementById('vol_weight_display');

    function updateVolumetricWeight() {
        const l = parseFloat(dimL ? dimL.value : 0) || 0;
        const w = parseFloat(dimW ? dimW.value : 0) || 0;
        const h = parseFloat(dimH ? dimH.value : 0) || 0;

        state.dim_l = l;
        state.dim_w = w;
        state.dim_h = h;

        // Show dimension warnings inline
        showDimWarning('dim_l', l, MAX_DIM_L, 'cm');
        showDimWarning('dim_w', w, MAX_DIM_W, 'cm');
        showDimWarning('dim_h', h, MAX_DIM_H, 'cm');

        if (l > 0 && w > 0 && h > 0) {
            const volWt = (l * w * h) / 5000;
            state.volumetricWeight = volWt;
            if (volDisplay) volDisplay.value = volWt.toFixed(3) + ' kg';
        } else {
            state.volumetricWeight = 0;
            if (volDisplay) volDisplay.value = '';
        }
        updateChargeableWeight();
    }

    function showDimWarning(id, val, max, unit) {
        const inp = document.getElementById(id);
        const errEl = document.getElementById(`err_${id}`);
        if (!inp) return;
        if (val > max) {
            inp.style.borderColor = '#ef4444';
            if (errEl) { errEl.textContent = `Max ${max} ${unit}`; errEl.classList.add('show'); }
        } else {
            inp.style.borderColor = '';
            if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
        }
    }

    function updateChargeableWeight() {
        const actual = state.weight || 0;
        const vol    = state.volumetricWeight || 0;
        state.chargeableWeight = Math.max(actual, vol);

        // Update chargeable weight display in step 2 if it exists
        const cwEl = document.getElementById('chargeable_weight_display');
        if (cwEl) {
            if (state.chargeableWeight > 0) {
                cwEl.value = state.chargeableWeight.toFixed(3) + ' kg';
                cwEl.style.background = state.chargeableWeight > actual ? '#fef3c7' : '#f0fdf4';
            } else {
                cwEl.value = '';
            }
        }
    }

    [dimL, dimW, dimH].forEach(el => {
        el && el.addEventListener('input', updateVolumetricWeight);
    });

    /* ── Step 3: Load services when entering ── */
    document.querySelector('[data-step2-next]') && document.querySelector('[data-step2-next]').addEventListener('click', () => {
        if (validateStep(2)) { loadServices(); goToStep(3); }
    });

    function loadServices() {
        const container = document.getElementById('services_container');
        if (!container) return;
        const wt = getWeightInKg();
        state.weight = wt;
        updateChargeableWeight();

        // Price on chargeable weight (max of actual and volumetric)
        const billingWeight = state.chargeableWeight > 0 ? state.chargeableWeight : wt;

        container.innerHTML = `<div class="price-loading"><span class="spinner-border spinner-border-sm me-2"></span> Calculating prices...</div>`;

        const fetchUrl = `${SITE_URL}/api/pricing.php?weight=${billingWeight}&pickup=${encodeURIComponent(state.pickup.pincode)}&delivery=${encodeURIComponent(state.delivery.pincode)}&pickup_city=${encodeURIComponent(state.pickup.city)}&pickup_state=${encodeURIComponent(state.pickup.state)}&delivery_city=${encodeURIComponent(state.delivery.city)}&delivery_state=${encodeURIComponent(state.delivery.state)}&t=${Date.now()}`;

        fetch(fetchUrl)
            .then(r => r.json())
            .then(data => {
                if (data.success) renderServices(data.services, data.zone);
                else container.innerHTML = `<div class="alert alert-warning">Could not load pricing. Please try again.</div>`;
            })
            .catch(() => { container.innerHTML = `<div class="alert alert-danger">Network error. Please try again.</div>`; });
    }

    function renderServices(services, zone) {
        const container = document.getElementById('services_container');
        if (!container) return;

        const chargeableWeight = state.chargeableWeight || state.weight;

        const serviceMap = {
            standard:  { icon: 'bi-truck',         label: 'Standard Express', code: 'STD-EXP' },
            premium:   { icon: 'bi-lightning-fill', label: 'Premium Express',  code: 'PRM-EXP' },
            air_cargo: { icon: 'bi-airplane',       label: 'Air Cargo',         code: 'AIR-CGO' },
            surface:   { icon: 'bi-boxes',          label: 'Surface Cargo',     code: 'SRF-CGO' },
        };

        const zoneLabels = {
            'within_city': 'Within City',
            'within_state': 'Within State',
            'metro': 'Metro City',
            'rest_of_india': 'Rest of India'
        };
        const zoneLabel = zone && zoneLabels[zone] ? zoneLabels[zone] : 'Standard Rate';

        // Chargeable weight notice
        const volWt = state.volumetricWeight || 0;
        let weightNotice = '';
        if (volWt > state.weight) {
            weightNotice = `
                <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#92400e;">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Volumetric weight (${volWt.toFixed(3)} kg) exceeds actual weight (${state.weight.toFixed(3)} kg).</strong>
                    Priced on chargeable weight: <strong>${chargeableWeight.toFixed(3)} kg</strong>
                </div>`;
        }

        container.innerHTML = weightNotice + services.map(svc => {
            const meta = serviceMap[svc.type] || { icon: 'bi-box', label: svc.type, code: '' };

            // Color-code by speed — affects card border + badge palette
            let tatColor, tatBg, tatBorder, tatIcon;
            if (svc.tat <= 1) {
                tatColor = '#14532d'; tatBg = '#dcfce7'; tatBorder = '#16a34a'; tatIcon = 'bi-lightning-fill';
            } else if (svc.tat <= 2) {
                tatColor = '#1e3a8a'; tatBg = '#dbeafe'; tatBorder = '#2563eb'; tatIcon = 'bi-send-fill';
            } else if (svc.tat <= 4) {
                tatColor = '#78350f'; tatBg = '#fef3c7'; tatBorder = '#d97706'; tatIcon = 'bi-clock-fill';
            } else {
                tatColor = '#1f2937'; tatBg = '#f3f4f6'; tatBorder = '#6b7280'; tatIcon = 'bi-truck';
            }

            return `
            <div class="service-card" data-service="${svc.type}"
                 style="border-left:4px solid ${tatBorder};"
                 onclick="selectService('${svc.type}', ${svc.price}, '${escHtml(meta.label)}', '${escHtml(svc.tat_label)}')">
                <div class="service-card-check"><i class="bi bi-check-lg"></i></div>
                <div class="service-card-top">
                    <div>
                        <div class="service-card-name"><i class="${meta.icon} me-2" style="color:${tatBorder}"></i>${meta.label}</div>
                        <div class="service-card-code">${meta.code}</div>
                    </div>
                    <div style="text-align:right">
                        <div class="service-card-price">₹${svc.price.toLocaleString('en-IN')}</div>
                        <div class="service-card-price-label">${escHtml(zoneLabel)}</div>
                    </div>
                </div>
                <!-- Timeline row: duration → delivery day+date -->
                <div style="display:flex;align-items:center;gap:8px;margin:10px 0 6px;padding:10px 12px;
                            background:${tatBg};border-radius:8px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:5px;font-weight:700;font-size:13px;color:${tatColor};">
                        <i class="bi ${tatIcon}"></i> ${escHtml(svc.tat_label)}
                    </span>
                    <span style="color:${tatBorder};font-weight:700;font-size:16px;">→</span>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:${tatColor};">
                        <i class="bi bi-calendar2-check" style="font-size:14px;"></i>
                        Delivery by <strong>${escHtml(svc.eta)}</strong>
                    </span>
                </div>
                <div class="service-card-meta" style="margin-top:4px;">
                    <div class="service-card-meta-item" style="font-size:12px;color:#6b7280;">
                        <i class="bi bi-weight"></i> Chargeable: <strong>${chargeableWeight.toFixed(3)} kg</strong>
                    </div>
                </div>
            </div>`;
        }).join('');

        if (services.length === 0) {
            container.innerHTML = `<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No services available for this route/weight combination. Please contact us.</div>`;
        }
    }

    window.selectService = function (type, price, label, tat) {
        document.querySelectorAll('.service-card').forEach(c => c.classList.toggle('selected', c.dataset.service === type));
        state.serviceType  = type;
        state.servicePrice = price;
        state.serviceLabel = label;
        state.serviceTat   = tat;
    };

    /* ── Step 4: Options ── */
    document.querySelectorAll('.ewaybill-opt[data-ewaybill-opt]').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.ewaybill-opt[data-ewaybill-opt]').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            state.ewaybillOpt = opt.dataset.ewaybillOpt;
            const inputRow = document.getElementById('ewaybill_input_row');
            if (inputRow) inputRow.style.display = state.ewaybillOpt === 'enter' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('.ewaybill-opt[data-risk-opt]').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.ewaybill-opt[data-risk-opt]').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            state.riskSurcharge = opt.dataset.riskOpt;
        });
    });

    const ewaybillNoInput = document.getElementById('ewaybill_no');
    ewaybillNoInput && ewaybillNoInput.addEventListener('input', () => { state.ewaybillNo = ewaybillNoInput.value.trim(); });

    /* ── Packing material — show charges popup ── */
    const packingWrap = document.getElementById('packing_material_wrap');
    packingWrap && packingWrap.addEventListener('click', e => {
        e.preventDefault(); // Don't toggle yet — show popup first
        if (!state.packingMaterial) {
            showPackingChargePopup();
        } else {
            // Already selected — unselect
            state.packingMaterial = false;
            state.packingCharge   = 0;
            packingWrap.classList.remove('checked');
        }
    });

    function showPackingChargePopup() {
        // Fetch current packing charge from server
        fetch(`${SITE_URL}/api/settings.php?key=packing_charge`)
            .then(r => r.json())
            .then(data => {
                const charge = parseFloat(data.value || 50);
                displayPackingModal(charge);
            })
            .catch(() => displayPackingModal(50)); // fallback
    }

    function displayPackingModal(charge) {
        let modal = document.getElementById('packingChargeModal');
        if (modal) modal.remove();

        modal = document.createElement('div');
        modal.id = 'packingChargeModal';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
        modal.innerHTML = `
            <div style="background:#fff;border-radius:16px;padding:28px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="background:#e0e7ff;border-radius:12px;padding:12px;"><i class="bi bi-box2-heart" style="font-size:24px;color:#4338ca;"></i></div>
                    <div>
                        <h6 style="margin:0;font-weight:700;">Packing Material</h6>
                        <p style="margin:0;font-size:12px;color:#6b7280;">Professional packaging for your shipment</p>
                    </div>
                </div>
                <div style="background:#f0fdf4;border-radius:10px;padding:14px;margin-bottom:16px;text-align:center;">
                    <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">Additional Charge</div>
                    <div style="font-size:28px;font-weight:800;color:#166534;">₹${charge.toLocaleString('en-IN')}</div>
                    <div style="font-size:11px;color:#6b7280;">per shipment (incl. GST)</div>
                </div>
                <p style="font-size:13px;color:#374151;margin-bottom:20px;">
                    Our team will professionally pack your shipment using high-quality materials to ensure safe delivery.
                </p>
                <div style="display:flex;gap:10px;">
                    <button onclick="confirmPackingMaterial(${charge})"
                            style="flex:1;background:#001a93;color:#fff;border:none;border-radius:8px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;">
                        <i class="bi bi-check-circle me-1"></i> Add Packing (₹${charge})
                    </button>
                    <button onclick="document.getElementById('packingChargeModal').remove()"
                            style="flex:0;background:#f3f4f6;color:#374151;border:none;border-radius:8px;padding:10px 16px;font-size:14px;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }

    window.confirmPackingMaterial = function(charge) {
        state.packingMaterial = true;
        state.packingCharge   = charge;
        packingWrap && packingWrap.classList.add('checked');
        document.getElementById('packingChargeModal')?.remove();
    };

    /* ── Photo uploads (Step 4) ── */
    function initPhotoUpload(inputId, type, statusId, errorId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            // Client-side validation
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                showPhotoError(errorId, 'File must be under 5 MB');
                input.value = '';
                return;
            }
            if (!file.type.startsWith('image/')) {
                showPhotoError(errorId, 'Please select an image file (JPG, PNG, etc.)');
                input.value = '';
                return;
            }

            const statusEl = document.getElementById(statusId);
            if (statusEl) {
                statusEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';
                statusEl.style.color = '#6b7280';
                statusEl.style.display = 'flex';
                statusEl.style.alignItems = 'center';
            }

            const formData = new FormData();
            formData.append('photo', file);
            formData.append('type', type);

            fetch(`${SITE_URL}/api/upload-photo.php`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (type === 'address') state.photoAddressId = data.file_id;
                    if (type === 'parcel')  state.photoParcelId  = data.file_id;

                    // Show image preview
                    const previewId = type === 'address' ? 'photo_address_preview' : 'photo_parcel_preview';
                    const previewEl = document.getElementById(previewId);
                    if (previewEl) {
                        previewEl.src = data.file_url || URL.createObjectURL(file);
                        previewEl.style.display = 'block';
                    }

                    if (statusEl) {
                        statusEl.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> ${escHtml(file.name)} uploaded`;
                        statusEl.style.color = '#16a34a';
                    }
                    // Clear any previous error
                    const errEl = document.getElementById(errorId);
                    if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                } else {
                    showPhotoError(errorId, data.message || 'Upload failed. Please try again.');
                    input.value = '';
                    if (statusEl) { statusEl.innerHTML = ''; statusEl.style.display = 'none'; }
                }
            })
            .catch(() => {
                showPhotoError(errorId, 'Network error. Please try again.');
                input.value = '';
                if (statusEl) { statusEl.innerHTML = ''; statusEl.style.display = 'none'; }
            });
        });
    }

    // Initialize photo uploads
    initPhotoUpload('photo_address_input', 'address', 'photo_address_status', 'photo_address_error');
    initPhotoUpload('photo_parcel_input',  'parcel',  'photo_parcel_status',  'photo_parcel_error');

    /* ── Step 5: Build summary ── */
    function buildSummary() {
        // Pickup address
        const pEl = document.getElementById('summary_pickup');
        if (pEl) pEl.innerHTML = `
            <div class="summary-address-card">
                <i class="bi bi-geo-alt-fill mt-1"></i>
                <div>
                    <div class="summary-addr-name">${escHtml(state.pickup.name)} · ${escHtml(state.pickup.phone)}</div>
                    <div class="summary-addr-text">${escHtml(state.pickup.addr1)}${state.pickup.addr2 ? ', ' + escHtml(state.pickup.addr2) : ''}<br>${escHtml(state.pickup.city)}, ${escHtml(state.pickup.state)} - ${escHtml(state.pickup.pincode)}</div>
                </div>
            </div>`;

        // Delivery address
        const dEl = document.getElementById('summary_delivery');
        if (dEl) dEl.innerHTML = `
            <div class="summary-address-card">
                <i class="bi bi-geo-alt-fill mt-1" style="color:#ef4444"></i>
                <div>
                    <div class="summary-addr-name">${escHtml(state.delivery.name)} · ${escHtml(state.delivery.phone)}</div>
                    <div class="summary-addr-text">${escHtml(state.delivery.addr1)}${state.delivery.addr2 ? ', ' + escHtml(state.delivery.addr2) : ''}<br>${escHtml(state.delivery.city)}, ${escHtml(state.delivery.state)} - ${escHtml(state.delivery.pincode)}</div>
                </div>
            </div>`;

        // Service + pricing
        const servEl = document.getElementById('summary_service');
        if (servEl) servEl.textContent = `${state.serviceLabel} · ${state.serviceTat}`;

        // Actual weight
        const wEl = document.getElementById('summary_weight');
        if (wEl) wEl.textContent = `${state.weight.toFixed(3)} kg`;

        // Chargeable weight
        const chargeableWeight = state.chargeableWeight || state.weight;
        const cwEl = document.getElementById('summary_chargeable_weight');
        if (cwEl) {
            cwEl.textContent = `${chargeableWeight.toFixed(3)} kg`;
            if (chargeableWeight > state.weight) {
                cwEl.style.color = '#d97706';
                cwEl.style.fontWeight = '600';
            }
        }

        const pcsEl = document.getElementById('summary_pieces');
        if (pcsEl) pcsEl.textContent = state.pieces;

        const dimEl = document.getElementById('summary_dimensions');
        if (dimEl) {
            dimEl.textContent = state.dim_l > 0
                ? `${state.dim_l} × ${state.dim_w} × ${state.dim_h} cm`
                : '—';
        }

        const volEl = document.getElementById('summary_volumetric');
        if (volEl) {
            volEl.textContent = state.volumetricWeight > 0
                ? `${state.volumetricWeight.toFixed(3)} kg`
                : '—';
        }

        // Pricing (no discount)
        const totalPrice = state.servicePrice + (state.packingMaterial ? state.packingCharge : 0);

        const priceEl = document.getElementById('summary_base_price');
        if (priceEl) priceEl.textContent = `₹${state.servicePrice.toLocaleString('en-IN')}`;

        const packPriceEl = document.getElementById('summary_packing_price');
        if (packPriceEl) packPriceEl.textContent = state.packingMaterial ? `₹${state.packingCharge.toLocaleString('en-IN')}` : '—';

        const finalEl = document.getElementById('summary_final_price');
        if (finalEl) finalEl.textContent = `₹${totalPrice.toLocaleString('en-IN')}`;

        // Store final price in state
        state.finalPrice = totalPrice;

        const packEl = document.getElementById('summary_packing');
        if (packEl) packEl.textContent = state.packingMaterial ? `Yes (+₹${state.packingCharge})` : 'No';

        // Booking photos
        const photosWrap = document.getElementById('summary_photos_wrap');
        let anyPhoto = false;

        if (state.photoAddressId) {
            const wrap = document.getElementById('summary_photo_address_wrap');
            const img  = document.getElementById('summary_photo_address');
            if (img)  img.src = `${SITE_URL}/uploads/booking-photos/${encodeURIComponent(state.photoAddressId)}`;
            if (wrap) wrap.style.display = 'block';
            anyPhoto = true;
        }
        if (state.photoParcelId) {
            const wrap = document.getElementById('summary_photo_parcel_wrap');
            const img  = document.getElementById('summary_photo_parcel');
            if (img)  img.src = `${SITE_URL}/uploads/booking-photos/${encodeURIComponent(state.photoParcelId)}`;
            if (wrap) wrap.style.display = 'block';
            anyPhoto = true;
        }
        if (photosWrap) photosWrap.style.display = anyPhoto ? 'block' : 'none';
    }

    /* ── Step 6: Payment options ── */
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            state.paymentMethod = opt.dataset.payment;
        });
    });

    const gstWrap = document.getElementById('gst_invoice_wrap');
    gstWrap && gstWrap.addEventListener('click', () => {
        state.gstInvoice = !state.gstInvoice;
        gstWrap.classList.toggle('checked', state.gstInvoice);
        const gstFields = document.getElementById('gst_fields');
        if (gstFields) gstFields.style.display = state.gstInvoice ? 'block' : 'none';
    });

    const gstinInput = document.getElementById('gstin');
    gstinInput && gstinInput.addEventListener('input', () => { state.gstin = gstinInput.value.trim(); });
    const panInput = document.getElementById('pan_number');
    panInput && panInput.addEventListener('input', () => { state.pan = panInput.value.trim(); });

    /* ── Final submit ── */
    const submitBtn = document.getElementById('submit_booking');
    submitBtn && submitBtn.addEventListener('click', () => {
        if (!validateStep(6)) return;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Booking...';

        const totalPrice = state.servicePrice + (state.packingMaterial ? state.packingCharge : 0);

        const payload = {
            pickup:             state.pickup,
            delivery:           state.delivery,
            delivery_email:     state.delivery.email || '',
            weight:             state.weight,
            chargeable_weight:  state.chargeableWeight || state.weight,
            pieces:             state.pieces,
            declared_value:     state.declaredValue,
            description:        state.description,
            customer_ref:       state.customerRef,
            service_type:       state.serviceType,
            base_price:         state.servicePrice,
            packing_material:   state.packingMaterial ? 1 : 0,
            packing_charge:     state.packingMaterial ? state.packingCharge : 0,
            final_price:        totalPrice,
            ewaybill_no:        state.ewaybillOpt === 'enter' ? state.ewaybillNo : '',
            risk_surcharge:     state.riskSurcharge,
            length:             state.dim_l || 0,
            width:              state.dim_w || 0,
            height:             state.dim_h || 0,
            volumetric_weight:  state.volumetricWeight || 0,
            payment_method:     state.paymentMethod,
            gst_invoice:        state.gstInvoice ? 1 : 0,
            gstin:              state.gstin,
            pan_number:         state.pan,
            photo_address:      state.photoAddressId || '',
            photo_parcel:       state.photoParcelId  || '',
        };

        fetch(`${SITE_URL}/api/shipments.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                clearDraft();
                goToStep(7);
                const trackEl = document.getElementById('final_tracking_no');
                if (trackEl) trackEl.textContent = data.tracking_no;
                // Show GST invoice download link if requested
                if (state.gstInvoice && data.id) {
                    const invoiceLink = document.getElementById('gst_invoice_link');
                    if (invoiceLink) {
                        invoiceLink.href = `${SITE_URL}/customer/gst-invoice.php?id=${data.id}`;
                        invoiceLink.style.display = 'inline-flex';
                    }
                }
            } else {
                showFormError(data.message || 'Booking failed. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Confirm Booking';
            }
        })
        .catch(() => {
            showFormError('Network error. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Confirm Booking';
        });
    });

    function showFormError(msg) {
        const el = document.getElementById('form_error');
        if (el) { el.textContent = msg; el.style.display = 'block'; setTimeout(() => { el.style.display = 'none'; }, 5000); }
        else alert(msg);
    }

    /* ── Utility ── */
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Restore form fields from state ── */
    function restoreFormFields() {
        ['pincode', 'name', 'company', 'phone', 'gstin', 'addr1', 'addr2', 'city', 'state'].forEach(f => {
            const el = document.getElementById(`pickup_${f}`);
            if (el) el.value = state.pickup[f] || '';
        });

        ['pincode', 'name', 'company', 'phone', 'email', 'gstin', 'addr1', 'addr2', 'city', 'state'].forEach(f => {
            const el = document.getElementById(`delivery_${f}`);
            if (el) el.value = state.delivery[f] || '';
        });

        // Show pincode results if verified
        ['pickup', 'delivery'].forEach(type => {
            if (state[`${type}PincodeVerified`] && state[type].city) {
                const resultEl = document.getElementById(`${type}_pincode_result`);
                if (resultEl) {
                    resultEl.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> <strong>${escHtml(state[type].city)}</strong>, ${escHtml(state[type].state)} — Serviceable`;
                    resultEl.classList.add('show');
                }
            }
        });

        if (state.weight > 0 && weightInput) {
            const val = state.unit === 'gm' ? state.weight * 1000 : state.weight;
            weightInput.value = val;
        }
        if (state.pieces > 1 && piecesInput) piecesInput.value = state.pieces;
        if (state.declaredValue > 0 && declaredValueInput) declaredValueInput.value = state.declaredValue;
        if (state.description && descriptionInput) descriptionInput.value = state.description;
        if (state.customerRef && customerRefInput) customerRefInput.value = state.customerRef;

        // Dimensions
        if (state.dim_l > 0 && dimL) dimL.value = state.dim_l;
        if (state.dim_w > 0 && dimW) dimW.value = state.dim_w;
        if (state.dim_h > 0 && dimH) dimH.value = state.dim_h;
        if (state.dim_l > 0) updateVolumetricWeight();

        if (state.unit === 'gm' && unitGm) { unitGm.click(); }
        if (state.unit === 'kg' && unitKg) { unitKg.click(); }

        if (state.ewaybillOpt === 'enter' && state.ewaybillNo && ewaybillNoInput) ewaybillNoInput.value = state.ewaybillNo;
        const ewaybillBtn = document.querySelector(`[data-ewaybill-opt="${state.ewaybillOpt}"]`);
        if (ewaybillBtn) ewaybillBtn.click();

        const riskBtn = document.querySelector(`[data-risk-opt="${state.riskSurcharge}"]`);
        if (riskBtn) riskBtn.click();

        if (state.packingMaterial && packingWrap) {
            state.packingMaterial = false; // reset so click logic works
            packingWrap.classList.add('checked');
            state.packingMaterial = true;
        }

        if (state.paymentMethod) {
            const paymentBtn = document.querySelector(`[data-payment="${state.paymentMethod}"]`);
            if (paymentBtn) paymentBtn.click();
        }

        if (state.gstInvoice && gstWrap) gstWrap.click();
        if (state.gstin && gstinInput) gstinInput.value = state.gstin;
        if (state.pan && panInput) panInput.value = state.pan;

        // Restore photo upload status
        if (state.photoAddressId) {
            const statusEl = document.getElementById('photo_address_status');
            if (statusEl) {
                statusEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Address photo uploaded';
                statusEl.style.color = '#16a34a';
                statusEl.style.display = 'flex';
            }
        }
        if (state.photoParcelId) {
            const statusEl = document.getElementById('photo_parcel_status');
            if (statusEl) {
                statusEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Parcel photo uploaded';
                statusEl.style.color = '#16a34a';
                statusEl.style.display = 'flex';
            }
        }
    }

    /* ── Clear Form Button ── */
    window.clearBookingForm = function () {
        if (confirm('Are you sure you want to clear all form data? This cannot be undone.')) {
            isClearing = true; // Block any auto-saves
            clearInterval(autoSaveInterval);
            clearDraft();
            // Reset state object
            Object.keys(state).forEach(key => {
                if (defaultState.hasOwnProperty(key)) {
                    state[key] = JSON.parse(JSON.stringify(defaultState[key]));
                }
            });
            // Navigate to a fresh URL
            window.location.href = window.location.pathname + '?fresh=' + Date.now();
        }
    };

    /* ── Add clear button to the page ── */
    function addClearButton() {
        const topbar = document.querySelector('.cust-topbar-actions');
        if (topbar && !document.querySelector('[onclick*="clearBookingForm"]')) {
            const clearBtn = document.createElement('button');
            clearBtn.className = 'btn-outline-admin';
            clearBtn.style.cssText = 'font-size:12px;padding:7px 14px;background:#fee2e2;color:#dc2626;border-color:#fecaca;cursor:pointer;border:1px solid;border-radius:6px;margin-right:8px;';
            clearBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Clear Form';
            clearBtn.onclick = clearBookingForm;
            topbar.insertBefore(clearBtn, topbar.firstChild);
        }
    }

    function syncStep1FromDOM() {
        ['pickup', 'delivery'].forEach(type => {
            const fields = ['pincode', 'name', 'company', 'phone', 'email', 'gstin', 'addr1', 'addr2', 'city', 'state'];
            fields.forEach(f => {
                const el = document.getElementById(`${type}_${f}`);
                if (el) state[type][f] = el.value.trim();
            });
        });
    }

    /* ── Init ── */
    goToStep(1);
    restoreFormFields();
    addClearButton();

    if ((state.pickup && state.pickup.pincode) || (state.delivery && state.delivery.pincode) || state.weight > 0) {
        const indicator = document.createElement('div');
        indicator.style.cssText = 'background:#d1fae5;color:#065f46;padding:8px 12px;border-radius:6px;margin-bottom:12px;font-size:13px;display:flex;align-items:center;gap:8px;';
        indicator.innerHTML = '<i class="bi bi-info-circle"></i> <strong>Draft recovered:</strong> Your previous form data has been restored.';
        const mainContent = document.querySelector('.delivery-wizard-wrap');
        if (mainContent && mainContent.parentNode) {
            mainContent.parentNode.insertBefore(indicator, mainContent);
        }
    }

})();
