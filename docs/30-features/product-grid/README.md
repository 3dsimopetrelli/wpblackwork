# Product Grid Widget

Filterable or plain masonry/CSS-grid widget for products and posts.
Elementor widget slug: `bw-product-grid`.

Current notable UI/runtime deltas:
- `Filter Settings > Show Filters` is the current dual-mode switch for filtered vs simple grid behavior
- `Filter Settings > Filter Mode` promotes the drawer interaction to desktop too when set to `Filter Panel`
- `Filter Settings > Visible Filters` adds a desktop-only quick-access row above results for shared discovery groups
- `Filter Settings > Drawer Opening` lets the responsive drawer open from `left` or `right`
- mobile filter trigger uses the new bordered white pill + green icon treatment
- responsive drawer groups now support:
  - taxonomy groups: `Categories`, `Style / Subject`
  - numeric meta group: `Years`
  - token-based meta groups:
    - Phase 1: `Artist`, `Author`, `Publisher`
    - Phase 2: `Source`, `Technique`
- responsive toolbar uses the shared discovery state:
  - global search is a real feature flag driven by `Filter Settings > Show Search`
  - optional runtime `Order By` control is driven by `Filter Settings > Show Order By`
  - trigger style can be switched between `icon` and `dropdown` via `Order By Trigger Style`
  - optional desktop `Visible Filters` row reuses the same discovery state and backend filtering for:
    - `Categories` (current `types` group / subcategory-type group)
    - `Artists`
    - `Source`
    - `Year`
  - `Visible Filters` is desktop-only; mobile and tablet still use the `Filters` drawer
  - runtime sorting is backend-authoritative and shares the same AJAX flow as filters
  - supported runtime sort keys:
    - `default`
    - `recent`
    - `oldest`
    - `title_asc`
    - `title_desc`
    - `year_asc`
    - `year_desc`
  - `Default order` maps back to the widget query defaults (`order_by` + `order`)
  - when enabled, the global search placeholder inherits the widget query context when a single parent/default category is locked, otherwise falls back to `Search in collections...`
  - when disabled, Product Grid skips search UI, search runtime wiring, AJAX search payload, and backend search matching work
  - result count
  - active-only chips contract above the grid
  - reset action
- runtime sort is intentionally disabled when the widget is driven by `specific_ids`, so curated `post__in` ordering stays authoritative
- responsive filter drawer uses the detached dark-glass floating-panel treatment with light veil overlay, rounded shell, and premium close/apply controls
- mobile first paint is CSS-governed, so desktop filter labels do not flash before JS init
- Product Grid search now also matches canonical derived filter meta:
  - `_bw_filter_year_int`
  - `_bw_filter_author_text`
- Product Grid advanced meta filters are normalized from comma-separated editorial fields and indexed per supported context:
  - `Artist` -> `_bw_filter_artist_text`
  - `Author` -> `_bw_filter_author_text`
  - `Publisher` -> `_bw_filter_publisher_text`
  - `Source` -> `_bw_filter_source_text`
  - `Technique` -> `_bw_filter_technique_text`
- canonical filter meta is derived from editorial source fields:
  - Year: `_digital_year`, `_bw_biblio_year`, `_print_year`
  - Author / Artist: `_bw_biblio_author`, `_print_artist`, `_bw_artist_name`, `_digital_artist_name`
  - Publisher: `_digital_publisher`, `_bw_biblio_publisher`, `_print_publisher`
  - Source: `_digital_source`
  - Technique: `_digital_technique`, `_print_technique`
- `Layout` includes `Show Title`, `Show Description`, and `Show Price`
- `Layout` includes `Disable Hover Actions on Tablet & Mobile` to suppress product-card hover CTAs and hover media below desktop widths
- `Grid` exposes independent responsive `Post Gap Horizontal` and `Post Gap Vertical` controls for column and row spacing
- under `800px` the discovery controls switch to always-open full-width pills and active chips keep their remove control always visible

## Visible Filters

Visible Filters is an optional desktop-only quick-access surface for the discovery filters.

- it appears only when:
  - `Show Filters = On`
  - `Filter Mode = Filter Panel`
  - `Visible Filters = On`
  - `post_type = product`
- it is rendered between the toolbar controls and the active chips row
- it keeps the existing `Filters` button visible
- it reuses the same Product Grid filter system:
  - `filterState[widgetId]`
  - `filter_ui`
  - advanced filter refinement
  - year filtering logic
  - chips
  - reset
  - infinite scroll reset

