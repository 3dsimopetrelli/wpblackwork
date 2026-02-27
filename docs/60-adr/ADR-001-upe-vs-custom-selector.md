# ADR-001: UPE vs Custom Selector Strategy

## Status
Accepted

This decision is binding and may only be altered through a superseding ADR.

## Context

The checkout payment surface combines two layers:

- Stripe UPE components (provider-driven UI/components).
- Blackwork custom selector (`woocommerce/templates/checkout/payment.php` + `assets/js/bw-payment-methods.js`) responsible for orchestration of visible payment state, fallback behavior, and submit-path determinism.

Architecture maps and governance artifacts identify this surface as Tier 0 with high blast radius. Payment truth, UI selection state, wallet visibility, fragment refresh behavior, and submit orchestration converge at this boundary.

Without explicit authority assignment, mixed rendering between UPE and custom selector can produce:

- Duplicated or conflicting payment controls
- Mismatch between visible selection and submitted gateway
- Non-deterministic checkout behavior after fragment refresh
- Instability in fallback and multi-gateway scenarios

## Decision

Blackwork formally establishes the following authority boundary:

- The **Custom Selector** is the sole orchestration authority for checkout payment selection and actionable visible state.
- **Stripe UPE is a provider component layer and MUST NOT assume orchestration authority.**
- UPE-rendered elements MUST remain compliant with selector contract and MUST NOT override selector-selected submission state.

Authoritative submission state is bound to:

- Active radio/payment method selection
- Synchronized UI visibility
- Deterministic submit payload coherence

No provider integration may supersede selector authority without a superseding ADR.

This decision aligns with the Authority Hierarchy doctrine (ADR-002).

## Alternatives Considered

### 1. UPE as full orchestration authority
Rejected.
Conflicts with current multi-gateway architecture and selector-based orchestration model. Would destabilize fallback logic and fragment refresh determinism.

### 2. Dual authority (UPE + Custom Selector)
Rejected.
Creates ambiguous source-of-truth and increases race-condition risk during fragment refresh and re-render cycles.

### 3. Coexistence without explicit authority definition
Rejected.
Fails governance-grade clarity and allows architectural drift.

## Consequences

- Payment UI governance remains deterministic under selector control.
- UPE integration work is compatibility-scoped, not authority-expanding.
- Suppression/cleanup layers preventing duplicate controls remain valid architecture.
- Any change affecting selector/UPE coupling is classified Tier 0 high-risk.
- Mandatory regression journey validation is required for any modification at this boundary.

## Blast Radius Impact

This decision directly impacts:

- Checkout payment rendering
- Cart → Checkout transition
- Fragment refresh cycles
- Wallet visibility layers
- Gateway fallback logic
- Submit orchestration determinism

## Invariants Protected

- Visible payment selection MUST match submitted gateway.
- Provider components MUST NOT override checkout orchestration authority.
- UI integration failures MUST NOT alter payment/order authority boundaries.
- Payment truth remains provider confirmation + local order mapping (never UI state).
- Re-render cycles MUST converge to a single stable actionable method state.
