# Cart / Checkout JS Structure Analysis

## 1) JS files by area

### Cart
- `assets/js/bw-cart.js`
- `includes/modules/header/assets/js/bw-navshop.js`
- `assets/js/bw-price-variation.js`

### Cart popup
- `cart-popup/assets/js/bw-cart-popup.js`

### Checkout
- `assets/js/bw-checkout.js`
- `assets/js/bw-checkout-notices.js`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
- `assets/js/bw-stripe-upe-cleaner.js`

### Fragment refresh / lifecycle glue
- `cart-popup/assets/js/bw-cart-popup.js`
- `assets/js/bw-checkout.js`
- `assets/js/bw-payment-methods.js`
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
- `assets/js/bw-premium-loader.js`

### Coupon handling
- `cart-popup/assets/js/bw-cart-popup.js`
- `assets/js/bw-checkout.js`

## 2) Per-file behavior

| File | Main listened events | Uses Woo fragment events | Mutates cart state | Mutates payment state |
|---|---|---|---|---|
| `assets/js/bw-cart.js` | `change` qty, `click` plus/minus, `updated_cart_totals` | Yes (`updated_cart_totals`) | Yes (triggers `update_cart`) | No |
| `cart-popup/assets/js/bw-cart-popup.js` | `added_to_cart`, `wc_fragments_refreshed`, `wc_fragment_refresh`, promo/coupon clicks, qty/remove clicks, floating trigger click | Yes (`wc_fragment_refresh`, `wc_fragments_refreshed`) | Yes (AJAX add/remove/update qty, apply/remove coupon) | No direct payment mutation (only triggers `update_checkout`) |
| `assets/js/bw-checkout.js` | `update_checkout`, `updated_checkout`, `checkout_error`, `applied_coupon`, `removed_coupon`, qty/remove clicks | Yes (checkout fragment lifecycle: `update_checkout`/`updated_checkout`) | Yes (checkout cart qty/remove + apply/remove coupon AJAX) | No direct gateway truth mutation; payment UI delegated elsewhere |
| `assets/js/bw-payment-methods.js` | `change` payment radios, `updated_checkout`, `checkout_error`, custom `payment_method_selected` | Yes (`updated_checkout`) | No | Yes (payment method selection/UI orchestration) |
| `assets/js/bw-google-pay.js` | `change` payment radios, `updated_checkout`, Stripe `paymentmethod`/`cancel`, click trigger | Yes (`updated_checkout`) | No | Yes (sets hidden method id, wallet UI/state, triggers checkout update) |
| `assets/js/bw-apple-pay.js` | `change` payment radios, `updated_checkout`, Stripe `paymentmethod`/`cancel`, click trigger | Yes (`updated_checkout`) | No | Yes (sets hidden method id, wallet UI/state, triggers checkout update) |
| `assets/js/bw-stripe-upe-cleaner.js` | `updated_checkout` | Yes (`updated_checkout`) | No | Yes (payment DOM cleanup/visibility) |
| `includes/modules/header/assets/js/bw-navshop.js` | click on `.bw-navshop__cart[data-use-popup=yes]` | No | No | No |
| `assets/js/bw-price-variation.js` | add-to-cart button click, triggers `adding_to_cart`/`added_to_cart` | Indirect (fires add-to-cart events, not fragment listener) | Yes (add-to-cart flow trigger) | No |
| `assets/js/bw-premium-loader.js` | `update_checkout`, `updated_checkout`, `adding_to_cart`, `added_to_cart` | Yes (checkout update lifecycle) | No | No |

## 3) Shared JS between Cart and Checkout

Yes.

- `assets/js/bw-premium-loader.js` is explicitly shared (handles both cart add-to-cart lifecycle and checkout lifecycle).
- `cart-popup/assets/js/bw-cart-popup.js` is cart-popup focused but triggers checkout refresh (`update_checkout`) and Woo fragment refresh, so it touches both flows operationally.

## 4) Order details duplicated for desktop/responsive? JS bound twice?

Yes, markup is duplicated at runtime in checkout mobile mode.

- `assets/js/bw-checkout.js` clones the order summary from desktop right column into `#bw-order-summary-panel` (mobile accordion), including coupon/input structures.
- JS binding is designed to avoid duplicate handlers on the same element:
  - floating-label init uses `data-bw-floating-label-initialized` guard.
  - coupon apply click uses delegated handler with `.off('click.bwCoupon').on(...)`.
- Practically, there are two DOM instances (desktop + mobile clone), each with its own element-level listeners; this is intentional. No clear double-bind on the same node was found.

## 5) Floating cart icon: fragment-aware or only UI toggle?

For floating cart trigger in popup (`.bw-cart-floating-trigger`):

- It is mainly UI-toggle/visibility logic (`click`, `scroll`, class toggle `hidden/is-visible`).
- It is updated from cart data reload paths (`loadCartContents`, `updateBadge`, `toggleFloatingTrigger`), not from a dedicated fragment-specific visibility listener.
- There is a fragment listener in `bw-cart-popup.js` (`wc_fragments_refreshed wc_fragment_refresh`), but that block updates button states, not floating-trigger visibility directly.
