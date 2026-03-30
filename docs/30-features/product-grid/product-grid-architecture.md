# Product Grid — Architecture Map

## 1) Purpose

`bw-product-grid` renders a filterable or filterless masonry/CSS-grid layout of
products (or any post type).  The initial HTML is server-rendered by the
PHP widget class; subsequent filter and pagination changes are handled
entirely in JS via AJAX calls to handlers in `blackwork-core-plugin.php`.

---

## 2) Files

| File | Responsibility |
|------|----------------|
| `includes/widgets/class-bw-product-grid-widget.php` | Elementor widget: controls, `render()`, `render_posts()`, `render_post_item()` |
| `assets/js/bw-product-grid.js` | All frontend behaviour — filter state, AJAX, caching, animations, infinite scroll, Elementor lifecycle |
| `assets/css/bw-product-grid.css` | Layout (masonry / CSS-grid), filter bar, loading states, animations |
| `blackwork-core-plugin.php` | AJAX handlers (`bw_fpw_get_subcategories`, `bw_fpw_get_tags`, `bw_fpw_filter_posts`), rate limiting, server-side transient cache |

---

## 3) PHP Architecture

### 3.1 Controls

Controls are registered across four private methods:

| Method | Controls |
|--------|----------|
| `register_rebuild_layout_controls()` | Infinite scroll, initial items, batch size, desktop columns (`3`-`6`), max-width, masonry toggle, show title/description/price, `Disable Hover Actions on Tablet & Mobile` |
| `register_style_controls()` | Style tab text controls: content gap, title/description/price color, typography, and padding |
| `register_filter_controls()` | `Show Filters`, `Enable Responsive Filter Mode`, `Drawer Opening`, default category, show categories/subcategories/tags, filter bar titles, show `All` option |
| `register_query_controls()` | Post type, parent category (multi-select), subcategory (multi-select), specific IDs, order by, order direction |

### 3.2 Render pipeline

```
render()
├── render_wrapper_start()   — outer wrapper + responsive filter runtime attributes
├── render_filters()         — legacy inline rows OR responsive discovery drawer shell
└── render_posts()           — WP_Query + grid HTML + render_post_item() per post
    └── render_post_item()   — delegates to BW_Product_Card_Component or generic fallback
```

### 3.3 Wrapper-level runtime attributes

The outer `.bw-product-grid` wrapper is also part of the runtime contract.

| Attribute | Source in PHP | Used by |
|-----------|---------------|---------|
| `data-disable-hover-on-touch` | `disable_hover_on_touch` control | scoped CSS below desktop widths |

When `data-disable-hover-on-touch="yes"` the Product Grid locally disables:
- overlay CTA actions (`View Product`, `Add to Cart`)
- secondary hover image/video presentation

This is intentionally scoped at wrapper level so the behavior applies to:
- initial server-rendered cards
- cards injected later by AJAX

without changing the shared `BW_Product_Card_Component` authority for other widgets.

The outer `.bw-product-grid-wrapper` is a second wrapper-level contract:

| Attribute | Source in PHP | Used by |
|-----------|---------------|---------|
| `data-filter-breakpoint` | hardcoded `900` | CSS first paint + `toggleResponsiveFilters()` |
| `data-responsive-filter-mode` | `enable_responsive_filter_mode` | CSS/JS branch between legacy inline filters and discovery drawer mode |
| `data-drawer-side` | `responsive_filter_drawer_side` (`left` / `right`) | CSS drawer-side placement + slide-in direction |

There is currently no Elementor control for the filter breakpoint.

### 3.4 PHP → JS data contract (data-attributes on `.bw-fpw-grid`)

All values that the JS layer must know at runtime are serialised as
`data-*` attributes on the `.bw-fpw-grid` element during `render_posts()`.
This is the canonical bridge between PHP and JS.  Do not hardcode these
values in JS — always add a matching data-attribute in PHP first.

