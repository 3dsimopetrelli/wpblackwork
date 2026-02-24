/**
 * Stripe UPE Cleaner
 * Force-hides the "Card" accordion header that Stripe UPE injects in the
 * parent DOM (outside the iframe) in newer Stripe versions.
 *
 * Why this exists:
 *   Stripe UPE renders a <div class="p-PaymentAccordionButtonView"> directly
 *   into the page DOM — not inside the Stripe iframe — when using the
 *   accordion/tabs payment element. This creates a redundant "Card" row with
 *   a card brand icon visible above the input fields, which conflicts with
 *   the BlackWork custom accordion UI.
 *
 * Two-layer approach (belt-and-suspenders):
 *   1. CSS in bw-payment-methods.css: display:none on [class*="PaymentAccordionButtonView"]
 *   2. This JS: MutationObserver sets inline style directly, covering any
 *      future Stripe version that might change the class name.
 *
 * Selectors used (must stay in sync with bw-payment-methods.css):
 *   [class*="PaymentAccordionButtonView"]
 *   [data-testid="payment-accordion-wrapper"]
 */
(function($) {
    'use strict';

    var SELECTORS = [
        '[class*="PaymentAccordionButtonView"]',
        '[data-testid="payment-accordion-wrapper"]'
    ];

    function hideStripeCardRow() {
        var found = false;
        for (var i = 0; i < SELECTORS.length; i++) {
            var elements = document.querySelectorAll(SELECTORS[i]);
            for (var j = 0; j < elements.length; j++) {
                elements[j].style.setProperty('display', 'none', 'important');
                found = true;
            }
        }
        return found;
    }

    function initObserver() {
        // Try immediately first
        hideStripeCardRow();

        // Then watch for Stripe injecting the element asynchronously
        var observer = new MutationObserver(function() {
            hideStripeCardRow();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Fallback: retry after Stripe typically finishes rendering
        setTimeout(hideStripeCardRow, 500);
        setTimeout(hideStripeCardRow, 1500);
    }

    $(document).ready(function() {
        initObserver();
    });

    // Re-run after WooCommerce checkout AJAX updates
    $(document.body).on('updated_checkout', function() {
        setTimeout(hideStripeCardRow, 300);
        setTimeout(hideStripeCardRow, 800);
    });

})(jQuery);
