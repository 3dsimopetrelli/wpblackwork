# Product Grid Widget

`bw-product-grid` is the Product Grid widget for Blackwork.

It supports:

- filterless or filterable product/post grids
- legacy inline filters and responsive discovery mode
- popup / drawer filters
- desktop visible filters
- runtime sort
- search on/off
- Years
- advanced filters
- chips, reset, and infinite scroll

This file is the entry point only.

## Read Next

- [Architecture Map](product-grid-architecture.md)
  - full technical architecture
  - filter data flow
  - filter rules
  - performance rules
  - cache strategy
  - frontend behavior
  - known trade-offs
- [Fix Reports](fixes/README.md)
  - audit history
  - hardening sessions
  - closure reports
  - [Search Popup Filter Reset Visibility](fixes/2026-04-20-search-popup-filter-reset-visibility.md)
    - state-aware Reset visibility for popup-native filters
  - [Responsive Sort Mapping and Trigger Refinement](fixes/2026-04-19-responsive-sort-mapping-and-trigger-refinement.md)
    - canonical runtime sort keys
    - exact Lucide icon mapping
    - desktop/mobile trigger parity
- [Mobile Filter Drawer Restoration and Accordion Hardening](fixes/2026-04-18-mobile-filter-drawer-restoration.md)
  - responsive drawer restoration
  - accordion behavior notes
  - search visibility and year-source contract
- [Final Audit And Closure](fixes/2026-04-06-product-grid-final-audit-and-closure.md)
  - consolidated closure snapshot for the current Product Grid implementation cycle

## Key Files

| File | Role |
|------|------|
| `includes/widgets/class-bw-product-grid-widget.php` | Elementor widget class and PHP render contract |
| `assets/js/bw-product-grid.js` | Frontend state, AJAX, drawer/visible filters, infinite scroll |
| `assets/css/bw-product-grid.css` | Widget styling and surface behavior |
| `blackwork-core-plugin.php` | AJAX handlers, caching, throttling, backend filtering |
