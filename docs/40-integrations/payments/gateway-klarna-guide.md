# Gateway Klarna Guide

## Related Docs
- Global architecture: `payments-overview.md`
- Google Pay gateway: `gateway-google-pay-guide.md`
- Apple Pay gateway: `gateway-apple-pay-guide.md`

## Overview
This document tracks the BlackWork custom Klarna gateway architecture and settings.

- Gateway id: `bw_klarna`
- Provider: Stripe PaymentIntents (`payment_method_types[]=klarna`)
- Checkout flow: WooCommerce standard (`checkout -> redirect/auth -> thank you`)
- Webhook endpoint: `/?wc-api=bw_klarna`
- Source of truth for final payment completion: Stripe webhook (`payment_intent.succeeded`)

## Current Integration

### Gateway Class
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-klarna-gateway.php`
- Base class: `BW_Abstract_Stripe_Gateway`
- Supports:
  - `products`
  - `refunds`

### WooCommerce Registration
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Hook: `woocommerce_payment_gateways`
- Registered class: `BW_Klarna_Gateway`

### Bootstrap Requirement (CHECKOUT-01)
- `BW_Klarna_Gateway` must be loaded before gateway registration is evaluated.
- Required bootstrap surface:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Required includes for Klarna path:
  - `includes/Stripe/class-bw-stripe-api-client.php`
  - `includes/Utils/class-bw-stripe-safe-logger.php`
  - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
- Failure mode:
  - if `class_exists('BW_Klarna_Gateway')` is false during `woocommerce_payment_gateways`, `bw_klarna` is never added and checkout cannot render Klarna because `$available_gateways` does not contain it.

### Admin Tab (BlackWork Site > Checkout > Klarna Pay)
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`
- New sub-tab: `klarna-pay`
- Fields:
  - `bw_klarna_enabled`
  - `bw_klarna_publishable_key`
  - `bw_klarna_secret_key`
  - `bw_klarna_statement_descriptor`
  - `bw_klarna_webhook_secret`

### Connection Test (Live)
- JS: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/js/bw-klarna-admin.js`
- AJAX action: `bw_klarna_test_connection`
- Handler: `bw_klarna_test_connection_ajax_handler`
- Validation:
  - Secret key must start with `sk_live_`
  - Publishable key must start with `pk_live_`
  - API check: `GET /v1/account`

## PaymentIntent Flow

In `process_payment()`:
1. Validate WooCommerce order and selected payment method.
2. Validate live secret key exists.
3. Build Klarna billing details from order billing fields.
4. Create PaymentIntent with:
   - `payment_method_types[]=klarna`
   - `payment_method_data[type]=klarna`
   - `confirm=true`
   - `return_url`
   - metadata (`wc_order_id`, `bw_gateway`, `site_url`, `mode`)
5. Save meta (`_bw_klarna_pi_id`, mode, timestamps) and transaction id.
6. Handle PI status:

| PI status | Order status set | Rationale |
|---|---|---|
| `succeeded` | `on-hold` | Wait for webhook confirmation |
| `processing` | `on-hold` | Async capture; wait for webhook |
| `requires_action` | `pending` + redirect | Klarna auth redirect required |
| `requires_payment_method` \| `canceled` | error notice only | Payment not completed |

> `on-hold` is the canonical status for "payment attempted, awaiting webhook".
> The final order completion happens via `payment_intent.succeeded` webhook only.

## Return / Cancel / Failed UX

Global return router is handled in:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`

Behavior:
- If return has `redirect_status=failed|canceled` and order payment method is wallet (`bw_klarna`, `bw_google_pay`, `bw_apple_pay`):
  - do not complete payment
  - add checkout notice: payment canceled/not completed
  - redirect user back to checkout (no thank-you success flow)
  - keep cart/session usable for retry
- If order is already paid (webhook arrived first), normal thank-you flow is preserved.

## Webhook and Hardening

Handled via base class:
- Signature verification (`Stripe-Signature`, timestamp tolerance, `v1` match)
- Anti-conflict guard:
  - ignore if order missing
  - ignore if `order->get_payment_method() !== 'bw_klarna'`
- PI consistency check vs order meta
- Idempotency dedup (rolling event history)
- Event mapping:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
- Only webhook success should finalize payment state for production-safe consistency.

## Refunds

Handled by base class `process_refund()`:
- Supports partial/full refunds
- Uses order mode + secret key resolution
- Stripe idempotency key
- Saves refund meta:
  - `_bw_klarna_refund_ids`
  - `_bw_klarna_last_refund_id`

## Order Meta Keys

- `_bw_klarna_pi_id`
- `_bw_klarna_mode`
- `_bw_klarna_pm_id`
- `_bw_klarna_created_at`
- `_bw_klarna_processed_events`
- `_bw_klarna_refund_ids`
- `_bw_klarna_last_refund_id`

## UI Notes

- Checkout label: `Klarna - Flexible Payments`
- Order button text: `Place order with Klarna`
- Payment fields text:
  - Ready: `You'll be redirected to Klarna - Flexible payments to complete your purchase.`
  - Not configured: `Klarna is not configured. Activate Klarna (BlackWork) in WooCommerce > Settings > Payments.`
- Icon rendered as Klarna logo/chip in the payment row, styled in:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-payment-methods.css`

## Klarna Market Requirements

Klarna via Stripe is only available in specific countries/currencies.
If the WooCommerce store uses an unsupported country/currency, Klarna will not appear in
the checkout (Stripe silently excludes it from `payment_method_types`).

**Supported countries (Stripe Klarna):** AT, BE, DE, DK, ES, FI, FR, GB, IE, IT, NL, NO, SE, US (and others per Stripe docs).
**Supported currencies:** EUR, GBP, DKK, NOK, SEK, USD (country-dependent).

If your store is in an unsupported combination:
- Klarna gateway will be registered but Stripe will reject PI creation.
- The error surfaces as a WooCommerce checkout notice.
- There is currently no admin-level pre-flight warning for this case.

Reference: https://stripe.com/docs/payments/klarna#supported-currencies

## Open Items

1. Add test mode fields for Klarna to allow staging environment testing.
2. Add admin warning if Stripe account country/currency does not support Klarna.
3. Add pre-flight check in `process_payment()` that validates currency against Klarna's supported list before creating the PI.
