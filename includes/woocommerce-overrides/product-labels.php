<?php
/**
 * Blackwork Product Labels
 *
 * Centralized settings, resolver, and rendering helpers for WooCommerce
 * promotional labels/badges.
 *
 * @package BW_Main_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bw_get_product_labels_default_settings' ) ) {
	/**
	 * Return default settings for product labels.
	 *
	 * @return array<string,mixed>
	 */
	function bw_get_product_labels_default_settings() {
		return [
			'enabled'             => 1,
			'show_archive'        => 1,
			'show_single'         => 0,
			'max_visible'         => 2,
			'priority_order'      => [ 'staff_select', 'sale', 'free_download', 'new' ],
			'new_enabled'         => 1,
			'new_days'            => 30,
			'sale_enabled'        => 1,
			'sale_display_mode'   => 'save_percentage',
			'free_enabled'        => 1,
			'free_rule_mode'      => 'price_zero_only',
			'staff_enabled'       => 1,
			'staff_product_ids'   => [],
			'staff_manual_order'  => [],
		];
	}
}

if ( ! function_exists( 'bw_get_product_label_definitions' ) ) {
	/**
	 * Return canonical product label definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function bw_get_product_label_definitions() {
		return [
			'staff_select'  => [
				'label' => __( 'Staff Select', 'bw' ),
			],
			'sale'          => [
				'label' => __( 'Sale', 'bw' ),
			],
			'free_download' => [
				'label' => __( 'Free Download', 'bw' ),
			],
			'new'           => [
				'label' => __( 'New', 'bw' ),
			],
		];
	}
}

if ( ! function_exists( 'bw_get_product_label_priority_choices' ) ) {
	/**
	 * Return priority labels for admin UI.
	 *
	 * @return array<string,string>
	 */
	function bw_get_product_label_priority_choices() {
		$definitions = bw_get_product_label_definitions();
		$choices     = [];

		foreach ( $definitions as $label_key => $definition ) {
			$choices[ $label_key ] = isset( $definition['label'] ) ? (string) $definition['label'] : $label_key;
		}

		return $choices;
	}
}

