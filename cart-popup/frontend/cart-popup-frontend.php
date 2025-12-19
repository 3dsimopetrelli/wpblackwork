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
 * Safely retrieve WooCommerce cart instance.
 *
 * @return WC_Cart|null
 */
function bw_cart_popup_get_cart_instance() {
    if ( ! function_exists( 'WC' ) ) {
        return null;
    }

    $wc = WC();

    if ( ! $wc || ! isset( $wc->cart ) ) {
        return null;
    }

    $cart = $wc->cart;

    if ( ! $cart || ! class_exists( 'WC_Cart' ) || ! $cart instanceof WC_Cart ) {
        return null;
    }

    return $cart;
}

/**
 * Safely extract the first WooCommerce error notice as plain text.
 *
 * This prevents passing notice arrays to string-based formatting functions
 * (which triggers "Array to string conversion" warnings).
 *
 * @param string $fallback Default fallback message.
 *
 * @return string
 */
function bw_cart_popup_get_first_error_notice( $fallback = '' ) {
    $error_messages = wc_get_notices( 'error' );
    $message        = $fallback;

    if ( ! empty( $error_messages ) && is_array( $error_messages ) ) {
        $first_error = reset( $error_messages );

        if ( is_array( $first_error ) && isset( $first_error['notice'] ) ) {
            $message = wp_strip_all_tags( (string) $first_error['notice'] );
        } elseif ( is_string( $first_error ) ) {
            $message = wp_strip_all_tags( $first_error );
        }
    }

    if ( empty( $message ) ) {
        $message = __( 'An error occurred while processing your request.', 'bw' );
    }

    return $message;
}

/**
 * Aggiungi il markup HTML del cart pop-up nel footer
 * NOTA: Il markup viene sempre renderizzato perché è necessario anche per i widget
 * (anche se l'opzione globale cart popup è disattivata)
 */
