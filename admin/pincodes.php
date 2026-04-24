<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Pincodes / TAT';
$activePage = 'pincodes';

// Total count for header (quick query, no search)
try {
    $total = (int) $pdo->query("SELECT COUNT(*) FROM pincode_tat")->fetchColumn();
} catch (Exception $e) { $total = 0; }

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Pincodes / TAT Details</h4>
        <p id="totalCountLabel"><?= number_format($total) ?> pincodes in database</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= SITE_URL ?? '' ?>/admin/download_sample_csv.php" class="btn-outline-admin" style="text-decoration:none;">
            <i class="bi bi-download me-1"></i> Sample CSV
        </a>
        <button class="btn-outline-admin" onclick="document.getElementById('csvUpload').click()">
            <i class="bi bi-upload me-1"></i> Import CSV
        </button>
        <input type="file" id="csvUpload" accept=".csv" style="display:none" onchange="importCsv(this)">
        <button class="btn-outline-admin" id="btnGroupByState" onclick="toggleGroupByState()" title="Toggle state-wise grouping">
            <i class="bi bi-collection me-1"></i> Group by State
        </button>
        <button id="btnBulkDelete" class="btn-action danger" style="display:none;padding:5px 12px;height:auto;color:white;background:#dc3545;" onclick="confirmBulkDelete()">
            <i class="bi bi-trash me-1"></i> Delete Selected
        </button>
        <button class="btn-primary-admin" onclick="openAddPincode()">
            <i class="bi bi-plus-lg"></i> Add Pincode
        </button>
    </div>
</div>

<!-- State filter bar (hidden by default, loaded via AJAX) -->
<div id="stateFilterBar" style="display:none;margin-bottom:16px;">
    <div class="admin-card" style="padding:14px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <span style="font-size:13px;font-weight:600;color:var(--text);">
                <i class="bi bi-map me-1 text-primary"></i> Filter by State
            </span>
            <button onclick="clearStateFilter()" id="clearStateBtn"
                    style="display:none;background:none;border:none;font-size:12px;color:#dc2626;cursor:pointer;font-weight:600;">
                <i class="bi bi-x-circle me-1"></i> Clear Filter
            </button>
        </div>
        <div id="stateButtons" style="display:flex;flex-wrap:wrap;gap:8px;">
            <span style="font-size:12px;color:#6b7280;"><span class="spinner-border spinner-border-sm me-1"></span>Loading states…</span>
        </div>
    </div>
</div>

<div class="alert d-flex gap-2 mb-3" style="font-size:12px;border-radius:12px;border:none;background:rgba(245,158,11,0.08);color:#92400e;">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
        <strong>Import CSV:</strong> columns: <code>pincode, city, state, zone, tat_standard, tat_premium, tat_air, tat_surface, serviceable</code>. Existing pincodes will be updated.<br>
        <strong>To edit a pincode:</strong> Type in the search box below to find it — then click the <i class="bi bi-pencil"></i> Edit button in its row.
    </div>
</div>

