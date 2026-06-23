<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Widget_Bw_Product_Details extends Widget_Base {

	public function get_name() {
		return 'bw-product-details-table';
	}

	public function get_title() {
		return __( 'BW-SP Product Details', 'bw' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return array( 'blackwork' );
	}

	public function get_style_depends() {
		return array( 'bw-product-details-style' );
	}

	public function get_script_depends() {
		return array( 'bw-product-details-script' );
	}

	protected function register_controls() {

		// =========================================================
		// TAB: CONTENT
		// =========================================================
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'bw' ),
			)
		);

		$this->add_control(
			'content_type',
			array(
				'label'   => __( 'Content Type', 'bw' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'product_details',
				'options' => array(
					'product_details' => __( 'Product Details', 'bw' ),
					'compatibility'   => __( 'Compatibility', 'bw' ),
					'info_box'        => __( 'Info Box', 'bw' ),
				),
			)
		);

		// --- Product Details: table title ---
		$this->add_control(
			'table_title',
			array(
				'label'       => __( 'Table Title', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Collection Content', 'bw' ),
				'default'     => __( 'Collection Content', 'bw' ),
				'condition'   => array( 'content_type' => 'product_details' ),
			)
		);

		// --- Info Box: title ---
		$this->add_control(
			'info_box_title',
			array(
				'label'     => __( 'Title', 'bw' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Info', 'bw' ),
				'condition' => array( 'content_type' => 'info_box' ),
			)
		);

		// --- Info Box: WYSIWYG content ---
		$this->add_control(
			'info_box_content',
			array(
				'label'     => __( 'Content', 'bw' ),
				'type'      => Controls_Manager::WYSIWYG,
				'default'   => '',
				'condition' => array( 'content_type' => 'info_box' ),
			)
		);

		// --- Accordion ---
		$this->add_control(
			'accordion_divider',
			array(
				'type'  => Controls_Manager::DIVIDER,
				'style' => 'thick',
			)
		);

		$this->add_control(
			'accordion_enabled',
			array(
				'label'        => __( 'Enable Accordion', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'accordion_mobile',
			array(
				'label'        => __( 'Enable on Mobile / Tablet', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'accordion_enabled' => 'yes' ),
			)
		);

		$this->add_control(
			'accordion_desktop',
			array(
				'label'        => __( 'Enable on Desktop', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'accordion_enabled' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// =========================================================
		// TAB: STYLE
		// =========================================================

		$this->start_controls_section(
			'section_style_box',
			array(
				'label' => __( 'Box Style', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'box_border_color',
			array(
				'label'     => __( 'Border Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => array(
					'{{WRAPPER}} .bw-biblio-widget' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'box_border_width',
			array(
				'label'      => __( 'Border Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 10,
					),
				),
				'default'    => array(
					'size' => 1,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-biblio-widget' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'box_border_radius',
			array(
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'size' => 8,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-biblio-widget' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'box_padding',
			__( 'Padding', 'bw' ),
			'{{WRAPPER}} .bw-biblio-widget',
			'padding',
			array( 'px', '%' ),
			array(
				'top'    => 16,
				'right'  => 16,
				'bottom' => 16,
				'left'   => 16,
				'unit'   => 'px',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => __( 'Title', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_typography_group(
			$this,
			'title_typography',
			'{{WRAPPER}} .bw-biblio-title'
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-biblio-title' => 'color: {{VALUE}};',
				),
			)
		);

		BW_Widget_Helper::add_dimensions_control(
			$this,
			'title_padding',
			__( 'Padding', 'bw' ),
			'{{WRAPPER}} .bw-biblio-title',
			'padding',
			array( 'px', '%' ),
			array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 0,
				'unit'   => 'px',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_dividers',
			array(
				'label' => __( 'Row Dividers', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'divider_color',
			array(
				'label'     => __( 'Divider Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d9d9d9',
				'selectors' => array(
					'{{WRAPPER}} .bw-biblio-row:not(:last-child)' => 'border-bottom-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'divider_width',
			array(
				'label'      => __( 'Divider Weight', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 10,
					),
				),
				'default'    => array(
					'size' => 1,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bw-biblio-row:not(:last-child)' => 'border-bottom-width: {{SIZE}}{{UNIT}}; border-bottom-style: solid;',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_labels',
			array(
				'label' => __( 'Labels Typography', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_typography_group(
			$this,
			'labels_typography',
			'{{WRAPPER}} .bw-biblio-label'
		);

		$this->add_control(
			'labels_color',
			array(
				'label'     => __( 'Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-biblio-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_values',
			array(
				'label' => __( 'Values Typography', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_typography_group(
			$this,
			'values_typography',
			'{{WRAPPER}} .bw-biblio-value'
		);

		$this->add_control(
			'values_color',
			array(
				'label'     => __( 'Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bw-biblio-value' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_assets',
			array(
				'label' => __( 'Assets Typography', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		BW_Widget_Helper::add_typography_group(
			$this,
			'assets_typography',
			'{{WRAPPER}} .bw-biblio-row--assets .bw-biblio-value--assets-list'
		);

		$this->end_controls_section();
	}

	// =========================================================
	// RENDER
	// =========================================================

	protected function render() {
		$settings     = $this->get_settings_for_display();
		$content_type = isset( $settings['content_type'] ) ? $settings['content_type'] : 'product_details';

		// For product-driven content types: resolve product before building DOM (bail early on failure).
		$product            = null;
		$compatibility_rows = array();
		if ( in_array( $content_type, array( 'product_details', 'compatibility' ), true ) ) {
			$product = $this->resolve_product();
			if ( null === $product ) {
				return;
			}

			if ( 'compatibility' === $content_type ) {
				$compatibility_rows = $this->get_compatibility_rows( $product->get_id() );
				if ( empty( $compatibility_rows ) ) {
					return;
				}
			}
		}

		// Accordion settings.
		$accordion_on      = isset( $settings['accordion_enabled'] ) && 'yes' === $settings['accordion_enabled'];
		$accordion_mobile  = $accordion_on && isset( $settings['accordion_mobile'] ) && 'yes' === $settings['accordion_mobile'];
		$accordion_desktop = $accordion_on && isset( $settings['accordion_desktop'] ) && 'yes' === $settings['accordion_desktop'];

		// Title.
		if ( 'info_box' === $content_type ) {
			$title = isset( $settings['info_box_title'] ) ? $settings['info_box_title'] : '';
		} elseif ( 'compatibility' === $content_type ) {
			$title = __( 'Compatibility', 'bw' );
		} else {
			$title = isset( $settings['table_title'] ) && '' !== trim( $settings['table_title'] )
				? $settings['table_title']
				: __( 'Collection Content', 'bw' );
		}

		// Wrapper classes.
		$wrapper_cls = 'bw-biblio-widget';
		if ( $accordion_on ) {
			$wrapper_cls .= ' bw-biblio-accordion';
			if ( $accordion_mobile ) {
				$wrapper_cls .= ' bw-biblio-accordion--mobile'; }
			if ( $accordion_desktop ) {
				$wrapper_cls .= ' bw-biblio-accordion--desktop'; }
		}

		$this->add_render_attribute( 'wrapper', 'class', $wrapper_cls );
		$this->add_render_attribute( 'wrapper', 'data-widget-id', $this->get_id() );

		// SVG chevron.
		$arrow = '<svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 1.5L7 7.5L13 1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';

		if ( $accordion_on ) {
			echo '<button class="bw-biblio-accordion__trigger" type="button" aria-expanded="false">';
			echo '<span class="bw-biblio-title">' . esc_html( $title ) . '</span>';
			echo '<span class="bw-biblio-accordion__arrow">' . $arrow . '</span>';
			echo '</button>';
			echo '<div class="bw-biblio-accordion__body" aria-hidden="true">';
			echo '<div class="bw-biblio-accordion__body-inner">';
		} else {
			echo '<div class="bw-biblio-title">' . esc_html( $title ) . '</div>';
		}

		if ( 'info_box' === $content_type ) {
			$this->render_info_box( $settings );
		} elseif ( 'compatibility' === $content_type ) {
			$this->render_compatibility( $compatibility_rows );
		} else {
				$this->render_product_details( $product, $this->get_id() );
		}

		if ( $accordion_on ) {
			echo '</div>'; // .bw-biblio-accordion__body-inner
			echo '</div>'; // .bw-biblio-accordion__body
		}

		echo '</div>'; // .bw-biblio-widget
	}

	// =========================================================
	// PRIVATE HELPERS
	// =========================================================

	/**
	 * Resolve the WooCommerce product for the current context.
	 * Returns WC_Product on success, null on failure (notice already printed in editor).
	 */
	private function resolve_product() {
		$is_editor = class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! function_exists( 'wc_get_product' ) ) {
			if ( $is_editor ) {
				echo '<div class="bw-product-details-widget__notice">' . esc_html__( 'BW Product Details: WooCommerce not available.', 'bw' ) . '</div>';
			}
			return null;
		}

		$resolution = function_exists( 'bw_tbl_resolve_product_context_id' )
			? bw_tbl_resolve_product_context_id( array( '__widget_class' => __CLASS__ ) )
			: array(
				'id'     => absint( get_the_ID() ),
				'source' => 'fallback',
			);

		$product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;

		if ( ! $product_id ) {
			if ( $is_editor ) {
				echo '<div class="bw-product-details-widget__notice">' . esc_html__( 'BW Product Details: No product found. Select a Preview Product in Theme Builder.', 'bw' ) . '</div>';
			}
			return null;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			if ( $is_editor ) {
				echo '<div class="bw-product-details-widget__notice">' . esc_html__( 'BW Product Details: Product not found.', 'bw' ) . '</div>';
			}
			return null;
		}

		return $product;
	}

	/**
	 * Render Info Box content (WYSIWYG).
	 */
	private function render_info_box( $settings ) {
		$content = isset( $settings['info_box_content'] ) ? $settings['info_box_content'] : '';
		if ( '' !== $content ) {
			echo '<div class="bw-biblio-info-content">' . wp_kses_post( $content ) . '</div>';
		}
	}

	/**
	 * Render Product Details rows.
	 *
	 * @param \WC_Product $product
	 * @param string      $widget_id Widget instance ID.
	 */
	private function render_product_details( $product, $widget_id = '' ) {
		$product_id = $product->get_id();

		$digital_fields = function_exists( 'bw_get_digital_product_fields' )
			? bw_get_digital_product_fields()
			: array(
				'_digital_total_assets' => __( 'Total Assets', 'bw' ),
				'_digital_assets_list'  => __( 'Assets List', 'bw' ),
				'_digital_file_size'    => __( 'File size', 'bw' ),
				'_digital_formats'      => __( 'Formats included', 'bw' ),
				'_bw_artist_name'       => __( 'Digital Author', 'bw' ),
				'_digital_source'       => __( 'Source', 'bw' ),
				'_digital_publisher'    => __( 'Publisher', 'bw' ),
				'_digital_year'         => __( 'Year', 'bw' ),
				'_digital_technique'    => __( 'Technique', 'bw' ),
			);

		$book_fields = function_exists( 'bw_get_bibliographic_fields' )
			? bw_get_bibliographic_fields()
			: array(
				'_bw_biblio_title'     => __( 'Title', 'bw' ),
				'_bw_biblio_author'    => __( 'Author', 'bw' ),
				'_bw_biblio_publisher' => __( 'Publisher', 'bw' ),
				'_bw_biblio_year'      => __( 'Year', 'bw' ),
				'_bw_biblio_location'  => __( 'Place of Publication', 'bw' ),
				'_bw_biblio_language'  => __( 'Language', 'bw' ),
				'_bw_biblio_binding'   => __( 'Binding', 'bw' ),
				'_bw_biblio_pages'     => __( 'Pages', 'bw' ),
				'_bw_biblio_edition'   => __( 'Edition', 'bw' ),
				'_bw_biblio_condition' => __( 'Condition', 'bw' ),
			);

		$print_fields = function_exists( 'bw_get_prints_bibliographic_fields' )
			? bw_get_prints_bibliographic_fields()
			: array(
				'_print_artist'     => __( 'Artist', 'bw' ),
				'_print_publisher'  => __( 'Publisher', 'bw' ),
				'_print_year'       => __( 'Year', 'bw' ),
				'_print_technique'  => __( 'Technique', 'bw' ),
				'_print_material'   => __( 'Material', 'bw' ),
				'_print_plate_size' => __( 'Plate Size', 'bw' ),
				'_print_condition'  => __( 'Condition', 'bw' ),
			);

		$sections = array(
			array(
				'id'     => 'digital',
				'fields' => $digital_fields,
			),
			array(
				'id'     => 'prints',
				'fields' => $print_fields,
			),
			array(
				'id'     => 'books',
				'fields' => $book_fields,
			),
		);

		foreach ( $sections as $section ) {
			$rows = $this->get_section_rows( $product_id, $section['fields'] );

			if ( empty( $rows ) ) {
				continue;
			}

			echo '<div class="bw-biblio-section">';

			if ( ! empty( $section['subtitle'] ) ) {
				echo '<div class="bw-biblio-section-subtitle">' . esc_html( $section['subtitle'] ) . '</div>';
			}

			echo '<div class="bw-biblio-table bw-biblio-table--product-details">';

			if ( 'digital' === $section['id'] ) {
				$total_assets_row = $this->pull_row_by_meta( $rows, '_digital_total_assets' );
				$assets_list_row  = $this->pull_row_by_meta( $rows, '_digital_assets_list' );

				if ( $total_assets_row || $assets_list_row ) {
					$total_assets_value = $total_assets_row ? esc_html( $total_assets_row['value'] ) : '';
					$assets_list_value  = $assets_list_row ? nl2br( esc_html( $assets_list_row['value'] ) ) : '';
					$assets_list_id     = 'bw-biblio-assets-list-content-' . sanitize_html_class( $widget_id ? $widget_id : (string) $product_id );

					echo '<div class="bw-biblio-row bw-biblio-row--assets">';
					echo '<div class="bw-biblio-label bw-biblio-label--assets">' . $total_assets_value . '</div>';
					echo '<div class="bw-biblio-value bw-biblio-value--assets-list">';
					echo '<div id="' . esc_attr( $assets_list_id ) . '" class="bw-biblio-assets-list__content">' . $assets_list_value . '</div>';
					echo '<button type="button" class="bw-biblio-assets-list__toggle" aria-expanded="false" aria-controls="' . esc_attr( $assets_list_id ) . '" hidden>' . esc_html__( 'View complete list', 'bw' ) . '</button>';
					echo '</div>';
					echo '</div>';
				}
			}

			foreach ( $rows as $row ) {
				$value = '_digital_formats' === $row['meta']
					? $this->render_formats_pills( $row['value'] )
					: esc_html( $row['value'] );

				$row_classes = array( 'bw-biblio-row' );
				if ( in_array( $row['meta'], array( '_bw_biblio_title', '_bw_biblio_author', '_bw_biblio_publisher', '_bw_biblio_binding', '_bw_biblio_edition', '_digital_publisher' ), true ) ) {
					$row_classes[] = 'bw-biblio-row--long-text';
				} else {
					$row_classes[] = 'bw-biblio-row--compact';
				}

				echo '<div class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $row_classes ) ) ) . '">';
				echo '<div class="bw-biblio-label">' . esc_html( $row['label'] ) . '</div>';
				echo '<div class="bw-biblio-value">' . $value . '</div>';
				echo '</div>';
			}

			echo '</div>'; // .bw-biblio-table
			echo '</div>'; // .bw-biblio-section
		}
	}

	/**
	 * Render the compatibility block.
	 *
	 * @param array<int, array<string, string>> $rows Enabled compatibility rows.
	 */
	private function render_compatibility( $rows ) {
		if ( empty( $rows ) ) {
			return;
		}

		echo '<div class="bw-biblio-table bw-biblio-table--compatibility">';

		foreach ( $rows as $row ) {
			echo '<div class="bw-biblio-row bw-biblio-row--compatibility">';
			echo '<div class="bw-biblio-value bw-biblio-value--compatibility">' . esc_html( $row['label'] ) . '</div>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Resolve compatibility rows for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, string>>
	 */
	private function get_compatibility_rows( $product_id ) {
		if ( function_exists( 'bw_get_enabled_product_compatibility_rows' ) ) {
			return bw_get_enabled_product_compatibility_rows( $product_id );
		}

		return array(
			array(
				'meta'  => '_bw_compatibility_adobe_illustrator_photoshop',
				'label' => __( 'Adobe Illustrator, Photoshop', 'bw' ),
			),
			array(
				'meta'  => '_bw_compatibility_figma_sketch_adobe_xd',
				'label' => __( 'Figma, Sketch, Adobe XD', 'bw' ),
			),
			array(
				'meta'  => '_bw_compatibility_affinity_designer_photo',
				'label' => __( 'Affinity Designer & Photo', 'bw' ),
			),
			array(
				'meta'  => '_bw_compatibility_coreldraw_inkscape',
				'label' => __( 'CorelDRAW, Inkscape', 'bw' ),
			),
			array(
				'meta'  => '_bw_compatibility_canva_powerpoint',
				'label' => __( 'Canva, PowerPoint', 'bw' ),
			),
			array(
				'meta'  => '_bw_compatibility_cricut_silhouette',
				'label' => __( 'Cricut, Silhouette', 'bw' ),
			),
			array(
				'meta'  => '_bw_compatibility_blender_cinema4d',
				'label' => __( 'Blender, Cinema 4D', 'bw' ),
			),
		);
	}

	private function get_section_rows( $product_id, $fields ) {
		$rows = array();
		foreach ( $fields as $meta_key => $label ) {
			$value = get_post_meta( $product_id, $meta_key, true );
			if ( '_bw_artist_name' === $meta_key && '' === $value ) {
				$value = get_post_meta( $product_id, '_digital_artist_name', true );
			}
			if ( '' === $value ) {
				continue;
			}
			$rows[] = array(
				'meta'  => $meta_key,
				'label' => $label,
				'value' => $value,
			);
		}
		return $rows;
	}

	private function pull_row_by_meta( &$rows, $meta_key ) {
		foreach ( $rows as $index => $row ) {
			if ( $meta_key === $row['meta'] ) {
				unset( $rows[ $index ] );
				return $row;
			}
		}
		return null;
	}

	private function render_formats_pills( $value ) {
		$formats = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		if ( empty( $formats ) ) {
			return '';
		}
		$pills = array_map(
			static function ( $format ) {
				return '<span class="bw-biblio-pill">' . esc_html( strtoupper( $format ) ) . '</span>';
			},
			$formats
		);
		return '<div class="bw-biblio-pills">' . implode( '', $pills ) . '</div>';
	}
}
