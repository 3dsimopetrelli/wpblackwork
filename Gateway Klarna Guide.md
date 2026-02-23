# Gateway Klarna Guide

## Related Docs
- Global architecture: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Total Guide.md`
- Google Pay gateway: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/Gateway Google Pay Guide.md`

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
   - `succeeded` -> do **not** force immediate completion; wait for webhook confirmation path
   - `requires_action` -> redirect to Stripe/Klarna auth URL
   - `processing` -> on-hold
   - failed statuses -> WooCommerce error notice

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

## Next Improvements

1. Add test mode fields for Klarna if needed.
2. Add dedicated fallback messaging for unsupported country/currency combinations.
3. Add explicit admin warning if Stripe account country/currency rules do not support Klarna.
