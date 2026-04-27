    </main>
</div>

<?php include __DIR__ . '/rate-calc-modal.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar Toggle Logic
const custSidebar = document.getElementById('custSidebar');
const custOverlay = document.getElementById('custOverlay');
function setCustomerSidebarCollapsed(collapsed) {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    try { localStorage.setItem('customerSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
}
function openCustomerSidebar() {
    setCustomerSidebarCollapsed(false);
    custSidebar?.classList.add('open');
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        custOverlay?.classList.add('show');
    }
}
function closeCustomerSidebar() {
    setCustomerSidebarCollapsed(true);
    custSidebar?.classList.remove('open');
    custOverlay?.classList.remove('show');
}
try {
    if (localStorage.getItem('customerSidebarCollapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
    }
} catch (e) {}
document.getElementById('custToggle')?.addEventListener('click', openCustomerSidebar);
document.getElementById('custSidebarClose')?.addEventListener('click', closeCustomerSidebar);
custOverlay?.addEventListener('click', closeCustomerSidebar);
document.querySelectorAll('.cust-nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (link.getAttribute('href') && link.getAttribute('href') !== '#') closeCustomerSidebar();
    });
});

function openRateCalc() { 
    const modalEl = document.getElementById('rateCalcModal');
    if (modalEl) {
        new bootstrap.Modal(modalEl).show();
    }
}

// Global search helper (if needed by pages)
window.filterTable = function(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
};
</script>
</body>
</html>
