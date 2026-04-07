<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/context/context-registry.php';
require_once __DIR__ . '/request/request-normalizer.php';
require_once __DIR__ . '/cache/cache-service.php';
require_once __DIR__ . '/middleware/rate-limiter.php';
require_once __DIR__ . '/engine/query-planner.php';
require_once __DIR__ . '/engine/candidate-resolver.php';
require_once __DIR__ . '/engine/text-matcher.php';
require_once __DIR__ . '/index/year-index-service.php';
require_once __DIR__ . '/index/advanced-filter-index-service.php';
require_once __DIR__ . '/engine/advanced-filter-engine.php';
require_once __DIR__ . '/engine/facet-builder.php';
require_once __DIR__ . '/engine/search-engine-core.php';
require_once __DIR__ . '/cache/invalidation-service.php';
require_once __DIR__ . '/adapters/product-grid/product-grid-adapter.php';

add_action('wp_ajax_bw_fpw_filter_posts', 'bw_fpw_filter_posts');
add_action('wp_ajax_nopriv_bw_fpw_filter_posts', 'bw_fpw_filter_posts');
add_action('wp_ajax_bw_fpw_get_subcategories', 'bw_fpw_get_subcategories');
add_action('wp_ajax_nopriv_bw_fpw_get_subcategories', 'bw_fpw_get_subcategories');
add_action('wp_ajax_bw_fpw_get_tags', 'bw_fpw_get_tags');
add_action('wp_ajax_nopriv_bw_fpw_get_tags', 'bw_fpw_get_tags');
add_action('wp_ajax_bw_fpw_refresh_nonce', 'bw_fpw_ajax_refresh_nonce');
add_action('wp_ajax_nopriv_bw_fpw_refresh_nonce', 'bw_fpw_ajax_refresh_nonce');

add_action('save_post', 'bw_fpw_clear_grid_transients');
add_action('added_post_meta', 'bw_fpw_handle_product_filter_meta_change', 10, 4);
add_action('updated_post_meta', 'bw_fpw_handle_product_filter_meta_change', 10, 4);
add_action('deleted_post_meta', 'bw_fpw_handle_product_filter_meta_change', 10, 4);
add_action('set_object_terms', 'bw_fpw_handle_product_filter_term_change', 10, 6);
add_action('transition_post_status', 'bw_fpw_handle_product_filter_status_change', 10, 3);
add_action('bw_fpw_async_rebuild_advanced_filter_index', 'bw_fpw_async_rebuild_advanced_filter_index_callback');
