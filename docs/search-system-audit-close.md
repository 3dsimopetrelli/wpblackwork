# Search System Audit Close

## 1. Overview

The current Search system includes three related but distinct parts:

- the shared Search Engine
- Search Surface, which owns the overlay and the plugin-owned `/search/` route
- header live search, which remains a separate consumer

This hardening phase focused on:

- performance stabilization
- runtime reliability
- boundary clarity
- code cleanup

The purpose of this phase was not to redesign Search architecture. It was to make the existing system cheaper, safer, and easier to maintain without changing ownership boundaries.

## 2. Final Architecture (Authoritative State)

### Search Engine

- The Search Engine is the single shared engine for Search Surface consumers.
- It supports different request profiles, including `suggest` and `full`.
- The engine remains the authority for `/search/` and Search Surface-driven result rendering.

### Search Surface

- Search Surface owns the overlay and the `/search/` page.
- `/search/` is plugin-owned.
- Search Surface is not owned by Elementor or by the Product Grid widget runtime.

### Header Search

- Header live search remains a separate consumer path.
- It still uses `WP_Query` with the native `s=` search parameter.
- It has been hardened for performance and safety, but it has not been merged into the shared engine.

### Product Grid Widget

- The Product Grid widget remains a separate runtime.
- It is not the owner of Search.
- It may share styling or engine-adjacent contracts, but it does not own Search Surface or `/search/`.

The current separation between Search Surface and Product Grid is intentional and must be preserved.

## 3. Completed Fixes

### Performance & Scalability

- `F02` — header live search no longer performs explicit per-result N+1 `wc_get_product()` hydration; product payload building now uses bulk product loading.
- `F03` — header live search now uses generation-based response caching.
- `F04` — header live search now uses rate limiting / throttling.
- `F08` — paginated `/search/` results now use a single shared-engine execution instead of a second page-1 execution for filter UI.
- `F09` — the shared rate limiter is now object-cache-aware and cheaper under persistent object cache.
- `F10` — cache invalidation is more narrowly scoped by product family where repository evidence makes that safe.

### Runtime & Reliability

- `F11` — `/search/` URL-state parsing now sanitizes input values at the boundary before state construction.
- `F12` — Elementor frontend + active kit CSS dependency on `/search/` is now explicit, body-class-backed, and guarded with a narrow fallback path.

### Code Quality & Cleanup

- `F13` — dead JS function `isDiscoveryResponsiveToolbar()` was removed.
- `F14` — duplicate header live-search AJAX response key was removed.
- `F15` — header live-search error messaging was localized for i18n compatibility.
- SQL `LIKE` handling in the shared text matcher was converted to the idiomatic `$wpdb->prepare()` pattern while preserving current behavior.
- Header live-search cache-hit control flow was clarified with an explicit `return;`.
- Redundant `function_exists(...)` guards were removed from `includes/modules/header/frontend/ajax-search.php` under the current `require_once` loading model.

## 4. Important Behavioral Changes

- Header search is now cached and throttled.
- `/search/` now uses a single shared-engine call even on paginated results.
- Invalidation is now scoped by product family where that scope can be derived safely.
- Elementor kit loading is explicitly enforced on `/search/`, including active kit body class and kit CSS enqueue protection.

## 5. Known Limitations (Intentionally Deferred)

### F05 — Search Engine Query Model

- The shared text matcher still uses `LIKE '%term%'`.
- There is no FULLTEXT index or dedicated search index table.
- This query model does not scale linearly with catalog growth.

This is the primary scalability limitation and requires a Phase 2 redesign.

### F07 — Dual Search Systems

- Header search still uses WordPress native search semantics.
- `/search/` and Search Surface use the custom shared engine.

This divergence is intentional for now and must not be “fixed” without a dedicated redesign.

### Save-path sync work

- `bw_fpw_sync_product_filter_meta()` still runs synchronously in the save path.
- This is acceptable for the current system.
- It may be moved to async processing in a future project.

## 6. Guardrails for Future Development

- Do NOT merge Search into the Product Grid widget.
- Do NOT introduce a second search engine.
- Do NOT change `/search/` ownership to Elementor.
- Do NOT “optimize” the `LIKE` query without a proper indexed solution.
- Do NOT remove Elementor kit loading logic from `/search/` without replacing both the active kit CSS path and the `elementor-kit-{id}` body-class contract.

## 7. Next Phase (Optional, Informational Only)

Phase 2 may include:

- indexed search via FULLTEXT or a custom search table
- convergence of header search onto the shared engine
- improved ranking and relevance tuning

This is out of scope for the current system and should be treated as a separate project.
