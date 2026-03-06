# ADR-008 — Import Engine Authority and Convergence Hardening

## Status
Proposed

## Context
The Import Engine audit identified the active import authority surface in `admin/class-blackwork-site-settings.php` as a Tier-0 catalog mutation domain.

Observed runtime facts:
- Import execution is operator-driven from the admin tab (no async CLI/background runner currently in use).
- Product writes are performed through WooCommerce CRUD in `bw_import_save_product_from_row($data, $update_existing, $options)`.
- SKU is required and used for lookup/matching.
- There is no run-level lock, no SKU claim ledger, no durable run entity, and no per-row terminal ledger.
- Progress/checkpoint state is currently stored in user-scoped transient `bw_import_state_{current_user_id}`.

Observed risk profile:
- duplicate creation risk across overlapping runs
- partial row write risk
- weak resumability guarantees (transient expiry and cursor-only continuity)
- taxonomy/meta partial convergence under retries/failures
- publish/visibility correctness drift
- batch re-seek inefficiency on large files

Because import writes mutate canonical WooCommerce catalog authority, this is a Tier-0 authority/convergence surface.

## Problem Statement
The current import runtime lacks a formal authority and convergence contract. SKU is present but not enforced through durable run governance primitives (run lock, checkpoint ledger, row terminality, replay semantics).

Without these controls, overlapping runs and interruption scenarios can produce non-deterministic outcomes, including duplicate identity effects, partial convergence, and weak auditability.

A normative authority model is required so import behavior is deterministic, replay-safe, and auditable under failure/re-entry conditions.

## Decision
The Blackwork Import Engine MUST adopt the following normative authority and convergence model.

### A) SKU Canonical Identity
- SKU is the canonical product identity for import convergence.
- For one canonical SKU, at most one WooCommerce product entity may be authoritative.
- Create path is allowed only when canonical SKU resolution confirms no existing authoritative entity.
- Replays/retries for the same canonical SKU must converge to update/no-op semantics, never duplicate create semantics.

### B) Import Run Lock
- Only one authoritative import run may mutate catalog state at a time.
- The lock MUST record:
  - lock owner
  - acquisition timestamp
  - expiry timestamp
  - run identity
- If lock is stale (expiry exceeded), reclaim is allowed only through deterministic stale-lock protocol with explicit audit event.
- Lock contention must fail safe (no catalog mutation without lock ownership).

### C) Durable Run State
- Import progress MUST NOT rely only on user-scoped transient state.
- A durable run/checkpoint model is mandatory.
- Durable run state must include at minimum:
  - run ID
  - actor/context metadata
  - source file fingerprint
  - mapping snapshot
  - chunk size and processing configuration
  - terminal counters
  - run status lifecycle

### D) Row-Level Determinism
- Every row must resolve to one deterministic terminal state:
  - `created`
  - `updated`
  - `skipped`
  - `failed`
- Terminal row outcomes must be persisted in durable ledger form.
- Reprocessing a previously terminal row must not create divergent catalog state.

### E) Partial-Write Safety
- Row processing must ensure no ambiguous product identity state is left behind.
- Minimum atomicity expectation (without requiring full DB transaction support):
  - identity resolution and product entity selection must complete deterministically before downstream mutation
  - on write failure, row must terminate as `failed` with explicit stage code
  - retry path must re-enter from authoritative row/run state, not inferred cursor alone
- Partial side effects must be auditable and reconcilable by deterministic retry.

### F) Taxonomy/Meta Convergence
- Taxonomy and meta assignment must converge deterministically across retries.
- Repeated execution of same logical row must converge to same taxonomy/meta end state.
- Term creation/assignment behavior must be idempotent in effect and auditable under failure.

### G) Publish/Visibility Guard
- Imported publish/status values must be allowlisted.
- Non-allowlisted status values must be rejected or normalized deterministically.
- Visibility-affecting fields must not drift under retry/re-entry paths.

### H) Resume Model
- Resume must continue from authoritative durable checkpoint state.
- Resume behavior must not depend solely on transient cursor heuristics.
- Checkpoint commit boundary must bind:
  - processed row window
  - row terminal outcomes
  - aggregate run counters

### I) Failure Handling
- Failures must be fail-safe (no unsafe continuation without authority guarantees).
- Failure outputs must be auditable with structured reason/stage context.
- Recovery path must be explicit:
  - retry from authoritative checkpoint
  - stale lock reclaim protocol
  - deterministic completion/abort transition

## Consequences
### Benefits
- Deterministic import convergence under retries and interruptions.
- Practical elimination of duplicate-creation class for canonical SKU.
- Improved auditability and post-failure recovery confidence.
- Stronger Tier-0 governance compliance for catalog mutation authority.

### Operational Costs
- Additional implementation complexity (run state, lock lifecycle, row ledger).
- Storage and maintenance overhead for durable run/checkpoint artifacts.
- Operator workflow updates for lock/recovery semantics.

### Migration Implications
- Existing transient-only state model requires migration to durable run/checkpoint model.
- Legacy in-progress import states may require explicit migration/invalidations.
- Backward compatibility rules must be defined for active admin import workflows.

### Runtime Complexity Tradeoffs
- Increased control-plane complexity in exchange for deterministic data-plane behavior.
- Slight overhead per chunk/row for ledger and lock checks.
- Reduced ambiguity and lower integrity risk at scale and under concurrent/retry conditions.

## Implementation Constraints
- Scope is limited to import runtime and directly related governance/docs artifacts.
- No unrelated changes to checkout, auth, search, redirect, media-upload, or header runtime surfaces.
- Product write authority remains WooCommerce canonical product authority.
- Future implementation must:
  - respect Tier-0 governance lifecycle
  - complete Task Start and Acceptance Gate before coding
  - complete Task Closure and Release Gate before deployment
  - preserve deterministic behavior and auditable recovery path

## Governance Notes
- This ADR is required because import is a Tier-0 authority surface that mutates canonical catalog state.
- The decision establishes normative rules for identity authority, run ownership, resumability, and failure convergence.
- This ADR is the binding governance baseline for implementation tasks intended to reduce `R-IMP-10` exposure.
- Any deviation from this model requires explicit governance update and, where applicable, ADR amendment.
