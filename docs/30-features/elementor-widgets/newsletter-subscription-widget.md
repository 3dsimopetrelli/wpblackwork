# Newsletter Subscription Widget

## Purpose
`bw-newsletter-subscription` is the governed Mail Marketing opt-in widget for Elementor surfaces.

It reuses the shared Brevo/Mail Marketing subscription channel and only changes presentation at widget level. Submit flow, validation, consent enforcement, and response handling remain centralized.

## Status
- Status: `Almost ready`
- Quality: `~9.5/10`
- Phase: `Final manual validation`
- Summary:
  - full audit, hardening, cleanup, staged CSS cleanup, and first submit-path optimization are complete
  - remaining work is limited to final manual validation of `already_subscribed` behavior after conditional pre-lookup and confirmation of required Brevo audit attributes against the real production schema

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
- option: `bw_mail_marketing_subscription_settings`

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
The widget now exposes two governed presentation variants.

### `Style Footer`
Purpose:
- current footer-style newsletter form
- minimal per-instance override surface

Controls:
- `Style` = `Style Footer`
- `Show name field`

### `Style Section`
Purpose:
- large hero/section variant for standalone newsletter blocks
- same subscription channel and field names as footer style

Content controls:
- `Style` = `Style Section`
- `Show name field`
- `Title`
- `Subtitle`
- `Background Color`
- `Section Height` (`vh`)
- `Background Image`
- `Background Image Position` (`left|center|right`)
- `Background Image Fit` (`contain|cover`)

Style-tab controls:
- `Content Position` (`left|center|right`)
- `Title Typography`
- `Title Color`
- `Subtitle Typography`
- `Subtitle Color`
- `Privacy Typography`
- `Privacy Color`
- `Overlay Color`
- `Glow Color`
- `Overlay Opacity`

Implementation note:
- `Style Section` changes only layout/visual treatment
- the form still submits through the exact same AJAX action and consent flow as `Style Footer`
- the name field now uses one canonical control model with legacy saved-value compatibility preserved in render

## Rendering Behavior
Common runtime output:
- optional Name field
- Email field
- privacy checkbox
- submit button
- live-region message area

`Style Footer`:
- renders the original stacked form layout
- keeps the legacy floating-label treatment

`Style Section`:
- renders title + subtitle above the form
- uses a dark section shell with configurable background color and optional art image
- adds an internal overlay/glow layer above the art and below the content
- integrates the submit button into the email row on desktop
- keeps the same input names, nonce, consent checkbox, and message region as the footer style

When the channel is disabled:
- frontend: widget does not render
- Elementor editor: widget stays visible with a preview notice

## Mail Marketing Dependency
The widget does not own Brevo configuration.

It reads shared channel settings from:

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
6. Existing-contact lookup runs only when the active resubscribe policy requires contact-state inspection (`no_auto_resubscribe`).
7. When lookup is active, the runtime checks:
   - already subscribed to list
   - blocklisted/unsubscribed semantics
8. Effective mode is resolved:
   - single opt-in
   - double opt-in
9. Brevo call executes server-side.
10. Frontend receives a structured safe JSON response.

## Response Code Contract
Stable response codes:
- `empty_email`
- `invalid_email`
- `missing_consent`
- `rate_limited`
- `already_subscribed`
- `success`
- `generic_failure`

These response codes are shared across both style variants.

## Accessibility Baseline
Implemented:
- explicit label/input pairing
- `aria-describedby`
- `aria-invalid`
- `aria-disabled`
- `aria-live` message region
- `<noscript>` fallback message

Variant note:
- `Style Section` visually hides field labels and relies on placeholders for the designed look, but keeps the labels in the DOM for accessibility

## Asset Loading Model
Normal Elementor path:
- widget declares `get_style_depends()` and `get_script_depends()`

Custom footer exception:
- Theme Builder Lite footer templates can be injected late in `wp_footer`
- the channel runtime pre-enqueues widget assets during `wp_enqueue_scripts` when the active footer contains `bw-newsletter-subscription`
- render-time enqueue duplication has been removed; the asset model is now:
  - widget dependency handles for normal Elementor placement
  - runtime footer detection only for late-injected footer placement

## Completed Hardening / Cleanup Highlights
- endpoint abuse protection hardened with email cooldown and IP burst throttling
- Brevo fallback hardened so required consent/source metadata cannot be silently dropped
- Woo logs hardened to avoid raw subscriber email exposure
- floating-label state moved from `:has()` dependency to JS-managed `.is-filled` state
- client submit timeout and failure recovery added
- late-injected forms now receive initial field-state sync
- unused runtime payload `privacyUrl` removed
- name-field control model unified with backward compatibility
- background color control/render behavior aligned
- staged CSS cleanup completed across utility selectors, shared rules, low-conflict specificity, and section-variant structure

## Remaining Manual Validations
- validate `already_subscribed` behavior after conditional pre-lookup optimization
- validate required Brevo audit attributes against the real production Brevo schema

## Current Limitations
- the widget remains a governed design system surface, not a free-form style builder
- JS is required for submit
- no CAPTCHA / honeypot
- background art controls are intentionally minimal and currently limited to image, horizontal position, and fit

## Documentation Links
- `docs/40-integrations/brevo/brevo-mail-marketing-architecture.md`
- `docs/40-integrations/brevo/subscribe.md`
- `docs/40-integrations/brevo/brevo-architecture-map.md`
- `docs/40-integrations/brevo/mail-marketing-qa-checklist.md`
- `docs/tasks/task-close-template.md`
