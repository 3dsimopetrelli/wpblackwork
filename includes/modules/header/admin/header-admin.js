(function ($) {
    'use strict';

    function openMediaFrame($fieldWrap) {
        if (typeof wp === 'undefined' || !wp.media) {
            // eslint-disable-next-line no-console
            console.error('WordPress media library is not available on this page.');
            return;
        }

        const frame = wp.media({
            title: 'Select Media',
            button: { text: 'Use this media' },
            multiple: false,
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $fieldWrap.find('.bw-header-media-id').val(attachment.id);
            $fieldWrap.find('.bw-header-media-preview').html('<img src="' + attachment.url + '" alt="" style="max-width:80px;max-height:80px;display:block;" />');
            $fieldWrap.find('.bw-header-media-remove').prop('disabled', false);
        });

        frame.open();
    }

    $(document).on('click', '.bw-header-media-upload', function (e) {
        e.preventDefault();
        openMediaFrame($(this).closest('td'));
    });

    $(document).on('click', '.bw-header-media-remove', function (e) {
        e.preventDefault();
        const $fieldWrap = $(this).closest('td');
        $fieldWrap.find('.bw-header-media-id').val('0');
        $fieldWrap.find('.bw-header-media-preview').empty();
        $(this).prop('disabled', true);
    });

    $(document).on('click', '#bw-header-tabs .nav-tab', function (e) {
        e.preventDefault();

        const target = $(this).attr('href');
        if (!target) {
            return;
        }

        $('#bw-header-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.bw-header-tab-panel').hide().removeClass('is-active');
        $(target).show().addClass('is-active');
    });
})(jQuery);
