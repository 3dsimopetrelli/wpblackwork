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

