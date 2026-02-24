# Brevo Subscribe Architecture

## Purpose
This document defines the newsletter subscription architecture for Blackwork Site, aligned to the current admin IA:

- `Blackwork Site -> Mail Marketing -> General`
- `Blackwork Site -> Mail Marketing -> Checkout`

The objective is to keep Brevo configuration centralized, enforce explicit consent, and provide a reliable channel-specific checkout integration without breaking legacy installations.

---

## Scope
This document covers:

- Admin settings and migration
- Checkout frontend opt-in behavior
- Brevo API integration and state tracking
- Logging/audit expectations
- Safety and compliance constraints
- Future extension strategy for other site entry points

---

## High-Level Architecture

### Admin Layer
Primary admin module:

- `BW_Checkout_Subscribe_Admin`
- `BW_Mail_Marketing_Settings`

Responsibilities:

- Register submenu `Blackwork Site -> Mail Marketing`
- Render tabs `General` and `Checkout`
- Sanitize and persist settings
- Run legacy-to-new settings migration
- Handle AJAX Brevo connection test

### Service Layer (Current and Target)
Current:

- Subscription orchestration is handled inside `BW_Checkout_Subscribe_Frontend::process_subscription()`.

Target (planned):

- Introduce a dedicated `BW_Subscribe_Service` for centralized orchestration across channels.

Planned responsibilities:

- Consent validation
- Mode resolution (single vs DOI)
- Contact attribute mapping
- Resubscribe policy enforcement
- Unified state transitions and logging

### Brevo Client Layer
Shared low-level API wrapper:

- `BW_Brevo_Client`

Key methods:

- `get_account()`
- `get_lists()`
- `get_contact()`
- `upsert_contact($email, $attributes, $list_ids)`
- `send_double_opt_in(...)`

### Frontend Integration Layer
Checkout integration module:

- `BW_Checkout_Subscribe_Frontend`

Responsibilities:

- Inject newsletter checkbox in classic checkout billing fields
- Save consent metadata on order
- Trigger subscribe flow based on configured timing
- Write status/error metadata

### Logging and Audit Layer
Logging backend:

- WooCommerce logger via `wc_get_logger()`
- Source key: `bw-brevo`

Log context includes:

- `order_id`
- `email`
- `context` (`checkout_guest` or `checkout_user`)
- `result` (`pending`, `subscribed`, `skipped`, `error`)

---

## Settings Model

### New Source Options

```text
bw_mail_marketing_general_settings
bw_mail_marketing_checkout_settings
```

### Legacy Option (Fallback + Migration Source)

```text
bw_checkout_subscribe_settings
```

Migration runs on admin bootstrap and maps legacy keys into the new split model.

---

## Admin IA and Configuration

### General Tab (`Mail Marketing -> General`)
Global Brevo configuration shared by channels.

Fields:

- `api_key`: Brevo API key
- `api_base`: fixed to `https://api.brevo.com/v3` (readonly in UI)
- `list_id`: main audience/list
- `default_optin_mode`: `single_opt_in` | `double_opt_in`
- `double_optin_template_id`
- `double_optin_redirect_url`
- `sender_name`
- `sender_email`
- `debug_logging`: toggle
- `resubscribe_policy`: currently `no_auto_resubscribe`
- `sync_first_name`: toggle
- `sync_last_name`: toggle

Operational notes:

- List field prefers dropdown loaded from Brevo API (`get_lists()`), with numeric fallback if API retrieval fails.
- AJAX test connection is protected by capability and nonce checks.

### Checkout Tab (`Mail Marketing -> Checkout`)
Checkout-channel behavior only.

Fields:

- `enabled`: show newsletter checkbox
- `default_checked`
- `label_text`
- `privacy_text`
- `subscribe_timing`: `paid` (default) | `created`
- `channel_optin_mode`: `inherit` | `single_opt_in` | `double_opt_in`
- `placement_after_key` (default `billing_email`)
- `priority_offset` (default `5`)

Channel override behavior:

- If `channel_optin_mode = inherit`, checkout uses `default_optin_mode` from General tab.
- If forced to single/DOI, checkout ignores the General mode for this channel.

Classic checkout limitation:

- Integration applies to classic WooCommerce checkout form only.
- Checkout Block is explicitly excluded.

---

## Hook Map

### Admin

- `admin_menu`: register Mail Marketing submenu
- `admin_init`:
  - legacy migration
  - settings save handlers
- `wp_ajax_bw_brevo_test_connection`: Brevo connection test

### Checkout Frontend

- `woocommerce_before_checkout_billing_form`: render Contact heading
- `woocommerce_checkout_fields`: inject newsletter checkbox
- `woocommerce_form_field`: remove optional label for checkbox
- `woocommerce_checkout_update_order_meta`: persist consent metadata
- `woocommerce_checkout_order_processed`: subscribe when timing is `created`
- `woocommerce_order_status_processing`: subscribe when timing is `paid`
- `woocommerce_order_status_completed`: subscribe when timing is `paid`
- `wp_enqueue_scripts`: load checkout subscribe CSS

---

## Subscription Flow (Detailed)

### 1) Guest Checkout with Opt-in OFF

