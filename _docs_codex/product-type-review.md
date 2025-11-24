# Product Type Review

## Observed issues

1. **Documentation vs. implementation slugs diverge.** The reference document describes custom product types as `digital_asset`, `book`, and `print`, but the code actually registers `digitalassets`, `books`, and `prints`. This mismatch makes it hard to align expectations with what WooCommerce receives and may explain why a saved product type appears to "reset" for anyone following the older names. 【F:PRODUCT_TYPES_IMPLEMENTATION.md†L27-L80】【F:includes/product-types/product-types-init.php†L190-L196】

2. **Meta persistence depends on taxonomy lookups only.** The `bw_save_product_type_meta` and `bw_sync_product_type_on_term_set` routines never read the posted `product-type` value; they simply re-save whatever taxonomy term is already attached. If WooCommerce fails to assign the term on the same request (or the term is missing), the `_product_type` meta stays empty and `bw_variable_product_type_query` later falls back to the default type, which matches the reported “not saved” behavior. A save routine that reads `$_POST['product-type']` (or hooks into `woocommerce_admin_process_product_object`) would avoid this gap. 【F:includes/product-types/product-types-init.php†L93-L168】【F:includes/product-types/product-types-init.php†L134-L168】

3. **Two unrelated “Product Type” controls exist.** The custom metabox stores `_bw_product_type` (`digital` vs `physical`) for the showcase widget, but its label is simply “Product Type,” which can be mistaken for the WooCommerce product type selector. Changing it does not touch the WooCommerce product type taxonomy, so an editor may think they saved the type while only updating the widget metadata. Renaming or clarifying this field would prevent confusion. 【F:metabox/digital-products-metabox.php†L60-L120】

## Suggested next steps

- Align the documented slugs with the actual ones in code, or change the registrations to match the documented slugs.
- Capture the submitted product type directly during product saves so `_product_type` stays in sync even if taxonomy assignment fails.
- Clarify the metabox label/help text to distinguish showcase metadata from the WooCommerce product type selector.
