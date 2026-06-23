<?php
/**
 * BW Widget Helper Class
 *
 * Provides shared utility methods for Elementor widgets to reduce code duplication.
 * Contains common functionality used across multiple widget classes.
 *
 * @package BW_Elementor_Widgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for Elementor widgets with common utility methods.
 */
class BW_Widget_Helper {

	/**
	 * Parse a comma-separated string of IDs into an array of integers.
	 *
	 * Used by widgets to parse IDs from control inputs (e.g., "1, 2, 3" -> [1, 2, 3]).
	 *
	 * @param string $ids_string Comma-separated string of IDs.
	 * @return array<int> Array of unique integer IDs.
	 */
	public static function parse_ids( $ids_string ) {
		if ( empty( $ids_string ) ) {
			return array();
		}

		$parts = array_filter( array_map( 'trim', explode( ',', $ids_string ) ) );
		$ids   = array();

		foreach ( $parts as $part ) {
			if ( is_numeric( $part ) ) {
				$ids[] = (int) $part;
			}
		}

		return array_unique( $ids );
	}

	/**
	 * Extract slider value with unit from Elementor control settings.
	 *
	 * Handles responsive controls and returns normalized size/unit values.
	 * Used for controls like width, height, spacing, etc.
	 *
	 * @param array  $settings      Widget settings array.
	 * @param string $control_id    Control ID to retrieve.
	 * @param mixed  $default_size  Default size value if not found.
	 * @param string $default_unit  Default unit (e.g., 'px', '%', 'em').
	 * @return array{size: mixed, unit: string} Array with 'size' and 'unit' keys.
	 */
	public static function get_slider_value_with_unit( $settings, $control_id, $default_size = null, $default_unit = 'px' ) {
		if ( ! isset( $settings[ $control_id ] ) ) {
			return array(
				'size' => $default_size,
				'unit' => $default_unit,
			);
		}

		$value = $settings[ $control_id ];
		$size  = null;
		$unit  = $default_unit;

		if ( is_array( $value ) ) {
			if ( isset( $value['unit'] ) && '' !== $value['unit'] ) {
				$unit = $value['unit'];
			}

			if ( isset( $value['size'] ) && '' !== $value['size'] ) {
				$size = $value['size'];
			} elseif ( isset( $value['sizes'] ) && is_array( $value['sizes'] ) ) {
				// Responsive controls: try desktop, tablet, mobile in order
				foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
					if ( isset( $value['sizes'][ $device ] ) && '' !== $value['sizes'][ $device ] ) {
						$size = $value['sizes'][ $device ];
						break;
					}
				}
			}
		} elseif ( '' !== $value && null !== $value ) {
			$size = $value;
		}

		if ( null === $size ) {
			$size = $default_size;
		}

		if ( is_numeric( $size ) ) {
			$size = (float) $size;
		}

