<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class Widget_Bw_Products_Slide extends Widget_Base {

    public function get_name() { return 'bw_products_slide'; }
    public function get_title() { return 'BW Products Slide'; }
    public function get_icon() { return 'eicon-slider-full-screen'; }
    public function get_categories() { return [ 'general' ]; }

    public function get_script_depends() {
        return [ 'flickity-js', 'bw-products-slide-script' ];
    }

    public function get_style_depends() {
        return [ 'flickity-css', 'bw-products-slide-style' ];
    }

    protected function register_controls() {
        // Query Section
        $this->start_controls_section('query_section', [
            'label' => __( 'Query', 'plugin-name' ),
        ]);
        $this->add_control('post_type', [ 'label' => 'Post Type', 'type' => Controls_Manager::TEXT, 'default' => 'post' ]);
        $this->add_control('category', [ 'label' => 'Category', 'type' => Controls_Manager::TEXT, 'default' => '' ]);
        $this->add_control('include_ids', [ 'label' => 'Include IDs', 'type' => Controls_Manager::TEXT, 'default' => '' ]);
        $this->end_controls_section();

        // Display Section
        $this->start_controls_section('display_section', [ 'label' => 'Display Options' ]);
        $this->add_control('show_title', [ 'label' => 'Mostra titolo', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ]);
        $this->add_control('show_subtitle', [ 'label' => 'Mostra sottotitolo', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ]);
        $this->add_control('show_price', [ 'label' => 'Mostra prezzo', 'type' => Controls_Manager::SWITCHER, 'default' => '' ]);
        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section('layout_section', [ 'label' => 'Layout' ]);
        $this->add_control('columns', [ 'label' => 'Colonne', 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min'=>2,'max'=>6]], 'default' => ['size'=>3] ]);
        $this->add_control('gap', [ 'label' => 'Spazio tra colonne (px)', 'type' => Controls_Manager::NUMBER, 'default' => 20 ]);
        $this->end_controls_section();

        // Slider Section
        $this->start_controls_section('slider_section', [ 'label' => 'Slider Settings' ]);
        $this->add_control('autoplay_speed', [ 'label' => 'Autoplay Speed (ms)', 'type' => Controls_Manager::NUMBER, 'default' => 3000 ]);
        $this->add_control('wrap_around', [ 'label' => 'Loop infinito', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ]);
        $this->add_control('fade', [ 'label' => 'Effetto Fade', 'type' => Controls_Manager::SWITCHER, 'default' => '' ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $args = [ 'post_type' => $settings['post_type'], 'posts_per_page' => -1 ];
        if ($settings['category']) { $args['category_name'] = $settings['category']; }
        if ($settings['include_ids']) { $args['post__in'] = array_map('intval', explode(',', $settings['include_ids'])); }

        $query = new \WP_Query($args);
        echo '<div class="bw-products-slider"
                data-columns="'.esc_attr($settings['columns']['size']).'"
                data-gap="'.esc_attr($settings['gap']).'"
                data-autoplay="'.esc_attr($settings['autoplay_speed']).'"
                data-wrap="'.esc_attr($settings['wrap_around']).'"
                data-fade="'.esc_attr($settings['fade']).'">';

        while ($query->have_posts()) : $query->the_post();
            echo '<div class="carousel-cell">';
                if (has_post_thumbnail()) {
                    echo '<img src="'.get_the_post_thumbnail_url(get_the_ID(),'medium').'" alt="'.get_the_title().'">';
                }
                echo '<div class="caption">';
                    if ($settings['show_title']) { echo '<h4>'.get_the_title().'</h4>'; }
                    if ($settings['show_subtitle']) { echo '<p>'.get_the_excerpt().'</p>'; }
                    if ($settings['show_price'] && function_exists('wc_get_product')) {
                        $product = wc_get_product(get_the_ID());
                        if ($product) echo '<span class="price">'.$product->get_price_html().'</span>';
                    }
                echo '</div>';
            echo '</div>';
        endwhile;

        echo '</div>';
        wp_reset_postdata();
    }
}
