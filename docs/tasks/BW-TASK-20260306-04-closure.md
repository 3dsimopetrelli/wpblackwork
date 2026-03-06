# Blackwork Governance — Task Closure Artifact

## 1) Task Identification
- Task ID: `BW-TASK-20260306-04`
- Task title: Redirect Engine Authority Hardening
- Domain: Redirect / Routing Authority
- Tier classification: 0
- ADR reference: `docs/60-adr/ADR-007-redirect-authority-hardening.md`

## 2) Scope
Implemented scope:
- `includes/class-bw-redirects.php`
- `docs/00-governance/risk-register.md`
- `docs/tasks/BW-TASK-20260306-04-closure.md`

Out of scope respected:
- No WooCommerce routing module changes
- No auth/supabase module changes
- No frontend JS changes
- No unrelated module refactor

## 3) Implementation Summary
Implemented hardening controls in redirect engine runtime and save-time option guard:

0. Canonical normalization consistency
- Enforced single canonical normalization function: `bw_normalize_redirect_path()`.
- Function now normalizes deterministically across all critical surfaces:
  - save-time rule validation
  - runtime request matching
  - protected-route detection
  - loop/two-node checks
  - redirect target-path validation
- Canonicalization policy includes:
  - single leading slash
  - duplicated slash collapse
  - deterministic trailing slash normalization
  - fragment exclusion
  - lowercase canonical path
  - path-node comparison independent from query ordering/representation

1. Protected route gate
- Added protected families policy:
  - `/wp-admin`
  - `/wp-login.php`
  - `/wp-json/*`
  - `/cart`
  - `/checkout`
  - `/my-account`
  - `/wc-api`
- Enforced both at save-time (rule sanitation) and runtime (request/target guard).
- On protected-route match: redirect is skipped (fail-open).

2. Hook priority hardening
- Redirect hook moved from `template_redirect@5` to `template_redirect@10` (ADR policy: `>=10`).

3. Loop detection
- Save-time:
  - Reject self-loop (`source == target` after normalization).
  - Reject direct two-node loop (`A->B` and `B->A`).
- Runtime:
  - Direct reverse-loop guard for matched source/target pair.

4. Redirect hop limit
- Added runtime hop limit guard with `max_redirect_hops = 5`.
- Hop count tracked with short-lived cookie; if limit reached, redirect chain aborts fail-open.

5. Fail-open safety
- Any unsafe condition now aborts redirect and continues normal request lifecycle.

6. Deterministic precedence and evaluation
- Source rules are normalized and indexed (first valid source wins) before match check.
- Single deterministic exact-match lookup by normalized request key.

7. Performance safety
- Per-request indexed map avoids repeated comparisons after build.
- Duplicate/invalid/unsafe rules are skipped early.

## 4) Evidence
- Canonical normalization single-source evidence:
  - `bw_normalize_redirect_path()` is reused in save-time filter and runtime matcher/guards.
  - Equivalent representations converge to same canonical node (examples: `/account`, `/account/`, `/account?foo=1`, `https://example.com/account`).
- Protected-route guard implemented by:
  - `bw_get_protected_redirect_route_families()`
  - `bw_is_protected_redirect_path()`
  - `bw_is_protected_redirect_url()`
- Save-time guard implemented by:
  - `bw_redirects_enforce_save_time_safety()` via `pre_update_option_bw_redirects`
- Hop-limit guard implemented by:
  - `bw_get_redirect_max_hops()`
  - `bw_get_redirect_current_hop_count()`
  - `bw_set_redirect_hop_count()`
  - `bw_reset_redirect_hop_count()`
- Hook priority evidence:
  - `add_action( 'template_redirect', 'bw_maybe_redirect_request', 10 );`

## 5) Manual Verification Checklist
- [ ] Rule save: self-loop (`/a` -> `/a`) is rejected/skipped.
- [ ] Rule save: direct loop pair (`/a` -> `/b`, `/b` -> `/a`) cannot both persist.
- [ ] Protected route as source is not applied at runtime.
- [ ] Protected route as target is not redirected to.
- [ ] Redirect hook runs at priority 10.
- [ ] Normal valid redirect still executes with 301.
- [ ] No-match request passes through without side effects.
- [ ] Repeated chain over hop limit aborts and request continues (fail-open).
- [ ] Checkout/cart/my-account/auth flows remain unaffected by redirect engine.

## 6) Residual Risks
- Hop tracking via cookie is lightweight and may be imperfect under strict client cookie policies.
- Current storage model remains legacy (`bw_redirects` without full v2 schema metadata).
- Multi-hop cycle rejection at save-time is limited to direct two-node rule pairs by current scope.

## 7) Governance / Documentation Updates
- Updated risk posture for `R-RED-12` in:
  - `docs/00-governance/risk-register.md`
- No runtime hook map update in this task artifact (recommended in follow-up closure if governance requires explicit hook priority inventory sync).

## 8) Rollback Notes
- Revert file-level changes in:
  - `includes/class-bw-redirects.php`
  - `docs/00-governance/risk-register.md`
  - `docs/tasks/BW-TASK-20260306-04-closure.md`
- No database migration rollback required.
