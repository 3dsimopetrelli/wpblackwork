(function ($) {
    'use strict';

    function getConfig() {
        return (typeof window.bwProductLabelsAdmin !== 'undefined' && window.bwProductLabelsAdmin) ? window.bwProductLabelsAdmin : {};
    }

    function updateSortableInput($list) {
        var targetSelector = $list.attr('data-target-input');
        if (!targetSelector) {
            return;
        }

        var orderedKeys = [];

        $list.children('[data-key]').each(function () {
            var key = String($(this).attr('data-key') || '').trim();

            if (key) {
                orderedKeys.push(key);
            }
        });

        $(targetSelector).val(orderedKeys.join(','));
    }

    function updateEmptyState($list) {
        var emptyText = String($list.attr('data-empty-text') || '').trim();

        $list.find('.bw-product-labels-sortable__empty').remove();

        if (!$list.children('.bw-product-labels-sortable__item').length && emptyText) {
            $list.append(
                $('<li class="bw-product-labels-sortable__empty" />').text(emptyText)
            );
        }
    }

    function initSortableLists() {
        $('.bw-product-labels-sortable').each(function () {
            var $list = $(this);

            if (!$list.data('ui-sortable')) {
                $list.sortable({
                    items: '> .bw-product-labels-sortable__item',
                    axis: 'y',
                    update: function () {
                        updateSortableInput($list);
                    }
                });
            }

            updateSortableInput($list);
            updateEmptyState($list);
        });
    }

    function getStaffOrderList() {
        return $('#bw-product-labels-staff-order');
    }

    function syncStaffHiddenInputs() {
        var $list = getStaffOrderList();
        var orderedIds = [];

        $list.children('.bw-product-labels-sortable__item').each(function () {
            var productId = String($(this).attr('data-key') || '').trim();

            if (productId) {
                orderedIds.push(productId);
            }
        });

        $('#bw-product-labels-staff-product-ids').val(orderedIds.join(','));
        $('#bw-product-labels-staff-manual-order').val(orderedIds.join(','));
    }

    function syncStaffOrderListFromSelect() {
        var $select = $('#bw-product-labels-staff-products');
        var $list = getStaffOrderList();
        var optionMap = {};
        var existingOrder = [];

        if (!$select.length || !$list.length) {
            return;
        }

        $select.find('option:selected').each(function () {
            var $option = $(this);
            var productId = String($option.val() || '').trim();

            if (productId) {
                optionMap[productId] = String($option.text() || '').trim();
            }
        });

        $list.children('.bw-product-labels-sortable__item').each(function () {
            var productId = String($(this).attr('data-key') || '').trim();

            if (productId && optionMap[productId]) {
                existingOrder.push(productId);
            }
        });

        Object.keys(optionMap).forEach(function (productId) {
            if (existingOrder.indexOf(productId) === -1) {
                existingOrder.push(productId);
            }
        });

        $list.empty();

        existingOrder.forEach(function (productId) {
            $list.append(
                $('<li class="bw-product-labels-sortable__item" />')
                    .attr('data-key', productId)
                    .append('<span class="dashicons dashicons-menu-alt2" aria-hidden="true"></span>')
                    .append($('<span />').text(optionMap[productId]))
            );
        });

        updateEmptyState($list);
        syncStaffHiddenInputs();
    }

    function initStaffProductSelect() {
        var config = getConfig();
        var $select = $('#bw-product-labels-staff-products');

        if (!$select.length || typeof $.fn.select2 === 'undefined') {
            return;
        }

        $select.select2({
            ajax: {
                url: config.ajaxUrl || window.ajaxurl || '',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        action: 'bw_search_products',
                        nonce: config.nonce || ''
                    };
                },
                processResults: function (response) {
                    if (response && response.success === false) {
                        return { results: [] };
                    }

                    return { results: Array.isArray(response) ? response : [] };
                },
                cache: true
            },
            minimumInputLength: 2,
            width: '100%',
            placeholder: config.searchPlaceholder || ''
        });

        $select.on('change', syncStaffOrderListFromSelect);
        syncStaffOrderListFromSelect();
    }

    $(document).ready(function () {
        initSortableLists();
        initStaffProductSelect();

        if (getStaffOrderList().length && getStaffOrderList().data('ui-sortable')) {
            getStaffOrderList().sortable('option', 'update', function () {
                syncStaffHiddenInputs();
            });
        }
    });
})(jQuery);
