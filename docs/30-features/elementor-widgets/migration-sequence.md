# Migration Sequence (Program Baseline)

## Wave 0 — Documentation and governance baseline (completed)
- Subsystem docs and decisions established
- Architecture/planning/regression references aligned

## Wave 1 — Product-card authority convergence (in progress, major milestones completed)
- `BW_Product_Card_Component` introduced as canonical authority
- `BW_Product_Card_Renderer` converted to compatibility bridge
- Adopted in:
  - Woo related template
  - `bw-slick-slider` product path
  - `bw-filtered-post-wall` product path (widget + AJAX)
  - `bw-related-products` product path
- Product-card duplication reduced on migrated product paths

## Wave 2 — Wall/query-grid convergence (completed)
- Canonical direction confirmed: `bw-filtered-post-wall`
- `Enable Filter = yes/no` implemented in `bw-filtered-post-wall`
- `bw-wallpost` replacement path validated and documented:
  - `bw-filtered-post-wall` + `Enable Filter = No`
- `bw-wallpost` removed in governed removal wave

## Wave 3 — Slider runtime convergence (future)
- Introduce shared slider-core lifecycle authority
- Move repeated slider init logic to shared core
- Keep adapter-specific behavior for product/presentation special cases

## Wave 4 — Rationalization/deprecation cleanup (completed for deprecated widgets)
- Complete decisions for `bw-slick-slider` + `bw-slide-showcase`
- Executed governed widget removal under closure protocol:
  - `bw-wallpost` (removed)
  - `bw-add-to-cart` (removed)
  - `bw-add-to-cart-variation` (removed)
- Related dead code paths/assets removed with runtime verification

## Regression alignment (must hold per wave)
- Elementor frontend renders target widgets correctly
- Elementor editor and preview init/reinit remain stable
- No missing handle/runtime errors in console
- Product rendering remains coherent (image/title/price/CTA contracts)
- Slider multi-instance behavior remains deterministic
