(function ($) {
    'use strict';

    function renderApplePayPlaceholder(message, isError) {
        var render = function () {
            var placeholder = document.getElementById('bw-apple-pay-accordion-placeholder');
            if (!placeholder) {
                return;
            }
            var klass = isError ? ' bw-apple-pay-unavailable__text--error' : '';
            placeholder.innerHTML = '' +
                '<div class="bw-apple-pay-unavailable" role="status" aria-live="polite">' +
                    '<p class="bw-apple-pay-unavailable__text' + klass + '">' + message + '</p>' +
                '</div>' +
            '';
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', render);
        } else {
            render();
        }
    }

    var BW_APPLE_PAY_DEBUG = window.BW_APPLE_PAY_DEBUG === true;

    if (typeof bwApplePayParams === 'undefined' || typeof Stripe === 'undefined') {
        renderApplePayPlaceholder('Please refresh the page and try again, or choose another payment method.', true);
        return;
    }

    if (!bwApplePayParams.publishableKey) {
        renderApplePayPlaceholder('Apple Pay is not configured. Please choose another payment method.', true);
        return;
    }

    var stripe = Stripe(bwApplePayParams.publishableKey);
    var paymentRequest = null;
    var applePayAvailable = false;
    var applePayState = 'checking'; // checking | available | unavailable
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

    function disableApplePaySelection(withUnavailableMessage) {
        var $appleInput = $('input[name="payment_method"][value="bw_apple_pay"]');
        if (!$appleInput.length) {
            return;
        }

        // Keep the method selectable so the whole accordion row remains
        // consistently clickable. We only mark it unavailable.
        $appleInput.prop('disabled', false).removeAttr('aria-disabled');
        $appleInput.attr('data-bw-unavailable', '1');
        $appleInput.closest('.bw-payment-method').attr('data-bw-unavailable', '1');

        if (withUnavailableMessage) {
            renderApplePayPlaceholder('Apple Pay is not available on this device/browser. Choose another payment method.', true);
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

        scheduleGlobalWalletSync('apple_ui');
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
        applePayState = 'checking';
        applePayAvailable = false;

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
            renderApplePayPlaceholder('Unable to initialize Apple Pay on this device/browser.', true);
            disableApplePaySelection(false);
            return;
        }

        paymentRequest.canMakePayment().then(function (result) {
            BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] canMakePayment:', result);
            applePayAvailable = !!(result && result.applePay === true);
            window.BW_APPLE_PAY_AVAILABLE = applePayAvailable === true;
            applePayState = applePayAvailable ? 'available' : 'unavailable';

            if (!applePayAvailable) {
                disableApplePaySelection(true);
                showAppleButtonIfSelected();
                return;
            }

            $('input[name="payment_method"][value="bw_apple_pay"]')
                .prop('disabled', false)
                .removeAttr('aria-disabled')
                .removeAttr('data-bw-unavailable')
                .closest('.bw-payment-method')
                .removeAttr('data-bw-unavailable');
            renderApplePayPlaceholder('You\'ll be redirected to Apple Pay to complete your purchase.', false);
            ensureButton();
            showAppleButtonIfSelected();
        }).catch(function () {
            applePayAvailable = false;
            window.BW_APPLE_PAY_AVAILABLE = false;
            applePayState = 'unavailable';
            disableApplePaySelection(true);
            showAppleButtonIfSelected();
        });

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
        });

        showAppleButtonIfSelected();
    });
})(jQuery);
