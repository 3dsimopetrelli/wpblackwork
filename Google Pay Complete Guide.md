# Google Pay Complete Guide

## Scope
This guide documents the custom WooCommerce gateway `bw_google_pay` (Stripe integration), its architecture, runtime flow, fields, webhook behavior, hardening rules, and debugging playbook.

Goals:
- Keep production-safe behavior.
- Avoid conflicts with `wc_stripe` and PayPal gateways.
- Preserve WooCommerce standard flow (checkout -> order -> thank you -> emails).

---

## High-Level Architecture

### Gateways in checkout
- Credit/Debit Card: handled by `wc_stripe`.
- PayPal: handled by separate PayPal plugin.
- Google Pay: handled by custom gateway `bw_google_pay`.

### Core files
- Gateway class:
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
- Checkout script:
  - `assets/js/bw-google-pay.js`
- Checkout enqueue/localization:
  - `woocommerce/woocommerce-init.php`
- Admin settings + connection test:
  - `admin/class-blackwork-site-settings.php`
  - `admin/js/bw-google-pay-admin.js`

---

## Runtime Flow

## 1) Frontend availability (Google Pay)
- JS creates Stripe `paymentRequest(...)`.
- Calls `paymentRequest.canMakePayment()`.
- If available:
  - Shows custom Google Pay button.
  - Keeps gateway selectable.
- If unavailable:
  - Renders unavailable state (message + Google Wallet link).
  - Disables `bw_google_pay` radio.
  - If selected, auto-fallback to first available gateway.

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

## Data minimization
- Do not log full payloads or full secrets.
- Log only operational identifiers when needed:
  - order id, event id, PI id, status.

---

## Order State Rules

## `payment_intent.succeeded`
- If order not paid:
  - `payment_complete(pi_id)`
  - add single order note.
- If already paid:
  - no side effects.

## `payment_intent.payment_failed`
- If order already paid:
  - ignore.
- If not paid:
  - set `failed` once with reason.
  - avoid duplicate status updates.

---

## Frontend UX Rules (without changing accordion structure)

- If Google Pay not available on device/browser/account:
  - keep explanatory message
  - provide wallet link
  - do not allow selecting unusable method
  - fallback to other available gateway automatically
- If available:
  - show Google Pay button
  - hide generic Place order while Google Pay method is selected

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

## 4) Duplicate webhook delivery
- Expected behavior from Stripe.
- Must be harmless due to `_bw_gpay_processed_events` dedup.

---

## Test Plan (TEST and LIVE)

## Functional
- Successful payment -> thank you page + Woo email.
- Failed payment -> order moves to failed with single note.

## Webhook robustness
- Replay same `evt_...` -> no duplicate side effects.
- Send failed event after success -> ignored.
- Send event for non-`bw_google_pay` order -> ignored safely.

## Availability UX
- canMakePayment true -> button visible and usable.
- canMakePayment false -> method disabled, fallback gateway selected.

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
- Set keys for the active mode only.
- Configure webhook endpoint and matching `whsec` for active mode.
- Run connection test in active mode.
- Test one real/expected flow before going live.