		return array(
			'size' => $size,
			'unit' => $unit,
		);
	}

	/**
	 * Get all public post types as options for select controls.
	 *
	 * Returns an associative array of post type slug => label.
	 * Excludes 'attachment' post type and sorts alphabetically.
	 *
	 * @return array<string,string> Array of post type options [slug => label].
	 */
	public static function get_post_type_options() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$options = array();

		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return $options;
		}

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $post_type->name ) ) {
				continue;
			}

			if ( 'attachment' === $post_type->name ) {
				continue;
			}

			$label = '';

			if ( isset( $post_type->labels->singular_name ) && '' !== $post_type->labels->singular_name ) {
				$label = $post_type->labels->singular_name;
			} elseif ( isset( $post_type->label ) && '' !== $post_type->label ) {
				$label = $post_type->label;
			} else {
				$label = ucfirst( $post_type->name );
			}

			$options[ $post_type->name ] = $label;
		}

		asort( $options );

		return $options;
	}

	/**
	 * Register a widget's CSS/JS assets on demand when Elementor resolves its
	 * style/script dependencies.
	 *
	 * Reproduces the guarded bw_register_widget_assets() call used verbatim by
	 * several widgets' get_style_depends()/get_script_depends() methods: register
	 * only when the helper exists and either handle is not yet registered.
	 *
	 * @param string $name Widget asset slug (e.g. 'license-table').
	 * @return void
	 */
	public static function register_widget_dependencies( $name ) {
		if ( function_exists( 'bw_register_widget_assets' ) && ( ! wp_style_is( 'bw-' . $name . '-style', 'registered' ) || ! wp_script_is( 'bw-' . $name . '-script', 'registered' ) ) ) {
			bw_register_widget_assets( $name );
		}
	}

	/**
	 * Add a COLOR control mapped to a CSS custom property.
	 *
	 * Emits the array: label, type=COLOR, [default], selectors => [ selector =>
	 * "{css_var}: {{VALUE}};" ]. The default key is OMITTED when $default is null,
	 * to stay byte-identical with sites that declare no default. $extra is merged
	 * last for site-specific keys (condition, separator, etc.).
	 *
	 * @param \Elementor\Widget_Base $widget   Widget instance ($this).
	 * @param string                 $id       Control id.
	 * @param string                 $label    Already-translated label (caller wraps __()).
	 * @param string                 $selector CSS selector receiving the variable.
	 * @param string                 $css_var  CSS custom property name (e.g. '--bw-x').
	 * @param string|null            $default  Default color, or null to omit the default key.
	 * @param array                  $extra    Extra control args merged last.
	 * @return void
	 */
	public static function add_color_var_control( $widget, $id, $label, $selector, $css_var, $default = null, $extra = array() ) {
		$args = array(
			'label' => $label,
			'type'  => \Elementor\Controls_Manager::COLOR,
		);

		if ( null !== $default ) {
			$args['default'] = $default;
		}

		$args['selectors'] = array(
			$selector => $css_var . ': {{VALUE}};',
		);

		$widget->add_control( $id, array_merge( $args, $extra ) );
	}

	/**
	 * Add a responsive DIMENSIONS control bound to a 4-side CSS box property.
	 *
	 * Emits: label, type=DIMENSIONS, size_units, [default], selectors => [ selector
	 * => "{css_property}: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}}
	 * {{LEFT}}{{UNIT}};" ]. The default key is omitted when $default is null.
	 *
	 * @param \Elementor\Widget_Base $widget       Widget instance ($this).
	 * @param string                 $id           Control id.
	 * @param string                 $label        Already-translated label.
	 * @param string                 $selector     CSS selector.
	 * @param string                 $css_property CSS property (e.g. 'padding', 'border-radius', 'margin').
	 * @param array                  $size_units   Allowed size units.
	 * @param array|null             $default      Default dimensions array, or null to omit.
	 * @param array                  $extra        Extra control args merged last.
	 * @return void
	 */
	public static function add_dimensions_control( $widget, $id, $label, $selector, $css_property, $size_units, $default = null, $extra = array() ) {
		$args = array(
			'label'      => $label,
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => $size_units,
		);

		if ( null !== $default ) {
			$args['default'] = $default;
		}

		$args['selectors'] = array(
			$selector => $css_property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
		);

		$widget->add_responsive_control( $id, array_merge( $args, $extra ) );
	}

	/**
	 * Add a Typography group control.
	 *
	 * @param \Elementor\Widget_Base $widget   Widget instance ($this).
	 * @param string                 $name     Group control name.
	 * @param string                 $selector CSS selector.
	 * @param array                  $extra    Extra args merged last.
	 * @return void
	 */
	public static function add_typography_group( $widget, $name, $selector, $extra = array() ) {
		$widget->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array_merge(
				array(
					'name'     => $name,
					'selector' => $selector,
				),
				$extra
			)
		);
	}

	/**
	 * Validate an HTML tag against an allow-list, falling back to a default.
	 *
	 * Mirrors the per-widget title-tag guard: sanitize_key() then allow-list check.
	 *
	 * @param string $tag     Raw tag value.
	 * @param array  $allowed Allowed tags.
	 * @param string $default Fallback tag when not allowed.
	 * @return string
	 */
	public static function sanitize_html_tag( $tag, $allowed, $default ) {
		$tag = sanitize_key( (string) $tag );

		return in_array( $tag, $allowed, true ) ? $tag : $default;
	}
}
