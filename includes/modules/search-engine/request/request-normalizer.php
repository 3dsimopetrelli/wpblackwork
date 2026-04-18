<?php
if (!defined('ABSPATH')) {
    exit;
}

function bw_fpw_normalize_post_type($raw_post_type)
{
    $post_type = sanitize_key((string) $raw_post_type);
    $allowed_post_types = bw_fpw_get_allowed_post_types();

    if (!in_array($post_type, $allowed_post_types, true)) {
        return bw_fpw_get_default_post_type();
    }

    return $post_type;
}

function bw_fpw_normalize_widget_id($raw_widget_id)
{
    $widget_id = sanitize_text_field((string) $raw_widget_id);
    return substr($widget_id, 0, 64);
}

function bw_fpw_normalize_term_selector($raw_value)
{
    $value = sanitize_text_field((string) $raw_value);

    if ('all' === strtolower($value)) {
        return 'all';
    }

    $term_id = absint($value);
    return $term_id > 0 ? $term_id : 'all';
}

function bw_fpw_normalize_int_array($raw_values, $max_items = 50)
{
    $values = is_array($raw_values) ? $raw_values : [$raw_values];
    $normalized = [];

    foreach ($values as $value) {
        $int_value = absint($value);
        if ($int_value > 0) {
            $normalized[] = $int_value;
        }
    }

    $normalized = array_values(array_unique($normalized));

    if (count($normalized) > $max_items) {
        $normalized = array_slice($normalized, 0, $max_items);
    }

    return $normalized;
}

function bw_fpw_normalize_positive_int($raw_value, $default, $min, $max)
{
    $value = absint($raw_value);

    if ($value < $min) {
        $value = $default;
    }

    if ($value > $max) {
        $value = $max;
    }

    return $value;
}

function bw_fpw_prepare_post_in_values($post_ids)
{
    $normalized = bw_fpw_normalize_int_array((array) $post_ids, PHP_INT_MAX);
    $threshold = bw_fpw_get_large_post_in_threshold();

    if ($threshold > 0 && count($normalized) > $threshold) {
        $normalized = array_slice($normalized, 0, $threshold);
    }

    return $normalized;
}

function bw_fpw_normalize_bool($raw_value, $default = false)
{
    if (null === $raw_value) {
        return (bool) $default;
    }

    $normalized = filter_var($raw_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return null === $normalized ? (bool) $default : (bool) $normalized;
}

function bw_fpw_normalize_order_by($raw_order_by)
{
    $order_by = sanitize_key((string) $raw_order_by);
    $valid_order_by = ['date', 'modified', 'title', 'rand', 'id', 'year_int'];

    if (!in_array($order_by, $valid_order_by, true)) {
        return 'date';
    }

    return 'id' === $order_by ? 'ID' : $order_by;
}

function bw_fpw_normalize_order($raw_order)
{
    $order = strtoupper(sanitize_key((string) $raw_order));
    return in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';
}

function bw_fpw_normalize_sort_key($raw_sort_key)
{
    $sort_key = sanitize_key((string) $raw_sort_key);
    $aliases = function_exists('bw_fpw_get_discovery_sort_aliases') ? bw_fpw_get_discovery_sort_aliases() : [];
    $default_sort_key = function_exists('bw_fpw_get_discovery_sort_default_key') ? bw_fpw_get_discovery_sort_default_key() : 'newest';
    $valid_sort_keys = ['newest', 'oldest', 'title_asc', 'title_desc', 'year_asc', 'year_desc'];

    if (isset($aliases[$sort_key])) {
        $sort_key = $aliases[$sort_key];
    }

    return in_array($sort_key, $valid_sort_keys, true) ? $sort_key : $default_sort_key;
}

function bw_fpw_normalize_request_profile($raw_request_profile)
{
    $request_profile = sanitize_key((string) $raw_request_profile);

    if (!in_array($request_profile, ['full', 'suggest'], true)) {
        return 'full';
    }

    return $request_profile;
}

function bw_fpw_normalize_image_size($raw_image_size)
{
    $image_size = sanitize_key((string) $raw_image_size);
    $allowed_sizes = ['thumbnail', 'medium', 'medium_large', 'large', 'full', 'woocommerce_thumbnail', 'woocommerce_single', 'woocommerce_gallery_thumbnail'];

    if (!in_array($image_size, $allowed_sizes, true)) {
        return 'large';
    }

    return $image_size;
}

function bw_fpw_normalize_image_mode($raw_image_mode)
{
    $image_mode = sanitize_key((string) $raw_image_mode);

    if (!in_array($image_mode, ['proportional', 'cover'], true)) {
        return 'proportional';
    }

    return $image_mode;
}

function bw_fpw_normalize_search_query($search)
{
    if (!is_string($search)) {
        return '';
    }

    $normalized = sanitize_text_field(wp_unslash($search));
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));

    if (!is_string($normalized)) {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($normalized, 0, 100);
    }

    return substr($normalized, 0, 100);
}

