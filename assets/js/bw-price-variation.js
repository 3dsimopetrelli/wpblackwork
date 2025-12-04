/**
 * BW Price Variation Widget
 * Ricostruito per sfruttare gli hook WooCommerce e il cart popup.
 */

(function($) {
        'use strict';

        /**
         * Parse variations array from data attribute.
         * @param {string|Array} raw
         * @returns {Array}
         */
        function parseVariations(raw) {
                if (Array.isArray(raw)) {
                        return raw;
                }

                if (!raw) {
                        return [];
                }

                try {
                        return JSON.parse(raw);
                } catch (e) {
                        return [];
                }
        }

        /**
         * Create a variation map indexed by ID.
         * @param {Array} variations
         * @returns {Object}
         */
        function buildVariationMap(variations) {
                return variations.reduce(function(map, variation) {
                        if (variation && variation.id) {
                                map[variation.id] = variation;
                        }
                        return map;
                }, {});
        }

        /**
         * Format a numeric price using WooCommerce settings when price HTML is missing.
         * @param {*} price
         * @returns {string}
         */
        function formatPriceFromNumber(price) {
                if (price === undefined || price === null || price === '') {
                        return '';
                }

                if (typeof bwPriceVariation !== 'undefined' && bwPriceVariation.priceFormat) {
                        const format = bwPriceVariation.priceFormat;
                        const decimals = typeof format.decimals === 'number' ? format.decimals : 2;
                        let formatted = parseFloat(price).toFixed(decimals);

                        if (format.thousand_separator) {
                                const parts = formatted.split('.');
                                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, format.thousand_separator);
                                formatted = parts.join(format.decimal_separator || ',');
                        } else if (format.decimal_separator && format.decimal_separator !== '.') {
                                formatted = formatted.replace('.', format.decimal_separator);
                        }

                        const priceHTML = format.format
                                .replace('%1$s', '<span class="woocommerce-Price-currencySymbol">' + format.symbol + '</span>')
                                .replace('%2$s', formatted);

                        return '<span class="woocommerce-Price-amount amount">' + priceHTML + '</span>';
                }

                return '<span class="woocommerce-Price-amount amount">' + price + '</span>';
        }

        /**
         * Update the price display area.
         * @param {jQuery} $priceDisplay
         * @param {Object} variation
         */
        function updatePrice($priceDisplay, variation) {
                const defaultPriceHtml = $priceDisplay.data('default-price-html');
                const defaultPrice = $priceDisplay.data('default-price');
                const newHtml = variation && variation.price_html ? variation.price_html : '';

                if (newHtml) {
                        $priceDisplay.html(newHtml);
                        return;
                }

                if (variation && variation.price !== undefined && variation.price !== null && variation.price !== '') {
                        $priceDisplay.html(formatPriceFromNumber(variation.price));
                        return;
                }

                if (defaultPriceHtml) {
                        $priceDisplay.html(defaultPriceHtml);
                        return;
                }

                if (defaultPrice || defaultPrice === 0 || defaultPrice === '0') {
                        $priceDisplay.html(formatPriceFromNumber(defaultPrice));
                }
        }

        /**
         * Update the license/terms box.
         * @param {jQuery} $licenseBox
         * @param {Object} variation
         */
        function updateLicenseBox($licenseBox, variation) {
                if (!variation || !variation.license_html) {
                        $licenseBox.stop(true, true).fadeOut(150, function() {
                                $(this).empty();
                        });
                        return;
                }

                $licenseBox.stop(true, true);

                if ($licenseBox.is(':visible')) {
                        $licenseBox.fadeOut(100, function() {
                                $(this).html(variation.license_html).fadeIn(150);
                        });
                } else {
                        $licenseBox.html(variation.license_html).fadeIn(150);
                }
        }

        /**
         * Update button active state.
         * @param {jQuery} $buttons
         * @param {number} activeId
         */
        function updateActiveButton($buttons, activeId) {
                $buttons.removeClass('active').attr('aria-pressed', 'false');
                const $active = $buttons.filter('[data-variation-id="' + activeId + '"]').first();
                if ($active.length) {
                        $active.addClass('active').attr('aria-pressed', 'true');
                }
        }

        /**
         * Update Add to Cart button attributes and URL for the selected variation.
         * @param {jQuery} $button
         * @param {number} productId
         * @param {Object} variation
         */
        function updateAddToCartButton($button, productId, variation) {
                if (!$button.length || !variation) {
                        return;
                }

                const baseHref = $button.data('originalHref') || $button.attr('href');
                if (baseHref && !$button.data('originalHref')) {
                        $button.data('originalHref', baseHref);
                }

                $button.attr({
                        'data-product_id': productId,
                        'data-variation_id': variation.id,
                        'data-variation': JSON.stringify(variation.attributes || {}),
                        'data-product_sku': variation.sku || '',
                        'data-variation-price': variation.price || '',
                        'data-variation-price-html': variation.price_html || '',
                        'data-selected-variation-id': variation.id,
                });

                if (baseHref) {
                        const url = new URL(baseHref, window.location.origin);
                        url.searchParams.set('add-to-cart', productId);
                        url.searchParams.set('variation_id', variation.id);

                        if (variation.attributes && typeof variation.attributes === 'object') {
                                Object.entries(variation.attributes).forEach(function(entry) {
                                        const key = entry[0];
                                        const value = entry[1];
                                        if (value !== undefined && value !== null) {
                                                url.searchParams.set(key, value);
                                        }
                                });
                        }

                        $button.attr('href', url.toString());
                }
        }

        /**
         * Pick the variation to use as default.
         * @param {jQuery} $widget
         * @param {Array} variations
         * @param {Object} variationMap
         * @returns {Object|null}
         */
        function resolveDefaultVariation($widget, variations, variationMap) {
                const preferredId = parseInt($widget.data('default-variation-id'), 10);
                if (preferredId && variationMap[preferredId]) {
                        return variationMap[preferredId];
                }

                const $activeButton = $widget.find('.bw-price-variation__variation-button.active').first();
                const activeId = $activeButton.data('variation-id');
                if (activeId && variationMap[activeId]) {
                        return variationMap[activeId];
                }

                const firstInStock = variations.find(function(variation) {
                        return variation && variation.is_in_stock;
                });

                return firstInStock || variations[0] || null;
        }

        /**
         * Handle Add to Cart through WooCommerce AJAX endpoint.
         * @param {jQuery} $button
         * @param {number} productId
         * @param {Object} variation
         */
        function handleAddToCart($button, productId, variation) {
                if (!$button.length || !productId || !variation || !variation.id) {
                        return;
                }

                const quantity = parseInt($button.attr('data-quantity') || $button.data('quantity') || 1, 10) || 1;
                const payload = {
                        product_id: productId,
                        variation_id: variation.id,
                        quantity: quantity,
                        variation: variation.attributes || {},
                };

                let ajaxUrl = null;

                if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
                        ajaxUrl = wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart');
                } else if (typeof bwPriceVariation !== 'undefined' && bwPriceVariation.ajaxUrl) {
                        ajaxUrl = bwPriceVariation.ajaxUrl + '?action=woocommerce_add_to_cart';
                }

                if (!ajaxUrl) {
                        window.location.href = $button.attr('href');
                        return;
                }

                $button.addClass('loading');
                $(document.body).trigger('adding_to_cart', [$button, payload]);

                $.post(ajaxUrl, payload)
                        .done(function(response) {
                                if (response && response.error && response.product_url) {
                                        window.location.href = response.product_url;
                                        return;
                                }

                                $button.removeClass('added').addClass('added');
                                $(document.body).trigger('added_to_cart', [response.fragments || {}, response.cart_hash || '', $button]);
                        })
                        .fail(function() {
                                window.location.href = $button.attr('href');
                        })
                        .always(function() {
                                $button.removeClass('loading');
                        });
        }

        /**
         * Initialize a single widget instance.
         * @param {jQuery} $widget
         */
        function initPriceVariationWidget($widget) {
                if (!$widget || !$widget.length) {
                        return;
                }

                const variations = parseVariations($widget.data('variations'));
                if (!variations.length) {
                        return;
                }

                const variationMap = buildVariationMap(variations);
                const productId = $widget.data('product-id');
                const $priceDisplay = $widget.find('.bw-price-variation__price');
                const $licenseBox = $widget.find('.bw-price-variation__license-box');
                const $buttons = $widget.find('.bw-price-variation__variation-button');
                const $addToCartButton = $widget.find('.bw-add-to-cart-button');

                let activeVariation = resolveDefaultVariation($widget, variations, variationMap);

                if (activeVariation) {
                        updateActiveButton($buttons, activeVariation.id);
                        updatePrice($priceDisplay, activeVariation);
                        updateLicenseBox($licenseBox, activeVariation);
                        updateAddToCartButton($addToCartButton, productId, activeVariation);
                }

                $buttons.on('click', function(e) {
                        e.preventDefault();
                        const buttonVariationId = $(this).data('variation-id');
                        const parsedVariation = $(this).data('variation');
                        const variationFromMap = variationMap[buttonVariationId];
                        const selectedVariation = variationFromMap || parsedVariation || null;

                        if (!selectedVariation || !selectedVariation.id) {
                                return;
                        }

                        activeVariation = selectedVariation;

                        updateActiveButton($buttons, selectedVariation.id);
                        updatePrice($priceDisplay, selectedVariation);
                        updateLicenseBox($licenseBox, selectedVariation);
                        updateAddToCartButton($addToCartButton, productId, selectedVariation);

                        $widget.trigger('bw_price_variation_changed', {
                                variationId: selectedVariation.id,
                                price: selectedVariation.price,
                                priceHtml: selectedVariation.price_html,
                                sku: selectedVariation.sku,
                                attributes: selectedVariation.attributes,
                        });
                });

                $widget.on('click', '.bw-add-to-cart-button', function(e) {
                        const $btn = $(this);
                        if ($btn.hasClass('disabled')) {
                                e.preventDefault();
                                return;
                        }

                        e.preventDefault();
                        e.stopImmediatePropagation();
                        handleAddToCart($btn, productId, activeVariation);
                });
        }

        $(document).ready(function() {
                $('.bw-price-variation').each(function() {
                        initPriceVariationWidget($(this));
                });
        });

        $(window).on('elementor/frontend/init', function() {
                if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
                        elementorFrontend.hooks.addAction('frontend/element_ready/bw-price-variation.default', function($scope) {
                                const $widget = $scope.find('.bw-price-variation');
                                if ($widget.length) {
                                        initPriceVariationWidget($widget);
                                }
                        });
                }
        });
})(jQuery);
