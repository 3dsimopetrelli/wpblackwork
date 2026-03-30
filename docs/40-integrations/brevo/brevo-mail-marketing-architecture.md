# Overview
This document defines the internal architecture of the Brevo Mail Marketing module inside the `wpblackwork` plugin.

Scope:
- Checkout newsletter consent capture (classic WooCommerce checkout only)
- Elementor fixed-design newsletter widget channel
- Brevo contact sync and retry logic
- Order-level diagnostics and admin tooling
- Orders list visibility (`Newsletter` + `Source` columns, filters, bulk actions)
- User profile diagnostics panel

Out of scope:
- WooCommerce Checkout Block flow
- Marketing automation orchestration
- Background job workers or queue-based sync


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
- Fallbacks:
  - checkout posted data array
  - `$_POST['billing']['bw_subscribe_newsletter']`
  - `WC()->checkout()->get_value('bw_subscribe_newsletter')`
- Frontend diagnostic hidden input:
  - `bw_subscribe_newsletter_frontend` (`checked` / `unchecked`)

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
- `elementor_widget`
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
- `_bw_subscribe_consent_source`: source key
- `_bw_brevo_subscribed`: `subscribed|pending|skipped|error|not_subscribed`
- `_bw_brevo_status_reason`: reason key (`no_opt_in`, `api_error`, `already_subscribed`, `double_opt_in_sent`, `No consent recorded`)
- `_bw_brevo_contact_id`: Brevo contact ID when known
- `_bw_brevo_last_attempt_at`: last subscribe attempt datetime
- `_bw_brevo_last_attempt_source`: `checkout_paid_hook|checkout_created_hook|manual_retry|refresh_check|bulk_resync`
- `_bw_brevo_last_checked_at`: last remote check datetime
- `_bw_brevo_error_last`: last diagnostic error/warning
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
- `#10 Blackwork - Marketing`
  - primary list for consent granted contacts (`opt_in=1` + consent metadata present)
  - configured via General settings field `list_id` (`Main list` dropdown)
- `#11 Blackwork - Unconfirmed`
  - assigned immediately on DOI submit, before the subscriber clicks the confirmation link
  - makes pre-confirmation contacts visible in the Brevo panel
  - configured via General settings field `unconfirmed_list_id` (`Unconfirmed list` dropdown)
  - optional: if set to `0` / `— None —`, the pre-assign step is skipped and behavior is identical to the previous flow

