<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!bw_mf_is_corner_indicator_enabled()) {
    return;
}

add_filter('wp_prepare_attachment_for_js', 'bw_mf_prepare_attachment_corner_marker_data', 20, 3);

if (!function_exists('bw_mf_prepare_attachment_corner_marker_data')) {
    function bw_mf_prepare_attachment_corner_marker_data($response, $attachment, $meta)
    {
        if (!is_admin() || !is_array($response) || !($attachment instanceof WP_Post)) {
            return $response;
        }

        $response['bw_mf_has_folder'] = 0;
        $response['bw_mf_folder_color'] = '';

        $term_ids = wp_get_object_terms($attachment->ID, 'bw_media_folder', [
            'fields' => 'ids',
        ]);

        if (is_wp_error($term_ids) || empty($term_ids)) {
            return $response;
        }

        $first_term_id = (int) reset($term_ids);
        $color = bw_mf_sanitize_hex_color((string) get_term_meta($first_term_id, 'bw_mf_icon_color', true));

        $response['bw_mf_has_folder'] = 1;
        $response['bw_mf_folder_color'] = ($color !== '') ? $color : '';

        return $response;
    }
}
