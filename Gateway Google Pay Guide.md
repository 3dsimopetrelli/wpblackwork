# Gateway Google Pay Guide

## Related Docs
- Global architecture: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Total Guide.md`
- Klarna gateway: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Klarna Guide.md`

## Scope
This guide documents the custom WooCommerce gateway `bw_google_pay` (Stripe integration), with the current architecture and the real issues/fixes already applied during hardening and UX stabilization.

Goals:
- Keep production-safe behavior.
- Avoid conflicts with `wc_stripe` and PayPal gateways.
- Preserve WooCommerce standard flow (checkout -> order -> thank you -> emails).
- Keep a practical debugging runbook for future regressions.

---

## High-Level Architecture

### Gateways in checkout
- Credit/Debit Card: handled by `wc_stripe`.
- PayPal: handled by separate PayPal plugin.
- Google Pay: handled by custom gateway `bw_google_pay`.

### Core files
- Gateway class:
  - `includes/Gateways/class-bw-google-pay-gateway.php`
- Checkout script:
  - `assets/js/bw-google-pay.js`
- Checkout wallets orchestrator (shared):
  - `assets/js/bw-payment-methods.js`
- Checkout enqueue/localization:
  - `woocommerce/woocommerce-init.php`
- Admin settings + connection test:
  - `admin/class-blackwork-site-settings.php`
  - `admin/js/bw-google-pay-admin.js`

### Most relevant runtime files for current fixes
- `assets/js/bw-google-pay.js`
  - Google Pay init, canMakePayment, button mount/update, unavailable UI.
- `assets/js/bw-payment-methods.js`
  - Shared wallet/checkout button synchronization and accordion state normalization.
- `woocommerce/woocommerce-init.php`
  - Enqueue safety for Google Pay frontend script.

---

## Runtime Flow

## 1) Frontend availability (Google Pay)
- JS creates Stripe `paymentRequest(...)`.
- Calls `paymentRequest.canMakePayment()`.
- If available (`result && bwCanShowGooglePay(result)`):
  - Shows custom Google Pay button.
  - Keeps gateway selectable.
- If unavailable:
  - Renders unavailable state (message + Google Wallet link).
  - Hides Google Pay informational block (`icon + "After clicking Google Pay..."`) to avoid UX noise.
  - Leaves only unavailable notice/CTA as primary content in the accordion.
  - Prevents misleading mixed states after checkout refresh.

Main function: `initGooglePay()` in `assets/js/bw-google-pay.js`.

## 2) Checkout submit for Google Pay
- On Google Pay approval (`paymentmethod` event), JS posts Woo checkout AJAX with hidden field:
  - `bw_google_pay_method_id = pm_xxx`.
- Backend `process_payment($order_id)` creates Stripe PaymentIntent and handles status.

## 3) PaymentIntent creation (Stripe API)
- Endpoint: `POST https://api.stripe.com/v1/payment_intents`
- Important payload:
  - `amount`, `currency`, `payment_method`, `payment_method_types[]=card`, `confirm=true`, `return_url`.
  - metadata:
    - `wc_order_id`
    - `order_id` (retrocompat)
    - `bw_gateway=bw_google_pay`
    - `site_url`
    - `mode=test|live`
- Idempotency:
  - Header `Idempotency-Key: bw_gpay_<order_id>_<hash(pm_id)>`.

## 4) Webhook handling
- Endpoint:
  - `https://blackwork.pro/?wc-api=bw_google_pay`
- Supported events:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
- Signature:
  - Custom Stripe-Signature verification (HMAC SHA-256, timestamp tolerance 300s, `hash_equals`).
- Anti-conflict:
  - Process only if:
    - order exists
    - order payment method is exactly `bw_google_pay`
    - optional metadata `bw_gateway` matches
- PI consistency:
  - If `_bw_gpay_pi_id` exists and differs from incoming PI id, ignore event.
- Idempotency:
  - Save processed `event_id` list in `_bw_gpay_processed_events` (rolling last 20).
  - Ignore duplicate events.

---

## Admin Configuration Fields

Stored options:
- `bw_google_pay_enabled` (0/1)
- `bw_google_pay_test_mode` (0/1)
- `bw_google_pay_publishable_key` (live pk)
- `bw_google_pay_secret_key` (live sk)
- `bw_google_pay_test_publishable_key` (test pk)
- `bw_google_pay_test_secret_key` (test sk)
- `bw_google_pay_statement_descriptor`
- `bw_google_pay_webhook_secret` (live whsec)
- `bw_google_pay_test_webhook_secret` (test whsec)

