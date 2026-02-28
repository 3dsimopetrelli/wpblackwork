<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_default_footer_option')) {
    function bw_tbl_default_footer_option()
    {
        return [
            'version' => 1,
            'active_footer_template_id' => 0,
        ];
    }
}

if (!function_exists('bw_tbl_get_footer_option')) {
    function bw_tbl_get_footer_option()
    {
        $saved = get_option(BW_TBL_FOOTER_OPTION, []);
        $saved = is_array($saved) ? $saved : [];

        return array_replace(bw_tbl_default_footer_option(), $saved);
    }
}

if (!function_exists('bw_tbl_sanitize_footer_option')) {
    function bw_tbl_sanitize_footer_option($input)
    {
        $input = is_array($input) ? $input : [];
        $active_id = isset($input['active_footer_template_id']) ? absint($input['active_footer_template_id']) : 0;

        if ($active_id > 0) {
            $post = get_post($active_id);
            $type = $post ? get_post_meta($active_id, 'bw_template_type', true) : '';

            if (!$post || 'bw_template' !== $post->post_type || 'publish' !== $post->post_status || 'footer' !== bw_tbl_sanitize_template_type($type)) {
                $active_id = 0;
            }
        }

        return [
            'version' => 1,
            'active_footer_template_id' => $active_id,
        ];
    }
}

if (!function_exists('bw_tbl_get_footer_template_choices')) {
    function bw_tbl_get_footer_template_choices()
    {
        $query = new WP_Query(
            [
                'post_type' => 'bw_template',
                'post_status' => 'publish',
                'posts_per_page' => 200,
                'orderby' => 'title',
                'order' => 'ASC',
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => 'bw_template_type',
                        'value' => 'footer',
                    ],
                ],
            ]
        );

        $choices = [];
        foreach ($query->posts as $post_id) {
            $choices[(int) $post_id] = get_the_title($post_id);
        }

        return $choices;
    }
}

if (!function_exists('bw_tbl_resolve_active_footer_template_id')) {
    function bw_tbl_resolve_active_footer_template_id()
    {
        $footer_option = bw_tbl_get_footer_option();
        $active_id = isset($footer_option['active_footer_template_id']) ? absint($footer_option['active_footer_template_id']) : 0;

        if ($active_id <= 0) {
            return 0;
        }

        $post = get_post($active_id);
        if (!$post || 'bw_template' !== $post->post_type || 'publish' !== $post->post_status) {
            return 0;
        }

        $type = get_post_meta($active_id, 'bw_template_type', true);
        if ('footer' !== bw_tbl_sanitize_template_type($type)) {
            return 0;
        }

        return $active_id;
    }
}

if (!function_exists('bw_tbl_is_elementor_preview')) {
    function bw_tbl_is_elementor_preview()
    {
        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Plugin')) {
            return false;
        }

        $elementor = \Elementor\Plugin::$instance;
        return $elementor && isset($elementor->preview) && $elementor->preview->is_preview_mode();
    }
}

if (!function_exists('bw_tbl_is_elementor_editor_request')) {
    function bw_tbl_is_elementor_editor_request()
    {
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        if ('elementor' === $action) {
            return true;
        }

        // Elementor preview iframe requests usually include this query key.
        if (isset($_GET['elementor-preview'])) {
            return true;
        }

        return false;
    }
}

if (!function_exists('bw_tbl_get_runtime_footer_template_id')) {
    function bw_tbl_get_runtime_footer_template_id()
    {
        static $runtime_template_id = null;

        if (null !== $runtime_template_id) {
            return $runtime_template_id;
        }

        $runtime_template_id = 0;

        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed() || bw_tbl_is_elementor_preview() || bw_tbl_is_elementor_editor_request()) {
            return $runtime_template_id;
        }

        if (!bw_tbl_is_feature_enabled('footer_override_enabled')) {
            return $runtime_template_id;
        }

        $runtime_template_id = bw_tbl_resolve_active_footer_template_id();

        return $runtime_template_id;
    }
}

if (!function_exists('bw_tbl_prepare_footer_runtime')) {
    function bw_tbl_prepare_footer_runtime()
    {
        $template_id = bw_tbl_get_runtime_footer_template_id();
        if ($template_id <= 0) {
            return;
        }

        // Hello Elementor footer renderer.
        if (function_exists('hello_elementor_render_footer')) {
            remove_action('hello_elementor_footer', 'hello_elementor_render_footer');
        }
    }
}
add_action('wp', 'bw_tbl_prepare_footer_runtime', 20);

if (!function_exists('bw_tbl_footer_theme_fallback_css')) {
    function bw_tbl_footer_theme_fallback_css()
    {
        $template_id = bw_tbl_get_runtime_footer_template_id();
        if ($template_id <= 0) {
            return;
        }
        ?>
        <style id="bw-tbl-footer-theme-fallback-css">
            footer#colophon,
            #colophon.site-footer,
            footer.site-footer,
            #site-footer.site-footer,
            .site-footer {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'bw_tbl_footer_theme_fallback_css', 99);

if (!function_exists('bw_tbl_render_footer_template_content')) {
    function bw_tbl_render_footer_template_content($template_id)
    {
        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return '';
        }

        if (class_exists('\\Elementor\\Plugin')) {
            $plugin = \Elementor\Plugin::instance();
            if ($plugin && isset($plugin->frontend)) {
                try {
                    $content = $plugin->frontend->get_builder_content_for_display($template_id, true);
                } catch (\Throwable $exception) {
                    return '';
                }

                if (is_string($content) && '' !== trim($content)) {
                    return $content;
                }
            }
        }

        $post_content = get_post_field('post_content', $template_id);
        if (!is_string($post_content) || '' === trim($post_content)) {
            return '';
        }

        return (string) apply_filters('the_content', $post_content);
    }
}

if (!function_exists('bw_tbl_render_footer_template')) {
    function bw_tbl_render_footer_template()
    {
        $template_id = bw_tbl_get_runtime_footer_template_id();
        if ($template_id <= 0) {
            return;
        }

        $content = bw_tbl_render_footer_template_content($template_id);
        if ('' === $content) {
            return;
        }

        echo '<div class="bw-tbl-footer-template" data-bw-tbl-footer-template="1">';
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}
add_action('wp_footer', 'bw_tbl_render_footer_template', 20);
