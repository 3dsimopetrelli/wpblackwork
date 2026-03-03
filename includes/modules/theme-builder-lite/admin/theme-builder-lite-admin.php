<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_register_admin_settings')) {
    function bw_tbl_register_admin_settings()
    {
        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_FEATURE_FLAGS_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_tbl_sanitize_feature_flags',
                'default' => bw_tbl_feature_flag_defaults(),
            ]
        );

        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_CUSTOM_FONTS_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_tbl_sanitize_custom_fonts_option',
                'default' => bw_tbl_default_custom_fonts_option(),
            ]
        );

        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_FOOTER_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_tbl_sanitize_footer_option',
                'default' => bw_tbl_default_footer_option(),
            ]
        );

        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_SINGLE_PRODUCT_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_tbl_sanitize_single_product_option',
                'default' => bw_tbl_default_single_product_option(),
            ]
        );
    }
}
add_action('admin_init', 'bw_tbl_register_admin_settings');

if (!function_exists('bw_tbl_admin_menu')) {
    function bw_tbl_admin_menu()
    {
        add_submenu_page(
            'blackwork-site-settings',
            __('Theme Builder Lite', 'bw'),
            __('Theme Builder Lite', 'bw'),
            'manage_options',
            'bw-theme-builder-lite-settings',
            'bw_tbl_render_admin_page'
        );
    }
}
add_action('admin_menu', 'bw_tbl_admin_menu', 21);

if (!function_exists('bw_tbl_admin_enqueue_assets')) {
    function bw_tbl_admin_enqueue_assets($hook)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

        $is_settings_page = (
            'bw-theme-builder-lite-settings' === $page
            || 'blackwork-site-settings_page_bw-theme-builder-lite-settings' === $hook
            || ($screen && 'blackwork-site-settings_page_bw-theme-builder-lite-settings' === $screen->id)
        );

        // Never load the settings JS inside Elementor editor routes.
        if ('elementor' === $action) {
            return;
        }

        if (!$is_settings_page) {
            return;
        }

        wp_enqueue_media();

        $script_path = BW_MEW_PATH . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js';
        wp_enqueue_script(
            'bw-theme-builder-lite-admin',
            BW_MEW_URL . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js',
            ['jquery', 'media-editor', 'media-models', 'media-views'],
            file_exists($script_path) ? filemtime($script_path) : '1.0.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_tbl_admin_enqueue_assets');

if (!function_exists('bw_tbl_render_font_row')) {
    function bw_tbl_render_font_row($index, $font)
    {
        $index = absint($index);

        $family = isset($font['font_family']) ? (string) $font['font_family'] : '';
        $sources = isset($font['sources']) && is_array($font['sources']) ? $font['sources'] : [];
        $woff2 = isset($sources['woff2']) ? (string) $sources['woff2'] : '';
        $woff = isset($sources['woff']) ? (string) $sources['woff'] : '';
        $weight = isset($font['font_weight']) ? (string) $font['font_weight'] : '400';
        $style = isset($font['font_style']) ? (string) $font['font_style'] : 'normal';
        ?>
        <tr class="bw-tbl-font-row">
            <td>
                <input type="text" class="regular-text" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][font_family]" value="<?php echo esc_attr($family); ?>" placeholder="Inter" />
            </td>
            <td>
                <input type="url" class="regular-text bw-tbl-font-source bw-tbl-font-source-woff2" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][sources][woff2]" value="<?php echo esc_url($woff2); ?>" placeholder="https://...font.woff2" />
                <button type="button" class="button bw-tbl-media-select" data-format="woff2"><?php esc_html_e('Select .woff2', 'bw'); ?></button>
            </td>
            <td>
                <input type="url" class="regular-text bw-tbl-font-source bw-tbl-font-source-woff" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][sources][woff]" value="<?php echo esc_url($woff); ?>" placeholder="https://...font.woff" />
                <button type="button" class="button bw-tbl-media-select" data-format="woff"><?php esc_html_e('Select .woff', 'bw'); ?></button>
            </td>
            <td>
                <input type="text" class="small-text" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][font_weight]" value="<?php echo esc_attr($weight); ?>" placeholder="400" />
            </td>
            <td>
                <select name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][font_style]">
                    <option value="normal" <?php selected($style, 'normal'); ?>><?php esc_html_e('normal', 'bw'); ?></option>
                    <option value="italic" <?php selected($style, 'italic'); ?>><?php esc_html_e('italic', 'bw'); ?></option>
                    <option value="oblique" <?php selected($style, 'oblique'); ?>><?php esc_html_e('oblique', 'bw'); ?></option>
                </select>
            </td>
            <td>
                <button type="button" class="button-link-delete bw-tbl-remove-font-row"><?php esc_html_e('Remove', 'bw'); ?></button>
            </td>
        </tr>
        <?php
    }
}

