<?php
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Slide_Showcase extends Widget_Base {

    public function get_name() {
        return 'bw-slide-showcase';
    }

    public function get_title() {
        return 'BW Slide Showcase';
    }

    public function get_icon() {
        return 'eicon-slider-device';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'slick-js', 'bw-slick-slider-js' ];
    }

    public function get_style_depends() {
        return [ 'slick-css', 'bw-slide-showcase-style' ];
    }

    protected function register_controls() {
        $this->register_query_controls();
        $this->register_layout_controls();
        $this->register_image_controls();
        $this->register_slider_controls();
        $this->register_style_controls();
    }

    private function register_query_controls() {
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'bw-elementor-widgets' ),
        ] );

        $post_type_options = $this->get_post_type_options();
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
            'product_cat_parent',
            [
                'label'       => __( 'Categoria Padre', 'bw' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => false,
                'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : [],
                'condition'   => [ 'post_type' => 'product' ],
            ]
        );

        $this->add_control(
            'product_type',
            [
                'label'     => __( 'Product Type', 'bw' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    ''          => __( 'All', 'bw' ),
                    'simple'    => __( 'Simple', 'bw' ),
                    'variable'  => __( 'Variable', 'bw' ),
                    'grouped'   => __( 'Grouped', 'bw' ),
                    'external'  => __( 'External', 'bw' ),
                    'on_sale'   => __( 'On Sale', 'bw' ),
                    'featured'  => __( 'Featured', 'bw' ),
                ],
                'default'   => '',
                'condition' => [ 'post_type' => 'product' ],
            ]
        );

        $this->add_control( 'include_ids', [
            'label'       => __( 'ID specifici', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __( 'es. 12, 45, 78', 'bw-elementor-widgets' ),
            'description' => __( 'Inserisci gli ID separati da virgola.', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();
    }

    private function register_layout_controls() {
        $this->start_controls_section( 'layout_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
        ] );

        $column_options = [];
        foreach ( range( 1, 6 ) as $column ) {
            $column_options[ $column ] = (string) $column;
        }

        $this->add_control( 'columns', [
            'label'   => __( 'Numero colonne', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $column_options,
            'default' => '1',
        ] );

        // NOTA: Solo 'px' supportato per evitare problemi di layout con Slick variableWidth.
        // Le percentuali causano calcoli errati e le colonne vanno in wrapping verticale.
        $this->add_control( 'column_width', [
            'label'      => __( 'Larghezza colonna', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 100, 'max' => 1200, 'step' => 1 ],
            ],
            'render_type' => 'template',
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-slider' => '--bw-slide-showcase-column-width: {{SIZE}}{{UNIT}}; --bw-column-width: {{SIZE}}{{UNIT}};',
            ],
            'description' => __( 'Imposta la larghezza base degli elementi della vetrina (solo px). Valori responsive vanno impostati nei breakpoints.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_height', [
            'label'      => __( 'Altezza colonna', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%', 'vh' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 1200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                'vh' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
            ],
            'default'    => [
                'size' => 420,
                'unit' => 'px',
            ],
            'render_type' => 'template',
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-slider' => '--bw-slide-showcase-image-height: {{SIZE}}{{UNIT}}; --bw-image-height: {{SIZE}}{{UNIT}};',
            ],
            'description' => __( 'Imposta l\'altezza base delle colonne. Valori responsive vanno impostati nei breakpoints.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'gap', [
            'label' => __( 'Spazio tra colonne (px)', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 120, 'step' => 1 ],
            ],
            'default' => [ 'size' => 24, 'unit' => 'px' ],
        ] );

        $this->add_responsive_control( 'left_offset', [
            'label'       => __( 'Left Offset', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => [ 'px', '%' ],
            'range'       => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
            ],
            'default'     => [ 'size' => 30, 'unit' => 'px' ],
            'render_type' => 'template',
            'selectors'   => [
                '{{WRAPPER}} .bw-slide-showcase-slider' => 'padding-left: {{SIZE}}{{UNIT}};',
            ],
            'description' => __( 'Distanza dal bordo sinistro dello schermo. Default: 30px', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'layout_settings_heading', [
            'label'     => __( 'Settings', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_responsive_control( 'column_padding', [
            'label'      => __( 'Padding colonna', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'default'    => [
                'top'    => 50,
                'right'  => 50,
                'bottom' => 50,
                'left'   => 50,
                'unit'   => 'px',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-item' => '--bw-slide-showcase-padding-top: {{TOP}}{{UNIT}}; --bw-slide-showcase-padding-right: {{RIGHT}}{{UNIT}}; --bw-slide-showcase-padding-bottom: {{BOTTOM}}{{UNIT}}; --bw-slide-showcase-padding-left: {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'image_padding', [
            'label'      => __( 'Padding immagine', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    private function register_image_controls() {
        $this->start_controls_section( 'images_section', [
            'label' => __( 'Immagini', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_crop', [
            'label'        => __( 'Ritaglio proporzioni', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_responsive_control( 'border_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'default'    => [
                'top' => 16,
                'right' => 16,
                'bottom' => 16,
                'left' => 16,
                'unit' => 'px',
                'isLinked' => true,
            ],
        ] );

        $this->end_controls_section();
    }

    private function register_slider_controls() {
        $this->start_controls_section( 'slider_section', [
            'label' => __( 'Slider Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'infinite', [
            'label'        => __( 'Infinite', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'slides_to_scroll', [
            'label'   => __( 'Slides To Scroll', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'default' => 1,
        ] );

        $this->add_control( 'autoplay', [
            'label'        => __( 'Autoplay', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'autoplay_speed', [
            'label'   => __( 'Autoplay Speed (ms)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 100,
            'step'    => 100,
            'default' => 3000,
        ] );

        $this->add_control( 'speed', [
            'label'   => __( 'Transition Speed (ms)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 100,
            'step'    => 50,
            'default' => 500,
        ] );

        $this->add_control( 'arrows', [
            'label'        => __( 'Arrows', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'dots', [
            'label'        => __( 'Dots', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'fade', [
            'label'        => __( 'Fade', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'center_mode', [
            'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'variable_width', [
            'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'adaptive_height', [
            'label'        => __( 'Adaptive Height', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'pause_on_hover', [
            'label'        => __( 'Pause On Hover', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control(
            'custom_cursor_svg',
            [
                'label'       => __( 'Custom Cursor SVG', 'bw' ),
                'type'        => Controls_Manager::MEDIA,
                'media_types' => [ 'image', 'svg' ],
                'description' => __( 'Upload a custom SVG to use as cursor while dragging the slide. Leave empty to use default hand cursor.', 'bw' ),
            ]
        );

        $this->add_control(
            'cursor_hotspot',
            [
                'label'       => __( 'Cursor Hotspot (x,y)', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '16 16',
                'description' => __( 'Adjust the cursor anchor point (x y in px).', 'bw' ),
                'condition'   => [ 'custom_cursor_svg[url]!' => '' ],
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control( 'breakpoint', [
            'label'   => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 320,
            'default' => 1024,
        ] );

        $column_options = [];
        foreach ( range( 1, 6 ) as $column ) {
            $column_options[ $column ] = (string) $column;
        }

        $repeater->add_control( 'slides_to_show', [
            'label'   => __( 'Slides To Show', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $column_options,
            'default' => '1',
        ] );

        $repeater->add_control( 'slides_to_scroll', [
            'label'   => __( 'Slides To Scroll', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'default' => 1,
        ] );

        $repeater->add_control( 'responsive_infinite', [
            'label'        => __( 'Infinite', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $repeater->add_control( 'responsive_dots', [
            'label'        => __( 'Dots', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $repeater->add_control( 'responsive_arrows', [
            'label'        => __( 'Arrows', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $repeater->add_control( 'responsive_center_mode', [
            'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $repeater->add_control( 'responsive_variable_width', [
            'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'responsive', [
            'label'       => __( 'Responsive', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => __( 'Breakpoint: {{{ breakpoint }}}px', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();

        // Nuova sezione Responsive Slide
        $this->start_controls_section( 'responsive_slide_section', [
            'label' => __( 'Responsive Slide', 'bw-elementor-widgets' ),
        ] );

        $responsive_repeater = new Repeater();

        $responsive_repeater->add_control( 'breakpoint', [
            'label'       => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 320,
            'max'         => 2560,
            'default'     => 768,
            'description' => __( 'Larghezza massima dello schermo per questo breakpoint (max-width)', 'bw-elementor-widgets' ),
        ] );

        $responsive_repeater->add_control( 'slides_to_show', [
            'label'   => __( 'Slides To Show', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $column_options,
            'default' => '1',
        ] );

        $responsive_repeater->add_control( 'slides_to_scroll', [
            'label'   => __( 'Slides To Scroll', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'default' => 1,
        ] );

        $responsive_repeater->add_control( 'responsive_infinite', [
            'label'        => __( 'Infinite', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $responsive_repeater->add_control( 'responsive_dots', [
            'label'        => __( 'Dots', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $responsive_repeater->add_control( 'responsive_arrows', [
            'label'        => __( 'Arrows', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $responsive_repeater->add_control( 'responsive_center_mode', [
            'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $responsive_repeater->add_control( 'responsive_variable_width', [
            'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $responsive_repeater->add_control( 'responsive_width', [
            'label'       => __( 'Larghezza Colonna', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => [ 'px' ],
            'range'       => [
                'px' => [ 'min' => 100, 'max' => 1200, 'step' => 1 ],
            ],
            'description' => __( 'Imposta la larghezza delle colonne per questo breakpoint. Lascia vuoto per usare la larghezza predefinita.', 'bw-elementor-widgets' ),
        ] );

        $responsive_repeater->add_control( 'responsive_height', [
            'label'       => __( 'Altezza Colonna', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => [ 'px', '%', 'vh' ],
            'range'       => [
                'px' => [ 'min' => 0, 'max' => 1200, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                'vh' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
            ],
            'description' => __( 'Imposta l\'altezza delle colonne per questo breakpoint. Lascia vuoto per usare l\'altezza predefinita.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'responsive_slides', [
            'label'       => __( 'Breakpoints Responsive', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $responsive_repeater->get_controls(),
            'title_field' => __( 'Breakpoint: {{breakpoint}}px - {{slides_to_show}} slides', 'bw-elementor-widgets' ),
            'default'     => [
                [
                    'breakpoint'                => 1024,
                    'slides_to_show'            => '1',
                    'slides_to_scroll'          => 1,
                    'responsive_infinite'       => 'yes',
                    'responsive_dots'           => '',
                    'responsive_arrows'         => 'yes',
                    'responsive_center_mode'    => '',
                    'responsive_variable_width' => '',
                ],
                [
                    'breakpoint'                => 768,
                    'slides_to_show'            => '1',
                    'slides_to_scroll'          => 1,
                    'responsive_infinite'       => 'yes',
                    'responsive_dots'           => '',
                    'responsive_arrows'         => 'yes',
                    'responsive_center_mode'    => '',
                    'responsive_variable_width' => '',
                ],
                [
                    'breakpoint'                => 480,
                    'slides_to_show'            => '1',
                    'slides_to_scroll'          => 1,
                    'responsive_infinite'       => 'yes',
                    'responsive_dots'           => '',
                    'responsive_arrows'         => '',
                    'responsive_center_mode'    => '',
                    'responsive_variable_width' => '',
                ],
            ],
            'description' => __( 'Configura i breakpoint per adattare lo slider a diverse dimensioni dello schermo. I breakpoint vengono applicati con max-width.', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();

        // Nuova sezione Animation Slide Loading
        $this->start_controls_section( 'animation_loading_section', [
            'label' => __( 'Animation Slide Loading', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'loading_animation_type', [
            'label'   => __( 'Tipo Animazione', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'fade'       => __( 'Fade Only', 'bw-elementor-widgets' ),
                'fade-left'  => __( 'Fade + Entrata da Sinistra', 'bw-elementor-widgets' ),
                'fade-right' => __( 'Fade + Entrata da Destra', 'bw-elementor-widgets' ),
            ],
            'default' => 'fade',
            'description' => __( 'Seleziona il tipo di animazione per il caricamento delle slide.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'loading_animation_easing', [
            'label'   => __( 'Easing Animazione', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'linear'        => __( 'Linear', 'bw-elementor-widgets' ),
                'ease'          => __( 'Ease', 'bw-elementor-widgets' ),
                'ease-in'       => __( 'Ease In', 'bw-elementor-widgets' ),
                'ease-out'      => __( 'Ease Out', 'bw-elementor-widgets' ),
                'ease-in-out'   => __( 'Ease In Out', 'bw-elementor-widgets' ),
                'ease-in-quad'  => __( 'Ease In Quad', 'bw-elementor-widgets' ),
                'ease-out-quad' => __( 'Ease Out Quad', 'bw-elementor-widgets' ),
                'ease-in-cubic' => __( 'Ease In Cubic', 'bw-elementor-widgets' ),
                'ease-out-cubic'=> __( 'Ease Out Cubic', 'bw-elementor-widgets' ),
            ],
            'default' => 'ease-out',
            'description' => __( 'Seleziona la curva di easing per l\'animazione.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'loading_animation_duration', [
            'label'   => __( 'Durata Animazione (ms)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 100, 'max' => 2000, 'step' => 50 ],
            ],
            'default' => [ 'size' => 500, 'unit' => 'px' ],
            'description' => __( 'Durata dell\'animazione in millisecondi.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'loading_animation_mode', [
            'label'        => __( 'Animazione Sequenziale (Matrioska)', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'On', 'bw-elementor-widgets' ),
            'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Se attivo, le slide appariranno una dopo l\'altra con un leggero ritardo. Se disattivo, appariranno tutte insieme.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'loading_animation_stagger_delay', [
            'label'     => __( 'Ritardo tra Slide (ms)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SLIDER,
            'range'     => [
                'px' => [ 'min' => 0, 'max' => 500, 'step' => 10 ],
            ],
            'default'   => [ 'size' => 50, 'unit' => 'px' ],
            'condition' => [ 'loading_animation_mode' => 'yes' ],
            'description' => __( 'Ritardo tra l\'apparizione di ogni slide quando l\'animazione sequenziale è attiva.', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section( 'title_style_section', [
            'label' => __( 'Titolo', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_color', [
            'label'     => __( 'Colore', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-content .bw-title' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .bw-slide-showcase-content .bw-title',
        ] );

        $this->add_responsive_control( 'title_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-content .bw-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'subtitle_style_section', [
            'label' => __( 'Sottotitolo', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'subtitle_color', [
            'label'     => __( 'Colore', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-content .bw-subtitle' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'subtitle_typography',
            'selector' => '{{WRAPPER}} .bw-slide-showcase-content .bw-subtitle',
        ] );

        $this->add_responsive_control( 'subtitle_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-content .bw-subtitle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'info_style_section', [
            'label' => __( 'Informazioni', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'info_color', [
            'label'     => __( 'Colore', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-info, {{WRAPPER}} .bw-slide-showcase-info-item' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'info_typography',
            'selector' => '{{WRAPPER}} .bw-slide-showcase-info, {{WRAPPER}} .bw-slide-showcase-info-item',
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'content_style_section', [
            'label' => __( 'Colonna (immagine)', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_responsive_control( 'content_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'badge_style_section', [
            'label' => __( 'Badge', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'badge_color', [
            'label'     => __( 'Colore testo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-badge' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'badge_background_color', [
            'label'     => __( 'Colore sfondo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-badge' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'badge_typography',
            'selector' => '{{WRAPPER}} .bw-slide-showcase-badge',
        ] );

        $this->add_responsive_control( 'badge_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'button_style_section', [
            'label' => __( 'Bottone', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'button_typography',
            'selector' => '{{WRAPPER}} .bw-slide-showcase-view-btn',
        ] );

        $this->add_responsive_control( 'button_padding', [
            'label'      => __( 'Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-view-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'button_border_radius', [
            'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'default'    => [
                'top'    => 8,
                'right'  => 8,
                'bottom' => 8,
                'left'   => 8,
                'unit'   => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-slide-showcase-view-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->start_controls_tabs( 'button_style_tabs' );

        $this->start_controls_tab( 'button_style_normal', [
            'label' => __( 'Normale', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'button_text_color', [
            'label'     => __( 'Colore testo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-view-btn' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'button_background_color', [
            'label'     => __( 'Colore sfondo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-view-btn' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->start_controls_tab( 'button_style_hover', [
            'label' => __( 'Hover', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'button_text_color_hover', [
            'label'     => __( 'Colore testo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-view-btn:hover' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'button_background_color_hover', [
            'label'     => __( 'Colore sfondo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-slide-showcase-view-btn:hover' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        $this->start_controls_section(
            'section_arrows_style',
            [
                'label' => __( 'Navigation Arrows', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'arrows_color',
            [
                'label' => __( 'Arrow Color', 'bw' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-slide-showcase-slider .bw-slick-prev img, {{WRAPPER}} .bw-slide-showcase-slider .bw-slick-next img' => 'filter: brightness(0) saturate(100%) invert(0%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(0) contrast(100%) drop-shadow(0 0 0 {{VALUE}});',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_size',
            [
                'label' => __( 'Arrow Size', 'bw' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [ 'min' => 10, 'max' => 100 ],
                ],
                'default' => [
                    'size' => 30,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slide-showcase-slider .bw-slick-prev img, {{WRAPPER}} .bw-slide-showcase-slider .bw-slick-next img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_padding',
            [
                'label' => __( 'Arrow Padding', 'bw' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slide-showcase-slider .bw-slick-prev, {{WRAPPER}} .bw-slide-showcase-slider .bw-slick-next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_vertical_offset',
            [
                'label' => __( 'Vertical Offset', 'bw' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => -300, 'max' => 300, 'step' => 1 ],
                    '%'  => [ 'min' => -100, 'max' => 100, 'step' => 1 ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slide-showcase-slider .bw-slick-prev, {{WRAPPER}} .bw-slide-showcase-slider .bw-slick-next' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_prev_horizontal_offset',
            [
                'label' => __( 'Previous Arrow Horizontal Offset', 'bw' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => -300, 'max' => 300, 'step' => 1 ],
                    '%'  => [ 'min' => -100, 'max' => 100, 'step' => 1 ],
                ],
                'default' => [
                    'size' => 55,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slide-showcase-slider .bw-slick-prev' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_next_horizontal_offset',
            [
                'label' => __( 'Next Arrow Horizontal Offset', 'bw' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => -300, 'max' => 300, 'step' => 1 ],
                    '%'  => [ 'min' => -100, 'max' => 100, 'step' => 1 ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-slide-showcase-slider .bw-slick-next' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings           = $this->get_settings_for_display();
        $columns            = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 1;
        $gap                = isset( $settings['gap']['size'] ) ? max( 0, absint( $settings['gap']['size'] ) ) : 0;
        $image_height_data  = $this->get_slider_value_with_unit( $settings, 'image_height', 420, 'px' );
        $image_height       = isset( $image_height_data['size'] ) ? max( 0, (float) $image_height_data['size'] ) : 0;
        $image_height_unit  = isset( $image_height_data['unit'] ) ? $image_height_data['unit'] : 'px';
        $image_crop         = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];
        $include_ids        = isset( $settings['include_ids'] ) ? $this->parse_ids( $settings['include_ids'] ) : [];
        $post_type          = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product';
        $product_type       = isset( $settings['product_type'] ) ? sanitize_key( $settings['product_type'] ) : '';
        $product_cat        = isset( $settings['product_cat_parent'] ) ? absint( $settings['product_cat_parent'] ) : 0;
        $slides_scroll      = isset( $settings['slides_to_scroll'] ) ? max( 1, absint( $settings['slides_to_scroll'] ) ) : 1;
        $column_width_data  = $this->get_slider_value_with_unit( $settings, 'column_width', null, 'px' );
        $column_width       = isset( $column_width_data['size'] ) ? $column_width_data['size'] : null;
        $column_width_unit  = isset( $column_width_data['unit'] ) ? $column_width_data['unit'] : 'px';
        $cursor_svg         = ! empty( $settings['custom_cursor_svg']['url'] ) ? esc_url_raw( $settings['custom_cursor_svg']['url'] ) : '';
        $cursor_hotspot     = '16 16';
        if ( $cursor_svg && ! empty( $settings['cursor_hotspot'] ) ) {
            $hotspot_values = preg_split( '/\s+/', sanitize_text_field( $settings['cursor_hotspot'] ) );
            if ( is_array( $hotspot_values ) && count( $hotspot_values ) >= 2 ) {
                $hotspot_x = is_numeric( $hotspot_values[0] ) ? absint( $hotspot_values[0] ) : 16;
                $hotspot_y = is_numeric( $hotspot_values[1] ) ? absint( $hotspot_values[1] ) : 16;
                $cursor_hotspot = $hotspot_x . ' ' . $hotspot_y;
            }
        }
        $available_post_types = $this->get_post_type_options();
        if ( empty( $available_post_types ) ) {
            $available_post_types = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        if ( ! array_key_exists( $post_type, $available_post_types ) ) {
            $post_type_keys = array_keys( $available_post_types );
            $post_type      = array_key_exists( 'product', $available_post_types ) ? 'product' : reset( $post_type_keys );
        }

        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

        if ( 'product' === $post_type ) {
            $tax_query = [];
            if ( $product_cat > 0 ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ $product_cat ],
                ];
            }

            if ( in_array( $product_type, [ 'simple', 'variable', 'grouped', 'external' ], true ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => [ $product_type ],
                ];
            } elseif ( 'featured' === $product_type ) {
                $tax_query[] = [
                    'taxonomy' => 'product_visibility',
                    'field'    => 'slug',
                    'terms'    => [ 'featured' ],
                ];
            } elseif ( 'on_sale' === $product_type ) {
                if ( function_exists( 'wc_get_product_ids_on_sale' ) ) {
                    $sale_ids = wc_get_product_ids_on_sale();
                    $sale_ids = array_map( 'absint', (array) $sale_ids );
                    $sale_ids = array_filter( $sale_ids );
                    if ( ! empty( $sale_ids ) ) {
                        if ( isset( $query_args['post__in'] ) ) {
                            $query_args['post__in'] = array_values( array_intersect( $query_args['post__in'], $sale_ids ) );
                        } else {
                            $query_args['post__in'] = $sale_ids;
                        }
                    } else {
                        $query_args['post__in'] = [ 0 ];
                    }
                }
            }

            if ( ! empty( $tax_query ) ) {
                $query_args['tax_query'] = $tax_query;
            }
        }

        if ( isset( $query_args['post__in'] ) && empty( $query_args['post__in'] ) ) {
            $query_args['post__in'] = [ 0 ];
        }

        $slider_settings = $this->prepare_slider_settings( $settings, $columns, $slides_scroll );
        $wrapper_classes = [ 'bw-slide-showcase-slider', 'bw-slick-slider' ];

        if ( ! $image_crop ) {
            $wrapper_classes[] = 'bw-slide-showcase--no-crop';
        }

        $wrapper_style  = '--bw-slide-showcase-gap:' . $gap . 'px;';
        $wrapper_style .= '--bw-gap:' . $gap . 'px;';
        $wrapper_style .= '--bw-slide-showcase-columns:' . $columns . ';';
        $wrapper_style .= '--bw-columns:' . $columns . ';';
        $has_custom_column_width = false;
        if ( null !== $column_width && '' !== $column_width && (float) $column_width > 0 ) {
            $wrapper_style          .= '--bw-slide-showcase-column-width:' . $column_width . $column_width_unit . ';';
            $wrapper_style          .= '--bw-column-width:' . $column_width . $column_width_unit . ';';
            $has_custom_column_width = true;
        } else {
            $wrapper_style .= '--bw-slide-showcase-column-width:auto;';
            $wrapper_style .= '--bw-column-width:auto;';
        }

        if ( $image_height > 0 ) {
            $wrapper_style .= '--bw-slide-showcase-image-height:' . $image_height . $image_height_unit . ';';
            $wrapper_style .= '--bw-image-height:' . $image_height . $image_height_unit . ';';
        } else {
            $wrapper_style .= '--bw-slide-showcase-image-height:auto;';
            $wrapper_style .= '--bw-image-height:auto;';
        }

        if ( $cursor_svg ) {
            $custom_cursor = 'cursor: url(\'' . $cursor_svg . '\') ' . $cursor_hotspot . ', ';
            $wrapper_style .= $custom_cursor . '-webkit-grab;';
            $wrapper_style .= $custom_cursor . 'grab;';
        } else {
            $wrapper_style .= 'cursor: -webkit-grab;';
            $wrapper_style .= 'cursor: grab;';
        }

        $slider_settings_json = ! empty( $slider_settings ) ? wp_json_encode( $slider_settings ) : '';
        if ( $slider_settings_json ) {
            $slider_settings_json = htmlspecialchars( $slider_settings_json, ENT_QUOTES, 'UTF-8' );
        }

        // Impostazioni animazione loading
        $loading_animation_type     = isset( $settings['loading_animation_type'] ) ? sanitize_key( $settings['loading_animation_type'] ) : 'fade';
        $loading_animation_easing   = isset( $settings['loading_animation_easing'] ) ? sanitize_key( $settings['loading_animation_easing'] ) : 'ease-out';
        $loading_animation_duration = isset( $settings['loading_animation_duration']['size'] ) ? max( 100, absint( $settings['loading_animation_duration']['size'] ) ) : 500;
        $loading_animation_mode     = isset( $settings['loading_animation_mode'] ) && 'yes' === $settings['loading_animation_mode'];
        $loading_animation_stagger  = isset( $settings['loading_animation_stagger_delay']['size'] ) ? max( 0, absint( $settings['loading_animation_stagger_delay']['size'] ) ) : 50;

        $query = new \WP_Query( $query_args );

        $border_radius_value = $this->format_dimensions( isset( $settings['border_radius'] ) ? $settings['border_radius'] : [] );
        $object_fit          = $image_crop ? 'cover' : 'contain';
        $button_text         = __( 'View Collection', 'bw-elementor-widgets' );
        ?>
        <div
            class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>"
            data-columns="<?php echo esc_attr( $columns ); ?>"
            <?php if ( $has_custom_column_width ) : ?>
                data-has-column-width="true"
            <?php endif; ?>
            <?php if ( $slider_settings_json ) : ?>
                data-slider-settings="<?php echo $slider_settings_json; ?>"
            <?php endif; ?>
            data-loading-animation-type="<?php echo esc_attr( $loading_animation_type ); ?>"
            data-loading-animation-easing="<?php echo esc_attr( $loading_animation_easing ); ?>"
            data-loading-animation-duration="<?php echo esc_attr( $loading_animation_duration ); ?>"
            data-loading-animation-mode="<?php echo esc_attr( $loading_animation_mode ? 'sequential' : 'simultaneous' ); ?>"
            data-loading-animation-stagger="<?php echo esc_attr( $loading_animation_stagger ); ?>"
            style="<?php echo esc_attr( $wrapper_style ); ?>"
        >
            <?php if ( $query->have_posts() ) : ?>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();

                    $post_id        = get_the_ID();
                    $permalink      = get_permalink( $post_id );
                    $product_title  = get_the_title( $post_id );
                    $image_url      = '';
                    $showcase_image = get_post_meta( $post_id, '_bw_showcase_image', true );
                    $image_id       = 0;
                    if ( empty( $showcase_image ) ) {
                        $legacy_image = get_post_meta( $post_id, '_product_showcase_image', true );
                        if ( $legacy_image ) {
                            $showcase_image = $legacy_image;
                        }
                    }

                    if ( $showcase_image ) {
                        if ( is_numeric( $showcase_image ) ) {
                            $image_id  = absint( $showcase_image );
                            $image_url = wp_get_attachment_url( $image_id );
                        } else {
                            $image_url = esc_url_raw( $showcase_image );
                        }
                    }

                    if ( ! $image_url && $image_id ) {
                        $image_url = wp_get_attachment_url( $image_id );
                    }

                    if ( ! $image_url && has_post_thumbnail( $post_id ) ) {
                        $image_url = get_the_post_thumbnail_url( $post_id, 'large' );
                    }

                    $showcase_title_meta  = trim( (string) get_post_meta( $post_id, '_bw_showcase_title', true ) );
                    $showcase_title       = '' !== $showcase_title_meta ? $showcase_title_meta : $product_title;
                    $showcase_description = trim( (string) get_post_meta( $post_id, '_bw_showcase_description', true ) );

                    $meta_assets_count = get_post_meta( $post_id, '_bw_assets_count', true );
                    if ( '' === $meta_assets_count ) {
                        $meta_assets_count = get_post_meta( $post_id, '_product_assets_count', true );
                    }

                    $meta_size_mb = get_post_meta( $post_id, '_bw_file_size', true );
                    if ( '' === $meta_size_mb ) {
                        $meta_size_mb = get_post_meta( $post_id, '_product_size_mb', true );
                    }

                    $meta_formats = get_post_meta( $post_id, '_bw_formats', true );
                    if ( '' === $meta_formats ) {
                        $meta_formats = get_post_meta( $post_id, '_product_formats', true );
                    }
                    $meta_product_type_raw = get_post_meta( $post_id, '_bw_product_type', true );
                    $product_type_value    = sanitize_key( $meta_product_type_raw );
                    if ( ! in_array( $product_type_value, [ 'digital', 'physical' ], true ) ) {
                        $product_type_value = 'digital';
                    }
                    $meta_info_1_raw = get_post_meta( $post_id, '_bw_info_1', true );
                    $meta_info_2_raw = get_post_meta( $post_id, '_bw_info_2', true );
                    $meta_button_text  = get_post_meta( $post_id, '_product_button_text', true );
                    $meta_button_link  = get_post_meta( $post_id, '_product_button_link', true );
                    $meta_color_value  = get_post_meta( $post_id, '_bw_texts_color', true );
                    if ( empty( $meta_color_value ) ) {
                        $meta_color_value = get_post_meta( $post_id, '_product_color', true );
                    }
                    $meta_color        = sanitize_hex_color( $meta_color_value );
                    if ( empty( $meta_color ) ) {
                        $meta_color = '#ffffff';
                    }

                    $btn_url = $permalink;
                    if ( ! empty( $meta_button_link ) ) {
                        $btn_url = esc_url( $meta_button_link );
                    }

                    $button_text_value = $button_text;
                    if ( ! empty( $meta_button_text ) ) {
                        $button_text_value = wp_strip_all_tags( $meta_button_text );
                    }

                    $link_attrs = '';

                    $item_styles = [];
                    if ( $border_radius_value ) {
                        $item_styles[] = 'border-radius: ' . $border_radius_value . ';';
                    }
                    if ( ! empty( $meta_color ) ) {
                        $item_styles[] = '--bw-slide-showcase-text-color: ' . $meta_color . ';';
                        $item_styles[] = '--bw-slide-showcase-badge-border-color: ' . $meta_color . ';';
                    }

                    $item_style_attr = '';
                    if ( ! empty( $item_styles ) ) {
                        $item_style_attr = ' style="' . esc_attr( implode( ' ', $item_styles ) ) . '"';
                    }

                    $assets_display = '';
                    if ( '' !== $meta_assets_count ) {
                        $assets_number = absint( $meta_assets_count );
                        if ( $assets_number > 0 ) {
                            $assets_label   = _n( 'Asset', 'Assets', $assets_number, 'bw-elementor-widgets' );
                            $assets_display = sprintf( '%d %s', $assets_number, $assets_label );
                        }
                    }

                    $size_display = '';
                    if ( '' !== $meta_size_mb ) {
                        $size_display = trim( wp_strip_all_tags( $meta_size_mb ) );
                        if ( '' !== $size_display && ! preg_match( '/[a-zA-Z]/', $size_display ) ) {
                            $size_display .= 'MB';
                        }
                    }

                    $format_badges = [];
                    if ( '' !== $meta_formats ) {
                        $raw_formats = explode( ',', $meta_formats );
                        foreach ( $raw_formats as $format ) {
                            $format = trim( wp_strip_all_tags( $format ) );
                            if ( '' !== $format ) {
                                $format_badges[] = $format;
                            }
                        }
                    }

                    $info_1_display   = '' !== $meta_info_1_raw ? trim( wp_strip_all_tags( $meta_info_1_raw ) ) : '';
                    $info_2_display   = '' !== $meta_info_2_raw ? trim( wp_strip_all_tags( $meta_info_2_raw ) ) : '';
                    $has_digital_info = ( 'digital' === $product_type_value ) && ( $assets_display || $size_display || ! empty( $format_badges ) );
                    $has_physical_info = ( 'physical' === $product_type_value ) && ( '' !== $info_1_display || '' !== $info_2_display );
                    $has_bottom_info   = $has_digital_info || $has_physical_info;
                    $has_cta           = ! empty( $btn_url ) && ! empty( $button_text_value );

                    ?>
                    <div class="bw-slide-showcase-slide">
                        <div class="bw-slide-showcase-item"<?php echo $item_style_attr; ?>>
                            <div class="bw-slide-showcase-media">
                                <?php if ( $image_url ) : ?>
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="bw-slide-showcase-image" loading="lazy" style="<?php echo $this->build_image_style( $image_height, $object_fit ); ?>">
                                <?php endif; ?>
                            </div>

                            <?php if ( $showcase_title || $showcase_description ) : ?>
                                <div class="bw-slide-showcase-content">
                                    <?php if ( $showcase_title ) : ?>
                                        <h2 class="bw-title"><?php echo esc_html( $showcase_title ); ?></h2>
                                    <?php endif; ?>
                                    <?php if ( $showcase_description ) : ?>
                                        <p class="bw-subtitle"><?php echo esc_html( $showcase_description ); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $has_bottom_info || $has_cta ) : ?>
                                <div class="bw-slide-showcase-bottom-section" style="color: <?php echo esc_attr( $meta_color ); ?>;">
                                    <?php if ( $has_bottom_info ) : ?>
                                        <div class="bw-slide-showcase-info">
                                            <?php if ( 'digital' === $product_type_value ) : ?>
                                                <?php if ( $assets_display ) : ?>
                                                    <div class="bw-slide-showcase-info-item"><?php echo esc_html( $assets_display ); ?></div>
                                                <?php endif; ?>
                                                <?php if ( $size_display ) : ?>
                                                    <div class="bw-slide-showcase-info-item"><?php echo esc_html( $size_display ); ?></div>
                                                <?php endif; ?>
                                                <?php if ( ! empty( $format_badges ) ) : ?>
                                                    <div class="bw-slide-showcase-badges">
                                                        <?php foreach ( $format_badges as $format_badge ) : ?>
                                                            <span class="bw-slide-showcase-badge"><?php echo esc_html( $format_badge ); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ( 'physical' === $product_type_value ) : ?>
                                                <div class="bw-slide-showcase-physical">
                                                    <?php if ( '' !== $info_1_display ) : ?>
                                                        <div class="bw-slide-showcase-info-item"><?php echo esc_html( $info_1_display ); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ( '' !== $info_2_display ) : ?>
                                                        <div class="bw-slide-showcase-info-item"><?php echo esc_html( $info_2_display ); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( $has_cta ) : ?>
                                        <div class="bw-slide-showcase-cta">
                                            <a href="<?php echo esc_url( $btn_url ); ?>" class="bw-slide-showcase-arrow"<?php echo $link_attrs; ?>
                                                aria-label="<?php echo esc_attr( $button_text_value ); ?>">
                                                <span aria-hidden="true">&rsaquo;</span>
                                            </a>
                                            <a href="<?php echo esc_url( $btn_url ); ?>" class="bw-slide-showcase-view-btn"<?php echo $link_attrs; ?>>
                                                <?php echo esc_html( $button_text_value ); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="bw-slide-showcase-placeholder">
                    <div class="bw-slide-showcase-placeholder__inner">
                        <?php esc_html_e( 'Nessun prodotto trovato.', 'bw-elementor-widgets' ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
    }

    protected function get_slider_value_with_unit( $settings, $control_id, $default_size = null, $default_unit = 'px' ) {
        if ( ! isset( $settings[ $control_id ] ) ) {
            return [
                'size' => $default_size,
                'unit' => $default_unit,
            ];
        }

        $value = $settings[ $control_id ];
        $size  = null;
        $unit  = $default_unit;

        if ( is_array( $value ) ) {
            if ( isset( $value['unit'] ) && '' !== $value['unit'] ) {
                $unit = $value['unit'];
            }

            if ( isset( $value['size'] ) && '' !== $value['size'] ) {
                $size = $value['size'];
            } elseif ( isset( $value['sizes'] ) && is_array( $value['sizes'] ) ) {
                foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
                    if ( isset( $value['sizes'][ $device ] ) && '' !== $value['sizes'][ $device ] ) {
                        $size = $value['sizes'][ $device ];
                        break;
                    }
                }
            }
        } elseif ( '' !== $value && null !== $value ) {
            $size = $value;
        }

        if ( null === $size ) {
            $size = $default_size;
        }

        if ( is_numeric( $size ) ) {
            $size = (float) $size;
        }

        return [
            'size' => $size,
            'unit' => $unit,
        ];
    }

    private function get_post_type_options() {
        $post_types = get_post_types(
            [
                'public' => true,
            ],
            'objects'
        );

        $options = [];

        if ( empty( $post_types ) || ! is_array( $post_types ) ) {
            return $options;
        }

        foreach ( $post_types as $post_type ) {
            if ( ! isset( $post_type->name ) ) {
                continue;
            }

            if ( 'attachment' === $post_type->name ) {
                continue;
            }

            $label = '';

            if ( isset( $post_type->labels->singular_name ) && '' !== $post_type->labels->singular_name ) {
                $label = $post_type->labels->singular_name;
            } elseif ( isset( $post_type->label ) && '' !== $post_type->label ) {
                $label = $post_type->label;
            } else {
                $label = ucfirst( $post_type->name );
            }

            $options[ $post_type->name ] = $label;
        }

        asort( $options );

        return $options;
    }

    private function parse_ids( $ids_string ) {
        if ( empty( $ids_string ) ) {
            return [];
        }

        $parts = array_filter( array_map( 'trim', explode( ',', $ids_string ) ) );
        $ids   = [];

        foreach ( $parts as $part ) {
            if ( is_numeric( $part ) ) {
                $ids[] = (int) $part;
            }
        }

        return array_unique( $ids );
    }

    private function prepare_slider_settings( $settings, $columns, $slides_scroll ) {
        $slider_settings = [
            'infinite'       => isset( $settings['infinite'] ) && 'yes' === $settings['infinite'],
            'slidesToShow'   => $columns,
            'slidesToScroll' => $slides_scroll,
            'autoplay'       => isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'],
            'autoplaySpeed'  => isset( $settings['autoplay_speed'] ) ? max( 100, absint( $settings['autoplay_speed'] ) ) : 3000,
            'speed'          => isset( $settings['speed'] ) ? max( 100, absint( $settings['speed'] ) ) : 500,
            'arrows'         => isset( $settings['arrows'] ) ? 'yes' === $settings['arrows'] : true,
            'dots'           => isset( $settings['dots'] ) && 'yes' === $settings['dots'],
            'fade'           => isset( $settings['fade'] ) && 'yes' === $settings['fade'],
            'centerMode'     => isset( $settings['center_mode'] ) && 'yes' === $settings['center_mode'],
            'variableWidth'  => isset( $settings['variable_width'] ) && 'yes' === $settings['variable_width'],
            'adaptiveHeight' => isset( $settings['adaptive_height'] ) && 'yes' === $settings['adaptive_height'],
            'pauseOnHover'   => isset( $settings['pause_on_hover'] ) ? 'yes' === $settings['pause_on_hover'] : true,
        ];

        $slider_settings['slidesToScroll'] = max( 1, min( $slider_settings['slidesToScroll'], $columns ) );

        $responsive = [];

        // Supporta sia i nuovi 'responsive_slides' che i vecchi 'responsive' per retrocompatibilità
        $responsive_source = ! empty( $settings['responsive_slides'] ) && is_array( $settings['responsive_slides'] )
            ? $settings['responsive_slides']
            : ( ! empty( $settings['responsive'] ) && is_array( $settings['responsive'] ) ? $settings['responsive'] : [] );

        if ( ! empty( $responsive_source ) ) {
            foreach ( $responsive_source as $item ) {
                if ( empty( $item['breakpoint'] ) ) {
                    continue;
                }

                $breakpoint = absint( $item['breakpoint'] );
                if ( $breakpoint <= 0 ) {
                    continue;
                }

                $item_settings = [];
                if ( ! empty( $item['slides_to_show'] ) ) {
                    $item_settings['slidesToShow'] = max( 1, absint( $item['slides_to_show'] ) );
                }

                if ( ! empty( $item['slides_to_scroll'] ) ) {
                    $item_settings['slidesToScroll'] = max( 1, absint( $item['slides_to_scroll'] ) );
                }

                if ( isset( $item['responsive_infinite'] ) ) {
                    $item_settings['infinite'] = 'yes' === $item['responsive_infinite'];
                }

                if ( isset( $item['responsive_dots'] ) ) {
                    $item_settings['dots'] = 'yes' === $item['responsive_dots'];
                }

                if ( isset( $item['responsive_arrows'] ) ) {
                    $item_settings['arrows'] = 'yes' === $item['responsive_arrows'];
                }

                if ( isset( $item['responsive_center_mode'] ) ) {
                    $item_settings['centerMode'] = 'yes' === $item['responsive_center_mode'];
                }

                if ( isset( $item['responsive_variable_width'] ) ) {
                    $item_settings['variableWidth'] = 'yes' === $item['responsive_variable_width'];
                }

                // Supporto per larghezza responsive
                if ( isset( $item['responsive_width'] ) && ! empty( $item['responsive_width'] ) ) {
                    $width_data = $item['responsive_width'];
                    $width_size = null;
                    $width_unit = 'px';

                    if ( is_array( $width_data ) ) {
                        if ( isset( $width_data['size'] ) && '' !== $width_data['size'] ) {
                            $width_size = $width_data['size'];
                        }
                        if ( isset( $width_data['unit'] ) && '' !== $width_data['unit'] ) {
                            $width_unit = $width_data['unit'];
                        }
                    } elseif ( is_numeric( $width_data ) ) {
                        $width_size = $width_data;
                    }

                    if ( null !== $width_size && '' !== $width_size ) {
                        $item_settings['responsiveWidth'] = [
                            'size' => (float) $width_size,
                            'unit' => $width_unit,
                        ];
                    }
                }

                // Supporto per altezza responsive
                if ( isset( $item['responsive_height'] ) && ! empty( $item['responsive_height'] ) ) {
                    $height_data = $item['responsive_height'];
                    $height_size = null;
                    $height_unit = 'px';

                    if ( is_array( $height_data ) ) {
                        if ( isset( $height_data['size'] ) && '' !== $height_data['size'] ) {
                            $height_size = $height_data['size'];
                        }
                        if ( isset( $height_data['unit'] ) && '' !== $height_data['unit'] ) {
                            $height_unit = $height_data['unit'];
                        }
                    } elseif ( is_numeric( $height_data ) ) {
                        $height_size = $height_data;
                    }

                    if ( null !== $height_size && '' !== $height_size ) {
                        $item_settings['responsiveHeight'] = [
                            'size' => (float) $height_size,
                            'unit' => $height_unit,
                        ];
                    }
                }

                if ( isset( $item_settings['slidesToShow'], $item_settings['slidesToScroll'] ) ) {
                    $item_settings['slidesToScroll'] = min( $item_settings['slidesToScroll'], $item_settings['slidesToShow'] );
                }

                if ( ! empty( $item_settings ) ) {
                    $responsive[] = [
                        'breakpoint' => $breakpoint,
                        'settings'   => $item_settings,
                    ];
                }
            }
        }

        if ( ! empty( $responsive ) ) {
            $slider_settings['responsive'] = $responsive;
        }

        return $slider_settings;
    }

    private function format_dimensions( $dimensions ) {
        if ( empty( $dimensions ) || ! is_array( $dimensions ) ) {
            return '';
        }

        $unit  = $this->sanitize_dimension_unit( isset( $dimensions['unit'] ) ? $dimensions['unit'] : '' );
        $top    = isset( $dimensions['top'] ) && '' !== $dimensions['top'] ? $dimensions['top'] . $unit : '';
        $right  = isset( $dimensions['right'] ) && '' !== $dimensions['right'] ? $dimensions['right'] . $unit : '';
        $bottom = isset( $dimensions['bottom'] ) && '' !== $dimensions['bottom'] ? $dimensions['bottom'] . $unit : '';
        $left   = isset( $dimensions['left'] ) && '' !== $dimensions['left'] ? $dimensions['left'] . $unit : '';

        $values = array_filter( [ $top, $right, $bottom, $left ], static function( $value ) {
            return '' !== $value;
        } );

        if ( empty( $values ) ) {
            return '';
        }

        if ( count( $values ) < 4 ) {
            $top    = $top ?: '0' . $unit;
            $right  = $right ?: '0' . $unit;
            $bottom = $bottom ?: '0' . $unit;
            $left   = $left ?: '0' . $unit;
        }

        return trim( sprintf( '%s %s %s %s', $top, $right, $bottom, $left ) );
    }

    private function build_image_style( $image_height, $object_fit ) {
        $styles = [];

        $styles[] = 'height: 100%;';

        $allowed_fits = [ 'cover', 'contain', 'fill', 'none', 'scale-down' ];
        $fit_value    = in_array( $object_fit, $allowed_fits, true ) ? $object_fit : 'cover';
        $styles[]     = 'object-fit: ' . $fit_value . ';';

        return esc_attr( implode( ' ', $styles ) );
    }

    private function sanitize_dimension_unit( $unit, array $allowed = [ 'px', '%', 'em' ], $fallback = 'px' ) {
        $unit = is_string( $unit ) ? strtolower( trim( $unit ) ) : '';

        return in_array( $unit, $allowed, true ) ? $unit : $fallback;
    }
}
