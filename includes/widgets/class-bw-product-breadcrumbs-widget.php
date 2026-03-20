<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BW Product Breadcrumbs Widget.
 *
 * Renders a deterministic breadcrumb trail for the current WooCommerce single product.
 */
class Widget_Bw_Product_Breadcrumbs extends Widget_Base {

    public function get_name() {
        return 'bw-product-breadcrumbs';
    }

    public function get_title() {
        return __( 'BW-SP Product Breadcrumbs', 'bw' );
    }

    public function get_icon() {
        return 'eicon-site-navigation';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( function_exists( 'bw_register_widget_assets' ) && ! wp_style_is( 'bw-product-breadcrumbs-style', 'registered' ) ) {
            bw_register_widget_assets( 'product-breadcrumbs', [], false );
        }

        return [ 'bw-product-breadcrumbs-style' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'bw' ),
        ] );

        $this->add_control( 'product_id', [
            'label'       => __( 'Product ID', 'bw' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Leave empty to use current product', 'bw' ),
            'description' => __( 'ID of the product to preview in editor. Leave empty on single-product templates.', 'bw' ),
            'label_block' => true,
        ] );

        $this->add_control( 'show_home', [
            'label'        => __( 'Show Home', 'bw' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw' ),
            'label_off'    => __( 'No', 'bw' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_shop', [
            'label'        => __( 'Show Shop', 'bw' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw' ),
            'label_off'    => __( 'No', 'bw' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_category_path', [
            'label'        => __( 'Show Category Path', 'bw' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'bw' ),
            'label_off'    => __( 'No', 'bw' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'title_word_limit', [
            'label'       => __( 'Title Word Limit', 'bw' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => 0,
            'step'        => 1,
            'description' => __( 'Set to 0 to show the full product title. Positive values limit only the current breadcrumb item by word count.', 'bw' ),
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_box', [
            'label' => __( 'Container', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'background_color', [
            'label'     => __( 'Background', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#F7F7F5',
            'selectors' => [
                '{{WRAPPER}} .bw-product-breadcrumbs' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'padding', [
            'label'      => __( 'Padding', 'bw' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px' ],
            'default'    => [
                'top'      => 0,
                'right'    => 0,
                'bottom'   => 0,
                'left'     => 0,
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-product-breadcrumbs' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'border_radius', [
            'label'      => __( 'Border Radius', 'bw' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 60,
                ],
            ],
            'default'    => [
                'size' => 20,
                'unit' => 'px',
            ],
            'selectors'  => [
                '{{WRAPPER}} .bw-product-breadcrumbs' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_text', [
            'label' => __( 'Text', 'bw' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_responsive_control( 'text_align', [
            'label'   => __( 'Alignment', 'bw' ),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'   => [ 'title' => __( 'Left', 'bw' ), 'icon' => 'eicon-text-align-left' ],
                'center' => [ 'title' => __( 'Center', 'bw' ), 'icon' => 'eicon-text-align-center' ],
                'right'  => [ 'title' => __( 'Right', 'bw' ), 'icon' => 'eicon-text-align-right' ],
            ],
            'default'   => 'left',
            'selectors' => [
                '{{WRAPPER}} .bw-product-breadcrumbs' => 'text-align: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'           => 'typography',
            'selector'       => '{{WRAPPER}} .bw-product-breadcrumbs__item, {{WRAPPER}} .bw-product-breadcrumbs__link, {{WRAPPER}} .bw-product-breadcrumbs__current',
            'fields_options' => [
                'typography' => [ 'default' => 'yes' ],
                'font_size'  => [
                    'default'        => [ 'size' => 18, 'unit' => 'px' ],
                    'tablet_default' => [ 'size' => 18, 'unit' => 'px' ],
                    'mobile_default' => [ 'size' => 18, 'unit' => 'px' ],
                ],
                'line_height' => [
                    'default'        => [ 'size' => 110, 'unit' => '%' ],
                    'tablet_default' => [ 'size' => 110, 'unit' => '%' ],
                    'mobile_default' => [ 'size' => 120, 'unit' => '%' ],
                ],
                'font_weight' => [
                    'default' => '600',
                ],
            ],
        ] );

        $this->add_control( 'link_color', [
            'label'     => __( 'Link Color', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#8B8B8B',
            'selectors' => [
                '{{WRAPPER}} .bw-product-breadcrumbs__link' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'current_color', [
            'label'     => __( 'Current Item Color', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .bw-product-breadcrumbs__current' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'separator_color', [
            'label'     => __( 'Separator Color', 'bw' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#8B8B8B',
            'selectors' => [
                '{{WRAPPER}} .bw-product-breadcrumbs__item + .bw-product-breadcrumbs__item::before' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings    = $this->get_settings_for_display();
        $is_editor   = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();
        $product_id  = $this->resolve_product_id( $settings );
        $breadcrumbs = $product_id > 0 ? $this->build_breadcrumbs( $product_id ) : [];

        if ( ! empty( $breadcrumbs ) ) {
            $breadcrumbs = $this->apply_breadcrumb_settings( $breadcrumbs, $settings );
        }

        if ( empty( $breadcrumbs ) ) {
            if ( $is_editor ) {
                $breadcrumbs = $this->get_editor_placeholder_breadcrumbs( $settings );
            } else {
                return;
            }
        }

        $this->add_render_attribute( 'nav', 'class', 'bw-product-breadcrumbs' );
        $this->add_render_attribute( 'nav', 'aria-label', __( 'Breadcrumb', 'bw' ) );

        echo '<nav ' . $this->get_render_attribute_string( 'nav' ) . '>';
        echo '<ol class="bw-product-breadcrumbs__list">';

        foreach ( $breadcrumbs as $crumb ) {
            $is_current = ! empty( $crumb['current'] );
            echo '<li class="bw-product-breadcrumbs__item">';

            if ( $is_current ) {
                echo '<span class="bw-product-breadcrumbs__current" aria-current="page">' . esc_html( $crumb['label'] ) . '</span>';
            } else {
                echo '<a class="bw-product-breadcrumbs__link" href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a>';
            }

            echo '</li>';
        }

        echo '</ol>';
        echo '</nav>';
    }

    private function resolve_product_id( array $settings ): int {
        $product_id = ! empty( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        if ( ! $product_id && function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
            $resolution = bw_tbl_resolve_product_context_id( array_merge( $settings, [ '__widget_class' => __CLASS__ ] ) );
            $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;
        }

        if ( ! $product_id ) {
            $post_id = absint( get_the_ID() );
            if ( 'product' === get_post_type( $post_id ) ) {
                $product_id = $post_id;
            }
        }

        return $product_id;
    }

    private function build_breadcrumbs( int $product_id ): array {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return [];
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return [];
        }

        $breadcrumbs = [
            [
                'label'   => __( 'Home', 'bw' ),
                'url'     => home_url( '/' ),
                'current' => false,
                'type'    => 'home',
            ],
        ];

        $shop_page_id = function_exists( 'wc_get_page_id' ) ? absint( wc_get_page_id( 'shop' ) ) : 0;
        if ( $shop_page_id > 0 ) {
            $breadcrumbs[] = [
                'label'   => get_the_title( $shop_page_id ),
                'url'     => get_permalink( $shop_page_id ),
                'current' => false,
                'type'    => 'shop',
            ];
        }

        foreach ( $this->get_category_breadcrumbs( $product_id ) as $term_crumb ) {
            $breadcrumbs[] = $term_crumb;
        }

        $breadcrumbs[] = [
            'label'   => $product->get_name(),
            'url'     => '',
            'current' => true,
            'type'    => 'current_product',
        ];

        return $breadcrumbs;
    }

    private function get_category_breadcrumbs( int $product_id ): array {
        $terms = get_the_terms( $product_id, 'product_cat' );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return [];
        }

        $best_term = $this->resolve_best_category_term( $terms );
        if ( ! $best_term instanceof \WP_Term ) {
            return [];
        }

        $term_ids     = array_reverse( get_ancestors( $best_term->term_id, 'product_cat', 'taxonomy' ) );
        $term_ids[]   = $best_term->term_id;
        $breadcrumbs  = [];

        foreach ( $term_ids as $term_id ) {
            $term = get_term( $term_id, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }

            $term_link = get_term_link( $term );
            if ( is_wp_error( $term_link ) ) {
                continue;
            }

            $breadcrumbs[] = [
                'label'   => $term->name,
                'url'     => $term_link,
                'current' => false,
                'type'    => 'category',
            ];
        }

        return $breadcrumbs;
    }

    private function apply_breadcrumb_settings( array $breadcrumbs, array $settings ): array {
        $show_home          = empty( $settings['show_home'] ) || 'yes' === $settings['show_home'];
        $show_shop          = empty( $settings['show_shop'] ) || 'yes' === $settings['show_shop'];
        $show_category_path = empty( $settings['show_category_path'] ) || 'yes' === $settings['show_category_path'];
        $title_word_limit   = isset( $settings['title_word_limit'] ) ? absint( $settings['title_word_limit'] ) : 0;

        $breadcrumbs = array_values(
            array_filter(
                $breadcrumbs,
                static function ( array $crumb ) use ( $show_home, $show_shop, $show_category_path ) {
                    $type = isset( $crumb['type'] ) ? $crumb['type'] : '';

                    if ( 'home' === $type ) {
                        return $show_home;
                    }

                    if ( 'shop' === $type ) {
                        return $show_shop;
                    }

                    if ( 'category' === $type ) {
                        return $show_category_path;
                    }

                    return true;
                }
            )
        );

        if ( $title_word_limit > 0 ) {
            foreach ( $breadcrumbs as &$crumb ) {
                if ( ! empty( $crumb['current'] ) && 'current_product' === ( $crumb['type'] ?? '' ) ) {
                    $crumb['label'] = $this->truncate_breadcrumb_words( $crumb['label'], $title_word_limit );
                }
            }
            unset( $crumb );
        }

        return $breadcrumbs;
    }

    private function truncate_breadcrumb_words( string $text, int $word_limit ): string {
        if ( $word_limit <= 0 ) {
            return $text;
        }

        return wp_trim_words( wp_strip_all_tags( $text ), $word_limit, '...' );
    }

    private function resolve_best_category_term( array $terms ) {
        $valid_terms = array_values(
            array_filter(
                $terms,
                static function ( $term ) {
                    return $term instanceof \WP_Term;
                }
            )
        );

        if ( empty( $valid_terms ) ) {
            return null;
        }

        usort(
            $valid_terms,
            static function ( \WP_Term $left, \WP_Term $right ) {
                $left_depth  = count( get_ancestors( $left->term_id, 'product_cat', 'taxonomy' ) );
                $right_depth = count( get_ancestors( $right->term_id, 'product_cat', 'taxonomy' ) );

                if ( $left_depth !== $right_depth ) {
                    return $right_depth <=> $left_depth;
                }

                return $left->term_id <=> $right->term_id;
            }
        );

        return $valid_terms[0];
    }

    private function get_editor_placeholder_breadcrumbs( array $settings ): array {
        $breadcrumbs = [
            [
                'label'   => __( 'Home', 'bw' ),
                'url'     => '#',
                'current' => false,
                'type'    => 'home',
            ],
            [
                'label'   => __( 'Shop', 'bw' ),
                'url'     => '#',
                'current' => false,
                'type'    => 'shop',
            ],
            [
                'label'   => __( 'Category', 'bw' ),
                'url'     => '#',
                'current' => false,
                'type'    => 'category',
            ],
            [
                'label'   => __( 'Product Title', 'bw' ),
                'url'     => '',
                'current' => true,
                'type'    => 'current_product',
            ],
        ];

        return $this->apply_breadcrumb_settings( $breadcrumbs, $settings );
    }

    protected function content_template() {
        ?>
        <#
        view.addRenderAttribute( 'nav', 'class', 'bw-product-breadcrumbs' );
        var showHome = ! settings.show_home || 'yes' === settings.show_home;
        var showShop = ! settings.show_shop || 'yes' === settings.show_shop;
        var showCategoryPath = ! settings.show_category_path || 'yes' === settings.show_category_path;
        var titleWordLimit = parseInt( settings.title_word_limit || 0, 10 );
        var productTitle = '<?php echo esc_js( __( 'Product Title', 'bw' ) ); ?>';

        if ( titleWordLimit > 0 ) {
            var words = productTitle.trim().split( /\s+/ );
            if ( words.length > titleWordLimit ) {
                productTitle = words.slice( 0, titleWordLimit ).join( ' ' ) + '...';
            }
        }
        #>
        <nav {{{ view.getRenderAttributeString( 'nav' ) }}} aria-label="Breadcrumb">
            <ol class="bw-product-breadcrumbs__list">
                <# if ( showHome ) { #>
                <li class="bw-product-breadcrumbs__item"><a class="bw-product-breadcrumbs__link" href="#"><?php echo esc_html__( 'Home', 'bw' ); ?></a></li>
                <# } #>
                <# if ( showShop ) { #>
                <li class="bw-product-breadcrumbs__item"><a class="bw-product-breadcrumbs__link" href="#"><?php echo esc_html__( 'Shop', 'bw' ); ?></a></li>
                <# } #>
                <# if ( showCategoryPath ) { #>
                <li class="bw-product-breadcrumbs__item"><a class="bw-product-breadcrumbs__link" href="#"><?php echo esc_html__( 'Category', 'bw' ); ?></a></li>
                <# } #>
                <li class="bw-product-breadcrumbs__item"><span class="bw-product-breadcrumbs__current" aria-current="page">{{{ productTitle }}}</span></li>
            </ol>
        </nav>
        <?php
    }
}
