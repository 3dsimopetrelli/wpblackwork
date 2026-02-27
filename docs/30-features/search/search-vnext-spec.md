# Search vNext Specification

## 1) Goals / Non-goals

### Goals
- Define a deterministic, read-only Search runtime for product discovery.
- Add stronger filter coverage with explicit query-contract mapping.
- Introduce deterministic Initial Letter Indexing.
- Establish performance guardrails (debounce, cache, bounded payload).
- Preserve authority boundaries per ADR-002.

### Non-goals
- Search MUST NOT mutate payment, order, provisioning, consent, or auth truth.
- Search MUST NOT create/modify products, orders, users, or marketing state.
- Search vNext does not redefine checkout/payment/routing authority.
- Search vNext does not introduce write-side side effects.

## 2) Input Contract (Request Parameters)

Search vNext MUST accept only sanitized, bounded parameters.

### Core parameters
- `q` (string, required for active search)
  - Min length: 2
  - Max length: 120
- `page` (int, optional)
  - Min: 1
  - Default: 1
- `per_page` (int, optional)
  - Allowed: 1..24
  - Default: 12

### Taxonomy filters (optional)
- `category[]` (array of slugs)
- `tag[]` (array of slugs)
- `attributes` (object)
  - Example: `{ "pa_color": ["black","white"], "pa_size": ["m"] }`

### Commerce-facing read filters (optional)
- `in_stock` (boolean)
- `price_min` (decimal >= 0)
- `price_max` (decimal >= 0)
- `on_sale` (boolean)

### Initial-letter parameters (optional)
- `initial` (single normalized letter group token)
  - Allowed: `A..Z`, `0-9`, `#`
- `group_by_initial` (boolean)

### Sorting (optional)
- `sort` enum:
  - `relevance` (default when `q` present)
  - `title_asc`
  - `title_desc`
  - `price_asc`
  - `price_desc`
  - `newest`

### Contract rules
- Unknown params MUST be ignored.
- Invalid params MUST fail validation with deterministic error payload.
- Empty `q` MAY be allowed only for browse mode if at least one filter is present.

## 3) Output Contract (Response Schema)

Search vNext MUST return a stable JSON envelope.

```json
{
  "ok": true,
  "request_id": "string",
  "query": {
    "q": "string",
    "page": 1,
    "per_page": 12,
    "sort": "relevance",
    "filters": {}
  },
  "meta": {
    "total": 0,
    "total_pages": 0,
    "has_next": false,
    "duration_ms": 0,
    "cache": "hit|miss|bypass"
  },
  "products": [
    {
      "id": 0,
      "sku": "string",
      "title": "string",
      "slug": "string",
      "permalink": "string",
      "image_url": "string",
      "price_html": "string",
      "price_value": 0,
      "currency": "EUR",
      "in_stock": true,
      "on_sale": false,
      "initial": "A"
    }
  ],
  "initials": {
    "available": ["A", "B", "C", "0-9", "#"],
    "selected": "A"
  },
  "error": null
}
```

Error contract:
- `ok = false`
- deterministic `error.code` and `error.message`
- empty `products`

## 4) Filter Model vNext

Search vNext MUST map each filter to explicit query constraints.

### Tax filters
- `category[]` MUST map to `product_cat` taxonomy constraints.
- `tag[]` MUST map to product tags constraints.
- `attributes` MUST map to registered product attribute taxonomies (e.g. `pa_*`).
- Multiple taxonomy families MUST combine deterministically (`AND` between families; `OR` inside same family unless explicitly configured).

### Meta/read filters
- `in_stock=true` MUST constrain to purchasable stock states.
- `on_sale=true` MUST constrain to sale-eligible products.
- `price_min`/`price_max` MUST constrain product price range with numeric-safe comparison.

### Availability/price
- Price and availability filters MUST be read-only constraints.
- Conflicting bounds (`price_min > price_max`) MUST fail validation.

### Sorting/ranking
- `relevance` MUST be deterministic for same input + same catalog snapshot.
- Non-relevance sort MUST define deterministic tie-break:
  - primary sort key + secondary `title_asc` + tertiary `id_asc`.
- Sort mode MUST NOT alter authority surfaces; it only affects presentation order.

## 5) Initial Letter Indexing Model

### Indexed field
- Primary index source MUST be product display title.
- Fallback MAY use normalized post title when display title is unavailable.

