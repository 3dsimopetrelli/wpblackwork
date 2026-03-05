(function ($) {
    'use strict';

    function statusLabel(status) {
        if (status === 'ok') {
            return 'OK';
        }
        if (status === 'warn') {
            return 'WARN';
        }
        if (status === 'error') {
            return 'ERROR';
        }
        return 'UNKNOWN';
    }

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

        var status = checkData.status || 'unknown';
        var badgeText = statusLabel(status);
        $row.find('.bw-status-badge').text(badgeText);
        $row.find('.bw-status-summary').text(checkData.summary || '-');

        $row.removeClass('bw-status-ok bw-status-warn bw-status-error bw-status-unknown')
            .addClass('bw-status-' + status);
    }

    function setOverviewItem(key, title, checkData) {
        var $item = $('#bw-system-overview').find('[data-overview="' + key + '"]');
        if (!$item.length) {
            return;
        }

        var status = (checkData && checkData.status) ? checkData.status : 'unknown';
        var icon = '•';
        if (status === 'ok') {
            icon = '✔';
        } else if (status === 'warn') {
            icon = '⚠';
        } else if (status === 'error') {
            icon = '✖';
        }

        $item.text(icon + ' ' + title + ' ' + statusLabel(status));
        $item.removeClass('bw-status-ok bw-status-warn bw-status-error bw-status-unknown')
            .addClass('bw-status-' + status);
    }

    function renderResults(payload) {
        $('#bw-system-status-results').show();
        $('#bw-system-generated-at').text(payload.generated_at || '-');
        $('#bw-system-source').text(payload.cached ? 'Cache' : 'Live');
        $('#bw-system-ttl').text((payload.ttl_seconds || '-') + 's');
        $('#bw-system-execution-time').text((payload.execution_time_ms || 0) + ' ms');

        if (payload.checks) {
            setCheckRow('media', payload.checks.media || {});
            setCheckRow('database', payload.checks.database || {});
            setCheckRow('images', payload.checks.images || {});
            setCheckRow('wordpress', payload.checks.wordpress || {});
            setCheckRow('server', payload.checks.server || {});

            setOverviewItem('server', 'Server', payload.checks.server || {});
            setOverviewItem('database', 'Database', payload.checks.database || {});
            setOverviewItem('media', 'Media Storage', payload.checks.media || {});
            setOverviewItem('wordpress', 'WordPress', payload.checks.wordpress || {});
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
