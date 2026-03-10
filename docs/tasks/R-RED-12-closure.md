# R-RED-12 — Redirect Authority Drift Closure

## Task Identification
- Task ID: `R-RED-12`
- Title: Redirect authority drift
- Domain: Redirect / Routing Authority
- Status: `CLOSED`
- Closure date: `2026-03-10`

## Allowed Scope (Implemented)
- Strict non-Supabase redirect surfaces only.
- Maintenance-mode redirect precedence clarification.

## Frozen / Excluded Scope
- `includes/woocommerce-overrides/class-bw-my-account.php`
- `includes/woocommerce-overrides/class-bw-supabase-auth.php`
- Any `bw_supabase_*` symbol/surface
- Any auth/session/login/logout/account redirect flow

## File Changed
- `BW_coming_soon/includes/functions.php`

## Runtime Surface Touched
- `template_redirect` hook precedence for `bw_show_coming_soon`.

## Implemented Change
- From:
  - `add_action('template_redirect', 'bw_show_coming_soon');`
- To:
  - `add_action('template_redirect', 'bw_show_coming_soon', 1);`

## Determinism Improvement
- Before: `bw_show_coming_soon()` and `bw_maybe_redirect_request()` could both execute at priority `10`, leaving authority to registration order.
- After: Coming Soon gate executes first (`@1`) and becomes explicit authority in anonymous maintenance-mode requests.
- Generic redirect engine remains unchanged.

## Validation Summary
- `php -l BW_coming_soon/includes/functions.php` -> PASS
- `composer run lint:main` -> PASS
- Manual regression checklist: pending/required in operations protocol.

## Supabase Freeze Verification
- No Supabase/auth surfaces touched.
- Freeze policy respected throughout implementation and closure.

## Final Status Classification
- Non-Supabase redirect scope: `MITIGATED`
- Auth/Supabase redirect scope: `FROZEN` (deferred to later dedicated review)

## Governance Sync
- Updated:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
  - `docs/50-ops/regression-protocol.md`
