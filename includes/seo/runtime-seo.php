<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_SEO_RUNTIME_LOADED')) {
    define('BW_SEO_RUNTIME_LOADED', true);
}

if (!defined('BW_SEO_SCHEMA_FIX_VERSION')) {
    define('BW_SEO_SCHEMA_FIX_VERSION', 2);
}

/**
 * Remove Hello Elementor fallback meta description to avoid duplicates
 * when Rank Math is active.
 */
function bw_seo_disable_hello_elementor_meta_description()
{
    if (function_exists('hello_elementor_add_description_meta_tag')) {
        remove_action('wp_head', 'hello_elementor_add_description_meta_tag');
    }
}
add_action('after_setup_theme', 'bw_seo_disable_hello_elementor_meta_description', 50);

/**
 * Determine whether the current request should be indexable.
 */
function bw_seo_is_indexable_context()
{
    if (is_admin()) {
        return false;
    }

    if (is_404() || is_search()) {
        return false;
    }

    if (function_exists('is_cart') && is_cart()) {
        return false;
    }

    if (function_exists('is_checkout') && is_checkout()) {
        return false;
    }

    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }

    if (is_front_page() || is_home() || is_page() || is_single()) {
        return true;
    }

    if (function_exists('is_shop') && is_shop()) {
        return true;
    }

    if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
        return true;
    }

    if (is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag')) {
        return true;
    }

    return false;
}

/**
 * Build a canonical URL for the current frontend request.
 */
