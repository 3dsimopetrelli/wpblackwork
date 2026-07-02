<?php
/**
 * Blackwork Site Settings — product/variation CSV export engine.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderizza il tab Import Product.
 */
function bw_export_get_selected_category_id()
{
    $raw_value = '';

    if (isset($_POST['bw_export_category'])) {
        $raw_value = wp_unslash($_POST['bw_export_category']);
    } elseif (isset($_GET['bw_export_category'])) {
        $raw_value = wp_unslash($_GET['bw_export_category']);
    }

    return max(0, absint($raw_value));
}

function bw_export_get_selected_product_value()
{
    $raw_value = 'all';

    if (isset($_POST['bw_export_product'])) {
        $raw_value = sanitize_text_field(wp_unslash($_POST['bw_export_product']));
    } elseif (isset($_GET['bw_export_product'])) {
        $raw_value = sanitize_text_field(wp_unslash($_GET['bw_export_product']));
    }

    if ($raw_value === '' || $raw_value === 'all') {
        return 'all';
    }

    return (string) absint($raw_value);
}

function bw_export_get_include_variations()
{
    if (isset($_POST['bw_export_include_variations'])) {
        return !empty($_POST['bw_export_include_variations']);
    }

    return true;
}

function bw_export_get_profile()
{
    $raw_value = 'master_csv';

    if (isset($_POST['bw_export_profile'])) {
        $raw_value = sanitize_key(wp_unslash($_POST['bw_export_profile']));
    } elseif (isset($_GET['bw_export_profile'])) {
        $raw_value = sanitize_key(wp_unslash($_GET['bw_export_profile']));
    }

    $allowed = ['master_csv', 'woo_native_csv'];
    if (!in_array($raw_value, $allowed, true)) {
        return 'master_csv';
    }

    return $raw_value;
}

function bw_export_get_category_options()
{
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $by_parent = [];
    foreach ($terms as $term) {
        $parent_id = isset($term->parent) ? (int) $term->parent : 0;
        if (!isset($by_parent[$parent_id])) {
            $by_parent[$parent_id] = [];
        }
        $by_parent[$parent_id][] = $term;
    }

    $options = [];
    bw_export_walk_category_terms($by_parent, 0, 0, $options);

    return $options;
}

function bw_export_walk_category_terms($by_parent, $parent_id, $depth, &$options)
{
    if (empty($by_parent[$parent_id]) || !is_array($by_parent[$parent_id])) {
        return;
    }

    foreach ($by_parent[$parent_id] as $term) {
        $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
        $options[(int) $term->term_id] = $prefix . $term->name;
        bw_export_walk_category_terms($by_parent, (int) $term->term_id, $depth + 1, $options);
    }
}

function bw_export_get_products_for_category($category_id)
{
    $query_args = [
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ];

    if ($category_id > 0) {
        $query_args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => [$category_id],
            'include_children' => true,
        ]];
    }

    $product_ids = get_posts($query_args);
    if (empty($product_ids)) {
        return [];
    }

    $options = [];
    foreach ($product_ids as $product_id) {
        $product_id = (int) $product_id;
        $sku = (string) get_post_meta($product_id, '_sku', true);
        $title = get_the_title($product_id);
        $label = $title !== '' ? $title : sprintf(__('Product #%d', 'bw'), $product_id);
        if ($sku !== '') {
            $label .= ' [' . $sku . ']';
        }
        $options[$product_id] = $label;
    }

    return $options;
}

function bw_export_get_master_template_columns()
{
    return [
        'row_type',
        'parent_sku',
        'parent_post_id',
        'post_id',
        'source_variation_id',
        'post_status',
        'status',
        'post_title',
        'post_name',
        'post_content',
        'post_excerpt',
        'menu_order',
        'sku',
        'woo_product_type',
        'featured',
        'catalog_visibility',
        'virtual',
        'downloadable',
        'regular_price',
        'sale_price',
        'current_price',
        'price',
        'date_on_sale_from',
        'date_on_sale_to',
        'tax_status',
        'tax_class',
        'manage_stock',
        'stock_quantity',
        'stock_status',
        'backorders',
        'sold_individually',
        'weight',
        'length',
        'width',
        'height',
        'shipping_class',
        'purchase_note',
        'reviews_allowed',
        'featured_image',
        'image_url',
        'product_gallery',
        'product_cat',
        'product_subcategories',
        'product_tag',
        'upsell_skus',
        'cross_sell_skus',
        'grouped_skus',
        'external_url',
        'external_button_text',
        'download_limit',
        'download_expiry',
        'downloadable_files_json',
        'variation_downloadable_files_json',
        'attributes_json',
        'default_attributes_json',
        'variation_attributes_json',
        'variation_regular_price',
        'variation_sale_price',
        'variation_stock_quantity',
        'variation_stock_status',
        'variation_manage_stock',
        'variation_backorders',
        'variation_weight',
        'variation_length',
        'variation_width',
        'variation_height',
        'variation_description',
        '_bw_product_type',
        '_bw_texts_color',
        '_bw_showcase_image',
        '_bw_showcase_title',
        '_bw_showcase_description',
        '_bw_file_size',
        '_bw_assets_count',
        '_bw_formats',
        '_bw_info_1',
        '_bw_info_2',
        '_product_button_text',
        '_product_button_link',
        '_bw_showcase_linked_product',
        '_bw_slider_hover_image',
        '_bw_slider_hover_video',
        '_bw_biblio_title',
        '_bw_biblio_author',
        '_bw_biblio_publisher',
        '_bw_biblio_year',
        '_bw_biblio_language',
        '_bw_biblio_binding',
        '_bw_biblio_pages',
        '_bw_biblio_edition',
        '_bw_biblio_condition',
        '_bw_biblio_location',
        '_print_artist',
        '_print_publisher',
        '_print_year',
        '_print_technique',
        '_print_material',
        '_print_plate_size',
        '_print_condition',
        '_digital_total_assets',
        '_digital_assets_list',
        '_digital_file_size',
        '_digital_formats',
        '_bw_artist_name',
        '_digital_artist_name',
        '_digital_source',
        '_digital_publisher',
        '_digital_year',
        '_digital_technique',
        '_bw_compatibility_configured',
        '_bw_compatibility_adobe_illustrator_photoshop',
        '_bw_compatibility_figma_sketch_adobe_xd',
        '_bw_compatibility_affinity_designer_photo',
        '_bw_compatibility_coreldraw_inkscape',
        '_bw_compatibility_canva_powerpoint',
        '_bw_compatibility_cricut_silhouette',
        '_bw_compatibility_blender_cinema4d',
        '_product_showcase_image',
        '_product_size_mb',
        '_product_assets_count',
        '_product_formats',
        '_product_color',
        '_bw_variation_license_col1_json',
        '_bw_variation_license_col2_json',
    ];
}

