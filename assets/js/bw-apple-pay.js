(function ($) {
    'use strict';

    var BW_APPLE_PAY_DEBUG = window.BW_APPLE_PAY_DEBUG === true;
    var BW_APPLE_PAY_NS = '.bwApplePay';

    var STATE = {
        IDLE: 'idle',
        METHOD_SELECTED: 'method_selected',
        NATIVE_AVAILABLE: 'native_available',
        NATIVE_UNAVAILABLE_HELPER: 'native_unavailable_helper',
        PROCESSING: 'processing',
        ERROR: 'error'
    };

    var app = {
        state: STATE.IDLE,
        paymentRequest: null,
        isCheckingAvailability: false,
        nativeAvailable: false,
        helperEnabled: true,
        initialized: false
    };

    window.BW_APPLE_PAY_AVAILABLE = false;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function isAppleMethodSelected() {
        return $('input[name="payment_method"]:checked').val() === 'bw_apple_pay';
    }

    function getApplePayFailureHint(extraReason) {
        var hint = 'Possible causes: Apple Pay is not supported on this browser/device, no eligible card is configured in Wallet, or the domain is not verified in Stripe.';
        if (extraReason) {
            hint += ' Technical detail: ' + extraReason;
        }
        return hint;
    }

    function parseAmount(text) {
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
        if (bwApplePayParams && typeof bwApplePayParams.orderTotalCents === 'number' && bwApplePayParams.orderTotalCents > 0) {
            return bwApplePayParams.orderTotalCents;
        }

        var $el = $('.order-total .woocommerce-Price-amount bdi').first();
        if (!$el.length) {
            $el = $('.woocommerce-Price-amount bdi').last();
        }

        return Math.round(parseAmount($el.text()) * 100);
    }

    function getExpressCheckoutTarget() {
        var selectors = [
            '.bw-checkout-express__gateway-grid',
            '.bw-checkout-express__title',
            '#wc-stripe-express-checkout-element',
            '#wc-stripe-payment-request-wrapper',
            '.wc-stripe-express-checkout',
            '.wc-stripe-payment-request-buttons',
            '#wc-stripe-express-checkout-element-wrapper',
            '#wcpay-express-checkout-element',
            '.wcpay-express-checkout-wrapper',
            '#wc-stripe-payment-request-button-separator',
            '#wc-stripe-express-checkout-button-separator',
            '#wcpay-express-checkout-button-separator',
            '.wc-stripe-ece-button-separator'
        ];

        for (var i = 0; i < selectors.length; i += 1) {
            var el = document.querySelector(selectors[i]);
            if (!el) {
                continue;
            }

            var isVisible = true;
            if (window.getComputedStyle) {
                var style = window.getComputedStyle(el);
                isVisible = style.display !== 'none' && style.visibility !== 'hidden';
            }

            if (isVisible) {
                return el;
            }
        }

        return null;
    }

    function scrollToExpressCheckout(ev) {
        if (ev && typeof ev.preventDefault === 'function') {
            ev.preventDefault();
        }

        var target = getExpressCheckoutTarget();
        if (!target) {
            window.bwCheckout.renderCheckoutNotice('info', 'Express Checkout area was not detected. Please use the Apple Pay/Google Pay buttons at the top of checkout.');
            return;
        }

        var offsetTop = 140;
        var top = Math.max(0, Math.round(target.getBoundingClientRect().top + window.pageYOffset - offsetTop));

        try {
            window.scrollTo({ top: top, behavior: 'smooth' });
        } catch (error) {
            window.scrollTo(0, top);
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.animate) {
            window.jQuery('html, body').stop(true).animate({ scrollTop: top }, 260);
        }

        target.classList.add('bw-express-checkout-highlight');
        setTimeout(function () {
            target.classList.remove('bw-express-checkout-highlight');
        }, 1500);
    }

    function scheduleGlobalWalletSync(reason) {
        if (typeof window.bwScheduleSyncCheckoutActionButtons === 'function') {
            window.bwScheduleSyncCheckoutActionButtons(reason || 'apple_pay');
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

    function ensureButton() {
        var container = document.getElementById('bw-apple-pay-button');
        if (!container) {
            return;
        }

        var button = document.getElementById('bw-apple-pay-trigger');
        if (button) {
            return;
        }

        button = document.createElement('button');
        button.type = 'button';
        button.id = 'bw-apple-pay-trigger';
        button.className = 'bw-custom-applepay-btn';
        button.textContent = 'Pay with \uf8ff Apple Pay';

        container.innerHTML = '';
        container.appendChild(button);
    }

    function setButtonLoading(isLoading) {
        var button = document.getElementById('bw-apple-pay-trigger');
        if (!button) {
            return;
        }

        if (isLoading) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Processing Apple Pay...';
            return;
        }

        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.textContent = 'Pay with \uf8ff Apple Pay';
    }

    function setPlaceOrderHidden(shouldHide) {
        document.body.classList.toggle('bw-apple-pay-hide-place-order', shouldHide);
    }

    function setAppleInfoVisibility(shouldShow) {
        $('.payment_method_bw_apple_pay .bw-apple-pay-info').toggle(!!shouldShow);
    }

    function renderPlaceholder(title, lines, isError) {
        var placeholder = document.getElementById('bw-apple-pay-accordion-placeholder');
        if (!placeholder) {
            return;
        }

        var html = '';
        html += '<div class="bw-apple-pay-init-info" role="status" aria-live="polite">';
        if (title && String(title).trim() !== '') {
            html += '<p class="bw-apple-pay-init-info__title">' + escapeHtml(title) + '</p>';
        }

        for (var i = 0; i < lines.length; i += 1) {
            var className = i === 0 ? 'bw-apple-pay-init-info__reason' : 'bw-apple-pay-init-info__check';
            if (isError && i === 0) {
                className += ' bw-apple-pay-init-info__reason--error';
            }
            html += '<p class="' + className + '">' + escapeHtml(lines[i]) + '</p>';
        }

        html += '</div>';
        placeholder.innerHTML = html;
    }

    function clearPlaceholder() {
        var placeholder = document.getElementById('bw-apple-pay-accordion-placeholder');
        if (!placeholder) {
            return;
        }
        placeholder.innerHTML = '';
    }

    function applyUiState(reason) {
        var isSelected = isAppleMethodSelected();
        var showWalletButton = isSelected;
        var hidePlaceOrder = false;
        var hasPrimaryAction = false;

        setButtonLoading(app.state === STATE.PROCESSING);
        setAppleInfoVisibility(app.state === STATE.NATIVE_AVAILABLE);

        if (!isSelected) {
            app.state = STATE.IDLE;
        }

        switch (app.state) {
            case STATE.IDLE:
                showWalletButton = false;
                hidePlaceOrder = false;
                renderPlaceholder('Apple Pay', [
                    'Select Apple Pay to continue.'
                ], false);
                break;

            case STATE.METHOD_SELECTED:
                hidePlaceOrder = false;
                renderPlaceholder('Apple Pay initialization', [
                    'Checking Apple Pay availability for your browser and device...'
                ], false);
                break;

            case STATE.NATIVE_AVAILABLE:
                hidePlaceOrder = true;
                hasPrimaryAction = true;
                clearPlaceholder();
                break;

            case STATE.NATIVE_UNAVAILABLE_HELPER:
                if (app.helperEnabled) {
                    hidePlaceOrder = true;
                    hasPrimaryAction = true;
                    renderPlaceholder('', [
                        'Apple Pay is available on Safari with Wallet configured.',
                        'Use the Apple Pay button below to jump to Express Checkout at the top of the page.'
                    ], false);
                } else {
                    hidePlaceOrder = false;
                    renderPlaceholder('Apple Pay unavailable', [
                        'Apple Pay is only available on Safari with Wallet configured for this checkout.',
                        'Use Google Pay, Credit / Debit Card, or another available method.'
                    ], true);
                }
                break;

            case STATE.PROCESSING:
                hidePlaceOrder = true;
                hasPrimaryAction = true;
                renderPlaceholder('Apple Pay', [
                    'Processing payment. Please wait...'
                ], false);
                break;

            case STATE.ERROR:
            default:
                hidePlaceOrder = false;
                renderPlaceholder('Apple Pay unavailable', [
                    'Apple Pay is only available on Safari with Wallet configured for this checkout.',
                    'Use Google Pay, Credit / Debit Card, or another available method.'
                ], true);
                break;
        }

        // In helper mode the Apple button is still actionable, so we expose it
        // as available to the shared wallet-action synchronizer.
        window.BW_APPLE_PAY_AVAILABLE = isSelected && (app.nativeAvailable || (app.helperEnabled && app.state === STATE.NATIVE_UNAVAILABLE_HELPER));

        $('#bw-apple-pay-button-wrapper').toggle(showWalletButton);
        setPlaceOrderHidden(hidePlaceOrder);
        dedupeApplePayDom();
        scheduleGlobalWalletSync(reason || 'apple_state');
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

        window.bwCheckout.renderCheckoutNotice('error', 'Please fill in required fields: ' + invalidLabels.join(', ') + '.');
        return false;
    }

    function disableApplePaySelection() {
        var $appleInput = $('input[name="payment_method"][value="bw_apple_pay"]');
        if (!$appleInput.length) {
            return;
        }

        $appleInput.attr('data-bw-unavailable', '1');
        $appleInput.closest('.bw-payment-method').attr('data-bw-unavailable', '1');
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
        var hidePlaceOrderForUnavailableApple = isAppleMethod && applePayState === 'unavailable' && enableExpressFallback;

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

        document.body.classList.toggle('bw-apple-pay-hide-place-order', hidePlaceOrderForUnavailableApple);
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
            renderApplePayUnavailableFallback();
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
            BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] canMakePayment:', result);
            applePayAvailable = !!(result && result.applePay === true);
            window.BW_APPLE_PAY_AVAILABLE = applePayAvailable === true;
            applePayState = applePayAvailable ? 'available' : 'unavailable';

            if (!applePayAvailable) {
                disableApplePaySelection();
                renderApplePayUnavailableFallback();
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
            BW_APPLE_PAY_DEBUG && console.warn('[BW Apple Pay] unavailable reason:', error && error.message ? error.message : 'canMakePayment failed unexpectedly');
            renderApplePayUnavailableFallback();
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

        $hidden.val(methodId || '');
    }

    function submitCheckoutWithApplePay(ev, methodId) {
        var $form = $('form.checkout');

        setHiddenMethodId(methodId);
        app.state = STATE.PROCESSING;
        applyUiState('apple_submit_start');

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
                    return;
                }

                ev.complete('fail');
                app.state = STATE.ERROR;

                if (response && response.messages) {
                    var $notices = $('.woocommerce-notices-wrapper').first();
                    if ($notices.length) {
                        $notices.html(response.messages);
                    }
                    $('html, body').animate({ scrollTop: 0 }, 300);
                }

                applyUiState('apple_submit_error');
            },
            error: function () {
                ev.complete('fail');
                app.state = STATE.ERROR;
                window.bwCheckout.renderCheckoutNotice('error', 'Apple Pay request failed. Please try again or choose another payment method.');
                applyUiState('apple_submit_error_request');
            }
        });
    }

    function runAvailabilityCheck() {
        if (!app.paymentRequest || app.isCheckingAvailability) {
            return;
        }

        app.isCheckingAvailability = true;
        app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
        app.nativeAvailable = false;
        window.BW_APPLE_PAY_AVAILABLE = false;
        applyUiState('apple_check_start');

        app.paymentRequest.canMakePayment().then(function (result) {
            BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] canMakePayment:', result);
            app.nativeAvailable = !!(result && result.applePay === true);

            if (app.nativeAvailable) {
                app.state = STATE.NATIVE_AVAILABLE;
                app.isCheckingAvailability = false;
                applyUiState('apple_native_available');
                return;
            }

            app.state = app.helperEnabled ? STATE.NATIVE_UNAVAILABLE_HELPER : STATE.ERROR;
            app.isCheckingAvailability = false;
            applyUiState(app.helperEnabled ? 'apple_unavailable_helper' : 'apple_unavailable_error');
        }).catch(function (error) {
            app.nativeAvailable = false;
            app.state = app.helperEnabled ? STATE.NATIVE_UNAVAILABLE_HELPER : STATE.ERROR;
            app.isCheckingAvailability = false;

            if (BW_APPLE_PAY_DEBUG) {
                console.warn('[BW Apple Pay] availability check failed:', error && error.message ? error.message : 'unknown');
            }

            applyUiState('apple_check_error');
        });
    }

    function handlePrimaryButtonClick(ev) {
        ev.preventDefault();

        if (!isAppleMethodSelected()) {
            return;
        }

        if (app.state === STATE.NATIVE_AVAILABLE) {
            if (!validateRequiredFields()) {
                return;
            }

            if (!app.paymentRequest) {
                app.state = STATE.ERROR;
                applyUiState('apple_missing_payment_request');
                return;
            }

            var openResult = app.paymentRequest.show();
            if (openResult && typeof openResult.catch === 'function') {
                openResult.catch(function (error) {
                    console.warn('[BW Apple Pay] native show() rejected:', error && error.message ? error.message : error);
                    app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
                    applyUiState('apple_native_show_rejected');
                });
            }
            return;
        }

        if (app.state === STATE.NATIVE_UNAVAILABLE_HELPER && app.helperEnabled) {
            scrollToExpressCheckout(ev);
            return;
        }

        window.bwCheckout.renderCheckoutNotice('error', 'Apple Pay is not available in this environment. Choose another payment method.');
    }

    function initPaymentRequest() {
        var initialCents = getOrderTotalCents();
        var country = (bwApplePayParams.country || 'IT').toUpperCase();
        var currency = (bwApplePayParams.currency || 'eur').toLowerCase();

        try {
            app.paymentRequest = stripe.paymentRequest({
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
            app.state = STATE.ERROR;
            renderPlaceholder('Apple Pay initialization error', [
                'Initializing Apple Pay failed.',
                getApplePayFailureHint(err && err.message ? err.message : '')
            ], true);
            applyUiState('apple_init_payment_request_error');
            return false;
        }

        app.paymentRequest.on('paymentmethod', function (ev) {
            submitCheckoutWithApplePay(ev, ev.paymentMethod.id);
        });

        app.paymentRequest.on('cancel', function () {
            setHiddenMethodId('');
            app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
            window.bwCheckout.renderCheckoutNotice('error', 'Apple Pay payment was canceled. You can choose another payment method or try again.');
            window.bwCheckout.removeLoadingState();
            $(document.body).trigger('update_checkout');
        });

        return true;
    }

    function bindEvents() {
        $(document)
            .off('click' + BW_APPLE_PAY_NS, '#bw-apple-pay-trigger')
            .on('click' + BW_APPLE_PAY_NS, '#bw-apple-pay-trigger', handlePrimaryButtonClick);

        $(document.body)
            .off('change' + BW_APPLE_PAY_NS, 'input[name="payment_method"]')
            .on('change' + BW_APPLE_PAY_NS, 'input[name="payment_method"]', function () {
                app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
                applyUiState('apple_method_changed');
                runAvailabilityCheck();
            });

        $(document.body)
            .off('updated_checkout' + BW_APPLE_PAY_NS)
            .on('updated_checkout' + BW_APPLE_PAY_NS, function () {
                dedupeApplePayDom();
                ensureButton();

                if (app.paymentRequest) {
                    var updatedCents = getOrderTotalCents() || 100;
                    app.paymentRequest.update({
                        total: {
                            label: 'BlackWork Order',
                            amount: updatedCents
                        }
                    });
                }

                app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
                applyUiState('apple_updated_checkout');
                runAvailabilityCheck();
            });
    }

    if (typeof bwApplePayParams === 'undefined' || typeof Stripe === 'undefined') {
        renderPlaceholder('Apple Pay initialization error', [
            'Initializing Apple Pay failed.',
            getApplePayFailureHint('Stripe.js is missing or not loaded.')
        ], true);
        return;
    }

    if (!bwApplePayParams.publishableKey) {
        renderPlaceholder('Apple Pay initialization error', [
            'Initializing Apple Pay failed.',
            getApplePayFailureHint('Missing live publishable key.')
        ], true);
        return;
    }

    var stripe = Stripe(bwApplePayParams.publishableKey);

    $(document).ready(function () {
        if (app.initialized) {
            return;
        }

        app.initialized = true;
        app.helperEnabled = !(bwApplePayParams && bwApplePayParams.enableExpressFallback === false);

        ensureButton();
        bindEvents();

        app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
        applyUiState('apple_ready');

        if (!initPaymentRequest()) {
            return;
        }

        runAvailabilityCheck();
    });
})(jQuery);
