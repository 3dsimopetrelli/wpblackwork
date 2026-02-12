# Header Widgets Architecture (BW Search, BW NavShop, BW Navigation)

## Obiettivo
Questo documento descrive l'architettura attuale dei 3 widget header usati in Elementor:
- `BW Search`
- `BW NavShop`
- `BW Navigation`

Scopo: usare questo contesto per costruire una versione **header custom** (senza Elementor), mantenendo le stesse funzionalita e UX.

---

## File sorgente principali

### Widget PHP (controlli + render)
- `includes/widgets/class-bw-search-widget.php`
- `includes/widgets/class-bw-navshop-widget.php`
- `includes/widgets/class-bw-navigation-widget.php`

### CSS
- `assets/css/bw-search.css`
- `assets/css/bw-navshop.css`
- `assets/css/bw-navigation.css`

### JS
- `assets/js/bw-search.js`
- `assets/js/bw-navshop.js`
- `assets/js/bw-navigation.js`

### Bootstrap asset + AJAX live search
- `bw-main-elementor-widgets.php`
  - register/enqueue script/style dei 3 widget
  - localizzazione `bwSearchAjax` (`ajaxUrl`, `nonce`)
  - handler AJAX `bw_live_search_products`

---

## Architettura attuale (visione d'insieme)

1. Ogni widget Elementor registra controlli (Content/Style).
2. Il `render()` genera markup HTML + blocchi `<style>` inline per breakpoint per-widget.
3. I CSS globali dei widget forniscono layout, animazioni, reset visuali.
4. I JS dei widget gestiscono interazioni UI:
   - Search overlay + live search AJAX
   - Cart popup trigger
   - Mobile nav drawer
5. Per Search live, backend WordPress risponde via `admin-ajax.php` (action `bw_live_search_products`).

Per il custom header, questi 5 punti vanno replicati senza dipendere dalle API di Elementor.

---

## BW Search: contratto funzionale

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

## JS behavior chiave (`assets/js/bw-search.js`)
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

## BW NavShop: contratto funzionale

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

## JS behavior chiave (`assets/js/bw-navshop.js`)
- Click su `.bw-navshop__cart[data-use-popup="yes"]`
  - se `window.BW_CartPopup.openPanel` esiste: apre popup
  - fallback: redirect href normale

## Woo fragments
- Filtro `woocommerce_add_to_cart_fragments`
- Aggiorna `.bw-navshop__cart .bw-navshop__cart-count` lato AJAX.

---

## BW Navigation: contratto funzionale

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

## JS behavior chiave (`assets/js/bw-navigation.js`)
- Inizializza istanze per ogni `.bw-navigation`.
- Setta `--bw-nav-stagger-delay` su ogni item mobile (70ms step).
- Toggle open/close + `aria-expanded`, `aria-hidden`.
- Blocca scroll body con classe `bw-navigation-mobile-open`.

---

## Criticita emerse (importanti per il custom header)

1. Layout conflittuale da settings per-widget
- In Elementor, `Widget Position` dei singoli widget puo applicare `margin-left: auto` e "rompere" il layout globale.
- Nel custom header va evitato: il posizionamento deve essere centralizzato nel layout header, non sui singoli blocchi.

2. Breakpoint non sincronizzati
- `BW Navigation`, `BW Search`, `BW NavShop` hanno breakpoint indipendenti.
- Se diversi, tablet/mobile mostrano comportamenti incoerenti.
- Nel custom header usare una mappa breakpoint unica.

3. Overlay Search
- Deve essere fuori dai container (`appendTo(body)` attuale).
- Nel custom header va montato gia a livello root (fuori header) per evitare overflow clipping.

---

## Proposta architettura custom header (senza Elementor)

## 1) Config centrale (PHP)
Creare una configurazione unificata (DB option o file PHP) con sezioni:
- `navigation`
- `search`
- `navshop`
- `breakpoints`

Esempio campi minimi:
- `breakpoints.mobile` (unico)
- `search.mobile_icon_breakpoint`
- `navshop.cart_icon_mobile_breakpoint`
- `navshop.account_mobile_breakpoint`
- `navigation.mobile_breakpoint`

Nel custom header impostare default coerenti e, idealmente, derivarli tutti da `breakpoints.mobile`.

## 2) Render server-side
Creare template custom (es. `templates/header/custom-header.php`) che renderizza:
- logo
- blocco nav
- blocco search
- blocco cart/account
- overlay search (fuori dalla navbar)
- overlay navigation mobile

## 3) JS modulare
Riutilizzare logica esistente con minime refactor:
- `SearchController` (open/close/live search)
- `NavigationController` (drawer mobile)
- `NavshopController` (cart popup trigger)

## 4) CSS per layout header unico
- Un solo layout system (flex o grid) deciso a livello header.
- Eliminare logiche di auto-margin "per-widget".
- Mantenere classi BEM attuali per compatibilita.

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

## Sequenza consigliata di migrazione

1. Estrarre config corrente Elementor in un array standard (per confronto).
2. Implementare custom render statico dell'header (senza logiche).
3. Portare Search overlay + AJAX.
4. Portare NavShop + fragments badge + cart popup trigger.
5. Portare Navigation mobile drawer.
6. Allineare animazioni e breakpoint.
7. Rimuovere dipendenze Elementor per l'header.

---

## Nota per Codex (contesto operativo)

Quando si sviluppa la versione custom:
- considerare `assets/js/bw-search.js`, `assets/js/bw-navshop.js`, `assets/js/bw-navigation.js` come baseline funzionale;
- preservare classi CSS principali per ridurre regressioni;
- centralizzare i breakpoint in un punto solo;
- non usare regole di posizionamento basate su `{{WRAPPER}}`/auto margin per-widget.

