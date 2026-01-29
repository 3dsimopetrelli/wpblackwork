/**
 * Shopify-style Cart Interactions
 */
(function ($) {
    'use strict';

    var BW_Cart = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            var timeout;
            $(document.body).on('change', '.bw-cart-item__quantity input.qty', function () {
                var $input = $(this);

                // Clear any existing timeout to avoid multiple AJAX calls
                if (timeout) {
                    clearTimeout(timeout);
                }

                // Wait a bit before updating to allow multiple changes
                timeout = setTimeout(function () {
                    $('[name="update_cart"]').prop('disabled', false).trigger('click');
                }, 500);
            });

            // Handle plus/minus buttons
            $(document.body).on('click', '.bw-qty-pill button', function (e) {
                e.preventDefault();
                var $button = $(this);
                var $input = $button.parent().find('input.qty');
                var val = parseFloat($input.val()) || 0;
                var step = parseFloat($input.attr('step')) || 1;
                var min = parseFloat($input.attr('min')) || 0;
                var max = parseFloat($input.attr('max')) || Infinity;

                if ($button.hasClass('plus')) {
                    if (val + step <= max) {
                        $input.val(val + step).trigger('change');
                    }
                } else if ($button.hasClass('minus')) {
                    if (val - step >= min) {
                        $input.val(val - step).trigger('change');
                    }
                }
            });

            // Handle AJAX updates from WooCommerce
            $(document.body).on('updated_cart_totals', function () {
                // Re-initialize any elements if needed
                console.log('Cart totals updated');
            });
        }
    };

    $(document).ready(function () {
        BW_Cart.init();
    });

})(jQuery);
