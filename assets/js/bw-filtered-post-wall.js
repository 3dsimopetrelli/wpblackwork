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

    function initFilterState(widgetId) {
        if (!filterState[widgetId]) {
            filterState[widgetId] = {
                category: 'all',
                subcategories: [],
                tags: []
            };
        }
    }

    function loadSubcategories(categoryId, $container, widgetId) {
        var $grid = $('.bw-fpw-grid[data-widget-id="' + widgetId + '"]');
        var postType = $grid.attr('data-post-type') || 'product';

        console.log('📂 Loading subcategories for category:', categoryId);

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
                if (response.success && response.data) {
                    var subcats = response.data;
                    var html = '';

                    $.each(subcats, function(index, subcat) {
                        html += '<button class="bw-fpw-filter-button bw-fpw-subcat-button" data-subcategory="' + subcat.term_id + '">';
                        html += subcat.name + ' (' + subcat.count + ')';
                        html += '</button>';
                    });

                    $container.html(html);

                    // Show subcategories section
                    $('.bw-fpw-filter-subcategories').slideDown(300);
                } else {
                    $container.html('<p class="bw-fpw-no-subcats">No subcategories found</p>');
                    $('.bw-fpw-filter-subcategories').slideUp(300);
                }
            },
            error: function() {
                console.error('❌ Error loading subcategories');
                $container.html('<p class="bw-fpw-error">Error loading subcategories</p>');
            }
        });
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

        // Show loading state
        $wrapper.addClass('bw-filtered-post-wall--loading');
        $filters.addClass('loading');

        // Destroy masonry before AJAX
        destroyGridInstance($grid);

        $.ajax({
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
                if (response.success && response.data) {
                    // Replace grid content
                    $grid.html(response.data.html);

                    // CRITICAL: Wait for images to load before reinitializing masonry
                    withImagesLoaded($grid, function() {
                        console.log('📸 Images loaded, reinitializing grid');

                        // Reinitialize masonry after images are loaded
                        initGrid($grid);

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
                    $grid.html('<div class="bw-fpw-placeholder">No posts found.</div>');

                    // Remove loading state
                    $wrapper.removeClass('bw-filtered-post-wall--loading');
                    $filters.removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error:', error);
                $grid.html('<div class="bw-fpw-placeholder">Error loading posts.</div>');
                $wrapper.removeClass('bw-filtered-post-wall--loading');
                $filters.removeClass('loading');
            }
        });
    }

    function initFilters() {
        $(document).on('click', '.bw-fpw-cat-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $filters = $button.closest('.bw-fpw-filters');
            var widgetId = $filters.attr('data-widget-id');
            var categoryId = $button.attr('data-category');

            initFilterState(widgetId);

            // Update active state
            $filters.find('.bw-fpw-cat-button').removeClass('active');
            $button.addClass('active');

            // Update filter state
            filterState[widgetId].category = categoryId;
            filterState[widgetId].subcategories = [];

            // Clear subcategory active states
            $filters.find('.bw-fpw-subcat-button').removeClass('active');

            console.log('📁 Category selected:', categoryId);

            if (categoryId === 'all') {
                // Hide subcategories section
                $('.bw-fpw-filter-subcategories').slideUp(300);
            } else {
                // Load subcategories
                var $subcatContainer = $filters.find('.bw-fpw-subcategories-container');
                loadSubcategories(categoryId, $subcatContainer, widgetId);
            }

            // Filter posts
            filterPosts(widgetId);
        });

        // Subcategory filter
        $(document).on('click', '.bw-fpw-subcat-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $filters = $button.closest('.bw-fpw-filters');
            var widgetId = $filters.attr('data-widget-id');
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

            // Filter posts
            filterPosts(widgetId);
        });

        // Tag filter
        $(document).on('click', '.bw-fpw-tag-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $filters = $button.closest('.bw-fpw-filters');
            var widgetId = $filters.attr('data-widget-id');
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

            // Filter posts
            filterPosts(widgetId);
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
            initGrid($grid);
        });
    }

    $(function () {
        initWidget($(document));
        initFilters();
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
