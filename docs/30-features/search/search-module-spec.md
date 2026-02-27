# Search Module Specification (AS-IS)

## 1) Module Classification

- Domain: Search Domain
- Tier: Tier 1 (Runtime UX + query orchestration)
- Authority level: Presentation/read-only runtime layer
- Authority boundaries:
  - Search runtime MUST read product data from canonical WooCommerce/WordPress sources.
  - Search runtime MUST NOT define payment truth, order truth, entitlement truth, consent truth, or routing authority.
  - Search UI state CANNOT be treated as business authority.
- Couplings:
  - WooCommerce read-only coupling for product reads (`wc_get_product`, pricing HTML, image ID, permalink).
  - WordPress query coupling through `WP_Query` and taxonomy filters (`product_cat`, `product_type`).
  - Header runtime coupling for injection/enqueue lifecycle (`wp_body_open`, `wp_enqueue_scripts`).

## 2) File & Responsibility Map

### Admin settings surface

- `includes/modules/header/admin/settings-schema.php`
  - Defines option schema/defaults for `bw_header_settings`.
  - Owns merge + sanitize contract (`bw_header_default_settings`, `bw_header_get_settings`, `bw_header_sanitize_settings`).
  - Registers settings via `bw_header_register_settings`.

- `includes/modules/header/admin/header-admin.php`
  - Registers submenu `bw-header-settings` under `blackwork-site-settings`.
  - Renders Header admin UI; includes search-related fields (label, icon, mobile spacing).
  - Enqueues admin media script for icon upload.

- `includes/modules/header/admin/header-admin.js`
  - Handles media-picker upload/remove behavior and local admin tab switching.

### Frontend rendering + runtime

- `includes/modules/header/frontend/header-render.php`
  - Injects header via `wp_body_open`.
  - Builds search blocks via `bw_header_render_search_block()`.
  - Integrates `search-overlay.php` template in both desktop/mobile header surfaces.

- `includes/modules/header/templates/parts/search-overlay.php`
  - Search overlay DOM contract (form, input, results container, loading/message nodes).
  - Provides fallback form submit path (`method=get`, `action=home_url('/')`).

- `includes/modules/header/frontend/assets.php`
  - Enqueues search CSS/JS in frontend.
  - Localizes AJAX payload config (`bwSearchAjax.ajaxUrl`, `bwSearchAjax.nonce`).
  - Applies dynamic CSS tied to settings.

- `includes/modules/header/assets/js/bw-search.js`
  - Runtime controller (`BWSearchWidget`) for open/close, debounce, AJAX, result rendering.
  - Uses abort-on-new-request behavior and input-length gate (`>=2`).

- `includes/modules/header/assets/css/bw-search.css`
  - Search button, fullscreen overlay, loading/results grid, responsive layout styles.
  - Includes styles for category filter classes, even when filter controls are not rendered by current template.

- `includes/modules/header/assets/css/header-layout.css`
  - Header-level placement and responsive behavior affecting search block visibility/positioning.

### Backend endpoint

- `includes/modules/header/frontend/ajax-search.php`
  - Defines live search endpoint `bw_header_live_search_products()`.
  - Registers guest + authenticated AJAX hooks:
    - `wp_ajax_bw_live_search_products`
    - `wp_ajax_nopriv_bw_live_search_products`

### Related but non-primary search endpoint

- `metabox/digital-products-metabox.php`
  - Defines admin-only product search endpoint (`bw_search_products_ajax`) used by metabox Select2 flows.
  - This endpoint is NOT the header live-search runtime path.

## 3) Runtime Data Flow

### Entry points (UI)

1. User clicks `.bw-search-button` in desktop or mobile header block.
2. JS toggles overlay state (`.bw-search-overlay.is-active`).
3. User types in `.bw-search-overlay__input`.

### Request path

- Transport: WordPress AJAX (`admin-ajax.php`), not REST.
- Action: `bw_live_search_products`.
- Security token: nonce `bw_search_nonce`.
- JS source: `bw-search.js` sends:
  - `action`
  - `nonce`
  - `search_term`
  - `categories` (currently empty array in standard flow)

### Query build (server)

In `bw_header_live_search_products()`:
- Rejects short terms (`strlen(search_term) < 2`) with empty success payload.
- Builds `WP_Query`:
  - `post_type = product`
  - `post_status = publish`
  - `posts_per_page = 12`
  - `s = search_term`
- Optional tax filters if posted:
  - `product_cat` by slug from `categories[]`
  - `product_type` in allowed set (`simple`, `variable`, `grouped`, `external`)

### Response shape

`wp_send_json_success` payload:
- `products[]` entries include:
  - `id`
  - `title`
  - `price_html`
  - `image_url`
  - `permalink`
