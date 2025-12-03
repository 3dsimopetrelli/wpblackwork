/**
 * BW Price Variation Widget JavaScript
 */

(function($) {
	'use strict';

	/**
	 * Initialize Price Variation Widget
	 */
	function initPriceVariation() {
		$('.bw-price-variation').each(function() {
			const $widget = $(this);
			const $priceDisplay = $widget.find('.bw-price-variation__price');
                        const $buttons = $widget.find('.bw-price-variation__variation-button');
                        const $licenseBox = $widget.find('.bw-price-variation__license-box');
                        const $addToCartButton = $widget.find('.bw-add-to-cart-button');
                        const productId = $widget.data('product-id');

			// Handle button clicks
			$buttons.on('click', function() {
				const $button = $(this);
                                const variationId = $button.data('variation-id');
                                const price = $button.data('price');
                                const priceHtml = $button.data('price-html');
                                const sku = $button.data('sku');
                                const attributes = normalizeAttributes($button.data('attributes'));

				// Update active state
				$buttons.removeClass('active');
				$button.addClass('active');

				// Update price display with fade animation
                                if (hasPriceValue(price) || priceHtml) {
                                        updatePriceDisplayWithFade($priceDisplay, price, priceHtml);
                                }

				// Load and display license HTML with fade animation
				loadVariationLicenseHTMLWithFade(variationId, $licenseBox);

				// Update Add To Cart button for selected variation
                                if ($addToCartButton.length && variationId) {
                                        updateAddToCartButton($addToCartButton, productId, variationId, sku, attributes);
                                }

				// Trigger custom event for other scripts (e.g., WooCommerce variations)
				$widget.trigger('bw_price_variation_changed', {
					variationId: variationId,
                                        price: price,
                                        priceHtml: priceHtml,
                                        sku: sku,
                                        attributes: attributes
                                });
                        });

                        // Load initial license box (for the first active variation)
                        const $activeButton = $buttons.filter('.active').first();
                        if ($activeButton.length) {
                                const initialVariationId = $activeButton.data('variation-id');
                                const initialAttributes = normalizeAttributes($activeButton.data('attributes'));
                                const initialPrice = $activeButton.data('price');
                                const initialPriceHtml = $activeButton.data('price-html');

                                loadVariationLicenseHTML(initialVariationId, $licenseBox);

                                if ($addToCartButton.length && initialVariationId) {
                                        updateAddToCartButton($addToCartButton, productId, initialVariationId, $activeButton.data('sku'), initialAttributes);
                                }

                                if (hasPriceValue(initialPrice) || initialPriceHtml) {
                                        updatePriceDisplay($priceDisplay, initialPrice, initialPriceHtml);
                                }
                        }
                });
        }

	/**
	 * Update price display with formatted price
	 */
        function updatePriceDisplay($priceDisplay, price, priceHtml) {
                // If priceHtml is provided and not empty, use it
                if (priceHtml && priceHtml.trim() !== '') {
                        $priceDisplay.html(priceHtml);
                        return;
                }

                // Validate price value
                if (!hasPriceValue(price)) {
                        console.warn('BW Price Variation: Invalid price value', price);
                        return;
                }

                // Use WooCommerce price format if available
                if (typeof bwPriceVariation !== 'undefined' && typeof bwPriceVariation.priceFormat !== 'undefined') {
                        const priceFormat = bwPriceVariation.priceFormat;

			// Format the number
			let formattedNumber = parseFloat(price).toFixed(priceFormat.decimals);

			// Add thousand separator
			if (priceFormat.thousand_separator) {
				const parts = formattedNumber.split('.');
				parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, priceFormat.thousand_separator);
				formattedNumber = parts.join(priceFormat.decimal_separator);
			} else if (priceFormat.decimal_separator !== '.') {
				formattedNumber = formattedNumber.replace('.', priceFormat.decimal_separator);
			}

			// Build price HTML with currency symbol in correct position
			const priceHTML = priceFormat.format
				.replace('%1$s', '<span class="woocommerce-Price-currencySymbol">' + priceFormat.symbol + '</span>')
				.replace('%2$s', formattedNumber);

			$priceDisplay.html('<span class="woocommerce-Price-amount amount">' + priceHTML + '</span>');
		} else {
			// Fallback: If bwPriceVariation is not available (e.g., in Elementor editor)
                        // Try to get currency symbol from existing price display
                        const existingCurrency = $priceDisplay.find('.woocommerce-Price-currencySymbol').text();
                        const currencySymbol = existingCurrency || '€'; // Default to Euro if not found

                        const formattedPrice = parseFloat(price).toFixed(2);
                        $priceDisplay.html('<span class="woocommerce-Price-amount amount">' +
                                '<span class="woocommerce-Price-currencySymbol">' + currencySymbol + '</span>' +
                                formattedPrice + '</span>');
		}
	}

	/**
	 * Update price display with fade animation
	 */
        function updatePriceDisplayWithFade($priceDisplay, price, priceHtml) {
                // Fade out
                $priceDisplay.css('opacity', '0');

                // Update price after fade out (200ms)
                setTimeout(function() {
                        updatePriceDisplay($priceDisplay, price, priceHtml);
                        // Fade in
                        $priceDisplay.css('opacity', '1');
                }, 200);
        }

	/**
	 * Update Add To Cart button for selected variation
	 */
        function updateAddToCartButton($button, productId, variationId, sku, attributes) {
                if (!$button.length || !variationId) {
                        return;
                }

                const normalizedAttributes = normalizeAttributes(attributes);

                // Cache the original href to avoid stacking query strings
                if (!$button.data('original-href')) {
                        $button.data('original-href', $button.attr('href'));
                }

                // Update data attributes
                $button.attr('data-variation_id', variationId);
                $button.attr('data-product_sku', sku || '');
                $button.attr('data-product_id', productId);
                $button.attr('data-variation', JSON.stringify(normalizedAttributes || {}));
                $button.attr('data-quantity', 1);
                $button.data('variation', normalizedAttributes || {});
                $button.data('variation_id', variationId);
                $button.data('product_id', productId);
                $button.data('quantity', 1);

                // Update href to include variation_id parameter
                const baseUrl = $button.data('original-href') || $button.attr('href');
                if (baseUrl) {
                        // Remove old query parameters
                        let newUrl = baseUrl.split('?')[0];

			// Build query parameters
			const params = new URLSearchParams({
				'add-to-cart': productId,
				'variation_id': variationId
			});

			// Add variation attributes to query string
                        if (normalizedAttributes && typeof normalizedAttributes === 'object') {
                                for (const key in normalizedAttributes) {
                                        if (Object.prototype.hasOwnProperty.call(normalizedAttributes, key)) {
                                                params.append(key, normalizedAttributes[key]);
                                        }
                                }
                        }

			$button.attr('href', newUrl + '?' + params.toString());
		}

		// Remove 'added' class if it exists (from previous add to cart)
                $button.removeClass('added');
        }

        function normalizeAttributes(attributes) {
                if (!attributes) {
                        return {};
                }

                if (typeof attributes === 'string') {
                        try {
                                return JSON.parse(attributes);
                        } catch (e) {
                                return {};
                        }
                }

                return attributes;
        }

        function hasPriceValue(price) {
                return price === 0 || price === '0' || (price !== undefined && price !== null && price !== '');
        }

	/**
	 * Load variation license HTML via AJAX
	 */
	function loadVariationLicenseHTML(variationId, $licenseBox) {
		if (!variationId) {
			$licenseBox.hide().html('');
			return;
		}

		// Make AJAX request to get variation meta
		$.ajax({
			url: bwPriceVariation.ajaxUrl,
			type: 'POST',
			data: {
				action: 'bw_get_variation_license_html',
				variation_id: variationId,
				nonce: bwPriceVariation.nonce
			},
			success: function(response) {
				if (response.success && response.data.html) {
					$licenseBox.html(response.data.html).show();
				} else {
					// No HTML content, hide the box
					$licenseBox.hide().html('');
				}
			},
			error: function() {
				// On error, hide the box
				$licenseBox.hide().html('');
			}
		});
	}

	/**
	 * Load variation license HTML via AJAX with fade animation
	 */
	function loadVariationLicenseHTMLWithFade(variationId, $licenseBox) {
		if (!variationId) {
			$licenseBox.fadeOut(200, function() {
				$(this).html('');
			});
			return;
		}

		// Fade out current content
		$licenseBox.fadeOut(200, function() {
			// Make AJAX request to get variation meta
			$.ajax({
				url: bwPriceVariation.ajaxUrl,
				type: 'POST',
				data: {
					action: 'bw_get_variation_license_html',
					variation_id: variationId,
					nonce: bwPriceVariation.nonce
				},
				success: function(response) {
					if (response.success && response.data.html) {
						$licenseBox.html(response.data.html).fadeIn(200);
					} else {
						// No HTML content, keep box hidden
						$licenseBox.html('');
					}
				},
				error: function() {
					// On error, keep box hidden
					$licenseBox.html('');
				}
			});
		});
	}

	/**
	 * Handle Add to Cart button click for variations
	 */
	function handleAddToCartClick($button) {
		// If the button has the cart popup class, let that handler take care of it
		if ($button.hasClass('bw-btn-addtocart')) {
			return true; // Continue with default behavior (cart popup will handle it)
		}

		// Otherwise, handle it with standard WooCommerce AJAX
		const variationId = $button.attr('data-variation_id') || $button.data('variation_id');
		const productId = $button.attr('data-product_id') || $button.data('product_id');
		const quantity = $button.attr('data-quantity') || $button.data('quantity') || 1;
		const variation = $button.data('variation') || {};

		if (!variationId || !productId) {
			console.error('BW Price Variation: Missing product or variation ID');
			return false;
		}

		// Add to cart via AJAX
		const data = {
			action: 'woocommerce_add_to_cart',
			product_id: productId,
			variation_id: variationId,
			quantity: quantity,
			variation: variation
		};

		$.post(bwPriceVariation.ajaxUrl, data, function(response) {
			if (response && response.error) {
				console.error('BW Price Variation: Add to cart failed', response);
				// Fallback: redirect to product page
				window.location.href = $button.attr('href');
			} else {
				// Trigger WooCommerce fragments refresh
				$(document.body).trigger('wc_fragment_refresh');
				$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

				// Add 'added' class for visual feedback
				$button.addClass('added');
			}
		}).fail(function() {
			// On error, redirect to cart URL with parameters
			window.location.href = $button.attr('href');
		});

		return false; // Prevent default link behavior
	}

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		initPriceVariation();

		// Handle Add to Cart button clicks
		$(document).on('click', '.bw-add-to-cart-button', function(e) {
			const $button = $(this);

			// Skip if button is disabled
			if ($button.hasClass('disabled')) {
				e.preventDefault();
				return false;
			}

			// Let the cart popup handler take care of it if enabled
			if ($button.hasClass('bw-btn-addtocart')) {
				return true;
			}

			// Handle with our custom handler
			e.preventDefault();
			return handleAddToCartClick($button);
		});
	});

	/**
	 * Reinitialize for Elementor editor
	 */
	$(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction('frontend/element_ready/bw-price-variation.default', function($scope) {
			initPriceVariation();
		});
	});

})(jQuery);
