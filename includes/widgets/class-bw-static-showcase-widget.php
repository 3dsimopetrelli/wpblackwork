<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Static_Showcase extends Widget_Base {

    public function get_name() {
        return 'bw-static-showcase';
    }

    public function get_title() {
        return 'BW Static Showcase';
    }

    public function get_icon() {
        return 'eicon-frame-expand';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        return [ 'bw-static-showcase-style' ];
    }

    protected function register_controls() {
        $this->register_query_controls();
        $this->register_showcase_label_controls();
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
            'product_id',
            [
                'label'       => __( 'Product ID', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'Enter product ID', 'bw-elementor-widgets' ),
                'description' => __( 'Enter the ID of the product to display.', 'bw-elementor-widgets' ),
                'condition'   => [
                    'post_type' => 'product',
                    'use_metabox_product!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'use_metabox_product',
            [
                'label'        => __( 'Usa prodotto da Metabox Slide Showcase', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'ON', 'bw-elementor-widgets' ),
                'label_off'    => __( 'OFF', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Quando attivo, il widget userà il prodotto selezionato nel Metabox Slide Showcase del prodotto corrente.', 'bw-elementor-widgets' ),
                'condition'    => [ 'post_type' => 'product' ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_showcase_label_controls() {
        $this->start_controls_section( 'showcase_label_section', [
            'label' => __( 'Showcase Label', 'bw-elementor-widgets' ),
        ] );

        $this->add_control(
            'showcase_label_text',
            [
                'label'       => __( 'Label Text', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __( 'Inserisci il testo della label...', 'bw-elementor-widgets' ),
                'description' => __( 'Testo da visualizzare sopra l\'immagine principale. Può essere sovrascritto dal Metabox Slide Showcase.', 'bw-elementor-widgets' ),
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_layout_controls() {
        $this->start_controls_section( 'layout_section', [
            'label' => __( 'Layout', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'container_height', [
            'label'      => __( 'Container Height', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'vh' ],
            'range'      => [
                'px' => [ 'min' => 200, 'max' => 1200, 'step' => 1 ],
                'vh' => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
            ],
            'default'    => [
                'size' => 600,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-static-showcase-container' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'content_padding', [
            'label'      => __( 'Content Padding', 'bw-elementor-widgets' ),
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
                '{{WRAPPER}} .bw-static-showcase-left' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'right_column_padding', [
            'label'      => __( 'Right Column Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'default'    => [
                'top'    => 0,
                'right'  => 0,
                'bottom' => 0,
                'left'   => 0,
                'unit'   => 'px',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-static-showcase-right' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'right_column_gap', [
            'label' => __( 'Right Column Gap (px)', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'range' => [
                'px' => [ 'min' => 0, 'max' => 50, 'step' => 1 ],
            ],
            'default' => [ 'size' => 8, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .bw-static-showcase-right' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'horizontal_column_gap', [
            'label'      => __( 'Gap orizzontale tra colonne (px)', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::SLIDER,
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 0, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .bw-static-showcase-container' => 'gap: {{SIZE}}{{UNIT}};',
            ],
            'description' => __( 'Controlla lo spazio tra l\'immagine principale (sinistra) e la colonna destra.', 'bw-elementor-widgets' ),
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
            'label'      => __( 'Border Radius Container', 'bw-elementor-widgets' ),
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
            'selectors' => [
                '{{WRAPPER}} .bw-static-showcase-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'images_border_radius', [
            'label'       => __( 'Border Radius Immagini', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::DIMENSIONS,
            'size_units'  => [ 'px', '%', 'em' ],
            'default'     => [
                'top'      => 0,
                'right'    => 0,
                'bottom'   => 0,
                'left'     => 0,
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'selectors'   => [
                '{{WRAPPER}} .bw-slide-showcase-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .bw-static-showcase-right-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'description' => __( 'Applica lo stesso border radius a tutte e tre le immagini del widget.', 'bw-elementor-widgets' ),
        ] );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        // Text Color Control
        $this->start_controls_section( 'text_color_section', [
            'label' => __( 'Text Color', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'text_color', [
            'label'     => __( 'Text Color', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .bw-static-showcase-container' => '--bw-slide-showcase-text-color: {{VALUE}};',
                '{{WRAPPER}} .bw-static-showcase-container' => '--bw-slide-showcase-badge-border-color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        // Title Style
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

        // Subtitle Style
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

        // Info Style
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

        // Badge Style
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

        // Button Style
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

        // Showcase Label Style
        $this->start_controls_section( 'showcase_label_style_section', [
            'label' => __( 'Showcase Label', 'bw-elementor-widgets' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'showcase_label_color', [
            'label'     => __( 'Colore', 'bw-elementor-widgets' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .bw-showcase-label' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'showcase_label_typography',
            'label'    => __( 'Tipografia', 'bw-elementor-widgets' ),
            'selector' => '{{WRAPPER}} .bw-showcase-label',
        ] );

        $this->add_responsive_control( 'showcase_label_margin', [
            'label'      => __( 'Margini', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem' ],
            'default'    => [
                'top'    => 0,
                'right'  => 0,
                'bottom' => 10,
                'left'   => 0,
                'unit'   => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-showcase-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings   = $this->get_settings_for_display();
        $post_type  = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product';
        $use_metabox_product = isset( $settings['use_metabox_product'] ) && 'yes' === $settings['use_metabox_product'];

        // Determine product ID
        $product_id = 0;
        if ( $use_metabox_product ) {
            // Get the current post ID (the product being viewed/edited)
            $current_post_id = get_the_ID();
            if ( $current_post_id && 'product' === get_post_type( $current_post_id ) ) {
                // Read the linked product from the metabox
                $linked_product = get_post_meta( $current_post_id, '_bw_showcase_linked_product', true );
                $product_id = $linked_product ? absint( $linked_product ) : 0;
            }
        } else {
            // Use the manually entered product ID
            $product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;
        }

        $image_crop = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];

        if ( ! $product_id ) {
            ?>
            <div class="bw-static-showcase-placeholder">
                <div class="bw-static-showcase-placeholder__inner">
                    <?php esc_html_e( 'Please enter a Product ID in the widget settings.', 'bw-elementor-widgets' ); ?>
                </div>
            </div>
            <?php
            return;
        }

        $post = get_post( $product_id );

        if ( ! $post || $post->post_type !== $post_type || $post->post_status !== 'publish' ) {
            ?>
            <div class="bw-static-showcase-placeholder">
                <div class="bw-static-showcase-placeholder__inner">
                    <?php esc_html_e( 'Product not found or not published.', 'bw-elementor-widgets' ); ?>
                </div>
            </div>
            <?php
            return;
        }

        $product_title  = get_the_title( $product_id );
        $permalink      = get_permalink( $product_id );

        // Main image logic
        $image_url      = '';
        $showcase_image = get_post_meta( $product_id, '_bw_showcase_image', true );
        $image_id       = 0;

        if ( empty( $showcase_image ) ) {
            $legacy_image = get_post_meta( $product_id, '_product_showcase_image', true );
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

        if ( ! $image_url && has_post_thumbnail( $product_id ) ) {
            $image_url = get_the_post_thumbnail_url( $product_id, 'large' );
        }

        // Gallery images for right column
        $gallery_images = [];
        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $gallery_ids = $product->get_gallery_image_ids();
                if ( ! empty( $gallery_ids ) ) {
                    foreach ( array_slice( $gallery_ids, 0, 2 ) as $gallery_id ) {
                        $gallery_url = wp_get_attachment_url( $gallery_id );
                        if ( $gallery_url ) {
                            $gallery_images[] = $gallery_url;
                        }
                    }
                }
            }
        }

        // Metabox data
        $showcase_title_meta  = trim( (string) get_post_meta( $product_id, '_bw_showcase_title', true ) );
        $showcase_title       = '' !== $showcase_title_meta ? $showcase_title_meta : $product_title;
        $showcase_description = trim( (string) get_post_meta( $product_id, '_bw_showcase_description', true ) );

        // Showcase Label logic: metabox has priority over widget settings
        $showcase_label_enabled = get_post_meta( $product_id, '_bw_showcase_label_enabled', true );
        $showcase_label_meta    = trim( (string) get_post_meta( $product_id, '_bw_showcase_label', true ) );
        $showcase_label_widget  = isset( $settings['showcase_label_text'] ) ? trim( (string) $settings['showcase_label_text'] ) : '';

        $showcase_label = '';
        if ( 'yes' === $showcase_label_enabled && '' !== $showcase_label_meta ) {
            // Priority 1: Metabox value (if enabled and not empty)
            $showcase_label = $showcase_label_meta;
        } elseif ( '' !== $showcase_label_widget ) {
            // Priority 2: Widget value
            $showcase_label = $showcase_label_widget;
        }
        // Priority 3: If both empty, $showcase_label remains empty

        $meta_assets_count = get_post_meta( $product_id, '_bw_assets_count', true );
        if ( '' === $meta_assets_count ) {
            $meta_assets_count = get_post_meta( $product_id, '_product_assets_count', true );
        }

        $meta_size_mb = get_post_meta( $product_id, '_bw_file_size', true );
        if ( '' === $meta_size_mb ) {
            $meta_size_mb = get_post_meta( $product_id, '_product_size_mb', true );
        }

        $meta_formats = get_post_meta( $product_id, '_bw_formats', true );
        if ( '' === $meta_formats ) {
            $meta_formats = get_post_meta( $product_id, '_product_formats', true );
        }

        $meta_product_type_raw = get_post_meta( $product_id, '_bw_product_type', true );
        $product_type_value    = sanitize_key( $meta_product_type_raw );
        if ( ! in_array( $product_type_value, [ 'digital', 'physical' ], true ) ) {
            $product_type_value = 'digital';
        }

        $meta_button_text  = get_post_meta( $product_id, '_product_button_text', true );
        $meta_button_link  = get_post_meta( $product_id, '_product_button_link', true );
        $meta_color_value  = get_post_meta( $product_id, '_bw_texts_color', true );

        if ( empty( $meta_color_value ) ) {
            $meta_color_value = get_post_meta( $product_id, '_product_color', true );
        }

        $meta_color = sanitize_hex_color( $meta_color_value );
        if ( empty( $meta_color ) ) {
            $meta_color = '#ffffff';
        }

        $btn_url = $permalink;
        if ( ! empty( $meta_button_link ) ) {
            $btn_url = esc_url( $meta_button_link );
        }

        $button_text = __( 'View Collection', 'bw-elementor-widgets' );
        if ( ! empty( $meta_button_text ) ) {
            $button_text = wp_strip_all_tags( $meta_button_text );
        }

        $meta_info_1_raw = get_post_meta( $product_id, '_bw_info_1', true );
        $meta_info_2_raw = get_post_meta( $product_id, '_bw_info_2', true );

        // Process data
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
        $has_cta           = ! empty( $btn_url ) && ! empty( $button_text );

        $object_fit = $image_crop ? 'cover' : 'contain';

        $container_classes = [ 'bw-static-showcase-container' ];
        if ( ! $image_crop ) {
            $container_classes[] = 'bw-static-showcase--no-crop';
        }

        $inline_styles = [];
        if ( ! empty( $meta_color ) ) {
            $inline_styles[] = '--bw-slide-showcase-text-color: ' . $meta_color . ';';
            $inline_styles[] = '--bw-slide-showcase-badge-border-color: ' . $meta_color . ';';
        }

        $container_style_attr = '';
        if ( ! empty( $inline_styles ) ) {
            $container_style_attr = ' style="' . esc_attr( implode( ' ', $inline_styles ) ) . '"';
        }

        ?>
        <?php if ( '' !== $showcase_label ) : ?>
            <div class="bw-showcase-label">
                <?php echo esc_html( $showcase_label ); ?>
            </div>
        <?php endif; ?>

        <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $container_classes ) ) ); ?>"<?php echo $container_style_attr; ?>>
            <div class="bw-static-showcase-left">
                <?php if ( $image_url ) : ?>
                    <div class="bw-slide-showcase-media">
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="bw-slide-showcase-image" style="<?php echo $this->build_image_style( $object_fit ); ?>">
                    </div>
                <?php endif; ?>

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
                                <a href="<?php echo esc_url( $btn_url ); ?>" class="bw-slide-showcase-arrow" aria-label="<?php echo esc_attr( $button_text ); ?>">
                                    <span aria-hidden="true">&rsaquo;</span>
                                </a>
                                <a href="<?php echo esc_url( $btn_url ); ?>" class="bw-slide-showcase-view-btn">
                                    <?php echo esc_html( $button_text ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bw-static-showcase-right">
                <?php if ( ! empty( $gallery_images ) ) : ?>
                    <?php foreach ( $gallery_images as $gallery_image_url ) : ?>
                        <div class="bw-static-showcase-right-image">
                            <img src="<?php echo esc_url( $gallery_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" style="<?php echo $this->build_image_style( $object_fit ); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="bw-static-showcase-right-image bw-static-showcase-right-image--placeholder">
                        <span class="bw-static-showcase-placeholder-text"><?php esc_html_e( 'No gallery images', 'bw-elementor-widgets' ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function build_image_style( $object_fit ) {
        $styles = [];
        $styles[] = 'height: 100%;';
        $styles[] = 'width: 100%;';

        $allowed_fits = [ 'cover', 'contain', 'fill', 'none', 'scale-down' ];
        $fit_value    = in_array( $object_fit, $allowed_fits, true ) ? $object_fit : 'cover';
        $styles[]     = 'object-fit: ' . $fit_value . ';';

        return esc_attr( implode( ' ', $styles ) );
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
}
