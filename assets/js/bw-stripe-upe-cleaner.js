/**
 * Stripe UPE Cleaner
 * Hides the "Card" accordion header in Stripe UPE to show only input fields
 */
(function($) {
    'use strict';

    // Wait for Stripe UPE to load
    function hideStripeCardHeader() {
        // Use MutationObserver to watch for Stripe iframe changes
        const observer = new MutationObserver(function(mutations) {
            // Find the Stripe UPE element
            const stripeElement = document.querySelector('.wc-stripe-upe-element');

            if (stripeElement) {
                // Try to hide elements via CSS injection
                const style = document.createElement('style');
                style.textContent = `
                    .wc-stripe-upe-element {
                        overflow: hidden !important;
                    }
                    .wc-stripe-upe-element > div:first-child {
                        margin-top: -60px !important;
                    }
                `;

                // Append style if not already added
                if (!document.getElementById('bw-stripe-upe-cleaner-style')) {
                    style.id = 'bw-stripe-upe-cleaner-style';
                    document.head.appendChild(style);
                }
            }
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Also try immediately if element already exists
        setTimeout(function() {
            const stripeElement = document.querySelector('.wc-stripe-upe-element');
            if (stripeElement) {
                const style = document.createElement('style');
                style.textContent = `
                    .wc-stripe-upe-element {
                        overflow: hidden !important;
                    }
                    .wc-stripe-upe-element > div:first-child {
                        margin-top: -60px !important;
                    }
                `;

                if (!document.getElementById('bw-stripe-upe-cleaner-style')) {
                    style.id = 'bw-stripe-upe-cleaner-style';
                    document.head.appendChild(style);
                }
            }
        }, 1000);
    }

    // Run on page load
    $(document).ready(function() {
        hideStripeCardHeader();
    });

    // Re-run when checkout updates (AJAX)
    $(document.body).on('updated_checkout', function() {
        hideStripeCardHeader();
    });

})(jQuery);
