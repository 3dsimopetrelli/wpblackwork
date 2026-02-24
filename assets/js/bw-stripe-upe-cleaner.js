/**
 * Stripe UPE Cleaner
 * Force-hides the "Card" and "Klarna" accordion rows that Stripe UPE injects
 * in the parent DOM (outside the iframe) in newer Stripe versions.
 *
 * Why this exists:
 *   Stripe UPE renders <div class="p-AccordionButton" data-value="card"> directly
 *   into the page DOM — not inside the Stripe iframe — when using the
 *   accordion/tabs payment element. This creates a redundant "Card" row with
 *   a card brand icon visible above the input fields, which conflicts with
 *   the BlackWork custom accordion UI.
 *
 * Three-layer approach (belt, suspenders, and duct tape):
 *   1. bw-checkout.css: display:none on .p-AccordionButton[data-value] selectors
 *      (specificity 0,3,0 — same level as Stripe's own bundled CSS)
 *   2. bw-payment-methods.css: display:none on [class*="PaymentAccordionButtonView"]
 *      (broad fallback for class-name variations across Stripe versions)
 *   3. This JS: inline style.setProperty('display','none','important') + per-element
 *      MutationObserver on 'style' attribute so Stripe can't re-show via JS after hide.
 *
 * Selectors used (must stay in sync with bw-checkout.css and bw-payment-methods.css):
 *   [data-value="card"], [data-value="klarna"]  — outer p-AccordionButton containers
 *   [class*="PaymentAccordionButtonView"]        — inner label/view elements
 *   [data-testid="payment-accordion-wrapper"]    — wrapper testid (fallback)
 */
(function($) {
    'use strict';

    var SELECTORS = [
        '[data-value="card"]',
        '[data-value="klarna"]',
        '[class*="PaymentAccordionButtonView"]',
        '[data-testid="payment-accordion-wrapper"]'
    ];

    // Per-element observers so Stripe can't re-show by mutating the style attr.
    var watchedElements = new WeakSet ? new WeakSet() : null;

    function forceHideElement(el) {
        el.style.setProperty('display', 'none', 'important');

        // Attach a per-element attribute observer only once per element.
        if (watchedElements && !watchedElements.has(el)) {
            watchedElements.add(el);
            var attrObserver = new MutationObserver(function() {
                if (el.style.display !== 'none') {
                    el.style.setProperty('display', 'none', 'important');
                }
            });
            attrObserver.observe(el, { attributes: true, attributeFilter: ['style'] });
        }
    }

    function hideStripeCardRow() {
        for (var i = 0; i < SELECTORS.length; i++) {
            var elements = document.querySelectorAll(SELECTORS[i]);
            for (var j = 0; j < elements.length; j++) {
                forceHideElement(elements[j]);
            }
        }
    }

    function initObserver() {
        // Try immediately first.
        hideStripeCardRow();

        // Watch for Stripe injecting the element asynchronously (childList changes).
        var domObserver = new MutationObserver(function() {
            hideStripeCardRow();
        });

        domObserver.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Fallback retries after Stripe typically finishes rendering.
        setTimeout(hideStripeCardRow, 500);
        setTimeout(hideStripeCardRow, 1500);
    }

    $(document).ready(function() {
        initObserver();
    });

    // Re-run after WooCommerce checkout AJAX updates.
    $(document.body).on('updated_checkout', function() {
        setTimeout(hideStripeCardRow, 300);
        setTimeout(hideStripeCardRow, 800);
    });

})(jQuery);
