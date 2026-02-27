# Import Engine v2 — Storage & Executor Design

## 1) Goals / Non-goals

### Goals
- The engine MUST be resumable across process interruptions.
- The engine MUST be idempotent per SKU.
- The engine MUST execute with bounded memory and bounded chunk work.
- The engine MUST expose observable run state and row-level events.

### Non-goals
- This document does NOT define UI implementation details.
- This document does NOT define legacy importer refactors.
- This document does NOT define alternative executor/storage architectures.

## 2) Storage Model Overview

Import Engine v2 MUST use custom DB tables as the canonical runtime state store.

Rationale:
- Run state, checkpoint state, and event trace are high-volume and lifecycle-bound.
- Deterministic resume/retry requires transactional persistence and indexed queries.
- Governance traceability requires durable run/event history.

What MUST be stored:
- Run identity, status, namespace, source fingerprint.
- Checkpoint cursor and counters.
- Lock ownership and expiration metadata.
- Structured run events (errors, warnings, retry notes, conflict notes).

What MUST NOT be stored in `wp_options`/transients:
- Active run cursor/state.
- Per-run lock state.
- Per-row/per-event execution trace.
- Retry/backoff state.

`wp_options` MAY store static feature flags only (for example, v2 enable/disable), but MUST NOT store mutable execution state.

## 3) Database Schema

### A) `bw_import_runs`

Purpose:
- Canonical run lifecycle record + checkpoint summary.

Columns (minimum):
- `run_id` (PK, bigint unsigned auto-increment OR UUID as canonical primary key)
- `created_at` (datetime, not null)
- `updated_at` (datetime, not null)
- `status` (enum/string: `queued`, `running`, `paused`, `failed`, `completed`, `cancelled`)
- `import_namespace` (varchar, not null)
- `source_file_path` or `file_ref` (text/varchar, not null)
- `file_fingerprint` (varchar hash, not null)
- `mapping_snapshot_json` (longtext/json, not null)
- `chunk_size` (int, not null)
- `row_cursor` (bigint/int, not null, default 0)
- `total_rows` (int, nullable)
- `total_created` (int, not null, default 0)
- `total_updated` (int, not null, default 0)
- `total_skipped` (int, not null, default 0)
- `total_errors` (int, not null, default 0)
- `last_error_summary` (text, nullable)
- `lock_owner` (varchar, nullable)
- `lock_expires_at` (datetime, nullable)
- `version` (int, not null)

Indexes (minimum):
- PK on `run_id`
- Index on `status`
- Index on `created_at`
- Composite index on (`status`, `updated_at`)
- Index on `file_fingerprint`

### B) `bw_import_run_events`

Purpose:
- Immutable event stream for observability, diagnostics, and recovery evidence.

Columns (minimum):
- `id` (PK, bigint unsigned auto-increment)
- `run_id` (FK reference to `bw_import_runs.run_id`, not null)
- `event_type` (varchar, not null; e.g. `row_error`, `warning`, `info`, `image_pending`, `retry`, `conflict_note`)
- `row_number` (int, nullable)
- `sku` (varchar, nullable)
- `message` (text, not null)
- `payload_json` (longtext/json, nullable)
- `created_at` (datetime, not null)

Indexes (minimum):
- PK on `id`
- Index on `run_id`
- Index on (`run_id`, `created_at`)
- Index on `event_type`
- Index on `created_at`
- Index on `sku` (if populated)

## 4) Executor Model (Action Scheduler)

Executor MUST use WooCommerce Action Scheduler as the primary queue.

Action hooks:
- `bw_import_process_chunk`
- `bw_import_finalize_run`
- `bw_import_process_images` (optional but supported by this design)

Scheduling rules:
- On run creation (`status=queued`), the system MUST enqueue the first `bw_import_process_chunk` action.
- The next chunk action MUST be enqueued only after checkpoint persistence succeeds.
- Finalization MUST be enqueued only after terminal chunk completion conditions are met.

Concurrency rules:
- At most one active chunk executor MAY run for a given `run_id`.
- Duplicate scheduled actions for the same `run_id` MUST be harmless due to lock/idempotency guards.

Retry rules:
- Chunk execution MUST support bounded retries with backoff.
- Retry entry MUST be idempotent: the same chunk action re-run MUST NOT duplicate SKU effects.
- Exceeded retry budget MUST transition run to `failed` or `paused` according to failure class.

