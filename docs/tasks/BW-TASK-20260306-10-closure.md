# BW-TASK-20260306-10 — SVG Upload Sanitization Hardening

## Scope
- `blackwork-core-plugin.php`
- `docs/00-governance/risk-register.md`

## Implementation Summary
- Preserved capability gate for SVG upload allowance (`manage_options`).
- Added fail-closed `wp_handle_upload_prefilter` security gate for SVG uploads.
- Added allowlist-based SVG sanitization path with deterministic validation checks.
- Added deterministic rejection for malformed/unsafe SVG payloads.
- Added explicit rejection policy for `svgz` uploads.
- Hardened `wp_check_filetype_and_ext` override to only normalize validated real SVG content.
- Kept non-SVG upload behavior unchanged.

## Runtime Surfaces Touched
- `upload_mimes`
- `wp_handle_upload_prefilter`
- `wp_check_filetype_and_ext`

## Verification Evidence (Planned/Manual)
- Safe SVG upload accepted and stored as sanitized payload.
- SVG containing script/event payload rejected before attachment creation.
- `svgz` rejected with deterministic error message.
- Non-SVG uploads unaffected.

## Residual Risks
- Allowlist strictness may reject some complex but benign SVG features.
- Sanitizer approach should be periodically re-reviewed against new SVG parser bypass patterns.

## Scope Drift
- None. Implementation remained inside approved SVG upload pipeline scope.
