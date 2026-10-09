/* reset-password.js - complete password reset */
'use strict';

(function () {
    const form = document.getElementById('resetPasswordForm');
    const tokenInput = document.getElementById('token');
    const passInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const spinner = document.getElementById('submitSpinner');
    const alertBox = document.getElementById('alert-box');

    if (!form || !tokenInput || !passInput || !confirmInput || !submitBtn || !submitText || !spinner || !alertBox) return;

    function showAlert(message, type = 'danger') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.classList.add('d-none');
    }

    function setError(inputEl, errorId, message) {
        inputEl.classList.add('is-invalid');
        const el = document.getElementById(errorId);
        if (el) { el.textContent = message; el.classList.add('visible'); }
    }

    function clearError(inputEl, errorId) {
        inputEl.classList.remove('is-invalid');
        const el = document.getElementById(errorId);
        if (el) { el.textContent = ''; el.classList.remove('visible'); }
    }

    function validate() {
        let valid = true;
        clearError(passInput, 'password-error');
        clearError(confirmInput, 'confirm-password-error');

        if (!tokenInput.value.trim()) {
            showAlert('Reset link is missing or invalid.');
            valid = false;
        }
        if (passInput.value.length < 8) {
            setError(passInput, 'password-error', 'Password must be at least 8 characters.');
            valid = false;
        }
        if (confirmInput.value !== passInput.value) {
            setError(confirmInput, 'confirm-password-error', 'Passwords do not match.');
            valid = false;
        }
        return valid;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAlert();
        if (!validate()) return;

        submitBtn.disabled = true;
        submitText.textContent = 'Resetting...';
        spinner.classList.remove('d-none');

        try {
            const resp = await fetch('auth/reset-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: tokenInput.value.trim(),
                    password: passInput.value,
                    confirm_password: confirmInput.value,
                }),
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                showAlert(data.message || 'Password reset successfully. Redirecting to sign in...', 'success');
                form.reset();
                setTimeout(() => { window.location.href = 'login.php'; }, 1000);
            } else {
                showAlert(data.message || 'Unable to reset password. Please request a new reset link.');
            }
        } catch {
            showAlert('Network error. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitText.textContent = 'Reset Password';
            spinner.classList.add('d-none');
        }
    });

    passInput.addEventListener('input', () => clearError(passInput, 'password-error'));
    confirmInput.addEventListener('input', () => clearError(confirmInput, 'confirm-password-error'));
})();
