# Blackwork Governance — Task Start Template

## Context
- Task title: Keep Blackwork Site admin menu icon green across all wp-admin screens
- Request source: User request on 2026-03-16
- Expected outcome: The `Blackwork Site` top-level admin menu icon remains green with the black border/dot styling both when its screen is active and when the user is browsing other wp-admin screens.
- Constraints:
  - Preserve existing menu slug, callback, capability, and submenu behavior.
  - Keep the change limited to admin visual behavior only.
  - Avoid CSS bleed into non-Blackwork menu items.

## Task Classification
- Domain: Blackwork admin panel UX
- Incident/Task type: Admin visual consistency fix
- Risk level (L1/L2/L3): L1
- Tier classification (0/1/2/3): 3
- Affected systems: WordPress admin sidebar, Blackwork Site top-level menu icon styling
- Integration impact: Internal admin-only surface
- Regression scope required: Blackwork top-level menu visibility and styling on active/non-active admin screens

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/20-development/admin-panel-map.md`
- Integration docs to read:
  - `docs/20-development/admin-ui-guidelines.md`
- Ops/control docs to read:
  - `docs/50-ops/blackwork-development-protocol.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/governance/task-close.md`
  - `docs/templates/task-closure-template.md`
- Runbook to follow:
  - N/A (admin visual-only fix)
- Architecture references to read:
  - `admin/class-blackwork-site-settings.php`
  - `admin/css/blackwork-site-menu.css`

## Scope Declaration
- Proposed strategy:
  - Keep the existing scoped menu CSS.
  - Change the enqueue behavior so the Blackwork menu icon stylesheet loads on all wp-admin screens.
  - Preserve strict selector scoping to `#toplevel_page_blackwork-site-settings`.
- Files likely impacted:
  - `admin/class-blackwork-site-settings.php`
  - `docs/20-development/admin-panel-map.md`
  - `docs/tasks/BW-TASK-20260316-04-start.md`
- Explicitly out-of-scope surfaces:
  - Site Settings page layout
  - Submenu rendering
  - Frontend/storefront behavior
  - Option storage or save handlers
- Risk analysis:
  - Main risk is loading a menu stylesheet globally in wp-admin.
  - Mitigation: stylesheet remains tiny and fully scoped to the Blackwork menu ID only.
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
- Authority surfaces touched: None; admin visual-only surface
- Data integrity risk: None
- Security surface changes: None
- Runtime hook/order changes: None
- Requires ADR? (Yes/No): No
- Risk register impact required? (Yes/No): No
- Risk dashboard impact required? (Yes/No): No

## System Invariants Check
- Declared invariants that MUST remain true:
  - `Blackwork Site` top-level menu continues to route to `blackwork-site-settings`
  - Existing submenu ordering remains unchanged
  - Non-Blackwork admin menu items remain unaffected
- Any invariant at risk? (Yes/No): No
- Mitigation plan for invariant protection:
  - Change only the enqueue guard for the already-scoped menu stylesheet.

## Determinism Statement
- Input/output determinism declared? (Yes/No): Yes
- Ordering determinism declared? (Yes/No): Yes
- Retry determinism declared? (Yes/No): Yes
- Pagination/state convergence determinism declared? (Yes/No): Yes
- Determinism risks and controls:
  - The same stylesheet is deterministically loaded on every wp-admin screen.
  - Styling remains constrained to a single menu selector, so render behavior converges identically across admin pages.

## Testing Strategy
- Local testing plan:
  - Verify the Blackwork Site icon styling on a Blackwork screen.
  - Verify the Blackwork Site icon styling on a non-Blackwork admin screen.
  - Run `php -l` on modified PHP files.
  - Run `composer run lint:main`.
- Edge cases expected:
  - WordPress admin screens unrelated to Blackwork should show no side effects.
- Failure scenarios considered:
  - Global enqueue could affect other admin items if selectors are too broad.

## Documentation Update Plan
Documentation layers that MUST be considered before implementation:

- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
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
  - Revert the menu enqueue change; no database rollback required.

## 6A) Documentation Alignment Requirement
Before implementation begins, the documentation architecture MUST be evaluated.

The following documentation layers MUST be checked for potential updates:
- `docs/00-governance/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/00-planning/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/10-architecture/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/20-development/`
  - Impacted? (Yes/No): Yes
  - Target documents (if known): `docs/20-development/admin-panel-map.md`
- `docs/30-features/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/40-integrations/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
- `docs/50-ops/`
  - Impacted? (Yes/No): No
  - Target documents (if known):
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
