jQuery(function ($) {
    var panel = $('#bw-user-mail-marketing-panel');
    if (!panel.length) {
        return;
    }

    var cfg = window.bwUserMailMarketing || {};
    var userId = panel.data('user-id');
    var checkBtn = $('#bw-user-check-status');
    var syncBtn = $('#bw-user-sync-status');
    var badgeEl = $('#bw-user-status-badge');
    var messageEl = $('#bw-user-inline-message');

    function setBusy(isBusy) {
        checkBtn.prop('disabled', isBusy);
        syncBtn.prop('disabled', isBusy);
    }

    function renderMessage(type, text) {
        messageEl.css({
            color: type === 'error' ? '#8a1f11' : '#0a3622',
            fontWeight: 600
        }).text(text || '');
    }

    function updatePayload(payload) {
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
                panel.find('[data-bw-user-field="' + key + '"]').text(payload.meta[key] || '');
            });
        }
        if (payload.message) {
            renderMessage('success', payload.message);
        }
    }

    function callAction(actionName) {
        if (!cfg.ajaxUrl || !cfg.nonce || !userId) {
            renderMessage('error', cfg.errorText || 'Action failed.');
            return;
        }

        setBusy(true);
        renderMessage('success', 'Working...');

        $.ajax({
            url: cfg.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: actionName,
                nonce: cfg.nonce,
                user_id: userId
            }
        }).done(function (response) {
            if (response && response.success) {
                updatePayload(response.data);
                return;
            }

            var err = response && response.data && response.data.message
                ? response.data.message
                : (cfg.errorText || 'Action failed.');
            renderMessage('error', err);
        }).fail(function (xhr) {
            var err = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                ? xhr.responseJSON.data.message
                : (cfg.errorText || 'Network error.');
            renderMessage('error', err);
        }).always(function () {
            setBusy(false);
        });
    }

    checkBtn.on('click', function (event) {
        event.preventDefault();
        callAction('bw_brevo_user_check_status');
    });

    syncBtn.on('click', function (event) {
        event.preventDefault();
        callAction('bw_brevo_user_sync_status');
    });
});