| Attribute | Source in PHP | Read by JS |
|-----------|---------------|------------|
| `data-widget-id` | `$this->get_id()` | all state lookups |
| `data-post-type` | `$settings['post_type']` | filter/AJAX calls |
| `data-layout-mode` | masonry_effect control | `initGrid()` |
| `data-columns-desktop/tablet/mobile` | controls | `setItemWidths()` |
| `data-gap-x-desktop/tablet/mobile` | `Grid > Post Gap Horizontal` | CSS vars + masonry width/gutter |
| `data-gap-y-desktop/tablet/mobile` | `Grid > Post Gap Vertical` | CSS vars + masonry row spacing |
| `data-breakpoint-tablet-min/max`, `data-breakpoint-mobile-max` | hardcoded defaults | `getCurrentDevice()` |
| `data-image-size` | `$image_size` (default `large`) | `filterPosts()` AJAX payload |
| `data-image-mode` | `$image_mode` (default `proportional`) | `filterPosts()` AJAX payload |
| `data-hover-effect` | `$hover_effect` (default `yes`) | `filterPosts()` AJAX payload |
| `data-open-cart-popup` | `$open_cart_popup` (default `no`) | `filterPosts()` AJAX payload |
| `data-show-title` | `show_title` control | CSS visibility contract |
| `data-show-description` | `show_description` control | CSS visibility contract |
| `data-show-price` | `show_price` control | CSS visibility contract |
| `data-order-by`, `data-order` | query controls | `filterPosts()` AJAX payload |
| `data-initial-items`, `data-load-batch-size`, `data-per-page` | controls | paging state |
| `data-current-page`, `data-next-page`, `data-next-offset` | `render_posts()` derived | paging state |
| `data-loaded-count`, `data-has-more` | `render_posts()` derived | paging state |
| `data-infinite-enabled` | `infinite_scroll` control | `syncInfiniteObserver()` |
| `data-load-trigger-offset` | hardcoded 300 px | `syncInfiniteObserver()` |

> **Invariant:** when adding a new Elementor control that must reach the
> AJAX handler, (1) read it in `render_posts()` from `$settings`, (2) add
> a `data-*` attribute to the grid, (3) read it in `filterPosts()` via
> `$grid.attr()`.  Never add a new hardcoded default only in JS.

### 3.5 render_post_item() signature

```php
private function render_post_item(
    string $post_type,
    bool   $open_cart_popup,
    string $image_loading       = 'lazy',
    string $hover_image_loading = 'lazy',
    string $image_size          = 'large',
    string $image_mode          = 'proportional',
    bool   $show_title          = true,
    bool   $show_description    = true,
    bool   $show_price          = true
)
```

`$image_size`, `$image_mode`, and the three visibility booleans are passed
from `render_posts()`.  The initial server render therefore respects the
active widget settings directly.

Important nuance for later maintenance:
- the AJAX handler currently receives `image_*` / hover / popup flags
- it does **not** receive `show_title`, `show_description`, or `show_price`
- AJAX markup still renders those content blocks, and final visibility stays
  governed by the grid-level `data-show-*` attributes plus CSS

---

### 3.6 Responsive filter surfaces

The widget currently has two filter surface families:

- legacy inline path:
  - desktop inline rows: `.bw-fpw-filters`
  - classic mobile trigger/panel below the hardcoded breakpoint
- responsive discovery path (`Enable Responsive Filter Mode = yes`):
  - desktop + mobile share a discovery toolbar and the same drawer shell
  - inline desktop rows are suppressed
  - drawer shell can open from `left` or `right` via `Drawer Opening`

Responsive discovery PHP shell authority:
- toolbar result count + reset action
- global search input (`Search in collection...`)
- filter trigger pill
- drawer shell regions:
  - header
  - scrollable body
  - sticky footer CTA

Drawer body content is data-bootstrapped in PHP and rendered live in JS from centralized state.

### 3.7 Responsive filter first-paint contract

To avoid a mobile first-paint flash of desktop filter labels, visibility is decided in two layers:
- CSS first-paint authority:
  - legacy mode: `@media (max-width: 899px)` hides `.bw-fpw-filters` and shows `.bw-fpw-mobile-filter`
  - discovery mode: wrapper-level `data-responsive-filter-mode="yes"` hides legacy inline rows immediately
- JS runtime authority:
  - `toggleResponsiveFilters()` still adds/removes `.bw-fpw-mobile-filters-enabled`
  - JS remains responsible for panel open/close and resize cleanup

This split is intentional: CSS prevents FOUC on reload, while JS retains behavior/state control.

## 4) JavaScript Architecture

### 4.1 Module structure

`bw-product-grid.js` is a self-contained IIFE.  All state is module-level
(not global).  There are no exported symbols; Elementor integration is
done via `elementorFrontend.hooks.addAction`.

### 4.2 Per-widget state objects

Each live widget instance owns two main state objects keyed by `widgetId`:

```
filterState[widgetId]       = {
                                category,
                                subcategories[],
                                tags[],
                                search,
                                appliedSearch,
                                resultCount,
                                options: { types[], tags[] },
                                labels:  { types{}, tags{} },
                                ui: {
                                  showTypes,
                                  showTags,
                                  optionSearches: { types, tags },
                                  openGroups:     { types, tags }
                                }
                              }
widgetPagingState[widgetId] = { gridEl, initialItems, loadBatchSize,
                                perPage, currentPage, nextPage,
                                loadedCount, nextOffset, hasMore,
                                infiniteEnabled, loadTriggerOffset,
                                isLoading, observer }
```

