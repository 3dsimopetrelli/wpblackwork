# Blackwork Governance -- Task Start

**Task ID:** `BW-TASK-20260611-PRESENTATION-SLIDER-FIRST-IMAGE-VISIBILITY`
**Title:** Audit BW-UI Presentation Slider first image visibility on single product templates
**Status:** `OPEN`

## Scope
- audit the frontend single-product Presentation Slider when the first/main image is missing on initial load at a specific viewport width
- inspect the widget PHP, CSS, JS, and shared Embla core files
- determine whether the issue is image loading, Embla init/reInit timing, CSS sizing, or template visibility/layout stability
- do not implement a fix yet

## Notes
- confirm how the widget behaves during initial load versus resize and after images finish loading
- focus on the horizontal single-product template path first, but note any vertical/popup/shared-side effects if relevant