function bw_export_get_woo_native_columns()
{
    return [
        'ID',
        'Type',
        'SKU',
        'Name',
        'Published',
        'Is featured?',
        'Visibility in catalog',
        'Short description',
        'Description',
        'Date sale price starts',
        'Date sale price ends',
        'Tax status',
        'Tax class',
        'In stock?',
        'Stock',
        'Backorders allowed?',
        'Sold individually?',
        'Weight (kg)',
        'Length (cm)',
        'Width (cm)',
        'Height (cm)',
        'Allow customer reviews?',
        'Purchase note',
        'Sale price',
        'Regular price',
        'Categories',
        'Tags',
        'Shipping class',
        'Images',
        'Download limit',
        'Download expiry days',
        'Parent',
        'Grouped products',
        'Upsells',
        'Cross-sells',
        'External URL',
        'Button text',
        'Position',
        'Attribute 1 name',
        'Attribute 1 value(s)',
        'Attribute 1 visible',
        'Attribute 1 global',
        'Attribute 1 default',
    ];
}

function bw_export_handle_request()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_export_permission', __('You do not have permission to export products.', 'bw'));
    }

    if (!isset($_POST['bw_export_products_nonce']) || !wp_verify_nonce($_POST['bw_export_products_nonce'], 'bw_export_products')) {
        return new WP_Error('bw_export_nonce', __('Invalid export nonce. Please try again.', 'bw'));
    }

    $category_id = bw_export_get_selected_category_id();
    $product_value = bw_export_get_selected_product_value();
    $include_variations = bw_export_get_include_variations();
    $profile = bw_export_get_profile();

    $product_ids = bw_export_resolve_product_ids($category_id, $product_value);
    if (empty($product_ids)) {
        return new WP_Error('bw_export_empty', __('No matching products were found for this export.', 'bw'));
    }

    bw_export_stream_csv($product_ids, $include_variations, $category_id, $product_value, $profile);
    exit;
}

/**
 * Process product export before the admin page sends HTML output.
 *
 * Successful exports stream the CSV and exit. Failed exports are stored so the
 * tab renderer can show a standard admin notice without retrying the request.
 */
function bw_export_maybe_handle_admin_request()
{
    if (!is_admin() || !isset($_POST['bw_export_products_submit'])) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';

    $is_legacy_page = ($page === 'blackwork-site-settings' && $tab === 'import-product');
    $is_standalone_page = ($page === 'blackwork-product-import-export');

    if (!$is_legacy_page && !$is_standalone_page) {
        return;
    }

    $GLOBALS['bw_export_request_result'] = bw_export_handle_request();
}

function bw_export_resolve_product_ids($category_id, $product_value)
{
    if ($product_value !== 'all') {
        $product_id = absint($product_value);
        return $product_id > 0 ? [$product_id] : [];
    }

    $product_ids = [];
    $paged = 1;

    do {
        $query_args = [
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 200,
            'paged' => $paged,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'suppress_filters' => false,
        ];

        if ($category_id > 0) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => [$category_id],
                'include_children' => true,
            ]];
        }

        $query = new WP_Query($query_args);
        if (!empty($query->posts)) {
            foreach ($query->posts as $product_id) {
                $product_ids[] = (int) $product_id;
            }
        }

        $has_more = $query->max_num_pages > $paged;
        wp_reset_postdata();
        $paged++;
    } while ($has_more);

    return $product_ids;
}

function bw_export_stream_csv($product_ids, $include_variations, $category_id, $product_value, $profile)
{
    $columns = bw_export_get_request_columns($product_ids, $include_variations, $profile);
    $filename = bw_export_build_filename($category_id, $product_value);
    $header_count = count($columns);
    $malformed_rows = 0;

    if ($header_count < 1) {
        bw_export_log_issue('export_error', 'Exporter aborted: empty header set.', ['profile' => $profile]);
        wp_die(esc_html__('Exporter header is empty. Please check configuration.', 'bw'));
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');

    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open export stream.', 'bw'));
    }

    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $columns);

    foreach ((array) $product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product || $product->is_type('variation')) {
            continue;
        }

        $row = $profile === 'woo_native_csv'
            ? bw_export_build_woo_native_product_row($product)
            : bw_export_build_product_csv_row($product);
        if (!bw_export_write_row($output, $columns, $row, $profile)) {
            $malformed_rows++;
        }

        if ($include_variations && $product->is_type('variable')) {
            foreach ((array) $product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation instanceof WC_Product_Variation) {
                    continue;
                }

                $variation_row = $profile === 'woo_native_csv'
                    ? bw_export_build_woo_native_variation_row($variation, $product)
                    : bw_export_build_variation_csv_row($variation, $product);
                if (!bw_export_write_row($output, $columns, $variation_row, $profile)) {
                    $malformed_rows++;
                }
            }
        }
    }

    if ($malformed_rows > 0) {
        bw_export_log_issue('export_warning', 'Exporter skipped malformed rows due to validation failures.', [
            'profile' => $profile,
            'malformed_rows' => $malformed_rows,
            'header_count' => $header_count,
        ]);
    }

    fclose($output);
}

function bw_export_get_request_columns($product_ids, $include_variations, $profile)
{
    if ($profile === 'woo_native_csv') {
        return bw_export_get_woo_native_columns();
    }

    $columns = bw_export_get_master_template_columns();
    $dynamic_meta_columns = bw_export_get_dynamic_meta_columns($product_ids, $include_variations);

    foreach ($dynamic_meta_columns as $meta_key) {
        if (!in_array($meta_key, $columns, true)) {
            $columns[] = $meta_key;
        }
    }

    return $columns;
}

function bw_export_build_filename($category_id, $product_value)
{
    $date_suffix = gmdate('Y-m-d-His');
    $parts = ['bw-product-export'];

    if ($category_id > 0) {
        $term = get_term($category_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $parts[] = sanitize_title($term->slug);
        }
    } else {
        $parts[] = 'all-categories';
    }

    if ($product_value !== 'all') {
        $parts[] = 'product-' . absint($product_value);
    } else {
        $parts[] = 'all-products';
    }

    $parts[] = $date_suffix;

    return implode('-', $parts) . '.csv';
}

