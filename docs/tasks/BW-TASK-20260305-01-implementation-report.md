# BW-TASK-20260305-01 — Implementation Report

> Note: this file captures the initial implementation phase.
> Final validated closure state is documented in `docs/tasks/BW-TASK-20260305-01-closure.md`.

## Task
- **Task ID:** BW-TASK-20260305-01
- **Title:** Set default landing page to Site Settings + add Status health dashboard
- **Date:** 2026-03-05

## Outcome Summary
Implemented successfully:
1. Top-level admin menu **Blackwork Site** now deterministically lands on **Site Settings**.
2. New admin submenu **Status** added under Blackwork Site.
3. New on-demand, read-only diagnostics dashboard with AJAX trigger, nonce/capability guards, and transient caching.

No storefront/Woo checkout behavior was modified.

## What Was Implemented

### 1) Admin menu default landing hardening
- Added a high-priority submenu ordering guard to keep `blackwork-site-settings` as first submenu item.
- This ensures the top-level click opens Site Settings instead of another child page.

**File:**
- `admin/class-blackwork-site-settings.php`

### 2) New System Status module (admin-only)
Created module:
- `includes/modules/system-status/system-status-module.php`

Bootstrap integration:
- `blackwork-core-plugin.php`

Admin page and assets:
- `includes/modules/system-status/admin/status-page.php`
- `includes/modules/system-status/admin/assets/system-status-admin.js`

Runtime/AJAX runner:
- `includes/modules/system-status/runtime/check-runner.php`

Checks:
- `includes/modules/system-status/runtime/checks/check-media.php`
- `includes/modules/system-status/runtime/checks/check-database.php`
- `includes/modules/system-status/runtime/checks/check-images.php`

## Status Page Behavior
- New submenu: **Blackwork Site > Status**
- UI buttons:
  - **Run System Check**: uses cached snapshot if available.
  - **Force Refresh**: bypasses cache and rebuilds snapshot.
- Output:
  - Per-check badge: `OK / WARN / ERROR`
  - Human-readable summary per check
  - Full JSON debug payload

## AJAX / Security Contract
- AJAX action: `bw_system_status_run_check`
- Gate: `manage_options`
- Nonce: `bw_system_status_run_check`
- Endpoint: admin-only (`wp_ajax_...`), no nopriv action
- Read-only execution: no delete/update/write operations in checks

## Caching Contract
- Transient key: `bw_system_status_snapshot_v1`
- TTL: `10 minutes`
- Payload includes:
  - `ok`
  - `generated_at`
  - `cached`
  - `ttl_seconds`
  - `checks` object

## Check Details (v1)

### A) Media check
- Total attachments count (`attachment`, `inherit`)
- Total bytes estimate from attachment files (`_wp_attached_file` + uploads base dir)
- Breakdown by type:
  - `jpeg`, `png`, `svg`, `video`, `webp`, `other`
- Defensive behavior:
  - bounded scan (limit 5000 rows) for performance
  - warns on partial scan and missing/unreadable files

### B) Database check
- Size estimate for top N largest tables (N=8)
- Preferred source: `information_schema.TABLES`
- Fallback source: `SHOW TABLE STATUS LIKE <prefix>%`
- Warn/error handling for restricted hosts and unavailable metadata

### C) Images check
- Enumerates all registered intermediate sizes
- Returns: `name`, `width`, `height`, `crop`

## Documentation Updates Performed
- Added feature index entry for system-status:
  - `docs/30-features/README.md`
- Added new feature documentation:
  - `docs/30-features/system-status/README.md`
- Added governance risk entry:
  - `docs/00-governance/risk-register.md` (`R-ADM-18`)
- Added decision log entry:
  - `docs/00-planning/decision-log.md` (`Entry 016`)

## Files Changed
- `admin/class-blackwork-site-settings.php`
- `blackwork-core-plugin.php`
- `includes/modules/system-status/system-status-module.php`
- `includes/modules/system-status/admin/status-page.php`
- `includes/modules/system-status/admin/assets/system-status-admin.js`
- `includes/modules/system-status/runtime/check-runner.php`
- `includes/modules/system-status/runtime/checks/check-media.php`
- `includes/modules/system-status/runtime/checks/check-database.php`
- `includes/modules/system-status/runtime/checks/check-images.php`
- `docs/30-features/README.md`
- `docs/30-features/system-status/README.md`
- `docs/00-governance/risk-register.md`
- `docs/00-planning/decision-log.md`

## Validation / Checks Run

### PHP syntax checks (`php -l`)
Executed on all modified PHP files:
- `blackwork-core-plugin.php` ✅
- `admin/class-blackwork-site-settings.php` ✅
- `includes/modules/system-status/system-status-module.php` ✅
- `includes/modules/system-status/admin/status-page.php` ✅
- `includes/modules/system-status/runtime/check-runner.php` ✅
- `includes/modules/system-status/runtime/checks/check-media.php` ✅
- `includes/modules/system-status/runtime/checks/check-database.php` ✅
- `includes/modules/system-status/runtime/checks/check-images.php` ✅

Result: **No syntax errors detected**.

### Project lint (`composer run lint:main`)
Executed as required by AGENTS workflow.

Result: **failed** (`exit code 2`) due to a large amount of **pre-existing PHPCS violations** in `blackwork-core-plugin.php` under WordPress coding standard.

Important note:
- This task introduced only a small bootstrap include in that file.
- The reported lint baseline is historical and not fully attributable to this task.

## Invariants Verification
- ✅ No storefront behavior changes
- ✅ No checkout/payment/auth authority boundary changes
- ✅ Diagnostics are read-only
- ✅ No heavy checks on normal page load (manual trigger + cache)
- ✅ Capability + nonce protections applied

## Suggested Prompt Snippet For GPT Update
Use this summary in GPT:

```md
Task BW-TASK-20260305-01 implemented.
- Fixed Blackwork top-level menu landing to Site Settings (deterministic submenu ordering).
- Added new admin module: Blackwork Site > Status.
- Added on-demand AJAX health dashboard (read-only, capability-gated, nonce-protected, transient-cached 10m).
- Checks v1: media totals+bytes by type, DB size estimate+largest tables, registered image sizes.
- Docs updated: feature README, governance risk register (R-ADM-18), decision log (Entry 016).
- Syntax checks passed on all modified PHP files.
- composer lint failed due to pre-existing baseline issues in blackwork-core-plugin.php.
```
