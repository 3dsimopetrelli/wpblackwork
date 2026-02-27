# Search vNext — Runtime Dataflow & Architecture

## 1. System Overview

Search vNext is a read-only discovery engine for product retrieval, filtering, and ranking.
It orchestrates query validation, cache/index usage, and response rendering support.

Search does NOT own commerce truth.
Search MUST consume canonical product/price/stock state and MUST NOT mutate it.

---

## 2. High-Level Dataflow Diagram

```text
[User Input]
      |
      v
[Search UI Layer]
      |
      | (debounced request)
      v
[API Endpoint]
      |
      v
[Validation Layer]
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
  |       [Query Engine]
  |             |
  |             v
  |        [Result Set]
  |             |
  |             v
  |        [Cache Store]
  |             |
  \-------------/
        |
        v
[Response Envelope]
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
| - Query orchestration                                         |
| - Filter application                                          |
| - Initial-letter indexing                                     |
| - Cache lookup/store                                          |
+--------------------------------------------------------------+
                            ^
                            | presentation request/response
                            |
+--------------------------------------------------------------+
| Tier 2 — UI Layer (Presentation)                             |
|--------------------------------------------------------------|
| - Overlay / input                                             |
| - Filter controls                                             |
| - Alphabet navigation                                         |
+--------------------------------------------------------------+
```

Authority rule:
- Tier 1 and Tier 2 MUST NOT redefine Tier 0 truth.

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
- Initials grouping MUST be stable under the same normalized titles and index snapshot.
- Cache HIT and MISS paths MUST converge to equivalent business payload for identical request/snapshot.

---

## 8. Performance Guardrails Summary

- `per_page` MUST be bounded (max 24).
- Debounce window MUST be bounded (target 250–350ms; default 300ms).
- DB workload MUST be bounded per request by contract and filter constraints.
- Response payload MUST be bounded to presentation-safe fields only.

