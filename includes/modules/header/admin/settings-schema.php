<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_HEADER_OPTION_KEY')) {
    define('BW_HEADER_OPTION_KEY', 'bw_header_settings');
}

if (!function_exists('bw_header_default_settings')) {
    function bw_header_default_settings()
    {
        return [
            'enabled' => 0,
            'header_title' => 'Blackwork Header',
            'background_color' => '#efefef',
            'background_transparent' => 0,
            'inner_padding_unit' => 'px',
            'inner_padding' => [
                'top' => 18,
                'right' => 28,
                'bottom' => 18,
                'left' => 28,
            ],
            'logo_attachment_id' => 0,
            'logo_width' => 54,
            'logo_height' => 54,
            'menus' => [
                'desktop_menu_id' => 0,
                'mobile_menu_id' => 0,
            ],
            'breakpoints' => [
                'mobile' => 1024,
            ],
            'mobile_layout' => [
                'right_icons_gap' => 16,
                'cart_badge_offset_x' => 0,
                'cart_badge_offset_y' => 0,
                'cart_badge_size' => 1.2,
                'desktop_cart_badge_offset_x' => 0,
                'desktop_cart_badge_offset_y' => 0,
                'desktop_cart_badge_size' => 1.2,
                'inner_padding' => [
                    'top' => 14,
                    'right' => 18,
                    'bottom' => 14,
                    'left' => 18,
                ],
                'hamburger_padding' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'hamburger_margin' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'search_padding' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'search_margin' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'cart_padding' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'cart_margin' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
            ],
            'icons' => [
                'mobile_hamburger_attachment_id' => 0,
                'mobile_cart_attachment_id' => 0,
                'mobile_search_attachment_id' => 0,
            ],
            'labels' => [
                'search' => 'Search',
                'account' => 'Account',
                'cart' => 'Cart',
            ],
            'links' => [
                'account' => '/my-account/',
                'cart' => '/cart/',
            ],
            'features' => [
                'search' => 1,
                'navigation' => 1,
                'navshop' => 1,
                'smart_scroll' => 0,
            ],
            'smart_header' => [
                'scroll_down_threshold' => 100,
                'scroll_up_threshold' => 0,
                'scroll_delta' => 1,
                'header_bg_color' => '#efefef',
                'header_bg_opacity' => 1,
                'header_scrolled_bg_color' => '#efefef',
                'header_scrolled_bg_opacity' => 0.86,
                'menu_blur_enabled' => 1,
                'menu_blur_amount' => 20,
                'menu_blur_radius' => 12,
                'menu_blur_tint_color' => '#ffffff',
                'menu_blur_tint_opacity' => 0.15,
                'menu_blur_scrolled_tint_color' => '#ffffff',
                'menu_blur_scrolled_tint_opacity' => 0.15,
                'menu_blur_padding_top' => 5,
                'menu_blur_padding_right' => 10,
                'menu_blur_padding_bottom' => 5,
                'menu_blur_padding_left' => 10,
            ],
        ];
    }
}