if ( ! function_exists( 'bw_parse_product_label_id_list' ) ) {
	/**
	 * Normalize a list of numeric IDs.
	 *
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	function bw_parse_product_label_id_list( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$ids = [];

		foreach ( $value as $raw_id ) {
			$parsed_id = absint( $raw_id );

			if ( $parsed_id > 0 && ! in_array( $parsed_id, $ids, true ) ) {
				$ids[] = $parsed_id;
			}
		}

		return $ids;
	}
}

if ( ! function_exists( 'bw_sanitize_product_labels_settings' ) ) {
	/**
	 * Sanitize persisted settings.
	 *
	 * @param mixed $input Raw posted/settings input.
	 * @return array<string,mixed>
	 */
	function bw_sanitize_product_labels_settings( $input ) {
		$defaults     = bw_get_product_labels_default_settings();
		$input        = is_array( $input ) ? $input : [];
		$definitions  = bw_get_product_label_definitions();
		$allowed_keys = array_keys( $definitions );

		$priority_order = [];

		if ( isset( $input['priority_order'] ) ) {
			$raw_priority = is_string( $input['priority_order'] )
				? explode( ',', (string) $input['priority_order'] )
				: (array) $input['priority_order'];

			foreach ( $raw_priority as $raw_key ) {
				$label_key = sanitize_key( $raw_key );

				if ( in_array( $label_key, $allowed_keys, true ) && ! in_array( $label_key, $priority_order, true ) ) {
					$priority_order[] = $label_key;
				}
			}
		}

		foreach ( $defaults['priority_order'] as $default_key ) {
			if ( in_array( $default_key, $allowed_keys, true ) && ! in_array( $default_key, $priority_order, true ) ) {
				$priority_order[] = $default_key;
			}
		}

		$staff_product_ids  = bw_parse_product_label_id_list( $input['staff_product_ids'] ?? [] );
		$staff_manual_order = bw_parse_product_label_id_list( $input['staff_manual_order'] ?? [] );
		$staff_manual_order = array_values(
			array_filter(
				$staff_manual_order,
				static function ( $product_id ) use ( $staff_product_ids ) {
					return in_array( $product_id, $staff_product_ids, true );
				}
			)
		);

		foreach ( $staff_product_ids as $product_id ) {
			if ( ! in_array( $product_id, $staff_manual_order, true ) ) {
				$staff_manual_order[] = $product_id;
			}
		}

		$sale_display_mode = isset( $input['sale_display_mode'] ) ? sanitize_key( $input['sale_display_mode'] ) : $defaults['sale_display_mode'];
		if ( ! in_array( $sale_display_mode, [ 'save_percentage', 'sale', 'discount_percentage' ], true ) ) {
			$sale_display_mode = $defaults['sale_display_mode'];
		}

		$free_rule_mode = isset( $input['free_rule_mode'] ) ? sanitize_key( $input['free_rule_mode'] ) : $defaults['free_rule_mode'];
		if ( ! in_array( $free_rule_mode, [ 'price_zero_only', 'price_zero_downloadable' ], true ) ) {
			$free_rule_mode = $defaults['free_rule_mode'];
		}

		return [
			'enabled'            => ! empty( $input['enabled'] ) ? 1 : 0,
			'show_archive'       => ! empty( $input['show_archive'] ) ? 1 : 0,
			'show_single'        => ! empty( $input['show_single'] ) ? 1 : 0,
			'max_visible'        => max( 1, min( 4, absint( $input['max_visible'] ?? $defaults['max_visible'] ) ) ),
			'priority_order'     => $priority_order,
			'new_enabled'        => ! empty( $input['new_enabled'] ) ? 1 : 0,
			'new_days'           => max( 1, min( 3650, absint( $input['new_days'] ?? $defaults['new_days'] ) ) ),
			'sale_enabled'       => ! empty( $input['sale_enabled'] ) ? 1 : 0,
			'sale_display_mode'  => $sale_display_mode,
			'free_enabled'       => ! empty( $input['free_enabled'] ) ? 1 : 0,
			'free_rule_mode'     => $free_rule_mode,
			'staff_enabled'      => ! empty( $input['staff_enabled'] ) ? 1 : 0,
			'staff_product_ids'  => $staff_product_ids,
			'staff_manual_order' => $staff_manual_order,
		];
	}
}

if ( ! function_exists( 'bw_get_product_labels_settings' ) ) {
	/**
	 * Return normalized settings.
	 *
	 * @return array<string,mixed>
	 */
	function bw_get_product_labels_settings() {
		$stored   = get_option( 'bw_product_labels_settings_v1', [] );
		$merged   = wp_parse_args( is_array( $stored ) ? $stored : [], bw_get_product_labels_default_settings() );
		$settings = bw_sanitize_product_labels_settings( $merged );

		return wp_parse_args( $settings, bw_get_product_labels_default_settings() );
	}
}

if ( ! function_exists( 'bw_get_product_label_priority_order' ) ) {
	/**
	 * Return resolved priority order.
	 *
	 * @param array<string,mixed>|null $settings Optional settings.
	 * @return string[]
	 */
	function bw_get_product_label_priority_order( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : bw_get_product_labels_settings();
		$order    = isset( $settings['priority_order'] ) ? (array) $settings['priority_order'] : [];

		return array_values( array_filter( array_map( 'sanitize_key', $order ) ) );
	}
}

