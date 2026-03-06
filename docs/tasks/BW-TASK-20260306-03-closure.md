# Blackwork Governance — Task Closure Template

## 1) Task Identification
- Task ID: `BW-TASK-20260306-03`
- Task title: FPW Public AJAX Scalability Hardening
- Domain: Filtered Post Wall / Public AJAX Runtime
- Tier classification: 1
- Implementation commit(s): N/A (working tree)

## 2) Implementation Summary

- What was implemented:
  - Added strict request normalization helpers for FPW endpoints (`post_type`, IDs arrays, term selectors, booleans, sort/order, image size, widget id length).
  - Introduced explicit `post_type` allowlist (`product`, `post`) with compatibility-first fallback to `product` for unknown values.
  - Removed unbounded FPW query paths by replacing `posts_per_page = -1` with capped values.
  - Added bounded pagination normalization in `bw_fpw_filter_posts` (`per_page` default 24, max 48, `page` normalized and capped).
  - Added lightweight transient-based throttle guard for nopriv traffic, fail-soft responses preserving frontend contract.
  - Replaced per-post tag term lookups with one batched `wp_get_object_terms(..., 'all_with_object_id')` aggregation on bounded post IDs.
- Modified files:
  - `blackwork-core-plugin.php`
  - `docs/00-governance/risk-register.md`
  - `docs/tasks/BW-TASK-20260306-03-closure.md`
- Runtime surfaces touched:
  - Existing AJAX callbacks only:
    - `bw_fpw_get_subcategories`
    - `bw_fpw_get_tags`
    - `bw_fpw_filter_posts`
- Hooks modified or registered:
  - No new hooks.
  - No hook priority changes.
- Database/data surfaces touched:
  - No schema changes.
  - Added transient throttle buckets (`bw_fpw_rl_*`).

## 3) Mandatory Closure Evidence

- No active unbounded `posts_per_page = -1` path:
  - `bw_fpw_filter_posts` now uses normalized bounded `$per_page` + `$page`.
  - `bw_fpw_get_filtered_post_ids_for_tags` now uses capped `bw_fpw_get_tag_source_posts_limit()`.
  - Repository check confirms no `posts_per_page => -1` remains in FPW paths.
- Invalid/unexpected `post_type` handling:
  - `bw_fpw_normalize_post_type()` enforces allowlist and normalizes unknown values to default `product`.
- Large-result requests capped:
  - Main filter endpoint: `per_page` hard max 48.
  - Tag source scan endpoint: hard max 300 post IDs.
- Throttle/rate guard observable:
  - `bw_fpw_is_throttled_request()` tracks per-action transient counters for nopriv.
  - On threshold, responses are fail-soft deterministic (`bw_fpw_send_throttled_response()`).
- Why new query path is cheaper:
  - Eliminates unbounded post scans.
  - Avoids SQL `FOUND_ROWS` (`no_found_rows => true`) in main query path.
  - Replaces N+1 term fetch loop with one batched term-relationship query.

## 4) Backward Compatibility & Fail-Open Notes

- Endpoint names/actions unchanged.
- Response schema for `bw_fpw_filter_posts` preserved (`html`, `has_posts`, `tags_html`, `available_tags`).
- Non-dangerous malformed inputs are normalized to safe defaults instead of fatal rejection.
- Nonce verification and publish-only query constraint remain active.
- No JS changes required.

## 5) Regression Surface Verification (Manual)

- Guest path:
  - Verify subcategory/tag/filter AJAX endpoints work as nopriv with valid nonce.
- Logged-in path:
  - Verify same endpoint behavior for authenticated users.
- Category/subcategory/tag filtering:
  - Validate deterministic filtered output and stable tag refresh.
- Invalid input behavior:
  - Submit invalid `post_type`, malformed arrays, invalid sort/order; confirm normalized safe response.
- Large dataset behavior:
  - Confirm filtered responses are bounded by caps and do not attempt full-catalog unbounded scans.
- Throttle behavior:
  - Burst requests above threshold and confirm deterministic fail-soft responses (no fatal errors).
- Response schema compatibility:
  - Confirm frontend continues consuming `html`, `has_posts`, `tags_html`, `available_tags`.

## 6) Documentation Alignment Verification

- `docs/00-governance/`
  - Impacted? Yes
  - Updated: `docs/00-governance/risk-register.md` (added `R-FPW-20`)
- `docs/00-planning/`
  - Impacted? No (no new standing operational policy beyond task mitigation)
- `docs/10-architecture/`
  - Impacted? No direct update in this task
- `docs/20-development/`
  - Impacted? No
- `docs/30-features/`
  - Impacted? No direct update in this task
- `docs/40-integrations/`
  - Impacted? No
- `docs/50-ops/`
  - Impacted? No direct update in this task
- `docs/60-adr/`
  - Impacted? No
- `docs/60-system/`
  - Impacted? No

## 7) Governance Artifact Updates

- Decision log updated? (`docs/00-planning/decision-log.md`): No (conditional update not triggered)
- Runtime hook map updated? (`docs/50-ops/runtime-hook-map.md`): No (hooks unchanged)
- Risk register updated? (`docs/00-governance/risk-register.md`): Yes

## 8) Residual Risks / Follow-up

- Residual risk:
  - Throttle keyed by IP+UA can over/under-throttle under NAT/shared proxies.
  - Tag source cap (300) may underrepresent tail tags in very large catalogs.
- Follow-up recommendation (doc gap):
  - FPW remains undocumented as dedicated feature module. Recommended next step:
    - add a dedicated FPW feature spec in `docs/30-features/`, or
    - update `docs/10-architecture/elementor-widget-architecture-context.md`, and/or
    - record explicit FPW gap in `docs/00-governance/docs-code-alignment-status.md`.

## 9) Closure Declaration
- Task closure status: IMPLEMENTED (pending manual runtime QA)
- Responsible reviewer: Codex
- Date: 2026-03-06
