# R-MF-02 + R-MF-03 — Media Folders Integrity/Runtime Closure

## Task Identification
- Risk IDs:
  - `R-MF-02` — Folder assignment data integrity
  - `R-MF-03` — Media admin runtime drift
- Module/Domain: Media Folders (admin runtime + media query filtering)
- Closure date: `2026-03-10`
- Final task status:
  - `R-MF-02`: `CLOSED`
  - `R-MF-03`: `CLOSED`
- Final risk status:
  - `R-MF-02`: `MITIGATED`
  - `R-MF-03`: `MITIGATED`

## File Changed
- `includes/modules/media-folders/runtime/media-query-filter.php`

## Runtime Surface Touched
- Media list/grid folder filter merge logic (`bw_mf_merge_tax_query`).

## Issue Fixed
- Previously, the merge logic could inherit existing `tax_query` relation `OR`, which could make folder/unassigned filtering non-restrictive when third-party taxonomy clauses were present.

## Implemented Hardening
- Existing tax clauses are preserved as an internal group with original relation.
- Media Folders folder/unassigned clause is applied outside that group.
- Explicit outer relation `AND` now enforces restrictive behavior deterministically.

## Determinism / Query Restriction Improvement
- Folder filtering remains restrictive even under competing `tax_query` writers.
- List/grid filtering contract remains deterministic for media folder scope.

## Assignment Integrity Assessment (R-MF-02)
- Assignment lifecycle re-validated.
- No active critical assignment bug confirmed.
- Existing assignment safeguards (capability/nonce/context/batch/term validation + cache invalidation) remain robust.

## Validation Summary
- `php -l includes/modules/media-folders/runtime/media-query-filter.php` -> PASS
- `composer run lint:main` -> PASS
- Manual regression checklist -> PASS
  - assign media to folder, reload, confirm persistence
  - remove folder, confirm attachment becomes unassigned
  - grid media filter by folder/unassigned remains correct
  - list view media filter remains correct
  - pagination/search still work in media library
  - folder filter stays restrictive with additional tax_query clauses
  - counts/tree updates remain correct after assignment/delete

## Supabase Freeze Verification
- Supabase freeze respected: YES
- Supabase/auth surfaces touched: NONE
- `bw_supabase_*` surfaces touched: NONE

## Governance Synchronization
- Updated:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
  - `docs/50-ops/regression-protocol.md`
- Closure protocol followed:
  - `docs/governance/task-close.md`

## Closure Declaration
- `R-MF-02` is mitigated and closed.
- `R-MF-03` is mitigated and closed.