function bw_cart_popup_render_panel() {
    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Recupera le impostazioni
    $checkout_text = get_option('bw_cart_popup_checkout_text', 'Proceed to checkout');
    // Forza l'URL del pulsante principale verso il checkout standard di WooCommerce
    // per garantire un comportamento coerente in ogni contesto.
    $checkout_url = wc_get_checkout_url();
    $continue_text = get_option('bw_cart_popup_continue_text', 'Continue shopping');
    $continue_url = get_option('bw_cart_popup_continue_url', '');
    $additional_svg = get_option('bw_cart_popup_additional_svg', '');
    $empty_cart_svg = get_option('bw_cart_popup_empty_cart_svg', '');
    $svg_black = get_option('bw_cart_popup_svg_black', 0);
    $return_shop_url = get_option('bw_cart_popup_return_shop_url', '');

    // Determina l'URL per continue shopping
    if (empty($continue_url)) {
        $continue_url = home_url('/shop/');
    }

    // Determina l'URL di ritorno allo shop (per empty cart)
    if (empty($return_shop_url)) {
        $return_shop_url = home_url('/shop/');
    }

    ?>
    <!-- BW Cart Pop-Up -->
    <div id="bw-cart-popup-overlay" class="bw-cart-popup-overlay"></div>
    <div id="bw-cart-popup-panel" class="bw-cart-popup-panel">
        <!-- Loading State -->
        <div class="bw-cart-popup-loading" style="display: none;">
            <div class="bw-cart-spinner"></div>
            <p>Loading cart...</p>
        </div>

        <!-- Header del pannello -->
        <div class="bw-cart-popup-header">
            <div class="bw-cart-popup-header-icon">
                <span class="bw-cart-icon"></span>
                <span class="bw-cart-badge">0</span>
            </div>
            <button type="button" class="bw-cart-popup-close" aria-label="Close cart">
                <span class="bw-close-icon"></span>
            </button>
        </div>

        <!-- Notifica verde: "Your item has been added to the cart" -->
        <div class="bw-cart-popup-notification" style="display: none;">
            <svg class="bw-cart-notification-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="bw-cart-notification-text">Your item has been added to the cart</span>
        </div>

        <!-- Layout Carrello Vuoto -->
        <div class="bw-cart-popup-empty-state" style="display: none;">
            <div class="bw-cart-empty-icon">
                <?php if (!empty($empty_cart_svg)): ?>
                    <?php echo $empty_cart_svg; ?>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                <?php endif; ?>
            </div>
            <p class="bw-cart-empty-text">Your cart is currently empty</p>
            <a href="<?php echo esc_url($return_shop_url); ?>" class="bw-cart-popup-return-shop elementor-button elementor-button-link elementor-size-md">
                Return to Shop
            </a>
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
                    <span class="bw-promo-remove-wrapper" style="display: none;">
                        <a href="#" class="bw-promo-remove-link">Remove coupon</a>
                    </span>
                </p>
                <div class="bw-cart-popup-promo-box" style="display: none;">
                    <div class="bw-promo-input-wrapper">
                        <input type="text" class="bw-promo-input" placeholder="Enter promo code" />
                        <button class="bw-promo-apply">Apply</button>
                    </div>
                </div>
                <div class="bw-promo-message"></div>
            </div>

            <!-- Totali -->
            <div class="bw-cart-popup-totals">
                <div class="bw-cart-popup-subtotal">
                    <span class="label">Subtotal:</span>
                    <span class="value" data-price="0">€0.00</span>
                </div>
                <div class="bw-cart-popup-discount" style="display: none;">
                    <span class="label">Discount:</span>
                    <span class="bw-cart-coupon-label" style="display: none;">
                        <span class="bw-cart-coupon-icon" aria-hidden="true"></span>
                        <span class="bw-cart-coupon-code"></span>
                    </span>
                    <span class="value" data-discount="0">-€0.00</span>
                    <span class="bw-cart-coupon-label" style="display: none;">
                        <span class="bw-cart-coupon-icon" aria-hidden="true"></span>
                        <span class="bw-cart-coupon-code"></span>
                    </span>
                </div>
                <div class="bw-cart-popup-total">
                    <span class="label">Total:</span>
                    <span class="value" data-total="0">€0.00</span>
                </div>
            </div>
        </div>

        <!-- Footer con pulsanti -->
        <div class="bw-cart-popup-footer">
            <a href="<?php echo esc_url($checkout_url); ?>" class="bw-cart-popup-checkout elementor-button elementor-button-link elementor-size-md" data-base-text="<?php echo esc_attr($checkout_text); ?>">
                <?php echo esc_html($checkout_text); ?>
            </a>
            <a href="<?php echo esc_url($continue_url); ?>" class="bw-cart-popup-continue">
                <?php echo esc_html($continue_text); ?>
            </a>
        </div>

        <?php if (!empty($additional_svg)): ?>
        <!-- SVG Personalizzato -->
        <div class="bw-cart-popup-custom-svg">
            <?php
            // Processa l'SVG per applicare fill nero se richiesto
            $svg_output = $additional_svg;
            if ($svg_black) {
                // Applica fill: #000 su tutti i tag path, circle, rect, polygon, etc.
                $svg_output = preg_replace('/<path([^>]*)>/i', '<path$1 style="fill: #000;">', $svg_output);
                $svg_output = preg_replace('/<circle([^>]*)>/i', '<circle$1 style="fill: #000;">', $svg_output);
                $svg_output = preg_replace('/<rect([^>]*)>/i', '<rect$1 style="fill: #000;">', $svg_output);
                $svg_output = preg_replace('/<polygon([^>]*)>/i', '<polygon$1 style="fill: #000;">', $svg_output);
                $svg_output = preg_replace('/<ellipse([^>]*)>/i', '<ellipse$1 style="fill: #000;">', $svg_output);
            }
            echo $svg_output;
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
add_action('wp_footer', 'bw_cart_popup_render_panel');

/**
 * Aggiungi CSS dinamico per le impostazioni configurabili
 * NOTA: Il CSS viene sempre renderizzato perché è necessario anche per i widget
 * (anche se l'opzione globale cart popup è disattivata)
 */
function bw_cart_popup_dynamic_css() {
    // Verifica se WooCommerce è attivo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Recupera le impostazioni generali
    $panel_width = get_option('bw_cart_popup_panel_width', 400);
    $overlay_color = get_option('bw_cart_popup_overlay_color', '#000000');
    $overlay_opacity = get_option('bw_cart_popup_overlay_opacity', 0.5);
    $panel_bg = get_option('bw_cart_popup_panel_bg', '#ffffff');

    // Margin per Cart Icon SVG
    $cart_icon_margin_top = get_option('bw_cart_popup_cart_icon_margin_top', 0);
    $cart_icon_margin_right = get_option('bw_cart_popup_cart_icon_margin_right', 0);
    $cart_icon_margin_bottom = get_option('bw_cart_popup_cart_icon_margin_bottom', 0);
    $cart_icon_margin_left = get_option('bw_cart_popup_cart_icon_margin_left', 0);

    // Padding per Empty Cart SVG
    $empty_cart_padding_top = get_option('bw_cart_popup_empty_cart_padding_top', 0);
    $empty_cart_padding_right = get_option('bw_cart_popup_empty_cart_padding_right', 0);
    $empty_cart_padding_bottom = get_option('bw_cart_popup_empty_cart_padding_bottom', 0);
    $empty_cart_padding_left = get_option('bw_cart_popup_empty_cart_padding_left', 0);

    // Promo code section settings
    $promo_input_padding_top = get_option('bw_cart_popup_promo_input_padding_top', 10);
    $promo_input_padding_right = get_option('bw_cart_popup_promo_input_padding_right', 12);
    $promo_input_padding_bottom = get_option('bw_cart_popup_promo_input_padding_bottom', 10);
    $promo_input_padding_left = get_option('bw_cart_popup_promo_input_padding_left', 12);
    $promo_placeholder_font_size = get_option('bw_cart_popup_promo_placeholder_font_size', 14);
    $apply_button_font_weight = get_option('bw_cart_popup_apply_button_font_weight', 'normal');

    // Proceed to Checkout button settings
    $checkout_bg = get_option('bw_cart_popup_checkout_bg', '#28a745');
    $checkout_bg_hover = get_option('bw_cart_popup_checkout_bg_hover', '#218838');
    $checkout_text_color = get_option('bw_cart_popup_checkout_text_color', '#ffffff');
    $checkout_text_hover = get_option('bw_cart_popup_checkout_text_hover', '#ffffff');
    $checkout_font_size = get_option('bw_cart_popup_checkout_font_size', 14);
    $checkout_border_radius = get_option('bw_cart_popup_checkout_border_radius', 6);
    $checkout_border_enabled = get_option('bw_cart_popup_checkout_border_enabled', 0);
    $checkout_border_width = get_option('bw_cart_popup_checkout_border_width', 1);
    $checkout_border_style = get_option('bw_cart_popup_checkout_border_style', 'solid');
    $checkout_border_color = get_option('bw_cart_popup_checkout_border_color', '#28a745');
    $checkout_padding_top = get_option('bw_cart_popup_checkout_padding_top', 12);
    $checkout_padding_right = get_option('bw_cart_popup_checkout_padding_right', 20);
    $checkout_padding_bottom = get_option('bw_cart_popup_checkout_padding_bottom', 12);
    $checkout_padding_left = get_option('bw_cart_popup_checkout_padding_left', 20);

    // Continue Shopping button settings
    $continue_bg = get_option('bw_cart_popup_continue_bg', '#6c757d');
    $continue_bg_hover = get_option('bw_cart_popup_continue_bg_hover', '#5a6268');
    $continue_text_color = get_option('bw_cart_popup_continue_text_color', '#ffffff');
    $continue_text_hover = get_option('bw_cart_popup_continue_text_hover', '#ffffff');
    $continue_font_size = get_option('bw_cart_popup_continue_font_size', 14);
    $continue_border_radius = get_option('bw_cart_popup_continue_border_radius', 6);
    $continue_border_enabled = get_option('bw_cart_popup_continue_border_enabled', 0);
    $continue_border_width = get_option('bw_cart_popup_continue_border_width', 1);
    $continue_border_style = get_option('bw_cart_popup_continue_border_style', 'solid');
    $continue_border_color = get_option('bw_cart_popup_continue_border_color', '#6c757d');
    $continue_padding_top = get_option('bw_cart_popup_continue_padding_top', 12);
    $continue_padding_right = get_option('bw_cart_popup_continue_padding_right', 20);
    $continue_padding_bottom = get_option('bw_cart_popup_continue_padding_bottom', 12);
    $continue_padding_left = get_option('bw_cart_popup_continue_padding_left', 20);

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

        /* === PROCEED TO CHECKOUT BUTTON === */
        .bw-cart-popup-checkout,
        .bw-cart-popup-return-shop {
            background-color: <?php echo esc_attr($checkout_bg); ?> !important;
            color: <?php echo esc_attr($checkout_text_color); ?> !important;
            font-size: <?php echo esc_attr($checkout_font_size); ?>px !important;
            border-radius: <?php echo esc_attr($checkout_border_radius); ?>px !important;
            padding: <?php echo esc_attr($checkout_padding_top); ?>px <?php echo esc_attr($checkout_padding_right); ?>px <?php echo esc_attr($checkout_padding_bottom); ?>px <?php echo esc_attr($checkout_padding_left); ?>px !important;
            <?php if ($checkout_border_enabled): ?>
            border: <?php echo esc_attr($checkout_border_width); ?>px <?php echo esc_attr($checkout_border_style); ?> <?php echo esc_attr($checkout_border_color); ?> !important;
            <?php else: ?>
            border: none !important;
            <?php endif; ?>
        }

        .bw-cart-popup-checkout:hover,
        .bw-cart-popup-return-shop:hover {
            background-color: <?php echo esc_attr($checkout_bg_hover); ?> !important;
            color: <?php echo esc_attr($checkout_text_hover); ?> !important;
            opacity: 1 !important;
        }

        /* === CONTINUE SHOPPING BUTTON === */
        .bw-cart-popup-continue {
            background-color: <?php echo esc_attr($continue_bg); ?> !important;
            color: <?php echo esc_attr($continue_text_color); ?> !important;
            font-size: <?php echo esc_attr($continue_font_size); ?>px !important;
            border-radius: <?php echo esc_attr($continue_border_radius); ?>px !important;
            padding: <?php echo esc_attr($continue_padding_top); ?>px <?php echo esc_attr($continue_padding_right); ?>px <?php echo esc_attr($continue_padding_bottom); ?>px <?php echo esc_attr($continue_padding_left); ?>px !important;
            <?php if ($continue_border_enabled): ?>
            border: <?php echo esc_attr($continue_border_width); ?>px <?php echo esc_attr($continue_border_style); ?> <?php echo esc_attr($continue_border_color); ?> !important;
            <?php else: ?>
            border: none !important;
            <?php endif; ?>
        }

        .bw-cart-popup-continue:hover {
            background-color: <?php echo esc_attr($continue_bg_hover); ?> !important;
            color: <?php echo esc_attr($continue_text_hover); ?> !important;
            opacity: 1 !important;
        }

        /* === MARGIN PER CART ICON SVG === */
        /* Applicato sia all'icona custom che a quella default */
        .bw-cart-popup-custom-svg svg,
        .bw-cart-icon {
            margin: <?php echo esc_attr($cart_icon_margin_top); ?>px <?php echo esc_attr($cart_icon_margin_right); ?>px <?php echo esc_attr($cart_icon_margin_bottom); ?>px <?php echo esc_attr($cart_icon_margin_left); ?>px !important;
        }

        /* === APPLY PROMO CODE BUTTON - USA STILE CHECKOUT === */
        /* Forza lo stile del pulsante checkout sul pulsante apply promo solo nel cart popup */
        .bw-cart-popup-panel .bw-promo-apply {
            background-color: <?php echo esc_attr($checkout_bg); ?> !important;
            color: <?php echo esc_attr($checkout_text_color); ?> !important;
            font-size: <?php echo esc_attr($checkout_font_size); ?>px !important;
            border-radius: <?php echo esc_attr($checkout_border_radius); ?>px !important;
            padding: <?php echo esc_attr($checkout_padding_top); ?>px <?php echo esc_attr($checkout_padding_right); ?>px <?php echo esc_attr($checkout_padding_bottom); ?>px <?php echo esc_attr($checkout_padding_left); ?>px !important;
            <?php if ($checkout_border_enabled): ?>
            border: <?php echo esc_attr($checkout_border_width); ?>px <?php echo esc_attr($checkout_border_style); ?> <?php echo esc_attr($checkout_border_color); ?> !important;
            <?php else: ?>
            border: none !important;
            <?php endif; ?>
            font-weight: <?php echo esc_attr($apply_button_font_weight); ?> !important;
            cursor: pointer;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .bw-cart-popup-panel .bw-promo-apply:hover {
            background-color: <?php echo esc_attr($checkout_bg_hover); ?> !important;
            color: <?php echo esc_attr($checkout_text_hover); ?> !important;
            opacity: 1 !important;
        }

        .bw-cart-popup-panel .bw-promo-apply:disabled {
            background-color: #cccccc !important;
            cursor: not-allowed;
        }

        /* === PROMO CODE INPUT SETTINGS === */
        /* Padding per input promo code */
        .bw-cart-popup-panel .bw-promo-input {
            padding: <?php echo esc_attr($promo_input_padding_top); ?>px <?php echo esc_attr($promo_input_padding_right); ?>px <?php echo esc_attr($promo_input_padding_bottom); ?>px <?php echo esc_attr($promo_input_padding_left); ?>px !important;
        }

        /* Font size placeholder input promo code */
        .bw-cart-popup-panel .bw-promo-input::placeholder {
            font-size: <?php echo esc_attr($promo_placeholder_font_size); ?>px;
        }

        /* === PADDING PER EMPTY CART SVG === */
        .bw-cart-empty-icon svg {
            padding: <?php echo esc_attr($empty_cart_padding_top); ?>px <?php echo esc_attr($empty_cart_padding_right); ?>px <?php echo esc_attr($empty_cart_padding_bottom); ?>px <?php echo esc_attr($empty_cart_padding_left); ?>px;
        }
    </style>
    <?php
}
add_action('wp_head', 'bw_cart_popup_dynamic_css');

/**
 * AJAX: Add to cart with sold-individually handling
 */
function bw_cart_popup_ajax_add_to_cart() {
    check_ajax_referer( 'bw_cart_popup_nonce', 'nonce' );

    if ( ! class_exists( 'WooCommerce' ) ) {
        wp_send_json_error( [ 'message' => __( 'WooCommerce is not active.', 'bw' ) ] );
    }

    $cart = bw_cart_popup_get_cart_instance();

    if ( ! $cart ) {
        wp_send_json_error( [ 'message' => __( 'Cart not initialized.', 'bw' ) ] );
    }

    $product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
    $quantity_raw = isset( $_POST['quantity'] ) ? wp_unslash( $_POST['quantity'] ) : 1;
    $quantity     = apply_filters( 'woocommerce_stock_amount', wc_stock_amount( $quantity_raw ) );
    $variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
    $variation    = [];

    // Recupera le variazioni passate
    if ( ! empty( $_POST['variation'] ) && is_array( $_POST['variation'] ) ) {
        foreach ( $_POST['variation'] as $key => $value ) {
            $variation[ sanitize_title( wp_unslash( $key ) ) ] = wc_clean( wp_unslash( $value ) );
        }
    } else {
        foreach ( $_POST as $key => $value ) {
            if ( 0 === strpos( $key, 'attribute_' ) ) {
                $variation[ sanitize_title( wp_unslash( $key ) ) ] = wc_clean( wp_unslash( $value ) );
            }
        }
    }

    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid product.', 'bw' ) ] );
    }

    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        wp_send_json_error( [ 'message' => __( 'Product not found.', 'bw' ) ] );
    }

    // Gestione prodotti vendibili singolarmente già nel carrello
    if ( $product->is_sold_individually() ) {
        $cart_item_key = $cart->find_product_in_cart( $cart->generate_cart_id( $product_id, $variation_id, $variation ) );

        if ( $cart_item_key ) {
            $message = sprintf( __( 'You cannot add another "%s" to your cart.', 'woocommerce' ), $product->get_name() );

            wp_send_json(
                [
                    'status'    => 'already_in_cart',
                    'message'   => wp_kses_post( $message ),
                    'cart_url'  => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
                    'product'   => [
                        'id'   => $product_id,
                        'name' => $product->get_name(),
                    ],
                ]
            );
        }
    }

    $added = $cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

    if ( ! $added ) {
        $message = bw_cart_popup_get_first_error_notice( __( 'Unable to add product to cart.', 'bw' ) );
        wc_clear_notices();

        wp_send_json_error( [ 'message' => $message ] );
    }

    // Costruisci i fragments come WooCommerce
    ob_start();
    woocommerce_mini_cart();
    $mini_cart = ob_get_clean();

    $fragments = apply_filters(
        'woocommerce_add_to_cart_fragments',
        [
            'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
        ],
        $product_id
    );

    $cart_hash = apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_hash(), $added );

    wp_send_json_success(
        [
            'status'    => 'added',
            'fragments' => $fragments,
            'cart_hash' => $cart_hash,
        ]
    );
}
add_action( 'wp_ajax_bw_cart_popup_add_to_cart', 'bw_cart_popup_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_bw_cart_popup_add_to_cart', 'bw_cart_popup_ajax_add_to_cart' );

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

    $cart = bw_cart_popup_get_cart_instance();

    if ( ! $cart ) {
        wp_send_json_error(['message' => 'Cart not initialized']);
    }
    $cart_items = [];
    $subtotal = 0;
    $discount = 0;
    $tax = 0;
    $total = 0;
    $item_count = 0;
    $applied_coupons = [];

    if (!$cart->is_empty()) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            $price_display = wc_get_price_to_display($product, ['qty' => $quantity]);
            $regular_price_display = wc_get_price_to_display($product, ['qty' => $quantity, 'price' => $product->get_regular_price()]);

            $cart_items[] = [
                'key' => $cart_item_key,
                'product_id' => $product_id,
                'name' => $product->get_name(),
                'quantity' => $quantity,
                'sold_individually' => (bool) $product->is_sold_individually(),
                'price' => wc_price($product->get_price()),
                'price_raw' => (float) $product->get_price(),
                'regular_price' => wc_price($product->get_regular_price()),
                'regular_price_raw' => (float) $product->get_regular_price(),
                'subtotal' => wc_price($price_display),
                'subtotal_raw' => (float) $price_display,
                'regular_subtotal' => wc_price($regular_price_display),
                'regular_subtotal_raw' => (float) $regular_price_display,
                'image' => $product->get_image('thumbnail'),
                'permalink' => $product->get_permalink(),
            ];
        }

        $subtotal = $cart->get_subtotal();
        $discount = $cart->get_discount_total();
        $tax = $cart->get_total_tax();
        $total = $cart->get_total('');
        $item_count = $cart->get_cart_contents_count();
        $applied_coupons = array_map('sanitize_text_field', array_values(array_unique($cart->get_applied_coupons())));
    }

    // Ottieni i coupon applicati
    $applied_coupons = $cart->get_applied_coupons();

    wp_send_json_success([
        'items' => $cart_items,
        'item_count' => $item_count,
        'coupons' => $applied_coupons,
        'subtotal' => wc_price($subtotal),
        'subtotal_raw' => $subtotal,
        'discount' => wc_price($discount),
        'discount_raw' => $discount,
        'tax' => wc_price($tax),
        'tax_raw' => $tax,
        'total' => wc_price($total),
        'total_raw' => $total,
        'empty' => $cart->is_empty(),
        'applied_coupons' => $applied_coupons,
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

    $cart = bw_cart_popup_get_cart_instance();

    if ( ! $cart ) {
        wp_send_json_error(['message' => 'Cart not initialized']);
    }

    $result = $cart->apply_coupon($coupon_code);

    if ($result) {
        // Ricalcola i totali
        $cart->calculate_totals();
        $subtotal = $cart->get_subtotal();
        $discount = $cart->get_discount_total();
        $tax = $cart->get_total_tax();
        $total = $cart->get_total('');
        $applied_coupons = array_map('sanitize_text_field', array_values(array_unique($cart->get_applied_coupons())));

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
            'coupons' => $applied_coupons,
        ]);
    } else {
        $message = bw_cart_popup_get_first_error_notice( __( 'Invalid coupon code', 'bw' ) );
        wc_clear_notices();

        wp_send_json_error(['message' => $message]);
    }
}
add_action('wp_ajax_bw_cart_popup_apply_coupon', 'bw_cart_popup_apply_coupon');
add_action('wp_ajax_nopriv_bw_cart_popup_apply_coupon', 'bw_cart_popup_apply_coupon');

