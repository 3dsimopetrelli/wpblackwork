<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BW_Price_Variation_Widget extends Widget_Base {

        private function render_editor_notice( $message ) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html( $message );
                echo '</div>';
        }

	public function get_name() {
		return 'bw-price-variation';
	}

	public function get_title() {
		return __( 'BW-SP Price Variation', 'bw' );
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	public function get_categories() {
		return [ 'blackwork' ];
	}

	public function get_style_depends() {
		return [ 'bw-embla-core-css', 'bw-price-variation-style' ];
	}

	public function get_script_depends() {
		return [ 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-price-variation-script' ];
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

        private function register_content_controls() {
                // Product Selection Section
                $this->start_controls_section(
			'section_product_selection',
			[
				'label' => __( 'Product', 'bw' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'product_id',
			[
				'label'       => __( 'Product ID', 'bw' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Leave empty to use current product', 'bw' ),
				'description' => __( 'Enter the ID of a variable product. Leave empty to automatically use the current product (only works on single product pages).', 'bw' ),
				'label_block' => true,
			]
		);

                $this->end_controls_section();

                $this->start_controls_section(
                        'section_rates_content',
                        [
                                'label' => __( 'Rates', 'bw' ),
                                'tab'   => Controls_Manager::TAB_CONTENT,
                        ]
                );

                $this->add_control(
                        'show_product_reviews_summary',
                        [
                                'label'        => __( 'Show Product Reviews', 'bw' ),
                                'type'         => Controls_Manager::SWITCHER,
                                'label_on'     => __( 'On', 'bw' ),
                                'label_off'    => __( 'Off', 'bw' ),
                                'return_value' => 'yes',
                                'default'      => '',
                                'description'  => __( 'Show the review stars and rating summary for the current product under the price. It appears only when this product has approved reviews in the BW Reviews module.', 'bw' ),
                        ]
                );

                $this->add_control(
                        'show_product_reviews_count',
                        [
                                'label'        => __( 'Show Reviews Count', 'bw' ),
                                'type'         => Controls_Manager::SWITCHER,
                                'label_on'     => __( 'On', 'bw' ),
                                'label_off'    => __( 'Off', 'bw' ),
                                'return_value' => 'yes',
                                'default'      => 'yes',
                                'condition'    => [
                                        'show_product_reviews_summary' => 'yes',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'product_reviews_alignment',
                        [
                                'label'     => __( 'Alignment', 'bw' ),
                                'type'      => Controls_Manager::CHOOSE,
                                'options'   => [
                                        'flex-start' => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
                                        'center'     => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
                                        'flex-end'   => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
                                ],
                                'default'   => 'center',
                                'selectors' => [
                                        '{{WRAPPER}} .bw-price-variation__reviews-summary' => 'justify-content: {{VALUE}};',
                                ],
                                'condition' => [
                                        'show_product_reviews_summary' => 'yes',
                                ],
                        ]
                );

                $this->end_controls_section();

                // Add To Cart Section
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

		$this->add_control(
			'show_more_payment_options',
			[
				'label'        => __( 'Show More Payment Options', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Show a centered text link under Add to Cart that sends the customer straight to checkout with the currently selected license. If no license has been changed yet, it uses the default selected variation.', 'bw' ),
				'condition'    => [
					'show_add_to_cart' => 'yes',
				],
			]
		);

                $this->end_controls_section();

                $this->start_controls_section(
                        'section_trust_review_content',
                        [
                                'label' => __( 'Review Trust', 'bw' ),
                                'tab'   => Controls_Manager::TAB_CONTENT,
                        ]
                );

                $this->add_control(
                        'trust_review_content_notice',
                        [
                                'type'            => Controls_Manager::RAW_HTML,
                                'raw'             => wp_kses_post( __( 'The review slider and fixed review box are managed globally in <strong>Blackwork Site -> Reviews Settings -> Trust Content</strong>. This widget section only documents that dependency and keeps the pricing column contract explicit.', 'bw' ) ),
                                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                        ]
                );

                $this->end_controls_section();

                $this->start_controls_section(
                        'section_digital_product_info_content',
                        [
                                'label' => __( 'Digital Product Info', 'bw' ),
                                'tab'   => Controls_Manager::TAB_CONTENT,
                        ]
                );

                $this->add_control(
                        'show_digital_product_info',
                        [
                                'label'        => __( 'Enable Information Box', 'bw' ),
                                'type'         => Controls_Manager::SWITCHER,
                                'label_on'     => __( 'On', 'bw' ),
                                'label_off'    => __( 'Off', 'bw' ),
                                'return_value' => 'yes',
                                'default'      => '',
                        ]
                );

                $info_repeater = new Repeater();

                $info_repeater->add_control(
                        'info_icon',
                        [
                                'label' => __( 'Icon', 'bw' ),
                                'type'  => Controls_Manager::ICONS,
                        ]
                );

                $info_repeater->add_control(
                        'info_title',
                        [
                                'label'       => __( 'Title', 'bw' ),
                                'type'        => Controls_Manager::TEXT,
                                'default'     => __( 'Instant Download', 'bw' ),
                                'label_block' => true,
                        ]
                );

                $info_repeater->add_control(
                        'info_description',
                        [
                                'label'       => __( 'Description', 'bw' ),
                                'type'        => Controls_Manager::TEXTAREA,
                                'default'     => '',
                                'rows'        => 3,
                                'placeholder' => __( 'Optional short description', 'bw' ),
                        ]
                );

                $this->add_control(
                        'digital_product_info_items',
                        [
                                'label'       => __( 'Information Items', 'bw' ),
                                'type'        => Controls_Manager::REPEATER,
                                'fields'      => $info_repeater->get_controls(),
                                'default'     => [
                                        [
                                                'info_icon'        => [ 'value' => 'bw-instant-download-file-down', 'library' => 'bw-custom' ],
                                                'info_title'       => __( 'Instant Download', 'bw' ),
                                                'info_description' => __( 'Get immediate access to your files after checkout.', 'bw' ),
                                        ],
                                        [
                                                'info_icon'        => [ 'value' => 'bw-premium-quality-gem', 'library' => 'bw-custom' ],
                                                'info_title'       => __( 'Premium Quality', 'bw' ),
                                                'info_description' => __( 'Professionally crafted assets built for production use.', 'bw' ),
                                        ],
                                        [
                                                'info_icon'        => [ 'value' => 'bw-lifetime-support-messages-square', 'library' => 'bw-custom' ],
                                                'info_title'       => __( 'Lifetime Support', 'bw' ),
                                                'info_description' => __( 'Keep a reliable reference point for using the product over time.', 'bw' ),
                                        ],
                                ],
                                'title_field' => '{{{ info_title }}}',
                                'condition'   => [
                                        'show_digital_product_info' => 'yes',
                                ],
                        ]
                );

                $this->end_controls_section();

                $this->start_controls_section(
                        'section_faq_button_content',
                        [
                                'label' => __( 'FAQ Button', 'bw' ),
                                'tab'   => Controls_Manager::TAB_CONTENT,
                        ]
                );

                $this->add_control(
                        'show_faq_button',
                        [
                                'label'        => __( 'Enable FAQ Button', 'bw' ),
                                'type'         => Controls_Manager::SWITCHER,
                                'label_on'     => __( 'On', 'bw' ),
                                'label_off'    => __( 'Off', 'bw' ),
                                'return_value' => 'yes',
                                'default'      => '',
                        ]
                );

                $this->add_control(
                        'faq_button_label',
                        [
                                'label'       => __( 'FAQ Label', 'bw' ),
                                'type'        => Controls_Manager::TEXT,
                                'default'     => __( 'Frequently Asked Questions', 'bw' ),
                                'label_block' => true,
                                'condition'   => [
                                        'show_faq_button' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'faq_button_link',
                        [
                                'label'         => __( 'FAQ Link', 'bw' ),
                                'type'          => Controls_Manager::URL,
                                'placeholder'   => __( 'https://your-site.com/faq', 'bw' ),
                                'show_external' => true,
                                'default'       => [
                                        'url'         => '',
                                        'is_external' => false,
                                        'nofollow'    => false,
                                ],
                                'condition'     => [
                                        'show_faq_button' => 'yes',
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
					'flex-start' => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
					'flex-end'   => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'flex-start',
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

		$this->start_controls_section(
			'section_rates_style',
			[
				'label' => __( 'Rates', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'product_reviews_margin',
			[
				'label'      => __( 'Margin', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__reviews-summary' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'show_product_reviews_summary' => 'yes',
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
					'{{WRAPPER}} .bw-price-variation__variation-button' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
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

		$this->add_control(
			'license_box_accordion_divider',
			[
				'type'  => Controls_Manager::DIVIDER,
				'style' => 'thick',
			]
		);

		$this->add_control(
			'license_box_accordion_mobile',
			[
				'label'        => __( 'Enable Accordion (Mobile/Tablet)', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'license_box_accordion_desktop',
			[
				'label'        => __( 'Enable Accordion (Desktop)', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'license_box_accordion_label',
			[
				'label'      => __( 'Button Label', 'bw' ),
				'type'       => Controls_Manager::TEXT,
				'default'    => __( 'About License', 'bw' ),
				'conditions' => [
					'relation' => 'or',
					'terms'    => [
						[
							'name'     => 'license_box_accordion_mobile',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'name'     => 'license_box_accordion_desktop',
							'operator' => '===',
							'value'    => 'yes',
						],
					],
				],
			]
		);

                $this->end_controls_section();

                // License Table Style
                $this->start_controls_section(
                        'section_license_table_style',
                        [
                                'label' => __( 'License Table', 'bw' ),
                                'tab'   => Controls_Manager::TAB_STYLE,
                        ]
                );

                $this->add_control(
                        'license_table_borderless',
                        [
                                'label'        => __( 'Remove Table Border', 'bw' ),
                                'type'         => Controls_Manager::SWITCHER,
                                'label_on'     => __( 'Yes', 'bw' ),
                                'label_off'    => __( 'No', 'bw' ),
                                'return_value' => 'yes',
                                'default'      => 'yes',
                                'selectors'    => [
                                        '{{WRAPPER}} .bw-license-table, {{WRAPPER}} .bw-license-table td, {{WRAPPER}} .bw-license-table th' => 'border: none !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'license_table_background_color',
                        [
                                'label'     => __( 'Table Background Color', 'bw' ),
                                'type'      => Controls_Manager::COLOR,
                                'default'   => '#ffffff',
                                'selectors' => [
                                        '{{WRAPPER}} .bw-license-table, {{WRAPPER}} .bw-license-table td, {{WRAPPER}} .bw-license-table th' => 'background-color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_group_control(
                        Group_Control_Typography::get_type(),
                        [
                                'name'           => 'license_table_col1_typography',
                                'label'          => __( 'First Column Typography', 'bw' ),
                                'selector'       => '{{WRAPPER}} .bw-license-table__cell--label',
                                'fields_options' => [
                                        'font_size' => [
                                                'default' => [
                                                        'size' => 13,
                                                        'unit' => 'px',
                                                ],
                                        ],
                                ],
                        ]
                );

                $this->add_control(
                        'license_table_col1_color',
                        [
                                'label'     => __( 'First Column Text Color', 'bw' ),
                                'type'      => Controls_Manager::COLOR,
                                'default'   => '#000000',
                                'selectors' => [
                                        '{{WRAPPER}} .bw-license-table__cell--label' => 'color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_group_control(
                        Group_Control_Typography::get_type(),
                        [
                                'name'           => 'license_table_col2_typography',
                                'label'          => __( 'Second Column Typography', 'bw' ),
                                'selector'       => '{{WRAPPER}} .bw-license-table__cell--value',
                                'fields_options' => [
                                        'font_size' => [
                                                'default' => [
                                                        'size' => 13,
                                                        'unit' => 'px',
                                                ],
                                        ],
                                ],
                        ]
                );

                $this->add_control(
                        'license_table_col2_color',
                        [
                                'label'     => __( 'Second Column Text Color', 'bw' ),
                                'type'      => Controls_Manager::COLOR,
                                'default'   => '#000000',
                                'selectors' => [
                                        '{{WRAPPER}} .bw-license-table__cell--value' => 'color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'license_table_col1_width',
                        [
                                'label'      => __( 'First Column Width (%)', 'bw' ),
                                'type'       => Controls_Manager::SLIDER,
                                'size_units' => [ '%' ],
                                'range'      => [
                                        '%' => [ 'min' => 0, 'max' => 100 ],
                                ],
                                'default'    => [ 'size' => 50, 'unit' => '%' ],
                                'selectors'  => [
                                        '{{WRAPPER}} .bw-license-table__cell--label' => 'width: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'license_table_col2_width',
                        [
                                'label'      => __( 'Second Column Width (%)', 'bw' ),
                                'type'       => Controls_Manager::SLIDER,
                                'size_units' => [ '%' ],
                                'range'      => [
                                        '%' => [ 'min' => 0, 'max' => 100 ],
                                ],
                                'default'    => [ 'size' => 50, 'unit' => '%' ],
                                'selectors'  => [
                                        '{{WRAPPER}} .bw-license-table__cell--value' => 'width: {{SIZE}}{{UNIT}};',
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
					'flex-start' => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
					'flex-end'   => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .bw-add-to-cart-wrapper' => 'justify-content: {{VALUE}};',
                                ],
                        ]
		);

	                $this->end_controls_section();

		// ----------------------------------------------------------------
		// Payment Options — style section
		// ----------------------------------------------------------------
		$this->start_controls_section(
			'section_payment_options_style',
			[
				'label' => __( 'Payment Options', 'bw' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'payment_options_style_enabled',
			[
				'label'        => __( 'Enable', 'bw' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'bw' ),
				'label_off'    => __( 'Off', 'bw' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'payment_options_typography',
				'label'     => __( 'More Payment Options Typography', 'bw' ),
				'selector'  => '{{WRAPPER}} .bw-price-variation__payment-options',
				'condition' => [
					'payment_options_style_enabled' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'payment_options_margin',
			[
				'label'      => __( 'More Payment Options Margin', 'bw' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bw-price-variation__payment-options-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'payment_options_style_enabled' => 'yes',
				],
			]
		);

		$this->end_controls_section();
        }

        private function get_product_id_from_settings( $settings ) {
                if ( function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
                        $settings['__widget_class'] = __CLASS__;
                        return bw_tbl_resolve_product_context_id( $settings );
                }

                if ( ! empty( $settings['product_id'] ) ) {
                        return [
                                'id' => absint( $settings['product_id'] ),
                                'source' => 'manual_setting',
                        ];
                }

                return [
                        'id' => 0,
                        'source' => 'missing',
                ];
        }

        private function get_variations_data( $available_variations ) {
                $variations_data = [];

                foreach ( $available_variations as $variation ) {
                        $variation_id  = $variation['variation_id'];
                        $variation_obj = wc_get_product( $variation_id );
                        $price_value   = isset( $variation['display_price'] ) ? $variation['display_price'] : wc_get_price_to_display( $variation_obj );
                        $price_html    = ! empty( $variation['price_html'] ) ? $variation['price_html'] : wc_price( $price_value );
$license_html  = function_exists( 'bw_get_variation_license_table_html' ) ? bw_get_variation_license_table_html( $variation_id ) : '';

                        $variations_data[] = [
                                'id'           => $variation_id,
                                'price'        => $price_value,
                                'price_html'   => $price_html,
                                'sku'          => $variation_obj ? $variation_obj->get_sku() : '',
                                'attributes'   => isset( $variation['attributes'] ) ? $variation['attributes'] : [],
                                'label'        => $variation_obj ? $variation_obj->get_attribute_summary() : '',
'license_html' => $license_html ? wp_kses_post( $license_html ) : '',
                                'is_in_stock'  => $variation_obj ? $variation_obj->is_in_stock() : true,
                        ];
                }

                return $variations_data;
        }

        private function get_default_variation( $variations_data ) {
                if ( empty( $variations_data ) ) {
                        return null;
                }

                foreach ( $variations_data as $variation ) {
                        if ( ! empty( $variation['is_in_stock'] ) ) {
                                return $variation;
                        }
                }

                return $variations_data[0];
        }

        private function get_main_attribute( $attributes ) {
                if ( empty( $attributes ) ) {
                        return [ '', [] ];
                }

                $main_attribute_name = array_key_first( $attributes );

                return [ $main_attribute_name, $attributes[ $main_attribute_name ] ];
        }

        private function get_product_reviews_summary( $product_id ) {
                if ( $product_id <= 0 || ! class_exists( 'BW_Reviews_Repository' ) || ! class_exists( 'BW_Reviews_Settings' ) ) {
                        return null;
                }

                if ( ! BW_Reviews_Settings::is_enabled() ) {
                        return null;
                }

                $repository = new BW_Reviews_Repository();
                $summary    = $repository->get_product_summary( $product_id );

                if ( empty( $summary['approved_count'] ) ) {
                        return null;
                }

                return $summary;
        }

        private function render_product_reviews_summary( $summary, $show_count ) {
                $average = isset( $summary['average_rating'] ) ? max( 0, min( 5, (float) $summary['average_rating'] ) ) : 0;
                $count   = isset( $summary['approved_count'] ) ? absint( $summary['approved_count'] ) : 0;

                if ( $count <= 0 ) {
                        return '';
                }

                $fill_percent = min( 100, max( 0, ( $average / 5 ) * 100 ) );
                $average_label = number_format_i18n( $average, 1 );
                $count_label   = sprintf(
                        _n( '%d Review', '%d Reviews', $count, 'bw' ),
                        $count
                );

                ob_start();
                ?>
                <div class="bw-price-variation__reviews-summary" aria-label="<?php echo esc_attr( sprintf( __( '%1$s rating from %2$d reviews', 'bw' ), $average_label, $count ) ); ?>">
                        <div class="bw-price-variation__reviews-inline">
                                <span class="bw-price-variation__reviews-stars" aria-hidden="true">
                                        <span class="bw-price-variation__reviews-stars-base">★★★★★</span>
                                        <span class="bw-price-variation__reviews-stars-fill" style="width: <?php echo esc_attr( (string) $fill_percent ); ?>%;">★★★★★</span>
                                </span>
                                <span class="bw-price-variation__reviews-rating"><?php echo esc_html( $average_label ); ?></span>
                                <?php if ( $show_count ) : ?>
                                        <span class="bw-price-variation__reviews-separator" aria-hidden="true">-</span>
                                        <span class="bw-price-variation__reviews-count"><?php echo esc_html( $count_label ); ?></span>
                                <?php endif; ?>
                        </div>
                </div>
                <?php

                return (string) ob_get_clean();
        }

        /**
         * Get global trust-content settings from Reviews Settings.
         *
         * @return array<string,mixed>
         */
        private function get_reviews_trust_settings() {
                if ( ! class_exists( 'BW_Reviews_Settings' ) || ! method_exists( 'BW_Reviews_Settings', 'get_trust_settings' ) ) {
                        return [];
                }

                $settings = BW_Reviews_Settings::get_trust_settings();

                return is_array( $settings ) ? $settings : [];
        }

        /**
         * Normalize global review-slider items.
         *
         * @param array<string,mixed> $trust_settings Trust settings.
         *
         * @return array<int,array<string,string>>
         */
        private function get_review_slider_items( $trust_settings ) {
                $items = [];
                $rows  = isset( $trust_settings['slider_reviews'] ) && is_array( $trust_settings['slider_reviews'] )
                        ? $trust_settings['slider_reviews']
                        : [];

                foreach ( $rows as $row ) {
                        if ( ! is_array( $row ) ) {
                                continue;
                        }

                        $text   = isset( $row['text'] ) ? trim( sanitize_textarea_field( (string) $row['text'] ) ) : '';
                        $author = isset( $row['author'] ) ? trim( sanitize_text_field( (string) $row['author'] ) ) : '';

                        if ( '' === $text || '' === $author ) {
                                continue;
                        }

                        $items[] = [
                                'text'   => $text,
                                'author' => $author,
                        ];

                        if ( count( $items ) >= 6 ) {
                                break;
                        }
                }

                return $items;
        }

        /**
         * Resolve fixed review-box content.
         *
         * @param array<string,mixed> $trust_settings Trust settings.
         *
         * @return string
         */
        private function get_review_box_content( $trust_settings ) {
                $content = isset( $trust_settings['review_box_content'] ) ? (string) $trust_settings['review_box_content'] : '';

                return '' !== trim( wp_strip_all_tags( $content ) ) ? wp_kses_post( $content ) : '';
        }

        /**
         * Normalize widget-level information-box items.
         *
         * @param array<string,mixed> $settings Widget settings.
         *
         * @return array<int,array<string,mixed>>
         */
        private function get_digital_product_info_items( $settings ) {
                $items = [];
                $rows  = isset( $settings['digital_product_info_items'] ) && is_array( $settings['digital_product_info_items'] )
                        ? $settings['digital_product_info_items']
                        : [];

                foreach ( $rows as $row ) {
                        if ( ! is_array( $row ) ) {
                                continue;
                        }

                        $title       = isset( $row['info_title'] ) ? trim( sanitize_text_field( (string) $row['info_title'] ) ) : '';
                        $description = isset( $row['info_description'] ) ? trim( sanitize_textarea_field( (string) $row['info_description'] ) ) : '';
                        $icon        = isset( $row['info_icon'] ) && is_array( $row['info_icon'] ) ? $row['info_icon'] : [];

                        if ( '' === $title && '' === $description && empty( $icon['value'] ) ) {
                                continue;
                        }

                        $items[] = [
                                'title'       => $title,
                                'description' => $description,
                                'icon'        => $icon,
                        ];
                }

                return $items;
        }

        /**
         * Render widget info-card icon markup.
         *
         * @param array<string,mixed> $icon Icon control payload.
         *
         * @return string
         */
        private function render_digital_product_info_icon( $item ) {
                $icon       = isset( $item['icon'] ) && is_array( $item['icon'] ) ? $item['icon'] : [];
                $icon_value = isset( $icon['value'] ) ? (string) $icon['value'] : '';
                $title      = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';

                if ( 'bw-instant-download-file-down' === $icon_value || 'fas fa-download' === $icon_value ) {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>';
                }

                if ( 'bw-premium-quality-gem' === $icon_value || 'fas fa-gem' === $icon_value ) {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.5 3 8 9l4 13 4-13-2.5-6"/><path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z"/><path d="M2 9h20"/></svg>';
                }

                if ( 'bw-lifetime-support-messages-square' === $icon_value || 'fas fa-life-ring' === $icon_value ) {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"/></svg>';
                }

                if ( empty( $icon_value ) ) {
                        if ( false !== stripos( $title, 'instant download' ) ) {
                                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>';
                        }

                        if ( false !== stripos( $title, 'premium quality' ) ) {
                                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.5 3 8 9l4 13 4-13-2.5-6"/><path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z"/><path d="M2 9h20"/></svg>';
                        }

                        if ( false !== stripos( $title, 'lifetime support' ) ) {
                                return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"/></svg>';
                        }

                        return '';
                }

                ob_start();
                Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );

                return (string) ob_get_clean();
        }

        protected function render() {
                if ( ! class_exists( 'WooCommerce' ) ) {
                        return;
                }

                $settings = $this->get_settings_for_display();
                $is_editor_mode = \Elementor\Plugin::$instance->editor->is_edit_mode();

                // Get product ID from settings or current context
                $product_resolution = $this->get_product_id_from_settings( $settings );
                $product_id = isset( $product_resolution['id'] ) ? absint( $product_resolution['id'] ) : 0;

                // Get the product
                global $product;
                $product = wc_get_product( $product_id );

                // Validate product exists and is variable type
                if ( ! $product ) {
                        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                                $this->render_editor_notice( __( 'BW Price Variation: Product not found. Please enter a valid Product ID or use this widget on a single product page.', 'bw' ) );
                        }
                        return;
                }

                if ( ! $product->is_type( 'variable' ) ) {
                        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                                $this->render_editor_notice( esc_html__( 'BW Price Variation: This widget only works with Variable products. Current product type: ', 'bw' ) . esc_html( $product->get_type() ) );
                        }
                        return;
                }

                $available_variations = $product->get_available_variations();

                if ( empty( $available_variations ) ) {
                        return;
                }

                // Build a normalized list of variations with price details and attributes
                $variations_data = $this->get_variations_data( $available_variations );

                // Pick the first in-stock variation as default, otherwise the first available
                $default_variation = $this->get_default_variation( $variations_data );

                if ( ! $default_variation ) {
                        return;
                }

                if ( empty( $default_variation['price_html'] ) ) {
                        $default_variation['price_html'] = wc_price( $default_variation['price'] );
                }

                $default_variation_attrs = isset( $default_variation['attributes'] ) ? $default_variation['attributes'] : [];

                // Get attributes for variations
                list( $main_attribute_name, $main_attribute_values ) = $this->get_main_attribute( $product->get_variation_attributes() );

                ?>
                <?php
                $default_variation_id   = $default_variation['id'];
                $default_price          = $default_variation['price'];
                $default_price_html     = $default_variation['price_html'];
                $default_price_html_raw = wp_kses_post( $default_price_html );
                $reviews_summary        = ( isset( $settings['show_product_reviews_summary'] ) && 'yes' === $settings['show_product_reviews_summary'] )
                        ? $this->get_product_reviews_summary( $product->get_id() )
                        : null;
                $reviews_summary_html   = $reviews_summary
                        ? $this->render_product_reviews_summary( $reviews_summary, isset( $settings['show_product_reviews_count'] ) && 'yes' === $settings['show_product_reviews_count'] )
                        : '';
                $trust_settings         = $this->get_reviews_trust_settings();
                $review_slider_items    = $this->get_review_slider_items( $trust_settings );
                $review_box_content     = $this->get_review_box_content( $trust_settings );
                $show_review_slider     = ! empty( $trust_settings['enable_review_slider'] ) && ! empty( $review_slider_items );
                $show_review_box        = ! empty( $trust_settings['enable_review_box'] ) && '' !== $review_box_content;
                $info_box_items         = ( isset( $settings['show_digital_product_info'] ) && 'yes' === $settings['show_digital_product_info'] )
                        ? $this->get_digital_product_info_items( $settings )
                        : [];
                $show_info_box          = ! empty( $info_box_items );
                $faq_button_enabled     = isset( $settings['show_faq_button'] ) && 'yes' === $settings['show_faq_button'];
                $faq_button_label       = isset( $settings['faq_button_label'] ) && '' !== trim( (string) $settings['faq_button_label'] )
                        ? (string) $settings['faq_button_label']
                        : __( 'Frequently Asked Questions', 'bw' );
                $faq_button_link        = isset( $settings['faq_button_link'] ) && is_array( $settings['faq_button_link'] )
                        ? $settings['faq_button_link']
                        : [];
                $faq_button_url         = isset( $faq_button_link['url'] ) ? esc_url( $faq_button_link['url'] ) : '';
                $show_faq_button_block  = ( $faq_button_enabled && '' !== $faq_button_url ) || ( $faq_button_enabled && '' === $faq_button_url && $is_editor_mode );
                ?>
                <div
                        class="bw-price-variation"
                        data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
                        data-variations="<?php echo esc_attr( wp_json_encode( $variations_data ) ); ?>"
                        data-default-variation-id="<?php echo esc_attr( $default_variation_id ); ?>"
                        data-product-url="<?php echo esc_url( $product->get_permalink() ); ?>"
                        data-checkout-url="<?php echo esc_url( wc_get_checkout_url() ); ?>"
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

                        <?php if ( '' !== $reviews_summary_html ) : ?>
                                <?php echo $reviews_summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php endif; ?>

                        <!-- Variation Buttons -->
                        <?php if ( ! empty( $main_attribute_values ) ) : ?>
                                <?php $attribute_key = 'attribute_' . sanitize_title( $main_attribute_name ); ?>
                                <div class="bw-price-variation__variations">
                                        <?php foreach ( $main_attribute_values as $attribute_value ) : ?>
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
                                                        type="button"
                                                        aria-pressed="<?php echo esc_attr( 'active' === $is_active ? 'true' : 'false' ); ?>"
                                                        aria-disabled="<?php echo esc_attr( $is_out_of_stock ? 'true' : 'false' ); ?>"
                                                >
                                                        <?php echo esc_html( $attribute_value ); ?>
                                                </button>
                                        <?php endforeach; ?>
                                </div>
                        <?php endif; ?>

			<!-- License Box -->
			<?php
			$default_license_html  = isset( $default_variation['license_html'] ) ? $default_variation['license_html'] : '';
			$accordion_mobile      = isset( $settings['license_box_accordion_mobile'] ) && 'yes' === $settings['license_box_accordion_mobile'];
			$accordion_desktop     = isset( $settings['license_box_accordion_desktop'] ) && 'yes' === $settings['license_box_accordion_desktop'];
			$has_accordion         = $accordion_mobile || $accordion_desktop;
			$accordion_label       = $has_accordion && isset( $settings['license_box_accordion_label'] ) && '' !== trim( $settings['license_box_accordion_label'] )
				? $settings['license_box_accordion_label']
				: __( 'About License', 'bw' );
			$accordion_cls         = 'bw-price-variation__license-accordion';
			if ( $accordion_mobile ) {
				$accordion_cls .= ' bw-license-accordion--mobile';
			}
			if ( $accordion_desktop ) {
				$accordion_cls .= ' bw-license-accordion--desktop';
			}
			?>
			<?php if ( $has_accordion ) : ?>
			<div class="<?php echo esc_attr( $accordion_cls ); ?>">
				<button class="bw-license-accordion__trigger" type="button" aria-expanded="false">
					<span class="bw-license-accordion__label"><?php echo esc_html( $accordion_label ); ?></span>
					<span class="bw-license-accordion__arrow" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
				</button>
				<div class="bw-license-accordion__body" aria-hidden="true">
					<div class="bw-license-accordion__body-inner">
						<div class="bw-price-variation__license-box"<?php echo $default_license_html ? '' : ' style="display: none;"'; ?>><?php echo $default_license_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already wp_kses_post'd in get_variations_data() ?></div>
					</div>
				</div>
			</div>
			<?php else : ?>
			<div class="bw-price-variation__license-box"<?php echo $default_license_html ? '' : ' style="display: none;"'; ?>><?php echo $default_license_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already wp_kses_post'd in get_variations_data() ?></div>
			<?php endif; ?>

                        <!-- Add To Cart Button -->
                        <?php if ( isset( $settings['show_add_to_cart'] ) && 'yes' === $settings['show_add_to_cart'] ) : ?>
                                <?php
                                $button_text = isset( $settings['add_to_cart_button_text'] ) && '' !== trim( $settings['add_to_cart_button_text'] )
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
                                $checkout_url    = add_query_arg( $url_params, wc_get_checkout_url() );

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
				<?php if ( isset( $settings['show_more_payment_options'] ) && 'yes' === $settings['show_more_payment_options'] && isset( $settings['payment_options_style_enabled'] ) && 'yes' === $settings['payment_options_style_enabled'] ) : ?>
					<div class="bw-price-variation__payment-options-wrapper">
						<a
							class="bw-price-variation__payment-options"
							href="<?php echo esc_url( $checkout_url ); ?>"
							data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
							data-variation_id="<?php echo esc_attr( $default_variation_id ); ?>"
							data-selected-variation-id="<?php echo esc_attr( $default_variation_id ); ?>"
						>
							<?php esc_html_e( 'More payment options', 'bw' ); ?>
						</a>
					</div>
				<?php endif; ?>
                        <?php endif; ?>

                        <?php if ( $show_review_slider || $show_review_box || $show_info_box || $show_faq_button_block ) : ?>
                                <div class="bw-price-variation__trust-stack">
                                        <?php if ( $show_review_slider ) : ?>
                                                <?php $is_single_review_slide = count( $review_slider_items ) < 2; ?>
                                                <section
                                                        class="bw-price-variation__trust-card bw-price-variation__review-slider<?php echo $is_single_review_slide ? ' is-single-slide' : ''; ?>"
                                                        data-bw-price-review-slider
                                                >
                                                        <?php if ( ! $is_single_review_slide ) : ?>
                                                                <button class="bw-price-variation__review-slider-arrow bw-price-variation__review-slider-arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Previous review', 'bw' ); ?>">
                                                                        <svg viewBox="0 0 20 20" focusable="false" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                        <?php endif; ?>

                                                        <div class="bw-embla-viewport bw-price-variation__review-slider-viewport">
                                                                <div class="bw-embla-container bw-price-variation__review-slider-container">
                                                                        <?php foreach ( $review_slider_items as $index => $review_item ) : ?>
                                                                                <article class="bw-embla-slide bw-price-variation__review-slide<?php echo 0 === $index ? ' is-active' : ''; ?>">
                                                                                        <div class="bw-price-variation__review-slide-stars" aria-hidden="true">★★★★★</div>
                                                                                        <blockquote class="bw-price-variation__review-slide-text">"<?php echo esc_html( $review_item['text'] ); ?>"</blockquote>
                                                                                        <div class="bw-price-variation__review-slide-author"><?php echo esc_html( $review_item['author'] ); ?></div>
                                                                                </article>
                                                                        <?php endforeach; ?>
                                                                </div>
                                                        </div>

                                                        <?php if ( ! $is_single_review_slide ) : ?>
                                                                <button class="bw-price-variation__review-slider-arrow bw-price-variation__review-slider-arrow--next" type="button" aria-label="<?php esc_attr_e( 'Next review', 'bw' ); ?>">
                                                                        <svg viewBox="0 0 20 20" focusable="false" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                </button>
                                                        <?php endif; ?>
                                                </section>
                                        <?php endif; ?>

                                        <?php if ( $show_review_box ) : ?>
                                                <section class="bw-price-variation__trust-card bw-price-variation__review-box">
                                                        <div class="bw-price-variation__review-box-stars" aria-hidden="true">★★★★★</div>
                                                        <div class="bw-price-variation__review-box-content"><?php echo $review_box_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized via wp_kses_post ?></div>
                                                </section>
                                        <?php endif; ?>

                                        <?php if ( $show_info_box ) : ?>
                                                <section class="bw-price-variation__info-grid">
                                                        <?php foreach ( $info_box_items as $info_item ) : ?>
                                                                <article class="bw-price-variation__trust-card bw-price-variation__info-card">
                                                                        <?php if ( ! empty( $info_item['icon']['value'] ) ) : ?>
                                                                                <div class="bw-price-variation__info-card-icon" aria-hidden="true">
                                                                                        <?php echo $this->render_digital_product_info_icon( $info_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                                                </div>
                                                                        <?php endif; ?>
                                                                        <?php if ( '' !== $info_item['title'] ) : ?>
                                                                                <h3 class="bw-price-variation__info-card-title"><?php echo esc_html( $info_item['title'] ); ?></h3>
                                                                        <?php endif; ?>
                                                                        <?php if ( '' !== $info_item['description'] ) : ?>
                                                                                <p class="bw-price-variation__info-card-description"><?php echo esc_html( $info_item['description'] ); ?></p>
                                                                        <?php endif; ?>
                                                                </article>
                                                        <?php endforeach; ?>
                                                </section>
                                        <?php endif; ?>

                                        <?php if ( $show_faq_button_block ) : ?>
                                                <?php
                                                $faq_target = ! empty( $faq_button_link['is_external'] ) ? ' target="_blank"' : '';
                                                $faq_rel    = [];
                                                if ( ! empty( $faq_button_link['is_external'] ) ) {
                                                        $faq_rel[] = 'noopener';
                                                        $faq_rel[] = 'noreferrer';
                                                }
                                                if ( ! empty( $faq_button_link['nofollow'] ) ) {
                                                        $faq_rel[] = 'nofollow';
                                                }
                                                ?>
                                                <div class="bw-price-variation__faq-wrapper">
                                                        <?php if ( '' !== $faq_button_url ) : ?>
                                                                <a class="bw-price-variation__trust-card bw-price-variation__faq-button" href="<?php echo esc_url( $faq_button_url ); ?>"<?php echo $faq_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo ! empty( $faq_rel ) ? ' rel="' . esc_attr( implode( ' ', array_unique( $faq_rel ) ) ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                                                        <span class="bw-price-variation__faq-button-icon" aria-hidden="true">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                                                                        </span>
                                                                        <span class="bw-price-variation__faq-button-label"><?php echo esc_html( $faq_button_label ); ?></span>
                                                                </a>
                                                        <?php else : ?>
                                                                <div class="bw-price-variation__trust-card bw-price-variation__faq-button bw-price-variation__faq-button--preview" aria-disabled="true">
                                                                        <span class="bw-price-variation__faq-button-icon" aria-hidden="true">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                                                                        </span>
                                                                        <span class="bw-price-variation__faq-button-label"><?php echo esc_html( $faq_button_label ); ?></span>
                                                                </div>
                                                        <?php endif; ?>
                                                </div>
                                        <?php endif; ?>
                                </div>
                        <?php endif; ?>
                </div>
                <?php
        }
}
