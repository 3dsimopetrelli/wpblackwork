# Blackwork Governance — Task Start Template

## 1) Task Classification

- Task ID: 
- Task title: 
- Domain: 
- Tier classification (0/1/2/3): 
  - Tier 0 = Authority/Data integrity critical
  - Tier 1 = Runtime orchestration / high coupling
  - Tier 2 = Localized feature/runtime surface
  - Tier 3 = Final stabilization / low authority impact
- Authority surface touched? (Yes/No): 
- Data integrity risk? (Low/Medium/High/Critical): 
- Requires ADR? (Yes/No): 
- Blast radius assessment:
  - Primary surfaces impacted:
  - Cross-domain collision points:
  - Hook-sensitive surfaces impacted:

Normative rules:
- Tier 0 tasks MUST be treated as frozen-authority changes unless explicitly authorized by roadmap and governance review.
- If authority ownership changes, ADR is REQUIRED before implementation.

## 2) Pre-Task Reading Checklist

Before implementation, the following documents MUST be reviewed and referenced:

- [ ] ADR index (`docs/60-adr/`)
- [ ] Authority Matrix (`docs/00-governance/authority-matrix.md`)
- [ ] Runtime Hook Map (`docs/50-ops/runtime-hook-map.md`)
- [ ] Domain Map (`docs/00-overview/domain-map.md`)
- [ ] Core Evolution Plan (`docs/00-planning/core-evolution-plan.md`)
- [ ] Regression Protocol (`docs/50-ops/regression-protocol.md`)
- [ ] Blast Radius Consolidation Map (`docs/00-governance/blast-radius-consolidation-map.md`)
- [ ] Relevant domain spec/audit documents:

Normative rules:
- Implementation MUST NOT start until this checklist is completed.
- Missing required references MUST block task start.

## 3) Scope Declaration

- Declared files to modify:
- Declared runtime surfaces:
- Declared docs to update:
- Explicitly out-of-scope surfaces:
- Explicitly forbidden changes:

Normative rules:
- Only declared surfaces MAY be modified.
- Undeclared surface changes MUST trigger stop-and-review.

## 4) Governance Impact Analysis

- Does this change authority ownership? (Yes/No)
- Does this introduce a new truth surface? (Yes/No)
- Does this modify runtime hook registration or priority? (Yes/No)
- Does this touch Tier 0 state or Tier 0 hooks? (Yes/No)
- Does this alter data identity rules (SKU, order, payment, consent, auth)? (Yes/No)

Required determination:
- Governance impact status: `None` / `Controlled` / `ADR Required`
- If `ADR Required`, implementation MUST NOT proceed.

## 5) Determinism Statement

Define deterministic behavior expected after change:

- Same input + same state snapshot MUST produce same output.
- Tie-break strategy (if ordering involved):
- Pagination/state convergence expectation:
- Retry/re-entry convergence expectation:

Normative rules:
- Any non-deterministic behavior MUST be explicitly identified and gated.
- If determinism cannot be guaranteed, task MUST escalate.

## 6) Documentation Update Plan

Specify all required sync updates:

- Roadmap update required? (Yes/No)
  - Target: `docs/00-planning/core-evolution-plan.md`
- Decision log update required? (Yes/No)
  - Target: `docs/00-planning/decision-log.md`
- Regression coverage map update required? (Yes/No)
  - Target: `docs/50-ops/regression-coverage-map.md`
- Runtime hook map update required? (Yes/No)
  - Target: `docs/50-ops/runtime-hook-map.md`
- Feature/domain spec update required? (Yes/No)
  - Target documents:
- Governance doc update required? (Yes/No)
  - Target documents:

Normative rules:
- Documentation synchronization plan MUST exist before implementation.
- If backlog status changes, roadmap update is REQUIRED.
- If decision direction changes, decision-log update is REQUIRED.

## 7) Acceptance Gate

Define measurable acceptance criteria:

1. 
2. 
3. 
4. 
5. 

Required validation sets:
- Regression surfaces to verify:
- Determinism checks to verify:
- Authority boundary checks to verify:

Normative rules:
- Acceptance criteria MUST be objective and testable.
- Task CANNOT be closed without satisfying all declared acceptance items.

## 8) Abort Conditions

Implementation MUST stop immediately and escalate if any condition occurs:

- Authority ownership drift detected.
- New truth surface introduced without ADR.
- Tier 0 hook priority mutation requested without governance approval.
- Undeclared cross-domain coupling discovered.
- Determinism guarantees fail or cannot be proven.
- Data integrity invariants become ambiguous.

Escalation path:
- Create/Update ADR if authority or invariant changes are involved.
- Update risk/governance docs before resuming implementation.
