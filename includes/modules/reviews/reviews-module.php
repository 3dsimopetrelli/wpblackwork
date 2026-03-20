<?php
/**
 * Reviews module bootstrap.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/services/class-bw-reviews-settings.php';
require_once __DIR__ . '/data/class-bw-reviews-installer.php';
require_once __DIR__ . '/data/class-bw-reviews-repository.php';
require_once __DIR__ . '/services/class-bw-review-verified-purchase-service.php';
require_once __DIR__ . '/services/class-bw-review-brevo-sync-service.php';
require_once __DIR__ . '/services/class-bw-review-email-service.php';
require_once __DIR__ . '/services/class-bw-review-confirmation-service.php';
require_once __DIR__ . '/services/class-bw-review-moderation-service.php';
require_once __DIR__ . '/services/class-bw-review-submission-service.php';
require_once __DIR__ . '/frontend/class-bw-reviews-widget-renderer.php';
require_once __DIR__ . '/runtime/class-bw-reviews-runtime.php';

if ( is_admin() ) {
    require_once __DIR__ . '/admin/class-bw-reviews-list-table.php';
    require_once __DIR__ . '/admin/class-bw-reviews-admin.php';
}

BW_Reviews_Installer::init();
BW_Reviews_Runtime::init();

if ( is_admin() && class_exists( 'BW_Reviews_Admin' ) ) {
    BW_Reviews_Admin::init();
}