Gateway enablement requires both:
- BlackWork setting enabled (`bw_google_pay_enabled=1`)
- WooCommerce Payments method enabled (`woocommerce_bw_google_pay_settings[enabled]=yes`)

---

## Order Meta Used by Gateway

Written at PI creation:
- `_bw_gpay_pi_id`
- `_bw_gpay_mode` (`test` or `live`)
- `_bw_gpay_pm_id`
- `_bw_gpay_created_at` (unix timestamp)

Written by webhook dedup:
- `_bw_gpay_processed_events` (array of Stripe `evt_...`, max 20)

---

## Security and Hardening Rules

## Mandatory safeguards in place
- Webhook signature verification with timestamp tolerance.
- Webhook anti-conflict with payment method check.
- Webhook dedup by `event_id`.
- PI mismatch protection against wrong order updates.
- Stripe API error handling (connection + non-2xx + API error object).
- Idempotency-Key for PaymentIntent creation.
- Safe frontend fallback when Google Pay is unavailable.

## Data minimization
- Do not log full payloads or full secrets.
- Log only operational identifiers when needed:
  - order id, event id, PI id, status.

---

## Order State Rules

### `process_payment()` return statuses

| PI status | Order status set | Rationale |
|---|---|---|
| `succeeded` | `on-hold` | Return received; wait for webhook to finalize |
| `processing` | `on-hold` | Async capture; wait for webhook |
| `requires_action` | error notice only | 3DS required; user redirected to auth |
| `requires_payment_method` / `canceled` | error notice only | Payment not completed |

> **Important:** `on-hold` is the correct WooCommerce status for "payment attempted,
> awaiting external confirmation". `pending` means "order created, payment not yet tried".
> The distinction matters for order automation, Brevo subscribe timing, and admin dashboards.

### Webhook state transitions (`BW_Abstract_Stripe_Gateway`)

| Stripe event | Final order status |
|---|---|
| `payment_intent.succeeded` | `payment_complete()` (processing/completed) |
| `payment_intent.payment_failed` | `failed` |

### Already-paid guard
If `$order->is_paid()` is true when the webhook arrives, no side effects occur.

---

## Frontend UX Rules (without changing accordion structure)

- If Google Pay not available on device/browser/account:
  - keep explanatory message
  - provide wallet link
  - hide Google info panel (`.bw-google-pay-info`)
  - keep checkout action state coherent (no wrong button shown)
- If available:
  - show Google Pay button
  - hide generic Place order while Google Pay method is selected

---

## Resolved Incidents (Important)

These were real regressions fixed in code and should be considered "known-good baseline" behavior now.

## Incident 1: Stuck on "Initializing Google Pay..."
Symptom:
- Accordion stayed in loading state.
- Google Pay button never appeared.
- Console showed Stripe error about `paymentRequest.total.amount`.

Root cause:
- `paymentRequest.total.amount` sometimes arrived in an invalid format for Stripe PaymentRequest (subunit mismatch / non-normalized value).

Applied fix:
- Added strict normalization helper in `assets/js/bw-google-pay.js`:
  - `bwNormalizeCents(value, fallback)`
- Applied both at:
  - init (`initialCents`)
  - checkout updates (`paymentRequest.update(...)` on `updated_checkout`)

Reference console error:
- `Invalid value for paymentRequest(): total.amount should be a positive amount in the currency's subunit`

## Incident 2: Google Pay row visible but JS not running
Symptom:
- Gateway row visible in checkout.
- JS behavior missing (no proper init / stale placeholder).

Root cause:
- Script enqueue was too strict and skipped in some combinations of gateway visibility/settings.

Applied fix:
- Hardened enqueue in `woocommerce/woocommerce-init.php`:
  - enqueue when BlackWork setting enabled OR gateway appears in Woo available gateways.

## Incident 3: Mixed checkout action states (wrong button shown)
Symptom:
- Google Pay selected but generic `Place order` visible.
- Intermittent mismatch after `updated_checkout`.

Root cause:
- UI state drift between wallet state and WooCommerce refreshed DOM.

Applied fix:
- Shared sync hardening in `assets/js/bw-payment-methods.js` (`bwSyncCheckoutActionButtons`).
- Google-side re-sync in `assets/js/bw-google-pay.js` after `updated_checkout`.

