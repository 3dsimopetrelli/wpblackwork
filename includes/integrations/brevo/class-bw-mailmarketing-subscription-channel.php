<?php
/**
 * Frontend subscription channel for fixed-design Elementor newsletter widget.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_MailMarketing_Subscription_Channel' ) ) {
    class BW_MailMarketing_Subscription_Channel {
        /**
         * @var BW_MailMarketing_Subscription_Channel|null
         */
        private static $instance = null;

        /**
         * Bootstrap singleton.
         *
         * @return BW_MailMarketing_Subscription_Channel
         */
        public static function init() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor.
         */
        private function __construct() {
            add_action( 'init', [ $this, 'register_assets' ] );
            add_action( 'wp_ajax_bw_mail_marketing_subscribe', [ $this, 'handle_submit' ] );
            add_action( 'wp_ajax_nopriv_bw_mail_marketing_subscribe', [ $this, 'handle_submit' ] );
        }

        /**
         * Register widget assets.
         *
         * @return void
         */
        public function register_assets() {
            $css_file = BW_MEW_PATH . 'assets/css/bw-newsletter-subscription.css';
            $js_file  = BW_MEW_PATH . 'assets/js/bw-newsletter-subscription.js';

            wp_register_style(
                'bw-newsletter-subscription-style',
                BW_MEW_URL . 'assets/css/bw-newsletter-subscription.css',
                [],
                file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0'
            );

            wp_register_script(
                'bw-newsletter-subscription-script',
                BW_MEW_URL . 'assets/js/bw-newsletter-subscription.js',
                [],
                file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
                true
            );

            wp_localize_script(
                'bw-newsletter-subscription-script',
                'bwMailMarketingSubscription',
                [
                    'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                    'networkErrorText' => __( 'Unable to connect right now. Please try again later.', 'bw' ),
                    'workingText'      => __( 'Submitting...', 'bw' ),
                ]
            );
        }

        /**
         * Handle frontend subscribe submit.
         *
         * @return void
         */
        public function handle_submit() {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! wp_verify_nonce( $nonce, 'bw_mail_marketing_subscription_submit' ) ) {
                wp_send_json_error(
                    [
                        'message' => __( 'Security check failed. Please refresh and try again.', 'bw' ),
                    ],
                    403
                );
            }

            if ( ! class_exists( 'BW_Mail_Marketing_Settings' ) || ! class_exists( 'BW_Brevo_Client' ) ) {
                wp_send_json_error(
                    [
                        'message' => __( 'Mail Marketing configuration is unavailable.', 'bw' ),
                    ],
                    500
                );
            }

            $general_settings = BW_Mail_Marketing_Settings::get_general_settings();
            $channel_settings = BW_Mail_Marketing_Settings::get_subscription_settings();

            if ( empty( $channel_settings['enabled'] ) ) {
                wp_send_json_error(
                    [
                        'message' => __( 'Newsletter widget is currently disabled.', 'bw' ),
                    ],
                    400
                );
            }

            $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
            $full_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
            $consent = ! empty( $_POST['privacy'] ) ? 1 : 0;

            if ( '' === $email || ! is_email( $email ) ) {
                wp_send_json_error(
                    [
                        'message' => __( 'Please enter a valid email address.', 'bw' ),
                    ],
                    400
                );
            }

            if ( 1 !== $consent ) {
                $message = ! empty( $channel_settings['consent_required_message'] )
                    ? $channel_settings['consent_required_message']
                    : __( 'Please confirm the privacy consent to subscribe.', 'bw' );
                wp_send_json_error(
                    [
                        'message' => $message,
                    ],
                    400
                );
            }

            $api_key = isset( $general_settings['api_key'] ) ? sanitize_text_field( (string) $general_settings['api_key'] ) : '';
            if ( '' === $api_key ) {
                $this->log_event( 'error', 'Missing Brevo API key for subscription widget.', $email, 'elementor_widget', 'missing_settings' );
                wp_send_json_error(
                    [
                        'message' => $this->get_error_message( $channel_settings ),
                    ],
                    500
                );
            }

            $list_id = class_exists( 'BW_MailMarketing_Service' )
                ? BW_MailMarketing_Service::resolve_channel_list_id( $general_settings, $channel_settings )
                : 0;
            if ( $list_id <= 0 ) {
                $this->log_event( 'error', 'Missing Brevo list ID for subscription widget.', $email, $channel_settings['source_key'], 'missing_list_id' );
                wp_send_json_error(
                    [
                        'message' => $this->get_error_message( $channel_settings ),
                    ],
                    500
                );
            }

            $client = new BW_Brevo_Client( $api_key, BW_Mail_Marketing_Settings::API_BASE_URL );
            if ( class_exists( 'BW_MailMarketing_Service' ) && BW_MailMarketing_Service::is_contact_blocklisted( $client, $email, $general_settings ) ) {
                $this->log_event( 'info', 'Skipping widget subscribe: contact is unsubscribed/blocklisted.', $email, $channel_settings['source_key'], 'skipped' );
                wp_send_json_error(
                    [
                        'message' => __( 'This contact cannot be resubscribed automatically.', 'bw' ),
                    ],
                    400
                );
            }

            $consent_at = current_time( 'mysql' );
            $attributes = class_exists( 'BW_MailMarketing_Service' )
                ? BW_MailMarketing_Service::build_brevo_attributes_from_subscription( $channel_settings['source_key'], $consent_at )
                : [];

            if ( class_exists( 'BW_MailMarketing_Service' ) ) {
                $attributes = array_merge(
                    $attributes,
                    BW_MailMarketing_Service::build_name_attributes_from_full_name( $full_name, $general_settings )
                );
            }

            $mode = class_exists( 'BW_MailMarketing_Service' )
                ? BW_MailMarketing_Service::resolve_channel_optin_mode( $general_settings, $channel_settings )
                : 'single_opt_in';

            $result = [];
            $attribute_warning = '';

            if ( 'double_opt_in' === $mode ) {
                $template_id = isset( $general_settings['double_optin_template_id'] ) ? absint( $general_settings['double_optin_template_id'] ) : 0;
                $redirect_url = isset( $general_settings['double_optin_redirect_url'] ) ? esc_url_raw( (string) $general_settings['double_optin_redirect_url'] ) : '';

                if ( $template_id <= 0 || '' === $redirect_url ) {
                    $this->log_event( 'error', 'Missing DOI template or redirect URL for subscription widget.', $email, $channel_settings['source_key'], 'error' );
                    wp_send_json_error(
                        [
                            'message' => $this->get_error_message( $channel_settings ),
                        ],
                        500
                    );
                }

                $sender = [];
                if ( ! empty( $general_settings['sender_email'] ) ) {
                    $sender['email'] = sanitize_email( (string) $general_settings['sender_email'] );
                }
                if ( ! empty( $general_settings['sender_name'] ) ) {
                    $sender['name'] = sanitize_text_field( (string) $general_settings['sender_name'] );
                }

                $result = $client->send_double_opt_in( $email, $template_id, $redirect_url, [ $list_id ], $attributes, $sender );
                if ( empty( $result['success'] ) && class_exists( 'BW_MailMarketing_Service' ) && BW_MailMarketing_Service::is_unknown_attribute_error( $result ) ) {
                    $attribute_warning = isset( $result['error'] ) ? sanitize_text_field( (string) $result['error'] ) : __( 'Brevo rejected custom attributes.', 'bw' );
                    $this->log_event( 'warning', 'BW_BREVO_ATTR_INVALID: Retrying DOI widget submit without marketing attributes.', $email, $channel_settings['source_key'], 'warning' );
                    $result = $client->send_double_opt_in(
                        $email,
                        $template_id,
                        $redirect_url,
                        [ $list_id ],
                        BW_MailMarketing_Service::strip_marketing_attributes( $attributes ),
                        $sender
                    );
                    if ( empty( $result['success'] ) && BW_MailMarketing_Service::is_unknown_attribute_error( $result ) ) {
                        $result = $client->send_double_opt_in( $email, $template_id, $redirect_url, [ $list_id ], [], $sender );
                    }
                }
            } else {
                $result = $client->upsert_contact( $email, $attributes, [ $list_id ] );
                if ( empty( $result['success'] ) && class_exists( 'BW_MailMarketing_Service' ) && BW_MailMarketing_Service::is_unknown_attribute_error( $result ) ) {
                    $attribute_warning = isset( $result['error'] ) ? sanitize_text_field( (string) $result['error'] ) : __( 'Brevo rejected custom attributes.', 'bw' );
                    $this->log_event( 'warning', 'BW_BREVO_ATTR_INVALID: Retrying widget submit without marketing attributes.', $email, $channel_settings['source_key'], 'warning' );
                    $result = $client->upsert_contact( $email, BW_MailMarketing_Service::strip_marketing_attributes( $attributes ), [ $list_id ] );
                    if ( empty( $result['success'] ) && BW_MailMarketing_Service::is_unknown_attribute_error( $result ) ) {
                        $result = $client->upsert_contact( $email, [], [ $list_id ] );
                    }
                }
            }

            if ( empty( $result['success'] ) ) {
                $error_message = $this->extract_error_message( $result, $this->get_error_message( $channel_settings ) );
                if ( '' !== $attribute_warning ) {
                    $error_message = $attribute_warning;
                }
                $this->log_event( 'error', $error_message, $email, $channel_settings['source_key'], 'error' );
                wp_send_json_error(
                    [
                        'message' => $error_message,
                    ],
                    500
                );
            }

            if ( '' !== $attribute_warning ) {
                $this->log_event( 'warning', 'Brevo accepted the widget subscribe after dropping unsupported attributes: ' . $attribute_warning, $email, $channel_settings['source_key'], 'warning' );
            }

            $success_message = ! empty( $channel_settings['success_message'] )
                ? $channel_settings['success_message']
                : __( 'Thanks for subscribing! Please check your inbox.', 'bw' );

            $this->log_event(
                'info',
                'double_opt_in' === $mode ? 'Widget DOI request sent.' : 'Widget contact subscribed.',
                $email,
                $channel_settings['source_key'],
                'double_opt_in' === $mode ? 'pending' : 'subscribed'
            );

            wp_send_json_success(
                [
                    'message' => $success_message,
                    'mode'    => $mode,
                ]
            );
        }

        /**
         * Default error message from channel settings.
         *
         * @param array $channel_settings Channel settings.
         *
         * @return string
         */
        private function get_error_message( $channel_settings ) {
            return ! empty( $channel_settings['error_message'] )
                ? sanitize_text_field( (string) $channel_settings['error_message'] )
                : __( 'Unable to subscribe right now. Please try again later.', 'bw' );
        }

        /**
         * Extract API error message safely.
         *
         * @param array  $result  Brevo result.
         * @param string $fallback Fallback message.
         *
         * @return string
         */
        private function extract_error_message( $result, $fallback ) {
            if ( ! empty( $result['error'] ) ) {
                return sanitize_text_field( (string) $result['error'] );
            }

            return sanitize_text_field( (string) $fallback );
        }

        /**
         * Write frontend subscription logs to Woo logger when available.
         *
         * @param string $level  Logger level.
         * @param string $message Message.
         * @param string $email  Contact email.
         * @param string $channel_source Consent source.
         * @param string $result Result key.
         *
         * @return void
         */
        private function log_event( $level, $message, $email, $channel_source, $result ) {
            if ( ! function_exists( 'wc_get_logger' ) ) {
                return;
            }

            $logger = wc_get_logger();
            $context = [
                'source'  => 'bw-brevo',
                'email'   => sanitize_email( (string) $email ),
                'context' => 'subscription_widget',
                'channel' => sanitize_key( (string) $channel_source ),
                'result'  => sanitize_key( (string) $result ),
            ];

            if ( 'error' === $level ) {
                $logger->error( $message, $context );
                return;
            }

            if ( 'warning' === $level ) {
                $logger->warning( $message, $context );
                return;
            }

            $logger->info( $message, $context );
        }
    }
}
