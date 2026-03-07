# Checkout Runtime Wallet Ownership

## Purpose
Define wallet runtime authority boundaries in Blackwork checkout when WooCommerce Stripe / WCPay express runtime is present.

## Architecture Boundary

### Native Stripe/WCPay express runtime
- Owns wallet launcher mount lifecycle.
- Owns secure wallet popup/handoff flow.
- Owns internal express container lifecycle and launch orchestration.

### Blackwork runtime
- Owns checkout selector orchestration and integration layering around payment method UI.
- Must not own direct wallet launch when Stripe/WCPay already owns it.
- Must not create parallel custom PaymentRequest launch ownership in this setup.

## Why custom launchers were unsupported
- Investigation confirmed custom Google Pay / Apple Pay launchers were parallel to native Stripe/WCPay ownership.
- Parallel launch paths were non-authoritative and produced nondeterministic behavior.
- No stable governance-safe runtime bridge was found for externally forcing native launcher flow.

## Governance-safe outcome
- Disable custom wallet launchers when Stripe/WCPay runtime owns wallet lifecycle.
- Enforce clean disabled semantics:
  - no render
  - no enqueue
  - no init
  - no dead DOM residue
- Preserve deterministic checkout behavior by maintaining a single wallet launch authority.

## Ownership Rule (Normative)
When Stripe/WCPay express runtime is active for wallet launch:
- Blackwork must not implement parallel custom wallet launchers.
- Blackwork must integrate only through authoritative Stripe/WCPay integration points.

## Implementation State Reference
- Toggle authority:
  - `bw_google_pay_enabled`
  - `bw_apple_pay_enabled`
- Enqueue gating:
  - `woocommerce/woocommerce-init.php`
- Gateway availability:
  - `includes/Gateways/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
- Template residue cleanup:
  - `woocommerce/templates/checkout/payment.php`

## Future Integration Constraints
- Do not add custom wallet launcher runtime that competes with Stripe/WCPay ownership.
- Any wallet enhancement must preserve:
  - single launch authority
  - deterministic runtime ownership
  - clean disable semantics
  - no dead selector/launcher surfaces
