/**
 * BW Animated Banner Widget JavaScript
 *
 * Hover pause: handled by CSS :hover rule (no JS needed).
 * Focus pause: handled by CSS :focus-within rule (no JS needed).
 * Animation duration: set via PHP inline style (no JS needed).
 *
 * This file only re-binds the widget in the Elementor editor when a widget
 * is rendered or re-rendered via element_ready.
 */

(function($) {
    'use strict';

    /**
     * Elementor Frontend & Editor Support
     * Re-initialises the widget after Elementor renders/re-renders it.
     */
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-animated-banner.default',
            function($scope) {
                // Nothing to init: CSS handles hover/focus pause and PHP sets duration.
                // Hook is kept as an extension point for future JS behaviour.
            }
        );
    });

})(jQuery);
