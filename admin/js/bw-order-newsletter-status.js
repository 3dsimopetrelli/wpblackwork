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
    var loadListsBtn = $('#bw-newsletter-load-lists');
    var advancedToggle = $('#bw-newsletter-advanced-toggle');
    var advancedContent = $('#bw-newsletter-advanced-content');

    function setButtonsDisabled(disabled) {
        refreshBtn.prop('disabled', disabled);
        retryBtn.prop('disabled', disabled);
        if (loadListsBtn.length) {
            loadListsBtn.prop('disabled', disabled);
        }
    }

    function setButtonBusy(button, busy) {
        if (!button || !button.length) {
            return;
        }
        button.toggleClass('is-busy', !!busy);
        button.find('.spinner').toggleClass('is-active', !!busy);
    }

    function renderMessage(type, text) {
        messageEl.removeClass('bw-notice-success bw-notice-error').addClass(
            type === 'error' ? 'bw-notice-error' : 'bw-notice-success'
        ).text(text || '');
    }

    function updatePanelFromPayload(payload) {
        if (!payload) {
            return;
        }

        if (payload.statusLabel) {
            var labelEl = badgeEl.find('.bw-newsletter-status-badge__label');
            if (labelEl.length) {
                labelEl.text(payload.statusLabel);
            } else {
                badgeEl.text(payload.statusLabel);
            }
        }

        if (payload.statusClass) {
            badgeEl.removeClass('bw-status--subscribed bw-status--pending bw-status--neutral bw-status--error')
                .addClass(payload.statusClass);
        }
        if (payload.statusIcon) {
            badgeEl.find('.dashicons')
                .removeClass('dashicons-yes-alt dashicons-update dashicons-minus dashicons-warning')
                .addClass(payload.statusIcon);
        }

        if (payload.meta) {
            Object.keys(payload.meta).forEach(function (key) {
                panel.find('[data-bw-field="' + key + '"]').text(payload.meta[key] || '—');
            });

            if (loadListsBtn.length) {
                if (String(payload.meta.list_needs_load) === '1') {
                    loadListsBtn.removeClass('hidden');
                } else {
                    loadListsBtn.addClass('hidden');
                }
            }
        }

        if (payload.message) {
            renderMessage('success', payload.message);
        }
    }

    function callAction(actionName, sourceBtn) {
        if (!cfg.ajaxUrl || !cfg.nonce || !orderId) {
            renderMessage('error', cfg.errorText || 'Action failed.');
            return;
        }

        setButtonsDisabled(true);
        setButtonBusy(sourceBtn, true);
        renderMessage('success', cfg.workingText || 'Working...');

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
            setButtonBusy(sourceBtn, false);
            setButtonsDisabled(false);
        });
    }

    refreshBtn.on('click', function (event) {
        event.preventDefault();
        callAction('bw_brevo_order_refresh_status', refreshBtn);
    });

    retryBtn.on('click', function (event) {
        event.preventDefault();
        var optInRaw = panel.find('[data-bw-field="opt_in_raw"]').text();
        if (String(optInRaw).trim() !== '1') {
            if (!window.confirm(cfg.retryNoOptInConfirm || 'No opt-in recorded. Retry will not subscribe. Continue?')) {
                return;
            }
        }
        callAction('bw_brevo_order_retry_subscribe', retryBtn);
    });

    if (loadListsBtn.length) {
        loadListsBtn.on('click', function (event) {
            event.preventDefault();
            callAction('bw_brevo_order_load_lists', loadListsBtn);
        });
    }

    panel.on('click', '.bw-copy-field', function (event) {
        event.preventDefault();
        var field = $(this).data('copy-field');
        if (!field) {
            return;
        }
        var value = panel.find('[data-bw-field="' + field + '"]').text().trim();
        if (!value || value === '—') {
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                renderMessage('success', 'Copied.');
            }).catch(function () {
                renderMessage('error', cfg.errorText || 'Copy failed.');
            });
        }
    });

    if (advancedToggle.length && advancedContent.length) {
        advancedToggle.on('click', function (event) {
            event.preventDefault();
            var isExpanded = advancedToggle.attr('aria-expanded') === 'true';
            advancedToggle.attr('aria-expanded', isExpanded ? 'false' : 'true');
            advancedToggle.text(isExpanded ? 'Show advanced' : 'Hide advanced');
            advancedContent.toggleClass('hidden', isExpanded);
        });
    }
});