if (!function_exists('bw_header_get_settings')) {
    function bw_header_get_settings()
    {
        $saved = get_option(BW_HEADER_OPTION_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_replace_recursive(bw_header_default_settings(), $saved);
    }
}

if (!function_exists('bw_header_sanitize_settings')) {
    function bw_header_sanitize_settings($input)
    {
        $defaults = bw_header_default_settings();
        $input = is_array($input) ? $input : [];

        $out = $defaults;
        $out['enabled'] = !empty($input['enabled']) ? 1 : 0;
        $out['header_title'] = isset($input['header_title']) ? sanitize_text_field($input['header_title']) : $defaults['header_title'];
        $out['background_color'] = isset($input['background_color']) ? sanitize_hex_color($input['background_color']) : $defaults['background_color'];
        if (empty($out['background_color'])) {
            $out['background_color'] = $defaults['background_color'];
        }
        $out['background_transparent'] = !empty($input['background_transparent']) ? 1 : 0;
        $out['inner_padding_unit'] = (isset($input['inner_padding_unit']) && in_array($input['inner_padding_unit'], ['px', '%'], true))
            ? $input['inner_padding_unit']
            : $defaults['inner_padding_unit'];
        $inner_padding = isset($input['inner_padding']) && is_array($input['inner_padding']) ? $input['inner_padding'] : [];
        $max_padding = $out['inner_padding_unit'] === '%' ? 100 : 400;
        $out['inner_padding']['top'] = isset($inner_padding['top']) ? max(0, min($max_padding, (float) $inner_padding['top'])) : $defaults['inner_padding']['top'];
        $out['inner_padding']['right'] = isset($inner_padding['right']) ? max(0, min($max_padding, (float) $inner_padding['right'])) : $defaults['inner_padding']['right'];
        $out['inner_padding']['bottom'] = isset($inner_padding['bottom']) ? max(0, min($max_padding, (float) $inner_padding['bottom'])) : $defaults['inner_padding']['bottom'];
        $out['inner_padding']['left'] = isset($inner_padding['left']) ? max(0, min($max_padding, (float) $inner_padding['left'])) : $defaults['inner_padding']['left'];

        $out['logo_attachment_id'] = isset($input['logo_attachment_id']) ? absint($input['logo_attachment_id']) : 0;
        $out['logo_width'] = isset($input['logo_width']) ? max(10, min(1200, absint($input['logo_width']))) : $defaults['logo_width'];
        $out['logo_height'] = isset($input['logo_height']) ? max(0, min(1200, absint($input['logo_height']))) : $defaults['logo_height'];

        $menus = isset($input['menus']) && is_array($input['menus']) ? $input['menus'] : [];
        $out['menus']['desktop_menu_id'] = isset($menus['desktop_menu_id']) ? absint($menus['desktop_menu_id']) : 0;
        $out['menus']['mobile_menu_id'] = isset($menus['mobile_menu_id']) ? absint($menus['mobile_menu_id']) : 0;

        $breakpoints = isset($input['breakpoints']) && is_array($input['breakpoints']) ? $input['breakpoints'] : [];
        $out['breakpoints']['mobile'] = isset($breakpoints['mobile']) ? max(320, min(1920, absint($breakpoints['mobile']))) : $defaults['breakpoints']['mobile'];

        $mobile_layout = isset($input['mobile_layout']) && is_array($input['mobile_layout']) ? $input['mobile_layout'] : [];
        $out['mobile_layout']['right_icons_gap'] = isset($mobile_layout['right_icons_gap']) ? max(0, min(200, (float) $mobile_layout['right_icons_gap'])) : $defaults['mobile_layout']['right_icons_gap'];
        $out['mobile_layout']['cart_badge_offset_x'] = isset($mobile_layout['cart_badge_offset_x']) ? max(-100, min(100, (float) $mobile_layout['cart_badge_offset_x'])) : $defaults['mobile_layout']['cart_badge_offset_x'];
        $out['mobile_layout']['cart_badge_offset_y'] = isset($mobile_layout['cart_badge_offset_y']) ? max(-100, min(100, (float) $mobile_layout['cart_badge_offset_y'])) : $defaults['mobile_layout']['cart_badge_offset_y'];
        $out['mobile_layout']['cart_badge_size'] = isset($mobile_layout['cart_badge_size']) ? max(0.6, min(3, (float) $mobile_layout['cart_badge_size'])) : $defaults['mobile_layout']['cart_badge_size'];
        $out['mobile_layout']['desktop_cart_badge_offset_x'] = isset($mobile_layout['desktop_cart_badge_offset_x']) ? max(-100, min(100, (float) $mobile_layout['desktop_cart_badge_offset_x'])) : $defaults['mobile_layout']['desktop_cart_badge_offset_x'];
        $out['mobile_layout']['desktop_cart_badge_offset_y'] = isset($mobile_layout['desktop_cart_badge_offset_y']) ? max(-100, min(100, (float) $mobile_layout['desktop_cart_badge_offset_y'])) : $defaults['mobile_layout']['desktop_cart_badge_offset_y'];
        $out['mobile_layout']['desktop_cart_badge_size'] = isset($mobile_layout['desktop_cart_badge_size']) ? max(0.6, min(3, (float) $mobile_layout['desktop_cart_badge_size'])) : $defaults['mobile_layout']['desktop_cart_badge_size'];
        $mobile_inner_padding = isset($mobile_layout['inner_padding']) && is_array($mobile_layout['inner_padding']) ? $mobile_layout['inner_padding'] : [];
        $out['mobile_layout']['inner_padding']['top'] = isset($mobile_inner_padding['top']) ? max(0, min(200, (float) $mobile_inner_padding['top'])) : $defaults['mobile_layout']['inner_padding']['top'];
        $out['mobile_layout']['inner_padding']['right'] = isset($mobile_inner_padding['right']) ? max(0, min(200, (float) $mobile_inner_padding['right'])) : $defaults['mobile_layout']['inner_padding']['right'];
        $out['mobile_layout']['inner_padding']['bottom'] = isset($mobile_inner_padding['bottom']) ? max(0, min(200, (float) $mobile_inner_padding['bottom'])) : $defaults['mobile_layout']['inner_padding']['bottom'];
        $out['mobile_layout']['inner_padding']['left'] = isset($mobile_inner_padding['left']) ? max(0, min(200, (float) $mobile_inner_padding['left'])) : $defaults['mobile_layout']['inner_padding']['left'];
        $mobile_box_fields = [
            'hamburger_padding',
            'hamburger_margin',
            'search_padding',
            'search_margin',
            'cart_padding',
            'cart_margin',
        ];
        foreach ($mobile_box_fields as $field) {
            $box = isset($mobile_layout[$field]) && is_array($mobile_layout[$field]) ? $mobile_layout[$field] : [];
            $is_margin = strpos($field, 'margin') !== false;
            $min = $is_margin ? -200 : 0;
            $max = 200;
            $out['mobile_layout'][$field]['top'] = isset($box['top']) ? max($min, min($max, (float) $box['top'])) : $defaults['mobile_layout'][$field]['top'];
            $out['mobile_layout'][$field]['right'] = isset($box['right']) ? max($min, min($max, (float) $box['right'])) : $defaults['mobile_layout'][$field]['right'];
            $out['mobile_layout'][$field]['bottom'] = isset($box['bottom']) ? max($min, min($max, (float) $box['bottom'])) : $defaults['mobile_layout'][$field]['bottom'];
            $out['mobile_layout'][$field]['left'] = isset($box['left']) ? max($min, min($max, (float) $box['left'])) : $defaults['mobile_layout'][$field]['left'];
        }

        $icons = isset($input['icons']) && is_array($input['icons']) ? $input['icons'] : [];
        $out['icons']['mobile_hamburger_attachment_id'] = isset($icons['mobile_hamburger_attachment_id']) ? absint($icons['mobile_hamburger_attachment_id']) : 0;
        $out['icons']['mobile_cart_attachment_id'] = isset($icons['mobile_cart_attachment_id']) ? absint($icons['mobile_cart_attachment_id']) : 0;
        $out['icons']['mobile_search_attachment_id'] = isset($icons['mobile_search_attachment_id']) ? absint($icons['mobile_search_attachment_id']) : 0;

        $labels = isset($input['labels']) && is_array($input['labels']) ? $input['labels'] : [];
        $out['labels']['search'] = isset($labels['search']) ? sanitize_text_field($labels['search']) : $defaults['labels']['search'];
        $out['labels']['account'] = isset($labels['account']) ? sanitize_text_field($labels['account']) : $defaults['labels']['account'];
        $out['labels']['cart'] = isset($labels['cart']) ? sanitize_text_field($labels['cart']) : $defaults['labels']['cart'];

        $links = isset($input['links']) && is_array($input['links']) ? $input['links'] : [];
        $out['links']['account'] = isset($links['account']) ? esc_url_raw($links['account']) : $defaults['links']['account'];
        $out['links']['cart'] = isset($links['cart']) ? esc_url_raw($links['cart']) : $defaults['links']['cart'];

        $features = isset($input['features']) && is_array($input['features']) ? $input['features'] : [];
        $out['features']['search'] = !empty($features['search']) ? 1 : 0;
        $out['features']['navigation'] = !empty($features['navigation']) ? 1 : 0;
        $out['features']['navshop'] = !empty($features['navshop']) ? 1 : 0;
        $out['features']['smart_scroll'] = !empty($features['smart_scroll']) ? 1 : 0;

        $smart_header = isset($input['smart_header']) && is_array($input['smart_header']) ? $input['smart_header'] : [];
        $out['smart_header']['scroll_down_threshold'] = isset($smart_header['scroll_down_threshold'])
            ? max(0, min(2000, absint($smart_header['scroll_down_threshold'])))
            : $defaults['smart_header']['scroll_down_threshold'];
        $out['smart_header']['scroll_up_threshold'] = isset($smart_header['scroll_up_threshold'])
            ? max(0, min(2000, absint($smart_header['scroll_up_threshold'])))
            : $defaults['smart_header']['scroll_up_threshold'];
        $out['smart_header']['scroll_delta'] = isset($smart_header['scroll_delta'])
            ? max(1, min(100, absint($smart_header['scroll_delta'])))
            : $defaults['smart_header']['scroll_delta'];
        $out['smart_header']['header_bg_color'] = isset($smart_header['header_bg_color'])
            ? sanitize_hex_color($smart_header['header_bg_color'])
            : $defaults['smart_header']['header_bg_color'];
        if (empty($out['smart_header']['header_bg_color'])) {
            $out['smart_header']['header_bg_color'] = $defaults['smart_header']['header_bg_color'];
        }
        $out['smart_header']['header_bg_opacity'] = isset($smart_header['header_bg_opacity'])
            ? max(0, min(1, (float) $smart_header['header_bg_opacity']))
            : $defaults['smart_header']['header_bg_opacity'];
        $out['smart_header']['header_scrolled_bg_color'] = isset($smart_header['header_scrolled_bg_color'])
            ? sanitize_hex_color($smart_header['header_scrolled_bg_color'])
            : $defaults['smart_header']['header_scrolled_bg_color'];
        if (empty($out['smart_header']['header_scrolled_bg_color'])) {
            $out['smart_header']['header_scrolled_bg_color'] = $defaults['smart_header']['header_scrolled_bg_color'];
        }
        $out['smart_header']['header_scrolled_bg_opacity'] = isset($smart_header['header_scrolled_bg_opacity'])
            ? max(0, min(1, (float) $smart_header['header_scrolled_bg_opacity']))
            : $defaults['smart_header']['header_scrolled_bg_opacity'];
        $out['smart_header']['menu_blur_enabled'] = !empty($smart_header['menu_blur_enabled']) ? 1 : 0;
        $out['smart_header']['menu_blur_amount'] = isset($smart_header['menu_blur_amount'])
            ? max(0, min(100, absint($smart_header['menu_blur_amount'])))
            : $defaults['smart_header']['menu_blur_amount'];
        $out['smart_header']['menu_blur_radius'] = isset($smart_header['menu_blur_radius'])
            ? max(0, min(200, absint($smart_header['menu_blur_radius'])))
            : $defaults['smart_header']['menu_blur_radius'];
        $out['smart_header']['menu_blur_tint_color'] = isset($smart_header['menu_blur_tint_color'])
            ? sanitize_hex_color($smart_header['menu_blur_tint_color'])
            : $defaults['smart_header']['menu_blur_tint_color'];
        if (empty($out['smart_header']['menu_blur_tint_color'])) {
            $out['smart_header']['menu_blur_tint_color'] = $defaults['smart_header']['menu_blur_tint_color'];
        }
        $out['smart_header']['menu_blur_tint_opacity'] = isset($smart_header['menu_blur_tint_opacity'])
            ? max(0, min(1, (float) $smart_header['menu_blur_tint_opacity']))
            : $defaults['smart_header']['menu_blur_tint_opacity'];
        $out['smart_header']['menu_blur_scrolled_tint_color'] = isset($smart_header['menu_blur_scrolled_tint_color'])
            ? sanitize_hex_color($smart_header['menu_blur_scrolled_tint_color'])
            : $defaults['smart_header']['menu_blur_scrolled_tint_color'];
        if (empty($out['smart_header']['menu_blur_scrolled_tint_color'])) {
            $out['smart_header']['menu_blur_scrolled_tint_color'] = $defaults['smart_header']['menu_blur_scrolled_tint_color'];
        }
        $out['smart_header']['menu_blur_scrolled_tint_opacity'] = isset($smart_header['menu_blur_scrolled_tint_opacity'])
            ? max(0, min(1, (float) $smart_header['menu_blur_scrolled_tint_opacity']))
            : $defaults['smart_header']['menu_blur_scrolled_tint_opacity'];
        $out['smart_header']['menu_blur_padding_top'] = isset($smart_header['menu_blur_padding_top'])
            ? max(0, min(200, absint($smart_header['menu_blur_padding_top'])))
            : $defaults['smart_header']['menu_blur_padding_top'];
        $out['smart_header']['menu_blur_padding_right'] = isset($smart_header['menu_blur_padding_right'])
            ? max(0, min(200, absint($smart_header['menu_blur_padding_right'])))
            : $defaults['smart_header']['menu_blur_padding_right'];
        $out['smart_header']['menu_blur_padding_bottom'] = isset($smart_header['menu_blur_padding_bottom'])
            ? max(0, min(200, absint($smart_header['menu_blur_padding_bottom'])))
            : $defaults['smart_header']['menu_blur_padding_bottom'];
        $out['smart_header']['menu_blur_padding_left'] = isset($smart_header['menu_blur_padding_left'])
            ? max(0, min(200, absint($smart_header['menu_blur_padding_left'])))
            : $defaults['smart_header']['menu_blur_padding_left'];

        if (empty($out['menus']['mobile_menu_id'])) {
            $out['menus']['mobile_menu_id'] = $out['menus']['desktop_menu_id'];
        }

        return $out;
    }
}

if (!function_exists('bw_header_register_settings')) {
    function bw_header_register_settings()
    {
        register_setting(
            'bw_header_settings_group',
            BW_HEADER_OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_header_sanitize_settings',
                'default' => bw_header_default_settings(),
            ]
        );
    }
}
add_action('admin_init', 'bw_header_register_settings');
