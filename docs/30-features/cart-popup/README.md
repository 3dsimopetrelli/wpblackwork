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
