<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_header_admin_menu')) {
    function bw_header_admin_menu()
    {
        add_submenu_page(
            'blackwork-site-settings',
            __('Header', 'bw'),
            __('Header', 'bw'),
            'manage_options',
            'bw-header-settings',
            'bw_header_render_admin_page'
        );
    }
}
add_action('admin_menu', 'bw_header_admin_menu', 20);

if (!function_exists('bw_header_admin_enqueue_assets')) {
    function bw_header_admin_enqueue_assets($hook)
    {
        $is_header_page = isset($_GET['page']) && $_GET['page'] === 'bw-header-settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$is_header_page) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'bw-header-admin',
            BW_MEW_URL . 'includes/modules/header/admin/header-admin.js',
            ['jquery', 'media-editor', 'media-models', 'media-views'],
            file_exists(BW_MEW_PATH . 'includes/modules/header/admin/header-admin.js') ? filemtime(BW_MEW_PATH . 'includes/modules/header/admin/header-admin.js') : '1.0.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'bw_header_admin_enqueue_assets');

if (!function_exists('bw_header_get_menu_options')) {
    function bw_header_get_menu_options()
    {
        $menus = wp_get_nav_menus();
        $out = [0 => __('Select a menu', 'bw')];

        if (is_wp_error($menus) || empty($menus)) {
            return $out;
        }

        foreach ($menus as $menu) {
            $out[(int) $menu->term_id] = $menu->name;
        }

        return $out;
    }
}

if (!function_exists('bw_header_render_media_field')) {
    function bw_header_render_media_field($name, $attachment_id, $label)
    {
        $attachment_id = absint($attachment_id);
        $url = $attachment_id ? wp_get_attachment_url($attachment_id) : '';
        ?>
        <tr>
            <th scope="row"><label><?php echo esc_html($label); ?></label></th>
            <td>
                <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($attachment_id); ?>" class="bw-header-media-id" />
                <div class="bw-header-media-preview" style="margin-bottom:8px;">
                    <?php if ($url) : ?>
                        <img src="<?php echo esc_url($url); ?>" alt="" style="max-width:80px;max-height:80px;display:block;" />
                    <?php endif; ?>
                </div>
                <button type="button" class="button bw-header-media-upload"><?php esc_html_e('Upload/Select', 'bw'); ?></button>
                <button type="button" class="button bw-header-media-remove" <?php disabled(!$attachment_id); ?>><?php esc_html_e('Remove', 'bw'); ?></button>
                <p class="description"><?php esc_html_e('Supports SVG and regular image attachments.', 'bw'); ?></p>
            </td>
        </tr>
        <?php
    }
}

if (!function_exists('bw_header_render_admin_page')) {
    function bw_header_render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = bw_header_get_settings();
        $menus = bw_header_get_menu_options();
        $settings_updated = !empty($_GET['settings-updated']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Blackwork Header Settings', 'bw'); ?></h1>
            <?php if ($settings_updated) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Header settings saved successfully.', 'bw'); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('bw_header_settings_group'); ?>
                <h2 class="nav-tab-wrapper" id="bw-header-tabs" style="margin-bottom:16px;">
                    <a href="#bw-header-tab-general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'bw'); ?></a>
                    <a href="#bw-header-tab-scroll" class="nav-tab"><?php esc_html_e('Header Scroll', 'bw'); ?></a>
                </h2>
                <p class="submit" style="margin: 8px 0 18px;">
                    <?php submit_button(__('Save Header Settings', 'bw'), 'primary', 'submit', false); ?>
                </p>
                <div id="bw-header-tab-general" class="bw-header-tab-panel is-active">
                    <table class="form-table" role="presentation">
                        <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Custom Header', 'bw'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                                <?php esc_html_e('Render custom header on frontend', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-title"><?php esc_html_e('Header Title', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-title" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[header_title]" value="<?php echo esc_attr($settings['header_title']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-background-color"><?php esc_html_e('Header Background Color', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-background-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[background_color]" value="<?php echo esc_attr($settings['background_color']); ?>" />
                            <p class="description"><?php esc_html_e('Used when Smart Header Scroll is OFF. If Smart Header Scroll is ON, Header Scroll colors/opacities take precedence.', 'bw'); ?></p>
                            <label style="display:block;margin-top:8px;">
                                <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[background_transparent]" value="1" <?php checked(!empty($settings['background_transparent'])); ?> />
                                <?php esc_html_e('Transparent background (override color in General tab)', 'bw'); ?>
                            </label>
                        </td>
                    </tr>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[logo_attachment_id]', $settings['logo_attachment_id'], __('Logo Upload', 'bw')); ?>
                    <tr>
                        <th scope="row"><label for="bw-header-logo-width"><?php esc_html_e('Logo Width (px)', 'bw'); ?></label></th>
                        <td><input id="bw-header-logo-width" type="number" min="10" max="1200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[logo_width]" value="<?php echo esc_attr((int) $settings['logo_width']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-logo-height"><?php esc_html_e('Logo Height (px)', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-logo-height" type="number" min="0" max="1200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[logo_height]" value="<?php echo esc_attr((int) $settings['logo_height']); ?>" />
                            <p class="description"><?php esc_html_e('Use 0 for auto height.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-desktop-menu"><?php esc_html_e('Desktop Menu', 'bw'); ?></label></th>
                        <td>
                            <select id="bw-header-desktop-menu" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[menus][desktop_menu_id]">
                                <?php foreach ($menus as $menu_id => $menu_name) : ?>
                                    <option value="<?php echo esc_attr($menu_id); ?>" <?php selected((int) $settings['menus']['desktop_menu_id'], (int) $menu_id); ?>><?php echo esc_html($menu_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-menu"><?php esc_html_e('Mobile Menu', 'bw'); ?></label></th>
                        <td>
                            <select id="bw-header-mobile-menu" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[menus][mobile_menu_id]">
                                <?php foreach ($menus as $menu_id => $menu_name) : ?>
                                    <option value="<?php echo esc_attr($menu_id); ?>" <?php selected((int) $settings['menus']['mobile_menu_id'], (int) $menu_id); ?>><?php echo esc_html($menu_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('If empty, desktop menu is used.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-breakpoint"><?php esc_html_e('Mobile Breakpoint (px)', 'bw'); ?></label></th>
                        <td><input id="bw-header-mobile-breakpoint" type="number" min="320" max="1920" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[breakpoints][mobile]" value="<?php echo esc_attr((int) $settings['breakpoints']['mobile']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-inner-padding-unit"><?php esc_html_e('Header Inner Padding Unit', 'bw'); ?></label></th>
                        <td>
                            <select id="bw-header-inner-padding-unit" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding_unit]">
                                <option value="px" <?php selected($settings['inner_padding_unit'], 'px'); ?>>px</option>
                                <option value="%" <?php selected($settings['inner_padding_unit'], '%'); ?>>%</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Header Inner Padding (Top Right Bottom Left)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][top]" value="<?php echo esc_attr((float) $settings['inner_padding']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][right]" value="<?php echo esc_attr((float) $settings['inner_padding']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][bottom]" value="<?php echo esc_attr((float) $settings['inner_padding']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="400" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[inner_padding][left]" value="<?php echo esc_attr((float) $settings['inner_padding']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                            <p class="description"><?php esc_html_e('Controls padding of .bw-custom-header__inner (the container shown in DevTools).', 'bw'); ?></p>
                        </td>
                    </tr>

                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[icons][mobile_hamburger_attachment_id]', $settings['icons']['mobile_hamburger_attachment_id'], __('Mobile Hamburger SVG', 'bw')); ?>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[icons][mobile_search_attachment_id]', $settings['icons']['mobile_search_attachment_id'], __('Mobile Search SVG', 'bw')); ?>
                    <?php bw_header_render_media_field(BW_HEADER_OPTION_KEY . '[icons][mobile_cart_attachment_id]', $settings['icons']['mobile_cart_attachment_id'], __('Mobile Cart SVG', 'bw')); ?>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-right-icons-gap"><?php esc_html_e('Mobile Right Icons Gap (px)', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-mobile-right-icons-gap" type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][right_icons_gap]" value="<?php echo esc_attr((float) $settings['mobile_layout']['right_icons_gap']); ?>" />
                            <p class="description"><?php esc_html_e('Distance between Search and Cart icons in mobile right area.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Hamburger Padding (px)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_padding][top]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_padding']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_padding][right]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_padding']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_padding][bottom]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_padding']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_padding][left]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_padding']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Hamburger Margin (px)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_margin][top]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_margin']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_margin][right]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_margin']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_margin][bottom]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_margin']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][hamburger_margin][left]" value="<?php echo esc_attr((float) $settings['mobile_layout']['hamburger_margin']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Search Padding (px)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_padding][top]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_padding']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_padding][right]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_padding']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_padding][bottom]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_padding']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_padding][left]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_padding']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Search Margin (px)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_margin][top]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_margin']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_margin][right]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_margin']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_margin][bottom]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_margin']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][search_margin][left]" value="<?php echo esc_attr((float) $settings['mobile_layout']['search_margin']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Cart Padding (px)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_padding][top]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_padding']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_padding][right]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_padding']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_padding][bottom]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_padding']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_padding][left]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_padding']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Mobile Cart Margin (px)', 'bw'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_margin][top]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_margin']['top']); ?>" placeholder="Top" style="width:90px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_margin][right]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_margin']['right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_margin][bottom]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_margin']['bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                            <input type="number" step="0.1" min="-200" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_margin][left]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_margin']['left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                            <p class="description"><?php esc_html_e('Use these controls to fine-tune mobile icon position and spacing.', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-cart-badge-offset-x"><?php esc_html_e('Mobile Cart Badge Offset X (px)', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-mobile-cart-badge-offset-x" type="number" step="0.1" min="-100" max="100" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_badge_offset_x]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_badge_offset_x']); ?>" />
                            <p class="description"><?php esc_html_e('Horizontal move of badge relative to cart icon (positive = right).', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-cart-badge-offset-y"><?php esc_html_e('Mobile Cart Badge Offset Y (px)', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-mobile-cart-badge-offset-y" type="number" step="0.1" min="-100" max="100" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_badge_offset_y]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_badge_offset_y']); ?>" />
                            <p class="description"><?php esc_html_e('Vertical move of badge relative to cart icon (positive = down).', 'bw'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-mobile-cart-badge-size"><?php esc_html_e('Mobile Cart Badge Size (em)', 'bw'); ?></label></th>
                        <td>
                            <input id="bw-header-mobile-cart-badge-size" type="number" step="0.05" min="0.6" max="3" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[mobile_layout][cart_badge_size]" value="<?php echo esc_attr((float) $settings['mobile_layout']['cart_badge_size']); ?>" />
                            <p class="description"><?php esc_html_e('Controls badge width/height/line-height scale.', 'bw'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="bw-header-label-search"><?php esc_html_e('Search Label', 'bw'); ?></label></th>
                        <td><input id="bw-header-label-search" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[labels][search]" value="<?php echo esc_attr($settings['labels']['search']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-label-account"><?php esc_html_e('Account Label', 'bw'); ?></label></th>
                        <td><input id="bw-header-label-account" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[labels][account]" value="<?php echo esc_attr($settings['labels']['account']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-label-cart"><?php esc_html_e('Cart Label', 'bw'); ?></label></th>
                        <td><input id="bw-header-label-cart" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[labels][cart]" value="<?php echo esc_attr($settings['labels']['cart']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-link-account"><?php esc_html_e('Account Link', 'bw'); ?></label></th>
                        <td><input id="bw-header-link-account" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[links][account]" value="<?php echo esc_attr($settings['links']['account']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bw-header-link-cart"><?php esc_html_e('Cart Link', 'bw'); ?></label></th>
                        <td><input id="bw-header-link-cart" type="text" class="regular-text" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[links][cart]" value="<?php echo esc_attr($settings['links']['cart']); ?>" /></td>
                    </tr>
                        </tbody>
                    </table>
                </div>

                <div id="bw-header-tab-scroll" class="bw-header-tab-panel" style="display:none;">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Smart Header Scroll', 'bw'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[features][smart_scroll]" value="1" <?php checked(!empty($settings['features']['smart_scroll'])); ?> />
                                        <?php esc_html_e('Hide on scroll down, show on scroll up', 'bw'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-scroll-down-threshold"><?php esc_html_e('Scroll Down Threshold (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-scroll-down-threshold" type="number" min="0" max="2000" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][scroll_down_threshold]" value="<?php echo esc_attr((int) $settings['smart_header']['scroll_down_threshold']); ?>" />
                                    <p class="description"><?php esc_html_e('Header hides after this scroll distance when moving down.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-scroll-up-threshold"><?php esc_html_e('Scroll Up Threshold (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-scroll-up-threshold" type="number" min="0" max="2000" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][scroll_up_threshold]" value="<?php echo esc_attr((int) $settings['smart_header']['scroll_up_threshold']); ?>" />
                                    <p class="description"><?php esc_html_e('Minimum upward scroll to show header. Use 0 for immediate show.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-scroll-delta"><?php esc_html_e('Scroll Delta (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-scroll-delta" type="number" min="1" max="100" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][scroll_delta]" value="<?php echo esc_attr((int) $settings['smart_header']['scroll_delta']); ?>" />
                                    <p class="description"><?php esc_html_e('Minimum movement to detect direction changes.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-blur-threshold"><?php esc_html_e('Blur Threshold (px)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-blur-threshold" type="number" min="0" max="2000" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][blur_threshold]" value="<?php echo esc_attr((int) $settings['smart_header']['blur_threshold']); ?>" />
                                    <p class="description"><?php esc_html_e('Apply blur/background effect after this scroll distance.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-throttle-delay"><?php esc_html_e('Throttle Delay (ms)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-throttle-delay" type="number" min="1" max="1000" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][throttle_delay]" value="<?php echo esc_attr((int) $settings['smart_header']['throttle_delay']); ?>" />
                                    <p class="description"><?php esc_html_e('Scroll handler cadence. Lower = more reactive.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-bg-color"><?php esc_html_e('Header BG Color (Smart)', 'bw'); ?></label></th>
                                <td><input id="bw-header-smart-bg-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_bg_color]" value="<?php echo esc_attr($settings['smart_header']['header_bg_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-bg-opacity"><?php esc_html_e('Header BG Opacity (Smart)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-smart-bg-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['header_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-bg-opacity').value=this.value;" />
                                    <input id="bw-header-smart-bg-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_bg_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['header_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-bg-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                    <p class="description"><?php esc_html_e('0 = fully transparent, 1 = fully opaque.', 'bw'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-scrolled-bg-color"><?php esc_html_e('Header BG Color (Scrolled)', 'bw'); ?></label></th>
                                <td><input id="bw-header-smart-scrolled-bg-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_scrolled_bg_color]" value="<?php echo esc_attr($settings['smart_header']['header_scrolled_bg_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-smart-scrolled-bg-opacity"><?php esc_html_e('Header BG Opacity (Scrolled)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-smart-scrolled-bg-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['header_scrolled_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-scrolled-bg-opacity').value=this.value;" />
                                    <input id="bw-header-smart-scrolled-bg-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][header_scrolled_bg_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['header_scrolled_bg_opacity']); ?>" oninput="document.getElementById('bw-header-smart-scrolled-bg-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Desktop Menu Blur Panel', 'bw'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_enabled]" value="1" <?php checked(!empty($settings['smart_header']['menu_blur_enabled'])); ?> />
                                        <?php esc_html_e('Enable blur panel (same visual logic as legacy Smart Header)', 'bw'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-amount"><?php esc_html_e('Blur Amount (px)', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-amount" type="number" min="0" max="100" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_amount]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_amount']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-radius"><?php esc_html_e('Panel Border Radius (px)', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-radius" type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_radius]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_radius']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-tint-color"><?php esc_html_e('Panel Tint Color', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-tint-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_tint_color]" value="<?php echo esc_attr($settings['smart_header']['menu_blur_tint_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-tint-opacity"><?php esc_html_e('Panel Tint Opacity', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-menu-blur-tint-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-tint-opacity').value=this.value;" />
                                    <input id="bw-header-menu-blur-tint-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_tint_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-tint-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-scrolled-tint-color"><?php esc_html_e('Panel Tint Color (Scrolled)', 'bw'); ?></label></th>
                                <td><input id="bw-header-menu-blur-scrolled-tint-color" type="color" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_scrolled_tint_color]" value="<?php echo esc_attr($settings['smart_header']['menu_blur_scrolled_tint_color']); ?>" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bw-header-menu-blur-scrolled-tint-opacity"><?php esc_html_e('Panel Tint Opacity (Scrolled)', 'bw'); ?></label></th>
                                <td>
                                    <input id="bw-header-menu-blur-scrolled-tint-opacity-range" type="range" min="0" max="1" step="0.01" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_scrolled_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-scrolled-tint-opacity').value=this.value;" />
                                    <input id="bw-header-menu-blur-scrolled-tint-opacity" type="number" min="0" max="1" step="0.01" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_scrolled_tint_opacity]" value="<?php echo esc_attr((float) $settings['smart_header']['menu_blur_scrolled_tint_opacity']); ?>" oninput="document.getElementById('bw-header-menu-blur-scrolled-tint-opacity-range').value=this.value;" style="width:90px;margin-left:10px;" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Panel Padding (px)', 'bw'); ?></th>
                                <td>
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_top]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_top']); ?>" placeholder="Top" style="width:90px;" />
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_right]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_right']); ?>" placeholder="Right" style="width:90px;margin-left:8px;" />
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_bottom]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_bottom']); ?>" placeholder="Bottom" style="width:90px;margin-left:8px;" />
                                    <input type="number" min="0" max="200" name="<?php echo esc_attr(BW_HEADER_OPTION_KEY); ?>[smart_header][menu_blur_padding_left]" value="<?php echo esc_attr((int) $settings['smart_header']['menu_blur_padding_left']); ?>" placeholder="Left" style="width:90px;margin-left:8px;" />
                                    <p class="description"><?php esc_html_e('Legacy default: 10 10 10 10.', 'bw'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php submit_button(__('Save Header Settings', 'bw')); ?>
            </form>
        </div>
        <?php
    }
}
