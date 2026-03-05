# Blackwork Working Protocol

## 1. Purpose
This protocol defines the official governance-driven workflow for system changes and audits in Blackwork.
It formalizes how to execute risk mitigation, controlled refactors, Tier 0 discipline, regression validation, and periodic re-audit.

This protocol is binding for all high-impact domains:
- Checkout
- Payments
- Auth / Supabase
- My Account
- Brevo
- Cross-domain callback and state contracts

Any technical task that touches these domains must follow this protocol before implementation starts.

## 1.1 Task Templates
Task templates are located in:

`docs/templates/`

Templates:
- `task-start-template.md`
- `task-closure-template.md`
- `maintenance-task-template.md`

## 2. Core Principle
Code is subordinate to governance.

This means:
- architecture invariants are primary constraints, not optional guidelines
- Tier 0 surfaces cannot be modified without blast-radius and risk review
- implementation speed cannot override authority boundaries
- release decisions must be based on proven convergence, not assumptions

No Tier 0 modification is allowed without:
1. Blast Radius review
2. Risk Register review
3. Explicit invariants preservation statement
4. Regression path declaration

## 3. Standard Work Cycle
### Phase A – Identification
Objective: classify the change and establish governance context before touching code.

Required actions:
1. Identify the domain and sub-domain:
   - checkout / payments / auth / supabase / my-account / brevo / cross-domain
2. Review Blast Radius:
   - identify whether impacted surfaces are Tier 0, Tier 1, or Tier 2
3. Review Risk Register:
   - list existing risk IDs relevant to the intended change
4. Identify invariants:
   - payment authority
   - order authority
   - auth/session authority
   - consent authority
   - callback idempotency and convergence constraints

Mandatory output of Phase A:
- `Domain`
- `Tier classification`
- `Relevant Risk IDs`
- `Invariants to preserve`
- `Initial regression scope`

No implementation is allowed before this output is complete.

### Phase B – Refactor Activation Model
Objective: activate implementation with a controlled prompt that enforces governance constraints.

Use the following template as mandatory activation payload:

```md
# Refactor Activation Prompt

## Context
- Problem statement:
- Domain:
- Tier level:
- Related Risk IDs:

## Governance Constraints
- Invariants to preserve:
  1.
  2.
  3.
- Authority boundaries that must not change:
  - Payment:
  - Order:
  - Auth/Session:
  - Consent:

## Scope
- File scope (explicit allowlist):
  - 
  - 
- Explicit out-of-scope files:
  - 
  - 

## Diff Discipline
- Minimal diff rule: apply the smallest change that restores/implements intended behavior.
- No unrelated edits.
- No opportunistic cleanup outside declared scope.

## Regression Requirement
- Mandatory checklist to execute:
  - Guest paid checkout
  - Logged-in paid checkout
  - Webhook replay/idempotency
  - Callback flow convergence
  - Domain-specific critical path

## Deliverables
- Summary of modified files
- Invariants verification statement
- Regression results
- Risk Register status suggestion (Open/Monitoring/Mitigated/Resolved)
```

Activation gate:
- if Risk IDs are missing for a Tier 0/Tier 1 task, create or map them before implementation
- if invariants cannot be stated clearly, stop and escalate clarification

### Phase C – Controlled Implementation Rules
Objective: execute only changes that remain inside governance boundaries.

Mandatory rules:
1. Do not change authority boundaries unless explicitly declared and approved.
2. Do not modify webhook authority semantics.
3. Do not bypass consent gate logic.
4. Do not alter onboarding semantics implicitly.
5. Payment truth cannot be changed by UI refactors.
6. Do not change callback ownership model through convenience edits.
7. Do not merge cross-domain side effects into a single unbounded patch.

Implementation discipline:
- keep diffs small and bounded
- preserve existing idempotency markers/guards
- preserve fallback/degrade behavior for non-blocking commerce
- avoid mixed concerns in a single commit (transport/state/render in one patch is discouraged for Tier 0)

### Phase D – Regression Minimum Suite
Objective: prove invariants were preserved after implementation.

Mandatory journeys before release:
1. Guest paid checkout
2. Logged-in paid checkout
3. Webhook replay handling
4. Supabase callback flow
5. My Account navigation
6. Downloads visibility
7. Brevo opt-in / no-opt-in behavior

Expected validation outcomes:
- deterministic gateway submission
- single-source payment truth
- stable callback convergence (no loop, no ghost loader, no flash)
- consistent onboarding marker behavior
- deterministic account route behavior
- consent gate never bypassed

If any mandatory journey fails, release is blocked.

### Phase E – Governance Update
Objective: synchronize governance artifacts with real system state after change validation.

