# Changelog

## Unreleased
- Docs: final cleanup pass (templates moved, auth consolidated, checkout docs normalized).
- Product Grid: race condition fix (abort stale subcategory/tag AJAX requests).
- Product Grid: PHP→data-attr→JS pipeline for render settings (image_size, image_mode, hover_effect, open_cart_popup).
- Product Grid: rate limiting extended to authenticated users (300/300/200 req/min, keyed by user ID).
- Product Grid: `destroyWidgetState()` function + MutationObserver for full state cleanup on re-render and editor deletion.
- Product Grid: documented `is-loading` vs `is-loading-visible` coupling.
- Docs: added `docs/30-features/product-grid/` with architecture map and hardening report.

## 2026-02-26
- Full documentation refactor and consolidation into `docs/`.
