<?php
/**
 * Reviews settings authority.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Reviews_Settings' ) ) {
    class BW_Reviews_Settings {
        const GENERAL_OPTION    = 'bw_reviews_general_settings';
        const DISPLAY_OPTION    = 'bw_reviews_display_settings';
        const SUBMISSION_OPTION = 'bw_reviews_submission_settings';
        const MODERATION_OPTION = 'bw_reviews_moderation_settings';
        const EMAIL_OPTION      = 'bw_reviews_email_settings';
        const BREVO_OPTION      = 'bw_reviews_brevo_settings';
        const SCHEMA_OPTION     = 'bw_reviews_schema_version';
        const TAB_PAGE_SLUG     = 'bw-reviews-settings';
        const LIST_PAGE_SLUG    = 'bw-reviews';
        const EDIT_PAGE_SLUG    = 'bw-reviews-edit';
        const MODULE_VERSION    = '1.0.0';

        /**
         * Get all supported review statuses.
         *
         * @return array<int,string>
         */
        public static function get_statuses() {
            return [
                'pending_confirmation',
                'pending_moderation',
                'approved',
                'rejected',
                'trash',
            ];
        }

        /**
         * Get admin tabs.
         *
         * @return array<string,string>
         */
        public static function get_tabs() {
            return [
                'general'    => __( 'General', 'bw' ),
                'display'    => __( 'Display', 'bw' ),
                'submission' => __( 'Submission', 'bw' ),
                'moderation' => __( 'Moderation', 'bw' ),
                'emails'     => __( 'Emails', 'bw' ),
                'brevo'      => __( 'Brevo', 'bw' ),
            ];
        }

        /**
         * Normalize a requested tab.
         *
         * @param string $tab Requested tab.
         *
         * @return string
         */
        public static function normalize_tab( $tab ) {
            $tab = sanitize_key( (string) $tab );
            $tabs = self::get_tabs();

            return isset( $tabs[ $tab ] ) ? $tab : 'general';
        }

        /**
         * Defaults for General settings.
         *
         * @return array<string,mixed>
         */
        public static function get_general_defaults() {
            return [
                'enabled'         => 0,
                'admin_page_size' => 20,
            ];
        }

        /**
         * Defaults for Display settings.
         *
         * @return array<string,mixed>
         */
        public static function get_display_defaults() {
            return [
                'initial_visible_count' => 6,
                'load_more_count'       => 6,
                'show_rating_breakdown' => 1,
                'show_dates'            => 1,
                'show_verified_badge'   => 1,
                'fallback_to_global_reviews_when_empty' => 0,
            ];
        }

        /**
         * Defaults for Submission settings.
         *
         * @return array<string,mixed>
         */
        public static function get_submission_defaults() {
            return [
                'allow_guests'             => 1,
                'logged_in_only'           => 0,
                'verified_buyers_only'     => 0,
                'require_email_confirmation' => 1,
            ];
        }

        /**
         * Defaults for Moderation settings.
         *
         * @return array<string,mixed>
         */
        public static function get_moderation_defaults() {
            return [
                'require_moderation'          => 1,
                'allow_review_editing'        => 1,
                'editing_logged_in_owners_only' => 1,
                'edit_requires_approval_again' => 1,
            ];
        }

        /**
         * Defaults for Email settings.
         *
         * @return array<string,mixed>
         */
        public static function get_email_defaults() {
            return [
                'confirmation_subject'        => __( 'Confirm your review', 'bw' ),
                'confirmation_heading'        => __( 'Confirm your review', 'bw' ),
                'confirmation_body'           => __( 'Hello {reviewer_name}, please confirm your review for {product_name} using the button below.', 'bw' ),
                'confirmation_button_label'   => __( 'Confirm review', 'bw' ),
                'confirmation_success_notice' => __( 'Your review has been confirmed.', 'bw' ),
                'confirmation_invalid_notice' => __( 'The review confirmation link is invalid.', 'bw' ),
                'confirmation_expired_notice' => __( 'The review confirmation link has expired.', 'bw' ),
                'confirmation_error_page_url' => '',
            ];
        }

        /**
         * Defaults for Brevo settings.
         *
         * @return array<string,mixed>
         */
        public static function get_brevo_defaults() {
            return [
                'enabled'                  => 0,
                'sync_on_submission'       => 0,
                'sync_on_confirmation'     => 1,
                'sync_on_approval'         => 1,
                'review_list_id'           => 0,
                'confirmed_review_list_id' => 0,
                'rewarded_review_list_id'  => 0,
            ];
        }

        /**
         * Get General settings.
         *
         * @return array<string,mixed>
         */
        public static function get_general_settings() {
            return self::merge_option( self::GENERAL_OPTION, self::get_general_defaults() );
        }

        /**
         * Get Display settings.
         *
         * @return array<string,mixed>
         */
        public static function get_display_settings() {
            return self::merge_option( self::DISPLAY_OPTION, self::get_display_defaults() );
        }

        /**
         * Get Submission settings.
         *
         * @return array<string,mixed>
         */
        public static function get_submission_settings() {
            return self::merge_option( self::SUBMISSION_OPTION, self::get_submission_defaults() );
        }

        /**
         * Get Moderation settings.
         *
         * @return array<string,mixed>
         */
        public static function get_moderation_settings() {
            return self::merge_option( self::MODERATION_OPTION, self::get_moderation_defaults() );
        }

        /**
         * Get Email settings.
         *
         * @return array<string,mixed>
         */
        public static function get_email_settings() {
            return self::merge_option( self::EMAIL_OPTION, self::get_email_defaults() );
        }

        /**
         * Get Brevo settings.
         *
         * @return array<string,mixed>
         */
        public static function get_brevo_settings() {
            return self::merge_option( self::BREVO_OPTION, self::get_brevo_defaults() );
        }

        /**
         * Whether the module is enabled.
         *
         * @return bool
         */
        public static function is_enabled() {
            $general = self::get_general_settings();

            return ! empty( $general['enabled'] );
        }

        /**
         * Get current schema version.
         *
         * @return string
         */
        public static function get_schema_version() {
            return (string) get_option( self::SCHEMA_OPTION, '' );
        }

        /**
         * Resolve global Brevo settings without duplicating credentials.
         *
         * @return array<string,mixed>
         */
        public static function get_global_brevo_settings() {
            if ( class_exists( 'BW_Mail_Marketing_Settings' ) ) {
                return BW_Mail_Marketing_Settings::get_general_settings();
            }

            return [
                'api_key'  => '',
                'api_base' => 'https://api.brevo.com/v3',
            ];
        }

        /**
         * Get normalized page size for admin list tables.
         *
         * @return int
         */
        public static function get_admin_page_size() {
            $general = self::get_general_settings();
            $size    = isset( $general['admin_page_size'] ) ? absint( $general['admin_page_size'] ) : 20;

            return max( 5, min( 100, $size ) );
        }

        /**
         * Merge option with defaults.
         *
         * @param string $option   Option name.
         * @param array  $defaults Defaults.
         *
         * @return array<string,mixed>
         */
        private static function merge_option( $option, $defaults ) {
            $saved = get_option( $option, [] );
            if ( ! is_array( $saved ) ) {
                $saved = [];
            }

            return array_replace( $defaults, $saved );
        }
    }
}
