(function ($) {
    'use strict';

    function getRowTemplate() {
        return $('#tmpl-bw-tbl-font-row').html() || '';
    }

    function getSingleProductRuleTemplate() {
        return $('#tmpl-bw-tbl-single-product-rule-row').html() || '';
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

    function nextSingleProductRuleIndex() {
        var max = -1;
        $('#bw-tbl-single-product-rules-list .bw-tbl-single-product-rule').each(function () {
            var name = $(this).find('select[name*="[rules]["]').first().attr('name') || '';
            var match = name.match(/\[rules\]\[(\d+)\]/);
            if (match) {
                var value = parseInt(match[1], 10);
                if (!isNaN(value) && value > max) {
                    max = value;
                }
            }
        });

        return max + 1;
    }

    function buildSingleProductRuleRow() {
        var html = getSingleProductRuleTemplate();
        if (!html) {
            return $();
        }

        var index = nextSingleProductRuleIndex();
        html = html.replace(/\[99999\]/g, '[' + index + ']');
        html = html.replace(/data-bw-tbl-rule-index="99999"/g, 'data-bw-tbl-rule-index="' + index + '"');
        var $row = $(html);
        $row.find('.bw-tbl-rule-number').text((index + 1).toString());
        return $row;
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
        var singleProductEnabled = $('#bw-tbl-flag-single-product-conditions').is(':checked');

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

        if (singleProductEnabled) {
            $('#bw-tbl-single-product-controls').show();
        } else {
            $('#bw-tbl-single-product-controls').hide();
        }
    }

    function syncExcludeFields($scope) {
        var $root = $scope && $scope.length ? $scope : $(document);
        var $rules = $root.filter('.bw-tbl-single-product-rule').add($root.find('.bw-tbl-single-product-rule'));
        $rules.each(function () {
            var $rule = $(this);
            var enabled = $rule.find('.bw-tbl-enable-exclude').is(':checked');
            if (enabled) {
                $rule.find('.bw-tbl-exclude-fields').show();
            } else {
                $rule.find('.bw-tbl-exclude-fields').hide();
            }
        });
    }

    function syncIncludeFields($scope) {
        var $root = $scope && $scope.length ? $scope : $(document);
        var $rules = $root.filter('.bw-tbl-single-product-rule').add($root.find('.bw-tbl-single-product-rule'));
        $rules.each(function () {
            var $rule = $(this);
            var mode = ($rule.find('.bw-tbl-include-mode-radio:checked').val() || 'all').toString();
            var $fields = $rule.find('.bw-tbl-include-fields');
            var $select = $fields.find('select');
            if (mode === 'selected') {
                $fields.show();
                $select.prop('disabled', false);
            } else {
                $fields.hide();
                $select.prop('disabled', true);
                $select.val([]);
            }
        });
    }

    $(document).on('click', '#bw-tbl-tabs .nav-tab', function (event) {
        event.preventDefault();
        var tabKey = ($(this).data('bw-tbl-tab') || 'settings').toString();
        setTab(tabKey);
    });

    $(document).on('change', '#bw-tbl-flag-custom-fonts, #bw-tbl-flag-footer-override, #bw-tbl-flag-single-product-conditions', function () {
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

    $(document).on('click', '#bw-tbl-add-single-product-rule', function (event) {
        event.preventDefault();
        var row = buildSingleProductRuleRow();
        if (!row.length) {
            return;
        }
        $('#bw-tbl-single-product-rules-list').append(row);
        syncIncludeFields(row);
        syncExcludeFields(row);
    });

    $(document).on('click', '.bw-tbl-remove-single-product-rule', function (event) {
        event.preventDefault();
        if (!window.confirm('Remove this rule?')) {
            return;
        }
        var $rows = $('#bw-tbl-single-product-rules-list .bw-tbl-single-product-rule');
        if ($rows.length <= 1) {
            var $rule = $(this).closest('.bw-tbl-single-product-rule');
            $rule.find('select').each(function () {
                if ($(this).is('[multiple]')) {
                    $(this).val([]);
                } else {
                    $(this).val('0');
                }
            });
            $rule.find('.bw-tbl-include-mode-radio[value="all"]').prop('checked', true);
            $rule.find('.bw-tbl-enable-exclude').prop('checked', false);
            syncIncludeFields($rule);
            syncExcludeFields($rule);
            return;
        }

        $(this).closest('.bw-tbl-single-product-rule').remove();
    });

    $(document).on('change', '.bw-tbl-enable-exclude', function () {
        var $rule = $(this).closest('.bw-tbl-single-product-rule');
        syncExcludeFields($rule);
    });

    $(document).on('change', '.bw-tbl-include-mode-radio', function () {
        var $rule = $(this).closest('.bw-tbl-single-product-rule');
        syncIncludeFields($rule);
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
        syncIncludeFields();
        syncExcludeFields();
    });
})(jQuery);
