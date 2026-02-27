# Blackwork Core — Domain Map

## 1. Purpose
This document defines Blackwork Core system topology, authority surfaces, and domain boundaries.

This map MUST be used to determine:
- which domain owns truth,
- which domain may mutate state,
- which domain may only signal or render,
- which cross-domain overrides are prohibited.

This document is architectural and normative. It MUST NOT be used as roadmap content.

## 2. System Topology Overview
Text-based layered topology:

- **Layer 0: Authority Domains (Tier 0)**
  - Redirect / Routing Authority
  - Import Products Authority
  - Payment confirmation and order-state convergence surfaces
  - Auth/session authority convergence surfaces
- **Layer 1: Orchestration Domains (Tier 1)**
  - Checkout Orchestration
  - Cart Interaction orchestration
  - Consent/Brevo orchestration
  - Supabase/Auth integration orchestration
  - Runtime hook/event coordination
- **Layer 2: Presentation Domains (Tier 2)**
  - Header / Global Layout presentation
  - UI overlays, loaders, and view composition layers
  - Non-authoritative visual reflection surfaces

Layering rules:
- Lower layers MUST NOT override higher-layer authority.
- Presentation layers MUST NOT become authority surfaces.
- Orchestration layers MAY coordinate but MUST NOT redefine truth ownership.

## 3. Domain Inventory

### 3.1 Redirect / Routing Authority
- Domain Name: Redirect / Routing Authority
- Tier: 0
- Primary Responsibility: Deterministic route redirection under protected-route policy.
- Authority Level: Defines Truth
- Owns: Redirect rule resolution and routing decision surface.
- Reads From: Persisted redirect rules and runtime request path/query.
- Cannot Override:
  - Protected commerce routes (`/cart`, `/checkout`, `/my-account` and protected prefixes)
  - Admin/platform critical routes
- Related Specs / ADR references:
  - `docs/30-features/redirect/redirect-engine-v2-spec.md`
  - `docs/50-ops/runtime-hook-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`

### 3.2 Import Products Authority
- Domain Name: Import Products Authority
- Tier: 0
- Primary Responsibility: Deterministic product identity and idempotent product import convergence.
- Authority Level: Defines Truth
- Owns: Import identity contract and Woo product write authority for import flow.
- Reads From: Structured import input and canonical SKU identity.
- Cannot Override:
  - Runtime cart state
  - Checkout/payment runtime behavior
  - Presentation-layer state
- Related Specs / ADR references:
  - `docs/30-features/import-products/import-products-vnext-spec.md`
  - `docs/00-planning/decision-log.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`

### 3.3 Checkout Orchestration
- Domain Name: Checkout Orchestration
- Tier: 1
- Primary Responsibility: Coordinate checkout runtime flow, render orchestration, and submission consistency.
- Authority Level: Mutates State
- Owns: Checkout runtime orchestration surface and checkout-specific recomputation lifecycle.
- Reads From: Cart state, payment availability, integration readiness.
- Cannot Override:
  - Payment confirmation truth
  - Redirect authority
  - Consent authority
- Related Specs / ADR references:
  - `docs/30-features/checkout/checkout-architecture-map.md`
  - `docs/40-integrations/cart-checkout-responsibility-matrix.md`
  - `docs/60-adr/ADR-001-upe-vs-custom-selector.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`

### 3.4 Payment Provider Integration
- Domain Name: Payment Provider Integration
- Tier: 1
- Primary Responsibility: Execute provider payment flows and reconcile provider outcomes to local order state.
- Authority Level: Defines Truth
- Owns: Payment outcome confirmation/reconciliation contract and provider callback processing.
- Reads From: Checkout intent, gateway configuration, provider callbacks/webhooks.
- Cannot Override:
  - Redirect authority policy
  - Auth/session authority boundaries
  - Consent authority boundaries
- Related Specs / ADR references:
  - `docs/40-integrations/payments/payments-architecture-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`
  - `docs/60-adr/ADR-006-provider-switch-model.md`

### 3.5 Cart Interaction (Cart Popup + Cart Page fallback)
- Domain Name: Cart Interaction
- Tier: 1
- Primary Responsibility: Operate cart interaction UX over canonical Woo cart state.
- Authority Level: Mutates State
- Owns: Cart interaction lifecycle and progressive enhancement behavior.
- Reads From: Canonical Woo cart session and fragment lifecycle events.
- Cannot Override:
  - Payment truth
  - Order truth
  - Redirect authority
- Related Specs / ADR references:
  - `docs/30-features/cart-popup/cart-popup-module-spec.md`
  - `docs/40-integrations/cart-checkout-responsibility-matrix.md`
  - `docs/60-adr/ADR-001-upe-vs-custom-selector.md`

