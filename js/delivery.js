/* ============================================================
   delivery.js — Careygo Multi-Step Delivery Wizard
   ============================================================ */
(function () {
    'use strict';

    const SITE_URL = window.SITE_URL || '';

    /* ── State ── */
    const state = {
        step: 1,
        totalSteps: 6,
        pickup: { pincode: '', city: '', state: '', name: '', phone: '', addr1: '', addr2: '' },
        delivery: { pincode: '', city: '', state: '', name: '', phone: '', addr1: '', addr2: '', email: '' },
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
        packingMaterial: false,
        paymentMethod: 'prepaid',
        gstInvoice: false,
        gstin: '',
        pan: '',
        savedPickupAddresses: [],
        savedDeliveryAddresses: [],
        selectedPickupAddrId: null,
        selectedDeliveryAddrId: null,
        useNewPickupAddr: false,
        useNewDeliveryAddr: false,
    };

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
            if (!state.pickup.pincode) { showErr('pickup_pincode', 'Enter pickup pincode'); ok = false; }
            if (!state.pickup.name)    { showErr('pickup_name',    'Enter full name');      ok = false; }
            if (!state.pickup.phone)   { showErr('pickup_phone',   'Enter mobile number'); ok = false; }
            if (!state.pickup.addr1)   { showErr('pickup_addr1',   'Enter address line 1'); ok = false; }
            if (!state.pickup.city)    { showErr('pickup_city',    'Enter city');           ok = false; }
            if (!state.pickup.state)   { showErr('pickup_state',   'Enter state');          ok = false; }

            if (!state.delivery.pincode) { showErr('delivery_pincode', 'Enter delivery pincode'); ok = false; }
            if (!state.delivery.name)    { showErr('delivery_name',    'Enter full name');         ok = false; }
            if (!state.delivery.phone)   { showErr('delivery_phone',   'Enter mobile number');    ok = false; }
            if (!state.delivery.addr1)   { showErr('delivery_addr1',   'Enter address line 1');   ok = false; }
            if (!state.delivery.city)    { showErr('delivery_city',    'Enter city');              ok = false; }
            if (!state.delivery.state)   { showErr('delivery_state',   'Enter state');             ok = false; }
        }
        if (n === 2) {
            if (!state.weight || state.weight <= 0) { showErr('weight', 'Enter a valid weight'); ok = false; }
            if (!state.pieces || state.pieces < 1)  { showErr('pieces', 'Min 1 piece required'); ok = false; }
        }
        if (n === 3) {
            if (!state.serviceType) { showErr('service_error', 'Please select a service type'); ok = false; }
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

    function clearErrors() {
        document.querySelectorAll('.wizard-error').forEach(e => { e.classList.remove('show'); e.textContent = ''; });
        document.querySelectorAll('.wizard-input.is-error, .wizard-select.is-error').forEach(e => e.classList.remove('is-error'));
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
        if (!pincode || pincode.length < 6) return;

        btn && (btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>');
        fetch(`${SITE_URL}/api/pincode.php?pincode=${encodeURIComponent(pincode)}`)
            .then(r => r.json())
            .then(data => {
                btn && (btn.innerHTML = '<i class="bi bi-search"></i> Check');
                if (data.success) {
                    const info = data.data;
                    state[type].city  = info.city;
                    state[type].state = info.state;
                    if (resultEl) {
                        resultEl.innerHTML = `<i class="bi bi-geo-alt-fill"></i> <strong>${info.city}</strong>, ${info.state}`;
                        resultEl.classList.add('show');
                    }
                    // auto-fill city/state fields
                    const cityEl  = document.getElementById(`${type}_city`);
                    const stateEl = document.getElementById(`${type}_state`);
                    if (cityEl)  { cityEl.value  = info.city;  state[type].city  = info.city; }
                    if (stateEl) { stateEl.value = info.state; state[type].state = info.state; }
                    loadSavedAddresses(type, pincode);
                } else {
                    if (resultEl) { resultEl.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> Pincode not found or not serviceable`; resultEl.classList.add('show'); }
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
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const type = inp.dataset.pincodeInput;
                state[type].pincode = inp.value.trim();
                lookupPincode(inp.value.trim(), type);
            }
        });
        inp.addEventListener('input', () => { state[inp.dataset.pincodeInput].pincode = inp.value.trim(); });
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
        // Auto-select first
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
            ? ['name', 'phone', 'email', 'addr1', 'addr2', 'city', 'state']
            : ['name', 'phone', 'addr1', 'addr2', 'city', 'state'];
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
    weightInput && weightInput.addEventListener('input', () => { state.weight = getWeightInKg(); });

    const piecesInput = document.getElementById('pieces');
    piecesInput && piecesInput.addEventListener('input', () => { state.pieces = parseInt(piecesInput.value) || 1; });

    const declaredValueInput = document.getElementById('declared_value');
    declaredValueInput && declaredValueInput.addEventListener('input', () => { state.declaredValue = parseFloat(declaredValueInput.value) || 0; });

    const descriptionInput = document.getElementById('description');
    descriptionInput && descriptionInput.addEventListener('input', () => { state.description = descriptionInput.value.trim(); });

    const customerRefInput = document.getElementById('customer_ref');
    customerRefInput && customerRefInput.addEventListener('input', () => { state.customerRef = customerRefInput.value.trim(); });

    /* ── Step 3: Load services when entering ── */
    document.querySelector('[data-step2-next]') && document.querySelector('[data-step2-next]').addEventListener('click', () => {
        if (validateStep(2)) { loadServices(); goToStep(3); }
    });

    function loadServices() {
        const container = document.getElementById('services_container');
        if (!container) return;
        const wt = getWeightInKg();
        state.weight = wt;

        container.innerHTML = `<div class="price-loading"><span class="spinner-border spinner-border-sm me-2"></span> Calculating prices...</div>`;

        fetch(`${SITE_URL}/api/pricing.php?weight=${wt}&pickup=${encodeURIComponent(state.pickup.pincode)}&delivery=${encodeURIComponent(state.delivery.pincode)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) renderServices(data.services);
                else container.innerHTML = `<div class="alert alert-warning">Could not load pricing. Please try again.</div>`;
            })
            .catch(() => { container.innerHTML = `<div class="alert alert-danger">Network error. Please try again.</div>`; });
    }

    function renderServices(services) {
        const container = document.getElementById('services_container');
        if (!container) return;

        const serviceMap = {
            standard:  { icon: 'bi-truck',         label: 'Standard Express', code: 'STD-EXP' },
            premium:   { icon: 'bi-lightning-fill', label: 'Premium Express',  code: 'PRM-EXP' },
            air_cargo: { icon: 'bi-airplane',       label: 'Air Cargo',         code: 'AIR-CGO' },
            surface:   { icon: 'bi-boxes',          label: 'Surface Cargo',     code: 'SRF-CGO' },
        };

        container.innerHTML = services.map(svc => {
            const meta = serviceMap[svc.type] || { icon: 'bi-box', label: svc.type, code: '' };
            return `
            <div class="service-card" data-service="${svc.type}" onclick="selectService('${svc.type}', ${svc.price}, '${escHtml(meta.label)}', '${escHtml(svc.tat_label)}')">
                <div class="service-card-check"><i class="bi bi-check-lg"></i></div>
                <div class="service-card-top">
                    <div>
                        <div class="service-card-name"><i class="${meta.icon} me-2" style="color:var(--primary)"></i>${meta.label}</div>
                        <div class="service-card-code">${meta.code}</div>
                    </div>
                    <div style="text-align:right">
                        <div class="service-card-price">₹${svc.price.toLocaleString('en-IN')}</div>
                        <div class="service-card-price-label">Estimated</div>
                    </div>
                </div>
                <div class="service-card-meta">
                    <div class="service-card-meta-item"><i class="bi bi-clock"></i> ${escHtml(svc.tat_label)}</div>
                    <div class="service-card-meta-item"><i class="bi bi-calendar3"></i> Est. ${escHtml(svc.eta)}</div>
                    <div class="service-card-meta-item"><i class="bi bi-weight"></i> ${state.weight.toFixed(3)} kg</div>
                </div>
            </div>`;
        }).join('');
    }

    window.selectService = function (type, price, label, tat) {
        document.querySelectorAll('.service-card').forEach(c => c.classList.toggle('selected', c.dataset.service === type));
        state.serviceType  = type;
        state.servicePrice = price;
        state.serviceLabel = label;
        state.serviceTat   = tat;
    };

    /* ── Step 4: E-waybill options ── */
    document.querySelectorAll('.ewaybill-opt').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.ewaybill-opt').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            state.ewaybillOpt = opt.dataset.ewaybillOpt;
            const inputRow = document.getElementById('ewaybill_input_row');
            if (inputRow) inputRow.style.display = state.ewaybillOpt === 'enter' ? 'block' : 'none';
        });
    });

    const ewaybillNoInput = document.getElementById('ewaybill_no');
    ewaybillNoInput && ewaybillNoInput.addEventListener('input', () => { state.ewaybillNo = ewaybillNoInput.value.trim(); });

    /* Packing material */
    const packingWrap = document.getElementById('packing_material_wrap');
    packingWrap && packingWrap.addEventListener('click', () => {
        state.packingMaterial = !state.packingMaterial;
        packingWrap.classList.toggle('checked', state.packingMaterial);
    });

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

        const wEl = document.getElementById('summary_weight');
        if (wEl) wEl.textContent = `${state.weight.toFixed(3)} kg`;

        const pcsEl = document.getElementById('summary_pieces');
        if (pcsEl) pcsEl.textContent = state.pieces;

        const priceEl = document.getElementById('summary_base_price');
        if (priceEl) priceEl.textContent = `₹${state.servicePrice.toLocaleString('en-IN')}`;

        const finalEl = document.getElementById('summary_final_price');
        if (finalEl) finalEl.textContent = `₹${state.servicePrice.toLocaleString('en-IN')}`;

        const packEl = document.getElementById('summary_packing');
        if (packEl) packEl.textContent = state.packingMaterial ? 'Yes' : 'No';
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

        const payload = {
            pickup:           state.pickup,
            delivery:         state.delivery,
            delivery_email:   state.delivery.email || '',
            weight:           state.weight,
            pieces:           state.pieces,
            declared_value:   state.declaredValue,
            description:      state.description,
            customer_ref:     state.customerRef,
            service_type:     state.serviceType,
            base_price:       state.servicePrice,
            final_price:      state.servicePrice,
            ewaybill_no:      state.ewaybillOpt === 'enter' ? state.ewaybillNo : '',
            packing_material: state.packingMaterial ? 1 : 0,
            payment_method:   state.paymentMethod,
            gst_invoice:      state.gstInvoice ? 1 : 0,
            gstin:            state.gstin,
            pan_number:       state.pan,
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
                goToStep(7); // success screen
                const trackEl = document.getElementById('final_tracking_no');
                if (trackEl) trackEl.textContent = data.tracking_no;
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

    /* ── Init first step ── */
    goToStep(1);

})();
