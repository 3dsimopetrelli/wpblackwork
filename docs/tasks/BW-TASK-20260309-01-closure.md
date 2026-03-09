# BW-TASK-20260309-01 — Blackwork Site Panel Realignment (Post-Rollback)

## Task Identification
- Task ID: `BW-TASK-20260309-01`
- Title: Restore Blackwork Site admin panel alignment to Shopify-style documented baseline
- Date: `2026-03-09`
- Status: Closed (implemented and validated)

## Scope
- Domain: Admin panel governance alignment (no storefront/runtime commerce behavior changes).
- Trigger: rollback drift affected admin authority files beyond intended Supabase rollback scope.

## Change Summary
- Restored panel authority files to the governed baseline:
  - `admin/class-blackwork-site-settings.php`
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
- Recovered panel-level invariants:
  - explicit `Site Settings` submenu alias under `blackwork-site-settings`
  - deterministic top-level landing via submenu ordering guard
  - `Mail Marketing` submenu registration under `Blackwork Site`
  - scoped admin UI kit gating (`bw_is_blackwork_site_admin_screen` + `bw_admin_enqueue_ui_kit_assets`)
  - page/tab-restricted Site Settings asset matrix in `bw_site_settings_admin_assets(...)`

## Validation Summary
- PHP syntax validation:
  - `php -l admin/class-blackwork-site-settings.php` -> PASS
  - `php -l includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php` -> PASS
- Project lint gate:
  - `composer run lint:main` -> PASS
- Scope safety:
  - no additional runtime/storefront files modified for this realignment task

## Governance Notes
- Decision log updated: Entry 032 in `docs/00-planning/decision-log.md`.
- Admin guide updated with post-rollback realignment note in `docs/20-development/admin-panel-map.md`.
- No new authority surface introduced.
- No ADR required (restorative alignment to existing baseline, not an architectural policy change).

## Closure Declaration
`BW-TASK-20260309-01` is closed with documentation alignment and validation evidence.

