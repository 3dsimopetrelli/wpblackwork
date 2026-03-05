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

        var $body = $('body');
        var $wrap = $('.wrap').first();
        var $form = $('#posts-filter');
        if (!$wrap.length || !$form.length) {
            return;
        }

        $body.addClass('bw-admin-templates-screen');
        $wrap.addClass('bw-admin-root bw-admin-page bw-admin-templates');

        if (!$wrap.children('.bw-admin-header').length) {
            var $title = $wrap.children('h1.wp-heading-inline').first();
            var $header = $('<div class="bw-admin-header bw-admin-header--split"></div>');
            var $headerMain = $('<div class="bw-admin-header-main"></div>');
            var $headerActions = $('<div class="bw-admin-header-actions"></div>');
            var listTitle = (cfg.listTitle || 'All Templates').toString();
            var listSubtitle = (cfg.listSubtitle || 'Manage templates, type, priority, and applies-to rules.').toString();

            if ($title.length) {
                $title.text(listTitle).addClass('bw-admin-title');
                $headerMain.append($title);
            } else {
                $headerMain.append($('<h1 class="bw-admin-title"></h1>').text(listTitle));
            }

            $headerMain.append($('<p class="bw-admin-subtitle"></p>').text(listSubtitle));
            $wrap.children('.page-title-action').each(function () {
                $headerActions.append($(this).addClass('button button-secondary'));
            });

            $header.append($headerMain);
            if ($headerActions.children().length) {
                $header.append($headerActions);
            }

            var $wpHeaderEnd = $wrap.children('.wp-header-end').first();
            if ($wpHeaderEnd.length) {
                $header.insertBefore($wpHeaderEnd);
            } else {
                $wrap.prepend($header);
            }
        }

        if (!$form.children('.bw-admin-action-bar').length) {
            var $bar = $('<div class="bw-admin-action-bar bw-admin-action-bar-list"></div>');
            $bar.append('<div class="bw-admin-action-meta"></div>');
            var $actions = $('<div class="bw-admin-action-buttons"></div>');
            var $search = $form.find('p.search-box').first();
            if ($search.length) {
                $actions.append($search);
            }
            $bar.append($actions);
            $form.prepend($bar);
        }

        var $views = $form.find('ul.subsubsub').first();
        if ($views.length && !$form.find('.bw-admin-views-helper').length) {
            $views.after(
                $('<p class="bw-admin-views-helper"></p>').text(
                    (cfg.actionHelper || 'Filter, search, and manage your templates.').toString()
                )
            );
        }

        $form.find('table.wp-list-table').each(function () {
            var $table = $(this);
            if ($table.parent('.bw-table-wrap').length) {
                return;
            }
            $table.wrap('<div class="bw-table-wrap"></div>');
        });
    });
})(jQuery);
