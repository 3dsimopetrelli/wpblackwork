# BW-TASK-20260418 — Mobile Filter Drawer Restoration (Start)

## 1) Task Identification
- Task ID: `BW-TASK-20260418-MOBILE-FILTER-RESTORATION`
- Task title: Responsive Product Grid mobile drawer restoration and accordion hardening
- Domain: Product Grid / Responsive Filter UX / Accordion / Year filter
- Tier classification: 2
- Request source: User-driven restoration and documentation pass on 2026-04-18

## 2) Context

The responsive Product Grid filter menu was progressively restored and refined
through a sequence of review passes and push cycles. The current task records
that work as a governed documentation artifact and keeps the scope locked to
the mobile filter drawer, its accordion groups, and the related filter
surface behaviors.

This task exists to document the final runtime shape of the drawer and the
implementation decisions that were made while restoring it.

## 3) Expected Outcome

The documentation set should record:

- the mobile drawer shell and footer contract
- the accordion interaction model
- the search visibility threshold in drawer mode
- the canonical source of the Year filter
- the visibility rule for `Clear all`
- the active chips presentation model
- the main performance and scalability watchpoints

## 4) Runtime Surfaces In Scope

- `assets/js/bw-product-grid.js`
- `assets/css/bw-product-grid.css`
- `includes/widgets/class-bw-product-grid-widget.php`
- `includes/modules/search-engine/index/year-index-service.php`
- `includes/modules/search-engine/engine/advanced-filter-engine.php`
- `includes/modules/search-surface/adapters/ajax-search-surface.php`
- `includes/modules/search-surface/runtime/headless-product-grid-renderer.php`

## 5) Documentation Surfaces In Scope

- `docs/30-features/product-grid/README.md`
- `docs/30-features/product-grid/fixes/README.md`
- `docs/30-features/product-grid/fixes/2026-04-18-mobile-filter-drawer-restoration.md`
- `docs/tasks/BW-TASK-20260418-mobile-filter-drawer-restoration-start.md`
- `docs/tasks/BW-TASK-20260418-mobile-filter-drawer-restoration-closure.md`

## 6) Acceptance Criteria

- Mobile drawer behavior is documented as a stable, production-style surface.
- Accordion behavior is documented as measured-height, synchronized, and
  visually fluid.
- Search visibility rule is documented as count-based in drawer mode.
- Year filter source is documented as canonical meta-driven indexing.
- `Clear all` visibility is documented as state-driven and fade-based.
- The performance risks for large taxonomies are explicitly called out.

## 7) Notes for Future Review

- This task is documentation-first.
- No runtime code changes are expected from this record alone.
- Follow-up work, if any, should focus on large-branch performance profiling,
  not on reintroducing the old accordion behavior.
