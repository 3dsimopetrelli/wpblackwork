# BW-TASK-20260306-15 — FPW Asset Localization Deduplication

## Scope
- `blackwork-core-plugin.php`
- `docs/tasks/BW-TASK-20260306-15-closure.md`

## Implementation Summary
- Hardened `bw_register_filtered_post_wall_widget_assets()` to prevent duplicate `wp_localize_script()` execution when the function is triggered by multiple hooks.
- Added a static in-function guard:
  - `static $fpw_assets_localized = false;`
  - localization now runs only once per request.

## Behavior Notes
- Existing hook registrations are unchanged.
- Script/style registration still runs on every invocation as before.
- Only localization is deduplicated to avoid repeated global injection and redundant nonce generation.

## Verification Steps
1. Confirm the static guard exists in `bw_register_filtered_post_wall_widget_assets()`.
2. Confirm `wp_localize_script('bw-filtered-post-wall-js', 'bwFilteredPostWallAjax', ...)` is wrapped by the one-time guard.
3. Run:
   - `php -l blackwork-core-plugin.php`
   - `composer run lint:main`
