# Big Text Widget

## Status
- Status: Implemented
- Visible editor title: `BW-UI Big Text`
- Runtime slug: `bw-big-text`
- Class: `BW_Big_Text_Widget`
- Main class file: `includes/widgets/class-bw-big-text-widget.php`
- Runtime assets:
  - `assets/css/bw-big-text.css`

## Purpose
`Big Text` is a premium editorial display-text widget for large statements and brand paragraphs that need to stay visually packed and well-composed across desktop, tablet, and mobile.

The widget is designed for:
- large editorial paragraphs / statements
- controlled line length
- compact, elegant line wrapping
- responsive typography that scales without collapsing into generic body text

## Content Strategy
### Why the widget does not use a full WYSIWYG surface
The widget intentionally uses a constrained textarea content surface instead of an open WYSIWYG editor.

Reasoning:
- this widget is not a free-form rich-text block
- the composition is the feature, so the markup surface must stay narrow
- unrestricted paragraph wrappers and editor-generated HTML would make line composition less predictable
- a limited inline-HTML allowlist is enough for emphasis/link use cases

Current content allowlist:
- `strong`
- `b`
- `em`
- `i`
- `a`
- `br`

Sanitization contract:
- content is sanitized with widget-local `wp_kses()`
- unsupported HTML is stripped
- auto modes normalize authored newlines to spaces before sanitization
- manual editorial mode treats each non-empty newline as a line group and sanitizes each line separately

## Composition Model
### 1) Auto Balance
Use this mode when the author wants the browser to do the best possible automatic wrapping.

Current strategy:
- controlled `max-inline-size`
- `text-wrap: balance` where supported
- fallback to `text-wrap: pretty`

Best use case:
- short-to-medium editorial statements that usually resolve to a limited number of lines

Important limitation:
- `text-wrap: balance` is excellent for headline-like blocks, but practical browser support is intentionally bounded to a limited line count
- it improves composition, but it does not guarantee identical line breaks across every viewport

### 2) Controlled Width
Use this mode when line length is the primary composition control.

Current strategy:
- `max-inline-size` is the main authority
- wrapping remains automatic
- `text-wrap: pretty` is preferred over plain default wrapping when available

Best use case:
- statements where the right width is more important than forcing exact editorial grouping

### 3) Editorial Lines
Use this mode when composition matters more than fully automatic reflow.

Current strategy:
- each non-empty newline in the textarea becomes a dedicated line group
- each line group renders as its own block
- each block uses `inline-size: fit-content` with `max-inline-size: 100%`
- line groups still fail soft on small screens by wrapping inside the line block if needed

Best use case:
- homepage/editorial statements where the line grouping is part of the visual direction

## Responsive Typography Strategy
### Fluid size authority
The widget supports:
- `Fixed`
- `Fluid`

Current recommendation:
- prefer `Fluid` for large editorial statements
- keep the type bounded by explicit min/max sizes
- define the fluid range across a minimum and maximum viewport width

Implementation contract:
- PHP computes a deterministic `clamp(...)` expression from the saved control values
- widget CSS applies that expression only when `Font Size Mode = Fluid`
- if `Fixed` is selected, the widget falls back to the Elementor typography control + widget defaults

Why this is the preferred approach:
- avoids abrupt breakpoint-only jumps
- preserves readable scaling across in-between viewport widths
- keeps the size bounded and deterministic

## Width and Wrapping Strategy
### `max-inline-size`
`max-inline-size` is the main width control surface because it maps to the reading measure directly and remains writing-mode-safe.

Recommended units:
- `ch` for composition-driven editorial width
- `rem` when width should track the typographic system
- `%` / `vw` / `px` only when layout context requires them

Current default:
- `24ch`

### `text-wrap: balance`
Current guidance:
- good fit for the widget
- not sufficient as the only strategy
- best used together with a bounded line length

This widget therefore treats `balance` as:
- a strong automatic enhancement
- not as an exact-composition guarantee

### Pure CSS vs hybrid editorial control
Pure CSS is good enough for:
- many headline-like or short editorial blocks
- width-led composition with fluid scaling

Pure CSS is not enough for:
- exact authorial line grouping across every viewport
- hard guarantees for long multi-line statements

That is why `Editorial Lines` exists:
- it gives a real-world hybrid solution
- it keeps the authoring surface simple
- it avoids a JS line-measurement engine

## Controls
### Content
- `Text`
  - textarea
  - limited inline HTML allowed
- `Composition Mode`
  - `Auto Balance`
  - `Controlled Width`
  - `Editorial Lines`
- `Max Text Width`
  - responsive
  - `ch / rem / % / vw / px`
- `Alignment`
  - responsive
  - left / center / right

### Style > Typography
- `Font Size Mode`
  - `Fluid`
  - `Fixed`
- `Fluid Min Font Size`
- `Fluid Max Font Size`
- `Fluid Min Viewport`
- `Fluid Max Viewport`
- Elementor typography group control
- `Text Color`
- `Line Height`
- `Letter Spacing`

### Style > Layout
- `Section Padding`
- `Editorial Line Gap`

## Visual Behavior
### Desktop
- large editorial type
- packed line-height
- tight letter-spacing
- width constrained enough to preserve composition

### Tablet / Mobile
- fluid type remains bounded
- line length remains controlled via `max-inline-size`
- manual editorial lines stay compact but can still wrap safely if needed

## Limitations and Tradeoffs
- `text-wrap: balance` improves composition but does not guarantee exact identical line breaks across all viewport widths
- very long manual line groups can still wrap on smaller screens; this is intentional fail-soft behavior
- the widget does not implement JavaScript text measurement or per-device manual line authoring
- the content surface is intentionally narrow; it is not a substitute for a general rich text editor

## Asset Registration
Asset registration authority remains aligned with the widget system:
- helper function in `blackwork-core-plugin.php`
- CSS registered through `bw_register_widget_assets( 'big-text', [], false )`

Current asset handle:
- `bw-big-text-style`

## Regression Checklist Summary
Minimum validation for this widget:
- widget loads in Elementor without errors
- default statement renders correctly
- `Auto Balance`, `Controlled Width`, and `Editorial Lines` all render safely
- responsive width/alignment/padding controls apply correctly
- fluid font size stays bounded between authored minimum and maximum sizes
- inline HTML sanitization strips unsupported tags while preserving allowed emphasis/link markup
