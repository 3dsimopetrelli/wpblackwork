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
         * Default marketing list ID fallback.
         */
        const DEFAULT_MARKETING_LIST_ID = 10;

        /**
         * Default unconfirmed list ID placeholder.
         */
        const DEFAULT_UNCONFIRMED_LIST_ID = 11;

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
         * Resolve marketing list ID with fallback.
         *
         * @param array $general_settings General settings.
         *
         * @return int
         */
        public static function resolve_marketing_list_id( $general_settings = [] ) {
            $configured = isset( $general_settings['list_id'] ) ? absint( $general_settings['list_id'] ) : 0;
            if ( $configured > 0 ) {
                return $configured;
            }

            return self::DEFAULT_MARKETING_LIST_ID;
        }

        /**
         * Resolve unconfirmed list ID placeholder (future DOI sources).
         *
         * @return int
         */
        public static function resolve_unconfirmed_list_id() {
            return self::DEFAULT_UNCONFIRMED_LIST_ID;
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

