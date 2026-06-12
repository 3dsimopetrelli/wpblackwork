# BW Accordion

## Purpose

`BW Accordion` is a standalone Elementor accordion widget for short editorial blocks, callouts, FAQs, and reusable content folds.

It is intentionally simple:
- one title
- one WYSIWYG content area
- one open/closed state control

It does **not** use nested Elementor inner widgets in this version.

## Runtime Files

| File | Responsibility |
|------|----------------|
| `includes/widgets/class-bw-accordion-widget.php` | Elementor controls, render markup, accessibility attributes |
| `assets/css/bw-accordion.css` | accordion layout, divider placement, arrow rotation, open/close state styling |
| `assets/js/bw-accordion.js` | toggle behavior, measured-height animation, Elementor editor re-init |

## Widget Contract

- Widget title: `BW Accordion`
- Widget slug: `bw-accordion`
- Elementor category: `blackwork`
- Icon: `eicon-accordion`

## Content Controls

### Accordion Title
- text field
- default: `Accordion Title`

### Accordion Content
- WYSIWYG field
- supports normal rich text and safe HTML output
- editor content is rendered as a single accordion panel, not as a nested widget container

### Initial State
- `Closed by Default`
- `Open by Default`

## Style Controls

### Title
- Typography
- Text Color
- Padding

### Content
- Typography
- Text Color
- Padding

### Divider
- Divider Color
- Divider Thickness
- Divider Spacing

### Arrow
- Arrow Color
- Arrow Size
- Arrow Stroke Weight

## Behavior Notes

- Clicking the title row toggles the accordion open/closed
- the arrow rotates smoothly with state changes
- the divider is shown under the title when closed and moves visually to the bottom of the block when open
- multiple accordion widgets on the same page work independently
- the widget is initialized on both frontend and Elementor editor preview
- the header uses a real button with `aria-expanded` and `aria-controls`

## QA Notes

- verify the widget appears under the `blackwork` category in Elementor
- confirm the content renders correctly with paragraphs, links, bold, and simple HTML
- confirm the default state control works in both directions
- confirm keyboard activation works on the header button
- confirm no console errors appear in frontend or editor preview

## Limitations

- this first version supports WYSIWYG content only
- it does not yet support nested Elementor widgets or drag-and-drop inner sections
- divider movement is implemented as a clean visual transition between the collapsed and expanded states, not as a complex reflowing geometry effect

## Files Changed for Initial Release

- `includes/widgets/class-bw-accordion-widget.php`
- `assets/css/bw-accordion.css`
- `assets/js/bw-accordion.js`
- `docs/30-features/elementor-widgets/widget-inventory.md`
- `docs/30-features/elementor-widgets/README.md`
