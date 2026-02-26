# Overview
This document defines the internal architecture of the Brevo Mail Marketing module inside the `wpblackwork` plugin.

Scope:
- Checkout newsletter consent capture (classic WooCommerce checkout only)
- Brevo contact sync and retry logic
- Order-level diagnostics and admin tooling
- Orders list visibility (Newsletter + Source columns, filters, bulk actions)
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

## Source taxonomy
Consent source is stored in `_bw_subscribe_consent_source` and currently supports:
- `checkout`
- `coming_soon`
- `footer`
- `popup`
- `my_account`
- `supabase_google`
- `supabase_facebook`
- `supabase_magic_link`
- `app_stripe`


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


# Brevo Data Model
## Lists
- `#10 Blackwork - Marketing`:
  - primary list for consent granted contacts (`opt_in=1` + consent metadata present)
- `#11 Blackwork - Unconfirmed`:
  - placeholder for future DOI/non-checkout capture flows (not actively used yet)

List behavior rules:
- When consent is granted, subscribe/upsert into Marketing list.
- If plugin setting `list_id` is configured, it has priority over fallback.
- Fallback marketing list ID is `10` for backwards compatibility.
- No subscription is allowed without explicit consent gating.

## Attribute model (centralized map)
Attributes are built from one shared map (`BW_MailMarketing_Service::build_brevo_attributes_from_order()`), then reused across:
- checkout paid/created flow
- manual retry
- bulk resync

| Key | Type | Example | Source |
|---|---|---|---|
| `SOURCE` | text | `checkout` | `_bw_subscribe_consent_source` |
| `CONSENT_SOURCE` | text | `checkout` | `_bw_subscribe_consent_source` |
| `CONSENT_AT` | ISO8601 text | `2026-02-24T18:50:00Z` | `_bw_subscribe_consent_at` |
| `CONSENT_STATUS` | text | `granted` | consent gate state |
| `BW_ORIGIN_SYSTEM` | text | `wp` | hardcoded |
| `BW_ENV` | text | `production` / `staging` | `wp_get_environment_type()` / `WP_ENV` |
| `LAST_ORDER_ID` | text/number | `26859` | order id |
| `LAST_ORDER_AT` | ISO8601 text | `2026-02-24T19:03:00Z` | paid date, fallback created date |
| `CUSTOMER_STATUS` | text | `customer` | order context |
| `FIRSTNAME` | text | `Mario` | billing first name (if enabled) |
| `LASTNAME` | text | `Rossi` | billing last name (if enabled) |

SOURCE evolution notes:
- Current implementation updates `SOURCE` with latest consent source.
- `SOURCE_FIRST` is reserved for future implementation (first-touch immutable source).
- Until `SOURCE_FIRST` exists, historical first-source is not persisted separately.

Operational prerequisite:
- Custom attributes must exist in Brevo:
  - `Contacts -> Settings -> Attributes`
- If missing, integration logs warning and applies fallback payloads.


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
- Contact attributes sent (when opt-in = 1):
  - `SOURCE` = consent source
  - `CONSENT_SOURCE` = consent source
  - `CONSENT_AT` = consent timestamp in ISO8601 (if available)
  - `CONSENT_STATUS` = `granted`
  - `BW_ORIGIN_SYSTEM` = `wp`
  - `BW_ENV` = `production|staging` (environment detection)
  - `LAST_ORDER_ID` = Woo order ID
  - `LAST_ORDER_AT` = paid datetime ISO8601 (fallback: order created datetime)
  - `CUSTOMER_STATUS` = `customer`
  - `FIRSTNAME` / `LASTNAME` if enabled in General settings
- On success:
  - `_bw_brevo_subscribed = subscribed`
  - `_bw_brevo_status_reason = subscribed`
  - clears `_bw_brevo_error_last`

## Double opt-in behavior
- Supported by configuration (general/channel mode).
- Sends DOI request to Brevo with template + redirect URL and the same attributes payload.
- On success:
  - `_bw_brevo_subscribed = pending`
  - `_bw_brevo_status_reason = double_opt_in_sent`

## Error handling
- Errors set:
  - `_bw_brevo_subscribed = error`
  - `_bw_brevo_error_last = <message>`
  - `_bw_brevo_status_reason = api_error`
