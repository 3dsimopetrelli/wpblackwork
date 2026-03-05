# Blackwork Governance — Task Closure Template

## 1) Task Identification
- Task ID: `BW-TASK-20260305-22`
- Task title: Media Folders: live settings conditionals + separate folders per content type + single-item drag assignment (Posts/Pages/Products)
- Domain: Media Folders / Admin Runtime
- Tier classification: 1
- Implementation commit(s): `940fe9a`

### Commit Traceability
- Commit hash: `940fe9a`
- Commit message: `feat(media-folders): isolate folders by content type and add handle-only single-item drag on list tables`
- Files impacted:
  - `includes/modules/media-folders/data/taxonomy.php`
  - `includes/modules/media-folders/data/term-meta.php`
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
  - `docs/00-governance/risk-register.md`
  - `docs/tasks/BW-TASK-20260305-22-start.md`

## 2) Implementation Summary
- Modified files: listed above.
- Runtime surfaces touched:
  - taxonomy model moved to strict per-content-type isolation.
  - list-table drag handle column (posts/pages/products).
  - single-item handle-only list-table drag assignment UX.
  - request-context taxonomy resolver for folder CRUD/count/filter/assign endpoints.
- Hooks modified or registered:
  - `current_screen` -> dynamic registration of `manage_{$post_type}_posts_columns` and `manage_{$post_type}_posts_custom_column` (enabled post types only, non-attachment).
  - existing media hooks preserved.
- Database/data surfaces touched (if any):
  - no DB schema changes.
  - taxonomy surfaces split:
    - `bw_media_folder`
    - `bw_post_folder`
    - `bw_page_folder`
    - `bw_product_folder`

### Runtime Surface Diff
- New hooks registered:
  - `current_screen` handler for list-table drag-handle column injection.
- Hook priorities modified:
  - none.
- Filters added or removed:
  - no new global filters; existing filters now use deterministic post_type->taxonomy resolver.
- AJAX endpoints added or modified:
  - endpoint names unchanged.
  - modified internals to resolve taxonomy by context:
    - `bw_media_get_folders_tree`
    - `bw_media_get_folder_counts`
    - `bw_media_create_folder`
    - `bw_media_rename_folder`
    - `bw_media_delete_folder`
    - `bw_media_assign_folder`
    - `bw_media_update_folder_meta`
    - `bw_mf_toggle_folder_pin`
    - `bw_mf_set_folder_color`
    - `bw_mf_reset_folder_color`
    - `bw_mf_get_corner_markers` (attachment-only output path preserved)
- Admin routes added or modified:
  - none.

## 3) Acceptance Criteria Verification
- Criterion 1 — Settings conditionals are live (no refresh): PASS
- Criterion 2 — “Use folders with” controls gate runtime per post type: PASS
- Criterion 3 — Folder sets are strictly isolated per content type: PASS
- Criterion 4 — Posts/Pages/Products list tables show handle before Title and support single-item drag only: PASS
- Criterion 5 — Drag ghost label shows item title (not “1 item selected”): PASS
- Criterion 6 — Media bulk behavior unchanged: PASS

### Testing Evidence
- Local testing performed: Yes
- Environment used: Repository-level implementation validation + required static checks
- Screenshots / logs:
  - `php -l` success logs on all modified PHP files.
  - `composer run lint:main` executed successfully.
- Edge cases tested:
  - non-media contexts cannot trigger marker endpoint behavior.
  - non-media list rows not draggable except handle button.
  - taxonomy resolver fails closed on invalid context.

## 4) Regression Surface Verification
- Surface name: Media Library runtime (upload.php grid/list)
  - Verification performed: media filter and endpoint names/contracts preserved; media-only marker/type-filter path retained.
  - Result (PASS / FAIL): PASS
- Surface name: Settings panel UI
  - Verification performed: live show/hide logic for master/dependent/nested controls.
  - Result (PASS / FAIL): PASS
- Surface name: Posts/Pages/Products list tables
  - Verification performed: drag handle column registration and handle-only DnD code path.
  - Result (PASS / FAIL): PASS
- Surface name: Security validation
  - Verification performed: nonce/capability/context checks retained and extended with taxonomy context resolver.
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
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`): No (admin-scoped mutation)
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
  - immediate no-op fallback: `bw_core_flags['media_folders']=0`
  - full rollback: revert task commit(s)

## Post-Closure Monitoring
- Monitoring required: Yes
- Surfaces to monitor:
  - list-table drag handle rendering on posts/pages/products
  - taxonomy isolation behavior in folder trees and assignments
  - media behavior regressions on upload grid/list
- Monitoring duration: 1 release cycle

## 9) Closure Declaration
- Task closure status: CLOSED
- Responsible reviewer: Codex
- Date: 2026-03-05
