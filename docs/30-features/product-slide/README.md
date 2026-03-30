# BW Product Slider

## Overview

`bw-product-slider` is the current canonical product slider widget in the repository.

Current runtime authority:
- widget class: `includes/widgets/class-bw-product-slider-widget.php`
- widget JS: `assets/js/bw-product-slider.js`
- widget CSS: `assets/css/bw-product-slider.css`
- shared carousel runtime: `assets/js/bw-embla-core.js`
- shared carousel base styles: `assets/css/bw-embla-core.css`
- shared card renderer: `includes/components/product-card/class-bw-product-card-component.php`

This widget is query-driven, horizontal-only, Embla-based, and delegates slide content to the shared product-card component.

## Runtime Identity

- slug: `bw-product-slider`
- class: `BW_Product_Slider_Widget`
- visible editor title: `BW-UI Product Slider`
- Elementor category: `blackwork`

## Current Feature Surface

### Query
- post type
- specific IDs override
- parent product category multi-select
- subcategory multi-select
- order by / order
- posts per page

### Slider Settings
- infinite loop
- autoplay
- drag free
- touch drag on/off
- mouse / trackpad drag on/off
- slide alignment (`start`, `center`, `end`)

### Responsive Breakpoints
- per-breakpoint max width
- slides to show
- slides to scroll
- show arrows
- show dots
- center mode
- variable width
- fixed slide width
- peek amount

### Card Settings
- show title
- show description
- show price
- show overlay buttons
- hover image source (`meta` or first gallery image)
- image size

## Architecture Notes

### Rendering
- server-rendered in PHP
- query performed via `WP_Query`
- cards rendered through `BW_Product_Card_Component::render()`
- no popup runtime
- no custom cursor runtime
- no vertical/elevator mode

### Carousel Runtime
- one instance per widget wrapper
- runtime class: `BWProductSlider`
- shared engine: `BWEmblaCore`
- editor-aware re-init via:
  - `frontend/element_ready/bw-product-slider.default`

### Breakpoint Split of Responsibilities
- PHP emits instance-scoped breakpoint CSS via `render_breakpoint_css()`
- JS performs Embla `reInit()` for interaction changes via `build_responsive_config()`

This means:
- slide width and arrows/dots visibility are CSS-governed
- slide scroll behavior and align reconfiguration are JS-governed

## Product Card Integration

Because the widget delegates to `BW_Product_Card_Component`, it automatically inherits:
- shared hover image logic
- shared hover video logic
- overlay buttons
- shared price/title/image rendering contracts
- shared default text baseline when Elementor typography is not explicitly authored:
  - title `14px`
  - description `14px`
  - price `12px`

Current hover-media precedence inherited from the component:
1. `_bw_slider_hover_video`
2. `_bw_slider_hover_image`
3. no hover media

## Caching

The widget caches query result IDs using transients prefixed with `bw_ps_` when:
- `orderby` is not `rand`
- Elementor editor mode is not active

Cache invalidation authority:
- `blackwork-core-plugin.php`
- callback: `bw_ps_clear_query_cache()`
- hook: `save_post`
- scope: WooCommerce product saves

## Asset Registration

Registered centrally in `blackwork-core-plugin.php`:
- `embla-js`
- `embla-autoplay-js`
- `bw-embla-core-js`
- `bw-embla-core-css`
- `bw-product-slider-script`
- `bw-product-slider-style`

The widget then consumes those handles through `get_script_depends()` and `get_style_depends()`.

## Historical Note

The subfolder `fixes/` contains historical fix reports from the older product-slide era.

Those records remain useful as historical context, but they are not the current runtime authority for the live widget now implemented as `bw-product-slider`.
