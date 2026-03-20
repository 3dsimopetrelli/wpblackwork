<?php
/**
 * Review-specific Brevo sync service.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Review_Brevo_Sync_Service' ) ) {
    class BW_Review_Brevo_Sync_Service {
        /**
         * @var BW_Reviews_Repository
         */
        private $repository;

        /**
         * Constructor.
         *
         * @param BW_Reviews_Repository|null $repository Repository instance.
         */
        public function __construct( $repository = null ) {
            $this->repository = $repository instanceof BW_Reviews_Repository ? $repository : new BW_Reviews_Repository();
        }

        /**
         * Sync a review for an event.
         *
         * @param array<string,mixed> $review Review row.
         * @param string              $event Event key.
         *
         * @return bool
         */
        public function sync_for_event( $review, $event ) {
            $settings = BW_Reviews_Settings::get_brevo_settings();
            if ( empty( $settings['enabled'] ) || ! is_array( $review ) ) {
                return false;
            }

            $event = sanitize_key( (string) $event );
            $event_map = [
                'submission'   => [ 'flag' => 'sync_on_submission', 'list_key' => 'review_list_id' ],
                'confirmation' => [ 'flag' => 'sync_on_confirmation', 'list_key' => 'confirmed_review_list_id' ],
                'approval'     => [ 'flag' => 'sync_on_approval', 'list_key' => 'confirmed_review_list_id' ],
            ];

            if ( ! isset( $event_map[ $event ] ) ) {
                return false;
            }

            $config = $event_map[ $event ];
            if ( empty( $settings[ $config['flag'] ] ) ) {
                return false;
            }

            $list_id = isset( $settings[ $config['list_key'] ] ) ? absint( $settings[ $config['list_key'] ] ) : 0;
            if ( $list_id <= 0 ) {
                return false;
            }

            $global = BW_Reviews_Settings::get_global_brevo_settings();
            $api_key = isset( $global['api_key'] ) ? (string) $global['api_key'] : '';

            if ( '' === $api_key || ! class_exists( 'BW_Brevo_Client' ) ) {
                return false;
            }

            $email = isset( $review['reviewer_email'] ) ? sanitize_email( (string) $review['reviewer_email'] ) : '';
            if ( '' === $email ) {
                return false;
            }

            $attributes = [];
            if ( ! empty( $review['reviewer_first_name'] ) ) {
                $attributes['FIRSTNAME'] = sanitize_text_field( (string) $review['reviewer_first_name'] );
            }
            if ( ! empty( $review['reviewer_last_name'] ) ) {
                $attributes['LASTNAME'] = sanitize_text_field( (string) $review['reviewer_last_name'] );
            }

            $api_base = ! empty( $global['api_base'] ) ? (string) $global['api_base'] : 'https://api.brevo.com/v3';
            $client   = new BW_Brevo_Client( $api_key, $api_base );
            $result = $client->upsert_contact( $email, $attributes, [ $list_id ] );

            $this->repository->update_review(
                absint( $review['id'] ),
                [
                    'brevo_sync_state'     => ! empty( $result['success'] ) ? 'synced' : 'error',
                    'brevo_last_synced_at' => current_time( 'mysql' ),
                    'updated_at'           => current_time( 'mysql' ),
                ]
            );

            return ! empty( $result['success'] );
        }
    }
}
