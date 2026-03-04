<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'bw_mf_register_term_meta', 11);

if (!function_exists('bw_mf_register_term_meta')) {
    function bw_mf_register_term_meta()
    {
        register_term_meta('bw_media_folder', 'bw_color', [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => false,
            'sanitize_callback' => 'bw_mf_sanitize_hex_color',
        ]);

        register_term_meta('bw_media_folder', 'bw_pinned', [
            'type' => 'integer',
            'single' => true,
            'default' => 0,
            'show_in_rest' => false,
            'sanitize_callback' => 'bw_mf_sanitize_checkbox_int',
        ]);

        register_term_meta('bw_media_folder', 'bw_sort', [
            'type' => 'integer',
            'single' => true,
            'default' => 0,
            'show_in_rest' => false,
            'sanitize_callback' => 'absint',
        ]);

        register_term_meta('bw_media_folder', 'bw_mf_icon_color', [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => false,
            'sanitize_callback' => 'bw_mf_sanitize_hex_color',
        ]);
    }
}

if (!function_exists('bw_mf_sanitize_hex_color')) {
    function bw_mf_sanitize_hex_color($value)
    {
        $color = sanitize_hex_color((string) $value);
        return $color ? $color : '';
    }
}

if (!function_exists('bw_mf_sanitize_checkbox_int')) {
    function bw_mf_sanitize_checkbox_int($value)
    {
        return !empty($value) ? 1 : 0;
    }
}
