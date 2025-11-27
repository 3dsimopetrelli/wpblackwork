/**
 * BW Animated Banner Widget JavaScript
 * Handles pause on hover functionality and Elementor editor support
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

        // Remove old event handlers to prevent duplicates
        $banner.off('mouseenter.bwbanner mouseleave.bwbanner focusin.bwbanner focusout.bwbanner');

        // Pause animation on hover
        $banner.on('mouseenter.bwbanner', function() {
            $banner.addClass('paused');
        });

        // Resume animation on mouse leave
        $banner.on('mouseleave.bwbanner', function() {
            $banner.removeClass('paused');
        });

        // Handle focus for accessibility
        $banner.on('focusin.bwbanner', function() {
            $banner.addClass('paused');
        });

        $banner.on('focusout.bwbanner', function() {
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
     * Initialize when document is ready (for frontend)
     */
    $(document).ready(function() {
        initAllBanners();
    });

    /**
     * Elementor Frontend & Editor Support
     */
    $(window).on('elementor/frontend/init', function() {
        // Hook for when widget is ready (works in both frontend and editor)
        elementorFrontend.hooks.addAction('frontend/element_ready/bw-animated-banner.default', function($scope) {
            var $banner = $scope.find('.bw-animated-banner');
            initAnimatedBanner($banner);
        });
    });

    /**
     * Additional support for Elementor Editor
     * This ensures the widget works when settings are changed in the editor
     */
    if (typeof elementor !== 'undefined') {
        // Listen for when the editor panel is opened/changed
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
            if (model.get('widgetType') === 'bw-animated-banner') {
                // Re-initialize after a short delay to ensure DOM is updated
                setTimeout(function() {
                    var $banner = $('#elementor-preview-iframe').contents().find('[data-id="' + model.id + '"]').find('.bw-animated-banner');
                    if ($banner.length) {
                        initAnimatedBanner($banner);
                    }
                }, 100);
            }
        });
    }

})(jQuery);
