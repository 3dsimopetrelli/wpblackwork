<?php
/**
 * BW Product Card Component
 *
 * Canonical reusable product-card component authority.
 *
 * @package BW_Main_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BW_Product_Card_Component
 */
class BW_Product_Card_Component {

	/**
	 * Default settings per le card.
	 *
	 * @var array
	 */
		private static $default_settings = [
			'image_size'               => 'large',
			'image_mode'               => 'cover',
			'image_loading'            => 'lazy',
			'hover_image_loading'      => 'lazy',
			'show_image'               => true,
			'show_hover_image'         => true,
			'hover_image_source'       => 'meta',
		'show_title'               => true,
		'show_description'         => false,
		'description_mode'         => 'auto',
		'show_price'               => true,
		'show_buttons'             => true,
		'show_add_to_cart'         => true,
		'open_cart_popup'          => false,
		'use_wc_product_class'     => false,
		'image_classes'            => '',
		'media_link_classes'       => '',
		'media_classes'            => '',
		'image_wrapper_classes'    => '',
		'content_classes'          => '',
		'title_classes'            => '',
		'description_classes'      => '',
		'price_classes'            => '',
		'overlay_classes'          => '',
		'overlay_buttons_classes'  => '',
		'view_button_classes'      => '',
		'cart_button_classes'      => '',
		'placeholder_classes'      => '',
		'card_classes'             => '',
		'wrapper_classes'          => '',
	];

	/**
	 * Render a product card.
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @param array          $settings Card settings.
	 * @return string
	 */
	public static function render( $product, $settings = [] ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product || ! $product instanceof WC_Product ) {
			return '';
		}

		$settings = wp_parse_args( $settings, self::$default_settings );
		$post_id  = $product->get_id();

		$item_classes = self::merge_classes(
			[ 'bw-wallpost-item', 'bw-slick-item', 'bw-product-card-item' ],
			$settings['wrapper_classes']
		);

		$card_classes = self::merge_classes(
			[ 'bw-wallpost-card', 'bw-slick-item__inner', 'bw-ss__card', 'bw-product-card' ],
			$settings['card_classes']
		);

		$content_classes = self::merge_classes(
			[ 'bw-wallpost-content', 'bw-slick-item__content', 'bw-ss__content', 'bw-slider-content', 'bw-slick-slider-text-box' ],
			$settings['content_classes']
		);

