# Migration Sequence (Program Baseline)

## Wave 0 — Documentation and governance baseline (current)
- Establish subsystem docs and decisions
- Align architecture, planning, and regression references
- No runtime changes

## Wave 1 — Product-card authority convergence
- Expand usage of `BW_Product_Card_Renderer` + `bw-product-card.css`
- Reduce duplicated product card markup/CTA/price logic across widget/template surfaces
- Start from lowest blast-radius adoption paths

## Wave 2 — Wall/query-grid convergence
- Define canonical merged direction for `bw-filtered-post-wall` + `bw-wallpost`
- Preserve optional filter controls in canonical widget
- Keep query determinism and filter UX contracts stable

## Wave 3 — Slider runtime convergence
- Introduce shared slider-core lifecycle authority
- Move repeated slider init logic to shared core
- Keep adapter-specific behavior for product/presentation special cases

## Wave 4 — Rationalization/deprecation cleanup
- Complete decisions for `bw-slick-slider` + `bw-slide-showcase`
- Execute `bw-add-to-cart` removal under governed closure protocol
- Remove dead code paths only after regression and closure artifacts

## Regression alignment (must hold per wave)
- Elementor frontend renders target widgets correctly
- Elementor editor and preview init/reinit remain stable
- No missing handle/runtime errors in console
- Product rendering remains coherent (image/title/price/CTA contracts)
- Slider multi-instance behavior remains deterministic