- Logs are written via Woo logger source `bw-brevo`.
- If Brevo rejects unknown custom attributes, the module retries once with a minimal payload (`FIRSTNAME`/`LASTNAME` only) and logs the fallback.
- If Brevo still rejects attributes, the module retries with empty attributes payload.
- Unknown-attribute failures are treated as non-fatal warnings:
  - checkout flow is not interrupted
  - order status is not forced to error for attribute-schema mismatches
  - warning is stored in `_bw_brevo_error_last` for diagnostics

## Brevo Attributes
The module sends these attributes only when consent is allowed (`opt_in=1` with consent metadata present):

| Key | Type | Context | Example |
|---|---|---|---|
| `SOURCE` | string | all subscribe/upsert flows | `checkout` |
| `CONSENT_SOURCE` | string | all subscribe/upsert flows | `checkout` |
| `CONSENT_AT` | ISO8601 datetime string | all flows when available | `2026-02-24T18:50:00Z` |
| `CONSENT_STATUS` | string | all flows | `granted` |
| `BW_ORIGIN_SYSTEM` | string | all flows | `wp` |
| `BW_ENV` | string | all flows | `production` |
| `LAST_ORDER_ID` | string/int | order-based flows | `26859` |
| `LAST_ORDER_AT` | ISO8601 datetime string | order-based flows | `2026-02-24T19:03:00Z` |
| `CUSTOMER_STATUS` | string | order-based flows | `customer` |
| `FIRSTNAME` | string | if enabled | `Mario` |
| `LASTNAME` | string | if enabled | `Rossi` |

Operational guidance:
- Create custom attributes in Brevo under `Contacts -> Settings -> Attributes` if they are not already available.
- If custom attributes are missing in Brevo, synchronization continues with fallback payloads and logs a warning.

## Retry logic
- Order metabox action `Retry subscribe` re-runs sync logic manually.
- Protected by nonce and capability checks.
- Guardrails:
  - no subscription without consent
  - no auto-resubscribe for blocklisted/unsubscribed contacts

## Bulk resync logic (orders list)
- Bulk action: `Mail Marketing: Resync to Brevo`
- Processes selected orders in chunks (25 per batch) to reduce timeout risk.
- Per-order behavior:
  - opt-in `0` => skipped (`_bw_brevo_status_reason = no_opt_in`)
  - invalid email => skipped
  - blocklisted/unsubscribed => skipped
  - opt-in `1` + valid config => idempotent upsert/DOI retry
- Meta updates include:
  - `_bw_brevo_subscribed`
  - `_bw_brevo_error_last`
  - `_bw_brevo_last_attempt_at`
  - `_bw_brevo_last_attempt_source = bulk_resync`
- Completion notice:
  - `Resync complete: X subscribed, Y skipped, Z errors`


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
- Advanced diagnostics includes `Brevo attributes payload (summary)` (keys only, no sensitive payload values)
- Copy-to-clipboard for email/contact ID

## Orders list custom columns: Newsletter + Source
Location:
- Orders list table (classic and HPOS list views)

Behavior:
- Reads only local order meta (no remote API calls)
- Displays compact state (`Subscribed`, `Pending`, `No`, `Error`) with visual indicator
- Tooltip includes status + consent timestamp
- `Source` column renders `_bw_subscribe_consent_source` as a muted pill (or `—` if empty)

## Orders list filters
- `Newsletter status` dropdown:
  - Any
  - Subscribed
  - Pending
  - No opt-in
  - Error
- `Source` dropdown:
  - Any
  - checkout
  - coming_soon
  - footer
  - popup
  - my_account
  - supabase_google
  - supabase_facebook
- Filters are applied using `meta_query` only (no API calls).

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
- Orders list columns and filter dropdowns do not call Brevo APIs.
- List names are resolved from transient cache (`bw_brevo_lists_map_<hash>`).
- Remote Brevo checks are on-demand only (button actions), not automatic on page load.
- Order and user panels use lightweight AJAX endpoints with nonce/capability checks.
- Bulk resync is processed in fixed-size chunks (25 orders per chunk).


# Future Improvements
- Fully exposed and validated double opt-in strategy per channel in admin UX.
- Bulk order/user resync tools with batching and rate-limit safeguards.
- Automation events/webhooks for CRM and lifecycle campaigns.
- Unified service layer for checkout + user profile + future touchpoints.
- Supabase flow integration with consent/state consistency guarantees.