		ob_start();
		?>
		<article <?php self::render_article_class_attribute( $item_classes, $product, $post_id, $settings ); ?>>
			<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $card_classes ) ) ); ?>">
				<div class="bw-slider-image-container">
					<?php echo self::render_product_image( $product, $settings ); ?>
				</div>

				<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $content_classes ) ) ); ?>">
					<?php echo self::render_product_content( $product, $settings ); ?>
				</div>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render multiple cards.
	 *
	 * @param array $products Product IDs or objects.
	 * @param array $settings Settings.
	 * @return string
	 */
	public static function render_many( $products, $settings = [] ) {
		if ( empty( $products ) || ! is_array( $products ) ) {
			return '';
		}

		$output = '';
		foreach ( $products as $product ) {
			$output .= self::render( $product, $settings );
		}

		return $output;
	}

	/**
	 * Compatibility alias for legacy calls.
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @param array          $settings Card settings.
	 * @return string
	 */
	public static function render_card( $product, $settings = [] ) {
		return self::render( $product, $settings );
	}

	/**
	 * Compatibility alias for legacy calls.
	 *
	 * @param array $products Product IDs or objects.
	 * @param array $settings Settings.
	 * @return string
	 */
	public static function render_cards( $products, $settings = [] ) {
		return self::render_many( $products, $settings );
	}

	/**
	 * Render product image section.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $settings Settings.
	 * @return string
	 */
		private static function render_product_image( $product, $settings ) {
			if ( ! $settings['show_image'] ) {
				return '';
			}

			$image_mode          = self::normalize_image_mode( $settings['image_mode'] );
			$post_id             = $product->get_id();
			$permalink           = function_exists( 'bw_get_safe_product_permalink' )
				? bw_get_safe_product_permalink( $product )
				: $product->get_permalink();
			$title               = $product->get_name();
			$image_size          = $settings['image_size'];
			$image_loading       = self::normalize_image_loading( isset( $settings['image_loading'] ) ? $settings['image_loading'] : 'lazy' );
			$hover_image_loading = self::normalize_image_loading(
				isset( $settings['hover_image_loading'] ) ? $settings['hover_image_loading'] : 'lazy'
			);

			$thumbnail_html = '';
			$image_id       = $product->get_image_id();

			if ( $image_id ) {
				$thumbnail_html = wp_get_attachment_image(
					$image_id,
					$image_size,
					false,
					[
						'loading' => $image_loading,
						'class'   => 'bw-slider-main bw-product-card-image-el bw-product-card-image-el--' . $image_mode,
					]
				);
			}

		$hover_image_html = '';
		if ( $settings['show_hover_image'] ) {
			$hover_source   = isset( $settings['hover_image_source'] ) ? sanitize_key( (string) $settings['hover_image_source'] ) : 'meta';
			$hover_image_id = 0;

			if ( 'gallery_first' === $hover_source ) {
				$gallery_ids = $product->get_gallery_image_ids();
				if ( ! empty( $gallery_ids ) ) {
					$hover_image_id = (int) reset( $gallery_ids );
				}
			} else {
				$hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );
			}

				if ( $hover_image_id ) {
					$hover_image_html = wp_get_attachment_image(
						$hover_image_id,
						$image_size,
						false,
						[
							'class'   => 'bw-slider-hover bw-product-card-image-el bw-product-card-image-el--' . $image_mode,
							'loading' => $hover_image_loading,
						]
					);
				}
			}

		$media_classes = [ 'bw-wallpost-media', 'bw-slick-item__image', 'bw-ss__media', 'bw-product-card-media--' . $image_mode ];
		if ( ! $thumbnail_html ) {
			$media_classes[] = 'bw-wallpost-media--placeholder';
			$media_classes[] = 'bw-slick-item__image--placeholder';
		}

		$media_classes = self::merge_classes(
			$media_classes,
			[
				$settings['image_classes'],
				$settings['media_classes'],
			]
		);

		$image_wrapper_classes = self::merge_classes(
			[ 'bw-wallpost-image', 'bw-slick-slider-image', 'bw-product-card-image--' . $image_mode ],
			$settings['image_wrapper_classes']
		);
		if ( $hover_image_html ) {
			$image_wrapper_classes = self::merge_classes(
				$image_wrapper_classes,
				[ 'bw-wallpost-image--has-hover', 'bw-slick-slider-image--has-hover' ]
			);
		}

		$media_link_classes = self::merge_classes(
			[ 'bw-wallpost-image-link-overlay' ],
			$settings['media_link_classes']
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
			<?php if ( $thumbnail_html ) : ?>
				<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $image_wrapper_classes ) ) ); ?>">
					<?php echo wp_kses_post( $thumbnail_html ); ?>
					<?php if ( $hover_image_html ) : ?>
						<?php echo wp_kses_post( $hover_image_html ); ?>
					<?php endif; ?>
				</div>

				<a class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_link_classes ) ) ); ?>" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>"></a>

				<?php if ( $settings['show_buttons'] ) : ?>
					<?php echo self::render_overlay_buttons( $product, $settings ); ?>
				<?php endif; ?>
			<?php else : ?>
				<?php $placeholder_classes = self::merge_classes( [ 'bw-wallpost-image-placeholder', 'bw-slick-item__image-placeholder' ], $settings['placeholder_classes'] ); ?>
				<span class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $placeholder_classes ) ) ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render overlay buttons.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $settings Settings.
	 * @return string
	 */
	private static function render_overlay_buttons( $product, $settings ) {
		$post_id         = $product->get_id();
		$permalink       = function_exists( 'bw_get_safe_product_permalink' )
			? bw_get_safe_product_permalink( $product )
			: $product->get_permalink();
		$has_add_to_cart = false;
		$add_to_cart_url = '';
		$open_cart_popup = $settings['open_cart_popup'];

		$overlay_classes = self::merge_classes(
			[ 'bw-wallpost-overlay', 'overlay-buttons', 'bw-ss__overlay', 'has-buttons' ],
			$settings['overlay_classes']
		);
		$overlay_buttons_classes = self::merge_classes(
			[ 'bw-wallpost-overlay-buttons', 'bw-ss__buttons', 'bw-slide-buttons' ],
			$settings['overlay_buttons_classes']
		);
		$view_button_classes = self::merge_classes(
			[ 'bw-wallpost-overlay-button', 'overlay-button', 'overlay-button--view', 'bw-ss__btn', 'bw-view-btn', 'bw-slide-button' ],
			$settings['view_button_classes']
		);
		$cart_button_classes = self::merge_classes(
			[ 'bw-wallpost-overlay-button', 'overlay-button', 'overlay-button--cart', 'bw-ss__btn', 'bw-btn-addtocart', 'bw-slide-button' ],
			$settings['cart_button_classes']
		);

		if ( $settings['show_add_to_cart'] && ! $product->is_type( 'variable' ) ) {
			$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
			if ( $cart_url ) {
				$add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $cart_url );
			}

			if ( ! $add_to_cart_url ) {
				$add_to_cart_url = $permalink;
			}

			$has_add_to_cart = true;
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $overlay_classes ) ) ); ?>">
			<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $overlay_buttons_classes ) ) ); ?><?php echo $has_add_to_cart ? ' bw-wallpost-overlay-buttons--double bw-ss__buttons--double' : ''; ?>">
				<a class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $view_button_classes ) ) ); ?>" href="<?php echo esc_url( $permalink ); ?>">
					<span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'View Product', 'bw-elementor-widgets' ); ?></span>
				</a>
				<?php if ( $has_add_to_cart && $add_to_cart_url ) : ?>
					<a class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $cart_button_classes ) ) ); ?>" href="<?php echo esc_url( $add_to_cart_url ); ?>" data-product_id="<?php echo esc_attr( $post_id ); ?>"<?php echo $open_cart_popup ? ' data-open-cart-popup="1"' : ''; ?>>
						<span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render product content section.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $settings Settings.
	 * @return string
	 */
	private static function render_product_content( $product, $settings ) {
		$permalink = function_exists( 'bw_get_safe_product_permalink' )
			? bw_get_safe_product_permalink( $product )
			: $product->get_permalink();
		$title     = $product->get_name();
		$excerpt   = '';

		if ( $settings['show_description'] ) {
			$description_mode = isset( $settings['description_mode'] ) ? sanitize_key( (string) $settings['description_mode'] ) : 'auto';
			$excerpt          = $product->get_short_description();

			if ( 'auto' === $description_mode && empty( $excerpt ) ) {
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

		$title_classes = self::merge_classes( [ 'bw-wallpost-title', 'bw-slick-item__title', 'bw-slick-title', 'bw-slider-title' ], $settings['title_classes'] );
		$description_classes = self::merge_classes( [ 'bw-wallpost-description', 'bw-slick-item__excerpt', 'bw-slick-description', 'bw-slider-description' ], $settings['description_classes'] );
		$price_classes = self::merge_classes( [ 'bw-wallpost-price', 'bw-slick-item__price', 'price', 'bw-slick-price', 'bw-slider-price' ], $settings['price_classes'] );

		ob_start();
		?>
		<?php if ( $settings['show_title'] ) : ?>
			<h3 class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $title_classes ) ) ); ?>">
				<a href="<?php echo esc_url( $permalink ); ?>">
					<?php echo esc_html( $title ); ?>
				</a>
			</h3>
		<?php endif; ?>

		<?php if ( $settings['show_description'] && ! empty( $excerpt ) ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $description_classes ) ) ); ?>"><?php echo wp_kses_post( $excerpt ); ?></div>
		<?php endif; ?>

		<?php if ( $settings['show_price'] && $price_html ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $price_classes ) ) ); ?>"><?php echo wp_kses_post( $price_html ); ?></div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Merge class lists.
	 *
	 * @param array        $base_classes Base classes.
	 * @param string|array $extra_classes Extra classes.
	 * @return array
	 */
	private static function merge_classes( $base_classes, $extra_classes ) {
		$merged = [];

		foreach ( (array) $base_classes as $class_set ) {
			foreach ( preg_split( '/\s+/', (string) $class_set ) as $class_name ) {
				$class_name = trim( $class_name );
				if ( '' !== $class_name ) {
					$merged[] = $class_name;
				}
			}
		}

		foreach ( (array) $extra_classes as $class_set ) {
			foreach ( preg_split( '/\s+/', (string) $class_set ) as $class_name ) {
				$class_name = trim( $class_name );
				if ( '' !== $class_name ) {
					$merged[] = $class_name;
				}
			}
		}

		return array_values( array_unique( $merged ) );
	}

	/**
	 * Get price markup for a product.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	private static function get_price_markup( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$price_html = $product->get_price_html();
		if ( ! empty( $price_html ) ) {
			return $price_html;
		}

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
	 * Normalize image mode setting.
	 *
	 * @param string $image_mode Raw image mode.
	 * @return string
	 */
	private static function normalize_image_mode( $image_mode ) {
		$image_mode = sanitize_key( (string) $image_mode );

		if ( ! in_array( $image_mode, [ 'proportional', 'cover' ], true ) ) {
			return 'cover';
		}

		return $image_mode;
	}

	/**
	 * Normalize image loading setting.
	 *
	 * @param string $image_loading Raw image loading mode.
	 * @return string
	 */
	private static function normalize_image_loading( $image_loading ) {
		$image_loading = sanitize_key( (string) $image_loading );

		if ( ! in_array( $image_loading, [ 'lazy', 'eager', 'auto' ], true ) ) {
			return 'lazy';
		}

		return $image_loading;
	}

	/**
	 * Render class attribute for article wrapper.
	 *
	 * @param array      $item_classes Item classes.
	 * @param WC_Product $product      Product object.
	 * @param int        $post_id      Post ID.
	 * @param array      $settings     Settings.
	 * @return void
	 */
	private static function render_article_class_attribute( $item_classes, $product, $post_id, $settings ) {
		$use_wc_product_class = ! empty( $settings['use_wc_product_class'] );
		if ( $use_wc_product_class && function_exists( 'wc_product_class' ) ) {
			wc_product_class( implode( ' ', array_map( 'sanitize_html_class', $item_classes ) ), $product );
			return;
		}

		post_class( $item_classes, $post_id );
	}
}
