# Blackwork Governance — Task Start Template

## Context
- Task title: Add Shop template type to All Templates dropdown and template-type surfaces
- Request source: User request on 2026-03-16
- Expected outcome: `Shop` is available in the template type dropdowns/filters for `All Templates` and template edit surfaces, and the new Shop authority surface accepts/reflects `shop` templates coherently.
- Constraints:
  - Preserve existing shop runtime behavior already introduced in Theme Builder Lite.
  - Keep backward compatibility with already linked `product_archive` templates in Shop settings.
  - Do not break Product Archive category/tag archive routing.

## Task Classification
- Domain: Theme Builder Lite / Template Type Authority / Admin UX
- Incident/Task type: Admin feature extension / type-system alignment
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 2
- Affected systems: All Templates list UX, template-type metabox, Shop settings validation, import type map
- Integration impact: Theme Builder Lite template-type integrity
- Regression scope required: All Templates type dropdown/filter, template edit type selector, Shop settings selection/linkage, Product Archive linkage safety

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
- Runbook to follow:
  - Theme Builder Lite targeted regression checks
- Architecture references to read:
  - `includes/modules/theme-builder-lite/cpt/template-meta.php`
  - `includes/modules/theme-builder-lite/admin/bw-templates-list-ux.php`
  - `includes/modules/theme-builder-lite/admin/import-template.php`
  - `includes/modules/theme-builder-lite/runtime/shop-runtime.php`

## Scope Declaration
- Proposed strategy:
  - Add `shop` to the authoritative template-type enum and admin labels.
  - Expose `Shop` in All Templates inline type dropdown, type filter, and template metabox selector.
  - Update Shop settings validation/choices to accept `shop` templates while preserving legacy `product_archive` compatibility.
  - Update docs to declare the new template-type surface.
- Files likely impacted:
  - `includes/modules/theme-builder-lite/cpt/template-meta.php`
  - `includes/modules/theme-builder-lite/admin/bw-templates-list-ux.php`
  - `includes/modules/theme-builder-lite/admin/import-template.php`
  - `includes/modules/theme-builder-lite/runtime/shop-runtime.php`
  - `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`
  - `docs/20-development/admin-panel-map.md`
  - `docs/00-planning/decision-log.md`
  - `docs/50-ops/regression-protocol.md`
  - `docs/tasks/BW-TASK-20260316-06-start.md`
- Explicitly out-of-scope surfaces:
  - New runtime request context separate from `product_archive`
  - Import widget heuristics for auto-detecting `shop`
  - Product Archive rule storage contract
- Risk analysis:
  - Main risk is type/linkage drift between `shop` and legacy `product_archive` templates selected in Shop settings.
  - Mitigation: Shop validation accepts both during compatibility window; docs and badges clarify authority.
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
- Authority surfaces touched: Theme Builder Lite template-type authority and Shop linkage validation
- Data integrity risk: Low-Medium
- Security surface changes: None
- Runtime hook/order changes: None
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - Shop override remains bounded to shop root requests only.
  - Product Archive category rules remain authoritative for category/tag archive routing.
  - Invalid template type/linkage must fail open rather than fatal.
  - All Templates remains WP-native in mechanics.
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Keep compatibility validation in Shop settings.
  - Keep Product Archive settings and resolver branch unchanged.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - Template type enum is centralized.
  - Shop linkage uses explicit single-template validation with safe fallback.

## Testing Strategy
- Local testing plan:
  - Verify `Shop` appears in All Templates inline type dropdown.
  - Verify `Shop` appears in the template edit type metabox.
  - Verify `Shop` appears in All Types filter.
  - Verify Shop settings accept a `shop` template and save/reload.
  - Run `php -l` on modified PHP files.
  - Run `composer run lint:main`.
- Edge cases expected:
  - legacy Shop selection still using `product_archive`
  - template type changed from `product_archive` to `shop` while linked
- Failure scenarios considered:
  - Shop templates disappearing from selector because validation is too strict
  - linked-template badges not reflecting updated type semantics

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/00-planning/decision-log.md`
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
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
  - Revert template-type enum and Shop validation changes; existing option data remains safe.

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
  - Impacted? (Yes/No): No
  - Target documents (if known):
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