function bw_export_order_row_for_csv($row, $columns)
{
    $ordered = [];
    foreach ($columns as $column) {
        $value = isset($row[$column]) ? $row[$column] : '';
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value);
        }
        $ordered[] = $value;
    }

    return $ordered;
}

function bw_export_write_row($output, $headers, $row_data, $profile)
{
    if (is_array($row_data)) {
        $row_data = array_filter(
            $row_data,
            static function ($key) {
                return is_string($key);
            },
            ARRAY_FILTER_USE_KEY
        );
    } else {
        $row_data = [];
    }

    $json_columns_lookup = [];
    if ($profile === 'master_csv') {
        $json_columns_lookup = array_flip(bw_export_get_json_columns_for_profile($profile));
    }

    $normalized_row = [];
    foreach ($headers as $column_key) {
        $value = array_key_exists($column_key, $row_data) ? $row_data[$column_key] : '';
        if (is_array($value) || is_object($value)) {
            $value = bw_export_json($value);
        } elseif (isset($json_columns_lookup[$column_key])) {
            $value = bw_export_force_json_string($value);
        }
        $normalized_row[] = $value;
    }

    $validation = bw_export_validate_row_payload($headers, $normalized_row, $row_data, $profile);
    if (!$validation['ok']) {
        $row_type = isset($row_data['row_type']) ? (string) $row_data['row_type'] : '';
        $post_id = isset($row_data['post_id']) ? $row_data['post_id'] : '';
        $source_variation_id = isset($row_data['source_variation_id']) ? $row_data['source_variation_id'] : '';
        bw_export_log_issue('row_validation', $validation['message'], [
            'profile' => $profile,
            'row_type' => $row_type,
            'post_id' => $post_id,
            'source_variation_id' => $source_variation_id,
            'sku' => isset($row_data['sku']) ? $row_data['sku'] : '',
            'header_count' => count($headers),
            'normalized_row_count' => count($normalized_row),
            'row_data_count' => is_array($row_data) ? count($row_data) : 0,
        ]);
        return false;
    }

    if (
        defined('WP_DEBUG') &&
        WP_DEBUG &&
        isset($row_data['row_type']) &&
        $row_data['row_type'] === 'variation'
    ) {
        $first_five = array_slice($normalized_row, 0, 5);
        $last_five = array_slice($normalized_row, -5);
        bw_export_log_issue('writer_trace', 'WRITER_USED_FOR_VARIATION', [
            'profile' => $profile,
            'header_count' => count($headers),
            'normalized_row_count' => count($normalized_row),
            'first_five_values' => $first_five,
            'last_five_values' => $last_five,
            'post_id' => isset($row_data['post_id']) ? $row_data['post_id'] : '',
            'source_variation_id' => isset($row_data['source_variation_id']) ? $row_data['source_variation_id'] : '',
        ]);
    }

    fputcsv($output, $normalized_row);
    return true;
}

function bw_export_validate_row_payload($headers, $row, $row_data, $profile)
{
    if (count($row) !== count($headers)) {
        return [
            'ok' => false,
            'message' => sprintf(
                'CSV column mismatch: row has %d columns but header has %d.',
                (int) count($row),
                (int) count($headers)
            ),
        ];
    }

    $row_type = isset($row_data['row_type']) ? (string) $row_data['row_type'] : '';
    if ($row_type === '') {
        return ['ok' => false, 'message' => 'Missing required field: row_type'];
    }

    $post_id = isset($row_data['post_id']) ? trim((string) $row_data['post_id']) : '';
    $sku = isset($row_data['sku']) ? trim((string) $row_data['sku']) : '';
    if ($post_id === '' && $sku === '') {
        bw_export_log_issue('row_warning', 'Missing both post_id and sku in exported row.', [
            'profile' => $profile,
            'row_type' => $row_type,
        ]);
    }

    if ($row_type === 'product') {
        $post_title = isset($row_data['post_title']) ? trim((string) $row_data['post_title']) : '';
        $post_name = isset($row_data['post_name']) ? trim((string) $row_data['post_name']) : '';
        if ($post_title === '' && $post_name === '') {
            bw_export_log_issue('row_warning', 'Product row missing both post_title and post_name.', [
                'profile' => $profile,
                'post_id' => $post_id,
                'sku' => $sku,
            ]);
        }
    }

    if ($row_type === 'variation') {
        $parent_sku = isset($row_data['parent_sku']) ? trim((string) $row_data['parent_sku']) : '';
        $parent_post_id = isset($row_data['parent_post_id']) ? trim((string) $row_data['parent_post_id']) : '';
        if ($parent_sku === '' && $parent_post_id === '') {
            bw_export_log_issue('row_warning', 'Variation row missing both parent_sku and parent_post_id.', [
                'profile' => $profile,
                'post_id' => $post_id,
                'sku' => $sku,
            ]);
        }
    }

    foreach (bw_export_get_json_columns_for_profile($profile) as $json_column) {
        if (!array_key_exists($json_column, $row_data)) {
            continue;
        }
        $value = trim((string) $row_data[$json_column]);
        if ($value === '') {
            continue;
        }
        json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            bw_export_log_issue('json_warning', sprintf('Invalid JSON in column %s: %s', $json_column, json_last_error_msg()), [
                'profile' => $profile,
                'row_type' => $row_type,
                'post_id' => $post_id,
                'sku' => $sku,
            ]);
        }
    }

    return ['ok' => true, 'message' => ''];
}

function bw_export_get_json_columns_for_profile($profile)
{
    if ($profile !== 'master_csv') {
        return [];
    }

    return [
        'downloadable_files_json',
        'variation_downloadable_files_json',
        'attributes_json',
        'default_attributes_json',
        'variation_attributes_json',
        '_bw_variation_license_col1_json',
        '_bw_variation_license_col2_json',
    ];
}

function bw_export_log_issue($type, $message, $context = [])
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $payload = [
        'type' => (string) $type,
        'message' => (string) $message,
        'context' => (array) $context,
    ];

    error_log('[BW Export] ' . wp_json_encode($payload));
}

function bw_export_blank_csv_row()
{
    return array_fill_keys(bw_export_get_master_template_columns(), '');
}

