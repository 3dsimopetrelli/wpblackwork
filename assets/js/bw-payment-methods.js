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

    if (window.__BW_PAYMENT_METHODS_BOOTSTRAPPED__) {
        return;
    }
    window.__BW_PAYMENT_METHODS_BOOTSTRAPPED__ = true;

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
    var BW_SELECTOR_SESSION_KEY = 'bw_checkout_selected_payment_method';
    var BW_INTERNAL_RADIO_CHANGE = false;
    var BW_LAST_SELECTED_METHOD = '';
    var BW_LAST_EXPLICIT_SELECTION = '';
    var BW_PENDING_USER_SELECTION = '';
    var BW_IS_CONVERGING = false;
    var BW_TOOLTIP_DOC_BOUND = false;

    function getPersistedSelectedMethod() {
        try {
            return window.sessionStorage ? String(window.sessionStorage.getItem(BW_SELECTOR_SESSION_KEY) || '') : '';
        } catch (error) {
            return '';
        }
    }

    function persistSelectedMethod(methodValue) {
        try {
            if (!window.sessionStorage) {
                return;
            }
            if (methodValue) {
                window.sessionStorage.setItem(BW_SELECTOR_SESSION_KEY, methodValue);
            } else {
                window.sessionStorage.removeItem(BW_SELECTOR_SESSION_KEY);
            }
        } catch (error) {
            // Ignore storage failures (private mode / blocked storage): runtime still converges in-memory.
        }
    }

    function findFallbackPaymentMethod(excludedValue) {
        if (BW_LAST_SELECTED_METHOD && BW_LAST_SELECTED_METHOD !== excludedValue) {
            var remembered = document.querySelector('input[name="payment_method"][value="' + BW_LAST_SELECTED_METHOD + '"]');
            if (remembered && !remembered.disabled) {
                return remembered;
            }
        }

        return Array.from(document.querySelectorAll('input[name="payment_method"]')).find(function (radio) {
            return radio.value !== excludedValue && !radio.disabled;
        }) || null;
    }

    function getSelectableRadioByValue(scope, methodValue) {
        var searchScope = scope || document;
        if (!methodValue) {
            return null;
        }

        var radio = searchScope.querySelector('input[name="payment_method"][value="' + methodValue + '"]');
        if (!radio || radio.disabled) {
            return null;
        }

        if (isWalletGateway(methodValue) && isWalletExplicitlyUnavailable(methodValue)) {
            return null;
        }

        return radio;
    }

    function getPaymentContainer() {
        return document.querySelector('#payment');
    }

    function getEnabledPaymentMethodRadios(container) {
        var scope = container || document;
        return Array.from(scope.querySelectorAll('input[name="payment_method"]')).filter(function (radio) {
            return !radio.disabled;
        });
    }

    function getPreferredPaymentMethodRadio(container, preferredValue) {
        var scope = container || document;

        if (preferredValue) {
            var preferred = getSelectableRadioByValue(scope, preferredValue);
            if (preferred) {
                return preferred;
            }
        }

        var checked = scope.querySelector('input[name="payment_method"]:checked');

        var explicit = getSelectableRadioByValue(scope, BW_LAST_EXPLICIT_SELECTION);
        if (explicit) {
            // Validation-triggered checkout refresh can transiently restore the
            // platform default (card). The user's explicit selection remains
            // authoritative unless it's actually unavailable.
            if (!checked || checked.disabled || checked.value !== explicit.value) {
                return explicit;
            }
        }

        // Explicit current selection is authoritative, and wallet selections
        // must never be overridden by restore-from-persistence logic.
        if (checked) {
            if (isWalletGateway(checked.value)) {
                return checked;
            }
            if (!checked.disabled) {
                return checked;
            }
        }

        // Restore remembered/persisted method only when there is no valid
        // current selection or the selected method is unavailable.
        if (BW_LAST_SELECTED_METHOD) {
            var remembered = scope.querySelector('input[name="payment_method"][value="' + BW_LAST_SELECTED_METHOD + '"]');
            if (remembered && !remembered.disabled) {
                return remembered;
            }
        }

        var persisted = getPersistedSelectedMethod();
        if (persisted) {
            var persistedRadio = scope.querySelector('input[name="payment_method"][value="' + persisted + '"]');
            if (persistedRadio && !persistedRadio.disabled) {
                return persistedRadio;
            }
        }

        var enabled = getEnabledPaymentMethodRadios(scope);
        return enabled.length ? enabled[0] : null;
    }

    function applySingleRadioSelection(container, selectedRadio) {
        var scope = container || document;
        var radios = scope.querySelectorAll('input[name="payment_method"]');

        BW_INTERNAL_RADIO_CHANGE = true;
        radios.forEach(function (input) {
            input.checked = !!selectedRadio && input === selectedRadio;
        });
        BW_INTERNAL_RADIO_CHANGE = false;
    }

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

    function getWalletRadio(gatewayId) {
        return document.querySelector('input[name="payment_method"][value="' + gatewayId + '"]');
    }

    function isWalletExplicitlyUnavailable(gatewayId) {
        var radio = getWalletRadio(gatewayId);
        if (!radio) {
            return false;
        }

        var methodRow = radio.closest('.bw-payment-method');
        var markedUnavailable = radio.hasAttribute('data-bw-unavailable') || (methodRow && methodRow.hasAttribute('data-bw-unavailable'));
        if (!markedUnavailable) {
            return false;
        }

        // Google Pay marks "checking" with data-bw-gpay-checking while still
        // converging; this must not trigger fallback to card.
        if (gatewayId === 'bw_google_pay' && radio.hasAttribute('data-bw-gpay-checking')) {
            return false;
        }

        return true;
    }

    function updateBodyWalletClass(gatewayId, isActiveWallet) {
        document.body.classList.remove.apply(document.body.classList, BW_BODY_WALLET_CLASSES);
        if (isActiveWallet) {
            document.body.classList.add('bw-wallet-' + gatewayId);
        }
    }

    function syncCheckoutActionButtonsForSelectedMethod(selected) {
        var selectedGateway = selected || '';
        var isWallet = isWalletGateway(selectedGateway);
        var available = isWallet && isWalletAvailable(selectedGateway);
        var hasActionButton = isWallet && hasWalletActionButton(selectedGateway);

        // Fallback only when wallet is explicitly unavailable.
        // During first-pass wallet initialization/checking we keep wallet selected
        // and let wallet scripts converge UI/button availability.
        if (isWallet && isWalletExplicitlyUnavailable(selectedGateway)) {
            var fallback = findFallbackPaymentMethod(selectedGateway);
            if (fallback) {
                applySingleRadioSelection(getPaymentContainer(), fallback);
                selectedGateway = fallback.value;
                BW_LAST_SELECTED_METHOD = selectedGateway;
                persistSelectedMethod(selectedGateway);
                updatePlaceOrderButton(fallback);
                isWallet = isWalletGateway(selectedGateway);
                available = isWallet && isWalletAvailable(selectedGateway);
                hasActionButton = isWallet && hasWalletActionButton(selectedGateway);
            }
        }

        hideAllWalletButtons();
        showPlaceOrderButtons();
        updateBodyWalletClass('', false);

        if (isWallet && available && hasActionButton) {
            showWalletButton(selectedGateway);
            hidePlaceOrderButtons();
            updateBodyWalletClass(selectedGateway, true);
        }
    }

    /**
     * Single authority convergence routine.
     * Ensures selected row, checked radio and actionable submit state are coherent.
     *
     * @param {string} reason Optional debug reason.
     * @param {Object} options Convergence options.
     */
    function bwConvergeCheckoutSelectorState(reason, options) { // eslint-disable-line no-unused-vars
        var opts = options || {};
        if (!opts.preferredValue) {
            opts.preferredValue = BW_PENDING_USER_SELECTION || BW_LAST_EXPLICIT_SELECTION || '';
        }
        if (BW_IS_CONVERGING) {
            return;
        }
        BW_IS_CONVERGING = true;

        try {
            normalizeCardGatewayTitles();
            dedupeWalletDom();

            var selectedRadio = syncAccordionState(opts.preferredValue || '');
            syncCheckoutActionButtonsForSelectedMethod(selectedRadio ? selectedRadio.value : '');

            if (BW_PENDING_USER_SELECTION) {
                var pendingRadio = document.querySelector('input[name="payment_method"][value="' + BW_PENDING_USER_SELECTION + '"]');
                var pendingUnavailable = pendingRadio && isWalletGateway(BW_PENDING_USER_SELECTION)
                    ? isWalletExplicitlyUnavailable(BW_PENDING_USER_SELECTION)
                    : (pendingRadio && pendingRadio.hasAttribute('data-bw-unavailable'));
                if (!pendingRadio || pendingRadio.disabled || pendingUnavailable || (selectedRadio && selectedRadio.value === BW_PENDING_USER_SELECTION)) {
                    BW_PENDING_USER_SELECTION = '';
                }
            }

            if (opts.triggerWooSelectionEvent && selectedRadio && window.jQuery) {
                window.jQuery(document.body).trigger('payment_method_selected');
            }
        } finally {
            BW_IS_CONVERGING = false;
        }
    }

    function bwSyncCheckoutActionButtons(reason) { // eslint-disable-line no-unused-vars
        bwConvergeCheckoutSelectorState(reason || 'sync', {});
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
        var cardRows = document.querySelectorAll(
            '.bw-payment-method.payment_method_stripe, ' +
            '.bw-payment-method[data-gateway-id="stripe"], ' +
            '.bw-payment-method.payment_method_woocommerce_payments, ' +
            '.bw-payment-method[data-gateway-id="woocommerce_payments"]'
        );

        cardRows.forEach(function (row) {
            var label = row.querySelector('.bw-payment-method__label');
            if (!label) {
                return;
            }

            var icon = label.querySelector('.bw-payment-method__icon');
            var title = label.querySelector('.bw-payment-method__title');

            if (!title) {
                title = document.createElement('span');
                title.className = 'bw-payment-method__title';
                label.insertBefore(title, icon || label.firstChild);
            }

            title.textContent = 'Credit / Debit Card';

            Array.from(label.childNodes).forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '') {
                    node.textContent = '';
                }
            });
        });
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
    function syncAccordionState(preferredValue) {
        var paymentContainer = getPaymentContainer();
        if (!paymentContainer) {
            return null;
        }

        var checkedRadio = getPreferredPaymentMethodRadio(paymentContainer, preferredValue);
        applySingleRadioSelection(paymentContainer, checkedRadio);
        BW_LAST_SELECTED_METHOD = checkedRadio ? checkedRadio.value : '';
        persistSelectedMethod(BW_LAST_SELECTED_METHOD);

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

        return checkedRadio;
    }

    /**
     * Animate accordion open/close in response to a user interaction.
     * @param {HTMLElement} radio - The radio button that just became checked.
     */
    function handlePaymentMethodChange(radio) {
        var paymentContainer = getPaymentContainer();
        if (!paymentContainer) {
            return;
        }

        if (radio.disabled) {
            return;
        }

        BW_LAST_SELECTED_METHOD = radio.value;
        BW_LAST_EXPLICIT_SELECTION = radio.value;
        BW_PENDING_USER_SELECTION = radio.value;
        persistSelectedMethod(BW_LAST_SELECTED_METHOD);
        bwConvergeCheckoutSelectorState('method_change', {
            preferredValue: radio.value,
            triggerWooSelectionEvent: true
        });
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
        var checkoutForm = document.querySelector('form.checkout');
        if (checkoutForm) {
            checkoutForm.classList.remove('processing');
        }
    }

    // -------------------------------------------------------------------------
    // Document-level event delegation (survives WooCommerce DOM replacement)
    // -------------------------------------------------------------------------

    // Payment method radio change.
    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target.type === 'radio' && target.name === 'payment_method') {
            if (BW_INTERNAL_RADIO_CHANGE) {
                return;
            }

            if (target.disabled) {
                BW_INTERNAL_RADIO_CHANGE = true;
                target.checked = false;
                var fallbackRadio = findFallbackPaymentMethod(target.value);
                if (fallbackRadio) {
                    fallbackRadio.checked = true;
                    handlePaymentMethodChange(fallbackRadio);
                } else {
                    syncAccordionState();
                }
                BW_INTERNAL_RADIO_CHANGE = false;
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
            var isUnavailable = radio.disabled;
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
                    var isUnavailable = radio.disabled;
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
                    return !radio.disabled;
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

        if (!BW_TOOLTIP_DOC_BOUND) {
            document.addEventListener('click', function (event) {
                document.querySelectorAll('.bw-payment-icon--more .bw-payment-icon__tooltip').forEach(function (tooltip) {
                    var owner = tooltip.closest('.bw-payment-icon--more');
                    if (owner && !owner.contains(event.target)) {
                        tooltip.style.display = 'none';
                    }
                });
            });
            BW_TOOLTIP_DOC_BOUND = true;
        }

        moreIcons.forEach(function (moreIcon) {
            if (moreIcon.getAttribute('data-bw-tooltip-bound') === '1') {
                return;
            }
            moreIcon.setAttribute('data-bw-tooltip-bound', '1');

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
        });
    }

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    function init() {
        if (!(window.CSS && CSS.supports && CSS.supports('selector(:has(*))'))) {
            document.body.classList.add('bw-no-has-support');
        }
        BW_LAST_SELECTED_METHOD = getPersistedSelectedMethod() || BW_LAST_SELECTED_METHOD;
        BW_LAST_EXPLICIT_SELECTION = BW_LAST_EXPLICIT_SELECTION || BW_LAST_SELECTED_METHOD;
        normalizeCardGatewayTitles();
        bwConvergeCheckoutSelectorState('init');
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
            $(document.body).off('update_checkout.bwWalletUi').on('update_checkout.bwWalletUi', function () {
                var currentSelection = getSelectedPaymentMethod();
                var authoritativeSelection = BW_PENDING_USER_SELECTION || BW_LAST_EXPLICIT_SELECTION || currentSelection;
                persistSelectedMethod(authoritativeSelection);
                bwScheduleSync('update_checkout');
            });

            $(document.body).off('updated_checkout.bwWalletUi').on('updated_checkout.bwWalletUi', function () {
                bwConvergeCheckoutSelectorState('updated_checkout', {
                    preferredValue: BW_PENDING_USER_SELECTION || ''
                });
                initPaymentIconsTooltips();
                bwScheduleSync('updated_checkout');
            });

            $(document.body).off('payment_method_selected.bwWalletUi').on('payment_method_selected.bwWalletUi', function () {
                bwConvergeCheckoutSelectorState('payment_method_selected');
                bwScheduleSync('payment_method_selected');
            });

            $(document).off('change.bwWalletUi', 'input[name="payment_method"]').on('change.bwWalletUi', 'input[name="payment_method"]', function () {
                bwConvergeCheckoutSelectorState('payment_method_change');
                bwScheduleSync('payment_method_change');
            });

            $(document.body).off('checkout_error.bwWalletUi').on('checkout_error.bwWalletUi', function () {
                removeLoadingState();
                bwConvergeCheckoutSelectorState('checkout_error');
                bwScheduleSync('checkout_error');
            });
        });
    }

    // Wallet cancel/return may not always emit deterministic selectors immediately:
    // converge again on tab/page focus transitions.
    window.addEventListener('pageshow', function () {
        bwScheduleSync('pageshow');
    });

    window.addEventListener('focus', function () {
        bwScheduleSync('window_focus');
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            bwScheduleSync('visibilitychange_visible');
        }
    });

    // -------------------------------------------------------------------------
    // Shared checkout notice renderer (used by Google Pay, Apple Pay, Klarna).
    // -------------------------------------------------------------------------

    function bwNoticeEscapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCheckoutNotice(type, message) {
        var $ = window.jQuery;
        if (!$) { return; }
        var klass = type === 'error' ? 'woocommerce-error' : 'woocommerce-info';
        var $notices = $('.woocommerce-notices-wrapper').first();
        if ($notices.length) {
            $notices.html('<ul class="' + klass + '" role="alert"><li>' + bwNoticeEscapeHtml(message) + '</li></ul>');
        }
        $('html, body').animate({ scrollTop: 0 }, 250);
    }

    // Expose shared utilities for wallet gateway scripts (Google Pay, Apple Pay).
    window.bwCheckout = window.bwCheckout || {};
    window.bwCheckout.removeLoadingState = removeLoadingState;
    window.bwCheckout.renderCheckoutNotice = renderCheckoutNotice;

})();
