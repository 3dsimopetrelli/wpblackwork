(function () {
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

    function flagExpressPaymentMethods() {
        var list = document.querySelector('.woocommerce-checkout-payment ul.payment_methods');

        if (!list) {
            return;
        }

        var keywords = ['vpay', 'apple', 'gpay', 'google'];
        var hasExpress = false;

        list.classList.remove('bw-has-express-payments');
        list.querySelectorAll('.bw-express-divider').forEach(function (divider) {
            divider.remove();
        });

        list.querySelectorAll('li').forEach(function (item) {
            item.classList.remove('bw-express-payment', 'bw-standard-payment');

            var token = ((item.id || '') + ' ' + item.textContent).toLowerCase();
            var isExpress = keywords.some(function (key) {
                return token.indexOf(key) !== -1;
            });

            if (isExpress) {
                item.classList.add('bw-express-payment');
                hasExpress = true;
            } else {
                item.classList.add('bw-standard-payment');
            }
        });

        if (hasExpress) {
            list.classList.add('bw-has-express-payments');

            var firstStandard = list.querySelector('li.bw-standard-payment');

            if (firstStandard) {
                var divider = document.createElement('li');
                divider.className = 'bw-express-divider';
                divider.setAttribute('aria-hidden', 'true');

                var inner = document.createElement('div');
                inner.className = 'bw-express-divider__inner';

                var label = document.createElement('span');
                label.textContent = 'OR';

                inner.appendChild(label);
                divider.appendChild(inner);

                list.insertBefore(divider, firstStandard);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', flagExpressPaymentMethods);

    if (window.jQuery) {
        window.jQuery(function ($) {
            $(document.body).on('updated_checkout payment_method_selected', flagExpressPaymentMethods);
        });
    }

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

            // Handle coupon form submission via AJAX
            $(document).on('submit', 'form.checkout_coupon', function(e) {
                e.preventDefault();

                var $form = $(this);
                var couponInput = $form.find('input[name="coupon_code"]');
                var couponCode = couponInput.val().trim();

                // Validation
                if (!couponCode) {
                    showCouponMessage('Please enter a coupon code', 'error');
                    return false;
                }

                // Disable submit button
                var $button = $form.find('button[type="submit"]');
                $button.prop('disabled', true);
                setOrderSummaryLoading(true);

                // Apply coupon via AJAX
                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon'),
                    data: {
                        security: wc_checkout_params.apply_coupon_nonce,
                        coupon_code: couponCode
                    },
                    success: function(response) {
                        if (response.error) {
                            showCouponMessage(response.error, 'error');
                        } else if (response.success) {
                            couponInput.val('');
                            showCouponMessage('Coupon applied successfully', 'success');
                            $(document.body).trigger('applied_coupon', [couponCode]);
                            $(document.body).trigger('update_checkout', { update_shipping_method: false });
                        }
                    },
                    error: function() {
                        showCouponMessage('Error applying coupon. Please try again.', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });

                return false;
            });
        });
    }
})();
