(function ($) {
    'use strict';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getApplePayFailureHint(extraReason) {
        var hint = 'Possible causes: Apple Pay is not supported on this browser/device, no eligible card is configured in Wallet, or the domain is not verified in Stripe.';
        if (extraReason) {
            hint += ' Technical detail: ' + extraReason;
        }
        return hint;
    }

    function renderApplePayPlaceholder(message, isError, reason, title) {
        var render = function () {
            var placeholder = document.getElementById('bw-apple-pay-accordion-placeholder');
            if (!placeholder) {
                return;
            }
            var safeMessage = escapeHtml(message);
            var safeReason = reason ? escapeHtml(reason) : '';
            var safeTitle = title ? escapeHtml(title) : '';

            if (safeTitle || safeReason) {
                var reasonClass = isError ? ' bw-apple-pay-init-info__reason--error' : '';
                placeholder.innerHTML = '' +
                    '<div class="bw-apple-pay-init-info" role="status" aria-live="polite">' +
                        (safeTitle ? '<p class="bw-apple-pay-init-info__title">' + safeTitle + '</p>' : '') +
                        '<p class="bw-apple-pay-init-info__reason' + reasonClass + '">' + safeMessage + '</p>' +
                        (safeReason ? '<p class="bw-apple-pay-init-info__check">' + safeReason + '</p>' : '') +
                    '</div>' +
                '';
                return;
            }

            var klass = isError ? ' bw-apple-pay-unavailable__text--error' : '';
            placeholder.innerHTML = '' +
                '<div class="bw-apple-pay-unavailable" role="status" aria-live="polite">' +
                    '<p class="bw-apple-pay-unavailable__text' + klass + '">' + safeMessage + '</p>' +
                '</div>';
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', render);
        } else {
            render();
        }
    }

    var BW_APPLE_PAY_DEBUG = window.BW_APPLE_PAY_DEBUG === true;

    if (typeof bwApplePayParams === 'undefined' || typeof Stripe === 'undefined') {
        renderApplePayPlaceholder(
            'Initializing Apple Pay failed.',
            true,
            getApplePayFailureHint('Stripe.js is missing or not loaded.'),
            'Apple Pay initialization error'
        );
        return;
    }

    if (!bwApplePayParams.publishableKey) {
        renderApplePayPlaceholder(
            'Initializing Apple Pay failed.',
            true,
            getApplePayFailureHint('Missing live publishable key.'),
            'Apple Pay initialization error'
        );
        return;
    }

    var stripe = Stripe(bwApplePayParams.publishableKey);
    var paymentRequest = null;
    var applePayAvailable = false;
    var applePayState = 'initializing'; // initializing | available | unavailable
    var isCheckingAvailability = false;
    window.BW_APPLE_PAY_AVAILABLE = false;

    function parseAmount(text) {
        var cleaned = text.replace(/[^\d.,]/g, '');
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
        var $el = $('.order-total .woocommerce-Price-amount bdi').first();
        if (!$el.length) {
            $el = $('.woocommerce-Price-amount bdi').last();
        }
        return Math.round(parseAmount($el.text()) * 100);
    }

    function validateRequiredFields() {
        var $form = $('form.checkout');
        var $requiredRows = $form.find('.validate-required:visible');
        var invalidLabels = [];

        $requiredRows.each(function () {
            var $row = $(this);
            var $input = $row.find('input, select, textarea').filter(function () {
                var $el = $(this);
                var type = ($el.attr('type') || '').toLowerCase();
                return !$el.prop('disabled') && type !== 'hidden';
            }).first();

            if (!$input.length) {
                return;
            }

            var isValid = true;
            var type = ($input.attr('type') || '').toLowerCase();

            if (type === 'checkbox') {
                isValid = $input.is(':checked');
            } else if ($input.is('select')) {
                isValid = $.trim($input.val() || '') !== '';
            } else {
                isValid = $.trim($input.val() || '') !== '';
            }

            if (!isValid) {
                var labelText = $.trim($row.find('label').first().clone().children().remove().end().text()) || 'Required field';
                invalidLabels.push(labelText);
                $row.removeClass('woocommerce-validated').addClass('woocommerce-invalid');
            } else {
                $row.removeClass('woocommerce-invalid').addClass('woocommerce-validated');
            }
        });

        if (!invalidLabels.length) {
            return true;
        }

        var $notices = $('.woocommerce-notices-wrapper').first();
        if ($notices.length) {
            $notices.html(
                '<ul class="woocommerce-error" role="alert"><li>Please fill in required fields: ' +
                invalidLabels.join(', ') +
                '.</li></ul>'
            );
        }
        $('html, body').animate({ scrollTop: 0 }, 250);
        return false;
    }

    function renderCheckoutNotice(type, message) {
        var klass = type === 'error' ? 'woocommerce-error' : 'woocommerce-info';
        var $notices = $('.woocommerce-notices-wrapper').first();
        if ($notices.length) {
            $notices.html('<ul class="' + klass + '" role="alert"><li>' + message + '</li></ul>');
        }
        $('html, body').animate({ scrollTop: 0 }, 250);
    }

    function disableApplePaySelection() {
        var $appleInput = $('input[name="payment_method"][value="bw_apple_pay"]');
        if (!$appleInput.length) {
            return;
        }

        var wasChecked = $appleInput.is(':checked');
        $appleInput.prop('disabled', true).attr('aria-disabled', 'true');
        $appleInput.attr('data-bw-unavailable', '1');
        $appleInput.closest('.bw-payment-method').attr('data-bw-unavailable', '1');
        if (wasChecked) {
            $appleInput.prop('checked', false);
            var $fallback = $('input[name="payment_method"]').not('[value="bw_apple_pay"]').filter(':enabled').first();
            if ($fallback.length) {
                var fallbackRadio = $fallback.get(0);
                fallbackRadio.checked = true;
                fallbackRadio.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                $(document.body).trigger('payment_method_selected');
            }
        }
    }

    function dedupeApplePayDom() {
        var $wrappers = $('#bw-apple-pay-button-wrapper');
        if ($wrappers.length > 1) {
            $wrappers.slice(1).remove();
        }

        var $triggers = $('#bw-apple-pay-trigger');
        if ($triggers.length > 1) {
            $triggers.slice(1).remove();
        }
    }

    function scheduleGlobalWalletSync(reason) {
        if (typeof window.bwScheduleSyncCheckoutActionButtons === 'function') {
            window.bwScheduleSyncCheckoutActionButtons(reason || 'apple_pay');
        }
    }

    function syncApplePayInfoVisibility() {
        var shouldShow = applePayState === 'available' && applePayAvailable === true;
        $('.payment_method_bw_apple_pay .bw-apple-pay-info').toggle(shouldShow);
    }

    function showAppleButtonIfSelected() {
        var selectedMethod = $('input[name="payment_method"]:checked').val();
        var $wrapper = $('#bw-apple-pay-button-wrapper');
        var isAppleMethod = selectedMethod === 'bw_apple_pay';

        if (isAppleMethod) {
            if (applePayState === 'available' && applePayAvailable) {
                $wrapper.show();
                $('#bw-apple-pay-accordion-placeholder').hide();
            } else {
                $wrapper.hide();
                $('#bw-apple-pay-accordion-placeholder').show();
            }
            dedupeApplePayDom();
        } else {
            $wrapper.hide();
        }

        syncApplePayInfoVisibility();
        scheduleGlobalWalletSync('apple_ui');
    }

    function renderApplePayState() {
        if (applePayState === 'initializing') {
            renderApplePayPlaceholder(
                'Initializing Apple Pay…',
                false,
                '',
                'Apple Pay initialization'
            );
            return;
        }

        if (applePayState === 'unavailable') {
            renderApplePayPlaceholder(
                'Apple Pay non disponibile: verifica Safari/iPhone, carta nel Wallet, HTTPS, e dominio verificato su Stripe.',
                true,
                getApplePayFailureHint('canMakePayment returned no applePay support.'),
                'Apple Pay unavailable'
            );
        }
    }

    function runAvailabilityCheck() {
        if (!paymentRequest || isCheckingAvailability) {
            return;
        }
        isCheckingAvailability = true;
        applePayState = 'initializing';
        applePayAvailable = false;
        window.BW_APPLE_PAY_AVAILABLE = false;
        renderApplePayState();
        showAppleButtonIfSelected();

        paymentRequest.canMakePayment().then(function (result) {
            console.log('[BW Apple Pay] canMakePayment:', result);
            applePayAvailable = !!(result && result.applePay === true);
            window.BW_APPLE_PAY_AVAILABLE = applePayAvailable === true;
            applePayState = applePayAvailable ? 'available' : 'unavailable';

            if (!applePayAvailable) {
                disableApplePaySelection();
                renderApplePayState();
                showAppleButtonIfSelected();
                isCheckingAvailability = false;
                return;
            }

            $('input[name="payment_method"][value="bw_apple_pay"]')
                .prop('disabled', false)
                .removeAttr('aria-disabled')
                .removeAttr('data-bw-unavailable')
                .closest('.bw-payment-method')
                .removeAttr('data-bw-unavailable');
            renderApplePayPlaceholder(
                'You\'ll be redirected to Apple Pay to complete your purchase.',
                false
            );
            ensureButton();
            showAppleButtonIfSelected();
            isCheckingAvailability = false;
        }).catch(function (error) {
            applePayAvailable = false;
            window.BW_APPLE_PAY_AVAILABLE = false;
            applePayState = 'unavailable';
            renderApplePayPlaceholder(
                'Apple Pay non disponibile: verifica Safari/iPhone, carta nel Wallet, HTTPS, e dominio verificato su Stripe.',
                true,
                getApplePayFailureHint(error && error.message ? error.message : 'canMakePayment failed unexpectedly.'),
                'Apple Pay unavailable'
            );
            disableApplePaySelection();
            showAppleButtonIfSelected();
            isCheckingAvailability = false;
        });
    }

    function submitCheckoutWithApplePay(ev, methodId) {
        var $form = $('form.checkout');

        var $hidden = $('#bw_apple_pay_method_id');
        if (!$hidden.length) {
            $hidden = $('<input>', {
                type: 'hidden',
                id: 'bw_apple_pay_method_id',
                name: 'bw_apple_pay_method_id',
                value: ''
            });
            $form.append($hidden);
        }
        $hidden.val(methodId);

        $.ajax({
            type: 'POST',
            url: bwApplePayParams.ajaxCheckoutUrl,
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] Checkout response:', response);

                if (response && response.result === 'success') {
                    ev.complete('success');
                    window.location.href = response.redirect;
                } else {
                    ev.complete('fail');
                    if (response && response.messages) {
                        var $notices = $('.woocommerce-notices-wrapper').first();
                        if ($notices.length) {
                            $notices.html(response.messages);
                        }
                        $('html, body').animate({ scrollTop: 0 }, 300);
                    }
                }
            },
            error: function () {
                ev.complete('fail');
            }
        });
    }

    function ensureButton() {
        if ($('#bw-apple-pay-trigger').length) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.id = 'bw-apple-pay-trigger';
        button.className = 'bw-custom-applepay-btn';
        button.textContent = 'Pay with Apple Pay';

        var container = document.getElementById('bw-apple-pay-button');
        if (!container) {
            return;
        }

        container.innerHTML = '';
        container.appendChild(button);

        $(document)
            .off('click.bwapplepay', '#bw-apple-pay-trigger')
            .on('click.bwapplepay', '#bw-apple-pay-trigger', function (e) {
                e.preventDefault();
                if (!validateRequiredFields()) {
                    return;
                }
                paymentRequest.show();
            });
    }

    function initApplePay() {
        var initialCents = getOrderTotalCents();
        applePayState = 'initializing';
        applePayAvailable = false;
        renderApplePayState();

        var country = (bwApplePayParams.country || 'IT').toUpperCase();
        var currency = (bwApplePayParams.currency || 'eur').toLowerCase();

        try {
            paymentRequest = stripe.paymentRequest({
                country: country,
                currency: currency,
                total: {
                    label: 'BlackWork Order',
                    amount: initialCents || 100
                },
                requestPayerName: true,
                requestPayerEmail: true,
                requestPayerPhone: true
            });
        } catch (err) {
            renderApplePayPlaceholder(
                'Initializing Apple Pay failed.',
                true,
                getApplePayFailureHint(err && err.message ? err.message : ''),
                'Apple Pay initialization error'
            );
            disableApplePaySelection();
            return;
        }

        runAvailabilityCheck();

        paymentRequest.on('paymentmethod', function (ev) {
            submitCheckoutWithApplePay(ev, ev.paymentMethod.id);
        });

        paymentRequest.on('cancel', function () {
            BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] Payment sheet canceled by customer.');
            if (bwApplePayParams && bwApplePayParams.adminDebug) {
                console.info('[BW Wallet Debug][apple_pay_cancel]', {
                    gateway_id: 'bw_apple_pay',
                    order_id: null,
                    order_status: 'checkout',
                    redirect_status: 'canceled',
                    is_paid: false,
                    is_cart_empty: null
                });
            }

            renderCheckoutNotice('error', 'Apple Pay payment was canceled. You can choose another payment method or try again.');
            $('#bw_apple_pay_method_id').val('');
            $('form.checkout').removeClass('processing');
            $('#place_order').removeClass('processing');
            $(document.body).trigger('update_checkout');
        });
    }

    $(document).ready(function () {
        initApplePay();

        $(document.body).off('change.bwapplepay', 'input[name="payment_method"]').on('change.bwapplepay', 'input[name="payment_method"]', function () {
            runAvailabilityCheck();
            showAppleButtonIfSelected();
        });

        $(document.body).off('updated_checkout.bwapplepay').on('updated_checkout.bwapplepay', function () {
            dedupeApplePayDom();
            showAppleButtonIfSelected();

            if (paymentRequest) {
                var updatedCents = getOrderTotalCents() || 100;
                paymentRequest.update({
                    total: {
                        label: 'BlackWork Order',
                        amount: updatedCents
                    }
                });
            }
            runAvailabilityCheck();
        });

        showAppleButtonIfSelected();
    });
})(jQuery);
