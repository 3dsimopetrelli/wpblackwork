<?php
/**
 * Blackwork Site Settings — main settings page router / tab dispatch.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

    if ('import-product' === $requested_tab) {
        wp_safe_redirect(bw_product_import_export_admin_url());
        exit;
    }

    // Determina quale tab è attivo
    $allowed_tabs = ['info', 'layout', 'cart-popup', 'shipping', 'bw-coming-soon', 'account-page', 'my-account-page', 'checkout', 'redirect', 'loading'];
    $active_tab = $requested_tab;
    if (!in_array($active_tab, $allowed_tabs, true)) {
        $active_tab = 'info';
    }

    $save_button_map = [
        'layout' => 'bw_layout_settings_submit',
        'cart-popup' => 'bw_cart_popup_submit',
        'shipping' => 'bw_cart_shipping_submit',
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
                <a href="?page=blackwork-site-settings&tab=shipping"
                    class="nav-tab <?php echo $active_tab === 'shipping' ? 'nav-tab-active' : ''; ?>">
                    Shipping
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
                } elseif ($active_tab === 'shipping') {
                    bw_site_render_shipping_tab();
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
