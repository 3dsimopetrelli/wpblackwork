# Product Grid — Responsive Sort Mapping and Trigger Refinement

**Date**: 2026-04-19  
**Status**: documented, audited, ready for manual QA / closure review

---

## Scope

This report documents the runtime sort cleanup wave for `BW Product Grid`
responsive discovery mode and records the final code state after the Radar
driven sort hardening pass.

It covers:

- canonical sort-key normalization and compatibility aliases
- backend-authoritative sort resolution
- cache-key and request normalization alignment
- responsive trigger UI parity between desktop and mobile
- exact Lucide SVG icon mapping
- search-surface / headless renderer parity

It reflects the current code in:

- `includes/modules/search-engine/engine/sort-config.php`
- `includes/modules/search-engine/request/request-normalizer.php`
- `includes/modules/search-engine/engine/query-planner.php`
- `includes/modules/search-engine/cache/cache-service.php`
- `includes/widgets/class-bw-product-grid-widget.php`
- `includes/modules/search-surface/runtime/headless-product-grid-renderer.php`
- `assets/js/bw-product-grid.js`
- `assets/css/bw-product-grid.css`

---

## Final Feature State

### Canonical runtime sort

The responsive runtime sort now resolves around the canonical sort set:

- `newest`
- `oldest`
- `title_asc`
- `title_desc`
- `year_asc`
- `year_desc`

Legacy compatibility remains available through alias normalization:

- `random_seeded` -> `newest`

The runtime default is `newest` / `Recently added`.

### Labels

Trigger labels are compact:

- `newest` -> `Latest`
- `oldest` -> `Earliest`
- `title_asc` -> `A–Z`
- `title_desc` -> `Z–A`
- `year_asc` -> `Year ↑`
- `year_desc` -> `Year ↓`

Menu labels remain explicit:

- `Recently added`
- `Oldest added`
- `Alphabetical A to Z`
- `Alphabetical Z to A`
- `Year, oldest first`
- `Year, newest first`

### Backend authority

Sort resolution remains backend-authoritative.

The request normalizer, query planner, and cache key builder now agree on the
canonical sort vocabulary and fallback behavior:

- invalid or unknown keys fall back to `newest`
- year sorts still depend on canonical meta `_bw_filter_year_int`
- non-product year sorting falls back to `newest`
- dead `random_seeded` / `default` query planner branches were removed

### Responsive trigger behavior

Desktop and mobile sort trigger behavior is intentionally split:

- desktop dropdown:
  - label aligned left
  - Lucide `chevron-down` aligned right
  - icon shell hidden in desktop dropdown presentation
- mobile:
  - icon-only trigger presentation
  - text label hidden
  - compact square/circle sizing aligned to the responsive toolbar contract

### Exact Lucide icon mapping

The runtime sort icons now use exact Lucide SVGs:

- `arrow-down-up`
- `clock-arrow-down`
- `clock-arrow-up`
- `arrow-down-a-z`
- `arrow-up-z-a`
- `calendar-arrow-up`
- `calendar-arrow-down`
- `chevron-down`

### Search-surface parity

The headless/search-surface renderer mirrors the same shared sort metadata
and trigger behavior so the responsive discovery toolbar remains consistent
across Product Grid and search-surface render paths.

---

## Files Updated

- `includes/modules/search-engine/engine/sort-config.php`
- `includes/modules/search-engine/request/request-normalizer.php`
- `includes/modules/search-engine/engine/query-planner.php`
- `includes/modules/search-engine/cache/cache-service.php`
- `includes/widgets/class-bw-product-grid-widget.php`
- `includes/modules/search-surface/runtime/headless-product-grid-renderer.php`
- `assets/js/bw-product-grid.js`
- `assets/css/bw-product-grid.css`

---

## Commit Traceability

Main commit recorded in the sort hardening wave:

| Commit | Message | Contribution |
|--------|---------|--------------|
| `f9886774` | `Set newest as default sort; remove random_seeded; harden sort-config` | canonical default sort, alias cleanup, sort-config hardening |

Supporting commits in the same wave:

| Commit | Message | Contribution |
|--------|---------|--------------|
| `52cd092f` | `Task sorting part 1` | initial runtime sort consolidation work |
| `c8a6da3f` | `Update class-bw-product-grid-widget.php` | widget/runtime sort surface alignment |
| `2d491b4e` | `Update bw-product-grid.css` | responsive presentation and sizing refinement |
| `f64efa34` | `Update class-bw-product-grid-widget.php` | responsive trigger markup refinement |

---

## Validation Summary

This closure record is documentation-led.

Validated behaviors that should remain true in the live runtime:

- default sort is `newest` / `Recently added`
- `random_seeded` remains backward-compatible as an alias only
- unknown sort values fall back safely to `newest`
- year sorting continues to rely on canonical `_bw_filter_year_int`
- desktop and mobile sort triggers remain visually distinct but semantically linked
- search-surface parity remains aligned with Product Grid responsive sort

---

## Residual Risks

- Large data sets still rely on the same backend indexes and query planner;
  this documentation pass does not change runtime performance characteristics.
- Any future icon or label changes must be applied symmetrically in the
  widget, headless renderer, JS runtime, and responsive CSS.

---

## Closure Declaration

- Task closure status: `CLOSED`
- Date: `2026-04-19`
- No runtime code was modified in this documentation closure pass