### Normalization rules
Normalization MUST be deterministic and locale-safe:
- Trim whitespace.
- Unicode normalize to canonical form.
- Convert to uppercase.
- Strip leading punctuation and symbols for letter detection.
- Transliterate accented Latin letters to base ASCII for index grouping.
- If first alphanumeric is digit -> group `0-9`.
- If no valid alphanumeric -> group `#`.

### Storage strategy
- vNext MUST support precomputed index strategy.
- Runtime on-the-fly recompute MAY exist as fallback only.
- Precomputed index MUST be invalidated/rebuilt on product title changes and product publish status changes.
- Index state MUST converge deterministically after rebuild.

### UX interaction model
- UI MUST expose alphabet navigation using available initials from response.
- Selecting an initial MUST apply deterministic initial constraint to results.
- `group_by_initial=true` MUST return grouped presentation data or stable `initial` labels per item.

### Edge cases
- Numeric-leading titles -> `0-9`.
- Symbol-leading titles with first later letter -> resolved letter group.
- Non-Latin/unsupported transliteration -> `#` unless mapped deterministically.
- Empty title -> `#`.

## 6) Performance Model

### Caching
- Search vNext MUST support server-side cache for query responses.
- Cache key MUST include normalized query + filters + sort + page + per_page + locale/currency context.
- Cache MUST be bounded by TTL and invalidated on relevant product updates.

### Query optimization constraints
- Query paths MUST avoid unbounded scans when deterministic indexes are available.
- Expensive joins/meta filters MUST be bounded and measurable.
- Per-request DB workload MUST be bounded by `per_page` contract.

### Response bounds
- `per_page` hard max: 24.
- Payload MUST exclude non-essential heavy fields.

### Debounce rules
- Client requests MUST be debounced (target 250–350ms; default 300ms).
- In-flight request cancellation MUST be supported when a newer query supersedes the old one.
- Same-key duplicate requests SHOULD be coalesced.

## 7) Security Model

- Public search endpoint MAY remain guest-accessible.
- Nonce validation MUST be required for interactive AJAX calls.
- All input params MUST be sanitized and validated before query construction.
- Taxonomy and sort values MUST be allowlisted.
- Price bounds MUST be numeric-validated.
- Output MUST expose only presentation-safe product fields.
- Search runtime MUST NOT leak admin-only metadata, secrets, or user-private data.

## 8) Observability

Search vNext MUST provide structured observability.

### Minimum telemetry
- `request_id`
- normalized query fingerprint
- duration (`duration_ms`)
- result count
- cache state (`hit/miss/bypass`)
- validation errors count

### Failure observability
- Deterministic error codes for:
  - validation failure
  - endpoint failure
  - timeout/dependency error
- Logs MUST avoid PII overexposure and MUST NOT log secrets.

### Operational counters
- QPS / request volume
- cache hit ratio
- p95 latency
- error rate by error code

## 9) Regression Journeys (vNext)

1. Basic search
- Input: `q` only
- Verify deterministic result ordering and response schema.

2. Taxonomy filter search
- Input: `q + category[] + attributes`
- Verify exact filter application and stable totals.

3. Price/stock filter search
- Input: `q + in_stock + price_min/max`
- Verify numeric filter correctness and bounded payload.

4. Initial-letter navigation
- Input: `initial=A`, then `initial=0-9`, then `initial=#`
- Verify deterministic grouping and edge-case handling.

5. Pagination determinism
- Input: same query across `page=1..n`
- Verify no duplicates across pages and stable totals for unchanged catalog snapshot.

6. Cache behavior
- Repeat same query twice
- Verify second response is cache hit and schema identical (except telemetry fields).

7. Validation failures
- Invalid sort, invalid price bounds, invalid params
- Verify deterministic error payload and no partial data leakage.

8. Guest security path
- Guest request with/without valid nonce according to endpoint contract
- Verify accepted/denied behavior is consistent and auditable.

9. Degrade path
- Endpoint failure simulation
- Verify UI remains navigable and fallback submission path remains functional.

## Governance Alignment

- This specification is read-only by design and MUST comply with ADR-002 authority hierarchy.
- Search vNext CANNOT mutate commerce truth or cross-domain authority surfaces.
- Any future change that alters Search authority classification requires governance review and ADR escalation if authority behavior changes.
