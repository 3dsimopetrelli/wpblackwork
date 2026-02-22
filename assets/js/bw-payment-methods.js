/**
 * BW Payment Methods - Shopify-style Accordion
 *
 * Handles accordion behavior and dynamic payment button text.
 * Compatible with all WooCommerce payment gateways (Stripe, PayPal, etc.).
 *
 * Uses document-level event delegation so that handlers survive the full
 * DOM replacement that WooCommerce performs on every `updated_checkout` event.
 */

(function () {
    'use strict';

    var BW_SYNC_TIMER = null;
    var BW_SYNC_DELAY_MS = 80;
    var BW_WALLET_GATEWAY_TO_SELECTORS = {
        bw_google_pay: ['#bw-google-pay-button-wrapper', '#bw-google-pay-trigger'],
        bw_apple_pay: ['#bw-apple-pay-button-wrapper', '#bw-apple-pay-trigger']
    };
    var BW_BODY_WALLET_CLASSES = [
        'bw-wallet-bw_google_pay',
        'bw-wallet-bw_apple_pay'
    ];

    function getSelectedPaymentMethod() {
        var checked = document.querySelector('input[name="payment_method"]:checked');
        return checked ? checked.value : '';
    }

    function getPlaceOrderButtons() {
        return window.jQuery ? window.jQuery('#place_order, button[name="woocommerce_checkout_place_order"]') : null;
    }

    function hidePlaceOrderButtons() {
        var $buttons = getPlaceOrderButtons();
        if (!$buttons || !$buttons.length) {
            return;
        }
        $buttons.each(function () {
            this.style.setProperty('display', 'none', 'important');
        });
    }

    function showPlaceOrderButtons() {
        var $buttons = getPlaceOrderButtons();
        if (!$buttons || !$buttons.length) {
            return;
        }
        $buttons.each(function () {
            this.style.display = '';
        });
        $buttons.show();
    }

    function hideAllWalletButtons() {
        if (!window.jQuery) {
            return;
        }
        Object.keys(BW_WALLET_GATEWAY_TO_SELECTORS).forEach(function (gatewayId) {
            BW_WALLET_GATEWAY_TO_SELECTORS[gatewayId].forEach(function (selector) {
                window.jQuery(selector).hide();
            });
        });
    }

    function showWalletButton(gatewayId) {
        if (!window.jQuery || !BW_WALLET_GATEWAY_TO_SELECTORS[gatewayId]) {
            return;
        }
        BW_WALLET_GATEWAY_TO_SELECTORS[gatewayId].forEach(function (selector) {
            window.jQuery(selector).show();
        });
    }

    function dedupeWalletDom() {
        if (!window.jQuery) {
            return;
        }

        ['#bw-google-pay-button-wrapper', '#bw-google-pay-trigger', '#bw-apple-pay-button-wrapper', '#bw-apple-pay-trigger'].forEach(function (selector) {
            var $nodes = window.jQuery(selector);
            if ($nodes.length > 1) {
                $nodes.slice(1).remove();
            }
        });
    }

    function isWalletGateway(gatewayId) {
        return Object.prototype.hasOwnProperty.call(BW_WALLET_GATEWAY_TO_SELECTORS, gatewayId);
    }

    function isWalletAvailable(gatewayId) {
        if (gatewayId === 'bw_google_pay') {
            return window.BW_GPAY_AVAILABLE === true;
        }
        if (gatewayId === 'bw_apple_pay') {
            return window.BW_APPLE_PAY_AVAILABLE === true;
        }
        return true;
    }

    function hasWalletActionButton(gatewayId) {
        if (!window.jQuery) {
            return false;
        }
        if (gatewayId === 'bw_google_pay') {
            return window.jQuery('#bw-google-pay-trigger').length > 0;
        }
        if (gatewayId === 'bw_apple_pay') {
            return window.jQuery('#bw-apple-pay-trigger').length > 0;
        }
        return false;
    }

    function updateBodyWalletClass(gatewayId, isActiveWallet) {
        document.body.classList.remove.apply(document.body.classList, BW_BODY_WALLET_CLASSES);
        if (isActiveWallet) {
            document.body.classList.add('bw-wallet-' + gatewayId);
        }
    }

    /**
     * Single source of truth for checkout action buttons visibility.
     * Keeps exactly one actionable button visible at all times.
     *
     * @param {string} reason Optional debug reason.
     */
    function bwSyncCheckoutActionButtons(reason) { // eslint-disable-line no-unused-vars
        syncAccordionState();
        dedupeWalletDom();

        var selected = getSelectedPaymentMethod();
        var isWallet = isWalletGateway(selected);
        var available = isWallet && isWalletAvailable(selected);
        var hasActionButton = isWallet && hasWalletActionButton(selected);

        hideAllWalletButtons();
        showPlaceOrderButtons();
        updateBodyWalletClass('', false);

        if (isWallet && available && hasActionButton) {
            showWalletButton(selected);
            hidePlaceOrderButtons();
            updateBodyWalletClass(selected, true);
            return;
        }

        // Auto-fallback for wallet methods that are selected but unavailable.
        if (window.jQuery && isWallet && !available) {
            if (selected === 'bw_google_pay') {
                var $fallback = window.jQuery('input[name="payment_method"]').not('[value="bw_google_pay"]').not(':disabled').first();
                if ($fallback.length) {
                    $fallback.prop('checked', true).trigger('change');
                }
            }
        }
    }

    function bwScheduleSync(reason) { // eslint-disable-line no-unused-vars
        clearTimeout(BW_SYNC_TIMER);
        BW_SYNC_TIMER = setTimeout(function () {
            bwSyncCheckoutActionButtons(reason || 'scheduled');
        }, BW_SYNC_DELAY_MS);
    }

    function initWalletMutationObserver() {
        if (!window.MutationObserver) {
            return;
        }
        var paymentNode = document.querySelector('#payment');
        if (!paymentNode) {
            return;
        }
        var observer = new MutationObserver(function () {
            bwScheduleSync('mutation');
        });
        observer.observe(paymentNode, { childList: true, subtree: true });
    }

    function normalizeCardGatewayTitles() {
        var stripeTitle = document.querySelector('.payment_method_stripe .bw-payment-method__title');
        if (stripeTitle) {
            stripeTitle.textContent = 'Credit / Debit Card';
        }
    }

    window.bwSyncCheckoutActionButtons = bwSyncCheckoutActionButtons;
    window.bwScheduleSyncCheckoutActionButtons = bwScheduleSync;

    // -------------------------------------------------------------------------
    // Accordion state helpers
    // -------------------------------------------------------------------------

    /**
     * Sync JS-managed state (is-selected class, place-order button text).
     * NOTE: the accordion open/close is now driven entirely by the CSS
     * :has(input[name="payment_method"]:checked) rule, so this function no
     * longer needs to manage is-open.  It still keeps is-selected in sync
     * (for border/background styling) and updates the button label.
     */
    function syncAccordionState() {
        var paymentContainer = document.querySelector('#payment');
        if (!paymentContainer) {
            return;
        }

        var checkedRadio = paymentContainer.querySelector('input[name="payment_method"]:checked');

        paymentContainer.querySelectorAll('.bw-payment-method').forEach(function (method) {
            var radio      = method.querySelector('input[name="payment_method"]');
            var content    = method.querySelector('.bw-payment-method__content');
            var isSelected = radio && radio.checked;

            method.classList.toggle('is-selected', isSelected);

            // Keep is-open in sync for browsers that don't support :has().
            if (content) {
                content.classList.toggle('is-open', isSelected);
            }
        });

        if (checkedRadio) {
            updatePlaceOrderButton(checkedRadio);
        }
    }

    /**
     * Animate accordion open/close in response to a user interaction.
     * @param {HTMLElement} radio - The radio button that just became checked.
     */
    function handlePaymentMethodChange(radio) {
        var paymentContainer = document.querySelector('#payment');
        if (!paymentContainer) {
            return;
        }

        paymentContainer.querySelectorAll('input[name="payment_method"]').forEach(function (input) {
            input.checked = input === radio;
        });

        syncAccordionState();

        // Notify WooCommerce.
        if (window.jQuery) {
            window.jQuery(document.body).trigger('payment_method_selected');
        }
    }

    // -------------------------------------------------------------------------
    // Place-order button text
    // -------------------------------------------------------------------------

    /**
     * Update the "Place order" button label to match the selected payment method.
     * @param {HTMLElement} radio
     */
    function updatePlaceOrderButton(radio) {
        var placeOrderBtn = document.querySelector('.bw-place-order-btn');
        if (!placeOrderBtn) {
            return;
        }

        var buttonTextSpan = placeOrderBtn.querySelector('.bw-place-order-btn__text');
        var buttonText     = radio.getAttribute('data-order_button_text') ||
                             placeOrderBtn.getAttribute('data-default-text') ||
                             'Place order';

        if (buttonTextSpan) {
            buttonTextSpan.textContent = buttonText;
        } else {
            placeOrderBtn.textContent = buttonText;
        }

        placeOrderBtn.classList.toggle('bw-place-order-btn--klarna', radio.value === 'bw_klarna');
        placeOrderBtn.classList.toggle('bw-place-order-btn--apple-pay', radio.value === 'bw_apple_pay');

        placeOrderBtn.setAttribute('value', buttonText);

        // Brief fade for visual feedback.
        if (buttonTextSpan) {
            buttonTextSpan.style.opacity = '0';
            setTimeout(function () {
                buttonTextSpan.style.opacity = '1';
            }, 50);
        }
    }

    // -------------------------------------------------------------------------
    // Form submission loading state
    // -------------------------------------------------------------------------

    function handleFormSubmission() {
        var checkoutForm = document.querySelector('form.checkout');
        if (!checkoutForm) {
            return;
        }

        checkoutForm.addEventListener('submit', function () {
            var placeOrderBtn = document.querySelector('.bw-place-order-btn');
            if (placeOrderBtn && !placeOrderBtn.classList.contains('processing')) {
                placeOrderBtn.classList.add('processing');
            }
        });
    }

    function removeLoadingState() {
        var placeOrderBtn = document.querySelector('.bw-place-order-btn');
        if (placeOrderBtn) {
            placeOrderBtn.classList.remove('processing');
        }
    }

    // -------------------------------------------------------------------------
    // Document-level event delegation (survives WooCommerce DOM replacement)
    // -------------------------------------------------------------------------

    // Payment method radio change.
    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target.type === 'radio' && target.name === 'payment_method') {
            if (target.disabled || target.getAttribute('data-bw-unavailable') === '1') {
                return;
            }
            handlePaymentMethodChange(target);
        }
    });

    // Entire payment-method header is clickable (except direct radio click).
    document.addEventListener('click', function (event) {
        var header = event.target.closest('.bw-payment-method__header');
        if (!header) {
            return;
        }

        // Let native radio click behavior pass through.
        if (event.target.type === 'radio') {
            return;
        }

        var radio = header.querySelector('input[type="radio"]');
        if (radio) {
            var isUnavailable = radio.disabled || radio.getAttribute('data-bw-unavailable') === '1';
            if (isUnavailable) {
                event.preventDefault();
                return;
            }
            event.preventDefault();
            if (!radio.checked) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                // Keep state synced even when clicking an already selected row.
                handlePaymentMethodChange(radio);
            }
        }
    });

    // Keyboard navigation (accessibility).
    document.addEventListener('keydown', function (event) {
        // Enter / Space on the label triggers the radio.
        if (event.key === 'Enter' || event.key === ' ') {
            var target = event.target;
            if (target.classList.contains('bw-payment-method__label')) {
                event.preventDefault();
                var radio = target.previousElementSibling;
                if (radio && radio.type === 'radio') {
                    var isUnavailable = radio.disabled || radio.getAttribute('data-bw-unavailable') === '1';
                    if (isUnavailable) {
                        return;
                    }
                    radio.checked = true;
                    handlePaymentMethodChange(radio);
                }
            }
        }

        // Arrow keys cycle between payment methods.
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            var target = event.target;
            if (target.type === 'radio' && target.name === 'payment_method') {
                event.preventDefault();
                var allRadios    = Array.from(document.querySelectorAll('input[name="payment_method"]')).filter(function (radio) {
                    return !radio.disabled && radio.getAttribute('data-bw-unavailable') !== '1';
                });
                var currentIndex = allRadios.indexOf(target);
                var nextIndex;

                if (event.key === 'ArrowDown') {
                    nextIndex = (currentIndex + 1) % allRadios.length;
                } else {
                    nextIndex = (currentIndex - 1 + allRadios.length) % allRadios.length;
                }

                var nextRadio = allRadios[nextIndex];
                if (nextRadio) {
                    nextRadio.focus();
                    nextRadio.checked = true;
                    handlePaymentMethodChange(nextRadio);
                }
            }
        }
    });

    // -------------------------------------------------------------------------
    // Payment icons tooltip
    // -------------------------------------------------------------------------

    function initPaymentIconsTooltips() {
        var moreIcons = document.querySelectorAll('.bw-payment-icon--more');

        moreIcons.forEach(function (moreIcon) {
            var tooltip     = moreIcon.querySelector('.bw-payment-icon__tooltip');
            var hideTimeout;

            if (!tooltip) {
                return;
            }

            moreIcon.addEventListener('mouseenter', function () {
                clearTimeout(hideTimeout);
                tooltip.style.display = 'flex';
            });

            tooltip.addEventListener('mouseenter', function () {
                clearTimeout(hideTimeout);
                tooltip.style.display = 'flex';
            });

            moreIcon.addEventListener('mouseleave', function (event) {
                if (!tooltip.contains(event.relatedTarget)) {
                    hideTimeout = setTimeout(function () {
                        tooltip.style.display = 'none';
                    }, 100);
                }
            });

            tooltip.addEventListener('mouseleave', function () {
                hideTimeout = setTimeout(function () {
                    tooltip.style.display = 'none';
                }, 100);
            });

            document.addEventListener('click', function (event) {
                if (!moreIcon.contains(event.target)) {
                    tooltip.style.display = 'none';
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    function init() {
        if (!(window.CSS && CSS.supports && CSS.supports('selector(:has(*))'))) {
            document.body.classList.add('bw-no-has-support');
        }
        normalizeCardGatewayTitles();
        syncAccordionState();
        handleFormSubmission();
        initPaymentIconsTooltips();
        bwScheduleSync('init');
        initWalletMutationObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-sync after WooCommerce replaces the #payment DOM fragment.
    // The DOM is fully ready when `updated_checkout` fires — no delay needed.
    if (window.jQuery) {
        window.jQuery(function ($) {
            $(document.body).off('updated_checkout.bwWalletUi').on('updated_checkout.bwWalletUi', function () {
                normalizeCardGatewayTitles();
                syncAccordionState();
                initPaymentIconsTooltips();
                bwScheduleSync('updated_checkout');
            });

            $(document.body).off('payment_method_selected.bwWalletUi').on('payment_method_selected.bwWalletUi', function () {
                normalizeCardGatewayTitles();
                syncAccordionState();
                bwScheduleSync('payment_method_selected');
            });

            $(document).off('change.bwWalletUi', 'input[name="payment_method"]').on('change.bwWalletUi', 'input[name="payment_method"]', function () {
                normalizeCardGatewayTitles();
                syncAccordionState();
                bwScheduleSync('payment_method_change');
            });

            $(document.body).off('checkout_error.bwWalletUi').on('checkout_error.bwWalletUi', function () {
                removeLoadingState();
                bwScheduleSync('checkout_error');
            });
        });
    }

})();
