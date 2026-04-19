# BW-TASK-20260419 — Sort Runtime Mapping and Trigger Parity (Closure)

## 1) Task Identification
- Task ID: `BW-TASK-20260419-SORT-RUNTIME-MAPPING`
- Task title: Product Grid responsive runtime sort canonicalization and trigger parity
- Domain: Product Grid / Responsive Discovery / Runtime Sort
- Tier classification: 2
- Closure date: `2026-04-19`
- Final task status: `CLOSED`

## 2) Completed Outcome

The responsive Product Grid sort system has been documented as a
production-ready runtime control with canonical key resolution and responsive
trigger parity.

Completed documentation outcome:

- canonical runtime sort keys are recorded as the source of truth
- `newest` is recorded as the default sort / `Latest`
- `random_seeded` is recorded as a compatibility alias only
- request normalization, cache keys, and query planning are recorded as aligned
- exact Lucide SVG parity is recorded for the runtime sort icons
- desktop sort trigger behavior is recorded as left label + right chevron
- mobile sort trigger behavior is recorded as icon-only
- search-surface parity is recorded as matching the Product Grid runtime

## 3) Files Updated

- `docs/30-features/product-grid/fixes/2026-04-19-responsive-sort-mapping-and-trigger-refinement.md`
- `docs/30-features/product-grid/README.md`
- `docs/30-features/product-grid/fixes/README.md`
- `docs/30-features/product-grid/product-grid-architecture.md`
- `docs/tasks/BW-TASK-20260419-sort-runtime-mapping-start.md`
- `docs/tasks/BW-TASK-20260419-sort-runtime-mapping-closure.md`

## 4) Final Documentation Contract

The Product Grid runtime sort is now documented with the following contract:

- canonical sort key set:
  - `newest`
  - `oldest`
  - `title_asc`
  - `title_desc`
  - `year_asc`
  - `year_desc`
- compatibility alias:
  - `random_seeded` -> `newest`
- backend resolution remains authoritative
- invalid or unknown values fall back to `newest`
- year sort remains bound to `_bw_filter_year_int`
- non-product year sort falls back to `newest`
- desktop dropdown uses:
  - label on the left
  - Lucide `chevron-down` on the right
- mobile sort trigger uses:
  - icon-only presentation
  - compact responsive sizing

## 5) Commit Traceability

Recent sort hardening commit chain reflected in the runtime behavior:

| Commit | Message | Contribution |
|--------|---------|--------------|
| `f9886774` | `Set newest as default sort; remove random_seeded; harden sort-config` | canonical default sort, alias cleanup, sort-config hardening |
| `52cd092f` | `Task sorting part 1` | initial runtime sort consolidation work |
| `c8a6da3f` | `Update class-bw-product-grid-widget.php` | widget sort surface alignment |
| `2d491b4e` | `Update bw-product-grid.css` | responsive presentation / sizing refinement |
| `f64efa34` | `Update class-bw-product-grid-widget.php` | responsive trigger markup refinement |

## 6) Validation Summary

Documentation-only closure.

Manually verified behaviors that should remain true in the live runtime:

- desktop sort trigger keeps its label/chevron split
- mobile sort trigger remains icon-only
- `newest` is the default visible order state
- `random_seeded` remains accepted only as a compatibility alias
- the sort control still resolves through the backend planner

## 7) Residual Risks

- The sort system still depends on the shared query planner and cache-key
  normalization; future key additions must be mirrored across all surfaces.
- Any icon family change must be mirrored in the widget, headless renderer,
  JS runtime, and responsive CSS.

## 8) Closure Declaration

- Task closure status: `CLOSED`
- Date: `2026-04-19`
- No runtime code was modified in this documentation closure pass
