<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Navigation_Widget extends Widget_Base {
    /**
     * Flag used while adding custom classes to menu links.
     *
     * @var bool
     */
    private $is_rendering_menu = false;

    public function get_name() {
        return 'bw-navigation';
    }

    public function get_title() {
        return __( 'BW Navigation', 'bw' );
    }

    public function get_icon() {
        return 'eicon-nav-menu';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( function_exists( 'bw_register_navigation_widget_assets' ) && ! wp_style_is( 'bw-navigation-style', 'registered' ) ) {
            bw_register_navigation_widget_assets();
        }

        return [ 'bw-navigation-style' ];
    }

    public function get_script_depends() {
        if ( function_exists( 'bw_register_navigation_widget_assets' ) && ! wp_script_is( 'bw-navigation-script', 'registered' ) ) {
            bw_register_navigation_widget_assets();
        }

        return [ 'bw-navigation-script' ];
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_desktop_style_controls();
        $this->register_mobile_style_controls();
        $this->register_mobile_icon_style_controls();
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
            'desktop_menu_id',
            [
                'label'       => __( 'Desktop Menu', 'bw' ),
                'type'        => Controls_Manager::SELECT,
                'label_block' => true,
                'options'     => $this->get_available_menus(),
                'default'     => '',
            ]
        );

        $this->add_control(
            'mobile_menu_id',
            [
                'label'       => __( 'Mobile Menu', 'bw' ),
                'type'        => Controls_Manager::SELECT,
                'label_block' => true,
                'options'     => $this->get_available_menus(),
                'default'     => '',
                'description' => __( 'Se vuoto, usa lo stesso menu Desktop.', 'bw' ),
            ]
        );

        $this->add_control(
            'mobile_breakpoint',
            [
                'label'       => __( 'Mobile Breakpoint (px)', 'bw' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 767,
                'min'         => 320,
                'max'         => 1920,
                'step'        => 1,
                'description' => __( 'Sotto questo breakpoint viene mostrata la versione mobile.', 'bw' ),
            ]
        );

        $this->add_control(
            'mobile_toggle_icon',
            [
                'label'       => __( 'Mobile Toggle Icon (SVG Upload)', 'bw' ),
                'type'        => Controls_Manager::MEDIA,
                'description' => __( 'Carica una SVG custom per l\'hamburger mobile. Se vuoto usa l\'icona di default.', 'bw' ),
            ]
        );

        $this->end_controls_section();
    }

    private function register_desktop_style_controls() {
        $this->start_controls_section(
            'section_desktop_style',
            [
                'label' => __( 'Desktop Menu Style', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'desktop_typography',
                'selector' => '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__link',
            ]
        );

        $this->add_control(
            'desktop_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'desktop_color_hover',
            [
                'label'     => __( 'Hover Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__link:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__link:focus-visible' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'desktop_color_active',
            [
                'label'     => __( 'Active Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__desktop .current-menu-item > .bw-navigation__link' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__desktop .current-menu-ancestor > .bw-navigation__link' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__desktop .current_page_item > .bw-navigation__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'desktop_alignment',
            [
                'label'   => __( 'Alignment', 'bw' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
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
                'default'               => 'left',
                'selectors_dictionary'  => [
                    'left'   => 'flex-start',
                    'center' => 'center',
                    'right'  => 'flex-end',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__list' => 'justify-content: {{VALUE}};',
                ],
                'toggle' => false,
            ]
        );

        $this->add_responsive_control(
            'desktop_horizontal_padding',
            [
                'label'      => __( 'Horizontal Padding', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 120, 'step' => 1 ],
                    'em' => [ 'min' => 0, 'max' => 8, 'step' => 0.1 ],
                    'rem' => [ 'min' => 0, 'max' => 8, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => 4,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__link' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'desktop_vertical_padding',
            [
                'label'      => __( 'Vertical Padding', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 120, 'step' => 1 ],
                    'em' => [ 'min' => 0, 'max' => 8, 'step' => 0.1 ],
                    'rem' => [ 'min' => 0, 'max' => 8, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__link' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'desktop_items_gap',
            [
                'label'      => __( 'Space Between', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 120, 'step' => 1 ],
                    'em' => [ 'min' => 0, 'max' => 10, 'step' => 0.1 ],
                    'rem' => [ 'min' => 0, 'max' => 10, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navigation__desktop .bw-navigation__list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_mobile_style_controls() {
        $this->start_controls_section(
            'section_mobile_style',
            [
                'label' => __( 'Mobile Menu Style', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'mobile_overlay_background',
            [
                'label'     => __( 'Mobile Panel Background', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__mobile-panel' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'mobile_typography',
                'selector' => '{{WRAPPER}} .bw-navigation__mobile .bw-navigation__link',
            ]
        );

        $this->add_control(
            'mobile_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__mobile .bw-navigation__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_color_hover',
            [
                'label'     => __( 'Hover Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__mobile .bw-navigation__link:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__mobile .bw-navigation__link:focus-visible' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_color_active',
            [
                'label'     => __( 'Active Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__mobile .current-menu-item > .bw-navigation__link' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__mobile .current-menu-ancestor > .bw-navigation__link' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__mobile .current_page_item > .bw-navigation__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_mobile_icon_style_controls() {
        $this->start_controls_section(
            'section_mobile_icon_style',
            [
                'label' => __( 'Mobile Toggle Icon', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'mobile_icon_size',
            [
                'label'      => __( 'Icon Size', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range'      => [
                    'px' => [ 'min' => 8, 'max' => 120, 'step' => 1 ],
                    'em' => [ 'min' => 0.5, 'max' => 8, 'step' => 0.1 ],
                    'rem' => [ 'min' => 0.5, 'max' => 8, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => 28,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-navigation__toggle-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_icon_color',
            [
                'label'     => __( 'Icon Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__toggle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_icon_color_hover',
            [
                'label'     => __( 'Icon Hover Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .bw-navigation__toggle:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bw-navigation__toggle:focus-visible' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $desktop_menu_id = isset( $settings['desktop_menu_id'] ) ? absint( $settings['desktop_menu_id'] ) : 0;
        $mobile_menu_id  = isset( $settings['mobile_menu_id'] ) ? absint( $settings['mobile_menu_id'] ) : 0;
        $breakpoint      = isset( $settings['mobile_breakpoint'] ) ? max( 320, min( 1920, absint( $settings['mobile_breakpoint'] ) ) ) : 767;

        if ( $desktop_menu_id <= 0 ) {
            echo '<div class="bw-navigation bw-navigation--empty">' . esc_html__( 'Select a desktop menu to display.', 'bw' ) . '</div>';
            return;
        }

        if ( $mobile_menu_id <= 0 ) {
            $mobile_menu_id = $desktop_menu_id;
        }

        $desktop_menu = $this->render_menu( $desktop_menu_id, 'bw-navigation__list bw-navigation__list--desktop' );
        $mobile_menu  = $this->render_menu( $mobile_menu_id, 'bw-navigation__list bw-navigation__list--mobile' );

        if ( empty( $desktop_menu ) ) {
            echo '<div class="bw-navigation bw-navigation--empty">' . esc_html__( 'No menu items found.', 'bw' ) . '</div>';
            return;
        }

        if ( empty( $mobile_menu ) ) {
            $mobile_menu = $desktop_menu;
        }

        $toggle_icon_markup = $this->get_toggle_icon_markup( $settings );
        $widget_id          = $this->get_id();
        ?>
        <style>
            @media (max-width: <?php echo esc_html( $breakpoint ); ?>px) {
                .elementor-element-<?php echo esc_attr( $widget_id ); ?> .bw-navigation__desktop {
                    display: none;
                }

                .elementor-element-<?php echo esc_attr( $widget_id ); ?> .bw-navigation__toggle {
                    display: inline-flex;
                }
            }

            @media (min-width: <?php echo esc_html( $breakpoint + 1 ); ?>px) {
                .elementor-element-<?php echo esc_attr( $widget_id ); ?> .bw-navigation__toggle,
                .elementor-element-<?php echo esc_attr( $widget_id ); ?> .bw-navigation__mobile-overlay {
                    display: none !important;
                }
            }
        </style>
        <div class="bw-navigation" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-breakpoint="<?php echo esc_attr( $breakpoint ); ?>">
            <nav class="bw-navigation__desktop" aria-label="<?php esc_attr_e( 'Desktop navigation', 'bw' ); ?>">
                <?php echo $desktop_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </nav>

            <button class="bw-navigation__toggle" type="button" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'bw' ); ?>">
                <span class="bw-navigation__toggle-icon" aria-hidden="true">
                    <?php echo $toggle_icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
            </button>

            <div class="bw-navigation__mobile-overlay" aria-hidden="true">
                <div class="bw-navigation__mobile-panel">
                    <button class="bw-navigation__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'bw' ); ?>">
                        <span class="bw-navigation__close-icon" aria-hidden="true"></span>
                    </button>

                    <nav class="bw-navigation__mobile" aria-label="<?php esc_attr_e( 'Mobile navigation', 'bw' ); ?>">
                        <?php echo $mobile_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_menu( $menu_id, $menu_class ) {
        if ( $menu_id <= 0 ) {
            return '';
        }

        $this->is_rendering_menu = true;
        add_filter( 'nav_menu_link_attributes', [ $this, 'filter_nav_menu_link_attributes' ], 10, 3 );

        $menu = wp_nav_menu(
            [
                'menu'        => $menu_id,
                'menu_class'  => $menu_class,
                'container'   => false,
                'fallback_cb' => '__return_empty_string',
                'echo'        => false,
                'depth'       => 1,
            ]
        );

        remove_filter( 'nav_menu_link_attributes', [ $this, 'filter_nav_menu_link_attributes' ], 10 );
        $this->is_rendering_menu = false;

        return is_string( $menu ) ? $menu : '';
    }

    /**
     * Adds widget-specific class to rendered menu links.
     *
     * @param array<string,string> $atts Link attributes.
     * @param \WP_Post             $item Menu item.
     * @param stdClass             $args Menu args.
     * @return array<string,string>
     */
    public function filter_nav_menu_link_attributes( $atts, $item, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( ! $this->is_rendering_menu ) {
            return $atts;
        }

        if ( isset( $atts['class'] ) && ! empty( $atts['class'] ) ) {
            $atts['class'] .= ' bw-navigation__link';
        } else {
            $atts['class'] = 'bw-navigation__link';
        }

        return $atts;
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

    private function get_toggle_icon_markup( $settings ) {
        $media_id  = isset( $settings['mobile_toggle_icon']['id'] ) ? absint( $settings['mobile_toggle_icon']['id'] ) : 0;
        $media_url = isset( $settings['mobile_toggle_icon']['url'] ) ? esc_url( $settings['mobile_toggle_icon']['url'] ) : '';

        if ( $media_id > 0 ) {
            $image = wp_get_attachment_image( $media_id, 'full', false, [ 'class' => 'bw-navigation__toggle-icon-image' ] );
            if ( $image ) {
                return $image;
            }
        }

        if ( '' !== $media_url ) {
            return sprintf(
                '<img class="bw-navigation__toggle-icon-image" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
                $media_url,
                esc_attr__( 'Menu', 'bw' )
            );
        }

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>';
    }
}
