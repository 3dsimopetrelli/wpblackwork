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

    if (window.jQuery) {
        window.jQuery(function ($) {
            $(document.body)
                .on('update_checkout applied_coupon removed_coupon wc_cart_emptied', function () {
                    setOrderSummaryLoading(true);
                })
                .on('updated_checkout checkout_error', function () {
                    setOrderSummaryLoading(false);
                });
        });
    }
})();
