# Product Grid — Architecture Map

## 1) Overview

`bw-product-grid` renders a filterable or filterless masonry/CSS-grid layout of
products (or any post type).  The initial HTML is server-rendered by the
PHP widget class; subsequent filter and pagination changes are handled
entirely in JS via AJAX calls to the Product Grid adapter in
`includes/modules/search-engine/adapters/product-grid/product-grid-adapter.php`,
which delegates search/filter runtime work to the shared engine under
`includes/modules/search-engine/`.

---

## 2) Files

| File | Responsibility |
|------|----------------|
| `includes/widgets/class-bw-product-grid-widget.php` | Elementor widget: controls, `render()`, `render_posts()`, `render_post_item()` |
| `assets/js/bw-product-grid.js` | All frontend behaviour — filter state, AJAX, caching, animations, infinite scroll, Elementor lifecycle |
| `assets/css/bw-product-grid.css` | Layout (masonry / CSS-grid), filter bar, loading states, animations |
| `includes/modules/search-engine/adapters/product-grid/product-grid-adapter.php` | Product Grid AJAX surface: nonce validation, rate limit invocation, engine dispatch, HTML rendering, delta protocol, response contract |
| `includes/modules/search-engine/engine/` | Shared search/filter engine: orchestration, query planning, candidates, text match, advanced filters, facet builder |
| `includes/modules/search-engine/cache/` | Search-domain cache keys, transients, generations, invalidation, canonical filter-meta sync |
| `includes/modules/search-engine/search-engine-module.php` | Search-domain hook registration bootstrap |
| `blackwork-core-plugin.php` | Bootstrap/wiring only for the search/filter domain |

---

## 3) PHP Architecture

### 3.1 Controls

Controls are registered across four private methods:

| Method | Controls |
|--------|----------|
| `register_rebuild_layout_controls()` | Infinite scroll, initial items, batch size, desktop columns (`3`-`6`), max-width, masonry toggle, show title/description/price, `Disable Hover Actions on Tablet & Mobile` |
| `register_style_controls()` | Style tab text controls: content gap, title/description/price color, typography, and padding |
| `register_filter_controls()` | `Show Filters`, `Filter Mode`, `Visible Filters`, `Show Search`, `Show Order By`, `Order By Trigger Style`, `Drawer Opening`, default category, show categories/subcategories/tags, filter bar titles, show `All` option |
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
| `data-context-slug` | resolved product-family context (`digital-collections` / `books` / `prints` / `mixed`) | Years + advanced meta-filter bootstrap and AJAX context payload |
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
| `data-search-enabled` | `show_search` control | search feature-flag gating in JS + AJAX |
| `data-show-order-by` | `show_order_by` control gated by responsive discovery mode and `specific_ids` absence | runtime sort trigger/menu gating in JS |
| `data-show-visible-filters` | `show_visible_filters` control gated by responsive discovery mode + `post_type = product` | desktop visible-filter row gating in JS |
| `data-order-trigger-style` | `order_by_trigger_style` control | shared runtime sort trigger rendering |
| `data-default-sort-key` | canonical `newest` | initial JS sort state |
| `data-order-by`, `data-order` | query controls | `filterPosts()` AJAX payload |
| `data-default-order-by`, `data-default-order` | query controls | runtime `newest` mapping in JS |
| `data-specific-ids-mode` | `specific_ids` present or not | runtime sort disablement in curated-ID mode |
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
- optional global search input (`Search in collection...`) when `Show Search = On`
- optional runtime `Order By` trigger when `Show Order By = On`
  - trigger variants: `icon` or `dropdown`
  - both open the same shared floating sort menu
  - sorting is not treated as a filter chip and is not cleared by `Reset filters`
- filter trigger pill
- active-chip row above the grid (active filters only)
- drawer shell regions:
  - header
  - scrollable body
  - sticky footer CTA

Drawer body content is data-bootstrapped in PHP and rendered live in JS from centralized state.

