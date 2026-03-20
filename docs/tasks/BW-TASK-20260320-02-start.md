# Blackwork Governance - Task Start Template

## Context
- Task title: Plan a governed modern Reviews system and Elementor widget program
- Request source: User request on 2026-03-20
- Expected outcome: Implement the governed Reviews backend/module architecture for Blackwork, including schema, settings, admin management, confirmation flow, Brevo reuse, and Woo-native review disabling.
- Constraints:
  - Keep the Reviews domain isolated under `includes/modules/reviews/`
  - Follow Blackwork governance and documentation discipline before any code work
  - Preserve the current BW Elementor widget architecture and loader conventions
  - Reuse shared Brevo credentials and extract list loading before Reviews consumes it
  - Keep business logic outside any future Elementor widget adapter

## Task Classification
- Domain: Elementor Widgets / Architecture / Content Experience / Review System
- Incident/Task type: Governed feature implementation
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - Blackwork Elementor widget subsystem
  - custom reviews data model and admin surfaces
  - WooCommerce product review authority surface
  - Brevo review-channel synchronization
- Integration impact: Medium
- Regression scope required:
  - admin review management
  - review submission and confirmation runtime
  - WooCommerce native review disabling
  - Brevo shared-service reuse

## Pre-Task Reading Checklist
- Feature docs to read:
  - `docs/30-features/elementor-widgets/README.md`
  - `docs/30-features/elementor-widgets/widget-inventory.md`
  - `docs/30-features/elementor-widgets/architecture-direction.md`
  - `docs/30-features/elementor-widgets/rationalization-policy.md`
  - `docs/30-features/elementor-widgets/migration-sequence.md`
- Integration docs to read:
  - `docs/10-architecture/elementor-widget-architecture-context.md`
- Ops/control docs to read:
  - `docs/50-ops/blackwork-development-protocol.md`
  - `docs/50-ops/incident-classification.md`
  - `docs/50-ops/maintenance-decision-matrix.md`
  - `docs/50-ops/maintenance-workflow.md`
- Governance docs to read:
  - `docs/00-governance/ai-task-protocol.md`
  - `docs/templates/task-start-template.md`
  - `docs/templates/task-closure-template.md`
  - `docs/governance/task-close.md`
- Runbook to follow:
  - Governed planning and documentation-first flow for Elementor feature work
- Architecture references to read:
  - `includes/class-bw-widget-loader.php`
  - `includes/class-bw-widget-helper.php`
  - `blackwork-core-plugin.php`

## Scope Declaration
- Proposed strategy:
  - Implement Reviews as a dedicated module under `includes/modules/reviews/`.
  - Create a custom reviews table with installer/version handling and repository-based access.
  - Deliver Blackwork admin screens for review queue management and settings tabs.
  - Extract reusable Brevo list loading into a shared integration service before Reviews uses it.
- Files likely impacted:
  - `docs/tasks/BW-TASK-20260320-02-start.md`
  - `blackwork-core-plugin.php`
  - `admin/class-blackwork-site-settings.php`
  - `includes/integrations/brevo/`
  - `includes/modules/reviews/`
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Explicitly out-of-scope surfaces:
  - Elementor reviews widget UI implementation
  - review-image upload flow
  - reward automation/coupon issuance
  - review import/migration from Woo native comments
- Risk analysis:
  - "reviews" may mean testimonials, editorial reviews, product review cards, or a broader content system; ambiguity must be resolved before implementation
  - architecture can drift quickly if content authority is not fixed first
  - a data-backed review system could require governance escalation if it introduces admin routes, persistence rules, or runtime authority changes
  - a purely widget-local solution may be fast but could create duplicated content and weak reuse
- ADR evaluation (REQUIRED / NOT REQUIRED): NOT REQUIRED in this planning-open phase; re-evaluate if a new data authority, persistence model, or cross-domain runtime contract is proposed

## Runtime Surface Declaration
- New hooks expected: none in this phase
- Hook priority modifications: none in this phase
- Filters expected: none in this phase
- AJAX endpoints expected: none in this phase
- Admin routes expected: none in this phase

## 3.1) Implementation Scope Lock
- All files expected to change are listed? Yes
- Hidden coupling risks discovered? Yes

## Governance Impact Analysis
- Authority surfaces touched:
  - planning and documentation authority only in this phase
  - future review-content authority remains undecided pending prompt comparison
- Data integrity risk: Low in this phase; potentially Medium if later scope introduces stored review entities or shared content authority
- Security surface changes: None in this phase
- Runtime hook/order changes: None in this phase
- Requires ADR? No for task opening; maybe later depending on architecture choice
- Risk register impact required? No for task opening; re-evaluate if this becomes a governed implementation program
- Risk dashboard impact required? No for task opening

## System Invariants Check
- Declared invariants that MUST remain true:
  - no runtime code changes occur before architecture direction is agreed
  - BW widget loader conventions remain the reference path for any future widget
  - review content authority must be explicit and singular once chosen
  - planning outputs must stay aligned with Blackwork documentation structure
  - no unrelated domains are pulled into scope without explicit justification
