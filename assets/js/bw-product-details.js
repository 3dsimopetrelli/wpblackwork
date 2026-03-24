/**
 * BW Product Details Widget — Accordion
 *
 * Premium, Shopify-style accordion behavior.
 * Uses actual measured heights (not max-height hacks) for silky animation.
 *
 * Responsive logic via matchMedia:
 *   .bw-biblio-accordion--mobile  → accordion active on ≤ 1024 px
 *   .bw-biblio-accordion--desktop → accordion active on ≥ 1025 px
 * If neither class is present, accordion is active on all breakpoints.
 */
(function ($) {
    'use strict';

    // ---------------------------------------------------------------------------
    // Animation constants — tuned for a premium, settled feel.
    // Opening uses an "expo out" curve: content snaps confidently into place.
    // Closing uses a standard ease-in-out: controlled, not abrupt.
    // ---------------------------------------------------------------------------
    var OPEN_MS        = 380;
    var CLOSE_MS       = 300;
    var OPEN_EASING    = 'cubic-bezier(0.16, 1, 0.3, 1)';   // expo-out / Shopify-style
    var CLOSE_EASING   = 'cubic-bezier(0.4, 0, 0.2, 1)';    // standard material ease
    var BREAKPOINT     = '(min-width: 1025px)';

    // ---------------------------------------------------------------------------
    // initBiblioAccordion
    // ---------------------------------------------------------------------------
    function initBiblioAccordion($widget) {
        // Guard: only accordions, only once.
        if (!$widget.hasClass('bw-biblio-accordion')) { return; }
        if ($widget.data('bwBiblioAcc'))              { return; }
        $widget.data('bwBiblioAcc', true);

        var hasMobile  = $widget.hasClass('bw-biblio-accordion--mobile');
        var hasDesktop = $widget.hasClass('bw-biblio-accordion--desktop');

        // If neither sub-toggle is set, treat both as enabled.
        if (!hasMobile && !hasDesktop) {
            hasMobile  = true;
            hasDesktop = true;
        }

        var $trigger = $widget.find('> .bw-biblio-accordion__trigger');
        var $body    = $widget.find('> .bw-biblio-accordion__body');
        var bodyEl   = $body[0];
        var mql      = window.matchMedia(BREAKPOINT);
        var timer    = null;

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------

        function clearTransition() {
            bodyEl.style.transition = '';
        }

        function buildTransition(durationMs, easing, opacityMs) {
            return [
                'height '  + durationMs + 'ms ' + easing,
                'opacity ' + opacityMs  + 'ms ease'
            ].join(', ');
        }

        // -----------------------------------------------------------------------
        // State: activate / deactivate
        // -----------------------------------------------------------------------

        function activate() {
            clearTimeout(timer);
            clearTransition();
            $widget.addClass('bw-js-accordion-active').removeClass('is-open');
            $trigger.attr('aria-expanded', 'false');
            $body.attr('aria-hidden', 'true');
            // Set initial closed state without any transition.
            bodyEl.style.height   = '0';
            bodyEl.style.overflow = 'hidden';
            bodyEl.style.opacity  = '0';
        }

        function deactivate() {
            clearTimeout(timer);
            clearTransition();
            $widget.removeClass('bw-js-accordion-active is-open');
            // Remove all inline styles → body flows naturally.
            bodyEl.style.cssText = '';
            $trigger.attr('aria-expanded', 'false');
            $body.attr('aria-hidden', 'false');
        }

        // -----------------------------------------------------------------------
        // Animation: open / close
        // -----------------------------------------------------------------------

        function open() {
            clearTimeout(timer);

            var targetH = bodyEl.scrollHeight;

            // Mark open immediately so CSS selector .is-open can apply padding-top
            // (which is included in scrollHeight measured above, so order matters).
            $widget.addClass('is-open');
            $trigger.attr('aria-expanded', 'true');
            $body.attr('aria-hidden', 'false');

            // Measure again after .is-open padding-top is applied.
            targetH = bodyEl.scrollHeight;

            // Apply transition then set target values.
            bodyEl.style.transition = buildTransition(OPEN_MS, OPEN_EASING, Math.round(OPEN_MS * 0.75));
            bodyEl.style.height     = targetH + 'px';
            bodyEl.style.opacity    = '1';

            // After animation: release height constraint so dynamic content works.
            timer = setTimeout(function () {
                if ($widget.hasClass('is-open')) {
                    bodyEl.style.height   = '';
                    bodyEl.style.overflow = '';
                    clearTransition();
                }
            }, OPEN_MS + 60);
        }

        function close() {
            clearTimeout(timer);

            // 1. Pin the current rendered height (avoid jumping from "auto").
            bodyEl.style.height   = bodyEl.offsetHeight + 'px';
            bodyEl.style.overflow = 'hidden';
            clearTransition();

            // 2. Force layout recalculation so the browser registers the pinned height.
            // eslint-disable-next-line no-unused-expressions
            bodyEl.offsetHeight;

            // 3. Apply transition and animate to zero.
            bodyEl.style.transition = buildTransition(CLOSE_MS, CLOSE_EASING, Math.round(CLOSE_MS * 0.6));

            // rAF ensures the transition is registered before we change the values.
            requestAnimationFrame(function () {
                $widget.removeClass('is-open');
                $trigger.attr('aria-expanded', 'false');
                $body.attr('aria-hidden', 'true');
                bodyEl.style.height  = '0';
                bodyEl.style.opacity = '0';
            });

            // Clean up transition after animation ends.
            timer = setTimeout(clearTransition, CLOSE_MS + 60);
        }

        // -----------------------------------------------------------------------
        // Responsive: update accordion mode on breakpoint change
        // -----------------------------------------------------------------------

        function updateMode() {
            if (mql.matches ? hasDesktop : hasMobile) {
                activate();
            } else {
                deactivate();
            }
        }

        updateMode();

        // matchMedia listener (with Safari < 14 fallback).
        if (mql.addEventListener) {
            mql.addEventListener('change', updateMode);
        } else {
            mql.addListener(updateMode);
        }

        // -----------------------------------------------------------------------
        // Click handler
        // -----------------------------------------------------------------------

        $trigger.on('click.bwBiblioAccordion', function () {
            if (!$widget.hasClass('bw-js-accordion-active')) { return; }
            if ($widget.hasClass('is-open')) {
                close();
            } else {
                open();
            }
        });

        // Keyboard: space / enter already fire click on <button>, nothing extra needed.
    }

    // ---------------------------------------------------------------------------
    // Initialisation
    // ---------------------------------------------------------------------------

    function initAll() {
        $('.bw-biblio-widget.bw-biblio-accordion').each(function () {
            initBiblioAccordion($(this));
        });
    }

    $(document).ready(initAll);

    // Elementor live-preview re-init.
    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) { return; }
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-product-details-table.default',
            function ($scope) {
                var $widget = $scope.find('.bw-biblio-widget.bw-biblio-accordion');
                if ($widget.length) {
                    initBiblioAccordion($widget);
                }
            }
        );
    });

})(jQuery);
