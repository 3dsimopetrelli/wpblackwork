<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Go_To_App_Widget extends Widget_Base {
    public function get_name() {
        return 'bw-go-to-app';
    }

    public function get_title() {
        return __( 'Go to App', 'bw' );
    }

    public function get_icon() {
        return 'eicon-external-link-square';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        if ( ! wp_style_is( 'bw-go-to-app-style', 'registered' ) && function_exists( 'bw_register_go_to_app_widget_assets' ) ) {
            bw_register_go_to_app_widget_assets();
        }

        return [ 'bw-go-to-app-style' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
            ]
        );

        $this->add_control(
            'content_text',
            [
                'label'       => __( 'Text', 'bw' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Open Blackwork App for full-resolution files and vector-ready use.', 'bw' ),
                'placeholder' => __( 'Enter the card copy', 'bw' ),
                'rows'        => 4,
                'label_block' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'       => __( 'Button Text', 'bw' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Open App', 'bw' ),
                'placeholder' => __( 'Open App', 'bw' ),
                'label_block' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'button_link',
            [
                'label'       => __( 'Button Link', 'bw' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://your-app-link.com', 'bw' ),
                'dynamic'     => [ 'active' => true ],
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_card',
            [
                'label' => __( 'Card', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ececec',
                'selectors' => [
                    '{{WRAPPER}} .bw-go-to-app' => 'background-color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 80, 'step' => 1 ],
                ],
                'default'    => [ 'size' => 22, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-go-to-app' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_text',
            [
                'label' => __( 'Text', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'content_typography',
                'selector' => '{{WRAPPER}} .bw-go-to-app__text',
            ]
        );

        $this->add_control(
            'content_text_color',
            [
                'label'     => __( 'Text Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}} .bw-go-to-app__text' => 'color: {{VALUE}};',
                ],
                'render_type' => 'ui',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .bw-go-to-app__button-label',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $text = ! empty( $settings['content_text'] ) ? (string) $settings['content_text'] : '';
        $button_text = ! empty( $settings['button_text'] ) ? (string) $settings['button_text'] : __( 'Open App', 'bw' );
        $link = ! empty( $settings['button_link']['url'] ) ? $settings['button_link'] : [];
        $button_tag = ! empty( $link['url'] ) ? 'a' : 'div';

        $this->add_render_attribute(
            'button',
            [
                'class' => 'bw-go-to-app__button',
            ]
        );

        if ( 'a' === $button_tag ) {
            $this->add_link_attributes( 'button', $link );
        }
        ?>
        <div class="bw-go-to-app">
            <?php if ( '' !== trim( wp_strip_all_tags( $text ) ) ) : ?>
                <div class="bw-go-to-app__text"><?php echo wp_kses_post( nl2br( esc_html( $text ) ) ); ?></div>
            <?php endif; ?>

            <<?php echo esc_html( $button_tag ); ?> <?php echo $this->get_render_attribute_string( 'button' ); ?>>
                <span class="bw-go-to-app__button-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 5H19V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 14L19 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 14V19H5V5H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="bw-go-to-app__button-label"><?php echo esc_html( $button_text ); ?></span>
            </<?php echo esc_html( $button_tag ); ?>>
        </div>
        <?php
    }
}