function bw_seo_get_current_canonical_url()
{
    $url = '';

    if (function_exists('bw_ss_is_search_results_request') && bw_ss_is_search_results_request()) {
        $base = function_exists('bw_ss_get_search_results_url') ? bw_ss_get_search_results_url() : home_url('/search/');
        $query_string = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
        $args = [];
        if ('' !== $query_string) {
            parse_str($query_string, $args);
        }
        $url = add_query_arg($args, $base);
    } elseif (is_singular()) {
        $object_id = get_queried_object_id();
        if ($object_id > 0) {
            $url = get_permalink($object_id);
        }
    } elseif (function_exists('is_shop') && is_shop()) {
        $shop_id = (int) wc_get_page_id('shop');
        if ($shop_id > 0) {
            $url = get_permalink($shop_id);
        }
    } elseif (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $term_link = get_term_link($term);
            if (!is_wp_error($term_link)) {
                $url = $term_link;
            }
        }
    } elseif (is_front_page() || is_home()) {
        $url = home_url('/');
    } else {
        $url = home_url(add_query_arg([], isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/'));
    }

    return is_string($url) ? esc_url_raw($url) : '';
}

/**
 * Force Rank Math canonical when missing/empty.
 *
 * @param mixed $canonical Rank Math canonical.
 * @return mixed
 */
function bw_seo_filter_rank_math_canonical($canonical)
{
    if (is_admin()) {
        return $canonical;
    }

    if (is_string($canonical) && '' !== trim($canonical)) {
        return $canonical;
    }

    $resolved = bw_seo_get_current_canonical_url();
    return '' !== $resolved ? $resolved : $canonical;
}
add_filter('rank_math/frontend/canonical', 'bw_seo_filter_rank_math_canonical', 20);

/**
 * Normalize robots directives on public indexable pages.
 *
 * @param array<string,mixed> $robots Robots directives.
 * @return array<string,mixed>
 */
function bw_seo_filter_wp_robots($robots)
{
    if (!bw_seo_is_indexable_context()) {
        return $robots;
    }

    return bw_seo_get_normalized_wp_robots();
}
add_filter('wp_robots', 'bw_seo_filter_wp_robots', 99);

/**
 * Canonical robots directives for public indexable contexts.
 *
 * @return array<string,mixed>
 */
function bw_seo_get_normalized_wp_robots()
{
    return [
        'index' => true,
        'follow' => true,
        'max-snippet' => -1,
        'max-video-preview' => -1,
        'max-image-preview' => 'large',
    ];
}

/**
 * Normalize Rank Math robots directives for indexable contexts.
 *
 * @param mixed $robots Rank Math robots data.
 * @return mixed
 */
function bw_seo_filter_rank_math_robots($robots)
{
    if (!bw_seo_is_indexable_context()) {
        return $robots;
    }

    // Keep only index/follow here because Rank Math may combine these with
    // wp_robots max-* directives in a single robots tag.
    return [
        'index',
        'follow',
    ];
}
add_filter('rank_math/frontend/robots', 'bw_seo_filter_rank_math_robots', 99);

/**
 * Validate social image quality and mime suitability.
 */
function bw_seo_is_usable_social_image($url, $attachment_id = 0)
{
    $url = is_string($url) ? trim($url) : '';
    if ('' === $url) {
        return false;
    }

    if (!preg_match('#^https?://#i', $url)) {
        return false;
    }

    $extension = strtolower((string) pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if ('svg' === $extension || 'svgz' === $extension) {
        return false;
    }

    if ($attachment_id > 0) {
        $mime = get_post_mime_type($attachment_id);
        if (is_string($mime) && '' !== $mime && !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return false;
        }

        $meta = wp_get_attachment_metadata($attachment_id);
        if (is_array($meta)) {
            $width = isset($meta['width']) ? (int) $meta['width'] : 0;
            $height = isset($meta['height']) ? (int) $meta['height'] : 0;
            if ($width > 0 && $height > 0 && ($width < 600 || $height < 315)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Resolve attachment image URL ensuring usable social dimensions/mime.
 */
function bw_seo_get_attachment_social_image($attachment_id)
{
    $attachment_id = (int) $attachment_id;
    if ($attachment_id <= 0) {
        return '';
    }

    foreach (['full', 'large', 'medium_large'] as $size) {
        $candidate = wp_get_attachment_image_url($attachment_id, $size);
        if (is_string($candidate) && bw_seo_is_usable_social_image($candidate, $attachment_id)) {
            return esc_url_raw($candidate);
        }
    }

    return '';
}

/**
 * Resolve a local raster organization logo URL for schema/social usage.
 */
function bw_seo_get_local_organization_logo_url()
{
    $candidate_ids = [];
    $logo_id = (int) get_theme_mod('custom_logo');
    if ($logo_id > 0) {
        $candidate_ids[] = $logo_id;
    }

    $site_icon_id = (int) get_option('site_icon');
    if ($site_icon_id > 0) {
        $candidate_ids[] = $site_icon_id;
    }

    foreach (array_values(array_unique($candidate_ids)) as $attachment_id) {
        $url = bw_seo_get_attachment_social_image($attachment_id);
        if ('' !== $url) {
            return $url;
        }
    }

    return '';
}

/**
 * Detect whether an URL is local to current host.
 */
function bw_seo_is_local_site_url($url)
{
    $url_host = wp_parse_url((string) $url, PHP_URL_HOST);
    $site_host = wp_parse_url((string) home_url('/'), PHP_URL_HOST);
    if (!is_string($url_host) || '' === $url_host || !is_string($site_host) || '' === $site_host) {
        return false;
    }

    return strtolower($url_host) === strtolower($site_host);
}

/**
 * Get local attachment dimensions by URL when available.
 *
 * @return array<string,int>
 */
function bw_seo_get_local_attachment_dimensions($url)
{
    $attachment_id = attachment_url_to_postid((string) $url);
    if ($attachment_id <= 0) {
        return [];
    }

    $meta = wp_get_attachment_metadata($attachment_id);
    if (!is_array($meta)) {
        return [];
    }

    $width = isset($meta['width']) ? (int) $meta['width'] : 0;
    $height = isset($meta['height']) ? (int) $meta['height'] : 0;
    if ($width <= 0 || $height <= 0) {
        return [];
    }

    return [
        'width' => $width,
        'height' => $height,
    ];
}

/**
 * Resolve current object social image fallback.
 */
function bw_seo_resolve_social_image_url()
{
    $candidate_ids = [];
    $candidate_urls = [];

    // Product/category/page primary coverage.
    if (is_singular()) {
        $thumb_id = (int) get_post_thumbnail_id(get_queried_object_id());
        if ($thumb_id > 0) {
            $candidate_ids[] = $thumb_id;
        }
    } elseif (is_tax('product_cat') || (function_exists('is_product_taxonomy') && is_product_taxonomy())) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $term_thumb = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($term_thumb > 0) {
                $candidate_ids[] = $term_thumb;
            }
        }
    } elseif (function_exists('is_shop') && is_shop()) {
        $shop_id = (int) wc_get_page_id('shop');
        if ($shop_id > 0) {
            $shop_thumb = (int) get_post_thumbnail_id($shop_id);
            if ($shop_thumb > 0) {
                $candidate_ids[] = $shop_thumb;
            }
        }
    }

    // Front page featured image fallback.
    if (is_front_page()) {
        $front_id = (int) get_option('page_on_front');
        if ($front_id > 0) {
            $front_thumb = (int) get_post_thumbnail_id($front_id);
            if ($front_thumb > 0) {
                $candidate_ids[] = $front_thumb;
            }
        }
    }

    // Link page dedicated fallback chain (if module is active and current route is link page).
    if (function_exists('bw_link_page_get_settings')) {
        $settings = bw_link_page_get_settings();
        if (is_array($settings) && !empty($settings['page_id']) && is_page((int) $settings['page_id'])) {
            if (!empty($settings['social_image_id'])) {
                $candidate_ids[] = (int) $settings['social_image_id'];
            }
            if (!empty($settings['background_image_id'])) {
                $candidate_ids[] = (int) $settings['background_image_id'];
            }
            if (!empty($settings['logo_id'])) {
                $candidate_ids[] = (int) $settings['logo_id'];
            }
        }
    }

    // Global plugin fallback image option.
    $global_fallback_id = (int) get_option('bw_seo_default_social_image_id', 0);
    if ($global_fallback_id > 0) {
        $candidate_ids[] = $global_fallback_id;
    }
    $global_fallback_url = trim((string) get_option('bw_seo_default_social_image_url', ''));
    if ('' !== $global_fallback_url) {
        $candidate_urls[] = esc_url_raw($global_fallback_url);
    }

    // Allow programmatic override for environment-specific fallback.
    $filtered_global_url = apply_filters('bw_seo_default_social_image_url', '');
    if (is_string($filtered_global_url) && '' !== trim($filtered_global_url)) {
        $candidate_urls[] = esc_url_raw($filtered_global_url);
    }

    // Last-resort logo/icon fallback.
    $logo_id = (int) get_theme_mod('custom_logo');
    if ($logo_id > 0) {
        $candidate_ids[] = $logo_id;
    }

    $site_icon_id = (int) get_option('site_icon');
    if ($site_icon_id > 0) {
        $candidate_ids[] = $site_icon_id;
    }

    $candidate_ids = array_values(array_unique(array_filter(array_map('intval', $candidate_ids))));
    foreach ($candidate_ids as $attachment_id) {
        $resolved = bw_seo_get_attachment_social_image($attachment_id);
        if ('' !== $resolved) {
            return $resolved;
        }
    }

    foreach ($candidate_urls as $candidate_url) {
        if (bw_seo_is_usable_social_image($candidate_url, 0)) {
            return $candidate_url;
        }
    }

    return '';
}

/**
 * Rank Math OG/Twitter image fallback.
 *
 * @param mixed $image_url Current image URL.
 * @return mixed
 */
function bw_seo_filter_rank_math_social_image($image_url)
{
    if (!isset($GLOBALS['bw_seo_social_meta_state']) || !is_array($GLOBALS['bw_seo_social_meta_state'])) {
        $GLOBALS['bw_seo_social_meta_state'] = [];
    }

    if (is_string($image_url) && '' !== trim($image_url)) {
        $GLOBALS['bw_seo_social_meta_state']['rank_math_image_seen'] = true;
        return $image_url;
    }

    $fallback = bw_seo_resolve_social_image_url();
    if ('' !== $fallback) {
        $GLOBALS['bw_seo_social_meta_state']['rank_math_image_seen'] = true;
        $GLOBALS['bw_seo_social_meta_state']['resolved_fallback_image'] = $fallback;
        return $fallback;
    }

    return $image_url;
}
add_filter('rank_math/opengraph/facebook/image', 'bw_seo_filter_rank_math_social_image', 20);
add_filter('rank_math/opengraph/twitter/image', 'bw_seo_filter_rank_math_social_image', 20);

/**
 * Capture social image tags emitted by Rank Math to prevent duplicates.
 *
 * @param mixed $content Meta tag content value.
 * @return mixed
 */
function bw_seo_capture_rank_math_facebook_image_tag($content)
{
    if (is_string($content) && '' !== trim($content)) {
        if (!isset($GLOBALS['bw_seo_social_meta_state']) || !is_array($GLOBALS['bw_seo_social_meta_state'])) {
            $GLOBALS['bw_seo_social_meta_state'] = [];
        }
        $GLOBALS['bw_seo_social_meta_state']['og_image_tag_present'] = true;
    }
    return $content;
}
add_filter('rank_math/opengraph/facebook/image', 'bw_seo_capture_rank_math_facebook_image_tag', 999);

/**
 * Capture social image tags emitted by Rank Math to prevent duplicates.
 *
 * @param mixed $content Meta tag content value.
 * @return mixed
 */
function bw_seo_capture_rank_math_twitter_image_tag($content)
{
    if (is_string($content) && '' !== trim($content)) {
        if (!isset($GLOBALS['bw_seo_social_meta_state']) || !is_array($GLOBALS['bw_seo_social_meta_state'])) {
            $GLOBALS['bw_seo_social_meta_state'] = [];
        }
        $GLOBALS['bw_seo_social_meta_state']['twitter_image_tag_present'] = true;
    }
    return $content;
}
add_filter('rank_math/opengraph/twitter/image', 'bw_seo_capture_rank_math_twitter_image_tag', 999);

/**
 * Determine whether fallback image tags should be rendered.
 */
function bw_seo_should_render_social_image_fallback()
{
    if (is_admin()) {
        return false;
    }

    if (is_singular('product')) {
        return false;
    }

    return bw_seo_is_indexable_context();
}

/**
 * Render OG/Twitter image tags only when they are missing.
 */
function bw_seo_render_missing_social_image_meta()
{
    if (!bw_seo_should_render_social_image_fallback()) {
        return;
    }

    $state = isset($GLOBALS['bw_seo_social_meta_state']) && is_array($GLOBALS['bw_seo_social_meta_state'])
        ? $GLOBALS['bw_seo_social_meta_state']
        : [];

    $has_og_image = !empty($state['og_image_tag_present']);
    $has_twitter_image = !empty($state['twitter_image_tag_present']);
    if ($has_og_image && $has_twitter_image) {
        return;
    }

    $fallback = bw_seo_resolve_social_image_url();
    if ('' === $fallback) {
        return;
    }

    $fallback = esc_url($fallback);
    if ('' === $fallback) {
        return;
    }

    if (!$has_og_image) {
        echo "\n<meta property=\"og:image\" content=\"" . esc_attr($fallback) . "\" />\n";
    }

    if (!$has_twitter_image) {
        echo "<meta name=\"twitter:image\" content=\"" . esc_attr($fallback) . "\" />\n";
    }

    if (!isset($GLOBALS['bw_seo_social_meta_state']) || !is_array($GLOBALS['bw_seo_social_meta_state'])) {
        $GLOBALS['bw_seo_social_meta_state'] = [];
    }
    $GLOBALS['bw_seo_social_meta_state']['resolved_fallback_image'] = $fallback;
}
add_action('wp_head', 'bw_seo_render_missing_social_image_meta', 999);

/**
 * Keep OG URL aligned with canonical URL.
 *
 * @param mixed $url OG URL.
 * @return mixed
 */
function bw_seo_filter_rank_math_og_url($url)
{
    $canonical = bw_seo_get_current_canonical_url();
    if ('' === $canonical) {
        return $url;
    }

    return $canonical;
}
add_filter('rank_math/opengraph/url', 'bw_seo_filter_rank_math_og_url', 20);

/**
 * Repair search-route JSON-LD URL/@id when plugin-owned /search/ route is active.
 *
 * @param mixed $data Rank Math JSON-LD payload.
 * @param mixed $jsonld JsonLD object.
 * @return mixed
 */
function bw_seo_filter_rank_math_json_ld($data, $jsonld)
{
    if (!is_array($data)) {
        return $data;
    }

    $fallback_image = bw_seo_resolve_social_image_url();
    $logo_image = bw_seo_get_local_organization_logo_url();
    $fallback_image_id = '' !== $fallback_image ? $fallback_image : '';

    if (!empty($data['ImageObject']) && is_array($data['ImageObject']) && '' !== $fallback_image) {
        foreach ($data['ImageObject'] as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            $entity_url = isset($entity['url']) ? esc_url_raw((string) $entity['url']) : '';
            $entity_width = isset($entity['width']) ? (int) $entity['width'] : 0;
            $entity_height = isset($entity['height']) ? (int) $entity['height'] : 0;
            $should_replace = (
                '' === $entity_url
                || !bw_seo_is_usable_social_image($entity_url, 0)
                || !bw_seo_is_local_site_url($entity_url)
                || ($entity_width > 0 && $entity_height > 0 && ($entity_width < 600 || $entity_height < 315))
            );

            if (!$should_replace) {
                continue;
            }

            $data['ImageObject'][$key]['url'] = $fallback_image;
            $data['ImageObject'][$key]['@id'] = $fallback_image_id;
            $dims = bw_seo_get_local_attachment_dimensions($fallback_image);
            if (!empty($dims['width']) && !empty($dims['height'])) {
                $data['ImageObject'][$key]['width'] = (int) $dims['width'];
                $data['ImageObject'][$key]['height'] = (int) $dims['height'];
            }
        }
    }

    if (!empty($data['Organization']) && is_array($data['Organization'])) {
        foreach ($data['Organization'] as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            if ('' !== $logo_image) {
                $data['Organization'][$key]['logo'] = $logo_image;
            }
        }
    }

    if (is_singular() && '' !== $fallback_image_id) {
        foreach (['WebPage', 'ItemPage'] as $type_key) {
            if (empty($data[$type_key]) || !is_array($data[$type_key])) {
                continue;
            }
            foreach ($data[$type_key] as $k => $entity) {
                if (!is_array($entity)) {
                    continue;
                }
                if (!is_singular('product')) {
                    $data[$type_key][$k]['primaryImageOfPage'] = ['@id' => $fallback_image_id];
                }
            }
        }

        if (!empty($data['Article']) && is_array($data['Article']) && !is_singular('product')) {
            foreach ($data['Article'] as $k => $entity) {
                if (!is_array($entity)) {
                    continue;
                }
                $data['Article'][$k]['image'] = ['@id' => $fallback_image_id];
            }
        }
    }

    if (is_singular('product') && !empty($data['Product']) && is_array($data['Product'])) {
        $product_id = get_queried_object_id();
        $sku = $product_id > 0 ? get_post_meta($product_id, '_sku', true) : '';
        $sku = is_string($sku) ? trim($sku) : '';

        foreach ($data['Product'] as $k => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            if ('' !== $sku && empty($data['Product'][$k]['sku'])) {
                $data['Product'][$k]['sku'] = $sku;
            }

            if (!empty($data['Product'][$k]['offers']) && is_array($data['Product'][$k]['offers'])) {
                $offers = $data['Product'][$k]['offers'];
                if ('' !== $logo_image && (!isset($offers['seller']) || !is_array($offers['seller']))) {
                    $offers['seller'] = [];
                }
                if ('' !== $logo_image && is_array($offers['seller'])) {
                    $offers['seller']['logo'] = $logo_image;
                }
                $data['Product'][$k]['offers'] = $offers;
            }
        }
    }

    $is_search_route = function_exists('bw_ss_is_search_results_request') && bw_ss_is_search_results_request();
    if (!$is_search_route) {
        return $data;
    }

    $canonical = bw_seo_get_current_canonical_url();
    if ('' === $canonical) {
        $canonical = home_url('/search/');
    }
    $webpage_id = trailingslashit($canonical) . '#webpage';

    if (!empty($data['CollectionPage']) && is_array($data['CollectionPage'])) {
        foreach ($data['CollectionPage'] as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }
            $data['CollectionPage'][$key]['url'] = $canonical;
            $data['CollectionPage'][$key]['@id'] = $webpage_id;
            $data['CollectionPage'][$key]['isPartOf'] = ['@id' => home_url('/#website')];
        }
    }

    if (!empty($data['WebPage']) && is_array($data['WebPage'])) {
        foreach ($data['WebPage'] as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }
            $data['WebPage'][$key]['url'] = $canonical;
            $data['WebPage'][$key]['@id'] = $webpage_id;
        }
    }

    if (!empty($data['SearchResultsPage']) && is_array($data['SearchResultsPage'])) {
        foreach ($data['SearchResultsPage'] as $key => $entity) {
            if (!is_array($entity)) {
                continue;
            }
            $data['SearchResultsPage'][$key]['url'] = $canonical;
            $data['SearchResultsPage'][$key]['@id'] = $webpage_id;
        }
    }

    return $data;
}
add_filter('rank_math/json_ld', 'bw_seo_filter_rank_math_json_ld', 20, 2);

/**
 * Runtime diagnostics helpers (admin-only, query-gated).
 */
function bw_seo_is_debug_request()
{
    if (is_admin()) {
        if (!current_user_can('manage_options')) {
            return false;
        }
    } else {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return false;
        }
    }

    $flag = isset($_GET['bw_seo_debug']) ? sanitize_text_field(wp_unslash($_GET['bw_seo_debug'])) : '';
    return '1' === $flag;
}

function bw_seo_runtime_debug_admin_notice()
{
    if (!is_admin() || !bw_seo_is_debug_request()) {
        return;
    }

    $runtime_file = BW_MEW_PATH . 'includes/seo/runtime-seo.php';
    $runtime_mtime = file_exists($runtime_file) ? (int) filemtime($runtime_file) : 0;
    $schema_fix_version = defined('BW_SEO_SCHEMA_FIX_VERSION') ? (int) BW_SEO_SCHEMA_FIX_VERSION : 0;
    $rank_math_active = defined('RANK_MATH_VERSION') || class_exists('RankMath');
    $data = [
        'plugin_main_file' => BW_MEW_PATH . 'blackwork-core-plugin.php',
        'runtime_file' => $runtime_file,
        'runtime_file_exists' => file_exists($runtime_file) ? 'yes' : 'no',
        'runtime_file_mtime_unix' => $runtime_mtime,
        'runtime_file_mtime_gmt' => $runtime_mtime > 0 ? gmdate('c', $runtime_mtime) : '',
        'schema_fix_version' => $schema_fix_version,
        'runtime_loaded_constant' => defined('BW_SEO_RUNTIME_LOADED') ? 'yes' : 'no',
        'runtime_loaded_function' => function_exists('bw_seo_filter_rank_math_canonical') ? 'yes' : 'no',
        'schema_quality_function_image_local_logo' => function_exists('bw_seo_get_local_organization_logo_url') ? 'yes' : 'no',
        'schema_quality_function_jsonld_filter' => function_exists('bw_seo_filter_rank_math_json_ld') ? 'yes' : 'no',
        'rank_math_active' => $rank_math_active ? 'yes' : 'no',
        'filter_rank_math_canonical' => (string) has_filter('rank_math/frontend/canonical', 'bw_seo_filter_rank_math_canonical'),
        'filter_rank_math_robots' => (string) has_filter('rank_math/frontend/robots', 'bw_seo_filter_rank_math_robots'),
        'filter_rank_math_json_ld' => (string) has_filter('rank_math/json_ld', 'bw_seo_filter_rank_math_json_ld'),
        'filter_wp_robots' => (string) has_filter('wp_robots', 'bw_seo_filter_wp_robots'),
    ];

    echo '<div class="notice notice-info"><p><strong>BW SEO Debug</strong></p><pre style="white-space:pre-wrap;">'
        . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT))
        . '</pre></div>';
}
add_action('admin_notices', 'bw_seo_runtime_debug_admin_notice');

