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

        function closeCartPopupError() {
                if (window.BW_CartPopup && typeof BW_CartPopup.closeErrorModal === 'function') {
                        BW_CartPopup.closeErrorModal();
                }
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
        function updateAddToCartButton($button, productId, selection, productMode) {
                if (!$button.length) {
                        return;
                }

                const baseHref = $button.data('originalHref') || $button.attr('href');
                if (baseHref && !$button.data('originalHref')) {
                        $button.data('originalHref', baseHref);
                }

                const isVariationSelection = productMode === 'variable' && selection && selection.id;
                const buttonAttrs = {
                        'data-product_id': productId,
                        'data-product-type': productMode || 'simple'
                };

                if (isVariationSelection) {
                        buttonAttrs['data-variation_id'] = selection.id;
                        buttonAttrs['data-variation'] = JSON.stringify(selection.attributes || {});
                        buttonAttrs['data-product_sku'] = selection.sku || '';
                        buttonAttrs['data-variation-price'] = selection.price || '';
                        buttonAttrs['data-variation-price-html'] = selection.price_html || '';
                        buttonAttrs['data-selected-variation-id'] = selection.id;
                } else {
                        buttonAttrs['data-product_sku'] = selection && selection.sku ? selection.sku : '';
                }

                $button.attr(buttonAttrs);

                if (!isVariationSelection) {
                        $button.removeAttr('data-variation_id data-variation data-selected-variation-id data-variation-price data-variation-price-html');
                }

                // Handle stock status - disable button if out of stock
                if (selection && selection.is_in_stock === false) {
                        $button.addClass('disabled').attr('aria-disabled', 'true');
                } else {
                        $button.removeClass('disabled').attr('aria-disabled', 'false');
                }

                // Build the proper URL with variation parameters when needed.
                if (baseHref) {
                        // Parse the base URL to extract the product permalink
                        const url = new URL(baseHref, window.location.origin);

                        // Clear old params and set new ones
                        url.searchParams.set('add-to-cart', productId);
                        url.searchParams.set('product_id', productId);
                        url.searchParams.set('quantity', 1);

                        if (isVariationSelection) {
                                url.searchParams.set('variation_id', selection.id);
                        }

                        if (isVariationSelection && selection.attributes && typeof selection.attributes === 'object') {
                                Object.entries(selection.attributes).forEach(function(entry) {
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
         * Update checkout/payment options link so it follows the active variation.
         * @param {jQuery} $link
         * @param {number} productId
         * @param {Object} variation
         * @param {string} checkoutUrl
         */
        function updatePaymentOptionsLink($link, productId, selection, checkoutUrl, productMode) {
                if (!$link.length || !checkoutUrl) {
                        return;
                }

                const url = new URL(checkoutUrl, window.location.origin);

                url.searchParams.set('add-to-cart', productId);
                url.searchParams.set('product_id', productId);
                url.searchParams.set('quantity', 1);

                const isVariationSelection = productMode === 'variable' && selection && selection.id;
                if (isVariationSelection) {
                        url.searchParams.set('variation_id', selection.id);
                }

                if (isVariationSelection && selection.attributes && typeof selection.attributes === 'object') {
                        Object.entries(selection.attributes).forEach(function(entry) {
                                const key = entry[0];
                                const value = entry[1];
                                if (value !== undefined && value !== null) {
                                        url.searchParams.set(key, value);
                                }
                        });
                }

                $link.attr({
                        href: url.toString(),
                        'data-product_id': productId,
                        'data-product-type': productMode || 'simple'
                });

                if (isVariationSelection) {
                        $link.attr('data-variation_id', selection.id);
                        $link.attr('data-selected-variation-id', selection.id);
                } else {
                        $link.removeAttr('data-variation_id data-selected-variation-id');
                }

                if (selection && selection.is_in_stock === false) {
                        $link.addClass('is-disabled').attr('aria-disabled', 'true');
                } else {
                        $link.removeClass('is-disabled').attr('aria-disabled', 'false');
                }
        }

        /**
         * Sync the WooCommerce-compatible cart form used by official PayPal smart buttons.
         * @param {jQuery} $form
         * @param {number} productId
         * @param {Object} variation
         */
        function syncPayPalCartForm($form, productId, variation) {
                if (!$form.length || !variation || !variation.id) {
                        return;
                }

                const ensureField = function(name, value, extraClass) {
                        let $field = $form.find('[name="' + name + '"]').first();

                        if (!$field.length) {
                                $field = $('<input>', {
                                        type: 'hidden',
                                        name: name
                                });

                                if (extraClass) {
                                        $field.addClass(extraClass);
                                }

                                $form.prepend($field);
                        }

                        $field.val(value).attr('value', value);
                        return $field;
                };

                ensureField('add-to-cart', productId);
                ensureField('product_id', productId);
                ensureField('quantity', 1);
                ensureField('variation_id', variation.id);

                const attrKeys = [];
                if (variation.attributes && typeof variation.attributes === 'object') {
                        Object.entries(variation.attributes).forEach(function(entry) {
                                const key = entry[0];
                                const value = entry[1] === undefined || entry[1] === null ? '' : entry[1];
                                attrKeys.push(key);

                                const $field = ensureField(key, value, 'bw-price-variation__variation-attribute');
                                $field.attr('data-attribute-name', key);
                        });
                }

                $form.find('.bw-price-variation__variation-attribute').each(function() {
                        const name = $(this).attr('name') || $(this).data('attribute-name');
                        if (name && attrKeys.indexOf(name) === -1) {
                                $(this).remove();
                        }
                });

                $form.attr({
                        'data-product_id': productId,
                        'data-variation_id': variation.id
                });

                const $priceMirror = $form.find('.bw-price-variation__paypal-price-mirror').first();
                if ($priceMirror.length) {
                        if (variation.price_html) {
                                $priceMirror.html(variation.price_html);
                        } else if (variation.price !== undefined && variation.price !== null && variation.price !== '') {
                                $priceMirror.html(formatPriceFromNumber(variation.price));
                        }
                }

                $form.trigger('change');
                $form.find('[name="variation_id"]').trigger('change');
                $(document.body).trigger('ppcp_refresh_payment_buttons');
        }

        /**
         * Check whether an element is visible in the current layout.
         * @param {HTMLElement} element
         * @returns {boolean}
         */
        function isVisibleInLayout(element) {
                if (!element) {
                        return false;
                }

                return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
        }

        /**
         * WooCommerce PayPal Payments binds product buttons to the first global form.cart.
         * On some templates a hidden duplicate WooCommerce product form is still present.
         * When no other visible product form exists, demote those hidden duplicates so the
         * official PayPal script can bind to the Price Variation cart form instead.
         * @param {jQuery} $form
         */
        function prioritizePayPalCartForm($form) {
                if (!$form.length) {
                        return;
                }

                const targetForm = $form.get(0);
                const forms = Array.from(document.querySelectorAll('form.cart'));

                if (!forms.length || forms[0] === targetForm) {
                        return;
                }

                const otherVisibleForms = forms.filter(function(form) {
                        return form !== targetForm && isVisibleInLayout(form);
                });

                if (otherVisibleForms.length) {
                        return;
                }

                forms.forEach(function(form) {
                        if (form === targetForm) {
                                form.classList.add('cart');
                                form.classList.add('variations_form');

                                form.querySelectorAll('[data-bw-paypal-original-name]').forEach(function(field) {
                                        if (!field.hasAttribute('name')) {
                                                field.setAttribute('name', field.getAttribute('data-bw-paypal-original-name'));
                                        }
                                });
                                return;
                        }

                        if (!form.dataset.bwPaypalDemoted) {
                                form.dataset.bwPaypalDemoted = 'yes';
                                form.classList.remove('cart');
                                form.classList.remove('variations_form');

                                form.querySelectorAll('[name]').forEach(function(field) {
                                        const name = field.getAttribute('name') || '';
                                        const isPayPalSensitiveField = [
                                                'add-to-cart',
                                                'product_id',
                                                'quantity',
                                                'variation_id'
                                        ].indexOf(name) !== -1 || name.indexOf('attribute_') === 0;

                                        if (!isPayPalSensitiveField) {
                                                return;
                                        }

                                        if (!field.dataset.bwPaypalOriginalName) {
                                                field.dataset.bwPaypalOriginalName = name;
                                        }

                                        field.removeAttribute('name');
                                });
                        }
                });

                $form.find('.ppc-button-wrapper').trigger('ppcp-reload-buttons');
                $(document).trigger('ppcp_refresh_payment_buttons');
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
        function handleAddToCart($button, productId, selection, productUrl) {
                if (!$button.length || !productId) {
                        console.error('BW Price Variation: Missing required data', {button: $button.length, productId, selection});
                        return;
                }

                const quantity = parseInt($button.attr('data-quantity') || $button.data('quantity') || 1, 10) || 1;
                const isVariationSelection = selection && selection.id;

                // Build the payload in the format WooCommerce expects
                const payload = {
                        'add-to-cart': productId,
                        product_id: productId,
                        quantity: quantity
                };

                // Add variation attributes to the payload with proper keys
                if (isVariationSelection) {
                        payload.variation_id = selection.id;
                        // Flatten attributes directly into payload and also pass variation array
                        payload.variation = {};

                        Object.keys(selection.attributes || {}).forEach(function(key) {
                                const value = selection.attributes[key];
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

                function isSoldIndividuallyMessage(message) {
                        if (!message) {
                                return false;
                        }

                        const normalized = message.toLowerCase();

                        return (
                                normalized.includes('cannot add another') ||
                                normalized.includes('already in your cart') ||
                                normalized.includes('already in the cart') ||
                                normalized.includes('già nel carrello') ||
                                normalized.includes('venduto singolarmente') ||
                                normalized.includes('solo 1') ||
                                normalized.includes('solo uno') ||
                                normalized.includes('only 1') ||
                                normalized.includes('only one') ||
                                normalized.includes('non puoi aggiungere')
                        );
                }

                // Determine the AJAX URL
                let ajaxUrl = null;

                // First try to use WooCommerce's wc-ajax endpoint (preferred for variations)
                if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
                        ajaxUrl = wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart');
                }
                // Fallback to standard WordPress AJAX with action parameter
                else if (typeof bwPriceVariation !== 'undefined' && bwPriceVariation.ajaxUrl) {
                        ajaxUrl = bwPriceVariation.ajaxUrl;
                        payload.action = 'woocommerce_add_to_cart';
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
                                const rawMessage = response && response.messages ? response.messages : '';
                                const parsedMessage = rawMessage ? $('<div/>').html(rawMessage).text().trim() : '';
                                const isSoldIndividually = isSoldIndividuallyMessage(parsedMessage);

                                if (isValidationError) {
                                        const targetUrl = productUrl || (response && response.product_url) || window.location.href;
                                        const returnLabel = (typeof bwPriceVariation !== 'undefined'
                                                && bwPriceVariation.labels
                                                && bwPriceVariation.labels.returnToProduct)
                                                ? bwPriceVariation.labels.returnToProduct
                                                : 'Return to product';

                                        if (window.BW_CartPopup) {
                                                BW_CartPopup.openPanel();

                                                if (isSoldIndividually && parsedMessage) {
                                                        BW_CartPopup.showErrorModal(parsedMessage, {
                                                                returnUrl: targetUrl,
                                                                returnLabel: returnLabel
                                                        });
                                                } else if (!hasFragments) {
                                                        closeCartPopupError();
                                                }
                                        }

                                        if (isSoldIndividually) {
                                                return;
                                        }
                                }

                                // Success!
                                $button.removeClass('added').addClass('added');
                                $(document.body).trigger('added_to_cart', [response.fragments || {}, response.cart_hash || '', $button]);

                                if (window.BW_CartPopup) {
                                        closeCartPopupError();
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

                                if (!selection || selection.is_in_stock !== false) {
                                        $button.removeClass('disabled');
                                        $button.attr('aria-disabled', 'false').removeAttr('disabled');
                                }
                        });
        }

        function ensureCartHasItems(force) {
                if (!window.BW_CartPopup || typeof BW_CartPopup.ensureCartItems !== 'function') {
                        return null;
                }

                try {
                        return BW_CartPopup.ensureCartItems(!!force);
                } catch (err) {
                        return null;
                }
        }

        function isVariationAlreadyInCart(productId, variationId) {
                if (!window.BW_CartPopup || typeof BW_CartPopup.hasCartVariation !== 'function') {
                        return false;
                }

                return BW_CartPopup.hasCartVariation(productId, variationId);
        }

        function showAlreadyInCartMessage() {
                if (!window.BW_CartPopup || typeof BW_CartPopup.showAlreadyInCartModal !== 'function') {
                        return;
                }

                const continueHandler = function() {
                        if (typeof BW_CartPopup.closeAlreadyInCartModal === 'function') {
                                BW_CartPopup.closeAlreadyInCartModal();
                        }
                };

                BW_CartPopup.showAlreadyInCartModal('This product is already in your cart.', null, {
                        continueUrl: null,
                        continueLabel: 'Continue shopping',
                        onContinue: continueHandler,
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

                if ($widget.data('bwPvInit')) {
                        return;
                }

                $widget.data('bwPvInit', true);

                const productType = (($widget.data('product-type') || '').toString()).toLowerCase();
                const hasVariationData = !!$widget.data('variations');
                const isVariableMode = productType === 'variable' || (!productType && hasVariationData);
                const isSimpleMode = productType === 'simple' || (!productType && !hasVariationData);
                const variations = isVariableMode ? parseVariations($widget.data('variations')) : [];

                if (isVariableMode && !variations.length) {
                        console.warn('BW Price Variation: No variations found');
                        return;
                }

                const variationMap = isVariableMode ? buildVariationMap(variations) : {};
                const productId = $widget.data('product-id');
                const $priceDisplay = $widget.find('.bw-price-variation__price');
                const $licenseBox = $widget.find('.bw-price-variation__license-box');
                const $buttons = $widget.find('.bw-price-variation__variation-button');
                const $addToCartButton = $widget.find('.bw-add-to-cart-button');
                const $paymentOptionsLink = $widget.find('.bw-price-variation__payment-options');
                const $payPalCartForm = $widget.find('.bw-price-variation__cart-form').first();
                const checkoutUrl = $widget.data('checkout-url');
                const productUrl = $widget.data('product-url');

                let activeSelection = null;
                if (isVariableMode) {
                        activeSelection = resolveDefaultVariation($widget, variations, variationMap);
                } else if (isSimpleMode) {
                        activeSelection = {
                                id: null,
                                sku: $addToCartButton.attr('data-product_sku') || '',
                                price: $priceDisplay.data('default-price'),
                                price_html: $priceDisplay.data('default-price-html') || '',
                                attributes: {},
                                is_in_stock: !$addToCartButton.hasClass('disabled'),
                        };
                }

                // No-op until the floating button is created below.
                // Will be replaced with the real sync function after floating ATC init.
                var syncFloatingAtc = function() {};

                if (activeSelection) {
                        updatePrice($priceDisplay, activeSelection);
                        updateAddToCartButton($addToCartButton, productId, activeSelection, isVariableMode ? 'variable' : 'simple');
                        updatePaymentOptionsLink($paymentOptionsLink, productId, activeSelection, checkoutUrl, isVariableMode ? 'variable' : 'simple');

                        if (isVariableMode && activeSelection.id) {
                                updateActiveButton($buttons, activeSelection.id);
                                updateLicenseBox($licenseBox, activeSelection);
                                prioritizePayPalCartForm($payPalCartForm);
                                syncPayPalCartForm($payPalCartForm, productId, activeSelection);
                        }
                }

                if (isVariableMode) {
                        $buttons.on('click', function(e) {
                                e.preventDefault();

                                // Block clicks on out-of-stock variations
                                if ($(this).hasClass('out-of-stock')) {
                                        return false;
                                }

                                const buttonVariationId = $(this).data('variation-id');
                                const selectedVariation = variationMap[buttonVariationId] || null;

                                if (!selectedVariation || !selectedVariation.id) {
                                        console.error('BW Price Variation: No variation data found for button');
                                        return;
                                }

                                // Check if variation is in stock
                                if (selectedVariation.is_in_stock === false) {
                                        return false;
                                }

                                activeSelection = selectedVariation;

                                updateActiveButton($buttons, selectedVariation.id);
                                updatePrice($priceDisplay, selectedVariation);
                                updateLicenseBox($licenseBox, selectedVariation);
                                updateAddToCartButton($addToCartButton, productId, selectedVariation, 'variable');
                                updatePaymentOptionsLink($paymentOptionsLink, productId, selectedVariation, checkoutUrl, 'variable');
                                prioritizePayPalCartForm($payPalCartForm);
                                syncPayPalCartForm($payPalCartForm, productId, selectedVariation);
                                syncFloatingAtc();

                                $widget.trigger('bw_price_variation_changed', {
                                        variationId: selectedVariation.id,
                                        price: selectedVariation.price,
                                        priceHtml: selectedVariation.price_html,
                                        sku: selectedVariation.sku,
                                        attributes: selectedVariation.attributes,
                                });
                        });
                }

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

                                const $btn = $(this);
                                const selectionForAdd = activeSelection;
                                const proceedAdd = function() {
                                        handleAddToCart($btn, productId, selectionForAdd, productUrl);
                                };

                                if (isVariableMode && selectionForAdd && selectionForAdd.id && window.BW_CartPopup) {
                                        const cartPromise = ensureCartHasItems(true);

                                        if (cartPromise && typeof cartPromise.then === 'function') {
                                                cartPromise
                                                        .then(function() {
                                                                if (isVariationAlreadyInCart(productId, selectionForAdd.id)) {
                                                                        showAlreadyInCartMessage();
                                                                        return;
                                                                }

                                                                proceedAdd();
                                                        })
                                                        .fail(function() {
                                                                proceedAdd();
                                                        });

                                                return;
                                        }

                                        if (isVariationAlreadyInCart(productId, selectionForAdd.id)) {
                                                showAlreadyInCartMessage();
                                                return;
                                        }
                                }

                                proceedAdd();
                        }, true); // true = capture phase
                }

                // -----------------------------------------------------------------
                // Floating Add to Cart
                //
                // Strategy: IntersectionObserver on .bw-add-to-cart-wrapper fires
                // only on viewport-entry/exit transitions — zero scroll-listener
                // overhead.  The floating button is a singleton appended to <body>
                // to escape all Elementor stacking contexts and overflow containers.
                // State sync is one-directional: widget → floating button.
                // Click delegates to the original button's native capture listener
                // so all ATC logic (AJAX, cart popup, sold-individually) runs once.
                //
                // Sticky Elementor column: if the widget column is position:sticky,
                // the wrapper never exits the viewport, the observer never fires
                // "not intersecting", and the floating button stays hidden — correct.
                // -----------------------------------------------------------------
                (function() {
                        // Do nothing inside the Elementor editor
                        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode()) {
                                return;
                        }

	                        // IntersectionObserver is available in all modern browsers;
	                        // graceful no-op on very old environments
	                        if (typeof IntersectionObserver === 'undefined') {
	                                return;
	                        }

	                        // Mobile-only CTA: below 481px the floating button turns into
	                        // a full-width bottom bar. Outside that range we do not
	                        // initialize the floating layer at all.
	                        var mobileMql = window.matchMedia('(max-width: 480px)');
	                        if (!mobileMql.matches) {
	                                return;
	                        }

	                        var $addToCartWrapper = $widget.find('.bw-add-to-cart-wrapper');
	                        if (!$addToCartWrapper.length || !$addToCartButton.length) {
	                                return;
	                        }

                        // ---------- Singleton floating button ----------
                        // One element for the whole page regardless of how many
                        // Price Variation widgets exist.
                        var $floatWrap = $('#bw-float-atc-wrap');
                        if (!$floatWrap.length) {
                                $floatWrap = $(
                                        '<div id="bw-float-atc-wrap" class="bw-float-atc-wrap" aria-hidden="true">' +
                                        '<button class="bw-float-atc" type="button" tabindex="-1">Add to Cart</button>' +
                                        '</div>'
                                );
                                $('body').append($floatWrap);
                        }
                        var $floatBtn = $floatWrap.find('.bw-float-atc');

                        // ---------- Visual style copy ----------
                        // The original button's appearance is controlled by the Elementor
                        // panel (border-radius, colors, font, padding, etc.) and emitted as
                        // scoped inline <style> tags — we can't reference them from <body>.
                        // Solution: read the live computed styles once at init and apply them
                        // as inline styles on the floating button.  Only layout-specific
                        // overrides (min-width, shadow) remain in bw-price-variation.css.
                        (function() {
                                var cs = window.getComputedStyle($addToCartButton[0]);
                                $floatBtn.css({
                                        'background-color' : cs.backgroundColor,
                                        'color'            : cs.color,
                                        'border-radius'    : cs.borderRadius,
                                        'border-top'       : cs.borderTopWidth    + ' ' + cs.borderTopStyle    + ' ' + cs.borderTopColor,
                                        'border-right'     : cs.borderRightWidth  + ' ' + cs.borderRightStyle  + ' ' + cs.borderRightColor,
                                        'border-bottom'    : cs.borderBottomWidth + ' ' + cs.borderBottomStyle + ' ' + cs.borderBottomColor,
                                        'border-left'      : cs.borderLeftWidth   + ' ' + cs.borderLeftStyle   + ' ' + cs.borderLeftColor,
                                        'font-size'        : cs.fontSize,
                                        'font-weight'      : cs.fontWeight,
                                        'font-family'      : cs.fontFamily,
                                        'letter-spacing'   : cs.letterSpacing,
                                        'text-transform'   : cs.textTransform,
                                        'padding-top'      : cs.paddingTop,
                                        'padding-right'    : cs.paddingRight,
                                        'padding-bottom'   : cs.paddingBottom,
                                        'padding-left'     : cs.paddingLeft,
                                        'line-height'      : cs.lineHeight,
                                });
                        })();

                        // ---------- State sync ----------
                        // Mirror label and disabled state from the original button.
                        // Called once at init and on every variation change.
                        syncFloatingAtc = function() {
                                var text = $addToCartButton.text().trim();
                                $floatBtn.text(text || 'Add to Cart');

                                var isOos     = activeSelection && activeSelection.is_in_stock === false;
                                var isDisabled = !activeSelection || isOos || $addToCartButton.hasClass('disabled');
                                $floatBtn
                                        .prop('disabled', isDisabled)
                                        .toggleClass('is-disabled', isDisabled);
                        };

                        // Initial sync
                        syncFloatingAtc();

                        // ---------- Click delegation ----------
                        // Dispatch a real native MouseEvent so the original button's
                        // capture-phase listener fires exactly as if the user clicked it.
                        $floatWrap
                                .off('click.bwFloatAtc')
                                .on('click.bwFloatAtc', '.bw-float-atc', function() {
                                        if ($(this).prop('disabled')) {
                                                return;
                                        }
                                        $addToCartButton[0].dispatchEvent(
                                                new MouseEvent('click', { bubbles: true, cancelable: true })
                                        );
                                });

                        // ---------- Visibility observer ----------
                        // threshold:0 → callback fires on every entry/exit transition.
                        //
                        // Key fix: !isIntersecting alone is not enough — it is also true
                        // when the button has NOT YET been reached (below the fold on load).
                        // We show the floating CTA only when the original button is ABOVE
                        // the viewport (user has scrolled past it downward).
                        // entry.boundingClientRect.top < 0 → element is above viewport top.
                        var floatObserver = new IntersectionObserver(function(entries) {
                                var entry     = entries[0];
                                var isVisible = entry.isIntersecting;
                                var showFloat = !isVisible && entry.boundingClientRect.top < 0;
                                $floatWrap
                                        .toggleClass('is-visible', showFloat)
                                        .attr('aria-hidden', showFloat ? 'false' : 'true');
                        }, { threshold: 0 });

                        floatObserver.observe($addToCartWrapper[0]);
                })();
        }

        /**
         * Initialize the license box accordion.
         * @param {jQuery} $widget
         */
        function initLicenseAccordion($widget) {
                var $accordion = $widget.find('.bw-price-variation__license-accordion');
                if (!$accordion.length) return;
                if ($accordion.data('bwLicAcc')) return;
                $accordion.data('bwLicAcc', true);

                var hasMobile  = $accordion.hasClass('bw-license-accordion--mobile');
                var hasDesktop = $accordion.hasClass('bw-license-accordion--desktop');

                if (!hasMobile && !hasDesktop) {
                        hasMobile  = true;
                        hasDesktop = true;
                }

                var $trigger = $accordion.find('> button.bw-license-accordion__trigger');
                var $body    = $accordion.find('> .bw-license-accordion__body');
                var bodyEl   = $body[0];
                var mql      = window.matchMedia('(min-width: 1025px)');
                var timer    = null;

                var OPEN_MS      = 360;
                var CLOSE_MS     = 280;
                var OPEN_EASING  = 'cubic-bezier(0.16, 1, 0.3, 1)';
                var CLOSE_EASING = 'cubic-bezier(0.4, 0, 0.2, 1)';

                function requestStickyRefresh() {
                        $(window).trigger('bw:sticky-sidebar:refresh');
                }

                function setTrans(ms, easing, opMs) {
                        bodyEl.style.transition = 'height ' + ms + 'ms ' + easing + ', opacity ' + opMs + 'ms ease';
                }
                function clearTrans() { bodyEl.style.transition = ''; }

                function activate() {
                        clearTimeout(timer);
                        clearTrans();
                        bodyEl.style.height   = '0';
                        bodyEl.style.overflow = 'hidden';
                        bodyEl.style.opacity  = '0';
                        $accordion.addClass('bw-js-accordion-active').removeClass('is-open');
                        $trigger.attr('aria-expanded', 'false');
                        $body.attr('aria-hidden', 'true');
                        requestStickyRefresh();
                }

                function deactivate() {
                        clearTimeout(timer);
                        clearTrans();
                        $accordion.removeClass('bw-js-accordion-active is-open');
                        bodyEl.style.cssText = '';
                        $trigger.attr('aria-expanded', 'false');
                        $body.attr('aria-hidden', 'false');
                        requestStickyRefresh();
                }

                function openAccordion() {
                        if ($accordion.hasClass('is-open')) return;
                        clearTimeout(timer);

                        $accordion.addClass('is-open');
                        $trigger.attr('aria-expanded', 'true');
                        $body.attr('aria-hidden', 'false');

                        clearTrans();
                        bodyEl.style.height   = '0';
                        bodyEl.style.overflow = 'hidden';
                        bodyEl.style.opacity  = '0';
                        requestStickyRefresh();

                        var targetH = bodyEl.scrollHeight;
                        // eslint-disable-next-line no-unused-expressions
                        bodyEl.offsetHeight; // forced reflow

                        setTrans(OPEN_MS, OPEN_EASING, Math.round(OPEN_MS * 0.75));
                        bodyEl.style.height  = targetH + 'px';
                        bodyEl.style.opacity = '1';

                        timer = setTimeout(function() {
                                if ($accordion.hasClass('is-open')) {
                                        clearTrans();
                                        bodyEl.style.height   = '';
                                        bodyEl.style.overflow = '';
                                        requestStickyRefresh();
                                }
                        }, OPEN_MS + 80);
                }

                function closeAccordion() {
                        if (!$accordion.hasClass('is-open')) return;
                        clearTimeout(timer);

                        var currentH = bodyEl.getBoundingClientRect().height;
                        clearTrans();
                        bodyEl.style.height   = currentH + 'px';
                        bodyEl.style.overflow = 'hidden';

                        // eslint-disable-next-line no-unused-expressions
                        bodyEl.offsetHeight; // forced reflow

                        $accordion.removeClass('is-open');
                        $trigger.attr('aria-expanded', 'false');
                        $body.attr('aria-hidden', 'true');
                        requestStickyRefresh();

                        // eslint-disable-next-line no-unused-expressions
                        bodyEl.offsetHeight; // commit is-open removal

                        setTrans(CLOSE_MS, CLOSE_EASING, Math.round(CLOSE_MS * 0.6));
                        bodyEl.style.height  = '0';
                        bodyEl.style.opacity = '0';

                        timer = setTimeout(function() {
                                clearTrans();
                                requestStickyRefresh();
                        }, CLOSE_MS + 80);
                }

                function updateMode() {
                        if (mql.matches ? hasDesktop : hasMobile) {
                                activate();
                        } else {
                                deactivate();
                        }
                }

                updateMode();

                if (mql.addEventListener) {
                        mql.addEventListener('change', updateMode);
                } else {
                        mql.addListener(updateMode);
                }

                $trigger.on('click.bwAccordion', function() {
                        if (!$accordion.hasClass('bw-js-accordion-active')) return;
                        if ($accordion.hasClass('is-open')) {
                                closeAccordion();
                        } else {
                                openAccordion();
                        }
                });
        }

        $(document).ready(function() {
                $('.bw-price-variation').each(function() {
                        initPriceVariationWidget($(this));
                        initLicenseAccordion($(this));
                });
        });

        $(window).on('elementor/frontend/init', function() {
                if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
                        elementorFrontend.hooks.addAction('frontend/element_ready/bw-price-variation.default', function($scope) {
                                const $widget = $scope.find('.bw-price-variation');
                                if ($widget.length) {
                                        initPriceVariationWidget($widget);
                                        initLicenseAccordion($widget);
                                }
                        });
                }
        });
})(jQuery);
