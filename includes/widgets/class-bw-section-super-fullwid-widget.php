<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! class_exists( 'Widget_Bw_Section_Super_Fullwid_Base', false ) ) {
    if ( class_exists( '\\Elementor\\Widget_Nested_Base' ) ) {
        abstract class Widget_Bw_Section_Super_Fullwid_Base extends \Elementor\Widget_Nested_Base {}
    } else {
        abstract class Widget_Bw_Section_Super_Fullwid_Base extends Widget_Base {}
    }
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Section_Super_Fullwid extends Widget_Bw_Section_Super_Fullwid_Base {

    public function get_name() {
        return 'bw-section-super-fullwid';
    }

    public function get_title() {
        return __( 'BW Section Super FullWid', 'bw' );
    }

    public function get_icon() {
        return 'eicon-section';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        $handle = 'bw-section-super-fullwid';

        if ( ! wp_style_is( $handle, 'registered' ) ) {
            $this->register_widget_style( $handle );
        }

        return [ $handle ];
    }

    protected function register_controls() {
        $layout_tab = defined( 'Elementor\Controls_Manager::TAB_LAYOUT' )
            ? Controls_Manager::TAB_LAYOUT
            : Controls_Manager::TAB_CONTENT;

        $this->start_controls_section(
            'section_layout',
            [
                'label' => __( 'Layout', 'bw' ),
                'tab'   => $layout_tab,
            ]
        );

        $this->add_control(
            'section_bg_color',
            [
                'label'     => __( 'Colore sfondo', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-section-super-fullwid' => '--bw-section-bg: {{VALUE}};',
                ],
                'default'   => 'transparent',
            ]
        );

        $this->add_responsive_control(
            'section_padding',
            [
                'label'      => __( 'Padding sezione', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-section-super-fullwid' => '--bw-section-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default'    => [
                    'top'    => 80,
                    'right'  => 0,
                    'bottom' => 80,
                    'left'   => 0,
                    'unit'   => 'px',
                ],
            ]
        );

        $this->add_control(
            'overflow_toggle',
            [
                'label'     => __( 'Overflow', 'bw' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    'visible' => __( 'Visibile', 'bw' ),
                    'hidden'  => __( 'Nascosto', 'bw' ),
                ],
                'default'   => 'visible',
                'selectors' => [
                    '{{WRAPPER}} .bw-section-super-fullwid' => 'overflow: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'section_zindex',
            [
                'label'     => __( 'Z-index', 'bw' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 1,
                'selectors' => [
                    '{{WRAPPER}} .bw-section-super-fullwid' => '--bw-section-z: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'section_fullheight',
            [
                'label'        => __( 'Altezza sezione 100vh', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Attiva', 'bw' ),
                'label_off'    => __( 'Disattiva', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'vertical_align',
            [
                'label'     => __( 'Allineamento verticale', 'bw' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => __( 'Top', 'bw' ),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'center'     => [
                        'title' => __( 'Centro', 'bw' ),
                        'icon'  => 'eicon-v-align-middle',
                    ],
                    'flex-end'   => [
                        'title' => __( 'Bottom', 'bw' ),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .bw-section-super-fullwid.bw-section-fullheight .bw-section-super-inner' => 'justify-content: {{VALUE}};',
                ],
                'condition' => [
                    'section_fullheight' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings        = $this->get_settings_for_display();
        $is_fullheight   = ! empty( $settings['section_fullheight'] ) && 'yes' === $settings['section_fullheight'];
        $section_classes = [ 'bw-section-super-fullwid' ];

        if ( $is_fullheight ) {
            $section_classes[] = 'bw-section-fullheight';
        }

        $section_class_attr = implode( ' ', array_map( 'sanitize_html_class', $section_classes ) );

        echo '<section class="' . esc_attr( $section_class_attr ) . '">';
        echo '<div class="bw-section-super-inner">';
        if ( method_exists( $this, 'render_children' ) ) {
            $this->render_children();
        } elseif ( method_exists( $this, 'get_content' ) ) {
            echo $this->get_content();
        }
        echo '</div>';
        echo '</section>';
    }

    private function register_widget_style( $handle ) {
        $css_file = BW_MEW_PATH . 'assets/css/bw-section-super-fullwid.css';
        $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_register_style(
            $handle,
            BW_MEW_URL . 'assets/css/bw-section-super-fullwid.css',
            [],
            $version
        );
    }
}
