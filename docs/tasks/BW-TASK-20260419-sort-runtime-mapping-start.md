# Blackwork Governance — Task Start

## 1) Task Identification
- Task ID: `BW-TASK-20260419-SORT-RUNTIME-MAPPING`
- Task title: Product Grid responsive runtime sort canonicalization and trigger parity
- Domain: Product Grid / Responsive Discovery / Runtime Sort
- Tier classification: 2
- Request source: Radar analysis + user-driven responsive sort refinement

## 2) Context

This task records the final state of the Product Grid runtime sort wave after
the Radar feedback cycle. The sort system now has a canonical key set, exact
Lucide icon parity, and a responsive trigger split between desktop and mobile.

The task is documentation-oriented and exists to close the loop around the
sort implementation, not to redesign the runtime architecture.

## 3) Expected Outcome

The documentation set should record:

- canonical sort keys and alias compatibility
- `newest` as the runtime default
- backend-authoritative sort resolution
- request / cache / planner normalization consistency
- exact Lucide icon mapping for the runtime sort trigger
- desktop label + chevron behavior
- mobile icon-only trigger behavior
- search-surface parity for the shared sort UI

## 4) Runtime Surfaces In Scope

- `includes/modules/search-engine/engine/sort-config.php`
- `includes/modules/search-engine/request/request-normalizer.php`
- `includes/modules/search-engine/engine/query-planner.php`
- `includes/modules/search-engine/cache/cache-service.php`
- `includes/widgets/class-bw-product-grid-widget.php`
- `includes/modules/search-surface/runtime/headless-product-grid-renderer.php`
- `assets/js/bw-product-grid.js`
- `assets/css/bw-product-grid.css`

## 5) Documentation Surfaces In Scope

- `docs/30-features/product-grid/README.md`
- `docs/30-features/product-grid/product-grid-architecture.md`
- `docs/30-features/product-grid/fixes/README.md`
- `docs/30-features/product-grid/fixes/2026-04-19-responsive-sort-mapping-and-trigger-refinement.md`
- `docs/tasks/BW-TASK-20260419-sort-runtime-mapping-start.md`
- `docs/tasks/BW-TASK-20260419-sort-runtime-mapping-closure.md`

## 6) Acceptance Criteria

- Canonical runtime sort behavior is documented as `newest`-first.
- Alias compatibility is documented for `random_seeded`.
- Icon mapping is documented with exact Lucide SVG parity.
- Desktop/mobile trigger split is documented clearly.
- Search-surface parity is documented.
- The final closure note records the runtime behavior and the documentation-only closeout.

## 7) Notes for Future Review

- This task is documentation-first.
- No runtime code changes are expected from this record alone.
- Future sort changes must update the widget, JS runtime, backend planner,
  cache key handling, and responsive CSS together.
