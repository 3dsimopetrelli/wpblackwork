(function ($) {
    'use strict';

    // ============================================
    // MASONRY SYSTEM (from wallpost)
    // ============================================

    function useCssGrid($grid) {
        var mode = String($grid.attr('data-layout-mode') || '').toLowerCase();
        if (!mode) {
            var masonryEffect = String($grid.attr('data-masonry-effect') || '').toLowerCase();
            return masonryEffect === 'no';
        }
        return mode === 'css-grid';
    }

    function getMasonryContainer($grid) {
        return $grid;
    }

    // Detect if we're in Elementor editor
    function isElementorEditor() {
        return (typeof elementorFrontend !== 'undefined' &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode()) ||
            (typeof elementor !== 'undefined');
    }

    function useEditorMasonryFallback($grid) {
        return isElementorEditor() && !useCssGrid($grid);
    }

    function getMasonryInstance($grid) {
        var $container = getMasonryContainer($grid);
        return $container && $container.length ? $container.data('masonry') : null;
    }

    function getCurrentDevice($grid) {
        var width = window.innerWidth || $(window).width();

        var tabletMin = parseInt($grid.attr('data-breakpoint-tablet-min')) || 768;
        var tabletMax = parseInt($grid.attr('data-breakpoint-tablet-max')) || 1024;
        var mobileMax = parseInt($grid.attr('data-breakpoint-mobile-max')) || 767;

        if (width <= mobileMax) {
            return 'mobile';
        } else if (width >= tabletMin && width <= tabletMax) {
            return 'tablet';
        }
        return 'desktop';
    }

    function getColumns($grid, device) {
        var attr = 'data-columns-' + device;
        var columns = parseInt($grid.attr(attr));

        if (!columns || isNaN(columns)) {
            columns = device === 'mobile' ? 1 : (device === 'tablet' ? 2 : 4);
        }

        return columns;
    }

    function getGapValue($grid, device, axis) {
        var suffix = axis === 'y' ? 'y' : 'x';
        var attr = 'data-gap-' + suffix + '-' + device;
        var gap = parseInt($grid.attr(attr));

        if (isNaN(gap)) {
            gap = 10;
        }

        return gap;
    }

    function getCurrentItemWidth($grid) {
        if (!$grid || !$grid.length) {
            return 0;
        }

        var cachedWidth = parseFloat($grid.data('bw-item-width'));
        if (cachedWidth && cachedWidth > 0) {
            return cachedWidth;
        }

        var $firstItem = $grid.find('.bw-fpw-item').first();
        if (!$firstItem.length) {
            return 0;
        }

        var measuredWidth = parseFloat($firstItem.outerWidth());
        return measuredWidth > 0 ? measuredWidth : 0;
    }

    function setItemWidths($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        if (useEditorMasonryFallback($grid)) {
            $grid.find('.bw-fpw-item').css({
                'width': '',
                'margin-bottom': '',
                'position': ''
            });
            $grid.removeData('bw-item-width');
            return;
        }

        if (useCssGrid($grid)) {
            $grid.find('.bw-fpw-item').css({
                'width': '',
                'margin-bottom': ''
            });
            $grid.removeData('bw-item-width');
            return;
        }

        var $masonryContainer = getMasonryContainer($grid);

        var device = getCurrentDevice($grid);
        var columnsCount = getColumns($grid, device);
        var horizontalGap = getGapValue($grid, device, 'x');
        var verticalGap = getGapValue($grid, device, 'y');
        var $items = $masonryContainer.find('.bw-fpw-item');

        if (!$items.length) {
            return;
        }

        var containerWidth = $masonryContainer.width();
        var totalGap = horizontalGap * (columnsCount - 1);
        var itemWidth = (containerWidth - totalGap) / columnsCount;
        $grid.data('bw-item-width', itemWidth > 0 ? itemWidth : 0);

        $items.each(function () {
            var $item = $(this);
            $item.css({
                'width': itemWidth + 'px',
                'margin-bottom': verticalGap + 'px'
            });
        });
    }

    function getPrimaryImageScope($scope) {
        if (!$scope || !$scope.length) {
            return $scope;
        }

        var $primaryImages = $scope.filter('img.bw-slider-main').add($scope.find('img.bw-slider-main'));

        return $primaryImages.length ? $primaryImages : $scope;
    }

    function withImagesLoaded($scope, callback, timeout) {
        if (typeof callback !== 'function') {
            return;
        }

        var maxWait = typeof timeout === 'number' && timeout > 0 ? timeout : 0;

        if (typeof $scope.imagesLoaded === 'function') {
            if (maxWait > 0) {
                var fired = false;
                var fallbackTimer = setTimeout(function () {
                    if (!fired) {
                        fired = true;
                        callback();
                    }
                }, maxWait);
                $scope.imagesLoaded(function () {
                    if (!fired) {
                        fired = true;
                        clearTimeout(fallbackTimer);
                        callback();
                    }
                });
            } else {
                $scope.imagesLoaded(function () {
                    callback();
                });
            }
            return;
        }

        callback();
    }

    function withImagesLoadedFallback($grid, timeoutMs, callback) {
        if (typeof callback !== 'function') {
            return;
        }

        var hasCompleted = false;
        var fallbackDelay = Math.max(0, parseInteger(timeoutMs, 0));
        var fallbackTimer = null;

        var completeOnce = function () {
            if (hasCompleted) {
                return;
            }

            hasCompleted = true;

            if (fallbackTimer) {
                clearTimeout(fallbackTimer);
                fallbackTimer = null;
            }

            callback();
        };

        if (fallbackDelay > 0) {
            fallbackTimer = setTimeout(function () {
                completeOnce();
            }, fallbackDelay);
        }

        withImagesLoaded($grid, completeOnce);
    }

    function updateGridHeight($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        if (useCssGrid($grid)) {
            $grid.css('height', '');
            return;
        }

        var instance = getMasonryInstance($grid);
        if (!instance) {
            return;
        }

        var maxHeight = 0;
        var $items = getMasonryContainer($grid).find('.bw-fpw-item');

        $items.each(function () {
            var $item = $(this);
            var itemBottom = $item.position().top + $item.outerHeight(true);
            if (itemBottom > maxHeight) {
                maxHeight = itemBottom;
            }
        });

        if (maxHeight > 0) {
            $grid.css('height', maxHeight + 'px');
        }
    }

    function destroyGridInstance($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        var $masonryContainer = getMasonryContainer($grid);
        if (typeof $.fn.masonry === 'function' && $masonryContainer.data('masonry')) {
            $masonryContainer.masonry('destroy');
        }

        $grid.removeClass('bw-fpw-initialized');
    }

    function layoutGrid($grid, forceReinit, onReady, imageWaitTimeout) {
        var finalizeLayout = function () {
            if (typeof onReady === 'function') {
                onReady();
            }
        };

        if (useCssGrid($grid)) {
            if (typeof $.fn.masonry === 'function' && $grid.data('masonry')) {
                $grid.masonry('destroy');
            }

            $grid.removeClass('bw-fpw-editor-masonry-fallback');
            $grid.attr('data-editor-masonry-fallback', 'no');
            $grid.addClass('bw-fpw-initialized');
            $grid.find('.bw-fpw-item').css({
                'width': '',
                'margin-bottom': '',
                'position': ''
            });
            $grid.css('height', '');
            finalizeLayout();
            return;
        }

        // Editor preview always uses a stable grid fallback.
        if (useEditorMasonryFallback($grid)) {
            if (typeof $.fn.masonry === 'function' && $grid.data('masonry')) {
                $grid.masonry('destroy');
            }

            $grid.addClass('bw-fpw-editor-masonry-fallback');
            $grid.attr('data-editor-masonry-fallback', 'yes');
            $grid.addClass('bw-fpw-initialized');
            $grid.find('.bw-fpw-item').css({
                'width': '',
                'margin-bottom': '',
                'position': ''
            });
            $grid.css('height', '');
            finalizeLayout();
            return;
        }

        $grid.removeClass('bw-fpw-editor-masonry-fallback');
        $grid.attr('data-editor-masonry-fallback', 'no');

        if (typeof $.fn.masonry !== 'function') {
            finalizeLayout();
            return;
        }

        if (forceReinit && $grid[0]) {
            void $grid[0].offsetHeight;
        }

        var device = getCurrentDevice($grid);
        var columnsCount = getColumns($grid, device);
        var horizontalGap = getGapValue($grid, device, 'x');
        var verticalGap = getGapValue($grid, device, 'y');
        var instance = $grid.data('masonry');

        var lastColumns = $grid.data('bw-last-columns');
        var lastHorizontalGap = $grid.data('bw-last-gap-x');
        var lastDevice = $grid.data('bw-last-device');

        if (instance && (lastColumns !== columnsCount || lastHorizontalGap !== horizontalGap || lastDevice !== device)) {
            forceReinit = true;
        }

        $grid.data('bw-last-columns', columnsCount);
        $grid.data('bw-last-gap-x', horizontalGap);
        $grid.data('bw-last-gap-y', verticalGap);
        $grid.data('bw-last-device', device);

        if (instance && !forceReinit) {
            setItemWidths($grid);

            withImagesLoaded(getPrimaryImageScope($grid), function () {
                instance.options.gutter = horizontalGap;
                var currentItemWidth = getCurrentItemWidth($grid);
                if (currentItemWidth > 0) {
                    instance.options.columnWidth = currentItemWidth;
                }

                if (typeof instance.reloadItems === 'function') {
                    instance.reloadItems();
                }

                instance.layout();
                updateGridHeight($grid);
                finalizeLayout();
            }, imageWaitTimeout);
            return;
        }

        withImagesLoaded(getPrimaryImageScope($grid), function () {
            destroyGridInstance($grid);
            setItemWidths($grid);

            var masonryOptions = {
                itemSelector: '.bw-fpw-item',
                columnWidth: getCurrentItemWidth($grid) || '.bw-fpw-item',
                percentPosition: false,
                gutter: horizontalGap,
                horizontalOrder: true,
                transitionDuration: '0'
            };

            $grid.masonry(masonryOptions);
            $grid.addClass('bw-fpw-initialized');

            var masonryInstance = $grid.data('masonry');
            if (masonryInstance && typeof masonryInstance.reloadItems === 'function') {
                masonryInstance.reloadItems();
            }

            if (masonryInstance && typeof masonryInstance.layout === 'function') {
                masonryInstance.layout();
            }

            updateGridHeight($grid);
            finalizeLayout();
        }, imageWaitTimeout || 200);
    }

    function initGrid($grid, onReady) {
        if (!$grid || !$grid.length) {
            return;
        }

        layoutGrid($grid, true, onReady);
    }

    // ============================================
    // FILTER SYSTEM
    // ============================================

    var filterState = {};
    var widgetPagingState = {};
    var staggerTimersByWidget = {};
    var staggerObserversByWidget = {};
    var searchDebounceTimers = {};

    // ============================================
    // PERFORMANCE OPTIMIZATION - CACHING SYSTEM
    // ============================================

    var ajaxCache = {};
    var ajaxRequestQueue = {};
    var loadingIndicatorTimers = {}; // delayed show timers keyed by widgetId

    // Nonce refresh: single shared promise so concurrent requests don't fire
    // multiple refreshes simultaneously.
    var _nonceRefreshPromise = null;

    function refreshNonce() {
        if (_nonceRefreshPromise) {
            return _nonceRefreshPromise;
        }
        _nonceRefreshPromise = $.ajax({
            url: bwProductGridAjax.ajaxurl,
            type: 'POST',
            data: { action: 'bw_fpw_refresh_nonce' }
        }).then(
            function (response) {
                _nonceRefreshPromise = null;
                if (response && response.success && response.data && response.data.nonce) {
                    bwProductGridAjax.nonce = response.data.nonce;
                }
            },
            function () {
                _nonceRefreshPromise = null;
                return $.Deferred().reject().promise();
            }
        );
        return _nonceRefreshPromise;
    }
    // Spacer elements inserted after .bw-fpw-load-state while an infinite-scroll
    // batch is loading.  Height = 100 vh → prevents the user from scrolling past
    // the loading indicator to the footer before new posts arrive.
    var infiniteLoadSpacers = {};
    // Tracks the fade-out clear timers for subcats/tags containers so they can
    // be cancelled if a new load fires before the 150 ms delay completes.
    // Keys: widgetId + '_subcats' | widgetId + '_tags'
    var filterAnimTimers = {};
    var discoverySearchTimers = {};
    var yearInputCommitTimers = {};
    var CACHE_DURATION = 5 * 60 * 1000; // 5 minutes
    var CACHE_MAX_ENTRIES = 80;

    function getCacheKey(action, params) {
        return action + '_' + JSON.stringify(params);
    }

    function getCachedData(cacheKey) {
        var cached = ajaxCache[cacheKey];
        if (!cached) {
            return null;
        }

        var now = new Date().getTime();
        if (now - cached.timestamp > CACHE_DURATION) {
            delete ajaxCache[cacheKey];
            return null;
        }

        return cached.data;
    }

    function pruneAjaxCache() {
        var now = new Date().getTime();
        var keys = Object.keys(ajaxCache);

        keys.forEach(function (key) {
            var cached = ajaxCache[key];

            if (!cached || now - cached.timestamp > CACHE_DURATION) {
                delete ajaxCache[key];
            }
        });

        keys = Object.keys(ajaxCache);

        if (keys.length <= CACHE_MAX_ENTRIES) {
            return;
        }

        keys
            .sort(function (a, b) {
                return ajaxCache[a].timestamp - ajaxCache[b].timestamp;
            })
            .slice(0, keys.length - CACHE_MAX_ENTRIES)
            .forEach(function (key) {
                delete ajaxCache[key];
            });
    }

    function setCachedData(cacheKey, data) {
        pruneAjaxCache();
        ajaxCache[cacheKey] = {
            data: data,
            timestamp: new Date().getTime()
        };
        pruneAjaxCache();
    }

    function parseInteger(value, fallback) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? fallback : parsed;
    }

    function parseBoolData(value) {
        var normalized = String(value || '').toLowerCase();
        return normalized === '1' || normalized === 'true' || normalized === 'yes';
    }

    function createEmptyYearState() {
        return {
            from: null,
            to: null
        };
    }

    function createEmptyYearBounds() {
        return {
            min: null,
            max: null
        };
    }

    function parseNullableYear(value) {
        var parsed = parseInteger(value, NaN);
        return isNaN(parsed) || parsed <= 0 ? null : parsed;
    }

    function normalizeYearRange(from, to) {
        var normalizedFrom = parseNullableYear(from);
        var normalizedTo = parseNullableYear(to);

        if (normalizedFrom !== null && normalizedTo !== null && normalizedFrom > normalizedTo) {
            var temp = normalizedFrom;
            normalizedFrom = normalizedTo;
            normalizedTo = temp;
        }

        return {
            from: normalizedFrom,
            to: normalizedTo
        };
    }

    function getYearDraftState(state) {
        if (!state.ui.yearDraft) {
            state.ui.yearDraft = {
                from: state.year.from,
                to: state.year.to
            };
        }

        return state.ui.yearDraft;
    }

    function getYearRangeLabel(yearState) {
        if (!yearState) {
            return '';
        }

        if (yearState.from !== null && yearState.to !== null) {
            return yearState.from === yearState.to
                ? String(yearState.from)
                : yearState.from + '–' + yearState.to;
        }

        if (yearState.from !== null) {
            return 'From ' + yearState.from;
        }

        if (yearState.to !== null) {
            return 'Up to ' + yearState.to;
        }

        return '';
    }

    function hasActiveYearFilter(state) {
        return !!(state && state.year && (state.year.from !== null || state.year.to !== null));
    }

    function isWidgetSearchEnabled(widgetId, state) {
        var resolvedState = state || (widgetId ? filterState[widgetId] : null);
        var $grid;

        if (resolvedState && resolvedState.ui && typeof resolvedState.ui.searchEnabled === 'boolean') {
            return resolvedState.ui.searchEnabled;
        }

        if (!widgetId) {
            return true;
        }

        $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first();

        return ($grid.attr('data-search-enabled') || 'yes') === 'yes';
    }

    function hasActiveDiscoveryFilters(state) {
        var hasActiveTokenGroups = false;
        var searchEnabled = false;

        if (!state) {
            return false;
        }

        searchEnabled = isWidgetSearchEnabled(null, state);

        getDiscoveryTokenGroupKeys().forEach(function (groupKey) {
            if (!hasActiveTokenGroups && getDiscoverySelections(state, groupKey).length > 0) {
                hasActiveTokenGroups = true;
            }
        });

        return state.subcategories.length > 0 ||
            state.tags.length > 0 ||
            hasActiveTokenGroups ||
            hasActiveYearFilter(state) ||
            (searchEnabled && $.trim(state.search || '') !== '');
    }

    function disconnectInfiniteObserver(widgetId) {
        var state = widgetPagingState[widgetId];

        if (state && state.observer) {
            state.observer.disconnect();
            state.observer = null;
        }
    }

    function getWidgetPagingState(widgetId) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');

        if (!$grid.length) {
            return null;
        }

        var state = widgetPagingState[widgetId] || {};

        if (state.gridEl && state.gridEl !== $grid[0]) {
            disconnectInfiniteObserver(widgetId);
            state = {};
        }

        state.gridEl = $grid[0];
        state.initialItems = parseInteger($grid.attr('data-initial-items'), typeof state.initialItems === 'number' ? state.initialItems : 12);
        state.loadBatchSize = parseInteger($grid.attr('data-load-batch-size'), typeof state.loadBatchSize === 'number' ? state.loadBatchSize : state.initialItems);
        state.perPage = parseInteger($grid.attr('data-per-page'), typeof state.perPage === 'number' ? state.perPage : 12);
        state.currentPage = Math.max(1, parseInteger($grid.attr('data-current-page'), typeof state.currentPage === 'number' ? state.currentPage : 1));
        state.nextPage = Math.max(0, parseInteger($grid.attr('data-next-page'), typeof state.nextPage === 'number' ? state.nextPage : state.currentPage + 1));
        state.loadedCount = Math.max(0, parseInteger($grid.attr('data-loaded-count'), typeof state.loadedCount === 'number' ? state.loadedCount : $grid.find('.bw-fpw-item').length));
        state.nextOffset = Math.max(0, parseInteger($grid.attr('data-next-offset'), typeof state.nextOffset === 'number' ? state.nextOffset : state.loadedCount));
        state.hasMore = parseBoolData($grid.attr('data-has-more'));
        state.infiniteEnabled = parseBoolData($grid.attr('data-infinite-enabled')) && state.perPage > 0;
        state.loadTriggerOffset = Math.max(0, parseInteger($grid.attr('data-load-trigger-offset'), typeof state.loadTriggerOffset === 'number' ? state.loadTriggerOffset : 600));
        state.isLoading = !!state.isLoading;

        if (state.initialItems === 0) {
            state.initialItems = 12;
        }

        if (state.loadBatchSize < 1) {
            state.loadBatchSize = state.perPage > 0 ? state.perPage : 12;
        }

        if (state.infiniteEnabled) {
            state.perPage = state.loadBatchSize;
        } else if (state.initialItems > 0) {
            state.perPage = state.initialItems;
        }

        widgetPagingState[widgetId] = state;

        return state;
    }

    // Updates the load-state element classes and manages the "loading more"
    // visual indicator.
    //
    // TWO SEPARATE CONCERNS — keep them distinct:
    //
    //   is-loading          Logical flag.  Set as soon as an AJAX request
    //                       starts.  Read by syncInfiniteObserver() to block
    //                       a new observer while a request is in flight.
    //                       NEVER remove this class manually — always go
    //                       through updateWidgetPagingState({ isLoading: … }).
    //
    //   is-loading-visible  Visual flag.  Added only after a 400 ms delay so
    //                       the "LOADING MORE" indicator never flickers for
    //                       fast (cached) responses.  Drives the CSS
    //                       opacity transition on .bw-fpw-load-indicator.
    //
    // If you ever refactor is-loading away, audit syncInfiniteObserver() and
    // loadNextPage() — both guard on state.isLoading directly.
    function updateInfiniteUi(widgetId) {
        var state = getWidgetPagingState(widgetId);

        if (!state) {
            return;
        }

        var $loadState = $('.bw-fpw-load-state[data-widget-id="' + widgetId + '"]');

        if (!$loadState.length) {
            return;
        }

        var isActive = state.infiniteEnabled && state.hasMore;
        var isComplete = state.infiniteEnabled && !state.hasMore;

        $loadState.toggleClass('bw-fpw-load-state--disabled', !state.infiniteEnabled);
        $loadState.toggleClass('bw-fpw-load-state--complete', isComplete);
        $loadState.toggleClass('is-active', isActive);
        $loadState.toggleClass('is-loading', !!state.isLoading); // logical — see note above
        $loadState.attr('data-has-more', state.hasMore ? '1' : '0');

        // is-loading-visible: visual only — delayed to avoid flash on fast loads.
        if (state.isLoading) {
            if (!loadingIndicatorTimers[widgetId]) {
                loadingIndicatorTimers[widgetId] = setTimeout(function () {
                    delete loadingIndicatorTimers[widgetId];
                    $loadState.addClass('is-loading-visible');
                }, 400);
            }
        } else {
            if (loadingIndicatorTimers[widgetId]) {
                clearTimeout(loadingIndicatorTimers[widgetId]);
                delete loadingIndicatorTimers[widgetId];
            }
            $loadState.removeClass('is-loading-visible');
        }
    }

    function updateWidgetPagingState(widgetId, metadata) {
        var state = getWidgetPagingState(widgetId);
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');

        if (!state || !$grid.length) {
            return null;
        }

        metadata = metadata || {};

        if (typeof metadata.perPage !== 'undefined') {
            state.perPage = parseInteger(metadata.perPage, state.perPage);
        }

        if (typeof metadata.initialItems !== 'undefined') {
            state.initialItems = parseInteger(metadata.initialItems, state.initialItems);
        }

        if (typeof metadata.loadBatchSize !== 'undefined') {
            state.loadBatchSize = parseInteger(metadata.loadBatchSize, state.loadBatchSize);
        }

        if (typeof metadata.currentPage !== 'undefined') {
            state.currentPage = Math.max(1, parseInteger(metadata.currentPage, state.currentPage));
        }

        if (typeof metadata.nextPage !== 'undefined') {
            state.nextPage = Math.max(0, parseInteger(metadata.nextPage, state.nextPage));
        }

        if (typeof metadata.hasMore !== 'undefined') {
            state.hasMore = !!metadata.hasMore;
        }

        if (typeof metadata.loadedCount !== 'undefined') {
            state.loadedCount = Math.max(0, parseInteger(metadata.loadedCount, state.loadedCount));
        }

        if (typeof metadata.nextOffset !== 'undefined') {
            state.nextOffset = Math.max(0, parseInteger(metadata.nextOffset, state.nextOffset));
        }

        if (typeof metadata.infiniteEnabled !== 'undefined') {
            state.infiniteEnabled = !!metadata.infiniteEnabled && state.perPage > 0;
        }

        if (typeof metadata.isLoading !== 'undefined') {
            state.isLoading = !!metadata.isLoading;
        }

        if (state.loadBatchSize < 1) {
            state.loadBatchSize = state.perPage > 0 ? state.perPage : 12;
        }

        if (state.infiniteEnabled && state.loadBatchSize > 0) {
            state.perPage = state.loadBatchSize;
        } else if (state.initialItems > 0) {
            state.perPage = state.initialItems;
        }

        if (!state.hasMore) {
            state.nextOffset = 0;
        }

        $grid.attr('data-initial-items', state.initialItems);
        $grid.attr('data-load-batch-size', state.loadBatchSize);
        $grid.attr('data-per-page', state.perPage);
        $grid.attr('data-current-page', state.currentPage);
        $grid.attr('data-next-page', state.nextPage);
        $grid.attr('data-loaded-count', state.loadedCount);
        $grid.attr('data-next-offset', state.nextOffset);
        $grid.attr('data-has-more', state.hasMore ? '1' : '0');
        $grid.attr('data-infinite-enabled', state.infiniteEnabled ? 'yes' : 'no');

        widgetPagingState[widgetId] = state;
        updateInfiniteUi(widgetId);

        return state;
    }

    function syncInfiniteObserver(widgetId) {
        var state = getWidgetPagingState(widgetId);
        var $loadState = $('.bw-fpw-load-state[data-widget-id="' + widgetId + '"]');

        disconnectInfiniteObserver(widgetId);

        if (
            !state ||
            !state.infiniteEnabled ||
            !state.hasMore ||
            state.isLoading ||
            typeof window.IntersectionObserver === 'undefined' ||
            !$loadState.length
        ) {
            return;
        }

        var $sentinel = $loadState.find('.bw-fpw-load-sentinel');

        if (!$sentinel.length) {
            return;
        }

        state.observer = new window.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var currentState = getWidgetPagingState(widgetId);
                    if (!currentState || currentState.isLoading) {
                        return;
                    }
                    disconnectInfiniteObserver(widgetId);
                    loadNextPage(widgetId);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px ' + state.loadTriggerOffset + 'px 0px',
            threshold: 0
        });

        state.observer.observe($sentinel[0]);
        widgetPagingState[widgetId] = state;
    }

    function initFilterState(widgetId) {
        if (!filterState[widgetId]) {
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first();
            var defaultCategory = $filters.attr('data-default-category');
            var initialCategory = 'all';
            var searchEnabled = ($grid.attr('data-search-enabled') || 'yes') === 'yes';

            if (defaultCategory && defaultCategory !== 'all') {
                initialCategory = defaultCategory;
            }

            filterState[widgetId] = {
                sortKey: normalizeDiscoverySortKey($grid.attr('data-default-sort-key') || 'default'),
                category: initialCategory,
                subcategories: [],
                tags: [],
                artists: [],
                authors: [],
                publishers: [],
                sources: [],
                techniques: [],
                search: '',
                appliedSearch: '',
                year: createEmptyYearState(),
                yearBounds: createEmptyYearBounds(),
                yearQuickRanges: [],
                resultCount: 0,
                options: {
                    types: [],
                    tags: [],
                    artist: [],
                    author: [],
                    publisher: [],
                    source: [],
                    technique: []
                },
                labels: {
                    types: {},
                    tags: {},
                    artist: {},
                    author: {},
                    publisher: {},
                    source: {},
                    technique: {}
                },
                ui: {
                    showTypes: true,
                    showTags: true,
                    showYears: false,
                    showArtist: false,
                    showAuthor: false,
                    showPublisher: false,
                    showSource: false,
                    showTechnique: false,
                    showOrderBy: ($grid.attr('data-show-order-by') || 'no') === 'yes',
                    showVisibleFilters: ($grid.attr('data-show-visible-filters') || 'no') === 'yes',
                    orderTriggerStyle: ($grid.attr('data-order-trigger-style') || 'icon') === 'dropdown' ? 'dropdown' : 'icon',
                    sortMenuOpen: false,
                    visibleFilterOpenGroup: '',
                    optionSearches: {
                        types: '',
                        tags: '',
                        artist: '',
                        author: '',
                        publisher: '',
                        source: '',
                        technique: ''
                    },
                    openGroups: {
                        types: false,
                        tags: false,
                        years: false,
                        artist: false,
                        author: false,
                        publisher: false,
                        source: false,
                        technique: false
                    },
                    searchEnabled: searchEnabled,
                    lastFilterUiSignature: '',
                    yearDraft: {
                        from: null,
                        to: null
                    }
                }
            };
        }
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function uniqueIntArray(values) {
        var seen = {};
        var result = [];

        (Array.isArray(values) ? values : []).forEach(function (value) {
            var parsed = parseInteger(value, NaN);

            if (!isNaN(parsed) && parsed > 0 && !seen[parsed]) {
                seen[parsed] = true;
                result.push(parsed);
            }
        });

        return result;
    }

    function normalizeDiscoveryText(value) {
        var text = String(value || '').toLowerCase();

        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        return $.trim(text);
    }

    function getDiscoveryTokenGroupKeys() {
        return ['artist', 'author', 'publisher', 'source', 'technique'];
    }

    function isDiscoveryTokenGroup(groupKey) {
        return getDiscoveryTokenGroupKeys().indexOf(groupKey) > -1;
    }

    function normalizeDiscoveryTokenValue(value) {
        return normalizeDiscoveryText(value);
    }

    function uniqueStringArray(values) {
        var seen = {};
        var result = [];

        (Array.isArray(values) ? values : []).forEach(function (value) {
            var normalized = normalizeDiscoveryTokenValue(value);

            if (normalized && !seen[normalized]) {
                seen[normalized] = true;
                result.push(normalized);
            }
        });

        return result;
    }

    function getDiscoverySortOptions() {
        return {
            'default': {
                triggerLabel: 'Default',
                menuLabel: 'Default order',
                orderBy: null,
                order: null
            },
            recent: {
                triggerLabel: 'Latest',
                menuLabel: 'Recently added',
                orderBy: 'date',
                order: 'DESC'
            },
            oldest: {
                triggerLabel: 'Earliest',
                menuLabel: 'Oldest added',
                orderBy: 'date',
                order: 'ASC'
            },
            title_asc: {
                triggerLabel: 'A–Z',
                menuLabel: 'Alphabetical A to Z',
                orderBy: 'title',
                order: 'ASC'
            },
            title_desc: {
                triggerLabel: 'Z–A',
                menuLabel: 'Alphabetical Z to A',
                orderBy: 'title',
                order: 'DESC'
            },
            year_asc: {
                triggerLabel: 'Year ↑',
                menuLabel: 'Year, oldest first',
                orderBy: 'date',
                order: 'ASC'
            },
            year_desc: {
                triggerLabel: 'Year ↓',
                menuLabel: 'Year, newest first',
                orderBy: 'date',
                order: 'DESC'
            }
        };
    }

    function normalizeDiscoverySortKey(value) {
        var sortKey = String(value || 'default').toLowerCase();
        var options = getDiscoverySortOptions();

        return options.hasOwnProperty(sortKey) ? sortKey : 'default';
    }

    function getDefaultDiscoverySortConfig(widgetId) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first();
        var orderBy = String($grid.attr('data-default-order-by') || $grid.attr('data-order-by') || 'date');
        var order = String($grid.attr('data-default-order') || $grid.attr('data-order') || 'DESC').toUpperCase();

        if (['date', 'modified', 'title', 'rand', 'ID'].indexOf(orderBy) === -1) {
            orderBy = 'date';
        }

        if (['ASC', 'DESC'].indexOf(order) === -1) {
            order = 'DESC';
        }

        if (orderBy === 'rand') {
            order = 'ASC';
        }

        return {
            orderBy: orderBy,
            order: order
        };
    }

    function isDiscoverySortEnabled(widgetId, state) {
        var resolvedState = state || (widgetId ? filterState[widgetId] : null);
        var $grid;

        if (resolvedState && resolvedState.ui && typeof resolvedState.ui.showOrderBy === 'boolean') {
            return resolvedState.ui.showOrderBy;
        }

        if (!widgetId) {
            return false;
        }

        $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first();
        return ($grid.attr('data-show-order-by') || 'no') === 'yes';
    }

    function isDiscoveryVisibleFiltersEnabled(widgetId, state) {
        var resolvedState = state || (widgetId ? filterState[widgetId] : null);
        var $grid;

        if (resolvedState && resolvedState.ui && typeof resolvedState.ui.showVisibleFilters === 'boolean') {
            return resolvedState.ui.showVisibleFilters;
        }

        if (!widgetId) {
            return false;
        }

        $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first();
        return ($grid.attr('data-show-visible-filters') || 'no') === 'yes';
    }

    function getDiscoveryGroupConfigs() {
        return [
            {
                key: 'types',
                label: 'Categories',
                visibleLabel: 'Categories',
                placeholder: 'Search types...'
            },
            {
                key: 'tags',
                label: 'Style / Subject',
                visibleLabel: 'Style / Subject',
                placeholder: 'Search styles...'
            },
            {
                key: 'years',
                label: 'Years',
                visibleLabel: 'Year',
                placeholder: ''
            },
            {
                key: 'artist',
                label: 'Artist',
                visibleLabel: 'Artists',
                placeholder: 'Search artists...'
            },
            {
                key: 'author',
                label: 'Author',
                visibleLabel: 'Author',
                placeholder: 'Search authors...'
            },
            {
                key: 'publisher',
                label: 'Publisher',
                visibleLabel: 'Publisher',
                placeholder: 'Search publishers...'
            },
            {
                key: 'source',
                label: 'Source',
                visibleLabel: 'Source',
                placeholder: 'Search sources...'
            },
            {
                key: 'technique',
                label: 'Technique',
                visibleLabel: 'Technique',
                placeholder: 'Search techniques...'
            }
        ];
    }

    function getDiscoveryGroupConfig(groupKey) {
        var config = null;

        getDiscoveryGroupConfigs().some(function (groupConfig) {
            if (groupConfig.key === groupKey) {
                config = groupConfig;
                return true;
            }

            return false;
        });

        return config;
    }

    function getVisibleDiscoveryGroupKeys() {
        return ['types', 'tags', 'artist', 'author', 'source', 'technique', 'years'];
    }

    function getDiscoverySortTriggerStyle(widgetId, state) {
        var resolvedState = state || (widgetId ? filterState[widgetId] : null);
        var $grid;

        if (resolvedState && resolvedState.ui && resolvedState.ui.orderTriggerStyle) {
            return resolvedState.ui.orderTriggerStyle;
        }

        if (!widgetId) {
            return 'icon';
        }

        $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first();
        return ($grid.attr('data-order-trigger-style') || 'icon') === 'dropdown' ? 'dropdown' : 'icon';
    }

    function getEffectiveDiscoverySortConfig(widgetId, state) {
        var resolvedState = state || getDiscoveryState(widgetId);
        var sortKey = normalizeDiscoverySortKey(resolvedState && resolvedState.sortKey ? resolvedState.sortKey : 'default');
        var options = getDiscoverySortOptions();
        var option = options[sortKey] || options['default'];
        var defaults = getDefaultDiscoverySortConfig(widgetId);

        return {
            sortKey: sortKey,
            triggerLabel: option.triggerLabel,
            menuLabel: option.menuLabel,
            orderBy: sortKey === 'default' ? defaults.orderBy : option.orderBy,
            order: sortKey === 'default' ? defaults.order : option.order
        };
    }

    function getDiscoverySelectionStateKey(groupKey) {
        switch (groupKey) {
            case 'types':
                return 'subcategories';
            case 'tags':
                return 'tags';
            case 'artist':
                return 'artists';
            case 'author':
                return 'authors';
            case 'publisher':
                return 'publishers';
            case 'source':
                return 'sources';
            case 'technique':
                return 'techniques';
            default:
                return '';
        }
    }

    function getDiscoveryGroupVisibilityFlag(groupKey) {
        switch (groupKey) {
            case 'types':
                return 'showTypes';
            case 'tags':
                return 'showTags';
            case 'years':
                return 'showYears';
            case 'artist':
                return 'showArtist';
            case 'author':
                return 'showAuthor';
            case 'publisher':
                return 'showPublisher';
            case 'source':
                return 'showSource';
            case 'technique':
                return 'showTechnique';
            default:
                return '';
        }
    }

    function getDiscoveryOptionKey(groupKey, option) {
        if (groupKey === 'types' || groupKey === 'tags') {
            return parseInteger(option && option.term_id, 0);
        }

        return normalizeDiscoveryTokenValue(option && option.value);
    }

    function getDiscoveryBootstrapPayload(widgetId) {
        var $bootstrap = $('.bw-fpw-discovery-bootstrap[data-widget-id="' + widgetId + '"]').first();

        if (!$bootstrap.length) {
            return null;
        }

        try {
            return JSON.parse($bootstrap.text() || '{}');
        } catch (error) {
            return null;
        }
    }

    function isDiscoveryDrawerMode(widgetId) {
        return isResponsiveFilterDrawerMode(widgetId);
    }

    function storeDiscoveryLabels(state, groupKey, options) {
        if (!state || !state.labels || !state.labels[groupKey]) {
            return;
        }

        (Array.isArray(options) ? options : []).forEach(function (option) {
            var optionKey = getDiscoveryOptionKey(groupKey, option);

            if ((isDiscoveryTokenGroup(groupKey) ? !!optionKey : optionKey > 0) && option.name) {
                state.labels[groupKey][optionKey] = option.name;
            }
        });
    }

    function sortDiscoveryOptions(options) {
        return (Array.isArray(options) ? options.slice() : []).sort(function (a, b) {
            var countA = parseInteger(a && a.count, 0);
            var countB = parseInteger(b && b.count, 0);

            if (countA !== countB) {
                return countB - countA;
            }

            return String(a && a.name || '').localeCompare(String(b && b.name || ''));
        });
    }

    function mergeSelectedDiscoveryOptions(state, groupKey, options) {
        var selectedIds = getDiscoverySelections(state, groupKey);
        var optionMap = {};

        sortDiscoveryOptions(options).forEach(function (option) {
            var optionKey = getDiscoveryOptionKey(groupKey, option);

            if (isDiscoveryTokenGroup(groupKey) ? !!optionKey : optionKey > 0) {
                optionMap[String(optionKey)] = isDiscoveryTokenGroup(groupKey) ? {
                    value: optionKey,
                    name: option.name || state.labels[groupKey][optionKey] || '',
                    count: Math.max(0, parseInteger(option.count, 0))
                } : {
                    term_id: optionKey,
                    name: option.name || state.labels[groupKey][optionKey] || '',
                    count: Math.max(0, parseInteger(option.count, 0))
                };
            }
        });

        (Array.isArray(selectedIds) ? selectedIds : []).forEach(function (selectedValue) {
            var mapKey = String(selectedValue);
            if (!optionMap[mapKey]) {
                optionMap[mapKey] = isDiscoveryTokenGroup(groupKey) ? {
                    value: selectedValue,
                    name: state.labels[groupKey][selectedValue] || '',
                    count: 0
                } : {
                    term_id: selectedValue,
                    name: state.labels[groupKey][selectedValue] || '',
                    count: 0
                };
            }
        });

        return sortDiscoveryOptions(Object.keys(optionMap).map(function (key) {
            return optionMap[key];
        })).filter(function (option) {
            var optionKey = getDiscoveryOptionKey(groupKey, option);
            return (isDiscoveryTokenGroup(groupKey) ? !!optionKey : optionKey > 0) && option.name;
        });
    }

    function updateDiscoveryOptions(widgetId, filterUi) {
        var state = filterState[widgetId];

        if (!state || !filterUi) {
            return;
        }

        if (Array.isArray(filterUi.types)) {
            storeDiscoveryLabels(state, 'types', filterUi.types);
            state.options.types = mergeSelectedDiscoveryOptions(state, 'types', filterUi.types);
        }

        if (Array.isArray(filterUi.tags)) {
            storeDiscoveryLabels(state, 'tags', filterUi.tags);
            state.options.tags = mergeSelectedDiscoveryOptions(state, 'tags', filterUi.tags);
        }

        // null means server skipped advanced UI rebuild (e.g. append load) — keep state as-is.
        if (filterUi.advanced !== null && filterUi.advanced !== undefined) {
            getDiscoveryTokenGroupKeys().forEach(function (groupKey) {
                var groupUi = filterUi.advanced[groupKey] || null;
                var visibilityFlag = getDiscoveryGroupVisibilityFlag(groupKey);
                var stateKey = getDiscoverySelectionStateKey(groupKey);

                if (visibilityFlag) {
                    state.ui[visibilityFlag] = !!(groupUi && groupUi.supported);
                }

                if (groupUi && Array.isArray(groupUi.options)) {
                    storeDiscoveryLabels(state, groupKey, groupUi.options);
                    state.options[groupKey] = mergeSelectedDiscoveryOptions(state, groupKey, groupUi.options);
                } else if (visibilityFlag && !state.ui[visibilityFlag]) {
                    state.options[groupKey] = [];
                    if (stateKey) {
                        state[stateKey] = [];
                    }
                    state.ui.optionSearches[groupKey] = '';
                    state.ui.openGroups[groupKey] = false;
                }
            });
        }

        if (typeof filterUi.result_count !== 'undefined') {
            state.resultCount = Math.max(0, parseInteger(filterUi.result_count, state.resultCount));
        }

        if (filterUi.year) {
            state.ui.showYears = !!filterUi.year.supported;
            state.yearBounds = {
                min: parseNullableYear(filterUi.year.min),
                max: parseNullableYear(filterUi.year.max)
            };
            state.yearQuickRanges = Array.isArray(filterUi.year.quick_ranges) ? filterUi.year.quick_ranges.slice() : [];

            if (!state.ui.showYears) {
                state.year = createEmptyYearState();
            }

            state.ui.yearDraft = {
                from: state.year.from,
                to: state.year.to
            };
        }
    }

    function getDiscoveryFilterUiSignature(filterUi) {
        if (!filterUi) {
            return '';
        }

        try {
            return JSON.stringify(filterUi) || '';
        } catch (error) {
            return '';
        }
    }

    function getDiscoveryState(widgetId) {
        initFilterState(widgetId);
        return filterState[widgetId];
    }

    function getDiscoverySelections(state, groupKey) {
        var stateKey = getDiscoverySelectionStateKey(groupKey);
        return stateKey && Array.isArray(state[stateKey]) ? state[stateKey] : [];
    }

    function hasDiscoverySelection(state, groupKey, termId) {
        return getDiscoverySelections(state, groupKey).indexOf(termId) > -1;
    }

    function formatResultCount(count) {
        var safeCount = Math.max(0, parseInteger(count, 0));
        return safeCount + (safeCount === 1 ? ' result' : ' results');
    }

    function isDiscoveryResponsiveToolbar() {
        return (window.innerWidth || $(window).width() || 0) <= 800;
    }

    function renderDiscoveryResultCount(widgetId) {
        var state = filterState[widgetId];
        var resultText = formatResultCount(state && typeof state.resultCount !== 'undefined' ? state.resultCount : 0);
        var hasActiveFilters = hasActiveDiscoveryFilters(state);

        $('.bw-fpw-discovery-result-count[data-widget-id="' + widgetId + '"]').text(resultText);
        $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').attr('data-result-count', state.resultCount || 0);
        $('.bw-fpw-discovery-reset[data-widget-id="' + widgetId + '"]').toggleClass('is-hidden', !hasActiveFilters);
    }

    function getDiscoveryActiveChips(widgetId) {
        var state = filterState[widgetId];
        var chips = [];

        if (!state) {
            return chips;
        }

        state.subcategories.forEach(function (termId) {
            chips.push({
                group: 'types',
                term_id: termId,
                name: state.labels.types[termId] || '',
                selected: true
            });
        });

        state.tags.forEach(function (termId) {
            chips.push({
                group: 'tags',
                value: termId,
                name: state.labels.tags[termId] || '',
                selected: true
            });
        });

        getDiscoveryTokenGroupKeys().forEach(function (groupKey) {
            getDiscoverySelections(state, groupKey).forEach(function (tokenValue) {
                chips.push({
                    group: groupKey,
                    value: tokenValue,
                    name: state.labels[groupKey][tokenValue] || '',
                    selected: true
                });
            });
        });

        if (hasActiveYearFilter(state)) {
            chips.push({
                group: 'years',
                value: '',
                name: getYearRangeLabel(state.year),
                selected: true
            });
        }

        return chips.filter(function (chip) {
            return !!chip.name;
        });
    }

    function buildDiscoveryChipHtml(chip, widgetId) {
        var filterValue = typeof chip.value !== 'undefined' ? chip.value : chip.term_id;

        return '<div class="bw-fpw-active-chip bw-fpw-quick-filter is-selected" data-widget-id="' + widgetId + '" data-group="' + chip.group + '" data-filter-value="' + escapeHtml(filterValue == null ? '' : filterValue) + '">' +
            '<span class="bw-fpw-active-chip__label bw-fpw-quick-filter__label">' + escapeHtml(chip.name) + '</span>' +
            '<button class="bw-fpw-active-chip__remove bw-fpw-quick-filter__remove" type="button" aria-label="Remove ' + escapeHtml(chip.name) + '" data-widget-id="' + widgetId + '" data-group="' + chip.group + '" data-filter-value="' + escapeHtml(filterValue == null ? '' : filterValue) + '"></button>' +
            '</div>';
    }

    function renderDiscoveryActiveChips(widgetId) {
        var activeChips = getDiscoveryActiveChips(widgetId);
        var html = '';
        var containers;
        var state = getDiscoveryState(widgetId);

        activeChips.forEach(function (chip) {
            html += buildDiscoveryChipHtml(chip, widgetId);
        });

        containers = document.querySelectorAll('.bw-fpw-active-chips[data-widget-id="' + widgetId + '"]');

        containers.forEach(function (container) {
            if (container.classList.contains('bw-fpw-quick-filters') && isDiscoveryVisibleFiltersEnabled(widgetId, state)) {
                container.innerHTML = '';
                container.classList.add('is-empty');
                return;
            }

            container.innerHTML = html;
            container.classList.toggle('is-empty', html === '');
        });
    }

    function getResolvedYearDraft(state) {
        var draft = getYearDraftState(state);
        var bounds = state.yearBounds || createEmptyYearBounds();
        var fallbackMin = parseNullableYear(bounds.min);
        var fallbackMax = parseNullableYear(bounds.max);
        var normalized = normalizeYearRange(
            draft.from !== null ? draft.from : state.year.from,
            draft.to !== null ? draft.to : state.year.to
        );

        return {
            from: normalized.from !== null ? normalized.from : fallbackMin,
            to: normalized.to !== null ? normalized.to : fallbackMax,
            min: fallbackMin,
            max: fallbackMax
        };
    }

    function buildYearSliderTrackStyle(resolvedDraft) {
        if (!resolvedDraft || resolvedDraft.min === null || resolvedDraft.max === null || resolvedDraft.max <= resolvedDraft.min) {
            return '';
        }

        var span = resolvedDraft.max - resolvedDraft.min;
        var start = Math.max(0, ((resolvedDraft.from - resolvedDraft.min) / span) * 100);
        var end = Math.max(start, ((resolvedDraft.to - resolvedDraft.min) / span) * 100);

        return 'left:' + start + '%;width:' + Math.max(0, end - start) + '%;';
    }

    function renderDiscoveryYearPanelContent(widgetId, state) {
        var bounds = state.yearBounds || createEmptyYearBounds();
        var resolvedDraft = getResolvedYearDraft(state);
        var quickRanges = Array.isArray(state.yearQuickRanges) ? state.yearQuickRanges : [];
        var html = '';

        if (bounds.min !== null && bounds.max !== null) {
            html += '<div class="bw-fpw-year-slider" data-widget-id="' + widgetId + '">';
            html += '<div class="bw-fpw-year-slider__topline"><span class="bw-fpw-year-slider__bound">' + escapeHtml(bounds.min) + '</span><span class="bw-fpw-year-slider__bound">' + escapeHtml(bounds.max) + '</span></div>';
            html += '<div class="bw-fpw-year-slider__range">';
            html += '<div class="bw-fpw-year-slider__track"></div>';
            html += '<div class="bw-fpw-year-slider__active" style="' + buildYearSliderTrackStyle(resolvedDraft) + '"></div>';
            html += '<input class="bw-fpw-year-slider__input bw-fpw-year-slider__input--from" type="range" min="' + escapeHtml(bounds.min) + '" max="' + escapeHtml(bounds.max) + '" value="' + escapeHtml(resolvedDraft.from !== null ? resolvedDraft.from : bounds.min) + '" data-widget-id="' + widgetId + '" data-year-edge="from" />';
            html += '<input class="bw-fpw-year-slider__input bw-fpw-year-slider__input--to" type="range" min="' + escapeHtml(bounds.min) + '" max="' + escapeHtml(bounds.max) + '" value="' + escapeHtml(resolvedDraft.to !== null ? resolvedDraft.to : bounds.max) + '" data-widget-id="' + widgetId + '" data-year-edge="to" />';
            html += '</div>';
            html += '<div class="bw-fpw-year-slider__selection">' + escapeHtml(getYearRangeLabel(normalizeYearRange(resolvedDraft.from, resolvedDraft.to)) || 'Any year') + '</div>';
            html += '</div>';
        }

        html += '<div class="bw-fpw-year-inputs">';
        html += '<label class="bw-fpw-year-input"><span class="bw-fpw-year-input__label">From</span><input class="bw-fpw-year-input__field" type="number" inputmode="numeric" min="' + escapeHtml(bounds.min !== null ? bounds.min : '') + '" max="' + escapeHtml(bounds.max !== null ? bounds.max : '') + '" value="' + escapeHtml(state.year.from !== null ? state.year.from : '') + '" placeholder="From" data-widget-id="' + widgetId + '" data-year-field="from" /></label>';
        html += '<label class="bw-fpw-year-input"><span class="bw-fpw-year-input__label">To</span><input class="bw-fpw-year-input__field" type="number" inputmode="numeric" min="' + escapeHtml(bounds.min !== null ? bounds.min : '') + '" max="' + escapeHtml(bounds.max !== null ? bounds.max : '') + '" value="' + escapeHtml(state.year.to !== null ? state.year.to : '') + '" placeholder="To" data-widget-id="' + widgetId + '" data-year-field="to" /></label>';
        html += '</div>';

        if (quickRanges.length) {
            html += '<div class="bw-fpw-year-quick-ranges">';
            quickRanges.forEach(function (range) {
                var rangeFrom = parseNullableYear(range.from);
                var rangeTo = parseNullableYear(range.to);
                var isSelected = state.year.from === rangeFrom && state.year.to === rangeTo;
                html += '<button class="bw-fpw-year-quick-range' + (isSelected ? ' is-selected' : '') + '" type="button" data-widget-id="' + widgetId + '" data-year-from="' + escapeHtml(rangeFrom !== null ? rangeFrom : '') + '" data-year-to="' + escapeHtml(rangeTo !== null ? rangeTo : '') + '">' + escapeHtml(range.label || getYearRangeLabel({ from: rangeFrom, to: rangeTo })) + '</button>';
            });
            html += '</div>';
        }

        return html;
    }

    function renderDiscoveryTokenPanelContent(widgetId, state, groupConfig, surfaceKey) {
        var groupKey = groupConfig.key;
        var options = state.options[groupKey] || [];
        var selectedIds = getDiscoverySelections(state, groupKey);
        var termSearch = normalizeDiscoveryText(state.ui.optionSearches[groupKey]);
        var visibleOptions = options.filter(function (option) {
            if (!termSearch) {
                return true;
            }

            return normalizeDiscoveryText(option.name).indexOf(termSearch) > -1;
        });
        var html = '';

        html += '<label class="bw-fpw-discovery-group-search">';
        html += '<span class="bw-fpw-discovery-group-search__icon" aria-hidden="true"></span>';
        html += '<input class="bw-fpw-discovery-group-search__input" type="search" data-widget-id="' + widgetId + '" data-group="' + groupKey + '" data-surface="' + escapeHtml(surfaceKey || 'drawer') + '" value="' + escapeHtml(state.ui.optionSearches[groupKey]) + '" placeholder="' + escapeHtml(groupConfig.placeholder) + '" autocomplete="off" />';
        html += '</label>';
        html += '<div class="bw-fpw-discovery-options">';

        if (visibleOptions.length) {
            visibleOptions.forEach(function (option) {
                var optionValue = getDiscoveryOptionKey(groupKey, option);
                var isSelected = selectedIds.indexOf(optionValue) > -1;
                var isDisabled = !isSelected && parseInteger(option.count, 0) <= 0;

                html += '<button class="bw-fpw-discovery-option' + (isSelected ? ' is-selected' : '') + (isDisabled ? ' is-disabled' : '') + '" type="button" data-widget-id="' + widgetId + '" data-group="' + groupKey + '" data-filter-value="' + escapeHtml(optionValue) + '"' + (isDisabled ? ' disabled' : '') + '>';
                html += '<span class="bw-fpw-discovery-option__check"><span class="bw-fpw-discovery-option__tick"></span></span>';
                html += '<span class="bw-fpw-discovery-option__label">' + escapeHtml(option.name) + '</span>';
                html += '<span class="bw-fpw-discovery-option__count">' + escapeHtml(parseInteger(option.count, 0)) + '</span>';
                html += '</button>';
            });
        } else {
            html += '<div class="bw-fpw-discovery-options__empty">No matches found</div>';
        }

        html += '</div>';

        return html;
    }

    function updateYearRangePresentation(widgetId) {
        var state = getDiscoveryState(widgetId);
        var resolvedDraft = getResolvedYearDraft(state);
        var normalizedLabel = getYearRangeLabel(normalizeYearRange(resolvedDraft.from, resolvedDraft.to)) || 'Any year';
        var $context = $('.bw-fpw-discovery-group--years[data-widget-id="' + widgetId + '"], .bw-fpw-visible-filter[data-widget-id="' + widgetId + '"][data-group="years"]');

        if (!$context.length) {
            return;
        }

        $context.find('.bw-fpw-year-slider__active').attr('style', buildYearSliderTrackStyle(resolvedDraft));
        $context.find('.bw-fpw-year-slider__selection').text(normalizedLabel);

        $context.find('.bw-fpw-year-slider__input--from').val(resolvedDraft.from !== null ? resolvedDraft.from : resolvedDraft.min);
        $context.find('.bw-fpw-year-slider__input--to').val(resolvedDraft.to !== null ? resolvedDraft.to : resolvedDraft.max);
        $context.find('.bw-fpw-year-input__field[data-year-field="from"]').val(state.ui.yearDraft.from !== null ? state.ui.yearDraft.from : '');
        $context.find('.bw-fpw-year-input__field[data-year-field="to"]').val(state.ui.yearDraft.to !== null ? state.ui.yearDraft.to : '');
    }

    function renderDiscoveryDrawerGroups(widgetId) {
        var state = filterState[widgetId];
        var $groups = $('.bw-fpw-drawer-groups[data-widget-id="' + widgetId + '"]');
        var chevronIcon = '<svg class="bw-fpw-discovery-group__chevron-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>';
        var html = '';

        if (!state || !$groups.length) {
            return;
        }

        getDiscoveryGroupConfigs().forEach(function (groupConfig) {
            var groupKey = groupConfig.key;
            var visibilityFlag = getDiscoveryGroupVisibilityFlag(groupKey);
            var showGroup = visibilityFlag ? !!state.ui[visibilityFlag] : false;
            var termSearch = groupKey === 'years' ? '' : normalizeDiscoveryText(state.ui.optionSearches[groupKey]);
            var isOpen = groupKey === 'years'
                ? (!!state.ui.openGroups.years || hasActiveYearFilter(state))
                : (!!state.ui.openGroups[groupKey] || !!termSearch);

            if (!showGroup) {
                return;
            }

            if (groupKey === 'years') {
                html += '<section class="bw-fpw-discovery-group bw-fpw-discovery-group--years' + (isOpen ? ' is-open' : '') + '" data-widget-id="' + widgetId + '" data-group="years">';
                html += '<button class="bw-fpw-discovery-group__toggle" type="button" data-widget-id="' + widgetId + '" data-group="years">';
                html += '<span class="bw-fpw-discovery-group__title">' + escapeHtml(groupConfig.label) + '</span>';
                html += '<span class="bw-fpw-discovery-group__chevron" aria-hidden="true">' + chevronIcon + '</span>';
                html += '</button>';
                html += '<div class="bw-fpw-discovery-group__panel" aria-hidden="' + (isOpen ? 'false' : 'true') + '">';
                html += renderDiscoveryYearPanelContent(widgetId, state);
                html += '</div>';
                html += '</section>';
                return;
            }

            html += '<section class="bw-fpw-discovery-group' + (isOpen ? ' is-open' : '') + '" data-widget-id="' + widgetId + '" data-group="' + groupKey + '">';
            html += '<button class="bw-fpw-discovery-group__toggle" type="button" data-widget-id="' + widgetId + '" data-group="' + groupKey + '">';
            html += '<span class="bw-fpw-discovery-group__title">' + escapeHtml(groupConfig.label) + '</span>';
            html += '<span class="bw-fpw-discovery-group__chevron" aria-hidden="true">' + chevronIcon + '</span>';
            html += '</button>';
            html += '<div class="bw-fpw-discovery-group__panel" aria-hidden="' + (isOpen ? 'false' : 'true') + '">';
            html += renderDiscoveryTokenPanelContent(widgetId, state, groupConfig, 'drawer');
            html += '</div>';
            html += '</section>';
        });

        $groups.html(html);
    }

    function getDiscoveryVisibleFilterSummary(state, groupKey) {
        if (!state) {
            return '';
        }

        if (groupKey === 'years') {
            return hasActiveYearFilter(state) ? '1' : '';
        }

        var selected = getDiscoverySelections(state, groupKey);
        return selected.length > 0 ? String(selected.length) : '';
    }

    function getDiscoveryVisibleFilterInlineSummary(state, groupKey) {
        if (!state) {
            return '';
        }

        if (groupKey === 'years' && hasActiveYearFilter(state)) {
            return getYearRangeLabel(state.year) || '';
        }

        return '';
    }

    function clearDiscoveryFilterGroup(widgetId, groupKey) {
        var state = getDiscoveryState(widgetId);
        var stateKey = getDiscoverySelectionStateKey(groupKey);

        if (!state) {
            return;
        }

        if (groupKey === 'types') {
            state.subcategories = [];
        } else if (groupKey === 'tags') {
            state.tags = [];
        } else if (groupKey === 'years') {
            state.year = createEmptyYearState();
            state.ui.yearDraft = createEmptyYearState();
        } else if (stateKey && isDiscoveryTokenGroup(groupKey)) {
            state[stateKey] = [];
        } else {
            return;
        }

        if (state.ui.visibleFilterOpenGroup === groupKey) {
            state.ui.visibleFilterOpenGroup = '';
        }

        renderDiscoveryUi(widgetId);
        filterPosts(widgetId);
    }

    function closeDiscoveryVisibleFilterPanel(widgetId) {
        var state = filterState[widgetId];

        if (!state || !state.ui || !state.ui.visibleFilterOpenGroup) {
            return;
        }

        state.ui.visibleFilterOpenGroup = '';
        renderDiscoveryVisibleFilters(widgetId);
    }

    function closeAllDiscoveryVisibleFilterPanels(exceptWidgetId) {
        Object.keys(filterState).forEach(function (widgetId) {
            if (exceptWidgetId && String(widgetId) === String(exceptWidgetId)) {
                return;
            }

            closeDiscoveryVisibleFilterPanel(widgetId);
        });
    }

    function renderDiscoveryVisibleFilters(widgetId) {
        var state = filterState[widgetId];
        var $containers = $('.bw-fpw-visible-filters[data-widget-id="' + widgetId + '"]');
        var html = '';

        if (!state || !$containers.length) {
            return;
        }

        if (!isDiscoveryVisibleFiltersEnabled(widgetId, state)) {
            $containers.empty().addClass('is-hidden').attr('aria-hidden', 'true');
            return;
        }

        getVisibleDiscoveryGroupKeys().forEach(function (groupKey) {
            var groupConfig = getDiscoveryGroupConfig(groupKey);
            var visibilityFlag = getDiscoveryGroupVisibilityFlag(groupKey);
            var isSupported = visibilityFlag ? !!state.ui[visibilityFlag] : false;
            var isOpen = state.ui.visibleFilterOpenGroup === groupKey;
            var summary = getDiscoveryVisibleFilterSummary(state, groupKey);
            var inlineSummary = getDiscoveryVisibleFilterInlineSummary(state, groupKey);
            var hasActiveSelection = groupKey === 'years'
                ? hasActiveYearFilter(state)
                : getDiscoverySelections(state, groupKey).length > 0;

            if (!groupConfig || !isSupported) {
                return;
            }

            html += '<div class="bw-fpw-visible-filter' + (isOpen ? ' is-open' : '') + (hasActiveSelection ? ' is-selected' : '') + '" data-widget-id="' + widgetId + '" data-group="' + groupKey + '">';
            html += '<button class="bw-fpw-visible-filter__trigger' + (hasActiveSelection ? ' has-active-selection' : '') + '" type="button" data-widget-id="' + widgetId + '" data-group="' + groupKey + '" aria-expanded="' + (isOpen ? 'true' : 'false') + '">';
            html += '<span class="bw-fpw-visible-filter__label">' + escapeHtml(groupConfig.visibleLabel || groupConfig.label) + '</span>';
            if (inlineSummary) {
                html += '<span class="bw-fpw-visible-filter__summary">' + escapeHtml(inlineSummary) + '</span>';
            }
            if (!hasActiveSelection) {
                html += '<span class="bw-fpw-visible-filter__chevron" aria-hidden="true"><svg class="bw-fpw-visible-filter__chevron-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>';
            }
            html += '</button>';

            if (hasActiveSelection) {
                html += '<button class="bw-fpw-visible-filter__clear' + (groupKey === 'years' ? ' is-clear-only' : '') + '" type="button" data-widget-id="' + widgetId + '" data-group="' + groupKey + '" aria-label="Clear ' + escapeHtml(groupConfig.visibleLabel || groupConfig.label) + '">';
                html += '<span class="bw-fpw-visible-filter__clear-count">' + escapeHtml(summary) + '</span>';
                html += '<span class="bw-fpw-visible-filter__clear-x" aria-hidden="true"></span>';
                html += '</button>';
            }

            if (isOpen) {
                html += '<div class="bw-fpw-visible-filter__panel" aria-hidden="false">';
                html += '<div class="bw-fpw-visible-filter__panel-inner">';

                if (groupKey === 'years') {
                    html += renderDiscoveryYearPanelContent(widgetId, state);
                } else {
                    html += renderDiscoveryTokenPanelContent(widgetId, state, groupConfig, 'visible');
                }

                html += '</div>';
                html += '</div>';
            }

            html += '</div>';
        });

        $containers
            .html(html)
            .toggleClass('is-hidden', html === '')
            .attr('aria-hidden', html === '' ? 'true' : 'false');
    }

    function closeDiscoverySortMenu(widgetId) {
        var state = filterState[widgetId];

        if (!state || !state.ui || !state.ui.sortMenuOpen) {
            return;
        }

        state.ui.sortMenuOpen = false;
        renderDiscoverySortControl(widgetId);
    }

    function closeAllDiscoverySortMenus(exceptWidgetId) {
        Object.keys(filterState).forEach(function (widgetId) {
            if (exceptWidgetId && String(widgetId) === String(exceptWidgetId)) {
                return;
            }

            closeDiscoverySortMenu(widgetId);
        });
    }

    function renderDiscoverySortControl(widgetId) {
        var state = filterState[widgetId];
        var config;
        var isOpen;
        var $sort;
        var $trigger;
        var $menu;

        if (!isDiscoverySortEnabled(widgetId, state)) {
            return;
        }

        config = getEffectiveDiscoverySortConfig(widgetId, state);
        isOpen = !!(state && state.ui && state.ui.sortMenuOpen);
        $sort = $('.bw-fpw-sort[data-widget-id="' + widgetId + '"]');

        if (!$sort.length) {
            return;
        }

        $trigger = $sort.find('.bw-fpw-sort-trigger');
        $menu = $sort.find('.bw-fpw-sort-menu');

        $sort.toggleClass('is-open', isOpen);
        $sort.attr('data-sort-key', config.sortKey);
        $sort.attr('data-trigger-style', getDiscoverySortTriggerStyle(widgetId, state));
        $trigger.attr('aria-expanded', isOpen ? 'true' : 'false');
        $menu.attr('aria-hidden', isOpen ? 'false' : 'true');
        $sort.find('[data-sort-current-label]').text(config.triggerLabel);

        $sort.find('.bw-fpw-sort-option').each(function () {
            var $option = $(this);
            var isSelected = normalizeDiscoverySortKey($option.attr('data-sort-key')) === config.sortKey;

            $option.toggleClass('is-selected', isSelected);
            $option.attr('aria-checked', isSelected ? 'true' : 'false');
        });
    }

    function renderDiscoverySearch(widgetId) {
        var state = filterState[widgetId];
        var searchEnabled = isWidgetSearchEnabled(widgetId, state);
        var value = state ? state.search || '' : '';
        var hasValue = $.trim(value) !== '';

        if (!searchEnabled) {
            $('.bw-fpw-discovery-search__input[data-widget-id="' + widgetId + '"]').val('');
            $('.bw-fpw-discovery-search[data-widget-id="' + widgetId + '"]').removeClass('has-value');
            return;
        }

        $('.bw-fpw-discovery-search__input[data-widget-id="' + widgetId + '"]').val(value);
        $('.bw-fpw-discovery-search[data-widget-id="' + widgetId + '"]').toggleClass('has-value', hasValue);
    }

    function getDiscoveryDrawerBody(widgetId) {
        var $panel = getMobileFilterPanel(widgetId);

        if (!$panel.length) {
            return null;
        }

        return $panel[0].querySelector('.bw-fpw-mobile-filter-panel__body--drawer');
    }

    function syncDiscoveryDrawerStickyState(widgetId) {
        var body = getDiscoveryDrawerBody(widgetId);
        var chips;

        if (!body) {
            return;
        }

        chips = body.querySelector('.bw-fpw-active-chips--drawer[data-widget-id="' + widgetId + '"]');

        if (!chips) {
            return;
        }

        chips.classList.toggle('is-stuck', body.scrollTop > 2);
    }

    function ensureDiscoveryDrawerBodyListener(widgetId) {
        var body = getDiscoveryDrawerBody(widgetId);

        if (!body || body.getAttribute('data-sticky-chip-listener-bound') === 'yes') {
            return;
        }

        body.addEventListener('scroll', function () {
            syncDiscoveryDrawerStickyState(widgetId);
        }, { passive: true });
        body.setAttribute('data-sticky-chip-listener-bound', 'yes');
    }

    function preserveDiscoveryDrawerScrollPosition(widgetId, renderCallback) {
        var body = getDiscoveryDrawerBody(widgetId);
        var previousScrollTop;

        if (!body) {
            renderCallback();
            return;
        }

        previousScrollTop = body.scrollTop;
        renderCallback();

        body.scrollTop = previousScrollTop;

        window.requestAnimationFrame(function () {
            body.scrollTop = previousScrollTop;
            syncDiscoveryDrawerStickyState(widgetId);
        });
    }

    function renderDiscoveryUi(widgetId) {
        var state = filterState[widgetId];

        if (!isDiscoveryDrawerMode(widgetId)) {
            return;
        }

        ensureDiscoveryDrawerBodyListener(widgetId);

        preserveDiscoveryDrawerScrollPosition(widgetId, function () {
            if (isDiscoverySortEnabled(widgetId, state)) {
                renderDiscoverySortControl(widgetId);
            }
            renderDiscoveryVisibleFilters(widgetId);
            if (isWidgetSearchEnabled(widgetId, state)) {
                renderDiscoverySearch(widgetId);
            }
            renderDiscoveryResultCount(widgetId);
            renderDiscoveryActiveChips(widgetId);
            renderDiscoveryDrawerGroups(widgetId);
        });
    }

    function getWidgetSearchBindingNamespace(widgetId) {
        return '.bwProductGridSearch' + String(widgetId || '');
    }

    function bindSearchFeatureHandlersForWidget(widgetId) {
        var state;
        var namespace;
        var $discoveryInput;
        var $legacyInput;

        if (!widgetId) {
            return;
        }

        initFilterState(widgetId);
        state = getDiscoveryState(widgetId);
        namespace = getWidgetSearchBindingNamespace(widgetId);
        $discoveryInput = $('.bw-fpw-discovery-search__input[data-widget-id="' + widgetId + '"]');
        $legacyInput = $('.bw-fpw-search-input[data-widget-id="' + widgetId + '"]');

        $discoveryInput.off(namespace);
        $legacyInput.off(namespace);

        if (!isWidgetSearchEnabled(widgetId, state)) {
            return;
        }

        $discoveryInput.on('input' + namespace, function () {
            var normalizedSearch;

            if (!isDiscoveryDrawerMode(widgetId) || !isWidgetSearchEnabled(widgetId, state)) {
                return;
            }

            state.search = $(this).val() || '';
            normalizedSearch = $.trim(state.search);

            renderDiscoverySearch(widgetId);

            if (discoverySearchTimers[widgetId]) {
                clearTimeout(discoverySearchTimers[widgetId]);
            }

            discoverySearchTimers[widgetId] = setTimeout(function () {
                delete discoverySearchTimers[widgetId];

                if (normalizedSearch === $.trim(state.appliedSearch || '')) {
                    return;
                }

                filterPosts(widgetId);
            }, 650);
        });

        $legacyInput.on('input' + namespace, function () {
            var val;

            if (isDiscoveryDrawerMode(widgetId) || !isWidgetSearchEnabled(widgetId, filterState[widgetId])) {
                return;
            }

            val = $.trim($(this).val());
            filterState[widgetId].search = val;

            if (val.length === 1) {
                if (searchDebounceTimers[widgetId]) {
                    clearTimeout(searchDebounceTimers[widgetId]);
                    delete searchDebounceTimers[widgetId];
                }
                return;
            }

            if (searchDebounceTimers[widgetId]) {
                clearTimeout(searchDebounceTimers[widgetId]);
            }
            searchDebounceTimers[widgetId] = setTimeout(function () {
                delete searchDebounceTimers[widgetId];
                filterPosts(widgetId);
            }, 250);
        });
    }

    function unbindSearchFeatureHandlersForWidget(widgetId) {
        var namespace;

        if (!widgetId) {
            return;
        }

        namespace = getWidgetSearchBindingNamespace(widgetId);
        $('.bw-fpw-discovery-search__input[data-widget-id="' + widgetId + '"]').off(namespace);
        $('.bw-fpw-search-input[data-widget-id="' + widgetId + '"]').off(namespace);
    }

    function syncSearchFeatureBindings() {
        var activeWidgetIds = {};

        $('.bw-fpw-grid').each(function () {
            var widgetId = $(this).attr('data-widget-id');

            if (!widgetId) {
                return;
            }

            activeWidgetIds[widgetId] = true;
            bindSearchFeatureHandlersForWidget(widgetId);
        });

        Object.keys(filterState).forEach(function (widgetId) {
            if (!activeWidgetIds[widgetId]) {
                unbindSearchFeatureHandlersForWidget(widgetId);
            }
        });
    }

    function syncDiscoveryResponse(widgetId, data, options) {
        var state = filterState[widgetId];
        var previousResultCount;
        var nextSignature;
        var shouldRender = false;

        options = options || {};

        if (!state || !isDiscoveryDrawerMode(widgetId) || !data) {
            return;
        }

        if (data.filter_ui) {
            previousResultCount = state.resultCount;
            nextSignature = getDiscoveryFilterUiSignature(data.filter_ui);
            updateDiscoveryOptions(widgetId, data.filter_ui);
            shouldRender = nextSignature !== (state.ui.lastFilterUiSignature || '') || state.resultCount !== previousResultCount;
            state.ui.lastFilterUiSignature = nextSignature;
        } else if (typeof data.result_count !== 'undefined') {
            previousResultCount = state.resultCount;
            state.resultCount = Math.max(0, parseInteger(data.result_count, state.resultCount));
            shouldRender = state.resultCount !== previousResultCount;
        }

        state.appliedSearch = isWidgetSearchEnabled(widgetId, state) ? state.search : '';

        if (!options.skipRender && shouldRender) {
            renderDiscoveryUi(widgetId);
        }
    }

    function toggleDiscoverySelection(widgetId, groupKey, termId) {
        var state = getDiscoveryState(widgetId);
        var stateKey = getDiscoverySelectionStateKey(groupKey);
        var selections = getDiscoverySelections(state, groupKey).slice();
        var normalizedValue = isDiscoveryTokenGroup(groupKey)
            ? normalizeDiscoveryTokenValue(termId)
            : parseInteger(termId, 0);
        var index = selections.indexOf(normalizedValue);

        if (!stateKey || (isDiscoveryTokenGroup(groupKey) ? !normalizedValue : normalizedValue <= 0)) {
            return;
        }

        state.ui.openGroups[groupKey] = true;

        if (index > -1) {
            selections.splice(index, 1);
        } else {
            selections.push(normalizedValue);
        }

        if (isDiscoveryTokenGroup(groupKey)) {
            state[stateKey] = uniqueStringArray(selections);
        } else {
            state[stateKey] = uniqueIntArray(selections);
        }
    }

    function resetDiscoveryFilters(widgetId, closePanel) {
        var state = getDiscoveryState(widgetId);
        var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
        var defaultCategory = $filters.attr('data-default-category') || 'all';

        state.category = defaultCategory;
        state.subcategories = [];
        state.tags = [];
        state.artists = [];
        state.authors = [];
        state.publishers = [];
        state.sources = [];
        state.techniques = [];
        state.search = '';
        state.appliedSearch = '';
        state.year = createEmptyYearState();
        state.ui.optionSearches.types = '';
        state.ui.optionSearches.tags = '';
        state.ui.optionSearches.artist = '';
        state.ui.optionSearches.author = '';
        state.ui.optionSearches.publisher = '';
        state.ui.optionSearches.source = '';
        state.ui.optionSearches.technique = '';
        state.ui.openGroups.types = false;
        state.ui.openGroups.tags = false;
        state.ui.openGroups.years = false;
        state.ui.openGroups.artist = false;
        state.ui.openGroups.author = false;
        state.ui.openGroups.publisher = false;
        state.ui.openGroups.source = false;
        state.ui.openGroups.technique = false;
        state.ui.sortMenuOpen = false;
        state.ui.visibleFilterOpenGroup = '';
        state.ui.yearDraft = createEmptyYearState();

        if (yearInputCommitTimers[widgetId]) {
            clearTimeout(yearInputCommitTimers[widgetId]);
            delete yearInputCommitTimers[widgetId];
        }

        renderDiscoveryUi(widgetId);
        filterPosts(widgetId);

        if (closePanel) {
            closeMobilePanel(widgetId);
        }
    }

    function commitYearRange(widgetId, from, to) {
        var state = getDiscoveryState(widgetId);
        var normalized = normalizeYearRange(from, to);
        var prevLabel = getYearRangeLabel(state.year);
        var nextLabel = getYearRangeLabel(normalized);

        state.year = normalized;
        state.ui.yearDraft = {
            from: normalized.from,
            to: normalized.to
        };
        state.ui.openGroups.years = true;

        renderDiscoveryUi(widgetId);

        if (prevLabel === nextLabel) {
            return;
        }

        filterPosts(widgetId);
    }

    function removeActiveDiscoveryFilter(widgetId, groupKey, termId) {
        var state = getDiscoveryState(widgetId);
        var stateKey = getDiscoverySelectionStateKey(groupKey);
        var normalizedValue = isDiscoveryTokenGroup(groupKey)
            ? normalizeDiscoveryTokenValue(termId)
            : parseInteger(termId, 0);

        if (groupKey === 'types') {
            state.subcategories = state.subcategories.filter(function (id) {
                return id !== normalizedValue;
            });
        } else if (groupKey === 'tags') {
            state.tags = state.tags.filter(function (id) {
                return id !== normalizedValue;
            });
        } else if (stateKey && isDiscoveryTokenGroup(groupKey)) {
            state[stateKey] = state[stateKey].filter(function (value) {
                return value !== normalizedValue;
            });
        } else if (groupKey === 'years') {
            state.year = createEmptyYearState();
            state.ui.yearDraft = createEmptyYearState();
        } else {
            return;
        }

        renderDiscoveryUi(widgetId);
        filterPosts(widgetId);
    }

    // ── Infinite-scroll load spacer ───────────────────────────────────────────
    // While a batch of posts is loading the footer must not be reachable.
    // We insert a full-viewport-height div after .bw-fpw-load-state so the user
    // stays in the loading area.  The spacer is removed once posts are appended
    // (they take its place) or on error/abort.

    function addLoadSpacer(widgetId) {
        removeLoadSpacer(widgetId); // clear any leftover first
        var $loadState = $('.bw-fpw-load-state[data-widget-id="' + widgetId + '"]');
        if (!$loadState.length) {
            return;
        }
        var $spacer = $('<div>')
            .addClass('bw-fpw-load-spacer')
            .attr('data-widget-id', widgetId)
            .attr('aria-hidden', 'true')
            .css('height', (window.innerHeight || 600) + 'px');
        $loadState.after($spacer);
        infiniteLoadSpacers[widgetId] = $spacer[0];
    }

    function removeLoadSpacer(widgetId) {
        if (infiniteLoadSpacers[widgetId]) {
            $(infiniteLoadSpacers[widgetId]).remove();
            delete infiniteLoadSpacers[widgetId];
        }
        // Safety net: remove any stale spacer left in the DOM
        $('.bw-fpw-load-spacer[data-widget-id="' + widgetId + '"]').remove();
    }
    // ─────────────────────────────────────────────────────────────────────────

    function loadSubcategories(categoryId, widgetId, autoOpenMobile) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');
        var postType = $grid.attr('data-post-type') || 'product';
        var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
        var $subcatRow = $('.bw-fpw-filter-subcategories[data-widget-id="' + widgetId + '"]');
        var $subcatContainers = $('.bw-fpw-subcategories-container[data-widget-id="' + widgetId + '"]');
        var hasPostsAttr = $filters.attr('data-has-posts');
        var hasPosts = typeof hasPostsAttr === 'undefined' ? true : hasPostsAttr === '1';
        var isMobile = isInMobileMode(widgetId);
        var queueKey = widgetId + '_subcats';

        // Abort any in-flight subcategory request for this widget so a rapid
        // category change never lets a stale response overwrite the current one.
        if (ajaxRequestQueue[queueKey]) {
            ajaxRequestQueue[queueKey].abort();
            delete ajaxRequestQueue[queueKey];
        }

        // Fade out before clearing.  Cancel any pending clear timer first so a
        // previous 150 ms delay cannot empty the container we are about to fill.
        if ($subcatContainers.length) {
            $subcatContainers.removeClass('bw-fpw-animating').css('opacity', '0');
            if (filterAnimTimers[queueKey]) {
                clearTimeout(filterAnimTimers[queueKey]);
            }
            filterAnimTimers[queueKey] = setTimeout(function () {
                delete filterAnimTimers[queueKey];
                $subcatContainers.empty();
            }, 150);
        }

        // Check cache first
        var cacheKey = getCacheKey('subcategories', {
            category_id: categoryId,
            post_type: postType,
            widget_id: widgetId
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            processSubcategoriesResponse(cachedResponse, widgetId, $subcatContainers, $subcatRow, hasPosts, isMobile, autoOpenMobile);
            return;
        }

        $subcatContainers.addClass('bw-fpw-options-loading');

        ajaxRequestQueue[queueKey] = $.ajax({
            url: bwProductGridAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_fpw_get_subcategories',
                category_id: categoryId,
                post_type: postType,
                nonce: bwProductGridAjax.nonce
            },
            success: function (response) {
                delete ajaxRequestQueue[queueKey];
                $subcatContainers.removeClass('bw-fpw-options-loading');
                setCachedData(cacheKey, response);
                processSubcategoriesResponse(response, widgetId, $subcatContainers, $subcatRow, hasPosts, isMobile, autoOpenMobile);
            },
            error: function (xhr, status) {
                delete ajaxRequestQueue[queueKey];
                $subcatContainers.removeClass('bw-fpw-options-loading');
                if (status === 'abort') {
                    return;
                }
                $subcatContainers.html('<p class="bw-fpw-error">Error loading subcategories</p>');
                if ($subcatRow.length) {
                    if (hasPosts) {
                        $subcatRow.show();
                    } else {
                        $subcatRow.hide();
                    }
                }
            }
        });
    }

    function processSubcategoriesResponse(response, widgetId, $subcatContainers, $subcatRow, hasPosts, isMobile, autoOpenMobile) {
        if (response.success && response.data) {
            var subcats = response.data;
            var html = '';

            $.each(subcats, function (index, subcat) {
                html += '<button class="bw-fpw-filter-option bw-fpw-subcat-button" data-subcategory="' + subcat.term_id + '">';
                html += '<span class="bw-fpw-option-label">' + subcat.name + '</span> ';
                html += '<span class="bw-fpw-option-count">(' + subcat.count + ')</span>';
                html += '</button>';
            });

            $subcatContainers.each(function () {
                var $container = $(this);
                $container.html(html);
                // Trigger fade + slide animation
                setTimeout(function () {
                    $container.addClass('bw-fpw-animating').css('opacity', '1');
                }, 50);
            });
            if ($subcatRow.length) {
                var hasButtons = $subcatContainers.find('.bw-fpw-subcat-button').length > 0;
                if (hasPosts && hasButtons) {
                    $subcatRow.css('opacity', '0').show();
                    setTimeout(function () {
                        $subcatRow.css('opacity', '1');
                    }, 50);
                } else {
                    $subcatRow.hide();
                }
            }

            // Auto-open subcategories dropdown in mobile mode
            if (isMobile && autoOpenMobile && subcats.length > 0) {
                var $mobileSubcatGroup = $('.bw-fpw-mobile-filter-group--subcategories[data-widget-id="' + widgetId + '"]');
                if ($mobileSubcatGroup.length && !$mobileSubcatGroup.hasClass('is-open')) {
                    setTimeout(function () {
                        $mobileSubcatGroup.addClass('is-open');
                        $mobileSubcatGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'false');
                    }, 200);
                }
            }
        } else {
            $subcatContainers.html('<p class="bw-fpw-no-subcats">No subcategories found</p>');
            if ($subcatRow.length) {
                $subcatRow.hide();
            }
        }
    }

    function loadTags(categoryId, widgetId, subcategories, autoOpenMobile) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');
        var postType = $grid.attr('data-post-type') || 'product';
        var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
        var $tagRow = $('.bw-fpw-filter-row--tags[data-widget-id="' + widgetId + '"]');
        var $tagContainers = $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"]');
        var hasPostsAttr = $filters.attr('data-has-posts');
        var hasPosts = typeof hasPostsAttr === 'undefined' ? true : hasPostsAttr === '1';
        var isMobile = isInMobileMode(widgetId);
        var queueKey = widgetId + '_tags';

        // Abort any in-flight tag request for this widget.
        if (ajaxRequestQueue[queueKey]) {
            ajaxRequestQueue[queueKey].abort();
            delete ajaxRequestQueue[queueKey];
        }

        // Fade out before clearing.  Cancel any pending clear timer first.
        if ($tagContainers.length) {
            $tagContainers.removeClass('bw-fpw-animating').css('opacity', '0');
            if (filterAnimTimers[queueKey]) {
                clearTimeout(filterAnimTimers[queueKey]);
            }
            filterAnimTimers[queueKey] = setTimeout(function () {
                delete filterAnimTimers[queueKey];
                $tagContainers.empty();
            }, 150);
        }

        // Read active year range from filterState so tags are scoped to the current year filter.
        var tagState = filterState[widgetId];
        var tagYearFrom = tagState && tagState.year && tagState.year.from !== null ? tagState.year.from : null;
        var tagYearTo   = tagState && tagState.year && tagState.year.to   !== null ? tagState.year.to   : null;

        // Check cache first
        var cacheKey = getCacheKey('tags', {
            category_id: categoryId,
            post_type: postType,
            subcategories: subcategories || [],
            widget_id: widgetId,
            year_from: tagYearFrom,
            year_to: tagYearTo
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            processTagsResponse(cachedResponse, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile);
            return;
        }

        $tagContainers.addClass('bw-fpw-options-loading');

        ajaxRequestQueue[queueKey] = $.ajax({
            url: bwProductGridAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_fpw_get_tags',
                category_id: categoryId,
                post_type: postType,
                subcategories: subcategories || [],
                year_from: tagYearFrom !== null ? tagYearFrom : '',
                year_to: tagYearTo   !== null ? tagYearTo   : '',
                nonce: bwProductGridAjax.nonce
            },
            success: function (response) {
                delete ajaxRequestQueue[queueKey];
                $tagContainers.removeClass('bw-fpw-options-loading');
                setCachedData(cacheKey, response);
                processTagsResponse(response, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile);
            },
            error: function (xhr, status) {
                delete ajaxRequestQueue[queueKey];
                $tagContainers.removeClass('bw-fpw-options-loading');
                if (status === 'abort') {
                    return;
                }
                $tagContainers.html('<p class="bw-fpw-error">Error loading tags</p>');
                if ($tagRow.length) {
                    if (hasPosts) {
                        $tagRow.show();
                    } else {
                        $tagRow.hide();
                    }
                }
            }
        });
    }

    function processTagsResponse(response, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile) {
        if (response.success && response.data && response.data.length > 0) {
            var tags = response.data;
            var html = '';

            $.each(tags, function (index, tag) {
                html += '<button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="' + tag.term_id + '">';
                html += '<span class="bw-fpw-option-label">' + tag.name + '</span> ';
                html += '<span class="bw-fpw-option-count">(' + tag.count + ')</span>';
                html += '</button>';
            });

            $tagContainers.each(function () {
                var $container = $(this);
                $container.html(html);
                // Trigger fade + slide animation
                setTimeout(function () {
                    $container.addClass('bw-fpw-animating').css('opacity', '1');
                }, 50);
            });

            if ($tagRow.length) {
                var hasButtons = $tagContainers.find('.bw-fpw-tag-button').length > 0;
                if (hasPosts && hasButtons) {
                    $tagRow.css('opacity', '0').show();
                    setTimeout(function () {
                        $tagRow.css('opacity', '1');
                    }, 50);
                } else {
                    $tagRow.hide();
                }
            }

            // Auto-open tags dropdown in mobile mode
            if (isMobile && autoOpenMobile && tags.length > 0) {
                var $mobileTagGroup = $('.bw-fpw-mobile-filter-group--tags[data-widget-id="' + widgetId + '"]');
                if ($mobileTagGroup.length && !$mobileTagGroup.hasClass('is-open')) {
                    setTimeout(function () {
                        $mobileTagGroup.addClass('is-open');
                        $mobileTagGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'false');
                    }, 400);
                }
            }
        } else {
            // No tags found - hide the tag row and close dropdown
            $tagContainers.html('');
            if ($tagRow.length) {
                $tagRow.hide();
            }

            // Close tags dropdown in mobile if no tags available
            if (isMobile) {
                var $mobileTagGroup = $('.bw-fpw-mobile-filter-group--tags[data-widget-id="' + widgetId + '"]');
                if ($mobileTagGroup.length) {
                    $mobileTagGroup.removeClass('is-open');
                    $mobileTagGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'true');
                }
            }
        }
    }

    function createResponseNodes(html) {
        var trimmedHtml = $.trim(html || '');

        if (!trimmedHtml) {
            return $();
        }

        return $($.parseHTML(trimmedHtml, document, true));
    }

    function getResponseItems($nodes) {
        if (!$nodes || !$nodes.length) {
            return $();
        }

        return $nodes.filter('.bw-fpw-item').add($nodes.find('.bw-fpw-item'));
    }

    function clearStaggerTimers(widgetId) {
        if (!widgetId) {
            return;
        }

        if (staggerTimersByWidget[widgetId]) {
            staggerTimersByWidget[widgetId].forEach(function (timerId) {
                clearTimeout(timerId);
            });
            staggerTimersByWidget[widgetId] = [];
        }

        if (staggerObserversByWidget[widgetId]) {
            staggerObserversByWidget[widgetId].forEach(function (obs) {
                obs.disconnect();
            });
            staggerObserversByWidget[widgetId] = [];
        }
    }

    // Full cleanup for one widget instance.  Call when a widget is destroyed
    // (editor deletion) or replaced by a fresh render (Elementor re-render).
    function destroyWidgetState(widgetId) {
        if (!widgetId) {
            return;
        }

        unbindSearchFeatureHandlersForWidget(widgetId);
        getDetachedDrawerHost(widgetId).remove();
        unlockDrawerBodyScrollIfSafe();

        // Reveal animation timers and observers
        clearStaggerTimers(widgetId);

        // Infinite-scroll sentinel observer
        disconnectInfiniteObserver(widgetId);

        // Filter animation timers (fade-out clear on subcats / tags)
        var subcatKey = widgetId + '_subcats';
        var tagKey    = widgetId + '_tags';
        if (filterAnimTimers[subcatKey]) {
            clearTimeout(filterAnimTimers[subcatKey]);
            delete filterAnimTimers[subcatKey];
        }
        if (filterAnimTimers[tagKey]) {
            clearTimeout(filterAnimTimers[tagKey]);
            delete filterAnimTimers[tagKey];
        }

        // In-flight AJAX requests
        [widgetId, subcatKey, tagKey].forEach(function (key) {
            if (ajaxRequestQueue[key]) {
                ajaxRequestQueue[key].abort();
                delete ajaxRequestQueue[key];
            }
        });

        // Loading-indicator delay timer
        if (loadingIndicatorTimers[widgetId]) {
            clearTimeout(loadingIndicatorTimers[widgetId]);
            delete loadingIndicatorTimers[widgetId];
        }

        if (discoverySearchTimers[widgetId]) {
            clearTimeout(discoverySearchTimers[widgetId]);
            delete discoverySearchTimers[widgetId];
        }

        if (yearInputCommitTimers[widgetId]) {
            clearTimeout(yearInputCommitTimers[widgetId]);
            delete yearInputCommitTimers[widgetId];
        }

        // Scroll reveal listener (namespaced per widget)
        $(window).off('scroll.bwreveal' + widgetId);

        // Infinite-scroll load spacer
        removeLoadSpacer(widgetId);

        // Search debounce timer
        if (searchDebounceTimers[widgetId]) {
            clearTimeout(searchDebounceTimers[widgetId]);
            delete searchDebounceTimers[widgetId];
        }

        // Per-widget state objects
        delete filterState[widgetId];
        delete widgetPagingState[widgetId];
        delete staggerTimersByWidget[widgetId];
        delete staggerObserversByWidget[widgetId];
        delete lastDeviceByGrid[widgetId];
        delete infiniteLoadSpacers[widgetId];
        unlockDrawerBodyScrollIfSafe();
        syncSearchFeatureBindings();
    }

    function prepareItemsForReveal($items, mode) {
        if (!$items || !$items.length) {
            return;
        }

        var revealMode = mode === 'initial' ? 'initial' : 'append';

        $items
            .removeClass('bw-fpw-item--reveal-initial bw-fpw-item--reveal-append bw-fpw-item--visible')
            .addClass('bw-fpw-item--reveal bw-fpw-item--reveal-' + revealMode);
    }

    function cleanupRevealClasses($item) {
        if (!$item || !$item.length) {
            return;
        }

        $item.removeClass('bw-fpw-item--reveal bw-fpw-item--reveal-initial bw-fpw-item--reveal-append bw-fpw-item--visible');
    }

    function animatePostsStaggered($items, mode, widgetId) {
        if (!$items || !$items.length) {
            return;
        }

        var $revealItems = $items.filter('.bw-fpw-item--reveal');
        var revealMode = mode === 'initial' ? 'initial' : 'append';
        var baseDelay = 20;
        var cleanupDelay = 300;

        if (!$revealItems.length) {
            return;
        }

        if (widgetId) {
            if (revealMode !== 'append') {
                clearStaggerTimers(widgetId);
            }
            if (!staggerTimersByWidget[widgetId]) {
                staggerTimersByWidget[widgetId] = [];
            }
        }

        $revealItems.each(function (index) {
            var $item = $(this);
            var delay = index * baseDelay;

            var revealTimer = setTimeout(function () {
                $item.addClass('bw-fpw-item--visible');

                var cleanupTimer = setTimeout(function () {
                    $item.removeClass('bw-fpw-item--reveal bw-fpw-item--reveal-initial bw-fpw-item--reveal-append bw-fpw-item--visible');
                }, cleanupDelay);

                if (widgetId && staggerTimersByWidget[widgetId]) {
                    staggerTimersByWidget[widgetId].push(cleanupTimer);
                }
            }, delay);

            if (widgetId && staggerTimersByWidget[widgetId]) {
                staggerTimersByWidget[widgetId].push(revealTimer);
            }
        });
    }

    // Per-batch reveal for appended cards.
    //
    // Two mechanisms work together so cards always fade in regardless of
    // scroll speed:
    //
    //   1. IntersectionObserver — handles normal scrolling.  All entries that
    //      arrive in the same IO callback are treated as one batch.
    //
    //   2. Scroll + rAF sweep — on every animation frame where a scroll
    //      occurred, any still-pending card whose bounding rect is inside the
    //      viewport is swept up.  This catches items the browser skipped
    //      during fast / inertia scrolling (items that entered and left the
    //      viewport between two IO frames and were never reported as
    //      isIntersecting: true).
    //
    // Both paths call revealBatch(), which sorts items by position
    // (top → left) and staggers them sequentially so the order is always
    // natural and never random.
    function revealItemsPerViewport($items, widgetId) {
        if (!$items || !$items.length) {
            return;
        }

        if (typeof window.IntersectionObserver === 'undefined') {
            animatePostsStaggered($items, 'append', widgetId);
            return;
        }

        var STAGGER = 20;        // ms between items within a batch
        var cleanupDelay = 300;  // must be >= CSS transition duration (0.25s)
        var FALLBACK_MS = 15000; // safety net for genuinely stuck items only

        if (widgetId) {
            if (!staggerTimersByWidget[widgetId]) {
                staggerTimersByWidget[widgetId] = [];
            }
            if (!staggerObserversByWidget[widgetId]) {
                staggerObserversByWidget[widgetId] = [];
            }
        }

        // Pending items tracked by a temp attribute for O(1) lookup.
        var ATTR = 'data-bw-rid';
        var uidCounter = 0;
        var pendingMap = {};   // uid → DOM element
        var pendingCount = 0;

        $items.filter('.bw-fpw-item--reveal').each(function () {
            var uid = ++uidCounter;
            this.setAttribute(ATTR, uid);
            pendingMap[uid] = this;
            pendingCount++;
        });

        if (!pendingCount) {
            return;
        }

        function scheduleItemReveal($item, delay) {
            var t = setTimeout(function () {
                $item.addClass('bw-fpw-item--visible');
                var ct = setTimeout(function () {
                    $item.removeAttr(ATTR);
                    $item.removeClass(
                        'bw-fpw-item--reveal bw-fpw-item--reveal-initial ' +
                        'bw-fpw-item--reveal-append bw-fpw-item--visible'
                    );
                }, cleanupDelay);
                if (widgetId && staggerTimersByWidget[widgetId]) {
                    staggerTimersByWidget[widgetId].push(ct);
                }
            }, delay);
            if (widgetId && staggerTimersByWidget[widgetId]) {
                staggerTimersByWidget[widgetId].push(t);
            }
        }

        // Sort elements by visual position: top row first, left-to-right within
        // the same row.  Guarantees a natural reading-order stagger every time.
        function sortByPosition(elements) {
            return elements.slice().sort(function (a, b) {
                var ra = a.getBoundingClientRect();
                var rb = b.getBoundingClientRect();
                var dy = ra.top - rb.top;
                if (Math.abs(dy) > 20) {
                    return dy;
                }
                return ra.left - rb.left;
            });
        }

        function revealBatch(elements) {
            // Filter to only still-pending items (avoid double-reveal).
            var toReveal = elements.filter(function (el) {
                var uid = el.getAttribute(ATTR);
                return uid && pendingMap[uid];
            });
            if (!toReveal.length) {
                return;
            }
            sortByPosition(toReveal).forEach(function (el, i) {
                var uid = el.getAttribute(ATTR);
                delete pendingMap[uid];
                pendingCount--;
                scheduleItemReveal($(el), i * STAGGER);
            });
            if (!pendingCount) {
                teardown();
            }
        }

        var io = null;
        var scrollNs = 'scroll.bwreveal' + (widgetId || uidCounter);

        function teardown() {
            if (io) {
                io.disconnect();
                io = null;
            }
            $(window).off(scrollNs);
        }

        function getPendingInViewport() {
            var vph = window.innerHeight || document.documentElement.clientHeight;
            var result = [];
            Object.keys(pendingMap).forEach(function (uid) {
                var el = pendingMap[uid];
                if (!el) {
                    return;
                }
                var r = el.getBoundingClientRect();
                if (r.bottom > 0 && r.top < vph) {
                    result.push(el);
                }
            });
            return result;
        }

        // 1. IntersectionObserver — normal scrolling
        io = new window.IntersectionObserver(function (entries) {
            var entering = [];
            entries.forEach(function (e) {
                if (!e.isIntersecting) {
                    return;
                }
                var uid = e.target.getAttribute(ATTR);
                if (uid && pendingMap[uid]) {
                    entering.push(e.target);
                }
            });
            if (entering.length) {
                revealBatch(entering);
            }
        }, { threshold: 0 });

        Object.keys(pendingMap).forEach(function (uid) {
            io.observe(pendingMap[uid]);
        });

        if (widgetId && staggerObserversByWidget[widgetId]) {
            staggerObserversByWidget[widgetId].push(io);
        }

        // 2. Scroll + rAF sweep — fast / inertia scrolling safety net
        var ticking = false;
        $(window).on(scrollNs, function () {
            if (ticking || !pendingCount) {
                return;
            }
            ticking = true;
            requestAnimationFrame(function () {
                ticking = false;
                var inView = getPendingInViewport();
                if (inView.length) {
                    revealBatch(inView);
                }
            });
        });

        // Initial sweep — reveals items already in the viewport at append time.
        var initialVisible = getPendingInViewport();
        if (initialVisible.length) {
            revealBatch(initialVisible);
        }

        // Per-item safety fallback — only for genuinely stuck items.
        Object.keys(pendingMap).forEach(function (uid) {
            var el = pendingMap[uid];
            if (!el) {
                return;
            }
            var ft = setTimeout(function () {
                if (pendingMap[uid]) {
                    revealBatch([el]);
                }
            }, FALLBACK_MS);
            if (widgetId && staggerTimersByWidget[widgetId]) {
                staggerTimersByWidget[widgetId].push(ft);
            }
        });
    }

    function finalizeGridUpdate($grid, $items, appendMode, callback, revealMode) {
        var widgetId = $grid.attr('data-widget-id');

        var doAnimate = function () {
            if (appendMode) {
                revealItemsPerViewport($items, widgetId);
            } else {
                animatePostsStaggered($items, revealMode, widgetId);
            }
            if (typeof callback === 'function') {
                callback();
            }
        };

        var runFinalize = function () {
            if (appendMode) {
                layoutGrid($grid, false, doAnimate, 600);
            } else {
                initGrid($grid, doAnimate);
            }
        };

        if (useCssGrid($grid)) {
            runFinalize();
            return;
        }

        var $imageScope = appendMode && $items && $items.length ? $items : $grid;
        withImagesLoaded(getPrimaryImageScope($imageScope), runFinalize, appendMode ? 200 : 200);
    }

    function runInitialReveal($grid) {
        if (
            !$grid ||
            !$grid.length ||
            $grid.attr('data-initial-reveal-done') === 'yes' ||
            isElementorEditor()
        ) {
            return;
        }

        var widgetId = $grid.attr('data-widget-id');
        var $items = $grid.children('.bw-fpw-item').filter('.bw-fpw-item--reveal');

        if (!$items.length) {
            $grid.attr('data-initial-reveal-done', 'yes');
            return;
        }

        animatePostsStaggered($items, 'initial', widgetId);
        $grid.attr('data-initial-reveal-done', 'yes');
    }

    function loadNextPage(widgetId) {
        var state = getWidgetPagingState(widgetId);

        if (!state || !state.infiniteEnabled || !state.hasMore || state.isLoading) {
            return;
        }

        var nextPage = state.nextPage > 0 ? state.nextPage : state.currentPage + 1;
        filterPosts(widgetId, {
            append: true,
            page: nextPage,
            offset: state.nextOffset > 0 ? state.nextOffset : state.loadedCount
        });
    }

    function filterPosts(widgetId, options) {
        options = options || {};

        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');
        var $wrapper = $grid.closest('.bw-product-grid');
        var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
        var pagingState = getWidgetPagingState(widgetId);
        var appendMode = !!options.append;

        if (!$grid.length) {
            return;
        }

        if (!pagingState) {
            return;
        }

        var requestedPage = appendMode
            ? Math.max(1, parseInteger(options.page, pagingState.nextPage > 0 ? pagingState.nextPage : pagingState.currentPage + 1))
            : 1;

        if (appendMode && (!pagingState.infiniteEnabled || !pagingState.hasMore || pagingState.isLoading)) {
            return;
        }

        if (!appendMode) {
            clearStaggerTimers(widgetId);
        }

        var state = filterState[widgetId];
        var postType = $grid.attr('data-post-type') || 'product';
        var imageToggle = 'yes'; // always true — no per-widget control yet
        var imageSize = $grid.attr('data-image-size') || 'large';
        var imageMode = $grid.attr('data-image-mode') || 'proportional';
        var hoverEffect = $grid.attr('data-hover-effect') || 'yes';
        var openCartPopup = $grid.attr('data-open-cart-popup') || 'no';
        var sortConfig = getEffectiveDiscoverySortConfig(widgetId, state);
        var orderBy = sortConfig.orderBy;
        var order = sortConfig.order;
        var sortKey = sortConfig.sortKey;
        var contextSlug = $grid.attr('data-context-slug') || '';
        var searchEnabled = isWidgetSearchEnabled(widgetId, state);
        var requestPerPage = appendMode ? pagingState.loadBatchSize : pagingState.initialItems;
        var requestedOffset = appendMode ? Math.max(0, parseInteger(options.offset, pagingState.nextOffset > 0 ? pagingState.nextOffset : pagingState.loadedCount)) : 0;
        var searchTerm = searchEnabled ? (state.search || '') : '';
        var yearFrom = state.year && state.year.from !== null ? state.year.from : null;
        var yearTo = state.year && state.year.to !== null ? state.year.to : null;
        var artistValues = uniqueStringArray(state.artists || []);
        var authorValues = uniqueStringArray(state.authors || []);
        var publisherValues = uniqueStringArray(state.publishers || []);
        var sourceValues = uniqueStringArray(state.sources || []);
        var techniqueValues = uniqueStringArray(state.techniques || []);

        if (!appendMode && requestPerPage === 0) {
            requestPerPage = pagingState.perPage;
        }

        if (appendMode && requestPerPage < 1) {
            return;
        }

        // Cancel pending request for this widget if exists
        if (ajaxRequestQueue[widgetId]) {
            ajaxRequestQueue[widgetId].abort();
        }

        disconnectInfiniteObserver(widgetId);

        // Check cache first
        var cacheKey = getCacheKey('filter_posts', {
            widget_id: widgetId,
            post_type: postType,
            context_slug: contextSlug,
            category: state.category,
            subcategories: state.subcategories,
            tags: state.tags,
            search_enabled: searchEnabled ? 'yes' : 'no',
            search: searchTerm,
            year_from: yearFrom,
            year_to: yearTo,
            artist: artistValues,
            author: authorValues,
            publisher: publisherValues,
            source: sourceValues,
            technique: techniqueValues,
            sort_key: sortKey,
            order_by: orderBy,
            order: order,
            image_mode: imageMode,
            per_page: requestPerPage,
            page: requestedPage,
            offset: requestedOffset
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            if (appendMode) {
                updateWidgetPagingState(widgetId, {
                    isLoading: true
                });
                addLoadSpacer(widgetId);
            } else {
                $filters.addClass('loading');
            }

            processFilterResponse(cachedResponse, widgetId, $grid, $wrapper, $filters, {
                append: appendMode,
                hadMasonryBefore: !!getMasonryInstance($grid),
                requestedPage: requestedPage,
                perPage: requestPerPage,
                requestedOffset: requestedOffset
            });
            return;
        }

        if (appendMode) {
            updateWidgetPagingState(widgetId, {
                isLoading: true
            });
            addLoadSpacer(widgetId);
        } else {
            removeLoadSpacer(widgetId); // clear any spacer left from a previous append
            $filters.addClass('loading');
            updateWidgetPagingState(widgetId, {
                currentPage: 1,
                nextPage: 0,
                hasMore: false,
                loadedCount: 0,
                nextOffset: 0,
                isLoading: false
            });
        }

        // OPTIMIZATION: Only destroy masonry if necessary
        // Store current instance to check if we need full reinit
        var hadMasonryBefore = getMasonryInstance($grid) ? true : false;

        ajaxRequestQueue[widgetId] = $.ajax({
            url: bwProductGridAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_fpw_filter_posts',
                widget_id: widgetId,
                post_type: postType,
                context_slug: contextSlug,
                category: state.category,
                subcategories: state.subcategories,
                tags: state.tags,
                search_enabled: searchEnabled ? 'yes' : 'no',
                search: searchTerm,
                year_from: yearFrom,
                year_to: yearTo,
                artist: artistValues,
                author: authorValues,
                publisher: publisherValues,
                source: sourceValues,
                technique: techniqueValues,
                sort_key: sortKey,
                image_toggle: imageToggle,
                image_size: imageSize,
                image_mode: imageMode,
                hover_effect: hoverEffect,
                open_cart_popup: openCartPopup,
                order_by: orderBy,
                order: order,
                per_page: requestPerPage,
                page: requestedPage,
                offset: requestedOffset,
                nonce: bwProductGridAjax.nonce
            },
            success: function (response) {
                // Cache the response
                setCachedData(cacheKey, response);
                // Clear request queue
                delete ajaxRequestQueue[widgetId];
                // Process response
                processFilterResponse(response, widgetId, $grid, $wrapper, $filters, {
                    append: appendMode,
                    hadMasonryBefore: hadMasonryBefore,
                    requestedPage: requestedPage,
                    perPage: requestPerPage,
                    requestedOffset: requestedOffset
                });
            },
            error: function (xhr, status, error) {
                // Clear request queue
                delete ajaxRequestQueue[widgetId];

                // Don't show error if request was aborted
                if (status === 'abort') {
                    return;
                }

                // 403 = nonce expired. Refresh nonce and retry once.
                if (xhr.status === 403 && !options._nonceRetry) {
                    refreshNonce().then(function () {
                        filterPosts(widgetId, $.extend({}, options, { _nonceRetry: true }));
                    });
                    return;
                }

                if (appendMode) {
                    // Stop infinite scroll — retrying a failed request (e.g. 403)
                    // would immediately re-trigger the sentinel and loop forever.
                    removeLoadSpacer(widgetId);
                    updateWidgetPagingState(widgetId, {
                        isLoading: false,
                        hasMore: false
                    });
                    updateInfiniteUi(widgetId);
                    return;
                }

                $grid.html('<div class="bw-fpw-placeholder">Error loading posts.</div>');
                $filters.removeClass('loading');
                $filters.attr('data-has-posts', '0');
                $('.bw-fpw-filter-row--subcategories[data-widget-id="' + widgetId + '"], .bw-fpw-filter-row--tags[data-widget-id="' + widgetId + '"]').hide();
            }
        });
    }

    function processFilterResponse(response, widgetId, $grid, $wrapper, $filters, options) {
        options = options || {};

        var appendMode = !!options.append;
        var fallbackPage = Math.max(1, parseInteger(options.requestedPage, appendMode ? 2 : 1));
        var fallbackPerPage = parseInteger(options.perPage, 12);
        var fallbackOffset = Math.max(0, parseInteger(options.requestedOffset, appendMode ? fallbackPerPage : 0));
        var currentPagingState = getWidgetPagingState(widgetId);
        var paginationMeta = {
            perPage: currentPagingState ? currentPagingState.perPage : fallbackPerPage,
            initialItems: currentPagingState ? currentPagingState.initialItems : fallbackPerPage,
            loadBatchSize: currentPagingState ? currentPagingState.loadBatchSize : fallbackPerPage,
            currentPage: Math.max(1, parseInteger(response && response.data ? response.data.page : fallbackPage, fallbackPage)),
            hasMore: !!(response && response.data && response.data.has_more),
            nextPage: Math.max(0, parseInteger(response && response.data ? response.data.next_page : 0, 0)),
            loadedCount: Math.max(0, parseInteger(response && response.data ? response.data.loaded_count : (appendMode ? fallbackOffset : 0), appendMode ? fallbackOffset : 0)),
            nextOffset: Math.max(0, parseInteger(response && response.data ? response.data.next_offset : 0, 0)),
            infiniteEnabled: currentPagingState ? currentPagingState.infiniteEnabled : parseBoolData($grid.attr('data-infinite-enabled'))
        };

        if (paginationMeta.loadBatchSize <= 0 && paginationMeta.perPage > 0) {
            paginationMeta.loadBatchSize = paginationMeta.perPage;
        }

        if (paginationMeta.perPage <= 0 && paginationMeta.loadBatchSize > 0) {
            paginationMeta.perPage = paginationMeta.loadBatchSize;
        }

        if (paginationMeta.initialItems <= 0 && currentPagingState && currentPagingState.initialItems > 0) {
            paginationMeta.initialItems = currentPagingState.initialItems;
        }

        if (paginationMeta.loadBatchSize <= 0 && currentPagingState && currentPagingState.loadBatchSize > 0) {
            paginationMeta.loadBatchSize = currentPagingState.loadBatchSize;
        }

        if ((paginationMeta.perPage <= 0 && paginationMeta.loadBatchSize <= 0) || paginationMeta.initialItems <= 0) {
            paginationMeta.infiniteEnabled = false;
            paginationMeta.hasMore = false;
            paginationMeta.nextPage = 0;
            paginationMeta.nextOffset = 0;
        }

        if (response.success && response.data) {
            var hasPosts = !!response.data.has_posts;
            var $responseNodes = createResponseNodes(response.data.html);
            var $responseItems = getResponseItems($responseNodes);

            syncDiscoveryResponse(widgetId, response.data, {
                skipRender: appendMode
            });
            prepareItemsForReveal($responseItems, appendMode ? 'append' : 'initial');

            if (appendMode) {
                if ($responseItems.length) {
                    $grid.append($responseItems);
                    $(document.body).trigger('bw:grid_rendered', [$grid]);
                }

                updateWidgetPagingState(widgetId, $.extend({}, paginationMeta, {
                    isLoading: true
                }));

                finalizeGridUpdate($grid, $responseItems, true, function () {
                    removeLoadSpacer(widgetId);
                    updateWidgetPagingState(widgetId, {
                        isLoading: false
                    });
                    syncInfiniteObserver(widgetId);
                }, 'append');

                return;
            }

            $filters.attr('data-has-posts', hasPosts ? '1' : '0');

            if (options.hadMasonryBefore) {
                destroyGridInstance($grid);
            }

            clearStaggerTimers(widgetId);
            $grid.empty().append($responseNodes);
            $(document.body).trigger('bw:grid_rendered', [$grid]);

            var $subcatRow = $('.bw-fpw-filter-row--subcategories[data-widget-id="' + widgetId + '"]');
            var $subcatOptions = $('.bw-fpw-subcategories-container[data-widget-id="' + widgetId + '"]');

            if ($subcatRow.length) {
                if (!hasPosts) {
                    $subcatRow.hide();
                } else if ($subcatOptions.find('.bw-fpw-subcat-button').length) {
                    $subcatRow.show();
                }
            }

            if (typeof response.data.tags_html !== 'undefined') {
                var $tagRow = $('.bw-fpw-filter-row--tags[data-widget-id="' + widgetId + '"]');
                var $tagOptions = $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"]');

                if ($tagRow.length && $tagOptions.length) {
                    var availableTags = Array.isArray(response.data.available_tags) ? response.data.available_tags.map(function (tag) { return parseInt(tag); }) : [];

                    if (availableTags.length) {
                        filterState[widgetId].tags = filterState[widgetId].tags.filter(function (tag) {
                            return availableTags.indexOf(tag) > -1;
                        });
                    }

                    if (!hasPosts) {
                        filterState[widgetId].tags = [];
                        $tagOptions.removeClass('bw-fpw-animating').css('opacity', '0');
                        setTimeout(function () {
                            $tagOptions.empty();
                            $tagRow.hide();
                        }, 150);
                    } else if (response.data.tags_html) {
                        $tagOptions.removeClass('bw-fpw-animating').css('opacity', '0');
                        setTimeout(function () {
                            $tagOptions.each(function () {
                                $(this).html(response.data.tags_html);
                            });
                            $tagRow.css('opacity', '0').show();

                            setTimeout(function () {
                                $tagOptions.addClass('bw-fpw-animating').css('opacity', '1');
                                $tagRow.css('opacity', '1');
                            }, 50);

                            if (filterState[widgetId].tags.length) {
                                $tagOptions.find('.bw-fpw-tag-button').each(function () {
                                    var $tagButton = $(this);
                                    var tagId = parseInt($tagButton.attr('data-tag'));

                                    if (filterState[widgetId].tags.indexOf(tagId) > -1) {
                                        $tagButton.addClass('active');
                                    }
                                });
                            }

                            // Auto-open tags dropdown in mobile mode after loading
                            if (isInMobileMode(widgetId)) {
                                var $mobileTagGroup = $('.bw-fpw-mobile-filter-group--tags[data-widget-id="' + widgetId + '"]');
                                if ($mobileTagGroup.length && $tagOptions.find('.bw-fpw-tag-button').length > 0) {
                                    setTimeout(function () {
                                        if (!$mobileTagGroup.hasClass('is-open')) {
                                            $mobileTagGroup.addClass('is-open');
                                            $mobileTagGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'false');
                                        }
                                    }, 100);
                                }
                            }
                        }, 150);
                    } else {
                        filterState[widgetId].tags = [];
                        $tagOptions.removeClass('bw-fpw-animating').css('opacity', '0');
                        setTimeout(function () {
                            $tagOptions.empty();
                            $tagRow.hide();
                        }, 150);
                    }
                }
            }

            updateWidgetPagingState(widgetId, $.extend({}, paginationMeta, {
                loadedCount: Math.max(0, parseInteger(response.data.loaded_count, $responseItems.length)),
                isLoading: false
            }));

            finalizeGridUpdate($grid, $responseItems, false, function () {
                $filters.removeClass('loading');
                syncInfiniteObserver(widgetId);
            }, 'initial');

        } else {
            if (appendMode) {
                updateWidgetPagingState(widgetId, {
                    isLoading: false,
                    hasMore: false,
                    nextPage: 0,
                    nextOffset: 0
                });
                syncInfiniteObserver(widgetId);
                return;
            }

            if (isDiscoveryDrawerMode(widgetId)) {
                filterState[widgetId].resultCount = 0;
                updateDiscoveryOptions(widgetId, {
                    types: [],
                    tags: [],
                    result_count: 0
                });
                renderDiscoveryUi(widgetId);
            }

            var emptyStateHtml = '<div class="bw-fpw-empty-state">';
            emptyStateHtml += '<p class="bw-fpw-empty-message">No results found.</p>';
            emptyStateHtml += '<button class="elementor-button bw-fpw-reset-filters" data-widget-id="' + widgetId + '">RESET FILTERS</button>';
            emptyStateHtml += '</div>';
            clearStaggerTimers(widgetId);
            $grid.html(emptyStateHtml);

            // Remove loading state
            $filters.removeClass('loading');
            $filters.attr('data-has-posts', '0');
            updateWidgetPagingState(widgetId, {
                currentPage: 1,
                hasMore: false,
                nextPage: 0,
                loadedCount: 0,
                nextOffset: 0,
                isLoading: false
            });
            $('.bw-fpw-filter-row--subcategories[data-widget-id="' + widgetId + '"], .bw-fpw-filter-row--tags[data-widget-id="' + widgetId + '"]').hide();
        }
    }

    function isInMobileMode(widgetId) {
        var $wrapper = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]').closest('.bw-product-grid-wrapper');
        return $wrapper.hasClass('bw-fpw-mobile-filters-enabled');
    }

    function isResponsiveFilterDrawerMode(widgetId) {
        var $wrapper = $('.bw-fpw-mobile-filter[data-widget-id="' + widgetId + '"]').closest('.bw-product-grid-wrapper');

        if (!$wrapper.length) {
            $wrapper = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]').closest('.bw-product-grid-wrapper');
        }

        return $wrapper.attr('data-responsive-filter-mode') === 'yes';
    }

    function getProductGridWrapper(widgetId) {
        return $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').closest('.bw-product-grid-wrapper');
    }

    function getDetachedDrawerHost(widgetId) {
        return $('.bw-fpw-drawer-host[data-widget-id="' + widgetId + '"]');
    }

    function getMobileFilterPanel(widgetId) {
        var $panel = $('.bw-fpw-mobile-filter-panel[data-widget-id="' + widgetId + '"]').first();

        if ($panel.length) {
            return $panel;
        }

        return $('.bw-fpw-mobile-filter[data-widget-id="' + widgetId + '"] .bw-fpw-mobile-filter-panel').first();
    }

    function ensureDetachedDiscoveryDrawer(widgetId) {
        var $wrapper;
        var $panel;
        var $host;

        if (!widgetId || !isResponsiveFilterDrawerMode(widgetId)) {
            return;
        }

        $wrapper = getProductGridWrapper(widgetId);
        $panel = getMobileFilterPanel(widgetId);

        if (!$wrapper.length || !$panel.length) {
            return;
        }

        $host = getDetachedDrawerHost(widgetId);

        if (!$host.length) {
            $host = $('<div class="bw-product-grid-wrapper bw-fpw-drawer-host"></div>');
            $host.attr('data-widget-id', widgetId);
            $('body').append($host);
        }

        $host.attr('data-responsive-filter-mode', 'yes');
        $host.attr('data-drawer-side', $wrapper.attr('data-drawer-side') || 'left');
        $host.toggleClass('bw-fpw-mobile-panel-open', $wrapper.hasClass('bw-fpw-mobile-panel-open'));

        if ($panel.parent()[0] !== $host[0]) {
            $panel.attr('data-widget-id', widgetId);
            $host.append($panel);
        }
    }

    function lockDrawerBodyScroll() {
        $('body').addClass('bw-fpw-drawer-no-scroll');
    }

    function unlockDrawerBodyScrollIfSafe() {
        var hasOpenFilterDrawer = $('.bw-fpw-mobile-filter-panel[aria-hidden="false"]').length > 0;

        if (!hasOpenFilterDrawer) {
            $('body').removeClass('bw-fpw-drawer-no-scroll');
        }
    }

    function initFilters() {
        $(document).on('click', '.bw-fpw-sort-trigger', function (e) {
            var widgetId = $(this).attr('data-widget-id');
            var state = getDiscoveryState(widgetId);

            if (!widgetId || !isDiscoveryDrawerMode(widgetId) || !isDiscoverySortEnabled(widgetId, state)) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            closeAllDiscoveryVisibleFilterPanels();
            closeAllDiscoverySortMenus(widgetId);
            state.ui.sortMenuOpen = !state.ui.sortMenuOpen;
            renderDiscoverySortControl(widgetId);
        });

        $(document).on('click', '.bw-fpw-sort-option', function (e) {
            var $option = $(this);
            var widgetId = $option.attr('data-widget-id');
            var state = getDiscoveryState(widgetId);
            var nextSortKey = normalizeDiscoverySortKey($option.attr('data-sort-key'));

            if (!widgetId || !isDiscoveryDrawerMode(widgetId) || !isDiscoverySortEnabled(widgetId, state)) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            if (state.sortKey === nextSortKey) {
                state.ui.sortMenuOpen = false;
                renderDiscoverySortControl(widgetId);
                return;
            }

            state.sortKey = nextSortKey;
            state.ui.sortMenuOpen = false;
            renderDiscoverySortControl(widgetId);
            filterPosts(widgetId);
        });

        $(document).on('click', function (e) {
            if ($(e.target).closest('.bw-fpw-sort').length) {
                return;
            }

            closeAllDiscoverySortMenus();
        });

        $(document).on('click', '.bw-fpw-visible-filter__trigger', function (e) {
            var widgetId = $(this).attr('data-widget-id');
            var groupKey = $(this).attr('data-group');
            var state = getDiscoveryState(widgetId);

            if (!widgetId || !isDiscoveryDrawerMode(widgetId) || !isDiscoveryVisibleFiltersEnabled(widgetId, state)) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            closeAllDiscoverySortMenus();
            closeAllDiscoveryVisibleFilterPanels(widgetId);
            state.ui.visibleFilterOpenGroup = state.ui.visibleFilterOpenGroup === groupKey ? '' : groupKey;
            renderDiscoveryVisibleFilters(widgetId);
        });

        $(document).on('click', '.bw-fpw-visible-filter__clear', function (e) {
            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');
            var groupKey = $button.attr('data-group');
            var state = getDiscoveryState(widgetId);

            if (!widgetId || !isDiscoveryDrawerMode(widgetId) || !isDiscoveryVisibleFiltersEnabled(widgetId, state)) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            clearDiscoveryFilterGroup(widgetId, groupKey);
        });

        $(document).on('click', function (e) {
            if ($(e.target).closest('.bw-fpw-visible-filter').length) {
                return;
            }

            closeAllDiscoveryVisibleFilterPanels();
        });

        $(document).on('click', '.bw-fpw-discovery-group__toggle', function (e) {
            e.preventDefault();

            var widgetId = $(this).attr('data-widget-id');
            var groupKey = $(this).attr('data-group');
            var state = getDiscoveryState(widgetId);

            if (!isDiscoveryDrawerMode(widgetId) || !state.ui.openGroups.hasOwnProperty(groupKey)) {
                return;
            }

            state.ui.openGroups[groupKey] = !state.ui.openGroups[groupKey];
            renderDiscoveryUi(widgetId);
        });

        $(document).on('input', '.bw-fpw-discovery-group-search__input', function () {
            var widgetId = $(this).attr('data-widget-id');
            var groupKey = $(this).attr('data-group');
            var surface = $(this).attr('data-surface') || 'drawer';
            var state = getDiscoveryState(widgetId);
            var cursorPosition = this.selectionStart;

            if (!isDiscoveryDrawerMode(widgetId) || !state.ui.optionSearches.hasOwnProperty(groupKey)) {
                return;
            }

            state.ui.optionSearches[groupKey] = $(this).val() || '';
            renderDiscoveryUi(widgetId);

            var $replacementInput = $('.bw-fpw-discovery-group-search__input[data-widget-id="' + widgetId + '"][data-group="' + groupKey + '"][data-surface="' + surface + '"]');
            if ($replacementInput.length) {
                $replacementInput.trigger('focus');
                if (typeof cursorPosition === 'number' && $replacementInput[0].setSelectionRange) {
                    $replacementInput[0].setSelectionRange(cursorPosition, cursorPosition);
                }
            }
        });

        $(document).on('click', '.bw-fpw-discovery-option', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');
            var groupKey = $button.attr('data-group');
            var rawValue = $button.attr('data-filter-value');
            var selectionValue = isDiscoveryTokenGroup(groupKey)
                ? normalizeDiscoveryTokenValue(rawValue)
                : parseInteger(rawValue, 0);

            if (!isDiscoveryDrawerMode(widgetId) || !groupKey || (isDiscoveryTokenGroup(groupKey) ? !selectionValue : selectionValue <= 0)) {
                return;
            }

            toggleDiscoverySelection(widgetId, groupKey, selectionValue);
            renderDiscoveryUi(widgetId);
            filterPosts(widgetId);
        });

        $(document).on('click', '.bw-fpw-active-chip__remove', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');
            var groupKey = $button.attr('data-group');
            var rawValue = $button.attr('data-filter-value');
            var filterValue = isDiscoveryTokenGroup(groupKey)
                ? normalizeDiscoveryTokenValue(rawValue)
                : parseInteger(rawValue, 0);

            if (!widgetId || !isDiscoveryDrawerMode(widgetId)) {
                return;
            }

            removeActiveDiscoveryFilter(widgetId, groupKey, filterValue);
        });

        $(document).on('click', '.bw-fpw-discovery-reset', function (e) {
            e.preventDefault();

            var widgetId = $(this).attr('data-widget-id');

            if (widgetId && isDiscoveryDrawerMode(widgetId)) {
                closeDiscoveryVisibleFilterPanel(widgetId);
                resetDiscoveryFilters(widgetId, false);
            }
        });

        $(document).on('click', '.bw-fpw-cat-button', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.closest('[data-widget-id]').attr('data-widget-id');
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var categoryId = $button.attr('data-category');
            var $subcatContainer = $('.bw-fpw-subcategories-container[data-widget-id="' + widgetId + '"]');
            var $tagOptions = $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"]');

            initFilterState(widgetId);

            // Update active state
            $filters.find('.bw-fpw-cat-button').removeClass('active');
            $button.addClass('active');

            // Update filter state
            filterState[widgetId].category = categoryId;
            filterState[widgetId].subcategories = [];
            filterState[widgetId].tags = [];

            // Reset tag visual state
            $filters.find('.bw-fpw-tag-button').removeClass('active');

            // Clear subcategory active states
            $filters.find('.bw-fpw-subcat-button').removeClass('active');

            var isMobileMode = isInMobileMode(widgetId);

            if ($subcatContainer.length) {
                // Auto-open subcategories dropdown in mobile when selecting a category
                loadSubcategories(categoryId, widgetId, isMobileMode);
            } else if (isMobileMode) {
                // Close subcategories dropdown if no subcategories available
                var $mobileSubcatGroup = $('.bw-fpw-mobile-filter-group--subcategories[data-widget-id="' + widgetId + '"]');
                if ($mobileSubcatGroup.length) {
                    $mobileSubcatGroup.removeClass('is-open');
                    $mobileSubcatGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'true');
                }
            }

            // Load tags for the selected category
            if ($tagOptions.length) {
                // Load tags via AJAX, auto-open in mobile mode
                loadTags(categoryId, widgetId, [], isMobileMode);
            }

            // Filter posts only if NOT in mobile mode
            // In mobile mode, wait for "Show Results" button click
            if (!isMobileMode) {
                filterPosts(widgetId);
            }
        });

        // Subcategory filter
        $(document).on('click', '.bw-fpw-subcat-button', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.closest('[data-widget-id]').attr('data-widget-id');
            var subcatId = parseInt($button.attr('data-subcategory'));

            initFilterState(widgetId);

            // Toggle active state
            $button.toggleClass('active');

            // Update filter state
            var subcats = filterState[widgetId].subcategories;
            var index = subcats.indexOf(subcatId);

            if (index > -1) {
                subcats.splice(index, 1);
            } else {
                subcats.push(subcatId);
            }

            // Reset tag state — previously selected tags may not exist under the new subcategory
            filterState[widgetId].tags = [];
            $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"] .bw-fpw-tag-button').removeClass('active');

            // Reload tags based on category and selected subcategories
            var currentCategory = filterState[widgetId].category;
            var $tagOptions = $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"]');
            var isMobileMode = isInMobileMode(widgetId);

            if ($tagOptions.length && currentCategory) {
                // Load tags via AJAX, auto-open in mobile mode
                loadTags(currentCategory, widgetId, subcats, isMobileMode);
            }

            // Filter posts only if NOT in mobile mode
            // In mobile mode, wait for "Show Results" button click
            if (!isMobileMode) {
                filterPosts(widgetId);
            }
        });

        // Tag filter
        $(document).on('click', '.bw-fpw-tag-button', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.closest('[data-widget-id]').attr('data-widget-id');
            var tagId = parseInt($button.attr('data-tag'));

            initFilterState(widgetId);

            // Toggle active state
            $button.toggleClass('active');

            // Update filter state
            var tags = filterState[widgetId].tags;
            var index = tags.indexOf(tagId);

            if (index > -1) {
                tags.splice(index, 1);
            } else {
                tags.push(tagId);
            }

            // Filter posts only if NOT in mobile mode
            // In mobile mode, wait for "Show Results" button click
            if (!isInMobileMode(widgetId)) {
                filterPosts(widgetId);
            }
        });

        $(document).on('click', '.bw-fpw-mobile-filter-button', function (e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            closeAllDiscoveryVisibleFilterPanels();
            openMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-filter-close', function (e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter-panel').attr('data-widget-id') || $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            closeMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-apply', function (e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter-panel').attr('data-widget-id') || $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            filterPosts(widgetId);
            closeMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-filter-panel--drawer', function (e) {
            if (e.target !== this) {
                return;
            }

            var widgetId = $(this).attr('data-widget-id') || $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            closeMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-dropdown-toggle', function () {
            var $group = $(this).closest('.bw-fpw-mobile-filter-group');
            var $panel = $group.find('.bw-fpw-mobile-dropdown-panel');
            var isOpen = $group.hasClass('is-open');

            // Toggle class - CSS handles the animation
            $group.toggleClass('is-open');

            if (isOpen) {
                $panel.attr('aria-hidden', 'true');
            } else {
                $panel.attr('aria-hidden', 'false');
            }
        });

        $(document).on('input', '.bw-fpw-year-slider__input', function () {
            var $input = $(this);
            var widgetId = $input.attr('data-widget-id');
            var state = getDiscoveryState(widgetId);
            var draft = getYearDraftState(state);
            var bounds = state.yearBounds || createEmptyYearBounds();
            var edge = $input.attr('data-year-edge');
            var otherValue;
            var nextRange;

            if (!widgetId || !isDiscoveryDrawerMode(widgetId)) {
                return;
            }

            if (edge === 'from') {
                draft.from = parseNullableYear($input.val());
                otherValue = draft.to !== null ? draft.to : bounds.max;
                nextRange = normalizeYearRange(draft.from, otherValue);
            } else {
                draft.to = parseNullableYear($input.val());
                otherValue = draft.from !== null ? draft.from : bounds.min;
                nextRange = normalizeYearRange(otherValue, draft.to);
            }

            draft.from = nextRange.from;
            draft.to = nextRange.to;
            state.ui.yearDraft = {
                from: draft.from,
                to: draft.to
            };

            updateYearRangePresentation(widgetId);
        });

        $(document).on('change mouseup touchend', '.bw-fpw-year-slider__input', function () {
            var $input = $(this);
            var widgetId = $input.attr('data-widget-id');
            var state = getDiscoveryState(widgetId);
            var draft = getYearDraftState(state);

            if (!widgetId || !isDiscoveryDrawerMode(widgetId)) {
                return;
            }

            commitYearRange(widgetId, draft.from, draft.to);
        });

        $(document).on('input', '.bw-fpw-year-input__field', function () {
            var $input = $(this);
            var widgetId = $input.attr('data-widget-id');
            var field = $input.attr('data-year-field');
            var state = getDiscoveryState(widgetId);
            var current = {
                from: field === 'from' ? parseNullableYear($input.val()) : state.year.from,
                to: field === 'to' ? parseNullableYear($input.val()) : state.year.to
            };

            if (!widgetId || !isDiscoveryDrawerMode(widgetId)) {
                return;
            }

            state.ui.yearDraft = normalizeYearRange(current.from, current.to);
            updateYearRangePresentation(widgetId);

            if (yearInputCommitTimers[widgetId]) {
                clearTimeout(yearInputCommitTimers[widgetId]);
            }

            yearInputCommitTimers[widgetId] = setTimeout(function () {
                delete yearInputCommitTimers[widgetId];
                commitYearRange(widgetId, state.ui.yearDraft.from, state.ui.yearDraft.to);
            }, 500);
        });

        $(document).on('keydown', '.bw-fpw-year-input__field', function (e) {
            if (e.key !== 'Enter') {
                return;
            }

            e.preventDefault();

            var widgetId = $(this).attr('data-widget-id');
            var state = getDiscoveryState(widgetId);

            if (yearInputCommitTimers[widgetId]) {
                clearTimeout(yearInputCommitTimers[widgetId]);
                delete yearInputCommitTimers[widgetId];
            }

            commitYearRange(widgetId, state.ui.yearDraft.from, state.ui.yearDraft.to);
        });

        $(document).on('blur change', '.bw-fpw-year-input__field', function () {
            var widgetId = $(this).attr('data-widget-id');
            var state = getDiscoveryState(widgetId);

            if (!widgetId || !isDiscoveryDrawerMode(widgetId)) {
                return;
            }

            if (yearInputCommitTimers[widgetId]) {
                clearTimeout(yearInputCommitTimers[widgetId]);
                delete yearInputCommitTimers[widgetId];
            }

            commitYearRange(widgetId, state.ui.yearDraft.from, state.ui.yearDraft.to);
        });

        $(document).on('click', '.bw-fpw-year-quick-range', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');

            if (!widgetId || !isDiscoveryDrawerMode(widgetId)) {
                return;
            }

            commitYearRange(widgetId, $button.attr('data-year-from'), $button.attr('data-year-to'));
        });

        // Reset filters button
        $(document).on('click', '.bw-fpw-reset-filters', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');

            if (!widgetId) {
                return;
            }

            if (isDiscoveryDrawerMode(widgetId)) {
                resetDiscoveryFilters(widgetId, isInMobileMode(widgetId));
                return;
            }

            initFilterState(widgetId);

            // Get default category from filters
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var defaultCategory = $filters.attr('data-default-category') || 'all';

            // Reset filter state (preserve options/labels caches from discovery state if present)
            var prevState = filterState[widgetId] || {};
            filterState[widgetId] = {
                sortKey: prevState.sortKey || normalizeDiscoverySortKey($('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first().attr('data-default-sort-key') || 'default'),
                category: defaultCategory,
                subcategories: [],
                tags: [],
                artists: [],
                authors: [],
                publishers: [],
                sources: [],
                techniques: [],
                search: '',
                appliedSearch: '',
                year: createEmptyYearState(),
                yearBounds: prevState.yearBounds || createEmptyYearBounds(),
                yearQuickRanges: prevState.yearQuickRanges || [],
                resultCount: 0,
                options: {
                    types: [],
                    tags: [],
                    artist: [],
                    author: [],
                    publisher: [],
                    source: [],
                    technique: []
                },
                labels: prevState.labels || {
                    types: {},
                    tags: {},
                    artist: {},
                    author: {},
                    publisher: {},
                    source: {},
                    technique: {}
                },
                ui: {
                    showTypes: true,
                    showTags: true,
                    showYears: !!(prevState.ui && prevState.ui.showYears),
                    showArtist: !!(prevState.ui && prevState.ui.showArtist),
                    showAuthor: !!(prevState.ui && prevState.ui.showAuthor),
                    showPublisher: !!(prevState.ui && prevState.ui.showPublisher),
                    showSource: !!(prevState.ui && prevState.ui.showSource),
                    showTechnique: !!(prevState.ui && prevState.ui.showTechnique),
                    showOrderBy: !!(prevState.ui && prevState.ui.showOrderBy),
                    orderTriggerStyle: prevState.ui && prevState.ui.orderTriggerStyle
                        ? prevState.ui.orderTriggerStyle
                        : (($('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first().attr('data-order-trigger-style') || 'icon') === 'dropdown' ? 'dropdown' : 'icon'),
                    sortMenuOpen: false,
                    searchEnabled: prevState.ui && typeof prevState.ui.searchEnabled === 'boolean'
                        ? prevState.ui.searchEnabled
                        : (($('.bw-fpw-grid[data-widget-id="' + widgetId + '"]').first().attr('data-search-enabled') || 'yes') === 'yes'),
                    lastFilterUiSignature: '',
                    optionSearches: {
                        types: '',
                        tags: '',
                        artist: '',
                        author: '',
                        publisher: '',
                        source: '',
                        technique: ''
                    },
                    openGroups: {
                        types: false,
                        tags: false,
                        years: false,
                        artist: false,
                        author: false,
                        publisher: false,
                        source: false,
                        technique: false
                    },
                    yearDraft: createEmptyYearState()
                }
            };

            // Clear search timers and inputs
            if (discoverySearchTimers[widgetId]) {
                clearTimeout(discoverySearchTimers[widgetId]);
                delete discoverySearchTimers[widgetId];
            }
            if (searchDebounceTimers[widgetId]) {
                clearTimeout(searchDebounceTimers[widgetId]);
                delete searchDebounceTimers[widgetId];
            }
            $('.bw-fpw-search-input[data-widget-id="' + widgetId + '"]').val('');

            // Reset all category buttons
            $filters.find('.bw-fpw-cat-button').removeClass('active');

            // Activate the default category button
            var $defaultCatButton = $filters.find('.bw-fpw-cat-button[data-category="' + defaultCategory + '"]');
            $defaultCatButton.addClass('active');

            // Reset all subcategory buttons
            $filters.find('.bw-fpw-subcat-button').removeClass('active');

            // Reset all tag buttons
            $filters.find('.bw-fpw-tag-button').removeClass('active');

            // Close mobile panel if open
            var isMobile = isInMobileMode(widgetId);
            if (isMobile) {
                closeMobilePanel(widgetId);
            }

            // Reload subcategories if default category is not 'all'
            var $subcatContainer = $('.bw-fpw-subcategories-container[data-widget-id="' + widgetId + '"]');
            if ($subcatContainer.length && defaultCategory !== 'all') {
                loadSubcategories(defaultCategory, widgetId, false);
            }

            // Reload tags if default category is not 'all'
            var $tagOptions = $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"]');
            if ($tagOptions.length && defaultCategory !== 'all') {
                loadTags(defaultCategory, widgetId, [], false);
            }

            // Filter posts to show initial state
            filterPosts(widgetId);

        });

        $(document).on('keyup', function (e) {
            if (e.key !== 'Escape') {
                return;
            }

            closeAllDiscoverySortMenus();

            $('.bw-fpw-mobile-filter-panel[aria-hidden="false"]').each(function () {
                var widgetId = $(this).attr('data-widget-id') || $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');

                if (widgetId) {
                    closeMobilePanel(widgetId);
                }
            });
        });

        $(document).on('change', '.bw-fpw-sort-select', function () {
            var $select = $(this);
            var widgetId = $select.attr('data-widget-id');
            var val = $select.val() || '';
            var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');

            if (!$grid.length) {
                return;
            }

            var newOrderBy, newOrder;

            if (val === '') {
                // "Default" option — restore widget's configured sort
                newOrderBy = $select.attr('data-default-order-by') || 'date';
                newOrder   = $select.attr('data-default-order')    || 'DESC';
            } else {
                var parts = val.split('|');
                if (parts.length !== 2 || !parts[0] || !parts[1]) {
                    return;
                }
                newOrderBy = parts[0];
                newOrder   = parts[1];
            }

            $grid.attr('data-order-by', newOrderBy);
            $grid.attr('data-order', newOrder);

            filterPosts(widgetId);
        });
    }

    function openMobilePanel(widgetId) {
        var $wrapper = getProductGridWrapper(widgetId);
        var $host;
        var $panel;

        ensureDetachedDiscoveryDrawer(widgetId);
        $host = getDetachedDrawerHost(widgetId);
        $panel = getMobileFilterPanel(widgetId);

        if ($panel.length) {
            $wrapper.addClass('bw-fpw-mobile-panel-open');
            $host.addClass('bw-fpw-mobile-panel-open');
            $panel.attr('aria-hidden', 'false');

            if (isResponsiveFilterDrawerMode(widgetId)) {
                lockDrawerBodyScroll();
                ensureDiscoveryDrawerBodyListener(widgetId);
                syncDiscoveryDrawerStickyState(widgetId);
            }
        }
    }

    function closeMobilePanel(widgetId) {
        var $panel = getMobileFilterPanel(widgetId);
        var $wrapper = getProductGridWrapper(widgetId);
        var $host = getDetachedDrawerHost(widgetId);

        if ($panel.length) {
            $wrapper.removeClass('bw-fpw-mobile-panel-open');
            $host.removeClass('bw-fpw-mobile-panel-open');
            $panel.attr('aria-hidden', 'true');

            if (isResponsiveFilterDrawerMode(widgetId)) {
                syncDiscoveryDrawerStickyState(widgetId);
                unlockDrawerBodyScrollIfSafe();
            }
        }
    }

    function toggleResponsiveFilters() {
        $('.bw-product-grid-wrapper').each(function () {
            var $wrapper = $(this);
            var breakpoint = parseInt($wrapper.attr('data-filter-breakpoint')) || 900;
            var responsiveDrawerMode = $wrapper.attr('data-responsive-filter-mode') === 'yes';
            var width = window.innerWidth || $(window).width();
            var widgetId = $wrapper.find('.bw-fpw-grid').first().attr('data-widget-id');

            if (responsiveDrawerMode || width < breakpoint) {
                $wrapper.addClass('bw-fpw-mobile-filters-enabled');

                if (responsiveDrawerMode && widgetId) {
                    ensureDetachedDiscoveryDrawer(widgetId);
                }
            } else {
                $wrapper.removeClass('bw-fpw-mobile-filters-enabled bw-fpw-mobile-panel-open');

                if (widgetId) {
                    getDetachedDrawerHost(widgetId).removeClass('bw-fpw-mobile-panel-open');
                    getMobileFilterPanel(widgetId).attr('aria-hidden', 'true');
                } else {
                    $wrapper.find('.bw-fpw-mobile-filter-panel').attr('aria-hidden', 'true');
                }

                unlockDrawerBodyScrollIfSafe();
            }
        });
    }

    // ============================================
    // INITIALIZATION
    // ============================================

    function initWidget($scope) {
        var $context = $scope && $scope.length ? $scope : $(document);
        var $grids = $();

        if ($context.hasClass('bw-fpw-grid')) {
            $grids = $grids.add($context);
        }

        $grids = $grids.add($context.find('.bw-fpw-grid'));

        if (!$grids.length) {
            return;
        }

        $grids.each(function () {
            var $grid = $(this);
            var widgetId = $grid.attr('data-widget-id');

            // If this widget was previously initialised with a different DOM
            // element it has been re-rendered by Elementor — purge stale state
            // before starting fresh.
            var existingState = widgetPagingState[widgetId];
            if (existingState && existingState.gridEl && existingState.gridEl !== $grid[0]) {
                destroyWidgetState(widgetId);
            }

            initFilterState(widgetId);
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var state = filterState[widgetId];

            if ($filters.length) {
                var $activeCategory = $filters.find('.bw-fpw-cat-button.active').first();
                if ($activeCategory.length) {
                    state.category = $activeCategory.attr('data-category') || 'all';
                }

                var initialSubcats = [];
                $filters.find('.bw-fpw-subcat-button.active').each(function () {
                    var id = parseInt($(this).attr('data-subcategory'));
                    if (!isNaN(id)) {
                        initialSubcats.push(id);
                    }
                });
                state.subcategories = initialSubcats;

                var initialTags = [];
                $filters.find('.bw-fpw-tag-button.active').each(function () {
                    var id = parseInt($(this).attr('data-tag'));
                    if (!isNaN(id)) {
                        initialTags.push(id);
                    }
                });
                state.tags = initialTags;
            }

            if (isDiscoveryDrawerMode(widgetId)) {
                ensureDetachedDiscoveryDrawer(widgetId);
                var bootstrapPayload = getDiscoveryBootstrapPayload(widgetId) || {};

                state.ui.searchEnabled = !!bootstrapPayload.search_enabled;
                state.ui.showTypes = !!bootstrapPayload.show_types;
                state.ui.showTags = !!bootstrapPayload.show_tags;
                state.ui.showYears = !!bootstrapPayload.show_years;
                state.ui.showOrderBy = !!bootstrapPayload.show_order_by;
                state.ui.showVisibleFilters = !!bootstrapPayload.show_visible_filters;
                state.ui.orderTriggerStyle = bootstrapPayload.order_trigger_style === 'dropdown' ? 'dropdown' : 'icon';
                state.sortKey = normalizeDiscoverySortKey(bootstrapPayload.default_sort_key || state.sortKey || 'default');
                state.resultCount = Math.max(0, parseInteger($grid.attr('data-result-count'), 0));

                updateDiscoveryOptions(widgetId, {
                    types: Array.isArray(bootstrapPayload.types) ? bootstrapPayload.types : [],
                    tags: Array.isArray(bootstrapPayload.tags) ? bootstrapPayload.tags : [],
                    year: bootstrapPayload.year || null,
                    advanced: bootstrapPayload.advanced || {},
                    result_count: state.resultCount
                });
                state.ui.lastFilterUiSignature = getDiscoveryFilterUiSignature({
                    types: Array.isArray(bootstrapPayload.types) ? bootstrapPayload.types : [],
                    tags: Array.isArray(bootstrapPayload.tags) ? bootstrapPayload.tags : [],
                    year: bootstrapPayload.year || null,
                    advanced: bootstrapPayload.advanced || {},
                    result_count: state.resultCount
                });
                renderDiscoveryUi(widgetId);
            } else {
                state.ui.searchEnabled = ($grid.attr('data-search-enabled') || 'yes') === 'yes';
                state.ui.showOrderBy = ($grid.attr('data-show-order-by') || 'no') === 'yes';
                state.ui.showVisibleFilters = ($grid.attr('data-show-visible-filters') || 'no') === 'yes';
                state.ui.orderTriggerStyle = ($grid.attr('data-order-trigger-style') || 'icon') === 'dropdown' ? 'dropdown' : 'icon';
            }

            if (!state.ui.searchEnabled) {
                state.search = '';
                state.appliedSearch = '';
                if (discoverySearchTimers[widgetId]) {
                    clearTimeout(discoverySearchTimers[widgetId]);
                    delete discoverySearchTimers[widgetId];
                }
                if (searchDebounceTimers[widgetId]) {
                    clearTimeout(searchDebounceTimers[widgetId]);
                    delete searchDebounceTimers[widgetId];
                }
            }

            if (!isElementorEditor()) {
                var $initialItems = $grid.children('.bw-fpw-item');
                if ($initialItems.length) {
                    prepareItemsForReveal($initialItems, 'initial');
                }
            }

            updateWidgetPagingState(widgetId, {
                isLoading: false
            });
            initGrid($grid, function () {
                runInitialReveal($grid);
                syncInfiniteObserver(widgetId);
            });
        });

        syncSearchFeatureBindings();
    }

    // Window resize handler
    var lastDeviceByGrid = {};

    function handleGridResize() {
        var isEditor = isElementorEditor();

        toggleResponsiveFilters();

        $('.bw-fpw-grid.bw-fpw-initialized').each(function () {
            var $grid = $(this);

            if (useCssGrid($grid)) {
                layoutGrid($grid, false);
                return;
            }

            if (isEditor) {
                setItemWidths($grid);
                layoutGrid($grid, false);
                updateGridHeight($grid);
                return;
            }

            var gridId = $grid.attr('data-widget-id') || $grid.index();
            var currentDevice = getCurrentDevice($grid);
            var previousDevice = lastDeviceByGrid[gridId];
            var deviceChanged = currentDevice !== previousDevice;

            if (deviceChanged && $grid[0]) {
                void $grid[0].offsetHeight;
            }

            setItemWidths($grid);
            layoutGrid($grid, deviceChanged);
            updateGridHeight($grid);

            lastDeviceByGrid[gridId] = currentDevice;
        });
    }

    $(function () {
        initFilters();
        syncSearchFeatureBindings();
        handleGridResize();

        // Fallback: if Elementor's frontend JS fails to fire the
        // 'frontend/element_ready' hook (e.g. due to an Elementor JS
        // error in initOnReadyComponents), manually initialise any
        // grids that are still uninitialised after a short grace period.
        setTimeout(function () {
            var $uninitialized = $('.bw-fpw-grid').not('.bw-fpw-initialized');
            if ($uninitialized.length) {
                initWidget($(document));
            }
        }, 500);

        var resizeTimer;
        $(window)
            .off('resize.bwProductGrid orientationchange.bwProductGrid')
            .on('resize.bwProductGrid orientationchange.bwProductGrid', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    handleGridResize();
                }, 150);
            });
    });

    // ============================================
    // ELEMENTOR INTEGRATION
    // ============================================

    var hooksRegistered = false;

    function addElementorHandler($scope) {
        var $targetScope = $scope && $scope.length ? $scope : $(document);

        setTimeout(function () {
            initWidget($targetScope);
        }, 80);
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

        elementorFrontend.hooks.addAction('frontend/element_ready/bw-product-grid.default', addElementorHandler);

        // In the Elementor editor, watch for widget DOM nodes being removed so
        // we can release all state tied to that widget ID.  MutationObserver is
        // used only inside the editor to avoid any overhead on the frontend.
        if (isElementorEditor() && typeof window.MutationObserver !== 'undefined') {
            var domObserver = new window.MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.removedNodes.forEach(function (node) {
                        if (!node || node.nodeType !== 1) {
                            return;
                        }
                        var $node = $(node);
                        var $grids = $node.hasClass('bw-fpw-grid')
                            ? $node
                            : $node.find('.bw-fpw-grid');
                        $grids.each(function () {
                            var wId = $(this).attr('data-widget-id');
                            if (wId) {
                                destroyWidgetState(wId);
                            }
                        });
                    });
                });
            });
            domObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);

})(jQuery);
