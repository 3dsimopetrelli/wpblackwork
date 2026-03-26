# BW Custom Header Architecture

Questo documento descrive l'architettura del nuovo modulo header custom (senza dipendenza da Elementor), mantenendo UX e classi dei widget header esistenti (`BW Search`, `BW NavShop`, `BW Navigation`).

## Obiettivo
- Render server-side dell'header.
- Riuso massimo di CSS/JS esistenti (`bw-search*`, `bw-navshop*`, `bw-navigation*`).
- Mantenere intatto il Cart Pop-up custom: il click su cart deve continuare a usare `window.BW_CartPopup.openPanel()` con fallback al link.

## Struttura Modulo
- `includes/modules/header/header-module.php`
- `includes/modules/header/admin/settings-schema.php`
- `includes/modules/header/admin/header-admin.php`
- `includes/modules/header/admin/header-admin.js`
- `includes/modules/header/frontend/assets.php`
- `includes/modules/header/frontend/header-render.php`
- `includes/modules/header/frontend/fragments.php`
- `includes/modules/header/frontend/ajax-search.php`
- `includes/modules/header/helpers/menu.php`
- `includes/modules/header/helpers/svg.php`
- `includes/modules/header/templates/header.php`
- `includes/modules/header/templates/parts/search-overlay.php`
- `includes/modules/header/templates/parts/mobile-nav.php`
- `includes/modules/header/assets/css/header-layout.css`
- `includes/modules/header/assets/css/bw-search.css`
- `includes/modules/header/assets/css/bw-navshop.css`
- `includes/modules/header/assets/css/bw-navigation.css`
- `includes/modules/header/assets/js/header-init.js`
- `includes/modules/header/assets/js/bw-search.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `includes/modules/header/assets/js/bw-navigation.js`

## Admin
Menu in WP Admin:
- `Blackwork Site`
  - `Settings` (esistente)
  - `Header` (nuovo)

Option unica: `bw_header_settings`
- `enabled`
- `header_title`
- `hero_overlap.enabled`
- `hero_overlap.page_ids`
- `logo_attachment_id`
- `logo_width`
- `logo_height`
- `menus.desktop_menu_id`
- `menus.mobile_menu_id`
- `breakpoints.mobile` (default 1024)
- `icons.mobile_hamburger_attachment_id`
- `icons.mobile_search_attachment_id`
- `icons.mobile_cart_attachment_id`
- `labels.search`
- `labels.account`
- `labels.cart`
- `links.account`
- `links.cart`

Tab reali in `Blackwork Site > Header`:
- `General`
- `Header Scroll`
- `Hero Overlap`

Nota di robustezza UI:
- Il cambio tab ha fallback server-side via query arg `tab`, quindi i pannelli restano navigabili anche se il JS admin non si inizializza.

## Frontend contract
Classi principali preservate:
- Search: `.bw-search-button`, `.bw-search-overlay`, `.bw-search-results*`
- Navigation: `.bw-navigation`, `.bw-navigation__toggle`, `.bw-navigation__mobile-overlay`, `.bw-navigation__mobile-panel`, `.bw-navigation__link`
- NavShop: `.bw-navshop`, `.bw-navshop__cart`, `.bw-navshop__cart-icon`, `.bw-navshop__cart-count`, `.bw-navshop__account`

Data attribute critico Cart Pop-up:
- `a.bw-navshop__cart[data-use-popup="yes"]`

## Rendering
Hook frontend:
- `wp_body_open` (priority 5) in `includes/modules/header/frontend/header-render.php`

Layout default:
- Desktop: logo sinistra, nav + search al centro, account + cart a destra.
- Mobile: hamburger sinistra, logo centro, search + cart a destra.
- `Hero Overlap`: l'header vive sopra la prima sezione, mantiene il wrapper trasparente e usa lo stesso rilevamento dark-zone esistente per attivare lo stato bianco su hero scure.

Breakpoint unificato:
- `breakpoints.mobile` (gestito con inline CSS generato in `frontend/assets.php`).

Contract runtime recente:
- Il template espone `data-hero-overlap="yes|no"` per il boot JS.
- `header-init.js` tratta `Hero Overlap` come variante sticky/fixed anche senza Smart Scroll attivo.
- L'header overlay viene nascosto durante il preload e rivelato dopo i recheck di layout (`requestAnimationFrame`, `window.load`, `document.fonts.ready`) per evitare il flash iniziale con header bianco e hero non ancora assestata.
- In `Hero Overlap`, il panel blur viene forzato anche in mobile; il bordo aggiunto durante il tuning iniziale e' stato rimosso, resta solo il glass.
- Le icone mobile (hamburger, search, cart) condividono la stessa logica dark-zone del logo.

## JS behavior
- `bw-navshop.js`: click cart -> `BW_CartPopup.openPanel()` se disponibile, fallback `window.location.href`.
- `bw-navigation.js`: apertura/chiusura pannello mobile, ESC, click overlay, close su click link.
- `bw-search.js`: overlay search + live search AJAX.

## AJAX + Fragments
- Live search: contratto invariato
  - action: `bw_live_search_products`
  - nonce: `bw_search_nonce`
  - endpoint: `admin-ajax.php`
- Woo fragments badge cart:
  - filter: `woocommerce_add_to_cart_fragments`
  - selector aggiornato: `.bw-navshop__cart .bw-navshop__cart-count`

## Integrazione bootstrap
Il modulo viene incluso da:
- `blackwork-core-plugin.php`

Elementor widget code esistente non viene rimosso in questa fase.

## Post-Migration Functional Notes

Integrazione consolidata dalla documentazione post-migrazione:
- Contratto Search invariato (`bw_live_search_products`, nonce `bw_search_nonce`, `admin-ajax.php`).
- Overlay search spostato nel `body` per evitare clipping da container parent.
- NavShop mantiene trigger Cart Pop-up con fallback a redirect.
- Navigation mobile mantiene chiusura su overlay, `ESC` e click link.
- Breakpoint desktop/mobile governato da `bw_header_settings.breakpoints.mobile`.

Parita funzionale verificata sui blocchi principali:
- Search overlay fullscreen + live results.
- NavShop account/cart con badge Woo fragments.
- Navigation desktop + drawer mobile.

## 2026-03 Hardening Notes

Task closeout `Hero Overlap` V1:
- selezione pagine dal pannello Header
- startup overlay stabile senza jump iniziale
- rilevazione dark-zone riusata, non duplicata
- glass presente anche in mobile overlay
- icone mobile allineate al cambio colore del logo
- `Search` desktop mantenuto nero sul bottone verde