Mandatory post-change actions:
1. Update Risk Register status for involved risk IDs.
2. Update Technical Hardening Plan if sequencing, mitigation priority, or acceptance criteria changed.
3. Update domain audit documents only if runtime behavior changed.
4. Record whether invariants were preserved or intentionally modified.

Status discipline:
- `Open`: risk still active and unmanaged
- `Monitoring`: mitigation exists but stability still being observed
- `Mitigated`: mitigation validated, residual risk low
- `Resolved`: independently re-audited and confirmed closed

## 4. Periodic System Re-Audit
Periodic re-audit is mandatory to detect drift between governance contracts and runtime reality.

Suggested cadence:
- Tier 0 domains: monthly or before major release
- Tier 1 domains: per milestone
- Cross-domain contracts: every time callback or authority semantics change

Use the following structured re-audit prompt:

```md
# Re-Audit Prompt

## Scope
- Domains:
- Anchors:
- Related Risk IDs:

## Validation Targets
1. Authority hierarchy validation
   - Payment authority unchanged?
   - Order authority unchanged?
   - Auth/session authority unchanged?
   - Consent authority unchanged?

2. Callback convergence validation
   - Any loop path?
   - Any ghost loader/stuck state?
   - Any non-idempotent callback side effect?

3. Idempotency guarantees
   - Webhook replay-safe?
   - Guest claim replay-safe?
   - Marketing sync replay-safe?

4. State boundary validation
   - Cross-domain writes within allowed boundaries?
   - Any UI layer overriding authoritative state?

## Output Required
- Findings by severity
- Updated risk status recommendation
- Regression gaps identified
- Governance document updates required
```

Re-audit stop condition:
- if authority, callback convergence, idempotency, or state boundary checks fail, open/raise corresponding risks immediately and block release.

## 5. Absolute Rules
Never do the following on Tier 0 surfaces:
- Never let UI state redefine payment or order authority.
- Never bypass webhook validation/idempotency guards.
- Never disable or weaken consent gate checks to unblock flow.
- Never silently change onboarding marker semantics.
- Never introduce callback redirects without loop/convergence proof.
- Never mix unrelated Tier 0 domains in an unscoped patch.
- Never mark risks as `Resolved` without audit-backed evidence.
- Never release when mandatory regression journeys are incomplete.

## 6. Governance Stack Reference
This protocol depends on the following governance stack:
- [System Normative Charter](./system-normative-charter.md)
- [Cross-Domain State Dictionary](./cross-domain-state-dictionary.md)
- [Unified Callback Contracts](./callback-contracts.md)
- [Blast-Radius Consolidation Map](./blast-radius-consolidation-map.md)
- [Risk Register](./risk-register.md)
- [Technical Hardening Plan](./technical-hardening-plan.md)
- Domain audits:
  - [Checkout Payment Selector Audit](../50-ops/audits/checkout-payment-selector-audit.md)
  - [My Account Domain Audit](../50-ops/audits/my-account-domain-audit.md)

If conflicts appear, the normative order is:
1. System Normative Charter
2. Cross-Domain State Dictionary
3. Callback Contracts
4. Blast Radius + Risk Register
5. Hardening Plan + domain audits

## 7. Launching a Check in a New GPT Session
When opening a new GPT session for a technical task, provide a structured context package.

Mandatory input payload:
1. Problem statement:
   - precise runtime issue or desired change
2. Domain:
   - one or more of checkout/payments/auth/supabase/my-account/brevo
3. Invariants:
   - explicit list of invariants that must remain unchanged
4. Risk context:
   - relevant Risk IDs from Risk Register
5. Scope:
   - file allowlist and out-of-scope list

Request format:
- ask GPT to generate a `Refactor Activation Prompt` based on this protocol
- require explicit mapping between planned edits and invariants
- require regression checklist before implementation

Recommended bootstrap message:

```md
Follow Blackwork Working Protocol.

Problem:
Domain:
Tier:
Risk IDs:
Invariants to preserve:
Allowed files:
Out-of-scope files:

Generate the Refactor Activation Prompt only.
Do not implement yet.
```

## 8. Maturity Model
### If this protocol is followed
- Tier 0 changes become predictable and auditable
- regression detection happens before release impact
- authority boundaries remain stable across refactors
- risk closure becomes evidence-based, not assumption-based
- system knowledge compounds through structured governance updates

### If this protocol is ignored
- cross-domain regressions increase and become harder to isolate
- callback and state drift accumulate silently
- release quality depends on manual memory instead of contracts
- risk statuses lose meaning and governance layer degrades
- emergency hotfix frequency rises due to preventable invariant breaks

Governance maturity target:
- every high-impact change traces to Risk IDs, invariants, and validated regression outcomes
- no Tier 0 change proceeds without protocol-compliant activation and closure
