# Gateway Apple Pay Guide

## Related Docs
- Global architecture: `payments-overview.md`
- Google Pay gateway: `gateway-google-pay-guide.md`
- Klarna gateway: `gateway-klarna-guide.md`

## Overview
This document tracks the BlackWork custom Apple Pay gateway architecture and current behavior.

- Gateway id: `bw_apple_pay`
- Provider: Stripe PaymentIntents (`payment_method_types[]=card`, Apple Pay via wallet availability)
- Checkout flow: WooCommerce standard (`checkout -> return -> webhook confirmation`)
- Webhook endpoint: `/?wc-api=bw_apple_pay`
- Source of truth for final payment completion: Stripe webhook (`payment_intent.succeeded`)

## Current Integration

### Gateway Class
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-apple-pay-gateway.php`
- Base class: `BW_Abstract_Stripe_Gateway`
- Supports:
  - `products`
  - `refunds`
- Order button text:
  - `Place order with Apple Pay`

### WooCommerce Registration
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Hook: `woocommerce_payment_gateways`
- Registered class: `BW_Apple_Pay_Gateway`

### Admin Tab (BlackWork Site > Checkout > Apple Pay)
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`
- Sub-tab: `apple-pay`
- Fields:
  - `bw_apple_pay_enabled`
  - `bw_apple_pay_express_helper_enabled` (checkbox: helper scroll on unavailable Apple Pay)
  - `bw_apple_pay_publishable_key`
  - `bw_apple_pay_secret_key`
  - `bw_apple_pay_statement_descriptor`
  - `bw_apple_pay_webhook_secret`
- Live-only UI (no test mode fields in tab).

### Admin Scripts (Connection + Domain checks)
- JS file: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/js/bw-apple-pay-admin.js`
- AJAX actions:
  - `bw_apple_pay_test_connection`
  - `bw_apple_pay_verify_domain`
- Connection check validation:
  - `sk_live_` required for secret key.
  - `pk_live_` required when publishable key is provided.
  - Uses Stripe `GET /v1/account`.
- Domain check:
  - Calls Stripe endpoint for payment method domains and checks current site host.

## Key Resolution Strategy

Apple Pay does not require separate Stripe keys at account level, but this plugin keeps dedicated Apple fields for admin UX.

Runtime behavior in gateway:
- Live secret key resolution:
  1. `bw_apple_pay_secret_key`
  2. fallback `bw_google_pay_secret_key`
- Live publishable key resolution:
  1. `bw_apple_pay_publishable_key`
  2. fallback `bw_google_pay_publishable_key`

Reference methods:
- `resolve_live_secret_key()`
- `resolve_live_publishable_key()`

## Checkout Rendering

Template sections involved:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/checkout/payment.php`

Key elements:
- `#bw-apple-pay-accordion-placeholder`
- `#bw-apple-pay-button-wrapper`
- `#bw-apple-pay-button`
- `.bw-apple-pay-info`

Rendered states:
1. Not enabled/not configured: admin guidance message.
2. Initializing: loading message in accordion.
3. Available: custom Apple Pay button appears.
4. Unavailable + helper enabled: show only helper text (no "Apple Pay unavailable" title) and use Apple button to scroll to Express Checkout.
5. Unavailable + helper disabled: show explicit unavailable message and keep Woo fallback path.

## Frontend JS Flow

- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-apple-pay.js`

Main behavior:
1. Initialize Stripe with live publishable key.
2. Build `paymentRequest`.
3. Run `canMakePayment()`.
4. Consider available only if:
   - `result && result.applePay === true`
5. Maintain explicit state machine:
   - `idle`
   - `method_selected`
   - `native_available`
   - `native_unavailable_helper`
   - `processing`
   - `error`
6. Handle events:
   - `paymentmethod` -> submit Woo checkout AJAX with `bw_apple_pay_method_id`
   - `cancel` -> show notice, clear hidden method id, keep checkout retry path
7. Re-run state after:
   - `payment_method` change
   - WooCommerce `updated_checkout`

All `console.log` / `console.info` calls are guarded by `BW_APPLE_PAY_DEBUG` or
`bwApplePayParams.adminDebug` and produce no output in production.

## Shared Wallet UI Orchestration

- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-payment-methods.js`