Manual fallback:
- A manual trigger MAY enqueue/resume the same Action Scheduler hooks.
- Manual fallback MUST honor identical lock/checkpoint/idempotency rules.

## 5) Locking & Concurrency

Lock model:
- Each chunk processor MUST acquire run lock before processing.
- Lock ownership MUST be stored in `bw_import_runs.lock_owner`.
- Lock expiry MUST be stored in `bw_import_runs.lock_expires_at`.

MUST rules:
- Only one chunk processor per run may hold a valid lock at a time.
- Lock TTL MUST be finite.
- Lock renewal MUST occur during long-running chunk execution.

Stale lock behavior:
- If `lock_expires_at < now`, lock MAY be reclaimed by a new worker.
- Reclaim action MUST emit a `conflict_note` or `retry` event in `bw_import_run_events`.

Concurrent runs touching same SKU:
- Concurrent run creation is allowed.
- SKU-level idempotency MUST prevent duplicate entity creation.
- If overlapping SKU writes are detected, system MUST emit `conflict_note` event(s) containing `run_id`, `sku`, and resolution outcome.

## 6) Checkpoint Semantics

Checkpoint write contract:
- Checkpoint update MUST be atomic with totals update.
- `row_cursor` and aggregate counters MUST reflect the same committed chunk boundary.

Exactly-once effect scope:
- Within a run, SKU mutation effect MUST be exactly-once from business perspective.
- Re-entry after retry/reclaim MUST reconcile from persisted state and MUST NOT duplicate create effects.

Chunk determinism:
- Chunk boundaries MUST be deterministic for a given run configuration (`chunk_size`, source ordering, mapping snapshot).
- Reprocessing a chunk after interruption MUST converge to same post-checkpoint state.

## 7) Failure Modes & Recovery

Hard fail conditions (run to `failed`):
- Corrupt mapping snapshot or unreadable source reference.
- Persistent storage write failure for checkpoint state.
- Repeated lock/contention failure beyond bounded retry policy.

Pause conditions (run to `paused`):
- Operator pause action.
- Recoverable dependency instability (e.g. temporary image network issues if configured to pause on threshold).

Partial run recovery:
- Resume MUST continue from last committed checkpoint.
- Rows marked terminal (`done`, `skipped`, `error`) MUST NOT be re-applied as create operations.

Manual resume flow:
- Operator resumes run -> enqueue `bw_import_process_chunk` from persisted cursor.
- Resume action MUST be auditable via run event.

Cancellation behavior:
- Cancellation MUST set status `cancelled`.
- No new chunk actions MUST be scheduled after cancellation.
- In-flight action completion MUST honor cancellation check before scheduling next chunk.

## 8) Admin Visibility Requirements

Admin visibility MUST provide:
- Run list (run_id, status, created_at, updated_at, namespace, source fingerprint).
- Progress view (row_cursor, totals, percentage if total known).
- Last error summary.
- Event log stream filtered by run_id.
- Operator controls: resume, pause, cancel.

Visibility invariants:
- Displayed status MUST derive from `bw_import_runs` canonical state.
- Event log MUST derive from `bw_import_run_events` and remain append-only.

## 9) Migration Plan from Legacy Importer

Migration constraints:
- Legacy importer MUST remain available behind feature flag until v2 is proven stable.
- v2 activation MUST be explicitly controllable.

Conversion plan:
- In v2 flow, each import starts by creating `bw_import_runs` record.
- Same CSV input MUST be processed deterministically under SKU identity contract.
- Legacy request-bound execution state MUST NOT be reused as v2 canonical run state.

Rollback plan:
- Disable v2 feature flag.
- Route new imports to legacy importer.
- Existing v2 run records remain for audit visibility and MUST NOT be silently deleted.

## 10) Regression & Acceptance Gates (Design-Level)

The following MUST pass once implemented:
- 800-row import completes without timeout under chunked execution.
- Resume works after forced process kill.
- Duplicate SKU in file fails deterministically.
- Re-run does not create duplicate products per SKU.
- Image failures do not fail run core convergence.
- Action Scheduler queue remains stable under load.

Additional acceptance constraints:
- Run/event tables remain internally consistent under retry/reclaim scenarios.
- Lock reclaim behavior is deterministic and auditable.
- No mutable run state is stored in `wp_options` or transients.
