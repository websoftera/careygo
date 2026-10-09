/* forgot-password.js - password reset request */
'use strict';

(function () {
    const form = document.getElementById('forgotPasswordForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const spinner = document.getElementById('submitSpinner');
    const alertBox = document.getElementById('alert-box');

    if (!form || !emailInput || !submitBtn || !submitText || !spinner || !alertBox) return;

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
        clearError(emailInput, 'email-error');
        const email = emailInput.value.trim();
        if (!email) {
            setError(emailInput, 'email-error', 'Email is required.');
            return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setError(emailInput, 'email-error', 'Enter a valid email address.');
            return false;
        }
        return true;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAlert();
        if (!validate()) return;

        submitBtn.disabled = true;
        submitText.textContent = 'Sending...';
        spinner.classList.remove('d-none');

        try {
            const resp = await fetch('auth/forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: emailInput.value.trim().toLowerCase() }),
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                showAlert(data.message || 'If this email is registered, a reset link has been sent.', 'success');
                form.reset();
            } else {
                showAlert(data.message || 'Unable to send reset link. Please try again.');
            }
        } catch {
            showAlert('Network error. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitText.textContent = 'Send Reset Link';
            spinner.classList.add('d-none');
        }
    });

    emailInput.addEventListener('input', () => clearError(emailInput, 'email-error'));
})();
