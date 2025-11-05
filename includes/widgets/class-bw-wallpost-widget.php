<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_WallPost_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-wallpost';
    }

    public function get_title() {
        return esc_html__( 'BW WallPost', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'imagesloaded', 'masonry', 'bw-wallpost-js' ];
    }

    public function get_style_depends() {
        return [ 'bw-wallpost-style' ];
    }

    protected function register_controls() {
        $this->register_query_controls();
        $this->register_layout_controls();
        $this->register_image_controls();
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

        $this->add_control( 'post_type', [
            'label'   => __( 'Post Type', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => $post_type_options,
            'default' => $default_post_type,
        ] );

        $this->add_control( 'parent_category', [
            'label'       => __( 'Categoria padre', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::SELECT2,
            'label_block' => true,
            'multiple'    => false,
            'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : [],
            'condition'   => [ 'post_type' => 'product' ],
        ] );

        $this->add_control( 'subcategory', [
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
        ] );

        $this->add_control( 'specific_ids', [
            'label'       => __( 'ID specifici', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __( 'es. 12, 45, 78', 'bw-elementor-widgets' ),
            'description' => __( 'Inserisci gli ID separati da virgola.', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'order_by', [
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
        ] );

        $this->add_control( 'order', [
            'label'     => __( 'Direzione ordinamento', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'DESC',
            'options'   => [
                'ASC'  => __( 'Crescente (A → Z, 1 → 9, vecchio → nuovo)', 'bw-elementor-widgets' ),
                'DESC' => __( 'Decrescente (Z → A, 9 → 1, nuovo → vecchio)', 'bw-elementor-widgets' ),
            ],
            'condition' => [
                'order_by!' => 'rand', // Nascondi quando ordinamento è casuale
            ],
        ] );

        $this->end_controls_section();
    }

    private function register_layout_controls() {
        $this->start_controls_section( 'layout_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'posts_per_page', [
            'label'   => __( 'Numero di post', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => -1,
            'max'     => 100,
            'step'    => 1,
            'default' => 12,
        ] );

        $this->add_responsive_control( 'margin_top', [
            'label'      => __( 'Margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => -200, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => -50, 'max' => 50, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'margin_bottom', [
            'label'      => __( 'Margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => -200, 'max' => 200, 'step' => 1 ],
                '%'  => [ 'min' => -50, 'max' => 50, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        // ===============================================
        // SEZIONE: Layout Desktop (default)
        // ===============================================
        $this->start_controls_section( 'section_layout_desktop', [
            'label' => __( 'Layout Desktop', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'columns_desktop', [
            'label'   => __( 'Numero Colonne Desktop', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '4',
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
            ],
        ] );

        $this->add_control( 'gap_desktop', [
            'label'   => __( 'Gap Colonne Desktop', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 15,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ] );

        $this->add_control( 'image_height_desktop', [
            'label'   => __( 'Altezza Immagine Desktop', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 625,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 100,
                    'max' => 1000,
                ],
            ],
        ] );

        $this->end_controls_section();

        // ===============================================
        // SEZIONE: Responsive Settings
        // ===============================================
        $this->start_controls_section( 'section_responsive', [
            'label' => __( 'Responsive', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        // --- TABLET SETTINGS ---
        $this->add_control( 'heading_tablet', [
            'label'     => __( 'Impostazioni Tablet', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'breakpoint_tablet_min', [
            'label'       => __( 'Larghezza Minima Tablet (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 768,
            'min'         => 600,
            'max'         => 1200,
            'description' => __( 'Dispositivi con larghezza >= a questo valore saranno considerati tablet', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'breakpoint_tablet_max', [
            'label'       => __( 'Larghezza Massima Tablet (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 1024,
            'min'         => 768,
            'max'         => 1400,
            'description' => __( 'Dispositivi con larghezza <= a questo valore saranno considerati tablet', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'columns_tablet', [
            'label'   => __( 'Numero Colonne Tablet', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '2',
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
        ] );

        $this->add_control( 'gap_tablet', [
            'label'   => __( 'Gap Colonne Tablet', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 10,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ] );

        $this->add_control( 'image_height_tablet', [
            'label'   => __( 'Altezza Immagine Tablet', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 400,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 100,
                    'max' => 800,
                ],
            ],
        ] );

        // --- MOBILE SETTINGS ---
        $this->add_control( 'heading_mobile', [
            'label'     => __( 'Impostazioni Mobile', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'breakpoint_mobile_max', [
            'label'       => __( 'Larghezza Massima Mobile (px)', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 767,
            'min'         => 320,
            'max'         => 900,
            'description' => __( 'Dispositivi con larghezza <= a questo valore saranno considerati mobile', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'columns_mobile', [
            'label'   => __( 'Numero Colonne Mobile', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'default' => '1',
            'options' => [
                '1' => '1',
                '2' => '2',
            ],
        ] );

        $this->add_control( 'gap_mobile', [
            'label'   => __( 'Gap Colonne Mobile', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 10,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 0,
                    'max' => 50,
                ],
            ],
        ] );

        $this->add_control( 'image_height_mobile', [
            'label'   => __( 'Altezza Immagine Mobile', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'default' => [
                'size' => 300,
                'unit' => 'px',
            ],
            'range'   => [
                'px' => [
                    'min' => 150,
                    'max' => 600,
                ],
            ],
        ] );

        $this->end_controls_section();
    }

    private function register_image_controls() {
        // Sezione: Image Settings
        $this->start_controls_section( 'image_section', [
            'label' => __( 'Image Settings', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_toggle', [
            'label'        => __( 'Show Featured Image', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Mostra/nascondi l\'immagine in evidenza', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_size', [
            'label'   => __( 'Image Size', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'thumbnail'    => __( 'Thumbnail', 'bw-elementor-widgets' ),
                'medium'       => __( 'Medium', 'bw-elementor-widgets' ),
                'medium_large' => __( 'Medium Large', 'bw-elementor-widgets' ),
                'large'        => __( 'Large', 'bw-elementor-widgets' ),
                'full'         => __( 'Full', 'bw-elementor-widgets' ),
            ],
            'default'   => 'large',
            'condition' => [ 'image_toggle' => 'yes' ],
        ] );


        $this->add_responsive_control( 'image_border_radius', [
            'label'      => __( 'Image Border Radius', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'default'    => [
                'top'      => '8',
                'right'    => '8',
                'bottom'   => '8',
                'left'     => '8',
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost-media'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-wallpost-media img' => 'border-radius: inherit;',
                '{{WRAPPER}} .bw-wallpost-overlay' => 'border-radius: inherit;',
                '{{WRAPPER}} .bw-wallpost-image'   => 'border-radius: inherit;',
            ],
            'condition' => [ 'image_toggle' => 'yes' ],
        ] );

        $this->add_control( 'image_object_fit', [
            'label'   => __( 'Image Object Fit', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'cover'   => __( 'Cover', 'bw-elementor-widgets' ),
                'contain' => __( 'Contain', 'bw-elementor-widgets' ),
                'fill'    => __( 'Fill', 'bw-elementor-widgets' ),
                'none'    => __( 'None', 'bw-elementor-widgets' ),
            ],
            'default'   => 'cover',
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost-media img' => 'object-fit: {{VALUE}};',
            ],
            'condition' => [ 'image_toggle' => 'yes' ],
        ] );

        $this->add_control( 'image_background_color', [
            'label'       => __( 'Background Immagine', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::COLOR,
            'default'     => 'transparent',
            'description' => __( 'Colore di sfondo per immagini PNG con trasparenze', 'bw-elementor-widgets' ),
            'selectors'   => [
                '{{WRAPPER}} .bw-wallpost-media' => 'background-color: {{VALUE}};',
                '{{WRAPPER}} .bw-wallpost-image' => 'background-color: {{VALUE}};',
            ],
            'condition'   => [ 'image_toggle' => 'yes' ],
        ] );

        $this->end_controls_section();

        // Sezione: Hover Effect
        $this->start_controls_section( 'hover_section', [
            'label' => __( 'Hover Effect', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'hover_effect', [
            'label'        => __( 'Enable Hover Effect', 'bw-elementor-widgets' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
            'label_off'    => __( 'No', 'bw-elementor-widgets' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __( 'Attiva effetto fade al passaggio del mouse', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'hover_opacity', [
            'label'   => __( 'Hover Opacity', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.1 ],
            ],
            'default' => [
                'size' => 0.7,
            ],
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-hover-opacity: {{SIZE}};',
            ],
            'condition' => [ 'hover_effect' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section( 'typography_section', [
            'label' => __( 'Typography', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_typography_heading', [
            'label' => __( 'Titolo', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::HEADING,
        ] );

        $this->add_control( 'title_color', [
            'label'     => __( 'Colore titolo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-title' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .bw-wallpost .bw-wallpost-title',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 14,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_responsive_control( 'title_margin_top', [
            'label'      => __( 'Titolo - margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-title' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'title_margin_bottom', [
            'label'      => __( 'Titolo - margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'description_typography_heading', [
            'label'     => __( 'Descrizione', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'description_color', [
            'label'     => __( 'Colore descrizione', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-description' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'description_typography',
            'selector' => '{{WRAPPER}} .bw-wallpost .bw-wallpost-description',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 14,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_responsive_control( 'description_margin_top', [
            'label'      => __( 'Descrizione - margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-description' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'description_margin_bottom', [
            'label'      => __( 'Descrizione - margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'price_typography_heading', [
            'label'     => __( 'Prezzo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'price_color', [
            'label'     => __( 'Colore prezzo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-price' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'price_typography',
            'selector' => '{{WRAPPER}} .bw-wallpost .bw-wallpost-price',
            'fields_options' => [
                'font_size' => [
                    'default' => [
                        'size' => 14,
                        'unit' => 'px',
                    ],
                ],
            ],
        ] );

        $this->add_responsive_control( 'price_margin_top', [
            'label'      => __( 'Prezzo - margine superiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-price' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'price_margin_bottom', [
            'label'      => __( 'Prezzo - margine inferiore', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => -100, 'max' => 200, 'step' => 1 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost .bw-wallpost-price' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'overlay_buttons_section', [
            'label' => __( 'Overlay Buttons', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'overlay_buttons_typography',
            'selector' => '{{WRAPPER}} .bw-wallpost .bw-wallpost-overlay-button',
        ] );

        $this->start_controls_tabs( 'overlay_buttons_color_tabs' );

        $this->start_controls_tab( 'overlay_buttons_color_normal', [
            'label' => __( 'Normal', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'overlay_buttons_text_color', [
            'label'     => __( 'Colore testo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-overlay-buttons-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'overlay_buttons_background_color', [
            'label'     => __( 'Colore sfondo', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#FFFFFF',
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-overlay-buttons-background: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->start_controls_tab( 'overlay_buttons_color_hover', [
            'label' => __( 'Hover', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'overlay_buttons_text_color_hover', [
            'label'     => __( 'Colore testo (hover)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-overlay-buttons-color-hover: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'overlay_buttons_background_color_hover', [
            'label'     => __( 'Colore sfondo (hover)', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#80FD03',
            'selectors' => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-overlay-buttons-background-hover: {{VALUE}};',
            ],
        ] );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control( 'overlay_buttons_border_radius', [
            'label'      => __( 'Raggio bordi', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 200 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'default'    => [
                'size' => 8,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-overlay-buttons-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'overlay_buttons_padding', [
            'label'      => __( 'Padding pulsanti', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'default'    => [
                'top'      => '13',
                'right'    => '10',
                'bottom'   => '13',
                'left'     => '10',
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-wallpost' => '--bw-wallpost-overlay-buttons-padding-top: {{TOP}}{{UNIT}}; --bw-wallpost-overlay-buttons-padding-right: {{RIGHT}}{{UNIT}}; --bw-wallpost-overlay-buttons-padding-bottom: {{BOTTOM}}{{UNIT}}; --bw-wallpost-overlay-buttons-padding-left: {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings          = $this->get_settings_for_display();
        $post_type         = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'post';
        $available_post_types = $this->get_post_type_options();

        if ( empty( $available_post_types ) ) {
            $available_post_types = [ 'post' => __( 'Post', 'bw-elementor-widgets' ) ];
        }

        if ( ! array_key_exists( $post_type, $available_post_types ) ) {
            $post_type_keys = array_keys( $available_post_types );
            $post_type      = array_key_exists( 'post', $available_post_types ) ? 'post' : reset( $post_type_keys );
        }

        $posts_per_page = isset( $settings['posts_per_page'] ) ? (int) $settings['posts_per_page'] : 6;
        if ( 0 === $posts_per_page ) {
            $posts_per_page = -1;
        }

        // Get desktop values
        $columns_desktop = isset( $settings['columns_desktop'] ) ? max( 1, absint( $settings['columns_desktop'] ) ) : 4;
        $columns_desktop = max( 1, min( 6, $columns_desktop ) );
        $gap_desktop_data = $this->get_slider_value_with_unit( $settings, 'gap_desktop', 15, 'px' );
        $gap_desktop_size = isset( $gap_desktop_data['size'] ) ? (float) $gap_desktop_data['size'] : 15;
        if ( ! is_finite( $gap_desktop_size ) ) {
            $gap_desktop_size = 15;
        }
        $image_height_desktop_data = $this->get_slider_value_with_unit( $settings, 'image_height_desktop', 625, 'px' );
        $image_height_desktop = isset( $image_height_desktop_data['size'] ) ? (float) $image_height_desktop_data['size'] : 625;

        // Get tablet values
        $breakpoint_tablet_min = isset( $settings['breakpoint_tablet_min'] ) ? absint( $settings['breakpoint_tablet_min'] ) : 768;
        $breakpoint_tablet_max = isset( $settings['breakpoint_tablet_max'] ) ? absint( $settings['breakpoint_tablet_max'] ) : 1024;
        $columns_tablet  = isset( $settings['columns_tablet'] ) ? max( 1, absint( $settings['columns_tablet'] ) ) : 2;
        $columns_tablet  = max( 1, min( 4, $columns_tablet ) );
        $gap_tablet_data = $this->get_slider_value_with_unit( $settings, 'gap_tablet', 10, 'px' );
        $gap_tablet_size = isset( $gap_tablet_data['size'] ) ? (float) $gap_tablet_data['size'] : 10;
        if ( ! is_finite( $gap_tablet_size ) ) {
            $gap_tablet_size = 10;
        }
        $image_height_tablet_data = $this->get_slider_value_with_unit( $settings, 'image_height_tablet', 400, 'px' );
        $image_height_tablet = isset( $image_height_tablet_data['size'] ) ? (float) $image_height_tablet_data['size'] : 400;

        // Get mobile values
        $breakpoint_mobile_max = isset( $settings['breakpoint_mobile_max'] ) ? absint( $settings['breakpoint_mobile_max'] ) : 767;
        $columns_mobile  = isset( $settings['columns_mobile'] ) ? max( 1, absint( $settings['columns_mobile'] ) ) : 1;
        $columns_mobile  = max( 1, min( 2, $columns_mobile ) );
        $gap_mobile_data = $this->get_slider_value_with_unit( $settings, 'gap_mobile', 10, 'px' );
        $gap_mobile_size = isset( $gap_mobile_data['size'] ) ? (float) $gap_mobile_data['size'] : 10;
        if ( ! is_finite( $gap_mobile_size ) ) {
            $gap_mobile_size = 10;
        }
        $image_height_mobile_data = $this->get_slider_value_with_unit( $settings, 'image_height_mobile', 300, 'px' );
        $image_height_mobile = isset( $image_height_mobile_data['size'] ) ? (float) $image_height_mobile_data['size'] : 300;

        // Nuovi controlli immagine
        $image_toggle    = isset( $settings['image_toggle'] ) && 'yes' === $settings['image_toggle'];
        $image_size      = isset( $settings['image_size'] ) ? $settings['image_size'] : 'large';
        $hover_effect    = isset( $settings['hover_effect'] ) && 'yes' === $settings['hover_effect'];

        $include_ids = isset( $settings['specific_ids'] ) ? $this->parse_ids( $settings['specific_ids'] ) : [];

        $parent_category = isset( $settings['parent_category'] ) ? absint( $settings['parent_category'] ) : 0;
        $subcategories   = isset( $settings['subcategory'] ) ? array_filter( array_map( 'absint', (array) $settings['subcategory'] ) ) : [];

        // Get ordering settings
        $order_by = isset( $settings['order_by'] ) ? sanitize_key( $settings['order_by'] ) : 'date';
        $order    = isset( $settings['order'] ) ? strtoupper( sanitize_key( $settings['order'] ) ) : 'DESC';

        // Validate order_by
        $valid_order_by = [ 'date', 'modified', 'title', 'rand', 'ID' ];
        if ( ! in_array( $order_by, $valid_order_by, true ) ) {
            $order_by = 'date';
        }

        // Validate order
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        // For random order, ignore ASC/DESC
        if ( 'rand' === $order_by ) {
            $order = 'ASC'; // Doesn't matter for rand, but WP_Query expects it
        }

        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => $posts_per_page > 0 ? $posts_per_page : -1,
            'post_status'    => 'publish',
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

        if ( 'product' === $post_type ) {
            $tax_query = [];

            if ( ! empty( $subcategories ) ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $subcategories,
                ];
            } elseif ( $parent_category > 0 ) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [ $parent_category ],
                ];
            }

            if ( ! empty( $tax_query ) ) {
                $query_args['tax_query'] = $tax_query;
            }
        }

        $wrapper_classes = [ 'bw-wallpost' ];
        $wrapper_style   = '--bw-wallpost-columns:' . $columns_desktop . ';';
        $wrapper_style  .= '--bw-wallpost-gap:' . $gap_desktop_size . 'px;';

        $grid_attributes = [
            'class'                       => 'bw-wallpost-grid',
            'data-columns-desktop'        => $columns_desktop,
            'data-gap-desktop'            => $gap_desktop_size,
            'data-breakpoint-tablet-min'  => $breakpoint_tablet_min,
            'data-breakpoint-tablet-max'  => $breakpoint_tablet_max,
            'data-columns-tablet'         => $columns_tablet,
            'data-gap-tablet'             => $gap_tablet_size,
            'data-breakpoint-mobile-max'  => $breakpoint_mobile_max,
            'data-columns-mobile'         => $columns_mobile,
            'data-gap-mobile'             => $gap_mobile_size,
        ];

        $grid_attr_html = '';
        foreach ( $grid_attributes as $attr => $value ) {
            if ( '' === $value && 0 !== $value ) {
                continue;
            }

            $grid_attr_html .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( (string) $value ) );
        }

        $query = new \WP_Query( $query_args );
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
            <div<?php echo $grid_attr_html; ?>>
                <?php if ( $query->have_posts() ) : ?>
                    <?php
                    while ( $query->have_posts() ) :
                        $query->the_post();

                        $post_id   = get_the_ID();
                        $permalink = get_permalink( $post_id );
                        $title     = get_the_title( $post_id );
                        $excerpt   = get_the_excerpt( $post_id );

                        if ( empty( $excerpt ) ) {
                            $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 30 );
                        }

                        if ( ! empty( $excerpt ) && false === strpos( $excerpt, '<p' ) ) {
                            $excerpt = '<p>' . $excerpt . '</p>';
                        }

                        $thumbnail_html = '';

                        if ( $image_toggle && has_post_thumbnail( $post_id ) ) {
                            $thumbnail_args = [
                                'loading' => 'lazy',
                                'class'   => 'bw-slider-main',
                            ];

                            $thumbnail_html = get_the_post_thumbnail( $post_id, $image_size, $thumbnail_args );
                        }

                        $hover_image_html = '';
                        if ( $hover_effect && 'product' === $post_type ) {
                            $hover_image_id = (int) get_post_meta( $post_id, '_bw_slider_hover_image', true );

                            if ( $hover_image_id ) {
                                $hover_image_html = wp_get_attachment_image(
                                    $hover_image_id,
                                    $image_size,
                                    false,
                                    [
                                        'class'   => 'bw-slider-hover',
                                        'loading' => 'lazy',
                                    ]
                                );
                            }
                        }

                        $price_html     = '';
                        $has_add_to_cart = false;
                        $add_to_cart_url = '';

                        if ( 'product' === $post_type ) {
                            $price_html = $this->get_price_markup( $post_id );

                            if ( function_exists( 'wc_get_product' ) ) {
                                $product = wc_get_product( $post_id );

                                if ( $product ) {
                                    if ( $product->is_type( 'variable' ) ) {
                                        $add_to_cart_url = $permalink;
                                    } else {
                                        $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

                                        if ( $cart_url ) {
                                            $add_to_cart_url = add_query_arg( 'add-to-cart', $product->get_id(), $cart_url );
                                        }
                                    }

                                    if ( ! $add_to_cart_url ) {
                                        $add_to_cart_url = $permalink;
                                    }

                                    $has_add_to_cart = true;
                                }
                            }
                        }

                        $view_label = 'product' === $post_type
                            ? esc_html__( 'View Product', 'bw-elementor-widgets' )
                            : esc_html__( 'Read More', 'bw-elementor-widgets' );
                        ?>
                        <article <?php post_class( 'bw-wallpost-item bw-slick-item' ); ?>>
                            <div class="bw-wallpost-card bw-slick-item__inner bw-ss__card">
                                <div class="bw-slider-image-container">
                                    <?php
                                    $media_classes = [ 'bw-wallpost-media', 'bw-slick-item__image', 'bw-ss__media' ];
                                    if ( ! $thumbnail_html ) {
                                        $media_classes[] = 'bw-wallpost-media--placeholder';
                                        $media_classes[] = 'bw-slick-item__image--placeholder';
                                    }
                                    ?>
                                    <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $media_classes ) ) ); ?>">
                                        <?php if ( $thumbnail_html ) : ?>
                                            <a class="bw-wallpost-media-link bw-slick-item__media-link bw-ss__media-link" href="<?php echo esc_url( $permalink ); ?>">
                                                <div class="bw-wallpost-image bw-slick-slider-image<?php echo $hover_image_html ? ' bw-wallpost-image--has-hover bw-slick-slider-image--has-hover' : ''; ?>">
                                                    <?php echo wp_kses_post( $thumbnail_html ); ?>
                                                    <?php if ( $hover_image_html ) : ?>
                                                        <?php echo wp_kses_post( $hover_image_html ); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </a>

                                            <div class="bw-wallpost-overlay overlay-buttons bw-ss__overlay has-buttons">
                                                <div class="bw-wallpost-overlay-buttons bw-ss__buttons bw-slide-buttons<?php echo $has_add_to_cart ? ' bw-wallpost-overlay-buttons--double bw-ss__buttons--double' : ''; ?>">
                                                    <a class="bw-wallpost-overlay-button overlay-button overlay-button--view bw-ss__btn bw-view-btn bw-slide-button" href="<?php echo esc_url( $permalink ); ?>">
                                                        <span class="bw-wallpost-overlay-button__label overlay-button__label"><?php echo $view_label; ?></span>
                                                    </a>
                                                    <?php if ( 'product' === $post_type && $has_add_to_cart && $add_to_cart_url ) : ?>
                                                        <a class="bw-wallpost-overlay-button overlay-button overlay-button--cart bw-ss__btn bw-btn-addtocart bw-slide-button" href="<?php echo esc_url( $add_to_cart_url ); ?>">
                                                            <span class="bw-wallpost-overlay-button__label overlay-button__label"><?php esc_html_e( 'Add to Cart', 'bw-elementor-widgets' ); ?></span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <span class="bw-wallpost-image-placeholder bw-slick-item__image-placeholder" aria-hidden="true"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="bw-wallpost-content bw-slick-item__content bw-ss__content bw-slider-content bw-slick-slider-text-box">
                                    <h3 class="bw-wallpost-title bw-slick-item__title bw-slick-title bw-slider-title">
                                        <a href="<?php echo esc_url( $permalink ); ?>">
                                            <?php echo esc_html( $title ); ?>
                                        </a>
                                    </h3>

                                    <?php if ( ! empty( $excerpt ) ) : ?>
                                        <div class="bw-wallpost-description bw-slick-item__excerpt bw-slick-description bw-slider-description"><?php echo wp_kses_post( $excerpt ); ?></div>
                                    <?php endif; ?>

                                    <?php if ( $price_html ) : ?>
                                        <div class="bw-wallpost-price bw-slick-item__price price bw-slick-price bw-slider-price"><?php echo wp_kses_post( $price_html ); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="bw-wallpost-placeholder">
                        <?php esc_html_e( 'Nessun contenuto disponibile.', 'bw-elementor-widgets' ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            /* Mobile */
            @media (max-width: <?php echo esc_attr( $breakpoint_mobile_max ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-wallpost-media img,
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-ss__media img,
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-slick-item__image img {
                    height: <?php echo esc_attr( $image_height_mobile ); ?>px !important;
                }
            }

            /* Tablet */
            @media (min-width: <?php echo esc_attr( $breakpoint_tablet_min ); ?>px) and (max-width: <?php echo esc_attr( $breakpoint_tablet_max ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-wallpost-media img,
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-ss__media img,
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-slick-item__image img {
                    height: <?php echo esc_attr( $image_height_tablet ); ?>px !important;
                }
            }

            /* Desktop */
            @media (min-width: <?php echo esc_attr( $breakpoint_tablet_max + 1 ); ?>px) {
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-wallpost-media img,
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-ss__media img,
                .elementor-element-<?php echo esc_attr( $this->get_id() ); ?> .bw-slick-item__image img {
                    height: <?php echo esc_attr( $image_height_desktop ); ?>px !important;
                }
            }
        </style>
        <?php
        wp_reset_postdata();
    }

    private function get_slider_value_with_unit( $settings, $control_id, $default_size = null, $default_unit = 'px' ) {
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
            if ( ! isset( $post_type->name ) || 'attachment' === $post_type->name ) {
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

    private function get_price_markup( $post_id ) {
        if ( ! $post_id ) {
            return '';
        }

        $format_price = static function ( $value ) {
            if ( '' === $value || null === $value ) {
                return '';
            }

            if ( function_exists( 'wc_price' ) && is_numeric( $value ) ) {
                return wc_price( $value );
            }

            if ( is_numeric( $value ) ) {
                $value = number_format_i18n( (float) $value, 2 );
            }

            return esc_html( $value );
        };

        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post_id );
            if ( $product ) {
                $price_html = $product->get_price_html();
                if ( ! empty( $price_html ) ) {
                    return $price_html;
                }

                $regular_price = $product->get_regular_price();
                $sale_price    = $product->get_sale_price();
                $current_price = $product->get_price();

                $regular_markup = $format_price( $regular_price );
                $sale_markup    = $format_price( $sale_price );
                $current_markup = $format_price( $current_price );

                if ( $sale_markup && $regular_markup && $sale_markup !== $regular_markup ) {
                    return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                        '<span class="price-sale">' . $sale_markup . '</span>';
                }

                if ( $current_markup ) {
                    return '<span class="price-regular">' . $current_markup . '</span>';
                }
            }
        }

        $regular_price = get_post_meta( $post_id, '_regular_price', true );
        $sale_price    = get_post_meta( $post_id, '_sale_price', true );
        $current_price = get_post_meta( $post_id, '_price', true );

        if ( '' === $current_price && '' === $regular_price && '' === $sale_price ) {
            $additional_keys = [ 'price', 'product_price' ];
            foreach ( $additional_keys as $meta_key ) {
                $meta_value = get_post_meta( $post_id, $meta_key, true );
                if ( '' !== $meta_value && null !== $meta_value ) {
                    $current_price = $meta_value;
                    break;
                }
            }
        }

        $regular_markup = $format_price( $regular_price );
        $sale_markup    = $format_price( $sale_price );
        $current_markup = $format_price( $current_price );

        if ( $sale_markup && $regular_markup && $sale_markup !== $regular_markup ) {
            return '<span class="price-original"><del>' . $regular_markup . '</del></span>' .
                '<span class="price-sale">' . $sale_markup . '</span>';
        }

        if ( $current_markup ) {
            return '<span class="price-regular">' . $current_markup . '</span>';
        }

        if ( $regular_markup ) {
            return '<span class="price-regular">' . $regular_markup . '</span>';
        }

        return '';
    }
}
