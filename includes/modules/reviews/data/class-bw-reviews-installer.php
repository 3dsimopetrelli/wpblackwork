<?php
/**
 * Reviews schema installer.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BW_Reviews_Installer' ) ) {
    class BW_Reviews_Installer {
        /**
         * Bootstrap installer checks.
         */
        public static function init() {
            add_action( 'init', [ __CLASS__, 'maybe_install' ], 12 );
        }

        /**
         * Ensure schema and default options exist.
         */
        public static function maybe_install() {
            if ( BW_Reviews_Settings::MODULE_VERSION === BW_Reviews_Settings::get_schema_version() ) {
                return;
            }

            self::install_schema();
            self::seed_default_options();
            update_option( BW_Reviews_Settings::SCHEMA_OPTION, BW_Reviews_Settings::MODULE_VERSION, false );
        }

        /**
         * Get reviews table name.
         *
         * @return string
         */
        public static function get_table_name() {
            global $wpdb;

            return $wpdb->prefix . 'bw_reviews';
        }

        /**
         * Install reviews schema.
         */
        private static function install_schema() {
            global $wpdb;

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $table_name      = self::get_table_name();
            $charset_collate = $wpdb->get_charset_collate();
            $sql             = "CREATE TABLE {$table_name} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                product_id BIGINT UNSIGNED NOT NULL,
                order_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NULL,
                reviewer_first_name VARCHAR(100) NOT NULL DEFAULT '',
                reviewer_last_name VARCHAR(100) NOT NULL DEFAULT '',
                reviewer_display_name VARCHAR(191) NOT NULL,
                reviewer_email VARCHAR(191) NOT NULL,
                reviewer_email_hash CHAR(64) NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                content LONGTEXT NOT NULL,
                status VARCHAR(32) NOT NULL,
                status_before_trash VARCHAR(32) NULL,
                featured TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                verified_purchase TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                confirmation_token_hash CHAR(64) NULL,
                confirmation_token_expires_at DATETIME NULL,
                confirmed_at DATETIME NULL,
                approved_at DATETIME NULL,
                rejected_at DATETIME NULL,
                edited_at DATETIME NULL,
                edit_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                brevo_sync_state VARCHAR(32) NOT NULL DEFAULT 'not_synced',
                brevo_last_synced_at DATETIME NULL,
                reward_state VARCHAR(32) NOT NULL DEFAULT 'none',
                reward_coupon_code VARCHAR(100) NULL,
                rewarded_at DATETIME NULL,
                source VARCHAR(32) NOT NULL DEFAULT 'product_page',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uniq_product_email (product_id, reviewer_email_hash),
                UNIQUE KEY uniq_confirmation_token (confirmation_token_hash),
                KEY idx_product_status_featured_date (product_id, status, featured, created_at),
                KEY idx_status_date (status, created_at),
                KEY idx_user_product (user_id, product_id),
                KEY idx_order_id (order_id),
                KEY idx_email_hash (reviewer_email_hash)
            ) {$charset_collate};";

            dbDelta( $sql );
        }

        /**
         * Seed default settings.
         */
        private static function seed_default_options() {
            add_option( BW_Reviews_Settings::GENERAL_OPTION, BW_Reviews_Settings::get_general_defaults() );
            add_option( BW_Reviews_Settings::DISPLAY_OPTION, BW_Reviews_Settings::get_display_defaults() );
            add_option( BW_Reviews_Settings::SUBMISSION_OPTION, BW_Reviews_Settings::get_submission_defaults() );
            add_option( BW_Reviews_Settings::MODERATION_OPTION, BW_Reviews_Settings::get_moderation_defaults() );
            add_option( BW_Reviews_Settings::EMAIL_OPTION, BW_Reviews_Settings::get_email_defaults() );
            add_option( BW_Reviews_Settings::BREVO_OPTION, BW_Reviews_Settings::get_brevo_defaults() );
        }
    }
}
