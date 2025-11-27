<?php
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Widget_Bw_Slide_Showcase' ) ) {
    require_once __DIR__ . '/class-bw-slide-showcase-widget.php';
}

class Widget_Bw_Product_Slide extends Widget_Bw_Slide_Showcase {

    public function get_name() {
        return 'bw-product-slide';
    }

    public function get_title() {
        return 'BW Product Slide';
    }

    public function get_icon() {
        return 'eicon-post-slider';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'slick-js', 'bw-product-slide-js' ];
    }

    public function get_style_depends() {
        return [ 'slick-css', 'bw-product-slide-style' ];
    }

    protected function register_controls() {
        parent::register_controls();

        $sections_to_remove = [
            'title_style_section',
            'subtitle_style_section',
            'info_style_section',
            'badge_style_section',
            'button_style_section',
        ];

        foreach ( $sections_to_remove as $section_id ) {
            $this->remove_control( $section_id );
        }

        // Pulizia Query: rimuove controlli non necessari
        $this->remove_control( 'product_cat_parent' );
        $this->remove_control( 'product_type' );
        $this->remove_control( 'include_ids' );

        // Pulizia Layout: rimuove controlli non necessari
        $this->remove_control( 'left_offset' );
        $this->remove_control( 'column_padding' );
        $this->remove_control( 'image_padding' );
        $this->remove_control( 'layout_settings_heading' );

        // Pulizia Slider Settings: rimuove il controllo Infinite (sostituito con Loop)
        $this->remove_control( 'infinite' );

        // Rimuove il controllo responsive originale (verrà ricreato in una sezione dedicata)
        $this->remove_control( 'responsive' );

        // Aggiunge il controllo Use Product Gallery nella sezione Query
        $this->add_control(
            'use_product_gallery',
            [
                'label'        => __( 'Use Product Gallery Images', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw-elementor-widgets' ),
                'label_off'    => __( 'No', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'When enabled, the slider will use the product gallery images from the current single product page. Query settings will be ignored.', 'bw-elementor-widgets' ),
            ],
            [
                'position' => [
                    'type' => 'section',
                    'at'   => 'start',
                    'of'   => 'query_section',
                ],
            ]
        );

        // Aggiunge controlli style per il popup
        $this->register_popup_style_controls();

        // Rimuove la sezione Animation Slide Loading del parent
        $this->remove_control( 'animation_loading_section' );

        // Aggiunge la sezione Responsive Slider
        $this->register_responsive_slider_controls();

        // Aggiunge la nuova sezione Animation simplificata
        $this->register_animation_controls();

        // Rimuove il controllo border_radius del parent (senza selettori)
        $this->remove_control( 'border_radius' );

        // Ricrea il controllo border_radius con i selettori corretti per il frontend
        $this->add_responsive_control(
            'border_radius',
            [
                'label'      => __( 'Border Radius', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'default'    => [
                    'top'      => 16,
                    'right'    => 16,
                    'bottom'   => 16,
                    'left'     => 16,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-product-slide-item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ],
            [
                'position' => [
                    'type' => 'section',
                    'at'   => 'end',
                    'of'   => 'images_section',
                ],
            ]
        );

        // Rimuove la sezione "Colonna (immagine)" del parent
        $this->remove_control( 'content_style_section' );

        // Aggiunge la nuova sezione "Slide Style"
        $this->register_slide_style_controls();

        // Aggiunge controllo Loop ON/OFF
        $this->add_control(
            'loop',
            [
                'label'        => __( 'Loop', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw-elementor-widgets' ),
                'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Abilita o disabilita il loop infinito dello slider.', 'bw-elementor-widgets' ),
            ],
            [
                'position' => [
                    'type' => 'section',
                    'at'   => 'start',
                    'of'   => 'slider_section',
                ],
            ]
        );

        // Aggiunge controllo Show Slide Count ON/OFF
        $this->add_control(
            'show_slide_count',
            [
                'label'        => __( 'Show Slide Count', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw-elementor-widgets' ),
                'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Mostra o nasconde il contatore delle slide (es. "1/5").', 'bw-elementor-widgets' ),
            ],
            [
                'position' => [
                    'type'  => 'control',
                    'at'    => 'after',
                    'of'    => 'loop',
                    'index' => 'slider_section',
                ],
            ]
        );

        $this->update_responsive_control(
            'column_width',
            [
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide-wrapper' => '--bw-product-slide-column-width: {{SIZE}}{{UNIT}}; --bw-column-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bw-product-slide-item'     => '--bw-slide-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_color',
            [
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide .bw-product-slide-arrows .bw-prev img, {{WRAPPER}} .bw-product-slide .bw-product-slide-arrows .bw-next img' => 'filter: brightness(0) saturate(100%) invert(0%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(0) contrast(100%) drop-shadow(0 0 0 {{VALUE}});',
                ],
            ]
        );

        $this->update_control(
            'arrows_size',
            [
                'default'   => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide .bw-product-slide-arrows button img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->update_control(
            'arrows_padding',
            [
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide .bw-product-slide-arrows button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_vertical_offset',
            [
                'default'   => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide' => '--bw-product-slide-arrow-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_prev_horizontal_offset',
            [
                'default'   => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide' => '--bw-product-slide-arrows-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->update_control(
            'arrows_next_horizontal_offset',
            [
                'default'   => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide' => '--bw-product-slide-arrows-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Registra la sezione Slide Style con Background Color
     */
    private function register_slide_style_controls() {
        $this->start_controls_section(
            'slide_style_section',
            [
                'label' => __( 'Slide Style', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'slide_background_color',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings      = $this->get_settings_for_display();
        $columns       = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 1;
        $gap                  = isset( $settings['gap']['size'] ) ? max( 0, absint( $settings['gap']['size'] ) ) : 0;
        $image_height_data    = $this->get_slider_value_with_unit( $settings, 'image_height', 420, 'px' );
        $image_height_value   = isset( $image_height_data['size'] ) ? max( 0, (float) $image_height_data['size'] ) : 0;
        $image_height_unit    = isset( $image_height_data['unit'] ) ? $image_height_data['unit'] : 'px';
        $image_crop    = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];
        $post_type     = isset( $settings['post_type'] ) ? sanitize_key( $settings['post_type'] ) : 'product';
        $slides_scroll = isset( $settings['slides_to_scroll'] ) ? max( 1, absint( $settings['slides_to_scroll'] ) ) : 1;
        $column_width  = $this->get_column_width_value( $settings );
        $column_unit   = $this->get_column_width_unit( $settings );
        $use_product_gallery = isset( $settings['use_product_gallery'] ) && 'yes' === $settings['use_product_gallery'];
        $animation_fade = isset( $settings['animation_fade'] ) && 'yes' === $settings['animation_fade'];
        $show_slide_count = isset( $settings['show_slide_count'] ) && 'yes' === $settings['show_slide_count'];

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

        $slider_settings      = $this->prepare_slider_settings( $settings, $columns, $slides_scroll );
        $slider_settings_json = ! empty( $slider_settings ) ? wp_json_encode( $slider_settings ) : '';
        if ( $slider_settings_json ) {
            $slider_settings_json = htmlspecialchars( $slider_settings_json, ENT_QUOTES, 'UTF-8' );
        }

        $slides = [];

        // Se "Use Product Gallery" è attivo, usa le immagini della gallery del prodotto corrente
        if ( $use_product_gallery && function_exists( 'wc_get_product' ) && is_product() ) {
            global $post;
            $product_id = $post ? $post->ID : 0;

            if ( $product_id > 0 ) {
                $product = wc_get_product( $product_id );

                if ( $product ) {
                    $product_title = $product->get_name();
                    $image_ids     = [];

                    // Aggiungi la featured image come prima immagine
                    $featured_image_id = $product->get_image_id();
                    if ( $featured_image_id ) {
                        $image_ids[] = $featured_image_id;
                    }

                    // Aggiungi le gallery images
                    $gallery_image_ids = $product->get_gallery_image_ids();
                    if ( ! empty( $gallery_image_ids ) && is_array( $gallery_image_ids ) ) {
                        $image_ids = array_merge( $image_ids, $gallery_image_ids );
                    }

                    // Costruisci l'array slides dalle immagini della gallery
                    foreach ( $image_ids as $image_id ) {
                        $image_src = wp_get_attachment_image_src( $image_id, 'large' );

                        if ( ! $image_src || empty( $image_src[0] ) ) {
                            continue;
                        }

                        $image_url        = $image_src[0];
                        $image_width      = isset( $image_src[1] ) ? (int) $image_src[1] : 0;
                        $raw_image_height = isset( $image_src[2] ) ? (int) $image_src[2] : 0;

                        if ( ! $image_url ) {
                            continue;
                        }

                        $slides[] = [
                            'image' => [
                                'url'    => $image_url,
                                'width'  => $image_width,
                                'height' => $raw_image_height,
                                'srcset' => wp_get_attachment_image_srcset( $image_id, 'large' ),
                                'sizes'  => wp_get_attachment_image_sizes( $image_id, 'large' ),
                            ],
                            'title' => $product_title,
                        ];
                    }
                }
            }
        } else {
            // Logica normale: usa la query per ottenere i prodotti
            $query = new \WP_Query( $query_args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $post_id      = get_the_ID();
                    $thumbnail_id = get_post_thumbnail_id( $post_id );

                    if ( ! $thumbnail_id ) {
                        continue;
                    }

                    $image_src = wp_get_attachment_image_src( $thumbnail_id, 'large' );

                    if ( ! $image_src || empty( $image_src[0] ) ) {
                        continue;
                    }

                    $image_url        = $image_src[0];
                    $image_width      = isset( $image_src[1] ) ? (int) $image_src[1] : 0;
                    $raw_image_height = isset( $image_src[2] ) ? (int) $image_src[2] : 0;

                    if ( ! $image_url ) {
                        continue;
                    }

                    $slides[] = [
                        'image' => [
                            'url'    => $image_url,
                            'width'  => $image_width,
                            'height' => $raw_image_height,
                            'srcset' => wp_get_attachment_image_srcset( $thumbnail_id, 'large' ),
                            'sizes'  => wp_get_attachment_image_sizes( $thumbnail_id, 'large' ),
                        ],
                        'title' => get_the_title( $post_id ),
                    ];
                }
            }

            wp_reset_postdata();
        }

        if ( empty( $slides ) ) {
            echo '<div class="bw-product-slide-placeholder">' . esc_html__( 'Nessun prodotto trovato.', 'bw-elementor-widgets' ) . '</div>';
            return;
        }

        $total_slides = count( $slides );
        $product_title = ! empty( $slides ) ? $slides[0]['title'] : '';
        $wrapper_style  = '--bw-product-slide-gap:' . $gap . 'px;';
        if ( $image_height_value > 0 ) {
            $wrapper_style .= '--bw-product-slide-image-height:' . $image_height_value . $image_height_unit . ';';
        } else {
            $wrapper_style .= '--bw-product-slide-image-height:auto;';
        }

        $has_custom_column_width = false;
        if ( null !== $column_width ) {
            $wrapper_style         .= '--bw-product-slide-column-width:' . $column_width . $column_unit . ';';
            $wrapper_style         .= '--bw-column-width:' . $column_width . $column_unit . ';';
            $wrapper_style         .= '--bw-slide-width:' . $column_width . $column_unit . ';';
            $has_custom_column_width = true;
        } else {
            $wrapper_style .= '--bw-product-slide-column-width:auto;';
            $wrapper_style .= '--bw-column-width:auto;';
            $wrapper_style .= '--bw-slide-width:auto;';
        }

        $object_fit  = $image_crop ? 'cover' : 'contain';
        $image_style = $this->build_image_style( $object_fit );
        ?>
        <div class="bw-product-slide" data-show-slide-count="<?php echo esc_attr( $show_slide_count ? 'true' : 'false' ); ?>">
            <div
                class="bw-product-slide-wrapper slick-slider"
                data-columns="<?php echo esc_attr( $columns ); ?>"
                data-image-crop="<?php echo esc_attr( $image_crop ? 'true' : 'false' ); ?>"
                data-animation-fade="<?php echo esc_attr( $animation_fade ? 'true' : 'false' ); ?>"
                <?php if ( $has_custom_column_width ) : ?>
                    data-has-column-width="true"
                <?php endif; ?>
                <?php if ( $slider_settings_json ) : ?>
                    data-slider-settings="<?php echo $slider_settings_json; ?>"
                <?php endif; ?>
                style="<?php echo esc_attr( $wrapper_style ); ?>"
            >
                <?php foreach ( $slides as $index => $slide ) : ?>
                    <?php
                    $image_attributes = [
                        'src'           => $slide['image']['url'],
                        'alt'           => $slide['title'],
                        'style'         => $image_style,
                        'class'         => 'bw-slide-image bw-fade-image',
                        'decoding'      => 'async',
                        'loading'       => 0 === $index ? 'eager' : 'lazy',
                        'fetchpriority' => 0 === $index ? 'high' : 'low',
                    ];

                    if ( ! empty( $slide['image']['width'] ) ) {
                        $image_attributes['width'] = (int) $slide['image']['width'];
                    }

                    if ( ! empty( $slide['image']['height'] ) ) {
                        $image_attributes['height'] = (int) $slide['image']['height'];
                    }

                    if ( ! empty( $slide['image']['srcset'] ) ) {
                        $image_attributes['srcset'] = $slide['image']['srcset'];
                    }

                    if ( ! empty( $slide['image']['sizes'] ) ) {
                        $image_attributes['sizes'] = $slide['image']['sizes'];
                    }

                    $image_attribute_strings = [];
                    foreach ( $image_attributes as $attribute => $value ) {
                        $image_attribute_strings[] = sprintf(
                            '%1$s="%2$s"',
                            esc_attr( $attribute ),
                            esc_attr( (string) $value )
                        );
                    }
                    ?>
                    <div class="bw-product-slide-item" data-index="<?php echo esc_attr( $index + 1 ); ?>">
                        <img <?php echo implode( ' ', $image_attribute_strings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bw-product-slide-ui">
                <div class="bw-product-slide-count">
                    <span class="current">1</span>/<span class="total"><?php echo esc_html( $total_slides ); ?></span>
                </div>
                <div class="bw-product-slide-arrows">
                    <button class="bw-prev" type="button" aria-label="<?php esc_attr_e( 'Slide precedente', 'bw-elementor-widgets' ); ?>">
                        <img src="<?php echo esc_url( BW_MEW_URL . 'assets/img/arrow-l.svg' ); ?>" alt="<?php esc_attr_e( 'Indietro', 'bw-elementor-widgets' ); ?>">
                    </button>
                    <button class="bw-next" type="button" aria-label="<?php esc_attr_e( 'Slide successiva', 'bw-elementor-widgets' ); ?>">
                        <img src="<?php echo esc_url( BW_MEW_URL . 'assets/img/arrow-d.svg' ); ?>" alt="<?php esc_attr_e( 'Avanti', 'bw-elementor-widgets' ); ?>">
                    </button>
                </div>
            </div>
            <!-- POPUP FULLSCREEN -->
            <div class="bw-product-slide-popup" hidden aria-hidden="true">
                <div class="bw-product-slide-popup-header">
                    <div class="bw-popup-title"><?php echo esc_html( $product_title ); ?></div>
                    <div class="bw-popup-close">
                        <button class="bw-popup-close-btn" aria-label="Close">
                            <svg class="close-icon" viewBox="0 0 40 40" width="50" height="50">
                                <line x1="10" y1="10" x2="30" y2="30"></line>
                                <line x1="30" y1="10" x2="10" y2="30"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bw-popup-content">
                    <?php foreach ( $slides as $slide ) : ?>
                        <?php
                        $popup_attributes = [
                            'src'           => $slide['image']['url'],
                            'alt'           => $slide['title'],
                            'class'         => 'bw-popup-img',
                            'decoding'      => 'async',
                            'loading'       => 'lazy',
                            'fetchpriority' => 'low',
                        ];

                        if ( ! empty( $slide['image']['width'] ) ) {
                            $popup_attributes['width'] = (int) $slide['image']['width'];
                        }

                        if ( ! empty( $slide['image']['height'] ) ) {
                            $popup_attributes['height'] = (int) $slide['image']['height'];
                        }

                        if ( ! empty( $slide['image']['srcset'] ) ) {
                            $popup_attributes['srcset'] = $slide['image']['srcset'];
                        }

                        if ( ! empty( $slide['image']['sizes'] ) ) {
                            $popup_attributes['sizes'] = $slide['image']['sizes'];
                        }

                        $popup_attribute_strings = [];
                        foreach ( $popup_attributes as $attribute => $value ) {
                            $popup_attribute_strings[] = sprintf(
                                '%1$s="%2$s"',
                                esc_attr( $attribute ),
                                esc_attr( (string) $value )
                            );
                        }
                        ?>
                        <img <?php echo implode( ' ', $popup_attribute_strings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_column_width_value( $settings ) {
        if ( empty( $settings['column_width'] ) || ! is_array( $settings['column_width'] ) ) {
            return null;
        }

        $size = isset( $settings['column_width']['size'] ) ? $settings['column_width']['size'] : null;
        if ( null === $size || '' === $size ) {
            return null;
        }

        $size = (float) $size;
        if ( $size <= 0 ) {
            return null;
        }

        return $size;
    }

    private function get_column_width_unit( $settings ) {
        if ( empty( $settings['column_width'] ) || ! is_array( $settings['column_width'] ) ) {
            return 'px';
        }

        $unit          = isset( $settings['column_width']['unit'] ) ? $settings['column_width']['unit'] : 'px';
        $allowed_units = [ 'px', '%' ];

        if ( ! in_array( $unit, $allowed_units, true ) ) {
            $unit = 'px';
        }

        return $unit;
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

    private function build_image_style( $object_fit ) {
        $allowed_fits = [ 'cover', 'contain', 'fill', 'none', 'scale-down' ];
        $fit_value    = in_array( $object_fit, $allowed_fits, true ) ? $object_fit : 'cover';

        return 'object-fit: ' . $fit_value . '; object-position: top center;';
    }

    /**
     * Prepara le impostazioni dello slider, sovrascrivendo il metodo parent
     * per aggiungere il supporto al controllo Loop personalizzato
     */
    private function prepare_slider_settings( $settings, $columns, $slides_scroll ) {
        // Usa il controllo 'loop' invece di 'infinite'
        $loop_enabled = isset( $settings['loop'] ) && 'yes' === $settings['loop'];

        $slider_settings = [
            'infinite'       => $loop_enabled,
            'slidesToShow'   => $columns,
            'slidesToScroll' => $slides_scroll,
            'autoplay'       => isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'],
            'autoplaySpeed'  => isset( $settings['autoplay_speed'] ) ? max( 100, absint( $settings['autoplay_speed'] ) ) : 3000,
            'speed'          => isset( $settings['speed'] ) ? max( 100, absint( $settings['speed'] ) ) : 500,
            'arrows'         => isset( $settings['arrows'] ) ? 'yes' === $settings['arrows'] : true,
            'dots'           => isset( $settings['dots'] ) && 'yes' === $settings['dots'],
            'centerMode'     => isset( $settings['center_mode'] ) && 'yes' === $settings['center_mode'],
            'variableWidth'  => isset( $settings['variable_width'] ) && 'yes' === $settings['variable_width'],
            'adaptiveHeight' => isset( $settings['adaptive_height'] ) && 'yes' === $settings['adaptive_height'],
            'pauseOnHover'   => isset( $settings['pause_on_hover'] ) ? 'yes' === $settings['pause_on_hover'] : true,
        ];

        $slider_settings['slidesToScroll'] = max( 1, min( $slider_settings['slidesToScroll'], $columns ) );

        $responsive = [];
        if ( ! empty( $settings['responsive'] ) && is_array( $settings['responsive'] ) ) {
            foreach ( $settings['responsive'] as $item ) {
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

                if ( isset( $item['responsive_show_slide_count'] ) ) {
                    $item_settings['showSlideCount'] = 'yes' === $item['responsive_show_slide_count'];
                }

                // Gestione Column Width responsive
                if ( ! empty( $item['responsive_column_width'] ) && is_array( $item['responsive_column_width'] ) ) {
                    $width_size = isset( $item['responsive_column_width']['size'] ) ? $item['responsive_column_width']['size'] : null;
                    $width_unit = isset( $item['responsive_column_width']['unit'] ) ? $item['responsive_column_width']['unit'] : 'px';

                    if ( null !== $width_size && '' !== $width_size ) {
                        $item_settings['responsiveWidth'] = [
                            'size' => (float) $width_size,
                            'unit' => $width_unit,
                        ];
                    }
                }

                // Gestione Column Height responsive
                if ( ! empty( $item['responsive_column_height'] ) && is_array( $item['responsive_column_height'] ) ) {
                    $height_size = isset( $item['responsive_column_height']['size'] ) ? $item['responsive_column_height']['size'] : null;
                    $height_unit = isset( $item['responsive_column_height']['unit'] ) ? $item['responsive_column_height']['unit'] : 'px';

                    if ( null !== $height_size && '' !== $height_size ) {
                        $item_settings['responsiveHeight'] = [
                            'size' => (float) $height_size,
                            'unit' => $height_unit,
                        ];
                    }
                }

                // Gestione Column Gap responsive
                if ( ! empty( $item['responsive_column_gap'] ) && is_array( $item['responsive_column_gap'] ) ) {
                    $gap_size = isset( $item['responsive_column_gap']['size'] ) ? $item['responsive_column_gap']['size'] : null;
                    $gap_unit = isset( $item['responsive_column_gap']['unit'] ) ? $item['responsive_column_gap']['unit'] : 'px';

                    if ( null !== $gap_size && '' !== $gap_size ) {
                        $item_settings['responsiveGap'] = [
                            'size' => (float) $gap_size,
                            'unit' => $gap_unit,
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

    /**
     * Registra i controlli style per il popup
     */
    private function register_popup_style_controls() {
        // Close Button Style
        $this->start_controls_section(
            'popup_close_button_style',
            [
                'label' => __( 'Popup Close Button', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'popup_close_color',
            [
                'label'     => __( 'Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-popup-close-btn .close-icon' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_close_color_hover',
            [
                'label'     => __( 'Hover Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bw-popup-close-btn:hover .close-icon, {{WRAPPER}} .bw-popup-close-btn:focus .close-icon' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'popup_close_size',
            [
                'label'      => __( 'Size', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 50,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-popup-close-btn .close-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Popup Title Style
        $this->start_controls_section(
            'popup_title_style',
            [
                'label' => __( 'Popup Title', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'popup_title_typography',
                'selector' => '{{WRAPPER}} .bw-popup-title',
            ]
        );

        $this->add_control(
            'popup_title_color',
            [
                'label'     => __( 'Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-popup-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Popup Header Style
        $this->start_controls_section(
            'popup_header_style',
            [
                'label' => __( 'Popup Header', 'bw-elementor-widgets' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'popup_header_padding',
            [
                'label'      => __( 'Padding', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem' ],
                'default'    => [
                    'top'      => 20,
                    'right'    => 40,
                    'bottom'   => 20,
                    'left'     => 40,
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-product-slide-popup-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'popup_header_background',
            [
                'label'     => __( 'Background Color', 'bw-elementor-widgets' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bw-product-slide-popup-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Registra la sezione Responsive Slider con il repeater senza Infinite ON/OFF
     */
    private function register_responsive_slider_controls() {
        $this->start_controls_section(
            'responsive_slider_section',
            [
                'label' => __( 'Responsive Slider', 'bw-elementor-widgets' ),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'breakpoint',
            [
                'label'   => __( 'Breakpoint (px)', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 320,
                'default' => 1024,
            ]
        );

        $column_options = [];
        foreach ( range( 1, 6 ) as $column ) {
            $column_options[ $column ] = (string) $column;
        }

        $repeater->add_control(
            'slides_to_show',
            [
                'label'   => __( 'Slides To Show', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $column_options,
                'default' => '1',
            ]
        );

        $repeater->add_control(
            'slides_to_scroll',
            [
                'label'   => __( 'Slides To Scroll', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'default' => 1,
            ]
        );

        $repeater->add_control(
            'responsive_dots',
            [
                'label'        => __( 'Dots', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'responsive_arrows',
            [
                'label'        => __( 'Arrows', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $repeater->add_control(
            'responsive_center_mode',
            [
                'label'        => __( 'Center Mode', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'responsive_variable_width',
            [
                'label'        => __( 'Variable Width', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'responsive_show_slide_count',
            [
                'label'        => __( 'Show Slide Count', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Mostra o nasconde il contatore delle slide per questo breakpoint.', 'bw-elementor-widgets' ),
            ]
        );

        $repeater->add_control(
            'responsive_column_width',
            [
                'label'      => __( 'Column Width', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vw' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 2000, 'step' => 1 ],
                    '%'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                    'vw' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => '',
                    'unit' => 'px',
                ],
                'description' => __( 'Larghezza delle colonne per questo breakpoint. Lascia vuoto per usare il valore globale.', 'bw-elementor-widgets' ),
            ]
        );

        $repeater->add_control(
            'responsive_column_height',
            [
                'label'      => __( 'Column Height', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 2000, 'step' => 1 ],
                    'vh' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => '',
                    'unit' => 'px',
                ],
                'description' => __( 'Altezza delle colonne per questo breakpoint. Lascia vuoto per usare il valore globale.', 'bw-elementor-widgets' ),
            ]
        );

        $repeater->add_control(
            'responsive_column_gap',
            [
                'label'      => __( 'Column Gap / Space Between Columns', 'bw-elementor-widgets' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => '',
                    'unit' => 'px',
                ],
                'description' => __( 'Spazio tra le colonne per questo breakpoint. Lascia vuoto per usare il valore globale.', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'responsive',
            [
                'label'       => __( 'Responsive', 'bw-elementor-widgets' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'title_field' => __( 'Breakpoint: {{{ breakpoint }}}px', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Registra la sezione Animation Slide Loading semplificata
     */
    private function register_animation_controls() {
        $this->start_controls_section(
            'animation_loading_section',
            [
                'label' => __( 'Animation Slide Loading', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'animation_fade',
            [
                'label'        => __( 'Animation Fade', 'bw-elementor-widgets' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw-elementor-widgets' ),
                'label_off'    => __( 'Off', 'bw-elementor-widgets' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Quando attivo, le immagini delle slide appaiono con un effetto fade morbido durante il caricamento.', 'bw-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();
    }
}
