# WooCommerce Template Overrides

Questa directory contiene template personalizzati per WooCommerce che utilizzano il sistema centralizzato delle product card del plugin BW Elementor Widgets.

## Come Usare

### Metodo 1: Copia nel tema

Per utilizzare questi template, copiali nella directory del tuo tema seguendo questa struttura:

```
your-theme/
└── woocommerce/
    ├── content-product.php
    └── loop/
        └── no-products-found.php
```

### Metodo 2: Usa i filtri PHP

Puoi anche integrare il renderer direttamente tramite filtri nel tuo tema o in un plugin personalizzato:

```php
// In functions.php o in un plugin custom
add_filter( 'woocommerce_product_loop_start', 'custom_product_loop_start' );
function custom_product_loop_start() {
    // Usa la classe BW_Product_Card_Renderer per personalizzare le card
}
```

## Template Disponibili

### content-product.php
Template per la card prodotto singola negli archivi e nei loop.
Utilizza `BW_Product_Card_Renderer::render_card()` per generare card in stile BW Wallpost.

## Personalizzazione

Tutti i template utilizzano la classe `BW_Product_Card_Renderer` che supporta queste opzioni:

- `image_size`: Dimensione immagine (thumbnail, medium, large, full)
- `show_image`: Mostra/nascondi immagine
- `show_hover_image`: Abilita immagine hover
- `show_title`: Mostra/nascondi titolo
- `show_description`: Mostra/nascondi descrizione
- `show_price`: Mostra/nascondi prezzo
- `show_buttons`: Mostra/nascondi pulsanti overlay
- `show_add_to_cart`: Mostra/nascondi pulsante add to cart
- `open_cart_popup`: Apri cart popup invece di andare alla pagina carrello

## Note

- Assicurati che il plugin BW Elementor Widgets sia attivo
- I template utilizzano le stesse classi CSS del widget BW Wallpost
- Puoi personalizzare l'aspetto tramite i CSS del tuo tema

## Maintenance Governance

### Version Alignment Log
- Date: 2026-03-09
- Risk reference: `R-WOO-24` (WooCommerce template overrides stale risk)
- Patch items completed:
  - `checkout/form-coupon.php` (patch 1)
    - Previous override header: `@version 7.0.1`
    - Current override header: `@version 9.8.0`
    - Strategy: minimal compatibility rebase (core form contract + preserved BlackWork custom UX classes)
  - `checkout/payment.php` (patch 2)
    - Previous override header: `@version 8.1.0`
    - Current override header: `@version 9.8.0`
    - Strategy: structural alignment only (`WC()->cart` guard, no-available-methods core branch, translator-safe noscript) with custom payment UI preserved
  - `checkout/form-checkout.php` (patch 3)
    - Previous override header: custom/non-core metadata
    - Current override header: WooCommerce template metadata block (`@version 9.4.0`)
    - Strategy: minimal structural compatibility patch (`aria-label` + trailing `woocommerce_after_checkout_form` hook restore), no layout/runtime flow rewrite

### Minimal Patch Strategy (Template Rebases)
- Keep custom UI classes/layout where behavior is stable.
- Re-align structural WooCommerce contracts first:
  - form wrapper and method
  - canonical field IDs and accessibility labels
  - nonce handling via core helpers
- Avoid payment/auth/Supabase adjacent runtime edits in template-only rebases.
- Validate with checkout regression matrix before marking patch item closed.

### R-WOO-24 Remaining Patch Items
- `cart/cart.php`
- `single-product/related.php`
