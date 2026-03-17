<?php
use Elementor\Controls_Manager;
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
            'show_name_field',
            [
                'label'        => __( 'Show name field', 'bw' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'bw' ),
                'label_off'    => __( 'Off', 'bw' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if ( ! class_exists( 'BW_Mail_Marketing_Settings' ) ) {
            return;
        }

        if ( wp_style_is( 'bw-newsletter-subscription-style', 'registered' ) ) {
            wp_enqueue_style( 'bw-newsletter-subscription-style' );
        }

        if ( wp_script_is( 'bw-newsletter-subscription-script', 'registered' ) ) {
            wp_enqueue_script( 'bw-newsletter-subscription-script' );
        }

        $widget_settings = $this->get_settings_for_display();
        $settings = BW_Mail_Marketing_Settings::get_subscription_settings();
        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();
        $show_name_field = ! isset( $widget_settings['show_name_field'] ) || 'yes' === $widget_settings['show_name_field'];
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
        ?>
        <div class="bw-newsletter-subscription-widget<?php echo empty( $settings['enabled'] ) ? ' is-disabled-preview' : ''; ?>" id="<?php echo esc_attr( $widget_id ); ?>">
            <?php if ( empty( $settings['enabled'] ) && $is_editor ) : ?>
                <div class="bw-newsletter-subscription-preview-notice">
                    <?php esc_html_e( 'This widget is currently disabled in Mail Marketing > Subscription, but it remains visible here for layout preview.', 'bw' ); ?>
                </div>
            <?php endif; ?>

            <form
                class="bw-newsletter-subscription-form"
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
                </div>

                <button class="bw-newsletter-subscription-button" type="submit" aria-disabled="false">
                    <span class="bw-newsletter-subscription-button__label"><?php echo esc_html( $button_text ); ?></span>
                </button>

                <div
                    id="<?php echo esc_attr( $message_id ); ?>"
                    class="bw-newsletter-subscription-message"
                    aria-live="polite"
                    aria-atomic="true"
                    role="status"
                ></div>
            </form>
        </div>
        <?php
    }
}
