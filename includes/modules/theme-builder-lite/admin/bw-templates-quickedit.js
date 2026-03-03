(function ($) {
    'use strict';

    function hasSelectEnhancer() {
        return typeof $.fn.selectWoo === 'function' || typeof $.fn.select2 === 'function';
    }

    function initSearchableTaxonomySelects(row) {
        if (!hasSelectEnhancer()) {
            return;
        }

        row.find('.bw-tbl-qe-taxonomy-select').each(function () {
            var select = $(this);
            var currentValue = select.val();

            try {
                if (select.hasClass('select2-hidden-accessible')) {
                    if (typeof select.selectWoo === 'function') {
                        select.selectWoo('destroy');
                    } else if (typeof select.select2 === 'function') {
                        select.select2('destroy');
                    }
                }
            } catch (e) {
                // Fail-open: keep native multiselect usable.
            }

            var config = {
                width: '100%',
                closeOnSelect: false,
                placeholder: String(select.attr('data-placeholder') || ''),
                allowClear: false
            };

            if (typeof select.selectWoo === 'function') {
                select.selectWoo(config);
            } else if (typeof select.select2 === 'function') {
                select.select2(config);
            }

            if (Array.isArray(currentValue)) {
                select.val(currentValue).trigger('change');
            }
        });
    }

    function csvFromArray(arr) {
        if (!Array.isArray(arr) || !arr.length) {
            return '';
        }
        return arr.join(',');
    }

    function clearQuickEdit(row) {
        row.find('.bw-tbl-qe-sp-inc-ids, .bw-tbl-qe-sp-exc-ids').val('');
        row.find('.bw-tbl-qe-sp-inc-cat, .bw-tbl-qe-sp-exc-cat').val([]);
        row.find('.bw-tbl-qe-rules-touched').val('0');
    }

    function fillQuickEdit(row, data) {
        data = data || {};
        var type = String(data.type || 'footer');

        row.find('.bw-tbl-qe-type').val(type);
        row.find('.bw-tbl-qe-type-label').text(String(data.type_label || type));

        var sp = data.single_product || {};
        row.find('.bw-tbl-qe-sp-inc-cat').val(sp.include_categories || []);
        row.find('.bw-tbl-qe-sp-inc-ids').val(csvFromArray(sp.include_ids || []));
        row.find('.bw-tbl-qe-sp-exc-cat').val(sp.exclude_categories || []);
        row.find('.bw-tbl-qe-sp-exc-ids').val(csvFromArray(sp.exclude_ids || []));
        row.find('.bw-tbl-qe-rules-touched').val('0');
        initSearchableTaxonomySelects(row);
    }

    function getPostId(id) {
        if (typeof id === 'object' && id !== null) {
            id = typeof id.id !== 'undefined' ? id.id : id;
        }

        if (typeof id === 'string') {
            id = id.replace('post-', '');
        }

        return parseInt(id, 10) || 0;
    }

    var originalEdit = inlineEditPost.edit;
    inlineEditPost.edit = function (id) {
        originalEdit.apply(this, arguments);

        var postId = getPostId(id);
        if (!postId) {
            return;
        }

        var postRow = $('#post-' + postId);
        var quickRow = $('#edit-' + postId);
        if (!postRow.length || !quickRow.length) {
            return;
        }

        clearQuickEdit(quickRow);
        initSearchableTaxonomySelects(quickRow);

        var rawData = postRow.find('.bw-tbl-qe-data').attr('data-bw-qe');
        if (!rawData) {
            return;
        }

        var parsed = {};
        try {
            parsed = JSON.parse(rawData);
        } catch (e) {
            parsed = {};
        }

        fillQuickEdit(quickRow, parsed);
    };

    $(document).on(
        'change input',
        '.bw-tbl-qe-sp-inc-cat, .bw-tbl-qe-sp-inc-ids, .bw-tbl-qe-sp-exc-cat, .bw-tbl-qe-sp-exc-ids',
        function () {
            $(this).closest('tr.inline-editor').find('.bw-tbl-qe-rules-touched').val('1');
        }
    );
})(jQuery);
