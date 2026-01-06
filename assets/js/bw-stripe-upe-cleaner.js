/**
 * Stripe UPE Cleaner
 * Hides the "Card" accordion header in Stripe UPE to show only input fields
 */
(function($) {
    'use strict';

    // Wait for Stripe UPE to load and try to hide the Card header
    function hideStripeCardHeader() {
        // Use MutationObserver to watch for Stripe iframe changes
        const observer = new MutationObserver(function(mutations) {
            // Find the Stripe UPE element
            const stripeElement = document.querySelector('.wc-stripe-upe-element');

            if (stripeElement) {
                // Add a class to enable CSS targeting
                stripeElement.classList.add('bw-stripe-upe-cleaned');
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
                stripeElement.classList.add('bw-stripe-upe-cleaned');
            }
        }, 500);
    }

    // Run on page load
    $(document).ready(function() {
        hideStripeCardHeader();
    });

    // Re-run when checkout updates (AJAX)
    $(document.body).on('updated_checkout', function() {
        setTimeout(hideStripeCardHeader, 300);
    });

})(jQuery);
