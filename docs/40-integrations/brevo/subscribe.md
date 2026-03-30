# Documentation Governance & Update Workflow

Document Version: 1.4
Last Updated: 2026-03-17
Last Update Summary: Closed the Elementor subscription widget documentation wave and aligned Brevo docs with the hardened public widget channel.

## 0.1 Purpose of this Document
This file is the official technical specification and single source of truth for the Brevo Mail Marketing system in the Blackwork Site plugin.

It governs and documents:

- Architecture
- Configuration
- Runtime flow
- Compliance logic
- State machine behavior

## 0.2 How to Update This Document
Mandatory maintenance rules:

- Every architectural change must be documented in the relevant architecture section.
- Every new setting must be added under the correct configuration section.
- Every new hook must be listed in the `Subscription Flow (Runtime Behavior)` section.
- Every runtime state change must update the `State Machine` section.
- Every logging behavior change must update the `Logging Specification` section.
- Deprecated logic must be moved to `Migration & Legacy Notes` and marked as deprecated.
- Historical notes must never be deleted; they must be marked as deprecated and retained.
- Each update must refresh the top metadata lines:
  - `Document Version`
  - `Last Updated`
  - `Last Update Summary`

## 0.3 Versioning Strategy
Internal document versioning rules:

- Major: architectural refactors or major model changes
- Minor: new functional capabilities or new documented subsystems
- Patch: corrections, clarifications, non-functional documentation fixes

Current baseline:

- Document Version: `1.4`
- Last Updated: `2026-03-17`
- Last Update Summary: `Closed the Elementor subscription widget documentation wave and aligned Brevo docs with the hardened public widget channel.`

## 0.4 Change Log Section
A persistent change log must be maintained at the end of this document under `10. Change Log`.

Required format:

```text
## vX.Y - YYYY-MM-DD
- Change summary line 1
- Change summary line 2
```

---

# 1. Overview
This specification describes the Brevo Mail Marketing system implemented in the Blackwork Site plugin.

Current admin IA:

- `Blackwork Site -> Mail Marketing -> General`
- `Blackwork Site -> Mail Marketing -> Checkout`
- `Blackwork Site -> Mail Marketing -> Subscription`

System goals:

- Centralize Brevo credentials and global behavior
- Separate global settings from checkout-channel behavior
- Reuse one Brevo data model across checkout and site-wide widget channels
- Guarantee explicit consent-driven subscription logic
- Provide auditable status transitions and logs

Runtime scope documented here:

- Admin settings and migration
- Checkout subscription channel
- Elementor fixed-design subscription widget channel
- Brevo API integration
- Metadata state tracking
- Compliance and safety rules

---

# 2. Architecture Overview
The system is organized into five layers.

## 2.1 Admin Layer
Primary components:

- `BW_Checkout_Subscribe_Admin`
- `BW_Mail_Marketing_Settings`

Responsibilities:

- Register `Mail Marketing` submenu under `Blackwork Site`
- Render tabbed admin UI (`General`, `Checkout`, `Subscription`)
- Render WooCommerce Order Admin Newsletter Status panel
- Sanitize and persist settings
- Execute legacy migration bootstrap
- Handle secure AJAX connection testing
- Handle secure AJAX order/user actions

## 2.2 Service Layer
Current shared service surfaces:

- `BW_MailMarketing_Service`
- `BW_MailMarketing_Subscription_Channel`

Responsibilities:

- Resolve list selection (`inherit` vs `custom`)
- Resolve effective opt-in mode (`inherit` vs channel override)
- Build Brevo attribute payloads
- Parse full name into Brevo name attributes
- Detect unsupported attribute-schema errors and strip marketing attributes safely
- Own the public widget subscription endpoint runtime

## 2.3 Brevo Client Layer
Shared API adapter:

- `BW_Brevo_Client`

Primary methods:

- `get_account()`
- `get_lists()`
- `get_contact($email)`
- `upsert_contact($email, $attributes, $list_ids)`
- `send_double_opt_in(...)`

## 2.4 Frontend Integration Layer
Current channels:

- Checkout classic form
- Fixed-design Elementor newsletter widget

Primary components:

- `BW_Checkout_Subscribe_Frontend`
- `BW_Newsletter_Subscription_Widget`
- `BW_MailMarketing_Subscription_Channel`

Responsibilities:

- Inject checkout checkbox
- Save consent metadata
- Trigger checkout subscription at configured timing
- Render the Elementor widget
- Process widget AJAX submits with explicit consent and Brevo sync

