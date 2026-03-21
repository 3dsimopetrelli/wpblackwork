<?php
/**
 * Reviews widget renderer.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Reviews_Widget_Renderer' ) ) {
    class BW_Reviews_Widget_Renderer {
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
         * Render the full widget.
         *
         * @param array<string,mixed> $settings Widget settings.
         * @param string              $widget_id Widget instance ID.
         *
         * @return string
         */
        public function render_widget( $settings, $widget_id ) {
            $view = $this->build_widget_view( $settings, $widget_id );

            if ( empty( $view['product_id'] ) ) {
                return '';
            }

            ob_start();
            $this->include_template( 'widget.php', [ 'view' => $view ] );
            return (string) ob_get_clean();
        }

        /**
         * Render review cards for AJAX responses.
         *
         * @param array<int,array<string,mixed>> $reviews Review rows.
         * @param array<string,mixed>            $context Rendering context.
         *
         * @return string
         */
        public function render_reviews_html( $reviews, $context = [] ) {
            ob_start();

            if ( ! empty( $reviews ) ) {
                foreach ( $reviews as $review ) {
                    $card = $this->prepare_review_card( $review, $context );
                    $this->include_template( 'review-card.php', [ 'card' => $card ] );
                }
            }

            return (string) ob_get_clean();
        }

        /**
         * Build frontend data for the widget template.
         *
         * @param array<string,mixed> $settings Widget settings.
         * @param string              $widget_id Widget instance ID.
         *
         * @return array<string,mixed>
         */
        public function build_widget_view( $settings, $widget_id ) {
            $product_id = $this->resolve_product_id( $settings );
            $display    = BW_Reviews_Settings::get_display_settings();
            $submission = BW_Reviews_Settings::get_submission_settings();
            $moderation = BW_Reviews_Settings::get_moderation_settings();
            $emails     = BW_Reviews_Settings::get_email_settings();
            $sort       = 'featured';
            $limit      = max( 1, absint( $display['initial_visible_count'] ) );
            $is_logged_in = is_user_logged_in();
            $current_user = $is_logged_in ? wp_get_current_user() : null;
            $owned_review = $is_logged_in ? $this->repository->find_by_product_and_user_id( $product_id, get_current_user_id() ) : null;
            $can_edit_own = $this->can_edit_reviews() && $is_logged_in;
            $product_summary = $product_id > 0 ? $this->repository->get_product_summary( $product_id ) : $this->get_empty_summary();
            $review_source   = $this->resolve_review_source( $product_summary, $display );
            $summary         = 'global' === $review_source ? $this->repository->get_global_summary() : $product_summary;
            $reviews         = 'global' === $review_source
                ? $this->repository->get_global_reviews( $sort, 0, $limit )
                : ( $product_id > 0 ? $this->repository->get_product_reviews( $product_id, $sort, 0, $limit ) : [] );
            $shown           = count( $reviews );
            $card_context    = [
                'show_dates'          => ! empty( $display['show_dates'] ),
                'show_verified_badge' => ! empty( $display['show_verified_badge'] ),
                'can_edit_own'        => $can_edit_own,
                'owned_review_id'     => is_array( $owned_review ) ? absint( $owned_review['id'] ) : 0,
                'show_product_context' => 'global' === $review_source,
            ];

            $config = [
                'instanceId'          => (string) $widget_id,
                'productId'           => $product_id,
                'reviewSource'        => $review_source,
                'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
                'nonce'               => wp_create_nonce( 'bw_reviews_submit' ),
                'isLoggedIn'          => $is_logged_in,
                'canWriteReview'      => $this->can_write_reviews(),
                'canEditOwnReview'    => $can_edit_own,
                'sortDefault'         => $sort,
                'initialVisibleCount' => $limit,
                'loadMoreCount'       => max( 1, absint( $display['load_more_count'] ) ),
                'showRatingBreakdown' => ! empty( $display['show_rating_breakdown'] ),
                'showDates'           => ! empty( $display['show_dates'] ),
                'showVerifiedBadge'   => ! empty( $display['show_verified_badge'] ),
                'editingEnabled'      => ! empty( $moderation['allow_review_editing'] ),
                'hasMore'             => $summary['approved_count'] > $shown,
                'shownCount'          => $shown,
                'totalApproved'       => absint( $summary['approved_count'] ),
                'sortOptions'         => $this->get_sort_options(),
                'strings'             => [
                    'step'                     => __( 'Step', 'bw' ),
                    'of'                       => __( 'of', 'bw' ),
                    'closeConfirm'             => __( 'Close this review form? Your progress will be lost.', 'bw' ),
                    'validation'               => __( 'Please complete the required fields.', 'bw' ),
                    'writeReview'              => __( 'Write a review', 'bw' ),
                    'editReview'               => __( 'Edit your review', 'bw' ),
                    'loadMore'                 => __( 'Show more reviews', 'bw' ),
                    'loading'                  => __( 'Loading…', 'bw' ),
                    'submit'                   => __( 'Submit review', 'bw' ),
                    'saveChanges'              => __( 'Save changes', 'bw' ),
                    'back'                     => __( 'Back', 'bw' ),
                    'next'                     => __( 'Next', 'bw' ),
                    'close'                    => __( 'Close', 'bw' ),
                    'firstNameRequired'        => __( 'First name is required.', 'bw' ),
                    'lastNameRequired'         => __( 'Last name is required.', 'bw' ),
                    'emailRequired'            => __( 'A valid email address is required.', 'bw' ),
                    'privacyRequired'          => __( 'Please accept the terms and privacy acknowledgement before continuing.', 'bw' ),
                    'ratingRequired'           => __( 'Please select a rating.', 'bw' ),
                    'contentRequired'          => __( 'Please share your review before continuing.', 'bw' ),
                    'duplicateLoggedIn'        => __( 'You already reviewed this product. You can edit your existing review instead.', 'bw' ),
                    'duplicateGuest'           => __( 'A review for this product already exists for this email address.', 'bw' ),
                    'editPending'              => __( 'Your changes were saved and are awaiting approval.', 'bw' ),
                    'editUpdated'              => __( 'Your review has been updated.', 'bw' ),
                    'confirmationRequired'     => __( 'Your review was submitted. Please check your email and click the confirmation link to publish your review.', 'bw' ),
                    'pendingModeration'        => __( 'Your review was submitted and is awaiting approval.', 'bw' ),
                    'approved'                 => __( 'Your review is now live.', 'bw' ),
                    'confirmedLive'            => __( 'Your review has been confirmed and is now live. You can find it below.', 'bw' ),
                    'confirmedPending'         => __( 'Your review has been confirmed and is awaiting approval.', 'bw' ),
                    'confirmationInvalid'      => (string) $emails['confirmation_invalid_notice'],
                    'confirmationExpired'      => (string) $emails['confirmation_expired_notice'],
                    'continue'                 => __( 'Continue', 'bw' ),
                    'dislike'                  => __( 'Dislike it', 'bw' ),
                    'love'                     => __( 'Love it!', 'bw' ),
                ],
                'identity'            => [
                    'displayName' => $current_user instanceof WP_User ? $current_user->display_name : '',
                    'email'       => $current_user instanceof WP_User ? $current_user->user_email : '',
                ],
                'ownedReviewId'       => is_array( $owned_review ) ? absint( $owned_review['id'] ) : 0,
                'submissionPolicy'    => [
                    'allowGuests'           => ! empty( $submission['allow_guests'] ),
                    'loggedInOnly'          => ! empty( $submission['logged_in_only'] ),
                    'verifiedBuyersOnly'    => ! empty( $submission['verified_buyers_only'] ),
                    'requireConfirmation'   => ! empty( $submission['require_email_confirmation'] ),
                ],
            ];

            return [
                'instance_id'          => sanitize_html_class( (string) $widget_id ),
                'product_id'           => $product_id,
                'product_name'         => $product_id > 0 ? get_the_title( $product_id ) : '',
                'average_rating'       => (float) $summary['average_rating'],
                'average_label'        => number_format_i18n( (float) $summary['average_rating'], 1 ),
                'summary_stars_html'   => $this->render_stars( (float) $summary['average_rating'] ),
                'approved_count'       => absint( $summary['approved_count'] ),
                'approved_count_label' => sprintf(
                    _n( '%d Review', '%d Reviews', absint( $summary['approved_count'] ), 'bw' ),
                    absint( $summary['approved_count'] )
                ),
                'breakdown'            => is_array( $summary['breakdown'] ) ? $summary['breakdown'] : [],
                'reviews_html'         => $this->render_reviews_html(
                    $reviews,
                    $card_context
                ),
                'show_breakdown'       => ! empty( $display['show_rating_breakdown'] ),
                'show_dates'           => ! empty( $display['show_dates'] ),
                'show_verified_badge'  => ! empty( $display['show_verified_badge'] ),
                'can_write_review'     => $this->can_write_reviews(),
                'has_reviews'          => absint( $summary['approved_count'] ) > 0,
                'has_more'             => $summary['approved_count'] > $shown,
                'load_more_label'      => __( 'Show more reviews', 'bw' ),
                'write_review_label'   => __( 'Write a review', 'bw' ),
                'sort_label'           => __( 'Featured', 'bw' ),
                'sort_options'         => $this->get_sort_options(),
                'breakdown_interactive' => ! empty( $display['show_rating_breakdown'] ) && absint( $summary['approved_count'] ) > 0,
                'review_source'        => $review_source,
                'config'               => $config,
                'render_modal'         => true,
                'modal_title_create'   => __( 'Write a review', 'bw' ),
                'modal_title_edit'     => __( 'Edit your review', 'bw' ),
                'empty_title'          => __( 'No reviews yet', 'bw' ),
                'empty_message'        => __( 'Be the first to share your experience with this piece.', 'bw' ),
                'terms_url'            => $this->get_terms_url(),
                'privacy_url'          => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
            ];
        }

        /**
         * Prepare one review card for rendering.
         *
         * @param array<string,mixed> $review   Review row.
         * @param array<string,mixed> $context  Render context.
         *
         * @return array<string,mixed>
         */
        public function prepare_review_card( $review, $context = [] ) {
            $show_dates          = ! empty( $context['show_dates'] );
            $show_verified_badge = ! empty( $context['show_verified_badge'] );
            $can_edit_own        = ! empty( $context['can_edit_own'] );
            $owned_review_id     = isset( $context['owned_review_id'] ) ? absint( $context['owned_review_id'] ) : 0;
            $review_id           = isset( $review['id'] ) ? absint( $review['id'] ) : 0;

            return [
                'id'                => $review_id,
                'reviewer_name'     => isset( $review['reviewer_display_name'] ) ? (string) $review['reviewer_display_name'] : '',
                'rating'            => isset( $review['rating'] ) ? absint( $review['rating'] ) : 0,
                'stars_html'        => $this->render_stars( isset( $review['rating'] ) ? absint( $review['rating'] ) : 0 ),
                'content_html'      => wpautop( esc_html( isset( $review['content'] ) ? (string) $review['content'] : '' ) ),
                'date_label'        => $show_dates && ! empty( $review['created_at'] ) ? $this->format_review_date( (string) $review['created_at'] ) : '',
                'verified_purchase' => $show_verified_badge && ! empty( $review['verified_purchase'] ),
                'featured'          => ! empty( $review['featured'] ),
                'editable'          => $can_edit_own && $owned_review_id > 0 && $owned_review_id === $review_id,
                'show_product_context' => ! empty( $context['show_product_context'] ),
                'product_name'      => ! empty( $review['product_title'] ) ? (string) $review['product_title'] : get_the_title( isset( $review['product_id'] ) ? absint( $review['product_id'] ) : 0 ),
                'product_image_url' => ! empty( $context['show_product_context'] ) ? $this->get_product_image_url( isset( $review['product_id'] ) ? absint( $review['product_id'] ) : 0 ) : '',
            ];
        }

        /**
         * Get one review card HTML.
         *
         * @param array<string,mixed> $review   Review row.
         * @param array<string,mixed> $context  Render context.
         *
         * @return string
         */
        public function render_review_card_html( $review, $context = [] ) {
            $card = $this->prepare_review_card( $review, $context );

            ob_start();
            $this->include_template( 'review-card.php', [ 'card' => $card ] );
            return (string) ob_get_clean();
        }

        /**
         * Render star markup.
         *
         * @param float $rating Rating value.
         * @param int   $max    Maximum stars.
         *
         * @return string
         */
        public function render_stars( $rating, $max = 5 ) {
            $rating = max( 0, min( (float) $rating, (float) $max ) );
            $filled = (int) round( $rating );

            ob_start();
            ?>
            <span class="bw-reviews-stars" aria-hidden="true">
                <?php for ( $index = 1; $index <= $max; $index++ ) : ?>
                    <span class="bw-reviews-stars__star<?php echo $index <= $filled ? ' is-filled' : ''; ?>">★</span>
                <?php endfor; ?>
            </span>
            <?php
            return (string) ob_get_clean();
        }

        /**
         * Resolve current product context.
         *
         * @param array<string,mixed> $settings Widget settings.
         *
         * @return int
         */
        private function resolve_product_id( $settings ) {
            $product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

            if ( $product_id > 0 ) {
                return $product_id;
            }

            if ( function_exists( 'bw_tbl_resolve_product_context_id' ) ) {
                $resolution = bw_tbl_resolve_product_context_id( [ '__widget_class' => __CLASS__ ] );
                $product_id = isset( $resolution['id'] ) ? absint( $resolution['id'] ) : 0;
            }

            if ( $product_id <= 0 && function_exists( 'wc_get_product' ) ) {
                global $product;

                if ( $product instanceof WC_Product ) {
                    $product_id = absint( $product->get_id() );
                } elseif ( function_exists( 'is_product' ) && is_product() ) {
                    $product_id = absint( get_the_ID() );
                }
            }

            return $product_id;
        }

        /**
         * Determine whether frontend submission is allowed by policy.
         *
         * @return bool
         */
        private function can_write_reviews() {
            if ( ! BW_Reviews_Settings::is_enabled() ) {
                return false;
            }

            $submission = BW_Reviews_Settings::get_submission_settings();

            if ( ! is_user_logged_in() && ! empty( $submission['logged_in_only'] ) ) {
                return false;
            }

            if ( ! is_user_logged_in() && empty( $submission['allow_guests'] ) ) {
                return false;
            }

            return true;
        }

        /**
         * Determine whether owner edit actions should be shown.
         *
         * @return bool
         */
        private function can_edit_reviews() {
            $moderation = BW_Reviews_Settings::get_moderation_settings();

            return ! empty( $moderation['allow_review_editing'] );
        }

        /**
         * Get review list sort options.
         *
         * @return array<int,array<string,string>>
         */
        private function get_sort_options() {
            return [
                [
                    'value' => 'featured',
                    'label' => __( 'Featured', 'bw' ),
                ],
                [
                    'value' => 'newest',
                    'label' => __( 'Newest', 'bw' ),
                ],
                [
                    'value' => 'highest_rating',
                    'label' => __( 'Highest Ratings', 'bw' ),
                ],
                [
                    'value' => 'lowest_rating',
                    'label' => __( 'Lowest Ratings', 'bw' ),
                ],
            ];
        }

        /**
         * Format a review date for display.
         *
         * @param string $mysql_date MySQL date string.
         *
         * @return string
         */
        private function format_review_date( $mysql_date ) {
            $timestamp = strtotime( $mysql_date );

            return $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : '';
        }

        /**
         * Resolve whether widget should fall back to global approved reviews.
         *
         * @param array<string,mixed> $settings        Widget settings.
         * @param array<string,mixed> $product_summary Product summary.
         *
         * @return string
         */
        private function resolve_review_source( $product_summary, $display ) {
            $fallback_enabled   = ! empty( $display['fallback_to_global_reviews_when_empty'] );
            $has_product_reviews = ! empty( $product_summary['approved_count'] );

            if ( $fallback_enabled && ! $has_product_reviews ) {
                return 'global';
            }

            return 'product';
        }

        /**
         * Product thumbnail URL for review card context.
         *
         * @param int $product_id Product ID.
         *
         * @return string
         */
        private function get_product_image_url( $product_id ) {
            $product_id = absint( $product_id );

            if ( $product_id <= 0 ) {
                return '';
            }

            $image_url = get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' );

            if ( $image_url ) {
                return (string) $image_url;
            }

            if ( function_exists( 'wc_placeholder_img_src' ) ) {
                return (string) wc_placeholder_img_src( 'woocommerce_thumbnail' );
            }

            return '';
        }

        /**
         * Empty summary shape.
         *
         * @return array<string,mixed>
         */
        private function get_empty_summary() {
            return [
                'average_rating' => 0,
                'approved_count' => 0,
                'breakdown'      => [],
            ];
        }

        /**
         * Resolve the terms of service URL.
         *
         * @return string
         */
        private function get_terms_url() {
            $page = get_page_by_path( 'terms-of-service' );

            if ( $page instanceof WP_Post ) {
                return get_permalink( $page );
            }

            return home_url( '/terms-of-service/' );
        }

        /**
         * Include a frontend template file.
         *
         * @param string              $template Template file.
         * @param array<string,mixed> $vars     Variables.
         *
         * @return void
         */
        private function include_template( $template, $vars = [] ) {
            $template_path = __DIR__ . '/templates/' . ltrim( $template, '/' );

            if ( ! file_exists( $template_path ) ) {
                return;
            }

            extract( $vars, EXTR_SKIP );
            include $template_path;
        }
    }
}
