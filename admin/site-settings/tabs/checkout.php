<?php
/**
 * Blackwork Site Settings — Checkout tab renderer.
 *
 * Extracted verbatim from class-blackwork-site-settings.php (Phase 3a, BW-TASK-20260629). Function bodies are unchanged.
 *
 * @package BW_Elementor_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Checkout customization tab.
 */
function bw_site_render_checkout_tab()
{
    $saved = false;

    if (isset($_POST['bw_checkout_settings_submit']) || isset($_POST['bw_checkout_footer_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('bw_checkout_settings_save', 'bw_checkout_settings_nonce');

        $logo = isset($_POST['bw_checkout_logo']) ? esc_url_raw(wp_unslash($_POST['bw_checkout_logo'])) : '';
        $logo_align = isset($_POST['bw_checkout_logo_align']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_logo_align'])) : 'left';
        $logo_width = isset($_POST['bw_checkout_logo_width']) ? absint($_POST['bw_checkout_logo_width']) : 200;
        $logo_padding_top = isset($_POST['bw_checkout_logo_padding_top']) ? absint($_POST['bw_checkout_logo_padding_top']) : 0;
        $logo_padding_right = isset($_POST['bw_checkout_logo_padding_right']) ? absint($_POST['bw_checkout_logo_padding_right']) : 0;
        $logo_padding_bottom = isset($_POST['bw_checkout_logo_padding_bottom']) ? absint($_POST['bw_checkout_logo_padding_bottom']) : 30;
        $logo_padding_left = isset($_POST['bw_checkout_logo_padding_left']) ? absint($_POST['bw_checkout_logo_padding_left']) : 0;
        $show_order_heading = isset($_POST['bw_checkout_show_order_heading']) ? '1' : '0';
        $page_bg = isset($_POST['bw_checkout_page_bg']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_page_bg'])) : '';
        $grid_bg = isset($_POST['bw_checkout_grid_bg']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_grid_bg'])) : '';
        $left_bg = isset($_POST['bw_checkout_left_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_left_bg_color'])) : '';
        $right_bg = isset($_POST['bw_checkout_right_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_right_bg_color'])) : '';
        $right_sticky_top = isset($_POST['bw_checkout_right_sticky_top']) ? absint($_POST['bw_checkout_right_sticky_top']) : 20;
        $right_margin_top = isset($_POST['bw_checkout_right_margin_top']) ? absint($_POST['bw_checkout_right_margin_top']) : 0;
        $right_padding_top = isset($_POST['bw_checkout_right_padding_top']) ? absint($_POST['bw_checkout_right_padding_top']) : 0;
        $right_padding_right = isset($_POST['bw_checkout_right_padding_right']) ? absint($_POST['bw_checkout_right_padding_right']) : 0;
        $right_padding_bottom = isset($_POST['bw_checkout_right_padding_bottom']) ? absint($_POST['bw_checkout_right_padding_bottom']) : 0;
        $right_padding_left = isset($_POST['bw_checkout_right_padding_left']) ? absint($_POST['bw_checkout_right_padding_left']) : 28;
        $border_color = isset($_POST['bw_checkout_border_color']) ? sanitize_hex_color(wp_unslash($_POST['bw_checkout_border_color'])) : '';
        $legal_text = isset($_POST['bw_checkout_legal_text']) ? wp_kses_post(wp_unslash($_POST['bw_checkout_legal_text'])) : '';
        $footer_copyright = isset($_POST['bw_checkout_footer_copyright_text']) ? wp_kses_post(wp_unslash($_POST['bw_checkout_footer_copyright_text'])) : '';
        $show_footer_copyright = isset($_POST['bw_checkout_show_footer_copyright']) ? '1' : '0';
        $show_return_to_shop = isset($_POST['bw_checkout_show_return_to_shop']) ? '1' : '0';
        $left_width_percent = isset($_POST['bw_checkout_left_width']) ? absint($_POST['bw_checkout_left_width']) : 62;
        $right_width_percent = isset($_POST['bw_checkout_right_width']) ? absint($_POST['bw_checkout_right_width']) : 38;
        $thumb_ratio = isset($_POST['bw_checkout_thumb_ratio']) ? sanitize_key(wp_unslash($_POST['bw_checkout_thumb_ratio'])) : 'square';
        $thumb_width = isset($_POST['bw_checkout_thumb_width']) ? absint($_POST['bw_checkout_thumb_width']) : 110;
        $footer_text = isset($_POST['bw_checkout_footer_text']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_footer_text'])) : '';
        $supabase_provision_enabled = isset($_POST['bw_supabase_checkout_provision_enabled']) ? '1' : '0';
        $supabase_invite_redirect = isset($_POST['bw_supabase_invite_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_invite_redirect_url'])) : '';
        $supabase_expired_link_redirect = isset($_POST['bw_supabase_expired_link_redirect_url']) ? esc_url_raw(wp_unslash($_POST['bw_supabase_expired_link_redirect_url'])) : '';
        $google_pay_enabled = isset($_POST['bw_google_pay_enabled']) ? 1 : 0;
        $google_pay_test_mode = isset($_POST['bw_google_pay_test_mode']) ? 1 : 0;
        $google_pay_pub_key = isset($_POST['bw_google_pay_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_publishable_key'])) : '';
        $google_pay_sec_key = isset($_POST['bw_google_pay_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_secret_key'])) : '';
        $google_pay_test_pub_key = isset($_POST['bw_google_pay_test_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_test_publishable_key'])) : '';
        $google_pay_test_sec_key = isset($_POST['bw_google_pay_test_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_test_secret_key'])) : '';
        $google_pay_statement_descriptor = isset($_POST['bw_google_pay_statement_descriptor']) ? substr(sanitize_text_field(wp_unslash($_POST['bw_google_pay_statement_descriptor'])), 0, 22) : '';
        $google_pay_webhook_secret = isset($_POST['bw_google_pay_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_webhook_secret'])) : '';
        $google_pay_test_webhook_secret = isset($_POST['bw_google_pay_test_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_google_pay_test_webhook_secret'])) : '';
        $klarna_enabled = isset($_POST['bw_klarna_enabled']) ? 1 : 0;
        $klarna_pub_key = isset($_POST['bw_klarna_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_klarna_publishable_key'])) : '';
        $klarna_sec_key = isset($_POST['bw_klarna_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_klarna_secret_key'])) : '';
        $klarna_statement_descriptor = isset($_POST['bw_klarna_statement_descriptor']) ? substr(sanitize_text_field(wp_unslash($_POST['bw_klarna_statement_descriptor'])), 0, 22) : '';
        $klarna_webhook_secret = isset($_POST['bw_klarna_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_klarna_webhook_secret'])) : '';
        $apple_pay_enabled = isset($_POST['bw_apple_pay_enabled']) ? 1 : 0;
        $apple_pay_express_helper_enabled = isset($_POST['bw_apple_pay_express_helper_enabled']) ? 1 : 0;
        $apple_pay_pub_key = isset($_POST['bw_apple_pay_publishable_key']) ? sanitize_text_field(wp_unslash($_POST['bw_apple_pay_publishable_key'])) : '';
        $apple_pay_sec_key = isset($_POST['bw_apple_pay_secret_key']) ? sanitize_text_field(wp_unslash($_POST['bw_apple_pay_secret_key'])) : '';
        $apple_pay_statement_descriptor = isset($_POST['bw_apple_pay_statement_descriptor']) ? substr(sanitize_text_field(wp_unslash($_POST['bw_apple_pay_statement_descriptor'])), 0, 22) : '';
        $apple_pay_webhook_secret = isset($_POST['bw_apple_pay_webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['bw_apple_pay_webhook_secret'])) : '';

        // Policy Settings
        $policies = [
            'refund' => 'bw_checkout_policy_refund',
            'shipping' => 'bw_checkout_policy_shipping',
            'privacy' => 'bw_checkout_policy_privacy',
            'terms' => 'bw_checkout_policy_terms',
            'contact' => 'bw_checkout_policy_contact'
        ];

        foreach ($policies as $key => $option_prefix) {
            $policy_data = isset($_POST[$option_prefix]) ? wp_unslash($_POST[$option_prefix]) : [];
            if (!is_array($policy_data)) {
                $policy_data = [];
            }
            $sanitized_data = [
                'enabled' => isset($policy_data['enabled']) ? '1' : '0',
                'title' => isset($policy_data['title']) ? sanitize_text_field($policy_data['title']) : '',
                'subtitle' => isset($policy_data['subtitle']) ? sanitize_text_field($policy_data['subtitle']) : '',
                'content' => isset($policy_data['content']) ? wp_kses_post($policy_data['content']) : '',
            ];
            update_option($option_prefix, $sanitized_data);
        }

        // Google Maps settings
        $google_maps_enabled = isset($_POST['bw_google_maps_enabled']) ? '1' : '0';
        $google_maps_api_key = isset($_POST['bw_google_maps_api_key']) ? sanitize_text_field(wp_unslash($_POST['bw_google_maps_api_key'])) : '';
        $google_maps_autofill = isset($_POST['bw_google_maps_autofill']) ? '1' : '0';
        $google_maps_restrict_country = isset($_POST['bw_google_maps_restrict_country']) ? '1' : '0';

        if (!in_array($thumb_ratio, ['square', 'portrait', 'landscape'], true)) {
            $thumb_ratio = 'square';
        }

        // Ensure thumb_width is within reasonable bounds
        if ($thumb_width < 50) {
            $thumb_width = 50;
        }
        if ($thumb_width > 300) {
            $thumb_width = 300;
        }

        $page_bg = $page_bg ?: '#ffffff';
        $grid_bg = $grid_bg ?: '#ffffff';
        $left_bg = $left_bg ?: '#ffffff';
        $right_bg = $right_bg ?: 'transparent';
        $border_color = $border_color ?: '#262626';

        if (!in_array($logo_align, ['left', 'center', 'right'], true)) {
            $logo_align = 'left';
        }

        if (function_exists('bw_mew_normalize_checkout_column_widths')) {
            $widths = bw_mew_normalize_checkout_column_widths($left_width_percent, $right_width_percent);
            $left_width_percent = $widths['left'];
            $right_width_percent = $widths['right'];
        }

        update_option('bw_checkout_logo', $logo);
        update_option('bw_checkout_logo_align', $logo_align);
        update_option('bw_checkout_logo_width', $logo_width);
        update_option('bw_checkout_logo_padding_top', $logo_padding_top);
        update_option('bw_checkout_logo_padding_right', $logo_padding_right);
        update_option('bw_checkout_logo_padding_bottom', $logo_padding_bottom);
        update_option('bw_checkout_logo_padding_left', $logo_padding_left);
        update_option('bw_checkout_show_order_heading', $show_order_heading);
        update_option('bw_checkout_page_bg', $page_bg);
        update_option('bw_checkout_grid_bg', $grid_bg);
        update_option('bw_checkout_left_bg_color', $left_bg);
        update_option('bw_checkout_right_bg_color', $right_bg);
        update_option('bw_checkout_right_sticky_top', $right_sticky_top);
        update_option('bw_checkout_right_margin_top', $right_margin_top);
        update_option('bw_checkout_right_padding_top', $right_padding_top);
        update_option('bw_checkout_right_padding_right', $right_padding_right);
        update_option('bw_checkout_right_padding_bottom', $right_padding_bottom);
        update_option('bw_checkout_right_padding_left', $right_padding_left);
        update_option('bw_checkout_border_color', $border_color);
        update_option('bw_checkout_legal_text', $legal_text);
        update_option('bw_checkout_footer_copyright_text', $footer_copyright);
        update_option('bw_checkout_show_footer_copyright', $show_footer_copyright);
        update_option('bw_checkout_show_return_to_shop', $show_return_to_shop);
        update_option('bw_checkout_left_width', $left_width_percent);
        update_option('bw_checkout_right_width', $right_width_percent);
        update_option('bw_checkout_thumb_ratio', $thumb_ratio);
        update_option('bw_checkout_thumb_width', $thumb_width);
        update_option('bw_checkout_footer_text', $footer_text);
        update_option('bw_supabase_checkout_provision_enabled', $supabase_provision_enabled);
        update_option('bw_supabase_invite_redirect_url', $supabase_invite_redirect);
        update_option('bw_supabase_expired_link_redirect_url', $supabase_expired_link_redirect);
        update_option('bw_google_pay_enabled', $google_pay_enabled);
        update_option('bw_google_pay_test_mode', $google_pay_test_mode);
        update_option('bw_google_pay_publishable_key', $google_pay_pub_key);
        update_option('bw_google_pay_secret_key', $google_pay_sec_key);
        update_option('bw_google_pay_test_publishable_key', $google_pay_test_pub_key);
        update_option('bw_google_pay_test_secret_key', $google_pay_test_sec_key);
        update_option('bw_google_pay_statement_descriptor', $google_pay_statement_descriptor);
        update_option('bw_google_pay_webhook_secret', $google_pay_webhook_secret);
        update_option('bw_google_pay_test_webhook_secret', $google_pay_test_webhook_secret);
        update_option('bw_klarna_enabled', $klarna_enabled);
        update_option('bw_klarna_publishable_key', $klarna_pub_key);
        update_option('bw_klarna_secret_key', $klarna_sec_key);
        update_option('bw_klarna_statement_descriptor', $klarna_statement_descriptor);
        update_option('bw_klarna_webhook_secret', $klarna_webhook_secret);
        update_option('bw_apple_pay_enabled', $apple_pay_enabled);
        update_option('bw_apple_pay_express_helper_enabled', $apple_pay_express_helper_enabled);
        update_option('bw_apple_pay_publishable_key', $apple_pay_pub_key);
        update_option('bw_apple_pay_secret_key', $apple_pay_sec_key);
        update_option('bw_apple_pay_statement_descriptor', $apple_pay_statement_descriptor);
        update_option('bw_apple_pay_webhook_secret', $apple_pay_webhook_secret);

        // Save Google Maps settings
        update_option('bw_google_maps_enabled', $google_maps_enabled);
        update_option('bw_google_maps_api_key', $google_maps_api_key);
        update_option('bw_google_maps_autofill', $google_maps_autofill);
        update_option('bw_google_maps_restrict_country', $google_maps_restrict_country);

        // Section Headings settings
        $hide_billing_heading_val = isset($_POST['bw_checkout_hide_billing_heading']) ? '1' : '0';
        $hide_additional_heading_val = isset($_POST['bw_checkout_hide_additional_heading']) ? '1' : '0';
        $address_heading_label_val = isset($_POST['bw_checkout_address_heading_label']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_address_heading_label'])) : '';
        $free_order_message_val = isset($_POST['bw_checkout_free_order_message']) ? sanitize_textarea_field(wp_unslash($_POST['bw_checkout_free_order_message'])) : '';
        $free_order_button_text_val = isset($_POST['bw_checkout_free_order_button_text']) ? sanitize_text_field(wp_unslash($_POST['bw_checkout_free_order_button_text'])) : '';

        update_option('bw_checkout_hide_billing_heading', $hide_billing_heading_val);
        update_option('bw_checkout_hide_additional_heading', $hide_additional_heading_val);
        update_option('bw_checkout_address_heading_label', $address_heading_label_val);
        update_option('bw_checkout_free_order_message', $free_order_message_val);
        update_option('bw_checkout_free_order_button_text', $free_order_button_text_val);

        // Keep Checkout Fields section heading settings in sync with Style tab settings.
        $checkout_fields_settings = get_option('bw_checkout_fields_settings', ['version' => 1]);
        if (!is_array($checkout_fields_settings)) {
            $checkout_fields_settings = ['version' => 1];
        }
        if (empty($checkout_fields_settings['version'])) {
            $checkout_fields_settings['version'] = 1;
        }
        $checkout_fields_settings['section_headings'] = [
            'hide_billing_details' => '1' === $hide_billing_heading_val ? 1 : 0,
            'hide_additional_info' => '1' === $hide_additional_heading_val ? 1 : 0,
            'address_heading_text' => '' !== $address_heading_label_val ? $address_heading_label_val : __('Delivery', 'bw'),
        ];
        update_option('bw_checkout_fields_settings', $checkout_fields_settings);

        // Redirect to the same tab to prevent losing tab state.
        $allowed_checkout_tabs = ['style', 'supabase', 'fields', 'subscribe', 'google-maps', 'google-pay', 'klarna-pay', 'apple-pay', 'footer'];
        $checkout_tab = isset($_GET['checkout_tab']) ? sanitize_key(wp_unslash($_GET['checkout_tab'])) : 'style';
        if (!in_array($checkout_tab, $allowed_checkout_tabs, true)) {
            $checkout_tab = 'style';
        }
        wp_safe_redirect(add_query_arg(array(
            'page' => 'blackwork-site-settings',
            'tab' => 'checkout',
            'checkout_tab' => $checkout_tab,
            'saved' => '1'
        ), admin_url('admin.php')));
        exit;
    }

    $saved = isset($_GET['saved']) && '1' === sanitize_key(wp_unslash($_GET['saved']));

    $logo = get_option('bw_checkout_logo', '');
    $logo_align = get_option('bw_checkout_logo_align', 'left');
    if (!in_array($logo_align, ['left', 'center', 'right'], true)) {
        $logo_align = 'left';
    }
    $logo_width = get_option('bw_checkout_logo_width', 200);
    $logo_padding_top = get_option('bw_checkout_logo_padding_top', 0);
    $logo_padding_right = get_option('bw_checkout_logo_padding_right', 0);
    $logo_padding_bottom = get_option('bw_checkout_logo_padding_bottom', 30);
    $logo_padding_left = get_option('bw_checkout_logo_padding_left', 0);
    $show_order_heading = get_option('bw_checkout_show_order_heading', '1');
    $page_bg = get_option('bw_checkout_page_bg', get_option('bw_checkout_page_bg_color', '#ffffff'));
    $grid_bg = get_option('bw_checkout_grid_bg', get_option('bw_checkout_grid_bg_color', '#ffffff'));
    $left_bg = get_option('bw_checkout_left_bg_color', '#ffffff');
    $right_bg = get_option('bw_checkout_right_bg_color', 'transparent');
    $right_sticky_top = get_option('bw_checkout_right_sticky_top', 20);
    $right_margin_top = get_option('bw_checkout_right_margin_top', 0);
    $right_padding_top = get_option('bw_checkout_right_padding_top', 0);
    $right_padding_right = get_option('bw_checkout_right_padding_right', 0);
    $right_padding_bottom = get_option('bw_checkout_right_padding_bottom', 0);
    $right_padding_left = get_option('bw_checkout_right_padding_left', 28);
    $footer_copyright = get_option('bw_checkout_footer_copyright_text', '');
    $show_footer_copyright = get_option('bw_checkout_show_footer_copyright', '1');
    $show_return_to_shop = get_option('bw_checkout_show_return_to_shop', '1');
    $border_color = get_option('bw_checkout_border_color', '#262626');
    $legal_text = get_option('bw_checkout_legal_text', '');
    $left_width_percent = get_option('bw_checkout_left_width', 62);
    $right_width_percent = get_option('bw_checkout_right_width', 38);
    $thumb_ratio = get_option('bw_checkout_thumb_ratio', 'square');
    $thumb_width = get_option('bw_checkout_thumb_width', 110);
    $footer_text = get_option('bw_checkout_footer_text', '');
    $supabase_provision_enabled = get_option('bw_supabase_checkout_provision_enabled', '0');
    $supabase_invite_redirect = get_option('bw_supabase_invite_redirect_url', '');
    $supabase_expired_link_redirect = get_option('bw_supabase_expired_link_redirect_url', '');
    $supabase_service_key = get_option('bw_supabase_service_role_key', '');
    $default_invite_redirect = home_url('/my-account/set-password/');
    $supabase_invite_redirect = $supabase_invite_redirect ? $supabase_invite_redirect : $default_invite_redirect;
    $default_expired_link_redirect = home_url('/link-expired/');
    $supabase_expired_link_redirect = $supabase_expired_link_redirect ? $supabase_expired_link_redirect : $default_expired_link_redirect;

    // Section Headings settings (fallback to checkout fields settings to keep a single source of truth).
    $checkout_fields_settings = get_option('bw_checkout_fields_settings', []);
    $checkout_section_headings = (is_array($checkout_fields_settings) && isset($checkout_fields_settings['section_headings']) && is_array($checkout_fields_settings['section_headings']))
        ? $checkout_fields_settings['section_headings']
        : [];
    $hide_billing_heading = get_option('bw_checkout_hide_billing_heading', !empty($checkout_section_headings['hide_billing_details']) ? '1' : '0');
    $hide_additional_heading = get_option('bw_checkout_hide_additional_heading', !empty($checkout_section_headings['hide_additional_info']) ? '1' : '0');
    $address_heading_label = get_option('bw_checkout_address_heading_label', isset($checkout_section_headings['address_heading_text']) ? sanitize_text_field($checkout_section_headings['address_heading_text']) : '');
    $free_order_message = get_option('bw_checkout_free_order_message', '');
    $free_order_button_text = get_option('bw_checkout_free_order_button_text', '');

    // Get Policy Data
    $policy_names = ['refund', 'shipping', 'privacy', 'terms', 'contact'];
    $policy_settings = [];
    foreach ($policy_names as $name) {
        $policy_settings[$name] = get_option("bw_checkout_policy_{$name}", [
            'enabled' => '1',
            'title' => '',
            'subtitle' => '',
            'content' => '',
        ]);
    }
    ?>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Impostazioni salvate con successo!</strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('bw_checkout_settings_save', 'bw_checkout_settings_nonce'); ?>

        <?php
        $active_checkout_tab = isset($_GET['checkout_tab']) ? sanitize_key(wp_unslash($_GET['checkout_tab'])) : 'style';
        $allowed_checkout_tabs = ['style', 'supabase', 'fields', 'subscribe', 'google-maps', 'google-pay', 'klarna-pay', 'apple-pay', 'footer'];
        if (!in_array($active_checkout_tab, $allowed_checkout_tabs, true)) {
            $active_checkout_tab = 'style';
        }

        $style_tab_url = add_query_arg('checkout_tab', 'style');
        $supabase_tab_url = add_query_arg('checkout_tab', 'supabase');
        $fields_tab_url = add_query_arg('checkout_tab', 'fields');
        $google_maps_tab_url = add_query_arg('checkout_tab', 'google-maps');
        $google_pay_tab_url = add_query_arg('checkout_tab', 'google-pay');
        $klarna_pay_tab_url = add_query_arg('checkout_tab', 'klarna-pay');
        $apple_pay_tab_url = add_query_arg('checkout_tab', 'apple-pay');
        $footer_tab_url = add_query_arg('checkout_tab', 'footer');
        ?>

        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo 'style' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($style_tab_url); ?>">
                <?php esc_html_e('Style', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'supabase' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($supabase_tab_url); ?>">
                <?php esc_html_e('Supabase Provider', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'fields' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($fields_tab_url); ?>">
                <?php esc_html_e('Checkout Fields', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'google-maps' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($google_maps_tab_url); ?>">
                <?php esc_html_e('Google Maps', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'google-pay' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($google_pay_tab_url); ?>">
                <?php esc_html_e('Google Pay', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'klarna-pay' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($klarna_pay_tab_url); ?>">
                <?php esc_html_e('Klarna Pay', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'apple-pay' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($apple_pay_tab_url); ?>">
                <?php esc_html_e('Apple Pay', 'bw'); ?>
            </a>
            <a class="nav-tab <?php echo 'footer' === $active_checkout_tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url($footer_tab_url); ?>">
                <?php esc_html_e('Footer Cleanup', 'bw'); ?>
            </a>
        </h2>

        <div class="bw-tab-panel" data-bw-tab="style" <?php echo 'style' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_logo">Logo Checkout</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_logo" name="bw_checkout_logo"
                            value="<?php echo esc_attr($logo); ?>" class="regular-text" />
                        <button type="button" class="button bw-media-upload" data-target="#bw_checkout_logo">Seleziona
                            immagine</button>
                        <p class="description">Logo mostrato sopra il layout di checkout.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_logo_align">Checkout Logo Alignment</label>
                    </th>
                    <td>
                        <select id="bw_checkout_logo_align" name="bw_checkout_logo_align">
                            <option value="left" <?php selected($logo_align, 'left'); ?>>Left</option>
                            <option value="center" <?php selected($logo_align, 'center'); ?>>Center</option>
                            <option value="right" <?php selected($logo_align, 'right'); ?>>Right</option>
                        </select>
                        <p class="description">Posizione orizzontale del logo nel checkout.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Larghezza Logo</label>
                    </th>
                    <td>
                        <input type="number" name="bw_checkout_logo_width" value="<?php echo esc_attr($logo_width); ?>"
                            min="50" max="800" style="width: 100px;" /> px
                        <p class="description">Larghezza massima del logo (default: 200px).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Padding Logo</label>
                    </th>
                    <td>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Top: <input type="number" name="bw_checkout_logo_padding_top"
                                    value="<?php echo esc_attr($logo_padding_top); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Right: <input type="number" name="bw_checkout_logo_padding_right"
                                    value="<?php echo esc_attr($logo_padding_right); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Bottom: <input type="number" name="bw_checkout_logo_padding_bottom"
                                    value="<?php echo esc_attr($logo_padding_bottom); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                Left: <input type="number" name="bw_checkout_logo_padding_left"
                                    value="<?php echo esc_attr($logo_padding_left); ?>" min="0" max="200"
                                    style="width: 70px;" /> px
                            </label>
                        </div>
                        <p class="description">Spazi intorno al logo (Top, Right, Bottom, Left).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_show_order_heading">Mostra titolo "Your order"</label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_checkout_show_order_heading" name="bw_checkout_show_order_heading"
                                value="1" <?php checked($show_order_heading, '1'); ?> />
                            <span style="font-weight: 500;">Attiva</span>
                        </label>
                        <p class="description">Mostra o nascondi il titolo "Your order" nella colonna destra.</p>
                    </td>
                </tr>
                <tr class="bw-section-break">
                    <th scope="row" colspan="2" style="padding-bottom:0;">
                        <h3 style="margin:0;">Colori di sfondo checkout</h3>
                        <p class="description" style="margin-top:6px;">Gestisci il colore della pagina e del contenitore
                            griglia per evitare stacchi visivi tra le colonne.</p>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_page_bg">Checkout Page Background</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_page_bg" name="bw_checkout_page_bg"
                            value="<?php echo esc_attr($page_bg); ?>" class="bw-color-picker"
                            data-default-color="#ffffff" />
                        <p class="description">Colore di sfondo della pagina checkout (body/wrapper).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_grid_bg">Checkout Grid Background</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_grid_bg" name="bw_checkout_grid_bg"
                            value="<?php echo esc_attr($grid_bg); ?>" class="bw-color-picker"
                            data-default-color="#ffffff" />
                        <p class="description">Colore di sfondo del contenitore griglia checkout (.bw-checkout-grid).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_left_bg_color">Background colonna sinistra</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_left_bg_color" name="bw_checkout_left_bg_color"
                            value="<?php echo esc_attr($left_bg); ?>" class="bw-color-picker"
                            data-default-color="#ffffff" />
                        <p class="description">Colore di sfondo della colonna principale con i campi checkout.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_bg_color">Background colonna destra (riepilogo)</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_right_bg_color" name="bw_checkout_right_bg_color"
                            value="<?php echo esc_attr($right_bg); ?>" class="bw-color-picker"
                            data-default-color="transparent" />
                        <p class="description">Colore di sfondo del riepilogo ordine sticky.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_sticky_top">Right Column Sticky Offset Top (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_right_sticky_top" name="bw_checkout_right_sticky_top"
                            value="<?php echo esc_attr(absint($right_sticky_top)); ?>" min="0" step="1"
                            style="width: 90px;" />
                        <p class="description">Distance from top when column becomes sticky during scroll.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_margin_top">Right Column Margin Top (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_right_margin_top" name="bw_checkout_right_margin_top"
                            value="<?php echo esc_attr(absint($right_margin_top)); ?>" min="0" step="1"
                            style="width: 90px;" />
                        <p class="description">Initial top margin to align the column with the form (e.g., 150px to lower
                            it).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_left_width">Larghezza colonna sinistra (%)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_left_width" name="bw_checkout_left_width"
                            value="<?php echo esc_attr($left_width_percent); ?>" min="10" max="90" step="1"
                            style="width: 90px;" />
                        <p class="description">Percentuale dedicata al form (default 62%).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_right_width">Larghezza colonna destra (%)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_right_width" name="bw_checkout_right_width"
                            value="<?php echo esc_attr($right_width_percent); ?>" min="10" max="90" step="1"
                            style="width: 90px;" />
                        <p class="description">Percentuale dedicata al riepilogo (default 38%). Se la somma supera il 100%,
                            verrà bilanciata automaticamente.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Padding colonna destra (px)</th>
                    <td>
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <label for="bw_checkout_right_padding_top"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Top</span>
                                <input type="number" id="bw_checkout_right_padding_top" name="bw_checkout_right_padding_top"
                                    value="<?php echo esc_attr($right_padding_top); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                            <label for="bw_checkout_right_padding_right"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Right</span>
                                <input type="number" id="bw_checkout_right_padding_right"
                                    name="bw_checkout_right_padding_right"
                                    value="<?php echo esc_attr($right_padding_right); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                            <label for="bw_checkout_right_padding_bottom"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Bottom</span>
                                <input type="number" id="bw_checkout_right_padding_bottom"
                                    name="bw_checkout_right_padding_bottom"
                                    value="<?php echo esc_attr($right_padding_bottom); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                            <label for="bw_checkout_right_padding_left"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Left</span>
                                <input type="number" id="bw_checkout_right_padding_left"
                                    name="bw_checkout_right_padding_left"
                                    value="<?php echo esc_attr($right_padding_left); ?>" min="0" max="200"
                                    style="width: 80px;" />
                            </label>
                        </div>
                        <p class="description">Imposta il padding della colonna destra (riepilogo ordine) su desktop e
                            mobile.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_thumb_ratio">Order Item Thumbnail Format (Nails)</label>
                    </th>
                    <td>
                        <select id="bw_checkout_thumb_ratio" name="bw_checkout_thumb_ratio">
                            <option value="square" <?php selected($thumb_ratio, 'square'); ?>>Square (1:1)</option>
                            <option value="portrait" <?php selected($thumb_ratio, 'portrait'); ?>>Portrait (2:3)</option>
                            <option value="landscape" <?php selected($thumb_ratio, 'landscape'); ?>>Landscape (3:2)
                            </option>
                        </select>
                        <p class="description">Formato proporzioni miniature prodotto nel riepilogo ordine (consigliato per
                            immagini "nails").</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_thumb_width">Tab Nails Width (px)</label>
                    </th>
                    <td>
                        <input type="number" id="bw_checkout_thumb_width" name="bw_checkout_thumb_width"
                            value="<?php echo esc_attr($thumb_width); ?>" min="50" max="300" step="1"
                            style="width: 90px;" />
                        <span style="margin-left: 5px;">px</span>
                        <p class="description">Larghezza delle miniature prodotto nel checkout (min: 50px, max: 300px,
                            default: 110px). Le immagini vengono ridimensionate automaticamente mantenendo la qualità.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_border_color">Colore bordi centrali / separatore</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_border_color" name="bw_checkout_border_color"
                            value="<?php echo esc_attr($border_color); ?>" class="bw-color-picker"
                            data-default-color="#262626" />
                        <p class="description">Colore del bordo verticale tra le due colonne.</p>
                    </td>
                </tr>
                <tr class="bw-section-break">
                    <th scope="row" colspan="2" style="padding-bottom:0;">
                        <h3 style="margin:0;">Section Headings</h3>
                        <p class="description" style="margin-top:6px;">Configure checkout section headings and free order
                            behavior.</p>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_hide_billing_heading">Hide Billing Details heading</label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_checkout_hide_billing_heading"
                                name="bw_checkout_hide_billing_heading" value="1" <?php checked('1', $hide_billing_heading); ?> />
                            <span style="font-weight: 500;">Remove the default WooCommerce "Billing details" heading.</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_hide_additional_heading">Hide Additional information heading</label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_checkout_hide_additional_heading"
                                name="bw_checkout_hide_additional_heading" value="1" <?php checked('1', $hide_additional_heading); ?> />
                            <span style="font-weight: 500;">Remove the default "Additional information" heading above order
                                notes.</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_address_heading_label">Address section heading label</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_address_heading_label" name="bw_checkout_address_heading_label"
                            value="<?php echo esc_attr($address_heading_label); ?>" class="regular-text"
                            placeholder="Delivery" />
                        <p class="description">Suggested: Delivery / Address / Shipping address.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_free_order_message">Free Order Message</label>
                    </th>
                    <td>
                        <textarea id="bw_checkout_free_order_message" name="bw_checkout_free_order_message" rows="3"
                            class="large-text"
                            placeholder="Your order is free. Complete your details and click Place order."><?php echo esc_textarea($free_order_message); ?></textarea>
                        <p class="description">Shown when order total becomes 0 (e.g., after applying a 100% discount
                            coupon). Stripe express buttons and divider will be hidden.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_free_order_button_text">Free Order Button Text</label>
                    </th>
                    <td>
                        <input type="text" id="bw_checkout_free_order_button_text" name="bw_checkout_free_order_button_text"
                            value="<?php echo esc_attr($free_order_button_text); ?>" class="regular-text"
                            placeholder="Confirm free order" />
                        <p class="description">Text for the Place Order button when order total is 0. Original button text
                            is restored when total becomes greater than 0.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="supabase" <?php echo 'supabase' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            // Check if Supabase keys are configured
            $supabase_project_url_check = get_option('bw_supabase_project_url', '');
            $supabase_configured = !empty($supabase_service_key) && !empty($supabase_project_url_check);
            ?>
            <div class="notice notice-info inline" style="margin: 15px 0;">
                <p>
                    <strong><?php esc_html_e('Supabase API Keys:', 'bw'); ?></strong>
                    <?php if ($supabase_configured): ?>
                        <span style="color: #00a32a;">&#10003; <?php esc_html_e('Configured', 'bw'); ?></span>
                    <?php else: ?>
                        <span style="color: #d63638;">&#10007; <?php esc_html_e('Not configured', 'bw'); ?></span>
                    <?php endif; ?>
                    &mdash;
                    <?php
                    printf(
                        /* translators: %s: link to Account Page settings */
                        esc_html__('Supabase Project URL, Anon Key, and Service Role Key are configured in %s.', 'bw'),
                        '<a href="' . esc_url(admin_url('admin.php?page=blackwork-site-settings&tab=account-page')) . '">' . esc_html__('Account Page > Technical Settings', 'bw') . '</a>'
                    );
                    ?>
                </p>
            </div>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="bw_supabase_checkout_provision_enabled"><?php esc_html_e('Supabase checkout provisioning', 'bw'); ?></label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_supabase_checkout_provision_enabled"
                                name="bw_supabase_checkout_provision_enabled" value="1" <?php checked($supabase_provision_enabled, '1'); ?> />
                            <span
                                style="font-weight: 500;"><?php esc_html_e('Invite Supabase users after guest checkout', 'bw'); ?></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, guest orders trigger a Supabase invite email that leads users to set their password.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="bw_supabase_invite_redirect_url"><?php esc_html_e('Supabase invite redirect URL', 'bw'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="bw_supabase_invite_redirect_url" name="bw_supabase_invite_redirect_url"
                            value="<?php echo esc_attr($supabase_invite_redirect); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('URL where Supabase directs users after the invite link (default: /my-account/set-password/). The URL must be allowlisted in Supabase Redirect URLs.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="bw_supabase_expired_link_redirect_url"><?php esc_html_e('Supabase expired link redirect URL', 'bw'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="bw_supabase_expired_link_redirect_url" name="bw_supabase_expired_link_redirect_url"
                            value="<?php echo esc_attr($supabase_expired_link_redirect); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('URL where users are redirected when invite links are invalid or expired (e.g. otp_expired). Default: /link-expired/.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
                <?php if ('1' === $supabase_provision_enabled && !$supabase_service_key): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Supabase provisioning warning', 'bw'); ?></th>
                        <td>
                            <div class="notice notice-warning inline">
                                <p><?php esc_html_e('Provisioning is enabled but Supabase Service Role Key is missing. Invites will not be sent.', 'bw'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="bw-tab-panel" data-bw-tab="fields" <?php echo 'fields' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php if (class_exists('BW_Checkout_Fields_Admin')): ?>
                <?php BW_Checkout_Fields_Admin::get_instance()->render_tab(); ?>
            <?php else: ?>
                <p><?php esc_html_e('Checkout Fields module is unavailable.', 'bw'); ?></p>
            <?php endif; ?>
        </div>

        <div class="bw-tab-panel" data-bw-tab="subscribe" <?php echo 'subscribe' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $mail_marketing_checkout_url = add_query_arg(
                [
                    'page' => 'blackwork-mail-marketing',
                    'tab' => 'checkout',
                ],
                admin_url('admin.php')
            );
            ?>
            <div class="notice notice-info inline">
                <p>
                    <strong><?php esc_html_e('Subscribe settings moved.', 'bw'); ?></strong>
                    <?php
                    printf(
                        /* translators: %s: link to Mail Marketing page */
                        esc_html__('Manage newsletter settings in %s.', 'bw'),
                        '<a href="' . esc_url($mail_marketing_checkout_url) . '">' . esc_html__('Blackwork Site > Mail Marketing > Checkout', 'bw') . '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="bw-tab-panel" data-bw-tab="google-maps" <?php echo 'google-maps' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            // Get Google Maps settings
            $google_maps_enabled = get_option('bw_google_maps_enabled', '0');
            $google_maps_api_key = get_option('bw_google_maps_api_key', '');
            $google_maps_autofill = get_option('bw_google_maps_autofill', '1');
            $google_maps_restrict_country = get_option('bw_google_maps_restrict_country', '1');
            $google_maps_test_nonce = wp_create_nonce('bw_google_maps_test_connection');
            ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label
                            for="bw_google_maps_enabled"><?php esc_html_e('Enable Address Autocomplete', 'bw'); ?></label>
                    </th>
                    <td>
                        <label style="display: inline-flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="bw_google_maps_enabled" name="bw_google_maps_enabled" value="1" <?php checked($google_maps_enabled, '1'); ?> />
                            <span style="font-weight: 500;"><?php esc_html_e('Active', 'bw'); ?></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Enable Google Places API to suggest addresses as users type in the checkout form.', 'bw'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <div id="bw-google-maps-conditional-fields"
                style="<?php echo '1' === $google_maps_enabled ? '' : 'display:none;'; ?>">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="bw_google_maps_api_key"><?php esc_html_e('Google Maps API Key', 'bw'); ?>
                                *</label>
                        </th>
                        <td>
                            <input type="text" id="bw_google_maps_api_key" name="bw_google_maps_api_key"
                                value="<?php echo esc_attr($google_maps_api_key); ?>" class="regular-text"
                                placeholder="AIzaSyB..." />
                            <p class="description">
                                <?php
                                echo sprintf(
                                    /* translators: %s: URL to Google Cloud Console */
                                    esc_html__('Create an API key at %s and enable the Places API.', 'bw'),
                                    '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
                                );
                                ?>
                                <br>
                                <strong><?php esc_html_e('Free tier: $200/month (~70,000 autocomplete requests)', 'bw'); ?></strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="bw_google_maps_test_connection"><?php esc_html_e('API Connection Test', 'bw'); ?></label>
                        </th>
                        <td>
                            <button type="button" id="bw_google_maps_test_connection" class="button button-secondary">
                                <?php esc_html_e('Test Google Maps Connection', 'bw'); ?>
                            </button>
                            <p id="bw_google_maps_test_result" class="description" style="margin-top: 10px; display:none;"></p>
                            <p class="description" style="margin-top: 10px;">
                                <strong><?php esc_html_e('Monitor API usage & costs:', 'bw'); ?></strong><br>
                                <a href="https://console.cloud.google.com/apis/api/maps-backend.googleapis.com/quotas"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('Maps JavaScript API Quotas', 'bw'); ?></a> |
                                <a href="https://console.cloud.google.com/apis/api/places-backend.googleapis.com/quotas"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('Places API Quotas', 'bw'); ?></a> |
                                <a href="https://console.cloud.google.com/apis/dashboard"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('API Metrics Dashboard', 'bw'); ?></a> |
                                <a href="https://console.cloud.google.com/billing/budgets"
                                    target="_blank" rel="noopener noreferrer"><?php esc_html_e('Billing Budgets & Alerts', 'bw'); ?></a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label
                                for="bw_google_maps_autofill"><?php esc_html_e('Auto-fill City & Postcode', 'bw'); ?></label>
                        </th>
                        <td>
                            <label style="display: inline-flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="bw_google_maps_autofill" name="bw_google_maps_autofill" value="1"
                                    <?php checked($google_maps_autofill, '1'); ?> />
                                <span style="font-weight: 500;"><?php esc_html_e('Active', 'bw'); ?></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When user selects an address, automatically fill City and Postal Code fields.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label
                                for="bw_google_maps_restrict_country"><?php esc_html_e('Restrict to Selected Country', 'bw'); ?></label>
                        </th>
                        <td>
                            <label style="display: inline-flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="bw_google_maps_restrict_country"
                                    name="bw_google_maps_restrict_country" value="1" <?php checked($google_maps_restrict_country, '1'); ?> />
                                <span style="font-weight: 500;"><?php esc_html_e('Active (Recommended)', 'bw'); ?></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Search addresses ONLY in the country selected in the "Country/Region" dropdown. Improves search accuracy and relevance.', 'bw'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr class="bw-section-break">
                        <th scope="row" colspan="2" style="padding-bottom:0;">
                            <h3 style="margin:0;"><?php esc_html_e('Privacy & GDPR', 'bw'); ?></h3>
                            <p class="description" style="margin-top:6px;">
                                <?php esc_html_e('Google Places API may track user searches. Add a notice in your Privacy Policy.', 'bw'); ?>
                            </p>
                        </th>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <div
                                style="background: #f0f6fc; border-left: 4px solid #0969da; padding: 16px; margin-top: 10px;">
                                <p style="margin: 0 0 10px 0; font-weight: 600;">
                                    ℹ️ <?php esc_html_e('How to get your Google Maps API Key:', 'bw'); ?>
                                </p>
                                <ol style="margin: 0; padding-left: 20px;">
                                    <li><?php esc_html_e('Go to', 'bw'); ?> <a href="https://console.cloud.google.com/"
                                            target="_blank">Google Cloud Console</a></li>
                                    <li><?php esc_html_e('Create a new project (or select an existing one)', 'bw'); ?>
                                    </li>
                                    <li><?php esc_html_e('Enable "Places API" in APIs & Services', 'bw'); ?></li>
                                    <li><?php esc_html_e('Go to Credentials → Create Credentials → API Key', 'bw'); ?>
                                    </li>
                                    <li><?php esc_html_e('Restrict the key to your domain and Places API only', 'bw'); ?>
                                    </li>
                                    <li><?php esc_html_e('Paste the key above and save settings', 'bw'); ?></li>
                                </ol>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    var $testButton = $('#bw_google_maps_test_connection');
                    var $testResult = $('#bw_google_maps_test_result');
                    var defaultButtonText = $testButton.text();

                    function setTestMessage(type, message, details) {
                        var color = '#0a7a2f';
                        var bg = '#ecfdf3';
                        var border = '#9ee7b3';

                        if (type === 'error') {
                            color = '#a90000';
                            bg = '#fff1f1';
                            border = '#f0b2b2';
                        }

                        var text = message || '';
                        if (details) {
                            text += ' ' + details;
                        }

                        $testResult
                            .text(text)
                            .css({
                                display: 'block',
                                color: color,
                                background: bg,
                                border: '1px solid ' + border,
                                borderRadius: '6px',
                                padding: '10px 12px',
                                fontWeight: 500
                            });
                    }

                    // Toggle conditional fields
                    $('#bw_google_maps_enabled').on('change', function () {
                        if ($(this).is(':checked')) {
                            $('#bw-google-maps-conditional-fields').slideDown(200);
                        } else {
                            $('#bw-google-maps-conditional-fields').slideUp(200);
                        }
                    });

                    $testButton.on('click', function () {
                        var apiKey = ($('#bw_google_maps_api_key').val() || '').trim();

                        if (!apiKey) {
                            setTestMessage('error', '<?php echo esc_js(__('Insert a Google Maps API key first.', 'bw')); ?>');
                            return;
                        }

                        $testButton.prop('disabled', true).text('<?php echo esc_js(__('Testing…', 'bw')); ?>');
                        $testResult.hide().text('');

                        $.post(ajaxurl, {
                            action: 'bw_google_maps_test_connection',
                            nonce: '<?php echo esc_js($google_maps_test_nonce); ?>',
                            api_key: apiKey
                        }).done(function (response) {
                            if (response && response.success && response.data) {
                                setTestMessage('success', response.data.message || '<?php echo esc_js(__('Connection successful.', 'bw')); ?>', response.data.details || '');
                                return;
                            }

                            var data = response && response.data ? response.data : {};
                            setTestMessage('error', data.message || '<?php echo esc_js(__('Connection test failed.', 'bw')); ?>', data.details || '');
                        }).fail(function () {
                            setTestMessage('error', '<?php echo esc_js(__('Unable to run connection test. Please try again.', 'bw')); ?>');
                        }).always(function () {
                            $testButton.prop('disabled', false).text(defaultButtonText);
                        });
                    });
                });
            </script>
        </div>

        <div class="bw-tab-panel" data-bw-tab="google-pay" <?php echo 'google-pay' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $google_pay_enabled = get_option('bw_google_pay_enabled', 0);
            $google_pay_test_mode = get_option('bw_google_pay_test_mode', 0);
            $google_pay_pub_key = get_option('bw_google_pay_publishable_key', '');
            $google_pay_sec_key = get_option('bw_google_pay_secret_key', '');
            $google_pay_test_pub_key = get_option('bw_google_pay_test_publishable_key', '');
            $google_pay_test_sec_key = get_option('bw_google_pay_test_secret_key', '');
            $google_pay_statement_descriptor = get_option('bw_google_pay_statement_descriptor', '');
            $google_pay_webhook_secret = get_option('bw_google_pay_webhook_secret', '');
            $google_pay_test_webhook_secret = get_option('bw_google_pay_test_webhook_secret', '');
            $google_pay_webhook_url = add_query_arg('wc-api', 'bw_google_pay', home_url('/'));
            ?>

            <div class="bw-settings-section">
                <h2 class="title">Google Pay (Stripe Integration)</h2>
                <p class="description">Configura Google Pay tramite Stripe per il checkout personalizzato. Nota: Google Pay
                    richiede HTTPS attivo e dominio verificato su Stripe.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Abilita Gateway</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_google_pay_enabled" type="checkbox" id="bw_google_pay_enabled" value="1" <?php checked(1, $google_pay_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Mode</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_google_pay_test_mode" type="checkbox" id="bw_google_pay_test_mode" value="1"
                                    <?php checked(1, $google_pay_test_mode); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Live Publishable Key</th>
                        <td>
                            <input name="bw_google_pay_publishable_key" type="text" id="bw_google_pay_publishable_key"
                                value="<?php echo esc_attr($google_pay_pub_key); ?>" class="regular-text" placeholder="pk_live_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Secret Key</th>
                        <td>
                            <input name="bw_google_pay_secret_key" type="password" id="bw_google_pay_secret_key"
                                value="<?php echo esc_attr($google_pay_sec_key); ?>" class="regular-text" placeholder="sk_live_..." />
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Test Publishable Key</th>
                        <td>
                            <input name="bw_google_pay_test_publishable_key" type="text" id="bw_google_pay_test_publishable_key"
                                value="<?php echo esc_attr($google_pay_test_pub_key); ?>" class="regular-text" placeholder="pk_test_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Secret Key</th>
                        <td>
                            <input name="bw_google_pay_test_secret_key" type="password" id="bw_google_pay_test_secret_key"
                                value="<?php echo esc_attr($google_pay_test_sec_key); ?>" class="regular-text" placeholder="sk_test_..." />
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Verifica connessione (globale)</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <span id="bw-google-pay-mode-pill" class="bw-google-pay-mode-pill">Modalita attiva: TEST</span>
                                <button type="button" class="button" id="bw-google-pay-test-connection">Verifica connessione (TEST)</button>
                            </div>
                            <span id="bw-google-pay-test-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">Questo controllo usa la modalità attiva: <strong>Test Mode ON = chiavi test</strong>, <strong>Test Mode OFF = chiavi live</strong>.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Statement Descriptor</th>
                        <td>
                            <input name="bw_google_pay_statement_descriptor" type="text" id="bw_google_pay_statement_descriptor"
                                value="<?php echo esc_attr($google_pay_statement_descriptor); ?>" class="regular-text" placeholder="BlackWork Store" maxlength="22" />
                            <p class="description">Testo visualizzato nell'estratto conto del cliente (max 22 caratteri). Lascia vuoto per usare il default dell'account Stripe.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code><?php echo esc_url($google_pay_webhook_url); ?></code>
                            <p class="description">Inserisci questo URL nella Dashboard Stripe → Sviluppatori → Webhook → Aggiungi endpoint. Abilita gli eventi: <strong>payment_intent.succeeded</strong>, <strong>payment_intent.payment_failed</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Webhook Secret</th>
                        <td>
                            <input name="bw_google_pay_webhook_secret" type="password" id="bw_google_pay_webhook_secret"
                                value="<?php echo esc_attr($google_pay_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">La chiave di firma del webhook live (inizia con <code>whsec_</code>). Trovala nella Dashboard Stripe → Webhook → dettaglio endpoint.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Webhook Secret</th>
                        <td>
                            <input name="bw_google_pay_test_webhook_secret" type="password" id="bw_google_pay_test_webhook_secret"
                                value="<?php echo esc_attr($google_pay_test_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">La chiave di firma del webhook di test.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <style>
                .bw-settings-divider hr {
                    border: 0;
                    border-top: 1px solid #ddd;
                    margin: 10px 0;
                }

                .bw-settings-section {
                    padding: 20px;
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                }

                .bw-google-pay-test-result {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: 8px;
                    font-weight: 600;
                }

                .bw-google-pay-connection-row {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .bw-google-pay-mode-pill {
                    display: inline-flex;
                    align-items: center;
                    padding: 4px 10px;
                    border-radius: 999px;
                    font-size: 12px;
                    font-weight: 700;
                    letter-spacing: 0.2px;
                    text-transform: uppercase;
                    background: #fef3c7;
                    color: #92400e;
                    border: 1px solid #f59e0b;
                }

                .bw-google-pay-mode-pill.is-live {
                    background: #ecfdf3;
                    color: #166534;
                    border-color: #22c55e;
                }

                .bw-google-pay-test-result::before {
                    content: "";
                    width: 9px;
                    height: 9px;
                    border-radius: 50%;
                    background: #b8c0cc;
                }

                .bw-google-pay-test-result.is-success {
                    color: #0a7d33;
                }

                .bw-google-pay-test-result.is-success::before {
                    background: #0a7d33;
                }

                .bw-google-pay-test-result.is-error {
                    color: #b42318;
                }

                .bw-google-pay-test-result.is-error::before {
                    background: #b42318;
                }

                .bw-google-pay-test-result.is-testing {
                    color: #475467;
                }
            </style>
        </div>

        <div class="bw-tab-panel" data-bw-tab="klarna-pay" <?php echo 'klarna-pay' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $klarna_enabled = get_option('bw_klarna_enabled', 0);
            $klarna_pub_key = get_option('bw_klarna_publishable_key', '');
            $klarna_sec_key = get_option('bw_klarna_secret_key', '');
            $klarna_statement_descriptor = get_option('bw_klarna_statement_descriptor', '');
            $klarna_webhook_secret = get_option('bw_klarna_webhook_secret', '');
            $klarna_webhook_url = add_query_arg('wc-api', 'bw_klarna', home_url('/'));
            ?>

            <div class="bw-settings-section">
                <h2 class="title">Klarna Pay (Stripe Integration)</h2>
                <p class="description">Configure Klarna Flexible Payments via Stripe for the custom checkout. Klarna requires HTTPS and supported buyer country/currency in Stripe.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Enable Gateway</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_klarna_enabled" type="checkbox" id="bw_klarna_enabled" value="1" <?php checked(1, $klarna_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Live Publishable Key</th>
                        <td>
                            <input name="bw_klarna_publishable_key" type="text" id="bw_klarna_publishable_key"
                                value="<?php echo esc_attr($klarna_pub_key); ?>" class="regular-text" placeholder="pk_live_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Secret Key</th>
                        <td>
                            <input name="bw_klarna_secret_key" type="password" id="bw_klarna_secret_key"
                                value="<?php echo esc_attr($klarna_sec_key); ?>" class="regular-text" placeholder="sk_live_..." />
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Connection Check (Global)</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <span id="bw-klarna-mode-pill" class="bw-google-pay-mode-pill is-live">ACTIVE MODE: LIVE</span>
                                <button type="button" class="button" id="bw-klarna-test-connection">Verify connection (LIVE)</button>
                            </div>
                            <span id="bw-klarna-test-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">This check always validates <strong>live keys</strong> for Klarna.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Statement Descriptor</th>
                        <td>
                            <input name="bw_klarna_statement_descriptor" type="text" id="bw_klarna_statement_descriptor"
                                value="<?php echo esc_attr($klarna_statement_descriptor); ?>" class="regular-text" placeholder="BlackWork Store" maxlength="22" />
                            <p class="description">Text shown on the customer statement (max 22 chars). Leave empty to use Stripe account default.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code><?php echo esc_url($klarna_webhook_url); ?></code>
                            <p class="description">Add this endpoint in Stripe Dashboard → Developers → Webhooks. Enable: <strong>payment_intent.succeeded</strong>, <strong>payment_intent.payment_failed</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Webhook Secret</th>
                        <td>
                            <input name="bw_klarna_webhook_secret" type="password" id="bw_klarna_webhook_secret"
                                value="<?php echo esc_attr($klarna_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">Webhook signing secret for live endpoint (starts with <code>whsec_</code>).</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bw-tab-panel" data-bw-tab="apple-pay" <?php echo 'apple-pay' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <?php
            $apple_pay_enabled = get_option('bw_apple_pay_enabled', 0);
            $apple_pay_express_helper_enabled = get_option('bw_apple_pay_express_helper_enabled', 1);
            $apple_pay_pub_key = get_option('bw_apple_pay_publishable_key', '');
            $apple_pay_sec_key = get_option('bw_apple_pay_secret_key', '');
            $apple_pay_statement_descriptor = get_option('bw_apple_pay_statement_descriptor', '');
            $apple_pay_webhook_secret = get_option('bw_apple_pay_webhook_secret', '');
            $apple_pay_webhook_url = add_query_arg('wc-api', 'bw_apple_pay', home_url('/'));
            $apple_pay_site_domain = wp_parse_url(home_url('/'), PHP_URL_HOST);
            $apple_pay_site_domain = is_string($apple_pay_site_domain) ? strtolower(trim($apple_pay_site_domain)) : '';
            ?>

            <div class="bw-settings-section">
                <h2 class="title">Apple Pay (Stripe Integration)</h2>
                <p class="description">Configure Apple Pay via Stripe for the custom checkout. Apple Pay requires HTTPS and domain verification in Stripe.</p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Enable Gateway</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_apple_pay_enabled" type="checkbox" id="bw_apple_pay_enabled" value="1" <?php checked(1, $apple_pay_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Live Publishable Key</th>
                        <td>
                            <input name="bw_apple_pay_publishable_key" type="password" id="bw_apple_pay_publishable_key"
                                value="<?php echo esc_attr($apple_pay_pub_key); ?>" class="regular-text" placeholder="pk_live_..." />
                            <p class="description">Optional override. Leave empty to use global live keys already configured in BlackWork.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Secret Key</th>
                        <td>
                            <input name="bw_apple_pay_secret_key" type="password" id="bw_apple_pay_secret_key"
                                value="<?php echo esc_attr($apple_pay_sec_key); ?>" class="regular-text" placeholder="sk_live_..." />
                            <p class="description">Optional override. Leave empty to use global live keys already configured in BlackWork.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Express Checkout Helper</th>
                        <td>
                            <label class="bw-switch">
                                <input name="bw_apple_pay_express_helper_enabled" type="checkbox" id="bw_apple_pay_express_helper_enabled" value="1" <?php checked(1, $apple_pay_express_helper_enabled); ?> />
                                <span class="bw-slider round"></span>
                            </label>
                            <p class="description" style="margin-top: 8px;">When Apple Pay is unavailable, use the Apple button to scroll to the Express Checkout section.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Connection Check (Global)</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <span id="bw-apple-pay-mode-pill" class="bw-google-pay-mode-pill is-live">ACTIVE MODE: LIVE</span>
                                <button type="button" class="button" id="bw-apple-pay-test-connection">Verify connection (LIVE)</button>
                            </div>
                            <span id="bw-apple-pay-test-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">This check always validates <strong>live keys</strong> for Apple Pay. Test keys are never accepted.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Domain Verification</th>
                        <td>
                            <div class="bw-google-pay-connection-row">
                                <button type="button" class="button" id="bw-apple-pay-verify-domain">Verify domain in Stripe</button>
                            </div>
                            <span id="bw-apple-pay-domain-result" class="bw-google-pay-test-result" aria-live="polite"></span>
                            <p class="description" style="margin-top: 8px;">
                                Checks Stripe Payment Method Domains for: <strong><?php echo esc_html($apple_pay_site_domain); ?></strong>.
                                If not verified/enabled, Apple Pay will not be available in checkout.
                                <br />
                                Verify/manage domains in Stripe:
                                <a href="https://dashboard.stripe.com/settings/payment_methods" target="_blank" rel="noopener noreferrer">Stripe Dashboard → Settings → Payment Methods</a>.
                            </p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Statement Descriptor</th>
                        <td>
                            <input name="bw_apple_pay_statement_descriptor" type="text" id="bw_apple_pay_statement_descriptor"
                                value="<?php echo esc_attr($apple_pay_statement_descriptor); ?>" class="regular-text" placeholder="BlackWork Store" maxlength="22" />
                            <p class="description">Text shown on the customer statement (max 22 chars). Leave empty to use Stripe account default.</p>
                        </td>
                    </tr>

                    <tr class="bw-settings-divider">
                        <td colspan="2"><hr></td>
                    </tr>

                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code><?php echo esc_url($apple_pay_webhook_url); ?></code>
                            <p class="description">Add this endpoint in Stripe Dashboard → Developers → Webhooks. Enable: <strong>payment_intent.succeeded</strong>, <strong>payment_intent.payment_failed</strong>, <strong>payment_intent.canceled</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Live Webhook Secret</th>
                        <td>
                            <input name="bw_apple_pay_webhook_secret" type="password" id="bw_apple_pay_webhook_secret"
                                value="<?php echo esc_attr($apple_pay_webhook_secret); ?>" class="regular-text" placeholder="whsec_..." />
                            <p class="description">Webhook signing secret for the live endpoint (starts with <code>whsec_</code>).</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bw-tab-panel" data-bw-tab="footer" <?php echo 'footer' === $active_checkout_tab ? '' : 'style="display:none;"'; ?>>
            <div class="bw-settings-header" style="margin-bottom: 25px;">
                <h2><?php esc_html_e('Checkout Footer Cleanup', 'bw'); ?></h2>
                <p><?php esc_html_e('Manage the policy links and content shown at the bottom of the checkout page.', 'bw'); ?>
                </p>
            </div>

            <table class="form-table" role="presentation" style="margin-bottom: 30px;">
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_legal_text">Testo informativo legale</label>
                    </th>
                    <td>
                        <textarea id="bw_checkout_legal_text" name="bw_checkout_legal_text" rows="4"
                            class="large-text"><?php echo esc_textarea($legal_text); ?></textarea>
                        <p class="description">Testo mostrato sotto i metodi di pagamento; supporta link e HTML consentito.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="bw_checkout_show_footer_copyright"><?php esc_html_e('Show Footer Copyright', 'bw'); ?></label>
                    </th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_checkout_show_footer_copyright"
                                name="bw_checkout_show_footer_copyright" value="1" <?php checked('1', $show_footer_copyright); ?> />
                            <span class="description">Mostra o nascondi il testo di copyright nel footer.</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bw_checkout_footer_copyright_text">Text of Footer Copyright</label>
                    </th>
                    <td>
                        <textarea id="bw_checkout_footer_copyright_text" name="bw_checkout_footer_copyright_text" rows="2"
                            class="large-text"><?php echo esc_textarea($footer_copyright); ?></textarea>
                        <p class="description">Testo mostrato nel footer della colonna sinistra; viene preceduto da
                            "Copyright © {anno},".</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mostra link "Return to shop"</th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="bw_checkout_show_return_to_shop"
                                name="bw_checkout_show_return_to_shop" value="1" <?php checked('1', $show_return_to_shop); ?> />
                            <span class="description">Attiva o disattiva il link di ritorno allo shop nel footer della
                                colonna sinistra.</span>
                        </label>
                    </td>
                </tr>
            </table>

            <h3 style="margin-bottom: 20px;"><?php esc_html_e('Policy Sections (Popups)', 'bw'); ?></h3>

            <?php foreach ($policy_settings as $key => $data): ?>
                <?php $policy_enabled = !isset($data['enabled']) || '1' === (string) $data['enabled']; ?>
                <div class="bw-policy-section"
                    style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 15px; text-transform: capitalize;">
                        <?php echo esc_html($key); ?> Policy
                    </h3>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Enabled', 'bw'); ?></label></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="bw_checkout_policy_<?php echo esc_attr($key); ?>[enabled]"
                                        class="bw-policy-enabled-toggle"
                                        data-policy-key="<?php echo esc_attr($key); ?>"
                                        value="1" <?php checked('1', isset($data['enabled']) ? (string) $data['enabled'] : '1'); ?> />
                                    <span class="description"><?php esc_html_e('Show this policy link and popup in checkout footer.', 'bw'); ?></span>
                                </label>
                            </td>
                        </tr>
                        <tr class="bw-policy-fields bw-policy-fields--<?php echo esc_attr($key); ?>"
                            <?php echo $policy_enabled ? '' : 'style="display:none;"'; ?>>
                            <th scope="row"><label><?php esc_html_e('Link Title', 'bw'); ?></label></th>
                            <td>
                                <input type="text" name="bw_checkout_policy_<?php echo esc_attr($key); ?>[title]"
                                    value="<?php echo esc_attr($data['title']); ?>" class="regular-text"
                                    placeholder="<?php echo esc_attr(ucfirst($key) . ' policy'); ?>" />
                            </td>
                        </tr>
                        <tr class="bw-policy-fields bw-policy-fields--<?php echo esc_attr($key); ?>"
                            <?php echo $policy_enabled ? '' : 'style="display:none;"'; ?>>
                            <th scope="row"><label><?php esc_html_e('Popup Subtitle', 'bw'); ?></label></th>
                            <td>
                                <input type="text" name="bw_checkout_policy_<?php echo esc_attr($key); ?>[subtitle]"
                                    value="<?php echo esc_attr($data['subtitle']); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Optional subtitle shown inside the popup.', 'bw'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="bw-policy-fields bw-policy-fields--<?php echo esc_attr($key); ?>"
                            <?php echo $policy_enabled ? '' : 'style="display:none;"'; ?>>
                            <th scope="row"><label><?php esc_html_e('Content', 'bw'); ?></label></th>
                            <td>
                                <?php
                                wp_editor($data['content'], "bw_checkout_policy_{$key}_content", [
                                    'textarea_name' => "bw_checkout_policy_{$key}[content]",
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                    'teeny' => false,
                                    'quicktags' => true
                                ]);
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>

            <?php submit_button('Salva Footer', 'primary', 'bw_checkout_footer_submit'); ?>
        </div>

        <?php if (in_array($active_checkout_tab, ['fields', 'subscribe', 'footer'], true)): ?>
            <?php // Buttons rendered inside module panels. ?>
        <?php else: ?>
            <?php submit_button('Salva impostazioni', 'primary', 'bw_checkout_settings_submit'); ?>
        <?php endif; ?>
    </form>

        <script>
        jQuery(document).ready(function ($) {
            function togglePolicyFields(checkbox) {
                var $checkbox = $(checkbox);
                var key = $checkbox.data('policy-key');
                if (!key) {
                    return;
                }

                var isEnabled = $checkbox.is(':checked');
                var $rows = $('.bw-policy-fields--' + key);
                if (isEnabled) {
                    $rows.stop(true, true).slideDown(150);
                } else {
                    $rows.stop(true, true).slideUp(150);
                }
            }

            $('.bw-policy-enabled-toggle').each(function () {
                togglePolicyFields(this);
            });

            $(document).on('change', '.bw-policy-enabled-toggle', function () {
                togglePolicyFields(this);
            });

            $('.bw-media-upload').on('click', function (e) {
                e.preventDefault();

                const targetInput = $(this).data('target');
                const frame = wp.media({
                    title: 'Seleziona immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });

                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url);
                });

                frame.open();
            });

            $('.bw-color-picker').wpColorPicker();
        });
    </script>
    <?php
}
