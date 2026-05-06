<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BW_LINK_PAGE_OPTION')) {
    define('BW_LINK_PAGE_OPTION', 'bw_link_page_settings_v1');
}

if (!defined('BW_LINK_PAGE_DB_VERSION')) {
    define('BW_LINK_PAGE_DB_VERSION', '1');
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

function bw_link_page_get_clicks_table_name()
{
    global $wpdb;

    return $wpdb->prefix . 'bw_link_page_clicks';
}

function bw_link_page_install_clicks_table()
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table_name = bw_link_page_get_clicks_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id BIGINT UNSIGNED NOT NULL,
        link_id VARCHAR(80) NOT NULL,
        link_label TEXT NOT NULL,
        target_url TEXT NULL,
        clicked_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY page_id (page_id),
        KEY link_id (link_id),
        KEY clicked_at (clicked_at),
        KEY page_clicked_at (page_id, clicked_at)
    ) {$charset_collate};";

    dbDelta($sql);
    update_option('bw_link_page_db_version', BW_LINK_PAGE_DB_VERSION, false);
}

function bw_link_page_maybe_install_clicks_table()
{
    $current_version = (string) get_option('bw_link_page_db_version', '');

    if (BW_LINK_PAGE_DB_VERSION === $current_version) {
        return;
    }

    bw_link_page_install_clicks_table();
}
add_action('admin_init', 'bw_link_page_maybe_install_clicks_table', 5);

function bw_link_page_build_link_id($link, $index)
{
    $link = is_array($link) ? $link : [];
    $label = isset($link['label']) ? (string) $link['label'] : '';
    $url = isset($link['url']) ? (string) $link['url'] : '';

    $slug = sanitize_title($label);
    if ('' === $slug) {
        $slug = 'link';
    }

    $fingerprint = substr(md5($label . '|' . $url . '|' . (string) $index), 0, 10);
    $id = $slug . '-' . $fingerprint;

    return substr($id, 0, 80);
}

function bw_link_page_sanitize_link_id($raw_link_id)
{
    $link_id = sanitize_text_field((string) $raw_link_id);
    $link_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $link_id);

    if (!is_string($link_id)) {
        return '';
    }

    return substr($link_id, 0, 80);
}

function bw_link_page_debug_log($message, $context = [])
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $line = '[BW Link Page] ' . (string) $message;
    if (!empty($context)) {
        $encoded = wp_json_encode($context);
        if (is_string($encoded)) {
            $line .= ' ' . $encoded;
        }
    }

    error_log($line);
}

