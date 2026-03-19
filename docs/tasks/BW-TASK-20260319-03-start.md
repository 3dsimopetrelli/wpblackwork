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
  - verify whether the active implementation is Slick-based or Embla-based
  - document the current real feature surface and known architectural constraints
  - update the relevant Markdown files so they match the repository state
- Files likely impacted:
  - `docs/tasks/BW-TASK-20260319-03-start.md`
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
- Explicitly out-of-scope surfaces:
  - runtime rewrites from Slick to Embla
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

## Current audit hypothesis (to verify)
- `bw-presentation-slide` is still Slick-based in the current repository state
- Embla migration may have been discussed or partially developed, but is not yet the live authority in this workspace
