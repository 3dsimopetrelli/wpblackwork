(function ($) {
    'use strict';

    var settings = window.bwSlickSliderAdmin || {};
    var ajaxUrl = '';
    var nonce = settings.nonce || '';

    function resolveAjaxUrl() {
        if (settings.ajaxUrl) {
            return settings.ajaxUrl;
        }

        if (typeof window.ajaxurl !== 'undefined' && window.ajaxurl) {
            return window.ajaxurl;
        }

        if (window.elementorCommon && window.elementorCommon.config && window.elementorCommon.config.ajaxurl) {
            return window.elementorCommon.config.ajaxurl;
        }

        return '';
    }

    function ensureAjaxUrl() {
        ajaxUrl = resolveAjaxUrl();
        return ajaxUrl;
    }

    var selectors = {
        parent: 'select[data-setting="product_cat_parent"]',
        child: 'select[data-setting="product_cat_child"]'
    };

    ensureAjaxUrl();

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

    function fetchChildCategories(parentId, callback) {
        if (!ensureAjaxUrl()) {
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

    function setupPanelControls($context, parentId, childValues) {
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
                return;
            }

            setTimeout(function () {
                initializePanel(panel, model, view, attempt + 1);
            }, 150);

            return;
        }

        setupPanelControls($context, parentId, childValues);
    }

    $(document).on('change', selectors.parent, handleParentChange);

    $(document).ready(function () {
        $(selectors.child).each(function () {
            var $child = $(this);
            if (!$child.prop('disabled')) {
                $child.prop('disabled', !$child.val());
            }
        });

    });

    $(window).on('elementor:init', function () {
        if (window.elementor && window.elementor.hooks) {
            window.elementor.hooks.addAction('panel/open_editor/widget/bw-slick-slider', function (panel, model, view) {
                initializePanel(panel, model, view);
            });
        }
    });
})(jQuery);
