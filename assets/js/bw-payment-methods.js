/**
 * BW Payment Methods - Shopify-style Accordion
 *
 * Handles accordion behavior and dynamic payment button text
 * Compatible with all WooCommerce payment gateways (Stripe, PayPal, etc.)
 */

(function () {
    'use strict';

    /**
     * Initialize payment methods accordion
     */
    function initPaymentMethodsAccordion() {
        var paymentContainer = document.querySelector('#payment');

        if (!paymentContainer) {
            return;
        }

        // Handle radio button changes
        var radioButtons = paymentContainer.querySelectorAll('input[name="payment_method"]');

        radioButtons.forEach(function (radio) {
            radio.addEventListener('change', function () {
                handlePaymentMethodChange(this);
            });
        });

        // Initialize on first load - open the first payment method
        var firstChecked = paymentContainer.querySelector('input[name="payment_method"]:checked');
        if (firstChecked) {
            handlePaymentMethodChange(firstChecked);
        }
    }

    /**
     * Handle payment method change
     * @param {HTMLElement} radio - The selected radio button
     */
    function handlePaymentMethodChange(radio) {
        var paymentContainer = document.querySelector('#payment');

        if (!paymentContainer) {
            return;
        }

        // Close all payment boxes with smooth animation
        var allBoxes = paymentContainer.querySelectorAll('.bw-payment-method__content');
        allBoxes.forEach(function (box) {
            box.classList.remove('is-open');
        });

        // Open the selected payment box with smooth animation
        var selectedMethod = radio.closest('.bw-payment-method');
        if (selectedMethod) {
            var contentBox = selectedMethod.querySelector('.bw-payment-method__content');
            if (contentBox) {
                // Small delay to ensure smooth animation
                setTimeout(function () {
                    contentBox.classList.add('is-open');
                }, 10);
            }
        }

        // Update the place order button text
        updatePlaceOrderButton(radio);

        // Trigger WooCommerce event
        if (window.jQuery) {
            window.jQuery(document.body).trigger('payment_method_selected');
        }
    }

    /**
     * Update place order button text based on selected payment method
     * @param {HTMLElement} radio - The selected radio button
     */
    function updatePlaceOrderButton(radio) {
        var placeOrderBtn = document.querySelector('.bw-place-order-btn');

        if (!placeOrderBtn) {
            return;
        }

        var buttonTextSpan = placeOrderBtn.querySelector('.bw-place-order-btn__text');
        var buttonText = radio.getAttribute('data-order_button_text') || placeOrderBtn.getAttribute('data-default-text') || 'Place order';

        // Update button text
        if (buttonTextSpan) {
            buttonTextSpan.textContent = buttonText;
        } else {
            placeOrderBtn.textContent = buttonText;
        }

        // Update button value
        placeOrderBtn.setAttribute('value', buttonText);

        // Add smooth animation
        if (buttonTextSpan) {
            buttonTextSpan.style.opacity = '0';
            setTimeout(function () {
                buttonTextSpan.style.opacity = '1';
            }, 50);
        }
    }

    /**
     * Handle form submission - add loading state
     */
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

    /**
     * Remove loading state on checkout error
     */
    function removeLoadingState() {
        var placeOrderBtn = document.querySelector('.bw-place-order-btn');

        if (placeOrderBtn) {
            placeOrderBtn.classList.remove('processing');
        }
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        initPaymentMethodsAccordion();
        handleFormSubmission();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-initialize on WooCommerce checkout updates
    if (window.jQuery) {
        window.jQuery(function ($) {
            $(document.body).on('updated_checkout', function () {
                // Small delay to ensure DOM is updated
                setTimeout(function () {
                    initPaymentMethodsAccordion();
                }, 100);
            });

            $(document.body).on('checkout_error', function () {
                removeLoadingState();
            });
        });
    }

    /**
     * Handle keyboard navigation (accessibility)
     */
    document.addEventListener('keydown', function (event) {
        // Handle Enter/Space on payment method labels
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

        // Handle Arrow keys for navigation between payment methods
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            var target = event.target;

            if (target.type === 'radio' && target.name === 'payment_method') {
                event.preventDefault();
                var allRadios = Array.from(document.querySelectorAll('input[name="payment_method"]'));
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

    /**
     * Handle payment method header clicks (entire header is clickable)
     */
    document.addEventListener('click', function (event) {
        var header = event.target.closest('.bw-payment-method__header');

        if (header) {
            var radio = header.querySelector('input[type="radio"]');

            // Don't trigger if clicking directly on radio or label
            if (event.target.type !== 'radio' && !event.target.classList.contains('bw-payment-method__label')) {
                if (radio && !radio.checked) {
                    radio.checked = true;
                    handlePaymentMethodChange(radio);
                }
            }
        }
    });

    /**
     * Handle payment icons tooltip visibility
     * Keeps tooltip open when hovering over it
     */
    function initPaymentIconsTooltips() {
        var moreIcons = document.querySelectorAll('.bw-payment-icon--more');

        moreIcons.forEach(function (moreIcon) {
            var tooltip = moreIcon.querySelector('.bw-payment-icon__tooltip');
            var hideTimeout;

            if (!tooltip) {
                return;
            }

            // Show tooltip on hover over badge
            moreIcon.addEventListener('mouseenter', function () {
                clearTimeout(hideTimeout);
                tooltip.style.display = 'flex';
            });

            // Keep tooltip open when hovering over tooltip itself
            tooltip.addEventListener('mouseenter', function () {
                clearTimeout(hideTimeout);
                tooltip.style.display = 'flex';
            });

            // Hide tooltip with delay when leaving badge
            moreIcon.addEventListener('mouseleave', function (event) {
                // Check if we're moving to the tooltip
                if (!tooltip.contains(event.relatedTarget)) {
                    hideTimeout = setTimeout(function () {
                        tooltip.style.display = 'none';
                    }, 100);
                }
            });

            // Hide tooltip when leaving tooltip
            tooltip.addEventListener('mouseleave', function () {
                hideTimeout = setTimeout(function () {
                    tooltip.style.display = 'none';
                }, 100);
            });

            // Hide tooltip on click outside
            document.addEventListener('click', function (event) {
                if (!moreIcon.contains(event.target)) {
                    tooltip.style.display = 'none';
                }
            });
        });
    }

    // Initialize tooltips on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPaymentIconsTooltips);
    } else {
        initPaymentIconsTooltips();
    }

    // Re-initialize tooltips on checkout update
    if (window.jQuery) {
        window.jQuery(document.body).on('updated_checkout', function () {
            setTimeout(function () {
                initPaymentIconsTooltips();
            }, 100);
        });
    }

})();
