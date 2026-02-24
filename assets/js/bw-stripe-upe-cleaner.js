/**
 * Stripe UPE Cleaner
 * Force-hides the "Card"/"Klarna" accordion rows Stripe UPE injects in the
 * page DOM (outside the iframe) in newer Stripe versions.
 *
 * Why CSS alone is not reliable:
 *   Stripe.js dynamically injects its own CSS (elements-in-*.css) AFTER our
 *   static stylesheet loads. When two !important rules have equal specificity,
 *   the LATER source wins — Stripe's dynamic CSS wins over ours.
 *
 * The only approach that beats any CSS: inline style via style.setProperty,
 * which has the highest possible cascade priority (inline > !important in sheets).
 *
 * Three layers:
 *   1. CSS in bw-checkout.css + bw-payment-methods.css — catches SSR/no-JS cases
 *   2. This JS: inline style.setProperty on match + per-element style-attr observer
 *      so Stripe's own JS cannot re-show the element after we hide it
 *   3. setInterval safety net for the first 4 seconds after page load
 *
 * DOM structure (confirmed via DevTools):
 *   .p-Accordion
 *     .p-AccordionItem
 *       .p-AccordionButton[data-value="card"]   ← HIDE THIS ENTIRE NODE
 *         .p-AccordionButtonContent
 *           .p-PaymentAccordionButtonView       ← also directly targeted
 *             .p-PaymentAccordionButtonIconContainer  (card icon)
 *             .p-PaymentAccordionButtonText
 *               .p-HeightObserver
 *                 <div>Card</div>
 *       .p-AccordionPanel#card                  ← keep visible (contains card fields)
 */
(function ($) {
    'use strict';

    /* Selectors to hide — every element matching any of these gets display:none inline. */
    var SELECTORS = [
        '.p-AccordionButton[data-value="card"]',
        '.p-AccordionButton[data-value="klarna"]',
        '[class*="PaymentAccordionButtonView"]',
        '[data-testid="payment-accordion-wrapper"]'
    ];

    /* WeakSet to track which elements already have a per-element observer. */
    var watched = (typeof WeakSet !== 'undefined') ? new WeakSet() : null;

    function forceHide(el) {
        el.style.setProperty('display', 'none', 'important');

        /* Per-element observer: catch Stripe re-showing via style attribute mutation. */
        if (watched && !watched.has(el)) {
            watched.add(el);
            var obs = new MutationObserver(function () {
                if (el.style.display !== 'none') {
                    el.style.setProperty('display', 'none', 'important');
                }
            });
            obs.observe(el, { attributes: true, attributeFilter: ['style'] });
        }
    }

    function hideAll() {
        for (var i = 0; i < SELECTORS.length; i++) {
            var nodes = document.querySelectorAll(SELECTORS[i]);
            for (var j = 0; j < nodes.length; j++) {
                forceHide(nodes[j]);
            }
        }
    }

    /* Global DOM observer: catch elements added asynchronously by Stripe. */
    function initDomObserver() {
        if (!window.MutationObserver) { return; }
        new MutationObserver(function () {
            hideAll();
        }).observe(document.body, { childList: true, subtree: true });
    }

    /* setInterval safety net: Stripe may show elements between our MO callbacks. */
    function startPolling() {
        var ticks = 0;
        var interval = setInterval(function () {
            hideAll();
            ticks++;
            if (ticks >= 20) { clearInterval(interval); } /* stop after 4 s (20 × 200 ms) */
        }, 200);
    }

    $(document).ready(function () {
        hideAll();
        initDomObserver();
        startPolling();

        /* Extra passes at common Stripe render milestones. */
        setTimeout(hideAll, 500);
        setTimeout(hideAll, 1000);
        setTimeout(hideAll, 2000);
    });

    /* Re-run after WooCommerce checkout AJAX updates. */
    $(document.body).on('updated_checkout', function () {
        hideAll();
        startPolling();
        setTimeout(hideAll, 500);
    });

})(jQuery);
