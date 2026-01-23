console.log('[BW Checkout] Script file loaded and executing');

(function() {
    'use strict';

    console.log('[BW Checkout] IIFE started');

    // Force execution even if there are errors in other scripts
    var $ = window.jQuery;

    if (!$) {
        console.log('[BW Checkout] jQuery not ready, will retry');
        // jQuery not loaded, retry after short delay
        setTimeout(arguments.callee, 100);
        return;
    }

    console.log('[BW Checkout] jQuery is ready, continuing initialization');

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

    // Store active message to persist through checkout updates
    var activeCouponMessage = null;
    var messageTimer = null;

    function showCouponMessage(message, type) {
        var messageEl = document.getElementById('bw-coupon-message');
        if (!messageEl) {
            return;
        }

        // Store message for persistence through DOM updates
        activeCouponMessage = { message: message, type: type, timestamp: Date.now() };

        // Clear any existing timer
        if (messageTimer) {
            clearTimeout(messageTimer);
        }

        // Show message
        messageEl.className = 'bw-coupon-message ' + type;
        messageEl.textContent = message;
        messageEl.style.opacity = '1';

        // Auto hide after 5 seconds with fade-out
        messageTimer = setTimeout(function() {
            messageEl.style.opacity = '0';
            setTimeout(function() {
                messageEl.className = 'bw-coupon-message';
                messageEl.style.opacity = '';
                activeCouponMessage = null;
            }, 300); // Wait for fade-out animation
        }, 5000);
    }

    function restoreCouponMessage() {
        if (!activeCouponMessage) {
            return;
        }

        // Only restore if message is less than 5 seconds old
        var age = Date.now() - activeCouponMessage.timestamp;
        if (age > 5000) {
            activeCouponMessage = null;
            return;
        }

        var messageEl = document.getElementById('bw-coupon-message');
        if (!messageEl) {
            return;
        }

        messageEl.className = 'bw-coupon-message ' + activeCouponMessage.type;
        messageEl.textContent = activeCouponMessage.message;
        messageEl.style.opacity = '1';

        // Set remaining time for auto-hide
        var remainingTime = 5000 - age;
        if (messageTimer) {
            clearTimeout(messageTimer);
        }

        messageTimer = setTimeout(function() {
            messageEl.style.opacity = '0';
            setTimeout(function() {
                messageEl.className = 'bw-coupon-message';
                messageEl.style.opacity = '';
                activeCouponMessage = null;
            }, 300);
        }, remainingTime);
    }

    function getOrderTotalText() {
        var totalEl = document.querySelector('.bw-checkout-right .order-total .amount') ||
            document.querySelector('.woocommerce-checkout-review-order .order-total .amount');

        if (!totalEl) {
            return '';
        }

        return totalEl.textContent.trim();
    }

    function updateOrderSummaryTotals() {
        var totalText = getOrderTotalText();

        if (!totalText) {
            return;
        }

        var toggleTotal = document.querySelector('.bw-order-summary-total');
        if (toggleTotal) {
            toggleTotal.textContent = totalText;
        }

        var mobileTotal = document.querySelector('.bw-mobile-total-amount');
        if (mobileTotal) {
            mobileTotal.textContent = totalText;
        }
    }

    function initOrderSummaryToggle() {
        var toggle = document.querySelector('.bw-order-summary-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = document.body.classList.toggle('bw-order-summary-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
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
                    // Restore coupon message if it was showing before update
                    restoreCouponMessage();
                    updateOrderSummaryTotals();
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


    // Fix Stripe error icon positioning (force inline layout with inline styles)
    function fixStripeErrorLayout() {
        // Find all Stripe error containers in both main DOM and shadow roots
        var errorContainers = document.querySelectorAll('.Error, [class*="Error"]');

        errorContainers.forEach(function(container) {
            // Check if it's actually a Stripe error (has ErrorIcon and ErrorText)
            var icon = container.querySelector('.ErrorIcon, [class*="ErrorIcon"]');
            var text = container.querySelector('.ErrorText, [class*="ErrorText"]');

            if (icon && text) {
                // Force inline layout with inline styles using !important (overrides everything)
                container.style.setProperty('display', 'flex', 'important');
                container.style.setProperty('flex-direction', 'row', 'important');
                container.style.setProperty('align-items', 'flex-start', 'important');
                container.style.setProperty('gap', '8px', 'important');

                icon.style.setProperty('display', 'inline-flex', 'important');
                icon.style.setProperty('flex-shrink', '0', 'important');
                icon.style.setProperty('width', '16px', 'important');
                icon.style.setProperty('height', '16px', 'important');
                icon.style.setProperty('margin', '2px 0 0 0', 'important');

                text.style.setProperty('display', 'inline-block', 'important');
                text.style.setProperty('flex', '1 1 auto', 'important');
                text.style.setProperty('margin', '0', 'important');
            }
        });
    }

    // Run immediately and observe for new errors
    function observeStripeErrors() {
        // Run initial fix
        fixStripeErrorLayout();

        // Observe DOM changes to catch dynamically added errors
        var observer = new MutationObserver(function(mutations) {
            var shouldFix = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            var hasError = node.matches && (
                                node.matches('.Error, [class*="Error"]') ||
                                node.querySelector('.Error, [class*="Error"]')
                            );
                            if (hasError) {
                                shouldFix = true;
                            }
                        }
                    });
                }
            });
            if (shouldFix) {
                setTimeout(fixStripeErrorLayout, 10);
            }
        });

        // Observe the payment area for changes
        var paymentArea = document.querySelector('.bw-checkout-payment, #payment');
        if (paymentArea) {
            observer.observe(paymentArea, {
                childList: true,
                subtree: true
            });
        }
    }

    console.log('[BW Checkout] About to define initFloatingLabel');

    // Floating label for coupon input
    function initFloatingLabel() {
        console.log('[BW Checkout] initFloatingLabel called');
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

        // Handle coupon button click (form converted to div with type="button")
        var couponContainer = couponInput.closest('.checkout_coupon, .woocommerce-form-coupon');
        var applyButton = couponContainer ? couponContainer.querySelector('.bw-apply-button') : null;

        console.log('[BW Checkout] Looking for apply button:', {
            couponContainer: couponContainer,
            applyButton: applyButton,
            couponContainerHTML: couponContainer ? couponContainer.outerHTML.substring(0, 200) : 'NOT FOUND'
        });

        function applyCouponAjax() {
            console.log('[BW Checkout] applyCouponAjax function called!');
            clearError();

            var couponCode = couponInput.value.trim();

            if (!couponCode) {
                showError('Please enter a coupon code');
                return false;
            }

            // Apply coupon via custom AJAX handler with proper session persistence
            if (window.jQuery && window.bwCheckoutParams) {
                var $ = window.jQuery;

                setOrderSummaryLoading(true);

                $.ajax({
                    type: 'POST',
                    url: bwCheckoutParams.ajax_url,
                    data: {
                        action: 'bw_apply_coupon',
                        nonce: bwCheckoutParams.nonce,
                        coupon_code: couponCode
                    },
                    success: function(response) {
                        console.log('[BW Checkout] AJAX response received:', response);
                        console.log('[BW Checkout] response.success:', response.success);
                        console.log('[BW Checkout] response.data:', response.data);

                        if (response.success) {
                            console.log('[BW Checkout] Coupon applied successfully!');
                            // Trigger checkout update to refresh totals
                            $(document.body).trigger('update_checkout');
                            showCouponMessage('Coupon applied successfully', 'success');
                            // Clear input after successful application
                            couponInput.value = '';
                            updateHasValue();
                        } else {
                            console.log('[BW Checkout] Coupon application failed');
                            setOrderSummaryLoading(false);
                            var errorMessage = response.data && response.data.message
                                ? response.data.message
                                : 'Invalid coupon code';
                            console.log('[BW Checkout] Error message:', errorMessage);
                            showError(errorMessage);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[BW Checkout] AJAX error:', {xhr: xhr, status: status, error: error});
                        setOrderSummaryLoading(false);
                        showError('Error applying coupon. Please try again.');
                    }
                });
                return false;
            } else {
                // Fallback: show error if jQuery or BW checkout params not available
                console.error('[BW Checkout] jQuery or bwCheckoutParams not available');
                showError('Unable to apply coupon. Please refresh the page.');
                return false;
            }
        }

        // SIMPLE JQUERY DELEGATION - Works like cart popup
        // Use jQuery's delegation to handle button clicks even if WooCommerce recreates the element
        if (window.jQuery) {
            console.log('[BW Checkout] Setting up jQuery delegation for apply button');

            // Apply button click handler
            $(document).on('click', '.bw-apply-button', function(e) {
                console.log('[BW Checkout] Apply button clicked!');
                e.preventDefault();
                e.stopPropagation();
                applyCouponAjax();
                return false;
            });

            // Enter key in coupon input
            $(document).on('keypress', '#coupon_code', function(e) {
                if (e.which === 13) { // Enter key
                    console.log('[BW Checkout] Enter key pressed in coupon input');
                    e.preventDefault();
                    e.stopPropagation();
                    applyCouponAjax();
                    return false;
                }
            });

            console.log('[BW Checkout] jQuery delegation successfully set up');
        } else {
            console.error('[BW Checkout] jQuery not available for event delegation');
        }

        // Check on page load (in case of browser autofill)
        updateHasValue();

        // Check if we should show messages on page load (after coupon form submission)
        var shouldShowMessages = false;
        try {
            shouldShowMessages = sessionStorage.getItem('bw_coupon_action') === 'true';
            if (shouldShowMessages) {
                sessionStorage.removeItem('bw_coupon_action');
            }
        } catch(e) {}

        // Listen for WooCommerce notice events to catch coupon errors and success
        if (window.jQuery) {
            // Show messages on initial load if flag is set (after form POST)
            if (shouldShowMessages) {
                setTimeout(function() {
                    var wcNotices = document.querySelectorAll('.woocommerce-error, .woocommerce-message, .woocommerce-info');
                    if (wcNotices.length > 0) {
                        wcNotices.forEach(function(notice) {
                            var text = notice.textContent.trim();
                            // Check if it's a coupon-related message
                            if (text.toLowerCase().includes('coupon') || text.toLowerCase().includes('code')) {
                                // Determine if it's an error or success
                                var isError = notice.classList.contains('woocommerce-error');

                                if (isError) {
                                    showError(text);
                                } else {
                                    // Success message - show in green success div
                                    showCouponMessage(text, 'success');
                                }

                                // Hide WooCommerce notice to avoid duplication
                                notice.style.display = 'none';
                            }
                        });
                    }
                }, 100);
            }

            // Re-check after WooCommerce checkout update (AJAX updates)
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
                            try {
                                sessionStorage.setItem('bw_coupon_action', 'true');
                            } catch(e) {}
                        });
                    }

                    updateHasValue();
                }
            });
        }
    }

    /**
     * Transform checkout fields into floating label fields
     */
    function initCheckoutFloatingLabels() {
        console.log('[BW Checkout] Initializing floating labels');

        // Target checkout form fields (including select dropdowns)
        var fieldSelectors = [
            '#billing_first_name',
            '#billing_last_name',
            '#billing_company',
            '#billing_country',
            '#billing_state',
            '#billing_address_1',
            '#billing_address_2',
            '#billing_city',
            '#billing_postcode',
            '#billing_phone',
            '#billing_email',
            '#shipping_first_name',
            '#shipping_last_name',
            '#shipping_company',
            '#shipping_country',
            '#shipping_state',
            '#shipping_address_1',
            '#shipping_address_2',
            '#shipping_city',
            '#shipping_postcode'
        ];

        fieldSelectors.forEach(function(selector) {
            var input = document.querySelector(selector);
            if (!input) return;

            // Skip if already wrapped
            if (input.closest('.bw-field-wrapper')) return;

            // Get field label text
            var fieldRow = input.closest('.form-row');
            if (!fieldRow) return;

            var originalLabel = fieldRow.querySelector('label[for="' + input.id + '"]');
            if (!originalLabel) return;

            // Get clean label text (remove asterisks, abbr, optional text, etc.)
            var labelClone = originalLabel.cloneNode(true);
            // Remove abbr (asterisks), optional spans, and other non-text elements
            var elemsToRemove = labelClone.querySelectorAll('abbr, .optional, .required');
            elemsToRemove.forEach(function(elem) { elem.remove(); });
            var labelText = labelClone.textContent.replace(/\*/g, '').trim();

            if (!labelText) return;

            // Check if this is a Select2 field
            var isSelect2 = input.tagName === 'SELECT' && input.classList.contains('select2-hidden-accessible');
            var elementToWrap = input;

            // For Select2, wrap the .select2-container instead of the hidden select
            if (isSelect2) {
                var select2Container = fieldRow.querySelector('.select2-container');
                if (select2Container) {
                    elementToWrap = select2Container;
                }
            }

            // Skip if element to wrap is already inside a wrapper
            if (elementToWrap.closest('.bw-field-wrapper')) return;

            // Wrap input/select2 with floating label structure
            var wrapper = document.createElement('span');
            wrapper.className = 'bw-field-wrapper';

            // Create floating label
            var floatingLabel = document.createElement('label');
            floatingLabel.className = 'bw-floating-label';
            floatingLabel.setAttribute('for', input.id);
            floatingLabel.textContent = labelText;

            // Insert wrapper before element
            elementToWrap.parentNode.insertBefore(wrapper, elementToWrap);
            wrapper.appendChild(elementToWrap);
            wrapper.appendChild(floatingLabel);

            // For Select2, also move the hidden select into wrapper
            if (isSelect2 && elementToWrap !== input) {
                wrapper.appendChild(input);
            }

            // Hide original label
            if (originalLabel) {
                originalLabel.style.display = 'none';
            }

            // Add has-floating-label class to form-row
            fieldRow.classList.add('bw-has-floating-label');

            // Function to update has-value class
            function updateHasValue() {
                var hasValue = input.value && input.value.trim() !== '';

                if (hasValue) {
                    wrapper.classList.add('has-value');
                } else {
                    wrapper.classList.remove('has-value');
                }
            }

            // Listen to appropriate events based on field type
            if (isSelect2) {
                // Select2 events
                if (window.jQuery) {
                    jQuery(input).on('select2:select select2:clear change', updateHasValue);
                }
            } else {
                // Standard input events
                input.addEventListener('input', updateHasValue);
                input.addEventListener('blur', updateHasValue);
                input.addEventListener('change', updateHasValue);
            }

            // Check initial value
            updateHasValue();
        });

        console.log('[BW Checkout] Floating labels initialized');
    }

    /**
     * Initialize Google Places Autocomplete (if enabled)
     */
    function initGooglePlacesAutocomplete() {
        // Check if Google Maps is enabled
        if (!window.bwGoogleMapsSettings || !window.bwGoogleMapsSettings.enabled) {
            console.log('[BW Checkout] Google Maps not enabled');
            return;
        }

        // Check if Google Maps API is loaded
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            console.warn('[BW Checkout] Google Maps API not loaded');
            return;
        }

        console.log('[BW Checkout] Initializing Google Places Autocomplete');

        var addressInput = document.getElementById('billing_address_1');
        if (!addressInput) {
            console.warn('[BW Checkout] Address input not found');
            return;
        }

        // Get selected country
        var countrySelect = document.querySelector('select[name="billing_country"]');
        var selectedCountry = countrySelect ? countrySelect.value : '';

        // Configure autocomplete options
        var options = {
            types: ['address'],
            fields: ['address_components', 'formatted_address', 'geometry']
        };

        // Add country restriction if enabled and country is selected
        if (window.bwGoogleMapsSettings.restrictToCountry && selectedCountry) {
            options.componentRestrictions = {
                country: selectedCountry.toLowerCase()
            };
        }

        // Create autocomplete instance
        window.bwGoogleAutocomplete = new google.maps.places.Autocomplete(addressInput, options);

        // Listen for place selection
        window.bwGoogleAutocomplete.addListener('place_changed', function() {
            var place = window.bwGoogleAutocomplete.getPlace();
            
            if (!place.address_components) {
                console.warn('[BW Checkout] No address components found');
                return;
            }

            console.log('[BW Checkout] Place selected:', place);

            // Parse address components
            var addressData = {
                street: '',
                city: '',
                postcode: '',
                state: '',
                country: ''
            };

            place.address_components.forEach(function(component) {
                var types = component.types;

                if (types.includes('street_number')) {
                    addressData.street = component.long_name + ' ';
                }
                if (types.includes('route')) {
                    addressData.street += component.long_name;
                }
                if (types.includes('locality') || types.includes('postal_town')) {
                    addressData.city = component.long_name;
                }
                if (types.includes('postal_code')) {
                    addressData.postcode = component.long_name;
                }
                if (types.includes('administrative_area_level_1')) {
                    addressData.state = component.short_name;
                }
                if (types.includes('country')) {
                    addressData.country = component.short_name;
                }
            });

            // Fill address field
            if (addressData.street) {
                addressInput.value = addressData.street.trim();
                jQuery(addressInput).trigger('change');
            }

            // Auto-fill city and postcode if enabled
            if (window.bwGoogleMapsSettings.autoFillCityPostcode) {
                if (addressData.city) {
                    var cityInput = document.getElementById('billing_city');
                    if (cityInput) {
                        cityInput.value = addressData.city;
                        jQuery(cityInput).trigger('change');
                    }
                }

                if (addressData.postcode) {
                    var postcodeInput = document.getElementById('billing_postcode');
                    if (postcodeInput) {
                        postcodeInput.value = addressData.postcode;
                        jQuery(postcodeInput).trigger('change');
                    }
                }
            }

            // Update floating labels for filled fields
            setTimeout(function() {
                initCheckoutFloatingLabels();
            }, 100);

            // Trigger WooCommerce update
            if (window.jQuery) {
                jQuery(document.body).trigger('update_checkout');
            }
        });

        // Update country restriction when country changes
        if (countrySelect && window.bwGoogleMapsSettings.restrictToCountry) {
            jQuery(countrySelect).on('change', function() {
                var newCountry = this.value;
                if (window.bwGoogleAutocomplete && newCountry) {
                    window.bwGoogleAutocomplete.setComponentRestrictions({
                        country: newCountry.toLowerCase()
                    });
                    console.log('[BW Checkout] Google Places country restriction updated:', newCountry);
                }
            });
        }

        console.log('[BW Checkout] Google Places Autocomplete initialized');
    }

    /**
     * Move "Delivery" section heading below newsletter checkbox.
     * This creates a visual separation between Contact section and Delivery section.
     */
    function moveDeliveryHeading() {
        var newsletterField = document.getElementById('bw_subscribe_newsletter_field');
        if (!newsletterField) {
            return;
        }

        // Try multiple selectors to find the Delivery heading
        var deliveryHeading = document.querySelector('.checkout-delivery-title') ||
                             document.querySelector('.bw-checkout-section-heading--delivery') ||
                             document.querySelector('h2.checkout-section-title');

        // If not found by class, try finding by text content
        if (!deliveryHeading) {
            var allHeadings = document.querySelectorAll('h2, h3, .checkout-section-title');
            for (var i = 0; i < allHeadings.length; i++) {
                if (allHeadings[i].textContent.trim().toLowerCase() === 'delivery') {
                    deliveryHeading = allHeadings[i];
                    break;
                }
            }
        }

        if (deliveryHeading) {
            // Add class for styling
            deliveryHeading.classList.add('bw-delivery-section-title');

            // Move the heading (and its wrapper if it has one) after the newsletter field
            var headingToMove = deliveryHeading.closest('.bw-checkout-section-heading') || deliveryHeading;

            // Add class to wrapper if it exists
            if (headingToMove.classList.contains('bw-checkout-section-heading')) {
                headingToMove.classList.add('bw-checkout-section-heading--delivery');
            }

            // Insert after newsletter field
            if (newsletterField.nextSibling) {
                newsletterField.parentNode.insertBefore(headingToMove, newsletterField.nextSibling);
            } else {
                newsletterField.parentNode.appendChild(headingToMove);
            }

            console.log('[BW Checkout] Delivery heading moved below newsletter checkbox');
        }
    }

    /**
     * Detect if order total is 0 and toggle free order UI.
     * Shows free order banner, hides express buttons and divider.
     * Creates banner dynamically if it doesn't exist during AJAX updates.
     */
    function detectFreeOrder() {
        // Try multiple selectors for order total
        var totalElement = document.querySelector('.order-total .woocommerce-Price-amount') ||
                          document.querySelector('.order-total .amount') ||
                          document.querySelector('.cart-subtotal .woocommerce-Price-amount');

        if (!totalElement) {
            console.log('[BW Checkout] Total element not found, cannot detect free order');
            return;
        }

        var totalText = totalElement.textContent || totalElement.innerText || '';
        // Remove currency symbols, spaces, and parse
        var totalValue = parseFloat(totalText.replace(/[^\d.,]/g, '').replace(',', '.'));

        console.log('[BW Checkout] Detected total value:', totalValue);

        var body = document.body;
        var isFree = totalValue === 0 || isNaN(totalValue) && totalText.includes('0');

        // Find or create banner and divider
        var banner = document.querySelector('.bw-free-order-banner');
        var divider = document.querySelector('.bw-express-divider');
        var expressCheckout = document.querySelector('#wc-stripe-express-checkout-element');

        if (isFree) {
            body.classList.add('bw-free-order');

            // Create banner if it doesn't exist
            if (!banner) {
                banner = createFreeOrderBanner();

                // Insert in same position as divider if divider exists
                if (divider && divider.parentNode) {
                    divider.parentNode.insertBefore(banner, divider);
                    console.log('[BW Checkout] Free order banner created before divider');
                } else {
                    // Otherwise insert before customer details or express checkout
                    var insertPoint = document.querySelector('.woocommerce-billing-fields') ||
                                     document.querySelector('#customer_details') ||
                                     expressCheckout;

                    if (insertPoint && insertPoint.parentNode) {
                        insertPoint.parentNode.insertBefore(banner, insertPoint);
                        console.log('[BW Checkout] Free order banner created and inserted before customer details');
                    }
                }
            }

            // Show banner if it exists
            if (banner) {
                banner.classList.add('bw-free-order-active');
                banner.style.display = 'block';
            }

            // Hide divider if it exists
            if (divider) {
                divider.style.display = 'none';
            }

            // Update button text for free order
            updatePlaceOrderButton(true);

            console.log('[BW Checkout] Free order detected, UI updated');
        } else {
            body.classList.remove('bw-free-order');

            // Hide banner if it exists
            if (banner) {
                banner.classList.remove('bw-free-order-active');
                banner.style.display = 'none';
            }

            // Show divider if it exists
            if (divider) {
                divider.style.display = '';
            }

            // Restore original button text
            updatePlaceOrderButton(false);

            console.log('[BW Checkout] Paid order detected, UI updated');
        }
    }

    /**
     * Update Place Order button text based on free order state.
     * Stores original button text on first call to restore it later.
     *
     * @param {boolean} isFree - Whether order is free (total = 0)
     */
    function updatePlaceOrderButton(isFree) {
        // Find Place Order button
        var button = document.querySelector('#place_order') ||
                    document.querySelector('.woocommerce-checkout button[type="submit"]');

        if (!button) {
            return;
        }

        // Store original button text on first call
        if (!button.hasAttribute('data-original-text')) {
            var originalText = button.textContent || button.innerText || button.value;
            button.setAttribute('data-original-text', originalText);
            console.log('[BW Checkout] Stored original button text:', originalText);
        }

        if (isFree) {
            // Set free order button text
            var freeText = window.bwCheckoutParams && window.bwCheckoutParams.freeOrderButtonText
                ? window.bwCheckoutParams.freeOrderButtonText
                : 'Confirm free order';

            button.textContent = freeText;
            if (button.value) {
                button.value = freeText;
            }
            console.log('[BW Checkout] Button text changed to:', freeText);
        } else {
            // Restore original button text
            var originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.textContent = originalText;
                if (button.value) {
                    button.value = originalText;
                }
                console.log('[BW Checkout] Button text restored to:', originalText);
            }
        }
    }

    /**
     * Create free order banner HTML dynamically.
     *
     * @return {HTMLElement}
     */
    function createFreeOrderBanner() {
        var banner = document.createElement('div');
        banner.className = 'bw-free-order-banner bw-free-order-active';

        var content = document.createElement('div');
        content.className = 'bw-free-order-banner__content';

        var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('class', 'bw-free-order-banner__icon');
        icon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        icon.setAttribute('width', '24');
        icon.setAttribute('height', '24');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '2');
        icon.setAttribute('stroke-linecap', 'round');
        icon.setAttribute('stroke-linejoin', 'round');

        var path1 = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path1.setAttribute('d', 'M22 11.08V12a10 10 0 1 1-5.93-9.14');
        icon.appendChild(path1);

        var polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        polyline.setAttribute('points', '22 4 12 14.01 9 11.01');
        icon.appendChild(polyline);

        var message = document.createElement('div');
        // Get message from localized data or use default
        var messageText = window.bwCheckoutParams && window.bwCheckoutParams.freeOrderMessage
            ? window.bwCheckoutParams.freeOrderMessage
            : 'Your order is free. Complete your details and click Place order.';

        // Check if message contains HTML tags
        if (messageText.indexOf('<') !== -1) {
            message.innerHTML = messageText;
        } else {
            var p = document.createElement('p');
            p.textContent = messageText;
            message.appendChild(p);
        }

        content.appendChild(icon);
        content.appendChild(message);
        banner.appendChild(content);

        return banner;
    }

    /**
     * Hide section headings based on body classes.
     */
    function hideSectionHeadings() {
        var body = document.body;

        // Hide Billing Details heading
        if (body.classList.contains('bw-hide-billing-heading')) {
            var billingHeadings = document.querySelectorAll('.woocommerce-billing-fields h3, .woocommerce-billing-fields__field-wrapper h3');
            billingHeadings.forEach(function(heading) {
                var text = heading.textContent.trim().toLowerCase();
                if (text.includes('billing') || text.includes('fatturazione') || text.includes('dati di fatturazione')) {
                    heading.style.display = 'none';
                }
            });
        }

        // Hide Additional Information heading
        if (body.classList.contains('bw-hide-additional-heading')) {
            var additionalHeadings = document.querySelectorAll('.woocommerce-additional-fields h3, .woocommerce-additional-fields__field-wrapper h3');
            additionalHeadings.forEach(function(heading) {
                var text = heading.textContent.trim().toLowerCase();
                if (text.includes('additional') || text.includes('aggiuntiv') || text.includes('note')) {
                    heading.style.display = 'none';
                }
            });
        }
    }

    /**
     * Initialize mobile order summary accordion.
     * Creates toggle bar and wraps right column for mobile view.
     * ONLY runs on mobile/tablet (≤900px).
     */
    function initMobileOrderSummary() {
        console.log('[BW Checkout] Initializing mobile order summary accordion');

        // Only run on mobile/tablet screens (≤900px)
        if (window.innerWidth > 900) {
            console.log('[BW Checkout] Desktop view detected, skipping mobile accordion');
            return;
        }

        var rightColumn = document.querySelector('.bw-checkout-right');
        if (!rightColumn) {
            console.log('[BW Checkout] Right column not found, skipping mobile summary init');
            return;
        }

        // Check if already initialized (skip if accordion exists and is in the DOM)
        var existingToggle = document.querySelector('.bw-order-summary-toggle');
        var existingPanel = document.querySelector('#bw-order-summary-panel');

        if (existingToggle && existingPanel && document.body.contains(existingToggle)) {
            console.log('[BW Checkout] Mobile accordion already exists in DOM, skipping');
            return;
        }

        // Clean up orphaned elements if they exist but not in DOM
        if (existingToggle && !document.body.contains(existingToggle)) {
            existingToggle.remove();
        }
        if (existingPanel && !document.body.contains(existingPanel)) {
            existingPanel.remove();
        }

        // Create toggle bar
        var toggleBar = document.createElement('button');
        toggleBar.type = 'button';
        toggleBar.className = 'bw-order-summary-toggle';
        toggleBar.setAttribute('aria-expanded', 'false');
        toggleBar.setAttribute('aria-controls', 'bw-order-summary-panel');

        var toggleLabel = document.createElement('span');
        toggleLabel.className = 'bw-order-summary-label';
        toggleLabel.innerHTML = '<span class="bw-order-summary-text">Order summary</span><svg class="bw-order-summary-icon" width="12" height="8" viewBox="0 0 12 8" fill="none"><path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        var toggleTotal = document.createElement('span');
        toggleTotal.className = 'bw-order-summary-total';
        toggleTotal.textContent = '...';

        toggleBar.appendChild(toggleLabel);
        toggleBar.appendChild(toggleTotal);

        // Create panel with CLONED right column
        var panel = document.createElement('div');
        panel.id = 'bw-order-summary-panel';
        panel.className = 'bw-order-summary-panel';
        panel.setAttribute('aria-hidden', 'true');

        // Find the form element
        var form = document.querySelector('form.checkout');
        if (!form) {
            console.log('[BW Checkout] Checkout form not found');
            return;
        }

        console.log('[BW Checkout] Form found');

        // Insert toggle bar BEFORE the form (as sibling, not child)
        form.parentNode.insertBefore(toggleBar, form);
        console.log('[BW Checkout] Toggle bar inserted before form');

        // Insert panel after toggle bar (still before form)
        form.parentNode.insertBefore(panel, form);
        console.log('[BW Checkout] Panel inserted before form');

        // Add test content to see if toggle works
        var testContent = document.createElement('div');
        testContent.style.padding = '20px';
        testContent.style.backgroundColor = '#f0f0f0';
        testContent.innerHTML = '<h3 style="margin: 0 0 10px 0;">TEST CONTENT</h3><p style="margin: 0;">This is test content to verify the toggle is working correctly. If you can see this text when clicking the toggle, the accordion mechanism is functioning properly.</p>';
        panel.appendChild(testContent);

        // CLONE right column into panel (keep original in grid)
        var rightColumnClone = rightColumn.cloneNode(true);
        panel.appendChild(rightColumnClone);
        console.log('[BW Checkout] Right column CLONED into panel (original remains in grid)');
        console.log('[BW Checkout] Original right column will be hidden via CSS on mobile');
        console.log('[BW Checkout] TEST CONTENT added to panel');

        // Add toggle functionality
        toggleBar.addEventListener('click', function() {
            var isExpanded = toggleBar.getAttribute('aria-expanded') === 'true';
            toggleBar.setAttribute('aria-expanded', !isExpanded);
            panel.setAttribute('aria-hidden', isExpanded);

            if (!isExpanded) {
                panel.style.maxHeight = panel.scrollHeight + 'px';
            } else {
                panel.style.maxHeight = '0';
            }
        });

        // Keyboard support
        toggleBar.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleBar.click();
            }
        });

        // Add mutation observer to detect if toggle gets removed from DOM
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.removedNodes.forEach(function(node) {
                    if (node === toggleBar || node === panel) {
                        console.log('[BW Checkout] Toggle/panel removed from DOM, reinserting');
                        if (!document.body.contains(toggleBar)) {
                            form.parentNode.insertBefore(toggleBar, form);
                        }
                        if (!document.body.contains(panel)) {
                            form.parentNode.insertBefore(panel, form);
                        }
                    }
                });
            });
        });

        // Observe the form's parent for removed children
        observer.observe(form.parentNode, { childList: true });

        console.log('[BW Checkout] Mobile order summary accordion initialized with mutation observer');
    }

    /**
     * Add mobile total row before Place Order button.
     * ONLY runs on mobile/tablet (≤900px).
     */
    function addMobileTotalRow() {
        console.log('[BW Checkout] Adding mobile total row');

        // Only run on mobile/tablet screens (≤900px)
        if (window.innerWidth > 900) {
            console.log('[BW Checkout] Desktop view detected, skipping mobile total row');
            return;
        }

        // Remove existing mobile total row before recreating
        var existingTotalRow = document.querySelector('.bw-mobile-total-row');
        if (existingTotalRow) {
            existingTotalRow.remove();
        }

        var placeOrderButton = document.querySelector('#place_order');
        if (!placeOrderButton) {
            console.log('[BW Checkout] Place order button not found');
            return;
        }

        // Create mobile total row
        var totalRow = document.createElement('div');
        totalRow.className = 'bw-mobile-total-row';

        var totalLabel = document.createElement('span');
        totalLabel.className = 'bw-mobile-total-label';
        totalLabel.textContent = 'Total';

        var totalAmount = document.createElement('span');
        totalAmount.className = 'bw-mobile-total-amount';
        totalAmount.textContent = '...';

        totalRow.appendChild(totalLabel);
        totalRow.appendChild(totalAmount);

        // Insert before place order button
        placeOrderButton.parentElement.insertBefore(totalRow, placeOrderButton);

        console.log('[BW Checkout] Mobile total row added');
    }

    /**
     * Update total amounts in toggle bar and mobile total row.
     * Also refreshes the cloned right column content.
     */
    function updateMobileTotals() {
        // Only update if mobile accordion exists
        var panel = document.getElementById('bw-order-summary-panel');
        if (!panel) {
            return;
        }

        // Find total amount from original right column in grid
        var rightColumn = document.querySelector('.bw-checkout-grid .bw-checkout-right');
        if (!rightColumn) {
            return;
        }

        var totalElement = rightColumn.querySelector('.order-total .woocommerce-Price-amount, .order-total .amount');
        if (!totalElement) {
            console.log('[BW Checkout] Total element not found');
            return;
        }

        var totalText = totalElement.textContent.trim();
        console.log('[BW Checkout] Updating mobile totals to:', totalText);

        // Update toggle bar total
        var toggleTotal = document.querySelector('.bw-order-summary-total');
        if (toggleTotal) {
            toggleTotal.textContent = totalText;
        }

        // Update mobile total row
        var mobileTotalAmount = document.querySelector('.bw-mobile-total-amount');
        if (mobileTotalAmount) {
            mobileTotalAmount.textContent = totalText;
        }

        // Update cloned right column in panel with fresh content
        var oldClone = panel.querySelector('.bw-checkout-right');
        if (oldClone && rightColumn) {
            var newClone = rightColumn.cloneNode(true);
            panel.replaceChild(newClone, oldClone);
            console.log('[BW Checkout] Refreshed cloned right column in panel');
        }
    }

    console.log('[BW Checkout] Script reached end, about to initialize. DOM readyState:', document.readyState);

    // Initialize all functions when DOM is ready
    if (document.readyState === 'loading') {
        console.log('[BW Checkout] DOM still loading, waiting for DOMContentLoaded');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[BW Checkout] DOMContentLoaded fired, initializing now');
            initCustomSticky();
            observeStripeErrors();
            initFloatingLabel();
            initCheckoutFloatingLabels();
            initGooglePlacesAutocomplete();
            moveDeliveryHeading();
            detectFreeOrder();
            hideSectionHeadings();
            initMobileOrderSummary();
            addMobileTotalRow();
            updateMobileTotals();
        });
    } else {
        console.log('[BW Checkout] DOM already loaded, initializing immediately');
        initCustomSticky();
        observeStripeErrors();
        initFloatingLabel();
        initCheckoutFloatingLabels();
        initGooglePlacesAutocomplete();
        moveDeliveryHeading();
        detectFreeOrder();
        hideSectionHeadings();
        initMobileOrderSummary();
        addMobileTotalRow();
        updateMobileTotals();
    }

    // Re-initialize floating labels and detect free order after WooCommerce AJAX update
    if (window.jQuery) {
        jQuery(document.body).on('updated_checkout', function() {
            console.log('[BW Checkout] Checkout updated, refreshing components');

            // Detect free order immediately without delay
            detectFreeOrder();

            // Re-initialize mobile accordion if it was removed (only creates if missing)
            initMobileOrderSummary();

            // Re-add mobile total row (gets removed by WooCommerce form update)
            addMobileTotalRow();

            // Update mobile totals and refresh cloned content immediately
            updateMobileTotals();

            // Also re-run after a small delay for elements that render slower
            setTimeout(function() {
                initCheckoutFloatingLabels();
                initGooglePlacesAutocomplete();
                moveDeliveryHeading();
                detectFreeOrder();
                hideSectionHeadings();
                updateMobileTotals();
            }, 50);
        });
    }

    console.log('[BW Checkout] Script execution completed');
})();