function bw_export_get_dynamic_meta_columns($product_ids, $include_variations)
{
    global $wpdb;

    $scan_post_ids = bw_export_get_meta_scan_post_ids($product_ids, $include_variations);
    if (empty($scan_post_ids)) {
        return [];
    }

    $meta_keys = [];

    foreach (array_chunk($scan_post_ids, 250) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders)",
            $chunk
        );

        $results = $wpdb->get_col($query);
        if (empty($results)) {
            continue;
        }

        foreach ($results as $meta_key) {
            if (!is_string($meta_key) || bw_export_should_skip_dynamic_meta_key($meta_key)) {
                continue;
            }

            $meta_keys[$meta_key] = true;
        }
    }

    $columns = array_keys($meta_keys);
    natcasesort($columns);

    return array_values($columns);
}

function bw_export_get_meta_scan_post_ids($product_ids, $include_variations)
{
    $scan_post_ids = [];

    foreach ((array) $product_ids as $product_id) {
        $product_id = absint($product_id);
        if ($product_id < 1) {
            continue;
        }

        $scan_post_ids[$product_id] = true;

        if (!$include_variations) {
            continue;
        }

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            continue;
        }

        foreach ((array) $product->get_children() as $variation_id) {
            $variation_id = absint($variation_id);
            if ($variation_id > 0) {
                $scan_post_ids[$variation_id] = true;
            }
        }
    }

    return array_map('intval', array_keys($scan_post_ids));
}

function bw_export_should_skip_dynamic_meta_key($meta_key)
{
    static $blocked_exact = [
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_old_date',
        '_wp_desired_post_slug',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
    ];

    if ($meta_key === '') {
        return true;
    }

    if (in_array($meta_key, $blocked_exact, true)) {
        return true;
    }

    if (strpos($meta_key, '_oembed_') === 0) {
        return true;
    }

    if (strpos($meta_key, '_wp_') === 0) {
        return true;
    }

    if (strpos($meta_key, '_transient_') === 0) {
        return true;
    }

    if (strpos($meta_key, '_elementor_') === 0) {
        return true;
    }

    return false;
}

function bw_export_apply_dynamic_meta_to_row(&$row, $all_meta)
{
    foreach ((array) $all_meta as $meta_key => $values) {
        if (!is_string($meta_key) || bw_export_should_skip_dynamic_meta_key($meta_key)) {
            continue;
        }

        // Keep curated/exported canonical columns authoritative.
        if (array_key_exists($meta_key, $row)) {
            continue;
        }

        $normalized_values = array_map('maybe_unserialize', (array) $values);
        if (count($normalized_values) === 1) {
            $row[$meta_key] = bw_export_dynamic_meta_scalar($meta_key, $normalized_values[0]);
            continue;
        }

        $row[$meta_key] = bw_export_dynamic_meta_scalar($meta_key, $normalized_values);
    }
}

function bw_export_dynamic_meta_scalar($meta_key, $value)
{
    $meta_key = (string) $meta_key;
    if (substr($meta_key, -5) === '_json') {
        return bw_export_force_json_string($value);
    }

    return bw_export_meta_scalar($value);
}

function bw_export_force_json_string($value)
{
    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $encoded = wp_json_encode(json_decode($trimmed, true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($encoded) ? $encoded : '';
        }
    }

    $encoded = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($encoded) ? $encoded : '';
}

function bw_export_build_product_csv_row($product)
{
    $post_id = $product->get_id();
    $post = get_post($post_id);
    $all_meta = get_post_meta($post_id);
    $get_meta = static function ($key) use ($all_meta) {
        return isset($all_meta[$key][0]) ? maybe_unserialize($all_meta[$key][0]) : '';
    };

    $row = bw_export_blank_csv_row();
    $row['row_type'] = 'product';
    $row['parent_post_id'] = '';
    $row['post_id'] = $post_id;
    $row['source_variation_id'] = '';
    $row['post_status'] = $post ? $post->post_status : '';
    $row['status'] = method_exists($product, 'get_status') ? (string) $product->get_status() : '';
    $row['post_title'] = $post ? $post->post_title : '';
    $row['post_name'] = $post ? $post->post_name : '';
    $row['post_content'] = $post ? $post->post_content : '';
    $row['post_excerpt'] = $post ? $post->post_excerpt : '';
    $row['menu_order'] = $post ? (string) $post->menu_order : '';
    $row['sku'] = (string) $product->get_sku();
    $row['woo_product_type'] = (string) $product->get_type();
    $row['featured'] = bw_export_bool_flag($product->is_featured());
    $row['catalog_visibility'] = method_exists($product, 'get_catalog_visibility') ? (string) $product->get_catalog_visibility() : '';
    $row['virtual'] = bw_export_bool_flag($product->is_virtual());
    $row['downloadable'] = bw_export_bool_flag($product->is_downloadable());
    $row['regular_price'] = (string) $product->get_regular_price();
    $row['sale_price'] = (string) $product->get_sale_price();
    $row['current_price'] = (string) $product->get_price();
    $row['price'] = (string) $product->get_price();
    $row['date_on_sale_from'] = bw_export_format_wc_date($product->get_date_on_sale_from());
    $row['date_on_sale_to'] = bw_export_format_wc_date($product->get_date_on_sale_to());
    $row['tax_status'] = (string) $product->get_tax_status();
    $row['tax_class'] = (string) $product->get_tax_class();
    $row['manage_stock'] = bw_export_bool_flag($product->get_manage_stock());
    $row['stock_quantity'] = bw_export_scalar_or_empty($product->get_stock_quantity());
    $row['stock_status'] = (string) $product->get_stock_status();
    $row['backorders'] = (string) $product->get_backorders();
    $row['sold_individually'] = bw_export_bool_flag($product->get_sold_individually());
    $row['weight'] = (string) $product->get_weight();
    $row['length'] = (string) $product->get_length();
    $row['width'] = (string) $product->get_width();
    $row['height'] = (string) $product->get_height();
    $row['shipping_class'] = (string) $product->get_shipping_class();
    $row['purchase_note'] = (string) $product->get_purchase_note();
    $row['reviews_allowed'] = bw_export_bool_flag(comments_open($post_id));
    $row['featured_image'] = bw_export_attachment_url($product->get_image_id());
    $row['image_url'] = $row['featured_image'];
    $row['product_gallery'] = implode(',', bw_export_attachment_urls($product->get_gallery_image_ids()));
    $row['product_cat'] = implode(',', bw_export_get_product_term_slugs($post_id, 'product_cat'));
    $row['product_subcategories'] = implode(',', bw_export_get_product_subcategory_slugs($post_id));
    $row['product_tag'] = implode(',', bw_export_get_product_term_slugs($post_id, 'product_tag'));
    $row['upsell_skus'] = implode(',', bw_export_resolve_product_skus($product->get_upsell_ids()));
    $row['cross_sell_skus'] = implode(',', bw_export_resolve_product_skus($product->get_cross_sell_ids()));
    $row['grouped_skus'] = implode(',', bw_export_get_grouped_child_skus($product));
    $row['external_url'] = $product->is_type('external') ? (string) $product->get_product_url() : '';
    $row['external_button_text'] = $product->is_type('external') ? (string) $product->get_button_text() : '';
    $row['download_limit'] = bw_export_scalar_or_empty($product->get_download_limit());
    $row['download_expiry'] = bw_export_scalar_or_empty($product->get_download_expiry());
    $row['downloadable_files_json'] = bw_export_json_array(bw_export_get_downloads_payload($product));
    $row['variation_downloadable_files_json'] = bw_export_json_array([]);
    $row['attributes_json'] = bw_export_json(bw_export_build_attributes_payload($product));
    $row['default_attributes_json'] = bw_export_json($product->is_type('variable') ? $product->get_default_attributes() : []);

    foreach ([
        '_bw_product_type',
        '_bw_texts_color',
        '_bw_showcase_title',
        '_bw_showcase_description',
        '_bw_file_size',
        '_bw_assets_count',
        '_bw_formats',
        '_bw_info_1',
        '_bw_info_2',
        '_product_button_text',
        '_product_button_link',
        '_bw_showcase_linked_product',
        '_bw_biblio_title',
        '_bw_biblio_author',
        '_bw_biblio_publisher',
        '_bw_biblio_year',
        '_bw_biblio_language',
        '_bw_biblio_binding',
        '_bw_biblio_pages',
        '_bw_biblio_edition',
        '_bw_biblio_condition',
        '_bw_biblio_location',
        '_print_artist',
        '_print_publisher',
        '_print_year',
        '_print_technique',
        '_print_material',
        '_print_plate_size',
        '_print_condition',
        '_digital_total_assets',
        '_digital_assets_list',
        '_digital_file_size',
        '_digital_formats',
        '_bw_artist_name',
        '_digital_artist_name',
        '_digital_source',
        '_digital_publisher',
        '_digital_year',
        '_digital_technique',
        '_bw_compatibility_configured',
        '_bw_compatibility_adobe_illustrator_photoshop',
        '_bw_compatibility_figma_sketch_adobe_xd',
        '_bw_compatibility_affinity_designer_photo',
        '_bw_compatibility_coreldraw_inkscape',
        '_bw_compatibility_canva_powerpoint',
        '_bw_compatibility_cricut_silhouette',
        '_bw_compatibility_blender_cinema4d',
        '_product_size_mb',
        '_product_assets_count',
        '_product_formats',
        '_product_color',
    ] as $meta_key) {
        $row[$meta_key] = bw_export_meta_scalar($get_meta($meta_key));
    }

    $row['_bw_showcase_image'] = bw_export_media_meta_value($get_meta('_bw_showcase_image'));
    $row['_bw_slider_hover_image'] = bw_export_media_meta_value($get_meta('_bw_slider_hover_image'));
    $row['_bw_slider_hover_video'] = bw_export_media_meta_value($get_meta('_bw_slider_hover_video'));
    $row['_product_showcase_image'] = bw_export_media_meta_value($get_meta('_product_showcase_image'));
    bw_export_apply_dynamic_meta_to_row($row, $all_meta);

    return $row;
}

