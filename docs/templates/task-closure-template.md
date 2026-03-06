# Blackwork Governance — Task Closure Template

This template defines the mandatory governance protocol for closing implementation tasks in the Blackwork repository.  
A task MUST NOT be closed unless all required verification and documentation alignment steps have been completed.

## 1) Task Identification
- Task ID:
- Task title:
- Domain:
- Tier classification:
- Implementation commit(s):

### Commit Traceability

List all commits associated with this task.

- Commit hash:
- Commit message:
- Files impacted:

Normative rule:
All commits implementing the task MUST be traceable to the Task ID.

Normative rules:
- Task identification fields are REQUIRED.
- Closure MUST NOT proceed with missing identification metadata.

## 2) Implementation Summary
Briefly describe what was implemented.

- Modified files:
- Runtime surfaces touched:
- Hooks modified or registered:
- Database/data surfaces touched (if any):

### Runtime Surface Diff

Declare all runtime surfaces affected by this task.

- New hooks registered:
- Hook priorities modified:
- Filters added or removed:
- AJAX endpoints added or modified:
- Admin routes added or modified:

Normative rule:
All runtime surface mutations MUST be declared.

Normative rule:
- All modified surfaces MUST be explicitly declared.

## 3) Acceptance Criteria Verification
List each acceptance criterion declared in the Task Start Template.

- Criterion 1 — Status (PASS / FAIL):
- Criterion 2 — Status (PASS / FAIL):
- Criterion 3 — Status (PASS / FAIL):

Normative rules:
- All criteria MUST pass before closure.
- If any criterion fails, the task MUST remain open.

### Testing Evidence

Provide evidence of testing performed.

- Local testing performed: Yes / No
- Environment used:
- Screenshots / logs:
- Edge cases tested:

Normative rule:
Testing evidence MUST exist for runtime-critical changes.

## 4) Regression Surface Verification
List all regression surfaces declared at task start.

- Surface name:
  - Verification performed:
  - Result (PASS / FAIL):
- Surface name:
  - Verification performed:
  - Result (PASS / FAIL):

Examples of common surfaces:
- Checkout flow
- Media folders runtime
- Admin panel UI
- Elementor widget runtime

Normative rule:
- All declared regression surfaces MUST be verified.

## 5) Determinism Verification
- Input/output determinism verified? (Yes/No)
- Ordering determinism verified? (Yes/No)
- Retry/re-entry convergence verified? (Yes/No)

Normative rule:
- If determinism guarantees fail, the task MUST NOT close.

## 6) Documentation Alignment Verification
The following documentation layers MUST be checked:

- `docs/00-governance/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/00-planning/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/10-architecture/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/20-development/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/30-features/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/40-integrations/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/50-ops/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/60-adr/`
  - Impacted? (Yes/No)
  - Documents updated:
- `docs/60-system/`
  - Impacted? (Yes/No)
  - Documents updated:

Normative rule:
- Documentation MUST reflect implemented system behavior.

## 7) Governance Artifact Updates
- Roadmap updated? (`docs/00-planning/core-evolution-plan.md`)
- Decision log updated? (`docs/00-planning/decision-log.md`)
- Risk register updated? (`docs/00-governance/risk-register.md`)
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`)
- Feature documentation updated? (`docs/30-features/...`)

Normative rule:
- If system behavior changes, governance/documentation updates are REQUIRED.

## 8) Final Integrity Check
Confirm:
- No authority drift introduced
- No new truth surface created
- No invariant broken
- No undocumented runtime hook change

- Integrity verification status: PASS / FAIL

Normative rule:
- Integrity status MUST be PASS for closure.

## Rollback Safety

Confirm rollback feasibility.

- Can the change be reverted via commit revert? (Yes / No)
- Database migration involved? (Yes / No)
- Manual rollback steps required?

Normative rule:
If rollback is not trivial, mitigation steps MUST be documented.

## Post-Closure Monitoring
Specify if runtime monitoring is required after deployment.

- Monitoring required: Yes / No
- Surfaces to monitor:
- Monitoring duration:

Examples:
- checkout flow
- media library runtime
- payment gateways
- admin UI runtime

Normative rule:
If runtime-critical surfaces are modified, monitoring MUST be declared.

## Release Gate Preparation
- Release Gate required? (Yes/No)

Release validation notes:
- runtime surfaces to verify
- operational smoke tests required

- Rollback readiness confirmed? (Yes/No)

Normative rules:
- Release Gate preparation MUST be completed before deployment.
- Closure artifacts MUST provide enough evidence to execute Release Gate checks.

## 9) Closure Declaration
- Task closure status: CLOSED / REOPEN REQUIRED
- Responsible reviewer:
- Date:

Normative rule:
- A task MUST NOT be marked CLOSED unless all verification sections pass.

## Governance Enforcement Rule
This template defines the mandatory closure protocol for implementation tasks.

A task MUST NOT be closed unless:
- acceptance criteria pass
- determinism guarantees are verified
- documentation alignment is complete
- governance artifacts are updated

All AI agents and contributors MUST follow this protocol.
