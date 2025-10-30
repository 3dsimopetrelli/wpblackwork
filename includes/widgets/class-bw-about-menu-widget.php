<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_About_Menu_Widget extends Widget_Base {
    /**
     * Holds the menu identifier used while filtering link attributes.
     *
     * @var int|string|null
     */
    private $current_menu_for_filter = null;

    public function get_name() {
        return 'bw-about-menu';
    }

    public function get_title() {
        return __( 'BW About Menu', 'bw' );
    }

    public function get_icon() {
        return 'eicon-nav-menu';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( function_exists( 'bw_register_about_menu_widget_assets' ) && ! wp_style_is( 'bw-about-menu-style', 'registered' ) ) {
            bw_register_about_menu_widget_assets();
        }

        return wp_style_is( 'bw-about-menu-style', 'registered' ) ? [ 'bw-about-menu-style' ] : [];
    }

    public function get_script_depends() {
        if ( function_exists( 'bw_register_about_menu_widget_assets' ) && ! wp_script_is( 'bw-about-menu-script', 'registered' ) ) {
            bw_register_about_menu_widget_assets();
        }

        return wp_script_is( 'bw-about-menu-script', 'registered' ) ? [ 'bw-about-menu-script' ] : [];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_container_controls();
        $this->register_style_items_controls();
    }

    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
            ]
        );

        $this->add_control(
            'menu_id',
            [
                'label'       => __( 'Menu', 'bw' ),
                'type'        => Controls_Manager::SELECT,
                'label_block' => true,
                'options'     => $this->get_available_menus(),
                'description' => __( 'Select a WordPress menu to display.', 'bw' ),
                'render_type' => 'template',
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label'        => __( 'Alignment', 'bw' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => [
                    'flex-start' => [
                        'title' => __( 'Left', 'bw' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center'     => [
                        'title' => __( 'Center', 'bw' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'flex-end'   => [
                        'title' => __( 'Right', 'bw' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'      => 'center',
                'selectors'    => [
                    '{{WRAPPER}} .bw-about-menu'       => 'justify-content: {{VALUE}};',
                    '{{WRAPPER}} .bw-about-menu__list' => 'justify-content: {{VALUE}};',
                ],
                'toggle'       => true,
                'render_type'  => 'ui',
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_container_controls() {
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __( 'Menu Container', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_border_color',
            [
                'label'     => __( 'Border Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-about-menu' => 'border-color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_responsive_control(
            'container_border_width',
            [
                'label'      => __( 'Border Width', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-about-menu' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-about-menu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'container_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-about-menu' => 'background-color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-about-menu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_responsive_control(
            'container_gap',
            [
                'label'      => __( 'Items Gap', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-about-menu__list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_items_controls() {
        $this->start_controls_section(
            'section_style_items',
            [
                'label' => __( 'Menu Items', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'items_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-about-menu__list' => 'color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'items_typography',
                'selector' => '{{WRAPPER}} .bw-about-menu__link',
                'render_type' => 'ui',
            ]
        );

        $this->add_responsive_control(
            'items_spacing',
            [
                'label'      => __( 'Spacing', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-about-menu__list .menu-item' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bw-about-menu__list .menu-item:last-child' => 'margin-right: 0;',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'items_hover_color',
            [
                'label'     => __( 'Hover Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-about-menu__list .menu-item:hover > .bw-about-menu__link' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-about-menu__list .menu-item:focus-within > .bw-about-menu__link' => 'color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'spotlight_color',
            [
                'label'     => __( 'Spotlight Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}} .bw-about-menu__list::before' => 'background: radial-gradient(circle, {{VALUE}}66 0%, {{VALUE}}00 70%);',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_control(
            'spotlight_transition_speed',
            [
                'label'      => __( 'Spotlight Transition Speed', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 's' ],
                'range'      => [
                    's' => [
                        'min' => 0.1,
                        'max' => 2,
                        'step' => 0.05,
                    ],
                ],
                'default'    => [
                    'size' => 0.5,
                    'unit' => 's',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-about-menu__list' => '--spotlight-move-duration: {{SIZE}}{{UNIT}};',
                ],
                'description' => __( 'Adjust the movement speed of the spotlight.', 'bw' ),
                'render_type' => 'ui',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $menu_id  = isset( $settings['menu_id'] ) ? $settings['menu_id'] : '';

        if ( empty( $menu_id ) ) {
            echo '<div class="bw-about-menu bw-about-menu--empty">' . esc_html__( 'Select a menu to display.', 'bw' ) . '</div>';
            return;
        }

        $this->current_menu_for_filter = $menu_id;
        add_filter( 'nav_menu_link_attributes', [ $this, 'add_link_attributes' ], 10, 3 );

        $menu = wp_nav_menu(
            [
                'menu'        => (int) $menu_id,
                'menu_class'  => 'bw-about-menu__list',
                'container'   => false,
                'fallback_cb' => '__return_empty_string',
                'echo'        => false,
                'depth'       => 1,
            ]
        );

        remove_filter( 'nav_menu_link_attributes', [ $this, 'add_link_attributes' ], 10 );
        $this->current_menu_for_filter = null;

        if ( empty( $menu ) ) {
            echo '<div class="bw-about-menu bw-about-menu--empty">' . esc_html__( 'No menu items found.', 'bw' ) . '</div>';
            return;
        }

        echo '<div class="bw-about-menu">' . $menu . '</div>';
    }

    private function get_available_menus() {
        $menus = wp_get_nav_menus();

        if ( empty( $menus ) || is_wp_error( $menus ) ) {
            return [ '' => __( 'No menus found', 'bw' ) ];
        }

        $options = [ '' => __( 'Select a menu', 'bw' ) ];

        foreach ( $menus as $menu ) {
            $options[ $menu->term_id ] = $menu->name;
        }

        return $options;
    }

    /**
     * Adds the widget specific class to menu links when rendering through wp_nav_menu.
     *
     * @param array   $atts Link attributes.
     * @param \WP_Post $item Menu item data.
     * @param stdClass $args Menu arguments.
     *
     * @return array
     */
    public function add_link_attributes( $atts, $item, $args ) {
        if ( empty( $this->current_menu_for_filter ) ) {
            return $atts;
        }

        $current_menu = (string) $this->current_menu_for_filter;

        $menu_arg = $args->menu ?? '';

        if ( $menu_arg instanceof \WP_Term ) {
            $menu_arg = (string) $menu_arg->term_id;
        } elseif ( is_object( $menu_arg ) && isset( $menu_arg->term_id ) ) {
            $menu_arg = (string) $menu_arg->term_id;
        } else {
            $menu_arg = (string) $menu_arg;
        }

        if ( '' !== $menu_arg && $menu_arg !== $current_menu ) {
            return $atts;
        }

        if ( isset( $atts['class'] ) && ! empty( $atts['class'] ) ) {
            $atts['class'] .= ' bw-about-menu__link';
        } else {
            $atts['class'] = 'bw-about-menu__link';
        }

        $atts['class'] = trim( $atts['class'] );

        return $atts;
    }
}