`Show Search` is implemented as a real feature flag:
- PHP skips search markup entirely when the control is `Off`
- discovery bootstrap exposes `search_enabled`
- grid runtime exposes `data-search-enabled`
- JS binds search handlers only to search-enabled widget inputs and bypasses search UI/state wiring when disabled
- AJAX sends `search_enabled`
- the backend forces the effective search term to `''` and skips search-term matching work

Runtime sorting is implemented as one shared stateful feature:
- JS state uses a single `sortKey`
- AJAX sends `sort_key` plus effective `order_by` / `order`
- the backend maps `sort_key` authoritatively to real query args
- `default` means the widget’s own Elementor query defaults
- `year_asc` / `year_desc` sort on canonical `_bw_filter_year_int`
- widgets configured with `specific_ids` do not expose runtime sorting, so curated `post__in` order remains authoritative

### 3.7 Runtime Sort (Order By)

#### Feature overview

Runtime Sort is the user-facing Product Grid ordering control.

- scope: responsive discovery toolbar only
- purpose: change the real backend order of the current result set
- nature: runtime user control, not only editor-defined query setup

#### Elementor controls

- `Show Order By`
  - switcher
  - enables the runtime sort feature
- `Order By Trigger Style`
  - select
  - values: `icon`, `dropdown`
  - shown only when `Show Order By = yes`

The feature is intended for responsive discovery mode. No legacy inline placement is implemented in v1.

#### Runtime state

Shared client-side source of truth:

- `filterState[widgetId].sortKey`

Canonical sort values:

- `newest`
- `oldest`
- `title_asc`
- `title_desc`
- `year_asc`
- `year_desc`

Backward compatibility aliases:

- `random_seeded` -> `newest`

Both trigger modes read and update the same state key.

#### Trigger modes

`icon`
- circular green trigger
- arrow-up-down icon
- opens the shared floating sort menu

`dropdown`
- soft rounded pill
- current selected short label
- chevron on the right
- opens the same shared floating sort menu

Only the trigger UI differs. State, menu logic, AJAX, and backend mapping remain shared.

#### Label system

Trigger labels are short:

- `newest` -> `Latest`
- `oldest` -> `Earliest`
- `title_asc` -> `A–Z`
- `title_desc` -> `Z–A`
- `year_asc` -> `Year ↑`
- `year_desc` -> `Year ↓`

Menu labels remain full:

- `Recently added`
- `Oldest added`
- `Alphabetical A to Z`
- `Alphabetical Z to A`
- `Year, oldest first`
- `Year, newest first`

This keeps the trigger compact and the menu explicit.

#### Backend mapping

`sort_key` is authoritative when present.

- `newest` -> `date DESC`
- `oldest` -> `date ASC`
- `title_asc` -> `title ASC`
- `title_desc` -> `title DESC`
- `year_asc` -> canonical `_bw_filter_year_int ASC`
- `year_desc` -> canonical `_bw_filter_year_int DESC`

Unknown or legacy values fall back to `newest`.

Year sorting uses canonical meta and not raw editorial year fields.

#### Newest order

`Newest` means the widget’s default runtime order.

It is intentionally resolved backend-first and not left as a UI-only label.

#### Interaction with the rest of Product Grid

- filters are preserved
- Years filter is preserved
- advanced filters are preserved
- search ON/OFF is compatible
- sort is not represented as a chip
- `Reset filters` does not reset sort
- exact Lucide SVG mapping is preserved across widget, headless renderer,
  and JS runtime
- desktop trigger shows left-aligned label + right-aligned chevron
- mobile trigger uses icon-only presentation for compact toolbar spacing

#### Infinite scroll

Sort changes:

- reset paging
- force replace-mode refresh
- restart from page 1
- prevent mixed ordering between old and new appended results

#### specific_ids

When `specific_ids` is active:

- runtime sort is disabled
- no sort trigger is rendered

Reason:
- preserve editorial `post__in` ordering

#### UI layering

The floating sort menu uses dedicated layering rules:

- the toolbar establishes a stacking context
- `.bw-fpw-sort.is-open` elevates the active sort control
- the menu has its own z-index
- the panel sits above product cards and overlay actions
- the open menu remains clickable

#### Limitations (v1)

