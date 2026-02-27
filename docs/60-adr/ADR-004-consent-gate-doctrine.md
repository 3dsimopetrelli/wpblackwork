# ADR-004: Consent Gate Doctrine

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context
Blackwork integrates marketing activation flows (including Brevo synchronization) into commerce and account journeys.
Consent-related behavior intersects checkout, account surfaces, and post-order automation.

ADR-002 formalizes authority hierarchy (Payment > Authentication > Provisioning).
This ADR formalizes consent as a dedicated gating control layer for marketing operations.

Consent must be enforceable, auditable, and deterministic, while preserving authority boundaries:
- payment truth remains payment authority,
- identity/session remains authentication authority,
- entitlement activation remains provisioning authority.

Without explicit doctrine, systems can drift into invalid patterns:
- silent marketing activation without verifiable consent,
- consent state inferred from non-authoritative signals,
- consent withdrawal incorrectly altering commerce or entitlement truth,
- non-auditable consent transitions.

## Decision
Blackwork formally adopts the Consent Gate Doctrine.

Binding decision:
- Marketing communication MUST require explicit valid consent where legally required.
- Consent state MUST be stored deterministically and verifiably in local authority data.
- Consent is a gating control for marketing automation and MUST NOT be treated as payment or authentication authority.
- Provisioning flows MAY depend on consent only where explicitly declared by policy and implementation contract.
- Consent withdrawal MUST stop future marketing automation and MUST NOT revoke valid payment truth or valid entitlement already activated.
- All consent state transitions MUST be auditable.

## Consent Authority Scope
Consent authority scope is limited to communication and marketing activation eligibility.

Consent controls what the system MAY do for marketing channels (subscribe/sync/send), not what the system knows about payment or identity truth.

Authoritative consent surfaces MAY include:
- checkout consent capture controls (e.g., newsletter opt-in checkbox),
- account-level consent preference controls (if exposed),
- explicit compliant administrative correction paths.

Non-authoritative consent signals include:
- inferred behavior from page visits,
- payment success screens,
- redirect/query artifacts,
- remote provider status alone.

Consent MUST be evaluated from local authoritative consent data, not inferred UI state.

## Cross-Layer Interaction Rules
- Consent MUST NOT confirm, deny, or mutate payment authority.
- Consent MUST NOT confirm, deny, or mutate authentication authority.
- Consent MUST NOT directly mutate provisioning authority unless a provisioning contract explicitly requires consent and documents the dependency.
- Payment success MUST NOT imply consent.
- Authentication success MUST NOT imply consent.
- Provisioning completion MUST NOT imply consent.
- Consent granted MAY enable marketing automation only through explicit gated execution paths.
- Consent denied or missing MUST hard-stop write-side marketing operations.

When cross-layer conflict occurs, ADR-002 authority order prevails.

## Consent Storage & Audit Requirements
Consent storage and audit records MUST satisfy the following:

1. Deterministic local storage
- Consent state MUST be stored locally in canonical metadata/state keys.
- Stored state MUST include at least:
  - consent decision (granted/denied/withdrawn as applicable),
  - consent timestamp,
  - consent source/surface.

2. Verifiability
- Consent evidence MUST be sufficient to prove whether a write-side marketing action was allowed.
- Write-side marketing operations MUST check consent gate prior to remote API calls.

3. Auditability
- Every consent state transition MUST leave a local traceable record.
- Marketing write attempts MUST be traceable with outcome class (executed/skipped/error) and reason.

4. Local authority doctrine
- Local consent metadata is source-of-truth for eligibility.
- Remote provider state MAY be observed but MUST NOT redefine local consent truth.

## Withdrawal Rules
- Consent withdrawal MUST immediately block future marketing automation actions.
- Withdrawal MUST NOT alter confirmed payment outcomes.
- Withdrawal MUST NOT alter authenticated identity/session truth.
- Withdrawal MUST NOT revoke already valid entitlements activated under valid upstream authority.
- Withdrawal events MUST be auditable with timestamp/source and reflected in local authority state.
- Subsequent retries or automated marketing jobs MUST converge to non-send/non-subscribe behavior unless new valid consent is captured.

## Alternatives Considered

### 1) Consent as commerce authority
Rejected.
Would allow marketing state to alter payment/order truth and violates authority hierarchy.

### 2) Implicit consent from checkout/payment completion
Rejected.
Legally and architecturally unsafe; payment completion is not consent evidence.

### 3) Remote-provider consent as sole authority
Rejected.
Violates local authority doctrine and breaks deterministic gating/audit requirements.

### 4) Best-effort consent without audit trail
Rejected.
Insufficient for governance, compliance evidence, and deterministic control.

## Consequences
- Marketing activation becomes deterministic, auditable, and legally safer.
- Consent is enforced as a hard gate for write-side marketing flows.
- Payment/auth/provisioning authority boundaries remain intact.
- Withdrawal behavior becomes predictable and non-destructive to commerce truth.
- Cross-domain changes touching consent gate become Tier 0-sensitive and require regression validation.

## Invariants Protected
- No write-side marketing action MAY execute without valid consent where required.
- Consent state MUST be locally stored, verifiable, and auditable.
- Consent MUST NOT override payment, authentication, or core provisioning authority.
- Payment/auth/provisioning truth MUST remain independent from marketing consent state.
- Consent withdrawal MUST stop future marketing automation and MUST NOT retroactively invalidate valid payment or entitlement truth.
- Consent transitions and marketing outcomes MUST converge deterministically under retries and duplicate triggers.
