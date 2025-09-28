<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_Bw_Products_Slide extends Widget_Base {

    public function get_name() {
        return 'bw-products-slide';
    }

    public function get_title() {
        return 'BW Products Slide';
    }

    public function get_icon() {
        return 'eicon-slider-full-screen';
    }

    // 👇 Ora usa la categoria "Black Work"
    public function get_categories() {
        return [ 'black-work' ];
    }

    public function get_script_depends() {
        return [ 'flickity-js', 'bw-products-js' ];
    }

    public function get_style_depends() {
        return [ 'flickity-css', 'bw-products-style' ];
    }

    protected function register_controls() {
        // SECTION QUERY
        $this->start_controls_section('query_section', [
            'label' => __( 'Query', 'plugin-name' ),
        ]);

        $this->add_control('post_type', [
            'label' => __( 'Post Type', 'plugin-name' ),
            'type' => Controls_Manager::SELECT,
            'options' => $this->get_post_type_options(),
            'default' => 'post',
        ]);
        $this->add_control('category', [
            'label' => __( 'Category', 'plugin-name' ),
            'type' => Controls_Manager::TEXT,
            'default' => ''
        ]);
        $this->add_control('include_ids', [
            'label' => __( 'Include IDs', 'plugin-name' ),
            'type' => Controls_Manager::TEXT,
            'default' => ''
        ]);
        $this->end_controls_section();

        // SECTION DISPLAY OPTIONS
        $this->start_controls_section('display_section', [
            'label' => __( 'Display Options', 'plugin-name' ),
        ]);
        $this->add_control('show_title', [
            'label' => __( 'Mostra titolo', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes'
        ]);
        $this->add_control('show_subtitle', [
            'label' => __( 'Mostra sottotitolo', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => 'yes'
        ]);
        $this->add_control('show_price', [
            'label' => __( 'Mostra prezzo', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default' => ''
        ]);
        $this->end_controls_section();

        // SECTION LAYOUT
        $this->start_controls_section('layout_section', [
            'label' => __( 'Layout', 'plugin-name' ),
        ]);

        $this->add_control('columns', [
            'label' => __( 'Numero di colonne', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 2,
            'max' => 6,
            'step' => 1,
            'default' => 3,
        ]);

        $this->add_control('gap', [
            'label' => __( 'Spazio tra item (px)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 20,
        ]);

        $this->add_control('image_height', [
            'label' => __( 'Altezza immagini (px)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 0,
        ]);
        $this->end_controls_section();

        // SECTION SLIDER SETTINGS
        $this->start_controls_section('slider_section', [
            'label' => __( 'Slider Settings', 'plugin-name' ),
        ]);

        $this->add_control('autoplay', [
            'label' => __( 'Autoplay', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'On', 'plugin-name' ),
            'label_off' => __( 'Off', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('autoplay_speed', [
            'label' => __( 'Durata autoplay (ms)', 'plugin-name' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 100,
            'step' => 100,
            'default' => 3000,
            'condition' => [
                'autoplay' => 'yes',
            ],
        ]);

        $this->add_control('prev_next_buttons', [
            'label' => __( 'Mostra arrows', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Show', 'plugin-name' ),
            'label_off' => __( 'Hide', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('page_dots', [
            'label' => __( 'Mostra dots', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Show', 'plugin-name' ),
            'label_off' => __( 'Hide', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('wrap_around', [
            'label' => __( 'Wrap-around', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'On', 'plugin-name' ),
            'label_off' => __( 'Off', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('fade', [
            'label' => __( 'Effetto Fade', 'plugin-name' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'Fade', 'plugin-name' ),
            'label_off' => __( 'Slide', 'plugin-name' ),
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('style_section', [
            'label' => __( 'Style', 'plugin-name' ),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('title_heading', [
            'label' => __( 'Titolo', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .bw-products-slider .carousel-cell .caption h4',
            ]
        );

        $this->add_control('title_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .carousel-cell .caption h4' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('description_heading', [
            'label' => __( 'Descrizione', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'selector' => '{{WRAPPER}} .bw-products-slider .carousel-cell .caption p',
            ]
        );

        $this->add_control('description_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .carousel-cell .caption p' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('price_heading', [
            'label' => __( 'Prezzo', 'plugin-name' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .bw-products-slider .carousel-cell .caption .price',
            ]
        );

        $this->add_control('price_color', [
            'label' => __( 'Colore', 'plugin-name' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bw-products-slider .carousel-cell .caption .price' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings          = $this->get_settings_for_display();
        $carousel_id       = 'bw-products-slider-' . $this->get_id();
        $post_type         = ! empty( $settings['post_type'] ) && 'all' !== $settings['post_type'] ? $settings['post_type'] : 'any';
        $columns           = ! empty( $settings['columns'] ) ? absint( $settings['columns'] ) : 3;
        $gap               = isset( $settings['gap'] ) ? max( 0, absint( $settings['gap'] ) ) : 20;
        $image_height      = isset( $settings['image_height'] ) ? absint( $settings['image_height'] ) : 0;
        $autoplay_speed    = ! empty( $settings['autoplay_speed'] ) ? absint( $settings['autoplay_speed'] ) : 3000;
        $autoplay_value    = ( isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'] ) ? $autoplay_speed : 0;
        $wrap_around       = isset( $settings['wrap_around'] ) && 'yes' === $settings['wrap_around'];
        $page_dots         = isset( $settings['page_dots'] ) && 'yes' === $settings['page_dots'];
        $prev_next_buttons = isset( $settings['prev_next_buttons'] ) && 'yes' === $settings['prev_next_buttons'];
        $fade              = isset( $settings['fade'] ) && 'yes' === $settings['fade'];

        $query_args = [
            'post_type'           => $post_type,
            'posts_per_page'      => -1,
            'ignore_sticky_posts' => true,
        ];

        if ( ! empty( $settings['include_ids'] ) ) {
            $include_ids = array_filter( array_map( 'absint', explode( ',', $settings['include_ids'] ) ) );
            if ( ! empty( $include_ids ) ) {
                $query_args['post__in']       = $include_ids;
                $query_args['orderby']        = 'post__in';
                $query_args['posts_per_page'] = count( $include_ids );
            }
        }

        if ( ! empty( $settings['category'] ) ) {
            $category = sanitize_text_field( $settings['category'] );

            if ( 'product' === $post_type && taxonomy_exists( 'product_cat' ) ) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => array_map( 'sanitize_title', array_map( 'trim', explode( ',', $category ) ) ),
                    ],
                ];
            } else {
                $query_args['category_name'] = $category;
            }
        }

        $slider_classes = [ 'bw-products-slider' ];
        if ( $fade ) {
            $slider_classes[] = 'bw-products-slider--fade';
        }

        $wrapper_style  = '--bw-columns:' . max( 1, $columns ) . ';';
        $wrapper_style .= '--bw-gap:' . $gap . 'px;';
        if ( $image_height > 0 ) {
            $wrapper_style .= '--bw-image-height:' . $image_height . 'px;';
        } else {
            $wrapper_style .= '--bw-image-height:auto;';
        }

        $query = new \WP_Query( $query_args );
        ?>
        <div
            id="<?php echo esc_attr( $carousel_id ); ?>"
            class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $slider_classes ) ) ); ?>"
            data-autoplay="<?php echo esc_attr( $autoplay_value ); ?>"
            data-wrap-around="<?php echo esc_attr( $wrap_around ? 'true' : 'false' ); ?>"
            data-page-dots="<?php echo esc_attr( $page_dots ? 'true' : 'false' ); ?>"
            data-prev-next-buttons="<?php echo esc_attr( $prev_next_buttons ? 'true' : 'false' ); ?>"
            data-fade="<?php echo esc_attr( $fade ? 'yes' : 'no' ); ?>"
            style="<?php echo esc_attr( $wrapper_style ); ?>"
        >
            <?php if ( $query->have_posts() ) : ?>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();

                    $post_id    = get_the_ID();
                    $permalink  = get_permalink( $post_id );
                    $title      = get_the_title( $post_id );
                    $excerpt    = get_the_excerpt( $post_id );
                    $media_html = '';

                    if ( has_post_thumbnail( $post_id ) ) {
                        $media_html = get_the_post_thumbnail( $post_id, 'large', [ 'class' => 'product-image', 'loading' => 'lazy' ] );
                    }

                    if ( empty( $excerpt ) ) {
                        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 20 );
                    }

                    $price_html = '';
                    if ( isset( $settings['show_price'] ) && 'yes' === $settings['show_price'] ) {
                        if ( function_exists( 'wc_get_product' ) ) {
                            $product = wc_get_product( $post_id );
                            if ( $product ) {
                                $price_html = $product->get_price_html();
                            }
                        }

                        if ( empty( $price_html ) ) {
                            $price_meta_keys = [ '_price', 'price', 'product_price' ];
                            foreach ( $price_meta_keys as $meta_key ) {
                                $meta_value = get_post_meta( $post_id, $meta_key, true );
                                if ( '' !== $meta_value && null !== $meta_value ) {
                                    $price_html = esc_html( $meta_value );
                                    break;
                                }
                            }
                        }
                    }
                    ?>
                    <article <?php post_class( 'carousel-cell product-slide' ); ?>>
                        <?php if ( $media_html ) : ?>
                            <div class="cell-media">
                                <a class="product-link" href="<?php echo esc_url( $permalink ); ?>">
                                    <?php echo wp_kses_post( $media_html ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="caption">
                            <?php if ( isset( $settings['show_title'] ) && 'yes' === $settings['show_title'] ) : ?>
                                <h4 class="product-title">
                                    <a class="product-link" href="<?php echo esc_url( $permalink ); ?>">
                                        <?php echo esc_html( $title ); ?>
                                    </a>
                                </h4>
                            <?php endif; ?>

                            <?php if ( isset( $settings['show_subtitle'] ) && 'yes' === $settings['show_subtitle'] && ! empty( $excerpt ) ) : ?>
                                <p class="product-description"><?php echo wp_kses_post( $excerpt ); ?></p>
                            <?php endif; ?>

                            <?php if ( isset( $settings['show_price'] ) && 'yes' === $settings['show_price'] && ! empty( $price_html ) ) : ?>
                                <div class="product-price price"><?php echo wp_kses_post( $price_html ); ?></div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="carousel-cell product-slide">
                    <div class="caption">
                        <p class="product-description"><?php esc_html_e( 'Nessun contenuto disponibile.', 'plugin-name' ); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
    }

    private function get_post_type_options() {
        $options = [ 'all' => __( 'ALL', 'plugin-name' ) ];

        $post_types = get_post_types( [ 'public' => true ], 'objects' );

        foreach ( $post_types as $post_type => $object ) {
            $label = isset( $object->labels->singular_name ) ? $object->labels->singular_name : $post_type;
            $options[ $post_type ] = $label;
        }

        return $options;
    }
}
