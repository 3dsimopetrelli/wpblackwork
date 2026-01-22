<?php
/**
 * Checkout subscribe frontend handler.
 *
 * Hooks used:
 * - woocommerce_before_checkout_billing_form: render Contact header.
 * - woocommerce_checkout_fields: inject newsletter checkbox.
 * - woocommerce_checkout_update_order_meta: store consent.
 * - woocommerce_checkout_order_processed: subscribe on checkout submit.
 * - woocommerce_order_status_processing/completed: subscribe on paid.
 * - wp_enqueue_scripts: enqueue checkout subscribe styles.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Checkout_Subscribe_Frontend {
    const OPTION_NAME = 'bw_checkout_subscribe_settings';
    const OPTION_VERSION = 1;

    /**
     * Initialize hooks.
     */
    public static function init() {
        $instance = new self();
        return $instance;
    }

    private function __construct() {
        add_action( 'woocommerce_before_checkout_billing_form', [ $this, 'render_contact_header' ], 5 );
        add_filter( 'woocommerce_checkout_fields', [ $this, 'inject_newsletter_field' ], 30, 1 );
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_consent_meta' ], 10, 2 );
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'maybe_subscribe_on_created' ], 20, 3 );
        add_action( 'woocommerce_order_status_processing', [ $this, 'maybe_subscribe_on_paid' ] );
        add_action( 'woocommerce_order_status_completed', [ $this, 'maybe_subscribe_on_paid' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 25 );
        add_filter( 'woocommerce_form_field', [ $this, 'remove_optional_marker' ], 10, 4 );
    }

    /**
     * Render the Contact header above billing email.
     */
    public function render_contact_header() {
        if ( ! $this->should_apply() ) {
            return;
        }

        if ( $this->is_block_checkout() ) {
            return;
        }

        $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
        ?>
        <div class="bw-checkout-section-heading bw-checkout-section-heading--contact">
            <h2 class="checkout-section-title checkout-contact-title"><?php esc_html_e( 'Contact', 'bw' ); ?></h2>
            <?php if ( ! is_user_logged_in() && $account_url ) : ?>
                <a class="bw-checkout-section-link" href="<?php echo esc_url( $account_url ); ?>">
                    <?php esc_html_e( 'Sign in', 'bw' ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }


    /**
     * Inject newsletter checkbox after billing email.
     *
     * @param array $fields Checkout fields.
     *
     * @return array
     */
    public function inject_newsletter_field( $fields ) {
        if ( ! $this->should_apply() ) {
            return $fields;
        }

        if ( $this->is_block_checkout() ) {
            return $fields;
        }

        $settings = $this->get_settings();
        if ( empty( $settings['enabled'] ) ) {
            return $fields;
        }

        if ( empty( $fields['billing'] ) ) {
            return $fields;
        }

        $email_priority = isset( $fields['billing']['billing_email']['priority'] ) ? absint( $fields['billing']['billing_email']['priority'] ) : 110;
        $label          = $settings['label_text'] ? $settings['label_text'] : __( 'Email me with news and offers', 'bw' );

        $fields['billing']['bw_subscribe_newsletter'] = [
            'type'     => 'checkbox',
            'label'    => $label,
            'required' => false,
            'priority' => $email_priority + 5,
            'default'  => ! empty( $settings['default_checked'] ) ? 1 : 0,
            'class'    => [ 'form-row-wide', 'bw-checkout-newsletter' ],
            'clear'    => true,
        ];

        if ( ! empty( $settings['privacy_text'] ) ) {
            $fields['billing']['bw_subscribe_newsletter']['description'] = $settings['privacy_text'];
        }

        return $fields;
    }

    /**
     * Save consent metadata on checkout.
     *
     * @param int   $order_id Order ID.
     * @param array $data     Posted data.
     */
    public function save_consent_meta( $order_id, $data ) {
        if ( $this->is_block_checkout() ) {
            return;
        }

        $settings = $this->get_settings();
        if ( empty( $settings['enabled'] ) ) {
            return;
        }

        $opt_in = ! empty( $_POST['bw_subscribe_newsletter'] ) ? 1 : 0;

        update_post_meta( $order_id, '_bw_subscribe_newsletter', $opt_in );

        if ( $opt_in ) {
            update_post_meta( $order_id, '_bw_subscribe_consent_at', current_time( 'mysql' ) );
            update_post_meta( $order_id, '_bw_subscribe_consent_source', 'checkout' );
        }
    }

    /**
     * Subscribe on order creation if configured.
     *
     * @param int   $order_id Order ID.
     * @param array $posted   Posted data.
     * @param WC_Order $order Order object.
     */
    public function maybe_subscribe_on_created( $order_id, $posted, $order ) {
        $settings = $this->get_settings();
        if ( empty( $settings['enabled'] ) || 'created' !== $settings['subscribe_timing'] ) {
            return;
        }

        $this->process_subscription( $order );
    }

    /**
     * Subscribe on order paid if configured.
     *
     * @param int $order_id Order ID.
     */
    public function maybe_subscribe_on_paid( $order_id ) {
        $settings = $this->get_settings();
        if ( empty( $settings['enabled'] ) || 'paid' !== $settings['subscribe_timing'] ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $this->process_subscription( $order );
    }

    /**
     * Enqueue checkout subscribe styles.
     */
    public function enqueue_assets() {
        if ( ! $this->should_apply() || $this->is_block_checkout() ) {
            return;
        }

        $css_file = BW_MEW_PATH . 'assets/css/bw-checkout-subscribe.css';
        $version  = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

        wp_enqueue_style(
            'bw-checkout-subscribe',
            BW_MEW_URL . 'assets/css/bw-checkout-subscribe.css',
            [ 'bw-checkout' ],
            $version
        );
    }

    /**
     * Process subscription logic and call Brevo.
     *
     * @param WC_Order $order Order object.
     */
    private function process_subscription( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        if ( $this->is_block_checkout() ) {
            return;
        }

        $opt_in = $order->get_meta( '_bw_subscribe_newsletter', true );
        if ( empty( $opt_in ) ) {
            return;
        }

        $already = $order->get_meta( '_bw_brevo_subscribed', true );
        if ( ! empty( $already ) ) {
            return;
        }

        $settings = $this->get_settings();
        if ( empty( $settings['api_key'] ) || empty( $settings['list_id'] ) ) {
            $this->log_error( 'Brevo settings missing API key or list ID.', $order );
            return;
        }

        $email = $order->get_billing_email();
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( 'Invalid email address for subscription.', $order );
            return;
        }

        if ( ! class_exists( 'BW_Brevo_Client' ) ) {
            $this->log_error( 'Brevo client unavailable.', $order );
            return;
        }

        $client = new BW_Brevo_Client( $settings['api_key'], $settings['api_base'] );
        $attributes = [];

        if ( ! empty( $settings['double_optin_enabled'] ) ) {
            if ( empty( $settings['double_optin_template_id'] ) || empty( $settings['double_optin_redirect_url'] ) ) {
                $this->log_error( 'Double opt-in enabled but template ID or redirect URL missing.', $order );
                return;
            }

            $sender = [];
            if ( ! empty( $settings['sender_email'] ) ) {
                $sender['email'] = $settings['sender_email'];
            }
            if ( ! empty( $settings['sender_name'] ) ) {
                $sender['name'] = $settings['sender_name'];
            }

            $result = $client->send_double_opt_in(
                $email,
                $settings['double_optin_template_id'],
                $settings['double_optin_redirect_url'],
                [ $settings['list_id'] ],
                $attributes,
                $sender
            );

            if ( empty( $result['success'] ) ) {
                $this->log_error( 'Brevo double opt-in failed: ' . ( isset( $result['error'] ) ? $result['error'] : 'Unknown error' ), $order );
                return;
            }

            $order->update_meta_data( '_bw_brevo_subscribed', 'pending' );
            $order->save();
            return;
        }

        $result = $client->upsert_contact( $email, $attributes, [ $settings['list_id'] ] );
        if ( empty( $result['success'] ) ) {
            $this->log_error( 'Brevo subscribe failed: ' . ( isset( $result['error'] ) ? $result['error'] : 'Unknown error' ), $order );
            return;
        }

        $order->update_meta_data( '_bw_brevo_subscribed', '1' );
        $order->save();
    }

    /**
     * Determine if settings should apply on checkout.
     *
     * @return bool
     */
    private function should_apply() {
        if ( ! function_exists( 'is_checkout' ) ) {
            return false;
        }

        if ( is_admin() && ! wp_doing_ajax() ) {
            return false;
        }

        return is_checkout();
    }

    /**
     * Get settings with defaults.
     *
     * @return array
     */
    private function get_settings() {
        $defaults = [
            'version'                   => self::OPTION_VERSION,
            'enabled'                   => 1,
            'default_checked'           => 1,
            'label_text'                => __( 'Email me with news and offers', 'bw' ),
            'privacy_text'              => '',
            'api_key'                   => '',
            'api_base'                  => 'https://api.brevo.com/v3',
            'list_id'                   => 0,
            'double_optin_enabled'      => 0,
            'double_optin_template_id'  => 0,
            'double_optin_redirect_url' => '',
            'sender_name'               => '',
            'sender_email'              => '',
            'subscribe_timing'           => 'created',
        ];

        $settings = get_option( self::OPTION_NAME, $defaults );
        if ( ! is_array( $settings ) ) {
            return $defaults;
        }

        return array_merge( $defaults, $settings );
    }


    /**
     * Detect if the checkout page uses the WooCommerce Checkout block.
     *
     * @return bool
     */
    private function is_block_checkout() {
        if ( ! function_exists( 'wc_get_page_id' ) || ! function_exists( 'has_block' ) ) {
            return false;
        }

        $checkout_page_id = wc_get_page_id( 'checkout' );
        if ( ! $checkout_page_id ) {
            return false;
        }

        $post = get_post( $checkout_page_id );
        if ( ! $post ) {
            return false;
        }

        return has_block( 'woocommerce/checkout', $post->post_content );
    }

    /**
     * Log errors to WooCommerce logger.
     *
     * @param string   $message Error message.
     * @param WC_Order $order   Order object.
     */
    private function log_error( $message, $order = null ) {
        if ( ! function_exists( 'wc_get_logger' ) ) {
            return;
        }

        $context = [ 'source' => 'bw-brevo' ];
        if ( $order instanceof WC_Order ) {
            $context['order_id'] = $order->get_id();
        }

        wc_get_logger()->error( $message, $context );
    }
}
