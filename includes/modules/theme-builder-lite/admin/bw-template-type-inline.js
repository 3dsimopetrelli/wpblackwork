(function ($) {
    'use strict';
    var cfg = window.bwTblInlineType || {};

    function setStatus($select, message, isError) {
        var $status = $select.siblings('.bw-tbl-inline-type-status');
        $status.text(message || '');
        $status.css('color', isError ? '#b32d2e' : '#2271b1');
    }

    function resetSelect($select, value) {
        if (typeof value === 'string') {
            $select.val(value);
        }
        $select.prop('disabled', false);
    }

    $(document).on('change', '.bw-tbl-inline-type-select', function () {
        var $select = $(this);
        var originalValue = ($select.data('original') || $select.find('option:selected').val() || '').toString();
        var linked = ($select.data('linked') || '0').toString() === '1';
        var postId = parseInt($select.data('post-id'), 10);
        var newValue = ($select.val() || '').toString();

        if (!postId || !newValue || newValue === originalValue) {
            return;
        }

        if (linked && !window.confirm(cfg.confirmMessage || 'Continue?')) {
            $select.val(originalValue);
            return;
        }

        $select.prop('disabled', true);
        setStatus($select, cfg.saving || 'Saving...', false);

        $.ajax({
            url: cfg.ajaxUrl || '',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'bw_tbl_update_template_type',
                nonce: cfg.nonce || '',
                post_id: postId,
                template_type: newValue
            }
        }).done(function (response) {
            if (!response || !response.success) {
                resetSelect($select, originalValue);
                setStatus($select, cfg.error || 'Error', true);
                return;
            }

            $select.data('original', newValue);
            setStatus($select, cfg.saved || 'Saved', false);
            window.setTimeout(function () {
                window.location.reload();
            }, 350);
        }).fail(function () {
            resetSelect($select, originalValue);
            setStatus($select, cfg.error || 'Error', true);
        });
    });

    $(function () {
        $('.bw-tbl-inline-type-select').each(function () {
            var $select = $(this);
            $select.data('original', ($select.val() || '').toString());
        });
    });
})(jQuery);