function bw_export_build_variation_csv_row($variation, $parent_product)
{
    $variation_id = $variation->get_id();
    $post = get_post($variation_id);
    $all_meta = get_post_meta($variation_id);
    $get_meta = static function ($key) use ($all_meta) {
        return isset($all_meta[$key][0]) ? maybe_unserialize($all_meta[$key][0]) : '';
    };

    $row = bw_export_blank_csv_row();
    $parent_product_id = (int) $parent_product->get_id();
    $parent_sku = bw_export_get_product_or_fallback_sku($parent_product);
    $row['row_type'] = 'variation';
    $row['parent_sku'] = $parent_sku;
    $row['parent_post_id'] = $parent_product_id > 0 ? (string) $parent_product_id : '';
    $row['post_id'] = $variation_id;
    $row['source_variation_id'] = (string) $variation_id;
    $row['post_status'] = $post ? $post->post_status : '';
    $row['status'] = (string) $variation->get_status();
    $row['post_title'] = $post ? $post->post_title : '';
    $row['post_name'] = $post ? $post->post_name : '';
    $row['post_content'] = $post ? $post->post_content : '';
    $row['post_excerpt'] = $post ? $post->post_excerpt : '';
    $row['menu_order'] = $post ? (string) $post->menu_order : '';
    $row['sku'] = bw_export_get_variation_or_fallback_sku($variation, $parent_product_id);
    $row['woo_product_type'] = 'variation';
    $row['virtual'] = bw_export_bool_flag($variation->is_virtual());
    $row['downloadable'] = bw_export_bool_flag($variation->is_downloadable());
    $row['regular_price'] = (string) $variation->get_regular_price();
    $row['sale_price'] = (string) $variation->get_sale_price();
    $row['current_price'] = (string) $variation->get_price();
    $row['price'] = (string) $variation->get_price();
    $row['date_on_sale_from'] = bw_export_format_wc_date($variation->get_date_on_sale_from());
    $row['date_on_sale_to'] = bw_export_format_wc_date($variation->get_date_on_sale_to());
    $row['tax_status'] = (string) $variation->get_tax_status();
    $row['tax_class'] = (string) $variation->get_tax_class();
    $row['manage_stock'] = bw_export_bool_flag($variation->get_manage_stock());
    $row['stock_quantity'] = bw_export_scalar_or_empty($variation->get_stock_quantity());
    $row['stock_status'] = (string) $variation->get_stock_status();
    $row['backorders'] = (string) $variation->get_backorders();
    $row['weight'] = (string) $variation->get_weight();
    $row['length'] = (string) $variation->get_length();
    $row['width'] = (string) $variation->get_width();
    $row['height'] = (string) $variation->get_height();
    $row['featured_image'] = bw_export_attachment_url($variation->get_image_id());
    $row['image_url'] = $row['featured_image'];
    $row['download_limit'] = bw_export_scalar_or_empty($variation->get_download_limit());
    $row['download_expiry'] = bw_export_scalar_or_empty($variation->get_download_expiry());
    $variation_downloads = bw_export_get_downloads_payload($variation);
    $row['downloadable_files_json'] = bw_export_json_array($variation_downloads);
    $row['variation_downloadable_files_json'] = bw_export_json_array($variation_downloads);
    $row['variation_attributes_json'] = bw_export_json_array(bw_export_normalize_variation_attributes($variation));
    $row['variation_regular_price'] = (string) $variation->get_regular_price();
    $row['variation_sale_price'] = (string) $variation->get_sale_price();
    $row['variation_stock_quantity'] = bw_export_scalar_or_empty($variation->get_stock_quantity());
    $row['variation_stock_status'] = (string) $variation->get_stock_status();
    $row['variation_manage_stock'] = bw_export_bool_flag($variation->get_manage_stock());
    $row['variation_backorders'] = (string) $variation->get_backorders();
    $row['variation_weight'] = (string) $variation->get_weight();
    $row['variation_length'] = (string) $variation->get_length();
    $row['variation_width'] = (string) $variation->get_width();
    $row['variation_height'] = (string) $variation->get_height();
    $row['variation_description'] = (string) $variation->get_description();
    $row['_bw_variation_license_col1_json'] = bw_export_json($get_meta('_bw_variation_license_col1'));
    $row['_bw_variation_license_col2_json'] = bw_export_json($get_meta('_bw_variation_license_col2'));
    bw_export_apply_dynamic_meta_to_row($row, $all_meta);

    return $row;
}