In discovery drawer mode the same `filterState` is the single source of truth for:
- drawer checkboxes
- global discovery search
  - placeholder label is PHP-derived from widget query context when a single default/parent category is locked
- quick filters / selected pills
- result count
- reset actions

The responsive drawer shell is intentionally style-only and does not alter filter behaviour:
- overlay uses a light veil plus blur so page context remains visible behind the drawer
- the drawer itself is a detached dark-glass panel with large radius and tight viewport margins
- header, close control, and footer CTA follow the same floating-surface language used by other mobile navigation surfaces

`filterState` is initialised by `initFilterState()` (once per widget) and reset only when `destroyWidgetState()` is called.

`widgetPagingState` is updated on every AJAX response via
`updateWidgetPagingState()`.  `getWidgetPagingState()` automatically
resets the object and disconnects the observer when it detects that
`gridEl !== $grid[0]` (Elementor re-render).

### 4.3 AJAX request queue

```
ajaxRequestQueue[widgetId]           — filterPosts() active request
ajaxRequestQueue[widgetId + '_subcats'] — loadSubcategories() active request
ajaxRequestQueue[widgetId + '_tags']    — loadTags() active request
```

Each caller aborts any previous entry before issuing a new one.  This
prevents stale responses from overwriting current results when the user
changes filters rapidly.

### 4.4 Client-side cache

`ajaxCache` stores responses from all three AJAX endpoints.  Keys are
produced by `getCacheKey(type, params)` (SHA-ish hash of sorted params).
TTL: 5 minutes (`CACHE_DURATION`).

Cache is checked first in `filterPosts()`, `loadSubcategories()`, and
`loadTags()`.  Random-order (`order_by=rand`) always skips the cache.

### 4.5 Filter animation timers

```
filterAnimTimers[widgetId + '_subcats']  — 150 ms clear timer for subcategories container
filterAnimTimers[widgetId + '_tags']     — 150 ms clear timer for tags container
```

The fade-out is: `opacity → 0` immediately, then `empty()` after 150 ms.
If a new `loadSubcategories()` / `loadTags()` call fires before the timer
expires the timer is cancelled so it cannot empty freshly-rendered content.

### 4.6 Loading-state classes (coupling contract)

Two CSS classes on `.bw-fpw-load-state` serve different roles and must not
be confused:

| Class | Role | Set by |
|-------|------|--------|
| `is-loading` | Logical flag. Read by `syncInfiniteObserver()` and `loadNextPage()` to prevent concurrent requests. | `updateWidgetPagingState({ isLoading: true/false })` |
| `is-loading-visible` | Visual flag. Controls CSS opacity transition on `.bw-fpw-load-indicator`. Added only after a 400 ms delay (`loadingIndicatorTimers`) to avoid flash on fast/cached loads. | `updateInfiniteUi()` internal timer |

> **Invariant:** never remove `is-loading` without auditing
> `syncInfiniteObserver()` and `loadNextPage()`.

### 4.7 Stagger animation

Items entering the viewport are animated in sequence by
`animatePostsStaggered()`.  Per-widget timers are tracked in
`staggerTimersByWidget[widgetId]` and per-widget observers in
`staggerObserversByWidget[widgetId]`.  Both are cleared by
`clearStaggerTimers(widgetId)`.

### 4.8 Infinite scroll

`syncInfiniteObserver(widgetId)` creates an `IntersectionObserver` that
watches `.bw-fpw-load-sentinel`.  When the sentinel enters the viewport
and `state.hasMore && !state.isLoading`, it calls `loadNextPage()`.

The observer reference is stored in `widgetPagingState[widgetId].observer`
and disconnected via `disconnectInfiniteObserver(widgetId)`.

### 4.9 Responsive / device modes

`getCurrentDevice($grid)` returns `desktop`, `tablet`, or `mobile` based
on `window.innerWidth` vs. the grid's breakpoint data-attributes.

`isInMobileMode(widgetId)` returns true when the wrapper has the class
`bw-fpw-mobile-filters-enabled` (set by `toggleResponsiveFilters()`).

In responsive discovery mode the toolbar + drawer currently behaves as follows:
- desktop above `800px`:
  - search/filter controls are compact pill triggers that expand on hover/focus
- responsive mode at `800px` and below:
  - search/filter controls become always-open, full-width controls
  - quick filters collapse to selected-only pills to reduce noise
