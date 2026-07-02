<?php
/**
 * Blackwork Site Settings — front-end site-layout runtime (guarded).
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!function_exists('bw_site_layout_default_settings')) {
    function bw_site_layout_default_settings()
    {
        return [
            'version' => 1,
            'enabled' => 0,
            'max_content_width' => 1400,
            'desktop_padding' => 32,
            'tablet_padding' => 24,
            'mobile_padding' => 18,
            'apply_to_main_content' => 1,
            'apply_to_header' => 1,
            'apply_to_footer' => 1,
            'allow_full_bleed_sections' => 1,
        ];
    }
}

if (!function_exists('bw_site_layout_sanitize_settings')) {
    function bw_site_layout_sanitize_settings($input)
    {
        $input = is_array($input) ? $input : [];
        $defaults = bw_site_layout_default_settings();

        return [
            'version' => 1,
            'enabled' => !empty($input['enabled']) ? 1 : 0,
            'max_content_width' => isset($input['max_content_width']) ? max(960, min(2400, absint($input['max_content_width']))) : $defaults['max_content_width'],
            'desktop_padding' => isset($input['desktop_padding']) ? max(0, min(200, absint($input['desktop_padding']))) : $defaults['desktop_padding'],
            'tablet_padding' => isset($input['tablet_padding']) ? max(0, min(160, absint($input['tablet_padding']))) : $defaults['tablet_padding'],
            'mobile_padding' => isset($input['mobile_padding']) ? max(0, min(120, absint($input['mobile_padding']))) : $defaults['mobile_padding'],
            'apply_to_main_content' => !empty($input['apply_to_main_content']) ? 1 : 0,
            'apply_to_header' => !empty($input['apply_to_header']) ? 1 : 0,
            'apply_to_footer' => !empty($input['apply_to_footer']) ? 1 : 0,
            'allow_full_bleed_sections' => !empty($input['allow_full_bleed_sections']) ? 1 : 0,
        ];
    }
}

if (!function_exists('bw_site_layout_get_settings')) {
    function bw_site_layout_get_settings()
    {
        $saved = get_option(BW_SITE_LAYOUT_OPTION, []);
        $saved = is_array($saved) ? $saved : [];

        return array_replace(bw_site_layout_default_settings(), $saved);
    }
}

if (!function_exists('bw_site_layout_is_enabled')) {
    function bw_site_layout_is_enabled()
    {
        $settings = bw_site_layout_get_settings();
        return !empty($settings['enabled']);
    }
}

if (!function_exists('bw_site_layout_build_runtime_css')) {
    function bw_site_layout_build_runtime_css($settings)
    {
        $max_content_width = isset($settings['max_content_width']) ? max(960, min(2400, absint($settings['max_content_width']))) : 1400;
        $desktop_padding = isset($settings['desktop_padding']) ? max(0, min(200, absint($settings['desktop_padding']))) : 32;
        $tablet_padding = isset($settings['tablet_padding']) ? max(0, min(160, absint($settings['tablet_padding']))) : 24;
        $mobile_padding = isset($settings['mobile_padding']) ? max(0, min(120, absint($settings['mobile_padding']))) : 18;

        $css = [];
        $css[] = ':root{--bw-site-max-width:' . $max_content_width . 'px;--bw-site-padding-x:' . $desktop_padding . 'px;--bw-site-padding-x-tablet:' . $tablet_padding . 'px;--bw-site-padding-x-mobile:' . $mobile_padding . 'px;--bw-site-shell-padding:var(--bw-site-padding-x);--bw-site-breakout-extra:320px;}';
        $css[] = '@media (max-width: 1024px){:root{--bw-site-shell-padding:var(--bw-site-padding-x-tablet);}}';
        $css[] = '@media (max-width: 767px){:root{--bw-site-shell-padding:var(--bw-site-padding-x-mobile);}}';
        $css[] = 'body.bw-site-layout-active{--bw-site-shell-max-width:calc(var(--bw-site-max-width) + (var(--bw-site-shell-padding) * 2));}';
        $css[] = 'body.bw-site-layout-active.bw-site-layout-main-enabled .bw-site-layout-main-shell,body.bw-site-layout-active.bw-site-layout-main-enabled .bw-tbl-runtime-template-content{width:100%;max-width:var(--bw-site-shell-max-width);margin-inline:auto;padding-inline:var(--bw-site-shell-padding);box-sizing:border-box;}';
        $css[] = 'body.bw-site-layout-active.bw-site-layout-header-enabled .bw-custom-header__inner{width:100%;max-width:var(--bw-site-shell-max-width) !important;margin-inline:auto !important;box-sizing:border-box !important;}';
        $css[] = 'body.bw-site-layout-active.bw-site-layout-footer-enabled .bw-site-layout-footer-shell{width:100%;max-width:var(--bw-site-shell-max-width);margin-inline:auto;padding-inline:var(--bw-site-shell-padding);box-sizing:border-box;}';

        return implode("\n", $css);
    }
}

if (!function_exists('bw_site_layout_enqueue_frontend_runtime_styles')) {
    function bw_site_layout_enqueue_frontend_runtime_styles()
    {
        if (is_admin() || !bw_site_layout_is_enabled()) {
            return;
        }

        wp_enqueue_style('bw-fullbleed-style');
        wp_add_inline_style('bw-fullbleed-style', bw_site_layout_build_runtime_css(bw_site_layout_get_settings()));
    }
}
add_action('wp_enqueue_scripts', 'bw_site_layout_enqueue_frontend_runtime_styles', 40);

if (!function_exists('bw_site_layout_add_body_classes')) {
    function bw_site_layout_add_body_classes($classes)
    {
        if (is_admin() || !bw_site_layout_is_enabled()) {
            return $classes;
        }

        $settings = bw_site_layout_get_settings();

        $classes[] = 'bw-site-layout-active';

        if (!empty($settings['apply_to_main_content'])) {
            $classes[] = 'bw-site-layout-main-enabled';
        }

        if (!empty($settings['apply_to_header'])) {
            $classes[] = 'bw-site-layout-header-enabled';
        }

        if (!empty($settings['apply_to_footer'])) {
            $classes[] = 'bw-site-layout-footer-enabled';
        }

        if (!empty($settings['allow_full_bleed_sections'])) {
            $classes[] = 'bw-site-layout-allow-full-bleed';
        }

        return array_values(array_unique($classes));
    }
}
add_filter('body_class', 'bw_site_layout_add_body_classes');

if (!function_exists('bw_site_layout_should_wrap_main_content')) {
    function bw_site_layout_should_wrap_main_content()
    {
        if (is_admin() || !bw_site_layout_is_enabled() || wp_doing_ajax() || is_feed() || is_embed()) {
            return false;
        }

        $settings = bw_site_layout_get_settings();
        if (empty($settings['apply_to_main_content'])) {
            return false;
        }

        if (function_exists('is_woocommerce') && is_woocommerce()) {
            return false;
        }

        if (!in_the_loop() || !is_main_query()) {
            return false;
        }

        if (is_singular('bw_template')) {
            return false;
        }

        return true;
    }
}

if (!function_exists('bw_site_layout_wrap_the_content')) {
    function bw_site_layout_wrap_the_content($content)
    {
        if (!bw_site_layout_should_wrap_main_content()) {
            return $content;
        }

        if (!is_string($content) || '' === trim($content)) {
            return $content;
        }

        if (false !== strpos($content, 'bw-site-layout-main-shell')) {
            return $content;
        }

        return '<div class="bw-site-layout-main-shell" data-bw-site-layout-main-shell="1">' . $content . '</div>';
    }
}
add_filter('the_content', 'bw_site_layout_wrap_the_content', 20);

if (!function_exists('bw_site_layout_open_woocommerce_shell')) {
    function bw_site_layout_open_woocommerce_shell()
    {
        if (!bw_site_layout_is_enabled()) {
            return;
        }

        $settings = bw_site_layout_get_settings();
        if (empty($settings['apply_to_main_content'])) {
            return;
        }

        echo '<div class="bw-site-layout-main-shell bw-site-layout-main-shell--woocommerce" data-bw-site-layout-main-shell="woocommerce">';
    }
}
add_action('woocommerce_before_main_content', 'bw_site_layout_open_woocommerce_shell', 5);

if (!function_exists('bw_site_layout_close_woocommerce_shell')) {
    function bw_site_layout_close_woocommerce_shell()
    {
        if (!bw_site_layout_is_enabled()) {
            return;
        }

        $settings = bw_site_layout_get_settings();
        if (empty($settings['apply_to_main_content'])) {
            return;
        }

        echo '</div>';
    }
}
add_action('woocommerce_after_main_content', 'bw_site_layout_close_woocommerce_shell', 50);
