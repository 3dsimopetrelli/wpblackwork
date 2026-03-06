# ADR-007: Redirect Authority Hardening

## Status
Proposed

This ADR is proposed and blocks Tier 0 redirect-authority implementation until approved.

## 1) Context

The Blackwork redirect engine currently executes at `template_redirect` priority `5` via `bw_maybe_redirect_request()` in `includes/class-bw-redirects.php`.

Current runtime characteristics:

- Rule evaluation is linear (`O(n)` scan) against `bw_redirects` option data.
- Match model is exact normalized `path + query` comparison.
- Runtime safety includes only partial loop prevention (self-loop checks).
- Protected-route policy is not fully enforced as a non-overridable authority gate.
- Redirect execution can occur early in the `template_redirect` stack and may short-circuit downstream runtime.

Observed governance risk context (aligned with `R-RED-12`):

- Redirect loops and unstable chain behavior remain possible in multi-rule scenarios.
- Authority override risk exists for commerce/auth-critical routes.
- Deterministic precedence is not sufficiently constrained by explicit runtime policy.
- Performance scales linearly with rule growth.

This surface is Tier 0 Routing Authority and intersects Checkout, Payments return paths, Auth/Supabase callback flows, and My Account route continuity.

## 2) Problem Statement

The current implementation does not enforce a complete authority-safe redirect contract for a Tier 0 routing surface.

Authority risks:

- Redirect logic can execute before/against critical route ownership boundaries without a strict protected-route deny gate.
- Priority/precedence behavior can produce authority collisions with other Tier 0 `template_redirect` handlers.

Determinism risks:

- Rule matching and precedence are not governed by a fully explicit, auditable policy.
- Multi-hop cycle prevention is incomplete, allowing non-convergent redirect behavior.
- Runtime behavior under larger rulesets is not bounded by indexed exact-match policy.

Without formal authority hardening, redirect behavior can violate protected route safety, convergence expectations, and Tier 0 invariants.

## 3) Decision

Blackwork adopts the following normative Redirect Authority Hardening model.

### 3.1 Protected Route Policy (Non-overridable)

The redirect engine MUST NOT create or execute redirects where source or target intersects protected families, including at minimum:

- `/wp-admin`
- `/wp-login.php`
- `/cart`
- `/checkout`
- `/my-account`
- `/wc-api`
- REST routes (`/wp-json/*` and equivalent base)
- WooCommerce core pages and derived endpoint paths

Rules:

- Protected-route validation MUST run at save-time and runtime.
- Any protected-route violation MUST fail closed for redirect execution and fail open for request continuity (no redirect, continue normal request lifecycle).
- Protected-route policy is not user-overridable in normal admin flows.

### 3.2 Redirect Precedence Rules

Redirect authority is constrained by deterministic precedence:

1. WordPress canonical handling
2. Protected-route gate
3. Blackwork redirect evaluation
4. WooCommerce endpoint routing and downstream domain handlers

Rules:

- Redirect evaluation MUST be skipped when protected-route gate fails.
- No-match MUST pass control downstream without side effects.

### 3.3 Hook Priority Policy

For Tier 0 safety and deterministic precedence:

- Redirect runtime MUST remain on `template_redirect`.
- Redirect hook priority MUST be `>= 10`.
- Redirect hook priority `< 10` is prohibited.
- Any future priority change requires governance review and ADR supersession if it changes authority/precedence semantics.

### 3.4 Loop Detection Rules

Loop prevention MUST exist at both save-time and runtime.

Save-time rules:

- Reject self-loop (`source == target` after normalization).
- Reject direct two-node loop (`A -> B` when `B -> A` exists among enabled rules).
- Reject multi-hop cycles using enabled-rule graph cycle detection.

Runtime rules:

- Maintain deterministic hop-count protection for redirect chains.
- Abort redirect execution when hop limit is exceeded.

### 3.5 Redirect Hop Limit

- Global runtime redirect hop limit is set to `5`.
- If limit is exceeded, runtime MUST abort further redirects and continue request without redirect side effects.

### 3.6 Fail-Open Behavior

When redirect safety cannot be guaranteed, runtime MUST fail open:

- invalid/unsafe rule -> skip rule
- protected-route conflict -> skip redirect
- cycle/hop-limit breach -> abort redirect chain
- normalization/validation anomaly -> no redirect

Fail-open means preserving request continuity and downstream authority handling, never forcing a speculative redirect.

## 4) Consequences

Positive impacts:

- Stronger protection of Tier 0 authority boundaries.
- Deterministic and auditable redirect behavior.
- Reduced risk of checkout/auth/account route takeover.
- Improved convergence safety through cycle + hop controls.
- Better scalability path by requiring bounded evaluation model.

Negative impacts / costs:

- Stricter validation may reject previously accepted redirect rules.
- Additional governance and test burden for Tier 0 rollout.
- Operational complexity increases for rule management and diagnostics.
- Potential need for migration/normalization of legacy rules.

## 5) Implementation Constraints

Binding constraints for the follow-up implementation task:

- Scope MUST stay within redirect engine and directly related governance docs unless scope update is approved.
- No cross-domain authority changes are permitted (Checkout/Payments/Auth/Consent remain external authority owners).
- No hidden runtime surface mutations (new hooks/priority changes must be explicitly declared).
- Protected-route gate MUST be enforced before redirect execution.
- Existing request continuity MUST be preserved via fail-open behavior.
- Matching and precedence behavior MUST be deterministic and documented.
- Implementation MUST include regression evidence for:
  - protected route non-overriding
  - loop/cycle prevention
  - precedence consistency
  - route continuity in commerce/auth paths
- Performance-sensitive changes MUST record lightweight closure evidence per governance protocol.

## 6) Governance Notes

This decision requires an ADR because it modifies Tier 0 Routing Authority behavior, specifically:

- redirect precedence semantics
- `template_redirect` priority policy
- protected-route authority enforcement
- loop/cycle convergence rules

Per governance doctrine:

- Tier 0 authority-impacting changes require explicit architectural decisioning.
- Redirect authority MUST NOT override protected commerce/auth/platform routes.
- Determinism and convergence are mandatory invariants, not implementation preferences.

This ADR provides the binding architecture contract that unblocks governed implementation once approved, and it anchors required updates to risk posture (`R-RED-12`) and runtime documentation.
