<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_get_guarded_theme_roots')) {
    function bw_tbl_get_guarded_theme_roots()
    {
        $roots = [];

        $child_uri = untrailingslashit((string) get_stylesheet_directory_uri());
        $child_dir = untrailingslashit((string) get_stylesheet_directory());
        if ('' !== $child_uri && '' !== $child_dir) {
            $roots[] = [
                'uri' => $child_uri,
                'dir' => $child_dir,
            ];
        }

        $parent_uri = untrailingslashit((string) get_template_directory_uri());
        $parent_dir = untrailingslashit((string) get_template_directory());
        if ('' !== $parent_uri && '' !== $parent_dir && $parent_uri !== $child_uri) {
            $roots[] = [
                'uri' => $parent_uri,
                'dir' => $parent_dir,
            ];
        }

        return $roots;
    }
}

if (!function_exists('bw_tbl_style_src_to_local_path')) {
    function bw_tbl_style_src_to_local_path($src, $root_uri, $root_dir)
    {
        $src = is_string($src) ? trim($src) : '';
        if ('' === $src) {
            return '';
        }

        $root_uri = is_string($root_uri) ? untrailingslashit($root_uri) : '';
        $root_dir = is_string($root_dir) ? untrailingslashit($root_dir) : '';
        if ('' === $root_uri || '' === $root_dir) {
            return '';
        }

        if (0 === strpos($src, '//')) {
            $scheme = is_ssl() ? 'https:' : 'http:';
            $src = $scheme . $src;
        }

        if (0 !== strpos($src, 'http://') && 0 !== strpos($src, 'https://')) {
            $src = home_url('/' . ltrim($src, '/'));
        }

        $src_no_query = strtok($src, '?');
        $src_no_query = is_string($src_no_query) ? $src_no_query : '';
        if ('' === $src_no_query) {
            return '';
        }

        if (0 !== strpos($src_no_query, $root_uri . '/')) {
            return '';
        }

        $relative = ltrim(substr($src_no_query, strlen($root_uri)), '/');
        if ('' === $relative) {
            return '';
        }

        $path = $root_dir . '/' . $relative;
        if (!file_exists($path) || !is_readable($path)) {
            return '';
        }

        return $path;
    }
}

if (!function_exists('bw_tbl_strip_media_queries')) {
    function bw_tbl_strip_media_queries($css)
    {
        $css = is_string($css) ? $css : '';
        if ('' === $css) {
            return '';
        }

        $out = '';
        $len = strlen($css);
        $offset = 0;

        while ($offset < $len) {
            $media_pos = strpos($css, '@media', $offset);
            if (false === $media_pos) {
                $out .= substr($css, $offset);
                break;
            }

            $out .= substr($css, $offset, $media_pos - $offset);
            $open_brace_pos = strpos($css, '{', $media_pos);
            if (false === $open_brace_pos) {
                break;
            }

            $depth = 0;
            $closed = false;
            for ($i = $open_brace_pos; $i < $len; $i++) {
                $char = $css[$i];
                if ('{' === $char) {
                    $depth++;
                } elseif ('}' === $char) {
                    $depth--;
                    if ($depth <= 0) {
                        $offset = $i + 1;
                        $closed = true;
                        break;
                    }
                }
            }

            if (!$closed) {
                break;
            }
        }

        return trim($out);
    }
}

if (!function_exists('bw_tbl_match_theme_root_for_style_src')) {
    function bw_tbl_match_theme_root_for_style_src($src_no_query, $roots)
    {
        foreach ($roots as $root) {
            $uri = isset($root['uri']) ? (string) $root['uri'] : '';
            if ('' !== $uri && 0 === strpos($src_no_query, $uri . '/')) {
                return $root;
            }
        }

        return null;
    }
}

if (!function_exists('bw_tbl_apply_elementor_child_theme_css_guard')) {
    function bw_tbl_apply_elementor_child_theme_css_guard()
    {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        $footer_option = function_exists('bw_tbl_get_footer_option') ? bw_tbl_get_footer_option() : [];
        $disable_all = !empty($footer_option['elementor_disable_child_css']);
        $disable_breakpoints = !empty($footer_option['elementor_disable_child_breakpoints']);

        if (!$disable_all && !$disable_breakpoints) {
            return;
        }

        global $wp_styles;
        if (!($wp_styles instanceof WP_Styles)) {
            return;
        }

        $roots = bw_tbl_get_guarded_theme_roots();
        if (empty($roots)) {
            return;
        }

        foreach ($wp_styles->queue as $handle) {
            $registered = isset($wp_styles->registered[$handle]) ? $wp_styles->registered[$handle] : null;
            if (!($registered instanceof _WP_Dependency)) {
                continue;
            }

            $src = isset($registered->src) ? (string) $registered->src : '';
            if ('' === $src) {
                continue;
            }

            $src_no_query = strtok($src, '?');
            $src_no_query = is_string($src_no_query) ? $src_no_query : '';
            if ('' === $src_no_query) {
                continue;
            }

            // Handle relative style src values.
            if (0 !== strpos($src_no_query, 'http://') && 0 !== strpos($src_no_query, 'https://') && 0 !== strpos($src_no_query, '//')) {
                $src_no_query = home_url('/' . ltrim($src_no_query, '/'));
            } elseif (0 === strpos($src_no_query, '//')) {
                $src_no_query = (is_ssl() ? 'https:' : 'http:') . $src_no_query;
            }

            $matched_root = bw_tbl_match_theme_root_for_style_src($src_no_query, $roots);
            if (!is_array($matched_root)) {
                continue;
            }

            if ($disable_all) {
                wp_dequeue_style($handle);
                continue;
            }

            $path = bw_tbl_style_src_to_local_path(
                $src,
                isset($matched_root['uri']) ? (string) $matched_root['uri'] : '',
                isset($matched_root['dir']) ? (string) $matched_root['dir'] : ''
            );
            if ('' === $path) {
                continue;
            }

            $raw_css = file_get_contents($path);
            if (!is_string($raw_css) || '' === $raw_css) {
                continue;
            }

            $stripped = bw_tbl_strip_media_queries($raw_css);
            if ('' === $stripped) {
                wp_dequeue_style($handle);
                continue;
            }

            $deps = isset($registered->deps) && is_array($registered->deps) ? $registered->deps : [];
            $ver = isset($registered->ver) ? $registered->ver : false;
            $media = isset($registered->args) ? $registered->args : 'all';

            wp_dequeue_style($handle);
            wp_deregister_style($handle);
            wp_register_style($handle, false, $deps, $ver, $media);
            wp_enqueue_style($handle);
            wp_add_inline_style($handle, $stripped);
        }
    }
}
add_action('wp_enqueue_scripts', 'bw_tbl_apply_elementor_child_theme_css_guard', 9999);
