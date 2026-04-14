/* pending.js — Auto-refresh and status check */
'use strict';

(function () {
    let countdown = 30;
    const refreshBtn = document.getElementById('refreshBtn');

    // Only run if on pending (non-rejected) state
    if (!refreshBtn) return;

    // Update button text with countdown
    function updateCountdown() {
        if (countdown > 0) {
            refreshBtn.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i> Check Status (${countdown}s)`;
            countdown--;
        } else {
            checkStatus();
        }
    }

    const timer = setInterval(updateCountdown, 1000);

    window.checkStatus = function () {
        clearInterval(timer);
        countdown = 0;
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking…';

        fetch('../auth/status.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'approved') {
                    window.location.href = 'dashboard.php';
                } else if (data.status === 'rejected') {
                    window.location.reload();
                } else {
                    refreshBtn.disabled = false;
                    countdown = 30;
                    const t = setInterval(() => {
                        if (countdown > 0) {
                            refreshBtn.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i> Check Status (${countdown}s)`;
                            countdown--;
                        } else {
                            clearInterval(t);
                            checkStatus();
                        }
                    }, 1000);
                }
            })
            .catch(() => {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Check Status';
            });
    };
})();