## 2.5 Logging and Audit Layer
Backend:

- WooCommerce logger via `wc_get_logger()`

Source:

- `bw-brevo`

Context payload can include:

- `order_id`
- `email`
- `context`
- `result`
- `action`
- `source`

---

# 3. Admin Information Architecture

## 3.1 Mail Marketing -> General
Purpose: global Brevo configuration shared across channels.

Option name:

```text
bw_mail_marketing_general_settings
```

Fields:

- `api_key`
- `api_base` (`https://api.brevo.com/v3`, fixed)
- `list_id`
- `default_optin_mode`
- `double_optin_template_id`
- `double_optin_redirect_url`
- `sender_name`
- `sender_email`
- `debug_logging`
- `resubscribe_policy`
- `sync_first_name`
- `sync_last_name`

Brevo list loading behavior:

- Preferred mode: dropdown via Brevo API (`get_lists()`)
- Fallback mode: numeric `list_id` input if list retrieval fails

Connection test:

- AJAX action: `bw_brevo_test_connection`
- Protected by capability check and nonce validation
- Validates API key only

## 3.2 Mail Marketing -> Checkout
Purpose: checkout-channel-specific behavior.

Option name:

```text
bw_mail_marketing_checkout_settings
```

Fields:

- `enabled`
- `default_checked`
- `label_text`
- `privacy_text`
- `subscribe_timing` (`paid` default, `created` optional)
- `channel_optin_mode` (`inherit` | `single_opt_in` | `double_opt_in`)
- `placement_after_key`
- `priority_offset`

Checkout support limitation:

- Only classic WooCommerce checkout is supported.
- Checkout Blocks are explicitly excluded from this integration.

## 3.3 Mail Marketing -> Subscription
Purpose: central configuration for the reusable Elementor newsletter widget channel.

Option name:

```text
bw_mail_marketing_subscription_settings
```

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

Runtime-ready message fields already centralized in the schema/localized config:

- `empty_email_message`
- `invalid_email_message`
- `already_subscribed_message`
- `loading_message`
- `rate_limited_message`

Implementation note:

- The runtime already supports the full message map above.
- The admin UI currently exposes the core copy fields and privacy URL override.
- Additional message controls can be surfaced later without changing the endpoint contract.

### Elementor widget controls
Current widget controls:

- `Style`

`Style Footer`:
- `Show name field`

`Style Section`:
- `Show name field`
- `Title`
- `Subtitle`
- `Background Color`
- `Section Height`
- `Background Image`
- `Background Image Position`
- `Background Image Fit`
- `Content Position`
- `Title Typography`
- `Title Color`
- `Subtitle Typography`
- `Subtitle Color`
- `Privacy Typography`
- `Privacy Color`
- `Overlay Color`
- `Glow Color`
- `Overlay Opacity`

Design contract:

- The widget keeps a governed design contract with two presentation variants.
- Elementor controls are limited to style selection plus section-layout copy/media controls.
- Brevo behavior is owned by `Mail Marketing -> Subscription`, not by per-instance style controls.

## 3.4 WooCommerce Order Admin -> Newsletter Status Panel
Purpose: operational visibility and manual controls per order.

Location:

- WooCommerce order edit screen

UI components:

- Status badge
- `Check Brevo`
- `Retry subscribe`
- Inline response area
- `Consent`, `Brevo Sync`, and `Advanced` sections
- Advanced diagnostics table and payload summary

## 3.5 User Profile -> Mail Marketing - Brevo
Purpose: user-level diagnostics without automatic subscription authority.

Location:

- WP Admin user edit/profile screen

Capabilities:

- check current Brevo presence/status
- sync diagnostics status
- display consent metadata when present

---

# 4. Subscription Flow (Runtime Behavior)

## 4.1 Hook Map
Admin hooks:

- `admin_menu`
- `admin_init`
- `wp_ajax_bw_brevo_test_connection`
- `woocommerce_admin_order_data_after_order_details`
- `wp_ajax_bw_brevo_order_refresh_status`
- `wp_ajax_bw_brevo_order_retry_subscribe`
- `wp_ajax_bw_brevo_user_check_status`
- `wp_ajax_bw_brevo_user_sync_status`

Checkout hooks:

