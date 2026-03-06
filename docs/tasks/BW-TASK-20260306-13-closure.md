# BW-TASK-20260306-13 — Import Engine Hardening

## Scope
- `admin/class-blackwork-site-settings.php`
- `docs/00-governance/risk-register.md`
- `docs/00-planning/decision-log.md`
- `docs/tasks/BW-TASK-20260306-13-closure.md`

## Implementation Summary
- Added run-level lock model in `wp_options` via `bw_import_run_lock` with ownership (`run_id`, `owner_user_id`) and timing fields (`acquired_at`, `heartbeat_at`, `expires_at`).
- Added durable authoritative run-state model in `wp_options`:
  - active pointer: `bw_import_active_run`
  - per-run snapshot: `bw_import_run_{run_id}`
- Kept existing user transient (`bw_import_state_{user_id}`) as non-authoritative UI mirror only.
- Added deterministic row outcome ledger (`created|updated|skipped|failed`) keyed by row identity (`offset/index + sku hash`) with bounded retention (last 500 outcomes) and non-double-counting counters.
- Hardened run execution path to:
  - acquire lock before chunk mutation
  - persist checkpoint after each chunk
  - refresh lock heartbeat across chunk lifecycle
  - release lock on completion/failure and upload replacement/reset path
- Hardened SKU convergence in `bw_import_save_product_from_row()`:
  - ID-first + SKU lookup resolution
  - pre-create SKU re-resolve gate
  - deterministic retry-to-update path on SKU-set conflict.
- Added publish status allowlist guard in row normalization:
  - allowed: `draft`, `publish`, `pending`, `private`
  - invalid value -> normalized to `draft` with deterministic warning.

## Lock Model
- Single authoritative lock key: `bw_import_run_lock`.
- Lock contention behavior:
  - if lock belongs to another active run/user and is not stale: abort with deterministic admin-facing error; no catalog mutation.
  - if stale: reclaim lock and append deterministic warning to run state.
- Lock lifecycle:
  - acquire at run-request start
  - heartbeat refresh at chunk start/end
  - release on terminal completion/failure and on upload replacement of an active run.

## Durable Run State Model
- Authoritative run state fields include:
  - `run_id`, `status`, `owner_user_id`
  - `file_path`, `file_fingerprint`
  - `mapping_snapshot`, `options_snapshot`
  - `row_cursor`, `counters`, `totals`
  - `row_outcomes`, `row_outcome_order`
  - `last_errors`, `last_warnings`
  - timestamps (`started_at`, `updated_at`, `completed_at`)
  - lock metadata mirror (`lock`)
- Resume now converges from durable state via active run pointer, not transient-only cursor state.

## Row Outcome Determinism
- Each row has one terminal outcome: `created`, `updated`, `skipped`, or `failed`.
- Re-entry/retry does not double count existing row identity outcomes.
- Counters/totals are synchronized deterministically from ledger-backed counters.
- Outcome messages are retained for recent rows within bounded storage.

## Resume Behavior
- Admin refresh resumes from `bw_import_active_run` + `bw_import_run_{run_id}`.
- Mid-run interruption resumes from last committed checkpoint (`row_cursor` + counters/errors/warnings/outcomes).
- Stale lock can be reclaimed, with warning trace persisted to run state.

## Verification Steps
1. Upload CSV and verify a new `run_id` is generated with durable state persisted in options.
2. Start import and verify lock acquisition prevents concurrent second-operator chunk mutation.
3. Force an interruption between chunks and verify resume continues from durable `row_cursor` checkpoint.
4. Confirm row outcomes are deterministic and not double-counted on repeated run requests.
5. Confirm duplicate-SKU rows resolve to update path (no duplicate product creation).
6. Confirm invalid `post_status` values normalize to `draft` and emit warnings.
7. Run:
   - `php -l admin/class-blackwork-site-settings.php`
   - `composer run lint:main`

## Residual Risks
- No dedicated import-run DB table yet; option-backed retention is bounded and may need future migration for large historical telemetry.
- Full multi-node/process lock atomicity still depends on WordPress option write semantics in shared DB environments.
- Partial side effects outside product save transaction boundaries (e.g., term creation) remain bounded but not fully transactional.
