# System Status (Admin Diagnostics)

## Purpose
Provide an admin-only, on-demand health snapshot under `Blackwork Site > Status` without impacting storefront runtime.

## Scope (v1)
- Menu routing: top-level `Blackwork Site` lands on `Site Settings`.
- New submenu page: `Status`.
- Manual button-triggered AJAX checks (no cron).
- Read-only diagnostics with transient cache.

## Checks
- Media Library:
  - total attachments count
  - total bytes estimate from attachment files
  - bytes by type (`jpeg`, `png`, `svg`, `video`, `webp`, `other`)
- Database:
  - total size estimate for largest tables (top N)
  - source tracking (`information_schema` or `SHOW TABLE STATUS` fallback)
- Images:
  - registered image sizes (`name`, `width`, `height`, `crop`)

## Security & Safety
- Capability gated (`manage_options`).
- Nonce protected AJAX action.
- Read-only behavior only (no delete/update writes).
- Heavy work runs only on button click and uses transient cache (`10 minutes`) to avoid repeated scans on page reload.

## File Map
- Bootstrap: `includes/modules/system-status/system-status-module.php`
- Admin page: `includes/modules/system-status/admin/status-page.php`
- Admin JS: `includes/modules/system-status/admin/assets/system-status-admin.js`
- AJAX runner: `includes/modules/system-status/runtime/check-runner.php`
- Checks:
  - `includes/modules/system-status/runtime/checks/check-media.php`
  - `includes/modules/system-status/runtime/checks/check-database.php`
  - `includes/modules/system-status/runtime/checks/check-images.php`

## Operational Notes
- Media byte totals are calculated from readable files and can return partial/warn on very large libraries.
- Database sizes are estimates and can degrade gracefully when host permissions restrict `information_schema`.
- The dashboard uses `OK/WARN/ERROR` per check and exposes raw JSON for debugging.
