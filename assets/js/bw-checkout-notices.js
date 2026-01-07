/**
 * BW Checkout Notices
 *
 * Moves WooCommerce notices into the left column of checkout
 */

(function($) {
    'use strict';

    /**
     * Move notices into left column
     */
    function moveNotices() {
        const leftColumn = $('.bw-checkout-left');

        if (!leftColumn.length) {
            return;
        }

        // Find all notices outside the form
        const notices = $('.woocommerce-checkout > .woocommerce-notices-wrapper, .woocommerce-checkout > .woocommerce-error, .woocommerce-checkout > .woocommerce-message, .woocommerce-checkout > .woocommerce-info, body.woocommerce-checkout > .woocommerce-notices-wrapper, body.woocommerce-checkout > .woocommerce-error, body.woocommerce-checkout > .woocommerce-message, body.woocommerce-checkout > .woocommerce-info');

        if (notices.length) {
            // Create container if it doesn't exist
            let container = leftColumn.find('.bw-checkout-notices-container');

            if (!container.length) {
                container = $('<div class="bw-checkout-notices-container"></div>');
                leftColumn.prepend(container);
            }

            // Move each notice into the container
            notices.each(function() {
                const notice = $(this);

                // If it's a wrapper, move its children
                if (notice.hasClass('woocommerce-notices-wrapper')) {
                    notice.children().appendTo(container);
                } else {
                    notice.appendTo(container);
                }
            });
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        moveNotices();
    });

    /**
     * Re-run after AJAX updates (e.g., after failed checkout submission)
     */
    $(document.body).on('checkout_error updated_checkout', function() {
        setTimeout(moveNotices, 100);
    });

})(jQuery);
