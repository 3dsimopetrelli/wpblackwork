# Newsletter Subscription Widget

## Purpose
`bw-newsletter-subscription` is the canonical fixed-design Mail Marketing opt-in widget for Elementor surfaces.

It is the current reusable site-wide capture entrypoint for Brevo outside WooCommerce checkout.

Primary goals:
- reuse Mail Marketing/Brevo configuration without duplicating logic in Elementor
- keep submit handling GDPR-safe
- avoid client-side exposure of Brevo credentials
- remain visually opinionated and operationally deterministic

## Runtime Authority
Widget file:
- `includes/widgets/class-bw-newsletter-subscription-widget.php`

Channel runtime:
- `includes/integrations/brevo/class-bw-mailmarketing-subscription-channel.php`

Shared service dependencies:
- `includes/integrations/brevo/class-bw-mailmarketing-service.php`
- `includes/integrations/brevo/class-bw-brevo-client.php`

Frontend assets:
- `assets/css/bw-newsletter-subscription.css`
- `assets/js/bw-newsletter-subscription.js`

Admin authority:
- `Blackwork Site -> Mail Marketing -> Subscription`
- settings option: `bw_mail_marketing_subscription_settings`

## Widget Contract
Widget slug:

```text
bw-newsletter-subscription
```

Editor title:

```text
Newsletter Subscription
```

Category:

```text
blackwork
```

## Editor Controls
The widget deliberately exposes only minimal per-instance content controls.

Current controls:
- `Show name field`
- `Consent text`
- `Name float label`
- `Email float label`

Not intentionally exposed:
- design system/style controls for this custom runtime
- Brevo list selection
- API behavior
- opt-in mode
- response copy authority

Reason:
- channel behavior must remain centralized in Mail Marketing settings
- the widget should stay reusable and deterministic across footer/pages/templates

## Mail Marketing Dependency
The widget does not own Brevo configuration.

It reads channel settings from:

```text
bw_mail_marketing_subscription_settings
```

Key fields used at runtime:
- `enabled`
- `source_key`
- `list_mode`
- `list_id`
- `channel_optin_mode`
- `consent_required`
- `privacy_url`
- `name_label`
- `email_label`
- `consent_prefix`
- `privacy_link_label`
- `button_text`
- `success_message`
- `error_message`
- `consent_required_message`

Runtime-ready message keys already supported:
- `empty_email_message`
- `invalid_email_message`
- `already_subscribed_message`
- `loading_message`
- `rate_limited_message`

## Rendering Behavior
The widget renders:
- optional Name field
- Email field
- privacy checkbox
- submit button
- live-region message area

When the channel is disabled:
- frontend: widget does not render
- Elementor editor: widget remains visible with a preview notice for layout work

## Submit Flow
1. User submits the widget.
2. Frontend JS validates:
   - empty email
   - invalid email
   - missing consent when required
3. JS sends AJAX to `admin-ajax.php` with:
   - `action=bw_mail_marketing_subscribe`
   - `nonce`
   - normalized email
   - optional name
   - consent flag
4. Server validates again.
5. Cooldown gate runs before Brevo write.
6. Brevo contact lookup checks:
   - already subscribed to list
   - blocklisted/unsubscribed semantics
7. Effective mode is resolved:
   - single opt-in
   - double opt-in
8. Brevo call executes server-side.
9. Frontend receives a structured safe JSON response.

## Response Code Contract
Stable response codes:
- `empty_email`
- `invalid_email`
- `missing_consent`
- `rate_limited`
- `already_subscribed`
- `success`
- `generic_failure`

These codes are the public contract for the widget runtime and should remain stable unless documentation is updated.

## Security Model
Implemented protections:
- nonce validation
- server-side-only Brevo API calls
- deterministic email normalization
- server-side consent enforcement
- transient cooldown keyed by normalized email + request IP hash
- generic customer-facing failures
- detailed provider errors logged server-side only

Not implemented:
- CAPTCHA
- honeypot
- queue-based retry worker

## Accessibility Baseline
Implemented:
- explicit label/input pairing
- `aria-describedby`
- `aria-invalid`
- `aria-disabled`
- `aria-live` message region
- `<noscript>` fallback message

## Asset Loading Model
Normal Elementor path:
- widget declares `get_style_depends()` and `get_script_depends()`

Custom footer exception:
- Theme Builder Lite footer templates can be injected late in `wp_footer`
- the channel runtime pre-enqueues widget assets during `wp_enqueue_scripts` when the active footer contains `bw-newsletter-subscription`

Purpose:
- keep frontend/footer output styled correctly
- avoid CSS/JS missing due to late render timing

## Brevo Data Model
The widget uses the same Brevo data model as the broader Mail Marketing system.

Minimum attributes sent:
- `SOURCE`
- `CONSENT_SOURCE`
- `CONSENT_AT`
- `CONSENT_STATUS`
- `BW_ORIGIN_SYSTEM`
- `BW_ENV`

Name attributes:
- `FIRSTNAME`
- `LASTNAME`

Source value:
- defaults to `elementor_widget`
- can be overridden through the Subscription tab

## Current Limitations
- fixed-design only
- JS is required for submit
- no local entity meta surface equivalent to Woo orders
- full message schema exists in runtime, but not all message keys are exposed in the current admin UI

## Documentation Links
- `docs/40-integrations/brevo/brevo-mail-marketing-architecture.md`
- `docs/40-integrations/brevo/subscribe.md`
- `docs/40-integrations/brevo/brevo-architecture-map.md`
- `docs/40-integrations/brevo/mail-marketing-qa-checklist.md`
- `docs/20-development/admin-panel-map.md`