function bw_export_build_woo_native_product_row($product)
{
    $post_id = $product->get_id();
    $post = get_post($post_id);
    $row = array_fill_keys(bw_export_get_woo_native_columns(), '');
    $primary_attribute = bw_export_get_woo_native_primary_attribute($product);
    $product_sku = bw_export_get_product_or_fallback_sku($product);

    $row['ID'] = (string) $post_id;
    $row['Type'] = (string) $product->get_type();
    $row['SKU'] = $product_sku;
    $row['Name'] = $post ? (string) $post->post_title : '';
    $row['Published'] = ($post && $post->post_status === 'publish') ? '1' : '0';
    $row['Is featured?'] = $product->is_featured() ? '1' : '0';
    $row['Visibility in catalog'] = method_exists($product, 'get_catalog_visibility') ? (string) $product->get_catalog_visibility() : '';
    $row['Short description'] = $post ? (string) $post->post_excerpt : '';
    $row['Description'] = $post ? (string) $post->post_content : '';
    $row['Date sale price starts'] = bw_export_format_wc_date($product->get_date_on_sale_from());
    $row['Date sale price ends'] = bw_export_format_wc_date($product->get_date_on_sale_to());
    $row['Tax status'] = (string) $product->get_tax_status();
    $row['Tax class'] = (string) $product->get_tax_class();
    $row['In stock?'] = $product->is_in_stock() ? '1' : '0';
    $row['Stock'] = bw_export_scalar_or_empty($product->get_stock_quantity());
    $row['Backorders allowed?'] = $product->get_backorders() === 'no' ? '0' : '1';
    $row['Sold individually?'] = $product->get_sold_individually() ? '1' : '0';
    $row['Weight (kg)'] = (string) $product->get_weight();
    $row['Length (cm)'] = (string) $product->get_length();
    $row['Width (cm)'] = (string) $product->get_width();
    $row['Height (cm)'] = (string) $product->get_height();
    $row['Allow customer reviews?'] = comments_open($post_id) ? '1' : '0';
    $row['Purchase note'] = (string) $product->get_purchase_note();
    $row['Sale price'] = (string) $product->get_sale_price();
    $row['Regular price'] = (string) $product->get_regular_price();
    $row['Categories'] = implode(', ', bw_export_get_product_category_paths($post_id));
    $row['Tags'] = implode(', ', bw_export_get_product_term_names($post_id, 'product_tag'));
    $row['Shipping class'] = (string) $product->get_shipping_class();
    $row['Images'] = implode(', ', bw_export_attachment_urls(array_merge([$product->get_image_id()], $product->get_gallery_image_ids())));
    $row['Download limit'] = bw_export_scalar_or_empty($product->get_download_limit());
    $row['Download expiry days'] = bw_export_scalar_or_empty($product->get_download_expiry());
    $row['Parent'] = '';
    $row['Grouped products'] = implode(', ', bw_export_resolve_product_skus($product->is_type('grouped') ? $product->get_children() : []));
    $row['Upsells'] = implode(', ', bw_export_resolve_product_skus($product->get_upsell_ids()));
    $row['Cross-sells'] = implode(', ', bw_export_resolve_product_skus($product->get_cross_sell_ids()));
    $row['External URL'] = $product->is_type('external') ? (string) $product->get_product_url() : '';
    $row['Button text'] = $product->is_type('external') ? (string) $product->get_button_text() : '';
    $row['Position'] = $post ? (string) $post->menu_order : '';
    $row['Attribute 1 name'] = $primary_attribute['name'];
    $row['Attribute 1 value(s)'] = $primary_attribute['values'];
    $row['Attribute 1 visible'] = $primary_attribute['visible'];
    $row['Attribute 1 global'] = $primary_attribute['global'];
    $row['Attribute 1 default'] = $primary_attribute['default'];

    $row['row_type'] = 'product';
    $row['parent_post_id'] = '';
    $row['post_id'] = (string) $post_id;
    $row['post_title'] = $row['Name'];
    $row['post_name'] = $post ? (string) $post->post_name : '';
    $row['sku'] = $product_sku;

    return $row;
}

