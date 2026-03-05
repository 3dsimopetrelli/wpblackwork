(function ($) {
    'use strict';

    function getRowTemplate() {
        return $('#tmpl-bw-tbl-font-row').html() || '';
    }

    function getSingleProductRuleTemplate() {
        return $('#tmpl-bw-tbl-single-product-rule-row').html() || '';
    }

    function getProductArchiveRuleTemplate() {
        return $('#tmpl-bw-tbl-product-archive-rule-row').html() || '';
    }

    function nextIndex() {
        var max = -1;
        $('#bw-tbl-fonts-list .bw-tbl-font-row').each(function () {
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

    function nextProductArchiveRuleIndex() {
        var max = -1;
        $('#bw-tbl-product-archive-rules-list .bw-tbl-product-archive-rule').each(function () {
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

    function buildProductArchiveRuleRow() {
        var html = getProductArchiveRuleTemplate();
        if (!html) {
            return $();
        }

        var index = nextProductArchiveRuleIndex();
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
        var productArchiveEnabled = $('#bw-tbl-flag-product-archive-conditions').is(':checked');

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

        if (productArchiveEnabled) {
            $('#bw-tbl-product-archive-controls').show();
        } else {
            $('#bw-tbl-product-archive-controls').hide();
        }
    }

    function syncExcludeFields($scope) {
        var $root = $scope && $scope.length ? $scope : $(document);
        var $rules = $root
            .filter('.bw-tbl-single-product-rule, .bw-tbl-product-archive-rule')
            .add($root.find('.bw-tbl-single-product-rule, .bw-tbl-product-archive-rule'));
        $rules.each(function () {
            var $rule = $(this);
            var enabled = $rule.find('.bw-tbl-enable-exclude').is(':checked');
            var $excludeInputs = $rule.find('.bw-tbl-exclude-fields input[type="checkbox"]');
            if (enabled) {
                $rule.find('.bw-tbl-exclude-fields').show();
                $excludeInputs.prop('disabled', false);
            } else {
                $rule.find('.bw-tbl-exclude-fields').hide();
                $excludeInputs.prop('disabled', true);
            }
        });
    }

    function syncIncludeFields($scope) {
        var $root = $scope && $scope.length ? $scope : $(document);
        var $rules = $root
            .filter('.bw-tbl-single-product-rule, .bw-tbl-product-archive-rule')
            .add($root.find('.bw-tbl-single-product-rule, .bw-tbl-product-archive-rule'));
        $rules.each(function () {
            var $rule = $(this);
            var mode = ($rule.find('.bw-tbl-include-mode-radio:checked').val() || 'all').toString();
            var $fields = $rule.find('.bw-tbl-include-fields');
            var $includeInputs = $fields.find('input[type="checkbox"]');
            if (mode === 'selected') {
                $fields.show();
                $includeInputs.prop('disabled', false);
            } else {
                $fields.hide();
                $includeInputs.prop('disabled', true);
                $includeInputs.prop('checked', false);
            }
        });
    }

    $(document).on('click', '#bw-tbl-tabs .nav-tab', function (event) {
        event.preventDefault();
        var tabKey = ($(this).data('bw-tbl-tab') || 'settings').toString();
        setTab(tabKey);
    });

    $(document).on('change', '#bw-tbl-flag-custom-fonts, #bw-tbl-flag-footer-override, #bw-tbl-flag-single-product-conditions, #bw-tbl-flag-product-archive-conditions', function () {
        syncFeatureSections();
    });

    $(document).on('click', '#bw-tbl-add-font-row', function (event) {
        event.preventDefault();
        var row = buildRow();
        if (!row.length) {
            return;
        }
        $('#bw-tbl-fonts-list').append(row);
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

    $(document).on('click', '#bw-tbl-add-product-archive-rule', function (event) {
        event.preventDefault();
        var row = buildProductArchiveRuleRow();
        if (!row.length) {
            return;
        }
        $('#bw-tbl-product-archive-rules-list').append(row);
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
                $(this).val('0');
            });
            $rule.find('.bw-tbl-include-fields input[type="checkbox"], .bw-tbl-exclude-fields input[type="checkbox"]').prop('checked', false);
            $rule.find('.bw-tbl-include-mode-radio[value="all"]').prop('checked', true);
            $rule.find('.bw-tbl-enable-exclude').prop('checked', false);
            syncIncludeFields($rule);
            syncExcludeFields($rule);
            return;
        }

        $(this).closest('.bw-tbl-single-product-rule').remove();
    });

    $(document).on('click', '.bw-tbl-remove-product-archive-rule', function (event) {
        event.preventDefault();
        if (!window.confirm('Remove this rule?')) {
            return;
        }
        var $rows = $('#bw-tbl-product-archive-rules-list .bw-tbl-product-archive-rule');
        if ($rows.length <= 1) {
            var $rule = $(this).closest('.bw-tbl-product-archive-rule');
            $rule.find('select').each(function () {
                $(this).val('0');
            });
            $rule.find('.bw-tbl-include-fields input[type="checkbox"], .bw-tbl-exclude-fields input[type="checkbox"]').prop('checked', false);
            $rule.find('.bw-tbl-include-mode-radio[value="all"]').prop('checked', true);
            $rule.find('.bw-tbl-enable-exclude').prop('checked', false);
            syncIncludeFields($rule);
            syncExcludeFields($rule);
            return;
        }

        $(this).closest('.bw-tbl-product-archive-rule').remove();
    });

    $(document).on('change', '.bw-tbl-enable-exclude', function () {
        var $rule = $(this).closest('.bw-tbl-single-product-rule, .bw-tbl-product-archive-rule');
        syncExcludeFields($rule);
    });

    $(document).on('change', '.bw-tbl-include-mode-radio', function () {
        var $rule = $(this).closest('.bw-tbl-single-product-rule, .bw-tbl-product-archive-rule');
        syncIncludeFields($rule);
    });

    $(document).on('click', '.bw-tbl-remove-font-row', function (event) {
        event.preventDefault();
        var $rows = $('#bw-tbl-fonts-list .bw-tbl-font-row');
        if ($rows.length <= 1) {
            $(this).closest('.bw-tbl-font-row').find('input[type="text"], input[type="url"]').val('');
            $(this).closest('.bw-tbl-font-row').find('select').val('normal');
            return;
        }

        $(this).closest('.bw-tbl-font-row').remove();
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

    function initSingleProductPreviewSearch() {
        var $hidden = $('#bw-tbl-single-product-preview-product-id');
        if (!$hidden.length) {
            return;
        }

        var cfg = window.bwTblAdmin || {};
        var ajaxUrl = (cfg.ajaxUrl || '').toString();
        var nonce = (cfg.previewProductNonce || '').toString();
        if (!ajaxUrl || !nonce) {
            return;
        }

        var i18n = cfg.i18n || {};
        var $search = $('#bw-tbl-single-product-preview-product-search');
        var $results = $('#bw-tbl-single-product-preview-product-results');
        var $selected = $('#bw-tbl-single-product-preview-product-selected-text');
        var $clear = $('#bw-tbl-single-product-preview-product-clear');
        var debounceTimer = null;

        function clearResults() {
            $results.hide().empty();
        }

        function setSelected(id, title) {
            var cleanId = parseInt(id, 10);
            if (isNaN(cleanId) || cleanId <= 0) {
                cleanId = 0;
            }

            var cleanTitle = (title || '').toString();
            $hidden.val(cleanId > 0 ? cleanId : 0);
            if (cleanId > 0 && cleanTitle) {
                $selected.text(cleanTitle + ' (ID ' + cleanId + ')');
            } else {
                $selected.text('None selected');
            }
            $selected.attr('data-selected-id', cleanId > 0 ? cleanId : 0);
            $selected.attr('data-selected-title', cleanTitle);
        }

        function renderItems(items) {
            $results.empty();

            if (!items || !items.length) {
                $('<div class="bw-tbl-ajax-search-empty" />')
                    .text((i18n.noResults || 'No products found.').toString())
                    .appendTo($results);
                $results.show();
                return;
            }

            items.forEach(function (item) {
                var id = parseInt(item.id, 10);
                var text = (item.text || '').toString();
                if (!id || !text) {
                    return;
                }

                $('<button type="button" class="bw-tbl-ajax-search-item" />')
                    .attr('data-id', id)
                    .attr('data-title', text)
                    .text(text + ' (ID ' + id + ')')
                    .appendTo($results);
            });

            $results.show();
        }

        function searchProducts(term) {
            var query = (term || '').toString().trim();
            if (query.length < 2) {
                clearResults();
                return;
            }

            $results.show().html(
                $('<div class="bw-tbl-ajax-search-empty" />').text((i18n.searching || 'Searching products...').toString())
            );

            $.post(ajaxUrl, {
                action: 'bw_tbl_search_preview_products',
                nonce: nonce,
                q: query
            })
                .done(function (response) {
                    if (!response || response.success !== true || !response.data) {
                        renderItems([]);
                        return;
                    }
                    renderItems(response.data.items || []);
                })
                .fail(function () {
                    $results.empty();
                    $('<div class="bw-tbl-ajax-search-empty" />')
                        .text((i18n.requestFailed || 'Search failed. Try again.').toString())
                        .appendTo($results);
                    $results.show();
                });
        }

        $search.on('input', function () {
            var term = $(this).val();
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(function () {
                searchProducts(term);
            }, 250);
        });

        $results.on('click', '.bw-tbl-ajax-search-item', function () {
            var $item = $(this);
            setSelected($item.data('id'), $item.data('title'));
            $search.val($item.data('title') || '');
            clearResults();
        });

        $clear.on('click', function (event) {
            event.preventDefault();
            $search.val('');
            setSelected(0, '');
            clearResults();
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('#bw-tbl-single-product-preview-product-search, #bw-tbl-single-product-preview-product-results').length) {
                clearResults();
            }
        });

        var selectedTitle = ($selected.attr('data-selected-title') || '').toString();
        if (selectedTitle) {
            $search.val(selectedTitle);
        }
    }

    $(function () {
        var tabFromQuery = '';
        try {
            var params = new URLSearchParams(window.location.search || '');
            tabFromQuery = (params.get('tab') || '').toString();
        } catch (err) {
            tabFromQuery = '';
        }
        var allowedTabs = ['settings', 'fonts', 'footer', 'single-product', 'product-archive', 'import-template'];
        var initialTab = allowedTabs.indexOf(tabFromQuery) >= 0 ? tabFromQuery : 'settings';
        setTab(initialTab);
        syncFeatureSections();
        syncIncludeFields();
        syncExcludeFields();
        initSingleProductPreviewSearch();
    });
})(jQuery);
