# Changelog

## Unreleased
- BW Presentation Slide: custom cursor redesigned to fixed glassmorphism (single on/off toggle; 10+ configuration controls removed).
- BW Presentation Slide: fixed orphaned popup overlay in `<body>` on Elementor widget destroy/re-render.
- BW Presentation Slide: fixed `TypeError: Cannot read properties of undefined (reading 'removeClass')` — selector cache now assigned before `emblaCore.init()`.
- BW Presentation Slide: fixed system cursor hidden over arrow buttons when custom cursor is active.
- BW Presentation Slide: dead code removal, selector caching, breakpoint sort/break refactor, CSS specificity cleanup (three audit waves).
- Docs: created `docs/30-features/presentation-slide/` with feature README, fixes index, and hardening report.
- Docs: final cleanup pass (templates moved, auth consolidated, checkout docs normalized).
- Product Grid: race condition fix (abort stale subcategory/tag AJAX requests).
- Product Grid: PHP→data-attr→JS pipeline for render settings (image_size, image_mode, hover_effect, open_cart_popup).
- Product Grid: rate limiting extended to authenticated users (300/300/200 req/min, keyed by user ID).
- Product Grid: `destroyWidgetState()` function + MutationObserver for full state cleanup on re-render and editor deletion.
- Product Grid: documented `is-loading` vs `is-loading-visible` coupling.
- Docs: added `docs/30-features/product-grid/` with architecture map and hardening report.

## 2026-02-26
- Full documentation refactor and consolidation into `docs/`.