function bw_export_build_woo_native_variation_row($variation, $parent_product)
{
    $post = get_post($variation->get_id());
    $row = array_fill_keys(bw_export_get_woo_native_columns(), '');
    $parent_product_id = (int) $parent_product->get_id();
    $parent_sku = bw_export_get_product_or_fallback_sku($parent_product);
    $variation_sku = bw_export_get_variation_or_fallback_sku($variation, $parent_product_id);
    $parent_reference = $parent_sku !== '' ? $parent_sku : ($parent_product_id > 0 ? 'id:' . $parent_product_id : '');
    $primary_attribute = bw_export_get_woo_native_primary_attribute($parent_product);
    $variation_attribute_value = bw_export_get_variation_primary_attribute_value($variation, $primary_attribute);

    $row['ID'] = (string) $variation->get_id();
    $row['Type'] = 'variation';
    $row['SKU'] = $variation_sku;
    $row['Name'] = $post ? (string) $post->post_title : '';
    $row['Published'] = ($post && $post->post_status === 'publish') ? '1' : '0';
    $row['Tax status'] = (string) $variation->get_tax_status();
    $row['Tax class'] = (string) $variation->get_tax_class();
    $row['In stock?'] = $variation->is_in_stock() ? '1' : '0';
    $row['Stock'] = bw_export_scalar_or_empty($variation->get_stock_quantity());
    $row['Backorders allowed?'] = $variation->get_backorders() === 'no' ? '0' : '1';
    $row['Weight (kg)'] = (string) $variation->get_weight();
    $row['Length (cm)'] = (string) $variation->get_length();
    $row['Width (cm)'] = (string) $variation->get_width();
    $row['Height (cm)'] = (string) $variation->get_height();
    $row['Sale price'] = (string) $variation->get_sale_price();
    $row['Regular price'] = (string) $variation->get_regular_price();
    $row['Images'] = implode(', ', bw_export_attachment_urls([$variation->get_image_id()]));
    $row['Download limit'] = bw_export_scalar_or_empty($variation->get_download_limit());
    $row['Download expiry days'] = bw_export_scalar_or_empty($variation->get_download_expiry());
    $row['Parent'] = $parent_reference;
    $row['Position'] = $post ? (string) $post->menu_order : '';
    $row['Attribute 1 name'] = $primary_attribute['name'];
    $row['Attribute 1 value(s)'] = $variation_attribute_value;
    $row['Attribute 1 visible'] = '1';
    $row['Attribute 1 global'] = $primary_attribute['global'];

    $row['row_type'] = 'variation';
    $row['parent_sku'] = $parent_sku;
    $row['parent_post_id'] = $parent_product_id > 0 ? (string) $parent_product_id : '';
    $row['post_id'] = (string) $variation->get_id();
    $row['post_title'] = $row['Name'];
    $row['post_name'] = $post ? (string) $post->post_name : '';
    $row['sku'] = $variation_sku;

    return $row;
}

function bw_export_get_product_or_fallback_sku($product)
{
    $product_id = (int) $product->get_id();
    $sku = trim((string) $product->get_sku());
    if ($sku !== '') {
        return $sku;
    }

    return $product_id > 0 ? 'bw-' . $product_id : '';
}

function bw_export_get_variation_or_fallback_sku($variation, $parent_product_id)
{
    $variation_id = (int) $variation->get_id();
    $sku = trim((string) $variation->get_sku());
    if ($sku !== '') {
        return $sku;
    }

    if ($parent_product_id > 0 && $variation_id > 0) {
        return 'bw-' . $parent_product_id . '-' . $variation_id;
    }

    return $variation_id > 0 ? 'bw-var-' . $variation_id : '';
}

function bw_export_get_product_term_names($product_id, $taxonomy)
{
    $terms = wp_get_post_terms($product_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $names = [];
    foreach ($terms as $term) {
        if (!empty($term->name)) {
            $names[] = (string) $term->name;
        }
    }

    $names = array_values(array_unique($names));
    natcasesort($names);
    return array_values($names);
}

function bw_export_get_product_category_paths($product_id)
{
    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $paths = [];
    foreach ($terms as $term) {
        if (!$term || is_wp_error($term)) {
            continue;
        }

        $ancestors = array_reverse(get_ancestors((int) $term->term_id, 'product_cat'));
        $names = [];

        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term((int) $ancestor_id, 'product_cat');
            if ($ancestor && !is_wp_error($ancestor) && $ancestor->name !== '') {
                $names[] = (string) $ancestor->name;
            }
        }

        if ($term->name !== '') {
            $names[] = (string) $term->name;
        }

        if (!empty($names)) {
            $paths[] = implode(' > ', $names);
        }
    }

    $paths = array_values(array_unique($paths));
    natcasesort($paths);
    return array_values($paths);
}

function bw_export_normalize_woo_attribute_label($attribute_name)
{
    $attribute_name = (string) $attribute_name;
    if ($attribute_name === '') {
        return '';
    }

    if (strpos($attribute_name, 'attribute_pa_') === 0) {
        return (string) wc_attribute_label(str_replace('attribute_', '', $attribute_name));
    }

    if (strpos($attribute_name, 'pa_') === 0) {
        return (string) wc_attribute_label($attribute_name);
    }

    if (strpos($attribute_name, 'attribute_') === 0) {
        $attribute_name = str_replace('attribute_', '', $attribute_name);
    }

    $attribute_name = str_replace(['_', '-'], ' ', $attribute_name);
    return ucwords(trim($attribute_name));
}

function bw_export_get_woo_native_primary_attribute($product)
{
    $payload = bw_export_build_attributes_payload($product);
    $first_attribute = !empty($payload) ? $payload[0] : [];
    $raw_name = isset($first_attribute['name']) ? (string) $first_attribute['name'] : '';
    $values = isset($first_attribute['options']) && is_array($first_attribute['options'])
        ? implode(', ', array_map('strval', $first_attribute['options']))
        : '';
    $is_global = ($raw_name !== '' && strpos($raw_name, 'pa_') === 0) ? '1' : '0';
    $default_value = '';
    $source_key = '';

    if ($raw_name !== '') {
        $source_key = $is_global === '1'
            ? 'attribute_' . $raw_name
            : 'attribute_' . sanitize_title($raw_name);
    }

    if ($product->is_type('variable')) {
        $defaults = $product->get_default_attributes();
        if (!empty($defaults) && $raw_name !== '') {
            if (isset($defaults[$raw_name])) {
                $default_value = (string) $defaults[$raw_name];
            } else {
                $short_name = str_replace('pa_', '', $raw_name);
                if (isset($defaults[$short_name])) {
                    $default_value = (string) $defaults[$short_name];
                }
            }
        }
    }

    return [
        'name' => bw_export_normalize_woo_attribute_label($raw_name),
        'raw_name' => $raw_name,
        'values' => $values,
        'visible' => !empty($first_attribute['visible']) ? '1' : '0',
        'global' => $is_global,
        'default' => $default_value,
        'source_key' => $source_key,
    ];
}

function bw_export_get_variation_primary_attribute_value($variation, $primary_attribute)
{
    $variation_attributes = $variation->get_variation_attributes();
    if (empty($variation_attributes) || !is_array($variation_attributes)) {
        return '';
    }

    $source_key = isset($primary_attribute['source_key']) ? (string) $primary_attribute['source_key'] : '';
    if ($source_key !== '' && array_key_exists($source_key, $variation_attributes)) {
        return (string) $variation_attributes[$source_key];
    }

    $first_key = array_key_first($variation_attributes);
    if ($first_key === null) {
        return '';
    }

    return isset($variation_attributes[$first_key]) ? (string) $variation_attributes[$first_key] : '';
}

function bw_export_bool_flag($value)
{
    return !empty($value) ? 'yes' : 'no';
}

