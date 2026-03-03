<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_import_template_max_size')) {
    function bw_tbl_import_template_max_size()
    {
        $default = 8 * MB_IN_BYTES;
        return (int) apply_filters('bw_tbl_import_template_max_size', $default);
    }
}

if (!function_exists('bw_tbl_import_type_map')) {
    function bw_tbl_import_type_map($raw_type)
    {
        $raw_type = sanitize_key((string) $raw_type);
        $raw_type = str_replace('-', '_', $raw_type);

        $map = [
            'product_archive' => 'product_archive',
            'single_product' => 'single_product',
            'footer' => 'footer',
            'single_post' => 'single_post',
            'single_page' => 'single_page',
            'archive' => 'archive',
            'search' => 'search',
            'error_404' => 'error_404',
            '404' => 'error_404',
        ];

        return isset($map[$raw_type]) ? $map[$raw_type] : '';
    }
}

if (!function_exists('bw_tbl_import_template_choices')) {
    function bw_tbl_import_template_choices()
    {
        $values = function_exists('bw_tbl_template_type_allowed_values')
            ? bw_tbl_template_type_allowed_values()
            : ['footer', 'single_post', 'single_page', 'single_product', 'product_archive', 'archive', 'search', 'error_404'];

        $labels = [
            'footer' => __('Footer', 'bw'),
            'single_post' => __('Single Post', 'bw'),
            'single_page' => __('Single Page', 'bw'),
            'single_product' => __('Single Product', 'bw'),
            'product_archive' => __('Product Archive', 'bw'),
            'archive' => __('Archive', 'bw'),
            'search' => __('Search Results', 'bw'),
            'error_404' => __('Error 404', 'bw'),
        ];

        $choices = [];
        foreach ($values as $value) {
            $value = sanitize_key((string) $value);
            if ('' === $value || !isset($labels[$value])) {
                continue;
            }
            $choices[$value] = $labels[$value];
        }

        return $choices;
    }
}

if (!function_exists('bw_tbl_import_redirect_url')) {
    function bw_tbl_import_redirect_url($status, $message, $post_id = 0)
    {
        $args = [
            'page' => 'bw-theme-builder-lite-settings',
            'tab' => 'import-template',
            'bw_tbl_import_status' => sanitize_key((string) $status),
            'bw_tbl_import_message' => rawurlencode((string) $message),
        ];

        $post_id = absint($post_id);
        if ($post_id > 0) {
            $args['bw_tbl_import_post_id'] = $post_id;
        }

        return add_query_arg($args, admin_url('admin.php'));
    }
}

if (!function_exists('bw_tbl_import_result')) {
    function bw_tbl_import_result()
    {
        $status = isset($_GET['bw_tbl_import_status']) ? sanitize_key(wp_unslash($_GET['bw_tbl_import_status'])) : '';
        if ('' === $status) {
            return null;
        }

        $message = isset($_GET['bw_tbl_import_message']) ? sanitize_text_field(rawurldecode(wp_unslash($_GET['bw_tbl_import_message']))) : '';
        $post_id = isset($_GET['bw_tbl_import_post_id']) ? absint(wp_unslash($_GET['bw_tbl_import_post_id'])) : 0;

        return [
            'status' => $status,
            'message' => $message,
            'post_id' => $post_id,
        ];
    }
}

