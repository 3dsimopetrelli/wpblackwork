# BW License Table

## Purpose

`BW License Table` is a standalone Elementor widget for reusable licensing tables, feature comparison cards, and permission matrices.

It is designed to work as:
- a single license card
- a duplicated Commercial / Extended / Enterprise / AI license system
- a generic comparison table for future Blackwork licensing surfaces

## Runtime Files

| File | Responsibility |
|------|----------------|
| `includes/widgets/class-bw-license-table-widget.php` | Elementor controls, repeater contract, render markup |
| `assets/css/bw-license-table.css` | card layout, responsive table-to-stack behavior, tooltip presentation |
| `assets/js/bw-elementor-widget-panel.js` | editor panel family-color mapping for the visible title `BW License Table` |

## Widget Contract

- Widget title: `BW License Table`
- Widget slug: `bw-license-table`
- Elementor category: `blackwork`
- Icon: `eicon-table-of-contents`

## Content Controls

### License Header
- License Title
- Short Description

### License Rows
- `Allowed License Rows` repeater
- `Restricted License Rows` repeater
- legacy `License Rows` repeater remains available only for backward compatibility
- each row includes:
  - `Feature Title`
  - `Permission Text`
  - `Example / Explanation`
  - `Tooltip Mode`

Default content ships with:
- an Allowed preset
- a Restricted preset
- legacy widget instances continue rendering their old single repeater as the Allowed section until manually rebuilt

### Section Labels
- `Show Allowed Divider`
- `Divider Text`
- `Show Restricted Divider`
- `Restricted Section Title`

### Footer CTA
- `Show Footer CTA`
- `Footer Text`
- `Footer Link URL`
- `Open In New Tab`

## Layout Contract

### Desktop
- single card/table surface
- header block at top
- allowed section and restricted section render in sequence
- optional footer CTA renders below all license rows
- rows render in a 3-column grid
- default balance:
  - feature: ~30%
  - permission: ~20%
  - explanation: ~50%

### Mobile
- each row remains a compact multi-column row
- repeated per-row labels are not shown
- no horizontal overflow

## Tooltip System

- Tooltip mode is controlled per repeater row
- Tooltip mode `OFF`:
  - explanation renders directly in the third column
- Tooltip mode `ON`:
  - desktop/tablet: explanation is shown in a scoped tooltip triggered by hover/focus on a small `?` button
  - mobile: explanation stays hidden until the same `?` trigger is tapped
  - outside tap and `Escape` close the active tooltip
  - only one tooltip is open at a time on the page

Architecture choice:
- lightweight widget-scoped JS is used only for tooltip positioning and tap-state handling
- no global tooltip framework was introduced
- cold-load safety still comes from hidden-by-default markup + CSS, not from JS

## Style Controls

### Wrapper / Card
- background
- border
- border radius
- padding
- box shadow
- row gap

### Header
- title typography / color
- description typography / color
- header spacing
- title padding
- description padding

### Divider
- divider typography / color
- divider alignment
- divider margin / padding
- optional top / bottom lines
- restricted title top spacing

### Feature Column
- typography
- text color
- background
- padding
- radius

### Permission Column
- typography
- text color
- background
- padding
- radius

### Example / Tooltip Column
- typography
- text color
- background
- padding
- radius
- tooltip background
- tooltip text color
- tooltip radius
- tooltip width

### Rows
- row background
- optional alternate row background
- border
- radius
- padding
- internal column gap

### Footer CTA
- typography
- text color
- hover color
- alignment
- top padding
- bottom padding
- divider color
- divider thickness

## Architecture Notes

- follows the standard BW widget loader path:
  - `includes/widgets/class-bw-*-widget.php`
- uses the generic widget asset helper:
  - `bw_register_widget_assets( 'license-table', [], false )`
- no new global helper layer was introduced
- no new global tooltip framework was introduced
- all runtime styling is widget-scoped under `.bw-license-table-widget`
- dynamic style controls use Elementor selectors and CSS-variable output, matching the current BW widget direction
- footer CTA follows the same widget-scoped CSS-variable pattern as the header/divider/row systems

## Extension Points

Future extensions can stay inside the existing structure by:
- adding repeater fields
- adding optional permission-state badges or colors
- adding richer per-row metadata
- adding alternate row presets
- adding optional heading/section rows inside the same repeater contract

No extra architecture is required for those extensions.