List behavior rules:
- When consent is granted, subscribe/upsert into the Marketing list.
- If plugin setting `list_id` is configured, it has priority over fallback.
- Fallback marketing list ID is `10` for backward compatibility.
- Subscription widget channel can inherit the General list or use its own custom list selection.
- No subscription is allowed without explicit consent gating.
- Removal from the Unconfirmed list after confirmation is not handled by the plugin; use a Brevo Automation (trigger: added to list #10 → remove from list #11).

## Subscription channel settings
Option name:

```text
bw_mail_marketing_subscription_settings
```

Admin location:
- `Blackwork Site -> Mail Marketing -> Subscription`

Operational fields:
- `enabled`
- `source_key`
- `list_mode` (`inherit` | `custom`)
- `list_id`
- `channel_optin_mode` (`inherit` | `single_opt_in` | `double_opt_in`)
- `consent_required`
- `privacy_url`

Widget copy fields:
- `name_label`
- `email_label`
- `consent_prefix`
- `privacy_link_label`
- `button_text`
- `success_message`
- `error_message`
- `consent_required_message`

Message-schema fields already supported by runtime defaults/localized config:
- `empty_email_message`
- `invalid_email_message`
- `already_subscribed_message`
- `loading_message`
- `rate_limited_message`

Implementation note:
- The settings schema supports all fields above.
- The current admin UI exposes the core widget copy fields and privacy URL.
- The extended validation/loading/rate-limit messages are already centralized in runtime configuration and can be surfaced in admin later without changing the endpoint contract.

## Elementor widget controls
Widget slug and file:
- `bw-newsletter-subscription`
- `includes/widgets/class-bw-newsletter-subscription-widget.php`

Current editor controls:
- `Style`

`Style Footer` controls:
- `Show name field`

`Style Section` controls:
- `Show name field`
- `Title`
- `Subtitle`
- `Background Color`
- `Section Height`
- `Background Image`
- `Background Image Position`
- `Background Image Fit`

`Style Section` style-tab controls:
- `Content Position`
- `Title Typography`
- `Subtitle Typography`
- `Privacy Typography`

Current control strategy:
- The widget keeps behavior fixed and exposes only governed presentation variants.
- Elementor controls are limited to layout/copy overrides for the section variant.
- Channel logic, Brevo list behavior, and default copy authority remain in `Mail Marketing -> Subscription`.

## Attribute model (centralized map)
Attributes are built from shared helpers in `BW_MailMarketing_Service`, then reused across:
- checkout paid/created flow
- manual retry
- bulk resync
- Elementor widget submit flow

| Key | Type | Example | Source |
|---|---|---|---|
| `SOURCE` | text | `checkout` / `elementor_widget` | consent source |
| `CONSENT_SOURCE` | text | `checkout` / `elementor_widget` | consent source |
| `CONSENT_AT` | ISO8601 text | `2026-03-17T10:15:00Z` | consent timestamp |
| `CONSENT_STATUS` | text | `granted` | consent gate state |
| `BW_ORIGIN_SYSTEM` | text | `wp` | hardcoded |
| `BW_ENV` | text | `production` / `staging` | `wp_get_environment_type()` / `WP_ENV` |
| `LAST_ORDER_ID` | text/number | `26859` | order id |
| `LAST_ORDER_AT` | ISO8601 text | `2026-02-24T19:03:00Z` | paid date, fallback created date |
| `CUSTOMER_STATUS` | text | `customer` | order context |
| `FIRSTNAME` | text | `Mario` | billing first name or parsed widget name |
| `LASTNAME` | text | `Rossi` | billing last name or parsed widget name |

SOURCE evolution notes:
- Current implementation updates `SOURCE` with the latest consent source.
- `SOURCE_FIRST` is reserved for future first-touch persistence.
- Until `SOURCE_FIRST` exists, historical first-touch attribution is not stored separately.

Operational prerequisite:
- Custom attributes must exist in Brevo:
  - `Contacts -> Settings -> Attributes`
- If missing, the integration logs warnings and retries with reduced payloads.


# Brevo Sync Logic
## Trigger hooks
Checkout / order runtime:
- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`
- `woocommerce_checkout_order_processed`

Public widget runtime:
- `wp_ajax_bw_mail_marketing_subscribe`
- `wp_ajax_nopriv_bw_mail_marketing_subscribe`

Asset/runtime hooks:
- `init` (`BW_MailMarketing_Subscription_Channel::register_assets`)
- `wp_enqueue_scripts` (`BW_MailMarketing_Subscription_Channel::maybe_enqueue_runtime_assets`)

## Conditions before sync
Checkout/order paths require:
- channel enabled
- explicit opt-in recorded
- valid consent timestamp and source
- valid billing email
- valid Brevo configuration

Elementor widget path requires:
- subscription channel enabled
- valid normalized email
- explicit privacy consent when `consent_required=1`
- valid Brevo configuration
- resolved list ID available (`inherit` or `custom`)

## Single opt-in behavior
- Calls Brevo contact upsert with list assignment.
- Upsert is idempotent and updates existing contacts.
- Attributes sent when consent is valid:
  - `SOURCE`
  - `CONSENT_SOURCE`
  - `CONSENT_AT`
  - `CONSENT_STATUS`
  - `BW_ORIGIN_SYSTEM`
  - `BW_ENV`
  - order attributes when order context exists
  - name attributes when configured

## Double opt-in behavior
- Supported by General + channel override settings.
- Widget channel uses the same DOI config authority as other Mail Marketing flows.
- DOI submit two-step flow (widget channel):
  1. `upsert_contact(email, attributes, [unconfirmed_list_id])` — assigns contact to Unconfirmed list immediately; non-fatal (DOI proceeds even if upsert fails)
  2. `send_double_opt_in(email, template_id, redirect_url, [list_id], attributes, sender)` — sends confirmation email; `list_id` (Marketing list) is assigned by Brevo only after the subscriber clicks the confirmation link
- Before confirmation: contact is visible in Brevo under the Unconfirmed list (if `unconfirmed_list_id` is configured)
- After confirmation: contact is added to the Marketing list by Brevo automatically
- On success:
  - DOI paths return a logical success state to the frontend
  - order-based flows persist `_bw_brevo_subscribed = pending`

## Public widget endpoint hardening
Endpoint:
- `bw_mail_marketing_subscribe`

Security contract:
- nonce verification with `bw_mail_marketing_subscription_submit`
- Brevo API key never exposed in HTML/JS
- Brevo requests stay server-side only

Validation contract:
- deterministic server-side email normalization:
  - trim
  - collapse whitespace
  - lowercase
- explicit server-side states:
  - `empty_email`
  - `invalid_email`
  - `missing_consent`
  - `rate_limited`
  - `already_subscribed`
  - `success`
  - `generic_failure`

Response contract:

```json
{
  "success": true,
  "data": {
    "code": "success",
    "message": "Safe user-facing text"
  }
}
```

Rate limiting:
- transient-based cooldown
- keyed by normalized email + request IP hash
- cooldown window: `45` seconds

Already subscribed handling:
- existing contact lookup runs before write
- if the contact is already in the configured list:
  - no second write is made
  - frontend receives `already_subscribed`

Error exposure policy:
- raw Brevo/provider errors stay in server logs only
- frontend receives generic safe copy from centralized widget config

## Frontend runtime behavior
JS runtime file:
- `assets/js/bw-newsletter-subscription.js`

Implemented behavior:
- client-side trim/lowercase validation before request
- empty email guard
- invalid email guard
- missing consent guard
- busy state with button disable + `aria-disabled`
- double-submit prevention
- safe malformed-JSON fallback
- safe network failure fallback
- form reset on success except for `already_subscribed`

Localized frontend config:
- `ajaxUrl`
- `consentRequired`
- `privacyUrl`
- `messages.emptyEmail`
- `messages.invalidEmail`
- `messages.missingConsent`
- `messages.loading`
- `messages.genericFailure`
- `messages.success`
- `messages.alreadySubscribed`
- `messages.rateLimited`
- `messages.networkFailure`

## Custom footer asset strategy
Problem solved:
- Theme Builder Lite custom footer templates are injected in `wp_footer`
- waiting until widget render can be too late for CSS that must print in `wp_head`

Current behavior:
- `BW_MailMarketing_Subscription_Channel::maybe_enqueue_runtime_assets()` detects whether the active footer contains `bw-newsletter-subscription`
- when detected, the widget CSS/JS are enqueued early during `wp_enqueue_scripts`

Result:
- editor/footer/frontend rendering now shares the same asset availability model
- the widget no longer renders unstyled in custom footer runtime due to late enqueue timing

## Error handling
Order paths:
- set `_bw_brevo_subscribed = error`
- set `_bw_brevo_error_last`
- log with `bw-brevo`

Widget path:
- logs detailed provider failures server-side only
- returns safe `generic_failure` to users
- retries attribute errors with reduced payloads

Unknown attribute behavior:
- detect unsupported Brevo custom attributes
- retry with stripped marketing attributes
- retry again with empty attributes if needed
- keep checkout/order placement non-blocking
- keep widget submit deterministic and safe


# Admin UI
## Mail Marketing admin page
Location:
- `Blackwork Site -> Mail Marketing`

Tabs:
- `General`
- `Checkout`
- `Subscription`

General tab option key:
```text
bw_mail_marketing_general_settings
```

General tab operational fields:
- `api_key` — Brevo API key
- `list_id` — Main list ID (Marketing list, assigned after DOI confirmation or on single opt-in)
- `unconfirmed_list_id` — Unconfirmed list ID (assigned immediately on DOI submit, pre-confirmation; optional — `0` skips pre-assign)
- `default_optin_mode` — `single_opt_in` | `double_opt_in`
- `double_optin_template_id` — Brevo template ID for DOI confirmation email
- `double_optin_redirect_url` — redirect URL after subscriber confirms
- `sender_email` — sender email for DOI outgoing messages
- `sender_name` — sender name for DOI outgoing messages

Subscription tab responsibilities:
- enable/disable widget channel
- source key
- list inherit/custom resolution
- custom list selector/fallback numeric input
- channel opt-in mode override
- fixed-design widget copy authority
- privacy URL override

## Order metabox: Newsletter Status
Location:
- WooCommerce order edit screen (full-width metabox, high priority)

Features:
- status badge with icon/color
- `Check Brevo`
- `Retry subscribe`
- contextual inline notices
- `Consent`, `Brevo Sync`, and `Advanced` sections
- advanced diagnostics includes `Brevo attributes payload (summary)`

## Orders list custom columns: Newsletter + Source
Location:
- Orders list table (classic and HPOS list views)

Behavior:
- local meta only, no remote API calls
- `Newsletter` status indicator
- `Source` consent pill from `_bw_subscribe_consent_source`

## User profile panel: Mail Marketing - Brevo
Location:
- WP Admin user profile/edit user screens

Features:
- current Brevo status view
- consent timestamp/source display when available
- explicit `Check Brevo` / `Sync status` actions
- does not auto-subscribe users


# Status Mapping
UI status is derived from consent and sync meta:

- **Subscribed**
  - opt-in `1`
  - sync status `subscribed`
- **Pending**
  - sync status `pending`
- **No opt-in / Skipped**
  - opt-in `0`
  - or explicit skip reason (`no_opt_in`, `No consent recorded`, `missing_list_id`, `blocklisted`)
- **Error**
  - sync status `error`
  - and/or non-empty last error diagnostics

Reason labels are resolved from `_bw_brevo_status_reason` and surfaced in admin UI.


# Performance Notes
- Orders list columns and filters do not call Brevo APIs.
- List names are resolved from transient cache.
- Remote Brevo checks are on-demand only.
- Widget runtime assets are registered once and loaded only where needed.
- Custom footer pre-enqueue avoids late-style misses without globally loading the widget on every page.


# Future Improvements
- Expose the full widget message schema in `Mail Marketing -> Subscription` admin UI.
- Add first-touch source persistence (`SOURCE_FIRST`).
- Add honeypot or stronger abuse mitigation if public submit traffic grows.
- Add non-AJAX fallback submit path only if a second consumer requires it.
- Add automated integration tests for widget submit, DOI, and public cooldown behavior.
