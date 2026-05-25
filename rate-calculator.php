<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$pageTitle = 'Rate Calculator - Careygo';
$metaDescription = 'Calculate Careygo courier rate estimates by pickup pincode, delivery pincode, and shipment weight.';
$metaKeywords = 'Careygo rate calculator, courier charges, shipping rate estimate, courier price calculator, pincode courier rates';
$canonicalUrl = SITE_URL . '/rate-calculator.php';

require_once __DIR__ . '/includes/header.php';
?>

<main class="public-tool-page">
    <section class="public-tool-band">
        <div class="container">
            <div class="public-tool-shell">
                <div class="public-tool-heading">
                    <span class="public-tool-icon"><i class="bi bi-calculator-fill"></i></span>
                    <div>
                        <h1>Rate Calculator</h1>
                        <p>Get a quick courier rate estimate without signing in.</p>
                    </div>
                </div>
                <div class="public-tool-actions" aria-label="Page actions">
                    <a href="index.php" class="public-tool-action">
                        <i class="bi bi-house-door"></i> Back to Home
                    </a>
                </div>

                <div class="rate-card">
                    <div class="rc-field-group">
                        <label class="rc-label"><i class="bi bi-geo-alt-fill text-primary me-1"></i>Pickup Pincode</label>
                        <div class="rc-pincode-row">
                            <input type="text" class="wizard-input" id="rc_pickup_pincode"
                                   maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit pincode"
                                   oninput="rcResetResults()">
                        </div>
                        <div class="pincode-result" id="rc_pickup_result"></div>
                    </div>

                    <div class="rc-field-group">
                        <label class="rc-label"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Delivery Pincode</label>
                        <div class="rc-pincode-row">
                            <input type="text" class="wizard-input" id="rc_delivery_pincode"
                                   maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit pincode"
                                   oninput="rcResetResults()">
                        </div>
                        <div class="pincode-result" id="rc_delivery_result"></div>
                    </div>

                    <div class="rc-field-group">
                        <label class="rc-label"><i class="bi bi-weight me-1"></i>Shipment Weight</label>
                        <div class="weight-input-wrap">
                            <input type="number" id="rc_weight" step="0.001" min="0.001" placeholder="0.000" oninput="rcResetResults()">
                            <button type="button" class="weight-unit-btn active" id="rc_unit_kg" onclick="rcSetUnit('kg')">KG</button>
                            <button type="button" class="weight-unit-btn" id="rc_unit_gm" onclick="rcSetUnit('gm')">GM</button>
                        </div>
                    </div>

                    <label class="cust-checkbox-wrap mb-3" id="rc_packing_wrap">
                        <input type="checkbox" id="rc_packing_material" onchange="rcResetResults()">
                        <div class="cust-checkbox-box"><i class="bi bi-check-lg"></i></div>
                        <div>
                            <div style="font-size:13px;font-weight:600;">Packing Material <span id="rc_packing_charge_hint" style="font-size:11px;color:#6b7280;">(optional)</span></div>
                            <div style="font-size:12px;color:#6b7280;">Enter packing material charge for this estimate</div>
                        </div>
                    </label>

                    <div id="rc_packing_charge_row" style="display:block;margin-top:-4px;margin-bottom:14px;">
                        <label class="rc-label">Packing Material Charge (Rs.)</label>
                        <input type="number" id="rc_packing_charge" class="wizard-input" min="0" max="9999" step="1" placeholder="0" oninput="rcResetResults()">
                    </div>

                    <div style="margin-top:-4px;margin-bottom:14px;">
                        <label class="rc-label">Tempo Charge (Rs.)</label>
                        <input type="number" id="rc_tempo_charge" class="wizard-input" min="0" max="9999" step="1" placeholder="0" oninput="rcResetResults()">
                    </div>

                    <div id="rc_error" class="cust-alert cust-alert-danger" style="display:none;margin-bottom:12px;"></div>

                    <div class="rc-button-row">
                        <button type="button" class="btn-new-delivery justify-content-center" id="rc_calc_btn" onclick="rcCalculate()">
                            <i class="bi bi-calculator me-2"></i> Calculate Rates
                        </button>
                        <button type="button" class="btn-reset-rate justify-content-center" id="rc_reset_btn" onclick="rcResetCalculator()">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Reset
                        </button>
                    </div>

                    <div id="rc_results"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.public-tool-page {
    background: #f0f2f9;
    font-family: 'Poppins', sans-serif;
}
.public-tool-band {
    padding: 48px 0 64px;
}
.public-tool-shell {
    max-width: 560px;
    margin: 0 auto;
}
.public-tool-heading {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
}
.public-tool-heading h1 {
    color: #1a1a2e;
    font-family: 'Montserrat', sans-serif;
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 4px;
}
.public-tool-heading p {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}
.public-tool-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin: -8px 0 18px;
}
.public-tool-action {
    align-items: center;
    background: #fff;
    border: 1.5px solid #e4e7f0;
    border-radius: 10px;
    color: #001A93;
    cursor: pointer;
    display: inline-flex;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    gap: 6px;
    min-height: 38px;
    padding: 8px 14px;
    text-decoration: none;
}
.public-tool-action:hover,
.public-tool-action:focus {
    background: #001A93;
    border-color: #001A93;
    color: #fff;
}
.public-tool-icon {
    align-items: center;
    background: #001A93;
    border-radius: 14px;
    color: #fff;
    display: inline-flex;
    flex: 0 0 52px;
    font-size: 22px;
    height: 52px;
    justify-content: center;
    width: 52px;
}
.rate-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,26,147,0.08);
    padding: 28px;
}
.rc-field-group {
    margin-bottom: 18px;
}
.rc-label {
    color: #1a1a2e;
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 7px;
}
.rc-pincode-row {
    align-items: flex-start;
    display: flex;
    gap: 8px;
}
.rc-pincode-row .wizard-input {
    flex: 1;
}
.wizard-input {
    background: #fff;
    border: 1.5px solid #e4e7f0;
    border-radius: 10px;
    color: #1a1a2e;
    font: inherit;
    min-height: 44px;
    outline: none;
    padding: 10px 13px;
    width: 100%;
}
.wizard-input:focus {
    border-color: #001A93;
    box-shadow: 0 0 0 3px rgba(0,26,147,0.08);
}
.pincode-result {
    color: #6b7280;
    display: none;
    font-size: 12px;
    margin-top: 7px;
}
.pincode-result.show {
    display: block;
}
.weight-input-wrap {
    align-items: stretch;
    border: 1.5px solid #e4e7f0;
    border-radius: 10px;
    display: flex;
    overflow: hidden;
}
.weight-input-wrap:focus-within {
    border-color: #001A93;
    box-shadow: 0 0 0 3px rgba(0,26,147,0.08);
}
.weight-input-wrap input {
    border: 0;
    flex: 1;
    min-width: 0;
    outline: 0;
    padding: 10px 13px;
}
.weight-unit-btn {
    background: #f0f2f9;
    border: 0;
    border-left: 1px solid #e4e7f0;
    color: #6b7280;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    min-width: 54px;
}
.weight-unit-btn.active {
    background: #001A93;
    color: #fff;
}
.cust-checkbox-wrap {
    align-items: center;
    border: 1.5px solid #e4e7f0;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    gap: 12px;
    padding: 12px;
}
.cust-checkbox-wrap input[type="checkbox"] {
    display: none;
}
.cust-checkbox-box {
    align-items: center;
    border: 1.5px solid #d1d5db;
    border-radius: 6px;
    color: transparent;
    display: flex;
    flex: 0 0 22px;
    height: 22px;
    justify-content: center;
    width: 22px;
}
.cust-checkbox-wrap.checked {
    border-color: #001A93;
}
.cust-checkbox-wrap.checked .cust-checkbox-box {
    background: #001A93;
    border-color: #001A93;
    color: #fff;
}
.cust-alert {
    border-radius: 10px;
    font-size: 13px;
    padding: 11px 13px;
}
.cust-alert-danger,
.cust-alert-error {
    background: rgba(239,68,68,0.1);
    color: #b91c1c;
}
.cust-alert-warning {
    background: rgba(245,158,11,0.13);
    color: #92400e;
}
.btn-new-delivery {
    align-items: center;
    background: #001A93;
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    font-size: 13px;
    font-weight: 600;
    gap: 6px;
    padding: 10px 18px;
    text-decoration: none;
}
.btn-new-delivery:hover {
    background: #001270;
    color: #fff;
}
.rc-button-row {
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(0, 1fr) auto;
}
.btn-reset-rate {
    align-items: center;
    background: #fff;
    border: 1.5px solid #dbe2ee;
    border-radius: 10px;
    color: #001A93;
    cursor: pointer;
    display: inline-flex;
    font-size: 13px;
    font-weight: 600;
    gap: 6px;
    min-height: 44px;
    padding: 10px 18px;
    text-decoration: none;
}
.btn-reset-rate:hover,
.btn-reset-rate:focus {
    background: #eef3ff;
    border-color: #001A93;
    color: #001A93;
}
.rc-results-header {
    align-items: center;
    border-top: 1.5px solid #e4e7f0;
    color: #6b7280;
    display: flex;
    flex-wrap: wrap;
    font-size: 12px;
    gap: 6px;
    justify-content: space-between;
    margin-top: 18px;
    padding: 14px 0 10px;
}
.rc-zone-badge {
    background: rgba(0,26,147,.08);
    border-radius: 20px;
    color: #001A93;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
}
.rc-service-row {
    align-items: center;
    background: #f0f2f9;
    border: 1.5px solid transparent;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 12px 16px;
    transition: border-color .15s, background .15s;
}
.rc-service-row:hover {
    background: rgba(0,26,147,.03);
    border-color: #001A93;
}
.rc-service-left {
    align-items: center;
    display: flex;
    gap: 12px;
    min-width: 0;
}
.rc-service-icon {
    color: #001A93;
    flex-shrink: 0;
    font-size: 20px;
    text-align: center;
    width: 28px;
}
.rc-service-name {
    color: #1a1a2e;
    font-size: 13px;
    font-weight: 600;
}
.rc-service-tat {
    color: #6b7280;
    font-size: 11px;
    margin-top: 2px;
}
.rc-service-price {
    color: #001A93;
    font-family: 'Montserrat', sans-serif;
    font-size: 18px;
    font-weight: 700;
    white-space: nowrap;
}
.rc-disclaimer {
    color: #6b7280;
    font-size: 11px;
    margin-top: 10px;
    text-align: center;
}
@media (max-width: 575.98px) {
    .public-tool-band {
        padding: 28px 0 44px;
    }
    .public-tool-heading {
        align-items: flex-start;
    }
    .public-tool-heading h1 {
        font-size: 25px;
    }
    .public-tool-actions {
        display: block;
        margin-top: -4px;
    }
    .public-tool-action {
        justify-content: center;
        width: 100%;
    }
    .rate-card {
        padding: 20px;
    }
    .rc-button-row {
        grid-template-columns: 1fr;
    }
    .btn-reset-rate,
    .btn-new-delivery {
        width: 100%;
    }
    .rc-service-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
(function () {
    'use strict';
    const RC_URL = '<?= rtrim(SITE_URL, '/') ?>';
    let rcUnit = 'kg';
    let rcPackingCharge = 0;

    document.getElementById('rc_packing_wrap')?.addEventListener('click', e => {
        e.preventDefault();
        const chk = document.getElementById('rc_packing_material');
        if (!chk) return;
        chk.checked = !chk.checked;
        document.getElementById('rc_packing_wrap')?.classList.toggle('checked', chk.checked);
        document.getElementById('rc_packing_charge_row').style.display = 'block';
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
        rcPackingCharge = Math.min(9999, Math.max(0, parseFloat(e.target.value || 0) || 0));
        if ((parseFloat(e.target.value || 0) || 0) !== rcPackingCharge) e.target.value = rcPackingCharge ? String(rcPackingCharge) : '';
        const hint = document.getElementById('rc_packing_charge_hint');
        if (hint) hint.textContent = rcPackingCharge > 0 ? `(Rs. ${rcPackingCharge.toLocaleString('en-IN')})` : '(optional)';
        const chk = document.getElementById('rc_packing_material');
        if (chk) {
            chk.checked = rcPackingCharge > 0;
            document.getElementById('rc_packing_wrap')?.classList.toggle('checked', chk.checked);
        }
    });

    window.rcSetUnit = function (u) {
        rcUnit = u;
        document.getElementById('rc_unit_kg').classList.toggle('active', u === 'kg');
        document.getElementById('rc_unit_gm').classList.toggle('active', u === 'gm');
        rcResetResults();
    };

    window.rcLookup = function (type) {
        const inp = document.getElementById(`rc_${type}_pincode`);
        const res = document.getElementById(`rc_${type}_result`);
        const pin = inp ? inp.value.trim() : '';
        if (!pin || pin.length < 6) {
            if (res) {
                res.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Please enter 6 digit pincode';
                res.classList.add('show');
            }
            return;
        }

        fetch(`${RC_URL}/api/pincode.php?pincode=${encodeURIComponent(pin)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const info = data.data;
                    if (res) {
                        res.innerHTML = `<i class="bi bi-geo-alt-fill"></i> <strong>${esc(info.city)}</strong>, ${esc(info.state)}`;
                        res.classList.add('show');
                    }
                } else if (res) {
                    res.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Pincode not serviceable';
                    res.classList.add('show');
                }
            })
            .catch(() => {});
    };

    window.rcResetResults = function () {
        const r = document.getElementById('rc_results');
        if (r) r.innerHTML = '';
        const e = document.getElementById('rc_error');
        if (e) e.style.display = 'none';
    };

    window.rcResetCalculator = function () {
        ['rc_pickup_pincode', 'rc_delivery_pincode', 'rc_weight', 'rc_packing_charge', 'rc_tempo_charge'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        ['rc_pickup_result', 'rc_delivery_result'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.innerHTML = '';
                el.classList.remove('show');
            }
        });

        const packing = document.getElementById('rc_packing_material');
        if (packing) packing.checked = false;
        document.getElementById('rc_packing_wrap')?.classList.remove('checked');
        const packHint = document.getElementById('rc_packing_charge_hint');
        if (packHint) packHint.textContent = '(optional)';
        rcPackingCharge = 0;
        rcSetUnit('kg');

        const btn = document.getElementById('rc_calc_btn');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-calculator me-2"></i> Calculate Rates';
            btn.disabled = false;
        }

        rcResetResults();
        document.getElementById('rc_pickup_pincode')?.focus();
    };

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
            if (errEl) { errEl.textContent = 'Please enter pickup 6 digit pincode.'; errEl.style.display = 'block'; } return;
        }
        if (!delivery || delivery.length < 6) {
            if (errEl) { errEl.textContent = 'Please enter delivery 6 digit pincode.'; errEl.style.display = 'block'; } return;
        }
        if (weight <= 0) {
            if (errEl) { errEl.textContent = 'Enter a valid weight.'; errEl.style.display = 'block'; } return;
        }
        if (weight > 60) {
            if (errEl) { errEl.textContent = 'Maximum allowable weight is 60 kg'; errEl.style.display = 'block'; } return;
        }

        if (btn) { btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Calculating...'; btn.disabled = true; }
        if (results) results.innerHTML = '';

        fetch(`${RC_URL}/api/pricing.php?weight=${weight}&pickup=${encodeURIComponent(pickup)}&delivery=${encodeURIComponent(delivery)}`)
            .then(r => r.json())
            .then(data => {
                if (btn) { btn.innerHTML = '<i class="bi bi-calculator me-2"></i> Calculate Rates'; btn.disabled = false; }
                if (data.success && data.services && data.services.length > 0) {
                    rcRenderResults(data.services, data.zone, weight);
                } else if (results) {
                    results.innerHTML = '<div class="cust-alert cust-alert-warning mt-3">No pricing available for this route.</div>';
                }
            })
            .catch(() => {
                if (btn) { btn.innerHTML = '<i class="bi bi-calculator me-2"></i> Calculate Rates'; btn.disabled = false; }
                if (errEl) { errEl.textContent = 'Network error. Please try again.'; errEl.style.display = 'block'; }
            });
    };

    function rcRenderResults(services, zone, weight) {
        const results = document.getElementById('rc_results');
        if (!results) return;

        const zoneLabels = {
            within_city: 'Within City', within_state: 'Within State',
            metro: 'Metro', rest_of_india: 'Rest of India'
        };
        const svcMap = {
            standard:  { icon: 'bi-truck', label: 'Standard Express' },
            premium:   { icon: 'bi-lightning-fill', label: 'Premium Express' },
            air_cargo: { icon: 'bi-airplane', label: 'Air Cargo' },
            surface:   { icon: 'bi-boxes', label: 'Surface Cargo' },
        };

        const zoneTxt = zone ? (zoneLabels[zone] || zone) : '';
        const includePacking = !!document.getElementById('rc_packing_material')?.checked;
        rcPackingCharge = Math.min(9999, Math.max(0, parseFloat(document.getElementById('rc_packing_charge')?.value || 0) || 0));
        const rcTempoCharge = Math.min(9999, Math.max(0, parseFloat(document.getElementById('rc_tempo_charge')?.value || 0) || 0));
        if (includePacking && rcPackingCharge <= 0) {
            const errEl = document.getElementById('rc_error');
            if (errEl) { errEl.textContent = 'Enter packing material charge.'; errEl.style.display = 'block'; }
            return;
        }
        const packingCharge = includePacking ? rcPackingCharge : 0;
        const rows = services.map(svc => {
            const m = svcMap[svc.type] || { icon: 'bi-box', label: svc.type };
            const basePrice = parseFloat(svc.price) || 0;
            const totalPrice = basePrice + packingCharge + rcTempoCharge;
            const chargeableWeight = parseFloat(svc.chargeable_weight || weight) || weight;
            return `<div class="rc-service-row">
                <div class="rc-service-left">
                    <i class="${m.icon} rc-service-icon"></i>
                    <div>
                        <div class="rc-service-name">${esc(m.label)}</div>
                        <div class="rc-service-tat"><i class="bi bi-clock me-1"></i>${esc(svc.tat_label)} - Est. ${esc(svc.eta)} - Chargeable ${rcFormatWeight(chargeableWeight)}</div>
                    </div>
                </div>
                <div class="rc-service-price">Rs. ${totalPrice.toLocaleString('en-IN')}</div>
            </div>`;
        }).join('');

        results.innerHTML = `
            <div class="rc-results-header">
                <span><i class="bi bi-weight me-1"></i>${weight.toFixed(3)} kg</span>
                ${zoneTxt ? `<span class="rc-zone-badge"><i class="bi bi-geo-alt me-1"></i>${esc(zoneTxt)}</span>` : ''}
            </div>
            ${rows}
            ${includePacking ? `<p class="rc-disclaimer">Packing Material included: Rs. ${packingCharge.toLocaleString('en-IN')} per shipment.</p>` : ''}
            ${rcTempoCharge > 0 ? `<p class="rc-disclaimer">Tempo Charge included: Rs. ${rcTempoCharge.toLocaleString('en-IN')} per shipment.</p>` : ''}
            <p class="rc-disclaimer">* Estimates only. Final charges may vary based on actual weight and dimensions.</p>`;
    }

    ['rc_pickup_pincode', 'rc_delivery_pincode'].forEach(id => {
        const el = document.getElementById(id);
        el?.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '').slice(0, 6);
            rcResetResults();
            if (el.value.length === 6) rcLookup(id.includes('pickup') ? 'pickup' : 'delivery');
        });
        el?.addEventListener('paste', e => {
            e.preventDefault();
            el.value = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            rcResetResults();
            if (el.value.length === 6) rcLookup(id.includes('pickup') ? 'pickup' : 'delivery');
        });
        el?.addEventListener('keydown', e => {
            if (e.key === 'Enter') rcLookup(id.includes('pickup') ? 'pickup' : 'delivery');
        });
    });

    document.getElementById('rc_weight')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') rcCalculate();
    });

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }
    function rcFormatWeight(kg) {
        const value = parseFloat(kg) || 0;
        if (value > 0 && value < 1) return Math.round(value * 1000) + ' g';
        if (Number.isInteger(value)) return value + ' kg';
        return value.toFixed(3).replace(/\.?0+$/, '') + ' kg';
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
