<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handler AJAX per la ricerca live dei prodotti
 * 
 * Migrated from blackwork-core-plugin.php to allow standalone header usage.
 */

add_action('wp_ajax_bw_live_search_products', 'bw_header_live_search_products');
add_action('wp_ajax_nopriv_bw_live_search_products', 'bw_header_live_search_products');

function bw_header_live_search_safe_empty($message = '')
{
    $message = sanitize_text_field((string) $message);

    wp_send_json_success([
        'products' => [],
        'message' => $message,
    ]);
}

function bw_header_live_search_get_cache_ttl()
{
    return 3 * MINUTE_IN_SECONDS;
}

function bw_header_live_search_get_cache_generation()
{
    return (int) get_option('bw_header_live_search_cache_gen', 0);
}

function bw_header_live_search_bump_cache_generation()
{
    update_option('bw_header_live_search_cache_gen', bw_header_live_search_get_cache_generation() + 1, false);
}

function bw_header_live_search_maybe_invalidate_cache($post_id)
{
    $post_id = absint($post_id);

    if ($post_id <= 0 || 'product' !== get_post_type($post_id)) {
        return;
    }

    bw_header_live_search_bump_cache_generation();
}

add_action('save_post_product', 'bw_header_live_search_maybe_invalidate_cache');
add_action('deleted_post', 'bw_header_live_search_maybe_invalidate_cache');

function bw_header_live_search_build_cache_key($search_term, $categories, $product_type)
{
    $payload = [
        'schema' => 'v1',
        'gen' => bw_header_live_search_get_cache_generation(),
        'term' => bw_header_live_search_normalize_term($search_term),
        'categories' => bw_header_live_search_normalize_categories($categories),
        'product_type' => sanitize_key((string) $product_type),
    ];
    $payload_json = wp_json_encode($payload);

    if (!is_string($payload_json) || '' === $payload_json) {
        $payload_json = serialize($payload);
    }

    return 'bw_hdr_search_' . hash('sha256', $payload_json);
}

function bw_header_live_search_get_cached_response($cache_key)
{
    $cached = get_transient($cache_key);
    return is_array($cached) ? $cached : false;
}

function bw_header_live_search_store_cached_response($cache_key, $payload)
{
    if (!is_array($payload)) {
        return false;
    }

    return set_transient($cache_key, $payload, bw_header_live_search_get_cache_ttl());
}

function bw_header_live_search_send_throttled_response()
{
    wp_send_json_success([
        'products' => [],
        'message' => '',
        'throttled' => true,
    ]);
}

function bw_header_live_search_normalize_term($raw_term)
{
    if (!is_scalar($raw_term) && null !== $raw_term) {
        return '';
    }

    $term = sanitize_text_field(wp_unslash((string) $raw_term));
    return trim($term);
}

function bw_header_live_search_normalize_categories($raw_categories)
{
    if (!is_array($raw_categories)) {
        return [];
    }

    $categories = [];
    foreach ($raw_categories as $category) {
        if (!is_scalar($category) && null !== $category) {
            continue;
        }

        $slug = sanitize_title(wp_unslash((string) $category));
        if ('' === $slug) {
            continue;
        }

        $categories[] = $slug;
    }

    return array_values(array_unique($categories));
}

function bw_header_live_search_build_visibility_tax_query($categories, $product_type)
{
    $tax_query = [];

    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $categories,
        ];
    }

    if ('' !== $product_type) {
        $tax_query[] = [
            'taxonomy' => 'product_type',
            'field' => 'slug',
            'terms' => [$product_type],
        ];
    }

    // Enforce WooCommerce catalog visibility constraints.
    if (function_exists('wc_get_product_visibility_term_ids')) {
        $visibility_term_ids = wc_get_product_visibility_term_ids();
        $excluded_terms = [];

        if (!empty($visibility_term_ids['exclude-from-search'])) {
            $excluded_terms[] = (int) $visibility_term_ids['exclude-from-search'];
        }

        $excluded_terms = array_values(array_unique(array_filter(array_map('absint', $excluded_terms))));
        if (!empty($excluded_terms)) {
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field' => 'term_taxonomy_id',
                'terms' => $excluded_terms,
                'operator' => 'NOT IN',
            ];
        }
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    return $tax_query;
}