function bw_link_page_track_click_ajax()
{
    bw_link_page_maybe_install_clicks_table();

    if (!check_ajax_referer('bw_link_page_track_click', 'nonce', false)) {
        wp_send_json_error(['message' => 'invalid_nonce'], 400);
    }

    $settings = bw_link_page_get_settings();
    $configured_page_id = !empty($settings['page_id']) ? (int) $settings['page_id'] : 0;

    $page_id = isset($_POST['page_id']) ? absint(wp_unslash($_POST['page_id'])) : 0;
    $link_id = isset($_POST['link_id']) ? bw_link_page_sanitize_link_id(wp_unslash($_POST['link_id'])) : '';
    $link_label = isset($_POST['link_label']) ? sanitize_text_field(wp_unslash($_POST['link_label'])) : '';
    $target_url = isset($_POST['target_url']) ? esc_url_raw(wp_unslash($_POST['target_url'])) : '';

    bw_link_page_debug_log('track_click_payload', [
        'configured_page_id' => $configured_page_id,
        'page_id' => $page_id,
        'link_id' => $link_id,
        'link_label' => $link_label,
        'target_url' => $target_url,
    ]);

    if ($configured_page_id <= 0 || $page_id <= 0 || $configured_page_id !== $page_id) {
        bw_link_page_debug_log('track_click_invalid_page', [
            'configured_page_id' => $configured_page_id,
            'page_id' => $page_id,
        ]);
        wp_send_json_error(['message' => 'invalid_page'], 400);
    }

    if ('' === $link_id || '' === $link_label) {
        bw_link_page_debug_log('track_click_invalid_payload', [
            'link_id' => $link_id,
            'link_label' => $link_label,
        ]);
        wp_send_json_error(['message' => 'invalid_payload'], 400);
    }

    global $wpdb;

    $inserted = $wpdb->insert(
        bw_link_page_get_clicks_table_name(),
        [
            'page_id' => $page_id,
            'link_id' => $link_id,
            'link_label' => $link_label,
            'target_url' => $target_url,
            'clicked_at' => current_time('mysql'),
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );

    if (false === $inserted) {
        bw_link_page_debug_log('track_click_insert_failed', [
            'last_error' => $wpdb->last_error,
        ]);
        wp_send_json_error(['message' => 'insert_failed'], 500);
    }

    bw_link_page_debug_log('track_click_insert_ok', [
        'insert_id' => (int) $wpdb->insert_id,
        'page_id' => $page_id,
        'link_id' => $link_id,
    ]);

    wp_send_json_success(['ok' => true]);
}
add_action('wp_ajax_bw_link_page_track_click', 'bw_link_page_track_click_ajax');
add_action('wp_ajax_nopriv_bw_link_page_track_click', 'bw_link_page_track_click_ajax');

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

function bw_link_page_get_analytics_summary($page_id)
{
    global $wpdb;

    $table = bw_link_page_get_clicks_table_name();

    $today_start = wp_date('Y-m-d 00:00:00', current_time('timestamp'));
    $seven_days_start = wp_date('Y-m-d H:i:s', strtotime('-7 days', current_time('timestamp')));
    $thirty_days_start = wp_date('Y-m-d H:i:s', strtotime('-30 days', current_time('timestamp')));

    return [
        'total' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE page_id = %d", $page_id)),
        'today' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE page_id = %d AND clicked_at >= %s", $page_id, $today_start)),
        'last_7_days' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE page_id = %d AND clicked_at >= %s", $page_id, $seven_days_start)),
        'last_30_days' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE page_id = %d AND clicked_at >= %s", $page_id, $thirty_days_start)),
    ];
}

function bw_link_page_get_analytics_daily_clicks($page_id)
{
    global $wpdb;

    $table = bw_link_page_get_clicks_table_name();
    $start = wp_date('Y-m-d 00:00:00', strtotime('-29 days', current_time('timestamp')));

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE(clicked_at) AS click_day, COUNT(*) AS clicks
            FROM {$table}
            WHERE page_id = %d AND clicked_at >= %s
            GROUP BY DATE(clicked_at)
            ORDER BY click_day ASC",
            $page_id,
            $start
        ),
        ARRAY_A
    );

    $mapped = [];
    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (empty($row['click_day'])) {
                continue;
            }
            $mapped[(string) $row['click_day']] = (int) $row['clicks'];
        }
    }

    $series = [];
    for ($offset = 29; $offset >= 0; $offset--) {
        $day = wp_date('Y-m-d', strtotime('-' . $offset . ' days', current_time('timestamp')));
        $series[] = [
            'date' => $day,
            'label' => wp_date('M j', strtotime($day)),
            'count' => isset($mapped[$day]) ? (int) $mapped[$day] : 0,
        ];
    }

    return $series;
}

function bw_link_page_get_analytics_link_rows($page_id)
{
    global $wpdb;

    $table = bw_link_page_get_clicks_table_name();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                link_id,
                MAX(link_label) AS link_label,
                COUNT(*) AS total_clicks,
                MAX(clicked_at) AS last_click
            FROM {$table}
            WHERE page_id = %d
            GROUP BY link_id
            ORDER BY total_clicks DESC, last_click DESC
            LIMIT 100",
            $page_id
        ),
        ARRAY_A
    );

    return is_array($rows) ? $rows : [];
}

function bw_link_page_render_settings_tab($settings, $pages, $logo_url)
{
    ?>
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
    <?php
}

