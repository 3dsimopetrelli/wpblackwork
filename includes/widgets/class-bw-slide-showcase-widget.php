<?php
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

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
        $this->register_button_controls();
    }

    private function register_query_controls() {
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'bw-elementor-widgets' ),
        ] );

        $this->add_control(
            'product_cat_parent',
            [
                'label'       => __( 'Categoria Padre', 'bw' ),
                'type'        => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => false,
                'options'     => function_exists( 'bw_get_parent_product_categories' ) ? bw_get_parent_product_categories() : [],
            ]
        );

        $this->add_control(
            'product_type',
            [
                'label'   => __( 'Product Type', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    ''          => __( 'All', 'bw' ),
                    'simple'    => __( 'Simple', 'bw' ),
                    'variable'  => __( 'Variable', 'bw' ),
                    'grouped'   => __( 'Grouped', 'bw' ),
                    'external'  => __( 'External', 'bw' ),
                    'on_sale'   => __( 'On Sale', 'bw' ),
                    'featured'  => __( 'Featured', 'bw' ),
                ],
                'default' => '',
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

        $this->add_control( 'gap', [
            'label' => __( 'Spazio tra colonne (px)', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 120, 'step' => 1 ],
            ],
            'default' => [ 'size' => 24, 'unit' => 'px' ],
        ] );

        $this->add_responsive_control( 'side_padding', [
            'label'      => __( 'Side Padding', 'bw-elementor-widgets' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'allowed_dimensions' => [ 'left', 'right' ],
            'size_units' => [ 'px', '%', 'em' ],
            'default'    => [
                'left' => 50,
                'right' => 50,
                'unit' => 'px',
                'isLinked' => false,
            ],
        ] );

        $this->add_control( 'top_spacing', [
            'label' => __( 'Top Spacing (px)', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
            ],
            'default' => [ 'size' => 50, 'unit' => 'px' ],
        ] );

        $this->add_control( 'bottom_spacing', [
            'label' => __( 'Bottom Spacing (px)', 'bw-elementor-widgets' ),
            'type'  => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range' => [
                'px' => [ 'min' => 0, 'max' => 200, 'step' => 1 ],
            ],
            'default' => [ 'size' => 50, 'unit' => 'px' ],
        ] );

        $this->end_controls_section();
    }

    private function register_image_controls() {
        $this->start_controls_section( 'images_section', [
            'label' => __( 'Immagini', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'image_height', [
            'label'   => __( 'Altezza immagini (px)', 'bw-elementor-widgets' ),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 0,
            'default' => 420,
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
    }

    private function register_button_controls() {
        $this->start_controls_section( 'view_button_section', [
            'label' => __( 'View Buttons', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'view_button_text', [
            'label'       => __( 'Testo bottone', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'View Collection', 'bw-elementor-widgets' ),
            'placeholder' => __( 'View Collection', 'bw-elementor-widgets' ),
        ] );

        $this->add_control( 'view_button_link', [
            'label'       => __( 'Link bottone', 'bw-elementor-widgets' ),
            'type'        => Controls_Manager::URL,
            'placeholder' => __( 'https://example.com', 'bw-elementor-widgets' ),
            'show_external' => true,
            'default'     => [
                'url'         => '',
                'is_external' => false,
                'nofollow'    => false,
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings      = $this->get_settings_for_display();
        $columns       = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 1;
        $gap           = isset( $settings['gap']['size'] ) ? max( 0, absint( $settings['gap']['size'] ) ) : 0;
        $image_height  = isset( $settings['image_height'] ) ? max( 0, absint( $settings['image_height'] ) ) : 0;
        $image_crop    = isset( $settings['image_crop'] ) && 'yes' === $settings['image_crop'];
        $include_ids   = isset( $settings['include_ids'] ) ? $this->parse_ids( $settings['include_ids'] ) : [];
        $product_type  = isset( $settings['product_type'] ) ? sanitize_key( $settings['product_type'] ) : '';
        $product_cat   = isset( $settings['product_cat_parent'] ) ? absint( $settings['product_cat_parent'] ) : 0;
        $slides_scroll = isset( $settings['slides_to_scroll'] ) ? max( 1, absint( $settings['slides_to_scroll'] ) ) : 1;

        $query_args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        if ( ! empty( $include_ids ) ) {
            $query_args['post__in'] = $include_ids;
            $query_args['orderby']  = 'post__in';
        }

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
        if ( $image_height > 0 ) {
            $wrapper_style .= '--bw-slide-showcase-image-height:' . $image_height . 'px;';
            $wrapper_style .= '--bw-image-height:' . $image_height . 'px;';
        } else {
            $wrapper_style .= '--bw-image-height:auto;';
        }

        $slider_settings_json = ! empty( $slider_settings ) ? wp_json_encode( $slider_settings ) : '';
        if ( $slider_settings_json ) {
            $slider_settings_json = htmlspecialchars( $slider_settings_json, ENT_QUOTES, 'UTF-8' );
        }

        $query = new \WP_Query( $query_args );

        $border_radius_value = $this->format_dimensions( isset( $settings['border_radius'] ) ? $settings['border_radius'] : [] );
        $side_padding_value  = $this->format_side_padding( isset( $settings['side_padding'] ) ? $settings['side_padding'] : [] );
        $top_spacing_value   = $this->format_slider_dimension( isset( $settings['top_spacing'] ) ? $settings['top_spacing'] : [] );
        $bottom_spacing_value = $this->format_slider_dimension( isset( $settings['bottom_spacing'] ) ? $settings['bottom_spacing'] : [] );
        $object_fit          = $image_crop ? 'cover' : 'contain';
        $button_text         = ! empty( $settings['view_button_text'] ) ? $settings['view_button_text'] : __( 'View Collection', 'bw-elementor-widgets' );
        $custom_link         = isset( $settings['view_button_link'] ) ? $settings['view_button_link'] : [];
        ?>
        <div
            class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>"
            data-columns="<?php echo esc_attr( $columns ); ?>"
            <?php if ( $slider_settings_json ) : ?>
                data-slider-settings="<?php echo $slider_settings_json; ?>"
            <?php endif; ?>
            style="<?php echo esc_attr( $wrapper_style ); ?>"
        >
            <?php if ( $query->have_posts() ) : ?>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();

                    $post_id   = get_the_ID();
                    $permalink = get_permalink( $post_id );
                    $title     = get_the_title( $post_id );
                    $subtitle  = get_the_excerpt( $post_id );

                    if ( empty( $subtitle ) ) {
                        $subtitle = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 20 );
                    }

                    $image_url = '';
                    if ( has_post_thumbnail( $post_id ) ) {
                        $image_url = get_the_post_thumbnail_url( $post_id, 'large' );
                    }

                    $btn_url = $permalink;
                    if ( ! empty( $custom_link['url'] ) ) {
                        $btn_url = $custom_link['url'];
                    }

                    $link_attributes = [];
                    $rel_values      = [];

                    if ( ! empty( $custom_link['is_external'] ) ) {
                        $link_attributes['target'] = '_blank';
                        $rel_values[]               = 'noopener';
                    }

                    if ( ! empty( $custom_link['nofollow'] ) ) {
                        $rel_values[] = 'nofollow';
                    }

                    if ( ! empty( $rel_values ) ) {
                        $link_attributes['rel'] = implode( ' ', array_unique( $rel_values ) );
                    }

                    $link_attrs = '';
                    foreach ( $link_attributes as $attr_key => $attr_value ) {
                        $link_attrs .= ' ' . $attr_key . '="' . esc_attr( $attr_value ) . '"';
                    }

                    ?>
                    <div class="bw-slide-showcase-slide">
                        <div class="bw-slide-showcase-item"<?php if ( $border_radius_value ) : ?> style="border-radius: <?php echo esc_attr( $border_radius_value ); ?>;"<?php endif; ?>>
                            <?php if ( $image_url ) : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="bw-slide-showcase-image" style="<?php echo $this->build_image_style( $image_height, $object_fit ); ?>">
                            <?php endif; ?>
                            <div class="bw-slide-showcase-overlay"></div>
                            <div class="bw-slide-showcase-content" style="<?php echo $this->build_content_style( $side_padding_value, $top_spacing_value, $bottom_spacing_value ); ?>">
                                <div class="bw-slide-showcase-title-section">
                                    <h1><?php echo esc_html( $title ); ?></h1>
                                    <?php if ( $subtitle ) : ?>
                                        <p><?php echo esc_html( $subtitle ); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="bw-slide-showcase-bottom-section">
                                    <div class="bw-slide-showcase-info">
                                        <div>
                                            <div class="bw-slide-showcase-info-item">29 Assets</div>
                                            <div class="bw-slide-showcase-badges">
                                                <span class="bw-slide-showcase-badge">SVG</span>
                                                <span class="bw-slide-showcase-badge">EPS</span>
                                                <span class="bw-slide-showcase-badge">PNG</span>
                                            </div>
                                        </div>
                                        <div class="bw-slide-showcase-info-item">95.2MB</div>
                                    </div>
                                    <a href="<?php echo esc_url( $btn_url ); ?>" class="bw-slide-showcase-view-btn"<?php echo $link_attrs; ?>>
                                        <span class="bw-slide-showcase-arrow">→</span>
                                        <?php echo esc_html( $button_text ); ?>
                                    </a>
                                </div>
                            </div>
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

    private function format_side_padding( $dimensions ) {
        if ( empty( $dimensions ) || ! is_array( $dimensions ) ) {
            return '';
        }

        $unit = $this->sanitize_dimension_unit( isset( $dimensions['unit'] ) ? $dimensions['unit'] : '' );
        $left = isset( $dimensions['left'] ) && '' !== $dimensions['left'] ? $dimensions['left'] . $unit : '';
        $right = isset( $dimensions['right'] ) && '' !== $dimensions['right'] ? $dimensions['right'] . $unit : '';

        $styles = [];
        if ( $left ) {
            $styles[] = 'padding-left: ' . $left . ';';
        }
        if ( $right ) {
            $styles[] = 'padding-right: ' . $right . ';';
        }

        return implode( ' ', $styles );
    }

    private function format_slider_dimension( $setting ) {
        if ( empty( $setting ) || ! is_array( $setting ) ) {
            return '';
        }

        if ( ! isset( $setting['size'] ) || '' === $setting['size'] ) {
            return '';
        }

        $unit = $this->sanitize_dimension_unit( isset( $setting['unit'] ) ? $setting['unit'] : '', [ 'px', '%', 'em' ], 'px' );
        return $setting['size'] . $unit;
    }

    private function build_content_style( $side_padding, $top_spacing, $bottom_spacing ) {
        $styles = [];

        if ( $side_padding ) {
            $styles[] = trim( $side_padding );
        }

        if ( $top_spacing ) {
            $styles[] = 'padding-top: ' . $top_spacing . ';';
        }

        if ( $bottom_spacing ) {
            $styles[] = 'padding-bottom: ' . $bottom_spacing . ';';
        }

        return esc_attr( implode( ' ', $styles ) );
    }

    private function build_image_style( $image_height, $object_fit ) {
        $styles = [];

        if ( $image_height > 0 ) {
            $styles[] = 'height: ' . absint( $image_height ) . 'px;';
        } else {
            $styles[] = 'height: auto;';
        }

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
