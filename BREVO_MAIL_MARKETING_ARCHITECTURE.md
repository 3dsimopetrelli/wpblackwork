# Overview
This document defines the internal architecture of the Brevo Mail Marketing module inside the `wpblackwork` plugin.

Scope:
- Checkout newsletter consent capture (classic WooCommerce checkout only)
- Brevo contact sync and retry logic
- Order-level diagnostics and admin tooling
- Orders list visibility (Newsletter column)
- User profile diagnostics panel

Out of scope:
- WooCommerce Checkout Block flow
- Marketing automation orchestration


# Checkout Flow
## Field injection
- Field key/name: `bw_subscribe_newsletter`
- Field type: checkbox
- Injected through `woocommerce_checkout_fields` in:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
- Default location: billing section, after `billing_email` in classic checkout templates.
- The field is rendered inside the main checkout `<form name="checkout">`.

## Save lifecycle
Consent is persisted in two steps for robustness:
1. Primary save on `woocommerce_checkout_create_order` (order object context).
2. Fallback save on `woocommerce_checkout_update_order_meta` (post-meta context), with non-downgrade guards.

Input sources:
- Primary: `$_POST['bw_subscribe_newsletter']`
- Fallbacks: checkout posted data array, `$_POST['billing']['bw_subscribe_newsletter']`, `WC()->checkout()->get_value('bw_subscribe_newsletter')`
- Frontend diagnostic hidden input: `bw_subscribe_newsletter_frontend` (`checked` / `unchecked`)

## Meta created during checkout
- `_bw_subscribe_newsletter`
- `_bw_subscribe_consent_at`
- `_bw_subscribe_consent_source`
- `_bw_checkout_field_received`
- `_bw_checkout_field_value_raw`
- `_bw_checkout_post_keys_snapshot`
- `_bw_subscribe_newsletter_frontend`


# Order Meta Keys
## Canonical keys currently used by the module
- `_bw_subscribe_newsletter`: `0|1`
- `_bw_subscribe_consent_at`: local datetime (`Y-m-d H:i:s`)
- `_bw_subscribe_consent_source`: currently `checkout`
- `_bw_brevo_subscribed`: `subscribed|pending|skipped|error|not_subscribed`
- `_bw_brevo_status_reason`: reason key (for example: `no_opt_in`, `api_error`, `already_subscribed`)
- `_bw_brevo_contact_id`: Brevo contact ID when known
- `_bw_brevo_last_attempt_at`: last subscribe attempt datetime
- `_bw_brevo_last_attempt_source`: `checkout_paid_hook|checkout_created_hook|manual_retry|refresh_check`
- `_bw_brevo_last_checked_at`: last remote check datetime
- `_bw_brevo_error_last`: last error message
- `_bw_checkout_field_received`: `yes|no`
- `_bw_checkout_field_value_raw`: normalized raw posted value
- `_bw_checkout_post_keys_snapshot`: compact presence snapshot for POST diagnostics
- `_bw_subscribe_newsletter_frontend`: `checked|unchecked`

## Compatibility aliases (read/write fallback mapping)
The codebase also supports legacy or alternative naming in selected paths:
- `_bw_brevo_status` -> fallback/read equivalent of `_bw_brevo_subscribed`
- `_bw_last_error` -> fallback/read equivalent of `_bw_brevo_error_last`

## Requested alias mapping (documentation bridge)
For teams using alternative naming in external docs:
- `_bw_consent_timestamp` == `_bw_subscribe_consent_at`
- `_bw_consent_source` == `_bw_subscribe_consent_source`
- `_bw_brevo_status` == `_bw_brevo_subscribed`
- `_bw_last_subscription_attempt` == `_bw_brevo_last_attempt_at`
- `_bw_attempt_source` == `_bw_brevo_last_attempt_source`
- `_bw_last_error` == `_bw_brevo_error_last`
- `_bw_checkout_field_raw` == `_bw_checkout_field_value_raw`
- `_bw_frontend_checkbox_state` == `_bw_subscribe_newsletter_frontend`


# Brevo Sync Logic
## Trigger hooks
- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`
- `woocommerce_checkout_order_processed` (when checkout timing is `created`)

## Conditions before sync
- Checkout channel enabled.
- Explicit opt-in recorded (`_bw_subscribe_newsletter = 1`).
- Valid billing email.
- Brevo API key configured.
- Main list ID configured.
- Respect no-auto-resubscribe policy for blocklisted/unsubscribed contacts.

## Single opt-in behavior
- Calls Brevo contact upsert with list assignment.
- On success:
  - `_bw_brevo_subscribed = subscribed`
  - `_bw_brevo_status_reason = subscribed`
  - clears `_bw_brevo_error_last`

## Double opt-in behavior
- Supported by configuration (general/channel mode).
- Sends DOI request to Brevo with template + redirect URL.
- On success:
  - `_bw_brevo_subscribed = pending`
  - `_bw_brevo_status_reason = double_opt_in_sent`

## Error handling
- Errors set:
  - `_bw_brevo_subscribed = error`
  - `_bw_brevo_error_last = <message>`
  - `_bw_brevo_status_reason = api_error`
- Logs are written via Woo logger source `bw-brevo`.

## Retry logic
- Order metabox action `Retry subscribe` re-runs sync logic manually.
- Protected by nonce and capability checks.
- Guardrails:
  - no subscription without consent
  - no auto-resubscribe for blocklisted/unsubscribed contacts


# Admin UI
## Order metabox: Newsletter Status
Location:
- WooCommerce order edit screen (full-width metabox, high priority)

Features:
- Status badge with icon and color state
- Actions:
  - `Check Brevo` (read-only remote check)
  - `Retry subscribe` (write action)
- Contextual notice messages
- Structured sections:
  - `Consent`
  - `Brevo Sync`
  - `Advanced` (collapsible diagnostics)
- Copy-to-clipboard for email/contact ID

## Orders list custom column: Newsletter
Location:
- Orders list table (classic and HPOS list views)

Behavior:
- Reads only local order meta (no remote API calls)
- Displays compact state (`Subscribed`, `Pending`, `No`, `Error`) with visual indicator
- Tooltip includes status + consent timestamp

## User profile panel: Mail Marketing - Brevo
Location:
- WP Admin user profile/edit user screens (top panel)

Features:
- Current Brevo status view
- Consent timestamp/source display (if present in user meta)
- Actions:
  - `Check Brevo` (read-only)
  - `Sync status` (writes user status diagnostics only)
- Does not auto-subscribe users


# Status Mapping
UI status is derived from consent and sync meta:

- **Subscribed**
  - Opt-in `1` and Brevo sync status `subscribed`
- **Pending**
  - Brevo sync status `pending` (typically DOI flow)
- **No opt-in / Skipped**
  - Opt-in `0` or explicit skipped reasons (`no_opt_in`, `missing_list_id`, etc.)
- **Error**
  - Brevo status `error` and/or non-empty last error metadata

Reason labels are resolved from `_bw_brevo_status_reason` and surfaced in UI.


# Performance Notes
- Orders list column does not call Brevo APIs.
- List names are resolved from transient cache (`bw_brevo_lists_map_<hash>`).
- Remote Brevo checks are on-demand only (button actions), not automatic on page load.
- Order and user panels use lightweight AJAX endpoints with nonce/capability checks.


# Future Improvements
- Fully exposed and validated double opt-in strategy per channel in admin UX.
- Bulk order/user resync tools with batching and rate-limit safeguards.
- Automation events/webhooks for CRM and lifecycle campaigns.
- Unified service layer for checkout + user profile + future touchpoints.
- Supabase flow integration with consent/state consistency guarantees.
