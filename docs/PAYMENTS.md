# PAYMENTS Architecture Guide

## Overview
This plugin uses WooCommerce checkout standard flow and supports multiple payment providers.

### Active custom gateways (all in production)

| Gateway ID | Method | File |
|---|---|---|
| `bw_google_pay` | Google Pay (Stripe) | `includes/Gateways/class-bw-google-pay-gateway.php` |
| `bw_klarna` | Klarna (Stripe) | `includes/Gateways/class-bw-klarna-gateway.php` |
| `bw_apple_pay` | Apple Pay (Stripe) | `includes/Gateways/class-bw-apple-pay-gateway.php` |

### Coexisting gateways (third-party plugins)
- `wc_stripe` (cards, official Stripe plugin)
- PayPal plugin (separate)

### Architecture goal
- Reuse Stripe hardening logic via shared `BW_Abstract_Stripe_Gateway` base class.
- One webhook endpoint per gateway (Option B — zero migration risk).
- No code duplication across Google Pay, Klarna, Apple Pay.

## File Structure

Plugin root: `wp-content/plugins/wpblackwork/`

```
includes/Gateways/
  class-bw-abstract-stripe-gateway.php   ← shared base (webhook, refund, dedup, logging)
  class-bw-google-pay-gateway.php        ← Google Pay implementation
  class-bw-klarna-gateway.php            ← Klarna implementation
  class-bw-apple-pay-gateway.php         ← Apple Pay implementation
includes/Stripe/
  class-bw-stripe-api-client.php         ← Stripe HTTP client
includes/Utils/
  class-bw-stripe-safe-logger.php        ← WC_Logger wrapper
```

## Checkout Flow (Google Pay)
1. User selects method `bw_google_pay` in checkout accordion.
2. Frontend JS (`assets/js/bw-google-pay.js`) runs Stripe Payment Request and checks availability (`canMakePayment`).
3. On approval, JS posts checkout with hidden field `bw_google_pay_method_id`.
4. `BW_Google_Pay_Gateway::process_payment()` creates Stripe PaymentIntent via shared helper.
5. Gateway saves PI refs on order meta and sets transaction id.
6. On success, WooCommerce redirects to thank-you page and triggers normal email flow.

## Stripe Webhook Configuration

**Three separate webhook endpoints must be registered in the Stripe Dashboard:**

| Gateway | Endpoint URL | Events to subscribe |
|---|---|---|
| Google Pay | `https://yourdomain.com/?wc-api=bw_google_pay` | `payment_intent.succeeded`, `payment_intent.payment_failed` |
| Klarna | `https://yourdomain.com/?wc-api=bw_klarna` | `payment_intent.succeeded`, `payment_intent.payment_failed` |
| Apple Pay | `https://yourdomain.com/?wc-api=bw_apple_pay` | `payment_intent.succeeded`, `payment_intent.payment_failed` |

Each endpoint has its own signing secret stored in:
- `bw_google_pay_webhook_secret`
- `bw_klarna_webhook_secret`
- `bw_apple_pay_webhook_secret`

> If only one webhook is configured, only that gateway will receive events.
> The other two gateways will never finalize orders via webhook.

## Webhook Flow
Endpoints (all active):
- `/?wc-api=bw_google_pay`
- `/?wc-api=bw_klarna`
- `/?wc-api=bw_apple_pay`

Handled in base class (`BW_Abstract_Stripe_Gateway::handle_webhook`):
1. Verify Stripe signature (`t=`, one or more `v1=`, 300s tolerance).
2. Parse event payload.
3. Resolve order id from metadata (`wc_order_id`, fallback `order_id`).
4. Anti-conflict guard: process only if `order->get_payment_method() === gateway id`.
5. Optional gateway metadata guard (`metadata[bw_gateway]`).
6. PI consistency guard against stored PI meta.
7. Idempotency: ignore already processed event ids (rolling list of last 20).
8. Apply state transition:
   - `payment_intent.succeeded` => `payment_complete` (if not paid)
   - `payment_intent.payment_failed` => `failed` (if not already paid)

