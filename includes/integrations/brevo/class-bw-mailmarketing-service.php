<?php
/**
 * Shared Mail Marketing service helpers for Brevo data model.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_MailMarketing_Service' ) ) {
    class BW_MailMarketing_Service {
        /**
         * Build canonical Brevo attributes from order context.
         *
         * @param WC_Order $order          Woo order.
         * @param string   $context_source Fallback consent source.
         *
         * @return array
         */
        public static function build_brevo_attributes_from_order( $order, $context_source = 'checkout' ) {
            if ( ! $order instanceof WC_Order ) {
                return [];
            }

            $source = (string) $order->get_meta( '_bw_subscribe_consent_source', true );
            if ( '' === $source ) {
                $source = sanitize_key( (string) $context_source );
            }
            if ( '' === $source ) {
                $source = 'checkout';
            }
            $source = sanitize_key( $source );

            $consent_at = (string) $order->get_meta( '_bw_subscribe_consent_at', true );
            $opt_in = (int) $order->get_meta( '_bw_subscribe_newsletter', true );

            $consent_status = 'pending';
            if ( 1 === $opt_in && '' !== trim( $consent_at ) && '' !== trim( $source ) ) {
                $consent_status = 'granted';
            }

            $attributes = [
                'SOURCE'           => $source,
                'CONSENT_SOURCE'   => $source,
                'CONSENT_STATUS'   => $consent_status,
                'BW_ORIGIN_SYSTEM' => 'wp',
                'BW_ENV'           => self::detect_environment(),
            ];

            if ( '' !== trim( $consent_at ) ) {
                $attributes['CONSENT_AT'] = self::normalize_to_iso8601( $consent_at );
            }

            $attributes['LAST_ORDER_ID'] = (string) absint( $order->get_id() );

            $order_date = $order->get_date_paid();
            if ( ! $order_date ) {
                $order_date = $order->get_date_created();
            }
            if ( $order_date instanceof WC_DateTime ) {
                $attributes['LAST_ORDER_AT'] = gmdate( 'c', (int) $order_date->getTimestamp() );
            }

            $attributes['CUSTOMER_STATUS'] = 'customer';

            return $attributes;
        }

        /**
         * Build optional name attributes based on settings.
         *
         * @param WC_Order $order            Woo order.
         * @param array    $general_settings General settings.
         *
         * @return array
         */
        public static function build_name_attributes_from_order( $order, $general_settings ) {
            if ( ! $order instanceof WC_Order ) {
                return [];
            }

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
         * Build canonical Brevo attributes from site subscription context.
         *
         * @param string $source     Consent source key.
         * @param string $consent_at Consent datetime.
         * @param array  $context    Optional context payload.
         *
         * @return array
         */
        public static function build_brevo_attributes_from_subscription( $source, $consent_at, $context = [] ) {
            $source = sanitize_key( (string) $source );
            if ( '' === $source ) {
                $source = 'elementor_widget';
            }

            $attributes = [
                'SOURCE'           => $source,
                'CONSENT_SOURCE'   => $source,
                'CONSENT_STATUS'   => 'granted',
                'BW_ORIGIN_SYSTEM' => 'wp',
                'BW_ENV'           => self::detect_environment(),
            ];

            $normalized_consent_at = self::normalize_to_iso8601( $consent_at );
            if ( '' !== $normalized_consent_at ) {
                $attributes['CONSENT_AT'] = $normalized_consent_at;
            }

            if ( ! empty( $context['customer_status'] ) ) {
                $attributes['CUSTOMER_STATUS'] = sanitize_key( (string) $context['customer_status'] );
            }

            if ( ! empty( $context['last_order_id'] ) ) {
                $attributes['LAST_ORDER_ID'] = (string) absint( $context['last_order_id'] );
            }

            if ( ! empty( $context['last_order_at'] ) ) {
                $normalized_last_order_at = self::normalize_to_iso8601( $context['last_order_at'] );
                if ( '' !== $normalized_last_order_at ) {
                    $attributes['LAST_ORDER_AT'] = $normalized_last_order_at;
                }
            }

            return $attributes;
        }

        /**
         * Build optional name attributes from a full-name field.
         *
         * @param string $full_name         Submitted full name.
         * @param array  $general_settings  General settings.
         *
         * @return array
         */
        public static function build_name_attributes_from_full_name( $full_name, $general_settings ) {
            $full_name = trim( preg_replace( '/\s+/', ' ', (string) $full_name ) );
            if ( '' === $full_name ) {
                return [];
            }

            $parts = explode( ' ', $full_name );
            $first_name = array_shift( $parts );
            $last_name = implode( ' ', $parts );
            $attributes = [];

            if ( ! empty( $general_settings['sync_first_name'] ) && '' !== $first_name ) {
                $attributes['FIRSTNAME'] = sanitize_text_field( $first_name );
            }

            if ( ! empty( $general_settings['sync_last_name'] ) && '' !== $last_name ) {
                $attributes['LASTNAME'] = sanitize_text_field( $last_name );
            }

            return $attributes;
        }

        /**
         * Summarize attribute keys for admin diagnostics.
         *
         * @param array $attributes Attributes payload.
         *
         * @return string
         */
        public static function summarize_attribute_keys( $attributes ) {
            if ( ! is_array( $attributes ) || empty( $attributes ) ) {
                return '—';
            }

            $keys = array_keys( $attributes );
            $keys = array_map( 'sanitize_key', $keys );
            $keys = array_filter( $keys );

            if ( empty( $keys ) ) {
                return '—';
            }

            return implode( ', ', $keys );
        }

        /**
         * Resolve marketing list ID from General settings.
         * Returns 0 if not configured — callers must treat 0 as a configuration error.
         *
         * @param array $general_settings General settings.
         *
         * @return int
         */
        public static function resolve_marketing_list_id( $general_settings = [] ) {
            return isset( $general_settings['list_id'] ) ? absint( $general_settings['list_id'] ) : 0;
        }

        /**
         * Resolve list ID for a channel with inherit/custom behavior.
         *
         * @param array $general_settings General settings.
         * @param array $channel_settings Channel settings.
         *
         * @return int
         */
        public static function resolve_channel_list_id( $general_settings = [], $channel_settings = [] ) {
            $mode = isset( $channel_settings['list_mode'] ) ? sanitize_key( (string) $channel_settings['list_mode'] ) : 'inherit';
            if ( 'custom' === $mode ) {
                $custom_id = isset( $channel_settings['list_id'] ) ? absint( $channel_settings['list_id'] ) : 0;
                if ( $custom_id > 0 ) {
                    return $custom_id;
                }
            }

            return self::resolve_marketing_list_id( $general_settings );
        }

        /**
         * Resolve opt-in mode from channel override + general settings.
         *
         * @param array $general_settings General settings.
         * @param array $channel_settings Channel settings.
         *
         * @return string
         */
        public static function resolve_channel_optin_mode( $general_settings = [], $channel_settings = [] ) {
            $channel_mode = isset( $channel_settings['channel_optin_mode'] ) ? sanitize_key( (string) $channel_settings['channel_optin_mode'] ) : 'inherit';
            if ( in_array( $channel_mode, [ 'single_opt_in', 'double_opt_in' ], true ) ) {
                return $channel_mode;
            }

            return ( isset( $general_settings['default_optin_mode'] ) && 'double_opt_in' === $general_settings['default_optin_mode'] )
                ? 'double_opt_in'
                : 'single_opt_in';
        }

        /**
         * Strip custom marketing attributes when Brevo schema is incomplete.
         *
         * @param array $attributes Full payload.
         *
         * @return array
         */
        public static function strip_marketing_attributes( $attributes ) {
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
         * Required subscription audit keys that must not be lost during fallback.
         *
         * @return string[]
         */
        public static function get_required_subscription_attribute_keys() {
            return [
                'SOURCE',
                'CONSENT_SOURCE',
                'CONSENT_STATUS',
                'CONSENT_AT',
            ];
        }

        /**
         * Determine whether a fallback payload drops required subscription metadata.
         *
         * Only keys present in the original payload are enforced, so callers can safely use
         * this with partial payloads without inventing requirements that were never set.
         *
         * @param array $original_attributes Original attributes payload.
         * @param array $fallback_attributes Fallback attributes payload.
         *
         * @return bool
         */
        public static function fallback_drops_required_subscription_attributes( $original_attributes, $fallback_attributes ) {
            if ( ! is_array( $original_attributes ) || ! is_array( $fallback_attributes ) ) {
                return false;
            }

            foreach ( self::get_required_subscription_attribute_keys() as $required_key ) {
                if ( array_key_exists( $required_key, $original_attributes ) && ! array_key_exists( $required_key, $fallback_attributes ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Detect Brevo unknown-attribute errors.
         *
         * @param array $result Brevo response payload.
         *
         * @return bool
         */
        public static function is_unknown_attribute_error( $result ) {
            if ( empty( $result['error'] ) ) {
                return false;
            }

            $error = strtolower( (string) $result['error'] );
            if ( false !== strpos( $error, 'attribute' ) && false !== strpos( $error, 'exist' ) ) {
                return true;
            }

            return false !== strpos( $error, 'unknown' ) && false !== strpos( $error, 'attribute' );
        }

        /**
         * Determine if a contact is blocked/unsubscribed according to Brevo.
         *
         * @param BW_Brevo_Client $client           Brevo client.
         * @param string          $email            Contact email.
         * @param array           $general_settings General settings.
         *
         * @return bool
         */
        public static function is_contact_blocklisted( $client, $email, $general_settings = [] ) {
            if ( ! $client instanceof BW_Brevo_Client ) {
                return false;
            }

            if ( empty( $general_settings['resubscribe_policy'] ) || 'no_auto_resubscribe' !== $general_settings['resubscribe_policy'] ) {
                return false;
            }

            $result = $client->get_contact( $email );
            if ( ! empty( $result['success'] ) && ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
                return ! empty( $result['data']['emailBlacklisted'] );
            }

            return false;
        }

        /**
         * Normalize stored datetime into ISO8601 UTC.
         *
         * @param string $value Datetime value.
         *
         * @return string
         */
        private static function normalize_to_iso8601( $value ) {
            $value = trim( (string) $value );
            if ( '' === $value ) {
                return '';
            }

            $timezone = wp_timezone();
            $date = date_create_from_format( 'Y-m-d H:i:s', $value, $timezone );
            if ( ! $date ) {
                $timestamp = strtotime( $value );
                if ( ! $timestamp ) {
                    return '';
                }
                return gmdate( 'c', (int) $timestamp );
            }

            return gmdate( 'c', (int) $date->getTimestamp() );
        }

        /**
         * Detect environment string for BW_ENV attribute.
         *
         * @return string
         */
        private static function detect_environment() {
            if ( function_exists( 'wp_get_environment_type' ) ) {
                $env = wp_get_environment_type();
                if ( is_string( $env ) && '' !== trim( $env ) ) {
                    return sanitize_key( $env );
                }
            }

            if ( defined( 'WP_ENV' ) && is_string( WP_ENV ) && '' !== trim( WP_ENV ) ) {
                return sanitize_key( WP_ENV );
            }

            return 'production';
        }
    }
}
