# BW Related Products Widget

## Purpose

`bw-related-products` è il widget Elementor per visualizzare prodotti correlati su template single-product.

Obiettivi principali:
- riutilizzare `BW_Product_Card_Component` come authority della card prodotto (no logica duplicata)
- griglia proporzionale: le immagini si scalano alla loro proporzione naturale senza altezza fissa
- controlli minimali nell'editor Elementor, evitando duplicazione dei settings già gestiti dal component
- live preview reattivo nel canvas Elementor senza richiedere una pubblicazione

## Runtime Authority

File widget:
- `includes/widgets/class-bw-related-products-widget.php`

Component card:
- `includes/components/product-card/class-bw-product-card-component.php`

Asset CSS:
- `assets/css/bw-product-card.css` (stile card — dichiarato via `get_style_depends`)
- `assets/css/bw-related-products.css` (layout griglia — dichiarato via `get_style_depends`)

Baseline typography inherited from the shared component when Elementor style controls are not explicitly set:
- titolo `14px`
- descrizione `14px`
- prezzo `12px`

## Widget Contract

Slug:

```
bw-related-products
```

Visible title:

```
BW-SP Related Products
```

Categoria Elementor:

```
blackwork
```

## Editor Controls

### Sezione Content

| Controllo | Tipo | Default | Note |
|---|---|---|---|
| `query_by` | SELECT | `category` | Modalità di query: Category, Subcategory, Tag |
| `posts_per_page` | NUMBER | `4` | Numero di prodotti correlati (1–12) |
| `show_title` | SWITCHER | `yes` | Mostra il titolo del prodotto |
| `show_description` | SWITCHER | off | Mostra la descrizione breve |
| `show_price` | SWITCHER | `yes` | Mostra il prezzo |

### Sezione Layout

| Controllo | Tipo | Default | Note |
|---|---|---|---|
| `columns` | SELECT | `4` | Colonne desktop (1–6). Tablet e mobile: sempre 2 (CSS hardcodato). Live preview reattivo via `selectors`. |
| `gap` | SLIDER (responsive) | `24px` | Gap tra le card. |
| `show_mobile_overlay_actions` | SWITCHER | off | Mostra/nasconde su tablet + mobile (`<1025px`) l'overlay `View Product / Add to Cart`. Desktop resta invariato. |

### Sezione Style > Typography

| Controllo | Note |
|---|---|
| `title_color`, `title_typography`, `title_margin_top/bottom` | Stile del titolo della card |
| `description_color`, `description_typography`, `description_margin_top/bottom` | Stile della descrizione |
| `price_color`, `price_typography`, `price_margin_top/bottom` | Stile del prezzo |

## Controlli Rimossi (2026-03)

I seguenti controlli sono stati eliminati in favore di default fissi delegati al component:

| Rimosso | Motivazione |
|---|---|
| **Image Settings** (show image, size, height, border radius, object-fit, background, hover effect) | Duplicazione: il component gestisce già tutti questi aspetti. Hardcodato: `image_mode: proportional`, `show_image: true`, `show_hover_image: true`. |
| `open_cart_popup` | Rimosso dalla UI. Hardcodato a `false` nel render. |
| `margin_top` / `margin_bottom` | Gestiti tramite padding del contenitore Elementor nativo. |
| **Sezione Style > Overlay Buttons** (colori, raggio, padding pulsanti) | Stile overlay delegato ai default di `bw-product-card.css`. |
| **Sezione Style > Card Container** (padding, background) | Stile container delegato ai default di `bw-product-card.css`. |

## Rendering Behavior

### Contesto prodotto

Il widget risolve il prodotto corrente in questo ordine:

1. `global $product` — se è una pagina prodotto reale
2. `bw_tbl_resolve_product_context_id()` — se si è in un template Theme Builder Lite single-product con preview product configurato
3. Fallback editor: se nessun prodotto è disponibile e si è in `is_edit_mode()`, vengono mostrati gli ultimi prodotti pubblicati

In frontend senza prodotto corrente: il widget non renderizza nulla (silent return).

### Query correlati

La modalità di query è selezionata tramite `query_by`:

