# BW-TASK-20260614-reusable-license-terms-start

## Task
Implement reusable License Terms system with backward compatibility.

## Scope
- Add a reusable `bw_license` admin record type under `Blackwork Site`.
- Add a License Terms metabox storing reusable two-column rows in `_bw_license_rows`.
- Replace the variation-local License Terms table UI with a reusable License dropdown saved in `_bw_variation_license_id`.
- Keep legacy variation license meta readable and keep `bw_get_variation_license_table_html( $variation_id )` as the compatibility resolver.
- Preserve the existing frontend `license_html` contract consumed by `BW-SP Price Variation`.

## Required outputs
- Reusable License CPT available in wp-admin under `Blackwork Site`
- License Terms metabox with default rows and safe HTML support in Column 2
- Variation License Terms dropdown with saved selected license per variation
- Compatibility resolver that prefers reusable license rows and falls back to legacy variation-local rows
- Documentation updates for the new License architecture and fallback behavior

## Constraints
- Do not delete legacy variation license meta.
- Do not break existing variation switching, add to cart behavior, or `license_html` payload shape.
- Do not rewrite frontend JS unless strictly necessary.
- Keep the main frontend resolver function name: `bw_get_variation_license_table_html()`.
