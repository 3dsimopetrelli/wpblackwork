<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bw_tbl_preview_debug_log')) {
    function bw_tbl_preview_debug_log($message, $context = [])
    {
        if (!defined('BW_TBL_DEBUG_PREVIEW') || !BW_TBL_DEBUG_PREVIEW) {
            return;
        }

        $suffix = '';
        if (is_array($context) && !empty($context)) {
            $suffix = ' ' . wp_json_encode($context);
        }

        error_log('[BW TBL Preview] ' . (string) $message . $suffix); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

if (!function_exists('bw_tbl_is_elementor_preview_request')) {
    function bw_tbl_is_elementor_preview_request()
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
            return false;
        }

        if (!defined('ELEMENTOR_VERSION') || !class_exists('\\Elementor\\Plugin')) {
            return false;
        }

        $plugin = \Elementor\Plugin::$instance;
        $is_preview_mode = $plugin && isset($plugin->preview) && method_exists($plugin->preview, 'is_preview_mode') && $plugin->preview->is_preview_mode();

        if ($is_preview_mode) {
            return true;
        }

        return isset($_GET['elementor-preview']) || isset($_GET['elementor_library']);
    }
}

if (!function_exists('bw_tbl_get_elementor_preview_template_id')) {
    function bw_tbl_get_elementor_preview_template_id()
    {
        $template_id = isset($_GET['elementor-preview']) ? absint(wp_unslash($_GET['elementor-preview'])) : 0;

        if ($template_id <= 0 && isset($_GET['post'])) {
            $template_id = absint(wp_unslash($_GET['post']));
        }

        if ($template_id <= 0 && is_singular('bw_template')) {
            $template_id = absint(get_queried_object_id());
        }

        return $template_id > 0 ? $template_id : 0;
    }
}

if (!function_exists('bw_tbl_get_elementor_preview_single_product_context')) {
    function bw_tbl_get_elementor_preview_single_product_context()
    {
        if (!bw_tbl_is_elementor_preview_request()) {
            return [
                'apply' => false,
                'reason' => 'not_preview_request',
                'template_id' => 0,
                'product_id' => 0,
            ];
        }

        $template_id = bw_tbl_get_elementor_preview_template_id();
        if ($template_id <= 0) {
            return [
                'apply' => false,
                'reason' => 'missing_template_id',
                'template_id' => 0,
                'product_id' => 0,
            ];
        }

        $template_post = get_post($template_id);
        if (!($template_post instanceof WP_Post) || 'bw_template' !== $template_post->post_type) {
            return [
                'apply' => false,
                'reason' => 'not_bw_template',
                'template_id' => $template_id,
                'product_id' => 0,
            ];
        }

        $template_type = get_post_meta($template_id, 'bw_template_type', true);
        if (function_exists('bw_tbl_sanitize_template_type')) {
            $template_type = bw_tbl_sanitize_template_type($template_type);
        } else {
            $template_type = sanitize_key((string) $template_type);
        }

        if ('single_product' !== $template_type) {
            return [
                'apply' => false,
                'reason' => 'template_type_mismatch',
                'template_id' => $template_id,
                'product_id' => 0,
            ];
        }

        if (!function_exists('bw_tbl_get_single_product_preview_product_id') || !function_exists('bw_tbl_is_valid_preview_product')) {
            return [
                'apply' => false,
                'reason' => 'missing_preview_helpers',
                'template_id' => $template_id,
                'product_id' => 0,
            ];
        }

        $product_id = bw_tbl_get_single_product_preview_product_id(true);
        if (!bw_tbl_is_valid_preview_product($product_id)) {
            return [
                'apply' => false,
                'reason' => 'invalid_preview_product',
                'template_id' => $template_id,
                'product_id' => absint($product_id),
            ];
        }

        return [
            'apply' => true,
            'reason' => 'ok',
            'template_id' => $template_id,
            'product_id' => absint($product_id),
        ];
    }
}

