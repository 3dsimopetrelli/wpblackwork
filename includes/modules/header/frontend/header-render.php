<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_get_cart_count')) {
    function bw_header_get_cart_count()
    {
        if (!function_exists('WC')) {
            return 0;
        }

        $wc = WC();
        if (!$wc || !isset($wc->cart) || !$wc->cart) {
            return 0;
        }

        return max(0, (int) $wc->cart->get_cart_contents_count());
    }
}

if (!function_exists('bw_header_default_search_icon_svg')) {
    function bw_header_default_search_icon_svg()
    {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="16.65" y1="16.65" x2="21" y2="21"></line></svg>';
    }
}

if (!function_exists('bw_header_default_hamburger_svg')) {
    function bw_header_default_hamburger_svg()
    {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>';
    }
}

if (!function_exists('bw_header_default_cart_svg')) {
    function bw_header_default_cart_svg()
    {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle><path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H7"></path></svg>';
    }
}

if (!function_exists('bw_header_render_search_block')) {
    function bw_header_render_search_block($args = [])
    {
        $widget_id = isset($args['widget_id']) ? sanitize_key($args['widget_id']) : wp_generate_uuid4();
        $label = isset($args['label']) && $args['label'] !== '' ? (string) $args['label'] : __('Search', 'bw');
        $icon_markup = isset($args['icon_markup']) ? (string) $args['icon_markup'] : bw_header_default_search_icon_svg();
        $show_header_text = !empty($args['show_header_text']);
        $header_text = isset($args['header_text']) ? (string) $args['header_text'] : __("Type what you're looking for", 'bw');
        $placeholder = isset($args['placeholder']) ? (string) $args['placeholder'] : __('Type...', 'bw');
        $hint_text = isset($args['hint_text']) ? (string) $args['hint_text'] : __('Hit enter to search or ESC to close', 'bw');

        ob_start();
        ?>
        <div class="elementor-widget-bw-search bw-header-search" data-bw-search-root="1">
            <button class="bw-search-button" type="button" aria-label="<?php esc_attr_e('Open search', 'bw'); ?>">
                <span class="bw-search-button__label"><?php echo esc_html($label); ?></span>
                <span class="bw-search-button__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </button>
            <?php
            $overlay_args = [
                'widget_id' => $widget_id,
                'show_header_text' => $show_header_text,
                'header_text' => $header_text,
                'placeholder' => $placeholder,
                'hint_text' => $hint_text,
            ];
            include BW_MEW_PATH . 'includes/modules/header/templates/parts/search-overlay.php';
            ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}

if (!function_exists('bw_header_render_frontend')) {
    function bw_header_render_frontend()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return;
        }

        if (defined('ELEMENTOR_VERSION') && class_exists('\\Elementor\\Plugin')) {
            $elementor = \Elementor\Plugin::$instance;
            if ($elementor && isset($elementor->preview) && $elementor->preview->is_preview_mode()) {
                return;
            }
        }

        $settings = bw_header_get_settings();
        if (empty($settings['enabled'])) {
            return;
        }

        $desktop_menu_id = !empty($settings['menus']['desktop_menu_id']) ? absint($settings['menus']['desktop_menu_id']) : 0;
        if ($desktop_menu_id <= 0) {
            return;
        }

        $mobile_menu_id = !empty($settings['menus']['mobile_menu_id']) ? absint($settings['menus']['mobile_menu_id']) : $desktop_menu_id;
        if ($mobile_menu_id <= 0) {
            $mobile_menu_id = $desktop_menu_id;
        }

        $desktop_menu_html = bw_header_render_menu($desktop_menu_id, 'bw-navigation__list bw-navigation__list--desktop');
        $mobile_menu_html = bw_header_render_menu($mobile_menu_id, 'bw-navigation__list bw-navigation__list--mobile');

        if ($desktop_menu_html === '') {
            return;
        }

        if ($mobile_menu_html === '') {
            $mobile_menu_html = $desktop_menu_html;
        }

        $search_label = !empty($settings['labels']['search']) ? (string) $settings['labels']['search'] : __('Search', 'bw');
        $account_label = !empty($settings['labels']['account']) ? (string) $settings['labels']['account'] : __('Account', 'bw');
        $cart_label = !empty($settings['labels']['cart']) ? (string) $settings['labels']['cart'] : __('Cart', 'bw');

        $account_link = !empty($settings['links']['account']) ? esc_url($settings['links']['account']) : esc_url(home_url('/my-account/'));
        $cart_link = !empty($settings['links']['cart']) ? esc_url($settings['links']['cart']) : esc_url(home_url('/cart/'));

        $logo_html = bw_header_get_logo_markup(isset($settings['logo_attachment_id']) ? $settings['logo_attachment_id'] : 0, $settings);
        $hamburger_icon = bw_header_get_icon_markup(
            isset($settings['icons']['mobile_hamburger_attachment_id']) ? $settings['icons']['mobile_hamburger_attachment_id'] : 0,
            bw_header_default_hamburger_svg(),
            'bw-navigation__toggle-icon-image'
        );
        $search_icon = bw_header_get_icon_markup(
            isset($settings['icons']['mobile_search_attachment_id']) ? $settings['icons']['mobile_search_attachment_id'] : 0,
            bw_header_default_search_icon_svg(),
            'bw-search-button__icon-image'
        );
        $cart_icon = bw_header_get_icon_markup(
            isset($settings['icons']['mobile_cart_attachment_id']) ? $settings['icons']['mobile_cart_attachment_id'] : 0,
            bw_header_default_cart_svg(),
            'bw-navshop__cart-icon-image'
        );

        $cart_count = bw_header_get_cart_count();
        $cart_count_class = $cart_count > 0 ? 'bw-navshop__cart-count' : 'bw-navshop__cart-count is-empty';

        $header_title = isset($settings['header_title']) ? sanitize_text_field($settings['header_title']) : 'Blackwork Header';
        $smart_scroll_enabled = !empty($settings['features']['smart_scroll']);
        $menu_blur_enabled = !empty($settings['smart_header']['menu_blur_enabled']);
        $header_classes = 'bw-custom-header';
        if ($smart_scroll_enabled) {
            $header_classes .= ' bw-custom-header--smart bw-header-visible';
        }

        $search_desktop_markup = bw_header_render_search_block([
            'widget_id' => 'bw-header-search-desktop',
            'label' => $search_label,
            'icon_markup' => $search_icon,
            'show_header_text' => true,
            'header_text' => __("Type what you're looking for", 'bw'),
            'placeholder' => __('Type...', 'bw'),
            'hint_text' => __('Hit enter to search or ESC to close', 'bw'),
        ]);

        $search_mobile_markup = bw_header_render_search_block([
            'widget_id' => 'bw-header-search-mobile',
            'label' => $search_label,
            'icon_markup' => $search_icon,
            'show_header_text' => true,
            'header_text' => __("Type what you're looking for", 'bw'),
            'placeholder' => __('Type...', 'bw'),
            'hint_text' => __('Hit enter to search or ESC to close', 'bw'),
        ]);

        include BW_MEW_PATH . 'includes/modules/header/templates/header.php';
    }
}
add_action('wp_body_open', 'bw_header_render_frontend', 5);

if (!function_exists('bw_header_disable_theme_header')) {
    function bw_header_disable_theme_header()
    {
        if (is_admin() || !bw_header_is_enabled()) {
            return;
        }

        // Hello Elementor header renderer.
        if (function_exists('hello_elementor_render_header')) {
            remove_action('hello_elementor_header', 'hello_elementor_render_header');
        }
    }
}
add_action('wp', 'bw_header_disable_theme_header', 1);

if (!function_exists('bw_header_theme_header_fallback_css')) {
    function bw_header_theme_header_fallback_css()
    {
        if (is_admin() || !bw_header_is_enabled()) {
            return;
        }
        ?>
        <style id="bw-custom-header-theme-fallback">
            header#site-header,
            #site-header.site-header,
            .site-header.dynamic-header {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'bw_header_theme_header_fallback_css', 99);
