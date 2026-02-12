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

Breakpoint unificato:
- `breakpoints.mobile` (gestito con inline CSS generato in `frontend/assets.php`).

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
- `bw-main-elementor-widgets.php`

Elementor widget code esistente non viene rimosso in questa fase.
