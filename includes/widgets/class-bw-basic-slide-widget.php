<?php
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Basic_Slide_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-basic-slide';
    }

    public function get_title() {
        return __( 'BW-UI Basic Slide', 'bw' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_script_depends() {
        return [ 'embla-js', 'embla-autoplay-js', 'bw-embla-core-js', 'bw-basic-slide-script' ];
    }

    public function get_style_depends() {
        return [ 'bw-embla-core-css', 'bw-basic-slide-style' ];
    }

    protected function register_controls() {
        $this->register_general_controls();
        $this->register_slide_controls();
        $this->register_wall_controls();
        $this->register_style_controls();
    }

    private function register_general_controls() {
        $this->start_controls_section(
            'section_general',
            [
                'label' => __( 'General', 'bw' ),
            ]
        );

        $this->add_control(
            'display_mode',
            [
                'label'   => __( 'Mode', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'slide',
                'options' => [
                    'slide' => __( 'Slide', 'bw' ),
                    'wall'  => __( 'Wall', 'bw' ),
                ],
            ]
        );

        $this->add_control(
            'gallery',
            [
                'label'      => __( 'Add Images', 'bw' ),
                'type'       => Controls_Manager::GALLERY,
                'default'    => [],
                'show_label' => false,
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label'   => __( 'Image Resolution', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'large',
                'options' => [
                    'thumbnail'    => __( 'Thumbnail (150×150)', 'bw' ),
                    'medium'       => __( 'Medium (300×300)', 'bw' ),
                    'medium_large' => __( 'Medium Large (768×auto)', 'bw' ),
                    'large'        => __( 'Large (1024×1024)', 'bw' ),
                    'custom_1200'  => __( 'Custom (1200×auto)', 'bw' ),
                    'custom_1500'  => __( 'Custom (1500×auto)', 'bw' ),
                    'full'         => __( 'Full Size (Original)', 'bw' ),
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_slide_controls() {
        $this->start_controls_section(
            'section_slide_settings',
            [
                'label'     => __( 'Slide Settings', 'bw' ),
                'condition' => [
                    'display_mode' => 'slide',
                ],
            ]
        );

        $this->add_control(
            'infinite_loop',
            [
                'label'        => __( 'Infinite Loop', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label'        => __( 'Autoplay', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label'     => __( 'Autoplay Speed (ms)', 'bw' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 3000,
                'min'       => 1000,
                'max'       => 10000,
                'step'      => 250,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'touch_drag',
            [
                'label'        => __( 'Touch Drag', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Allow swipe drag on touch devices.', 'bw' ),
            ]
        );

        $this->add_control(
            'mouse_drag',
            [
                'label'        => __( 'Mouse / Trackpad Drag', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Allow mouse drag and horizontal two-finger trackpad gestures on desktop.', 'bw' ),
            ]
        );

        $this->add_control(
            'slide_align',
            [
                'label'   => __( 'Slide Alignment', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'start',
                'options' => [
                    'start'  => __( 'Start', 'bw' ),
                    'center' => __( 'Center', 'bw' ),
                    'end'    => __( 'End', 'bw' ),
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_slide_breakpoints',
            [
                'label'     => __( 'Responsive Breakpoints', 'bw' ),
                'condition' => [
                    'display_mode' => 'slide',
                ],
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'breakpoint',
            [
                'label'   => __( 'Breakpoint (px)', 'bw' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1280,
            ]
        );

        $repeater->add_control(
            'slides_to_show',
            [
                'label'   => __( 'Slides to Show', 'bw' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 3,
                'min'     => 1,
                'max'     => 10,
            ]
        );

        $repeater->add_control(
            'slides_to_scroll',
            [
                'label'   => __( 'Slides to Scroll', 'bw' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1,
                'min'     => 1,
                'max'     => 10,
            ]
        );

        $repeater->add_control(
            'show_arrows',
            [
                'label'        => __( 'Show Arrows', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $repeater->add_control(
            'show_dots',
            [
                'label'        => __( 'Show Dots', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'start_offset_left',
            [
                'label'       => __( 'Start Offset Left', 'bw' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'px' ],
                'range'       => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 240,
                        'step' => 1,
                    ],
                ],
                'default'     => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'description' => __( 'Adds left breathing room before the first visible slide without changing the card ratio.', 'bw' ),
            ]
        );

        $repeater->add_control(
            'center_mode',
            [
                'label'        => __( 'Center Mode', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $repeater->add_control(
            'variable_width',
            [
                'label'        => __( 'Variable / Proportional Width', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'When enabled, each slide width follows the image proportion. Use a fixed height for editorial mixed-width strips.', 'bw' ),
            ]
        );

        $repeater->add_control(
            'slide_width',
            [
                'label'       => __( 'Slide Width (px)', 'bw' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => '',
                'min'         => 120,
                'max'         => 2000,
                'step'        => 10,
                'placeholder' => __( 'Auto', 'bw' ),
                'condition'   => [
                    'variable_width!' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'image_height_mode',
            [
                'label'   => __( 'Image Height Mode', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto'    => __( 'Scale / Original Ratio', 'bw' ),
                    'fixed'   => __( 'Fixed Height', 'bw' ),
                    'contain' => __( 'Contain', 'bw' ),
                    'cover'   => __( 'Cover', 'bw' ),
                ],
            ]
        );

        $repeater->add_control(
            'image_height',
            [
                'label'      => __( 'Image Height', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 100, 'max' => 1600, 'step' => 10 ],
                    'vh' => [ 'min' => 10, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 520,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'image_height_mode!' => 'auto',
                ],
            ]
        );

        $this->add_control(
            'slide_breakpoints',
            [
                'label'       => __( 'Breakpoints', 'bw' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    [
                        'breakpoint'       => 1280,
                        'slides_to_show'   => 3,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => 'yes',
                        'show_dots'        => '',
                        'start_offset_left'=> [
                            'size' => 0,
                            'unit' => 'px',
                        ],
                        'image_height_mode'=> 'auto',
                    ],
                    [
                        'breakpoint'       => 900,
                        'slides_to_show'   => 2,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => 'yes',
                        'show_dots'        => '',
                        'start_offset_left'=> [
                            'size' => 0,
                            'unit' => 'px',
                        ],
                        'image_height_mode'=> 'auto',
                    ],
                    [
                        'breakpoint'       => 640,
                        'slides_to_show'   => 1,
                        'slides_to_scroll' => 1,
                        'show_arrows'      => '',
                        'show_dots'        => 'yes',
                        'start_offset_left'=> [
                            'size' => 0,
                            'unit' => 'px',
                        ],
                        'image_height_mode'=> 'auto',
                    ],
                ],
                'title_field' => 'Breakpoint: {{{ breakpoint }}}px',
            ]
        );

        $this->end_controls_section();
    }

    private function register_wall_controls() {
        $this->start_controls_section(
            'section_wall_settings',
            [
                'label'     => __( 'Wall Settings', 'bw' ),
                'condition' => [
                    'display_mode' => 'wall',
                ],
            ]
        );

        $this->add_responsive_control(
            'wall_columns',
            [
                'label'          => __( 'Columns', 'bw' ),
                'type'           => Controls_Manager::SELECT,
                'default'        => '6',
                'tablet_default' => '4',
                'mobile_default' => '3',
                'options'        => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                ],
                'selectors'      => [
                    '{{WRAPPER}} .bw-basic-slide-wall-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ],
            ]
        );

        $this->add_responsive_control(
            'wall_height',
            [
                'label'      => __( 'Wall Height', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh', '%' ],
                'range'      => [
                    'px' => [ 'min' => 200, 'max' => 2000, 'step' => 10 ],
                    'vh' => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
                    '%'  => [ 'min' => 10, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 560,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-basic-slide-wall-shell' => 'max-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'wall_gradient',
            [
                'label'        => __( 'Bottom Gradient', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'bw' ),
                'label_off'    => __( 'No', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'wall_gradient_color',
            [
                'label'     => __( 'Gradient Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'condition' => [
                    'wall_gradient' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bw-basic-slide-wall-shell' => '--bw-bs-wall-gradient-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wall_gradient_height',
            [
                'label'      => __( 'Gradient Height', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh', '%' ],
                'range'      => [
                    'px' => [ 'min' => 20, 'max' => 400, 'step' => 1 ],
                    'vh' => [ 'min' => 2, 'max' => 60, 'step' => 1 ],
                    '%'  => [ 'min' => 2, 'max' => 60, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 140,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'wall_gradient' => 'yes',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-basic-slide-wall-shell' => '--bw-bs-wall-gradient-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function register_style_controls() {
        $this->start_controls_section(
            'section_style_images',
            [
                'label' => __( 'Images', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_gap',
            [
                'label'      => __( 'Gap', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vw' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 80, 'step' => 1 ],
                    'vw' => [ 'min' => 0, 'max' => 10, 'step' => 0.1 ],
                ],
                'default'    => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-basic-slide-wrapper' => '--bw-bs-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_radius',
            [
                'label'      => __( 'Image Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 60, 'step' => 1 ],
                    '%'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-basic-slide-wrapper' => '--bw-bs-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_navigation',
            [
                'label'     => __( 'Navigation', 'bw' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'display_mode' => 'slide',
                ],
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => __( 'Arrow Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bw-bs-arrow' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_size',
            [
                'label'      => __( 'Arrow Size', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 12, 'max' => 80, 'step' => 1 ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-bs-arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $gallery  = ! empty( $settings['gallery'] ) && is_array( $settings['gallery'] ) ? $settings['gallery'] : [];

        if ( empty( $gallery ) ) {
            return;
        }

        $mode      = ( $settings['display_mode'] ?? 'slide' ) === 'wall' ? 'wall' : 'slide';
        $widget_id = $this->get_id();
        $classes   = 'bw-basic-slide-wrapper bw-basic-slide-mode-' . $mode;

        if ( 'slide' === $mode ) {
            $classes .= ' loading';
        }

        $this->add_render_attribute(
            'wrapper',
            [
                'class'          => $classes,
                'data-widget-id' => $widget_id,
                'data-mode'      => $mode,
            ]
        );

        if ( 'slide' === $mode ) {
            $this->add_render_attribute( 'wrapper', 'data-config', wp_json_encode( $this->build_js_config( $settings ) ) );
        }

        ?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <?php
            if ( 'slide' === $mode ) {
                $this->render_slide_breakpoint_css( $settings );
                $this->render_slide_mode( $gallery, $settings );
            } else {
                $this->render_wall_mode( $gallery, $settings );
            }
            ?>
        </div>
        <?php
    }

    private function render_slide_mode( array $gallery, array $settings ) {
        $image_size    = $this->get_image_size( $settings['image_size'] ?? 'large' );
        $eager_count   = $this->get_slide_eager_count( $settings );
        $dots_position = 'center';
        ?>
        <div class="bw-bs-horizontal">
            <div class="bw-embla-viewport bw-bs-embla-viewport">
                <div class="bw-embla-container">
                    <?php foreach ( $gallery as $index => $image ) : ?>
                        <div class="bw-embla-slide bw-bs-slide" data-bw-index="<?php echo esc_attr( $index ); ?>">
                            <div class="bw-bs-media">
                                <?php echo $this->render_gallery_image( $image, $image_size, $index < $eager_count, 0 === $index, 'bw-bs-image bw-embla-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bw-bs-arrows-container">
                <button class="bw-bs-arrow bw-bs-arrow-prev" aria-label="<?php esc_attr_e( 'Previous', 'bw' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M6 8L2 12L6 16"/>
                        <path d="M2 12H22"/>
                    </svg>
                </button>
                <button class="bw-bs-arrow bw-bs-arrow-next" aria-label="<?php esc_attr_e( 'Next', 'bw' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M18 8L22 12L18 16"/>
                        <path d="M2 12H22"/>
                    </svg>
                </button>
            </div>

            <div class="bw-bs-dots-container bw-ps-dots-container bw-ps-dots-<?php echo esc_attr( $dots_position ); ?>"></div>
        </div>
        <?php
    }

    private function render_wall_mode( array $gallery, array $settings ) {
        $image_size      = $this->get_image_size( $settings['image_size'] ?? 'large' );
        $desktop_columns = max( 1, absint( $settings['wall_columns'] ?? 6 ) );
        $gradient_class  = ( $settings['wall_gradient'] ?? 'yes' ) === 'yes' ? 'has-bottom-gradient' : '';
        ?>
        <div class="bw-basic-slide-wall-shell <?php echo esc_attr( $gradient_class ); ?>">
            <div class="bw-basic-slide-wall-grid">
                <?php foreach ( $gallery as $index => $image ) : ?>
                    <div class="bw-basic-slide-wall-item">
                        <div class="bw-bs-media">
                            <?php echo $this->render_gallery_image( $image, $image_size, $index < $desktop_columns, 0 === $index, 'bw-bs-image bw-lazy-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function render_gallery_image( array $image, $image_size, $is_eager, $is_first, $class_name ) {
        $image_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;
        $image_url = isset( $image['url'] ) ? (string) $image['url'] : '';
        $attrs = [
            'class'         => $class_name,
            'loading'       => $is_eager ? 'eager' : 'lazy',
            'decoding'      => $is_first ? 'sync' : 'async',
            'fetchpriority' => $is_first ? 'high' : 'auto',
        ];

        if ( $image_id ) {
            return wp_get_attachment_image( $image_id, $image_size, false, $attrs );
        }

        if ( '' === $image_url ) {
            return '';
        }

        return sprintf(
            '<img class="%1$s" src="%2$s" alt="" loading="%3$s" decoding="%4$s" fetchpriority="%5$s">',
            esc_attr( $class_name ),
            esc_url( $image_url ),
            esc_attr( $attrs['loading'] ),
            esc_attr( $attrs['decoding'] ),
            esc_attr( $attrs['fetchpriority'] )
        );
    }

    private function build_js_config( array $settings ) {
        $responsive = [];
        if ( ! empty( $settings['slide_breakpoints'] ) && is_array( $settings['slide_breakpoints'] ) ) {
            foreach ( $settings['slide_breakpoints'] as $breakpoint ) {
                $responsive[] = [
                    'breakpoint'    => absint( $breakpoint['breakpoint'] ?? 0 ),
                    'slidesToScroll'=> max( 1, absint( $breakpoint['slides_to_scroll'] ?? 1 ) ),
                    'centerMode'    => ( $breakpoint['center_mode'] ?? '' ) === 'yes',
                    'variableWidth' => ( $breakpoint['variable_width'] ?? '' ) === 'yes',
                ];
            }
        }

        return [
            'mode' => 'slide',
            'horizontal' => [
                'infinite'        => ( $settings['infinite_loop'] ?? 'yes' ) === 'yes',
                'autoplay'        => ( $settings['autoplay'] ?? '' ) === 'yes',
                'autoplaySpeed'   => absint( $settings['autoplay_speed'] ?? 3000 ),
                'align'           => $settings['slide_align'] ?? 'start',
                'enableTouchDrag' => ( $settings['touch_drag'] ?? 'yes' ) === 'yes',
                'enableMouseDrag' => ( $settings['mouse_drag'] ?? 'yes' ) === 'yes',
                'responsive'      => $responsive,
            ],
        ];
    }

    private function render_slide_breakpoint_css( array $settings ) {
        $breakpoints = ! empty( $settings['slide_breakpoints'] ) && is_array( $settings['slide_breakpoints'] ) ? $settings['slide_breakpoints'] : [];
        $widget_id   = $this->get_id();
        $prefix      = '.elementor-element-' . esc_attr( $widget_id );
        $sel_slide   = $prefix . ' .bw-bs-slide';
        $sel_media   = $prefix . ' .bw-bs-media';
        $sel_img     = $prefix . ' .bw-bs-image';
        $sel_arrows  = $prefix . ' .bw-bs-arrows-container';
        $sel_dots    = $prefix . ' .bw-bs-dots-container';
        $sel_viewport= $prefix . ' .bw-bs-embla-viewport';

        if ( empty( $breakpoints ) ) {
            return;
        }

        usort(
            $breakpoints,
            function ( $a, $b ) {
                return absint( $b['breakpoint'] ?? 0 ) - absint( $a['breakpoint'] ?? 0 );
            }
        );

        $css = '<style>';
        $css .= $this->build_slide_breakpoint_rule( $breakpoints[0], $sel_slide, $sel_media, $sel_img, $sel_arrows, $sel_dots, $sel_viewport );

        foreach ( $breakpoints as $breakpoint ) {
            $bp_px = absint( $breakpoint['breakpoint'] ?? 0 );
            if ( $bp_px <= 0 ) {
                continue;
            }

            $css .= '@media (max-width:' . $bp_px . 'px){';
            $css .= $this->build_slide_breakpoint_rule( $breakpoint, $sel_slide, $sel_media, $sel_img, $sel_arrows, $sel_dots, $sel_viewport );
            $css .= '}';
        }

        $css .= '</style>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $css;
    }

    private function build_slide_breakpoint_rule( array $bp, $sel_slide, $sel_media, $sel_img, $sel_arrows, $sel_dots, $sel_viewport ) {
        $slides_to_show = max( 1, absint( $bp['slides_to_show'] ?? 1 ) );
        $variable_width = ( $bp['variable_width'] ?? '' ) === 'yes';
        $slide_width    = absint( $bp['slide_width'] ?? 0 );
        $height_mode    = sanitize_key( $bp['image_height_mode'] ?? 'auto' );
        $show_arrows    = ( $bp['show_arrows'] ?? 'yes' ) === 'yes';
        $show_dots      = ( $bp['show_dots'] ?? '' ) === 'yes';
        $start_offset_left = $bp['start_offset_left'] ?? null;
        $height_value   = '';

        if ( ! empty( $bp['image_height']['size'] ) ) {
            $height_value = (float) $bp['image_height']['size'] . ( $bp['image_height']['unit'] ?? 'px' );
        }

        if ( $variable_width ) {
            $slide_size = 'auto';
        } elseif ( $slide_width > 0 ) {
            $slide_size = $slide_width . 'px';
        } elseif ( $slides_to_show > 1 ) {
            $slide_size = 'calc(100% / ' . $slides_to_show . ')';
        } else {
            $slide_size = '100%';
        }

        $rule  = $sel_slide . '{flex:0 0 ' . $slide_size . ';}';
        if ( ! empty( $start_offset_left['size'] ) ) {
            $rule .= $sel_viewport . '{box-sizing:border-box;padding-left:' . (float) $start_offset_left['size'] . ( $start_offset_left['unit'] ?? 'px' ) . ';}';
        } else {
            $rule .= $sel_viewport . '{box-sizing:border-box;padding-left:0;}';
        }
        $rule .= $sel_arrows . '{display:' . ( $show_arrows ? 'flex' : 'none' ) . ';}';
        $rule .= $sel_dots . '{display:' . ( $show_dots ? 'flex' : 'none' ) . ';}';

        if ( 'auto' === $height_mode || '' === $height_value ) {
            $rule .= $sel_media . '{height:auto;}';
            $rule .= $sel_img . '{width:100%;height:auto;max-width:100%;object-fit:cover;}';
            return $rule;
        }

        if ( $variable_width ) {
            $rule .= $sel_slide . '{width:auto;}';
            if ( 'auto' === $height_mode || '' === $height_value ) {
                $rule .= $sel_media . '{display:inline-flex;width:auto;height:auto;}';
                $rule .= $sel_img . '{width:auto;height:auto;max-width:none;object-fit:contain;}';
                return $rule;
            }
            $rule .= $sel_media . '{display:inline-flex;width:auto;height:' . $height_value . ';}';
            $rule .= $sel_img . '{width:auto;height:100%;max-width:none;' . ( 'cover' === $height_mode ? 'object-fit:cover;' : 'object-fit:contain;' ) . '}';
            return $rule;
        }

        $rule .= $sel_media . '{height:' . $height_value . ';}';

        if ( 'cover' === $height_mode ) {
            $rule .= $sel_img . '{width:100%;height:100%;object-fit:cover;}';
        } elseif ( 'contain' === $height_mode ) {
            $rule .= $sel_img . '{width:100%;height:100%;object-fit:contain;}';
        } else {
            $rule .= $sel_img . '{width:auto;height:100%;max-width:100%;object-fit:contain;}';
        }

        return $rule;
    }

    private function get_slide_eager_count( array $settings ) {
        $breakpoints = ! empty( $settings['slide_breakpoints'] ) && is_array( $settings['slide_breakpoints'] ) ? $settings['slide_breakpoints'] : [];
        if ( empty( $breakpoints ) ) {
            return 1;
        }

        usort(
            $breakpoints,
            function ( $a, $b ) {
                return absint( $b['breakpoint'] ?? 0 ) - absint( $a['breakpoint'] ?? 0 );
            }
        );

        return max( 1, absint( $breakpoints[0]['slides_to_show'] ?? 1 ) );
    }

    private function get_image_size( $size_setting ) {
        switch ( $size_setting ) {
            case 'custom_1200':
                return [ 1200, 0 ];
            case 'custom_1500':
                return [ 1500, 0 ];
            default:
                return $size_setting;
        }
    }
}
