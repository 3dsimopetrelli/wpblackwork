# Supabase Create Password Audit (2026-02-13)

## Scope
- Reviewed: `SUPABASE_CREATE_PASSWORD.md`
- Checked linked files:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-supabase-bridge.js`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-password-modal.css`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`

## Result
- Core flow is wired correctly (bridge + AJAX endpoints + modal rendering conditions).
- All linked files exist.
- One functional inconsistency found in password policy validation.

## Finding
1. UI password rules and backend requirements are inconsistent.
   - UI accepts: length + uppercase + (number OR special char):
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-account-page.js:381`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js:116`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php:493`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php:506`
   - Backend `bw_supabase_create_password` requires: length + lowercase + uppercase + number + special char:
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php:895`
   - Impact: valid UI passwords can be rejected server-side during OTP signup create-password flow.

2. Modal endpoint uses weaker backend policy than create-password endpoint.
   - `bw_set_password_modal` checks only length >= 8:
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php:1498`
     - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php:1530`
   - Impact: different behavior between onboarding paths.

## Notes
- Runtime lint/tests could not be executed in this environment because `php` and `node` binaries are not available in PATH.

---

## Addendum (2026-02-18) - Post-Audit Stabilization

After the initial audit, the checkout -> invite -> create-password flow was hardened to remove My Account transition glitches.

### Implemented hardening
- Early callback/preload guard in head:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Bridge transition-state management (`bw_auth_in_progress`):
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-supabase-bridge.js`
- Callback rendering guards in account templates:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/my-account.php`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`
- Logged-in stale callback URL cleanup:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`

### Resolved regressions
1. Guest flow flash of logged-out My Account before popup.
2. Logged-in refresh showing `Completing sign-in` on stale `?bw_auth_callback=1`.

### Current canonical reference
- Use:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/SUPABASE_CREATE_PASSWORD.md`
- This file now includes:
  - full target behavior,
  - stable baseline rules,
  - mandatory regression checklist,
  - debug-first smoke matrix.
