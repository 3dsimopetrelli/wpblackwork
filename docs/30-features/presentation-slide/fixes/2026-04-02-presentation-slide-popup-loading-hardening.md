# BW Presentation Slide — Popup & Loading Hardening

**Date:** 2026-04-02  
**Protocol reference:** `docs/governance/task-close.md`  
**Tier classification:** Tier 2 — Widget / Frontend / UX  
**Domain:** Elementor Widget Runtime

## Scope
Focused hardening pass on `bw-presentation-slide` after external radar review.

This pass intentionally addressed only low-risk, implementation-safe items:
- popup image fade-in contract
- shimmer fallback behavior
- reduced-motion support
- popup viewport height fallback
- duplicate wheel-listener guard
- custom cursor movement path optimization
- PHP docblock cleanup

This pass intentionally did **not** replace the Embla `internalEngine()` usage inside the horizontal wheel/trackpad gesture path. That dependency remains a slider-family technical caveat and should be handled consistently across widgets, not only here.

## Files Updated
- `includes/widgets/class-bw-presentation-slide-widget.php`
- `assets/js/bw-embla-core.js`
- `assets/js/bw-presentation-slide.js`
- `assets/css/bw-presentation-slide.css`
- `docs/30-features/presentation-slide/README.md`
- `docs/30-features/presentation-slide/fixes/README.md`
- `docs/30-features/presentation-slide/fixes/2026-04-02-presentation-slide-popup-loading-hardening.md`

## Runtime Changes

### 1. Popup image fade-in fixed
- Popup images now render with `class="bw-embla-img"`.
- This restores compatibility with `BWEmblaCore.initImageLoading()` and prevents popup images from remaining permanently invisible behind `opacity: 0`.

### 2. Shimmer skeleton fallback hardened
- `BWEmblaCore.initImageLoading()` now also marks the nearest `.bw-ps-image` wrapper as `.is-loaded`.
- CSS now hides the shimmer via:
  - `.bw-ps-image.is-loaded::before`
  - existing `:has(img.is-loaded)` enhancement
- Result: older browsers without full `:has()` support no longer leave the shimmer visible forever.

### 3. Reduced-motion support
- Added `@media (prefers-reduced-motion: reduce)` to disable shimmer animation for motion-sensitive users.

### 4. Popup viewport height hardening
- `.bw-ps-popup` now keeps `min-height: 100vh` fallback and upgrades to `100dvh` via `@supports`.
- This reduces Safari mobile viewport-chrome mismatch.

### 5. Wheel handler duplicate-attach guard
- `_attachWheelHandler()` now exits early if the listener is already attached.
- This reduces the risk of duplicated passive-false wheel listeners under unexpected re-entry paths.

### 6. Custom cursor movement path
- Cursor RAF updates now write CSS variables for transform-based movement instead of updating `left/top`.
- This keeps movement on the transform/compositing path and avoids unnecessary layout work.

### 7. PHP cleanup
- Removed the mismatched popup-title docblock that was incorrectly attached above `get_product_context()`.

## Validation
- `php -l includes/widgets/class-bw-presentation-slide-widget.php` -> PASS
- `composer run lint:main` -> PASS

## Remaining Caveat
- Horizontal wheel/trackpad gesture behavior still uses Embla `internalEngine()` for target/snap control.
- This was left unchanged on purpose to avoid introducing a widget-local behavior drift relative to the wider Embla slider family.
