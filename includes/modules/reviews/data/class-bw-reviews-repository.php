<?php
/**
 * Reviews repository.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Reviews_Repository' ) ) {
    class BW_Reviews_Repository {
        /**
         * Get reviews table name.
         *
         * @return string
         */
        public function get_table_name() {
            return BW_Reviews_Installer::get_table_name();
        }

        /**
         * Insert a review row.
         *
         * @param array<string,mixed> $data Insert data.
         *
         * @return int
         */
        public function insert_review( $data ) {
            global $wpdb;

            $result = $wpdb->insert( $this->get_table_name(), $data );

            return false === $result ? 0 : absint( $wpdb->insert_id );
        }

        /**
         * Update a review row.
         *
         * @param int                 $review_id Review ID.
         * @param array<string,mixed> $data      Update data.
         *
         * @return bool
         */
        public function update_review( $review_id, $data ) {
            global $wpdb;

            $review_id = absint( $review_id );
            if ( $review_id <= 0 || empty( $data ) ) {
                return false;
            }

            $result = $wpdb->update(
                $this->get_table_name(),
                $data,
                [ 'id' => $review_id ]
            );

            return false !== $result;
        }

        /**
         * Delete a review permanently.
         *
         * @param int $review_id Review ID.
         *
         * @return bool
         */
        public function delete_review( $review_id ) {
            global $wpdb;

            $review_id = absint( $review_id );
            if ( $review_id <= 0 ) {
                return false;
            }

            $result = $wpdb->delete(
                $this->get_table_name(),
                [ 'id' => $review_id ],
                [ '%d' ]
            );

            return false !== $result;
        }

        /**
         * Get a review by ID.
         *
         * @param int $review_id Review ID.
         *
         * @return array<string,mixed>|null
         */
        public function get_review( $review_id ) {
            global $wpdb;

            $review_id = absint( $review_id );
            if ( $review_id <= 0 ) {
                return null;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->get_table_name()} WHERE id = %d",
                    $review_id
                ),
                ARRAY_A
            );

            return is_array( $row ) ? $row : null;
        }

        /**
         * Get a review by confirmation token hash.
         *
         * @param string $token_hash Token hash.
         *
         * @return array<string,mixed>|null
         */
        public function get_review_by_confirmation_token_hash( $token_hash ) {
            global $wpdb;

            $token_hash = strtolower( sanitize_text_field( (string) $token_hash ) );
            if ( '' === $token_hash ) {
                return null;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->get_table_name()} WHERE confirmation_token_hash = %s",
                    $token_hash
                ),
                ARRAY_A
            );

            return is_array( $row ) ? $row : null;
        }

        /**
         * Find review by product and email hash.
         *
         * @param int    $product_id  Product ID.
         * @param string $email_hash  Email hash.
         *
         * @return array<string,mixed>|null
         */
        public function find_by_product_and_email_hash( $product_id, $email_hash ) {
            global $wpdb;

            $product_id = absint( $product_id );
            $email_hash = strtolower( sanitize_text_field( (string) $email_hash ) );

            if ( $product_id <= 0 || '' === $email_hash ) {
                return null;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->get_table_name()} WHERE product_id = %d AND reviewer_email_hash = %s LIMIT 1",
                    $product_id,
                    $email_hash
                ),
                ARRAY_A
            );

            return is_array( $row ) ? $row : null;
        }

        /**
         * Find review by product and user.
         *
         * @param int $product_id Product ID.
         * @param int $user_id    User ID.
         *
         * @return array<string,mixed>|null
         */
        public function find_by_product_and_user_id( $product_id, $user_id ) {
            global $wpdb;

            $product_id = absint( $product_id );
            $user_id    = absint( $user_id );

            if ( $product_id <= 0 || $user_id <= 0 ) {
                return null;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->get_table_name()} WHERE product_id = %d AND user_id = %d LIMIT 1",
                    $product_id,
                    $user_id
                ),
                ARRAY_A
            );

            return is_array( $row ) ? $row : null;
        }

        /**
         * Query reviews for the admin list table.
         *
         * @param array<string,mixed> $args Query arguments.
         *
         * @return array<int,array<string,mixed>>
         */
        public function get_admin_reviews( $args = [] ) {
            global $wpdb;

            $defaults = [
                'status'      => '',
                'featured'    => '',
                'verified'    => '',
                'rating'      => 0,
                'product_id'  => 0,
                'search'      => '',
                'per_page'    => BW_Reviews_Settings::get_admin_page_size(),
                'paged'       => 1,
                'orderby'     => 'created_at',
                'order'       => 'DESC',
            ];

            $args  = array_replace( $defaults, $args );
            $table = $this->get_table_name();
            $posts = $wpdb->posts;

            $where  = [];
            $params = [];

            if ( '' !== $args['status'] ) {
                if ( 'featured' === $args['status'] ) {
                    $where[] = 'r.featured = 1';
                    $where[] = "r.status <> 'trash'";
                } else {
                    $where[] = 'r.status = %s';
                    $params[] = sanitize_key( $args['status'] );
                }
            }

            $product_id = absint( $args['product_id'] );
            if ( $product_id > 0 ) {
                $where[] = 'r.product_id = %d';
                $params[] = $product_id;
            }

            $rating = absint( $args['rating'] );
            if ( $rating > 0 ) {
                $where[] = 'r.rating = %d';
                $params[] = $rating;
            }

            if ( '' !== $args['verified'] ) {
                $where[] = 'r.verified_purchase = %d';
                $params[] = '1' === (string) $args['verified'] ? 1 : 0;
            }

            if ( '' !== $args['featured'] ) {
                $where[] = 'r.featured = %d';
                $params[] = '1' === (string) $args['featured'] ? 1 : 0;
            }

            $search = trim( (string) $args['search'] );
            if ( '' !== $search ) {
                $like    = '%' . $wpdb->esc_like( $search ) . '%';
                $where[] = '(r.reviewer_display_name LIKE %s OR r.reviewer_email LIKE %s OR r.content LIKE %s OR p.post_title LIKE %s)';
                array_push( $params, $like, $like, $like, $like );
            }

            $where_sql = empty( $where ) ? '' : 'WHERE ' . implode( ' AND ', $where );
            $orderby   = in_array( $args['orderby'], [ 'created_at', 'rating', 'reviewer_display_name', 'status', 'featured' ], true ) ? $args['orderby'] : 'created_at';
            $order     = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
            $per_page  = max( 1, absint( $args['per_page'] ) );
            $paged     = max( 1, absint( $args['paged'] ) );
            $offset    = ( $paged - 1 ) * $per_page;

            $sql = "SELECT r.*, p.post_title AS product_title
                FROM {$table} r
                LEFT JOIN {$posts} p ON p.ID = r.product_id
                {$where_sql}
                ORDER BY r.{$orderby} {$order}
                LIMIT %d OFFSET %d";

            $params[] = $per_page;
            $params[] = $offset;

            $prepared = $wpdb->prepare( $sql, $params );
            $rows     = $wpdb->get_results( $prepared, ARRAY_A );

            return is_array( $rows ) ? $rows : [];
        }

        /**
         * Count reviews for admin list table.
         *
         * @param array<string,mixed> $args Query arguments.
         *
         * @return int
         */
        public function count_admin_reviews( $args = [] ) {
            global $wpdb;

            $args['per_page'] = 1;
            $args['paged']    = 1;

            $defaults = [
                'status'     => '',
                'featured'   => '',
                'verified'   => '',
                'rating'     => 0,
                'product_id' => 0,
                'search'     => '',
            ];

            $args  = array_replace( $defaults, $args );
            $table = $this->get_table_name();
            $posts = $wpdb->posts;

            $where  = [];
            $params = [];

            if ( '' !== $args['status'] ) {
                if ( 'featured' === $args['status'] ) {
                    $where[] = 'r.featured = 1';
                    $where[] = "r.status <> 'trash'";
                } else {
                    $where[] = 'r.status = %s';
                    $params[] = sanitize_key( $args['status'] );
                }
            }

            $product_id = absint( $args['product_id'] );
            if ( $product_id > 0 ) {
                $where[] = 'r.product_id = %d';
                $params[] = $product_id;
            }

            $rating = absint( $args['rating'] );
            if ( $rating > 0 ) {
                $where[] = 'r.rating = %d';
                $params[] = $rating;
            }

            if ( '' !== $args['verified'] ) {
                $where[] = 'r.verified_purchase = %d';
                $params[] = '1' === (string) $args['verified'] ? 1 : 0;
            }

            if ( '' !== $args['featured'] ) {
                $where[] = 'r.featured = %d';
                $params[] = '1' === (string) $args['featured'] ? 1 : 0;
            }

            $search = trim( (string) $args['search'] );
            if ( '' !== $search ) {
                $like    = '%' . $wpdb->esc_like( $search ) . '%';
                $where[] = '(r.reviewer_display_name LIKE %s OR r.reviewer_email LIKE %s OR r.content LIKE %s OR p.post_title LIKE %s)';
                array_push( $params, $like, $like, $like, $like );
            }

            $where_sql = empty( $where ) ? '' : 'WHERE ' . implode( ' AND ', $where );
            $sql       = "SELECT COUNT(*) FROM {$table} r LEFT JOIN {$posts} p ON p.ID = r.product_id {$where_sql}";
            $prepared  = empty( $params ) ? $sql : $wpdb->prepare( $sql, $params );

            return absint( $wpdb->get_var( $prepared ) );
        }

        /**
         * Get counts for list-table views.
         *
         * @return array<string,int>
         */
        public function get_view_counts() {
            global $wpdb;

            $table = $this->get_table_name();
            $rows  = $wpdb->get_results(
                "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status",
                ARRAY_A
            );

            $counts = [
                'all'                  => 0,
                'pending_confirmation' => 0,
                'pending_moderation'   => 0,
                'approved'             => 0,
                'rejected'             => 0,
                'trash'                => 0,
                'featured'             => 0,
            ];

            if ( is_array( $rows ) ) {
                foreach ( $rows as $row ) {
                    $status = isset( $row['status'] ) ? sanitize_key( $row['status'] ) : '';
                    $total  = isset( $row['total'] ) ? absint( $row['total'] ) : 0;

                    if ( isset( $counts[ $status ] ) ) {
                        $counts[ $status ] = $total;
                    }

                    if ( 'trash' !== $status ) {
                        $counts['all'] += $total;
                    }
                }
            }

            $counts['featured'] = absint(
                $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$table} WHERE featured = 1 AND status <> 'trash'"
                )
            );

            return $counts;
        }

        /**
         * Get approved reviews for a product.
         *
         * @param int    $product_id Product ID.
         * @param string $sort       Sort mode.
         * @param int    $offset     Offset.
         * @param int    $limit      Limit.
         *
         * @return array<int,array<string,mixed>>
         */
        public function get_product_reviews( $product_id, $sort = 'featured', $offset = 0, $limit = 6 ) {
            global $wpdb;

            $product_id = absint( $product_id );
            if ( $product_id <= 0 ) {
                return [];
            }

            $sort_map = [
                'featured'       => 'featured DESC, created_at DESC',
                'newest'         => 'created_at DESC',
                'highest_rating' => 'rating DESC, created_at DESC',
                'lowest_rating'  => 'rating ASC, created_at DESC',
            ];
            $order_by = isset( $sort_map[ $sort ] ) ? $sort_map[ $sort ] : $sort_map['featured'];

            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->get_table_name()}
                WHERE product_id = %d AND status = 'approved'
                ORDER BY {$order_by}
                LIMIT %d OFFSET %d",
                $product_id,
                max( 1, absint( $limit ) ),
                max( 0, absint( $offset ) )
            );

            $rows = $wpdb->get_results( $sql, ARRAY_A );

            return is_array( $rows ) ? $rows : [];
        }

        /**
         * Count approved reviews for a product.
         *
         * @param int $product_id Product ID.
         *
         * @return int
         */
        public function count_product_reviews( $product_id ) {
            global $wpdb;

            $product_id = absint( $product_id );
            if ( $product_id <= 0 ) {
                return 0;
            }

            return absint(
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE product_id = %d AND status = 'approved'",
                        $product_id
                    )
                )
            );
        }

        /**
         * Build rating summary for a product.
         *
         * @param int $product_id Product ID.
         *
         * @return array<string,mixed>
         */
        public function get_product_summary( $product_id ) {
            global $wpdb;

            $product_id = absint( $product_id );
            if ( $product_id <= 0 ) {
                return [
                    'average_rating' => 0,
                    'approved_count' => 0,
                    'breakdown'      => [],
                ];
            }

            $table  = $this->get_table_name();
            $avg    = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT AVG(rating) FROM {$table} WHERE product_id = %d AND status = 'approved'",
                    $product_id
                )
            );
            $counts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT rating, COUNT(*) AS total FROM {$table} WHERE product_id = %d AND status = 'approved' GROUP BY rating",
                    $product_id
                ),
                ARRAY_A
            );

            $total     = $this->count_product_reviews( $product_id );
            $breakdown = [];

            for ( $rating = 5; $rating >= 1; $rating-- ) {
                $count = 0;
                if ( is_array( $counts ) ) {
                    foreach ( $counts as $row ) {
                        if ( isset( $row['rating'] ) && absint( $row['rating'] ) === $rating ) {
                            $count = absint( $row['total'] );
                            break;
                        }
                    }
                }

                $breakdown[] = [
                    'rating'  => $rating,
                    'count'   => $count,
                    'percent' => $total > 0 ? round( ( $count / $total ) * 100, 1 ) : 0,
                ];
            }

            return [
                'average_rating' => $avg ? round( (float) $avg, 1 ) : 0,
                'approved_count' => $total,
                'breakdown'      => $breakdown,
            ];
        }
    }
}
