# ADR-002: Authority Hierarchy (Payment/Auth/Provisioning)

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context
Blackwork runtime flows cross multiple domains: checkout execution, provider confirmation, authentication/session continuity, provisioning/claim, and entitlement activation.

Prior architecture maps define these boundaries implicitly, and ADR-001 already formalizes selector authority for checkout payment orchestration.
This ADR formalizes the global top-down authority hierarchy to remove ambiguity and prevent circular authority assumptions between payment, identity, and provisioning layers.

Without a strict hierarchy, the system can drift into invalid patterns:
- UI success states treated as payment truth,
- authentication state interpreted as payment confirmation,
- provisioning activation without validated payment and validated identity,
- cross-layer override loops where downstream layers mutate upstream authority.

## Decision
Blackwork formally adopts a strict three-layer authority hierarchy:

1. Payment Authority (highest)
2. Authentication Authority (middle)
3. Provisioning Authority (lower)

All runtime decisions that cross these domains MUST respect this order.
A lower layer MUST NOT supersede, reinterpret, or rewrite authority owned by a higher layer.

## Authority Layers (Explicit Definitions)

### 1) Payment Authority
Payment authority is established exclusively by provider-confirmed payment outcome and authoritative local order-state mapping.

Payment Authority MUST include:
- provider confirmation channels (webhook/callback validated by trust checks),
- deterministic local order-state convergence.

Payment Authority MUST NOT be inferred from:
- frontend success screens,
- client-side UI state,
- pre-confirmation redirects.

### 2) Authentication Authority
Authentication authority is established by validated identity/session continuity and token trust boundaries.

Authentication Authority MUST include:
- identity/session validity,
- authenticated principal continuity,
- token/session trust enforcement.

Authentication Authority MUST NOT:
- override payment truth,
- rewrite payment/order lifecycle outcomes,
- imply entitlement activation without provisioning rules.

### 3) Provisioning Authority
Provisioning authority governs claim eligibility, entitlement activation, and access state transitions.

Provisioning Authority MUST include:
- claim eligibility validation,
- entitlement activation only after required upstream predicates,
- repeat-safe convergence for claim/activation flows.

Provisioning Authority MUST NOT:
- activate access without validated payment and validated identity,
- alter payment/order authority,
- infer identity validity from UI state alone.

## Cross-Layer Rules
- UI state is never authority.
- Payment confirmation CANNOT be inferred from frontend success surfaces.
- Authentication state MUST NOT override payment truth.
- Provisioning MUST NOT activate without validated payment and validated identity.
- Lower layers MUST NOT supersede higher authority layers.
- Cross-layer reads are allowed; cross-layer authority takeover is prohibited.
- Callback and webhook handlers MUST remain idempotent and converge to a stable state.
- Any conflict between layers MUST resolve in favor of higher-layer authority.

## Alternatives Considered

### 1) Flat authority model (all domains can finalize state)
Rejected.
Creates circular authority and non-deterministic conflict resolution across payment, auth, and provisioning.

### 2) Auth-first model (identity can finalize commerce transitions)
Rejected.
Violates payment truth boundaries and introduces risk of entitlement activation without provider-confirmed payment.

### 3) Provisioning-led model (claim state drives upstream truth)
Rejected.
Inverts trust boundaries and allows downstream side effects to redefine commerce or identity authority.

### 4) Soft, undocumented precedence
Rejected.
Insufficient for governance-grade enforcement and prone to drift under iterative changes.

## Consequences
- Cross-domain decisions become deterministic under a single top-down authority doctrine.
- Checkout/auth/provisioning coupling can be evolved without collapsing source-of-truth boundaries.
- Callback, claim, and post-order flows are constrained by explicit authority gates.
- Tier 0 changes in payment/auth/provisioning intersections require explicit hierarchy validation during regression.
- ADR-001 remains compatible and anchored: selector authority governs checkout orchestration, but cannot redefine payment truth.

## Invariants Protected
- Payment truth MUST originate from provider confirmation + local authoritative order mapping.
- Authentication continuity MUST be validated before identity-dependent account/provisioning transitions.
- Provisioning and entitlement activation MUST require valid payment truth and valid identity.
- No UI surface can create, upgrade, or override authority state.
- Repeated callbacks/retries MUST converge and MUST NOT create cross-layer divergence.
- Authority precedence MUST remain acyclic: Payment > Auth > Provisioning.
