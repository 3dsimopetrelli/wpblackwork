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

			// Handle button clicks
			$buttons.on('click', function() {
				const $button = $(this);
				const variationId = $button.data('variation-id');
				const price = $button.data('price');

				// Update active state
				$buttons.removeClass('active');
				$button.addClass('active');

				// Update price display with fade animation
				if (price && typeof woocommerce_price_format !== 'undefined') {
					updatePriceDisplayWithFade($priceDisplay, price);
				}

				// Load and display license HTML with fade animation
				loadVariationLicenseHTMLWithFade(variationId, $licenseBox);

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
			const formattedPrice = accounting.formatMoney(
				price,
				woocommerce_price_format.symbol,
				woocommerce_price_format.decimal_separator,
				woocommerce_price_format.thousand_separator,
				woocommerce_price_format.decimals
			);

			const priceHTML = woocommerce_price_format.format
				.replace('%1$s', '<span class="woocommerce-Price-currencySymbol">' + woocommerce_price_format.symbol + '</span>')
				.replace('%2$s', formattedPrice.replace(woocommerce_price_format.symbol, ''));

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
	 * Load variation license HTML via AJAX
	 */
	function loadVariationLicenseHTML(variationId, $licenseBox) {
		if (!variationId) {
			$licenseBox.hide().html('');
			return;
		}

		// Show loading state
		$licenseBox.html('<p>Loading...</p>').show();

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
			// Show loading state
			$licenseBox.html('<p>Loading...</p>').fadeIn(200);

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
					// Fade out loading, then fade in new content
					$licenseBox.fadeOut(200, function() {
						if (response.success && response.data.html) {
							$licenseBox.html(response.data.html).fadeIn(200);
						} else {
							// No HTML content, hide the box
							$licenseBox.html('');
						}
					});
				},
				error: function() {
					// On error, hide the box
					$licenseBox.fadeOut(200, function() {
						$(this).html('');
					});
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
