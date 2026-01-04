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

        // Close all payment boxes
        var allBoxes = paymentContainer.querySelectorAll('.bw-payment-method__content');
        allBoxes.forEach(function (box) {
            box.style.display = 'none';
        });

        // Open the selected payment box
        var selectedMethod = radio.closest('.bw-payment-method');
        if (selectedMethod) {
            var contentBox = selectedMethod.querySelector('.bw-payment-method__content');
            if (contentBox) {
                contentBox.style.display = 'block';
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

})();
