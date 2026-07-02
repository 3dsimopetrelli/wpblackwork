<?php
/**
 * Mail-marketing (Brevo) settings store — option keys, defaults, sanitization.
 *
 * Extracted verbatim from class-bw-checkout-subscribe-admin.php (Phase 3c,
 * BW-TASK-20260629). The guarded BW_Mail_Marketing_Settings class definition is
 * unchanged.
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
