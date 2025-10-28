(function ($) {
    'use strict';

    var settings = window.bwSlickSliderAdmin || {};
    var ajaxUrl = settings.ajaxUrl || window.ajaxurl || '';
    var nonce = settings.nonce || '';
    var postsNonce = settings.postsNonce || '';

    var selectors = {
        parent: 'select[data-setting="product_cat_parent"]',
        child: 'select[data-setting="product_cat_child"]',
        specificPosts: 'select[data-setting="specific_posts"]',
        postType: 'select[data-setting="content_type"]'
    };

    function getSettingValue(model, setting) {
        if (!model) {
            return '';
        }

        if (typeof model.getSetting === 'function') {
            return model.getSetting(setting);
        }

        var settingsModel = model.get && model.get('settings');
        if (settingsModel && typeof settingsModel.get === 'function') {
            return settingsModel.get(setting);
        }

        return '';
    }

    function normalizeIds(value) {
        if (Array.isArray(value)) {
            return value
                .map(function (item) { return String(item); })
                .filter(function (item) { return item !== ''; });
        }

        if (typeof value === 'number') {
            return [String(value)];
        }

        if (typeof value === 'string') {
            return value
                .split(',')
                .map(function (item) { return item.trim(); })
                .filter(function (item) { return item !== ''; });
        }

        return [];
    }

    function getChildSelect($parentSelect) {
        var $controlsWrapper = $parentSelect.closest('.elementor-control').parent();

        if (!$controlsWrapper.length) {
            $controlsWrapper = $parentSelect.closest('.elementor-control');
        }

        return $controlsWrapper.find(selectors.child).first();
    }

    function resetChildSelect($childSelect) {
        if (!$childSelect.length) {
            return;
        }

        $childSelect.empty();
        $childSelect.prop('disabled', true);
        $childSelect.val([]);
        $childSelect.trigger('change');
    }

    function populateChildSelect($childSelect, options, selectedValues) {
        if (!$childSelect.length) {
            return;
        }

        $childSelect.empty();

        var selected = Array.isArray(selectedValues) ? selectedValues.map(String) : [];
        var hasOptions = false;

        $.each(options, function (value, label) {
            hasOptions = true;
            var stringValue = String(value);
            var $option = $('<option></option>').attr('value', stringValue).text(label);

            if (selected.indexOf(stringValue) !== -1) {
                $option.prop('selected', true);
            }

            $childSelect.append($option);
        });

        $childSelect.prop('disabled', !hasOptions);
        $childSelect.trigger('change');
    }

    function applySelectedPostsOptions($select, options, selectedIds) {
        if (!$select.length) {
            return;
        }

        var selected = Array.isArray(selectedIds) ? selectedIds.map(String) : [];

        if (!selected.length) {
            return;
        }

        var existing = {};

        $select.find('option').each(function () {
            var $option = $(this);
            existing[String($option.attr('value'))] = $option;
        });

        selected.forEach(function (id) {
            var value = String(id);
            var label = options && (options[value] || options[id]) ? (options[value] || options[id]) : value;

            if (existing[value]) {
                existing[value].prop('selected', true);

                if (label !== value) {
                    existing[value].text(label);
                }
            } else {
                var $option = $('<option></option>')
                    .attr('value', value)
                    .text(label)
                    .prop('selected', true);

                $select.append($option);
                existing[value] = $option;
            }
        });

        $select.val(selected);
    }

    function getPostTypeValue($context) {
        var $postTypeSelect;

        if ($context && $context.length) {
            $postTypeSelect = $context.find(selectors.postType).first();
        } else {
            $postTypeSelect = $(selectors.postType).first();
        }

        if ($postTypeSelect && $postTypeSelect.length) {
            var value = $postTypeSelect.val();
            return value ? value : 'any';
        }

        return 'any';
    }

    function fetchPostsByIds(ids, postType, callback) {
        if (!ajaxUrl || !postsNonce || !Array.isArray(ids) || !ids.length) {
            callback({});
            return;
        }

        $.post(ajaxUrl, {
            action: 'bw_get_posts_by_ids',
            ids: ids,
            post_type: postType || 'any',
            nonce: postsNonce
        })
            .done(function (response) {
                if (response && response.success && response.data) {
                    callback(response.data);
                } else {
                    callback({});
                }
            })
            .fail(function () {
                callback({});
            });
    }

    function withSelect2(callback, attempt) {
        if (!ajaxUrl) {
            return;
        }

        if (typeof attempt === 'undefined') {
            attempt = 0;
        }

        if (typeof $.fn.select2 === 'function') {
            callback();
            return;
        }

        if (attempt > 10) {
            return;
        }

        setTimeout(function () {
            withSelect2(callback, attempt + 1);
        }, 150);
    }

    function initializeSpecificPostsSelect($context, model) {
        var savedIds = model ? normalizeIds(getSettingValue(model, 'specific_posts')) : [];
        var modelPostType = model ? getSettingValue(model, 'content_type') : '';
        var initialPostType = modelPostType ? modelPostType : getPostTypeValue($context);

        var runInitialization = function () {
            var $elements = $context && $context.length ? $context.find(selectors.specificPosts) : $(selectors.specificPosts);

            $elements = $elements.filter(function () {
                var $element = $(this);

                return !$element.hasClass('bw-specific-posts-initialized') &&
                    !$element.hasClass('select2-hidden-accessible') &&
                    !$element.data('bwSpecificPostsInitializing');
            });

            if (!$elements.length) {
                return;
            }

            $elements.each(function () {
                var $select = $(this);

                $select.data('bwSpecificPostsInitializing', true);

                var initializeSelect2 = function () {
                    $select.select2({
                        width: '100%',
                        allowClear: true,
                        placeholder: $select.attr('placeholder') || '',
                        ajax: {
                            url: ajaxUrl,
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    action: 'bw_search_posts',
                                    q: params.term || '',
                                    post_type: getPostTypeValue($context)
                                };
                            },
                            processResults: function (data) {
                                if (data && data.results) {
                                    return { results: data.results };
                                }

                                return { results: [] };
                            },
                            cache: true
                        },
                        minimumInputLength: 2
                    });

                    $select.removeData('bwSpecificPostsInitializing');
                    $select.addClass('bw-specific-posts-initialized');
                };

                if (savedIds.length) {
                    fetchPostsByIds(savedIds, initialPostType || 'any', function (options) {
                        applySelectedPostsOptions($select, options, savedIds);
                        initializeSelect2();
                    });
                } else {
                    initializeSelect2();
                }
            });
        };

        withSelect2(runInitialization);
    }

    function fetchChildCategories(parentId, callback) {
        if (!ajaxUrl) {
            callback({});
            return;
        }

        $.post(ajaxUrl, {
            action: 'bw_get_child_categories',
            parent_id: parentId,
            nonce: nonce
        })
            .done(function (response) {
                if (response && response.success && response.data) {
                    callback(response.data);
                } else {
                    callback({});
                }
            })
            .fail(function () {
                callback({});
            });
    }

    function handleParentChange(event) {
        var $parent = $(event.currentTarget);
        var parentId = $parent.val();
        var $childSelect = getChildSelect($parent);

        if (!parentId) {
            resetChildSelect($childSelect);
            return;
        }

        resetChildSelect($childSelect);

        fetchChildCategories(parentId, function (options) {
            populateChildSelect($childSelect, options, []);
        });
    }

    function getPanelContext(panel, view) {
        if (panel && panel.$el) {
            return panel.$el;
        }

        if (view && view.$el) {
            return view.$el;
        }

        return $();
    }

    function setupPanelControls($context, parentId, childValues, model) {
        var $childSelect = $context.find(selectors.child).first();

        if ($childSelect.length) {
            if (parentId) {
                fetchChildCategories(parentId, function (options) {
                    populateChildSelect($childSelect, options, childValues);
                });
            } else {
                resetChildSelect($childSelect);
            }
        }

        initializeSpecificPostsSelect($context, model);
    }

    function initializePanel(panel, model, view, attempt) {
        if (!model || model.get('widgetType') !== 'bw-slick-slider') {
            return;
        }

        if (typeof attempt === 'undefined') {
            attempt = 0;
        }

        var parentId = getSettingValue(model, 'product_cat_parent');
        var childValues = getSettingValue(model, 'product_cat_child');

        if (!Array.isArray(childValues)) {
            childValues = childValues ? [childValues] : [];
        }

        var $context = getPanelContext(panel, view);

        if (!$context.length || (!$context.find(selectors.parent).length && !$context.find(selectors.child).length)) {
            if (attempt > 10) {
                initializeSpecificPostsSelect($(document), model);
                return;
            }

            setTimeout(function () {
                initializePanel(panel, model, view, attempt + 1);
            }, 150);

            return;
        }

        setupPanelControls($context, parentId, childValues, model);
    }

    $(document).on('change', selectors.parent, handleParentChange);

    $(document).ready(function () {
        $(selectors.child).each(function () {
            var $child = $(this);
            if (!$child.prop('disabled')) {
                $child.prop('disabled', !$child.val());
            }
        });

        initializeSpecificPostsSelect($(document));
    });

    $(window).on('elementor:init', function () {
        if (window.elementor && window.elementor.hooks) {
            window.elementor.hooks.addAction('panel/open_editor/widget/bw-slick-slider', function (panel, model, view) {
                initializePanel(panel, model, view);
            });
        }
    });
})(jQuery);
