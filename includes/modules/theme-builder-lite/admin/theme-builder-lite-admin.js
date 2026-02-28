(function ($) {
    'use strict';

    function getRowTemplate() {
        return $('#tmpl-bw-tbl-font-row').html() || '';
    }

    function nextIndex() {
        var max = -1;
        $('#bw-tbl-fonts-table tbody tr').each(function () {
            var name = $(this).find('input[name*="[fonts]["]').first().attr('name') || '';
            var match = name.match(/\[fonts\]\[(\d+)\]/);
            if (match) {
                var value = parseInt(match[1], 10);
                if (!isNaN(value) && value > max) {
                    max = value;
                }
            }
        });

        return max + 1;
    }

    function buildRow() {
        var html = getRowTemplate();
        if (!html) {
            return $();
        }

        var index = nextIndex();
        html = html.replace(/\[99999\]/g, '[' + index + ']');

        return $(html);
    }

    $(document).on('click', '#bw-tbl-add-font-row', function (event) {
        event.preventDefault();
        var row = buildRow();
        if (!row.length) {
            return;
        }
        $('#bw-tbl-fonts-table tbody').append(row);
    });

    $(document).on('click', '.bw-tbl-remove-font-row', function (event) {
        event.preventDefault();
        var $rows = $('#bw-tbl-fonts-table tbody tr');
        if ($rows.length <= 1) {
            $(this).closest('tr').find('input[type="text"], input[type="url"]').val('');
            $(this).closest('tr').find('select').val('normal');
            return;
        }

        $(this).closest('tr').remove();
    });

    function extractExtension(url) {
        var clean = (url || '').split('?')[0].split('#')[0];
        var parts = clean.split('.');
        if (parts.length < 2) {
            return '';
        }

        return parts.pop().toLowerCase();
    }

    $(document).on('click', '.bw-tbl-media-select', function (event) {
        event.preventDefault();

        var $button = $(this);
        var format = ($button.data('format') || '').toString().toLowerCase();
        var $input = $button.siblings('input.bw-tbl-font-source');

        var frame = wp.media({
            title: 'Select font file',
            button: {
                text: 'Use this font'
            },
            multiple: false,
            library: {
                type: ''
            }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment && attachment.url ? attachment.url : '';
            var extension = extractExtension(url);

            if (format && extension !== format) {
                window.alert('Selected file must be .' + format);
                return;
            }

            $input.val(url).trigger('change');
        });

        frame.open();
    });
})(jQuery);
