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

    function cleanFileName(path) {
        var value = String(path || '');
        var parts = value.split('/');
        return parts.length ? parts[parts.length - 1] : value;
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

    function setOverviewItem(key, status, summary) {
        var $item = $('#bw-system-overview').find('[data-overview="' + key + '"]');
        if (!$item.length) {
            return;
        }

        var icon = '•';
        if (status === 'ok') {
            icon = '✔';
        } else if (status === 'warn') {
            icon = '⚠';
        } else if (status === 'error') {
            icon = '✖';
        }

        $item.find('.bw-overview-pill-status').text(icon + ' ' + statusLabel(status || 'unknown'));
        $item.find('.bw-overview-pill-summary').text(summary || '-');
        $item.removeClass('bw-status-ok bw-status-warn bw-status-error bw-status-unknown').addClass('bw-status-' + (status || 'unknown'));
    }

    function setList($container, rows) {
        $container.empty();
        if (!rows || !rows.length) {
            $container.append('<li>-</li>');
            return;
        }

        rows.forEach(function (row) {
            $container.append($('<li/>').text(row));
        });
    }

    function appendTextRow($container, text) {
        $container.append($('<li/>').text(text));
    }

    function renderMedia(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var byType = metrics.by_type || {};
        var largest = metrics.largest_file || null;
        var largestLabel = '-';

        if (largest && largest.file) {
            largestLabel = cleanFileName(largest.file) + ' (' + (largest.size_human || '-') + ')';
        }

        $('[data-field="media-total-files"]').text(metrics.total_files || metrics.attachments_total || '-');
        $('[data-field="media-total-bytes"]').text(metrics.total_bytes_human || '-');
        $('[data-field="media-largest-file"]').text(largestLabel);
        $('[data-field="media-type-jpeg"]').text((byType.jpeg && byType.jpeg.bytes_human) ? byType.jpeg.bytes_human : '-');
        $('[data-field="media-type-png"]').text((byType.png && byType.png.bytes_human) ? byType.png.bytes_human : '-');
        $('[data-field="media-type-svg"]').text((byType.svg && byType.svg.bytes_human) ? byType.svg.bytes_human : '-');
        $('[data-field="media-type-video"]').text((byType.video && byType.video.bytes_human) ? byType.video.bytes_human : '-');
        $('[data-field="media-type-webp"]').text((byType.webp && byType.webp.bytes_human) ? byType.webp.bytes_human : '-');
        $('[data-field="media-type-other"]').text((byType.other && byType.other.bytes_human) ? byType.other.bytes_human : '-');

        var $largestList = $('[data-field="media-largest-list"]');
        $largestList.empty();
        (metrics.top_largest_files || []).forEach(function (item) {
            var $li = $('<li/>');
            var label = cleanFileName(item.file) + ' (' + (item.size_human || '-') + ')';
            if (item.id) {
                label += ' #' + item.id;
            }

            if (item.edit_url) {
                $('<a/>')
                    .attr('href', item.edit_url)
                    .attr('target', '_blank')
                    .attr('rel', 'noopener noreferrer')
                    .text(label)
                    .appendTo($li);
            } else {
                $li.text(label);
            }
            $largestList.append($li);
        });
        if (!$largestList.children().length) {
            appendTextRow($largestList, '-');
        }

        var warningRows = (checkData.warnings || []).slice();
        (metrics.missing_files || []).forEach(function (entry) {
            warningRows.push('Missing/unreadable: #' + (entry.id || 0) + ' ' + (entry.file || '(no path)'));
        });
        setList($('[data-field="media-warnings"]'), warningRows);
    }

    function renderImageSizes(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var sizes = metrics.sizes || [];
        $('[data-field="images-total-sizes"]').text(metrics.total_registered_sizes || sizes.length || '-');
        var generatedCounts = metrics.generated_counts || null;
        var perSize = (generatedCounts && generatedCounts.per_size) ? generatedCounts.per_size : null;
        var $tbody = $('[data-field="images-size-table-body"]');
        $tbody.empty();

        sizes.forEach(function (size) {
            var generatedValue = '—';
            if (perSize && Object.prototype.hasOwnProperty.call(perSize, size.name)) {
                generatedValue = String(perSize[size.name]);
            }

            var $tr = $('<tr/>');
            $('<td/>').text(size.name || '-').appendTo($tr);
            $('<td/>').text((size.width || 0) + 'x' + (size.height || 0)).appendTo($tr);
            $('<td/>').text(size.crop ? 'yes' : 'no').appendTo($tr);
            $('<td/>').text(generatedValue).appendTo($tr);
            $tbody.append($tr);
        });

        if (!$tbody.children().length) {
            var $empty = $('<tr/>');
            $('<td/>').attr('colspan', 4).text('-').appendTo($empty);
            $tbody.append($empty);
        }

        if (generatedCounts) {
            var hint = 'Generated totals: ' + (generatedCounts.total_resized_files || 0) + ' resized files';
            if (generatedCounts.partial) {
                hint += ' (partial: scanned ' + generatedCounts.scanned_attachments + ' of ' + generatedCounts.total_image_attachments + ')';
            }
            $('[data-field="images-generated-hint"]').text(hint);
        } else {
            $('[data-field="images-generated-hint"]').text('Generated counts not computed yet. Use \"Compute generated counts\".');
        }
    }

    function renderDatabase(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        var largest = metrics.largest_table || null;

        $('[data-field="database-total-size"]').text(metrics.total_db_size_human || '-');
        $('[data-field="database-table-count"]').text(metrics.total_table_count || '-');
        $('[data-field="database-largest-table"]').text(largest ? ((largest.name || '-') + ' (' + (largest.size_human || '-') + ')') : '-');

        var tableRows = [];
        (metrics.top_largest_tables || []).forEach(function (table) {
            tableRows.push((table.name || '-') + ' (' + (table.size_human || '-') + ')');
        });
        setList($('[data-field="database-table-list"]'), tableRows);

        var autoloadRows = [];
        if (metrics.autoload) {
            autoloadRows.push('Autoload size: ' + (metrics.autoload.total_size_human || '-'));
            autoloadRows.push('Autoload warning: ' + (metrics.autoload.warning_threshold_exceeded ? 'Yes' : 'No'));
        }
        setList($('[data-field="database-autoload"]'), autoloadRows);
    }

    function renderWordPress(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        $('[data-field="wp-version"]').text(metrics.wordpress_version || '-');
        $('[data-field="wp-php-version"]').text(metrics.php_version || '-');
        $('[data-field="wp-wc-version"]').text(metrics.woocommerce_version || '-');
        $('[data-field="wp-debug"]').text(metrics.wp_debug ? 'ON' : 'OFF');

        var rows = [
            'WordPress: ' + (metrics.wordpress_version || '-'),
            'PHP: ' + (metrics.php_version || '-'),
            'WooCommerce: ' + (metrics.woocommerce_version || '-'),
            'DISALLOW_FILE_EDIT: ' + (metrics.disallow_file_edit ? 'ON' : 'OFF')
        ];
        setList($('[data-field="wordpress-list"]'), rows);
        setList($('[data-field="wordpress-warnings"]'), checkData.warnings || []);
    }

    function renderLimits(checkData) {
        if (!checkData || !checkData.metrics) {
            return;
        }

        var metrics = checkData.metrics;
        $('[data-field="limits-upload"]').text(metrics.upload_max_filesize || '-');
        $('[data-field="limits-post"]').text(metrics.post_max_size || '-');
        $('[data-field="limits-memory"]').text(metrics.memory_limit || '-');
        $('[data-field="limits-time"]').text(metrics.max_execution_time || '-');

        var rows = [
            'upload_max_filesize: ' + (metrics.upload_max_filesize || '-'),
            'post_max_size: ' + (metrics.post_max_size || '-'),
            'memory_limit: ' + (metrics.memory_limit || '-'),
            'max_execution_time: ' + (metrics.max_execution_time || '-')
        ];

        if (checkData.warnings && checkData.warnings.length) {
            rows = rows.concat(checkData.warnings);
        }

        setList($('[data-field="limits-list"]'), rows);
    }

    function renderResults(payload) {
        latestPayload = payload;

        $('#bw-system-status-results').show();
        $('#bw-system-generated-at').text(payload.generated_at || '-');
        $('#bw-system-source').text(payload.cached ? 'Cached' : 'Live');
        $('#bw-system-ttl').text((payload.ttl_seconds || '-') + 's');
        $('#bw-system-execution-time').text((payload.execution_time_ms || 0) + 'ms');

        var checks = payload.checks || {};

        setCardStatus('media', checks.media || {});
        setCardStatus('images', checks.images || {});
        setCardStatus('database', checks.database || {});
        setCardStatus('wordpress', checks.wordpress || {});
        setCardStatus('limits', checks.limits || {});

        var mediaSummary = ((checks.media || {}).metrics && (checks.media || {}).metrics.total_bytes_human) ? ('Media ' + checks.media.metrics.total_bytes_human) : ((checks.media || {}).summary || '-');
        var dbSummary = ((checks.database || {}).metrics && (checks.database || {}).metrics.total_db_size_human) ? ('DB ' + checks.database.metrics.total_db_size_human) : ((checks.database || {}).summary || '-');
        var wpSummary = ((checks.wordpress || {}).metrics) ? ('WP ' + (checks.wordpress.metrics.wordpress_version || '-') + ' on PHP ' + (checks.wordpress.metrics.php_version || '-')) : ((checks.wordpress || {}).summary || '-');
        var limitsSummary = ((checks.limits || {}).metrics) ? ('Upload ' + (checks.limits.metrics.upload_max_filesize || '-') + ' • Memory ' + (checks.limits.metrics.memory_limit || '-')) : ((checks.limits || {}).summary || '-');

        setOverviewItem('media', (checks.media || {}).status || 'unknown', mediaSummary);
        setOverviewItem('database', (checks.database || {}).status || 'unknown', dbSummary);
        setOverviewItem('wordpress', (checks.wordpress || {}).status || 'unknown', wpSummary);
        setOverviewItem('limits', (checks.limits || {}).status || 'unknown', limitsSummary);

        renderMedia(checks.media || {});
        renderImageSizes(checks.images || {});
        renderDatabase(checks.database || {});
        renderWordPress(checks.wordpress || {});
        renderLimits(checks.limits || {});

        $('#bw-system-status-json').text(JSON.stringify(payload, null, 2));
    }

    function runChecks(options) {
        var requestPayload = {
            action: 'bw_system_status_run_check',
            nonce: bwSystemStatus.nonce,
            force_refresh: options.forceRefresh ? '1' : '0',
            check_scope: options.scope || 'all'
        };

        setFeedback('warning', bwSystemStatus.messages.running);

        $.post(bwSystemStatus.ajaxUrl, requestPayload).done(function (response) {
            if (!response || !response.success || !response.data) {
                setFeedback('error', bwSystemStatus.messages.failed);
                return;
            }

            renderResults(response.data);
            setFeedback('success', response.data.cached ? 'Loaded cached snapshot.' : 'Check completed.');
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
        link.download = 'blackwork-status-debug.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }

    $(function () {
        $('#bw-system-status-run').on('click', function (event) {
            event.preventDefault();
            runChecks({ forceRefresh: false, scope: 'all' });
        });

        $('#bw-system-status-refresh').on('click', function (event) {
            event.preventDefault();
            runChecks({ forceRefresh: true, scope: 'all' });
        });

        $('.bw-system-run-section').on('click', function (event) {
            event.preventDefault();
            var scope = String($(this).data('scope') || 'all');
            runChecks({ forceRefresh: true, scope: scope });
        });

        $('#bw-system-download-json').on('click', function (event) {
            event.preventDefault();
            downloadJson();
        });
    });
})(jQuery);
