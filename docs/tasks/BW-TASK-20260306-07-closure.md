# Blackwork Governance — Task Closure Artifact

## 1) Task Identification
- Task ID: `BW-TASK-20260306-07`
- Task title: Media Folders Runtime Isolation Hardening
- Domain: Media Library / Attachment Query Isolation
- Tier classification: 1
- Risk reference: `R-MF-02` (runtime query isolation mitigation)

## 2) Scope
Implemented scope:
- `includes/modules/media-folders/runtime/media-query-filter.php`
- `docs/00-governance/risk-register.md`
- `docs/tasks/BW-TASK-20260306-07-closure.md`

Conditional scope usage:
- `includes/modules/media-folders/runtime/ajax.php`: not modified (no shared helper extraction needed)

## 3) Implementation Summary
1. `ajax_query_attachments_args` signature safety
- Grid filter callback converted to variadic input handling (`bw_mf_filter_media_grid_query(...$filter_args)`).
- Defensive extraction of `$args/$query` avoids argument-count/type failures if filter invocation shape varies.

2. Query contamination protection
- Grid/list mutation is now attachment-only and context-gated.
- If post type is not `attachment`, taxonomy is invalid, payload is malformed, or context checks fail, original query is returned untouched.

3. `pre_get_posts` isolation hardening
- List filter restricted to admin intended context + `attachment` screen.
- Main-query guard retained.
- Added re-entry guard to avoid recursive/nested accidental mutation.

4. Deterministic taxonomy filtering
- Added validated taxonomy resolver (`bw_mf_get_valid_media_folder_taxonomy`) enforcing taxonomy existence and `attachment` binding.
- `tax_query` merge remains additive/deterministic and does not overwrite unrelated clauses.

5. Fail-open safety
- Invalid args, unsupported context, unknown taxonomy, empty computed MF clause, or malformed payload all bypass mutation.

6. Performance guardrails
- No per-attachment lookups introduced in query filters.
- No unbounded loops or deep recursion introduced.
- Filtering remains query-level (`tax_query`) and cheap guard-first.

7. Admin vs modal parity
- Both list and grid paths now depend on the same attachment taxonomy validation contract.
- Folder filter payload remains normalized deterministically in both paths.

## 4) Determinism Evidence
- Input/output determinism:
  - Same request payload and context produce the same mutated/bypassed query outcome.
- Ordering determinism:
  - Guard evaluation order is stable: context -> post_type -> taxonomy validity -> payload -> tax_query merge.
- Retry/re-entry determinism:
  - Re-entry guard prevents nested list-query mutation side effects.
  - Repeated identical modal requests converge to the same query args.

## 5) Runtime Surfaces Touched
- Existing runtime hooks only (unchanged registration):
  - `pre_get_posts` @ 20 -> `bw_mf_filter_media_list_query`
  - `ajax_query_attachments_args` @ 20 -> `bw_mf_filter_media_grid_query`
- No new hooks/endpoints/routes introduced.

## 6) Manual Verification Checklist
- [ ] Media modal (grid): valid folder selection mutates `tax_query` only for `attachment`.
- [ ] Media modal (grid): malformed `bw_media_folder` payload returns original args (no fatal, no contamination).
- [ ] Media modal (grid): non-attachment `post_type` query is not modified.
- [ ] Admin media list (upload.php): folder/unassigned filter applies deterministically.
- [ ] Admin list without folder params: no query mutation.
- [ ] Non-media admin queries remain unchanged (no leakage).
- [ ] Existing `tax_query` clauses are preserved and merged, not overwritten.
- [ ] Large media library baseline: no additional expensive per-item loops from filter path.

## 7) Residual Risks
- Third-party plugins can still mutate attachment queries before/after this filter and create cross-plugin interactions.
- Duplicate `R-MF-01` IDs in governance docs remain a traceability issue (pre-existing documentation conflict).
- Extremely large datasets may still require future indexed/materialized count strategy beyond query-filter hardening.

## 8) Governance / Documentation Updates
- Updated mitigation text in `docs/00-governance/risk-register.md` (`R-MF-02`).
- Added this task artifact for closure evidence.
- No runtime hook-map update required (runtime surface unchanged).
- No decision-log update required (no new standing authority rule).

## 9) Validation Commands
- `php -l includes/modules/media-folders/runtime/media-query-filter.php` -> PASS
- `composer run lint:main` -> PASS
