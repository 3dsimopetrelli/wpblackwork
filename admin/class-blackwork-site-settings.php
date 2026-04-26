<?php
/**
 * Blackwork Site Settings Page
 *
 * Pagina unificata sotto Settings con tab per Cart Pop-up e BW Coming Soon
 *
 * @package BW_Elementor_Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_SITE_LAYOUT_OPTION')) {
    define('BW_SITE_LAYOUT_OPTION', 'bw_site_layout_settings_v1');
}

/**
 * Registra la pagina Blackwork Site come voce principale nella sidebar
 */
function bw_site_settings_menu()
{
    // SVG icona cerchio verde pieno
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#80FD03"/></svg>');

    add_menu_page(
        'Blackwork Site',           // Page title
        'Blackwork Site',           // Menu title
        'manage_options',           // Capability
        'blackwork-site-settings',  // Menu slug
        'bw_site_settings_page',    // Callback function
        $icon_svg,                  // Icon (cerchio verde #80FD03)
        30                          // Position (dopo Comments)
    );

    // Keep an explicit first submenu entry so clicking Blackwork Site does not
    // default to the first child module (e.g. BW Templates list).
    add_submenu_page(
        'blackwork-site-settings',
        __('Site Settings', 'bw'),
        __('Site Settings', 'bw'),
        'manage_options',
        'blackwork-site-settings',
        'bw_site_settings_page'
    );
}
add_action('admin_menu', 'bw_site_settings_menu');

/**
 * Register Promotions & Labels dedicated submenu page.
 */
function bw_product_labels_admin_menu()
{
    add_submenu_page(
        'blackwork-site-settings',
        __('Promotions & Labels', 'bw'),
        __('Promotions & Labels', 'bw'),
        'manage_options',
        'bw-product-labels-settings',
        'bw_product_labels_render_admin_page'
    );
}
add_action('admin_menu', 'bw_product_labels_admin_menu', 61);

/**
 * Force Site Settings as first submenu entry for Blackwork top-level menu.
 */
function bw_site_settings_force_default_submenu()
{
    global $submenu;

    if (empty($submenu['blackwork-site-settings']) || !is_array($submenu['blackwork-site-settings'])) {
        return;
    }

    $target_item = null;
    $other_items = [];

    foreach ($submenu['blackwork-site-settings'] as $item) {
        if (isset($item[2]) && 'blackwork-site-settings' === $item[2]) {
            $target_item = $item;
            continue;
        }

        $other_items[] = $item;
    }

    if (null === $target_item) {
        $target_item = [
            __('Site Settings', 'bw'),
            'manage_options',
            'blackwork-site-settings',
            __('Site Settings', 'bw'),
        ];
    }

    $submenu['blackwork-site-settings'] = array_merge([$target_item], $other_items);
}
add_action('admin_menu', 'bw_site_settings_force_default_submenu', 999);

/**
 * Carica lo stile per l'icona del menu admin su tutto wp-admin.
 *
 * The stylesheet is intentionally tiny and scoped to the Blackwork top-level
 * menu selector, so it is safe to load globally to preserve icon styling even
 * when another admin screen is active.
 */
function bw_site_settings_admin_menu_icon_styles($_hook)
{
    $menu_style_path = BW_MEW_PATH . 'admin/css/blackwork-site-menu.css';
    $menu_style_version = file_exists($menu_style_path) ? filemtime($menu_style_path) : '1.0.0';

    wp_enqueue_style(
        'bw-site-settings-admin-menu',
        BW_MEW_URL . 'admin/css/blackwork-site-menu.css',
        [],
        $menu_style_version
    );
}
add_action('admin_enqueue_scripts', 'bw_site_settings_admin_menu_icon_styles');

/**
 * Check whether current admin screen belongs to Blackwork Site panel.
 */
function bw_is_blackwork_site_admin_screen($hook, $page_slug = '')
{
    $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';

    if ('toplevel_page_blackwork-site-settings' === $hook) {
        return true;
    }

    if (0 === strpos($hook, 'blackwork-site-settings_page_')) {
        return true;
    }

    if (0 === strpos($hook, 'blackwork-site_page_')) {
        return true;
    }

    if ('edit.php' === $hook && 'bw_template' === $post_type) {
        return true;
    }

    $allowed_pages = [
        'blackwork-site-settings',
        'bw-product-labels-settings',
        'blackwork-mail-marketing',
        'bw-reviews',
        'bw-reviews-settings',
        'bw-reviews-edit',
    ];

    return !empty($page_slug) && in_array($page_slug, $allowed_pages, true);
}

/**
 * Enqueue shared Blackwork admin UI kit styles.
 */
function bw_admin_enqueue_ui_kit_assets($hook)
{
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (!bw_is_blackwork_site_admin_screen($hook, $current_page)) {
        return;
    }

    $ui_kit_path = BW_MEW_PATH . 'admin/css/bw-admin-ui-kit.css';
    $ui_kit_version = file_exists($ui_kit_path) ? filemtime($ui_kit_path) : '1.0.0';

    wp_enqueue_style(
        'bw-admin-ui-kit',
        BW_MEW_URL . 'admin/css/bw-admin-ui-kit.css',
        [],
        $ui_kit_version
    );
}
add_action('admin_enqueue_scripts', 'bw_admin_enqueue_ui_kit_assets', 12);

/**
 * Carica gli assets per la pagina admin
 */
function bw_site_settings_admin_assets($hook)
{
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $current_tab_raw = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';

    $is_site_settings_page = ('blackwork-site-settings' === $current_page || 'toplevel_page_blackwork-site-settings' === $hook);
    $is_product_labels_page = (
        'bw-product-labels-settings' === $current_page
        || 'blackwork-site-settings_page_bw-product-labels-settings' === $hook
    );
    $is_mail_marketing_page = (
        'blackwork-mail-marketing' === $current_page
        || 'blackwork-site-settings_page_blackwork-mail-marketing' === $hook
    );

    // Site Settings asset matrix is restricted to Site Settings and Mail Marketing pages.
    if (!$is_site_settings_page && !$is_mail_marketing_page && !$is_product_labels_page) {
        return;
    }

    $site_settings_tabs = [
        'info',
        'layout',
        'cart-popup',
        'bw-coming-soon',
        'account-page',
        'my-account-page',
        'checkout',
        'redirect',
        'import-product',
        'loading',
    ];
    $mail_marketing_tabs = ['general', 'checkout', 'subscription'];

    $current_site_settings_tab = in_array($current_tab_raw, $site_settings_tabs, true) ? $current_tab_raw : 'info';
    $current_mail_marketing_tab = in_array($current_tab_raw, $mail_marketing_tabs, true) ? $current_tab_raw : 'general';

    // Base Site Settings admin CSS (used by Site Settings and Mail Marketing controls).
    $site_settings_css_path = BW_MEW_PATH . 'admin/css/blackwork-site-settings.css';
    $site_settings_css_version = file_exists($site_settings_css_path) ? filemtime($site_settings_css_path) : '1.0.0';

    wp_enqueue_style(
        'bw-site-settings-admin',
        BW_MEW_URL . 'admin/css/blackwork-site-settings.css',
        [],
        $site_settings_css_version
    );

    // Enqueue only where media upload/select controls are present.
    if ($is_site_settings_page && in_array($current_site_settings_tab, ['account-page', 'checkout'], true)) {
        wp_enqueue_media();
    }

    if ($is_product_labels_page) {
        $select2_css_path = BW_MEW_PATH . 'assets/lib/select2/css/select2.css';
        $select2_js_path = BW_MEW_PATH . 'assets/lib/select2/js/select2.full.min.js';
        $product_labels_admin_js_path = BW_MEW_PATH . 'admin/js/bw-product-labels-admin.js';

        wp_enqueue_style(
            'bw-select2-admin',
            BW_MEW_URL . 'assets/lib/select2/css/select2.css',
            [],
            file_exists($select2_css_path) ? filemtime($select2_css_path) : '4.0.3'
        );

        wp_enqueue_script(
            'bw-select2-admin',
            BW_MEW_URL . 'assets/lib/select2/js/select2.full.min.js',
            ['jquery'],
            file_exists($select2_js_path) ? filemtime($select2_js_path) : '4.0.3',
            true
        );

        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script(
            'bw-product-labels-admin',
            BW_MEW_URL . 'admin/js/bw-product-labels-admin.js',
            ['jquery', 'bw-select2-admin', 'jquery-ui-sortable'],
            file_exists($product_labels_admin_js_path) ? filemtime($product_labels_admin_js_path) : '1.0.0',
            true
        );

        wp_localize_script(
            'bw-product-labels-admin',
            'bwProductLabelsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_search_products'),
                'searchPlaceholder' => esc_html__('Search products...', 'bw'),
                'manualOrderEmpty' => esc_html__('Selected products will appear here.', 'bw'),
            ]
        );
    }

    if ($is_site_settings_page && 'redirect' === $current_site_settings_tab) {
        $redirects_script_path = BW_MEW_PATH . 'admin/js/bw-redirects.js';
        $redirects_version = file_exists($redirects_script_path) ? filemtime($redirects_script_path) : '1.0.0';

        wp_enqueue_script(
            'bw-redirects-admin',
            BW_MEW_URL . 'admin/js/bw-redirects.js',
            ['jquery'],
            $redirects_version,
            true
        );
    }

    // Enqueue Brevo test script only on Mail Marketing > General.
    if ($is_mail_marketing_page && 'general' === $current_mail_marketing_tab) {
        $subscribe_script_path = BW_MEW_PATH . 'admin/js/bw-checkout-subscribe.js';
        $subscribe_version = file_exists($subscribe_script_path) ? filemtime($subscribe_script_path) : '1.0.0';

        wp_enqueue_script(
            'bw-checkout-subscribe-admin',
            BW_MEW_URL . 'admin/js/bw-checkout-subscribe.js',
            ['jquery'],
            $subscribe_version,
            true
        );

        wp_localize_script(
            'bw-checkout-subscribe-admin',
            'bwCheckoutSubscribe',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_checkout_subscribe_test'),
                'errorText' => esc_html__('Connection failed. Please check the API key and network.', 'bw'),
                'testingText' => esc_html__('Testing connection...', 'bw'),
            ]
        );
    }

    if ($is_site_settings_page && 'checkout' === $current_site_settings_tab) {
        // Checkout tab uses media selector + WP color picker + payment diagnostics scripts.
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        $google_pay_admin_script_path = BW_MEW_PATH . 'admin/js/bw-google-pay-admin.js';
        $google_pay_admin_version = file_exists($google_pay_admin_script_path) ? filemtime($google_pay_admin_script_path) : '1.0.0';

        wp_enqueue_script(
            'bw-google-pay-admin',
            BW_MEW_URL . 'admin/js/bw-google-pay-admin.js',
            ['jquery'],
            $google_pay_admin_version,
            true
        );

        wp_localize_script(
            'bw-google-pay-admin',
            'bwGooglePayAdmin',
            [
                'nonce' => wp_create_nonce('bw_google_pay_test_connection'),
                'errorText' => esc_html__('Connection test failed. Please verify your Stripe keys.', 'bw'),
                'testingText' => esc_html__('Testing connection…', 'bw'),
            ]
        );

        $klarna_admin_script_path = BW_MEW_PATH . 'admin/js/bw-klarna-admin.js';
        $klarna_admin_version = file_exists($klarna_admin_script_path) ? filemtime($klarna_admin_script_path) : '1.0.0';

        wp_enqueue_script(
            'bw-klarna-admin',
            BW_MEW_URL . 'admin/js/bw-klarna-admin.js',
            ['jquery'],
            $klarna_admin_version,
            true
        );

        wp_localize_script(
            'bw-klarna-admin',
            'bwKlarnaAdmin',
            [
                'nonce' => wp_create_nonce('bw_klarna_test_connection'),
                'errorText' => esc_html__('Connection test failed. Please verify your Stripe keys.', 'bw'),
                'testingText' => esc_html__('Testing connection…', 'bw'),
            ]
        );

        $apple_pay_admin_script_path = BW_MEW_PATH . 'admin/js/bw-apple-pay-admin.js';
        $apple_pay_admin_version = file_exists($apple_pay_admin_script_path) ? filemtime($apple_pay_admin_script_path) : '1.0.0';

        wp_enqueue_script(
            'bw-apple-pay-admin',
            BW_MEW_URL . 'admin/js/bw-apple-pay-admin.js',
            ['jquery'],
            $apple_pay_admin_version,
            true
        );

        wp_localize_script(
            'bw-apple-pay-admin',
            'bwApplePayAdmin',
            [
                'nonce' => wp_create_nonce('bw_apple_pay_test_connection'),
                'errorText' => esc_html__('Connection test failed. Please verify your Stripe keys.', 'bw'),
                'testingText' => esc_html__('Testing connection…', 'bw'),
                'testingDomainText' => esc_html__('Checking domain…', 'bw'),
                'domainOkText' => esc_html__('Domain verified in Stripe.', 'bw'),
                'domainErrorText' => esc_html__('Domain verification failed. Please check Stripe domain settings.', 'bw'),
            ]
        );
    }

    if ($is_site_settings_page && 'cart-popup' === $current_site_settings_tab) {
        // Cart Pop-up tab border controls.
        $border_toggle_path = BW_MEW_PATH . 'assets/js/bw-border-toggle-admin.js';
        $border_toggle_version = file_exists($border_toggle_path) ? filemtime($border_toggle_path) : '1.0.0';

        wp_enqueue_script(
            'bw-border-toggle-admin',
            BW_MEW_URL . 'assets/js/bw-border-toggle-admin.js',
            ['jquery'],
            $border_toggle_version,
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_site_settings_admin_assets');

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

/**
 * AJAX handler to test Stripe connection for Google Pay settings.
 */
function bw_google_pay_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_google_pay_test_connection', 'nonce');

    $mode = isset($_POST['mode']) && 'test' === sanitize_key(wp_unslash($_POST['mode'])) ? 'test' : 'live';
    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    $publishable_key = isset($_POST['publishable_key']) ? sanitize_text_field(wp_unslash($_POST['publishable_key'])) : '';

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Secret key is required.', 'bw')]);
    }

    $expected_secret_prefix = 'test' === $mode ? 'sk_test_' : 'sk_live_';
    $expected_publishable_prefix = 'test' === $mode ? 'pk_test_' : 'pk_live_';

    if (0 !== strpos($secret_key, $expected_secret_prefix)) {
        wp_send_json_error([
            'message' => 'test' === $mode
                ? __('The selected Test Mode requires a key starting with sk_test_.', 'bw')
                : __('The selected Live Mode requires a key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 !== strpos($publishable_key, $expected_publishable_prefix)) {
        wp_send_json_error([
            'message' => 'test' === $mode
                ? __('The selected Test Mode requires a publishable key starting with pk_test_.', 'bw')
                : __('The selected Live Mode requires a publishable key starting with pk_live_.', 'bw'),
        ]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/account',
        [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request. Please verify your keys.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['id'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response. Please try again.', 'bw')]);
    }

    $api_mode = !empty($payload['livemode']) ? 'live' : 'test';
    // Some Stripe account payloads can report an unexpected livemode value even when
    // the API key pair is correct. Prefix validation above already guarantees key mode.
    $effective_mode = $mode;
    if ($api_mode !== $mode) {
        $effective_mode = $mode;
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: 1: mode label, 2: Stripe account id */
            __('Connected successfully (%1$s mode) - Account: %2$s', 'bw'),
            'test' === $effective_mode ? __('Test', 'bw') : __('Live', 'bw'),
            sanitize_text_field((string) $payload['id'])
        ),
        'mode' => $effective_mode,
    ]);
}
add_action('wp_ajax_bw_google_pay_test_connection', 'bw_google_pay_test_connection_ajax_handler');

/**
 * AJAX handler to test Google Maps + Places API connectivity for checkout autocomplete.
 */
function bw_google_maps_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_google_maps_test_connection', 'nonce');

    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

    if ('' === $api_key) {
        wp_send_json_error(['message' => __('Google Maps API key is required.', 'bw')]);
    }

    if (0 !== strpos($api_key, 'AIza')) {
        wp_send_json_error([
            'message' => __('The API key format looks invalid. It should start with "AIza".', 'bw'),
        ]);
    }

    $site_referer = home_url('/');

    $maps_js_url = add_query_arg(
        [
            'key' => $api_key,
            'libraries' => 'places',
            'v' => 'weekly',
        ],
        'https://maps.googleapis.com/maps/api/js'
    );

    $maps_js_response = wp_remote_get(
        $maps_js_url,
        [
            'timeout' => 20,
            'headers' => [
                'Referer' => $site_referer,
            ],
        ]
    );

    if (is_wp_error($maps_js_response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Google Maps JavaScript API: %s', 'bw'),
                $maps_js_response->get_error_message()
            ),
        ]);
    }

    $maps_status_code = (int) wp_remote_retrieve_response_code($maps_js_response);
    $maps_body = (string) wp_remote_retrieve_body($maps_js_response);

    if ($maps_status_code < 200 || $maps_status_code >= 300) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %d: HTTP status code */
                __('Google Maps JavaScript API returned HTTP %d.', 'bw'),
                $maps_status_code
            ),
        ]);
    }

    $known_maps_errors = [
        'RefererNotAllowedMapError' => __('Referrer is not allowed. Add your domain to HTTP referrer restrictions.', 'bw'),
        'InvalidKeyMapError' => __('Invalid API key.', 'bw'),
        'ApiNotActivatedMapError' => __('Maps JavaScript API or Places API is not enabled in Google Cloud.', 'bw'),
        'ApiProjectMapError' => __('The key is linked to a project that cannot use this API.', 'bw'),
        'BillingNotEnabledMapError' => __('Billing is not enabled for this Google Cloud project.', 'bw'),
        'ExpiredKeyMapError' => __('This API key is expired.', 'bw'),
    ];

    foreach ($known_maps_errors as $error_code => $error_message) {
        if (false !== strpos($maps_body, $error_code)) {
            wp_send_json_error([
                'message' => $error_message,
                'details' => sprintf(
                    /* translators: %s: Google Maps error code */
                    __('Google error code: %s', 'bw'),
                    $error_code
                ),
            ]);
        }
    }

    wp_send_json_success([
        'message' => __('Google Maps key is valid for Maps JavaScript API and referrer restrictions look correct.', 'bw'),
        'details' => sprintf(
            /* translators: %s: tested site URL */
            __('Tested referrer: %s. Note: server-side Places Web Service is intentionally not checked when key uses HTTP referrer restrictions.', 'bw'),
            esc_url_raw($site_referer)
        ),
    ]);
}
add_action('wp_ajax_bw_google_maps_test_connection', 'bw_google_maps_test_connection_ajax_handler');

/**
 * AJAX handler to test Stripe connection for Klarna settings (live mode).
 */
function bw_klarna_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_klarna_test_connection', 'nonce');

    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    $publishable_key = isset($_POST['publishable_key']) ? sanitize_text_field(wp_unslash($_POST['publishable_key'])) : '';

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Secret key is required.', 'bw')]);
    }

    if (0 !== strpos($secret_key, 'sk_live_')) {
        wp_send_json_error([
            'message' => __('Klarna live mode requires a key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 !== strpos($publishable_key, 'pk_live_')) {
        wp_send_json_error([
            'message' => __('Klarna live mode requires a publishable key starting with pk_live_.', 'bw'),
        ]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/account',
        [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request. Please verify your keys.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['id'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response. Please try again.', 'bw')]);
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %s: Stripe account id */
            __('Connected successfully (Live mode) - Account: %s', 'bw'),
            sanitize_text_field((string) $payload['id'])
        ),
        'mode' => 'live',
    ]);
}
add_action('wp_ajax_bw_klarna_test_connection', 'bw_klarna_test_connection_ajax_handler');

/**
 * AJAX handler to test Stripe connection for Apple Pay settings (live mode only).
 */
function bw_apple_pay_test_connection_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_apple_pay_test_connection', 'nonce');

    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    $publishable_key = isset($_POST['publishable_key']) ? sanitize_text_field(wp_unslash($_POST['publishable_key'])) : '';

    if ('' === $secret_key) {
        $secret_key = (string) get_option('bw_google_pay_secret_key', '');
    }
    if ('' === $publishable_key) {
        $publishable_key = (string) get_option('bw_google_pay_publishable_key', '');
    }

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Live secret key is required. Add Apple Pay key or configure global live keys.', 'bw')]);
    }

    if (0 === strpos($secret_key, 'sk_test_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode does not accept test keys. Use a secret key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 === strpos($publishable_key, 'pk_test_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode does not accept test publishable keys. Use a key starting with pk_live_.', 'bw'),
        ]);
    }

    if (0 !== strpos($secret_key, 'sk_live_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode requires a key starting with sk_live_.', 'bw'),
        ]);
    }

    if ('' !== $publishable_key && 0 !== strpos($publishable_key, 'pk_live_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode requires a publishable key starting with pk_live_.', 'bw'),
        ]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/account',
        [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request. Please verify your keys.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['id'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response. Please try again.', 'bw')]);
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %s: Stripe account id */
            __('Connected successfully (Live mode) - Account: %s', 'bw'),
            sanitize_text_field((string) $payload['id'])
        ),
        'mode' => 'live',
    ]);
}
add_action('wp_ajax_bw_apple_pay_test_connection', 'bw_apple_pay_test_connection_ajax_handler');

/**
 * AJAX handler to verify Apple Pay domain status in Stripe (live mode only).
 */
function bw_apple_pay_verify_domain_ajax_handler()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'bw')]);
    }

    check_ajax_referer('bw_apple_pay_test_connection', 'nonce');

    $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
    if ('' === $secret_key) {
        $secret_key = (string) get_option('bw_apple_pay_secret_key', '');
    }
    if ('' === $secret_key) {
        $secret_key = (string) get_option('bw_google_pay_secret_key', '');
    }

    if ('' === $secret_key) {
        wp_send_json_error(['message' => __('Live secret key is required before checking domain verification.', 'bw')]);
    }

    if (0 === strpos($secret_key, 'sk_test_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode does not accept test keys. Use a secret key starting with sk_live_.', 'bw'),
        ]);
    }

    if (0 !== strpos($secret_key, 'sk_live_')) {
        wp_send_json_error([
            'message' => __('Apple Pay LIVE mode requires a secret key starting with sk_live_.', 'bw'),
        ]);
    }

    $site_domain = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $site_domain = is_string($site_domain) ? strtolower(trim($site_domain)) : '';
    if ('' === $site_domain) {
        wp_send_json_error(['message' => __('Unable to detect your site domain.', 'bw')]);
    }

    $response = wp_remote_get(
        'https://api.stripe.com/v1/payment_method_domains?limit=100',
        [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
        ]
    );

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: WP error message */
                __('Unable to reach Stripe API: %s', 'bw'),
                $response->get_error_message()
            ),
        ]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
        $stripe_error = '';
        if (is_array($payload) && isset($payload['error']['message'])) {
            $stripe_error = sanitize_text_field((string) $payload['error']['message']);
        }

        wp_send_json_error([
            'message' => $stripe_error
                ? sprintf(__('Stripe API error: %s', 'bw'), $stripe_error)
                : __('Stripe API rejected the request while checking domain verification.', 'bw'),
        ]);
    }

    if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
        wp_send_json_error(['message' => __('Unexpected Stripe response while checking domain verification.', 'bw')]);
    }

    $domain_variants = array_unique(array_filter([
        $site_domain,
        preg_replace('/^www\./', '', $site_domain),
        'www.' . preg_replace('/^www\./', '', $site_domain),
    ]));

    $matched = null;
    foreach ($payload['data'] as $item) {
        if (!is_array($item) || empty($item['domain_name'])) {
            continue;
        }
        $domain_name = strtolower((string) $item['domain_name']);
        if (in_array($domain_name, $domain_variants, true)) {
            $matched = $item;
            break;
        }
    }

    if (null === $matched) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: domain name */
                __('Domain not found in Stripe Payment Method Domains: %s. Add and verify it in Stripe Dashboard > Settings > Payment method domains.', 'bw'),
                $site_domain
            ),
        ]);
    }

    $is_enabled = isset($matched['enabled']) ? (bool) $matched['enabled'] : false;
    $matched_domain = sanitize_text_field((string) $matched['domain_name']);

    if (!$is_enabled) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: domain name */
                __('Domain found but not enabled: %s. Enable it in Stripe Payment method domains.', 'bw'),
                $matched_domain
            ),
        ]);
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %s: domain name */
            __('Domain verified and enabled in Stripe: %s', 'bw'),
            $matched_domain
        ),
        'domain' => $matched_domain,
        'enabled' => true,
    ]);
}
add_action('wp_ajax_bw_apple_pay_verify_domain', 'bw_apple_pay_verify_domain_ajax_handler');

/**
 * Renderizza la pagina delle impostazioni con tab
 */
