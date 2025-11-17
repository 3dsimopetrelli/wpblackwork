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

        ?>
        <div class="bw-navshop">
            <?php if ( $reverse_order ) : ?>
                <a href="<?php echo esc_url( $account_link ); ?>" class="bw-navshop__item bw-navshop__account">
                    <?php echo esc_html( $account_text ); ?>
                </a>
                <a href="<?php echo esc_url( $cart_link ); ?>" class="bw-navshop__item bw-navshop__cart">
                    <?php echo esc_html( $cart_text ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $cart_link ); ?>" class="bw-navshop__item bw-navshop__cart">
                    <?php echo esc_html( $cart_text ); ?>
                </a>
                <a href="<?php echo esc_url( $account_link ); ?>" class="bw-navshop__item bw-navshop__account">
                    <?php echo esc_html( $account_text ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
}
