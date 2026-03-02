<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_allow_font_upload_mimes')) {
    function bw_tbl_allow_font_upload_mimes($mimes)
    {
        if (!current_user_can('manage_options')) {
            return $mimes;
        }

        $mimes['woff'] = 'font/woff';
        $mimes['woff2'] = 'font/woff2';

        return $mimes;
    }
}
add_filter('upload_mimes', 'bw_tbl_allow_font_upload_mimes');

if (!function_exists('bw_tbl_fix_font_filetype_and_ext')) {
    function bw_tbl_fix_font_filetype_and_ext($data, $file, $filename, $mimes)
    {
        $filetype = wp_check_filetype($filename, $mimes);
        if (empty($filetype['ext'])) {
            return $data;
        }

        if ('woff' === $filetype['ext']) {
            $data['ext'] = 'woff';
            $data['type'] = 'font/woff';
            $data['proper_filename'] = $filename;
        }

        if ('woff2' === $filetype['ext']) {
            $data['ext'] = 'woff2';
            $data['type'] = 'font/woff2';
            $data['proper_filename'] = $filename;
        }

        return $data;
    }
}
add_filter('wp_check_filetype_and_ext', 'bw_tbl_fix_font_filetype_and_ext', 10, 4);

if (!function_exists('bw_tbl_default_custom_fonts_option')) {
    function bw_tbl_default_custom_fonts_option()
    {
        return [
            'version' => 1,
            'fonts' => [],
        ];
    }
}

if (!function_exists('bw_tbl_get_custom_fonts_option')) {
    function bw_tbl_get_custom_fonts_option()
    {
        $saved = get_option(BW_TBL_CUSTOM_FONTS_OPTION, []);
        $saved = is_array($saved) ? $saved : [];

        $option = array_replace_recursive(bw_tbl_default_custom_fonts_option(), $saved);
        $option['fonts'] = isset($option['fonts']) && is_array($option['fonts']) ? $option['fonts'] : [];

        return $option;
    }
}

if (!function_exists('bw_tbl_normalize_font_style')) {
    function bw_tbl_normalize_font_style($value)
    {
        $style = strtolower(trim((string) $value));
        $allowed = ['normal', 'italic', 'oblique'];

        if (!in_array($style, $allowed, true)) {
            return 'normal';
        }

        return $style;
    }
}

if (!function_exists('bw_tbl_normalize_font_weight')) {
    function bw_tbl_normalize_font_weight($value)
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return '400';
        }

        if (preg_match('/^(normal|bold|bolder|lighter)$/i', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^[1-9]00$/', $value)) {
            return $value;
        }

        return '400';
    }
}

if (!function_exists('bw_tbl_font_source_from_attachment_url')) {
    function bw_tbl_font_source_from_attachment_url($raw_url, $expected_format)
    {
        $url = esc_url_raw((string) $raw_url);
        if ('' === $url) {
            return '';
        }

        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($extension !== $expected_format) {
            return '';
        }

        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id <= 0) {
            return '';
        }

        $file_path = get_attached_file($attachment_id);
        if (!is_string($file_path) || '' === $file_path || !file_exists($file_path)) {
            return '';
        }

        $filetype = wp_check_filetype_and_ext($file_path, wp_basename($file_path));
        $detected_ext = isset($filetype['ext']) ? strtolower((string) $filetype['ext']) : '';
        $detected_type = isset($filetype['type']) ? (string) $filetype['type'] : '';

        if ($detected_ext !== $expected_format) {
            return '';
        }

        $mime = (string) get_post_mime_type($attachment_id);
        $allowed_mimes = [
            'woff' => ['font/woff', 'application/font-woff', 'application/x-font-woff', 'application/octet-stream'],
            'woff2' => ['font/woff2', 'application/font-woff2', 'application/x-font-woff2', 'application/octet-stream'],
        ];

        if (
            !isset($allowed_mimes[$expected_format])
            || !in_array($mime, $allowed_mimes[$expected_format], true)
            || !in_array($detected_type, $allowed_mimes[$expected_format], true)
        ) {
            return '';
        }

        $resolved_url = wp_get_attachment_url($attachment_id);
        return $resolved_url ? esc_url_raw($resolved_url) : '';
    }
}

if (!function_exists('bw_tbl_sanitize_custom_fonts_option')) {
    function bw_tbl_sanitize_custom_fonts_option($input)
    {
        $input = is_array($input) ? $input : [];
        $raw_fonts = isset($input['fonts']) && is_array($input['fonts']) ? $input['fonts'] : [];

        $sanitized_fonts = [];

        foreach ($raw_fonts as $font) {
            if (!is_array($font)) {
                continue;
            }

            $family = isset($font['font_family']) ? sanitize_text_field(wp_unslash($font['font_family'])) : '';
            if ('' === $family) {
                continue;
            }

            $raw_sources = isset($font['sources']) && is_array($font['sources']) ? $font['sources'] : [];
            $woff2 = isset($raw_sources['woff2']) ? bw_tbl_font_source_from_attachment_url($raw_sources['woff2'], 'woff2') : '';
            $woff = isset($raw_sources['woff']) ? bw_tbl_font_source_from_attachment_url($raw_sources['woff'], 'woff') : '';

            if ('' === $woff2 && '' === $woff) {
                continue;
            }

            $sanitized_fonts[] = [
                'font_family' => $family,
                'sources' => [
                    'woff2' => $woff2,
                    'woff' => $woff,
                ],
                'font_weight' => bw_tbl_normalize_font_weight(isset($font['font_weight']) ? $font['font_weight'] : ''),
                'font_style' => bw_tbl_normalize_font_style(isset($font['font_style']) ? $font['font_style'] : ''),
            ];
        }

        return [
            'version' => 1,
            'fonts' => $sanitized_fonts,
        ];
    }
}

