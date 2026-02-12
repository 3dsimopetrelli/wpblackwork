# Header Custom Architecture (Post-Migration)

## Stato
Migrazione completata: i widget Elementor header legacy (`BW Search`, `BW NavShop`, `BW Navigation`) sono stati rimossi.

L'header ora e server-rendered tramite modulo custom in `includes/modules/header/`, mantenendo UX e classi BEM compatibili.

---

## File sorgente principali (attuali)

### Render/template header custom
- `includes/modules/header/frontend/header-render.php`
- `includes/modules/header/templates/header.php`
- `includes/modules/header/templates/parts/search-overlay.php`
- `includes/modules/header/templates/parts/mobile-nav.php`

### CSS
- `includes/modules/header/assets/css/bw-search.css`
- `includes/modules/header/assets/css/bw-navshop.css`
- `includes/modules/header/assets/css/bw-navigation.css`
- `includes/modules/header/assets/css/header-layout.css`

### JS
- `includes/modules/header/assets/js/bw-search.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `includes/modules/header/assets/js/bw-navigation.js`
- `includes/modules/header/assets/js/header-init.js`

### Bootstrap asset + AJAX live search
- `bw-main-elementor-widgets.php`
  - handler AJAX `bw_live_search_products` (contratto preservato)
- `includes/modules/header/frontend/assets.php`
  - enqueue asset header custom
  - localizzazione `bwSearchAjax` (`ajaxUrl`, `nonce`)

---

## Contratto funzionale attuale (visione d'insieme)

1. Il modulo header legge una configurazione unica (`bw_header_settings`).
2. Il render server-side genera markup desktop/mobile + overlay search/nav.
3. I CSS del modulo header forniscono layout, animazioni, reset visuali.
4. I JS del modulo header gestiscono interazioni UI:
   - Search overlay + live search AJAX
   - Cart popup trigger
   - Mobile nav drawer
5. Per Search live, backend WordPress risponde via `admin-ajax.php` (action `bw_live_search_products`).

---

## BW Search (header custom): contratto funzionale

## Responsabilita
- Bottone `Search` desktop.
- In mobile: testo -> icona (upload SVG custom + fallback lente).
- Overlay fullscreen con input, close, hint, filtri categorie opzionali.
- Live search AJAX prodotti WooCommerce.

## Markup/selector chiave
- Trigger: `.bw-search-button`
- Overlay: `.bw-search-overlay`
- Stato aperto: `.bw-search-overlay.is-active`
- Input: `.bw-search-overlay__input`
- Risultati: `.bw-search-results`, `.bw-search-results__grid`
- Filtri categoria: `.bw-category-filter`

## JS behavior chiave (`includes/modules/header/assets/js/bw-search.js`)
- Sposta overlay nel `body` per evitare clipping da parent container.
- Apertura/chiusura con classe `is-active`.
- `ESC` chiude overlay.
- Debounce 300ms live search.
- AJAX POST:
  - `action: bw_live_search_products`
  - `nonce: bwSearchAjax.nonce`
  - `search_term`
  - `categories` (slug array)
- Rendering card risultato con immagine, titolo, prezzo HTML.

## Backend AJAX (`bw-main-elementor-widgets.php`)
- Hook:
  - `wp_ajax_bw_live_search_products`
  - `wp_ajax_nopriv_bw_live_search_products`
- Sicurezza: `check_ajax_referer('bw_search_nonce', 'nonce')`
- Query `WP_Query` su `post_type=product`, `s=search_term`, max 12.
- Filtri tassonomie: `product_cat` e opzionale `product_type`.

## Dipendenze runtime
- Script `bw-search-script` dipende da `jquery`, `imagesloaded`, `masonry`.
- Localize object globale: `bwSearchAjax`.

---

## BW NavShop (header custom): contratto funzionale

## Responsabilita
- Link `Cart` + `Account`.
- Opzione ordine inverso (`reverse_order`).
- In mobile:
  - toggle visibilita account (`show_account_mobile` + breakpoint)
  - `Cart` testo -> icona (`cart_icon_mobile_breakpoint`)
  - SVG cart custom upload + size + color
- Badge quantita cart con posizione/stile custom.
- Opzione apertura Cart Pop-Up (`data-use-popup="yes"`).

## Markup/selector chiave
- Wrapper: `.bw-navshop`
- Item: `.bw-navshop__item`
- Cart: `.bw-navshop__cart`
- Label cart: `.bw-navshop__cart-label`
- Icon cart mobile: `.bw-navshop__cart-icon`
- Badge count: `.bw-navshop__cart-count`

## JS behavior chiave (`includes/modules/header/assets/js/bw-navshop.js`)
- Click su `.bw-navshop__cart[data-use-popup="yes"]`
  - se `window.BW_CartPopup.openPanel` esiste: apre popup
  - fallback: redirect href normale

## Woo fragments
- Filtro `woocommerce_add_to_cart_fragments`
- Aggiorna `.bw-navshop__cart .bw-navshop__cart-count` lato AJAX.

---

## BW Navigation (header custom): contratto funzionale

## Responsabilita
- Menu desktop e menu mobile separati (se mobile vuoto usa desktop).
- Mobile toggle icon custom upload (SVG), size/color/hover color.
- Breakpoint mobile configurabile per switch desktop/mobile.
- Overlay mobile a pannello:
  - slide da sinistra
  - maschera grigia fade in/out
  - close button stile cart popup
  - chiusura su click overlay, `ESC`, click link menu
  - animazione ingresso voci sequenziale

## Markup/selector chiave
- Root: `.bw-navigation`
- Desktop nav: `.bw-navigation__desktop`
- Toggle: `.bw-navigation__toggle`
- Overlay: `.bw-navigation__mobile-overlay`
- Panel: `.bw-navigation__mobile-panel`
- Close: `.bw-navigation__close`
- Mobile list item: `.bw-navigation__mobile .menu-item`
- Stato aperto: `.bw-navigation__mobile-overlay.is-open`

## JS behavior chiave (`includes/modules/header/assets/js/bw-navigation.js`)
- Inizializza istanze per ogni `.bw-navigation`.
- Setta `--bw-nav-stagger-delay` su ogni item mobile (70ms step).
- Toggle open/close + `aria-expanded`, `aria-hidden`.
- Blocca scroll body con classe `bw-navigation-mobile-open`.

---

## Nota storica

Questo documento nasce come analisi pre-migrazione. Le criticita indicate (allineamenti per-widget, breakpoint non sincronizzati, overlay limitato dai container) sono state affrontate nel modulo header custom.

---

## Mappa "parita funzionale" (checklist)

- [ ] Search bottone desktop + icona mobile custom
- [ ] Search overlay fullscreen con close, ESC, click fuori
- [ ] Live search AJAX con nonce + filtri categoria
- [ ] NavShop cart/account con reverse order
- [ ] Badge quantita cart con fragments WooCommerce
- [ ] Toggle account mobile on/off + breakpoint
- [ ] Cart testo->icona mobile + SVG custom + size/color
- [ ] Navigation desktop menu + mobile menu separato
- [ ] Drawer mobile: fade mask + slide panel + stagger items + close X
- [ ] Breakpoint unificato o chiaramente gerarchizzato

---

## Stato migrazione

Completato:
1. Render custom header server-side.
2. Search/NavShop/Navigation portati in modulo dedicato.
3. Contratto AJAX live search preservato.
4. Trigger Cart Pop-up preservato.
5. Rimozione widget Elementor header legacy.

---

## Nota operativa

Per ulteriori evoluzioni usare come baseline:
- `includes/modules/header/assets/js/bw-search.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `includes/modules/header/assets/js/bw-navigation.js`
- `includes/modules/header/frontend/assets.php`