<div class="admin-card">
    <!-- Card header: title + search box -->
    <div class="admin-card-header" style="flex-wrap:wrap;gap:12px;">
        <h6 class="admin-card-title"><i class="bi bi-geo-alt me-2"></i>Pincode List</h6>
        <div style="display:flex;align-items:center;gap:10px;flex:1;justify-content:flex-end;">
            <!-- Result info -->
            <span id="resultInfo" style="font-size:12px;color:#6b7280;white-space:nowrap;"></span>
            <!-- Search box -->
            <div class="pincode-search-wrap" style="position:relative;min-width:280px;max-width:380px;">
                <i class="bi bi-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;pointer-events:none;"></i>
                <input type="text"
                       id="pincodeSearchBox"
                       placeholder="Search pincode, city, state, zone… (min 3 chars)"
                       style="width:100%;padding:8px 36px 8px 34px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-family:'Poppins',sans-serif;outline:none;transition:border-color .2s;background:#fff;"
                       autocomplete="off">
                <button id="searchClearBtn"
                        onclick="clearSearch()"
                        style="display:none;position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;font-size:16px;line-height:1;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Search hint -->
    <div id="searchHint" style="display:none;padding:6px 16px;font-size:12px;color:#6b7280;background:#fafafa;border-bottom:1px solid var(--border);">
        <i class="bi bi-info-circle me-1"></i> Type at least 3 characters to search
    </div>

    <!-- Table -->
    <div class="admin-table-wrap" style="overflow-x:auto;">
        <table class="admin-table" id="pincodesTable">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;">
                        <input type="checkbox" id="selectAllPincodes" onchange="toggleSelectAll(this)" title="Select all on this page">
                    </th>
                    <th>Pincode</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zone</th>
                    <th style="text-align:center;">Std TAT</th>
                    <th style="text-align:center;">Prm TAT</th>
                    <th style="text-align:center;">Air TAT</th>
                    <th style="text-align:center;">Srf TAT</th>
                    <th style="text-align:center;">Serviceable</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="pincodesBody">
                <tr><td colspan="11">
                    <div style="padding:30px;text-align:center;color:#6b7280;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading pincodes…
                    </div>
                </td></tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div id="paginationWrap" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:14px 16px;border-top:1px solid var(--border);">
        <div id="pageInfo" style="font-size:13px;color:#6b7280;"></div>
        <div id="pageButtons" style="display:flex;gap:4px;flex-wrap:wrap;"></div>
    </div>
</div>

<!-- Add/Edit Pincode Modal -->
<div class="modal fade admin-modal" id="pincodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="pincodeModalTitle"><i class="bi bi-geo-alt me-2"></i>Add Pincode</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pc_id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="admin-form-label">Pincode *</label><input type="text" class="admin-form-control" id="pc_pincode" maxlength="10" placeholder="411001"></div>
                    <div class="col-md-4"><label class="admin-form-label">City *</label><input type="text" class="admin-form-control" id="pc_city" placeholder="Pune"></div>
                    <div class="col-md-4"><label class="admin-form-label">State *</label><input type="text" class="admin-form-control" id="pc_state" placeholder="Maharashtra"></div>
                    <div class="col-md-4"><label class="admin-form-label">Zone</label><input type="text" class="admin-form-control" id="pc_zone" placeholder="Metro / Tier1 / Tier2"></div>
                    <div class="col-md-2"><label class="admin-form-label">Std TAT (days)</label><input type="number" class="admin-form-control" id="pc_tat_standard" min="1" value="3"></div>
                    <div class="col-md-2"><label class="admin-form-label">Premium TAT</label><input type="number" class="admin-form-control" id="pc_tat_premium" min="1" value="1"></div>
                    <div class="col-md-2"><label class="admin-form-label">Air TAT</label><input type="number" class="admin-form-control" id="pc_tat_air" min="1" value="2"></div>
                    <div class="col-md-2"><label class="admin-form-label">Surface TAT</label><input type="number" class="admin-form-control" id="pc_tat_surface" min="1" value="5"></div>
                    <div class="col-md-4">
                        <label class="admin-form-label">Serviceable</label>
                        <select class="admin-select" id="pc_serviceable">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-admin" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-primary-admin" onclick="savePincode()"><i class="bi bi-check-lg me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ── State filter buttons ── */
.state-filter-btn {
    background: #f3f4f6;
    color: #374151;
    border: 1.5px solid #e5e7eb;
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    font-family: 'Poppins', sans-serif;
    white-space: nowrap;
}
.state-filter-btn:hover { border-color: var(--primary); color: var(--primary); background: #eef2ff; }
.state-filter-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }

