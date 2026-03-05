(function ($) {
    'use strict';

    var latestPayload = null;

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

    function setCardStatus(checkKey, checkData) {
        var $card = $('.bw-system-card[data-check="' + checkKey + '"]');
        if (!$card.length || !checkData) {
            return;
        }

        var status = checkData.status || 'unknown';
        $card.find('.bw-system-card-badge').text(statusLabel(status));
        $card.find('.bw-system-card-summary').text(checkData.summary || '-');

        $card.removeClass('bw-status-ok bw-status-warn bw-status-error bw-status-unknown').addClass('bw-status-' + status);
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

        $item.text(icon + ' ' + title + ': ' + statusLabel(status));
        $item.removeClass('bw-status-ok bw-status-warn bw-status-error bw-status-unknown').addClass('bw-status-' + status);
    }

    function renderMedia(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var byType = metrics.by_type || {};

        $('[data-field="media-total-files"]').text(metrics.total_files || metrics.attachments_total || '-');
        $('[data-field="media-total-bytes"]').text(metrics.total_bytes_human || '-');
        $('[data-field="media-type-jpeg"]').text((byType.jpeg && byType.jpeg.bytes_human) ? byType.jpeg.bytes_human : '-');
        $('[data-field="media-type-png"]').text((byType.png && byType.png.bytes_human) ? byType.png.bytes_human : '-');
        $('[data-field="media-type-svg"]').text((byType.svg && byType.svg.bytes_human) ? byType.svg.bytes_human : '-');
        $('[data-field="media-type-video"]').text((byType.video && byType.video.bytes_human) ? byType.video.bytes_human : '-');
        $('[data-field="media-type-webp"]').text((byType.webp && byType.webp.bytes_human) ? byType.webp.bytes_human : '-');
        $('[data-field="media-type-other"]').text((byType.other && byType.other.bytes_human) ? byType.other.bytes_human : '-');
    }

    function renderImageSizes(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var sizes = metrics.sizes || [];
        var $list = $('[data-field="images-size-list"]');

        $('[data-field="images-total-sizes"]').text(metrics.total_registered_sizes || metrics.count || sizes.length || '-');
        $list.empty();

        if (!sizes.length) {
            $list.append('<li>-</li>');
            return;
        }

        sizes.forEach(function (size) {
            var label = (size.name || '-') + ': ' + (size.width || 0) + 'x' + (size.height || 0) + ', crop=' + (size.crop ? 'yes' : 'no');
            $list.append($('<li/>').text(label));
        });
    }

    function renderDatabase(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var tables = metrics.top_largest_tables || metrics.largest_tables || [];
        var $list = $('[data-field="database-table-list"]');

        $list.empty();
        $list.append($('<li/>').text('Total DB size: ' + (metrics.total_db_size_human || '-')));
        $list.append($('<li/>').text('Tables: ' + (metrics.total_table_count || '-')));
        if (metrics.autoload && metrics.autoload.total_size_human) {
            $list.append($('<li/>').text('Autoload: ' + metrics.autoload.total_size_human));
        }

        if (tables.length) {
            tables.slice(0, 5).forEach(function (table) {
                $list.append($('<li/>').text((table.name || '-') + ' (' + (table.size_human || '-') + ')'));
            });
        }
    }

    function renderWordPress(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var $list = $('[data-field="wordpress-list"]');
        $list.empty();
        $list.append($('<li/>').text('WordPress: ' + (metrics.wordpress_version || '-')));
        $list.append($('<li/>').text('WooCommerce: ' + (metrics.woocommerce_version || '-')));
        $list.append($('<li/>').text('PHP: ' + (metrics.php_version || '-')));
        $list.append($('<li/>').text('Memory limit: ' + (metrics.php_memory_limit || '-')));
        $list.append($('<li/>').text('WP_DEBUG: ' + (metrics.wp_debug ? 'ON' : 'OFF')));
        $list.append($('<li/>').text('DISALLOW_FILE_EDIT: ' + (metrics.disallow_file_edit ? 'ON' : 'OFF')));
    }

    function renderServer(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var $list = $('[data-field="server-list"]');
        $list.empty();
        $list.append($('<li/>').text('upload_max_filesize: ' + (metrics.upload_max_filesize || '-')));
        $list.append($('<li/>').text('post_max_size: ' + (metrics.post_max_size || '-')));
        $list.append($('<li/>').text('memory_limit: ' + (metrics.memory_limit || '-')));
        $list.append($('<li/>').text('max_execution_time: ' + (metrics.max_execution_time || '-')));
    }

    function renderResults(payload) {
        latestPayload = payload;

        $('#bw-system-status-results').show();
        $('#bw-system-generated-at').text(payload.generated_at || '-');
        $('#bw-system-source').text(payload.cached ? 'Cache' : 'Live');
        $('#bw-system-ttl').text((payload.ttl_seconds || '-') + 's');
        $('#bw-system-execution-time').text((payload.execution_time_ms || 0) + ' ms');

        var checks = payload.checks || {};

        setCardStatus('media', checks.media || {});
        setCardStatus('images', checks.images || {});
        setCardStatus('database', checks.database || {});
        setCardStatus('wordpress', checks.wordpress || {});
        setCardStatus('server', checks.server || {});

        setOverviewItem('media', 'Media Storage', checks.media || {});
        setOverviewItem('database', 'Database', checks.database || {});
        setOverviewItem('wordpress', 'WordPress', checks.wordpress || {});
        setOverviewItem('server', 'PHP Limits', checks.server || {});

        renderMedia(checks.media || {});
        renderImageSizes(checks.images || {});
        renderDatabase(checks.database || {});
        renderWordPress(checks.wordpress || {});
        renderServer(checks.server || {});

        $('#bw-system-status-json').text(JSON.stringify(payload, null, 2));
    }

    function runChecks(options) {
        var payload = {
            action: 'bw_system_status_run_check',
            nonce: bwSystemStatus.nonce,
            force_refresh: options.forceRefresh ? '1' : '0'
        };

        if (options.checks && options.checks.length) {
            payload.checks = options.checks.join(',');
        }

        setFeedback('warning', bwSystemStatus.messages.running);

        $.post(bwSystemStatus.ajaxUrl, payload).done(function (response) {
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

    function downloadJson() {
        if (!latestPayload) {
            return;
        }

        var blob = new Blob([JSON.stringify(latestPayload, null, 2)], { type: 'application/json' });
        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'blackwork-system-status.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }

    $(function () {
        $('#bw-system-status-run').on('click', function (event) {
            event.preventDefault();
            runChecks({ forceRefresh: false, checks: [] });
        });

        $('#bw-system-status-refresh').on('click', function (event) {
            event.preventDefault();
            runChecks({ forceRefresh: true, checks: [] });
        });

        $('.bw-system-run-section').on('click', function (event) {
            event.preventDefault();
            var checks = String($(this).data('checks') || '').split(',').map(function (item) {
                return $.trim(item);
            }).filter(function (item) {
                return item.length > 0;
            });
            runChecks({ forceRefresh: true, checks: checks });
        });

        $('#bw-system-download-json').on('click', function (event) {
            event.preventDefault();
            downloadJson();
        });
    });
})(jQuery);