## Refund Flow
`BW_Abstract_Stripe_Gateway::process_refund()` handles:
- WooCommerce order validation
- gateway ownership check
- mode-aware key selection from order meta
- partial/full refund amount handling
- idempotency key
- Stripe refund call
- optional fallback using `latest_charge`
- refund meta update + order note only after valid Stripe refund id

Idempotency key format:
- `bw_gpay_refund_{order_id}_{pi_id}_{amount_or_full}_{reason_hash}`

## Anti-Conflict with `wc_stripe`
Hard guards prevent cross-gateway side effects:
- Webhook ignores orders not owned by this gateway.
- Refund blocks non-owned orders.
- PI mismatch protection avoids wrong order updates.

## Option Names (Google Pay)
Kept unchanged:
- `bw_google_pay_publishable_key`
- `bw_google_pay_secret_key`
- `bw_google_pay_test_publishable_key`
- `bw_google_pay_test_secret_key`
- `bw_google_pay_webhook_secret`
- `bw_google_pay_test_webhook_secret`
- `bw_google_pay_test_mode`
- `bw_google_pay_enabled`
- `bw_google_pay_statement_descriptor`

## Order Meta Conventions
Current Google Pay keys (kept):
- `_bw_gpay_pi_id`
- `_bw_gpay_mode`
- `_bw_gpay_pm_id`
- `_bw_gpay_created_at`
- `_bw_gpay_processed_events`
- `_bw_gpay_refund_ids`
- `_bw_gpay_last_refund_id`

Backwards compatibility:
- Existing keys are still read/written.
- No migration required for historical orders.

## Webhook Strategy: Option A vs Option B
Option A (single router `wc-api=bw_stripe_wallets`):
- Pros: one endpoint, centralized routing.
- Cons: requires Stripe dashboard updates and migration risks.

Option B (current):
- Keep dedicated endpoint per gateway.
- Reuse all logic in abstract base.
- Pros: zero migration risk, safest for production.

Current implementation uses Option B.

## Order Status Conventions

| `process_payment()` PI status | Order status | Who finalizes |
|---|---|---|
| `succeeded` | `on-hold` | Stripe webhook |
| `processing` | `on-hold` | Stripe webhook |
| `requires_action` | `pending` + redirect | User completes 3DS, then webhook |
| `requires_payment_method` / `canceled` | error notice, no status change | User retries |

> **Rule:** `on-hold` = payment attempted, waiting for webhook.
> `pending` = 3DS auth redirect in progress.
> Never call `payment_complete()` directly in `process_payment()`.
> Only the webhook (`payment_intent.succeeded`) triggers `payment_complete()`.

## Stuck Order Monitoring

A WP-Cron job (`bw_mew_check_stuck_orders`) runs hourly and:
- Finds orders in `pending`/`on-hold` with a BW PaymentIntent meta key.
- That have not been updated in 4+ hours and are not paid.
- Sends an admin email notification and logs to `wc-bw-gateway` logger.

If orders appear stuck, check:
1. Stripe Dashboard → Webhooks → delivery logs for failures.
2. WooCommerce → Status → Logs → filter `bw-gateway`.
3. Verify all 3 webhook endpoints are registered in Stripe with correct signing secrets.

## How to Add a New Gateway (template)
1. Create gateway class extending `BW_Abstract_Stripe_Gateway`.
2. Implement required option-name methods and meta key map.
3. Implement specific `process_payment()` and UI rendering.
4. Register class in WooCommerce gateway filter.
5. Add admin tab/settings using gateway-specific option names.
6. Register a webhook endpoint in Stripe Dashboard for `/?wc-api=bw_{gateway_id}`.
7. Reuse base webhook and refund logic.

## Testing Checklist
### Checkout success
- Google Pay payment succeeds.
- Order marked paid.
- Thank-you redirect and emails work.

### Checkout failure
- Payment failure shows notice.
- Order not marked paid.

### Webhook replay
- Re-send same `evt_*`.
- No duplicate notes/status changes.

### Refund
- Partial refund works.
- Full refund works.
- Double request does not duplicate side effects.

### Conflict checks
- Webhook for other gateway order is ignored.
- Refund for non-owned gateway order is blocked.

### Availability UX
- `canMakePayment = false`: method disabled/fallback to other methods.
- `canMakePayment = true`: button is shown and checkout completes.
