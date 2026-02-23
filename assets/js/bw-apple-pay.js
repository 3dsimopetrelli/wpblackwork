(function ($) {
    'use strict';

    var BW_APPLE_PAY_DEBUG = window.BW_APPLE_PAY_DEBUG === true;
    var BW_APPLE_PAY_NS = '.bwApplePay';
    var FALLBACK_MODE_HELPER = 'helper_scroll';
    var FALLBACK_MODE_INLINE = 'inline_express';

    var STATE = {
        IDLE: 'idle',
        METHOD_SELECTED: 'method_selected',
        NATIVE_AVAILABLE: 'native_available',
        NATIVE_UNAVAILABLE_HELPER: 'native_unavailable_helper',
        NATIVE_UNAVAILABLE_INLINE_POSSIBLE: 'native_unavailable_inline_possible',
        PROCESSING: 'processing',
        ERROR: 'error'
    };

    var app = {
        state: STATE.IDLE,
        paymentRequest: null,
        isCheckingAvailability: false,
        nativeAvailable: false,
        canUseInlineExpress: false,
        fallbackMode: FALLBACK_MODE_HELPER,
        lastInlineCapabilityReason: '',
        lastCanMakePaymentResult: null,
        initialized: false
    };

    window.BW_APPLE_PAY_AVAILABLE = false;
    window.BW_APPLE_PAY_ACTION_AVAILABLE = false;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getFallbackMode() {
        var rawMode = bwApplePayParams && typeof bwApplePayParams.nonSafariFallbackMode === 'string'
            ? bwApplePayParams.nonSafariFallbackMode
            : '';

        if (rawMode !== FALLBACK_MODE_HELPER && rawMode !== FALLBACK_MODE_INLINE) {
            return FALLBACK_MODE_HELPER;
        }

        return rawMode;
    }

    function isAppleMethodSelected() {
        return $('input[name="payment_method"]:checked').val() === 'bw_apple_pay';
    }

    function isSafariBrowser() {
        var ua = window.navigator && window.navigator.userAgent ? window.navigator.userAgent : '';
        var isSafari = /Safari/i.test(ua) && !/Chrome|CriOS|Chromium|Edg|OPR|Firefox|FxiOS|Android/i.test(ua);
        return isSafari;
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

    function renderCheckoutNotice(type, message) {
        var klass = type === 'error' ? 'woocommerce-error' : 'woocommerce-info';
        var $notices = $('.woocommerce-notices-wrapper').first();

        if ($notices.length) {
            $notices.html('<ul class="' + klass + '" role="alert"><li>' + escapeHtml(message) + '</li></ul>');
        }

        $('html, body').animate({ scrollTop: 0 }, 250);
    }

    function scrollToExpressCheckout(ev) {
        if (ev && typeof ev.preventDefault === 'function') {
            ev.preventDefault();
        }

        var target = getExpressCheckoutTarget();
        if (!target) {
            // Keep checkout stable: no hard error when target cannot be resolved.
            renderCheckoutNotice('info', 'Express Checkout area was not detected. Please use the Apple Pay/Google Pay buttons at the top of checkout.');
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
        html += '<p class="bw-apple-pay-init-info__title">' + escapeHtml(title) + '</p>';

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

    function renderInlineExpressPlaceOrderHint() {
        var placeholder = document.getElementById('bw-apple-pay-accordion-placeholder');
        if (!placeholder) {
            return;
        }

        placeholder.innerHTML = '' +
            '<div class="bw-apple-pay-init-info" role="status" aria-live="polite">' +
                '<p class="bw-apple-pay-init-info__reason">Use the Place order button to activate Apple Express Checkout.</p>' +
            '</div>';
    }

    function clearPlaceholder() {
        var placeholder = document.getElementById('bw-apple-pay-accordion-placeholder');
        if (!placeholder) {
            return;
        }
        placeholder.innerHTML = '';
    }

    function setInlineNeutralHint() {
        renderInlineExpressPlaceOrderHint();
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
                // Keep native Apple Pay panel visually aligned with other methods:
                // no extra title block, only the standard inline info text.
                clearPlaceholder();
                break;

            case STATE.NATIVE_UNAVAILABLE_HELPER:
                hidePlaceOrder = true;
                hasPrimaryAction = true;
                renderPlaceholder('Apple Pay unavailable', [
                    'Apple Pay is available on Safari with Wallet configured.',
                    'Use the Apple Pay button below to jump to Express Checkout at the top of the page.'
                ], false);
                break;

            case STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE:
                hidePlaceOrder = true;
                hasPrimaryAction = true;
                setInlineNeutralHint();
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
                if (app.fallbackMode === FALLBACK_MODE_INLINE) {
                    hidePlaceOrder = true;
                    hasPrimaryAction = true;
                    setInlineNeutralHint();
                } else {
                    renderPlaceholder('Apple Pay unavailable', [
                        'Apple Pay is only available on Safari with Wallet configured for this checkout.',
                        'Use Google Pay, Credit / Debit Card, or another available method.'
                    ], true);
                }
                break;
        }

        window.BW_APPLE_PAY_ACTION_AVAILABLE = isSelected && hasPrimaryAction;
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

        renderCheckoutNotice('error', 'Please fill in required fields: ' + invalidLabels.join(', ') + '.');
        return false;
    }

    function setHiddenMethodId(methodId) {
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
                renderCheckoutNotice('error', 'Apple Pay request failed. Please try again or choose another payment method.');
                applyUiState('apple_submit_error_request');
            }
        });
    }

    function detectInlineExpressCapability(canMakePaymentResult) {
        var capability = {
            feasible: false,
            reason: ''
        };

        if (!canMakePaymentResult || typeof canMakePaymentResult !== 'object') {
            capability.reason = 'No wallet capability object returned by Stripe canMakePayment().';
            return capability;
        }

        if (canMakePaymentResult.applePay === true) {
            capability.feasible = true;
            return capability;
        }

        capability.reason = 'Inline Apple Pay cannot be executed in this browser context. Stripe Express buttons are rendered in cross-origin iframes and cannot be triggered programmatically.';
        return capability;
    }

    function hasAppleCapability(result) {
        return !!(result && typeof result === 'object' && result.applePay === true);
    }

    function runAvailabilityCheck() {
        if (!app.paymentRequest || app.isCheckingAvailability) {
            return;
        }

        app.isCheckingAvailability = true;
        app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
        app.nativeAvailable = false;
        app.canUseInlineExpress = false;
        window.BW_APPLE_PAY_AVAILABLE = false;
        applyUiState('apple_check_start');

        app.paymentRequest.canMakePayment().then(function (result) {
            BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] canMakePayment:', result);
            app.lastCanMakePaymentResult = result || null;
            app.nativeAvailable = hasAppleCapability(result);
            window.BW_APPLE_PAY_AVAILABLE = app.nativeAvailable;

            if (app.nativeAvailable) {
                app.state = STATE.NATIVE_AVAILABLE;
                app.isCheckingAvailability = false;
                applyUiState('apple_native_available');
                return;
            }

            if (app.fallbackMode === FALLBACK_MODE_HELPER) {
                app.state = STATE.NATIVE_UNAVAILABLE_HELPER;
                app.isCheckingAvailability = false;
                applyUiState('apple_unavailable_helper');
                return;
            }

            var inlineCapability = detectInlineExpressCapability(result);
            app.canUseInlineExpress = inlineCapability.feasible;
            app.lastInlineCapabilityReason = inlineCapability.reason || '';
            app.state = STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE;
            app.isCheckingAvailability = false;
            if (!app.canUseInlineExpress) {
                console.warn('[BW Apple Pay] inline_express fallback limited:', app.lastInlineCapabilityReason);
            }
            applyUiState('apple_unavailable_inline');
        }).catch(function (error) {
            app.nativeAvailable = false;
            window.BW_APPLE_PAY_AVAILABLE = false;
            app.state = app.fallbackMode === FALLBACK_MODE_HELPER ? STATE.NATIVE_UNAVAILABLE_HELPER : STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE;
            app.isCheckingAvailability = false;

            if (BW_APPLE_PAY_DEBUG) {
                console.warn('[BW Apple Pay] availability check failed:', error && error.message ? error.message : 'unknown');
            }

            if (app.fallbackMode === FALLBACK_MODE_INLINE) {
                console.warn('[BW Apple Pay] inline_express fallback limited: canMakePayment check failed.');
            }

            applyUiState('apple_check_error');
        });
    }

    function tryOpenInlineAppleExpress() {
        if (!validateRequiredFields()) {
            return false;
        }

        if (!app.paymentRequest) {
            return false;
        }

        try {
            app.paymentRequest.show();
            return true;
        } catch (error) {
            console.warn('[BW Apple Pay] inline_express show() failed:', error && error.message ? error.message : error);
            setInlineNeutralHint();
            return false;
        }
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

            app.paymentRequest.show();
            return;
        }

        if (app.state === STATE.NATIVE_UNAVAILABLE_HELPER) {
            scrollToExpressCheckout(ev);
            return;
        }

        if (app.state === STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE) {
            if (tryOpenInlineAppleExpress()) {
                return;
            }
            app.state = STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE;
            applyUiState('apple_inline_show_failed');
            return;
        }

        if (app.fallbackMode === FALLBACK_MODE_INLINE && app.state === STATE.ERROR) {
            if (tryOpenInlineAppleExpress()) {
                return;
            }
            app.state = STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE;
            applyUiState('apple_inline_error_retry');
            return;
        }

        renderCheckoutNotice('error', 'Apple Pay is not available in this environment. Choose another payment method.');
    }

    function handlePlaceOrderClick(ev) {
        var isPlaceOrderButton = ev.target && (
            ev.target.id === 'place_order' ||
            ev.target.name === 'woocommerce_checkout_place_order'
        );
        if (!isPlaceOrderButton) {
            return;
        }

        if (!isAppleMethodSelected()) {
            return;
        }

        if (app.fallbackMode !== FALLBACK_MODE_INLINE) {
            return;
        }

        if (app.state !== STATE.ERROR) {
            return;
        }

        // Prevent Woo submit (which would fail with "Apple Pay session missing")
        // and route inline fallback to Apple Express trigger.
        ev.preventDefault();
        ev.stopPropagation();
        if (app.fallbackMode === FALLBACK_MODE_INLINE) {
            if (tryOpenInlineAppleExpress()) {
                return;
            }
            app.state = STATE.NATIVE_UNAVAILABLE_INLINE_POSSIBLE;
            applyUiState('apple_place_order_inline_unavailable');
            return;
        }

        scrollToExpressCheckout(ev);
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
                requestPayerPhone: true,
                // Apple gateway must never trigger non-Apple wallets.
                disableWallets: ['googlePay', 'link', 'browserCard']
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
            renderCheckoutNotice('error', 'Apple Pay payment was canceled. You can choose another payment method or try again.');
            $('form.checkout').removeClass('processing');
            $('#place_order').removeClass('processing');
            $(document.body).trigger('update_checkout');
        });

        return true;
    }

    function bindEvents() {
        $(document)
            .off('click' + BW_APPLE_PAY_NS, '#bw-apple-pay-trigger')
            .on('click' + BW_APPLE_PAY_NS, '#bw-apple-pay-trigger', handlePrimaryButtonClick);

        $(document)
            .off('click' + BW_APPLE_PAY_NS, '#place_order, button[name="woocommerce_checkout_place_order"]')
            .on('click' + BW_APPLE_PAY_NS, '#place_order, button[name="woocommerce_checkout_place_order"]', handlePlaceOrderClick);

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
        app.fallbackMode = getFallbackMode();

        ensureButton();
        bindEvents();

        app.state = isAppleMethodSelected() ? STATE.METHOD_SELECTED : STATE.IDLE;
        applyUiState('apple_ready');

        if (!initPaymentRequest()) {
            return;
        }

        // Keep Safari detection available for debugging and future extension without changing behavior.
        BW_APPLE_PAY_DEBUG && console.log('[BW Apple Pay] Browser Safari:', isSafariBrowser());

        runAvailabilityCheck();
    });
})(jQuery);
