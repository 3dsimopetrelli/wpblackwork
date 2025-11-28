<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Add_To_Cart_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-add-to-cart';
    }

    public function get_title() {
        return __( 'BW Add to Cart', 'bw' );
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
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
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'       => __( 'Button Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Add to Cart', 'bw' ),
                'placeholder' => __( 'Enter button text', 'bw' ),
                'label_block' => true,
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_style',
            [
                'label' => __( 'Button', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .bw-add-to-cart-button',
                'fields_options' => [
                    'typography' => [ 'default' => 'yes' ],
                    'font_size'  => [
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

        $this->start_controls_tabs( 'tabs_button_colors' );

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
                    '{{WRAPPER}} .bw-add-to-cart-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
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
            'button_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-button' => 'border-color: {{VALUE}};',
                ],
                'condition' => [ 'button_border_switch' => 'yes' ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-button:hover, {{WRAPPER}} .bw-add-to-cart-button:focus' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-button:hover, {{WRAPPER}} .bw-add-to-cart-button:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-button:hover, {{WRAPPER}} .bw-add-to-cart-button:focus' => 'border-color: {{VALUE}};',
                ],
                'condition' => [ 'button_border_switch' => 'yes' ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_switch',
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
                    '{{WRAPPER}} .bw-add-to-cart-button' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
                ],
                'condition'  => [ 'button_border_switch' => 'yes' ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
                'default'    => [ 'size' => 100, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'button_border_switch' => 'yes' ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
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
            'button_width',
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
            ]
        );

        $this->add_responsive_control(
            'button_alignment',
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
        $product = wc_get_product( $product );

        if ( ! $product || ! $product->is_purchasable() ) {
            return;
        }

        $settings       = $this->get_settings_for_display();
        $open_cart_popup = isset( $settings['open_cart_popup'] ) && 'yes' === $settings['open_cart_popup'];
        $button_text    = isset( $settings['button_text'] ) && '' !== trim( $settings['button_text'] )
            ? $settings['button_text']
            : __( 'Add to Cart', 'bw' );

        $classes = [
            'bw-add-to-cart-button',
            'elementor-button',
            'elementor-button-link',
            'elementor-size-md',
            'product_type_' . $product->get_type(),
        ];

        if ( $product->supports( 'ajax_add_to_cart' ) ) {
            $classes[] = 'add_to_cart_button';
            $classes[] = 'ajax_add_to_cart';
        }

        if ( ! $product->is_in_stock() ) {
            $classes[] = 'disabled';
        }

        if ( $open_cart_popup ) {
            $classes[] = 'bw-btn-addtocart';
        }

        $attributes = [
            'href'               => esc_url( $product->add_to_cart_url() ),
            'data-quantity'      => 1,
            'data-product_id'    => $product->get_id(),
            'data-product_sku'   => $product->get_sku(),
            'rel'                => 'nofollow',
            'class'              => implode( ' ', array_filter( $classes ) ),
        ];

        if ( $open_cart_popup ) {
            $attributes['data-open-cart-popup'] = '1';
        }

        $this->add_render_attribute( 'wrapper', 'class', 'bw-add-to-cart-wrapper' );
        $this->add_render_attribute( 'button', $attributes );

        echo sprintf(
            '<div %1$s><a %2$s>%3$s</a></div>',
            $this->get_render_attribute_string( 'wrapper' ),
            $this->get_render_attribute_string( 'button' ),
            esc_html( $button_text )
        );
    }
}
