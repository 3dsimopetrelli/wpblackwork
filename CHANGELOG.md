# Changelog

## Unreleased
- Docs: aligned `BW-SP Price Variation` documentation with current runtime reality, clarifying compact reviews-as-trust usage, variation-bound license disclosure accordion behavior, single-axis selector constraints, and current `More payment options` render gating.
- Product Grid: redesigned the mobile filter trigger as a bordered white pill with green icon shell, moved mobile first-paint filter visibility into CSS to prevent desktop-filter flash on reload, and added `Disable Hover Actions on Tablet & Mobile` in `Layout`.
- Title Product: added `Big Text`-style responsive title sizing controls (`Max Text Width`, `Fixed/Fluid` mode, fluid min/max sizes and viewport bounds) and changed the default title weight to `500`.
- Product Card: aligned shared default typography baseline to title `14px`, description `14px`, and price `12px`, with Product Slider defaults realigned to the same contract when no Elementor typography override is present.
- Showcase Slide: CTA arrow now renders a dedicated chevron SVG, and `Style > Link Button` now exposes responsive typography for the green CTA text pill.
- Showcase Slide: added breakpoint-level fixed frame ratios (`3:2`, `4:3`, `1:1`, `16:9`), curated `Classic Photo (3:2)` size presets (`Balanced`, `Large`, `XL Peek`), and universal `Start Offset Left` viewport spacing for first-card breathing room.
- Product Grid: `Desktop Columns` now supports `5` and `6`, and `Style > Text` now exposes content gap plus title/description/price color, typography, and padding controls.
- Mosaic Slider: hardened image loading and first reveal by promoting only the active viewport primary images, demoting hidden fallback markup to lazy, and waiting for the first image decode-ready state before reveal.
- Header: added `Hero Overlap` mode with page targeting, dedicated admin tab, fixed-overlay startup, transparent wrapper, and reuse of the existing dark-zone detection for white-on-dark hero starts.
- Header: hardened `Hero Overlap` boot and visuals by fixing empty admin tabs, removing first-paint jump, enabling mobile glass from overlay start, syncing mobile icon dark-zone color behavior with the logo, keeping desktop `Search` text black on the green pill, and removing the temporary glass border.
- Hero Slide: added new `BW-UI Hero Slide` Elementor widget with static-first hero rendering, responsive height/max-width controls, background image layer, and future-ready `Slide` mode surface.
- Hero Slide: title sanitization now allows inline `style` on `<span>`, so custom underline treatments entered in Elementor WYSIWYG render correctly on the frontend.
- Mosaic Slider: added new `BW-UI Mosaic Slider` Elementor widget with desktop 5-item asymmetric mosaic pages, mobile linear Embla fallback, and shared `BW_Product_Card_Component` reuse for product results.
- Price Variation: removed "Other Payment Methods" section entirely (controls, style section, render block, JS toggle handler, CSS).
- Price Variation: removed "Open Cart Popup" control — cart popup always opens on add-to-cart via AJAX handler; control was unused.
- Price Variation: fixed dead-code bug in `get_default_variation()` — bare `return;` prevented fallback to first variation; now correctly falls back to `$variations_data[0]` when no in-stock variation exists.
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
