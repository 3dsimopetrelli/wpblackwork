<?php
/**
 * Shared Brevo list loading and caching helpers.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Brevo_Lists_Service' ) ) {
    class BW_Brevo_Lists_Service {
        /**
         * Retrieve lists from cache or Brevo API.
         *
         * @param string $api_key Brevo API key.
         *
         * @return array<string,mixed>
         */
        public static function get_lists( $api_key ) {
            $api_key = (string) $api_key;

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

            $client = new BW_Brevo_Client( $api_key, self::get_api_base_url() );
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

            self::cache_lists_map( $api_key, $lists );

            return [
                'success' => true,
                'message' => '',
                'lists'   => $lists,
            ];
        }

        /**
         * Get cached Brevo list map.
         *
         * @param string $api_key Brevo API key.
         *
         * @return array<int,string>
         */
        public static function get_cached_lists_map( $api_key ) {
            $api_key = (string) $api_key;
            if ( '' === $api_key ) {
                return [];
            }

            $cache_key = 'bw_brevo_lists_map_' . md5( $api_key );
            $cached    = get_transient( $cache_key );

            return is_array( $cached ) ? $cached : [];
        }

        /**
         * Persist list map into transient cache.
         *
         * @param string $api_key Brevo API key.
         * @param array  $lists   Brevo lists.
         */
        public static function cache_lists_map( $api_key, $lists ) {
            $api_key = (string) $api_key;
            if ( '' === $api_key || ! is_array( $lists ) || empty( $lists ) ) {
                return;
            }

            $map = [];
            foreach ( $lists as $list ) {
                $id   = isset( $list['id'] ) ? absint( $list['id'] ) : 0;
                $name = isset( $list['name'] ) ? sanitize_text_field( (string) $list['name'] ) : '';

                if ( $id > 0 && '' !== $name ) {
                    $map[ $id ] = $name;
                }
            }

            if ( empty( $map ) ) {
                return;
            }

            $cache_key = 'bw_brevo_lists_map_' . md5( $api_key );
            set_transient( $cache_key, $map, HOUR_IN_SECONDS );
        }

        /**
         * Resolve a list label from cache.
         *
         * @param int    $list_id List ID.
         * @param string $api_key Brevo API key.
         *
         * @return array<string,mixed>
         */
        public static function resolve_list_label( $list_id, $api_key ) {
            $list_id = absint( $list_id );
            if ( $list_id <= 0 ) {
                return [
                    'label'      => __( 'Not configured (#0)', 'bw' ),
                    'needs_load' => 0,
                ];
            }

            $lists_map = self::get_cached_lists_map( $api_key );
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
         * Resolve the configured Brevo API base URL.
         *
         * @return string
         */
        private static function get_api_base_url() {
            if ( class_exists( 'BW_Mail_Marketing_Settings' ) ) {
                return BW_Mail_Marketing_Settings::API_BASE_URL;
            }

            return 'https://api.brevo.com/v3';
        }
    }
}