if (!function_exists('bw_tbl_render_import_template_tab')) {
    function bw_tbl_render_import_template_tab()
    {
        $result = bw_tbl_import_result();
        $max_size = bw_tbl_import_template_max_size();
        $max_mb = number_format_i18n($max_size / MB_IN_BYTES, 0);
        if (is_array($result)) {
            $notice_class = ('success' === $result['status']) ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($notice_class) . '" style="margin:0 0 12px 0;padding:10px 12px;"><p style="margin:0;">' . esc_html($result['message']) . '</p>';
            if ('success' === $result['status'] && !empty($result['post_id'])) {
                $edit_link = get_edit_post_link((int) $result['post_id'], 'raw');
                if (is_string($edit_link) && '' !== $edit_link) {
                    echo '<p style="margin:8px 0 0 0;"><a class="button button-secondary" href="' . esc_url($edit_link) . '">' . esc_html__('Edit Imported Template', 'bw') . '</a></p>';
                }
            }
            echo '</div>';
        }
        ?>
        <p><?php esc_html_e('Import an Elementor JSON export and create a BW Template draft with mapped template type and Elementor metadata.', 'bw'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('bw_tbl_import_template'); ?>
            <input type="hidden" name="action" value="bw_tbl_import_template" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="bw-tbl-import-file"><?php esc_html_e('Elementor JSON File', 'bw'); ?></label></th>
                    <td>
                        <input id="bw-tbl-import-file" type="file" name="bw_tbl_import_file" accept=".json,application/json,text/json,text/plain" required />
                        <p class="description"><?php echo esc_html(sprintf(__('Accepted: .json (max %s MB)', 'bw'), (string) $max_mb)); ?></p>
                        <p class="description"><?php esc_html_e('Template type is auto-detected from the JSON export type. If not mappable, import is aborted.', 'bw'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Import Template', 'bw'), 'primary', 'bw_tbl_import_submit', false); ?>
        </form>
        <?php bw_tbl_render_import_history(); ?>
        <?php
    }
}

if (!function_exists('bw_tbl_render_import_history')) {
    function bw_tbl_render_import_history()
    {
        $query = new WP_Query(
            [
                'post_type' => 'bw_template',
                'post_status' => ['draft', 'publish', 'pending', 'future', 'private'],
                'posts_per_page' => 10,
                'no_found_rows' => true,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => [
                    [
                        'key' => 'bw_tbl_imported',
                        'value' => '1',
                    ],
                ],
            ]
        );

        echo '<hr style="margin:20px 0;" />';
        echo '<h2 style="margin:0 0 10px 0;">' . esc_html__('Recent Imports', 'bw') . '</h2>';

        if (!$query->have_posts()) {
            echo '<p class="description">' . esc_html__('No imported templates yet.', 'bw') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Title', 'bw') . '</th>';
        echo '<th>' . esc_html__('Detected Type', 'bw') . '</th>';
        echo '<th>' . esc_html__('Date', 'bw') . '</th>';
        echo '<th>' . esc_html__('Actions', 'bw') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($query->posts as $post) {
            if (!($post instanceof WP_Post)) {
                continue;
            }

            $post_id = absint($post->ID);
            if ($post_id <= 0) {
                continue;
            }

            $title = get_the_title($post_id);
            $title = is_string($title) && '' !== trim($title) ? $title : sprintf(__('Template #%d', 'bw'), $post_id);

            $raw_type = get_post_meta($post_id, 'bw_template_type', true);
            $type = function_exists('bw_tbl_sanitize_template_type') ? bw_tbl_sanitize_template_type($raw_type) : sanitize_key((string) $raw_type);
            $type_label = $type;
            if (function_exists('bw_tbl_admin_template_type_label')) {
                $type_label = bw_tbl_admin_template_type_label($type);
            }

            $edit_link = get_edit_post_link($post_id, 'raw');
            $date_display = get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $post_id);

            echo '<tr>';
            echo '<td>' . esc_html($title) . '</td>';
            echo '<td>' . esc_html((string) $type_label) . '</td>';
            echo '<td>' . esc_html((string) $date_display) . '</td>';
            echo '<td>';
            if (is_string($edit_link) && '' !== $edit_link) {
                echo '<a class="button button-small" href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'bw') . '</a>';
            } else {
                echo '<span class="description">' . esc_html__('Unavailable', 'bw') . '</span>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

if (!function_exists('bw_tbl_import_template_handle')) {
    function bw_tbl_import_template_handle()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to import templates.', 'bw'));
        }

        check_admin_referer('bw_tbl_import_template');

        if (empty($_FILES['bw_tbl_import_file']) || !is_array($_FILES['bw_tbl_import_file'])) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('No file uploaded.', 'bw')));
            exit;
        }

        $file = $_FILES['bw_tbl_import_file'];
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        if (UPLOAD_ERR_OK !== $error) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Upload failed. Please try again.', 'bw')));
            exit;
        }

        $name = isset($file['name']) ? sanitize_file_name(wp_unslash($file['name'])) : '';
        $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        $size = isset($file['size']) ? (int) $file['size'] : 0;

        if ('' === $name || '' === $tmp_name || !is_uploaded_file($tmp_name)) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Invalid uploaded file.', 'bw')));
            exit;
        }

        $max_size = bw_tbl_import_template_max_size();
        if ($size <= 0 || $size > $max_size) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('File is too large or empty.', 'bw')));
            exit;
        }

        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if ('json' !== $ext) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Only .json files are allowed.', 'bw')));
            exit;
        }

        $checked = wp_check_filetype_and_ext($tmp_name, $name, ['json' => 'application/json']);
        $validated_ext = isset($checked['ext']) ? (string) $checked['ext'] : '';
        if ('' !== $validated_ext && 'json' !== $validated_ext) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Uploaded file type is not valid JSON.', 'bw')));
            exit;
        }

        $json_raw = file_get_contents($tmp_name); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if (!is_string($json_raw) || '' === trim($json_raw)) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('JSON file is empty.', 'bw')));
            exit;
        }

        $payload = json_decode($json_raw, true);
        if (!is_array($payload) || JSON_ERROR_NONE !== json_last_error()) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Invalid JSON payload.', 'bw')));
            exit;
        }

        $title = isset($payload['title']) ? sanitize_text_field((string) $payload['title']) : '';
        if ('' === $title) {
            $title = sanitize_text_field((string) wp_basename($name, '.json'));
        }
        if ('' === $title) {
            $title = __('Imported Template', 'bw');
        }

        $raw_type = isset($payload['type']) ? (string) $payload['type'] : '';
        $template_type = bw_tbl_import_type_map($raw_type);

        $allowed_types = bw_tbl_import_template_choices();
        if ('' === $template_type || !isset($allowed_types[$template_type])) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Template type could not be mapped from JSON type. Import aborted.', 'bw')));
            exit;
        }

        $elements = [];
        if (isset($payload['content']) && is_array($payload['content'])) {
            $elements = $payload['content'];
        } elseif (isset($payload['elements']) && is_array($payload['elements'])) {
            $elements = $payload['elements'];
        }

        if (empty($elements)) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('JSON does not contain Elementor content elements.', 'bw')));
            exit;
        }

        $page_settings = [];
        if (isset($payload['page_settings']) && is_array($payload['page_settings'])) {
            $page_settings = $payload['page_settings'];
        }

        $elementor_data_json = wp_json_encode($elements);
        $page_settings_json = wp_json_encode($page_settings);
        if (!is_string($elementor_data_json) || '' === $elementor_data_json || !is_string($page_settings_json)) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Unable to encode Elementor data for import.', 'bw')));
            exit;
        }

        $prefixed_title = sprintf(__('Imported — %s', 'bw'), $title);

        $post_id = wp_insert_post(
            [
                'post_type' => 'bw_template',
                'post_status' => 'draft',
                'post_title' => $prefixed_title,
            ],
            true
        );

        if (is_wp_error($post_id)) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Could not create BW Template post.', 'bw')));
            exit;
        }

        $post_id = absint($post_id);
        if ($post_id <= 0) {
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Could not create BW Template post.', 'bw')));
            exit;
        }

        $required_meta = [
            'bw_template_type' => $template_type,
            'bw_tbl_imported' => '1',
            '_elementor_data' => wp_slash($elementor_data_json),
            '_elementor_edit_mode' => 'builder',
            '_elementor_version' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0',
            '_elementor_page_settings' => wp_slash($page_settings_json),
        ];

        $meta_ok = true;
        foreach ($required_meta as $meta_key => $meta_value) {
            $updated = update_post_meta($post_id, $meta_key, $meta_value);
            if (false === $updated) {
                $meta_ok = false;
                break;
            }
        }

        if (!$meta_ok) {
            wp_delete_post($post_id, true);
            wp_safe_redirect(bw_tbl_import_redirect_url('error', __('Import failed while saving Elementor metadata.', 'bw')));
            exit;
        }

        wp_safe_redirect(bw_tbl_import_redirect_url('success', __('Template imported successfully as draft.', 'bw'), $post_id));
        exit;
    }
}
add_action('admin_post_bw_tbl_import_template', 'bw_tbl_import_template_handle');