- `woocommerce_checkout_fields`
- `woocommerce_checkout_create_order`
- `woocommerce_checkout_update_order_meta`
- `woocommerce_checkout_order_processed`
- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`
- `wp_enqueue_scripts`

Widget/public hooks:

- `init`
- `wp_enqueue_scripts`
- `wp_ajax_bw_mail_marketing_subscribe`
- `wp_ajax_nopriv_bw_mail_marketing_subscribe`

## 4.2 Guest Checkout with Opt-in OFF
Sequence:

1. Checkout posts without consent.
2. Metadata saved:
   - `_bw_subscribe_newsletter = 0`
   - `_bw_subscribe_consent_source = checkout`
   - `_bw_brevo_subscribed = skipped`
3. Processor exits before Brevo subscription call.
4. No API subscription request is executed.

## 4.3 Guest Checkout with Opt-in ON
Sequence:

1. Checkout posts with consent.
2. Metadata saved:
   - `_bw_subscribe_newsletter = 1`
   - `_bw_subscribe_consent_at = <timestamp>`
   - `_bw_subscribe_consent_source = checkout`
3. Subscription execution depends on timing.

## 4.4 Timing Behavior: `paid` vs `created`

### `paid` (default)
Executed on:

- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`

### `created`
Executed on:

- `woocommerce_checkout_order_processed`

In both modes, execution path performs:

- consent validation
- settings validation
- email validation
- mode resolution (single vs DOI)
- Brevo API request
- state write + log event

## 4.5 Elementor Widget Submit Flow
Sequence:

1. Widget renders server-side with fixed markup and localized runtime config.
2. JS validates:
   - empty email
   - invalid email
   - missing consent
3. Button enters busy state and prevents double submit.
4. AJAX posts to `admin-ajax.php` with:
   - `action=bw_mail_marketing_subscribe`
   - `nonce`
   - `email`
   - optional `name`
   - `privacy=1`
5. Server validates again and normalizes email deterministically.
6. Cooldown gate runs before Brevo write.
7. Existing contact lookup prevents duplicate list-write when already subscribed.
8. Effective mode is resolved:
   - `single_opt_in`
   - `double_opt_in`
9. Brevo request executes server-side only.
10. Frontend receives structured safe JSON and updates the live region.

## 4.6 Double Opt-in Enabled
When resolved mode is DOI:

- Preconditions:
  - `double_optin_template_id`
  - `double_optin_redirect_url`
- Call:
  - `send_double_opt_in(...)`
- Result:
  - frontend receives logical success
  - order-based flows persist `pending`

## 4.7 Email Already Exists
Single opt-in path uses:

- `upsert_contact(..., updateEnabled=true)`

Widget-specific precheck:

- if existing contact is already in the resolved list:
  - no new write is sent
  - response code is `already_subscribed`

## 4.8 Email Unsubscribed / Blocklisted
Current enforced behavior:

- pre-check `get_contact($email)`
- if `emailBlacklisted` is true and resubscribe policy is `no_auto_resubscribe`:
  - skip subscription
  - log with `bw-brevo`
  - return safe failure to frontend or `skipped` on order paths

## 4.9 Public Endpoint Validation States
Stable widget endpoint codes:

- `empty_email`
- `invalid_email`
- `missing_consent`
- `rate_limited`
- `already_subscribed`
- `success`
- `generic_failure`

These codes are the canonical frontend contract for widget submit handling.

## 4.10 Custom Footer Runtime
Theme Builder Lite custom footers are injected late in the page lifecycle.

Current behavior:

- the widget channel scans the active footer template for `bw-newsletter-subscription`
- when present, assets are enqueued early during `wp_enqueue_scripts`

Purpose:

- keep frontend/footer/custom-footer styling consistent
- avoid late CSS misses caused by `wp_footer` injection timing

---

# 5. State Machine
Primary order meta key:

```text
_bw_brevo_subscribed
```

Allowed values and semantics:

## 5.1 `pending`
Set when DOI request is accepted and user confirmation is pending.

## 5.2 `subscribed`
Set after successful single opt-in upsert.

## 5.3 `skipped`
Set when subscription is intentionally not performed.

Typical reasons:

- no explicit consent
- no consent recorded
- blocklisted contact
- invalid email
- missing list

## 5.4 `error`
Set when validation or API execution fails on order-based flows.

Companion error key:

```text
_bw_brevo_error_last
```

Stores the latest failure or warning reason for diagnostics.

Widget note:

- the public widget endpoint does not persist order state
- its canonical state machine is the structured response code map

---

# 6. Logging Specification
Logger backend:

- WooCommerce logger (`wc_get_logger()`)

Source namespace:

```text
bw-brevo
```

Minimum log context fields:

- `order_id` when available
- `email` when available
- `context`
- `result`
- `action`
- `source`

Logging expectations:

