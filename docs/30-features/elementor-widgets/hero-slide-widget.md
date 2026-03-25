# Hero Slide Widget

## Status
- Status: Implemented
- Visible editor title: `BW-UI Hero Slide`
- Runtime slug: `bw-hero-slide`
- Class: `BW_Hero_Slide_Widget`
- Main class file: `includes/widgets/class-bw-hero-slide-widget.php`
- Runtime assets:
  - `assets/css/bw-hero-slide.css`

## Purpose
`Hero Slide` is a premium hero-section Elementor widget built for editorial landing blocks with:
- large centered title
- centered subtitle
- CTA button row/grid
- full-width background image
- responsive spacing and height controls

The widget is intentionally implemented as:
- `Static` now
- `Slide` later

V1 does not introduce slider runtime complexity. It keeps the data surface and mode selector ready for a future slide implementation while rendering a governed static hero layout today.

## Runtime Contract
### Mode surface
The widget exposes:
- `Static`
- `Slide`

Current behavior:
- `Static` is the only implemented runtime mode
- if `Slide` is selected, the widget still fails closed to the static hero rendering contract
- Elementor shows an editor note explaining that slide mode is reserved for future implementation

### Rendering structure
Current static render includes:
- background image layer
- overlay / glow layer
- centered content shell
- title
- subtitle
- flex-wrapping CTA button list

No JS runtime is required in V1.

## Visual Direction
The current widget is designed to match the repository request references:
- dark premium atmosphere
- soft centered glow
- large centered headline
- centered subtitle
- rounded glass-like CTA buttons
- button wrapping preserved on smaller screens

The look is owned by widget-local CSS, not by a shared slider skin.

## Content Controls
### Content
- `Mode`
  - `Static`
  - `Slide`
- `Title`
  - safe inline HTML supported for emphasis
  - current allowlist: `u`, `strong`, `em`, `span`, `br`
- `Subtitle`
- `Background Image`

### Buttons
Buttons are authored through a repeater.

Each repeater item supports:
- `Button Text`
- `Link Type`
  - `Manual URL`
  - `Product Category Archive`
  - `Post Category Archive`
  - `Archive Page`
- `Link`
  - Elementor `URL` control when `Manual URL` is selected
- `Product Category`
  - shown when `Product Category Archive` is selected
- `Post Category`
  - shown when `Post Category Archive` is selected
- `Archive Page`
  - shown when `Archive Page` is selected

### Content > Layout
- `Hero Height`
  - responsive
  - `px / vh / %`
- `Content Max Width`
  - responsive
  - `px / % / vw`

## Link Resolution Contract
The widget does not introduce a repository-wide CTA routing framework.

V1 supports these link targets cleanly:
- manual URL
- WooCommerce product category archive via `get_term_link( term_id, 'product_cat' )`
- WordPress post category archive via `get_term_link( term_id, 'category' )`
- archive page via:
  - `page_for_posts` / blog archive for `post`
  - `get_post_type_archive_link()` for archive-capable public post types

Manual URLs reuse Elementor-native link attribute handling through:
- `Controls_Manager::URL`
- `add_link_attributes()`

## Style Controls
### Style > Layout
- `Content Alignment`
  - responsive
  - left / center
- `Section Padding`
  - responsive
- `Overlay Color`
- `Glow Color`
- `Overlay Opacity`

### Style > Title
- typography
- color
- padding
  - responsive

### Style > Subtitle
- typography
- color
- padding
  - responsive

### Style > Buttons
- `Border Width`
- `Border Radius`
  - responsive
- `Glass Effect`
- `Fill Enabled`
- `Fill Color`
- `Border Color`
- button typography
- text color
- `Button Padding`
  - responsive
- `Button Horizontal Gap`
  - responsive
- `Button Vertical Gap`
  - responsive

## Layout Behavior
### Desktop
- full-width hero
- content centered inside a max-width content shell
- buttons render in a horizontal row and wrap when required

### Tablet / Mobile
- content remains centered by default unless alignment is changed
- button row wraps into multiple lines
- button spacing remains controlled independently in both axes
- hero height remains device-specific through responsive `px / vh / %` controls

## Background Media Behavior
- background image is rendered as a dedicated media layer
- image uses `cover`
- image does not distort
- the overlay/glow system sits above the media and below the content
- if no background image is selected, the widget falls back to a dark gradient background
- title HTML is normalized before sanitization so editor paragraph wrappers do not break the single H1 contract

## Button Visual Contract
The widget uses a local premium button treatment:
- rounded outline-first shape
- optional translucent glass effect
- optional fill mode
- subtle upward hover movement
- no exaggerated motion

Current runtime defaults:
- dark premium hero
- light text
- restrained border
- low-intensity glass fill

## Future Slide Mode Recommendation
The current implementation is intentionally ready for future extension:
- `Mode` is already part of the widget data contract
- static rendering is isolated from potential future slide runtime
- current CSS namespace is widget-local and can support a future slide shell

Recommended future direction for `Slide` mode:
- keep current static hero as the single-slide visual authority
- add a slide data model only when needed
- introduce slider runtime only when multiple hero panels are actually implemented
- reuse existing Embla family architecture if `Slide` mode becomes real

## Asset Registration
Asset registration authority remains centralized in:
- `blackwork-core-plugin.php`

Current asset handle:
- `bw-hero-slide-style`

V1 does not register a widget-local script because the current runtime is static.

## Regression Checklist Summary
Minimum validation for this widget:
- widget loads in Elementor without errors
- `Static` mode renders correctly
- `Slide` mode fails closed to static rendering without breaking output
- title/subtitle/buttons/background image render safely
- responsive `Hero Height` applies per device
- responsive `Content Max Width` applies per device
- buttons wrap cleanly on tablet/mobile
- all supported link target types resolve to valid URLs
- glass/fill button styles behave correctly without breaking accessibility

## Constraints
- V1 must not add slider runtime
- V1 must remain lightweight
- no popup or AJAX behavior is part of this widget
- no generic repository-wide link-builder abstraction is introduced in this wave
