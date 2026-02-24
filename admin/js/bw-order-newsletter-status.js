jQuery(function ($) {
    var panel = $('#bw-newsletter-status-panel');
    if (!panel.length) {
        return;
    }

    var cfg = window.bwOrderNewsletterStatus || {};
    var orderId = panel.data('order-id');
    var messageEl = $('#bw-newsletter-inline-message');
    var badgeEl = $('#bw-newsletter-status-badge');
    var refreshBtn = $('#bw-newsletter-refresh');
    var retryBtn = $('#bw-newsletter-retry');

    function setButtonsDisabled(disabled) {
        refreshBtn.prop('disabled', disabled);
        retryBtn.prop('disabled', disabled);
    }

    function renderMessage(type, text) {
        messageEl.removeClass('notice-success notice-error').css({
            color: type === 'error' ? '#691010' : '#0a3622',
            fontWeight: 600
        }).text(text || '');
    }

    function updatePanelFromPayload(payload) {
        if (!payload) {
            return;
        }

        if (payload.statusLabel) {
            badgeEl.text(payload.statusLabel);
        }

        if (payload.statusClass) {
            badgeEl.removeClass('bw-status--subscribed bw-status--pending bw-status--neutral bw-status--error')
                .addClass(payload.statusClass);
        }

        if (payload.meta) {
            Object.keys(payload.meta).forEach(function (key) {
                panel.find('[data-bw-field="' + key + '"]').text(payload.meta[key] || '');
            });
        }

        if (payload.message) {
            renderMessage('success', payload.message);
        }
    }

    function callAction(actionName) {
        if (!cfg.ajaxUrl || !cfg.nonce || !orderId) {
            renderMessage('error', cfg.errorText || 'Action failed.');
            return;
        }

        setButtonsDisabled(true);
        renderMessage('success', 'Working...');

        $.ajax({
            url: cfg.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: actionName,
                nonce: cfg.nonce,
                order_id: orderId
            }
        }).done(function (response) {
            if (response && response.success) {
                updatePanelFromPayload(response.data);
                return;
            }

            var errorMsg = response && response.data && response.data.message
                ? response.data.message
                : (cfg.errorText || 'Action failed.');
            renderMessage('error', errorMsg);
        }).fail(function (xhr) {
            var errorMsg = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                ? xhr.responseJSON.data.message
                : (cfg.errorText || 'Network error.');
            renderMessage('error', errorMsg);
        }).always(function () {
            setButtonsDisabled(false);
        });
    }

    refreshBtn.on('click', function (event) {
        event.preventDefault();
        callAction('bw_brevo_order_refresh_status');
    });

    retryBtn.on('click', function (event) {
        event.preventDefault();
        callAction('bw_brevo_order_retry_subscribe');
    });
});
