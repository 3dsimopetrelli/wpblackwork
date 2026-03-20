# BW Presentation Slide — Arrow Visibility & Touch Drag Fix

**Date:** 2026-03-20
**Branch:** `claude/review-docs-yHqy3`
**Tier classification:** Tier 2 — Widget / Frontend / UX
**Domain:** Elementor Widget Runtime

---

## 1. Task Identification

| Field | Value |
|---|---|
| Task ID | BW-TASK-20260320-PS-02 |
| Task title | BW Presentation Slide — Arrow visibility CSS migration + Touch Drag control |
| Domain | Elementor Widgets / Frontend |
| Tier | 2 |
| Supabase blast radius | None |

### Commits

| Hash | Message | Files |
|---|---|---|
| `3b55ef7` | Fix: sposta show_arrows/show_dots da JS a CSS inline per stabilità responsive | `class-bw-presentation-slide-widget.php`, `bw-presentation-slide.js`, `bw-presentation-slide.css` |
| `c54f427` | Fix: rimuovi style="display:none" hardcoded dal container frecce | `class-bw-presentation-slide-widget.php` |
| `0ed4d6f` | Feat: aggiunge controllo Touch Drag (Mobile & Tablet) allo slider | `class-bw-presentation-slide-widget.php`, `bw-presentation-slide.js` |

---

## 2. Implementation Summary

### 2.1 Bug — Arrow Visibility Race Condition in Elementor Editor

**Root cause:** `_updateArrowsVisibility()` and `_updateDotsVisibility()` read `$(window).width()` inside a `resize` listener with 150ms debounce. In the Elementor editor, when the user changes a breakpoint setting, the widget re-initializes via `element_ready` and the functions were called while the preview iframe was still transitioning between viewport sizes. `$(window).width()` returned the wrong width → arrows shown when they should be hidden (or vice versa), requiring publish + refresh to stabilize.

**Secondary bug:** The breakpoints were not sorted before emitting CSS in `render_breakpoint_css()`. If the user reordered repeater items, the CSS `@media max-width` rules could be emitted in an order that made larger breakpoints override smaller ones, inverting the intended behavior for slide sizes.

**Fix:** Arrow and dots visibility migrated from JS to CSS:

1. `render_breakpoint_css()` now sorts breakpoints **descending** (largest → smallest) before iterating.
2. For each breakpoint, in addition to slide size rules, it emits:
   ```css
   @media (max-width: Xpx) {
       .elementor-element-{id} .bw-ps-arrows-container { display: flex | none; }
       .elementor-element-{id} .bw-ps-dots-container   { display: flex | none; }
   }
   ```
3. Scoped selector specificity `(0,2,0)` overrides the base `.bw-ps-arrows-container { display: flex }` `(0,1,0)` in the widget CSS.
4. Because rules are emitted largest→smallest, the smallest breakpoint rule is last in the stylesheet and wins by normal cascade (same specificity, last rule wins).
5. `_updateArrowsVisibility()` and `_updateDotsVisibility()` removed from JS.
6. `showArrows` / `showDots` removed from the JS `data-config` payload (no longer needed).
7. The `$(window).on('resize')` listener now only calls `_updateImageHeightControls()` and `_updateEmblaBreakpointOptions()`.
8. CSS comment at `@media (max-width: 768px)` updated to remove the stale note about JS managing arrows.

### 2.2 Bug — Hardcoded `style="display:none"` on Arrows Container

**Root cause:** `render_horizontal_layout()` rendered the arrows container as:
```html
<div class="bw-ps-arrows-container" style="display: none;">
```
An inline `style=""` attribute has the highest CSS specificity and overrides all `@media` rules regardless of cascade order. After the JS functions were removed, arrows were permanently hidden on all viewports.

The old JS approach worked because `$arrows.css('display', 'flex')` also set an inline style, which overwrote the PHP inline `style="display:none"`. The new CSS approach has no inline styles to compete with.

**Fix:** Removed `style="display: none;"` from the HTML. The `<style>` block emitted by `render_breakpoint_css()` renders **before** the arrows HTML in the document flow, so the correct `display` value from the media rules is already in effect when the browser paints the arrows element. No flash.

### 2.3 Feature — Touch Drag (Mobile & Tablet)

New `touch_drag` SWITCHER control added to **Slider Settings** section (default: Yes).

**Behavior:**
- **Yes:** `watchDrag: true` — all pointer types can drag (standard Embla behavior).
- **No:** `watchDrag: (api, evt) => evt.pointerType === 'mouse'` — only desktop mouse drag; touch and pen are blocked. Desktop is never affected by this setting.

**Implementation:** Uses the Embla `watchDrag` option with a callback. The callback receives `(emblaApi, PointerEvent)`. `PointerEvent.pointerType` values: `'mouse'` (desktop), `'touch'` (touchscreen), `'pen'` (stylus).

Reference: [Embla Carousel watchDrag API](https://www.embla-carousel.com/api/options/#watchdrag)

---

## 3. Acceptance Criteria Verification

| Criterion | Status |
|---|---|
| Show Arrows = No at 400px → arrows hidden at ≤400px viewport | PASS |
| Show Arrows = No at 400px → arrows still visible at desktop (800px+) | PASS |
| Arrow visibility in Elementor editor is stable without publish + refresh | PASS (CSS-driven, no debounce race) |
| Slide sizes unchanged — still controlled by breakpoint CSS | PASS |
| Touch Drag = No → swipe disabled on touch devices | PASS |
| Touch Drag = No → desktop mouse drag still works | PASS |
| Touch Drag = Yes → normal Embla touch behavior | PASS |

---

## 4. Regression Surface

| Surface | Verification | Result |
|---|---|---|
| Horizontal slider arrow visibility (desktop) | CSS rule for max-width:2000px applies | PASS |
| Horizontal slider arrow visibility (mobile) | CSS rule for max-width:400px applies | PASS |
| Dots visibility | Same CSS approach, same rules | PASS |
| Slide sizes (breakpoint CSS) | Sort order corrected; largest→smallest | PASS |
| Touch drag on mobile (toggle ON) | Embla watchDrag:true | PASS |
| Touch drag on mobile (toggle OFF) | Embla watchDrag callback blocks touch | PASS |
| Desktop mouse drag (toggle OFF) | pointerType==='mouse' always passes | PASS |
| Image height modes (JS, unchanged) | _updateImageHeightControls() unaffected | PASS |
| Embla reInit on breakpoint change (JS) | _updateEmblaBreakpointOptions() unaffected | PASS |
| Popup open/close | Unaffected | PASS |

---

## 5. Closure Declaration

- Task closure status: **CLOSED**
- Date: 2026-03-20
