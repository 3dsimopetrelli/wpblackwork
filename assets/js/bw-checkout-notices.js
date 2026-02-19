/**
 * BW Checkout Notices
 *
 * Moves WooCommerce notices into the left column of checkout
 */

(function($) {
    'use strict';

    function normalizeLabel(text) {
        if (!text) {
            return '';
        }

        var cleaned = String(text)
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        cleaned = cleaned
            .replace(/^(Billing|Shipping)\s+/i, '')
            .replace(/\s+is\s+a\s+required\s+field\.?$/i, '')
            .replace(/\s+\(optional\)\s*$/i, '')
            .replace(/\s*\*+\s*/g, ' ')
            .trim();

        return cleaned;
    }

    function getVisibleInvalidRequiredLabels() {
        var labels = [];
        var $form = $('form.checkout');

        if (!$form.length) {
            return labels;
        }

        $form.find('.validate-required.woocommerce-invalid:visible').each(function () {
            var $row = $(this);
            var labelText = normalizeLabel(
                $row.find('label').first().clone().children().remove().end().text()
            );

            if (labelText) {
                labels.push(labelText);
            }
        });

        return labels;
    }

    function buildCompactErrorMessage($notice) {
        var labels = getVisibleInvalidRequiredLabels();

        if (!labels.length) {
            $notice.find('li').each(function () {
                var cleaned = normalizeLabel($(this).text());
                if (cleaned) {
                    labels.push(cleaned);
                }
            });
        }

        if (!labels.length) {
            return '';
        }

        var unique = Array.from(new Set(labels));
        return 'Please fill in required fields: ' + unique.join(', ') + '.';
    }

    function normalizeErrorNotice($notice) {
        if (!$notice || !$notice.length || !$notice.hasClass('woocommerce-error')) {
            return;
        }

        var compact = buildCompactErrorMessage($notice);
        if (!compact) {
            return;
        }

        $notice.html('<li>' + compact + '</li>');
    }

    /**
     * Move notices into left column
     */
    function moveNotices() {
        // Only run on checkout page
        if (!$('body').hasClass('woocommerce-checkout')) {
            return;
        }

        const leftColumn = $('.bw-checkout-left');

        if (!leftColumn.length) {
            return;
        }

        // Find ALL notices anywhere on the page (not inside left column already)
        const notices = $('.woocommerce-error, .woocommerce-message, .woocommerce-info').not('.bw-checkout-left .woocommerce-error, .bw-checkout-left .woocommerce-message, .bw-checkout-left .woocommerce-info');

        if (notices.length) {
            // Create container if it doesn't exist
            let container = leftColumn.find('.bw-checkout-notices-container');

            if (!container.length) {
                container = $('<div class="bw-checkout-notices-container"></div>');
                leftColumn.prepend(container);
            }

            // Clear container and move notices
            container.empty();

            // Clone and append each notice to avoid removing from original position if needed later
            notices.each(function() {
                const notice = $(this).clone();
                normalizeErrorNotice(notice);
                container.append(notice);
            });

            // Hide original notices
            notices.hide();
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        setTimeout(moveNotices, 100);
    });

    /**
     * Re-run after AJAX updates (e.g., after failed checkout submission)
     */
    $(document.body).on('checkout_error updated_checkout', function() {
        setTimeout(moveNotices, 200);
    });

    /**
     * Re-run when DOM changes (for dynamic notices)
     */
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            const hasNewNotices = mutations.some(function(mutation) {
                return Array.from(mutation.addedNodes).some(function(node) {
                    return node.nodeType === 1 && (
                        node.classList && (
                            node.classList.contains('woocommerce-error') ||
                            node.classList.contains('woocommerce-message') ||
                            node.classList.contains('woocommerce-info')
                        )
                    );
                });
            });

            if (hasNewNotices) {
                setTimeout(moveNotices, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})(jQuery);
