<?php
/**
 * Mail Marketing admin module (Brevo) + legacy bridge.
 *
 * New options:
 * - bw_mail_marketing_general_settings
 * - bw_mail_marketing_checkout_settings
 *
 * Legacy option (fallback/migration):
 * - bw_checkout_subscribe_settings
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Mail_Marketing_Settings' ) ) {
    class BW_Mail_Marketing_Settings {
        const GENERAL_OPTION = 'bw_mail_marketing_general_settings';
        const CHECKOUT_OPTION = 'bw_mail_marketing_checkout_settings';
        const LEGACY_OPTION = 'bw_checkout_subscribe_settings';
        const API_BASE_URL = 'https://api.brevo.com/v3';
        const VERSION = 1;

        /**
         * Bootstrap hooks.
         */
        public static function init() {
            add_action( 'admin_init', [ __CLASS__, 'maybe_migrate_legacy_settings' ], 5 );
        }

        /**
         * Migrate legacy subscribe option to split mail marketing options.
         */
        public static function maybe_migrate_legacy_settings() {
            $legacy = get_option( self::LEGACY_OPTION, null );
            if ( ! is_array( $legacy ) || empty( $legacy ) ) {
                return;
            }

            $general = get_option( self::GENERAL_OPTION, null );
            $checkout = get_option( self::CHECKOUT_OPTION, null );

            $has_general = is_array( $general ) && ! empty( $general );
            $has_checkout = is_array( $checkout ) && ! empty( $checkout );

            if ( $has_general && $has_checkout ) {
                return;
            }

            if ( ! $has_general ) {
                update_option( self::GENERAL_OPTION, self::map_legacy_to_general( $legacy ) );
            }

            if ( ! $has_checkout ) {
                update_option( self::CHECKOUT_OPTION, self::map_legacy_to_checkout( $legacy ) );
            }
        }

        /**
         * Defaults for General settings.
         *
         * @return array
         */
        public static function get_general_defaults() {
            return [
                'version'                     => self::VERSION,
                'api_key'                     => '',
                'api_base'                    => self::API_BASE_URL,
                'list_id'                     => 0,
                'default_optin_mode'          => 'single_opt_in',
                'double_optin_template_id'    => 0,
                'double_optin_redirect_url'   => '',
                'sender_name'                 => '',
                'sender_email'                => '',
                'debug_logging'               => 0,
                'resubscribe_policy'          => 'no_auto_resubscribe',
                'sync_first_name'             => 1,
                'sync_last_name'              => 1,
            ];
        }

        /**
         * Defaults for Checkout channel settings.
         *
         * @return array
         */
        public static function get_checkout_defaults() {
            return [
                'version'             => self::VERSION,
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
         * Retrieve General settings with fallback.
         *
         * @return array
         */
        public static function get_general_settings() {
            $defaults = self::get_general_defaults();
            $settings = get_option( self::GENERAL_OPTION, null );

            if ( is_array( $settings ) && ! empty( $settings ) ) {
                return array_merge( $defaults, $settings, [ 'api_base' => self::API_BASE_URL ] );
            }

            $legacy = get_option( self::LEGACY_OPTION, null );
            if ( is_array( $legacy ) && ! empty( $legacy ) ) {
                return array_merge( $defaults, self::map_legacy_to_general( $legacy ) );
            }

            return $defaults;
        }

        /**
         * Retrieve Checkout settings with fallback.
         *
         * @return array
         */
        public static function get_checkout_settings() {
            $defaults = self::get_checkout_defaults();
            $settings = get_option( self::CHECKOUT_OPTION, null );

            if ( is_array( $settings ) && ! empty( $settings ) ) {
                return array_merge( $defaults, $settings );
            }

            $legacy = get_option( self::LEGACY_OPTION, null );
            if ( is_array( $legacy ) && ! empty( $legacy ) ) {
                return array_merge( $defaults, self::map_legacy_to_checkout( $legacy ) );
            }

            return $defaults;
        }

        /**
         * Map legacy option fields to General settings.
         *
         * @param array $legacy Legacy settings.
         *
         * @return array
         */
        private static function map_legacy_to_general( $legacy ) {
            $defaults = self::get_general_defaults();

            $mapped = [
                'version'                   => self::VERSION,
                'api_key'                   => isset( $legacy['api_key'] ) ? sanitize_text_field( (string) $legacy['api_key'] ) : '',
                'api_base'                  => self::API_BASE_URL,
                'list_id'                   => isset( $legacy['list_id'] ) ? absint( $legacy['list_id'] ) : 0,
                'default_optin_mode'        => ! empty( $legacy['double_optin_enabled'] ) ? 'double_opt_in' : 'single_opt_in',
                'double_optin_template_id'  => isset( $legacy['double_optin_template_id'] ) ? absint( $legacy['double_optin_template_id'] ) : 0,
                'double_optin_redirect_url' => isset( $legacy['double_optin_redirect_url'] ) ? esc_url_raw( (string) $legacy['double_optin_redirect_url'] ) : '',
                'sender_name'               => isset( $legacy['sender_name'] ) ? sanitize_text_field( (string) $legacy['sender_name'] ) : '',
                'sender_email'              => isset( $legacy['sender_email'] ) ? sanitize_email( (string) $legacy['sender_email'] ) : '',
            ];

            return array_merge( $defaults, $mapped );
        }

        /**
         * Map legacy option fields to Checkout channel settings.
         *
         * @param array $legacy Legacy settings.
         *
         * @return array
         */
        private static function map_legacy_to_checkout( $legacy ) {
            $defaults = self::get_checkout_defaults();

            $timing = isset( $legacy['subscribe_timing'] ) ? sanitize_key( (string) $legacy['subscribe_timing'] ) : 'paid';
            if ( ! in_array( $timing, [ 'created', 'paid' ], true ) ) {
                $timing = 'paid';
            }

            $mapped = [
                'version'             => self::VERSION,
                'enabled'             => ! empty( $legacy['enabled'] ) ? 1 : 0,
                'default_checked'     => ! empty( $legacy['default_checked'] ) ? 1 : 0,
                'label_text'          => isset( $legacy['label_text'] ) ? sanitize_text_field( (string) $legacy['label_text'] ) : $defaults['label_text'],
                'privacy_text'        => isset( $legacy['privacy_text'] ) ? sanitize_textarea_field( (string) $legacy['privacy_text'] ) : '',
                'subscribe_timing'    => $timing,
                'channel_optin_mode'  => 'inherit',
                'placement_after_key' => 'billing_email',
                'priority_offset'     => 5,
            ];

            return array_merge( $defaults, $mapped );
        }
    }
}

