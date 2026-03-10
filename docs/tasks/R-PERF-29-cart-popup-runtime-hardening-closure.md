# R-PERF-29 — Cart Popup Runtime Scope Hardening + Checkout Suppression Closure

## Task Identification
- Task ID: `R-PERF-29`
- Title: Cart Popup runtime scope hardening + checkout suppression
- Domain: Cart Popup / Frontend Runtime / Admin Settings
- Closure date: `2026-03-10`
- Final task status: `CLOSED`
- Final risk status: `MITIGATED`

## Protocol Reference
- Closure executed following: `docs/governance/task-close.md`

## Files Changed
- `cart-popup/cart-popup.php`
- `cart-popup/frontend/cart-popup-frontend.php`
- `cart-popup/admin/settings-page.php`
- `admin/class-blackwork-site-settings.php`

## Runtime Surfaces Touched
- Cart Popup runtime-needed guard and frontend load gating.
- Cart Popup assets enqueue path.
- Cart Popup panel render path in footer.
- Cart Popup dynamic CSS output path in head.
- Cart Pop-up admin tab option save/render in unified Blackwork Site Panel.

## Issue Fixed
- Cart Popup runtime surfaces were loaded globally more broadly than necessary.
- Checkout showed a floating popup entrypoint that is undesired by default.

## Hardening Implemented
- Introduced centralized runtime-needed guard usage across:
  - `bw_cart_popup_enqueue_assets()`
  - `bw_cart_popup_render_panel()`
  - `bw_cart_popup_dynamic_css()`
- Added admin setting:
  - key: `bw_cart_popup_disable_on_checkout`
  - label: `Disabilita Cart Pop-Up in checkout`
  - default: enabled (`1`)
- When enabled on checkout:
  - floating icon suppressed
  - panel markup suppressed
  - popup CSS/JS suppressed
  - popup runtime unavailable
- Outside checkout, behavior remains unchanged.

## Validation Summary
- `php -l cart-popup/cart-popup.php` -> PASS
- `php -l cart-popup/frontend/cart-popup-frontend.php` -> PASS
- `php -l admin/class-blackwork-site-settings.php` -> PASS
- `composer run lint:main` -> PASS
- Manual regression checklist: completed for checkout suppression, non-checkout parity, widget/header/cart integration, and admin option persistence.

## Supabase Freeze Verification
- Freeze respected: YES
- Any `bw_supabase_*` surface touched: NO
- Any auth/session/My Account integration touched: NO

## Governance Synchronization
- Updated:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
  - `docs/50-ops/regression-protocol.md`

## Closure Declaration
Cart Popup runtime scope hardening and checkout suppression are complete with minimal scope and no Supabase/auth blast radius. `R-PERF-29` remains `MITIGATED` with monitoring only, and this governed task is `CLOSED`.
