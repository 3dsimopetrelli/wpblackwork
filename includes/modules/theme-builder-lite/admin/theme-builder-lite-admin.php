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

        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_SINGLE_PRODUCT_PREVIEW_PRODUCT_OPTION,
            [
                'type' => 'integer',
                'sanitize_callback' => 'bw_tbl_sanitize_single_product_preview_product_id',
                'default' => 0,
            ]
        );

        register_setting(
            'bw_tbl_settings_group',
            BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => 'bw_tbl_sanitize_product_archive_rules_option',
                'default' => bw_tbl_default_product_archive_rules_option(),
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

        wp_localize_script(
            'bw-theme-builder-lite-admin',
            'bwTblAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'previewProductNonce' => wp_create_nonce('bw_tbl_preview_product_search'),
                'i18n' => [
                    'searching' => __('Searching products...', 'bw'),
                    'noResults' => __('No products found.', 'bw'),
                    'requestFailed' => __('Search failed. Try again.', 'bw'),
                    'selectedPrefix' => __('Selected:', 'bw'),
                ],
            ]
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_tbl_admin_enqueue_assets');

if (!function_exists('bw_tbl_enqueue_editor_cleanup_assets')) {
    function bw_tbl_enqueue_editor_cleanup_assets()
    {
        $flags = function_exists('bw_tbl_get_feature_flags') ? bw_tbl_get_feature_flags() : [];
        if (empty($flags['hide_pro_upgrade_panels'])) {
            return;
        }

        $style_path = BW_MEW_PATH . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-editor-cleanup.css';
        wp_enqueue_style(
            'bw-tbl-elementor-editor-cleanup',
            BW_MEW_URL . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-editor-cleanup.css',
            [],
            file_exists($style_path) ? filemtime($style_path) : '1.0.0'
        );

        $script_path = BW_MEW_PATH . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-editor-cleanup.js';
        wp_enqueue_script(
            'bw-tbl-elementor-editor-cleanup',
            BW_MEW_URL . 'includes/modules/theme-builder-lite/admin/theme-builder-lite-editor-cleanup.js',
            ['jquery'],
            file_exists($script_path) ? filemtime($script_path) : '1.0.0',
            true
        );
    }
}
add_action('elementor/editor/after_enqueue_styles', 'bw_tbl_enqueue_editor_cleanup_assets', 20);
add_action('elementor/editor/after_enqueue_scripts', 'bw_tbl_enqueue_editor_cleanup_assets', 20);

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
        <div class="bw-form-row bw-tbl-font-row">
            <div class="bw-grid-2 bw-grid-2--gap bw-form-row__layout">
                <div class="bw-form-row__field bw-form-row__field-family">
                    <label class="bw-form-row__label"><?php esc_html_e('Font Family', 'bw'); ?></label>
                    <input type="text" class="regular-text" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][font_family]" value="<?php echo esc_attr($family); ?>" placeholder="Inter" />
                </div>

                <div class="bw-grid-50-50 bw-form-row__meta">
                    <div class="bw-form-row__field bw-form-row__field-compact">
                        <label class="bw-form-row__label"><?php esc_html_e('Weight', 'bw'); ?></label>
                        <input type="text" class="small-text" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][font_weight]" value="<?php echo esc_attr($weight); ?>" placeholder="400" />
                    </div>
                    <div class="bw-form-row__field bw-form-row__field-compact">
                        <label class="bw-form-row__label"><?php esc_html_e('Style', 'bw'); ?></label>
                        <select name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][font_style]">
                            <option value="normal" <?php selected($style, 'normal'); ?>><?php esc_html_e('normal', 'bw'); ?></option>
                            <option value="italic" <?php selected($style, 'italic'); ?>><?php esc_html_e('italic', 'bw'); ?></option>
                            <option value="oblique" <?php selected($style, 'oblique'); ?>><?php esc_html_e('oblique', 'bw'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="bw-form-row__source bw-form-row__source-woff2">
                    <label class="bw-form-row__label"><?php esc_html_e('WOFF2 Source', 'bw'); ?></label>
                    <div class="bw-form-row__source-control">
                        <input type="url" class="regular-text bw-tbl-font-source bw-tbl-font-source-woff2" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][sources][woff2]" value="<?php echo esc_url($woff2); ?>" placeholder="https://...font.woff2" />
                        <button type="button" class="button button-secondary bw-tbl-media-select" data-format="woff2"><?php esc_html_e('Select .woff2', 'bw'); ?></button>
                    </div>
                </div>
                <div class="bw-form-row__source bw-form-row__source-woff">
                    <label class="bw-form-row__label"><?php esc_html_e('WOFF Source', 'bw'); ?></label>
                    <div class="bw-form-row__source-control">
                        <input type="url" class="regular-text bw-tbl-font-source bw-tbl-font-source-woff" name="<?php echo esc_attr(BW_TBL_CUSTOM_FONTS_OPTION); ?>[fonts][<?php echo esc_attr((string) $index); ?>][sources][woff]" value="<?php echo esc_url($woff); ?>" placeholder="https://...font.woff" />
                        <button type="button" class="button button-secondary bw-tbl-media-select" data-format="woff"><?php esc_html_e('Select .woff', 'bw'); ?></button>
                    </div>
                </div>

                <div class="bw-form-row__actions">
                    <button type="button" class="button bw-button--danger bw-tbl-remove-font-row"><?php esc_html_e('Remove', 'bw'); ?></button>
                </div>
            </div>
        </div>
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
            <table class="form-table bw-admin-table bw-admin-form-grid bw-tbl-rule-table" role="presentation">
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

