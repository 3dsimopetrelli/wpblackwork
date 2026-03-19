# Changelog

## Unreleased
- Static Showcase: lazy-load fade-in for all three images (main + gallery) via `loading="lazy"` + CSS opacity transition + JS `is-loaded` class.
- Static Showcase: switched to `wp_get_attachment_image()` for full srcset/sizes support on attachment-based images.
- Static Showcase: batched all `get_post_meta()` calls into a single DB read per render.
- Static Showcase: draft/private products now visible in Elementor editor context.
- Static Showcase: extracted duplicate placeholder HTML into `render_placeholder()`.
- Static Showcase: removed aggressive `margin: 0; padding: 0` wildcard reset inside container; removed redundant `object-fit: cover` from gallery image CSS (set by inline style).
- Static Showcase: registered `bw-static-showcase-script` JS handle; widget declares it via `get_script_depends()`.
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
