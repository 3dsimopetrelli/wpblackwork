<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_get_default_per_page()
{
    return 24;
}

function bw_fpw_get_max_per_page()
{
    return 100;
}

function bw_fpw_get_php_sort_max_ids()
{
    return 5000;
}

function bw_fpw_get_tag_source_posts_limit()
{
    return 300;
}

function bw_fpw_get_client_response_cache_ttl()
{
    return 10 * MINUTE_IN_SECONDS;
}

function bw_fpw_get_year_index_cache_ttl()
{
    return 15 * MINUTE_IN_SECONDS;
}

function bw_fpw_get_advanced_filter_index_cache_ttl()
{
    return 15 * MINUTE_IN_SECONDS;
}

function bw_fpw_get_derived_filter_dataset_cache_ttl()
{
    return bw_fpw_get_client_response_cache_ttl();
}

function bw_fpw_get_index_build_lock_ttl()
{
    return 45;
}

function bw_fpw_get_index_build_lock_wait_attempts()
{
    return 5;
}

function bw_fpw_get_index_build_lock_wait_interval_us()
{
    return 150000;
}

function bw_fpw_get_large_post_in_threshold()
{
    return 12000;
}

function bw_fpw_get_max_candidate_set_size()
{
    return min(12000, bw_fpw_get_large_post_in_threshold());
}

function bw_fpw_get_cache_generation($context_slug)
{
    static $cache = [];
    $opt = 'bw_fpw_cache_gen_' . ('' === $context_slug ? 'all' : sanitize_key($context_slug));
    if (!array_key_exists($opt, $cache)) {
        $cache[$opt] = (int) get_option($opt, 0);
    }
    return $cache[$opt];
}

function bw_fpw_get_index_build_lock_option_name($namespace, $context_slug)
{
    $normalized_namespace = sanitize_key((string) $namespace);
    $normalized_context = bw_fpw_normalize_context_slug($context_slug);

    return 'bw_fpw_lock_' . $normalized_namespace . '_' . ($normalized_context ?: 'unknown');
}

function bw_fpw_acquire_index_build_lock($namespace, $context_slug)
{
    $option_name = bw_fpw_get_index_build_lock_option_name($namespace, $context_slug);
    $now = time();
    $ttl = bw_fpw_get_index_build_lock_ttl();

    if (add_option($option_name, $now, '', false)) {
        return true;
    }

    $existing = (int) get_option($option_name, 0);

    if ($existing > 0 && ($now - $existing) > $ttl) {
        delete_option($option_name);

        return add_option($option_name, $now, '', false);
    }

    return false;
}

function bw_fpw_release_index_build_lock($namespace, $context_slug)
{
    delete_option(bw_fpw_get_index_build_lock_option_name($namespace, $context_slug));
}