if ( ! function_exists( 'bw_get_product_label_staff_ids' ) ) {
	/**
	 * Return normalized Staff Select product IDs.
	 *
	 * Manual order is the authoritative order when available.
	 *
	 * @param array<string,mixed>|null $settings Optional settings.
	 * @return int[]
	 */
	function bw_get_product_label_staff_ids( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : bw_get_product_labels_settings();

		$product_ids  = bw_parse_product_label_id_list( $settings['staff_product_ids'] ?? [] );
		$manual_order = bw_parse_product_label_id_list( $settings['staff_manual_order'] ?? [] );
		$resolved     = [];

		foreach ( $manual_order as $product_id ) {
			if ( in_array( $product_id, $product_ids, true ) && ! in_array( $product_id, $resolved, true ) ) {
				$resolved[] = $product_id;
			}
		}

		foreach ( $product_ids as $product_id ) {
			if ( ! in_array( $product_id, $resolved, true ) ) {
				$resolved[] = $product_id;
			}
		}

		return $resolved;
	}
}

if ( ! function_exists( 'bw_product_label_calculate_sale_percentage' ) ) {
	/**
	 * Calculate rounded WooCommerce sale percentage.
	 *
	 * @param WC_Product $product Product object.
	 * @return int
	 */
	function bw_product_label_calculate_sale_percentage( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
			return 0;
		}

		$regular_price = (float) $product->get_regular_price();
		$sale_price    = (float) $product->get_sale_price();

		if ( $regular_price <= 0 || $sale_price < 0 || $sale_price >= $regular_price ) {
			return 0;
		}

		$percentage = (int) round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );

		return max( 1, $percentage );
	}
}

if ( ! function_exists( 'bw_get_product_label_sale_text' ) ) {
	/**
	 * Return Sale label text for the configured display mode.
	 *
	 * @param WC_Product          $product  Product object.
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	function bw_get_product_label_sale_text( $product, $settings ) {
		$display_mode = isset( $settings['sale_display_mode'] ) ? sanitize_key( $settings['sale_display_mode'] ) : 'save_percentage';
		$percentage   = bw_product_label_calculate_sale_percentage( $product );

		switch ( $display_mode ) {
			case 'discount_percentage':
				return $percentage > 0 ? sprintf( '-%d%%', $percentage ) : __( 'Sale', 'bw' );

			case 'sale':
				return __( 'Sale', 'bw' );

			case 'save_percentage':
			default:
				return $percentage > 0 ? sprintf( __( 'Save %d%%', 'bw' ), $percentage ) : __( 'Sale', 'bw' );
		}
	}
}

if ( ! function_exists( 'bw_product_matches_new_label' ) ) {
	/**
	 * Whether the product matches the New rule.
	 *
	 * @param int                 $product_id Product ID.
	 * @param array<string,mixed> $settings   Settings.
	 * @return bool
	 */
	function bw_product_matches_new_label( $product_id, $settings ) {
		$new_days = absint( $settings['new_days'] ?? 0 );

		if ( $new_days <= 0 ) {
			return false;
		}

		$publish_timestamp = (int) get_post_time( 'U', true, $product_id );
		if ( $publish_timestamp <= 0 ) {
			return false;
		}

		$current_timestamp = (int) current_time( 'timestamp', true );
		$age_in_seconds    = max( 0, $current_timestamp - $publish_timestamp );

		return $age_in_seconds <= ( $new_days * DAY_IN_SECONDS );
	}
}

if ( ! function_exists( 'bw_product_matches_free_download_label' ) ) {
	/**
	 * Whether the product matches the Free Download rule.
	 *
	 * @param WC_Product          $product  Product object.
	 * @param array<string,mixed> $settings Settings.
	 * @return bool
	 */
	function bw_product_matches_free_download_label( $product, $settings ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$price     = (float) $product->get_price();
		$rule_mode = isset( $settings['free_rule_mode'] ) ? sanitize_key( $settings['free_rule_mode'] ) : 'price_zero_only';

		if ( $price > 0 ) {
			return false;
		}

		if ( 'price_zero_downloadable' === $rule_mode ) {
			return $product->is_downloadable();
		}

		return true;
	}
}

