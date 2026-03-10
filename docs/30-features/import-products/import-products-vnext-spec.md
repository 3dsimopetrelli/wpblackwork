# Import Products vNext Specification

## 1) Module Classification

- Domain: Data Import Domain
- Tier: Tier 0 Data Integrity Surface
- Authority: WooCommerce product database is canonical product authority

Normative rules:
- The importer MUST treat Woo product data as source of truth.
- Import runtime MUST NOT introduce a parallel product authority.
- Import runtime MUST preserve authority hierarchy constraints (ADR-002).
- External dependencies (for example remote image hosts) MUST be treated as execution providers, not authority providers (ADR-006).

## 2) Input Contract

### Accepted format
- CSV MUST be the canonical vNext interchange format.

### Encoding
- Input CSV MUST be UTF-8.
- UTF-8 BOM MUST be detected and stripped at parse boundary.

### Delimiter contract
- Default delimiter MUST be comma (`,`) unless an explicit delimiter is declared for the run.
- Delimiter selection MUST be deterministic per run and persisted in run metadata.

### Required columns
- SKU MUST be present for every row.
- SKU MUST be unique across the Woo product store.
- Rows without SKU MUST fail fast at parse/validation stage.
- SKU MUST be treated as immutable identity once product is created.

### Optional columns
- Optional columns MAY include:
  - title/slug/status/type
  - descriptions
  - pricing and sale windows
  - stock/inventory fields
  - dimensions/shipping/tax fields
  - categories/tags
  - attributes
  - featured image/gallery references
  - custom meta fields
- Optional fields MUST be ignored safely if unmapped.

## 3) Idempotency Model

### Row Identity Key
- Each row MUST resolve to a deterministic Row Identity Key:
  - `row_identity_key = import_namespace + sku`
- Canonical unique key = SKU.

### Convergence requirements
- Re-running the same dataset MUST converge to the same Woo product records.
- Duplicate product creation for an already-seen Row Identity Key MUST NOT occur.
- A row processed multiple times MUST yield one effective product identity target.
- For any given SKU, importer MUST guarantee at most one Woo product entity exists.

### SKU Immutability Rule
- Once a product is created using a given SKU, that SKU MUST NOT be reassigned to a different logical product.
- SKU change MUST be treated as new identity and new Row Identity Key.
- Import runtime MUST NOT silently mutate SKU during update path.

### Create/update rules
- If Row Identity Key resolves to an existing product, importer MUST execute update path.
- If Row Identity Key does not resolve and create mode is allowed, importer MAY create once.
- If create mode is disabled, non-resolvable rows MUST be marked as skipped with explicit reason.

## 4) Execution Model (Bulk Safe)

### Current runtime hardening snapshot (2026-03-10)
- Legacy runtime now includes in-chunk checkpoint persistence (`row_cursor` updates during chunk execution) to reduce replay side effects after interrupted runs.
- Import normalization now enforces enum allowlists for:
  - `product_type` (`simple|variable|grouped|external`)
  - `stock_status` (`instock|outofstock|onbackorder`)
  - `backorders` (`no|notify|yes`)
  - `tax_status` (`taxable|shipping|none`)
- Invalid enum values are ignored safely with row warnings; valid rows remain unchanged.

### Chunking
- Import execution MUST be chunked.
- A run MUST process rows in bounded chunk sizes, not as one monolithic request.

### Resumable processing
- Import execution MUST be resumable from checkpoints.
- Interrupted runs MUST support safe re-entry without duplicating completed row effects.
- Completed runs MUST be terminal and non-restartable under the same run identity.

### Checkpointing
- Checkpoint state MUST include at minimum:
  - run ID
  - chunk index or row cursor
  - per-row terminal status (`done` / `error` / `skipped`)
  - timestamp of last processed checkpoint
  - durable processed-row identity set used for replay-safe dedupe across resume/retry

### Progress reporting
- Admin runtime MUST expose deterministic progress indicators:
  - total rows
  - processed rows
  - created/updated/skipped/error counters
  - current run status (`queued` / `running` / `paused` / `failed` / `completed`)

## 5) Image Handling Model

### Separation of concerns
- Image acquisition MUST be separated from core product row mutation path.
- Product write path MUST NOT require synchronous per-row network sideload completion to remain convergent.

### Reuse/dedup rules
- Image reuse lookup MUST occur before acquisition attempts.
- Duplicate image ingestion for identical canonical image references SHOULD converge to one attachment target per reference.

### Failure tolerance
- Image failures MUST be isolated at row/image task level.
- Core product row processing MUST remain non-blocking for non-critical image failures where business-safe.
- Failed image tasks MUST be retryable with idempotent behavior.

### Retry model
- Image retries MUST preserve row identity linkage and run traceability.
- Repeated image retries MUST converge, not duplicate attachments indefinitely.

## 6) Taxonomy & Attributes Model

### Term creation rules
- Missing category/tag/attribute terms MAY be created when allowed by run policy.
- Term assignment MUST remain deterministic per row identity.

### Caching requirement (conceptual)
- Taxonomy/term lookup caching MUST be used conceptually to avoid repeated expensive lookups inside bulk loops.
- Cache scope MUST be run-bounded and safe to invalidate at run end.

### Variation support stance
- vNext scope explicitly: variation import is NOT supported unless a dedicated variation contract is formalized.
- Variable product parent field writes MAY be supported where mapped, but child variation lifecycle is out of current vNext scope.

## 7) Error Handling & Audit

### Per-row error structure
- Each failed row MUST emit structured error data including:
  - run ID
  - row index/source reference
  - row identity key
  - error code
  - error message
  - stage (`parse` / `map` / `save` / `taxonomy` / `image`)

### Run-level audit record
- Each import run MUST produce a run audit record containing:
  - run ID
  - actor/user ID
  - input file fingerprint/metadata
  - mapping profile snapshot
  - chunk/checkpoint stats
  - aggregate counters
  - start/end timestamps
  - terminal status

### Retry policy and safe re-entry
- Row retries MUST be safe and idempotent.
- Run restarts MUST re-use existing run identity or create explicit successor linkage.
- Safe re-entry MUST NOT re-apply already terminal row mutations as duplicates.
- Lock reclaim under interruption MUST be deterministic and concurrency-safe (single reclaim winner).

## 8) Security & Permissions

### Capability and nonce gates
- Import actions MUST require explicit admin capability checks.
- All write actions MUST enforce nonce validation.

### File validation
- Uploaded file type and extension MUST be validated against an explicit allowlist.
- Parsing MUST reject malformed header states with explicit error output.
- Input contract violations MUST fail fast with traceable run errors.

### Boundary rules
- Import endpoints MUST remain admin-scoped.
- Import runtime MUST not expose privileged write paths to unauthenticated contexts.

## 9) Regression Checklist

1. Small file run
- Verify deterministic completion and accurate counters on a minimal dataset.

2. Mid-size run
- Verify chunk progression, checkpoint updates, and final convergence on medium dataset.

3. Duplicate re-run
- Re-run same file and mapping; verify no duplicate product creation for identical row identities.

4. Timeout/interruption simulation
- Interrupt run mid-way; resume and verify safe re-entry with no duplicate terminal effects.

5. Image failure simulation
- Simulate unreachable image references; verify product row convergence, error traceability, and retry-safe behavior.

6. SKU collision handling
- Verify duplicate SKU in the same input file fails deterministically with explicit row-level errors.
