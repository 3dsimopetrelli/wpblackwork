# Rationalization and Deprecation Policy

## Status decisions (governed)
- `bw-add-to-cart`: **DEPRECATE (active)** -> **DELETE** in removal wave
- `bw-add-to-cart-variation`: **DEPRECATE (active)** -> **DELETE** in removal wave
- `bw-filtered-post-wall` + `bw-wallpost`: **MERGE** into one canonical wall/query-grid widget
- `bw-wallpost`: **DEPRECATE (active)**, replacement path is `bw-filtered-post-wall` with `Enable Filter = No`
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

Current deprecation communication:
- deprecated widgets must expose an editor-facing notice in Elementor controls
- replacement direction must be explicit and conservative (no implicit/automatic migration promises)

## Non-negotiable constraints
- no undeclared behavior drift for WooCommerce product rendering
- no hidden asset loading regressions
- no breaking Elementor editor initialization
- apply incremental waves only
