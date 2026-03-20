<?php
/**
 * Verified purchase checks for reviews.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Review_Verified_Purchase_Service' ) ) {
    class BW_Review_Verified_Purchase_Service {
        /**
         * Check if identity purchased a product.
         *
         * @param int    $product_id Product ID.
         * @param string $email      Normalized email.
         * @param int    $user_id    User ID.
         *
         * @return array<string,mixed>
         */
        public function check_eligibility( $product_id, $email, $user_id = 0 ) {
            if ( ! function_exists( 'wc_get_orders' ) ) {
                return [
                    'eligible' => false,
                    'order_id' => 0,
                ];
            }

            $product_id = absint( $product_id );
            $email      = sanitize_email( (string) $email );
            $user_id    = absint( $user_id );

            if ( $product_id <= 0 || '' === $email ) {
                return [
                    'eligible' => false,
                    'order_id' => 0,
                ];
            }

            $statuses = [ 'wc-processing', 'wc-completed' ];
            $queries  = [];

            if ( $user_id > 0 ) {
                $queries[] = [
                    'customer_id' => $user_id,
                ];
            }

            $queries[] = [
                'billing_email' => $email,
            ];

            foreach ( $queries as $query_args ) {
                $args = array_merge(
                    [
                        'status' => $statuses,
                        'limit'  => 50,
                        'return' => 'objects',
                        'orderby' => 'date',
                        'order'   => 'DESC',
                    ],
                    $query_args
                );

                $orders = wc_get_orders( $args );
                if ( empty( $orders ) ) {
                    continue;
                }

                foreach ( $orders as $order ) {
                    if ( ! $order instanceof WC_Order ) {
                        continue;
                    }

                    foreach ( $order->get_items() as $item ) {
                        if ( ! $item instanceof WC_Order_Item_Product ) {
                            continue;
                        }

                        $ordered_product_id   = absint( $item->get_product_id() );
                        $ordered_variation_id = absint( $item->get_variation_id() );

                        if ( $ordered_product_id === $product_id || $ordered_variation_id === $product_id ) {
                            return [
                                'eligible' => true,
                                'order_id' => absint( $order->get_id() ),
                            ];
                        }
                    }
                }
            }

            return [
                'eligible' => false,
                'order_id' => 0,
            ];
        }
    }
}
