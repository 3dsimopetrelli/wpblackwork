<?php
/**
 * Review submission service.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Review_Submission_Service' ) ) {
    class BW_Review_Submission_Service {
        /**
         * @var BW_Reviews_Repository
         */
        private $repository;

        /**
         * @var BW_Review_Verified_Purchase_Service
         */
        private $verified_purchase_service;

        /**
         * @var BW_Review_Confirmation_Service
         */
        private $confirmation_service;

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
         * @param BW_Reviews_Repository|null             $repository Repository.
         * @param BW_Review_Verified_Purchase_Service|null $verified_purchase_service Verified purchase service.
         * @param BW_Review_Confirmation_Service|null    $confirmation_service Confirmation service.
         * @param BW_Review_Email_Service|null           $email_service Email service.
         * @param BW_Review_Brevo_Sync_Service|null      $brevo_service Brevo service.
         */
        public function __construct( $repository = null, $verified_purchase_service = null, $confirmation_service = null, $email_service = null, $brevo_service = null ) {
            $this->repository                = $repository instanceof BW_Reviews_Repository ? $repository : new BW_Reviews_Repository();
            $this->verified_purchase_service = $verified_purchase_service instanceof BW_Review_Verified_Purchase_Service ? $verified_purchase_service : new BW_Review_Verified_Purchase_Service();
            $this->confirmation_service      = $confirmation_service instanceof BW_Review_Confirmation_Service ? $confirmation_service : new BW_Review_Confirmation_Service( $this->repository );
            $this->email_service             = $email_service instanceof BW_Review_Email_Service ? $email_service : new BW_Review_Email_Service();
            $this->brevo_service             = $brevo_service instanceof BW_Review_Brevo_Sync_Service ? $brevo_service : new BW_Review_Brevo_Sync_Service( $this->repository );
        }

        /**
         * Submit a review.
         *
         * @param array<string,mixed> $payload Raw payload.
         *
         * @return array<string,mixed>
         */
        public function submit_review( $payload ) {
            $submission_settings = BW_Reviews_Settings::get_submission_settings();
            $moderation_settings = BW_Reviews_Settings::get_moderation_settings();
            $user_id             = get_current_user_id();
            $is_logged_in        = $user_id > 0;

            if ( empty( BW_Reviews_Settings::get_general_settings()['enabled'] ) ) {
                return $this->error_response( 'disabled', __( 'Reviews are currently disabled.', 'bw' ) );
            }

            if ( ! empty( $submission_settings['logged_in_only'] ) && ! $is_logged_in ) {
                return $this->error_response( 'login_required', __( 'You must be logged in to submit a review.', 'bw' ) );
            }

            if ( empty( $submission_settings['allow_guests'] ) && ! $is_logged_in ) {
                return $this->error_response( 'guest_disabled', __( 'Guest reviews are currently disabled.', 'bw' ) );
            }

            $product_id = isset( $payload['product_id'] ) ? absint( $payload['product_id'] ) : 0;
            $rating     = isset( $payload['rating'] ) ? absint( $payload['rating'] ) : 0;
            $content    = isset( $payload['content'] ) ? wp_kses_post( wp_unslash( (string) $payload['content'] ) ) : '';
            $source     = isset( $payload['source'] ) ? sanitize_key( (string) $payload['source'] ) : 'product_page';

            if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
                return $this->error_response( 'invalid_product', __( 'Invalid product selected.', 'bw' ) );
            }

            if ( $rating < 1 || $rating > 5 ) {
                return $this->error_response( 'invalid_rating', __( 'Please select a valid rating.', 'bw' ) );
            }

            $content = trim( wp_strip_all_tags( $content ) );
            if ( '' === $content ) {
                return $this->error_response( 'invalid_content', __( 'Review text is required.', 'bw' ) );
            }

            $identity = $this->resolve_identity( $payload, $user_id );
            if ( empty( $identity['email'] ) || empty( $identity['display_name'] ) ) {
                return $this->error_response( 'invalid_identity', __( 'Reviewer details are required.', 'bw' ) );
            }

            $existing_review = $this->repository->find_by_product_and_email_hash( $product_id, $identity['email_hash'] );
            if ( ! $existing_review && $user_id > 0 ) {
                $existing_review = $this->repository->find_by_product_and_user_id( $product_id, $user_id );
            }

            if ( $existing_review ) {
                if ( $user_id > 0 && empty( $existing_review['user_id'] ) ) {
                    $this->repository->update_review(
                        absint( $existing_review['id'] ),
                        [
                            'user_id'     => $user_id,
                            'updated_at'  => current_time( 'mysql' ),
                        ]
                    );
                }

                if ( $user_id > 0 ) {
                    return $this->error_response( 'duplicate_review', __( 'You already reviewed this product. You can edit your existing review instead.', 'bw' ) );
                }

                return $this->error_response( 'duplicate_review', __( 'A review for this product already exists for this email address.', 'bw' ) );
            }

            $verification = [
                'eligible' => true,
                'order_id' => 0,
            ];

            if ( ! empty( $submission_settings['verified_buyers_only'] ) ) {
                $verification = $this->verified_purchase_service->check_eligibility( $product_id, $identity['email'], $user_id );
                if ( empty( $verification['eligible'] ) ) {
                    return $this->error_response( 'not_verified_buyer', __( 'Only verified buyers can submit a review for this product.', 'bw' ) );
                }
            }

            $status = 'approved';
            if ( ! empty( $submission_settings['require_email_confirmation'] ) ) {
                $status = 'pending_confirmation';
            } elseif ( ! empty( $moderation_settings['require_moderation'] ) ) {
                $status = 'pending_moderation';
            }

            $now = current_time( 'mysql' );
            $review_id = $this->repository->insert_review(
                [
                    'product_id'            => $product_id,
                    'order_id'              => absint( $verification['order_id'] ),
                    'user_id'               => $user_id > 0 ? $user_id : null,
                    'reviewer_first_name'   => $identity['first_name'],
                    'reviewer_last_name'    => $identity['last_name'],
                    'reviewer_display_name' => $identity['display_name'],
                    'reviewer_email'        => $identity['email'],
                    'reviewer_email_hash'   => $identity['email_hash'],
                    'rating'                => $rating,
                    'content'               => $content,
                    'status'                => $status,
                    'featured'              => 0,
                    'verified_purchase'     => ! empty( $verification['eligible'] ) ? 1 : 0,
                    'source'                => $source ? $source : 'product_page',
                    'created_at'            => $now,
                    'updated_at'            => $now,
                    'approved_at'           => 'approved' === $status ? $now : null,
                ]
            );

            if ( $review_id <= 0 ) {
                return $this->error_response( 'save_failed', __( 'Unable to save the review right now.', 'bw' ) );
            }

            $review = $this->repository->get_review( $review_id );
            if ( ! is_array( $review ) ) {
                return $this->error_response( 'save_failed', __( 'Unable to load the saved review.', 'bw' ) );
            }

            if ( 'pending_confirmation' === $status ) {
                $raw_token = $this->confirmation_service->prepare_confirmation( $review_id );
                $this->email_service->send_confirmation_email( $review, $raw_token );
                $this->brevo_service->sync_for_event( $review, 'submission' );

                return [
                    'success'              => true,
                    'resultCode'           => 'submitted',
                    'statusAfterSubmit'    => 'pending_confirmation',
                    'message'              => __( 'We’ve sent a confirmation link to your email. Please confirm to publish your review.', 'bw' ),
                    'requiresConfirmation' => true,
                    'reviewId'             => $review_id,
                ];
            }

            if ( 'pending_moderation' === $status ) {
                $this->brevo_service->sync_for_event( $review, 'submission' );

                return [
                    'success'              => true,
                    'resultCode'           => 'submitted',
                    'statusAfterSubmit'    => 'pending_moderation',
                    'message'              => __( 'Your review has been received and is awaiting approval.', 'bw' ),
                    'requiresConfirmation' => false,
                    'reviewId'             => $review_id,
                ];
            }

            $this->brevo_service->sync_for_event( $review, 'submission' );
            $this->brevo_service->sync_for_event( $review, 'approval' );

            return [
                'success'              => true,
                'resultCode'           => 'submitted',
                'statusAfterSubmit'    => 'approved',
                'message'              => __( 'Your review is live. Thank you for sharing your perspective.', 'bw' ),
                'requiresConfirmation' => false,
                'reviewId'             => $review_id,
            ];
        }

        /**
         * Update a review for the current owner.
         *
         * @param int                 $review_id Review ID.
         * @param array<string,mixed> $payload   Payload.
         * @param int                 $user_id   User ID.
         *
         * @return array<string,mixed>
         */
        public function update_review_for_owner( $review_id, $payload, $user_id ) {
            $review_id = absint( $review_id );
            $user_id   = absint( $user_id );
            $review    = $this->repository->get_review( $review_id );
            $moderation = BW_Reviews_Settings::get_moderation_settings();

            if ( ! is_array( $review ) || $user_id <= 0 || absint( $review['user_id'] ) !== $user_id ) {
                return $this->error_response( 'forbidden', __( 'You cannot edit this review.', 'bw' ) );
            }

            if ( empty( $moderation['allow_review_editing'] ) || empty( $moderation['editing_logged_in_owners_only'] ) ) {
                return $this->error_response( 'editing_disabled', __( 'Review editing is currently disabled.', 'bw' ) );
            }

            if ( 'trash' === (string) $review['status'] ) {
                return $this->error_response( 'forbidden', __( 'You cannot edit this review.', 'bw' ) );
            }

            $rating     = isset( $payload['rating'] ) ? absint( $payload['rating'] ) : absint( $review['rating'] );
            $content    = isset( $payload['content'] ) ? trim( wp_strip_all_tags( wp_unslash( (string) $payload['content'] ) ) ) : trim( (string) $review['content'] );

            if ( $rating < 1 || $rating > 5 ) {
                return $this->error_response( 'invalid_rating', __( 'Please select a valid rating.', 'bw' ) );
            }

            if ( '' === $content ) {
                return $this->error_response( 'invalid_content', __( 'Review text is required.', 'bw' ) );
            }

            $status = (string) $review['status'];
            if ( 'approved' === $status && ! empty( $moderation['edit_requires_approval_again'] ) ) {
                $status = 'pending_moderation';
            }

            $updated = $this->repository->update_review(
                $review_id,
                [
                    'rating'     => $rating,
                    'content'    => $content,
                    'status'     => $status,
                    'edited_at'  => current_time( 'mysql' ),
                    'edit_count' => absint( $review['edit_count'] ) + 1,
                    'updated_at' => current_time( 'mysql' ),
                ]
            );

            if ( ! $updated ) {
                return $this->error_response( 'save_failed', __( 'Unable to update the review right now.', 'bw' ) );
            }

            return [
                'success'         => true,
                'resultCode'      => 'updated',
                'statusAfterEdit' => $status,
                'message'         => 'pending_moderation' === $status
                    ? __( 'Your changes were saved and are awaiting approval.', 'bw' )
                    : __( 'Your review has been updated.', 'bw' ),
            ];
        }

        /**
         * Normalize reviewer identity.
         *
         * @param array<string,mixed> $payload Payload.
         * @param int                 $user_id User ID.
         *
         * @return array<string,string>
         */
        public function resolve_identity( $payload, $user_id = 0 ) {
            $user_id = absint( $user_id );
            $first_name = '';
            $last_name  = '';
            $email      = '';
            $display_name = '';

            if ( $user_id > 0 ) {
                $user = get_userdata( $user_id );
                if ( $user instanceof WP_User ) {
                    $first_name = trim( (string) $user->first_name );
                    $last_name  = trim( (string) $user->last_name );
                    $email      = $this->normalize_email( $user->user_email );
                    if ( '' === trim( $first_name . $last_name ) ) {
                        $display_name = sanitize_text_field( (string) $user->display_name );
                    }
                }
            } else {
                $first_name = isset( $payload['first_name'] ) ? sanitize_text_field( wp_unslash( (string) $payload['first_name'] ) ) : '';
                $last_name  = isset( $payload['last_name'] ) ? sanitize_text_field( wp_unslash( (string) $payload['last_name'] ) ) : '';
                $email      = isset( $payload['email'] ) ? $this->normalize_email( $payload['email'] ) : '';
            }

            if ( empty( $display_name ) ) {
                $display_name = $this->build_display_name( $first_name, $last_name );
            }

            return [
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'email'        => $email,
                'email_hash'   => $email ? strtolower( hash( 'sha256', $email ) ) : '',
            ];
        }

        /**
         * Normalize email according to approved strategy.
         *
         * @param mixed $value Raw email.
         *
         * @return string
         */
        public function normalize_email( $value ) {
            $value = wp_unslash( (string) $value );
            $value = trim( $value );
            $value = preg_replace( '/\s+/', '', $value );
            $value = strtolower( $value );
            $value = sanitize_email( $value );

            return is_email( $value ) ? $value : '';
        }

        /**
         * Build display name.
         *
         * @param string $first_name First name.
         * @param string $last_name  Last name.
         *
         * @return string
         */
        private function build_display_name( $first_name, $last_name ) {
            $display_name = trim( preg_replace( '/\s+/', ' ', trim( $first_name . ' ' . $last_name ) ) );

            return '' !== $display_name ? $display_name : __( 'Anonymous', 'bw' );
        }

        /**
         * Build a standard error response.
         *
         * @param string $code    Error code.
         * @param string $message Error message.
         *
         * @return array<string,mixed>
         */
        private function error_response( $code, $message ) {
            return [
                'success'    => false,
                'resultCode' => sanitize_key( $code ),
                'message'    => $message,
            ];
        }
    }
}
