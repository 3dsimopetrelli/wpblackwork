(function ($) {
    'use strict';

    function csvFromArray(arr) {
        if (!Array.isArray(arr) || !arr.length) {
            return '';
        }
        return arr.join(',');
    }

    function clearQuickEdit(row) {
        row.find('.bw-tbl-qe-priority').val('10');
        row.find('.bw-tbl-qe-sp-inc-ids, .bw-tbl-qe-sp-exc-ids, .bw-tbl-qe-post-inc-ids, .bw-tbl-qe-post-exc-ids, .bw-tbl-qe-page-inc-ids, .bw-tbl-qe-page-exc-ids').val('');
        row.find('.bw-tbl-qe-pa-inc-shop, .bw-tbl-qe-pa-exc-shop, .bw-tbl-qe-arc-inc-blog, .bw-tbl-qe-arc-exc-blog').prop('checked', false);
        row.find('[id^="bw-tbl-qe-"][multiple]').val([]);
        row.find('.bw-tbl-qe-priority-touched').val('0');
        row.find('.bw-tbl-qe-rules-touched').val('0');
    }

    function toggleSection(row, section) {
        section = String(section || '');
        if (!section) {
            return;
        }

        row.find('.bw-tbl-qe-section').hide();
        row.find('.bw-tbl-qe-section').each(function () {
            if (String($(this).data('section') || '') === section) {
                $(this).show();
            }
        });
    }

    function fillQuickEdit(row, data) {
        data = data || {};
        var type = String(data.type || 'footer');

        row.find('.bw-tbl-qe-type').val(type);
        row.find('.bw-tbl-qe-type-label').text(String(data.type_label || type));
        row.find('.bw-tbl-qe-priority').val(data.priority || 10);

        var sp = data.single_product || {};
        row.find('.bw-tbl-qe-sp-inc-cat').val(sp.include_categories || []);
        row.find('.bw-tbl-qe-sp-inc-ids').val(csvFromArray(sp.include_ids || []));
        row.find('.bw-tbl-qe-sp-exc-cat').val(sp.exclude_categories || []);
        row.find('.bw-tbl-qe-sp-exc-ids').val(csvFromArray(sp.exclude_ids || []));

        var pa = data.product_archive || {};
        row.find('.bw-tbl-qe-pa-inc-shop').prop('checked', Number(pa.include_shop || 0) === 1);
        row.find('.bw-tbl-qe-pa-inc-cat').val(pa.include_categories || []);
        row.find('.bw-tbl-qe-pa-inc-tag').val(pa.include_tags || []);
        row.find('.bw-tbl-qe-pa-exc-shop').prop('checked', Number(pa.exclude_shop || 0) === 1);
        row.find('.bw-tbl-qe-pa-exc-cat').val(pa.exclude_categories || []);
        row.find('.bw-tbl-qe-pa-exc-tag').val(pa.exclude_tags || []);

        var post = data.single_post || {};
        row.find('.bw-tbl-qe-post-inc-cat').val(post.include_categories || []);
        row.find('.bw-tbl-qe-post-inc-ids').val(csvFromArray(post.include_ids || []));
        row.find('.bw-tbl-qe-post-exc-cat').val(post.exclude_categories || []);
        row.find('.bw-tbl-qe-post-exc-ids').val(csvFromArray(post.exclude_ids || []));

        var page = data.single_page || {};
        row.find('.bw-tbl-qe-page-inc-ids').val(csvFromArray(page.include_ids || []));
        row.find('.bw-tbl-qe-page-exc-ids').val(csvFromArray(page.exclude_ids || []));

        var arc = data.archive || {};
        row.find('.bw-tbl-qe-arc-inc-blog').prop('checked', Number(arc.include_blog || 0) === 1);
        row.find('.bw-tbl-qe-arc-inc-cat').val(arc.include_categories || []);
        row.find('.bw-tbl-qe-arc-exc-blog').prop('checked', Number(arc.exclude_blog || 0) === 1);
        row.find('.bw-tbl-qe-arc-exc-cat').val(arc.exclude_categories || []);

        var section = String(data.last_section || '');
        if (!section) {
            section = String(data.first_non_empty_section || '');
        }
        if (!section) {
            section = 'single_product';
        }
        row.find('.bw-tbl-qe-section-select').val(section);

        row.find('.bw-tbl-qe-priority-touched').val('0');
        row.find('.bw-tbl-qe-rules-touched').val('0');
        toggleSection(row, section);
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

    $(document).on('input', '.bw-tbl-qe-priority', function () {
        $(this).closest('tr.inline-editor').find('.bw-tbl-qe-priority-touched').val('1');
    });

    $(document).on(
        'change input',
        '.bw-tbl-qe-sp-inc-cat, .bw-tbl-qe-sp-inc-ids, .bw-tbl-qe-sp-exc-cat, .bw-tbl-qe-sp-exc-ids, .bw-tbl-qe-pa-inc-shop, .bw-tbl-qe-pa-inc-cat, .bw-tbl-qe-pa-inc-tag, .bw-tbl-qe-pa-exc-shop, .bw-tbl-qe-pa-exc-cat, .bw-tbl-qe-pa-exc-tag, .bw-tbl-qe-post-inc-cat, .bw-tbl-qe-post-inc-ids, .bw-tbl-qe-post-exc-cat, .bw-tbl-qe-post-exc-ids, .bw-tbl-qe-page-inc-ids, .bw-tbl-qe-page-exc-ids, .bw-tbl-qe-arc-inc-blog, .bw-tbl-qe-arc-inc-cat, .bw-tbl-qe-arc-exc-blog, .bw-tbl-qe-arc-exc-cat',
        function () {
            $(this).closest('tr.inline-editor').find('.bw-tbl-qe-rules-touched').val('1');
        }
    );

    $(document).on('change', '.bw-tbl-qe-section-select', function () {
        var row = $(this).closest('tr.inline-editor');
        toggleSection(row, $(this).val());
    });
})(jQuery);
