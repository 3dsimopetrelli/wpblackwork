# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders: settings live-conditional UI + enable folders for Posts/Pages/Products
- Request source: User request in current thread
- Expected outcome: Live-reactive Media Folders settings UI (no refresh required) and admin list-table folder UX support for Posts, Pages, Products, while preserving current Media behavior.
- Constraints:
  - Keep existing Media behavior intact.
  - No changes to existing option keys/defaults/save logic for current settings controls.
  - New post type support must remain admin-only and modular.
  - Existing Media Library grid/list filters and endpoints must not regress.
  - Capability/nonce validation required for any new/extended admin action paths.
  - Performance-safe with large datasets.

## Task Classification
- Domain: Media Folders / Admin Runtime / Settings UI
- Incident/Task type: Feature hardening + scoped extension
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/modules/media-folders/admin/*`
  - `includes/modules/media-folders/runtime/*`
  - `includes/modules/media-folders/data/*`
  - `docs/20-development/*`
  - `docs/30-features/media-folders/*`
  - `docs/00-planning/*` (decision contract update)
- Integration impact: WordPress admin list tables (`upload.php`, `edit.php`, `edit.php?post_type=page`, `edit.php?post_type=product`)
- Regression scope required:
  - Media Library grid/list filters
  - Media sidebar CRUD/assign/bulk
  - Settings persistence
  - No-op behavior when disabled

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
- Integration docs to read:
  - `docs/20-development/admin-panel-map.md`
- Ops/control docs to read:
  - `docs/50-ops/runtime-hook-map.md` (reference for declared runtime surfaces)
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/00-governance/risk-register.md`
  - `docs/00-planning/decision-log.md`
- Runbook to follow:
  - N/A (no dedicated runbook for Media Folders)
- Architecture references to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`

## Scope Declaration
- Proposed strategy:
  - Add settings-page live conditional behavior using lightweight admin JS on settings screen only.
  - Add “Use folders with” controls (Media/Posts/Pages/Products) in settings and persist in `bw_core_flags`.
  - Extend Media Folders runtime screen guards + taxonomy object types + list query filtering + assignment normalization to support selected post types.
  - Keep grid/ajax media behavior unchanged unless running on Media Library.
- Files likely impacted:
  - `includes/modules/media-folders/data/installer.php`
  - `includes/modules/media-folders/data/taxonomy.php`
  - `includes/modules/media-folders/admin/media-folders-settings.php`
  - `includes/modules/media-folders/admin/media-folders-admin.php`
  - `includes/modules/media-folders/admin/media-folders-sidebar.php`
  - `includes/modules/media-folders/admin/assets/media-folders.js`
  - `includes/modules/media-folders/admin/assets/media-folders.css`
  - `includes/modules/media-folders/runtime/media-query-filter.php`
  - `includes/modules/media-folders/runtime/ajax.php`
  - `docs/30-features/media-folders/media-folders-module-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
- Explicitly out-of-scope surfaces:
  - Storefront/frontend behavior
  - Woo checkout/payments/auth modules
  - New DB tables/migrations
  - Media modal takeover
- Risk analysis:
  - Medium risk from extending admin hooks beyond `upload.php`.
  - Medium risk from selector/click/drag behavior on WP list tables.
  - Medium risk of count/filter mismatch if post-type context is not propagated.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED (no authority model change, scoped admin extension)

## Runtime Surface Declaration
- New hooks expected:
  - `admin_footer-edit.php` (sidebar mount on selected post type list tables)
- Hook priority modifications:
  - None expected.
- Filters expected:
  - Extend `pre_get_posts` scope from Media-only to selected post types on list tables.
  - Keep `ajax_query_attachments_args` Media-only.
- AJAX endpoints expected:
  - No new endpoint names expected; existing endpoints extended to respect selected post type context.
- Admin routes expected:
  - No new routes; settings page updated.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes)
- Hidden coupling risks discovered? (Yes)
  - Coupling with WP list table DOM (`#the-list`, row ids, bulk checkbox patterns).
  - Coupling with current `bw_mf_context=upload` validation in AJAX.

## Governance Impact Analysis
- Authority surfaces touched:
  - Media Folders admin runtime and settings only.
- Data integrity risk:
  - Medium (cross-post-type assignment/filter coherence).
- Security surface changes:
  - Existing nonce/capability checks reused; context validation broadened from strict `upload` to declared allowed admin list contexts.
- Runtime hook/order changes:
  - Additional mount hook for `edit.php`; widened list filter guard.
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): Yes (if risk posture changes; otherwise verify existing Media Folders risks remain sufficient)

## System Invariants Check
- Declared invariants that MUST remain true:
  - Module master flag OFF => no-op.
  - Existing Media Library behavior unchanged when Media support is enabled.
  - Grid attachment query filter remains fail-open and Media-only.
  - No frontend/storefront mutation.
  - All assignment operations validated with nonce/capability and allowed post-type checks.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Add explicit screen/post-type guard helpers.
  - Keep media-grid logic conditionally bound to upload screen only.
  - Fail-open on unknown screen/post type/context.
  - Keep endpoint action names/contracts unchanged.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Risk: list-table filtering ambiguity across post types.
  - Control: explicit post-type context passed from UI and validated server-side.
  - Risk: repeated refresh calls from observers.
  - Control: existing coalesced scheduler retained, no new recursive refresh loops.
  - Risk: mixed screen context for AJAX.
  - Control: strict allowed-context mapping and fail-open returns on invalid context.

## Testing Strategy
- Local testing plan:
  - Settings page live toggles:
    - master enable/disable updates dependent controls instantly.
    - corner indicator toggle updates tooltip toggle visibility instantly.
    - “Use folders with” controls visible only when master is enabled.
  - Media (`upload.php`) regression:
    - sidebar mounts; list/grid filtering works; DnD + bulk still works.
  - Posts/Pages/Products list tables:
    - sidebar mounts only when corresponding flag enabled.
    - folder/unassigned filtering via URL params works.
    - row drag/drop and bulk assignment works.
  - Disabled behavior:
    - master OFF = no sidebar/assets/runtime filters.
    - per-post-type OFF = no sidebar on that list table.
- Edge cases expected:
  - Empty folder tree.
  - Unassigned filter with zero items.
  - Large selection capped by existing batch limit.
  - Unknown/disabled post type context in AJAX.
- Failure scenarios considered:
  - Invalid nonce/capability/context.
  - Taxonomy not attached to target post type.
  - List table selectors missing.

Acceptance criteria:
1. Settings toggles react live without page refresh.
2. Posts/Pages/Products list-table screens show folder sidebar + filtering + assignment only when enabled via settings.
3. Media Library behavior remains unchanged.
4. Feature flags OFF produce no-op behavior.
5. No new security regression on AJAX actions.

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? (Yes)
  - Target documents (if known): `docs/00-governance/risk-register.md` (only if risk posture changed)
- `docs/00-planning/`
  - Impacted? (Yes)
  - Target documents (if known): `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/20-development/`
  - Impacted? (Yes)
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes)
  - Target documents (if known): `docs/30-features/media-folders/media-folders-module-spec.md`
- `docs/40-integrations/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/50-ops/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/60-adr/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/60-system/`
  - Impacted? (No)
  - Target documents (if known): N/A

## Rollback Strategy
- Revert via commit possible? (Yes/No): Yes
- Database migration involved? (Yes/No): No
- Manual rollback steps required?
  - Revert commit(s), or set `bw_core_flags['media_folders']=0` as immediate no-op fallback.

## 6A) Documentation Alignment Requirement
- `docs/00-governance/`
  - Impacted? (Yes)
  - Target documents (if known): `risk-register.md` (conditional update)
- `docs/00-planning/`
  - Impacted? (Yes)
  - Target documents (if known): `decision-log.md`
- `docs/10-architecture/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/20-development/`
  - Impacted? (Yes)
  - Target documents (if known): `admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes)
  - Target documents (if known): `media-folders-module-spec.md`
- `docs/40-integrations/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/50-ops/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/60-adr/`
  - Impacted? (No)
  - Target documents (if known): N/A
- `docs/60-system/`
  - Impacted? (No)
  - Target documents (if known): N/A

## Acceptance Gate
DO NOT IMPLEMENT YET.

Gate checklist:
- Task Classification completed? (Yes/No): Yes
- Pre-Task Reading Checklist completed? (Yes/No): Yes
- Scope Declaration completed? (Yes/No): Yes
- Implementation Scope Lock passed? (Yes/No): Yes
- Governance Impact Analysis completed? (Yes/No): Yes
- System Invariants Check completed? (Yes/No): Yes
- Determinism Statement completed? (Yes/No): Yes
- Documentation Update Plan completed? (Yes/No): Yes
- Documentation Alignment Requirement completed? (Yes/No): Yes

## Abort Conditions
- Scope drift detected
- Undeclared authority surface discovered
- Invariant breach risk not mitigated
- Determinism cannot be guaranteed
- Required documentation alignment cannot be completed
- ADR required but not approved

## Governance Enforcement Rule
Implementation begins only after this Task Start is complete and acceptance gate is fully green.