function bw_fpw_normalize_search_value($search)
{
    if (!is_string($search) || '' === $search) {
        return '';
    }

    $search = remove_accents(mb_strtolower($search, 'UTF-8'));
    $search = preg_replace('/\s+/', ' ', trim($search));

    return is_string($search) ? $search : '';
}

function bw_fpw_extract_year_int($value)
{
    if (is_int($value) || is_float($value)) {
        $year = (int) $value;
        return $year > 0 ? $year : null;
    }

    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ('' === $value) {
        return null;
    }

    if (preg_match('/(?<!\d)(\d{3,4})(?!\d)/', $value, $matches)) {
        $year = (int) $matches[1];
        return $year > 0 ? $year : null;
    }

    return null;
}

function bw_fpw_normalize_filter_token_label($value)
{
    if (!is_scalar($value)) {
        return '';
    }

    $normalized = sanitize_text_field(wp_unslash((string) $value));
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));

    return is_string($normalized) ? $normalized : '';
}

function bw_fpw_normalize_filter_token_value($value)
{
    $label = bw_fpw_normalize_filter_token_label($value);

    if ('' === $label) {
        return '';
    }

    $normalized = remove_accents($label);
    $normalized = strtolower($normalized);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));

    return is_string($normalized) ? $normalized : '';
}

function bw_fpw_extract_filter_tokens_from_value($value)
{
    $segments = is_array($value) ? $value : explode(',', (string) $value);
    $tokens = [];

    foreach ($segments as $segment) {
        $label = bw_fpw_normalize_filter_token_label($segment);
        $normalized_value = bw_fpw_normalize_filter_token_value($label);

        if ('' === $label || '' === $normalized_value || isset($tokens[$normalized_value])) {
            continue;
        }

        $tokens[$normalized_value] = [
            'value' => $normalized_value,
            'label' => $label,
        ];
    }

    return array_values($tokens);
}

function bw_fpw_join_filter_token_labels_for_storage($tokens)
{
    $labels = [];

    foreach ((array) $tokens as $token) {
        if (!is_array($token) || empty($token['label'])) {
            continue;
        }

        $labels[] = (string) $token['label'];
    }

    return implode(', ', $labels);
}

function bw_fpw_normalize_filter_token_selection_array($values, $limit = 50)
{
    if (!is_array($values)) {
        $values = [$values];
    }

    $normalized = [];

    foreach ($values as $value) {
        $normalized_value = bw_fpw_normalize_filter_token_value($value);

        if ('' === $normalized_value || isset($normalized[$normalized_value])) {
            continue;
        }

        $normalized[$normalized_value] = $normalized_value;

        if (count($normalized) >= $limit) {
            break;
        }
    }

    return array_values($normalized);
}

function bw_fpw_normalize_author_text($value)
{
    if (!is_string($value)) {
        return '';
    }

    return trim(sanitize_text_field(wp_unslash($value)));
}

