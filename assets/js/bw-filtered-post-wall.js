(function ($) {
    'use strict';

    console.log('🚀 BW Filtered Post Wall: Script loaded');

    // ============================================
    // MASONRY SYSTEM (from wallpost)
    // ============================================

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

    function setItemWidths($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        var device = getCurrentDevice($grid);
        var columnsCount = getColumns($grid, device);
        var gap = getGutterValue($grid, device);
        var $items = $grid.find('.bw-fpw-item');

        if (!$items.length) {
            return;
        }

        var containerWidth = $grid.width();
        var totalGap = gap * (columnsCount - 1);
        var itemWidth = (containerWidth - totalGap) / columnsCount;

        $items.each(function() {
            var $item = $(this);
            $item.css({
                'width': itemWidth + 'px',
                'margin-bottom': gap + 'px'
            });
        });
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

        var maxHeight = 0;
        var $items = $grid.find('.bw-fpw-item');

        $items.each(function() {
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

        if (typeof $.fn.masonry === 'function' && $grid.data('masonry')) {
            $grid.masonry('destroy');
        }

        $grid.removeClass('bw-fpw-initialized');
    }

    function layoutGrid($grid, forceReinit) {
        if (typeof $.fn.masonry !== 'function') {
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

            withImagesLoaded($grid, function () {
                instance.options.gutter = gap;

                if (typeof instance.reloadItems === 'function') {
                    instance.reloadItems();
                }

                instance.layout();
                updateGridHeight($grid);

                setTimeout(function() {
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 100);
            });
            return;
        }

        var initializeMasonry = function () {
            destroyGridInstance($grid);
            setItemWidths($grid);

            var masonryOptions = {
                itemSelector: '.bw-fpw-item',
                percentPosition: false,
                gutter: gap,
                horizontalOrder: true,
                transitionDuration: '0.3s'
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

            setTimeout(function() {
                var instance = $grid.data('masonry');
                if (instance && typeof instance.layout === 'function') {
                    instance.layout();
                    updateGridHeight($grid);
                }
            }, 100);
        };

        withImagesLoaded($grid, initializeMasonry);
    }

    function initGrid($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        layoutGrid($grid, true);
    }

    // ============================================
    // FILTER SYSTEM
    // ============================================

    var filterState = {};

    // ============================================
    // PERFORMANCE OPTIMIZATION - CACHING SYSTEM
    // ============================================

    var ajaxCache = {};
    var ajaxRequestQueue = {};
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

    function clearWidgetCache(widgetId) {
        Object.keys(ajaxCache).forEach(function(key) {
            if (key.indexOf('_' + widgetId) > -1) {
                delete ajaxCache[key];
            }
        });
    }

    // ============================================
    // DEBOUNCING SYSTEM
    // ============================================

    var debounceTimers = {};

    function debounce(func, wait, immediate) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;

            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };

            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);

            if (callNow) func.apply(context, args);
        };
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

    function loadSubcategories(categoryId, widgetId, autoOpenMobile) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');
        var postType = $grid.attr('data-post-type') || 'product';
        var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');
        var $subcatRow = $('.bw-fpw-filter-subcategories[data-widget-id="' + widgetId + '"]');
        var $subcatContainers = $('.bw-fpw-subcategories-container[data-widget-id="' + widgetId + '"]');
        var hasPostsAttr = $filters.attr('data-has-posts');
        var hasPosts = typeof hasPostsAttr === 'undefined' ? true : hasPostsAttr === '1';
        var isMobile = isInMobileMode(widgetId);

        // Fade out before clearing
        if ($subcatContainers.length) {
            $subcatContainers.removeClass('bw-fpw-animating').css('opacity', '0');
            setTimeout(function() {
                $subcatContainers.empty();
            }, 150);
        }

        console.log('📂 Loading subcategories for category:', categoryId);

        // Check cache first
        var cacheKey = getCacheKey('subcategories', {
            category_id: categoryId,
            post_type: postType,
            widget_id: widgetId
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            console.log('⚡ Using cached subcategories');
            processSubcategoriesResponse(cachedResponse, widgetId, $subcatContainers, $subcatRow, hasPosts, isMobile, autoOpenMobile);
            return;
        }

        $.ajax({
            url: bwFilteredPostWallAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_fpw_get_subcategories',
                category_id: categoryId,
                post_type: postType,
                nonce: bwFilteredPostWallAjax.nonce
            },
            success: function(response) {
                // Cache the response
                setCachedData(cacheKey, response);
                processSubcategoriesResponse(response, widgetId, $subcatContainers, $subcatRow, hasPosts, isMobile, autoOpenMobile);
            },
            error: function() {
                console.error('❌ Error loading subcategories');
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

            $.each(subcats, function(index, subcat) {
                html += '<button class="bw-fpw-filter-option bw-fpw-subcat-button" data-subcategory="' + subcat.term_id + '">';
                html += '<span class="bw-fpw-option-label">' + subcat.name + '</span> ';
                html += '<span class="bw-fpw-option-count">(' + subcat.count + ')</span>';
                html += '</button>';
            });

            $subcatContainers.each(function() {
                var $container = $(this);
                $container.html(html);
                // Trigger fade + slide animation
                setTimeout(function() {
                    $container.addClass('bw-fpw-animating').css('opacity', '1');
                }, 50);
            });
            if ($subcatRow.length) {
                var hasButtons = $subcatContainers.find('.bw-fpw-subcat-button').length > 0;
                if (hasPosts && hasButtons) {
                    $subcatRow.css('opacity', '0').show();
                    setTimeout(function() {
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
                    setTimeout(function() {
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

        // Fade out before clearing
        if ($tagContainers.length) {
            $tagContainers.removeClass('bw-fpw-animating').css('opacity', '0');
            setTimeout(function() {
                $tagContainers.empty();
            }, 150);
        }

        console.log('🏷️ Loading tags for category:', categoryId);

        // Check cache first
        var cacheKey = getCacheKey('tags', {
            category_id: categoryId,
            post_type: postType,
            subcategories: subcategories || [],
            widget_id: widgetId
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            console.log('⚡ Using cached tags');
            processTagsResponse(cachedResponse, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile);
            return;
        }

        $.ajax({
            url: bwFilteredPostWallAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_fpw_get_tags',
                category_id: categoryId,
                post_type: postType,
                subcategories: subcategories || [],
                nonce: bwFilteredPostWallAjax.nonce
            },
            success: function(response) {
                // Cache the response
                setCachedData(cacheKey, response);
                processTagsResponse(response, widgetId, $tagContainers, $tagRow, hasPosts, isMobile, autoOpenMobile);
            },
            error: function() {
                console.error('❌ Error loading tags');
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

            $.each(tags, function(index, tag) {
                html += '<button class="bw-fpw-filter-option bw-fpw-tag-button" data-tag="' + tag.term_id + '">';
                html += '<span class="bw-fpw-option-label">' + tag.name + '</span> ';
                html += '<span class="bw-fpw-option-count">(' + tag.count + ')</span>';
                html += '</button>';
            });

            $tagContainers.each(function() {
                var $container = $(this);
                $container.html(html);
                // Trigger fade + slide animation
                setTimeout(function() {
                    $container.addClass('bw-fpw-animating').css('opacity', '1');
                }, 50);
            });

            if ($tagRow.length) {
                var hasButtons = $tagContainers.find('.bw-fpw-tag-button').length > 0;
                if (hasPosts && hasButtons) {
                    $tagRow.css('opacity', '0').show();
                    setTimeout(function() {
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
                    setTimeout(function() {
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

    function loadAndOpenTagsInMobile(categoryId, widgetId) {
        var $mobileTagGroup = $('.bw-fpw-mobile-filter-group--tags[data-widget-id="' + widgetId + '"]');
        var $tagOptions = $('.bw-fpw-tag-options[data-widget-id="' + widgetId + '"]');

        // Check if tags are available in the mobile panel
        if ($mobileTagGroup.length && $tagOptions.length) {
            var hasTags = $tagOptions.find('.bw-fpw-tag-button').length > 0;

            if (hasTags) {
                // Auto-open tags dropdown in mobile
                setTimeout(function() {
                    if (!$mobileTagGroup.hasClass('is-open')) {
                        $mobileTagGroup.addClass('is-open');
                        $mobileTagGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'false');
                    }
                }, 300);
            } else {
                // Close tags dropdown if no tags available
                $mobileTagGroup.removeClass('is-open');
                $mobileTagGroup.find('.bw-fpw-mobile-dropdown-panel').attr('aria-hidden', 'true');
            }
        }
    }

    function animatePostsStaggered($grid) {
        if (!$grid || !$grid.length) {
            return;
        }

        var $items = $grid.find('.bw-fpw-item');

        if (!$items.length) {
            return;
        }

        // Reset all items to invisible state first
        $items.removeClass('bw-fpw-item--visible');

        // Apply staggered fade-in with delay
        $items.each(function(index) {
            var $item = $(this);
            var delay = index * 80; // 80ms delay between each item

            setTimeout(function() {
                $item.addClass('bw-fpw-item--visible');
            }, delay);
        });

        console.log('✨ Staggered animation applied to', $items.length, 'posts');
    }

    function filterPosts(widgetId) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');
        var $wrapper = $grid.closest('.bw-filtered-post-wall');
        var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');

        if (!$grid.length) {
            console.error('❌ Grid not found for widget:', widgetId);
            return;
        }

        var state = filterState[widgetId];
        var postType = $grid.attr('data-post-type') || 'product';
        var imageToggle = $grid.attr('data-image-toggle') || 'no';
        var imageSize = $grid.attr('data-image-size') || 'large';
        var hoverEffect = $grid.attr('data-hover-effect') || 'no';
        var openCartPopup = $grid.attr('data-open-cart-popup') || 'no';
        var orderBy = $grid.attr('data-order-by') || 'date';
        var order = $grid.attr('data-order') || 'DESC';

        console.log('🔍 Filtering posts:', state);

        // Cancel pending request for this widget if exists
        if (ajaxRequestQueue[widgetId]) {
            ajaxRequestQueue[widgetId].abort();
            console.log('⚠️ Cancelled pending request for widget:', widgetId);
        }

        // Check cache first
        var cacheKey = getCacheKey('filter_posts', {
            widget_id: widgetId,
            post_type: postType,
            category: state.category,
            subcategories: state.subcategories,
            tags: state.tags,
            order_by: orderBy,
            order: order
        });

        var cachedResponse = getCachedData(cacheKey);
        if (cachedResponse) {
            console.log('⚡ Using cached filter results - INSTANT!');
            processFilterResponse(cachedResponse, widgetId, $grid, $wrapper, $filters, false);
            return;
        }

        // Fade out grid before filtering
        $grid.css('opacity', '0');

        // Show loading state
        $wrapper.addClass('bw-filtered-post-wall--loading');
        $filters.addClass('loading');

        // OPTIMIZATION: Only destroy masonry if necessary
        // Store current instance to check if we need full reinit
        var hadMasonryBefore = $grid.data('masonry') ? true : false;

        ajaxRequestQueue[widgetId] = $.ajax({
            url: bwFilteredPostWallAjax.ajaxurl,
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
                hover_effect: hoverEffect,
                open_cart_popup: openCartPopup,
                order_by: orderBy,
                order: order,
                nonce: bwFilteredPostWallAjax.nonce
            },
            success: function(response) {
                // Cache the response
                setCachedData(cacheKey, response);
                // Clear request queue
                delete ajaxRequestQueue[widgetId];
                // Process response
                processFilterResponse(response, widgetId, $grid, $wrapper, $filters, hadMasonryBefore);
            },
            error: function(xhr, status, error) {
                // Clear request queue
                delete ajaxRequestQueue[widgetId];

                // Don't show error if request was aborted
                if (status === 'abort') {
                    console.log('🚫 Request aborted for widget:', widgetId);
                    return;
                }

                console.error('❌ AJAX error:', error);
                $grid.html('<div class="bw-fpw-placeholder">Error loading posts.</div>');
                $wrapper.removeClass('bw-filtered-post-wall--loading');
                $filters.removeClass('loading');
                $filters.attr('data-has-posts', '0');
                $('.bw-fpw-filter-row--subcategories[data-widget-id="' + widgetId + '"], .bw-fpw-filter-row--tags[data-widget-id="' + widgetId + '"]').hide();
            }
        });
    }

    function processFilterResponse(response, widgetId, $grid, $wrapper, $filters, hadMasonryBefore) {
        if (response.success && response.data) {
            var hasPosts = !!response.data.has_posts;
            $filters.attr('data-has-posts', hasPosts ? '1' : '0');

            // OPTIMIZATION: Only destroy masonry if content changed
            if (hadMasonryBefore) {
                destroyGridInstance($grid);
            }

            // Replace grid content
            $grid.html(response.data.html);

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
                    var availableTags = Array.isArray(response.data.available_tags) ? response.data.available_tags.map(function(tag){ return parseInt(tag); }) : [];

                    if (availableTags.length) {
                        filterState[widgetId].tags = filterState[widgetId].tags.filter(function(tag){
                            return availableTags.indexOf(tag) > -1;
                        });
                    }

                    if (!hasPosts) {
                        filterState[widgetId].tags = [];
                        $tagOptions.removeClass('bw-fpw-animating').css('opacity', '0');
                        setTimeout(function() {
                            $tagOptions.empty();
                            $tagRow.hide();
                        }, 150);
                    } else if (response.data.tags_html) {
                        $tagOptions.removeClass('bw-fpw-animating').css('opacity', '0');
                        setTimeout(function() {
                            $tagOptions.each(function() {
                                $(this).html(response.data.tags_html);
                            });
                            $tagRow.css('opacity', '0').show();

                            setTimeout(function() {
                                $tagOptions.addClass('bw-fpw-animating').css('opacity', '1');
                                $tagRow.css('opacity', '1');
                            }, 50);

                            if (filterState[widgetId].tags.length) {
                                $tagOptions.find('.bw-fpw-tag-button').each(function(){
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
                                    setTimeout(function() {
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
                        setTimeout(function() {
                            $tagOptions.empty();
                            $tagRow.hide();
                        }, 150);
                    }
                }
            }

            // CRITICAL: Wait for images to load before reinitializing masonry
            withImagesLoaded($grid, function() {
                console.log('📸 Images loaded, reinitializing grid');

                // Reinitialize masonry after images are loaded
                initGrid($grid);

                // Fade in grid and apply staggered animation to posts
                setTimeout(function() {
                    $grid.css('opacity', '1');

                    // Apply staggered fade-in animation to posts
                    animatePostsStaggered($grid);
                }, 100);

                // Additional layout passes for stability
                setTimeout(function() {
                    var instance = $grid.data('masonry');
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 200);

                setTimeout(function() {
                    var instance = $grid.data('masonry');
                    if (instance && typeof instance.layout === 'function') {
                        instance.layout();
                        updateGridHeight($grid);
                    }
                }, 500);

                // Remove loading state after images loaded
                $wrapper.removeClass('bw-filtered-post-wall--loading');
                $filters.removeClass('loading');
            });

            console.log('✅ Posts filtered successfully');
        } else {
            console.error('❌ Filter response error:', response);
            var emptyStateHtml = '<div class="bw-fpw-empty-state">';
            emptyStateHtml += '<p class="bw-fpw-empty-message">No content available</p>';
            emptyStateHtml += '<button class="elementor-button bw-fpw-reset-filters" data-widget-id="' + widgetId + '">RESET FILTERS</button>';
            emptyStateHtml += '</div>';
            $grid.html(emptyStateHtml);

            // Remove loading state
            $wrapper.removeClass('bw-filtered-post-wall--loading');
            $filters.removeClass('loading');
            $filters.attr('data-has-posts', '0');
            $('.bw-fpw-filter-row--subcategories[data-widget-id="' + widgetId + '"], .bw-fpw-filter-row--tags[data-widget-id="' + widgetId + '"]').hide();
        }
    }

    function isInMobileMode(widgetId) {
        var $wrapper = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]').closest('.bw-filtered-post-wall-wrapper');
        return $wrapper.hasClass('bw-fpw-mobile-filters-enabled');
    }

    function initFilters() {
        $(document).on('click', '.bw-fpw-cat-button', function(e) {
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
            $('.bw-fpw-cat-button').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            }).removeClass('active');
            $button.addClass('active');

            // Update filter state
            filterState[widgetId].category = categoryId;
            filterState[widgetId].subcategories = [];
            filterState[widgetId].tags = [];

            // Reset tag visual state
            $('.bw-fpw-tag-button').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            }).removeClass('active');

            // Clear subcategory active states
            $('.bw-fpw-subcat-button').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            }).removeClass('active');

            console.log('📁 Category selected:', categoryId);

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
        $(document).on('click', '.bw-fpw-subcat-button', function(e) {
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

            console.log('📂 Subcategories selected:', subcats);

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
        $(document).on('click', '.bw-fpw-tag-button', function(e) {
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

            console.log('🏷️ Tags selected:', tags);

            // Filter posts only if NOT in mobile mode
            // In mobile mode, wait for "Show Results" button click
            if (!isInMobileMode(widgetId)) {
                filterPosts(widgetId);
            }
        });

        $(document).on('click', '.bw-fpw-mobile-filter-button', function(e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            openMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-filter-close', function(e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            closeMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-apply', function(e) {
            e.preventDefault();

            var widgetId = $(this).closest('.bw-fpw-mobile-filter').attr('data-widget-id');
            filterPosts(widgetId);
            closeMobilePanel(widgetId);
        });

        $(document).on('click', '.bw-fpw-mobile-dropdown-toggle', function() {
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
        $(document).on('click', '.bw-fpw-reset-filters', function(e) {
            e.preventDefault();

            var $button = $(this);
            var widgetId = $button.attr('data-widget-id');

            if (!widgetId) {
                console.error('❌ Widget ID not found for reset button');
                return;
            }

            initFilterState(widgetId);

            console.log('🔄 Resetting filters for widget:', widgetId);

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
            $('.bw-fpw-cat-button').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            }).removeClass('active');

            // Activate the default category button
            var $defaultCatButton = $('.bw-fpw-cat-button[data-category="' + defaultCategory + '"]').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            });
            $defaultCatButton.addClass('active');

            // Reset all subcategory buttons
            $('.bw-fpw-subcat-button').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            }).removeClass('active');

            // Reset all tag buttons
            $('.bw-fpw-tag-button').filter(function(){
                return $(this).closest('[data-widget-id]').attr('data-widget-id') === widgetId;
            }).removeClass('active');

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

            console.log('✅ Filters reset successfully');
        });
    }

    function openMobilePanel(widgetId) {
        var $panel = $('.bw-fpw-mobile-filter[data-widget-id="' + widgetId + '"] .bw-fpw-mobile-filter-panel');
        var $wrapper = $panel.closest('.bw-filtered-post-wall-wrapper');

        if ($panel.length) {
            $wrapper.addClass('bw-fpw-mobile-panel-open');
            $panel.attr('aria-hidden', 'false');
        }
    }

    function closeMobilePanel(widgetId) {
        var $panel = $('.bw-fpw-mobile-filter[data-widget-id="' + widgetId + '"] .bw-fpw-mobile-filter-panel');
        var $wrapper = $panel.closest('.bw-filtered-post-wall-wrapper');

        if ($panel.length) {
            $wrapper.removeClass('bw-fpw-mobile-panel-open');
            $panel.attr('aria-hidden', 'true');
        }
    }

    function toggleResponsiveFilters() {
        $('.bw-filtered-post-wall-wrapper').each(function() {
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

            initFilterState(widgetId);
            var $filters = $('.bw-fpw-filters[data-widget-id="' + widgetId + '"]');

            if ($filters.length) {
                var $activeCategory = $filters.find('.bw-fpw-cat-button.active').first();
                if ($activeCategory.length) {
                    filterState[widgetId].category = $activeCategory.attr('data-category') || 'all';
                }

                var initialSubcats = [];
                $filters.find('.bw-fpw-subcat-button.active').each(function(){
                    var id = parseInt($(this).attr('data-subcategory'));
                    if (!isNaN(id)) {
                        initialSubcats.push(id);
                    }
                });
                filterState[widgetId].subcategories = initialSubcats;

                var initialTags = [];
                $filters.find('.bw-fpw-tag-button.active').each(function(){
                    var id = parseInt($(this).attr('data-tag'));
                    if (!isNaN(id)) {
                        initialTags.push(id);
                    }
                });
                filterState[widgetId].tags = initialTags;
            }
            initGrid($grid);

            // Apply staggered animation on initial load
            withImagesLoaded($grid, function() {
                setTimeout(function() {
                    animatePostsStaggered($grid);
                }, 100);
            });
        });
    }

    $(function () {
        initWidget($(document));
        initFilters();
        toggleResponsiveFilters();

        var resizeTimer;
        $(window).on('resize orientationchange', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                toggleResponsiveFilters();
            }, 150);
        });
    });

    // Window resize handler
    var resizeTimeout;
    var lastDeviceByGrid = {};

    $(window).on('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            $('.bw-fpw-grid.bw-fpw-initialized').each(function () {
                var $grid = $(this);
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
        }, 150);
    });

    // ============================================
    // ELEMENTOR INTEGRATION
    // ============================================

    var hooksRegistered = false;
    var FilteredPostWallHandlerClass;

    function addElementorHandler($scope) {
        if (!FilteredPostWallHandlerClass) {
            if (
                typeof elementorModules === 'undefined' ||
                !elementorModules.frontend ||
                !elementorModules.frontend.handlers ||
                !elementorModules.frontend.handlers.Base
            ) {
                initWidget($scope);
                return;
            }

            FilteredPostWallHandlerClass = elementorModules.frontend.handlers.Base.extend({
                onInit: function () {
                    elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);

                    var self = this;

                    setTimeout(function() {
                        initWidget(self.$element);
                    }, 50);
                },

                onElementChange: function (settingKey) {
                    var self = this;
                    var $grid = this.$element.find('.bw-fpw-grid');

                    if (!$grid.length) {
                        return;
                    }

                    if (this.layoutTimeout) {
                        clearTimeout(this.layoutTimeout);
                    }

                    var needsFullReinit = settingKey && (
                        settingKey.indexOf('posts_per_page') !== -1 ||
                        settingKey.indexOf('columns') !== -1 ||
                        settingKey.indexOf('gap') !== -1 ||
                        settingKey.indexOf('order') !== -1
                    );

                    if (needsFullReinit) {
                        destroyGridInstance($grid);
                    }

                    this.layoutTimeout = setTimeout(function () {
                        if (needsFullReinit) {
                            initGrid($grid);

                            setTimeout(function() {
                                layoutGrid($grid, false);
                            }, 200);
                        } else {
                            layoutGrid($grid, false);
                        }
                    }, 150);
                },

                onDestroy: function () {
                    var $grid = this.$element.find('.bw-fpw-grid');

                    if (this.layoutTimeout) {
                        clearTimeout(this.layoutTimeout);
                    }

                    if ($grid.length) {
                        $grid.each(function () {
                            destroyGridInstance($(this));
                        });
                    }

                    elementorModules.frontend.handlers.Base.prototype.onDestroy.apply(this, arguments);
                }
            });
        }

        if (
            FilteredPostWallHandlerClass &&
            elementorFrontend.elementsHandler &&
            typeof elementorFrontend.elementsHandler.addHandler === 'function'
        ) {
            elementorFrontend.elementsHandler.addHandler(FilteredPostWallHandlerClass, { $element: $scope });
            return;
        }

        initWidget($scope);
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

        elementorFrontend.hooks.addAction('frontend/element_ready/bw-filtered-post-wall.default', addElementorHandler);
    }

    registerElementorHooks();
    $(window).on('elementor/frontend/init', registerElementorHooks);

    // Detect if we're in Elementor editor
    function isElementorEditor() {
        return (typeof elementorFrontend !== 'undefined' &&
                elementorFrontend.isEditMode &&
                elementorFrontend.isEditMode()) ||
               (typeof elementor !== 'undefined');
    }

    // Enhanced initialization for editor
    if (isElementorEditor()) {
        var editorResizeTimeout;
        $(window).off('resize.bwFPW').on('resize.bwFPW', function () {
            clearTimeout(editorResizeTimeout);
            editorResizeTimeout = setTimeout(function() {
                $('.bw-fpw-grid.bw-fpw-initialized').each(function () {
                    var $grid = $(this);
                    setItemWidths($grid);
                    layoutGrid($grid, false);
                    updateGridHeight($grid);
                });
            }, 150);
        });
    }

})(jQuery);
