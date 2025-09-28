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
        $carousel_id = 'bw-products-slider-' . $this->get_id();

        ?>
        <div id="<?php echo esc_attr( $carousel_id ); ?>" class="bw-products-slider">
            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                <div class="carousel-cell">
                    <div class="cell-media">Slide <?php echo $i; ?></div>
                </div>
            <?php endfor; ?>
        </div>
        <?php
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            ?>
            <script>
            jQuery(function($){
                var $carousel = $('.bw-products-slider');
                if ($carousel.length && !$carousel.data('flickity')) {
                    $carousel.flickity({
                        cellAlign: 'left',
                        contain: true,
                        wrapAround: true,
                        pageDots: true,
                        prevNextButtons: true,
                        autoPlay: 3000
                    });
                }
            });
            </script>
            <?php
        }
        ?>
        <?php
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
