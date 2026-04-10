# Search vNext — Runtime Dataflow & Architecture

## 1. System Overview

Search vNext is a read-only discovery engine for product retrieval, filtering, and ranking.
It orchestrates query validation, cache/index usage, and response rendering support.

Current implementation state:
- Phase 1 extraction is complete.
- The shared procedural engine now lives under `includes/modules/search-engine/`.
- Product Grid is the first migrated consumer through `adapters/product-grid/product-grid-adapter.php`.
- Header live search remains a separate legacy surface; Search Surface v2 is future work and is expected to consume the shared engine later.

Search does NOT own commerce truth.
Search MUST consume canonical product/price/stock state and MUST NOT mutate it.

---

## 2. High-Level Dataflow Diagram

```text
[User Input]
      |
      v
[Consumer UI Layer]
      |
      | (debounced request)
      v
[Consumer Adapter / AJAX Endpoint]
      |
      v
[Request Normalization]
      |
      v
[Cache Lookup]
   /           \
  v             v
[HIT]         [MISS]
  |             |
  |             v
  |       [Index Layer]
  |             |
  |             v
  |       [Engine Core Orchestration]
  |             |
  |             v
  |       [Planner / Candidates / Text Match / Advanced Filters]
  |             |
  |             v
  |       [Facet Builder + Result Set]
  |             |
  |             v
  |        [Cache Store]
  |             |
  \-------------/
        |
        v
[Adapter Response Envelope]
      |
      v
[UI Render]
```

Deterministic lifecycle rule:
- For the same normalized request and the same catalog snapshot, the runtime MUST produce an equivalent response envelope.

---

## 3. Authority Boundary Diagram

```text
+--------------------------------------------------------------+
| Tier 0 — Commerce Truth (Authoritative)                      |
|--------------------------------------------------------------|
| - Products (canonical entity state)                          |
| - Prices (canonical commerce value)                          |
| - Stock (canonical availability)                             |
+--------------------------------------------------------------+
                            ^
                            | read-only consumption
                            |
+--------------------------------------------------------------+
| Tier 1 — Search Runtime (Orchestration, Non-authoritative)   |
|--------------------------------------------------------------|
| - Shared engine module (`includes/modules/search-engine/`)   |
| - Query orchestration / planning                             |
| - Candidate resolution / text matching                       |
| - Filter application / facet datasets                        |
| - Cache lookup/store + index services                        |
+--------------------------------------------------------------+
                            ^
                            | consumer request/response
                            |
+--------------------------------------------------------------+
| Tier 2 — UI Layer (Presentation)                             |
|--------------------------------------------------------------|
| - Product Grid JS + widget shell                             |
| - Legacy header search shell                                 |
| - Future Search Surface v2 shell                             |
+--------------------------------------------------------------+
```

Authority rule:
- Tier 1 and Tier 2 MUST NOT redefine Tier 0 truth.

Current ownership boundary:
- `search-engine-module.php` owns search-domain hook registration only.
- `engine/search-engine-core.php` orchestrates the shared engine and delegates sub-operations to dedicated files.
- `adapters/product-grid/product-grid-adapter.php` owns Product Grid request validation, nonce/rate-limit checks, HTML rendering, `filter_ui_hashes` delta protocol, and the existing Product Grid response contract.
- `blackwork-core-plugin.php` is no longer the search/filter business-logic host for this domain.

---

## 4. Index Lifecycle Flow

```text
[Product Update Event]
          |
          v
[Index Invalidation]
          |
          v
[Rebuild Trigger (sync or async)]
          |
          v
[Index Rebuild Process]
          |
          v
[Index Convergence State]
```

Index convergence rule:
- Index state MUST converge deterministically after invalidation/rebuild.
- Repeated rebuild triggers for the same product snapshot MUST converge to the same index output.

---

## 5. Cache Lifecycle Flow

```text
[Normalized Query + Filters + Sort + Page]
                    |
                    v
            [Hash -> Cache Key]
                    |
                    v
        [TTL + Invalidation Rules Applied]
                    |
          +---------+---------+
          |                   |
          v                   v
     [Cache Hit]         [Cache Miss]
          |                   |
          |              [Compute + Store]
          |                   |
          +---------+---------+
                    |
                    v
              [Response Return]
```

Cache lifecycle constraints:
- Cache key MUST be derived from normalized request state.
- TTL and invalidation MUST bound staleness.
- Cache hit ratio MUST be observable as a runtime metric.
- Existing transient prefixes and Product Grid cache behavior were preserved in Phase 1.

---

## 6. Failure & Degrade Paths

```text
[Incoming Request]
       |
       v
[Validation OK?] --no--> [Validation Error Envelope] --> [UI Error State]
       |                                                   |
      yes                                                  v
       |                                           [UI remains navigable]
       v
[Cache Layer Available?] --no--> [Bypass Cache] --> [Query Engine]
       |
      yes
       |
       v
[Cache Hit?] --yes--> [Return Cached Envelope] --> [UI Render]
       |
      no
       |
       v
[Query Success?] --no--> [Query Error Envelope] --> [UI Error State]
       |                                                   |
      yes                                                  v
       |                                           [UI remains navigable]
       v
[Build Response Envelope]
       |
       v
[UI Render]
```

Degrade-safe rule:
- On validation/cache/query/endpoint failures, UI MUST remain navigable and MUST NOT block core site navigation.

---

## 7. Determinism Guarantees

- Same input + same catalog snapshot MUST produce same output (allowing telemetry variance such as `duration_ms`).
- Sorting MUST apply deterministic tie-break rules.
- Pagination MUST be stable for unchanged catalog snapshot.
- Cache HIT and MISS paths MUST converge to equivalent business payload for identical request/snapshot.

Phase 1 runtime notes:
- Product Grid remains the authoritative consumer contract for the current shared engine.
- `tags_html` remains an accepted Phase 1 tech-debt exception inside the engine payload/cache envelope.
- The inline PHP year-sort execution path still lives inside `bw_fpw_execute_search()`.
- Phase 2 MUST extract that PHP-sort execution path to a dedicated execution-planner-style surface before new execution paths are introduced.

---

## 8. Performance Guardrails Summary

- `per_page` MUST be bounded (max 24).
- Debounce window MUST be bounded (target 250–350ms; default 300ms).
- DB workload MUST be bounded per request by contract and filter constraints.
- Response payload MUST be bounded to presentation-safe fields only.
- Candidate retrieval MUST be capped inside the engine-owned candidate resolver.
- Candidate cap MUST remain less than or equal to the large `post__in` safety threshold.
