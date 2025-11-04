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

    function destroyGridInstance($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        if (typeof $.fn.masonry === 'function' && $grid.data('masonry')) {
            $grid.masonry('destroy');
        }

        $grid.removeClass('bw-wallpost-initialized');
    }

    function removeGridObserver($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        var observer = $grid.data('bwWallpostObserver');
        if (observer && typeof observer.disconnect === 'function') {
            observer.disconnect();
        }

        $grid.removeData('bwWallpostObserver');
    }

    function layoutGrid($grid, forceReinit) {
        if (typeof $.fn.masonry !== 'function') {
            return;
        }

        var gutterData = getGutterValue($grid);
        var instance = $grid.data('masonry');

        if (instance && !forceReinit) {
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
            destroyGridInstance($grid);

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

    function initGrid($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        layoutGrid($grid, true);
        observeGrid($grid);
    }

    function initWallpost($scope) {
        var $context = $scope && $scope.length ? $scope : $(document);
        var $grids = $();

        if ($context.hasClass('bw-wallpost-grid')) {
            $grids = $grids.add($context);
        }

        $grids = $grids.add($context.find('.bw-wallpost-grid'));

        if (!$grids.length) {
            return;
        }

        $grids.each(function () {
            initGrid($(this));
        });
    }

    var documentObserver;

    function observeDocument() {
        if (typeof window.MutationObserver === 'undefined' || documentObserver) {
            return;
        }

        if (!document.body) {
            return;
        }

        documentObserver = new window.MutationObserver(function (mutations) {
            var $gridsToInit = $();

            mutations.forEach(function (mutation) {
                if (!mutation.addedNodes || !mutation.addedNodes.length) {
                    return;
                }

                $(mutation.addedNodes).each(function () {
                    if (!this || this.nodeType !== 1) {
                        return;
                    }

                    var $node = $(this);

                    if ($node.hasClass('bw-wallpost-grid')) {
                        $gridsToInit = $gridsToInit.add($node);
                    }

                    $gridsToInit = $gridsToInit.add($node.find('.bw-wallpost-grid'));
                });
            });

            if (!$gridsToInit.length) {
                return;
            }

            $gridsToInit.each(function () {
                var $grid = $(this);

                if ($grid.length && $.contains(document.documentElement, $grid[0])) {
                    initGrid($grid);
                }
            });
        });

        documentObserver.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    $(function () {
        initWallpost($(document));
        observeDocument();
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
            var shouldForceReinit = false;

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
                        mutation.attributeName === 'data-gutter-unit'
                    ) {
                        shouldRelayout = true;
                    }

                    if (mutation.attributeName === 'data-columns') {
                        shouldRelayout = true;
                        shouldForceReinit = true;
                    }

                    if (mutation.attributeName === 'style') {
                        shouldRelayout = true;
                        shouldForceReinit = true;
                    }
                }
            });

            if (shouldRelayout) {
                layoutGrid($grid, shouldForceReinit);
            }
        });

        observer.observe($grid[0], {
            childList: true,
            subtree: false,
            attributes: true,
            attributeFilter: ['data-gutter-size', 'data-gutter-unit', 'data-columns']
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
    var WallpostHandlerClass;

    function addElementorHandler($scope) {
        if (!WallpostHandlerClass) {
            if (
                typeof elementorModules === 'undefined' ||
                !elementorModules.frontend ||
                !elementorModules.frontend.handlers ||
                !elementorModules.frontend.handlers.Base
            ) {
                initWallpost($scope);
                return;
            }

            WallpostHandlerClass = elementorModules.frontend.handlers.Base.extend({
                onInit: function () {
                    elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);

                    initWallpost(this.$element);
                },

                onElementChange: function () {
                    var $grid = this.$element.find('.bw-wallpost-grid');

                    if (!$grid.length) {
                        return;
                    }

                    $grid.each(function () {
                        layoutGrid($(this), true);
                    });
                },

                onDestroy: function () {
                    var $grid = this.$element.find('.bw-wallpost-grid');

                    if ($grid.length) {
                        $grid.each(function () {
                            var $thisGrid = $(this);
                            removeGridObserver($thisGrid);
                            destroyGridInstance($thisGrid);
                        });
                    }

                    elementorModules.frontend.handlers.Base.prototype.onDestroy.apply(this, arguments);
                }
            });
        }

        if (
            WallpostHandlerClass &&
            elementorFrontend.elementsHandler &&
            typeof elementorFrontend.elementsHandler.addHandler === 'function'
        ) {
            elementorFrontend.elementsHandler.addHandler(WallpostHandlerClass, { $element: $scope });
            return;
        }

        initWallpost($scope);
    }

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

        elementorFrontend.hooks.addAction('frontend/element_ready/bw-wallpost.default', addElementorHandler);
        elementorFrontend.hooks.addAction('frontend/element_ready/bw-wallpost-widget.default', addElementorHandler);
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);
})(jQuery);
