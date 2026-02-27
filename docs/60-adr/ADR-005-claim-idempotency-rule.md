# ADR-005: Claim Idempotency Rule

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork provisioning and claim flows can be triggered from multiple asynchronous paths: payment-confirmed lifecycle hooks, callback-driven identity continuation, manual retry paths, and user/browser re-entry.

ADR-002 defines authority hierarchy (Payment > Authentication > Provisioning).
ADR-003 defines callback convergence and anti-flash discipline.

This ADR formalizes deterministic claim idempotency to guarantee entitlement stability under retries, duplicate triggers, out-of-order events, and concurrent execution.

## Problem Definition (Duplicate / Race Risks)

Without strict claim idempotency:

- Duplicate entitlement activation may occur.
- Webhook-confirmed transitions and claim execution may race.
- Browser refresh may cause repeated writes.
- Manual retry may collide with in-flight automatic claim.
- Out-of-order invocation may partially mutate state.
- Partial writes may create unrecoverable divergence.

These conditions threaten deterministic entitlement ownership.

## Decision

Blackwork formally adopts the Claim Idempotency Rule as a Tier 0 governance constraint.

Binding rules:

- Claim execution MUST be idempotent.
- A given Claim Identity Key MUST result in at most one effective entitlement mutation.
- Claim execution MAY be retried but MUST converge to one stable terminal state.
- Duplicate triggers MUST NOT create duplicate entitlements.
- Entitlement activation MUST be monotonic.
- Claim execution MUST be concurrency-safe.
- Claim state persistence and entitlement mutation MUST be atomically consistent or recoverably reconcilable.
- Failed claim attempts MUST be safely retryable.
- Claim logic MUST tolerate out-of-order webhook and frontend invocation.

## Claim Identity Model

A claim MUST be uniquely defined by a deterministic Claim Identity Key.

Minimum identity dimensions:

- Order identity
- Subject identity (resolved authenticated principal)
- Entitlement scope (access target)

Normative constraints:

- Same business claim target MUST resolve to the same Claim Identity Key.
- Distinct claim targets MUST NOT share a Claim Identity Key.
- Claim Identity Key design MUST structurally prevent duplicate activation.

## Idempotency Enforcement Rules

- Every claim attempt MUST execute under an idempotency guard derived from the Claim Identity Key.
- If claim is already completed, re-entry MUST short-circuit without mutation.
- If claim is in progress, concurrent attempts MUST NOT duplicate side effects.
- If claim previously failed, retry MUST execute only missing safe steps.
- Claim completion markers MUST reflect terminal convergence and be readable across all trigger paths.
- Idempotency decisions MUST rely solely on authoritative persisted state.

## Concurrency Safety Model

- Claim handlers MUST assume concurrent invocation.
- Check-then-write without protection CANNOT be considered safe.
- Concurrency control MUST use locking, transactional guarantees, compare-and-set, or equivalent safe pattern.
- Concurrent attempts for the same Claim Identity Key MUST converge to one effective entitlement mutation.
- Concurrency conflicts MUST degrade to retryable states, not duplicate writes.

## Monotonic Entitlement Rules

- Entitlement transitions MUST be monotonic.
- Active/claimed state CANNOT regress due to retries or reordering.
- Reprocessing MUST preserve already-valid entitlement truth.
- Claim completion requires:
  - Entitlement exists,
  - Entitlement state is valid,
  - Idempotency marker persisted.
- Claim state MUST NOT override Payment or Authentication authority.

## Retry & Re-entry Behavior

- Retries are expected and MUST be safe.
- Execution MUST be deterministic for identical authority state + Claim Identity Key.
- Out-of-order events MUST be tolerated:
  - If prerequisites are unmet, claim MUST exit safely without mutation.
  - When prerequisites converge later, retry MUST complete claim without duplication.
- Browser refresh, callback revisit, and manual retry MUST converge to the same terminal state.
- Trigger storms MUST yield exactly one effective entitlement outcome.

## Alternatives Considered

### 1) Best-effort claim execution
Rejected.
Cannot guarantee convergence.

### 2) UI/session-driven completion
Rejected.
Violates authority model and async safety.

### 3) Single-trigger assumption
Rejected.
Operational reality includes retries and multi-source triggers.

### 4) Manual duplicate correction
Rejected.
Non-deterministic and not governance-grade.

## Consequences

- Claim flows become deterministic under concurrency and retries.
- Entitlement activation remains stable and non-duplicative.
- Recovery from transient failure is safe.
- Cross-domain authority boundaries remain intact.
- Provisioning integrity is structurally enforced.

## Invariants Protected

- Same claim target MUST NOT activate entitlement more than once.
- Repeated triggers MUST converge to one stable entitlement state.
- Claim execution MUST remain concurrency-safe.
- Entitlement transitions MUST be monotonic and non-regressive.
- Claim logic remains strictly downstream of Payment and Authentication authority.
- Out-of-order invocation MUST NOT produce duplicate or contradictory entitlement state.
