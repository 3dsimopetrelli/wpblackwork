<?php
/**
 * Reviews admin list table.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'BW_Reviews_List_Table' ) ) {
    class BW_Reviews_List_Table extends WP_List_Table {
        /**
         * @var BW_Reviews_Repository
         */
        private $repository;

        /**
         * Constructor.
         */
        public function __construct() {
            parent::__construct(
                [
                    'singular' => 'bw_review',
                    'plural'   => 'bw_reviews',
                    'ajax'     => false,
                ]
            );

            $this->repository = new BW_Reviews_Repository();
        }

        /**
         * Prepare list-table items.
         */
        public function prepare_items() {
            $per_page = BW_Reviews_Settings::get_admin_page_size();
            $paged    = $this->get_pagenum();
            $view     = isset( $_REQUEST['review_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['review_status'] ) ) : '';

            $args = [
                'status'     => $view,
                'product_id' => isset( $_REQUEST['filter_product_id'] ) ? absint( wp_unslash( $_REQUEST['filter_product_id'] ) ) : 0,
                'rating'     => isset( $_REQUEST['filter_rating'] ) ? absint( wp_unslash( $_REQUEST['filter_rating'] ) ) : 0,
                'verified'   => isset( $_REQUEST['filter_verified'] ) ? sanitize_key( wp_unslash( $_REQUEST['filter_verified'] ) ) : '',
                'featured'   => isset( $_REQUEST['filter_featured'] ) ? sanitize_key( wp_unslash( $_REQUEST['filter_featured'] ) ) : '',
                'search'     => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
                'per_page'   => $per_page,
                'paged'      => $paged,
                'orderby'    => isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at',
                'order'      => isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'DESC',
            ];

            $this->items = $this->repository->get_admin_reviews( $args );

            $total_items = $this->repository->count_admin_reviews( $args );
            $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

            $this->set_pagination_args(
                [
                    'total_items' => $total_items,
                    'per_page'    => $per_page,
                    'total_pages' => ceil( $total_items / $per_page ),
                ]
            );
        }

        /**
         * Get columns.
         *
         * @return array<string,string>
         */
        public function get_columns() {
            return [
                'cb'        => '<input type="checkbox" />',
                'reviewer'  => __( 'Reviewer', 'bw' ),
                'email'     => __( 'Email', 'bw' ),
                'product'   => __( 'Product', 'bw' ),
                'rating'    => __( 'Rating', 'bw' ),
                'status'    => __( 'Status', 'bw' ),
                'verified'  => __( 'Verified', 'bw' ),
                'featured'  => __( 'Featured', 'bw' ),
                'date'      => __( 'Date', 'bw' ),
            ];
        }

        /**
         * Get sortable columns.
         *
         * @return array<string,array<int,mixed>>
         */
        protected function get_sortable_columns() {
            return [
                'reviewer' => [ 'reviewer_display_name', false ],
                'rating'   => [ 'rating', false ],
                'status'   => [ 'status', false ],
                'featured' => [ 'featured', false ],
                'date'     => [ 'created_at', true ],
            ];
        }

        /**
         * Get bulk actions.
         *
         * @return array<string,string>
         */
        protected function get_bulk_actions() {
            return [
                'approve'   => __( 'Approve', 'bw' ),
                'reject'    => __( 'Reject', 'bw' ),
                'feature'   => __( 'Feature', 'bw' ),
                'unfeature' => __( 'Unfeature', 'bw' ),
                'trash'     => __( 'Trash', 'bw' ),
                'delete'    => __( 'Delete permanently', 'bw' ),
            ];
        }

        /**
         * Checkbox column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_cb( $item ) {
            return sprintf(
                '<input type="checkbox" name="review_ids[]" value="%d" />',
                absint( $item['id'] )
            );
        }

        /**
         * Reviewer column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_reviewer( $item ) {
            $review_id    = absint( $item['id'] );
            $reviewer     = isset( $item['reviewer_display_name'] ) ? $item['reviewer_display_name'] : __( 'Anonymous', 'bw' );
            $content      = isset( $item['content'] ) ? wp_trim_words( wp_strip_all_tags( (string) $item['content'] ), 12 ) : '';
            $status       = isset( $item['status'] ) ? sanitize_key( (string) $item['status'] ) : '';
            $featured     = ! empty( $item['featured'] );
            $actions      = [];
            $base_url     = admin_url( 'admin.php?page=' . BW_Reviews_Settings::LIST_PAGE_SLUG );
            $edit_url     = admin_url( 'admin.php?page=' . BW_Reviews_Settings::EDIT_PAGE_SLUG . '&review_id=' . $review_id );

            if ( in_array( $status, [ 'pending_confirmation', 'pending_moderation', 'rejected' ], true ) ) {
                $actions['approve'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'approve', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html__( 'Approve', 'bw' )
                );
            }

            if ( ! in_array( $status, [ 'rejected', 'trash' ], true ) ) {
                $actions['reject'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'reject', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html__( 'Reject', 'bw' )
                );
            }

            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( $edit_url ),
                esc_html__( 'Edit', 'bw' )
            );

            if ( 'trash' === $status ) {
                $actions['restore'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'restore', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html__( 'Restore', 'bw' )
                );
                $actions['delete'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'delete', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html__( 'Delete permanently', 'bw' )
                );
            } else {
                $actions[ $featured ? 'unfeature' : 'feature' ] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => $featured ? 'unfeature' : 'feature', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html( $featured ? __( 'Unfeature', 'bw' ) : __( 'Feature', 'bw' ) )
                );
                $actions['trash'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'trash', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html__( 'Trash', 'bw' )
                );
            }

            if ( 'pending_confirmation' === $status ) {
                $actions['resend_confirmation'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'resend_confirmation', 'review_id' => $review_id ], $base_url ), 'bw_reviews_row_action_' . $review_id ) ),
                    esc_html__( 'Resend confirmation', 'bw' )
                );
            }

            return sprintf(
                '<strong>%1$s</strong><div class="row-actions">%2$s</div><div class="description">%3$s</div>',
                esc_html( $reviewer ),
                $this->row_actions( $actions, false ),
                esc_html( $content )
            );
        }

        /**
         * Email column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_email( $item ) {
            return esc_html( (string) $item['reviewer_email'] );
        }

        /**
         * Product column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_product( $item ) {
            $product_id = absint( $item['product_id'] );
            $title      = ! empty( $item['product_title'] ) ? (string) $item['product_title'] : __( '(deleted product)', 'bw' );

            if ( $product_id <= 0 ) {
                return esc_html( $title );
            }

            $url = get_edit_post_link( $product_id );

            if ( $url ) {
                return sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( $url ),
                    esc_html( $title )
                );
            }

            return esc_html( $title );
        }

        /**
         * Rating column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_rating( $item ) {
            return esc_html( sprintf( '%d / 5', absint( $item['rating'] ) ) );
        }

        /**
         * Status column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_status( $item ) {
            $status = isset( $item['status'] ) ? sanitize_key( (string) $item['status'] ) : '';
            $review_id = absint( $item['id'] );
            $base_url  = admin_url( 'admin.php?page=' . BW_Reviews_Settings::LIST_PAGE_SLUG );
            if ( 'approved' === $status ) {
                $action_url = wp_nonce_url(
                    add_query_arg(
                        [
                            'action'    => 'reject',
                            'review_id' => $review_id,
                        ],
                        $base_url
                    ),
                    'bw_reviews_row_action_' . $review_id
                );

                return sprintf(
                    '<a class="bw-reviews-admin-status-toggle is-approved" href="%1$s">%2$s</a>',
                    esc_url( $action_url ),
                    esc_html__( 'Approved', 'bw' )
                );
            }

            if ( 'rejected' === $status ) {
                $action_url = wp_nonce_url(
                    add_query_arg(
                        [
                            'action'    => 'approve',
                            'review_id' => $review_id,
                        ],
                        $base_url
                    ),
                    'bw_reviews_row_action_' . $review_id
                );

                return sprintf(
                    '<a class="bw-reviews-admin-status-toggle is-rejected" href="%1$s">%2$s</a>',
                    esc_url( $action_url ),
                    esc_html__( 'Reject', 'bw' )
                );
            }

            return sprintf(
                '<span class="bw-reviews-admin-status-label is-%1$s">%2$s</span>',
                esc_attr( $status ),
                esc_html( BW_Reviews_Admin::get_status_label( $status ) )
            );
        }

        /**
         * Verified column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_verified( $item ) {
            if ( ! empty( $item['verified_purchase'] ) ) {
                return '<span class="bw-reviews-admin-verified is-yes" aria-label="' . esc_attr__( 'Verified purchase', 'bw' ) . '" title="' . esc_attr__( 'Verified purchase', 'bw' ) . '">✓</span>';
            }

            return '<span class="bw-reviews-admin-verified is-no" aria-label="' . esc_attr__( 'Not verified', 'bw' ) . '" title="' . esc_attr__( 'Not verified', 'bw' ) . '">—</span>';
        }

        /**
         * Featured column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_featured( $item ) {
            if ( empty( $item['featured'] ) ) {
                return '&mdash;';
            }

            return '<span class="bw-reviews-admin-pin" aria-label="' . esc_attr__( 'Featured', 'bw' ) . '" title="' . esc_attr__( 'Featured', 'bw' ) . '">📌</span>';
        }

        /**
         * Date column.
         *
         * @param array<string,mixed> $item Review row.
         *
         * @return string
         */
        protected function column_date( $item ) {
            $date = isset( $item['created_at'] ) ? (string) $item['created_at'] : '';
            return '' !== $date ? esc_html( $date ) : '—';
        }

        /**
         * Default column renderer.
         *
         * @param array<string,mixed> $item        Review row.
         * @param string              $column_name Column name.
         *
         * @return string
         */
        protected function column_default( $item, $column_name ) {
            if ( isset( $item[ $column_name ] ) ) {
                return esc_html( (string) $item[ $column_name ] );
            }

            return '';
        }

        /**
         * Render admin views.
         *
         * @return array<string,string>
         */
        protected function get_views() {
            $current = isset( $_REQUEST['review_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['review_status'] ) ) : '';
            $counts  = $this->repository->get_view_counts();
            $base    = admin_url( 'admin.php?page=' . BW_Reviews_Settings::LIST_PAGE_SLUG );

            $views = [
                'all' => sprintf(
                    '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    esc_url( $base ),
                    '' === $current ? 'current' : '',
                    esc_html__( 'All', 'bw' ),
                    absint( $counts['all'] )
                ),
            ];

            foreach ( [
                'pending_confirmation' => __( 'Pending confirmation', 'bw' ),
                'pending_moderation'   => __( 'Pending moderation', 'bw' ),
                'approved'             => __( 'Approved', 'bw' ),
                'rejected'             => __( 'Rejected', 'bw' ),
                'trash'                => __( 'Trash', 'bw' ),
                'featured'             => __( 'Featured', 'bw' ),
            ] as $status => $label ) {
                $views[ $status ] = sprintf(
                    '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    esc_url( add_query_arg( 'review_status', $status, $base ) ),
                    $current === $status ? 'current' : '',
                    esc_html( $label ),
                    absint( $counts[ $status ] )
                );
            }

            return $views;
        }

        /**
         * Render filter controls.
         *
         * @param string $which Position.
         */
        protected function extra_tablenav( $which ) {
            if ( 'top' !== $which ) {
                return;
            }

            $selected_product  = isset( $_REQUEST['filter_product_id'] ) ? absint( wp_unslash( $_REQUEST['filter_product_id'] ) ) : 0;
            $selected_rating   = isset( $_REQUEST['filter_rating'] ) ? absint( wp_unslash( $_REQUEST['filter_rating'] ) ) : 0;
            $selected_verified = isset( $_REQUEST['filter_verified'] ) ? sanitize_key( wp_unslash( $_REQUEST['filter_verified'] ) ) : '';
            $selected_featured = isset( $_REQUEST['filter_featured'] ) ? sanitize_key( wp_unslash( $_REQUEST['filter_featured'] ) ) : '';
            $products          = get_posts(
                [
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => 200,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ]
            );
            ?>
            <div class="alignleft actions">
                <select name="filter_product_id">
                    <option value="0"><?php esc_html_e( 'All products', 'bw' ); ?></option>
                    <?php foreach ( $products as $product ) : ?>
                        <option value="<?php echo esc_attr( $product->ID ); ?>" <?php selected( $selected_product, $product->ID ); ?>>
                            <?php echo esc_html( get_the_title( $product ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_rating">
                    <option value="0"><?php esc_html_e( 'All ratings', 'bw' ); ?></option>
                    <?php for ( $rating = 5; $rating >= 1; $rating-- ) : ?>
                        <option value="<?php echo esc_attr( $rating ); ?>" <?php selected( $selected_rating, $rating ); ?>>
                            <?php echo esc_html( sprintf( __( '%d stars', 'bw' ), $rating ) ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="filter_verified">
                    <option value=""><?php esc_html_e( 'All verified states', 'bw' ); ?></option>
                    <option value="1" <?php selected( $selected_verified, '1' ); ?>><?php esc_html_e( 'Verified only', 'bw' ); ?></option>
                    <option value="0" <?php selected( $selected_verified, '0' ); ?>><?php esc_html_e( 'Not verified', 'bw' ); ?></option>
                </select>
                <select name="filter_featured">
                    <option value=""><?php esc_html_e( 'All featured states', 'bw' ); ?></option>
                    <option value="1" <?php selected( $selected_featured, '1' ); ?>><?php esc_html_e( 'Featured only', 'bw' ); ?></option>
                    <option value="0" <?php selected( $selected_featured, '0' ); ?>><?php esc_html_e( 'Not featured', 'bw' ); ?></option>
                </select>
                <?php submit_button( __( 'Filter', 'bw' ), '', 'filter_action', false ); ?>
            </div>
            <?php
        }
    }
}
