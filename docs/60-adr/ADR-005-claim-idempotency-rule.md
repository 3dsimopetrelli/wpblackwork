# ADR-005: Claim Idempotency Rule

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context
Blackwork provisioning and claim flows can be triggered from multiple asynchronous paths: payment-confirmed lifecycle hooks, callback-driven identity continuation, manual retry paths, and user/browser re-entry.

ADR-002 defines authority hierarchy (Payment > Authentication > Provisioning).
ADR-003 defines callback convergence and anti-flash discipline.

This ADR formalizes deterministic claim idempotency so entitlement activation remains safe under retries, duplicate triggers, out-of-order events, and concurrent execution.

## Problem Definition (Duplicate / Race Risks)
Without explicit claim idempotency discipline, the system is exposed to:

- duplicate entitlement activation from repeated triggers,
- race conditions between webhook-confirmed payment transitions and claim execution,
- frontend refresh/re-entry causing repeated claim writes,
- manual retry colliding with in-flight automatic claim,
- out-of-order invocation where claim attempts occur before required authority predicates converge,
- non-deterministic partial writes that cannot safely recover.

These risks can produce duplicated ownership effects, unstable entitlement visibility, and inconsistent post-order account state.

## Decision
Blackwork formally adopts the Claim Idempotency Rule as a Tier 0 governance constraint for provisioning/claim flows.

Binding rules:
- Claim execution MUST be idempotent.
- Claim execution MAY be retried but MUST converge to one stable entitlement state.
- Duplicate triggers (webhook retry, manual retry, browser refresh, callback re-entry) MUST NOT create duplicate entitlements.
- Entitlement activation MUST be monotonic.
- Claim execution MUST be safe under concurrent invocation.
- Claim state MUST be persisted before and/or atomically with entitlement mutation.
- Failed claim attempts MUST be safely retryable.
- Claim logic MUST tolerate out-of-order webhook and frontend invocation.

## Claim Identity Model
A claim MUST be identified by a deterministic Claim Identity Key representing one business entitlement target.

Minimum identity dimensions:
- order identity,
- subject identity (resolved authenticated principal / mapped user target),
- entitlement scope (what access/ownership is being attached).

Normative model:
- The same business claim target MUST always resolve to the same Claim Identity Key.
- Distinct business targets MUST NOT share a Claim Identity Key.
- Claim Identity Key design MUST prevent duplicate activation for the same order/subject/scope tuple.

## Idempotency Enforcement Rules
- Every claim attempt MUST execute under an idempotency guard derived from the Claim Identity Key.
- If a claim for the same key is already completed, re-entry MUST short-circuit to completed without additional mutation.
- If a claim for the same key is in progress, concurrent attempts MUST NOT duplicate side effects.
- If a claim previously failed, retry MUST re-evaluate prerequisites and execute only missing/safe steps.
- Claim completion marker(s) MUST reflect terminal convergence state and be readable by all trigger paths.
- Idempotency decisions MUST be based on authoritative persisted state, never transient UI signals.

## Concurrency Safety Model
- Claim handlers MUST assume concurrent invocation is possible.
- Claim mutation paths MUST use concurrency-safe guards (e.g., lock/compare-and-set/transactional pattern) appropriate to runtime capabilities.
- Check-then-write without protection CANNOT be considered safe.
- Concurrent attempts for the same Claim Identity Key MUST converge to one effective entitlement mutation.
- Concurrency conflicts MUST degrade safely to retryable outcomes, not duplicate activation.

## Monotonic Entitlement Rules
- Entitlement state transitions MUST be monotonic.
- Activation CANNOT regress from active/claimed to unclaimed due to duplicate/reordered triggers.
- Reprocessing MUST preserve already-valid entitlement truth.
- Partial progress markers MAY exist, but terminal claimed state MUST be stable and non-regressive.
- Provisioning claim state MUST NOT override Payment or Authentication authority.

## Retry & Re-entry Behavior
- Retries are permitted and expected under transient failure.
- Retry execution MUST be deterministic for equal authority state and equal Claim Identity Key.
- Out-of-order events MUST be tolerated:
  - If prerequisites are not met, claim MUST defer/exit safely without unsafe mutation.
  - When prerequisites converge later, retry/re-entry MUST complete claim without duplication.
- Browser refresh, callback revisit, and manual retry MUST all converge to the same terminal state.
- Duplicate trigger storms MUST yield one entitlement outcome and stable idempotency markers.

## Alternatives Considered

### 1) Best-effort claim execution without strict idempotency
Rejected.
Cannot guarantee convergence under duplicate triggers and race conditions.

### 2) UI/session-driven claim completion
Rejected.
Violates authority model and is unsafe under async retries/re-entry.

### 3) Single-trigger assumption (webhook-only or frontend-only)
Rejected.
Operational reality includes multiple trigger sources and retries.

### 4) Manual-only recovery for duplicates
Rejected.
Not deterministic, not scalable, and not governance-grade.

## Consequences
- Claim/provisioning flows become deterministic under duplicate and concurrent invocation.
- Entitlement activation remains stable across retries and out-of-order events.
- Duplicate ownership/entitlement side effects are structurally prevented.
- Recovery from transient failures is safer through retry convergence.
- Cross-domain integrity with ADR-002 and ADR-003 is reinforced.

## Invariants Protected
- Same claim target MUST NOT activate entitlements more than once.
- Repeated claim triggers MUST converge to one stable terminal entitlement state.
- Claim execution MUST remain safe under concurrent invocation.
- Entitlement transitions MUST be monotonic and non-regressive.
- Claim logic MUST remain downstream of Payment and Authentication authority.
- Out-of-order invocation MUST NOT create duplicate or contradictory entitlement state.
