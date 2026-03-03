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

        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_SINGLE_PRODUCT_RULES_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_tbl_sanitize_single_product_rules_option',
                'default' => bw_tbl_default_single_product_rules_option(),
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

        $style_path = BW_MEW_PATH . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.css';
        wp_enqueue_style(
            'bw-theme-builder-lite-admin',
            BW_MEW_URL . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.css',
            [],
            file_exists($style_path) ? filemtime($style_path) : '1.0.0'
        );

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

if (!function_exists('bw_tbl_render_product_cat_checklist')) {
    function bw_tbl_render_product_cat_checklist($name, $selected_ids, $parent_product_categories)
    {
        $selected_ids = is_array($selected_ids) ? array_values(array_map('absint', $selected_ids)) : [];
        $parent_product_categories = is_array($parent_product_categories) ? $parent_product_categories : [];
        $parent_term_ids = array_values(array_map('absint', array_keys($parent_product_categories)));
        $parent_term_map = array_fill_keys($parent_term_ids, true);
        $selected_ids = array_values(
            array_filter(
                $selected_ids,
                static function ($term_id) use ($parent_term_map) {
                    $term_id = absint($term_id);
                    return $term_id > 0 && isset($parent_term_map[$term_id]);
                }
            )
        );

        if (empty($parent_term_ids)) {
            return '';
        }

        $selected_map = array_fill_keys($selected_ids, true);
        $html = '<ul>';
        foreach ($parent_product_categories as $term_id => $term_name) {
            $term_id = absint($term_id);
            if ($term_id <= 0) {
                continue;
            }
            $checked = isset($selected_map[$term_id]) ? ' checked="checked"' : '';
            $html .= '<li><label><input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr((string) $term_id) . '"' . $checked . ' /> ' . esc_html((string) $term_name) . '</label></li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

if (!function_exists('bw_tbl_render_single_product_rule_row')) {
    function bw_tbl_render_single_product_rule_row($index, $rule, $template_choices, $parent_product_categories)
    {
        $index = absint($index);
        $rule = is_array($rule) ? $rule : [];
        $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
        $include = isset($rule['include_product_cat']) && is_array($rule['include_product_cat']) ? array_map('absint', $rule['include_product_cat']) : [];
        $include_mode = !empty($include) ? 'selected' : 'all';
        $exclude = isset($rule['exclude_product_cat']) && is_array($rule['exclude_product_cat']) ? array_map('absint', $rule['exclude_product_cat']) : [];
        $exclude_enabled = !empty($exclude);
        $include_input_name = BW_TBL_SINGLE_PRODUCT_RULES_OPTION . '[rules][' . $index . '][include_product_cat][]';
        $exclude_input_name = BW_TBL_SINGLE_PRODUCT_RULES_OPTION . '[rules][' . $index . '][exclude_product_cat][]';
        $include_checklist = bw_tbl_render_product_cat_checklist($include_input_name, $include, $parent_product_categories);
        $exclude_checklist = bw_tbl_render_product_cat_checklist($exclude_input_name, $exclude, $parent_product_categories);
        ?>
        <div class="bw-tbl-single-product-rule" data-bw-tbl-rule-index="<?php echo esc_attr((string) $index); ?>">
            <p class="bw-tbl-rule-heading">
                <strong><?php esc_html_e('Rule', 'bw'); ?> #<span class="bw-tbl-rule-number"><?php echo esc_html((string) ($index + 1)); ?></span></strong>
            </p>
            <table class="form-table bw-tbl-rule-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Active Single Product Template', 'bw'); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][template_id]">
                            <option value="0"><?php esc_html_e('Select template', 'bw'); ?></option>
                            <?php foreach ($template_choices as $choice_id => $choice_title) : ?>
                                <option value="<?php echo esc_attr((string) $choice_id); ?>" <?php selected($template_id, (int) $choice_id); ?>><?php echo esc_html($choice_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Include Product Categories', 'bw'); ?></label>
                    </th>
                    <td>
                        <fieldset class="bw-tbl-include-mode">
                            <label>
                                <input type="radio" class="bw-tbl-include-mode-radio" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][include_mode]" value="all" <?php checked('all', $include_mode); ?> />
                                <?php esc_html_e('Apply to all categories', 'bw'); ?>
                            </label>
                            <br />
                            <label>
                                <input type="radio" class="bw-tbl-include-mode-radio" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][include_mode]" value="selected" <?php checked('selected', $include_mode); ?> />
                                <?php esc_html_e('Apply only to selected categories', 'bw'); ?>
                            </label>
                        </fieldset>
                        <div class="bw-tbl-include-fields">
                            <div class="bw-tbl-term-checklist-wrap">
                                <?php echo $include_checklist; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                        <p class="description"><?php esc_html_e('If set to “all”, this rule matches every category unless excluded.', 'bw'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Exclude Product Categories', 'bw'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" class="bw-tbl-enable-exclude" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][exclude_enabled]" value="1" <?php checked($exclude_enabled); ?> />
                            <?php esc_html_e('Enable exclusions (optional)', 'bw'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Optional. If a product matches an excluded category, this rule will not apply (exclusions override includes).', 'bw'); ?></p>
                        <div class="bw-tbl-exclude-fields">
                            <div class="bw-tbl-term-checklist-wrap">
                                <?php echo $exclude_checklist; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <p class="bw-tbl-rule-actions">
                <button type="button" class="button button-link-delete bw-tbl-remove-single-product-rule"><?php esc_html_e('Remove rule', 'bw'); ?></button>
            </p>
        </div>
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
        $single_product_rules_option = bw_tbl_get_single_product_rules_option();
        $single_product_choices = bw_tbl_get_single_product_template_choices();
        $parent_product_categories = bw_tbl_get_parent_product_category_choices();
        $single_product_enabled = !empty($single_product_rules_option['enabled']);
        $single_product_rules = isset($single_product_rules_option['rules']) && is_array($single_product_rules_option['rules']) ? $single_product_rules_option['rules'] : [];
        $single_product_rules_count = count($single_product_rules);
        $single_product_active_templates_count = count(bw_tbl_get_single_product_rules_template_ids($single_product_rules_option));
        $single_product_missing_active_template = $single_product_enabled && $single_product_active_templates_count <= 0;
        if (empty($single_product_rules)) {
            $single_product_rules = [
                [
                    'template_id' => 0,
                    'include_product_cat' => [],
                    'exclude_product_cat' => [],
                ],
            ];
        }

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
                    <a href="#bw-tbl-tab-single-product" class="nav-tab" data-bw-tbl-tab="single-product"><?php esc_html_e('Single Product (by Category)', 'bw'); ?></a>
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
                    <div class="notice <?php echo $single_product_missing_active_template ? 'notice-warning' : 'notice-info'; ?>" style="margin:0 0 12px 0;padding:10px 12px;">
                        <p style="margin:0;">
                            <strong><?php esc_html_e('Status:', 'bw'); ?></strong>
                            <?php echo $single_product_enabled ? esc_html__('Enabled', 'bw') : esc_html__('Disabled', 'bw'); ?>
                            <span style="margin:0 8px;color:#9aa0a6;">|</span>
                            <strong><?php esc_html_e('Rules:', 'bw'); ?></strong>
                            <?php echo esc_html((string) $single_product_rules_count); ?>
                            <span style="margin:0 8px;color:#9aa0a6;">|</span>
                            <strong><?php esc_html_e('Active Templates:', 'bw'); ?></strong>
                            <?php echo esc_html((string) $single_product_active_templates_count); ?>
                        </p>
                        <?php if ($single_product_missing_active_template) : ?>
                            <p style="margin:8px 0 0 0;">
                                <?php esc_html_e('Single Product override is enabled but no valid template is linked in rules.', 'bw'); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Single Product Override', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-single-product-conditions" type="checkbox" name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_RULES_OPTION); ?>[enabled]" value="1" <?php checked(!empty($single_product_rules_option['enabled'])); ?> />
                                    <?php esc_html_e('Resolve Single Product templates using product-category include/exclude rules.', 'bw'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('This affects WooCommerce single product pages only (not product category archive pages).', 'bw'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div id="bw-tbl-single-product-controls" style="margin-top:8px;">
                        <div id="bw-tbl-single-product-rules-list">
                            <?php foreach ($single_product_rules as $rule_index => $single_product_rule) : ?>
                                <?php bw_tbl_render_single_product_rule_row($rule_index, $single_product_rule, $single_product_choices, $parent_product_categories); ?>
                            <?php endforeach; ?>
                        </div>
                        <p class="bw-tbl-rules-toolbar">
                            <button type="button" class="button" id="bw-tbl-add-single-product-rule"><?php esc_html_e('+ Add Rule', 'bw'); ?></button>
                        </p>
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
        <script type="text/html" id="tmpl-bw-tbl-single-product-rule-row">
            <?php
            bw_tbl_render_single_product_rule_row(
                99999,
                [
                    'template_id' => 0,
                    'include_product_cat' => [],
                    'exclude_product_cat' => [],
                ],
                $single_product_choices,
                $parent_product_categories
            );
            ?>
        </script>
        <?php
    }
}
