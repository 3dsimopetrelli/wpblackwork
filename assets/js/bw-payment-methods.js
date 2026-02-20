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

        // Deselect all.
        paymentContainer.querySelectorAll('.bw-payment-method').forEach(function (method) {
            method.classList.remove('is-selected');
        });
        paymentContainer.querySelectorAll('.bw-payment-method__content').forEach(function (box) {
            box.classList.remove('is-open');
        });

        // Select the chosen method.
        var selectedMethod = radio.closest('.bw-payment-method');
        if (selectedMethod) {
            selectedMethod.classList.add('is-selected');

            var contentBox = selectedMethod.querySelector('.bw-payment-method__content');
            if (contentBox) {
                contentBox.classList.add('is-open');
            }
        }

        updatePlaceOrderButton(radio);

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
            handlePaymentMethodChange(target);
        }
    });

    // Entire payment-method header is clickable (except radio/label themselves).
    document.addEventListener('click', function (event) {
        var header = event.target.closest('.bw-payment-method__header');
        if (!header) {
            return;
        }

        // Skip if the click landed directly on the radio or its label — the
        // browser will fire the `change` event for those automatically.
        if (event.target.type === 'radio' ||
            event.target.classList.contains('bw-payment-method__label') ||
            event.target.closest('.bw-payment-method__label')) {
            return;
        }

        var radio = header.querySelector('input[type="radio"]');
        if (radio && !radio.checked) {
            radio.checked = true;
            handlePaymentMethodChange(radio);
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
                var allRadios    = Array.from(document.querySelectorAll('input[name="payment_method"]'));
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
        syncAccordionState();
        handleFormSubmission();
        initPaymentIconsTooltips();
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
            $(document.body).on('updated_checkout', function () {
                syncAccordionState();
                initPaymentIconsTooltips();
            });

            $(document.body).on('checkout_error', function () {
                removeLoadingState();
            });
        });
    }

})();
