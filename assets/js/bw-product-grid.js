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

    function getGutterValue($grid, device) {
        var attr = 'data-gap-' + device;
        var gap = parseInt($grid.attr(attr));

        if (!gap || isNaN(gap)) {
            gap = 15;
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
        var gap = getGutterValue($grid, device);
        var $items = $masonryContainer.find('.bw-fpw-item');

        if (!$items.length) {
            return;
        }

        var containerWidth = $masonryContainer.width();
        var totalGap = gap * (columnsCount - 1);
        var itemWidth = (containerWidth - totalGap) / columnsCount;
        $grid.data('bw-item-width', itemWidth > 0 ? itemWidth : 0);

        $items.each(function () {
            var $item = $(this);
            $item.css({
                'width': itemWidth + 'px',
                'margin-bottom': gap + 'px'
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
        var gap = getGutterValue($grid, device);
        var instance = $grid.data('masonry');

        var lastColumns = $grid.data('bw-last-columns');
        var lastGutter = $grid.data('bw-last-gutter');
        var lastDevice = $grid.data('bw-last-device');

        if (instance && (lastColumns !== columnsCount || lastGutter !== gap || lastDevice !== device)) {
            forceReinit = true;
        }

        $grid.data('bw-last-columns', columnsCount);
        $grid.data('bw-last-gutter', gap);
        $grid.data('bw-last-device', device);

        if (instance && !forceReinit) {
            setItemWidths($grid);

            withImagesLoaded(getPrimaryImageScope($grid), function () {
                instance.options.gutter = gap;
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
                gutter: gap,
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
        }, imageWaitTimeout || 800);
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
    var CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

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

    function setCachedData(cacheKey, data) {
        ajaxCache[cacheKey] = {
            data: data,
            timestamp: new Date().getTime()
        };
    }

    function parseInteger(value, fallback) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? fallback : parsed;
    }

    function parseBoolData(value) {
        var normalized = String(value || '').toLowerCase();
        return normalized === '1' || normalized === 'true' || normalized === 'yes';
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
            // Check if there's a default category set
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var defaultCategory = $filters.attr('data-default-category');
            var initialCategory = 'all';

            if (defaultCategory && defaultCategory !== 'all') {
                initialCategory = defaultCategory;
            }

            filterState[widgetId] = {
                category: initialCategory,
                subcategories: [],
                tags: []
            };
        }
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
                setCachedData(cacheKey, response);
                processSubcategoriesResponse(response, widgetId, $subcatContainers, $subcatRow, hasPosts, isMobile, autoOpenMobile);
            },
            error: function (xhr, status) {
                delete ajaxRequestQueue[queueKey];
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

        // Check cache first
        var cacheKey = getCacheKey('tags', {
            category_id: categoryId,
            post_type: postType,
            subcategories: subcategories || [],
            widget_id: widgetId
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            processTagsResponse(cachedResponse, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile);
            return;
        }

        ajaxRequestQueue[queueKey] = $.ajax({
            url: bwProductGridAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_fpw_get_tags',
                category_id: categoryId,
                post_type: postType,
                subcategories: subcategories || [],
                nonce: bwProductGridAjax.nonce
            },
            success: function (response) {
                delete ajaxRequestQueue[queueKey];
                setCachedData(cacheKey, response);
                processTagsResponse(response, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile);
            },
            error: function (xhr, status) {
                delete ajaxRequestQueue[queueKey];
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

        // Scroll reveal listener (namespaced per widget)
        $(window).off('scroll.bwreveal' + widgetId);

        // Infinite-scroll load spacer
        removeLoadSpacer(widgetId);

        // Per-widget state objects
        delete filterState[widgetId];
        delete widgetPagingState[widgetId];
        delete staggerTimersByWidget[widgetId];
        delete staggerObserversByWidget[widgetId];
        delete lastDeviceByGrid[widgetId];
        delete infiniteLoadSpacers[widgetId];
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
        var baseDelay = 40;
        var cleanupDelay = 600;

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

        var STAGGER = 40;        // ms between items within a batch
        var cleanupDelay = 600;  // must be >= CSS transition duration (0.45s)
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
        withImagesLoaded(getPrimaryImageScope($imageScope), runFinalize, appendMode ? 450 : 800);
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
        var orderBy = $grid.attr('data-order-by') || 'date';
        var order = $grid.attr('data-order') || 'DESC';
        var requestPerPage = appendMode ? pagingState.loadBatchSize : pagingState.initialItems;
        var requestedOffset = appendMode ? Math.max(0, parseInteger(options.offset, pagingState.nextOffset > 0 ? pagingState.nextOffset : pagingState.loadedCount)) : 0;

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
            category: state.category,
            subcategories: state.subcategories,
            tags: state.tags,
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
                category: state.category,
                subcategories: state.subcategories,
                tags: state.tags,
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

            var emptyStateHtml = '<div class="bw-fpw-empty-state">';
            emptyStateHtml += '<p class="bw-fpw-empty-message">No content available</p>';
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

    function initFilters() {
        $(document).on('click', '.bw-fpw-cat-button', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.closest('[data-widget-id]').attr('data-widget-id');
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var categoryId = $button.attr('data-category');
            var $subcatRow = $('.bw-fpw-filter-subcategories[data-widget-id="' + widgetId + '"]');
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
            openMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-filter-close', function (e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            closeMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-apply', function (e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            filterPosts(widgetId);
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

        // Reset filters button
        $(document).on('click', '.bw-fpw-reset-filters', function (e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');

            if (!widgetId) {
                return;
            }

            initFilterState(widgetId);

            // Get default category from filters
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
            var defaultCategory = $filters.attr('data-default-category') || 'all';

            // Reset filter state
            filterState[widgetId] = {
                category: defaultCategory,
                subcategories: [],
                tags: []
            };

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
    }

    function openMobilePanel(widgetId) {
        var $panel = $('.bw-fpw-mobile-filter[data-widget-id="' + widgetId + '"] .bw-fpw-mobile-filter-panel');
        var $wrapper = $panel.closest('.bw-product-grid-wrapper');

        if ($panel.length) {
            $wrapper.addClass('bw-fpw-mobile-panel-open');
            $panel.attr('aria-hidden', 'false');
        }
    }

    function closeMobilePanel(widgetId) {
        var $panel = $('.bw-fpw-mobile-filter[data-widget-id="' + widgetId + '"] .bw-fpw-mobile-filter-panel');
        var $wrapper = $panel.closest('.bw-product-grid-wrapper');

        if ($panel.length) {
            $wrapper.removeClass('bw-fpw-mobile-panel-open');
            $panel.attr('aria-hidden', 'true');
        }
    }

    function toggleResponsiveFilters() {
        $('.bw-product-grid-wrapper').each(function () {
            var $wrapper = $(this);
            var breakpoint = parseInt($wrapper.attr('data-filter-breakpoint')) || 900;
            var width = window.innerWidth || $(window).width();

            if (width < breakpoint) {
                $wrapper.addClass('bw-fpw-mobile-filters-enabled');
            } else {
                $wrapper.removeClass('bw-fpw-mobile-filters-enabled bw-fpw-mobile-panel-open');
                $wrapper.find('.bw-fpw-mobile-filter-panel').attr('aria-hidden', 'true');
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

            if ($filters.length) {
                var $activeCategory = $filters.find('.bw-fpw-cat-button.active').first();
                if ($activeCategory.length) {
                    filterState[widgetId].category = $activeCategory.attr('data-category') || 'all';
                }

                var initialSubcats = [];
                $filters.find('.bw-fpw-subcat-button.active').each(function () {
                    var id = parseInt($(this).attr('data-subcategory'));
                    if (!isNaN(id)) {
                        initialSubcats.push(id);
                    }
                });
                filterState[widgetId].subcategories = initialSubcats;

                var initialTags = [];
                $filters.find('.bw-fpw-tag-button.active').each(function () {
                    var id = parseInt($(this).attr('data-tag'));
                    if (!isNaN(id)) {
                        initialTags.push(id);
                    }
                });
                filterState[widgetId].tags = initialTags;
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
