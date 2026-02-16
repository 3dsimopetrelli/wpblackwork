# Supabase Create Password - Technical Recovery Notes

## Scope
This document tracks the current "Set Password" flow for `Login Provider = Supabase` on WooCommerce My Account, including where logic lives and how to recover quickly if the flow breaks.

## Target Behavior
- Provider `wordpress`:
  - No Supabase checks.
  - No password gating modal.
  - Default WordPress/WooCommerce behavior.
- Provider `supabase`:
  - User reaches the site from Supabase confirmation/invite link.
  - Supabase token is bridged to a WordPress session.
  - On `/my-account/`, if `bw_supabase_onboarded != 1`, a blocking modal appears.
  - User sets password in Supabase, then modal closes and account becomes usable.

## Important Concept
The modal appears only for logged-in WP users.  
If the user is not logged in, modal cannot open.

## Main Files
- Bridge script:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-supabase-bridge.js`
- Bridge enqueue/localize:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Supabase token login + modal AJAX endpoints:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Modal HTML + enqueue:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`
- Modal JS:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js`
- Modal CSS:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-password-modal.css`
- Supabase login template + invite error rendering:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`

## Current Endpoints
- `bw_supabase_token_login`:
  - Uses Supabase `access_token` to fetch `/auth/v1/user`.
  - Creates/authenticates WP session and sets onboarding flags.
- `bw_get_password_status`:
  - Returns `{ enabled, needs_password }` for modal gating.
- `bw_set_password_modal`:
  - Updates password in Supabase and marks onboarding complete.
- `bw_supabase_create_password`:
  - Create-password endpoint used in signup OTP flow.

## Known Failure Modes
1. Invite link expired (`otp_expired`):
   - User cannot be authenticated from that link.
   - User is redirected to `Supabase expired link redirect URL` (default: `/link-expired/`).
   - Must request/generate a fresh invite link.

2. User lands on site but remains logged out:
   - Bridge not executed, bridge failed, or invite token invalid/expired.
   - Check `admin-ajax.php` call for `action=bw_supabase_token_login`.

3. Modal not visible even if provider is Supabase:
   - User is not logged in.
   - Or user meta `bw_supabase_onboarded` is already `1`.

## Supabase Email Template Rules (Critical)
- Use `{{ .ConfirmationURL }}` as CTA href.
- Do not replace CTA with a static `/my-account/` URL.
- Supabase allow-list must include:
  - `https://blackwork.pro/my-account/`
  - `https://blackwork.pro/my-account/set-password/` (compatibility)
  - `https://blackwork.pro/link-expired/` (expired-link fallback page)

## New Checkout Setting (Supabase Provider Tab)
- `Supabase expired link redirect URL`:
  - Default: `https://blackwork.pro/link-expired/`
  - Used when callback hash contains `error_code=otp_expired`.

## Quick Test Checklist
1. Set `Login Provider = Supabase`.
2. Create a fresh invite (do not reuse expired link).
3. Open invite link immediately.
4. Confirm user is logged in on `/my-account/`.
5. Confirm modal appears if `bw_supabase_onboarded = 0`.
6. Submit password and confirm modal closes.

## Fast Debug Checklist
1. Browser URL after click:
   - Check for `#error_code=otp_expired`.
2. Network:
   - `POST admin-ajax.php` with `action=bw_supabase_token_login`.
   - Verify HTTP status and JSON payload.
3. WP user meta:
   - `bw_supabase_onboarded`
   - `bw_supabase_invited`
   - `bw_supabase_user_id`
4. Provider setting:
   - `bw_account_login_provider` must be `supabase`.

## Rollback Strategy
If a regression appears, revert only affected files (keep small blast radius):
- Bridge layer:
  - `assets/js/bw-supabase-bridge.js`
  - `woocommerce/woocommerce-init.php`
- Modal layer:
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `assets/js/bw-password-modal.js`
  - `assets/css/bw-password-modal.css`
