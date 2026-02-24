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
        add_filter( 'woocommerce_form_field', [ $this, 'remove_optional_label' ], 10, 4 );
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_consent_meta' ], 10, 2 );
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'maybe_subscribe_on_created' ], 20, 3 );
        add_action( 'woocommerce_order_status_processing', [ $this, 'maybe_subscribe_on_paid' ] );
        add_action( 'woocommerce_order_status_completed', [ $this, 'maybe_subscribe_on_paid' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 25 );
    }

    /**
     * Render the Contact heading above billing email.
     */
    public function render_contact_header() {
        if ( ! $this->should_apply() || $this->is_block_checkout() ) {
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
     * Inject newsletter checkbox after configured billing field.
     *
     * @param array $fields Checkout fields.
     *
     * @return array
     */
    public function inject_newsletter_field( $fields ) {
        if ( ! $this->should_apply() || $this->is_block_checkout() ) {
            return $fields;
        }

        $checkout_settings = $this->get_checkout_settings();
        if ( empty( $checkout_settings['enabled'] ) || empty( $fields['billing'] ) ) {
            return $fields;
        }

        $after_key = ! empty( $checkout_settings['placement_after_key'] )
            ? sanitize_key( $checkout_settings['placement_after_key'] )
            : 'billing_email';

        $anchor_priority = isset( $fields['billing'][ $after_key ]['priority'] )
            ? absint( $fields['billing'][ $after_key ]['priority'] )
            : ( isset( $fields['billing']['billing_email']['priority'] ) ? absint( $fields['billing']['billing_email']['priority'] ) : 110 );

        $offset = isset( $checkout_settings['priority_offset'] ) ? intval( $checkout_settings['priority_offset'] ) : 5;
        $offset = max( -50, min( 50, $offset ) );

        $label = $checkout_settings['label_text']
            ? $checkout_settings['label_text']
            : __( 'Email me with news and offers', 'bw' );

        $fields['billing']['bw_subscribe_newsletter'] = [
            'type'     => 'checkbox',
            'label'    => $label,
            'required' => false,
            'priority' => max( 1, $anchor_priority + $offset ),
            'default'  => ! empty( $checkout_settings['default_checked'] ) ? 1 : 0,
            'class'    => [ 'form-row-wide', 'bw-checkout-newsletter' ],
            'clear'    => true,
        ];

        if ( ! empty( $checkout_settings['privacy_text'] ) ) {
            $fields['billing']['bw_subscribe_newsletter']['description'] = $checkout_settings['privacy_text'];
        }

        return $fields;
    }

    /**
     * Remove optional label from newsletter checkbox only.
     *
     * @param string $field Field HTML.
     * @param string $key   Field key.
     *
     * @return string
     */
    public function remove_optional_label( $field, $key ) {
        if ( 'bw_subscribe_newsletter' !== $key ) {
            return $field;
        }

        $field = preg_replace(
            '/<span\s+class=["\']optional["\'][^>]*>\s*\([^)]*\)\s*<\/span>/i',
            '',
            $field
        );

        return $field;
    }

    /**
     * Save checkout consent metadata.
     *
     * @param int $order_id Order ID.
     */
    public function save_consent_meta( $order_id ) {
        if ( $this->is_block_checkout() ) {
            return;
        }

        $checkout_settings = $this->get_checkout_settings();
        if ( empty( $checkout_settings['enabled'] ) ) {
            return;
        }

        $opt_in = ! empty( $_POST['bw_subscribe_newsletter'] ) ? 1 : 0;

        update_post_meta( $order_id, '_bw_subscribe_newsletter', $opt_in );
        update_post_meta( $order_id, '_bw_subscribe_consent_source', 'checkout' );

        if ( $opt_in ) {
            update_post_meta( $order_id, '_bw_subscribe_consent_at', current_time( 'mysql' ) );
        } else {
            update_post_meta( $order_id, '_bw_brevo_subscribed', 'skipped' );
            delete_post_meta( $order_id, '_bw_brevo_error_last' );
        }
    }

    /**
     * Subscribe on order creation if configured.
     *
     * @param int      $order_id Order ID.
     * @param array    $posted   Posted checkout data.
     * @param WC_Order $order    Order object.
     */
    public function maybe_subscribe_on_created( $order_id, $posted, $order ) {
        $checkout_settings = $this->get_checkout_settings();
        if ( empty( $checkout_settings['enabled'] ) || 'created' !== $checkout_settings['subscribe_timing'] ) {
            return;
        }

        $this->process_subscription( $order );
    }

    /**
     * Subscribe on paid order status if configured.
     *
     * @param int $order_id Order ID.
     */
    public function maybe_subscribe_on_paid( $order_id ) {
        $checkout_settings = $this->get_checkout_settings();
        if ( empty( $checkout_settings['enabled'] ) || 'paid' !== $checkout_settings['subscribe_timing'] ) {
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

        $checkout_settings = $this->get_checkout_settings();
        if ( empty( $checkout_settings['enabled'] ) ) {
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
     * Execute subscription flow for checkout order.
     *
     * @param WC_Order $order Order object.
     */
    private function process_subscription( $order ) {
        if ( ! $order instanceof WC_Order || $this->is_block_checkout() ) {
            return;
        }

        $opt_in = $order->get_meta( '_bw_subscribe_newsletter', true );
        if ( empty( $opt_in ) ) {
            $order->update_meta_data( '_bw_brevo_subscribed', 'skipped' );
            $order->save();
            $this->log_event( 'info', 'Skipping subscribe: no explicit consent.', $order, '', 'skipped' );
            return;
        }

        $already = (string) $order->get_meta( '_bw_brevo_subscribed', true );
        if ( in_array( $already, [ 'subscribed', 'pending', '1' ], true ) ) {
            return;
        }

        $general_settings = $this->get_general_settings();
        $checkout_settings = $this->get_checkout_settings();

        if ( empty( $general_settings['api_key'] ) || empty( $general_settings['list_id'] ) ) {
            $this->mark_error( $order, __( 'Brevo settings missing API key or list ID.', 'bw' ) );
            return;
        }

        $email = $order->get_billing_email();
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->mark_error( $order, __( 'Invalid billing email for newsletter subscription.', 'bw' ) );
            return;
        }

        if ( ! class_exists( 'BW_Brevo_Client' ) ) {
            $this->mark_error( $order, __( 'Brevo client unavailable.', 'bw' ), $email );
            return;
        }

        $client = new BW_Brevo_Client( $general_settings['api_key'], BW_Mail_Marketing_Settings::API_BASE_URL );

        if ( $this->is_contact_blocklisted( $client, $email, $general_settings ) ) {
            $order->update_meta_data( '_bw_brevo_subscribed', 'skipped' );
            $order->save();
            $this->log_event( 'info', 'Skipping subscribe: contact is unsubscribed/blocklisted.', $order, $email, 'skipped' );
            return;
        }

        $attributes = $this->build_contact_attributes( $order, $general_settings );
        $mode = $this->resolve_optin_mode( $general_settings, $checkout_settings );

        if ( 'double_opt_in' === $mode ) {
            if ( empty( $general_settings['double_optin_template_id'] ) || empty( $general_settings['double_optin_redirect_url'] ) ) {
                $this->mark_error( $order, __( 'Double opt-in requires template ID and redirect URL.', 'bw' ), $email );
                return;
            }

            $sender = [];
            if ( ! empty( $general_settings['sender_email'] ) ) {
                $sender['email'] = $general_settings['sender_email'];
            }
            if ( ! empty( $general_settings['sender_name'] ) ) {
                $sender['name'] = $general_settings['sender_name'];
            }

            $result = $client->send_double_opt_in(
                $email,
                absint( $general_settings['double_optin_template_id'] ),
                $general_settings['double_optin_redirect_url'],
                [ absint( $general_settings['list_id'] ) ],
                $attributes,
                $sender
            );

            if ( empty( $result['success'] ) ) {
                $this->mark_error( $order, $this->extract_error_message( $result, 'Brevo double opt-in failed.' ), $email );
                return;
            }

            $order->update_meta_data( '_bw_brevo_subscribed', 'pending' );
            $order->delete_meta_data( '_bw_brevo_error_last' );
            $order->save();
            $this->log_event( 'info', 'Brevo double opt-in request sent.', $order, $email, 'pending' );
            return;
        }

        $result = $client->upsert_contact(
            $email,
            $attributes,
            [ absint( $general_settings['list_id'] ) ]
        );

        if ( empty( $result['success'] ) ) {
            $this->mark_error( $order, $this->extract_error_message( $result, 'Brevo subscribe failed.' ), $email );
            return;
        }

        $order->update_meta_data( '_bw_brevo_subscribed', 'subscribed' );
        $order->delete_meta_data( '_bw_brevo_error_last' );
        $order->save();
        $this->log_event( 'info', 'Brevo contact subscribed.', $order, $email, 'subscribed' );
    }

    /**
     * Build attributes payload according to General settings.
     *
     * @param WC_Order $order            Order object.
     * @param array    $general_settings General settings.
     *
     * @return array
     */
    private function build_contact_attributes( $order, $general_settings ) {
        $attributes = [];

        if ( ! empty( $general_settings['sync_first_name'] ) ) {
            $first_name = trim( (string) $order->get_billing_first_name() );
            if ( '' !== $first_name ) {
                $attributes['FIRSTNAME'] = $first_name;
            }
        }

        if ( ! empty( $general_settings['sync_last_name'] ) ) {
            $last_name = trim( (string) $order->get_billing_last_name() );
            if ( '' !== $last_name ) {
                $attributes['LASTNAME'] = $last_name;
            }
        }

        return $attributes;
    }

    /**
     * Resolve checkout opt-in mode from channel + general settings.
     *
     * @param array $general_settings  General settings.
     * @param array $checkout_settings Checkout settings.
     *
     * @return string
     */
    private function resolve_optin_mode( $general_settings, $checkout_settings ) {
        $channel_mode = isset( $checkout_settings['channel_optin_mode'] ) ? $checkout_settings['channel_optin_mode'] : 'inherit';
        if ( in_array( $channel_mode, [ 'single_opt_in', 'double_opt_in' ], true ) ) {
            return $channel_mode;
        }

        return ( isset( $general_settings['default_optin_mode'] ) && 'double_opt_in' === $general_settings['default_optin_mode'] )
            ? 'double_opt_in'
            : 'single_opt_in';
    }

    /**
     * Guard against auto-resubscribing blocked/unsubscribed contacts.
     *
     * @param BW_Brevo_Client $client           Brevo client.
     * @param string          $email            Contact email.
     * @param array           $general_settings General settings.
     *
     * @return bool
     */
    private function is_contact_blocklisted( $client, $email, $general_settings ) {
        if ( empty( $general_settings['resubscribe_policy'] ) || 'no_auto_resubscribe' !== $general_settings['resubscribe_policy'] ) {
            return false;
        }

        $result = $client->get_contact( $email );
        if ( ! empty( $result['success'] ) && ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
            if ( ! empty( $result['data']['emailBlacklisted'] ) ) {
                return true;
            }
        }

        if ( isset( $result['code'] ) && 404 === (int) $result['code'] ) {
            return false;
        }

        return false;
    }

    /**
     * Persist an error state in order meta and logger.
     *
     * @param WC_Order $order   Order object.
     * @param string   $message Error message.
     * @param string   $email   Email.
     */
    private function mark_error( $order, $message, $email = '' ) {
        $order->update_meta_data( '_bw_brevo_subscribed', 'error' );
        $order->update_meta_data( '_bw_brevo_error_last', $message );
        $order->save();

        $this->log_event( 'error', $message, $order, $email, 'error' );
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
     * Retrieve General mail marketing settings.
     *
     * @return array
     */
    private function get_general_settings() {
        if ( class_exists( 'BW_Mail_Marketing_Settings' ) ) {
            return BW_Mail_Marketing_Settings::get_general_settings();
        }

        // Fallback in case admin class was not loaded.
        return [
            'api_key'                   => '',
            'api_base'                  => 'https://api.brevo.com/v3',
            'list_id'                   => 0,
            'default_optin_mode'        => 'single_opt_in',
            'double_optin_template_id'  => 0,
            'double_optin_redirect_url' => '',
            'sender_name'               => '',
            'sender_email'              => '',
            'debug_logging'             => 0,
            'resubscribe_policy'        => 'no_auto_resubscribe',
            'sync_first_name'           => 1,
            'sync_last_name'            => 1,
        ];
    }

    /**
     * Retrieve Checkout channel settings.
     *
     * @return array
     */
    private function get_checkout_settings() {
        if ( class_exists( 'BW_Mail_Marketing_Settings' ) ) {
            return BW_Mail_Marketing_Settings::get_checkout_settings();
        }

        return [
            'enabled'             => 1,
            'default_checked'     => 0,
            'label_text'          => __( 'Email me with news and offers', 'bw' ),
            'privacy_text'        => '',
            'subscribe_timing'    => 'paid',
            'channel_optin_mode'  => 'inherit',
            'placement_after_key' => 'billing_email',
            'priority_offset'     => 5,
        ];
    }

    /**
     * Detect if checkout page uses WooCommerce blocks.
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
     * Resolve error message from client result.
     *
     * @param array  $result Result payload.
     * @param string $fallback Fallback text.
     *
     * @return string
     */
    private function extract_error_message( $result, $fallback ) {
        if ( ! empty( $result['error'] ) ) {
            return sanitize_text_field( (string) $result['error'] );
        }

        return $fallback;
    }

    /**
     * Write event/error logs with standardized context.
     *
     * @param string   $level   Logger level.
     * @param string   $message Message.
     * @param WC_Order $order   Order.
     * @param string   $email   Email.
     * @param string   $result  Result state.
     */
    private function log_event( $level, $message, $order = null, $email = '', $result = 'info' ) {
        if ( ! function_exists( 'wc_get_logger' ) ) {
            return;
        }

        $general_settings = $this->get_general_settings();
        $debug_enabled = ! empty( $general_settings['debug_logging'] );

        $context = [
            'source'  => 'bw-brevo',
            'result'  => $result,
            'context' => ( $order instanceof WC_Order && (int) $order->get_user_id() > 0 ) ? 'checkout_user' : 'checkout_guest',
            'debug'   => $debug_enabled ? 1 : 0,
        ];

        if ( $order instanceof WC_Order ) {
            $context['order_id'] = $order->get_id();
            if ( '' === $email ) {
                $email = (string) $order->get_billing_email();
            }
        }

        if ( '' !== $email ) {
            $context['email'] = sanitize_email( $email );
        }

        $logger = wc_get_logger();
        if ( 'error' === $level ) {
            $logger->error( $message, $context );
            return;
        }

        $logger->info( $message, $context );
    }
}
