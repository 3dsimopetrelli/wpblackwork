# Header System Technical Audit

## Executive Summary
Il sistema header del plugin ha due percorsi runtime:

1. **Percorso principale attuale (Custom Header Module)** sotto `includes/modules/header/*`, attivato da opzione `bw_header_settings[enabled]`.
2. **Percorso legacy Smart Header** (`assets/js/bw-smart-header.js` + `assets/css/bw-smart-header.css`) caricato solo quando il modulo custom **non** e abilitato.

Il modulo custom renderizza un header desktop+mobile proprio, gestisce Smart Scroll (hide/show + scrolled state + dark-zone detection), search overlay AJAX, menu mobile off-canvas e cart badge WooCommerce fragments. La surface admin e "Blackwork Site -> Header", con storage unico in `wp_options` (`bw_header_settings`).

---

## 1) File Inventory

### PHP (rendering/hooks/admin/templates)
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/header-module.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/assets.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/header-render.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/fragments.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/ajax-search.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/helpers/menu.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/helpers/svg.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/settings-schema.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/header.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/parts/mobile-nav.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/parts/search-overlay.php`

Legacy/fallback:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/bw-main-elementor-widgets.php` (`bw_enqueue_smart_header_assets`)

### JS
Custom header:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/header-init.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-navigation.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-navshop.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-search.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.js`

Legacy smart header:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-smart-header.js`

### CSS
Custom header:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/header-layout.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-navigation.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-navshop.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-search.css`

Legacy:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-smart-header.css`

### Assets (icons/images/svg)
- Icone default header sono **inline SVG** in PHP (`bw_header_default_*_svg`).
- Close icon mobile nav e **data URI SVG** in CSS (`bw-navigation.css`).
- Logo/hamburger/search/cart custom caricabili via **Media Library attachment IDs** (non file statici fissi del modulo).

### Enqueue + conditional loading
- Custom frontend enqueue: `bw_header_enqueue_assets()` su `wp_enqueue_scripts` priority 20.
  - Condizioni: `!is_admin()`, `bw_header_is_enabled() === true`, non Elementor preview.
  - Dequeue legacy handles `bw-*-style/script`, `bw-smart-header-style/script`.
- Custom admin enqueue: `bw_header_admin_enqueue_assets()` su `admin_enqueue_scripts`.
  - Solo quando `$_GET['page'] === 'bw-header-settings'`.
- Legacy enqueue: `bw_enqueue_smart_header_assets()` su `wp_enqueue_scripts`.
  - Salta se custom header enabled.
  - Salta in admin e Elementor preview.

---

## 2) Admin Panel / Settings Surface

Admin page:
- **Blackwork Site -> Header**
- `add_submenu_page('blackwork-site-settings', ..., 'bw-header-settings', capability 'manage_options', ...)`
- Storage: `wp_options`, option key **`bw_header_settings`** (`BW_HEADER_OPTION_KEY`)
- Sanitization: `bw_header_sanitize_settings`
- Register: `register_setting('bw_header_settings_group', BW_HEADER_OPTION_KEY, ...)`
- Nonce: standard WordPress `settings_fields()` / `options.php` flow.

Field map (key -> default -> type -> storage/effect):
- `enabled` -> `0` -> checkbox -> `wp_options`; abilita render custom header.
- `header_title` -> `Blackwork Header` -> text -> `aria-label` header.
- `background_color` -> `#efefef` -> color -> bg base (solo se smart scroll OFF).
- `background_transparent` -> `0` -> checkbox -> bg transparente generale.
- `inner_padding_unit` -> `px` -> select(px/% ) -> padding desktop container.
- `inner_padding[top/right/bottom/left]` -> `18/28/18/28` -> number -> padding `.bw-custom-header__inner`.
- `logo_attachment_id` -> `0` -> media hidden ID -> logo output.
- `logo_width` -> `54` -> number.
- `logo_height` -> `54` -> number.
- `menus[desktop_menu_id]` -> `0` -> select menu.
- `menus[mobile_menu_id]` -> `0` -> select menu (fallback desktop).
- `breakpoints[mobile]` -> `1024` -> number -> switch desktop/mobile states.

Mobile layout:
- `mobile_layout[right_icons_gap]` -> `16` number.
- `mobile_layout[inner_padding][t/r/b/l]` -> `14/18/14/18` number.
- `mobile_layout[hamburger_padding][t/r/b/l]` -> `0`.
- `mobile_layout[hamburger_margin][t/r/b/l]` -> `0`.
- `mobile_layout[search_padding][t/r/b/l]` -> `0`.
- `mobile_layout[search_margin][t/r/b/l]` -> `0`.
- `mobile_layout[cart_padding][t/r/b/l]` -> `0`.
- `mobile_layout[cart_margin][t/r/b/l]` -> `0`.
- `mobile_layout[cart_badge_offset_x/y]` -> `0/0`.
- `mobile_layout[cart_badge_size]` -> `1.2`.
- `mobile_layout[desktop_cart_badge_offset_x/y]` -> `0/0`.
- `mobile_layout[desktop_cart_badge_size]` -> `1.2`.