if (!function_exists('bw_tbl_apply_elementor_single_product_preview_context')) {
    function bw_tbl_apply_elementor_single_product_preview_context()
    {
        static $applied = false;
        if ($applied) {
            return;
        }

        $context = bw_tbl_get_elementor_preview_single_product_context();
        bw_tbl_preview_debug_log('preview request detected', [
            'detected' => bw_tbl_is_elementor_preview_request(),
            'template_id' => $context['template_id'],
            'product_id' => $context['product_id'],
            'reason' => $context['reason'],
        ]);

        if (empty($context['apply'])) {
            return;
        }

        $product_id = absint($context['product_id']);
        $product_post = get_post($product_id);
        if (!($product_post instanceof WP_Post)) {
            bw_tbl_preview_debug_log('preview context skipped: product post not found', ['product_id' => $product_id]);
            return;
        }

        global $product;

        $preview_product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

        if (function_exists('wc_setup_product_data')) {
            wc_setup_product_data($product_id);
        }

        if ($preview_product && is_a($preview_product, 'WC_Product')) {
            $product = $preview_product;
        }

        $GLOBALS['bw_tbl_preview_product_id'] = $product_id;
        set_query_var('bw_tbl_preview_product_id', $product_id);
        $applied = true;

        bw_tbl_preview_debug_log('preview context applied without wp_query mutation', [
            'template_id' => $context['template_id'],
            'product_id' => $product_id,
            'wc_product' => ($product && is_a($product, 'WC_Product')),
            'wp_query_mutated' => false,
        ]);

        bw_tbl_preview_debug_log('preview bridge values set', [
            'global_bw_tbl_preview_product_id' => isset($GLOBALS['bw_tbl_preview_product_id']) ? absint($GLOBALS['bw_tbl_preview_product_id']) : 0,
            'query_var_bw_tbl_preview_product_id' => absint(get_query_var('bw_tbl_preview_product_id')),
        ]);
    }
}
add_action('wp', 'bw_tbl_apply_elementor_single_product_preview_context', 20);

/**
 * Ripristina il contesto prodotto durante il re-render AJAX di Elementor.
 *
 * Quando l'utente modifica un controllo nell'editor, Elementor ri-renderizza il widget
 * via wp_ajax_elementor_ajax. In quel contesto admin-ajax.php NON esegue il hook `wp`,
 * quindi bw_tbl_apply_elementor_single_product_preview_context() non gira mai e i bridge
 * globals restano vuoti. Questo hook (priority 1, prima che Elementor elabori la richiesta)
 * risolve il product ID e lo inietta nei globals usati da tutti i widget BW.
 *
 * Copre due scenari:
 *   1. Editing diretto di una pagina prodotto → editor_post_id è il prodotto stesso.
 *   2. Editing di un template BW → legge l'opzione "preview product" salvata nelle impostazioni.
 */
if (!function_exists('bw_tbl_apply_elementor_ajax_product_context')) {
    function bw_tbl_apply_elementor_ajax_product_context()
    {
        // Solo per utenti con permessi di editing
        if (!current_user_can('edit_posts')) {
            return;
        }

        if (!class_exists('\Elementor\Plugin') || !function_exists('wc_get_product')) {
            return;
        }

        $product_id = 0;

        // Caso 1: si sta editando direttamente una pagina prodotto
        // editor_post_id è l'ID del post in editing
        if (!empty($_POST['editor_post_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $editor_post_id = absint($_POST['editor_post_id']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($editor_post_id > 0 && wc_get_product($editor_post_id)) {
                $product_id = $editor_post_id;
                bw_tbl_preview_debug_log('ajax_context: resolved via editor_post_id', ['product_id' => $product_id]);
            }
        }

        // Caso 2: si sta editando un template BW → usa il preview product salvato nelle opzioni
        if ($product_id <= 0 && function_exists('bw_tbl_get_preview_product_id')) {
            $option_id = absint(bw_tbl_get_preview_product_id());
            if ($option_id > 0) {
                $product_id = $option_id;
                bw_tbl_preview_debug_log('ajax_context: resolved via option_v1', ['product_id' => $product_id]);
            }
        }

        if ($product_id <= 0 && function_exists('bw_tbl_get_single_product_preview_product_id')) {
            $legacy_id = absint(bw_tbl_get_single_product_preview_product_id(false));
            if ($legacy_id > 0) {
                $product_id = $legacy_id;
                bw_tbl_preview_debug_log('ajax_context: resolved via option_legacy', ['product_id' => $product_id]);
            }
        }

        if ($product_id <= 0) {
            bw_tbl_preview_debug_log('ajax_context: no product resolved, skipping');
            return;
        }

        // Setta i bridge globals → bw_tbl_resolve_product_context_id() li troverà
        $GLOBALS['bw_tbl_preview_product_id'] = $product_id;

        // Setta global $product → widget che lo usano direttamente funzioneranno
        global $product;
        $preview_product = wc_get_product($product_id);
        if ($preview_product && is_a($preview_product, 'WC_Product')) {
            $product = $preview_product;
        }

        // Inizializza il WooCommerce product data context
        if (function_exists('wc_setup_product_data')) {
            wc_setup_product_data($product_id);
        }

        bw_tbl_preview_debug_log('ajax_context: bridge set', [
            'product_id' => $product_id,
            'wc_product'  => ($product && is_a($product, 'WC_Product')),
        ]);
    }
}
// Priority 1: prima che Elementor elabori la richiesta AJAX di re-render widget
add_action('wp_ajax_elementor_ajax', 'bw_tbl_apply_elementor_ajax_product_context', 1);

