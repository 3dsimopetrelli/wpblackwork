<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_LINK_PAGE_OPTION')) {
    define('BW_LINK_PAGE_OPTION', 'bw_link_page_settings_v1');
}

/**
 * Return normalized Link Page settings.
 *
 * @return array<string,mixed>
 */
function bw_link_page_get_settings()
{
    $defaults = [
        'page_id' => 0,
        'logo_id' => 0,
        'title' => '',
        'description' => '',
        'links' => [],
        'socials' => [
            'instagram' => ['enabled' => 0, 'url' => ''],
            'youtube' => ['enabled' => 0, 'url' => ''],
            'pinterest' => ['enabled' => 0, 'url' => ''],
        ],
    ];

    $settings = get_option(BW_LINK_PAGE_OPTION, []);
    $settings = is_array($settings) ? $settings : [];

    return wp_parse_args($settings, $defaults);
}

/**
 * Sanitize and normalize settings payload.
 *
 * @param mixed $raw Raw option value.
 * @return array<string,mixed>
 */
function bw_link_page_sanitize_settings($raw)
{
    $raw = is_array($raw) ? $raw : [];

    $settings = [
        'page_id' => isset($raw['page_id']) ? absint($raw['page_id']) : 0,
        'logo_id' => isset($raw['logo_id']) ? absint($raw['logo_id']) : 0,
        'title' => isset($raw['title']) ? sanitize_text_field($raw['title']) : '',
        'description' => isset($raw['description']) ? sanitize_textarea_field($raw['description']) : '',
        'links' => [],
        'socials' => [
            'instagram' => ['enabled' => 0, 'url' => ''],
            'youtube' => ['enabled' => 0, 'url' => ''],
            'pinterest' => ['enabled' => 0, 'url' => ''],
        ],
    ];

    if (!empty($raw['links']) && is_array($raw['links'])) {
        foreach ($raw['links'] as $link) {
            if (!is_array($link)) {
                continue;
            }

            $label = isset($link['label']) ? sanitize_text_field($link['label']) : '';
            $url = isset($link['url']) ? esc_url_raw($link['url']) : '';
            $target = !empty($link['target']) ? 1 : 0;

            if ('' === $label || '' === $url) {
                continue;
            }

            $settings['links'][] = [
                'label' => $label,
                'url' => $url,
                'target' => $target,
            ];
        }
    }

    $social_keys = ['instagram', 'youtube', 'pinterest'];
    foreach ($social_keys as $key) {
        $social = isset($raw['socials'][$key]) && is_array($raw['socials'][$key]) ? $raw['socials'][$key] : [];
        $settings['socials'][$key] = [
            'enabled' => !empty($social['enabled']) ? 1 : 0,
            'url' => isset($social['url']) ? esc_url_raw($social['url']) : '',
        ];
    }

    return $settings;
}

function bw_link_page_register_settings()
{
    register_setting('bw_link_page_settings_group', BW_LINK_PAGE_OPTION, [
        'type' => 'array',
        'sanitize_callback' => 'bw_link_page_sanitize_settings',
        'default' => bw_link_page_get_settings(),
    ]);
}
add_action('admin_init', 'bw_link_page_register_settings');

function bw_link_page_add_admin_menu()
{
    add_submenu_page(
        'blackwork-site-settings',
        __('Link Page', 'bw'),
        __('Link Page', 'bw'),
        'manage_options',
        'bw-link-page-settings',
        'bw_link_page_render_admin_page'
    );
}
add_action('admin_menu', 'bw_link_page_add_admin_menu', 62);