1. Checkout submitted.
2. Consent save writes:
   - `_bw_subscribe_newsletter = 0`
   - `_bw_subscribe_consent_source = checkout`
   - `_bw_brevo_subscribed = skipped`
3. Paid/created trigger reaches subscription processor.
4. Processor exits early due to no explicit consent.
5. No Brevo API call is made.

### 2) Guest Checkout with Opt-in ON

1. Checkout submitted.
2. Consent save writes:
   - `_bw_subscribe_newsletter = 1`
   - `_bw_subscribe_consent_source = checkout`
   - `_bw_subscribe_consent_at = <timestamp>`
3. Subscription execution timing depends on `subscribe_timing`.

### 3) Timing = `paid`

1. Order transitions to `processing` or `completed`.
2. `maybe_subscribe_on_paid()` executes.
3. Processor validates consent/settings/email/client.
4. Mode resolved from channel override or General default.
5. Calls single opt-in or DOI API path.
6. Writes final state meta (`subscribed` / `pending` / `skipped` / `error`).

### 4) Timing = `created`

1. On `woocommerce_checkout_order_processed`, subscription processor is called.
2. Same validation/mode/API/state logic applies.

### 5) Double Opt-in Enabled

1. Resolved mode is `double_opt_in`.
2. Requires:
   - `double_optin_template_id`
   - `double_optin_redirect_url`
3. Calls `send_double_opt_in(...)`.
4. On success writes `_bw_brevo_subscribed = pending`.
5. Contact joins list after end-user confirms DOI email.

### 6) Email Already Exists

1. Single opt-in path calls `upsert_contact(...)` with `updateEnabled=true`.
2. Existing contact is updated, not duplicated.
3. On success writes `_bw_brevo_subscribed = subscribed`.

### 7) Email Unsubscribed / Blocklisted

1. Pre-check uses `get_contact(email)`.
2. If contact is detected as blacklisted (`emailBlacklisted`), processor skips subscription.
3. Writes `_bw_brevo_subscribed = skipped`.
4. Logs reason via `bw-brevo` source.

Note:

- Current implementation explicitly checks `emailBlacklisted`; additional Brevo opt-out semantics may require further hardening.

---

## State Machine

Order meta key:

```text
_bw_brevo_subscribed
```

Allowed values:

### `pending`
Set when DOI request is accepted by Brevo and confirmation is still required.

### `subscribed`
Set when single opt-in `upsert_contact()` succeeds.

### `skipped`
Set when subscription is intentionally not executed, e.g.:

- no explicit consent
- contact blocklisted

### `error`
Set when subscription process fails validation or API call.

Companion error key:

```text
_bw_brevo_error_last
```

Stores latest error message for troubleshooting.

---

## Consent and Metadata

Checkout consent metadata:

- `_bw_subscribe_newsletter`
- `_bw_subscribe_consent_at`
- `_bw_subscribe_consent_source`

Audit/processing metadata:

- `_bw_brevo_subscribed`
- `_bw_brevo_error_last`

---

## Compliance and Safety Requirements

### Explicit Consent Only
No subscription call must be performed when user did not opt in.

### No Automatic Resubscribe
When resubscribe policy is `no_auto_resubscribe`, blocklisted contacts must not be silently re-added.

### Logging Requirements
All outcomes (success/skip/error) must be traceable through Woo logger source `bw-brevo` with order and contact context.

### GDPR Considerations

- Avoid pre-checked consent where not legally allowed by jurisdiction.
- Keep privacy text explicit and channel-specific.
- Maintain auditable timestamp/source of consent.
- Ensure unsubscribe and preference management are honored in Brevo lifecycle.

---

## Migration and Backward Compatibility

### Migration Strategy
On admin initialization:

1. If legacy option exists and new options are missing, map values into new options.
2. Keep legacy option readable as fallback to avoid breaking existing live sites.

### Backward Compatibility Notes

- Old admin route `Checkout -> Subscribe` is deprecated.
- Current behavior: it shows a notice and points to `Mail Marketing -> Checkout`.
- Editable settings are centralized in Mail Marketing tabs.

---

## Known Constraints

- Checkout integration supports classic checkout only (not Checkout Block).
- Placement settings may be constrained by checkout template rendering order if template hardcodes field sequence.
- “Unsubscribed” semantics are only partially represented by current blacklisted check.

---

## Future Roadmap

### Channel Expansion

- Footer newsletter module
- Popup newsletter module
- My Account newsletter toggle
- OAuth/social login soft opt-in flow

### Platform Hardening

- Introduce centralized `BW_Subscribe_Service`
- Expand no-resubscribe guard to all Brevo opt-out states
- Standardize state transition policy across all channels
- Add automated integration tests for paid/created/DOI flows

### Observability

- Structured log payload conventions
- Optional admin diagnostics page for subscription events
- Better correlation between order/user/contact events

---

## Legacy Notes (Deprecated Behavior)

Deprecated admin IA:

- `Blackwork Site -> Checkout -> Subscribe`

Deprecated primary option model:

- `bw_checkout_subscribe_settings`

Status:

- Deprecated but still supported as migration/fallback source.
- New development must target:
  - `bw_mail_marketing_general_settings`
  - `bw_mail_marketing_checkout_settings`