function bw_export_scalar_or_empty($value)
{
    if ($value === null || $value === '') {
        return '';
    }

    return is_scalar($value) ? (string) $value : '';
}

function bw_export_format_wc_date($value)
{
    if ($value instanceof WC_DateTime) {
        return $value->date_i18n('Y-m-d');
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (is_string($value) && $value !== '') {
        return $value;
    }

    return '';
}

function bw_export_attachment_url($attachment_id)
{
    $attachment_id = absint($attachment_id);
    if ($attachment_id < 1) {
        return '';
    }

    $url = wp_get_attachment_url($attachment_id);
    return $url ? (string) $url : '';
}

function bw_export_attachment_urls($attachment_ids)
{
    $urls = [];
    foreach ((array) $attachment_ids as $attachment_id) {
        $url = bw_export_attachment_url($attachment_id);
        if ($url !== '') {
            $urls[] = $url;
        }
    }

    return $urls;
}

function bw_export_get_product_term_slugs($product_id, $taxonomy)
{
    $terms = wp_get_post_terms($product_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $slugs = [];
    foreach ($terms as $term) {
        if (!empty($term->slug)) {
            $slugs[] = (string) $term->slug;
        }
    }

    return $slugs;
}

function bw_export_get_product_subcategory_slugs($product_id)
{
    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $slugs = [];
    foreach ($terms as $term) {
        if ((int) $term->parent > 0 && !empty($term->slug)) {
            $slugs[] = (string) $term->slug;
        }
    }

    return $slugs;
}

function bw_export_resolve_product_skus($product_ids)
{
    $values = [];
    foreach ((array) $product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            continue;
        }

        $sku = (string) $product->get_sku();
        $values[] = $sku !== '' ? $sku : (string) $product_id;
    }

    return $values;
}

function bw_export_get_grouped_child_skus($product)
{
    if (!$product->is_type('grouped')) {
        return [];
    }

    return bw_export_resolve_product_skus($product->get_children());
}

function bw_export_get_downloads_payload($product)
{
    if (!method_exists($product, 'get_downloads')) {
        return [];
    }

    $downloads = $product->get_downloads();
    if (empty($downloads)) {
        return [];
    }

    $payload = [];
    foreach ($downloads as $download_id => $download) {
        if (!is_object($download)) {
            continue;
        }

        $payload[] = [
            'id' => (string) $download_id,
            'name' => method_exists($download, 'get_name') ? (string) $download->get_name() : '',
            'file_url' => method_exists($download, 'get_file') ? (string) $download->get_file() : '',
            'file_type' => bw_export_detect_download_file_type(method_exists($download, 'get_file') ? (string) $download->get_file() : ''),
            'file_size' => null,
        ];
    }

    return $payload;
}

function bw_export_detect_download_file_type($file_url)
{
    $file_url = trim((string) $file_url);
    if ($file_url === '') {
        return '';
    }

    $path = wp_parse_url($file_url, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '';
    }

    $extension = pathinfo($path, PATHINFO_EXTENSION);
    return is_string($extension) ? strtolower($extension) : '';
}

function bw_export_normalize_variation_attributes($variation)
{
    $raw_attributes = method_exists($variation, 'get_attributes') ? (array) $variation->get_attributes() : [];
    if (empty($raw_attributes)) {
        return [];
    }

    $normalized = [];
    foreach ($raw_attributes as $attribute_key => $attribute_value) {
        if (!is_scalar($attribute_value)) {
            continue;
        }

        $value = trim((string) $attribute_value);
        if ($value === '') {
            continue;
        }

        $key = bw_export_normalize_variation_attribute_key($attribute_key);
        if ($key === '') {
            continue;
        }

        $resolved = bw_export_resolve_variation_attribute_value($attribute_key, $value);
        $normalized[$key] = $resolved;
    }

    return $normalized;
}

function bw_export_normalize_variation_attribute_key($attribute_key)
{
    $key = (string) $attribute_key;
    if (strpos($key, 'attribute_') === 0) {
        $key = substr($key, 10);
    }
    if (strpos($key, 'pa_') === 0) {
        $key = substr($key, 3);
    }

    return sanitize_title($key);
}

function bw_export_resolve_variation_attribute_value($attribute_key, $value)
{
    $taxonomy = (string) $attribute_key;
    if (strpos($taxonomy, 'attribute_') === 0) {
        $taxonomy = substr($taxonomy, 10);
    }

    if (taxonomy_exists($taxonomy)) {
        $term = get_term_by('slug', $value, $taxonomy);
        if ($term && !is_wp_error($term) && !empty($term->name)) {
            return (string) $term->name;
        }
    }

    return (string) $value;
}

function bw_export_build_attributes_payload($product)
{
    $attributes = $product->get_attributes();
    if (empty($attributes)) {
        return [];
    }

    $payload = [];
    foreach ($attributes as $attribute) {
        if (!$attribute instanceof WC_Product_Attribute) {
            continue;
        }

        $options = [];
        if ($attribute->is_taxonomy()) {
            foreach ((array) $attribute->get_options() as $term_id) {
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) {
                    $options[] = !empty($term->slug) ? (string) $term->slug : (string) $term->name;
                }
            }
        } else {
            foreach ((array) $attribute->get_options() as $option) {
                if (is_scalar($option) && $option !== '') {
                    $options[] = (string) $option;
                }
            }
        }

        $payload[] = [
            'name' => (string) $attribute->get_name(),
            'label' => $attribute->is_taxonomy() ? wc_attribute_label($attribute->get_name()) : (string) $attribute->get_name(),
            'options' => $options,
            'visible' => !empty($attribute->get_visible()),
            'variation' => !empty($attribute->get_variation()),
            'position' => (int) $attribute->get_position(),
        ];
    }

    return $payload;
}

function bw_export_json($value)
{
    if (empty($value)) {
        return '';
    }

    $encoded = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($encoded) ? $encoded : '';
}

function bw_export_json_array($value)
{
    if (!is_array($value)) {
        $value = [];
    }

    $encoded = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($encoded) ? $encoded : '[]';
}

function bw_export_meta_scalar($value)
{
    if (is_array($value) || is_object($value)) {
        return bw_export_json($value);
    }

    return is_scalar($value) ? (string) $value : '';
}

function bw_export_media_meta_value($value)
{
    if (is_numeric($value)) {
        $url = bw_export_attachment_url((int) $value);
        if ($url !== '') {
            return $url;
        }
    }

    return bw_export_meta_scalar($value);
}
