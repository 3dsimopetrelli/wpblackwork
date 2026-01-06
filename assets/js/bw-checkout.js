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

            // RADICAL FIX: Remove coupon server-side, wait for confirmation, then do a FULL PAGE RELOAD
            // This is the most reliable way to ensure session persistence without race conditions
            // The page reload guarantees that the checkout fragments are generated with the updated session
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
                        // CRITICAL: Use redirect instead of reload() to prevent POST replay
                        // window.location.reload() can replay the last POST (coupon application)
                        // Redirect to same page forces a fresh GET request without POST data
                        window.location.href = window.location.pathname + window.location.search;
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
                    // FIX: Don't set loading or show message here - it's already handled by our custom AJAX call
                    // This event is triggered by our custom handler for integration with WooCommerce ecosystem
                    // Just ensure loading state is set (in case called from elsewhere)
                    setOrderSummaryLoading(true);
                })
                .on('updated_checkout', function () {
                    setOrderSummaryLoading(false);
                })
                .on('checkout_error', function () {
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

    // Floating label for coupon input
    function initFloatingLabel() {
        var couponInput = document.getElementById('coupon_code');
        var wrapper = couponInput ? couponInput.closest('.bw-coupon-input-wrapper') : null;
        var label = wrapper ? wrapper.querySelector('.bw-floating-label') : null;
        var errorDiv = document.querySelector('.bw-coupon-error');

        if (!couponInput || !wrapper || !label) {
            return;
        }

        function updateHasValue() {
            var hasValue = couponInput.value.trim() !== '';

            if (hasValue) {
                wrapper.classList.add('has-value');
                // Change label text to short version
                if (label.getAttribute('data-short')) {
                    label.textContent = label.getAttribute('data-short');
                }
            } else {
                wrapper.classList.remove('has-value');
                // Change label text back to full version
                if (label.getAttribute('data-full')) {
                    label.textContent = label.getAttribute('data-full');
                }
            }
        }

        function clearError() {
            if (errorDiv) {
                errorDiv.style.display = 'none';
                errorDiv.textContent = '';
            }
        }

        function showError(message) {
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }

        // Check on input
        couponInput.addEventListener('input', function() {
            updateHasValue();
            clearError();
        });

        // Clear error on focus
        couponInput.addEventListener('focus', clearError);

        // Handle coupon form submission
        var couponForm = couponInput.closest('form');
        if (couponForm) {
            couponForm.addEventListener('submit', function(e) {
                clearError();
            });
        }

        // Check on page load (in case of browser autofill)
        updateHasValue();

        // Listen for WooCommerce notice events to catch coupon errors
        if (window.jQuery) {
            // Re-check after WooCommerce checkout update
            window.jQuery(document.body).on('updated_checkout', function() {
                var newCouponInput = document.getElementById('coupon_code');
                var newWrapper = newCouponInput ? newCouponInput.closest('.bw-coupon-input-wrapper') : null;
                var newLabel = newWrapper ? newWrapper.querySelector('.bw-floating-label') : null;
                var newErrorDiv = document.querySelector('.bw-coupon-error');

                if (newCouponInput && newWrapper && newLabel) {
                    couponInput = newCouponInput;
                    wrapper = newWrapper;
                    label = newLabel;
                    errorDiv = newErrorDiv;

                    // Re-attach listener to new element
                    couponInput.addEventListener('input', function() {
                        updateHasValue();
                        clearError();
                    });
                    couponInput.addEventListener('focus', clearError);

                    var newCouponForm = couponInput.closest('form');
                    if (newCouponForm) {
                        newCouponForm.addEventListener('submit', function() {
                            clearError();
                        });
                    }

                    updateHasValue();

                    // Check for WooCommerce error notices (only errors, not success messages)
                    // Skip this on initial page load to prevent duplicate messages on refresh
                    var isInitialLoad = !couponInput.value || couponInput.value.trim() === '';

                    if (!isInitialLoad) {
                        setTimeout(function() {
                            // Only check for actual errors, not success messages
                            var wcErrors = document.querySelectorAll('.woocommerce-error');
                            if (wcErrors.length > 0) {
                                wcErrors.forEach(function(notice) {
                                    var text = notice.textContent.trim();
                                    // Check if it's a coupon-related error (not success)
                                    if (text.toLowerCase().includes('coupon') ||
                                        text.toLowerCase().includes('code') ||
                                        text.toLowerCase().includes('expired') ||
                                        text.toLowerCase().includes('not valid') ||
                                        text.toLowerCase().includes('does not exist')) {
                                        showError(text);
                                        // Hide WooCommerce notice to avoid duplication
                                        notice.style.display = 'none';
                                    }
                                });
                            }
                        }, 100);
                    }
                }
            });
        }
    }

    // Initialize floating label
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFloatingLabel);
    } else {
        initFloatingLabel();
    }
})();
