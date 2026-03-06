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

if (!function_exists('bw_header_live_search_safe_empty')) {
    function bw_header_live_search_safe_empty($message = '')
    {
        $message = sanitize_text_field((string) $message);

        wp_send_json_success([
            'products' => [],
            'results' => [],
            'message' => $message,
        ]);
    }
}

if (!function_exists('bw_header_live_search_normalize_term')) {
    function bw_header_live_search_normalize_term($raw_term)
    {
        if (!is_scalar($raw_term) && null !== $raw_term) {
            return '';
        }

        $term = sanitize_text_field(wp_unslash((string) $raw_term));
        return trim($term);
    }
}

if (!function_exists('bw_header_live_search_normalize_categories')) {
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
}

if (!function_exists('bw_header_live_search_build_visibility_tax_query')) {
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
            if (!empty($visibility_term_ids['exclude-from-catalog'])) {
                $excluded_terms[] = (int) $visibility_term_ids['exclude-from-catalog'];
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
}

if (!function_exists('bw_header_live_search_products')) {
    function bw_header_live_search_products()
    {
        $nonce_valid = check_ajax_referer('bw_search_nonce', 'nonce', false);
        if (false === $nonce_valid) {
            bw_header_live_search_safe_empty();
        }

        $search_term = isset($_POST['search_term']) ? bw_header_live_search_normalize_term($_POST['search_term']) : '';
        $search_length = function_exists('mb_strlen') ? mb_strlen($search_term) : strlen($search_term);
        if ('' === $search_term || $search_length < 2) {
            bw_header_live_search_safe_empty();
        }

        $categories = isset($_POST['categories']) ? bw_header_live_search_normalize_categories($_POST['categories']) : [];

        $product_type = '';
        if (isset($_POST['product_type'])) {
            $product_type_raw = bw_header_live_search_normalize_term($_POST['product_type']);
            if ('' !== $product_type_raw && in_array($product_type_raw, ['simple', 'variable', 'grouped', 'external'], true)) {
                $product_type = $product_type_raw;
            } elseif ('' !== $product_type_raw) {
                // Invalid filter parameter: fail-safe empty response.
                bw_header_live_search_safe_empty();
            }
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
        $products = [];

        if ($query->have_posts()) {
            foreach ((array) $query->posts as $product_id) {
                $product_id = absint($product_id);
                if ($product_id <= 0) {
                    continue;
                }

                $product = wc_get_product($product_id);
                if (!$product) {
                    continue;
                }

                $image_id = $product->get_image_id();
                $image_url = '';
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                }
                if (!$image_url) {
                    $image_url = wc_placeholder_img_src('medium');
                }

                $products[] = [
                    'id' => $product_id,
                    'title' => get_the_title($product_id),
                    'price_html' => $product->get_price_html(),
                    'image_url' => $image_url,
                    'permalink' => get_permalink($product_id),
                ];
            }
        }

        wp_send_json_success([
            'products' => $products,
            'results' => $products,
            'message' => empty($products) ? __('Nessun prodotto trovato', 'bw') : '',
        ]);
    }
}
