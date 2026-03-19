<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Controls_Manager;

add_action( 'init', 'bw_ess_register_assets' );
add_action( 'elementor/editor/after_enqueue_styles', 'bw_ess_enqueue_assets' );
add_action( 'elementor/frontend/after_enqueue_styles', 'bw_ess_enqueue_assets' );
add_action( 'elementor/element/container/section_layout/after_section_end', 'bw_ess_register_container_controls', 10, 1 );
add_action( 'elementor/frontend/container/before_render', 'bw_ess_apply_container_render_attributes' );

function bw_ess_register_assets() {
    $handle     = 'bw-elementor-sticky-sidebar-style';
    $style_file = __DIR__ . '/assets/elementor-sticky-sidebar.css';
    $style_url  = BW_MEW_URL . 'includes/modules/elementor-sticky-sidebar/assets/elementor-sticky-sidebar.css';
    $version    = file_exists( $style_file ) ? filemtime( $style_file ) : BLACKWORK_PLUGIN_VERSION;

    if ( ! wp_style_is( $handle, 'registered' ) ) {
        wp_register_style( $handle, $style_url, [], $version );
    }
}

function bw_ess_enqueue_assets() {
    if ( ! wp_style_is( 'bw-elementor-sticky-sidebar-style', 'registered' ) ) {
        bw_ess_register_assets();
    }

    wp_enqueue_style( 'bw-elementor-sticky-sidebar-style' );
}

function bw_ess_get_layout_tab() {
    if ( defined( 'Elementor\Controls_Manager::TAB_LAYOUT' ) ) {
        return Controls_Manager::TAB_LAYOUT;
    }

    return 'layout';
}

function bw_ess_register_container_controls( $element ) {
    if ( ! is_object( $element ) || ! method_exists( $element, 'start_controls_section' ) ) {
        return;
    }

    $element->start_controls_section(
        'bw_ess_sticky_sidebar_section',
        [
            'label' => __( 'BW Sticky Sidebar', 'bw' ),
            'tab'   => bw_ess_get_layout_tab(),
        ]
    );

    $element->add_control(
        'bw_ess_enable_sticky_sidebar',
        [
            'label'        => __( 'Enable Sticky Sidebar', 'bw' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw' ),
            'label_off'    => __( 'No', 'bw' ),
            'return_value' => 'yes',
            'default'      => '',
        ]
    );

    $element->add_control(
        'bw_ess_sticky_top_offset',
        [
            'label'      => __( 'Sticky Top Offset', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 200,
                ],
            ],
            'default'    => [
                'size' => 24,
                'unit' => 'px',
            ],
            'condition'  => [
                'bw_ess_enable_sticky_sidebar' => 'yes',
            ],
        ]
    );

    $element->add_control(
        'bw_ess_sticky_devices',
        [
            'label'     => __( 'Sticky Devices', 'bw' ),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'desktop' => __( 'Desktop Only', 'bw' ),
                'tablet'  => __( 'Tablet + Desktop', 'bw' ),
                'all'     => __( 'All Devices', 'bw' ),
            ],
            'default'   => 'desktop',
            'condition' => [
                'bw_ess_enable_sticky_sidebar' => 'yes',
            ],
        ]
    );

    $element->add_control(
        'bw_ess_sticky_note',
        [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => esc_html__( 'Apply this to the outer sidebar container. Sticky can fail if any ancestor container uses overflow hidden/auto or if the sticky container is taller than the viewport.', 'bw' ),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            'condition'       => [
                'bw_ess_enable_sticky_sidebar' => 'yes',
            ],
        ]
    );

    $element->end_controls_section();
}

function bw_ess_apply_container_render_attributes( $element ) {
    if ( ! is_object( $element ) || ! method_exists( $element, 'get_settings_for_display' ) || ! method_exists( $element, 'add_render_attribute' ) ) {
        return;
    }

    $settings = $element->get_settings_for_display();

    if ( empty( $settings['bw_ess_enable_sticky_sidebar'] ) || 'yes' !== $settings['bw_ess_enable_sticky_sidebar'] ) {
        return;
    }

    $device_mode = isset( $settings['bw_ess_sticky_devices'] ) ? sanitize_key( $settings['bw_ess_sticky_devices'] ) : 'desktop';

    if ( ! in_array( $device_mode, [ 'desktop', 'tablet', 'all' ], true ) ) {
        $device_mode = 'desktop';
    }

    $top_offset = 24;

    if ( isset( $settings['bw_ess_sticky_top_offset']['size'] ) ) {
        $top_offset = absint( $settings['bw_ess_sticky_top_offset']['size'] );
    }

    $element->add_render_attribute( '_wrapper', 'class', 'bw-ess-sticky-sidebar' );
    $element->add_render_attribute( '_wrapper', 'class', 'bw-ess-sticky-sidebar--' . $device_mode );
    $element->add_render_attribute( '_wrapper', 'data-bw-sticky-sidebar', 'yes' );
    $element->add_render_attribute( '_wrapper', 'style', '--bw-ess-sticky-top: ' . $top_offset . 'px;' );
}