function bw_seo_runtime_debug_frontend_marker()
{
    if (!bw_seo_is_debug_request()) {
        return;
    }

    $resolved_fallback = bw_seo_resolve_social_image_url();
    $is_product = is_singular('product');
    $is_indexable = bw_seo_is_indexable_context();
    $runtime_file = BW_MEW_PATH . 'includes/seo/runtime-seo.php';
    $runtime_mtime = file_exists($runtime_file) ? (int) filemtime($runtime_file) : 0;
    $schema_fix_version = defined('BW_SEO_SCHEMA_FIX_VERSION') ? (int) BW_SEO_SCHEMA_FIX_VERSION : 0;
    $state = isset($GLOBALS['bw_seo_social_meta_state']) && is_array($GLOBALS['bw_seo_social_meta_state'])
        ? $GLOBALS['bw_seo_social_meta_state']
        : [];

    echo "\n<!-- BW SEO RUNTIME LOADED -->\n";
    echo "<!-- BW SEO SCHEMA FIX VERSION: " . esc_html((string) $schema_fix_version) . " -->\n";
    echo "<!-- BW SEO DEBUG: indexable=" . ($is_indexable ? 'yes' : 'no')
        . " product=" . ($is_product ? 'yes' : 'no')
        . " runtime_file=" . esc_html($runtime_file)
        . " runtime_mtime_unix=" . esc_html((string) $runtime_mtime)
        . " runtime_mtime_gmt=" . esc_html($runtime_mtime > 0 ? gmdate('c', $runtime_mtime) : '')
        . " schema_fix_version=" . esc_html((string) $schema_fix_version)
        . " has_schema_quality_fn=" . (function_exists('bw_seo_get_local_organization_logo_url') ? 'yes' : 'no')
        . " has_jsonld_filter_fn=" . (function_exists('bw_seo_filter_rank_math_json_ld') ? 'yes' : 'no')
        . " rank_math_jsonld_filter=" . esc_html((string) has_filter('rank_math/json_ld', 'bw_seo_filter_rank_math_json_ld'))
        . " fallback_image=" . esc_html($resolved_fallback)
        . " og_present=" . (!empty($state['og_image_tag_present']) ? 'yes' : 'no')
        . " tw_present=" . (!empty($state['twitter_image_tag_present']) ? 'yes' : 'no')
        . " -->\n";
}
add_action('wp_head', 'bw_seo_runtime_debug_frontend_marker', 1);
