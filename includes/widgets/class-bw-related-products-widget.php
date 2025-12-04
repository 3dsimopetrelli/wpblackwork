<?php
/**
 * BW Related Products Widget
 *
 * Display related products using centralized product card renderer.
 * Features complete style controls like BW Wallpost and BW Slick Slider.
 *
 * @package BW_Main_Elementor_Widgets
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Related_Products_Widget extends Widget_Base {

	public function get_name() {
		return 'bw-related-products';
	}

	public function get_title() {
		return esc_html__( 'BW Related Products', 'bw-elementor-widgets' );
	}

	public function get_icon() {
		return 'eicon-product-related';
	}

	public function get_categories() {
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		return [ 'bw-product-card-style', 'bw-related-products-style' ];
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_image_controls();
		$this->register_style_controls();
	}

	/**
	 * Content Controls
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'bw-elementor-widgets' ),
			]
		);

		$this->add_control(
			'query_by',
			[
				'label'   => __( 'Query by', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'category',
				'options' => [
					'category'    => __( 'Category', 'bw-elementor-widgets' ),
					'subcategory' => __( 'Subcategory', 'bw-elementor-widgets' ),
					'tag'         => __( 'Tag', 'bw-elementor-widgets' ),
				],
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'   => __( 'Numero di prodotti', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'        => __( 'Show Title', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_description',
			[
				'label'        => __( 'Show Description', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'show_price',
			[
				'label'        => __( 'Show Price', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'open_cart_popup',
			[
				'label'        => __( 'Apri cart pop-up su Add to Cart', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Sì', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Se attivo, il pulsante Add to Cart apre il cart pop-up.', 'bw-elementor-widgets' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Layout Controls
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout', 'bw-elementor-widgets' ),
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'   => __( 'Columns', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options' => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
			]
		);

		$this->add_responsive_control(
			'gap',
			[
				'label'      => __( 'Gap', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
				],
				'default' => [
					'size' => 24,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .bw-related-products-grid' => '--bw-rp-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'margin_top',
			[
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px'  => [ 'min' => 0, 'max' => 300 ],
					'em'  => [ 'min' => 0, 'max' => 20 ],
					'rem' => [ 'min' => 0, 'max' => 20 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-related-products-widget' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'margin_bottom',
			[
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px'  => [ 'min' => 0, 'max' => 300 ],
					'em'  => [ 'min' => 0, 'max' => 20 ],
					'rem' => [ 'min' => 0, 'max' => 20 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-related-products-widget' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Image Controls
	 */
	private function register_image_controls() {
		$this->start_controls_section(
			'section_image',
			[
				'label' => __( 'Image Settings', 'bw-elementor-widgets' ),
			]
		);

		$this->add_control(
			'image_toggle',
			[
				'label'        => __( 'Show Featured Image', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'   => __( 'Image Size', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'thumbnail'    => __( 'Thumbnail', 'bw-elementor-widgets' ),
					'medium'       => __( 'Medium', 'bw-elementor-widgets' ),
					'medium_large' => __( 'Medium Large', 'bw-elementor-widgets' ),
					'large'        => __( 'Large', 'bw-elementor-widgets' ),
					'full'         => __( 'Full', 'bw-elementor-widgets' ),
				],
				'default'   => 'large',
				'condition' => [ 'image_toggle' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'image_height',
			[
				'label'      => __( 'Image Height', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 100, 'max' => 1000 ],
				],
				'default' => [
					'size' => 400,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-media img' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bw-product-card .bw-ss__media img' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [ 'image_toggle' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => __( 'Image Border Radius', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'      => '8',
					'right'    => '8',
					'bottom'   => '8',
					'left'     => '8',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-media'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bw-product-card .bw-wallpost-media img' => 'border-radius: inherit;',
					'{{WRAPPER}} .bw-product-card .bw-wallpost-overlay' => 'border-radius: inherit;',
					'{{WRAPPER}} .bw-product-card .bw-wallpost-image'   => 'border-radius: inherit;',
				],
				'condition' => [ 'image_toggle' => 'yes' ],
			]
		);

		$this->add_control(
			'image_object_fit',
			[
				'label'   => __( 'Image Object Fit', 'bw-elementor-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'cover'   => __( 'Cover', 'bw-elementor-widgets' ),
					'contain' => __( 'Contain', 'bw-elementor-widgets' ),
					'fill'    => __( 'Fill', 'bw-elementor-widgets' ),
					'none'    => __( 'None', 'bw-elementor-widgets' ),
				],
				'default'   => 'cover',
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-media img' => 'object-fit: {{VALUE}};',
				],
				'condition' => [ 'image_toggle' => 'yes' ],
			]
		);

		$this->add_control(
			'image_background_color',
			[
				'label'     => __( 'Background Immagine', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-media' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card .bw-wallpost-image' => 'background-color: {{VALUE}};',
				],
				'condition' => [ 'image_toggle' => 'yes' ],
			]
		);

		$this->add_control(
			'hover_effect',
			[
				'label'        => __( 'Enable Hover Effect', 'bw-elementor-widgets' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
				'label_off'    => __( 'No', 'bw-elementor-widgets' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style Controls
	 */
	private function register_style_controls() {
		// Typography Section
		$this->start_controls_section(
			'section_typography',
			[
				'label' => __( 'Typography', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		// Title
		$this->add_control(
			'title_typography_heading',
			[
				'label' => __( 'Titolo', 'bw-elementor-widgets' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Colore titolo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card' => '--bw-card-title-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .bw-product-card .bw-wallpost-title',
			]
		);

		$this->add_responsive_control(
			'title_margin_top',
			[
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -100, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-title' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_margin_bottom',
			[
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -100, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Description
		$this->add_control(
			'description_typography_heading',
			[
				'label'     => __( 'Descrizione', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => __( 'Colore descrizione', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-description' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card' => '--bw-card-description-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .bw-product-card .bw-wallpost-description',
			]
		);

		$this->add_responsive_control(
			'description_margin_top',
			[
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -100, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-description' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'description_margin_bottom',
			[
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -100, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Price
		$this->add_control(
			'price_typography_heading',
			[
				'label'     => __( 'Prezzo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'price_color',
			[
				'label'     => __( 'Colore prezzo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-price' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bw-product-card' => '--bw-card-price-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .bw-product-card .bw-wallpost-price',
			]
		);

		$this->add_responsive_control(
			'price_margin_top',
			[
				'label'      => __( 'Margin Top', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -100, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-price' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'price_margin_bottom',
			[
				'label'      => __( 'Margin Bottom', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -100, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-wallpost-price' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Overlay Buttons Section
		$this->start_controls_section(
			'section_overlay_buttons',
			[
				'label' => __( 'Overlay Buttons', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'overlay_buttons_typography',
				'selector' => '{{WRAPPER}} .bw-product-card .bw-wallpost-overlay-button',
			]
		);

		$this->start_controls_tabs( 'overlay_buttons_color_tabs' );

		$this->start_controls_tab(
			'overlay_buttons_color_normal',
			[
				'label' => __( 'Normal', 'bw-elementor-widgets' ),
			]
		);

		$this->add_control(
			'overlay_buttons_text_color',
			[
				'label'     => __( 'Colore testo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-product-card' => '--bw-card-overlay-buttons-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'overlay_buttons_background_color',
			[
				'label'     => __( 'Colore sfondo', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => [
					'{{WRAPPER}} .bw-product-card' => '--bw-card-overlay-buttons-background: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'overlay_buttons_color_hover',
			[
				'label' => __( 'Hover', 'bw-elementor-widgets' ),
			]
		);

		$this->add_control(
			'overlay_buttons_text_color_hover',
			[
				'label'     => __( 'Colore testo (hover)', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-product-card' => '--bw-card-overlay-buttons-color-hover: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'overlay_buttons_background_color_hover',
			[
				'label'     => __( 'Colore sfondo (hover)', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#80FD03',
				'selectors' => [
					'{{WRAPPER}} .bw-product-card' => '--bw-card-overlay-buttons-background-hover: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'overlay_buttons_border_radius',
			[
				'label'      => __( 'Raggio bordi', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 200 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card' => '--bw-card-overlay-buttons-radius: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'overlay_buttons_padding',
			[
				'label'      => __( 'Padding pulsanti', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'default'    => [
					'top'      => '13',
					'right'    => '10',
					'bottom'   => '13',
					'left'     => '10',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card' => '--bw-card-overlay-buttons-padding-top: {{TOP}}{{UNIT}}; --bw-card-overlay-buttons-padding-right: {{RIGHT}}{{UNIT}}; --bw-card-overlay-buttons-padding-bottom: {{BOTTOM}}{{UNIT}}; --bw-card-overlay-buttons-padding-left: {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Card Container Section
		$this->start_controls_section(
			'section_card_container',
			[
				'label' => __( 'Card Container', 'bw-elementor-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => __( 'Padding Card', 'bw-elementor-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-product-card .bw-slick-slider-text-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_background_color',
			[
				'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-product-card .bw-slick-slider-text-box' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get related products based on query type
	 */
	protected function get_related_products_by_type( $product, $query_by, $posts_per_page ) {
		$product_id  = $product->get_id();
		$related_ids = [];

		switch ( $query_by ) {
			case 'subcategory':
				$related_ids = $this->get_related_by_subcategory( $product_id );
				break;

			case 'tag':
				$related_ids = $this->get_related_by_tag( $product_id );
				break;

			case 'category':
			default:
				$related_ids = wc_get_related_products(
					$product_id,
					$posts_per_page,
					$product->get_upsell_ids()
				);
				break;
		}

		// Limit results
		if ( count( $related_ids ) > $posts_per_page ) {
			$related_ids = array_slice( $related_ids, 0, $posts_per_page );
		}

		return $related_ids;
	}

	/**
	 * Get related products by subcategory
	 */
	protected function get_related_by_subcategory( $product_id ) {
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );

		if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
			return [];
		}

		// Find subcategories (categories with parent)
		$subcategories = [];
		foreach ( $product_categories as $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
				$subcategories[] = $cat_id;
			}
		}

		if ( empty( $subcategories ) ) {
			return [];
		}

		// Query products with the same subcategory
		$query_args = [
			'post_type'      => 'product',
			'posts_per_page' => 50,
			'post__not_in'   => [ $product_id ],
			'orderby'        => 'rand',
			'fields'         => 'ids',
			'tax_query'      => [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $subcategories,
				],
			],
		];

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Get related products by tag
	 */
	protected function get_related_by_tag( $product_id ) {
		$product_tags = wp_get_post_terms( $product_id, 'product_tag', [ 'fields' => 'ids' ] );

		if ( empty( $product_tags ) || is_wp_error( $product_tags ) ) {
			return [];
		}

		// Query products with the same tags
		$query_args = [
			'post_type'      => 'product',
			'posts_per_page' => 50,
			'post__not_in'   => [ $product_id ],
			'orderby'        => 'rand',
			'fields'         => 'ids',
			'tax_query'      => [
				[
					'taxonomy' => 'product_tag',
					'field'    => 'term_id',
					'terms'    => $product_tags,
					'operator' => 'IN',
				],
			],
		];

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Get current product context
	 */
	protected function get_current_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		global $product;

		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		$queried_id = get_queried_object_id();
		if ( $queried_id ) {
			$maybe_product = wc_get_product( $queried_id );
			if ( $maybe_product instanceof \WC_Product ) {
				return $maybe_product;
			}
		}

		if ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$editor_product = wc_get_product( get_the_ID() );
			if ( $editor_product instanceof \WC_Product ) {
				return $editor_product;
			}
		}

		return null;
	}

	/**
	 * Get fallback products for preview
	 */
	protected function get_fallback_products( $limit = 4 ) {
		$query_args = [
			'post_type'      => 'product',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		];

		$query = new \WP_Query( $query_args );
		return $query->posts;
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		if ( ! function_exists( 'wc_get_product' ) || ! class_exists( 'BW_Product_Card_Renderer' ) ) {
			return;
		}

		$settings        = $this->get_settings_for_display();
		$query_by        = isset( $settings['query_by'] ) ? $settings['query_by'] : 'category';
		$posts_per_page  = isset( $settings['posts_per_page'] ) ? absint( $settings['posts_per_page'] ) : 4;
		$columns         = isset( $settings['columns'] ) ? absint( $settings['columns'] ) : 4;
		$image_size      = isset( $settings['image_size'] ) ? $settings['image_size'] : 'large';
		$show_image      = isset( $settings['image_toggle'] ) && 'yes' === $settings['image_toggle'];
		$show_hover      = isset( $settings['hover_effect'] ) && 'yes' === $settings['hover_effect'];
		$show_title      = isset( $settings['show_title'] ) && 'yes' === $settings['show_title'];
		$show_description = isset( $settings['show_description'] ) && 'yes' === $settings['show_description'];
		$show_price      = isset( $settings['show_price'] ) && 'yes' === $settings['show_price'];
		$open_cart_popup = isset( $settings['open_cart_popup'] ) && 'yes' === $settings['open_cart_popup'];

		$product = $this->get_current_product();
		$is_editor = class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		// In editor mode without a product, show fallback
		if ( ! $product instanceof \WC_Product ) {
			if ( $is_editor ) {
				$related_product_ids = $this->get_fallback_products( $posts_per_page );
				if ( empty( $related_product_ids ) ) {
					echo '<div class="bw-related-products-widget bw-related-products-empty">';
					echo '<p>' . esc_html__( 'Nessun prodotto disponibile per l\'anteprima.', 'bw-elementor-widgets' ) . '</p>';
					echo '</div>';
					return;
				}
			} else {
				return; // No product in frontend
			}
		} else {
			$related_product_ids = $this->get_related_products_by_type( $product, $query_by, $posts_per_page );
		}

		// Show fallback message if no products
		if ( empty( $related_product_ids ) ) {
			echo '<div class="bw-related-products-widget bw-related-products-empty">';
			echo '<p>' . esc_html__( 'Non ci sono prodotti correlati.', 'bw-elementor-widgets' ) . '</p>';
			echo '</div>';
			return;
		}

		// Prepare card settings
		$card_settings = [
			'image_size'          => $image_size,
			'show_image'          => $show_image,
			'show_hover_image'    => $show_hover,
			'show_title'          => $show_title,
			'show_description'    => $show_description,
			'show_price'          => $show_price,
			'show_buttons'        => true,
			'show_add_to_cart'    => true,
			'open_cart_popup'     => $open_cart_popup,
		];

		?>
		<div class="bw-related-products-widget">
			<div class="bw-related-products-grid" style="--bw-rp-columns: <?php echo esc_attr( $columns ); ?>;">
				<?php
				foreach ( $related_product_ids as $related_product_id ) {
					echo BW_Product_Card_Renderer::render_card( $related_product_id, $card_settings );
				}
				?>
			</div>
		</div>
		<?php
	}
}
