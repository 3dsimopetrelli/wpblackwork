<?php
/**
 * Checkout subscribe settings (Brevo) admin module.
 *
 * Settings are stored in a single option: bw_checkout_subscribe_settings.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BW_Checkout_Subscribe_Admin {
    const OPTION_NAME = 'bw_checkout_subscribe_settings';
    const OPTION_VERSION = 1;

    /**
     * @var BW_Checkout_Subscribe_Admin|null
     */
    private static $instance = null;

    /**
     * Initialize admin hooks.
     */
    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get instance.
     *
     * @return BW_Checkout_Subscribe_Admin
     */
    public static function get_instance() {
        return self::init();
    }

    private function __construct() {
        add_action( 'admin_init', [ $this, 'handle_post' ] );
        add_action( 'wp_ajax_bw_brevo_test_connection', [ $this, 'handle_test_connection' ] );
    }

    /**
     * Handle settings save.
     */
    public function handle_post() {
        if ( empty( $_POST['bw_checkout_subscribe_submit'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( 'bw_checkout_subscribe_save', 'bw_checkout_subscribe_nonce' );

        $settings = [
            'version'                   => self::OPTION_VERSION,
            'enabled'                   => ! empty( $_POST['bw_checkout_subscribe_enabled'] ) ? 1 : 0,
            'default_checked'           => ! empty( $_POST['bw_checkout_subscribe_default_checked'] ) ? 1 : 0,
            'label_text'                => isset( $_POST['bw_checkout_subscribe_label_text'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_checkout_subscribe_label_text'] ) ) : '',
            'privacy_text'              => isset( $_POST['bw_checkout_subscribe_privacy_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bw_checkout_subscribe_privacy_text'] ) ) : '',
            'api_key'                   => isset( $_POST['bw_checkout_subscribe_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_checkout_subscribe_api_key'] ) ) : '',
            'api_base'                  => isset( $_POST['bw_checkout_subscribe_api_base'] ) ? esc_url_raw( wp_unslash( $_POST['bw_checkout_subscribe_api_base'] ) ) : 'https://api.brevo.com/v3',
            'list_id'                   => isset( $_POST['bw_checkout_subscribe_list_id'] ) ? absint( $_POST['bw_checkout_subscribe_list_id'] ) : 0,
            'double_optin_enabled'      => ! empty( $_POST['bw_checkout_subscribe_double_optin_enabled'] ) ? 1 : 0,
            'double_optin_template_id'  => isset( $_POST['bw_checkout_subscribe_double_optin_template_id'] ) ? absint( $_POST['bw_checkout_subscribe_double_optin_template_id'] ) : 0,
            'double_optin_redirect_url' => isset( $_POST['bw_checkout_subscribe_double_optin_redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['bw_checkout_subscribe_double_optin_redirect_url'] ) ) : '',
            'sender_name'               => isset( $_POST['bw_checkout_subscribe_sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_checkout_subscribe_sender_name'] ) ) : '',
            'sender_email'              => isset( $_POST['bw_checkout_subscribe_sender_email'] ) ? sanitize_email( wp_unslash( $_POST['bw_checkout_subscribe_sender_email'] ) ) : '',
            'subscribe_timing'           => isset( $_POST['bw_checkout_subscribe_timing'] ) ? sanitize_key( wp_unslash( $_POST['bw_checkout_subscribe_timing'] ) ) : 'created',
        ];

        if ( empty( $settings['label_text'] ) ) {
            $settings['label_text'] = __( 'Email me with news and offers', 'bw' );
        }

        if ( ! in_array( $settings['subscribe_timing'], [ 'created', 'paid' ], true ) ) {
            $settings['subscribe_timing'] = 'created';
        }

        update_option( self::OPTION_NAME, $settings );

        $redirect_args = [
            'page'         => 'blackwork-site-settings',
            'tab'          => 'checkout',
            'checkout_tab' => 'subscribe',
            'bw_checkout_subscribe_saved' => '1',
        ];

        wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * AJAX handler to test Brevo connection.
     */
    public function handle_test_connection() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'bw' ) ] );
        }

        check_ajax_referer( 'bw_checkout_subscribe_test', 'nonce' );

        $api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $api_base = isset( $_POST['api_base'] ) ? esc_url_raw( wp_unslash( $_POST['api_base'] ) ) : 'https://api.brevo.com/v3';

        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => __( 'API key is required.', 'bw' ) ] );
        }

        if ( ! class_exists( 'BW_Brevo_Client' ) ) {
            wp_send_json_error( [ 'message' => __( 'Brevo client is unavailable.', 'bw' ) ] );
        }

        $client = new BW_Brevo_Client( $api_key, $api_base );
        $result = $client->get_account();

        if ( empty( $result['success'] ) ) {
            $message = isset( $result['error'] ) ? $result['error'] : __( 'Unable to connect to Brevo.', 'bw' );
            wp_send_json_error( [ 'message' => $message ] );
        }

        wp_send_json_success( [ 'message' => __( 'Connection successful.', 'bw' ) ] );
    }

    /**
     * Render the Subscribe tab.
     */
    public function render_tab() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();

        if ( isset( $_GET['bw_checkout_subscribe_saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bw_checkout_subscribe_saved'] ) ) ) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Subscribe settings saved.', 'bw' ) . '</strong></p></div>';
        }

        if ( $this->is_block_checkout() ) {
            echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Subscribe settings target the classic checkout form. The Checkout Block is detected and will not be affected.', 'bw' ) . '</strong></p></div>';
        }
        ?>

        <input type="hidden" name="bw_checkout_subscribe_form" value="1" />
        <?php wp_nonce_field( 'bw_checkout_subscribe_save', 'bw_checkout_subscribe_nonce' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable newsletter checkbox', 'bw' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="bw_checkout_subscribe_enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
                        <?php esc_html_e( 'Show the newsletter opt-in on checkout.', 'bw' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Default checked', 'bw' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="bw_checkout_subscribe_default_checked" value="1" <?php checked( $settings['default_checked'], 1 ); ?> />
                        <?php esc_html_e( 'Pre-check the opt-in checkbox.', 'bw' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_label_text"><?php esc_html_e( 'Checkbox label', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_subscribe_label_text" name="bw_checkout_subscribe_label_text" value="<?php echo esc_attr( $settings['label_text'] ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_privacy_text"><?php esc_html_e( 'Privacy text', 'bw' ); ?></label>
                </th>
                <td>
                    <textarea id="bw_checkout_subscribe_privacy_text" name="bw_checkout_subscribe_privacy_text" rows="3" class="large-text"><?php echo esc_textarea( $settings['privacy_text'] ); ?></textarea>
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e( 'Brevo API connection', 'bw' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_api_key"><?php esc_html_e( 'Brevo API key', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="password" id="bw_checkout_subscribe_api_key" name="bw_checkout_subscribe_api_key" value="<?php echo esc_attr( $settings['api_key'] ); ?>" class="regular-text" autocomplete="new-password" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_api_base"><?php esc_html_e( 'API base URL', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="url" id="bw_checkout_subscribe_api_base" name="bw_checkout_subscribe_api_base" value="<?php echo esc_attr( $settings['api_base'] ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Default: https://api.brevo.com/v3', 'bw' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Test connection', 'bw' ); ?></th>
                <td>
                    <button type="button" class="button" id="bw-brevo-test-connection"><?php esc_html_e( 'Test connection', 'bw' ); ?></button>
                    <span class="bw-brevo-test-result" aria-live="polite"></span>
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e( 'Audience', 'bw' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_list_id"><?php esc_html_e( 'List ID', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="number" id="bw_checkout_subscribe_list_id" name="bw_checkout_subscribe_list_id" value="<?php echo esc_attr( $settings['list_id'] ); ?>" class="small-text" min="0" />
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e( 'Double opt-in', 'bw' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable double opt-in', 'bw' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="bw_checkout_subscribe_double_optin_enabled" value="1" <?php checked( $settings['double_optin_enabled'], 1 ); ?> />
                        <?php esc_html_e( 'Send confirmation email via Brevo.', 'bw' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_double_optin_template_id"><?php esc_html_e( 'Template ID', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="number" id="bw_checkout_subscribe_double_optin_template_id" name="bw_checkout_subscribe_double_optin_template_id" value="<?php echo esc_attr( $settings['double_optin_template_id'] ); ?>" class="small-text" min="0" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_double_optin_redirect_url"><?php esc_html_e( 'Confirmation redirect URL', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="url" id="bw_checkout_subscribe_double_optin_redirect_url" name="bw_checkout_subscribe_double_optin_redirect_url" value="<?php echo esc_attr( $settings['double_optin_redirect_url'] ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_sender_name"><?php esc_html_e( 'Sender name', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="text" id="bw_checkout_subscribe_sender_name" name="bw_checkout_subscribe_sender_name" value="<?php echo esc_attr( $settings['sender_name'] ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_sender_email"><?php esc_html_e( 'Sender email', 'bw' ); ?></label>
                </th>
                <td>
                    <input type="email" id="bw_checkout_subscribe_sender_email" name="bw_checkout_subscribe_sender_email" value="<?php echo esc_attr( $settings['sender_email'] ); ?>" class="regular-text" />
                </td>
            </tr>
        </table>

        <h3><?php esc_html_e( 'Subscription timing', 'bw' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_checkout_subscribe_timing"><?php esc_html_e( 'Subscribe on', 'bw' ); ?></label>
                </th>
                <td>
                    <select id="bw_checkout_subscribe_timing" name="bw_checkout_subscribe_timing">
                        <option value="created" <?php selected( $settings['subscribe_timing'], 'created' ); ?>><?php esc_html_e( 'Order created (checkout submit)', 'bw' ); ?></option>
                        <option value="paid" <?php selected( $settings['subscribe_timing'], 'paid' ); ?>><?php esc_html_e( 'Order paid (processing/completed)', 'bw' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Subscribe settings', 'bw' ), 'primary', 'bw_checkout_subscribe_submit' ); ?>
        <?php
    }

    /**
     * Get saved settings with defaults.
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
}