function bw_header_live_search_build_products_payload($product_ids)
{
    $product_ids = array_values(array_filter(array_map('absint', (array) $product_ids)));

    if (empty($product_ids)) {
        return [];
    }

    $products = wc_get_products([
        'include' => $product_ids,
        'limit' => count($product_ids),
        'status' => 'publish',
    ]);

    if (empty($products)) {
        return [];
    }

    $products_by_id = [];

    foreach ($products as $product) {
        if (!$product instanceof WC_Product) {
            continue;
        }

        $product_id = $product->get_id();
        if ($product_id <= 0) {
            continue;
        }

        $products_by_id[$product_id] = $product;
    }

    $payload = [];

    foreach ($product_ids as $product_id) {
        if (!isset($products_by_id[$product_id])) {
            continue;
        }

        $product = $products_by_id[$product_id];
        $image_id = $product->get_image_id();
        $image_url = '';

        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
        }

        if (!$image_url) {
            $image_url = wc_placeholder_img_src('medium');
        }

        $payload[] = [
            'id' => $product_id,
            'title' => get_the_title($product_id),
            'price_html' => $product->get_price_html(),
            'image_url' => $image_url,
            'permalink' => get_permalink($product_id),
        ];
    }

    return $payload;
}

function bw_header_live_search_products()
{
    $nonce_valid = check_ajax_referer('bw_search_nonce', 'nonce', false);
    if (false === $nonce_valid) {
        bw_header_live_search_safe_empty();
        return;
    }

    $search_term = isset($_POST['search_term']) ? bw_header_live_search_normalize_term($_POST['search_term']) : '';
    $search_length = function_exists('mb_strlen') ? mb_strlen($search_term) : strlen($search_term);
    if ('' === $search_term || $search_length < 2) {
        bw_header_live_search_safe_empty();
        return;
    }

    if (function_exists('bw_fpw_is_throttled_request') && bw_fpw_is_throttled_request('bw_header_live_search')) {
        bw_header_live_search_send_throttled_response();
        return;
    }

    $categories = isset($_POST['categories']) ? bw_header_live_search_normalize_categories($_POST['categories']) : [];

    $product_type = '';
    if (isset($_POST['product_type'])) {
        $product_type_raw = bw_header_live_search_normalize_term($_POST['product_type']);
        // Only standard WooCommerce types are accepted here. Custom plugin types
        // (digitalassets, books, prints) are not exposed via the search UI and
        // therefore intentionally excluded. To add them, extend this whitelist.
        if ('' !== $product_type_raw && in_array($product_type_raw, ['simple', 'variable', 'grouped', 'external'], true)) {
            $product_type = $product_type_raw;
        } elseif ('' !== $product_type_raw) {
            // Unknown type passed from an untrusted caller — return empty, not an error.
            bw_header_live_search_safe_empty();
            return;
        }
    }

    $cache_key = bw_header_live_search_build_cache_key($search_term, $categories, $product_type);
    $cached_response = bw_header_live_search_get_cached_response($cache_key);

    if (is_array($cached_response)) {
        wp_send_json_success($cached_response);
        return;
    }

    $args = [
        'post_type' => 'product',
        'posts_per_page' => 10,
        'post_status' => 'publish',
        's' => $search_term,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'cache_results' => false,
        'fields' => 'ids',
    ];

    $tax_query = bw_header_live_search_build_visibility_tax_query($categories, $product_type);
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);
    $products = bw_header_live_search_build_products_payload($query->posts);
    $response_payload = [
        'products' => $products,
        'message' => empty($products) ? __('Nessun prodotto trovato', 'bw') : '',
    ];

    bw_header_live_search_store_cached_response($cache_key, $response_payload);

    wp_send_json_success($response_payload);
}
