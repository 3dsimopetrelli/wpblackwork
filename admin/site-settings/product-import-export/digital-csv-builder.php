<?php
/**
 * Blackwork Site Settings — digital-asset CSV builder (JSON-to-CSV) engine.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bw_digital_csv_builder_user_can_manage()
{
    return current_user_can('manage_woocommerce') || current_user_can('manage_options');
}

function bw_digital_csv_builder_allowed_subcategories()
{
    return [
        'Botany',
        'Mammals',
        'Birds',
        'Fish',
        'Insects',
        'Reptiles & Amphibians',
        'Paleontology',
        'Skeletal Anatomy',
        'Muscular Anatomy',
        'Internal Anatomy',
        'Surgery',
        'Medical Conditions',
        'Physiognomy / Phrenology',
        'Astronomy',
        'Mathematics / Geometry',
        'Physics',
        'Chemistry',
        'Scientific Instruments',
        'Maps',
        'Exploration / Voyages',
        'Ethnography',
        'Landscapes',
        'Religious Scenes',
        'Mythology',
        'Allegory',
        'Symbolism / Iconology',
        'Architecture',
        'Ornament / Patterns',
        'Furniture',
        'Interiors',
        'Weapons & Armor',
        'Tools & Instruments',
        'Decorative Objects',
        'Everyday Objects',
        'Ships',
        'Vehicles (Land)',
        'Aviation',
        'Machinery',
        'Heraldry / Coats of Arms',
        'Emblems / Seals',
        'Letterforms / Calligraphy',
        'Ornaments / Borders',
    ];
}

function bw_digital_csv_builder_template_path()
{
    $templates = bw_product_import_template_registry();
    if (empty($templates['digital_csv']['path'])) {
        return '';
    }

    return (string) $templates['digital_csv']['path'];
}

function bw_digital_csv_builder_parse_template()
{
    $template_path = bw_digital_csv_builder_template_path();
    if ($template_path === '' || !file_exists($template_path) || !is_readable($template_path)) {
        return new WP_Error('bw_digital_csv_template_missing', __('Digital CSV template is not available.', 'bw'));
    }

    $rows = [];
    $handle = fopen($template_path, 'r');
    if (!$handle) {
        return new WP_Error('bw_digital_csv_template_open', __('Digital CSV template could not be opened.', 'bw'));
    }

    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);

    if (count($rows) !== 4) {
        return new WP_Error('bw_digital_csv_template_shape', __('Digital CSV template must contain exactly 1 header row and 3 data rows.', 'bw'));
    }

    $headers = isset($rows[0]) ? array_map('strval', (array) $rows[0]) : [];
    $header_count = count($headers);
    if ($header_count < 1) {
        return new WP_Error('bw_digital_csv_template_headers', __('Digital CSV template headers are empty.', 'bw'));
    }

    $assoc_rows = [];
    for ($i = 1; $i < 4; $i++) {
        if (count($rows[$i]) !== $header_count) {
            return new WP_Error('bw_digital_csv_template_columns', __('Digital CSV template rows do not match the header column count.', 'bw'));
        }
        $assoc_rows[] = array_combine($headers, $rows[$i]);
    }

    return [
        'headers' => $headers,
        'rows' => $assoc_rows,
    ];
}

function bw_digital_csv_builder_read_complete_json_input()
{
    $raw_json = isset($_POST['bw_digital_csv_complete_json']) ? (string) wp_unslash($_POST['bw_digital_csv_complete_json']) : '';

    if (!empty($_FILES['bw_digital_csv_complete_json_file']['tmp_name']) && is_uploaded_file($_FILES['bw_digital_csv_complete_json_file']['tmp_name'])) {
        $uploaded_json = file_get_contents($_FILES['bw_digital_csv_complete_json_file']['tmp_name']);
        if ($uploaded_json !== false && trim((string) $uploaded_json) !== '') {
            $raw_json = (string) $uploaded_json;
        }
    }

    return $raw_json;
}

function bw_digital_csv_builder_creative_value_has_forbidden_token($value)
{
    $value = (string) $value;

    return preg_match(
        '~(^meta:|^_bw_|^_digital_|^row_type$|^parent_sku$|^sku$|^post_name$|^featured_image$|^product_gallery$|^variation_image$|^variation_download_url$|^variation_download_name$|^variation_regular_price$|download_zip_url|featured image url|download zip url|showcase_image|hover_image|hover_video|variation_|meta:_|_bw_|_digital_)~i',
        $value
    ) === 1;
}

function bw_digital_csv_builder_validate_complete_json($raw_json)
{
    $raw_json = (string) $raw_json;
    if (trim($raw_json) === '') {
        return new WP_Error('bw_digital_csv_json_empty', __('Complete Product JSON is required.', 'bw'));
    }

    $decoded = json_decode($raw_json, true);
    if (!is_array($decoded)) {
        return new WP_Error('bw_digital_csv_json_invalid', __('Complete Product JSON could not be parsed.', 'bw'));
    }

    $top_level_keys = [
        'source_data',
        'creative_data',
    ];

    $unknown_keys = array_diff(array_keys($decoded), $top_level_keys);
    if (!empty($unknown_keys)) {
        return new WP_Error(
            'bw_digital_csv_json_unknown_top_level_keys',
            sprintf(
                /* translators: %s: unexpected JSON keys */
                __('Complete Product JSON contains unsupported top-level keys: %s', 'bw'),
                implode(', ', $unknown_keys)
            )
        );
    }

    foreach ($top_level_keys as $required_top_level_key) {
        if (!array_key_exists($required_top_level_key, $decoded)) {
            return new WP_Error(
                'bw_digital_csv_json_missing_top_level_key',
                sprintf(
                    /* translators: %s: missing JSON key */
                    __('Complete Product JSON is missing required top-level key: %s', 'bw'),
                    $required_top_level_key
                )
            );
        }
    }

    if (!is_array($decoded['source_data'])) {
        return new WP_Error('bw_digital_csv_json_source_data_type', __('Complete Product JSON source_data must be an object.', 'bw'));
    }

    if (!is_array($decoded['creative_data'])) {
        return new WP_Error('bw_digital_csv_json_creative_data_type', __('Complete Product JSON creative_data must be an object.', 'bw'));
    }

    $allowed_source_keys = [
        'product_title',
        'digital_assets_list',
        'featured_image_url',
        'product_gallery',
        'hover_image_url',
        'showcase_image_urls',
        'hover_video_url',
        'year',
        'author_artist',
        'total_assets',
        'formats',
        'source_notes',
        'product_context',
        'technique',
        'price',
        'price_commercial',
        'price_extended',
        'download_zip_url',
        'download_zip_url_commercial',
        'download_zip_url_extended',
    ];
    $allowed_creative_keys = [
        'post_title',
        'post_content',
        'post_excerpt',
        'tags',
        'product_subcategories',
        'visual_analysis_notes',
    ];
    $required_source_keys = $allowed_source_keys;
    $required_creative_keys = [
        'post_title',
        'post_content',
        'post_excerpt',
        'tags',
        'product_subcategories',
    ];

    $unknown_source_keys = array_diff(array_keys($decoded['source_data']), $allowed_source_keys);
    if (!empty($unknown_source_keys)) {
        return new WP_Error(
            'bw_digital_csv_json_source_data_unknown_keys',
            sprintf(
                __('Complete Product JSON source_data contains unsupported keys: %s', 'bw'),
                implode(', ', $unknown_source_keys)
            )
        );
    }

    $unknown_creative_keys = array_diff(array_keys($decoded['creative_data']), $allowed_creative_keys);
    if (!empty($unknown_creative_keys)) {
        return new WP_Error(
            'bw_digital_csv_json_creative_data_unknown_keys',
            sprintf(
                __('Complete Product JSON creative_data contains unsupported keys: %s', 'bw'),
                implode(', ', $unknown_creative_keys)
            )
        );
    }

    foreach ($required_source_keys as $required_source_key) {
        if (!array_key_exists($required_source_key, $decoded['source_data'])) {
            return new WP_Error(
                'bw_digital_csv_json_source_data_missing_key',
                sprintf(
                    __('Complete Product JSON source_data is missing required key: %s', 'bw'),
                    $required_source_key
                )
            );
        }
    }

    foreach ($required_creative_keys as $required_creative_key) {
        if (!array_key_exists($required_creative_key, $decoded['creative_data'])) {
            return new WP_Error(
                'bw_digital_csv_json_creative_data_missing_key',
                sprintf(
                    __('Complete Product JSON creative_data is missing required key: %s', 'bw'),
                    $required_creative_key
                )
            );
        }
    }

    if (!is_array($decoded['source_data']['product_gallery'])) {
        return new WP_Error('bw_digital_csv_json_gallery_type', __('Complete Product JSON source_data.product_gallery must be an array.', 'bw'));
    }

    if (!is_array($decoded['source_data']['showcase_image_urls'])) {
        return new WP_Error('bw_digital_csv_json_showcase_type', __('Complete Product JSON source_data.showcase_image_urls must be an array.', 'bw'));
    }

    if (!is_array($decoded['creative_data']['tags'])) {
        return new WP_Error('bw_digital_csv_json_tags_type', __('Complete Product JSON creative_data.tags must be an array.', 'bw'));
    }

    if (!is_array($decoded['creative_data']['product_subcategories'])) {
        return new WP_Error('bw_digital_csv_json_subcategories_type', __('Complete Product JSON creative_data.product_subcategories must be an array.', 'bw'));
    }

    foreach ($decoded['creative_data'] as $creative_key => $creative_value) {
        if (is_array($creative_value)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($creative_value));
            foreach ($iterator as $index => $value) {
                if (!is_scalar($value)) {
                    continue;
                }

                $value = (string) $value;
                if (preg_match('~https?://|www\.dropbox\.com|dl\.dropboxusercontent\.com~i', $value)) {
                    return new WP_Error('bw_digital_csv_json_contains_urls', __('Complete Product JSON creative_data must not contain media or download URLs.', 'bw'));
                }
                if (bw_digital_csv_builder_creative_value_has_forbidden_token($value)) {
                    return new WP_Error(
                        'bw_digital_csv_json_creative_data_forbidden_array_item',
                        sprintf(
                            __('creative_data.%1$s item %2$d contains a forbidden CSV/meta token.', 'bw'),
                            $creative_key,
                            ((int) $index) + 1
                        )
                    );
                }
            }
            continue;
        }

        if (!is_scalar($creative_value)) {
            return new WP_Error(
                'bw_digital_csv_json_creative_scalar_expected',
                sprintf(__('Complete Product JSON creative_data.%s must be a string or array.', 'bw'), $creative_key)
            );
        }

        $creative_value = (string) $creative_value;
        if (preg_match('~https?://|www\.dropbox\.com|dl\.dropboxusercontent\.com~i', $creative_value)) {
            return new WP_Error('bw_digital_csv_json_contains_urls', __('Complete Product JSON creative_data must not contain media or download URLs.', 'bw'));
        }
        if (bw_digital_csv_builder_creative_value_has_forbidden_token($creative_value)) {
            return new WP_Error('bw_digital_csv_json_creative_data_forbidden_content', __('Complete Product JSON creative_data must not contain CSV fields, WordPress meta keys, or ZIP/price field names.', 'bw'));
        }
    }

    $tags = array_values(array_filter(array_map(static function ($tag) {
        return trim((string) $tag);
    }, $decoded['creative_data']['tags']), static function ($tag) {
        return $tag !== '';
    }));

    if (count($tags) !== 10) {
        return new WP_Error('bw_digital_csv_json_tags_count', __('Complete Product JSON creative_data.tags must contain exactly 10 items.', 'bw'));
    }

    $allowed_subcategories = bw_digital_csv_builder_allowed_subcategories();
    $subcategories = array_values(array_filter(array_map(static function ($subcategory) {
        return trim((string) $subcategory);
    }, $decoded['creative_data']['product_subcategories']), static function ($subcategory) {
        return $subcategory !== '';
    }));

    $invalid_subcategories = array_values(array_diff($subcategories, $allowed_subcategories));
    if (!empty($invalid_subcategories)) {
        return new WP_Error(
            'bw_digital_csv_json_subcategories_invalid',
            sprintf(
                /* translators: %s: invalid subcategories */
                __('Complete Product JSON creative_data contains invalid Digital subcategories: %s', 'bw'),
                implode(', ', $invalid_subcategories)
            )
        );
    }

    if (count($subcategories) > 5) {
        return new WP_Error('bw_digital_csv_json_subcategories_too_many', __('Complete Product JSON creative_data.product_subcategories must contain no more than 5 items.', 'bw'));
    }

    foreach (['product_gallery', 'showcase_image_urls'] as $source_array_key) {
        foreach ($decoded['source_data'][$source_array_key] as $index => $item_value) {
            if (!is_scalar($item_value)) {
                return new WP_Error(
                    'bw_digital_csv_json_source_data_array_item_type',
                    sprintf(
                        __('Complete Product JSON source_data.%1$s item %2$d must be a string URL.', 'bw'),
                        $source_array_key,
                        $index + 1
                    )
                );
            }
        }
    }

    foreach ([
        'product_title',
        'digital_assets_list',
        'featured_image_url',
        'hover_image_url',
        'hover_video_url',
        'year',
        'author_artist',
        'total_assets',
        'formats',
        'source_notes',
        'product_context',
        'technique',
        'price',
        'price_commercial',
        'price_extended',
        'download_zip_url',
        'download_zip_url_commercial',
        'download_zip_url_extended',
    ] as $source_scalar_key) {
        if (!is_scalar($decoded['source_data'][$source_scalar_key])) {
            return new WP_Error(
                'bw_digital_csv_json_source_data_scalar_expected',
                sprintf(__('Complete Product JSON source_data.%s must be a string.', 'bw'), $source_scalar_key)
            );
        }
    }

    return [
        'source_data' => [
            'product_title' => trim((string) $decoded['source_data']['product_title']),
            'digital_assets_list' => trim((string) $decoded['source_data']['digital_assets_list']),
            'featured_image_url' => trim((string) $decoded['source_data']['featured_image_url']),
            'product_gallery' => array_values(array_filter(array_map(static function ($url) {
                return trim((string) $url);
            }, $decoded['source_data']['product_gallery']), static function ($url) {
                return $url !== '';
            })),
            'hover_image_url' => trim((string) $decoded['source_data']['hover_image_url']),
            'showcase_image_urls' => array_values(array_filter(array_map(static function ($url) {
                return trim((string) $url);
            }, $decoded['source_data']['showcase_image_urls']), static function ($url) {
                return $url !== '';
            })),
            'hover_video_url' => trim((string) $decoded['source_data']['hover_video_url']),
            'year' => trim((string) $decoded['source_data']['year']),
            'author_artist' => trim((string) $decoded['source_data']['author_artist']),
            'total_assets' => trim((string) $decoded['source_data']['total_assets']),
            'formats' => trim((string) $decoded['source_data']['formats']),
            'source_notes' => trim((string) $decoded['source_data']['source_notes']),
            'product_context' => trim((string) $decoded['source_data']['product_context']),
            'technique' => trim((string) $decoded['source_data']['technique']),
            'price' => trim((string) $decoded['source_data']['price']),
            'price_commercial' => trim((string) $decoded['source_data']['price_commercial']),
            'price_extended' => trim((string) $decoded['source_data']['price_extended']),
            'download_zip_url' => trim((string) $decoded['source_data']['download_zip_url']),
            'download_zip_url_commercial' => trim((string) $decoded['source_data']['download_zip_url_commercial']),
            'download_zip_url_extended' => trim((string) $decoded['source_data']['download_zip_url_extended']),
        ],
        'creative_data' => [
            'post_title' => trim((string) $decoded['creative_data']['post_title']),
            'post_content' => trim((string) $decoded['creative_data']['post_content']),
            'post_excerpt' => trim((string) $decoded['creative_data']['post_excerpt']),
            'tags' => $tags,
            'product_subcategories' => $subcategories,
            'visual_analysis_notes' => isset($decoded['creative_data']['visual_analysis_notes']) ? trim((string) $decoded['creative_data']['visual_analysis_notes']) : '',
        ],
    ];
}

