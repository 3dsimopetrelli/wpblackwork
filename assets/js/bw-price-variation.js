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

                // Handle stock status - disable button if out of stock
                if (variation.is_in_stock === false) {
                        $button.addClass('disabled').attr('aria-disabled', 'true');
                } else {
                        $button.removeClass('disabled').attr('aria-disabled', 'false');
                }

                // Build the proper URL with variation parameters
                if (baseHref) {
                        // Parse the base URL to extract the product permalink
                        const url = new URL(baseHref, window.location.origin);

                        // Clear old params and set new ones
                        url.searchParams.set('add-to-cart', productId);
                        url.searchParams.set('variation_id', variation.id);
                        url.searchParams.set('quantity', 1);

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
                        console.error('BW Price Variation: Missing required data', {button: $button.length, productId, variation});
                        return;
                }

                const quantity = parseInt($button.attr('data-quantity') || $button.data('quantity') || 1, 10) || 1;

                // Build the payload in the format WooCommerce expects
                const payload = {
                        'add-to-cart': productId,
                        product_id: productId,
                        variation_id: variation.id,
                        quantity: quantity
                };

                // Add variation attributes to the payload with proper keys
                if (variation.attributes && typeof variation.attributes === 'object') {
                        // Flatten attributes directly into payload and also pass variation array
                        payload.variation = {};

                        Object.keys(variation.attributes).forEach(function(key) {
                                const value = variation.attributes[key];
                                payload[key] = value;
                                payload.variation[key] = value;
                        });
                }

                // Add nonce if available for security
                if (typeof bwPriceVariation !== 'undefined' && bwPriceVariation.nonce) {
                        payload.security = bwPriceVariation.nonce;
                }

                if (window.BW_CartPopup && typeof BW_CartPopup.closeErrorModal === 'function') {
                        BW_CartPopup.closeErrorModal();
                }

                // Determine the AJAX URL
                let ajaxUrl = null;
                let useAdminAjax = false;

                // First try to use WooCommerce's wc-ajax endpoint (preferred for variations)
                if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
                        ajaxUrl = wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart');
                }
                // Fallback to standard WordPress AJAX with action parameter
                else if (typeof bwPriceVariation !== 'undefined' && bwPriceVariation.ajaxUrl) {
                        ajaxUrl = bwPriceVariation.ajaxUrl;
                        payload.action = 'woocommerce_add_to_cart';
                        useAdminAjax = true;
                }
                // Last resort: use the href URL (non-AJAX)
                else {
                        console.warn('BW Price Variation: No AJAX URL available, using fallback href');
                        window.location.href = $button.attr('href');
                        return;
                }

                $button.addClass('loading');
                $(document.body).trigger('adding_to_cart', [$button, payload]);

                $.post(ajaxUrl, payload)
                        .done(function(response) {
                                const hasFragments = response && response.fragments;
                                const isValidationError = response && (response.error || response.result === 'failure');

                                // WooCommerce signals validation errors with the `error` flag. Only treat as error
                                // when fragments are not present, so a successful add that also returns notices
                                // does not re-open the validation modal.
                                if (isValidationError && !hasFragments) {
                                        const rawMessage = response.messages || '';
                                        const parsedMessage = rawMessage ? $('<div/>').html(rawMessage).text().trim() : '';
                                        const fallbackMessage = parsedMessage || 'Please choose product options before adding to cart.';

                                        if (window.BW_CartPopup) {
                                                BW_CartPopup.openPanel();
                                                BW_CartPopup.showErrorModal(fallbackMessage);
                                        }

                                        return;
                                }

                                // Success!
                                $button.removeClass('added').addClass('added');
                                $(document.body).trigger('added_to_cart', [response.fragments || {}, response.cart_hash || '', $button]);

                                if (window.BW_CartPopup) {
                                        if (typeof BW_CartPopup.closeErrorModal === 'function') {
                                                BW_CartPopup.closeErrorModal();
                                        }
                                        BW_CartPopup.openPanel();
                                }
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                                console.error('BW Price Variation: AJAX request failed', {
                                        status: textStatus,
                                        error: errorThrown,
                                        responseText: jqXHR.responseText,
                                        statusCode: jqXHR.status
                                });

                                const errorMessage = 'Unable to add product to cart. Please try again.';

                                if (window.BW_CartPopup) {
                                        BW_CartPopup.openPanel();
                                        BW_CartPopup.showErrorModal(errorMessage);
                                }
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
                        console.warn('BW Price Variation: Widget element not found');
                        return;
                }

                const variations = parseVariations($widget.data('variations'));
                if (!variations.length) {
                        console.warn('BW Price Variation: No variations found');
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

                        // Block clicks on out-of-stock variations
                        if ($(this).hasClass('out-of-stock')) {
                                return false;
                        }

                        const buttonVariationId = $(this).data('variation-id');
                        const parsedVariation = $(this).data('variation');
                        const variationFromMap = variationMap[buttonVariationId];
                        const selectedVariation = variationFromMap || parsedVariation || null;

                        if (!selectedVariation || !selectedVariation.id) {
                                console.error('BW Price Variation: No variation data found for button');
                                return;
                        }

                        // Check if variation is in stock
                        if (selectedVariation.is_in_stock === false) {
                                return false;
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

                // Use direct event listener with capture phase to intercept before other handlers
                if ($addToCartButton.length) {
                        const addToCartBtn = $addToCartButton[0];

                        addToCartBtn.addEventListener('click', function(e) {
                                if ($(this).hasClass('disabled')) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        return false;
                                }

                                e.preventDefault();
                                e.stopImmediatePropagation();
                                handleAddToCart($(this), productId, activeVariation);
                        }, true); // true = capture phase
                }

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
