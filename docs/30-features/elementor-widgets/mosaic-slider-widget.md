# Mosaic Slider Widget

## Status
- Status: Implemented
- Visible editor title: `BW-UI Mosaic Slider`
- Runtime slug: `bw-mosaic-slider`
- Class: `BW_Mosaic_Slider_Widget`
- Main class file: `includes/widgets/class-bw-mosaic-slider-widget.php`
- Runtime assets:
  - `assets/js/bw-mosaic-slider.js`
  - `assets/css/bw-mosaic-slider.css`
- Shared runtime dependencies:
  - `assets/js/bw-embla-core.js`
  - `assets/css/bw-embla-core.css`
  - `assets/css/bw-product-card.css`

## Purpose
`Mosaic Slider` is a governed Embla-based mixed-content Elementor widget for editorial layouts built around one featured item plus four supporting items.

The widget exists to cover a layout family that is intentionally different from the canonical `bw-product-slider`:
- desktop uses an asymmetric 5-item mosaic page
- tablet/mobile collapse to a linear Embla slider
- `product` results must continue to reuse the shared product-card authority
- non-product content can still be queried safely through a widget-local editorial card renderer

## Runtime Contract
### Desktop
- Breakpoint: `>= 1000px`
- Embla slide unit: one complete mosaic page
- Page composition:
  - 1 featured item
  - 4 supporting items
- Query results are chunked into deterministic batches of `5`
- The first item in each batch is always the featured item
- The next four items fill supporting slots in query order

### Responsive
- Breakpoint: `< 1000px`
- Desktop mosaic is disabled
- The widget initializes a dedicated mobile/tablet Embla viewport
- Cards return to natural component height
- Alignment remains `start`
- Visible-card count is configurable separately for tablet and mobile
- Decimal values are supported to reveal part of the next slide

## Desktop Layout Variants
The widget exposes four desktop variants:

### `Big Post Center`
- Featured item is centered
- Supporting cards render as two stacked cards on the left and two stacked cards on the right

### `Big Center Split`
- This is the split editorial composition
- The underlying desktop page alternates internally between:
  - `split-left`
  - `split-right`
- This preserves the “broken / split” rhythm while keeping the control surface simple in Elementor

### `Big Post Left`
- True 50/50 desktop split
- Left half: one featured item spanning two rows
- Right half: `2x2` supporting grid

### `Big Post Right`
- True 50/50 desktop split
- Left half: `2x2` supporting grid
- Right half: one featured item spanning two rows

## Auto Scale Modes
Style > Layout exposes two governed desktop scale modes:

### `Auto Scale Mosaic`
- When `Off`:
  - desktop height is controlled manually through `Desktop Mosaic Height`
- When `On`:
  - the mosaic scales proportionally as width shrinks
  - desktop height is no longer manually authored

### `Auto Scale Square Format`
- Visible only when `Auto Scale Mosaic` is enabled
- Meaning:
  - square tile system, not a square outer canvas
- Runtime result:
  - the autoscaled desktop page switches to a `4x2` square-tile composition
  - the featured card occupies a `2x2` square area
  - each supporting card occupies a `1x1` square area

## Query Surface
Supported source types:
- `product`
- `post`

Supported query inputs:
- `Source Type`
- parent category
- sub-category
- `Manual IDs`
- `Item Count`
- `Order By`
- `Order`
- `Randomize Items`

Query rules:
- manual IDs override taxonomy filters
- if randomize is enabled and manual IDs are present, only the selected ID set is shuffled
- if randomize is enabled and manual IDs are not present, the query uses randomized ordering
- deterministic transient caching is skipped when randomize is enabled
- parent category controls now support multi-select for both `product` and `post` source types
- when one or more parent categories are selected, taxonomy matching uses `IN` with children included
- sub-category selections still override the parent-category filter for the active source type

## Rendering Authority
### Product path
When `post_type = product`, rendering authority remains:
- `BW_Product_Card_Component::render()`

This preserves:
- shared hover-image logic
- shared hover-video logic
- shared explicit image-loading inputs
- shared price markup
- shared overlay button system
- shared add-to-cart state handling

### Editorial path
When `post_type = post`, the widget uses its own lighter editorial renderer.

This path is intentionally local to the widget and does not create a second shared product-card system.

## Slider Settings
### Content > Layout
- `Desktop Mosaic Variant`
- informational breakpoint note for responsive behavior below `1000px`

### Content > Slider Settings
- `Infinite Loop`
- `Autoplay`
- `Autoplay Speed`
- `Drag Free`
- `Touch Drag (Mobile & Tablet)`
- `Mouse / Trackpad Drag (Desktop)`
- `Show Arrows`
- `Show Dots`

Responsive drag note:
- touch drag is only applied to the responsive/mobile runtime
- desktop drag is governed separately by the mouse/trackpad setting
- responsive wheel/two-finger horizontal scrolling is handled in the widget JS runtime

### Content > Card Settings
- `Show Title`
- `Show Description`
- `Show Price`
  - products only
