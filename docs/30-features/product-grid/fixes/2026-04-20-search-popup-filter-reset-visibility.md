# Product Grid — Search Popup Filter Reset Visibility

**Date**: 2026-04-20  
**Status**: documented, implemented, ready for manual QA

---

## Scope

This report documents the Search Popup filter footer cleanup that makes the
`Reset` action state-aware.

The popup filter footer now keeps the Reset button hidden until the user has
an active filter selection. This keeps the footer aligned with the actual
filter state instead of exposing a dormant reset control.

It reflects the current runtime state in:

- `includes/modules/search-surface/frontend/search-surface.js`
- `includes/modules/search-surface/frontend/search-surface-template.php`
- `includes/modules/search-surface/frontend/search-surface.css`

---

## Final Feature State

### State-aware Reset visibility

The popup filter footer now behaves as a state-driven control surface:

- `Reset` is hidden by default
- `Reset` becomes visible only when at least one filter is active
- active selection is detected from the same filter state used by:
  - filter chips
  - year range selection
  - filter count updates
  - filter URL construction

Filters considered active:

- subcategories
- tags
- year range
- advanced filter groups

### Year parity

Year selection remains part of the same visibility contract:

- selecting a year range activates the filter state
- the year chip can appear in the chip strip
- the Reset button becomes visible as soon as the year state is active

### Reset behavior

The Reset action still clears the full popup filter state:

- subcategories
- tags
- year range
- advanced groups

The footer then returns to its hidden-reset state after the filter panel
rerenders.

---

## Files Updated

- `includes/modules/search-surface/frontend/search-surface.js`

No backend/query code was modified.

---

## Implementation Notes

### Visibility helper

The popup JS now uses a small helper to detect whether the filter state is
actually active.

The helper checks:

- `filterSel.subcategories`
- `filterSel.tags`
- `filterSel.year.from` / `filterSel.year.to`
- `filterSel.advanced`

The Reset button visibility is then synced from that helper whenever the
popup filter view is rendered or updated.

### Safe integration points

The visibility sync is triggered from the existing filter lifecycle:

- filter render
- chip updates
- reset action
- popup binding

This keeps the change local to the popup-native Search Surface layer and
avoids any change to query logic or filter semantics.

---

## Validation Summary

Validation performed on the updated JavaScript file:

- `node --check includes/modules/search-surface/frontend/search-surface.js`

Expected manual QA behavior in the browser:

- open the popup filter panel with no active filters -> `Reset` is hidden
- activate any filter -> `Reset` appears
- clear the active filters -> `Reset` hides again

---

## Residual Risks

- If future filter groups are added, the helper must be updated so their
  selection state participates in the same visibility contract.
- The Reset button remains footer-based; if the footer structure changes in a
  future redesign, the visibility sync should continue to target the same
  popup-native state helper rather than hardcoded DOM assumptions.

---

## Closure Declaration

- Task closure status: `CLOSED`
- Date: `2026-04-20`
- This report documents a popup-native UI-state refinement only

