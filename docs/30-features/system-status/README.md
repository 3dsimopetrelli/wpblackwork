# System Status (Admin Diagnostics)

## Purpose
Provide an admin-only, on-demand, read-only diagnostics dashboard under `Blackwork Site > Status` without adding load to storefront or normal admin navigation.

## Architecture Overview
- Module root: `includes/modules/system-status/`
- Bootstrap: `system-status-module.php`
- Admin rendering:
  - `admin/status-page.php`
  - `admin/assets/system-status-admin.js`
- Runtime execution:
  - `runtime/check-runner.php`
  - `runtime/checks/check-media.php`
  - `runtime/checks/check-database.php`
  - `runtime/checks/check-images.php`
  - `runtime/checks/check-wordpress.php`
  - `runtime/checks/check-server.php`

## UX Layout (Shopify-Style)
- Header: `Status` + short explanatory subtitle.
- Action Bar (below WP notices):
  - metadata: `Last check`, `Source (Live/Cached)`, `TTL`, `Exec`
  - actions: `Run full check`, `Force refresh`
- Overview strip: compact status cards for `PHP Limits`, `Database`, `Media Storage`, `WordPress`.
- Main cards:
  - `Images & Media Storage`
  - `Image Sizes`
  - `Database`
  - `WordPress Environment`
  - `PHP Limits`
- Details are collapsible; debug JSON is hidden by default behind `Show debug JSON`.

## Diagnostic Checks
- `media`
  - total files/bytes
  - by-type bytes + percentages (`jpeg`, `png`, `svg`, `video`, `webp`, `other`)
  - largest file
  - top 10 largest files with attachment edit links when available
- `images`
  - registered sizes list (`name`, `width`, `height`, `crop`)
  - duplicate size detection
  - optional on-demand generated file counts (`image_sizes_counts`)
- `database`
  - total DB size estimate
  - table count
  - largest table
  - top 10 largest tables
  - autoload size with warn threshold at `3MB`
- `wordpress`
  - WordPress/PHP/WooCommerce versions
  - `WP_DEBUG`
  - `DISALLOW_FILE_EDIT`
- `limits` (PHP limits)
  - `upload_max_filesize`
  - `post_max_size`
  - `memory_limit`
  - `max_execution_time`

## Snapshot and Caching Model
- Transient key: `bw_system_status_snapshot_v1`
- TTL: `600` seconds (10 minutes)
- Full payload includes:
  - `ok`
  - `generated_at`
  - `ttl_seconds`
  - `execution_time_ms`
  - `cached`
  - `checks`
- Check scopes:
  - `all`, `media`, `images`, `database`, `wordpress`, `limits`, `image_sizes_counts`
- Partial scope execution recomputes only selected checks and merges them into the cached snapshot.
- `all` does not run `image_sizes_counts` (heavy path stays explicit on-demand only).

## Security Model
- Endpoint: `wp_ajax_bw_system_status_run_check` (admin-only).
- Capability gate: `manage_options`.
- Nonce verification required for every request.
- Diagnostics remain strictly read-only.
- Partial failures are isolated: each check returns structured `status` + `summary` and never breaks global payload format.

## Performance Safeguards
- No heavy work on page load.
- Heavy checks run only via action buttons and cache reuse.
- Bounded scans:
  - media file scan is capped
  - generated image counts scan is capped to `3000` attachments
- Partial scans return `warn` and explicit partial metadata.
- DB size uses estimate strategy with fallback (`information_schema` preferred, `SHOW TABLE STATUS` fallback).

## UX Rationale
The dashboard intentionally favors owner/admin readability:
- metric-first cards over technical tables
- per-section actions to avoid unnecessary full reruns
- collapsible details for advanced inspection
- debug JSON available on demand only

This keeps day-to-day health checks fast and understandable while preserving full diagnostics depth when needed.
