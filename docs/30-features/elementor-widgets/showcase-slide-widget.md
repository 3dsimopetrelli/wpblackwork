# Showcase Slide Widget

## Status
- Status: Implemented
- Runtime slug: `bw-showcase-slide`
- Class: `BW_Showcase_Slide_Widget`
- Main class file: `includes/widgets/class-bw-showcase-slide-widget.php`
- Runtime assets:
  - `assets/js/bw-showcase-slide.js`
  - `assets/css/bw-showcase-slide.css`

## Purpose
`Showcase Slide` is the Embla-based curated showcase slider for editorial product storytelling sourced from the product showcase metabox.

It sits between:
- `BW Presentation Slide`
- `BW Static Showcase`

The widget combines:
- slider behavior and responsive breakpoint control from the current Embla slider family
- showcase content authority from the existing metabox
- a high-fidelity split-pill CTA layout

## Content Authority
The widget reads from the existing product showcase metabox fields.

Import reference:
- `import-info/showcase-slide-metabox-import-map.md`

Current content surfaces:
- `Showcase Title`
- `Showcase Description`
- digital/physical supporting metadata
- CTA text/link
- showcase image
- `Texts color`

### Product Type Branching
The widget reads `_bw_product_type` from the showcase metabox and renders the footer differently depending on that value.

- `digital`
  - uses the digital data fields
  - renders footer metadata as pill badges
  - badge sources:
    - `_bw_assets_count`
    - `_bw_file_size`
    - `_bw_formats`
- `physical`
  - does not render badge pills
  - renders two plain text lines in the footer area
  - text sources:
    - `_bw_info_1`
    - `_bw_info_2`

This is an explicit runtime branch, not just an admin-only metabox visibility rule.

### Text Color Rule
`Texts color` from the metabox is the single authority for text and badge color inside the slide.

This includes:
- title
- description
- metadata labels
- in-slide textual surfaces that inherit the card text token

No automatic contrast detection should be introduced.

## Query / Selection Model
The widget uses manual product ID composition.

Current behavior:
- the editor enters a comma-separated list of product IDs
- products are rendered in the exact order provided
- only published WooCommerce products are queried
- if a product has no showcase image meta, the featured image is used as fallback

## Controls Direction

### Content
- `Query`
  - `Product IDs`
- `Slider Settings`
  - `Infinite Loop`
  - `Autoplay`
  - `Autoplay Speed`
  - `Pause on Hover`
  - `Drag Free`
  - `Touch Drag`
  - `Slide Alignment`
  - `Responsive Breakpoints`
  - breakpoint px
  - slides to show
  - slides to scroll
  - show arrows
  - show dots
  - start offset left
  - center mode
  - frame ratio
  - frame fit
  - classic photo size
  - variable width
  - slide width
    - image height mode
    - image height
    - image width

#### Breakpoint Layout Contract
The breakpoint repeater now supports three different width-authority modes:

- `Free / Existing Controls`
  - keeps the legacy width contract
  - width is driven by `slides to show`, `slide width`, or `variable width`
- fixed ratio modes (`3:2`, `4:3`, `1:1`, `16:9`)
  - lock the card/media frame with CSS `aspect-ratio`
  - `frame fit` becomes the image-fit authority (`cover` or `contain`)
  - legacy image height/width controls are hidden to avoid conflicting states
- `Classic Photo (3:2)`
  - exposes curated width presets through `Classic Photo Size`
  - hides `slides to show`, `slide width`, and `variable width`
  - is intended for “peek” compositions where the next card should remain partially visible

#### Breakpoint Width Contract
`Image Width` is not a universal “media only” control. Its effect changes based on the breakpoint configuration:

- when `Variable Width = Yes`
- and `Image Height Mode = contain` or `cover`
- and `Image Width` uses `%`

the percentage is applied to the whole slide/card width, not only to the inner image.

