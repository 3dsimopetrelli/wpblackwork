<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Add_To_Cart_Variation_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-add-to-cart-variation';
    }

    public function get_title() {
        return __( 'BW Add To Cart Variation', 'bw' );
    }

    public function get_icon() {
        return 'eicon-product-add-to-cart';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        return [ 'bw-add-to-cart-variation-style' ];
    }

    public function get_script_depends() {
        return [ 'wc-add-to-cart-variation', 'bw-add-to-cart-variation-script' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_price_style_controls();
        $this->register_variation_buttons_style_controls();
        $this->register_license_box_style_controls();
        $this->register_add_to_cart_style_controls();
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
            'use_current_product',
            [
                'label'        => __( 'Use Current Product', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'product_id',
            [
                'label'       => __( 'Product ID', 'bw' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => '',
                'description' => __( 'Optional: choose a specific variable product when not using the current product.', 'bw' ),
                'condition'   => [ 'use_current_product!' => 'yes' ],
            ]
        );

        $this->add_control(
            'main_attribute',
            [
                'label'       => __( 'Main Attribute Slug', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'e.g. pa_license', 'bw' ),
                'description' => __( 'Leave empty to use the first product attribute.', 'bw' ),
            ]
        );

        $this->end_controls_section();
    }

    private function register_price_style_controls() {
        $this->start_controls_section(
            'section_price_style',
            [
                'label' => __( 'Price', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'selector' => '{{WRAPPER}} .bw-add-to-cart-variation__price, {{WRAPPER}} .bw-add-to-cart-variation__price .woocommerce-Price-amount, {{WRAPPER}} .bw-add-to-cart-variation__price .woocommerce-Price-currencySymbol',
                'fields_options' => [
                    'typography' => [ 'default' => 'yes' ],
                    'font_size'  => [
                        'default' => [ 'size' => 36, 'unit' => 'px' ],
                    ],
                    'line_height' => [
                        'default' => [ 'size' => 1.2, 'unit' => 'em' ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'     => __( 'Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__price, {{WRAPPER}} .bw-add-to-cart-variation__price .woocommerce-Price-amount, {{WRAPPER}} .bw-add-to-cart-variation__price .woocommerce-Price-currencySymbol' => 'color: {{VALUE}};',
                ],
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
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__price' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'price_margin',
            [
                'label'      => __( 'Margin', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', 'rem' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_variation_buttons_style_controls() {
        $this->start_controls_section(
            'section_variations_style',
            [
                'label' => __( 'Variation Buttons', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'variation_buttons_typography',
                'selector' => '{{WRAPPER}} .bw-add-to-cart-variation__variation-button',
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
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variations' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'variation_buttons_column_gap',
            [
                'label'      => __( 'Horizontal Spacing', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variations' => 'column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'variation_buttons_row_gap',
            [
                'label'      => __( 'Vertical Spacing', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variations' => 'row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'variation_buttons_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 200 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
                'default'    => [ 'size' => 999, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_variation_buttons_style' );

        $this->start_controls_tab(
            'tab_variation_buttons_normal',
            [ 'label' => __( 'Normal', 'bw' ) ]
        );

        $this->add_control(
            'variation_buttons_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_variation_buttons_hover',
            [ 'label' => __( 'Hover', 'bw' ) ]
        );

        $this->add_control(
            'variation_buttons_hover_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_hover_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_hover_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_variation_buttons_active',
            [ 'label' => __( 'Active/Selected', 'bw' ) ]
        );

        $this->add_control(
            'variation_buttons_active_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_active_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'variation_buttons_active_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__variation-button.is-active' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    private function register_license_box_style_controls() {
        $this->start_controls_section(
            'section_license_box_style',
            [
                'label' => __( 'License Box', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'license_box_border',
                'selector' => '{{WRAPPER}} .bw-add-to-cart-variation__license-box',
            ]
        );

        $this->add_control(
            'license_box_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__license-box' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'license_box_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__license-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'license_box_margin',
            [
                'label'      => __( 'Margin', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__license-box' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'license_box_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__license-box' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'license_box_typography',
                'selector' => '{{WRAPPER}} .bw-add-to-cart-variation__license-box',
            ]
        );

        $this->add_control(
            'license_box_animation_duration',
            [
                'label'       => __( 'Fade Duration (ms)', 'bw' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'ms' ],
                'range'       => [ 'ms' => [ 'min' => 0, 'max' => 2000 ] ],
                'default'     => [ 'size' => 180, 'unit' => 'ms' ],
                'description' => __( 'Controls the fade animation when price or license content changes.', 'bw' ),
            ]
        );

        $this->end_controls_section();
    }

    private function register_add_to_cart_style_controls() {
        $this->start_controls_section(
            'section_add_to_cart_style',
            [
                'label' => __( 'Add to Cart Button', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'open_cart_popup',
            [
                'label'        => __( 'Open Cart Pop-up', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw' ),
                'label_off'    => __( 'Off', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
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
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'add_to_cart_typography',
                'selector'       => '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart',
                'fields_options' => [
                    'typography'  => [ 'default' => 'yes' ],
                    'font_size'   => [ 'default' => [ 'size' => 18, 'unit' => 'px' ] ],
                    'line_height' => [ 'default' => [ 'size' => 1.2, 'unit' => 'em' ] ],
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_add_to_cart_style' );

        $this->start_controls_tab(
            'tab_add_to_cart_normal',
            [ 'label' => __( 'Normal', 'bw' ) ]
        );

        $this->add_control(
            'add_to_cart_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'add_to_cart_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'add_to_cart_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_add_to_cart_hover',
            [ 'label' => __( 'Hover', 'bw' ) ]
        );

        $this->add_control(
            'add_to_cart_text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'add_to_cart_background_color_hover',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'add_to_cart_border_color_hover',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'add_to_cart_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'add_to_cart_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 200 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
                'default'    => [ 'size' => 100, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'add_to_cart_width_switch',
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
            'add_to_cart_width',
            [
                'label'      => __( 'Button Width (%)', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ '%' ],
                'range'      => [ '%' => [ 'min' => 1, 'max' => 100 ] ],
                'default'    => [ 'size' => 100, 'unit' => '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'add_to_cart_width_switch' => 'yes' ],
            ]
        );

        $this->add_responsive_control(
            'add_to_cart_alignment',
            [
                'label'   => __( 'Alignment', 'bw' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left'   => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
                    'right'  => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'add_to_cart_margin',
            [
                'label'      => __( 'Margin', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-add-to-cart-variation__add-to-cart-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function find_default_variation( $variations, $default_attributes ) {
        $normalized_defaults = [];
        foreach ( (array) $default_attributes as $key => $value ) {
            $normalized_defaults[ sanitize_title( $key ) ] = strtolower( (string) $value );
        }

        foreach ( $variations as $variation ) {
            if ( empty( $variation['variation_id'] ) ) {
                continue;
            }

            $attributes = isset( $variation['attributes'] ) ? (array) $variation['attributes'] : [];
            $matches    = true;

            foreach ( $normalized_defaults as $name => $value ) {
                $attribute_key = 'attribute_' . $name;
                if ( ! isset( $attributes[ $attribute_key ] ) || strtolower( (string) $attributes[ $attribute_key ] ) !== $value ) {
                    $matches = false;
                    break;
                }
            }

            if ( $matches && ( ! isset( $variation['is_in_stock'] ) || $variation['is_in_stock'] ) ) {
                return $variation;
            }
        }

        foreach ( $variations as $variation ) {
            if ( isset( $variation['is_in_stock'] ) && ! $variation['is_in_stock'] ) {
                continue;
            }
            return $variation;
        }

        return isset( $variations[0] ) ? $variations[0] : null;
    }

    private function get_product_instance( $settings ) {
        global $product;

        if ( isset( $settings['use_current_product'] ) && 'yes' === $settings['use_current_product'] ) {
            $candidate = $product ? wc_get_product( $product ) : null;
            if ( ! $candidate && get_the_ID() ) {
                $candidate = wc_get_product( get_the_ID() );
            }
            if ( $candidate ) {
                return $candidate;
            }
        }

        $product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;
        if ( $product_id ) {
            $candidate = wc_get_product( $product_id );
            if ( $candidate ) {
                return $candidate;
            }
        }

        // Fallback for Elementor preview: pick the first variable product available.
        $products = wc_get_products(
            [
                'status' => 'publish',
                'type'   => 'variable',
                'limit'  => 1,
            ]
        );

        return ! empty( $products ) ? $products[0] : null;
    }

    private function prepare_variations_data( $available_variations ) {
        $prepared = [];

        foreach ( $available_variations as $variation ) {
$variation_id  = isset( $variation['variation_id'] ) ? absint( $variation['variation_id'] ) : 0;
$variation_obj = $variation_id ? wc_get_product( $variation_id ) : null;
$license_html  = ( $variation_id && function_exists( 'bw_get_variation_license_table_html' ) ) ? bw_get_variation_license_table_html( $variation_id ) : '';

$variation['bw_license_html'] = $license_html ? wp_kses_post( $license_html ) : '';
            if ( empty( $variation['price_html'] ) && $variation_obj ) {
                $variation['price_html'] = wc_price( $variation_obj->get_price() );
            }

            $prepared[] = $variation;
        }

        return $prepared;
    }

    private function render_variation_buttons( $attribute_values, $variations, $main_attribute_key, $default_variation_id ) {
        if ( empty( $attribute_values ) ) {
            return;
        }

        echo '<div class="bw-add-to-cart-variation__variations">';

        foreach ( $attribute_values as $value ) {
            $matched_variation = null;
            foreach ( $variations as $variation ) {
                $attributes = isset( $variation['attributes'] ) ? $variation['attributes'] : [];
                if ( isset( $attributes[ $main_attribute_key ] ) && strtolower( $attributes[ $main_attribute_key ] ) === strtolower( $value ) ) {
                    $matched_variation = $variation;
                    break;
                }
            }

            if ( ! $matched_variation ) {
                continue;
            }

            $is_active   = ( $matched_variation['variation_id'] === $default_variation_id );
            $is_disabled = isset( $matched_variation['is_in_stock'] ) && ! $matched_variation['is_in_stock'];
            $classes     = [ 'bw-add-to-cart-variation__variation-button' ];

            if ( $is_active ) {
                $classes[] = 'is-active';
            }

            if ( $is_disabled ) {
                $classes[] = 'is-disabled';
            }

            printf(
                '<button type="button" class="%1$s" data-value="%2$s" aria-pressed="%3$s" %4$s>%5$s</button>',
                esc_attr( implode( ' ', $classes ) ),
                esc_attr( $value ),
                esc_attr( $is_active ? 'true' : 'false' ),
                $is_disabled ? 'disabled aria-disabled="true"' : '',
                esc_html( $value )
            );
        }

        echo '</div>';
    }

    protected function render() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $product  = $this->get_product_instance( $settings );

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div class="bw-add-to-cart-variation">' . esc_html__( 'Select a variable product to preview the BW Add To Cart Variation widget.', 'bw' ) . '</div>';
            }
            return;
        }

        $available_variations = $product->get_available_variations();
        if ( empty( $available_variations ) ) {
            return;
        }

        $attributes      = $product->get_variation_attributes();
        $main_attribute  = sanitize_title( $settings['main_attribute'] ?? '' );
        $main_attribute  = $main_attribute ? $main_attribute : array_key_first( $attributes );
        $attribute_key   = 'attribute_' . $main_attribute;
        $attribute_values = isset( $attributes[ $main_attribute ] ) ? $attributes[ $main_attribute ] : [];

        if ( ! $main_attribute || empty( $attribute_values ) ) {
            return;
        }

        $prepared_variations = $this->prepare_variations_data( $available_variations );
        $default_variation   = $this->find_default_variation( $prepared_variations, $product->get_default_attributes() );

        if ( ! $default_variation ) {
            return;
        }

        $default_price_html = wp_kses_post( $default_variation['price_html'] ?? $product->get_price_html() );
        $default_license    = $default_variation['bw_license_html'] ?? '';
        $animation_duration = isset( $settings['license_box_animation_duration']['size'] ) ? absint( $settings['license_box_animation_duration']['size'] ) : 180;
        $open_cart_popup    = isset( $settings['open_cart_popup'] ) && 'yes' === $settings['open_cart_popup'];
        $button_text        = ! empty( $settings['add_to_cart_button_text'] ) ? $settings['add_to_cart_button_text'] : __( 'Add to Cart', 'bw' );

        $form_attributes = [
            'class'                   => 'variations_form cart bw-add-to-cart-variation__form',
            'action'                  => esc_url( $product->get_permalink() ),
            'method'                  => 'post',
            'data-product_id'         => $product->get_id(),
            'data-product_variations' => wc_esc_json( $prepared_variations ),
        ];

        $add_to_cart_classes = [
            'bw-add-to-cart-variation__add-to-cart',
            'elementor-button',
            'elementor-button-link',
            'add_to_cart_button',
            'single_add_to_cart_button',
        ];

        if ( $open_cart_popup ) {
            $add_to_cart_classes[] = 'bw-btn-addtocart';
        }

        $add_to_cart_href = add_query_arg(
            array_merge(
                [
                    'add-to-cart'  => $product->get_id(),
                    'variation_id' => $default_variation['variation_id'],
                    'quantity'     => 1,
                ],
                $default_variation['attributes'] ?? []
            ),
            $product->get_permalink()
        );

        $is_default_in_stock = ! isset( $default_variation['is_in_stock'] ) || $default_variation['is_in_stock'];

        ?>
        <div
            class="bw-add-to-cart-variation"
            data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
            data-product-url="<?php echo esc_url( $product->get_permalink() ); ?>"
            data-main-attribute="<?php echo esc_attr( $main_attribute ); ?>"
            data-animation-duration="<?php echo esc_attr( $animation_duration ); ?>"
        >
            <span class="bw-add-to-cart-variation__price"><?php echo $default_price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>

            <?php $this->render_variation_buttons( $attribute_values, $prepared_variations, $attribute_key, $default_variation['variation_id'] ); ?>

            <div class="bw-add-to-cart-variation__license-box <?php echo empty( $default_license ) ? 'is-hidden' : ''; ?>">
                <?php echo wp_kses_post( $default_license ); ?>
            </div>

            <form <?php
            foreach ( $form_attributes as $key => $value ) {
                $escaped_value = 'data-product_variations' === $key ? $value : esc_attr( $value );
                echo esc_attr( $key ) . '="' . $escaped_value . '" ';
            }
            ?>>
                <?php foreach ( $attributes as $attribute_name => $options ) :
                    $selected = $product->get_variation_default_attribute( $attribute_name );
                    wc_dropdown_variation_attribute_options(
                        [
                            'options'   => $options,
                            'attribute' => $attribute_name,
                            'product'   => $product,
                            'name'      => 'attribute_' . sanitize_title( $attribute_name ),
                            'selected'  => $selected,
                            'class'     => 'hidden',
                        ]
                    );
                endforeach; ?>
                <input type="hidden" name="variation_id" value="<?php echo esc_attr( $default_variation['variation_id'] ); ?>" />
            </form>

            <div class="bw-add-to-cart-variation__add-to-cart-wrapper">
                <a
                    class="<?php echo esc_attr( implode( ' ', $add_to_cart_classes ) . ( $is_default_in_stock ? '' : ' disabled' ) ); ?>"
                    href="<?php echo esc_url( $add_to_cart_href ); ?>"
                    data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                    data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                    data-variation_id="<?php echo esc_attr( $default_variation['variation_id'] ); ?>"
                    data-quantity="1"
                    <?php echo $open_cart_popup ? 'data-open-cart-popup="1"' : ''; ?>
                >
                    <?php echo esc_html( $button_text ); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
