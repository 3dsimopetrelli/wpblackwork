<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Navshop_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-navshop';
    }

    public function get_title() {
        return __( 'BW NavShop', 'bw' );
    }

    public function get_icon() {
        return 'eicon-nav-menu';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-navshop-style', 'registered' ) && function_exists( 'bw_register_navshop_widget_assets' ) ) {
            bw_register_navshop_widget_assets();
        }

        return [ 'bw-navshop-style' ];
    }

    public function get_script_depends() {
        if ( ! wp_script_is( 'bw-navshop-script', 'registered' ) && function_exists( 'bw_register_navshop_widget_assets' ) ) {
            bw_register_navshop_widget_assets();
        }

        return [ 'bw-navshop-script' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls() {
        // Content Settings
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content Settings', 'bw' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'cart_text',
            [
                'label'       => __( 'Cart Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Cart', 'bw' ),
                'placeholder' => __( 'Enter cart text', 'bw' ),
                'label_block' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'cart_link',
            [
                'label'       => __( 'Cart Link', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '/cart/',
                'placeholder' => __( 'Enter cart URL', 'bw' ),
                'label_block' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'use_cart_popup',
            [
                'label'        => __( 'Usa Cart Pop-Up', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'ON', 'bw' ),
                'label_off'    => __( 'OFF', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'description'  => __( 'Se è ON, il pulsante Cart attiva l\'animazione Cart Pop-Up', 'bw' ),
                'render_type'  => 'template',
            ]
        );

        $this->add_control(
            'show_cart_count',
            [
                'label'        => __( 'Show Cart Count', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'ON', 'bw' ),
                'label_off'    => __( 'OFF', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'description'  => __( 'Mostra il contatore quantità in alto a destra della voce Cart.', 'bw' ),
                'render_type'  => 'template',
            ]
        );

        $this->add_control(
            'account_text',
            [
                'label'       => __( 'Account Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Account', 'bw' ),
                'placeholder' => __( 'Enter account text', 'bw' ),
                'label_block' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'account_link',
            [
                'label'       => __( 'Account Link', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '/my-account/',
                'placeholder' => __( 'Enter account URL', 'bw' ),
                'label_block' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'show_account_mobile',
            [
                'label'        => __( 'Show Account on Mobile', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'ON', 'bw' ),
                'label_off'    => __( 'OFF', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Se OFF, il tasto Account viene nascosto su mobile.', 'bw' ),
                'render_type'  => 'template',
            ]
        );

        $this->add_control(
            'account_mobile_breakpoint',
            [
                'label'       => __( 'Account Mobile Breakpoint (px)', 'bw' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 767,
                'min'         => 320,
                'max'         => 1920,
                'step'        => 1,
                'description' => __( 'Sotto questo breakpoint (max-width), il tasto Account viene nascosto se "Show Account on Mobile" è OFF.', 'bw' ),
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'cart_mobile_custom_svg',
            [
                'label'       => __( 'Mobile Cart Custom SVG', 'bw' ),
                'type'        => Controls_Manager::MEDIA,
                'description' => __( 'Carica un file SVG personalizzato per l\'icona Cart in mobile. Se vuoto usa l\'icona di default.', 'bw' ),
                'render_type' => 'template',
            ]
        );

        $this->add_responsive_control(
            'cart_mobile_icon_size',
            [
                'label'      => __( 'Mobile Cart Icon Size', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => 8, 'max' => 120, 'step' => 1 ],
                    'em' => [ 'min' => 0.5, 'max' => 8, 'step' => 0.1 ],
                    'rem' => [ 'min' => 0.5, 'max' => 8, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => 1.1,
                    'unit' => 'em',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop__cart-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'cart_icon_mobile_breakpoint',
            [
                'label'       => __( 'Cart Icon Breakpoint (px)', 'bw' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 767,
                'min'         => 320,
                'max'         => 1920,
                'step'        => 1,
                'description' => __( 'Sotto questo breakpoint (max-width), la voce Cart diventa icona.', 'bw' ),
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'reverse_order',
            [
                'label'        => __( 'Reverse Order', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'description'  => __( 'Enable to show Account before Cart', 'bw' ),
                'render_type'  => 'template',
            ]
        );

        $this->add_responsive_control(
            'items_spacing',
            [
                'label'      => __( 'Spacing Between Items', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                    'em' => [ 'min' => 0, 'max' => 10, 'step' => 0.1 ],
                    'rem' => [ 'min' => 0, 'max' => 10, 'step' => 0.1 ],
                ],
                'default'    => [ 'size' => 20, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop__item:not(:last-child)' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        // Typography and Colors
        $this->start_controls_section(
            'section_style',
            [
                'label' => __( 'Typography & Colors', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'text_typography',
                'selector' => '{{WRAPPER}} .bw-navshop__item',
                'fields_options' => [
                    'font_size' => [
                        'default' => [
                            'unit' => 'px',
                            'size' => 24,
                        ],
                    ],
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_text_colors' );

        $this->start_controls_tab(
            'tab_text_normal',
            [
                'label' => __( 'Normal', 'bw' ),
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop__item' => 'color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_text_hover',
            [
                'label' => __( 'Hover', 'bw' ),
            ]
        );

        $this->add_control(
            'text_color_hover',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop__item:hover' => 'color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'text_alignment',
            [
                'label'        => __( 'Alignment', 'bw' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => [
                    'left'   => [
                        'title' => __( 'Left', 'bw' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'bw' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __( 'Right', 'bw' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'      => 'left',
                'selectors_dictionary' => [
                    'left'   => 'flex-start',
                    'center' => 'center',
                    'right'  => 'flex-end',
                ],
                'selectors'    => [
                    '{{WRAPPER}}' => 'display: flex; justify-content: {{VALUE}};',
                ],
                'toggle'       => false,
                'render_type'  => 'ui',
                'separator'    => 'before',
            ]
        );

        $this->add_responsive_control(
            'widget_position',
            [
                'label'        => __( 'Widget Position', 'bw' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => [
                    'left'   => [
                        'title' => __( 'Left', 'bw' ),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'bw' ),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'right'  => [
                        'title' => __( 'Right', 'bw' ),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'default'      => 'left',
                'selectors_dictionary' => [
                    'left'   => 'margin-left: 0; margin-right: 0;',
                    'center' => 'margin-left: auto; margin-right: auto;',
                    'right'  => 'margin-left: auto; margin-right: 0;',
                ],
                'selectors'    => [
                    '{{WRAPPER}}' => '{{VALUE}}',
                ],
                'toggle'       => false,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_cart_count_style',
            [
                'label'     => __( 'Cart Count Badge', 'bw' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_cart_count' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'cart_count_typography',
                'selector' => '{{WRAPPER}} .bw-navshop__cart-count',
            ]
        );

        $this->add_control(
            'cart_count_text_color',
            [
                'label'     => __( 'Number Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop__cart-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cart_count_background_color',
            [
                'label'     => __( 'Circle Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop__cart-count' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'cart_count_padding',
            [
                'label'      => __( 'Badge Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', 'rem' ],
                'default'    => [
                    'top'    => 0,
                    'right'  => 4,
                    'bottom' => 0,
                    'left'   => 4,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop__cart-count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'cart_count_offset_top',
            [
                'label'      => __( 'Position Top', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => -80, 'max' => 80, 'step' => 1 ],
                    'em' => [ 'min' => -5, 'max' => 5, 'step' => 0.1 ],
                    'rem' => [ 'min' => -5, 'max' => 5, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => -0.55,
                    'unit' => 'em',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop__cart-count' => 'top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'cart_count_offset_right',
            [
                'label'      => __( 'Position Right', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => -120, 'max' => 120, 'step' => 1 ],
                    'em' => [ 'min' => -8, 'max' => 8, 'step' => 0.1 ],
                    'rem' => [ 'min' => -8, 'max' => 8, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => -0.85,
                    'unit' => 'em',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop__cart-count' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_cart_icon_style',
            [
                'label' => __( 'Mobile Cart Icon', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'cart_mobile_icon_color',
            [
                'label'     => __( 'Icon Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop__cart-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $cart_text = isset( $settings['cart_text'] ) && '' !== trim( $settings['cart_text'] )
            ? $settings['cart_text']
            : __( 'Cart', 'bw' );

        $cart_link = isset( $settings['cart_link'] ) && '' !== trim( $settings['cart_link'] )
            ? $settings['cart_link']
            : '/cart/';

        $account_text = isset( $settings['account_text'] ) && '' !== trim( $settings['account_text'] )
            ? $settings['account_text']
            : __( 'Account', 'bw' );

        $account_link = isset( $settings['account_link'] ) && '' !== trim( $settings['account_link'] )
            ? $settings['account_link']
            : '/my-account/';

        $reverse_order = isset( $settings['reverse_order'] ) && 'yes' === $settings['reverse_order'];
        $use_cart_popup = isset( $settings['use_cart_popup'] ) && 'yes' === $settings['use_cart_popup'];
        $show_cart_count = isset( $settings['show_cart_count'] ) && 'yes' === $settings['show_cart_count'];
        $show_account_mobile = ! isset( $settings['show_account_mobile'] ) || 'yes' === $settings['show_account_mobile'];
        $account_mobile_breakpoint = isset( $settings['account_mobile_breakpoint'] )
            ? max( 320, min( 1920, absint( $settings['account_mobile_breakpoint'] ) ) )
            : 767;
        $cart_icon_mobile_breakpoint = isset( $settings['cart_icon_mobile_breakpoint'] )
            ? max( 320, min( 1920, absint( $settings['cart_icon_mobile_breakpoint'] ) ) )
            : 767;
        $cart_count = $show_cart_count ? $this->get_cart_count() : 0;
        $cart_mobile_icon_svg = $this->get_cart_mobile_icon_markup( $settings );

        // Attributi per il link del cart
        $cart_attrs = '';
        if ( $use_cart_popup ) {
            $cart_attrs = ' data-use-popup="yes"';
        }

        $wrapper_classes = [ 'bw-navshop' ];
        if ( ! $show_account_mobile ) {
            $wrapper_classes[] = 'bw-navshop--hide-account-mobile';
        }

        ?>
        <style>
            @media (max-width: <?php echo esc_html( $cart_icon_mobile_breakpoint ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-navshop__cart-label {
                    display: none;
                }

                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-navshop__cart-icon {
                    display: inline-flex;
                }
            }
        </style>
        <?php if ( ! $show_account_mobile ) : ?>
            <style>
                @media (max-width: <?php echo esc_html( $account_mobile_breakpoint ); ?>px) {
                    .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-navshop--hide-account-mobile .bw-navshop__account {
                        display: none;
                    }
                }
            </style>
        <?php endif; ?>
        <div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
            <?php if ( $reverse_order ) : ?>
                <a href="<?php echo esc_url( $account_link ); ?>" class="bw-navshop__item bw-navshop__account">
                    <?php echo esc_html( $account_text ); ?>
                </a>
                <a href="<?php echo esc_url( $cart_link ); ?>" class="bw-navshop__item bw-navshop__cart" aria-label="<?php echo esc_attr( $cart_text ); ?>"<?php echo $cart_attrs; ?>>
                    <span class="bw-navshop__cart-label"><?php echo esc_html( $cart_text ); ?></span>
                    <span class="bw-navshop__cart-icon" aria-hidden="true">
                        <?php echo $cart_mobile_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                    <?php if ( $show_cart_count ) : ?>
                        <span class="bw-navshop__cart-count<?php echo $cart_count > 0 ? '' : ' is-empty'; ?>"><?php echo esc_html( $cart_count ); ?></span>
                    <?php endif; ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $cart_link ); ?>" class="bw-navshop__item bw-navshop__cart" aria-label="<?php echo esc_attr( $cart_text ); ?>"<?php echo $cart_attrs; ?>>
                    <span class="bw-navshop__cart-label"><?php echo esc_html( $cart_text ); ?></span>
                    <span class="bw-navshop__cart-icon" aria-hidden="true">
                        <?php echo $cart_mobile_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                    <?php if ( $show_cart_count ) : ?>
                        <span class="bw-navshop__cart-count<?php echo $cart_count > 0 ? '' : ' is-empty'; ?>"><?php echo esc_html( $cart_count ); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo esc_url( $account_link ); ?>" class="bw-navshop__item bw-navshop__account">
                    <?php echo esc_html( $account_text ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Restituisce la quantità articoli nel carrello WooCommerce.
     *
     * @return int
     */
    private function get_cart_count() {
        if ( ! function_exists( 'WC' ) ) {
            return 0;
        }

        $wc = WC();
        if ( ! $wc || ! isset( $wc->cart ) || ! $wc->cart ) {
            return 0;
        }

        return max( 0, (int) $wc->cart->get_cart_contents_count() );
    }

    /**
     * Restituisce il markup SVG dell'icona cart mobile (custom o fallback).
     *
     * @param array<string,mixed> $settings Widget settings.
     * @return string
     */
    private function get_cart_mobile_icon_markup( $settings ) {
        $media_id = isset( $settings['cart_mobile_custom_svg']['id'] ) ? absint( $settings['cart_mobile_custom_svg']['id'] ) : 0;
        $media_url = isset( $settings['cart_mobile_custom_svg']['url'] ) ? esc_url( $settings['cart_mobile_custom_svg']['url'] ) : '';

        if ( $media_id > 0 ) {
            $image_markup = wp_get_attachment_image( $media_id, 'full', false, [ 'class' => 'bw-navshop__cart-icon-image' ] );
            if ( $image_markup ) {
                return $image_markup;
            }
        }

        if ( '' !== $media_url ) {
            return sprintf(
                '<img class="bw-navshop__cart-icon-image" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
                $media_url,
                esc_attr__( 'Cart', 'bw' )
            );
        }

        // Backward compatibility: if previous saved value was raw SVG markup in textarea.
        $custom_svg = isset( $settings['cart_mobile_custom_svg'] ) && is_string( $settings['cart_mobile_custom_svg'] )
            ? trim( (string) $settings['cart_mobile_custom_svg'] )
            : '';

        if ( '' !== $custom_svg ) {
            $sanitized_svg = $this->sanitize_svg_markup( $custom_svg );
            if ( '' !== $sanitized_svg ) {
                return $sanitized_svg;
            }
        }

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle><path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H7"></path></svg>';
    }

    /**
     * Sanitizza markup SVG consentendo solo tag/attributi sicuri.
     *
     * @param string $svg SVG raw markup.
     * @return string
     */
    private function sanitize_svg_markup( $svg ) {
        $allowed_svg_tags = [
            'svg'     => [
                'xmlns'             => true,
                'viewBox'           => true,
                'width'             => true,
                'height'            => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
                'stroke-linejoin'   => true,
                'class'             => true,
                'role'              => true,
                'aria-hidden'       => true,
                'focusable'         => true,
            ],
            'path'    => [
                'd'                 => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
                'stroke-linejoin'   => true,
                'transform'         => true,
            ],
            'circle'  => [
                'cx'                => true,
                'cy'                => true,
                'r'                 => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
            ],
            'rect'    => [
                'x'                 => true,
                'y'                 => true,
                'width'             => true,
                'height'            => true,
                'rx'                => true,
                'ry'                => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
            ],
            'line'    => [
                'x1'                => true,
                'y1'                => true,
                'x2'                => true,
                'y2'                => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
            ],
            'polyline' => [
                'points'            => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
                'stroke-linejoin'   => true,
            ],
            'polygon' => [
                'points'            => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
                'stroke-linejoin'   => true,
            ],
            'g'       => [
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'transform'         => true,
            ],
            'title'   => [],
            'desc'    => [],
        ];

        return wp_kses( $svg, $allowed_svg_tags );
    }
}

if ( ! function_exists( 'bw_navshop_cart_count_fragment' ) ) {
    /**
     * Aggiorna il badge quantità cart del widget NavShop via WooCommerce fragments.
     *
     * @param array<string,string> $fragments Frammenti WooCommerce.
     * @return array<string,string>
     */
    function bw_navshop_cart_count_fragment( $fragments ) {
        if ( ! function_exists( 'WC' ) ) {
            return $fragments;
        }

        $wc = WC();
        if ( ! $wc || ! isset( $wc->cart ) || ! $wc->cart ) {
            return $fragments;
        }

        $count = max( 0, (int) $wc->cart->get_cart_contents_count() );
        $class = $count > 0 ? 'bw-navshop__cart-count' : 'bw-navshop__cart-count is-empty';
        $markup = '<span class="' . esc_attr( $class ) . '">' . esc_html( $count ) . '</span>';

        $fragments['.bw-navshop__cart .bw-navshop__cart-count'] = $markup;

        return $fragments;
    }
}
add_filter( 'woocommerce_add_to_cart_fragments', 'bw_navshop_cart_count_fragment' );
