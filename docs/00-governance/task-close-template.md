# Blackwork Governance — Task Close Template

## 1) Scope Verification

- Task ID:
- Task title:
- Declared scope reference:
- Actual files changed:
- Any undeclared file changed? (Yes/No)
- Any undeclared runtime surface touched? (Yes/No)

Normative rules:
- Actual modifications MUST match declared scope.
- If undeclared surfaces were modified, closure MUST be blocked until governance review.

## 2) Authority Verification

- Authority ownership changed? (Yes/No)
- Tier classification changed? (Yes/No)
- Tier 0 surface touched? (Yes/No)
- Runtime hook priority changed? (Yes/No)
- ADR required by outcome? (Yes/No)
- ADR reference (if required):

Normative rules:
- Authority drift MUST NOT occur silently.
- Any authority/tier/hook-priority change affecting authority behavior REQUIRES ADR.

## 3) Determinism Verification

- Determinism statement from task-start reviewed? (Yes/No)
- Same input + same state snapshot => same output verified? (Yes/No)
- Ordering tie-break consistency verified? (Yes/No/Not Applicable)
- Pagination/state convergence verified? (Yes/No/Not Applicable)
- Retry/re-entry convergence verified? (Yes/No/Not Applicable)

Normative rules:
- Task closure MUST be blocked if determinism checks fail.

## 4) Regression Checklist Confirmation

Regression surfaces verified (explicit list):

- [ ] Core functional surface 1:
- [ ] Core functional surface 2:
- [ ] Tier 0-sensitive surface checks:
- [ ] Cross-domain coupling checks:
- [ ] Runtime hook-sensitive checks:

Result summary:
- Passed:
- Failed:
- Blocked:

Normative rules:
- Required regression checks MUST be executed and recorded.
- Tier 0/Tier 1 tasks MUST include regression evidence before closure.

## 5) Documentation Sync Confirmation

Documentation updates completed:

- Roadmap updated? (Yes/No/Not Required)
  - `docs/00-planning/core-evolution-plan.md`
- Decision log updated? (Yes/No/Not Required)
  - `docs/00-planning/decision-log.md`
- Feature/domain spec updated? (Yes/No/Not Required)
- Governance docs updated? (Yes/No/Not Required)
- Runtime hook map updated? (Yes/No/Not Required)
- Regression coverage map updated? (Yes/No/Not Required)

Normative rules:
- If backlog state changed, roadmap update is REQUIRED.
- If decision direction changed, decision-log update is REQUIRED.
- Closure MUST NOT occur before documentation synchronization.

## 5.1) Invariant Confirmation

- Any system invariant modified? (Yes/No)
- Any invariant weakened or made ambiguous? (Yes/No)
- Any new invariant implicitly introduced? (Yes/No)

If YES:
- ADR reference:
- Documentation updated accordingly.

Normative rule:
- Closure MUST be blocked if invariant drift occurred without ADR.

## 6) Risk Reclassification

Post-implementation risk assessment:

- Data integrity risk: Low / Medium / High / Critical
- Authority drift risk: Low / Medium / High / Critical
- Runtime collision risk: Low / Medium / High / Critical
- Determinism risk: Low / Medium / High / Critical
- Overall residual risk:

Risk status:
- `Open` / `Monitoring` / `Mitigated` / `Resolved`

Normative rules:
- Risk status MUST be explicit at closure.
- High/Critical residual risk MUST block full closure and require escalation plan.

## 7) Closure Declaration

- Task status: `CLOSED` / `PARTIAL` / `BLOCKED`
- Closure date:
- Governance declaration:
  - No unauthorized authority mutation occurred.
  - No undeclared surface changes remain unresolved.
  - Determinism and regression obligations are satisfied.

Required closure action:
- Append closure entry to `docs/00-planning/decision-log.md`.

Closure notes:
