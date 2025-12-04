<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Related_Post_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-related-post';
    }

    public function get_title() {
        return esc_html__( 'BW Related Post', 'bw-elementor-widgets' );
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_settings',
            [
                'label' => __( 'Settings', 'bw-elementor-widgets' ),
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label'   => __( 'Number of Posts', 'bw-elementor-widgets' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 5,
                'min'     => 1,
                'max'     => 20,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings       = $this->get_settings_for_display();
        $posts_per_page = ! empty( $settings['posts_per_page'] ) ? (int) $settings['posts_per_page'] : 5;
        $current_id     = get_the_ID();

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $posts_per_page,
            'post__not_in'   => $current_id ? [ $current_id ] : [],
        ];

        $category_ids = $current_id ? wp_get_post_terms( $current_id, 'category', [ 'fields' => 'ids' ] ) : [];

        if ( ! empty( $category_ids ) && ! is_wp_error( $category_ids ) ) {
            $args['category__in'] = $category_ids;
        }

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            echo '<ul>';

            while ( $query->have_posts() ) {
                $query->the_post();
                echo '<li>' . esc_html( get_the_title() ) . '</li>';
            }

            echo '</ul>';
        } else {
            echo '<p>' . esc_html__( 'Nessun post correlato trovato.', 'bw-elementor-widgets' ) . '</p>';
        }

        wp_reset_postdata();
    }
}