- Auth/AJAX layer:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`

Then retest with the "Quick Test Checklist" above.

---

## Order-Received Flow (Guest + Supabase) — Current Stable Behavior

Date of latest validated behavior: **February 16, 2026**.

### Goal
After a guest checkout (Supabase provider enabled), the user must:
1. Receive order confirmation email.
2. Receive Supabase invite/account-setup email.
3. Click invite email CTA.
4. Land in My Account flow and set password.
5. Access downloads/order history securely.

### Current UX on `/checkout/order-received/...`
- Custom hero and cards are rendered by:
  - `woocommerce/templates/checkout/order-received.php`
- Main CTA behavior:
  - If user is **not logged in** and provider/provisioning uses Supabase:
    - CTA is static reminder text (no account redirect).
    - Label: `Check your email to finish account setup`
  - Otherwise:
    - CTA links to My Account.
- "What happens next?" explains:
  - order confirmation email,
  - account setup email,
  - account access after password setup.
- Checkout logo is reused on order-received through:
  - `woocommerce/woocommerce-init.php` (`bw_mew_render_order_received_logo_header`)
  - `assets/css/bw-order-confirmation.css`

### Files that define this behavior
- Template + copy + CTA logic:
  - `woocommerce/templates/checkout/order-received.php`
- Styling/layout/responsive:
  - `assets/css/bw-order-confirmation.css`
- Logo header renderer + checkout settings reuse:
  - `woocommerce/woocommerce-init.php`
- Supabase invite/send + resend message copy:
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- My Account login template (post-checkout notices/resend block):
  - `woocommerce/templates/myaccount/form-login.php`
- My Account page styling:
  - `assets/css/bw-account-page.css`

---

## Non-Break Rules (Must Always Hold)

1. Do not remove `{{ .ConfirmationURL }}` from Supabase email template CTA.
2. Do not bypass token bridge when provider is Supabase.
3. Do not redirect guest order-received users directly into broken login loops.
4. Do not change order-received CTA semantics without updating both:
   - CTA logic in template,
   - explanatory text in "What happens next?".
5. Any CSS/layout change must be verified on desktop + mobile (especially logo/header overlap).

---

## Mandatory Regression Checklist (Run After Any Related Change)

### A. Guest checkout with Supabase enabled
1. Place new guest order with a fresh email.
2. Confirm order status reaches `processing` or `completed`.
3. Confirm debug log contains invite trace and `status 200` send to Supabase.
4. Open order-received page:
   - custom hero visible,
   - CTA text is `Check your email to finish account setup`,
   - order summary + billing box visible.

### B. Email delivery + invite link
1. Confirm two emails expected by user:
   - order confirmation,
   - account setup/invite.
2. Click invite CTA quickly.
3. Confirm My Account flow starts and password setup is available.

### C. Expired link behavior
1. Reuse old/expired invite link.
2. Confirm fallback to expired-link flow/page (no broken loop).
3. From fallback, request new invite and complete onboarding.

### D. Existing-user resend behavior
1. From post-checkout My Account notice, trigger resend for an existing account.
2. Confirm message:
   - `Your account is already active. Use Magic Link, code, or password to sign in.`
3. Confirm sign-in methods still work (Magic Link/code/password).

### E. Visual stability
1. Verify logo placement on order-received (desktop + mobile).
2. Verify hero typography/button wrapping (no overlap on narrow screens).
3. Verify boxes stack/order as expected on mobile.

---

## Quick Incident Triage (If It Breaks Again)

1. Check `debug.log` for:
   - `Supabase invite trace`,
   - `Supabase invite sent ... status 200`,
   - `BW template trace` for `checkout/order-received.php`.
2. If invite sent is 200 but email missing:
   - inspect Supabase Auth email provider health/settings and resend from dashboard.
3. If order-received layout not updated:
   - confirm deployed plugin version and clear all caches (server/CDN/plugin/browser).
4. If onboarding stuck:
   - verify bridge request `action=bw_supabase_token_login`,
   - inspect `bw_supabase_onboarded` user meta and session state.