V1 visible groups:
- `Categories`
  - this means the current `types` group already used by discovery
  - it does not introduce a new parent-category filtering system
- `Artists`
- `Source`
- `Year`

Visible filter panels:
- are desktop-only anchored floating panels
- use the same dark-glass surface language as the drawer/sort surfaces
- allow only one open panel at a time
- reuse token-group search/count/selection logic for:
  - `Categories`
  - `Artists`
  - `Source`
- reuse the existing Year UI for:
  - slider
  - from/to inputs
  - quick ranges

Reset behaviour:
- `Reset filters` still resets filters only
- it does not change runtime sort

## Runtime Sort (Order By)

### Feature overview

Runtime Sort is the user-facing sort control for the Product Grid.

- it appears in the responsive discovery toolbar only
- it changes the real backend ordering of the current result set
- it is user-driven runtime behavior, not just the editor-defined default query

### Elementor controls

- `Show Order By`
  - switcher
  - available in the filter settings area
  - intended for responsive discovery mode
- `Order By Trigger Style`
  - select
  - values: `icon`, `dropdown`
  - shown only when `Show Order By = yes`

### Runtime state

The runtime sort uses one shared state key:

- `filterState[widgetId].sortKey`

Supported values:

- `default`
- `recent`
- `oldest`
- `title_asc`
- `title_desc`
- `year_asc`
- `year_desc`

This is the single source of truth for both trigger modes.

### Trigger modes

`icon`
- circular green button
- arrow-up-down icon
- opens the shared floating sort menu

`dropdown`
- soft light pill
- dynamic selected label
- chevron on the right
- opens the same shared floating sort menu

Only the trigger UI changes. Sort logic, menu logic, AJAX, and backend mapping stay shared.

### Labels

Trigger labels are compact:

- `default` -> `Default`
- `recent` -> `Latest`
- `oldest` -> `Earliest`
- `title_asc` -> `A–Z`
- `title_desc` -> `Z–A`
- `year_asc` -> `Year ↑`
- `year_desc` -> `Year ↓`

Menu labels stay explicit:

- `Default order`
- `Recently added`
- `Oldest added`
- `Alphabetical A to Z`
- `Alphabetical Z to A`
- `Year, oldest first`
- `Year, newest first`

This keeps the trigger compact and the menu readable.

### Backend mapping

The backend remains authoritative.

- `default` -> widget defaults (`order_by` + `order`)
- `recent` -> `date DESC`
- `oldest` -> `date ASC`
- `title_asc` -> `title ASC`
- `title_desc` -> `title DESC`
- `year_asc` -> `_bw_filter_year_int ASC`
- `year_desc` -> `_bw_filter_year_int DESC`

Year sorting uses canonical meta, not raw editorial year fields.

### Default order

`Default` means the widget’s Elementor query settings.

It is not hardcoded to `date DESC`.

### Interaction with the rest of the system

- filters -> preserved
- Years -> preserved
- advanced filters -> preserved
- search ON/OFF -> compatible
- chips -> sort is not included
- reset filters -> does not reset sort

### Infinite scroll

Changing sort:

- resets paging
- uses replace mode
- restarts from page 1
- prevents mixed ordering between old and new result sets

### specific_ids

If the widget uses `specific_ids`:

- runtime sort is disabled
- the trigger is not rendered

Reason:
- preserve editorial `post__in` ordering

### UI / layering

The sort dropdown menu has dedicated layering rules:

- `.bw-fpw-sort.is-open` elevates the sort layer
- the menu has its own z-index
- it sits above product cards and card overlays
- it remains clickable when open

### Limitations (v1)

- available only in the responsive discovery toolbar
- not implemented in legacy inline mode
- not included in active chips
- not reset by `Reset filters`

### Final status

Runtime Sort is:

- implemented
- integrated with the Product Grid architecture
- stable at code level
- ready for browser QA

## Documents

- [Architecture Map](product-grid-architecture.md) — full PHP/JS/AJAX architecture reference
- [Fixes](fixes/README.md) — chronological list of hardening and bug-fix reports

## Key Files

| File | Role |
|------|------|
| `includes/widgets/class-bw-product-grid-widget.php` | PHP widget class (controls, render, `render_post_item`) |
| `assets/js/bw-product-grid.js` | Frontend JS (filter state, AJAX, infinite scroll, animations) |
| `assets/css/bw-product-grid.css` | All widget styles |
| `blackwork-core-plugin.php` | AJAX handlers (`bw_fpw_*`), rate limiting, transient cache |
