# Documentation Governance & Update Workflow

Document Version: 1.0  
Last Updated: 2026-02-24  
Last Update Summary: Initial Mail Marketing architecture refactor.

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

Starting baseline:

- Document Version: `1.0`
- Last Updated: `2026-02-24`
- Last Update Summary: `Initial Mail Marketing architecture refactor.`

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

System goals:

- Centralize Brevo credentials and global behavior
- Separate global settings from checkout-channel behavior
- Preserve backward compatibility during migration
- Guarantee explicit consent-driven subscription logic
- Provide auditable status transitions and logs

Runtime scope currently documented here:

- Admin settings and migration
- Checkout subscription channel
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
- Render tabbed admin UI (`General`, `Checkout`)
- Sanitize and persist settings
- Execute legacy migration bootstrap
- Handle secure AJAX connection testing

## 2.2 Service Orchestration Layer
Current implementation:

- Runtime orchestration is handled inside `BW_Checkout_Subscribe_Frontend::process_subscription()`.

Planned direction:

- Introduce centralized `BW_Subscribe_Service` to decouple channel logic from checkout-specific class.

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
Current channel:

- Checkout classic form only

Primary component:

- `BW_Checkout_Subscribe_Frontend`

Responsibilities:

- Inject checkout checkbox
- Save consent metadata
- Trigger subscription at configured timing
- Persist subscription status and errors

## 2.5 Logging and Audit Layer
Backend:

- WooCommerce logger via `wc_get_logger()`

Source:

- `bw-brevo`

Context payload includes:

- `order_id`
- `email`
- `context` (`checkout_guest` or `checkout_user`)
- `result` (`pending`, `subscribed`, `skipped`, `error`)

---

# 3. Admin Information Architecture

## 3.1 Mail Marketing -> General
Purpose: global Brevo configuration shared across channels.

Option name:

```text
bw_mail_marketing_general_settings
```

Fields:

- `api_key` (required for API operations)
- `api_base` (fixed: `https://api.brevo.com/v3`, not editable)
- `list_id` (main list)
- `default_optin_mode` (`single_opt_in` | `double_opt_in`)
- `double_optin_template_id`
- `double_optin_redirect_url`
- `sender_name`
- `sender_email`
- `debug_logging` (toggle)
- `resubscribe_policy` (`no_auto_resubscribe`)
- `sync_first_name` (toggle)
- `sync_last_name` (toggle)

Brevo list loading behavior:

- Preferred mode: dropdown from Brevo API (`get_lists()`)
- Fallback mode: numeric `list_id` input if list retrieval fails
- This fallback is mandatory to avoid UI hard-failure when API lookup is unavailable

Connection test:

- AJAX action: `bw_brevo_test_connection`
- Protected by capability check and nonce validation

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
- `placement_after_key` (default `billing_email`)
- `priority_offset` (default `5`)

Channel override behavior:

- `inherit`: uses `General.default_optin_mode`
- `single_opt_in`: forces single opt-in for checkout
- `double_opt_in`: forces DOI for checkout

Checkout support limitation:

- Only classic WooCommerce checkout is supported.
- Checkout Block is explicitly excluded from this integration.

---

# 4. Subscription Flow (Runtime Behavior)

## 4.1 Hook Map
Admin hooks:

- `admin_menu`
- `admin_init` (migration + save handlers)
- `wp_ajax_bw_brevo_test_connection`

Checkout hooks:

- `woocommerce_before_checkout_billing_form`
- `woocommerce_checkout_fields`
- `woocommerce_form_field`
- `woocommerce_checkout_update_order_meta`
- `woocommerce_checkout_order_processed` (timing `created`)
- `woocommerce_order_status_processing` (timing `paid`)
- `woocommerce_order_status_completed` (timing `paid`)
- `wp_enqueue_scripts`

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

- Consent validation
- Settings validation
- Email validation
- Mode resolution (single vs DOI)
- Brevo API request
- State write + log event

## 4.5 Double Opt-in Enabled
When resolved mode is DOI:

- Preconditions:
  - `double_optin_template_id`
  - `double_optin_redirect_url`
- Call:
  - `send_double_opt_in(...)`
- On success:
  - `_bw_brevo_subscribed = pending`

## 4.6 Email Already Exists
Single opt-in path uses:

- `upsert_contact(..., updateEnabled=true)`

Result:

- Existing contact is updated, not duplicated.

## 4.7 Email Unsubscribed / Blocklisted
Current enforced behavior:

- Pre-check `get_contact($email)`
- If `emailBlacklisted` is true:
  - skip subscription
  - set `_bw_brevo_subscribed = skipped`
  - log reason with `bw-brevo`

Note:

- Current guard is explicit for blocklisted contacts.
- Additional Brevo opt-out semantics should be expanded in future hardening.

---

# 5. State Machine
Primary meta key:

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
- blocklisted contact

## 5.4 `error`
Set when validation or API execution fails.

Companion error key:

```text
_bw_brevo_error_last
```

Stores latest failure reason for troubleshooting.

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
- `context` (`checkout_guest` / `checkout_user`)
- `result` (`pending`, `subscribed`, `skipped`, `error`)

Logging expectations:

- Every terminal outcome must be logged.
- Error logs must include actionable reason.
- Skip logs must include explicit reason (consent missing, blocklisted, etc.).

---

# 7. Compliance & Safety
Mandatory safety rules:

- No subscription without explicit consent.
- No automatic resubscribe of blocklisted/unsubscribed contacts under `no_auto_resubscribe` policy.
- All runtime outcomes must be auditable through metadata and logs.

GDPR-oriented requirements:

- Consent language must be explicit (`label_text`, `privacy_text`).
- Default checked behavior must be used only where legally valid.
- Consent source and timestamp must be retained for audit.
- Preference and unsubscribe semantics from Brevo must be respected.

---

# 8. Migration & Legacy Notes
## 8.1 Migration Strategy
Legacy option:

```text
bw_checkout_subscribe_settings
```

Migration behavior:

- If new options are empty and legacy option exists:
  - map global fields to `bw_mail_marketing_general_settings`
  - map checkout fields to `bw_mail_marketing_checkout_settings`

## 8.2 Legacy Compatibility
Deprecated admin path:

- `Blackwork Site -> Checkout -> Subscribe`

Current behavior:

- Deprecated route shows notice and points to Mail Marketing page.
- Editable configuration is centralized in `Mail Marketing -> General/Checkout`.

Backward-compatibility note:

- Legacy option can still be read as fallback to avoid breaking old installations.

---

# 9. Future Roadmap
Channel expansion:

- Footer newsletter integration
- Popup newsletter integration
- My Account newsletter toggle
- OAuth login soft opt-in integration

Architecture hardening:

- Introduce centralized `BW_Subscribe_Service`
- Extend unsubscribe/blocklist guard coverage beyond current explicit blacklisted check
- Normalize state transition contract across channels
- Add automated integration tests for `created`, `paid`, and DOI paths

Observability improvements:

- Structured log conventions
- Diagnostics view for subscription events
- Cross-entity traceability (order/user/contact)

---

# 10. Change Log
## v1.0 - 2026-02-24
- Initial Mail Marketing architecture refactor
