<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Price_Variation_Widget extends Widget_Base {

	public function get_name() {
		return 'bw-price-variation';
	}

	public function get_title() {
		return __( 'BW Price Variation', 'bw' );
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	public function get_categories() {
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		return [ 'bw-price-variation-style' ];
	}

	public function get_script_depends() {
		return [ 'bw-price-variation-script' ];
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	private function register_content_controls() {
		$this->start_controls_section(
			'section_add_to_cart_content',
			[
				'label' => __( 'Add To Cart', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_add_to_cart',
			[
				'label'        => __( 'Show Add To Cart Button', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw' ),
				'label_off'    => __( 'No', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'open_cart_popup',
			[
				'label'        => __( 'Open Cart Pop Up', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'show_add_to_cart' => 'yes',
				],
			]
		);

		$this->add_control(
			'add_to_cart_button_text',
			[
				'label'       => __( 'Button Text', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Add to Cart', 'bw' ),
				'placeholder' => __( 'Enter button text', 'bw' ),
				'label_block' => true,
				'condition'   => [
					'show_add_to_cart' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_style_controls() {
		// Price Style
		$this->start_controls_section(
			'section_price_style',
			[
				'label' => __( 'Price', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'price_alignment',
			[
				'label'   => __( 'Alignment', 'bw' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__price-wrapper' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_typography',
				'label'    => __( 'Price Typography', 'bw' ),
				'selector' => '{{WRAPPER}} .bw-price-variation__price, {{WRAPPER}} .bw-price-variation__price .woocommerce-Price-amount, {{WRAPPER}} .bw-price-variation__price .amount',
			]
		);

		$this->add_control(
			'price_color',
			[
				'label'     => __( 'Price Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__price, {{WRAPPER}} .bw-price-variation__price .woocommerce-Price-amount, {{WRAPPER}} .bw-price-variation__price .woocommerce-Price-currencySymbol' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'price_margin_bottom',
			[
				'label'      => __( 'Margin Bottom', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px'  => [ 'min' => 0, 'max' => 100 ],
					'em'  => [ 'min' => 0, 'max' => 10 ],
					'rem' => [ 'min' => 0, 'max' => 10 ],
				],
				'default'    => [ 'size' => 30, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__price-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Variation Buttons Style
		$this->start_controls_section(
			'section_buttons_style',
			[
				'label' => __( 'Variation Buttons', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .bw-price-variation__variation-button',
			]
		);

		$this->add_responsive_control(
			'variation_buttons_alignment',
			[
				'label'   => __( 'Alignment', 'bw' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
					'center'     => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
					'flex-end'   => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variations' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_gap',
			[
				'label'      => __( 'Gap Between Buttons', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
					'em' => [ 'min' => 0, 'max' => 5 ],
				],
				'default'    => [ 'size' => 10, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__variations' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 12,
					'right'  => 24,
					'bottom' => 12,
					'left'   => 24,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
					'em' => [ 'min' => 0, 'max' => 10 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [ 'size' => 50, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Button States (Normal/Active)
		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => __( 'Normal', 'bw' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_switch',
			[
				'label'        => __( 'Show Border', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'bw' ),
				'label_off'    => __( 'No', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'button_border_color',
			[
				'label'     => __( 'Border Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_switch' => 'yes',
				],
			]
		);

		$this->add_control(
			'button_border_width',
			[
				'label'      => __( 'Border Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 10 ],
				],
				'default'    => [ 'size' => 1, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'border-width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'button_border_switch' => 'yes',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_active',
			[
				'label' => __( 'Active', 'bw' ),
			]
		);

		$this->add_control(
			'button_active_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variation-button.active, {{WRAPPER}} .bw-price-variation__variation-button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_active_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variation-button.active, {{WRAPPER}} .bw-price-variation__variation-button:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_active_border_color',
			[
				'label'     => __( 'Border Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__variation-button.active, {{WRAPPER}} .bw-price-variation__variation-button:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_switch' => 'yes',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'buttons_margin_bottom',
			[
				'label'      => __( 'Margin Bottom', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px'  => [ 'min' => 0, 'max' => 100 ],
					'em'  => [ 'min' => 0, 'max' => 10 ],
					'rem' => [ 'min' => 0, 'max' => 10 ],
				],
				'default'    => [ 'size' => 20, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__variations' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		// License Box Style
		$this->start_controls_section(
			'section_box_style',
			[
				'label' => __( 'License Box', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'box_border',
				'label'    => __( 'Border', 'bw' ),
				'selector' => '{{WRAPPER}} .bw-price-variation__license-box',
			]
		);

		$this->add_control(
			'box_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
					'em' => [ 'min' => 0, 'max' => 5 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [ 'size' => 8, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__license-box' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'box_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 20,
					'right'  => 20,
					'bottom' => 20,
					'left'   => 20,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__license-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'box_margin',
			[
				'label'      => __( 'Margin', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__license-box' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'box_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bw-price-variation__license-box' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Add To Cart Button Style
		$this->start_controls_section(
			'section_add_to_cart_style',
			[
				'label'     => __( 'Add To Cart Button', 'bw' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_add_to_cart' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => 'add_to_cart_button_typography',
				'selector'       => '{{WRAPPER}} .bw-add-to-cart-button',
				'fields_options' => [
					'typography'  => [ 'default' => 'yes' ],
					'font_size'   => [
						'default' => [
							'size' => 24,
							'unit' => 'px',
						],
					],
					'line_height' => [
						'default' => [
							'size' => 24,
							'unit' => 'px',
						],
					],
				],
			]
		);

		$this->start_controls_tabs( 'tabs_add_to_cart_button_colors' );

		$this->start_controls_tab(
			'tab_add_to_cart_button_normal',
			[
				'label' => __( 'Normal', 'bw' ),
			]
		);

		$this->add_control(
			'add_to_cart_button_text_color',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'add_to_cart_button_background_color',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#80FD03',
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'add_to_cart_button_border_color',
			[
				'label'     => __( 'Border Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'border-color: {{VALUE}};',
				],
				'condition' => [ 'add_to_cart_button_border_switch' => 'yes' ],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_add_to_cart_button_hover',
			[
				'label' => __( 'Hover', 'bw' ),
			]
		);

		$this->add_control(
			'add_to_cart_button_text_color_hover',
			[
				'label'     => __( 'Text Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-button:hover, {{WRAPPER}} .bw-add-to-cart-button:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'add_to_cart_button_background_color_hover',
			[
				'label'     => __( 'Background Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-button:hover, {{WRAPPER}} .bw-add-to-cart-button:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'add_to_cart_button_border_color_hover',
			[
				'label'     => __( 'Border Color', 'bw' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-button:hover, {{WRAPPER}} .bw-add-to-cart-button:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [ 'add_to_cart_button_border_switch' => 'yes' ],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'add_to_cart_button_border_switch',
			[
				'label'        => __( 'Border', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'add_to_cart_button_border_width',
			[
				'label'      => __( 'Border Width', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 10 ],
				],
				'default'    => [ 'size' => 1, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
				],
				'condition'  => [ 'add_to_cart_button_border_switch' => 'yes' ],
			]
		);

		$this->add_control(
			'add_to_cart_button_border_radius',
			[
				'label'      => __( 'Border Radius', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'default'    => [ 'size' => 100, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'add_to_cart_button_padding',
			[
				'label'      => __( 'Padding', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 15,
					'right'  => 25,
					'bottom' => 15,
					'left'   => 25,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'add_to_cart_button_margin',
			[
				'label'      => __( 'Margin', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => 20,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .bw-add-to-cart-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'add_to_cart_button_width_switch',
			[
				'label'        => __( 'Use Button Width (%)', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_responsive_control(
			'add_to_cart_button_width',
			[
				'label'      => __( 'Button Width (%)', 'bw' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => 1, 'max' => 100 ],
				],
				'default'    => [ 'size' => 100, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-add-to-cart-button' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'add_to_cart_button_width_switch' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'add_to_cart_button_alignment',
			[
				'label'   => __( 'Alignment', 'bw' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'left',
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-wrapper' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		global $product;
		$product = wc_get_product( get_the_ID() );

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

                $available_variations = $product->get_available_variations();

                if ( empty( $available_variations ) ) {
                        return;
                }

                // Build a normalized list of variations with price details and attributes
                $variations_data = [];
                foreach ( $available_variations as $variation ) {
                        $variation_id   = $variation['variation_id'];
                        $variation_obj  = wc_get_product( $variation_id );
                        $price_value    = isset( $variation['display_price'] ) ? $variation['display_price'] : wc_get_price_to_display( $variation_obj );
                        $price_html     = ! empty( $variation['price_html'] ) ? $variation['price_html'] : wc_price( $price_value );
                        $attributes_set = isset( $variation['attributes'] ) ? $variation['attributes'] : [];
                        $license_html   = get_post_meta( $variation_id, '_bw_variation_license_html', true );

                        $variations_data[] = [
                                'id'            => $variation_id,
                                'price'         => $price_value,
                                'price_html'    => $price_html,
                                'sku'           => $variation_obj ? $variation_obj->get_sku() : '',
                                'attributes'    => $attributes_set,
                                'label'         => $variation_obj ? $variation_obj->get_attribute_summary() : '',
                                'license_html'  => $license_html ? wp_kses_post( $license_html ) : '',
                                'is_in_stock'   => $variation_obj ? $variation_obj->is_in_stock() : true,
                        ];
                }

                // Pick the first in-stock variation as default, otherwise the first available
                $default_variation       = null;
                $default_variation_attrs = [];

                foreach ( $variations_data as $variation ) {
                        if ( $variation && $variation['id'] ) {
                                $default_variation = $variation;
                                break;
                        }
                }

                if ( ! $default_variation ) {
                        return;
                }

                if ( empty( $default_variation['price_html'] ) ) {
                        $default_variation['price_html'] = wc_price( $default_variation['price'] );
                }

                $default_variation_attrs = isset( $default_variation['attributes'] ) ? $default_variation['attributes'] : [];

                // Get attributes for variations
                $attributes            = $product->get_variation_attributes();
                $main_attribute_name   = '';
                $main_attribute_values = [];

                if ( ! empty( $attributes ) ) {
                        // Get the first attribute as "main" attribute
                        $main_attribute_name   = array_key_first( $attributes );
                        $main_attribute_values = $attributes[ $main_attribute_name ];
                }

		$border_style = isset( $settings['button_border_switch'] ) && 'yes' === $settings['button_border_switch'] ? 'solid' : 'none';
		?>
                <?php
                $default_variation_id   = $default_variation['id'];
                $default_price          = $default_variation['price'];
                $default_price_html     = $default_variation['price_html'];
                $default_price_html_raw = wp_kses_post( $default_price_html );
                ?>
                <div
                        class="bw-price-variation"
                        data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
                        data-variations="<?php echo esc_attr( wp_json_encode( $variations_data ) ); ?>"
                        data-default-variation-id="<?php echo esc_attr( $default_variation_id ); ?>"
                >
                        <!-- Price Display -->
                        <div class="bw-price-variation__price-wrapper">
                                <span
                                        class="bw-price-variation__price"
                                        data-default-price="<?php echo esc_attr( $default_price ); ?>"
                                        data-default-price-html="<?php echo esc_attr( $default_price_html_raw ); ?>"
                                >
                                        <?php echo $default_price_html_raw; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </span>
                        </div>

                        <!-- Variation Buttons -->
                        <?php if ( ! empty( $main_attribute_values ) ) : ?>
                                <?php $attribute_key = 'attribute_' . sanitize_title( $main_attribute_name ); ?>
                                <div class="bw-price-variation__variations">
                                        <?php foreach ( $main_attribute_values as $index => $attribute_value ) : ?>
                                                <?php
                                                // Locate the variation data that matches the current attribute value.
                                                $matched_variation = null;
                                                foreach ( $variations_data as $variation_entry ) {
                                                        if ( isset( $variation_entry['attributes'][ $attribute_key ] ) && strtolower( $variation_entry['attributes'][ $attribute_key ] ) === strtolower( $attribute_value ) ) {
                                                                $matched_variation = $variation_entry;
                                                                break;
                                                        }
                                                }

                                                if ( ! $matched_variation ) {
                                                        continue;
                                                }

                                                $is_active = ( $default_variation_id === $matched_variation['id'] ) ? 'active' : '';
                                                $is_out_of_stock = ! $matched_variation['is_in_stock'];
                                                $button_classes = 'bw-price-variation__variation-button ' . $is_active;
                                                if ( $is_out_of_stock ) {
                                                        $button_classes .= ' out-of-stock';
                                                }
                                                ?>
                                                <button
                                                        class="<?php echo esc_attr( trim( $button_classes ) ); ?>"
                                                        data-variation-id="<?php echo esc_attr( $matched_variation['id'] ); ?>"
                                                        data-variation='<?php echo esc_attr( wp_json_encode( $matched_variation ) ); ?>'
                                                        style="border-style: <?php echo esc_attr( $border_style ); ?>;"
                                                        type="button"
                                                        aria-pressed="<?php echo esc_attr( 'active' === $is_active ? 'true' : 'false' ); ?>"
                                                        <?php echo $is_out_of_stock ? 'disabled aria-disabled="true"' : ''; ?>
                                                >
                                                        <?php echo esc_html( $attribute_value ); ?>
                                                        <?php if ( $is_out_of_stock ) : ?>
                                                                <span class="out-of-stock-label" style="display: none;"><?php esc_html_e( '(Out of stock)', 'bw' ); ?></span>
                                                        <?php endif; ?>
                                                </button>
                                        <?php endforeach; ?>
                                </div>
                        <?php endif; ?>

			<!-- License Box (will be populated by JS) -->
			<div class="bw-price-variation__license-box" style="display: none;"></div>

			<!-- Add To Cart Button -->
			<?php if ( isset( $settings['show_add_to_cart'] ) && 'yes' === $settings['show_add_to_cart'] ) : ?>
				<?php
				$open_cart_popup = isset( $settings['open_cart_popup'] ) && 'yes' === $settings['open_cart_popup'];
                                $button_text     = isset( $settings['add_to_cart_button_text'] ) && '' !== trim( $settings['add_to_cart_button_text'] )
                                        ? $settings['add_to_cart_button_text']
                                        : __( 'Add to Cart', 'bw' );

				$classes = [
					'bw-add-to-cart-button',
					'elementor-button',
					'elementor-button-link',
					'elementor-size-md',
					'single_add_to_cart_button',
					'button',
					'alt',
				];

				// Use the default variation ID for initial state
				$variation_product = wc_get_product( $default_variation_id );
                                if ( ! $variation_product ) {
                                        $variation_product = $product;
                                }

				// Check stock for the specific variation, not just the parent product
				if ( ! $variation_product->is_in_stock() ) {
					$classes[] = 'disabled';
				}

				if ( $open_cart_popup ) {
					$classes[] = 'bw-btn-addtocart';
				}

                                // Build proper add to cart URL for variations
                                // Use the product permalink as base URL for proper WooCommerce handling
                                $add_to_cart_url = $product->get_permalink();
                                $url_params = [
                                        'add-to-cart'   => $product->get_id(),
                                        'variation_id'  => $default_variation_id,
                                        'quantity'      => 1,
                                ];

                                // Add variation attributes to URL
                                if ( ! empty( $default_variation_attrs ) ) {
                                        $url_params = array_merge( $url_params, $default_variation_attrs );
                                }

                                $add_to_cart_url = add_query_arg( $url_params, $add_to_cart_url );

                                $attributes = [
                                        'href'                 => esc_url( $add_to_cart_url ),
                                        'data-quantity'        => 1,
                                        'data-product_id'      => $product->get_id(),
                                        'data-variation_id'    => $default_variation_id,
                                        'data-variation'       => wp_json_encode( $default_variation_attrs ),
                                        'data-product_sku'     => $variation_product->get_sku(),
                                        'data-variation-price' => $default_price,
                                        'data-variation-price-html' => wp_kses_post( $default_price_html ),
                                        'rel'                  => 'nofollow',
                                        'class'                => implode( ' ', array_filter( $classes ) ),
                                        'data-selected-variation-id' => $default_variation_id,
                                ];

				if ( $open_cart_popup ) {
					$attributes['data-open-cart-popup'] = '1';
				}
				?>
				<div class="bw-add-to-cart-wrapper">
					<a <?php
					foreach ( $attributes as $key => $value ) {
						if ( is_string( $value ) || is_numeric( $value ) ) {
							echo esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
						}
					}
					?>><?php echo esc_html( $button_text ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
