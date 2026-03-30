# Product Grid Widget

Filterable or plain masonry/CSS-grid widget for products and posts.
Elementor widget slug: `bw-product-grid`.

Current notable UI/runtime deltas:
- `Filter Settings > Show Filters` is the current dual-mode switch for filtered vs simple grid behavior
- `Filter Settings > Enable Responsive Filter Mode` promotes the drawer interaction to desktop too
- `Filter Settings > Drawer Opening` lets the responsive drawer open from `left` or `right`
- mobile filter trigger uses the new bordered white pill + green icon treatment
- responsive drawer groups are currently labeled `Categories` and `Style / Subject`
- responsive toolbar uses the shared discovery state:
  - global search: `Search in collection...`
  - result count
  - quick filters / selected pills contract
  - reset action
- mobile first paint is CSS-governed, so desktop filter labels do not flash before JS init
- `Layout` includes `Show Title`, `Show Description`, and `Show Price`
- `Layout` includes `Disable Hover Actions on Tablet & Mobile` to suppress product-card hover CTAs and hover media below desktop widths
- `Grid` exposes independent responsive `Post Gap Horizontal` and `Post Gap Vertical` controls for column and row spacing
- under `800px` the discovery controls switch to always-open full-width pills and quick filters collapse to selected-only pills

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
