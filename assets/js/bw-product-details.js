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
    // Timing constants
    // expo-out for open (content snaps confidently into place)
    // standard ease for close (controlled, settled)
    // ---------------------------------------------------------------------------
    var OPEN_MS      = 380;
    var CLOSE_MS     = 300;
    var OPEN_EASING  = 'cubic-bezier(0.16, 1, 0.3, 1)';
    var CLOSE_EASING = 'cubic-bezier(0.4, 0, 0.2, 1)';
    var BREAKPOINT   = '(min-width: 1025px)';

    // ---------------------------------------------------------------------------
    // initBiblioAccordion
    // ---------------------------------------------------------------------------
    function initBiblioAccordion($widget) {
        if (!$widget.hasClass('bw-biblio-accordion')) { return; }
        if ($widget.data('bwBiblioAcc'))              { return; }
        $widget.data('bwBiblioAcc', true);

        var hasMobile  = $widget.hasClass('bw-biblio-accordion--mobile');
        var hasDesktop = $widget.hasClass('bw-biblio-accordion--desktop');

        // Neither sub-toggle → active on all breakpoints
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

        function setTransition(durationMs, easing, opacityMs) {
            bodyEl.style.transition = [
                'height '  + durationMs + 'ms ' + easing,
                'opacity ' + opacityMs  + 'ms ease'
            ].join(', ');
        }

        function clearTransition() {
            bodyEl.style.transition = '';
        }

        // -----------------------------------------------------------------------
        // activate / deactivate
        // -----------------------------------------------------------------------

        function activate() {
            clearTimeout(timer);
            clearTransition();
            // Set closed state via inline styles (override CSS flash-prevention)
            bodyEl.style.height   = '0';
            bodyEl.style.overflow = 'hidden';
            bodyEl.style.opacity  = '0';
            $widget.addClass('bw-js-accordion-active').removeClass('is-open');
            $trigger.attr('aria-expanded', 'false');
            $body.attr('aria-hidden', 'true');
        }

        function deactivate() {
            clearTimeout(timer);
            clearTransition();
            $widget.removeClass('bw-js-accordion-active is-open');
            // Remove all inline constraints — body flows naturally
            bodyEl.style.cssText = '';
            $trigger.attr('aria-expanded', 'false');
            $body.attr('aria-hidden', 'false');
        }

        // -----------------------------------------------------------------------
        // open
        // -----------------------------------------------------------------------

        function open() {
            if ($widget.hasClass('is-open')) { return; }
            clearTimeout(timer);

            // 1. Add .is-open so CSS padding-top on inner and border-bottom on
            //    trigger are applied before we measure the target height.
            $widget.addClass('is-open');
            $trigger.attr('aria-expanded', 'true');
            $body.attr('aria-hidden', 'false');

            // 2. Make sure we start from a known zero state, no transition yet.
            clearTransition();
            bodyEl.style.height   = '0';
            bodyEl.style.overflow = 'hidden';
            bodyEl.style.opacity  = '0';

            // 3. Measure the full target height now that .is-open is active.
            //    scrollHeight gives the true content height regardless of
            //    the current height:0 clipping.
            var targetH = bodyEl.scrollHeight;

            // 4. Force a layout recalculation so the browser registers
            //    height:0 as the definite FROM value before we set the
            //    transition. Without this the browser can batch the
            //    height change and skip the animation entirely.
            // eslint-disable-next-line no-unused-expressions
            bodyEl.offsetHeight;

            // 5. Apply transition, then set target values in the same tick.
            //    The forced reflow in step 4 guarantees the animation fires.
            setTransition(OPEN_MS, OPEN_EASING, Math.round(OPEN_MS * 0.75));
            bodyEl.style.height  = targetH + 'px';
            bodyEl.style.opacity = '1';

            // 6. After animation: release fixed height so content can reflow
            //    freely (e.g. images lazy-loading, dynamic content).
            timer = setTimeout(function () {
                if ($widget.hasClass('is-open')) {
                    clearTransition();
                    bodyEl.style.height   = '';
                    bodyEl.style.overflow = '';
                }
            }, OPEN_MS + 80);
        }

        // -----------------------------------------------------------------------
        // close
        // -----------------------------------------------------------------------

        function close() {
            if (!$widget.hasClass('is-open')) { return; }
            clearTimeout(timer);

            // 1. Pin the current rendered height as an explicit px value.
            //    getBoundingClientRect().height is more reliable than
            //    offsetHeight when fractional pixels are involved.
            var currentH = bodyEl.getBoundingClientRect().height;
            clearTransition();
            bodyEl.style.height   = currentH + 'px';
            bodyEl.style.overflow = 'hidden';

            // 2. Force reflow so the browser registers currentH as FROM value.
            // eslint-disable-next-line no-unused-expressions
            bodyEl.offsetHeight;

            // 3. Remove .is-open (arrow resets, separator disappears).
            $widget.removeClass('is-open');
            $trigger.attr('aria-expanded', 'false');
            $body.attr('aria-hidden', 'true');

            // 4. Another forced reflow to commit the is-open removal before
            //    the transition starts — prevents any CSS-triggered height
            //    change from interfering with the animation start.
            // eslint-disable-next-line no-unused-expressions
            bodyEl.offsetHeight;

            // 5. Animate to zero.
            setTransition(CLOSE_MS, CLOSE_EASING, Math.round(CLOSE_MS * 0.6));
            bodyEl.style.height  = '0';
            bodyEl.style.opacity = '0';

            // 6. Clean up transition; leave height:0 and opacity:0 in place
            //    so the CSS flash-prevention rule and the inline state agree.
            timer = setTimeout(clearTransition, CLOSE_MS + 80);
        }

        // -----------------------------------------------------------------------
        // Responsive
        // -----------------------------------------------------------------------

        function updateMode() {
            if (mql.matches ? hasDesktop : hasMobile) {
                activate();
            } else {
                deactivate();
            }
        }

        updateMode();

        if (mql.addEventListener) {
            mql.addEventListener('change', updateMode);
        } else {
            mql.addListener(updateMode); // Safari < 14 fallback
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
    }

    // ---------------------------------------------------------------------------
    // Init
    // ---------------------------------------------------------------------------

    function initAll() {
        $('.bw-biblio-widget.bw-biblio-accordion').each(function () {
            initBiblioAccordion($(this));
        });
    }

    $(document).ready(initAll);

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