function bw_digital_csv_builder_parse_url_list($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return [];
    }

    $parts = preg_split('/[\r\n,]+/', $value);
    $urls = [];
    foreach ((array) $parts as $part) {
        $part = trim((string) $part);
        if ($part !== '') {
            $urls[] = $part;
        }
    }

    return $urls;
}

function bw_digital_csv_builder_convert_dropbox_url($url, &$error_message = '')
{
    $error_message = '';
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return $url;
    }

    $host = strtolower((string) $parts['host']);
    $is_dropbox = $host === 'www.dropbox.com' || $host === 'dropbox.com' || $host === 'dl.dropboxusercontent.com';
    if (!$is_dropbox) {
        return $url;
    }

    $query = isset($parts['query']) ? (string) $parts['query'] : '';
    $query_segments = $query === '' ? [] : preg_split('/&/', $query);
    $filtered_segments = [];
    $preserved_keys = [];
    $had_rlkey = false;
    $had_st = false;

    foreach ((array) $query_segments as $segment) {
        if ($segment === '') {
            continue;
        }

        $pair = explode('=', $segment, 2);
        $raw_key = isset($pair[0]) ? (string) $pair[0] : '';
        $decoded_key = strtolower(rawurldecode($raw_key));

        if ($decoded_key === 'rlkey') {
            $had_rlkey = true;
        }
        if ($decoded_key === 'st') {
            $had_st = true;
        }

        if (in_array($decoded_key, ['dl', 'raw'], true)) {
            continue;
        }

        $preserved_keys[] = $decoded_key;
        $filtered_segments[] = $segment;
    }

    if ($had_rlkey && !in_array('rlkey', $preserved_keys, true)) {
        $error_message = __('Dropbox conversion removed rlkey unexpectedly.', 'bw');
        return '';
    }

    if ($had_st && !in_array('st', $preserved_keys, true)) {
        $error_message = __('Dropbox conversion removed st unexpectedly.', 'bw');
        return '';
    }

    $scheme = isset($parts['scheme']) ? (string) $parts['scheme'] : 'https';
    $path = isset($parts['path']) ? (string) $parts['path'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
    $query_string = !empty($filtered_segments) ? '?' . implode('&', $filtered_segments) : '';

    return $scheme . '://dl.dropboxusercontent.com' . $path . $query_string . $fragment;
}

