<?php
/**
 * BW Cart Pop-Up Frontend Logic
 *
 * Gestisce la logica frontend del pannello cart pop-up
 *
 * @package BW_Cart_Popup
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aggiungi il markup HTML del cart pop-up nel footer
 */
function bw_cart_popup_render_panel() {
    // Verifica se la funzionalità è attiva
    if (!get_option('bw_cart_popup_active', 0)) {
        return;
    }

    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Recupera le impostazioni
    $checkout_text = get_option('bw_cart_popup_checkout_text', 'Proceed to checkout');
    $continue_text = get_option('bw_cart_popup_continue_text', 'Continue shopping');

    ?>
    <!-- BW Cart Pop-Up -->
    <div id="bw-cart-popup-overlay" class="bw-cart-popup-overlay"></div>
    <div id="bw-cart-popup-panel" class="bw-cart-popup-panel">
        <!-- Header del pannello -->
        <div class="bw-cart-popup-header">
            <div class="bw-cart-popup-header-icon">
                <span class="bw-cart-icon"></span>
            </div>
            <h3 class="bw-cart-popup-title">Your Cart</h3>
            <button class="bw-cart-popup-close" aria-label="Close cart">
                <span class="bw-close-icon"></span>
            </button>
        </div>

        <!-- Contenuto del carrello -->
        <div class="bw-cart-popup-content">
            <div class="bw-cart-popup-items">
                <!-- I prodotti vengono caricati dinamicamente via AJAX -->
            </div>

            <!-- Divider -->
            <div class="bw-cart-popup-divider"></div>

            <!-- Sezione Promo Code -->
            <div class="bw-cart-popup-promo">
                <p class="bw-cart-popup-promo-trigger">
                    Have a promo code? <a href="#" class="bw-promo-link">Click here.</a>
                </p>
                <div class="bw-cart-popup-promo-box" style="display: none;">
                    <input type="text" class="bw-promo-input" placeholder="Enter promo code" />
                    <button class="bw-promo-apply">Apply</button>
                    <div class="bw-promo-message"></div>
                </div>
            </div>

            <!-- Totali -->
            <div class="bw-cart-popup-totals">
                <div class="bw-cart-popup-subtotal">
                    <span class="label">Subtotal:</span>
                    <span class="value" data-price="0">€0.00</span>
                </div>
                <div class="bw-cart-popup-discount" style="display: none;">
                    <span class="label">Discount:</span>
                    <span class="value" data-discount="0">-€0.00</span>
                </div>
                <div class="bw-cart-popup-vat">
                    <span class="label">VAT:</span>
                    <span class="value" data-tax="0">€0.00</span>
                </div>
                <div class="bw-cart-popup-total">
                    <span class="label">Total:</span>
                    <span class="value" data-total="0">€0.00</span>
                </div>
            </div>
        </div>

        <!-- Footer con pulsanti -->
        <div class="bw-cart-popup-footer">
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="bw-cart-popup-checkout elementor-button elementor-button-link elementor-size-md">
                <?php echo esc_html($checkout_text); ?>
            </a>
            <button class="bw-cart-popup-continue">
                <?php echo esc_html($continue_text); ?>
            </button>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'bw_cart_popup_render_panel');

/**
 * Aggiungi CSS dinamico per le impostazioni configurabili
 */
function bw_cart_popup_dynamic_css() {
    // Verifica se la funzionalità è attiva
    if (!get_option('bw_cart_popup_active', 0)) {
        return;
    }

    // Recupera le impostazioni
    $panel_width = get_option('bw_cart_popup_panel_width', 400);
    $overlay_color = get_option('bw_cart_popup_overlay_color', '#000000');
    $overlay_opacity = get_option('bw_cart_popup_overlay_opacity', 0.5);
    $panel_bg = get_option('bw_cart_popup_panel_bg', '#ffffff');
    $checkout_color = get_option('bw_cart_popup_checkout_color', '#28a745');
    $continue_color = get_option('bw_cart_popup_continue_color', '#6c757d');
    $cart_icon_css = get_option('bw_cart_popup_cart_icon_css', '');
    $close_icon_css = get_option('bw_cart_popup_close_icon_css', '');

    // Converti colore hex in rgba per l'overlay
    $overlay_rgb = bw_cart_popup_hex_to_rgb($overlay_color);

    ?>
    <style id="bw-cart-popup-dynamic-css">
        /* CSS Dinamico generato dalle impostazioni admin */

        /* Overlay */
        .bw-cart-popup-overlay.active {
            background-color: rgba(<?php echo esc_attr($overlay_rgb); ?>, <?php echo esc_attr($overlay_opacity); ?>);
        }

        /* Pannello */
        .bw-cart-popup-panel {
            width: <?php echo esc_attr($panel_width); ?>px;
            background-color: <?php echo esc_attr($panel_bg); ?>;
        }

        /* Pulsante Continue Shopping */
        .bw-cart-popup-continue {
            background-color: <?php echo esc_attr($continue_color); ?> !important;
        }

        <?php if (!empty($cart_icon_css)): ?>
        /* CSS Personalizzato Icona Carrello */
        .bw-cart-icon {
            <?php echo $cart_icon_css; ?>
        }
        <?php endif; ?>

        <?php if (!empty($close_icon_css)): ?>
        /* CSS Personalizzato Icona Chiusura */
        .bw-close-icon {
            <?php echo $close_icon_css; ?>
        }
        <?php endif; ?>
    </style>
    <?php
}
add_action('wp_head', 'bw_cart_popup_dynamic_css');

