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
                'selectors'    => [
                    '{{WRAPPER}} .bw-navshop' => 'text-align: {{VALUE}};',
                ],
                'toggle'       => false,
                'render_type'  => 'ui',
                'separator'    => 'before',
            ]
        );

        $this->end_controls_section();

        // Account Panel Style
        $this->start_controls_section(
            'section_panel_style',
            [
                'label' => __( 'Account Panel Style', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'panel_background_color',
            [
                'label'     => __( 'Panel Background', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop-panel, body .bw-navshop-panel[data-widget-id="{{ID}}"]' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'panel_width',
            [
                'label'      => __( 'Panel Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vw' ],
                'range'      => [
                    'px' => [ 'min' => 200, 'max' => 800, 'step' => 10 ],
                    '%'  => [ 'min' => 10, 'max' => 100, 'step' => 1 ],
                    'vw' => [ 'min' => 10, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 400, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop-panel, body .bw-navshop-panel[data-widget-id="{{ID}}"]' => 'width: {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'panel_padding',
            [
                'label'      => __( 'Panel Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 40,
                    'right'  => 30,
                    'bottom' => 40,
                    'left'   => 30,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navshop-panel__content, body .bw-navshop-panel[data-widget-id="{{ID}}"] .bw-navshop-panel__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'overlay_background',
            [
                'label'     => __( 'Overlay Background', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(0, 0, 0, 0.5)',
                'selectors' => [
                    '{{WRAPPER}} .bw-navshop-overlay, body .bw-navshop-overlay[data-widget-id="{{ID}}"]' => 'background-color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $cart_text = isset( $settings['cart_text'] ) && '' !== trim( $settings['cart_text'] )
            ? $settings['cart_text']
            : __( 'Cart', 'bw' );

        $account_text = isset( $settings['account_text'] ) && '' !== trim( $settings['account_text'] )
            ? $settings['account_text']
            : __( 'Account', 'bw' );

        $reverse_order = isset( $settings['reverse_order'] ) && 'yes' === $settings['reverse_order'];

        // Get WooCommerce URLs
        $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#';
        $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '#';

        $widget_id = $this->get_id();

        ?>
        <div class="bw-navshop" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
            <?php if ( $reverse_order ) : ?>
                <a href="<?php echo esc_url( $account_url ); ?>" class="bw-navshop__item bw-navshop__account" data-action="account">
                    <?php echo esc_html( $account_text ); ?>
                </a>
                <a href="<?php echo esc_url( $cart_url ); ?>" class="bw-navshop__item bw-navshop__cart" data-action="cart">
                    <?php echo esc_html( $cart_text ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $cart_url ); ?>" class="bw-navshop__item bw-navshop__cart" data-action="cart">
                    <?php echo esc_html( $cart_text ); ?>
                </a>
                <a href="<?php echo esc_url( $account_url ); ?>" class="bw-navshop__item bw-navshop__account" data-action="account">
                    <?php echo esc_html( $account_text ); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Account Panel -->
        <div class="bw-navshop-overlay" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" style="display: none;">
            <div class="bw-navshop-panel" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
                <button class="bw-navshop-panel__close" type="button" aria-label="<?php esc_attr_e( 'Close', 'bw' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="bw-navshop-panel__content">
                    <?php
                    // Check if user is logged in
                    if ( is_user_logged_in() ) {
                        // Show account navigation for logged in users
                        if ( function_exists( 'wc_get_account_menu_items' ) ) {
                            $menu_items = wc_get_account_menu_items();
                            if ( ! empty( $menu_items ) ) {
                                echo '<nav class="bw-navshop-panel__nav">';
                                echo '<ul>';
                                foreach ( $menu_items as $endpoint => $label ) {
                                    $url = wc_get_account_endpoint_url( $endpoint );
                                    echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
                                }
                                echo '</ul>';
                                echo '</nav>';
                            }
                        }
                    } else {
                        // Show login form for guests
                        if ( function_exists( 'woocommerce_login_form' ) ) {
                            woocommerce_login_form();
                        } else {
                            echo '<p>' . __( 'Please login to access your account.', 'bw' ) . '</p>';
                            echo '<a href="' . esc_url( $account_url ) . '" class="button">' . __( 'Login', 'bw' ) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}
