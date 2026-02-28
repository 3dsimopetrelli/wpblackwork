# Theme Builder Lite - Runtime Hook Map (Foundation)

## 1) Purpose
This map defines the planned runtime hook contract for Theme Builder Lite MVP.
It is governance-facing and deterministic by design.

Scope:
- Custom Fonts
- Footer template rendering
- Single Product template rendering
- Condition resolution lifecycle

Non-scope:
- Redirect authority
- Checkout/payment/auth authority surfaces
- Global template stack ownership

## 2) Hook Inventory (Planned)

| Hook | Priority | Callback | Purpose | Domain | Notes |
|---|---:|---|---|---|---|
| `plugins_loaded` | 15 | `bw_tbl_bootstrap_module` | Load module files after plugin bootstrap | Theme Builder Lite | Must short-circuit if Elementor/Woo deps missing where required. |
| `init` | 9 | `bw_tbl_register_template_cpt` | Register `bw_template` CPT | Theme Builder Lite | CPT is storage + editor target only. |
| `init` | 10 | `bw_tbl_register_template_meta` | Register/sanitize template meta schema | Theme Builder Lite | Includes conditions/version/priority/type/enabled. |
| `elementor/cpt_support` (filter) | 10 | `bw_tbl_add_elementor_cpt_support` | Allow Elementor Free editing of `bw_template` | Elementor Integration | No custom builder logic. |
| `wp_enqueue_scripts` | 20 | `bw_tbl_enqueue_custom_fonts` | Enqueue generated `@font-face` CSS | Custom Fonts | Runs only if flags + font records available. |
| `wp` | 20 | `bw_tbl_prepare_footer_runtime` | Precompute footer candidate + remove known theme footer callbacks if needed | Footer Runtime | Execute only when footer module enabled and candidate exists. |
| `wp_head` | 99 | `bw_tbl_footer_fallback_css` | Conditional footer-hide CSS fallback | Footer Runtime | Used only when callback removal is not possible. |
| `wp_footer` | 20 | `bw_tbl_render_footer_template` | Render resolved footer template | Footer Runtime | Fail-open: if resolve/render fails, do nothing. |
| `template_redirect` | 9 | `bw_tbl_prepare_single_product_runtime` | Resolve single product candidate and wire request-local hooks | Single Product Runtime | Must only run on `is_product()`. |
| `woocommerce_before_single_product` | 5 | `bw_tbl_render_single_product_template` | Render resolved Elementor template for single product | Single Product Runtime | Active only if resolver selected a template for this request. |

## 3) Woo Single Product Replacement Contract

When `bw_tbl_prepare_single_product_runtime` resolves a template:
- Remove default Woo single product callbacks for current request only.
- Register custom renderer at `woocommerce_before_single_product` priority 5.

Default callbacks intended for removal (request-local):
- `woocommerce_before_single_product_summary`:
  - `woocommerce_show_product_sale_flash` (10)
  - `woocommerce_show_product_images` (20)
- `woocommerce_single_product_summary`:
  - `woocommerce_template_single_title` (5)
  - `woocommerce_template_single_rating` (10)
  - `woocommerce_template_single_price` (10)
  - `woocommerce_template_single_excerpt` (20)
  - `woocommerce_template_single_add_to_cart` (30)
  - `woocommerce_template_single_meta` (40)
  - `woocommerce_template_single_sharing` (50)
  - `WC_Structured_Data::generate_product_data` (60)
- `woocommerce_after_single_product_summary`:
  - `woocommerce_output_product_data_tabs` (10)
  - `woocommerce_upsell_display` (15)
  - `woocommerce_output_related_products` (20)

Governance rule:
- This removal must be request-scoped and enabled only when a valid Theme Builder Lite template is selected.

## 4) Condition Engine Runtime Calls

Primary runtime API:
- `bw_tbl_resolve_template($type, $context)`

Context keys (MVP):
- `is_product`
- `product_id`
- `product_cat_ids`
- `is_search`

Resolution sequence:
1. Load published `bw_template` posts for `$type`.
2. Filter by `_bw_tmpl_enabled`.
3. Evaluate `_bw_tmpl_conditions`.
4. Sort by deterministic tie-break.
5. Return one template ID or `0`.

Determinism tie-break (mandatory):
1. `_bw_tmpl_priority` DESC
2. Specificity score DESC
3. `post_modified_gmt` DESC
4. `ID` DESC

## 5) Conflict and Collision Rules

### 5.1 Footer collisions
Potential collisions:
- Theme footer callbacks
- Third-party footer injections
- Existing `wp_footer` plugin callbacks (example: account modal output)

Resolution:
- Theme footer suppression only when a custom footer is active.
- Keep non-theme `wp_footer` callbacks untouched.
- Custom footer render at priority 20 to remain compatible with late scripts/modals.

### 5.2 Single product collisions
Potential collisions:
- Existing Woo single product customizations
- Third-party content injections in Woo hooks

Resolution:
- Default Woo callbacks are removed only when custom template active.
- Third-party callbacks are not globally removed unless explicitly mapped in future hardening pass.
- If collisions remain, maintain feature-flagged off switch at module level.

### 5.3 Existing plugin override stack
Existing stack to preserve:
- `woocommerce_locate_template` and `woocommerce_locate_core_template` custom resolver in `woocommerce/woocommerce-init.php`.

Rule:
- Theme Builder Lite must not modify this resolver behavior for MVP.

## 6) Feature Flag Hook Gates

Option key:
- `bw_theme_builder_lite_flags`

Gate checks:
- Before registering runtime hooks, check `enabled`.
- Before footer hooks, check `footer_templates_enabled`.
- Before single product hooks, check `single_product_templates_enabled`.
- Before custom fonts enqueue, check `custom_fonts_enabled`.

Rollback behavior:
- Turning flags off disables callbacks and returns control to theme/Woo defaults.

## 7) Fallback and Fail-Open Rules

Footer fail-open:
- Resolver miss -> do nothing.
- Render error -> do nothing and keep theme footer.

Single product fail-open:
- Resolver miss -> no hook removals; Woo default lifecycle remains.
- Invalid template content -> abort custom render and keep Woo default.

Custom fonts fail-open:
- Invalid source or broken metadata -> skip font-face generation for that entry.

## 8) What Must Not Change

- Do not alter Tier 0 hook priorities documented in core runtime map.
- Do not hijack `template_include` globally.
- Do not register hooks without feature-flag gating.
- Do not suppress theme fallback when no valid custom template exists.