class BW_Checkout_Subscribe_Admin {
    const PAGE_SLUG = 'blackwork-mail-marketing';

    /**
     * @var BW_Checkout_Subscribe_Admin|null
     */
    private static $instance = null;

    /**
     * Initialize hooks.
     */
    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get singleton.
     *
     * @return BW_Checkout_Subscribe_Admin
     */
    public static function get_instance() {
        return self::init();
    }

    private function __construct() {
        BW_Mail_Marketing_Settings::init();

        add_action( 'admin_menu', [ $this, 'register_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_post' ] );
        add_action( 'wp_ajax_bw_brevo_test_connection', [ $this, 'handle_test_connection' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_order_newsletter_assets' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_order_newsletter_metabox' ] );
        add_action( 'wp_ajax_bw_brevo_order_refresh_status', [ $this, 'handle_order_refresh_status' ] );
        add_action( 'wp_ajax_bw_brevo_order_retry_subscribe', [ $this, 'handle_order_retry_subscribe' ] );
    }

    /**
     * Register Mail Marketing submenu.
     */
    public function register_submenu() {
        add_submenu_page(
            'blackwork-site-settings',
            __( 'Mail Marketing', 'bw' ),
            __( 'Mail Marketing', 'bw' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_mail_marketing_page' ]
        );
    }

    /**
     * Handle save for General/Checkout tabs.
     */
    public function handle_post() {
        if ( empty( $_POST['bw_mail_marketing_submit'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( 'bw_mail_marketing_save', 'bw_mail_marketing_nonce' );

        $active_tab = isset( $_POST['bw_mail_marketing_tab'] ) ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_tab'] ) ) : 'general';
        if ( ! in_array( $active_tab, [ 'general', 'checkout' ], true ) ) {
            $active_tab = 'general';
        }

        if ( 'general' === $active_tab ) {
            $settings = BW_Mail_Marketing_Settings::get_general_defaults();

            $settings['api_key'] = isset( $_POST['bw_mail_marketing_general_api_key'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_general_api_key'] ) )
                : '';

            $settings['list_id'] = isset( $_POST['bw_mail_marketing_general_list_id'] )
                ? absint( $_POST['bw_mail_marketing_general_list_id'] )
                : 0;

            $settings['default_optin_mode'] = isset( $_POST['bw_mail_marketing_general_default_optin_mode'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_general_default_optin_mode'] ) )
                : 'single_opt_in';

            if ( ! in_array( $settings['default_optin_mode'], [ 'single_opt_in', 'double_opt_in' ], true ) ) {
                $settings['default_optin_mode'] = 'single_opt_in';
            }

            $settings['double_optin_template_id'] = isset( $_POST['bw_mail_marketing_general_double_optin_template_id'] )
                ? absint( $_POST['bw_mail_marketing_general_double_optin_template_id'] )
                : 0;

            $settings['double_optin_redirect_url'] = isset( $_POST['bw_mail_marketing_general_double_optin_redirect_url'] )
                ? esc_url_raw( wp_unslash( $_POST['bw_mail_marketing_general_double_optin_redirect_url'] ) )
                : '';

            $settings['sender_name'] = isset( $_POST['bw_mail_marketing_general_sender_name'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_general_sender_name'] ) )
                : '';

            $settings['sender_email'] = isset( $_POST['bw_mail_marketing_general_sender_email'] )
                ? sanitize_email( wp_unslash( $_POST['bw_mail_marketing_general_sender_email'] ) )
                : '';

            $settings['debug_logging'] = ! empty( $_POST['bw_mail_marketing_general_debug_logging'] ) ? 1 : 0;

            $settings['resubscribe_policy'] = isset( $_POST['bw_mail_marketing_general_resubscribe_policy'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_general_resubscribe_policy'] ) )
                : 'no_auto_resubscribe';

            if ( ! in_array( $settings['resubscribe_policy'], [ 'no_auto_resubscribe' ], true ) ) {
                $settings['resubscribe_policy'] = 'no_auto_resubscribe';
            }

            $settings['sync_first_name'] = ! empty( $_POST['bw_mail_marketing_general_sync_first_name'] ) ? 1 : 0;
            $settings['sync_last_name'] = ! empty( $_POST['bw_mail_marketing_general_sync_last_name'] ) ? 1 : 0;
            $settings['api_base'] = BW_Mail_Marketing_Settings::API_BASE_URL;

            update_option( BW_Mail_Marketing_Settings::GENERAL_OPTION, $settings );
        } else {
            $settings = BW_Mail_Marketing_Settings::get_checkout_defaults();

            $settings['enabled'] = ! empty( $_POST['bw_mail_marketing_checkout_enabled'] ) ? 1 : 0;
            $settings['default_checked'] = ! empty( $_POST['bw_mail_marketing_checkout_default_checked'] ) ? 1 : 0;
            $settings['label_text'] = isset( $_POST['bw_mail_marketing_checkout_label_text'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_checkout_label_text'] ) )
                : '';
            $settings['privacy_text'] = isset( $_POST['bw_mail_marketing_checkout_privacy_text'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_checkout_privacy_text'] ) )
                : '';

            if ( empty( $settings['label_text'] ) ) {
                $settings['label_text'] = __( 'Email me with news and offers', 'bw' );
            }

            $settings['subscribe_timing'] = isset( $_POST['bw_mail_marketing_checkout_timing'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_checkout_timing'] ) )
                : 'paid';

            if ( ! in_array( $settings['subscribe_timing'], [ 'created', 'paid' ], true ) ) {
                $settings['subscribe_timing'] = 'paid';
            }

            $settings['channel_optin_mode'] = isset( $_POST['bw_mail_marketing_checkout_channel_optin_mode'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_checkout_channel_optin_mode'] ) )
                : 'inherit';

            if ( ! in_array( $settings['channel_optin_mode'], [ 'inherit', 'single_opt_in', 'double_opt_in' ], true ) ) {
                $settings['channel_optin_mode'] = 'inherit';
            }

            $settings['placement_after_key'] = isset( $_POST['bw_mail_marketing_checkout_placement_after_key'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_checkout_placement_after_key'] ) )
                : 'billing_email';

            if ( empty( $settings['placement_after_key'] ) ) {
                $settings['placement_after_key'] = 'billing_email';
            }

            $offset = isset( $_POST['bw_mail_marketing_checkout_priority_offset'] )
                ? intval( wp_unslash( $_POST['bw_mail_marketing_checkout_priority_offset'] ) )
                : 5;
            $settings['priority_offset'] = max( -50, min( 50, $offset ) );

            update_option( BW_Mail_Marketing_Settings::CHECKOUT_OPTION, $settings );
        }

        $redirect_args = [
            'page' => self::PAGE_SLUG,
            'tab'  => $active_tab,
            'saved' => '1',
        ];

        wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * AJAX handler for Brevo connection test.
     */
    public function handle_test_connection() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'bw' ) ] );
        }

        check_ajax_referer( 'bw_checkout_subscribe_test', 'nonce' );

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        if ( '' === $api_key ) {
            wp_send_json_error( [ 'message' => __( 'Brevo API key is required.', 'bw' ) ] );
        }

        if ( ! class_exists( 'BW_Brevo_Client' ) ) {
            wp_send_json_error( [ 'message' => __( 'Brevo client is unavailable.', 'bw' ) ] );
        }

        $client = new BW_Brevo_Client( $api_key, BW_Mail_Marketing_Settings::API_BASE_URL );
        $result = $client->get_account();

        if ( empty( $result['success'] ) ) {
            $message = isset( $result['error'] ) ? $result['error'] : __( 'Unable to connect to Brevo.', 'bw' );
            wp_send_json_error( [
                'message' => $message,
                'status'  => 'error',
            ] );
        }

        $account_email = '';
        if ( ! empty( $result['data']['email'] ) ) {
            $account_email = sanitize_email( (string) $result['data']['email'] );
        }

        $success_msg = $account_email
            ? sprintf( __( 'Connection successful. Account: %s', 'bw' ), $account_email )
            : __( 'Connection successful.', 'bw' );

        $account_info = [];
        if ( ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
            if ( ! empty( $result['data']['email'] ) ) {
                $account_info['email'] = sanitize_email( (string) $result['data']['email'] );
            }
            if ( ! empty( $result['data']['companyName'] ) ) {
                $account_info['companyName'] = sanitize_text_field( (string) $result['data']['companyName'] );
            }
            if ( ! empty( $result['data']['plan'] ) ) {
                $account_info['plan'] = sanitize_text_field( (string) $result['data']['plan'] );
            }
        }

        wp_send_json_success( [
            'message' => $success_msg,
            'status'  => 'success',
            'account' => $account_info,
        ] );
    }

    /**
     * Render the new Mail Marketing admin page.
     */
    public function render_mail_marketing_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
        if ( ! in_array( $active_tab, [ 'general', 'checkout' ], true ) ) {
            $active_tab = 'general';
        }

        $general_settings = BW_Mail_Marketing_Settings::get_general_settings();
        $checkout_settings = BW_Mail_Marketing_Settings::get_checkout_settings();
        $lists_data = $this->get_brevo_lists( $general_settings['api_key'] );

        $base_url = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
            ],
            admin_url( 'admin.php' )
        );

        $general_url = add_query_arg( 'tab', 'general', $base_url );
        $checkout_url = add_query_arg( 'tab', 'checkout', $base_url );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Mail Marketing', 'bw' ); ?></h1>

            <?php if ( isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Mail Marketing settings saved.', 'bw' ); ?></strong></p></div>
            <?php endif; ?>

            <h2 class="nav-tab-wrapper">
                <a class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $general_url ); ?>"><?php esc_html_e( 'General', 'bw' ); ?></a>
                <a class="nav-tab <?php echo 'checkout' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $checkout_url ); ?>"><?php esc_html_e( 'Checkout', 'bw' ); ?></a>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field( 'bw_mail_marketing_save', 'bw_mail_marketing_nonce' ); ?>
                <input type="hidden" name="bw_mail_marketing_submit" value="1" />
                <input type="hidden" name="bw_mail_marketing_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

                <?php if ( 'general' === $active_tab ) : ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_api_key"><?php esc_html_e( 'Brevo API key', 'bw' ); ?></label></th>
                            <td>
                                <input type="password" id="bw_mail_marketing_general_api_key" name="bw_mail_marketing_general_api_key" class="regular-text" value="<?php echo esc_attr( $general_settings['api_key'] ); ?>" autocomplete="new-password" />
                                <p class="description"><?php esc_html_e( 'Required for all channels.', 'bw' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'API base URL', 'bw' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" value="<?php echo esc_attr( BW_Mail_Marketing_Settings::API_BASE_URL ); ?>" readonly="readonly" />
                                <p class="description"><?php esc_html_e( 'Managed by plugin and not editable.', 'bw' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Test connection', 'bw' ); ?></th>
                            <td>
                                <button type="button" class="button" id="bw-brevo-test-connection"><?php esc_html_e( 'Test connection', 'bw' ); ?></button>
                                <span class="bw-brevo-test-result" aria-live="polite"></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_list_id"><?php esc_html_e( 'Main list', 'bw' ); ?></label></th>
                            <td>
                                <?php if ( ! empty( $lists_data['success'] ) ) : ?>
                                    <select id="bw_mail_marketing_general_list_id" name="bw_mail_marketing_general_list_id">
                                        <option value="0"><?php esc_html_e( 'Select list', 'bw' ); ?></option>
                                        <?php foreach ( $lists_data['lists'] as $list ) : ?>
                                            <option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( (int) $general_settings['list_id'], (int) $list['id'] ); ?>>
                                                <?php echo esc_html( sprintf( '#%d - %s', (int) $list['id'], $list['name'] ) ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <input type="number" id="bw_mail_marketing_general_list_id" name="bw_mail_marketing_general_list_id" value="<?php echo esc_attr( $general_settings['list_id'] ); ?>" class="small-text" min="0" />
                                    <p class="description"><?php echo esc_html( $lists_data['message'] ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_default_optin_mode"><?php esc_html_e( 'Default opt-in mode', 'bw' ); ?></label></th>
                            <td>
                                <select id="bw_mail_marketing_general_default_optin_mode" name="bw_mail_marketing_general_default_optin_mode">
                                    <option value="single_opt_in" <?php selected( $general_settings['default_optin_mode'], 'single_opt_in' ); ?>><?php esc_html_e( 'Single opt-in', 'bw' ); ?></option>
                                    <option value="double_opt_in" <?php selected( $general_settings['default_optin_mode'], 'double_opt_in' ); ?>><?php esc_html_e( 'Double opt-in', 'bw' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_double_optin_template_id"><?php esc_html_e( 'DOI template ID', 'bw' ); ?></label></th>
                            <td><input type="number" id="bw_mail_marketing_general_double_optin_template_id" name="bw_mail_marketing_general_double_optin_template_id" value="<?php echo esc_attr( $general_settings['double_optin_template_id'] ); ?>" class="small-text" min="0" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_double_optin_redirect_url"><?php esc_html_e( 'DOI redirect URL', 'bw' ); ?></label></th>
                            <td><input type="url" id="bw_mail_marketing_general_double_optin_redirect_url" name="bw_mail_marketing_general_double_optin_redirect_url" value="<?php echo esc_attr( $general_settings['double_optin_redirect_url'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_sender_name"><?php esc_html_e( 'Sender name', 'bw' ); ?></label></th>
                            <td><input type="text" id="bw_mail_marketing_general_sender_name" name="bw_mail_marketing_general_sender_name" value="<?php echo esc_attr( $general_settings['sender_name'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_sender_email"><?php esc_html_e( 'Sender email', 'bw' ); ?></label></th>
                            <td><input type="email" id="bw_mail_marketing_general_sender_email" name="bw_mail_marketing_general_sender_email" value="<?php echo esc_attr( $general_settings['sender_email'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Debug logging', 'bw' ); ?></th>
                            <td>
                                <label><input type="checkbox" name="bw_mail_marketing_general_debug_logging" value="1" <?php checked( $general_settings['debug_logging'], 1 ); ?> /> <?php esc_html_e( 'Enable verbose logs in WooCommerce logger.', 'bw' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_general_resubscribe_policy"><?php esc_html_e( 'Resubscribe policy', 'bw' ); ?></label></th>
                            <td>
                                <select id="bw_mail_marketing_general_resubscribe_policy" name="bw_mail_marketing_general_resubscribe_policy">
                                    <option value="no_auto_resubscribe" <?php selected( $general_settings['resubscribe_policy'], 'no_auto_resubscribe' ); ?>><?php esc_html_e( 'Do not auto-resubscribe unsubscribed/blocklisted', 'bw' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Attribute sync', 'bw' ); ?></th>
                            <td>
                                <label style="margin-right: 16px;"><input type="checkbox" name="bw_mail_marketing_general_sync_first_name" value="1" <?php checked( $general_settings['sync_first_name'], 1 ); ?> /> <?php esc_html_e( 'First name', 'bw' ); ?></label>
                                <label><input type="checkbox" name="bw_mail_marketing_general_sync_last_name" value="1" <?php checked( $general_settings['sync_last_name'], 1 ); ?> /> <?php esc_html_e( 'Last name', 'bw' ); ?></label>
                            </td>
                        </tr>
                    </table>
                <?php else : ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable newsletter checkbox', 'bw' ); ?></th>
                            <td>
                                <label><input type="checkbox" name="bw_mail_marketing_checkout_enabled" value="1" <?php checked( $checkout_settings['enabled'], 1 ); ?> /> <?php esc_html_e( 'Show the opt-in checkbox on checkout.', 'bw' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Default checked', 'bw' ); ?></th>
                            <td>
                                <label><input type="checkbox" name="bw_mail_marketing_checkout_default_checked" value="1" <?php checked( $checkout_settings['default_checked'], 1 ); ?> /> <?php esc_html_e( 'Pre-check checkbox (verify GDPR compliance).', 'bw' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_checkout_label_text"><?php esc_html_e( 'Checkbox label', 'bw' ); ?></label></th>
                            <td><input type="text" id="bw_mail_marketing_checkout_label_text" name="bw_mail_marketing_checkout_label_text" value="<?php echo esc_attr( $checkout_settings['label_text'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_checkout_privacy_text"><?php esc_html_e( 'Privacy text', 'bw' ); ?></label></th>
                            <td><textarea id="bw_mail_marketing_checkout_privacy_text" name="bw_mail_marketing_checkout_privacy_text" rows="3" class="large-text"><?php echo esc_textarea( $checkout_settings['privacy_text'] ); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_checkout_timing"><?php esc_html_e( 'Subscribe timing', 'bw' ); ?></label></th>
                            <td>
                                <select id="bw_mail_marketing_checkout_timing" name="bw_mail_marketing_checkout_timing">
                                    <option value="paid" <?php selected( $checkout_settings['subscribe_timing'], 'paid' ); ?>><?php esc_html_e( 'Order paid (default)', 'bw' ); ?></option>
                                    <option value="created" <?php selected( $checkout_settings['subscribe_timing'], 'created' ); ?>><?php esc_html_e( 'Order created (checkout submit)', 'bw' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_checkout_channel_optin_mode"><?php esc_html_e( 'Channel opt-in mode', 'bw' ); ?></label></th>
                            <td>
                                <select id="bw_mail_marketing_checkout_channel_optin_mode" name="bw_mail_marketing_checkout_channel_optin_mode">
                                    <option value="inherit" <?php selected( $checkout_settings['channel_optin_mode'], 'inherit' ); ?>><?php esc_html_e( 'Inherit General setting', 'bw' ); ?></option>
                                    <option value="single_opt_in" <?php selected( $checkout_settings['channel_optin_mode'], 'single_opt_in' ); ?>><?php esc_html_e( 'Force single opt-in', 'bw' ); ?></option>
                                    <option value="double_opt_in" <?php selected( $checkout_settings['channel_optin_mode'], 'double_opt_in' ); ?>><?php esc_html_e( 'Force double opt-in', 'bw' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_checkout_placement_after_key"><?php esc_html_e( 'Placement after field key', 'bw' ); ?></label></th>
                            <td>
                                <input type="text" id="bw_mail_marketing_checkout_placement_after_key" name="bw_mail_marketing_checkout_placement_after_key" value="<?php echo esc_attr( $checkout_settings['placement_after_key'] ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Default: billing_email', 'bw' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="bw_mail_marketing_checkout_priority_offset"><?php esc_html_e( 'Priority offset', 'bw' ); ?></label></th>
                            <td><input type="number" id="bw_mail_marketing_checkout_priority_offset" name="bw_mail_marketing_checkout_priority_offset" value="<?php echo esc_attr( $checkout_settings['priority_offset'] ); ?>" min="-50" max="50" step="1" /></td>
                        </tr>
                    </table>
                <?php endif; ?>

                <?php submit_button( __( 'Save Mail Marketing settings', 'bw' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Legacy renderer used by old Checkout > Subscribe route.
     */
    public function render_tab() {
        $mail_marketing_checkout = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'tab'  => 'checkout',
            ],
            admin_url( 'admin.php' )
        );

        echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Subscribe settings moved to Blackwork Site > Mail Marketing > Checkout.', 'bw' ) . '</strong> <a href="' . esc_url( $mail_marketing_checkout ) . '">' . esc_html__( 'Open Mail Marketing', 'bw' ) . '</a></p></div>';
    }

    /**
     * Enqueue order panel assets only on WooCommerce order admin screens.
     *
     * @param string $hook Admin hook suffix.
     */
    public function enqueue_order_newsletter_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' ], true ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen ) {
            return;
        }

        $screen_id = isset( $screen->id ) ? (string) $screen->id : '';
        if ( ! in_array( $screen_id, [ 'shop_order', 'woocommerce_page_wc-orders' ], true ) ) {
            return;
        }

        $js_file = BW_MEW_PATH . 'admin/js/bw-order-newsletter-status.js';
        $version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

        wp_enqueue_script(
            'bw-order-newsletter-status',
            BW_MEW_URL . 'admin/js/bw-order-newsletter-status.js',
            [ 'jquery' ],
            $version,
            true
        );

        wp_localize_script(
            'bw-order-newsletter-status',
            'bwOrderNewsletterStatus',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'bw_brevo_order_actions' ),
                'errorText' => esc_html__( 'Action failed. Please retry.', 'bw' ),
            ]
        );
    }

    /**
     * Register full-width newsletter metabox on order edit screen.
     */
    public function register_order_newsletter_metabox() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Safety net: ensure legacy inline rendering inside Woo "General" column is not active.
        remove_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'render_order_newsletter_panel' ] );

        $screens = [ 'shop_order' ];
        if ( function_exists( 'wc_get_page_screen_id' ) ) {
            $hpos_screen = wc_get_page_screen_id( 'shop-order' );
            if ( $hpos_screen ) {
                $screens[] = $hpos_screen;
            }
        }

        $screens = array_unique( array_filter( $screens ) );

        foreach ( $screens as $screen ) {
            add_meta_box(
                'bw-newsletter-status-panel-metabox',
                __( 'Newsletter Status', 'bw' ),
                [ $this, 'render_order_newsletter_metabox' ],
                $screen,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render metabox wrapper and resolve order object.
     *
     * @param WP_Post|WC_Order $object Post or order object from metabox callback.
     */
    public function render_order_newsletter_metabox( $object ) {
        $order = null;

        if ( $object instanceof WC_Order ) {
            $order = $object;
        } elseif ( $object instanceof WP_Post ) {
            $order = wc_get_order( $object->ID );
        }

        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $this->render_order_newsletter_panel( $order );
    }

    /**
     * Render newsletter status panel in WooCommerce order admin metabox.
     *
     * @param WC_Order $order Woo order object.
     */
    public function render_order_newsletter_panel( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $payload = $this->build_order_status_payload( $order );
        $status_class = $this->get_status_badge_class( $payload['status'] );
        ?>
        <style>
            .bw-newsletter-status-badge.bw-status--subscribed { background:#d1f8e0;color:#0a3622;border:1px solid #75d39a; }
            .bw-newsletter-status-badge.bw-status--pending { background:#e7eefc;color:#1b3b7a;border:1px solid #9ab1e9; }
            .bw-newsletter-status-badge.bw-status--neutral { background:#f3f4f6;color:#2c3338;border:1px solid #c3c4c7; }
            .bw-newsletter-status-badge.bw-status--error { background:#fce2e2;color:#691010;border:1px solid #e99a9a; }
            #bw-newsletter-status-panel .bw-newsletter-meta-table th { width:220px; }
        </style>
        <div id="bw-newsletter-status-panel" class="bw-newsletter-status-panel" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
                <span id="bw-newsletter-status-badge" class="bw-newsletter-status-badge <?php echo esc_attr( $status_class ); ?>" style="display:inline-block;padding:6px 10px;border-radius:999px;font-weight:600;">
                    <?php echo esc_html( $this->get_status_label( $payload['status'] ) ); ?>
                </span>
                <button type="button" class="button" id="bw-newsletter-refresh"><?php esc_html_e( 'Refresh', 'bw' ); ?></button>
                <button type="button" class="button button-secondary" id="bw-newsletter-retry"><?php esc_html_e( 'Retry subscribe', 'bw' ); ?></button>
                <span id="bw-newsletter-inline-message" aria-live="polite"></span>
            </div>

            <table class="widefat striped bw-newsletter-meta-table" style="max-width:100%;margin-top:8px;">
                <tbody>
                    <tr><th><?php esc_html_e( 'Email', 'bw' ); ?></th><td data-bw-field="email"><?php echo esc_html( $payload['meta']['email'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'List ID used', 'bw' ); ?></th><td data-bw-field="list_id"><?php echo esc_html( (string) $payload['meta']['list_id'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Opt-in value', 'bw' ); ?></th><td data-bw-field="opt_in"><?php echo esc_html( (string) $payload['meta']['opt_in'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Consent timestamp', 'bw' ); ?></th><td data-bw-field="consent_at"><?php echo esc_html( $payload['meta']['consent_at'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Consent source', 'bw' ); ?></th><td data-bw-field="consent_source"><?php echo esc_html( $payload['meta']['consent_source'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Current Brevo status', 'bw' ); ?></th><td data-bw-field="brevo_status"><?php echo esc_html( $payload['meta']['brevo_status'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Last error', 'bw' ); ?></th><td data-bw-field="last_error"><?php echo esc_html( $payload['meta']['last_error'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Last checked at', 'bw' ); ?></th><td data-bw-field="last_checked_at"><?php echo esc_html( $payload['meta']['last_checked_at'] ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Brevo contact id', 'bw' ); ?></th><td data-bw-field="contact_id"><?php echo esc_html( $payload['meta']['contact_id'] ); ?></td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * AJAX refresh status from Brevo contact API (read-only sync).
     */
    public function handle_order_refresh_status() {
        $order = $this->get_order_from_ajax_request();
        if ( ! $order ) {
            return;
        }

        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $email   = (string) $order->get_billing_email();
        $list_id = isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0;

        if ( '' === $email || ! is_email( $email ) ) {
            $this->set_order_error_meta( $order, __( 'Order has no valid billing email.', 'bw' ) );
            $this->log_order_action( $order, $email, 'error', 'refresh', 'Invalid order email.' );
            wp_send_json_error( [ 'message' => __( 'Order has no valid billing email.', 'bw' ) ] );
        }

        if ( empty( $general['api_key'] ) ) {
            $this->set_order_error_meta( $order, __( 'Brevo API key is not configured.', 'bw' ) );
            $this->log_order_action( $order, $email, 'error', 'refresh', 'Missing API key.' );
            wp_send_json_error( [ 'message' => __( 'Brevo API key is not configured.', 'bw' ) ] );
        }

        $client = new BW_Brevo_Client( $general['api_key'], BW_Mail_Marketing_Settings::API_BASE_URL );
        $result = $client->get_contact( $email );

        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );

        if ( empty( $result['success'] ) ) {
            if ( isset( $result['code'] ) && 404 === (int) $result['code'] ) {
                update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'skipped' );
                delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
                delete_post_meta( $order->get_id(), '_bw_brevo_contact_id' );

                $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Contact not found in Brevo.' );
                wp_send_json_success( $this->build_order_status_payload( $order, __( 'Contact not found in Brevo.', 'bw' ) ) );
            }

            $error = isset( $result['error'] ) ? (string) $result['error'] : __( 'Unable to refresh status from Brevo.', 'bw' );
            $this->set_order_error_meta( $order, $error );
            $this->log_order_action( $order, $email, 'error', 'refresh', $error );
            wp_send_json_error( [ 'message' => $error ] );
        }

        $data = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : [];
        $contact_id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
        $is_blacklisted = ! empty( $data['emailBlacklisted'] );
        $list_ids = isset( $data['listIds'] ) && is_array( $data['listIds'] ) ? array_map( 'absint', $data['listIds'] ) : [];
        $in_list = $list_id > 0 ? in_array( $list_id, $list_ids, true ) : false;

        if ( $contact_id > 0 ) {
            update_post_meta( $order->get_id(), '_bw_brevo_contact_id', (string) $contact_id );
        }

        if ( $is_blacklisted ) {
            update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'skipped' );
            delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
            $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Contact is blocklisted/unsubscribed.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'Contact is blocklisted/unsubscribed.', 'bw' ) ) );
        }

        if ( $in_list ) {
            update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'subscribed' );
            delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
            $this->log_order_action( $order, $email, 'subscribed', 'refresh', 'Contact found in configured list.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'Contact found in configured list.', 'bw' ) ) );
        }

        update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'skipped' );
        delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
        $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Contact exists but is not in configured list.' );
        wp_send_json_success( $this->build_order_status_payload( $order, __( 'Contact exists but is not in configured list.', 'bw' ) ) );
    }

    /**
     * AJAX retry subscribe for an order (write action).
     */
    public function handle_order_retry_subscribe() {
        $order = $this->get_order_from_ajax_request();
        if ( ! $order ) {
            return;
        }

        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $checkout = BW_Mail_Marketing_Settings::get_checkout_settings();

        $email = (string) $order->get_billing_email();
        $list_id = isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0;
        $opt_in = (int) $order->get_meta( '_bw_subscribe_newsletter', true );

        if ( 1 !== $opt_in ) {
            update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'skipped' );
            $this->log_order_action( $order, $email, 'skipped', 'retry', 'Retry skipped: customer did not opt-in.' );
            wp_send_json_error( [ 'message' => __( 'Retry skipped: customer did not opt-in.', 'bw' ) ] );
        }

        if ( '' === $email || ! is_email( $email ) ) {
            $this->set_order_error_meta( $order, __( 'Order has no valid billing email.', 'bw' ) );
            $this->log_order_action( $order, $email, 'error', 'retry', 'Invalid order email.' );
            wp_send_json_error( [ 'message' => __( 'Order has no valid billing email.', 'bw' ) ] );
        }

        if ( empty( $general['api_key'] ) || $list_id <= 0 ) {
            $this->set_order_error_meta( $order, __( 'Missing Brevo API key or list id.', 'bw' ) );
            $this->log_order_action( $order, $email, 'error', 'retry', 'Missing API key/list id.' );
            wp_send_json_error( [ 'message' => __( 'Missing Brevo API key or list id.', 'bw' ) ] );
        }

        $client = new BW_Brevo_Client( $general['api_key'], BW_Mail_Marketing_Settings::API_BASE_URL );
        $contact = $client->get_contact( $email );
        if ( ! empty( $contact['success'] ) && ! empty( $contact['data'] ) && is_array( $contact['data'] ) && ! empty( $contact['data']['emailBlacklisted'] ) ) {
            update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'skipped' );
            delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
            $this->log_order_action( $order, $email, 'skipped', 'retry', 'Retry skipped: contact is blocklisted/unsubscribed.' );
            wp_send_json_error( [ 'message' => __( 'Retry skipped: contact is blocklisted/unsubscribed.', 'bw' ) ] );
        }

        $mode = isset( $checkout['channel_optin_mode'] ) ? (string) $checkout['channel_optin_mode'] : 'inherit';
        if ( ! in_array( $mode, [ 'single_opt_in', 'double_opt_in' ], true ) ) {
            $mode = ( isset( $general['default_optin_mode'] ) && 'double_opt_in' === $general['default_optin_mode'] ) ? 'double_opt_in' : 'single_opt_in';
        }

        $attributes = [];
        if ( ! empty( $general['sync_first_name'] ) ) {
            $first_name = trim( (string) $order->get_billing_first_name() );
            if ( '' !== $first_name ) {
                $attributes['FIRSTNAME'] = $first_name;
            }
        }
        if ( ! empty( $general['sync_last_name'] ) ) {
            $last_name = trim( (string) $order->get_billing_last_name() );
            if ( '' !== $last_name ) {
                $attributes['LASTNAME'] = $last_name;
            }
        }

        if ( 'double_opt_in' === $mode ) {
            $template_id = isset( $general['double_optin_template_id'] ) ? absint( $general['double_optin_template_id'] ) : 0;
            $redirect_url = isset( $general['double_optin_redirect_url'] ) ? (string) $general['double_optin_redirect_url'] : '';
            if ( $template_id <= 0 || '' === $redirect_url ) {
                $this->set_order_error_meta( $order, __( 'DOI requires template id and redirect URL.', 'bw' ) );
                $this->log_order_action( $order, $email, 'error', 'retry', 'Missing DOI template/redirect.' );
                wp_send_json_error( [ 'message' => __( 'DOI requires template id and redirect URL.', 'bw' ) ] );
            }

            $sender = [];
            if ( ! empty( $general['sender_email'] ) ) {
                $sender['email'] = sanitize_email( (string) $general['sender_email'] );
            }
            if ( ! empty( $general['sender_name'] ) ) {
                $sender['name'] = sanitize_text_field( (string) $general['sender_name'] );
            }

            $result = $client->send_double_opt_in( $email, $template_id, $redirect_url, [ $list_id ], $attributes, $sender );
            if ( empty( $result['success'] ) ) {
                $error = isset( $result['error'] ) ? (string) $result['error'] : __( 'Brevo DOI failed.', 'bw' );
                $this->set_order_error_meta( $order, $error );
                $this->log_order_action( $order, $email, 'error', 'retry', $error );
                wp_send_json_error( [ 'message' => $error ] );
            }

            update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'pending' );
            delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
            update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
            $this->log_order_action( $order, $email, 'pending', 'retry', 'DOI request sent.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'DOI request sent successfully.', 'bw' ) ) );
        }

        $result = $client->upsert_contact( $email, $attributes, [ $list_id ] );
        if ( empty( $result['success'] ) ) {
            $error = isset( $result['error'] ) ? (string) $result['error'] : __( 'Brevo subscribe failed.', 'bw' );
            $this->set_order_error_meta( $order, $error );
            $this->log_order_action( $order, $email, 'error', 'retry', $error );
            wp_send_json_error( [ 'message' => $error ] );
        }

        update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'subscribed' );
        delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
        $this->log_order_action( $order, $email, 'subscribed', 'retry', 'Retry subscribe succeeded.' );
        wp_send_json_success( $this->build_order_status_payload( $order, __( 'Retry subscribe succeeded.', 'bw' ) ) );
    }

    /**
     * Validate AJAX request and resolve order.
     *
     * @return WC_Order|null
     */
    private function get_order_from_ajax_request() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'bw' ) ] );
        }

        check_ajax_referer( 'bw_brevo_order_actions', 'nonce' );

        $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
        if ( $order_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid order id.', 'bw' ) ] );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order ) {
            wp_send_json_error( [ 'message' => __( 'Order not found.', 'bw' ) ] );
        }

        return $order;
    }

    /**
     * Build panel payload and debug values from order meta.
     *
     * @param WC_Order $order   Order object.
     * @param string   $message Optional message.
     *
     * @return array
     */
    private function build_order_status_payload( $order, $message = '' ) {
        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $opt_in = (int) $order->get_meta( '_bw_subscribe_newsletter', true );
        $status = (string) $order->get_meta( '_bw_brevo_subscribed', true );

        if ( '' === $status ) {
            $status = 1 === $opt_in ? 'pending' : 'not_subscribed';
        }

        if ( '1' === $status ) {
            $status = 'subscribed';
        }

        if ( ! in_array( $status, [ 'subscribed', 'pending', 'skipped', 'error', 'not_subscribed' ], true ) ) {
            $status = 'not_subscribed';
        }

        $meta = [
            'email'           => (string) $order->get_billing_email(),
            'list_id'         => isset( $general['list_id'] ) ? (string) absint( $general['list_id'] ) : '0',
            'opt_in'          => (string) $opt_in,
            'consent_at'      => (string) $order->get_meta( '_bw_subscribe_consent_at', true ),
            'consent_source'  => (string) $order->get_meta( '_bw_subscribe_consent_source', true ),
            'brevo_status'    => (string) $status,
            'last_error'      => (string) $order->get_meta( '_bw_brevo_error_last', true ),
            'last_checked_at' => (string) $order->get_meta( '_bw_brevo_last_checked_at', true ),
            'contact_id'      => (string) $order->get_meta( '_bw_brevo_contact_id', true ),
        ];

        return [
            'message'      => $message,
            'status'       => $status,
            'statusLabel'  => $this->get_status_label( $status ),
            'statusClass'  => $this->get_status_badge_class( $status ),
            'meta'         => $meta,
        ];
    }

    /**
     * Map status key to user-facing label.
     *
     * @param string $status Status key.
     *
     * @return string
     */
    private function get_status_label( $status ) {
        switch ( $status ) {
            case 'subscribed':
                return __( 'Subscribed', 'bw' );
            case 'pending':
                return __( 'Pending', 'bw' );
            case 'error':
                return __( 'Error', 'bw' );
            case 'skipped':
            case 'not_subscribed':
            default:
                return __( 'Not subscribed', 'bw' );
        }
    }

    /**
     * Map status key to badge class.
     *
     * @param string $status Status key.
     *
     * @return string
     */
    private function get_status_badge_class( $status ) {
        switch ( $status ) {
            case 'subscribed':
                return 'bw-status--subscribed';
            case 'pending':
                return 'bw-status--pending';
            case 'error':
                return 'bw-status--error';
            case 'skipped':
            case 'not_subscribed':
            default:
                return 'bw-status--neutral';
        }
    }

    /**
     * Persist error status meta on order.
     *
     * @param WC_Order $order Order object.
     * @param string   $error Error message.
     */
    private function set_order_error_meta( $order, $error ) {
        update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'error' );
        update_post_meta( $order->get_id(), '_bw_brevo_error_last', sanitize_text_field( $error ) );
        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
    }

    /**
     * Write order-level newsletter log entry.
     *
     * @param WC_Order $order  Order object.
     * @param string   $email  Email.
     * @param string   $result Result key.
     * @param string   $action Action key (refresh/retry).
     * @param string   $msg    Message.
     */
    private function log_order_action( $order, $email, $result, $action, $msg ) {
        if ( ! function_exists( 'wc_get_logger' ) || ! $order instanceof WC_Order ) {
            return;
        }

        $context = [
            'source'   => 'bw-brevo',
            'order_id' => $order->get_id(),
            'email'    => sanitize_email( (string) $email ),
            'result'   => sanitize_key( (string) $result ),
            'action'   => sanitize_key( (string) $action ),
        ];

        $logger = wc_get_logger();
        if ( 'error' === $result ) {
            $logger->error( $msg, $context );
            return;
        }

        $logger->info( $msg, $context );
    }

    /**
     * Retrieve lists from Brevo API.
     *
     * @param string $api_key API key.
     *
     * @return array
     */
    private function get_brevo_lists( $api_key ) {
        if ( '' === $api_key ) {
            return [
                'success' => false,
                'message' => __( 'Insert API key and save to load list dropdown. Numeric input fallback remains available.', 'bw' ),
                'lists'   => [],
            ];
        }

        if ( ! class_exists( 'BW_Brevo_Client' ) ) {
            return [
                'success' => false,
                'message' => __( 'Brevo client unavailable.', 'bw' ),
                'lists'   => [],
            ];
        }

        $client = new BW_Brevo_Client( $api_key, BW_Mail_Marketing_Settings::API_BASE_URL );
        $result = $client->get_lists( 50, 0 );

        if ( empty( $result['success'] ) ) {
            return [
                'success' => false,
                'message' => isset( $result['error'] ) ? sanitize_text_field( (string) $result['error'] ) : __( 'Unable to load lists from Brevo. Use numeric List ID.', 'bw' ),
                'lists'   => [],
            ];
        }

        $lists = [];
        if ( ! empty( $result['data']['lists'] ) && is_array( $result['data']['lists'] ) ) {
            foreach ( $result['data']['lists'] as $list ) {
                if ( empty( $list['id'] ) ) {
                    continue;
                }

                $lists[] = [
                    'id'   => absint( $list['id'] ),
                    'name' => isset( $list['name'] ) ? sanitize_text_field( (string) $list['name'] ) : __( 'Untitled', 'bw' ),
                ];
            }
        }

        if ( empty( $lists ) ) {
            return [
                'success' => false,
                'message' => __( 'No lists returned by API. Use numeric List ID.', 'bw' ),
                'lists'   => [],
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'lists'   => $lists,
        ];
    }
}
