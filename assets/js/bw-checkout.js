(function () {
    // Guard against double-execution if script loads multiple times
    if (window.BWCheckout) {
        return;
    }
    window.BWCheckout = true;

    function triggerCheckoutUpdate() {
        if (window.jQuery && window.jQuery(document.body).trigger) {
            window.jQuery(document.body).trigger('update_checkout');
        }
    }

    function setOrderSummaryLoading(isLoading) {
        var summary = document.querySelector('.bw-order-summary');

        if (!summary) {
            return;
        }

        if (isLoading) {
            summary.classList.add('is-loading');
        } else {
            summary.classList.remove('is-loading');
        }
    }

    function updateQuantity(input, delta) {
        var current = parseFloat(input.value || 0);
        var step = parseFloat(input.getAttribute('step')) || 1;
        var min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min')) : 0;
        var max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max')) : Infinity;
        var next = current + delta * step;

        if (isNaN(next)) {
            next = 0;
        }

        if (next < min) {
            next = min;
        }

        if (next > max) {
            next = max;
        }

        if (next !== current) {
            input.value = next;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    document.addEventListener('click', function (event) {
        // Handle express checkout buttons
        var expressBtn = event.target.closest('.bw-express-checkout__button');
        if (expressBtn) {
            var paymentMethod = expressBtn.getAttribute('data-payment-method');
            // Placeholder for future express checkout integration
            // Will be integrated with actual payment gateways (Shop Pay, PayPal, Google Pay)
            console.log('Express checkout clicked:', paymentMethod);
            return;
        }

        var button = event.target.closest('.bw-qty-btn');

        if (button) {
            var shell = button.closest('.bw-qty-shell');
            var input = shell ? shell.querySelector('input.qty') : null;

            if (!input) {
                return;
            }

            var delta = button.classList.contains('bw-qty-btn--minus') ? -1 : 1;
            updateQuantity(input, delta);
            setOrderSummaryLoading(true);
            return;
        }

        var remove = event.target.closest('.bw-review-item__remove');

        if (remove) {
            event.preventDefault();

            var item = remove.closest('[data-cart-item]');
            var qtyInput = item ? item.querySelector('input.qty') : null;

            if (qtyInput) {
                qtyInput.value = 0;
                qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
                setOrderSummaryLoading(true);
            } else {
                window.location.href = remove.getAttribute('href');
            }

            return;
        }
    });

    document.addEventListener('change', function (event) {
        var qtyInput = event.target.closest('.bw-review-item input.qty');

        if (!qtyInput) {
            return;
        }

        triggerCheckoutUpdate();
        setOrderSummaryLoading(true);
    });

    function showCouponMessage(message, type) {
        var messageEl = document.getElementById('bw-coupon-message');
        if (!messageEl) {
            return;
        }

        messageEl.className = 'bw-coupon-message ' + type;
        messageEl.textContent = message;

        // Auto hide after 5 seconds
        setTimeout(function() {
            messageEl.className = 'bw-coupon-message';
        }, 5000);
    }

    if (window.jQuery) {
        window.jQuery(function ($) {
            $(document.body)
                .on('update_checkout wc_cart_emptied', function () {
                    setOrderSummaryLoading(true);
                })
                .on('applied_coupon', function (event, couponCode) {
                    setOrderSummaryLoading(true);
                    showCouponMessage('Coupon code applied successfully', 'success');
                })
                .on('removed_coupon', function (event, couponCode) {
                    setOrderSummaryLoading(true);
                    showCouponMessage('Coupon code removed', 'success');
                })
                .on('updated_checkout checkout_error', function () {
                    setOrderSummaryLoading(false);
                });

            // Client-side validation for better UX (prevents unnecessary AJAX call)
            // WooCommerce also validates server-side, but this provides immediate feedback
            $(document).on('submit', 'form.checkout_coupon', function(e) {
                var couponInput = $(this).find('input[name="coupon_code"]');
                if (couponInput.length && !couponInput.val()) {
                    e.preventDefault();
                    showCouponMessage('Please enter a coupon code', 'error');
                    return false;
                }
            });
        });
    }
})();
