# Cart Popup

## Files
- [cart-popup-technical-guide.md](cart-popup-technical-guide.md): cart popup architecture, flows, and coupon handling.

## Boundary Clarification
- Cart Popup is the primary Cart domain document and is classified as non-authoritative for payment/auth/provisioning truth.
- Cart controls transition-to-checkout UX but does not own checkout payment orchestration.
- Checkout authority reference: [../checkout/checkout-architecture-map.md](../checkout/checkout-architecture-map.md).

## Current UI Contract
- The opening cart popup uses the same floating dark-glass surface language as the mobile navigation and Product Grid discovery drawer.
- Overlay, shell spacing/radius, close control, and footer CTAs are style-only treatments and do not alter cart runtime behaviour.
- A configurable shipping notice can render directly above the checkout CTA:
  - text: `Tax included. Final shipping confirmed at checkout.`
  - only the `shipping` word is linked
  - frontend render is PHP-based in the cart popup footer so the notice survives normal cart refreshes without a separate JS contract
  - when WooCommerce cart totals already include shipping, the popup also renders a dedicated `Shipping` totals row between `Subtotal` and `Total`
  - popup total remains the native WooCommerce total; no manual recalculation is applied

## Shipping Notice Settings
- Site Settings tab:
  - `Blackwork Site -> Site Settings -> Shipping`
- Option keys:
  - `bw_cart_shipping_notice_enabled`
  - `bw_cart_shipping_notice_url`
- Defaults:
  - notice enabled
  - URL fallback `/shipping/`
- Behavior:
  - if disabled, the notice is not rendered
  - if the URL is empty or invalid, frontend falls back to `/shipping/`
  - the totals area shows a `Shipping` row only when WooCommerce reports a shipping amount greater than zero
