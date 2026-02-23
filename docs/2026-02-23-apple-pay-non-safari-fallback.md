# Apple Pay Non-Safari Fallback (2026-02-23)

## Why we do not click Stripe Express buttons programmatically
The Stripe plugin renders Express Checkout buttons inside cross-origin iframes (Stripe Elements).
Because of browser security boundaries, our custom gateway script cannot trigger those buttons programmatically.

## Apple Pay State Machine
Implemented in `assets/js/bw-apple-pay.js` with a single UI source of truth:
- `idle`
- `method_selected`
- `native_available`
- `native_unavailable_helper`
- `native_unavailable_inline_possible`
- `processing`
- `error`

The state machine controls:
- Apple Pay primary button visibility/enabled/loading state
- WooCommerce place-order visibility
- Apple Pay messaging in accordion placeholder

## Non-Safari fallback modes
Configured in Blackwork Site > Checkout > Apple Pay via `Non-Safari fallback mode`.

### `helper_scroll` (default)
- Apple Pay primary button remains visible when Apple Pay is selected.
- Button action scrolls user to top Express Checkout area.
- No PaymentIntent is created in this path.

### `inline_express`
- Capability detection runs first.
- If a safe/confirmable inline Apple Pay flow is not available in the current browser context,
  UI falls back to a clear guidance message and **does not** create PaymentIntents.

## Regression safety notes
- Existing webhook-based payment completion flow is unchanged.
- Existing gateway/server PaymentIntent logic is unchanged.
- Google Pay and Klarna code paths are untouched.

## Test checklist
1. Safari + Apple Wallet: native Apple Pay available, custom Apple Pay button submits wallet flow.
2. Chrome (helper mode): Apple Pay selected -> helper guidance + scroll behavior, no PI created.
3. Chrome (inline mode): if not feasible, show fallback message and no PI creation.
4. Rapid payment method switching + `updated_checkout`: no duplicated handlers/buttons.
