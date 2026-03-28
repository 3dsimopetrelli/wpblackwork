# Blackwork Governance -- Task Start

## 1) Context
- Task ID: `BW-TASK-20260328-03`
- Task title: Integrate official PayPal product button into `BW-SP Price Variation`
- Request source: User request on 2026-03-28
- Expected outcome:
  - add the official `WooCommerce PayPal Payments` single-product button above `More payment options`
  - keep both surfaces governed by the same `Show More Payment Options` toggle
  - preserve the currently selected variation/license when the PayPal express button is used
- Constraints:
  - do not build a custom PayPal SDK button
  - reuse the official `WooCommerce PayPal Payments` product-button flow
  - keep `More payment options` as the text fallback under the official button

## 2) Task Classification
- Domain: Elementor Widgets / Price Variation / WooCommerce PayPal Payments
- Incident/Task type: Governed payment integration
- Risk level (L1/L2/L3): L2
- Tier classification (0/1/2/3): 1
- Affected systems:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - `assets/js/bw-price-variation.js`
  - `assets/css/bw-price-variation.css`
  - price-variation widget docs
- Integration impact: Medium

## 3) Scope Declaration
- Proposed strategy:
  - render the official PayPal single-product button only when `Show More Payment Options` is enabled
  - add a WooCommerce-compatible `form.cart variations_form` bridge inside the widget
  - sync `variation_id` and `attribute_*` hidden inputs from the active widget variation
  - keep the existing `More payment options` checkout shortcut under the official button
- Explicitly out-of-scope:
  - replacing checkout payment methods globally
  - custom PayPal SDK integration
  - changes to PayPal plugin settings pages

## 4) Risk Analysis
- Main risk:
  - the official PayPal product button expects a standard WooCommerce product form and variation fields
- Mitigation:
  - provide a synchronized `form.cart` bridge inside the widget instead of fabricating a custom checkout flow

## 5) Testing Strategy
- Local testing plan:
  - lint modified PHP
  - syntax-check modified JS
  - run project PHP lint command required by governance
  - verify docs reflect the official-button bridge behavior

## 6) Current Readiness
- Status: OPEN
- Analysis status:
  - implementation-ready
