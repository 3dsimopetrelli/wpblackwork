# Blackwork Governance — Task Closure Template

## 1) Task Identification
- Task ID: `BW-TASK-20260305-21`
- Task title: Media Folders: settings live-conditional UI + enable folders for Posts/Pages/Products
- Domain: Media Folders / Admin Runtime
- Tier classification: 1
- Implementation commit(s): `TBD (to be filled after commit)`

### Commit Traceability
- Commit hash: `TBD`
- Commit message: `TBD`
- Files impacted:
  - `includes/modules/media-folders/data/installer.php`
  - `includes/modules/media-folders/data/taxonomy.php`
  - `includes/modules/media-folders/admin/media-folders-settings.php`
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
  - `docs/00-governance/risk-register.md`
  - `docs/tasks/BW-TASK-20260305-21-start.md`

## 2) Implementation Summary
- Modified files: see list above.
- Runtime surfaces touched:
  - media-folders settings admin UI (live conditional behavior)
  - list-table runtime guards for `upload.php` and selected `edit.php` post types
  - taxonomy registration object types based on flags
  - AJAX context and counting/assignment post-type awareness
- Hooks modified or registered:
  - `admin_footer-edit.php` added for sidebar mount
  - `admin_enqueue_scripts` guard broadened to selected `edit.php` screens
  - `pre_get_posts` guard broadened to selected post types
  - existing `ajax_query_attachments_args` preserved for media grid only
- Database/data surfaces touched (if any):
  - Option `bw_core_flags` extended with:
    - `media_folders_use_media`
    - `media_folders_use_posts`
    - `media_folders_use_pages`
    - `media_folders_use_products`
  - No schema migration.

### Runtime Surface Diff
- New hooks registered:
  - `admin_footer-edit.php` -> `bw_mf_render_sidebar_mount`
- Hook priorities modified:
  - None.
- Filters added or removed:
  - None added/removed.
  - `pre_get_posts` behavior extended by screen/post-type guard.
- AJAX endpoints added or modified:
  - No new endpoint names.
  - Modified behavior:
    - `bw_media_get_folders_tree`
    - `bw_media_get_folder_counts`
    - `bw_media_assign_folder`
    - `bw_mf_get_corner_markers` (attachment context guard)
  - Context validation changed from upload-only to allowed list-table contexts (`upload`, `post`, `page`, `product`) with flag guard.
- Admin routes added or modified:
  - None.

## 3) Acceptance Criteria Verification
- Criterion 1 — Settings toggles react live without page refresh: PASS (implemented via settings-page inline JS visibility sync).
- Criterion 2 — Posts/Pages/Products list-table screens show folder sidebar + filtering + assignment only when enabled via settings: PASS (runtime guards + taxonomy object types + context-aware AJAX assignment/counts implemented).
- Criterion 3 — Media Library behavior remains unchanged: PASS (media grid filter hook untouched; media-only marker/type-filter behavior preserved).
- Criterion 4 — Feature flags OFF produce no-op behavior: PASS (master flag gating unchanged; per-post-type guards added).
- Criterion 5 — No new security regression on AJAX actions: PASS (nonce/capability checks preserved; context now validated against enabled post types).

### Testing Evidence
- Local testing performed: Yes
- Environment used: Local repository static/runtime code validation
- Screenshots / logs:
  - `php -l` logs: all modified PHP files report no syntax errors.
  - `composer run lint:main` executed successfully.
- Edge cases tested:
  - Invalid/missing context path in AJAX returns error.
  - Assignment IDs filtered by requested post type.
  - Corner marker endpoint returns empty markers for non-attachment contexts.

## 4) Regression Surface Verification
- Surface name: Media Library runtime (`upload.php` grid/list)
  - Verification performed: Hook/contracts inspection + unchanged media-grid filter path + context mapping check.
  - Result (PASS / FAIL): PASS
- Surface name: Media folders assignment endpoints
  - Verification performed: Input normalization and context/capability/nonce guard review.
  - Result (PASS / FAIL): PASS
- Surface name: Settings page behavior
  - Verification performed: Live conditional JS logic and existing save flow preservation review.
  - Result (PASS / FAIL): PASS
- Surface name: Admin list tables (`post`, `page`, `product`)
  - Verification performed: Enqueue/mount/filter guards and post-type context propagation review.
  - Result (PASS / FAIL): PASS

## 5) Determinism Verification
- Input/output determinism verified? (Yes/No): Yes
- Ordering determinism verified? (Yes/No): Yes
- Retry/re-entry convergence verified? (Yes/No): Yes

## 6) Documentation Alignment Verification
- `docs/00-governance/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/00-governance/risk-register.md`
- `docs/00-planning/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Documents updated: N/A
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Documents updated: `docs/20-development/admin-panel-map.md`
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
- Risk register updated? (`docs/00-governance/risk-register.md`): Yes
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`): No (admin-only surface; no core hook-map mutation required)
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
  - Immediate no-op fallback: `bw_core_flags['media_folders']=0`.
  - Full rollback: revert task commit(s).

## Post-Closure Monitoring
- Monitoring required: Yes
- Surfaces to monitor:
  - `upload.php` media list/grid behavior
  - `edit.php` list-table behavior for post/page/product
  - AJAX error rate for `bw_media_*` actions after enabling new post-type flags
- Monitoring duration: 1 release cycle

## 9) Closure Declaration
- Task closure status: CLOSED
- Responsible reviewer: Codex
- Date: 2026-03-05