function bw_site_settings_page()
{
    // Verifica permessi
    if (!current_user_can('manage_options')) {
        return;
    }

    $requested_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'info';

    if ('product-labels' === $requested_tab) {
        wp_safe_redirect(admin_url('admin.php?page=bw-product-labels-settings'));
        exit;
    }

    // Determina quale tab è attivo
    $allowed_tabs = ['info', 'layout', 'cart-popup', 'bw-coming-soon', 'account-page', 'my-account-page', 'checkout', 'redirect', 'import-product', 'loading'];
    $active_tab = $requested_tab;
    if (!in_array($active_tab, $allowed_tabs, true)) {
        $active_tab = 'info';
    }

    $save_button_map = [
        'layout' => 'bw_layout_settings_submit',
        'cart-popup' => 'bw_cart_popup_submit',
        'bw-coming-soon' => 'bw_coming_soon_submit',
        'account-page' => 'bw_account_page_submit',
        'my-account-page' => 'bw_myaccount_content_submit',
        'checkout' => 'bw_checkout_settings_submit',
        'redirect' => 'bw_redirects_submit',
        'loading' => 'bw_loading_settings_submit',
    ];
    $active_submit_name = isset($save_button_map[$active_tab]) ? $save_button_map[$active_tab] : '';
    ?>
    <div class="wrap bw-admin-root bw-admin-page bw-admin-page-site-settings">
        <div class="bw-admin-header">
            <h1 class="bw-admin-title"><?php esc_html_e('Site Settings', 'bw'); ?></h1>
            <p class="bw-admin-subtitle"><?php esc_html_e('Manage core Blackwork site configuration from a unified admin panel.', 'bw'); ?></p>
        </div>

        <div class="bw-admin-action-bar">
            <div class="bw-admin-action-meta">
                <?php esc_html_e('Select a panel tab, then save the current section changes.', 'bw'); ?>
            </div>
            <div class="bw-admin-action-buttons">
                <?php if (!empty($active_submit_name)) : ?>
                    <button type="button" class="button button-primary" id="bw-site-settings-save-proxy" data-submit-name="<?php echo esc_attr($active_submit_name); ?>">
                        <?php esc_html_e('Save Settings', 'bw'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <section class="bw-admin-card bw-admin-card-site-settings">
            <h2 class="bw-admin-card-title"><?php esc_html_e('Panels', 'bw'); ?></h2>
            <p class="bw-admin-card-helper"><?php esc_html_e('Switch between configuration domains without leaving Site Settings.', 'bw'); ?></p>

            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper bw-admin-tabs">
                <a href="?page=blackwork-site-settings&tab=info"
                    class="nav-tab <?php echo $active_tab === 'info' ? 'nav-tab-active' : ''; ?>">
                    Info
                </a>
                <a href="?page=blackwork-site-settings&tab=layout"
                    class="nav-tab <?php echo $active_tab === 'layout' ? 'nav-tab-active' : ''; ?>">
                    Layout
                </a>
                <a href="?page=blackwork-site-settings&tab=cart-popup"
                    class="nav-tab <?php echo $active_tab === 'cart-popup' ? 'nav-tab-active' : ''; ?>">
                    Cart Pop-up
                </a>
                <a href="?page=blackwork-site-settings&tab=bw-coming-soon"
                    class="nav-tab <?php echo $active_tab === 'bw-coming-soon' ? 'nav-tab-active' : ''; ?>">
                    BW Coming Soon
                </a>
                <a href="?page=blackwork-site-settings&tab=account-page"
                    class="nav-tab <?php echo $active_tab === 'account-page' ? 'nav-tab-active' : ''; ?>">
                    Login Page
                </a>
                <a href="?page=blackwork-site-settings&tab=my-account-page"
                    class="nav-tab <?php echo $active_tab === 'my-account-page' ? 'nav-tab-active' : ''; ?>">
                    My Account Page
                </a>
                <a href="?page=blackwork-site-settings&tab=checkout"
                    class="nav-tab <?php echo $active_tab === 'checkout' ? 'nav-tab-active' : ''; ?>">
                    Checkout
                </a>
                <a href="?page=blackwork-site-settings&tab=redirect"
                    class="nav-tab <?php echo $active_tab === 'redirect' ? 'nav-tab-active' : ''; ?>">
                    Redirect
                </a>
                <a href="?page=blackwork-site-settings&tab=import-product"
                    class="nav-tab <?php echo $active_tab === 'import-product' ? 'nav-tab-active' : ''; ?>">
                    Product Import / Export
                </a>
                <a href="?page=blackwork-site-settings&tab=loading"
                    class="nav-tab <?php echo $active_tab === 'loading' ? 'nav-tab-active' : ''; ?>">
                    Loading
                </a>
            </nav>

            <!-- Tab Content -->
                <div class="tab-content bw-admin-site-settings-content">
                    <?php
                    // Renderizza il contenuto del tab attivo
                if ($active_tab === 'info') {
                    bw_site_render_info_tab();
                } elseif ($active_tab === 'layout') {
                    bw_site_render_layout_tab();
                } elseif ($active_tab === 'cart-popup') {
                    bw_site_render_cart_popup_tab();
                } elseif ($active_tab === 'bw-coming-soon') {
                    bw_site_render_coming_soon_tab();
                } elseif ($active_tab === 'account-page') {
                    bw_site_render_account_page_tab();
                } elseif ($active_tab === 'my-account-page') {
                    bw_site_render_my_account_front_tab();
                } elseif ($active_tab === 'checkout') {
                    bw_site_render_checkout_tab();
                } elseif ($active_tab === 'redirect') {
                    bw_site_render_redirect_tab();
                } elseif ($active_tab === 'import-product') {
                    bw_site_render_import_product_tab();
                } elseif ($active_tab === 'loading') {
                    bw_site_render_loading_tab();
                }
                ?>
            </div>
        </section>
    </div>
    <?php if (!empty($active_submit_name)) : ?>
    <script>
    (function () {
        var proxyButton = document.getElementById('bw-site-settings-save-proxy');
        if (!proxyButton) {
            return;
        }

        proxyButton.addEventListener('click', function () {
            var submitName = proxyButton.getAttribute('data-submit-name');
            var contentRoot = document.querySelector('.bw-admin-site-settings-content');
            if (!contentRoot) {
                return;
            }

            var targetButton = null;
            if (submitName) {
                targetButton = contentRoot.querySelector('[type="submit"][name="' + submitName + '"]');
            }

            if (!targetButton) {
                targetButton = contentRoot.querySelector('button[type="submit"], input[type="submit"]');
            }

            if (targetButton) {
                targetButton.click();
            }
        });
    })();
    </script>
    <?php endif; ?>
    <?php
}

/**
 * Render info tab with reusable frontend utility classes.
 */
function bw_site_render_info_tab()
{
    $hover_class = 'bw-hover-underline-ltr';
    $breakout_class = 'bw-layout-breakout';
    $full_bleed_class = 'bw-layout-full-bleed';
    $responsive_full_bleed_class = 'bw-layout-full-bleed-responsive';
    $strong_radius_class = 'bw-strong-border-radius';
    ?>
    <section class="bw-admin-card">
        <h2 class="bw-admin-card-title"><?php esc_html_e('Utility CSS Classes', 'bw'); ?></h2>
        <p class="bw-admin-card-helper"><?php esc_html_e('Reusable frontend utility classes for Elementor and other Blackwork surfaces.', 'bw'); ?></p>

        <table class="form-table bw-admin-table" role="presentation">
            <tr>
                <th scope="row"><label for="bw-site-info-hover-class"><?php esc_html_e('Hover underline class', 'bw'); ?></label></th>
                <td>
                    <div class="bw-site-info-copy-row">
                        <input type="text" id="bw-site-info-hover-class" class="regular-text code" readonly value="<?php echo esc_attr($hover_class); ?>" />
                        <button type="button" class="button bw-site-info-copy-button" data-copy-target="bw-site-info-hover-class"><?php esc_html_e('Copy class', 'bw'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Use this class to animate an underline from left to right on hover.', 'bw'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="bw-site-info-breakout-class"><?php esc_html_e('Layout breakout class', 'bw'); ?></label></th>
                <td>
                    <div class="bw-site-info-copy-row">
                        <input type="text" id="bw-site-info-breakout-class" class="regular-text code" readonly value="<?php echo esc_attr($breakout_class); ?>" />
                        <button type="button" class="button bw-site-info-copy-button" data-copy-target="bw-site-info-breakout-class"><?php esc_html_e('Copy class', 'bw'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Apply this to an Elementor Container or Section to let sliders and visual bands extend beyond the global layout width lock while staying centered. Requires Layout > Allow Full-Bleed Sections.', 'bw'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="bw-site-info-full-bleed-class"><?php esc_html_e('Full-bleed class', 'bw'); ?></label></th>
                <td>
                    <div class="bw-site-info-copy-row">
                        <input type="text" id="bw-site-info-full-bleed-class" class="regular-text code" readonly value="<?php echo esc_attr($full_bleed_class); ?>" />
                        <button type="button" class="button bw-site-info-copy-button" data-copy-target="bw-site-info-full-bleed-class"><?php esc_html_e('Copy class', 'bw'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Apply this to an Elementor Container or Section to span the full viewport width and ignore the global layout shell. Best for hero bands and edge-to-edge visual strips.', 'bw'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="bw-site-info-responsive-full-bleed-class"><?php esc_html_e('Responsive full-bleed class', 'bw'); ?></label></th>
                <td>
                    <div class="bw-site-info-copy-row">
                        <input type="text" id="bw-site-info-responsive-full-bleed-class" class="regular-text code" readonly value="<?php echo esc_attr($responsive_full_bleed_class); ?>" />
                        <button type="button" class="button bw-site-info-copy-button" data-copy-target="bw-site-info-responsive-full-bleed-class"><?php esc_html_e('Copy class', 'bw'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Apply this to an Elementor Container or Section to go full-bleed only on tablet and mobile. Desktop keeps the normal centered shell.', 'bw'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="bw-site-info-strong-radius-class"><?php esc_html_e('Strong Border Radius', 'bw'); ?></label></th>
                <td>
                    <div class="bw-site-info-copy-row">
                        <input type="text" id="bw-site-info-strong-radius-class" class="regular-text code" readonly value="<?php echo esc_attr($strong_radius_class); ?>" />
                        <button type="button" class="button bw-site-info-copy-button" data-copy-target="bw-site-info-strong-radius-class"><?php esc_html_e('Copy class', 'bw'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Apply this to an Elementor Container or Section to clip the whole element with a strong 15px border radius, including cover background images and overlays.', 'bw'); ?></p>
                </td>
            </tr>
        </table>
    </section>

    <section class="bw-admin-card">
        <h2 class="bw-admin-card-title"><?php esc_html_e('How to Use Them', 'bw'); ?></h2>
        <p class="bw-admin-card-helper"><?php esc_html_e('For layout classes, use the Elementor Container or Section wrapper instead of the inner widget whenever possible.', 'bw'); ?></p>

        <ol class="bw-site-info-steps">
            <li><?php esc_html_e('Open the Elementor element you want to affect.', 'bw'); ?></li>
            <li><?php esc_html_e('Go to Advanced -> CSS Classes.', 'bw'); ?></li>
            <li><?php echo esc_html(sprintf(__('Paste %s for hover underline, use %s / %s on the Container or Section that should extend past the layout shell, use %s when only tablet/mobile should go full-bleed, or use %s when a full container with background image needs rounded corners.', 'bw'), $hover_class, $breakout_class, $full_bleed_class, $responsive_full_bleed_class, $strong_radius_class)); ?></li>
            <li><?php esc_html_e('Use breakout for controlled extra width, full-bleed for true edge-to-edge sections, responsive full-bleed when only smaller screens should break out, and Strong Border Radius when the whole Elementor wrapper should clip its background.', 'bw'); ?></li>
        </ol>
    </section>

    <script>
    (function () {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.bw-site-info-copy-button');
            var target;
            var originalText;

            if (!button) {
                return;
            }

            target = document.getElementById(button.getAttribute('data-copy-target'));
            if (!target) {
                return;
            }

            originalText = button.textContent;
            target.focus();
            target.select();
            target.setSelectionRange(0, target.value.length);

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(target.value).then(function () {
                    button.textContent = <?php echo wp_json_encode(__('Copied', 'bw')); ?>;
                    window.setTimeout(function () {
                        button.textContent = originalText;
                    }, 1400);
                }).catch(function () {
                    document.execCommand('copy');
                });
                return;
            }

            document.execCommand('copy');
        });
    })();
    </script>
    <?php
}

/**
 * Render layout tab for the global content width system.
 */
function bw_site_render_layout_tab()
{
    $settings = bw_site_layout_get_settings();
    $saved = false;

    if (isset($_POST['bw_layout_settings_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_layout_settings_save', 'bw_layout_settings_nonce');

        $raw_settings = isset($_POST['bw_site_layout']) && is_array($_POST['bw_site_layout'])
            ? wp_unslash($_POST['bw_site_layout'])
            : [];

        $settings = bw_site_layout_sanitize_settings($raw_settings);
        update_option(BW_SITE_LAYOUT_OPTION, $settings);
        $saved = true;
    }
    ?>
    <?php if ($saved) : ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php esc_html_e('Layout settings saved successfully.', 'bw'); ?></strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_layout_settings_save', 'bw_layout_settings_nonce'); ?>

        <section class="bw-admin-card">
            <h2 class="bw-admin-card-title"><?php esc_html_e('Global Layout Width System', 'bw'); ?></h2>
            <p class="bw-admin-card-helper"><?php esc_html_e('Create a reusable centered shell with a configurable max content width and responsive horizontal padding.', 'bw'); ?></p>

            <table class="form-table bw-admin-table" role="presentation">
                <tr>
                    <th scope="row"><label for="bw-site-layout-enabled"><?php esc_html_e('Enable Global Width System', 'bw'); ?></label></th>
                    <td>
                        <label for="bw-site-layout-enabled">
                            <input type="checkbox" id="bw-site-layout-enabled" name="bw_site_layout[enabled]" value="1" <?php checked(1, (int) $settings['enabled']); ?> />
                            <?php esc_html_e('Lock inner content width on wide screens while keeping the viewport full width.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-max-width"><?php esc_html_e('Max Content Width', 'bw'); ?></label></th>
                    <td>
                        <input type="number" id="bw-site-layout-max-width" name="bw_site_layout[max_content_width]" value="<?php echo esc_attr((string) $settings['max_content_width']); ?>" min="960" max="2400" step="1" class="small-text" />
                        <span class="description"><?php esc_html_e('px. Example values: 1400, 1500, 1600.', 'bw'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-desktop-padding"><?php esc_html_e('Desktop Horizontal Padding', 'bw'); ?></label></th>
                    <td>
                        <input type="number" id="bw-site-layout-desktop-padding" name="bw_site_layout[desktop_padding]" value="<?php echo esc_attr((string) $settings['desktop_padding']); ?>" min="0" max="200" step="1" class="small-text" />
                        <span class="description"><?php esc_html_e('px. Inner shell side padding on desktop.', 'bw'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-tablet-padding"><?php esc_html_e('Tablet Horizontal Padding', 'bw'); ?></label></th>
                    <td>
                        <input type="number" id="bw-site-layout-tablet-padding" name="bw_site_layout[tablet_padding]" value="<?php echo esc_attr((string) $settings['tablet_padding']); ?>" min="0" max="160" step="1" class="small-text" />
                        <span class="description"><?php esc_html_e('px. Applied below 1024px.', 'bw'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-mobile-padding"><?php esc_html_e('Mobile Horizontal Padding', 'bw'); ?></label></th>
                    <td>
                        <input type="number" id="bw-site-layout-mobile-padding" name="bw_site_layout[mobile_padding]" value="<?php echo esc_attr((string) $settings['mobile_padding']); ?>" min="0" max="120" step="1" class="small-text" />
                        <span class="description"><?php esc_html_e('px. Applied below 767px.', 'bw'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-apply-main"><?php esc_html_e('Apply to Main Content', 'bw'); ?></label></th>
                    <td>
                        <label for="bw-site-layout-apply-main">
                            <input type="checkbox" id="bw-site-layout-apply-main" name="bw_site_layout[apply_to_main_content]" value="1" <?php checked(1, (int) $settings['apply_to_main_content']); ?> />
                            <?php esc_html_e('Wrap singular content and WooCommerce main content in the global shell.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-apply-header"><?php esc_html_e('Apply to Header', 'bw'); ?></label></th>
                    <td>
                        <label for="bw-site-layout-apply-header">
                            <input type="checkbox" id="bw-site-layout-apply-header" name="bw_site_layout[apply_to_header]" value="1" <?php checked(1, (int) $settings['apply_to_header']); ?> />
                            <?php esc_html_e('Center and constrain the custom Blackwork header inner content.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-apply-footer"><?php esc_html_e('Apply to Footer', 'bw'); ?></label></th>
                    <td>
                        <label for="bw-site-layout-apply-footer">
                            <input type="checkbox" id="bw-site-layout-apply-footer" name="bw_site_layout[apply_to_footer]" value="1" <?php checked(1, (int) $settings['apply_to_footer']); ?> />
                            <?php esc_html_e('Wrap the Blackwork Theme Builder Lite footer output in the global shell.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-site-layout-full-bleed"><?php esc_html_e('Allow Full-Bleed Sections', 'bw'); ?></label></th>
                    <td>
                        <label for="bw-site-layout-full-bleed">
                            <input type="checkbox" id="bw-site-layout-full-bleed" name="bw_site_layout[allow_full_bleed_sections]" value="1" <?php checked(1, (int) $settings['allow_full_bleed_sections']); ?> />
                            <?php esc_html_e('Enable breakout/full-bleed utility classes so selected Elementor containers can extend past the shell when needed.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </section>

        <section class="bw-admin-card">
            <h2 class="bw-admin-card-title"><?php esc_html_e('Wrapper Strategy', 'bw'); ?></h2>
            <p class="bw-admin-card-helper"><?php esc_html_e('The viewport stays full width. Only the inner shell stops at the configured max width and remains centered. Use the Info tab utility classes on Elementor Containers or Sections whenever a slider or hero should break out of that shell.', 'bw'); ?></p>
        </section>

        <?php submit_button(__('Save Layout Settings', 'bw'), 'primary', 'bw_layout_settings_submit'); ?>
    </form>
    <?php
}

/**
 * Return selected products for Promotions & Labels admin controls.
 *
 * @param int[] $product_ids Product IDs.
 * @return array<int,array<string,mixed>>
 */
function bw_get_product_labels_admin_selected_products($product_ids)
{
    $product_ids = array_values(array_filter(array_map('absint', (array) $product_ids)));
    if (empty($product_ids)) {
        return [];
    }

    $products = [];

    foreach ($product_ids as $product_id) {
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;
        if (!$product instanceof WC_Product) {
            continue;
        }

        $products[] = [
            'id' => $product_id,
            'title' => $product->get_name(),
        ];
    }

    return $products;
}

/**
 * Render Promotions & Labels dedicated admin page.
 *
 * @return void
 */
function bw_product_labels_render_admin_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap bw-admin-root bw-admin-page bw-admin-page-product-labels">
        <div class="bw-admin-header">
            <h1 class="bw-admin-title"><?php esc_html_e('Promotions & Labels', 'bw'); ?></h1>
            <p class="bw-admin-subtitle"><?php esc_html_e('Manage automatic and manual WooCommerce product badges from a dedicated Blackwork admin page.', 'bw'); ?></p>
        </div>

        <div class="bw-admin-action-bar">
            <div class="bw-admin-action-meta">
                <?php esc_html_e('Adjust label rules, then save the current configuration.', 'bw'); ?>
            </div>
            <div class="bw-admin-action-buttons">
                <button type="button" class="button button-primary" id="bw-product-labels-save-proxy">
                    <?php esc_html_e('Save Settings', 'bw'); ?>
                </button>
            </div>
        </div>

        <section class="bw-admin-card bw-admin-card-product-labels">
            <h2 class="bw-admin-card-title"><?php esc_html_e('Panels', 'bw'); ?></h2>
            <p class="bw-admin-card-helper"><?php esc_html_e('Configure label logic and curated product assignments in one dedicated surface.', 'bw'); ?></p>

            <div class="bw-admin-site-settings-content">
                <?php bw_site_render_product_labels_tab(); ?>
            </div>
        </section>
    </div>
    <script>
    (function () {
        var proxyButton = document.getElementById('bw-product-labels-save-proxy');
        if (!proxyButton) {
            return;
        }

        proxyButton.addEventListener('click', function () {
            var targetButton = document.querySelector('.bw-product-labels-admin [type="submit"][name="bw_product_labels_settings_submit"]');

            if (targetButton) {
                targetButton.click();
            }
        });
    })();
    </script>
    <?php
}

/**
 * Render Promotions & Labels tab.
 *
 * @return void
 */
function bw_site_render_product_labels_tab()
{
    if (isset($_POST['bw_product_labels_settings_submit'])) {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'bw'));
        }

        check_admin_referer('bw_product_labels_settings_save', 'bw_product_labels_settings_nonce');

        $raw_settings = isset($_POST['bw_product_labels_settings']) ? wp_unslash($_POST['bw_product_labels_settings']) : [];
        $settings = function_exists('bw_sanitize_product_labels_settings')
            ? bw_sanitize_product_labels_settings($raw_settings)
            : [];

        update_option('bw_product_labels_settings_v1', $settings, false);

        $labels_tab = isset($_GET['labels_tab']) ? sanitize_key(wp_unslash($_GET['labels_tab'])) : 'general';
        $allowed_labels_tabs = ['general', 'new', 'sale', 'free-download', 'staff-select'];
        if (!in_array($labels_tab, $allowed_labels_tabs, true)) {
            $labels_tab = 'general';
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'blackwork-site-settings',
            'tab' => 'product-labels',
            'labels_tab' => $labels_tab,
            'saved' => '1',
        ], admin_url('admin.php')));
        exit;
    }

    $settings = function_exists('bw_get_product_labels_settings')
        ? bw_get_product_labels_settings()
        : [];
    $priority_choices = function_exists('bw_get_product_label_priority_choices')
        ? bw_get_product_label_priority_choices()
        : [
            'staff_select' => __('Staff Select', 'bw'),
            'sale' => __('Sale', 'bw'),
            'free_download' => __('Free Download', 'bw'),
            'new' => __('New', 'bw'),
        ];

    $active_labels_tab = isset($_GET['labels_tab']) ? sanitize_key(wp_unslash($_GET['labels_tab'])) : 'general';
    $allowed_labels_tabs = ['general', 'new', 'sale', 'free-download', 'staff-select'];
    if (!in_array($active_labels_tab, $allowed_labels_tabs, true)) {
        $active_labels_tab = 'general';
    }

    $saved = isset($_GET['saved']) && '1' === sanitize_key(wp_unslash($_GET['saved']));
    $priority_order = isset($settings['priority_order']) ? (array) $settings['priority_order'] : array_keys($priority_choices);
    $selected_staff_products = bw_get_product_labels_admin_selected_products(
        function_exists('bw_get_product_label_staff_ids') ? bw_get_product_label_staff_ids($settings) : []
    );

    $general_tab_url = add_query_arg('labels_tab', 'general');
    $new_tab_url = add_query_arg('labels_tab', 'new');
    $sale_tab_url = add_query_arg('labels_tab', 'sale');
    $free_tab_url = add_query_arg('labels_tab', 'free-download');
    $staff_tab_url = add_query_arg('labels_tab', 'staff-select');
    ?>
    <?php if ($saved) : ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php esc_html_e('Product label settings saved successfully.', 'bw'); ?></strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="bw-product-labels-admin">
        <?php wp_nonce_field('bw_product_labels_settings_save', 'bw_product_labels_settings_nonce'); ?>

        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo 'general' === $active_labels_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($general_tab_url); ?>"><?php esc_html_e('General', 'bw'); ?></a>
            <a class="nav-tab <?php echo 'new' === $active_labels_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($new_tab_url); ?>"><?php esc_html_e('New', 'bw'); ?></a>
            <a class="nav-tab <?php echo 'sale' === $active_labels_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($sale_tab_url); ?>"><?php esc_html_e('Sale', 'bw'); ?></a>
            <a class="nav-tab <?php echo 'free-download' === $active_labels_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($free_tab_url); ?>"><?php esc_html_e('Free Download', 'bw'); ?></a>
            <a class="nav-tab <?php echo 'staff-select' === $active_labels_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($staff_tab_url); ?>"><?php esc_html_e('Staff Select', 'bw'); ?></a>
        </h2>

        <div class="bw-tab-panel" data-bw-tab="general" <?php echo 'general' === $active_labels_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table bw-admin-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable product labels', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                            <?php esc_html_e('Enable the Blackwork product label system.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Show labels on archive cards', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[show_archive]" value="1" <?php checked(!empty($settings['show_archive'])); ?> />
                            <?php esc_html_e('Render labels on shared Blackwork WooCommerce product cards.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Show labels on single product', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[show_single]" value="1" <?php checked(!empty($settings['show_single'])); ?> />
                            <?php esc_html_e('Render labels on WooCommerce single product pages.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Show icons on labels', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[show_icons]" value="1" <?php checked(!empty($settings['show_icons'])); ?> />
                            <?php esc_html_e('Display the icon inside supported labels.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-product-labels-max-visible"><?php esc_html_e('Max visible labels per product', 'bw'); ?></label></th>
                    <td>
                        <input type="number" min="1" max="4" step="1" id="bw-product-labels-max-visible" name="bw_product_labels_settings[max_visible]" value="<?php echo esc_attr((string) ($settings['max_visible'] ?? 2)); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e('Controls how many badges can be shown at the same time for a product.', 'bw'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Priority order', 'bw'); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e('Drag to reorder. Higher items win when a product matches multiple labels.', 'bw'); ?></p>
                        <ul class="bw-product-labels-sortable" data-target-input="#bw-product-labels-priority-order">
                            <?php foreach ($priority_order as $label_key) : ?>
                                <?php if (!isset($priority_choices[$label_key])) { continue; } ?>
                                <li class="bw-product-labels-sortable__item" data-key="<?php echo esc_attr($label_key); ?>">
                                    <span class="dashicons dashicons-menu-alt2" aria-hidden="true"></span>
                                    <span><?php echo esc_html($priority_choices[$label_key]); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="hidden" id="bw-product-labels-priority-order" name="bw_product_labels_settings[priority_order]" value="<?php echo esc_attr(implode(',', $priority_order)); ?>" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="new" <?php echo 'new' === $active_labels_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table bw-admin-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable New label', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[new_enabled]" value="1" <?php checked(!empty($settings['new_enabled'])); ?> />
                            <?php esc_html_e('Automatically label recently published products as New.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-product-labels-new-days"><?php esc_html_e('Duration in days', 'bw'); ?></label></th>
                    <td>
                        <input type="number" min="1" max="3650" step="1" id="bw-product-labels-new-days" name="bw_product_labels_settings[new_days]" value="<?php echo esc_attr((string) ($settings['new_days'] ?? 30)); ?>" class="small-text" />
                        <p class="description"><?php esc_html_e('Products published within this number of days will receive the New label automatically.', 'bw'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="sale" <?php echo 'sale' === $active_labels_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table bw-admin-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Sale label', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[sale_enabled]" value="1" <?php checked(!empty($settings['sale_enabled'])); ?> />
                            <?php esc_html_e('Use WooCommerce pricing to automatically label on-sale products.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-product-labels-sale-display-mode"><?php esc_html_e('Display mode', 'bw'); ?></label></th>
                    <td>
                        <select id="bw-product-labels-sale-display-mode" name="bw_product_labels_settings[sale_display_mode]">
                            <option value="save_percentage" <?php selected(($settings['sale_display_mode'] ?? 'save_percentage'), 'save_percentage'); ?>><?php esc_html_e('Save %', 'bw'); ?></option>
                            <option value="sale" <?php selected(($settings['sale_display_mode'] ?? 'save_percentage'), 'sale'); ?>><?php esc_html_e('Sale', 'bw'); ?></option>
                            <option value="discount_percentage" <?php selected(($settings['sale_display_mode'] ?? 'save_percentage'), 'discount_percentage'); ?>><?php esc_html_e('-%', 'bw'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="free-download" <?php echo 'free-download' === $active_labels_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table bw-admin-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Free Download label', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[free_enabled]" value="1" <?php checked(!empty($settings['free_enabled'])); ?> />
                            <?php esc_html_e('Automatically label free products according to the selected rule.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-product-labels-free-rule-mode"><?php esc_html_e('Rule mode', 'bw'); ?></label></th>
                    <td>
                        <select id="bw-product-labels-free-rule-mode" name="bw_product_labels_settings[free_rule_mode]">
                            <option value="price_zero_only" <?php selected(($settings['free_rule_mode'] ?? 'price_zero_only'), 'price_zero_only'); ?>><?php esc_html_e('price = 0 only', 'bw'); ?></option>
                            <option value="price_zero_downloadable" <?php selected(($settings['free_rule_mode'] ?? 'price_zero_only'), 'price_zero_downloadable'); ?>><?php esc_html_e('price = 0 + downloadable only', 'bw'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="staff-select" <?php echo 'staff-select' === $active_labels_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table bw-admin-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Staff Select label', 'bw'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bw_product_labels_settings[staff_enabled]" value="1" <?php checked(!empty($settings['staff_enabled'])); ?> />
                            <?php esc_html_e('Allow manually curated products to receive the Staff Select label.', 'bw'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bw-product-labels-staff-products"><?php esc_html_e('Product multi-select', 'bw'); ?></label></th>
                    <td>
                        <select id="bw-product-labels-staff-products" class="bw-product-labels-product-select" multiple="multiple" style="width: 100%;">
                            <?php foreach ($selected_staff_products as $selected_product) : ?>
                                <option value="<?php echo esc_attr((string) $selected_product['id']); ?>" selected="selected"><?php echo esc_html($selected_product['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Search and select the products that should show the Staff Select label.', 'bw'); ?></p>
                        <input type="hidden" id="bw-product-labels-staff-product-ids" name="bw_product_labels_settings[staff_product_ids]" value="<?php echo esc_attr(implode(',', wp_list_pluck($selected_staff_products, 'id'))); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Manual order', 'bw'); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e('Drag to preserve the manual order of selected Staff Select products.', 'bw'); ?></p>
                        <ul class="bw-product-labels-sortable bw-product-labels-sortable--products" id="bw-product-labels-staff-order" data-target-input="#bw-product-labels-staff-manual-order" data-empty-text="<?php echo esc_attr__('Selected products will appear here.', 'bw'); ?>">
                            <?php foreach ($selected_staff_products as $selected_product) : ?>
                                <li class="bw-product-labels-sortable__item" data-key="<?php echo esc_attr((string) $selected_product['id']); ?>">
                                    <span class="dashicons dashicons-menu-alt2" aria-hidden="true"></span>
                                    <span><?php echo esc_html($selected_product['title']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="hidden" id="bw-product-labels-staff-manual-order" name="bw_product_labels_settings[staff_manual_order]" value="<?php echo esc_attr(implode(',', wp_list_pluck($selected_staff_products, 'id'))); ?>" />
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Save Promotions & Labels', 'bw'), 'primary', 'bw_product_labels_settings_submit'); ?>
    </form>
    <?php
}

/**
 * Renderizza il tab Account Page
 */
function bw_site_render_account_page_tab()
{
    $saved = false;

    if (isset($_POST['bw_account_page_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_account_page_save', 'bw_account_page_nonce');

        $login_provider = isset($_POST['bw_account_login_provider']) ? sanitize_key(wp_unslash($_POST['bw_account_login_provider'])) : 'wordpress';
        $login_image = isset($_POST['bw_account_login_image']) ? esc_url_raw(wp_unslash($_POST['bw_account_login_image'])) : '';
        $login_image_id = isset($_POST['bw_account_login_image_id']) ? absint(wp_unslash($_POST['bw_account_login_image_id'])) : 0;
        $logo = isset($_POST['bw_account_logo']) ? esc_url_raw(wp_unslash($_POST['bw_account_logo'])) : '';
        $logo_id = isset($_POST['bw_account_logo_id']) ? absint(wp_unslash($_POST['bw_account_logo_id'])) : 0;
        $logo_width = isset($_POST['bw_account_logo_width']) ? absint(wp_unslash($_POST['bw_account_logo_width'])) : 180;
        $logo_padding_top = isset($_POST['bw_account_logo_padding_top']) ? absint(wp_unslash($_POST['bw_account_logo_padding_top'])) : 0;
        $logo_padding_bottom = isset($_POST['bw_account_logo_padding_bottom']) ? absint(wp_unslash($_POST['bw_account_logo_padding_bottom'])) : 30;
        $login_title_supabase = isset($_POST['bw_account_login_title_supabase']) ? sanitize_text_field(wp_unslash($_POST['bw_account_login_title_supabase'])) : '';
        $login_subtitle_supabase = isset($_POST['bw_account_login_subtitle_supabase']) ? sanitize_textarea_field(wp_unslash($_POST['bw_account_login_subtitle_supabase'])) : '';
        $login_title_wordpress = isset($_POST['bw_account_login_title_wordpress']) ? sanitize_text_field(wp_unslash($_POST['bw_account_login_title_wordpress'])) : '';
        $login_subtitle_wordpress = isset($_POST['bw_account_login_subtitle_wordpress']) ? sanitize_textarea_field(wp_unslash($_POST['bw_account_login_subtitle_wordpress'])) : '';
        $show_social_buttons = isset($_POST['bw_account_show_social_buttons']) ? 1 : 0;
        $facebook = isset($_POST['bw_account_facebook']) ? 1 : 0;
        $google = isset($_POST['bw_account_google']) ? 1 : 0;
        $facebook_app_id = isset($_POST['bw_account_facebook_app_id']) ? sanitize_text_field(wp_unslash($_POST['bw_account_facebook_app_id'])) : '';
        $facebook_app_secret = isset($_POST['bw_account_facebook_app_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_account_facebook_app_secret'])) : '';
        $google_client_id = isset($_POST['bw_account_google_client_id']) ? sanitize_text_field(wp_unslash($_POST['bw_account_google_client_id'])) : '';
        $google_client_secret = isset($_POST['bw_account_google_client_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_account_google_client_secret'])) : '';
        $passwordless_url = isset($_POST['bw_account_passwordless_url']) ? esc_url_raw(wp_unslash($_POST['bw_account_passwordless_url'])) : '';
        $supabase_project_url = isset($_POST['bw_supabase_project_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_project_url']))) : '';
        $supabase_anon_key = isset($_POST['bw_supabase_anon_key']) ? sanitize_textarea_field(trim(wp_unslash($_POST['bw_supabase_anon_key']))) : '';
        $supabase_service_key = isset($_POST['bw_supabase_service_role_key']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_service_role_key'])) : '';
        $supabase_auth_mode = isset($_POST['bw_supabase_auth_mode']) ? sanitize_key(wp_unslash($_POST['bw_supabase_auth_mode'])) : 'password';
        $supabase_login_mode = isset($_POST['bw_supabase_login_mode']) ? sanitize_key(wp_unslash($_POST['bw_supabase_login_mode'])) : 'native';
        $supabase_cookie_name = isset($_POST['bw_supabase_jwt_cookie_name']) ? sanitize_key(wp_unslash($_POST['bw_supabase_jwt_cookie_name'])) : 'bw_supabase_session';
        $supabase_storage = isset($_POST['bw_supabase_session_storage']) ? sanitize_key(wp_unslash($_POST['bw_supabase_session_storage'])) : 'cookie';
        $supabase_link_users = isset($_POST['bw_supabase_enable_wp_user_linking']) ? 1 : 0;
        $supabase_debug_log = isset($_POST['bw_supabase_debug_log']) ? 1 : 0;
        $supabase_with_plugins = isset($_POST['bw_supabase_with_plugins']) ? 1 : 0;
        $supabase_registration = isset($_POST['bw_supabase_registration_mode']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_registration_mode'])) : 'R2';
        $supabase_signup_url = isset($_POST['bw_supabase_provider_signup_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_provider_signup_url'])) : '';
        $supabase_reset_url = isset($_POST['bw_supabase_provider_reset_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_provider_reset_url'])) : '';
        $supabase_confirm_url = isset($_POST['bw_supabase_email_confirm_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_email_confirm_redirect_url']))) : '';
        $supabase_magic_link_enabled = isset($_POST['bw_supabase_magic_link_enabled']) ? 1 : 0;
        $supabase_otp_allow_signup = isset($_POST['bw_supabase_otp_allow_signup']) ? 1 : 0;
        $supabase_oauth_google_enabled = isset($_POST['bw_supabase_oauth_google_enabled']) ? 1 : 0;
        $supabase_oauth_facebook_enabled = isset($_POST['bw_supabase_oauth_facebook_enabled']) ? 1 : 0;
        $supabase_oauth_apple_enabled = isset($_POST['bw_supabase_oauth_apple_enabled']) ? 1 : 0;
        $supabase_google_client_id = isset($_POST['bw_supabase_google_client_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_google_client_id'])) : '';
        $supabase_google_client_secret = isset($_POST['bw_supabase_google_client_secret']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_google_client_secret'])) : '';
        $supabase_google_redirect_url = isset($_POST['bw_supabase_google_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_google_redirect_url'])) : '';
        $supabase_google_scopes = isset($_POST['bw_supabase_google_scopes']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_google_scopes'])) : '';
        $supabase_google_prompt = isset($_POST['bw_supabase_google_prompt']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_google_prompt'])) : '';
        $supabase_facebook_app_id = isset($_POST['bw_supabase_facebook_app_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_facebook_app_id'])) : '';
        $supabase_facebook_app_secret = isset($_POST['bw_supabase_facebook_app_secret']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_facebook_app_secret'])) : '';
        $supabase_facebook_redirect_url = isset($_POST['bw_supabase_facebook_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_facebook_redirect_url'])) : '';
        $supabase_facebook_scopes = isset($_POST['bw_supabase_facebook_scopes']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_facebook_scopes'])) : '';
        $supabase_apple_client_id = isset($_POST['bw_supabase_apple_client_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_apple_client_id'])) : '';
        $supabase_apple_team_id = isset($_POST['bw_supabase_apple_team_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_apple_team_id'])) : '';
        $supabase_apple_key_id = isset($_POST['bw_supabase_apple_key_id']) ? sanitize_text_field(wp_unslash($_POST['bw_supabase_apple_key_id'])) : '';
        $supabase_apple_private_key = isset($_POST['bw_supabase_apple_private_key']) ? sanitize_textarea_field(wp_unslash($_POST['bw_supabase_apple_private_key'])) : '';
        $supabase_apple_redirect_url = isset($_POST['bw_supabase_apple_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_apple_redirect_url'])) : '';
        $supabase_password_enabled = isset($_POST['bw_supabase_login_password_enabled']) ? 1 : 0;
        $supabase_magic_link_redirect = isset($_POST['bw_supabase_magic_link_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_magic_link_redirect_url']))) : '';
        $supabase_oauth_redirect = isset($_POST['bw_supabase_oauth_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_oauth_redirect_url']))) : '';
        $supabase_signup_redirect = isset($_POST['bw_supabase_signup_redirect_url']) ? esc_url_raw(trim(wp_unslash($_POST['bw_supabase_signup_redirect_url']))) : '';
        $supabase_auto_login = isset($_POST['bw_supabase_auto_login_after_confirm']) ? 1 : 0;
        $supabase_create_users = isset($_POST['bw_supabase_create_wp_users']) ? 1 : 0;

        if (!in_array($login_provider, ['wordpress', 'supabase'], true)) {
            $login_provider = 'wordpress';
        }

        if (!in_array($supabase_auth_mode, ['password'], true)) {
            $supabase_auth_mode = 'password';
        }

        if (!in_array($supabase_login_mode, ['native', 'oidc'], true)) {
            $supabase_login_mode = 'native';
        }

        if (!in_array($supabase_storage, ['cookie', 'usermeta'], true)) {
            $supabase_storage = 'cookie';
        }
        if (!in_array($supabase_registration, ['R1', 'R2', 'R3'], true)) {
            $supabase_registration = 'R2';
        }

        if ($logo_width < 20) {
            $logo_width = 20;
        }
        if ($logo_width > 400) {
            $logo_width = 400;
        }
        if ($logo_padding_top > 200) {
            $logo_padding_top = 200;
        }
        if ($logo_padding_bottom > 200) {
            $logo_padding_bottom = 200;
        }

        if ($login_image_id) {
            $login_image_url = wp_get_attachment_url($login_image_id);
            if ($login_image_url) {
                $login_image = $login_image_url;
            }
        }
        if ($logo_id) {
            $logo_url = wp_get_attachment_url($logo_id);
            if ($logo_url) {
                $logo = $logo_url;
            }
        }

        update_option('bw_account_login_provider', $login_provider);
        update_option('bw_account_login_image', $login_image);
        update_option('bw_account_login_image_id', $login_image_id);
        update_option('bw_account_logo', $logo);
        update_option('bw_account_logo_id', $logo_id);
        update_option('bw_account_logo_width', $logo_width);
        update_option('bw_account_logo_padding_top', $logo_padding_top);
        update_option('bw_account_logo_padding_bottom', $logo_padding_bottom);
        update_option('bw_account_login_title_supabase', $login_title_supabase);
        update_option('bw_account_login_subtitle_supabase', $login_subtitle_supabase);
        update_option('bw_account_login_title_wordpress', $login_title_wordpress);
        update_option('bw_account_login_subtitle_wordpress', $login_subtitle_wordpress);
        // Legacy fallback options used by older code paths.
        update_option('bw_account_login_title', $login_title_wordpress);
        update_option('bw_account_login_subtitle', $login_subtitle_wordpress);
        update_option('bw_account_show_social_buttons', $show_social_buttons);
        update_option('bw_account_passwordless_url', $passwordless_url);

        // WordPress provider options - only save if WordPress is selected (preserve Supabase settings when switching)
        if ('wordpress' === $login_provider) {
            update_option('bw_account_facebook', $facebook);
            update_option('bw_account_google', $google);
            update_option('bw_account_facebook_app_id', $facebook_app_id);
            update_option('bw_account_facebook_app_secret', $facebook_app_secret);
            update_option('bw_account_google_client_id', $google_client_id);
            update_option('bw_account_google_client_secret', $google_client_secret);
        }

        // Supabase provider options - only save if Supabase is selected (preserve WordPress settings when switching)
        if ('supabase' === $login_provider) {
            update_option('bw_supabase_project_url', $supabase_project_url);
            update_option('bw_supabase_anon_key', $supabase_anon_key);
            update_option('bw_supabase_service_role_key', $supabase_service_key);
            update_option('bw_supabase_auth_mode', $supabase_auth_mode);
            update_option('bw_supabase_login_mode', $supabase_login_mode);
            update_option('bw_supabase_jwt_cookie_name', $supabase_cookie_name);
            update_option('bw_supabase_session_storage', $supabase_storage);
            update_option('bw_supabase_enable_wp_user_linking', $supabase_link_users);
            update_option('bw_supabase_debug_log', $supabase_debug_log);
            update_option('bw_supabase_with_plugins', $supabase_with_plugins);
            update_option('bw_supabase_registration_mode', $supabase_registration);
            update_option('bw_supabase_provider_signup_url', $supabase_signup_url);
            update_option('bw_supabase_provider_reset_url', $supabase_reset_url);
            update_option('bw_supabase_email_confirm_redirect_url', $supabase_confirm_url);
            update_option('bw_supabase_magic_link_enabled', $supabase_magic_link_enabled);
            update_option('bw_supabase_otp_allow_signup', $supabase_otp_allow_signup);
            update_option('bw_supabase_oauth_google_enabled', $supabase_oauth_google_enabled);
            update_option('bw_supabase_oauth_facebook_enabled', $supabase_oauth_facebook_enabled);
            update_option('bw_supabase_oauth_apple_enabled', $supabase_oauth_apple_enabled);
            update_option('bw_supabase_google_client_id', $supabase_google_client_id);
            update_option('bw_supabase_google_client_secret', $supabase_google_client_secret);
            update_option('bw_supabase_google_redirect_url', $supabase_google_redirect_url);
            update_option('bw_supabase_google_scopes', $supabase_google_scopes);
            update_option('bw_supabase_google_prompt', $supabase_google_prompt);
            update_option('bw_supabase_facebook_app_id', $supabase_facebook_app_id);
            update_option('bw_supabase_facebook_app_secret', $supabase_facebook_app_secret);
            update_option('bw_supabase_facebook_redirect_url', $supabase_facebook_redirect_url);
            update_option('bw_supabase_facebook_scopes', $supabase_facebook_scopes);
            update_option('bw_supabase_apple_client_id', $supabase_apple_client_id);
            update_option('bw_supabase_apple_team_id', $supabase_apple_team_id);
            update_option('bw_supabase_apple_key_id', $supabase_apple_key_id);
            update_option('bw_supabase_apple_private_key', $supabase_apple_private_key);
            update_option('bw_supabase_apple_redirect_url', $supabase_apple_redirect_url);
            update_option('bw_supabase_login_password_enabled', $supabase_password_enabled);
            update_option('bw_supabase_magic_link_redirect_url', $supabase_magic_link_redirect);
            update_option('bw_supabase_oauth_redirect_url', $supabase_oauth_redirect);
            update_option('bw_supabase_signup_redirect_url', $supabase_signup_redirect);
            update_option('bw_supabase_auto_login_after_confirm', $supabase_auto_login);
            update_option('bw_supabase_create_wp_users', $supabase_create_users);
        }

        // Clear social login settings cache.
        if (class_exists('BW_Social_Login')) {
            BW_Social_Login::clear_cache();
        }

        $saved = true;
    }

    $login_provider = get_option('bw_account_login_provider', 'wordpress');
    $login_image = get_option('bw_account_login_image', '');
    $login_image_id = (int) get_option('bw_account_login_image_id', 0);
    $logo = get_option('bw_account_logo', '');
    $logo_id = (int) get_option('bw_account_logo_id', 0);
    $logo_width = (int) get_option('bw_account_logo_width', 180);
    $logo_padding_top = (int) get_option('bw_account_logo_padding_top', 0);
    $logo_padding_bottom = (int) get_option('bw_account_logo_padding_bottom', 30);
    $legacy_login_title = get_option('bw_account_login_title', 'Log in to Blackwork');
    $legacy_login_subtitle = get_option(
        'bw_account_login_subtitle',
        "If you are new, we will create your account automatically.\nNew or returning, this works the same."
    );
    $login_title_supabase = get_option('bw_account_login_title_supabase', $legacy_login_title);
    $login_subtitle_supabase = get_option('bw_account_login_subtitle_supabase', $legacy_login_subtitle);
    $login_title_wordpress = get_option('bw_account_login_title_wordpress', $legacy_login_title);
    $login_subtitle_wordpress = get_option('bw_account_login_subtitle_wordpress', $legacy_login_subtitle);
    $show_social_buttons = (int) get_option('bw_account_show_social_buttons', 1);
    $facebook = (int) get_option('bw_account_facebook', 0);
    $google = (int) get_option('bw_account_google', 0);
    $facebook_app_id = get_option('bw_account_facebook_app_id', '');
    $facebook_app_secret = get_option('bw_account_facebook_app_secret', '');
    $google_client_id = get_option('bw_account_google_client_id', '');
    $google_client_secret = get_option('bw_account_google_client_secret', '');
    $passwordless_url = get_option('bw_account_passwordless_url', '');
    $supabase_project_url = get_option('bw_supabase_project_url', '');
    $supabase_anon_key = get_option('bw_supabase_anon_key', '');
    $supabase_service_key = get_option('bw_supabase_service_role_key', '');
    $supabase_auth_mode = get_option('bw_supabase_auth_mode', 'password');
    $supabase_login_mode = get_option('bw_supabase_login_mode', 'native');
    $supabase_cookie_name = get_option('bw_supabase_jwt_cookie_name', 'bw_supabase_session');
    $supabase_storage = get_option('bw_supabase_session_storage', 'cookie');
    $supabase_link_users = (int) get_option('bw_supabase_enable_wp_user_linking', 0);
    $supabase_debug_log = (int) get_option('bw_supabase_debug_log', 0);
    $supabase_with_plugins = (int) get_option('bw_supabase_with_plugins', 0);
    $supabase_registration = get_option('bw_supabase_registration_mode', 'R2');
    $supabase_signup_url = get_option('bw_supabase_provider_signup_url', '');
    $supabase_reset_url = get_option('bw_supabase_provider_reset_url', '');
    $supabase_confirm_url = get_option('bw_supabase_email_confirm_redirect_url', site_url('/my-account/?bw_email_confirmed=1'));
    $supabase_magic_link_enabled = (int) get_option('bw_supabase_magic_link_enabled', 1);
    $supabase_otp_allow_signup = (int) get_option('bw_supabase_otp_allow_signup', 1);
    $supabase_oauth_google_enabled = (int) get_option('bw_supabase_oauth_google_enabled', 1);
    $supabase_oauth_facebook_enabled = (int) get_option('bw_supabase_oauth_facebook_enabled', 1);
    $supabase_oauth_apple_enabled = (int) get_option('bw_supabase_oauth_apple_enabled', 0);
    $supabase_google_client_id = get_option('bw_supabase_google_client_id', '');
    $supabase_google_client_secret = get_option('bw_supabase_google_client_secret', '');
    $supabase_google_redirect_url = get_option('bw_supabase_google_redirect_url', site_url('/my-account/'));
    $supabase_google_scopes = get_option('bw_supabase_google_scopes', 'email profile');
    $supabase_google_prompt = get_option('bw_supabase_google_prompt', 'select_account');
    $supabase_facebook_app_id = get_option('bw_supabase_facebook_app_id', '');
    $supabase_facebook_app_secret = get_option('bw_supabase_facebook_app_secret', '');
    $supabase_facebook_redirect_url = get_option('bw_supabase_facebook_redirect_url', site_url('/my-account/'));
    $supabase_facebook_scopes = get_option('bw_supabase_facebook_scopes', 'email,public_profile');
    $supabase_apple_client_id = get_option('bw_supabase_apple_client_id', '');
    $supabase_apple_team_id = get_option('bw_supabase_apple_team_id', '');
    $supabase_apple_key_id = get_option('bw_supabase_apple_key_id', '');
    $supabase_apple_private_key = get_option('bw_supabase_apple_private_key', '');
    $supabase_apple_redirect_url = get_option('bw_supabase_apple_redirect_url', site_url('/my-account/'));
    $supabase_password_enabled = (int) get_option('bw_supabase_login_password_enabled', 1);
    $supabase_magic_link_redirect = get_option('bw_supabase_magic_link_redirect_url', site_url('/my-account/'));
    $supabase_oauth_redirect = get_option('bw_supabase_oauth_redirect_url', site_url('/my-account/'));
    $supabase_signup_redirect = get_option('bw_supabase_signup_redirect_url', site_url('/my-account/?bw_email_confirmed=1'));
    $supabase_auto_login = (int) get_option('bw_supabase_auto_login_after_confirm', 0);
    $supabase_create_users = (int) get_option('bw_supabase_create_wp_users', 1);

    $facebook_redirect = function_exists('bw_mew_get_social_redirect_uri') ? bw_mew_get_social_redirect_uri('facebook') : add_query_arg('bw_social_login_callback', 'facebook', wc_get_page_permalink('myaccount'));
    $google_redirect = function_exists('bw_mew_get_social_redirect_uri') ? bw_mew_get_social_redirect_uri('google') : add_query_arg('bw_social_login_callback', 'google', wc_get_page_permalink('myaccount'));

    $login_image_url = $login_image;
    if ($login_image_id) {
        $login_image_attachment = wp_get_attachment_url($login_image_id);
        if ($login_image_attachment) {
            $login_image_url = $login_image_attachment;
        }
    }
    $logo_url = $logo;
    if ($logo_id) {
        $logo_attachment = wp_get_attachment_url($logo_id);
        if ($logo_attachment) {
            $logo_url = $logo_attachment;
        }
    }
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>
    <form method="post" action="">
        <?php wp_nonce_field('bw_account_page_save', 'bw_account_page_nonce'); ?>

        <h2 class="nav-tab-wrapper bw-account-settings-tabs" role="tablist">
            <a href="#design" class="nav-tab nav-tab-active" role="tab" aria-selected="true" data-bw-account-tab="design">
                <?php esc_html_e('Design', 'bw'); ?>
            </a>
            <a href="#technical" class="nav-tab" role="tab" aria-selected="false" data-bw-account-tab="technical">
                <?php esc_html_e('Provider WordPress or Supabase', 'bw'); ?>
            </a>
        </h2>

        <div class="bw-account-settings-tab" data-bw-account-tab="design">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="bw_account_login_image"><?php esc_html_e('Login Image (cover)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_login_image" name="bw_account_login_image"
                                value="<?php echo esc_attr($login_image_url); ?>" class="regular-text" />
                            <input type="hidden" id="bw_account_login_image_id" name="bw_account_login_image_id"
                                value="<?php echo esc_attr($login_image_id); ?>" />
                            <button type="button" class="button bw-media-upload" data-target="#bw_account_login_image"
                                data-id-target="#bw_account_login_image_id"><?php esc_html_e('Select image', 'bw'); ?></button>
                            <p class="description"><?php esc_html_e('Cover image shown on the left side.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_account_logo"><?php esc_html_e('Logo', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_logo" name="bw_account_logo"
                                value="<?php echo esc_attr($logo_url); ?>" class="regular-text" />
                            <input type="hidden" id="bw_account_logo_id" name="bw_account_logo_id"
                                value="<?php echo esc_attr($logo_id); ?>" />
                            <button type="button" class="button bw-media-upload" data-target="#bw_account_logo"
                                data-id-target="#bw_account_logo_id"><?php esc_html_e('Select logo', 'bw'); ?></button>
                            <p class="description"><?php esc_html_e('Logo displayed above the login form.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_account_logo_width"><?php esc_html_e('Logo width (px)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="bw_account_logo_width" name="bw_account_logo_width"
                                value="<?php echo esc_attr($logo_width); ?>" min="20" max="400" step="1"
                                class="small-text" />
                            <p class="description"><?php esc_html_e('Max logo width in pixels. Default: 180px', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_logo_padding_top"><?php esc_html_e('Padding top logo (px)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="bw_account_logo_padding_top" name="bw_account_logo_padding_top"
                                value="<?php echo esc_attr($logo_padding_top); ?>" min="0" max="200" step="1"
                                class="small-text" />
                            <p class="description"><?php esc_html_e('Space above the logo in pixels.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_logo_padding_bottom"><?php esc_html_e('Padding bottom logo (px)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="bw_account_logo_padding_bottom" name="bw_account_logo_padding_bottom"
                                value="<?php echo esc_attr($logo_padding_bottom); ?>" min="0" max="200" step="1"
                                class="small-text" />
                            <p class="description"><?php esc_html_e('Space below the logo in pixels.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="supabase" <?php echo 'supabase' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_title_supabase"><?php esc_html_e('Login Title (Supabase)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_login_title_supabase" name="bw_account_login_title_supabase"
                                value="<?php echo esc_attr($login_title_supabase); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Title shown when Login Provider is Supabase.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="supabase" <?php echo 'supabase' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_subtitle_supabase"><?php esc_html_e('Login Subtitle (Supabase)', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_account_login_subtitle_supabase" name="bw_account_login_subtitle_supabase" rows="3"
                                class="large-text"><?php echo esc_textarea($login_subtitle_supabase); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Subtitle shown when Login Provider is Supabase. Use new lines for line breaks.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="wordpress" <?php echo 'wordpress' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_title_wordpress"><?php esc_html_e('Login Title (WordPress)', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_account_login_title_wordpress" name="bw_account_login_title_wordpress"
                                value="<?php echo esc_attr($login_title_wordpress); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Title shown when Login Provider is WordPress.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-login-copy-field" data-bw-login-copy-provider="wordpress" <?php echo 'wordpress' === $login_provider ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="bw_account_login_subtitle_wordpress"><?php esc_html_e('Login Subtitle (WordPress)', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_account_login_subtitle_wordpress" name="bw_account_login_subtitle_wordpress" rows="3"
                                class="large-text"><?php echo esc_textarea($login_subtitle_wordpress); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Subtitle shown when Login Provider is WordPress. Use new lines for line breaks.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_show_social_buttons"><?php esc_html_e('Show social login buttons', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_account_show_social_buttons"
                                    name="bw_account_show_social_buttons" value="1" <?php checked(1, $show_social_buttons); ?> />
                                <?php esc_html_e('Show social login buttons', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Hide or show the social login buttons without disabling providers.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bw-account-settings-tab" data-bw-account-tab="technical" style="display:none;">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Login Provider', 'bw'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="radio" name="bw_account_login_provider" value="wordpress" <?php checked('wordpress', $login_provider); ?> />
                                    <?php esc_html_e('WordPress', 'bw'); ?>
                                </label>
                                <label style="display:block;">
                                    <input type="radio" name="bw_account_login_provider" value="supabase" <?php checked('supabase', $login_provider); ?> />
                                    <?php esc_html_e('Supabase', 'bw'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Choose which login provider is the default for the My Account page.', 'bw'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
                <tbody class="bw-login-provider-section" data-bw-login-provider="wordpress" <?php echo 'supabase' === $login_provider ? 'style="display:none;"' : ''; ?>>
                    <tr>
                        <th scope="row"><?php esc_html_e('Social login providers', 'bw'); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:8px;">
                                <input type="checkbox" id="bw_account_facebook" name="bw_account_facebook" value="1" <?php checked(1, $facebook); ?> />
                                <?php esc_html_e('Enable Facebook Login', 'bw'); ?>
                            </label>
                            <label style="display:block;">
                                <input type="checkbox" id="bw_account_google" name="bw_account_google" value="1" <?php checked(1, $google); ?> />
                                <?php esc_html_e('Enable Google Login', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Enable providers to configure their credentials.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-account-provider-option" data-bw-account-provider="facebook" <?php echo $facebook ? '' : 'style="display:none;"'; ?>>
                        <td colspan="2">
                            <div class="bw-settings-group">
                                <div class="bw-settings-group__title"><?php esc_html_e('Facebook settings', 'bw'); ?>
                                </div>
                                <details class="bw-oauth-help-accordion"
                                    style="background: #f0f6fc; border: 1px solid #0969da; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                                    <summary
                                        style="cursor: pointer; font-weight: 600; color: #0969da; font-size: 14px; user-select: none;">
                                        📘 Come ottenere Facebook App ID e Secret
                                    </summary>
                                    <div style="padding: 12px 0 0 0; color: #1f2328; line-height: 1.6;">
                                        <p style="margin: 0 0 12px 0;"><strong>Segui questi passi:</strong></p>
                                        <ol style="margin: 0 0 12px 20px; padding: 0;">
                                            <li style="margin-bottom: 8px;">
                                                <strong>Vai alla console Facebook Developers:</strong><br>
                                                <a href="https://developers.facebook.com/apps/" target="_blank"
                                                    rel="noopener"
                                                    style="color: #0969da; text-decoration: none; font-weight: 500;">
                                                    🔗 https://developers.facebook.com/apps/
                                                </a>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Clicca su "Crea un'app"</strong> (Create App)
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Seleziona tipo:</strong> "Consumatore" (Consumer)
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Compila:</strong> Nome app e email di contatto
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Aggiungi il prodotto "Facebook Login"</strong>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Vai su Impostazioni > Di base</strong> per trovare:
                                                <ul style="margin: 4px 0 0 20px;">
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">ID app</code>
                                                        → Copia in "Facebook App ID" sotto</li>
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">Chiave segreta dell'app</code>
                                                        → Clicca "Mostra", copia in "Facebook App Secret" sotto</li>
                                                </ul>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Configura Redirect URI:</strong><br>
                                                Vai su <strong>Facebook Login > Impostazioni</strong><br>
                                                Nel campo "Valid OAuth Redirect URIs" incolla l'URL dal campo
                                                <strong>"Facebook Redirect URI"</strong> sotto
                                            </li>
                                            <li style="margin-bottom: 0;">
                                                <strong style="color: #d1242f;">⚠️ IMPORTANTE:</strong> Pubblica l'app
                                                (passa da "Development" a "Live" in Impostazioni > Di base)
                                            </li>
                                        </ol>
                                        <p
                                            style="margin: 12px 0 0 0; padding: 10px; background: #fff8c5; border-left: 3px solid #9a6700; border-radius: 3px; font-size: 13px;">
                                            💡 <strong>Tip:</strong> Tieni aperta la console Facebook in un'altra tab mentre
                                            compili i campi sotto.
                                        </p>
                                    </div>
                                </details>
                                <div class="bw-settings-group__grid">
                                    <label
                                        for="bw_account_facebook_app_id"><?php esc_html_e('Facebook App ID', 'bw'); ?></label>
                                    <input type="text" id="bw_account_facebook_app_id" name="bw_account_facebook_app_id"
                                        value="<?php echo esc_attr($facebook_app_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_account_facebook_app_secret"><?php esc_html_e('Facebook App Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_account_facebook_app_secret"
                                        name="bw_account_facebook_app_secret"
                                        value="<?php echo esc_attr($facebook_app_secret); ?>" class="regular-text" />
                                    <label><?php esc_html_e('Facebook Redirect URI', 'bw'); ?></label>
                                    <input type="text" readonly class="regular-text"
                                        value="<?php echo esc_url($facebook_redirect); ?>" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Use this URL in the Facebook app panel to configure the redirect URI.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr class="bw-account-provider-option" data-bw-account-provider="google" <?php echo $google ? '' : 'style="display:none;"'; ?>>
                        <td colspan="2">
                            <div class="bw-settings-group">
                                <div class="bw-settings-group__title"><?php esc_html_e('Google settings', 'bw'); ?></div>
                                <details class="bw-oauth-help-accordion"
                                    style="background: #f0f6fc; border: 1px solid #0969da; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                                    <summary
                                        style="cursor: pointer; font-weight: 600; color: #0969da; font-size: 14px; user-select: none;">
                                        📗 Come ottenere Google Client ID e Secret
                                    </summary>
                                    <div style="padding: 12px 0 0 0; color: #1f2328; line-height: 1.6;">
                                        <p style="margin: 0 0 12px 0;"><strong>Segui questi passi:</strong></p>
                                        <ol style="margin: 0 0 12px 20px; padding: 0;">
                                            <li style="margin-bottom: 8px;">
                                                <strong>Vai alla Google Cloud Console:</strong><br>
                                                <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                                                    rel="noopener"
                                                    style="color: #0969da; text-decoration: none; font-weight: 500;">
                                                    🔗 https://console.cloud.google.com/apis/credentials
                                                </a>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Crea un nuovo progetto</strong> (se non ne hai già uno)<br>
                                                Clicca sul menu progetti in alto e poi "Nuovo progetto"
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Configura schermata consenso OAuth:</strong><br>
                                                <a href="https://console.cloud.google.com/apis/credentials/consent"
                                                    target="_blank" rel="noopener"
                                                    style="color: #0969da; text-decoration: none;">
                                                    🔗 Vai alla schermata consenso
                                                </a><br>
                                                Seleziona "Esterno" (External) e compila i campi obbligatori
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Crea credenziali OAuth 2.0:</strong>
                                                <ul style="margin: 4px 0 0 20px;">
                                                    <li>Clicca "+ Crea credenziali" > "ID client OAuth"</li>
                                                    <li>Tipo: "Applicazione web" (Web application)</li>
                                                    <li>Nome: "BlackWork Login" (o un nome a tua scelta)</li>
                                                </ul>
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Configura Redirect URI:</strong><br>
                                                Nella sezione "URI di reindirizzamento autorizzati":<br>
                                                Clicca "+ Aggiungi URI" e incolla l'URL dal campo <strong>"Google Redirect
                                                    URI"</strong> sotto
                                            </li>
                                            <li style="margin-bottom: 8px;">
                                                <strong>Clicca "Crea"</strong> e copia le credenziali:
                                                <ul style="margin: 4px 0 0 20px;">
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">ID client</code>
                                                        → Copia in "Google Client ID" sotto</li>
                                                    <li><code
                                                            style="background: #eff1f3; padding: 2px 6px; border-radius: 3px; font-family: monospace;">Segreto client</code>
                                                        → Copia in "Google Client Secret" sotto</li>
                                                </ul>
                                            </li>
                                            <li style="margin-bottom: 0;">
                                                <strong style="color: #d1242f;">⚠️ IMPORTANTE:</strong> Pubblica l'app OAuth
                                                (passa da "Testing" a "Production" nella schermata consenso)
                                            </li>
                                        </ol>
                                        <p
                                            style="margin: 12px 0 0 0; padding: 10px; background: #fff8c5; border-left: 3px solid #9a6700; border-radius: 3px; font-size: 13px;">
                                            💡 <strong>Tip:</strong> Tieni aperta la console Google in un'altra tab mentre
                                            compili i campi sotto.
                                        </p>
                                    </div>
                                </details>
                                <div class="bw-settings-group__grid">
                                    <label
                                        for="bw_account_google_client_id"><?php esc_html_e('Google Client ID', 'bw'); ?></label>
                                    <input type="text" id="bw_account_google_client_id" name="bw_account_google_client_id"
                                        value="<?php echo esc_attr($google_client_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_account_google_client_secret"><?php esc_html_e('Google Client Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_account_google_client_secret"
                                        name="bw_account_google_client_secret"
                                        value="<?php echo esc_attr($google_client_secret); ?>" class="regular-text" />
                                    <label><?php esc_html_e('Google Redirect URI', 'bw'); ?></label>
                                    <input type="text" readonly class="regular-text"
                                        value="<?php echo esc_url($google_redirect); ?>" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Configure this URL in the authorized redirect URIs in the Google Cloud Console.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_account_passwordless_url"><?php esc_html_e('URL "Log in Without Password"', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_account_passwordless_url" name="bw_account_passwordless_url"
                                value="<?php echo esc_attr($passwordless_url); ?>" class="regular-text"
                                placeholder="<?php echo esc_url(wp_login_url()); ?>" />
                            <p class="description">
                                <?php esc_html_e('Imposta il link da usare per il login senza password o magic link.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
                <tbody class="bw-login-provider-section" data-bw-login-provider="supabase" <?php echo 'supabase' === $login_provider ? '' : 'style="display:none;"'; ?>>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_project_url"><?php esc_html_e('Supabase Project URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_supabase_project_url" name="bw_supabase_project_url"
                                value="<?php echo esc_attr($supabase_project_url); ?>" class="regular-text"
                                placeholder="https://xxxx.supabase.co" />
                            <p class="description">
                                <?php esc_html_e('Found in Supabase Dashboard → Settings → API Keys.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_anon_key"><?php esc_html_e('Supabase Anon/Public Key', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_supabase_anon_key" name="bw_supabase_anon_key" rows="4"
                                class="large-text"><?php echo esc_textarea($supabase_anon_key); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('The anon key is safe for client-side usage with RLS enabled. It is used here for server-side Auth calls.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_service_role_key"><?php esc_html_e('Supabase Service Role Key (optional)', 'bw'); ?></label>
                        </th>
                        <td>
                            <textarea id="bw_supabase_service_role_key" name="bw_supabase_service_role_key" rows="4"
                                class="large-text"><?php echo esc_textarea($supabase_service_key); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Service role bypasses RLS and must never be exposed to the browser. Keep it server-side only.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_auth_mode"><?php esc_html_e('Auth Mode', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_auth_mode" name="bw_supabase_auth_mode">
                                <option value="password" <?php selected('password', $supabase_auth_mode); ?>>
                                    <?php esc_html_e('Email + Password', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Uses POST /auth/v1/token?grant_type=password for server-side login.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_login_mode"><?php esc_html_e('Login Mode', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_login_mode" name="bw_supabase_login_mode">
                                <option value="native" <?php selected('native', $supabase_login_mode); ?>>
                                    <?php esc_html_e('Native Supabase Login (email/password)', 'bw'); ?>
                                </option>
                                <option value="oidc" <?php selected('oidc', $supabase_login_mode); ?>>
                                    <?php esc_html_e('OIDC Login (OpenID Connect redirect)', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose whether login uses the Supabase password flow or redirects to OpenID Connect.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_with_plugins"><?php esc_html_e('SupabaseWithPlugins (OIDC)', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_with_plugins" name="bw_supabase_with_plugins"
                                    value="1" <?php checked(1, $supabase_with_plugins); ?> />
                                <?php esc_html_e('Enable OIDC plugin integration', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, authentication is handled by OpenID Connect Generic Client (OIDC redirect flow). The frontend form keeps the same style, but password is not submitted to WordPress.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-oidc-warning" <?php echo ($supabase_with_plugins && 'native' === $supabase_login_mode) ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('OIDC login notice', 'bw'); ?></th>
                        <td>
                            <p class="description">
                                <?php esc_html_e('OIDC enabled but login is set to native email/password. OIDC will not hijack the login submit.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_registration_mode"><?php esc_html_e('Registration Mode', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_registration_mode" name="bw_supabase_registration_mode">
                                <option value="R1" <?php selected('R1', $supabase_registration); ?>>
                                    <?php esc_html_e('Redirect to Provider Signup (recommended)', 'bw'); ?>
                                </option>
                                <option value="R2" <?php selected('R2', $supabase_registration); ?>>
                                    <?php esc_html_e('Native Supabase Registration (email/password)', 'bw'); ?>
                                </option>
                                <option value="R3" <?php selected('R3', $supabase_registration); ?>>
                                    <?php esc_html_e('Disable Registration', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description"><strong><?php esc_html_e('R1 (Redirect):', 'bw'); ?></strong>
                                <?php esc_html_e('Register tab will show a CTA button that redirects to the Provider signup page. In OIDC mode, WordPress user is created after first successful login if the OIDC plugin is configured to create users.', 'bw'); ?>
                            </p>
                            <p class="description"><strong><?php esc_html_e('R2 (Native):', 'bw'); ?></strong>
                                <?php esc_html_e('Register tab will show the full Supabase email/password registration form and will create the Supabase user via Supabase Auth API. In OIDC mode, login remains OIDC; registration is still native via Supabase API.', 'bw'); ?>
                            </p>
                            <p class="description"><strong><?php esc_html_e('R3 (Disable):', 'bw'); ?></strong>
                                <?php esc_html_e('Register tab is hidden or disabled. Users can only log in. Use this if you manage accounts externally.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R1">
                        <th scope="row">
                            <label
                                for="bw_supabase_provider_signup_url"><?php esc_html_e('Provider Signup URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_supabase_provider_signup_url" name="bw_supabase_provider_signup_url"
                                value="<?php echo esc_attr($supabase_signup_url); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Where users create a new account (Supabase/Provider hosted signup).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-oidc-option" data-bw-oidc="1">
                        <th scope="row">
                            <label
                                for="bw_supabase_provider_reset_url"><?php esc_html_e('Provider Reset URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="bw_supabase_provider_reset_url" name="bw_supabase_provider_reset_url"
                                value="<?php echo esc_attr($supabase_reset_url); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Reset password page hosted by your provider (used in OIDC mode).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R2">
                        <th scope="row">
                            <label
                                for="bw_supabase_email_confirm_redirect_url"><?php esc_html_e('Email Confirm Redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_email_confirm_redirect_url"
                                name="bw_supabase_email_confirm_redirect_url"
                                value="<?php echo esc_attr($supabase_confirm_url); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('URL where Supabase redirects the user after email confirmation. Must be allowlisted in Supabase Redirect URLs.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R2">
                        <th scope="row">
                            <label
                                for="bw_supabase_auto_login_after_confirm"><?php esc_html_e('Auto-login after email confirmation', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_auto_login_after_confirm"
                                    name="bw_supabase_auto_login_after_confirm" value="1" <?php checked(1, $supabase_auto_login); ?> />
                                <?php esc_html_e('Attempt to log users into WordPress after Supabase email confirmation.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, the frontend bridges the #access_token fragment to WordPress via AJAX to create a WP session.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-option" data-bw-registration-mode="R2">
                        <th scope="row">
                            <label
                                for="bw_supabase_create_wp_users"><?php esc_html_e('Create WordPress user if missing', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_create_wp_users" name="bw_supabase_create_wp_users"
                                    value="1" <?php checked(1, $supabase_create_users); ?> />
                                <?php esc_html_e('Create a WordPress user automatically when Supabase confirms a new email.', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If enabled, create a WP user automatically when a Supabase-confirmed email does not exist in WordPress.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-registration-note">
                        <th scope="row"><?php esc_html_e('Email confirmation auto-login', 'bw'); ?></th>
                        <td>
                            <p class="description">
                                <?php esc_html_e('Email confirmation auto-login settings are only used for R2 Native Supabase Registration.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Magic link login', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_magic_link_enabled"
                                    name="bw_supabase_magic_link_enabled" value="1" <?php checked(1, $supabase_magic_link_enabled); ?> />
                                <?php esc_html_e('Enable magic link email login', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Uses Supabase /auth/v1/otp magic link. Users receive a sign-in link by email.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('OTP signup behavior', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_otp_allow_signup" name="bw_supabase_otp_allow_signup"
                                    value="1" <?php checked(1, $supabase_otp_allow_signup); ?> />
                                <?php esc_html_e('Allow OTP login to create Supabase users', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When disabled, OTP login only works for existing Supabase users and will show an error for unknown emails.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('OAuth login providers', 'bw'); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:8px;">
                                <input type="checkbox" id="bw_supabase_oauth_google_enabled"
                                    name="bw_supabase_oauth_google_enabled" value="1" <?php checked(1, $supabase_oauth_google_enabled); ?> />
                                <?php esc_html_e('Enable Google OAuth', 'bw'); ?>
                            </label>
                            <label style="display:block;">
                                <input type="checkbox" id="bw_supabase_oauth_facebook_enabled"
                                    name="bw_supabase_oauth_facebook_enabled" value="1" <?php checked(1, $supabase_oauth_facebook_enabled); ?> />
                                <?php esc_html_e('Enable Facebook OAuth', 'bw'); ?>
                            </label>
                            <label style="display:block; margin-top:8px;">
                                <input type="checkbox" id="bw_supabase_oauth_apple_enabled"
                                    name="bw_supabase_oauth_apple_enabled" value="1" <?php checked(1, $supabase_oauth_apple_enabled); ?> />
                                <?php esc_html_e('Enable Apple OAuth', 'bw'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="bw-supabase-google-option" <?php echo $supabase_oauth_google_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('Google settings', 'bw'); ?></th>
                        <td>
                            <div class="bw-provider-box bw-provider-box--google bw-settings-group">
                                <div class="bw-provider-box__title"><?php esc_html_e('Google settings', 'bw'); ?></div>
                                <div class="bw-provider-box__grid">
                                    <label
                                        for="bw_supabase_google_client_id"><?php esc_html_e('Client ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_client_id" name="bw_supabase_google_client_id"
                                        value="<?php echo esc_attr($supabase_google_client_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_supabase_google_client_secret"><?php esc_html_e('Client Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_client_secret"
                                        name="bw_supabase_google_client_secret"
                                        value="<?php echo esc_attr($supabase_google_client_secret); ?>"
                                        class="regular-text" />
                                    <label
                                        for="bw_supabase_google_redirect_url"><?php esc_html_e('Redirect URL', 'bw'); ?></label>
                                    <input type="url" id="bw_supabase_google_redirect_url"
                                        name="bw_supabase_google_redirect_url"
                                        value="<?php echo esc_attr($supabase_google_redirect_url); ?>"
                                        class="regular-text" />
                                    <label for="bw_supabase_google_scopes"><?php esc_html_e('Scopes', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_scopes" name="bw_supabase_google_scopes"
                                        value="<?php echo esc_attr($supabase_google_scopes); ?>" class="regular-text" />
                                    <label for="bw_supabase_google_prompt"><?php esc_html_e('Prompt', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_google_prompt" name="bw_supabase_google_prompt"
                                        value="<?php echo esc_attr($supabase_google_prompt); ?>" class="regular-text" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr class="bw-supabase-facebook-option" <?php echo $supabase_oauth_facebook_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('Facebook settings', 'bw'); ?></th>
                        <td>
                            <div class="bw-provider-box bw-provider-box--facebook bw-settings-group">
                                <div class="bw-provider-box__title"><?php esc_html_e('Facebook settings', 'bw'); ?></div>
                                <div class="bw-provider-box__grid">
                                    <label for="bw_supabase_facebook_app_id"><?php esc_html_e('App ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_facebook_app_id" name="bw_supabase_facebook_app_id"
                                        value="<?php echo esc_attr($supabase_facebook_app_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_supabase_facebook_app_secret"><?php esc_html_e('App Secret', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_facebook_app_secret"
                                        name="bw_supabase_facebook_app_secret"
                                        value="<?php echo esc_attr($supabase_facebook_app_secret); ?>"
                                        class="regular-text" />
                                    <label
                                        for="bw_supabase_facebook_redirect_url"><?php esc_html_e('Redirect URL', 'bw'); ?></label>
                                    <input type="url" id="bw_supabase_facebook_redirect_url"
                                        name="bw_supabase_facebook_redirect_url"
                                        value="<?php echo esc_attr($supabase_facebook_redirect_url); ?>"
                                        class="regular-text" />
                                    <label for="bw_supabase_facebook_scopes"><?php esc_html_e('Scopes', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_facebook_scopes" name="bw_supabase_facebook_scopes"
                                        value="<?php echo esc_attr($supabase_facebook_scopes); ?>" class="regular-text" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr class="bw-supabase-apple-option" <?php echo $supabase_oauth_apple_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row"><?php esc_html_e('Apple settings', 'bw'); ?></th>
                        <td>
                            <div class="bw-provider-box bw-provider-box--apple bw-settings-group">
                                <div class="bw-provider-box__title"><?php esc_html_e('Apple settings', 'bw'); ?></div>
                                <div class="bw-provider-box__grid">
                                    <label for="bw_supabase_apple_client_id"><?php esc_html_e('Client ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_apple_client_id" name="bw_supabase_apple_client_id"
                                        value="<?php echo esc_attr($supabase_apple_client_id); ?>" class="regular-text" />
                                    <label for="bw_supabase_apple_team_id"><?php esc_html_e('Team ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_apple_team_id" name="bw_supabase_apple_team_id"
                                        value="<?php echo esc_attr($supabase_apple_team_id); ?>" class="regular-text" />
                                    <label for="bw_supabase_apple_key_id"><?php esc_html_e('Key ID', 'bw'); ?></label>
                                    <input type="text" id="bw_supabase_apple_key_id" name="bw_supabase_apple_key_id"
                                        value="<?php echo esc_attr($supabase_apple_key_id); ?>" class="regular-text" />
                                    <label
                                        for="bw_supabase_apple_private_key"><?php esc_html_e('Private Key', 'bw'); ?></label>
                                    <textarea id="bw_supabase_apple_private_key" name="bw_supabase_apple_private_key"
                                        rows="4"
                                        class="large-text"><?php echo esc_textarea($supabase_apple_private_key); ?></textarea>
                                    <label
                                        for="bw_supabase_apple_redirect_url"><?php esc_html_e('Redirect URL', 'bw'); ?></label>
                                    <input type="url" id="bw_supabase_apple_redirect_url"
                                        name="bw_supabase_apple_redirect_url"
                                        value="<?php echo esc_attr($supabase_apple_redirect_url); ?>"
                                        class="regular-text" />
                                </div>
                                <p class="description">
                                    <?php esc_html_e('These fields are used to configure the provider and will be needed in Supabase Auth settings. Keep secrets private.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Password login', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_login_password_enabled"
                                    name="bw_supabase_login_password_enabled" value="1" <?php checked(1, $supabase_password_enabled); ?> />
                                <?php esc_html_e('Enable login with password button', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_magic_link_redirect_url"><?php esc_html_e('Magic link redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_magic_link_redirect_url"
                                name="bw_supabase_magic_link_redirect_url"
                                value="<?php echo esc_attr($supabase_magic_link_redirect); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Redirect after magic link login (must be allowlisted in Supabase).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_oauth_redirect_url"><?php esc_html_e('OAuth redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_oauth_redirect_url" name="bw_supabase_oauth_redirect_url"
                                value="<?php echo esc_attr($supabase_oauth_redirect); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Redirect after Google/Facebook OAuth (must be allowlisted in Supabase).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_signup_redirect_url"><?php esc_html_e('Signup confirmation redirect URL', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_signup_redirect_url" name="bw_supabase_signup_redirect_url"
                                value="<?php echo esc_attr($supabase_signup_redirect); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Redirect after confirming signup email (must be allowlisted in Supabase).', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <?php if ($supabase_with_plugins): ?>
                        <?php
                        $oidc_active = function_exists('bw_oidc_is_active') ? bw_oidc_is_active() : false;
                        $oidc_auth_url = function_exists('bw_oidc_get_auth_url') ? bw_oidc_get_auth_url() : '';
                        $oidc_redirect = function_exists('bw_oidc_get_redirect_uri') ? bw_oidc_get_redirect_uri() : '';
                        $oidc_provider = function_exists('bw_oidc_get_provider_base_url') ? bw_oidc_get_provider_base_url() : '';
                        ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('OIDC Integration Status', 'bw'); ?></th>
                            <td>
                                <p><strong><?php esc_html_e('Plugin active:', 'bw'); ?></strong>
                                    <?php echo $oidc_active ? esc_html__('Yes', 'bw') : esc_html__('No', 'bw'); ?></p>
                                <p><strong><?php esc_html_e('Redirect URI:', 'bw'); ?></strong>
                                    <?php echo $oidc_redirect ? esc_html($oidc_redirect) : esc_html__('Not available', 'bw'); ?>
                                </p>
                                <p><strong><?php esc_html_e('Auth URL:', 'bw'); ?></strong>
                                    <?php echo $oidc_auth_url ? esc_html($oidc_auth_url) : esc_html__('Not available', 'bw'); ?>
                                </p>
                                <?php if ($oidc_provider): ?>
                                    <p><strong><?php esc_html_e('Provider base URL:', 'bw'); ?></strong>
                                        <?php echo esc_html($oidc_provider); ?></p>
                                <?php endif; ?>
                                <?php if (!$oidc_active): ?>
                                    <div class="notice notice-warning inline">
                                        <p><?php esc_html_e('Install/activate OpenID Connect Generic Client and configure Client ID/Secret + endpoints.', 'bw'); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_jwt_cookie_name"><?php esc_html_e('Supabase JWT Cookie Name', 'bw'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bw_supabase_jwt_cookie_name" name="bw_supabase_jwt_cookie_name"
                                value="<?php echo esc_attr($supabase_cookie_name); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Base name used for access/refresh cookies when session storage is set to secure cookie.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_session_storage"><?php esc_html_e('Session Storage', 'bw'); ?></label>
                        </th>
                        <td>
                            <select id="bw_supabase_session_storage" name="bw_supabase_session_storage">
                                <option value="cookie" <?php selected('cookie', $supabase_storage); ?>>
                                    <?php esc_html_e('Secure cookie only', 'bw'); ?>
                                </option>
                                <option value="usermeta" <?php selected('usermeta', $supabase_storage); ?>>
                                    <?php esc_html_e('WP usermeta', 'bw'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose where Supabase session tokens are stored after login.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label
                                for="bw_supabase_enable_wp_user_linking"><?php esc_html_e('Link Supabase users to WP users', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_enable_wp_user_linking"
                                    name="bw_supabase_enable_wp_user_linking" value="1" <?php checked(1, $supabase_link_users); ?> />
                                <?php esc_html_e('Match existing WordPress users by email on Supabase login.', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bw_supabase_debug_log"><?php esc_html_e('Debug logging', 'bw'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bw_supabase_debug_log" name="bw_supabase_debug_log" value="1"
                                    <?php checked(1, $supabase_debug_log); ?> />
                                <?php esc_html_e('Log Supabase Auth status codes (never logs credentials).', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php submit_button('Salva impostazioni', 'primary', 'bw_account_page_submit'); ?>
    </form>

    <script>
        jQuery(document).ready(function($) {
            $('.bw-media-upload').on('click', function (e) {
                e.preventDefault();

                const targetInput = $(this).data('target');
                const targetIdInput = $(this).data('id-target');
                const frame = wp.media({
                    title: 'Seleziona immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });

                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url);
                    if (targetIdInput) {
                        $(targetIdInput).val(attachment.id);
                    }
                });

                frame.open();
            });

            $('#bw_account_login_image').on('input', function () {
                $('#bw_account_login_image_id').val('');
            });

            $('#bw_account_logo').on('input', function () {
                $('#bw_account_logo_id').val('');
            });

            var accountTabLinks = $('.bw-account-settings-tabs .nav-tab');
            var accountTabs = $('.bw-account-settings-tab');

            var providerRadios = $('input[name="bw_account_login_provider"]');
            var providerSections = $('.bw-login-provider-section');
            var loginCopyRows = $('.bw-login-copy-field');
            var registrationMode = $('#bw_supabase_registration_mode');
            var registrationRows = $('.bw-supabase-registration-option');
            var registrationNote = $('.bw-supabase-registration-note');
            var oidcRows = $('.bw-supabase-oidc-option');
            var oidcToggle = $('#bw_supabase_with_plugins');
            var loginMode = $('#bw_supabase_login_mode');
            var oidcWarning = $('.bw-supabase-oidc-warning');
            var appleToggle = $('#bw_supabase_oauth_apple_enabled');
            var appleRows = $('.bw-supabase-apple-option');
            var googleToggle = $('#bw_supabase_oauth_google_enabled');
            var facebookToggle = $('#bw_supabase_oauth_facebook_enabled');
            var googleRows = $('.bw-supabase-google-option');
            var facebookRows = $('.bw-supabase-facebook-option');
            var accountFacebookToggle = $('#bw_account_facebook');
            var accountGoogleToggle = $('#bw_account_google');
            var accountProviderRows = $('.bw-account-provider-option');

            var setAccountTab = function (tab) {
                accountTabLinks.each(function () {
                    var $link = $(this);
                    var linkTab = ($link.attr('href') || '').replace('#', '');
                    var isActive = linkTab === tab;
                    $link.toggleClass('nav-tab-active', isActive);
                    $link.attr('aria-selected', isActive ? 'true' : 'false');
                });

                accountTabs.each(function () {
                    var $tab = $(this);
                    var tabName = $tab.data('bw-account-tab');
                    $tab.toggle(tabName === tab);
                });
            };

            var toggleProviderSections = function (provider) {
                providerSections.each(function () {
                    var $section = $(this);
                    var sectionProvider = $section.data('bw-login-provider');
                    $section.toggle(sectionProvider === provider);
                });
            };

            var toggleLoginCopyFields = function (provider) {
                loginCopyRows.each(function () {
                    var $row = $(this);
                    var rowProvider = $row.data('bw-login-copy-provider');
                    $row.toggle(rowProvider === provider);
                });
            };

            var toggleRegistrationMode = function (mode) {
                registrationRows.each(function () {
                    var $row = $(this);
                    $row.toggle($row.data('bw-registration-mode') === mode);
                });
                if (registrationNote.length) {
                    registrationNote.toggle(mode !== 'R2');
                }
            };

            var toggleOidcRows = function (enabled) {
                oidcRows.toggle(!!enabled);
            };

            var toggleOidcWarning = function (enabled, mode) {
                if (!oidcWarning.length) {
                    return;
                }

                oidcWarning.toggle(!!enabled && mode === 'native');
            };

            var toggleAppleRows = function (enabled) {
                appleRows.toggle(!!enabled);
            };

            var toggleGoogleRows = function (enabled) {
                googleRows.toggle(!!enabled);
            };

            var toggleFacebookRows = function (enabled) {
                facebookRows.toggle(!!enabled);
            };

            var toggleAccountProviderRows = function (provider, enabled) {
                accountProviderRows.filter('[data-bw-account-provider="' + provider + '"]').toggle(!!enabled);
            };

            var initialAccountTab = window.location.hash ? window.location.hash.replace('#', '') : 'design';
            if(initialAccountTab !== 'technical' && initialAccountTab !== 'design') {
            initialAccountTab = 'design';
        }
        setAccountTab(initialAccountTab);

        toggleProviderSections(providerRadios.filter(':checked').val() || 'wordpress');
        toggleLoginCopyFields(providerRadios.filter(':checked').val() || 'wordpress');
        toggleRegistrationMode(registrationMode.val());
        toggleOidcRows(oidcToggle.is(':checked'));
        toggleOidcWarning(oidcToggle.is(':checked'), loginMode.val());
        toggleAppleRows(appleToggle.is(':checked'));
        toggleGoogleRows(googleToggle.is(':checked'));
        toggleFacebookRows(facebookToggle.is(':checked'));
        toggleAccountProviderRows('facebook', accountFacebookToggle.is(':checked'));
        toggleAccountProviderRows('google', accountGoogleToggle.is(':checked'));

        accountTabLinks.on('click', function (event) {
            event.preventDefault();
            var tab = ($(this).attr('href') || '').replace('#', '');
            if (!tab) {
                return;
            }
            setAccountTab(tab);
            if (history.replaceState) {
                history.replaceState(null, document.title, '#' + tab);
            } else {
                window.location.hash = tab;
            }
        });

        $(window).on('hashchange', function () {
            var tab = window.location.hash ? window.location.hash.replace('#', '') : 'design';
            if (tab !== 'technical' && tab !== 'design') {
                tab = 'design';
            }
            setAccountTab(tab);
        });

        providerRadios.on('change', function () {
            var provider = $(this).val();
            toggleProviderSections(provider);
            toggleLoginCopyFields(provider);
        });

        registrationMode.on('change', function () {
            toggleRegistrationMode($(this).val());
        });

        oidcToggle.on('change', function () {
            var enabled = $(this).is(':checked');
            toggleOidcRows(enabled);
            toggleOidcWarning(enabled, loginMode.val());
        });

        loginMode.on('change', function () {
            toggleOidcWarning(oidcToggle.is(':checked'), $(this).val());
        });

        appleToggle.on('change', function () {
            toggleAppleRows($(this).is(':checked'));
        });

        googleToggle.on('change', function () {
            toggleGoogleRows($(this).is(':checked'));
        });

        facebookToggle.on('change', function () {
            toggleFacebookRows($(this).is(':checked'));
        });

        accountFacebookToggle.on('change', function () {
            toggleAccountProviderRows('facebook', $(this).is(':checked'));
        });

        accountGoogleToggle.on('change', function () {
            toggleAccountProviderRows('google', $(this).is(':checked'));
        });
                                                                                        });
    </script>
    <?php
}

/**
 * Render the My Account front-end customization tab.
 */
function bw_site_render_my_account_front_tab()
{
    $saved = false;

    if (isset($_POST['bw_myaccount_content_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_myaccount_front_save', 'bw_myaccount_front_nonce');

        $black_box_text = isset($_POST['bw_myaccount_black_box_text'])
            ? wp_kses_post(wp_unslash($_POST['bw_myaccount_black_box_text']))
            : '';
        $support_link = isset($_POST['bw_myaccount_support_link'])
            ? esc_url_raw(wp_unslash($_POST['bw_myaccount_support_link']))
            : '';

        update_option('bw_myaccount_black_box_text', $black_box_text);
        update_option('bw_myaccount_support_link', $support_link);

        $saved = true;
    }

    $black_box_text = get_option(
        'bw_myaccount_black_box_text',
        __('Your mockups will always be here, available to download. Please enjoy them!', 'bw')
    );
    $support_link = get_option('bw_myaccount_support_link', '');
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php esc_html_e('Impostazioni salvate con successo!', 'bw'); ?></strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_myaccount_front_save', 'bw_myaccount_front_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label
                        for="bw_myaccount_black_box_text"><?php esc_html_e('Testo Box Nero (My Account)', 'bw'); ?></label>
                </th>
                <td>
                    <textarea id="bw_myaccount_black_box_text" name="bw_myaccount_black_box_text" rows="6"
                        class="large-text"><?php echo esc_textarea($black_box_text); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Contenuto mostrato nel box nero in alto alla dashboard My Account. Puoi utilizzare HTML semplice; il testo verrà sanificato.', 'bw'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_myaccount_support_link"><?php esc_html_e('Link Support (My Account)', 'bw'); ?></label>
                </th>
                <td>
                    <input id="bw_myaccount_support_link" name="bw_myaccount_support_link" type="url"
                        class="regular-text" value="<?php echo esc_attr($support_link); ?>" placeholder="https://blackwork.pro/support/" />
                    <p class="description">
                        <?php esc_html_e('URL del pulsante "Contact support" nel box nero della dashboard My Account.', 'bw'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Salva impostazioni', 'bw'), 'primary', 'bw_myaccount_content_submit'); ?>
    </form>
    <?php
}

/**
 * Render the Checkout customization tab.
 */
function bw_site_render_checkout_tab()
{
    $saved = false;

    if (isset($_POST['bw_checkout_settings_submit']) || isset($_POST['bw_checkout_footer_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_checkout_settings_save', 'bw_checkout_settings_nonce');

        $logo = isset($_POST['bw_checkout_logo']) ? esc_url_raw(wp_unslash($_POST['bw_checkout_logo'])) : '';
        $logo_align = isset($_POST['bw_checkout_logo_align']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_logo_align'])) : 'left';
        $logo_width = isset($_POST['bw_checkout_logo_width']) ? absint($_POST['bw_checkout_logo_width']) : 200;
        $logo_padding_top = isset($_POST['bw_checkout_logo_padding_top']) ? absint($_POST['bw_checkout_logo_padding_top']) : 0;
        $logo_padding_right = isset($_POST['bw_checkout_logo_padding_right']) ? absint($_POST['bw_checkout_logo_padding_right']) : 0;
        $logo_padding_bottom = isset($_POST['bw_checkout_logo_padding_bottom']) ? absint($_POST['bw_checkout_logo_padding_bottom']) : 30;
        $logo_padding_left = isset($_POST['bw_checkout_logo_padding_left']) ? absint($_POST['bw_checkout_logo_padding_left']) : 0;
        $show_order_heading = isset($_POST['bw_checkout_show_order_heading']) ? '1' : '0';
        $page_bg = isset($_POST['bw_checkout_page_bg']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_page_bg'])) : '';
        $grid_bg = isset($_POST['bw_checkout_grid_bg']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_grid_bg'])) : '';
        $left_bg = isset($_POST['bw_checkout_left_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_left_bg_color'])) : '';
        $right_bg = isset($_POST['bw_checkout_right_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_right_bg_color'])) : '';
        $right_sticky_top = isset($_POST['bw_checkout_right_sticky_top']) ? absint($_POST['bw_checkout_right_sticky_top']) : 20;
        $right_margin_top = isset($_POST['bw_checkout_right_margin_top']) ? absint($_POST['bw_checkout_right_margin_top']) : 0;
        $right_padding_top = isset($_POST['bw_checkout_right_padding_top']) ? absint($_POST['bw_checkout_right_padding_top']) : 0;
        $right_padding_right = isset($_POST['bw_checkout_right_padding_right']) ? absint($_POST['bw_checkout_right_padding_right']) : 0;
        $right_padding_bottom = isset($_POST['bw_checkout_right_padding_bottom']) ? absint($_POST['bw_checkout_right_padding_bottom']) : 0;
        $right_padding_left = isset($_POST['bw_checkout_right_padding_left']) ? absint($_POST['bw_checkout_right_padding_left']) : 28;
        $border_color = isset($_POST['bw_checkout_border_color']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_border_color'])) : '';
        $legal_text = isset($_POST['bw_checkout_legal_text']) ? wp_kses_post(wp_unslash($_POST['bw_checkout_legal_text'])) : '';
        $footer_copyright = isset($_POST['bw_checkout_footer_copyright_text']) ? wp_kses_post(wp_unslash($_POST['bw_checkout_footer_copyright_text'])) : '';
        $show_footer_copyright = isset($_POST['bw_checkout_show_footer_copyright']) ? '1' : '0';
        $show_return_to_shop = isset($_POST['bw_checkout_show_return_to_shop']) ? '1' : '0';
        $left_width_percent = isset($_POST['bw_checkout_left_width']) ? absint($_POST['bw_checkout_left_width']) : 62;
        $right_width_percent = isset($_POST['bw_checkout_right_width']) ? absint($_POST['bw_checkout_right_width']) : 38;
        $thumb_ratio = isset($_POST['bw_checkout_thumb_ratio']) ? sanitize_key(wp_unslash($_POST['bw_checkout_thumb_ratio'])) : 'square';
        $thumb_width = isset($_POST['bw_checkout_thumb_width']) ? absint($_POST['bw_checkout_thumb_width']) : 110;
        $footer_text = isset($_POST['bw_checkout_footer_text']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_footer_text'])) : '';
        $supabase_provision_enabled = isset($_POST['bw_supabase_checkout_provision_enabled']) ? '1' : '0';
        $supabase_invite_redirect = isset($_POST['bw_supabase_invite_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_invite_redirect_url'])) : '';
        $supabase_expired_link_redirect = isset($_POST['bw_supabase_expired_link_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_expired_link_redirect_url'])) : '';
        $google_pay_enabled = isset($_POST['bw_google_pay_enabled']) ? 1 : 0;
        $google_pay_test_mode = isset($_POST['bw_google_pay_test_mode']) ? 1 : 0;
        $google_pay_pub_key = isset($_POST['bw_google_pay_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_publishable_key'])) : '';
        $google_pay_sec_key = isset($_POST['bw_google_pay_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_secret_key'])) : '';
        $google_pay_test_pub_key = isset($_POST['bw_google_pay_test_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_test_publishable_key'])) : '';
        $google_pay_test_sec_key = isset($_POST['bw_google_pay_test_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_test_secret_key'])) : '';
        $google_pay_statement_descriptor = isset($_POST['bw_google_pay_statement_descriptor']) ? substr(sanitize_text_field(wp_unslash($_POST['bw_google_pay_statement_descriptor'])), 0, 22) : '';
        $google_pay_webhook_secret = isset($_POST['bw_google_pay_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_webhook_secret'])) : '';
        $google_pay_test_webhook_secret = isset($_POST['bw_google_pay_test_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_test_webhook_secret'])) : '';
        $klarna_enabled = isset($_POST['bw_klarna_enabled']) ? 1 : 0;
        $klarna_pub_key = isset($_POST['bw_klarna_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_klarna_publishable_key'])) : '';
        $klarna_sec_key = isset($_POST['bw_klarna_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_klarna_secret_key'])) : '';
        $klarna_statement_descriptor = isset($_POST['bw_klarna_statement_descriptor']) ? substr(sanitize_text_field(wp_unslash($_POST['bw_klarna_statement_descriptor'])), 0, 22) : '';
        $klarna_webhook_secret = isset($_POST['bw_klarna_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_klarna_webhook_secret'])) : '';
        $apple_pay_enabled = isset($_POST['bw_apple_pay_enabled']) ? 1 : 0;
        $apple_pay_express_helper_enabled = isset($_POST['bw_apple_pay_express_helper_enabled']) ? 1 : 0;
        $apple_pay_pub_key = isset($_POST['bw_apple_pay_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_apple_pay_publishable_key'])) : '';
        $apple_pay_sec_key = isset($_POST['bw_apple_pay_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_apple_pay_secret_key'])) : '';
        $apple_pay_statement_descriptor = isset($_POST['bw_apple_pay_statement_descriptor']) ? substr(sanitize_text_field(wp_unslash($_POST['bw_apple_pay_statement_descriptor'])), 0, 22) : '';
        $apple_pay_webhook_secret = isset($_POST['bw_apple_pay_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_apple_pay_webhook_secret'])) : '';

        // Policy Settings
        $policies = [
            'refund' => 'bw_checkout_policy_refund',
            'shipping' => 'bw_checkout_policy_shipping',
            'privacy' => 'bw_checkout_policy_privacy',
            'terms' => 'bw_checkout_policy_terms',
            'contact' => 'bw_checkout_policy_contact'
        ];

        foreach ($policies as $key => $option_prefix) {
            $policy_data = isset($_POST[$option_prefix]) ? wp_unslash($_POST[$option_prefix]) : [];
            if (!is_array($policy_data)) {
                $policy_data = [];
            }
            $sanitized_data = [
                'enabled' => isset($policy_data['enabled']) ? '1' : '0',
                'title' => isset($policy_data['title']) ? sanitize_text_field($policy_data['title']) : '',
                'subtitle' => isset($policy_data['subtitle']) ? sanitize_text_field($policy_data['subtitle']) : '',
                'content' => isset($policy_data['content']) ? wp_kses_post($policy_data['content']) : '',
            ];
            update_option($option_prefix, $sanitized_data);
        }

        // Google Maps settings
        $google_maps_enabled = isset($_POST['bw_google_maps_enabled']) ? '1' : '0';
        $google_maps_api_key = isset($_POST['bw_google_maps_api_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_maps_api_key'])) : '';
        $google_maps_autofill = isset($_POST['bw_google_maps_autofill']) ? '1' : '0';
        $google_maps_restrict_country = isset($_POST['bw_google_maps_restrict_country']) ? '1' : '0';

        if (!in_array($thumb_ratio, ['square', 'portrait', 'landscape'], true)) {
            $thumb_ratio = 'square';
        }

        // Ensure thumb_width is within reasonable bounds
        if ($thumb_width < 50) {
            $thumb_width = 50;
        }
        if ($thumb_width > 300) {
            $thumb_width = 300;
        }

        $page_bg = $page_bg ?: '#ffffff';
        $grid_bg = $grid_bg ?: '#ffffff';
        $left_bg = $left_bg ?: '#ffffff';
        $right_bg = $right_bg ?: 'transparent';
        $border_color = $border_color ?: '#262626';

        if (!in_array($logo_align, ['left', 'center', 'right'], true)) {
            $logo_align = 'left';
        }

        if (function_exists('bw_mew_normalize_checkout_column_widths')) {
            $widths = bw_mew_normalize_checkout_column_widths($left_width_percent, $right_width_percent);
            $left_width_percent = $widths['left'];
            $right_width_percent = $widths['right'];
        }

        update_option('bw_checkout_logo', $logo);
        update_option('bw_checkout_logo_align', $logo_align);
        update_option('bw_checkout_logo_width', $logo_width);
        update_option('bw_checkout_logo_padding_top', $logo_padding_top);
        update_option('bw_checkout_logo_padding_right', $logo_padding_right);
        update_option('bw_checkout_logo_padding_bottom', $logo_padding_bottom);
        update_option('bw_checkout_logo_padding_left', $logo_padding_left);
        update_option('bw_checkout_show_order_heading', $show_order_heading);
        update_option('bw_checkout_page_bg', $page_bg);
        update_option('bw_checkout_grid_bg', $grid_bg);
        update_option('bw_checkout_left_bg_color', $left_bg);
        update_option('bw_checkout_right_bg_color', $right_bg);
        update_option('bw_checkout_right_sticky_top', $right_sticky_top);
        update_option('bw_checkout_right_margin_top', $right_margin_top);
        update_option('bw_checkout_right_padding_top', $right_padding_top);
        update_option('bw_checkout_right_padding_right', $right_padding_right);
        update_option('bw_checkout_right_padding_bottom', $right_padding_bottom);
        update_option('bw_checkout_right_padding_left', $right_padding_left);
        update_option('bw_checkout_border_color', $border_color);
        update_option('bw_checkout_legal_text', $legal_text);
        update_option('bw_checkout_footer_copyright_text', $footer_copyright);
        update_option('bw_checkout_show_footer_copyright', $show_footer_copyright);
        update_option('bw_checkout_show_return_to_shop', $show_return_to_shop);
        update_option('bw_checkout_left_width', $left_width_percent);
        update_option('bw_checkout_right_width', $right_width_percent);
        update_option('bw_checkout_thumb_ratio', $thumb_ratio);
        update_option('bw_checkout_thumb_width', $thumb_width);
        update_option('bw_checkout_footer_text', $footer_text);
        update_option('bw_supabase_checkout_provision_enabled', $supabase_provision_enabled);
        update_option('bw_supabase_invite_redirect_url', $supabase_invite_redirect);
        update_option('bw_supabase_expired_link_redirect_url', $supabase_expired_link_redirect);
        update_option('bw_google_pay_enabled', $google_pay_enabled);
        update_option('bw_google_pay_test_mode', $google_pay_test_mode);
        update_option('bw_google_pay_publishable_key', $google_pay_pub_key);
        update_option('bw_google_pay_secret_key', $google_pay_sec_key);
        update_option('bw_google_pay_test_publishable_key', $google_pay_test_pub_key);
        update_option('bw_google_pay_test_secret_key', $google_pay_test_sec_key);
        update_option('bw_google_pay_statement_descriptor', $google_pay_statement_descriptor);
        update_option('bw_google_pay_webhook_secret', $google_pay_webhook_secret);
        update_option('bw_google_pay_test_webhook_secret', $google_pay_test_webhook_secret);
        update_option('bw_klarna_enabled', $klarna_enabled);
        update_option('bw_klarna_publishable_key', $klarna_pub_key);
        update_option('bw_klarna_secret_key', $klarna_sec_key);
        update_option('bw_klarna_statement_descriptor', $klarna_statement_descriptor);
        update_option('bw_klarna_webhook_secret', $klarna_webhook_secret);
        update_option('bw_apple_pay_enabled', $apple_pay_enabled);
        update_option('bw_apple_pay_express_helper_enabled', $apple_pay_express_helper_enabled);
        update_option('bw_apple_pay_publishable_key', $apple_pay_pub_key);
        update_option('bw_apple_pay_secret_key', $apple_pay_sec_key);
        update_option('bw_apple_pay_statement_descriptor', $apple_pay_statement_descriptor);
        update_option('bw_apple_pay_webhook_secret', $apple_pay_webhook_secret);

        // Save Google Maps settings
        update_option('bw_google_maps_enabled', $google_maps_enabled);
        update_option('bw_google_maps_api_key', $google_maps_api_key);
        update_option('bw_google_maps_autofill', $google_maps_autofill);
        update_option('bw_google_maps_restrict_country', $google_maps_restrict_country);

        // Section Headings settings
        $hide_billing_heading_val = isset($_POST['bw_checkout_hide_billing_heading']) ? '1' : '0';
        $hide_additional_heading_val = isset($_POST['bw_checkout_hide_additional_heading']) ? '1' : '0';
        $address_heading_label_val = isset($_POST['bw_checkout_address_heading_label']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_address_heading_label'])) : '';
        $free_order_message_val = isset($_POST['bw_checkout_free_order_message']) ? sanitize_textarea_field(wp_unslash($_POST['bw_checkout_free_order_message'])) : '';
        $free_order_button_text_val = isset($_POST['bw_checkout_free_order_button_text']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_free_order_button_text'])) : '';

        update_option('bw_checkout_hide_billing_heading', $hide_billing_heading_val);
        update_option('bw_checkout_hide_additional_heading', $hide_additional_heading_val);
        update_option('bw_checkout_address_heading_label', $address_heading_label_val);
        update_option('bw_checkout_free_order_message', $free_order_message_val);
        update_option('bw_checkout_free_order_button_text', $free_order_button_text_val);

        // Keep Checkout Fields section heading settings in sync with Style tab settings.
        $checkout_fields_settings = get_option('bw_checkout_fields_settings', ['version' => 1]);
        if (!is_array($checkout_fields_settings)) {
            $checkout_fields_settings = ['version' => 1];
        }
        if (empty($checkout_fields_settings['version'])) {
            $checkout_fields_settings['version'] = 1;
        }
        $checkout_fields_settings['section_headings'] = [
            'hide_billing_details' => '1' === $hide_billing_heading_val ? 1 : 0,
            'hide_additional_info' => '1' === $hide_additional_heading_val ? 1 : 0,
            'address_heading_text' => '' !== $address_heading_label_val ? $address_heading_label_val : __('Delivery', 'bw'),
        ];
        update_option('bw_checkout_fields_settings', $checkout_fields_settings);

        // Redirect to the same tab to prevent losing tab state.
        $allowed_checkout_tabs = ['style', 'supabase', 'fields', 'subscribe', 'google-maps', 'google-pay', 'klarna-pay', 'apple-pay', 'footer'];
        $checkout_tab = isset($_GET['checkout_tab']) ? sanitize_key(wp_unslash($_GET['checkout_tab'])) : 'style';
        if (!in_array($checkout_tab, $allowed_checkout_tabs, true)) {
            $checkout_tab = 'style';
        }
        wp_safe_redirect(add_query_arg(array(
            'page' => 'blackwork-site-settings',
            'tab' => 'checkout',
            'checkout_tab' => $checkout_tab,
            'saved' => '1'
        ), admin_url('admin.php')));
        exit;
    }

    $saved = isset($_GET['saved']) && '1' === sanitize_key(wp_unslash($_GET['saved']));

    $logo = get_option('bw_checkout_logo', '');
    $logo_align = get_option('bw_checkout_logo_align', 'left');
    if (!in_array($logo_align, ['left', 'center', 'right'], true)) {
        $logo_align = 'left';
    }
    $logo_width = get_option('bw_checkout_logo_width', 200);
    $logo_padding_top = get_option('bw_checkout_logo_padding_top', 0);
    $logo_padding_right = get_option('bw_checkout_logo_padding_right', 0);
    $logo_padding_bottom = get_option('bw_checkout_logo_padding_bottom', 30);
    $logo_padding_left = get_option('bw_checkout_logo_padding_left', 0);
    $show_order_heading = get_option('bw_checkout_show_order_heading', '1');
    $page_bg = get_option('bw_checkout_page_bg', get_option('bw_checkout_page_bg_color', '#ffffff'));
    $grid_bg = get_option('bw_checkout_grid_bg', get_option('bw_checkout_grid_bg_color', '#ffffff'));
    $left_bg = get_option('bw_checkout_left_bg_color', '#ffffff');
    $right_bg = get_option('bw_checkout_right_bg_color', 'transparent');
    $right_sticky_top = get_option('bw_checkout_right_sticky_top', 20);
    $right_margin_top = get_option('bw_checkout_right_margin_top', 0);
    $right_padding_top = get_option('bw_checkout_right_padding_top', 0);
    $right_padding_right = get_option('bw_checkout_right_padding_right', 0);
    $right_padding_bottom = get_option('bw_checkout_right_padding_bottom', 0);
    $right_padding_left = get_option('bw_checkout_right_padding_left', 28);
    $footer_copyright = get_option('bw_checkout_footer_copyright_text', '');
    $show_footer_copyright = get_option('bw_checkout_show_footer_copyright', '1');
    $show_return_to_shop = get_option('bw_checkout_show_return_to_shop', '1');
    $border_color = get_option('bw_checkout_border_color', '#262626');
    $legal_text = get_option('bw_checkout_legal_text', '');
    $left_width_percent = get_option('bw_checkout_left_width', 62);
    $right_width_percent = get_option('bw_checkout_right_width', 38);
    $thumb_ratio = get_option('bw_checkout_thumb_ratio', 'square');
    $thumb_width = get_option('bw_checkout_thumb_width', 110);
    $footer_text = get_option('bw_checkout_footer_text', '');
    $supabase_provision_enabled = get_option('bw_supabase_checkout_provision_enabled', '0');
    $supabase_invite_redirect = get_option('bw_supabase_invite_redirect_url', '');
    $supabase_expired_link_redirect = get_option('bw_supabase_expired_link_redirect_url', '');
    $supabase_service_key = get_option('bw_supabase_service_role_key', '');
    $default_invite_redirect = home_url('/my-account/set-password/');
    $supabase_invite_redirect = $supabase_invite_redirect ? $supabase_invite_redirect : $default_invite_redirect;
    $default_expired_link_redirect = home_url('/link-expired/');
    $supabase_expired_link_redirect = $supabase_expired_link_redirect ? $supabase_expired_link_redirect : $default_expired_link_redirect;

    // Section Headings settings (fallback to checkout fields settings to keep a single source of truth).
    $checkout_fields_settings = get_option('bw_checkout_fields_settings', []);
    $checkout_section_headings = (is_array($checkout_fields_settings) && isset($checkout_fields_settings['section_headings']) && is_array($checkout_fields_settings['section_headings']))
        ? $checkout_fields_settings['section_headings']
        : [];
    $hide_billing_heading = get_option('bw_checkout_hide_billing_heading', !empty($checkout_section_headings['hide_billing_details']) ? '1' : '0');
    $hide_additional_heading = get_option('bw_checkout_hide_additional_heading', !empty($checkout_section_headings['hide_additional_info']) ? '1' : '0');
    $address_heading_label = get_option('bw_checkout_address_heading_label', isset($checkout_section_headings['address_heading_text']) ? sanitize_text_field($checkout_section_headings['address_heading_text']) : '');
    $free_order_message = get_option('bw_checkout_free_order_message', '');
    $free_order_button_text = get_option('bw_checkout_free_order_button_text', '');

    // Get Policy Data
    $policy_names = ['refund', 'shipping', 'privacy', 'terms', 'contact'];
    $policy_settings = [];
    foreach ($policy_names as $name) {
        $policy_settings[$name] = get_option("bw_checkout_policy_{$name}", [
            'enabled' => '1',
            'title' => '',
            'subtitle' => '',
            'content' => '',
        ]);
    }
    ?>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_checkout_settings_save', 'bw_checkout_settings_nonce'); ?>

        <?php
        $active_checkout_tab = isset($_GET['checkout_tab']) ? sanitize_key(wp_unslash($_GET['checkout_tab'])) : 'style';
        $allowed_checkout_tabs = ['style', 'supabase', 'fields', 'subscribe', 'google-maps', 'google-pay', 'klarna-pay', 'apple-pay', 'footer'];
        if (!in_array($active_checkout_tab, $allowed_checkout_tabs, true)) {
            $active_checkout_tab = 'style';
        }

        $style_tab_url = add_query_arg('checkout_tab', 'style');
        $supabase_tab_url = add_query_arg('checkout_tab', 'supabase');
        $fields_tab_url = add_query_arg('checkout_tab', 'fields');
        $google_maps_tab_url = add_query_arg('checkout_tab', 'google-maps');
        $google_pay_tab_url = add_query_arg('checkout_tab', 'google-pay');
        $klarna_pay_tab_url = add_query_arg('checkout_tab', 'klarna-pay');
        $apple_pay_tab_url = add_query_arg('checkout_tab', 'apple-pay');
        $footer_tab_url = add_query_arg('checkout_tab', 'footer');
        ?>

        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo 'style' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($style_tab_url); ?>">
                <?php esc_html_e('Style', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'supabase' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($supabase_tab_url); ?>">
                <?php esc_html_e('Supabase Provider', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'fields' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($fields_tab_url); ?>">
                <?php esc_html_e('Checkout Fields', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'google-maps' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($google_maps_tab_url); ?>">
                <?php esc_html_e('Google Maps', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'google-pay' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($google_pay_tab_url); ?>">
                <?php esc_html_e('Google Pay', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'klarna-pay' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($klarna_pay_tab_url); ?>">
                <?php esc_html_e('Klarna Pay', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'apple-pay' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($apple_pay_tab_url); ?>">
                <?php esc_html_e('Apple Pay', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'footer' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($footer_tab_url); ?>">
                <?php esc_html_e('Footer Cleanup', 'bw'); ?>
            </a>
        </h2>

        <div class="bw-tab-panel" data-bw-tab="style" <?php echo 'style' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_logo">Logo Checkout</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_logo" name="bw_checkout_logo"
                            value="<?php echo esc_attr($logo); ?>" class="regular-text" />
                        <button type="button" class="button bw-media-upload" data-target="#bw_checkout_logo">Seleziona
                            immagine</button>
                        <p class="description">Logo mostrato sopra il layout di checkout.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_logo_align">Checkout Logo Alignment</label>
                    </th>
                    <td>
                        <select id="bw_checkout_logo_align" name="bw_checkout_logo_align">
                            <option value="left" <?php selected($logo_align, 'left'); ?>>Left</option>
                            <option value="center" <?php selected($logo_align, 'center'); ?>>Center</option>
                            <option value="right" <?php selected($logo_align, 'right'); ?>>Right</option>
                        </select>
                        <p class="description">Posizione orizzontale del logo nel checkout.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Larghezza Logo</label>
                    </th>
                    <td>
                        <input type="number" name="bw_checkout_logo_width" value="<?php echo esc_attr($logo_width); ?>"
                            min="50" max="800" style="width: 100px;" /> px
                        <p class="description">Larghezza massima del logo (default: 200px).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Padding Logo</label>
                    </th>
                    <td>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Top: <input type="number" name="bw_checkout_logo_padding_top"
                                    value="<?php echo esc_attr($logo_padding_top); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Right: <input type="number" name="bw_checkout_logo_padding_right"
                                    value="<?php echo esc_attr($logo_padding_right); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Bottom: <input type="number" name="bw_checkout_logo_padding_bottom"
                                    value="<?php echo esc_attr($logo_padding_bottom); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Left: <input type="number" name="bw_checkout_logo_padding_left"
                                    value="<?php echo esc_attr($logo_padding_left); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                        </div>
                        <p class="description">Spazi intorno al logo (Top, Right, Bottom, Left).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_show_order_heading">Mostra titolo "Your order"</label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_checkout_show_order_heading" name="bw_checkout_show_order_heading"
                                value="1" <?php checked($show_order_heading, '1'); ?> />
                            <span style="font-weight: 500;">Attiva</span>
                        </label>
                        <p class="description">Mostra o nascondi il titolo "Your order" nella colonna destra.</p>
                    </td>
                </tr>
                <tr class="bw-section-break">
                    <th scope="row" colspan="2" style="padding-bottom:0;">
                        <h3 style="margin:0;">Colori di sfondo checkout</h3>
                        <p class="description" style="margin-top:6px;">Gestisci il colore della pagina e del contenitore
                            griglia per evitare stacchi visivi tra le colonne.</p>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_page_bg">Checkout Page Background</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_page_bg" name="bw_checkout_page_bg"
                            value="<?php echo esc_attr($page_bg); ?>" class="bw-color-picker"
                            data-default-color="#ffffff" />
                        <p class="description">Colore di sfondo della pagina checkout (body/wrapper).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_grid_bg">Checkout Grid Background</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_grid_bg" name="bw_checkout_grid_bg"
                            value="<?php echo esc_attr($grid_bg); ?>" class="bw-color-picker"
                            data-default-color="#ffffff" />
                        <p class="description">Colore di sfondo del contenitore griglia checkout (.bw-checkout-grid).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_left_bg_color">Background colonna sinistra</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_left_bg_color" name="bw_checkout_left_bg_color"
                            value="<?php echo esc_attr($left_bg); ?>" class="bw-color-picker"
                            data-default-color="#ffffff" />
                        <p class="description">Colore di sfondo della colonna principale con i campi checkout.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_bg_color">Background colonna destra (riepilogo)</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_right_bg_color" name="bw_checkout_right_bg_color"
                            value="<?php echo esc_attr($right_bg); ?>" class="bw-color-picker"
                            data-default-color="transparent" />
                        <p class="description">Colore di sfondo del riepilogo ordine sticky.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_sticky_top">Right Column Sticky Offset Top (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_right_sticky_top" name="bw_checkout_right_sticky_top"
                            value="<?php echo esc_attr(absint($right_sticky_top)); ?>" min="0" step="1"
                            style="width: 90px;" />
                        <p class="description">Distance from top when column becomes sticky during scroll.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_margin_top">Right Column Margin Top (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_right_margin_top" name="bw_checkout_right_margin_top"
                            value="<?php echo esc_attr(absint($right_margin_top)); ?>" min="0" step="1"
                            style="width: 90px;" />
                        <p class="description">Initial top margin to align the column with the form (e.g., 150px to lower
                            it).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_left_width">Larghezza colonna sinistra (%)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_left_width" name="bw_checkout_left_width"
                            value="<?php echo esc_attr($left_width_percent); ?>" min="10" max="90" step="1"
                            style="width: 90px;" />
                        <p class="description">Percentuale dedicata al form (default 62%).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_width">Larghezza colonna destra (%)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_right_width" name="bw_checkout_right_width"
                            value="<?php echo esc_attr($right_width_percent); ?>" min="10" max="90" step="1"
                            style="width: 90px;" />
                        <p class="description">Percentuale dedicata al riepilogo (default 38%). Se la somma supera il 100%,
                            verrà bilanciata automaticamente.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Padding colonna destra (px)</th>
                    <td>
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <label for="bw_checkout_right_padding_top"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Top</span>
                                <input type="number" id="bw_checkout_right_padding_top" name="bw_checkout_right_padding_top"
                                    value="<?php echo esc_attr($right_padding_top); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                            <label for="bw_checkout_right_padding_right"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Right</span>
                                <input type="number" id="bw_checkout_right_padding_right"
                                    name="bw_checkout_right_padding_right"
                                    value="<?php echo esc_attr($right_padding_right); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                            <label for="bw_checkout_right_padding_bottom"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Bottom</span>
                                <input type="number" id="bw_checkout_right_padding_bottom"
                                    name="bw_checkout_right_padding_bottom"
                                    value="<?php echo esc_attr($right_padding_bottom); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                            <label for="bw_checkout_right_padding_left"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Left</span>
                                <input type="number" id="bw_checkout_right_padding_left"
                                    name="bw_checkout_right_padding_left"
                                    value="<?php echo esc_attr($right_padding_left); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                        </div>
                        <p class="description">Imposta il padding della colonna destra (riepilogo ordine) su desktop e
                            mobile.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_thumb_ratio">Order Item Thumbnail Format (Nails)</label>
                    </th>
                    <td>
                        <select id="bw_checkout_thumb_ratio" name="bw_checkout_thumb_ratio">
                            <option value="square" <?php selected($thumb_ratio, 'square'); ?>>Square (1:1)</option>
                            <option value="portrait" <?php selected($thumb_ratio, 'portrait'); ?>>Portrait (2:3)</option>
                            <option value="landscape" <?php selected($thumb_ratio, 'landscape'); ?>>Landscape (3:2)
                            </option>
                        </select>
                        <p class="description">Formato proporzioni miniature prodotto nel riepilogo ordine (consigliato per
                            immagini "nails").</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_thumb_width">Tab Nails Width (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_thumb_width" name="bw_checkout_thumb_width"
                            value="<?php echo esc_attr($thumb_width); ?>" min="50" max="300" step="1"
                            style="width: 90px;" />
                        <span style="margin-left: 5px;">px</span>
                        <p class="description">Larghezza delle miniature prodotto nel checkout (min: 50px, max: 300px,
                            default: 110px). Le immagini vengono ridimensionate automaticamente mantenendo la qualità.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_border_color">Colore bordi centrali / separatore</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_border_color" name="bw_checkout_border_color"
                            value="<?php echo esc_attr($border_color); ?>" class="bw-color-picker"
                            data-default-color="#262626" />
                        <p class="description">Colore del bordo verticale tra le due colonne.</p>
                    </td>
                </tr>
                <tr class="bw-section-break">
                    <th scope="row" colspan="2" style="padding-bottom:0;">
                        <h3 style="margin:0;">Section Headings</h3>
                        <p class="description" style="margin-top:6px;">Configure checkout section headings and free order
                            behavior.</p>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_hide_billing_heading">Hide Billing Details heading</label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_checkout_hide_billing_heading"
                                name="bw_checkout_hide_billing_heading" value="1" <?php checked('1', $hide_billing_heading); ?> />
                            <span style="font-weight: 500;">Remove the default WooCommerce "Billing details" heading.</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_hide_additional_heading">Hide Additional information heading</label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_checkout_hide_additional_heading"
                                name="bw_checkout_hide_additional_heading" value="1" <?php checked('1', $hide_additional_heading); ?> />
                            <span style="font-weight: 500;">Remove the default "Additional information" heading above order
                                notes.</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_address_heading_label">Address section heading label</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_address_heading_label" name="bw_checkout_address_heading_label"
                            value="<?php echo esc_attr($address_heading_label); ?>" class="regular-text"
                            placeholder="Delivery" />
                        <p class="description">Suggested: Delivery / Address / Shipping address.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_free_order_message">Free Order Message</label>
                    </th>
                    <td>
                        <textarea id="bw_checkout_free_order_message" name="bw_checkout_free_order_message" rows="3"
                            class="large-text"
                            placeholder="Your order is free. Complete your details and click Place order."><?php echo esc_textarea($free_order_message); ?></textarea>
                        <p class="description">Shown when order total becomes 0 (e.g., after applying a 100% discount
                            coupon). Stripe express buttons and divider will be hidden.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_free_order_button_text">Free Order Button Text</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_free_order_button_text" name="bw_checkout_free_order_button_text"
                            value="<?php echo esc_attr($free_order_button_text); ?>" class="regular-text"
                            placeholder="Confirm free order" />
                        <p class="description">Text for the Place Order button when order total is 0. Original button text
                            is restored when total becomes greater than 0.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="supabase" <?php echo 'supabase' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            // Check if Supabase keys are configured
            $supabase_project_url_check = get_option('bw_supabase_project_url', '');
            $supabase_configured = !empty($supabase_service_key) && !empty($supabase_project_url_check);
            ?>
            <div class="notice notice-info inline" style="margin: 15px 0;">
                <p>
                    <strong><?php esc_html_e('Supabase API Keys:', 'bw'); ?></strong>
                    <?php if ($supabase_configured): ?>
                        <span style="color: #00a32a;">&#10003; <?php esc_html_e('Configured', 'bw'); ?></span>
                    <?php else: ?>
                        <span style="color: #d63638;">&#10007; <?php esc_html_e('Not configured', 'bw'); ?></span>
                    <?php endif; ?>
                    &mdash;
                    <?php
                    printf(
                        /* translators: %s: link to Account Page settings */
                        esc_html__('Supabase Project URL, Anon Key, and Service Role Key are configured in %s.', 'bw'),
                        '<a href="' . esc_url(admin_url('admin.php?page=blackwork-site-settings&tab=account-page')) . '">' . esc_html__('Account Page > Technical Settings', 'bw') . '</a>'
                    );
                    ?>
                </p>
            </div>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="bw_supabase_checkout_provision_enabled"><?php esc_html_e('Supabase checkout provisioning', 'bw'); ?></label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_supabase_checkout_provision_enabled"
                                name="bw_supabase_checkout_provision_enabled" value="1" <?php checked($supabase_provision_enabled, '1'); ?> />
                            <span
                                style="font-weight: 500;"><?php esc_html_e('Invite Supabase users after guest checkout', 'bw'); ?></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, guest orders trigger a Supabase invite email that leads users to set their password.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="bw_supabase_invite_redirect_url"><?php esc_html_e('Supabase invite redirect URL', 'bw'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="bw_supabase_invite_redirect_url" name="bw_supabase_invite_redirect_url"
                            value="<?php echo esc_attr($supabase_invite_redirect); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('URL where Supabase directs users after the invite link (default: /my-account/set-password/). The URL must be allowlisted in Supabase Redirect URLs.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="bw_supabase_expired_link_redirect_url"><?php esc_html_e('Supabase expired link redirect URL', 'bw'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="bw_supabase_expired_link_redirect_url" name="bw_supabase_expired_link_redirect_url"
                            value="<?php echo esc_attr($supabase_expired_link_redirect); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('URL where users are redirected when invite links are invalid or expired (e.g. otp_expired). Default: /link-expired/.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
                <?php if ('1' === $supabase_provision_enabled && !$supabase_service_key): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Supabase provisioning warning', 'bw'); ?></th>
                        <td>
                            <div class="notice notice-warning inline">
                                <p><?php esc_html_e('Provisioning is enabled but Supabase Service Role Key is missing. Invites will not be sent.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="fields" <?php echo 'fields' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php if (class_exists('BW_Checkout_Fields_Admin')): ?>
                <?php BW_Checkout_Fields_Admin::get_instance()->render_tab(); ?>
            <?php else: ?>
                <p><?php esc_html_e('Checkout Fields module is unavailable.', 'bw'); ?></p>
            <?php endif; ?>
        </div>

        <div class="bw-tab-panel" data-bw-tab="subscribe" <?php echo 'subscribe' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $mail_marketing_checkout_url = add_query_arg(
                [
                    'page' => 'blackwork-mail-marketing',
                    'tab' => 'checkout',
                ],
                admin_url('admin.php')
            );
            ?>
            <div class="notice notice-info inline">
                <p>
                    <strong><?php esc_html_e('Subscribe settings moved.', 'bw'); ?></strong>
                    <?php
                    printf(
                        /* translators: %s: link to Mail Marketing page */
                        esc_html__('Manage newsletter settings in %s.', 'bw'),
                        '<a href="' . esc_url($mail_marketing_checkout_url) . '">' . esc_html__('Blackwork Site > Mail Marketing > Checkout', 'bw') . '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="bw-tab-panel" data-bw-tab="google-maps" <?php echo 'google-maps' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            // Get Google Maps settings
            $google_maps_enabled = get_option('bw_google_maps_enabled', '0');
            $google_maps_api_key = get_option('bw_google_maps_api_key', '');
            $google_maps_autofill = get_option('bw_google_maps_autofill', '1');
            $google_maps_restrict_country = get_option('bw_google_maps_restrict_country', '1');
            $google_maps_test_nonce = wp_create_nonce('bw_google_maps_test_connection');
            ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="bw_google_maps_enabled"><?php esc_html_e('Enable Address Autocomplete', 'bw'); ?></label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_google_maps_enabled" name="bw_google_maps_enabled" value="1" <?php checked($google_maps_enabled, '1'); ?> />
                            <span style="font-weight: 500;"><?php esc_html_e('Active', 'bw'); ?></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Enable Google Places API to suggest addresses as users type in the checkout form.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <div id="bw-google-maps-conditional-fields"
                style="<?php echo '1' === $google_maps_enabled ? '' : 'display:none;'; ?>">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="bw_google_maps_api_key"><?php esc_html_e('Google Maps API Key', 'bw'); ?>
                                *</label>
                        </th>
                        <td>
                            <input type="text" id="bw_google_maps_api_key" name="bw_google_maps_api_key"
                                value="<?php echo esc_attr($google_maps_api_key); ?>" class="regular-text"
                                placeholder="AIzaSyB..." />
                            <p class="description">
                                <?php
                                echo sprintf(
                                    /* translators: %s: URL to Google Cloud Console */
                                    esc_html__('Create an API key at %s and enable the Places API.', 'bw'),
                                    '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
                                );
                                ?>
                                <br>
                                <strong><?php esc_html_e('Free tier: $200/month (~70,000 autocomplete requests)', 'bw'); ?></strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="bw_google_maps_test_connection"><?php esc_html_e('API Connection Test', 'bw'); ?></label>
                        </th>
                        <td>
                            <button type="button" id="bw_google_maps_test_connection" class="button button-secondary">
                                <?php esc_html_e('Test Google Maps Connection', 'bw'); ?>
                            </button>
                            <p id="bw_google_maps_test_result" class="description" style="margin-top: 10px; display:none;"></p>
                            <p class="description" style="margin-top: 10px;">
                                <strong><?php esc_html_e('Monitor API usage & costs:', 'bw'); ?></strong><br>
                                <a href="https://console.cloud.google.com/apis/api/maps-backend.googleapis.com/quotas"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('Maps JavaScript API Quotas', 'bw'); ?></a> |
                                <a href="https://console.cloud.google.com/apis/api/places-backend.googleapis.com/quotas"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('Places API Quotas', 'bw'); ?></a> |
                                <a href="https://console.cloud.google.com/apis/dashboard"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('API Metrics Dashboard', 'bw'); ?></a> |
                                <a href="https://console.cloud.google.com/billing/budgets"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('Billing Budgets & Alerts', 'bw'); ?></a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label
                                for="bw_google_maps_autofill"><?php esc_html_e('Auto-fill City & Postcode', 'bw'); ?></label>
                        </th>
                        <td>
                            <label style="display: inline-flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="bw_google_maps_autofill" name="bw_google_maps_autofill" value="1"
                                    <?php checked($google_maps_autofill, '1'); ?> />
                                <span style="font-weight: 500;"><?php esc_html_e('Active', 'bw'); ?></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When user selects an address, automatically fill City and Postal Code fields.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label
                                for="bw_google_maps_restrict_country"><?php esc_html_e('Restrict to Selected Country', 'bw'); ?></label>
                        </th>
                        <td>
                            <label style="display: inline-flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="bw_google_maps_restrict_country"
                                    name="bw_google_maps_restrict_country" value="1" <?php checked($google_maps_restrict_country, '1'); ?> />
                                <span style="font-weight: 500;"><?php esc_html_e('Active (Recommended)', 'bw'); ?></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Search addresses ONLY in the country selected in the "Country/Region" dropdown. Improves search accuracy and relevance.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr class="bw-section-break">
                        <th scope="row" colspan="2" style="padding-bottom:0;">
                            <h3 style="margin:0;"><?php esc_html_e('Privacy & GDPR', 'bw'); ?></h3>
                            <p class="description" style="margin-top:6px;">
                                <?php esc_html_e('Google Places API may track user searches. Add a notice in your Privacy Policy.', 'bw'); ?>
                            </p>
                        </th>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <div
                                style="background: #f0f6fc; border-left: 4px solid #0969da; padding: 16px; margin-top: 10px;">
                                <p style="margin: 0 0 10px 0; font-weight: 600;">
                                    ℹ️ <?php esc_html_e('How to get your Google Maps API Key:', 'bw'); ?>
                                </p>
                                <ol style="margin: 0; padding-left: 20px;">
                                    <li><?php esc_html_e('Go to', 'bw'); ?> <a href="https://console.cloud.google.com/"
                                            target="_blank">Google Cloud Console</a></li>
                                    <li><?php esc_html_e('Create a new project (or select an existing one)', 'bw'); ?>
                                    </li>
                                    <li><?php esc_html_e('Enable "Places API" in APIs & Services', 'bw'); ?></li>
                                    <li><?php esc_html_e('Go to Credentials → Create Credentials → API Key', 'bw'); ?>
                                    </li>
                                    <li><?php esc_html_e('Restrict the key to your domain and Places API only', 'bw'); ?>
                                    </li>
                                    <li><?php esc_html_e('Paste the key above and save settings', 'bw'); ?></li>
                                </ol>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    var $testButton = $('#bw_google_maps_test_connection');
                    var $testResult = $('#bw_google_maps_test_result');
                    var defaultButtonText = $testButton.text();

                    function setTestMessage(type, message, details) {
                        var color = '#0a7a2f';
                        var bg = '#ecfdf3';
                        var border = '#9ee7b3';

                        if (type === 'error') {
                            color = '#a90000';
                            bg = '#fff1f1';
                            border = '#f0b2b2';
                        }

                        var text = message || '';
                        if (details) {
                            text += ' ' + details;
                        }

                        $testResult
                            .text(text)
                            .css({
                                display: 'block',
                                color: color,
                                background: bg,
                                border: '1px solid ' + border,
                                borderRadius: '6px',
                                padding: '10px 12px',
                                fontWeight: 500
                            });
                    }

                    // Toggle conditional fields
                    $('#bw_google_maps_enabled').on('change', function () {
                        if ($(this).is(':checked')) {
                            $('#bw-google-maps-conditional-fields').slideDown(200);
                        } else {
                            $('#bw-google-maps-conditional-fields').slideUp(200);
                        }
                    });

                    $testButton.on('click', function () {
                        var apiKey = ($('#bw_google_maps_api_key').val() || '').trim();

                        if (!apiKey) {
                            setTestMessage('error', '<?php echo esc_js(__('Insert a Google Maps API key first.', 'bw')); ?>');
                            return;
                        }

                        $testButton.prop('disabled', true).text('<?php echo esc_js(__('Testing…', 'bw')); ?>');
                        $testResult.hide().text('');

                        $.post(ajaxurl, {
                            action: 'bw_google_maps_test_connection',
                            nonce: '<?php echo esc_js($google_maps_test_nonce); ?>',
                            api_key: apiKey
                        }).done(function (response) {
                            if (response && response.success && response.data) {
                                setTestMessage('success', response.data.message || '<?php echo esc_js(__('Connection successful.', 'bw')); ?>', response.data.details || '');
                                return;
                            }

                            var data = response && response.data ? response.data : {};
                            setTestMessage('error', data.message || '<?php echo esc_js(__('Connection test failed.', 'bw')); ?>', data.details || '');
                        }).fail(function () {
                            setTestMessage('error', '<?php echo esc_js(__('Unable to run connection test. Please try again.', 'bw')); ?>');
                        }).always(function () {
                            $testButton.prop('disabled', false).text(defaultButtonText);
                        });
                    });
                });
            </script>
        </div>

        <div class="bw-tab-panel" data-bw-tab="google-pay" <?php echo 'google-pay' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $google_pay_enabled = get_option('bw_google_pay_enabled', 0);
            $google_pay_test_mode = get_option('bw_google_pay_test_mode', 0);
            $google_pay_pub_key = get_option('bw_google_pay_publishable_key', '');
            $google_pay_sec_key = get_option('bw_google_pay_secret_key', '');
            $google_pay_test_pub_key = get_option('bw_google_pay_test_publishable_key', '');
            $google_pay_test_sec_key = get_option('bw_google_pay_test_secret_key', '');
            $google_pay_statement_descriptor = get_option('bw_google_pay_statement_descriptor', '');
            $google_pay_webhook_secret = get_option('bw_google_pay_webhook_secret', '');
            $google_pay_test_webhook_secret = get_option('bw_google_pay_test_webhook_secret', '');
            $google_pay_webhook_url = add_query_arg('wc-api', 'bw_google_pay', home_url('/'));
            ?>

            <div class="bw-settings-section">
                <h2 class="title">Google Pay (Stripe Integration)</h2>
                <p class="description">Configura Google Pay tramite Stripe per il checkout personalizzato. Nota: Google Pay
                    richiede HTTPS attivo e dominio verificato su Stripe.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Abilita Gateway</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_google_pay_enabled" type="checkbox" id="bw_google_pay_enabled" value="1" <?php checked(1, $google_pay_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Mode</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_google_pay_test_mode" type="checkbox" id="bw_google_pay_test_mode" value="1"
                                    <?php checked(1, $google_pay_test_mode); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Live Publishable Key</th>
                        <td>
                            <input name="bw_google_pay_publishable_key" type="text" id="bw_google_pay_publishable_key"
                                value="<?php echo esc_attr($google_pay_pub_key); ?>" class="regular-text" placeholder="pk_live_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Secret Key</th>
                        <td>
                            <input name="bw_google_pay_secret_key" type="password" id="bw_google_pay_secret_key"
                                value="<?php echo esc_attr($google_pay_sec_key); ?>" class="regular-text" placeholder="sk_live_..." />
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Test Publishable Key</th>
                        <td>
                            <input name="bw_google_pay_test_publishable_key" type="text" id="bw_google_pay_test_publishable_key"
                                value="<?php echo esc_attr($google_pay_test_pub_key); ?>" class="regular-text" placeholder="pk_test_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Secret Key</th>
                        <td>
                            <input name="bw_google_pay_test_secret_key" type="password" id="bw_google_pay_test_secret_key"
                                value="<?php echo esc_attr($google_pay_test_sec_key); ?>" class="regular-text" placeholder="sk_test_..." />
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Verifica connessione (globale)</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <span id="bw-google-pay-mode-pill" class="bw-google-pay-mode-pill">Modalita attiva: TEST</span>
                                <button type="button" class="button" id="bw-google-pay-test-connection">Verifica connessione (TEST)</button>
                            </div>
                            <span id="bw-google-pay-test-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">Questo controllo usa la modalità attiva: <strong>Test Mode ON = chiavi test</strong>, <strong>Test Mode OFF = chiavi live</strong>.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Statement Descriptor</th>
                        <td>
                            <input name="bw_google_pay_statement_descriptor" type="text" id="bw_google_pay_statement_descriptor"
                                value="<?php echo esc_attr($google_pay_statement_descriptor); ?>" class="regular-text" placeholder="BlackWork Store" maxlength="22" />
                            <p class="description">Testo visualizzato nell'estratto conto del cliente (max 22 caratteri). Lascia vuoto per usare il default dell'account Stripe.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code><?php echo esc_url($google_pay_webhook_url); ?></code>
                            <p class="description">Inserisci questo URL nella Dashboard Stripe → Sviluppatori → Webhook → Aggiungi endpoint. Abilita gli eventi: <strong>payment_intent.succeeded</strong>, <strong>payment_intent.payment_failed</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Webhook Secret</th>
                        <td>
                            <input name="bw_google_pay_webhook_secret" type="password" id="bw_google_pay_webhook_secret"
                                value="<?php echo esc_attr($google_pay_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">La chiave di firma del webhook live (inizia con <code>whsec_</code>). Trovala nella Dashboard Stripe → Webhook → dettaglio endpoint.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Webhook Secret</th>
                        <td>
                            <input name="bw_google_pay_test_webhook_secret" type="password" id="bw_google_pay_test_webhook_secret"
                                value="<?php echo esc_attr($google_pay_test_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">La chiave di firma del webhook di test.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <style>
                .bw-settings-divider hr {
                    border: 0;
                    border-top: 1px solid #ddd;
                    margin: 10px 0;
                }

                .bw-settings-section {
                    padding: 20px;
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                }

                .bw-google-pay-test-result {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: 8px;
                    font-weight: 600;
                }

                .bw-google-pay-connection-row {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .bw-google-pay-mode-pill {
                    display: inline-flex;
                    align-items: center;
                    padding: 4px 10px;
                    border-radius: 999px;
                    font-size: 12px;
                    font-weight: 700;
                    letter-spacing: 0.2px;
                    text-transform: uppercase;
                    background: #fef3c7;
                    color: #92400e;
                    border: 1px solid #f59e0b;
                }

                .bw-google-pay-mode-pill.is-live {
                    background: #ecfdf3;
                    color: #166534;
                    border-color: #22c55e;
                }

                .bw-google-pay-test-result::before {
                    content: "";
                    width: 9px;
                    height: 9px;
                    border-radius: 50%;
                    background: #b8c0cc;
                }

                .bw-google-pay-test-result.is-success {
                    color: #0a7d33;
                }

                .bw-google-pay-test-result.is-success::before {
                    background: #0a7d33;
                }

                .bw-google-pay-test-result.is-error {
                    color: #b42318;
                }

                .bw-google-pay-test-result.is-error::before {
                    background: #b42318;
                }

                .bw-google-pay-test-result.is-testing {
                    color: #475467;
                }
            </style>
        </div>

        <div class="bw-tab-panel" data-bw-tab="klarna-pay" <?php echo 'klarna-pay' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $klarna_enabled = get_option('bw_klarna_enabled', 0);
            $klarna_pub_key = get_option('bw_klarna_publishable_key', '');
            $klarna_sec_key = get_option('bw_klarna_secret_key', '');
            $klarna_statement_descriptor = get_option('bw_klarna_statement_descriptor', '');
            $klarna_webhook_secret = get_option('bw_klarna_webhook_secret', '');
            $klarna_webhook_url = add_query_arg('wc-api', 'bw_klarna', home_url('/'));
            ?>

            <div class="bw-settings-section">
                <h2 class="title">Klarna Pay (Stripe Integration)</h2>
                <p class="description">Configure Klarna Flexible Payments via Stripe for the custom checkout. Klarna requires HTTPS and supported buyer country/currency in Stripe.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Enable Gateway</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_klarna_enabled" type="checkbox" id="bw_klarna_enabled" value="1" <?php checked(1, $klarna_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Live Publishable Key</th>
                        <td>
                            <input name="bw_klarna_publishable_key" type="text" id="bw_klarna_publishable_key"
                                value="<?php echo esc_attr($klarna_pub_key); ?>" class="regular-text" placeholder="pk_live_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Secret Key</th>
                        <td>
                            <input name="bw_klarna_secret_key" type="password" id="bw_klarna_secret_key"
                                value="<?php echo esc_attr($klarna_sec_key); ?>" class="regular-text" placeholder="sk_live_..." />
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Connection Check (Global)</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <span id="bw-klarna-mode-pill" class="bw-google-pay-mode-pill is-live">ACTIVE MODE: LIVE</span>
                                <button type="button" class="button" id="bw-klarna-test-connection">Verify connection (LIVE)</button>
                            </div>
                            <span id="bw-klarna-test-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">This check always validates <strong>live keys</strong> for Klarna.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Statement Descriptor</th>
                        <td>
                            <input name="bw_klarna_statement_descriptor" type="text" id="bw_klarna_statement_descriptor"
                                value="<?php echo esc_attr($klarna_statement_descriptor); ?>" class="regular-text" placeholder="BlackWork Store" maxlength="22" />
                            <p class="description">Text shown on the customer statement (max 22 chars). Leave empty to use Stripe account default.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code><?php echo esc_url($klarna_webhook_url); ?></code>
                            <p class="description">Add this endpoint in Stripe Dashboard → Developers → Webhooks. Enable: <strong>payment_intent.succeeded</strong>, <strong>payment_intent.payment_failed</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Webhook Secret</th>
                        <td>
                            <input name="bw_klarna_webhook_secret" type="password" id="bw_klarna_webhook_secret"
                                value="<?php echo esc_attr($klarna_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">Webhook signing secret for live endpoint (starts with <code>whsec_</code>).</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bw-tab-panel" data-bw-tab="apple-pay" <?php echo 'apple-pay' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $apple_pay_enabled = get_option('bw_apple_pay_enabled', 0);
            $apple_pay_express_helper_enabled = get_option('bw_apple_pay_express_helper_enabled', 1);
            $apple_pay_pub_key = get_option('bw_apple_pay_publishable_key', '');
            $apple_pay_sec_key = get_option('bw_apple_pay_secret_key', '');
            $apple_pay_statement_descriptor = get_option('bw_apple_pay_statement_descriptor', '');
            $apple_pay_webhook_secret = get_option('bw_apple_pay_webhook_secret', '');
            $apple_pay_webhook_url = add_query_arg('wc-api', 'bw_apple_pay', home_url('/'));
            $apple_pay_site_domain = wp_parse_url(home_url('/'), PHP_URL_HOST);
            $apple_pay_site_domain = is_string($apple_pay_site_domain) ? strtolower(trim($apple_pay_site_domain)) : '';
            ?>

            <div class="bw-settings-section">
                <h2 class="title">Apple Pay (Stripe Integration)</h2>
                <p class="description">Configure Apple Pay via Stripe for the custom checkout. Apple Pay requires HTTPS and domain verification in Stripe.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Enable Gateway</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_apple_pay_enabled" type="checkbox" id="bw_apple_pay_enabled" value="1" <?php checked(1, $apple_pay_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Live Publishable Key</th>
                        <td>
                            <input name="bw_apple_pay_publishable_key" type="password" id="bw_apple_pay_publishable_key"
                                value="<?php echo esc_attr($apple_pay_pub_key); ?>" class="regular-text" placeholder="pk_live_..." />
                            <p class="description">Optional override. Leave empty to use global live keys already configured in BlackWork.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Secret Key</th>
                        <td>
                            <input name="bw_apple_pay_secret_key" type="password" id="bw_apple_pay_secret_key"
                                value="<?php echo esc_attr($apple_pay_sec_key); ?>" class="regular-text" placeholder="sk_live_..." />
                            <p class="description">Optional override. Leave empty to use global live keys already configured in BlackWork.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Express Checkout Helper</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_apple_pay_express_helper_enabled" type="checkbox" id="bw_apple_pay_express_helper_enabled" value="1" <?php checked(1, $apple_pay_express_helper_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                            <p class="description" style="margin-top: 8px;">When Apple Pay is unavailable, use the Apple button to scroll to the Express Checkout section.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Connection Check (Global)</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <span id="bw-apple-pay-mode-pill" class="bw-google-pay-mode-pill is-live">ACTIVE MODE: LIVE</span>
                                <button type="button" class="button" id="bw-apple-pay-test-connection">Verify connection (LIVE)</button>
                            </div>
                            <span id="bw-apple-pay-test-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">This check always validates <strong>live keys</strong> for Apple Pay. Test keys are never accepted.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Domain Verification</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <button type="button" class="button" id="bw-apple-pay-verify-domain">Verify domain in Stripe</button>
                            </div>
                            <span id="bw-apple-pay-domain-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">
                                Checks Stripe Payment Method Domains for: <strong><?php echo esc_html($apple_pay_site_domain); ?></strong>.
                                If not verified/enabled, Apple Pay will not be available in checkout.
                                <br />
                                Verify/manage domains in Stripe:
                                <a href="https://dashboard.stripe.com/settings/payment_methods" target="_blank" rel="noopener noreferrer">Stripe Dashboard → Settings → Payment Methods</a>.
                            </p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Statement Descriptor</th>
                        <td>
                            <input name="bw_apple_pay_statement_descriptor" type="text" id="bw_apple_pay_statement_descriptor"
                                value="<?php echo esc_attr($apple_pay_statement_descriptor); ?>" class="regular-text" placeholder="BlackWork Store" maxlength="22" />
                            <p class="description">Text shown on the customer statement (max 22 chars). Leave empty to use Stripe account default.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code><?php echo esc_url($apple_pay_webhook_url); ?></code>
                            <p class="description">Add this endpoint in Stripe Dashboard → Developers → Webhooks. Enable: <strong>payment_intent.succeeded</strong>, <strong>payment_intent.payment_failed</strong>, <strong>payment_intent.canceled</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Webhook Secret</th>
                        <td>
                            <input name="bw_apple_pay_webhook_secret" type="password" id="bw_apple_pay_webhook_secret"
                                value="<?php echo esc_attr($apple_pay_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">Webhook signing secret for the live endpoint (starts with <code>whsec_</code>).</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bw-tab-panel" data-bw-tab="footer" <?php echo 'footer' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <div class="bw-settings-header" style="margin-bottom: 25px;">
                <h2><?php esc_html_e('Checkout Footer Cleanup', 'bw'); ?></h2>
                <p><?php esc_html_e('Manage the policy links and content shown at the bottom of the checkout page.', 'bw'); ?>
                </p>
            </div>

            <table class="form-table" role="presentation" style="margin-bottom: 30px;">
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_legal_text">Testo informativo legale</label>
                    </th>
                    <td>
                        <textarea id="bw_checkout_legal_text" name="bw_checkout_legal_text" rows="4"
                            class="large-text"><?php echo esc_textarea($legal_text); ?></textarea>
                        <p class="description">Testo mostrato sotto i metodi di pagamento; supporta link e HTML consentito.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="bw_checkout_show_footer_copyright"><?php esc_html_e('Show Footer Copyright', 'bw'); ?></label>
                    </th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_checkout_show_footer_copyright"
                                name="bw_checkout_show_footer_copyright" value="1" <?php checked('1', $show_footer_copyright); ?> />
                            <span class="description">Mostra o nascondi il testo di copyright nel footer.</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_footer_copyright_text">Text of Footer Copyright</label>
                    </th>
                    <td>
                        <textarea id="bw_checkout_footer_copyright_text" name="bw_checkout_footer_copyright_text" rows="2"
                            class="large-text"><?php echo esc_textarea($footer_copyright); ?></textarea>
                        <p class="description">Testo mostrato nel footer della colonna sinistra; viene preceduto da
                            "Copyright © {anno},".</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mostra link "Return to shop"</th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_checkout_show_return_to_shop"
                                name="bw_checkout_show_return_to_shop" value="1" <?php checked('1', $show_return_to_shop); ?> />
                            <span class="description">Attiva o disattiva il link di ritorno allo shop nel footer della
                                colonna sinistra.</span>
                        </label>
                    </td>
                </tr>
            </table>

            <h3 style="margin-bottom: 20px;"><?php esc_html_e('Policy Sections (Popups)', 'bw'); ?></h3>

            <?php foreach ($policy_settings as $key => $data): ?>
                <?php $policy_enabled = !isset($data['enabled']) || '1' === (string) $data['enabled']; ?>
                <div class="bw-policy-section"
                    style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 15px; text-transform: capitalize;">
                        <?php echo esc_html($key); ?> Policy
                    </h3>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Enabled', 'bw'); ?></label></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="bw_checkout_policy_<?php echo esc_attr($key); ?>[enabled]"
                                        class="bw-policy-enabled-toggle"
                                        data-policy-key="<?php echo esc_attr($key); ?>"
                                        value="1" <?php checked('1', isset($data['enabled']) ? (string) $data['enabled'] : '1'); ?> />
                                    <span class="description"><?php esc_html_e('Show this policy link and popup in checkout footer.', 'bw'); ?></span>
                                </label>
                            </td>
                        </tr>
                        <tr class="bw-policy-fields bw-policy-fields--<?php echo esc_attr($key); ?>"
                            <?php echo $policy_enabled ? '' : 'style="display:none;"'; ?>>
                            <th scope="row"><label><?php esc_html_e('Link Title', 'bw'); ?></label></th>
                            <td>
                                <input type="text" name="bw_checkout_policy_<?php echo esc_attr($key); ?>[title]"
                                    value="<?php echo esc_attr($data['title']); ?>" class="regular-text"
                                    placeholder="<?php echo esc_attr(ucfirst($key) . ' policy'); ?>" />
                            </td>
                        </tr>
                        <tr class="bw-policy-fields bw-policy-fields--<?php echo esc_attr($key); ?>"
                            <?php echo $policy_enabled ? '' : 'style="display:none;"'; ?>>
                            <th scope="row"><label><?php esc_html_e('Popup Subtitle', 'bw'); ?></label></th>
                            <td>
                                <input type="text" name="bw_checkout_policy_<?php echo esc_attr($key); ?>[subtitle]"
                                    value="<?php echo esc_attr($data['subtitle']); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Optional subtitle shown inside the popup.', 'bw'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="bw-policy-fields bw-policy-fields--<?php echo esc_attr($key); ?>"
                            <?php echo $policy_enabled ? '' : 'style="display:none;"'; ?>>
                            <th scope="row"><label><?php esc_html_e('Content', 'bw'); ?></label></th>
                            <td>
                                <?php
                                wp_editor($data['content'], "bw_checkout_policy_{$key}_content", [
                                    'textarea_name' => "bw_checkout_policy_{$key}[content]",
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                    'teeny' => false,
                                    'quicktags' => true
                                ]);
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>

            <?php submit_button('Salva Footer', 'primary', 'bw_checkout_footer_submit'); ?>
        </div>

        <?php if (in_array($active_checkout_tab, ['fields', 'subscribe', 'footer'], true)): ?>
            <?php // Buttons rendered inside module panels. ?>
        <?php else: ?>
            <?php submit_button('Salva impostazioni', 'primary', 'bw_checkout_settings_submit'); ?>
        <?php endif; ?>
    </form>

        <script>
        jQuery(document).ready(function ($) {
            function togglePolicyFields(checkbox) {
                var $checkbox = $(checkbox);
                var key = $checkbox.data('policy-key');
                if (!key) {
                    return;
                }

                var isEnabled = $checkbox.is(':checked');
                var $rows = $('.bw-policy-fields--' + key);
                if (isEnabled) {
                    $rows.stop(true, true).slideDown(150);
                } else {
                    $rows.stop(true, true).slideUp(150);
                }
            }

            $('.bw-policy-enabled-toggle').each(function () {
                togglePolicyFields(this);
            });

            $(document).on('change', '.bw-policy-enabled-toggle', function () {
                togglePolicyFields(this);
            });

            $('.bw-media-upload').on('click', function (e) {
                e.preventDefault();

                const targetInput = $(this).data('target');
                const frame = wp.media({
                    title: 'Seleziona immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });

                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url);
                });

                frame.open();
            });

            $('.bw-color-picker').wpColorPicker();
        });
    </script>
    <?php
}

/**
 * Renderizza il tab Cart Pop-up
 */
function bw_site_render_cart_popup_tab()
{
    // Salva le impostazioni se il form è stato inviato
    $saved = false;
    if (isset($_POST['bw_cart_popup_submit'])) {
        $saved = bw_cart_popup_save_settings();
    }

    // Recupera le impostazioni correnti
    $active = get_option('bw_cart_popup_active', 0);
    $show_floating_trigger = get_option('bw_cart_popup_show_floating_trigger', 0);
    $disable_on_checkout = get_option('bw_cart_popup_disable_on_checkout', 1);
    $checkout_text = get_option('bw_cart_popup_checkout_text', 'Proceed to checkout');
    $continue_text = get_option('bw_cart_popup_continue_text', 'Continue shopping');
    $continue_url = get_option('bw_cart_popup_continue_url', '');
    $additional_svg = get_option('bw_cart_popup_additional_svg', '');
    $empty_cart_svg = get_option('bw_cart_popup_empty_cart_svg', '');
    $svg_black = get_option('bw_cart_popup_svg_black', 0);
    $return_shop_url = get_option('bw_cart_popup_return_shop_url', '');
    $show_quantity_badge = get_option('bw_cart_popup_show_quantity_badge', 1);
    $promo_section_label = get_option('bw_cart_popup_promo_section_label', 'Promo code section');

    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_cart_popup_save', 'bw_cart_popup_nonce'); ?>

        <table class="form-table" role="presentation">
            <!-- Toggle ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_active">Attiva Cart Pop-Up</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_active" name="bw_cart_popup_active" value="1" <?php checked(1, $active); ?> />
                        <span class="description">Quando attivo, i pulsanti "Add to Cart" apriranno il pannello slide-in
                            invece di andare alla pagina carrello.</span>
                    </label>
                </td>
            </tr>

            <!-- Floating cart trigger ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_show_floating_trigger">Mostra pulsante carrello fisso</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_show_floating_trigger"
                            name="bw_cart_popup_show_floating_trigger" value="1" <?php checked(1, $show_floating_trigger); ?> />
                        <span class="description">Attiva l'icona fissa in basso a destra con badge quantità; cliccandola si
                            apre il cart pop-up.</span>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_disable_on_checkout">Disabilita Cart Pop-Up in checkout</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_disable_on_checkout"
                            name="bw_cart_popup_disable_on_checkout" value="1" <?php checked(1, $disable_on_checkout); ?> />
                        <span class="description">Quando attivo, in checkout non vengono caricati icona flottante,
                            pannello, CSS e JS del Cart Pop-Up.</span>
                    </label>
                </td>
            </tr>

            <!-- Slide-in Animation ON/OFF -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_slide_animation">Slide-in animation (cart open)</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_slide_animation" name="bw_cart_popup_slide_animation"
                            value="1" <?php checked(1, get_option('bw_cart_popup_slide_animation', 1)); ?> />
                        <span class="description">Quando attivo, il cart pop-up si apre automaticamente con slide-in da
                            destra ogni volta che un prodotto viene aggiunto al carrello.</span>
                    </label>
                </td>
            </tr>

            <!-- Badge quantità -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_show_quantity_badge">Mostra badge quantità (thumbnail)</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_show_quantity_badge"
                            name="bw_cart_popup_show_quantity_badge" value="1" <?php checked(1, $show_quantity_badge); ?> />
                        <span class="description">Attiva o disattiva il pallino con il numero di pezzi sopra l’immagine
                            prodotto nel cart pop-up.</span>
                    </label>
                </td>
            </tr>

            <!-- Sezione Pulsanti -->
            <tr>
                <th colspan="2">
                    <h2>Configurazione Pulsanti</h2>
                </th>
            </tr>

            <!-- Testo Proceed to Checkout -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_checkout_text">Testo Pulsante Checkout</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_checkout_text" name="bw_cart_popup_checkout_text"
                        value="<?php echo esc_attr($checkout_text); ?>" class="regular-text" />
                    <p class="description">Testo del pulsante (default: "Proceed to checkout")</p>
                </td>
            </tr>

            <!-- Testo Continue Shopping -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_text">Testo Pulsante</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_continue_text" name="bw_cart_popup_continue_text"
                        value="<?php echo esc_attr($continue_text); ?>" class="regular-text" />
                    <p class="description">Testo del pulsante (default: "Continue shopping")</p>
                </td>
            </tr>

            <!-- Link Personalizzato -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_continue_url">Link Personalizzato</label>
                </th>
                <td>
                    <input type="url" id="bw_cart_popup_continue_url" name="bw_cart_popup_continue_url"
                        value="<?php echo esc_attr($continue_url); ?>" class="regular-text" placeholder="/shop/" />
                    <p class="description">URL personalizzato per il pulsante Continue Shopping (lascia vuoto per usare
                        /shop/ di default)</p>
                </td>
            </tr>

            <!-- === PROMO CODE SECTION === -->
            <tr>
                <th colspan="2">
                    <hr style="margin: 30px 0 20px 0; border: none; border-top: 2px solid #ddd;">
                    <h2 style="margin: 20px 0 10px 0;">Promo Code Section</h2>
                </th>
            </tr>

            <!-- Section Label -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_promo_section_label">Section Label</label>
                </th>
                <td>
                    <input type="text" id="bw_cart_popup_promo_section_label" name="bw_cart_popup_promo_section_label"
                        value="<?php echo esc_attr($promo_section_label); ?>" class="regular-text" />
                    <p class="description">Label per la sezione promo code (default: "Promo code section")</p>
                </td>
            </tr>

            <!-- === EMPTY CART SETTINGS === -->
            <tr>
                <th colspan="2">
                    <h3 style="margin: 30px 0 10px 0;">Empty Cart Settings</h3>
                </th>
            </tr>

            <!-- Return to Shop URL -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_return_shop_url">Return to Shop URL</label>
                </th>
                <td>
                    <input type="url" id="bw_cart_popup_return_shop_url" name="bw_cart_popup_return_shop_url"
                        value="<?php echo esc_attr($return_shop_url); ?>" class="regular-text" placeholder="/shop/" />
                    <p class="description">URL personalizzato per il pulsante "Return to Shop" (lascia vuoto per usare
                        /shop/ di default)</p>
                </td>
            </tr>

            <!-- Sezione SVG Personalizzato -->
            <tr>
                <th colspan="2">
                    <h2>SVG Personalizzato</h2>
                </th>
            </tr>

            <!-- SVG Aggiuntivo -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_additional_svg">Cart Pop-Up SVG Icon (Custom)</label>
                </th>
                <td>
                    <textarea id="bw_cart_popup_additional_svg" name="bw_cart_popup_additional_svg" rows="8"
                        class="large-text code"><?php echo esc_textarea($additional_svg); ?></textarea>
                    <p class="description">Incolla qui il codice SVG completo da visualizzare nel Cart Pop-Up. Esempio:
                        &lt;svg xmlns="http://www.w3.org/2000/svg"...&gt;...&lt;/svg&gt;</p>
                </td>
            </tr>

            <!-- Empty Cart SVG (Custom) -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_empty_cart_svg">Empty Cart SVG (Custom)</label>
                </th>
                <td>
                    <textarea id="bw_cart_popup_empty_cart_svg" name="bw_cart_popup_empty_cart_svg" rows="8"
                        class="large-text code"><?php echo esc_textarea($empty_cart_svg); ?></textarea>
                    <p class="description">Incolla qui il codice SVG personalizzato per l'icona del carrello vuoto. Se
                        vuoto, verrà usata l'icona di default.</p>
                </td>
            </tr>

            <!-- Opzione colore nero SVG -->
            <tr>
                <th scope="row">
                    <label for="bw_cart_popup_svg_black">Colora SVG di Nero</label>
                </th>
                <td>
                    <label class="switch">
                        <input type="checkbox" id="bw_cart_popup_svg_black" name="bw_cart_popup_svg_black" value="1" <?php checked(1, $svg_black); ?> />
                        <span class="description">Applica automaticamente fill: #000 su tutti i path dell'SVG</span>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva Impostazioni', 'primary', 'bw_cart_popup_submit'); ?>
    </form>

    <!-- Note informative -->
    <div class="card" style="margin-top: 20px;">
        <h2>Note sull'utilizzo</h2>
        <ul>
            <li><strong>Funzionalità OFF:</strong> I pulsanti "Add to Cart" comportano in modo standard e portano alla
                pagina del carrello.</li>
            <li><strong>Funzionalità ON:</strong> Cliccando su "Add to Cart" si apre un pannello slide-in da destra con
                overlay scuro.</li>
            <li><strong>Design:</strong> Il pannello replica il design del mini-cart con header, lista prodotti, promo code,
                totali e pulsanti azione.</li>
            <li><strong>Promo Code:</strong> Al click su "Click here" appare un box per inserire il coupon con calcolo
                real-time dello sconto.</li>
            <li><strong>CSS Personalizzato:</strong> Puoi modificare ulteriormente lo stile editando il file
                <code>assets/css/bw-cart-popup.css</code>
            </li>
        </ul>
    </div>

    <style>
        .switch input {
            margin-right: 10px;
        }

        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
        }

        .card h2 {
            margin-top: 0;
        }

        .card ul {
            list-style: disc;
            padding-left: 20px;
        }

        .card li {
            margin-bottom: 10px;
        }

        /* Layout compatto per padding */
        .bw-padding-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .bw-padding-field {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .bw-padding-field input {
            margin-bottom: 5px;
        }

        .bw-padding-field label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
    </style>
    <?php
    // JavaScript for border toggle is now loaded via bw-border-toggle-admin.js
}

/**
 * Renderizza il tab Redirect.
 */
function bw_site_render_redirect_tab()
{
    $saved = false;

    if (isset($_POST['bw_redirects_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_redirects_save', 'bw_redirects_nonce');

        $redirects_input = isset($_POST['bw_redirects']) && is_array($_POST['bw_redirects']) ? wp_unslash($_POST['bw_redirects']) : [];
        $sanitized = [];

        foreach ($redirects_input as $redirect) {
            $target_raw = isset($redirect['target_url']) ? trim((string) $redirect['target_url']) : '';
            $source_raw = isset($redirect['source_url']) ? trim((string) $redirect['source_url']) : '';
            $target = esc_url_raw($target_raw);
            $normalized_source = bw_normalize_redirect_path($source_raw);
            $source_to_store = '' !== $source_raw ? sanitize_text_field($source_raw) : '';

            if ('' === $target || '' === $normalized_source) {
                continue;
            }

            $sanitized[] = [
                'source' => $source_to_store,
                'target' => $target,
            ];
        }

        update_option('bw_redirects', $sanitized);
        $saved = true;
    }

    $redirects = get_option('bw_redirects', []);

    if (!is_array($redirects)) {
        $redirects = [];
    }

    if (empty($redirects)) {
        $redirects[] = [
            'source' => '',
            'target' => '',
        ];
    }

    $next_index = count($redirects);
    ?>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Redirect salvati con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_redirects_save', 'bw_redirects_nonce'); ?>

        <table class="form-table bw-redirects-table" role="presentation">
            <thead>
                <tr>
                    <th scope="col">Link d'arrivo</th>
                    <th scope="col">Link di redirect</th>
                    <th scope="col">Azioni</th>
                </tr>
            </thead>
            <tbody id="bw-redirects-rows" data-next-index="<?php echo esc_attr($next_index); ?>">
                <?php foreach ($redirects as $index => $redirect):
                    $target = isset($redirect['target']) ? $redirect['target'] : '';
                    $source = isset($redirect['source']) ? $redirect['source'] : '';
                    ?>
                    <tr class="bw-redirect-row">
                        <td>
                            <label>
                                Inserisci il link d'arrivo
                                <input type="text" name="bw_redirects[<?php echo esc_attr($index); ?>][target_url]"
                                    value="<?php echo esc_attr($target); ?>" class="regular-text"
                                    placeholder="https://esempio.com/pagina" />
                            </label>
                            <p class="description">URL assoluto verso cui reindirizzare l'utente.</p>
                        </td>
                        <td>
                            <label>
                                Inserisci il link di redirect
                                <input type="text" name="bw_redirects[<?php echo esc_attr($index); ?>][source_url]"
                                    value="<?php echo esc_attr($source); ?>" class="regular-text"
                                    placeholder="/promo/black-friday" />
                            </label>
                            <p class="description">Accetta un path relativo (es. /promo) o un URL completo.</p>
                        </td>
                        <td class="bw-redirect-actions">
                            <button type="button" class="button button-link-delete bw-remove-redirect">Rimuovi</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            <button type="button" class="button" id="bw-add-redirect">Aggiungi redirect</button>
        </p>

        <script type="text/html" id="bw-redirect-row-template">
                                                                                                    <tr class="bw-redirect-row">
                                                                                                        <td>
                                                                                                            <label>
                                                                                                                Inserisci il link d'arrivo
                                                                                                                <input type="text" name="bw_redirects[__index__][target_url]" value="" class="regular-text" placeholder="https://esempio.com/pagina" />
                                                                                                            </label>
                                                                                                            <p class="description">URL assoluto verso cui reindirizzare l'utente.</p>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <label>
                                                                                                                Inserisci il link di redirect
                                                                                                                <input type="text" name="bw_redirects[__index__][source_url]" value="" class="regular-text" placeholder="/promo/black-friday" />
                                                                                                            </label>
                                                                                                            <p class="description">Accetta un path relativo (es. /promo) o un URL completo.</p>
                                                                                                        </td>
                                                                                                        <td class="bw-redirect-actions">
                                                                                                            <button type="button" class="button button-link-delete bw-remove-redirect">Rimuovi</button>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                </script>

        <?php submit_button('Salva redirect', 'primary', 'bw_redirects_submit'); ?>
    </form>
    <?php
}

/**
 * Renderizza il tab BW Coming Soon
 */
function bw_site_render_coming_soon_tab()
{
    $settings_defaults = [
        'subscribe_enabled' => 0,
        'list_id_override' => 0,
        'channel_optin_mode' => 'inherit',
        'success_message' => __('Thanks for subscribing! Please check your inbox.', 'bw'),
        'error_message' => __('Unable to subscribe right now. Please try again later.', 'bw'),
    ];

    $saved_settings = get_option('bw_coming_soon_brevo_settings', []);
    if (!is_array($saved_settings)) {
        $saved_settings = [];
    }
    $brevo_settings = array_merge($settings_defaults, $saved_settings);

    // Salva le impostazioni se il form è stato inviato
    $saved = false;
    if (isset($_POST['bw_coming_soon_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_coming_soon_save', 'bw_coming_soon_nonce');

        $active_value = isset($_POST['bw_coming_soon_toggle']) ? 1 : 0;
        update_option('bw_coming_soon_active', $active_value);

        $channel_optin_mode = isset($_POST['bw_coming_soon_channel_optin_mode'])
            ? sanitize_key(wp_unslash($_POST['bw_coming_soon_channel_optin_mode']))
            : 'inherit';
        if (!in_array($channel_optin_mode, ['inherit', 'single_opt_in', 'double_opt_in'], true)) {
            $channel_optin_mode = 'inherit';
        }

        $updated_brevo_settings = [
            'subscribe_enabled' => !empty($_POST['bw_coming_soon_subscribe_enabled']) ? 1 : 0,
            'list_id_override' => isset($_POST['bw_coming_soon_list_id_override']) ? absint(wp_unslash($_POST['bw_coming_soon_list_id_override'])) : 0,
            'channel_optin_mode' => $channel_optin_mode,
            'success_message' => isset($_POST['bw_coming_soon_success_message'])
                ? sanitize_textarea_field(wp_unslash($_POST['bw_coming_soon_success_message']))
                : $settings_defaults['success_message'],
            'error_message' => isset($_POST['bw_coming_soon_error_message'])
                ? sanitize_textarea_field(wp_unslash($_POST['bw_coming_soon_error_message']))
                : $settings_defaults['error_message'],
        ];
        update_option('bw_coming_soon_brevo_settings', $updated_brevo_settings);
        $brevo_settings = array_merge($settings_defaults, $updated_brevo_settings);
        $saved = true;
    }

    $active = (int) get_option('bw_coming_soon_active', 0);
    ?>
    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_coming_soon_save', 'bw_coming_soon_nonce'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_toggle">Attiva modalità Coming Soon</label>
                </th>
                <td>
                    <input type="checkbox" id="bw_coming_soon_toggle" name="bw_coming_soon_toggle" value="1" <?php checked(1, $active); ?> />
                    <span class="description">Quando attivo, il sito mostrerà la pagina Coming Soon ai visitatori non
                        loggati.</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_subscribe_enabled"><?php esc_html_e('Enable Coming Soon subscribe form', 'bw'); ?></label>
                </th>
                <td>
                    <input
                        type="checkbox"
                        id="bw_coming_soon_subscribe_enabled"
                        name="bw_coming_soon_subscribe_enabled"
                        value="1"
                        <?php checked(1, (int) $brevo_settings['subscribe_enabled']); ?>
                    />
                    <span class="description"><?php esc_html_e('Use Mail Marketing Brevo settings for Coming Soon subscriptions.', 'bw'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_list_id_override"><?php esc_html_e('Brevo list override', 'bw'); ?></label>
                </th>
                <td>
                    <input
                        type="number"
                        id="bw_coming_soon_list_id_override"
                        name="bw_coming_soon_list_id_override"
                        value="<?php echo esc_attr((int) $brevo_settings['list_id_override']); ?>"
                        min="0"
                        step="1"
                        class="small-text"
                    />
                    <span class="description"><?php esc_html_e('Optional. Leave empty/0 to use Mail Marketing main list.', 'bw'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_channel_optin_mode"><?php esc_html_e('Channel opt-in mode', 'bw'); ?></label>
                </th>
                <td>
                    <select id="bw_coming_soon_channel_optin_mode" name="bw_coming_soon_channel_optin_mode">
                        <option value="inherit" <?php selected($brevo_settings['channel_optin_mode'], 'inherit'); ?>><?php esc_html_e('Inherit General setting', 'bw'); ?></option>
                        <option value="single_opt_in" <?php selected($brevo_settings['channel_optin_mode'], 'single_opt_in'); ?>><?php esc_html_e('Force single opt-in', 'bw'); ?></option>
                        <option value="double_opt_in" <?php selected($brevo_settings['channel_optin_mode'], 'double_opt_in'); ?>><?php esc_html_e('Force double opt-in', 'bw'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_success_message"><?php esc_html_e('Success message', 'bw'); ?></label>
                </th>
                <td>
                    <textarea
                        id="bw_coming_soon_success_message"
                        name="bw_coming_soon_success_message"
                        rows="3"
                        class="large-text"
                    ><?php echo esc_textarea((string) $brevo_settings['success_message']); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bw_coming_soon_error_message"><?php esc_html_e('Error message', 'bw'); ?></label>
                </th>
                <td>
                    <textarea
                        id="bw_coming_soon_error_message"
                        name="bw_coming_soon_error_message"
                        rows="3"
                        class="large-text"
                    ><?php echo esc_textarea((string) $brevo_settings['error_message']); ?></textarea>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva impostazioni', 'primary', 'bw_coming_soon_submit'); ?>
    </form>
    <?php
}

/**
 * Renderizza il tab Import Product.
 */
function bw_export_get_selected_category_id()
{
    $raw_value = '';

    if (isset($_POST['bw_export_category'])) {
        $raw_value = wp_unslash($_POST['bw_export_category']);
    } elseif (isset($_GET['bw_export_category'])) {
        $raw_value = wp_unslash($_GET['bw_export_category']);
    }

    return max(0, absint($raw_value));
}

function bw_export_get_selected_product_value()
{
    $raw_value = 'all';

    if (isset($_POST['bw_export_product'])) {
        $raw_value = sanitize_text_field(wp_unslash($_POST['bw_export_product']));
    } elseif (isset($_GET['bw_export_product'])) {
        $raw_value = sanitize_text_field(wp_unslash($_GET['bw_export_product']));
    }

    if ($raw_value === '' || $raw_value === 'all') {
        return 'all';
    }

    return (string) absint($raw_value);
}

function bw_export_get_include_variations()
{
    if (isset($_POST['bw_export_include_variations'])) {
        return !empty($_POST['bw_export_include_variations']);
    }

    return true;
}

function bw_export_get_category_options()
{
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $by_parent = [];
    foreach ($terms as $term) {
        $parent_id = isset($term->parent) ? (int) $term->parent : 0;
        if (!isset($by_parent[$parent_id])) {
            $by_parent[$parent_id] = [];
        }
        $by_parent[$parent_id][] = $term;
    }

    $options = [];
    bw_export_walk_category_terms($by_parent, 0, 0, $options);

    return $options;
}

function bw_export_walk_category_terms($by_parent, $parent_id, $depth, &$options)
{
    if (empty($by_parent[$parent_id]) || !is_array($by_parent[$parent_id])) {
        return;
    }

    foreach ($by_parent[$parent_id] as $term) {
        $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
        $options[(int) $term->term_id] = $prefix . $term->name;
        bw_export_walk_category_terms($by_parent, (int) $term->term_id, $depth + 1, $options);
    }
}

function bw_export_get_products_for_category($category_id)
{
    $query_args = [
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ];

    if ($category_id > 0) {
        $query_args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => [$category_id],
            'include_children' => true,
        ]];
    }

    $product_ids = get_posts($query_args);
    if (empty($product_ids)) {
        return [];
    }

    $options = [];
    foreach ($product_ids as $product_id) {
        $product_id = (int) $product_id;
        $sku = (string) get_post_meta($product_id, '_sku', true);
        $title = get_the_title($product_id);
        $label = $title !== '' ? $title : sprintf(__('Product #%d', 'bw'), $product_id);
        if ($sku !== '') {
            $label .= ' [' . $sku . ']';
        }
        $options[$product_id] = $label;
    }

    return $options;
}

function bw_export_get_template_columns()
{
    return [
        'row_type',
        'parent_sku',
        'post_id',
        'post_status',
        'post_title',
        'post_name',
        'post_content',
        'post_excerpt',
        'menu_order',
        'sku',
        'woo_product_type',
        'featured',
        'catalog_visibility',
        'virtual',
        'downloadable',
        'regular_price',
        'sale_price',
        'current_price',
        'date_on_sale_from',
        'date_on_sale_to',
        'tax_status',
        'tax_class',
        'manage_stock',
        'stock_quantity',
        'stock_status',
        'backorders',
        'sold_individually',
        'weight',
        'length',
        'width',
        'height',
        'shipping_class',
        'purchase_note',
        'reviews_allowed',
        'featured_image',
        'product_gallery',
        'product_cat',
        'product_subcategories',
        'product_tag',
        'upsell_skus',
        'cross_sell_skus',
        'grouped_skus',
        'external_url',
        'external_button_text',
        'download_limit',
        'download_expiry',
        'downloadable_files_json',
        'attributes_json',
        'default_attributes_json',
        'variation_attributes_json',
        'variation_regular_price',
        'variation_sale_price',
        'variation_stock_quantity',
        'variation_stock_status',
        'variation_manage_stock',
        'variation_backorders',
        'variation_weight',
        'variation_length',
        'variation_width',
        'variation_height',
        'variation_description',
        '_bw_product_type',
        '_bw_texts_color',
        '_bw_showcase_image',
        '_bw_showcase_title',
        '_bw_showcase_description',
        '_bw_file_size',
        '_bw_assets_count',
        '_bw_formats',
        '_bw_info_1',
        '_bw_info_2',
        '_product_button_text',
        '_product_button_link',
        '_bw_showcase_linked_product',
        '_bw_slider_hover_image',
        '_bw_slider_hover_video',
        '_bw_biblio_title',
        '_bw_biblio_author',
        '_bw_biblio_publisher',
        '_bw_biblio_year',
        '_bw_biblio_language',
        '_bw_biblio_binding',
        '_bw_biblio_pages',
        '_bw_biblio_edition',
        '_bw_biblio_condition',
        '_bw_biblio_location',
        '_print_artist',
        '_print_publisher',
        '_print_year',
        '_print_technique',
        '_print_material',
        '_print_plate_size',
        '_print_condition',
        '_digital_total_assets',
        '_digital_assets_list',
        '_digital_file_size',
        '_digital_formats',
        '_bw_artist_name',
        '_digital_artist_name',
        '_digital_source',
        '_digital_publisher',
        '_digital_year',
        '_digital_technique',
        '_bw_compatibility_configured',
        '_bw_compatibility_adobe_illustrator_photoshop',
        '_bw_compatibility_figma_sketch_adobe_xd',
        '_bw_compatibility_affinity_designer_photo',
        '_bw_compatibility_coreldraw_inkscape',
        '_bw_compatibility_canva_powerpoint',
        '_bw_compatibility_cricut_silhouette',
        '_bw_compatibility_blender_cinema4d',
        '_product_showcase_image',
        '_product_size_mb',
        '_product_assets_count',
        '_product_formats',
        '_product_color',
        '_bw_variation_license_col1_json',
        '_bw_variation_license_col2_json',
    ];
}

function bw_export_handle_request()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_export_permission', __('You do not have permission to export products.', 'bw'));
    }

    if (!isset($_POST['bw_export_products_nonce']) || !wp_verify_nonce($_POST['bw_export_products_nonce'], 'bw_export_products')) {
        return new WP_Error('bw_export_nonce', __('Invalid export nonce. Please try again.', 'bw'));
    }

    $category_id = bw_export_get_selected_category_id();
    $product_value = bw_export_get_selected_product_value();
    $include_variations = bw_export_get_include_variations();

    $product_ids = bw_export_resolve_product_ids($category_id, $product_value);
    if (empty($product_ids)) {
        return new WP_Error('bw_export_empty', __('No matching products were found for this export.', 'bw'));
    }

    bw_export_stream_csv($product_ids, $include_variations, $category_id, $product_value);
    exit;
}

/**
 * Process product export before the admin page sends HTML output.
 *
 * Successful exports stream the CSV and exit. Failed exports are stored so the
 * tab renderer can show a standard admin notice without retrying the request.
 */
function bw_export_maybe_handle_admin_request()
{
    if (!is_admin() || !isset($_POST['bw_export_products_submit'])) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';

    if ($page !== 'blackwork-site-settings' || $tab !== 'import-product') {
        return;
    }

    $GLOBALS['bw_export_request_result'] = bw_export_handle_request();
}
add_action('admin_init', 'bw_export_maybe_handle_admin_request', 5);

function bw_export_resolve_product_ids($category_id, $product_value)
{
    if ($product_value !== 'all') {
        $product_id = absint($product_value);
        return $product_id > 0 ? [$product_id] : [];
    }

    $product_ids = [];
    $paged = 1;

    do {
        $query_args = [
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 200,
            'paged' => $paged,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'suppress_filters' => false,
        ];

        if ($category_id > 0) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => [$category_id],
                'include_children' => true,
            ]];
        }

        $query = new WP_Query($query_args);
        if (!empty($query->posts)) {
            foreach ($query->posts as $product_id) {
                $product_ids[] = (int) $product_id;
            }
        }

        $has_more = $query->max_num_pages > $paged;
        wp_reset_postdata();
        $paged++;
    } while ($has_more);

    return $product_ids;
}

function bw_export_stream_csv($product_ids, $include_variations, $category_id, $product_value)
{
    $columns = bw_export_get_request_columns($product_ids, $include_variations);
    $filename = bw_export_build_filename($category_id, $product_value);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');

    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open export stream.', 'bw'));
    }

    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $columns);

    foreach ((array) $product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product || $product->is_type('variation')) {
            continue;
        }

        $row = bw_export_build_product_csv_row($product);
        fputcsv($output, bw_export_order_row_for_csv($row, $columns));

        if ($include_variations && $product->is_type('variable')) {
            foreach ((array) $product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation instanceof WC_Product_Variation) {
                    continue;
                }

                $variation_row = bw_export_build_variation_csv_row($variation, $product);
                fputcsv($output, bw_export_order_row_for_csv($variation_row, $columns));
            }
        }
    }

    fclose($output);
}

function bw_export_get_request_columns($product_ids, $include_variations)
{
    $columns = bw_export_get_template_columns();
    $dynamic_meta_columns = bw_export_get_dynamic_meta_columns($product_ids, $include_variations);

    foreach ($dynamic_meta_columns as $meta_key) {
        if (!in_array($meta_key, $columns, true)) {
            $columns[] = $meta_key;
        }
    }

    return $columns;
}

function bw_export_build_filename($category_id, $product_value)
{
    $date_suffix = gmdate('Y-m-d-His');
    $parts = ['bw-product-export'];

    if ($category_id > 0) {
        $term = get_term($category_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $parts[] = sanitize_title($term->slug);
        }
    } else {
        $parts[] = 'all-categories';
    }

    if ($product_value !== 'all') {
        $parts[] = 'product-' . absint($product_value);
    } else {
        $parts[] = 'all-products';
    }

    $parts[] = $date_suffix;

    return implode('-', $parts) . '.csv';
}

function bw_product_transfer_get_active_mode()
{
    $mode = 'export';

    if (isset($_POST['bw_import_upload_submit']) || isset($_POST['bw_import_run'])) {
        $mode = 'import';
    } elseif (isset($_POST['bw_export_products_submit'])) {
        $mode = 'export';
    } elseif (isset($_GET['product_flow'])) {
        $requested = sanitize_key(wp_unslash($_GET['product_flow']));
        if (in_array($requested, ['export', 'import'], true)) {
            $mode = $requested;
        }
    }

    return $mode;
}

function bw_export_order_row_for_csv($row, $columns)
{
    $ordered = [];
    foreach ($columns as $column) {
        $value = isset($row[$column]) ? $row[$column] : '';
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value);
        }
        $ordered[] = $value;
    }

    return $ordered;
}

function bw_export_blank_csv_row()
{
    return array_fill_keys(bw_export_get_template_columns(), '');
}

function bw_export_get_dynamic_meta_columns($product_ids, $include_variations)
{
    global $wpdb;

    $scan_post_ids = bw_export_get_meta_scan_post_ids($product_ids, $include_variations);
    if (empty($scan_post_ids)) {
        return [];
    }

    $meta_keys = [];

    foreach (array_chunk($scan_post_ids, 250) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders)",
            $chunk
        );

        $results = $wpdb->get_col($query);
        if (empty($results)) {
            continue;
        }

        foreach ($results as $meta_key) {
            if (!is_string($meta_key) || bw_export_should_skip_dynamic_meta_key($meta_key)) {
                continue;
            }

            $meta_keys[$meta_key] = true;
        }
    }

    $columns = array_keys($meta_keys);
    natcasesort($columns);

    return array_values($columns);
}

function bw_export_get_meta_scan_post_ids($product_ids, $include_variations)
{
    $scan_post_ids = [];

    foreach ((array) $product_ids as $product_id) {
        $product_id = absint($product_id);
        if ($product_id < 1) {
            continue;
        }

        $scan_post_ids[$product_id] = true;

        if (!$include_variations) {
            continue;
        }

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            continue;
        }

        foreach ((array) $product->get_children() as $variation_id) {
            $variation_id = absint($variation_id);
            if ($variation_id > 0) {
                $scan_post_ids[$variation_id] = true;
            }
        }
    }

    return array_map('intval', array_keys($scan_post_ids));
}

function bw_export_should_skip_dynamic_meta_key($meta_key)
{
    static $blocked_exact = [
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_old_date',
        '_wp_desired_post_slug',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
    ];

    if ($meta_key === '') {
        return true;
    }

    if (in_array($meta_key, $blocked_exact, true)) {
        return true;
    }

    return strpos($meta_key, '_oembed_') === 0;
}

function bw_export_apply_dynamic_meta_to_row(&$row, $all_meta)
{
    foreach ((array) $all_meta as $meta_key => $values) {
        if (!is_string($meta_key) || bw_export_should_skip_dynamic_meta_key($meta_key)) {
            continue;
        }

        $normalized_values = array_map('maybe_unserialize', (array) $values);
        if (count($normalized_values) === 1) {
            $row[$meta_key] = bw_export_meta_scalar($normalized_values[0]);
            continue;
        }

        $row[$meta_key] = bw_export_meta_scalar($normalized_values);
    }
}

function bw_export_build_product_csv_row($product)
{
    $post_id = $product->get_id();
    $post = get_post($post_id);
    $all_meta = get_post_meta($post_id);
    $get_meta = static function ($key) use ($all_meta) {
        return isset($all_meta[$key][0]) ? maybe_unserialize($all_meta[$key][0]) : '';
    };

    $row = bw_export_blank_csv_row();
    $row['row_type'] = 'product';
    $row['post_id'] = $post_id;
    $row['post_status'] = $post ? $post->post_status : '';
    $row['post_title'] = $post ? $post->post_title : '';
    $row['post_name'] = $post ? $post->post_name : '';
    $row['post_content'] = $post ? $post->post_content : '';
    $row['post_excerpt'] = $post ? $post->post_excerpt : '';
    $row['menu_order'] = $post ? (string) $post->menu_order : '';
    $row['sku'] = (string) $product->get_sku();
    $row['woo_product_type'] = (string) $product->get_type();
    $row['featured'] = bw_export_bool_flag($product->is_featured());
    $row['catalog_visibility'] = method_exists($product, 'get_catalog_visibility') ? (string) $product->get_catalog_visibility() : '';
    $row['virtual'] = bw_export_bool_flag($product->is_virtual());
    $row['downloadable'] = bw_export_bool_flag($product->is_downloadable());
    $row['regular_price'] = (string) $product->get_regular_price();
    $row['sale_price'] = (string) $product->get_sale_price();
    $row['current_price'] = (string) $product->get_price();
    $row['date_on_sale_from'] = bw_export_format_wc_date($product->get_date_on_sale_from());
    $row['date_on_sale_to'] = bw_export_format_wc_date($product->get_date_on_sale_to());
    $row['tax_status'] = (string) $product->get_tax_status();
    $row['tax_class'] = (string) $product->get_tax_class();
    $row['manage_stock'] = bw_export_bool_flag($product->get_manage_stock());
    $row['stock_quantity'] = bw_export_scalar_or_empty($product->get_stock_quantity());
    $row['stock_status'] = (string) $product->get_stock_status();
    $row['backorders'] = (string) $product->get_backorders();
    $row['sold_individually'] = bw_export_bool_flag($product->get_sold_individually());
    $row['weight'] = (string) $product->get_weight();
    $row['length'] = (string) $product->get_length();
    $row['width'] = (string) $product->get_width();
    $row['height'] = (string) $product->get_height();
    $row['shipping_class'] = (string) $product->get_shipping_class();
    $row['purchase_note'] = (string) $product->get_purchase_note();
    $row['reviews_allowed'] = bw_export_bool_flag(comments_open($post_id));
    $row['featured_image'] = bw_export_attachment_url($product->get_image_id());
    $row['product_gallery'] = implode(',', bw_export_attachment_urls($product->get_gallery_image_ids()));
    $row['product_cat'] = implode(',', bw_export_get_product_term_slugs($post_id, 'product_cat'));
    $row['product_subcategories'] = implode(',', bw_export_get_product_subcategory_slugs($post_id));
    $row['product_tag'] = implode(',', bw_export_get_product_term_slugs($post_id, 'product_tag'));
    $row['upsell_skus'] = implode(',', bw_export_resolve_product_skus($product->get_upsell_ids()));
    $row['cross_sell_skus'] = implode(',', bw_export_resolve_product_skus($product->get_cross_sell_ids()));
    $row['grouped_skus'] = implode(',', bw_export_get_grouped_child_skus($product));
    $row['external_url'] = $product->is_type('external') ? (string) $product->get_product_url() : '';
    $row['external_button_text'] = $product->is_type('external') ? (string) $product->get_button_text() : '';
    $row['download_limit'] = bw_export_scalar_or_empty($product->get_download_limit());
    $row['download_expiry'] = bw_export_scalar_or_empty($product->get_download_expiry());
    $row['downloadable_files_json'] = bw_export_json(bw_export_get_downloads_payload($product));
    $row['attributes_json'] = bw_export_json(bw_export_build_attributes_payload($product));
    $row['default_attributes_json'] = bw_export_json($product->is_type('variable') ? $product->get_default_attributes() : []);

    foreach ([
        '_bw_product_type',
        '_bw_texts_color',
        '_bw_showcase_title',
        '_bw_showcase_description',
        '_bw_file_size',
        '_bw_assets_count',
        '_bw_formats',
        '_bw_info_1',
        '_bw_info_2',
        '_product_button_text',
        '_product_button_link',
        '_bw_showcase_linked_product',
        '_bw_biblio_title',
        '_bw_biblio_author',
        '_bw_biblio_publisher',
        '_bw_biblio_year',
        '_bw_biblio_language',
        '_bw_biblio_binding',
        '_bw_biblio_pages',
        '_bw_biblio_edition',
        '_bw_biblio_condition',
        '_bw_biblio_location',
        '_print_artist',
        '_print_publisher',
        '_print_year',
        '_print_technique',
        '_print_material',
        '_print_plate_size',
        '_print_condition',
        '_digital_total_assets',
        '_digital_assets_list',
        '_digital_file_size',
        '_digital_formats',
        '_bw_artist_name',
        '_digital_artist_name',
        '_digital_source',
        '_digital_publisher',
        '_digital_year',
        '_digital_technique',
        '_bw_compatibility_configured',
        '_bw_compatibility_adobe_illustrator_photoshop',
        '_bw_compatibility_figma_sketch_adobe_xd',
        '_bw_compatibility_affinity_designer_photo',
        '_bw_compatibility_coreldraw_inkscape',
        '_bw_compatibility_canva_powerpoint',
        '_bw_compatibility_cricut_silhouette',
        '_bw_compatibility_blender_cinema4d',
        '_product_size_mb',
        '_product_assets_count',
        '_product_formats',
        '_product_color',
    ] as $meta_key) {
        $row[$meta_key] = bw_export_meta_scalar($get_meta($meta_key));
    }

    $row['_bw_showcase_image'] = bw_export_media_meta_value($get_meta('_bw_showcase_image'));
    $row['_bw_slider_hover_image'] = bw_export_media_meta_value($get_meta('_bw_slider_hover_image'));
    $row['_bw_slider_hover_video'] = bw_export_media_meta_value($get_meta('_bw_slider_hover_video'));
    $row['_product_showcase_image'] = bw_export_media_meta_value($get_meta('_product_showcase_image'));
    bw_export_apply_dynamic_meta_to_row($row, $all_meta);

    return $row;
}

function bw_export_build_variation_csv_row($variation, $parent_product)
{
    $variation_id = $variation->get_id();
    $post = get_post($variation_id);
    $all_meta = get_post_meta($variation_id);
    $get_meta = static function ($key) use ($all_meta) {
        return isset($all_meta[$key][0]) ? maybe_unserialize($all_meta[$key][0]) : '';
    };

    $row = bw_export_blank_csv_row();
    $row['row_type'] = 'variation';
    $row['parent_sku'] = (string) $parent_product->get_sku();
    $row['post_id'] = $variation_id;
    $row['post_status'] = $post ? $post->post_status : '';
    $row['post_title'] = $post ? $post->post_title : '';
    $row['post_name'] = $post ? $post->post_name : '';
    $row['post_content'] = $post ? $post->post_content : '';
    $row['post_excerpt'] = $post ? $post->post_excerpt : '';
    $row['menu_order'] = $post ? (string) $post->menu_order : '';
    $row['sku'] = (string) $variation->get_sku();
    $row['woo_product_type'] = 'variation';
    $row['virtual'] = bw_export_bool_flag($variation->is_virtual());
    $row['downloadable'] = bw_export_bool_flag($variation->is_downloadable());
    $row['regular_price'] = (string) $variation->get_regular_price();
    $row['sale_price'] = (string) $variation->get_sale_price();
    $row['current_price'] = (string) $variation->get_price();
    $row['date_on_sale_from'] = bw_export_format_wc_date($variation->get_date_on_sale_from());
    $row['date_on_sale_to'] = bw_export_format_wc_date($variation->get_date_on_sale_to());
    $row['tax_status'] = (string) $variation->get_tax_status();
    $row['tax_class'] = (string) $variation->get_tax_class();
    $row['manage_stock'] = bw_export_bool_flag($variation->get_manage_stock());
    $row['stock_quantity'] = bw_export_scalar_or_empty($variation->get_stock_quantity());
    $row['stock_status'] = (string) $variation->get_stock_status();
    $row['backorders'] = (string) $variation->get_backorders();
    $row['weight'] = (string) $variation->get_weight();
    $row['length'] = (string) $variation->get_length();
    $row['width'] = (string) $variation->get_width();
    $row['height'] = (string) $variation->get_height();
    $row['featured_image'] = bw_export_attachment_url($variation->get_image_id());
    $row['download_limit'] = bw_export_scalar_or_empty($variation->get_download_limit());
    $row['download_expiry'] = bw_export_scalar_or_empty($variation->get_download_expiry());
    $row['downloadable_files_json'] = bw_export_json(bw_export_get_downloads_payload($variation));
    $row['variation_attributes_json'] = bw_export_json($variation->get_variation_attributes());
    $row['variation_regular_price'] = (string) $variation->get_regular_price();
    $row['variation_sale_price'] = (string) $variation->get_sale_price();
    $row['variation_stock_quantity'] = bw_export_scalar_or_empty($variation->get_stock_quantity());
    $row['variation_stock_status'] = (string) $variation->get_stock_status();
    $row['variation_manage_stock'] = bw_export_bool_flag($variation->get_manage_stock());
    $row['variation_backorders'] = (string) $variation->get_backorders();
    $row['variation_weight'] = (string) $variation->get_weight();
    $row['variation_length'] = (string) $variation->get_length();
    $row['variation_width'] = (string) $variation->get_width();
    $row['variation_height'] = (string) $variation->get_height();
    $row['variation_description'] = (string) $variation->get_description();
    $row['_bw_variation_license_col1_json'] = bw_export_json($get_meta('_bw_variation_license_col1'));
    $row['_bw_variation_license_col2_json'] = bw_export_json($get_meta('_bw_variation_license_col2'));
    bw_export_apply_dynamic_meta_to_row($row, $all_meta);

    return $row;
}

function bw_export_bool_flag($value)
{
    return !empty($value) ? 'yes' : 'no';
}

function bw_export_scalar_or_empty($value)
{
    if ($value === null || $value === '') {
        return '';
    }

    return is_scalar($value) ? (string) $value : '';
}

function bw_export_format_wc_date($value)
{
    if ($value instanceof WC_DateTime) {
        return $value->date_i18n('Y-m-d');
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (is_string($value) && $value !== '') {
        return $value;
    }

    return '';
}

function bw_export_attachment_url($attachment_id)
{
    $attachment_id = absint($attachment_id);
    if ($attachment_id < 1) {
        return '';
    }

    $url = wp_get_attachment_url($attachment_id);
    return $url ? (string) $url : '';
}

function bw_export_attachment_urls($attachment_ids)
{
    $urls = [];
    foreach ((array) $attachment_ids as $attachment_id) {
        $url = bw_export_attachment_url($attachment_id);
        if ($url !== '') {
            $urls[] = $url;
        }
    }

    return $urls;
}

function bw_export_get_product_term_slugs($product_id, $taxonomy)
{
    $terms = wp_get_post_terms($product_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $slugs = [];
    foreach ($terms as $term) {
        if (!empty($term->slug)) {
            $slugs[] = (string) $term->slug;
        }
    }

    return $slugs;
}

function bw_export_get_product_subcategory_slugs($product_id)
{
    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $slugs = [];
    foreach ($terms as $term) {
        if ((int) $term->parent > 0 && !empty($term->slug)) {
            $slugs[] = (string) $term->slug;
        }
    }

    return $slugs;
}

function bw_export_resolve_product_skus($product_ids)
{
    $values = [];
    foreach ((array) $product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            continue;
        }

        $sku = (string) $product->get_sku();
        $values[] = $sku !== '' ? $sku : (string) $product_id;
    }

    return $values;
}

function bw_export_get_grouped_child_skus($product)
{
    if (!$product->is_type('grouped')) {
        return [];
    }

    return bw_export_resolve_product_skus($product->get_children());
}

function bw_export_get_downloads_payload($product)
{
    if (!method_exists($product, 'get_downloads')) {
        return [];
    }

    $downloads = $product->get_downloads();
    if (empty($downloads)) {
        return [];
    }

    $payload = [];
    foreach ($downloads as $download_id => $download) {
        if (!is_object($download)) {
            continue;
        }

        $payload[] = [
            'id' => (string) $download_id,
            'name' => method_exists($download, 'get_name') ? (string) $download->get_name() : '',
            'file' => method_exists($download, 'get_file') ? (string) $download->get_file() : '',
        ];
    }

    return $payload;
}

function bw_export_build_attributes_payload($product)
{
    $attributes = $product->get_attributes();
    if (empty($attributes)) {
        return [];
    }

    $payload = [];
    foreach ($attributes as $attribute) {
        if (!$attribute instanceof WC_Product_Attribute) {
            continue;
        }

        $options = [];
        if ($attribute->is_taxonomy()) {
            foreach ((array) $attribute->get_options() as $term_id) {
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) {
                    $options[] = !empty($term->slug) ? (string) $term->slug : (string) $term->name;
                }
            }
        } else {
            foreach ((array) $attribute->get_options() as $option) {
                if (is_scalar($option) && $option !== '') {
                    $options[] = (string) $option;
                }
            }
        }

        $payload[] = [
            'name' => (string) $attribute->get_name(),
            'label' => $attribute->is_taxonomy() ? wc_attribute_label($attribute->get_name()) : (string) $attribute->get_name(),
            'options' => $options,
            'visible' => !empty($attribute->get_visible()),
            'variation' => !empty($attribute->get_variation()),
            'position' => (int) $attribute->get_position(),
        ];
    }

    return $payload;
}

function bw_export_json($value)
{
    if (empty($value)) {
        return '';
    }

    $encoded = wp_json_encode($value);
    return is_string($encoded) ? $encoded : '';
}

function bw_export_meta_scalar($value)
{
    if (is_array($value) || is_object($value)) {
        return bw_export_json($value);
    }

    return is_scalar($value) ? (string) $value : '';
}

function bw_export_media_meta_value($value)
{
    if (is_numeric($value)) {
        $url = bw_export_attachment_url((int) $value);
        if ($url !== '') {
            return $url;
        }
    }

    return bw_export_meta_scalar($value);
}

function bw_site_render_import_product_tab()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return;
    }

    $notices = [];
    $state = bw_import_get_state();
    $active_mode = bw_product_transfer_get_active_mode();
    $selected_export_category_id = bw_export_get_selected_category_id();
    $selected_export_product_value = bw_export_get_selected_product_value();

    if (isset($GLOBALS['bw_export_request_result'])) {
        $export_result = $GLOBALS['bw_export_request_result'];
        if (is_wp_error($export_result)) {
            $notices[] = ['type' => 'error', 'message' => $export_result->get_error_message()];
        }
    }

    if (isset($_POST['bw_import_upload_submit'])) {
        $upload_result = bw_import_handle_upload_request();
        if (is_wp_error($upload_result)) {
            $notices[] = ['type' => 'error', 'message' => $upload_result->get_error_message()];
        } else {
            $state = $upload_result;
            $notices[] = ['type' => 'success', 'message' => __('CSV uploaded successfully. Configure the mapping below.', 'bw')];
        }
    }

    if (isset($_POST['bw_import_run'])) {
        $import_result = bw_import_handle_run_request($state);

        if (is_wp_error($import_result)) {
            $notices[] = ['type' => 'error', 'message' => $import_result->get_error_message()];
        } elseif (!empty($import_result['message'])) {
            $notices[] = ['type' => 'success', 'message' => esc_html($import_result['message'])];
        }
    }

    if (!empty($notices)) {
        foreach ($notices as $notice) {
            $class = $notice['type'] === 'error' ? 'notice-error' : 'notice-success';
            ?>
            <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
                <p><?php echo esc_html($notice['message']); ?></p>
            </div>
            <?php
        }
    }

    $state = bw_import_get_state();
    $export_category_options = bw_export_get_category_options();
    $export_product_options = $selected_export_category_id > 0 ? bw_export_get_products_for_category($selected_export_category_id) : [];
    if ($selected_export_product_value !== 'all') {
        $selected_product_id = absint($selected_export_product_value);
        if ($selected_product_id < 1 || !isset($export_product_options[$selected_product_id])) {
            $selected_export_product_value = 'all';
        }
    }
    ?>
    <section class="bw-admin-card">
        <h2 class="bw-admin-card-title"><?php esc_html_e('Product Import / Export', 'bw'); ?></h2>
        <p class="bw-admin-card-helper"><?php esc_html_e('Use Export to generate a structured product CSV, or switch to Import to upload and map a CSV back into WooCommerce.', 'bw'); ?></p>

        <nav class="nav-tab-wrapper bw-admin-tabs" style="margin-top:12px;">
            <a href="?page=blackwork-site-settings&tab=import-product&product_flow=export"
                class="nav-tab <?php echo $active_mode === 'export' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Export Product', 'bw'); ?>
            </a>
            <a href="?page=blackwork-site-settings&tab=import-product&product_flow=import"
                class="nav-tab <?php echo $active_mode === 'import' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Import Product', 'bw'); ?>
            </a>
        </nav>
    </section>

    <?php if ($active_mode === 'export') : ?>
        <section class="bw-admin-card">
            <h3 class="bw-admin-card-title"><?php esc_html_e('Export Product', 'bw'); ?></h3>
            <p class="bw-admin-card-helper"><?php esc_html_e('Export a category or a single product to a master CSV with WooCommerce standard fields, Blackwork meta keys, and the raw product meta keys detected on the exported products.', 'bw'); ?></p>

            <form method="get" style="max-width: 760px;">
                <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['page']))) : 'blackwork-site-settings'; ?>" />
                <input type="hidden" name="tab" value="import-product" />
                <input type="hidden" name="product_flow" value="export" />
                <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="bw_export_category"><?php esc_html_e('Category', 'bw'); ?></label></th>
                            <td>
                                <select id="bw_export_category" name="bw_export_category" style="min-width: 320px;">
                                    <option value="0" <?php selected($selected_export_category_id, 0); ?>>
                                        <?php esc_html_e('All product categories', 'bw'); ?>
                                    </option>
                                    <?php foreach ($export_category_options as $term_id => $term_label): ?>
                                        <option value="<?php echo esc_attr($term_id); ?>" <?php selected($selected_export_category_id, (int) $term_id); ?>>
                                            <?php echo esc_html($term_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Choose a category first if you want a smaller product list. Leave it on all categories to export the whole catalog.', 'bw'); ?></p>
                                <p style="margin-top:10px;"><?php submit_button(__('Load Products', 'bw'), 'secondary', '', false); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>

            <form method="post" style="max-width: 760px;">
                <?php wp_nonce_field('bw_export_products', 'bw_export_products_nonce'); ?>
                <input type="hidden" name="product_flow" value="export" />
                <input type="hidden" name="bw_export_category" value="<?php echo esc_attr($selected_export_category_id); ?>" />
                <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="bw_export_product"><?php esc_html_e('Product', 'bw'); ?></label></th>
                            <td>
                                <select id="bw_export_product" name="bw_export_product" style="min-width: 420px;">
                                    <option value="all" <?php selected($selected_export_product_value, 'all'); ?>>
                                        <?php echo $selected_export_category_id > 0 ? esc_html__('All products in selected category', 'bw') : esc_html__('All products', 'bw'); ?>
                                    </option>
                                    <?php foreach ($export_product_options as $product_id => $product_label): ?>
                                        <option value="<?php echo esc_attr($product_id); ?>" <?php selected((string) $selected_export_product_value, (string) $product_id); ?>>
                                            <?php echo esc_html($product_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php
                                    if ($selected_export_category_id > 0) {
                                        esc_html_e('Export one product from the selected category, or keep "All products in selected category" to export the full category set.', 'bw');
                                    } else {
                                        esc_html_e('Without a category filter, this will export the whole product catalog or one manually selected product if available.', 'bw');
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Options', 'bw'); ?></th>
                            <td>
                                <label style="display:inline-flex; gap:8px; align-items:center;">
                                    <input type="checkbox" name="bw_export_include_variations" value="1" <?php checked(bw_export_get_include_variations()); ?> />
                                    <span><?php esc_html_e('Include variation rows for variable products', 'bw'); ?></span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(__('Export CSV', 'bw'), 'primary', 'bw_export_products_submit', false); ?>
            </form>
        </section>
    <?php endif; ?>

    <?php if ($active_mode === 'import') : ?>
        <section class="bw-admin-card">
            <h3 class="bw-admin-card-title"><?php esc_html_e('Import Product', 'bw'); ?></h3>
            <p class="bw-admin-card-helper"><?php esc_html_e('Upload a CSV file to import or update WooCommerce products and custom meta fields.', 'bw'); ?></p>

        <h3><?php esc_html_e('1. Upload CSV', 'bw'); ?></h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_flow" value="import" />
            <?php wp_nonce_field('bw_import_upload', 'bw_import_upload_nonce'); ?>
            <input type="file" name="bw_import_csv" accept=".csv" />
            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px; max-width: 620px;">
                <strong><?php esc_html_e('Update existing products', 'bw'); ?></strong>
                <label style="display: flex; gap: 8px; align-items: flex-start;">
                    <input type="checkbox" name="bw_import_update_existing" value="1" <?php checked(!empty($state['update_existing'])); ?> />
                    <span><?php esc_html_e('Existing products that match by ID or SKU will be updated. Products that do not exist will be skipped.', 'bw'); ?></span>
                </label>
            </div>
            <?php submit_button(__('Upload & Analyze', 'bw'), 'primary', 'bw_import_upload_submit', false); ?>
        </form>

        <?php if (!empty($state['upload_summary'])): ?>
            <hr />
            <h3><?php esc_html_e('Upload summary', 'bw'); ?></h3>
            <table class="widefat fixed" style="max-width:700px;">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Uploaded file', 'bw'); ?></th>
                        <td><?php echo esc_html($state['upload_summary']['file_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Total fields in file', 'bw'); ?></th>
                        <td><?php echo (int) $state['upload_summary']['total_fields']; ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Fields detected', 'bw'); ?></th>
                        <td><?php echo (int) $state['upload_summary']['loaded_fields']; ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Missing field names', 'bw'); ?></th>
                        <td>
                            <?php if (!empty($state['upload_summary']['missing'])): ?>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($state['upload_summary']['missing'] as $missing_header): ?>
                                        <li><?php echo esc_html($missing_header); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?php esc_html_e('All fields were loaded successfully.', 'bw'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Replaced fields', 'bw'); ?></th>
                        <td>
                            <?php
                            $replaced_count = isset($state['upload_summary']['replaced_count']) ? (int) $state['upload_summary']['replaced_count'] : 0;
                            if ($replaced_count > 0):
                                ?>
                                <strong><?php echo esc_html(sprintf(__('Replaced headers: %d', 'bw'), $replaced_count)); ?></strong>
                                <ul style="margin: 4px 0 0 20px;">
                                    <?php foreach ((array) $state['upload_summary']['replaced'] as $replaced_header): ?>
                                        <li><?php echo esc_html($replaced_header); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?php esc_html_e('No empty headers were replaced.', 'bw'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Duplicate headers', 'bw'); ?></th>
                        <td>
                            <?php
                            $duplicate_count = isset($state['upload_summary']['duplicate_count']) ? (int) $state['upload_summary']['duplicate_count'] : 0;
                            $duplicates = isset($state['upload_summary']['duplicates']) ? (array) $state['upload_summary']['duplicates'] : [];
                            if ($duplicate_count > 0):
                                ?>
                                <strong><?php echo esc_html(sprintf(__('Duplicated fields: %d', 'bw'), $duplicate_count)); ?></strong>
                                <ul style="margin: 4px 0 0 20px;">
                                    <?php foreach ($duplicates as $header => $positions): ?>
                                        <li>
                                            <?php
                                            echo esc_html(
                                                sprintf(
                                                    /* translators: 1: header label, 2: column positions */
                                                    __('%1$s (columns: %2$s)', 'bw'),
                                                    $header,
                                                    implode(', ', array_map('intval', (array) $positions))
                                                )
                                            );
                                            ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <?php esc_html_e('No duplicate header names detected.', 'bw'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($state['headers'])): ?>
            <hr />
            <h3><?php esc_html_e('2. Map CSV columns', 'bw'); ?></h3>
            <p><?php esc_html_e('Match each CSV column to a WooCommerce field or a custom meta field.', 'bw'); ?></p>

            <form method="post">
                <input type="hidden" name="product_flow" value="import" />
                <?php wp_nonce_field('bw_import_run', 'bw_import_run_nonce'); ?>
                <?php
                $state_skip_images = array_key_exists('skip_images', $state) ? !empty($state['skip_images']) : true;
                ?>
                <table class="widefat fixed" style="max-width:900px;">
                    <thead>
                        <tr>
                            <th style="width:50%;"><?php esc_html_e('CSV Column', 'bw'); ?></th>
                            <th><?php esc_html_e('Map To', 'bw'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $options = bw_import_get_mapping_options();
                        $auto_mapping = bw_import_guess_mapping($state['headers'], $options);
                        $submitted_mapping = [];

                        if (!empty($_POST['bw_import_mapping'])) {
                            foreach ((array) $_POST['bw_import_mapping'] as $submitted_header => $submitted_value) {
                                $submitted_mapping[$submitted_header] = sanitize_text_field(wp_unslash($submitted_value));
                            }
                        }

                        foreach ($state['headers'] as $header):
                            $current_value = isset($submitted_mapping[$header])
                                ? $submitted_mapping[$header]
                                : (isset($auto_mapping[$header]) ? $auto_mapping[$header] : 'ignore');
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($header); ?></strong></td>
                                <td>
                                    <select name="bw_import_mapping[<?php echo esc_attr($header); ?>]" style="width:100%;">
                                        <option value="ignore" <?php selected($current_value, 'ignore'); ?>>
                                            <?php esc_html_e('Ignore this column', 'bw'); ?>
                                        </option>
                                        <?php foreach ($options as $group_label => $group_options): ?>
                                            <optgroup label="<?php echo esc_attr($group_label); ?>">
                                                <?php foreach ($group_options as $key => $label): ?>
                                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_value, $key); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                        endforeach;
                        ?>
                    </tbody>
                </table>

                <p><strong><?php esc_html_e('Preview (first 5 rows):', 'bw'); ?></strong></p>
                <div style="overflow:auto; max-width:900px;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <?php foreach ($state['headers'] as $header): ?>
                                    <th><?php echo esc_html($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($state['sample'] as $sample_row): ?>
                                <tr>
                                    <?php foreach ($state['headers'] as $index => $header): ?>
                                        <td><?php echo isset($sample_row[$index]) ? esc_html($sample_row[$index]) : ''; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 6px; max-width: 620px;">
                    <strong><?php esc_html_e('Image import behavior', 'bw'); ?></strong>
                    <label style="display: flex; gap: 8px; align-items: flex-start;">
                        <input type="checkbox" name="bw_import_skip_images" value="1" <?php checked($state_skip_images); ?> />
                        <span><?php esc_html_e('Skip image sideload for safety (recommended). Image columns are ignored and logged as skipped by configuration.', 'bw'); ?></span>
                    </label>
                </div>

                <?php
                $button_label = !empty($state['in_progress'])
                    ? __('Continue Import (next chunk)', 'bw')
                    : __('Save Mapping & Run Import', 'bw');
                submit_button($button_label, 'primary', 'bw_import_run');
                ?>
            </form>
        <?php endif; ?>

        <?php if (!empty($state['in_progress']) && !empty($state['totals']) && !empty($state['headers'])): ?>
            <hr />
            <h3><?php esc_html_e('Import progress', 'bw'); ?></h3>
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: row cursor, 2: created count, 3: updated count, 4: skipped count */
                        __('Processed rows: %1$d — Created: %2$d, Updated: %3$d, Skipped: %4$d', 'bw'),
                        isset($state['row_cursor']) ? (int) $state['row_cursor'] : 0,
                        isset($state['totals']['created']) ? (int) $state['totals']['created'] : 0,
                        isset($state['totals']['updated']) ? (int) $state['totals']['updated'] : 0,
                        isset($state['totals']['skipped']) ? (int) $state['totals']['skipped'] : 0
                    )
                );
                ?>
            </p>
            <form method="post">
                <input type="hidden" name="product_flow" value="import" />
                <?php wp_nonce_field('bw_import_run', 'bw_import_run_nonce'); ?>
                <?php submit_button(__('Continue Import (next chunk)', 'bw'), 'secondary', 'bw_import_run', false); ?>
            </form>
        <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php
}

/**
 * Gestisce il caricamento del CSV e salva lo stato temporaneo.
 *
 * @return array|WP_Error
 */
function bw_import_handle_upload_request()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to upload files.', 'bw'));
    }

    if (!isset($_POST['bw_import_upload_nonce']) || !wp_verify_nonce($_POST['bw_import_upload_nonce'], 'bw_import_upload')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    if (empty($_FILES['bw_import_csv']['name'])) {
        return new WP_Error('bw_import_file', __('Please select a CSV file to upload.', 'bw'));
    }

    add_filter('upload_dir', 'bw_import_upload_dir');
    $upload = wp_handle_upload(
        $_FILES['bw_import_csv'],
        [
            'test_form' => false,
            'mimes' => ['csv' => 'text/csv', 'txt' => 'text/plain'],
        ]
    );
    remove_filter('upload_dir', 'bw_import_upload_dir');

    if (isset($upload['error'])) {
        return new WP_Error('bw_import_upload_error', $upload['error']);
    }

    $parsed = bw_import_parse_csv_file($upload['file'], 5);
    if (is_wp_error($parsed)) {
        return $parsed;
    }

    $active_run_id = bw_import_get_active_run_id();
    if ($active_run_id !== '') {
        bw_import_release_lock($active_run_id, get_current_user_id(), true);
    }

    $summary = bw_import_calculate_header_stats($parsed['headers']);

    $update_existing = !empty($_POST['bw_import_update_existing']);
    $run_id = bw_import_generate_run_id();
    $chunk_size = bw_import_chunk_size();
    $file_fingerprint = bw_import_file_fingerprint($upload['file']);
    $now = time();

    $state = [
        'run_id' => $run_id,
        'status' => 'queued',
        'owner_user_id' => get_current_user_id(),
        'file_path' => $upload['file'],
        'file_fingerprint' => $file_fingerprint,
        'file_url' => $upload['url'],
        'headers' => $parsed['headers'],
        'sample' => $parsed['rows'],
        'update_existing' => $update_existing,
        'skip_images' => true,
        'row_cursor' => 0,
        'in_progress' => false,
        'mapping' => [],
        'totals' => [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'failed' => 0,
        ],
        'counters' => [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ],
        'row_outcomes' => [],
        'row_outcome_order' => [],
        'processed_row_keys' => [],
        'mapping_snapshot' => [],
        'options_snapshot' => [
            'update_existing' => $update_existing,
            'skip_images' => true,
            'chunk_size' => $chunk_size,
        ],
        'started_at' => $now,
        'updated_at' => $now,
        'completed_at' => 0,
        'lock' => [],
        'last_errors' => [],
        'last_warnings' => [],
        'upload_summary' => [
            'file_name' => basename($upload['file']),
            'total_fields' => $summary['total'],
            'loaded_fields' => $summary['loaded'],
            'missing' => $summary['missing'],
            'replaced' => $summary['replaced'],
            'replaced_count' => $summary['replaced_count'],
            'duplicates' => $summary['duplicates'],
            'duplicate_count' => $summary['duplicate_count'],
        ],
    ];

    bw_import_save_run_state($state);
    bw_import_set_active_run_id($run_id);
    bw_import_save_state($state);

    return $state;
}

/**
 * Gestisce l'esecuzione dell'import.
 *
 * @param array $state Stato corrente dell'upload.
 *
 * @return array|WP_Error
 */
function bw_import_handle_run_request($state)
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return new WP_Error('bw_import_permission', __('You do not have permission to run the import.', 'bw'));
    }

    if (!isset($_POST['bw_import_run_nonce']) || !wp_verify_nonce($_POST['bw_import_run_nonce'], 'bw_import_run')) {
        return new WP_Error('bw_import_nonce', __('Invalid nonce. Please try again.', 'bw'));
    }

    $active_run_id = bw_import_get_active_run_id();
    $requested_run_id = isset($state['run_id']) ? sanitize_text_field((string) $state['run_id']) : '';
    $run_id = $active_run_id !== '' ? $active_run_id : $requested_run_id;

    if ($active_run_id !== '' && $requested_run_id !== '' && $active_run_id !== $requested_run_id) {
        return new WP_Error('bw_import_run_mismatch', __('An active import run is already authoritative. Refresh and continue the active run.', 'bw'));
    }

    $run_state = $run_id !== '' ? bw_import_get_run_state($run_id) : [];

    if (empty($run_state['file_path']) || empty($run_state['headers'])) {
        return new WP_Error('bw_import_missing_state', __('No CSV file is attached. Upload a file before running the import.', 'bw'));
    }

    if (empty($run_state['run_id'])) {
        $run_state['run_id'] = bw_import_generate_run_id();
    }
    $run_id = (string) $run_state['run_id'];
    $current_status = isset($run_state['status']) ? sanitize_key((string) $run_state['status']) : '';
    if ($current_status === 'completed') {
        return [
            'message' => __('This import run is already completed. Upload a new CSV to start another run.', 'bw'),
        ];
    }

    $is_first_step = empty($run_state['mapping_snapshot']);

    $lock_result = bw_import_acquire_lock($run_id, get_current_user_id());
    if (empty($lock_result['ok'])) {
        return new WP_Error('bw_import_lock_held', isset($lock_result['message']) ? $lock_result['message'] : __('An import run is currently locked by another operator.', 'bw'));
    }

    if (!empty($lock_result['warning'])) {
        $run_state['last_warnings'] = bw_import_merge_bounded_messages(
            isset($run_state['last_warnings']) ? (array) $run_state['last_warnings'] : [],
            [$lock_result['warning']]
        );
    }

    $run_state['owner_user_id'] = get_current_user_id();
    $run_state['status'] = 'running';
    $run_state['updated_at'] = time();
    $run_state['lock'] = bw_import_get_lock_payload();
    bw_import_save_run_state($run_state);
    bw_import_set_active_run_id($run_id);
    bw_import_save_state($run_state);

    bw_import_refresh_lock($run_id, get_current_user_id());

    $mapping = [];

    if ($is_first_step) {
        $raw_mapping = isset($_POST['bw_import_mapping']) ? (array) $_POST['bw_import_mapping'] : [];
        foreach ($run_state['headers'] as $header) {
            $value = isset($raw_mapping[$header]) ? sanitize_text_field(wp_unslash($raw_mapping[$header])) : 'ignore';
            if ('ignore' !== $value) {
                $mapping[$header] = $value;
            }
        }

        if (!bw_import_has_identifier($mapping)) {
            bw_import_release_lock($run_id, get_current_user_id());
            return new WP_Error('bw_import_missing_identifier', __('Please map SKU to proceed. Product ID may be mapped as secondary, but SKU is mandatory.', 'bw'));
        }

        $sku_validation = bw_import_validate_unique_skus($run_state['file_path'], $run_state['headers'], $mapping);
        if (is_wp_error($sku_validation)) {
            bw_import_release_lock($run_id, get_current_user_id());
            return $sku_validation;
        }

        $run_state['mapping'] = $mapping;
        $run_state['mapping_snapshot'] = $mapping;
        $run_state['row_cursor'] = 0;
        $run_state['in_progress'] = true;
        $run_state['status'] = 'running';
        $run_state['totals'] = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'failed' => 0,
        ];
        $run_state['counters'] = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $run_state['row_outcomes'] = [];
        $run_state['row_outcome_order'] = [];
        $run_state['processed_row_keys'] = [];
        $run_state['last_errors'] = [];
        $run_state['last_warnings'] = [];
        $run_state['skip_images'] = !empty($_POST['bw_import_skip_images']);
        $run_state['options_snapshot'] = [
            'update_existing' => !empty($run_state['update_existing']),
            'skip_images' => !empty($run_state['skip_images']),
            'chunk_size' => bw_import_chunk_size(),
        ];
    } else {
        $mapping = isset($run_state['mapping_snapshot']) && is_array($run_state['mapping_snapshot']) ? $run_state['mapping_snapshot'] : [];
        if (!bw_import_has_identifier($mapping)) {
            $run_state['status'] = 'failed';
            $run_state['updated_at'] = time();
            bw_import_save_run_state($run_state);
            bw_import_release_lock($run_id, get_current_user_id());
            return new WP_Error('bw_import_missing_identifier', __('Import state is missing SKU mapping. Re-upload the CSV and map SKU again.', 'bw'));
        }
    }

    $chunk_size = isset($run_state['options_snapshot']['chunk_size']) ? absint($run_state['options_snapshot']['chunk_size']) : bw_import_chunk_size();
    if ($chunk_size < 1) {
        $chunk_size = bw_import_chunk_size();
    }

    $parsed_chunk = bw_import_parse_csv_chunk($run_state['file_path'], (int) $run_state['row_cursor'], $chunk_size);
    if (is_wp_error($parsed_chunk)) {
        $run_state['status'] = 'failed';
        $run_state['last_errors'] = bw_import_merge_bounded_messages(
            isset($run_state['last_errors']) ? (array) $run_state['last_errors'] : [],
            [$parsed_chunk->get_error_message()]
        );
        $run_state['updated_at'] = time();
        bw_import_save_run_state($run_state);
        bw_import_save_state($run_state);
        bw_import_release_lock($run_id, get_current_user_id());
        return $parsed_chunk;
    }

    $update_existing = !empty($run_state['update_existing']);
    $checkpoint_every = (int) apply_filters('bw_import_checkpoint_every', 10);
    if ($checkpoint_every < 1) {
        $checkpoint_every = 1;
    }
    if ($checkpoint_every > $chunk_size) {
        $checkpoint_every = $chunk_size;
    }

    $options = [
        'skip_images' => !empty($run_state['skip_images']),
        'checkpoint_every' => $checkpoint_every,
        'checkpoint_callback' => static function ($absolute_row) use (&$run_state, $run_id) {
            $absolute_row = max(0, (int) $absolute_row);
            if ($absolute_row <= (int) $run_state['row_cursor']) {
                return;
            }

            $run_state['row_cursor'] = $absolute_row;
            $run_state['updated_at'] = time();
            $run_state['lock'] = bw_import_get_lock_payload();
            bw_import_save_run_state($run_state);
            bw_import_save_state($run_state);
            bw_import_refresh_lock($run_id, get_current_user_id());
        },
    ];
    $result = bw_import_process_rows(
        $parsed_chunk['headers'],
        $parsed_chunk['rows'],
        $mapping,
        $update_existing,
        (int) $run_state['row_cursor'],
        $options,
        $run_state
    );

    $run_state['row_cursor'] = (int) $parsed_chunk['next_row'];
    $run_state['last_errors'] = bw_import_merge_bounded_messages(
        isset($run_state['last_errors']) ? (array) $run_state['last_errors'] : [],
        isset($result['errors']) ? (array) $result['errors'] : []
    );
    $run_state['last_warnings'] = bw_import_merge_bounded_messages(
        isset($run_state['last_warnings']) ? (array) $run_state['last_warnings'] : [],
        isset($result['warnings']) ? (array) $result['warnings'] : []
    );
    $run_state['updated_at'] = time();
    $run_state['lock'] = bw_import_get_lock_payload();

    if (empty($parsed_chunk['eof'])) {
        $run_state['status'] = 'running';
        bw_import_save_run_state($run_state);
        bw_import_save_state($run_state);
        bw_import_refresh_lock($run_id, get_current_user_id());
        return [
            'message' => sprintf(
                /* translators: 1: processed rows, 2: created count, 3: updated count, 4: skipped count */
                __('Chunk completed. Processed rows: %1$d — Created: %2$d, Updated: %3$d, Skipped: %4$d. Click continue to process the next chunk.', 'bw'),
                (int) $run_state['row_cursor'],
                (int) $run_state['totals']['created'],
                (int) $run_state['totals']['updated'],
                (int) $run_state['totals']['skipped']
            ),
        ];
    }

    $run_state['status'] = 'completed';
    $run_state['in_progress'] = false;
    $run_state['completed_at'] = time();
    bw_import_save_run_state($run_state);

    $message = sprintf(
        /* translators: 1: created count, 2: updated count, 3: skipped count, 4: errors count */
        __('Import completed. Created: %1$d, Updated: %2$d, Skipped: %3$d, Errors: %4$d', 'bw'),
        (int) $run_state['totals']['created'],
        (int) $run_state['totals']['updated'],
        (int) $run_state['totals']['skipped'],
        (int) $run_state['totals']['errors']
    );

    if (!empty($run_state['last_errors'])) {
        $message .= ' — ' . implode(' | ', array_map('esc_html', (array) $run_state['last_errors']));
    }

    bw_import_release_lock($run_id, get_current_user_id());
    bw_import_set_active_run_id('');
    bw_import_clear_state($run_id, true);

    return [
        'message' => $message,
    ];
}

/**
 * Chunk size for importer run steps.
 *
 * @return int
 */
function bw_import_chunk_size()
{
    return (int) apply_filters('bw_import_chunk_size', 50);
}

/**
 * Percorso di upload personalizzato per i CSV dell'importer.
 *
 * @param array $dirs Directory upload corrente.
 *
 * @return array
 */
function bw_import_upload_dir($dirs)
{
    $dirs['subdir'] = '/blackwork-import';
    $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
    $dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];
    return $dirs;
}

/**
 * Option keys and helpers for durable import runtime state.
 */
function bw_import_lock_option_key()
{
    return 'bw_import_run_lock';
}

function bw_import_active_run_option_key()
{
    return 'bw_import_active_run';
}

function bw_import_run_option_key($run_id)
{
    return 'bw_import_run_' . sanitize_key((string) $run_id);
}

function bw_import_generate_run_id()
{
    if (function_exists('wp_generate_uuid4')) {
        return sanitize_key(str_replace('-', '', wp_generate_uuid4()));
    }

    return sanitize_key(uniqid('bwimp_', true));
}

function bw_import_file_fingerprint($file_path)
{
    if (!is_string($file_path) || $file_path === '' || !file_exists($file_path)) {
        return '';
    }

    $sha1 = @sha1_file($file_path);
    if (is_string($sha1) && $sha1 !== '') {
        return $sha1;
    }

    return md5($file_path . '|' . filesize($file_path) . '|' . filemtime($file_path));
}

function bw_import_get_active_run_id()
{
    $run_id = get_option(bw_import_active_run_option_key(), '');
    return is_string($run_id) ? sanitize_text_field($run_id) : '';
}

function bw_import_set_active_run_id($run_id)
{
    $run_id = sanitize_text_field((string) $run_id);

    if ($run_id === '') {
        delete_option(bw_import_active_run_option_key());
        return;
    }

    update_option(bw_import_active_run_option_key(), $run_id, false);
}

function bw_import_get_run_state($run_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    if ($run_id === '') {
        return [];
    }

    $state = get_option(bw_import_run_option_key($run_id), []);
    if (!is_array($state)) {
        return [];
    }

    if (!isset($state['processed_row_keys']) || !is_array($state['processed_row_keys'])) {
        $state['processed_row_keys'] = [];
    }

    if (!empty($state['row_outcomes']) && is_array($state['row_outcomes'])) {
        foreach (array_keys($state['row_outcomes']) as $row_key) {
            $row_key = sanitize_text_field((string) $row_key);
            if ($row_key !== '' && !isset($state['processed_row_keys'][$row_key])) {
                $state['processed_row_keys'][$row_key] = true;
            }
        }
    }

    return $state;
}

function bw_import_sync_totals_from_counters($run_state)
{
    $run_state = is_array($run_state) ? $run_state : [];
    $counters = isset($run_state['counters']) && is_array($run_state['counters']) ? $run_state['counters'] : [];

    $created = isset($counters['created']) ? absint($counters['created']) : 0;
    $updated = isset($counters['updated']) ? absint($counters['updated']) : 0;
    $skipped = isset($counters['skipped']) ? absint($counters['skipped']) : 0;
    $failed = isset($counters['failed']) ? absint($counters['failed']) : 0;

    $run_state['counters'] = [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'failed' => $failed,
    ];
    $run_state['totals'] = [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $failed,
        'failed' => $failed,
    ];

    return $run_state;
}

function bw_import_save_run_state($run_state)
{
    if (!is_array($run_state) || empty($run_state['run_id'])) {
        return;
    }

    $run_state = bw_import_sync_totals_from_counters($run_state);
    $run_state['updated_at'] = time();
    if (!isset($run_state['processed_row_keys']) || !is_array($run_state['processed_row_keys'])) {
        $run_state['processed_row_keys'] = [];
    }

    update_option(bw_import_run_option_key($run_state['run_id']), $run_state, false);
}

function bw_import_lock_compare_and_swap($expected_lock, $new_lock)
{
    global $wpdb;

    $option_name = bw_import_lock_option_key();
    $expected = is_array($expected_lock) ? $expected_lock : [];
    $replacement = is_array($new_lock) ? $new_lock : [];

    if (empty($expected)) {
        return add_option($option_name, $replacement, '', false);
    }

    $table = $wpdb->options;
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table} SET option_value = %s WHERE option_name = %s AND option_value = %s",
            maybe_serialize($replacement),
            $option_name,
            maybe_serialize($expected)
        )
    );

    return 1 === (int) $updated;
}

function bw_import_lock_delete_if_matches($expected_lock)
{
    global $wpdb;

    $expected = is_array($expected_lock) ? $expected_lock : [];
    if (empty($expected)) {
        return false;
    }

    $option_name = bw_import_lock_option_key();
    $table = $wpdb->options;
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table} WHERE option_name = %s AND option_value = %s",
            $option_name,
            maybe_serialize($expected)
        )
    );

    return 1 === (int) $deleted;
}

function bw_import_clear_run_state($run_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    if ($run_id === '') {
        return;
    }

    delete_option(bw_import_run_option_key($run_id));
    if (bw_import_get_active_run_id() === $run_id) {
        bw_import_set_active_run_id('');
    }
}

function bw_import_get_lock_payload()
{
    $lock = get_option(bw_import_lock_option_key(), []);
    return is_array($lock) ? $lock : [];
}

function bw_import_is_lock_stale($lock)
{
    if (!is_array($lock) || empty($lock['expires_at'])) {
        return true;
    }

    return (int) $lock['expires_at'] <= time();
}

function bw_import_acquire_lock($run_id, $user_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    $user_id = absint($user_id);
    $attempts = 0;

    while ($attempts < 3) {
        $attempts++;
        $existing_lock = bw_import_get_lock_payload();
        $stale = bw_import_is_lock_stale($existing_lock);

        if (!empty($existing_lock) && !$stale) {
            $locked_run = isset($existing_lock['run_id']) ? sanitize_text_field((string) $existing_lock['run_id']) : '';
            $locked_owner = isset($existing_lock['owner_user_id']) ? absint($existing_lock['owner_user_id']) : 0;

            if (!($locked_run === $run_id && $locked_owner === $user_id)) {
                return [
                    'ok' => false,
                    'message' => sprintf(
                        /* translators: 1: user id, 2: run id */
                        __('Another import run is active (owner user ID: %1$d, run: %2$s). Please retry later.', 'bw'),
                        $locked_owner,
                        $locked_run !== '' ? $locked_run : 'n/a'
                    ),
                ];
            }
        }

        $now = time();
        $lock_payload = [
            'run_id' => $run_id,
            'owner_user_id' => $user_id,
            'acquired_at' => $now,
            'expires_at' => $now + 300,
            'heartbeat_at' => $now,
        ];

        if (bw_import_lock_compare_and_swap($existing_lock, $lock_payload)) {
            $response = ['ok' => true];
            if ($stale && !empty($existing_lock)) {
                $response['warning'] = __('A stale import lock was reclaimed before continuing.', 'bw');
            }

            return $response;
        }
    }

    return [
        'ok' => false,
        'message' => __('Unable to acquire import lock safely due to concurrent updates. Please retry.', 'bw'),
    ];
}

function bw_import_refresh_lock($run_id, $user_id)
{
    $run_id = sanitize_text_field((string) $run_id);
    $user_id = absint($user_id);
    $lock = bw_import_get_lock_payload();

    if (
        empty($lock) ||
        (isset($lock['run_id']) ? sanitize_text_field((string) $lock['run_id']) : '') !== $run_id ||
        (isset($lock['owner_user_id']) ? absint($lock['owner_user_id']) : 0) !== $user_id
    ) {
        return false;
    }

    $now = time();
    $updated_lock = $lock;
    $updated_lock['heartbeat_at'] = $now;
    $updated_lock['expires_at'] = $now + 300;
    return bw_import_lock_compare_and_swap($lock, $updated_lock);
}

function bw_import_release_lock($run_id, $user_id, $force = false)
{
    $run_id = sanitize_text_field((string) $run_id);
    $user_id = absint($user_id);
    $lock = bw_import_get_lock_payload();
    if (empty($lock)) {
        return true;
    }

    $locked_run = isset($lock['run_id']) ? sanitize_text_field((string) $lock['run_id']) : '';
    $locked_owner = isset($lock['owner_user_id']) ? absint($lock['owner_user_id']) : 0;

    if ($force) {
        delete_option(bw_import_lock_option_key());
        return true;
    }

    if ($locked_run === $run_id && ($locked_owner === $user_id || bw_import_is_lock_stale($lock))) {
        if (bw_import_lock_delete_if_matches($lock)) {
            return true;
        }

        $latest = bw_import_get_lock_payload();
        if (empty($latest)) {
            return true;
        }

        $latest_run = isset($latest['run_id']) ? sanitize_text_field((string) $latest['run_id']) : '';
        if ($latest_run === $run_id && bw_import_is_lock_stale($latest)) {
            delete_option(bw_import_lock_option_key());
            return true;
        }

        return false;
    }

    return false;
}

function bw_import_make_row_identity($row_offset, $row_index, $sku)
{
    $cursor = absint($row_offset + $row_index + 2);
    $sku_hash = md5(sanitize_text_field((string) $sku));
    return 'r' . $cursor . '_s' . $sku_hash;
}

function bw_import_record_row_outcome(&$run_state, $row_identity, $outcome, $message = '')
{
    $allowed_outcomes = ['created', 'updated', 'skipped', 'failed'];
    if (!in_array($outcome, $allowed_outcomes, true)) {
        return false;
    }

    if (!is_array($run_state)) {
        $run_state = [];
    }

    if (!isset($run_state['row_outcomes']) || !is_array($run_state['row_outcomes'])) {
        $run_state['row_outcomes'] = [];
    }
    if (!isset($run_state['row_outcome_order']) || !is_array($run_state['row_outcome_order'])) {
        $run_state['row_outcome_order'] = [];
    }
    if (!isset($run_state['counters']) || !is_array($run_state['counters'])) {
        $run_state['counters'] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    }
    if (!isset($run_state['processed_row_keys']) || !is_array($run_state['processed_row_keys'])) {
        $run_state['processed_row_keys'] = [];
    }

    if ($row_identity === '' || isset($run_state['processed_row_keys'][$row_identity])) {
        return false;
    }

    $run_state['processed_row_keys'][$row_identity] = true;
    $run_state['row_outcomes'][$row_identity] = [
        'outcome' => $outcome,
        'message' => sanitize_text_field((string) $message),
        'at' => time(),
    ];
    $run_state['row_outcome_order'][] = $row_identity;
    $run_state['counters'][$outcome] = isset($run_state['counters'][$outcome]) ? ((int) $run_state['counters'][$outcome] + 1) : 1;

    while (count($run_state['row_outcome_order']) > 500) {
        $oldest = array_shift($run_state['row_outcome_order']);
        if ($oldest !== null) {
            unset($run_state['row_outcomes'][$oldest]);
        }
    }

    $run_state = bw_import_sync_totals_from_counters($run_state);
    return true;
}

/**
 * Salva stato import come transient UI mirror (non-authoritative).
 *
 * @param array $state Stato da salvare.
 */
function bw_import_save_state($state)
{
    set_transient('bw_import_state_' . get_current_user_id(), $state, HOUR_IN_SECONDS);
}

/**
 * Recupera lo stato import: preferisce run state durevole attivo.
 *
 * @return array
 */
function bw_import_get_state()
{
    $active_run_id = bw_import_get_active_run_id();
    if ($active_run_id !== '') {
        $run_state = bw_import_get_run_state($active_run_id);
        if (!empty($run_state)) {
            bw_import_save_state($run_state);
            return $run_state;
        }

        bw_import_set_active_run_id('');
    }

    $state = get_transient('bw_import_state_' . get_current_user_id());
    return is_array($state) ? $state : [];
}

/**
 * Pulisce lo stato di importazione.
 */
function bw_import_clear_state($run_id = '', $preserve_run_state = true)
{
    delete_transient('bw_import_state_' . get_current_user_id());

    if ($run_id !== '' && !$preserve_run_state) {
        bw_import_clear_run_state($run_id);
        return;
    }

    bw_import_set_active_run_id('');
}

/**
 * Effettua il parse del CSV.
 *
 * @param string $file_path Percorso del file.
 * @param int    $max_rows  Numero massimo di righe da leggere (0 = tutte).
 *
 * @return array|WP_Error
 */
function bw_import_parse_csv_file($file_path, $max_rows = 0)
{
    if (!file_exists($file_path)) {
        return new WP_Error('bw_import_missing_file', __('The uploaded CSV file cannot be found.', 'bw'));
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return new WP_Error('bw_import_open_error', __('Unable to open the CSV file.', 'bw'));
    }

    $headers = fgetcsv($handle);
    if (empty($headers)) {
        fclose($handle);
        return new WP_Error('bw_import_headers', __('The CSV file is missing a header row.', 'bw'));
    }

    $rows = [];
    $row_count = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $rows[] = $data;
        $row_count++;
        if ($max_rows > 0 && $row_count >= $max_rows) {
            break;
        }
    }

    fclose($handle);

    return [
        'headers' => $headers,
        'rows' => $rows,
    ];
}

/**
 * Open CSV file handle for importer chunk reads.
 *
 * @param string $file_path CSV file path.
 *
 * @return resource|WP_Error
 */
function bw_import_open_csv($file_path)
{
    if (!file_exists($file_path)) {
        return new WP_Error('bw_import_missing_file', __('The uploaded CSV file cannot be found.', 'bw'));
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return new WP_Error('bw_import_open_error', __('Unable to open the CSV file.', 'bw'));
    }

    return $handle;
}

/**
 * Read CSV headers from an opened handle.
 *
 * @param resource $handle CSV handle.
 *
 * @return array|WP_Error
 */
function bw_import_read_headers($handle)
{
    $headers = fgetcsv($handle);
    if (empty($headers)) {
        return new WP_Error('bw_import_headers', __('The CSV file is missing a header row.', 'bw'));
    }

    return $headers;
}

/**
 * Read one CSV chunk from an opened handle.
 *
 * @param resource $handle    CSV handle.
 * @param int      $start_row Zero-based row offset excluding header.
 * @param int      $limit     Chunk size.
 *
 * @return array
 */
function bw_import_read_chunk($handle, $start_row, $limit)
{
    $start_row = max(0, (int) $start_row);
    $limit = max(1, (int) $limit);

    $current_row = 0;
    while ($current_row < $start_row && ($data = fgetcsv($handle)) !== false) {
        $current_row++;
    }

    $rows = [];
    $read_count = 0;
    while ($read_count < $limit && ($data = fgetcsv($handle)) !== false) {
        $rows[] = $data;
        $read_count++;
        $current_row++;
    }

    return [
        'rows' => $rows,
        'next_row' => $current_row,
        'eof' => feof($handle),
    ];
}

/**
 * Parse CSV in deterministic chunks without loading full file in memory.
 *
 * @param string $file_path  CSV file path.
 * @param int    $start_row  Zero-based row index in data rows (header excluded).
 * @param int    $limit      Max number of rows to return.
 *
 * @return array|WP_Error
 */
function bw_import_parse_csv_chunk($file_path, $start_row, $limit)
{
    $start_row = max(0, (int) $start_row);
    $limit = max(1, (int) $limit);

    $handle = bw_import_open_csv($file_path);
    if (is_wp_error($handle)) {
        return $handle;
    }

    $headers = bw_import_read_headers($handle);
    if (is_wp_error($headers)) {
        fclose($handle);
        return $headers;
    }

    $chunk = bw_import_read_chunk($handle, $start_row, $limit);
    fclose($handle);

    return [
        'headers' => $headers,
        'rows' => $chunk['rows'],
        'next_row' => $chunk['next_row'],
        'eof' => $chunk['eof'],
    ];
}

/**
 * Genera un riepilogo dei campi trovati nel CSV caricato.
 *
 * @param array $headers Elenco delle intestazioni.
 *
 * @return array
 */
function bw_import_calculate_header_stats($headers)
{
    $clean_headers = array_map('trim', (array) $headers);
    $total_fields = count($clean_headers);
    $loaded_headers = array_filter($clean_headers, static function ($header) {
        return '' !== $header;
    });
    $missing_headers = [];
    $replaced_headers = [];
    $duplicates = [];
    $header_positions = [];

    foreach ($clean_headers as $index => $header) {
        if ('' === $header) {
            $placeholder = sprintf(
                /* translators: %d: column index */
                __('Column %d (missing header name)', 'bw'),
                (int) $index + 1
            );
            $missing_headers[] = $placeholder;
            $replaced_headers[] = $placeholder;
            continue;
        }

        $normalized = strtolower($header);
        if (!isset($header_positions[$normalized])) {
            $header_positions[$normalized] = [];
        }

        $header_positions[$normalized][] = (int) $index + 1;
    }

    foreach ($header_positions as $header => $positions) {
        if (count($positions) > 1) {
            $duplicates[$header] = $positions;
        }
    }

    return [
        'total' => $total_fields,
        'loaded' => count($loaded_headers),
        'missing' => $missing_headers,
        'replaced' => $replaced_headers,
        'replaced_count' => count($replaced_headers),
        'duplicates' => $duplicates,
        'duplicate_count' => array_sum(array_map(static function ($positions) {
            return max(0, count($positions) - 1);
        }, $duplicates)),
    ];
}

/**
 * Restituisce le opzioni di mapping organizzate per gruppo.
 *
 * @return array
 */
function bw_import_get_mapping_options()
{
    $options = [
        __('Product Core', 'bw') => [
            'product_id' => __('Product ID', 'bw'),
            'sku' => __('Product SKU', 'bw'),
            'post_title' => __('Product Title (post_title)', 'bw'),
            'post_name' => __('Product Slug (post_name)', 'bw'),
            'post_status' => __('Product Status', 'bw'),
            'product_type' => __('Product Type', 'bw'),
            'post_content' => __('Product Description (post_content)', 'bw'),
            'post_excerpt' => __('Product Short Description (post_excerpt)', 'bw'),
        ],
        __('Pricing', 'bw') => [
            'regular_price' => __('Regular Price', 'bw'),
            'sale_price' => __('Sale Price', 'bw'),
            'sale_price_dates_from' => __('Sale Start Date', 'bw'),
            'sale_price_dates_to' => __('Sale End Date', 'bw'),
        ],
        __('Inventory', 'bw') => [
            'stock_quantity' => __('Stock Quantity', 'bw'),
            'manage_stock' => __('Manage Stock (yes/no)', 'bw'),
            'stock_status' => __('Stock Status', 'bw'),
            'backorders' => __('Backorders', 'bw'),
            'sold_individually' => __('Sold Individually', 'bw'),
        ],
        __('Shipping', 'bw') => [
            'weight' => __('Weight', 'bw'),
            'length' => __('Length', 'bw'),
            'width' => __('Width', 'bw'),
            'height' => __('Height', 'bw'),
            'shipping_class' => __('Shipping Class', 'bw'),
        ],
        __('Tax', 'bw') => [
            'tax_status' => __('Tax Status', 'bw'),
            'tax_class' => __('Tax Class', 'bw'),
        ],
        __('Categories & Tags', 'bw') => [
            'categories' => __('Product Categories (comma separated)', 'bw'),
            'tags' => __('Product Tags (comma separated)', 'bw'),
        ],
        __('Images', 'bw') => [
            'featured_image' => __('Product Image (featured image URL)', 'bw'),
            'gallery_images' => __('Product Gallery (comma-separated image URLs)', 'bw'),
        ],
        __('Links', 'bw') => [
            'upsells' => __('Upsells (comma-separated IDs or SKUs)', 'bw'),
            'cross_sells' => __('Cross-sells (comma-separated IDs or SKUs)', 'bw'),
        ],
    ];

    $attribute_options = bw_import_attribute_options();
    if (!empty($attribute_options)) {
        $options[__('Attributes', 'bw')] = $attribute_options;
    }

    $meta_fields = bw_import_detect_custom_meta_fields();

    $product_slider_meta = bw_import_product_slider_meta_options($meta_fields);
    if (!empty($product_slider_meta)) {
        $options[__('MetaFields', 'bw')] = $product_slider_meta;
    }

    if (!empty($meta_fields)) {
        $meta_fields = array_values(array_diff($meta_fields, ['_bw_slider_hover_image']));
        if (!empty($meta_fields)) {
            $meta_group = [];
            foreach ($meta_fields as $meta_key) {
                $meta_group['meta:' . $meta_key] = sprintf(__('Meta: %1$s (%2$s)', 'bw'), bw_import_pretty_meta_label($meta_key), $meta_key);
            }
            $options[__('Custom Meta Fields (Metabox)', 'bw')] = $meta_group;
        }
    }

    return $options;
}

/**
 * Prova ad effettuare un auto-mapping basato sul nome della colonna.
 *
 * @param array $headers  Header del CSV.
 * @param array $options  Opzioni di mapping organizzate per gruppo.
 *
 * @return array
 */
function bw_import_guess_mapping($headers, $options)
{
    $flat_options = [];
    foreach ($options as $group_options) {
        foreach ($group_options as $key => $label) {
            $flat_options[$key] = [
                'normalized_key' => bw_import_normalize_mapping_key($key),
                'normalized_label' => bw_import_normalize_string($label),
            ];
        }
    }

    $aliases = bw_import_get_mapping_aliases();
    $guessed = [];

    foreach ($headers as $header) {
        $normalized_header = bw_import_normalize_string($header);

        if (isset($aliases[$normalized_header]) && isset($flat_options[$aliases[$normalized_header]])) {
            $guessed[$header] = $aliases[$normalized_header];
            continue;
        }

        foreach ($flat_options as $key => $normalized) {
            if ($normalized_header === $normalized['normalized_key'] || $normalized_header === $normalized['normalized_label']) {
                $guessed[$header] = $key;
                break;
            }
        }
    }

    return $guessed;
}

/**
 * Normalizza una stringa per renderla confrontabile.
 *
 * @param string $value Valore da normalizzare.
 *
 * @return string
 */
function bw_import_normalize_string($value)
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

/**
 * Normalizza la chiave di mapping.
 *
 * @param string $key Chiave di mapping (es. meta:_foo, attribute_color).
 *
 * @return string
 */
function bw_import_normalize_mapping_key($key)
{
    if (strpos($key, 'meta:') === 0) {
        $key = substr($key, 5);
    }

    if (strpos($key, 'attribute_') === 0) {
        $key = substr($key, strlen('attribute_'));
    }

    return bw_import_normalize_string($key);
}

/**
 * Restituisce alias comuni per gli header del CSV.
 *
 * @return array
 */
function bw_import_get_mapping_aliases()
{
    $aliases = [
        'title' => 'post_title',
        'product_title' => 'post_title',
        'producttitle' => 'post_title',
        'name' => 'post_title',
        'product_name' => 'post_title',
        'slug' => 'post_name',
        'status' => 'post_status',
        'type' => 'product_type',
        'description' => 'post_content',
        'long_description' => 'post_content',
        'short_description' => 'post_excerpt',
        'regular_price' => 'regular_price',
        'price' => 'regular_price',
        'sale_price' => 'sale_price',
        'discount_price' => 'sale_price',
        'qty' => 'stock_quantity',
        'quantity' => 'stock_quantity',
        'stock' => 'stock_quantity',
        'featured_image' => 'featured_image',
        'image' => 'featured_image',
        'gallery' => 'gallery_images',
        'category' => 'categories',
        'categories' => 'categories',
        'tag' => 'tags',
        'tags' => 'tags',
        'upsell' => 'upsells',
        'upsells' => 'upsells',
        'crosssell' => 'cross_sells',
        'cross_sells' => 'cross_sells',
    ];

    $normalized_aliases = [];
    foreach ($aliases as $alias => $target) {
        $normalized_aliases[bw_import_normalize_string($alias)] = $target;
    }

    return $normalized_aliases;
}

/**
 * Rileva i meta fields presenti nei file del metabox.
 *
 * @return array
 */
function bw_import_detect_custom_meta_fields()
{
    $meta_keys = [];

    $metabox_functions = [
        'bw_get_bibliographic_fields',
        'bw_get_prints_bibliographic_fields',
        'bw_get_digital_product_fields',
    ];

    foreach ($metabox_functions as $meta_function) {
        if (!function_exists($meta_function)) {
            continue;
        }

        $fields = call_user_func($meta_function);
        if (empty($fields) || !is_array($fields)) {
            continue;
        }

        foreach (array_keys($fields) as $meta_key) {
            if (strpos($meta_key, '_') === 0) {
                $meta_keys[$meta_key] = true;
            }
        }
    }

    $meta_directories = [
        trailingslashit(BW_MEW_PATH) . 'metabox/',
        trailingslashit(BW_MEW_PATH) . 'includes/product-types/',
    ];

    foreach ($meta_directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        foreach (glob($directory . '*.php') as $file) {
            $contents = file_get_contents($file);
            if (!$contents) {
                continue;
            }

            if (preg_match_all("/(?:update_post_meta|add_post_meta|get_post_meta)\s*\(\s*\$[a-zA-Z0-9_\->]+\s*,\s*'([^']+)'/", $contents, $matches)) {
                foreach ($matches[1] as $meta_key) {
                    if (strpos($meta_key, '_') === 0) {
                        $meta_keys[$meta_key] = true;
                    }
                }
            }
        }
    }

    return array_keys($meta_keys);
}

/**
 * Restituisce le opzioni di mapping per il meta field dello slider prodotto.
 *
 * @param array $detected_meta Meta rilevati automaticamente.
 *
 * @return array
 */
function bw_import_product_slider_meta_options($detected_meta)
{
    $meta_key = '_bw_slider_hover_image';

    if (!in_array($meta_key, $detected_meta, true)) {
        $detected_meta[] = $meta_key;
    }

    return [
        'meta:' . $meta_key => sprintf(__('Image over (%s)', 'bw'), $meta_key),
    ];
}

/**
 * Genera opzioni per gli attributi globali WooCommerce.
 *
 * @return array
 */
function bw_import_attribute_options()
{
    $options = [];
    if (!function_exists('wc_get_attribute_taxonomies')) {
        return $options;
    }

    $attributes = wc_get_attribute_taxonomies();
    if (empty($attributes)) {
        return $options;
    }

    foreach ($attributes as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        $options['attribute_' . $taxonomy] = sprintf(__('Global Attribute: %s', 'bw'), $attribute->attribute_label);
    }

    return $options;
}

/**
 * Converte la chiave meta in etichetta leggibile.
 *
 * @param string $meta_key Meta key.
 *
 * @return string
 */
function bw_import_pretty_meta_label($meta_key)
{
    $label = str_replace('_', ' ', $meta_key);
    $label = trim($label, ' _');
    return ucwords($label);
}

/**
 * Verifica che ci sia almeno un identificativo prodotto mappato.
 *
 * @param array $mapping Mapping selezionato.
 *
 * @return bool
 */
function bw_import_has_identifier($mapping)
{
    $values = array_values($mapping);
    return in_array('sku', $values, true);
}

/**
 * Elabora le righe del CSV in base al mapping.
 *
 * @param array $headers  Header del CSV.
 * @param array $rows     Righe del CSV.
 * @param array $mapping  Mapping colonne -> campi.
 *
 * @return array
 */
function bw_import_process_rows($headers, $rows, $mapping, $update_existing = false, $row_offset = 0, $options = [], &$run_state = null)
{
    $result = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors_count' => 0,
        'errors' => [],
        'warnings' => [],
    ];

    $checkpoint_callback = (isset($options['checkpoint_callback']) && is_callable($options['checkpoint_callback']))
        ? $options['checkpoint_callback']
        : null;
    $checkpoint_every = isset($options['checkpoint_every']) ? absint($options['checkpoint_every']) : 0;
    $processed_since_checkpoint = 0;
    $last_absolute_row = -1;

    $maybe_checkpoint = static function ($absolute_row) use (&$processed_since_checkpoint, &$last_absolute_row, $checkpoint_every, $checkpoint_callback) {
        if (!$checkpoint_callback || $checkpoint_every < 1) {
            return;
        }

        $last_absolute_row = (int) $absolute_row;
        $processed_since_checkpoint++;
        if ($processed_since_checkpoint >= $checkpoint_every) {
            call_user_func($checkpoint_callback, $last_absolute_row);
            $processed_since_checkpoint = 0;
        }
    };

    foreach ($rows as $row_index => $row) {
        $row_data = [];
        foreach ($headers as $i => $header) {
            $row_data[$header] = isset($row[$i]) ? $row[$i] : '';
        }

        $prepared = bw_import_prepare_row_data($row_data, $mapping);
        if (is_wp_error($prepared)) {
            $row_identity = bw_import_make_row_identity($row_offset, $row_index, '');
            $recorded = bw_import_record_row_outcome($run_state, $row_identity, 'failed', $prepared->get_error_message());
            if ($recorded) {
                $result['skipped']++;
                $result['errors_count']++;
                $result['errors'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $prepared->get_error_message());
            }
            $maybe_checkpoint($row_offset + $row_index + 1);
            continue;
        }

        if (!empty($prepared['warnings'])) {
            foreach ((array) $prepared['warnings'] as $warning) {
                $result['warnings'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $warning);
            }
        }

        $row_sku = isset($prepared['product']['sku']) ? (string) $prepared['product']['sku'] : '';
        $row_identity = bw_import_make_row_identity($row_offset, $row_index, $row_sku);
        if (!empty($run_state['processed_row_keys']) && is_array($run_state['processed_row_keys']) && isset($run_state['processed_row_keys'][$row_identity])) {
            $maybe_checkpoint($row_offset + $row_index + 1);
            continue;
        }

        $save_result = bw_import_save_product_from_row($prepared, $update_existing, $options);
        if (is_wp_error($save_result)) {
            $error_code = $save_result->get_error_code();
            $row_recorded = false;
            if ('bw_import_missing_product_match' === $error_code) {
                $row_recorded = bw_import_record_row_outcome($run_state, $row_identity, 'skipped', $save_result->get_error_message());
                if ($row_recorded) {
                    $result['skipped']++;
                }
            } else {
                $row_recorded = bw_import_record_row_outcome($run_state, $row_identity, 'failed', $save_result->get_error_message());
                if ($row_recorded) {
                    $result['skipped']++;
                    $result['errors_count']++;
                }
            }
            if ($row_recorded) {
                $result['errors'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $save_result->get_error_message());
            }
            $maybe_checkpoint($row_offset + $row_index + 1);
            continue;
        }

        if (!empty($save_result['warnings'])) {
            foreach ((array) $save_result['warnings'] as $warning) {
                $result['warnings'][] = sprintf(__('Row %1$d: %2$s', 'bw'), $row_offset + $row_index + 2, $warning);
            }
        }

        if (!empty($save_result['status']) && $save_result['status'] === 'updated') {
            if (bw_import_record_row_outcome($run_state, $row_identity, 'updated')) {
                $result['updated']++;
            }
        } else {
            if (bw_import_record_row_outcome($run_state, $row_identity, 'created')) {
                $result['created']++;
            }
        }

        $maybe_checkpoint($row_offset + $row_index + 1);
    }

    if ($checkpoint_callback && $processed_since_checkpoint > 0 && $last_absolute_row >= 0) {
        call_user_func($checkpoint_callback, $last_absolute_row);
    }

    return $result;
}

/**
 * Prepara i dati della riga in base al mapping.
 *
 * @param array $row_data Dati riga.
 * @param array $mapping  Mapping.
 *
 * @return array|WP_Error
 */
function bw_import_prepare_row_data($row_data, $mapping)
{
    $data = [
        'product' => [],
        'meta' => [],
        'categories' => [],
        'tags' => [],
        'attributes' => [],
        'upsells' => [],
        'cross_sells' => [],
        'warnings' => [],
    ];

    foreach ($row_data as $header => $value) {
        $target = isset($mapping[$header]) ? $mapping[$header] : 'ignore';
        if ('ignore' === $target) {
            continue;
        }

        $clean_value = is_string($value) ? trim(wp_unslash($value)) : $value;

        if (strpos($target, 'meta:') === 0) {
            $meta_key = substr($target, 5);
            $data['meta'][$meta_key] = $clean_value;
            continue;
        }

        if (strpos($target, 'attribute_') === 0) {
            $taxonomy = substr($target, strlen('attribute_'));
            $data['attributes'][$taxonomy] = $clean_value;
            continue;
        }

        switch ($target) {
            case 'product_id':
                $data['product']['id'] = absint($clean_value);
                break;
            case 'sku':
                $data['product']['sku'] = sanitize_text_field($clean_value);
                break;
            case 'post_title':
                $data['product']['name'] = sanitize_text_field($clean_value);
                break;
            case 'post_name':
                $data['product']['slug'] = sanitize_title($clean_value);
                break;
            case 'post_status':
                $status = strtolower(sanitize_key($clean_value));
                $allowed_statuses = ['draft', 'publish', 'pending', 'private'];
                if ($status !== '' && !in_array($status, $allowed_statuses, true)) {
                    $data['product']['status'] = 'draft';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid status value */
                        __('Invalid status "%s" normalized to draft.', 'bw'),
                        $status
                    );
                } else {
                    $data['product']['status'] = $status;
                }
                break;
            case 'product_type':
                $product_type = sanitize_key($clean_value);
                $allowed_product_types = ['simple', 'variable', 'grouped', 'external'];
                if ($product_type !== '' && !in_array($product_type, $allowed_product_types, true)) {
                    $data['product']['type'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid product type */
                        __('Invalid product type "%s" ignored.', 'bw'),
                        $product_type
                    );
                } else {
                    $data['product']['type'] = $product_type;
                }
                break;
            case 'post_content':
                $data['product']['description'] = wp_kses_post($clean_value);
                break;
            case 'post_excerpt':
                $data['product']['short_description'] = wp_kses_post($clean_value);
                break;
            case 'regular_price':
                $data['product']['regular_price'] = wc_format_decimal($clean_value);
                break;
            case 'sale_price':
                $data['product']['sale_price'] = wc_format_decimal($clean_value);
                break;
            case 'sale_price_dates_from':
                $data['product']['sale_start'] = sanitize_text_field($clean_value);
                break;
            case 'sale_price_dates_to':
                $data['product']['sale_end'] = sanitize_text_field($clean_value);
                break;
            case 'stock_quantity':
                $data['product']['stock_quantity'] = (float) $clean_value;
                break;
            case 'manage_stock':
                $data['product']['manage_stock'] = in_array(strtolower($clean_value), ['yes', '1', 'true'], true);
                break;
            case 'stock_status':
                $stock_status = sanitize_key($clean_value);
                $allowed_stock_statuses = ['instock', 'outofstock', 'onbackorder'];
                if ($stock_status !== '' && !in_array($stock_status, $allowed_stock_statuses, true)) {
                    $data['product']['stock_status'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid stock status */
                        __('Invalid stock status "%s" ignored.', 'bw'),
                        $stock_status
                    );
                } else {
                    $data['product']['stock_status'] = $stock_status;
                }
                break;
            case 'backorders':
                $backorders = sanitize_key($clean_value);
                $allowed_backorders = ['no', 'notify', 'yes'];
                if ($backorders !== '' && !in_array($backorders, $allowed_backorders, true)) {
                    $data['product']['backorders'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid backorders value */
                        __('Invalid backorders value "%s" ignored.', 'bw'),
                        $backorders
                    );
                } else {
                    $data['product']['backorders'] = $backorders;
                }
                break;
            case 'sold_individually':
                $data['product']['sold_individually'] = in_array(strtolower($clean_value), ['yes', '1', 'true'], true);
                break;
            case 'weight':
            case 'length':
            case 'width':
            case 'height':
                $data['product'][$target] = wc_format_decimal($clean_value);
                break;
            case 'shipping_class':
                $data['product']['shipping_class'] = sanitize_title($clean_value);
                break;
            case 'tax_status':
                $tax_status = sanitize_key($clean_value);
                $allowed_tax_statuses = ['taxable', 'shipping', 'none'];
                if ($tax_status !== '' && !in_array($tax_status, $allowed_tax_statuses, true)) {
                    $data['product']['tax_status'] = '';
                    $data['warnings'][] = sprintf(
                        /* translators: %s: invalid tax status */
                        __('Invalid tax status "%s" ignored.', 'bw'),
                        $tax_status
                    );
                } else {
                    $data['product']['tax_status'] = $tax_status;
                }
                break;
            case 'tax_class':
                $data['product']['tax_class'] = sanitize_title($clean_value);
                break;
            case 'categories':
                $data['categories'] = bw_import_explode_list($clean_value);
                break;
            case 'tags':
                $data['tags'] = bw_import_explode_list($clean_value);
                break;
            case 'featured_image':
                $data['product']['featured_image'] = esc_url_raw($clean_value);
                break;
            case 'gallery_images':
                $data['product']['gallery'] = array_map('esc_url_raw', bw_import_explode_list($clean_value));
                break;
            case 'upsells':
                $data['upsells'] = bw_import_explode_list($clean_value);
                break;
            case 'cross_sells':
                $data['cross_sells'] = bw_import_explode_list($clean_value);
                break;
        }
    }

    if (empty($data['product']['sku'])) {
        return new WP_Error('bw_import_missing_identifiers', __('Missing SKU for this row.', 'bw'));
    }

    return $data;
}

/**
 * Suddivide una stringa in array usando virgola o pipe.
 *
 * @param string $value Valore da esplodere.
 *
 * @return array
 */
function bw_import_explode_list($value)
{
    $value = (string) $value;
    $parts = preg_split('/[|,]/', $value);
    $parts = array_filter(array_map('trim', $parts));
    return $parts;
}

/**
 * Salva un prodotto a partire dai dati di riga.
 *
 * @param array $data             Dati preparati.
 * @param bool  $update_existing  Se true, aggiorna solo prodotti già esistenti.
 *
 * @return string|WP_Error
 */
function bw_import_save_product_from_row($data, $update_existing = false, $options = [])
{
    $options = wp_parse_args(
        $options,
        [
            'skip_images' => true,
            'sku_retry_done' => false,
        ]
    );

    $product_id = isset($data['product']['id']) ? absint($data['product']['id']) : 0;
    $sku = isset($data['product']['sku']) ? $data['product']['sku'] : '';
    $product = null;
    $status = 'created';
    $warnings = [];

    if ($product_id) {
        $product = wc_get_product($product_id);
    }

    if (!$product && $sku) {
        $maybe_id = wc_get_product_id_by_sku($sku);
        if ($maybe_id) {
            $product = wc_get_product($maybe_id);
            $product_id = $maybe_id;
        }
    }

    if ($product) {
        $status = 'updated';
    } elseif ($update_existing) {
        return new WP_Error(
            'bw_import_missing_product_match',
            __('Skipping row because no existing product matches the provided ID or SKU.', 'bw')
        );
    } else {
        if ($sku) {
            $resolved_product_id = wc_get_product_id_by_sku($sku);
            if ($resolved_product_id) {
                $product = wc_get_product($resolved_product_id);
                if ($product) {
                    $product_id = $resolved_product_id;
                    $status = 'updated';
                }
            }
        }

        if (!$product) {
            $product_type = !empty($data['product']['type']) ? $data['product']['type'] : 'simple';

            try {
                $product = wc_get_product_object($product_type);
            } catch (Throwable $exception) {
                return new WP_Error('bw_import_product_object', $exception->getMessage());
            }

            if (!$product) {
                return new WP_Error('bw_import_product_object', __('Unable to create product object for type.', 'bw'));
            }
        }
    }

    if ($sku) {
        try {
            $product->set_sku($sku);
        } catch (WC_Data_Exception $exception) {
            if (empty($options['sku_retry_done'])) {
                $resolved_product_id = wc_get_product_id_by_sku($sku);
                if ($resolved_product_id) {
                    $retry_data = $data;
                    $retry_data['product']['id'] = $resolved_product_id;
                    $retry_options = $options;
                    $retry_options['sku_retry_done'] = true;
                    return bw_import_save_product_from_row($retry_data, false, $retry_options);
                }
            }

            return new WP_Error('bw_import_sku', $exception->getMessage());
        }
    }

    if (!empty($data['product']['name'])) {
        $product->set_name($data['product']['name']);
    }

    if (!empty($data['product']['slug'])) {
        $product->set_slug($data['product']['slug']);
    }

    if (!empty($data['product']['status'])) {
        $product->set_status($data['product']['status']);
    }

    if (!empty($data['product']['description'])) {
        $product->set_description($data['product']['description']);
    }

    if (!empty($data['product']['short_description'])) {
        $product->set_short_description($data['product']['short_description']);
    }

    if (isset($data['product']['regular_price'])) {
        $product->set_regular_price($data['product']['regular_price']);
    }

    if (isset($data['product']['sale_price'])) {
        $product->set_sale_price($data['product']['sale_price']);
    }

    if (!empty($data['product']['sale_start'])) {
        $product->set_date_on_sale_from($data['product']['sale_start']);
    }

    if (!empty($data['product']['sale_end'])) {
        $product->set_date_on_sale_to($data['product']['sale_end']);
    }

    if (isset($data['product']['stock_quantity'])) {
        $product->set_stock_quantity($data['product']['stock_quantity']);
    }

    if (isset($data['product']['manage_stock'])) {
        $product->set_manage_stock((bool) $data['product']['manage_stock']);
    }

    if (!empty($data['product']['stock_status'])) {
        $product->set_stock_status($data['product']['stock_status']);
    }

    if (!empty($data['product']['backorders'])) {
        $product->set_backorders($data['product']['backorders']);
    }

    if (isset($data['product']['sold_individually'])) {
        $product->set_sold_individually((bool) $data['product']['sold_individually']);
    }

    foreach (['weight', 'length', 'width', 'height'] as $dimension) {
        if (isset($data['product'][$dimension])) {
            $setter = 'set_' . $dimension;
            $product->$setter($data['product'][$dimension]);
        }
    }

    if (!empty($data['product']['shipping_class'])) {
        $shipping_class_id = 0;

        if (is_numeric($data['product']['shipping_class'])) {
            $shipping_class_id = (int) $data['product']['shipping_class'];
        } else {
            $existing_shipping_class = term_exists($data['product']['shipping_class'], 'product_shipping_class');

            if ($existing_shipping_class && !is_wp_error($existing_shipping_class)) {
                $shipping_class_id = (int) $existing_shipping_class['term_id'];
            } else {
                $created_shipping_class = wp_insert_term($data['product']['shipping_class'], 'product_shipping_class');

                if ($created_shipping_class && !is_wp_error($created_shipping_class)) {
                    $shipping_class_id = (int) $created_shipping_class['term_id'];
                }
            }
        }

        if ($shipping_class_id) {
            $product->set_shipping_class_id($shipping_class_id);
        }
    }

    if (!empty($data['product']['tax_status'])) {
        $product->set_tax_status($data['product']['tax_status']);
    }

    if (!empty($data['product']['tax_class'])) {
        $product->set_tax_class($data['product']['tax_class']);
    }

    if (!empty($data['categories'])) {
        $category_ids = bw_import_resolve_term_ids($data['categories'], 'product_cat');
        if (!empty($category_ids)) {
            $product->set_category_ids($category_ids);
        }
    }

    if (!empty($data['tags'])) {
        $tag_ids = bw_import_resolve_term_ids($data['tags'], 'product_tag');
        if (!empty($tag_ids)) {
            $product->set_tag_ids($tag_ids);
        }
    }

    if (!empty($data['meta'])) {
        foreach ($data['meta'] as $meta_key => $meta_value) {
            $product->update_meta_data($meta_key, $meta_value);
        }
    }

    if (!empty($data['attributes'])) {
        $attribute_objects = bw_import_build_attributes($data['attributes']);
        if (!empty($attribute_objects)) {
            $product->set_attributes($attribute_objects);
        }
    }

    if (!empty($data['upsells'])) {
        $product->set_upsell_ids(bw_import_locate_product_ids($data['upsells']));
    }

    if (!empty($data['cross_sells'])) {
        $product->set_cross_sell_ids(bw_import_locate_product_ids($data['cross_sells']));
    }

    if (!empty($options['skip_images'])) {
        if (!empty($data['product']['featured_image']) || !empty($data['product']['gallery'])) {
            $warnings[] = __('Images skipped by configuration.', 'bw');
        }
    } else {
        if (!empty($data['product']['featured_image'])) {
            $attachment_id = bw_import_handle_image($data['product']['featured_image'], $product_id);
            if ($attachment_id) {
                $product->set_image_id($attachment_id);
            } else {
                $warnings[] = __('Featured image sideload failed.', 'bw');
            }
        }

        if (!empty($data['product']['gallery'])) {
            $gallery_ids = [];
            foreach ($data['product']['gallery'] as $image_url) {
                $image_id = bw_import_handle_image($image_url, $product_id);
                if ($image_id) {
                    $gallery_ids[] = $image_id;
                } else {
                    $warnings[] = sprintf(
                        /* translators: %s: image URL */
                        __('Gallery image sideload failed: %s', 'bw'),
                        esc_url_raw($image_url)
                    );
                }
            }
            $product->set_gallery_image_ids($gallery_ids);
        }
    }

    try {
        $product->save();
    } catch (Throwable $exception) {
        return new WP_Error('bw_import_save', $exception->getMessage());
    }

    return [
        'status' => $status,
        'warnings' => $warnings,
    ];
}

/**
 * Recupera ID prodotto da ID o SKU.
 *
 * @param array $references Elenco di riferimenti.
 *
 * @return array
 */
function bw_import_locate_product_ids($references)
{
    $ids = [];
    foreach ($references as $reference) {
        $reference = trim($reference);
        if (is_numeric($reference)) {
            $ids[] = (int) $reference;
            continue;
        }

        $maybe_id = wc_get_product_id_by_sku($reference);
        if ($maybe_id) {
            $ids[] = $maybe_id;
        }
    }

    return $ids;
}

/**
 * Imposta termini su tassonomie prodotto.
 *
 * @param int    $product_id ID prodotto.
 * @param array  $terms      Elenco termini.
 * @param string $taxonomy   Tassonomia.
 */
function bw_import_assign_terms($product_id, $terms, $taxonomy)
{
    $term_ids = [];
    foreach ($terms as $term) {
        $existing = term_exists($term, $taxonomy);
        if ($existing && !is_wp_error($existing)) {
            $term_ids[] = (int) $existing['term_id'];
        } else {
            $created = wp_insert_term($term, $taxonomy);
            if (!is_wp_error($created)) {
                $term_ids[] = (int) $created['term_id'];
            }
        }
    }

    if (!empty($term_ids)) {
        wp_set_object_terms($product_id, $term_ids, $taxonomy, false);
    }
}

/**
 * Resolve term IDs for a taxonomy, creating missing terms when possible.
 *
 * @param array  $terms    Terms list.
 * @param string $taxonomy Taxonomy slug.
 *
 * @return array
 */
function bw_import_resolve_term_ids($terms, $taxonomy)
{
    $term_ids = [];
    foreach ((array) $terms as $term) {
        $existing = term_exists($term, $taxonomy);
        if ($existing && !is_wp_error($existing)) {
            $term_ids[] = (int) $existing['term_id'];
            continue;
        }

        $created = wp_insert_term($term, $taxonomy);
        if (!is_wp_error($created) && !empty($created['term_id'])) {
            $term_ids[] = (int) $created['term_id'];
        }
    }

    return $term_ids;
}

/**
 * Gestisce il download e l'associazione di immagini da URL.
 *
 * @param string $image_url  URL immagine.
 * @param int    $product_id ID prodotto.
 *
 * @return int Attachment ID.
 */
function bw_import_handle_image($image_url, $product_id)
{
    if (empty($image_url)) {
        return 0;
    }

    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $image_id = attachment_url_to_postid($image_url);
    if ($image_id) {
        return $image_id;
    }

    $sideload = media_sideload_image($image_url, $product_id, null, 'id');
    if (is_wp_error($sideload)) {
        return 0;
    }

    return (int) $sideload;
}

/**
 * Applica attributi globali al prodotto.
 *
 * @param int   $product_id ID prodotto.
 * @param array $attributes Attributi.
 */
function bw_import_apply_attributes($product_id, $attributes)
{
    $product_attributes = [];

    foreach ($attributes as $taxonomy => $value) {
        $terms = bw_import_explode_list($value);
        if (empty($terms)) {
            continue;
        }

        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $term_ids = [];
        foreach ($terms as $term) {
            $existing = term_exists($term, $taxonomy);
            if ($existing && !is_wp_error($existing)) {
                $term_ids[] = (int) $existing['term_id'];
            } else {
                $inserted = wp_insert_term($term, $taxonomy);
                if (!is_wp_error($inserted)) {
                    $term_ids[] = (int) $inserted['term_id'];
                }
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($product_id, $term_ids, $taxonomy, false);
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_options($term_ids);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $product_attributes[$taxonomy] = $attribute;
    }

    if (!empty($product_attributes)) {
        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_attributes($product_attributes);
            $product->save();
        }
    }
}

/**
 * Build product attributes array without saving the product.
 *
 * @param array $attributes Raw attributes map.
 *
 * @return array
 */
function bw_import_build_attributes($attributes)
{
    $product_attributes = [];

    foreach ((array) $attributes as $taxonomy => $value) {
        $terms = bw_import_explode_list($value);
        if (empty($terms) || !taxonomy_exists($taxonomy)) {
            continue;
        }

        $term_ids = bw_import_resolve_term_ids($terms, $taxonomy);
        if (empty($term_ids)) {
            continue;
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_options($term_ids);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $product_attributes[$taxonomy] = $attribute;
    }

    return $product_attributes;
}

/**
 * Find mapped CSV header for a target field.
 *
 * @param array  $headers Headers list.
 * @param array  $mapping Mapping array.
 * @param string $target  Mapping target.
 *
 * @return string
 */
function bw_import_find_mapped_header($headers, $mapping, $target)
{
    foreach ((array) $headers as $header) {
        if (isset($mapping[$header]) && $mapping[$header] === $target) {
            return (string) $header;
        }
    }

    return '';
}

/**
 * Validate duplicate SKU rows before any write operation.
 *
 * @param string $file_path CSV path.
 * @param array  $headers   CSV headers.
 * @param array  $mapping   Mapping snapshot.
 *
 * @return true|WP_Error
 */
function bw_import_validate_unique_skus($file_path, $headers, $mapping)
{
    $sku_header = bw_import_find_mapped_header($headers, $mapping, 'sku');
    if ($sku_header === '') {
        return new WP_Error('bw_import_missing_sku_mapping', __('SKU mapping is required to run the import.', 'bw'));
    }

    $sku_column_index = array_search($sku_header, (array) $headers, true);
    if ($sku_column_index === false) {
        return new WP_Error('bw_import_missing_sku_column', __('Mapped SKU column was not found in CSV headers.', 'bw'));
    }

    $seen_skus = [];
    $cursor = 0;
    $chunk_size = bw_import_chunk_size();
    $duplicate_examples = [];

    while (true) {
        $chunk = bw_import_parse_csv_chunk($file_path, $cursor, $chunk_size);
        if (is_wp_error($chunk)) {
            return $chunk;
        }

        foreach ($chunk['rows'] as $row) {
            $raw_sku = isset($row[$sku_column_index]) ? $row[$sku_column_index] : '';
            $sku = sanitize_text_field(trim((string) $raw_sku));
            if ($sku === '') {
                continue;
            }

            if (isset($seen_skus[$sku])) {
                $duplicate_examples[] = $sku;
                if (count($duplicate_examples) >= 10) {
                    break 2;
                }
            } else {
                $seen_skus[$sku] = true;
            }
        }

        if (!empty($chunk['eof'])) {
            break;
        }
        $cursor = (int) $chunk['next_row'];
    }

    if (!empty($duplicate_examples)) {
        return new WP_Error(
            'bw_import_duplicate_sku',
            sprintf(
                /* translators: %s: duplicate SKU list */
                __('Duplicate SKU values found in CSV. Import aborted before writes. Examples: %s', 'bw'),
                implode(', ', array_unique($duplicate_examples))
            )
        );
    }

    return true;
}

/**
 * Merge and bound user-visible importer messages.
 *
 * @param array $existing Existing list.
 * @param array $incoming New list.
 * @param int   $limit    Max retained messages.
 *
 * @return array
 */
function bw_import_merge_bounded_messages($existing, $incoming, $limit = 20)
{
    $merged = array_merge((array) $existing, (array) $incoming);
    if (count($merged) <= $limit) {
        return $merged;
    }

    return array_slice($merged, -1 * absint($limit));
}

/**
 * Renderizza il tab Loading
 */
function bw_site_render_loading_tab()
{
    $saved = false;

    if (isset($_POST['bw_loading_settings_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_loading_settings_save', 'bw_loading_settings_nonce');

        $global_spinner_hidden = isset($_POST['bw_loading_global_spinner_hidden']) ? 1 : 0;
        update_option('bw_loading_global_spinner_hidden', $global_spinner_hidden);

        $saved = true;
    }

    $global_spinner_hidden = get_option('bw_loading_global_spinner_hidden', 1);
    ?>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_loading_settings_save', 'bw_loading_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Nascondi Spinner WC</th>
                <td>
                    <label for="bw_loading_global_spinner_hidden">
                        <input name="bw_loading_global_spinner_hidden" type="checkbox" id="bw_loading_global_spinner_hidden"
                            value="1" <?php checked(1, $global_spinner_hidden); ?> />
                        Nascondi caricamento standard e maschera grigia di WooCommerce (Checkout e altro)
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button('Salva Impostazioni', 'primary', 'bw_loading_settings_submit'); ?>
    </form>
    <?php
}

// bw_site_render_google_pay_tab() removed — settings are managed inside
// bw_site_render_checkout_tab() under the "Google Pay" sub-tab.
