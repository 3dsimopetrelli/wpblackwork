# BW-TASK-20260306-12 — Asset Version Guard Hardening

## Scope
- `blackwork-core-plugin.php`
- `docs/tasks/BW-TASK-20260306-12-closure.md`

## Implementation Summary
- Added bootstrap constant `BLACKWORK_PLUGIN_VERSION` (if not already defined) using plugin header version (`2.1.0`) as deterministic fallback source.
- Replaced the remaining unguarded Slick JS version resolution (`filemtime(__DIR__ . '/assets/js/bw-slick-slider.js')`) with guarded behavior.
- New version resolution contract:
  - if file exists -> use `filemtime($path)`
  - if file missing -> fallback to `BLACKWORK_PLUGIN_VERSION`

## Guarded filemtime behavior
- Guard added only for Slick JS enqueue path.
- Existing script handle, URL, dependencies, and footer flag are unchanged.
- No hooks/endpoints/runtime authority changes introduced.

## Fallback Version Strategy
- `BLACKWORK_PLUGIN_VERSION` is now single source of truth fallback for this hardening task path.
- This avoids PHP warnings and keeps deterministic cache-busting when file artifacts are missing.

## Verification Steps
1. Confirm `BLACKWORK_PLUGIN_VERSION` is defined near bootstrap constants.
2. Confirm Slick JS version uses guarded `file_exists + filemtime` fallback logic.
3. Confirm enqueue contract unchanged (`bw-slick-slider-js`, same URL/deps/footer behavior).
4. Run mandatory checks:
   - `php -l blackwork-core-plugin.php`
   - `composer run lint:main`
