# PAYMENTS Architecture Guide

## Overview
This plugin uses WooCommerce checkout standard flow and supports multiple payment providers.
Current active custom wallet gateway:
- `bw_google_pay` (Stripe PaymentIntents)

Coexisting gateways:
- `wc_stripe` (cards, official plugin)
- PayPal plugin (separate)

Goal of this architecture:
- Reuse Stripe hardening logic across multiple custom gateways.
- Keep `bw_google_pay` behavior unchanged.
- Make `bw_klarna` and `bw_apple_pay` easy to add without code duplication.

## File Structure
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-abstract-stripe-gateway.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-google-pay-gateway.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-klarna-gateway.php` (placeholder)
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-apple-pay-gateway.php` (placeholder)
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Stripe/class-bw-stripe-api-client.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Utils/class-bw-stripe-safe-logger.php`
- Legacy bootstrap path kept for compatibility:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-google-pay-gateway.php`

## Checkout Flow (Google Pay)
1. User selects method `bw_google_pay` in checkout accordion.
2. Frontend JS (`assets/js/bw-google-pay.js`) runs Stripe Payment Request and checks availability (`canMakePayment`).
3. On approval, JS posts checkout with hidden field `bw_google_pay_method_id`.
4. `BW_Google_Pay_Gateway::process_payment()` creates Stripe PaymentIntent via shared helper.
5. Gateway saves PI refs on order meta and sets transaction id.
6. On success, WooCommerce redirects to thank-you page and triggers normal email flow.

## Webhook Flow
Endpoint (active):
- `/?wc-api=bw_google_pay`

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

## How to Add New Gateway (Klarna/Apple Pay)
1. Create gateway class extending `BW_Abstract_Stripe_Gateway`.
2. Implement required option-name methods and meta key map.
3. Implement specific `process_payment()` and UI rendering.
4. Register class in WooCommerce gateway filter only when ready.
5. Add admin tab/settings using gateway-specific option names.
6. Reuse base webhook and refund logic.

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
