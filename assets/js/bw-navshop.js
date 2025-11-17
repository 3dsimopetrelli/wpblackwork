/**
 * BW NavShop Widget JavaScript
 */
(function($) {
    'use strict';

    /**
     * NavShop Handler Class
     */
    class BWNavShop {
        constructor($element) {
            this.$element = $element;
            this.widgetId = $element.data('widget-id');
            this.$overlay = $(`.bw-navshop-overlay[data-widget-id="${this.widgetId}"]`);
            this.$panel = $(`.bw-navshop-panel[data-widget-id="${this.widgetId}"]`);
            this.$accountBtn = $element.find('.bw-navshop__account');
            this.$cartBtn = $element.find('.bw-navshop__cart');
            this.$closeBtn = this.$panel.find('.bw-navshop-panel__close');

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            const self = this;

            // Account button click
            this.$accountBtn.on('click', function(e) {
                e.preventDefault();
                self.openPanel();
            });

            // Cart button click - redirect to cart page
            this.$cartBtn.on('click', function(e) {
                // Let the default link behavior happen
                // The href is already set to the cart URL in PHP
            });

            // Close button click
            this.$closeBtn.on('click', function(e) {
                e.preventDefault();
                self.closePanel();
            });

            // Overlay click to close
            this.$overlay.on('click', function(e) {
                if (e.target === this) {
                    self.closePanel();
                }
            });

            // ESC key to close
            $(document).on('keydown.bw-navshop', function(e) {
                if (e.key === 'Escape' && self.$panel.hasClass('is-active')) {
                    self.closePanel();
                }
            });
        }

        openPanel() {
            // Show overlay first
            this.$overlay.show();

            // Force reflow to ensure transition works
            this.$overlay[0].offsetHeight;

            // Add active classes
            this.$overlay.addClass('is-active');
            this.$panel.addClass('is-active');

            // Prevent body scroll
            $('body').addClass('bw-navshop-panel-open');

            // Focus on close button for accessibility
            setTimeout(() => {
                this.$closeBtn.focus();
            }, 400);
        }

        closePanel() {
            // Remove active classes
            this.$overlay.removeClass('is-active');
            this.$panel.removeClass('is-active');

            // Remove body scroll lock
            $('body').removeClass('bw-navshop-panel-open');

            // Hide overlay after transition
            setTimeout(() => {
                if (!this.$overlay.hasClass('is-active')) {
                    this.$overlay.hide();
                }
            }, 300);
        }

        destroy() {
            this.$accountBtn.off('click');
            this.$cartBtn.off('click');
            this.$closeBtn.off('click');
            this.$overlay.off('click');
            $(document).off('keydown.bw-navshop');
        }
    }

    /**
     * Initialize NavShop widgets
     */
    function initNavShop() {
        $('.bw-navshop').each(function() {
            const $widget = $(this);

            // Prevent multiple initializations
            if ($widget.data('bw-navshop-initialized')) {
                return;
            }

            const navshop = new BWNavShop($widget);
            $widget.data('bw-navshop-instance', navshop);
            $widget.data('bw-navshop-initialized', true);
        });
    }

    /**
     * Initialize on document ready
     */
    $(function() {
        initNavShop();
    });

    /**
     * Initialize on Elementor preview load
     */
    $(window).on('elementor/frontend/init', function() {
        // Initialize immediately for existing widgets
        initNavShop();

        // Re-initialize when Elementor preview updates
        if (window.elementorFrontend) {
            elementorFrontend.hooks.addAction('frontend/element_ready/bw-navshop.default', function($scope) {
                const $widget = $scope.find('.bw-navshop');
                if ($widget.length) {
                    // Destroy existing instance if any
                    const existingInstance = $widget.data('bw-navshop-instance');
                    if (existingInstance && typeof existingInstance.destroy === 'function') {
                        existingInstance.destroy();
                    }

                    // Remove initialization flag
                    $widget.removeData('bw-navshop-initialized');
                    $widget.removeData('bw-navshop-instance');

                    // Re-initialize
                    const navshop = new BWNavShop($widget);
                    $widget.data('bw-navshop-instance', navshop);
                    $widget.data('bw-navshop-initialized', true);
                }
            });
        }
    });

    /**
     * Cleanup on page unload
     */
    $(window).on('unload', function() {
        $('.bw-navshop').each(function() {
            const instance = $(this).data('bw-navshop-instance');
            if (instance && typeof instance.destroy === 'function') {
                instance.destroy();
            }
        });
    });

})(jQuery);
