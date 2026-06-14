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
  - the popup does not render a shipping amount row because shipping may vary by destination and is confirmed at checkout
  - popup total remains the native WooCommerce total; no manual recalculation is applied

## Shipping Notice Settings
- Site Settings tab:
  - `Blackwork Site -> Site Settings -> Shipping`
- Option keys:
  - `bw_cart_shipping_notice_enabled`
  - `bw_cart_shipping_notice_url`
  - `bw_checkout_shipping_info_popup_text`
- Defaults:
  - notice enabled
  - URL fallback `/shipping/`
- Behavior:
  - if disabled, the notice is not rendered
  - the Shipping page link field is shown only when the cart shipping notice is enabled
  - if the URL is empty or invalid, frontend falls back to `/shipping/`
  - if enabled, the cart popup hides the shipping row and shows a display-only total that excludes shipping because final shipping is destination-dependent and confirmed at checkout
  - if disabled, the cart popup returns to normal WooCommerce-style display behavior:
    - shipping row is shown when WooCommerce reports shipping > 0
    - displayed total uses the native WooCommerce total
  - the checkout Shipping info popup text field remains visible independently from the cart popup notice toggle
  - when the checkout Shipping info popup text is empty, the `Shipping (?)` trigger is not rendered in checkout
  - checkout shipping calculations remain fully WooCommerce-native
