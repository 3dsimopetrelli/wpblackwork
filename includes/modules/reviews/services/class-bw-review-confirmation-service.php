<?php
/**
 * Review confirmation flow service.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Review_Confirmation_Service' ) ) {
    class BW_Review_Confirmation_Service {
        /**
         * @var BW_Reviews_Repository
         */
        private $repository;

        /**
         * @var BW_Review_Email_Service
         */
        private $email_service;

        /**
         * @var BW_Review_Brevo_Sync_Service
         */
        private $brevo_service;

        /**
         * Constructor.
         *
         * @param BW_Reviews_Repository|null      $repository    Repository instance.
         * @param BW_Review_Email_Service|null    $email_service Email service.
         * @param BW_Review_Brevo_Sync_Service|null $brevo_service Brevo service.
         */
        public function __construct( $repository = null, $email_service = null, $brevo_service = null ) {
            $this->repository    = $repository instanceof BW_Reviews_Repository ? $repository : new BW_Reviews_Repository();
            $this->email_service = $email_service instanceof BW_Review_Email_Service ? $email_service : new BW_Review_Email_Service();
            $this->brevo_service = $brevo_service instanceof BW_Review_Brevo_Sync_Service ? $brevo_service : new BW_Review_Brevo_Sync_Service( $this->repository );
        }

        /**
         * Generate and persist a confirmation token.
         *
         * @param int $review_id Review ID.
         *
         * @return string
         */
        public function prepare_confirmation( $review_id ) {
            $review_id = absint( $review_id );
            if ( $review_id <= 0 ) {
                return '';
            }

            $raw_token = wp_generate_password( 48, false, false );
            $hash      = $this->hash_token( $raw_token );
            $expires   = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * 2 );

            $this->repository->update_review(
                $review_id,
                [
                    'confirmation_token_hash'       => $hash,
                    'confirmation_token_expires_at' => get_date_from_gmt( $expires, 'Y-m-d H:i:s' ),
                    'updated_at'                    => current_time( 'mysql' ),
                ]
            );

            return $raw_token;
        }

        /**
         * Send confirmation for a review.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function resend_confirmation( $review_id ) {
            $review = $this->repository->get_review( $review_id );
            if ( ! is_array( $review ) || 'pending_confirmation' !== (string) $review['status'] ) {
                return false;
            }

            $raw_token = $this->prepare_confirmation( $review_id );
            if ( '' === $raw_token ) {
                return false;
            }

            return $this->email_service->send_confirmation_email( $review, $raw_token );
        }

        /**
         * Confirm a review from raw token.
         *
         * @param int    $review_id  Review ID.
         * @param string $raw_token  Raw token.
         *
         * @return array<string,mixed>
         */
        public function confirm_review( $review_id, $raw_token ) {
            $review_id = absint( $review_id );
            $raw_token = sanitize_text_field( (string) $raw_token );
            $hash      = $this->hash_token( $raw_token );
            $review    = $this->repository->get_review( $review_id );

            if ( ! is_array( $review ) || 'pending_confirmation' !== (string) $review['status'] ) {
                return [
                    'success' => false,
                    'code'    => 'invalid',
                    'review'  => $review,
                ];
            }

            if ( empty( $review['confirmation_token_hash'] ) || ! hash_equals( (string) $review['confirmation_token_hash'], $hash ) ) {
                return [
                    'success' => false,
                    'code'    => 'invalid',
                    'review'  => $review,
                ];
            }

            $expires_at = isset( $review['confirmation_token_expires_at'] ) ? strtotime( (string) $review['confirmation_token_expires_at'] ) : false;
            if ( ! $expires_at || $expires_at < time() ) {
                return [
                    'success' => false,
                    'code'    => 'expired',
                    'review'  => $review,
                ];
            }

            $moderation   = BW_Reviews_Settings::get_moderation_settings();
            $next_status  = ! empty( $moderation['require_moderation'] ) ? 'pending_moderation' : 'approved';
            $update       = [
                'status'                        => $next_status,
                'confirmation_token_hash'       => null,
                'confirmation_token_expires_at' => null,
                'confirmed_at'                  => current_time( 'mysql' ),
                'updated_at'                    => current_time( 'mysql' ),
            ];

            if ( 'approved' === $next_status ) {
                $update['approved_at'] = current_time( 'mysql' );
            }

            $this->repository->update_review( $review_id, $update );
            $confirmed_review = $this->repository->get_review( $review_id );

            $this->brevo_service->sync_for_event( $confirmed_review, 'confirmation' );
            if ( 'approved' === $next_status ) {
                $this->brevo_service->sync_for_event( $confirmed_review, 'approval' );
            }

            return [
                'success' => true,
                'code'    => 'confirmed',
                'review'  => $confirmed_review,
            ];
        }

        /**
         * Hash a raw token.
         *
         * @param string $raw_token Raw token.
         *
         * @return string
         */
        public function hash_token( $raw_token ) {
            return strtolower( hash( 'sha256', (string) $raw_token ) );
        }
    }
}