### 3.6 Header / Global Layout
- Domain Name: Header / Global Layout
- Tier: 2
- Primary Responsibility: Global layout rendering, navigation presentation, and non-authoritative runtime UI state.
- Authority Level: Presentation Only
- Owns: Header render surface and responsive/scroll presentation state machine.
- Reads From: Runtime context, menu configuration, read-only cart indicators.
- Cannot Override:
  - Routing decisions
  - Payment/order/provisioning/consent authority
  - Checkout/cart business truth
- Related Specs / ADR references:
  - `docs/30-features/header/header-module-spec.md`
  - `docs/30-features/header/header-scroll-state-machine-spec.md`
  - `docs/30-features/header/header-responsive-contract.md`
  - `docs/30-features/header/header-admin-settings-map.md`

### 3.7 Consent / Brevo Sync
- Domain Name: Consent / Brevo Sync
- Tier: 1
- Primary Responsibility: Enforce consent gate and execute marketing sync as downstream non-blocking flow.
- Authority Level: Mutates State
- Owns: Consent-gated marketing write operations and local consent-sync state.
- Reads From: Local consent metadata and order lifecycle triggers.
- Cannot Override:
  - Payment truth
  - Order lifecycle authority
  - Auth/provisioning authority
- Related Specs / ADR references:
  - `docs/40-integrations/brevo/brevo-architecture-map.md`
  - `docs/60-adr/ADR-004-consent-gate-doctrine.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`

### 3.8 Supabase/Auth Sync
- Domain Name: Supabase/Auth Sync
- Tier: 1
- Primary Responsibility: Coordinate identity/session bridge and onboarding/provisioning convergence with local authority constraints.
- Authority Level: Mutates State
- Owns: Auth callback/session bridge and onboarding claim convergence path.
- Reads From: Auth provider outputs, local session state, order-linked provisioning signals.
- Cannot Override:
  - Payment confirmation truth
  - Routing authority policy
  - Consent authority boundaries
- Related Specs / ADR references:
  - `docs/40-integrations/auth/auth-architecture-map.md`
  - `docs/40-integrations/supabase/supabase-architecture-map.md`
  - `docs/60-adr/ADR-002-authority-hierarchy.md`
  - `docs/60-adr/ADR-005-claim-idempotency-rule.md`

### 3.9 Shared Runtime Utilities (fragments, loaders, JS bridges)
- Domain Name: Shared Runtime Utilities
- Tier: 2
- Primary Responsibility: Runtime event propagation, UI sync triggers, and non-authoritative glue behavior.
- Authority Level: Signals Only
- Owns: Lifecycle signaling surfaces (fragments, `updated_checkout`, loader/bridge utilities).
- Reads From: Domain state snapshots and frontend lifecycle events.
- Cannot Override:
  - Any domain authority truth
  - Payment/order/auth/consent routing decisions
- Related Specs / ADR references:
  - `docs/50-ops/runtime-hook-map.md`
  - `docs/50-ops/regression-coverage-map.md`
  - `docs/60-adr/ADR-003-callback-anti-flash-model.md`

## 4. Cross-Domain Interaction Model
Cross-domain interaction rules:
- Redirect domain MUST NOT override protected Checkout routes.
- Import domain MUST NOT mutate runtime Cart state.
- Header domain MUST NOT define business logic or authority truth.
- Cart Popup domain MUST NOT define payment truth.
- Payment domain MUST NOT override routing authority.
- Consent/Brevo domain MUST NOT alter payment or order authority.
- Supabase/Auth domain MUST NOT redefine payment confirmation truth.

Interaction discipline:
- Domains MAY read required upstream state when explicitly needed.
- Domains MUST NOT mutate authority outside owned boundaries.
- Presentation and utility domains MUST remain non-authoritative.

## 5. Authority Hierarchy Enforcement
Authority hierarchy is governed by ADR-002.

Enforcement rules:
- Higher-tier authority surfaces MUST NOT be overridden by lower-tier domains.
- Lower-tier orchestration/presentation layers MAY coordinate or reflect state but MUST NOT redefine truth.
- In conflicts, declared authority owner MUST prevail.

Reference:
- `docs/60-adr/ADR-002-authority-hierarchy.md`

## 6. Change Governance
Change governance by tier:
- Tier 0 changes REQUIRE ADR and explicit governance traceability.
- Tier 1 changes REQUIRE regression validation against affected domain contracts.
- Tier 2 changes are UX-scoped and MUST remain non-authoritative.

Global governance constraints:
- Any change that shifts authority boundaries MUST be documented as architecture decision.
- Any change that modifies runtime hook priority on Tier 0 surfaces MUST be treated as behavior-contract modification.
- Documentation MUST be updated when domain boundaries or authority ownership change.
