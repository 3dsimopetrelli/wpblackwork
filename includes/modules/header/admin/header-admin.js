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
            $fieldWrap.find('.bw-header-media-preview').html('<img src="' + attachment.url + '" alt="" class="bw-header-media-preview-image" />');
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
        const target = $(this).data('target') || $(this).attr('href');
        if (!target) {
            return;
        }

        if (typeof target !== 'string' || target.charAt(0) !== '#') {
            return;
        }

        e.preventDefault();

        $('#bw-header-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.bw-header-tab-panel').hide().removeClass('is-active');
        $(target).show().addClass('is-active');

        if (window.history && typeof window.history.replaceState === 'function') {
            const href = $(this).attr('href');
            if (href) {
                window.history.replaceState(null, document.title, href);
            }
        }
    });
})(jQuery);
