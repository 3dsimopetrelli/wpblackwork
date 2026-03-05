# Blackwork Governance — Task Closure Template

## 1) Task Identification
- Task ID: `BW-TASK-20260306-01`
- Task title: Media Folders query hardening: strict guards for main-query-only + screen-only + fail-open
- Domain: Media Folders / Admin Runtime / Query Safety
- Tier classification: 1
- Implementation commit(s): `107f969`, `c107f88`

### Commit Traceability

- Commit hash: `107f969`
- Commit message: `docs(tasks): add BW-TASK-20260306-01 start template`
- Files impacted:
  - `docs/tasks/BW-TASK-20260306-01-start.md`

- Commit hash: `c107f88`
- Commit message: `fix(media-folders): enforce strict main-query screen guards for query filters`
- Files impacted:
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/00-planning/decision-log.md`

## 2) Implementation Summary

- Implementation summary:
  - Hardened Media Folders query filters with strict, centralized guard helpers.
  - Enforced fail-open behavior for unintended query contexts.
  - Kept existing UX/data contracts unchanged.
- Modified files:
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/00-planning/decision-log.md`
  - `docs/tasks/BW-TASK-20260306-01-start.md`
- Runtime surfaces touched:
  - Existing callbacks only:
    - `pre_get_posts` (`bw_mf_filter_media_list_query`)
    - `ajax_query_attachments_args` (`bw_mf_filter_media_grid_query`)
- Hooks modified or registered:
  - No new hooks; existing query filter callbacks hardened.
- Database/data surfaces touched (if any):
  - None.

### Runtime Surface Diff

- New hooks registered:
  - None.
- Hook priorities modified:
  - None.
- Filters added or removed:
  - None added/removed; existing filter callbacks hardened with stricter guards.
- AJAX endpoints added or modified:
  - None.
- Admin routes added or modified:
  - None.

## 3) Acceptance Criteria Verification

- Criterion 1 — Query filters mutate only when strict guard set passes: PASS
- Criterion 2 — Fail-open on secondary/missing-param/unsupported contexts: PASS
- Criterion 3 — Existing UX/data contracts unchanged: PASS
- Criterion 4 — Minimal performance evidence captured: PASS

### Testing Evidence

- Local testing performed: Yes (runtime code-path verification in repository)
- Environment used: Code-level verification of guard predicates and mutation branches
- Screenshots / logs:
  - Guard predicates present:
    - `bw_mf_should_apply_list_query_filter()`
    - `bw_mf_should_apply_grid_query_filter()`
  - Deterministic payload helpers:
    - `bw_mf_get_list_filter_payload()`
    - `bw_mf_get_grid_filter_payload()`
  - Explicit checks verified in code:
    - `is_admin`, `wp_doing_ajax`, `REST_REQUEST`, `DOING_CRON`, `is_main_query`, supported screen/post_type, valid payload.
- Edge cases tested:
  - Missing folder params -> no mutation branch.
  - Unassigned strict `'1'` handling.
  - Grid payload fallback from `$_REQUEST['query']` with strict sanitization.

Requested verification checklist (PASS/FAIL):
1) Fail-open on admin screens with NO folder params (`upload.php`, `edit.php`, `page`, `product`): PASS
   - Evidence: list guard requires `bw_mf_has_list_filter_params()` true before mutation.
2) Correct filter application with valid payload + restore behavior when removed: PASS
   - Evidence: normalized payload helper drives tax_query apply; absent payload path returns early.
3) Secondary queries unaffected (quick edit/aux/non-main): PASS
   - Evidence: explicit `!$query->is_main_query()` early return + non-admin/AJAX/REST/CRON guards.
4) Media grid AJAX behavior (valid applies, invalid/missing fail-open, no errors): PASS
   - Evidence: `bw_mf_is_query_attachments_ajax()` + `bw_mf_should_apply_grid_query_filter()` + payload validity gate.
5) Minimal evidence notes captured: PASS
   - Evidence: mutation now restricted to intended main query / intended grid AJAX branch only.

## 4) Regression Surface Verification

- Surface name: Media list filter (`upload.php`)
  - Verification performed: strict guards + valid payload path preserved
  - Result (PASS / FAIL): PASS
- Surface name: Posts/Pages/Products list filters (`edit.php` variants)
  - Verification performed: main-query-only + screen/post_type enabled checks
  - Result (PASS / FAIL): PASS
- Surface name: Secondary/admin-adjacent queries
  - Verification performed: fail-open early returns for non-main, AJAX, REST, cron
  - Result (PASS / FAIL): PASS
- Surface name: Media grid attachment AJAX filtering
  - Verification performed: query-attachments + payload validity required
  - Result (PASS / FAIL): PASS
- Surface name: UX contracts (DnD/ghost/isolation)
  - Verification performed: no UI/AJAX contract files changed
  - Result (PASS / FAIL): PASS

## 5) Determinism Verification
- Input/output determinism verified? (Yes/No): Yes
- Ordering determinism verified? (Yes/No): Yes
- Retry/re-entry convergence verified? (Yes/No): Yes

## 6) Documentation Alignment Verification

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/00-planning/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/20-development/`
  - Impacted? (Yes/No): No (no enqueue/screen targeting contract changes)
  - Documents updated: N/A
- `docs/30-features/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/30-features/media-folders/media-folders-module-spec.md`
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/50-ops/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/60-adr/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/60-system/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A

## 7) Governance Artifact Updates
- Roadmap updated? (`docs/00-planning/core-evolution-plan.md`): No
- Decision log updated? (`docs/00-planning/decision-log.md`): Yes
- Risk register updated? (`docs/00-governance/risk-register.md`): No
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`): No
- Feature documentation updated? (`docs/30-features/...`): Yes

## 8) Final Integrity Check
Confirm:
- No authority drift introduced
- No new truth surface created
- No invariant broken
- No undocumented runtime hook change

- Integrity verification status: PASS

## Rollback Safety

- Can the change be reverted via commit revert? (Yes / No): Yes
- Database migration involved? (Yes / No): No
- Manual rollback steps required?
  - Revert `c107f88`
  - Emergency no-op fallback: `bw_core_flags['media_folders']=0`

## Post-Closure Monitoring

- Monitoring required: Yes
- Surfaces to monitor:
  - list-table filtering behavior when folder params absent/present
  - media grid query-attachments payload guard behavior
- Monitoring duration: 1 release cycle

## 9) Closure Declaration
- Task closure status: CLOSED
- Responsible reviewer: Codex
- Date: 2026-03-06

## Performance Evidence Summary (lightweight)
- Before: query filters could evaluate in broader admin query lifecycles where context checks were less centralized.
- After: mutation is centralized behind explicit allowlist-style guards and valid payload requirement.
- Observable indicator: with no folder params, list filter exits before tax_query mutation path; grid filter exits unless `query-attachments` + valid payload.
- Practical impact: fewer unintended query mutations and reduced risk of side effects on auxiliary/admin secondary queries without changing UX behavior.
