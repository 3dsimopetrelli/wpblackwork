/**
 * Stripe Elements Custom Styling - Shopify Style
 *
 * Configures Stripe Elements appearance to match Shopify checkout design.
 * This script intercepts Stripe initialization and applies custom styling.
 */

(function() {
    'use strict';

    /**
     * Shopify-style configuration for Stripe Elements
     */
    const shopifyStyle = {
        base: {
            fontSize: '15px',
            color: '#1f2937',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            fontSmoothing: 'antialiased',
            '::placeholder': {
                color: '#9ca3af'
            },
            iconColor: '#6b7280',
        },
        invalid: {
            color: '#991b1b',
            iconColor: '#991b1b',
        },
        complete: {
            iconColor: '#27ae60',
        }
    };

    /**
     * Appearance API configuration (for newer Stripe versions)
     */
    const shopifyAppearance = {
        theme: 'flat',
        variables: {
            colorPrimary: '#000000',
            colorBackground: '#ffffff',
            colorText: '#1f2937',
            colorDanger: '#991b1b',
            colorSuccess: '#27ae60',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            fontSizeBase: '15px',
            fontWeightNormal: '400',
            fontWeightMedium: '500',
            borderRadius: '8px',
            spacingUnit: '4px',
        },
        rules: {
            '.Input': {
                border: '1px solid #d1d5db',
                boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                padding: '16px 18px',
                transition: 'border-color 0.2s ease, box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
            },
            '.Input:hover': {
                borderColor: '#9ca3af',
            },
            '.Input:focus': {
                borderColor: '#3b82f6',
                boxShadow: '0 0 0 3px rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                outline: 'none',
            },
            '.Input--invalid': {
                borderColor: '#fecaca',
                backgroundColor: '#fef2f2',
            },
            '.Label': {
                display: 'none', // Hide labels like Shopify
            },
        },
    };

    /**
     * Override Stripe createElement to inject custom styling
     */
    function overrideStripeCreateElement() {
        // Check if Stripe is loaded
        if (typeof Stripe === 'undefined') {
            // Retry after a short delay
            setTimeout(overrideStripeCreateElement, 100);
            return;
        }

        // Store original createElement method
        const originalCreate = Stripe.prototype.elements;

        // Override elements method
        Stripe.prototype.elements = function(options) {
            // Merge our appearance config with existing options
            const mergedOptions = {
                ...options,
                appearance: shopifyAppearance,
            };

            return originalCreate.call(this, mergedOptions);
        };

        console.log('Stripe Elements styling configured (Shopify style)');
    }

    /**
     * Apply custom styling to Stripe Elements after they're mounted
     */
    function applyStripeElementsCSS() {
        // Add custom CSS class to Stripe containers
        const stripeContainers = document.querySelectorAll('.wc-stripe-elements-field, .wc-stripe-iban-element-field');

        stripeContainers.forEach(function(container) {
            container.classList.add('bw-stripe-field');
        });
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        overrideStripeCreateElement();
        applyStripeElementsCSS();

        // Re-apply on checkout update
        if (window.jQuery) {
            window.jQuery(document.body).on('updated_checkout', function() {
                setTimeout(applyStripeElementsCSS, 100);
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
