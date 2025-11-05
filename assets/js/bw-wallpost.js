(function ($) {
    'use strict';

    /**
     * Get current device breakpoint
     * @returns {string} 'mobile', 'tablet', or 'desktop'
     */
    function getCurrentDevice() {
        var width = window.innerWidth;
        if (width < 768) return 'mobile';
        if (width < 1025) return 'tablet';
        return 'desktop';
    }

    /**
     * Get number of columns for current device
     * @param {jQuery} $grid - The grid element
     * @returns {number} Number of columns
     */
    function getColumns($grid) {
        var device = getCurrentDevice();
        var columns;

        switch(device) {
            case 'mobile':
                columns = $grid.data('columns-mobile') || 1;
                break;
            case 'tablet':
                columns = $grid.data('columns-tablet') || 2;
                break;
            default:
                columns = $grid.data('columns') || 4;
        }

        // Try to read from CSS variable first (for Elementor editor live updates)
        var $wrapper = $grid.closest('.bw-wallpost');
        if ($wrapper.length && $wrapper[0]) {
            var computedStyle = window.getComputedStyle($wrapper[0]);
            var cssColumns = computedStyle.getPropertyValue('--bw-wallpost-columns');

            if (cssColumns && cssColumns !== '') {
                var parsedColumns = parseInt(cssColumns.trim());
                if (!isNaN(parsedColumns) && parsedColumns > 0) {
                    columns = parsedColumns;
                }
            }
        }

        return parseInt(columns) || 1;
    }

    function getGutterValue($grid) {
        var device = getCurrentDevice();
        var size, unit;

        // Get responsive gutter values based on device
        switch(device) {
            case 'mobile':
                size = parseFloat($grid.data('gutter-mobile'));
                unit = 'px';
                break;
            case 'tablet':
                size = parseFloat($grid.data('gutter-tablet'));
                unit = 'px';
                break;
            default:
                size = parseFloat($grid.data('gutter-size'));
                unit = $grid.data('gutter-unit') || 'px';
        }

        // Try to read from CSS variable first (for Elementor editor live updates)
        var $wrapper = $grid.closest('.bw-wallpost');
        if ($wrapper.length && $wrapper[0]) {
            // Use getComputedStyle instead of jQuery .css() for reliable CSS variable reading
            var computedStyle = window.getComputedStyle($wrapper[0]);
            var cssGap = computedStyle.getPropertyValue('--bw-wallpost-gap');

            if (cssGap && cssGap !== '') {
                // Parse the CSS value (e.g., "24px" or "2%")
                var matches = cssGap.trim().match(/^([\d.]+)([a-z%]*)$/i);
                if (matches) {
                    var cssSize = parseFloat(matches[1]);
                    var cssUnit = matches[2] || 'px';

                    if (!isNaN(cssSize)) {
                        size = cssSize;
                        unit = cssUnit;
                    }
                }
            }
        }

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

    function updateGridHeight($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        var instance = $grid.data('masonry');
        if (!instance) {
            return;
        }

        // Calculate the height based on positioned items
        var maxHeight = 0;
        var $items = $grid.find('.bw-wallpost-item');

        $items.each(function() {
            var $item = $(this);
            var itemBottom = $item.position().top + $item.outerHeight(true);
            if (itemBottom > maxHeight) {
                maxHeight = itemBottom;
            }
        });

        // Set explicit height on the container
        if (maxHeight > 0) {
            $grid.css('height', maxHeight + 'px');
        }
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

        // Force browser reflow to ensure CSS changes are applied
        if (forceReinit && $grid[0]) {
            void $grid[0].offsetHeight;
        }

        var gutterData = getGutterValue($grid);
        var columnsCount = getColumns($grid);
        var instance = $grid.data('masonry');
        var isEditor = isElementorEditor();

        // Check if we need to force reinit due to CSS variable changes
        var lastColumns = $grid.data('bw-last-columns');
        var lastGutter = $grid.data('bw-last-gutter');

        if (instance && (lastColumns !== columnsCount || lastGutter !== gutterData.value)) {
            forceReinit = true;
        }

        // Store current values for next comparison
        $grid.data('bw-last-columns', columnsCount);
        $grid.data('bw-last-gutter', gutterData.value);

        if (instance && !forceReinit) {
            withImagesLoaded($grid, function () {
                instance.options.gutter = gutterData.value;

                if (typeof instance.reloadItems === 'function') {
                    instance.reloadItems();
                }

                instance.layout();

                // Update container height immediately
                updateGridHeight($grid);

                // Force height recalculation after delay
                setTimeout(function() {
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 100);

                // Additional check for editor mode
                setTimeout(function() {
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 300);
            });
            return;
        }

        var initializeMasonry = function () {
            // Destroy existing instance completely
            destroyGridInstance($grid);

            var data = getGutterValue($grid);
            $grid.masonry({
                itemSelector: '.bw-wallpost-item',
                percentPosition: true,
                gutter: data.value
            });
            $grid.addClass('bw-wallpost-initialized');

            var masonryInstance = $grid.data('masonry');

            // Reload items to ensure all elements are recognized
            if (masonryInstance && typeof masonryInstance.reloadItems === 'function') {
                masonryInstance.reloadItems();
            }

            // Force layout immediately
            if (masonryInstance && typeof masonryInstance.layout === 'function') {
                masonryInstance.layout();
            }

            // Update height immediately after initialization
            updateGridHeight($grid);

            // Additional layouts for editor mode
            if (isEditor) {
                // Second layout pass at 100ms
                setTimeout(function() {
                    var instance = $grid.data('masonry');
                    if (instance && typeof instance.reloadItems === 'function') {
                        instance.reloadItems();
                    }
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 100);

                // Third layout pass at 300ms
                setTimeout(function() {
                    var instance = $grid.data('masonry');
                    if (instance && typeof instance.reloadItems === 'function') {
                        instance.reloadItems();
                    }
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 300);

                // Final layout pass at 500ms
                setTimeout(function() {
                    var instance = $grid.data('masonry');
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 500);
            } else {
                // Frontend: fewer layout passes
                setTimeout(function() {
                    var instance = $grid.data('masonry');
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 100);
            }
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

    var resizeTimeout;
    var lastDevice = getCurrentDevice();

    $(window).on('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            var currentDevice = getCurrentDevice();
            var deviceChanged = currentDevice !== lastDevice;

            $('.bw-wallpost-grid.bw-wallpost-initialized').each(function () {
                var $grid = $(this);

                // Force browser reflow to ensure CSS media queries are applied
                if (deviceChanged && $grid[0]) {
                    void $grid[0].offsetHeight;
                }

                // Always force reinit when device changes to ensure CSS variables are re-read
                layoutGrid($grid, deviceChanged);

                updateGridHeight($grid);
            });

            lastDevice = currentDevice;
        }, 150);
    });

    function observeGrid($grid) {
        if (typeof window.MutationObserver === 'undefined') {
            return;
        }

        if (!$grid.length || $grid.data('bwWallpostObserver')) {
            return;
        }

        var relayoutTimeout;
        var observer = new window.MutationObserver(function (mutations) {
            if (!$grid.length || !$.contains(document, $grid[0])) {
                observer.disconnect();
                $grid.removeData('bwWallpostObserver');
                return;
            }

            var shouldRelayout = false;
            var shouldForceReinit = false;

            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' && (mutation.addedNodes.length || mutation.removedNodes.length)) {
                    shouldRelayout = true;
                    return;
                }

                if (mutation.type === 'attributes') {
                    if (
                        mutation.attributeName === 'data-gutter-size' ||
                        mutation.attributeName === 'data-gutter-unit' ||
                        mutation.attributeName === 'data-gutter-tablet' ||
                        mutation.attributeName === 'data-gutter-mobile'
                    ) {
                        shouldRelayout = true;
                        shouldForceReinit = true;
                    }

                    if (
                        mutation.attributeName === 'data-columns' ||
                        mutation.attributeName === 'data-columns-tablet' ||
                        mutation.attributeName === 'data-columns-mobile'
                    ) {
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
                // Clear previous timeout to debounce multiple rapid changes
                if (relayoutTimeout) {
                    clearTimeout(relayoutTimeout);
                }

                // In Elementor editor, use shorter delay for immediate feedback
                var delay = isElementorEditor() ? 50 : 100;

                relayoutTimeout = setTimeout(function() {
                    layoutGrid($grid, shouldForceReinit);
                }, delay);
            }
        });

        observer.observe($grid[0], {
            childList: true,
            subtree: false,
            attributes: true,
            attributeFilter: [
                'data-gutter-size',
                'data-gutter-unit',
                'data-gutter-tablet',
                'data-gutter-mobile',
                'data-columns',
                'data-columns-tablet',
                'data-columns-mobile'
            ]
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

    /**
     * Waits for the grid to be ready with all items loaded before proceeding
     * IMPROVED: Uses intelligent polling + MutationObserver to detect when Elementor has finished re-rendering
     *
     * @param {jQuery} $element - The widget element
     * @param {Function} callback - Called when grid is ready
     * @param {number} maxAttempts - Maximum polling attempts
     */
    function waitForGridReady($element, callback, maxAttempts) {
        maxAttempts = maxAttempts || 30; // 30 attempts = 3 seconds max
        var attempts = 0;
        var lastItemCount = -1;
        var stableCount = 0;
        var mutationDetected = false;

        // IMPROVEMENT 1: Use MutationObserver to detect DOM changes
        var observer = null;
        if (typeof window.MutationObserver !== 'undefined' && $element[0]) {
            observer = new window.MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Detect if Elementor is modifying the widget content
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutationDetected = true;
                        // Reset stability counter when DOM changes
                        stableCount = 0;
                    }
                });
            });

            observer.observe($element[0], {
                childList: true,
                subtree: true
            });
        }

        var pollInterval = setInterval(function() {
            attempts++;

            var $currentGrid = $element.find('.bw-wallpost-grid');

            // Check if grid exists and is in DOM
            if (!$currentGrid.length || !$.contains(document.documentElement, $currentGrid[0])) {
                if (attempts >= maxAttempts) {
                    if (observer) observer.disconnect();
                    clearInterval(pollInterval);
                    return;
                }
                return;
            }

            var $items = $currentGrid.find('.bw-wallpost-item');
            var currentItemCount = $items.length;

            // IMPROVEMENT 2: Only consider stable if mutations have stopped AND count is stable
            var isCountStable = currentItemCount === lastItemCount && currentItemCount > 0;

            if (isCountStable) {
                stableCount++;

                // IMPROVEMENT 3: Require 3 stable checks (300ms) to ensure Elementor finished
                if (stableCount >= 3 && !mutationDetected) {
                    if (observer) observer.disconnect();
                    clearInterval(pollInterval);
                    callback($currentGrid, $items);
                    return;
                }
            } else {
                stableCount = 0;
            }

            // Reset mutation flag after checking
            mutationDetected = false;
            lastItemCount = currentItemCount;

            // Timeout after max attempts
            if (attempts >= maxAttempts) {
                if (observer) observer.disconnect();
                clearInterval(pollInterval);
                // Even if not perfectly stable, proceed if we have items
                if (currentItemCount > 0 && $currentGrid.length) {
                    callback($currentGrid, $items);
                }
            }
        }, 100); // Check every 100ms
    }

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

                    var self = this;

                    // Initialize with delay to ensure DOM is ready
                    setTimeout(function() {
                        initWallpost(self.$element);
                    }, 50);
                },

                onElementChange: function (settingKey) {
                    var self = this;
                    var $grid = this.$element.find('.bw-wallpost-grid');

                    if (!$grid.length) {
                        return;
                    }

                    // Clear any pending timeout
                    if (this.layoutTimeout) {
                        clearTimeout(this.layoutTimeout);
                    }

                    // IMPROVEMENT: Detect more control types including new order_by and responsive controls
                    var isPostsChange = settingKey && settingKey.indexOf('posts_per_page') !== -1;
                    var isColumnsChange = settingKey && settingKey.indexOf('columns') !== -1;
                    var isGapChange = settingKey && settingKey.indexOf('gap') !== -1;
                    var isOrderChange = settingKey && (settingKey.indexOf('order_by') !== -1 || settingKey.indexOf('order') !== -1);

                    var needsFullReinit = isPostsChange || isColumnsChange || isGapChange || isOrderChange;

                    // Console log for debugging (can be removed later)
                    if (typeof console !== 'undefined' && console.log) {
                        console.log('BW WallPost: onElementChange triggered', {
                            settingKey: settingKey,
                            needsFullReinit: needsFullReinit,
                            isPostsChange: isPostsChange,
                            isOrderChange: isOrderChange,
                            isColumnsChange: isColumnsChange,
                            isGapChange: isGapChange
                        });
                    }

                    // Show loading overlay for visual feedback
                    if (needsFullReinit) {
                        var $wrapper = this.$element.find('.bw-wallpost');
                        if ($wrapper.length) {
                            $wrapper.addClass('bw-wallpost--loading');
                        }
                    }

                    // For gap and columns changes, update attributes immediately
                    if (isGapChange || isColumnsChange) {
                        var settings = this.getElementSettings();

                        if (isGapChange) {
                            // Desktop gap
                            if (settings.gap) {
                                var gapSize = settings.gap.size || 15;
                                var gapUnit = settings.gap.unit || 'px';
                                $grid.attr('data-gutter-size', gapSize);
                                $grid.attr('data-gutter-unit', gapUnit);

                                // Update CSS variable immediately
                                var $wrapper = this.$element.find('.bw-wallpost');
                                if ($wrapper.length) {
                                    $wrapper[0].style.setProperty('--bw-wallpost-gap', gapSize + gapUnit);
                                }
                            }

                            // Tablet gap
                            if (settings.gap_tablet) {
                                var gapTabletSize = settings.gap_tablet.size || 10;
                                $grid.attr('data-gutter-tablet', gapTabletSize);
                            }

                            // Mobile gap
                            if (settings.gap_mobile) {
                                var gapMobileSize = settings.gap_mobile.size || 10;
                                $grid.attr('data-gutter-mobile', gapMobileSize);
                            }
                        }

                        if (isColumnsChange) {
                            // Desktop columns
                            if (settings.columns) {
                                $grid.attr('data-columns', settings.columns);

                                // Update CSS variable for columns immediately
                                var $wrapper = this.$element.find('.bw-wallpost');
                                if ($wrapper.length) {
                                    $wrapper[0].style.setProperty('--bw-wallpost-columns', settings.columns);
                                }
                            }

                            // Tablet columns
                            if (settings.columns_tablet) {
                                $grid.attr('data-columns-tablet', settings.columns_tablet);
                            }

                            // Mobile columns
                            if (settings.columns_mobile) {
                                $grid.attr('data-columns-mobile', settings.columns_mobile);
                            }
                        }
                    }

                    // Destroy existing instance immediately to prevent conflicts
                    if (needsFullReinit) {
                        destroyGridInstance($grid);
                        removeGridObserver($grid);
                    }

                    /**
                     * IMPROVED: Wait longer for posts/order changes as they trigger Ajax
                     */
                    var initialDelay = isPostsChange || isOrderChange ? 400 : 150;
                    var maxAttempts = isPostsChange || isOrderChange ? 35 : 25;

                    this.layoutTimeout = setTimeout(function () {
                        if (needsFullReinit) {
                            // Use polling to wait for grid to be ready with all items loaded
                            waitForGridReady(self.$element, function($readyGrid, $items) {
                                // Double-check grid is still in DOM
                                if (!$.contains(document.documentElement, $readyGrid[0])) {
                                    return;
                                }

                                // CRITICAL: Destroy any existing instance before reinit
                                destroyGridInstance($readyGrid);
                                removeGridObserver($readyGrid);

                                // Force browser reflow to ensure clean state
                                void $readyGrid[0].offsetHeight;

                                // Console log for debugging
                                if (typeof console !== 'undefined' && console.log) {
                                    console.log('BW WallPost: Reinitializing grid', {
                                        items: $items.length,
                                        columns: $readyGrid.attr('data-columns')
                                    });
                                }

                                // Now reinitialize with fresh DOM
                                initGrid($readyGrid);

                                // Remove loading overlay after successful initialization
                                setTimeout(function() {
                                    var $wrapper = self.$element.find('.bw-wallpost');
                                    if ($wrapper.length) {
                                        $wrapper.removeClass('bw-wallpost--loading');
                                    }
                                }, 400);

                                // IMPROVEMENT: More aggressive layout passes
                                var layoutPasses = [200, 400, 700, 1000];
                                layoutPasses.forEach(function(delay) {
                                    setTimeout(function() {
                                        var instance = $readyGrid.data('masonry');
                                        if (instance && typeof instance.reloadItems === 'function') {
                                            instance.reloadItems();
                                        }
                                        if (instance && typeof instance.layout === 'function') {
                                            instance.layout();
                                            updateGridHeight($readyGrid);
                                        }
                                    }, delay);
                                });

                                // Console log when complete
                                setTimeout(function() {
                                    if (typeof console !== 'undefined' && console.log) {
                                        console.log('BW WallPost: Layout complete');
                                    }
                                }, 1100);
                            }, maxAttempts);
                        } else {
                            // For minor changes: just update layout
                            var $currentGrid = self.$element.find('.bw-wallpost-grid');
                            if ($currentGrid.length) {
                                layoutGrid($currentGrid, false);

                                // Remove loading overlay
                                setTimeout(function() {
                                    var $wrapper = self.$element.find('.bw-wallpost');
                                    if ($wrapper.length) {
                                        $wrapper.removeClass('bw-wallpost--loading');
                                    }
                                }, 200);
                            }
                        }
                    }, initialDelay);
                },

                onDestroy: function () {
                    var $grid = this.$element.find('.bw-wallpost-grid');

                    // Clear any pending timeout
                    if (this.layoutTimeout) {
                        clearTimeout(this.layoutTimeout);
                    }

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

    // Handle elementor/frontend/after_load event
    $(window).on('elementor/frontend/after_load', function() {
        setTimeout(function() {
            $('.bw-wallpost-grid').each(function() {
                var $grid = $(this);
                if ($grid.hasClass('bw-wallpost-initialized')) {
                    layoutGrid($grid, false);
                    updateGridHeight($grid);
                } else {
                    initGrid($grid);
                }
            });
        }, 100);
    });

    // Additional support for Elementor editor
    if (typeof elementor !== 'undefined') {
        // Listen to Elementor preview loaded event
        elementor.on('preview:loaded', function() {
            var $previewContents = elementor.$previewContents;

            if ($previewContents && $previewContents.length) {
                initWallpost($previewContents);
            }
        });
    }

    // Detect if we're in Elementor editor
    function isElementorEditor() {
        return (typeof elementorFrontend !== 'undefined' &&
                elementorFrontend.isEditMode &&
                elementorFrontend.isEditMode()) ||
               (typeof elementor !== 'undefined');
    }

    // Enhanced initialization for editor
    if (isElementorEditor()) {
        // Re-layout on window resize with debouncing for editor
        var editorResizeTimeout;
        $(window).off('resize.bwWallpost').on('resize.bwWallpost', function () {
            clearTimeout(editorResizeTimeout);
            editorResizeTimeout = setTimeout(function() {
                $('.bw-wallpost-grid.bw-wallpost-initialized').each(function () {
                    var $grid = $(this);
                    layoutGrid($grid, false);
                    updateGridHeight($grid);
                });
            }, 150);
        });

        // Additional observer for editor iframe changes
        if (typeof window.MutationObserver !== 'undefined') {
            var checkAndInitEditor = function() {
                var $editorWindow = elementorFrontend && elementorFrontend.getElements &&
                                   elementorFrontend.getElements('$window');

                if ($editorWindow && $editorWindow.length) {
                    var editorDoc = $editorWindow[0].document;
                    if (editorDoc) {
                        $(editorDoc).find('.bw-wallpost-grid').each(function() {
                            var $grid = $(this);
                            if (!$grid.hasClass('bw-wallpost-initialized')) {
                                initGrid($grid);
                            } else {
                                // Update height for already initialized grids
                                updateGridHeight($grid);
                            }
                        });
                    }
                }
            };

            // Check periodically in editor
            setInterval(checkAndInitEditor, 2000);
        }

        // Force height update on scroll/resize in editor
        var editorScrollTimeout;
        $(window).on('scroll.bwWallpost', function() {
            clearTimeout(editorScrollTimeout);
            editorScrollTimeout = setTimeout(function() {
                $('.bw-wallpost-grid.bw-wallpost-initialized').each(function () {
                    updateGridHeight($(this));
                });
            }, 100);
        });
    }
})(jQuery);
