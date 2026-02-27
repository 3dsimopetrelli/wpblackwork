# ADR-006: Provider Switch Model

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork integrates external providers across payments, authentication, provisioning, and marketing synchronization.

Provider implementations may change over time, but system invariants, authority hierarchy, convergence discipline, and deterministic behavior MUST remain stable.

ADR-001 formalizes orchestration authority versus provider UI.
ADR-002 formalizes authority hierarchy.
ADR-003 formalizes callback convergence.
ADR-005 formalizes claim idempotency.

This ADR establishes providers as interchangeable execution layers behind stable internal contracts.

## Decision

Blackwork formally adopts the Provider Switch Model.

Binding rules:

- Providers MUST be treated strictly as execution layers.
- Providers MAY signal state; only local reconciliation MAY define authoritative state.
- Providers MUST NOT become business authority layers.
- Provider callbacks/webhooks MUST pass authenticity + idempotency gates before any mutation.
- Switching providers MUST NOT alter authority hierarchy, invariants, or convergence discipline.
- Provider adapters MUST be thin, deterministic, and replaceable.
- Provider outages MUST degrade safely without corrupting local authority truth.
- Provider migration MUST preserve idempotency and monotonic convergence guarantees.
- No internal invariant MAY reference provider-specific semantics.

## Provider Definition & Scope

A provider is any external system that:

- Executes remote operations
- Emits remote signals/events
- Is accessed via defined integration contract

Examples:
- Payment processors
- Identity brokers
- Marketing automation platforms

Providers are NOT:

- Local authority for payment/auth/provisioning/consent truth
- Orchestration authority for checkout UX
- Source of invariant definitions

## Contract Boundary Rules

Every provider integration MUST define explicit canonical contract boundaries:

- Canonical inputs (what local system sends)
- Canonical outputs/events (what local system consumes)
- Normalized error classes

Rules:

- Provider-specific payloads MUST be translated into canonical local representations.
- Local reconciliation layer is the only mutation entrypoint for business-critical state.
- Provider-specific UI MUST NOT become orchestration authority.
- Canonical contract MUST isolate provider differences so upper layers remain provider-agnostic.
- No business rule MAY depend on raw provider-specific status codes.

## Switching Discipline (Migration Rules)

Switching providers MUST follow strict migration discipline:

### 1. Contract Parity
Equivalent local contract coverage MUST be declared and verified.

### 2. Canonical Mapping
Provider identifiers, statuses, and event semantics MUST map to canonical representations.
Mapping MUST preserve traceability where required.

### 3. Callback/Event Mapping
Provider events MUST route through existing reconciliation pathways.
Replay and duplicate behavior MUST preserve idempotency guarantees.

### 4. State Continuity
Existing authoritative local state MUST remain valid post-switch.
In-flight operations MUST converge without regression.

### 5. Cutover Safety
Dual active providers MUST NOT simultaneously hold authority over the same contract surface.
Authority source MUST be unambiguous during migration windows.

### 6. Post-Switch Validation
Tier 0 regression validation MUST confirm invariants unchanged.

## Failure Mode & Degrade-Safely Rules

- Provider outages MUST NOT corrupt local authority truth.
- Delayed callbacks MUST reconcile deterministically.
- Partial provider-side success MUST NOT create contradictory local terminal state.
- Transient errors MUST remain retry-safe and idempotent.
- Provider anomalies MUST normalize to safe local states.
- Commerce-critical flows MUST remain non-speculative during provider instability.
- Provider outage MUST NOT elevate UI state to authority.

## Regression Requirements (Tier 0)

Provider switch or major adapter modification is Tier 0 and MUST validate:

- Authority hierarchy compatibility (ADR-002)
- Callback convergence discipline (ADR-003)
- Claim idempotency guarantees (ADR-005)
- Orchestration authority boundaries (ADR-001)
- Failure/degrade-safe behavior
- Canonical mapping integrity

Switch is incomplete until invariant-preserving regression evidence is recorded.

## Alternatives Considered

### 1) Provider-specific authority model
Rejected.
Couples business truth to external implementation.

### 2) Direct provider payload usage
Rejected.
Breaks canonical abstraction and increases drift.

### 3) Provider UI as orchestration authority
Rejected.
Conflicts with selector doctrine.

### 4) Informal switching without mapping discipline
Rejected.
Cannot guarantee invariant preservation.

## Consequences

- Provider replacement becomes structurally feasible.
- Integration complexity is isolated in thin adapters.
- Callback handling remains deterministic across migration.
- Outage scenarios remain safe.
- Governance burden increases for provider switch events.

## Invariants Protected

- Local authority doctrine remains primary.
- Providers MAY signal but MUST NOT define authoritative truth.
- Provider switches MUST preserve hierarchy and convergence invariants.
- Provider-specific UI MUST NOT become orchestration authority.
- Idempotency and monotonic state progression MUST survive migration.
- Failure/degraded behavior MUST NOT corrupt business truth.