function bw_fpw_get_empty_advanced_filter_selections()
{
    $selections = [];

    foreach (bw_fpw_get_advanced_filter_group_keys() as $group_key) {
        $selections[$group_key] = [];
    }

    return $selections;
}

function bw_fpw_normalize_advanced_filter_selections($filters)
{
    $normalized = bw_fpw_get_empty_advanced_filter_selections();

    if (!is_array($filters)) {
        return $normalized;
    }

    foreach ($normalized as $group_key => $values) {
        $normalized[$group_key] = bw_fpw_normalize_filter_token_selection_array(
            isset($filters[$group_key]) ? $filters[$group_key] : [],
            50
        );
    }

    return $normalized;
}

function bw_fpw_normalize_year_bound($value)
{
    if (null === $value || '' === $value) {
        return null;
    }

    return bw_fpw_extract_year_int(is_scalar($value) ? (string) $value : '');
}

function bw_fpw_normalize_year_range($from, $to)
{
    $normalized_from = bw_fpw_normalize_year_bound($from);
    $normalized_to = bw_fpw_normalize_year_bound($to);

    if (null !== $normalized_from && null !== $normalized_to && $normalized_from > $normalized_to) {
        $temp = $normalized_from;
        $normalized_from = $normalized_to;
        $normalized_to = $temp;
    }

    return [
        'from' => $normalized_from,
        'to' => $normalized_to,
    ];
}

function bw_fpw_normalize_array_for_cache_key($values)
{
    if (!is_array($values)) {
        return [];
    }

    $normalized = array_map('absint', $values);
    $normalized = array_filter(
        $normalized,
        static function ($value) {
            return $value > 0;
        }
    );
    $normalized = array_values(array_unique($normalized));
    sort($normalized, SORT_NUMERIC);

    return $normalized;
}

function bw_fpw_normalize_token_array_for_cache_key($values)
{
    $normalized = bw_fpw_normalize_filter_token_selection_array($values, 50);
    sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

    return array_values($normalized);
}

function bw_fpw_normalize_advanced_filters_for_cache_key($filters)
{
    $normalized = bw_fpw_normalize_advanced_filter_selections($filters);

    foreach ($normalized as $group_key => $values) {
        $normalized[$group_key] = bw_fpw_normalize_token_array_for_cache_key($values);
    }

    return $normalized;
}

function bw_fpw_normalize_filter_ui_hashes($hashes)
{
    $normalized = [];

    if (!is_array($hashes)) {
        return $normalized;
    }

    foreach (['types', 'tags', 'advanced', 'year', 'result_count'] as $section_key) {
        if (!isset($hashes[$section_key])) {
            continue;
        }

        $hash = preg_replace('/[^a-f0-9]/i', '', (string) $hashes[$section_key]);

        if (is_string($hash) && '' !== $hash) {
            $normalized[$section_key] = strtolower($hash);
        }
    }

    return $normalized;
}

