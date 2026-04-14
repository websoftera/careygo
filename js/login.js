/* login.js — Login page JS for Careygo */
'use strict';

(function () {
    const form        = document.getElementById('loginForm');
    const emailInput  = document.getElementById('email');
    const passInput   = document.getElementById('password');
    const submitBtn   = document.getElementById('submitBtn');
    const submitText  = document.getElementById('submitText');
    const spinner     = document.getElementById('submitSpinner');
    const alertBox    = document.getElementById('alert-box');
    const togglePass  = document.getElementById('togglePassword');
    const eyeIcon     = document.getElementById('eyeIcon');

    // Toggle password visibility
    togglePass.addEventListener('click', () => {
        const isHidden = passInput.type === 'password';
        passInput.type = isHidden ? 'text' : 'password';
        eyeIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // Show alert
    function showAlert(message, type = 'danger') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.classList.add('d-none');
    }

    // Field error helper
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

    // Validate
    function validate() {
        let valid = true;
        clearError(emailInput, 'email-error');
        clearError(passInput, 'password-error');

        if (!emailInput.value.trim()) {
            setError(emailInput, 'email-error', 'Email is required.'); valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
            setError(emailInput, 'email-error', 'Enter a valid email address.'); valid = false;
        }

        if (!passInput.value) {
            setError(passInput, 'password-error', 'Password is required.'); valid = false;
        }

        return valid;
    }

    // Submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAlert();
        if (!validate()) return;

        submitBtn.disabled = true;
        submitText.textContent = 'Signing in…';
        spinner.classList.remove('d-none');

        try {
            const resp = await fetch('auth/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email:    emailInput.value.trim().toLowerCase(),
                    password: passInput.value,
                }),
            });

            const data = await resp.json();

            if (resp.ok && data.success) {
                showAlert('Login successful! Redirecting…', 'success');
                setTimeout(() => { window.location.href = data.redirect; }, 600);
            } else {
                showAlert(data.message || 'Invalid credentials. Please try again.');
                submitBtn.disabled = false;
                submitText.textContent = 'Sign In';
                spinner.classList.add('d-none');
            }
        } catch {
            showAlert('Network error. Please try again.');
            submitBtn.disabled = false;
            submitText.textContent = 'Sign In';
            spinner.classList.add('d-none');
        }
    });

    // Clear errors on input
    emailInput.addEventListener('input', () => clearError(emailInput, 'email-error'));
    passInput.addEventListener('input',  () => clearError(passInput,  'password-error'));
})();
