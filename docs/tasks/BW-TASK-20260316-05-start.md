# Blackwork Governance — Task Start Template

## Context
- Task title: Add dedicated Shop tab to Theme Builder Lite with footer-style control surface
- Request source: User request on 2026-03-16
- Expected outcome: `Blackwork Site > Theme Builder Lite` gains a new `Shop` tab, visually and structurally similar to `Footer`, with a simple enable toggle and active template selector for the WooCommerce shop page.
- Constraints:
  - Preserve existing `Product Archive` rule behavior for category/tag archives.
  - Keep the new Shop authority surface bounded to Woo shop/archive root requests only.
  - Reuse existing `product_archive` templates instead of introducing a new template type in this phase.
  - Preserve fail-open behavior when Shop is disabled or no valid template is selected.

## Task Classification
- Domain: Theme Builder Lite / Woo Shop admin + runtime resolver
- Incident/Task type: Admin feature extension / runtime branch addition
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: Theme Builder Lite settings page, Woo product archive resolver branch, All Templates linkage badges
- Integration impact: WooCommerce shop archive rendering under Theme Builder Lite
- Regression scope required: Theme Builder Lite settings save, shop page resolution, product category archive resolution, All Templates linkage truth

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- Integration docs to read:
  - `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
  - `docs/20-development/admin-panel-map.md`
- Ops/control docs to read:
  - `docs/50-ops/regression-protocol.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/governance/task-close.md`
  - `docs/templates/task-closure-template.md`
  - `docs/00-planning/decision-log.md`
- Runbook to follow:
  - Theme Builder Lite targeted regression checks
- Architecture references to read:
  - `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
  - `includes/modules/theme-builder-lite/runtime/template-resolver.php`
  - `includes/modules/theme-builder-lite/runtime/product-archive-runtime.php`
  - `includes/modules/theme-builder-lite/admin/bw-templates-list-ux.php`

## Scope Declaration
- Proposed strategy:
  - Add a dedicated Shop option snapshot and resolver helper.
  - Render a new `Shop` admin tab with footer-style controls (`enabled` + active template selector).
  - Resolve Shop before Product Archive settings rules when request context is Woo shop/post-type archive root.
  - Reflect Shop linkage truth inside All Templates badges/summaries.
- Files likely impacted:
  - `includes/modules/theme-builder-lite/config/feature-flags.php`
  - `includes/modules/theme-builder-lite/runtime/shop-runtime.php`
  - `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
  - `includes/modules/theme-builder-lite/runtime/template-resolver.php`
  - `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
  - `includes/modules/theme-builder-lite/admin/bw-templates-list-ux.php`
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
  - `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260316-05-start.md`
- Explicitly out-of-scope surfaces:
  - New template type `shop`
  - Import Template type mapping changes
  - Legacy `bw_tbl_display_rules_v1` metabox behavior
  - Footer runtime changes
- Risk analysis:
  - Main risk is precedence drift between Shop and Product Archive settings branches.
  - Mitigation: make Shop branch explicit and scoped only to `product_archive_kind=shop`, preserving fail-open behavior and existing category/tag branch semantics.
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED

## Runtime Surface Declaration
- New hooks expected: None
- Hook priority modifications: None
- Filters expected: None
- AJAX endpoints expected: None
- Admin routes expected: None

### Supabase Flow Risk Alert
- Not applicable.

## 3.1) Implementation Scope Lock
- All files expected to change are listed? (Yes/No): Yes
- Hidden coupling risks discovered? (Yes/No): No

## Governance Impact Analysis
- Authority surfaces touched: Theme Builder Lite settings authority for Woo shop page
- Data integrity risk: Low
- Security surface changes: None
- Runtime hook/order changes: None
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - `Product Archive` rules remain authoritative for product category/tag archives only.
  - Theme Builder Lite resolver remains fail-open on invalid/no-match states.
  - Shop selection only accepts published `bw_template` posts with type `product_archive`.
  - All Templates list mechanics remain WP-native.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Shop branch returns `handled=false` outside shop context.
  - Product Archive branch remains untouched for `product_cat`.
  - Linked-template badges are updated to surface truth for the new authority surface.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Shop branch is a single-template deterministic selector.
  - Resolver order is explicit: Shop first for shop context, Product Archive rules for category context, generic candidate selection only as fallback.

## Testing Strategy
- Local testing plan:
  - Save Theme Builder Lite Shop settings and reload.
  - Verify shop page resolves selected template when enabled.
  - Verify shop page fails open when disabled or invalid.
  - Verify product category archive rules still resolve correctly.
  - Verify All Templates shows Shop linkage badge for selected template.
  - Run `php -l` on modified PHP files.
  - Run `composer run lint:main`.
- Edge cases expected:
  - Shop enabled but no template selected.
  - Selected template later retyped/unpublished.
  - Same template linked to Shop and Product Archive rules.
- Failure scenarios considered:
  - Shop branch accidentally intercepting category/tag archive requests.
  - Badge/linkage truth drift in All Templates.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/50-ops/regression-protocol.md`
- `docs/60-adr/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? (Yes/No): No
  - Target documents (if known):

## Rollback Strategy
- Revert via commit possible? (Yes/No): Yes
- Database migration involved? (Yes/No): No
- Manual rollback steps required?
  - Revert Shop option/runtime/admin changes; no data migration required.

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/10-architecture/theme-builder-lite/runtime-hook-map.md`
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/50-ops/regression-protocol.md`
- `docs/60-adr/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/60-system/`
  - Impacted? (Yes/No): No
  - Target documents (if known):

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

## Release Gate Awareness
All tasks will pass through the Release Gate before deployment.
