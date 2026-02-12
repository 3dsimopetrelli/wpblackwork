<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handler AJAX per la ricerca live dei prodotti
 * 
 * Migrated from bw-main-elementor-widgets.php to allow standalone header usage.
 */

// Se l'action è già stata registrata altrove (retrocompatibilità), rimuoviamola per evitare doppi hook
if (has_action('wp_ajax_bw_live_search_products', 'bw_live_search_products')) {
    remove_action('wp_ajax_bw_live_search_products', 'bw_live_search_products');
}
if (has_action('wp_ajax_nopriv_bw_live_search_products', 'bw_live_search_products')) {
    remove_action('wp_ajax_nopriv_bw_live_search_products', 'bw_live_search_products');
}

add_action('wp_ajax_bw_live_search_products', 'bw_header_live_search_products');
add_action('wp_ajax_nopriv_bw_live_search_products', 'bw_header_live_search_products');

if (!function_exists('bw_header_live_search_products')) {
    function bw_header_live_search_products()
    {
        // Verifica nonce per sicurezza
        check_ajax_referer('bw_search_nonce', 'nonce');

        // Ottieni parametri dalla richiesta
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [];
        $product_type = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : '';

        // Se il termine di ricerca è troppo corto, restituisci risultati vuoti
        if (strlen($search_term) < 2) {
            wp_send_json_success([
                'products' => [],
                'message' => '',
            ]);
        }

        // Prepara gli argomenti della query
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 12,
            'post_status' => 'publish',
            's' => $search_term,
        ];

        // Prepara tax_query per filtri
        $tax_query = [];

        // Aggiungi filtro per categorie se specificato
        if (!empty($categories)) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $categories,
            ];
        }

        // Aggiungi filtro per product type se specificato (solo tipi standard WooCommerce)
        if (!empty($product_type) && in_array($product_type, ['simple', 'variable', 'grouped', 'external'], true)) {
            $tax_query[] = [
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => $product_type,
            ];
        }

        // Aggiungi tax_query agli args se non è vuoto
        if (!empty($tax_query)) {
            // Se c'è più di un filtro, specifica la relazione AND
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }

        // Esegui la query
        $query = new WP_Query($args);

        $products = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);

                if (!$product) {
                    continue;
                }

                // Ottieni l'immagine in evidenza
                $image_id = $product->get_image_id();
                $image_url = '';

                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                }

                // Se non c'è immagine, usa un placeholder
                if (!$image_url) {
                    $image_url = wc_placeholder_img_src('medium');
                }

                // Prepara i dati del prodotto
                $products[] = [
                    'id' => $product_id,
                    'title' => get_the_title(),
                    'price_html' => $product->get_price_html(),
                    'image_url' => $image_url,
                    'permalink' => get_permalink($product_id),
                ];
            }
            wp_reset_postdata();
        }

        // Restituisci i risultati
        wp_send_json_success([
            'products' => $products,
            'message' => empty($products) ? __('Nessun prodotto trovato', 'bw') : '',
        ]);
    }
}
