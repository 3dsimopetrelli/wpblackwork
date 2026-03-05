(function ($) {
    'use strict';

    function setFeedback(type, message) {
        var $feedback = $('#bw-system-status-feedback');
        if (!$feedback.length) {
            return;
        }

        $feedback.removeClass('notice-success notice-warning notice-error').addClass('notice-' + type).show();
        $feedback.find('p').text(message || '');
    }

    function setCheckRow(checkKey, checkData) {
        var $row = $('#bw-system-status-table').find('tr[data-check="' + checkKey + '"]');
        if (!$row.length || !checkData) {
            return;
        }

        var badgeText = (checkData.status || 'unknown').toUpperCase();
        $row.find('.bw-status-badge').text(badgeText);
        $row.find('.bw-status-summary').text(checkData.summary || '-');

        $row.removeClass('bw-status-ok bw-status-warn bw-status-error bw-status-unknown')
            .addClass('bw-status-' + (checkData.status || 'unknown'));
    }

    function renderResults(payload) {
        $('#bw-system-status-results').show();
        $('#bw-system-generated-at').text(payload.generated_at || '-');
        $('#bw-system-source').text(payload.cached ? 'Cache' : 'Live');

        if (payload.checks) {
            setCheckRow('media', payload.checks.media || {});
            setCheckRow('database', payload.checks.database || {});
            setCheckRow('images', payload.checks.images || {});
        }

        $('#bw-system-status-json').text(JSON.stringify(payload, null, 2));
    }

    function runChecks(forceRefresh) {
        setFeedback('warning', bwSystemStatus.messages.running);

        $.post(bwSystemStatus.ajaxUrl, {
            action: 'bw_system_status_run_check',
            nonce: bwSystemStatus.nonce,
            force_refresh: forceRefresh ? '1' : '0'
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                setFeedback('error', bwSystemStatus.messages.failed);
                return;
            }

            renderResults(response.data);
            setFeedback('success', response.data.cached ? 'Loaded cached snapshot.' : 'System checks completed.');
        }).fail(function () {
            setFeedback('error', bwSystemStatus.messages.failed);
        });
    }

    $(function () {
        $('#bw-system-status-run').on('click', function (event) {
            event.preventDefault();
            runChecks(false);
        });

        $('#bw-system-status-refresh').on('click', function (event) {
            event.preventDefault();
            runChecks(true);
        });
    });
})(jQuery);