/**
 * AJAX: Rimuovi coupon
 */
function bw_cart_popup_remove_coupon() {
    check_ajax_referer('bw_cart_popup_nonce', 'nonce');

    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce not active']);
    }

    $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';

    if (empty($coupon_code)) {
        wp_send_json_error(['message' => 'Please provide a coupon code']);
    }

    $cart = bw_cart_popup_get_cart_instance();

    if ( ! $cart ) {
        wp_send_json_error(['message' => 'Cart not initialized']);
    }

    $result = $cart->remove_coupon($coupon_code);

    if ($result) {
        // Ricalcola i totali
        $cart->calculate_totals();
        $subtotal = $cart->get_subtotal();
        $discount = $cart->get_discount_total();
        $tax = $cart->get_total_tax();
        $total = $cart->get_total('');
        $applied_coupons = $cart->get_applied_coupons();

        wp_send_json_success([
            'message' => 'Coupon removed successfully!',
            'subtotal' => wc_price($subtotal),
            'subtotal_raw' => $subtotal,
            'discount' => wc_price($discount),
            'discount_raw' => $discount,
            'tax' => wc_price($tax),
            'tax_raw' => $tax,
            'total' => wc_price($total),
            'total_raw' => $total,
            'applied_coupons' => $applied_coupons,
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to remove coupon']);
    }
}
add_action('wp_ajax_bw_cart_popup_remove_coupon', 'bw_cart_popup_remove_coupon');
add_action('wp_ajax_nopriv_bw_cart_popup_remove_coupon', 'bw_cart_popup_remove_coupon');

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

    $cart = bw_cart_popup_get_cart_instance();

    if ( ! $cart ) {
        wp_send_json_error(['message' => 'Cart not initialized']);
    }

    $result = $cart->remove_cart_item($cart_item_key);

    if ($result) {
        // Ricalcola i totali
        $cart->calculate_totals();

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

    $cart = bw_cart_popup_get_cart_instance();

    if ( ! $cart ) {
        wp_send_json_error(['message' => 'Cart not initialized']);
    }

    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if (empty($cart_item_key) || $quantity < 0) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    if ($quantity == 0) {
        // Rimuovi il prodotto se la quantità è 0
        $cart->remove_cart_item($cart_item_key);
    } else {
        // Aggiorna la quantità
        $cart->set_quantity($cart_item_key, $quantity);
    }

    // Ricalcola i totali
    $cart->calculate_totals();

    wp_send_json_success(['message' => 'Quantity updated']);
}
add_action('wp_ajax_bw_cart_popup_update_quantity', 'bw_cart_popup_update_quantity');
add_action('wp_ajax_nopriv_bw_cart_popup_update_quantity', 'bw_cart_popup_update_quantity');
