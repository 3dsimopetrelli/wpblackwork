# Payments Runbook

## 1. Domain Scope
Includes gateway orchestration, payment UI state, Stripe-based flows, webhook-driven completion, and custom gateway behavior.

Related folders:
- `docs/40-integrations/payments/`
- `docs/30-features/checkout/`

Related docs:
- `../../40-integrations/payments/payments-overview.md`
- `../../40-integrations/payments/gateway-google-pay-guide.md`
- `../../40-integrations/payments/gateway-apple-pay-guide.md`
- `../../40-integrations/payments/gateway-klarna-guide.md`
- `../../40-integrations/payments/payment-test-checklist.md`
- `../regression-protocol.md`

## 2. Critical Risk Points
- Stripe webhook mismatch or missed events causing order state drift.
- Express checkout controls not aligned with selected method.
- Fallback logic failure when wallet method is unavailable.
- Cross-gateway side effects due to weak ownership checks.

High-risk integrations and dependencies:
- Stripe PaymentIntent and webhook contracts.
- WooCommerce order status transitions.
- Frontend method orchestration scripts.

## 3. Pre-Maintenance Checklist
- Read payments overview and gateway-specific docs.
- Verify current webhook assumptions and idempotency rules.
- Identify fragile areas: method ownership checks, fallback paths, return vs webhook timing.

## 4. Safe Fix Protocol
- Preserve webhook-first payment completion model.
- Keep gateway boundaries strict; avoid cross-mutation of orders.
- Keep fallback behavior explicit and testable.
- Do not change gateway state machine without explicit review.
- ADR required when payment architecture, webhook strategy, or status model changes.

## 5. Regression Checklist (Domain Specific)
- Test successful flow for each enabled custom gateway.
- Test failed/cancelled flow and retry behavior.
- Test express checkout logic visibility and action sync.
- Test gateway fallback logic when wallet method is unavailable.
- Confirm webhook processing updates order status correctly.
- Scan console/network for payment-related JS/API errors.

## 6. Documentation Update Requirements
- Update `CHANGELOG.md` for any payment flow or gateway behavior change.
- Update affected gateway docs and payments overview.
- Update ADR when payment contracts, webhook model, or fallback strategy changes.
