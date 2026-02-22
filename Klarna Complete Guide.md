# Klarna Complete Guide

## Overview
This document tracks the BlackWork custom Klarna gateway architecture and settings.

- Gateway id: `bw_klarna`
- Provider: Stripe PaymentIntents (`payment_method_types[]=klarna`)
- Checkout flow: WooCommerce standard (`checkout -> redirect/auth -> thank you`)
- Webhook endpoint: `/?wc-api=bw_klarna`

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
   - `succeeded` -> `payment_complete`
   - `requires_action` -> redirect to Stripe/Klarna auth URL
   - `processing` -> on-hold
   - failed statuses -> WooCommerce error notice

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
- Payment fields text informs user about redirect flow.
- Icon currently rendered as custom Klarna wordmark chip, styled in:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-payment-methods.css`

## Next Improvements

1. Add test mode fields for Klarna if needed.
2. Add branded Klarna SVG asset instead of wordmark chip.
3. Add dedicated fallback messaging for unsupported country/currency combinations.
4. Add explicit admin warning if Stripe account country/currency rules do not support Klarna.
