<?php
/**
 * Blackwork Site Settings — admin menu registration, submenu ordering, screen detection.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

    add_submenu_page(
        'blackwork-site-settings',
        __('Product Import / Export', 'bw'),
        __('Product Import / Export', 'bw'),
        'manage_options',
        'blackwork-product-import-export',
        'bw_product_import_export_page'
    );
}

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
        'blackwork-product-import-export',
        'bw-product-labels-settings',
        'blackwork-mail-marketing',
        'bw-reviews',
        'bw-reviews-settings',
        'bw-reviews-edit',
    ];

    return !empty($page_slug) && in_array($page_slug, $allowed_pages, true);
}

function bw_product_import_export_admin_url($args = [])
{
    $base_args = ['page' => 'blackwork-product-import-export'];

    if (isset($_GET['product_flow'])) {
        $product_flow = sanitize_key(wp_unslash($_GET['product_flow']));
        if (in_array($product_flow, ['export', 'import'], true)) {
            $base_args['product_flow'] = $product_flow;
        }
    }

    foreach ((array) $args as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $base_args[$key] = $value;
    }

    return add_query_arg($base_args, admin_url('admin.php'));
}

function bw_product_import_export_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap bw-admin-root bw-admin-page bw-admin-page-site-settings">
        <div class="bw-admin-header">
            <h1 class="bw-admin-title"><?php esc_html_e('Product Import / Export', 'bw'); ?></h1>
            <p class="bw-admin-subtitle"><?php esc_html_e('Export structured product CSVs or import product data back into WooCommerce.', 'bw'); ?></p>
        </div>
        <div class="bw-admin-site-settings-content">
            <?php bw_site_render_import_product_tab(); ?>
        </div>
    </div>
    <?php
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
