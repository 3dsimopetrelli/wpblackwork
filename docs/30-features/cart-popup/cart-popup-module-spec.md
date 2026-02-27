# Cart Pop-Up Module Specification

## 1) Module Classification

- Domain: Cart Interaction Domain
- Layer: Operational UX Layer
- Tier: Tier 0
- Authority Level: None (non-authoritative)
- Business Mutation Scope: Cart-only
- Payment Mutation: Forbidden
- Provisioning Mutation: Forbidden
- Consent Mutation: Forbidden

Feature flag behavior (admin toggle model):
- `bw_cart_popup_active` defines module activation intent.
- `bw_cart_popup_slide_animation` controls auto-open behavior on add-to-cart lifecycle.
- `bw_cart_popup_show_floating_trigger` controls floating trigger surface visibility.
- Runtime precedence MUST be read from current implementation behavior, not inferred intent.
- Current implementation note: add-to-cart open behavior is effectively gated by `slide_animation` and widget `data-open-cart-popup="1"` paths; governance interpretation remains non-authoritative.

## 2) Progressive Enhancement Model

Cart Pop-Up is a Progressive Enhancement Layer over the canonical WooCommerce Cart Page.

Baseline behavior (canonical Woo flow):
- Add-to-cart redirects to Cart Page.
- Cart Page functions independently of Cart Pop-Up JS.
- Checkout remains accessible from canonical Cart Page.

Enhancement behavior (when JS/module is operational):
- JS may intercept add-to-cart interaction flows.
- Slide-in panel renders cart contents, totals, coupon interactions, and CTA.
- Floating trigger reflects cart operational state.
- `update_checkout` may be emitted operationally after cart-affecting interactions.

Degrade-safely rules:
- If Cart Pop-Up JS fails, default Woo behavior MUST continue.
- No business logic MAY depend exclusively on Cart Pop-Up JS.
- Canonical Cart Page MUST remain fully checkout-compatible.

No divergence rule:
- Coupon logic MUST be identical to Cart Page.
- Quantity/remove logic MUST be identical to Cart Page.
- Totals shown in popup MUST derive from canonical Woo cart session only.

## 3) Admin Configuration Surface

Primary options and defaults (current implementation):

- Activation flag:
  - `bw_cart_popup_active` (default `0`)
- Floating trigger toggle:
  - `bw_cart_popup_show_floating_trigger` (default `0`)
- Slide-in animation / add-to-cart open behavior:
  - `bw_cart_popup_slide_animation` (default `1`)
- Width controls (desktop):
  - `bw_cart_popup_panel_width` in px (default `400`)
- Width controls (mobile):
  - `bw_cart_popup_mobile_width` in % (default `100`)
- Overlay color:
  - `bw_cart_popup_overlay_color` (default `#000000`)
- Overlay opacity:
  - `bw_cart_popup_overlay_opacity` (default `0.5`)
- Panel background:
  - `bw_cart_popup_panel_bg` (default `#ffffff`)

Responsive breakpoint behavior:
- Dynamic CSS applies mobile width at `max-width: 768px`.

Option interaction constraints:
- Floating trigger behavior MUST NOT define business truth; it is display-only.
- Slide-in behavior MUST remain an interaction enhancement, not authority.
- Width/overlay/presentation options MUST NOT alter cart business rules.

## 4) Runtime Lifecycle

Operational flow:

1. Add-to-cart interception
- Cart Pop-Up listens to Woo add-to-cart lifecycle (`added_to_cart`) and widget-specific paths.
- Optional direct interception paths may convert eligible link actions to AJAX add.

2. Fragment lifecycle integration
- Cart Pop-Up emits `wc_fragment_refresh` after cart mutation operations (remove/update quantity and related refresh paths).
- Cart Pop-Up listens to `wc_fragments_refreshed` / `wc_fragment_refresh` for UI state synchronization tasks.

3. Checkout recomputation trigger
- Cart Pop-Up may emit `update_checkout` after cart/coupon operations that affect checkout totals.
- `update_checkout` is recomputation trigger only and MUST remain non-authoritative.

4. Coupon lifecycle
- Popup coupon apply/remove uses popup-specific AJAX endpoints.
- Resulting totals/coupon presentation MUST reflect canonical Woo cart session outcomes.

5. Quantity/remove lifecycle
- Quantity and remove operations are executed via Woo AJAX-backed endpoints.
- UI updates follow response-driven refresh and fragment sync.

6. Floating trigger lifecycle
- Visibility is controlled by cart-count state + scroll visibility policy.
- Trigger is operational UX only and MUST NOT infer authority.

