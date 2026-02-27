# Redirect Engine — v2 Specification

## 1. Scope
The Redirect Engine v2 defines deterministic internal routing redirections configured from the Blackwork admin panel.

This module is classified as **Tier 0 Routing Authority**.

Responsibility boundaries:
- The module SHALL evaluate redirect rules for eligible frontend requests.
- The module SHALL enforce protected-route and safety policies before any redirect execution.
- The module SHALL provide deterministic, auditable matching behavior.

Non-responsibilities:
- The module MUST NOT redefine payment, order, auth, provisioning, or consent authority.
- The module MUST NOT override protected commerce and platform-critical routes.
- The module MUST NOT act as a generic external redirect broker.

## 2. Redirect Types
Redirect Engine v2 SHALL support:
- `301` (Permanent Redirect)
- `302` (Temporary Redirect)

Admin behavior:
- Admin UI MUST provide explicit status-code selection per rule.
- Default status code MUST be `301`.
- Status value MUST be validated server-side to allow only `301` or `302`.

Runtime behavior:
- Runtime MUST emit the configured rule status code.
- Invalid or missing status code in persisted data MUST fallback to `301`.

## 3. Protected Route Policy (Critical)
The following route families are immutable protected surfaces:
- `/wp-admin`
- `/wp-login.php`
- `/cart`
- `/checkout`
- `/my-account`
- `/wc-api`
- REST routes (`/wp-json/*` and equivalent REST base paths)
- Any WooCommerce core page route returned by `wc_get_page_permalink()`
- Any WooCommerce account/checkout endpoint derived from configured Woo endpoints

Critical rules:
- Redirect Engine MUST NOT allow rule creation where **source** matches a protected route.
- Redirect Engine MUST NOT allow rule creation where **target** matches a protected route.
- Redirect Engine MUST NOT execute a redirect when protected-route gate fails at runtime.
- Admin validation MUST reject protected-route violations with explicit per-row error messages.

The protected-route policy SHALL be treated as non-overridable in normal admin operations.

## 4. Loop Prevention Model
Redirect Engine v2 SHALL enforce loop prevention at save-time and runtime.

Save-time validation:
- Self-loop detection MUST reject `source == target` after normalization.
- Direct two-node loop detection MUST reject `A -> B` if `B -> A` exists (enabled rules scope).
- Multi-hop cycle detection MUST run on the full enabled rule graph and MUST reject any cycle.

Runtime protection:
- Runtime SHALL maintain a redirect hop counter for the current request chain.
- Maximum hop limit SHALL be `5`.
- If hop limit is exceeded, redirect execution MUST abort and request MUST continue without additional redirect.

Determinism requirements:
- Loop checks MUST run against normalized canonical source/target keys.
- Disabled rules MUST NOT participate in runtime graph.

## 5. Matching Model
Supported match types:
- `exact` (default)
- `prefix` (optional, explicit per-rule flag)
- `wildcard` (optional, explicit syntax and explicit per-rule enable)

Exact matching:
- MUST use normalized source key comparison.
- MUST be deterministic and case policy MUST be defined globally (default: case-sensitive path, exact query compare).

Prefix matching:
- MUST only run when rule `match_type=prefix`.
- MUST apply longest-prefix-wins policy.

Wildcard matching:
- SHALL be disabled by default.
- If enabled, wildcard syntax MUST be explicit and documented (for example `*` segment wildcard).
- Wildcard processing MUST be deterministic and bounded.

Normalization policy:
- Leading slash MUST be normalized for path keys.
- Query parameters MUST be normalized into canonical order before match evaluation.
- Trailing slash policy MUST be explicit and global:
  - Either strict mode (trailing slash significant), or
  - canonical mode (trailing slash normalized).

v2 default policy:
- Query canonicalization SHALL be enabled.
- Trailing slash SHALL be normalized to canonical path form for matching.

## 6. Precedence Model
Redirect evaluation order SHALL be deterministic and globally enforced:

1. WordPress canonical handling
2. Protected route gate
3. Blackwork Redirect Engine
4. WooCommerce endpoint routing

Hooking requirements:
- Redirect Engine MUST be attached at a priority that preserves this order.
- If WordPress canonical processing is on `template_redirect` default priority, Redirect Engine SHALL run after canonical completion and before Woo endpoint-specific handlers.
- Runtime MUST document and enforce final hook priority used.

Conflict policy:
- If protected route gate blocks evaluation, Blackwork redirect MUST NOT execute.
- If no redirect rule matches, runtime MUST pass control to downstream Woo routing.

## 7. Performance Model
Redirect Engine v2 performance guardrails:

Indexing:
- Exact-match rules MUST be indexed by normalized source key in hash-map form.
- Exact lookup MUST be O(1) average-case.
- Prefix/wildcard rules MUST be stored in dedicated structures separate from exact index.

