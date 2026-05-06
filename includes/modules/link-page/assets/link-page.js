(function () {
    'use strict';

    function toFormBody(payload) {
        return new URLSearchParams(payload).toString();
    }

    function sendClick(payload, endpoint) {
        if (!endpoint) {
            return;
        }

        var body = toFormBody(payload);

        if (navigator.sendBeacon) {
            var blob = new Blob([body], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
            navigator.sendBeacon(endpoint, blob);
            return;
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body,
            keepalive: true,
            credentials: 'same-origin'
        }).catch(function () {
            return null;
        });
    }

    function initAnalyticsTracking() {
        var config = window.bwLinkPageAnalytics || {};
        var endpoint = typeof config.endpoint === 'string' ? config.endpoint : '';
        var action = typeof config.action === 'string' ? config.action : 'bw_link_page_track_click';
        var nonce = typeof config.nonce === 'string' ? config.nonce : '';
        var pageId = Number(config.pageId || 0);

        if (!endpoint || !pageId) {
            return;
        }

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            var link = target.closest('.link-item[data-bw-link-id]');
            if (!link) {
                return;
            }

            var linkId = String(link.getAttribute('data-bw-link-id') || '');
            var linkLabel = String(link.getAttribute('data-bw-link-label') || '').trim();
            var targetUrl = String(link.getAttribute('href') || '');

            if (!linkId || !linkLabel) {
                return;
            }

            sendClick({
                action: action,
                nonce: nonce,
                page_id: String(pageId),
                link_id: linkId,
                link_label: linkLabel,
                target_url: targetUrl
            }, endpoint);
        }, { capture: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAnalyticsTracking);
    } else {
        initAnalyticsTracking();
    }
}());
