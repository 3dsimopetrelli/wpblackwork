<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_default_shop_option')) {
    function bw_tbl_default_shop_option()
    {
        return [
            'version' => 1,
            'enabled' => 0,
            'active_shop_template_id' => 0,
        ];
    }
}

if (!function_exists('bw_tbl_is_valid_shop_template')) {
    function bw_tbl_is_valid_shop_template($template_id)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return false;
        }

        $post = get_post($template_id);
        if (!$post || 'bw_template' !== $post->post_type || 'publish' !== $post->post_status) {
            return false;
        }

        $type = get_post_meta($template_id, 'bw_template_type', true);
        $type = function_exists('bw_tbl_sanitize_template_type')
            ? bw_tbl_sanitize_template_type($type)
            : sanitize_key((string) $type);

        return 'product_archive' === $type;
    }
}

if (!function_exists('bw_tbl_sanitize_shop_option')) {
    function bw_tbl_sanitize_shop_option($input)
    {
        $input = is_array($input) ? $input : [];
        $enabled = !empty($input['enabled']) ? 1 : 0;
        $active_id = isset($input['active_shop_template_id']) ? absint($input['active_shop_template_id']) : 0;

        if (!bw_tbl_is_valid_shop_template($active_id)) {
            $active_id = 0;
        }

        return [
            'version' => 1,
            'enabled' => $enabled,
            'active_shop_template_id' => $active_id,
        ];
    }
}

if (!function_exists('bw_tbl_get_shop_option')) {
    function bw_tbl_get_shop_option()
    {
        $saved = get_option(BW_TBL_SHOP_OPTION, []);
        $saved = is_array($saved) ? $saved : [];

        return array_replace(bw_tbl_default_shop_option(), bw_tbl_sanitize_shop_option($saved));
    }
}

if (!function_exists('bw_tbl_get_shop_template_choices')) {
    function bw_tbl_get_shop_template_choices()
    {
        if (function_exists('bw_tbl_get_product_archive_template_choices')) {
            return bw_tbl_get_product_archive_template_choices();
        }

        return [];
    }
}

if (!function_exists('bw_tbl_resolve_active_shop_template_id')) {
    function bw_tbl_resolve_active_shop_template_id()
    {
        $shop_option = bw_tbl_get_shop_option();
        if (empty($shop_option['enabled'])) {
            return 0;
        }

        $active_id = isset($shop_option['active_shop_template_id']) ? absint($shop_option['active_shop_template_id']) : 0;
        if (!bw_tbl_is_valid_shop_template($active_id)) {
            return 0;
        }

        return $active_id;
    }
}

if (!function_exists('bw_tbl_runtime_resolve_shop_settings_winner')) {
    function bw_tbl_runtime_resolve_shop_settings_winner($context = [])
    {
        $kind = isset($context['product_archive_kind']) ? sanitize_key((string) $context['product_archive_kind']) : '';
        if ('shop' !== $kind) {
            return [
                'handled' => false,
                'winner_id' => 0,
            ];
        }

        $shop_option = bw_tbl_get_shop_option();
        if (empty($shop_option['enabled'])) {
            return [
                'handled' => false,
                'winner_id' => 0,
            ];
        }

        return [
            'handled' => true,
            'winner_id' => bw_tbl_resolve_active_shop_template_id(),
        ];
    }
}