Icons:
- `icons[mobile_hamburger_attachment_id]` -> `0`.
- `icons[mobile_search_attachment_id]` -> `0`.
- `icons[mobile_cart_attachment_id]` -> `0`.

Labels/links:
- `labels[search/account/cart]` -> `Search/Account/Cart`.
- `links[account/cart]` -> `/my-account/` `/cart/`.

Feature flags:
- `features[search]` -> `1` (non esposto direttamente nella UI attuale; preservato da sanitize se non postato).
- `features[navigation]` -> `1` (idem).
- `features[navshop]` -> `1` (idem).
- `features[smart_scroll]` -> `0` (tab Header Scroll).

Smart Header Scroll tab:
- `smart_header[scroll_down_threshold]` -> `100` number.
- `smart_header[scroll_up_threshold]` -> `0` number.
- `smart_header[scroll_delta]` -> `1` number.
- `smart_header[header_bg_color]` -> `#efefef` color.
- `smart_header[header_bg_opacity]` -> `1` range+number.
- `smart_header[header_scrolled_bg_color]` -> `#efefef` color.
- `smart_header[header_scrolled_bg_opacity]` -> `0.86` range+number.
- `smart_header[menu_blur_enabled]` -> `1` checkbox.
- `smart_header[menu_blur_amount]` -> `20` number.
- `smart_header[menu_blur_radius]` -> `12` number.
- `smart_header[menu_blur_tint_color]` -> `#ffffff` color.
- `smart_header[menu_blur_tint_opacity]` -> `0.15` range+number.
- `smart_header[menu_blur_scrolled_tint_color]` -> `#ffffff` color.
- `smart_header[menu_blur_scrolled_tint_opacity]` -> `0.15` range+number.
- `smart_header[menu_blur_padding_top/right/bottom/left]` -> `5/10/5/10` number.

---

## 3) Frontend Rendering Contract

- Entry point: `bw_header_render_frontend()` hooked on `wp_body_open` (priority 5).
- Theme header override:
  - `bw_header_disable_theme_header()` on `wp` removes `hello_elementor_render_header` if present.
  - `bw_header_theme_header_fallback_css()` on `wp_head` hides typical theme header selectors.
- Render conditions: non admin, non ajax/feed/embed, non Elementor preview, `enabled=1`.
- Template used: `includes/modules/header/templates/header.php`.
- Menu output: `wp_nav_menu()` via `bw_header_render_menu()`, with injected link class `bw-navigation__link`.
- Logo/icons: media attachment or fallback inline SVG.
- Cart/account links:
  - account URL from settings or default `/my-account/`.
  - cart URL from settings or default `/cart/`.
  - cart count from Woo `WC()->cart->get_cart_contents_count()`.
  - cart popup intent via `data-use-popup="yes"` (JS opens `window.BW_CartPopup` if available).

---

## 4) Smart Header Scroll Model (state machine)

### Trigger model (custom header path)
Source: `header-init.js` + localized `bwHeaderConfig`.

Inputs:
- `scrollDownThreshold`, `scrollUpThreshold`, `scrollDelta`, `breakpoint`.
- Current scroll position/direction.
- Dark-zone overlap detection (manual `.smart-header-dark-zone` + auto background analysis).

### States
- `top/reset`: near top (`<=2px`), removes hidden/visible transition classes.
- `visible`: class `bw-header-visible`.
- `hidden`: class `bw-header-hidden`.
- `scrolled`: class `bw-header-scrolled` if `scrollTop > 2`.
- `on-dark-zone`: class `bw-header-on-dark` when overlap >=30%.
- responsive mode class: `is-mobile` / `is-desktop`.

### State transitions
- On load: `boot()` ensures header in body, sets mobile/desktop class, initializes sticky logic, removes preload class.
- On scroll:
  - If top: reset.
  - If below activation point (`max(headerHeight, scrollDownThreshold)`): stays visible logic.
  - If past activation:
    - scrolling down by delta > `scrollDelta` => hidden.
    - scrolling up with `upDelta >= scrollUpThreshold` => visible.
- On resize: recompute offsets and state classes.
- On dark-zone observer callback/scroll: toggles `bw-header-on-dark`.

### What changes per state
- CSS classes only (no payment/business mutation).
- `bw-header-scrolled` drives background style swap.
- Hidden/visible drives `transform: translateY(...)`.
- Inline CSS (from PHP) controls smart bg/scrolled bg opacity and blur panel values.

