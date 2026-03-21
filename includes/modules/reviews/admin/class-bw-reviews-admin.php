<?php
/**
 * Reviews admin UI.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Reviews_Admin' ) ) {
    class BW_Reviews_Admin {
        /**
         * @var BW_Reviews_Admin|null
         */
        private static $instance = null;

        /**
         * @var BW_Reviews_Repository
         */
        private $repository;

        /**
         * @var BW_Review_Moderation_Service
         */
        private $moderation_service;

        /**
         * Initialize hooks.
         *
         * @return BW_Reviews_Admin
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
            $this->repository         = new BW_Reviews_Repository();
            $this->moderation_service = new BW_Review_Moderation_Service( $this->repository );

            add_action( 'admin_menu', [ $this, 'register_pages' ], 35 );
            add_action( 'admin_init', [ $this, 'handle_settings_post' ] );
            add_action( 'admin_init', [ $this, 'handle_list_actions' ] );
            add_action( 'admin_init', [ $this, 'handle_edit_post' ] );
        }

        /**
         * Register admin pages.
         */
        public function register_pages() {
            add_submenu_page(
                'blackwork-site-settings',
                __( 'Reviews', 'bw' ),
                __( 'Reviews', 'bw' ),
                'manage_options',
                BW_Reviews_Settings::LIST_PAGE_SLUG,
                [ $this, 'render_list_page' ]
            );

            add_submenu_page(
                'blackwork-site-settings',
                __( 'Reviews Settings', 'bw' ),
                __( 'Reviews Settings', 'bw' ),
                'manage_options',
                BW_Reviews_Settings::TAB_PAGE_SLUG,
                [ $this, 'render_settings_page' ]
            );

            add_submenu_page(
                null,
                __( 'Edit Review', 'bw' ),
                __( 'Edit Review', 'bw' ),
                'manage_options',
                BW_Reviews_Settings::EDIT_PAGE_SLUG,
                [ $this, 'render_edit_page' ]
            );
        }

        /**
         * Get review status label.
         *
         * @param string $status Status key.
         *
         * @return string
         */
        public static function get_status_label( $status ) {
            $labels = [
                'pending_confirmation' => __( 'Pending confirmation', 'bw' ),
                'pending_moderation'   => __( 'Pending moderation', 'bw' ),
                'approved'             => __( 'Approved', 'bw' ),
                'rejected'             => __( 'Rejected', 'bw' ),
                'trash'                => __( 'Trash', 'bw' ),
            ];

            return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );
        }

        /**
         * Handle settings save.
         */
        public function handle_settings_post() {
            if ( empty( $_POST['bw_reviews_settings_submit'] ) ) {
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            check_admin_referer( 'bw_reviews_settings_save', 'bw_reviews_settings_nonce' );

            $tab = isset( $_POST['bw_reviews_settings_tab'] ) ? BW_Reviews_Settings::normalize_tab( wp_unslash( $_POST['bw_reviews_settings_tab'] ) ) : 'general';

            if ( 'general' === $tab ) {
                $settings = [
                    'enabled'         => ! empty( $_POST['bw_reviews_general_enabled'] ) ? 1 : 0,
                    'admin_page_size' => isset( $_POST['bw_reviews_general_admin_page_size'] ) ? max( 5, min( 100, absint( $_POST['bw_reviews_general_admin_page_size'] ) ) ) : 20,
                ];
                update_option( BW_Reviews_Settings::GENERAL_OPTION, $settings );
            } elseif ( 'display' === $tab ) {
                $settings = [
                    'initial_visible_count' => isset( $_POST['bw_reviews_display_initial_visible_count'] ) ? max( 1, absint( $_POST['bw_reviews_display_initial_visible_count'] ) ) : 6,
                    'load_more_count'       => isset( $_POST['bw_reviews_display_load_more_count'] ) ? max( 1, absint( $_POST['bw_reviews_display_load_more_count'] ) ) : 6,
                    'show_rating_breakdown' => ! empty( $_POST['bw_reviews_display_show_rating_breakdown'] ) ? 1 : 0,
                    'show_dates'            => ! empty( $_POST['bw_reviews_display_show_dates'] ) ? 1 : 0,
                    'show_verified_badge'   => ! empty( $_POST['bw_reviews_display_show_verified_badge'] ) ? 1 : 0,
                    'fallback_to_global_reviews_when_empty' => ! empty( $_POST['bw_reviews_display_fallback_to_global_reviews_when_empty'] ) ? 1 : 0,
                ];
                update_option( BW_Reviews_Settings::DISPLAY_OPTION, $settings );
            } elseif ( 'submission' === $tab ) {
                $settings = [
                    'allow_guests'               => ! empty( $_POST['bw_reviews_submission_allow_guests'] ) ? 1 : 0,
                    'logged_in_only'             => ! empty( $_POST['bw_reviews_submission_logged_in_only'] ) ? 1 : 0,
                    'verified_buyers_only'       => ! empty( $_POST['bw_reviews_submission_verified_buyers_only'] ) ? 1 : 0,
                    'require_email_confirmation' => ! empty( $_POST['bw_reviews_submission_require_email_confirmation'] ) ? 1 : 0,
                ];
                update_option( BW_Reviews_Settings::SUBMISSION_OPTION, $settings );
            } elseif ( 'moderation' === $tab ) {
                $settings = [
                    'require_moderation'            => ! empty( $_POST['bw_reviews_moderation_require_moderation'] ) ? 1 : 0,
                    'allow_review_editing'          => ! empty( $_POST['bw_reviews_moderation_allow_review_editing'] ) ? 1 : 0,
                    'editing_logged_in_owners_only' => ! empty( $_POST['bw_reviews_moderation_editing_logged_in_owners_only'] ) ? 1 : 0,
                    'edit_requires_approval_again'  => ! empty( $_POST['bw_reviews_moderation_edit_requires_approval_again'] ) ? 1 : 0,
                ];
                update_option( BW_Reviews_Settings::MODERATION_OPTION, $settings );
            } elseif ( 'emails' === $tab ) {
                $defaults = BW_Reviews_Settings::get_email_defaults();
                $settings = [
                    'confirmation_subject'        => isset( $_POST['bw_reviews_email_confirmation_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_reviews_email_confirmation_subject'] ) ) : $defaults['confirmation_subject'],
                    'confirmation_heading'        => isset( $_POST['bw_reviews_email_confirmation_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_reviews_email_confirmation_heading'] ) ) : $defaults['confirmation_heading'],
                    'confirmation_body'           => isset( $_POST['bw_reviews_email_confirmation_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bw_reviews_email_confirmation_body'] ) ) : $defaults['confirmation_body'],
                    'confirmation_button_label'   => isset( $_POST['bw_reviews_email_confirmation_button_label'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_reviews_email_confirmation_button_label'] ) ) : $defaults['confirmation_button_label'],
                    'confirmation_success_notice' => isset( $_POST['bw_reviews_email_confirmation_success_notice'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_reviews_email_confirmation_success_notice'] ) ) : $defaults['confirmation_success_notice'],
                    'confirmation_invalid_notice' => isset( $_POST['bw_reviews_email_confirmation_invalid_notice'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_reviews_email_confirmation_invalid_notice'] ) ) : $defaults['confirmation_invalid_notice'],
                    'confirmation_expired_notice' => isset( $_POST['bw_reviews_email_confirmation_expired_notice'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_reviews_email_confirmation_expired_notice'] ) ) : $defaults['confirmation_expired_notice'],
                ];
                update_option( BW_Reviews_Settings::EMAIL_OPTION, $settings );
            } else {
                $settings = [
                    'enabled'                  => ! empty( $_POST['bw_reviews_brevo_enabled'] ) ? 1 : 0,
                    'sync_on_submission'       => ! empty( $_POST['bw_reviews_brevo_sync_on_submission'] ) ? 1 : 0,
                    'sync_on_confirmation'     => ! empty( $_POST['bw_reviews_brevo_sync_on_confirmation'] ) ? 1 : 0,
                    'sync_on_approval'         => ! empty( $_POST['bw_reviews_brevo_sync_on_approval'] ) ? 1 : 0,
                    'review_list_id'           => isset( $_POST['bw_reviews_brevo_review_list_id'] ) ? absint( $_POST['bw_reviews_brevo_review_list_id'] ) : 0,
                    'confirmed_review_list_id' => isset( $_POST['bw_reviews_brevo_confirmed_review_list_id'] ) ? absint( $_POST['bw_reviews_brevo_confirmed_review_list_id'] ) : 0,
                    'rewarded_review_list_id'  => isset( $_POST['bw_reviews_brevo_rewarded_review_list_id'] ) ? absint( $_POST['bw_reviews_brevo_rewarded_review_list_id'] ) : 0,
                ];
                update_option( BW_Reviews_Settings::BREVO_OPTION, $settings );
            }

            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'  => BW_Reviews_Settings::TAB_PAGE_SLUG,
                        'tab'   => $tab,
                        'saved' => 1,
                    ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }

        /**
         * Handle row and bulk actions.
         */
        public function handle_list_actions() {
            $page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
            if ( BW_Reviews_Settings::LIST_PAGE_SLUG !== $page || ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $action = isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action']
                ? sanitize_key( wp_unslash( $_REQUEST['action'] ) )
                : ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '' );

            if ( '' === $action ) {
                return;
            }

            $review_ids = [];
            if ( isset( $_REQUEST['review_ids'] ) && is_array( $_REQUEST['review_ids'] ) ) {
                $review_ids = array_map( 'absint', wp_unslash( $_REQUEST['review_ids'] ) );
                check_admin_referer( 'bulk-bw_reviews' );
            } elseif ( isset( $_REQUEST['review_id'] ) ) {
                $review_ids = [ absint( wp_unslash( $_REQUEST['review_id'] ) ) ];
                check_admin_referer( 'bw_reviews_row_action_' . $review_ids[0] );
            }

            $review_ids = array_filter( $review_ids );
            if ( empty( $review_ids ) ) {
                return;
            }

            foreach ( $review_ids as $review_id ) {
                switch ( $action ) {
                    case 'approve':
                        $this->moderation_service->approve( $review_id );
                        break;
                    case 'reject':
                        $this->moderation_service->reject( $review_id );
                        break;
                    case 'feature':
                        $this->moderation_service->set_featured( $review_id, true );
                        break;
                    case 'unfeature':
                        $this->moderation_service->set_featured( $review_id, false );
                        break;
                    case 'trash':
                        $this->moderation_service->trash( $review_id );
                        break;
                    case 'restore':
                        $this->moderation_service->restore( $review_id );
                        break;
                    case 'delete':
                        $this->moderation_service->delete_permanently( $review_id );
                        break;
                    case 'resend_confirmation':
                        $this->moderation_service->resend_confirmation( $review_id );
                        break;
                }
            }

            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'    => BW_Reviews_Settings::LIST_PAGE_SLUG,
                        'updated' => 1,
                    ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }

        /**
         * Handle admin review edit save.
         */
        public function handle_edit_post() {
            if ( empty( $_POST['bw_reviews_edit_submit'] ) ) {
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            check_admin_referer( 'bw_reviews_edit_save', 'bw_reviews_edit_nonce' );

            $review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
            $review    = $this->repository->get_review( $review_id );

            if ( ! is_array( $review ) ) {
                return;
            }

            $status = isset( $_POST['review_status'] ) ? sanitize_key( wp_unslash( $_POST['review_status'] ) ) : (string) $review['status'];
            if ( ! in_array( $status, BW_Reviews_Settings::get_statuses(), true ) ) {
                $status = (string) $review['status'];
            }

            $submission_service = new BW_Review_Submission_Service( $this->repository );
            $first_name = isset( $_POST['reviewer_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['reviewer_first_name'] ) ) : '';
            $last_name  = isset( $_POST['reviewer_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['reviewer_last_name'] ) ) : '';
            $display    = trim( preg_replace( '/\s+/', ' ', trim( $first_name . ' ' . $last_name ) ) );
            $email      = isset( $_POST['reviewer_email'] ) ? $submission_service->normalize_email( $_POST['reviewer_email'] ) : (string) $review['reviewer_email'];
            if ( '' === $email ) {
                $email = (string) $review['reviewer_email'];
            }
            $rating     = isset( $_POST['rating'] ) ? max( 1, min( 5, absint( $_POST['rating'] ) ) ) : absint( $review['rating'] );
            $content    = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : (string) $review['content'];

            $update = [
                'reviewer_first_name'   => $first_name,
                'reviewer_last_name'    => $last_name,
                'reviewer_display_name' => '' !== $display ? $display : __( 'Anonymous', 'bw' ),
                'reviewer_email'        => $email,
                'reviewer_email_hash'   => strtolower( hash( 'sha256', $email ) ),
                'rating'                => $rating,
                'content'               => $content,
                'status'                => $status,
                'verified_purchase'     => ! empty( $_POST['verified_purchase'] ) ? 1 : 0,
                'featured'              => ! empty( $_POST['featured'] ) ? 1 : 0,
                'updated_at'            => current_time( 'mysql' ),
            ];

            if ( 'approved' === $status && empty( $review['approved_at'] ) ) {
                $update['approved_at'] = current_time( 'mysql' );
            }
            if ( 'rejected' === $status && empty( $review['rejected_at'] ) ) {
                $update['rejected_at'] = current_time( 'mysql' );
            }

            $this->repository->update_review( $review_id, $update );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'      => BW_Reviews_Settings::EDIT_PAGE_SLUG,
                        'review_id' => $review_id,
                        'saved'     => 1,
                    ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }

        /**
         * Render list page.
         */
        public function render_list_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $table = new BW_Reviews_List_Table();
            $table->prepare_items();
            ?>
            <div class="wrap bw-admin-root bw-admin-page bw-admin-page-reviews">
                <div class="bw-admin-header">
                    <h1 class="bw-admin-title"><?php esc_html_e( 'Reviews', 'bw' ); ?></h1>
                    <p class="bw-admin-subtitle"><?php esc_html_e( 'Moderate, feature, and manage the custom reviews stored by the Reviews module.', 'bw' ); ?></p>
                </div>

                <?php if ( isset( $_GET['updated'] ) ) : ?>
                    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Reviews updated.', 'bw' ); ?></p></div>
                <?php endif; ?>

                <div class="bw-admin-action-bar">
                    <div class="bw-admin-action-meta"><?php esc_html_e( 'Use views, filters, and bulk actions to manage review lifecycle states.', 'bw' ); ?></div>
                    <div class="bw-admin-action-buttons">
                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . BW_Reviews_Settings::TAB_PAGE_SLUG ) ); ?>">
                            <?php esc_html_e( 'Open Settings', 'bw' ); ?>
                        </a>
                    </div>
                </div>

                <form method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr( BW_Reviews_Settings::LIST_PAGE_SLUG ); ?>" />
                    <?php $table->search_box( __( 'Search reviews', 'bw' ), 'bw-reviews-search' ); ?>
                    <?php $table->display(); ?>
                </form>
            </div>
            <?php
        }

        /**
         * Render settings page.
         */
        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $active_tab   = isset( $_GET['tab'] ) ? BW_Reviews_Settings::normalize_tab( wp_unslash( $_GET['tab'] ) ) : 'general';
            $tabs         = BW_Reviews_Settings::get_tabs();
            $general      = BW_Reviews_Settings::get_general_settings();
            $display      = BW_Reviews_Settings::get_display_settings();
            $submission   = BW_Reviews_Settings::get_submission_settings();
            $moderation   = BW_Reviews_Settings::get_moderation_settings();
            $emails       = BW_Reviews_Settings::get_email_settings();
            $brevo        = BW_Reviews_Settings::get_brevo_settings();
            $global_brevo = BW_Reviews_Settings::get_global_brevo_settings();
            $lists_data   = BW_Brevo_Lists_Service::get_lists( isset( $global_brevo['api_key'] ) ? (string) $global_brevo['api_key'] : '' );
            ?>
            <div class="wrap bw-admin-root bw-admin-page bw-admin-page-reviews-settings">
                <div class="bw-admin-header">
                    <h1 class="bw-admin-title"><?php esc_html_e( 'Reviews Settings', 'bw' ); ?></h1>
                    <p class="bw-admin-subtitle"><?php esc_html_e( 'Configure admin behavior, submission policy, moderation, confirmation email, and Brevo sync for Reviews.', 'bw' ); ?></p>
                </div>

                <?php if ( isset( $_GET['saved'] ) ) : ?>
                    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Reviews settings saved.', 'bw' ); ?></p></div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field( 'bw_reviews_settings_save', 'bw_reviews_settings_nonce' ); ?>
                    <input type="hidden" name="bw_reviews_settings_submit" value="1" />
                    <input type="hidden" name="bw_reviews_settings_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

                    <div class="bw-admin-action-bar">
                        <div class="bw-admin-action-meta"><?php esc_html_e( 'Review behavior is configured by tab; WooCommerce products and orders remain the authority surface.', 'bw' ); ?></div>
                        <div class="bw-admin-action-buttons">
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bw' ); ?></button>
                        </div>
                    </div>

                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e( 'Sections', 'bw' ); ?></h2>
                        <nav class="nav-tab-wrapper bw-admin-tabs">
                            <?php foreach ( $tabs as $tab_key => $label ) : ?>
                                <a class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'page' => BW_Reviews_Settings::TAB_PAGE_SLUG, 'tab' => $tab_key ], admin_url( 'admin.php' ) ) ); ?>">
                                    <?php echo esc_html( $label ); ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    </section>

                    <section class="bw-admin-card">
                        <table class="form-table bw-admin-table" role="presentation">
                            <?php if ( 'general' === $active_tab ) : ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Enable Reviews module', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_general_enabled" value="1" <?php checked( $general['enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable custom reviews and disable WooCommerce native product reviews.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_general_admin_page_size"><?php esc_html_e( 'Admin page size', 'bw' ); ?></label></th>
                                    <td><input type="number" min="5" max="100" id="bw_reviews_general_admin_page_size" name="bw_reviews_general_admin_page_size" value="<?php echo esc_attr( $general['admin_page_size'] ); ?>" class="small-text" /></td>
                                </tr>
                            <?php elseif ( 'display' === $active_tab ) : ?>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_display_initial_visible_count"><?php esc_html_e( 'Initial visible reviews', 'bw' ); ?></label></th>
                                    <td><input type="number" min="1" id="bw_reviews_display_initial_visible_count" name="bw_reviews_display_initial_visible_count" value="<?php echo esc_attr( $display['initial_visible_count'] ); ?>" class="small-text" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_display_load_more_count"><?php esc_html_e( 'Reviews per Show more', 'bw' ); ?></label></th>
                                    <td><input type="number" min="1" id="bw_reviews_display_load_more_count" name="bw_reviews_display_load_more_count" value="<?php echo esc_attr( $display['load_more_count'] ); ?>" class="small-text" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Show rating breakdown', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_display_show_rating_breakdown" value="1" <?php checked( $display['show_rating_breakdown'], 1 ); ?> /> <?php esc_html_e( 'Display the rating summary breakdown.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Show dates', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_display_show_dates" value="1" <?php checked( $display['show_dates'], 1 ); ?> /> <?php esc_html_e( 'Display review submission dates.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Show verified badge', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_display_show_verified_badge" value="1" <?php checked( $display['show_verified_badge'], 1 ); ?> /> <?php esc_html_e( 'Display the verified purchase badge.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Use global reviews when empty', 'bw' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="bw_reviews_display_fallback_to_global_reviews_when_empty" value="1" <?php checked( $display['fallback_to_global_reviews_when_empty'], 1 ); ?> />
                                            <?php esc_html_e( 'When a product has no approved reviews, show the site-wide reviews summary and review list instead.', 'bw' ); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e( 'This helps avoid empty product pages. In this global mode, each review card also shows the reviewed product image and product name so visitors can understand which item the review belongs to.', 'bw' ); ?></p>
                                    </td>
                                </tr>
                            <?php elseif ( 'submission' === $active_tab ) : ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Allow guests', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_submission_allow_guests" value="1" <?php checked( $submission['allow_guests'], 1 ); ?> /> <?php esc_html_e( 'Allow guest users to submit reviews.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Logged-in only', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_submission_logged_in_only" value="1" <?php checked( $submission['logged_in_only'], 1 ); ?> /> <?php esc_html_e( 'Require login before submitting reviews.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Verified buyers only', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_submission_verified_buyers_only" value="1" <?php checked( $submission['verified_buyers_only'], 1 ); ?> /> <?php esc_html_e( 'Allow submissions only for verified buyers.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Require email confirmation', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_submission_require_email_confirmation" value="1" <?php checked( $submission['require_email_confirmation'], 1 ); ?> /> <?php esc_html_e( 'Send a confirmation email before the review enters moderation/approval.', 'bw' ); ?></label></td>
                                </tr>
                            <?php elseif ( 'moderation' === $active_tab ) : ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Require moderation', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_moderation_require_moderation" value="1" <?php checked( $moderation['require_moderation'], 1 ); ?> /> <?php esc_html_e( 'New confirmed reviews require manual approval.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Allow review editing', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_moderation_allow_review_editing" value="1" <?php checked( $moderation['allow_review_editing'], 1 ); ?> /> <?php esc_html_e( 'Allow owners to edit their review.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Logged-in owners only', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_moderation_editing_logged_in_owners_only" value="1" <?php checked( $moderation['editing_logged_in_owners_only'], 1 ); ?> /> <?php esc_html_e( 'Limit review editing to logged-in owners.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Edit requires approval again', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_moderation_edit_requires_approval_again" value="1" <?php checked( $moderation['edit_requires_approval_again'], 1 ); ?> /> <?php esc_html_e( 'Send approved reviews back to pending moderation when edited.', 'bw' ); ?></label></td>
                                </tr>
                            <?php elseif ( 'emails' === $active_tab ) : ?>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_subject"><?php esc_html_e( 'Confirmation subject', 'bw' ); ?></label></th>
                                    <td><input type="text" id="bw_reviews_email_confirmation_subject" name="bw_reviews_email_confirmation_subject" class="regular-text" value="<?php echo esc_attr( $emails['confirmation_subject'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_heading"><?php esc_html_e( 'Confirmation heading', 'bw' ); ?></label></th>
                                    <td><input type="text" id="bw_reviews_email_confirmation_heading" name="bw_reviews_email_confirmation_heading" class="regular-text" value="<?php echo esc_attr( $emails['confirmation_heading'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_body"><?php esc_html_e( 'Confirmation body', 'bw' ); ?></label></th>
                                    <td><textarea id="bw_reviews_email_confirmation_body" name="bw_reviews_email_confirmation_body" rows="4" class="large-text"><?php echo esc_textarea( $emails['confirmation_body'] ); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_button_label"><?php esc_html_e( 'Button label', 'bw' ); ?></label></th>
                                    <td><input type="text" id="bw_reviews_email_confirmation_button_label" name="bw_reviews_email_confirmation_button_label" class="regular-text" value="<?php echo esc_attr( $emails['confirmation_button_label'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_success_notice"><?php esc_html_e( 'Success notice', 'bw' ); ?></label></th>
                                    <td><input type="text" id="bw_reviews_email_confirmation_success_notice" name="bw_reviews_email_confirmation_success_notice" class="regular-text" value="<?php echo esc_attr( $emails['confirmation_success_notice'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_invalid_notice"><?php esc_html_e( 'Invalid-link notice', 'bw' ); ?></label></th>
                                    <td><input type="text" id="bw_reviews_email_confirmation_invalid_notice" name="bw_reviews_email_confirmation_invalid_notice" class="regular-text" value="<?php echo esc_attr( $emails['confirmation_invalid_notice'] ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="bw_reviews_email_confirmation_expired_notice"><?php esc_html_e( 'Expired-link notice', 'bw' ); ?></label></th>
                                    <td><input type="text" id="bw_reviews_email_confirmation_expired_notice" name="bw_reviews_email_confirmation_expired_notice" class="regular-text" value="<?php echo esc_attr( $emails['confirmation_expired_notice'] ); ?>" /></td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Global Brevo API key', 'bw' ); ?></th>
                                    <td>
                                        <input type="password" class="regular-text" value="<?php echo esc_attr( isset( $global_brevo['api_key'] ) ? (string) $global_brevo['api_key'] : '' ); ?>" readonly="readonly" autocomplete="off" />
                                        <p class="description"><?php esc_html_e( 'This is reused from Mail Marketing > General and cannot be edited here.', 'bw' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Enable Brevo sync', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_brevo_enabled" value="1" <?php checked( $brevo['enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable review-specific sync to Brevo lists.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Sync on submission', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_brevo_sync_on_submission" value="1" <?php checked( $brevo['sync_on_submission'], 1 ); ?> /> <?php esc_html_e( 'Sync when the review is initially submitted.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Sync on confirmation', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_brevo_sync_on_confirmation" value="1" <?php checked( $brevo['sync_on_confirmation'], 1 ); ?> /> <?php esc_html_e( 'Sync when email confirmation is completed.', 'bw' ); ?></label></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Sync on approval', 'bw' ); ?></th>
                                    <td><label><input type="checkbox" name="bw_reviews_brevo_sync_on_approval" value="1" <?php checked( $brevo['sync_on_approval'], 1 ); ?> /> <?php esc_html_e( 'Sync when a review is approved.', 'bw' ); ?></label></td>
                                </tr>
                                <?php $this->render_brevo_list_select_row( 'Review list', 'bw_reviews_brevo_review_list_id', $brevo['review_list_id'], $lists_data ); ?>
                                <?php $this->render_brevo_list_select_row( 'Confirmed review list', 'bw_reviews_brevo_confirmed_review_list_id', $brevo['confirmed_review_list_id'], $lists_data ); ?>
                                <?php $this->render_brevo_list_select_row( 'Rewarded review list', 'bw_reviews_brevo_rewarded_review_list_id', $brevo['rewarded_review_list_id'], $lists_data ); ?>
                            <?php endif; ?>
                        </table>
                    </section>
                </form>
            </div>
            <?php
        }

        /**
         * Render a Brevo list select row.
         *
         * @param string               $label     Label.
         * @param string               $field     Field name.
         * @param int                  $selected  Selected list ID.
         * @param array<string,mixed>  $lists_data Lists data.
         */
        private function render_brevo_list_select_row( $label, $field, $selected, $lists_data ) {
            ?>
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label></th>
                <td>
                    <?php if ( ! empty( $lists_data['success'] ) ) : ?>
                        <select id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>">
                            <option value="0"><?php esc_html_e( 'Select list', 'bw' ); ?></option>
                            <?php foreach ( $lists_data['lists'] as $list ) : ?>
                                <option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( absint( $selected ), absint( $list['id'] ) ); ?>>
                                    <?php echo esc_html( sprintf( '#%d - %s', absint( $list['id'] ), $list['name'] ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <input type="number" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( absint( $selected ) ); ?>" class="small-text" min="0" />
                        <p class="description"><?php echo esc_html( $lists_data['message'] ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }

        /**
         * Render edit page.
         */
        public function render_edit_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $review_id = isset( $_GET['review_id'] ) ? absint( wp_unslash( $_GET['review_id'] ) ) : 0;
            $review    = $this->repository->get_review( $review_id );

            if ( ! is_array( $review ) ) {
                echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Review not found.', 'bw' ) . '</p></div></div>';
                return;
            }
            ?>
            <div class="wrap bw-admin-root bw-admin-page bw-admin-page-review-edit">
                <div class="bw-admin-header">
                    <h1 class="bw-admin-title"><?php esc_html_e( 'Edit Review', 'bw' ); ?></h1>
                    <p class="bw-admin-subtitle"><?php esc_html_e( 'Edit stored review values and moderation state.', 'bw' ); ?></p>
                </div>

                <?php if ( isset( $_GET['saved'] ) ) : ?>
                    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Review saved.', 'bw' ); ?></p></div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field( 'bw_reviews_edit_save', 'bw_reviews_edit_nonce' ); ?>
                    <input type="hidden" name="bw_reviews_edit_submit" value="1" />
                    <input type="hidden" name="review_id" value="<?php echo esc_attr( $review_id ); ?>" />

                    <div class="bw-admin-action-bar">
                        <div class="bw-admin-action-meta"><?php echo esc_html( sprintf( __( 'Editing review #%d', 'bw' ), $review_id ) ); ?></div>
                        <div class="bw-admin-action-buttons">
                            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . BW_Reviews_Settings::LIST_PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Back to Reviews', 'bw' ); ?></a>
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Review', 'bw' ); ?></button>
                        </div>
                    </div>

                    <section class="bw-admin-card">
                        <table class="form-table bw-admin-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="reviewer_first_name"><?php esc_html_e( 'First name', 'bw' ); ?></label></th>
                                <td><input type="text" id="reviewer_first_name" name="reviewer_first_name" class="regular-text" value="<?php echo esc_attr( $review['reviewer_first_name'] ); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="reviewer_last_name"><?php esc_html_e( 'Last name', 'bw' ); ?></label></th>
                                <td><input type="text" id="reviewer_last_name" name="reviewer_last_name" class="regular-text" value="<?php echo esc_attr( $review['reviewer_last_name'] ); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="reviewer_email"><?php esc_html_e( 'Email', 'bw' ); ?></label></th>
                                <td><input type="email" id="reviewer_email" name="reviewer_email" class="regular-text" value="<?php echo esc_attr( $review['reviewer_email'] ); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="rating"><?php esc_html_e( 'Rating', 'bw' ); ?></label></th>
                                <td><input type="number" min="1" max="5" id="rating" name="rating" class="small-text" value="<?php echo esc_attr( $review['rating'] ); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="review_status"><?php esc_html_e( 'Status', 'bw' ); ?></label></th>
                                <td>
                                    <select id="review_status" name="review_status">
                                        <?php foreach ( BW_Reviews_Settings::get_statuses() as $status ) : ?>
                                            <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $review['status'], $status ); ?>>
                                                <?php echo esc_html( self::get_status_label( $status ) ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Flags', 'bw' ); ?></th>
                                <td>
                                    <label style="margin-right:16px;"><input type="checkbox" name="verified_purchase" value="1" <?php checked( $review['verified_purchase'], 1 ); ?> /> <?php esc_html_e( 'Verified purchase', 'bw' ); ?></label>
                                    <label><input type="checkbox" name="featured" value="1" <?php checked( $review['featured'], 1 ); ?> /> <?php esc_html_e( 'Featured', 'bw' ); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="content"><?php esc_html_e( 'Review content', 'bw' ); ?></label></th>
                                <td><textarea id="content" name="content" rows="8" class="large-text"><?php echo esc_textarea( $review['content'] ); ?></textarea></td>
                            </tr>
                        </table>
                    </section>
                </form>
            </div>
            <?php
        }
    }
}