function bw_fpw_build_engine_request(array $source = [])
{
    $search_enabled = bw_fpw_normalize_bool(isset($source['search_enabled']) ? wp_unslash($source['search_enabled']) : null, true);
    $search = $search_enabled
        ? bw_fpw_normalize_search_query(isset($source['search']) ? wp_unslash($source['search']) : '')
        : '';
    $request_profile = bw_fpw_normalize_request_profile(isset($source['request_profile']) ? wp_unslash($source['request_profile']) : 'full');
    $normalized_year_range = bw_fpw_normalize_year_range(
        isset($source['year_from']) ? wp_unslash($source['year_from']) : null,
        isset($source['year_to']) ? wp_unslash($source['year_to']) : null
    );
    $raw_per_page = isset($source['per_page']) ? wp_unslash($source['per_page']) : bw_fpw_get_default_per_page();
    $normalized_per_page = is_numeric($raw_per_page) ? (int) $raw_per_page : bw_fpw_get_default_per_page();
    $per_page = $normalized_per_page <= 0
        ? -1
        : bw_fpw_normalize_positive_int(
            $normalized_per_page,
            bw_fpw_get_default_per_page(),
            1,
            bw_fpw_get_max_per_page()
        );

    return [
        'widget_id' => bw_fpw_normalize_widget_id(isset($source['widget_id']) ? wp_unslash($source['widget_id']) : ''),
        'post_type' => bw_fpw_normalize_post_type(isset($source['post_type']) ? wp_unslash($source['post_type']) : bw_fpw_get_default_post_type()),
        'context_slug' => bw_fpw_normalize_context_slug(isset($source['context_slug']) ? wp_unslash($source['context_slug']) : ''),
        'category' => bw_fpw_normalize_term_selector(isset($source['category']) ? wp_unslash($source['category']) : 'all'),
        'subcategories' => bw_fpw_normalize_int_array(isset($source['subcategories']) ? wp_unslash($source['subcategories']) : [], 50),
        'tags' => bw_fpw_normalize_int_array(isset($source['tags']) ? wp_unslash($source['tags']) : [], 50),
        'search_enabled' => $search_enabled,
        'search' => $search,
        'request_profile' => $request_profile,
        'include_filter_ui' => bw_fpw_normalize_bool(isset($source['include_filter_ui']) ? wp_unslash($source['include_filter_ui']) : null, false),
        'year_from' => $normalized_year_range['from'],
        'year_to' => $normalized_year_range['to'],
        'advanced_filters' => bw_fpw_normalize_advanced_filter_selections([
            'artist' => isset($source['artist']) ? wp_unslash($source['artist']) : [],
            'author' => isset($source['author']) ? wp_unslash($source['author']) : [],
            'publisher' => isset($source['publisher']) ? wp_unslash($source['publisher']) : [],
            'source' => isset($source['source']) ? wp_unslash($source['source']) : [],
            'technique' => isset($source['technique']) ? wp_unslash($source['technique']) : [],
        ]),
        'client_filter_ui_hashes' => bw_fpw_normalize_filter_ui_hashes(isset($source['filter_ui_hashes']) ? wp_unslash($source['filter_ui_hashes']) : []),
        'image_toggle' => bw_fpw_normalize_bool(isset($source['image_toggle']) ? wp_unslash($source['image_toggle']) : null, false),
        'image_size' => bw_fpw_normalize_image_size(isset($source['image_size']) ? wp_unslash($source['image_size']) : 'large'),
        'image_mode' => bw_fpw_normalize_image_mode(isset($source['image_mode']) ? wp_unslash($source['image_mode']) : 'proportional'),
        'hover_effect' => bw_fpw_normalize_bool(isset($source['hover_effect']) ? wp_unslash($source['hover_effect']) : null, false),
        'open_cart_popup' => bw_fpw_normalize_bool(isset($source['open_cart_popup']) ? wp_unslash($source['open_cart_popup']) : null, false),
        'default_order_by' => bw_fpw_normalize_order_by(isset($source['order_by']) ? wp_unslash($source['order_by']) : 'date'),
        'default_order' => bw_fpw_normalize_order(isset($source['order']) ? wp_unslash($source['order']) : 'DESC'),
        'sort_key' => bw_fpw_normalize_sort_key(isset($source['sort_key']) ? wp_unslash($source['sort_key']) : (function_exists('bw_fpw_get_discovery_sort_default_key') ? bw_fpw_get_discovery_sort_default_key() : 'newest')),
        'per_page' => $per_page,
        'page' => bw_fpw_normalize_positive_int(
            isset($source['page']) ? wp_unslash($source['page']) : 1,
            1,
            1,
            1000
        ),
        'offset' => isset($source['offset']) && is_numeric(wp_unslash($source['offset'])) ? max(0, (int) wp_unslash($source['offset'])) : 0,
    ];
}
