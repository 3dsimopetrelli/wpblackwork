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

				// Update active state
				$buttons.removeClass('active');
				$button.addClass('active');

				// Update price display with fade animation
				if (price) {
					updatePriceDisplayWithFade($priceDisplay, price);
				}

				// Load and display license HTML with fade animation
				loadVariationLicenseHTMLWithFade(variationId, $licenseBox);

				// Update Add To Cart button for selected variation
				if ($addToCartButton.length && variationId) {
					updateAddToCartButton($addToCartButton, productId, variationId);
				}

				// Trigger custom event for other scripts (e.g., WooCommerce variations)
				$widget.trigger('bw_price_variation_changed', {
					variationId: variationId,
					price: price
				});
			});

			// Load initial license box (for the first active variation)
			const $activeButton = $buttons.filter('.active').first();
			if ($activeButton.length) {
				const initialVariationId = $activeButton.data('variation-id');
				loadVariationLicenseHTML(initialVariationId, $licenseBox);
			}
		});
	}

	/**
	 * Update price display with formatted price
	 */
	function updatePriceDisplay($priceDisplay, price) {
		// Use WooCommerce price format if available
		if (typeof accounting !== 'undefined' && typeof woocommerce_price_format !== 'undefined') {
			// Format only the number without currency symbol
			const formattedNumber = accounting.formatNumber(
				price,
				woocommerce_price_format.decimals,
				woocommerce_price_format.thousand_separator,
				woocommerce_price_format.decimal_separator
			);

			// Build price HTML with currency symbol in correct position
			const priceHTML = woocommerce_price_format.format
				.replace('%1$s', '<span class="woocommerce-Price-currencySymbol">' + woocommerce_price_format.symbol + '</span>')
				.replace('%2$s', formattedNumber);

			$priceDisplay.html('<span class="woocommerce-Price-amount amount">' + priceHTML + '</span>');
		} else {
			// Fallback: simple price display
			$priceDisplay.html('<span class="woocommerce-Price-amount amount">' + price + '</span>');
		}
	}

	/**
	 * Update price display with fade animation
	 */
	function updatePriceDisplayWithFade($priceDisplay, price) {
		// Fade out
		$priceDisplay.css('opacity', '0');

		// Update price after fade out (200ms)
		setTimeout(function() {
			updatePriceDisplay($priceDisplay, price);
			// Fade in
			$priceDisplay.css('opacity', '1');
		}, 200);
	}

	/**
	 * Update Add To Cart button for selected variation
	 */
	function updateAddToCartButton($button, productId, variationId) {
		if (!$button.length || !variationId) {
			return;
		}

		// Update data-variation_id attribute
		$button.attr('data-variation_id', variationId);

		// Update href to include variation_id parameter
		const baseUrl = $button.attr('href');
		if (baseUrl) {
			// Remove old variation_id parameter if exists
			let newUrl = baseUrl.split('?')[0];

			// Add variation_id parameter
			newUrl = newUrl + '?variation_id=' + variationId + '&add-to-cart=' + productId;

			$button.attr('href', newUrl);
		}

		// Remove 'added' class if it exists (from previous add to cart)
		$button.removeClass('added');
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
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		initPriceVariation();
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
