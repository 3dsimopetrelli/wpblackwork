# Cart Popup

## Files
- [cart-popup-technical-guide.md](cart-popup-technical-guide.md): cart popup architecture, flows, and coupon handling.

## Boundary Clarification
- Cart Popup is the primary Cart domain document and is classified as non-authoritative for payment/auth/provisioning truth.
- Cart controls transition-to-checkout UX but does not own checkout payment orchestration.
- Checkout authority reference: [../checkout/checkout-architecture-map.md](../checkout/checkout-architecture-map.md).