- responsive discovery toolbar only
- not implemented in legacy inline mode
- no chip integration
- no reset integration

#### Status

Runtime Sort is implemented, integrated with Product Grid architecture, and ready for final browser QA.

### 3.8 Responsive filter first-paint contract

To avoid a mobile first-paint flash of desktop filter labels, visibility is decided in two layers:
- CSS first-paint authority:
  - legacy mode: `@media (max-width: 899px)` hides `.bw-fpw-filters` and shows `.bw-fpw-mobile-filter`
  - discovery mode: wrapper-level `data-responsive-filter-mode="yes"` hides legacy inline rows immediately
- JS runtime authority:
  - `toggleResponsiveFilters()` still adds/removes `.bw-fpw-mobile-filters-enabled`
  - JS remains responsible for panel open/close and resize cleanup

This split is intentional: CSS prevents FOUC on reload, while JS retains behavior/state control.

## 4) Frontend Architecture

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
                                artists[],
                                authors[],
                                publishers[],
                                sources[],
                                techniques[],
                                search,
                                appliedSearch,
                                sortKey,
                                year: { from, to },
                                yearBounds: { min, max },
                                yearQuickRanges[],
                                resultCount,
                                options: {
                                  types[], tags[],
                                  artist[], author[],
                                  publisher[], source[],
                                  technique[]
                                },
                                labels:  {
                                  types{}, tags{},
                                  artist{}, author{},
                                  publisher{}, source{},
                                  technique{}
                                },
                                ui: {
                                  searchEnabled,
                                  showTypes,
                                  showTags,
                                  showYears,
                                  showArtist,
                                  showAuthor,
                                  showPublisher,
                                  showSource,
                                showTechnique,
                                showOrderBy,
                                showVisibleFilters,
                                orderTriggerStyle,
                                sortMenuOpen,
                                visibleFilterOpenGroup,
                                filterUiHashes: {
                                  types, tags,
                                  advanced, year,
                                  result_count
                                },
                                optionSearches: {
                                    types, tags,
                                    artist, author,
                                    publisher, source,
                                    technique
                                  },
                                  openGroups: {
                                    types, tags, years,
                                    artist, author,
                                    publisher, source,
                                    technique
                                  },
                                  yearDraft:      { from, to },
                                  drawerGroupMarkup: {},
                                  sortConfigCacheKey,
                                  sortConfigCacheValue
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
- global discovery search when `searchEnabled = true`
  - placeholder label is PHP-derived from widget query context when a single default/parent category is locked
- desktop-only visible filters row above the results
- active-only chips above the grid
- active-only chips inside the drawer, under the `Filters` title
- the runtime sort trigger + shared floating menu
- year slider, year inputs, and year quick ranges
- token-based advanced meta groups:
  - `Artist`
  - `Author`
  - `Publisher`
  - `Source`
  - `Technique`
- result count
- reset actions

The responsive drawer shell is intentionally style-only and does not alter filter behaviour:
- overlay uses a light veil plus blur so page context remains visible behind the drawer
- the drawer itself is a detached dark-glass panel with large radius and tight viewport margins
- header, close control, and footer CTA follow the same floating-surface language used by other mobile navigation surfaces

Desktop visible filters are a second UI surface over the same state, not a second filtering system:
- enabled via `Visible Filters`
- desktop only
- rendered between toolbar controls and active chips
- supported groups in v1:
  - `Categories`
    - implemented as the existing `types` group
  - `Style / Subject`
    - implemented as the existing `tags` group
  - `Artists`
  - `Author`
  - `Source`
  - `Technique`
  - `Year`
- `Filters` button remains visible as the full-panel entry point
- visible token groups reuse:
  - `ui.optionSearches[group]`
  - `toggleDiscoverySelection()`
  - `filterPosts()`
  - backend `filter_ui` refinement counts/options
- active token pills use:
  - inline count badge
  - hover count -> `X`
  - clear-only single-group reset on click
- visible `Year` reuses:
  - slider
  - from/to inputs
  - quick ranges
  - inline active range summary
  - `X`-only clear badge
- desktop visible-filter panel open state is tracked separately from drawer accordion state through:
  - `filterState[widgetId].ui.visibleFilterOpenGroup`

`filterState` is initialised by `initFilterState()` (once per widget) and reset only when `destroyWidgetState()` is called.

`widgetPagingState` is updated on every AJAX response via
`updateWidgetPagingState()`.  `getWidgetPagingState()` automatically
resets the object and disconnects the observer when it detects that
`gridEl !== $grid[0]` (Elementor re-render).

### 4.2.1 Filter Architecture Rules (MUST FOLLOW)

- Single source of truth:
  - frontend filter truth lives in `filterState[widgetId]`
  - backend filter truth lives in the normalized request payload
- UI surfaces are not independent filter systems:
  - visible desktop filters
  - popup / drawer filters
  - chips
  all mutate and reflect the same shared state
- New filters must always reuse:
  - shared state
  - shared payload building
  - shared backend normalization
  - shared `filter_ui` refinement
- Never add:
  - parallel desktop-only state
  - parallel drawer-only state
  - a second backend filter implementation for the same logical filter

### 4.2.2 Data Flow

Canonical flow:

1. UI interaction mutates `filterState[widgetId]`
2. mutation helpers normalize token selections early
3. `filterPosts()` serializes effective filter state into AJAX params
4. backend normalizes/sanitizes/dedupes payload values
5. normalized filters affect:
   - query scope
   - cache key
   - refinement / `filter_ui`
6. response returns:
   - cards + paging
   - full or partial `filter_ui` sections in replace mode
7. frontend merges response data back into shared state and updates the
   relevant UI surfaces

Append vs replace:
- replace mode:
  - result set is replaced
  - `filter_ui` may be refreshed or diffed by section
- append mode:
  - cards + paging only
  - filter UI must not be recomputed or resynced

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
produced by `getCacheKey(type, params)`.

Current cache-key format:

`bwpg::<request-family>::<params-json>`

Request-family namespacing currently covers:
- `filter_posts`
- `subcategories`
- `tags`

TTL: 5 minutes (`CACHE_DURATION`).

Client cache is now bounded:
- max entries: `80`
- expired entries are removed opportunistically
- oldest entries are evicted first once the cap is exceeded

Cache is checked first in `filterPosts()`, `loadSubcategories()`, and
`loadTags()`.  Random-order (`order_by=rand`) always skips the cache.

### 4.4.3 Frontend Rules

- Mutation points must keep state clean immediately.
  - do not rely only on late normalization before request dispatch
- `ajaxCache` must remain:
  - namespaced by request family
  - TTL-based
  - bounded by max-entry eviction
- avoid full DOM replacement when only one drawer group changed
- append mode must not trigger full discovery/filter resync

### 4.4.1 Sort-config memoization

`getEffectiveDiscoverySortConfig()` is memoized per widget.

The derived config is recomputed only when one of these changes:
- `filterState[widgetId].sortKey`
- widget default `order_by`
- widget default `order`

This avoids rebuilding the same resolved sort config across repeated renders
and AJAX calls when runtime sort state is unchanged.

### 4.4.2 Advanced-filter selection hygiene

Advanced-token arrays are normalized when mutated, not only later when consumed.

Current early-pruned groups:
- `artists`
- `authors`
- `publishers`
- `sources`
- `techniques`

Mutation helpers:
- `normalizeDiscoverySelectionStateValue(groupKey, values)`
- `setDiscoverySelectionState(state, groupKey, values)`

This keeps frontend state tidy by removing duplicates / empties close to the
actual mutation points.

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
  - active chips keep their remove control always visible
- drawer shell reuses the cart-popup visual language
- drawer-side is wrapper-configurable (`left` / `right`)
- accordion group labels are currently:
  - `Categories`
  - `Style / Subject`
  - `Years` (only when the widget resolves to a supported product-family context)
  - context-aware token groups:
    - `Artist` -> Digital Collections + Prints
    - `Author` -> Books
    - `Publisher` -> Digital Collections + Books + Prints
    - `Source` -> Digital Collections
    - `Technique` -> Digital Collections + Prints

The first-paint mobile/desktop decision is no longer JS-only; see the CSS contract above.

### 4.10 Discovery drawer rendering strategy

The drawer no longer rebuilds its full DOM tree on every small interaction.

Current rendering model:
- `renderDiscoveryDrawerGroups(widgetId)` performs the initial/full sync pass
- `patchDiscoveryDrawerGroups(widgetId, targetGroupKey)` updates either:
  - a single group, or
  - all groups selectively
- `renderDiscoveryDrawerGroup(widgetId, groupKey)` is the focused entry point
  for one-group rerenders

Current effect:
- a local search change inside one group rerenders only that group
- toggling one accordion group no longer forces unrelated groups to rebuild
- visible filters and the drawer remain synchronized because all surfaces still
  read from the same `filterState[widgetId]`

---

## 5) AJAX Handlers (PHP)

Product Grid AJAX endpoints are now registered by
`includes/modules/search-engine/search-engine-module.php` and implemented in
`includes/modules/search-engine/adapters/product-grid/product-grid-adapter.php`.

Ownership boundary:
- adapter owns Product Grid-specific request validation, try/catch error handling, HTML rendering, `filter_ui_hashes` delta behavior, and final response assembly
- shared engine owns normalized request execution, query planning, candidate resolution, search/filter evaluation, indexes, cache, and facet payload generation
- `blackwork-core-plugin.php` no longer owns Product Grid search/filter business logic

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
- Input: `widget_id`, `post_type`, `context_slug`, `category`, `subcategories[]`, `tags[]`, `artist[]`, `author[]`, `publisher[]`, `source[]`, `technique[]`, `search`, `year_from`, `year_to`, `image_toggle`, `image_size`, `image_mode`, `hover_effect`, `open_cart_popup`, `sort_key`, `order_by`, `order`, `per_page`, `page`, `offset`, `nonce`
- Returns: `{ html, tags_html, available_tags[], available_types[], filter_ui, filter_ui_hashes, result_count, has_posts, page, per_page, has_more, next_page, offset, loaded_count, next_offset }`
- Server cache: data-layer transient keyed on canonical dataset payload — 10 min (skipped for `rand`)
- Rate limit: 35 req/min (anon), 200 req/min (auth)

Response-cache refinement:
- the cache no longer fragments on purely visual widget settings that do not
  change the filtered dataset
- final card HTML is still rendered with the current widget settings, so
  widgets with different visual presentation cannot contaminate each other
- generation counters still remain part of the canonical cache key

### 5.3.1 Cache Strategy

- Dataset-affecting inputs must be part of the cache key.
- Purely visual/render-only inputs must not fragment the dataset cache.
- New filter inputs must be:
  - normalized before hashing
  - stable for array ordering
  - wired into generation-aware invalidation semantics
- Cache coherence relies on context generations.
  - do not reintroduce broad purge-by-pattern invalidation as the normal path
- The cache split is intentional:
  - data cache = expensive filtered/refined dataset
  - render layer = current HTML using widget presentation settings

### 5.3.1.1 Append vs Replace Mode

Append-mode note:
- append / infinite-scroll responses are intentionally smaller
- they return result cards + paging metadata only
- they do not recompute:
  - `tags_html`
  - `available_tags`
  - `available_types`
  - `filter_ui`
  - `year` UI
  - advanced filter UI

Replace-mode note:
- replace mode keeps server-authoritative refinement
- `filter_ui` can be returned fully or diffed by section hash
- replace mode is the path that updates the filter surfaces

Current search behavior:
- search term is normalized server-side
- matching uses title, slug, excerpt, taxonomy term names, and filter meta
- raw `post_content` is no longer part of the default Product Grid search path
- Year filtering inside search uses a JOIN on canonical `_bw_filter_year_int`
  instead of the older `IN (SELECT ...)` pattern
- taxonomy-name search uses JOIN-based matching instead of nested
  `IN (SELECT ...)` term-name subqueries
- searchable meta matching uses JOIN-based matching instead of nested
  `IN (SELECT ...)` postmeta subqueries
- canonical filter meta searched server-side:
  - `_bw_filter_year_int`
  - `_bw_filter_author_text`
  - `_bw_filter_artist_text`
  - `_bw_filter_publisher_text`
- source meta fallback is still checked during migration/backfill support:
  - `_digital_year`, `_bw_biblio_year`, `_print_year`
  - `_bw_biblio_author`, `_print_artist`, `_bw_artist_name`, `_digital_artist_name`
  - `_digital_publisher`, `_bw_biblio_publisher`, `_print_publisher`

Current year-sort optimization:
- when effective ordering resolves to canonical `year_int` and the request is
  already narrowed by search or active advanced filters, Product Grid can sort
  the candidate set in PHP using the cached Year index `post_map`
- the final `WP_Query` then runs on page-sized `post__in` IDs with
  `orderby=post__in`
- this avoids a broader meta JOIN + filesort on the final query path

### 5.3.2 Performance Rules For Filters

- Never introduce:
  - full-table scans in the hot request path
  - unbounded request-path `WP_Query` / collection scans without a hard reason
  - large `post__in` arrays without respecting the existing guard
- Always:
  - reuse cached indexes where available
  - respect generation-based invalidation
  - skip filter-ui recomputation on append
  - keep `filter_ui` section-diffable
- Search-specific rule:
  - do not reintroduce `post_content LIKE`
  - prefer canonical meta, joins on indexed tables, or precomputed/indexed data

Current Year filter behavior:
- discovery drawer only
- backed by canonical numeric meta `_bw_filter_year_int`
- range semantics:
  - `BETWEEN` when both bounds exist
  - `>=` when only `year_from` exists
  - `<=` when only `year_to` exists
- slider commits only on release; direct inputs commit on debounce / blur / Enter
- quick ranges are intentionally suppressed on tiny datasets where they would
  add noise rather than navigation value

Current empty-state copy:
- active refinements/search -> `No results found.`
- empty archive baseline -> `There is nothing in this archive yet.`

Canonical filter meta:
- Year: `_bw_filter_year_int`
- Author: `_bw_filter_author_text`
- Artist: `_bw_filter_artist_text`
- Publisher: `_bw_filter_publisher_text`
 - Source: `_bw_filter_source_text`
 - Technique: `_bw_filter_technique_text`

These values are derived from editorial source meta and kept in sync on product save/meta/category/status changes. Product Grid now maintains:
- a per-context Year index transient for bounds / quick ranges / drawer visibility
- a per-context advanced meta-filter index transient for token option lists and refinement of:
  - Artist
  - Author
  - Publisher
  - Source
  - Technique

Year index bootstraps:
- slider bounds
- quick ranges
- drawer visibility for supported product-family contexts (`digital-collections`, `books`, `prints`)
- the underlying Year index build now uses one JOIN-based SQL pass over:
  - posts
  - term relationships / taxonomy
  - canonical Year meta
  instead of the older two-stage `WP_Query(posts_per_page=-1)` + secondary
  postmeta scan pattern

Index build hardening:
- Year index rebuilds use a lightweight per-context build lock
- advanced filter index rebuilds use the same lock pattern
- concurrent requests briefly wait for the first builder to publish a transient before falling back to a local rebuild
- current index TTL balance:
  - response cache: `10 min`
  - year index: `15 min`
  - advanced filter index: `15 min`

Advanced-filter refinement scope hardening:
- the backend no longer resolves candidate IDs for advanced-filter UI during the default unfiltered context-root scope
- in that state, cached per-context indexes are used directly
- refined candidate scopes are still resolved when needed for:
  - search
  - active advanced filters
  - narrowed category/tag/year scopes

Large candidate safety guard:
- candidate ID arrays are first bounded by the engine-owned candidate cap and then normalized/capped for `post__in` safety at `12000`
- this protects against pathological `IN (...)` growth in very large result scopes
- the trade-off is explicit: at extreme scale the system may prioritise query safety over perfect completeness of the tail of that request

Candidate cap policy:
- `bw_fpw_get_max_candidate_set_size()` is the engine-owned cap for candidate retrieval
- it MUST remain less than or equal to `bw_fpw_get_large_post_in_threshold()`
- the Product Grid adapter does not own or override this limit

Derived dataset caches:
- `bw_fpw_get_related_tags_data()` now has dedicated cache lookup/write
- `bw_fpw_get_available_subcategories_data()` now has dedicated cache lookup/write
- cache keys are canonicalized from the effective filtering state and include
  the Product Grid cache generation counter

Replace-mode filter-ui diffing:
- replace-mode responses now include section hashes via `filter_ui_hashes`
- unchanged sections can be omitted from the response payload
- current diffed sections:
  - `types`
  - `tags`
  - `advanced`
  - `year`
  - `result_count`
- the frontend merges these partial updates into existing shared state

Advanced-filter index invalidation:
- advanced-filter indexes now use per-context generation counters
- normal invalidation no longer depends on broad `DELETE LIKE` scans of
  transient rows in `wp_options`

Advanced-filter index build path:
- the advanced-filter index is built from a single JOIN-based SQL pass over:
  - posts
  - term relationships / taxonomy
  - relevant canonical/source meta keys
- this replaces the older heavier pattern based on broad product-ID collection
  followed by secondary meta resolution
- on normal HTTP requests, a missing advanced-filter index does not block the
  current request on a full rebuild
  - an async cron rebuild is scheduled
  - the real expensive rebuild runs in cron context with lock protection
  - this is intentional to keep frontend request latency flatter on cold misses

### 5.3.3 When Adding A New Filter

Checklist:

- add the filter to shared frontend state
- normalize it at mutation time and again server-side
- include it in the AJAX payload only through the shared request path
- include it in the dataset cache key if it changes eligibility
- implement backend query support with bounded/query-safe logic
- wire it into `filter_ui` only if the UI truly needs dynamic refinement
- ensure append mode does not recompute or resend unnecessary UI for it
- avoid adding heavy request-path scans or new uncached expensive branches

### 5.3.4 Known Trade-Offs (Intentional)

- `post__in` threshold guard:
  - protects query safety on very large candidate sets
- search scope:
  - Product Grid intentionally excludes raw `post_content`
- authoritative refinement:
  - replace-mode UI refinement is still server-authoritative and therefore not free
- partial UI diffing:
  - reduces payload churn, but does not eliminate all replace-mode payload work
- throttling design:
  - anonymous protection is intentionally lightweight and hosting-friendly
  - it is not a full security / anti-bot framework

Current scalability position:
- default browse/search/append flows are significantly lighter after this hardening pass
- replace-mode refinement remains intentionally authoritative and therefore still carries real cost when the filter UI truly needs recomputation
- the largest remaining risk area is still very broad candidate sets combined with expensive refinement scopes

Validation already run:
- `php -l includes/modules/search-engine/engine/search-engine-core.php`
- `node -c assets/js/bw-product-grid.js`
- `composer run lint:main`

Recommended manual QA focus:
- search + filters
- append-mode result growth
- visible filters + popup drawer sync
- Years + year sort
- Digital / Books / Prints contexts
- search `ON/OFF`

Status:
- implemented
- documented
- ready for manual QA / review closure

### 5.4 bw_fpw_refresh_nonce

- Action: `wp_ajax[_nopriv]_bw_fpw_refresh_nonce`
- Input: none
- Returns: `{ nonce }`
- Purpose: refresh an expired Product Grid nonce without a full page reload

### 5.5 Rate limiting

`bw_fpw_is_throttled_request($action_key)` is now a hybrid throttling layer.

Authenticated users:
- still use a server-side transient counter keyed by user ID
- this keeps the original deterministic server-side throttle path

Anonymous users:
- use a signed fixed-window cookie as the primary lightweight counter
- also check a sparse server-side block transient keyed by the anonymous
  fingerprint + action
- fall back to a server-side transient counter only when the cookie path is
  missing, invalid, tampered, or cannot be persisted

This reduces normal anonymous `wp_options` churn while still tightening the
cookie-less / bypass-prone path.

Fingerprint model:
- anonymous: coarse fingerprint from IP + truncated UA
- authenticated: `'u' + user_id`

This remains a practical abuse-control layer, not a heavy security subsystem.

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
