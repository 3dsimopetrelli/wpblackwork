<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_filter_nav_link_class')) {
    function bw_header_filter_nav_link_class($atts)
    {
        if (isset($atts['class']) && !empty($atts['class'])) {
            $atts['class'] .= ' bw-navigation__link';
        } else {
            $atts['class'] = 'bw-navigation__link';
        }

        return $atts;
    }
}

if (!function_exists('bw_header_render_menu')) {
    function bw_header_render_menu($menu_id, $menu_class)
    {
        $menu_id = absint($menu_id);
        if ($menu_id <= 0) {
            return '';
        }

        add_filter('nav_menu_link_attributes', 'bw_header_filter_nav_link_class', 10, 1);
        $html = wp_nav_menu([
            'menu' => $menu_id,
            'menu_class' => $menu_class,
            'container' => false,
            'fallback_cb' => '__return_empty_string',
            'echo' => false,
            'depth' => 2,
        ]);
        remove_filter('nav_menu_link_attributes', 'bw_header_filter_nav_link_class', 10);

        return is_string($html) ? $html : '';
    }
}

if (!function_exists('bw_header_render_menu_location')) {
    function bw_header_render_menu_location($theme_location, $menu_class)
    {
        $theme_location = sanitize_key($theme_location);
        if ($theme_location === '') {
            return '';
        }

        add_filter('nav_menu_link_attributes', 'bw_header_filter_nav_link_class', 10, 1);
        $html = wp_nav_menu([
            'theme_location' => $theme_location,
            'menu_class' => $menu_class,
            'container' => false,
            'fallback_cb' => '__return_empty_string',
            'echo' => false,
            'depth' => 2,
        ]);
        remove_filter('nav_menu_link_attributes', 'bw_header_filter_nav_link_class', 10);

        return is_string($html) ? $html : '';
    }
}

if (!function_exists('bw_header_filter_footer_nav_link_class')) {
    function bw_header_filter_footer_nav_link_class($atts)
    {
        if (isset($atts['class']) && !empty($atts['class'])) {
            $atts['class'] .= ' bw-navigation__mobile-footer-link';
        } else {
            $atts['class'] = 'bw-navigation__mobile-footer-link';
        }

        return $atts;
    }
}

if (!function_exists('bw_header_render_footer_menu')) {
    function bw_header_render_footer_menu($theme_location, $menu_class)
    {
        $theme_location = sanitize_key($theme_location);
        if ($theme_location === '') {
            return '';
        }

        add_filter('nav_menu_link_attributes', 'bw_header_filter_footer_nav_link_class', 10, 1);
        $html = wp_nav_menu([
            'theme_location' => $theme_location,
            'menu_class' => $menu_class,
            'container' => false,
            'fallback_cb' => '__return_empty_string',
            'echo' => false,
            'depth' => 1,
        ]);
        remove_filter('nav_menu_link_attributes', 'bw_header_filter_footer_nav_link_class', 10);

        return is_string($html) ? $html : '';
    }
}