## Incident 4: Unavailable view too noisy
Symptom:
- Unavailable warning was shown together with generic “after clicking” info + icon block.

Applied fix:
- In unavailable state, force-hide `.bw-google-pay-info`.
- Keep only unavailable message + CTA.

## Incident 5: Stale "checking" state after checkout updates
Symptom:
- Temporary stale placeholder after Woo AJAX updates.

Applied fix:
- Added stale-checking guard and forced unavailable rerender when needed during refresh cycle.

---

## Known Production Pitfalls and Diagnosis

## 1) "The key is valid but belongs to Test Mode" while in live
Checklist:
- Confirm `Test Mode` checkbox state in BlackWork settings.
- Verify live keys actually start with:
  - `pk_live_`
  - `sk_live_`
- Ensure live and test keys are not accidentally swapped in fields.
- Re-save settings, then re-run connection test.

## 2) Google Pay method shows unavailable
Possible causes:
- No compatible card in Google Wallet for current account.
- Browser/device/account not eligible.
- Gateway disabled in WooCommerce Payments.
- Missing publishable key in active mode.

## 3) Checkout shows placeholder "Initializing Google Pay..."
Likely causes:
- JS did not initialize (missing Stripe script or localized params).
- Gateway disabled at WooCommerce payment method level.
- Invalid `paymentRequest.total.amount` formatting/subunit value.

Quick check:
- Open console and verify there is no `Invalid value for paymentRequest()` error.
- Verify `canMakePayment` log/result is not throwing and state moves out of checking.

## 4) Duplicate webhook delivery
- Expected behavior from Stripe.
- Must be harmless due to `_bw_gpay_processed_events` dedup.

## 5) Shared wallet UI synchronization
Google Pay visibility is synchronized with shared checkout wallet logic:
- Single source of truth in `assets/js/bw-payment-methods.js` (`bwSyncCheckoutActionButtons`).
- Prevents mixed UI states (wallet + wrong submit button shown together).
- Keeps card title normalization stable (`Credit / Debit Card`).

---

## Test Plan (TEST and LIVE)

## Functional
- Successful payment -> thank you page + Woo email.
- Failed payment -> order moves to failed with single note.

## Webhook robustness
- Replay same `evt_...` -> no duplicate side effects.
- Send failed event after success -> ignored.
- Send event for non-`bw_google_pay` order -> ignored safely.

## Availability UX (current behavior)
- canMakePayment true -> custom Google Pay button visible, generic place-order hidden when selected.
- canMakePayment false -> unavailable card shown with `Open Google Wallet` CTA; the extra Google info panel is hidden.
- after `updated_checkout` -> state remains coherent (no return to stale "initializing" without cause).

## Coexistence
- `wc_stripe` card checkout still works unchanged.
- PayPal checkout still works unchanged.

---

## Extension Strategy (Future Wallets)

To scale to Apple Pay/Klarna without duplicated logic:
- Extract shared Stripe PI + webhook logic into a reusable service layer.
- Keep per-wallet adapters only for:
  - frontend capability checks
  - wallet-specific button/render events
- Standardize PI metadata with:
  - `bw_gateway` values per adapter (`bw_google_pay`, `bw_apple_pay`, etc.)

---

## Quick Ops Checklist

- Enable BlackWork Google Pay setting.
- Enable WooCommerce payment method "Google Pay (BlackWork)".
- Verify active mode and key family coherence (live with `pk_live`/`sk_live`, test with `pk_test`/`sk_test`).
- Verify `paymentRequest.total.amount` is emitted as valid currency subunit integer.
- If unavailable, confirm expected capability limits (device/browser/account/wallet card) before treating as bug.
- Set keys for the active mode only.
- Configure webhook endpoint and matching `whsec` for active mode.
- Run connection test in active mode.
- Test one real/expected flow before going live.

---

## Quick Debug Snippet (Console)
Use this to verify frontend wiring in a live checkout session:

```js
(() => {
  console.log('typeof bwGooglePayParams =', typeof window.bwGooglePayParams);
  console.log('bwGooglePayParams =', window.bwGooglePayParams || null);
  console.log('typeof Stripe =', typeof window.Stripe);
  console.log('BW_GPAY_AVAILABLE =', window.BW_GPAY_AVAILABLE);
  const scripts = [...document.scripts]
    .map(s => s.src)
    .filter(src => src.includes('bw-google-pay') || src.includes('bw-payment-methods') || src.includes('stripe.com/v3'));
  console.log('loaded scripts =', scripts);
})();
```
