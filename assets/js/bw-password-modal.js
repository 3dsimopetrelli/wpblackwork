/**
 * Password Gating Modal for My Account
 *
 * Shows a blocking modal when Supabase provider is active
 * and the user hasn't set a password yet.
 */
(function () {
    'use strict';

    // Exit early if config not available
    if (typeof window.bwPasswordModal === 'undefined') {
        return;
    }

    var config = window.bwPasswordModal;
    var modal = null;
    var form = null;
    var newPasswordInput = null;
    var confirmPasswordInput = null;
    var submitButton = null;
    var errorBox = null;
    var rules = {};

    /**
     * Initialize modal elements
     */
    function initElements() {
        modal = document.getElementById('bw-password-modal');
        if (!modal) {
            return false;
        }

        form = document.getElementById('bw-password-modal-form');
        newPasswordInput = document.getElementById('bw_modal_new_password');
        confirmPasswordInput = document.getElementById('bw_modal_confirm_password');
        submitButton = modal.querySelector('.bw-password-modal__submit');
        errorBox = modal.querySelector('.bw-password-modal__error');

        // Get rule elements
        var ruleElements = modal.querySelectorAll('[data-rule]');
        ruleElements.forEach(function (el) {
            rules[el.getAttribute('data-rule')] = el;
        });

        return true;
    }

    /**
     * Open the modal with animation
     */
    function openModal() {
        if (!modal) return;

        modal.style.display = 'flex';

        // Trigger animation after display is set
        setTimeout(function () {
            modal.classList.add('is-active');
            document.body.classList.add('bw-password-modal-open');
            document.body.classList.add('bw-modal-open');
        }, 10);

        // Focus first input
        if (newPasswordInput) {
            setTimeout(function () {
                newPasswordInput.focus();
            }, 400);
        }
    }

    /**
     * Close the modal with animation
     */
    function closeModal() {
        if (!modal) return;

        modal.classList.remove('is-active');

        setTimeout(function () {
            modal.style.display = 'none';
            document.body.classList.remove('bw-password-modal-open');
            document.body.classList.remove('bw-modal-open');
        }, 400);
    }

    /**
     * Show error message
     */
    function showError(message) {
        if (!errorBox) return;

        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    /**
     * Hide error message
     */
    function hideError() {
        if (!errorBox) return;

        errorBox.textContent = '';
        errorBox.hidden = true;
    }

    /**
     * Force re-login when Supabase session is missing.
     */
    function redirectToLogin() {
        var keys = [
            'bw_pending_email',
            'bw_handled_supabase_hash',
            'bw_handled_supabase_code',
            'bw_handled_token_login',
            'bw_handled_email_confirm',
            'bw_handled_session_check',
            'bw_otp_needs_password',
            'bw_otp_mode',
            'bw_supabase_access_token',
            'bw_supabase_refresh_token',
            'bw_pending_access_token',
            'bw_pending_refresh_token',
            'bw_auth_flow'
        ];

        if (window.sessionStorage) {
            try {
                keys.forEach(function (key) {
                    sessionStorage.removeItem(key);
                });
            } catch (error) {
                // ignore sessionStorage errors
            }
        }

        if (window.localStorage) {
            try {
                localStorage.removeItem('bw_onboarded');
                localStorage.removeItem('bw_onboarded_email');
            } catch (error) {
                // ignore localStorage errors
            }
        }

        var fallbackUrl = (config.accountUrl || '/my-account/').replace(/\/+$/, '') + '/?logged_out=1';
        var targetUrl = config.logoutUrl || fallbackUrl;
        window.location.href = targetUrl;
    }

    /**
     * Render session-missing error with clickable "here" link.
     */
    function showSessionMissingError() {
        if (!errorBox) return;

        var prefix = i18n('sessionMissingPrefix', 'Supabase session is missing. Please log in again ');
        var linkText = i18n('sessionMissingLink', 'here');

        errorBox.textContent = '';
        errorBox.appendChild(document.createTextNode(prefix));

        var link = document.createElement('a');
        link.href = '#';
        link.textContent = linkText;
        link.className = 'bw-password-modal__relogin-link';
        link.addEventListener('click', function (event) {
            event.preventDefault();
            redirectToLogin();
        });

        errorBox.appendChild(link);
        errorBox.hidden = false;
    }

    /**
     * Get i18n string
     */
    function i18n(key, fallback) {
        return config.i18n && config.i18n[key] ? config.i18n[key] : fallback;
    }

    /**
     * Validate password rules and update UI
     */
    function validatePassword(password) {
        var lengthOk = password.length >= 8;
        var upperOk = /[A-Z]/.test(password);
        var numberOk = /[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password);

        if (rules.length) {
            rules.length.classList.toggle('is-valid', lengthOk);
        }
        if (rules.upper) {
            rules.upper.classList.toggle('is-valid', upperOk);
        }
        if (rules.number) {
            rules.number.classList.toggle('is-valid', numberOk);
        }

        return lengthOk && upperOk && numberOk;
    }

    /**
     * Check password status via AJAX
     */
    function checkPasswordStatus() {
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
            },
            body: new URLSearchParams({
                action: 'bw_get_password_status',
                nonce: config.nonce
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data && data.success && data.data) {
                    return data.data;
                }
                return { enabled: false, needs_password: false };
            })
            .catch(function () {
                return { enabled: false, needs_password: false };
            });
    }

    /**
     * Submit password via AJAX
     */
    function submitPassword(newPassword, confirmPassword) {
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
            },
            body: new URLSearchParams({
                action: 'bw_set_password_modal',
                nonce: config.nonce,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        })
            .then(function (response) {
                return response.json();
            });
    }

    /**
     * Handle form submission
     */
    function handleSubmit(event) {
        event.preventDefault();

        hideError();

        var newPassword = newPasswordInput ? newPasswordInput.value.trim() : '';
        var confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

        // Validate
        if (newPassword.length < 8) {
            showError(i18n('passwordTooShort', 'Password must be at least 8 characters.'));
            return;
        }

        if (newPassword !== confirmPassword) {
            showError(i18n('passwordMismatch', 'Passwords do not match.'));
            return;
        }

        if (!validatePassword(newPassword)) {
            showError(i18n('passwordTooShort', 'Password does not meet requirements.'));
            return;
        }

        // Disable button and show loading
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = i18n('saving', 'Saving...');
        }

        submitPassword(newPassword, confirmPassword)
            .then(function (response) {
                if (response && response.success) {
                    // Success - close modal
                    closeModal();

                    // Clear form
                    if (form) {
                        form.reset();
                    }

                    // Reset rules
                    Object.keys(rules).forEach(function (key) {
                        if (rules[key]) {
                            rules[key].classList.remove('is-valid');
                        }
                    });
                } else {
                    // Error
                    if (response && response.data && response.data.code === 'supabase_session_missing') {
                        showSessionMissingError();
                        return;
                    }
                    var message = response && response.data && response.data.message
                        ? response.data.message
                        : i18n('genericError', 'Unable to save password. Please try again.');
                    showError(message);
                }
            })
            .catch(function () {
                showError(i18n('genericError', 'Unable to save password. Please try again.'));
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = i18n('savePassword', 'Save password');
                }
            });
    }

    /**
     * Handle password visibility toggle
     */
    function handleToggle(event) {
        var button = event.target.closest('.bw-password-modal__toggle');
        if (!button) return;

        var targetId = button.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) return;

        var eyeIcon = button.querySelector('.bw-icon-eye');
        var eyeOffIcon = button.querySelector('.bw-icon-eye-off');

        if (input.type === 'password') {
            input.type = 'text';
            if (eyeIcon) eyeIcon.style.display = 'none';
            if (eyeOffIcon) eyeOffIcon.style.display = 'block';
        } else {
            input.type = 'password';
            if (eyeIcon) eyeIcon.style.display = 'block';
            if (eyeOffIcon) eyeOffIcon.style.display = 'none';
        }
    }

    /**
     * Handle password input for rule validation
     */
    function handlePasswordInput() {
        var password = newPasswordInput ? newPasswordInput.value : '';
        validatePassword(password);
    }

    /**
     * Initialize
     */
    function init() {
        if (!initElements()) {
            return;
        }

        // Bind events
        if (form) {
            form.addEventListener('submit', handleSubmit);
        }

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', handlePasswordInput);
        }

        // Toggle buttons
        var toggleButtons = modal.querySelectorAll('.bw-password-modal__toggle');
        toggleButtons.forEach(function (btn) {
            btn.addEventListener('click', handleToggle);
        });

        // Check if modal should be shown
        checkPasswordStatus().then(function (status) {
            if (status.enabled && status.needs_password) {
                openModal();
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
