<?php
/**
 * Reviews runtime hooks.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Reviews_Runtime' ) ) {
    class BW_Reviews_Runtime {
        /**
         * Initialize runtime hooks.
         */
        public static function init() {
            $instance = new self();
            return $instance;
        }

        /**
         * @var BW_Review_Submission_Service
         */
        private $submission_service;

        /**
         * @var BW_Review_Confirmation_Service
         */
        private $confirmation_service;

        /**
         * Constructor.
         */
        private function __construct() {
            $this->submission_service   = new BW_Review_Submission_Service();
            $this->confirmation_service = new BW_Review_Confirmation_Service();

            add_action( 'wp_ajax_bw_reviews_load_reviews', [ $this, 'handle_load_reviews' ] );
            add_action( 'wp_ajax_nopriv_bw_reviews_load_reviews', [ $this, 'handle_load_reviews' ] );
            add_action( 'wp_ajax_bw_reviews_submit', [ $this, 'handle_submit' ] );
            add_action( 'wp_ajax_nopriv_bw_reviews_submit', [ $this, 'handle_submit' ] );
            add_action( 'wp_ajax_bw_reviews_get_edit_review', [ $this, 'handle_get_edit_review' ] );
            add_action( 'wp_ajax_bw_reviews_update_review', [ $this, 'handle_update_review' ] );
            add_action( 'template_redirect', [ $this, 'handle_confirmation_request' ] );

            add_filter( 'woocommerce_enable_reviews', [ $this, 'maybe_disable_native_reviews' ] );
            add_filter( 'comments_open', [ $this, 'maybe_disable_product_comments' ], 10, 2 );
            add_filter( 'woocommerce_product_tabs', [ $this, 'remove_reviews_tab' ], 99 );
        }

        /**
         * Disable native Woo reviews when the module is enabled.
         *
         * @param bool $enabled Current enabled state.
         *
         * @return bool
         */
        public function maybe_disable_native_reviews( $enabled ) {
            if ( BW_Reviews_Settings::is_enabled() ) {
                return false;
            }

            return $enabled;
        }

        /**
         * Disable native product comments when reviews module is enabled.
         *
         * @param bool $open    Whether comments are open.
         * @param int  $post_id Post ID.
         *
         * @return bool
         */
        public function maybe_disable_product_comments( $open, $post_id ) {
            if ( BW_Reviews_Settings::is_enabled() && 'product' === get_post_type( $post_id ) ) {
                return false;
            }

            return $open;
        }

        /**
         * Remove Woo reviews tab.
         *
         * @param array<string,mixed> $tabs Product tabs.
         *
         * @return array<string,mixed>
         */
        public function remove_reviews_tab( $tabs ) {
            if ( BW_Reviews_Settings::is_enabled() && isset( $tabs['reviews'] ) ) {
                unset( $tabs['reviews'] );
            }

            return $tabs;
        }

        /**
         * Load server-rendered review cards.
         */
        public function handle_load_reviews() {
            check_ajax_referer( 'bw_reviews_submit', 'nonce' );

            if ( ! BW_Reviews_Settings::is_enabled() ) {
                wp_send_json_error(
                    [
                        'resultCode' => 'disabled',
                        'message'    => __( 'Reviews are currently disabled.', 'bw' ),
                    ],
                    400
                );
            }

            $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
            $sort       = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'featured';
            $offset     = isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
            $limit      = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : max( 1, absint( BW_Reviews_Settings::get_display_settings()['load_more_count'] ) );

            if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
                wp_send_json_error(
                    [
                        'resultCode' => 'invalid_product',
                        'message'    => __( 'Invalid product selected.', 'bw' ),
                    ],
                    400
                );
            }

            $repository = new BW_Reviews_Repository();
            $renderer   = new BW_Reviews_Widget_Renderer( $repository );
            $display    = BW_Reviews_Settings::get_display_settings();
            $moderation = BW_Reviews_Settings::get_moderation_settings();
            $owned      = is_user_logged_in() ? $repository->find_by_product_and_user_id( $product_id, get_current_user_id() ) : null;
            $reviews    = $repository->get_product_reviews( $product_id, $sort, $offset, max( 1, $limit ) );
            $total      = $repository->count_product_reviews( $product_id );
            $shown      = $offset + count( $reviews );

            wp_send_json_success(
                [
                    'html'          => $renderer->render_reviews_html(
                        $reviews,
                        [
                            'show_dates'          => ! empty( $display['show_dates'] ),
                            'show_verified_badge' => ! empty( $display['show_verified_badge'] ),
                            'can_edit_own'        => is_user_logged_in() && ! empty( $moderation['allow_review_editing'] ) && ! empty( $moderation['editing_logged_in_owners_only'] ),
                            'owned_review_id'     => is_array( $owned ) ? absint( $owned['id'] ) : 0,
                        ]
                    ),
                    'hasMore'       => $shown < $total,
                    'nextOffset'    => $shown,
                    'shownCount'    => $shown,
                    'totalApproved' => $total,
                ]
            );
        }

        /**
         * Handle public review submission.
         */
        public function handle_submit() {
            check_ajax_referer( 'bw_reviews_submit', 'nonce' );

            $payload = [
                'product_id' => isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0,
                'rating'     => isset( $_POST['rating'] ) ? absint( wp_unslash( $_POST['rating'] ) ) : 0,
                'content'    => isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '',
                'first_name' => isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '',
                'last_name'  => isset( $_POST['last_name'] ) ? wp_unslash( $_POST['last_name'] ) : '',
                'email'      => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
                'source'     => 'product_page',
            ];

            $result = $this->submission_service->submit_review( $payload );

            if ( ! empty( $result['success'] ) ) {
                wp_send_json_success( $result );
            }

            wp_send_json_error( $result, 400 );
        }

        /**
         * Get review payload for owner edit.
         */
        public function handle_get_edit_review() {
            if ( ! is_user_logged_in() ) {
                wp_send_json_error(
                    [
                        'resultCode' => 'forbidden',
                        'message'    => __( 'You must be logged in to edit a review.', 'bw' ),
                    ],
                    403
                );
            }

            check_ajax_referer( 'bw_reviews_submit', 'nonce' );

            $review_id   = isset( $_POST['review_id'] ) ? absint( wp_unslash( $_POST['review_id'] ) ) : 0;
            $repository  = new BW_Reviews_Repository();
            $review      = $repository->get_review( $review_id );
            $current_uid = get_current_user_id();
            $moderation  = BW_Reviews_Settings::get_moderation_settings();

            if (
                ! is_array( $review )
                || absint( $review['user_id'] ) !== $current_uid
                || empty( $moderation['allow_review_editing'] )
                || empty( $moderation['editing_logged_in_owners_only'] )
            ) {
                wp_send_json_error(
                    [
                        'resultCode' => 'forbidden',
                        'message'    => __( 'You cannot edit this review.', 'bw' ),
                    ],
                    403
                );
            }

            wp_send_json_success(
                [
                    'reviewId' => $review_id,
                    'mode'     => 'edit',
                    'prefill'  => [
                        'rating'  => absint( $review['rating'] ),
                        'content' => (string) $review['content'],
                    ],
                    'identity' => [
                        'displayName' => (string) $review['reviewer_display_name'],
                        'email'       => (string) $review['reviewer_email'],
                        'readonly'    => true,
                    ],
                ]
            );
        }

        /**
         * Update a review for the owner.
         */
        public function handle_update_review() {
            if ( ! is_user_logged_in() ) {
                wp_send_json_error(
                    [
                        'resultCode' => 'forbidden',
                        'message'    => __( 'You must be logged in to edit a review.', 'bw' ),
                    ],
                    403
                );
            }

            check_ajax_referer( 'bw_reviews_submit', 'nonce' );

            $review_id = isset( $_POST['review_id'] ) ? absint( wp_unslash( $_POST['review_id'] ) ) : 0;
            $payload   = [
                'rating'  => isset( $_POST['rating'] ) ? absint( wp_unslash( $_POST['rating'] ) ) : 0,
                'content' => isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '',
            ];

            $result = $this->submission_service->update_review_for_owner( $review_id, $payload, get_current_user_id() );

            if ( ! empty( $result['success'] ) ) {
                wp_send_json_success( $result );
            }

            wp_send_json_error( $result, 400 );
        }

        /**
         * Handle frontend confirmation requests.
         */
        public function handle_confirmation_request() {
            if ( ! isset( $_GET['bw_review_confirm'], $_GET['bw_review_token'] ) ) {
                return;
            }

            $review_id = absint( wp_unslash( $_GET['bw_review_confirm'] ) );
            $token     = sanitize_text_field( wp_unslash( $_GET['bw_review_token'] ) );
            $result    = $this->confirmation_service->confirm_review( $review_id, $token );
            $review    = ! empty( $result['review'] ) && is_array( $result['review'] ) ? $result['review'] : null;
            $product_id = $review && ! empty( $review['product_id'] ) ? absint( $review['product_id'] ) : 0;
            $target     = $product_id > 0 ? get_permalink( $product_id ) : home_url( '/' );

            $notice_key = 'invalid';
            if ( ! empty( $result['success'] ) ) {
                $notice_key = 'approved' === (string) $result['review']['status'] ? 'confirmed_approved' : 'confirmed_pending';
            } elseif ( ! empty( $result['code'] ) ) {
                $notice_key = sanitize_key( (string) $result['code'] );
            }

            $target = add_query_arg( 'bw_review_notice', $notice_key, $target );
            wp_safe_redirect( $target );
            exit;
        }
    }
}
