<?php
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Reviews_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-reviews';
    }

    public function get_title() {
        return __( 'BW Reviews', 'bw' );
    }

    public function get_icon() {
        return 'eicon-review';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        return [ 'bw-reviews-style' ];
    }

    public function get_script_depends() {
        return [ 'bw-reviews-script' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
            ]
        );

        $this->add_control(
            'product_id',
            [
                'label'       => __( 'Product ID Override', 'bw' ),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'default'     => 0,
                'description' => __( 'Leave empty to use the current single product context or Theme Builder preview product.', 'bw' ),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if ( ! class_exists( 'BW_Reviews_Widget_Renderer' ) ) {
            return;
        }

        if ( wp_style_is( 'bw-reviews-style', 'registered' ) ) {
            wp_enqueue_style( 'bw-reviews-style' );
        }

        if ( wp_script_is( 'bw-reviews-script', 'registered' ) ) {
            wp_enqueue_script( 'bw-reviews-script' );
        }

        $renderer = new BW_Reviews_Widget_Renderer();
        $settings = $this->get_settings_for_display();
        $output   = $renderer->render_widget( $settings, $this->get_id() );

        if ( '' !== $output ) {
            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ( $is_editor ) {
            echo '<div class="bw-reviews-widget__notice">' . esc_html__( 'BW Reviews: Product not found. Select a Preview Product in Theme Builder Lite > Single Product or set Product ID in the widget.', 'bw' ) . '</div>';
        }
    }
}