- Any invariant at risk? Yes
- Mitigation plan for invariant protection:
  - keep this phase documentation-only
  - separate discovery from implementation
  - compare proposals through fixed evaluation criteria before choosing a build path

## Determinism Statement
- Input/output determinism declared? Yes
- Ordering determinism declared? Yes
- Retry determinism declared? Yes
- Pagination/state convergence determinism declared? Yes
- Determinism risks and controls:
  - prompt comparison must use stable evaluation axes, not ad hoc preference shifts
  - if later implementation includes collections, ordering, featured items, or fallback behavior, those rules must be defined before coding
  - if later implementation includes multiple review sources, precedence must be declared explicitly

## Testing Strategy
- Local testing plan:
  - verify task-start completeness against the Blackwork template
  - verify referenced docs and architecture paths exist in the repository
  - use this document as the single intake point for later prompt comparison
- Edge cases expected:
  - prompt proposals that conflict with existing widget family boundaries
  - prompt proposals that implicitly require admin CRUD or new persistence without saying so
  - prompt proposals that mix testimonial content and product-review behavior into one undefined widget
- Failure scenarios considered:
  - implementation starts before review authority is chosen
  - multiple prompt ideas get merged without a clear decision record
  - visual ambition overrides maintainable architecture boundaries

## Documentation Update Plan
- `docs/00-governance/`
  - Impacted? No
  - Target documents (if known): -
- `docs/00-planning/`
  - Impacted? Maybe
  - Target documents (if known):
    - `docs/00-planning/decision-log.md`
    - `docs/00-planning/core-evolution-plan.md`
- `docs/10-architecture/`
  - Impacted? Maybe
  - Target documents (if known):
    - `docs/10-architecture/elementor-widget-architecture-context.md`
- `docs/20-development/`
  - Impacted? No
  - Target documents (if known): -
- `docs/30-features/`
  - Impacted? Yes
  - Target documents (if known):
    - `docs/30-features/elementor-widgets/README.md`
    - `docs/30-features/elementor-widgets/widget-inventory.md`
    - future review-system feature doc to be created after direction is chosen
- `docs/40-integrations/`
  - Impacted? No
  - Target documents (if known): -
- `docs/50-ops/`
  - Impacted? Maybe
  - Target documents (if known):
    - runbook or regression target only if implementation introduces new runtime checks
- `docs/60-adr/`
  - Impacted? Maybe
  - Target documents (if known):
    - new ADR only if architecture-binding authority changes are approved
- `docs/60-system/`
  - Impacted? No
  - Target documents (if known): -

## Rollback Strategy
- Revert via commit possible? Yes
- Database migration involved? No
- Manual rollback steps required?
  - Revert this task-start file if the planning thread is superseded by a different governed task

## 6A) Documentation Alignment Requirement
Before implementation begins, evaluate:
- `docs/00-governance/` - No
- `docs/00-planning/` - Maybe
- `docs/10-architecture/` - Maybe
- `docs/20-development/` - No
- `docs/30-features/` - Yes
- `docs/40-integrations/` - No
- `docs/50-ops/` - Maybe
- `docs/60-adr/` - Maybe
- `docs/60-system/` - No

## Acceptance Gate
- Task Classification completed? Yes
- Pre-Task Reading Checklist completed? Yes
- Scope Declaration completed? Yes
- Implementation Scope Lock passed? Yes
- Governance Impact Analysis completed? Yes
- System Invariants Check completed? Yes
- Determinism Statement completed? Yes
- Documentation Update Plan completed? Yes
- Documentation Alignment Requirement completed? Yes

## Prompt Intake Protocol
- This task remains open for prompt collection and comparison before implementation.
- Incoming prompts should be evaluated against these axes:
  - review content authority model
  - Elementor editor UX complexity
  - frontend visual ambition vs maintainability
  - reusability across templates/pages
  - asset/runtime scope
  - migration safety inside the current widget architecture
  - need for admin data management
- Each prompt batch should produce:
  - strengths
  - risks
  - hidden implementation assumptions
  - recommended keep/drop decisions

## Working Assumptions To Validate
- "Reviews" may include one or more of these tracks:
  - a visually modern testimonial/review widget for Elementor
  - a reusable review content structure behind the widget
  - a family of review layouts rather than one single widget
- The preferred Blackwork direction may become:
  - one canonical review widget with layout variants
  - one review data structure with multiple presentation skins
  - a phased approach where static curated reviews land first and data centralization follows later

## Abort Conditions
- prompt comparison reveals that the task actually spans multiple independent systems and needs to be split
- a chosen direction requires new authority ownership that has not been governed
- review requirements remain ambiguous after comparison and cannot be bounded deterministically

## Governance Enforcement Rule
Implementation must not begin until prompt comparison, review authority choice, and runtime boundaries are documented and agreed inside this task.
