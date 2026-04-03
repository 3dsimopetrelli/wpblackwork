# Product Grid Widget

Filterable or plain masonry/CSS-grid widget for products and posts.
Elementor widget slug: `bw-product-grid`.

Current notable UI/runtime deltas:
- `Filter Settings > Show Filters` is the current dual-mode switch for filtered vs simple grid behavior
- `Filter Settings > Enable Responsive Filter Mode` promotes the drawer interaction to desktop too
- `Filter Settings > Drawer Opening` lets the responsive drawer open from `left` or `right`
- mobile filter trigger uses the new bordered white pill + green icon treatment
- responsive drawer groups are currently labeled `Categories`, `Style / Subject`, and `Years` when the widget resolves to a supported product-family context
- responsive toolbar uses the shared discovery state:
  - global search placeholder inherits the widget query context when a single parent/default category is locked, otherwise falls back to `Search in collections...`
  - result count
  - active-only chips contract above the grid
  - reset action
- responsive filter drawer uses the detached dark-glass floating-panel treatment with light veil overlay, rounded shell, and premium close/apply controls
- mobile first paint is CSS-governed, so desktop filter labels do not flash before JS init
- Product Grid search now also matches canonical derived filter meta:
  - `_bw_filter_year_int`
  - `_bw_filter_author_text`
- canonical filter meta is derived from editorial source fields:
  - Year: `_digital_year`, `_bw_biblio_year`, `_print_year`
  - Author: `_bw_biblio_author`, `_print_artist`, `_bw_artist_name`
- `Layout` includes `Show Title`, `Show Description`, and `Show Price`
- `Layout` includes `Disable Hover Actions on Tablet & Mobile` to suppress product-card hover CTAs and hover media below desktop widths
- `Grid` exposes independent responsive `Post Gap Horizontal` and `Post Gap Vertical` controls for column and row spacing
- under `800px` the discovery controls switch to always-open full-width pills and active chips keep their remove control always visible

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
