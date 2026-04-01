# Basic Slide Widget

## Purpose
`bw-basic-slide` is a lightweight image-gallery widget with two presentation modes:
- `Slide` — Embla-based horizontal carousel
- `Wall` — responsive image wall with optional bottom fade gradient

It is designed as a simpler image-only companion to the more specialized BW slider widgets.

## Audit Status
Audit date:

```text
2026-04-01
```

Current audit result:
- documentation aligned to the active widget implementation
- no popup/gallery-overlay runtime exists in this widget
- `Wall` mode is now a fixed clipped surface with optional gradient, not an internally scrollable container
- `Slide` mode currently supports autoplay, desktop mouse drag, and horizontal two-finger trackpad gestures
- runtime still uses the same Embla wheel/internals pattern already present in the wider slider family; this remains an implementation caveat, not a newly introduced divergence

## Runtime Authority
Widget file:
- `includes/widgets/class-bw-basic-slide-widget.php`

Frontend assets:
- `assets/css/bw-basic-slide.css`
- `assets/js/bw-basic-slide.js`

Shared dependency:
- `assets/js/bw-embla-core.js`
- `assets/css/bw-embla-core.css`

Asset registration:
- `blackwork-core-plugin.php`

## Widget Contract
Widget slug:

```text
bw-basic-slide
```

Editor title:

```text
BW-UI Basic Slide
```

Category:

```text
blackwork
```

## Editor Controls

### General
- `Mode`
  - `Slide | Wall`
- `Add Images`
  - Elementor gallery source
- `Image Resolution`
  - `thumbnail | medium | medium_large | large | custom_1200 | custom_1500 | full`

### Slide Settings
Visible only when `Mode = Slide`.

- `Infinite Loop`
- `Autoplay`
- `Autoplay Speed`
- `Touch Drag`
- `Mouse / Trackpad Drag`
- `Slide Alignment`

### Responsive Breakpoints
Visible only when `Mode = Slide`.

Each breakpoint row currently supports:
- `Breakpoint (px)`
- `Slides to Show`
- `Slides to Scroll`
- `Show Arrows`
- `Show Dots`
- `Start Offset Left`
  - supports `px | % | vw`
- `Center Mode`
- `Variable Width`
  - current authority for proportional mixed-width strips
  - when enabled with a fixed image height, each slide width follows the image ratio
- `Slide Width (px)` when variable width is off
- `Image Height Mode`
  - `Scale / Original Ratio`
  - `Fixed Height`
  - `Contain`
  - `Cover`
- `Image Height`

### Wall Settings
Visible only when `Mode = Wall`.

- responsive `Columns`
- responsive `Wall Height`
- `Bottom Gradient` on/off
- `Gradient Color`
- responsive `Gradient Height`

### Style > Images
- responsive `Gap`
- responsive `Image Radius`

### Style > Navigation
Visible only when `Mode = Slide`.

- `Arrow Color`
- `Arrow Size`

## Rendering Behavior

### Slide mode
- renders a pure image Embla carousel
- uses responsive breakpoint CSS emitted by PHP for:
  - slide width
  - arrow visibility
  - dots visibility
  - image height behavior
- JS re-initializes Embla `slidesToScroll` / alignment when the active breakpoint changes
- desktop horizontal two-finger trackpad gestures are intercepted through the same wheel-driven pattern used by the other BW Embla sliders
- first visible image is promoted with `fetchpriority="high"` and `decoding="sync"`
- first visible slide group is eagerly loaded based on the largest configured breakpoint
- autoplay is supported through `embla-autoplay-js`
- per-instance breakpoint CSS is generated during render and attached to the widget style handle through `wp_add_inline_style()`

### Wall mode
- renders a responsive CSS grid, not a masonry layout
- preserves image aspect ratio naturally
- clips visually when the wall height is constrained; it does not create an internal mouse/trackpad scroll area
- optional bottom gradient communicates that additional content continues below
- no `View all` CTA is currently rendered

## Image Loading Contract
- gallery images are rendered through WordPress attachment APIs when IDs are available
- first image is promoted to:
  - `loading="eager"`
  - `fetchpriority="high"`
  - `decoding="sync"`
- additional initial images are eager-loaded up to the desktop visible-count contract
- remaining images stay lazy
- slide mode uses `BWEmblaCore.initImageLoading()` for progressive image reveal
- wall mode also reuses that fade-in helper even without carousel runtime

## Current Limitations
- `Wall` mode does not render a CTA button yet
- `Wall` mode uses simple responsive columns, not a per-breakpoint repeater contract
- `Slide` mode is intentionally image-only and does not render captions/content layers
- the desktop wheel/trackpad gesture path still relies on Embla internal engine access for its snap/target behavior

## Documentation Alignment Notes
- the inventory/status layer must describe `Wall` mode as visually clipped, not scrollable
- the widget-system README should treat `bw-basic-slide` as the current lightweight generic image-gallery surface
- architecture-direction should place `bw-basic-slide` in its own lightweight gallery family rather than conflating it with presentation/product/showcase sliders

## Related Documentation
- `docs/30-features/elementor-widgets/widget-inventory.md`
- `docs/30-features/elementor-widgets/README.md`
- `docs/30-features/elementor-widgets/architecture-direction.md`
