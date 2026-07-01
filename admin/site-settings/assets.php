<?php
/**
 * Blackwork Site Settings — admin asset enqueue (icon styles, UI kit, per-tab assets).
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

/**
 * Carica gli assets per la pagina admin
 */
function bw_site_settings_admin_assets($hook)
{
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $current_tab_raw = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';

    $is_site_settings_page = ('blackwork-site-settings' === $current_page || 'toplevel_page_blackwork-site-settings' === $hook);
    $is_product_transfer_page = (
        'blackwork-product-import-export' === $current_page
        || 'blackwork-site-settings_page_blackwork-product-import-export' === $hook
    );
    $is_product_labels_page = (
        'bw-product-labels-settings' === $current_page
        || 'blackwork-site-settings_page_bw-product-labels-settings' === $hook
    );
    $is_mail_marketing_page = (
        'blackwork-mail-marketing' === $current_page
        || 'blackwork-site-settings_page_blackwork-mail-marketing' === $hook
    );

    // Site Settings asset matrix is restricted to Site Settings and Mail Marketing pages.
    if (!$is_site_settings_page && !$is_mail_marketing_page && !$is_product_labels_page && !$is_product_transfer_page) {
        return;
    }

    $site_settings_tabs = [
        'info',
        'layout',
        'cart-popup',
        'shipping',
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

    if ($is_product_transfer_page || ($is_site_settings_page && 'import-product' === $current_site_settings_tab)) {
        $papaparse_path = BW_MEW_PATH . 'assets/lib/papaparse/papaparse.min.js';
        $import_admin_js_path = BW_MEW_PATH . 'admin/js/bw-product-import-admin.js';

        wp_enqueue_script(
            'bw-papaparse-admin',
            BW_MEW_URL . 'assets/lib/papaparse/papaparse.min.js',
            [],
            file_exists($papaparse_path) ? filemtime($papaparse_path) : '1.0.0',
            true
        );

        wp_enqueue_script(
            'bw-product-import-admin',
            BW_MEW_URL . 'admin/js/bw-product-import-admin.js',
            ['jquery', 'bw-papaparse-admin'],
            file_exists($import_admin_js_path) ? filemtime($import_admin_js_path) : '1.0.0',
            true
        );

        wp_localize_script(
            'bw-product-import-admin',
            'bwProductImportAdmin',
            [
                'strings' => [
                    'modalTitle' => esc_html__('CSV analysis', 'bw'),
                    'modalIntro' => esc_html__('We checked the uploaded CSV before import. Valid rows will continue, malformed rows will be skipped.', 'bw'),
                    'totalRows' => esc_html__('Total rows', 'bw'),
                    'validRows' => esc_html__('Valid rows', 'bw'),
                    'invalidRows' => esc_html__('Invalid rows', 'bw'),
                    'parserWarnings' => esc_html__('Parser warnings', 'bw'),
                    'continueImport' => esc_html__('Continue import', 'bw'),
                    'close' => esc_html__('Close', 'bw'),
                    'allRowsInvalid' => esc_html__('No valid product rows were found in this CSV. Please check the file and try again.', 'bw'),
                    'invalidRowsWarning' => esc_html__('Some rows are malformed and will be skipped. The import will continue with the valid rows only.', 'bw'),
                    'parseFailure' => esc_html__('The CSV could not be analyzed safely in the browser. The original file will be submitted unchanged.', 'bw'),
                    'preflightFailure' => esc_html__('CSV analysis failed unexpectedly. The original file will be submitted unchanged.', 'bw'),
                    'invalidPreviewLabel' => esc_html__('Example skipped rows', 'bw'),
                    'missingSkuAndName' => esc_html__('Missing SKU and name', 'bw'),
                    'missingVariationIdentifiers' => esc_html__('Missing variation SKU, parent SKU, or variation name', 'bw'),
                    'defaultUploadCta' => esc_html__('Upload & Analyze', 'bw'),
                    'analyzingCsv' => esc_html__('Analyzing CSV...', 'bw'),
                    'analyzingCsvStatus' => esc_html__('Analyzing CSV in your browser. A preflight check will open before upload.', 'bw'),
                    'preflightReadyStatus' => esc_html__('Preflight complete. Review the summary, then continue to upload and open the mapping step.', 'bw'),
                    'uploadingCsv' => esc_html__('Uploading CSV...', 'bw'),
                    'uploadingCsvCta' => esc_html__('Uploading...', 'bw'),
                    'selectedFilePrefix' => esc_html__('Selected file: ', 'bw'),
                    'fileSelectedStatus' => esc_html__('File selected. Click Upload & Analyze to continue.', 'bw'),
                    'copyPrompt' => esc_html__('Copy Prompt', 'bw'),
                    'copiedPrompt' => esc_html__('Copied', 'bw'),
                    'copyFailed' => esc_html__('Copy failed. Please try again.', 'bw'),
                ],
                'prompts' => bw_product_import_prompt_payload(),
            ]
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
