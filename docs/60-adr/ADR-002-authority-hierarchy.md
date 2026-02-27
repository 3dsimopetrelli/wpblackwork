# ADR-002: Authority Hierarchy (Payment/Auth/Provisioning)

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

Blackwork runtime flows cross multiple domains: checkout execution, cart transitions, provider confirmation, authentication/session continuity, provisioning/claim, and entitlement activation.

Architecture maps define these domains structurally. ADR-001 formalizes selector authority for checkout orchestration. This ADR formalizes the global top-down authority hierarchy to eliminate ambiguity and prevent circular authority assumptions between payment, identity, and provisioning layers.

Cart state, checkout UI, success screens, and frontend fragments are operational or presentation layers. They are NOT authority surfaces.

Without a strict hierarchy, the system can drift into invalid patterns:

- UI success states treated as payment truth
- Authentication state interpreted as payment confirmation
- Provisioning activation without validated payment and validated identity
- Downstream layers mutating upstream authority
- Circular override loops between domains

This ADR eliminates those failure modes.

## Decision

Blackwork formally adopts a strict three-layer authority hierarchy:

1. Payment Authority (highest)
2. Authentication Authority (middle)
3. Provisioning Authority (lower)

All cross-domain runtime decisions MUST respect this order.

Lower layers MAY read upstream authority.
Lower layers MUST NOT mutate, reinterpret, or override upstream authority.

Authority precedence is strictly acyclic and MUST remain:

Payment > Authentication > Provisioning

## Authority Layers (Explicit Definitions)

### 1) Payment Authority

Payment authority is established exclusively by provider-confirmed payment outcome and authoritative local order-state convergence.

Payment Authority MUST include:
- Provider confirmation channels (webhook/callback with trust validation)
- Deterministic, idempotent local order-state mapping
- Convergence under retries or duplicate events

Payment Authority MUST NOT be inferred from:
- Frontend success screens
- Client-side UI state
- Pre-confirmation redirects
- Cart or checkout rendering state

Payment truth exists independently of UI surfaces.

### 2) Authentication Authority

Authentication authority is established by validated identity, session continuity, and token trust boundaries.

Authentication Authority MUST include:
- Verified identity/session validity
- Authenticated principal continuity
- Token and session trust enforcement

Authentication Authority MUST NOT:
- Override payment truth
- Rewrite payment/order lifecycle outcomes
- Imply entitlement activation without validated provisioning rules

Authentication state is a prerequisite for identity-dependent transitions but not a commerce authority layer.

### 3) Provisioning Authority

Provisioning authority governs claim eligibility, entitlement activation, and access-state transitions.

Provisioning Authority MUST include:
- Claim eligibility validation
- Entitlement activation only after upstream predicates are satisfied
- Repeat-safe, idempotent convergence for claim flows

Provisioning Authority MUST NOT:
- Activate access without validated payment and validated identity
- Alter payment/order authority
- Infer identity validity from UI state alone

Provisioning is a downstream consequence layer, never an upstream authority layer.

## Non-Authority Surfaces

The following are explicitly non-authoritative:

- Cart state
- Checkout rendering
- Payment success screens
- Fragment refresh cycles
- Frontend UI indicators

These surfaces MAY reflect authority but MUST NOT create or redefine it.

## Cross-Layer Rules

- UI state is never authority.
- Payment confirmation CANNOT be inferred from frontend surfaces.
- Authentication MUST NOT override payment truth.
- Provisioning MUST NOT activate without validated payment AND validated identity.
- Lower layers MUST NOT supersede higher authority layers.
- Cross-layer reads are allowed; cross-layer authority takeover is prohibited.
- Callback and webhook handlers MUST remain idempotent and converge to a single stable state.
- In any conflict, higher-layer authority MUST prevail.

## Alternatives Considered

### 1) Flat authority model
Rejected.
Creates circular authority and non-deterministic state resolution.

### 2) Auth-first model
Rejected.
Violates payment truth boundaries and enables premature entitlement activation.

### 3) Provisioning-led model
Rejected.
Inverts trust hierarchy and allows downstream layers to redefine commerce or identity truth.

### 4) Soft or undocumented precedence
Rejected.
Insufficient for governance-grade enforcement and prone to architectural drift.

## Consequences

- Cross-domain decisions become deterministic.
- Cart and Checkout can be refactored safely without affecting payment truth.
- Claim flows are structurally gated by validated upstream authority.
- Tier 0 intersections (payment/auth/provisioning) require explicit hierarchy validation during regression.
- ADR-001 remains aligned: checkout orchestration authority does not redefine payment truth.

## Invariants Protected

- Payment truth MUST originate from provider confirmation + authoritative local order mapping.
- Authentication continuity MUST be validated before identity-dependent transitions.
- Provisioning activation MUST require valid payment truth AND valid identity.
- No UI surface can create, upgrade, downgrade, or override authority state.
- Repeated callbacks/retries MUST converge and MUST NOT create cross-layer divergence.
- Authority precedence MUST remain strictly acyclic: Payment > Authentication > Provisioning.
