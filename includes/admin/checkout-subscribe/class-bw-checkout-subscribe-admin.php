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
