<?php /* Rate Calculator Modal — included in all customer portal pages */ ?>

<!-- ===== RATE CALCULATOR MODAL ===== -->
<div class="modal fade" id="rateCalcModal" tabindex="-1" aria-labelledby="rateCalcModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable rc-dialog">
        <div class="modal-content rc-modal">

            <div class="modal-header rc-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="rc-header-icon"><i class="bi bi-calculator-fill"></i></div>
                    <div>
                        <h6 class="modal-title mb-0" id="rateCalcModalLabel">Rate Calculator</h6>
                        <div class="rc-header-sub">Quick courier rate estimate</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body rc-modal-body">

                <!-- Pickup Pincode -->
                <div class="rc-field-group">
                    <label class="rc-label"><i class="bi bi-geo-alt-fill text-primary me-1"></i>Pickup Pincode</label>
                    <div class="rc-pincode-row">
                        <input type="text" class="wizard-input" id="rc_pickup_pincode"
                               maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit pincode"
                               oninput="rcResetResults()">
                        <button class="btn-lookup" id="rc_pickup_btn" onclick="rcLookup('pickup')">
                            <i class="bi bi-search"></i> Check
                        </button>
                    </div>
                    <div class="pincode-result" id="rc_pickup_result"></div>
                </div>

                <!-- Delivery Pincode -->
                <div class="rc-field-group">
                    <label class="rc-label"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Delivery Pincode</label>
                    <div class="rc-pincode-row">
                        <input type="text" class="wizard-input" id="rc_delivery_pincode"
                               maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit pincode"
                               oninput="rcResetResults()">
                        <button class="btn-lookup" id="rc_delivery_btn" onclick="rcLookup('delivery')">
                            <i class="bi bi-search"></i> Check
                        </button>
                    </div>
                    <div class="pincode-result" id="rc_delivery_result"></div>
                </div>

                <!-- Weight -->
                <div class="rc-field-group">
                    <label class="rc-label"><i class="bi bi-weight me-1"></i>Shipment Weight</label>
                    <div class="weight-input-wrap">
                        <input type="number" id="rc_weight" step="0.001" min="0.001"
                               placeholder="0.000" oninput="rcResetResults()">
                        <button class="weight-unit-btn active" id="rc_unit_kg" onclick="rcSetUnit('kg')">KG</button>
                        <button class="weight-unit-btn" id="rc_unit_gm" onclick="rcSetUnit('gm')">GM</button>
                    </div>
                </div>

                <label class="cust-checkbox-wrap mb-3" id="rc_packing_wrap" style="cursor:pointer;">
                    <input type="checkbox" id="rc_packing_material" style="display:none;" onchange="rcResetResults()">
                    <div class="cust-checkbox-box"><i class="bi bi-check-lg"></i></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;">Packing Material <span id="rc_packing_charge_hint" style="font-size:11px;color:#6b7280;">(optional)</span></div>
                        <div style="font-size:12px;color:var(--muted);">Enter packing material charge for this estimate</div>
                    </div>
                </label>
                <div id="rc_packing_charge_row" style="display:none;margin-top:-4px;margin-bottom:14px;">
                    <label class="rc-label">Packing Material Charge (Rs.)</label>
                    <input type="number" id="rc_packing_charge" class="wizard-input" min="0" step="0.01" placeholder="0.00" oninput="rcResetResults()">
                </div>

                <!-- Error -->
                <div id="rc_error" class="cust-alert cust-alert-danger" style="display:none;margin-bottom:12px;"></div>

                <!-- Calculate Button -->
                <button class="btn-new-delivery w-100 justify-content-center" id="rc_calc_btn" onclick="rcCalculate()">
                    <i class="bi bi-calculator me-2"></i> Calculate Rates
                </button>

                <!-- Results -->
                <div id="rc_results"></div>

            </div><!-- /modal-body -->
        </div>
    </div>
</div>

<style>
/* ── Rate Calculator Modal ── */
.rc-dialog { max-width: 500px; }

.rc-modal { border-radius: 20px; overflow: hidden; border: none; }

