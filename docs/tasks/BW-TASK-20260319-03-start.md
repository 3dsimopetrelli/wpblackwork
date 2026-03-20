# Blackwork Governance — Task Start Template

## Context
- Task title: Audit current `BW Presentation Slider` runtime and documentation
- Request source: User request on 2026-03-19
- Expected outcome: Audit the real current implementation of `bw-presentation-slide`, including widget PHP, frontend JS/CSS runtime, asset registration, and documentation alignment after recent slider changes.
- Constraints:
  - Audit first; do not assume the latest push landed correctly
  - Update documentation to match the repository state actually present in this workspace
  - Keep code untouched unless a later implementation task explicitly requests runtime changes
  - Treat this as an open task; do not close until audit follow-up is agreed

## Task Classification
- Domain: Elementor Widgets / Slider Runtime / Presentation Slider
- Incident/Task type: Audit + documentation alignment
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `bw-presentation-slide` widget runtime
  - shared slider dependency model
  - widget documentation surfaces
- Integration impact: Medium
- Regression scope required:
  - widget registration
  - slider asset handles
  - frontend initialization contract
  - editor/runtime documentation consistency

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
- Integration docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Architecture references to read:
  - `includes/widgets/class-bw-presentation-slide-widget.php`
  - `assets/js/bw-presentation-slide.js`
  - `assets/css/bw-presentation-slide.css`
  - `blackwork-core-plugin.php`

## Scope Declaration
- Proposed strategy:
  - inspect current widget class, asset registration, and JS runtime
  - verify the actual Embla migration surface and identify any remaining non-Embla subflows
  - document the current real feature surface and known architectural constraints
  - update the relevant Markdown files so they match the repository state
- Files likely impacted:
  - `docs/tasks/BW-TASK-20260319-03-start.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Explicitly out-of-scope surfaces:
  - runtime rewrites beyond documentation alignment
  - popup redesign
  - cursor redesign
  - unrelated slider/widget refactors

## Governance Impact Analysis
- Authority surfaces touched:
  - documentation truth surfaces only in this phase
- Runtime hook/order changes:
  - none in this audit phase
- Requires ADR? No

## Determinism Statement
- Input/output determinism declared? Yes
- Determinism rule:
  - documentation updates must reflect the repository state actually present, not intended future state

## Documentation Update Plan
- `docs/10-architecture/`
  - `docs/10-architecture/elementor-widget-architecture-context.md`
- `docs/30-features/`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`

## Current audit result (verified)
- `bw-presentation-slide` is Embla-based in the current repository state for:
  - horizontal carousel mode
  - responsive vertical main/thumb mode
- desktop vertical mode remains a custom non-Embla elevator layout
- shared Embla asset authority lives in:
  - `assets/js/bw-embla-core.js`
  - `assets/css/bw-embla-core.css`
- the widget still owns substantial widget-local runtime behavior for:
  - popup overlay
  - custom cursor
  - breakpoint/image-height application
  - vertical desktop elevator behavior
- additional verified runtime notes after follow-up implementation:
  - popup open path is guarded by a real `pointerdown -> pointerup` sequence on the same target
  - popup overlay appends to `<body>` and remains widget-local runtime authority
  - popup image height is bounded to the viewport
  - popup style controls were intentionally removed again in favor of fixed CSS defaults
  - horizontal arrows render hidden by default and are shown only after breakpoint runtime confirms `show arrows`