function bw_digital_csv_builder_is_absolute_http_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }

    $scheme = strtolower((string) $parts['scheme']);

    return in_array($scheme, ['http', 'https'], true);
}

function bw_digital_csv_builder_validate_url_field($label, $original_url, $converted_url, $required = false, $conversion_error = '')
{
    $label = trim((string) $label);
    $original_url = trim((string) $original_url);
    $converted_url = trim((string) $converted_url);
    $conversion_error = trim((string) $conversion_error);

    if ($original_url === '') {
        if ($required && $converted_url === '') {
            return sprintf(__('%s is not a valid URL.', 'bw'), $label);
        }

        return '';
    }

    if ($conversion_error !== '') {
        if (stripos($conversion_error, 'rlkey') !== false) {
            return sprintf(__('Dropbox URL for %s lost the rlkey parameter during conversion.', 'bw'), $label);
        }
        if (stripos($conversion_error, 'st') !== false) {
            return sprintf(__('Dropbox URL for %s lost the st parameter during conversion.', 'bw'), $label);
        }

        return sprintf(__('Dropbox URL for %s could not be converted.', 'bw'), $label);
    }

    if ($converted_url === '' || !bw_digital_csv_builder_is_absolute_http_url($converted_url)) {
        return sprintf(__('%s is not a valid URL.', 'bw'), $label);
    }

    $original_parts = wp_parse_url($original_url);
    if (!is_array($original_parts) || empty($original_parts['host'])) {
        return '';
    }

    $original_host = strtolower((string) $original_parts['host']);
    $is_dropbox = in_array($original_host, ['www.dropbox.com', 'dropbox.com', 'dl.dropboxusercontent.com'], true);
    if (!$is_dropbox) {
        return '';
    }

    $converted_parts = wp_parse_url($converted_url);
    if (!is_array($converted_parts) || empty($converted_parts['host'])) {
        return sprintf(__('%s is not a valid URL.', 'bw'), $label);
    }

    $converted_host = strtolower((string) $converted_parts['host']);
    if ($converted_host !== 'dl.dropboxusercontent.com') {
        return sprintf(__('Dropbox URL for %s must use dl.dropboxusercontent.com after conversion.', 'bw'), $label);
    }

    $original_query = [];
    if (!empty($original_parts['query'])) {
        wp_parse_str((string) $original_parts['query'], $original_query);
    }

    $converted_query = [];
    if (!empty($converted_parts['query'])) {
        wp_parse_str((string) $converted_parts['query'], $converted_query);
    }

    if (array_key_exists('rlkey', $original_query) && !array_key_exists('rlkey', $converted_query)) {
        return sprintf(__('Dropbox URL for %s lost the rlkey parameter during conversion.', 'bw'), $label);
    }

    if (array_key_exists('st', $original_query) && !array_key_exists('st', $converted_query)) {
        return sprintf(__('Dropbox URL for %s lost the st parameter during conversion.', 'bw'), $label);
    }

    if (array_key_exists('dl', $converted_query)) {
        return sprintf(__('Dropbox URL for %s still contains the dl parameter after conversion.', 'bw'), $label);
    }

    if (array_key_exists('raw', $converted_query)) {
        return sprintf(__('Dropbox URL for %s still contains the raw parameter after conversion.', 'bw'), $label);
    }

    return '';
}

