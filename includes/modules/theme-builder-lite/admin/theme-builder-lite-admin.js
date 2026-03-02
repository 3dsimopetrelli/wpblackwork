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

    function setTab(tabKey) {
        var $tabs = $('#bw-tbl-tabs .nav-tab');
        var $panels = $('.bw-tbl-tab-panel');

        $tabs.removeClass('nav-tab-active');
        $panels.hide().removeClass('is-active');

        $tabs.filter('[data-bw-tbl-tab="' + tabKey + '"]').addClass('nav-tab-active');
        $panels.filter('[data-bw-tbl-panel="' + tabKey + '"]').show().addClass('is-active');
    }

    function syncFeatureSections() {
        var fontsEnabled = $('#bw-tbl-flag-custom-fonts').is(':checked');
        var footerEnabled = $('#bw-tbl-flag-footer-override').is(':checked');

        if (fontsEnabled) {
            $('#bw-tbl-fonts-controls').show();
        } else {
            $('#bw-tbl-fonts-controls').hide();
        }

        if (footerEnabled) {
            $('#bw-tbl-footer-controls').show();
        } else {
            $('#bw-tbl-footer-controls').hide();
        }
    }

    $(document).on('click', '#bw-tbl-tabs .nav-tab', function (event) {
        event.preventDefault();
        var tabKey = ($(this).data('bw-tbl-tab') || 'settings').toString();
        setTab(tabKey);
    });

    $(document).on('change', '#bw-tbl-flag-custom-fonts, #bw-tbl-flag-footer-override', function () {
        syncFeatureSections();
    });

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
        var libraryType = '';
        var modalTitle = 'Select font file';

        if (format === 'woff2') {
            libraryType = 'font/woff2';
            modalTitle = 'Select .woff2 font file';
        } else if (format === 'woff') {
            libraryType = 'font/woff';
            modalTitle = 'Select .woff font file';
        }

        var frame = wp.media({
            title: modalTitle,
            button: {
                text: 'Use this font'
            },
            multiple: false,
            library: {
                type: libraryType
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

    $(function () {
        setTab('settings');
        syncFeatureSections();
    });
})(jQuery);
