<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'bw_mf_register_settings_submenu', 60);

if (!function_exists('bw_mf_register_settings_submenu')) {
    function bw_mf_register_settings_submenu()
    {
        add_submenu_page(
            'blackwork-site-settings',
            __('Media Folders', 'bw'),
            __('Media Folders', 'bw'),
            'manage_options',
            'bw-media-folders-settings',
            'bw_mf_render_settings_page'
        );
    }
}

if (!function_exists('bw_mf_render_settings_page')) {
    function bw_mf_render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['bw_mf_settings_submit'])) {
            check_admin_referer('bw_mf_settings_save', 'bw_mf_settings_nonce');
            $enabled = !empty($_POST['bw_core_flags']['media_folders']) ? 1 : 0;
            bw_core_update_flags([
                'media_folders' => $enabled,
            ]);

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Media Folders settings saved.', 'bw') . '</p></div>';
        }

        $flags = bw_core_get_flags();
        $enabled = !empty($flags['media_folders']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Media Folders', 'bw'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('bw_mf_settings_save', 'bw_mf_settings_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Media Folders', 'bw'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="bw_core_flags[media_folders]" value="1" <?php checked($enabled); ?> />
                                    <?php esc_html_e('Enable folder sidebar and media organization in Media Library.', 'bw'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When disabled, Media Folders module is a no-op (no assets, no filters, no AJAX endpoints).', 'bw'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary" name="bw_mf_settings_submit" value="1">
                        <?php esc_html_e('Save Settings', 'bw'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
