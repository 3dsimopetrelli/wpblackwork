<?php
/**
 * Review moderation service.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Review_Moderation_Service' ) ) {
    class BW_Review_Moderation_Service {
        /**
         * @var BW_Reviews_Repository
         */
        private $repository;

        /**
         * @var BW_Review_Confirmation_Service
         */
        private $confirmation_service;

        /**
         * @var BW_Review_Brevo_Sync_Service
         */
        private $brevo_service;

        /**
         * Constructor.
         *
         * @param BW_Reviews_Repository|null       $repository Repository.
         * @param BW_Review_Confirmation_Service|null $confirmation_service Confirmation service.
         * @param BW_Review_Brevo_Sync_Service|null $brevo_service Brevo service.
         */
        public function __construct( $repository = null, $confirmation_service = null, $brevo_service = null ) {
            $this->repository           = $repository instanceof BW_Reviews_Repository ? $repository : new BW_Reviews_Repository();
            $this->confirmation_service = $confirmation_service instanceof BW_Review_Confirmation_Service ? $confirmation_service : new BW_Review_Confirmation_Service( $this->repository );
            $this->brevo_service        = $brevo_service instanceof BW_Review_Brevo_Sync_Service ? $brevo_service : new BW_Review_Brevo_Sync_Service( $this->repository );
        }

        /**
         * Approve a review.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function approve( $review_id ) {
            $review = $this->repository->get_review( $review_id );
            if ( ! is_array( $review ) ) {
                return false;
            }

            $updated = $this->repository->update_review(
                $review_id,
                [
                    'status'      => 'approved',
                    'approved_at' => current_time( 'mysql' ),
                    'updated_at'  => current_time( 'mysql' ),
                ]
            );

            if ( $updated ) {
                $fresh = $this->repository->get_review( $review_id );
                $this->brevo_service->sync_for_event( $fresh, 'approval' );
            }

            return $updated;
        }

        /**
         * Reject a review.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function reject( $review_id ) {
            return $this->repository->update_review(
                $review_id,
                [
                    'status'      => 'rejected',
                    'rejected_at' => current_time( 'mysql' ),
                    'updated_at'  => current_time( 'mysql' ),
                ]
            );
        }

        /**
         * Set featured state.
         *
         * @param int  $review_id Review ID.
         * @param bool $featured  Featured state.
         *
         * @return bool
         */
        public function set_featured( $review_id, $featured ) {
            return $this->repository->update_review(
                $review_id,
                [
                    'featured'   => $featured ? 1 : 0,
                    'updated_at' => current_time( 'mysql' ),
                ]
            );
        }

        /**
         * Move review to trash.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function trash( $review_id ) {
            $review = $this->repository->get_review( $review_id );
            if ( ! is_array( $review ) || 'trash' === (string) $review['status'] ) {
                return false;
            }

            return $this->repository->update_review(
                $review_id,
                [
                    'status_before_trash' => (string) $review['status'],
                    'status'              => 'trash',
                    'updated_at'          => current_time( 'mysql' ),
                ]
            );
        }

        /**
         * Restore review from trash.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function restore( $review_id ) {
            $review = $this->repository->get_review( $review_id );
            if ( ! is_array( $review ) || 'trash' !== (string) $review['status'] ) {
                return false;
            }

            $previous = isset( $review['status_before_trash'] ) ? sanitize_key( (string) $review['status_before_trash'] ) : '';
            if ( ! in_array( $previous, BW_Reviews_Settings::get_statuses(), true ) || 'trash' === $previous ) {
                $previous = 'rejected';
            }

            return $this->repository->update_review(
                $review_id,
                [
                    'status'              => $previous,
                    'status_before_trash' => null,
                    'updated_at'          => current_time( 'mysql' ),
                ]
            );
        }

        /**
         * Permanently delete a review.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function delete_permanently( $review_id ) {
            return $this->repository->delete_review( $review_id );
        }

        /**
         * Resend confirmation email for a pending confirmation review.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function resend_confirmation( $review_id ) {
            return $this->confirmation_service->resend_confirmation( $review_id );
        }
    }
}
