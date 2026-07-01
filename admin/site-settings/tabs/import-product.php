<?php
/**
 * Blackwork Site Settings — Import Product tab renderer.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bw_site_render_import_product_tab()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        return;
    }

    $notices = [];
    $digital_csv_builder = [
        'complete_json_input' => '',
        'errors' => [],
        'warnings' => [],
        'success' => [],
        'csv_payload' => '',
        'filename' => '',
    ];
    $state = bw_import_get_state();
    $active_mode = bw_product_transfer_get_active_mode();
    $selected_export_category_id = bw_export_get_selected_category_id();
    $selected_export_product_value = bw_export_get_selected_product_value();
    $selected_export_profile = bw_export_get_profile();
    $page_slug = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'blackwork-product-import-export';
    if (!in_array($page_slug, ['blackwork-product-import-export', 'blackwork-site-settings'], true)) {
        $page_slug = 'blackwork-product-import-export';
    }

    $base_import_export_url = $page_slug === 'blackwork-site-settings'
        ? add_query_arg(
            [
                'page' => 'blackwork-site-settings',
                'tab' => 'import-product',
            ],
            admin_url('admin.php')
        )
        : bw_product_import_export_admin_url();

    if (isset($GLOBALS['bw_export_request_result'])) {
        $export_result = $GLOBALS['bw_export_request_result'];
        if (is_wp_error($export_result)) {
            $notices[] = ['type' => 'error', 'message' => $export_result->get_error_message()];
        }
    }

    if (isset($_POST['bw_digital_csv_generate_submit'])) {
        if (!bw_digital_csv_builder_user_can_manage()) {
            $notices[] = ['type' => 'error', 'message' => __('You do not have permission to generate Digital CSV files.', 'bw')];
        } elseif (!isset($_POST['bw_digital_csv_generate_nonce']) || !wp_verify_nonce(wp_unslash($_POST['bw_digital_csv_generate_nonce']), 'bw_digital_csv_generate')) {
            $notices[] = ['type' => 'error', 'message' => __('Invalid Digital CSV generate nonce. Please try again.', 'bw')];
        } else {
            $digital_csv_builder['complete_json_input'] = bw_digital_csv_builder_read_complete_json_input();
            $builder_result = bw_digital_csv_builder_generate($digital_csv_builder['complete_json_input']);

            if (is_wp_error($builder_result)) {
                $digital_csv_builder['errors'][] = $builder_result->get_error_message();
            } else {
                $digital_csv_builder['errors'] = isset($builder_result['errors']) ? (array) $builder_result['errors'] : [];
                $digital_csv_builder['warnings'] = isset($builder_result['warnings']) ? (array) $builder_result['warnings'] : [];
                $digital_csv_builder['success'] = isset($builder_result['success']) ? (array) $builder_result['success'] : [];
                $digital_csv_builder['csv_payload'] = !empty($builder_result['ok']) && !empty($builder_result['csv']) ? base64_encode((string) $builder_result['csv']) : '';
                $digital_csv_builder['filename'] = isset($builder_result['filename']) ? (string) $builder_result['filename'] : '';
            }
        }
    }

    $import_notice = isset($_GET['bw_import_notice']) ? sanitize_key(wp_unslash($_GET['bw_import_notice'])) : '';
    if ($import_notice === 'cleared') {
        $notices[] = ['type' => 'success', 'message' => __('Import mapping cleared. You can upload a new CSV.', 'bw')];
    } elseif ($import_notice === 'lock_cleared') {
        $notices[] = ['type' => 'success', 'message' => __('Stuck import lock cleared. You can retry the import.', 'bw')];
    }

    $active_lock = bw_import_get_active_lock();
    if (!empty($active_lock) && !empty($active_lock['is_stale'])) {
        bw_import_clear_lock(true);
        $active_lock = [];
        $notices[] = ['type' => 'success', 'message' => __('A stale import lock was cleared automatically. You can retry the import.', 'bw')];
    }

    $inline_upload_error = '';

    if (isset($_POST['bw_import_clear_state_submit'])) {
        $clear_result = bw_import_handle_clear_request($state);
        if (is_wp_error($clear_result)) {
            $notices[] = ['type' => 'error', 'message' => $clear_result->get_error_message()];
        } else {
            wp_safe_redirect(add_query_arg(['product_flow' => 'import', 'bw_import_notice' => 'cleared'], $base_import_export_url));
            exit;
        }
    }

    if (isset($_POST['bw_import_clear_lock_submit'])) {
        $clear_lock_result = bw_import_handle_clear_lock_request();
        if (is_wp_error($clear_lock_result)) {
            $notices[] = ['type' => 'error', 'message' => $clear_lock_result->get_error_message()];
        } else {
            wp_safe_redirect(add_query_arg(['product_flow' => 'import', 'bw_import_notice' => 'lock_cleared'], $base_import_export_url));
            exit;
        }
    }

    $is_upload_request = isset($_POST['bw_import_upload_submit'])
        || (
            isset($_POST['bw_import_action'])
            && 'upload' === sanitize_key(wp_unslash($_POST['bw_import_action']))
        );

    if ($is_upload_request) {
        $upload_result = bw_import_handle_upload_request();
        if (is_wp_error($upload_result)) {
            $inline_upload_error = $upload_result->get_error_message();
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
            <a href="<?php echo esc_url(add_query_arg('product_flow', 'export', $base_import_export_url)); ?>"
                class="nav-tab <?php echo $active_mode === 'export' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Export Product', 'bw'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('product_flow', 'import', $base_import_export_url)); ?>"
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
                <input type="hidden" name="page" value="<?php echo esc_attr($page_slug); ?>" />
                <?php if ($page_slug === 'blackwork-site-settings') : ?>
                    <input type="hidden" name="tab" value="import-product" />
                <?php endif; ?>
                <input type="hidden" name="product_flow" value="export" />
                <input type="hidden" name="bw_export_profile" value="<?php echo esc_attr($selected_export_profile); ?>" />
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
                            <th scope="row"><label for="bw_export_profile"><?php esc_html_e('Export profile', 'bw'); ?></label></th>
                            <td>
                                <select id="bw_export_profile" name="bw_export_profile" style="min-width: 320px;">
                                    <option value="master_csv" <?php selected($selected_export_profile, 'master_csv'); ?>><?php esc_html_e('Master CSV (Blackwork full)', 'bw'); ?></option>
                                    <option value="woo_native_csv" <?php selected($selected_export_profile, 'woo_native_csv'); ?>><?php esc_html_e('Woo Native CSV (clean)', 'bw'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Master CSV includes dynamic meta and JSON fields. Woo Native CSV focuses on WooCommerce importer-friendly columns.', 'bw'); ?></p>
                            </td>
                        </tr>
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
        <section class="bw-admin-card bw-import-panel">
            <h3 class="bw-admin-card-title"><?php esc_html_e('Import Product', 'bw'); ?></h3>
            <p class="bw-admin-card-helper"><?php esc_html_e('Upload a CSV file to import or update WooCommerce products and custom meta fields.', 'bw'); ?></p>

        <?php if (!empty($active_lock)) : ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong><?php esc_html_e('Active import lock detected.', 'bw'); ?></strong>
                    <?php
                    echo ' ' . esc_html(
                        sprintf(
                            /* translators: 1: owner user id, 2: run id */
                            __('Owner user ID: %1$d. Run: %2$s.', 'bw'),
                            isset($active_lock['owner_user_id']) ? (int) $active_lock['owner_user_id'] : 0,
                            !empty($active_lock['run_id']) ? (string) $active_lock['run_id'] : 'n/a'
                        )
                    );
                    ?>
                </p>
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: 1: age in seconds, 2: ttl in seconds */
                            __('Current lock age: %1$d seconds. Lock TTL: %2$d seconds.', 'bw'),
                            isset($active_lock['age_seconds']) ? (int) $active_lock['age_seconds'] : 0,
                            bw_import_lock_ttl()
                        )
                    );
                    ?>
                </p>
                <p><?php esc_html_e('If the previous import request was interrupted, you can clear only the import lock and active run state here. No WooCommerce products will be deleted.', 'bw'); ?></p>
                <form method="post" style="margin-top:12px;">
                    <input type="hidden" name="product_flow" value="import" />
                    <?php wp_nonce_field('bw_import_clear_lock', 'bw_import_clear_lock_nonce'); ?>
                    <?php submit_button(__('Clear stuck import lock', 'bw'), 'secondary', 'bw_import_clear_lock_submit', false); ?>
                </form>
            </div>
        <?php endif; ?>

        <?php $import_templates = bw_product_import_template_registry(); ?>
        <?php
        $template_groups = [];
        foreach ($import_templates as $template_key => $template) {
            $group_label = isset($template['group']) ? (string) $template['group'] : '';
            if ($group_label === '') {
                continue;
            }
            if (!isset($template_groups[$group_label])) {
                $template_groups[$group_label] = [];
            }
            $template_groups[$group_label][$template_key] = $template;
        }
        ?>
        <div class="bw-import-templates-panel">
            <h3 class="bw-import-templates-panel__title"><?php esc_html_e('Product import templates', 'bw'); ?></h3>
            <p class="description bw-import-templates-panel__helper">
                <?php esc_html_e('Download the CSV template and Markdown guide, then copy the start prompt. Give all three resources to ChatGPT/Codex together with product images or source notes.', 'bw'); ?>
            </p>
            <p class="screen-reader-text" id="bw-import-template-copy-status" aria-live="polite"></p>
            <div class="bw-import-template-grid">
                <?php foreach ($template_groups as $group_label => $group_templates): ?>
                    <div class="bw-import-template-card">
                        <div class="bw-import-template-card__copy">
                            <strong class="bw-import-template-card__title"><?php echo esc_html($group_label); ?></strong>
                            <p class="bw-import-template-card__text">
                                <?php
                                if ($group_label === __('Digital Product', 'bw')) {
                                    esc_html_e('Variable digital product template with companion AI guide.', 'bw');
                                } elseif ($group_label === __('Books', 'bw')) {
                                    esc_html_e('Book import template with bibliographic guidance.', 'bw');
                                } else {
                                    esc_html_e('Print import template with technique and condition guidance.', 'bw');
                                }
                                ?>
                            </p>
                        </div>
                        <div class="bw-import-template-card__actions">
                            <?php
                            $prompt_template_key = '';
                            foreach ($group_templates as $template_key => $template):
                                if (substr((string) $template_key, -7) === '_prompt') {
                                    $prompt_template_key = (string) $template_key;
                                    continue;
                                }
                                $download_url = bw_product_import_template_download_url($template_key);
                                if ($download_url !== ''):
                                ?>
                                    <a class="button button-secondary" href="<?php echo esc_url($download_url); ?>">
                                        <?php echo esc_html($template['label']); ?>
                                    </a>
                                <?php
                                endif;
                            endforeach;

                            if ($prompt_template_key !== ''):
                                $aria_label = sprintf(
                                    /* translators: %s: product template group label */
                                    __('Copy %s start prompt', 'bw'),
                                    $group_label
                                );
                                ?>
                                <button
                                    type="button"
                                    class="button button-secondary bw-import-template-card__copy-prompt"
                                    data-prompt-key="<?php echo esc_attr($prompt_template_key); ?>"
                                    data-default-label="<?php echo esc_attr__('Copy Prompt', 'bw'); ?>"
                                    aria-label="<?php echo esc_attr($aria_label); ?>"
                                >
                                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                    <span><?php esc_html_e('Copy Prompt', 'bw'); ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bw-admin-card" style="margin-top:24px;">
            <h3 class="bw-admin-card-title"><?php esc_html_e('Create Digital CSV', 'bw'); ?></h3>
            <p class="bw-admin-card-helper"><?php esc_html_e('Paste or upload the Complete Product JSON generated by ChatGPT. The plugin will build and validate the Digital CSV before download.', 'bw'); ?></p>

            <form method="post" enctype="multipart/form-data" style="max-width:960px;">
                <input type="hidden" name="product_flow" value="import" />
                <?php wp_nonce_field('bw_digital_csv_generate', 'bw_digital_csv_generate_nonce'); ?>
                <table class="form-table bw-admin-table bw-admin-form-grid" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="bw_digital_csv_complete_json"><?php esc_html_e('Complete Product JSON', 'bw'); ?></label></th>
                            <td>
                                <textarea id="bw_digital_csv_complete_json" name="bw_digital_csv_complete_json" rows="20" class="large-text code" placeholder="<?php echo esc_attr("{\n  \"source_data\": {\n    \"product_title\": \"\",\n    \"digital_assets_list\": \"\",\n    \"featured_image_url\": \"\",\n    \"product_gallery\": [],\n    \"hover_image_url\": \"\",\n    \"showcase_image_urls\": [],\n    \"hover_video_url\": \"\",\n    \"year\": \"\",\n    \"author_artist\": \"\",\n    \"total_assets\": \"\",\n    \"formats\": \"\",\n    \"source_notes\": \"\",\n    \"product_context\": \"\",\n    \"technique\": \"\",\n    \"price\": \"\",\n    \"price_commercial\": \"\",\n    \"price_extended\": \"\",\n    \"download_zip_url\": \"\",\n    \"download_zip_url_commercial\": \"\",\n    \"download_zip_url_extended\": \"\"\n  },\n  \"creative_data\": {\n    \"post_title\": \"\",\n    \"post_content\": \"\",\n    \"post_excerpt\": \"\",\n    \"tags\": [],\n    \"product_subcategories\": [],\n    \"visual_analysis_notes\": \"\"\n  }\n}"); ?>"><?php echo esc_textarea($digital_csv_builder['complete_json_input']); ?></textarea>
                                <p class="description"><?php esc_html_e('Provide the full Complete Product JSON only. The plugin will convert Dropbox URLs, generate SKU and variation rows, map media and ZIP fields, and build the final CSV.', 'bw'); ?></p>
                                <input type="file" name="bw_digital_csv_complete_json_file" accept=".json,application/json,text/plain" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(__('Generate Digital CSV', 'bw'), 'secondary', 'bw_digital_csv_generate_submit', false); ?>
            </form>

            <div style="margin-top:18px;">
                <h4 style="margin:0 0 10px;"><?php esc_html_e('Validation report', 'bw'); ?></h4>

                <?php if (!empty($digital_csv_builder['errors'])) : ?>
                    <div class="notice notice-error inline">
                        <p><strong><?php esc_html_e('Blocking errors', 'bw'); ?></strong></p>
                        <ul style="margin:0 0 0 18px; list-style:disc;">
                            <?php foreach ($digital_csv_builder['errors'] as $builder_error) : ?>
                                <li><?php echo esc_html($builder_error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($digital_csv_builder['warnings'])) : ?>
                    <div class="notice notice-warning inline">
                        <p><strong><?php esc_html_e('Warnings', 'bw'); ?></strong></p>
                        <ul style="margin:0 0 0 18px; list-style:disc;">
                            <?php foreach ($digital_csv_builder['warnings'] as $builder_warning) : ?>
                                <li><?php echo esc_html($builder_warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($digital_csv_builder['success'])) : ?>
                    <div class="notice notice-success inline">
                        <p><strong><?php esc_html_e('Success summary', 'bw'); ?></strong></p>
                        <ul style="margin:0 0 0 18px; list-style:disc;">
                            <?php foreach ($digital_csv_builder['success'] as $builder_success) : ?>
                                <li><?php echo esc_html($builder_success); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($digital_csv_builder['csv_payload'] !== '' && empty($digital_csv_builder['errors'])) : ?>
                    <form method="post" style="margin-top:12px;">
                        <input type="hidden" name="product_flow" value="import" />
                        <?php wp_nonce_field('bw_digital_csv_download', 'bw_digital_csv_download_nonce'); ?>
                        <input type="hidden" name="bw_digital_csv_payload" value="<?php echo esc_attr($digital_csv_builder['csv_payload']); ?>" />
                        <input type="hidden" name="bw_digital_csv_filename" value="<?php echo esc_attr($digital_csv_builder['filename']); ?>" />
                        <?php submit_button(__('Download Completed Digital CSV', 'bw'), 'primary', 'bw_digital_csv_download_submit', false); ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="bw-import-upload-shell">
        <h3 class="bw-import-upload-shell__title"><?php esc_html_e('1. Upload CSV', 'bw'); ?></h3>
        <p class="bw-import-upload-shell__helper"><?php esc_html_e('Choose a CSV file to analyze before mapping columns and running the import.', 'bw'); ?></p>
        <form method="post" enctype="multipart/form-data" id="bw-import-upload-form" class="bw-import-upload-form">
            <input type="hidden" name="product_flow" value="import" />
            <input type="hidden" name="bw_import_action" value="" id="bw_import_action" />
            <?php wp_nonce_field('bw_import_upload', 'bw_import_upload_nonce'); ?>
            <div class="bw-import-upload-dropzone">
                <label for="bw_import_csv" class="bw-import-upload-dropzone__label"><?php esc_html_e('CSV file', 'bw'); ?></label>
                <input type="file" name="bw_import_csv" id="bw_import_csv" class="bw-import-upload-dropzone__input" accept=".csv,.txt,text/csv" />
                <p class="bw-import-upload-dropzone__hint"><?php esc_html_e('Select a clean CSV file. The selected filename will appear in the browser file input next to the button.', 'bw'); ?></p>
                <p id="bw-import-upload-selected-file" class="bw-import-upload-dropzone__selected-file" hidden></p>
                <p id="bw-import-upload-status" class="bw-import-upload-dropzone__status" aria-live="polite"></p>
                <?php if ($inline_upload_error !== '') : ?>
                    <div class="notice notice-error inline bw-import-upload-inline-error">
                        <p><?php echo esc_html($inline_upload_error); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bw-import-update-box">
                <strong class="bw-import-update-box__title"><?php esc_html_e('Update existing products', 'bw'); ?></strong>
                <label class="bw-import-update-box__label">
                    <input type="checkbox" name="bw_import_update_existing" value="1" class="bw-import-update-box__checkbox" <?php checked(!empty($state['update_existing'])); ?> />
                    <span><?php esc_html_e('When enabled, products matching an existing ID or SKU will be updated. Products that do not exist will be skipped.', 'bw'); ?></span>
                </label>
            </div>
            <?php submit_button(__('Upload & Analyze', 'bw'), 'primary', 'bw_import_upload_submit', false, ['id' => 'bw_import_upload_submit', 'data-default-label' => __('Upload & Analyze', 'bw')]); ?>
        </form>
        </div>
        <div id="bw-import-preflight-modal" style="display:none; position:fixed; inset:0; z-index:100000; background:rgba(0,0,0,.45); align-items:center; justify-content:center; padding:24px;">
            <div role="dialog" aria-modal="true" aria-labelledby="bw-import-preflight-title" style="width:min(680px, 100%); background:#fff; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden;">
                <div style="padding:24px 24px 18px; border-bottom:1px solid #e7e7e7;">
                    <h3 id="bw-import-preflight-title" style="margin:0 0 8px; font-size:24px; line-height:1.2;"><?php esc_html_e('CSV preflight check', 'bw'); ?></h3>
                    <p id="bw-import-preflight-intro" style="margin:0; color:#5b6470;"><?php esc_html_e('We checked the CSV in your browser before upload. Review the summary below. The file has not been uploaded yet. Click Continue import to upload the CSV and open the mapping step.', 'bw'); ?></p>
                </div>
                <div style="padding:20px 24px; display:grid; gap:16px;">
                    <div id="bw-import-preflight-stats" style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px;"></div>
                    <div id="bw-import-preflight-warning" class="notice notice-warning inline" style="display:none; margin:0;"></div>
                    <div id="bw-import-preflight-error" class="notice notice-error inline" style="display:none; margin:0;"></div>
                    <div id="bw-import-preflight-invalid" style="display:none;">
                        <strong style="display:block; margin-bottom:8px;"><?php esc_html_e('Example skipped rows', 'bw'); ?></strong>
                        <ul id="bw-import-preflight-invalid-list" style="margin:0; padding-left:18px; max-height:180px; overflow:auto;"></ul>
                    </div>
                </div>
                <div style="padding:18px 24px 24px; display:flex; gap:12px; justify-content:flex-end; border-top:1px solid #e7e7e7;">
                    <button type="button" class="button" id="bw-import-preflight-close"><?php esc_html_e('Close', 'bw'); ?></button>
                    <button type="button" class="button button-primary" id="bw-import-preflight-continue"><?php esc_html_e('Continue to mapping', 'bw'); ?></button>
                </div>
            </div>
        </div>

        <?php if (!empty($state['upload_summary']) && empty($state['headers'])): ?>
            <div class="bw-import-upload-summary">
                <h3 class="bw-import-upload-summary__title"><?php esc_html_e('Upload summary', 'bw'); ?></h3>
                <table class="widefat fixed bw-import-upload-summary__table">
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
                                    <ul class="bw-import-upload-summary__list">
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
                                    <ul class="bw-import-upload-summary__list bw-import-upload-summary__list--tight">
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
                                    <ul class="bw-import-upload-summary__list bw-import-upload-summary__list--tight">
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
            </div>
        <?php endif; ?>

        <?php if (!empty($state['headers'])): ?>
            <?php $mapping_auto_open = empty($state['in_progress']) ? 'true' : 'false'; ?>
            <div id="bw-import-mapping-reopen" class="bw-import-mapping-reopen" hidden>
                <div class="notice notice-info inline bw-import-mapping-reopen__notice">
                    <p>
                        <strong><?php esc_html_e('Mapping is ready', 'bw'); ?></strong>
                        <?php esc_html_e('Open the mapping step to review the detected column mapping and continue with Step 3.', 'bw'); ?>
                    </p>
                    <p class="bw-import-mapping-reopen__helper">
                        <?php esc_html_e('Clear the uploaded CSV mapping state and start over if you want to abandon this import setup.', 'bw'); ?>
                    </p>
                    <div class="bw-import-mapping-reopen__actions">
                        <button type="button" class="button button-secondary" id="bw-import-mapping-open">
                            <?php esc_html_e('Open mapping step', 'bw'); ?>
                        </button>
                        <form method="post" class="bw-import-mapping-reopen__clear-form" id="bw-import-mapping-clear-form">
                            <input type="hidden" name="product_flow" value="import" />
                            <?php wp_nonce_field('bw_import_clear_state', 'bw_import_clear_state_nonce'); ?>
                            <button
                                type="submit"
                                class="button button-link-delete bw-import-mapping-reopen__clear"
                                id="bw-import-mapping-clear"
                                name="bw_import_clear_state_submit"
                                value="1"
                            >
                                <?php esc_html_e('Abort import', 'bw'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div
                id="bw-import-mapping-modal"
                class="bw-import-mapping-modal"
                data-open-on-load="<?php echo esc_attr($mapping_auto_open); ?>"
            >
                <div class="bw-import-mapping-modal__backdrop" aria-hidden="true"></div>
                <div
                    class="bw-import-mapping-modal__dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="bw-import-mapping-title"
                    aria-describedby="bw-import-mapping-helper"
                >
                    <div id="bw-import-mapping-section" class="bw-import-mapping-section" tabindex="-1">
                        <div class="bw-import-mapping-modal__header">
                            <div class="bw-import-mapping-modal__header-copy">
                                <h3 id="bw-import-mapping-title" class="bw-import-mapping-modal__title" tabindex="-1"><?php esc_html_e('Step 2 — Map CSV columns', 'bw'); ?></h3>
                                <p id="bw-import-mapping-helper" class="bw-import-mapping-modal__helper"><?php esc_html_e('CSV uploaded and analyzed. Review the detected column mapping, then run the import.', 'bw'); ?></p>
                            </div>
                            <button
                                type="button"
                                class="button-link bw-import-mapping-modal__close"
                                id="bw-import-mapping-close"
                                aria-label="<?php esc_attr_e('Close mapping step', 'bw'); ?>"
                            >
                                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                            </button>
                        </div>

                        <form method="post">
                            <input type="hidden" name="product_flow" value="import" />
                            <?php wp_nonce_field('bw_import_run', 'bw_import_run_nonce'); ?>
                            <?php
                            $state_skip_images = array_key_exists('skip_images', $state) ? !empty($state['skip_images']) : true;
                            $state_import_images = !$state_skip_images;
                            ?>
                            <div class="bw-import-mapping-modal__body">
                                <?php if (!empty($state['upload_summary'])): ?>
                                    <div class="bw-import-mapping-modal__summary">
                                        <h4 class="bw-import-mapping-modal__summary-title"><?php esc_html_e('Step 1 — Upload summary', 'bw'); ?></h4>
                                        <table class="widefat fixed bw-import-upload-summary__table">
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
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <div class="notice notice-success inline bw-import-mapping-section__notice">
                                    <p><?php esc_html_e('Match each CSV column to a WooCommerce field or a custom meta field, then continue to Step 3 — Run import.', 'bw'); ?></p>
                                </div>

                                <div class="bw-import-mapping-status-summary" id="bw-import-mapping-status-summary" aria-live="polite">
                                    <span class="bw-import-mapping-status-summary__item">
                                        <strong><?php esc_html_e('Mapped:', 'bw'); ?></strong>
                                        <span id="bw-import-mapping-status-mapped">0</span>
                                    </span>
                                    <span class="bw-import-mapping-status-summary__item">
                                        <strong><?php esc_html_e('Ignored:', 'bw'); ?></strong>
                                        <span id="bw-import-mapping-status-ignored">0</span>
                                    </span>
                                </div>

                                <div
                                    class="notice notice-warning inline bw-import-mapping-digital-warning"
                                    id="bw-import-mapping-digital-warning"
                                    hidden
                                >
                                    <p><?php esc_html_e('Some important Digital fields are ignored. Review highlighted rows before running the import.', 'bw'); ?></p>
                                </div>

                                <table class="widefat fixed bw-import-mapping-table">
                                    <thead>
                                        <tr>
                                            <th class="bw-import-mapping-table__heading bw-import-mapping-table__heading--column"><?php esc_html_e('CSV Column', 'bw'); ?></th>
                                            <th class="bw-import-mapping-table__heading bw-import-mapping-table__heading--mapping"><?php esc_html_e('Map To', 'bw'); ?></th>
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
                                            <tr class="bw-import-mapping-table__row" data-mapping-header="<?php echo esc_attr($header); ?>">
                                                <td class="bw-import-mapping-table__column">
                                                    <div class="bw-import-mapping-table__column-inner">
                                                        <span
                                                            class="bw-import-mapping-status-badge"
                                                            data-state="ignored"
                                                            aria-label="<?php esc_attr_e('Ignored', 'bw'); ?>"
                                                        >
                                                            <span class="bw-import-mapping-status-badge__icon" aria-hidden="true">!</span>
                                                            <span class="screen-reader-text bw-import-mapping-status-badge__text"><?php esc_html_e('Ignored', 'bw'); ?></span>
                                                        </span>
                                                        <strong><?php echo esc_html($header); ?></strong>
                                                    </div>
                                                </td>
                                                <td class="bw-import-mapping-table__mapping">
                                                    <select class="bw-import-mapping-table__select" name="bw_import_mapping[<?php echo esc_attr($header); ?>]">
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

                                <div class="bw-import-mapping-preview">
                                    <p><strong><?php esc_html_e('Preview (first 5 rows):', 'bw'); ?></strong></p>
                                    <div class="bw-import-mapping-preview__table">
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
                                </div>

                                <div class="bw-import-mapping-images">
                                    <strong><?php esc_html_e('Image import to WordPress Media Library', 'bw'); ?></strong>
                                    <label class="bw-import-mapping-images__label">
                                        <input type="checkbox" name="bw_import_import_images" value="1" <?php checked($state_import_images); ?> />
                                        <span><?php esc_html_e('Import images from CSV URLs into the WordPress Media Library', 'bw'); ?></span>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('When enabled, WordPress downloads the featured image, product gallery images, hover image, showcase image, and variation images from the CSV URLs and saves them in the Media Library. Disable this only if you want to create/update the product without importing images.', 'bw'); ?>
                                    </p>
                                    <p class="description">
                                        <?php esc_html_e('Digital CSVs default to importing images. Other import profiles may keep image import disabled by default unless you enable it here.', 'bw'); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="bw-import-mapping-modal__footer">
                                <p class="bw-import-mapping-modal__footer-copy"><?php esc_html_e('Step 3 — Run import using the mapping above.', 'bw'); ?></p>
                                <div class="bw-import-mapping-modal__actions">
                                    <button type="button" class="button" id="bw-import-mapping-close-footer">
                                        <?php esc_html_e('Close / Review later', 'bw'); ?>
                                    </button>
                                    <?php
                                    $button_label = !empty($state['in_progress'])
                                        ? __('Continue Import (next chunk)', 'bw')
                                        : __('Save Mapping & Run Import', 'bw');
                                    submit_button($button_label, 'primary', 'bw_import_run', false);
                                    ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
