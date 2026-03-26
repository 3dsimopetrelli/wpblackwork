# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260325-04`
- Task title: Governed `Showcase Slide` breakpoint layout refinement
- Request source: User request on 2026-03-25
- Expected outcome:
  - inspect the current `Showcase Slide` widget controls/runtime and related documentation before changes
  - evaluate whether the existing breakpoint/image controls can already produce a fixed proportional showcase card
  - add a cleaner fixed-ratio control surface for the slide frame
  - support curated `Classic Photo (3:2)` sizing presets for partial next-slide reveal
  - support breakpoint-level left start offset for first-card breathing room
  - keep the new controls separated from legacy width/height controls so the editor surface stays understandable
  - close the intervention with synchronized technical documentation
- Constraints:
  - do not break the existing Embla-based slider contract
  - do not create conflicting simultaneous width authorities in the editor
  - fixed-ratio mode must work across all breakpoint combinations, not only in `Classic Photo`
  - `Start Offset Left` must work regardless of frame ratio mode

## 2) Task Classification
- Domain: Elementor Widgets / Showcase UX / Responsive Layout Controls
- Incident/Task type: Governed feature refinement
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `BW_Showcase_Slide_Widget` Elementor controls/render
  - Showcase Slide Embla runtime behavior
  - Showcase widget feature documentation
- Integration impact: Medium
- Regression scope required:
  - breakpoint control visibility/conditions
  - slide width authority and card ratio behavior
  - viewport start spacing
  - editor/frontend first paint consistency

## 3) Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/showcase-slide-widget.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/README.md`
- Architecture/code references to read:
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `assets/js/bw-showcase-slide.js`
  - `assets/css/bw-showcase-slide.css`
  - `includes/widgets/class-bw-presentation-slide-widget.php`
  - `assets/js/bw-presentation-slide.js`
- Governance docs to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`

## 4) Scope Declaration
- Proposed strategy:
  - add a fixed `Frame Ratio` dropdown inside the breakpoint repeater
  - add a conditional `Frame Fit` control for ratio-locked cards
  - add a conditional `Classic Photo Size` preset surface for `3:2` editorial peek layouts
  - add `Start Offset Left` as a viewport-level per-breakpoint offset
  - hide legacy controls where their authority would conflict with the new ratio-led modes
- Files likely impacted:
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `assets/js/bw-showcase-slide.js`
  - `docs/30-features/elementor-widgets/showcase-slide-widget.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `CHANGELOG.md`
  - `docs/tasks/BW-TASK-20260325-04-start.md`
  - `docs/tasks/BW-TASK-20260325-04-closure.md`
- Explicitly out-of-scope surfaces:
  - popup behavior
  - new asset handles
  - generalized carousel spacing framework outside `Showcase Slide`

## 5) Runtime Surface Declaration
- New hooks expected: none
- Hook priority modifications: none
- AJAX endpoints expected: none
- Admin routes expected: none

## 6) System Invariants
- The showcase metabox remains the content authority.
- The slider remains Embla-based and breakpoint-driven.
- Width/radius/offset behavior must remain deterministic for the same saved settings.
- Fixed-ratio mode must not silently combine with the legacy image-height/image-width contract.

## 7) Testing Strategy
- Verify fixed-ratio card behavior in Elementor/frontend.
- Verify `Classic Photo` presets create a partial next-slide reveal.
- Verify `Start Offset Left` creates initial breathing room without breaking snapping.
- Verify legacy `Free / Existing Controls` mode still works.
- Verify breakpoint control visibility stays coherent when switching modes.