if ( ! function_exists( 'bw_get_product_labels' ) ) {
	/**
	 * Resolve product labels for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int,array<string,mixed>>
	 */
	function bw_get_product_labels( $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 || ! class_exists( 'WooCommerce' ) ) {
			return [];
		}

		$settings = bw_get_product_labels_settings();
		if ( empty( $settings['enabled'] ) ) {
			return [];
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return [];
		}

		$matched_labels = [];
		$priority_order = bw_get_product_label_priority_order( $settings );
		$priority_map   = [];

		foreach ( $priority_order as $index => $label_key ) {
			$priority_map[ $label_key ] = $index + 1;
		}

		if ( ! empty( $settings['staff_enabled'] ) && in_array( $product_id, bw_get_product_label_staff_ids( $settings ), true ) ) {
			$matched_labels[] = [
				'key'      => 'staff_select',
				'label'    => __( 'Staff Select', 'bw' ),
				'priority' => isset( $priority_map['staff_select'] ) ? $priority_map['staff_select'] : 1,
			];
		}

		if ( ! empty( $settings['sale_enabled'] ) && $product->is_on_sale() ) {
			$matched_labels[] = [
				'key'      => 'sale',
				'label'    => bw_get_product_label_sale_text( $product, $settings ),
				'priority' => isset( $priority_map['sale'] ) ? $priority_map['sale'] : 2,
			];
		}

		if ( ! empty( $settings['free_enabled'] ) && bw_product_matches_free_download_label( $product, $settings ) ) {
			$matched_labels[] = [
				'key'      => 'free_download',
				'label'    => __( 'Free Download', 'bw' ),
				'priority' => isset( $priority_map['free_download'] ) ? $priority_map['free_download'] : 3,
			];
		}

		if ( ! empty( $settings['new_enabled'] ) && bw_product_matches_new_label( $product_id, $settings ) ) {
			$matched_labels[] = [
				'key'      => 'new',
				'label'    => __( 'New', 'bw' ),
				'priority' => isset( $priority_map['new'] ) ? $priority_map['new'] : 4,
			];
		}

		usort(
			$matched_labels,
			static function ( $left, $right ) {
				$left_priority  = isset( $left['priority'] ) ? (int) $left['priority'] : 999;
				$right_priority = isset( $right['priority'] ) ? (int) $right['priority'] : 999;

				if ( $left_priority === $right_priority ) {
					return strcmp( (string) ( $left['key'] ?? '' ), (string) ( $right['key'] ?? '' ) );
				}

				return $left_priority <=> $right_priority;
			}
		);

		$max_visible = max( 1, absint( $settings['max_visible'] ?? 1 ) );

		return array_slice( $matched_labels, 0, $max_visible );
	}
}

if ( ! function_exists( 'bw_get_product_labels_icon_svg' ) ) {
	/**
	 * Return inline SVG for icon-bearing labels.
	 *
	 * @param string $label_key Label key.
	 * @return string
	 */
	function bw_get_product_labels_icon_svg( $label_key ) {
		switch ( sanitize_key( $label_key ) ) {
			case 'staff_select':
				return '<svg class="bw-product-label__icon-svg bw-product-label__icon-svg--staff" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 1.5 14 8l-6 6.5L2 8 8 1.5Z" fill="currentColor"/></svg>';

			case 'free_download':
				return '<svg class="bw-product-label__icon-svg bw-product-label__icon-svg--download" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 2.25a.75.75 0 0 1 .75.75v5.44l1.72-1.72a.75.75 0 1 1 1.06 1.06L8.53 10.78a.75.75 0 0 1-1.06 0L4.47 7.78a.75.75 0 1 1 1.06-1.06l1.72 1.72V3A.75.75 0 0 1 8 2.25Z" fill="currentColor"/><path d="M3 12.25c0-.41.34-.75.75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1-.75-.75Z" fill="currentColor"/></svg>';

			default:
				return '';
		}
	}
}