- every terminal order/admin outcome must be logged
- widget endpoint failures must log provider detail server-side only
- customer-facing errors must remain generic and non-technical
- attribute-schema retries must log warning-level traces
- cooldown hits must log rate-limit events without exposing secrets

---

# 7. Compliance & Safety
Mandatory safety rules:

- No subscription without explicit consent.
- No automatic resubscribe of blocklisted/unsubscribed contacts under `no_auto_resubscribe`.
- All runtime outcomes must be auditable through metadata and logs.
- Manual retry must never bypass consent guardrails.
- The public widget endpoint must keep API keys and provider detail server-side only.

GDPR-oriented requirements:

- Consent text must be explicit and configurable at channel level.
- Consent default is opt-in required for the widget.
- Privacy Policy link can be overridden at channel level.
- Consent source and timestamp must be retained whenever local state exists.
- Preference and unsubscribe semantics from Brevo must be respected.

Accessibility essentials currently implemented for the widget:

- explicit label/input association
- `aria-describedby`
- `aria-invalid`
- `aria-disabled`
- live-region message updates
- no placeholder-only labeling

No-JS policy:

- widget submit requires JavaScript
- a deterministic `<noscript>` message is rendered instead of failing silently

---

# 8. Migration & Legacy Notes
## 8.1 Migration Strategy
Legacy option:

```text
bw_checkout_subscribe_settings
```

Migration behavior:

- if new options are empty and legacy option exists:
  - map global fields to `bw_mail_marketing_general_settings`
  - map checkout fields to `bw_mail_marketing_checkout_settings`

## 8.2 Legacy Compatibility
Deprecated admin path:

- `Blackwork Site -> Checkout -> Subscribe`

Current behavior:

- deprecated route shows notice and points to Mail Marketing page
- editable configuration is centralized in `Mail Marketing -> General/Checkout`

Backward-compatibility note:

- legacy option can still be read as fallback to avoid breaking older installations

## 8.3 Deprecated Future-Wave Notes
- `#11 Blackwork - Unconfirmed` remains reserved for future DOI/non-checkout capture flows
- `SOURCE_FIRST` remains documented but not implemented
- widget message schema is broader than the currently exposed admin controls

---

# 9. Future Roadmap
Channel expansion:

- Footer newsletter integration on the same subscription-channel authority
- Popup newsletter integration
- My Account newsletter toggle
- OAuth login soft opt-in integration

Architecture hardening:

- expose the full widget message schema in admin
- add first-touch source support (`SOURCE_FIRST`)
- expand unsubscribe detection beyond the current explicit blocklist flag
- add automated integration tests for widget/public endpoint flows

Observability improvements:

- structured log conventions
- dedicated diagnostics view for subscription events
- cross-entity traceability across order/user/widget touchpoints

---

# 10. Change Log
## v1.4 - 2026-03-17
- Closed the Elementor subscription widget documentation wave across Brevo, Elementor, and admin architecture docs.
- Documented the hardened public widget endpoint: nonce protection, deterministic response codes, server-side normalization, cooldown rate limiting, safe error exposure, and custom footer asset loading.
- Documented the current fixed-design widget editor controls and the Subscription tab settings schema, including runtime-ready message fields.

## v1.3 - 2026-03-16
- Added `Mail Marketing -> Subscription` as the dedicated admin tab for reusable site-wide newsletter widget settings.
- Added fixed-design Elementor newsletter widget channel with explicit consent submit flow and Brevo list inherit/custom override support.

## v1.2 - 2026-02-24
- Added shared service `BW_MailMarketing_Service` to centralize Brevo attribute mapping from order consent context.
- Standardized Brevo attributes across checkout/retry/bulk paths (`SOURCE`, `CONSENT_SOURCE`, `CONSENT_AT`, `CONSENT_STATUS`, `BW_ORIGIN_SYSTEM`, `BW_ENV`, `LAST_ORDER_ID`, `LAST_ORDER_AT`, `CUSTOMER_STATUS`).
- Added admin advanced payload summary row for Brevo attributes keys sent.
- Updated consent-gated admin UX (disabled retry/sync states and friendly no-consent notices).

## v1.1 - 2026-02-24
- Added WooCommerce Admin Order Newsletter Status panel.
- Added secure AJAX actions for order-level `Refresh` (read-only sync) and `Retry subscribe` (write action).
- Added new order meta fields `_bw_brevo_last_checked_at` and `_bw_brevo_contact_id`.

## v1.0 - 2026-02-24
- Initial Mail Marketing architecture refactor