### Precedence “General” vs “Header Scroll”
- If `features.smart_scroll=1`: smart header colors/opacities override general background.
- If smart scroll off: uses `background_transparent` / `background_color`.
- Mobile scrolled white override forced via high-specificity inline media rule.

### Exact JS events/handlers (custom path)
- `DOMContentLoaded` -> `boot()`.
- `window.scroll` (passive + rAF) -> `onScroll()`.
- `window.resize` -> recalc + responsive class + dark overlap check.
- `IntersectionObserver` callback -> dark-zone overlap evaluation.

Legacy path (when custom header disabled):
- `assets/js/bw-smart-header.js` with jQuery-ready init, `window.scroll`, `window.resize`, `window.load`, `beforeunload`, optional `elementor/frontend/init`.
- Classes: `.visible`, `.hidden`, `.scrolled`, `.smart-header--on-dark`.

---

## 5) Responsive Header Model

- Breakpoint source:
  - Dynamic option `breakpoints.mobile` (default 1024) used by JS and inline CSS.
  - Static CSS also includes media at `1024`, `769`, `768`.
- Markup strategy:
  - **Duplicated structures**: separate desktop block (`.bw-custom-header__desktop`) and mobile block (`.bw-custom-header__mobile`) in same template.
- Mobile menu behavior:
  - Off-canvas overlay panel (`.bw-navigation__mobile-overlay` + `.bw-navigation__mobile-panel`).
  - Toggle button opens/closes; close on overlay click, ESC, link click.
- Mobile toggle JS:
  - `bw-navigation.js` binds toggle/close/overlay/document keydown/link click.
  - Moves overlay to `<body>` to avoid transformed parent constraints.
- Clone/double-binding risk:
  - Navigation guarded by `data-bw-navigation-initialized`.
  - Search widget guarded per root via `data('bw-search-initialized')`, but each instance adds global `document keydown` listener; multiple widgets = multiple keydown handlers (functional ma potenziale overhead).

---

## 6) Coupling / Dependencies

- Elementor:
  - Enqueue skip in preview mode.
  - Theme header removal specifically for Hello Elementor hook.
- WooCommerce:
  - Cart count read from `WC()->cart`.
  - Fragment sync via `woocommerce_add_to_cart_fragments`.
  - Search AJAX queries `product` + `wc_get_product`.
  - Mobile auth link fallback to `wc_get_page_permalink('myaccount')`.
- Cart popup integration:
  - `bw-navshop.js` depends on global `window.BW_CartPopup.openPanel()`, fallback to cart URL.
- Shared runtime:
  - jQuery used by search/navshop/admin and legacy smart header.
  - Native JS in `header-init.js` + `bw-navigation.js`.
  - `IntersectionObserver` optional optimization with fallback check logic.

---

## 7) Risk & Regression Hotspots

High-risk hotspots:
- Global hooks affecting all frontend pages:
  - `wp_body_open` render injection.
  - `wp` header disable theme hook.
  - `wp_head` fallback CSS hide.
- Smart scroll listeners:
  - Scroll + resize + dark-zone detection on all pages.
- Dual-system coexistence:
  - Custom header + legacy smart header handles/dequeue order; potential conflicts if custom enable detection fails.
- Duplicated desktop/mobile markup:
  - parallel elements (cart/search/account) can divergere styling/behavior.
- Settings precedence ambiguity:
  - General bg vs smart scroll bg vs mobile forced white override.
- CSS specificity:
  - heavy use of `!important` + inline CSS may collide with theme/Elementor styles.
- Minor code bug in legacy cleanup:
  - `removeEventListener('resize', throttledScrollHandler)` appears mismatched handler name (legacy script only).

Short regression checklist:
- Desktop header render and menu dropdown.
- Mobile header render and off-canvas open/close (toggle, overlay click, ESC, link click).
- Smart scroll on/off transitions (top, down hide, up show, scrolled background).
- Page refresh at mid-scroll.
- Mobile scrolled background override.
- Search overlay open/close, live AJAX results, ESC close.
- Cart icon count sync after add-to-cart (fragment update).
- Cart icon click with/without cart-popup global.
- Account/cart link correctness.
- Elementor preview/editor (header not wrongly injected).

---

## 8) Unknowns / where to look

- Se in produzione esistono override tema/plugin su selettori header non presenti qui: **unknown**.
  - Verifica in theme files e in eventuali mu-plugins.
- Se sono presenti runtime asset media specifici (logo/icon attachments): **unknown** nel repo perche dipendono da upload DB/media library.
  - Verifica in `wp_posts/wp_postmeta` attachments e option `bw_header_settings`.