- drawer shell reuses the cart-popup visual language
- drawer-side is wrapper-configurable (`left` / `right`)
- accordion group labels are currently:
  - `Categories`
  - `Style / Subject`

The first-paint mobile/desktop decision is no longer JS-only; see the CSS contract above.

---

## 5) AJAX Handlers (PHP)

All Product Grid AJAX handlers are in `blackwork-core-plugin.php`.

### 5.1 bw_fpw_get_subcategories

- Action: `wp_ajax[_nopriv]_bw_fpw_get_subcategories`
- Input: `category_id`, `post_type`, `nonce`
- Returns: `[{ term_id, name, count }]`
- Server cache: `bw_fpw_subcats_{post_type}_{category_id}` — 15 min transient
- Rate limit: 60 req/min (anon), 300 req/min (auth)

### 5.2 bw_fpw_get_tags

- Action: `wp_ajax[_nopriv]_bw_fpw_get_tags`
- Input: `category_id`, `post_type`, `subcategories[]`, `nonce`
- Returns: `[{ term_id, name, count }]`
- Server cache: `bw_fpw_tags_{post_type}_{category_id}_{subcats_hash}` — 15 min transient
- Rate limit: 50 req/min (anon), 300 req/min (auth)

### 5.3 bw_fpw_filter_posts

- Action: `wp_ajax[_nopriv]_bw_fpw_filter_posts`
- Input: `widget_id`, `post_type`, `category`, `subcategories[]`, `tags[]`, `search`, `image_toggle`, `image_size`, `image_mode`, `hover_effect`, `open_cart_popup`, `order_by`, `order`, `per_page`, `page`, `offset`, `nonce`
- Returns: `{ html, tags_html, available_tags[], available_types[], filter_ui, result_count, has_posts, page, per_page, has_more, next_page, offset, loaded_count, next_offset }`
- Server cache: SHA-256 transient keyed on canonical payload — 10 min (skipped for `rand`)
- Rate limit: 35 req/min (anon), 200 req/min (auth)

Current search behavior:
- search term is normalized server-side
- matching uses title, excerpt, content, slug, and taxonomy term names
- a native WP `s` query is also merged into the matching-post-ID set

Current empty-state copy:
- active refinements/search -> `No results found.`
- empty archive baseline -> `There is nothing in this archive yet.`

### 5.4 bw_fpw_refresh_nonce

- Action: `wp_ajax[_nopriv]_bw_fpw_refresh_nonce`
- Input: none
- Returns: `{ nonce }`
- Purpose: refresh an expired Product Grid nonce without a full page reload

### 5.5 Rate limiting

`bw_fpw_is_throttled_request($action_key)` maintains a per-fingerprint
counter in a short-lived transient.

- **Anonymous users:** fingerprint = `md5(IP + '|' + UA[:128])`
- **Authenticated users:** fingerprint = `'u' + user_id`

Using `user_id` for authenticated users avoids false positives on shared
networks (offices, NAT) while still protecting against scripted abuse from
a single account.

---

## 6) Elementor Lifecycle Integration

### 6.1 Initialisation

```
elementorFrontend.hooks.addAction(
    'frontend/element_ready/bw-product-grid.default',
    addElementorHandler    // → setTimeout(initWidget, 80)
)
```

`initWidget($scope)` finds all `.bw-fpw-grid` in scope and, for each:
1. Checks if a prior state exists for the same `widgetId` with a **different** `gridEl` (re-render) → calls `destroyWidgetState(widgetId)`.
2. Calls `initFilterState(widgetId)`.
3. Reads active filter buttons from the DOM to seed `filterState`.
4. Calls `initGrid($grid, callback)` which sets up masonry/CSS-grid layout and then runs the initial reveal animation.
5. Calls `syncInfiniteObserver(widgetId)`.

### 6.2 destroyWidgetState(widgetId)

Full teardown for one widget instance.  Must be called on re-render and on
deletion to prevent memory leaks and orphaned timers.

Actions performed:
- `clearStaggerTimers(widgetId)` — cancel all reveal timers and observers
- `disconnectInfiniteObserver(widgetId)` — disconnect sentinel IO
- Cancel `filterAnimTimers` for subcats and tags keys
- Abort all in-flight AJAX requests (`widgetId`, `_subcats`, `_tags` keys)
- Clear `loadingIndicatorTimers[widgetId]`
- Unbind `scroll.bwreveal{widgetId}` listener
- Delete: `filterState`, `widgetPagingState`, `staggerTimersByWidget`, `staggerObserversByWidget`, `lastDeviceByGrid`

