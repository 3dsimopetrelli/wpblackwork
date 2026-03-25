(function ($) {
    'use strict';

    class BWHeroSlide {
        constructor(element) {
            this.$wrapper = $(element);
            this.$media = this.$wrapper.find('.bw-hero-slide__media').first();
            this.readyClass = 'bw-hs-is-ready';
            this._fallbackTimer = null;

            this.init();
        }

        init() {
            if (!this.$media.length) {
                this.markReady();
                return;
            }

            const mediaEl = this.$media.get(0);
            const imageUrl = this.$media.attr('data-background-image') || '';

            if (!imageUrl || mediaEl.classList.contains('bw-hero-slide__media--empty')) {
                this.markReady();
                return;
            }

            const probe = new Image();
            const done = () => {
                probe.onload = null;
                probe.onerror = null;
                window.clearTimeout(this._fallbackTimer);
                this.markReady();
            };

            probe.onload = done;
            probe.onerror = done;
            probe.src = imageUrl;

            if (probe.complete && probe.naturalWidth > 0) {
                done();
                return;
            }

            this._fallbackTimer = window.setTimeout(done, 1800);
        }

        markReady() {
            if (this.$wrapper.hasClass(this.readyClass)) {
                return;
            }

            requestAnimationFrame(() => {
                this.$wrapper.addClass(this.readyClass);
            });
        }
    }

    function boot($scope) {
        const $widgets = $scope && $scope.length
            ? $scope.find('.bw-hero-slide-wrapper')
            : $('.bw-hero-slide-wrapper');

        $widgets.each(function () {
            const $widget = $(this);

            if ($widget.data('bwHeroSlideReady')) {
                return;
            }

            $widget.data('bwHeroSlideReady', true);
            new BWHeroSlide(this);
        });
    }

    $(function () {
        boot($(document.body));
    });

    $(window).on('elementor/frontend/init', function () {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
            return;
        }

        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/bw-hero-slide.default',
            function ($scope) {
                boot($scope);
            }
        );
    });
})(jQuery);