if ( ! function_exists( 'bw_render_product_labels' ) ) {
	/**
	 * Render product labels for archive/single usage.
	 *
	 * @param int|WC_Product $product Product ID or object.
	 * @param string         $context Render context.
	 * @return string
	 */
	function bw_render_product_labels( $product, $context = 'archive' ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$settings = bw_get_product_labels_settings();
		if ( empty( $settings['enabled'] ) ) {
			return '';
		}

		$context = 'single' === $context ? 'single' : 'archive';

		if ( 'archive' === $context && empty( $settings['show_archive'] ) ) {
			return '';
		}

		if ( 'single' === $context && empty( $settings['show_single'] ) ) {
			return '';
		}

		$labels = bw_get_product_labels( $product->get_id() );
		if ( empty( $labels ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="bw-product-labels bw-product-labels--<?php echo esc_attr( $context ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
			<?php foreach ( $labels as $label_data ) : ?>
				<?php
				$label_key  = isset( $label_data['key'] ) ? sanitize_key( $label_data['key'] ) : '';
				$label_text = isset( $label_data['label'] ) ? (string) $label_data['label'] : '';
				if ( '' === $label_key || '' === $label_text ) {
					continue;
				}

				$icon_svg = bw_get_product_labels_icon_svg( $label_key );
				?>
				<span class="bw-product-label bw-product-label--<?php echo esc_attr( $label_key ); ?>" data-label-key="<?php echo esc_attr( $label_key ); ?>">
					<?php if ( '' !== $icon_svg ) : ?>
						<span class="bw-product-label__icon" aria-hidden="true"><?php echo wp_kses( $icon_svg, [ 'svg' => [ 'class' => true, 'viewBox' => true, 'viewbox' => true, 'aria-hidden' => true, 'focusable' => true ], 'path' => [ 'd' => true, 'fill' => true ] ] ); ?></span>
					<?php endif; ?>
					<span class="bw-product-label__text"><?php echo esc_html( $label_text ); ?></span>
				</span>
			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}

if ( ! function_exists( 'bw_product_labels_should_render_on_single' ) ) {
	/**
	 * Determine whether single-product labels should render.
	 *
	 * @return bool
	 */
	function bw_product_labels_should_render_on_single() {
		$settings = bw_get_product_labels_settings();

		return ! empty( $settings['enabled'] ) && ! empty( $settings['show_single'] );
	}
}

if ( ! function_exists( 'bw_mew_enqueue_product_labels_assets' ) ) {
	/**
	 * Enqueue frontend CSS needed by product labels.
	 *
	 * @return void
	 */
	function bw_mew_enqueue_product_labels_assets() {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'is_product' ) ) {
			return;
		}

		if ( is_product() && wp_style_is( 'bw-product-labels-style', 'registered' ) ) {
			wp_enqueue_style( 'bw-product-labels-style' );
		}
	}
}

if ( ! function_exists( 'bw_mew_render_single_product_labels' ) ) {
	/**
	 * Render labels inside the WooCommerce single product summary.
	 *
	 * @return void
	 */
	function bw_mew_render_single_product_labels() {
		if ( ! function_exists( 'is_product' ) || ! is_product() || ! bw_product_labels_should_render_on_single() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$markup = bw_render_product_labels( $product, 'single' );
		if ( '' === $markup ) {
			return;
		}

		if ( wp_style_is( 'bw-product-labels-style', 'registered' ) ) {
			wp_enqueue_style( 'bw-product-labels-style' );
		}

		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'bw_mew_prepare_single_product_labels' ) ) {
	/**
	 * Replace WooCommerce sale flash with Blackwork labels on single product pages.
	 *
	 * @return void
	 */
	function bw_mew_prepare_single_product_labels() {
		if ( ! function_exists( 'is_product' ) || ! is_product() || ! bw_product_labels_should_render_on_single() ) {
			return;
		}

		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		add_action( 'woocommerce_single_product_summary', 'bw_mew_render_single_product_labels', 4 );
	}
}
