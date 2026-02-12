<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom header reuses the existing AJAX contract:
 * action: bw_live_search_products
 * nonce: bw_search_nonce
 *
 * Handler is intentionally kept in bw-main-elementor-widgets.php
 * to avoid breaking existing Elementor widgets.
 */
