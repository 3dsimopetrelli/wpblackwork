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
- Elementor repeater
- Each row includes:
  - `Feature Title`
  - `Permission Text`
  - `Example / Explanation`
  - `Tooltip Mode`

Default content ships with the Commercial License preset.

## Layout Contract

### Desktop
- single card/table surface
- header block at top
- rows below in a 3-column grid
- default balance:
  - feature: ~30%
  - permission: ~20%
  - explanation: ~50%

### Mobile
- each row stacks into a compact card
- order:
  - Feature
  - Permission
  - Details
- no horizontal overflow

## Tooltip System

- Tooltip mode is controlled per repeater row
- Tooltip mode `OFF`:
  - explanation renders directly in the third column
- Tooltip mode `ON`:
  - desktop/tablet: explanation is shown in a scoped CSS tooltip triggered by hover/focus on a small info button
  - mobile: explanation automatically falls back to inline rendering

Architecture choice:
- no dedicated widget JS was added
- tooltip interaction is CSS-only to keep runtime light and avoid duplicate bindings when many widgets exist on the same page
- mobile fallback avoids brittle tap-state logic

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

## Architecture Notes

- follows the standard BW widget loader path:
  - `includes/widgets/class-bw-*-widget.php`
- uses the generic widget asset helper:
  - `bw_register_widget_assets( 'license-table', [], false )`
- no new global helper layer was introduced
- no new global tooltip framework was introduced
- all runtime styling is widget-scoped under `.bw-license-table-widget`
- dynamic style controls use Elementor selectors and CSS-variable output, matching the current BW widget direction

## Extension Points

Future extensions can stay inside the existing structure by:
- adding repeater fields
- adding optional permission-state badges or colors
- adding richer per-row metadata
- adding alternate row presets
- adding optional heading/section rows inside the same repeater contract

No extra architecture is required for those extensions.