if (!function_exists('bw_tbl_render_admin_page')) {
    function bw_tbl_render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $flags = bw_tbl_get_feature_flags();
        $fonts_option = bw_tbl_get_custom_fonts_option();
        $footer_option = bw_tbl_get_footer_option();
        $footer_choices = bw_tbl_get_footer_template_choices();
        $single_product_option = bw_tbl_get_single_product_option();
        $single_product_choices = bw_tbl_get_single_product_template_choices();
        $parent_product_categories = bw_tbl_get_parent_product_category_choices();

        $fonts = isset($fonts_option['fonts']) && is_array($fonts_option['fonts']) ? $fonts_option['fonts'] : [];
        if (empty($fonts)) {
            $fonts = [
                [
                    'font_family' => '',
                    'sources' => ['woff2' => '', 'woff' => ''],
                    'font_weight' => '400',
                    'font_style' => 'normal',
                ],
            ];
        }

        ?>
        <div class="wrap bw-tbl-admin-wrap">
            <h1><?php esc_html_e('Theme Builder Lite', 'bw'); ?></h1>
            <p><?php esc_html_e('Controls for Fonts, Footer, and Single Product category-based template override.', 'bw'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('bw_tbl_settings_group'); ?>

                <h2 class="nav-tab-wrapper" id="bw-tbl-tabs" style="margin-bottom:16px;">
                    <a href="#bw-tbl-tab-settings" class="nav-tab nav-tab-active" data-bw-tbl-tab="settings"><?php esc_html_e('Settings', 'bw'); ?></a>
                    <a href="#bw-tbl-tab-fonts" class="nav-tab" data-bw-tbl-tab="fonts"><?php esc_html_e('Fonts', 'bw'); ?></a>
                    <a href="#bw-tbl-tab-footer" class="nav-tab" data-bw-tbl-tab="footer"><?php esc_html_e('Footer', 'bw'); ?></a>
                    <a href="#bw-tbl-tab-single-product" class="nav-tab" data-bw-tbl-tab="single-product"><?php esc_html_e('Post Product Category', 'bw'); ?></a>
                </h2>

                <div id="bw-tbl-tab-settings" class="bw-tbl-tab-panel is-active" data-bw-tbl-panel="settings">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Theme Builder Lite', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-enabled" type="checkbox" name="<?php echo esc_attr(BW_TBL_FEATURE_FLAGS_OPTION); ?>[enabled]" value="1" <?php checked(!empty($flags['enabled'])); ?> />
                                    <?php esc_html_e('Master switch for Theme Builder Lite runtime.', 'bw'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('If disabled, all Theme Builder Lite frontend output is disabled (fonts + footer override).', 'bw'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Templates Resolver (Phase 2 Step 1)', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-templates-enabled" type="checkbox" name="<?php echo esc_attr(BW_TBL_FEATURE_FLAGS_OPTION); ?>[templates_enabled]" value="1" <?php checked(!empty($flags['templates_enabled'])); ?> />
                                    <?php esc_html_e('Enable template_include resolver for Single Post, Single Page, Search, and 404 contexts.', 'bw'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Fail-open: if no valid template is resolved, the active theme template is used.', 'bw'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="bw-tbl-tab-fonts" class="bw-tbl-tab-panel" data-bw-tbl-panel="fonts" style="display:none;">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Custom Fonts', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-custom-fonts" type="checkbox" name="<?php echo esc_attr(BW_TBL_FEATURE_FLAGS_OPTION); ?>[custom_fonts_enabled]" value="1" <?php checked(!empty($flags['custom_fonts_enabled'])); ?> />
                                    <?php esc_html_e('Output @font-face CSS on frontend when fonts are configured.', 'bw'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <div id="bw-tbl-fonts-controls" style="margin-top:8px;">
                        <p><?php esc_html_e('Upload/select WOFF2 (preferred) or WOFF files from the WordPress media library.', 'bw'); ?></p>
                        <table class="widefat striped" id="bw-tbl-fonts-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Font Family', 'bw'); ?></th>
                                    <th><?php esc_html_e('WOFF2 Source', 'bw'); ?></th>
                                    <th><?php esc_html_e('WOFF Source', 'bw'); ?></th>
                                    <th><?php esc_html_e('Weight', 'bw'); ?></th>
                                    <th><?php esc_html_e('Style', 'bw'); ?></th>
                                    <th><?php esc_html_e('Actions', 'bw'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fonts as $index => $font) : ?>
                                    <?php bw_tbl_render_font_row($index, $font); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <button type="button" class="button" id="bw-tbl-add-font-row"><?php esc_html_e('Add Font', 'bw'); ?></button>
                        </p>
                    </div>
                </div>

                <div id="bw-tbl-tab-footer" class="bw-tbl-tab-panel" data-bw-tbl-panel="footer" style="display:none;">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Footer Override', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-footer-override" type="checkbox" name="<?php echo esc_attr(BW_TBL_FEATURE_FLAGS_OPTION); ?>[footer_override_enabled]" value="1" <?php checked(!empty($flags['footer_override_enabled'])); ?> />
                                    <?php esc_html_e('Render active BW footer template instead of theme footer.', 'bw'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <div id="bw-tbl-footer-controls" style="margin-top:8px;">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="bw-tbl-active-footer-template"><?php esc_html_e('Active Footer Template', 'bw'); ?></label></th>
                                <td>
                                    <select id="bw-tbl-active-footer-template" name="<?php echo esc_attr(BW_TBL_FOOTER_OPTION); ?>[active_footer_template_id]">
                                        <option value="0"><?php esc_html_e('Use theme footer (disabled)', 'bw'); ?></option>
                                        <?php foreach ($footer_choices as $template_id => $template_title) : ?>
                                            <option value="<?php echo esc_attr((string) $template_id); ?>" <?php selected((int) $footer_option['active_footer_template_id'], (int) $template_id); ?>><?php echo esc_html($template_title); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('Create/edit templates under Blackwork Site > BW Templates. Phase 1 supports only templates with type "footer".', 'bw'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div id="bw-tbl-tab-single-product" class="bw-tbl-tab-panel" data-bw-tbl-panel="single-product" style="display:none;">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Single Product Override', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-single-product-conditions" type="checkbox" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_OPTION); ?>[enabled]" value="1" <?php checked(!empty($single_product_option['enabled'])); ?> />
                                    <?php esc_html_e('Resolve single product template from settings include/exclude product categories.', 'bw'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('When enabled, this settings tab is the source of truth for Single Product conditions.', 'bw'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div id="bw-tbl-single-product-controls" style="margin-top:8px;">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="bw-tbl-active-single-product-template"><?php esc_html_e('Active Single Product Template', 'bw'); ?></label></th>
                                <td>
                                    <select id="bw-tbl-active-single-product-template" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_OPTION); ?>[active_single_product_template_id]">
                                        <option value="0"><?php esc_html_e('Use theme single product template (disabled)', 'bw'); ?></option>
                                        <?php foreach ($single_product_choices as $template_id => $template_title) : ?>
                                            <option value="<?php echo esc_attr((string) $template_id); ?>" <?php selected((int) $single_product_option['active_single_product_template_id'], (int) $template_id); ?>><?php echo esc_html($template_title); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-tbl-include-product-cat"><?php esc_html_e('Include Product Categories', 'bw'); ?></label></th>
                                <td>
                                    <select id="bw-tbl-include-product-cat" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_OPTION); ?>[include_product_cat][]" multiple="multiple" size="8" style="min-width:280px;">
                                        <?php foreach ($parent_product_categories as $term_id => $term_name) : ?>
                                            <option value="<?php echo esc_attr((string) $term_id); ?>" <?php selected(in_array((int) $term_id, (array) $single_product_option['include_product_cat'], true)); ?>><?php echo esc_html($term_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('If empty, include behaves as match-all.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-tbl-exclude-product-cat"><?php esc_html_e('Exclude Product Categories', 'bw'); ?></label></th>
                                <td>
                                    <select id="bw-tbl-exclude-product-cat" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_OPTION); ?>[exclude_product_cat][]" multiple="multiple" size="8" style="min-width:280px;">
                                        <?php foreach ($parent_product_categories as $term_id => $term_name) : ?>
                                            <option value="<?php echo esc_attr((string) $term_id); ?>" <?php selected(in_array((int) $term_id, (array) $single_product_option['exclude_product_cat'], true)); ?>><?php echo esc_html($term_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Exclude rules are evaluated before include rules.', 'bw'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button(__('Save Theme Builder Lite Settings', 'bw')); ?>
            </form>
        </div>
        <script type="text/html" id="tmpl-bw-tbl-font-row">
            <?php
            bw_tbl_render_font_row(
                99999,
                [
                    'font_family' => '',
                    'sources' => ['woff2' => '', 'woff' => ''],
                    'font_weight' => '400',
                    'font_style' => 'normal',
                ]
            );
            ?>
        </script>
        <?php
    }
}