### 6.3 Editor deletion via MutationObserver

`registerElementorHooks()` attaches a `MutationObserver` on `document.body`
(subtree, childList) **only when `isElementorEditor()` is true**.  When a
`.bw-fpw-grid` node is removed from the DOM, `destroyWidgetState()` is
called for its `data-widget-id`.

---

## 7) Known Constraints

1. **Breakpoints are hardcoded** (`$breakpoint_tablet_min = 768`, etc.).
   There are no Elementor controls for them.  To make them configurable,
   add controls and map them to data-attributes following the contract in
   §3.3.

2. **`$image_size`, `$image_mode`, `$hover_effect`, `$open_cart_popup`**
   have no Elementor controls yet.  They are declared as named variables in
   `render_posts()` and exposed via data-attributes so adding a control
   later requires only: read from `$settings`, update the variable — the
   data-attribute and JS pipeline carry the value automatically.

3. **`show_title`, `show_description`, `show_price` are currently a
   presentation-only contract on AJAX refreshes.**  The initial PHP render
   passes the booleans directly into `render_post_item()`, but the AJAX
   endpoint still renders all three blocks and relies on grid-level
   `data-show-*` attributes plus CSS to keep visibility aligned.

4. **Transient cache is per-server.**  In multi-server environments, a
   request to a different server may miss cache and generate a fresh query.
   This is acceptable — the query is still rate-limited.

5. **Client-side cache is per-page-load.**  Navigating away and back clears
   the JS cache.  The server-side transient provides cross-session coverage.

---

## 8) Risk Surfaces

| Surface | Risk | Severity |
|---------|------|----------|
| `bw_fpw_filter_posts` AJAX handler | Any change to the response shape breaks JS rendering | High |
| `data-*` attribute contract on `.bw-fpw-grid` | Adding/renaming an attribute without updating both PHP and JS breaks the pipeline | High |
| `destroyWidgetState()` completeness | If a new timer or observer is added without a matching cleanup entry, leaks accumulate in the editor | Medium |
| Rate-limit transient key format | If `bw_fpw_get_request_fingerprint()` changes, existing buckets are orphaned (harmless but effective limit resets) | Low |
| `is-loading` / `is-loading-visible` class split | CSS rules that depend on only one class will break if the split is collapsed | Medium |
| Reveal animation constants | `STAGGER` e `baseDelay` in JS devono restare ≤ durata CSS `transition`; `cleanupDelay` deve essere ≥ durata CSS | Medium |

---

## 9) Hardening e ottimizzazioni 2026-03-16

### 9.1 Elementor frontend hook fallback (commit `41afb3d1`)

`registerElementorHooks()` registra il handler su `elementorFrontend.hooks.addAction(...)`.
Se `elementorFrontend.hooks` non è ancora disponibile al momento dell'esecuzione (timing race
su alcune configurazioni), il widget non veniva inizializzato.

Fallback aggiunto:

```javascript
if (typeof elementorFrontend !== 'undefined' && elementorFrontend.on) {
    elementorFrontend.on('init', function () {
        registerElementorHooks();
    });
}
```

Se anche questo path fallisce, `initAllGrids()` viene invocato direttamente su `document.ready`
come ultima rete di sicurezza.

### 9.2 Velocizzazione animazione reveal (commit `86c1db13`)

I parametri di animazione reveal sono stati ridotti per minimizzare il tempo percepito al caricamento
della pagina mantenendo coerenza visiva dello stagger sequenziale.

| Parametro | Prima | Dopo | File |
|-----------|-------|------|------|
| CSS `transition: opacity` | `1.8s ease` | `0.45s ease` | `assets/css/bw-product-grid.css` |
| `baseDelay` (initial reveal) | `80ms` | `40ms` | `assets/js/bw-product-grid.js` — `animatePostsStaggered()` |
| `STAGGER` (scroll/append batches) | `80ms` | `40ms` | `assets/js/bw-product-grid.js` — `revealItemsPerViewport()` |
| `cleanupDelay` (initial) | `1200ms` | `600ms` | `assets/js/bw-product-grid.js` — `animatePostsStaggered()` |
| `cleanupDelay` (scroll) | `2200ms` | `600ms` | `assets/js/bw-product-grid.js` — `revealItemsPerViewport()` |

Invariante da rispettare: `cleanupDelay` ≥ durata CSS `transition` (ora 450ms). Il commento
in `revealItemsPerViewport` documenta esplicitamente il vincolo.

Impatto: per una griglia da 20 prodotti la finestra totale del reveal passa da ~3.4s a ~1.3s.