/* ── Pagination buttons ── */
.pg-btn {
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    color: var(--text);
    cursor: pointer;
    transition: all .15s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
.pg-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); background: #eef2ff; }
.pg-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); font-weight: 700; }
.pg-btn:disabled { opacity: .4; cursor: not-allowed; }
.pg-ellipsis { padding: 0 6px; display:inline-flex;align-items:center;color:#9ca3af;font-size:14px; }

/* ── Search box focus ── */
#pincodeSearchBox:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,26,147,.08); }

/* ── Highlight search match ── */
mark.search-hl { background: #fef08a; padding: 0 2px; border-radius: 2px; font-weight: 600; }
</style>

<script>
/* ══════════════════════════════════════════════════════
   Pincode Admin — AJAX pagination + search
   ══════════════════════════════════════════════════════ */
const API_URL  = '<?= SITE_URL ?>/api/admin/pincodes.php';
const PER_PAGE = 40;

let _currentPage  = 1;
let _currentQ     = '';
let _currentState = '';
let _totalPages   = 1;
let _totalRows    = 0;
let _searchTimer  = null;
let _loading      = false;
let _groupByState = false;
let _selectedIds  = new Set(); // track checked IDs across pages

/* ── Init ── */
loadPage(1);

/* ── Search input ── */
const searchBox = document.getElementById('pincodeSearchBox');
searchBox.addEventListener('input', function () {
    const val = this.value.trim();
    const clearBtn = document.getElementById('searchClearBtn');
    const hint     = document.getElementById('searchHint');

    clearBtn.style.display = val ? 'block' : 'none';

    if (val.length === 0) {
        hint.style.display = 'none';
        clearSearch();
        return;
    }
    if (val.length < 3) {
        hint.style.display = 'block';
        return;
    }

    hint.style.display = 'none';
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(() => {
        _currentQ = val;
        loadPage(1);
    }, 350); // debounce 350 ms
});

searchBox.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') clearSearch();
});

function clearSearch() {
    searchBox.value = '';
    document.getElementById('searchClearBtn').style.display = 'none';
    document.getElementById('searchHint').style.display = 'none';
    _currentQ = '';
    loadPage(1);
}

/* ── Main data loader ── */
function loadPage(page) {
    if (_loading) return;
    _loading = true;
    _currentPage = page;

    const tbody = document.getElementById('pincodesBody');
    tbody.innerHTML = `<tr><td colspan="11">
        <div style="padding:24px;text-align:center;color:#6b7280;">
            <span class="spinner-border spinner-border-sm me-2"></span> Loading…
        </div></td></tr>`;

    const params = new URLSearchParams({
        page:  page,
        q:     _currentQ,
        state: _currentState,
    });

    fetch(`${API_URL}?${params}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
            _loading = false;
            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="11"><div class="empty-state"><p>Error loading data: ${escHtml(res.message)}</p></div></td></tr>`;
                return;
            }
            _totalRows  = res.total;
            _totalPages = res.pages;
            renderRows(res.data, res.q);
            renderPagination(res.page, res.pages, res.total);
        })
        .catch(err => {
            _loading = false;
            tbody.innerHTML = `<tr><td colspan="11"><div class="empty-state"><p>Network error. Please try again.</p></div></td></tr>`;
        });
}

