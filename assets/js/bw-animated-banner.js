/**
 * BW Animated Banner Widget JavaScript
 * Handles pause on hover functionality
 */

(function($) {
    'use strict';

    /**
     * Initialize Animated Banner
     */
    function initAnimatedBanner($banner) {
        if (!$banner || !$banner.length) {
            return;
        }

        var $track = $banner.find('.bw-animated-banner__track');

        if (!$track.length) {
            return;
        }

        // Pause animation on hover
        $banner.on('mouseenter', function() {
            $banner.addClass('paused');
        });

        // Resume animation on mouse leave
        $banner.on('mouseleave', function() {
            $banner.removeClass('paused');
        });

        // Handle focus for accessibility
        $banner.on('focusin', function() {
            $banner.addClass('paused');
        });

        $banner.on('focusout', function() {
            $banner.removeClass('paused');
        });

        // Update animation duration based on speed setting
        var speed = $banner.data('speed');
        if (speed) {
            var duration = Math.max(10, 100 - speed);
            $track.css('animation-duration', duration + 's');
        }
    }

    /**
     * Initialize all banners on page
     */
    function initAllBanners() {
        $('.bw-animated-banner').each(function() {
            initAnimatedBanner($(this));
        });
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        initAllBanners();
    });

    /**
     * Re-initialize when Elementor preview updates
     */
    if (typeof elementorFrontend !== 'undefined') {
        $(window).on('elementor/frontend/init', function() {
            elementorFrontend.hooks.addAction('frontend/element_ready/bw-animated-banner.default', function($scope) {
                var $banner = $scope.find('.bw-animated-banner');
                initAnimatedBanner($banner);
            });
        });
    }

})(jQuery);