7. Shared loader interaction
- `bw-premium-loader.js` may react to checkout/cart lifecycle events for UX feedback.
- Loader coupling MUST remain presentation-only.

Authority clarification:
- No authority mutation occurs in Cart Pop-Up JS layer.
- Payment truth, order truth, entitlement truth, and consent truth are outside Cart Pop-Up authority.

## 5) Allowed Mutations

Cart Pop-Up MAY:
- Mutate cart state via Woo AJAX-backed cart operations.
- Trigger `update_checkout` as recomputation instruction.
- Trigger Woo fragment refresh lifecycle events for UI synchronization.
- Mutate UI state (panel visibility, badges, floating trigger, messages, local presentation).

## 6) Forbidden Mutations

Cart Pop-Up MUST NOT:
- Mutate payment selection state.
- Define payment truth.
- Define order authority.
- Define entitlement/provisioning state.
- Define consent state.
- Override canonical cart/session business logic.
- Promote UI/fragment/redirect signals to authority status.

## 7) Coupling Map

Dependencies:
- Woo fragments lifecycle (`wc_fragment_refresh`, `wc_fragments_refreshed`).
- Checkout recomputation lifecycle (`update_checkout`, downstream `updated_checkout`).
- Shared loader utility (`bw-premium-loader.js`) for UX feedback.
- Checkout domain surfaces (recompute and reflected totals only).
- Payment UI layer (read-only awareness via checkout lifecycle side effects).

Allowed coupling:
- Event-based operational coupling for recomputation and UI refresh.
- Read-only coupling to downstream reflected states.
- Shared lifecycle listeners for UX continuity.

Prohibited coupling:
- Any coupling that gives Cart Pop-Up authority over payment/order/provisioning/consent truth.
- Any coupling where payment UI defines cart business truth.
- Any direct provider-authority mutation path from Cart Pop-Up.

## 8) Failure Modes & Risk Analysis

| Failure Mode | Expected Behavior | Degrade Strategy | Non-Authoritative Guarantee |
|---|---|---|---|
| Fragment failure / missed refresh | Popup UI may be stale temporarily | Fallback to canonical Woo cart page behavior; manual refresh/re-entry | Business truth remains canonical Woo session |
| `update_checkout` delay | Checkout totals/UI refresh may lag | Recompute completes when lifecycle event resolves; no authority decision from delay | No payment truth inferred from interim UI |
| JS race conditions in popup events | Duplicate UI actions may occur transiently | Event-driven convergence via canonical server responses | Authority not mutated by JS race outcomes |
| Mobile clone / responsive UI conflicts (checkout-adjacent) | Duplicate presentation surfaces may drift temporarily | Re-init/rebind logic and updated checkout refresh paths restore presentation | Clone surfaces remain presentation-only |
| Loader misbehavior (`bw-premium-loader.js`) | Incorrect loading UX only | Loader can fail independently without blocking canonical flows | Loader has no business authority |
| Admin misconfiguration | Popup behavior may be suboptimal/unexpected | Canonical Woo cart/checkout flow remains valid | Options do not grant authority mutation |
| Disabled pop-up state | No panel enhancement | Baseline Woo cart and checkout paths continue | Canonical flow unchanged |

## 9) Regression Sensitivity (Tier 0 Flags)

Changes requiring Tier 0 regression validation include:
- Woo fragment lifecycle behavior changes.
- `update_checkout` trigger behavior changes.
- Checkout JS modifications affecting recomputation/render coupling.
- Payment JS modifications affecting checkout render coupling.
- Shared loader utility changes touching cart/checkout lifecycle UX.
- WooCommerce major version updates affecting fragments/events/cart session behavior.
- Cart Pop-Up AJAX endpoint contract changes (coupon/quantity/remove payload handling).

## 10) Cross-References

- ADR-001 Selector Authority: `../../60-adr/ADR-001-upe-vs-custom-selector.md`
- ADR-002 Authority Hierarchy: `../../60-adr/ADR-002-authority-hierarchy.md`
- ADR-003 Callback Anti-Flash: `../../60-adr/ADR-003-callback-anti-flash-model.md`
- ADR-005 Claim Idempotency: `../../60-adr/ADR-005-claim-idempotency-rule.md`
- ADR-006 Provider Switch Model: `../../60-adr/ADR-006-provider-switch-model.md`
- Cart vs Checkout Responsibility Matrix: `../../40-integrations/cart-checkout-responsibility-matrix.md`
- Cart/Checkout JS Structure Analysis: `../../50-ops/audits/cart-checkout-js-structure-analysis.md`
