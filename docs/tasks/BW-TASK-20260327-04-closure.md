# Blackwork Governance -- Task Closure

- Task ID: `BW-TASK-20260327-04`
- Task title: Global layout width lock system in Site Settings
- Closure date: `2026-03-27`
- Authoritative closure protocol followed: `docs/governance/task-close.md`

## Outcome
- Added a new `Layout` tab under `Blackwork Site -> Site Settings`.
- Introduced the global layout option surface `bw_site_layout_settings_v1`.
- Added frontend runtime classes and CSS variables for a centered max-width shell.
- Applied the shell to:
  - singular main content via `the_content`
  - WooCommerce main content via wrapper hooks
  - custom header inner content
  - Theme Builder Lite footer output
- Added reusable utility classes to `Site Settings -> Info`:
  - `bw-layout-breakout`
  - `bw-layout-full-bleed`
  - `bw-strong-border-radius`
  - existing `bw-hover-underline-ltr` preserved

## Files Changed
- `admin/class-blackwork-site-settings.php`
- `blackwork-core-plugin.php`
- `assets/css/bw-custom-class.css`
- `includes/modules/theme-builder-lite/runtime/footer-runtime.php`
- `docs/20-development/admin-panel-map.md`
- `docs/tasks/BW-TASK-20260327-04-start.md`
- `docs/tasks/BW-TASK-20260327-04-closure.md`

## Verification
- Mandatory PHP syntax checks:
  - `php -l admin/class-blackwork-site-settings.php`
  - `php -l blackwork-core-plugin.php`
  - `php -l includes/modules/theme-builder-lite/runtime/footer-runtime.php`
  - result: passed
- `composer run lint:main`
  - result: passed

## Notes
- The main-content shell uses wrapper strategy first and explicit breakout/full-bleed utility classes for intentional exceptions.
- Legacy `bw-full-section` remains supported for backwards compatibility.