Responsibilities:
- Keep one active payment action at a time.
- Sync custom wallet buttons with selected gateway.
- Normalize accordion open/close behavior across Stripe/PayPal/Google/Klarna/Apple rows.

Important note:
- Apple Pay row behavior depends on this shared sync layer; regressions in shared selectors can affect Apple Pay open state and CTA visibility.

## Payment Processing (Server)

In `process_payment($order_id)`:
1. Validate order and selected payment method.
2. Validate `bw_apple_pay_method_id` exists.
3. Create PaymentIntent with:
   - `payment_method_types[]=card`
   - `payment_method=<pm_id from Apple Pay sheet>`
   - `confirm=true`
   - `return_url`
4. Save PI references and transaction id.
5. Handle statuses:

| PI status | Order status set | Rationale |
|---|---|---|
| `succeeded` \| `processing` | `on-hold` | Wait for webhook confirmation |
| `requires_action` | `pending` + redirect | 3DS auth required |
| `requires_payment_method` \| `canceled` | error notice only | Payment not completed |

> `on-hold` is the canonical status for "payment attempted, awaiting webhook".
> `pending` is only set when 3DS authentication is required (user redirected).
   - default -> generic failure notice

## Webhook and Hardening

Endpoint:
- `https://blackwork.pro/?wc-api=bw_apple_pay`

Handled via base gateway class:
- Signature verification (`Stripe-Signature`, timestamp tolerance, multi-v1 support).
- Anti-conflict guard:
  - order must exist
  - `order->get_payment_method() === 'bw_apple_pay'`
- Idempotency:
  - dedup with rolling event list in order meta.
- PI consistency check:
  - ignore events if PI id mismatches stored order PI id.
- Event handling:
  - `payment_intent.succeeded` -> complete payment once
  - `payment_intent.payment_failed` -> fail once if not already paid
  - `payment_intent.processing` -> keep pending/on-hold with note

## Order Meta Keys

- `_bw_apple_pay_pi_id`
- `_bw_apple_pay_mode`
- `_bw_apple_pay_pm_id`
- `_bw_apple_pay_created_at`
- `_bw_apple_pay_processed_events`
- `_bw_apple_pay_refund_ids`
- `_bw_apple_pay_last_refund_id`

## Return/Cancel UX Rules

Shared wallet return router:
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`

Behavior:
- If `redirect_status=failed|canceled` on wallet gateways (`bw_klarna`, `bw_google_pay`, `bw_apple_pay`):
  - do not complete payment
  - show checkout notice
  - redirect user back to checkout for retry
- If order already paid by webhook:
  - keep normal thank-you flow.

## Known Issues and Debug Notes (Current)

1. Apple Pay availability is environment-dependent:
- Requires Safari + Apple device + eligible card in Wallet + HTTPS + Stripe domain verification.
- On non-supported environments, helper mode scrolls user to top Express Checkout.

2. Shared accordion/button sync can impact Apple Pay UX:
- If shared script logic regresses, Apple row can appear selected while another gateway controls CTA.
- First file to inspect in these cases:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-payment-methods.js`

3. If Apple Pay stays in initializing state:
- Check browser console for:
  - `[BW Apple Pay] canMakePayment: ...`
- Check Stripe.js loaded and `bwApplePayParams.publishableKey` present.
- Check domain verification in Stripe dashboard and admin tab domain checker.

## Quick Production Checklist

1. Admin tab
- `Enable Gateway` checked.
- Live keys valid (`pk_live_`, `sk_live_`) or fallback live keys available.
- Webhook secret saved (`whsec_...`).
- Connection check returns success.
- Domain verification check returns success.

2. Checkout
- Apple row visible and selectable.
- Available device: custom Apple Pay button appears.
- Unavailable device (helper on): text-only helper guidance and Apple button scroll action.

3. Webhook
- `payment_intent.succeeded` finalizes order.
- Replayed events do not duplicate side effects.

## Next Improvements (Planned)

1. Add a dedicated `Gateway Apple Pay Guide` section in `/docs/PAYMENTS.md` cross-index.
2. Add a compact admin troubleshooting block listing exact failing prerequisites.
3. Keep shared wallet orchestration tests with Google/Klarna/Apple together to prevent cross-gateway regressions.
