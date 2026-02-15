<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_is_enabled')) {
    function bw_header_is_enabled()
    {
        $settings = bw_header_get_settings();
        return !empty($settings['enabled']);
    }
}

if (!function_exists('bw_header_get_mobile_breakpoint')) {
    function bw_header_get_mobile_breakpoint($settings = null)
    {
        if (!is_array($settings)) {
            $settings = bw_header_get_settings();
        }

        $breakpoint = isset($settings['breakpoints']['mobile']) ? absint($settings['breakpoints']['mobile']) : 1024;
        return max(320, min(1920, $breakpoint));
    }
}

if (!function_exists('bw_header_hex_to_rgba')) {
    function bw_header_hex_to_rgba($hex, $opacity = 1)
    {
        $hex = sanitize_hex_color($hex);
        if (!$hex) {
            return 'rgba(239,239,239,1)';
        }

        $opacity = max(0, min(1, (float) $opacity));
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return sprintf('rgba(%d,%d,%d,%.2f)', $r, $g, $b, $opacity);
    }
}

if (!function_exists('bw_header_enqueue_assets')) {
    function bw_header_enqueue_assets()
    {
        if (is_admin() || !bw_header_is_enabled()) {
            return;
        }

        if (defined('ELEMENTOR_VERSION') && class_exists('\\Elementor\\Plugin')) {
            $elementor = \Elementor\Plugin::$instance;
            if ($elementor && isset($elementor->preview) && $elementor->preview->is_preview_mode()) {
                return;
            }
        }

        $settings = bw_header_get_settings();
        $breakpoint = bw_header_get_mobile_breakpoint($settings);
        $mobile_layout = isset($settings['mobile_layout']) && is_array($settings['mobile_layout']) ? $settings['mobile_layout'] : [];
        $inner_padding_unit = (isset($settings['inner_padding_unit']) && in_array($settings['inner_padding_unit'], ['px', '%'], true))
            ? $settings['inner_padding_unit']
            : 'px';
        $inner_padding = isset($settings['inner_padding']) && is_array($settings['inner_padding']) ? $settings['inner_padding'] : [];
        $inner_padding_top = isset($inner_padding['top']) ? (float) $inner_padding['top'] : 18;
        $inner_padding_right = isset($inner_padding['right']) ? (float) $inner_padding['right'] : 28;
        $inner_padding_bottom = isset($inner_padding['bottom']) ? (float) $inner_padding['bottom'] : 18;
        $inner_padding_left = isset($inner_padding['left']) ? (float) $inner_padding['left'] : 28;
        $background_transparent = !empty($settings['background_transparent']);
        $header_bg = isset($settings['background_color']) ? sanitize_hex_color($settings['background_color']) : '#efefef';
        if (!$header_bg) {
            $header_bg = '#efefef';
        }
        $smart_header = isset($settings['smart_header']) && is_array($settings['smart_header']) ? $settings['smart_header'] : [];
        $smart_scroll_enabled = !empty($settings['features']['smart_scroll']);
        $smart_header_bg = isset($smart_header['header_bg_color']) ? sanitize_hex_color($smart_header['header_bg_color']) : '#efefef';
        if (!$smart_header_bg) {
            $smart_header_bg = '#efefef';
        }
        $smart_header_bg_opacity = isset($smart_header['header_bg_opacity']) ? max(0, min(1, (float) $smart_header['header_bg_opacity'])) : 1;
        $smart_header_scrolled_bg = isset($smart_header['header_scrolled_bg_color']) ? sanitize_hex_color($smart_header['header_scrolled_bg_color']) : '#efefef';
        if (!$smart_header_scrolled_bg) {
            $smart_header_scrolled_bg = '#efefef';
        }
        $smart_header_scrolled_opacity = isset($smart_header['header_scrolled_bg_opacity']) ? max(0, min(1, (float) $smart_header['header_scrolled_bg_opacity'])) : 0.86;

        $menu_blur_enabled = !empty($smart_header['menu_blur_enabled']);
        $menu_blur_amount = isset($smart_header['menu_blur_amount']) ? max(0, min(100, absint($smart_header['menu_blur_amount']))) : 20;
        $menu_blur_radius = isset($smart_header['menu_blur_radius']) ? max(0, min(200, absint($smart_header['menu_blur_radius']))) : 12;
        $menu_blur_tint_color = isset($smart_header['menu_blur_tint_color']) ? sanitize_hex_color($smart_header['menu_blur_tint_color']) : '#ffffff';
        if (!$menu_blur_tint_color) {
            $menu_blur_tint_color = '#ffffff';
        }
        $menu_blur_tint_opacity = isset($smart_header['menu_blur_tint_opacity']) ? max(0, min(1, (float) $smart_header['menu_blur_tint_opacity'])) : 0.15;
        $menu_blur_scrolled_tint_color = isset($smart_header['menu_blur_scrolled_tint_color']) ? sanitize_hex_color($smart_header['menu_blur_scrolled_tint_color']) : $menu_blur_tint_color;
        if (!$menu_blur_scrolled_tint_color) {
            $menu_blur_scrolled_tint_color = $menu_blur_tint_color;
        }
        $menu_blur_scrolled_tint_opacity = isset($smart_header['menu_blur_scrolled_tint_opacity']) ? max(0, min(1, (float) $smart_header['menu_blur_scrolled_tint_opacity'])) : $menu_blur_tint_opacity;
        $menu_blur_padding_top = isset($smart_header['menu_blur_padding_top']) ? max(0, min(200, absint($smart_header['menu_blur_padding_top']))) : 5;
        $menu_blur_padding_right = isset($smart_header['menu_blur_padding_right']) ? max(0, min(200, absint($smart_header['menu_blur_padding_right']))) : 10;
        $menu_blur_padding_bottom = isset($smart_header['menu_blur_padding_bottom']) ? max(0, min(200, absint($smart_header['menu_blur_padding_bottom']))) : 5;
        $menu_blur_padding_left = isset($smart_header['menu_blur_padding_left']) ? max(0, min(200, absint($smart_header['menu_blur_padding_left']))) : 10;
        $mobile_right_icons_gap = isset($mobile_layout['right_icons_gap']) ? max(0, min(200, (float) $mobile_layout['right_icons_gap'])) : 16;
        $mobile_cart_badge_offset_x = isset($mobile_layout['cart_badge_offset_x']) ? max(-100, min(100, (float) $mobile_layout['cart_badge_offset_x'])) : 0;
        $mobile_cart_badge_offset_y = isset($mobile_layout['cart_badge_offset_y']) ? max(-100, min(100, (float) $mobile_layout['cart_badge_offset_y'])) : 0;
        $mobile_cart_badge_size = isset($mobile_layout['cart_badge_size']) ? max(0.6, min(3, (float) $mobile_layout['cart_badge_size'])) : 1.2;
        $desktop_cart_badge_offset_x = isset($mobile_layout['desktop_cart_badge_offset_x']) ? max(-100, min(100, (float) $mobile_layout['desktop_cart_badge_offset_x'])) : 0;
        $desktop_cart_badge_offset_y = isset($mobile_layout['desktop_cart_badge_offset_y']) ? max(-100, min(100, (float) $mobile_layout['desktop_cart_badge_offset_y'])) : 0;
        $desktop_cart_badge_size = isset($mobile_layout['desktop_cart_badge_size']) ? max(0.6, min(3, (float) $mobile_layout['desktop_cart_badge_size'])) : 1.2;
        $mobile_inner_padding = isset($mobile_layout['inner_padding']) && is_array($mobile_layout['inner_padding']) ? $mobile_layout['inner_padding'] : ['top' => 14, 'right' => 18, 'bottom' => 14, 'left' => 18];
        $mobile_inner_padding_top = isset($mobile_inner_padding['top']) ? max(0, min(200, (float) $mobile_inner_padding['top'])) : 14;
        $mobile_inner_padding_right = isset($mobile_inner_padding['right']) ? max(0, min(200, (float) $mobile_inner_padding['right'])) : 18;
        $mobile_inner_padding_bottom = isset($mobile_inner_padding['bottom']) ? max(0, min(200, (float) $mobile_inner_padding['bottom'])) : 14;
        $mobile_inner_padding_left = isset($mobile_inner_padding['left']) ? max(0, min(200, (float) $mobile_inner_padding['left'])) : 18;

        $mobile_default_box = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
        $mobile_hamburger_padding = isset($mobile_layout['hamburger_padding']) && is_array($mobile_layout['hamburger_padding']) ? $mobile_layout['hamburger_padding'] : $mobile_default_box;
        $mobile_hamburger_margin = isset($mobile_layout['hamburger_margin']) && is_array($mobile_layout['hamburger_margin']) ? $mobile_layout['hamburger_margin'] : $mobile_default_box;
        $mobile_search_padding = isset($mobile_layout['search_padding']) && is_array($mobile_layout['search_padding']) ? $mobile_layout['search_padding'] : $mobile_default_box;
        $mobile_search_margin = isset($mobile_layout['search_margin']) && is_array($mobile_layout['search_margin']) ? $mobile_layout['search_margin'] : $mobile_default_box;
        $mobile_cart_padding = isset($mobile_layout['cart_padding']) && is_array($mobile_layout['cart_padding']) ? $mobile_layout['cart_padding'] : $mobile_default_box;
        $mobile_cart_margin = isset($mobile_layout['cart_margin']) && is_array($mobile_layout['cart_margin']) ? $mobile_layout['cart_margin'] : $mobile_default_box;

        $mobile_hamburger_padding_top = isset($mobile_hamburger_padding['top']) ? max(0, min(200, (float) $mobile_hamburger_padding['top'])) : 0;
        $mobile_hamburger_padding_right = isset($mobile_hamburger_padding['right']) ? max(0, min(200, (float) $mobile_hamburger_padding['right'])) : 0;
        $mobile_hamburger_padding_bottom = isset($mobile_hamburger_padding['bottom']) ? max(0, min(200, (float) $mobile_hamburger_padding['bottom'])) : 0;
        $mobile_hamburger_padding_left = isset($mobile_hamburger_padding['left']) ? max(0, min(200, (float) $mobile_hamburger_padding['left'])) : 0;
        $mobile_hamburger_margin_top = isset($mobile_hamburger_margin['top']) ? max(-200, min(200, (float) $mobile_hamburger_margin['top'])) : 0;
        $mobile_hamburger_margin_right = isset($mobile_hamburger_margin['right']) ? max(-200, min(200, (float) $mobile_hamburger_margin['right'])) : 0;
        $mobile_hamburger_margin_bottom = isset($mobile_hamburger_margin['bottom']) ? max(-200, min(200, (float) $mobile_hamburger_margin['bottom'])) : 0;
        $mobile_hamburger_margin_left = isset($mobile_hamburger_margin['left']) ? max(-200, min(200, (float) $mobile_hamburger_margin['left'])) : 0;

        $mobile_search_padding_top = isset($mobile_search_padding['top']) ? max(0, min(200, (float) $mobile_search_padding['top'])) : 0;
        $mobile_search_padding_right = isset($mobile_search_padding['right']) ? max(0, min(200, (float) $mobile_search_padding['right'])) : 0;
        $mobile_search_padding_bottom = isset($mobile_search_padding['bottom']) ? max(0, min(200, (float) $mobile_search_padding['bottom'])) : 0;
        $mobile_search_padding_left = isset($mobile_search_padding['left']) ? max(0, min(200, (float) $mobile_search_padding['left'])) : 0;
        $mobile_search_margin_top = isset($mobile_search_margin['top']) ? max(-200, min(200, (float) $mobile_search_margin['top'])) : 4;
        $mobile_search_margin_right = isset($mobile_search_margin['right']) ? max(-200, min(200, (float) $mobile_search_margin['right'])) : 0;
        $mobile_search_margin_bottom = isset($mobile_search_margin['bottom']) ? max(-200, min(200, (float) $mobile_search_margin['bottom'])) : 0;
        $mobile_search_margin_left = isset($mobile_search_margin['left']) ? max(-200, min(200, (float) $mobile_search_margin['left'])) : 0;

        $mobile_cart_padding_top = isset($mobile_cart_padding['top']) ? max(0, min(200, (float) $mobile_cart_padding['top'])) : 0;
        $mobile_cart_padding_right = isset($mobile_cart_padding['right']) ? max(0, min(200, (float) $mobile_cart_padding['right'])) : 0;
        $mobile_cart_padding_bottom = isset($mobile_cart_padding['bottom']) ? max(0, min(200, (float) $mobile_cart_padding['bottom'])) : 0;
        $mobile_cart_padding_left = isset($mobile_cart_padding['left']) ? max(0, min(200, (float) $mobile_cart_padding['left'])) : 0;
        $mobile_cart_margin_top = isset($mobile_cart_margin['top']) ? max(-200, min(200, (float) $mobile_cart_margin['top'])) : 0;
        $mobile_cart_margin_right = isset($mobile_cart_margin['right']) ? max(-200, min(200, (float) $mobile_cart_margin['right'])) : 0;
        $mobile_cart_margin_bottom = isset($mobile_cart_margin['bottom']) ? max(-200, min(200, (float) $mobile_cart_margin['bottom'])) : 0;
        $mobile_cart_margin_left = isset($mobile_cart_margin['left']) ? max(-200, min(200, (float) $mobile_cart_margin['left'])) : 0;

        $base_path = BW_MEW_PATH . 'includes/modules/header/assets/';
        $base_url = BW_MEW_URL . 'includes/modules/header/assets/';

        wp_enqueue_style(
            'bw-header-layout',
            $base_url . 'css/header-layout.css',
            [],
            file_exists($base_path . 'css/header-layout.css') ? filemtime($base_path . 'css/header-layout.css') : '1.0.0'
        );

        wp_enqueue_style(
            'bw-header-search-style',
            $base_url . 'css/bw-search.css',
            ['bw-header-layout'],
            file_exists($base_path . 'css/bw-search.css') ? filemtime($base_path . 'css/bw-search.css') : '1.0.0'
        );

        wp_enqueue_style(
            'bw-header-navshop-style',
            $base_url . 'css/bw-navshop.css',
            ['bw-header-layout'],
            file_exists($base_path . 'css/bw-navshop.css') ? filemtime($base_path . 'css/bw-navshop.css') : '1.0.0'
        );

        wp_enqueue_style(
            'bw-header-navigation-style',
            $base_url . 'css/bw-navigation.css',
            ['bw-header-layout'],
            file_exists($base_path . 'css/bw-navigation.css') ? filemtime($base_path . 'css/bw-navigation.css') : '1.0.0'
        );

        // Prevent legacy Elementor widget assets from overriding custom header styles/scripts.
        $legacy_style_handles = ['bw-search-style', 'bw-navshop-style', 'bw-navigation-style', 'bw-smart-header-style'];
        foreach ($legacy_style_handles as $handle) {
            if (wp_style_is($handle, 'enqueued')) {
                wp_dequeue_style($handle);
            }
        }

        $inline_css = "\n@media (max-width: {$breakpoint}px) {\n"
            . ".bw-custom-header{position:fixed !important;top:var(--bw-header-mobile-top-offset, 0px) !important;left:0 !important;right:0 !important;z-index:9998;width:auto !important;min-width:0 !important;max-width:none !important;margin:0 !important;box-sizing:border-box !important;transition: transform 0.3s ease-in-out, background-color 0.35s ease, opacity 0.35s ease;}\n"
            . ".bw-custom-header.bw-mobile-scrolled{background-color:#ffffff !important;}\n"
            . ".bw-custom-header .bw-custom-header__inner{padding: {$mobile_inner_padding_top}px {$mobile_inner_padding_right}px {$mobile_inner_padding_bottom}px {$mobile_inner_padding_left}px !important;width:100% !important;max-width:none !important;margin:0 !important;}\n"
            . ".bw-custom-header__desktop{display:none;}\n"
            . ".bw-custom-header__mobile{display:block;}\n"
            . ".bw-custom-header .bw-navigation__toggle{display:inline-flex;}\n"
            . ".bw-custom-header .bw-navigation__mobile-overlay{display:block;}\n"
            . ".bw-custom-header .bw-navshop--hide-account-mobile .bw-navshop__account{display:none;}\n"
            . ".bw-custom-header .bw-navshop__cart-label{display:none;}\n"
            . ".bw-custom-header .bw-navshop__cart-icon{display:inline-flex;}\n"
            . ".bw-custom-header .bw-search-button{display:inline-flex !important;background:transparent !important;border:none !important;box-shadow:none !important;padding:0 !important;min-width:auto !important;min-height:auto !important;}\n"
            . ".bw-custom-header .bw-search-button__label{display:none;}\n"
            . ".bw-custom-header .bw-search-button__icon{display:inline-flex;background:transparent !important;border:none !important;border-radius:0 !important;padding:0 !important;}\n"
            . ".bw-custom-header__mobile-right{gap: {$mobile_right_icons_gap}px !important;}\n"
            . ".bw-custom-header__mobile-left .bw-navigation__toggle{padding: {$mobile_hamburger_padding_top}px {$mobile_hamburger_padding_right}px {$mobile_hamburger_padding_bottom}px {$mobile_hamburger_padding_left}px !important;margin: {$mobile_hamburger_margin_top}px {$mobile_hamburger_margin_right}px {$mobile_hamburger_margin_bottom}px {$mobile_hamburger_margin_left}px !important;}\n"
            . ".bw-custom-header__mobile-right .bw-header-search .bw-search-button{padding: {$mobile_search_padding_top}px {$mobile_search_padding_right}px {$mobile_search_padding_bottom}px {$mobile_search_padding_left}px !important;margin: {$mobile_search_margin_top}px {$mobile_search_margin_right}px {$mobile_search_margin_bottom}px {$mobile_search_margin_left}px !important;}\n"
            . ".bw-custom-header__mobile-right .bw-header-navshop--mobile .bw-navshop__cart{padding: {$mobile_cart_padding_top}px {$mobile_cart_padding_right}px {$mobile_cart_padding_bottom}px {$mobile_cart_padding_left}px !important;margin: {$mobile_cart_margin_top}px {$mobile_cart_margin_right}px {$mobile_cart_margin_bottom}px {$mobile_cart_margin_left}px !important;}\n"
            . ".bw-custom-header__mobile-right .bw-header-navshop--mobile .bw-navshop__cart-count{transform: translate({$mobile_cart_badge_offset_x}px, {$mobile_cart_badge_offset_y}px) !important;min-width: {$mobile_cart_badge_size}em !important;height: {$mobile_cart_badge_size}em !important;line-height: {$mobile_cart_badge_size}em !important;}\n"
            . ".bw-custom-header__mobile-right .bw-header-navshop--mobile .bw-navshop__cart:has(.bw-navshop__cart-count.is-empty){margin-right: 8px !important;}\n"
            . "}\n"
            . "@media (min-width: " . ($breakpoint + 1) . "px) {\n"
            . ".bw-custom-header__desktop{display:flex;}\n"
            . ".bw-custom-header__mobile{display:none;}\n"
            . ".bw-custom-header .bw-navigation__toggle,.bw-custom-header .bw-navigation__mobile-overlay{display:none !important;}\n"
            . ".bw-custom-header__desktop-right .bw-navshop__cart-count{transform: translate({$desktop_cart_badge_offset_x}px, {$desktop_cart_badge_offset_y}px) !important;min-width: {$desktop_cart_badge_size}em !important;height: {$desktop_cart_badge_size}em !important;line-height: {$desktop_cart_badge_size}em !important;}\n"
            . "}\n";

        if ($smart_scroll_enabled) {
            $inline_css .= ".bw-custom-header{background-color: " . bw_header_hex_to_rgba($smart_header_bg, $smart_header_bg_opacity) . " !important;}\n";
            $inline_css .= ".bw-custom-header.bw-custom-header--smart.bw-header-scrolled{background-color: " . bw_header_hex_to_rgba($smart_header_scrolled_bg, $smart_header_scrolled_opacity) . " !important;}\n";
        } else {
            if ($background_transparent) {
                $inline_css .= ".bw-custom-header{background-color: transparent !important;}\n";
            } else {
                $inline_css .= ".bw-custom-header{background-color: {$header_bg} !important;}\n";
            }
            $inline_css .= ".bw-custom-header.bw-custom-header--smart.bw-header-scrolled{background-color: {$header_bg} !important;}\n";
        }
        $inline_css .= ".bw-custom-header__inner{padding: {$inner_padding_top}{$inner_padding_unit} {$inner_padding_right}{$inner_padding_unit} {$inner_padding_bottom}{$inner_padding_unit} {$inner_padding_left}{$inner_padding_unit} !important;}\n";
        $blur_tint = bw_header_hex_to_rgba($menu_blur_tint_color, $menu_blur_tint_opacity);
        $blur_scrolled_tint = bw_header_hex_to_rgba($menu_blur_scrolled_tint_color, $menu_blur_scrolled_tint_opacity);

        if ($menu_blur_enabled) {
            // Desktop panel blur
            $inline_css .= ".bw-custom-header__desktop-panel.is-blur-enabled{-webkit-backdrop-filter: blur({$menu_blur_amount}px);backdrop-filter: blur({$menu_blur_amount}px) !important;background-color:{$blur_tint} !important;padding: {$menu_blur_padding_top}px {$menu_blur_padding_right}px {$menu_blur_padding_bottom}px {$menu_blur_padding_left}px !important;border-radius: {$menu_blur_radius}px !important;}\n";
            $inline_css .= ".bw-custom-header.bw-header-scrolled .bw-custom-header__desktop-panel.is-blur-enabled{background-color:{$blur_scrolled_tint} !important;}\n";
            // Mobile panel blur (compact padding to avoid double-spacing with __inner)
            $mobile_blur_v = max(2, min(15, intval($menu_blur_padding_top)));
            $mobile_blur_h = max(2, min(10, intval(round($menu_blur_padding_right * 0.5))));
            $inline_css .= ".bw-custom-header__mobile-panel.is-blur-enabled{-webkit-backdrop-filter: blur({$menu_blur_amount}px);backdrop-filter: blur({$menu_blur_amount}px) !important;background-color:{$blur_tint} !important;padding: {$mobile_blur_v}px {$mobile_blur_h}px !important;border-radius: {$menu_blur_radius}px !important;}\n";
            $inline_css .= ".bw-custom-header.bw-header-scrolled .bw-custom-header__mobile-panel.is-blur-enabled{background-color:{$blur_scrolled_tint} !important;}\n";
        } else {
            $inline_css .= ".bw-custom-header__desktop-panel{backdrop-filter:none !important;-webkit-backdrop-filter:none !important;background:transparent !important;padding:0 !important;margin:0 !important;border-radius:0 !important;}\n";
            $inline_css .= ".bw-custom-header__mobile-panel{backdrop-filter:none !important;-webkit-backdrop-filter:none !important;background:transparent !important;padding:0 !important;margin:0 !important;border-radius:0 !important;}\n";
        }

        // Mobile scroll state must win over smart-header desktop rules.
        $inline_css .= "@media (max-width: {$breakpoint}px){.bw-custom-header.is-mobile.bw-mobile-scrolled,.bw-custom-header.bw-custom-header--smart.is-mobile.bw-mobile-scrolled,.bw-custom-header.bw-custom-header--smart.bw-header-scrolled.is-mobile.bw-mobile-scrolled{background-color:#ffffff !important;}}\n";

        // Disable legacy smart-header body offset when custom header is enabled.
        $inline_css .= "body:not(.elementor-editor-active){--smart-header-body-padding:0px !important;}\n";

        wp_add_inline_style('bw-header-layout', $inline_css);

        wp_enqueue_script(
            'bw-header-search-script',
            $base_url . 'js/bw-search.js',
            ['jquery'],
            file_exists($base_path . 'js/bw-search.js') ? filemtime($base_path . 'js/bw-search.js') : '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-header-navshop-script',
            $base_url . 'js/bw-navshop.js',
            ['jquery'],
            file_exists($base_path . 'js/bw-navshop.js') ? filemtime($base_path . 'js/bw-navshop.js') : '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-header-navigation-script',
            $base_url . 'js/bw-navigation.js',
            [],
            file_exists($base_path . 'js/bw-navigation.js') ? filemtime($base_path . 'js/bw-navigation.js') : '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-header-init',
            $base_url . 'js/header-init.js',
            ['jquery', 'bw-header-search-script', 'bw-header-navshop-script', 'bw-header-navigation-script'],
            file_exists($base_path . 'js/header-init.js') ? filemtime($base_path . 'js/header-init.js') : '1.0.0',
            true
        );

        $legacy_script_handles = ['bw-search-script', 'bw-navshop-script', 'bw-navigation-script', 'bw-smart-header-script'];
        foreach ($legacy_script_handles as $handle) {
            if (wp_script_is($handle, 'enqueued')) {
                wp_dequeue_script($handle);
            }
        }

        wp_localize_script(
            'bw-header-search-script',
            'bwSearchAjax',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_search_nonce'),
            ]
        );

        wp_localize_script(
            'bw-header-init',
            'bwHeaderConfig',
            [
                'breakpoint' => $breakpoint,
                'title' => isset($settings['header_title']) ? (string) $settings['header_title'] : 'Blackwork Header',
                'smartScroll' => !empty($settings['features']['smart_scroll']),
                'smartHeader' => [
                    'scrollDownThreshold' => isset($settings['smart_header']['scroll_down_threshold']) ? absint($settings['smart_header']['scroll_down_threshold']) : 100,
                    'scrollUpThreshold' => isset($settings['smart_header']['scroll_up_threshold']) ? absint($settings['smart_header']['scroll_up_threshold']) : 0,
                    'scrollDelta' => isset($settings['smart_header']['scroll_delta']) ? absint($settings['smart_header']['scroll_delta']) : 1,
                ],
            ]
        );
    }
}
add_action('wp_enqueue_scripts', 'bw_header_enqueue_assets', 20);
