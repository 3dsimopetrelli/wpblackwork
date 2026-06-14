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
- larger textarea field for easier multiline editing in Elementor
- default: `Accordion Title`
- title tag control: `Title HTML Tag`
- default tag: `h3`
- allowed tags: `h1`, `h2`, `h3`, `h4`, `h5`, `h6`, `div`, `span`

### Accordion Content
- WYSIWYG field
- supports normal rich text and safe HTML output
- editor content is rendered as a single accordion panel, not as a nested widget container

### Initial State
- `Closed by Default`
- `Open by Default`

### Icon Type
- default: `Plus / X`
- options: `Plus / X`, `Arrow`, `Custom SVG`
- `Custom SVG` accepts sanitized inline SVG markup
- custom SVG supports the same color and size controls, and rotates on toggle instead of morphing

## Style Controls

### Title
- Typography
- Closed State: Closed Title Color, Closed Background Color
- Open State: Open Title Color, Open Background Color
- Padding

### Content
- Typography
- Text Color
- Padding

### Divider
- Divider Color
- Divider Thickness
- Divider Spacing

### Icon
- Icon Color
- Icon Size
- Title / Icon Gap
- Icon Stroke Weight

## Behavior Notes

- Clicking the title row toggles the accordion open/closed
- the default icon morphs from a plus to an X with a soft rotation transition
- the arrow option rotates smoothly with state changes
- one real divider sits directly under the closed header and is physically pushed downward by the expanding panel when opened
- the panel height expands smoothly while content fades in and slides down slightly
- long titles wrap before the icon column and the title/icon spacing can be adjusted responsively
- closing reverses the same motion without a hard jump
- multiple accordion widgets on the same page work independently
- the widget is initialized on both frontend and Elementor editor preview
- the header uses a real button with `aria-expanded` and `aria-controls`

## QA Notes

- verify the widget appears under the `blackwork` category in Elementor
- confirm the content renders correctly with paragraphs, links, bold, and simple HTML
- confirm the title field is easier to edit as a textarea
- confirm the title HTML tag control renders `h1` through `h6`, `div`, and `span`
- confirm the default icon is `Plus / X`
- confirm the `Arrow` option still rotates correctly
- confirm sanitized `Custom SVG` renders without breaking the editor
- confirm the divider does not fade-swap and instead visibly travels down/up with the animated panel
- confirm closed/open title colors and header backgrounds can be controlled independently
- confirm the default state control works in both directions
- confirm keyboard activation works on the header button
- confirm no console errors appear in frontend or editor preview

## Limitations

- this first version supports WYSIWYG content only
- it does not yet support nested Elementor widgets or drag-and-drop inner sections
- divider movement is implemented as a clean visual transition between the collapsed and expanded states, not as a complex reflowing geometry effect
- the title is semantic and configurable, but the clickable header remains a button for accessibility

## Files Changed for Initial Release

- `includes/widgets/class-bw-accordion-widget.php`
- `assets/css/bw-accordion.css`
- `assets/js/bw-accordion.js`
- `docs/30-features/elementor-widgets/widget-inventory.md`
- `docs/30-features/elementor-widgets/README.md`
