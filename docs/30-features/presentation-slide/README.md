# BW Presentation Slide Widget

## Overview

`bw-presentation-slide` is the specialized gallery/presentation slider Elementor widget for Blackwork.
It is distinct from `bw-product-slide` (product-contextual slider) and is the canonical widget for standalone image galleries.

## Key Files

| File | Role |
|---|---|
| `includes/widgets/class-bw-presentation-slide-widget.php` | Elementor widget class (PHP) — controls, HTML render |
| `assets/js/bw-presentation-slide.js` | Runtime class `BWPresentationSlide` — Embla init, cursor, popup |
| `assets/css/bw-presentation-slide.css` | Widget styles — layouts, cursor, popup, responsive |
| `assets/js/bw-embla-core.js` | Shared Embla carousel core (`BWEmblaCore`) |
| `assets/css/bw-embla-core.css` | Shared Embla base styles |

## Layouts Supported

### Horizontal (Embla carousel)
- Single-row carousel with Embla.
- Supports: infinite loop, alignment (start/center/end), drag-free, autoplay.
- Responsive breakpoints: configurable slide width per viewport via Elementor controls.
- Image height modes: `auto`, `fixed`, `contain`, `cover`.
- Navigation: arrow buttons (absolute positioned), dot pagination.

### Vertical — Desktop (Elevator)
- Two-column layout: scrollable thumbnail strip on the left, main images on the right.
- Thumbnail click scrolls the main panel to the target image.
- Above 1024px only; falls back to responsive mode on mobile.

### Vertical — Responsive (Embla)
- Main Embla viewport + thumb Embla viewport synchronized.
- Thumbnail tap activates the corresponding main slide.

## Custom Cursor

A single glassmorphism custom cursor, toggled on/off via `enable_custom_cursor`.
Design is fixed — no user configuration:
- 88×88 px circle, frosted glass fill (`rgba(255,255,255,0.12)`, `backdrop-filter: blur(14px) saturate(1.8)`).
- Border: `1px solid rgba(0,0,0,0.28)`.
- Shows `←` / `→` glyphs on left/right image halves; `+` on center (for popup-enabled slides).
- Animated via RAF with easing; disabled on mobile (`@media max-width:768px`).
- System cursor is hidden over the widget area; restored over arrow navigation buttons.

## Popup / Modal

- Opens on image click when `enable_popup` is enabled.
- Overlay is appended to `<body>` (required for `position:fixed`).
- Full-screen white overlay with sticky header and scrollable image list.
- Popup title sourced from product name (WooCommerce context) or widget setting.
- Properly removed from DOM in `destroy()` to prevent orphaned overlays on Elementor re-render.

## Product Context

`get_product_context()` (PHP) resolves the WooCommerce product in scope:
1. Global `$product` if set and valid.
2. `bw_tbl_resolve_product_context_id()` fallback for Theme Builder Lite contexts.

Used by both `get_popup_title()` and `get_images_for_render()`.

## JavaScript Architecture

- Class: `BWPresentationSlide` (one instance per widget).
- Initialized via `elementorFrontend.hooks.addAction('frontend/element_ready/bw-presentation-slide.default', ...)`.
- Embla instances: `this.emblaCore` (horizontal), `this.emblaMain` / `this.emblaThumbs` (vertical responsive).
- Event namespacing: `.bwps-{widgetId}` and `.bwps-cursor-{widgetId}` for clean per-instance teardown.
- `destroy()` removes cursor element, popup overlay, all namespaced events, RAF animation.

## Known Constraints

- Custom cursor is desktop-only (`display:none !important` at ≤768px).
- Arrow button cursor (`cursor:pointer`) is explicitly restored via CSS override so `bw-ps-hide-cursor` does not suppress it.
- Breakpoints sorted ascending; first match wins (`break` after match).
- Selector cache (`_$horizontal`, `_$images`) must be assigned **before** `emblaCore.init()` because `onSelect` fires during init.

## Fixes Log

See `docs/30-features/presentation-slide/fixes/` for session-level hardening reports.
