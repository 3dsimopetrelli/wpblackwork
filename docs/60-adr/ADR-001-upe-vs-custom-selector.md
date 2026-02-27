# ADR-001: UPE vs Custom Selector Strategy

## Status
Accepted

## Context
The checkout payment experience combines two layers:
- Stripe UPE components (provider-driven UI/components).
- Blackwork custom selector (`woocommerce/templates/checkout/payment.php` + `assets/js/bw-payment-methods.js`) used to orchestrate visible payment state, fallback behavior, and submit-path determinism.

The architecture maps and governance artifacts identify this surface as high blast radius because payment truth, UI selection state, wallet visibility, and submit behavior converge here.

Without a clear authority boundary, mixed rendering between UPE and custom selector can cause:
- duplicated or conflicting payment controls,
- mismatch between visible selection and submitted gateway,
- non-deterministic checkout behavior after fragment refresh.

## Decision
Blackwork formally adopts the following boundary:

- The **Custom Selector** is the orchestration authority for checkout payment selection and visible actionable state.
- **Stripe UPE** is treated as a provider component layer, not as orchestration authority.
- Any UPE-rendered surface must remain compatible with selector contract and must not override selector-selected submission state.

Operationally, the authoritative submission path remains bound to selector contract:
- active radio/payment method state,
- synchronized UI visibility,
- submit payload coherence.

## Alternatives Considered
1. UPE as full orchestration authority.
- Rejected: conflicts with current checkout architecture and custom multi-gateway orchestration model; introduces instability with existing selector-dependent logic and fallback rules.

2. Dual authority (UPE + custom selector both authoritative).
- Rejected: creates ambiguous source-of-truth, increases race/conflict risk on fragment refresh, and weakens determinism guarantees.

3. Incremental coexistence without explicit authority assignment.
- Rejected: does not provide governance-grade constraints and leaves regressions likely on Tier 0 surfaces.

## Consequences
- Payment UI governance remains deterministic under custom selector control.
- UPE integration work must be compatibility-scoped, not authority-expanding.
- Cleanup/suppression layers that prevent duplicated controls remain valid architectural mechanisms.
- Any change touching selector/UPE coupling is treated as high-risk and must pass Tier 0 regression discipline.

## Invariants Protected
- Payment selector determinism: visible selection must match effective submission method.
- Authority hierarchy: provider components cannot supersede checkout orchestration authority.
- Non-blocking commerce: UI integration issues must not alter payment/order authority boundaries.
- Callback/webhook discipline: payment truth remains provider confirmation + local order mapping, not UI state.
- Convergence under refresh/re-entry: repeated renders/fragment updates must converge to one stable actionable method state.
