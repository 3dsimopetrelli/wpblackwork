<?php
namespace BW\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly.
}

class BW_Blackworkpro_Products extends Widget_Base {

        public function get_name() {
                return 'blackworkpro-products';
        }

        public function get_title() {
                return esc_html__( 'BlackworkPro Products', 'sas' );
        }

        public function get_icon() {
                return 'eicon-products';
        }

        public function get_categories() {
                return [ 'blackworkpro' ];
        }

        public function get_keywords() {
                return [ 'woocommerce', 'products', 'blackworkpro' ];
        }

        protected function register_controls() {

                $this->start_controls_section(
                        'section_layout',
                        [
                                'label' => esc_html__( 'Layout', 'sas' ),
                        ]
                );

                $this->add_control(
                        'columns',
                        [
                                'label'   => esc_html__( 'Columns', 'sas' ),
                                'type'    => Controls_Manager::SELECT,
                                'default' => '3',
                                'options' => [
                                        '3' => esc_html__( '3 Columns', 'sas' ),
                                        '4' => esc_html__( '4 Columns', 'sas' ),
                                ],
                        ]
                );

                $this->end_controls_section();

                $this->start_controls_section(
                        'section_query',
                        [
                                'label' => esc_html__( 'Query', 'sas' ),
                        ]
                );

                $options = [];
                $product_categories = get_terms(
                        [
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => false,
                        ]
                );

                if ( ! is_wp_error( $product_categories ) ) {
                        foreach ( $product_categories as $category ) {
                                $options[ $category->slug ] = $category->name;
                        }
                }

                $this->add_control(
                        'product_category',
                        [
                                'label'       => esc_html__( 'Category', 'sas' ),
                                'type'        => Controls_Manager::SELECT2,
                                'options'     => $options,
                                'label_block' => true,
                                'multiple'    => false,
                                'description' => esc_html__( 'Choose a product category to display. Leave empty to show all products.', 'sas' ),
                        ]
                );

                $this->add_control(
                        'product_ids',
                        [
                                'label'       => esc_html__( 'Product IDs', 'sas' ),
                                'type'        => Controls_Manager::TEXT,
                                'label_block' => true,
                                'description' => esc_html__( 'Enter product IDs separated by commas to show specific products. When set, the category selection is ignored.', 'sas' ),
                        ]
                );

                $this->add_control(
                        'products_per_page',
                        [
                                'label'   => esc_html__( 'Products to Show', 'sas' ),
                                'type'    => Controls_Manager::NUMBER,
                                'min'     => 1,
                                'default' => 4,
                        ]
                );

                $this->end_controls_section();
        }

        protected function render() {
                if ( ! function_exists( 'wc_get_template_part' ) ) {
                        return;
                }

                $settings = $this->get_settings_for_display();

                $columns = isset( $settings['columns'] ) ? (int) $settings['columns'] : 3;
                if ( ! in_array( $columns, [ 3, 4 ], true ) ) {
                        $columns = 3;
                }

                $limit = ! empty( $settings['products_per_page'] ) ? absint( $settings['products_per_page'] ) : $columns;
                if ( 0 === $limit ) {
                        $limit = $columns;
                }

                $args = [
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'ignore_sticky_posts' => 1,
                        'posts_per_page'      => $limit,
                        'orderby'             => 'date',
                        'order'               => 'DESC',
                        'no_found_rows'       => true,
                ];

                $ids = [];
                if ( ! empty( $settings['product_ids'] ) ) {
                        $ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $settings['product_ids'] ) ) ) );
                }

                if ( ! empty( $ids ) ) {
                        $args['post__in']       = $ids;
                        $args['orderby']        = 'post__in';
                        $args['posts_per_page'] = count( $ids );
                } elseif ( ! empty( $settings['product_category'] ) ) {
                        $args['tax_query'] = [
                                [
                                        'taxonomy' => 'product_cat',
                                        'field'    => 'slug',
                                        'terms'    => $settings['product_category'],
                                ],
                        ];
                }

                $query = new \WP_Query( $args );

                if ( $query->have_posts() ) {
                        if ( function_exists( 'wc_set_loop_prop' ) ) {
                                wc_set_loop_prop( 'is_shortcode', true );
                                wc_set_loop_prop( 'columns', $columns );
                                wc_set_loop_prop( 'name', 'blackworkpro-products' );
                        } else {
                                global $woocommerce_loop;
                                $woocommerce_loop['is_shortcode'] = true;
                                $woocommerce_loop['columns']      = $columns;
                        }

                        echo '<div class="blackworkpro-products blackworkpro-products--columns-' . esc_attr( $columns ) . '">';

                        woocommerce_product_loop_start();

                        while ( $query->have_posts() ) {
                                $query->the_post();
                                wc_get_template_part( 'content', 'product' );
                        }

                        woocommerce_product_loop_end();

                        echo '</div>';
                } else {
                        echo '<p class="blackworkpro-products__empty">' . esc_html__( 'No products were found matching your selection.', 'sas' ) . '</p>';
                }

                wp_reset_postdata();

                if ( function_exists( 'wc_reset_loop' ) ) {
                        wc_reset_loop();
                } else {
                        global $woocommerce_loop;
                        $woocommerce_loop = [];
                }
        }
}
