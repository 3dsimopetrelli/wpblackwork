<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW Product Slider Widget
 *
 * Horizontal Embla carousel that displays product cards via BW_Product_Card_Component.
 * Query-based: fetches products/posts via WP_Query. No popup, no custom cursor.
 */
class BW_Product_Slider_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-product-slider';
    }

    public function get_title() {
        return __( 'BW-UI Product Slider', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-post-slider';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-product-slider-script' ];
    }

    public function get_style_depends() {
        return [ 'bw-product-card-style', 'bw-embla-core-css', 'bw-product-slider-style' ];
    }

    protected function register_controls() {

        // ========================================
        // CONTENT TAB
        // ========================================

        // Query Section
        $this->start_controls_section(
            'section_query',
            [
                'label' => __( 'Query', 'bw-elementor-widgets' ),
            ]
        );

        $post_type_options = BW_Widget_Helper::get_post_type_options();
        if ( empty( $post_type_options ) ) {
            $post_type_options = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        $post_type_keys    = array_keys( $post_type_options );
        $default_post_type = array_key_exists( 'product', $post_type_options ) ? 'product' : reset( $post_type_keys );

        $this->add_control(
            'post_type',
            [
                'label'   => __( 'Post Type', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $post_type_options,
                'default' => $default_post_type,
            ]
        );

        $this->add_control(
            'parent_category',
            [
                'label'       => __( 'Categoria padre', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => false,
                'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : [],
                'condition'   => [ 'post_type' => 'product' ],
            ]
        );

        $this->add_control(
            'subcategory',
            [
                'label'       => __( 'Sotto-categoria', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => true,
                'options'     => [],
                'condition'   => [
                    'post_type'        => 'product',
                    'parent_category!' => '',
                ],
                'description' => __( 'Seleziona una o più sottocategorie della categoria padre scelta.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'specific_ids',
            [
                'label'       => __( 'ID specifici', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'es. 12, 45, 78', 'bw-elementor-widgets' ),
                'description' => __( 'Inserisci gli ID separati da virgola. Ignora le altre impostazioni query se specificato.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'order_by',
            [
                'label'   => __( 'Ordina per', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date'     => __( 'Data pubblicazione', 'bw-elementor-widgets' ),
                    'modified' => __( 'Data modifica', 'bw-elementor-widgets' ),
                    'title'    => __( 'Titolo', 'bw-elementor-widgets' ),
                    'rand'     => __( 'Casuale', 'bw-elementor-widgets' ),
                    'ID'       => __( 'ID', 'bw-elementor-widgets' ),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label'     => __( 'Direzione ordinamento', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'DESC',
                'options'   => [
                    'ASC'  => __( 'Crescente (A → Z, 1 → 9, vecchio → nuovo)', 'bw-elementor-widgets' ),
                    'DESC' => __( 'Decrescente (Z → A, 9 → 1, nuovo → vecchio)', 'bw-elementor-widgets' ),
                ],
                'condition' => [
                    'order_by!' => 'rand',
                ],
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label'   => __( 'Numero di prodotti', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 12,
                'min'     => 1,
                'max'     => 100,
            ]
        );

        $this->end_controls_section();

        // Slider Settings Section
        $this->start_controls_section(
            'section_slider_general',
            [
                'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'infinite_loop',
            [
                'label'        => __( 'Infinite Loop', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label'        => __( 'Autoplay', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'drag_free',
            [
                'label'        => __( 'Drag Free', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Free-scroll drag: the slide does not snap to position, scrolls freely', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'touch_drag',
            [
                'label'        => __( 'Touch Drag (Mobile & Tablet)', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Allow swiping with fingers to navigate slides on touch devices.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'mouse_drag',
            [
                'label'        => __( 'Mouse / Trackpad Drag (Desktop)', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Allow dragging slides with the mouse or two-finger trackpad swipe on desktop.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'slide_align',
            [
                'label'   => __( 'Slide Alignment', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'start'  => __( 'Start (default)', 'bw-elementor-widgets' ),
                    'center' => __( 'Center', 'bw-elementor-widgets' ),
                    'end'    => __( 'End', 'bw-elementor-widgets' ),
                ],
                'default'     => 'start',
                'description' => __( 'Where to align the selected slide inside the carousel viewport', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();

        // Responsive Breakpoints Section
        $this->start_controls_section(
            'section_breakpoints',
            [
                'label' => __( 'Responsive Breakpoints', 'bw-elementor-widgets' ),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'breakpoint',
            [
                'label'   => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1024,
            ]
        );

        $repeater->add_control(
            'slides_to_show',
            [
                'label'   => __( 'Slides to Show', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 2,
                'min'     => 1,
                'max'     => 10,
            ]
        );

        $repeater->add_control(
            'slides_to_scroll',
            [
                'label'   => __( 'Slides to Scroll', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1,
                'min'     => 1,
                'max'     => 10,
            ]
        );

        $repeater->add_control(
            'show_arrows',
            [
                'label'        => __( 'Show Arrows', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $repeater->add_control(
            'show_dots',
            [
                'label'        => __( 'Show Dots', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'center_mode',
            [
                'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'variable_width',
            [
                'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Use slide\'s natural width instead of a calculated fraction.', 'bw-elementor-widgets' ),
            ]
        );

        $repeater->add_control(
            'slide_width',
            [
                'label'       => __( 'Slide Width (px)', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => '',
                'min'         => 100,
                'max'         => 2000,
                'step'        => 10,
                'placeholder' => __( 'Auto', 'bw-elementor-widgets' ),
                'description' => __( 'Fixed width for slides. Leave empty for auto.', 'bw-elementor-widgets' ),
                'condition'   => [
                    'variable_width!' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'peek_amount',
            [
                'label'       => __( 'Peek Amount (px)', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 0,
                'min'         => 0,
                'max'         => 400,
                'step'        => 4,
                'description' => __( 'Mostra questa quantità di px della slide successiva per suggerire che il carousel è scorrevole. 0 = disabilitato.', 'bw-elementor-widgets' ),
                'condition'   => [
                    'variable_width!' => 'yes',
                    'slide_width'     => '',
                ],
            ]
        );

        $this->add_control(
            'breakpoints',
            [
                'label'       => __( 'Breakpoints', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    [
                        'breakpoint'       => 1280,
                        'slides_to_show'   => 3,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => 'yes',
                        'show_dots'        => '',
                    ],
                    [
                        'breakpoint'       => 767,
                        'slides_to_show'   => 2,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => 'yes',
                        'show_dots'        => '',
                    ],
                    [
                        'breakpoint'       => 480,
                        'slides_to_show'   => 1,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => '',
                        'show_dots'        => 'yes',
                    ],
                ],
                'title_field' => 'Breakpoint: {{{ breakpoint }}}px — {{{ slides_to_show }}} slides',
            ]
        );

        $this->end_controls_section();

        // Card Settings Section
        $this->start_controls_section(
            'section_card_settings',
            [
                'label' => __( 'Card Settings', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label'        => __( 'Show Title', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label'        => __( 'Show Description', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'show_price',
            [
                'label'        => __( 'Show Price', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_buttons',
            [
                'label'        => __( 'Show Overlay Buttons', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_buttons_mobile',
            [
                'label'        => __( 'Show Overlay Buttons on Mobile', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'show_buttons' => 'yes',
                ],
                'description'  => __( 'Hide the hover overlay buttons on mobile while keeping them visible on desktop and tablet.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'hover_image_source',
            [
                'label'   => __( 'Hover Image Source', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'meta',
                'options' => [
                    'meta'          => __( 'Custom Field (meta)', 'bw-elementor-widgets' ),
                    'gallery_first' => __( 'First Gallery Image', 'bw-elementor-widgets' ),
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_price_settings',
            [
                'label' => __( 'Price', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'show_lowest_variation_price_only',
            [
                'label'        => __( 'Show Lowest Variation Price Only', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'show_price' => 'yes',
                    'post_type'  => 'product',
                ],
                'description'  => __( 'For variable products, show only the lowest variation price instead of the full price range.', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();

        // ========================================
        // STYLE TAB
        // ========================================

        // Style → Images
        $this->start_controls_section(
            'section_style_images',
            [
                'label' => __( 'Images', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-slider-image-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slides_spacing',
            [
                'label'      => __( 'Spacing Between Slides', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-slide' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label'       => __( 'Image Size', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'large',
                'options'     => [
                    'thumbnail'    => __( 'Thumbnail (150×150)', 'bw-elementor-widgets' ),
                    'medium'       => __( 'Medium (300×300)', 'bw-elementor-widgets' ),
                    'medium_large' => __( 'Medium Large (768×auto)', 'bw-elementor-widgets' ),
                    'large'        => __( 'Large (1024×1024)', 'bw-elementor-widgets' ),
                    'full'         => __( 'Full Size (Original)', 'bw-elementor-widgets' ),
                ],
                'description' => __( 'Select image size for product card thumbnails.', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_title',
            [
                'label' => __( 'Title', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __( 'Typography', 'bw-elementor-widgets' ),
                'selector' => '{{WRAPPER}} .bw-product-card .bw-wallpost-title, {{WRAPPER}} .bw-product-card .bw-slider-title',
            ]
        );

        $this->add_responsive_control(
            'title_padding',
            [
                'label'      => __( 'Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-product-card .bw-wallpost-title, {{WRAPPER}} .bw-product-card .bw-slider-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_price',
            [
                'label' => __( 'Price', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'label'    => __( 'Typography', 'bw-elementor-widgets' ),
                'selector' => '{{WRAPPER}} .bw-product-card .bw-wallpost-price, {{WRAPPER}} .bw-product-card .bw-slider-price, {{WRAPPER}} .bw-product-card .price',
            ]
        );

        $this->add_responsive_control(
            'price_padding',
            [
                'label'      => __( 'Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-product-card .bw-wallpost-price, {{WRAPPER}} .bw-product-card .bw-slider-price, {{WRAPPER}} .bw-product-card .price' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Navigation Arrows
        $this->start_controls_section(
            'section_style_navigation',
            [
                'label' => __( 'Navigation Arrows', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => __( 'Arrow Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-arrow' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_size',
            [
                'label'      => __( 'Arrow Size', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 10,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-arrow' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_padding',
            [
                'label'      => __( 'Arrow Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-arrow' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrows_vertical_offset',
            [
                'label'      => __( 'Vertical Offset from Bottom', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => -50,
                        'max'  => 200,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => -40,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-arrows-container' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrows_horizontal_offset',
            [
                'label'      => __( 'Horizontal Offset from Right', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 200,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-arrows-container' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrows_gap',
            [
                'label'      => __( 'Gap Between Arrows', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-arrows-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Dots
        $this->start_controls_section(
            'section_style_dots',
            [
                'label' => __( 'Dots (Pagination)', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dots_color',
            [
                'label'     => __( 'Dots Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(0, 0, 0, 0.3)',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-dots-list li button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dots_active_color',
            [
                'label'     => __( 'Active Dot Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-ps-dots-list li.is-active button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dots_size',
            [
                'label'      => __( 'Dots Size', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 4,
                        'max'  => 30,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-dots-list li button' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'dots_position',
            [
                'label'   => __( 'Position', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'left'   => __( 'Left', 'bw-elementor-widgets' ),
                    'center' => __( 'Center', 'bw-elementor-widgets' ),
                    'right'  => __( 'Right', 'bw-elementor-widgets' ),
                ],
                'default' => 'center',
            ]
        );

        $this->add_control(
            'dots_vertical_offset',
            [
                'label'      => __( 'Vertical Offset from Bottom', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => -50,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => -25,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-ps-dots-container' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style → Card Overlay Buttons
        $this->start_controls_section(
            'section_style_card_buttons',
            [
                'label' => __( 'Card Overlay Buttons', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_buttons_bg',
            [
                'label'     => __( 'Background', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-card-overlay-buttons-background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_buttons_color',
            [
                'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-card-overlay-buttons-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'card_buttons_typography',
                'label'    => __( 'Typography', 'bw-elementor-widgets' ),
                'selector' => '{{WRAPPER}} .bw-product-card .overlay-button__label, {{WRAPPER}} .bw-product-card .bw-wallpost-overlay-button__label',
                'fields_options' => [
                    'font_size' => [
                        'tablet_default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                        'mobile_default' => [
                            'size' => 12,
                            'unit' => 'px',
                        ],
                    ],
                    'line_height' => [
                        'tablet_default' => [
                            'size' => 1.2,
                            'unit' => 'em',
                        ],
                        'mobile_default' => [
                            'size' => 1.2,
                            'unit' => 'em',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'card_buttons_bg_hover',
            [
                'label'     => __( 'Background (Hover)', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#80FD03',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-card-overlay-buttons-background-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_buttons_color_hover',
            [
                'label'     => __( 'Text Color (Hover)', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-card-overlay-buttons-color-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_buttons_radius',
            [
                'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => '--bw-card-overlay-buttons-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_buttons_padding',
            [
                'label'      => __( 'Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'default'    => [
                    'top'    => 12,
                    'right'  => 12,
                    'bottom' => 12,
                    'left'   => 12,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => '--bw-card-overlay-buttons-padding-top: {{TOP}}{{UNIT}}; --bw-card-overlay-buttons-padding-right: {{RIGHT}}{{UNIT}}; --bw-card-overlay-buttons-padding-bottom: {{BOTTOM}}{{UNIT}}; --bw-card-overlay-buttons-padding-left: {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings  = $this->get_settings_for_display();
        $widget_id = $this->get_id();

        // ── Build WP_Query args ───────────────────────────────────────────
        $args = [
            'post_type'              => ! empty( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product',
            'posts_per_page'         => ! empty( $settings['posts_per_page'] ) ? absint( $settings['posts_per_page'] ) : 12,
            'post_status'            => 'publish',
            'orderby'                => ! empty( $settings['order_by'] ) ? $settings['order_by'] : 'date',
            // Performance: slider has no pagination → skip SQL_CALC_FOUND_ROWS
            'no_found_rows'          => true,
            // update_post_meta_cache and update_post_term_cache are left at their
            // defaults (true): the card renderer calls get_post_meta() for hover
            // images/videos, and WooCommerce reads product-type terms via taxonomy
            // (get_the_terms($id, 'product_type')). Letting WP prime both caches
            // in one batch query each is faster than N individual DB lookups per card.
        ];

        if ( $args['orderby'] !== 'rand' ) {
            $args['order'] = ! empty( $settings['order'] ) ? $settings['order'] : 'DESC';
        }

        // Specific IDs override everything else
        if ( ! empty( $settings['specific_ids'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $settings['specific_ids'] ) ) );
            if ( ! empty( $ids ) ) {
                $args['post__in']        = $ids;
                $args['posts_per_page']  = count( $ids );
                $args['orderby']         = 'post__in';
            }
        } elseif ( $args['post_type'] === 'product' ) {
            // Category filtering
            $tax_query = [];

            if ( ! empty( $settings['subcategory'] ) ) {
                $subcats = is_array( $settings['subcategory'] ) ? $settings['subcategory'] : [ $settings['subcategory'] ];
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => array_map( 'absint', $subcats ),
                    'operator' => 'IN',
                ];
            } elseif ( ! empty( $settings['parent_category'] ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ absint( $settings['parent_category'] ) ],
                    'include_children' => true,
                    'operator' => 'IN',
                ];
            }

            if ( ! empty( $tax_query ) ) {
                $args['tax_query'] = $tax_query;
            }
        }

        // ── Transient cache ───────────────────────────────────────────────
        // Skip for random order (must vary each request) and Elementor editor
        // (must reflect live data during editing).
        $use_cache = ( 'rand' !== $args['orderby'] )
            && ! \Elementor\Plugin::$instance->editor->is_edit_mode();

        $posts     = null;
        $cache_key = '';

        if ( $use_cache ) {
            // Key covers only query-affecting args; excludes WP_Query performance flags.
            $key_data  = [
                'v'              => 1, // bump to force-invalidate all sliders on schema change
                'post_type'      => $args['post_type'],
                'posts_per_page' => $args['posts_per_page'],
                'orderby'        => $args['orderby'],
                'order'          => $args['order'] ?? 'DESC',
                'post__in'       => $args['post__in'] ?? [],
                'tax_query'      => $args['tax_query'] ?? [],
            ];
            $cache_key = 'bw_ps_' . md5( wp_json_encode( $key_data ) );
            $cached    = get_transient( $cache_key );

            if ( is_array( $cached ) && ! empty( $cached ) ) {
                // Rebuild post objects from IDs — WordPress object cache usually
                // has them already, so this is typically zero extra SQL.
                $posts = array_values( array_filter( array_map( 'get_post', $cached ) ) );
            }
        }

        if ( null === $posts ) {
            $query = new WP_Query( $args );
            $posts = $query->posts;
            wp_reset_postdata();

            if ( $use_cache && ! empty( $posts ) ) {
                set_transient( $cache_key, wp_list_pluck( $posts, 'ID' ), 5 * MINUTE_IN_SECONDS );
            }
        }

        if ( empty( $posts ) ) {
            echo '<p>' . esc_html__( 'No products found.', 'bw-elementor-widgets' ) . '</p>';
            return;
        }

        // ── JS config ─────────────────────────────────────────────────────
        $config = [
            'widgetId'    => $widget_id,
            'dotsPosition' => $settings['dots_position'] ?? 'center',
            'horizontal'  => [
                'infinite'        => ( $settings['infinite_loop'] ?? 'yes' ) === 'yes',
                'autoplay'        => ( $settings['autoplay'] ?? '' ) === 'yes',
                'autoplaySpeed'   => 3000,
                'pauseOnHover'    => true,
                'dragFree'        => ( $settings['drag_free'] ?? 'yes' ) === 'yes',
                'enableTouchDrag' => ( $settings['touch_drag'] ?? 'yes' ) === 'yes',
                'enableMouseDrag' => ( $settings['mouse_drag'] ?? 'yes' ) === 'yes',
                'align'           => $settings['slide_align'] ?? 'start',
                'responsive'      => $this->build_responsive_config( $settings ),
            ],
        ];

        $this->add_render_attribute( 'wrapper', [
            'class'          => 'bw-product-slider-wrapper',
            'data-widget-id' => esc_attr( $widget_id ),
            'data-config'    => wp_json_encode( $config ),
        ] );

        ?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <?php $this->render_horizontal_layout( $posts, $settings ); ?>
        </div>
        <?php
    }

    /**
     * Render the Embla horizontal carousel with product cards.
     */
    protected function render_horizontal_layout( $posts, $settings ) {
        $dots_position   = $settings['dots_position'] ?? 'center';
        $image_size = ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'large';

        // Eager-load the slides visible at the largest breakpoint; lazy-load the rest.
        $default_eager = 4;
        if ( ! empty( $settings['breakpoints'] ) ) {
            $bps_sorted = $settings['breakpoints'];
            usort( $bps_sorted, fn( $a, $b ) => absint( $b['breakpoint'] ) - absint( $a['breakpoint'] ) );
            $default_eager = max( 1, absint( $bps_sorted[0]['slides_to_show'] ?? 4 ) );
        }

        // Card settings passed to BW_Product_Card_Component::render()
        $card_settings_base = [
            'image_size'         => $image_size,
            'image_mode'         => 'cover',
            'show_title'         => ( $settings['show_title'] ?? 'yes' ) === 'yes',
            'show_description'   => ( $settings['show_description'] ?? '' ) === 'yes',
            'show_price'         => ( $settings['show_price'] ?? 'yes' ) === 'yes',
            'show_lowest_variation_price_only' => ( $settings['show_lowest_variation_price_only'] ?? '' ) === 'yes',
            'show_buttons'       => ( $settings['show_buttons'] ?? 'yes' ) === 'yes',
            'overlay_classes'    => ( $settings['show_buttons_mobile'] ?? '' ) === 'yes' ? '' : 'bw-ps-overlay-mobile-hidden',
            'hover_image_source'  => $settings['hover_image_source'] ?? 'meta',
            'open_cart_popup'     => false,
            'hover_image_loading' => 'lazy',
        ];

        // Breakpoint-responsive CSS (slide sizes + arrow/dot visibility)
        $this->render_breakpoint_css( $settings );

        ?>
        <div class="bw-ps-horizontal">
            <!-- Embla viewport: overflow:hidden -->
            <div class="bw-embla-viewport bw-ps-embla-viewport">
                <!-- Embla container: display:flex -->
                <div class="bw-embla-container">
                    <?php foreach ( $posts as $index => $post ) :
                        $is_eager = ( $index < $default_eager );
                        $card_s   = $card_settings_base;
                        $card_s['image_loading']      = $is_eager ? 'eager' : 'lazy';
                        // First slide is the LCP candidate: fetchpriority=high tells
                        // the browser to fetch it before other eager images.
                        $card_s['image_fetchpriority'] = ( 0 === $index ) ? 'high' : '';
                    ?>
                        <div class="bw-embla-slide bw-ps-slide"
                             data-bw-index="<?php echo esc_attr( $index ); ?>">
                            <?php echo BW_Product_Card_Component::render( $post->ID, $card_s ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bw-ps-arrows-container">
                <button class="bw-ps-arrow bw-ps-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'bw-elementor-widgets' ); ?>">&#8592;</button>
                <button class="bw-ps-arrow bw-ps-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'bw-elementor-widgets' ); ?>">&#8594;</button>
            </div>

            <!-- Container dots: BWEmblaCore injects <ul> here -->
            <div class="bw-ps-dots-container bw-ps-dots-<?php echo esc_attr( $dots_position ); ?>"></div>
        </div>
        <?php
    }

    /**
     * Emit scoped <style> with breakpoint CSS:
     * - Base (no @media): default slide size + arrows visible / dots hidden
     * - Per breakpoint @media max-width: slide size, arrow/dot visibility
     *
     * Ordered largest → smallest for correct CSS cascade.
     */
    protected function render_breakpoint_css( $settings ) {
        $widget_id  = $this->get_id();
        $el_prefix  = '.elementor-element-' . esc_attr( $widget_id );
        $sel_slide  = $el_prefix . ' .bw-ps-slide';
        $sel_arrows = $el_prefix . ' .bw-ps-arrows-container';
        $sel_dots   = $el_prefix . ' .bw-ps-dots-container';

        // Breakpoints: largest → smallest
        $breakpoints = ! empty( $settings['breakpoints'] ) ? $settings['breakpoints'] : [];
        if ( ! empty( $breakpoints ) ) {
            usort( $breakpoints, function ( $a, $b ) {
                return absint( $b['breakpoint'] ) - absint( $a['breakpoint'] );
            } );

            // The largest breakpoint becomes the base rule (no media query).
            // This ensures the settings apply on any screen wider than that breakpoint too.
            $top           = $breakpoints[0];
            $top_slides    = max( 1, absint( $top['slides_to_show'] ?? 1 ) );
            $top_var       = ( $top['variable_width'] ?? '' ) === 'yes';
            $top_sw        = absint( $top['slide_width'] ?? 0 );
            $top_peek      = absint( $top['peek_amount'] ?? 0 );
            $top_arrows    = ( $top['show_arrows'] ?? 'yes' ) === 'yes';
            $top_dots      = ( $top['show_dots'] ?? '' ) === 'yes';

            if ( $top_var ) {
                $base_slide_size = 'auto';
            } elseif ( $top_sw > 0 ) {
                $base_slide_size = $top_sw . 'px';
            } elseif ( $top_peek > 0 ) {
                $base_slide_size = 'calc((100% - ' . $top_peek . 'px) / ' . $top_slides . ')';
            } elseif ( $top_slides > 1 ) {
                $base_slide_size = 'calc(100% / ' . $top_slides . ')';
            } else {
                $base_slide_size = '100%';
            }

            $css  = '<style>';
            $css .= $sel_slide  . '{flex:0 0 ' . $base_slide_size . ';}';
            $css .= $sel_arrows . '{display:' . ( $top_arrows ? 'flex' : 'none' ) . ';}';
            $css .= $sel_dots   . '{display:' . ( $top_dots   ? 'flex' : 'none' ) . ';}';

            foreach ( $breakpoints as $bp ) {
                $bp_px          = absint( $bp['breakpoint'] );
                $slides_to_show = max( 1, absint( $bp['slides_to_show'] ?? 1 ) );
                $variable_width = ( $bp['variable_width'] ?? '' ) === 'yes';
                $slide_width    = absint( $bp['slide_width'] ?? 0 );
                $peek           = absint( $bp['peek_amount'] ?? 0 );
                $show_arrows    = ( $bp['show_arrows'] ?? 'yes' ) === 'yes';
                $show_dots      = ( $bp['show_dots'] ?? '' ) === 'yes';

                if ( $bp_px <= 0 ) {
                    continue;
                }

                if ( $variable_width ) {
                    $slide_size = 'auto';
                } elseif ( $slide_width > 0 ) {
                    $slide_size = $slide_width . 'px';
                } elseif ( $peek > 0 ) {
                    $slide_size = 'calc((100% - ' . $peek . 'px) / ' . $slides_to_show . ')';
                } elseif ( $slides_to_show > 1 ) {
                    $slide_size = 'calc(100% / ' . $slides_to_show . ')';
                } else {
                    $slide_size = '100%';
                }

                $css .= '@media (max-width:' . $bp_px . 'px){';
                $css .= $sel_slide  . '{flex:0 0 ' . $slide_size . ';}';
                $css .= $sel_arrows . '{display:' . ( $show_arrows ? 'flex' : 'none' ) . ';}';
                $css .= $sel_dots   . '{display:' . ( $show_dots   ? 'flex' : 'none' ) . ';}';
                $css .= '}';
            }
        } else {
            // No breakpoints configured: fall back to hardcoded defaults.
            $css  = '<style>';
            $css .= $sel_slide  . '{flex:0 0 calc(100% / 4);}';
            $css .= $sel_arrows . '{display:flex;}';
            $css .= $sel_dots   . '{display:none;}';
        }

        $css .= '</style>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $css;
    }

    /**
     * Build responsive config for JS (Embla reInit on breakpoint change).
     * slidesToShow is handled by CSS in render_breakpoint_css().
     */
    protected function build_responsive_config( $settings ) {
        $responsive = [];

        if ( ! empty( $settings['breakpoints'] ) ) {
            foreach ( $settings['breakpoints'] as $breakpoint ) {
                $responsive[] = [
                    'breakpoint'     => absint( $breakpoint['breakpoint'] ),
                    'slidesToScroll' => max( 1, absint( $breakpoint['slides_to_scroll'] ?? 1 ) ),
                    'centerMode'     => ( $breakpoint['center_mode'] ?? '' ) === 'yes',
                    'variableWidth'  => ( $breakpoint['variable_width'] ?? '' ) === 'yes',
                    'peek'           => absint( $breakpoint['peek_amount'] ?? 0 ),
                ];
            }
        }

        return $responsive;
    }
}
