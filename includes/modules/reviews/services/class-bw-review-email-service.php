<?php
/**
 * Review email delivery service.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Review_Email_Service' ) ) {
    class BW_Review_Email_Service {
        /**
         * Send confirmation email.
         *
         * @param array<string,mixed> $review    Review row.
         * @param string              $raw_token Raw token.
         *
         * @return bool
         */
        public function send_confirmation_email( $review, $raw_token ) {
            if ( ! is_array( $review ) || '' === (string) $raw_token ) {
                return false;
            }

            $to = isset( $review['reviewer_email'] ) ? sanitize_email( (string) $review['reviewer_email'] ) : '';
            if ( '' === $to ) {
                return false;
            }

            $settings = BW_Reviews_Settings::get_email_settings();
            $product_title = get_the_title( absint( $review['product_id'] ) );
            $base_url = get_permalink( absint( $review['product_id'] ) );
            if ( ! $base_url ) {
                $base_url = home_url( '/' );
            }

            $confirm_url = add_query_arg(
                [
                    'bw_review_confirm' => absint( $review['id'] ),
                    'bw_review_token'   => (string) $raw_token,
                ],
                $base_url
            );

            $tokens = [
                '{reviewer_name}'    => isset( $review['reviewer_display_name'] ) ? (string) $review['reviewer_display_name'] : '',
                '{product_name}'     => (string) $product_title,
                '{confirmation_url}' => esc_url( $confirm_url ),
            ];

            $subject = strtr( (string) $settings['confirmation_subject'], $tokens );
            $heading = strtr( (string) $settings['confirmation_heading'], $tokens );
            $body    = strtr( (string) $settings['confirmation_body'], $tokens );
            $button  = (string) $settings['confirmation_button_label'];

            $message  = '<html><body>';
            $message .= '<h2>' . wp_kses_post( $heading ) . '</h2>';
            $message .= '<p>' . wp_kses_post( $body ) . '</p>';
            $message .= '<p><a href="' . esc_url( $confirm_url ) . '">' . esc_html( $button ) . '</a></p>';
            $message .= '</body></html>';

            add_filter(
                'wp_mail_content_type',
                [ $this, 'set_html_content_type' ]
            );

            $sent = wp_mail( $to, $subject, $message );

            remove_filter(
                'wp_mail_content_type',
                [ $this, 'set_html_content_type' ]
            );

            return (bool) $sent;
        }

        /**
         * Mail content type callback.
         *
         * @return string
         */
        public function set_html_content_type() {
            return 'text/html';
        }
    }
}
