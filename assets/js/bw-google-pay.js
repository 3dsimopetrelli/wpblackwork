(function ($) {
    'use strict';

    var state = {
        paymentRequest: null,
        technicalError: false,
        initialized: false,
        canMakePaymentChecked: false,
        canMakePaymentResult: null,
        launchReady: false
    };

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

    function getAccordionPlaceholder() {
        return document.getElementById('bw-google-pay-accordion-placeholder');
    }

    function getExternalButtonWrapper() {
        return document.getElementById('bw-google-pay-button-wrapper');
    }

    function getExternalButtonContainer() {
        return document.getElementById('bw-google-pay-button');
    }

    function clearAccordionInlineUi() {
        var placeholder = getAccordionPlaceholder();
        if (!placeholder) {
            return;
        }

        placeholder.innerHTML = '';
        placeholder.style.display = 'none';
    }

    function renderTechnicalError(message) {
        var placeholder = getAccordionPlaceholder();
        if (placeholder) {
            placeholder.innerHTML = '' +
                '<div class="bw-gpay-unavailable" role="status" aria-live="polite">' +
                    '<p class="bw-gpay-unavailable__title">Google Pay integration error.</p>' +
                    '<p class="bw-gpay-unavailable__text">' + message + '</p>' +
                '</div>';
            placeholder.style.display = '';
        }

        var wrapper = getExternalButtonWrapper();
        if (wrapper) {
            wrapper.style.display = 'none';
        }

        state.technicalError = true;
        window.BW_GPAY_AVAILABLE = false;
        markGooglePayUnavailable();
        scheduleSelectorSync('gpay_technical_error');
    }

    function ensureSingleExternalTrigger() {
        var container = getExternalButtonContainer();
        if (!container) {
            return false;
        }

        var existing = document.getElementById('bw-google-pay-trigger');
        if (existing && existing.parentNode !== container) {
            existing.parentNode.removeChild(existing);
            existing = null;
        }

        if (!existing) {
            existing = document.createElement('button');
            existing.type = 'button';
            existing.id = 'bw-google-pay-trigger';
            existing.className = 'bw-custom-gpay-btn';
            existing.textContent = 'Google Pay';
            container.innerHTML = '';
            container.appendChild(existing);
        } else {
            container.innerHTML = '';
            container.appendChild(existing);
        }

        var wrapper = getExternalButtonWrapper();
        if (wrapper) {
            wrapper.style.display = '';
        }

        clearAccordionInlineUi();
        return true;
    }

    function parseWcAmount(text) {
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

    function getOrderTotalCents() {
        if (window.bwGooglePayParams && Number(window.bwGooglePayParams.orderTotalCents) > 0) {
            return Number(window.bwGooglePayParams.orderTotalCents);
        }

        var orderTotalNode = document.querySelector('.order-total .woocommerce-Price-amount bdi') ||
            document.querySelector('.woocommerce-Price-amount bdi:last-child');

        return Math.max(1, Math.round(parseWcAmount(orderTotalNode ? orderTotalNode.textContent : '') * 100));
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
                amount: getOrderTotalCents()
            }
        });
    }

    function runCanMakePaymentCheck() {
        if (!state.paymentRequest) {
            console.warn('[BW Google Pay] canMakePayment skipped: paymentRequest missing');
            return;
        }

        state.paymentRequest.canMakePayment().then(function (result) {
            state.canMakePaymentChecked = true;
            state.canMakePaymentResult = result || null;
            // Lifecycle guard: Stripe requires canMakePayment() to run before show().
            // launchReady is true once check completed and at least one wallet capability exists.
            state.launchReady = !!result;

            console.info('[BW Google Pay] canMakePayment completed', {
                result: result,
                launchReady: state.launchReady
            });
        }).catch(function (error) {
            state.canMakePaymentChecked = true;
            state.canMakePaymentResult = null;
            state.launchReady = false;

            console.error('[BW Google Pay] canMakePayment failed', error);
        });
    }

    function logNativeLauncherOwnership() {
        var roots = [
            document.getElementById('wc-stripe-express-checkout-element'),
            document.getElementById('wc-stripe-payment-request-wrapper'),
            document.getElementById('wcpay-express-checkout-element')
        ].filter(Boolean);

        var summary = roots.map(function (root) {
            var iframes = root.querySelectorAll('iframe').length;
            var buttons = root.querySelectorAll('button').length;
            var rect = root.getBoundingClientRect ? root.getBoundingClientRect() : { width: 0, height: 0 };
            return {
                id: root.id || '',
                className: root.className || '',
                connected: !!root.isConnected,
                visible: rect.width > 0 && rect.height > 0,
                iframeCount: iframes,
                buttonCount: buttons
            };
        });

        console.info('[BW Google Pay] native launcher ownership snapshot', {
            roots: summary,
            note: 'Stripe native express launcher may be internally owned (iframe/shadow) and not safely proxy-clickable.'
        });
    }

    function bindGooglePayLaunchHandler() {
        $(document)
            .off('click.bwgpay', '#bw-google-pay-trigger')
            .on('click.bwgpay', '#bw-google-pay-trigger', function (event) {
                event.preventDefault();
                console.info('[BW Google Pay] custom trigger clicked');

                if (!state.paymentRequest || state.technicalError) {
                    console.error('[BW Google Pay] launch blocked: invalid runtime state', {
                        hasPaymentRequest: !!state.paymentRequest,
                        technicalError: state.technicalError
                    });
                    return;
                }

                console.info('[BW Google Pay] paymentRequest readiness state', {
                    canMakePaymentChecked: state.canMakePaymentChecked,
                    canMakePaymentResult: state.canMakePaymentResult,
                    launchReady: state.launchReady
                });

                if (!state.canMakePaymentChecked) {
                    console.warn('[BW Google Pay] launch blocked: canMakePayment not completed yet');
                    runCanMakePaymentCheck();
                    return;
                }

                if (!state.launchReady) {
                    console.warn('[BW Google Pay] fallback show() skipped: readiness invalid');
                    return;
                }

                try {
                    console.info('[BW Google Pay] launching via local authoritative paymentRequest.show()');
                    var launch = state.paymentRequest.show();
                    if (launch && typeof launch.catch === 'function') {
                        launch.catch(function (error) {
                            console.error('[BW Google Pay] show() rejected', error);
                        });
                    }
                } catch (error) {
                    console.error('[BW Google Pay] show() threw', error);
                }
            });
    }

    function validateConfig() {
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

    function initRuntime() {
        if (state.initialized || state.technicalError) {
            return;
        }

        var configError = validateConfig();
        if (configError) {
            renderTechnicalError(configError);
            return;
        }

        try {
            var stripe = Stripe(bwGooglePayParams.publishableKey);
            state.paymentRequest = stripe.paymentRequest({
                country: (bwGooglePayParams.country || 'IT').toUpperCase(),
                currency: (bwGooglePayParams.currency || 'eur').toLowerCase(),
                disableWallets: ['link'],
                total: {
                    label: 'Ordine BlackWork',
                    amount: getOrderTotalCents()
                },
                requestPayerName: true,
                requestPayerEmail: true,
                requestPayerPhone: true
            });
        } catch (error) {
            console.error('[BW Google Pay] init failed', error);
            renderTechnicalError('Unable to initialize Google Pay. Please verify gateway configuration.');
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

        bindGooglePayLaunchHandler();
        ensureSingleExternalTrigger();

        window.BW_GPAY_AVAILABLE = true;
        markGooglePayAvailable();
        scheduleSelectorSync('gpay_ready');
        logNativeLauncherOwnership();
        runCanMakePaymentCheck();

        state.initialized = true;
    }

    $(document).ready(function () {
        initRuntime();

        $(document.body)
            .off('updated_checkout.bwgpay')
            .on('updated_checkout.bwgpay', function () {
                if (state.technicalError) {
                    return;
                }

                ensureSingleExternalTrigger();
                window.BW_GPAY_AVAILABLE = true;
                markGooglePayAvailable();
                scheduleSelectorSync('gpay_updated_checkout');
                updatePaymentRequestTotal();
                runCanMakePaymentCheck();
            });
    });

})(jQuery);