if (!function_exists('bw_tbl_get_valid_custom_fonts')) {
    function bw_tbl_get_valid_custom_fonts()
    {
        $fonts_option = bw_tbl_get_custom_fonts_option();
        if (empty($fonts_option['fonts']) || !is_array($fonts_option['fonts'])) {
            return [];
        }

        $fonts = [];

        foreach ($fonts_option['fonts'] as $font) {
            if (!is_array($font)) {
                continue;
            }

            $family = isset($font['font_family']) ? sanitize_text_field((string) $font['font_family']) : '';
            if ('' === $family) {
                continue;
            }

            $sources = isset($font['sources']) && is_array($font['sources']) ? $font['sources'] : [];
            $src_chunks = [];

            if (!empty($sources['woff2'])) {
                $src_chunks[] = 'url("' . esc_url_raw($sources['woff2']) . '") format("woff2")';
            }

            if (!empty($sources['woff'])) {
                $src_chunks[] = 'url("' . esc_url_raw($sources['woff']) . '") format("woff")';
            }

            if (empty($src_chunks)) {
                continue;
            }

            $fonts[] = [
                'font_family' => $family,
                'sources' => [
                    'woff2' => !empty($sources['woff2']) ? esc_url_raw($sources['woff2']) : '',
                    'woff' => !empty($sources['woff']) ? esc_url_raw($sources['woff']) : '',
                ],
                'font_weight' => bw_tbl_normalize_font_weight(isset($font['font_weight']) ? $font['font_weight'] : '400'),
                'font_style' => bw_tbl_normalize_font_style(isset($font['font_style']) ? $font['font_style'] : 'normal'),
            ];
        }

        return $fonts;
    }
}

if (!function_exists('bw_tbl_get_custom_font_families')) {
    function bw_tbl_get_custom_font_families()
    {
        $fonts = bw_tbl_get_valid_custom_fonts();
        if (empty($fonts)) {
            return [];
        }

        $families = [];
        $seen = [];

        foreach ($fonts as $font) {
            if (!is_array($font)) {
                continue;
            }

            $family = isset($font['font_family']) ? sanitize_text_field((string) $font['font_family']) : '';
            if ('' === $family || isset($seen[$family])) {
                continue;
            }

            $seen[$family] = true;
            $families[] = $family;
        }

        return $families;
    }
}

if (!function_exists('bw_tbl_build_custom_fonts_css')) {
    function bw_tbl_build_custom_fonts_css()
    {
        $fonts = bw_tbl_get_valid_custom_fonts();
        if (empty($fonts)) {
            return '';
        }

        $css_rules = [];

        foreach ($fonts as $font) {
            if (!is_array($font)) {
                continue;
            }

            $family = isset($font['font_family']) ? sanitize_text_field((string) $font['font_family']) : '';
            if ('' === $family) {
                continue;
            }

            $sources = isset($font['sources']) && is_array($font['sources']) ? $font['sources'] : [];
            $src_chunks = [];

            if (!empty($sources['woff2'])) {
                $src_chunks[] = 'url("' . esc_url_raw($sources['woff2']) . '") format("woff2")';
            }

            if (!empty($sources['woff'])) {
                $src_chunks[] = 'url("' . esc_url_raw($sources['woff']) . '") format("woff")';
            }

            if (empty($src_chunks)) {
                continue;
            }

            $weight = bw_tbl_normalize_font_weight(isset($font['font_weight']) ? $font['font_weight'] : '400');
            $style = bw_tbl_normalize_font_style(isset($font['font_style']) ? $font['font_style'] : 'normal');

            $css_rules[] = sprintf(
                "@font-face{font-family:'%s';src:%s;font-weight:%s;font-style:%s;font-display:swap;}",
                esc_attr($family),
                implode(',', $src_chunks),
                esc_attr($weight),
                esc_attr($style)
            );
        }

        if (empty($css_rules)) {
            return '';
        }

        return implode("\n", $css_rules);
    }
}

if (!function_exists('bw_tbl_enqueue_custom_fonts_css')) {
    function bw_tbl_enqueue_custom_fonts_css()
    {
        static $did_enqueue = false;
        if ($did_enqueue) {
            return;
        }

        if (!bw_tbl_is_feature_enabled('custom_fonts_enabled')) {
            return;
        }

        $css = bw_tbl_build_custom_fonts_css();
        if ('' === $css) {
            return;
        }

        wp_register_style('bw-tbl-custom-fonts', false, [], null);
        wp_enqueue_style('bw-tbl-custom-fonts');
        wp_add_inline_style('bw-tbl-custom-fonts', $css);
        $did_enqueue = true;
    }
}
add_action('wp_enqueue_scripts', 'bw_tbl_enqueue_custom_fonts_css', 20);
