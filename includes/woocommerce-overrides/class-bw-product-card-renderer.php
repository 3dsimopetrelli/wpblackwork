<?php
/**
 * BW Product Card Renderer
 *
 * Centralizzato renderer per le card prodotto in stile BW Wallpost.
 * Utilizzato da widget e override WooCommerce.
 *
 * @package BW_Main_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BW_Product_Card_Renderer
 *
 * Gestisce il rendering delle card prodotto con lo stesso stile del BW Wallpost widget.
 */
class BW_Product_Card_Renderer {

	/**
	 * Default settings per le card
	 *
	 * @var array
	 */
	private static $default_settings = [
		'image_size'          => 'large',
		'show_image'          => true,
		'show_hover_image'    => true,
		'show_title'          => true,
		'show_description'    => false,
		'show_price'          => true,
		'show_buttons'        => true,
		'show_add_to_cart'    => true,
		'open_cart_popup'     => false,
		'image_classes'       => '',
		'card_classes'        => '',
		'wrapper_classes'     => '',
	];

	/**
	 * Render a product card
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @param array          $settings Card settings.
	 * @return string HTML markup.
	 */
	public static function render_card( $product, $settings = [] ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product || ! $product instanceof WC_Product ) {
			return '';
		}

		// Merge settings con defaults
		$settings = wp_parse_args( $settings, self::$default_settings );

		$post_id    = $product->get_id();
		$permalink  = function_exists( 'bw_get_safe_product_permalink' )
			? bw_get_safe_product_permalink( $product )
			: $product->get_permalink();
		$title      = $product->get_name();
		$excerpt    = $product->get_short_description();

