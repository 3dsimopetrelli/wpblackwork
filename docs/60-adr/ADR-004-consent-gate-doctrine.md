# ADR-004: Consent Gate Doctrine

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork integrates marketing activation flows (including Brevo synchronization) into commerce and account journeys.

ADR-002 formalizes authority hierarchy (Payment > Authentication > Provisioning).

This ADR formalizes consent as a dedicated **control gate layer** governing marketing automation eligibility.

Consent is NOT an authority layer within the hierarchy.
Consent does NOT sit above, below, or between Payment/Auth/Provisioning.
Consent is a gating control mechanism applied to marketing write-side operations only.

Without explicit doctrine, systems may drift into:

- Silent marketing activation without verifiable consent
- Consent inferred from non-authoritative UI or commerce signals
- Consent withdrawal incorrectly mutating commerce or entitlement truth
- Non-auditable consent transitions
- Remote provider state redefining local consent truth

This ADR eliminates those failure modes.

## Decision

Blackwork formally adopts the Consent Gate Doctrine.

Binding rules:

- Marketing communication MUST require explicit valid consent where legally required.
- Consent state MUST be stored deterministically and verifiably in local authoritative data.
- Consent is a control gate for marketing automation and MUST NOT be treated as payment, authentication, or provisioning authority.
- Provisioning flows MAY depend on consent only where explicitly declared and documented.
- Consent withdrawal MUST stop future marketing automation.
- Consent withdrawal MUST NOT revoke confirmed payment truth.
- Consent withdrawal MUST NOT revoke valid entitlements already activated.
- Consent withdrawal MUST NOT trigger rollback logic in provisioning systems.
- All consent state transitions MUST be auditable.

## Consent Authority Scope

Consent controls what marketing write-side operations MAY execute.

Consent does NOT determine:

- Payment confirmation
- Identity/session truth
- Entitlement validity

Authoritative consent capture surfaces MAY include:

- Checkout opt-in controls
- Account preference controls
- Explicit administrative correction paths

Non-authoritative signals include:

- Page visits
- Success screens
- Redirect/query parameters
- Payment completion
- Remote provider subscription status alone

Consent MUST be evaluated from reconciled local consent state only.

## Cross-Layer Interaction Rules

- Consent MUST NOT confirm, deny, or mutate payment authority.
- Consent MUST NOT confirm, deny, or mutate authentication authority.
- Consent MUST NOT mutate provisioning authority unless explicitly declared by documented contract.
- Payment success MUST NOT imply consent.
- Authentication success MUST NOT imply consent.
- Provisioning completion MUST NOT imply consent.
- Consent granted MAY enable marketing automation only through explicit gated execution paths.
- Consent denied or missing MUST hard-stop write-side marketing operations.

In any cross-layer conflict, ADR-002 authority precedence prevails.

## Consent Storage & Audit Requirements

### 1. Deterministic Local Storage
Consent MUST be stored locally in canonical metadata/state keys.

Stored state MUST include:
- Consent decision (granted/denied/withdrawn)
- Timestamp
- Source/surface of capture
- Version or policy reference where applicable

### 2. Verifiability
Marketing write operations MUST check consent gate before remote API calls.
It MUST be possible to demonstrate why a marketing action executed or was skipped.

### 3. Auditability
Every consent state transition MUST leave a traceable local record.
Marketing write attempts MUST be logged with outcome classification.

### 4. Local Authority Doctrine
Local consent metadata is source-of-truth.
Remote provider state MAY be synchronized but MUST NOT redefine local consent truth.

## Withdrawal Rules

- Withdrawal MUST immediately block future marketing write-side operations.
- Withdrawal MUST NOT alter confirmed payment outcomes.
- Withdrawal MUST NOT alter authenticated identity/session truth.
- Withdrawal MUST NOT revoke valid entitlements already activated.
- Withdrawal MUST NOT trigger rollback logic in provisioning systems.
- Withdrawal events MUST be auditable.
- Retry or scheduled automation MUST converge to non-send behavior unless new valid consent is captured.

## Alternatives Considered

### 1) Consent as commerce authority
Rejected.
Violates authority hierarchy and creates cross-layer mutation risk.

### 2) Implicit consent from payment completion
Rejected.
Legally unsafe and architecturally invalid.

### 3) Remote-provider consent as sole authority
Rejected.
Breaks deterministic gating and audit requirements.

### 4) Soft consent without audit trail
Rejected.
Insufficient for governance-grade enforcement.

## Consequences

- Marketing activation becomes deterministic and auditable.
- Consent becomes a hard gate for marketing write-side flows.
- Payment/auth/provisioning boundaries remain intact.
- Withdrawal behavior is predictable and non-destructive to commerce truth.
- Consent changes touching automation surfaces become Tier 0-sensitive.

## Invariants Protected

- No marketing write-side action MAY execute without valid consent where required.
- Consent state MUST be locally stored, verifiable, and auditable.
- Consent MUST NOT override payment, authentication, or provisioning authority.
- Payment/auth/provisioning truth MUST remain independent of consent state.
- Withdrawal MUST stop future marketing automation without retroactive commerce mutation.
- Consent transitions and automation outcomes MUST converge deterministically under retries or duplicate triggers.
