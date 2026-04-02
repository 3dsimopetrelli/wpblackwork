(function () {
    var config = window.bwMailMarketingSubscription || {};
    var messages = config.messages || {};
    var REQUEST_TIMEOUT = 15000;

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

    function syncFieldState(field) {
        var wrapper;
        var value;

        if (!field || !field.classList || !field.classList.contains('bw-newsletter-subscription-input')) {
            return;
        }

        wrapper = field.closest('.bw-newsletter-subscription-field');
        if (!wrapper) {
            return;
        }

        value = typeof field.value === 'string' ? field.value.trim() : '';
        wrapper.classList.toggle('is-filled', value !== '');
    }

    function syncFormFieldStates(form) {
        if (!form) {
            return;
        }

        form.querySelectorAll('.bw-newsletter-subscription-input').forEach(syncFieldState);
    }

    function syncFormsInNode(node) {
        if (!node || node.nodeType !== 1) {
            return;
        }

        if (node.matches && node.matches('.bw-newsletter-subscription-form')) {
            syncFormFieldStates(node);
        }

        if (node.querySelectorAll) {
            node.querySelectorAll('.bw-newsletter-subscription-form').forEach(syncFormFieldStates);
        }
    }

    function bootstrapLateInjectedForms() {
        if (typeof window.MutationObserver !== 'function' || !document.body) {
            return;
        }

        (new window.MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (!mutation.addedNodes || !mutation.addedNodes.length) {
                    return;
                }

                Array.prototype.forEach.call(mutation.addedNodes, syncFormsInNode);
            });
        })).observe(document.body, {
            childList: true,
            subtree: true
        });
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

    function createTimeoutController(timeoutMs) {
        var controller = typeof window.AbortController === 'function' ? new window.AbortController() : null;
        var timerId = window.setTimeout(function () {
            if (controller) {
                controller.abort();
            }
        }, timeoutMs);

        return {
            signal: controller ? controller.signal : undefined,
            cleanup: function () {
                window.clearTimeout(timerId);
            }
        };
    }

    function handleSubmit(form, event) {
        var ajaxUrl = config.ajaxUrl || '';
        var nonce = form.getAttribute('data-nonce') || '';
        var formData;
        var timeoutControl;
        var request;

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

        timeoutControl = createTimeoutController(REQUEST_TIMEOUT);
        request = fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
            signal: timeoutControl.signal
        });

        if (!timeoutControl.signal) {
            request = Promise.race([
                request,
                new Promise(function (_, reject) {
                    window.setTimeout(function () {
                        reject(new Error('timeout'));
                    }, REQUEST_TIMEOUT);
                })
            ]);
        }

        request
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
                        syncFormFieldStates(form);
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
                timeoutControl.cleanup();
                setBusy(form, false);
            });
    }

    /*
     * Event delegation: attach a single listener on document instead of binding
     * directly to each form at script load time.
     *
     * This is necessary because Theme Builder Lite custom footer templates are
     * injected via wp_footer at priority 20, which runs AFTER wp_print_footer_scripts
     * (also priority 20, registered earlier by WordPress core). The IIFE therefore
     * executes before the form HTML exists in the DOM, so a direct querySelectorAll
     * would find nothing. Delegating from document captures submit events from any
     * form that matches, regardless of when it enters the DOM.
     */
    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || !form.classList.contains('bw-newsletter-subscription-form')) {
            return;
        }

        handleSubmit(form, event);
    });

    document.addEventListener('input', function (event) {
        syncFieldState(event.target);
    });

    document.addEventListener('change', function (event) {
        syncFieldState(event.target);
    });

    document.addEventListener('focusout', function (event) {
        syncFieldState(event.target);
    });

    document.querySelectorAll('.bw-newsletter-subscription-form').forEach(syncFormFieldStates);
    bootstrapLateInjectedForms();
}());
