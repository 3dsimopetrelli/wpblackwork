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
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		return [ 'bw-product-details-style' ];
	}

	public function get_script_depends() {
		return [ 'bw-product-details-script' ];
	}

	protected function register_controls() {

		// =========================================================
		// TAB: CONTENT
		// =========================================================
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Content', 'bw' ),
		] );

		$this->add_control( 'content_type', [
			'label'   => __( 'Content Type', 'bw' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'product_details',
			'options' => [
				'product_details' => __( 'Product Details', 'bw' ),
				'info_box'        => __( 'Info Box', 'bw' ),
			],
		] );

		// --- Product Details: table title ---
		$this->add_control( 'table_title', [
			'label'       => __( 'Table Title', 'bw' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => __( 'Product Details', 'bw' ),
			'default'     => __( 'Product Details', 'bw' ),
			'condition'   => [ 'content_type' => 'product_details' ],
		] );

		// --- Info Box: title ---
		$this->add_control( 'info_box_title', [
			'label'     => __( 'Title', 'bw' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Info', 'bw' ),
			'condition' => [ 'content_type' => 'info_box' ],
		] );

		// --- Info Box: WYSIWYG content ---
		$this->add_control( 'info_box_content', [
			'label'     => __( 'Content', 'bw' ),
			'type'      => Controls_Manager::WYSIWYG,
			'default'   => '',
			'condition' => [ 'content_type' => 'info_box' ],
		] );

		// --- Accordion ---
		$this->add_control( 'accordion_divider', [
			'type'  => Controls_Manager::DIVIDER,
			'style' => 'thick',
		] );

		$this->add_control( 'accordion_enabled', [
			'label'        => __( 'Enable Accordion', 'bw' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'bw' ),
			'label_off'    => __( 'Off', 'bw' ),
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->add_control( 'accordion_mobile', [
			'label'        => __( 'Enable on Mobile / Tablet', 'bw' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'bw' ),
			'label_off'    => __( 'Off', 'bw' ),
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => [ 'accordion_enabled' => 'yes' ],
		] );

		$this->add_control( 'accordion_desktop', [
			'label'        => __( 'Enable on Desktop', 'bw' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'bw' ),
			'label_off'    => __( 'Off', 'bw' ),
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => [ 'accordion_enabled' => 'yes' ],
		] );

		$this->end_controls_section();

		// =========================================================
		// TAB: STYLE
		// =========================================================

		$this->start_controls_section( 'section_style_box', [
			'label' => __( 'Box Style', 'bw' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'box_border_color', [
			'label'     => __( 'Border Color', 'bw' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#000000',
			'selectors' => [
				'{{WRAPPER}} .bw-biblio-widget' => 'border-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'box_border_width', [
			'label'      => __( 'Border Width', 'bw' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
			'default'    => [ 'size' => 1, 'unit' => 'px' ],
			'selectors'  => [
				'{{WRAPPER}} .bw-biblio-widget' => 'border-width: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'box_border_radius', [
			'label'      => __( 'Border Radius', 'bw' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'default'    => [ 'size' => 8, 'unit' => 'px' ],
			'selectors'  => [
				'{{WRAPPER}} .bw-biblio-widget' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_responsive_control( 'box_padding', [
			'label'      => __( 'Padding', 'bw' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'default'    => [
				'top'    => 16,
				'right'  => 16,
				'bottom' => 16,
				'left'   => 16,
				'unit'   => 'px',
			],
			'selectors'  => [
				'{{WRAPPER}} .bw-biblio-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style_title', [
			'label' => __( 'Title', 'bw' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'title_typography',
			'selector' => '{{WRAPPER}} .bw-biblio-title',
		] );

		$this->add_control( 'title_color', [
			'label'     => __( 'Color', 'bw' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .bw-biblio-title' => 'color: {{VALUE}};',
			],
		] );

		$this->add_responsive_control( 'title_padding', [
			'label'      => __( 'Padding', 'bw' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'default'    => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 0,
				'unit'   => 'px',
			],
			'selectors'  => [
				'{{WRAPPER}} .bw-biblio-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style_dividers', [
			'label' => __( 'Row Dividers', 'bw' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'divider_color', [
			'label'     => __( 'Divider Color', 'bw' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#d9d9d9',
			'selectors' => [
				'{{WRAPPER}} .bw-biblio-row:not(:last-child)' => 'border-bottom-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'divider_width', [
			'label'      => __( 'Divider Weight', 'bw' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
			'default'    => [ 'size' => 1, 'unit' => 'px' ],
			'selectors'  => [
				'{{WRAPPER}} .bw-biblio-row:not(:last-child)' => 'border-bottom-width: {{SIZE}}{{UNIT}}; border-bottom-style: solid;',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style_labels', [
			'label' => __( 'Labels Typography', 'bw' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'labels_typography',
			'selector' => '{{WRAPPER}} .bw-biblio-label',
		] );

		$this->add_control( 'labels_color', [
			'label'     => __( 'Color', 'bw' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .bw-biblio-label' => 'color: {{VALUE}};',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style_values', [
			'label' => __( 'Values Typography', 'bw' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'values_typography',
			'selector' => '{{WRAPPER}} .bw-biblio-value',
		] );

		$this->add_control( 'values_color', [
			'label'     => __( 'Color', 'bw' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .bw-biblio-value' => 'color: {{VALUE}};',
			],
		] );

		$this->add_responsive_control( 'values_alignment', [
			'label'     => __( 'Text Alignment', 'bw' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => [
				'left'   => [ 'title' => __( 'Left', 'bw' ),   'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
				'right'  => [ 'title' => __( 'Right', 'bw' ),  'icon' => 'eicon-text-align-right' ],
			],
			'default'   => 'left',
			'selectors' => [
				'{{WRAPPER}} .bw-biblio-value' => 'text-align: {{VALUE}};',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style_assets', [
			'label' => __( 'Assets Typography', 'bw' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'assets_typography',
			'selector' => '{{WRAPPER}} .bw-biblio-row--assets .bw-biblio-label--assets, {{WRAPPER}} .bw-biblio-row--assets .bw-biblio-value--assets-list',
		] );

		$this->end_controls_section();
	}

	// =========================================================
	// RENDER
	// =========================================================

	protected function render() {
		$settings     = $this->get_settings_for_display();
		$content_type = isset( $settings['content_type'] ) ? $settings['content_type'] : 'product_details';

		// For product_details: resolve product before building DOM (bail early on failure).
		$product = null;
		if ( 'product_details' === $content_type ) {
			$product = $this->resolve_product();
			if ( null === $product ) {
				return;
			}
		}

		// Accordion settings.
		$accordion_on      = isset( $settings['accordion_enabled'] ) && 'yes' === $settings['accordion_enabled'];
		$accordion_mobile  = $accordion_on && isset( $settings['accordion_mobile'] )  && 'yes' === $settings['accordion_mobile'];
		$accordion_desktop = $accordion_on && isset( $settings['accordion_desktop'] ) && 'yes' === $settings['accordion_desktop'];

		// Title.
		if ( 'info_box' === $content_type ) {
			$title = isset( $settings['info_box_title'] ) ? $settings['info_box_title'] : '';
		} else {
			$title = isset( $settings['table_title'] ) && '' !== trim( $settings['table_title'] )
				? $settings['table_title']
				: __( 'Product Details', 'bw' );
		}

		// Wrapper classes.
		$wrapper_cls = 'bw-biblio-widget';
		if ( $accordion_on ) {
			$wrapper_cls .= ' bw-biblio-accordion';
			if ( $accordion_mobile )  { $wrapper_cls .= ' bw-biblio-accordion--mobile'; }
			if ( $accordion_desktop ) { $wrapper_cls .= ' bw-biblio-accordion--desktop'; }
		}

		$this->add_render_attribute( 'wrapper', 'class', $wrapper_cls );

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
		} else {
			$this->render_product_details( $product );
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
			? bw_tbl_resolve_product_context_id( [ '__widget_class' => __CLASS__ ] )
			: [ 'id' => absint( get_the_ID() ), 'source' => 'fallback' ];

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
	 */
	private function render_product_details( $product ) {
		$product_id = $product->get_id();

		$digital_fields = function_exists( 'bw_get_digital_product_fields' )
			? bw_get_digital_product_fields()
			: [
				'_digital_total_assets' => __( 'Total Assets', 'bw' ),
				'_digital_assets_list'  => __( 'Assets List', 'bw' ),
				'_digital_file_size'    => __( 'File size', 'bw' ),
				'_digital_formats'      => __( 'Formats included', 'bw' ),
				'_digital_source'       => __( 'Source', 'bw' ),
				'_digital_publisher'    => __( 'Publisher', 'bw' ),
				'_digital_year'         => __( 'Year', 'bw' ),
				'_digital_technique'    => __( 'Technique', 'bw' ),
			];

		$book_fields = function_exists( 'bw_get_bibliographic_fields' )
			? bw_get_bibliographic_fields()
			: [
				'_bw_biblio_title'     => __( 'Title', 'bw' ),
				'_bw_biblio_author'    => __( 'Author', 'bw' ),
				'_bw_biblio_publisher' => __( 'Publisher', 'bw' ),
				'_bw_biblio_year'      => __( 'Year', 'bw' ),
				'_bw_biblio_language'  => __( 'Language', 'bw' ),
				'_bw_biblio_binding'   => __( 'Binding', 'bw' ),
				'_bw_biblio_pages'     => __( 'Pages', 'bw' ),
				'_bw_biblio_edition'   => __( 'Edition', 'bw' ),
				'_bw_biblio_condition' => __( 'Condition', 'bw' ),
				'_bw_biblio_location'  => __( 'Location', 'bw' ),
			];

		$print_fields = function_exists( 'bw_get_prints_bibliographic_fields' )
			? bw_get_prints_bibliographic_fields()
			: [
				'_print_artist'     => __( 'Artist', 'bw' ),
				'_print_publisher'  => __( 'Publisher', 'bw' ),
				'_print_year'       => __( 'Year', 'bw' ),
				'_print_technique'  => __( 'Technique', 'bw' ),
				'_print_material'   => __( 'Material', 'bw' ),
				'_print_plate_size' => __( 'Plate Size', 'bw' ),
				'_print_condition'  => __( 'Condition', 'bw' ),
			];

		$sections = [
			[
				'id'       => 'digital',
				'subtitle' => __( 'Collection content', 'bw' ),
				'fields'   => $digital_fields,
			],
			[
				'id'     => 'prints',
				'fields' => $print_fields,
			],
			[
				'id'     => 'books',
				'fields' => $book_fields,
			],
		];

		foreach ( $sections as $section ) {
			$rows = $this->get_section_rows( $product_id, $section['fields'] );

			if ( empty( $rows ) ) {
				continue;
			}

			echo '<div class="bw-biblio-section">';

			if ( ! empty( $section['subtitle'] ) ) {
				echo '<div class="bw-biblio-section-subtitle">' . esc_html( $section['subtitle'] ) . '</div>';
			}

			echo '<div class="bw-biblio-table">';

			if ( 'digital' === $section['id'] ) {
				$total_assets_row = $this->pull_row_by_meta( $rows, '_digital_total_assets' );
				$assets_list_row  = $this->pull_row_by_meta( $rows, '_digital_assets_list' );

				if ( $total_assets_row || $assets_list_row ) {
					$total_assets_value = $total_assets_row ? esc_html( $total_assets_row['value'] ) : '';
					$assets_list_value  = $assets_list_row  ? nl2br( esc_html( $assets_list_row['value'] ) ) : '';

					echo '<div class="bw-biblio-row bw-biblio-row--assets">';
					echo '<div class="bw-biblio-label bw-biblio-label--assets">' . $total_assets_value . '</div>';
					echo '<div class="bw-biblio-value bw-biblio-value--assets-list">' . $assets_list_value . '</div>';
					echo '</div>';
				}
			}

			foreach ( $rows as $row ) {
				$value = '_digital_formats' === $row['meta']
					? $this->render_formats_pills( $row['value'] )
					: esc_html( $row['value'] );

				echo '<div class="bw-biblio-row">';
				echo '<div class="bw-biblio-label">' . esc_html( $row['label'] ) . '</div>';
				echo '<div class="bw-biblio-value">' . $value . '</div>';
				echo '</div>';
			}

			echo '</div>'; // .bw-biblio-table
			echo '</div>'; // .bw-biblio-section
		}
	}

	private function get_section_rows( $product_id, $fields ) {
		$rows = [];
		foreach ( $fields as $meta_key => $label ) {
			$value = get_post_meta( $product_id, $meta_key, true );
			if ( '' === $value ) {
				continue;
			}
			$rows[] = [
				'meta'  => $meta_key,
				'label' => $label,
				'value' => $value,
			];
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
