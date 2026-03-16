<?php
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
        // V1 intentionally ships without Elementor editor controls.
    }

    protected function render() {
        if ( ! class_exists( 'BW_Mail_Marketing_Settings' ) ) {
            return;
        }

        $settings = BW_Mail_Marketing_Settings::get_subscription_settings();
        $is_editor = class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ( empty( $settings['enabled'] ) && ! $is_editor ) {
            return;
        }

        $privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
        $widget_id = 'bw-mm-subscription-' . esc_attr( $this->get_id() );
        $button_text = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Subscribe', 'bw' );
        ?>
        <div class="bw-newsletter-subscription-widget<?php echo empty( $settings['enabled'] ) ? ' is-disabled-preview' : ''; ?>" id="<?php echo esc_attr( $widget_id ); ?>">
            <?php if ( empty( $settings['enabled'] ) && $is_editor ) : ?>
                <div class="bw-newsletter-subscription-preview-notice">
                    <?php esc_html_e( 'This widget is currently disabled in Mail Marketing > Subscription, but it remains visible here for layout preview.', 'bw' ); ?>
                </div>
            <?php endif; ?>

            <form class="bw-newsletter-subscription-form" method="post" novalidate data-nonce="<?php echo esc_attr( wp_create_nonce( 'bw_mail_marketing_subscription_submit' ) ); ?>">
                <div class="bw-newsletter-subscription-field">
                    <label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-name' ); ?>">
                        <?php echo esc_html( $settings['name_label'] ); ?>
                    </label>
                    <input
                        id="<?php echo esc_attr( $widget_id . '-name' ); ?>"
                        class="bw-newsletter-subscription-input"
                        type="text"
                        name="name"
                        autocomplete="name"
                    />
                </div>

                <div class="bw-newsletter-subscription-field">
                    <label class="bw-newsletter-subscription-label" for="<?php echo esc_attr( $widget_id . '-email' ); ?>">
                        <?php echo esc_html( $settings['email_label'] ); ?>
                    </label>
                    <input
                        id="<?php echo esc_attr( $widget_id . '-email' ); ?>"
                        class="bw-newsletter-subscription-input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                    />
                </div>

                <label class="bw-newsletter-subscription-consent">
                    <input class="bw-newsletter-subscription-consent__checkbox" type="checkbox" name="privacy" value="1" required />
                    <span class="bw-newsletter-subscription-consent__text">
                        <?php echo esc_html( $settings['consent_prefix'] ); ?>
                        <?php if ( ! empty( $privacy_url ) ) : ?>
                            <a class="bw-newsletter-subscription-consent__link" href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html( $settings['privacy_link_label'] ); ?>
                            </a>
                        <?php else : ?>
                            <span class="bw-newsletter-subscription-consent__link">
                                <?php echo esc_html( $settings['privacy_link_label'] ); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </label>

                <button class="bw-newsletter-subscription-button" type="submit">
                    <span class="bw-newsletter-subscription-button__label"><?php echo esc_html( $button_text ); ?></span>
                </button>

                <div class="bw-newsletter-subscription-message" aria-live="polite"></div>
            </form>
        </div>
        <?php
    }
}
