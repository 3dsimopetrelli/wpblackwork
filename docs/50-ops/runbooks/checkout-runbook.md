# Checkout Runbook

## 1. Domain Scope
Includes checkout templates, checkout UI behavior, coupon flow, order review interactions, and checkout-specific maintenance guidance.

Related folders:
- `docs/30-features/checkout/`
- `docs/40-integrations/payments/`

Related docs:
- `../../30-features/checkout/complete-guide.md`
- `../../30-features/checkout/maintenance-guide.md`
- `../../40-integrations/payments/payments-overview.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Checkout rendering break due to template/CSS coupling.
- Coupon and totals desynchronization after AJAX updates.
- Payment section state drift (selected method vs visible action).
- Layout regressions at responsive breakpoints.

High-risk integrations and dependencies:
- WooCommerce checkout lifecycle (`updated_checkout`, notices, totals).
- Payments orchestration and gateway UI sync.
- Cart popup interactions that affect cart/checkout continuity.

## 3. Pre-Maintenance Checklist
- Read checkout guides and payments overview first.
- Identify fragile areas: coupon logic, totals, payment accordion, responsive layout.
- Map related integrations: payments gateways, auth handoff after purchase, analytics/event hooks if present.

## 4. Safe Fix Protocol
- Apply minimal, domain-scoped changes.
- Preserve documented checkout contract and WooCommerce flow.
- Do not alter payment completion semantics without payments review.
- Do not rewrite template structure broadly without regression plan.
- ADR required if checkout architecture/contracts are changed.

## 5. Regression Checklist (Domain Specific)
- Validate desktop/mobile checkout rendering.
- Validate billing/shipping input behavior and validation notices.
- Validate coupon apply/remove and totals recalculation.
- Validate payment method selection and CTA visibility consistency.
- Validate place-order and redirect/thank-you path.
- Scan browser console for JS errors/warnings on checkout interactions.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for every maintenance change affecting behavior.
- Update checkout domain docs when behavior, constraints, or troubleshooting notes change.
- Update ADR when checkout architecture or contracts are modified.
