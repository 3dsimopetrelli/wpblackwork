# Rationalization and Deprecation Policy

## Status decisions (governed)
- `bw-add-to-cart`: **DELETE** (completed)
- `bw-add-to-cart-variation`: **DELETE** (completed)
- `bw-wallpost`: **DELETE** (completed), replacement path is `bw-product-grid` with `Enable Filter = No`
- `bw-product-grid`: **KEEP as canonical wall/query-grid widget** (supports filtered and non-filtered mode)
- `bw-product-slide`: **KEEP as canonical product slider**
- `bw-presentation-slide`: **KEEP as specialized presentation/gallery slider**
- `bw-slick-slider` + `bw-slide-showcase`: **RATIONALIZE/MERGE path under review**
- `bw-related-products`: **KEEP as reference for shared product-card reuse**

## Decision categories
- `KEEP`: retain as-is with routine maintenance
- `KEEP + OPTIMIZE`: retain, but reduce duplication and harden runtime
- `MERGE`: converge two overlapping widgets into one canonical surface
- `REBUILD`: rework internals while preserving intended feature role
- `DEPRECATE`: freeze for new usage, keep temporarily for compatibility
- `DELETE`: remove after migration and validation gates

## Deprecation/removal policy
Before marking a widget as deprecated or deleted:
1. Identify replacement/canonical target
2. Validate no critical template/runtime dependency remains
3. Add migration note in feature docs and task closure artifact (`docs/tasks/`)
4. Run family regression checks (frontend + Elementor editor/preview)
5. Update governance docs if risk status changes

Current removal notes:
- replacement direction remains explicit and conservative (no implicit/automatic migration promises)
- removed widgets stay documented in governance/closure artifacts for traceability

## Non-negotiable constraints
- no undeclared behavior drift for WooCommerce product rendering
- no hidden asset loading regressions
- no breaking Elementor editor initialization
- apply incremental waves only