function bw_digital_csv_builder_slugify($title)
{
    return sanitize_title((string) $title);
}

function bw_digital_csv_builder_generate_parent_sku($title)
{
    $slug = bw_digital_csv_builder_slugify($title);
    if ($slug === '') {
        return '';
    }

    return strtoupper(str_replace('-', '_', $slug));
}

function bw_digital_csv_builder_assign_value(array &$row, $key, $value)
{
    if (array_key_exists($key, $row)) {
        $row[$key] = $value;
    }
}

function bw_digital_csv_builder_generate($raw_json)
{
    $template = bw_digital_csv_builder_parse_template();
    if (is_wp_error($template)) {
        return $template;
    }

    $complete_product = bw_digital_csv_builder_validate_complete_json($raw_json);
    if (is_wp_error($complete_product)) {
        return $complete_product;
    }

    $source_data = $complete_product['source_data'];
    $creative = $complete_product['creative_data'];
    $yaml = [
        'PRODUCT TITLE' => $source_data['product_title'],
        'DIGITAL ASSETS LIST' => $source_data['digital_assets_list'],
        'FEATURED IMAGE URL' => $source_data['featured_image_url'],
        'PRODUCT GALLERY' => implode(',', $source_data['product_gallery']),
        'HOVER IMAGE URL' => $source_data['hover_image_url'],
        'SHOWCASE IMAGE URLS' => implode(',', $source_data['showcase_image_urls']),
        'HOVER VIDEO URL' => $source_data['hover_video_url'],
        'YEAR' => $source_data['year'],
        'AUTHOR / ARTIST' => $source_data['author_artist'],
        'TOTAL ASSETS' => $source_data['total_assets'],
        'FORMATS' => $source_data['formats'],
        'SOURCE NOTES' => $source_data['source_notes'],
        'PRODUCT CONTEXT' => $source_data['product_context'],
        'TECHNIQUE' => $source_data['technique'],
        'PRICE' => $source_data['price'],
        'PRICE COMMERCIAL' => $source_data['price_commercial'],
        'PRICE EXTENDED' => $source_data['price_extended'],
        'DOWNLOAD ZIP URL' => $source_data['download_zip_url'],
        'DOWNLOAD ZIP URL COMMERCIAL' => $source_data['download_zip_url_commercial'],
        'DOWNLOAD ZIP URL EXTENDED' => $source_data['download_zip_url_extended'],
    ];

    $headers = $template['headers'];
    $rows = $template['rows'];
    $parent = $rows[0];
    $commercial = $rows[1];
    $extended = $rows[2];
    $errors = [];
    $warnings = [];
    $success = [];

    if (!empty($creative['product_subcategories']) && count($creative['product_subcategories']) < 3) {
        $warnings[] = __('Fewer than 3 product_subcategories were provided. Add more when visually and contextually justified.', 'bw');
    }

    $final_title = $creative['post_title'] !== '' ? $creative['post_title'] : trim((string) $yaml['PRODUCT TITLE']);
    $final_slug = bw_digital_csv_builder_slugify($final_title);
    $parent_sku = bw_digital_csv_builder_generate_parent_sku($final_title);
    $commercial_sku = $parent_sku !== '' ? $parent_sku . '_COMMERCIAL' : '';
    $extended_sku = $parent_sku !== '' ? $parent_sku . '_EXTENDED' : '';

    $featured_error = '';
    $featured_image_source = (string) $yaml['FEATURED IMAGE URL'];
    $featured_image_url = bw_digital_csv_builder_convert_dropbox_url($featured_image_source, $featured_error);
    $featured_validation_error = bw_digital_csv_builder_validate_url_field('FEATURED IMAGE URL', $featured_image_source, $featured_image_url, false, $featured_error);
    if ($featured_validation_error !== '') {
        $errors[] = $featured_validation_error;
    }

    $gallery_urls = [];
    foreach ($source_data['product_gallery'] as $gallery_index => $gallery_url) {
        $gallery_error = '';
        $converted_gallery_url = bw_digital_csv_builder_convert_dropbox_url($gallery_url, $gallery_error);
        $gallery_label = sprintf(__('PRODUCT GALLERY item %d', 'bw'), $gallery_index + 1);
        $gallery_validation_error = bw_digital_csv_builder_validate_url_field($gallery_label, $gallery_url, $converted_gallery_url, false, $gallery_error);
        if ($gallery_validation_error !== '') {
            $errors[] = $gallery_validation_error;
            continue;
        }
        if ($converted_gallery_url !== '') {
            $gallery_urls[] = $converted_gallery_url;
        }
    }

    $hover_image_error = '';
    $hover_image_source = (string) $yaml['HOVER IMAGE URL'];
    $hover_image_url = bw_digital_csv_builder_convert_dropbox_url($hover_image_source, $hover_image_error);
    $hover_image_validation_error = bw_digital_csv_builder_validate_url_field('HOVER IMAGE URL', $hover_image_source, $hover_image_url, false, $hover_image_error);
    if ($hover_image_validation_error !== '') {
        $errors[] = $hover_image_validation_error;
    }

    $showcase_urls = [];
    foreach ($source_data['showcase_image_urls'] as $showcase_index => $showcase_url) {
        $showcase_error = '';
        $converted_showcase_url = bw_digital_csv_builder_convert_dropbox_url($showcase_url, $showcase_error);
        $showcase_label = sprintf(__('SHOWCASE IMAGE URLS item %d', 'bw'), $showcase_index + 1);
        $showcase_validation_error = bw_digital_csv_builder_validate_url_field($showcase_label, $showcase_url, $converted_showcase_url, false, $showcase_error);
        if ($showcase_validation_error !== '') {
            $errors[] = $showcase_validation_error;
            continue;
        }
        if ($converted_showcase_url !== '') {
            $showcase_urls[] = $converted_showcase_url;
        }
    }
    $showcase_image_url = !empty($showcase_urls) ? $showcase_urls[0] : '';

    $hover_video_error = '';
    $hover_video_source = (string) $yaml['HOVER VIDEO URL'];
    $hover_video_url = bw_digital_csv_builder_convert_dropbox_url($hover_video_source, $hover_video_error);
    $hover_video_validation_error = bw_digital_csv_builder_validate_url_field('HOVER VIDEO URL', $hover_video_source, $hover_video_url, false, $hover_video_error);
    if ($hover_video_validation_error !== '') {
        $errors[] = $hover_video_validation_error;
    }

    $default_zip_error = '';
    $default_zip_source = (string) $yaml['DOWNLOAD ZIP URL'];
    $default_zip_url = bw_digital_csv_builder_convert_dropbox_url($default_zip_source, $default_zip_error);
    $default_zip_validation_error = bw_digital_csv_builder_validate_url_field('DOWNLOAD ZIP URL', $default_zip_source, $default_zip_url, false, $default_zip_error);
    if ($default_zip_validation_error !== '') {
        $errors[] = $default_zip_validation_error;
    }
    $commercial_zip_error = '';
    $commercial_zip_source = (string) $yaml['DOWNLOAD ZIP URL COMMERCIAL'];
    $commercial_zip_override = bw_digital_csv_builder_convert_dropbox_url($commercial_zip_source, $commercial_zip_error);
    $commercial_zip_validation_error = bw_digital_csv_builder_validate_url_field('DOWNLOAD ZIP URL COMMERCIAL', $commercial_zip_source, $commercial_zip_override, false, $commercial_zip_error);
    if ($commercial_zip_validation_error !== '') {
        $errors[] = $commercial_zip_validation_error;
    }
    $extended_zip_error = '';
    $extended_zip_source = (string) $yaml['DOWNLOAD ZIP URL EXTENDED'];
    $extended_zip_override = bw_digital_csv_builder_convert_dropbox_url($extended_zip_source, $extended_zip_error);
    $extended_zip_validation_error = bw_digital_csv_builder_validate_url_field('DOWNLOAD ZIP URL EXTENDED', $extended_zip_source, $extended_zip_override, false, $extended_zip_error);
    if ($extended_zip_validation_error !== '') {
        $errors[] = $extended_zip_validation_error;
    }

    $commercial_zip_url = $commercial_zip_override !== '' ? $commercial_zip_override : $default_zip_url;
    $extended_zip_url = $extended_zip_override !== '' ? $extended_zip_override : $default_zip_url;

    bw_digital_csv_builder_assign_value($parent, 'row_type', 'product');
    bw_digital_csv_builder_assign_value($parent, 'parent_sku', '');
    bw_digital_csv_builder_assign_value($parent, 'sku', $parent_sku);
    bw_digital_csv_builder_assign_value($parent, 'post_title', $final_title);
    bw_digital_csv_builder_assign_value($parent, 'post_name', $final_slug);
    bw_digital_csv_builder_assign_value($parent, 'post_status', 'publish');
    bw_digital_csv_builder_assign_value($parent, 'product_type', 'variable');
    bw_digital_csv_builder_assign_value($parent, 'default_variation', 'commercial');
    bw_digital_csv_builder_assign_value($parent, 'regular_price', trim((string) $yaml['PRICE']));
    bw_digital_csv_builder_assign_value($parent, 'categories', 'Digital Collections');
    bw_digital_csv_builder_assign_value($parent, 'tags', implode(',', $creative['tags']));
    bw_digital_csv_builder_assign_value($parent, 'post_content', $creative['post_content']);
    bw_digital_csv_builder_assign_value($parent, 'post_excerpt', $creative['post_excerpt']);
    bw_digital_csv_builder_assign_value($parent, 'featured_image', $featured_image_url);
    bw_digital_csv_builder_assign_value($parent, 'product_gallery', implode(',', $gallery_urls));
    bw_digital_csv_builder_assign_value($parent, 'stock_status', 'instock');
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_product_type', 'digital');
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_showcase_title', $final_title);
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_showcase_description', $creative['post_excerpt']);
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_showcase_image', $showcase_image_url);
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_assets_count', trim((string) $yaml['TOTAL ASSETS']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_formats', trim((string) $yaml['FORMATS']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_artist_name', trim((string) $yaml['AUTHOR / ARTIST']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_digital_source', trim((string) $yaml['SOURCE NOTES']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_digital_year', trim((string) $yaml['YEAR']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_digital_technique', trim((string) $yaml['TECHNIQUE']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_digital_total_assets', trim((string) $yaml['TOTAL ASSETS']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_digital_assets_list', trim((string) $yaml['DIGITAL ASSETS LIST']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_digital_formats', trim((string) $yaml['FORMATS']));
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_slider_hover_image', $hover_image_url);
    bw_digital_csv_builder_assign_value($parent, 'meta:_bw_slider_hover_video', $hover_video_url);

    bw_digital_csv_builder_assign_value($commercial, 'row_type', 'variation');
    bw_digital_csv_builder_assign_value($commercial, 'parent_sku', $parent_sku);
    bw_digital_csv_builder_assign_value($commercial, 'sku', $commercial_sku);
    bw_digital_csv_builder_assign_value($commercial, 'variation_name', 'Commercial');
    bw_digital_csv_builder_assign_value($commercial, 'variation_regular_price', trim((string) $yaml['PRICE COMMERCIAL']));
    bw_digital_csv_builder_assign_value($commercial, 'variation_image', $featured_image_url);
    bw_digital_csv_builder_assign_value($commercial, 'variation_enabled', 'yes');
    bw_digital_csv_builder_assign_value($commercial, 'variation_virtual', 'yes');
    bw_digital_csv_builder_assign_value($commercial, 'variation_downloadable', 'yes');
    bw_digital_csv_builder_assign_value($commercial, 'variation_download_name', sprintf('Commercial License — %s', $final_title));
    bw_digital_csv_builder_assign_value($commercial, 'variation_download_url', $commercial_zip_url);

    bw_digital_csv_builder_assign_value($extended, 'row_type', 'variation');
    bw_digital_csv_builder_assign_value($extended, 'parent_sku', $parent_sku);
    bw_digital_csv_builder_assign_value($extended, 'sku', $extended_sku);
    bw_digital_csv_builder_assign_value($extended, 'variation_name', 'Extended');
    bw_digital_csv_builder_assign_value($extended, 'variation_regular_price', trim((string) $yaml['PRICE EXTENDED']));
    bw_digital_csv_builder_assign_value($extended, 'variation_image', $featured_image_url);
    bw_digital_csv_builder_assign_value($extended, 'variation_enabled', 'yes');
    bw_digital_csv_builder_assign_value($extended, 'variation_virtual', 'yes');
    bw_digital_csv_builder_assign_value($extended, 'variation_downloadable', 'yes');
    bw_digital_csv_builder_assign_value($extended, 'variation_download_name', sprintf('Extended License — %s', $final_title));
    bw_digital_csv_builder_assign_value($extended, 'variation_download_url', $extended_zip_url);

    if (in_array('product_subcategories', $headers, true)) {
        bw_digital_csv_builder_assign_value($parent, 'product_subcategories', bw_import_product_subcategories_to_meta_string($creative['product_subcategories']));
        bw_digital_csv_builder_assign_value($commercial, 'product_subcategories', '');
        bw_digital_csv_builder_assign_value($extended, 'product_subcategories', '');
    } elseif (!empty($creative['product_subcategories'])) {
        $warnings[] = __('The current Digital template has no product_subcategories column, so validated subcategories were not written into the CSV.', 'bw');
    }

    if ($final_title === '') {
        $errors[] = __('Final post_title is empty after YAML/JSON fallback.', 'bw');
    }
    if ($final_slug === '') {
        $errors[] = __('Final post_name could not be generated from post_title.', 'bw');
    }
    if ($parent_sku === '') {
        $errors[] = __('Parent SKU could not be generated from the final post_title.', 'bw');
    }

    if (trim((string) $yaml['FEATURED IMAGE URL']) !== '') {
        if ($featured_image_url === '') {
            $errors[] = __('FEATURED IMAGE URL exists in YAML but featured_image would be empty.', 'bw');
        }
        if ((string) $commercial['variation_image'] === '' || (string) $extended['variation_image'] === '') {
            $errors[] = __('FEATURED IMAGE URL exists in YAML but one or both variation_image fields would be empty.', 'bw');
        }
    }

    if (trim((string) $yaml['PRODUCT GALLERY']) !== '' && (string) $parent['product_gallery'] === '') {
        $errors[] = __('PRODUCT GALLERY exists in YAML but product_gallery would be empty.', 'bw');
    }

    if (trim((string) $yaml['HOVER IMAGE URL']) !== '' && (string) $parent['meta:_bw_slider_hover_image'] === '') {
        $errors[] = __('HOVER IMAGE URL exists in YAML but meta:_bw_slider_hover_image would be empty.', 'bw');
    }

    if (trim((string) $yaml['SHOWCASE IMAGE URLS']) !== '' && (string) $parent['meta:_bw_showcase_image'] === '') {
        $errors[] = __('SHOWCASE IMAGE URLS exists in YAML but meta:_bw_showcase_image would be empty.', 'bw');
    }

    if (trim((string) $yaml['DOWNLOAD ZIP URL']) !== '') {
        if ((string) $commercial['variation_download_url'] === '' || (string) $extended['variation_download_url'] === '') {
            $errors[] = __('DOWNLOAD ZIP URL exists in YAML but one or both variation_download_url fields would be empty.', 'bw');
        }
    }
    if (trim((string) $yaml['DOWNLOAD ZIP URL COMMERCIAL']) !== '' && (string) $commercial['variation_download_url'] === '') {
        $errors[] = __('DOWNLOAD ZIP URL COMMERCIAL exists in YAML but the Commercial variation_download_url would be empty.', 'bw');
    }
    if (trim((string) $yaml['DOWNLOAD ZIP URL EXTENDED']) !== '' && (string) $extended['variation_download_url'] === '') {
        $errors[] = __('DOWNLOAD ZIP URL EXTENDED exists in YAML but the Extended variation_download_url would be empty.', 'bw');
    }

    $required_parent_fields = [
        'row_type',
        'sku',
        'post_title',
        'post_name',
        'post_status',
        'product_type',
        'default_variation',
        'categories',
        'stock_status',
        'meta:_bw_product_type',
    ];
    foreach ($required_parent_fields as $required_parent_field) {
        if (array_key_exists($required_parent_field, $parent) && trim((string) $parent[$required_parent_field]) === '') {
            $errors[] = sprintf(__('Parent row field %s is required and empty.', 'bw'), $required_parent_field);
        }
    }

    $required_variation_fields = [
        'row_type',
        'parent_sku',
        'sku',
        'variation_name',
        'variation_regular_price',
        'variation_enabled',
        'variation_virtual',
        'variation_downloadable',
        'variation_download_name',
    ];

    foreach (['Commercial' => $commercial, 'Extended' => $extended] as $label => $variation_row) {
        foreach ($required_variation_fields as $required_variation_field) {
            if (array_key_exists($required_variation_field, $variation_row) && trim((string) $variation_row[$required_variation_field]) === '') {
                $errors[] = sprintf(__('%1$s variation field %2$s is required and empty.', 'bw'), $label, $required_variation_field);
            }
        }
    }

    if ((string) $commercial['variation_downloadable'] === 'yes' && trim((string) $commercial['variation_download_url']) === '') {
        $errors[] = __('Commercial variation is downloadable but has no download ZIP URL. Provide DOWNLOAD ZIP URL or DOWNLOAD ZIP URL COMMERCIAL.', 'bw');
    }

    if ((string) $extended['variation_downloadable'] === 'yes' && trim((string) $extended['variation_download_url']) === '') {
        $errors[] = __('Extended variation is downloadable but has no download ZIP URL. Provide DOWNLOAD ZIP URL or DOWNLOAD ZIP URL EXTENDED.', 'bw');
    }

    $final_rows = [$headers, [], [], []];
    $ordered_rows = [$parent, $commercial, $extended];
    foreach ($ordered_rows as $index => $ordered_row) {
        foreach ($headers as $header) {
            $final_rows[$index + 1][] = isset($ordered_row[$header]) ? (string) $ordered_row[$header] : '';
        }
    }

    if (count($final_rows) !== 4) {
        $errors[] = __('Final CSV row count is invalid. Expected exactly 4 rows.', 'bw');
    }

    $header_count = count($headers);
    foreach ($final_rows as $row_index => $final_row) {
        if (count($final_row) !== $header_count) {
            $errors[] = sprintf(__('Final CSV row %d does not match the template column count.', 'bw'), $row_index + 1);
        }
    }

    $csv_handle = fopen('php://temp', 'r+');
    foreach ($final_rows as $csv_row) {
        fputcsv($csv_handle, $csv_row);
    }
    rewind($csv_handle);
    $csv_content = stream_get_contents($csv_handle);
    fclose($csv_handle);

    if (!empty($errors)) {
        return [
            'ok' => false,
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'success' => [],
            'csv' => '',
            'filename' => '',
            'yaml' => $yaml,
            'creative' => $creative,
        ];
    }

    $success[] = sprintf(__('Template columns validated: %d', 'bw'), $header_count);
    $success[] = __('Generated exactly 1 parent row and 2 variation rows.', 'bw');
    $success[] = __('Dropbox media and ZIP URLs were converted into final WordPress-compatible direct URLs.', 'bw');

    return [
        'ok' => true,
        'errors' => [],
        'warnings' => array_values(array_unique($warnings)),
        'success' => $success,
        'csv' => $csv_content,
        'filename' => sprintf('blackwork-digital-product-completed-%s.csv', $final_slug !== '' ? $final_slug : 'product'),
        'yaml' => $yaml,
        'creative' => $creative,
    ];
}

function bw_digital_csv_builder_handle_download_request()
{
    if (!bw_digital_csv_builder_user_can_manage()) {
        return new WP_Error('bw_digital_csv_permission', __('You do not have permission to download generated Digital CSV files.', 'bw'));
    }

    if (!isset($_POST['bw_digital_csv_download_nonce']) || !wp_verify_nonce(wp_unslash($_POST['bw_digital_csv_download_nonce']), 'bw_digital_csv_download')) {
        return new WP_Error('bw_digital_csv_download_nonce', __('Invalid Digital CSV download nonce. Please regenerate the CSV and try again.', 'bw'));
    }

    $payload = isset($_POST['bw_digital_csv_payload']) ? base64_decode((string) wp_unslash($_POST['bw_digital_csv_payload']), true) : false;
    $filename = isset($_POST['bw_digital_csv_filename']) ? sanitize_file_name((string) wp_unslash($_POST['bw_digital_csv_filename'])) : 'blackwork-digital-product-completed.csv';

    if ($payload === false || $payload === '') {
        return new WP_Error('bw_digital_csv_download_empty', __('Generated Digital CSV payload is missing. Please generate the CSV again.', 'bw'));
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
    header('Content-Length: ' . (string) strlen($payload));
    header('X-Content-Type-Options: nosniff');
    echo $payload;
    exit;
}

function bw_digital_csv_builder_maybe_download()
{
    if (!is_admin() || !isset($_POST['bw_digital_csv_download_submit'])) {
        return;
    }

    $download_result = bw_digital_csv_builder_handle_download_request();
    if (is_wp_error($download_result)) {
        wp_die(esc_html($download_result->get_error_message()), 400);
    }
}
