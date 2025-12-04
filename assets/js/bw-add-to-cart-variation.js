(function($) {
    'use strict';

    function buildUrlWithAttributes(baseUrl, productId, variation) {
        if (!baseUrl || !productId || !variation || !variation.variation_id) {
            return baseUrl;
        }

        const url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('add-to-cart', productId);
        url.searchParams.set('variation_id', variation.variation_id);

        if (variation.attributes) {
            Object.entries(variation.attributes).forEach(([key, value]) => {
                url.searchParams.set(key, value);
            });
        }

        return url.toString();
    }

    function fadeSwap($element, newHtml, duration) {
        if (!$element || !$element.length) {
            return;
        }

        const speed = duration || 200;
        $element.stop(true, true).fadeTo(speed, 0, function() {
            $element.html(newHtml);
            $element.fadeTo(speed, 1);
        });
    }

    function initWidget($widget) {
        if (!$widget || !$widget.length) {
            return;
        }

        const $form = $widget.find('.variations_form');
        const $price = $widget.find('.bw-add-to-cart-variation__price');
        const $license = $widget.find('.bw-add-to-cart-variation__license-box');
        const $buttons = $widget.find('.bw-add-to-cart-variation__variation-button');
        const $addToCart = $widget.find('.bw-add-to-cart-variation__add-to-cart');
        const mainAttribute = $widget.data('main-attribute');
        const animationDuration = parseInt($widget.data('animation-duration'), 10) || 200;
        const productId = parseInt($widget.data('product-id'), 10) || 0;
        const productUrl = $widget.data('product-url');

        if (!$form.length) {
            return;
        }

        // Ensure WooCommerce variation script is attached
        if (typeof $form.wc_variation_form === 'function') {
            $form.wc_variation_form();
        }

        let activeVariation = null;

        function setButtonState(value) {
            $buttons.removeClass('is-active');
            $buttons.filter('[data-value="' + value + '"]').addClass('is-active');
        }

        function updateAddToCart(variation) {
            if (!variation || !variation.variation_id || variation.is_in_stock === false) {
                $addToCart.addClass('disabled');
                $addToCart.removeAttr('href');
                return;
            }

            const href = buildUrlWithAttributes(productUrl, productId, variation);
            $addToCart
                .removeClass('disabled')
                .attr('href', href)
                .attr('data-product_id', productId)
                .attr('data-product_sku', variation.sku || '')
                .attr('data-variation_id', variation.variation_id)
                .attr('data-quantity', 1);
        }

        function updateDisplay(variation) {
            if (!variation) {
                return;
            }

            if (variation.price_html) {
                fadeSwap($price, variation.price_html, animationDuration);
            }

            const licenseHtml = variation.bw_license_html || '';
            if (licenseHtml) {
                $license.removeClass('is-hidden');
                fadeSwap($license, licenseHtml, animationDuration);
            } else {
                $license.addClass('is-hidden').empty();
            }

            updateAddToCart(variation);
        }

        $form.on('found_variation', function(event, variation) {
            activeVariation = variation;
            updateDisplay(variation);
        });

        $form.on('reset_data hide_variation', function() {
            activeVariation = null;
            $addToCart.addClass('disabled');
            $license.addClass('is-hidden').empty();
        });

        $buttons.on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);

            if ($btn.hasClass('is-disabled')) {
                return;
            }

            const value = $btn.data('value');
            const $select = $form.find('select[name="attribute_' + mainAttribute + '"]');

            if ($select.length) {
                $select.val(value).trigger('change');
                setButtonState(value);
            }
        });

        $addToCart.on('click', function(e) {
            if ($(this).hasClass('disabled')) {
                e.preventDefault();
                return false;
            }

            // If open cart popup is disabled we just let the link work.
            if (!$(this).data('open-cart-popup')) {
                return;
            }

            // Allow cart popup script to handle AJAX; prevent double navigation.
            if ($(this).attr('href')) {
                e.preventDefault();
            }
        });

        // Pre-select the active button if one is marked
        const $initialActive = $buttons.filter('.is-active').first();
        if ($initialActive.length) {
            $initialActive.trigger('click');
        } else {
            $form.trigger('check_variations');
        }
    }

    function initAll(scope) {
        const $widgets = scope ? scope.find('.bw-add-to-cart-variation') : $('.bw-add-to-cart-variation');
        $widgets.each(function() {
            initWidget($(this));
        });
    }

    $(document).ready(function() {
        initAll($(document.body));
    });

    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/bw-add-to-cart-variation.default', function($scope) {
                initAll($scope);
            });
        }
    });
})(jQuery);
