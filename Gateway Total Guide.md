# Gateway Total Guide

## Scope
This is the master guide for the BlackWork checkout payment architecture.
It consolidates:
- Official gateways (Stripe Card, PayPal)
- Custom BlackWork gateways (Google Pay, Klarna, Apple Pay)
- Shared checkout UI orchestration (accordion + action button sync)
- Incident history and fixes to prevent regressions

## Related Gateway Guides
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Google Pay Guide.md`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Klarna Guide.md`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Apple Pay Guide.md`

---

## Payment Stack Overview

## Official plugins
1. Credit / Debit Card
- Provider: `wc_stripe` official plugin
- Checkout behavior: standard Stripe card fields inside Woo payment box

2. PayPal
- Provider: official PayPal plugin
- Checkout behavior: plugin-managed

## Custom BlackWork gateways
1. `bw_google_pay`
- Stripe PaymentIntents + custom frontend `paymentRequest`
- Webhook endpoint: `/?wc-api=bw_google_pay`

2. `bw_klarna`
- Stripe PaymentIntents (`payment_method_types[]=klarna`) + redirect flow
- Webhook endpoint: `/?wc-api=bw_klarna`

3. `bw_apple_pay`
- Stripe PaymentIntents + Apple Pay availability checks
- Webhook endpoint: `/?wc-api=bw_apple_pay`

---

## Core Architecture Files

## Gateway classes
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-google-pay-gateway.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-klarna-gateway.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-apple-pay-gateway.php`
- Shared base class (hardening/refunds/webhook logic):
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-abstract-stripe-gateway.php`

## Checkout UI/JS
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-payment-methods.js`
  - Shared accordion state and action-button sync
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-google-pay.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-apple-pay.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-payment-methods.css`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/checkout/payment.php`

## Registration / routing
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`

## Admin settings tabs
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`

---

## Canonical Runtime Rules

1. Checkout source-of-truth for payment completion
- Do not mark paid on frontend return/cancel.
- Final success is confirmed by Stripe webhook `payment_intent.succeeded`.

2. Wallet fail/cancel UX
- If `redirect_status=failed|canceled` and order is wallet-based:
  - redirect back to checkout
  - show notice
  - keep retry flow possible
- If order already paid by webhook, keep thank-you flow.

3. Anti-conflict with official gateways
- Webhook and order updates must be gated by payment method id.
- Custom gateways must not mutate `wc_stripe` or PayPal orders.

4. Idempotency
- Deduplicate webhook events (rolling event id history).
- Ignore repeated events and PI/order mismatches.

---

## Accordion and Checkout UI Incident Log (Resolved)

## Incident A: Multiple payment boxes open together
Symptoms:
- Stripe card box stayed open while selecting Google/Apple.
- Multiple accordions appeared open in the same view.

Root cause:
- Radio state and UI state drifted after dynamic updates and custom handlers.

Fix:
- Normalize payment radio selection in shared wallet script.
- Enforce single selected method and single open box behavior.

## Incident B: Wrong action button visible (wallet + place order conflicts)
Symptoms:
- `Place order` visible while wallet method selected.
- Temporary duplicate CTA states after `updated_checkout`.

Fix:
- Centralized button sync in `bw-payment-methods.js`.
- Re-sync after Woo checkout updates.
- Ensure only one primary action is visible per selected method.

## Incident C: Stripe title changed from "Credit / Debit Card" to "Stripe"
Symptoms:
- Card method label switched unexpectedly when changing methods.

Fix:
- Label normalization and UI guard in shared payment methods script/template behavior.
- Keep canonical card label stable.

## Incident D: Google Pay stuck on "Initializing Google Pay..."
Symptoms:
- Google Pay button never became active.
- Unavailable/placeholder state persisted.

Root cause:
- Invalid `paymentRequest.total.amount` format/subunit in some states.

Fix:
- Added cents normalization in Google Pay JS (`bwNormalizeCents`).
- Applied at init and on `updated_checkout` updates.
- Added stale-checking fallback and unavailable rerender logic.

## Incident E: Google Pay unavailable state too noisy
Symptoms:
- Unavailable message shown together with extra icon/text blocks.

Fix:
- Hide Google info block when unavailable.
- Keep only core unavailable notice + wallet CTA for clarity.

## Incident F: Gateway row rendered but wallet JS not initialized
Symptoms:
- Method visible in checkout but behavior missing.

Fix:
- Harden enqueue logic in `woocommerce-init.php`:
  - load Google Pay JS when enabled OR when gateway appears in available gateways.

## Incident G: Apple/Klarna icon and click-target regressions
Symptoms:
- Icon size/render mismatches.
- Header/toggle click not always responsive.

Fix:
- Normalize icon rendering/styling selectors.
- Keep header label click area aligned with other methods.

---

## Current Status Summary by Gateway

## Stripe Card (official)
- Active and independent.
- Must remain isolated from custom wallet state logic.

## PayPal (official)
- Active and independent.
- No custom webhook coupling with BlackWork wallets.

## Google Pay (custom)
- Hardened for availability and amount normalization.
- Unavailable state now explicit and cleaner.

## Klarna (custom)
- Redirect flow stable.
- Return fail/cancel route uses checkout retry strategy.

## Apple Pay (custom)
- Implemented with live-only settings and availability checks.
- Requires Safari/Apple device + Wallet card + HTTPS + domain verification in Stripe.

---

## Regression Checklist (Run before releases)

1. Accordion behavior
- Selecting any method opens only that method.
- Switching method closes previous method.

2. CTA behavior
- Only one primary action button visible at a time.
- Wallet method must not show wrong CTA from another gateway.

3. Google Pay
- No infinite "Initializing" state.
- Console has no paymentRequest amount-subunit error.

4. Klarna
- Redirect starts correctly.
- Cancel/close returns to checkout with notice.

5. Apple Pay
- Availability state coherent (available/unavailable).
- Unavailable messaging visible and not conflicting.

6. Webhook safety
- Success completes only correct gateway orders.
- Replay events do not duplicate side effects.

---

## Documentation Status
Dedicated guides are now available for:
- Google Pay
- Klarna
- Apple Pay
