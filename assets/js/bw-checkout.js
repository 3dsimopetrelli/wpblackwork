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

        var remove = event.target.closest('.bw-review-item__remove, .bw-review-item__remove-text');

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

        // Handle coupon removal via AJAX
        var couponRemove = event.target.closest('.woocommerce-remove-coupon');

        if (couponRemove) {
            event.preventDefault();
            event.stopPropagation();

            var couponCode = couponRemove.getAttribute('data-coupon');

            if (!couponCode || !window.jQuery || !window.bwCheckoutParams) {
                return;
            }

            setOrderSummaryLoading(true);

            var $ = window.jQuery;

            // Use our custom AJAX endpoint to remove the coupon
            $.ajax({
                type: 'POST',
                url: bwCheckoutParams.ajax_url,
                data: {
                    action: 'bw_remove_coupon',
                    nonce: bwCheckoutParams.nonce,
                    coupon: couponCode
                },
                success: function (response) {
                    if (response.success) {
                        // Trigger checkout update to refresh totals
                        $(document.body).trigger('update_checkout');
                    } else {
                        setOrderSummaryLoading(false);
                        showCouponMessage(response.data.message || 'Error removing coupon', 'error');
                    }
                },
                error: function () {
                    setOrderSummaryLoading(false);
                    showCouponMessage('Error removing coupon', 'error');
                }
            });

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

    // Payment methods accordion logic moved to bw-payment-methods.js

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
                    // Don't set loading here - it's already handled by AJAX call
                    showCouponMessage('Coupon code removed', 'success');
                })
                .on('updated_checkout checkout_error', function () {
                    setOrderSummaryLoading(false);
                });

        });
    }

    // Custom sticky behavior for right column (desktop only)
    function initCustomSticky() {
        var BREAKPOINT = 900; // Match CSS @media breakpoint

        var rightColumn = document.querySelector('.bw-checkout-right');
        if (!rightColumn) {
            return;
        }

        var parent = rightColumn.parentElement;
        if (!parent) {
            return;
        }

        // Get CSS variables
        var style = window.getComputedStyle(rightColumn);
        var stickyTop = parseInt(style.getPropertyValue('--bw-checkout-right-sticky-top')) || 20;
        var marginTop = parseInt(style.getPropertyValue('--bw-checkout-right-margin-top')) || 0;

        var initialOffset = null;
        var isSticky = false;
        var placeholder = null;

        function isDesktop() {
            return window.innerWidth >= BREAKPOINT;
        }

        function resetSticky() {
            if (isSticky) {
                rightColumn.style.position = '';
                rightColumn.style.top = '';
                rightColumn.style.left = '';
                rightColumn.style.width = '';
                rightColumn.style.marginTop = marginTop + 'px'; // Restore original margin-top
                if (placeholder && placeholder.parentNode) {
                    placeholder.parentNode.removeChild(placeholder);
                    placeholder = null;
                }
                isSticky = false;
            }
        }

        function calculateOffsets() {
            // Reset to get accurate measurements
            resetSticky();

            var rect = rightColumn.getBoundingClientRect();
            // Calculate where the column starts relative to the document
            initialOffset = rect.top + window.pageYOffset;

            return {
                elementTop: rect.top,
                elementLeft: rect.left,
                elementWidth: rect.width
            };
        }

        function onScroll() {
            // Only apply sticky on desktop
            if (!isDesktop()) {
                resetSticky();
                return;
            }

            if (initialOffset === null) {
                calculateOffsets();
            }

            var scrollY = window.pageYOffset;
            // When we scroll past the column's initial position minus the sticky offset, make it sticky
            var threshold = initialOffset - stickyTop;

            if (scrollY >= threshold && !isSticky) {
                // Make it sticky
                isSticky = true;

                // Create placeholder to maintain layout
                if (!placeholder) {
                    placeholder = document.createElement('div');
                    placeholder.style.height = rightColumn.offsetHeight + 'px';
                    placeholder.style.width = rightColumn.offsetWidth + 'px';
                    parent.insertBefore(placeholder, rightColumn);
                }

                var rect = rightColumn.getBoundingClientRect();
                rightColumn.style.position = 'fixed';
                rightColumn.style.top = stickyTop + 'px';
                rightColumn.style.left = rect.left + 'px';
                rightColumn.style.width = rect.width + 'px';
                rightColumn.style.marginTop = '0';

            } else if (scrollY < threshold && isSticky) {
                // Return to normal
                resetSticky();
            }
        }

        function onResize() {
            // Reset sticky if switching to mobile
            if (!isDesktop()) {
                resetSticky();
                initialOffset = null;
                return;
            }

            // Recalculate on desktop resize
            initialOffset = null;
            if (isSticky) {
                calculateOffsets();
                setTimeout(onScroll, 0);
            }
        }

        // Initialize on load
        setTimeout(function() {
            calculateOffsets();
            onScroll();
        }, 100);

        // Listen to scroll and resize
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onResize);

        // Re-calculate on checkout update
        if (window.jQuery) {
            window.jQuery(document.body).on('updated_checkout', function() {
                setTimeout(function() {
                    initialOffset = null;
                    calculateOffsets();
                    onScroll();
                }, 500);
            });
        }
    }

    // Initialize custom sticky
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomSticky);
    } else {
        initCustomSticky();
    }
})();
