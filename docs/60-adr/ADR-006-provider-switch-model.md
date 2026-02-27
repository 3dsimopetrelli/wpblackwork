# ADR-006: Provider Switch Model

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context
Blackwork uses external providers across payments, authentication/provisioning, and marketing synchronization.
Provider implementations can evolve over time (e.g., gateway engines, auth brokers, marketing APIs), while system invariants and authority boundaries must remain stable.

ADR-001 formalizes selector orchestration authority versus provider UI components.
ADR-002 formalizes authority hierarchy.
ADR-003 formalizes callback convergence and anti-flash behavior.

This ADR formalizes providers as interchangeable implementation layers behind stable internal contracts, with strict switching discipline and failure-safe behavior.

## Decision
Blackwork formally adopts the Provider Switch Model.

Binding rules:
- Providers MUST be treated as replaceable implementation layers, not business authority layers.
- Provider-specific behavior MUST be mediated through stable internal contracts.
- Provider callbacks/webhooks MUST reconcile into local authoritative state and MUST NOT bypass it.
- Switching providers MUST NOT change authority hierarchy, invariants, or convergence doctrine.
- Provider adapters MUST be thin, deterministic, and replaceable.
- Provider outages or degraded responses MUST fail safely without corrupting local authority truth.
- Provider migration paths MUST preserve idempotency and monotonic convergence.

## Provider Definition & Scope
A provider is any external execution system integrated through Blackwork contracts, including but not limited to:
- payment execution providers,
- authentication/identity providers or brokers,
- marketing delivery/sync providers.

Provider scope:
- executes remote operations,
- returns remote signals/events,
- does not own local policy truth.

Provider is NOT:
- local authority for payment/order/auth/provisioning/consent truth,
- orchestration authority for checkout UX state,
- source of invariant definition.

## Contract Boundary Rules
- Every provider integration MUST expose explicit contract boundaries:
  - canonical inputs expected by local orchestration,
  - canonical outputs/events consumed by local reconciliation,
  - normalized error classes for deterministic handling.
- Provider adapters MUST translate provider-specific payloads into local canonical state transitions.
- Provider-specific UI components MUST NOT become orchestration authority.
- Provider callbacks MUST pass authenticity + idempotency gates before state mutation.
- Local authority reconciliation MUST be the only mutation entrypoint for business-critical state.
- Contract boundaries MUST isolate provider differences so higher layers remain provider-agnostic.

## Switching Discipline (Migration Rules)
Switching from provider A to provider B MUST follow controlled migration discipline:

1. Contract parity declaration
- Equivalent local contract coverage MUST be declared for required operations/events.

2. Data mapping definition
- Provider-specific identifiers/statuses MUST map to canonical local representations.
- Mapping MUST preserve historical traceability where required.

3. Callback/event mapping
- Callback/webhook semantics MUST be mapped to existing local reconciliation pathways.
- Duplicate/replay behavior MUST preserve existing idempotency guarantees.

4. State continuity guarantees
- Existing local authority state MUST remain valid across switch.
- In-flight operations MUST converge without authority regression.

5. Cutover safety
- Cutover MUST avoid dual-authority ambiguity.
- During migration windows, effective authority source per contract MUST be unambiguous.

6. Post-switch validation
- Tier 0 regression scope MUST confirm invariants unchanged.

## Failure Mode & Degrade-Safely Rules
- Provider down/timeouts MUST degrade without corrupting local authority truth.
- Delayed callbacks MUST reconcile deterministically when received.
- Partial sync/provider-side partial success MUST not create contradictory local terminal state.
- Transient provider errors MUST remain retry-safe and idempotent.
- Provider response anomalies MUST normalize to safe local error states.
- Commerce-critical authority flows MUST remain non-speculative under provider instability.
- No provider outage MAY promote UI state to authority.

## Regression Requirements (Tier 0)
Any provider switch, major provider adapter change, or callback contract change is Tier 0 and MUST include:

- authority hierarchy validation (ADR-002 compatibility),
- callback convergence and anti-flash validation (ADR-003 compatibility),
- idempotency/replay safety validation for callbacks and retries,
- deterministic orchestration validation where provider UI exists (ADR-001 compatibility),
- failure/degrade-safely validation under provider-down and delayed-event scenarios,
- migration mapping validation (status, identifiers, and terminal-state convergence).

No provider switch is complete until invariant-preserving regression evidence is recorded.

## Alternatives Considered

### 1) Provider-specific authority model
Rejected.
Would couple business truth to external implementations and violate local authority doctrine.

### 2) Direct provider payload usage without canonical contract layer
Rejected.
Increases drift, makes switching brittle, and breaks deterministic reconciliation.

### 3) Provider UI as orchestration authority
Rejected.
Conflicts with selector and local orchestration authority model.

### 4) Best-effort switching without formal migration mapping
Rejected.
Cannot guarantee idempotency, continuity, or invariant preservation.

## Consequences
- Provider replacement becomes structurally feasible without changing core authority doctrine.
- Integration complexity is isolated inside thin adapters and mapping layers.
- Callback/event handling remains deterministic during provider transitions.
- Outage handling is safer through enforced degrade-safely behavior.
- Governance review burden increases for provider switch events (Tier 0 by default).

## Invariants Protected
- Local authority doctrine remains primary; providers are execution layers only.
- Provider callbacks/webhooks MUST NOT bypass local reconciliation authority.
- Provider switches MUST preserve authority hierarchy and convergence invariants.
- Provider-specific UI MUST NOT become orchestration authority.
- Idempotency and monotonic state progression MUST survive provider migration.
- Failure/degraded provider behavior MUST NOT corrupt local business truth.
