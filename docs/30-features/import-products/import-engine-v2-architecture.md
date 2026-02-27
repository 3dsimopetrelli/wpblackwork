# Import Engine v2 — Deterministic Architecture Foundation

## 1) Scope and Authority Boundary

- Domain: Import / Catalog Data Integrity
- Tier: Tier 0 Data Integrity Surface
- Canonical authority: WooCommerce product database

Normative constraints:
- Import Engine v2 MUST treat WooCommerce as the only product truth authority.
- Import Engine v2 MUST NOT create any parallel product authority.
- Import Engine v2 MUST eliminate the invariant threat described in `R-IMP-10` by enforcing deterministic SKU convergence, resumable execution, and run-level traceability.
- Import Engine v2 MUST NOT rely on request-bound full CSV in-memory processing.

## 2) Identity Model (SKU-Only Authority)

Canonical identity model:
- Product identity key MUST be `sku` only.
- Every row MUST contain SKU.
- Rows without SKU MUST fail at validation stage before write execution.
- SKU MUST be immutable for identity resolution inside a run.

Row identity:
- `row_identity_key = import_namespace + sku`
- `import_namespace` MUST be persisted in run metadata.

Convergence invariants:
- For one SKU, at most one Woo product entity MUST exist.
- Re-run of identical logical data MUST converge to update-only behavior (no duplicate creation).

## 3) Run Lifecycle Model

Run entity:
- Import execution MUST be represented by a persistent `run` object.

Run states:
- `draft`
- `validated`
- `queued`
- `running`
- `paused`
- `failed`
- `completed`
- `aborted`

State transition rules:
- `draft -> validated` only after schema/mapping/identity checks pass.
- `validated -> queued` only after run plan is persisted.
- `queued -> running` only through executor start.
- `running -> paused|failed|completed|aborted` based on executor outcome.
- `paused -> running` MUST resume from persisted checkpoint.
- `failed -> running` MAY retry only under explicit operator action with same run identity or explicit successor linkage.

## 4) Checkpoint and Resume Contract

Checkpoint persistence MUST include:
- run ID
- chunk index
- row cursor (absolute offset)
- terminal row statuses (`done`, `skipped`, `error`)
- counters (`created`, `updated`, `skipped`, `error`)
- last successful checkpoint timestamp

Resume rules:
- Resume MUST continue from last committed checkpoint.
- Already terminal rows MUST NOT be re-written.
- Resume MUST be deterministic: same run state + same inputs = same continuation behavior.

## 5) Chunking and Memory Guard Strategy

Execution model:
- Processing MUST be chunked.
- Chunk size MUST be explicit run configuration with safe default.
- Parser MUST stream or incrementally read input; full-file row arrays in one request CANNOT be required.

Memory and time guardrails:
- Executor MUST enforce bounded per-chunk workload.
- Chunk commit boundary MUST be short-lived to reduce timeout exposure.
- Chunk retry MUST be safe and idempotent.

## 6) Idempotency Contract (No Duplicate SKU Ever)

Write-path rules:
- Resolver MUST check existing product by SKU before any create attempt.
- Create path MUST execute only when no product exists for SKU.
- Update path MUST execute when SKU already exists.

Duplicate prevention:
- Concurrent attempts on same SKU MUST converge to one effective product entity.
- Any create conflict on same SKU MUST degrade to deterministic resolution (resolve-existing then update or fail deterministically with trace).

Idempotency invariant:
- Duplicate SKU creation MUST be structurally impossible under normal and retry execution paths.

## 7) Row-Level Transaction Safety

Atomicity scope:
- Row processing MUST be atomic at row boundary (all critical row mutations committed together or row marked failed with no ambiguous terminal state).

Critical mutation set:
- core product fields
- SKU identity binding
- required taxonomy/meta writes for row validity

Failure behavior:
- Partial critical writes CANNOT be considered success.
- Row terminal state MUST reflect actual commit outcome.
- Retry MUST re-enter row safely without duplicate side effects.

## 8) Image Ingestion Architecture (Async-Safe, Retry-Safe)

Separation of concerns:
- Image ingestion MUST be decoupled from core row identity/critical write path.
- Product core write MUST be allowed to complete independently of remote image fetch latency.

Image task model:
- Each image reference MUST generate deterministic task identity linked to `run_id + row_identity_key + image_reference`.
- Image tasks MUST support terminal states (`done`, `error`, `skipped`) and retries.

Convergence rules:
- Repeated image retries MUST converge (no uncontrolled duplicate attachments).
- Image failure MUST NOT invalidate product identity convergence.

## 9) Audit and Logging Model

Run-level audit record MUST contain:
- run ID
- actor/user
- input fingerprint (file hash + size)
- mapping snapshot
- configuration snapshot (delimiter, chunk size, namespace)
- start/end timestamps
- terminal status
- aggregate counters

Row-level audit record MUST contain:
- run ID
- row index
- row identity key
- stage (`parse`, `validate`, `resolve`, `write`, `image`)
- status
- structured error code/message when failed

Observability rules:
- Every row MUST be traceable.
- Every failure MUST be attributable to stage and identity key.

## 10) Failure and Retry Convergence Rules

Failure classes:
- validation failure
- identity collision/conflict
- write failure
- taxonomy/meta assignment failure
- image acquisition failure
- executor interruption/timeout

Convergence rules:
- Validation failures MUST be terminal for that row unless input changes.
- Retryable failures MUST remain retry-safe and idempotent.
- Run restart MUST preserve previous terminal row decisions.
- Duplicate side effects across retries CANNOT be accepted.

## 11) Performance Invariants

Performance contract:
- Import MUST execute in bounded chunks.
- Import MUST avoid full-dataset request-bound memory model.
- Exact identity resolution by SKU MUST be deterministic and bounded per row.
- Throughput degradation under image/network instability MUST be isolated from core row convergence.

Non-negotiable invariants:
- Deterministic convergence per SKU.
- Resumable execution after interruption.
- Auditable run and row traceability.
- No duplicate product entity creation.

## 12) WooCommerce CRUD Integrity Constraints

- Product writes MUST use WooCommerce-consistent CRUD semantics.
- Import Engine v2 MUST preserve Woo data integrity expectations for post/meta/taxonomy coherence.
- Import runtime MUST NOT bypass identity safety checks for speed.
- Any optimization MUST preserve deterministic outcome equivalence.

## 13) R-IMP-10 Elimination Statement

R-IMP-10 threat coverage:
- Duplicate creation risk: eliminated by SKU-only identity and idempotent resolver contract.
- Partial writes risk: controlled by row atomicity and explicit terminal row state.
- No checkpoint/resume risk: eliminated by mandatory run/checkpoint model.
- No run trace risk: eliminated by run-level and row-level audit records.
- Request-bound model risk: eliminated by chunked incremental execution contract.
- Image network fragility risk: isolated via async-safe image task model.

Governance conclusion:
- Import Engine v2 architecture defined here is the required foundation to move R-IMP-10 from critical exposure to controlled implementation scope.
