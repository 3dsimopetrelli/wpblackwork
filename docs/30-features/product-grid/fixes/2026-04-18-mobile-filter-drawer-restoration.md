# Product Grid — Mobile Filter Drawer Restoration and Accordion Hardening

**Date**: 2026-04-18  
**Status**: documented, audited, ready for follow-up closure review

---

## Scope

This report records the restoration and stabilization pass for the responsive
Product Grid filter menu, with primary focus on the mobile drawer, accordion
behavior, year filter source, active chips, and the visibility model for
`Clear all`.

The work reflects the current runtime contract in:

- `assets/js/bw-product-grid.js`
- `assets/css/bw-product-grid.css`
- `includes/widgets/class-bw-product-grid-widget.php`
- `includes/modules/search-engine/index/year-index-service.php`
- `includes/modules/search-engine/engine/advanced-filter-engine.php`
- `includes/modules/search-surface/adapters/ajax-search-surface.php`
- `includes/modules/search-surface/runtime/headless-product-grid-renderer.php`

---

## What Was Restored

### Mobile drawer structure

The responsive filter drawer now behaves as a dedicated mobile control surface
with:

- a stable drawer shell
- a compact header with handle and title
- a `Clear all` action in the drawer header
- a body shell that hosts:
  - active filter chips
  - accordion groups
- a full-width footer CTA for `Show results`

### Accordion interaction

The accordion implementation was hardened to feel like a production mobile UI:

- a single accordion section is intended to be open at a time
- measured-height animation is used instead of a brittle fixed-height guess
- the panel uses `transitionend` to settle open/close state
- the container and inner wrapper are kept separate so the content does not
  visually reshape while the panel animates
- open/close behavior remains synchronous when switching groups

### Search visibility

The drawer search field is not rendered unconditionally.

Current drawer rule:

- render search only when the group has more than five options

Current visible-surface rule:

- render search on the visible filter surface regardless of option count

This explains the current difference between:

- `Categories` with a small number of entries, where search is hidden
- `Style / Subject` with a larger option set, where search is shown

### Year filter

The Year slider is not tag-driven.

It is sourced from the canonical product meta key:

- `_bw_filter_year_int`

The runtime builds:

- `min_year`
- `max_year`
- `quick_ranges`

from the actual values present in the indexed product set for the current
context.

### Clear-all visibility

`Clear all` in the mobile drawer is now treated as state-aware UI rather than a
permanently visible control:

- hidden by default
- shown only when at least one filter is active
- fades out when no filter state remains

Filters considered active:

- category/subcategory selections
- style/subject selections
- advanced token groups
- year range selections

Search text alone is not treated as a filter activation signal for this
visibility rule.

### Chips and footer actions

The active chip strip and footer actions were normalized to the current mobile
presentation language:

- compact chip padding
- darker chip background
- compact remove button spacing
- clearer separation from the accordion list
- reduced visual competition between `Clear all` and `Show results`

---

## Technical Notes

### Data sources

- Categories and subcategories are derived from taxonomy data.
- Tags are derived from filtered post IDs and taxonomy aggregation.
- Advanced groups are powered by the advanced-filter index.
- Years are powered by the year index service and the canonical year meta.

### Performance model

The implementation uses several performance guards:

- AJAX request aborts for stale subcategory/tag refreshes
- cache-backed year and advanced-filter indexes
- drawer rendering that depends on the current visible state rather than a
  second filtering engine

This is a solid base for a catalog of several thousand products, but the
largest cost center remains the tag/group aggregation path when the selected
category branch is very broad.

### Accordion stability

The current approach is significantly better than a raw `max-height` accordion
because it:

- measures real content height
- isolates content inside a stable inner wrapper
- avoids padding shifts as the panel opens/closes
- avoids the old "jump" feeling when groups switch

---

## Commit Traceability

Recent commits in the restoration chain:

| Commit | Message | Notes |
|--------|---------|-------|
| `93f4b559` | potenziamento accordion | early accordion strengthening pass |
| `51c11f79` | potenziamento accordion 2 | accordion refinement pass |
| `a2ac1465` | Rewrite accordion with JS-driven measured height animation | key transition to measured-height animation |
| `531f7c56` | Merge main: resolve accordion conflicts, adopt syncDiscoveryAccordionPanelState | accordion state sync consolidation |
| `1a31fa28` | Fix touch flash on mobile accordion options | mobile touch polish |
| `9b8456d2` | Fix accordion title text shift on mobile tap | text-shift hardening |
| `faa282e7` | Fix 5px toggle text shift on Style/Subject accordion open | tighter toggle behavior |
| `c256d377` | Remove contain: layout from accordion toggle - caused row compression | layout constraint removal |
| `8ab87eab` | Move Clear all to drawer header, Show results full width | drawer action repositioning |
| `e8f48c14` | Hide Clear all button by default until JS confirms active filters | state-aware clear-all visibility |
| `6a3c7fa6` | Allow multiple accordions open simultaneously to prevent toggle shift | intermediate accordion experiment |
| `e3c97171` | Fix accordion toggle scroll shift: lock drawer scrollTop on pointerdown | scroll-shift mitigation |
| `7cff9f50` | Fix accordion shift: add align-content: start to drawer content shell | shell alignment correction |
| `df238179` | Restore single-accordion behavior; increase toggle font to 16px | current UX direction anchor |

---

## Validation Summary

Documentation-only report.

Observed runtime behaviors to verify manually in the browser:

- drawer opens and closes without obvious layout jumps
- accordion sections switch cleanly
- `Clear all` fades in only after filter activation
- `Clear all` disappears after reset
- search appears only for sufficiently large option groups in the drawer
- year slider bounds match the canonical year index

---

## Residual Watchpoints

- very large taxonomy branches can still make category/tag option rendering
  expensive
- the drawer has no virtualization layer, so extremely large option sets should
  still be monitored
- broad category changes can trigger expensive tag re-aggregation on the first
  uncached pass

---

## Final Verdict

The mobile filter drawer is now documented as a coherent responsive filter
surface rather than a collection of isolated controls.

The current implementation is suitable for production use, with the main
remaining concern being broad-branch performance under very large content
sets.
