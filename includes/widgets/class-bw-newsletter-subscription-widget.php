<?php
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Newsletter_Subscription_Widget extends Widget_Base {

    public function get_name() {
        return 'bw-newsletter-subscription';
    }

    public function get_title() {
        return __( 'Newsletter Subscription', 'bw' );
    }

    public function get_icon() {
        return 'eicon-mail';
    }

    public function get_categories() {
        return [ 'blackwork' ];
    }

    public function get_style_depends() {
        return [ 'bw-newsletter-subscription-style' ];
    }

    public function get_script_depends() {
        return [ 'bw-newsletter-subscription-script' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'bw' ),
            ]
        );

        $this->add_control(
            'style_variant',
            [
                'label'   => __( 'Style', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'footer',
                'options' => [
                    'footer'  => __( 'Style Footer', 'bw' ),
                    'section' => __( 'Style Section', 'bw' ),
                ],
            ]
        );

        $this->add_control(
            'show_name_field',
            [
                'label'        => __( 'Show name field', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw' ),
                'label_off'    => __( 'Off', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'button_inside_email_field',
            [
                'label'        => __( 'Button inside email field', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw' ),
                'label_off'    => __( 'Off', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'style_variant' => 'footer',
                ],
            ]
        );

        $this->add_control(
            'privacy_custom_text_enabled',
            [
                'label'        => __( 'Custom privacy text', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw' ),
                'label_off'    => __( 'Off', 'bw' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'privacy_custom_text',
            [
                'label'       => __( 'Privacy Text', 'bw' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 4,
                'default'     => '',
                'description' => __( 'HTML allowed. Used only when Custom privacy text is enabled.', 'bw' ),
                'condition'   => [
                    'privacy_custom_text_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'footer_title',
            [
                'label'     => __( 'Title', 'bw' ),
                'type'      => Controls_Manager::TEXTAREA,
                'rows'      => 2,
                'default'   => __( 'PRIVATE ACCESS TO NEW RELEASES', 'bw' ),
                'description' => __( 'HTML allowed. Used when Style Footer is selected.', 'bw' ),
                'condition' => [
                    'style_variant' => 'footer',
                ],
            ]
        );

        $this->add_control(
            'footer_subtitle',
            [
                'label'     => __( 'Subtitle', 'bw' ),
                'type'      => Controls_Manager::TEXTAREA,
                'rows'      => 3,
                'default'   => __( 'Early access to rare books, prints, and curated selections. No noise. Only what matters.', 'bw' ),
                'description' => __( 'HTML allowed. Used when Style Footer is selected.', 'bw' ),
                'condition' => [
                    'style_variant' => 'footer',
                ],
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label'     => __( 'Title', 'bw' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'Step Inside the Archive', 'bw' ),
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_subtitle',
            [
                'label'     => __( 'Subtitle', 'bw' ),
                'type'      => Controls_Manager::TEXTAREA,
                'default'   => __( 'Get free sample files, early access to new collections, and rare finds from our archive.', 'bw' ),
                'rows'      => 3,
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_background_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#050505',
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_height',
            [
                'label'      => __( 'Section Height', 'bw' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'vh' ],
                'range'      => [
                    'vh' => [
                        'min'  => 40,
                        'max'  => 140,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'size' => 72,
                    'unit' => 'vh',
                ],
                'condition'  => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_background_image',
            [
                'label'     => __( 'Background Image', 'bw' ),
                'type'      => Controls_Manager::MEDIA,
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_background_image_position',
            [
                'label'     => __( 'Background Image Position', 'bw' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'left',
                'options'   => [
                    'left'   => __( 'Left', 'bw' ),
                    'center' => __( 'Center', 'bw' ),
                    'right'  => __( 'Right', 'bw' ),
                ],
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_background_image_fit',
            [
                'label'     => __( 'Background Image Fit', 'bw' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'contain',
                'options'   => [
                    'contain' => __( 'Contain', 'bw' ),
                    'cover'   => __( 'Cover', 'bw' ),
                ],
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_widget',
            [
                'label' => __( 'Wrap', 'bw' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'widget_padding',
            [
                'label'      => __( 'Padding', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh' ],
                'selectors'  => [
                    '{{WRAPPER}} .bw-newsletter-subscription-shell' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'widget_text_color',
            [
                'label'     => __( 'Background Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-widget-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'widget_border_radius',
            [
                'label'      => __( 'Border Radius', 'bw' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh' ],
                'selectors'  => [
                    '{{WRAPPER}}' => '--bw-ns-widget-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'footer_title_color',
            [
                'label'     => __( 'Title Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(111, 111, 111, 0.11)',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-footer-title-color: {{VALUE}};',
                ],
                'condition' => [
                    'style_variant' => 'footer',
                ],
            ]
        );

        $this->add_control(
            'footer_subtitle_color',
            [
                'label'     => __( 'Description Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#080808',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-footer-subtitle-color: {{VALUE}};',
                ],
                'condition' => [
                    'style_variant' => 'footer',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_section',
            [
                'label'     => __( 'Style Section', 'bw' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'style_variant' => 'section',
                ],
            ]
        );

        $this->add_control(
            'section_content_position',
            [
                'label'   => __( 'Content Position', 'bw' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => [
                    'left'   => __( 'Left', 'bw' ),
                    'center' => __( 'Center', 'bw' ),
                    'right'  => __( 'Right', 'bw' ),
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'section_title_typography',
                'label'          => __( 'Title Typography', 'bw' ),
                'selector'       => '{{WRAPPER}} .bw-newsletter-subscription-section-title',
                'fields_options' => [
                    'font_weight' => [
                        'default' => '500',
                    ],
                    'font_style' => [
                        'default' => 'normal',
                    ],
                ],
            ]
        );

        $this->add_control(
            'section_title_color',
            [
                'label'     => __( 'Title Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#F7F7F2',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-section-title-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'section_subtitle_typography',
                'label'          => __( 'Subtitle Typography', 'bw' ),
                'selector'       => '{{WRAPPER}} .bw-newsletter-subscription-section-subtitle, {{WRAPPER}} .bw-newsletter-subscription-section-subtitle p',
                'fields_options' => [
                    'font_weight' => [
                        'default' => '400',
                    ],
                    'font_style' => [
                        'default' => 'normal',
                    ],
                ],
            ]
        );

        $this->add_control(
            'section_subtitle_color',
            [
                'label'     => __( 'Subtitle Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(247, 247, 242, 0.86)',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-section-subtitle-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_privacy_typography',
                'label'    => __( 'Privacy Typography', 'bw' ),
                'selector' => '{{WRAPPER}} .bw-newsletter-subscription-consent, {{WRAPPER}} .bw-newsletter-subscription-consent__text, {{WRAPPER}} .bw-newsletter-subscription-consent__label, {{WRAPPER}} .bw-newsletter-subscription-consent__link',
            ]
        );

        $this->add_control(
            'section_privacy_color',
            [
                'label'     => __( 'Privacy Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(247, 247, 242, 0.84)',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-section-privacy-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'section_overlay_color',
            [
                'label'     => __( 'Overlay Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(8, 8, 8, 0.82)',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-overlay-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'section_glow_color',
            [
                'label'     => __( 'Glow Color', 'bw' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(128, 253, 3, 0.16)',
                'selectors' => [
                    '{{WRAPPER}}' => '--bw-ns-glow-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'section_overlay_opacity',
            [
                'label'     => __( 'Overlay Opacity', 'bw' ),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 0,
                'max'       => 1,
                'step'      => 0.01,
                'default'   => 1,
                'selectors' => [
                    '{{WRAPPER}}'                                           => '--bw-ns-overlay-opacity: {{VALUE}};',
                    '{{WRAPPER}} .bw-newsletter-subscription-section-overlay' => 'opacity: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if ( ! class_exists( 'BW_Mail_Marketing_Settings' ) ) {
            return;
        }

        $widget_settings = $this->get_settings_for_display();
        $raw_widget_settings = $this->get_data( 'settings' );
        if ( ! is_array( $raw_widget_settings ) ) {
            $raw_widget_settings = [];
        }
        $settings = BW_Mail_Marketing_Settings::get_subscription_settings();
        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();
        $style_variant = isset( $widget_settings['style_variant'] ) ? sanitize_key( $widget_settings['style_variant'] ) : 'footer';
        if ( ! in_array( $style_variant, [ 'footer', 'section' ], true ) ) {
            $style_variant = 'footer';
        }

        $show_name_field = $this->resolve_show_name_field_visibility( $widget_settings, $raw_widget_settings, $style_variant );
        $button_inside_email_field = 'footer' === $style_variant && $this->is_widget_switch_enabled( $widget_settings['button_inside_email_field'] ?? '' );
        $privacy_custom_text_enabled = $this->is_widget_switch_enabled( $widget_settings['privacy_custom_text_enabled'] ?? '' );
        $privacy_custom_text = '';
        if ( $privacy_custom_text_enabled && ! empty( $widget_settings['privacy_custom_text'] ) ) {
            $privacy_custom_text = trim( (string) $widget_settings['privacy_custom_text'] );
        }
        $name_label   = ! empty( $settings['name_label'] ) ? $settings['name_label'] : __( 'Name', 'bw' );
        $email_label  = ! empty( $settings['email_label'] ) ? $settings['email_label'] : __( 'Email address', 'bw' );
        $consent_text = ! empty( $settings['consent_prefix'] ) ? $settings['consent_prefix'] : __( 'I agree to the', 'bw' );
        $consent_required = ! isset( $settings['consent_required'] ) || ! empty( $settings['consent_required'] );

        if ( empty( $settings['enabled'] ) && ! $is_editor ) {
            return;
        }

        if ( $is_editor && class_exists( 'BW_Mail_Marketing_Settings' ) ) {
            $general_settings = BW_Mail_Marketing_Settings::get_general_settings();
            if ( empty( $general_settings['api_key'] ) ) {
                echo '<div class="bw-newsletter-subscription-preview-notice" style="margin-bottom:12px;">';
                esc_html_e( 'Brevo API key is not configured. Set it in Mail Marketing > General before this widget can submit.', 'bw' );
                echo '</div>';
            }
        }

        $privacy_url = ! empty( $settings['privacy_url'] )
            ? esc_url_raw( (string) $settings['privacy_url'] )
            : ( function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '' );
        $widget_id = 'bw-mm-subscription-' . esc_attr( $this->get_id() );
        $message_id = $widget_id . '-message';
        $consent_id = $widget_id . '-privacy';
        $button_text = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Subscribe', 'bw' );
        $footer_title = isset( $widget_settings['footer_title'] ) ? wp_kses_post( $widget_settings['footer_title'] ) : __( 'PRIVATE ACCESS TO NEW RELEASES', 'bw' );
        $footer_subtitle = isset( $widget_settings['footer_subtitle'] ) ? wp_kses_post( $widget_settings['footer_subtitle'] ) : __( 'Early access to rare books, prints, and curated selections. No noise. Only what matters.', 'bw' );
        $section_title = isset( $widget_settings['section_title'] ) ? wp_kses_post( $widget_settings['section_title'] ) : __( 'Step Inside the Archive', 'bw' );
        $section_subtitle = isset( $widget_settings['section_subtitle'] ) ? wp_kses_post( $widget_settings['section_subtitle'] ) : __( 'Get free sample files, early access to new collections, and rare finds from our archive.', 'bw' );
        $section_background_color = '#050505';
        if ( ! empty( $widget_settings['section_background_color'] ) ) {
            $section_background_color = $this->sanitize_widget_color_value( $widget_settings['section_background_color'], '#050505' );
        }
        $section_height = 72;
        if ( isset( $widget_settings['section_height']['size'] ) && '' !== $widget_settings['section_height']['size'] ) {
            $section_height = max( 40, min( 140, (int) $widget_settings['section_height']['size'] ) );
        }

        $section_background_image = '';
        if ( ! empty( $widget_settings['section_background_image']['url'] ) ) {
            $section_background_image = esc_url_raw( $widget_settings['section_background_image']['url'] );
        }

        $section_image_position = isset( $widget_settings['section_background_image_position'] ) ? sanitize_key( $widget_settings['section_background_image_position'] ) : 'left';
        if ( ! in_array( $section_image_position, [ 'left', 'center', 'right' ], true ) ) {
            $section_image_position = 'left';
        }

        $section_image_fit = isset( $widget_settings['section_background_image_fit'] ) ? sanitize_key( $widget_settings['section_background_image_fit'] ) : 'contain';
        if ( ! in_array( $section_image_fit, [ 'contain', 'cover' ], true ) ) {
            $section_image_fit = 'contain';
        }

        $section_content_position = isset( $widget_settings['section_content_position'] ) ? sanitize_key( $widget_settings['section_content_position'] ) : 'center';
        if ( ! in_array( $section_content_position, [ 'left', 'center', 'right' ], true ) ) {
            $section_content_position = 'center';
        }

        $widget_classes = [
            'bw-newsletter-subscription-widget',
            'bw-newsletter-subscription-widget--' . $style_variant,
        ];
        if ( $button_inside_email_field ) {
            $widget_classes[] = 'bw-newsletter-subscription-widget--button-inline';
        }

        $widget_style = '';
        if ( 'section' === $style_variant ) {
            $widget_style = sprintf(
                '--bw-ns-section-bg:%1$s; --bw-ns-section-height:%2$dvh; --bw-ns-section-image-fit:%3$s;',
                esc_attr( $section_background_color ?: '#050505' ),
                $section_height,
                esc_attr( $section_image_fit )
            );
        }

        $art_style = '';
        if ( '' !== $section_background_image ) {
            $art_style = sprintf( 'background-image:url(%s);', esc_url( $section_background_image ) );
        }
        ?>
        <div
            class="<?php echo esc_attr( implode( ' ', $widget_classes ) . ( empty( $settings['enabled'] ) ? ' is-disabled-preview' : '' ) ); ?>"
            id="<?php echo esc_attr( $widget_id ); ?>"
            <?php if ( 'section' === $style_variant ) : ?>
                data-section-art-position="<?php echo esc_attr( $section_image_position ); ?>"
                data-section-content-position="<?php echo esc_attr( $section_content_position ); ?>"
            <?php endif; ?>
            <?php if ( '' !== $widget_style ) : ?>
                style="<?php echo esc_attr( $widget_style ); ?>"
            <?php endif; ?>
        >
            <div class="bw-newsletter-subscription-shell">
                <?php if ( empty( $settings['enabled'] ) && $is_editor ) : ?>
                    <div class="bw-newsletter-subscription-preview-notice">
                        <?php esc_html_e( 'This widget is currently disabled in Mail Marketing > Subscription, but it remains visible here for layout preview.', 'bw' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( 'section' === $style_variant && '' !== $section_background_image ) : ?>
                    <div class="bw-newsletter-subscription-section-art" aria-hidden="true" style="<?php echo esc_attr( $art_style ); ?>"></div>
                <?php endif; ?>

                <?php if ( 'section' === $style_variant ) : ?>
                    <div class="bw-newsletter-subscription-section-overlay" aria-hidden="true"></div>
                <?php endif; ?>

                <?php if ( 'footer' === $style_variant && ( '' !== trim( wp_strip_all_tags( $footer_title ) ) || '' !== trim( wp_strip_all_tags( $footer_subtitle ) ) ) ) : ?>
                    <div class="bw-newsletter-subscription-footer-copy">
                        <?php if ( '' !== trim( wp_strip_all_tags( $footer_title ) ) ) : ?>
                            <h3 class="bw-newsletter-subscription-footer-title"><?php echo wp_kses_post( $footer_title ); ?></h3>
                        <?php endif; ?>

                        <?php if ( '' !== trim( wp_strip_all_tags( $footer_subtitle ) ) ) : ?>
                            <div class="bw-newsletter-subscription-footer-subtitle"><?php echo wpautop( wp_kses_post( $footer_subtitle ) ); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form
                    class="<?php echo esc_attr( 'bw-newsletter-subscription-form' . ( $button_inside_email_field ? ' is-button-inline' : '' ) ); ?>"
                    method="post"
                    novalidate
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'bw_mail_marketing_subscription_submit' ) ); ?>"
                    data-consent-required="<?php echo $consent_required ? '1' : '0'; ?>"
                >
                    <noscript>
                        <p class="bw-newsletter-subscription-noscript">
                            <?php esc_html_e( 'JavaScript is required to submit this form.', 'bw' ); ?>
                        </p>
                    </noscript>

                    <?php if ( 'section' === $style_variant && ( '' !== trim( wp_strip_all_tags( $section_title ) ) || '' !== trim( wp_strip_all_tags( $section_subtitle ) ) ) ) : ?>
                        <div class="bw-newsletter-subscription-section-copy">
                            <?php if ( '' !== trim( wp_strip_all_tags( $section_title ) ) ) : ?>
                                <h2 class="bw-newsletter-subscription-section-title"><?php echo wp_kses_post( $section_title ); ?></h2>
                            <?php endif; ?>

                            <?php if ( '' !== trim( wp_strip_all_tags( $section_subtitle ) ) ) : ?>
                                <div class="bw-newsletter-subscription-section-subtitle"><?php echo wpautop( wp_kses_post( $section_subtitle ) ); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_name_field ) : ?>
                        <div class="bw-newsletter-subscription-field">
                            <label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-name' ); ?>">
                                <?php echo esc_html( $name_label ); ?>
                            </label>
                            <input
                                id="<?php echo esc_attr( $widget_id . '-name' ); ?>"
                                class="bw-newsletter-subscription-input"
                                type="text"
                                name="name"
                                autocomplete="name"
                                placeholder="<?php echo esc_attr( $name_label ); ?>"
                            />
                        </div>
                    <?php endif; ?>

                    <?php if ( 'section' === $style_variant || $button_inside_email_field ) : ?>
                        <div class="bw-newsletter-subscription-inline">
                            <div class="bw-newsletter-subscription-field bw-newsletter-subscription-field--email">
                                <label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-email' ); ?>">
                                    <?php echo esc_html( $email_label ); ?>
                                </label>
                                <input
                                    id="<?php echo esc_attr( $widget_id . '-email' ); ?>"
                                    class="bw-newsletter-subscription-input"
                                    type="email"
                                    name="email"
                                    autocomplete="email"
                                    placeholder="<?php echo esc_attr( $email_label ); ?>"
                                    aria-describedby="<?php echo esc_attr( $message_id ); ?>"
                                    aria-invalid="false"
                                    required
                                />
                            </div>

                            <button class="bw-newsletter-subscription-button" type="submit" aria-disabled="false">
                                <span class="bw-newsletter-subscription-button__label"><?php echo esc_html( $button_text ); ?></span>
                            </button>
                        </div>
                    <?php else : ?>
                        <div class="bw-newsletter-subscription-field">
                            <label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-email' ); ?>">
                                <?php echo esc_html( $email_label ); ?>
                            </label>
                            <input
                                id="<?php echo esc_attr( $widget_id . '-email' ); ?>"
                                class="bw-newsletter-subscription-input"
                                type="email"
                                name="email"
                                autocomplete="email"
                                placeholder="<?php echo esc_attr( $email_label ); ?>"
                                aria-describedby="<?php echo esc_attr( $message_id ); ?>"
                                aria-invalid="false"
                                required
                            />
                        </div>
                    <?php endif; ?>

                    <div class="bw-newsletter-subscription-consent">
                        <input
                            id="<?php echo esc_attr( $consent_id ); ?>"
                            class="bw-newsletter-subscription-consent__checkbox"
                            type="checkbox"
                            name="privacy"
                            value="1"
                            aria-describedby="<?php echo esc_attr( $message_id ); ?>"
                            aria-invalid="false"
                            <?php echo $consent_required ? 'required' : ''; ?>
                        />
                        <?php if ( '' !== $privacy_custom_text ) : ?>
                            <span class="bw-newsletter-subscription-consent__text bw-newsletter-subscription-consent__text--custom">
                                <?php echo wp_kses_post( $privacy_custom_text ); ?>
                            </span>
                        <?php else : ?>
                            <span class="bw-newsletter-subscription-consent__text">
                                <label class="bw-newsletter-subscription-consent__label" for="<?php echo esc_attr( $consent_id ); ?>">
                                    <?php echo esc_html( $consent_text ); ?>
                                </label>
                                <?php
                                $privacy_link_label = ! empty( $settings['privacy_link_label'] )
                                    ? $settings['privacy_link_label']
                                    : __( 'Privacy Policy', 'bw' );
                                ?>
                                <?php if ( ! empty( $privacy_url ) ) : ?>
                                    <a class="bw-newsletter-subscription-consent__link" href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html( $privacy_link_label ); ?>
                                    </a>
                                <?php elseif ( ! empty( $privacy_link_label ) ) : ?>
                                    <span class="bw-newsletter-subscription-consent__link">
                                        <?php echo esc_html( $privacy_link_label ); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ( 'footer' === $style_variant && ! $button_inside_email_field ) : ?>
                        <button class="bw-newsletter-subscription-button" type="submit" aria-disabled="false">
                            <span class="bw-newsletter-subscription-button__label"><?php echo esc_html( $button_text ); ?></span>
                        </button>
                    <?php endif; ?>

                    <div
                        id="<?php echo esc_attr( $message_id ); ?>"
                        class="bw-newsletter-subscription-message"
                        aria-live="polite"
                        aria-atomic="true"
                        role="status"
                    ></div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Resolve name field visibility from canonical + legacy widget settings.
     *
     * Backward compatibility:
     * - legacy footer widgets used `show_name_field`
     * - legacy section widgets used `section_show_name_field`
     * - unsaved/legacy section widgets without either key should remain hidden
     *
     * @param array  $widget_settings     Settings merged for display.
     * @param array  $raw_widget_settings Raw saved widget settings.
     * @param string $style_variant       Active style variant.
     *
     * @return bool
     */
    private function resolve_show_name_field_visibility( $widget_settings, $raw_widget_settings, $style_variant ) {
        $widget_settings = is_array( $widget_settings ) ? $widget_settings : [];
        $raw_widget_settings = is_array( $raw_widget_settings ) ? $raw_widget_settings : [];

        if ( 'section' === $style_variant ) {
            if ( array_key_exists( 'section_show_name_field', $widget_settings ) ) {
                return $this->is_widget_switch_enabled( $widget_settings['section_show_name_field'] );
            }

            if ( array_key_exists( 'section_show_name_field', $raw_widget_settings ) ) {
                return $this->is_widget_switch_enabled( $raw_widget_settings['section_show_name_field'] );
            }

            return false;
        }

        if ( array_key_exists( 'show_name_field', $widget_settings ) ) {
            return $this->is_widget_switch_enabled( $widget_settings['show_name_field'] );
        }

        if ( array_key_exists( 'show_name_field', $raw_widget_settings ) ) {
            return $this->is_widget_switch_enabled( $raw_widget_settings['show_name_field'] );
        }

        return false;
    }

    /**
     * Normalize Elementor switcher values to a boolean state.
     *
     * Elementor commonly stores enabled switchers as `yes`, but older saved
     * widgets or editor states can surface equivalent truthy values.
     *
     * @param mixed $value Switcher value.
     *
     * @return bool
     */
    private function is_widget_switch_enabled( $value ) {
        if ( true === $value ) {
            return true;
        }

        if ( false === $value || null === $value ) {
            return false;
        }

        if ( is_int( $value ) || is_float( $value ) ) {
            return 0 !== (int) $value;
        }

        if ( ! is_string( $value ) ) {
            return false;
        }

        $value = strtolower( trim( $value ) );

        return in_array( $value, [ 'yes', '1', 'true', 'on' ], true );
    }

    /**
     * Sanitize Elementor color control values for safe inline CSS usage.
     *
     * Accepts the formats the control can realistically emit for this widget:
     * - hex / hex8
     * - rgb()
     * - rgba()
     * - transparent
     *
     * Falls back to the provided default when the value is not recognized.
     *
     * @param string $value    Raw color value.
     * @param string $fallback Fallback color.
     *
     * @return string
     */
    private function sanitize_widget_color_value( $value, $fallback ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        $fallback = is_string( $fallback ) && '' !== trim( $fallback ) ? trim( $fallback ) : '#050505';

        if ( '' === $value ) {
            return $fallback;
        }

        $hex_color = sanitize_hex_color( $value );
        if ( is_string( $hex_color ) && '' !== $hex_color ) {
            return $hex_color;
        }

        if ( preg_match( '/^rgba?\(\s*(\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(0|1|0?\.\d+))?\s*\)$/i', $value ) ) {
            return preg_replace( '/\s+/', '', $value );
        }

        if ( 'transparent' === strtolower( $value ) ) {
            return 'transparent';
        }

        return $fallback;
    }
}