- `category` — usa `wc_get_related_products()` (WooCommerce native)
- `subcategory` — query su `product_cat` con sottocategorie (categorie figlie)
- `tag` — query su `product_tag`

### Card output

Ogni card è renderizzata da `BW_Product_Card_Component::render()` con questi settings fissi:

```php
[
    'image_mode'       => 'proportional',  // immagine a proporzione naturale
    'show_image'       => true,
    'show_hover_image' => true,
    'show_buttons'     => true,            // overlay View / Add to Cart
    'show_add_to_cart' => true,
    'open_cart_popup'  => false,           // no cart popup
]
```

I campi `show_title`, `show_description`, `show_price` vengono passati dinamicamente dai settings dell'editor.

### Overlay tablet/mobile

Il widget non modifica globalmente il `BW_Product_Card_Component`.

Il controllo `show_mobile_overlay_actions` agisce localmente così:
- il wrapper emette la classe `bw-related-products-widget--mobile-overlay-off` quando il toggle è spento
- `assets/css/bw-related-products.css` nasconde l'overlay CTA sotto `1025px`

Questo mantiene:
- desktop invariato
- tablet governato dal toggle
- mobile governato dal toggle
- nessun effetto collaterale sugli altri widget che riusano il component

## Griglia CSS

### Struttura HTML

```html
<div class="bw-related-products-widget">
  <div class="bw-related-products-grid">
    <article class="bw-wallpost-item bw-product-card-item ...">
      ...
    </article>
  </div>
</div>
```

### CSS custom property

La colonna desktop è governata dalla CSS custom property `--bw-rp-columns`, iniettata da Elementor tramite il `selectors` del controllo `columns`:

```css
.elementor-element-XXXXX .bw-related-products-grid {
  --bw-rp-columns: 4; /* valore impostato nell'editor */
}
```

Tablet e mobile sono hardcodati nel CSS del widget e **non sovrascrivibili** dall'editor Elementor:

```css
@media (max-width: 1024px) {
  .bw-related-products-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 767px) {
  .bw-related-products-grid { grid-template-columns: repeat(2, 1fr); }
}
```

### Immagini proporzionali

Il controllo `align-items: start` sulla griglia CSS impedisce alle card di essere stirate ad altezza uguale tra loro. L'immagine mantiene il suo aspect ratio naturale.

Il component imposta `image_mode: 'proportional'` che aggiunge la classe `bw-product-card-image-el--proportional` all'`<img>`, gestita in `bw-product-card.css`:

```css
.bw-product-card img.bw-product-card-image-el--proportional {
  object-fit: contain;
}
```

## Live Preview Elementor

Il controllo `columns` usa `selectors` per iniettare direttamente CSS nel canvas dell'editor al cambio di valore:

```php
'selectors' => [
    '{{WRAPPER}} .bw-related-products-grid' => '--bw-rp-columns: {{VALUE}};',
],
```

**Regola critica**: il render PHP **non deve** impostare `--bw-rp-columns` tramite `style=""` inline sull'elemento HTML. L'inline style ha specificità più alta del CSS iniettato da Elementor e bloccherebbe il live preview, richiedendo una pubblicazione per vedere l'aggiornamento.

La variabile CSS è sufficiente anche in frontend: Elementor la genera nel foglio CSS dell'elemento al salvataggio.

## Dipendenze

- `BW_Product_Card_Component` (PHP) — authority rendering card
- `bw-product-card-style` (CSS handle) — stile della card
- `bw-related-products-style` (CSS handle) — layout griglia
- `WooCommerce` — `wc_get_product()`, `wc_get_related_products()`, `WC_Product`
- `bw_tbl_resolve_product_context_id()` (opzionale) — contesto prodotto in preview Theme Builder Lite

## Limitazioni note

- Non esiste un `content_template()` JS: il canvas Elementor mostra sempre un re-render PHP completo (tramite AJAX di Elementor) al cambio di controlli che modificano l'HTML (show_title, show_description, show_price, query_by).
- Il controllo `columns` usa `selectors` ed è quindi live senza re-render.
- La query correlati con `subcategory` restituisce array vuoto se il prodotto corrente appartiene solo a categorie radice (nessuna sottocategoria).