		// Se non c'è short description, prendi dal contenuto
		if ( empty( $excerpt ) ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 30 );
		}

		// Wrap in paragraph if needed
		if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
			$excerpt = '<p>' . $excerpt . '</p>';
		}

		// Build classes
		$item_classes = [ 'bw-wallpost-item', 'bw-slick-item', 'bw-product-card-item' ];
		if ( ! empty( $settings['wrapper_classes'] ) ) {
			$item_classes[] = $settings['wrapper_classes'];
		}

		$card_classes = [ 'bw-wallpost-card', 'bw-slick-item__inner', 'bw-ss__card', 'bw-product-card' ];
		if ( ! empty( $settings['card_classes'] ) ) {
			$card_classes[] = $settings['card_classes'];
		}

		ob_start();
		?>
		<article <?php post_class( $item_classes, $post_id ); ?>>
			<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_classes ) ) ); ?>">
				<div class="bw-slider-image-container">
					<?php echo self::render_product_image( $product, $settings ); ?>
				</div>

				<div class="bw-wallpost-content bw-slick-item__content bw-ss__content bw-slider-content bw-slick-slider-text-box">
					<?php echo self::render_product_content( $product, $settings ); ?>
				</div>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render product image section
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $settings Settings.
	 * @return string HTML markup.
	 */
	private static function render_product_image( $product, $settings ) {
		if ( ! $settings['show_image'] ) {
			return '';
		}

		$post_id      = $product->get_id();
		$permalink    = function_exists( 'bw_get_safe_product_permalink' )
			? bw_get_safe_product_permalink( $product )
			: $product->get_permalink();
		$title        = $product->get_name();
		$image_size   = $settings['image_size'];

		// Get main image
		$thumbnail_html = '';
		$image_id       = $product->get_image_id();

		if ( $image_id ) {
			$thumbnail_args = [
				'loading' => 'lazy',
				'class'   => 'bw-slider-main',
			];

			$thumbnail_html = wp_get_attachment_image( $image_id, $image_size, false, $thumbnail_args );
		}

		// Get hover image
		$hover_image_html = '';
		if ( $settings['show_hover_image'] ) {
			$hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );

			if ( $hover_image_id ) {
				$hover_image_html = wp_get_attachment_image(
					$hover_image_id,
					$image_size,
					false,
					[
						'class'   => 'bw-slider-hover',
						'loading' => 'lazy',
					]
				);
			}
		}

		// Build media classes
		$media_classes = [ 'bw-wallpost-media', 'bw-slick-item__image', 'bw-ss__media' ];
		if ( ! $thumbnail_html ) {
			$media_classes[] = 'bw-wallpost-media--placeholder';
			$media_classes[] = 'bw-slick-item__image--placeholder';
		}
		if ( ! empty( $settings['image_classes'] ) ) {
			$media_classes[] = $settings['image_classes'];
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
			<?php if ( $thumbnail_html ) : ?>
				<div class="bw-wallpost-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-wallpost-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
					<?php echo wp_kses_post( $thumbnail_html ); ?>
					<?php if ( $hover_image_html ) : ?>
						<?php echo wp_kses_post( $hover_image_html ); ?>
					<?php endif; ?>
				</div>

				<!-- Link invisibile che copre l'intera area dell'immagine -->
				<a class="bw-wallpost-image-link-overlay" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>"></a>

				<?php if ( $settings['show_buttons'] ) : ?>
					<?php echo self::render_overlay_buttons( $product, $settings ); ?>
				<?php endif; ?>
			<?php else : ?>
				<span class="bw-wallpost-image-placeholder bw-slick-item__image-placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render overlay buttons
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $settings Settings.
	 * @return string HTML markup.
	 */
	private static function render_overlay_buttons( $product, $settings ) {
		$post_id           = $product->get_id();
		$permalink         = function_exists( 'bw_get_safe_product_permalink' )
			? bw_get_safe_product_permalink( $product )
			: $product->get_permalink();
		$has_add_to_cart   = false;
		$add_to_cart_url   = '';
		$open_cart_popup   = $settings['open_cart_popup'];

		if ( $settings['show_add_to_cart'] ) {
			if ( $product->is_type( 'variable' ) ) {
				$add_to_cart_url = $permalink;
			} else {
				$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

				if ( $cart_url ) {
					$add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $cart_url );
				}
			}

			if ( ! $add_to_cart_url ) {
				$add_to_cart_url = $permalink;
			}

			$has_add_to_cart = true;
		}

		ob_start();
		?>
		<div class="bw-wallpost-overlay overlay-buttons bw-ss__overlay has-buttons">
			<div class="bw-wallpost-overlay-buttons bw-ss__buttons bw-slide-buttons<?php echo $has_add_to_cart ? ' bw-wallpost-overlay-buttons--double bw-ss__buttons--double' : ''; ?>">
				<a class="bw-wallpost-overlay-button overlay-button overlay-button--view bw-ss__btn bw-view-btn bw-slide-button" href="<?php echo esc_url( $permalink ); ?>">
					<span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'View Product', 'bw-elementor-widgets' ); ?></span>
				</a>
				<?php if ( $has_add_to_cart && $add_to_cart_url ) : ?>
					<a class="bw-wallpost-overlay-button overlay-button overlay-button--cart bw-ss__btn bw-btn-addtocart bw-slide-button" href="<?php echo esc_url( $add_to_cart_url ); ?>"<?php echo $open_cart_popup ? ' data-open-cart-popup="1"' : ''; ?>>
						<span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render product content section
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $settings Settings.
	 * @return string HTML markup.
	 */
	private static function render_product_content( $product, $settings ) {
		$post_id   = $product->get_id();
		$permalink = function_exists( 'bw_get_safe_product_permalink' )
			? bw_get_safe_product_permalink( $product )
			: $product->get_permalink();
		$title     = $product->get_name();
		$excerpt   = '';

		if ( $settings['show_description'] ) {
			$excerpt = $product->get_short_description();

			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 30 );
			}

			if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
				$excerpt = '<p>' . $excerpt . '</p>';
			}
		}

		$price_html = '';
		if ( $settings['show_price'] ) {
			$price_html = self::get_price_markup( $product );
		}

		ob_start();
		?>
		<?php if ( $settings['show_title'] ) : ?>
			<h3 class="bw-wallpost-title bw-slick-item__title bw-slick-title bw-slider-title">
				<a href="<?php echo esc_url( $permalink ); ?>">
					<?php echo esc_html( $title ); ?>
				</a>
			</h3>
		<?php endif; ?>

		<?php if ( $settings['show_description'] && ! empty( $excerpt ) ) : ?>
			<div class="bw-wallpost-description bw-slick-item__excerpt bw-slick-description bw-slider-description"><?php echo wp_kses_post( $excerpt ); ?></div>
		<?php endif; ?>

		<?php if ( $settings['show_price'] && $price_html ) : ?>
			<div class="bw-wallpost-price bw-slick-item__price price bw-slick-price bw-slider-price"><?php echo wp_kses_post( $price_html ); ?></div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get price markup for a product
	 *
	 * @param WC_Product $product Product object.
	 * @return string Price HTML.
	 */
	private static function get_price_markup( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$price_html = $product->get_price_html();
		if ( ! empty( $price_html ) ) {
			return $price_html;
		}

		// Fallback to manual formatting
		$format_price = function ( $value ) {
			if ( '' === $value || null === $value ) {
				return '';
			}

			if ( function_exists( 'wc_price' ) && is_numeric( $value ) ) {
				return wc_price( $value );
			}

			if ( is_numeric( $value ) ) {
				$value = number_format_i18n( (float) $value, 2 );
			}

			return esc_html( $value );
		};

		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$current_price = $product->get_price();

		$regular_markup = $format_price( $regular_price );
		$sale_markup    = $format_price( $sale_price );
		$current_markup = $format_price( $current_price );

		if ( $sale_markup && $regular_markup && $sale_markup !== $regular_markup ) {
			return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
				'<span class="price-sale">' . $sale_markup . '</span>';
		}

		if ( $current_markup ) {
			return '<span class="price-regular">' . $current_markup . '</span>';
		}

		if ( $regular_markup ) {
			return '<span class="price-regular">' . $regular_markup . '</span>';
		}

		return '';
	}

	/**
	 * Render multiple product cards
	 *
	 * @param array $products Array of product IDs or objects.
	 * @param array $settings Settings.
	 * @return string HTML markup.
	 */
	public static function render_cards( $products, $settings = [] ) {
		if ( empty( $products ) || ! is_array( $products ) ) {
			return '';
		}

		$output = '';
		foreach ( $products as $product ) {
			$output .= self::render_card( $product, $settings );
		}

		return $output;
	}
}