/**
 * Converti colore hex in RGB
 */
function bw_cart_popup_hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);

    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }

    return "$r, $g, $b";
}

/**
 * AJAX: Ottieni il contenuto del carrello
 */
function bw_cart_popup_get_cart_contents() {
    check_ajax_referer('bw_cart_popup_nonce', 'nonce');

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce not active']);
    }

    $cart = WC()->cart;
    $cart_items = [];
    $subtotal = 0;
    $discount = 0;
    $tax = 0;
    $total = 0;

    if (!$cart->is_empty()) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            $cart_items[] = [
                'key' => $cart_item_key,
                'product_id' => $product_id,
                'name' => $product->get_name(),
                'quantity' => $quantity,
                'price' => wc_price($product->get_price()),
                'subtotal' => wc_price($cart_item['line_subtotal']),
                'image' => $product->get_image('thumbnail'),
                'permalink' => $product->get_permalink(),
            ];
        }

        $subtotal = $cart->get_subtotal();
        $discount = $cart->get_discount_total();
        $tax = $cart->get_total_tax();
        $total = $cart->get_total('');
    }

    wp_send_json_success([
        'items' => $cart_items,
        'subtotal' => wc_price($subtotal),
        'subtotal_raw' => $subtotal,
        'discount' => wc_price($discount),
        'discount_raw' => $discount,
        'tax' => wc_price($tax),
        'tax_raw' => $tax,
        'total' => wc_price($total),
        'total_raw' => $total,
        'empty' => $cart->is_empty(),
    ]);
}
add_action('wp_ajax_bw_cart_popup_get_contents', 'bw_cart_popup_get_cart_contents');
add_action('wp_ajax_nopriv_bw_cart_popup_get_contents', 'bw_cart_popup_get_cart_contents');

/**
 * AJAX: Applica coupon
 */
function bw_cart_popup_apply_coupon() {
    check_ajax_referer('bw_cart_popup_nonce', 'nonce');

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce not active']);
    }

    $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';

    if (empty($coupon_code)) {
        wp_send_json_error(['message' => 'Please enter a coupon code']);
    }

    $result = WC()->cart->apply_coupon($coupon_code);

    if ($result) {
        // Ricalcola i totali
        WC()->cart->calculate_totals();

        $cart = WC()->cart;
        $subtotal = $cart->get_subtotal();
        $discount = $cart->get_discount_total();
        $tax = $cart->get_total_tax();
        $total = $cart->get_total('');

        wp_send_json_success([
            'message' => 'Coupon applied successfully!',
            'subtotal' => wc_price($subtotal),
            'subtotal_raw' => $subtotal,
            'discount' => wc_price($discount),
            'discount_raw' => $discount,
            'tax' => wc_price($tax),
            'tax_raw' => $tax,
            'total' => wc_price($total),
            'total_raw' => $total,
        ]);
    } else {
        // Ottieni il messaggio di errore di WooCommerce
        $error_messages = wc_get_notices('error');
        $message = !empty($error_messages) ? strip_tags($error_messages[0]['notice']) : 'Invalid coupon code';
        wc_clear_notices();

        wp_send_json_error(['message' => $message]);
    }
}
add_action('wp_ajax_bw_cart_popup_apply_coupon', 'bw_cart_popup_apply_coupon');
add_action('wp_ajax_nopriv_bw_cart_popup_apply_coupon', 'bw_cart_popup_apply_coupon');

/**
 * AJAX: Rimuovi prodotto dal carrello
 */
function bw_cart_popup_remove_item() {
    check_ajax_referer('bw_cart_popup_nonce', 'nonce');

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce not active']);
    }

    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

    if (empty($cart_item_key)) {
        wp_send_json_error(['message' => 'Invalid cart item']);
    }

    $result = WC()->cart->remove_cart_item($cart_item_key);

    if ($result) {
        // Ricalcola i totali
        WC()->cart->calculate_totals();

        wp_send_json_success(['message' => 'Item removed from cart']);
    } else {
        wp_send_json_error(['message' => 'Failed to remove item']);
    }
}
add_action('wp_ajax_bw_cart_popup_remove_item', 'bw_cart_popup_remove_item');
add_action('wp_ajax_nopriv_bw_cart_popup_remove_item', 'bw_cart_popup_remove_item');

/**
 * AJAX: Aggiorna quantità prodotto
 */
function bw_cart_popup_update_quantity() {
    check_ajax_referer('bw_cart_popup_nonce', 'nonce');

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce not active']);
    }

    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if (empty($cart_item_key) || $quantity < 0) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    if ($quantity == 0) {
        // Rimuovi il prodotto se la quantità è 0
        WC()->cart->remove_cart_item($cart_item_key);
    } else {
        // Aggiorna la quantità
        WC()->cart->set_quantity($cart_item_key, $quantity);
    }

    // Ricalcola i totali
    WC()->cart->calculate_totals();

    wp_send_json_success(['message' => 'Quantity updated']);
}
add_action('wp_ajax_bw_cart_popup_update_quantity', 'bw_cart_popup_update_quantity');
add_action('wp_ajax_nopriv_bw_cart_popup_update_quantity', 'bw_cart_popup_update_quantity');