- `Show Overlay Buttons`
  - products only
- `Hide Overlay Buttons`
  - responsive control
  - products only
- `Image Size`

## Style Controls
### Style > Layout
- `Auto Scale Mosaic`
- `Auto Scale Square Format`
  - shown only when `Auto Scale Mosaic` is enabled
- `Desktop Mosaic Height`
  - shown only when `Auto Scale Mosaic` is disabled
- `Horizontal Gap`
- `Vertical Gap`
- `Tablet Visible Slides`
  - supports decimals like `3.2`
- `Mobile Visible Slides`
  - supports decimals like `2.2`

Layout spacing note:
- `Horizontal Gap` governs the desktop internal column spacing
- responsive visible-slide math and responsive guttering are both derived from the same width model
- the desktop slide wrapper no longer adds extra left/right inline padding, so the mosaic page aligns cleanly with surrounding copy

### Style > Images
- `Image Border Radius`

### Style > Text
- Title typography
- Title padding
- Description typography
- Description padding
- Price typography
  - products only
- Price padding
  - products only
- `Text Items Gap`
  - responsive
  - controls distance between title / description / price blocks

Text spacing note:
- widget CSS resets residual title/description/price margins so `Text Items Gap` remains the dominant spacing control

## Overlay Button Contract
The widget reuses the shared product-card overlay button authority and adds widget-local constraints:
- overlay button group maximum width is capped at `280px`
- overlay button text size is normalized to `12px`
- button text remains fixed across large and small cards
- the width cap applies both to:
  - a single `View Product` button
  - a combined `View Product + Add to Cart` group

## Caching Contract
Deterministic query caching uses transients prefixed with:
- `bw_ms_`

Caching rules:
- active only when randomize is off
- skipped in Elementor editor mode
- cache payload stores queried post IDs
- invalidation authority lives in:
  - `blackwork-core-plugin.php`
  - callback: `bw_mosaic_slider_clear_query_cache()`

## Image Loading Contract
The widget uses a governed two-stage loading policy so hidden responsive fallback markup does not consume high-priority image bandwidth by accident.

### Server-side defaults
- desktop first page primary images start as `loading="auto"`
- later desktop pages stay `loading="lazy"`
- first three mobile cards start as `loading="auto"`
- later mobile cards stay `loading="lazy"`
- `fetchpriority` is not hard-coded server-side for Mosaic tiles
- product hover images remain lazy through `BW_Product_Card_Component`

### Client-side promotion
- after mode detection, JS promotes only the active viewport primary images
- desktop promotes the first `5` primary images of the active mosaic page to eager loading
- mobile promotes the first `3` primary images to eager loading
- only the first promoted active image receives `fetchpriority="high"`
- the inactive hidden viewport is explicitly demoted back to lazy loading

### Reveal timing
- wrapper starts server-rendered in loading state to prevent a first-paint flash before JS hydration
- widget wrapper `.loading` is not removed after bare Embla init anymore
- reveal now waits for the first active primary image:
  - `.bw-slider-main`
  - `.bw-ms-editorial-image`
- when supported, reveal waits for image decode completion instead of only the `load` event
- a timeout fallback prevents permanent loading state if an image errors or stalls

## JS Runtime Behavior
Widget-local runtime authority:
- `assets/js/bw-mosaic-slider.js`

Behavior:
- one active Embla instance per widget
- mode detection switches between desktop and mobile viewports
- previous mode is destroyed before the next is initialized
- active viewport primary images are promoted to eager/high-priority only after mode resolution
- hidden inactive viewport primary images are demoted to lazy
- responsive mode attaches horizontal wheel handling for trackpad/two-finger scrolling
- wrapper loading state is removed after the first active primary image is ready, with a bounded timeout fallback

## CSS Runtime Notes
Widget-local CSS authority:
- `assets/css/bw-mosaic-slider.css`

Important implementation details:
- desktop slide width is full-page Embla
- desktop gutter is applied through the desktop slide wrapper
- viewport uses `box-sizing: border-box`
  - this prevents width drift when `padding-inline` is used
- responsive slide width is calculated from:
  - visible-slide count
  - shared gap variable
- square autoscale mode changes the grid contract, not only the outer page ratio

## Regression Checklist Summary
Minimum runtime checks for this widget:
- widget initializes without console errors
- Elementor editor re-render destroys and rebuilds a single instance correctly
- all four desktop layout variants render in their correct geometry
- desktop paging stays in deterministic 5-item batches
- product queries render through `BW_Product_Card_Component`
- randomize bypasses deterministic cache reuse
- responsive mode activates below `1000px`
- responsive visible-slide decimals reveal the next slide correctly
- responsive drag works for touch and horizontal wheel/trackpad gestures
- overlay buttons respect the widget-local width cap and fixed `12px` typography

## Constraints
- `Mosaic Slider` does not replace `bw-product-slider`
- it must not introduce a second product-card authority
- it must remain a real Embla slider in responsive mode
- desktop and responsive viewports must never be initialized at the same time
- popup/modal behavior is out of scope
