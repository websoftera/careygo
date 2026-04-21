    </main>
</div>

<?php include __DIR__ . '/rate-calc-modal.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar Toggle Logic
const custSidebar = document.getElementById('custSidebar');
const custOverlay = document.getElementById('custOverlay');
document.getElementById('custToggle')?.addEventListener('click', () => { 
    custSidebar.classList.add('open'); 
    custOverlay.classList.add('show'); 
});
document.getElementById('custSidebarClose')?.addEventListener('click', () => { 
    custSidebar.classList.remove('open'); 
    custOverlay.classList.remove('show'); 
});
custOverlay?.addEventListener('click', () => { 
    custSidebar.classList.remove('open'); 
    custOverlay.classList.remove('show'); 
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
