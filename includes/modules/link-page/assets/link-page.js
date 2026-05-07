(function () {
    'use strict';

    function normalizeEmail(value) {
        if (typeof value !== 'string') {
            return '';
        }

        return value.trim().replace(/\s+/g, '').toLowerCase();
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function toFormBody(payload) {
        return new URLSearchParams(payload).toString();
    }

    function sendClick(payload, endpoint) {
        if (!endpoint) {
            return;
        }

        var body = toFormBody(payload);

        if (navigator.sendBeacon) {
            var blob = new Blob([body], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
            var queued = navigator.sendBeacon(endpoint, blob);
            if (queued) {
                return;
            }
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body,
            keepalive: true,
            credentials: 'same-origin'
        }).catch(function () {
            return null;
        });
    }

    function initAnalyticsTracking() {
        var appConfig = window.bwLinkPageConfig || {};
        var config = appConfig.analytics || {};
        var endpoint = typeof config.endpoint === 'string' ? config.endpoint : '';
        var action = typeof config.action === 'string' ? config.action : 'bw_link_page_track_click';
        var nonce = typeof config.nonce === 'string' ? config.nonce : '';
        var pageId = Number(config.pageId || 0);
        var enabled = !!config.enabled;

        if (!enabled || !endpoint || !pageId) {
            return;
        }

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            var link = target.closest('.link-item[data-bw-link-id]');
            if (!link) {
                return;
            }

            var linkId = String(link.getAttribute('data-bw-link-id') || '');
            var linkLabel = String(link.getAttribute('data-bw-link-label') || '').trim();
            var targetUrl = String(link.getAttribute('href') || '');

            if (!linkId || !linkLabel) {
                return;
            }

            sendClick({
                action: action,
                nonce: nonce,
                page_id: String(pageId),
                link_id: linkId,
                link_label: linkLabel,
                target_url: targetUrl
            }, endpoint);
        }, { capture: true });
    }

    function setNewsletterMessage(form, type, text) {
        var message = form.querySelector('.newsletter-message');
        if (!message) {
            return;
        }

        message.classList.remove('is-success', 'is-error', 'is-loading');
        if (type) {
            message.classList.add(type);
        }

        message.textContent = text || '';
    }

    function setConsentErrorState(form, hasError) {
        var consentWrapper = form.querySelector('.newsletter-consent');
        if (!consentWrapper) {
            return;
        }

        consentWrapper.classList.toggle('has-error', !!hasError);
    }

    function setEmailInvalidState(form, isInvalid) {
        var combo = form.querySelector('.newsletter-email-combo');
        var emailInput = form.querySelector('input[name="email"]');

        if (combo) {
            combo.classList.toggle('is-invalid', !!isInvalid);
        }

        if (emailInput) {
            emailInput.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
        }
    }

    function getNewsletterReadiness(form, consentRequired) {
        var emailInput = form.querySelector('input[name="email"]');
        var privacyInput = form.querySelector('input[name="privacy"]');
        var email = normalizeEmail(emailInput ? emailInput.value : '');
        var emailValid = !!emailInput && email !== '' && isValidEmail(email);
        var consentValid = !consentRequired || !privacyInput || !!privacyInput.checked;

        return {
            emailValid: emailValid,
            consentValid: consentValid,
            ready: emailValid && consentValid
        };
    }

    function updateNewsletterUIState(form, consentRequired) {
        var state = getNewsletterReadiness(form, consentRequired);
        form.classList.toggle('is-ready', state.ready);

        if (state.emailValid) {
            setEmailInvalidState(form, false);
        }

        if (state.consentValid) {
            setConsentErrorState(form, false);
        }
    }

    function setNewsletterBusy(form, busy) {
        var submit = form.querySelector('.newsletter-submit');
        if (submit) {
            submit.disabled = !!busy;
            submit.setAttribute('aria-disabled', busy ? 'true' : 'false');
        }
    }

    function initNewsletterForm() {
        var appConfig = window.bwLinkPageConfig || {};
        var config = appConfig.newsletter || {};
        var enabled = !!config.enabled;
        var endpoint = typeof config.endpoint === 'string' ? config.endpoint : '';
        var action = typeof config.action === 'string' ? config.action : 'bw_mail_marketing_subscribe';
        var nonce = typeof config.nonce === 'string' ? config.nonce : '';
        var consentRequired = Number(config.consentRequired || 0) === 1;
        var form = document.querySelector('.newsletter-form');

        if (!enabled || !form || !endpoint || !nonce) {
            return;
        }

        updateNewsletterUIState(form, consentRequired);

        form.addEventListener('input', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            if (target.matches('input[name="email"]')) {
                var email = normalizeEmail(target.value || '');
                if (email === '' || isValidEmail(email)) {
                    setEmailInvalidState(form, false);
                }
            }

            updateNewsletterUIState(form, consentRequired);
        });

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            if (target.matches('input[name="privacy"]')) {
                setConsentErrorState(form, !target.checked && consentRequired);
            }

            updateNewsletterUIState(form, consentRequired);
        });

        form.addEventListener('submit', function (event) {
            var emailInput = form.querySelector('input[name="email"]');
            var nameInput = form.querySelector('input[name="name"]');
            var privacyInput = form.querySelector('input[name="privacy"]');
            var email = normalizeEmail(emailInput ? emailInput.value : '');
            var payload;

            event.preventDefault();

            if (!emailInput || email === '') {
                setEmailInvalidState(form, true);
                setNewsletterMessage(form, 'is-error', 'Please enter your email address.');
                return;
            }

            if (!isValidEmail(email)) {
                setEmailInvalidState(form, true);
                setNewsletterMessage(form, 'is-error', 'Please enter a valid email address.');
                return;
            }

            if (consentRequired && privacyInput && !privacyInput.checked) {
                setConsentErrorState(form, true);
                setNewsletterMessage(form, 'is-error', 'Please confirm the privacy consent to subscribe.');
                return;
            }

            setEmailInvalidState(form, false);
            setConsentErrorState(form, false);

            payload = {
                action: action,
                nonce: nonce,
                email: email
            };

            if (nameInput && String(nameInput.value || '').trim() !== '') {
                payload.name = String(nameInput.value || '').trim();
            }

            if (privacyInput && privacyInput.checked) {
                payload.privacy = '1';
            }

            setNewsletterBusy(form, true);
            setNewsletterMessage(form, 'is-loading', 'Submitting...');

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: toFormBody(payload),
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return null;
                    });
                })
                .then(function (data) {
                    if (data && data.success) {
                        setNewsletterMessage(form, 'is-success', (data.data && data.data.message) ? data.data.message : 'Thanks for subscribing!');
                        form.reset();
                        updateNewsletterUIState(form, consentRequired);
                        return;
                    }

                    setNewsletterMessage(form, 'is-error', (data && data.data && data.data.message) ? data.data.message : 'Something went wrong. Please try again.');
                })
                .catch(function () {
                    setNewsletterMessage(form, 'is-error', 'Something went wrong. Please try again.');
                })
                .finally(function () {
                    setNewsletterBusy(form, false);
                    updateNewsletterUIState(form, consentRequired);
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAnalyticsTracking();
            initNewsletterForm();
        });
    } else {
        initAnalyticsTracking();
        initNewsletterForm();
    }
}());
