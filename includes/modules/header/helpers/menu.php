<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove current-menu-* classes from the home/front-page menu item
 * when the current request is NOT the front page. WordPress sometimes
 * incorrectly marks the Home item as current on WooCommerce archive
 * pages or other pages where is_front_page() fires unexpectedly.
 */
if (!function_exists('bw_header_fix_home_current_class')) {
    function bw_header_fix_home_current_class($classes, $item, $args, $depth)
    {
        if (is_front_page()) {
            return $classes;
        }

        $is_home_item = false;

        // Match by URL — normalise protocol so http/https differences are ignored.
        if (isset($item->url) && $item->url !== '') {
            $strip_proto = function ($url) {
                return strtolower(preg_replace('#^https?://#', '//', rtrim($url, '/')));
            };
            if ($strip_proto($item->url) === $strip_proto(home_url('/'))) {
                $is_home_item = true;
            }
        }

        // Also match when the item is a page type linked to the static front page.
        if (!$is_home_item) {
            $front_page_id = (int) get_option('page_on_front');
            if ($front_page_id > 0 && isset($item->object_id) && (int) $item->object_id === $front_page_id) {
                $is_home_item = true;
            }
        }

        if ($is_home_item) {
            $classes = array_diff($classes, [
                'current-menu-item',
                'current-menu-parent',
                'current-menu-ancestor',
                'current_page_item',
                'current_page_parent',
                'current_page_ancestor',
            ]);
        }

        return array_values($classes);
    }
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
