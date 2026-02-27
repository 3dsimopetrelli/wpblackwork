# Search System — Technical Audit (Current Implementation)

## 1) Executive Summary

The current Blackwork search runtime is a **header overlay live search for WooCommerce products**. It is implemented inside the custom Header module and uses `admin-ajax.php` (not REST) for live results.

Admin configuration is not exposed as a dedicated top-level “Search” tab; it is currently managed through:
- **Blackwork Site → Header** submenu page slug: `bw-header-settings`
- Header admin tabs: `General` and `Header Scroll`
- Search-related controls are in the Header `General` tab (label/icon/mobile spacing), while live-search query behavior is mostly hardcoded in runtime.

Main runtime surfaces:
- Frontend search trigger + overlay markup (header template)
- Frontend JS widget (`bw-search.js`) with debounce + AJAX
- AJAX endpoint (`bw_live_search_products`) querying WooCommerce products

Main risks observed:
- Performance risk on large catalogs due to uncached `WP_Query` text search per request
- Relevance limitations due to reliance on native `s` query + fixed `posts_per_page=12`
- Partial filter drift: backend accepts category/type filters, frontend currently sends no active category/type constraints
- No verified implementation found for “initial letter detection / initials indexing” in current runtime path

## 2) Entry Points & File Inventory

### 2.1 Admin Settings UI (Search-related controls)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.php`
  - Responsibilities:
    - Registers Header submenu (`bw-header-settings`) under `blackwork-site-settings`
    - Renders settings form containing search label/icon/mobile spacing controls
  - Key functions:
    - `bw_header_admin_menu()`
    - `bw_header_admin_enqueue_assets()`
    - `bw_header_render_admin_page()`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/settings-schema.php`
  - Responsibilities:
    - Defines defaults, option schema, sanitization, registration
  - Key functions:
    - `bw_header_default_settings()`
    - `bw_header_get_settings()`
    - `bw_header_sanitize_settings()`
    - `bw_header_register_settings()`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/admin/header-admin.js`
  - Responsibilities:
    - Admin media picker interactions and tab toggling in Header settings
  - Search relevance:
    - Handles icon upload UI used by search icon settings

### 2.2 Frontend Rendering + Runtime

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/header-render.php`
  - Responsibilities:
    - Builds header markup and injects search blocks
    - Hooks header rendering to `wp_body_open`
  - Key functions:
    - `bw_header_render_search_block()`
    - `bw_header_render_frontend()`
  - Hooks:
    - `add_action('wp_body_open', 'bw_header_render_frontend', 5)`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/templates/parts/search-overlay.php`
  - Responsibilities:
    - Search overlay HTML structure, form, results container, loading/message nodes

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/assets.php`
  - Responsibilities:
    - Enqueues search CSS/JS
    - Localizes AJAX config (`bwSearchAjax`)
    - Applies dynamic inline CSS tied to header settings
  - Key function:
    - `bw_header_enqueue_assets()`
  - Hook:
    - `add_action('wp_enqueue_scripts', 'bw_header_enqueue_assets', 20)`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/js/bw-search.js`
  - Responsibilities:
    - Search widget lifecycle, overlay open/close, debounce, AJAX calls, rendering live results
  - Key runtime object:
    - `class BWSearchWidget`

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/bw-search.css`
  - Responsibilities:
    - Search button styles, overlay behavior, results grid, loading state, responsive rules
    - Includes classes for category filters (`.bw-search-overlay__filters`, `.bw-category-filter`)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/assets/css/header-layout.css`
  - Responsibilities:
    - Header layout rules including placement/display of search block

### 2.3 Search Backend Endpoint(s)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/modules/header/frontend/ajax-search.php`
  - Responsibilities:
    - Live product search endpoint for header overlay
  - Key function:
    - `bw_header_live_search_products()`
  - Hooks:
    - `add_action('wp_ajax_bw_live_search_products', 'bw_header_live_search_products')`
    - `add_action('wp_ajax_nopriv_bw_live_search_products', 'bw_header_live_search_products')`

### 2.4 Related but Separate Search Endpoint (not header live search)

- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/metabox/digital-products-metabox.php`
  - Key function:
    - `bw_search_products_ajax()`
  - Hook:
    - `add_action('wp_ajax_bw_search_products', 'bw_search_products_ajax')`
  - Scope:
    - Admin Select2 product lookup for metabox workflows, not the frontend header search runtime.

### 2.5 REST Routes

- No `register_rest_route(...)` specific to the current header search runtime was found in inspected code for this feature path.

## 3) Admin Configuration Model

### 3.1 Option Keys and Storage

Primary option key:
- `bw_header_settings` (stored in `wp_options`)

Default and merge model:
- `bw_header_default_settings()` defines defaults
- `bw_header_get_settings()` merges saved values with defaults using `array_replace_recursive`

Sanitization:
- `bw_header_sanitize_settings()` sanitizes all persisted header/search-related fields
- `register_setting('bw_header_settings_group', BW_HEADER_OPTION_KEY, [...])`

### 3.2 Search-related Config Fields

Within `bw_header_settings`:
- `labels.search` (default: `Search`)
- `icons.mobile_search_attachment_id` (default: `0`)
- `mobile_layout.search_padding.{top,right,bottom,left}` (default all `0`)
- `mobile_layout.search_margin.{top,right,bottom,left}` (default all `0`)
- `features.search` (default: `1`)

Observed behavior notes:
- `features.search` exists in schema and frontend feature gating, but current admin form does not expose a dedicated checkbox for it in the inspected Header page; sanitization preserves existing value when key is not posted.
- No admin field was found for live-search relevance tuning (min chars, result limit, query strategy, category presets).

### 3.3 Fields for Filters / Initials Behavior

- No dedicated admin fields for “initial letter detection / initials indexing” were found in current search runtime settings.
- No dedicated admin filter configuration for header live search categories/product type was found in inspected Header settings UI.

## 4) Frontend UX Model

### 4.1 Where Search Input Appears

Search button is rendered in Header runtime in both desktop and mobile blocks via `bw_header_render_search_block()` and `search-overlay.php`.

User flow:
1. User clicks search button (`.bw-search-button`)
2. JS opens fullscreen overlay (`.bw-search-overlay` with `is-active`)
3. User types in search input
4. Debounced AJAX live search executes after 300ms and min length >= 2
5. Results render in `.bw-search-results__grid`

### 4.2 Result Rendering Surface

Rendered as card grid in overlay (`bw-search.js`):
- Product image
- Product title
- Product `price_html`
- Product link

Fallback messages:
- Empty/no results: message from backend or default localized text
- Network/server error: generic client error message

### 4.3 Filters UI and Behavior

Observed implementation state:
- Backend endpoint supports `categories[]` and `product_type`
- Frontend JS currently submits `categories: []` and does not submit `product_type`
- CSS has filter UI classes, but overlay template contains no filter controls markup by default

Conclusion:
- Filter capability exists partially at endpoint level, but active UI-to-query filter mapping is not fully wired in the current header overlay implementation.

### 4.4 Initials/Letter UX

- No alphabet bar, initials jump navigation, or letter grouping UI was found in active search overlay template/JS path.

## 5) Query & Filter Model (Core)

### 5.1 Query Builder

In `bw_header_live_search_products()`:
- `post_type = product`
- `post_status = publish`
- `posts_per_page = 12`
- `s = <search_term>`

### 5.2 Search Target Scope

Current target:
- WooCommerce products only

No evidence in this endpoint of:
- simultaneous post/page search
- explicit title-only search override
- custom relevance scoring

### 5.3 Taxonomy Filters

Supported by endpoint when provided:
- `product_cat` by slug from `categories[]`
- `product_type` taxonomy from `product_type` input (allowed values: `simple`, `variable`, `grouped`, `external`)

Tax query relation:
- `AND` when both category and product type filters are present

### 5.4 Meta/Price/Availability Filters

No explicit `meta_query` for price, stock, or custom fields in this endpoint.

### 5.5 Sorting/Pagination Model

Sorting:
- Uses default WordPress search ordering (`WP_Query` default for `s` context)

Pagination:
- No pagination/infinite-scroll in current live endpoint response
- Hard response cap is 12 products per request

### 5.6 UI Filter → Query Mapping (Current State)

- Search input text (`.bw-search-overlay__input`) → `$_POST['search_term']` → `args['s']`
- Category filters UI: **not active in rendered template**; JS posts empty categories array
- Product type filter UI: **not found in active overlay implementation**

## 6) Initial Letter Detection / Indexing Model

### 6.1 Current Implementation Status

No verified implementation of initial-letter detection/indexing was found in the active Search runtime path.

Not found in inspected runtime surfaces:
- header search endpoint (`ajax-search.php`)
- header search frontend JS (`bw-search.js`)
- search overlay template (`search-overlay.php`)
- search settings schema/admin page for header

### 6.2 Normalization/Storage/Edge Rules

Because initials indexing logic was not found:
- normalization rules (case/accent/punctuation/stopwords): **unknown**
- precomputed vs runtime initials index: **unknown**
- initials storage schema: **unknown**
- numeric/symbol/accented-letter behavior for initials: **unknown**

Where to verify next:
- archived docs and prior legacy implementations in `docs/99-archive/` for historical behavior
- any removed/legacy widget code if restored outside current branch

## 7) Runtime Integration & Hooks

### 7.1 Key Hooks for Search Runtime

- `wp_enqueue_scripts` (priority 20)
  - callback: `bw_header_enqueue_assets()`
  - enqueues JS/CSS and localizes AJAX config

- `wp_body_open` (priority 5)
  - callback: `bw_header_render_frontend()`
  - injects header + search trigger/overlay markup

- AJAX hooks:
  - `wp_ajax_bw_live_search_products`
  - `wp_ajax_nopriv_bw_live_search_products`
  - callback: `bw_header_live_search_products()`

### 7.2 Header/Navigation Integration

Search is structurally coupled to the custom Header module:
- rendered inside header template blocks (desktop/mobile)
- overlay moved to `<body>` at runtime by JS for fullscreen behavior

### 7.3 Elementor / Theme Interaction

- Header render/enqueue short-circuits in Elementor preview mode (`is_preview_mode()` checks)
- Theme header disabled/fallback CSS managed separately in header runtime, affecting where search appears

### 7.4 Cross-reference Risk (runtime-hook-map)

Search runtime uses Tier-1 style hooks/endpoints in current governance mapping; changes to hook order or header injection points can alter visibility/timing of search UI and endpoint behavior.

## 8) Performance & Caching

### 8.1 Caching

For the header live search endpoint:
- No transient/object cache layer detected
- No custom index table detected

### 8.2 Client-side Throttling

`bw-search.js` includes:
- 300ms debounce before AJAX call
- abort of in-flight request on new input

### 8.3 Query Complexity

Server query characteristics:
- full-text-like `s` search over product posts
- optional taxonomy constraints
- no explicit result cache

Potential hotspots under large catalog:
- repeated `WP_Query` calls for active typing sessions
- relevance and latency sensitivity depends on DB size/index state

### 8.4 Expected Large-catalog Behavior (from implementation)

- Response is capped to 12 items per request, which bounds payload size.
- Request count can still be high during rapid typing sessions because each debounced term triggers a new query.

## 9) Error Handling & Observability

### 9.1 API/Server Error Surfacing

- Nonce failure or WordPress AJAX failure returns standard WP error response.
- Client side handles failed responses with generic messages:
  - `Errore durante la ricerca`
  - `Errore di connessione`

### 9.2 Empty Result Handling

- Backend returns success with empty products + message (`Nessun prodotto trovato`)
- Frontend renders empty state message in overlay

### 9.3 Logging

- No dedicated search logging subsystem was found for this endpoint path.
- Observability is primarily via browser/network inspection and standard WP AJAX responses.

## 10) Security

### 10.1 Admin Surface

- Header settings page requires `manage_options`.
- Settings persistence uses WordPress Settings API (`settings_fields`, `register_setting`, sanitize callback).

### 10.2 AJAX Endpoint Security

`bw_header_live_search_products()`:
- Nonce check: `check_ajax_referer('bw_search_nonce', 'nonce')`
- Input sanitization:
  - `sanitize_text_field` for `search_term` and `product_type`
  - `array_map('sanitize_text_field', ...)` for `categories`
- Public access allowed via `wp_ajax_nopriv_*` (expected for guest search UX)

### 10.3 Output Safety

- Product title escaped in JS via `escapeHtml()` before insertion
- `price_html` is injected as HTML from WooCommerce output
- URL/image data are inserted into markup from server response

### 10.4 Data Leakage Considerations

- Endpoint returns only basic product card fields (id/title/price/image/permalink).
- No sensitive user/session data exposure identified in inspected response payload.

## 11) Verified Risk Summary

- Relevance risk: **Medium**
  - Native `s` query and fixed limit may not align with advanced merchandising/relevance expectations.

- Performance risk: **Medium to High** (catalog-size dependent)
  - No server-side cache/index layer on live search endpoint; frequent AJAX search calls under load.

- Security risk: **Low to Medium**
  - Nonce + sanitization present; public endpoint exists by design; no explicit rate-limiting found.

- UX regression risk: **Medium**
  - Overlay and runtime depend on header injection and JS initialization guards.
  - Filter UI drift risk: CSS/backend support exists, but active template/JS filter controls are not fully wired.

## 12) Open Questions / Unknowns

1. Initial letter detection / initials indexing implementation:
- **Unknown / not found in active code path.**
- Next place to check: archived docs or legacy removed code outside current runtime modules.

2. Intended product filter UX for live search:
- Backend supports category/type, but current active template/JS does not expose complete filter controls.
- Historical intent likely documented in archived header architecture docs; runtime currently does not prove full filter UX.

3. Search admin “tab” requirement alignment:
- No dedicated Search tab slug found in current admin navigation.
- Search configuration appears embedded in Header settings (`bw-header-settings`) rather than standalone search tab.

4. Any external search engine integration (Algolia/Elastic/custom index):
- **Not found** in inspected implementation for this feature path.
