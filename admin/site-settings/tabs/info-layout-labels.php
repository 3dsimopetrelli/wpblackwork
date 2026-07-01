<?php
/**
 * Blackwork Site Settings — Info, Layout, and Product Labels tab renderers.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

        <section class="bw-admin-card bw-import-panel">
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
