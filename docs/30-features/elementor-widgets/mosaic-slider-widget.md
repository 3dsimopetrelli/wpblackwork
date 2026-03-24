# Mosaic Slider Widget

## Status
- Status: Implemented
- Runtime slug: `bw-mosaic-slider`
- Class: `BW_Mosaic_Slider_Widget`
- Main class file: `includes/widgets/class-bw-mosaic-slider-widget.php`
- Runtime assets:
  - `assets/js/bw-mosaic-slider.js`
  - `assets/css/bw-mosaic-slider.css`

## Purpose
`Mosaic Slider` is an Embla-based mixed-content widget for editorial asymmetric compositions.

Desktop uses a 5-item mosaic page:
- 1 featured item
- 4 supporting items

Mobile below `1000px` abandons the mosaic and becomes a normal one-card draggable Embla slider.

## Layout Variants
Desktop supports three fixed variants:
- `Big post center`
- `Big post left`
- `Big post right`

Contract:
- each desktop Embla slide is a full mosaic page
- the first queried item in each 5-item batch is always the featured item
- the next four items fill the supporting positions in order

## Content Sources
Current source types:
- `product`
- `post`

### Product Rendering Authority
When `post_type = product`, card rendering is delegated to:
- `BW_Product_Card_Component::render()`

This preserves:
- shared hover-image behavior
- shared hover-video behavior
- shared overlay buttons
- shared title/price/image markup contracts

### Editorial Rendering
When `post_type = post`, the widget uses a local editorial card renderer owned by the widget class.

This renderer is intentionally lighter than the product-card component and does not introduce a second shared product-card authority.

## Query Model
Supported query inputs:
- source type (`product` / `post`)
- category
- sub-category
- manual IDs
- item count
- order by
- order
- randomize on/off

Rules:
- manual IDs override taxonomy filters
- if randomize is on and manual IDs are provided, only the selected ID set is shuffled
- if randomize is on and manual IDs are not provided, query ordering becomes `rand`
- deterministic transient caching is skipped whenever randomize is enabled

## Responsive Contract
- `>= 1000px`: desktop mosaic mode
- `< 1000px`: mobile linear slider mode

Mobile contract:
- cards become equal-sized
- one card per Embla slide
- touch drag remains available unless disabled in widget controls

## Slider Runtime
Shared Embla authority:
- `assets/js/bw-embla-core.js`
- `assets/css/bw-embla-core.css`

Widget-local runtime:
- one active Embla instance per widget
- JS switches between desktop and mobile viewports on breakpoint crossing
- inactive mode is destroyed before the new mode is initialized

## Current Controls
### Query
- `Source Type`
- product category / sub-category
- post category / sub-category
- `Manual IDs`
- `Item Count`
- `Order By`
- `Order`
- `Randomize Items`

### Layout
- `Desktop Mosaic Variant`

### Slider Settings
- `Infinite Loop`
- `Autoplay`
- `Autoplay Speed`
- `Drag Free`
- `Touch Drag`
- `Mouse / Trackpad Drag`
- `Show Arrows`
- `Show Dots`

### Card Settings
- `Show Title`
- `Show Description`
- `Show Price` (products only)
- `Show Overlay Buttons` (products only)
- `Image Size`

### Style
- desktop mosaic height
- horizontal gap
- vertical gap
- image border radius

## Caching
The widget caches deterministic query result IDs using transients prefixed with `bw_ms_` when:
- randomize is off
- Elementor editor mode is not active

Cache invalidation authority:
- `blackwork-core-plugin.php`
- callback: `bw_mosaic_slider_clear_query_cache()`

## Asset Registration
Registered centrally in `blackwork-core-plugin.php`:
- `bw-mosaic-slider-style`
- `bw-mosaic-slider-script`
- shared Embla handles already used across the slider family

## Constraints
- the widget does not replace `bw-product-slider`
- the widget does not introduce popup behavior
- product cards must continue to reuse `BW_Product_Card_Component`
- mobile behavior must stay a real Embla slider, not a CSS-only approximation
