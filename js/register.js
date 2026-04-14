/* register.js — Registration page JS for Careygo */
'use strict';

(function () {
    const form           = document.getElementById('registerForm');
    const fullName       = document.getElementById('full_name');
    const phoneInput     = document.getElementById('phone');
    const emailInput     = document.getElementById('email');
    const passInput      = document.getElementById('password');
    const confirmPass    = document.getElementById('confirm_password');
    const submitBtn      = document.getElementById('submitBtn');
    const submitText     = document.getElementById('submitText');
    const spinner        = document.getElementById('submitSpinner');
    const alertBox       = document.getElementById('alert-box');
    const togglePass     = document.getElementById('togglePassword');
    const eyeIcon        = document.getElementById('eyeIcon');
    const strengthFill   = document.getElementById('strengthFill');
    const strengthText   = document.getElementById('strengthText');

    // Toggle password visibility
    togglePass.addEventListener('click', () => {
        const isHidden = passInput.type === 'password';
        passInput.type = isHidden ? 'text' : 'password';
        eyeIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // Password strength meter
    passInput.addEventListener('input', () => {
        const val = passInput.value;
        let score = 0;
        if (val.length >= 8)               score++;
        if (/[A-Z]/.test(val))             score++;
        if (/[0-9]/.test(val))             score++;
        if (/[^A-Za-z0-9]/.test(val))      score++;

        strengthFill.className = 'strength-fill';
        if (val.length === 0) {
            strengthText.textContent = '';
        } else if (score <= 1) {
            strengthFill.classList.add('strength-weak');
            strengthText.textContent = 'Weak';
            strengthText.style.color = '#ef4444';
        } else if (score <= 2) {
            strengthFill.classList.add('strength-fair');
            strengthText.textContent = 'Fair';
            strengthText.style.color = '#f59e0b';
        } else {
            strengthFill.classList.add('strength-strong');
            strengthText.textContent = 'Strong';
            strengthText.style.color = '#22c55e';
        }
    });

    // Show alert
    function showAlert(message, type = 'danger') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() { alertBox.classList.add('d-none'); }

    // Field helpers
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

    // Validate all fields
    function validate() {
        let valid = true;

        [fullName, phoneInput, emailInput, passInput, confirmPass].forEach(el => {
            clearError(el, el.id + '-error');
        });

        if (!fullName.value.trim() || fullName.value.trim().length < 2) {
            setError(fullName, 'full_name-error', 'Full name must be at least 2 characters.'); valid = false;
        }

        if (!phoneInput.value.trim()) {
            setError(phoneInput, 'phone-error', 'Phone number is required.'); valid = false;
        } else if (!/^[\d\s\+\-\(\)]{7,20}$/.test(phoneInput.value.trim())) {
            setError(phoneInput, 'phone-error', 'Enter a valid phone number.'); valid = false;
        }

        if (!emailInput.value.trim()) {
            setError(emailInput, 'email-error', 'Email is required.'); valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
            setError(emailInput, 'email-error', 'Enter a valid email address.'); valid = false;
        }

        if (!passInput.value || passInput.value.length < 8) {
            setError(passInput, 'password-error', 'Password must be at least 8 characters.'); valid = false;
        }

        if (passInput.value !== confirmPass.value) {
            setError(confirmPass, 'confirm_password-error', 'Passwords do not match.'); valid = false;
        }

        return valid;
    }

    // Submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAlert();
        if (!validate()) return;

        submitBtn.disabled = true;
        submitText.textContent = 'Creating account…';
        spinner.classList.remove('d-none');

        try {
            const resp = await fetch('auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    full_name:        fullName.value.trim(),
                    phone:            phoneInput.value.trim(),
                    company_name:     document.getElementById('company_name').value.trim(),
                    email:            emailInput.value.trim().toLowerCase(),
                    password:         passInput.value,
                    confirm_password: confirmPass.value,
                }),
            });

            const data = await resp.json();

            if (resp.ok && data.success) {
                showAlert(data.message || 'Account created! Redirecting…', 'success');
                setTimeout(() => { window.location.href = data.redirect; }, 1200);
            } else {
                showAlert(data.message || 'Registration failed. Please try again.');
                submitBtn.disabled = false;
                submitText.textContent = 'Create Account';
                spinner.classList.add('d-none');
            }
        } catch {
            showAlert('Network error. Please try again.');
            submitBtn.disabled = false;
            submitText.textContent = 'Create Account';
            spinner.classList.add('d-none');
        }
    });

    // Clear errors on input
    [fullName, phoneInput, emailInput, passInput, confirmPass].forEach(el => {
        el.addEventListener('input', () => clearError(el, el.id + '-error'));
    });
})();
