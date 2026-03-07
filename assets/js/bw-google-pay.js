(function ($) {
    'use strict';

    var BW_GOOGLE_PAY_DEBUG = window.BW_GOOGLE_PAY_DEBUG === true;

    var state = {
        stripe: null,
        paymentRequest: null,
        initialized: false,
        hasTechnicalError: false
    };

    function debugLog() {
        if (!BW_GOOGLE_PAY_DEBUG || !window.console || typeof window.console.log !== 'function') {
            return;
        }
        window.console.log.apply(window.console, arguments);
    }

    function getGooglePayRadio() {
        return document.querySelector('input[name="payment_method"][value="bw_google_pay"]');
    }

    function markGooglePayUnavailable() {
        var radio = getGooglePayRadio();
        if (!radio) {
            return;
        }

        radio.setAttribute('data-bw-unavailable', '1');
        radio.removeAttribute('data-bw-gpay-checking');

        var row = radio.closest('.bw-payment-method');
        if (row) {
            row.setAttribute('data-bw-unavailable', '1');
        }
    }

    function markGooglePayAvailable() {
        var radio = getGooglePayRadio();
        if (!radio) {
            return;
        }

        radio.removeAttribute('data-bw-unavailable');
        radio.removeAttribute('data-bw-gpay-checking');

        var row = radio.closest('.bw-payment-method');
        if (row) {
            row.removeAttribute('data-bw-unavailable');
        }
    }

    function scheduleSelectorSync(reason) {
        if (typeof window.bwScheduleSyncCheckoutActionButtons === 'function') {
            window.bwScheduleSyncCheckoutActionButtons(reason || 'gpay');
        }
    }

    function renderTechnicalError(message) {
        var placeholder = document.getElementById('bw-google-pay-accordion-placeholder');
        if (placeholder) {
            placeholder.innerHTML = '' +
                '<div class="bw-gpay-unavailable" role="status" aria-live="polite">' +
                    '<p class="bw-gpay-unavailable__title">Google Pay integration error.</p>' +
                    '<p class="bw-gpay-unavailable__text">' + message + '</p>' +
                '</div>';
            placeholder.style.display = '';
        }

        var wrapper = document.getElementById('bw-google-pay-button-wrapper');
        if (wrapper) {
            wrapper.style.display = 'none';
        }

        state.hasTechnicalError = true;
        window.BW_GPAY_AVAILABLE = false;
        markGooglePayUnavailable();
        scheduleSelectorSync('gpay_technical_error');
    }

    function ensureGooglePayTrigger() {
        var container = document.getElementById('bw-google-pay-button');
        if (!container) {
            return false;
        }

        if (document.getElementById('bw-google-pay-trigger')) {
            return true;
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'bw-google-pay-trigger';
        btn.className = 'bw-custom-gpay-btn';
        btn.textContent = 'Google Pay';

        container.innerHTML = '';
        container.appendChild(btn);

        return true;
    }

    function showGooglePayActiveUi() {
        if (state.hasTechnicalError) {
            return;
        }

        var wrapper = document.getElementById('bw-google-pay-button-wrapper');
        var placeholder = document.getElementById('bw-google-pay-accordion-placeholder');

        ensureGooglePayTrigger();

        if (wrapper) {
            wrapper.style.display = '';
        }
        if (placeholder) {
            placeholder.style.display = 'none';
        }

        window.BW_GPAY_AVAILABLE = true;
        markGooglePayAvailable();
        scheduleSelectorSync('gpay_active');
    }

    function bwParseWcAmount(text) {
        var cleaned = String(text || '').replace(/[^\d.,]/g, '');
        if (!cleaned) {
            return 0;
        }

        var lastDot = cleaned.lastIndexOf('.');
        var lastComma = cleaned.lastIndexOf(',');

        if (lastComma > lastDot) {
            cleaned = cleaned.replace(/\./g, '').replace(',', '.');
        } else if (lastDot > lastComma) {
            cleaned = cleaned.replace(/,/g, '');
        }

        return parseFloat(cleaned) || 0;
    }

    function bwGetOrderTotalCents() {
        if (window.bwGooglePayParams && Number(window.bwGooglePayParams.orderTotalCents) > 0) {
            return Number(window.bwGooglePayParams.orderTotalCents);
        }

        var el = document.querySelector('.order-total .woocommerce-Price-amount bdi') ||
            document.querySelector('.woocommerce-Price-amount bdi:last-child');

        return Math.max(1, Math.round(bwParseWcAmount(el ? el.textContent : '') * 100));
    }

    function validateRequiredCheckoutFields() {
        var form = document.querySelector('form.checkout');
        if (!form) {
            return true;
        }

        var requiredRows = form.querySelectorAll('.validate-required');
        for (var i = 0; i < requiredRows.length; i += 1) {
            var row = requiredRows[i];
            if (row.offsetParent === null) {
                continue;
            }

            var input = row.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
            if (!input) {
                continue;
            }

            var type = (input.getAttribute('type') || '').toLowerCase();
            var valid = true;

            if (type === 'checkbox') {
                valid = !!input.checked;
            } else {
                valid = String(input.value || '').trim() !== '';
            }

            if (!valid) {
                row.classList.remove('woocommerce-validated');
                row.classList.add('woocommerce-invalid');
                input.focus();
                return false;
            }

            row.classList.remove('woocommerce-invalid');
            row.classList.add('woocommerce-validated');
        }

        return true;
    }

    function submitCheckoutWithGooglePay(ev, methodId) {
        var $form = $('form.checkout');
        if (!$form.length) {
            ev.complete('fail');
            return;
        }

        var $hidden = $('#bw_google_pay_method_id');
        if (!$hidden.length) {
            $hidden = $('<input>', {
                type: 'hidden',
                id: 'bw_google_pay_method_id',
                name: 'bw_google_pay_method_id',
                value: ''
            });
            $form.append($hidden);
        }
        $hidden.val(methodId);

        $.ajax({
            type: 'POST',
            url: bwGooglePayParams.ajaxCheckoutUrl,
            data: $form.serialize(),
            dataType: 'json'
        }).done(function (response) {
            if (response && response.result === 'success') {
                ev.complete('success');
                window.location.href = response.redirect;
                return;
            }

            ev.complete('fail');
            if (response && response.messages) {
                var $notices = $('.woocommerce-notices-wrapper').first();
                if ($notices.length) {
                    $notices.html(response.messages);
                }
                $('html, body').animate({ scrollTop: 0 }, 250);
            }
        }).fail(function () {
            ev.complete('fail');
        });
    }

    function updatePaymentRequestTotal() {
        if (!state.paymentRequest) {
            return;
        }

        state.paymentRequest.update({
            total: {
                label: 'Ordine BlackWork',
                amount: bwGetOrderTotalCents()
            }
        });
    }

    function bindGooglePayTrigger() {
        $(document)
            .off('click.bwgpay', '#bw-google-pay-trigger')
            .on('click.bwgpay', '#bw-google-pay-trigger', function (e) {
                e.preventDefault();

                if (state.hasTechnicalError || !state.paymentRequest) {
                    return;
                }

                if (!validateRequiredCheckoutFields()) {
                    return;
                }

                try {
                    var result = state.paymentRequest.show();
                    if (result && typeof result.catch === 'function') {
                        result.catch(function (error) {
                            debugLog('[BW Google Pay] show() rejected', error);
                        });
                    }
                } catch (error) {
                    debugLog('[BW Google Pay] show() threw', error);
                }
            });
    }

    function validateBootConfig() {
        if (typeof window.bwGooglePayParams === 'undefined') {
            return 'Google Pay settings are missing from checkout configuration.';
        }

        if (typeof window.Stripe === 'undefined') {
            return 'Stripe runtime is not available for Google Pay.';
        }

        if (!bwGooglePayParams.publishableKey) {
            return 'Stripe publishable key is missing for Google Pay.';
        }

        if (!bwGooglePayParams.ajaxCheckoutUrl) {
            return 'Checkout endpoint is missing for Google Pay.';
        }

        return '';
    }

    function initGooglePayRuntime() {
        if (state.initialized || state.hasTechnicalError) {
            return;
        }

        var configError = validateBootConfig();
        if (configError) {
            renderTechnicalError(configError);
            return;
        }

        try {
            state.stripe = Stripe(bwGooglePayParams.publishableKey);
            state.paymentRequest = state.stripe.paymentRequest({
                country: (bwGooglePayParams.country || 'IT').toUpperCase(),
                currency: (bwGooglePayParams.currency || 'eur').toLowerCase(),
                disableWallets: ['link'],
                total: {
                    label: 'Ordine BlackWork',
                    amount: bwGetOrderTotalCents()
                },
                requestPayerName: true,
                requestPayerEmail: true,
                requestPayerPhone: true
            });
        } catch (error) {
            renderTechnicalError('Unable to initialize Google Pay. Please verify gateway configuration.');
            debugLog('[BW Google Pay] init error', error);
            return;
        }

        state.paymentRequest.on('paymentmethod', function (ev) {
            submitCheckoutWithGooglePay(ev, ev.paymentMethod.id);
        });

        state.paymentRequest.on('cancel', function () {
            $('#bw_google_pay_method_id').val('');
            if (window.bwCheckout && typeof window.bwCheckout.removeLoadingState === 'function') {
                window.bwCheckout.removeLoadingState();
            }
            $(document.body).trigger('update_checkout');
        });

        bindGooglePayTrigger();
        showGooglePayActiveUi();
        state.initialized = true;

        // Diagnostic only: do not use as a user-facing gate.
        state.paymentRequest.canMakePayment().then(function (result) {
            debugLog('[BW Google Pay] canMakePayment diagnostic', result);
        }).catch(function (error) {
            debugLog('[BW Google Pay] canMakePayment diagnostic failed', error);
        });
    }

    $(document).ready(function () {
        initGooglePayRuntime();

        $(document.body).off('updated_checkout.bwgpay').on('updated_checkout.bwgpay', function () {
            if (state.hasTechnicalError) {
                return;
            }
            showGooglePayActiveUi();
            updatePaymentRequestTotal();
        });
    });

})(jQuery);
