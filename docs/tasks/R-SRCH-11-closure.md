# R-SRCH-11 — Search Runtime Coupling Risk Closure

## Task Identification
- Task ID: `R-SRCH-11`
- Title: Search runtime coupling risk
- Domain: Search / Header Runtime
- Final task status: `CLOSED`
- Final risk status: `MITIGATED`
- Closure date: `2026-03-10`

## Scope and Runtime Surfaces
- File changed:
  - `includes/modules/header/frontend/ajax-search.php`
- Runtime surface touched:
  - Header live-search visibility tax-query construction (`bw_header_live_search_build_visibility_tax_query`)
- Out-of-scope preserved:
  - Supabase/auth/session/My Account integrations
  - Checkout/payment runtime
  - Search overlay UX contract and client JS behavior

## Issue Fixed
- Previous live search visibility filtering excluded both `exclude-from-search` and `exclude-from-catalog`.
- This could hide products that remain searchable but are catalog-hidden, causing semantic drift from WooCommerce search behavior.

## Implemented Change
- Search-context visibility exclusion now applies only `exclude-from-search`.
- `exclude-from-catalog` is no longer excluded in the live-search tax query.
- Guest and logged-in AJAX endpoint behavior remains unchanged.

## Determinism / Semantics Alignment
- Live search now aligns with WooCommerce search visibility semantics more closely.
- Searchable products are no longer hidden incorrectly in live results due to catalog-only visibility exclusion.

## Validation Summary
- `php -l includes/modules/header/frontend/ajax-search.php` -> PASS
- `composer run lint:main` -> PASS
- Manual regression checklist -> PASS
  - live search finds searchable-but-catalog-hidden products
  - `exclude-from-search` products remain excluded
  - category/type filters still work
  - guest/logged-in AJAX responses still work
  - no runtime UI errors

## Supabase Freeze Verification
- Supabase freeze respected: YES
- Supabase/auth surfaces touched: NONE
- `bw_supabase_*` surfaces touched: NONE

## Governance Synchronization
- Updated:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
  - `docs/50-ops/regression-protocol.md`
- Closure protocol followed:
  - `docs/governance/task-close.md`

## Closure Declaration
`R-SRCH-11` mitigation is implemented and validated with minimal scope. Risk status is `MITIGATED`, and the governed task is `CLOSED`.
