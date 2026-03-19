<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Controls_Manager;

// ---------------------------------------------------------------------------
// Asset registration
// ---------------------------------------------------------------------------

add_action( 'init', 'bw_ess_register_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'bw_ess_enqueue_editor_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'bw_ess_enqueue_frontend_assets' );

function bw_ess_register_assets() {
    $dir = __DIR__ . '/assets/';
    $url = BW_MEW_URL . 'includes/modules/elementor-sticky-sidebar/assets/';

    wp_register_style(
        'bw-ess-style',
        $url . 'elementor-sticky-sidebar.css',
        [],
        file_exists( $dir . 'elementor-sticky-sidebar.css' ) ? filemtime( $dir . 'elementor-sticky-sidebar.css' ) : '1'
    );

    wp_register_script(
        'bw-ess-script',
        $url . 'elementor-sticky-sidebar.js',
        [ 'jquery' ],
        file_exists( $dir . 'elementor-sticky-sidebar.js' ) ? filemtime( $dir . 'elementor-sticky-sidebar.js' ) : '1',
        true
    );
}

function bw_ess_enqueue_editor_assets() {
    if ( ! wp_style_is( 'bw-ess-style', 'registered' ) ) {
        bw_ess_register_assets();
    }
    wp_enqueue_style( 'bw-ess-style' );
}

function bw_ess_enqueue_frontend_assets() {
    if ( ! wp_style_is( 'bw-ess-style', 'registered' ) ) {
        bw_ess_register_assets();
    }
    wp_enqueue_style( 'bw-ess-style' );
    wp_enqueue_script( 'bw-ess-script' );
}

// ---------------------------------------------------------------------------
// Controls registration
//
// Primary:  inject inside Elementor Pro's Motion Effects section so BW Sticky
//           appears alongside Pro's own Scrolling Effects / Mouse Effects /
//           Sticky controls (Advanced tab > Motion Effects).
//
// Fallback: when Elementor Pro is not active the Motion Effects section does
//           not exist. In that case we create our own section in the Advanced
//           tab (after the Layout section).
// ---------------------------------------------------------------------------

add_action(
    'elementor/element/container/_section_motion_effects/before_section_end',
    'bw_ess_inject_into_motion_effects',
    10,
    2
);

function bw_ess_inject_into_motion_effects( $element, $args ) {
    bw_ess_add_sticky_controls( $element );
}

// Fallback section — registered only when Elementor Pro is absent.
// We wrap registration in plugins_loaded so all plugins are available.
add_action( 'plugins_loaded', 'bw_ess_maybe_register_fallback_section', 20 );

function bw_ess_maybe_register_fallback_section() {
    // If Elementor Pro is active it provides _section_motion_effects, which
    // already hosts our controls. Skip the fallback section.
    if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
        return;
    }

    add_action(
        'elementor/element/container/section_layout/after_section_end',
        'bw_ess_inject_fallback_section',
        10,
        2
    );
}

function bw_ess_inject_fallback_section( $element, $args ) {
    if ( ! is_object( $element ) || ! method_exists( $element, 'start_controls_section' ) ) {
        return;
    }

    $element->start_controls_section(
        'bw_ess_section',
        [
            'label' => __( 'Motion Effects (BW)', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_ADVANCED,
        ]
    );

    bw_ess_add_sticky_controls( $element );

    $element->end_controls_section();
}

// ---------------------------------------------------------------------------
// Shared control definitions
// ---------------------------------------------------------------------------

function bw_ess_add_sticky_controls( $element ) {
    if ( ! is_object( $element ) || ! method_exists( $element, 'add_control' ) ) {
        return;
    }

    $element->add_control(
        'bw_ess_sticky',
        [
            'label'   => __( 'BW Sticky', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'none' => __( 'None', 'bw-elementor-widgets' ),
                'top'  => __( 'Top', 'bw-elementor-widgets' ),
            ],
            'default' => 'none',
        ]
    );

    $element->add_control(
        'bw_ess_sticky_offset',
        [
            'label'      => __( 'Sticky Offset', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 300,
                ],
            ],
            'default'   => [
                'size' => 0,
                'unit' => 'px',
            ],
            'condition' => [ 'bw_ess_sticky' => 'top' ],
        ]
    );

    $element->add_control(
        'bw_ess_sticky_on',
        [
            'label'     => __( 'Sticky On', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'desktop' => __( 'Desktop Only', 'bw-elementor-widgets' ),
                'tablet'  => __( 'Desktop + Tablet', 'bw-elementor-widgets' ),
                'all'     => __( 'All Devices', 'bw-elementor-widgets' ),
            ],
            'default'   => 'desktop',
            'condition' => [ 'bw_ess_sticky' => 'top' ],
        ]
    );

    $element->add_control(
        'bw_ess_sticky_bound',
        [
            'label'        => __( 'Stay Within Column', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __( 'Stop scrolling when the element reaches the bottom of its parent container.', 'bw-elementor-widgets' ),
            'condition'    => [ 'bw_ess_sticky' => 'top' ],
        ]
    );

    $element->add_control(
        'bw_ess_note',
        [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => esc_html__(
                'JS-based sticky. Works regardless of overflow settings on ancestor containers.',
                'bw-elementor-widgets'
            ),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            'condition'       => [ 'bw_ess_sticky' => 'top' ],
        ]
    );
}

// ---------------------------------------------------------------------------
// Render attributes
// ---------------------------------------------------------------------------

add_action( 'elementor/frontend/container/before_render', 'bw_ess_apply_render_attributes' );

function bw_ess_apply_render_attributes( $element ) {
    if ( ! is_object( $element ) || ! method_exists( $element, 'get_settings_for_display' ) ) {
        return;
    }

    $settings = $element->get_settings_for_display();
    $sticky   = isset( $settings['bw_ess_sticky'] ) ? $settings['bw_ess_sticky'] : 'none';

    if ( 'top' !== $sticky ) {
        return;
    }

    $offset  = isset( $settings['bw_ess_sticky_offset']['size'] )
        ? absint( $settings['bw_ess_sticky_offset']['size'] )
        : 0;

    $devices = isset( $settings['bw_ess_sticky_on'] )
        ? sanitize_key( $settings['bw_ess_sticky_on'] )
        : 'desktop';

    if ( ! in_array( $devices, [ 'desktop', 'tablet', 'all' ], true ) ) {
        $devices = 'desktop';
    }

    $bound = ( isset( $settings['bw_ess_sticky_bound'] ) && 'yes' === $settings['bw_ess_sticky_bound'] )
        ? 'yes'
        : 'no';

    $element->add_render_attribute( '_wrapper', 'data-bw-sticky', 'yes' );
    $element->add_render_attribute( '_wrapper', 'data-bw-sticky-offset', $offset );
    $element->add_render_attribute( '_wrapper', 'data-bw-sticky-on', $devices );
    $element->add_render_attribute( '_wrapper', 'data-bw-sticky-bound', $bound );
}