/* ── Render table rows ── */
function renderRows(rows, q) {
    const tbody = document.getElementById('pincodesBody');

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="11">
            <div class="empty-state">
                <i class="bi bi-geo-alt" style="font-size:32px;color:#d1d5db;"></i>
                <p style="margin-top:8px;color:#6b7280;">
                    ${_currentQ || _currentState ? 'No pincodes match your search.' : 'No pincodes yet. Import a CSV or add manually.'}
                </p>
            </div></td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(p => {
        const pin   = hl(escHtml(p.pincode), q);
        const city  = hl(escHtml(p.city),    q);
        const state = hl(escHtml(p.state),   q);
        const zone  = hl(escHtml(p.zone || '—'), q);
        const svcBadge = p.serviceable == 1
            ? '<span class="badge-status badge-approved" style="font-size:10px;">Yes</span>'
            : '<span class="badge-status badge-rejected" style="font-size:10px;">No</span>';
        const checked = _selectedIds.has(String(p.id)) ? 'checked' : '';

        return `<tr id="pc_${p.id}" data-id="${p.id}">
            <td style="text-align:center;"><input type="checkbox" class="pc-checkbox" value="${p.id}" ${checked} onchange="onCheckboxChange(this)"></td>
            <td style="font-size:13px;font-weight:700;">${pin}</td>
            <td style="font-size:13px;">${city}</td>
            <td style="font-size:13px;">${state}</td>
            <td style="font-size:12px;">${zone}</td>
            <td style="text-align:center;font-size:13px;">${p.tat_standard} day${p.tat_standard > 1 ? 's' : ''}</td>
            <td style="text-align:center;font-size:13px;">${p.tat_premium} day${p.tat_premium > 1 ? 's' : ''}</td>
            <td style="text-align:center;font-size:13px;">${p.tat_air} day${p.tat_air > 1 ? 's' : ''}</td>
            <td style="text-align:center;font-size:13px;">${p.tat_surface} day${p.tat_surface > 1 ? 's' : ''}</td>
            <td style="text-align:center;">${svcBadge}</td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn-action" onclick='editPincode(${JSON.stringify(p)})' title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn-action danger" onclick="confirmAction('Delete this pincode?', ()=>deletePincode(${p.id}))" title="Delete"><i class="bi bi-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');

    // Sync "select all" checkbox
    const allChecked = rows.every(p => _selectedIds.has(String(p.id)));
    document.getElementById('selectAllPincodes').checked = allChecked && rows.length > 0;
    toggleDeleteBtn();
}

/* ── Highlight search term in cell text ── */
function hl(text, q) {
    if (!q || q.length < 3) return text;
    // Escape regex special chars
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="search-hl">$1</mark>');
}

/* ── Render pagination controls ── */
function renderPagination(page, pages, total) {
    const from = total === 0 ? 0 : (page - 1) * PER_PAGE + 1;
    const to   = Math.min(page * PER_PAGE, total);

    // Result info
    const info = document.getElementById('pageInfo');
    if (_currentQ || _currentState) {
        info.innerHTML = `Showing <strong>${from}–${to}</strong> of <strong>${total}</strong> results
            ${_currentQ ? `for "<strong>${escHtml(_currentQ)}</strong>"` : ''}
            ${_currentState ? `in <strong>${escHtml(_currentState)}</strong>` : ''}`;
    } else {
        info.innerHTML = total === 0 ? 'No pincodes found.' : `Showing <strong>${from}–${to}</strong> of <strong>${total.toLocaleString()}</strong> pincodes`;
    }

    // Page buttons
    const btnWrap = document.getElementById('pageButtons');
    if (pages <= 1) { btnWrap.innerHTML = ''; return; }

    let html = '';

    // Prev
    html += `<button class="pg-btn" onclick="loadPage(${page - 1})" ${page <= 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i>
             </button>`;

    // Page numbers with smart ellipsis
    const pageNums = getPageRange(page, pages);
    let prevWasEllipsis = false;
    pageNums.forEach(p => {
        if (p === '…') {
            if (!prevWasEllipsis) html += `<span class="pg-ellipsis">…</span>`;
            prevWasEllipsis = true;
        } else {
            prevWasEllipsis = false;
            html += `<button class="pg-btn ${p === page ? 'active' : ''}"
                             onclick="loadPage(${p})">${p}</button>`;
        }
    });

    // Next
    html += `<button class="pg-btn" onclick="loadPage(${page + 1})" ${page >= pages ? 'disabled' : ''}>
                <i class="bi bi-chevron-right"></i>
             </button>`;

    btnWrap.innerHTML = html;
    document.getElementById('paginationWrap').style.display = 'flex';
}

/* ── Smart page range (always show first, last, current ±2) ── */
function getPageRange(current, total) {
    const delta   = 2;
    const range   = [];
    const rangeWE = []; // with ellipsis

    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
        range.push(i);
    }

    if (current - delta > 2) range.unshift('…');
    if (current + delta < total - 1) range.push('…');

    range.unshift(1);
    if (total > 1) range.push(total);

    return range;
}

/* ── Checkbox handling (persists across pages) ── */
function onCheckboxChange(cb) {
    if (cb.checked) {
        _selectedIds.add(cb.value);
    } else {
        _selectedIds.delete(cb.value);
        document.getElementById('selectAllPincodes').checked = false;
    }
    toggleDeleteBtn();
}

function toggleSelectAll(master) {
    document.querySelectorAll('.pc-checkbox').forEach(cb => {
        cb.checked = master.checked;
        if (master.checked) _selectedIds.add(cb.value);
        else                 _selectedIds.delete(cb.value);
    });
    toggleDeleteBtn();
}

function toggleDeleteBtn() {
    document.getElementById('btnBulkDelete').style.display = _selectedIds.size > 0 ? 'inline-block' : 'none';
}

/* ── Bulk delete ── */
function confirmBulkDelete() {
    if (!_selectedIds.size) return;
    const ids = Array.from(_selectedIds);
    confirmAction(`Delete ${ids.length} selected pincode${ids.length > 1 ? 's' : ''}?`, () => {
        fetch(API_URL, {
            method: 'DELETE', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ids}), credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(`Deleted ${ids.length} pincode${ids.length > 1 ? 's' : ''}`, 'success');
                _selectedIds.clear();
                toggleDeleteBtn();
                loadPage(_currentPage > 1 && _totalRows - ids.length <= (_currentPage - 1) * PER_PAGE
                    ? _currentPage - 1 : _currentPage);
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        });
    });
}

/* ── State grouping ── */
function toggleGroupByState() {
    _groupByState = !_groupByState;
    const btn = document.getElementById('btnGroupByState');
    const bar = document.getElementById('stateFilterBar');

    if (_groupByState) {
        btn.style.background    = 'var(--primary)';
        btn.style.color         = '#fff';
        btn.style.borderColor   = 'var(--primary)';
        bar.style.display       = 'block';
        loadStateButtons();
    } else {
        btn.style.background  = '';
        btn.style.color       = '';
        btn.style.borderColor = '';
        bar.style.display     = 'none';
        clearStateFilter();
    }
}

function loadStateButtons() {
    fetch(`${API_URL}?action=states`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const container = document.getElementById('stateButtons');
            const total = res.states.reduce((s, x) => s + parseInt(x.cnt), 0);

            container.innerHTML =
                `<button class="state-filter-btn ${!_currentState ? 'active' : ''}"
                         onclick="applyStateFilter('', this)">
                     All States <span style="opacity:.7;">(${total.toLocaleString()})</span>
                 </button>` +
                res.states.map(s =>
                    `<button class="state-filter-btn ${_currentState === s.state ? 'active' : ''}"
                             onclick="applyStateFilter(${JSON.stringify(s.state)}, this)">
                         ${escHtml(s.state)}
                         <span style="opacity:.7;">(${parseInt(s.cnt).toLocaleString()})</span>
                     </button>`
                ).join('');
        });
}

function applyStateFilter(state, clickedBtn) {
    document.querySelectorAll('.state-filter-btn').forEach(b => b.classList.remove('active'));
    if (clickedBtn) clickedBtn.classList.add('active');
    _currentState = state;
    const clearBtn = document.getElementById('clearStateBtn');
    clearBtn.style.display = state ? 'inline-block' : 'none';
    loadPage(1);
}

function clearStateFilter() {
    _currentState = '';
    document.getElementById('clearStateBtn').style.display = 'none';
    document.querySelectorAll('.state-filter-btn').forEach(b => b.classList.remove('active'));
    const allBtn = document.querySelector('.state-filter-btn[onclick*="\'\'"]') ||
                   document.querySelector('.state-filter-btn');
    if (allBtn) allBtn.classList.add('active');
    loadPage(1);
}

/* ── Add pincode ── */
function openAddPincode() {
    document.getElementById('pc_id').value = '';
    document.getElementById('pincodeModalTitle').innerHTML = '<i class="bi bi-geo-alt me-2"></i>Add Pincode';
    ['pc_pincode','pc_city','pc_state','pc_zone'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('pc_tat_standard').value = 3;
    document.getElementById('pc_tat_premium').value  = 1;
    document.getElementById('pc_tat_air').value      = 2;
    document.getElementById('pc_tat_surface').value  = 5;
    document.getElementById('pc_serviceable').value  = '1';
    new bootstrap.Modal(document.getElementById('pincodeModal')).show();
}

/* ── Edit pincode ── */
function editPincode(p) {
    document.getElementById('pc_id').value           = p.id;
    document.getElementById('pincodeModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pincode';
    document.getElementById('pc_pincode').value      = p.pincode;
    document.getElementById('pc_city').value         = p.city;
    document.getElementById('pc_state').value        = p.state;
    document.getElementById('pc_zone').value         = p.zone || '';
    document.getElementById('pc_tat_standard').value = p.tat_standard;
    document.getElementById('pc_tat_premium').value  = p.tat_premium;
    document.getElementById('pc_tat_air').value      = p.tat_air;
    document.getElementById('pc_tat_surface').value  = p.tat_surface;
    document.getElementById('pc_serviceable').value  = p.serviceable;
    new bootstrap.Modal(document.getElementById('pincodeModal')).show();
}

/* ── Save pincode ── */
function savePincode() {
    const payload = {
        id:           document.getElementById('pc_id').value || null,
        pincode:      document.getElementById('pc_pincode').value.trim(),
        city:         document.getElementById('pc_city').value.trim(),
        state:        document.getElementById('pc_state').value.trim(),
        zone:         document.getElementById('pc_zone').value.trim(),
        tat_standard: parseInt(document.getElementById('pc_tat_standard').value) || 3,
        tat_premium:  parseInt(document.getElementById('pc_tat_premium').value)  || 1,
        tat_air:      parseInt(document.getElementById('pc_tat_air').value)      || 2,
        tat_surface:  parseInt(document.getElementById('pc_tat_surface').value)  || 5,
        serviceable:  parseInt(document.getElementById('pc_serviceable').value),
    };
    if (!payload.pincode || !payload.city || !payload.state) {
        showToast('Fill required fields', 'warning'); return;
    }
    fetch(API_URL, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Pincode saved', 'success');
            bootstrap.Modal.getInstance(document.getElementById('pincodeModal')).hide();
            loadPage(_currentPage); // reload current page (no full page reload)
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

/* ── Delete single pincode ── */
function deletePincode(id) {
    fetch(API_URL, {
        method: 'DELETE', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id}), credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Deleted', 'success');
            _selectedIds.delete(String(id));
            toggleDeleteBtn();
            // Go back a page if this was the only row
            const remaining = _totalRows - 1;
            const newPage = _currentPage > 1 && remaining <= (_currentPage - 1) * PER_PAGE
                ? _currentPage - 1 : _currentPage;
            loadPage(newPage);
        } else {
            showToast(data.message || 'Delete failed', 'error');
        }
    });
}

/* ── CSV Import ── */
function importCsv(input) {
    const file = input.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('csv', file);
    showToast('Importing CSV…', 'default', 60000);
    fetch(`${API_URL}?action=import`, {
        method: 'POST', body: formData, credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`Imported ${data.count} pincodes successfully`, 'success', 4000);
            loadPage(1); // reload table (no full page reload)
            // Update total count in header
            fetch(`${API_URL}?page=1`, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(r => {
                    if (r.success) document.getElementById('totalCountLabel').textContent =
                        r.total.toLocaleString() + ' pincodes in database';
                });
        } else {
            showToast(data.message || 'Import failed', 'error');
        }
    })
    .catch(() => showToast('Import failed', 'error'));
    input.value = '';
}

/* ── Utility ── */
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

<?php require_once 'includes/footer.php'; ?>