function bw_link_page_render_analytics_tab($page_id)
{
    if ($page_id <= 0) {
        echo '<p>' . esc_html__('Select and save a Link Page in Settings to enable analytics.', 'bw') . '</p>';
        return;
    }

    $summary = bw_link_page_get_analytics_summary($page_id);
    $daily_series = bw_link_page_get_analytics_daily_clicks($page_id);
    $link_rows = bw_link_page_get_analytics_link_rows($page_id);

    $max_daily = 0;
    foreach ($daily_series as $point) {
        $max_daily = max($max_daily, (int) $point['count']);
    }

    $cards = [
        __('Total clicks', 'bw') => (int) $summary['total'],
        __('Today', 'bw') => (int) $summary['today'],
        __('Last 7 days', 'bw') => (int) $summary['last_7_days'],
        __('Last 30 days', 'bw') => (int) $summary['last_30_days'],
    ];

    ?>
    <div style="max-width:980px;">
        <div style="display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:12px;margin:16px 0 22px;">
            <?php foreach ($cards as $label => $value) : ?>
                <div style="border:1px solid #d9d9d9;border-radius:10px;padding:12px;background:#fff;">
                    <div style="font-size:12px;color:#666;margin-bottom:6px;"><?php echo esc_html($label); ?></div>
                    <div style="font-size:24px;line-height:1.1;font-weight:700;color:#111;"><?php echo esc_html((string) $value); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ((int) $summary['total'] <= 0) : ?>
            <p><?php esc_html_e('No link clicks yet.', 'bw'); ?></p>
            <?php return; ?>
        <?php endif; ?>

        <h2 style="margin:18px 0 10px;"><?php esc_html_e('Daily Clicks (Last 30 Days)', 'bw'); ?></h2>
        <div style="display:grid;grid-template-columns:repeat(30,minmax(0,1fr));gap:6px;align-items:end;min-height:150px;padding:14px;border:1px solid #d9d9d9;border-radius:10px;background:#fff;">
            <?php foreach ($daily_series as $point) :
                $count = (int) $point['count'];
                $height_pct = $max_daily > 0 ? max(3, (int) floor(($count / $max_daily) * 100)) : 3;
                ?>
                <div title="<?php echo esc_attr($point['label'] . ': ' . $count); ?>" style="display:flex;align-items:flex-end;justify-content:center;min-height:120px;">
                    <span style="display:block;width:100%;max-width:16px;border-radius:5px 5px 0 0;background:#80FD03;height:<?php echo esc_attr((string) $height_pct); ?>%;"></span>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 style="margin:22px 0 10px;"><?php esc_html_e('Links', 'bw'); ?></h2>
        <table class="widefat striped" style="max-width:980px;">
            <thead>
            <tr>
                <th><?php esc_html_e('Link label', 'bw'); ?></th>
                <th><?php esc_html_e('Total clicks', 'bw'); ?></th>
                <th><?php esc_html_e('Last click', 'bw'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($link_rows as $row) : ?>
                <tr>
                    <td><?php echo esc_html((string) $row['link_label']); ?></td>
                    <td><?php echo esc_html((string) ((int) $row['total_clicks'])); ?></td>
                    <td><?php echo esc_html((string) $row['last_click']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function bw_link_page_render_admin_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    bw_link_page_maybe_install_clicks_table();

    $settings = bw_link_page_get_settings();
    $pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
    $logo_url = !empty($settings['logo_id']) ? wp_get_attachment_image_url((int) $settings['logo_id'], 'medium') : '';
    $page_id = !empty($settings['page_id']) ? (int) $settings['page_id'] : 0;

    $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
    if (!in_array($active_tab, ['settings', 'analytics'], true)) {
        $active_tab = 'settings';
    }

    ?>
    <div class="wrap bw-site-settings-wrap">
        <h1><?php esc_html_e('Link Page', 'bw'); ?></h1>

        <nav class="nav-tab-wrapper" style="margin-bottom:16px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=bw-link-page-settings&tab=settings')); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Settings', 'bw'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bw-link-page-settings&tab=analytics')); ?>" class="nav-tab <?php echo 'analytics' === $active_tab ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Analytics', 'bw'); ?>
            </a>
        </nav>

        <?php if ('settings' === $active_tab) : ?>
            <p><?php esc_html_e('Configure one dedicated lightweight Link Page.', 'bw'); ?></p>
            <?php bw_link_page_render_settings_tab($settings, $pages, $logo_url); ?>
        <?php else : ?>
            <p><?php esc_html_e('Internal click analytics for the selected Link Page.', 'bw'); ?></p>
            <?php bw_link_page_render_analytics_tab($page_id); ?>
        <?php endif; ?>
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