function bw_link_page_enqueue_admin_assets($hook)
{
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $is_link_page_screen = (
        'bw-link-page-settings' === $current_page
        || 'blackwork-site-settings_page_bw-link-page-settings' === $hook
        || 'blackwork-site_page_bw-link-page-settings' === $hook
    );

    if (!$is_link_page_screen) {
        return;
    }

    wp_enqueue_media();

    $admin_js_path = BW_MEW_PATH . 'includes/modules/link-page/admin/link-page-admin.js';
    wp_enqueue_script(
        'bw-link-page-admin',
        BW_MEW_URL . 'includes/modules/link-page/admin/link-page-admin.js',
        ['jquery', 'media-editor', 'media-views', 'wp-util'],
        file_exists($admin_js_path) ? filemtime($admin_js_path) : BLACKWORK_PLUGIN_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'bw_link_page_enqueue_admin_assets', 20);

function bw_link_page_render_admin_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = bw_link_page_get_settings();
    $pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
    $logo_url = !empty($settings['logo_id']) ? wp_get_attachment_image_url((int) $settings['logo_id'], 'medium') : '';
    ?>
    <div class="wrap bw-site-settings-wrap">
        <h1><?php esc_html_e('Link Page', 'bw'); ?></h1>
        <p><?php esc_html_e('Configure one dedicated lightweight Link Page.', 'bw'); ?></p>

        <form method="post" action="options.php" class="bw-site-settings-form" style="max-width: 980px;">
            <?php settings_fields('bw_link_page_settings_group'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label for="bw-link-page-id"><?php esc_html_e('Page', 'bw'); ?></label></th>
                    <td>
                        <select id="bw-link-page-id" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[page_id]" required>
                            <option value="0"><?php esc_html_e('Select a page', 'bw'); ?></option>
                            <?php foreach ($pages as $page) : ?>
                                <option value="<?php echo esc_attr((string) $page->ID); ?>" <?php selected((int) $settings['page_id'], (int) $page->ID); ?>>
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Logo', 'bw'); ?></th>
                    <td>
                        <input type="hidden" id="bw-link-page-logo-id" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[logo_id]" value="<?php echo esc_attr((string) $settings['logo_id']); ?>">
                        <button type="button" class="button" id="bw-link-page-logo-upload"><?php esc_html_e('Select logo', 'bw'); ?></button>
                        <button type="button" class="button" id="bw-link-page-logo-remove"><?php esc_html_e('Remove', 'bw'); ?></button>
                        <div id="bw-link-page-logo-preview" style="margin-top:12px;">
                            <?php if (!empty($logo_url)) : ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="" style="max-width:140px;height:auto;display:block;">
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bw-link-page-title"><?php esc_html_e('Title (optional)', 'bw'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="bw-link-page-title" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[title]" value="<?php echo esc_attr($settings['title']); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bw-link-page-description"><?php esc_html_e('Description (optional)', 'bw'); ?></label></th>
                    <td>
                        <textarea id="bw-link-page-description" class="large-text" rows="4" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[description]"><?php echo esc_textarea($settings['description']); ?></textarea>
                    </td>
                </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e('Links', 'bw'); ?></h2>
            <table class="widefat striped" id="bw-link-page-links-table" style="max-width:980px;">
                <thead>
                <tr>
                    <th><?php esc_html_e('Label', 'bw'); ?></th>
                    <th><?php esc_html_e('URL', 'bw'); ?></th>
                    <th><?php esc_html_e('Open in new tab', 'bw'); ?></th>
                    <th><?php esc_html_e('Action', 'bw'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($settings['links'])) : ?>
                    <?php foreach ($settings['links'] as $index => $link) : ?>
                        <tr>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[links][<?php echo esc_attr((string) $index); ?>][label]" value="<?php echo esc_attr($link['label']); ?>"></td>
                            <td><input type="url" class="regular-text" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[links][<?php echo esc_attr((string) $index); ?>][url]" value="<?php echo esc_attr($link['url']); ?>"></td>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[links][<?php echo esc_attr((string) $index); ?>][target]" value="1" <?php checked(!empty($link['target'])); ?>> _blank</label></td>
                            <td><button type="button" class="button bw-link-page-remove-link"><?php esc_html_e('Remove', 'bw'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="bw-link-page-add-link"><?php esc_html_e('Add link', 'bw'); ?></button></p>

            <h2><?php esc_html_e('Socials', 'bw'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                <?php
                $social_labels = [
                    'instagram' => 'Instagram',
                    'youtube' => 'YouTube',
                    'pinterest' => 'Pinterest',
                ];
                foreach ($social_labels as $key => $label) :
                    $social = isset($settings['socials'][$key]) && is_array($settings['socials'][$key]) ? $settings['socials'][$key] : ['enabled' => 0, 'url' => ''];
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="checkbox" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[socials][<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked(!empty($social['enabled'])); ?>>
                                <?php esc_html_e('Enabled', 'bw'); ?>
                            </label>
                            <input type="url" class="regular-text" placeholder="https://" name="<?php echo esc_attr(BW_LINK_PAGE_OPTION); ?>[socials][<?php echo esc_attr($key); ?>][url]" value="<?php echo esc_attr((string) $social['url']); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(__('Save Link Page Settings', 'bw')); ?>
        </form>
    </div>
    <?php
}

function bw_link_page_template_include($template)
{
    if (is_admin()) {
        return $template;
    }

    $settings = bw_link_page_get_settings();
    $page_id = !empty($settings['page_id']) ? (int) $settings['page_id'] : 0;

    if ($page_id > 0 && is_page($page_id)) {
        $link_page_template = __DIR__ . '/templates/template-link-page.php';
        if (file_exists($link_page_template)) {
            return $link_page_template;
        }
    }

    return $template;
}
add_filter('template_include', 'bw_link_page_template_include', 999);
