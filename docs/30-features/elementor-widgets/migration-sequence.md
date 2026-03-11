# Migration Sequence (Program Baseline)

## Wave 0 — Documentation and governance baseline (completed)
- Subsystem docs and decisions established
- Architecture/planning/regression references aligned

## Wave 1 — Product-card authority convergence (in progress, major milestones completed)
- `BW_Product_Card_Renderer` extended with compatibility options
- Adopted in:
  - Woo related template
  - `bw-slick-slider` product path
  - `bw-wallpost` product path
  - `bw-filtered-post-wall` product path (widget + AJAX)
- Product-card duplication reduced on migrated product paths

## Wave 2 — Wall/query-grid convergence (in progress)
- Canonical direction confirmed: `bw-filtered-post-wall`
- `Enable Filter = yes/no` implemented in `bw-filtered-post-wall`
- `bw-wallpost` deprecated, replacement path documented:
  - `bw-filtered-post-wall` + `Enable Filter = No`
- Full retirement/removal of `bw-wallpost` is deferred to removal wave after validation

## Wave 3 — Slider runtime convergence (future)
- Introduce shared slider-core lifecycle authority
- Move repeated slider init logic to shared core
- Keep adapter-specific behavior for product/presentation special cases

## Wave 4 — Rationalization/deprecation cleanup (future)
- Complete decisions for `bw-slick-slider` + `bw-slide-showcase`
- Execute deprecated widget removal under governed closure protocol:
  - `bw-wallpost`
  - `bw-add-to-cart`
  - `bw-add-to-cart-variation`
- Remove dead code paths only after regression and closure artifacts

## Regression alignment (must hold per wave)
- Elementor frontend renders target widgets correctly
- Elementor editor and preview init/reinit remain stable
- No missing handle/runtime errors in console
- Product rendering remains coherent (image/title/price/CTA contracts)
- Slider multi-instance behavior remains deterministic
