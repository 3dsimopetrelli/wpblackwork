# Checkout

## Files
- [checkout-architecture-map.md](checkout-architecture-map.md): official checkout architecture reference map.
- [complete-guide.md](complete-guide.md): canonical checkout functional and technical guide.
- [maintenance-guide.md](maintenance-guide.md): checkout maintenance and hardening runbook.
- [order-confirmation-style-guide.md](order-confirmation-style-guide.md): order received page style/system guide.
- [woocommerce-customizations-folder.md](woocommerce-customizations-folder.md): WooCommerce folder scope note.

## Boundary Clarification
- Checkout is the primary authority domain for payment orchestration, selector determinism, fragment reflection discipline, and return-surface rendering.
- Cart/Mini-cart is non-authoritative and hands off control at transition-to-checkout.
- Cart domain reference: [../cart-popup/cart-popup-technical-guide.md](../cart-popup/cart-popup-technical-guide.md).