if (!function_exists('bw_tbl_render_product_archive_rule_row')) {
    function bw_tbl_render_product_archive_rule_row($index, $rule, $template_choices, $parent_product_categories)
    {
        $index = absint($index);
        $rule = is_array($rule) ? $rule : [];
        $template_id = isset($rule['template_id']) ? absint($rule['template_id']) : 0;
        $include = isset($rule['include_product_cat']) && is_array($rule['include_product_cat']) ? array_map('absint', $rule['include_product_cat']) : [];
        $include_mode = !empty($include) ? 'selected' : 'all';
        $exclude = isset($rule['exclude_product_cat']) && is_array($rule['exclude_product_cat']) ? array_map('absint', $rule['exclude_product_cat']) : [];
        $exclude_enabled = !empty($exclude);
        $include_input_name = BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION . '[rules][' . $index . '][include_product_cat][]';
        $exclude_input_name = BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION . '[rules][' . $index . '][exclude_product_cat][]';
        $include_checklist = bw_tbl_render_product_cat_checklist($include_input_name, $include, $parent_product_categories);
        $exclude_checklist = bw_tbl_render_product_cat_checklist($exclude_input_name, $exclude, $parent_product_categories);
        ?>
        <div class="bw-tbl-product-archive-rule" data-bw-tbl-rule-index="<?php echo esc_attr((string) $index); ?>">
            <p class="bw-tbl-rule-heading">
                <strong><?php esc_html_e('Rule', 'bw'); ?> #<span class="bw-tbl-rule-number"><?php echo esc_html((string) ($index + 1)); ?></span></strong>
            </p>
            <table class="form-table bw-admin-table bw-admin-form-grid bw-tbl-rule-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Active Product Archive Template', 'bw'); ?></label>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr(BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][template_id]">
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
                                <input type="radio" class="bw-tbl-include-mode-radio" name="<?php echo esc_attr(BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][include_mode]" value="all" <?php checked('all', $include_mode); ?> />
                                <?php esc_html_e('Apply to all categories', 'bw'); ?>
                            </label>
                            <br />
                            <label>
                                <input type="radio" class="bw-tbl-include-mode-radio" name="<?php echo esc_attr(BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][include_mode]" value="selected" <?php checked('selected', $include_mode); ?> />
                                <?php esc_html_e('Apply only to selected categories', 'bw'); ?>
                            </label>
                        </fieldset>
                        <div class="bw-tbl-include-fields">
                            <div class="bw-tbl-term-checklist-wrap">
                                <?php echo $include_checklist; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                        <p class="description"><?php esc_html_e('If set to “all”, this rule matches every product category archive unless excluded.', 'bw'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Exclude Product Categories', 'bw'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" class="bw-tbl-enable-exclude" name="<?php echo esc_attr(BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION); ?>[rules][<?php echo esc_attr((string) $index); ?>][exclude_enabled]" value="1" <?php checked($exclude_enabled); ?> />
                            <?php esc_html_e('Enable exclusions (optional)', 'bw'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Optional. If an archive matches an excluded category, this rule will not apply (exclusions override includes).', 'bw'); ?></p>
                        <div class="bw-tbl-exclude-fields">
                            <div class="bw-tbl-term-checklist-wrap">
                                <?php echo $exclude_checklist; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <p class="bw-tbl-rule-actions">
                <button type="button" class="button button-link-delete bw-tbl-remove-product-archive-rule"><?php esc_html_e('Remove rule', 'bw'); ?></button>
            </p>
        </div>
        <?php
    }
}

if (!function_exists('bw_tbl_ajax_search_preview_products')) {
    function bw_tbl_ajax_search_preview_products()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'bw')], 403);
        }

        check_ajax_referer('bw_tbl_preview_product_search', 'nonce');

        $search = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
        $search = trim($search);
        if ('' === $search) {
            wp_send_json_success(['items' => []]);
        }

        $query = new WP_Query(
            [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 20,
                's' => $search,
                'fields' => 'ids',
                'orderby' => 'title',
                'order' => 'ASC',
                'no_found_rows' => true,
            ]
        );

        $items = [];
        foreach ($query->posts as $product_id) {
            $product_id = absint($product_id);
            if ($product_id <= 0) {
                continue;
            }

            $title = get_the_title($product_id);
            if (!is_string($title) || '' === trim($title)) {
                $title = sprintf(__('Product #%d', 'bw'), $product_id);
            }

            $items[] = [
                'id' => $product_id,
                'text' => $title,
            ];
        }

        wp_send_json_success(['items' => $items]);
    }
}
add_action('wp_ajax_bw_tbl_search_preview_products', 'bw_tbl_ajax_search_preview_products');

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
        $preview_product_saved_id = bw_tbl_sanitize_single_product_preview_product_id(get_option(BW_TBL_SINGLE_PRODUCT_PREVIEW_PRODUCT_OPTION, 0));
        $preview_product_effective_id = bw_tbl_get_single_product_preview_product_id(true);
        $preview_product_saved_valid = $preview_product_saved_id > 0;
        $preview_product_effective_valid = $preview_product_effective_id > 0;
        $preview_product_title = '';
        if ($preview_product_effective_valid) {
            $preview_product_title = get_the_title($preview_product_effective_id);
            if (!is_string($preview_product_title) || '' === trim($preview_product_title)) {
                $preview_product_title = sprintf(__('Product #%d', 'bw'), $preview_product_effective_id);
            }
        }
        $product_archive_rules_option = bw_tbl_get_product_archive_rules_option();
        $product_archive_choices = bw_tbl_get_product_archive_template_choices();
        $parent_product_categories = bw_tbl_get_parent_product_category_choices();
        $single_product_enabled = !empty($single_product_rules_option['enabled']);
        $single_product_rules = isset($single_product_rules_option['rules']) && is_array($single_product_rules_option['rules']) ? $single_product_rules_option['rules'] : [];
        $single_product_rules_count = count($single_product_rules);
        $single_product_active_templates_count = count(bw_tbl_get_single_product_rules_template_ids($single_product_rules_option));
        $single_product_missing_active_template = $single_product_enabled && $single_product_active_templates_count <= 0;
        $single_product_preview_missing = $single_product_enabled && !$preview_product_effective_valid;
        $product_archive_enabled = !empty($product_archive_rules_option['enabled']);
        $product_archive_rules = isset($product_archive_rules_option['rules']) && is_array($product_archive_rules_option['rules']) ? $product_archive_rules_option['rules'] : [];
        $product_archive_rules_count = count($product_archive_rules);
        $product_archive_active_templates_count = count(bw_tbl_get_product_archive_rules_template_ids($product_archive_rules_option));
        $product_archive_missing_active_template = $product_archive_enabled && $product_archive_active_templates_count <= 0;
        if (empty($single_product_rules)) {
            $single_product_rules = [
                [
                    'template_id' => 0,
                    'include_product_cat' => [],
                    'exclude_product_cat' => [],
                ],
            ];
        }
        if (empty($product_archive_rules)) {
            $product_archive_rules = [
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
        <div class="wrap bw-admin-root bw-tbl-admin-wrap">
            <div class="bw-admin-header">
                <h1 class="bw-admin-title"><?php esc_html_e('Theme Builder Lite', 'bw'); ?></h1>
                <p class="bw-admin-subtitle"><?php esc_html_e('A lightweight Elementor Pro alternative, completely free.', 'bw'); ?></p>
            </div>
            <?php if (isset($_GET['settings-updated']) && '' !== sanitize_key(wp_unslash($_GET['settings-updated']))) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Theme Builder Lite settings saved.', 'bw'); ?></strong></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('bw_tbl_settings_group'); ?>
                <div class="bw-admin-action-bar">
                    <div class="bw-admin-action-meta">
                        <?php esc_html_e('Configure templates and lightweight theme builder settings.', 'bw'); ?>
                    </div>
                    <div class="bw-admin-action-buttons">
                        <?php submit_button(__('Save Settings', 'bw'), 'primary', 'submit', false); ?>
                    </div>
                </div>

                <section class="bw-admin-card">
                    <h2 class="bw-admin-card-title"><?php esc_html_e('Sections', 'bw'); ?></h2>
                    <p class="bw-admin-card-helper"><?php esc_html_e('A lightweight Elementor Pro alternative', 'bw'); ?></p>
                    <nav class="nav-tab-wrapper bw-admin-tabs" id="bw-tbl-tabs">
                        <a href="#bw-tbl-tab-settings" class="nav-tab nav-tab-active" data-bw-tbl-tab="settings"><?php esc_html_e('Settings', 'bw'); ?></a>
                        <a href="#bw-tbl-tab-fonts" class="nav-tab" data-bw-tbl-tab="fonts"><?php esc_html_e('Fonts', 'bw'); ?></a>
                        <a href="#bw-tbl-tab-footer" class="nav-tab" data-bw-tbl-tab="footer"><?php esc_html_e('Footer', 'bw'); ?></a>
                        <a href="#bw-tbl-tab-single-product" class="nav-tab" data-bw-tbl-tab="single-product"><?php esc_html_e('Single Product', 'bw'); ?></a>
                        <a href="#bw-tbl-tab-product-archive" class="nav-tab" data-bw-tbl-tab="product-archive"><?php esc_html_e('Product Archive', 'bw'); ?></a>
                        <a href="#bw-tbl-tab-import-template" class="nav-tab" data-bw-tbl-tab="import-template"><?php esc_html_e('Import Template', 'bw'); ?></a>
                    </nav>
                </section>

                <div id="bw-tbl-tab-settings" class="bw-tbl-tab-panel is-active" data-bw-tbl-panel="settings">
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Core Settings', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Control master flags for Theme Builder Lite runtime and resolver.', 'bw'); ?></p>
                        <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
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
                        <tr>
                            <th scope="row"><?php esc_html_e('Hide Pro upgrade panels', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-hide-pro-upgrade-panels" type="checkbox" name="<?php echo esc_attr(BW_TBL_FEATURE_FLAGS_OPTION); ?>[hide_pro_upgrade_panels]" value="1" <?php checked(!empty($flags['hide_pro_upgrade_panels'])); ?> />
                                    <?php esc_html_e('Hide Elementor editor sidebar sections that display Upgrade prompts.', 'bw'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Elementor Bug Workarounds', 'bw'); ?></th>
                            <td>
                                <label style="display:block;margin-bottom:6px;">
                                    <input id="bw-tbl-elementor-disable-breakpoints" type="checkbox" name="<?php echo esc_attr(BW_TBL_FOOTER_OPTION); ?>[elementor_disable_child_breakpoints]" value="1" <?php checked(!empty($footer_option['elementor_disable_child_breakpoints'])); ?> />
                                    <?php esc_html_e('Disable theme breakpoints in frontend and Elementor editor/preview', 'bw'); ?>
                                </label>
                                <label style="display:block;">
                                    <input id="bw-tbl-elementor-disable-child-css" type="checkbox" name="<?php echo esc_attr(BW_TBL_FOOTER_OPTION); ?>[elementor_disable_child_css]" value="1" <?php checked(!empty($footer_option['elementor_disable_child_css'])); ?> />
                                    <?php esc_html_e('Disable all theme CSS in frontend and Elementor editor/preview', 'bw'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Applies to frontend runtime and Elementor editor/preview. Use with caution.', 'bw'); ?></p>
                            </td>
                        </tr>
                    </table>
                    </section>
                </div>

                <div id="bw-tbl-tab-fonts" class="bw-tbl-tab-panel" data-bw-tbl-panel="fonts" style="display:none;">
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Custom Fonts', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Manage font activation and register media-hosted WOFF/WOFF2 sources.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
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
                        <div id="bw-tbl-fonts-list">
                            <?php foreach ($fonts as $index => $font) : ?>
                                <?php bw_tbl_render_font_row($index, $font); ?>
                            <?php endforeach; ?>
                        </div>
                        <p>
                            <button type="button" class="button button-secondary" id="bw-tbl-add-font-row"><?php esc_html_e('Add Font', 'bw'); ?></button>
                        </p>
                    </div>
                    </section>
                </div>

                <div id="bw-tbl-tab-footer" class="bw-tbl-tab-panel" data-bw-tbl-panel="footer" style="display:none;">
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Footer', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Enable footer override and choose the active footer template.', 'bw'); ?></p>
                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
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
                        <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
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
                            <tr>
                                <th scope="row"><?php esc_html_e('Exclude Footer On Pages', 'bw'); ?></th>
                                <td>
                                    <label>
                                        <input id="bw-tbl-footer-enable-exclusions" type="checkbox" name="<?php echo esc_attr(BW_TBL_FOOTER_OPTION); ?>[exclude_enabled]" value="1" <?php checked(!empty($footer_option['exclude_enabled'])); ?> />
                                        <?php esc_html_e('Enable exclusions (optional)', 'bw'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Optional. When enabled, BW footer override is skipped on selected WooCommerce pages.', 'bw'); ?></p>

                                    <div id="bw-tbl-footer-exclusion-controls" style="margin-top:8px;">
                                        <label style="display:block;margin-bottom:6px;">
                                            <input type="checkbox" name="<?php echo esc_attr(BW_TBL_FOOTER_OPTION); ?>[exclude_checkout]" value="1" <?php checked(!empty($footer_option['exclude_checkout'])); ?> />
                                            <?php esc_html_e('Checkout page', 'bw'); ?>
                                        </label>
                                        <label style="display:block;">
                                            <input type="checkbox" name="<?php echo esc_attr(BW_TBL_FOOTER_OPTION); ?>[exclude_order_received]" value="1" <?php checked(!empty($footer_option['exclude_order_received'])); ?> />
                                            <?php esc_html_e('Order received / thank-you pages', 'bw'); ?>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    </section>
                </div>

                <div id="bw-tbl-tab-single-product" class="bw-tbl-tab-panel" data-bw-tbl-panel="single-product" style="display:none;">
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Single Product Rules', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Define category-based template rules and editor preview context for single product pages.', 'bw'); ?></p>
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

                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
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
                        <tr>
                            <th scope="row"><?php esc_html_e('Preview Product (Editor Only)', 'bw'); ?></th>
                            <td>
                                <input
                                    type="hidden"
                                    id="bw-tbl-single-product-preview-product-id"
                                    name="<?php echo esc_attr(BW_TBL_SINGLE_PRODUCT_PREVIEW_PRODUCT_OPTION); ?>"
                                    value="<?php echo esc_attr((string) $preview_product_saved_id); ?>"
                                />
                                <input
                                    type="search"
                                    id="bw-tbl-single-product-preview-product-search"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('Search products by title...', 'bw'); ?>"
                                    autocomplete="off"
                                />
                                <button type="button" class="button" id="bw-tbl-single-product-preview-product-clear"><?php esc_html_e('Clear', 'bw'); ?></button>
                                <div id="bw-tbl-single-product-preview-product-results" class="bw-tbl-ajax-search-results" style="display:none;"></div>
                                <p class="description" id="bw-tbl-single-product-preview-product-selected-label">
                                    <strong><?php esc_html_e('Selected:', 'bw'); ?></strong>
                                    <span
                                        id="bw-tbl-single-product-preview-product-selected-text"
                                        data-selected-id="<?php echo esc_attr((string) $preview_product_effective_id); ?>"
                                        data-selected-title="<?php echo esc_attr((string) $preview_product_title); ?>"
                                    >
                                        <?php if ($preview_product_effective_valid) : ?>
                                            <?php echo esc_html($preview_product_title); ?> (ID <?php echo esc_html((string) $preview_product_effective_id); ?>)
                                        <?php else : ?>
                                            <?php esc_html_e('None selected', 'bw'); ?>
                                        <?php endif; ?>
                                    </span>
                                </p>
                                <p class="description"><?php esc_html_e('Used only for Elementor editor preview. Does not affect frontend.', 'bw'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php if ($single_product_preview_missing) : ?>
                        <div class="notice notice-warning" style="margin:0 0 12px 0;padding:10px 12px;">
                            <p style="margin:0;"><?php esc_html_e('Preview product missing/invalid. Elementor preview will not inject product context until a valid published product is selected.', 'bw'); ?></p>
                        </div>
                    <?php elseif (!$preview_product_saved_valid && $preview_product_effective_valid) : ?>
                        <div class="notice notice-info" style="margin:0 0 12px 0;padding:10px 12px;">
                            <p style="margin:0;">
                                <?php
                                printf(
                                    /* translators: %d product id */
                                    esc_html__('No preview product explicitly selected. Using fallback product ID %d for editor context.', 'bw'),
                                    (int) $preview_product_effective_id
                                );
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>

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
                    </section>
                </div>

                <div id="bw-tbl-tab-product-archive" class="bw-tbl-tab-panel" data-bw-tbl-panel="product-archive" style="display:none;">
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Product Archive Rules', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Configure category-based resolver rules for WooCommerce product archive templates.', 'bw'); ?></p>
                    <div class="notice <?php echo $product_archive_missing_active_template ? 'notice-warning' : 'notice-info'; ?>" style="margin:0 0 12px 0;padding:10px 12px;">
                        <p style="margin:0;">
                            <strong><?php esc_html_e('Status:', 'bw'); ?></strong>
                            <?php echo $product_archive_enabled ? esc_html__('Enabled', 'bw') : esc_html__('Disabled', 'bw'); ?>
                            <span style="margin:0 8px;color:#9aa0a6;">|</span>
                            <strong><?php esc_html_e('Rules:', 'bw'); ?></strong>
                            <?php echo esc_html((string) $product_archive_rules_count); ?>
                            <span style="margin:0 8px;color:#9aa0a6;">|</span>
                            <strong><?php esc_html_e('Active Templates:', 'bw'); ?></strong>
                            <?php echo esc_html((string) $product_archive_active_templates_count); ?>
                        </p>
                        <?php if ($product_archive_missing_active_template) : ?>
                            <p style="margin:8px 0 0 0;">
                                <?php esc_html_e('Product Archive override is enabled but no valid template is linked in rules.', 'bw'); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Product Archive Override', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input id="bw-tbl-flag-product-archive-conditions" type="checkbox" name="<?php echo esc_attr(BW_TBL_PRODUCT_ARCHIVE_RULES_OPTION); ?>[enabled]" value="1" <?php checked(!empty($product_archive_rules_option['enabled'])); ?> />
                                    <?php esc_html_e('Resolve Product Archive templates using product-category include/exclude rules.', 'bw'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('This affects WooCommerce product category archive pages only.', 'bw'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div id="bw-tbl-product-archive-controls" style="margin-top:8px;">
                        <div id="bw-tbl-product-archive-rules-list">
                            <?php foreach ($product_archive_rules as $rule_index => $product_archive_rule) : ?>
                                <?php bw_tbl_render_product_archive_rule_row($rule_index, $product_archive_rule, $product_archive_choices, $parent_product_categories); ?>
                            <?php endforeach; ?>
                        </div>
                        <p class="bw-tbl-rules-toolbar">
                            <button type="button" class="button" id="bw-tbl-add-product-archive-rule"><?php esc_html_e('+ Add Rule', 'bw'); ?></button>
                        </p>
                    </div>
                    </section>
                </div>

            </form>

                <div id="bw-tbl-tab-import-template" class="bw-tbl-tab-panel" data-bw-tbl-panel="import-template" style="display:none;">
                    <section class="bw-admin-card">
                        <h2 class="bw-admin-card-title"><?php esc_html_e('Import Template', 'bw'); ?></h2>
                        <p class="bw-admin-card-helper"><?php esc_html_e('Import Elementor JSON and map it into BW Template drafts safely.', 'bw'); ?></p>
                <?php
                if (function_exists('bw_tbl_render_import_template_tab')) {
                    bw_tbl_render_import_template_tab();
                } else {
                    echo '<div class="notice notice-error" style="margin:0 0 12px 0;padding:10px 12px;"><p style="margin:0;">' . esc_html__('Import module is unavailable.', 'bw') . '</p></div>';
                }
                ?>
                    </section>
            </div>
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
        <script type="text/html" id="tmpl-bw-tbl-product-archive-rule-row">
            <?php
            bw_tbl_render_product_archive_rule_row(
                99999,
                [
                    'template_id' => 0,
                    'include_product_cat' => [],
                    'exclude_product_cat' => [],
                ],
                $product_archive_choices,
                $parent_product_categories
            );
            ?>
        </script>
        <?php
    }
}