.rc-modal-header {
    background: linear-gradient(135deg, var(--primary) 0%, #3B5BDB 100%);
    padding: 18px 22px;
    border-bottom: none;
    color: #fff;
}
.rc-header-icon {
    width: 38px; height: 38px;
    background: rgba(255,255,255,.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.rc-header-sub { font-size: 11px; opacity: .75; margin-top: 1px; }

.rc-modal-body { padding: 22px; }

.rc-field-group { margin-bottom: 18px; }
.rc-label {
    display: block;
    font-size: 12px; font-weight: 600; color: var(--text);
    margin-bottom: 7px;
}
.rc-pincode-row {
    display: flex; gap: 8px; align-items: flex-start;
}
.rc-pincode-row .wizard-input { flex: 1; }

/* Results area */
.rc-results-header {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;
    font-size: 12px; color: var(--muted);
    padding: 14px 0 10px;
    border-top: 1.5px solid var(--border);
    margin-top: 18px;
}
.rc-zone-badge {
    background: rgba(0,26,147,.08);
    color: var(--primary);
    border-radius: 20px; padding: 3px 10px;
    font-size: 11px; font-weight: 600;
}
.rc-service-row {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--bg);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 8px;
    border: 1.5px solid transparent;
    transition: border-color .15s, background .15s;
}
.rc-service-row:hover { border-color: var(--primary); background: rgba(0,26,147,.03); }
.rc-service-left { display: flex; align-items: center; gap: 12px; }
.rc-service-icon { font-size: 20px; color: var(--primary); width: 28px; text-align: center; flex-shrink: 0; }
.rc-service-name { font-size: 13px; font-weight: 600; color: var(--text); }
.rc-service-tat  { font-size: 11px; color: var(--muted); margin-top: 2px; }
.rc-service-price {
    font-size: 18px; font-weight: 700;
    font-family: 'Montserrat', sans-serif;
    color: var(--primary);
    white-space: nowrap;
}
.rc-disclaimer { font-size: 11px; color: var(--muted); text-align: center; margin-top: 10px; }

/* Rate Calculator button in topbar */
.btn-rate-calc {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(0,26,147,.08);
    color: var(--primary);
    border: 1.5px solid rgba(0,26,147,.15);
    border-radius: 10px;
    padding: 7px 14px;
    font-size: 12px; font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
}
.btn-rate-calc:hover {
    background: var(--primary); color: #fff;
    border-color: var(--primary);
}
</style>

<script>
(function () {
    'use strict';
    const RC_URL = '<?= rtrim(SITE_URL, '/') ?>';
    let rcUnit = 'kg';
    let rcPackingCharge = 0;

    false && fetch(`${RC_URL}/api/settings.php?key=packing_charge`)
        .then(r => r.json())
        .then(data => {
            rcPackingCharge = Math.max(0, parseFloat(data.value || 0) || 0);
            const hint = document.getElementById('rc_packing_charge_hint');
            if (hint) hint.textContent = `(₹${rcPackingCharge.toLocaleString('en-IN')})`;
        })
        .catch(() => {});

    document.getElementById('rc_packing_wrap')?.addEventListener('click', e => {
        e.preventDefault();
        const chk = document.getElementById('rc_packing_material');
        if (!chk) return;
        chk.checked = !chk.checked;
        document.getElementById('rc_packing_wrap')?.classList.toggle('checked', chk.checked);
        document.getElementById('rc_packing_charge_row').style.display = chk.checked ? 'block' : 'none';
        if (!chk.checked) {
            rcPackingCharge = 0;
            const inp = document.getElementById('rc_packing_charge');
            if (inp) inp.value = '';
            const hint = document.getElementById('rc_packing_charge_hint');
            if (hint) hint.textContent = '(optional)';
        }
        rcResetResults();
    });
    document.getElementById('rc_packing_charge')?.addEventListener('input', e => {
        rcPackingCharge = Math.max(0, parseFloat(e.target.value || 0) || 0);
        const hint = document.getElementById('rc_packing_charge_hint');
        if (hint) hint.textContent = rcPackingCharge > 0 ? `(Rs.${rcPackingCharge.toLocaleString('en-IN')})` : '(optional)';
    });

    /* ── Unit toggle ── */
    window.rcSetUnit = function (u) {
        rcUnit = u;
        document.getElementById('rc_unit_kg').classList.toggle('active', u === 'kg');
        document.getElementById('rc_unit_gm').classList.toggle('active', u === 'gm');
    };

    /* ── Pincode lookup ── */
    window.rcLookup = function (type) {
        const inp = document.getElementById(`rc_${type}_pincode`);
        const btn = document.getElementById(`rc_${type}_btn`);
        const res = document.getElementById(`rc_${type}_result`);
        const pin = inp ? inp.value.trim() : '';
        if (!pin || pin.length < 6) {
            if (res) {
                res.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> Please enter 6 digit pincode`;
                res.classList.add('show');
            }
            return;
        }

        if (btn) btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        fetch(`${RC_URL}/api/pincode.php?pincode=${encodeURIComponent(pin)}`)
            .then(r => r.json())
            .then(data => {
                if (btn) btn.innerHTML = '<i class="bi bi-search"></i> Check';
                if (data.success) {
                    const info = data.data;
                    if (res) {
                        res.innerHTML = `<i class="bi bi-geo-alt-fill"></i> <strong>${esc(info.city)}</strong>, ${esc(info.state)}`;
                        res.classList.add('show');
                    }
                } else {
                    if (res) {
                        res.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> Pincode not serviceable`;
                        res.classList.add('show');
                    }
                }
            })
            .catch(() => { if (btn) btn.innerHTML = '<i class="bi bi-search"></i> Check'; });
    };

    /* ── Reset results on input change ── */
    window.rcResetResults = function () {
        const r = document.getElementById('rc_results');
        if (r) r.innerHTML = '';
    };

    /* ── Calculate ── */
    window.rcCalculate = function () {
        const pickup   = (document.getElementById('rc_pickup_pincode')?.value   || '').trim();
        const delivery = (document.getElementById('rc_delivery_pincode')?.value || '').trim();
        const wRaw     = parseFloat(document.getElementById('rc_weight')?.value || 0) || 0;
        const weight   = rcUnit === 'gm' ? wRaw / 1000 : wRaw;
        const errEl    = document.getElementById('rc_error');
        const results  = document.getElementById('rc_results');
        const btn      = document.getElementById('rc_calc_btn');

        if (errEl) errEl.style.display = 'none';

        if (!pickup || pickup.length < 6) {
            if (errEl) { errEl.textContent = 'Please enter 6 digit pincode'; errEl.style.display = 'block'; } return;
        }
        if (!delivery || delivery.length < 6) {
            if (errEl) { errEl.textContent = 'Please enter 6 digit pincode'; errEl.style.display = 'block'; } return;
        }
        if (weight <= 0) {
            if (errEl) { errEl.textContent = 'Enter a valid weight.'; errEl.style.display = 'block'; } return;
        }
        if (weight > 60) {
            if (errEl) { errEl.textContent = 'Maximum allowable weight is 60 kg'; errEl.style.display = 'block'; } return;
        }

        if (btn) { btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Calculating…'; btn.disabled = true; }
        if (results) results.innerHTML = '';

        fetch(`${RC_URL}/api/pricing.php?weight=${weight}&pickup=${encodeURIComponent(pickup)}&delivery=${encodeURIComponent(delivery)}`)
            .then(r => r.json())
            .then(data => {
                if (btn) { btn.innerHTML = '<i class="bi bi-calculator me-2"></i> Calculate Rates'; btn.disabled = false; }
                if (data.success && data.services && data.services.length > 0) {
                    rcRenderResults(data.services, data.zone, weight);
                } else {
                    if (results) results.innerHTML = '<div class="cust-alert cust-alert-warning mt-3">No pricing available for this route.</div>';
                }
            })
            .catch(() => {
                if (btn) { btn.innerHTML = '<i class="bi bi-calculator me-2"></i> Calculate Rates'; btn.disabled = false; }
                if (errEl) { errEl.textContent = 'Network error. Please try again.'; errEl.style.display = 'block'; }
            });
    };

    /* ── Render Results ── */
    function rcRenderResults(services, zone, weight) {
        const results = document.getElementById('rc_results');
        if (!results) return;

        const zoneLabels = {
            within_city: 'Within City', within_state: 'Within State',
            metro: 'Metro', rest_of_india: 'Rest of India'
        };
        const svcMap = {
            standard:  { icon: 'bi-truck',          label: 'Standard Express' },
            premium:   { icon: 'bi-lightning-fill',  label: 'Premium Express'  },
            air_cargo: { icon: 'bi-airplane',        label: 'Air Cargo'         },
            surface:   { icon: 'bi-boxes',           label: 'Surface Cargo'     },
        };

        const zoneTxt = zone ? (zoneLabels[zone] || zone) : '';
        const includePacking = !!document.getElementById('rc_packing_material')?.checked;
        rcPackingCharge = Math.max(0, parseFloat(document.getElementById('rc_packing_charge')?.value || 0) || 0);
        if (includePacking && rcPackingCharge <= 0) {
            const errEl = document.getElementById('rc_error');
            if (errEl) { errEl.textContent = 'Enter packing material charge.'; errEl.style.display = 'block'; }
            return;
        }
        const packingCharge = includePacking ? rcPackingCharge : 0;
        const rows = services.map(svc => {
            const m = svcMap[svc.type] || { icon: 'bi-box', label: svc.type };
            const basePrice = parseFloat(svc.price) || 0;
            const totalPrice = basePrice + packingCharge;
            if (includePacking) svc.price = totalPrice;
            return `<div class="rc-service-row">
                <div class="rc-service-left">
                    <i class="${m.icon} rc-service-icon"></i>
                    <div>
                        <div class="rc-service-name">${esc(m.label)}</div>
                        <div class="rc-service-tat"><i class="bi bi-clock me-1"></i>${esc(svc.tat_label)} · Est. ${esc(svc.eta)}</div>
                    </div>
                </div>
                <div class="rc-service-price">₹${svc.price.toLocaleString('en-IN')}</div>
            </div>`;
        }).join('');

        results.innerHTML = `
            <div class="rc-results-header">
                <span><i class="bi bi-weight me-1"></i>${weight.toFixed(3)} kg</span>
                ${zoneTxt ? `<span class="rc-zone-badge"><i class="bi bi-geo-alt me-1"></i>${esc(zoneTxt)}</span>` : ''}
            </div>
            ${rows}
            ${includePacking ? `<p class="rc-disclaimer">Packing Material included: ₹${packingCharge.toLocaleString('en-IN')} per shipment.</p>` : ''}
            <p class="rc-disclaimer">* Estimates only. Final charges may vary based on actual weight &amp; dimensions.</p>`;
    }

    /* ── Enter key shortcuts ── */
    ['rc_pickup_pincode', 'rc_delivery_pincode'].forEach(id => {
        const el = document.getElementById(id);
        el?.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '').slice(0, 6);
        });
        el?.addEventListener('paste', e => {
            e.preventDefault();
            el.value = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            rcResetResults();
        });
        el?.addEventListener('keydown', e => {
            if (e.key === 'Enter') rcLookup(id.includes('pickup') ? 'pickup' : 'delivery');
        });
    });
    document.getElementById('rc_weight')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') rcCalculate();
    });

    /* ── Reset modal state on close ── */
    document.getElementById('rateCalcModal')?.addEventListener('hidden.bs.modal', () => {
        ['rc_pickup_pincode','rc_delivery_pincode','rc_weight'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const packing = document.getElementById('rc_packing_material');
        if (packing) packing.checked = false;
        document.getElementById('rc_packing_wrap')?.classList.remove('checked');
        document.getElementById('rc_packing_charge_row').style.display = 'none';
        const packCharge = document.getElementById('rc_packing_charge');
        if (packCharge) packCharge.value = '';
        rcPackingCharge = 0;
        const packHint = document.getElementById('rc_packing_charge_hint');
        if (packHint) packHint.textContent = '(optional)';
        ['rc_pickup_result','rc_delivery_result'].forEach(id => {
            const el = document.getElementById(id); if (el) { el.innerHTML = ''; el.classList.remove('show'); }
        });
        const r = document.getElementById('rc_results'); if (r) r.innerHTML = '';
        const e = document.getElementById('rc_error'); if (e) e.style.display = 'none';
        rcSetUnit('kg');
    });

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>