- `message` string for empty-state feedback.

### Render model

- Render surface: fullscreen search overlay in header module.
- Results UI: grid card rendering in `.bw-search-results__grid`.
- Not used in current flow:
  - dedicated search modal outside header
  - REST-driven search page
  - runtime initials/alphabet navigation bar

## 4) Filter Model (Current)

### Active filters in current runtime

- Search term filter:
  - Input source: overlay text input
  - Query mapping: `WP_Query['s'] = search_term`

### Supported by backend but not fully active in default UI flow

- Category filter (`categories[]`):
  - Server mapping: `tax_query` on `product_cat` by slug
  - Current frontend behavior: sends empty categories array by default

- Product type filter (`product_type`):
  - Server mapping: `tax_query` on `product_type`
  - Current frontend behavior: value not sent by default flow

### Current filter-to-query contract

- UI MUST be considered authoritative only for user input capture, not business truth.
- Query constraints MUST be derived from request payload processed in `bw_header_live_search_products()`.
- No price, stock, availability, custom meta, or attribute filters are applied in the primary live-search endpoint.

## 5) “Initial letter detection” Status (Current)

- Status: **Not implemented in current runtime path**.
- Verified as not present in:
  - `includes/modules/header/frontend/ajax-search.php`
  - `includes/modules/header/assets/js/bw-search.js`
  - `includes/modules/header/templates/parts/search-overlay.php`
  - Header search admin settings surface
- Consequence:
  - There is no runtime initials index, no alphabet grouping, and no initials-based query constraint in AS-IS behavior.

## 6) Performance & Caching (Current)

- Server-side caching:
  - No transient/object-cache layer specific to `bw_live_search_products` endpoint was found.
- Request-bound behavior:
  - Every debounced search request executes a fresh `WP_Query`.
- Client-side throttling:
  - Debounce: 300ms.
  - In-flight request abort on new input.
- Known hotspots:
  - Text search (`s`) on product catalog at high request frequency.
  - Repeated request bursts during active typing on large datasets.
- Response bound:
  - `posts_per_page = 12` limits payload size but does not eliminate query-frequency cost.

## 7) Security & Validation (Current)

### Admin settings

- Capability gate: `manage_options` for Header settings page.
- Settings API registration with sanitize callback (`bw_header_sanitize_settings`).

### AJAX endpoint

- Nonce check: `check_ajax_referer('bw_search_nonce', 'nonce')`.
- Input sanitization:
  - `search_term`: `sanitize_text_field`
  - `categories`: `array_map('sanitize_text_field', ...)`
  - `product_type`: `sanitize_text_field` + allowlist check
- Guest access:
  - `wp_ajax_nopriv_bw_live_search_products` is enabled by design.

### Output/data leakage constraints

- Endpoint returns product card-level public fields only (id/title/price_html/image/permalink).
- Search runtime MUST NOT expose session secrets, credentials, or non-public user metadata.

## 8) Failure Modes & Degrade-Safely

### Endpoint failure

- Behavior:
  - Client shows generic connection/search error message.
- Degrade-safe rule:
  - Overlay MUST remain closable and navigation MUST remain usable.

### JS failure

- Behavior:
  - Live AJAX search may not initialize.
- Degrade-safe rule:
  - Search form submit path (`GET ?s=` to home search route) MUST remain available.
  - Site navigation CANNOT be blocked by search JS failure.

### Empty results

- Behavior:
  - Endpoint returns empty product array + message.
  - UI shows empty-state message in results container.
- Degrade-safe rule:
  - No crash/no stuck loading state MUST occur for empty payloads.

### Missing/disabled search feature

- Behavior:
  - Header feature gating controls whether search block is rendered.
- Degrade-safe rule:
  - Missing search block MUST NOT break header/cart/account navigation surfaces.

## 9) Regression Checklist (Current)

1. Search overlay open/close (desktop + mobile) works via button, overlay click, and ESC.
2. Typing fewer than 2 characters does not trigger visible result rendering.
3. Typing 2+ characters triggers AJAX requests to `bw_live_search_products`.
4. Successful response renders product cards with title, image, price, and link.
5. Empty response renders empty-state message and no JS errors.
6. Network/error response renders error message and keeps UI interactive.
7. Form submit fallback (`Enter`) navigates to standard search route (`?s=`).
8. Guest user flow works (`wp_ajax_nopriv_bw_live_search_products`).
9. Nonce failure returns protected error path and does not expose sensitive data.
10. Header rendering in Elementor preview mode remains non-interfering (search injection respects existing header module guards).

## Cross-References

- Technical audit: `../../50-ops/audits/search-system-technical-audit.md`
- Runtime hooks contract: `../../50-ops/runtime-hook-map.md`
