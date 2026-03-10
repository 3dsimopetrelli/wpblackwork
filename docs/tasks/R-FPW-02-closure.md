# R-FPW-02 Closure Record

## Task
- Task ID: `R-FPW-02`
- Title: Normalize transient cache key inputs (Filtered Post Wall)
- Domain: Filtered Post Wall / Public AJAX Runtime

## Protocol Reference
- Closure executed following: `docs/governance/task-close.md`

## Runtime Surface Touched
- File changed: `blackwork-core-plugin.php`
- Function: `bw_fpw_get_tags()`
- Surface: transient cache-key generation for tag lookup requests

## Issue Fixed
- Logically identical subcategory sets with different order could produce different transient keys, reducing cache reuse.
- Example before: `[12,8,3]` vs `[3,12,8]` -> different `md5(wp_json_encode(...))` payloads.

## Implemented Hardening
- Added cache-key-only canonicalization:
  - copy subcategory array
  - `sort(..., SORT_NUMERIC)`
  - hash sorted copy
- Query behavior, response payload, tag ordering, and filter semantics remain unchanged.

## Determinism Improvement
- Transient key generation is now deterministic for set-equivalent subcategory inputs.
- Cache fragmentation due to input-order variance is reduced.

## Validation Summary
- `php -l` passed
- `composer run lint:main` passed
- Patch scope confined to one PHP file

## Supabase Freeze Check
- Supabase/auth surfaces were not modified.

## Final Status
- FPW transient cache key instability: `MITIGATED`
- Task `R-FPW-02`: `CLOSED`
