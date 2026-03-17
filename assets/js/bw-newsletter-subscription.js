(function () {
    var config = window.bwMailMarketingSubscription || {};
    var messages = config.messages || {};
    var forms = document.querySelectorAll('.bw-newsletter-subscription-form');

    if (!forms.length) {
        return;
    }

    function getMessage(key, fallback) {
        if (messages[key]) {
            return messages[key];
        }

        return fallback || '';
    }

    function normalizeEmail(value) {
        if (typeof value !== 'string') {
            return '';
        }

        return value.trim().replace(/\s+/g, '').toLowerCase();
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function setFieldInvalid(field, invalid) {
        if (!field) {
            return;
        }

        field.setAttribute('aria-invalid', invalid ? 'true' : 'false');
    }

    function clearValidationState(form) {
        setFieldInvalid(form.querySelector('input[name="email"]'), false);
        setFieldInvalid(form.querySelector('input[name="privacy"]'), false);
    }

    function setMessage(form, type, text) {
        var message = form.querySelector('.bw-newsletter-subscription-message');

        if (!message) {
            return;
        }

        message.classList.remove('is-success', 'is-error', 'is-loading');

        if (type === 'error') {
            message.classList.add('is-error');
            message.setAttribute('role', 'alert');
            message.setAttribute('aria-live', 'assertive');
        } else if (type === 'loading') {
            message.classList.add('is-loading');
            message.setAttribute('role', 'status');
            message.setAttribute('aria-live', 'polite');
        } else if (type === 'success') {
            message.classList.add('is-success');
            message.setAttribute('role', 'status');
            message.setAttribute('aria-live', 'polite');
        } else {
            message.setAttribute('role', 'status');
            message.setAttribute('aria-live', 'polite');
        }

        message.textContent = text || '';
    }

    function setBusy(form, busy) {
        var button = form.querySelector('.bw-newsletter-subscription-button');

        form.dataset.busy = busy ? '1' : '0';

        if (!button) {
            return;
        }

        button.disabled = !!busy;
        button.setAttribute('aria-disabled', busy ? 'true' : 'false');

        if (busy) {
            button.setAttribute('aria-busy', 'true');
        } else {
            button.removeAttribute('aria-busy');
        }
    }

    function validateForm(form) {
        var emailField = form.querySelector('input[name="email"]');
        var consentField = form.querySelector('input[name="privacy"]');
        var consentRequired = form.getAttribute('data-consent-required') !== '0';
        var normalizedEmail = normalizeEmail(emailField ? emailField.value : '');

        clearValidationState(form);

        if (!emailField || normalizedEmail === '') {
            setFieldInvalid(emailField, true);
            setMessage(form, 'error', getMessage('emptyEmail', 'Please enter your email address.'));
            return false;
        }

        emailField.value = normalizedEmail;

        if (!isValidEmail(normalizedEmail)) {
            setFieldInvalid(emailField, true);
            setMessage(form, 'error', getMessage('invalidEmail', 'Please enter a valid email address.'));
            return false;
        }

        if (consentRequired && consentField && !consentField.checked) {
            setFieldInvalid(consentField, true);
            setMessage(form, 'error', getMessage('missingConsent', 'You must agree to the Privacy Policy.'));
            return false;
        }

        setMessage(form, '', '');
        return true;
    }

    function parseResponse(response) {
        return response.text().then(function (text) {
            if (!text) {
                return null;
            }

            try {
                return JSON.parse(text);
            } catch (error) {
                return null;
            }
        });
    }

    function getResponseMessage(payload, fallback) {
        if (payload && payload.data && payload.data.message) {
            return payload.data.message;
        }

        return fallback;
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var ajaxUrl = config.ajaxUrl || '';
            var nonce = form.getAttribute('data-nonce') || '';
            var formData;

            event.preventDefault();

            if (form.dataset.busy === '1') {
                return;
            }

            if (!validateForm(form)) {
                return;
            }

            if (!ajaxUrl || !nonce) {
                setMessage(form, 'error', getMessage('genericFailure', 'Something went wrong. Please try again.'));
                return;
            }

            formData = new FormData(form);
            formData.set('email', normalizeEmail(formData.get('email')));
            formData.set('action', 'bw_mail_marketing_subscribe');
            formData.set('nonce', nonce);

            setBusy(form, true);
            setMessage(form, 'loading', getMessage('loading', 'Submitting...'));

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    return parseResponse(response).then(function (payload) {
                        if (!payload || typeof payload.success !== 'boolean' || !payload.data || typeof payload.data.code !== 'string') {
                            return {
                                success: false,
                                data: {
                                    code: 'generic_failure',
                                    message: getMessage('genericFailure', 'Something went wrong. Please try again.')
                                }
                            };
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    if (payload.success) {
                        setMessage(form, 'success', getResponseMessage(payload, getMessage('success', 'You have been subscribed successfully.')));
                        clearValidationState(form);

                        if (!payload.data || payload.data.code !== 'already_subscribed') {
                            form.reset();
                        }

                        return;
                    }

                    setMessage(form, 'error', getResponseMessage(payload, getMessage('genericFailure', 'Something went wrong. Please try again.')));

                    if (payload.data && payload.data.code === 'missing_consent') {
                        setFieldInvalid(form.querySelector('input[name="privacy"]'), true);
                        return;
                    }

                    if (payload.data && (payload.data.code === 'empty_email' || payload.data.code === 'invalid_email')) {
                        setFieldInvalid(form.querySelector('input[name="email"]'), true);
                    }
                })
                .catch(function () {
                    setMessage(form, 'error', getMessage('networkFailure', 'Something went wrong. Please try again.'));
                })
                .finally(function () {
                    setBusy(form, false);
                });
        });
    });
}());
