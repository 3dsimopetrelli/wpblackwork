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

    function withImagesLoaded($grid, callback) {
        if (typeof callback !== 'function') {
            return;
        }

        if (typeof $grid.imagesLoaded === 'function') {
            $grid.imagesLoaded(function () {
                callback();
            });
            return;
        }

        callback();
    }

    function layoutGrid($grid) {
        if (typeof $.fn.masonry !== 'function') {
            return;
        }

        var gutterData = getGutterValue($grid);
        var instance = $grid.data('masonry');

        if (instance) {
            withImagesLoaded($grid, function () {
                instance.options.gutter = gutterData.value;

                if (typeof instance.reloadItems === 'function') {
                    instance.reloadItems();
                }

                instance.layout();
            });
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

        withImagesLoaded($grid, initializeMasonry);
    }

    function initWallpost($scope) {
        var $grids = $scope.find('.bw-wallpost-grid');

        if (!$grids.length) {
            return;
        }

        $grids.each(function () {
            var $grid = $(this);
            layoutGrid($grid);
            observeGrid($grid);
        });
    }

    function initMasonry($scope) {
        initWallpost($scope);
    }

    $(function () {
        initWallpost($(document));
    });

    $(window).on('resize', function () {
        $('.bw-wallpost-grid.bw-wallpost-initialized').each(function () {
            layoutGrid($(this));
        });
    });

    function observeGrid($grid) {
        if (typeof window.MutationObserver === 'undefined') {
            return;
        }

        if (!$grid.length || $grid.data('bwWallpostObserver')) {
            return;
        }

        var observer = new window.MutationObserver(function (mutations) {
            if (!$grid.length || !$.contains(document, $grid[0])) {
                observer.disconnect();
                $grid.removeData('bwWallpostObserver');
                return;
            }

            var shouldRelayout = false;

            mutations.forEach(function (mutation) {
                if (shouldRelayout) {
                    return;
                }

                if (mutation.type === 'childList' && (mutation.addedNodes.length || mutation.removedNodes.length)) {
                    shouldRelayout = true;
                    return;
                }

                if (mutation.type === 'attributes') {
                    if (
                        mutation.attributeName === 'data-gutter-size' ||
                        mutation.attributeName === 'data-gutter-unit' ||
                        mutation.attributeName === 'style'
                    ) {
                        shouldRelayout = true;
                    }
                }
            });

            if (shouldRelayout) {
                layoutGrid($grid);
            }
        });

        observer.observe($grid[0], {
            childList: true,
            subtree: false,
            attributes: true,
            attributeFilter: ['data-gutter-size', 'data-gutter-unit']
        });

        var wrapper = $grid.closest('.bw-wallpost');
        if (wrapper.length) {
            observer.observe(wrapper[0], {
                attributes: true,
                attributeFilter: ['style']
            });
        }

        $grid.data('bwWallpostObserver', observer);
    }

    var hooksRegistered = false;

    function registerElementorHooks() {
        if (hooksRegistered) {
            return;
        }

        if (
            typeof elementorFrontend === 'undefined' ||
            !elementorFrontend.hooks ||
            typeof elementorFrontend.hooks.addAction !== 'function'
        ) {
            return;
        }

        hooksRegistered = true;
        elementorFrontend.hooks.addAction('frontend/element_ready/bw-wallpost.default', initMasonry);
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);
})(jQuery);
