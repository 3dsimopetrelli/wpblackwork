# System Status (Admin Diagnostics)

## Purpose
Provide an admin-only, on-demand health snapshot under `Blackwork Site > Status` without impacting storefront runtime.

## Scope (v4)
- Menu routing: top-level `Blackwork Site` lands on `Site Settings`.
- New submenu page: `Status`.
- Manual button-triggered AJAX checks (no cron).
- Read-only diagnostics with transient cache.
- Shopify-like owner/admin UI with:
  - page header (title + subtitle)
  - dedicated action bar below notices with:
    - `Last check`, `Source`, `TTL`, `Exec`
    - `Run full check` + `Force refresh`
  - top overview strip with compact status pills
  - five metric-first cards with per-section actions
  - debug payload hidden by default via `Show debug JSON`

## Checks
- Media Library:
  - total attachments count
  - total bytes estimate from attachment files
  - bytes by type (`jpeg`, `png`, `svg`, `video`, `webp`, `other`) + percentage distribution
  - largest media file
  - top 10 largest media files (bounded list, clickable admin edit links when available)
- Database:
  - total database size estimate
  - total table count
  - largest table + top largest tables (`top 10`)
  - `wp_options` autoload size with warning threshold (`3MB`)
  - source tracking (`information_schema` or `SHOW TABLE STATUS` fallback)
- Images:
  - registered image sizes (`name`, `width`, `height`, `crop`)
  - estimated generated image count
  - duplicate size detection (`width/height/crop`)
  - optional on-demand generated counts per size (`Compute generated counts`)
- WordPress environment:
  - WordPress version
  - WooCommerce version (if available)
  - PHP version
  - PHP memory limit
  - `WP_DEBUG` and `DISALLOW_FILE_EDIT`
- PHP limits:
  - `upload_max_filesize`
  - `post_max_size`
  - `max_execution_time`
  - `memory_limit`

## Security & Safety
- Capability gated (`manage_options`).
- Nonce protected AJAX action.
- Read-only behavior only (no delete/update writes).
- Heavy work runs only on button click and uses transient cache (`10 minutes`) to avoid repeated scans on page reload.
- Check runner wraps each check with graceful-failure handling so partial failures still return structured JSON.
- AJAX supports `check_scope`: `all`, `media`, `images`, `database`, `wordpress`, `limits`, `image_sizes_counts`.
- Per-section refresh recomputes only requested scope and merges results into cached snapshot.
- `all` does not execute `image_sizes_counts` (heavy path remains explicit on-demand only).

## File Map
- Bootstrap: `includes/modules/system-status/system-status-module.php`
- Admin page: `includes/modules/system-status/admin/status-page.php`
- Admin JS: `includes/modules/system-status/admin/assets/system-status-admin.js`
- AJAX runner: `includes/modules/system-status/runtime/check-runner.php`
- Checks:
  - `includes/modules/system-status/runtime/checks/check-media.php`
  - `includes/modules/system-status/runtime/checks/check-database.php`
  - `includes/modules/system-status/runtime/checks/check-images.php`
  - `includes/modules/system-status/runtime/checks/check-wordpress.php`
  - `includes/modules/system-status/runtime/checks/check-server.php`

## Operational Notes
- Media byte totals are calculated from readable files and can return partial/warn on very large libraries; scans are bounded.
- On-demand `image_sizes_counts` scan is capped to `3000` image attachments and returns partial warning when cap is reached.
- Database sizes are estimates and can degrade gracefully when host permissions restrict `information_schema`.
- Snapshot payload includes: `generated_at`, `ttl_seconds`, `cached`, `execution_time_ms`, and `checks`.
- Each check returns `status` (`ok|warn|error`) and a human-readable `summary` used by badges.
- Full JSON payload remains available in the collapsed debug area and can be exported as a file.