function bw_fpw_get_cached_index_with_lock($transient_key, $lock_namespace, $context_slug, $builder, $cache_ttl)
{
    $cached = get_transient($transient_key);

    if (is_array($cached)) {
        return $cached;
    }

    if (bw_fpw_acquire_index_build_lock($lock_namespace, $context_slug)) {
        try {
            $index = is_callable($builder) ? call_user_func($builder) : [];
            set_transient($transient_key, $index, (int) $cache_ttl);
        } finally {
            bw_fpw_release_index_build_lock($lock_namespace, $context_slug);
        }

        return $index;
    }

    $attempts = bw_fpw_get_index_build_lock_wait_attempts();
    $wait_us = bw_fpw_get_index_build_lock_wait_interval_us();

    for ($i = 0; $i < $attempts; $i++) {
        if ($wait_us > 0) {
            usleep($wait_us);
        }

        $cached = get_transient($transient_key);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $index = is_callable($builder) ? call_user_func($builder) : [];
    set_transient($transient_key, $index, (int) $cache_ttl);

    return $index;
}

function bw_fpw_bump_cache_generation($context_slugs)
{
    $slugs = array_unique(array_filter((array) $context_slugs, 'is_string'));
    foreach ($slugs as $slug) {
        $opt = 'bw_fpw_cache_gen_' . ('' === $slug ? 'all' : sanitize_key($slug));
        update_option($opt, (int) get_option($opt, 0) + 1, false);
    }
}

function bw_fpw_clear_grid_transient_cache($context_slugs = null)
{
    if (null === $context_slugs) {
        $all_slugs = array_merge(['', 'mixed'], bw_fpw_get_supported_product_family_slugs());
    } else {
        $given = array_unique(array_filter(array_map('strval', (array) $context_slugs), 'is_string'));
        $all_slugs = array_unique(array_merge($given, ['', 'mixed']));
    }

    bw_fpw_bump_cache_generation($all_slugs);
}

function bw_fpw_build_derived_filter_dataset_transient_key($dataset, $params)
{
    $dataset = sanitize_key((string) $dataset);
    $context_slug = bw_fpw_normalize_context_slug(isset($params['context_slug']) ? $params['context_slug'] : '');
    $normalized_year_range = bw_fpw_normalize_year_range(
        isset($params['year_from']) ? $params['year_from'] : null,
        isset($params['year_to']) ? $params['year_to'] : null
    );
    $canonical_payload = [
        'schema' => 'v1',
        'dataset' => $dataset,
        'post_type' => bw_fpw_normalize_post_type(isset($params['post_type']) ? $params['post_type'] : bw_fpw_get_default_post_type()),
        'context_slug' => $context_slug,
        'cache_gen' => bw_fpw_get_cache_generation($context_slug),
        'category' => bw_fpw_normalize_term_selector(isset($params['category']) ? $params['category'] : 'all'),
        'subcategories' => bw_fpw_normalize_array_for_cache_key(isset($params['subcategories']) ? $params['subcategories'] : []),
        'tags' => bw_fpw_normalize_array_for_cache_key(isset($params['tags']) ? $params['tags'] : []),
        'search' => bw_fpw_normalize_search_value(isset($params['search']) ? (string) $params['search'] : ''),
        'year_from' => $normalized_year_range['from'],
        'year_to' => $normalized_year_range['to'],
        'advanced' => bw_fpw_normalize_advanced_filters_for_cache_key(isset($params['advanced_filters']) ? $params['advanced_filters'] : []),
    ];
    $payload_json = wp_json_encode($canonical_payload);

    if (!is_string($payload_json) || '' === $payload_json) {
        $payload_json = wp_json_encode(['fallback' => $canonical_payload]);
    }

    return 'bw_fpw_dataset_' . $dataset . '_' . hash('sha256', (string) $payload_json);
}

function bw_fpw_get_cached_derived_filter_dataset($dataset, $params, $builder)
{
    static $request_cache = [];

    $transient_key = bw_fpw_build_derived_filter_dataset_transient_key($dataset, $params);

    if (array_key_exists($transient_key, $request_cache)) {
        return $request_cache[$transient_key];
    }

    $cached = get_transient($transient_key);

    if (is_array($cached)) {
        $request_cache[$transient_key] = $cached;

        return $cached;
    }

    $result = is_callable($builder) ? call_user_func($builder) : [];
    $result = is_array($result) ? array_values($result) : [];

    $request_cache[$transient_key] = $result;
    set_transient($transient_key, $result, bw_fpw_get_derived_filter_dataset_cache_ttl());

    return $result;
}

function bw_fpw_generate_cache_key($params)
{
    $params = is_array($params) ? $params : [];
    $search_enabled = bw_fpw_normalize_bool(isset($params['search_enabled']) ? $params['search_enabled'] : null, true);
    $search_value = $search_enabled
        ? bw_fpw_normalize_search_query(isset($params['search']) ? (string) $params['search'] : '')
        : '';
    $request_profile = bw_fpw_normalize_request_profile(isset($params['request_profile']) ? $params['request_profile'] : 'full');

    $context_slug_for_key = isset($params['context_slug']) ? (string) $params['context_slug'] : '';

    $canonical_payload = [
        'schema' => 'v10',
        'post_type' => isset($params['post_type']) ? (string) $params['post_type'] : bw_fpw_get_default_post_type(),
        'context_slug' => $context_slug_for_key,
        'cache_gen' => bw_fpw_get_cache_generation($context_slug_for_key),
        'request_profile' => $request_profile,
        'include_filter_ui' => bw_fpw_normalize_bool(isset($params['include_filter_ui']) ? $params['include_filter_ui'] : null, false) ? 1 : 0,
        'category' => isset($params['category']) ? (string) $params['category'] : 'all',
        'subcategories' => bw_fpw_normalize_array_for_cache_key(isset($params['subcategories']) ? $params['subcategories'] : []),
        'tags' => bw_fpw_normalize_array_for_cache_key(isset($params['tags']) ? $params['tags'] : []),
        'search_enabled' => $search_enabled ? 1 : 0,
        'search' => $search_value,
        'year_from' => bw_fpw_normalize_year_bound(isset($params['year_from']) ? $params['year_from'] : null),
        'year_to' => bw_fpw_normalize_year_bound(isset($params['year_to']) ? $params['year_to'] : null),
        'artist' => bw_fpw_normalize_token_array_for_cache_key(isset($params['artist']) ? $params['artist'] : []),
        'author' => bw_fpw_normalize_token_array_for_cache_key(isset($params['author']) ? $params['author'] : []),
        'publisher' => bw_fpw_normalize_token_array_for_cache_key(isset($params['publisher']) ? $params['publisher'] : []),
        'source' => bw_fpw_normalize_token_array_for_cache_key(isset($params['source']) ? $params['source'] : []),
        'technique' => bw_fpw_normalize_token_array_for_cache_key(isset($params['technique']) ? $params['technique'] : []),
        'sort_key' => bw_fpw_normalize_sort_key(isset($params['sort_key']) ? $params['sort_key'] : (function_exists('bw_fpw_get_discovery_sort_default_key') ? bw_fpw_get_discovery_sort_default_key() : 'newest')),
        'order_by' => isset($params['order_by']) ? (string) $params['order_by'] : 'date',
        'order' => isset($params['order']) ? (string) $params['order'] : 'DESC',
        'per_page' => isset($params['per_page']) ? (int) $params['per_page'] : bw_fpw_get_default_per_page(),
        'page' => isset($params['page']) ? (int) $params['page'] : 1,
        'offset' => isset($params['offset']) ? (int) $params['offset'] : 0,
    ];

    $payload_json = wp_json_encode($canonical_payload);
    if (!is_string($payload_json) || '' === $payload_json) {
        $payload_json = serialize($canonical_payload);
    }

    $hash = hash('sha256', $payload_json);

    return 'bw_fpw_data_' . $hash;
}

function bw_fpw_build_engine_cache_key($request)
{
    return bw_fpw_generate_cache_key([
        'post_type' => isset($request['post_type']) ? $request['post_type'] : bw_fpw_get_default_post_type(),
        'context_slug' => isset($request['effective_context_slug']) ? $request['effective_context_slug'] : (isset($request['context_slug']) ? $request['context_slug'] : ''),
        'request_profile' => isset($request['request_profile']) ? $request['request_profile'] : 'full',
        'include_filter_ui' => isset($request['include_filter_ui']) ? $request['include_filter_ui'] : false,
        'category' => isset($request['category']) ? $request['category'] : 'all',
        'subcategories' => isset($request['subcategories']) ? $request['subcategories'] : [],
        'tags' => isset($request['tags']) ? $request['tags'] : [],
        'search_enabled' => isset($request['search_enabled']) ? $request['search_enabled'] : true,
        'search' => isset($request['search']) ? $request['search'] : '',
        'year_from' => isset($request['year_from']) ? $request['year_from'] : null,
        'year_to' => isset($request['year_to']) ? $request['year_to'] : null,
        'artist' => isset($request['advanced_filters']['artist']) ? $request['advanced_filters']['artist'] : [],
        'author' => isset($request['advanced_filters']['author']) ? $request['advanced_filters']['author'] : [],
        'publisher' => isset($request['advanced_filters']['publisher']) ? $request['advanced_filters']['publisher'] : [],
        'source' => isset($request['advanced_filters']['source']) ? $request['advanced_filters']['source'] : [],
        'technique' => isset($request['advanced_filters']['technique']) ? $request['advanced_filters']['technique'] : [],
        'sort_key' => bw_fpw_normalize_sort_key(isset($request['sort_key']) ? $request['sort_key'] : (function_exists('bw_fpw_get_discovery_sort_default_key') ? bw_fpw_get_discovery_sort_default_key() : 'newest')),
        'order_by' => isset($request['effective_order_by']) ? $request['effective_order_by'] : (isset($request['default_order_by']) ? $request['default_order_by'] : 'date'),
        'order' => isset($request['effective_order']) ? $request['effective_order'] : (isset($request['default_order']) ? $request['default_order'] : 'DESC'),
        'per_page' => isset($request['per_page']) ? $request['per_page'] : bw_fpw_get_default_per_page(),
        'page' => isset($request['page']) ? $request['page'] : 1,
        'offset' => isset($request['offset']) ? $request['offset'] : 0,
    ]);
}

function bw_fpw_get_cached_search_result($cache_key)
{
    $cached = get_transient($cache_key);
    return is_array($cached) ? $cached : false;
}

function bw_fpw_store_cached_search_result($cache_key, $payload)
{
    if (!is_array($payload)) {
        return false;
    }

    return set_transient($cache_key, $payload, bw_fpw_get_client_response_cache_ttl());
}

function bw_fpw_get_filter_ui_section_signature($value)
{
    $json = wp_json_encode($value);

    if (!is_string($json) || '' === $json) {
        $json = wp_json_encode(['fallback' => $value]);
    }

    return is_string($json) ? hash('sha256', $json) : '';
}

function bw_fpw_build_filter_ui_hashes($filter_ui)
{
    $filter_ui = is_array($filter_ui) ? $filter_ui : [];
    $hashes = [];

    foreach (['types', 'tags', 'advanced', 'year', 'result_count'] as $section_key) {
        if (array_key_exists($section_key, $filter_ui)) {
            $hashes[$section_key] = bw_fpw_get_filter_ui_section_signature($filter_ui[$section_key]);
        }
    }

    return $hashes;
}
