<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/runtime/url-state.php';
require_once __DIR__ . '/runtime/headless-product-grid-renderer.php';
require_once __DIR__ . '/runtime/search-results-page.php';

add_action( 'wp_enqueue_scripts', 'bw_ss_maybe_enqueue_search_results_assets' );
add_action( 'template_redirect', 'bw_ss_maybe_render_search_results_page', 9 );
add_filter( 'redirect_canonical', 'bw_ss_disable_canonical_redirect_for_results_route', 10, 2 );
add_filter( 'pre_get_document_title', 'bw_ss_filter_search_results_document_title', 20 );
add_filter( 'body_class', 'bw_ss_filter_search_results_body_class' );
