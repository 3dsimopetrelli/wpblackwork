# R-ADM-18 — Admin Diagnostics Integrity Risk Closure

## Task Identification
- Task ID: `R-ADM-18`
- Title: Admin diagnostics integrity risk
- Module/Domain: Admin / System Status diagnostics runtime
- Closure date: `2026-03-10`
- Final task status: `CLOSED`
- Final risk status: `MITIGATED`

## Files Changed
- `includes/modules/system-status/runtime/check-runner.php`
- `includes/modules/system-status/admin/assets/system-status-admin.js`
- `includes/modules/system-status/runtime/checks/check-database.php`

## Runtime Surfaces Touched
- System Status snapshot authority and metadata (`bw_system_status_build_snapshot`).
- System Status admin freshness/source indicator rendering.
- Database fallback diagnostics accumulation path.

## Issue Fixed
- Scoped diagnostics refreshes could overwrite global freshness metadata (`generated_at`, `execution_time_ms`, source semantics) even when only one section was recomputed.
- This made mixed-freshness snapshots appear fully live.

## Determinism / Freshness Improvement
- Added explicit partial refresh metadata:
  - `is_partial_refresh`
  - `refreshed_checks`
  - `last_full_generated_at`
- Full runs update `last_full_generated_at`.
- Partial runs preserve full-run timestamp authority and mark payload as partial.
- Admin UI now shows `Mixed` when payload is partial.
- DB fallback micro-hardening resets counters before `SHOW TABLE STATUS` accumulation to avoid double-count edge behavior.

## Validation Summary
- `php -l includes/modules/system-status/runtime/check-runner.php` -> PASS
- `php -l includes/modules/system-status/runtime/checks/check-database.php` -> PASS
- `composer run lint:main` -> PASS
- Manual regression checklist: completed
  - full check
  - partial section check
  - `Mixed` source indicator on partial payload
  - full refresh restores normal source behavior
  - metadata coherence checks
  - DB fallback totals/table count coherence
  - unauthorized request blocked

## Supabase Freeze Verification
- Freeze respected: YES
- Any `bw_supabase_*` surface touched: NO
- Any auth/session/My Account integration touched: NO

## Governance Synchronization
- Updated:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
  - `docs/50-ops/regression-protocol.md`
- Closure protocol followed:
  - `docs/governance/task-close.md`

## Closure Declaration
`R-ADM-18` diagnostics freshness hardening is implemented and validated with minimal scope. Risk status is `MITIGATED`, and the governed task is `CLOSED`.
