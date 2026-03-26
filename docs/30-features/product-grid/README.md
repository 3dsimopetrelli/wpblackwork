# Product Grid Widget

Filterable masonry/CSS-grid widget for products and posts.
Elementor widget slug: `bw-product-grid`.

Current notable UI/runtime deltas:
- mobile filter trigger uses the new bordered white pill + green icon treatment
- mobile first paint is CSS-governed, so desktop filter labels do not flash before JS init
- `Layout` includes `Disable Hover Actions on Tablet & Mobile` to suppress product-card hover CTAs and hover media below desktop widths

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
