<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_is_elementor_preview')) {
    function bw_header_is_elementor_preview()
    {
        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Plugin')) {
            return false;
        }
        $elementor = \Elementor\Plugin::$instance;
        return $elementor && isset($elementor->preview) && $elementor->preview->is_preview_mode();
    }
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

if (!function_exists('bw_header_is_hero_overlap_enabled')) {
    function bw_header_is_hero_overlap_enabled($settings = null)
    {
        if (!is_array($settings)) {
            $settings = bw_header_get_settings();
        }

        return !empty($settings['hero_overlap']['enabled']);
    }
}

if (!function_exists('bw_header_get_hero_overlap_page_ids')) {
    function bw_header_get_hero_overlap_page_ids($settings = null)
    {
        if (!is_array($settings)) {
            $settings = bw_header_get_settings();
        }

        $page_ids = isset($settings['hero_overlap']['page_ids']) && is_array($settings['hero_overlap']['page_ids'])
            ? $settings['hero_overlap']['page_ids']
            : [];

        return array_values(array_unique(array_filter(array_map('absint', $page_ids))));
    }
}

if (!function_exists('bw_header_is_hero_overlap_active')) {
    function bw_header_is_hero_overlap_active($settings = null)
    {
        if (!bw_header_is_hero_overlap_enabled($settings)) {
            return false;
        }

        if (!is_singular('page')) {
            return false;
        }

        $page_id = absint(get_queried_object_id());
        if ($page_id <= 0) {
            return false;
        }

        return in_array($page_id, bw_header_get_hero_overlap_page_ids($settings), true);
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

        if (bw_header_is_elementor_preview()) {
            return;
        }

        $settings = bw_header_get_settings();
        $breakpoint = bw_header_get_mobile_breakpoint($settings);
        $hero_overlap_active = bw_header_is_hero_overlap_active($settings);
        $inner_padding_unit = (isset($settings['inner_padding_unit']) && in_array($settings['inner_padding_unit'], ['px', '%'], true))
            ? $settings['inner_padding_unit']
            : 'px';
        $inner_padding = isset($settings['inner_padding']) && is_array($settings['inner_padding']) ? $settings['inner_padding'] : [];
        $inner_padding_top = isset($inner_padding['top']) ? (float) $inner_padding['top'] : 18;
        $inner_padding_right = isset($inner_padding['right']) ? (float) $inner_padding['right'] : 28;
        $inner_padding_bottom = isset($inner_padding['bottom']) ? (float) $inner_padding['bottom'] : 18;
        $inner_padding_left = isset($inner_padding['left']) ? (float) $inner_padding['left'] : 28;

        if ($inner_padding_unit === '%') {
            $inner_padding_top = 1;
        }
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
        $panel_blur_enabled = $menu_blur_enabled || $hero_overlap_active;
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
        // Responsive icon spacing and cart badge positioning are intentionally fixed in code.
        $mobile_right_icons_gap = 15;
        $mobile_cart_badge_offset_x = -9;
        $mobile_cart_badge_offset_y = 0;
        $desktop_cart_badge_offset_x = 0;
        $desktop_cart_badge_offset_y = 0;

        $mobile_inner_padding_top = 14;
        $mobile_inner_padding_right = 18;
        $mobile_inner_padding_bottom = 14;
        $mobile_inner_padding_left = 18;

        $mobile_hamburger_padding_top = 0;
        $mobile_hamburger_padding_right = 0;
        $mobile_hamburger_padding_bottom = 0;
        $mobile_hamburger_padding_left = 0;
        $mobile_hamburger_margin_top = 0;
        $mobile_hamburger_margin_right = 0;
        $mobile_hamburger_margin_bottom = 0;
        $mobile_hamburger_margin_left = 0;

        $mobile_search_padding_top = 0;
        $mobile_search_padding_right = 0;
        $mobile_search_padding_bottom = 0;
        $mobile_search_padding_left = 0;
        $mobile_search_margin_right = 0;
        $mobile_search_margin_bottom = 0;
        $mobile_search_margin_left = 0;

        $mobile_cart_padding_top = 0;
        $mobile_cart_padding_right = 25;
        $mobile_cart_padding_bottom = 0;
        $mobile_cart_padding_left = 0;
        $mobile_cart_margin_top = -5;
        $mobile_cart_margin_right = 0;
        $mobile_cart_margin_bottom = 0;
        $mobile_cart_margin_left = 0;

        $base_path = BW_MEW_PATH . 'includes/modules/header/assets/';
        $base_url = BW_MEW_URL . 'includes/modules/header/assets/';

        wp_enqueue_style(
            'bw-header-layout',
            $base_url . 'css/header-layout.css',
            [],
            filemtime($base_path . 'css/header-layout.css') ?: '1.0.0'
        );

        wp_enqueue_style(
            'bw-header-search-style',
            $base_url . 'css/bw-search.css',
            ['bw-header-layout'],
            filemtime($base_path . 'css/bw-search.css') ?: '1.0.0'
        );

        wp_enqueue_style(
            'bw-header-navshop-style',
            $base_url . 'css/bw-navshop.css',
            ['bw-header-layout'],
            filemtime($base_path . 'css/bw-navshop.css') ?: '1.0.0'
        );

        wp_enqueue_style(
            'bw-header-navigation-style',
            $base_url . 'css/bw-navigation.css',
            ['bw-header-layout'],
            filemtime($base_path . 'css/bw-navigation.css') ?: '1.0.0'
        );

        // Prevent legacy Elementor widget assets from overriding custom header styles/scripts.
        // wp_dequeue_* is a no-op when the handle is not enqueued; no guard needed.
        $legacy_style_handles = ['bw-search-style', 'bw-navshop-style', 'bw-navigation-style', 'bw-smart-header-style'];
        foreach ($legacy_style_handles as $handle) {
            wp_dequeue_style($handle);
        }

        // Build inline CSS as an array then implode — easier to add/remove rules.
        $css_parts = [];

        // — Mobile breakpoint rules —
        $css_parts[] = "@media (max-width: {$breakpoint}px) {";
        $css_parts[] = ".bw-custom-header{position:fixed !important;top:var(--bw-header-mobile-top-offset, 0px) !important;left:0 !important;right:0 !important;z-index:9998;width:auto !important;min-width:0 !important;max-width:none !important;margin:0 !important;box-sizing:border-box !important;transition: transform 0.3s ease-in-out, background-color 0.35s ease, opacity 0.35s ease;}";
        $css_parts[] = ".bw-custom-header.bw-mobile-scrolled{background-color:#ffffff !important;}";
        $css_parts[] = ".bw-custom-header .bw-custom-header__inner{padding: {$mobile_inner_padding_top}px {$mobile_inner_padding_right}px {$mobile_inner_padding_bottom}px {$mobile_inner_padding_left}px !important;width:100% !important;max-width:none !important;margin:0 !important;}";
        $css_parts[] = ".bw-custom-header__desktop{display:none;}";
        $css_parts[] = ".bw-custom-header__mobile{display:block;}";
        $css_parts[] = ".bw-custom-header .bw-navigation__toggle{display:inline-flex;}";
        $css_parts[] = ".bw-custom-header .bw-navigation__mobile-overlay{display:block;}";
        $css_parts[] = ".bw-custom-header .bw-navshop--hide-account-mobile .bw-navshop__account{display:none;}";
        $css_parts[] = ".bw-custom-header .bw-navshop__cart-label{display:none;}";
        $css_parts[] = ".bw-custom-header .bw-navshop__cart-icon{display:inline-flex;}";
        $css_parts[] = ".bw-custom-header .bw-search-button{display:inline-flex !important;background:transparent !important;border:none !important;box-shadow:none !important;padding:0 !important;}";
        $css_parts[] = ".bw-custom-header .bw-search-button__label{display:none;}";
        $css_parts[] = ".bw-custom-header .bw-search-button__icon{display:inline-flex;background:transparent !important;border:none !important;border-radius:0 !important;padding:0 !important;}";
        $css_parts[] = ".bw-custom-header__mobile-right{gap: {$mobile_right_icons_gap}px !important;}";
        $css_parts[] = ".bw-custom-header__mobile-left .bw-navigation__toggle{padding: {$mobile_hamburger_padding_top}px {$mobile_hamburger_padding_right}px {$mobile_hamburger_padding_bottom}px {$mobile_hamburger_padding_left}px !important;margin: {$mobile_hamburger_margin_top}px {$mobile_hamburger_margin_right}px {$mobile_hamburger_margin_bottom}px {$mobile_hamburger_margin_left}px !important;}";
        $css_parts[] = ".bw-custom-header__mobile-right .bw-header-search .bw-search-button{padding: {$mobile_search_padding_top}px {$mobile_search_padding_right}px {$mobile_search_padding_bottom}px {$mobile_search_padding_left}px !important;margin: -3px {$mobile_search_margin_right}px {$mobile_search_margin_bottom}px {$mobile_search_margin_left}px !important;}";
        $css_parts[] = ".bw-custom-header__mobile-right .bw-header-navshop--mobile .bw-navshop__cart{padding: {$mobile_cart_padding_top}px {$mobile_cart_padding_right}px {$mobile_cart_padding_bottom}px {$mobile_cart_padding_left}px !important;margin: {$mobile_cart_margin_top}px {$mobile_cart_margin_right}px {$mobile_cart_margin_bottom}px {$mobile_cart_margin_left}px !important;}";
        $css_parts[] = ".bw-custom-header__mobile-right .bw-header-navshop--mobile .bw-navshop__cart-count:not(.is-empty){top:26% !important;right:17px !important;transform: translate({$mobile_cart_badge_offset_x}px, calc(-50% + {$mobile_cart_badge_offset_y}px)) !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;min-width:14px !important;height:14px !important;padding:0 3px !important;line-height:1 !important;font-size:8px !important;font-weight:400 !important;}";
        // (badge is position:absolute — no flow-space compensation needed)
        $css_parts[] = "}";

        // — Desktop breakpoint rules —
        $css_parts[] = "@media (min-width: " . ($breakpoint + 1) . "px) {";
        $css_parts[] = ".bw-custom-header__desktop{display:flex;}";
        $css_parts[] = ".bw-custom-header__mobile{display:none;}";
        $css_parts[] = ".bw-custom-header .bw-navigation__toggle,.bw-custom-header .bw-navigation__mobile-overlay{display:none !important;}";
        $css_parts[] = ".bw-custom-header__desktop-right .bw-navshop__cart-count:not(.is-empty){transform: translate({$desktop_cart_badge_offset_x}px, {$desktop_cart_badge_offset_y}px) !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;min-width:14px !important;height:14px !important;padding:0 3px !important;line-height:1 !important;font-size:8px !important;font-weight:400 !important;}";
        $css_parts[] = "}";

        // — Background color —
        if ($smart_scroll_enabled) {
            $css_parts[] = ".bw-custom-header{background-color: " . bw_header_hex_to_rgba($smart_header_bg, $smart_header_bg_opacity) . " !important;}";
            $css_parts[] = ".bw-custom-header.bw-custom-header--smart.bw-header-scrolled{background-color: " . bw_header_hex_to_rgba($smart_header_scrolled_bg, $smart_header_scrolled_opacity) . " !important;}";
        } else {
            $css_parts[] = $background_transparent
                ? ".bw-custom-header{background-color: transparent !important;}"
                : ".bw-custom-header{background-color: {$header_bg} !important;}";
            $css_parts[] = ".bw-custom-header.bw-custom-header--smart.bw-header-scrolled{background-color: {$header_bg} !important;}";
        }

        // — Desktop inner padding —
        $css_parts[] = ".bw-custom-header__inner{padding: {$inner_padding_top}{$inner_padding_unit} {$inner_padding_right}{$inner_padding_unit} {$inner_padding_bottom}{$inner_padding_unit} {$inner_padding_left}{$inner_padding_unit} !important;}";

        // — Panel blur (tint computed only when blur is actually enabled) —
        if ($panel_blur_enabled) {
            $blur_tint = bw_header_hex_to_rgba($menu_blur_tint_color, $menu_blur_tint_opacity);
            $blur_scrolled_tint = bw_header_hex_to_rgba($menu_blur_scrolled_tint_color, $menu_blur_scrolled_tint_opacity);
            $mobile_blur_padding_top = 10;
            $mobile_blur_padding_right = 10;
            $mobile_blur_padding_bottom = 10;
            $mobile_blur_padding_left = 20;
            $mobile_blur_radius = 50;
            $css_parts[] = ".bw-custom-header__desktop-panel.is-blur-enabled{-webkit-backdrop-filter: blur({$menu_blur_amount}px);backdrop-filter: blur({$menu_blur_amount}px) !important;background-color:{$blur_tint} !important;padding: {$menu_blur_padding_top}px {$menu_blur_padding_right}px {$menu_blur_padding_bottom}px {$menu_blur_padding_left}px !important;border-radius: {$menu_blur_radius}px !important;}";
            $css_parts[] = ".bw-custom-header.bw-header-scrolled .bw-custom-header__desktop-panel.is-blur-enabled{background-color:{$blur_scrolled_tint} !important;}";
            // Mobile panel: always use the scrolled tint so the pill is visible
            // even at the very top of the page (no scroll required).
            $css_parts[] = ".bw-custom-header__mobile-panel.is-blur-enabled{-webkit-backdrop-filter: blur({$menu_blur_amount}px);backdrop-filter: blur({$menu_blur_amount}px) !important;background-color:{$blur_scrolled_tint} !important;padding: {$mobile_blur_padding_top}px {$mobile_blur_padding_right}px {$mobile_blur_padding_bottom}px {$mobile_blur_padding_left}px !important;border-radius: {$mobile_blur_radius}px !important;}";
        } else {
            $css_parts[] = ".bw-custom-header__desktop-panel{backdrop-filter:none !important;-webkit-backdrop-filter:none !important;background:transparent !important;padding:0 !important;margin:0 !important;border-radius:0 !important;}";
            $css_parts[] = ".bw-custom-header__mobile-panel{backdrop-filter:none !important;-webkit-backdrop-filter:none !important;background:transparent !important;padding:0 !important;margin:0 !important;border-radius:0 !important;}";
        }

        // Mobile scroll state must win over smart-header desktop rules.
        $css_parts[] = "@media (max-width: {$breakpoint}px){.bw-custom-header.is-mobile.bw-mobile-scrolled,.bw-custom-header.bw-custom-header--smart.is-mobile.bw-mobile-scrolled,.bw-custom-header.bw-custom-header--smart.bw-header-scrolled.is-mobile.bw-mobile-scrolled{background-color:#ffffff !important;}}";

        // Disable legacy smart-header body offset when custom header is enabled.
        $css_parts[] = "body:not(.elementor-editor-active){--smart-header-body-padding:0px !important;}";

        // — Hero overlap positioning and dark-zone color overrides —
        if ($hero_overlap_active) {
            $hero_overlap_mobile_tint_opacity = max($menu_blur_tint_opacity, 0.18);
            $hero_overlap_mobile_dark_tint_opacity = max($menu_blur_scrolled_tint_opacity, 0.22);
            $hero_overlap_mobile_tint = bw_header_hex_to_rgba($menu_blur_tint_color, $hero_overlap_mobile_tint_opacity);
            $hero_overlap_mobile_dark_tint = bw_header_hex_to_rgba($menu_blur_scrolled_tint_color, $hero_overlap_mobile_dark_tint_opacity);
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap{position:fixed !important;top:var(--bw-header-top-offset, 0px) !important;left:0 !important;right:0 !important;z-index:9998 !important;background-color:transparent !important;}";
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap.bw-header-preload{opacity:0 !important;visibility:hidden !important;pointer-events:none !important;}";
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap + .bw-header-spacer{display:block !important;height:0 !important;min-height:0 !important;}";
            $css_parts[] = "body.bw-has-sticky-header .bw-custom-header.bw-header--hero-overlap + .bw-header-spacer{display:block !important;height:0 !important;min-height:0 !important;}";
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap .bw-custom-header__desktop-panel.is-blur-enabled{box-shadow:0 14px 32px rgba(0,0,0,0.10) !important;}";
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-navigation__link,.bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-navshop__item,.bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-navigation__toggle,.bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-navigation__close{color:#ffffff !important;}";
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-custom-header__logo-image{filter:brightness(0) invert(1);}";
            $css_parts[] = ".bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-custom-header__logo-fallback{color:#ffffff !important;}";
            $css_parts[] = "@media (max-width: {$breakpoint}px){.bw-custom-header.bw-header--hero-overlap,.bw-custom-header.bw-custom-header--smart.bw-header--hero-overlap,.bw-custom-header.bw-header--hero-overlap.is-mobile.bw-mobile-scrolled,.bw-custom-header.bw-custom-header--smart.bw-header--hero-overlap.is-mobile.bw-mobile-scrolled,.bw-custom-header.bw-custom-header--smart.bw-header-scrolled.bw-header--hero-overlap{background-color:transparent !important;}.bw-custom-header.bw-header--hero-overlap .bw-custom-header__mobile-panel.is-blur-enabled{background-color:{$hero_overlap_mobile_tint} !important;box-shadow:0 12px 28px rgba(0,0,0,0.12) !important;}.bw-custom-header.bw-header--hero-overlap.bw-header-on-dark .bw-custom-header__mobile-panel.is-blur-enabled{background-color:{$hero_overlap_mobile_dark_tint} !important;box-shadow:0 14px 32px rgba(0,0,0,0.18) !important;}}";
        }

        // Suppress theme header elements (belt-and-suspenders alongside bw_header_disable_theme_header).
        $css_parts[] = "header#site-header,#site-header.site-header,.site-header.dynamic-header{display:none !important;}";

        wp_add_inline_style('bw-header-layout', implode("\n", $css_parts));

        wp_enqueue_script(
            'bw-header-navshop-script',
            $base_url . 'js/bw-navshop.js',
            ['jquery'],
            filemtime($base_path . 'js/bw-navshop.js') ?: '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-header-navigation-script',
            $base_url . 'js/bw-navigation.js',
            [],
            filemtime($base_path . 'js/bw-navigation.js') ?: '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-header-account-dropdown-script',
            $base_url . 'js/bw-account-dropdown.js',
            [],
            filemtime($base_path . 'js/bw-account-dropdown.js') ?: '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-header-init',
            $base_url . 'js/header-init.js',
            ['jquery', 'bw-header-navshop-script', 'bw-header-navigation-script', 'bw-header-account-dropdown-script'],
            filemtime($base_path . 'js/header-init.js') ?: '1.0.0',
            true
        );

        $legacy_script_handles = ['bw-search-script', 'bw-navshop-script', 'bw-navigation-script', 'bw-smart-header-script'];
        foreach ($legacy_script_handles as $handle) {
            wp_dequeue_script($handle);
        }

        wp_localize_script(
            'bw-header-init',
            'bwHeaderConfig',
            [
                'breakpoint' => $breakpoint,
                'title' => isset($settings['header_title']) ? (string) $settings['header_title'] : 'Blackwork Header',
                'search' => [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('bw_search_nonce'),
                    'messages' => [
                        'searchError' => __('Search error', 'bw'),
                        'connectionError' => __('Connection error', 'bw'),
                        'noResults' => __('No products found', 'bw'),
                    ],
                ],
                'smartScroll' => !empty($settings['features']['smart_scroll']),
                'heroOverlap' => $hero_overlap_active,
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
