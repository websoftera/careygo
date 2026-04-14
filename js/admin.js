/* ============================================================
   admin.js — Careygo Admin Panel JavaScript
   ============================================================ */
(function () {
    'use strict';

    /* ── Sidebar toggle ── */
    const sidebar   = document.getElementById('adminSidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn  = document.getElementById('sidebarClose');

    function openSidebar()  { sidebar && sidebar.classList.add('open');  overlay && overlay.classList.add('show'); }
    function closeSidebar() { sidebar && sidebar.classList.remove('open'); overlay && overlay.classList.remove('show'); }

    toggleBtn && toggleBtn.addEventListener('click', openSidebar);
    closeBtn  && closeBtn.addEventListener('click',  closeSidebar);
    overlay   && overlay.addEventListener('click',   closeSidebar);

    /* ── Toast notifications ── */
    window.showToast = function (msg, type = 'default', duration = 3500) {
        let wrap = document.getElementById('admin-toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'admin-toast-wrap';
            document.body.appendChild(wrap);
        }
        const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', default: 'bi-info-circle-fill' };
        const toast = document.createElement('div');
        toast.className = `admin-toast ${type}`;
        toast.innerHTML = `<i class="bi ${icons[type] || icons.default}"></i> <span>${msg}</span>`;
        wrap.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateY(8px)'; toast.style.transition = 'all 0.3s'; setTimeout(() => toast.remove(), 300); }, duration);
    };

    /* ── Confirm dialog ── */
    window.confirmAction = function (message, callback) {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            document.getElementById('confirmMessage').textContent = message;
            const btn = document.getElementById('confirmOkBtn');
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            newBtn.addEventListener('click', () => { bootstrap.Modal.getInstance(modal).hide(); callback(); });
            new bootstrap.Modal(modal).show();
        } else if (confirm(message)) {
            callback();
        }
    };

    /* ── Live search filter on tables ── */
    document.querySelectorAll('[data-search-table]').forEach(input => {
        const tableId = input.dataset.searchTable;
        const table   = document.getElementById(tableId);
        if (!table) return;
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    /* ── Status filter on tables ── */
    document.querySelectorAll('[data-filter-table]').forEach(select => {
        const tableId = select.dataset.filterTable;
        const col     = parseInt(select.dataset.filterCol || '0');
        const table   = document.getElementById(tableId);
        if (!table) return;
        select.addEventListener('change', () => {
            const val = select.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                const cell = row.cells[col];
                if (!cell) return;
                row.style.display = (!val || cell.textContent.trim().toLowerCase().includes(val)) ? '' : 'none';
            });
        });
    });

    /* ── Copy tracking number ── */
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(btn.dataset.copy).then(() => showToast('Copied to clipboard', 'success', 2000));
        });
    });

    /* ── Auto-close alerts ── */
    document.querySelectorAll('.alert-auto-close').forEach(el => {
        setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.4s'; setTimeout(() => el.remove(), 400); }, 4000);
    });

})();
