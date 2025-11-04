(function ($) {
    'use strict';

    function getGutterValue($grid) {
        var size = parseFloat($grid.data('gutter-size'));
        var unit = $grid.data('gutter-unit');

        if (isNaN(size)) {
            size = 0;
        }

        if ('%' !== unit && 'px' !== unit) {
            unit = 'px';
        }

        if ('%' === unit) {
            return {
                value: ($grid.innerWidth() * size) / 100,
                unit: unit,
                size: size
            };
        }

        return {
            value: size,
            unit: unit,
            size: size
        };
    }

    function layoutGrid($grid) {
        if (typeof $.fn.masonry !== 'function') {
            return;
        }

        var gutterData = getGutterValue($grid);
        var instance = $grid.data('masonry');

        if (instance) {
            instance.options.gutter = gutterData.value;
            instance.layout();
            return;
        }

        var initializeMasonry = function () {
            var data = getGutterValue($grid);
            $grid.masonry({
                itemSelector: '.bw-wallpost-item',
                percentPosition: true,
                gutter: data.value
            });
            $grid.addClass('bw-wallpost-initialized');
        };

        if (typeof $grid.imagesLoaded === 'function') {
            $grid.imagesLoaded(function () {
                initializeMasonry();
            });
        } else {
            initializeMasonry();
        }
    }

    function initWallpost($scope) {
        var $grids = $scope.find('.bw-wallpost-grid');

        if (!$grids.length) {
            return;
        }

        $grids.each(function () {
            var $grid = $(this);
            layoutGrid($grid);
        });
    }

    function initMasonry($scope) {
        initWallpost($scope);
    }

    $(window).on('load', function () {
        initWallpost($(document));
    });

    $(window).on('resize', function () {
        $('.bw-wallpost-grid.bw-wallpost-initialized').each(function () {
            layoutGrid($(this));
        });
    });

    $(window).on('elementor/frontend/init', function () {
        if (!window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }

        elementorFrontend.hooks.addAction('frontend/element_ready/bw-wallpost.default', initMasonry);
    });
})(jQuery);