Evaluation path:
- Exact index check SHALL run first.
- Prefix/wildcard evaluation SHALL run only if exact did not match.

Guardrails:
- Admin MUST show warning when active rule count exceeds configured threshold.
- Default warning threshold SHALL be `500` rules.
- Runtime MUST avoid full linear scan for exact rules.

Caching:
- An optional transient/object-cache snapshot MAY be used.
- Cache invalidation MUST occur on any create/update/delete/enable/disable operation.

## 8. Storage Model
Redirect Engine v2 SHALL persist data in a versioned option schema.

Primary option key:
- `bw_redirects_v2`

Schema requirements per rule:
- `id` (stable unique rule ID)
- `source` (raw input)
- `source_normalized` (canonical key)
- `target` (sanitized destination)
- `status_code` (`301|302`)
- `match_type` (`exact|prefix|wildcard`)
- `enabled` (`0|1`)
- `created_at` (UTC timestamp)
- `updated_at` (UTC timestamp)

Top-level metadata:
- `schema_version`
- `rules`
- `updated_at`

Example:
```php
[
  'schema_version' => 2,
  'updated_at'     => '2026-02-27T12:00:00Z',
  'rules'          => [
    [
      'id'                => 'r_01JABCDEF123',
      'source'            => '/promo/black-friday',
      'source_normalized' => '/promo/black-friday',
      'target'            => '/collections/black-friday/',
      'status_code'       => 301,
      'match_type'        => 'exact',
      'enabled'           => 1,
      'created_at'        => '2026-02-27T11:45:00Z',
      'updated_at'        => '2026-02-27T11:45:00Z',
    ],
  ],
]
```

## 9. Admin UX Requirements
Validation and feedback:
- Admin MUST provide per-row validation errors on save.
- Duplicate normalized source keys MUST be rejected.
- Protected-route violations MUST show explicit blocking warning.
- Loop/cycle violations MUST show explicit blocking warning.

Ordering:
- If order affects only prefix/wildcard conflict resolution, ordering UI MUST be enabled only for those rule classes.
- Drag-and-drop ordering MUST NOT be exposed when runtime order is irrelevant.

External destination safety:
- Admin MUST require explicit confirmation for external-host target rules.
- External-host rules MUST be clearly labeled in UI and audit logs.

Usability constraints:
- Disabled rules MUST remain visible and editable.
- Rule ID MUST be visible in advanced details for traceability.

## 10. Security Model
Access control:
- Create/update/delete/enable/disable operations MUST require `manage_options` capability.
- All mutating requests MUST require valid nonce.

Input/output hardening:
- Source and target MUST be sanitized server-side.
- Output in admin UI MUST be escaped contextually.
- Protocol-relative targets (`//example.com`) MUST be rejected.
- Invalid schemes MUST be rejected.

Host policy:
- Internal redirects SHOULD be default.
- External host redirects SHALL require explicit allowlist validation policy.
- Runtime MUST use safe redirect primitives (`wp_safe_redirect` or equivalent host-validated mechanism).

Header safety:
- Redirect target values MUST be validated to prevent header injection vectors.

## 11. Observability
Runtime observability:
- Optional debug mode SHALL be available.
- When enabled, runtime SHOULD log matched rule ID, match type, source key, and status code.

Admin auditability:
- Rule create/update/delete/enable/disable operations MUST generate admin audit entries.
- Audit entry MUST include actor, timestamp, rule ID, and action type.

Operational counters:
- System SHOULD expose matched-rule counters for diagnosis.
- Counter resets and retention policy MUST be documented.

## 12. Regression Gates
The following tests are mandatory release gates for Redirect Engine v2:

1. Protected commerce routes:
- Verify Redirect Engine cannot override `/checkout`, `/cart`, `/my-account`.

2. Admin and platform routes:
- Verify Redirect Engine cannot override `/wp-admin`, `/wp-login.php`, REST base, and `/wc-api` surfaces.

3. Loop safety:
- Verify self-loop, direct loop, and multi-hop cycle are blocked at save-time.
- Verify runtime hop guard aborts after max 5 hops.

4. Deterministic matching:
- Verify exact match is deterministic with normalized query/trailing-slash policy.
- Verify prefix/wildcard deterministic behavior (if enabled).

5. No authority leakage:
- Verify redirects do not mutate payment/order/provisioning/consent authority surfaces.

6. Performance gate:
- Verify deterministic behavior and acceptable response profile with at least 500 active rules.

7. Precedence gate:
- Verify precedence order is preserved: canonical -> protected gate -> redirect engine -> Woo routing.

8. Security gate:
- Verify nonce/capability enforcement for admin mutations.
- Verify protocol-relative and invalid-host targets are rejected per policy.
