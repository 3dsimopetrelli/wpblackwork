# BW-TASK-20260418 — Mobile Filter Drawer Restoration (Closure)

## 1) Task Identification
- Task ID: `BW-TASK-20260418-MOBILE-FILTER-RESTORATION`
- Task title: Responsive Product Grid mobile drawer restoration and accordion hardening
- Domain: Product Grid / Responsive Filter UX / Accordion / Year filter
- Tier classification: 2
- Closure date: `2026-04-18`
- Final task status: `CLOSED`

## 2) Completed Outcome

The responsive Product Grid mobile filter menu has been documented as a
restored, production-grade control surface.

Completed documentation outcome:

- the mobile drawer shell is recorded as the primary responsive filter UI
- the accordion implementation is recorded as measured-height and
  transitionend-driven
- the search visibility rule is recorded as count-based in drawer mode
- the canonical year data source is recorded as `_bw_filter_year_int`
- the `Clear all` action is recorded as state-aware and fade-hidden by default
- the active chips / footer CTA spacing has been captured as part of the
  drawer surface contract

## 3) Files Updated

- `docs/30-features/product-grid/fixes/2026-04-18-mobile-filter-drawer-restoration.md`
- `docs/30-features/product-grid/README.md`
- `docs/30-features/product-grid/fixes/README.md`
- `docs/tasks/BW-TASK-20260418-mobile-filter-drawer-restoration-start.md`
- `docs/tasks/BW-TASK-20260418-mobile-filter-drawer-restoration-closure.md`

## 4) Final Documentation Contract

The Product Grid mobile drawer is now documented with the following contract:

- drawer header:
  - handle
  - title
  - header `Clear all`
- drawer body:
  - active chips
  - accordion groups
  - group search where applicable
- drawer footer:
  - full-width `Show results`

Runtime behaviors recorded in the docs:

- `Clear all` is only visible when a real filter state exists
- year selection is sourced from canonical meta indexing
- search field display in drawer mode depends on option count
- the accordion uses a stable inner wrapper and real-height animation

## 5) Commit Traceability

Recent push / hardening chain reflected in the current drawer behavior:

| Commit | Message | Contribution |
|--------|---------|--------------|
| `51c11f79` | potenziamento accordion 2 | early accordion strengthening |
| `a2ac1465` | Rewrite accordion with JS-driven measured height animation | core animation model change |
| `531f7c56` | Merge main: resolve accordion conflicts, adopt syncDiscoveryAccordionPanelState | state synchronization cleanup |
| `8ab87eab` | Move Clear all to drawer header, Show results full width | drawer footer/header layout anchor |
| `e8f48c14` | Hide Clear all button by default until JS confirms active filters | state-aware clear-all visibility |
| `c256d377` | Remove contain: layout from accordion toggle - caused row compression | layout stability correction |
| `faa282e7` | Fix 5px toggle text shift on Style/Subject accordion open | toggle polish |
| `9b8456d2` | Fix accordion title text shift on mobile tap | touch stability |
| `1a31fa28` | Fix touch flash on mobile accordion options | mobile interaction polish |
| `7cff9f50` | Fix accordion shift: add align-content: start to drawer content shell | layout alignment hardening |
| `e3c97171` | Fix accordion toggle scroll shift: lock drawer scrollTop on pointerdown | scroll-shift mitigation |
| `df238179` | Restore single-accordion behavior; increase toggle font to 16px | final single-open behavior direction |

## 6) Validation Summary

Documentation-only closure.

Manually verified behaviors that should remain true in the live runtime:

- accordion sections switch without obvious jumpiness
- `Clear all` fades away when no filters remain
- `Clear all` appears after any checkbox or year filter is activated
- search appears only when drawer option counts warrant it
- the year slider range aligns with the canonical index bounds

## 7) Residual Risks

- Very large taxonomy branches can still make the first uncached option build
  expensive.
- The drawer still renders a real DOM button for each visible option, so
  extreme counts should be monitored.
- Tags under broad category branches remain the most likely performance hot
  spot.

## 8) Closure Declaration

- Task closure status: `CLOSED`
- Date: `2026-04-18`
- No runtime code was modified in this documentation closure pass
