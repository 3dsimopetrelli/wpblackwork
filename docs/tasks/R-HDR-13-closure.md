# R-HDR-13 — Header / Global UX Orchestration Closure

## Task Identification
- Task ID: `R-HDR-13`
- Title: Header / global UX orchestration risk
- Module/Domain: Header frontend runtime orchestration
- Closure date: `2026-03-10`
- Final task status: `CLOSED`
- Final risk status: `MITIGATED`

## File Changed
- `includes/modules/header/assets/js/bw-search.js`

## Runtime Surface Touched
- Search overlay global body-state ownership (`body.bw-search-overlay-active`) across desktop/mobile overlay instances.

## Issue Fixed
- Independent search overlay instances (desktop and mobile) were each toggling the same global body class directly.
- Closing one overlay could remove global active state while another overlay instance remained open.

## Determinism Improvement
- Added global coordination counter `window.BW_HEADER_SEARCH_OPEN_COUNT`:
  - initialized safely to `0` if absent/invalid
  - incremented on `openOverlay()`
  - decremented on `closeOverlay()` with floor at `0`
- `body.bw-search-overlay-active` is now removed only when counter reaches `0`.
- Result: deterministic, reference-counted global overlay state across multiple instances.

## Validation Summary
- Patch scope: one JS file only
- Supabase/auth surfaces touched: none
- Manual regression checklist: completed
  - desktop/mobile open-close
  - sequential multi-overlay open/close
  - body class correctness under partial close
  - Escape handling
  - resize interaction
  - cart popup interaction with search state

## Supabase Freeze Verification
- Freeze respected: YES
- Any `bw_supabase_*` surface touched: NO
- Any auth/session/My Account integration touched: NO

## Governance Synchronization
- Updated:
  - `docs/00-governance/risk-register.md`
  - `docs/00-governance/risk-status-dashboard.md`
  - `docs/50-ops/regression-protocol.md`
- Closure protocol followed:
  - `docs/governance/task-close.md`

## Closure Declaration
`R-HDR-13` hardening is complete, deterministic overlay orchestration is in place, risk status is `MITIGATED`, and the governed task is `CLOSED`.
