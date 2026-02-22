/**
 * BW Checkout Notices
 *
 * Moves WooCommerce notices into the left column of checkout
 */

(function($) {
    'use strict';
    var REQUIRED_ERRORS_AUTO_HIDE_MS = 3000;
    var requiredErrorsTimer = null;

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
        var noticeText = String($notice.text() || '').toLowerCase();
        var looksLikeRequiredError = noticeText.indexOf('required field') !== -1 ||
            noticeText.indexOf('please fill in required fields') !== -1;

        if (!labels.length && looksLikeRequiredError) {
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
        $notice.attr('data-bw-required-error', '1');
    }

    function isRequiredErrorNotice($notice) {
        if (!$notice || !$notice.length || !$notice.hasClass('woocommerce-error')) {
            return false;
        }

        if ($notice.attr('data-bw-required-error') === '1') {
            return true;
        }

        var text = String($notice.text() || '').toLowerCase();
        return text.indexOf('please fill in required fields') !== -1 || text.indexOf('required field') !== -1;
    }

    function areVisibleRequiredFieldsFilled() {
        var $form = $('form.checkout');
        if (!$form.length) {
            return true;
        }

        var isValid = true;

        $form.find('.validate-required:visible').each(function () {
            var $row = $(this);
            var $input = $row.find('input, select, textarea').filter(function () {
                var $el = $(this);
                var type = ($el.attr('type') || '').toLowerCase();
                return !$el.prop('disabled') && type !== 'hidden';
            }).first();

            if (!$input.length) {
                return;
            }

            var type = ($input.attr('type') || '').toLowerCase();
            var fieldValid = true;

            if (type === 'checkbox') {
                fieldValid = $input.is(':checked');
            } else {
                fieldValid = $.trim($input.val() || '') !== '';
            }

            if (!fieldValid) {
                isValid = false;
                return false;
            }
        });

        return isValid;
    }

    function dismissRequiredErrorNotices() {
        var $allErrors = $('.woocommerce-checkout .woocommerce-error').filter(function () {
            return isRequiredErrorNotice($(this));
        });

        if (!$allErrors.length) {
            return;
        }

        $allErrors.attr('data-bw-dismissed', '1').addClass('is-dismissing');
        setTimeout(function () {
            $allErrors.remove();
        }, 220);
    }

    function scheduleRequiredErrorsAutoHide() {
        if (requiredErrorsTimer) {
            clearTimeout(requiredErrorsTimer);
        }

        var hasRequiredErrors = $('.woocommerce-checkout .woocommerce-error').filter(function () {
            return isRequiredErrorNotice($(this));
        }).length > 0;

        if (!hasRequiredErrors) {
            return;
        }

        requiredErrorsTimer = setTimeout(function () {
            dismissRequiredErrorNotices();
        }, REQUIRED_ERRORS_AUTO_HIDE_MS);
    }

    function maybeDismissRequiredErrorsOnValidInput() {
        if (areVisibleRequiredFieldsFilled()) {
            dismissRequiredErrorNotices();
        }
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
        const notices = $('.woocommerce-error, .woocommerce-message, .woocommerce-info')
            .not('.bw-checkout-left .woocommerce-error, .bw-checkout-left .woocommerce-message, .bw-checkout-left .woocommerce-info')
            .not('[data-bw-dismissed="1"]');

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

            scheduleRequiredErrorsAutoHide();
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
        setTimeout(maybeDismissRequiredErrorsOnValidInput, 250);
    });

    $(document).on('input change blur', 'form.checkout .validate-required input, form.checkout .validate-required select, form.checkout .validate-required textarea', function () {
        maybeDismissRequiredErrorsOnValidInput();
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
