<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'BW_SS_REWRITE_VERSION' ) ) {
    define( 'BW_SS_REWRITE_VERSION', '1' );
}

require_once __DIR__ . '/runtime/popup-config.php';
require_once __DIR__ . '/runtime/url-state.php';
require_once __DIR__ . '/../search-engine/engine/sort-config.php';
require_once __DIR__ . '/runtime/headless-product-grid-renderer.php';
require_once __DIR__ . '/runtime/search-results-page.php';
require_once __DIR__ . '/runtime/trending-source.php';
require_once __DIR__ . '/frontend/search-surface-template.php';
require_once __DIR__ . '/adapters/ajax-search-surface.php';

function bw_ss_register_search_results_route() {
    add_rewrite_tag( '%bw_search_results%', '1' );
    add_rewrite_rule( '^search/?$', 'index.php?bw_search_results=1', 'top' );
}

function bw_ss_register_search_results_query_var( $vars ) {
    $vars   = is_array( $vars ) ? $vars : [];
    $vars[] = 'bw_search_results';

    return array_values( array_unique( $vars ) );
}

function bw_ss_flush_search_results_rewrite_rules() {
    bw_ss_register_search_results_route();
    flush_rewrite_rules( false );
    update_option( 'bw_ss_rewrite_rules_version', BW_SS_REWRITE_VERSION, false );
}

function bw_ss_maybe_flush_search_results_rewrite_rules() {
    $version = (string) get_option( 'bw_ss_rewrite_rules_version', '' );

    if ( BW_SS_REWRITE_VERSION === $version ) {
        return;
    }

    bw_ss_flush_search_results_rewrite_rules();
}

function bw_ss_on_plugin_activation() {
    bw_ss_flush_search_results_rewrite_rules();
}

add_action( 'init', 'bw_ss_register_search_results_route', 9 );
add_filter( 'query_vars', 'bw_ss_register_search_results_query_var' );
add_action( 'wp_enqueue_scripts', 'bw_ss_maybe_enqueue_search_results_assets' );
add_action( 'wp_enqueue_scripts', 'bw_ss_enqueue_frontend_assets', 25 );
add_action( 'template_redirect', 'bw_ss_maybe_render_search_results_page', 9 );
add_filter( 'redirect_canonical', 'bw_ss_disable_canonical_redirect_for_results_route', 10, 2 );
add_filter( 'pre_get_document_title', 'bw_ss_filter_search_results_document_title', 20 );
add_filter( 'body_class', 'bw_ss_filter_search_results_body_class' );
add_action( 'wp_ajax_bw_ss_overlay_payload', 'bw_ss_ajax_overlay_payload' );
add_action( 'wp_ajax_nopriv_bw_ss_overlay_payload', 'bw_ss_ajax_overlay_payload' );
add_action( 'init', 'bw_ss_maybe_flush_search_results_rewrite_rules', 20 );
