/**
 * Stripe UPE Cleaner
 * Hides the "Card" and "Klarna" labels in Stripe UPE to show clean form
 */
(function($) {
    'use strict';

    /**
     * Inject CSS into Stripe iframe to hide payment method labels
     */
    function injectStripeIframeStyles(iframe) {
        try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            if (!iframeDoc) return;

            // Check if styles already injected
            if (iframeDoc.getElementById('bw-stripe-cleaner-styles')) return;

            const style = iframeDoc.createElement('style');
            style.id = 'bw-stripe-cleaner-styles';
            style.textContent = `
                /* Hide all payment method labels (Card, Klarna) */
                .p-PaymentMethodHeader,
                .p-MethodSelector,
                .p-MethodChooser,
                [class*="PaymentMethodHeader"],
                [class*="MethodSelector"],
                [class*="TabLabel"],
                .Label--paymentMethod,
                .p-PaymentMethodSelector {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    overflow: hidden !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                /* Hide Klarna section entirely when inside card payment */
                [data-testid="klarna-payment-method"],
                [class*="klarna"],
                .p-PaymentMethod--klarna {
                    display: none !important;
                }
            `;
            iframeDoc.head.appendChild(style);
        } catch (e) {
            // Cross-origin iframe - cannot inject styles
        }
    }

    /**
     * Hide payment method labels using DOM manipulation and CSS
     */
    function hideStripePaymentMethodLabels() {
        // Add class to enable external CSS targeting
        const stripeElement = document.querySelector('.wc-stripe-upe-element');
        if (stripeElement) {
            stripeElement.classList.add('bw-stripe-upe-cleaned');
        }

        // Try to inject styles into Stripe iframes
        const iframes = document.querySelectorAll('.wc-stripe-upe-element iframe, #wc-stripe-payment-element iframe');
        iframes.forEach(injectStripeIframeStyles);

        // Add external CSS for elements outside iframe
        if (!document.getElementById('bw-stripe-label-hider')) {
            const style = document.createElement('style');
            style.id = 'bw-stripe-label-hider';
            style.textContent = `
                /* Hide Card and Klarna labels in Stripe UPE */
                .wc-stripe-upe-element .Label,
                .wc-stripe-upe-element [class*="Label"],
                .wc-stripe-upe-element [class*="PaymentMethod"]:not(input):not(.Input) > span:first-child,
                #wc-stripe-payment-element .Label,
                #wc-stripe-payment-element [class*="Label"] {
                    display: none !important;
                }
                /* Ensure only card fields show, hide Klarna picker if present */
                .bw-stripe-upe-cleaned [data-payment-method="klarna"],
                .bw-stripe-upe-cleaned .p-PaymentMethod--klarna {
                    display: none !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Main initialization with MutationObserver
     */
    function initStripeCleanup() {
        // Run immediately
        hideStripePaymentMethodLabels();

        // Watch for Stripe elements being added
        const observer = new MutationObserver(function(mutations) {
            let shouldRun = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            if (node.classList && (
                                node.classList.contains('wc-stripe-upe-element') ||
                                node.classList.contains('StripeElement') ||
                                node.querySelector && node.querySelector('.wc-stripe-upe-element, .StripeElement, iframe')
                            )) {
                                shouldRun = true;
                            }
                        }
                    });
                }
            });
            if (shouldRun) {
                setTimeout(hideStripePaymentMethodLabels, 100);
                setTimeout(hideStripePaymentMethodLabels, 500);
                setTimeout(hideStripePaymentMethodLabels, 1000);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Multiple attempts for slow-loading Stripe elements
        [300, 600, 1000, 2000, 3000].forEach(function(delay) {
            setTimeout(hideStripePaymentMethodLabels, delay);
        });
    }

    // Run on page load
    $(document).ready(function() {
        initStripeCleanup();
    });

    // Re-run when checkout updates (AJAX)
    $(document.body).on('updated_checkout', function() {
        setTimeout(hideStripePaymentMethodLabels, 100);
        setTimeout(hideStripePaymentMethodLabels, 500);
        setTimeout(hideStripePaymentMethodLabels, 1000);
    });

})(jQuery);
