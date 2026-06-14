# BW-TASK-20260614-cart-popup-shipping-notice-start

## Task
Add a configurable shipping notice above the Cart Pop-up checkout button.

## Scope
- Add a new `Shipping` tab under `Blackwork Site -> Site Settings`.
- Add settings for enabling/disabling the cart shipping notice and configuring its link destination.
- Render the shipping notice in the Cart Pop-up immediately above the green checkout button.
- Keep the Shipping link destination configurable and avoid hardcoded frontend URLs.
- Update docs for the new settings and frontend behavior.

## Required outputs
- `Shipping` tab available in `Blackwork Site -> Site Settings`
- `Show cart shipping notice` toggle stored in `bw_cart_shipping_notice_enabled`
- `Shipping page link` setting stored in `bw_cart_shipping_notice_url`
- Cart Pop-up notice rendered as:
  - `Tax included. Shipping calculated at checkout.`
  - only `Shipping` linked
- Docs updated for the new admin setting and cart popup behavior

## Constraints
- Do not change checkout button URL or behavior.
- Do not change cart totals, coupon behavior, cart items, or popup open/close behavior.
- If the notice is disabled, do not render it.
- If the configured link is empty or invalid, fall back to `/shipping/`.
- Prefer server-rendered PHP output so the notice remains stable across cart refreshes.
