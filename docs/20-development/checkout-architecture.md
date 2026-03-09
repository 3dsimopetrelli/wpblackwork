# Checkout Architecture

## Payment Method Selector Interaction Model

### Context
- Surface: checkout payment selector in `#payment`.
- Runtime components:
  - WooCommerce checkout runtime (`woocommerce/assets/js/frontend/checkout.js`)
  - Blackwork selector runtime (`assets/js/bw-payment-methods.js`)

### Interaction Contract
- Row/label click uses Blackwork normalization logic to keep:
  - checked radio
  - selected row styling
  - place-order/wallet action state
  synchronized.
- Direct radio click must also converge on first click and remain selected across `updated_checkout`.

### WooCommerce Interception Behavior
- WooCommerce binds direct click handling on `input[name="payment_method"]` and stops bubbling propagation in its handler.
- This means document-level click handlers are not authoritative for the direct radio-input path.

### Why the Radio Path Is Handled Separately
- Blackwork includes a direct hook for `#payment input[name="payment_method"]` in capture phase so selection intent is recorded even when WooCommerce stops propagation.
- Native radio behavior is preserved (no forced default-cancel on direct input click).
- Re-convergence then uses the explicit user selection as preferred value, preventing first-click rollback.

### Governance Notes
- Scope of the CHECKOUT-02 fix is JS-only (`assets/js/bw-payment-methods.js`).
- No template changes, no gateway business logic changes, no Supabase surface changes.
