# Psychadelic Banner Widget

## Purpose
`bw-psychadelic-banner` is a decorative editorial banner widget for high-impact branded sections.

It renders looping pill labels on a dark background, with an optional central PNG artwork layer that can be aligned left, center, or right per breakpoint.

## Runtime Authority
Widget file:
- `includes/widgets/class-bw-psychadelic-banner-widget.php`

Frontend assets:
- `assets/css/bw-psychadelic-banner.css`

Asset registration:
- `blackwork-core-plugin.php`

## Widget Contract
Widget slug:

```text
bw-psychadelic-banner
```

Editor title:

```text
BW-UI Psychadelic Banner
```

Category:

```text
blackwork
```

## Editor Controls

### Content
- `Labels List`
  - textarea
  - one non-empty line = one label
  - labels are deduplicated while preserving order
- `Center PNG`
  - optional artwork image
- `Center Image Position`
  - responsive
  - `Left | Center | Right`
- `Center Image Width`
  - responsive
  - supports `px | % | vw`
- `Banner Height`
  - responsive
  - supports `px | vh | %`
- `Animation`
  - `On | Off`

### Style
- `Background Color`
- `Label Text Color`
- `Label Border Color`
- `Label Background Color`
- `Labels Typography`
  - typography authority for family / weight / transform / tracking
  - font size is intentionally excluded from this group
- `Label Font Width (vw)`
- `Label Font Height (vh)`
- `Label Font Min`
- `Label Font Max`
- `Label Padding`
- `Label Radius`
- `Rows Gap`
- `Labels Gap`

## Rendering Behavior
- the widget builds 5 horizontal rows
- each row uses the same label list with a rotated starting point
- when `Animation = On`:
  - each row renders a duplicated label group
  - odd/even rows move in opposite directions for continuous marquee motion
- when `Animation = Off`:
  - rows stop animating
  - labels wrap and remain statically visible
- the central PNG is rendered in an absolute overlay layer above the moving labels
- the central image does not capture pointer events

## Typography Sizing Contract
Label font size is controlled through a viewport-driven clamp formula owned by the widget CSS:

```text
clamp(min, vw + vh, max)
```

This keeps the label scale responsive to both width and height of the viewport while still exposing lower and upper bounds.

Implementation note:
- the dedicated `vw/vh/min/max` controls are the authoritative font-size surface
- the Typography group intentionally does not expose font size

## Runtime Notes
- current implementation is CSS-only
- no widget JS runtime is required
- the loop effect depends on CSS transforms and duplicated label groups, not on Swiper/Embla or requestAnimationFrame

## Current Limitations
- label rows are presentation-only and not interactive filter controls
- animation speed is currently fixed in CSS and not yet exposed as an editor control
- the widget assumes short-to-medium label lengths; extremely long labels can visually dominate a row

## Related Documentation
- `docs/30-features/elementor-widgets/widget-inventory.md`
