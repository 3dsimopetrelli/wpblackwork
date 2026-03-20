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
        const SUBSCRIPTION_OPTION = 'bw_mail_marketing_subscription_settings';
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
            // One-time fix: consent_required was forced to 0 by a missing admin UI field.
            // The checkbox was parsed from POST but never rendered in the form, so every
            // save overwrote consent_required = 0 regardless of admin intent.
            // This migration runs once (keyed by consent_required_ui_patched) and resets
            // the value to 1 so GDPR consent is enforced by default.
            $subscription = get_option( self::SUBSCRIPTION_OPTION, null );
            if ( is_array( $subscription ) && empty( $subscription['consent_required_ui_patched'] ) ) {
                if ( array_key_exists( 'consent_required', $subscription ) && 0 === (int) $subscription['consent_required'] ) {
                    $subscription['consent_required'] = 1;
                }
                $subscription['consent_required_ui_patched'] = 1;
                update_option( self::SUBSCRIPTION_OPTION, $subscription );
            }

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
                'unconfirmed_list_id'         => 0,
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
         * Defaults for Subscription widget/channel settings.
         *
         * @return array
         */
        public static function get_subscription_defaults() {
            return [
                'version'                  => self::VERSION,
                'enabled'                  => 1,
                'source_key'               => 'elementor_widget',
                'list_mode'                => 'inherit',
                'list_id'                  => 0,
                'channel_optin_mode'       => 'inherit',
                'consent_required'         => 1,
                'privacy_url'              => '',
                'name_label'               => __( 'Name', 'bw' ),
                'email_label'              => __( 'Email address', 'bw' ),
                'consent_prefix'           => __( 'I agree to the', 'bw' ),
                'privacy_link_label'       => __( 'Privacy Policy', 'bw' ),
                'button_text'              => __( 'Subscribe', 'bw' ),
                'empty_email_message'      => __( 'Please enter your email address.', 'bw' ),
                'invalid_email_message'    => __( 'Please enter a valid email address.', 'bw' ),
                'success_message'          => __( 'Thanks for subscribing! Please check your inbox.', 'bw' ),
                'already_subscribed_message' => __( 'You are already subscribed.', 'bw' ),
                'loading_message'          => __( 'Submitting...', 'bw' ),
                'error_message'            => __( 'Unable to subscribe right now. Please try again later.', 'bw' ),
                'consent_required_message' => __( 'Please confirm the privacy consent to subscribe.', 'bw' ),
                'rate_limited_message'     => __( 'Please wait a moment before trying again.', 'bw' ),
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
         * Retrieve Subscription settings.
         *
         * @return array
         */
        public static function get_subscription_settings() {
            $defaults = self::get_subscription_defaults();
            $settings = get_option( self::SUBSCRIPTION_OPTION, null );

            if ( is_array( $settings ) && ! empty( $settings ) ) {
                return array_merge( $defaults, $settings );
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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_user_mail_marketing_assets' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_order_newsletter_metabox' ] );
        add_action( 'wp_ajax_bw_brevo_order_refresh_status', [ $this, 'handle_order_refresh_status' ] );
        add_action( 'wp_ajax_bw_brevo_order_retry_subscribe', [ $this, 'handle_order_retry_subscribe' ] );
        add_action( 'wp_ajax_bw_brevo_order_load_lists', [ $this, 'handle_order_load_lists' ] );
        add_action( 'admin_notices', [ $this, 'render_user_mail_marketing_top_panel' ] );
        add_action( 'wp_ajax_bw_brevo_user_check_status', [ $this, 'handle_user_check_status' ] );
        add_action( 'wp_ajax_bw_brevo_user_sync_status', [ $this, 'handle_user_sync_status' ] );
        add_filter( 'manage_edit-shop_order_columns', [ $this, 'register_orders_list_newsletter_column' ], 20 );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_orders_list_newsletter_column' ], 10, 2 );
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'register_orders_list_newsletter_column' ], 20 );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'render_hpos_orders_list_newsletter_column' ], 10, 2 );
        add_action( 'admin_head-edit.php', [ $this, 'print_orders_list_newsletter_column_css' ] );
        add_action( 'admin_head-woocommerce_page_wc-orders', [ $this, 'print_orders_list_newsletter_column_css' ] );
        add_action( 'restrict_manage_posts', [ $this, 'render_orders_newsletter_filters_classic' ] );
        add_action( 'pre_get_posts', [ $this, 'apply_orders_newsletter_filters_classic' ] );
        add_action( 'woocommerce_order_list_table_restrict_manage_orders', [ $this, 'render_orders_newsletter_filters_hpos' ], 10, 1 );
        add_filter( 'woocommerce_order_list_table_prepare_items_query_args', [ $this, 'apply_orders_newsletter_filters_hpos' ] );
        add_filter( 'bulk_actions-edit-shop_order', [ $this, 'register_orders_bulk_resync_action' ] );
        add_filter( 'bulk_actions-woocommerce_page_wc-orders', [ $this, 'register_orders_bulk_resync_action' ] );
        add_filter( 'handle_bulk_actions-edit-shop_order', [ $this, 'handle_orders_bulk_resync_action' ], 10, 3 );
        add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', [ $this, 'handle_orders_bulk_resync_action' ], 10, 3 );
        add_action( 'admin_notices', [ $this, 'render_bulk_resync_admin_notice' ] );
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
        if ( ! in_array( $active_tab, [ 'general', 'checkout', 'subscription' ], true ) ) {
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

            $settings['unconfirmed_list_id'] = isset( $_POST['bw_mail_marketing_general_unconfirmed_list_id'] )
                ? absint( $_POST['bw_mail_marketing_general_unconfirmed_list_id'] )
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
        } elseif ( 'checkout' === $active_tab ) {
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
        } else {
            $settings = BW_Mail_Marketing_Settings::get_subscription_defaults();

            $settings['enabled'] = ! empty( $_POST['bw_mail_marketing_subscription_enabled'] ) ? 1 : 0;
            $settings['source_key'] = isset( $_POST['bw_mail_marketing_subscription_source_key'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_subscription_source_key'] ) )
                : 'elementor_widget';
            if ( '' === $settings['source_key'] ) {
                $settings['source_key'] = 'elementor_widget';
            }

            $settings['list_mode'] = isset( $_POST['bw_mail_marketing_subscription_list_mode'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_subscription_list_mode'] ) )
                : 'inherit';
            if ( ! in_array( $settings['list_mode'], [ 'inherit', 'custom' ], true ) ) {
                $settings['list_mode'] = 'inherit';
            }

            $settings['list_id'] = isset( $_POST['bw_mail_marketing_subscription_list_id'] )
                ? absint( $_POST['bw_mail_marketing_subscription_list_id'] )
                : 0;

            $settings['channel_optin_mode'] = isset( $_POST['bw_mail_marketing_subscription_channel_optin_mode'] )
                ? sanitize_key( wp_unslash( $_POST['bw_mail_marketing_subscription_channel_optin_mode'] ) )
                : 'inherit';
            if ( ! in_array( $settings['channel_optin_mode'], [ 'inherit', 'single_opt_in', 'double_opt_in' ], true ) ) {
                $settings['channel_optin_mode'] = 'inherit';
            }

            $settings['consent_required'] = ! empty( $_POST['bw_mail_marketing_subscription_consent_required'] ) ? 1 : 0;

            $settings['privacy_url'] = isset( $_POST['bw_mail_marketing_subscription_privacy_url'] )
                ? esc_url_raw( wp_unslash( $_POST['bw_mail_marketing_subscription_privacy_url'] ) )
                : $settings['privacy_url'];

            $settings['name_label'] = isset( $_POST['bw_mail_marketing_subscription_name_label'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_subscription_name_label'] ) )
                : $settings['name_label'];
            if ( '' === $settings['name_label'] ) {
                $settings['name_label'] = __( 'Name', 'bw' );
            }

            $settings['email_label'] = isset( $_POST['bw_mail_marketing_subscription_email_label'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_subscription_email_label'] ) )
                : $settings['email_label'];
            if ( '' === $settings['email_label'] ) {
                $settings['email_label'] = __( 'Email address', 'bw' );
            }

            $settings['consent_prefix'] = isset( $_POST['bw_mail_marketing_subscription_consent_prefix'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_subscription_consent_prefix'] ) )
                : $settings['consent_prefix'];
            if ( '' === $settings['consent_prefix'] ) {
                $settings['consent_prefix'] = __( 'I agree to the', 'bw' );
            }

            $settings['privacy_link_label'] = isset( $_POST['bw_mail_marketing_subscription_privacy_link_label'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_subscription_privacy_link_label'] ) )
                : $settings['privacy_link_label'];
            if ( '' === $settings['privacy_link_label'] ) {
                $settings['privacy_link_label'] = __( 'Privacy Policy', 'bw' );
            }

            $settings['button_text'] = isset( $_POST['bw_mail_marketing_subscription_button_text'] )
                ? sanitize_text_field( wp_unslash( $_POST['bw_mail_marketing_subscription_button_text'] ) )
                : $settings['button_text'];
            if ( '' === $settings['button_text'] ) {
                $settings['button_text'] = __( 'Subscribe', 'bw' );
            }

            $settings['empty_email_message'] = isset( $_POST['bw_mail_marketing_subscription_empty_email_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_empty_email_message'] ) )
                : $settings['empty_email_message'];
            if ( '' === $settings['empty_email_message'] ) {
                $settings['empty_email_message'] = __( 'Please enter your email address.', 'bw' );
            }

            $settings['invalid_email_message'] = isset( $_POST['bw_mail_marketing_subscription_invalid_email_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_invalid_email_message'] ) )
                : $settings['invalid_email_message'];
            if ( '' === $settings['invalid_email_message'] ) {
                $settings['invalid_email_message'] = __( 'Please enter a valid email address.', 'bw' );
            }

            $settings['success_message'] = isset( $_POST['bw_mail_marketing_subscription_success_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_success_message'] ) )
                : $settings['success_message'];
            if ( '' === $settings['success_message'] ) {
                $settings['success_message'] = __( 'Thanks for subscribing! Please check your inbox.', 'bw' );
            }

            $settings['already_subscribed_message'] = isset( $_POST['bw_mail_marketing_subscription_already_subscribed_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_already_subscribed_message'] ) )
                : $settings['already_subscribed_message'];
            if ( '' === $settings['already_subscribed_message'] ) {
                $settings['already_subscribed_message'] = __( 'You are already subscribed.', 'bw' );
            }

            $settings['loading_message'] = isset( $_POST['bw_mail_marketing_subscription_loading_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_loading_message'] ) )
                : $settings['loading_message'];
            if ( '' === $settings['loading_message'] ) {
                $settings['loading_message'] = __( 'Submitting...', 'bw' );
            }

            $settings['error_message'] = isset( $_POST['bw_mail_marketing_subscription_error_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_error_message'] ) )
                : $settings['error_message'];
            if ( '' === $settings['error_message'] ) {
                $settings['error_message'] = __( 'Unable to subscribe right now. Please try again later.', 'bw' );
            }

            $settings['consent_required_message'] = isset( $_POST['bw_mail_marketing_subscription_consent_required_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_consent_required_message'] ) )
                : $settings['consent_required_message'];
            if ( '' === $settings['consent_required_message'] ) {
                $settings['consent_required_message'] = __( 'Please confirm the privacy consent to subscribe.', 'bw' );
            }

            $settings['rate_limited_message'] = isset( $_POST['bw_mail_marketing_subscription_rate_limited_message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['bw_mail_marketing_subscription_rate_limited_message'] ) )
                : $settings['rate_limited_message'];
            if ( '' === $settings['rate_limited_message'] ) {
                $settings['rate_limited_message'] = __( 'Please wait a moment before trying again.', 'bw' );
            }

            update_option( BW_Mail_Marketing_Settings::SUBSCRIPTION_OPTION, $settings );
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
        if ( ! in_array( $active_tab, [ 'general', 'checkout', 'subscription' ], true ) ) {
            $active_tab = 'general';
        }

        $general_settings = BW_Mail_Marketing_Settings::get_general_settings();
        $checkout_settings = BW_Mail_Marketing_Settings::get_checkout_settings();
        $subscription_settings = BW_Mail_Marketing_Settings::get_subscription_settings();
        $lists_data = $this->get_brevo_lists( $general_settings['api_key'] );

        $base_url = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
            ],
            admin_url( 'admin.php' )
        );

        $general_url = add_query_arg( 'tab', 'general', $base_url );
        $checkout_url = add_query_arg( 'tab', 'checkout', $base_url );
        $subscription_url = add_query_arg( 'tab', 'subscription', $base_url );
        ?>
        <div class="wrap bw-admin-root bw-admin-page bw-admin-page-mail-marketing">
            <div class="bw-admin-header">
                <h1 class="bw-admin-title"><?php esc_html_e( 'Mail Marketing', 'bw' ); ?></h1>
                <p class="bw-admin-subtitle"><?php esc_html_e( 'Configure email capture, Brevo integration, and checkout newsletter behavior.', 'bw' ); ?></p>
            </div>

            <?php if ( isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Mail Marketing settings saved.', 'bw' ); ?></strong></p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'bw_mail_marketing_save', 'bw_mail_marketing_nonce' ); ?>
                <input type="hidden" name="bw_mail_marketing_submit" value="1" />
                <input type="hidden" name="bw_mail_marketing_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

                <div class="bw-admin-action-bar">
                    <div class="bw-admin-action-meta">
                        <?php esc_html_e( 'Configure email capture and newsletter settings.', 'bw' ); ?>
                    </div>
                    <div class="bw-admin-action-buttons">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bw' ); ?></button>
                    </div>
                </div>

                <section class="bw-admin-card bw-admin-card-mail-marketing">
                    <h2 class="bw-admin-card-title"><?php esc_html_e( 'Sections', 'bw' ); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e( 'Switch between global Brevo settings and channel-specific controls.', 'bw' ); ?></p>
                    <nav class="nav-tab-wrapper bw-admin-tabs">
                        <a class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $general_url ); ?>"><?php esc_html_e( 'General', 'bw' ); ?></a>
                        <a class="nav-tab <?php echo 'checkout' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $checkout_url ); ?>"><?php esc_html_e( 'Checkout', 'bw' ); ?></a>
                        <a class="nav-tab <?php echo 'subscription' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $subscription_url ); ?>"><?php esc_html_e( 'Subscription', 'bw' ); ?></a>
                    </nav>
                </section>

                <?php if ( 'general' === $active_tab ) : ?>
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Brevo Connection', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'Set API access and target list for newsletter sync.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
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
                                <th scope="row"><label for="bw_mail_marketing_general_unconfirmed_list_id"><?php esc_html_e( 'Unconfirmed list', 'bw' ); ?></label></th>
                                <td>
                                    <?php if ( ! empty( $lists_data['success'] ) ) : ?>
                                        <select id="bw_mail_marketing_general_unconfirmed_list_id" name="bw_mail_marketing_general_unconfirmed_list_id">
                                            <option value="0"><?php esc_html_e( '— None —', 'bw' ); ?></option>
                                            <?php foreach ( $lists_data['lists'] as $list ) : ?>
                                                <option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( (int) $general_settings['unconfirmed_list_id'], (int) $list['id'] ); ?>>
                                                    <?php echo esc_html( sprintf( '#%d - %s', (int) $list['id'], $list['name'] ) ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <input type="number" id="bw_mail_marketing_general_unconfirmed_list_id" name="bw_mail_marketing_general_unconfirmed_list_id" value="<?php echo esc_attr( $general_settings['unconfirmed_list_id'] ); ?>" class="small-text" min="0" />
                                    <?php endif; ?>
                                    <p class="description"><?php esc_html_e( 'Optional. When set, contacts are assigned to this list immediately on Double opt-in submit, making them visible in Brevo before they confirm.', 'bw' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </section>

                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Opt-in and Sender', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'Set opt-in mode and sender information for outgoing communication.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
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
                        </table>
                    </section>

                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Advanced', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'Control logging and customer attribute synchronization behavior.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
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
                    </section>
                <?php elseif ( 'checkout' === $active_tab ) : ?>
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Checkout Opt-in', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'Control visibility and default behavior of the newsletter checkbox.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
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
                        </table>
                    </section>

                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Subscription Behavior', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'Define timing, opt-in mode override, and field placement strategy.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
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
                    </section>
                <?php else : ?>
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Elementor Subscription Channel', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'Configure the reusable newsletter widget for Elementor and other site-wide subscription surfaces.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable subscription widget', 'bw' ); ?></th>
                                <td>
                                    <label><input type="checkbox" name="bw_mail_marketing_subscription_enabled" value="1" <?php checked( $subscription_settings['enabled'], 1 ); ?> /> <?php esc_html_e( 'Allow the Elementor newsletter widget to submit to Brevo.', 'bw' ); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_source_key"><?php esc_html_e( 'Consent source key', 'bw' ); ?></label></th>
                                <td>
                                    <input type="text" id="bw_mail_marketing_subscription_source_key" name="bw_mail_marketing_subscription_source_key" value="<?php echo esc_attr( $subscription_settings['source_key'] ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Default source stored in Brevo attributes for this widget channel.', 'bw' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_list_mode"><?php esc_html_e( 'List selection', 'bw' ); ?></label></th>
                                <td>
                                    <select id="bw_mail_marketing_subscription_list_mode" name="bw_mail_marketing_subscription_list_mode">
                                        <option value="inherit" <?php selected( $subscription_settings['list_mode'], 'inherit' ); ?>><?php esc_html_e( 'Inherit General main list', 'bw' ); ?></option>
                                        <option value="custom" <?php selected( $subscription_settings['list_mode'], 'custom' ); ?>><?php esc_html_e( 'Use custom list', 'bw' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_list_id"><?php esc_html_e( 'Brevo list', 'bw' ); ?></label></th>
                                <td>
                                    <?php if ( ! empty( $lists_data['success'] ) ) : ?>
                                        <select id="bw_mail_marketing_subscription_list_id" name="bw_mail_marketing_subscription_list_id">
                                            <option value="0"><?php esc_html_e( 'Select list', 'bw' ); ?></option>
                                            <?php foreach ( $lists_data['lists'] as $list ) : ?>
                                                <option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( (int) $subscription_settings['list_id'], (int) $list['id'] ); ?>>
                                                    <?php echo esc_html( sprintf( '#%d - %s', (int) $list['id'], $list['name'] ) ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <input type="number" id="bw_mail_marketing_subscription_list_id" name="bw_mail_marketing_subscription_list_id" value="<?php echo esc_attr( $subscription_settings['list_id'] ); ?>" class="small-text" min="0" />
                                        <p class="description"><?php echo esc_html( $lists_data['message'] ); ?></p>
                                    <?php endif; ?>
                                    <p class="description"><?php esc_html_e( 'Used only when List selection is set to custom.', 'bw' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_channel_optin_mode"><?php esc_html_e( 'Channel opt-in mode', 'bw' ); ?></label></th>
                                <td>
                                    <select id="bw_mail_marketing_subscription_channel_optin_mode" name="bw_mail_marketing_subscription_channel_optin_mode">
                                        <option value="inherit" <?php selected( $subscription_settings['channel_optin_mode'], 'inherit' ); ?>><?php esc_html_e( 'Inherit General setting', 'bw' ); ?></option>
                                        <option value="single_opt_in" <?php selected( $subscription_settings['channel_optin_mode'], 'single_opt_in' ); ?>><?php esc_html_e( 'Force single opt-in', 'bw' ); ?></option>
                                        <option value="double_opt_in" <?php selected( $subscription_settings['channel_optin_mode'], 'double_opt_in' ); ?>><?php esc_html_e( 'Force double opt-in', 'bw' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Privacy consent required', 'bw' ); ?></th>
                                <td>
                                    <label><input type="checkbox" name="bw_mail_marketing_subscription_consent_required" value="1" <?php checked( $subscription_settings['consent_required'], 1 ); ?> /> <?php esc_html_e( 'Require privacy checkbox before submit (recommended for GDPR).', 'bw' ); ?></label>
                                </td>
                            </tr>
                        </table>
                    </section>

                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Widget Copy', 'bw' ); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e( 'These labels are used by the fixed-design Elementor newsletter widget.', 'bw' ); ?></p>
                        <table class="form-table bw-admin-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_name_label"><?php esc_html_e( 'Name label', 'bw' ); ?></label></th>
                                <td><input type="text" id="bw_mail_marketing_subscription_name_label" name="bw_mail_marketing_subscription_name_label" value="<?php echo esc_attr( $subscription_settings['name_label'] ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_email_label"><?php esc_html_e( 'Email label', 'bw' ); ?></label></th>
                                <td><input type="text" id="bw_mail_marketing_subscription_email_label" name="bw_mail_marketing_subscription_email_label" value="<?php echo esc_attr( $subscription_settings['email_label'] ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_consent_prefix"><?php esc_html_e( 'Consent prefix', 'bw' ); ?></label></th>
                                <td>
                                    <input type="text" id="bw_mail_marketing_subscription_consent_prefix" name="bw_mail_marketing_subscription_consent_prefix" value="<?php echo esc_attr( $subscription_settings['consent_prefix'] ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'The privacy policy link label is rendered separately next to this text.', 'bw' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_privacy_link_label"><?php esc_html_e( 'Privacy link label', 'bw' ); ?></label></th>
                                <td><input type="text" id="bw_mail_marketing_subscription_privacy_link_label" name="bw_mail_marketing_subscription_privacy_link_label" value="<?php echo esc_attr( $subscription_settings['privacy_link_label'] ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_privacy_url"><?php esc_html_e( 'Privacy policy URL', 'bw' ); ?></label></th>
                                <td>
                                    <input type="url" id="bw_mail_marketing_subscription_privacy_url" name="bw_mail_marketing_subscription_privacy_url" value="<?php echo esc_attr( $subscription_settings['privacy_url'] ); ?>" class="regular-text code" placeholder="<?php echo esc_attr( function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '' ); ?>" />
                                    <p class="description"><?php esc_html_e( 'Optional override for the Privacy Policy link used by the subscription widget.', 'bw' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_button_text"><?php esc_html_e( 'Button text', 'bw' ); ?></label></th>
                                <td><input type="text" id="bw_mail_marketing_subscription_button_text" name="bw_mail_marketing_subscription_button_text" value="<?php echo esc_attr( $subscription_settings['button_text'] ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_success_message"><?php esc_html_e( 'Success message', 'bw' ); ?></label></th>
                                <td><textarea id="bw_mail_marketing_subscription_success_message" name="bw_mail_marketing_subscription_success_message" rows="3" class="large-text"><?php echo esc_textarea( $subscription_settings['success_message'] ); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_error_message"><?php esc_html_e( 'Error message', 'bw' ); ?></label></th>
                                <td><textarea id="bw_mail_marketing_subscription_error_message" name="bw_mail_marketing_subscription_error_message" rows="3" class="large-text"><?php echo esc_textarea( $subscription_settings['error_message'] ); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw_mail_marketing_subscription_consent_required_message"><?php esc_html_e( 'Consent required message', 'bw' ); ?></label></th>
                                <td><textarea id="bw_mail_marketing_subscription_consent_required_message" name="bw_mail_marketing_subscription_consent_required_message" rows="2" class="large-text"><?php echo esc_textarea( $subscription_settings['consent_required_message'] ); ?></textarea></td>
                            </tr>
                        </table>
                    </section>
                <?php endif; ?>

                <div class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Mail Marketing settings', 'bw' ); ?></button>
                </div>
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

        $css_file = BW_MEW_PATH . 'admin/css/bw-order-newsletter-status.css';
        $css_version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';
        wp_enqueue_style(
            'bw-order-newsletter-status',
            BW_MEW_URL . 'admin/css/bw-order-newsletter-status.css',
            [],
            $css_version
        );

        wp_localize_script(
            'bw-order-newsletter-status',
            'bwOrderNewsletterStatus',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'bw_brevo_order_actions' ),
                'errorText' => esc_html__( 'Action failed. Please retry.', 'bw' ),
                'retryNoOptInConfirm' => esc_html__( 'No opt-in recorded. Retry will not subscribe. Continue?', 'bw' ),
                'workingText' => esc_html__( 'Working...', 'bw' ),
            ]
        );
    }

    /**
     * Enqueue user-profile mail marketing panel assets.
     *
     * @param string $hook Admin hook suffix.
     */
    public function enqueue_user_mail_marketing_assets( $hook ) {
        if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
            return;
        }

        $js_file = BW_MEW_PATH . 'admin/js/bw-user-mail-marketing.js';
        $version = file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0';

        wp_enqueue_script(
            'bw-user-mail-marketing',
            BW_MEW_URL . 'admin/js/bw-user-mail-marketing.js',
            [ 'jquery' ],
            $version,
            true
        );

        wp_localize_script(
            'bw-user-mail-marketing',
            'bwUserMailMarketing',
            [
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'bw_brevo_user_actions' ),
                'errorText' => esc_html__( 'Action failed. Please retry.', 'bw' ),
            ]
        );
    }

    /**
     * Render Mail Marketing panel as a top full-width container on user screens.
     */
    public function render_user_mail_marketing_top_panel() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->id, [ 'profile', 'user-edit' ], true ) ) {
            return;
        }

        $user_id = 0;
        if ( 'profile' === $screen->id ) {
            $user_id = get_current_user_id();
        } elseif ( isset( $_GET['user_id'] ) ) {
            $user_id = absint( wp_unslash( $_GET['user_id'] ) );
        }

        if ( $user_id <= 0 ) {
            return;
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            return;
        }

        $this->render_user_mail_marketing_panel( $user );
    }

    /**
     * Render Mail Marketing diagnostics panel content.
     *
     * @param WP_User $user User object.
     */
    public function render_user_mail_marketing_panel( $user ) {
        if ( ! $user instanceof WP_User ) {
            return;
        }

        if ( ! current_user_can( 'edit_user', $user->ID ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $payload = $this->build_user_status_payload( $user );
        $user_sync_allowed = $this->can_sync_user_status( $user );
        $sync_disabled_attr = $user_sync_allowed ? '' : 'disabled="disabled"';
        $sync_aria_disabled_attr = $user_sync_allowed ? 'aria-disabled="false"' : 'aria-disabled="true"';
        $sync_disabled_title = $user_sync_allowed
            ? ''
            : esc_attr__( 'Disabled: no consent recorded for this user.', 'bw' );
        ?>
        <style id="bw-user-mail-marketing-style">
            #bw-user-mail-marketing-panel-wrap { margin: 16px 0 18px; }
            #bw-user-mail-marketing-panel .bw-newsletter-status-badge.bw-status--subscribed { background:#d1f8e0;color:#0a3622;border:1px solid #75d39a; }
            #bw-user-mail-marketing-panel .bw-newsletter-status-badge.bw-status--pending { background:#e7eefc;color:#1b3b7a;border:1px solid #9ab1e9; }
            #bw-user-mail-marketing-panel .bw-newsletter-status-badge.bw-status--neutral { background:#f3f4f6;color:#2c3338;border:1px solid #c3c4c7; }
            #bw-user-mail-marketing-panel .bw-newsletter-status-badge.bw-status--error { background:#fce2e2;color:#691010;border:1px solid #e99a9a; }
            #bw-user-mail-marketing-panel {
                border: 1px solid #d0d5dd;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 4px 16px rgba(16, 24, 40, 0.06);
                overflow: hidden;
            }
            #bw-user-mail-marketing-panel .bw-mm-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 14px 16px;
                border-bottom: 1px solid #e4e7ec;
                background: linear-gradient(180deg, #f8fafc 0%, #f2f4f7 100%);
            }
            #bw-user-mail-marketing-panel .bw-mm-title {
                margin: 0;
                font-size: 16px;
                font-weight: 700;
                color: #101828;
            }
            #bw-user-mail-marketing-panel .bw-mm-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            #bw-user-mail-marketing-panel #bw-user-inline-message {
                margin: 10px 0 0;
                padding: 0 16px;
                font-weight: 600;
            }
            #bw-user-mail-marketing-panel .bw-user-mail-marketing-help {
                margin: 8px 0 0;
                padding: 0 16px;
                color: #646970;
            }
            #bw-user-mail-marketing-panel .bw-mm-table-wrap { padding: 10px 16px 16px; }
            #bw-user-mail-marketing-panel .bw-mm-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                border: 1px solid #e4e7ec;
                border-radius: 10px;
                overflow: hidden;
            }
            #bw-user-mail-marketing-panel .bw-mm-table th,
            #bw-user-mail-marketing-panel .bw-mm-table td {
                padding: 11px 12px;
                border-bottom: 1px solid #eaecf0;
                text-align: left;
                vertical-align: middle;
            }
            #bw-user-mail-marketing-panel .bw-mm-table tr:last-child th,
            #bw-user-mail-marketing-panel .bw-mm-table tr:last-child td { border-bottom: 0; }
            #bw-user-mail-marketing-panel .bw-mm-table th {
                width: 260px;
                background: #f9fafb;
                color: #111827;
                font-weight: 700;
            }
            #bw-user-mail-marketing-panel .bw-mm-table td { background: #fff; color: #1f2937; }
        </style>
        <div id="bw-user-mail-marketing-panel-wrap">
            <div id="bw-user-mail-marketing-panel" data-user-id="<?php echo esc_attr( $user->ID ); ?>" data-sync-allowed="<?php echo $user_sync_allowed ? '1' : '0'; ?>">
                <div class="bw-mm-header">
                    <h2 class="bw-mm-title"><?php esc_html_e( 'Mail Marketing - Brevo', 'bw' ); ?></h2>
                    <div class="bw-mm-actions">
                        <span id="bw-user-status-badge" class="bw-newsletter-status-badge <?php echo esc_attr( $this->get_status_badge_class( $payload['status'] ) ); ?>" style="display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;">
                            <?php echo esc_html( $payload['statusLabel'] ); ?>
                        </span>
                        <button type="button" class="button" id="bw-user-check-status"><?php esc_html_e( 'Check Brevo', 'bw' ); ?></button>
                        <button type="button" class="button button-secondary" id="bw-user-sync-status" <?php echo $sync_disabled_attr; ?> <?php echo $sync_aria_disabled_attr; ?> title="<?php echo $sync_disabled_title; ?>"><?php esc_html_e( 'Sync status', 'bw' ); ?></button>
                    </div>
                </div>
                <?php if ( ! $user_sync_allowed ) : ?>
                    <p class="description bw-user-mail-marketing-help"><?php esc_html_e( 'Disabled: no consent recorded for this user.', 'bw' ); ?></p>
                <?php endif; ?>
                <p id="bw-user-inline-message"></p>
                <div class="bw-mm-table-wrap">
                    <table class="bw-mm-table">
                        <tbody>
                            <tr><th><?php esc_html_e( 'Email', 'bw' ); ?></th><td data-bw-user-field="email"><?php echo esc_html( $payload['meta']['email'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'List', 'bw' ); ?></th><td data-bw-user-field="list_display"><?php echo esc_html( $payload['meta']['list_display'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Current status', 'bw' ); ?></th><td data-bw-user-field="status"><?php echo esc_html( $payload['meta']['status'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Status reason', 'bw' ); ?></th><td data-bw-user-field="status_reason"><?php echo esc_html( $payload['meta']['status_reason'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Last error', 'bw' ); ?></th><td data-bw-user-field="last_error"><?php echo esc_html( $payload['meta']['last_error'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Consent timestamp', 'bw' ); ?></th><td data-bw-user-field="consent_at"><?php echo esc_html( $payload['meta']['consent_at'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Consent source', 'bw' ); ?></th><td data-bw-user-field="consent_source"><?php echo esc_html( $payload['meta']['consent_source'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Last checked at', 'bw' ); ?></th><td data-bw-user-field="last_checked_at"><?php echo esc_html( $payload['meta']['last_checked_at'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Brevo contact id', 'bw' ); ?></th><td data-bw-user-field="contact_id"><?php echo esc_html( $payload['meta']['contact_id'] ); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register Newsletter column in WooCommerce orders list tables.
     *
     * @param array $columns Existing columns.
     *
     * @return array
     */
    public function register_orders_list_newsletter_column( $columns ) {
        if ( ! is_array( $columns ) ) {
            return $columns;
        }

        if ( isset( $columns['bw_newsletter'] ) && isset( $columns['bw_newsletter_source'] ) ) {
            return $columns;
        }

        $new_columns = [];
        $inserted = false;

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( in_array( $key, [ 'order_status', 'status' ], true ) ) {
                $new_columns['bw_newsletter'] = __( 'Newsletter', 'bw' );
                $new_columns['bw_newsletter_source'] = __( 'Source', 'bw' );
                $inserted = true;
            }
        }

        if ( ! $inserted ) {
            $new_columns['bw_newsletter'] = __( 'Newsletter', 'bw' );
            $new_columns['bw_newsletter_source'] = __( 'Source', 'bw' );
        }

        return $new_columns;
    }

    /**
     * Render Newsletter column for classic shop_order posts table.
     *
     * @param string $column  Column key.
     * @param int    $post_id Post ID.
     */
    public function render_orders_list_newsletter_column( $column, $post_id = 0 ) {
        if ( ! in_array( $column, [ 'bw_newsletter', 'bw_newsletter_source' ], true ) ) {
            return;
        }

        $order_id = absint( $post_id );
        if ( $order_id <= 0 && isset( $GLOBALS['post']->ID ) ) {
            $order_id = absint( $GLOBALS['post']->ID );
        }

        if ( 'bw_newsletter_source' === $column ) {
            $this->render_orders_list_source_cell( $order_id );
            return;
        }

        $this->render_orders_list_newsletter_cell( $order_id );
    }

    /**
     * Render Newsletter column for HPOS wc-orders table.
     *
     * @param string         $column Column key.
     * @param WC_Order|int   $order  Order object or ID.
     */
    public function render_hpos_orders_list_newsletter_column( $column, $order ) {
        if ( ! in_array( $column, [ 'bw_newsletter', 'bw_newsletter_source' ], true ) ) {
            return;
        }

        $order_id = 0;
        if ( $order instanceof WC_Order ) {
            $order_id = $order->get_id();
        } elseif ( is_numeric( $order ) ) {
            $order_id = absint( $order );
        }

        if ( 'bw_newsletter_source' === $column ) {
            $this->render_orders_list_source_cell( $order_id );
            return;
        }

        $this->render_orders_list_newsletter_cell( $order_id );
    }

    /**
     * Print lightweight styles for Newsletter orders-list column.
     */
    public function print_orders_list_newsletter_column_css() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $screen_id = isset( $screen->id ) ? (string) $screen->id : '';
        $post_type = isset( $screen->post_type ) ? (string) $screen->post_type : '';
        if ( 'shop_order' !== $post_type && 'woocommerce_page_wc-orders' !== $screen_id ) {
            return;
        }
        ?>
        <style id="bw-orders-newsletter-column-css">
            .column-bw_newsletter { width: 160px; }
            .column-bw_newsletter_source { width: 140px; }
            .bw-newsletter-col {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                font-weight: 600;
            }
            .bw-newsletter-col__dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                display: inline-block;
            }
            .bw-newsletter-col--subscribed .bw-newsletter-col__dot { background: #1f9d55; }
            .bw-newsletter-col--pending .bw-newsletter-col__dot { background: #d6a100; }
            .bw-newsletter-col--no .bw-newsletter-col__dot { background: #c0392b; }
            .bw-newsletter-col--neutral .bw-newsletter-col__dot { background: #98a2b3; }
            .bw-newsletter-col__warn {
                color: #b54708;
                vertical-align: middle;
                line-height: 1;
            }
            .bw-newsletter-source-pill {
                display: inline-block;
                padding: 3px 9px;
                border-radius: 999px;
                border: 1px solid #d0d5dd;
                background: #f8f9fb;
                color: #475467;
                font-size: 11px;
                line-height: 1.2;
                text-transform: lowercase;
            }
        </style>
        <?php
    }

    /**
     * Render source cell using order meta only (no API calls).
     *
     * @param int $order_id Order ID.
     */
    private function render_orders_list_source_cell( $order_id ) {
        if ( $order_id <= 0 ) {
            echo '&mdash;';
            return;
        }

        $source = trim( (string) get_post_meta( $order_id, '_bw_subscribe_consent_source', true ) );
        if ( '' === $source ) {
            echo '&mdash;';
            return;
        }

        printf(
            '<span class="bw-newsletter-source-pill">%s</span>',
            esc_html( sanitize_key( $source ) )
        );
    }

    /**
     * Render Newsletter cell value based on order meta only (no API calls).
     *
     * @param int $order_id Order ID.
     */
    private function render_orders_list_newsletter_cell( $order_id ) {
        if ( $order_id <= 0 ) {
            echo '&mdash;';
            return;
        }

        $data = $this->get_order_newsletter_list_data( $order_id );
        $tooltip = sprintf(
            /* translators: 1: status, 2: consent timestamp */
            __( 'Brevo status: %1$s | Consent: %2$s', 'bw' ),
            $data['brevo_status'],
            '' !== $data['consent_at'] ? $data['consent_at'] : __( 'n/a', 'bw' )
        );

        if ( 'error' === $data['brevo_status'] ) {
            $error_tooltip = '' !== $data['last_error'] ? $data['last_error'] : __( 'Unknown error', 'bw' );
            printf(
                '<span class="bw-newsletter-col bw-newsletter-col--neutral" title="%1$s"><span class="dashicons dashicons-warning bw-newsletter-col__warn" title="%2$s" aria-hidden="true"></span> %3$s</span>',
                esc_attr( $tooltip ),
                esc_attr( $error_tooltip ),
                esc_html__( 'Error', 'bw' )
            );
            return;
        }

        if ( 1 === $data['opt_in'] && 'subscribed' === $data['brevo_status'] ) {
            printf(
                '<span class="bw-newsletter-col bw-newsletter-col--subscribed" title="%1$s"><span class="bw-newsletter-col__dot" aria-hidden="true"></span> %2$s</span>',
                esc_attr( $tooltip ),
                esc_html__( 'Subscribed', 'bw' )
            );
            return;
        }

        if ( 1 === $data['opt_in'] && 'pending' === $data['brevo_status'] ) {
            printf(
                '<span class="bw-newsletter-col bw-newsletter-col--pending" title="%1$s"><span class="bw-newsletter-col__dot" aria-hidden="true"></span> %2$s</span>',
                esc_attr( $tooltip ),
                esc_html__( 'Pending', 'bw' )
            );
            return;
        }

        if ( 0 === $data['opt_in'] ) {
            printf(
                '<span class="bw-newsletter-col bw-newsletter-col--no" title="%1$s"><span class="bw-newsletter-col__dot" aria-hidden="true"></span> %2$s</span>',
                esc_attr( $tooltip ),
                esc_html__( 'No', 'bw' )
            );
            return;
        }

        printf(
            '<span class="bw-newsletter-col bw-newsletter-col--neutral" title="%1$s"><span class="bw-newsletter-col__dot" aria-hidden="true"></span> %2$s</span>',
            esc_attr( $tooltip ),
            esc_html( ucfirst( str_replace( '_', ' ', $data['brevo_status'] ) ) )
        );
    }

    /**
     * Get newsletter list-column data from order meta only.
     *
     * @param int $order_id Order ID.
     *
     * @return array
     */
    private function get_order_newsletter_list_data( $order_id ) {
        $opt_in = (int) get_post_meta( $order_id, '_bw_subscribe_newsletter', true );

        $status = (string) get_post_meta( $order_id, '_bw_brevo_status', true );
        if ( '' === $status ) {
            $status = (string) get_post_meta( $order_id, '_bw_brevo_subscribed', true );
        }
        if ( '1' === $status ) {
            $status = 'subscribed';
        }
        if ( '' === $status ) {
            $status = 'not_subscribed';
        }

        $last_error = (string) get_post_meta( $order_id, '_bw_last_error', true );
        if ( '' === $last_error ) {
            $last_error = (string) get_post_meta( $order_id, '_bw_brevo_error_last', true );
        }

        return [
            'opt_in'       => $opt_in,
            'brevo_status' => sanitize_key( $status ),
            'last_error'   => sanitize_text_field( $last_error ),
            'consent_at'   => (string) get_post_meta( $order_id, '_bw_subscribe_consent_at', true ),
        ];
    }

    /**
     * Render filters above classic orders list.
     */
    public function render_orders_newsletter_filters_classic() {
        global $typenow;

        if ( 'shop_order' !== $typenow ) {
            return;
        }

        $this->render_orders_newsletter_filters_controls();
    }

    /**
     * Render filters above HPOS orders list.
     */
    public function render_orders_newsletter_filters_hpos() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'woocommerce_page_wc-orders' !== (string) $screen->id ) {
            return;
        }

        $this->render_orders_newsletter_filters_controls();
    }

    /**
     * Print shared filter controls.
     */
    private function render_orders_newsletter_filters_controls() {
        $status = $this->get_orders_newsletter_filter_status();
        $source = $this->get_orders_newsletter_filter_source();
        ?>
        <select name="bw_newsletter_status" id="bw-newsletter-status-filter">
            <option value=""><?php esc_html_e( 'Newsletter status: Any', 'bw' ); ?></option>
            <option value="subscribed" <?php selected( $status, 'subscribed' ); ?>><?php esc_html_e( 'Subscribed', 'bw' ); ?></option>
            <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'bw' ); ?></option>
            <option value="no_opt_in" <?php selected( $status, 'no_opt_in' ); ?>><?php esc_html_e( 'No opt-in', 'bw' ); ?></option>
            <option value="error" <?php selected( $status, 'error' ); ?>><?php esc_html_e( 'Error', 'bw' ); ?></option>
        </select>
        <select name="bw_newsletter_source" id="bw-newsletter-source-filter">
            <option value=""><?php esc_html_e( 'Source: Any', 'bw' ); ?></option>
            <?php foreach ( $this->get_newsletter_source_filter_options() as $option ) : ?>
                <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $source, $option ); ?>><?php echo esc_html( $option ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Apply filters to classic orders query.
     *
     * @param WP_Query $query Query object.
     */
    public function apply_orders_newsletter_filters_classic( $query ) {
        if ( ! $query instanceof WP_Query || ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $post_type = $query->get( 'post_type' );
        if ( 'shop_order' !== $post_type ) {
            return;
        }

        $meta_query = $this->build_orders_newsletter_meta_query_filters();
        if ( empty( $meta_query ) ) {
            return;
        }

        $existing_meta_query = $query->get( 'meta_query' );
        if ( ! is_array( $existing_meta_query ) ) {
            $existing_meta_query = [];
        }

        $query->set( 'meta_query', array_merge( $existing_meta_query, $meta_query ) );
    }

    /**
     * Apply filters to HPOS orders query args.
     *
     * @param array $query_args Query args.
     *
     * @return array
     */
    public function apply_orders_newsletter_filters_hpos( $query_args ) {
        if ( ! is_array( $query_args ) ) {
            $query_args = [];
        }

        $meta_query = $this->build_orders_newsletter_meta_query_filters();
        if ( empty( $meta_query ) ) {
            return $query_args;
        }

        if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
            $query_args['meta_query'] = [];
        }

        $query_args['meta_query'] = array_merge( $query_args['meta_query'], $meta_query );
        return $query_args;
    }

    /**
     * Register orders bulk action.
     *
     * @param array $actions Existing actions.
     *
     * @return array
     */
    public function register_orders_bulk_resync_action( $actions ) {
        if ( ! is_array( $actions ) ) {
            return $actions;
        }

        $actions['bw_mail_marketing_resync'] = __( 'Mail Marketing: Resync to Brevo', 'bw' );
        return $actions;
    }

    /**
     * Handle orders bulk resync action.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $action      Action key.
     * @param array  $order_ids   Selected order IDs.
     *
     * @return string
     */
    public function handle_orders_bulk_resync_action( $redirect_to, $action, $order_ids ) {
        if ( 'bw_mail_marketing_resync' !== $action ) {
            return $redirect_to;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return add_query_arg(
                [
                    'bw_resync_processed'  => 0,
                    'bw_resync_no_consent' => 0,
                    'bw_resync_error'      => 1,
                ],
                $redirect_to
            );
        }

        if ( ! is_array( $order_ids ) ) {
            $order_ids = [];
        }

        $counts = [
            'processed'          => 0,
            'skipped_no_consent' => 0,
            'error'              => 0,
        ];

        $chunks = array_chunk( array_map( 'absint', $order_ids ), 25 );
        foreach ( $chunks as $chunk ) {
            foreach ( $chunk as $order_id ) {
                if ( $order_id <= 0 ) {
                    continue;
                }

                $order = wc_get_order( $order_id );
                if ( ! $order instanceof WC_Order ) {
                    $counts['error']++;
                    continue;
                }

                $result = $this->resync_order_to_brevo( $order, 'bulk_resync' );
                if ( isset( $result['result'] ) && isset( $counts[ $result['result'] ] ) ) {
                    $counts[ $result['result'] ]++;
                    continue;
                }
                $counts['error']++;
            }
        }

        return add_query_arg(
            [
                'bw_resync_processed'  => (int) $counts['processed'],
                'bw_resync_no_consent' => (int) $counts['skipped_no_consent'],
                'bw_resync_error'      => (int) $counts['error'],
            ],
            $redirect_to
        );
    }

    /**
     * Render admin notice after bulk resync.
     */
    public function render_bulk_resync_admin_notice() {
        if ( ! isset( $_GET['bw_resync_processed'], $_GET['bw_resync_no_consent'], $_GET['bw_resync_error'] ) ) {
            return;
        }

        $processed = absint( wp_unslash( $_GET['bw_resync_processed'] ) );
        $skipped_no_consent = absint( wp_unslash( $_GET['bw_resync_no_consent'] ) );
        $error = absint( wp_unslash( $_GET['bw_resync_error'] ) );
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: 1: processed count, 2: skipped no-consent count, 3: error count */
                            __( 'Resync complete: %1$d processed, %2$d skipped (no consent), %3$d errors.', 'bw' ),
                            $processed,
                            $skipped_no_consent,
                            $error
                        )
                    );
                    ?>
                </strong>
            </p>
        </div>
        <?php
    }

    /**
     * Build meta_query filters from admin request.
     *
     * @return array
     */
    private function build_orders_newsletter_meta_query_filters() {
        $meta_query = [];
        $status = $this->get_orders_newsletter_filter_status();
        $source = $this->get_orders_newsletter_filter_source();

        if ( '' !== $status ) {
            $status_clause = $this->build_newsletter_status_meta_query( $status );
            if ( ! empty( $status_clause ) ) {
                $meta_query[] = $status_clause;
            }
        }

        if ( '' !== $source ) {
            $meta_query[] = [
                'key'     => '_bw_subscribe_consent_source',
                'value'   => $source,
                'compare' => '=',
            ];
        }

        return $meta_query;
    }

    /**
     * Build newsletter-status meta query clause.
     *
     * @param string $status Selected status.
     *
     * @return array
     */
    private function build_newsletter_status_meta_query( $status ) {
        $status = sanitize_key( (string) $status );

        if ( 'subscribed' === $status ) {
            return [
                'relation' => 'AND',
                [
                    'key'     => '_bw_subscribe_newsletter',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => '_bw_brevo_subscribed',
                        'value'   => 'subscribed',
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_bw_brevo_subscribed',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                ],
            ];
        }

        if ( 'pending' === $status ) {
            return [
                'relation' => 'AND',
                [
                    'key'     => '_bw_subscribe_newsletter',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'key'     => '_bw_brevo_subscribed',
                    'value'   => 'pending',
                    'compare' => '=',
                ],
            ];
        }

        if ( 'error' === $status ) {
            return [
                'relation' => 'AND',
                [
                    'key'     => '_bw_subscribe_newsletter',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'key'     => '_bw_brevo_subscribed',
                    'value'   => 'error',
                    'compare' => '=',
                ],
            ];
        }

        if ( 'no_opt_in' === $status ) {
            return [
                'relation' => 'OR',
                [
                    'key'     => '_bw_subscribe_newsletter',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_bw_subscribe_newsletter',
                    'value'   => '0',
                    'compare' => '=',
                ],
                [
                    'key'     => '_bw_subscribe_newsletter',
                    'value'   => '',
                    'compare' => '=',
                ],
            ];
        }

        return [];
    }

    /**
     * Read current newsletter status filter from request.
     *
     * @return string
     */
    private function get_orders_newsletter_filter_status() {
        $status = isset( $_GET['bw_newsletter_status'] ) ? sanitize_key( wp_unslash( $_GET['bw_newsletter_status'] ) ) : '';
        return in_array( $status, [ 'subscribed', 'pending', 'no_opt_in', 'error' ], true ) ? $status : '';
    }

    /**
     * Read current source filter from request.
     *
     * @return string
     */
    private function get_orders_newsletter_filter_source() {
        $source = isset( $_GET['bw_newsletter_source'] ) ? sanitize_key( wp_unslash( $_GET['bw_newsletter_source'] ) ) : '';
        return in_array( $source, $this->get_newsletter_source_filter_options(), true ) ? $source : '';
    }

    /**
     * Supported consent sources.
     *
     * @return array
     */
    private function get_newsletter_source_filter_options() {
        return [
            'checkout',
            'coming_soon',
            'footer',
            'popup',
            'my_account',
            'supabase_google',
            'supabase_facebook',
        ];
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
        $consent_gate = $this->can_subscribe_order( $order );
        $retry_disabled_attr = ! empty( $consent_gate['allowed'] ) ? '' : 'disabled="disabled"';
        $retry_aria_disabled_attr = ! empty( $consent_gate['allowed'] ) ? 'aria-disabled="false"' : 'aria-disabled="true"';
        $retry_disabled_title = ! empty( $consent_gate['allowed'] )
            ? ''
            : esc_attr__( 'Disabled: no consent recorded for this order.', 'bw' );
        ?>
        <div id="bw-newsletter-status-panel" class="bw-newsletter-panel wc-metabox" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>" data-retry-allowed="<?php echo ! empty( $consent_gate['allowed'] ) ? '1' : '0'; ?>">
            <div class="bw-newsletter-panel__header">
                <span id="bw-newsletter-status-badge" class="bw-newsletter-status-badge status-badge <?php echo esc_attr( $payload['statusClass'] ); ?>">
                    <span class="dashicons <?php echo esc_attr( $payload['statusIcon'] ); ?>" aria-hidden="true"></span>
                    <span class="bw-newsletter-status-badge__label"><?php echo esc_html( $payload['statusLabel'] ); ?></span>
                </span>
                <div class="bw-newsletter-panel__actions">
                    <button type="button" class="button" id="bw-newsletter-refresh">
                        <span class="bw-btn-text"><?php esc_html_e( 'Check Brevo', 'bw' ); ?></span>
                        <span class="spinner"></span>
                    </button>
                    <button type="button" class="button button-secondary" id="bw-newsletter-retry" <?php echo $retry_disabled_attr; ?> <?php echo $retry_aria_disabled_attr; ?> title="<?php echo $retry_disabled_title; ?>">
                        <span class="bw-btn-text"><?php esc_html_e( 'Retry subscribe', 'bw' ); ?></span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
            <?php if ( empty( $consent_gate['allowed'] ) ) : ?>
                <p class="description bw-newsletter-panel__help"><?php esc_html_e( 'Disabled: no consent recorded for this order.', 'bw' ); ?></p>
            <?php endif; ?>
            <p id="bw-newsletter-inline-message" class="bw-newsletter-panel__notice" aria-live="polite"></p>

            <div class="bw-newsletter-panel__section">
                <h4><?php esc_html_e( 'Consent', 'bw' ); ?></h4>
                <table class="widefat striped bw-newsletter-meta-table">
                    <tbody>
                        <tr>
                            <th><?php esc_html_e( 'Email', 'bw' ); ?></th>
                            <td>
                                <span data-bw-field="email"><?php echo esc_html( $payload['meta']['email'] ); ?></span>
                                <button type="button" class="button-link bw-copy-field" data-copy-field="email" aria-label="<?php esc_attr_e( 'Copy email', 'bw' ); ?>">
                                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'List', 'bw' ); ?></th>
                            <td>
                                <span data-bw-field="list_display"><?php echo esc_html( (string) $payload['meta']['list_display'] ); ?></span>
                                <button type="button" class="button button-link bw-load-lists <?php echo ! empty( $payload['meta']['list_needs_load'] ) ? '' : 'hidden'; ?>" id="bw-newsletter-load-lists"><?php esc_html_e( 'Load lists', 'bw' ); ?></button>
                            </td>
                        </tr>
                        <tr><th><?php esc_html_e( 'Opt-in value', 'bw' ); ?></th><td data-bw-field="opt_in"><?php echo esc_html( (string) $payload['meta']['opt_in'] ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Consent timestamp', 'bw' ); ?></th><td data-bw-field="consent_at"><?php echo esc_html( $payload['meta']['consent_at'] ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Consent source', 'bw' ); ?></th><td data-bw-field="consent_source"><?php echo esc_html( $payload['meta']['consent_source'] ); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="bw-newsletter-panel__section">
                <h4><?php esc_html_e( 'Brevo Sync', 'bw' ); ?></h4>
                <table class="widefat striped bw-newsletter-meta-table">
                    <tbody>
                        <tr><th><?php esc_html_e( 'Current Brevo status', 'bw' ); ?></th><td data-bw-field="brevo_status"><?php echo esc_html( $payload['meta']['brevo_status'] ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Status reason', 'bw' ); ?></th><td data-bw-field="status_reason"><?php echo esc_html( $payload['meta']['status_reason'] ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Last subscription attempt at', 'bw' ); ?></th><td data-bw-field="last_attempt_at"><?php echo esc_html( $payload['meta']['last_attempt_at'] ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Attempt source', 'bw' ); ?></th><td data-bw-field="last_attempt_source"><?php echo esc_html( $payload['meta']['last_attempt_source'] ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Last checked at (remote)', 'bw' ); ?></th><td data-bw-field="last_checked_at"><?php echo esc_html( $payload['meta']['last_checked_at'] ); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="bw-newsletter-panel__advanced">
                <button type="button" class="button-link" id="bw-newsletter-advanced-toggle" aria-expanded="false"><?php esc_html_e( 'Show advanced', 'bw' ); ?></button>
                <div id="bw-newsletter-advanced-content" class="hidden">
                    <table class="widefat striped bw-newsletter-meta-table">
                        <tbody>
                            <tr><th><?php esc_html_e( 'Checkout field received', 'bw' ); ?></th><td data-bw-field="checkout_field_received"><?php echo esc_html( $payload['meta']['checkout_field_received'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Checkout field raw value', 'bw' ); ?></th><td data-bw-field="checkout_field_value_raw"><?php echo esc_html( $payload['meta']['checkout_field_value_raw'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'POST keys snapshot', 'bw' ); ?></th><td data-bw-field="checkout_post_keys_snapshot"><?php echo esc_html( $payload['meta']['checkout_post_keys_snapshot'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Frontend checkbox state', 'bw' ); ?></th><td data-bw-field="frontend_checkbox_state"><?php echo esc_html( $payload['meta']['frontend_checkbox_state'] ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Brevo attributes payload (summary)', 'bw' ); ?></th><td data-bw-field="brevo_attributes_summary"><?php echo esc_html( $payload['meta']['brevo_attributes_summary'] ); ?></td></tr>
                            <tr>
                                <th><?php esc_html_e( 'Brevo contact id', 'bw' ); ?></th>
                                <td>
                                    <span data-bw-field="contact_id"><?php echo esc_html( $payload['meta']['contact_id'] ); ?></span>
                                    <button type="button" class="button-link bw-copy-field" data-copy-field="contact_id" aria-label="<?php esc_attr_e( 'Copy Brevo contact id', 'bw' ); ?>">
                                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                    </button>
                                </td>
                            </tr>
                            <tr><th><?php esc_html_e( 'Last error', 'bw' ); ?></th><td data-bw-field="last_error"><?php echo esc_html( $payload['meta']['last_error'] ); ?></td></tr>
                        </tbody>
                    </table>
                    <span class="hidden" data-bw-field="opt_in_raw"><?php echo esc_html( $payload['meta']['opt_in_raw'] ); ?></span>
                </div>
            </div>
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

        $this->set_attempt_meta( $order, 'refresh_check' );
        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $email   = (string) $order->get_billing_email();
        $list_id = isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0;
        $opt_in  = (int) $order->get_meta( '_bw_subscribe_newsletter', true );

        if ( '' === $email || ! is_email( $email ) ) {
            $this->set_order_status_meta( $order, 'skipped', 'invalid_email' );
            $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Refresh skipped: invalid order email.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'Skipped: invalid order email.', 'bw' ) ) );
        }

        if ( empty( $general['api_key'] ) ) {
            $this->set_order_error_meta( $order, __( 'Brevo API key is not configured.', 'bw' ) );
            $this->log_order_action( $order, $email, 'error', 'refresh', 'Missing API key.' );
            wp_send_json_error( [ 'message' => __( 'Brevo API key is not configured.', 'bw' ) ] );
        }

        if ( $list_id <= 0 ) {
            $this->set_order_status_meta( $order, 'skipped', 'missing_list_id' );
            $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Refresh skipped: missing list id.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'Skipped: missing list ID.', 'bw' ) ) );
        }

        $client = new BW_Brevo_Client( $general['api_key'], BW_Mail_Marketing_Settings::API_BASE_URL );
        $result = $client->get_contact( $email );

        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );

        if ( empty( $result['success'] ) ) {
            if ( isset( $result['code'] ) && 404 === (int) $result['code'] ) {
                $this->set_order_status_meta( $order, 'skipped', 'contact_not_found' );
                delete_post_meta( $order->get_id(), '_bw_brevo_contact_id' );

                $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Contact not found in Brevo.' );
                if ( 1 !== $opt_in ) {
                    wp_send_json_success( $this->build_order_status_payload( $order, __( 'No opt-in — contact will not be created.', 'bw' ) ) );
                }
                wp_send_json_success( $this->build_order_status_payload( $order, __( 'Contact not found in Brevo yet. You can retry or check Brevo.', 'bw' ) ) );
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
            $this->set_order_status_meta( $order, 'skipped', 'contact_blocklisted' );
            $this->log_order_action( $order, $email, 'skipped', 'refresh', 'Contact is blocklisted/unsubscribed.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'Contact is blocklisted/unsubscribed.', 'bw' ) ) );
        }

        if ( $in_list ) {
            $this->set_order_status_meta( $order, 'subscribed', 'already_subscribed' );
            $this->log_order_action( $order, $email, 'subscribed', 'refresh', 'Contact found in configured list.' );
            wp_send_json_success( $this->build_order_status_payload( $order, __( 'Synced with Brevo successfully.', 'bw' ) ) );
        }

        $this->set_order_status_meta( $order, 'skipped', 'not_in_list' );
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

        $result = $this->resync_order_to_brevo( $order, 'manual_retry' );
        if ( in_array( $result['result'], [ 'error', 'skipped_no_consent' ], true ) ) {
            if ( 'skipped_no_consent' === $result['result'] ) {
                wp_send_json_error( [ 'message' => __( 'No opt-in recorded. Cannot subscribe this customer.', 'bw' ) ] );
            }
            wp_send_json_error( [ 'message' => $result['message'] ] );
        }

        wp_send_json_success( $this->build_order_status_payload( $order, $result['message'] ) );
    }

    /**
     * AJAX: load Brevo lists into cache and refresh panel payload.
     */
    public function handle_order_load_lists() {
        $order = $this->get_order_from_ajax_request();
        if ( ! $order ) {
            return;
        }

        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $api_key = isset( $general['api_key'] ) ? (string) $general['api_key'] : '';
        if ( '' === $api_key ) {
            wp_send_json_error( [ 'message' => __( 'Brevo API key is not configured.', 'bw' ) ] );
        }

        $lists = $this->get_brevo_lists( $api_key );
        if ( empty( $lists['success'] ) ) {
            $message = isset( $lists['message'] ) ? (string) $lists['message'] : __( 'Unable to load lists from Brevo.', 'bw' );
            wp_send_json_error( [ 'message' => $message ] );
        }

        wp_send_json_success( $this->build_order_status_payload( $order, __( 'Lists cache updated.', 'bw' ) ) );
    }

    /**
     * AJAX: check user contact status in Brevo (read-only).
     */
    public function handle_user_check_status() {
        $user = $this->get_user_from_ajax_request();
        if ( ! $user ) {
            return;
        }

        $payload = $this->fetch_user_brevo_payload( $user, false );
        if ( empty( $payload['success'] ) ) {
            wp_send_json_error( [ 'message' => $payload['message'] ] );
        }

        $this->log_user_action( $user, $payload['email'], $payload['status'], 'check', $payload['message'] );
        wp_send_json_success( $payload['data'] );
    }

    /**
     * AJAX: sync user contact status from Brevo to user meta (write).
     */
    public function handle_user_sync_status() {
        $user = $this->get_user_from_ajax_request();
        if ( ! $user ) {
            return;
        }

        $payload = $this->fetch_user_brevo_payload( $user, true );
        if ( empty( $payload['success'] ) ) {
            wp_send_json_error( [ 'message' => $payload['message'] ] );
        }

        $this->log_user_action( $user, $payload['email'], $payload['status'], 'sync', $payload['message'] );
        wp_send_json_success( $payload['data'] );
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
     * Validate AJAX request and resolve user.
     *
     * @return WP_User|null
     */
    private function get_user_from_ajax_request() {
        check_ajax_referer( 'bw_brevo_user_actions', 'nonce' );

        $user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
        if ( $user_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid user id.', 'bw' ) ] );
        }

        if ( ! current_user_can( 'edit_user', $user_id ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'bw' ) ] );
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user instanceof WP_User ) {
            wp_send_json_error( [ 'message' => __( 'User not found.', 'bw' ) ] );
        }

        return $user;
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
        $reason = (string) $order->get_meta( '_bw_brevo_status_reason', true );
        $last_error = (string) $order->get_meta( '_bw_brevo_error_last', true );
        $field_received = (string) $order->get_meta( '_bw_checkout_field_received', true );
        $field_raw = (string) $order->get_meta( '_bw_checkout_field_value_raw', true );
        $post_snapshot = (string) $order->get_meta( '_bw_checkout_post_keys_snapshot', true );
        $frontend_state = (string) $order->get_meta( '_bw_subscribe_newsletter_frontend', true );

        if ( '' === $status ) {
            $status = 1 === $opt_in ? 'pending' : 'not_subscribed';
        }

        if ( '1' === $status ) {
            $status = 'subscribed';
        }

        if ( ! in_array( $status, [ 'subscribed', 'pending', 'skipped', 'error', 'not_subscribed' ], true ) ) {
            $status = 'not_subscribed';
        }

        if ( '' === $reason ) {
            if ( 'skipped' === $status && ( 'no' === $field_received || 1 !== $opt_in ) ) {
                $reason = 'no_opt_in';
            } elseif ( 'error' === $status ) {
                $reason = 'api_error';
            } elseif ( 'subscribed' === $status ) {
                $reason = 'subscribed';
            }
        }

        $list_id = isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0;
        $list_info = $this->resolve_list_display( $general, $list_id );
        $reason_label = $this->get_status_reason_label( $reason, $last_error );
        $consent_gate = $this->can_subscribe_order( $order );
        $attributes_summary = '—';
        if ( class_exists( 'BW_MailMarketing_Service' ) ) {
            $consent_source = (string) $order->get_meta( '_bw_subscribe_consent_source', true );
            $attributes_for_summary = BW_MailMarketing_Service::build_brevo_attributes_from_order( $order, $consent_source );
            $attributes_for_summary = array_merge(
                $attributes_for_summary,
                BW_MailMarketing_Service::build_name_attributes_from_order( $order, $general )
            );
            $attributes_summary = BW_MailMarketing_Service::summarize_attribute_keys( $attributes_for_summary );
        }

        $formatted_opt_in = 1 === $opt_in ? __( 'Yes', 'bw' ) : __( 'No', 'bw' );

        $meta = [
            'email'           => $this->normalize_admin_value( (string) $order->get_billing_email() ),
            'list_display'    => $list_info['label'],
            'list_needs_load' => ! empty( $list_info['needs_load'] ) ? 1 : 0,
            'opt_in'          => $formatted_opt_in,
            'consent_at'      => $this->format_admin_datetime( (string) $order->get_meta( '_bw_subscribe_consent_at', true ) ),
            'consent_source'  => $this->normalize_admin_value( (string) $order->get_meta( '_bw_subscribe_consent_source', true ) ),
            'checkout_field_received' => '' !== $field_received ? $field_received : 'no',
            'checkout_field_value_raw' => $this->normalize_admin_value( $field_raw ),
            'checkout_post_keys_snapshot' => '' !== $post_snapshot ? $post_snapshot : 'bw_subscribe_newsletter: no, bw_subscribe_newsletter_frontend: missing',
            'frontend_checkbox_state' => $this->normalize_admin_value( $frontend_state ),
            'brevo_status'    => $this->normalize_admin_value( (string) $status ),
            'status_reason'   => $this->normalize_admin_value( $reason_label ),
            'last_error'      => $this->normalize_admin_value( $last_error ),
            'last_attempt_at' => $this->format_admin_datetime( (string) $order->get_meta( '_bw_brevo_last_attempt_at', true ) ),
            'last_attempt_source' => $this->normalize_admin_value( (string) $order->get_meta( '_bw_brevo_last_attempt_source', true ) ),
            'last_checked_at' => $this->format_admin_datetime( (string) $order->get_meta( '_bw_brevo_last_checked_at', true ) ),
            'contact_id'      => $this->normalize_admin_value( (string) $order->get_meta( '_bw_brevo_contact_id', true ) ),
            'opt_in_raw'      => (string) $opt_in,
            'retry_allowed'   => ! empty( $consent_gate['allowed'] ) ? '1' : '0',
            'brevo_attributes_summary' => $this->normalize_admin_value( $attributes_summary ),
        ];

        return [
            'message'      => $message,
            'status'       => $status,
            'statusLabel'  => $this->get_status_label( $status, $reason, $last_error ),
            'statusClass'  => $this->get_status_badge_class( $status ),
            'statusIcon'   => $this->get_status_icon_class( $status ),
            'meta'         => $meta,
        ];
    }

    /**
     * Build user mail-marketing payload from user meta.
     *
     * @param WP_User $user    User object.
     * @param string  $message Optional message.
     *
     * @return array
     */
    private function build_user_status_payload( $user, $message = '' ) {
        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $status = (string) get_user_meta( $user->ID, '_bw_brevo_user_status', true );
        $reason = (string) get_user_meta( $user->ID, '_bw_brevo_user_reason', true );
        $last_error = (string) get_user_meta( $user->ID, '_bw_brevo_user_error_last', true );
        if ( '' === $status ) {
            $status = 'not_subscribed';
        }

        $list_id = isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0;
        $list_info = $this->resolve_list_display( $general, $list_id );
        $meta = [
            'email'          => sanitize_email( (string) $user->user_email ),
            'list_display'   => $list_info['label'],
            'status'         => $status,
            'status_reason'  => $this->get_status_reason_label( $reason, $last_error ),
            'last_error'     => $last_error,
            'consent_at'     => (string) get_user_meta( $user->ID, '_bw_subscribe_consent_at', true ),
            'consent_source' => (string) get_user_meta( $user->ID, '_bw_subscribe_consent_source', true ),
            'last_checked_at'=> (string) get_user_meta( $user->ID, '_bw_brevo_user_last_checked_at', true ),
            'contact_id'     => (string) get_user_meta( $user->ID, '_bw_brevo_user_contact_id', true ),
        ];

        return [
            'message'     => $message,
            'status'      => $status,
            'statusLabel' => $this->get_status_label( $status, $reason, $last_error ),
            'statusClass' => $this->get_status_badge_class( $status ),
            'meta'        => $meta,
        ];
    }

    /**
     * Fetch user status from Brevo and optionally persist it.
     *
     * @param WP_User $user      User object.
     * @param bool    $persist   Whether to persist user meta.
     *
     * @return array
     */
    private function fetch_user_brevo_payload( $user, $persist = false ) {
        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $email = sanitize_email( (string) $user->user_email );
        $list_id = isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0;

        if ( '' === $email || ! is_email( $email ) ) {
            return [
                'success' => false,
                'status'  => 'error',
                'message' => __( 'User has no valid email.', 'bw' ),
                'email'   => $email,
            ];
        }

        if ( empty( $general['api_key'] ) ) {
            return [
                'success' => false,
                'status'  => 'error',
                'message' => __( 'Brevo API key is not configured.', 'bw' ),
                'email'   => $email,
            ];
        }

        if ( $list_id <= 0 ) {
            return [
                'success' => false,
                'status'  => 'error',
                'message' => __( 'Main list ID is not configured.', 'bw' ),
                'email'   => $email,
            ];
        }

        $client = new BW_Brevo_Client( $general['api_key'], BW_Mail_Marketing_Settings::API_BASE_URL );
        $result = $client->get_contact( $email );
        $status = 'not_subscribed';
        $reason = 'contact_not_found';
        $error = '';
        $contact_id = '';
        $message = __( 'Contact not found in Brevo.', 'bw' );

        if ( empty( $result['success'] ) ) {
            if ( ! isset( $result['code'] ) || 404 !== (int) $result['code'] ) {
                $status = 'error';
                $reason = 'api_error';
                $error = isset( $result['error'] ) ? sanitize_text_field( (string) $result['error'] ) : __( 'Brevo API error.', 'bw' );
                $message = $error;
            }
        } else {
            $data = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : [];
            $contact_id = isset( $data['id'] ) ? (string) absint( $data['id'] ) : '';
            $is_blacklisted = ! empty( $data['emailBlacklisted'] );
            $list_ids = isset( $data['listIds'] ) && is_array( $data['listIds'] ) ? array_map( 'absint', $data['listIds'] ) : [];
            $in_list = in_array( $list_id, $list_ids, true );

            if ( $is_blacklisted ) {
                $status = 'skipped';
                $reason = 'contact_blocklisted';
                $message = __( 'Contact is unsubscribed/blocklisted.', 'bw' );
            } elseif ( $in_list ) {
                $status = 'subscribed';
                $reason = 'already_subscribed';
                $message = __( 'Contact found in configured list.', 'bw' );
            } else {
                $status = 'skipped';
                $reason = 'not_in_list';
                $message = __( 'Contact exists but is not in configured list.', 'bw' );
            }
        }

        if ( $persist ) {
            update_user_meta( $user->ID, '_bw_brevo_user_status', $status );
            update_user_meta( $user->ID, '_bw_brevo_user_reason', $reason );
            update_user_meta( $user->ID, '_bw_brevo_user_last_checked_at', current_time( 'mysql' ) );
            if ( '' !== $contact_id ) {
                update_user_meta( $user->ID, '_bw_brevo_user_contact_id', $contact_id );
            }
            if ( '' !== $error ) {
                update_user_meta( $user->ID, '_bw_brevo_user_error_last', $error );
            } else {
                delete_user_meta( $user->ID, '_bw_brevo_user_error_last' );
            }
        }

        $data_payload = $this->build_user_status_payload( $user, $message );
        if ( ! $persist ) {
            $data_payload['status'] = $status;
            $data_payload['statusLabel'] = $this->get_status_label( $status, $reason, $error );
            $data_payload['statusClass'] = $this->get_status_badge_class( $status );
            $data_payload['meta']['status'] = $status;
            $data_payload['meta']['status_reason'] = $this->get_status_reason_label( $reason, $error );
            $data_payload['meta']['last_error'] = $error;
            $data_payload['meta']['contact_id'] = $contact_id;
        }

        return [
            'success' => true,
            'status'  => $status,
            'message' => $message,
            'email'   => $email,
            'data'    => $data_payload,
        ];
    }

    /**
     * Map status key to user-facing label.
     *
     * @param string $status Status key.
     *
     * @return string
     */
    private function get_status_label( $status, $reason = '', $last_error = '' ) {
        $reason_label = $this->get_status_reason_label( $reason, $last_error );

        switch ( $status ) {
            case 'subscribed':
                return __( 'Subscribed', 'bw' );
            case 'pending':
                return __( 'Pending', 'bw' );
            case 'error':
                if ( '' !== $reason_label ) {
                    return sprintf( __( 'Error (%s)', 'bw' ), $reason_label );
                }
                return __( 'Error', 'bw' );
            case 'skipped':
                if ( '' !== $reason_label ) {
                    return sprintf( __( 'Skipped (%s)', 'bw' ), $reason_label );
                }
                return __( 'Skipped', 'bw' );
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
     * Map status key to dashicon class.
     *
     * @param string $status Status key.
     *
     * @return string
     */
    private function get_status_icon_class( $status ) {
        switch ( $status ) {
            case 'subscribed':
                return 'dashicons-yes-alt';
            case 'pending':
                return 'dashicons-update';
            case 'error':
                return 'dashicons-warning';
            case 'skipped':
            case 'not_subscribed':
            default:
                return 'dashicons-minus';
        }
    }

    /**
     * Format a DB datetime string using WP timezone.
     *
     * @param string $value Datetime.
     *
     * @return string
     */
    private function format_admin_datetime( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '—';
        }

        $timezone = wp_timezone();
        $date = date_create_from_format( 'Y-m-d H:i:s', $value, $timezone );
        if ( ! $date ) {
            $timestamp = strtotime( $value );
            if ( ! $timestamp ) {
                return '—';
            }

            return wp_date( 'j M Y, H:i', $timestamp, $timezone );
        }

        return wp_date( 'j M Y, H:i', $date->getTimestamp(), $timezone );
    }

    /**
     * Normalize empty values for admin display.
     *
     * @param string $value Raw value.
     *
     * @return string
     */
    private function normalize_admin_value( $value ) {
        $value = trim( (string) $value );
        return '' === $value ? '—' : $value;
    }

    /**
     * Resync one order to Brevo using idempotent upsert/DOI logic.
     *
     * @param WC_Order $order          Order object.
     * @param string   $attempt_source Attempt source.
     *
     * @return array{result:string,message:string}
     */
    private function resync_order_to_brevo( $order, $attempt_source ) {
        if ( ! $order instanceof WC_Order ) {
            return [
                'result'  => 'error',
                'message' => __( 'Invalid order.', 'bw' ),
            ];
        }

        $this->set_attempt_meta( $order, $attempt_source );

        $email = (string) $order->get_billing_email();
        $consent_gate = $this->can_subscribe_order( $order );
        if ( empty( $consent_gate['allowed'] ) ) {
            $this->mark_no_consent_skipped( $order, $email, $attempt_source );
            return [
                'result'  => 'skipped_no_consent',
                'message' => __( 'Cannot subscribe: no consent recorded for this order.', 'bw' ),
            ];
        }

        if ( '' === $email || ! is_email( $email ) ) {
            $this->set_order_status_meta( $order, 'skipped', 'invalid_email' );
            $this->log_order_action( $order, $email, 'skipped', $attempt_source, 'Skipped: invalid email.' );
            return [
                'result'  => 'skipped',
                'message' => __( 'Skipped: invalid order email.', 'bw' ),
            ];
        }

        $general = BW_Mail_Marketing_Settings::get_general_settings();
        $checkout = BW_Mail_Marketing_Settings::get_checkout_settings();
        $list_id = class_exists( 'BW_MailMarketing_Service' )
            ? BW_MailMarketing_Service::resolve_marketing_list_id( $general )
            : ( isset( $general['list_id'] ) ? absint( $general['list_id'] ) : 0 );
        if ( empty( $general['api_key'] ) || $list_id <= 0 ) {
            $this->set_order_status_meta( $order, 'skipped', 'missing_list_id' );
            $this->log_order_action( $order, $email, 'skipped', $attempt_source, 'Skipped: missing API key or list ID.' );
            return [
                'result'  => 'skipped',
                'message' => __( 'Skipped: missing Brevo API key or list ID.', 'bw' ),
            ];
        }

        if ( ! class_exists( 'BW_Brevo_Client' ) ) {
            $this->set_order_error_meta( $order, __( 'Brevo client is unavailable.', 'bw' ) );
            $this->log_order_action( $order, $email, 'error', $attempt_source, 'Brevo client unavailable.' );
            return [
                'result'  => 'error',
                'message' => __( 'Brevo client is unavailable.', 'bw' ),
            ];
        }

        $client = new BW_Brevo_Client( $general['api_key'], BW_Mail_Marketing_Settings::API_BASE_URL );
        $contact = $client->get_contact( $email );
        if ( ! empty( $contact['success'] ) && ! empty( $contact['data'] ) && is_array( $contact['data'] ) && ! empty( $contact['data']['emailBlacklisted'] ) ) {
            $this->set_order_status_meta( $order, 'skipped', 'contact_blocklisted' );
            $this->log_order_action( $order, $email, 'skipped', $attempt_source, 'Skipped: contact blocklisted/unsubscribed.' );
            return [
                'result'  => 'skipped',
                'message' => __( 'Skipped: contact is blocklisted/unsubscribed.', 'bw' ),
            ];
        }

        $attributes = $this->build_brevo_attributes_for_order( $order, $general );
        $mode = isset( $checkout['channel_optin_mode'] ) ? (string) $checkout['channel_optin_mode'] : 'inherit';
        if ( ! in_array( $mode, [ 'single_opt_in', 'double_opt_in' ], true ) ) {
            $mode = ( isset( $general['default_optin_mode'] ) && 'double_opt_in' === $general['default_optin_mode'] ) ? 'double_opt_in' : 'single_opt_in';
        }

        if ( 'double_opt_in' === $mode ) {
            $attribute_warning = '';
            $template_id = isset( $general['double_optin_template_id'] ) ? absint( $general['double_optin_template_id'] ) : 0;
            $redirect_url = isset( $general['double_optin_redirect_url'] ) ? (string) $general['double_optin_redirect_url'] : '';
            if ( $template_id <= 0 || '' === $redirect_url ) {
                $this->set_order_error_meta( $order, __( 'DOI requires template id and redirect URL.', 'bw' ) );
                $this->log_order_action( $order, $email, 'error', $attempt_source, 'Missing DOI template/redirect.' );
                return [
                    'result'  => 'error',
                    'message' => __( 'DOI requires template id and redirect URL.', 'bw' ),
                ];
            }

            $sender = [];
            if ( ! empty( $general['sender_email'] ) ) {
                $sender['email'] = sanitize_email( (string) $general['sender_email'] );
            }
            if ( ! empty( $general['sender_name'] ) ) {
                $sender['name'] = sanitize_text_field( (string) $general['sender_name'] );
            }

            $result = $client->send_double_opt_in( $email, $template_id, $redirect_url, [ $list_id ], $attributes, $sender );
            if ( empty( $result['success'] ) && $this->is_brevo_unknown_attribute_error( $result ) ) {
                $minimal_attributes = $this->strip_marketing_attributes( $attributes );
                $attribute_warning = __( 'Brevo rejected one or more custom attributes. Retrying without custom attributes.', 'bw' );
                $this->log_order_action( $order, $email, 'warning', $attempt_source, 'Brevo rejected custom attributes for DOI. Retrying with minimal attributes.' );
                $result = $client->send_double_opt_in( $email, $template_id, $redirect_url, [ $list_id ], $minimal_attributes, $sender );
                if ( empty( $result['success'] ) && $this->is_brevo_unknown_attribute_error( $result ) ) {
                    $this->log_order_action( $order, $email, 'warning', $attempt_source, 'Brevo still rejected attributes for DOI. Retrying with empty attributes.' );
                    $result = $client->send_double_opt_in( $email, $template_id, $redirect_url, [ $list_id ], [], $sender );
                }
            }

            if ( empty( $result['success'] ) ) {
                if ( $this->is_brevo_unknown_attribute_error( $result ) ) {
                    $warning = isset( $result['error'] ) ? sanitize_text_field( (string) $result['error'] ) : __( 'Brevo rejected custom attributes.', 'bw' );
                    update_post_meta( $order->get_id(), '_bw_brevo_error_last', $warning );
                    $this->log_order_action( $order, $email, 'warning', $attempt_source, 'Brevo custom attribute warning (DOI): ' . $warning );
                    return [
                        'result'  => 'processed',
                        'message' => __( 'Brevo warning: custom attributes were rejected. Contact status unchanged.', 'bw' ),
                    ];
                }
                $error = isset( $result['error'] ) ? (string) $result['error'] : __( 'Brevo DOI failed.', 'bw' );
                $this->set_order_error_meta( $order, $error );
                $this->log_order_action( $order, $email, 'error', $attempt_source, $error );
                return [
                    'result'  => 'error',
                    'message' => $error,
                ];
            }

            update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'pending' );
            update_post_meta( $order->get_id(), '_bw_brevo_status_reason', 'double_opt_in_sent' );
            if ( '' !== $attribute_warning ) {
                update_post_meta( $order->get_id(), '_bw_brevo_error_last', $attribute_warning );
            } else {
                delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
            }
            update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
            $this->log_order_action( $order, $email, 'pending', $attempt_source, 'DOI request sent.' );
            return [
                'result'  => 'processed',
                'message' => __( 'DOI request sent successfully.', 'bw' ),
            ];
        }

        $attribute_warning = '';
        $result = $client->upsert_contact( $email, $attributes, [ $list_id ] );
        if ( empty( $result['success'] ) && $this->is_brevo_unknown_attribute_error( $result ) ) {
            $minimal_attributes = $this->strip_marketing_attributes( $attributes );
            $attribute_warning = __( 'Brevo rejected one or more custom attributes. Retrying without custom attributes.', 'bw' );
            $this->log_order_action( $order, $email, 'warning', $attempt_source, 'Brevo rejected custom attributes. Retrying with minimal attributes.' );
            $result = $client->upsert_contact( $email, $minimal_attributes, [ $list_id ] );
            if ( empty( $result['success'] ) && $this->is_brevo_unknown_attribute_error( $result ) ) {
                $this->log_order_action( $order, $email, 'warning', $attempt_source, 'Brevo still rejected attributes. Retrying with empty attributes.' );
                $result = $client->upsert_contact( $email, [], [ $list_id ] );
            }
        }

        if ( empty( $result['success'] ) ) {
            if ( $this->is_brevo_unknown_attribute_error( $result ) ) {
                $warning = isset( $result['error'] ) ? sanitize_text_field( (string) $result['error'] ) : __( 'Brevo rejected custom attributes.', 'bw' );
                update_post_meta( $order->get_id(), '_bw_brevo_error_last', $warning );
                $this->log_order_action( $order, $email, 'warning', $attempt_source, 'Brevo custom attribute warning (upsert): ' . $warning );
                return [
                    'result'  => 'processed',
                    'message' => __( 'Brevo warning: custom attributes were rejected. Contact status unchanged.', 'bw' ),
                ];
            }
            $error = isset( $result['error'] ) ? (string) $result['error'] : __( 'Brevo subscribe failed.', 'bw' );
            $this->set_order_error_meta( $order, $error );
            $this->log_order_action( $order, $email, 'error', $attempt_source, $error );
            return [
                'result'  => 'error',
                'message' => $error,
            ];
        }

        update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'subscribed' );
        update_post_meta( $order->get_id(), '_bw_brevo_status_reason', 'subscribed' );
        if ( '' !== $attribute_warning ) {
            update_post_meta( $order->get_id(), '_bw_brevo_error_last', $attribute_warning );
        } else {
            delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
        }
        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
        $this->log_order_action( $order, $email, 'subscribed', $attempt_source, 'Order synced to Brevo.' );

        return [
            'result'  => 'processed',
            'message' => __( 'Retry subscribe succeeded.', 'bw' ),
        ];
    }

    /**
     * Build Brevo attributes from order and consent metadata.
     *
     * @param WC_Order $order   Order object.
     * @param array    $general General settings.
     *
     * @return array
     */
    private function build_brevo_attributes_for_order( $order, $general ) {
        if ( ! class_exists( 'BW_MailMarketing_Service' ) ) {
            return [];
        }

        $consent_source = (string) $order->get_meta( '_bw_subscribe_consent_source', true );
        $attributes = BW_MailMarketing_Service::build_brevo_attributes_from_order( $order, $consent_source );
        $name_attributes = BW_MailMarketing_Service::build_name_attributes_from_order( $order, $general );

        return array_merge( $attributes, $name_attributes );
    }

    /**
     * Remove custom marketing attributes when Brevo schema rejects them.
     *
     * @param array $attributes Full attributes.
     *
     * @return array
     */
    private function strip_marketing_attributes( $attributes ) {
        if ( ! is_array( $attributes ) ) {
            return [];
        }

        unset(
            $attributes['SOURCE'],
            $attributes['CONSENT_SOURCE'],
            $attributes['CONSENT_AT'],
            $attributes['CONSENT_STATUS'],
            $attributes['BW_ORIGIN_SYSTEM'],
            $attributes['BW_ENV'],
            $attributes['LAST_ORDER_ID'],
            $attributes['LAST_ORDER_AT'],
            $attributes['CUSTOMER_STATUS']
        );
        return $attributes;
    }

    /**
     * Detect unknown-attribute errors returned by Brevo.
     *
     * @param array $result Brevo result.
     *
     * @return bool
     */
    private function is_brevo_unknown_attribute_error( $result ) {
        if ( empty( $result['error'] ) ) {
            return false;
        }

        $error = strtolower( (string) $result['error'] );
        if ( false !== strpos( $error, 'attribute' ) && false !== strpos( $error, 'exist' ) ) {
            return true;
        }

        if ( false !== strpos( $error, 'unknown' ) && false !== strpos( $error, 'attribute' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Centralized consent gate for server-side manual subscribe paths.
     *
     * @param WC_Order $order Order.
     *
     * @return array{allowed:bool}
     */
    private function can_subscribe_order( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return [ 'allowed' => false ];
        }

        $opt_in = (int) $order->get_meta( '_bw_subscribe_newsletter', true );
        $consent_at = trim( (string) $order->get_meta( '_bw_subscribe_consent_at', true ) );
        $consent_source = trim( (string) $order->get_meta( '_bw_subscribe_consent_source', true ) );

        if ( 1 !== $opt_in ) {
            return [ 'allowed' => false ];
        }

        if ( '' === $consent_at || '' === $consent_source ) {
            return [ 'allowed' => false ];
        }

        return [ 'allowed' => true ];
    }

    /**
     * Determine if user-side write actions are allowed based on consent evidence.
     *
     * @param WP_User $user User object.
     *
     * @return bool
     */
    private function can_sync_user_status( $user ) {
        if ( ! $user instanceof WP_User ) {
            return false;
        }

        $consent_at = trim( (string) get_user_meta( $user->ID, '_bw_subscribe_consent_at', true ) );
        $consent_source = trim( (string) get_user_meta( $user->ID, '_bw_subscribe_consent_source', true ) );

        return '' !== $consent_at && '' !== $consent_source;
    }

    /**
     * Persist and log no-consent skip status.
     *
     * @param WC_Order $order          Order.
     * @param string   $email          Email.
     * @param string   $attempt_source Attempt source.
     */
    private function mark_no_consent_skipped( $order, $email, $attempt_source ) {
        update_post_meta( $order->get_id(), '_bw_brevo_subscribed', 'skipped' );
        update_post_meta( $order->get_id(), '_bw_brevo_status_reason', 'No consent recorded' );
        delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );

        $this->log_order_action(
            $order,
            $email,
            'skipped',
            $attempt_source,
            'BW_BREVO_SKIP_NO_CONSENT: Cannot subscribe: no consent recorded for this order.'
        );
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
        update_post_meta( $order->get_id(), '_bw_brevo_status_reason', 'api_error' );
        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
    }

    /**
     * Persist non-error status transitions with reason.
     *
     * @param WC_Order $order  Order object.
     * @param string   $status Status.
     * @param string   $reason Reason key.
     */
    private function set_order_status_meta( $order, $status, $reason ) {
        update_post_meta( $order->get_id(), '_bw_brevo_subscribed', sanitize_key( (string) $status ) );
        update_post_meta( $order->get_id(), '_bw_brevo_status_reason', sanitize_key( (string) $reason ) );
        delete_post_meta( $order->get_id(), '_bw_brevo_error_last' );
        update_post_meta( $order->get_id(), '_bw_brevo_last_checked_at', current_time( 'mysql' ) );
    }

    /**
     * Persist last attempt diagnostics.
     *
     * @param WC_Order $order          Order.
     * @param string   $attempt_source Attempt source.
     */
    private function set_attempt_meta( $order, $attempt_source ) {
        update_post_meta( $order->get_id(), '_bw_brevo_last_attempt_at', current_time( 'mysql' ) );
        update_post_meta( $order->get_id(), '_bw_brevo_last_attempt_source', sanitize_key( (string) $attempt_source ) );
    }

    /**
     * Resolve list display as "Name (#ID)" with graceful fallback.
     *
     * @param array $general General settings.
     * @param int   $list_id List ID.
     *
     * @return string
     */
    private function resolve_list_display( $general, $list_id ) {
        if ( $list_id <= 0 ) {
            return [
                'label'      => __( 'Not configured (#0)', 'bw' ),
                'needs_load' => 0,
            ];
        }

        $api_key = isset( $general['api_key'] ) ? (string) $general['api_key'] : '';
        if ( '' === $api_key || ! class_exists( 'BW_Brevo_Client' ) ) {
            return [
                'label'      => sprintf( __( 'List #%d', 'bw' ), $list_id ),
                'needs_load' => 1,
            ];
        }

        $lists_map = $this->get_cached_brevo_lists_map( $api_key );
        if ( isset( $lists_map[ $list_id ] ) && '' !== $lists_map[ $list_id ] ) {
            return [
                'label'      => sprintf( '%s (#%d)', $lists_map[ $list_id ], $list_id ),
                'needs_load' => 0,
            ];
        }

        return [
            'label'      => sprintf( __( 'List #%d', 'bw' ), $list_id ),
            'needs_load' => 1,
        ];
    }

    /**
     * Get Brevo list-id => list-name map from transient or API.
     *
     * @param string $api_key Brevo API key.
     *
     * @return array<int,string>
     */
    private function get_cached_brevo_lists_map( $api_key ) {
        if ( class_exists( 'BW_Brevo_Lists_Service' ) ) {
            return BW_Brevo_Lists_Service::get_cached_lists_map( $api_key );
        }

        return [];
    }

    /**
     * Human-readable reason label for diagnostics.
     *
     * @param string $reason     Reason key.
     * @param string $last_error Last error.
     *
     * @return string
     */
    private function get_status_reason_label( $reason, $last_error = '' ) {
        $reason = sanitize_key( (string) $reason );

        $map = [
            'no-consent-recorded' => __( 'No consent recorded', 'bw' ),
            'no_consent_recorded' => __( 'No consent recorded', 'bw' ),
            'no_opt_in'            => __( 'No opt-in', 'bw' ),
            'missing_list_id'      => __( 'Missing list ID', 'bw' ),
            'invalid_email'        => __( 'Invalid email', 'bw' ),
            'already_subscribed'   => __( 'Already subscribed', 'bw' ),
            'contact_blocklisted'  => __( 'Unsubscribed or blocklisted', 'bw' ),
            'contact_not_found'    => __( 'Contact not found', 'bw' ),
            'not_in_list'          => __( 'Contact not in configured list', 'bw' ),
            'double_opt_in_sent'   => __( 'Double opt-in sent', 'bw' ),
            'subscribed'           => __( 'Subscribed', 'bw' ),
            'api_error'            => __( 'API error', 'bw' ),
            'missing_settings'     => __( 'Missing API/list settings', 'bw' ),
        ];

        if ( isset( $map[ $reason ] ) ) {
            if ( 'api_error' === $reason && '' !== $last_error ) {
                return sprintf( __( 'API error: %s', 'bw' ), sanitize_text_field( $last_error ) );
            }
            return $map[ $reason ];
        }

        if ( '' !== $last_error ) {
            return sanitize_text_field( $last_error );
        }

        return '';
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

        if ( 'warning' === $result ) {
            $logger->warning( $msg, $context );
            return;
        }

        $logger->info( $msg, $context );
    }

    /**
     * Write user-level newsletter log entry.
     *
     * @param WP_User $user   User object.
     * @param string  $email  Email.
     * @param string  $result Result key.
     * @param string  $action Action key.
     * @param string  $msg    Message.
     */
    private function log_user_action( $user, $email, $result, $action, $msg ) {
        if ( ! function_exists( 'wc_get_logger' ) || ! $user instanceof WP_User ) {
            return;
        }

        $context = [
            'source'  => 'bw-brevo',
            'user_id' => $user->ID,
            'email'   => sanitize_email( (string) $email ),
            'result'  => sanitize_key( (string) $result ),
            'action'  => sanitize_key( (string) $action ),
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
        if ( class_exists( 'BW_Brevo_Lists_Service' ) ) {
            return BW_Brevo_Lists_Service::get_lists( $api_key );
        }

        return [
            'success' => false,
            'message' => __( 'Brevo lists service unavailable.', 'bw' ),
            'lists'   => [],
        ];
    }

    /**
     * Persist lists map into transient cache.
     *
     * @param string $api_key API key.
     * @param array  $lists   List rows with id/name.
     */
    private function cache_brevo_lists_map( $api_key, $lists ) {
        if ( class_exists( 'BW_Brevo_Lists_Service' ) ) {
            BW_Brevo_Lists_Service::cache_lists_map( $api_key, $lists );
        }
    }
}