In all other combinations:
- pixel-based values remain a concrete image-width control
- percentage values should not be assumed to resize the slide unless the contract above is met

#### Classic Photo Preset Contract
When `Frame Ratio = Classic Photo (3:2)`:

- `Classic Photo Size = Balanced`
  - uses a moderate slide width intended for a clean 3:2 card with a subtle next-card reveal
- `Classic Photo Size = Large`
  - increases slide width while preserving the same 3:2 frame
- `Classic Photo Size = XL Peek`
  - pushes the slide wider so the following card reads as an editorial partial card at the edge of the viewport

These presets change slide width only. They do not alter the 3:2 ratio.

#### Start Offset Contract
`Start Offset Left` is a per-breakpoint carousel viewport offset.

- it adds left breathing room before the first visible slide
- it does not change the card ratio
- it applies regardless of `frame ratio`, `variable width`, or legacy width controls
- it is implemented at the viewport layer rather than as image/card margin, so Embla snapping remains coherent

### Style
- `Images`
  - border radius
  - spacing between slides
  - image size
- `Navigation Arrows`
  - color
  - size
  - padding
  - vertical offset
  - horizontal offset
  - gap
- `Dots (Pagination)`
  - color
  - active color
  - size
  - position
  - vertical offset
- `Custom Cursor`
  - enable on/off

### Popup
Not part of this widget. `Popup Settings` do not exist.

## Style Direction

### Images
The widget reuses the current Embla slider image-height contract:
- `auto`
- `fixed`
- `contain`
- `cover`

Responsive image-height changes are managed in JavaScript based on the breakpoint repeater settings.

When a fixed `frame ratio` is enabled, the ratio becomes the primary card-shape authority and the widget stops using the legacy image-height/image-width contract for that breakpoint.

### Navigation Arrows
Arrows follow the same lightweight Embla-family pattern already used in `BW Product Slider` / `BW Presentation Slide`.

### Dots
Dots are generated by `BWEmblaCore` and skinned locally by the widget.

### Custom Colors
No separate widget-level custom color system exists. Showcase content color is driven by metabox `Texts color`.

## CTA Contract
The CTA is not a standard single-pill button.

Current structure:
- one pill for the CTA text
- one detached green circular arrow button beside it
- both visually read as a single CTA system
- both remain separate elements in markup/design logic

This layout is a visual contract and should be treated as high fidelity.

### Mobile CTA Contract
Below `800px` viewport width:
- the green CTA controls are hidden
- the slide itself becomes the tappable CTA surface
- the destination uses the same URL as the CTA button
- tap navigation is protected by anti-drag logic so Embla swipe interactions are preserved

## Relationship To Existing Widgets

### `BW Presentation Slide`
- reference for slider settings
- reference for responsive breakpoints
- reference for Embla-family runtime behavior and custom-cursor pattern
- not a direct one-to-one clone because popup and gallery-specific logic are out of scope

### `BW Product Slider`
- reference for a thinner Embla widget adapter approach

### `BW Static Showcase`
- reference for showcase metabox field usage and showcase content structure

## Runtime Notes
- Asset registration is centralized in `blackwork-core-plugin.php`.
- The widget title is `BW-UI Showcase Slide`, so it participates in the Elementor panel black BW-UI family styling.
- The widget does not introduce popup, AJAX, or review dependencies.
- The widget uses `BWEmblaCore` and does not own a popup runtime.
- First render stability is partially handled server-side:
  - breakpoint CSS is emitted from PHP before JS boot
  - initial image height mode / image height / image width are seeded into CSS custom properties when legacy image controls are active
  - fixed frame ratio and start-offset rules are emitted in breakpoint CSS so Elementor/frontend first paint matches the configured layout more closely
- The custom glass cursor is stateful:
  - side slides show left/right navigation arrows
  - the active center slide shows a neutral dot cursor, not a navigation arrow
  - cursor direction is recalculated from live card position vs viewport center, not only from the slide index
