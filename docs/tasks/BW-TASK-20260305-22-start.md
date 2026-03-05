# Blackwork Governance — Task Start Template

## Context
- Task title: Media Folders: live settings conditionals + separate folders per content type + single-item drag assignment (Posts/Pages/Products)
- Request source: User request in current thread
- Expected outcome:
  - Live conditional settings UI in `Blackwork Site > Media Folders` without refresh.
  - “Use folders with” controls for Media/Posts/Pages/Products.
  - Strict folder-set isolation per content type (no cross-contamination).
  - List-table single-item drag assignment for Posts/Pages/Products with drag handle and title-based ghost label.
- Constraints:
  - No changes to existing option keys/defaults/save logic for current settings controls.
  - Must not break existing Media Library behavior and endpoints.
  - Admin-only scope; no storefront impact.
  - Capability + nonce validation for admin actions.
  - Performance-safe for large datasets (no heavy per-row queries).

## Task Classification
- Domain: Media Folders / Admin Runtime / Settings UX
- Incident/Task type: Feature extension + UX hardening
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/modules/media-folders/*`
  - `docs/30-features/media-folders/*`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
- Integration impact:
  - WP admin list-table screens: `upload.php`, `edit.php`, `edit.php?post_type=page`, `edit.php?post_type=product`
- Regression scope required:
  - Media Library grid/list filters + existing bulk flow.
  - Settings save and flag gating.
  - List-table render and DnD interactions for posts/pages/products.

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/media-folders/media-folders-module-spec.md`
- Integration docs to read:
  - `docs/20-development/admin-panel-map.md`
- Ops/control docs to read:
  - `docs/50-ops/runtime-hook-map.md` (runtime surface reference)
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/00-governance/risk-register.md`
  - `docs/00-planning/decision-log.md`
- Runbook to follow:
  - N/A
- Architecture references to read:
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`

## Scope Declaration
- Proposed strategy:
  - Implement settings live conditionals via admin-side JS only on settings page.
  - Keep master flag logic and existing option/save semantics intact.
  - Implement strict per-content-type folder isolation with separate taxonomies:
    - `bw_media_folder` (attachment)
    - `bw_post_folder` (post)
    - `bw_page_folder` (page)
    - `bw_product_folder` (product)
  - Introduce taxonomy resolver by current admin context/post type and route all folder tree/count/filter/assign logic through resolver.
  - For Posts/Pages/Products list tables:
    - show drag handle before title,
    - enforce single-item drag assignment only,
    - set drag ghost label to row title,
    - keep Media bulk-only behavior unchanged.
- Files likely impacted:
  - `includes/modules/media-folders/data/installer.php`
  - `includes/modules/media-folders/data/taxonomy.php`
  - `includes/modules/media-folders/data/term-meta.php` (if per-tax meta registration needed)
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
  - Storefront/frontend behavior.
  - Media modal takeover.
  - Checkout/auth/payments modules.
  - DB schema migrations/custom tables.
- Risk analysis:
  - Medium: taxonomy isolation migration/compatibility path.
  - Medium: list-table DOM coupling for drag handle and DnD.
  - Medium: query filter correctness across multiple post types.
- ADR evaluation (REQUIRED / NOT REQUIRED):
  - NOT REQUIRED (authority model unchanged; scoped admin behavior extension).

## Runtime Surface Declaration
- New hooks expected:
  - Potential taxonomy registration expansion on `init`.
  - Potential extra list-table admin hooks for drag-handle column/cell rendering.
- Hook priority modifications:
  - None expected.
- Filters expected:
  - `pre_get_posts` extended/resolved by post-type taxonomy.
  - Media grid `ajax_query_attachments_args` remains media-only.
- AJAX endpoints expected:
  - Existing endpoint names retained, payload/context extended with taxonomy resolver.
  - No mandatory new endpoint names expected.
- Admin routes expected:
  - None.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes)
- Hidden coupling risks discovered? (Yes)
  - WP list-table markup differs by post type and plugin/theme customizations.
  - Existing JS assumes media-centric selectors and DnD semantics.

## Governance Impact Analysis
- Authority surfaces touched:
  - Media Folders admin settings + admin runtime only.
- Data integrity risk:
  - Medium (separate folder sets must remain strictly isolated).
- Security surface changes:
  - Existing nonce/capability checks remain mandatory; context validation extended to taxonomy/post-type mapping.
- Runtime hook/order changes:
  - Possible additional admin hooks for list table drag handle render; no global/frontend hooks.
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): Yes (folder isolation + broader list-table runtime surface).

## System Invariants Check
- Declared invariants that MUST remain true:
  - `bw_core_flags['media_folders']=0` => effective no-op.
  - Media Library behavior preserved and not regressed.
  - No folder set leakage across content types.
  - Bulk assignment remains media-only.
  - Posts/Pages/Products DnD is single-item only.
  - Nonce + capability guards always enforced.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Central post-type/taxonomy resolver with strict mapping table.
  - Reject mismatched taxonomy/context in runtime and AJAX.
  - Separate per-taxonomy tree/count queries and caches.
  - Explicit single-item drag guard in list-table DnD handlers.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Risk: ambiguous taxonomy resolution across screens.
  - Control: deterministic map `post_type -> taxonomy`.
  - Risk: DnD assigning wrong item in list.
  - Control: enforce one source row ID per drag event for posts/pages/products.
  - Risk: stale folder tree due to shared cache.
  - Control: cache keys namespaced by taxonomy/post type and deterministic invalidation.

## Testing Strategy
- Local testing plan:
  - Settings:
    - master + nested toggles show/hide instantly.
  - Isolation:
    - create folders in Media/Posts/Pages/Products and verify each tree only shows its own taxonomy terms.
  - List-table UX:
    - drag handle visible before title on enabled post types.
    - single-row drag only; ghost text equals row title.
    - drop assigns correctly to selected folder.
  - Media regression:
    - existing bulk/media DnD unchanged.
    - media grid/list filters unchanged.
- Edge cases expected:
  - Empty folder trees per content type.
  - Disabled post type flags.
  - Missing product post type (Woo inactive) fail-open.
  - Very large list tables.
- Failure scenarios considered:
  - Invalid taxonomy/context in AJAX.
  - Missing nonce/capability.
  - Unknown post type flag state.

Acceptance criteria:
1. Settings conditional fields update live with no refresh.
2. “Use folders with” controls persist and correctly gate each screen.
3. Folder sets are isolated per content type (no cross-visibility).
4. Posts/Pages/Products list rows show drag handle and support single-item drag assignment only.
5. Drag ghost label equals dragged row title for posts/pages/products.
6. Media bulk behavior remains unchanged.

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? (Yes)
  - Target documents (if known): `docs/00-governance/risk-register.md`
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
  - Immediate fallback: set `bw_core_flags['media_folders']=0`.
  - Full rollback by commit revert.

## 6A) Documentation Alignment Requirement
- `docs/00-governance/`
  - Impacted? (Yes)
  - Target documents (if known): `risk-register.md`
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
Implementation MUST NOT begin until this template is fully completed and scope is accepted.
