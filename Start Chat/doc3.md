# Start Chat — Documentation Pack 3

Generated from repository docs snapshot (excluding docs/tasks).


---

## Source: `docs/30-features/product-slide/fixes/2025-12-14-bwproductslide-cleanup-opportunities.md`

# BWProductSlide Cleanup Opportunities

## Responsive visibility handlers are triplicated
The widget defines three nearly identical functions to toggle arrows, dots, and slide-count visibility. Each copies the same breakpoint-sorting logic, debounce timers, and resize bindings, which risks drift when one handler changes. Extracting a shared helper (e.g., `createResponsiveToggle({settingKey, selector, onUpdate})`) would collapse the repeated logic and centralize breakpoint resolution, with per-control callbacks limited to applying visibility or Slick options. This would also unify the three separate `resize` listeners into a single debounced stream. 【F:assets/js/bw-product-slide.js†L869-L1043】

## Multiple viewport-driven refresh paths
Responsive updates happen through window resize listeners inside `bindResponsiveUpdates` plus separate resize listeners attached for each visibility handler. These paths all debounce independently, meaning the slider may run redundant DOM queries and Slick updates on every resize. Consolidating viewport-driven work into one debounced pipeline (e.g., a single resize subscription that dispatches to both dimension updates and control visibility) would reduce overhead and simplify cleanup when tearing down the slider. 【F:assets/js/bw-product-slide.js†L560-L673】【F:assets/js/bw-product-slide.js†L869-L1043】

## Repeated breakpoint search logic
Within the visibility handlers the same "sort breakpoints ascending, then iterate backwards to find the first matching max-width" snippet appears three times. Extracting a utility (e.g., `getMatchedBreakpoint(settings.responsive, window.innerWidth)`) would avoid subtle inconsistencies and make future breakpoint rules easier to adjust in one place. 【F:assets/js/bw-product-slide.js†L874-L898】【F:assets/js/bw-product-slide.js†L934-L958】【F:assets/js/bw-product-slide.js†L991-L1014】

## Popup lifecycle wiring could be centralized
`bindPopup` stores the popup element in the body and wires multiple event handlers (image click, close button, overlay click, Escape key) each time the slider initializes. Because `initProductSlide` tears down and recreates Slick during refreshes, the popup wiring repeats even when markup is already in `<body>`. Moving popup binding into a dedicated initializer that checks for an existing binding (or using event namespaces removed on teardown) would avoid reattaching the same listeners across refresh cycles. 【F:assets/js/bw-product-slide.js†L34-L215】【F:assets/js/bw-product-slide.js†L675-L716】【F:assets/js/bw-product-slide.js†L1095-L1129】


---

## Source: `docs/30-features/product-slide/fixes/README.md`

# Product Slide Fixes

Historical fix reports and dated audits for BW Product Slide. These are not merged into the main feature guides.

## Files
- [2025-12-10-fix-one-big-slide-responsive.md](2025-12-10-fix-one-big-slide-responsive.md)
- [2025-12-10-fix-resize-breakpoint-regression.md](2025-12-10-fix-resize-breakpoint-regression.md)
- [2025-12-10-fix-responsive-arrows-slidecount.md](2025-12-10-fix-responsive-arrows-slidecount.md)
- [2025-12-10-fix-variable-width-responsive.md](2025-12-10-fix-variable-width-responsive.md)
- [2025-12-10-product-slide-popup-not-opening.md](2025-12-10-product-slide-popup-not-opening.md)
- [2025-12-13-fix-drag-swipe-animation.md](2025-12-13-fix-drag-swipe-animation.md)
- [2025-12-13-product-slide-vacuum-cleanup-report.md](2025-12-13-product-slide-vacuum-cleanup-report.md)
- [2025-12-14-bwproductslide-cleanup-opportunities.md](2025-12-14-bwproductslide-cleanup-opportunities.md)


---

## Source: `docs/30-features/product-types/README.md`

# Product Types

## Files
- [product-types-implementation.md](product-types-implementation.md): implementation and behavior of custom product types.
- [product-type-review.md](product-type-review.md): technical review and findings for product type behavior.


---

## Source: `docs/30-features/product-types/product-type-review.md`

# Product Type Review

## Observed issues

1. **Documentation vs. implementation slugs diverge.** The reference document describes custom product types as `digital_asset`, `book`, and `print`, but the code actually registers `digitalassets`, `books`, and `prints`. This mismatch makes it hard to align expectations with what WooCommerce receives and may explain why a saved product type appears to "reset" for anyone following the older names. 【F:PRODUCT_TYPES_IMPLEMENTATION.md†L27-L80】【F:includes/product-types/product-types-init.php†L190-L196】

2. **Meta persistence depends on taxonomy lookups only.** The `bw_save_product_type_meta` and `bw_sync_product_type_on_term_set` routines never read the posted `product-type` value; they simply re-save whatever taxonomy term is already attached. If WooCommerce fails to assign the term on the same request (or the term is missing), the `_product_type` meta stays empty and `bw_variable_product_type_query` later falls back to the default type, which matches the reported “not saved” behavior. A save routine that reads `$_POST['product-type']` (or hooks into `woocommerce_admin_process_product_object`) would avoid this gap. 【F:includes/product-types/product-types-init.php†L93-L168】【F:includes/product-types/product-types-init.php†L134-L168】

3. **Two unrelated “Product Type” controls exist.** The custom metabox stores `_bw_product_type` (`digital` vs `physical`) for the showcase widget, but its label is simply “Product Type,” which can be mistaken for the WooCommerce product type selector. Changing it does not touch the WooCommerce product type taxonomy, so an editor may think they saved the type while only updating the widget metadata. Renaming or clarifying this field would prevent confusion. 【F:metabox/digital-products-metabox.php†L60-L120】

## Suggested next steps

- Align the documented slugs with the actual ones in code, or change the registrations to match the documented slugs.
- Capture the submitted product type directly during product saves so `_product_type` stays in sync even if taxonomy assignment fails.
- Clarify the metabox label/help text to distinguish showcase metadata from the WooCommerce product type selector.


---

## Source: `docs/30-features/product-types/product-types-implementation.md`

# Product Types Implementation - Technical Documentation

## Overview

This document describes the implementation of custom WooCommerce product types and ensures that standard WooCommerce product types remain completely unaffected.

## Product Types

### Standard WooCommerce Product Types (UNMODIFIED)

The following product types work **exactly as in vanilla WooCommerce** with **NO customizations**:

- ✅ Simple Product
- ✅ Grouped Product
- ✅ External/Affiliate Product
- ✅ Variable Product

**Standard tabs for these types:**
- General
- Inventory
- Shipping (where applicable)
- Linked Products
- Attributes
- Variations (Variable Product only)
- Advanced

### Custom Product Types (WITH CUSTOMIZATIONS)

#### 1. Digital Assets (`digitalassets`)

**Behavior:** Virtual + Downloadable product with variation support

**Characteristics:**
- ✅ Virtual product (no shipping needed)
- ✅ Downloadable product (with downloadable files)
- ✅ Supports variations (extends `WC_Product_Variable`)

**Product Data Tabs:**
- ✅ General (with Downloadable Files section)
- ✅ Inventory
- ✅ Linked Products
- ✅ Attributes (with "Used for variations" checkbox)
- ✅ Variations
- ✅ Advanced
- ❌ Shipping (hidden - virtual products don't ship)

**Automatically enforced:**
- `_virtual` = 'yes'
- `_downloadable` = 'yes'
- Downloadable files field is visible

---

#### 2. Homebook (`books`)

**Behavior:** Physical product with shipping and variation support

**Characteristics:**
- ✅ Physical product (needs shipping)
- ✅ Supports variations (extends `WC_Product_Variable`)
- ❌ NOT downloadable
- ❌ NOT virtual

**Product Data Tabs:**
- ✅ General
- ✅ Inventory
- ✅ Shipping (with weight, dimensions, shipping class)
- ✅ Linked Products
- ✅ Attributes (with "Used for variations" checkbox)
- ✅ Variations
- ✅ Advanced

**Automatically enforced:**
- `_virtual` = 'no'
- `_downloadable` = 'no'
- Downloadable files field is **completely hidden**

---

#### 3. Print (`prints`)

**Behavior:** Physical product with shipping and variation support (same as Books)

**Characteristics:**
- ✅ Physical product (needs shipping)
- ✅ Supports variations (extends `WC_Product_Variable`)
- ❌ NOT downloadable
- ❌ NOT virtual

**Product Data Tabs:**
- ✅ General
- ✅ Inventory
- ✅ Shipping (with weight, dimensions, shipping class)
- ✅ Linked Products
- ✅ Attributes (with "Used for variations" checkbox)
- ✅ Variations
- ✅ Advanced

**Automatically enforced:**
- `_virtual` = 'no'
- `_downloadable` = 'no'
- Downloadable files field is **completely hidden**

---

## Implementation Details

### File Structure

```
/includes/product-types/
├── product-types-init.php              # Main initialization and customization logic
├── class-bw-product-type-digital.php   # WC_Product_Digital_Asset class
├── class-bw-product-type-book.php      # WC_Product_Book class
└── class-bw-product-type-print.php     # WC_Product_Print class
```

### Key Implementation Points

#### 1. Product Class Hierarchy

All custom product types extend `WC_Product_Variable` to support variations:

```php
class WC_Product_Digital_Asset extends WC_Product_Variable { ... }
class WC_Product_Book extends WC_Product_Variable { ... }
class WC_Product_Print extends WC_Product_Variable { ... }
```

#### 2. Tab Visibility (PHP Filter)

The `woocommerce_product_data_tabs` filter **adds** `show_if_*` classes for custom types:

- Does NOT remove or modify existing classes
- Standard WooCommerce types retain their original behavior
- Only adds visibility for custom types

**Example:**
```php
$tabs['general']['class'][] = 'show_if_digital_asset';  // ADDS to existing classes
```

This means:
- When product type = `simple`: General tab shows (matches `show_if_simple`)
- When product type = `digital_asset`: General tab shows (matches `show_if_digital_asset`)
- No interference between types

#### 3. JavaScript Enhancements

The JavaScript (`admin_footer` hook) **only affects custom product types**:

**STEP 1: Enable variation panels**
```javascript
$('.options_group.show_if_variable').addClass('show_if_digital_asset');
$('#variable_product_options').addClass('show_if_digital_asset');
```

**STEP 2: Show "Used for variations" checkbox**
```javascript
if (productType === 'digital_asset' || productType === 'book' || productType === 'print') {
    $('.woocommerce_attribute').each(function() {
        $(this).find('.enable_variation').parent().show();
    });
}
```

**STEP 3: Control downloadable fields visibility**
```javascript
// Only for custom types - standard types use default behavior
if (productType === 'book' || productType === 'print') {
    $('.show_if_downloadable').hide();  // Hide for physical products
}
else if (productType === 'digital_asset') {
    $('.show_if_downloadable').show();  // Show for digital products
}
// Standard types: no changes - default WooCommerce behavior
```

#### 4. Meta Value Enforcement

The `bw_force_product_type_meta_values()` function runs on `woocommerce_process_product_meta`:

```php
switch ( $product_type ) {
    case 'digital_asset':
        update_post_meta( $post_id, '_virtual', 'yes' );
        update_post_meta( $post_id, '_downloadable', 'yes' );
        break;

    case 'book':
    case 'print':
        update_post_meta( $post_id, '_virtual', 'no' );
        update_post_meta( $post_id, '_downloadable', 'no' );
        delete_post_meta( $post_id, '_downloadable_files' );
        break;

    // Standard types: NO changes (default case is empty)
}
```

---

## Attributes and Variations

### How Attributes Work

**Product Attributes are ALWAYS product-specific:**
- Each product has its own set of attributes stored in `_product_attributes` meta
- Attributes are NOT shared between products
- Variations are created based on each product's individual attributes

**"Used for variations" Checkbox:**
- ✅ Shows for Digital Assets, Books, Prints
- ✅ Allows creating variations based on attributes
- ✅ Controlled by JavaScript to ensure visibility
- ✅ Works exactly like Variable products in standard WooCommerce

### Global Attributes vs Product Attributes

**Global Attributes** (created in Products > Attributes):
- Can be used across multiple products
- Must be manually selected for each product
- Still stored individually per product in `_product_attributes`

**Product-Specific Attributes:**
- Created directly on the product edit page
- Only exist for that specific product
- Stored in `_product_attributes` meta

---

## Testing Checklist

### Test 1: Standard WooCommerce Product Types

Create and test each standard type to ensure **NO customizations**:

- [ ] **Simple Product**
  - [ ] All standard tabs visible (General, Inventory, Shipping, Linked Products, Advanced)
  - [ ] Virtual/Downloadable checkboxes work normally
  - [ ] No unexpected hidden fields

- [ ] **Variable Product**
  - [ ] All standard tabs visible including Variations
  - [ ] Attributes tab shows "Used for variations" checkbox
  - [ ] Variations can be created normally

- [ ] **Grouped Product**
  - [ ] Standard behavior unchanged
  - [ ] Linked Products tab works

- [ ] **External/Affiliate Product**
  - [ ] Product URL field visible and works
  - [ ] Button text field visible and works

### Test 2: Digital Assets

- [ ] Create a new Digital Assets product
- [ ] **Tabs visible:** General, Inventory, Linked Products, Attributes, Variations, Advanced
- [ ] **Tabs hidden:** Shipping
- [ ] **Downloadable Files section visible** in General tab
- [ ] Add attributes and check "Used for variations"
- [ ] Create variations successfully
- [ ] Save product and verify:
  - [ ] `_virtual` = 'yes'
  - [ ] `_downloadable` = 'yes'
  - [ ] Downloadable files are saved
  - [ ] Variations work correctly

### Test 3: Books

- [ ] Create a new Books product
- [ ] **Tabs visible:** General, Inventory, Shipping, Linked Products, Attributes, Variations, Advanced
- [ ] **Downloadable Files section HIDDEN**
- [ ] **Shipping tab VISIBLE** with weight, dimensions, shipping class
- [ ] Add attributes and check "Used for variations"
- [ ] Create variations successfully
- [ ] Save product and verify:
  - [ ] `_virtual` = 'no'
  - [ ] `_downloadable` = 'no'
  - [ ] No downloadable files saved
  - [ ] Shipping fields work correctly
  - [ ] Variations work correctly

### Test 4: Prints

- [ ] Create a new Prints product
- [ ] **Same behavior as Books** (physical product with variations)
- [ ] **Tabs visible:** General, Inventory, Shipping, Linked Products, Attributes, Variations, Advanced
- [ ] **Downloadable Files section HIDDEN**
- [ ] **Shipping tab VISIBLE**
- [ ] Add attributes and check "Used for variations"
- [ ] Create variations successfully
- [ ] Save product and verify:
  - [ ] `_virtual` = 'no'
  - [ ] `_downloadable` = 'no'
  - [ ] No downloadable files saved
  - [ ] Shipping fields work correctly
  - [ ] Variations work correctly

---

## Troubleshooting

### Issue: "Used for variations" checkbox not visible

**Solution:** The JavaScript uses a MutationObserver to detect when attributes are added. If the checkbox still doesn't appear:
1. Refresh the page after adding an attribute
2. Check that the product type is set to Digital Assets, Books, or Prints
3. Verify JavaScript is not blocked by browser console errors

### Issue: Downloadable files showing on Books/Prints

**Solution:** The `handleDownloadableFields()` function should hide these. If they still appear:
1. Refresh the page
2. Check browser console for JavaScript errors
3. Ensure the product type is correctly set

### Issue: Standard WooCommerce types behaving unexpectedly

**Solution:** Our code should NOT affect standard types. If issues occur:
1. Check that the product type is actually a standard type (not a custom one)
2. Disable the plugin temporarily to verify the issue is related
3. Check for conflicts with other plugins

---

## Summary of Changes

### What Changed

1. **Enhanced JavaScript** to properly show "Used for variations" checkbox
2. **Improved documentation** throughout the code
3. **Better separation** between custom and standard product types
4. **Fixed visibility** of variation-related UI elements

### What Did NOT Change

1. ✅ Standard WooCommerce product types remain **completely unmodified**
2. ✅ Custom product type classes (Digital Asset, Book, Print) remain the same
3. ✅ Meta value enforcement logic remains the same
4. ✅ Product data tabs filtering remains the same (just better documented)

---

## Conclusion

This implementation ensures:

- ✅ **Standard WooCommerce product types work exactly as in vanilla WooCommerce**
- ✅ **Custom product types (DigitalAssets, Books, Prints) have proper variation support**
- ✅ **Attributes tab shows "Used for variations" checkbox for all variable products**
- ✅ **No interference between standard and custom product types**
- ✅ **Clean, well-documented code with clear separation of concerns**

All requirements from the specification have been met.


---

## Source: `docs/30-features/redirect/redirect-engine-v2-spec.md`

# Redirect Engine — v2 Specification

## 1. Scope
The Redirect Engine v2 defines deterministic internal routing redirections configured from the Blackwork admin panel.

This module is classified as **Tier 0 Routing Authority**.

Responsibility boundaries:
- The module SHALL evaluate redirect rules for eligible frontend requests.
- The module SHALL enforce protected-route and safety policies before any redirect execution.
- The module SHALL provide deterministic, auditable matching behavior.

Non-responsibilities:
- The module MUST NOT redefine payment, order, auth, provisioning, or consent authority.
- The module MUST NOT override protected commerce and platform-critical routes.
- The module MUST NOT act as a generic external redirect broker.

## 2. Redirect Types
Redirect Engine v2 SHALL support:
- `301` (Permanent Redirect)
- `302` (Temporary Redirect)

Admin behavior:
- Admin UI MUST provide explicit status-code selection per rule.
- Default status code MUST be `301`.
- Status value MUST be validated server-side to allow only `301` or `302`.

Runtime behavior:
- Runtime MUST emit the configured rule status code.
- Invalid or missing status code in persisted data MUST fallback to `301`.

## 3. Protected Route Policy (Critical)
The following route families are immutable protected surfaces:
- `/wp-admin`
- `/wp-login.php`
- `/cart`
- `/checkout`
- `/my-account`
- `/wc-api`
- REST routes (`/wp-json/*` and equivalent REST base paths)
- Any WooCommerce core page route returned by `wc_get_page_permalink()`
- Any WooCommerce account/checkout endpoint derived from configured Woo endpoints

Critical rules:
- Redirect Engine MUST NOT allow rule creation where **source** matches a protected route.
- Redirect Engine MUST NOT allow rule creation where **target** matches a protected route.
- Redirect Engine MUST NOT execute a redirect when protected-route gate fails at runtime.
- Admin validation MUST reject protected-route violations with explicit per-row error messages.
- Any route that begins with a protected base path MUST be treated as protected (for example: `/checkout/*`, `/my-account/*`, `/cart/*`).

The protected-route policy SHALL be treated as non-overridable in normal admin operations.

## 4. Loop Prevention Model
Redirect Engine v2 SHALL enforce loop prevention at save-time and runtime.

Save-time validation:
- Self-loop detection MUST reject `source == target` after normalization.
- Direct two-node loop detection MUST reject `A -> B` if `B -> A` exists (enabled rules scope).
- Multi-hop cycle detection MUST run on the full enabled rule graph and MUST reject any cycle.

Runtime protection:
- Runtime SHALL maintain a redirect hop counter for the current request chain.
- Maximum hop limit SHALL be `5`.
- If hop limit is exceeded, redirect execution MUST abort and request MUST continue without additional redirect.

Determinism requirements:
- Loop checks MUST run against normalized canonical source/target keys.
- Disabled rules MUST NOT participate in runtime graph.

## 5. Matching Model
Supported match types:
- `exact` (default)
- `prefix` (optional, explicit per-rule flag)
- `wildcard` (optional, explicit syntax and explicit per-rule enable)

Exact matching:
- MUST use normalized source key comparison.
- MUST be deterministic and case policy MUST be defined globally (default: case-sensitive path, exact query compare).

Prefix matching:
- MUST only run when rule `match_type=prefix`.
- MUST apply longest-prefix-wins policy.

Wildcard matching:
- SHALL be disabled by default.
- If enabled, wildcard syntax MUST be explicit and documented (for example `*` segment wildcard).
- Wildcard processing MUST be deterministic and bounded.

Normalization policy:
- Leading slash MUST be normalized for path keys.
- Query parameters MUST be normalized into canonical order before match evaluation.
- Trailing slash policy MUST be explicit and global:
  - Either strict mode (trailing slash significant), or
  - canonical mode (trailing slash normalized).

v2 default policy:
- Query canonicalization SHALL be enabled.
- Trailing slash SHALL be normalized to canonical path form for matching.

## 6. Precedence Model
Redirect evaluation order SHALL be deterministic and globally enforced:

1. WordPress canonical handling
2. Protected route gate
3. Blackwork Redirect Engine
4. WooCommerce endpoint routing

Hooking requirements:
- Redirect Engine MUST run on the `template_redirect` hook.
- Redirect Engine MUST run AFTER WordPress canonical handling with priority `>= 10`.
- Redirect Engine MUST run BEFORE WooCommerce endpoint handlers.
- Redirect Engine priority `< 10` is prohibited.
- Runtime MUST document and enforce the final hook priority used.

Conflict policy:
- If protected route gate blocks evaluation, Blackwork redirect MUST NOT execute.
- If no redirect rule matches, runtime MUST pass control to downstream Woo routing.

## 7. Performance Model
Redirect Engine v2 performance guardrails:

Indexing:
- Exact-match rules MUST be indexed by normalized source key in hash-map form.
- Exact lookup MUST be O(1) average-case.
- Prefix/wildcard rules MUST be stored in dedicated structures separate from exact index.

Evaluation path:
- Exact index check SHALL run first.
- Prefix/wildcard evaluation SHALL run only if exact did not match.

Guardrails:
- Admin MUST show warning when active rule count exceeds configured threshold.
- Default warning threshold SHALL be `500` rules.
- Runtime MUST avoid full linear scan for exact rules.

Caching:
- An optional transient/object-cache snapshot MUST be used when available in the runtime environment.
- Cache invalidation MUST occur on any create/update/delete/enable/disable operation.

## 8. Storage Model
Redirect Engine v2 SHALL persist data in a versioned option schema.

Primary option key:
- `bw_redirects_v2`

Schema requirements per rule:
- `id` (stable unique rule ID)
- `source` (raw input)
- `source_normalized` (canonical key)
- `target` (sanitized destination)
- `status_code` (`301|302`)
- `match_type` (`exact|prefix|wildcard`)
- `enabled` (`0|1`)
- `created_at` (UTC timestamp)
- `updated_at` (UTC timestamp)

Top-level metadata:
- `schema_version`
- `rules`
- `updated_at`

Example:
```php
[
  'schema_version' => 2,
  'updated_at'     => '2026-02-27T12:00:00Z',
  'rules'          => [
    [
      'id'                => 'r_01JABCDEF123',
      'source'            => '/promo/black-friday',
      'source_normalized' => '/promo/black-friday',
      'target'            => '/collections/black-friday/',
      'status_code'       => 301,
      'match_type'        => 'exact',
      'enabled'           => 1,
      'created_at'        => '2026-02-27T11:45:00Z',
      'updated_at'        => '2026-02-27T11:45:00Z',
    ],
  ],
]
```

## 9. Admin UX Requirements
Validation and feedback:
- Admin MUST provide per-row validation errors on save.
- Duplicate normalized source keys MUST be rejected.
- Protected-route violations MUST show explicit blocking warning.
- Loop/cycle violations MUST show explicit blocking warning.

Ordering:
- If order affects only prefix/wildcard conflict resolution, ordering UI MUST be enabled only for those rule classes.
- Drag-and-drop ordering MUST NOT be exposed when runtime order is irrelevant.

External destination safety:
- Admin MUST require explicit confirmation for external-host target rules.
- External-host rules MUST be clearly labeled in UI and audit logs.

Usability constraints:
- Disabled rules MUST remain visible and editable.
- Rule ID MUST be visible in advanced details for traceability.

## 10. Security Model
Access control:
- Create/update/delete/enable/disable operations MUST require `manage_options` capability.
- All mutating requests MUST require valid nonce.

Input/output hardening:
- Source and target MUST be sanitized server-side.
- Output in admin UI MUST be escaped contextually.
- Protocol-relative targets (`//example.com`) MUST be rejected.
- Invalid schemes MUST be rejected.

Host policy:
- Internal redirects SHOULD be default.
- External host redirects SHALL require explicit allowlist validation policy.
- Runtime MUST use safe redirect primitives (`wp_safe_redirect` or equivalent host-validated mechanism).

Header safety:
- Redirect target values MUST be validated to prevent header injection vectors.

## 11. Observability
Runtime observability:
- Optional debug mode SHALL be available.
- When enabled, runtime SHOULD log matched rule ID, match type, source key, and status code.

Admin auditability:
- Rule create/update/delete/enable/disable operations MUST generate admin audit entries.
- Audit entry MUST include actor, timestamp, rule ID, and action type.

Operational counters:
- System SHOULD expose matched-rule counters for diagnosis.
- Counter resets and retention policy MUST be documented.

## 12. Regression Gates
The following tests are mandatory release gates for Redirect Engine v2:

1. Protected commerce routes:
- Verify Redirect Engine cannot override `/checkout`, `/cart`, `/my-account`.

2. Admin and platform routes:
- Verify Redirect Engine cannot override `/wp-admin`, `/wp-login.php`, REST base, and `/wc-api` surfaces.

3. Loop safety:
- Verify self-loop, direct loop, and multi-hop cycle are blocked at save-time.
- Verify runtime hop guard aborts after max 5 hops.

4. Deterministic matching:
- Verify exact match is deterministic with normalized query/trailing-slash policy.
- Verify prefix/wildcard deterministic behavior (if enabled).

5. No authority leakage:
- Verify redirects do not mutate payment/order/provisioning/consent authority surfaces.

6. Performance gate:
- Verify deterministic behavior and acceptable response profile with at least 500 active rules.

7. Precedence gate:
- Verify precedence order is preserved: canonical -> protected gate -> redirect engine -> Woo routing.

8. Security gate:
- Verify nonce/capability enforcement for admin mutations.
- Verify protocol-relative and invalid-host targets are rejected per policy.


---

## Source: `docs/30-features/search/search-module-spec.md`

# Search Module Specification (AS-IS)

## 1) Module Classification

- Domain: Search Domain
- Tier: Tier 1 (Runtime UX + query orchestration)
- Authority level: Presentation/read-only runtime layer
- Authority boundaries:
  - Search runtime MUST read product data from canonical WooCommerce/WordPress sources.
  - Search runtime MUST NOT define payment truth, order truth, entitlement truth, consent truth, or routing authority.
  - Search UI state CANNOT be treated as business authority.
- Couplings:
  - WooCommerce read-only coupling for product reads (`wc_get_product`, pricing HTML, image ID, permalink).
  - WordPress query coupling through `WP_Query` and taxonomy filters (`product_cat`, `product_type`).
  - Header runtime coupling for injection/enqueue lifecycle (`wp_body_open`, `wp_enqueue_scripts`).

## 2) File & Responsibility Map

### Admin settings surface

- `includes/modules/header/admin/settings-schema.php`
  - Defines option schema/defaults for `bw_header_settings`.
  - Owns merge + sanitize contract (`bw_header_default_settings`, `bw_header_get_settings`, `bw_header_sanitize_settings`).
  - Registers settings via `bw_header_register_settings`.

- `includes/modules/header/admin/header-admin.php`
  - Registers submenu `bw-header-settings` under `blackwork-site-settings`.
  - Renders Header admin UI; includes search-related fields (label, icon, mobile spacing).
  - Enqueues admin media script for icon upload.

- `includes/modules/header/admin/header-admin.js`
  - Handles media-picker upload/remove behavior and local admin tab switching.

### Frontend rendering + runtime

- `includes/modules/header/frontend/header-render.php`
  - Injects header via `wp_body_open`.
  - Builds search blocks via `bw_header_render_search_block()`.
  - Integrates `search-overlay.php` template in both desktop/mobile header surfaces.

- `includes/modules/header/templates/parts/search-overlay.php`
  - Search overlay DOM contract (form, input, results container, loading/message nodes).
  - Provides fallback form submit path (`method=get`, `action=home_url('/')`).

- `includes/modules/header/frontend/assets.php`
  - Enqueues search CSS/JS in frontend.
  - Localizes AJAX payload config (`bwSearchAjax.ajaxUrl`, `bwSearchAjax.nonce`).
  - Applies dynamic CSS tied to settings.

- `includes/modules/header/assets/js/bw-search.js`
  - Runtime controller (`BWSearchWidget`) for open/close, debounce, AJAX, result rendering.
  - Uses abort-on-new-request behavior and input-length gate (`>=2`).

- `includes/modules/header/assets/css/bw-search.css`
  - Search button, fullscreen overlay, loading/results grid, responsive layout styles.
  - Includes styles for category filter classes, even when filter controls are not rendered by current template.

- `includes/modules/header/assets/css/header-layout.css`
  - Header-level placement and responsive behavior affecting search block visibility/positioning.

### Backend endpoint

- `includes/modules/header/frontend/ajax-search.php`
  - Defines live search endpoint `bw_header_live_search_products()`.
  - Registers guest + authenticated AJAX hooks:
    - `wp_ajax_bw_live_search_products`
    - `wp_ajax_nopriv_bw_live_search_products`

### Related but non-primary search endpoint

- `metabox/digital-products-metabox.php`
  - Defines admin-only product search endpoint (`bw_search_products_ajax`) used by metabox Select2 flows.
  - This endpoint is NOT the header live-search runtime path.

## 3) Runtime Data Flow

### Entry points (UI)

1. User clicks `.bw-search-button` in desktop or mobile header block.
2. JS toggles overlay state (`.bw-search-overlay.is-active`).
3. User types in `.bw-search-overlay__input`.

### Request path

- Transport: WordPress AJAX (`admin-ajax.php`), not REST.
- Action: `bw_live_search_products`.
- Security token: nonce `bw_search_nonce`.
- JS source: `bw-search.js` sends:
  - `action`
  - `nonce`
  - `search_term`
  - `categories` (currently empty array in standard flow)

### Query build (server)

In `bw_header_live_search_products()`:
- Rejects short terms (`strlen(search_term) < 2`) with empty success payload.
- Builds `WP_Query`:
  - `post_type = product`
  - `post_status = publish`
  - `posts_per_page = 12`
  - `s = search_term`
- Optional tax filters if posted:
  - `product_cat` by slug from `categories[]`
  - `product_type` in allowed set (`simple`, `variable`, `grouped`, `external`)

### Response shape

`wp_send_json_success` payload:
- `products[]` entries include:
  - `id`
  - `title`
  - `price_html`
  - `image_url`
  - `permalink`
- `message` string for empty-state feedback.

### Render model

- Render surface: fullscreen search overlay in header module.
- Results UI: grid card rendering in `.bw-search-results__grid`.
- Not used in current flow:
  - dedicated search modal outside header
  - REST-driven search page
  - runtime initials/alphabet navigation bar

## 4) Filter Model (Current)

### Active filters in current runtime

- Search term filter:
  - Input source: overlay text input
  - Query mapping: `WP_Query['s'] = search_term`

### Supported by backend but not fully active in default UI flow

- Category filter (`categories[]`):
  - Server mapping: `tax_query` on `product_cat` by slug
  - Current frontend behavior: sends empty categories array by default

- Product type filter (`product_type`):
  - Server mapping: `tax_query` on `product_type`
  - Current frontend behavior: value not sent by default flow

### Current filter-to-query contract

- UI MUST be considered authoritative only for user input capture, not business truth.
- Query constraints MUST be derived from request payload processed in `bw_header_live_search_products()`.
- No price, stock, availability, custom meta, or attribute filters are applied in the primary live-search endpoint.

## 5) “Initial letter detection” Status (Current)

- Status: **Not implemented in current runtime path**.
- Verified as not present in:
  - `includes/modules/header/frontend/ajax-search.php`
  - `includes/modules/header/assets/js/bw-search.js`
  - `includes/modules/header/templates/parts/search-overlay.php`
  - Header search admin settings surface
- Consequence:
  - There is no runtime initials index, no alphabet grouping, and no initials-based query constraint in AS-IS behavior.

## 6) Performance & Caching (Current)

- Server-side caching:
  - No transient/object-cache layer specific to `bw_live_search_products` endpoint was found.
- Request-bound behavior:
  - Every debounced search request executes a fresh `WP_Query`.
- Client-side throttling:
  - Debounce: 300ms.
  - In-flight request abort on new input.
- Known hotspots:
  - Text search (`s`) on product catalog at high request frequency.
  - Repeated request bursts during active typing on large datasets.
- Response bound:
  - `posts_per_page = 12` limits payload size but does not eliminate query-frequency cost.

## 7) Security & Validation (Current)

### Admin settings

- Capability gate: `manage_options` for Header settings page.
- Settings API registration with sanitize callback (`bw_header_sanitize_settings`).

### AJAX endpoint

- Nonce check: `check_ajax_referer('bw_search_nonce', 'nonce')`.
- Input sanitization:
  - `search_term`: `sanitize_text_field`
  - `categories`: `array_map('sanitize_text_field', ...)`
  - `product_type`: `sanitize_text_field` + allowlist check
- Guest access:
  - `wp_ajax_nopriv_bw_live_search_products` is enabled by design.

### Output/data leakage constraints

- Endpoint returns product card-level public fields only (id/title/price_html/image/permalink).
- Search runtime MUST NOT expose session secrets, credentials, or non-public user metadata.

## 8) Failure Modes & Degrade-Safely

### Endpoint failure

- Behavior:
  - Client shows generic connection/search error message.
- Degrade-safe rule:
  - Overlay MUST remain closable and navigation MUST remain usable.

### JS failure

- Behavior:
  - Live AJAX search may not initialize.
- Degrade-safe rule:
  - Search form submit path (`GET ?s=` to home search route) MUST remain available.
  - Site navigation CANNOT be blocked by search JS failure.

### Empty results

- Behavior:
  - Endpoint returns empty product array + message.
  - UI shows empty-state message in results container.
- Degrade-safe rule:
  - No crash/no stuck loading state MUST occur for empty payloads.

### Missing/disabled search feature

- Behavior:
  - Header feature gating controls whether search block is rendered.
- Degrade-safe rule:
  - Missing search block MUST NOT break header/cart/account navigation surfaces.

## 9) Regression Checklist (Current)

1. Search overlay open/close (desktop + mobile) works via button, overlay click, and ESC.
2. Typing fewer than 2 characters does not trigger visible result rendering.
3. Typing 2+ characters triggers AJAX requests to `bw_live_search_products`.
4. Successful response renders product cards with title, image, price, and link.
5. Empty response renders empty-state message and no JS errors.
6. Network/error response renders error message and keeps UI interactive.
7. Form submit fallback (`Enter`) navigates to standard search route (`?s=`).
8. Guest user flow works (`wp_ajax_nopriv_bw_live_search_products`).
9. Nonce failure returns protected error path and does not expose sensitive data.
10. Header rendering in Elementor preview mode remains non-interfering (search injection respects existing header module guards).

## Cross-References

- Technical audit: `../../50-ops/audits/search-system-technical-audit.md`
- Runtime hooks contract: `../../50-ops/runtime-hook-map.md`


---

## Source: `docs/30-features/search/search-vnext-dataflow.md`

# Search vNext — Runtime Dataflow & Architecture

## 1. System Overview

Search vNext is a read-only discovery engine for product retrieval, filtering, and ranking.
It orchestrates query validation, cache/index usage, and response rendering support.

Search does NOT own commerce truth.
Search MUST consume canonical product/price/stock state and MUST NOT mutate it.

---

## 2. High-Level Dataflow Diagram

```text
[User Input]
      |
      v
[Search UI Layer]
      |
      | (debounced request)
      v
[API Endpoint]
      |
      v
[Validation Layer]
      |
      v
[Cache Lookup]
   /           \
  v             v
[HIT]         [MISS]
  |             |
  |             v
  |       [Index Layer]
  |             |
  |             v
  |       [Query Engine]
  |             |
  |             v
  |        [Result Set]
  |             |
  |             v
  |        [Cache Store]
  |             |
  \-------------/
        |
        v
[Response Envelope]
      |
      v
[UI Render]
```

Deterministic lifecycle rule:
- For the same normalized request and the same catalog snapshot, the runtime MUST produce an equivalent response envelope.

---

## 3. Authority Boundary Diagram

```text
+--------------------------------------------------------------+
| Tier 0 — Commerce Truth (Authoritative)                      |
|--------------------------------------------------------------|
| - Products (canonical entity state)                          |
| - Prices (canonical commerce value)                          |
| - Stock (canonical availability)                             |
+--------------------------------------------------------------+
                            ^
                            | read-only consumption
                            |
+--------------------------------------------------------------+
| Tier 1 — Search Runtime (Orchestration, Non-authoritative)   |
|--------------------------------------------------------------|
| - Query orchestration                                         |
| - Filter application                                          |
| - Initial-letter indexing                                     |
| - Cache lookup/store                                          |
+--------------------------------------------------------------+
                            ^
                            | presentation request/response
                            |
+--------------------------------------------------------------+
| Tier 2 — UI Layer (Presentation)                             |
|--------------------------------------------------------------|
| - Overlay / input                                             |
| - Filter controls                                             |
| - Alphabet navigation                                         |
+--------------------------------------------------------------+
```

Authority rule:
- Tier 1 and Tier 2 MUST NOT redefine Tier 0 truth.

---

## 4. Index Lifecycle Flow

```text
[Product Update Event]
          |
          v
[Index Invalidation]
          |
          v
[Rebuild Trigger (sync or async)]
          |
          v
[Index Rebuild Process]
          |
          v
[Index Convergence State]
```

Index convergence rule:
- Index state MUST converge deterministically after invalidation/rebuild.
- Repeated rebuild triggers for the same product snapshot MUST converge to the same index output.

---

## 5. Cache Lifecycle Flow

```text
[Normalized Query + Filters + Sort + Page]
                    |
                    v
            [Hash -> Cache Key]
                    |
                    v
        [TTL + Invalidation Rules Applied]
                    |
          +---------+---------+
          |                   |
          v                   v
     [Cache Hit]         [Cache Miss]
          |                   |
          |              [Compute + Store]
          |                   |
          +---------+---------+
                    |
                    v
              [Response Return]
```

Cache lifecycle constraints:
- Cache key MUST be derived from normalized request state.
- TTL and invalidation MUST bound staleness.
- Cache hit ratio MUST be observable as a runtime metric.

---

## 6. Failure & Degrade Paths

```text
[Incoming Request]
       |
       v
[Validation OK?] --no--> [Validation Error Envelope] --> [UI Error State]
       |                                                   |
      yes                                                  v
       |                                           [UI remains navigable]
       v
[Cache Layer Available?] --no--> [Bypass Cache] --> [Query Engine]
       |                                         
      yes
       |
       v
[Cache Hit?] --yes--> [Return Cached Envelope] --> [UI Render]
       |
      no
       |
       v
[Query Success?] --no--> [Query Error Envelope] --> [UI Error State]
       |                                                   |
      yes                                                  v
       |                                           [UI remains navigable]
       v
[Build Response Envelope]
       |
       v
[UI Render]
```

Degrade-safe rule:
- On validation/cache/query/endpoint failures, UI MUST remain navigable and MUST NOT block core site navigation.

---

## 7. Determinism Guarantees

- Same input + same catalog snapshot MUST produce same output (allowing telemetry variance such as `duration_ms`).
- Sorting MUST apply deterministic tie-break rules.
- Pagination MUST be stable for unchanged catalog snapshot.
- Initials grouping MUST be stable under the same normalized titles and index snapshot.
- Cache HIT and MISS paths MUST converge to equivalent business payload for identical request/snapshot.

---

## 8. Performance Guardrails Summary

- `per_page` MUST be bounded (max 24).
- Debounce window MUST be bounded (target 250–350ms; default 300ms).
- DB workload MUST be bounded per request by contract and filter constraints.
- Response payload MUST be bounded to presentation-safe fields only.



---

## Source: `docs/30-features/search/search-vnext-spec.md`

# Search vNext Specification

## 1) Goals / Non-goals

### Goals
- Define a deterministic, read-only Search runtime for product discovery.
- Add stronger filter coverage with explicit query-contract mapping.
- Introduce deterministic Initial Letter Indexing.
- Establish performance guardrails (debounce, cache, bounded payload).
- Preserve authority boundaries per ADR-002.

### Non-goals
- Search MUST NOT mutate payment, order, provisioning, consent, or auth truth.
- Search MUST NOT create/modify products, orders, users, or marketing state.
- Search vNext does not redefine checkout/payment/routing authority.
- Search vNext does not introduce write-side side effects.

## 2) Input Contract (Request Parameters)

Search vNext MUST accept only sanitized, bounded parameters.

### Core parameters
- `q` (string, required for active search)
  - Min length: 2
  - Max length: 120
- `page` (int, optional)
  - Min: 1
  - Default: 1
- `per_page` (int, optional)
  - Allowed: 1..24
  - Default: 12

### Taxonomy filters (optional)
- `category[]` (array of slugs)
- `tag[]` (array of slugs)
- `attributes` (object)
  - Example: `{ "pa_color": ["black","white"], "pa_size": ["m"] }`

### Commerce-facing read filters (optional)
- `in_stock` (boolean)
- `price_min` (decimal >= 0)
- `price_max` (decimal >= 0)
- `on_sale` (boolean)

### Initial-letter parameters (optional)
- `initial` (single normalized letter group token)
  - Allowed: `A..Z`, `0-9`, `#`
- `group_by_initial` (boolean)

### Sorting (optional)
- `sort` enum:
  - `relevance` (default when `q` present)
  - `title_asc`
  - `title_desc`
  - `price_asc`
  - `price_desc`
  - `newest`

### Contract rules
- Unknown params MUST be ignored.
- Invalid params MUST fail validation with deterministic error payload.
- Empty `q` MAY be allowed only for browse mode if at least one filter is present.

## 3) Output Contract (Response Schema)

Search vNext MUST return a stable JSON envelope.

```json
{
  "ok": true,
  "request_id": "string",
  "query": {
    "q": "string",
    "page": 1,
    "per_page": 12,
    "sort": "relevance",
    "filters": {}
  },
  "meta": {
    "total": 0,
    "total_pages": 0,
    "has_next": false,
    "duration_ms": 0,
    "cache": "hit|miss|bypass"
  },
  "products": [
    {
      "id": 0,
      "sku": "string",
      "title": "string",
      "slug": "string",
      "permalink": "string",
      "image_url": "string",
      "price_html": "string",
      "price_value": 0,
      "currency": "EUR",
      "in_stock": true,
      "on_sale": false,
      "initial": "A"
    }
  ],
  "initials": {
    "available": ["A", "B", "C", "0-9", "#"],
    "selected": "A"
  },
  "error": null
}
```

Error contract:
- `ok = false`
- deterministic `error.code` and `error.message`
- empty `products`

## 4) Filter Model vNext

Search vNext MUST map each filter to explicit query constraints.

### Tax filters
- `category[]` MUST map to `product_cat` taxonomy constraints.
- `tag[]` MUST map to product tags constraints.
- `attributes` MUST map to registered product attribute taxonomies (e.g. `pa_*`).
- Multiple taxonomy families MUST combine deterministically (`AND` between families; `OR` inside same family unless explicitly configured).

### Meta/read filters
- `in_stock=true` MUST constrain to purchasable stock states.
- `on_sale=true` MUST constrain to sale-eligible products.
- `price_min`/`price_max` MUST constrain product price range with numeric-safe comparison.

### Availability/price
- Price and availability filters MUST be read-only constraints.
- Conflicting bounds (`price_min > price_max`) MUST fail validation.

### Sorting/ranking
- `relevance` MUST be deterministic for same input + same catalog snapshot.
- Non-relevance sort MUST define deterministic tie-break:
  - primary sort key + secondary `title_asc` + tertiary `id_asc`.
- Sort mode MUST NOT alter authority surfaces; it only affects presentation order.

## 5) Initial Letter Indexing Model

### Indexed field
- Primary index source MUST be product display title.
- Fallback MAY use normalized post title when display title is unavailable.

### Normalization rules
Normalization MUST be deterministic and locale-safe:
- Trim whitespace.
- Unicode normalize to canonical form.
- Convert to uppercase.
- Strip leading punctuation and symbols for letter detection.
- Transliterate accented Latin letters to base ASCII for index grouping.
- If first alphanumeric is digit -> group `0-9`.
- If no valid alphanumeric -> group `#`.

### Storage strategy
- vNext MUST support precomputed index strategy.
- Runtime on-the-fly recompute MAY exist as fallback only.
- Precomputed index MUST be invalidated/rebuilt on product title changes and product publish status changes.
- Index state MUST converge deterministically after rebuild.

### UX interaction model
- UI MUST expose alphabet navigation using available initials from response.
- Selecting an initial MUST apply deterministic initial constraint to results.
- `group_by_initial=true` MUST return grouped presentation data or stable `initial` labels per item.

### Edge cases
- Numeric-leading titles -> `0-9`.
- Symbol-leading titles with first later letter -> resolved letter group.
- Non-Latin/unsupported transliteration -> `#` unless mapped deterministically.
- Empty title -> `#`.

## 6) Performance Model

### Caching
- Search vNext MUST support server-side cache for query responses.
- Cache key MUST include normalized query + filters + sort + page + per_page + locale/currency context.
- Cache MUST be bounded by TTL and invalidated on relevant product updates.

### Query optimization constraints
- Query paths MUST avoid unbounded scans when deterministic indexes are available.
- Expensive joins/meta filters MUST be bounded and measurable.
- Per-request DB workload MUST be bounded by `per_page` contract.

### Response bounds
- `per_page` hard max: 24.
- Payload MUST exclude non-essential heavy fields.

### Debounce rules
- Client requests MUST be debounced (target 250–350ms; default 300ms).
- In-flight request cancellation MUST be supported when a newer query supersedes the old one.
- Same-key duplicate requests SHOULD be coalesced.

## 7) Security Model

- Public search endpoint MAY remain guest-accessible.
- Nonce validation MUST be required for interactive AJAX calls.
- All input params MUST be sanitized and validated before query construction.
- Taxonomy and sort values MUST be allowlisted.
- Price bounds MUST be numeric-validated.
- Output MUST expose only presentation-safe product fields.
- Search runtime MUST NOT leak admin-only metadata, secrets, or user-private data.

## 8) Observability

Search vNext MUST provide structured observability.

### Minimum telemetry
- `request_id`
- normalized query fingerprint
- duration (`duration_ms`)
- result count
- cache state (`hit/miss/bypass`)
- validation errors count

### Failure observability
- Deterministic error codes for:
  - validation failure
  - endpoint failure
  - timeout/dependency error
- Logs MUST avoid PII overexposure and MUST NOT log secrets.

### Operational counters
- QPS / request volume
- cache hit ratio
- p95 latency
- error rate by error code

## 9) Regression Journeys (vNext)

1. Basic search
- Input: `q` only
- Verify deterministic result ordering and response schema.

2. Taxonomy filter search
- Input: `q + category[] + attributes`
- Verify exact filter application and stable totals.

3. Price/stock filter search
- Input: `q + in_stock + price_min/max`
- Verify numeric filter correctness and bounded payload.

4. Initial-letter navigation
- Input: `initial=A`, then `initial=0-9`, then `initial=#`
- Verify deterministic grouping and edge-case handling.

5. Pagination determinism
- Input: same query across `page=1..n`
- Verify no duplicates across pages and stable totals for unchanged catalog snapshot.

6. Cache behavior
- Repeat same query twice
- Verify second response is cache hit and schema identical (except telemetry fields).

7. Validation failures
- Invalid sort, invalid price bounds, invalid params
- Verify deterministic error payload and no partial data leakage.

8. Guest security path
- Guest request with/without valid nonce according to endpoint contract
- Verify accepted/denied behavior is consistent and auditable.

9. Degrade path
- Endpoint failure simulation
- Verify UI remains navigable and fallback submission path remains functional.

## Governance Alignment

- This specification is read-only by design and MUST comply with ADR-002 authority hierarchy.
- Search vNext CANNOT mutate commerce truth or cross-domain authority surfaces.
- Any future change that alters Search authority classification requires governance review and ADR escalation if authority behavior changes.


---

## Source: `docs/30-features/smart-header/README.md`

# Smart Header

## Files
- [smart-header-guide.md](smart-header-guide.md): main guide, including integrated automatic dark detection behavior.

## Archived / historical notes
- [smart-header-auto-dark-detection.md](../../99-archive/smart-header/smart-header-auto-dark-detection.md)
- [smart-header-dark-zones-guide.md](../../99-archive/smart-header/smart-header-dark-zones-guide.md)


---

## Source: `docs/30-features/smart-header/smart-header-guide.md`

# 🎯 Smart Header System - Guida all'uso

## ✅ Sistema Integrato e Funzionante

Il sistema Smart Header è ora **completamente integrato** nel plugin BW Elementor Widgets e carica automaticamente tutti i file necessari.

---

## 📋 Come Usare il Smart Header

### 1. Aggiungi la classe CSS in Elementor

Per attivare il sistema Smart Header sul tuo header, devi aggiungere la classe CSS `smart-header` al container principale dell'header:

**PASSAGGI IN ELEMENTOR:**

1. Apri il tuo **Header** in Elementor (Template → Theme Builder → Header)
2. Clicca sul **CONTAINER/SECTION principale** dell'header (quello più esterno che contiene tutto)
3. Nel pannello di sinistra, vai alla tab **"AVANZATE"** (icona ingranaggio ⚙️)
4. Trova la sezione **"CSS ID & Classi"**
5. Nel campo **"Classi CSS"** scrivi esattamente: `smart-header`
6. Clicca su **"Aggiorna"** per salvare

---

## 🎬 Comportamento del Smart Header

Una volta attivato, il sistema funziona automaticamente:

### ✅ Scroll DOWN (verso il basso)
- Quando scorri **giù oltre 100px**, l'header si **nasconde** scivolando verso l'alto
- Transizione smooth e fluida

### ✅ Scroll UP (verso l'alto)
- Appena scorri **su** (anche di poco), l'header **riappare** immediatamente
- Sempre visibile quando scorri verso l'alto

### ✅ Effetto Blur
- Dopo **50px di scroll**, l'header diventa **semi-trasparente** con effetto blur
- Background con backdrop-filter per un effetto moderno
- Box shadow leggera per dare profondità

### ✅ Posizione Fissa
- L'header rimane sempre **fisso in cima** alla pagina
- Il sistema calcola automaticamente l'altezza e aggiunge il padding necessario al body

---

## 🌗 Dark Zone Detection (Integrato)

Il sistema include anche il rilevamento automatico degli sfondi scuri:

- Scansiona sezioni Elementor/HTML e valuta il colore di background.
- Usa una soglia di luminosita (default `128`) per classificare una sezione come scura.
- Applica automaticamente lo stato dark agli elementi reattivi.

Formula di riferimento:

```javascript
Brightness = (R * 299 + G * 587 + B * 114) / 1000;
isDark = Brightness < 128;
```

### Modalita supportate

1. Automatica (consigliata): nessuna classe manuale richiesta.
2. Manuale (retrocompatibile): usa `.smart-header-dark-zone` per forzare una sezione come dark.

### Elementi reattivi

Per sincronizzare testi/logo/widget con il cambio colore:
- applica `.smart-header-reactive-text` agli elementi da rendere reattivi.

### Debug rapido

```javascript
window.bwSmartHeader.getState();
window.bwSmartHeader.getDarkZones();
```

---

## 🔧 File Integrati

Il sistema è composto da questi file:

```
/assets/css/bw-smart-header.css     ← Stili CSS
/assets/js/bw-smart-header.js       ← JavaScript per la logica
```

### Caricamento Automatico

I file vengono caricati automaticamente dal plugin **blackwork-core-plugin.php** tramite:
- `bw_enqueue_smart_header_assets()` - Funzione che registra e carica CSS e JS
- Hook `wp_enqueue_scripts` - Carica i file solo sul frontend (NON nell'editor Elementor)

---

## 🧪 Come Testare se Funziona

### Test 1: Verifica classe CSS
1. Apri il tuo sito WordPress (frontend, non editor)
2. Premi **F12** per aprire Developer Tools
3. Ispeziona l'header con il selettore elemento
4. Verifica che il container abbia la classe `smart-header`

### Test 2: Verifica caricamento file
1. Apri Developer Tools (F12)
2. Vai alla tab **"Network"**
3. Ricarica la pagina (Ctrl+R)
4. Cerca i file:
   - `bw-smart-header.css`
   - `bw-smart-header.js`
5. Devono essere entrambi caricati con status 200

### Test 3: Verifica funzionamento
1. Apri la **Console** in Developer Tools (F12 → Console)
2. Cerca il messaggio: `[Smart Header] ✅ Smart Header System inizializzato con successo`
3. Se vedi un warning `⚠️ Elemento non trovato`, verifica di aver aggiunto la classe

### Test 4: Test scroll
1. Scrolla la pagina **verso il basso** per almeno 100px
   - ✅ L'header deve scomparire
2. Scrolla **verso l'alto**
   - ✅ L'header deve riapparire immediatamente
3. Scrolla oltre 50px
   - ✅ Deve apparire l'effetto blur e la box shadow

---

## ⚙️ Personalizzazioni

### Modificare i parametri di scroll

Apri il file `/assets/js/bw-smart-header.js` e modifica la sezione `CONFIG`:

```javascript
const CONFIG = {
    scrollThreshold: 100,    // Pixel prima di nascondere l'header (aumenta per nascondere più tardi)
    scrollDelta: 5,          // Sensibilità scroll (diminuisci per reagire a scroll più piccoli)
    blurThreshold: 50,       // Quando attivare blur (diminuisci per blur immediato)
    hideDelay: 0,            // Delay prima di nascondere (in millisecondi)
    showDelay: 0,            // Delay prima di mostrare (in millisecondi)
    throttleDelay: 100,      // Performance throttling (aumenta se hai lag)
    debug: false             // Cambia a true per vedere log dettagliati in console
};
```

### Modificare colori e trasparenza

Apri il file `/assets/css/bw-smart-header.css` e modifica:

```css
/* Background normale */
.smart-header {
    background-color: rgba(255, 255, 255, 0.95) !important;
}

/* Background con scroll */
.smart-header.scrolled {
    background-color: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(12px);  /* Intensità blur */
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08) !important;
}
```

### Tema Scuro

Per usare un tema scuro, aggiungi **due classi** in Elementor:
- `smart-header` (obbligatoria)
- `dark-theme` (opzionale)

Il CSS include già gli stili per il tema scuro!

---

## 🔧 Risoluzione Problemi

### ❌ L'header non si nasconde/mostra

**SOLUZIONI:**
1. Verifica che la classe `smart-header` sia applicata correttamente
2. Apri la Console (F12) e cerca errori JavaScript
3. Verifica che i file CSS e JS siano caricati (tab Network)
4. Svuota la cache di WordPress
5. Controlla che non ci siano conflitti con altri plugin di sticky header

### ❌ Il blur non funziona

**SOLUZIONI:**
1. Il blur potrebbe non essere supportato dal browser (verifica con Chrome o Firefox aggiornati)
2. Il fallback automatico mostra comunque un background opaco
3. Alcuni browser vecchi non supportano `backdrop-filter`

### ❌ L'header copre il contenuto

**SOLUZIONI:**
1. Il padding viene calcolato automaticamente tramite CSS variable `--smart-header-height`
2. Se non funziona, puoi impostare manualmente nel CSS:
   ```css
   body:not(.elementor-editor-active) {
       padding-top: 120px; /* Sostituisci con l'altezza del tuo header */
   }
   ```

### ❌ Nell'editor Elementor l'header è fisso

**SOLUZIONI:**
1. Questo NON dovrebbe accadere, il JavaScript disabilita il sistema nell'editor
2. Verifica che il file JS sia caricato correttamente
3. Prova a ricaricare l'editor (svuota cache browser con Ctrl+Shift+R)

### ❌ Voglio vedere i log di debug

**SOLUZIONI:**
1. Apri `/assets/js/bw-smart-header.js`
2. Cambia `debug: false` in `debug: true`
3. Apri la Console (F12 → Console)
4. Ricarica la pagina e scorri
5. Vedrai log dettagliati di ogni azione

---

## 📱 Mobile e Responsive

Il sistema è completamente responsive:
- Transizioni leggermente più veloci su mobile
- Blur meno intenso su dispositivi mobile per migliori performance
- Compatibile con bounce scroll iOS

---

## ♿ Accessibilità

Il sistema rispetta le preferenze utente:
- `prefers-reduced-motion: reduce` → Disabilita le animazioni per utenti con disturbi vestibolari
- Cross-browser compatibility con fallback automatici

---

## 📊 Performance

Ottimizzazioni implementate:
- ✅ **requestAnimationFrame** per animazioni smooth sincronizzate
- ✅ **Throttling** degli eventi scroll per ridurre il carico
- ✅ **GPU acceleration** con transform e will-change
- ✅ **Passive event listeners** per migliori performance scroll
- ✅ **Calcolo dinamico** dell'altezza header con CSS variables

---

## 🚀 Checklist Finale

Prima di considerare il lavoro completato, verifica:

- [ ] Ho aggiunto la classe `smart-header` al container header in Elementor
- [ ] Ho salvato e pubblicato le modifiche in Elementor
- [ ] I file CSS e JS vengono caricati (verificato in Network tab)
- [ ] La Console mostra il messaggio di inizializzazione
- [ ] L'header si nasconde quando scrolo giù
- [ ] L'header riappare quando scrolo su
- [ ] L'effetto blur funziona dopo 50px di scroll
- [ ] Su mobile funziona correttamente
- [ ] Nell'editor Elementor l'header NON è fisso

---

## 📞 Supporto

Se hai ancora problemi:

1. Attiva `debug: true` nel JavaScript
2. Apri Console DevTools e copia tutti i messaggi
3. Verifica la tab Network per vedere se i file vengono caricati
4. Ispeziona l'elemento header e verifica quali classi CSS sono applicate

---

## 🎉 Fine!

Il tuo sistema Smart Header è ora completamente funzionante e integrato nel plugin BW Elementor Widgets!

**Versione:** 1.0.0
**Ultimo aggiornamento:** 2025


---

## Source: `docs/30-features/system-status/README.md`

# System Status (Admin Diagnostics)

## Purpose
Provide an admin-only, on-demand, read-only diagnostics dashboard under `Blackwork Site > Status` without adding load to storefront or normal admin navigation.

## Architecture Overview
- Module root: `includes/modules/system-status/`
- Bootstrap: `system-status-module.php`
- Admin rendering:
  - `admin/status-page.php`
  - `admin/assets/system-status-admin.js`
- Runtime execution:
  - `runtime/check-runner.php`
  - `runtime/checks/check-media.php`
  - `runtime/checks/check-database.php`
  - `runtime/checks/check-images.php`
  - `runtime/checks/check-wordpress.php`
  - `runtime/checks/check-server.php`

## UX Layout (Shopify-Style)
- Header: `Status` + short explanatory subtitle.
- Action Bar (below WP notices):
  - metadata: `Last check`, `Source (Live/Cached)`, `TTL`, `Exec`
  - actions: `Run full check`, `Force refresh`
- Overview strip: compact status cards for `PHP Limits`, `Database`, `Media Storage`, `WordPress`.
- Main cards:
  - `Images & Media Storage`
  - `Image Sizes`
  - `Database`
  - `WordPress Environment`
  - `PHP Limits`
- Details are collapsible; debug JSON is hidden by default behind `Show debug JSON`.

## Diagnostic Checks
- `media`
  - total files/bytes
  - by-type bytes + percentages (`jpeg`, `png`, `svg`, `video`, `webp`, `other`)
  - largest file
  - top 10 largest files with attachment edit links when available
- `images`
  - registered sizes list (`name`, `width`, `height`, `crop`)
  - duplicate size detection
  - optional on-demand generated file counts (`image_sizes_counts`)
- `database`
  - total DB size estimate
  - table count
  - largest table
  - top 10 largest tables
  - autoload size with warn threshold at `3MB`
- `wordpress`
  - WordPress/PHP/WooCommerce versions
  - `WP_DEBUG`
  - `DISALLOW_FILE_EDIT`
- `limits` (PHP limits)
  - `upload_max_filesize`
  - `post_max_size`
  - `memory_limit`
  - `max_execution_time`

## Snapshot and Caching Model
- Transient key: `bw_system_status_snapshot_v1`
- TTL: `600` seconds (10 minutes)
- Full payload includes:
  - `ok`
  - `generated_at`
  - `ttl_seconds`
  - `execution_time_ms`
  - `cached`
  - `checks`
- Check scopes:
  - `all`, `media`, `images`, `database`, `wordpress`, `limits`, `image_sizes_counts`
- Partial scope execution recomputes only selected checks and merges them into the cached snapshot.
- `all` does not run `image_sizes_counts` (heavy path stays explicit on-demand only).

## Security Model
- Endpoint: `wp_ajax_bw_system_status_run_check` (admin-only).
- Capability gate: `manage_options`.
- Nonce verification required for every request.
- Diagnostics remain strictly read-only.
- Partial failures are isolated: each check returns structured `status` + `summary` and never breaks global payload format.

## Performance Safeguards
- No heavy work on page load.
- Heavy checks run only via action buttons and cache reuse.
- Bounded scans:
  - media file scan is capped
  - generated image counts scan is capped to `3000` attachments
- Partial scans return `warn` and explicit partial metadata.
- DB size uses estimate strategy with fallback (`information_schema` preferred, `SHOW TABLE STATUS` fallback).

## UX Rationale
The dashboard intentionally favors owner/admin readability:
- metric-first cards over technical tables
- per-section actions to avoid unnecessary full reruns
- collapsible details for advanced inspection
- debug JSON available on demand only

This keeps day-to-day health checks fast and understandable while preserving full diagnostics depth when needed.


---

## Source: `docs/30-features/theme-builder-lite/theme-builder-lite-spec.md`

# Theme Builder Lite (Elementor Free) - Specification

## Status
- Phase 1: Implemented
- Phase 2 Step 1: Implemented (resolver skeleton only)
- Phase 2 Step 2: Implemented (conditions engine core, no UI yet)
- Phase 2 Step 3: Implemented (Display Rules metabox + deterministic persistence)
- Phase 2 Step 4: Implemented (archive non-Woo contexts + archive rules)
- Phase 2 Step 5: Implemented (Woo single product context + conditions)
- Phase 2 Step 6: Implemented (Woo product archive context + conditions)
- Single Product conditions UX stabilization: Implemented (settings-tab source of truth; Quick Edit conditions removed)
- Scope delivered in Phase 1:
  - Custom Fonts module
  - Footer Template module
- Scope delivered in Phase 2 Step 1:
  - `template_include` resolver skeleton with strict bypass guards
  - Deterministic winner selection (`priority DESC`, `template_id ASC`)
  - Runtime wrapper render path (Elementor builder first, `the_content` fallback)
  - New feature flag: `templates_enabled`
- Scope delivered in Phase 2 Step 2:
  - Display rules storage contract via `bw_tbl_display_rules_v1` post meta
  - Rules normalization pipeline (`include[]`, `exclude[]`) with deterministic invalid-rule stripping
  - Exclude-first evaluation + include evaluation contract integrated into resolver candidate filtering
- Scope delivered in Phase 2 Step 3:
  - WordPress-native `Display Rules` metabox on `bw_template`
  - Priority field persisted to `bw_template_priority` (`0..999`, default `10`)
  - Include/Exclude sections persisted to `bw_tbl_display_rules_v1` using normalized shape
- Scope delivered in Phase 2 Step 4:
  - Added `archive` template type (non-Woo contexts only)
  - Resolver context mapping for blog archive, category archive, tag archive, and post type archive
  - Conditions engine support for `archive_blog`, `archive_category`, `archive_tag`, `archive_post_type`
  - Archive-specific Include/Exclude controls in metabox with deterministic sanitize/save
- Scope delivered in Phase 2 Step 5:
  - Added `single_product` template type
  - Resolver context mapping for Woo single product requests (`is_product()`) with endpoint safety bypass unchanged
  - Conditions engine support for `product_category` (`product_cat` terms) and `product_id` (specific product IDs)
  - Single Product settings authority finalized in Theme Builder Lite tab with deterministic repeater v2 sanitize/save
- Scope delivered in Phase 2 Step 6:
  - Added `product_archive` template type
  - Resolver context mapping for Woo shop + product category/tag archives
  - Conditions engine support for `product_archive_shop`, `product_archive_category`, `product_archive_tag`
  - Product Archive settings authority finalized in Theme Builder Lite tab with deterministic repeater v2 sanitize/save
- Out of scope (not implemented):
  - Woo template stack takeover

## Task Start Template (Phase 1)

### 1) Task Classification
- Task ID: `TBL-PHASE1-FONTS-FOOTER-001`
- Task title: `Theme Builder Lite - Phase 1 Implementation (Custom Fonts + Footer Template)`
- Domain: `Theme Builder Lite / Global Layout / Elementor Integration`
- Tier classification: `1`
- Authority surface touched: `No`
- Data integrity risk: `Low-Medium`
- ADR required: `No`

### 2) Scope Declaration
- In scope:
  - Feature-flagged custom fonts storage + frontend `@font-face` output
  - `bw_template` CPT + footer template type
  - Active footer template selector in admin
  - Footer runtime rendering with fail-open fallback
- Out of scope:
  - Single product rendering changes
  - Woo hooks changes for product rendering
  - Header runtime changes

### 3) Determinism
- Fonts output is deterministic from `bw_custom_fonts_v1` option snapshot.
- Footer output is deterministic from one active template id (`bw_theme_builder_lite_footer_v1`).
- If active template is invalid/unpublished/missing -> runtime falls back to theme footer.

## Phase 1 Architecture (Implemented)

## A) Module Layout
- `includes/modules/theme-builder-lite/theme-builder-lite-module.php`
- `includes/modules/theme-builder-lite/config/feature-flags.php`
- `includes/modules/theme-builder-lite/cpt/template-cpt.php`
- `includes/modules/theme-builder-lite/cpt/template-meta.php`
- `includes/modules/theme-builder-lite/fonts/custom-fonts.php`
- `includes/modules/theme-builder-lite/runtime/footer-runtime.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.php`
- `includes/modules/theme-builder-lite/admin/theme-builder-lite-admin.js`

Bootstrap wiring:
- Loaded from `blackwork-core-plugin.php`.

## B) Feature Flags
Option key: `bw_theme_builder_lite_flags`
- `enabled` (master)
- `custom_fonts_enabled`
- `footer_override_enabled`
- `templates_enabled`

Behavior:
- If master flag is off, runtime output is disabled.
- Sub-flags gate fonts, footer, and Phase 2 template resolver independently.

## Single Product Conditions (Settings-Driven MVP)
- Quick Edit condition controls for `bw_template` are removed by design (stability hardening).
- Single Product conditions are configured in `Theme Builder Lite` admin tab: `Single Product`.
- Dedicated option key: `bw_theme_builder_lite_single_product_v1`
  - `enabled`
  - `active_single_product_template_id`
  - `include_product_cat` (parent `product_cat` IDs only)
  - `exclude_product_cat` (parent `product_cat` IDs only)
- Runtime precedence contract:
  - when this option is `enabled=1`, it is authoritative for `is_product()` resolution
  - `exclude` evaluates first
  - empty `include` = match-all
  - invalid/missing template or no-match => fail-open to theme template
- Backward compatibility:
  - legacy `bw_tbl_display_rules_v1` meta is retained in DB and can be migrated later
  - legacy data is not mutated by this settings-driven path

### 2026-03 Update - Elementor Preview Product Bridge Contract
- Elementor preview iframe does not reliably expose `elementor-preview` parameter inside widget render callbacks.
- Theme Builder Lite sets a preview bridge during preview context bootstrap:
  - global: `$GLOBALS['bw_tbl_preview_product_id']`
  - query var: `bw_tbl_preview_product_id`
- BW product-dependent widgets must resolve product context through shared resolver `bw_tbl_resolve_product_context_id()` (not by direct `$_GET` checks in render methods).
- Resolver order for preview fallback is deterministic:
  - bridge global -> bridge query var -> saved preview option.
- This avoids `WP_Query` spoofing and keeps frontend behavior unchanged.

### 2026-03 Operational Note
- `WP_Scripts::add ... slick-js not registered` notice is unrelated to Theme Builder Lite preview bridge/product-context resolution and should be handled in a separate asset-dependency task.

## C) Custom Fonts (Implemented)
Storage option: `bw_custom_fonts_v1`
- `version`
- `fonts[]` entries:
  - `font_family`
  - `sources.woff2`
  - `sources.woff`
  - `font_weight`
  - `font_style`

Security and validation:
- Upload mimes allow only `woff2`/`woff` for admins.
- Filetype/ext corrected through `wp_check_filetype_and_ext` filter.
- URLs sanitized and accepted only when they resolve to WP media attachments.
- MIME + extension validation enforced for each source.

Frontend behavior:
- `@font-face` CSS generated and enqueued only when:
  - master flag is enabled,
  - `custom_fonts_enabled` is enabled,
  - at least one valid font source exists.

### Custom Fonts - Elementor Typography Integration Contract
- Activation conditions:
  - `did_action('elementor/loaded')` is true
  - `bw_theme_builder_lite_flags[enabled] = 1`
  - `bw_theme_builder_lite_flags[custom_fonts_enabled] = 1`
- Data source:
  - Only `bw_custom_fonts_v1` snapshot
  - Only valid entries (attachment-backed source + mime/ext valid)
  - `font_family` values are deduplicated before injection
- UI mapping:
  - Injected via `elementor/fonts/additional_fonts` with priority `20`
  - Group key registered via `elementor/fonts/groups` as `Custom Fonts`
  - Dropdown label matches the exact stored `font_family` string
- Fail-open:
  - If Elementor is absent/not loaded, flags are off, or snapshot is invalid/empty, Elementor font list is returned unchanged.

### Custom Fonts - Editor Preview Enqueue Contract
- CSS builder is shared between frontend and editor contexts (single deterministic builder path).
- Editor hooks:
  - `elementor/editor/after_enqueue_styles`
  - `elementor/preview/enqueue_styles`
- Guards:
  - same feature flags as frontend
  - no enqueue when CSS output is empty
- Isolation:
  - no global wp-admin enqueue; styles are attached only through Elementor editor/preview hooks.

## D) Footer Template (Implemented)
CPT: `bw_template`
- Registered in admin under Blackwork Site menu.
- Elementor Free support added via `elementor/cpt_support` filter.
- Elementor support is also enforced via `elementor_cpt_support` option sync (`admin_init`) to avoid manual Elementor settings steps.
- Previewability for Elementor editor is enabled (`publicly_queryable=true`, rewrite slug `bw-template`, `show_in_rest=true`, `exclude_from_search=true`, `has_archive=false`).

Template type:
- Meta key `bw_template_type`
- Phase 1 allowed value: `footer`

Active footer selector:
- Option key: `bw_theme_builder_lite_footer_v1`
- Field: `active_footer_template_id` (single active footer)

Runtime behavior:
- If feature flag on and active template is valid (`publish`, type `footer`):
  - attempts to suppress known theme footer callback (Hello Elementor)
  - applies conservative fallback CSS for common theme footer selectors
  - renders Elementor content in `wp_footer`
- `bw_template` single requests use a dedicated preview template include path and are marked `noindex/nofollow/noarchive` via `wp_robots`.
- Fail-open:
  - invalid/missing template -> no override
  - render failure -> no override
  - theme footer remains the default fallback

### Footer Override - Final Rendering Contract
- Activation conditions:
  - Master flag `bw_theme_builder_lite_flags[enabled] = 1`
  - Footer flag `bw_theme_builder_lite_flags[footer_override_enabled] = 1`
  - Active footer template id resolves to a published `bw_template` with `bw_template_type=footer`
  - Request is frontend and not admin/ajax/feed/embed, not `is_singular('bw_template')`, and not Elementor editor/preview request.
- Rendering priority:
  - Runtime resolves a single deterministic active template id.
  - Elementor builder render (`get_builder_content_for_display`) is attempted first.
  - If Elementor render is empty/unavailable, classic `the_content` fallback is attempted.
- Preview safeguards:
  - Footer override is bypassed on Elementor editor/preview requests and on `bw_template` singular preview pages.
  - `bw_template` preview path returns normal frontend context (200 + WP head/footer hooks) through dedicated template include wiring.
- Fail-open invariant:
  - Any invalid template state, missing content, runtime exception, or guard mismatch returns control to theme footer with no hard failure.

## E) Explicit Non-Goals (Phase 1)
- No single product override logic.
- No `template_include` global interception.
- No WooCommerce template resolver changes.
- No header system modifications.

## F) Phase 2 Step 1 - Resolver Skeleton (Implemented)
Supported template contexts in this step:
- `single_post` (`is_singular('post')`)
- `single_page` (`is_page()`)
- `search` (`is_search()`)
- `error_404` (`is_404()`)

Resolver contract:
- Hook: `template_include` (priority `50`)
- Strict bypasses:
  - admin/ajax/feed/embed
  - `is_singular('bw_template')`
  - Elementor editor/preview requests
  - Woo safety list: `is_cart()`, `is_checkout()`, `is_account_page()`, `is_wc_endpoint_url()`
- Candidate selection:
  - `bw_template` + `publish` + matching `bw_template_type`
  - Step 2: candidates are filtered by conditions engine before winner selection
- Winner selection:
  - highest `bw_template_priority` first (default `10`)
  - tie-break: lowest template id
- Rendering:
  - Elementor builder output first
  - fallback to classic `the_content`
- Fail-open invariant:
  - if no winner / invalid winner / empty render / wrapper missing/error -> return original theme template unchanged.

### Phase 2 Step 2 - Conditions Engine Contract (Implemented)
- Storage meta key:
  - `bw_tbl_display_rules_v1` with normalized shape:
    - `include` => list of rules
    - `exclude` => list of rules
- Evaluation order:
  1) Exclude rules first: any match disqualifies template
  2) Include rules second:
     - if no applicable include rules => match-all in current template context
     - else at least one include rule must match
- Supported rule types in current implementation:
  - `single_post`:
    - `post_category` (term IDs)
    - `post_id` (post IDs)
  - `single_page`:
    - `page_id` (page IDs)
  - `search`, `error_404`:
    - no applicable include/exclude rule types (match-all by contract)
- Fail-open behavior:
  - missing/invalid/unparseable rules meta normalizes to empty include/exclude and does not hard-fail resolver flow.

### Phase 2 Step 3 - Display Rules Admin Contract (Implemented)
- Metabox scope:
  - `bw_template` edit screen only
- Fields:
  - `Priority` number input (`bw_template_priority`)
  - `Include Rules` table
  - `Exclude Rules` table
- Supported rule types in UI:
  - `post_category` (category term IDs)
  - `post_id` (post IDs)
  - `page_id` (page IDs)
- Persistence:
  - `bw_tbl_display_rules_v1` always saved with both keys:
    - `include` => array
    - `exclude` => array
  - Unknown rule types and invalid/non-positive IDs are dropped
  - IDs are deduplicated and sorted ascending before save
- Safety:
  - nonce + capability checks on save
  - empty/invalid input normalizes to empty arrays (fail-open with resolver contract).

### Admin UX - BW Templates List Table (Implemented)
- `bw_template` list table shows additional informative columns:
  - `Type` (label from `bw_template_type`)
  - `Priority` (`bw_template_priority`, default `10`)
  - `Applies To` (summary from `bw_tbl_display_rules_v1`, include/exclude with concise truncation)
- Optional type filter dropdown is available above the list table (`All Types` + specific template types).
- Quick Edit custom conditions are removed (core inline edit only).
- The `bw_template` post edit screen remains guidance-only for conditions.
- Single Product conditions are configured from Theme Builder Lite settings tab `Single Product`.
- Legacy storage schema `bw_tbl_display_rules_v1` remains unchanged in DB for backward compatibility.
- UI Contract (2026-03):
  - `Blackwork Site > All Templates` keeps WordPress native list table mechanics (`WP_List_Table`) intact.
  - UI layer is wrapper/skin only: `.bw-admin-root` shell, action bar, responsive table wrapper, and pill styling.
  - No changes to filters, bulk actions, search, sorting, pagination, row actions, URLs, nonce, or query behavior.

### 2026-03 Update - Single Product Settings Authority (v2 Repeater)
- Authoritative single-product conditions surface is the Theme Builder Lite settings tab (`Single Product`); Quick Edit is not an authority surface.
- New option snapshot: `bw_theme_builder_lite_single_product_rules_v2`
  - `enabled` (bool)
  - `rules[]`:
    - `template_id` (published `bw_template`, type `single_product`)
    - `include_product_cat[]` (parent `product_cat` term IDs only)
    - `exclude_product_cat[]` (parent `product_cat` term IDs only)
- Backward compatibility:
  - `bw_theme_builder_lite_single_product_v1` is preserved as legacy compatibility source.
  - If v2 is absent, resolver derives effective v2 payload from v1 without destructive migration.
- Deterministic evaluation contract:
  - Rules evaluate top-to-bottom.
  - Exclude-first, include-second.
  - First matching valid rule wins.
  - Include empty means match-all.
- Parent-only UI + ancestor runtime match:
  - UI shows only parent product categories (`parent=0`), flat checklist.
  - Runtime matches product terms including ancestors so parent rules match child-assigned products.
- Status summary behavior:
  - Single Product tab shows enabled state, rules count, and active template count.
  - Warning shown when enabled but no valid linked template exists.
- List UX behavior:
  - Badges: `Applies to: Footer`, `Applies to: Single Product`, `Applies to: Product Archive`, `Not linked`.
  - Inline Type dropdown on list table autosaves with nonce/capability checks.
  - Linked templates require confirmation before type mutation.
  - Invalid/unauthorized type mutations are rejected; linkage invalidation surfaces safely as `Not linked`.

### 2026-03 Update - Product Archive Settings Authority (v2 Repeater)
- Authoritative product-archive conditions surface is Theme Builder Lite settings tab `Product Archive`; Quick Edit is not an authority surface.
- New option snapshot: `bw_theme_builder_lite_product_archive_rules_v2`
  - `enabled` (bool)
  - `rules[]`:
    - `template_id` (published `bw_template`, type `product_archive`)
    - `include_product_cat[]` (parent `product_cat` IDs only)
    - `exclude_product_cat[]` (parent `product_cat` IDs only)
- UI contract:
  - Status summary (enabled/disabled, rule count, active template count, warning when enabled with no valid linked templates)
  - Repeater with explicit include mode (`all` vs `selected`)
  - Optional exclude toggle (`Enable exclusions`)
  - Parent-only flat product category checklist (no subcategories rendered)
- Runtime contract for product category archives:
  - Settings branch runs only for `product_cat` archive context.
  - Rules evaluate top-to-bottom, exclude-first.
  - Include empty means match-all.
  - First matching valid rule wins.
  - Archive term matching uses current term + ancestors so parent rules match child category archives.
- Fail-open:
  - Disabled/no match/invalid template => resolver returns original theme/Woo template path unchanged.
- Admin list UX:
  - Template row shows `Applies to: Product Archive` when linked by any enabled Product Archive rule.
  - `Not linked` remains true only when template is not referenced by active Footer/Single Product/Product Archive settings surfaces.

### 2026-03 Update - Import Template Tab (Elementor JSON -> bw_template)
- New admin tab: `Import Template` inside Theme Builder Lite settings.
- Upload contract:
  - accepts `.json` only
  - nonce-protected submission
  - capability gate: `manage_options`
  - size limited by filterable cap (default 8 MB)
- Import validation contract:
  - payload must be valid JSON object
  - must include Elementor content structure (`content[]` or `elements[]`)
  - template type is auto-detected from JSON payload (`type`/`template_type`/`doc_type`) with deterministic widget-heuristic fallback
  - no manual type override field is exposed in UI
- Type mapping contract:
  - `product-archive` -> `product_archive`
  - `single-product` -> `single_product`
  - `product` -> `single_product`
  - additional allowed BW types supported through same map/enum validation
  - if still unmappable, importer applies safe fallback type `single_page` and keeps post as `draft` with explicit admin notice
- Persistence contract:
  - creates `bw_template` post as `draft`
  - prefixes title as `Imported — {Original Title}`
  - sets `bw_template_type` to validated mapped type
  - marks imported record with `bw_tbl_imported=1`
  - writes required Elementor meta:
    - `_elementor_data`
    - `_elementor_edit_mode` (`builder`)
    - `_elementor_version`
    - `_elementor_page_settings`
    - `_elementor_template_type`
  - creates mirrored `elementor_library` post to ensure imported templates are discoverable in Elementor library popup
  - stores linkage between imported `bw_template` and mirrored library template via meta
- Fail-open + integrity:
  - on any validation/import error: no partial template kept
  - on metadata or library-mirror failure: created draft is deleted
  - importer reports explicit admin error notice and exits safely
- History:
  - tab renders deterministic `Recent Imports` list (last 10 by `bw_tbl_imported=1`) with title, detected type, date/time, and edit link
- Limitations:
  - no conversion of Elementor Pro widgets to free equivalents
  - no batch/zip import
  - media asset remapping is out of scope

## Extending Theme Builder Lite to New Contexts
Reusable extension pattern (2026-03):

1. Storage schema
- Create a versioned option snapshot per context (`*_vN`).
- Keep shape deterministic with explicit booleans and normalized arrays.
- Preserve previous version for compatibility fallback; avoid destructive migration in runtime path.

2. Admin UI pattern
- Use settings-tab authority for stable persistence.
- Repeater rows for multi-rule contexts.
- Explicit toggles for optional behavior (e.g., include mode, exclude enable).
- Keep UI fail-open: if JS fails, fields remain editable.

### Settings Page UI Contract (2026-03)
- `Blackwork Site > Theme Builder Lite` adopts the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`).
- Pattern: `.bw-admin-root` shell, page header + subtitle, top action bar with primary save CTA, `Sections` card with pill tabs, and card-grouped tab panels.
- Constraints:
  - UI-only styling alignment; no changes to option keys/defaults/sanitizers/save handlers.
  - Styling scope remains under `.bw-admin-root`; Theme Builder Lite module CSS selectors are additionally scoped to `.bw-admin-root.bw-tbl-admin-wrap`.

3. Runtime branch pattern
- Gate by master + feature flags before resolver branch.
- Apply global + Woo safety bypass first.
- Build normalized context payload once.
- Evaluate rules deterministically (document order + explicit precedence).
- On mismatch/error/invalid render, fail-open to theme template.

4. Validation rules
- Validate IDs/capabilities/types at sanitize time.
- Restrict taxonomy selectors to intended subset (e.g., parent-only categories).
- Reject invalid AJAX payloads with nonce + capability + enum validation.

5. List UX expectations
- Reflect active linkage with explicit badges.
- Surface unlinked state clearly (`Not linked`).
- Any inline mutation that can break linkage must warn and remain safe on failure.

6. Minimal test checklist for each new context
- Save/reload persistence.
- Deterministic winner behavior.
- Include/exclude precedence.
- Guard/bypass confirmation.
- Badge truth reflection.
- Fail-open fallback verification.

Implemented example:
- `Product Archive` follows this exact pattern with option `bw_theme_builder_lite_product_archive_rules_v2`, parent-only UI, ancestor-aware runtime matching, and linkage badges in Templates list UX.
- `Import Template` follows the same governance pattern as a settings-driven authority surface: explicit validation, deterministic type mapping, strict capability/nonce gates, and fail-open rollback on import errors.

### Phase 2 Step 4 - Archive Contexts (Non-Woo) (Implemented)
- Resolver type mapping:
  - `archive` when request is `is_home()` or `is_archive()`, excluding Woo archive surfaces (`is_shop`, `is_product_taxonomy`, `is_post_type_archive('product')`)
- Archive sub-context payload passed to conditions engine:
  - `archive_kind` in `{blog, category, tag, post_type, generic}`
  - `archive_term_id` for category/tag
  - `archive_post_types[]` for post type archives (excluding `product` and `bw_template`)
- Supported archive rule types:
  - `archive_blog`
  - `archive_category` (category term IDs)
  - `archive_tag` (tag term IDs)
  - `archive_post_type` (post type names)
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within archive context
  - Winner: highest priority, tie-break by lowest template ID

### Phase 2 Step 5 - Woo Single Product (Implemented)
- Resolver type mapping:
  - `single_product` when request is Woo product singular (`is_product()`)
  - Woo endpoint safety bypass remains strict: `is_cart()`, `is_checkout()`, `is_account_page()`, `is_wc_endpoint_url()`
- Single product context payload passed to conditions engine:
  - `product_id`
  - `product_category_term_ids` (`product_cat`)
- Supported single product rule types:
  - `product_category` (product category term IDs)
  - `product_id` (specific product IDs)
- Product category contract (parent-only):
  - `product_category` conditions accept only parent `product_cat` terms (`parent=0`).
  - Subcategories are excluded from admin condition selectors.
  - Save normalization drops non-parent IDs.
  - Matching is direct term ID comparison on assigned product terms (no parent-chain traversal).
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within `single_product` context (Elementor-like “All Products” behavior)
  - Winner: highest priority, tie-break by lowest template ID
- Settings-surface precedence update:
  - When `bw_theme_builder_lite_single_product_v1[enabled]=1`, settings tab conditions are authoritative for `single_product`.
  - If settings do not match or configured template is invalid, resolver fails open to theme template.

### Phase 2 Step 6 - Woo Product Archive (Implemented)
- Resolver type mapping:
  - `product_archive` when request is Woo shop archive (`is_shop()` or `is_post_type_archive('product')`)
  - `product_archive` when request is product category/tag archive (`is_product_category()`, `is_product_tag()`)
  - Unknown Woo product taxonomies remain bypassed (`is_product_taxonomy()` fallback branch)
- Product archive context payload passed to conditions engine:
  - `product_archive_kind` in `{shop, product_cat, product_tag, generic}`
  - `product_archive_term_id` for taxonomy archives
- Supported product archive rule types:
  - `product_archive_shop`
  - `product_archive_category` (`product_cat` term IDs)
  - `product_archive_tag` (`product_tag` term IDs)
- Product category contract (parent-only):
  - `product_archive_category` accepts only parent `product_cat` terms (`parent=0`).
  - Subcategories are intentionally excluded from UI and matching.
- Evaluation and precedence remain unchanged:
  - Exclude-first
  - Include empty => match-all within `product_archive` context (Elementor-like “All Product Archives” behavior)
  - Winner: highest priority, tie-break by lowest template ID

## Rollback
1. Disable master flag `bw_theme_builder_lite_flags[enabled]`.
2. Optionally disable only one sub-feature:
   - `custom_fonts_enabled=0`
   - `footer_override_enabled=0`
3. Keep stored data for safe re-enable.

When disabled:
- Theme footer renders normally.
- No Theme Builder Lite fonts CSS is output.


---

## Source: `docs/40-integrations/README.md`

# 40 Integrations

External service integrations and related operational references.

## Subfolders
- [payments](payments/README.md)
- [brevo](brevo/README.md)
- [auth](auth/README.md)
- [supabase](supabase/README.md)


---

## Source: `docs/40-integrations/auth/README.md`

# Auth

Provider-agnostic authentication integration area for account login capabilities.

## Current provider scope
- Supabase (implemented)

## Planned provider scope
- WordPress provider support

## Files
- [auth-architecture-map.md](auth-architecture-map.md): official provider-agnostic auth architecture reference.
- [social-login-setup-guide.md](social-login-setup-guide.md): Facebook and Google setup flow.


---

## Source: `docs/40-integrations/auth/auth-architecture-map.md`

# Auth Architecture Map

## 1) Purpose + Scope
This document is the official architecture reference for Blackwork authentication integration.
It describes current runtime behavior for provider selection (`WordPress` vs `Supabase`) and the OIDC broker dependency used for Supabase redirect login.

Scope boundaries:
- Documentation-only architecture model.
- Based on current plugin implementation reality.
- No code refactor guidance.

## 2) Admin Provider Switch Model
### Login Provider toggle (WordPress | Supabase)
- Option key: `bw_account_login_provider`.
- Values: `wordpress`, `supabase`.
- Saved from admin settings in `admin/class-blackwork-site-settings.php`.

### Runtime selection behavior
- My Account and login gates read `bw_account_login_provider` at runtime.
- Main consumers:
  - `woocommerce/templates/myaccount/form-login.php`
  - `woocommerce/templates/global/form-login.php`
  - `woocommerce/templates/checkout/order-received.php`
  - `includes/woocommerce-overrides/class-bw-my-account.php`
  - `includes/woocommerce-overrides/class-bw-supabase-auth.php` (provider-guarded handlers)

### Field rendering dependency
- Admin UI uses provider-scoped sections (`.bw-login-provider-section`) shown/hidden with JS in account settings screen.
- Supabase-only fields (project URL/keys, OIDC mode, session storage, onboarding controls) render under provider `supabase`.
- WordPress-only social settings render under provider `wordpress`.

### Config readiness gates (Supabase + OIDC)
- Core Supabase runtime config: `bw_supabase_project_url`, `bw_supabase_anon_key`.
- Mode keys: `bw_supabase_login_mode` (`native` | `oidc`), `bw_supabase_with_plugins`.
- OIDC broker config source: `openid_connect_generic_settings` (owned by OpenID Connect Generic Client plugin).
- OIDC helper access:
  - `bw_oidc_is_active()`
  - `bw_oidc_get_auth_url()`
  - `bw_oidc_get_redirect_uri()`

### Fallback behavior if Supabase/OIDC is misconfigured
- If provider is `supabase` but required config is missing, runtime returns auth errors and/or does not expose successful login path.
- If OIDC plugin is not active/configured, helper-derived OIDC URLs resolve empty and admin shows warning.
- Login mode normalization falls back to `native` when mode is invalid.

## 3) Provider Model
### WordPress native auth (primary local)
- Uses WooCommerce/WordPress account flows and WP auth/session.
- Includes optional WP-side social login controls from account settings.

### Supabase via OIDC broker (OpenID Connect Generic Client)
- Supabase provider can run in:
  - `native`: plugin-managed Supabase auth API + bridge flows.
  - `oidc`: redirect-based auth via OpenID Connect Generic Client broker.
- Broker handles authorization endpoint/callback exchange; Blackwork consumes broker URL/state indirectly.

### Social providers as sub-providers
- In Supabase context, Google/Facebook/Apple toggles are treated as Supabase/OIDC-related identity paths.
- In WordPress context, social provider controls are local provider settings.

### Provider precedence rules
1. `bw_account_login_provider` decides active provider family.
2. If provider is `supabase`, `bw_supabase_login_mode` decides native vs OIDC flow shape.
3. If `oidc` mode is selected, broker availability/config is a hard dependency for redirect auth.

## 4) State Hierarchy
Authentication state model used by templates, AJAX handlers, and gating logic:

1. `Anonymous`
- User not authenticated in WP context.
- Login/register UI shown according to provider.

2. `Pending verification / onboarding` (when applicable)
- Typical Supabase invite/password setup state.
- User may have account linkage metadata but not fully onboarded (`bw_supabase_onboarded` not set to 1).

3. `Authenticated`
- Active WP session established.
- For Supabase modes, session token context is also stored (cookie/usermeta according to configuration).

4. `Session expired / missing token`
- WP or Supabase token context is missing/expired.
- Runtime handlers request re-authentication or show recovery path.

5. `Logout`
- WP logout state reached; Supabase-linked session artifacts may also be cleared by auth flows/handlers.

## 5) Runtime Flow Model
### A) WordPress login flow
1. Provider resolves as `wordpress`.
2. WooCommerce login form submits with WP nonce (`woocommerce-login-nonce`).
3. WordPress authentication establishes WP session cookies.
4. User continues to account/checkout target.

### B) Supabase/OIDC redirect flow
1. Provider resolves as `supabase`.
2. Login mode resolves as `oidc`.
3. Client receives broker auth URL via helper/localized data (`bw_oidc_get_auth_url()`).
4. Browser redirects to provider authorize flow through OIDC broker.
5. Callback is completed by OIDC plugin endpoint.
6. WordPress session returns to account target/callback route.

### C) Session establishment + redirect back to target
- Native Supabase flows localize auth parameters via `bwAccountAuth` / `bwSupabaseBridge`.
- Callback and redirect URLs include account-oriented targets (`/my-account/`, `bw_auth_callback` patterns).

### D) Checkout gating (applicable)
- Checkout/order-received templates check provider + provisioning keys.
- Supabase provider and/or provisioning can alter post-order CTA and account activation path.

### E) Failure-safe degrade path
- If Supabase/OIDC readiness fails, user-facing flow degrades to retry/error paths instead of silent success.
- Provider-guarded handlers avoid executing Supabase-only logic when provider is `wordpress`.

## 6) Redirect/Callback Contract
### Callback endpoint ownership
- OIDC callback endpoint ownership is broker-side (OpenID Connect Generic Client plugin).
- Blackwork does not implement the OIDC token-exchange endpoint itself.

### Required params
- Standard OIDC callback expectations include `state` and `code`.
- Validation semantics are expected to be enforced by the broker plugin.

### Nonce/state validation expectations
- Blackwork AJAX auth handlers use `check_ajax_referer( 'bw-supabase-login', 'nonce' )`.
- OIDC redirect `state` validation is broker responsibility.

### Redirect target discipline
- Blackwork computes safe account/callback destinations (`my-account`, controlled query args).
- Redirect URL sanitization helper exists for Supabase invite/reset redirect paths (`bw_mew_supabase_sanitize_redirect_url`).
- Open redirect risk is mitigated by restricting/sanitizing redirect targets.

## 7) Storage + Session Model
### WP session assumptions
- WordPress auth remains session authority for site access.
- WooCommerce/My Account/Checkout gating relies on WP user/session state.

### Supabase/OIDC token storage
- Native Supabase token storage strategy key: `bw_supabase_session_storage` (`cookie` | `usermeta`).
- Cookie name base: `bw_supabase_jwt_cookie_name`.
- Token persistence helpers live in `includes/woocommerce-overrides/class-bw-supabase-auth.php`.
- OIDC broker-specific token internals are plugin-owned.

### OIDC identity to WP user mapping
- Mapping/linking controls exist through:
  - `bw_supabase_enable_wp_user_linking`
  - `bw_supabase_create_wp_users`
- Supabase onboarding/user link metadata is stored on WP users (`user_meta`).

### Logout invalidation semantics
- WP logout invalidates WordPress session.
- Supabase/OIDC linked session artifacts are handled by their respective auth flows/storage handlers.

## 8) Security Model
- CSRF protection:
  - WP login form nonce (`woocommerce-login-nonce`).
  - Supabase AJAX nonce (`bw-supabase-login`).
- Token leakage prevention:
  - Service role key intended server-side only.
  - Browser-localized config uses anon/public keys.
- Secrets isolation:
  - `bw_supabase_service_role_key` is configured for backend calls and must never be exposed client-side.
- Rate limiting expectations:
  - Supabase endpoints and resend flows contain anti-abuse/rate-limit behavior patterns.
- Audit/logging touchpoints:
  - Optional debug logging (`bw_supabase_debug_log`) with operational traces (without credential dumps by design intent).

## 9) High-Risk Zones
1. Provider switch logic (`bw_account_login_provider`)
- Misalignment can break all login-entry surfaces simultaneously (account, checkout, post-order gates).

2. Login templates and account scripts
- `woocommerce/templates/myaccount/form-login.php`
- `assets/js/bw-account-page.js`
- `assets/js/bw-supabase-bridge.js`
- High coupling between UI state, provider mode, and callback/query behavior.

3. OIDC plugin settings + redirect URIs
- External dependency (OpenID Connect Generic Client).
- Incorrect redirect URI/endpoint config breaks Supabase OIDC login despite valid Blackwork settings.

4. User mapping/creation paths
- WP user linkage and onboarding metadata can impact account access continuity and order ownership flows.

5. Checkout gate coupling
- Provider/provision flags influence order-received/login-gate behaviors and post-checkout account activation CTA.

## 10) Maintenance & Regression References
- [Regression Protocol](../../50-ops/regression-protocol.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- [Auth Setup Guide (current setup)](./social-login-setup-guide.md)


---

## Source: `docs/40-integrations/auth/social-login-setup-guide.md`

# Guida Configurazione Social Login - BlackWork

> Nota implementativa:
> Current implementation: Supabase-only.
> Planned: WordPress provider support.

## 🔵 Facebook Login Setup

### Step 1: Creare un'App Facebook

1. Vai su **https://developers.facebook.com/apps/**
2. Clicca su **"Crea un'app"** (Create App)
3. Seleziona il tipo: **"Consumatore"** (Consumer)
4. Compila i campi:
   - **Nome app**: Nome del tuo sito (es. "BlackWork Store")
   - **Email di contatto**: La tua email
   - **Scopo**: Seleziona "Altro"
5. Clicca **"Crea app"**

### Step 2: Configurare Facebook Login

1. Nella dashboard dell'app, cerca **"Facebook Login"** nei prodotti
2. Clicca su **"Configura"** (Set Up)
3. Seleziona **"Web"**
4. Inserisci l'URL del tuo sito (es. `https://tuosito.com`)

### Step 3: Configurare Valid OAuth Redirect URIs

1. Nel menu laterale: **Facebook Login > Impostazioni** (Settings)
2. Trova il campo **"Valid OAuth Redirect URIs"**
3. **IMPORTANTE**: Copia l'URL esatto dal pannello WordPress:
   - Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
   - Sotto "Facebook Redirect URI" c'è un campo read-only con l'URL
   - Copia quell'URL (es. `https://tuosito.com/my-account/?bw_social_login_callback=facebook`)
4. Incolla l'URL nel campo "Valid OAuth Redirect URIs" di Facebook
5. Clicca **"Salva modifiche"**

### Step 4: Ottenere App ID e App Secret

1. Nel menu laterale: **Impostazioni > Di base** (Settings > Basic)
2. Troverai:
   - **ID app** (App ID): es. `1234567890123456`
   - **Chiave segreta dell'app** (App Secret): clicca su "Mostra" per visualizzarla
3. **Copia questi valori**

### Step 5: Inserire in WordPress

1. Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
2. Spunta la checkbox **"Enable Facebook Login"**
3. Incolla:
   - **Facebook App ID**: il valore dell'ID app
   - **Facebook App Secret**: il valore della chiave segreta
4. Clicca **"Salva impostazioni"**

### Step 6: Pubblicare l'App (IMPORTANTE!)

**⚠️ L'app è in modalità sviluppo per default!**

Per rendere il login pubblico:
1. Nel menu laterale: **Impostazioni > Di base** (Settings > Basic)
2. Scorri in basso fino a **"Modalità app"** (App Mode)
3. Attiva l'interruttore per passare da **"Development"** a **"Live"**
4. Facebook potrebbe richiedere:
   - URL Privacy Policy
   - URL Terms of Service
   - Icona dell'app
5. Compila i campi richiesti e conferma

### Permessi Richiesti

L'app richiede solo:
- ✅ **email** (permesso base, sempre disponibile)
- ✅ **public_profile** (permesso base, sempre disponibile)

Non serve richiedere permessi aggiuntivi a Facebook.

---

## 🔴 Google Login Setup

### Step 1: Creare un Progetto Google Cloud

1. Vai su **https://console.cloud.google.com/**
2. In alto a sinistra, clicca sul menu a tendina dei progetti
3. Clicca **"Nuovo progetto"** (New Project)
4. Compila:
   - **Nome progetto**: Nome del tuo sito (es. "BlackWork Store")
   - **Organizzazione**: lascia vuoto se non hai un'organizzazione
5. Clicca **"Crea"**

### Step 2: Abilitare Google+ API

1. Nel menu laterale: **API e servizi > Libreria** (APIs & Services > Library)
2. Cerca **"Google+ API"**
3. Clicca su **"Google+ API"**
4. Clicca **"Abilita"** (Enable)

### Step 3: Configurare OAuth Consent Screen

1. Nel menu laterale: **API e servizi > Schermata consenso OAuth** (OAuth consent screen)
2. Seleziona:
   - **Tipo utente**: **"Esterno"** (External)
3. Clicca **"Crea"**
4. Compila la schermata di consenso:
   - **Nome dell'app**: Nome del tuo sito
   - **Email di supporto utenti**: La tua email
   - **Logo dell'app**: (facoltativo)
   - **Dominio app**: `tuosito.com`
   - **Email dello sviluppatore**: La tua email
5. Clicca **"Salva e continua"**
6. **Ambiti** (Scopes): clicca "Salva e continua" (gli ambiti base sono sufficienti)
7. **Utenti test**: se l'app è in "Testing", aggiungi le email di test
8. Clicca **"Salva e continua"**

### Step 4: Creare Credenziali OAuth 2.0

1. Nel menu laterale: **API e servizi > Credenziali** (Credentials)
2. Clicca su **"+ Crea credenziali"** (Create Credentials)
3. Seleziona **"ID client OAuth"** (OAuth client ID)
4. Scegli tipo: **"Applicazione web"** (Web application)
5. Compila:
   - **Nome**: "BlackWork Login" (o un nome a tua scelta)
   - **URI di reindirizzamento autorizzati** (Authorized redirect URIs):
     - Clicca **"+ Aggiungi URI"**
     - **IMPORTANTE**: Copia l'URL esatto dal pannello WordPress:
       - Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
       - Sotto "Google Redirect URI" c'è un campo read-only con l'URL
       - Copia quell'URL (es. `https://tuosito.com/my-account/?bw_social_login_callback=google`)
     - Incolla l'URL
6. Clicca **"Crea"**

### Step 5: Ottenere Client ID e Client Secret

1. Dopo aver creato le credenziali, apparirà un popup con:
   - **ID client**: es. `123456789-abc123xyz.apps.googleusercontent.com`
   - **Segreto client**: es. `GOCSPX-AbCdEfGhIjKlMnOpQrStUvWx`
2. **Copia questi valori** (puoi anche scaricare il JSON)
3. Se chiudi il popup, puoi sempre trovarli in **API e servizi > Credenziali**

### Step 6: Inserire in WordPress

1. Vai su **WordPress Admin > Blackwork > Site Settings > My Account Page**
2. Spunta la checkbox **"Enable Google Login"**
3. Incolla:
   - **Google Client ID**: il valore dell'ID client
   - **Google Client Secret**: il valore del segreto client
4. Clicca **"Salva impostazioni"**

### Step 7: Pubblicare l'App (IMPORTANTE!)

**⚠️ L'app è in modalità "Testing" per default!**

Per rendere il login pubblico:
1. Vai su **API e servizi > Schermata consenso OAuth**
2. In alto, sotto "Stato pubblicazione", clicca **"Pubblica app"** (Publish app)
3. Conferma la pubblicazione

**Nota**: Se lasci l'app in modalità "Testing", solo gli utenti aggiunti come "Utenti test" potranno fare login.

### Permessi Richiesti

L'app richiede solo:
- ✅ **openid** (identificativo utente)
- ✅ **email** (indirizzo email)
- ✅ **profile** (nome e foto profilo)

Questi sono permessi base e non richiedono verifica da Google.

---

## ✅ Checklist Finale di Verifica

### Facebook
- [ ] App creata su Facebook Developers
- [ ] Facebook Login configurato
- [ ] Valid OAuth Redirect URI configurato (URL esatto da WordPress)
- [ ] App ID copiato
- [ ] App Secret copiato
- [ ] App pubblicata (modalità "Live")
- [ ] Valori inseriti nel pannello WordPress
- [ ] Checkbox "Enable Facebook Login" attivata

### Google
- [ ] Progetto creato su Google Cloud Console
- [ ] Google+ API abilitata (o People API)
- [ ] OAuth Consent Screen configurato
- [ ] Credenziali OAuth 2.0 create
- [ ] Authorized Redirect URI configurato (URL esatto da WordPress)
- [ ] Client ID copiato
- [ ] Client Secret copiato
- [ ] App pubblicata (non in "Testing" o utenti test aggiunti)
- [ ] Valori inseriti nel pannello WordPress
- [ ] Checkbox "Enable Google Login" attivata

---

## 🧪 Test Funzionalità

### Come Testare

1. **Logout da WordPress** (se sei loggato)
2. Vai alla pagina **My Account** (es. `https://tuosito.com/my-account/`)
3. Dovresti vedere i bottoni:
   - **"Login with Google"** (con icona colorata)
   - **"Login with Facebook"** (con icona bianca su nero)
4. **Clicca su un bottone**
5. Dovresti essere reindirizzato a Facebook/Google
6. **Autorizza l'accesso**
7. Dovresti essere reindirizzato al tuo sito e **loggato automaticamente**

### Cosa Fare se Non Funziona

**Se vedi errore "Social login is not available at the moment":**
- Verifica che le checkbox "Enable" siano attive
- Verifica che App ID/Secret o Client ID/Secret siano inseriti correttamente
- Controlla che non ci siano spazi extra copiando/incollando

**Se vieni reindirizzato ma vedi errore "redirect_uri_mismatch":**
- L'URL di redirect nel pannello Facebook/Google NON corrisponde
- Copia ESATTAMENTE l'URL dal pannello WordPress (incluso `?bw_social_login_callback=...`)
- Verifica che sia HTTPS (non HTTP)

**Se vieni reindirizzato ma vedi errore "invalid_client":**
- Client ID o Client Secret errati
- Verifica di aver copiato i valori corretti
- Prova a rigenerare le credenziali

**Se il login funziona solo per te (admin) ma non per altri utenti:**
- L'app è ancora in modalità "Development" (Facebook) o "Testing" (Google)
- Pubblica l'app seguendo gli step sopra

---

## 🔐 Note di Sicurezza

### Protezione delle Credenziali

**⚠️ IMPORTANTE**: Non condividere mai pubblicamente:
- Facebook App Secret
- Google Client Secret

Questi valori sono sensibili e permettono di impersonare la tua app.

### HTTPS Obbligatorio

OAuth 2.0 richiede **HTTPS** per sicurezza. Se il tuo sito è solo HTTP:
1. Installa un certificato SSL (Let's Encrypt è gratuito)
2. Configura WordPress per usare HTTPS
3. Aggiorna gli URL di redirect nelle console Facebook/Google

### Backup delle Credenziali

Salva le credenziali in un password manager o file sicuro:
```
Facebook:
- App ID: 1234567890123456
- App Secret: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

Google:
- Client ID: 123456789-abc123xyz.apps.googleusercontent.com
- Client Secret: GOCSPX-AbCdEfGhIjKlMnOpQrStUvWx
```

---

## 📞 Supporto

Se dopo aver seguito questa guida i login social continuano a non funzionare:

1. **Attiva il debug di WordPress**:
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Controlla il log**: `wp-content/debug.log`

3. **Verifica gli URL di redirect**:
   - Devono essere IDENTICI in WordPress e nelle console OAuth
   - Inclusi protocollo (https://), dominio, path e query parameters

4. **Test con Browser DevTools**:
   - Apri DevTools (F12)
   - Tab "Network"
   - Clicca sul bottone social login
   - Verifica i redirect e eventuali errori

---

## 📚 Risorse Utili

### Facebook
- Developers Console: https://developers.facebook.com/apps/
- Documentazione Facebook Login: https://developers.facebook.com/docs/facebook-login/web
- Testing Login Flow: https://developers.facebook.com/tools/debug/

### Google
- Cloud Console: https://console.cloud.google.com/
- Documentazione OAuth 2.0: https://developers.google.com/identity/protocols/oauth2
- OAuth 2.0 Playground: https://developers.google.com/oauthplayground/

---

**Ultimo aggiornamento**: 2025-12-23
**Versione Plugin**: BW Elementor Widgets
**Compatibilità**: WordPress 5.0+, WooCommerce 3.0+


---

## Source: `docs/40-integrations/brevo/README.md`

# Brevo

## Files
- [brevo-architecture-map.md](brevo-architecture-map.md): official Brevo integration architecture map (consent gate, triggers, idempotent sync, observability).
- [brevo-mail-marketing-architecture.md](brevo-mail-marketing-architecture.md): Brevo integration architecture and runtime model.
- [brevo-mail-marketing-roadmap.md](brevo-mail-marketing-roadmap.md): Brevo roadmap and phases.
- [mail-marketing-qa-checklist.md](mail-marketing-qa-checklist.md): QA checklist for Brevo integration.
- [subscribe.md](subscribe.md): governance and update workflow for subscription model.

## Admin UI Contract
- `Blackwork Site > Mail Marketing` adopts the shared Blackwork Admin UI Kit (`admin/css/bw-admin-ui-kit.css`).
- Layout pattern: `.bw-admin-root` shell, header + subtitle, action bar with save CTA, card-grouped settings, and UI-kit tab styling.
- Scope and behavior constraints:
  - UI-only alignment (no option-key/default/validation/runtime behavior changes).
  - CSS remains scoped under `.bw-admin-root` and loaded only on Blackwork Site admin pages.


---

## Source: `docs/40-integrations/brevo/brevo-architecture-map.md`

# Brevo Integration Architecture Map

## 1) Purpose + Scope
This document defines the official architecture model for the Brevo integration in Blackwork.

Core guarantees:
- GDPR-safe opt-in model based on explicit consent evidence.
- Non-blocking checkout behavior (marketing sync is decoupled from payment completion).
- Idempotent contact synchronization (email-based upsert to Brevo).

Out of scope / non-guarantees:
- No continuous background polling model unless explicitly triggered by existing admin actions.
- No external-source authority override of local consent truth.

## 2) Integration Layers

### Admin Config Layer
- Settings source: `bw_mail_marketing_general_settings` and `bw_mail_marketing_checkout_settings`.
- Core controls: API key, list selection, opt-in mode/timing, channel flags, sender/DOI options, debug flag.

### Consent Capture Layer
- Checkout field injection (`bw_subscribe_newsletter`) and consent metadata persistence.
- Consent evidence saved on order before/after checkout save pipeline.

### Runtime Trigger Layer
- Automatic triggers:
  - order created hook (optional mode)
  - order paid hooks (`processing` / `completed`) as default model
- Manual/admin triggers:
  - order metabox retry
  - orders bulk resync action
  - explicit remote status check actions

### Brevo API Client Layer
- API wrapper: `BW_Brevo_Client`.
- Main operations:
  - account connectivity test
  - contact fetch
  - contact upsert + list assignment
  - double opt-in confirmation trigger

### Local State Layer
- Order meta + user meta are local authority for consent/sync status.
- Brevo is treated as delivery destination, not local truth owner.

### Admin Observability Layer
- Order-level panel/metabox actions (refresh, retry, load lists).
- Orders list columns + filters + bulk resync.
- User profile mail-marketing panel with check/sync actions.

### Logging Layer
- Logging via `wc_get_logger()` with source `bw-brevo`.
- Structured reason/status traces are persisted in meta and log messages.

## 3) Data Model (Local)
Canonical order-level meta keys:

Consent meta:
- `_bw_subscribe_newsletter` (0/1 opt-in flag)
- `_bw_subscribe_consent_at` (timestamp)
- `_bw_subscribe_consent_source` (e.g. checkout)

Sync meta:
- `_bw_brevo_subscribed` (runtime status)
- `_bw_brevo_status_reason` (reason code)
- `_bw_brevo_contact_id` (remote contact id when available)
- `_bw_brevo_last_attempt_at`
- `_bw_brevo_last_attempt_source`
- `_bw_brevo_last_checked_at`
- `_bw_brevo_error_last`

User-level observability mirrors:
- `_bw_brevo_user_status`
- `_bw_brevo_user_reason`
- `_bw_brevo_user_last_checked_at`
- `_bw_brevo_user_contact_id`
- `_bw_brevo_user_error_last`

Allowed canonical statuses for architecture mapping:
- `subscribed`
- `error`
- `skipped_no_consent`
- `skipped_other`

Implementation note:
- Runtime local values may include internal variants like `pending` and `skipped`; the canonical map above is the normalized cross-document model.

## 4) Runtime Flow Model

### A) Checkout opt-in -> order created -> order paid -> sync attempt -> local state update
1. Consent checkbox is captured on checkout.
2. Consent metadata is persisted to order.
3. At configured timing (default: paid), subscription attempt executes.
4. Brevo API call attempts upsert/DOI.
5. Local order meta is updated with status/reason/attempt/check/error fields.

### B) Manual retry from Order metabox -> same consent gate -> sync attempt -> update
1. Admin retry action triggers order resync.
2. Same consent gate (`can_subscribe_order`) is enforced.
3. Brevo call executes only when gate allows.
4. Local state/logs are updated as authoritative audit output.

### C) Bulk resync from Orders list -> safe processing -> update
1. Selected orders run through the same server-side resync path.
2. Each order is processed independently with per-order status result.
3. Local state updates are deterministic and idempotent by email/list model.

### D) Remote check action (“Check Brevo”) -> explicit admin-only remote call -> reconcile view
1. Admin explicitly triggers check action.
2. Remote contact/list status is queried.
3. Admin panel/view state is reconciled.
4. No implicit background mutation model is assumed beyond defined action behavior.

## 5) Consent Security Model (GDPR Gate)
- `can_subscribe_order()` is the hard gate for write-side subscribe operations.

Required consent conditions:
- opt-in flag present and true
- consent timestamp present
- consent source present

Invariants:
- No Brevo subscribe API call without valid consent evidence.
- Retry and bulk actions cannot bypass the consent gate.

## 6) Idempotency & Precedence Rules
- Primary identity key for Brevo sync: email.
- Upsert semantics ensure repeated sync attempts converge on same contact identity.
- Local consent is source-of-truth for eligibility.
- Brevo is downstream destination, not authority for local consent state.
- Subscribe timing precedence:
  - default: `paid` (safer alignment with completed commerce intent)
  - optional: `created` (explicitly configured alternative)

## 7) Failure Model & Safe Degrade

### Invalid API key / missing credentials
- Local status: error or skip-with-reason metadata.
- Logging: reason code/message persisted.
- Checkout impact: none (non-blocking).

### Brevo API down / timeout
- Local status: error.
- Logging: API failure message and attempt trace.
- Checkout impact: none.

### Rate limit / transient provider failure
- Local status: error or retry-later operational state via manual action.
- Logging: provider error persisted.
- Checkout impact: none.

### Attribute validation/schema rejection
- Runtime may retry with reduced/empty attributes.
- Local outcome can remain warning-like/error-like while preserving order flow.
- Checkout impact: none.

## 8) High-Risk Zones (Blast Radius)
- Checkout injection + consent persistence:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
- Paid-hook/runtime trigger path:
  - same frontend class (`maybe_subscribe_on_paid`, `process_subscription`)
- Consent hard gate:
  - `can_subscribe_order()` in frontend/admin handlers
- API client:
  - `includes/integrations/brevo/class-bw-brevo-client.php`
- Order/user admin actions and observability:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-admin.php`
  - `admin/js/bw-order-newsletter-status.js`
  - `admin/js/bw-user-mail-marketing.js`
- Orders list columns/filters/bulk resync:
  - `BW_Checkout_Subscribe_Admin` order list hooks and bulk handlers

## 9) Maintenance & Regression References
- [Regression Protocol](../../50-ops/regression-protocol.md)
- [Checkout Architecture Map](../../30-features/checkout/checkout-architecture-map.md)
- [Payments Architecture Map](../../40-integrations/payments/payments-architecture-map.md)
- [Brevo Mail Marketing Architecture](./brevo-mail-marketing-architecture.md)
- [Subscribe Governance](./subscribe.md)

## Normative Brevo Architecture Principles

### 1) Consent Gate Invariant (GDPR hard gate)
- `can_subscribe_order` is mandatory for any write-side subscribe operation (automatic, retry, bulk).
- No consent evidence means no Brevo subscribe API call.

### 2) Non-Blocking Commerce Invariant
- Brevo failures must never block checkout, payment execution, or order placement.
- Subscribe timing configuration must not alter payment/order authority.

### 3) Idempotency & Convergence Invariant
- Email is the primary contact identity for sync/upsert.
- Retries must converge on a single contact state (no duplicate-contact intent from integration flow).
- Local status transitions must remain deterministic for equal inputs.

### 4) Local Authority Invariant
- Consent truth is owned by WordPress/WooCommerce metadata.
- Brevo is delivery destination and synchronization target, not consent source-of-truth.

### 5) Observability Discipline
- No remote API calls should be executed purely during passive list rendering.
- Remote checks are allowed only through explicit admin actions.
- Every remote action must leave a local audit trail (meta updates + logger trace).

### 6) Error Normalization & Sensitive Data Policy
- Customer-facing errors must remain safe and minimal.
- Diagnostic detail belongs to logs/admin diagnostics.
- API keys and raw sensitive payloads must never be logged; only necessary identifiers should appear.

### 7) High-Risk Change Policy (Blast Radius Rule)
- Changes to consent capture, consent gate, paid-hook triggers, or Brevo API client behavior require regression validation.
- Such changes must follow the project regression protocol before release.


---

## Source: `docs/40-integrations/brevo/brevo-mail-marketing-architecture.md`

# Overview
This document defines the internal architecture of the Brevo Mail Marketing module inside the `wpblackwork` plugin.

Scope:
- Checkout newsletter consent capture (classic WooCommerce checkout only)
- Brevo contact sync and retry logic
- Order-level diagnostics and admin tooling
- Orders list visibility (Newsletter + Source columns, filters, bulk actions)
- User profile diagnostics panel

Out of scope:
- WooCommerce Checkout Block flow
- Marketing automation orchestration


# Checkout Flow
## Field injection
- Field key/name: `bw_subscribe_newsletter`
- Field type: checkbox
- Injected through `woocommerce_checkout_fields` in:
  - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
- Default location: billing section, after `billing_email` in classic checkout templates.
- The field is rendered inside the main checkout `<form name="checkout">`.

## Save lifecycle
Consent is persisted in two steps for robustness:
1. Primary save on `woocommerce_checkout_create_order` (order object context).
2. Fallback save on `woocommerce_checkout_update_order_meta` (post-meta context), with non-downgrade guards.

Input sources:
- Primary: `$_POST['bw_subscribe_newsletter']`
- Fallbacks: checkout posted data array, `$_POST['billing']['bw_subscribe_newsletter']`, `WC()->checkout()->get_value('bw_subscribe_newsletter')`
- Frontend diagnostic hidden input: `bw_subscribe_newsletter_frontend` (`checked` / `unchecked`)

## Meta created during checkout
- `_bw_subscribe_newsletter`
- `_bw_subscribe_consent_at`
- `_bw_subscribe_consent_source`
- `_bw_checkout_field_received`
- `_bw_checkout_field_value_raw`
- `_bw_checkout_post_keys_snapshot`
- `_bw_subscribe_newsletter_frontend`

## Source taxonomy
Consent source is stored in `_bw_subscribe_consent_source` and currently supports:
- `checkout`
- `coming_soon`
- `footer`
- `popup`
- `my_account`
- `supabase_google`
- `supabase_facebook`
- `supabase_magic_link`
- `app_stripe`


# Order Meta Keys
## Canonical keys currently used by the module
- `_bw_subscribe_newsletter`: `0|1`
- `_bw_subscribe_consent_at`: local datetime (`Y-m-d H:i:s`)
- `_bw_subscribe_consent_source`: currently `checkout`
- `_bw_brevo_subscribed`: `subscribed|pending|skipped|error|not_subscribed`
- `_bw_brevo_status_reason`: reason key (for example: `no_opt_in`, `api_error`, `already_subscribed`)
- `_bw_brevo_contact_id`: Brevo contact ID when known
- `_bw_brevo_last_attempt_at`: last subscribe attempt datetime
- `_bw_brevo_last_attempt_source`: `checkout_paid_hook|checkout_created_hook|manual_retry|refresh_check`
- `_bw_brevo_last_checked_at`: last remote check datetime
- `_bw_brevo_error_last`: last error message
- `_bw_checkout_field_received`: `yes|no`
- `_bw_checkout_field_value_raw`: normalized raw posted value
- `_bw_checkout_post_keys_snapshot`: compact presence snapshot for POST diagnostics
- `_bw_subscribe_newsletter_frontend`: `checked|unchecked`

## Compatibility aliases (read/write fallback mapping)
The codebase also supports legacy or alternative naming in selected paths:
- `_bw_brevo_status` -> fallback/read equivalent of `_bw_brevo_subscribed`
- `_bw_last_error` -> fallback/read equivalent of `_bw_brevo_error_last`

## Requested alias mapping (documentation bridge)
For teams using alternative naming in external docs:
- `_bw_consent_timestamp` == `_bw_subscribe_consent_at`
- `_bw_consent_source` == `_bw_subscribe_consent_source`
- `_bw_brevo_status` == `_bw_brevo_subscribed`
- `_bw_last_subscription_attempt` == `_bw_brevo_last_attempt_at`
- `_bw_attempt_source` == `_bw_brevo_last_attempt_source`
- `_bw_last_error` == `_bw_brevo_error_last`
- `_bw_checkout_field_raw` == `_bw_checkout_field_value_raw`
- `_bw_frontend_checkbox_state` == `_bw_subscribe_newsletter_frontend`


# Brevo Data Model
## Lists
- `#10 Blackwork - Marketing`:
  - primary list for consent granted contacts (`opt_in=1` + consent metadata present)
- `#11 Blackwork - Unconfirmed`:
  - placeholder for future DOI/non-checkout capture flows (not actively used yet)

List behavior rules:
- When consent is granted, subscribe/upsert into Marketing list.
- If plugin setting `list_id` is configured, it has priority over fallback.
- Fallback marketing list ID is `10` for backwards compatibility.
- No subscription is allowed without explicit consent gating.

## Attribute model (centralized map)
Attributes are built from one shared map (`BW_MailMarketing_Service::build_brevo_attributes_from_order()`), then reused across:
- checkout paid/created flow
- manual retry
- bulk resync

| Key | Type | Example | Source |
|---|---|---|---|
| `SOURCE` | text | `checkout` | `_bw_subscribe_consent_source` |
| `CONSENT_SOURCE` | text | `checkout` | `_bw_subscribe_consent_source` |
| `CONSENT_AT` | ISO8601 text | `2026-02-24T18:50:00Z` | `_bw_subscribe_consent_at` |
| `CONSENT_STATUS` | text | `granted` | consent gate state |
| `BW_ORIGIN_SYSTEM` | text | `wp` | hardcoded |
| `BW_ENV` | text | `production` / `staging` | `wp_get_environment_type()` / `WP_ENV` |
| `LAST_ORDER_ID` | text/number | `26859` | order id |
| `LAST_ORDER_AT` | ISO8601 text | `2026-02-24T19:03:00Z` | paid date, fallback created date |
| `CUSTOMER_STATUS` | text | `customer` | order context |
| `FIRSTNAME` | text | `Mario` | billing first name (if enabled) |
| `LASTNAME` | text | `Rossi` | billing last name (if enabled) |

SOURCE evolution notes:
- Current implementation updates `SOURCE` with latest consent source.
- `SOURCE_FIRST` is reserved for future implementation (first-touch immutable source).
- Until `SOURCE_FIRST` exists, historical first-source is not persisted separately.

Operational prerequisite:
- Custom attributes must exist in Brevo:
  - `Contacts -> Settings -> Attributes`
- If missing, integration logs warning and applies fallback payloads.


# Brevo Sync Logic
## Trigger hooks
- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`
- `woocommerce_checkout_order_processed` (when checkout timing is `created`)

## Conditions before sync
- Checkout channel enabled.
- Explicit opt-in recorded (`_bw_subscribe_newsletter = 1`).
- Valid billing email.
- Brevo API key configured.
- Main list ID configured.
- Respect no-auto-resubscribe policy for blocklisted/unsubscribed contacts.

## Single opt-in behavior
- Calls Brevo contact upsert with list assignment.
- Contact attributes sent (when opt-in = 1):
  - `SOURCE` = consent source
  - `CONSENT_SOURCE` = consent source
  - `CONSENT_AT` = consent timestamp in ISO8601 (if available)
  - `CONSENT_STATUS` = `granted`
  - `BW_ORIGIN_SYSTEM` = `wp`
  - `BW_ENV` = `production|staging` (environment detection)
  - `LAST_ORDER_ID` = Woo order ID
  - `LAST_ORDER_AT` = paid datetime ISO8601 (fallback: order created datetime)
  - `CUSTOMER_STATUS` = `customer`
  - `FIRSTNAME` / `LASTNAME` if enabled in General settings
- On success:
  - `_bw_brevo_subscribed = subscribed`
  - `_bw_brevo_status_reason = subscribed`
  - clears `_bw_brevo_error_last`

## Double opt-in behavior
- Supported by configuration (general/channel mode).
- Sends DOI request to Brevo with template + redirect URL and the same attributes payload.
- On success:
  - `_bw_brevo_subscribed = pending`
  - `_bw_brevo_status_reason = double_opt_in_sent`

## Error handling
- Errors set:
  - `_bw_brevo_subscribed = error`
  - `_bw_brevo_error_last = <message>`
  - `_bw_brevo_status_reason = api_error`
- Logs are written via Woo logger source `bw-brevo`.
- If Brevo rejects unknown custom attributes, the module retries once with a minimal payload (`FIRSTNAME`/`LASTNAME` only) and logs the fallback.
- If Brevo still rejects attributes, the module retries with empty attributes payload.
- Unknown-attribute failures are treated as non-fatal warnings:
  - checkout flow is not interrupted
  - order status is not forced to error for attribute-schema mismatches
  - warning is stored in `_bw_brevo_error_last` for diagnostics

## Brevo Attributes
The module sends these attributes only when consent is allowed (`opt_in=1` with consent metadata present):

| Key | Type | Context | Example |
|---|---|---|---|
| `SOURCE` | string | all subscribe/upsert flows | `checkout` |
| `CONSENT_SOURCE` | string | all subscribe/upsert flows | `checkout` |
| `CONSENT_AT` | ISO8601 datetime string | all flows when available | `2026-02-24T18:50:00Z` |
| `CONSENT_STATUS` | string | all flows | `granted` |
| `BW_ORIGIN_SYSTEM` | string | all flows | `wp` |
| `BW_ENV` | string | all flows | `production` |
| `LAST_ORDER_ID` | string/int | order-based flows | `26859` |
| `LAST_ORDER_AT` | ISO8601 datetime string | order-based flows | `2026-02-24T19:03:00Z` |
| `CUSTOMER_STATUS` | string | order-based flows | `customer` |
| `FIRSTNAME` | string | if enabled | `Mario` |
| `LASTNAME` | string | if enabled | `Rossi` |

Operational guidance:
- Create custom attributes in Brevo under `Contacts -> Settings -> Attributes` if they are not already available.
- If custom attributes are missing in Brevo, synchronization continues with fallback payloads and logs a warning.

## Retry logic
- Order metabox action `Retry subscribe` re-runs sync logic manually.
- Protected by nonce and capability checks.
- Guardrails:
  - no subscription without consent
  - no auto-resubscribe for blocklisted/unsubscribed contacts

## Bulk resync logic (orders list)
- Bulk action: `Mail Marketing: Resync to Brevo`
- Processes selected orders in chunks (25 per batch) to reduce timeout risk.
- Per-order behavior:
  - opt-in `0` => skipped (`_bw_brevo_status_reason = no_opt_in`)
  - invalid email => skipped
  - blocklisted/unsubscribed => skipped
  - opt-in `1` + valid config => idempotent upsert/DOI retry
- Meta updates include:
  - `_bw_brevo_subscribed`
  - `_bw_brevo_error_last`
  - `_bw_brevo_last_attempt_at`
  - `_bw_brevo_last_attempt_source = bulk_resync`
- Completion notice:
  - `Resync complete: X subscribed, Y skipped, Z errors`


# Admin UI
## Order metabox: Newsletter Status
Location:
- WooCommerce order edit screen (full-width metabox, high priority)

Features:
- Status badge with icon and color state
- Actions:
  - `Check Brevo` (read-only remote check)
  - `Retry subscribe` (write action)
- Contextual notice messages
- Structured sections:
  - `Consent`
  - `Brevo Sync`
  - `Advanced` (collapsible diagnostics)
- Advanced diagnostics includes `Brevo attributes payload (summary)` (keys only, no sensitive payload values)
- Copy-to-clipboard for email/contact ID

## Orders list custom columns: Newsletter + Source
Location:
- Orders list table (classic and HPOS list views)

Behavior:
- Reads only local order meta (no remote API calls)
- Displays compact state (`Subscribed`, `Pending`, `No`, `Error`) with visual indicator
- Tooltip includes status + consent timestamp
- `Source` column renders `_bw_subscribe_consent_source` as a muted pill (or `—` if empty)

## Orders list filters
- `Newsletter status` dropdown:
  - Any
  - Subscribed
  - Pending
  - No opt-in
  - Error
- `Source` dropdown:
  - Any
  - checkout
  - coming_soon
  - footer
  - popup
  - my_account
  - supabase_google
  - supabase_facebook
- Filters are applied using `meta_query` only (no API calls).

## User profile panel: Mail Marketing - Brevo
Location:
- WP Admin user profile/edit user screens (top panel)

Features:
- Current Brevo status view
- Consent timestamp/source display (if present in user meta)
- Actions:
  - `Check Brevo` (read-only)
  - `Sync status` (writes user status diagnostics only)
- Does not auto-subscribe users


# Status Mapping
UI status is derived from consent and sync meta:

- **Subscribed**
  - Opt-in `1` and Brevo sync status `subscribed`
- **Pending**
  - Brevo sync status `pending` (typically DOI flow)
- **No opt-in / Skipped**
  - Opt-in `0` or explicit skipped reasons (`no_opt_in`, `missing_list_id`, etc.)
- **Error**
  - Brevo status `error` and/or non-empty last error metadata

Reason labels are resolved from `_bw_brevo_status_reason` and surfaced in UI.


# Performance Notes
- Orders list columns and filter dropdowns do not call Brevo APIs.
- List names are resolved from transient cache (`bw_brevo_lists_map_<hash>`).
- Remote Brevo checks are on-demand only (button actions), not automatic on page load.
- Order and user panels use lightweight AJAX endpoints with nonce/capability checks.
- Bulk resync is processed in fixed-size chunks (25 orders per chunk).


# Future Improvements
- Fully exposed and validated double opt-in strategy per channel in admin UX.
- Bulk order/user resync tools with batching and rate-limit safeguards.
- Automation events/webhooks for CRM and lifecycle campaigns.
- Unified service layer for checkout + user profile + future touchpoints.
- Supabase flow integration with consent/state consistency guarantees.


---

## Source: `docs/40-integrations/brevo/brevo-mail-marketing-roadmap.md`

# 🚀 Blackwork -- Brevo Mail Marketing Roadmap

*Last updated: 2026-02-24*

Legend:

-   🟩 **Brevo Panel**
-   🟦 **WordPress Plugin (Blackwork Site)**
-   ✅ Completed
-   ⏳ In Progress / To Implement
-   🔜 Future Phase

------------------------------------------------------------------------

# PHASE 0 --- Foundations (Infrastructure)

## 🟩 Brevo

-   ✅ Domain authenticated (SPF / DKIM / DMARC)
-   ✅ Sender verified (hello@blackwork.pro)
-   ✅ Folder: Blackwork -- CRM
-   ✅ List: Blackwork -- Marketing
-   ✅ List: Blackwork -- Unconfirmed

## 🟦 WordPress Plugin

-   ✅ Mail Marketing section (General + Checkout)
-   ✅ API Key connected + Test Connection working
-   ✅ Checkout opt-in (classic checkout)
-   ✅ Brevo sync on paid hook
-   ✅ GDPR consent gating (no subscription without consent)
-   ✅ Order Admin "Newsletter Status" panel
-   ✅ Orders list column (Newsletter)
-   ✅ User profile panel (Brevo status)

------------------------------------------------------------------------

# PHASE 1 --- Brevo Data Model (Structured Attributes)

## 🟩 Brevo -- Required Contact Attributes

⏳ Create / verify these attributes in Brevo:

-   SOURCE (text)
-   CONSENT_SOURCE (text)
-   CONSENT_STATUS (text: granted / pending / revoked)
-   CONSENT_AT (text or date)
-   BW_ORIGIN_SYSTEM (text: wp / app)
-   BW_ENV (text: production / staging)

Optional (recommended): - LAST_ORDER_ID (number/text) - LAST_ORDER_AT
(date/text) - CUSTOMER_STATUS (lead / customer / repeat_customer)

## 🟦 WordPress Plugin

⏳ Send structured attributes on every upsert (checkout + retry + bulk).
⏳ Standardize source taxonomy: - checkout - footer - popup -
coming_soon - my_account - supabase_google - supabase_facebook -
supabase_magic_link - app_stripe

------------------------------------------------------------------------

# PHASE 2 --- Automations (Brevo)

## 🟩 Brevo

⏳ Welcome workflow (trigger: added to Marketing list) ⏳ Branch logic
based on CONSENT_SOURCE ⏳ Double opt-in workflow for Unconfirmed list
🔜 Post-purchase automation

## 🟦 WordPress Plugin

🔜 Optional post-order attribute updates (orders_count, status)

------------------------------------------------------------------------

# PHASE 3 --- New Subscription Channels

## 🟩 Brevo

⏳ Double opt-in template (popup/footer/coming soon) ⏳ Confirmation
redirect page

## 🟦 WordPress Plugin

🔜 Coming Soon migration to central subscribe service 🔜 Footer
newsletter form 🔜 Popup newsletter form 🔜 My Account marketing toggle
🔜 Supabase login flow with explicit opt-in prompt

------------------------------------------------------------------------

# PHASE 4 --- Compliance & Preferences

## 🟩 Brevo

⏳ Verify unsubscribe handling 🔜 Optional Preference Center

## 🟦 WordPress Plugin

🔜 My Account "Email Preferences" section 🔜 Sync unsubscribe status (if
needed)

------------------------------------------------------------------------

# PHASE 5 --- Admin Tools & Operations

## 🟩 Brevo

🔜 Saved segments (by SOURCE, CONSENT_STATUS, CUSTOMER_STATUS)

## 🟦 WordPress Plugin

⏳ Orders filter: Newsletter status ⏳ Orders filter: Source ⏳ Bulk
action: Resync to Brevo ⏳ CSV Export (Email + Status + Source +
Consent) 🔜 Bulk read-only Brevo check

------------------------------------------------------------------------

# PHASE 6 --- Monitoring & Optimization

## 🟩 Brevo

🔜 Deliverability monitoring 🔜 Bounce / complaint analysis

## 🟦 WordPress Plugin

🔜 Standardized reason codes in logs 🔜 QA checklist for each new
channel

------------------------------------------------------------------------

# Current Status Summary

Checkout opt-in + Brevo sync is fully operational and GDPR-safe.

Next strategic priority: → Complete Brevo attribute model + Welcome
automation.


---

## Source: `docs/40-integrations/brevo/mail-marketing-qa-checklist.md`

# Mail Marketing -- QA Checklist (Verification Report)

Blackwork Site Plugin  
Brevo Integration -- Checkout & Admin Refactor

Data verifica: 2026-02-24

---

## Pre-flight

- [ ] Backup database (or export relevant options)
- [ ] Enable WP_DEBUG_LOG (staging/local recommended)
- [ ] Confirm valid Brevo API key
- [ ] Ensure test list exists in Brevo

Esito: ⚠️ Partially implemented

Note QA:
- Questi sono prerequisiti operativi/manuali e non sono enforceati dal codice plugin.

---

## 1. Admin UI -- Structure

- [x] New submenu exists: **Blackwork Site → Mail Marketing**
- [x] Tabs visible: **General** and **Checkout**
- [x] Old page "Checkout → Subscribe":
  - [ ] Removed OR
  - [x] Displays notice + redirect/link to new page
- [x] No duplicated configuration pages exist

Esito: ⚠️ Partially implemented

Dettaglio:
- Submenu nuovo implementato (`add_submenu_page(... blackwork-mail-marketing ...)`).
- `General`/`Checkout` visibili nella nuova pagina.
- Vecchio `Checkout > Subscribe` non salva più impostazioni: mostra solo notice+link.
- Il route legacy `checkout_tab=subscribe` è ancora raggiungibile (nascosto dalla nav, ma ammesso dal controllo tab). Non crea divergenza perché non contiene più form di salvataggio.

Cosa manca (strict):
- Se si vuole “hard remove”, eliminare `subscribe` da `$allowed_checkout_tabs`.

---

## 2. Settings Migration

- [x] Previous values migrated automatically
- [x] New options exist in DB:
  - `bw_mail_marketing_general_settings`
  - `bw_mail_marketing_checkout_settings`
- [x] Changing values persists correctly after refresh (code path)
- [ ] Old option `bw_checkout_subscribe_settings` not used as primary source

Esito: ⚠️ Partially implemented

Dettaglio:
- Migrazione automatica presente su `admin_init` (`maybe_migrate_legacy_settings`).
- Mapping split corretto:
  - API/list/DOI → general settings
  - enabled/default_checked/label/privacy/timing → checkout settings
- Salvataggio nuove opzioni con `update_option(...)` implementato.
- Fallback legacy ancora presente (`get_general_settings()` / `get_checkout_settings()` leggono old option se nuove vuote).

Cosa manca (strict):
- “Single source of truth assoluta”: rimuovere fallback legacy dopo migrazione consolidata (o introdurre flag `migration_completed`).

---

## 3. General Tab -- Brevo Connection

### Test Connection

- [x] Button works with nonce & capability check (code path)
- [x] Invalid API key shows proper error
- [x] Valid API key returns success message
- [ ] No PHP warnings or JS errors (runtime browser test not eseguito in questa review)

### API Base URL

- [x] Not editable (hardcoded/readonly)

### List Selector

- [x] Dropdown loads lists (if API works)
- [x] Selected list saves correctly

Esito: ⚠️ Partially implemented

Dettaglio:
- AJAX protetto con `current_user_can('manage_options')` + `check_ajax_referer(...)`.
- Se API non disponibile, fallback a input numerico list ID implementato.
- Non effettuato test runtime browser/console in questa passata (solo code-trace).

---

## 4. Checkout Tab -- UI & Behavior

- [x] Enable newsletter checkbox toggle works
- [x] Default checked behaves correctly
- [x] Label and privacy text render properly
- [x] Subscribe timing select exists (paid / created)
- [ ] Placement compatible with Checkout Fields

Esito: ⚠️ Partially implemented

Dettaglio:
- Campi checkout tab presenti e persistiti.
- Frontend usa settings nuovi (con fallback) per inject newsletter field.
- Gap reale: il template `form-billing.php` forza rendering `billing_email` + `bw_subscribe_newsletter` in testa, quindi `placement_after_key`/`priority_offset` non hanno effetto pieno in questa architettura.

Cosa manca:
- Rimuovere hardcoded priority rendering dal template billing oppure adattarlo a rispettare `priority` del field.

---

## 5. Checkout -- Consent Save

### Checkbox NOT checked

- [x] `_bw_subscribe_newsletter` = 0
- [x] `_bw_brevo_subscribed` = skipped
- [x] No Brevo API call made

### Checkbox checked

- [x] `_bw_subscribe_newsletter` = 1
- [x] `_bw_subscribe_consent_at` saved
- [x] `_bw_subscribe_consent_source` = checkout
- [ ] `_bw_brevo_subscribed` = pending (if timing=paid)

Esito: ⚠️ Partially implemented

Dettaglio:
- OFF path coerente: stato `skipped`, niente chiamata API.
- ON path salva correttamente consenso/meta.
- Gap: `pending` non viene impostato subito su submit quando `timing=paid` (single opt-in). `pending` è usato per DOI request inviata.

Cosa manca:
- Se richiesto dal criterio QA, impostare `pending` già in fase submit quando timing=paid e opt-in=1.

---

## 6. Paid Trigger

- [x] On order status processing/completed:
  - [x] Contact added to Brevo list
  - [x] `_bw_brevo_subscribed` updated to subscribed
  - [x] Log written under `bw-brevo`

Esito: ✅ Fully implemented

Dettaglio:
- Hook presenti su `woocommerce_order_status_processing` e `woocommerce_order_status_completed`.
- Single opt-in usa `upsert_contact(...)`.
- Stato `subscribed` scritto su successo.
- Logging con `source => bw-brevo` + `order_id/email/context/result`.

---

## 7. Double Opt-in (If Enabled)

- [x] DOI email sent (code path)
- [x] Redirect works (redirect URL passed to API)
- [ ] Contact appears in list after confirmation (richiede test live)

Esito: ⚠️ Partially implemented

Dettaglio:
- Chiamata DOI implementata (`send_double_opt_in(...)`) con template, redirect, list, sender.
- Verifica “appears after confirmation” non dimostrabile solo da code review.

---

## 8. Duplicate Email Handling

- [x] No duplicate contacts created (upsert semantics)
- [x] Status logged correctly

Esito: ✅ Fully implemented (code-level)

Dettaglio:
- `upsert_contact` usa `updateEnabled=true`, quindi update contatto esistente invece di creare duplicati.
- Stati/log aggiornati coerentemente su successo/errore.

---

## 9. Unsubscribed / Blocklisted Handling

- [x] No automatic resubscribe (for blocklisted branch)
- [x] Status marked skipped
- [x] Log contains correct reason

Esito: ⚠️ Partially implemented

Dettaglio:
- Controllo pre-subscribe via `get_contact()` e skip se `emailBlacklisted`.
- Stato `skipped` + log reason presenti.
- Gap strict: la copertura “unsubscribed” dipende da come Brevo espone lo stato; codice oggi verifica esplicitamente `emailBlacklisted`.

Cosa manca:
- Estendere guardia con tutti i flag/stati Brevo rilevanti per opt-out/unsubscribed, non solo `emailBlacklisted`.

---

## 10. Security & Validation

- [x] AJAX protected with nonce
- [x] Capability checks in place
- [x] Inputs sanitized properly

Esito: ✅ Fully implemented

Dettaglio:
- AJAX test protetto con capability + nonce.
- Sanitizzazione coerente su campi general/checkout.

---

## 11. Regression Checks

- [ ] Checkout still completes normally (non eseguito runtime)
- [ ] No JS errors on frontend (non eseguito browser runtime)
- [ ] No impact on Supabase login flow (non eseguito e2e)
- [ ] No impact on cart popup (non eseguito e2e)

Esito: ⚠️ Partially implemented

Dettaglio:
- Code diff non mostra modifiche dirette ai moduli Supabase/cart popup.
- Mancano test e2e/manuali runtime per chiudere il punto con evidenza.

---

## Quick Smoke Test (2-Minute Check)

1. Test connection works
2. Place guest order with opt-in
3. Set order to paid
4. Contact appears in Brevo list
5. Log entry created

Esito: ❌ Missing execution evidence

Dettaglio:
- Checklist smoke non eseguita live in questa review (solo code verification rigorosa).

---

## Requested Focus Checks (Strict)

- [x] Migration legacy → new options: implementata
- [ ] New options as only source of truth: non al 100% (fallback legacy presente)
- [x] Old Checkout→Subscribe page no longer saves data
- [x] No duplicate editable subscribe config page
- [x] AJAX test protected (nonce + capability)
- [x] Paid timing hook wired and active
- [x] `_bw_brevo_subscribed` state machine implemented (`subscribed|pending|skipped|error`)
- [x] Logs written with source `bw-brevo`
- [ ] Unsubscribed/blocklisted never auto-resubscribed in all Brevo semantics (partial coverage: `emailBlacklisted`)

---

## Mental Flow Simulation

### Guest checkout with opt-in OFF
- `_bw_subscribe_newsletter=0`
- `_bw_brevo_subscribed=skipped`
- Paid hook exits before API subscribe

Esito: ✅

### Guest checkout with opt-in ON
- Consenso/meta salvati
- Se timing=paid: subscribe in fase status change
- `pending` solo su DOI request inviata

Esito: ⚠️ (pending semantics non allineata al punto checklist “pending on submit for paid”)

### Order moves to processing
- Hook paid attivo, subscribe path eseguito

Esito: ✅

### Email already exists in Brevo
- `upsert_contact` update, non duplica

Esito: ✅

### Email unsubscribed in Brevo
- Skip garantito se contatto risulta `emailBlacklisted`
- Copertura non totale per eventuali altri stati opt-out

Esito: ⚠️

---

## Production Gate Summary

Stato complessivo: ⚠️ NOT READY FOR STRICT PRODUCTION PUSH (senza chiudere i gap sotto)

Gap bloccanti consigliati:
1. Rendere effettivo il placement checkout (oggi parzialmente bypassato dal template billing hardcoded).
2. Decidere e uniformare semantica `pending` per timing=paid al submit.
3. Completare guardia no-resubscribe per tutti gli stati Brevo opt-out rilevanti.
4. Eseguire smoke/e2e reali (admin + checkout + paid + Brevo) con evidenza log.


---

## Source: `docs/40-integrations/brevo/subscribe.md`

# Documentation Governance & Update Workflow

Document Version: 1.2  
Last Updated: 2026-02-24  
Last Update Summary: Added centralized Brevo Data Model service, attribute map standardization, and consent-safe admin UX refinements.

## 0.1 Purpose of this Document
This file is the official technical specification and single source of truth for the Brevo Mail Marketing system in the Blackwork Site plugin.

It governs and documents:

- Architecture
- Configuration
- Runtime flow
- Compliance logic
- State machine behavior

## 0.2 How to Update This Document
Mandatory maintenance rules:

- Every architectural change must be documented in the relevant architecture section.
- Every new setting must be added under the correct configuration section.
- Every new hook must be listed in the `Subscription Flow (Runtime Behavior)` section.
- Every runtime state change must update the `State Machine` section.
- Every logging behavior change must update the `Logging Specification` section.
- Deprecated logic must be moved to `Migration & Legacy Notes` and marked as deprecated.
- Historical notes must never be deleted; they must be marked as deprecated and retained.
- Each update must refresh the top metadata lines:
  - `Document Version`
  - `Last Updated`
  - `Last Update Summary`

## 0.3 Versioning Strategy
Internal document versioning rules:

- Major: architectural refactors or major model changes
- Minor: new functional capabilities or new documented subsystems
- Patch: corrections, clarifications, non-functional documentation fixes

Current baseline:

- Document Version: `1.2`
- Last Updated: `2026-02-24`
- Last Update Summary: `Added centralized Brevo Data Model service, attribute map standardization, and consent-safe admin UX refinements.`

## 0.4 Change Log Section
A persistent change log must be maintained at the end of this document under `10. Change Log`.

Required format:

```text
## vX.Y - YYYY-MM-DD
- Change summary line 1
- Change summary line 2
```

---

# 1. Overview
This specification describes the Brevo Mail Marketing system implemented in the Blackwork Site plugin.

Current admin IA:

- `Blackwork Site -> Mail Marketing -> General`
- `Blackwork Site -> Mail Marketing -> Checkout`

System goals:

- Centralize Brevo credentials and global behavior
- Separate global settings from checkout-channel behavior
- Preserve backward compatibility during migration
- Guarantee explicit consent-driven subscription logic
- Provide auditable status transitions and logs

Runtime scope currently documented here:

- Admin settings and migration
- Checkout subscription channel
- Brevo API integration
- Metadata state tracking
- Compliance and safety rules

---

# 2. Architecture Overview
The system is organized into five layers.

## 2.1 Admin Layer
Primary components:

- `BW_Checkout_Subscribe_Admin`
- `BW_Mail_Marketing_Settings`

Responsibilities:

- Register `Mail Marketing` submenu under `Blackwork Site`
- Render tabbed admin UI (`General`, `Checkout`)
- Render WooCommerce Order Admin Newsletter Status panel
- Sanitize and persist settings
- Execute legacy migration bootstrap
- Handle secure AJAX connection testing
- Handle secure AJAX order actions (`Refresh`, `Retry subscribe`)

## 2.2 Service Orchestration Layer
Current implementation:

- Runtime orchestration is handled inside `BW_Checkout_Subscribe_Frontend::process_subscription()`.

Planned direction:

- Introduce centralized `BW_Subscribe_Service` to decouple channel logic from checkout-specific class.

## 2.3 Brevo Client Layer
Shared API adapter:

- `BW_Brevo_Client`

Primary methods:

- `get_account()`
- `get_lists()`
- `get_contact($email)`
- `upsert_contact($email, $attributes, $list_ids)`
- `send_double_opt_in(...)`

## 2.4 Frontend Integration Layer
Current channel:

- Checkout classic form only

Primary component:

- `BW_Checkout_Subscribe_Frontend`

Responsibilities:

- Inject checkout checkbox
- Save consent metadata
- Trigger subscription at configured timing
- Persist subscription status and errors

Admin order integration:

- `woocommerce_admin_order_data_after_order_details` renders status panel and debug table
- No Brevo call is executed automatically on page load
- Brevo sync/write calls are user-triggered via AJAX buttons

## 2.5 Logging and Audit Layer
Backend:

- WooCommerce logger via `wc_get_logger()`

Source:

- `bw-brevo`

Context payload includes:

- `order_id`
- `email`
- `context` (`checkout_guest` or `checkout_user`)
- `result` (`pending`, `subscribed`, `skipped`, `error`)
- `action` (`refresh`, `retry`, and runtime checkout flow)

---

# 3. Admin Information Architecture

## 3.1 Mail Marketing -> General
Purpose: global Brevo configuration shared across channels.

Option name:

```text
bw_mail_marketing_general_settings
```

Fields:

- `api_key` (required for API operations)
- `api_base` (fixed: `https://api.brevo.com/v3`, not editable)
- `list_id` (main list)
- `default_optin_mode` (`single_opt_in` | `double_opt_in`)
- `double_optin_template_id`
- `double_optin_redirect_url`
- `sender_name`
- `sender_email`
- `debug_logging` (toggle)
- `resubscribe_policy` (`no_auto_resubscribe`)
- `sync_first_name` (toggle)
- `sync_last_name` (toggle)

Brevo list loading behavior:

- Preferred mode: dropdown from Brevo API (`get_lists()`)
- Fallback mode: numeric `list_id` input if list retrieval fails
- This fallback is mandatory to avoid UI hard-failure when API lookup is unavailable

Connection test:

- AJAX action: `bw_brevo_test_connection`
- Protected by capability check and nonce validation

## 3.2 Mail Marketing -> Checkout
Purpose: checkout-channel-specific behavior.

Option name:

```text
bw_mail_marketing_checkout_settings
```

Fields:

- `enabled`
- `default_checked`
- `label_text`
- `privacy_text`
- `subscribe_timing` (`paid` default, `created` optional)
- `channel_optin_mode` (`inherit` | `single_opt_in` | `double_opt_in`)
- `placement_after_key` (default `billing_email`)
- `priority_offset` (default `5`)

Channel override behavior:

- `inherit`: uses `General.default_optin_mode`
- `single_opt_in`: forces single opt-in for checkout
- `double_opt_in`: forces DOI for checkout

Checkout support limitation:

- Only classic WooCommerce checkout is supported.
- Checkout Block is explicitly excluded from this integration.

## 3.3 WooCommerce Order Admin -> Newsletter Status Panel
Purpose: operational visibility and manual controls per order.

Location:

- WooCommerce order edit screen, rendered in order data area.

UI components:

- Prominent status badge:
  - `Subscribed` (green)
  - `Pending` (blue/gray)
  - `Not subscribed` (neutral)
  - `Error` (red)
- `Refresh` button (read-only sync with Brevo)
- `Retry subscribe` button (write action)
- Inline response message area
- Meta Debug Table with:
  - Email
  - List ID used
  - Opt-in value
  - Consent timestamp
  - Consent source
  - Current Brevo status
  - Last error
  - Last checked timestamp
  - Brevo contact ID

Order admin panel behavior:

- No network call on page load
- Buttons are disabled while AJAX action is running
- UI updates status badge and debug values inline after response

---

# 4. Subscription Flow (Runtime Behavior)

## 4.1 Hook Map
Admin hooks:

- `admin_menu`
- `admin_init` (migration + save handlers)
- `wp_ajax_bw_brevo_test_connection`
- `woocommerce_admin_order_data_after_order_details`
- `wp_ajax_bw_brevo_order_refresh_status`
- `wp_ajax_bw_brevo_order_retry_subscribe`

Checkout hooks:

- `woocommerce_before_checkout_billing_form`
- `woocommerce_checkout_fields`
- `woocommerce_form_field`
- `woocommerce_checkout_update_order_meta`
- `woocommerce_checkout_order_processed` (timing `created`)
- `woocommerce_order_status_processing` (timing `paid`)
- `woocommerce_order_status_completed` (timing `paid`)
- `wp_enqueue_scripts`

Order admin AJAX hooks:

- `wp_ajax_bw_brevo_order_refresh_status` (read-only sync)
- `wp_ajax_bw_brevo_order_retry_subscribe` (write action)

## 4.2 Guest Checkout with Opt-in OFF
Sequence:

1. Checkout posts without consent.
2. Metadata saved:
   - `_bw_subscribe_newsletter = 0`
   - `_bw_subscribe_consent_source = checkout`
   - `_bw_brevo_subscribed = skipped`
3. Processor exits before Brevo subscription call.
4. No API subscription request is executed.

## 4.3 Guest Checkout with Opt-in ON
Sequence:

1. Checkout posts with consent.
2. Metadata saved:
   - `_bw_subscribe_newsletter = 1`
   - `_bw_subscribe_consent_at = <timestamp>`
   - `_bw_subscribe_consent_source = checkout`
3. Subscription execution depends on timing.

## 4.4 Timing Behavior: `paid` vs `created`

### `paid` (default)
Executed on:

- `woocommerce_order_status_processing`
- `woocommerce_order_status_completed`

### `created`
Executed on:

- `woocommerce_checkout_order_processed`

In both modes, execution path performs:

- Consent validation
- Settings validation
- Email validation
- Mode resolution (single vs DOI)
- Brevo API request
- State write + log event

## 4.5 Double Opt-in Enabled
When resolved mode is DOI:

- Preconditions:
  - `double_optin_template_id`
  - `double_optin_redirect_url`
- Call:
  - `send_double_opt_in(...)`
- On success:
  - `_bw_brevo_subscribed = pending`

## 4.6 Email Already Exists
Single opt-in path uses:

- `upsert_contact(..., updateEnabled=true)`

Result:

- Existing contact is updated, not duplicated.

## 4.7 Email Unsubscribed / Blocklisted
Current enforced behavior:

- Pre-check `get_contact($email)`
- If `emailBlacklisted` is true:
  - skip subscription
  - set `_bw_brevo_subscribed = skipped`
  - log reason with `bw-brevo`

Note:

- Current guard is explicit for blocklisted contacts.
- Additional Brevo opt-out semantics should be expanded in future hardening.

## 4.8 Order Admin Refresh (Read-Only Sync)
Triggered manually by `Refresh` button in order admin panel.

Sequence:

1. Validate capability (`manage_woocommerce` or `manage_options`) and nonce.
2. Resolve order and billing email.
3. Call `BW_Brevo_Client->get_contact($email)`.
4. Compute status from Brevo response:
   - contact not found -> `skipped`
   - contact blocklisted -> `skipped`
   - contact in configured list -> `subscribed`
   - contact exists but not in configured list -> `skipped`
5. Update status metas only:
   - `_bw_brevo_subscribed`
   - `_bw_brevo_error_last` (clear on success path)
   - `_bw_brevo_last_checked_at`
   - `_bw_brevo_contact_id` (if available)
6. Log action with source `bw-brevo` and `action=refresh`.

## 4.9 Order Admin Retry Subscribe (Write Action)
Triggered manually by `Retry subscribe` button in order admin panel.

Preconditions:

- `_bw_subscribe_newsletter = 1`
- Valid billing email
- Valid `api_key` and `list_id`
- Contact must not be blocklisted/unsubscribed

Execution:

1. Validate capability + nonce.
2. Re-check contact blocklist status.
3. Resolve channel mode (single/DOI via checkout override + general fallback).
4. Execute:
   - single opt-in -> `upsert_contact(...)`
   - DOI -> `send_double_opt_in(...)`
5. Update state and meta:
   - success single -> `subscribed`
   - success DOI -> `pending`
   - failure -> `error` + `_bw_brevo_error_last`
6. Update `_bw_brevo_last_checked_at`.
7. Log action with source `bw-brevo` and `action=retry`.

---

# 5. State Machine
Primary meta key:

```text
_bw_brevo_subscribed
```

Allowed values and semantics:

## 5.1 `pending`
Set when DOI request is accepted and user confirmation is pending.

## 5.2 `subscribed`
Set after successful single opt-in upsert.

## 5.3 `skipped`
Set when subscription is intentionally not performed.

Typical reasons:

- no explicit consent
- blocklisted contact

## 5.4 `error`
Set when validation or API execution fails.

Companion error key:

```text
_bw_brevo_error_last
```

Stores latest failure reason for troubleshooting.

Additional order admin keys:

```text
_bw_brevo_last_checked_at
_bw_brevo_contact_id
```

Usage:

- `_bw_brevo_last_checked_at`: timestamp of last manual sync/retry check.
- `_bw_brevo_contact_id`: cached Brevo contact identifier when available.

---

# 6. Logging Specification
Logger backend:

- WooCommerce logger (`wc_get_logger()`)

Source namespace:

```text
bw-brevo
```

Minimum log context fields:

- `order_id` when available
- `email` when available
- `context` (`checkout_guest` / `checkout_user`)
- `result` (`pending`, `subscribed`, `skipped`, `error`)
- `action` (`refresh`, `retry`, or checkout runtime action)

Logging expectations:

- Every terminal outcome must be logged.
- Error logs must include actionable reason.
- Skip logs must include explicit reason (consent missing, blocklisted, etc.).
- Order admin actions must always log outcome (`refresh`/`retry`) with order and email context.

---

# 7. Compliance & Safety
Mandatory safety rules:

- No subscription without explicit consent.
- No automatic resubscribe of blocklisted/unsubscribed contacts under `no_auto_resubscribe` policy.
- All runtime outcomes must be auditable through metadata and logs.
- Manual Retry must never bypass blocklist/unsubscribe constraints.

GDPR-oriented requirements:

- Consent language must be explicit (`label_text`, `privacy_text`).
- Default checked behavior must be used only where legally valid.
- Consent source and timestamp must be retained for audit.
- Preference and unsubscribe semantics from Brevo must be respected.

---

# 8. Migration & Legacy Notes
## 8.1 Migration Strategy
Legacy option:

```text
bw_checkout_subscribe_settings
```

Migration behavior:

- If new options are empty and legacy option exists:
  - map global fields to `bw_mail_marketing_general_settings`
  - map checkout fields to `bw_mail_marketing_checkout_settings`

## 8.2 Legacy Compatibility
Deprecated admin path:

- `Blackwork Site -> Checkout -> Subscribe`

Current behavior:

- Deprecated route shows notice and points to Mail Marketing page.
- Editable configuration is centralized in `Mail Marketing -> General/Checkout`.

Backward-compatibility note:

- Legacy option can still be read as fallback to avoid breaking old installations.

---

# 9. Future Roadmap
Channel expansion:

- Footer newsletter integration
- Popup newsletter integration
- My Account newsletter toggle
- OAuth login soft opt-in integration

Architecture hardening:

- Introduce centralized `BW_Subscribe_Service`
- Extend unsubscribe/blocklist guard coverage beyond current explicit blacklisted check
- Normalize state transition contract across channels
- Add automated integration tests for `created`, `paid`, and DOI paths

Observability improvements:

- Structured log conventions
- Diagnostics view for subscription events
- Cross-entity traceability (order/user/contact)

---

# 10. Change Log
## v1.2 - 2026-02-24
- Added shared service `BW_MailMarketing_Service` to centralize Brevo attribute mapping from order consent context.
- Standardized Brevo attributes across checkout/retry/bulk paths (`SOURCE`, `CONSENT_SOURCE`, `CONSENT_AT`, `CONSENT_STATUS`, `BW_ORIGIN_SYSTEM`, `BW_ENV`, `LAST_ORDER_ID`, `LAST_ORDER_AT`, `CUSTOMER_STATUS`).
- Added admin advanced payload summary row for Brevo attributes keys sent.
- Updated consent-gated admin UX (disabled retry/sync states and friendly no-consent notices).

## v1.1 - 2026-02-24
- Added WooCommerce Admin Order Newsletter Status panel.
- Added secure AJAX actions for order-level `Refresh` (read-only sync) and `Retry subscribe` (write action).
- Added new order meta fields `_bw_brevo_last_checked_at` and `_bw_brevo_contact_id`.

## v1.0 - 2026-02-24
- Initial Mail Marketing architecture refactor


---

## Source: `docs/40-integrations/cart-checkout-responsibility-matrix.md`

# Cart vs Checkout Responsibility Matrix

## 1) Domain Definitions

### Cart Domain (non-authoritative operational state)
Cart domain manages cart interaction surfaces and transition UX to checkout:
- cart quantity/remove interactions,
- cart popup and mini-cart behavior,
- floating cart trigger visibility,
- add-to-cart UI lifecycle.

Cart domain is operational and presentation-oriented. It is explicitly non-authoritative for payment, auth, provisioning, and order truth.

### Checkout Domain (payment orchestration authority)
Checkout domain manages checkout orchestration surfaces:
- checkout form interaction lifecycle,
- checkout summary refresh and cloned mobile summary behavior,
- checkout coupon interaction in checkout context,
- orchestration handoff to payment UI layer.

Checkout domain owns payment orchestration entrypoint behavior but does not define payment truth.

### Payment UI Layer
Payment UI layer manages payment method interaction and wallet UI coupling:
- payment method selection UI,
- wallet trigger visibility and hidden method payload fields,
- UPE/custom selector coexistence handling.

Payment UI layer can mutate payment selection state, but authoritative payment truth remains callback/webhook reconciliation.

### Shared Lifecycle Utilities
Shared utility scripts provide non-business lifecycle support across Cart and Checkout:
- loader/progress/skeleton behavior,
- generic event-driven UX glue.

Shared utilities must remain non-authoritative and non-mutating for business authority state.

## 2) JS File Classification

Each file is assigned to exactly one category.

| JS File | Category |
|---|---|
| `assets/js/bw-cart.js` | Cart |
| `includes/modules/header/assets/js/bw-navshop.js` | Cart |
| `assets/js/bw-price-variation.js` | Cart |
| `cart-popup/assets/js/bw-cart-popup.js` | Cart |
| `assets/js/bw-checkout.js` | Checkout |
| `assets/js/bw-checkout-notices.js` | Checkout |
| `assets/js/bw-payment-methods.js` | Payment |
| `assets/js/bw-google-pay.js` | Payment |
| `assets/js/bw-apple-pay.js` | Payment |
| `assets/js/bw-stripe-upe-cleaner.js` | Payment |
| `assets/js/bw-premium-loader.js` | Shared Utility |

Explicit required classification:
- `bw-premium-loader.js` -> Shared Utility
- `bw-cart-popup.js` -> Cart
- `bw-checkout.js` -> Checkout
- `bw-payment-methods.js` -> Payment
- wallet JS (`bw-google-pay.js`, `bw-apple-pay.js`) -> Payment

## 3) Responsibility Table

| Component | May Mutate Cart State? | May Trigger Checkout Refresh? | May Mutate Payment Selection? | May Define Authority? | Domain Owner |
|---|---|---|---|---|---|
| `assets/js/bw-cart.js` | Yes | No | No | No | Cart |
| `includes/modules/header/assets/js/bw-navshop.js` | No | No | No | No | Cart |
| `assets/js/bw-price-variation.js` | Yes (add-to-cart flow trigger) | No | No | No | Cart |
| `cart-popup/assets/js/bw-cart-popup.js` | Yes | Yes (`update_checkout`) | No | No | Cart |
| `assets/js/bw-checkout.js` | Yes (checkout cart rows/coupon in checkout scope) | Yes | No | No | Checkout |
| `assets/js/bw-checkout-notices.js` | No | No | No | No | Checkout |
| `assets/js/bw-payment-methods.js` | No | Indirect (reacts to refresh lifecycle) | Yes | No | Payment |
| `assets/js/bw-google-pay.js` | No | Yes (`update_checkout`) | Yes | No | Payment |
| `assets/js/bw-apple-pay.js` | No | Yes (`update_checkout`) | Yes | No | Payment |
| `assets/js/bw-stripe-upe-cleaner.js` | No | No | Yes (UI cleanup effect) | No | Payment |
| `assets/js/bw-premium-loader.js` | No | No (listens only) | No | No | Shared Utility |

Normative interpretation:
- "May Define Authority?" is `No` for all JS components.
- Business authority is defined only by authoritative server-side reconciliation and ADR doctrine.

## 4) Hard Prohibitions

- Cart MUST NOT mutate payment selection or payment truth.
- Cart MUST NOT define authority state.
- Checkout MUST NOT redefine cart business truth outside checkout orchestration scope.
- Checkout MUST NOT infer payment truth from UI success or redirect artifacts.
- Payment UI layer MUST NOT define payment authority truth.
- Shared utilities MUST NOT mutate business authority state.
- Shared utilities MUST remain presentation-only lifecycle helpers.

## 4.1) Directional Flow Constraint

Operational flow direction is strictly:

`Cart -> Checkout -> Payment UI`

Reverse authority flow is prohibited.

- Cart MAY emit operational update events.
- Checkout MAY orchestrate payment selection.
- Payment UI MAY reflect or adjust payment selection state.
- No downstream layer MAY redefine upstream operational or business state.

## 4.2) Allowed Cross-Domain Interactions

The following interactions are explicitly permitted:

- Cart MAY trigger `update_checkout` after cart mutation.
- Checkout MAY re-render order summary based on cart state.
- Payment UI MAY react to `updated_checkout` lifecycle events.
- Shared utilities MAY listen to lifecycle events for UX purposes.

These interactions remain non-authoritative and MUST NOT mutate business truth.

## 5) Fragment Refresh Classification

### Event emission layer
- Cart layer may emit fragment refresh triggers for cart synchronization (`wc_fragment_refresh`) in cart-popup operations.
- Cart and checkout flows may emit `update_checkout` when checkout recomputation is needed.

`update_checkout` clarification:
- `update_checkout` is a recomputation trigger only.
- It MUST NOT define payment authority.
- It MUST NOT confirm payment truth.
- It MUST NOT mutate entitlement state.
- It MUST NOT override cart business truth.
- It is a recalculation and DOM refresh instruction only.

### State reflection layer
- Cart reflects cart visualization state (badge, popup list, mini-cart-facing UX).
- Checkout reflects checkout-form/order-summary state after `updated_checkout`.
- Payment UI reflects selected payment method and wallet visibility based on refreshed DOM and eligibility.

### State reconciliation layer
- Reconciliation is NOT owned by cart/checkout/payment JS.
- Authoritative reconciliation belongs to local server-side business logic and callback/webhook processing.
- JS layers consume reflected state and must remain non-authoritative.

## 5.1) Mobile Order Summary Duplication Pattern

The mobile order summary clone is classified as a Presentation Duplication Pattern.

Rules:
- Both DOM instances are presentation-only.
- Neither instance defines authority.
- JS binding MUST avoid double-binding on the same node.
- Clone logic MUST NOT introduce business mutation side effects.

## 6) Cross-References

- ADR-001 Selector Authority: `../60-adr/ADR-001-upe-vs-custom-selector.md`
- ADR-002 Authority Hierarchy: `../60-adr/ADR-002-authority-hierarchy.md`
- ADR-003 Callback Anti-Flash Model: `../60-adr/ADR-003-callback-anti-flash-model.md`
- ADR-005 Claim Idempotency Rule: `../60-adr/ADR-005-claim-idempotency-rule.md`
- ADR-006 Provider Switch Model: `../60-adr/ADR-006-provider-switch-model.md`

Related structural references:
- JS analysis source: `../50-ops/audits/cart-checkout-js-structure-analysis.md`
- Checkout architecture boundary: `../30-features/checkout/checkout-architecture-map.md`
- Payments architecture map: `./payments/payments-architecture-map.md`


---

## Source: `docs/40-integrations/payments/README.md`

# Payments

## Files
- [payments-architecture-map.md](payments-architecture-map.md): official payments integration architecture map.
- [payments-overview.md](payments-overview.md): central payments architecture overview.
- [gateway-google-pay-guide.md](gateway-google-pay-guide.md): Google Pay gateway guide.
- [gateway-klarna-guide.md](gateway-klarna-guide.md): Klarna gateway guide.
- [gateway-apple-pay-guide.md](gateway-apple-pay-guide.md): Apple Pay gateway guide.
- [payment-selector-map.md](payment-selector-map.md): payment UI selector map.
- [payment-test-checklist.md](payment-test-checklist.md): payment regression and validation checklist.


---

## Source: `docs/40-integrations/payments/gateway-apple-pay-guide.md`

# Gateway Apple Pay Guide

## Related Docs
- Global architecture: `payments-overview.md`
- Google Pay gateway: `gateway-google-pay-guide.md`
- Klarna gateway: `gateway-klarna-guide.md`

## Overview
This document tracks the BlackWork custom Apple Pay gateway architecture and current behavior.

- Gateway id: `bw_apple_pay`
- Provider: Stripe PaymentIntents (`payment_method_types[]=card`, Apple Pay via wallet availability)
- Checkout flow: WooCommerce standard (`checkout -> return -> webhook confirmation`)
- Webhook endpoint: `/?wc-api=bw_apple_pay`
- Source of truth for final payment completion: Stripe webhook (`payment_intent.succeeded`)

## Current Integration

### Gateway Class
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-apple-pay-gateway.php`
- Base class: `BW_Abstract_Stripe_Gateway`
- Supports:
  - `products`
  - `refunds`
- Order button text:
  - `Place order with Apple Pay`

### WooCommerce Registration
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Hook: `woocommerce_payment_gateways`
- Registered class: `BW_Apple_Pay_Gateway`

### Admin Tab (BlackWork Site > Checkout > Apple Pay)
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`
- Sub-tab: `apple-pay`
- Fields:
  - `bw_apple_pay_enabled`
  - `bw_apple_pay_express_helper_enabled` (checkbox: helper scroll on unavailable Apple Pay)
  - `bw_apple_pay_publishable_key`
  - `bw_apple_pay_secret_key`
  - `bw_apple_pay_statement_descriptor`
  - `bw_apple_pay_webhook_secret`
- Live-only UI (no test mode fields in tab).

### Admin Scripts (Connection + Domain checks)
- JS file: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/js/bw-apple-pay-admin.js`
- AJAX actions:
  - `bw_apple_pay_test_connection`
  - `bw_apple_pay_verify_domain`
- Connection check validation:
  - `sk_live_` required for secret key.
  - `pk_live_` required when publishable key is provided.
  - Uses Stripe `GET /v1/account`.
- Domain check:
  - Calls Stripe endpoint for payment method domains and checks current site host.

## Key Resolution Strategy

Apple Pay does not require separate Stripe keys at account level, but this plugin keeps dedicated Apple fields for admin UX.

Runtime behavior in gateway:
- Live secret key resolution:
  1. `bw_apple_pay_secret_key`
  2. fallback `bw_google_pay_secret_key`
- Live publishable key resolution:
  1. `bw_apple_pay_publishable_key`
  2. fallback `bw_google_pay_publishable_key`

Reference methods:
- `resolve_live_secret_key()`
- `resolve_live_publishable_key()`

## Checkout Rendering

Template sections involved:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/checkout/payment.php`

Key elements:
- `#bw-apple-pay-accordion-placeholder`
- `#bw-apple-pay-button-wrapper`
- `#bw-apple-pay-button`
- `.bw-apple-pay-info`

Rendered states:
1. Not enabled/not configured: admin guidance message.
2. Initializing: loading message in accordion.
3. Available: custom Apple Pay button appears.
4. Unavailable + helper enabled: show only helper text (no "Apple Pay unavailable" title) and use Apple button to scroll to Express Checkout.
5. Unavailable + helper disabled: show explicit unavailable message and keep Woo fallback path.

## Frontend JS Flow

- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-apple-pay.js`

Main behavior:
1. Initialize Stripe with live publishable key.
2. Build `paymentRequest`.
3. Run `canMakePayment()`.
4. Consider available only if:
   - `result && result.applePay === true`
5. Maintain explicit state machine:
   - `idle`
   - `method_selected`
   - `native_available`
   - `native_unavailable_helper`
   - `processing`
   - `error`
6. Handle events:
   - `paymentmethod` -> submit Woo checkout AJAX with `bw_apple_pay_method_id`
   - `cancel` -> show notice, clear hidden method id, keep checkout retry path
7. Re-run state after:
   - `payment_method` change
   - WooCommerce `updated_checkout`

All `console.log` / `console.info` calls are guarded by `BW_APPLE_PAY_DEBUG` or
`bwApplePayParams.adminDebug` and produce no output in production.

## Shared Wallet UI Orchestration

- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-payment-methods.js`

Responsibilities:
- Keep one active payment action at a time.
- Sync custom wallet buttons with selected gateway.
- Normalize accordion open/close behavior across Stripe/PayPal/Google/Klarna/Apple rows.

Important note:
- Apple Pay row behavior depends on this shared sync layer; regressions in shared selectors can affect Apple Pay open state and CTA visibility.

## Payment Processing (Server)

In `process_payment($order_id)`:
1. Validate order and selected payment method.
2. Validate `bw_apple_pay_method_id` exists.
3. Create PaymentIntent with:
   - `payment_method_types[]=card`
   - `payment_method=<pm_id from Apple Pay sheet>`
   - `confirm=true`
   - `return_url`
4. Save PI references and transaction id.
5. Handle statuses:

| PI status | Order status set | Rationale |
|---|---|---|
| `succeeded` \| `processing` | `on-hold` | Wait for webhook confirmation |
| `requires_action` | `pending` + redirect | 3DS auth required |
| `requires_payment_method` \| `canceled` | error notice only | Payment not completed |

> `on-hold` is the canonical status for "payment attempted, awaiting webhook".
> `pending` is only set when 3DS authentication is required (user redirected).
   - default -> generic failure notice

## Webhook and Hardening

Endpoint:
- `https://blackwork.pro/?wc-api=bw_apple_pay`

Handled via base gateway class:
- Signature verification (`Stripe-Signature`, timestamp tolerance, multi-v1 support).
- Anti-conflict guard:
  - order must exist
  - `order->get_payment_method() === 'bw_apple_pay'`
- Idempotency:
  - dedup with rolling event list in order meta.
- PI consistency check:
  - ignore events if PI id mismatches stored order PI id.
- Event handling:
  - `payment_intent.succeeded` -> complete payment once
  - `payment_intent.payment_failed` -> fail once if not already paid
  - `payment_intent.processing` -> keep pending/on-hold with note

## Order Meta Keys

- `_bw_apple_pay_pi_id`
- `_bw_apple_pay_mode`
- `_bw_apple_pay_pm_id`
- `_bw_apple_pay_created_at`
- `_bw_apple_pay_processed_events`
- `_bw_apple_pay_refund_ids`
- `_bw_apple_pay_last_refund_id`

## Return/Cancel UX Rules

Shared wallet return router:
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`

Behavior:
- If `redirect_status=failed|canceled` on wallet gateways (`bw_klarna`, `bw_google_pay`, `bw_apple_pay`):
  - do not complete payment
  - show checkout notice
  - redirect user back to checkout for retry
- If order already paid by webhook:
  - keep normal thank-you flow.

## Known Issues and Debug Notes (Current)

1. Apple Pay availability is environment-dependent:
- Requires Safari + Apple device + eligible card in Wallet + HTTPS + Stripe domain verification.
- On non-supported environments, helper mode scrolls user to top Express Checkout.

2. Shared accordion/button sync can impact Apple Pay UX:
- If shared script logic regresses, Apple row can appear selected while another gateway controls CTA.
- First file to inspect in these cases:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-payment-methods.js`

3. If Apple Pay stays in initializing state:
- Check browser console for:
  - `[BW Apple Pay] canMakePayment: ...`
- Check Stripe.js loaded and `bwApplePayParams.publishableKey` present.
- Check domain verification in Stripe dashboard and admin tab domain checker.

## Quick Production Checklist

1. Admin tab
- `Enable Gateway` checked.
- Live keys valid (`pk_live_`, `sk_live_`) or fallback live keys available.
- Webhook secret saved (`whsec_...`).
- Connection check returns success.
- Domain verification check returns success.

2. Checkout
- Apple row visible and selectable.
- Available device: custom Apple Pay button appears.
- Unavailable device (helper on): text-only helper guidance and Apple button scroll action.

3. Webhook
- `payment_intent.succeeded` finalizes order.
- Replayed events do not duplicate side effects.

## Next Improvements (Planned)

1. Add a dedicated `Gateway Apple Pay Guide` section in `/docs/PAYMENTS.md` cross-index.
2. Add a compact admin troubleshooting block listing exact failing prerequisites.
3. Keep shared wallet orchestration tests with Google/Klarna/Apple together to prevent cross-gateway regressions.


---

## Source: `docs/40-integrations/payments/gateway-google-pay-guide.md`

# Gateway Google Pay Guide

## Related Docs
- Global architecture: `payments-overview.md`
- Klarna gateway: `gateway-klarna-guide.md`
- Apple Pay gateway: `gateway-apple-pay-guide.md`

## Scope
This guide documents the custom WooCommerce gateway `bw_google_pay` (Stripe integration), with the current architecture and the real issues/fixes already applied during hardening and UX stabilization.

Goals:
- Keep production-safe behavior.
- Avoid conflicts with `wc_stripe` and PayPal gateways.
- Preserve WooCommerce standard flow (checkout -> order -> thank you -> emails).
- Keep a practical debugging runbook for future regressions.

---

## High-Level Architecture

### Gateways in checkout
- Credit/Debit Card: handled by `wc_stripe`.
- PayPal: handled by separate PayPal plugin.
- Google Pay: handled by custom gateway `bw_google_pay`.

### Core files
- Gateway class:
  - `includes/Gateways/class-bw-google-pay-gateway.php`
- Checkout script:
  - `assets/js/bw-google-pay.js`
- Checkout wallets orchestrator (shared):
  - `assets/js/bw-payment-methods.js`
- Checkout enqueue/localization:
  - `woocommerce/woocommerce-init.php`
- Admin settings + connection test:
  - `admin/class-blackwork-site-settings.php`
  - `admin/js/bw-google-pay-admin.js`

### Most relevant runtime files for current fixes
- `assets/js/bw-google-pay.js`
  - Google Pay init, canMakePayment, button mount/update, unavailable UI.
- `assets/js/bw-payment-methods.js`
  - Shared wallet/checkout button synchronization and accordion state normalization.
- `woocommerce/woocommerce-init.php`
  - Enqueue safety for Google Pay frontend script.

---

## Runtime Flow

## 1) Frontend availability (Google Pay)
- JS creates Stripe `paymentRequest(...)`.
- Calls `paymentRequest.canMakePayment()`.
- If available (`result && bwCanShowGooglePay(result)`):
  - Shows custom Google Pay button.
  - Keeps gateway selectable.
- If unavailable:
  - Renders unavailable state (message + Google Wallet link).
  - Hides Google Pay informational block (`icon + "After clicking Google Pay..."`) to avoid UX noise.
  - Leaves only unavailable notice/CTA as primary content in the accordion.
  - Prevents misleading mixed states after checkout refresh.

Main function: `initGooglePay()` in `assets/js/bw-google-pay.js`.

## 2) Checkout submit for Google Pay
- On Google Pay approval (`paymentmethod` event), JS posts Woo checkout AJAX with hidden field:
  - `bw_google_pay_method_id = pm_xxx`.
- Backend `process_payment($order_id)` creates Stripe PaymentIntent and handles status.

## 3) PaymentIntent creation (Stripe API)
- Endpoint: `POST https://api.stripe.com/v1/payment_intents`
- Important payload:
  - `amount`, `currency`, `payment_method`, `payment_method_types[]=card`, `confirm=true`, `return_url`.
  - metadata:
    - `wc_order_id`
    - `order_id` (retrocompat)
    - `bw_gateway=bw_google_pay`
    - `site_url`
    - `mode=test|live`
- Idempotency:
  - Header `Idempotency-Key: bw_gpay_<order_id>_<hash(pm_id)>`.

## 4) Webhook handling
- Endpoint:
  - `https://blackwork.pro/?wc-api=bw_google_pay`
- Supported events:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
- Signature:
  - Custom Stripe-Signature verification (HMAC SHA-256, timestamp tolerance 300s, `hash_equals`).
- Anti-conflict:
  - Process only if:
    - order exists
    - order payment method is exactly `bw_google_pay`
    - optional metadata `bw_gateway` matches
- PI consistency:
  - If `_bw_gpay_pi_id` exists and differs from incoming PI id, ignore event.
- Idempotency:
  - Save processed `event_id` list in `_bw_gpay_processed_events` (rolling last 20).
  - Ignore duplicate events.

---

## Admin Configuration Fields

Stored options:
- `bw_google_pay_enabled` (0/1)
- `bw_google_pay_test_mode` (0/1)
- `bw_google_pay_publishable_key` (live pk)
- `bw_google_pay_secret_key` (live sk)
- `bw_google_pay_test_publishable_key` (test pk)
- `bw_google_pay_test_secret_key` (test sk)
- `bw_google_pay_statement_descriptor`
- `bw_google_pay_webhook_secret` (live whsec)
- `bw_google_pay_test_webhook_secret` (test whsec)

Gateway enablement requires both:
- BlackWork setting enabled (`bw_google_pay_enabled=1`)
- WooCommerce Payments method enabled (`woocommerce_bw_google_pay_settings[enabled]=yes`)

---

## Order Meta Used by Gateway

Written at PI creation:
- `_bw_gpay_pi_id`
- `_bw_gpay_mode` (`test` or `live`)
- `_bw_gpay_pm_id`
- `_bw_gpay_created_at` (unix timestamp)

Written by webhook dedup:
- `_bw_gpay_processed_events` (array of Stripe `evt_...`, max 20)

---

## Security and Hardening Rules

## Mandatory safeguards in place
- Webhook signature verification with timestamp tolerance.
- Webhook anti-conflict with payment method check.
- Webhook dedup by `event_id`.
- PI mismatch protection against wrong order updates.
- Stripe API error handling (connection + non-2xx + API error object).
- Idempotency-Key for PaymentIntent creation.
- Safe frontend fallback when Google Pay is unavailable.

## Data minimization
- Do not log full payloads or full secrets.
- Log only operational identifiers when needed:
  - order id, event id, PI id, status.

---

## Order State Rules

### `process_payment()` return statuses

| PI status | Order status set | Rationale |
|---|---|---|
| `succeeded` | `on-hold` | Return received; wait for webhook to finalize |
| `processing` | `on-hold` | Async capture; wait for webhook |
| `requires_action` | error notice only | 3DS required; user redirected to auth |
| `requires_payment_method` / `canceled` | error notice only | Payment not completed |

> **Important:** `on-hold` is the correct WooCommerce status for "payment attempted,
> awaiting external confirmation". `pending` means "order created, payment not yet tried".
> The distinction matters for order automation, Brevo subscribe timing, and admin dashboards.

### Webhook state transitions (`BW_Abstract_Stripe_Gateway`)

| Stripe event | Final order status |
|---|---|
| `payment_intent.succeeded` | `payment_complete()` (processing/completed) |
| `payment_intent.payment_failed` | `failed` |

### Already-paid guard
If `$order->is_paid()` is true when the webhook arrives, no side effects occur.

---

## Frontend UX Rules (without changing accordion structure)

- If Google Pay not available on device/browser/account:
  - keep explanatory message
  - provide wallet link
  - hide Google info panel (`.bw-google-pay-info`)
  - keep checkout action state coherent (no wrong button shown)
- If available:
  - show Google Pay button
  - hide generic Place order while Google Pay method is selected

---

## Resolved Incidents (Important)

These were real regressions fixed in code and should be considered "known-good baseline" behavior now.

## Incident 1: Stuck on "Initializing Google Pay..."
Symptom:
- Accordion stayed in loading state.
- Google Pay button never appeared.
- Console showed Stripe error about `paymentRequest.total.amount`.

Root cause:
- `paymentRequest.total.amount` sometimes arrived in an invalid format for Stripe PaymentRequest (subunit mismatch / non-normalized value).

Applied fix:
- Added strict normalization helper in `assets/js/bw-google-pay.js`:
  - `bwNormalizeCents(value, fallback)`
- Applied both at:
  - init (`initialCents`)
  - checkout updates (`paymentRequest.update(...)` on `updated_checkout`)

Reference console error:
- `Invalid value for paymentRequest(): total.amount should be a positive amount in the currency's subunit`

## Incident 2: Google Pay row visible but JS not running
Symptom:
- Gateway row visible in checkout.
- JS behavior missing (no proper init / stale placeholder).

Root cause:
- Script enqueue was too strict and skipped in some combinations of gateway visibility/settings.

Applied fix:
- Hardened enqueue in `woocommerce/woocommerce-init.php`:
  - enqueue when BlackWork setting enabled OR gateway appears in Woo available gateways.

## Incident 3: Mixed checkout action states (wrong button shown)
Symptom:
- Google Pay selected but generic `Place order` visible.
- Intermittent mismatch after `updated_checkout`.

Root cause:
- UI state drift between wallet state and WooCommerce refreshed DOM.

Applied fix:
- Shared sync hardening in `assets/js/bw-payment-methods.js` (`bwSyncCheckoutActionButtons`).
- Google-side re-sync in `assets/js/bw-google-pay.js` after `updated_checkout`.

## Incident 4: Unavailable view too noisy
Symptom:
- Unavailable warning was shown together with generic “after clicking” info + icon block.

Applied fix:
- In unavailable state, force-hide `.bw-google-pay-info`.
- Keep only unavailable message + CTA.

## Incident 5: Stale "checking" state after checkout updates
Symptom:
- Temporary stale placeholder after Woo AJAX updates.

Applied fix:
- Added stale-checking guard and forced unavailable rerender when needed during refresh cycle.

---

## Known Production Pitfalls and Diagnosis

## 1) "The key is valid but belongs to Test Mode" while in live
Checklist:
- Confirm `Test Mode` checkbox state in BlackWork settings.
- Verify live keys actually start with:
  - `pk_live_`
  - `sk_live_`
- Ensure live and test keys are not accidentally swapped in fields.
- Re-save settings, then re-run connection test.

## 2) Google Pay method shows unavailable
Possible causes:
- No compatible card in Google Wallet for current account.
- Browser/device/account not eligible.
- Gateway disabled in WooCommerce Payments.
- Missing publishable key in active mode.

## 3) Checkout shows placeholder "Initializing Google Pay..."
Likely causes:
- JS did not initialize (missing Stripe script or localized params).
- Gateway disabled at WooCommerce payment method level.
- Invalid `paymentRequest.total.amount` formatting/subunit value.

Quick check:
- Open console and verify there is no `Invalid value for paymentRequest()` error.
- Verify `canMakePayment` log/result is not throwing and state moves out of checking.

## 4) Duplicate webhook delivery
- Expected behavior from Stripe.
- Must be harmless due to `_bw_gpay_processed_events` dedup.

## 5) Shared wallet UI synchronization
Google Pay visibility is synchronized with shared checkout wallet logic:
- Single source of truth in `assets/js/bw-payment-methods.js` (`bwSyncCheckoutActionButtons`).
- Prevents mixed UI states (wallet + wrong submit button shown together).
- Keeps card title normalization stable (`Credit / Debit Card`).

---

## Test Plan (TEST and LIVE)

## Functional
- Successful payment -> thank you page + Woo email.
- Failed payment -> order moves to failed with single note.

## Webhook robustness
- Replay same `evt_...` -> no duplicate side effects.
- Send failed event after success -> ignored.
- Send event for non-`bw_google_pay` order -> ignored safely.

## Availability UX (current behavior)
- canMakePayment true -> custom Google Pay button visible, generic place-order hidden when selected.
- canMakePayment false -> unavailable card shown with `Open Google Wallet` CTA; the extra Google info panel is hidden.
- after `updated_checkout` -> state remains coherent (no return to stale "initializing" without cause).

## Coexistence
- `wc_stripe` card checkout still works unchanged.
- PayPal checkout still works unchanged.

---

## Extension Strategy (Future Wallets)

To scale to Apple Pay/Klarna without duplicated logic:
- Extract shared Stripe PI + webhook logic into a reusable service layer.
- Keep per-wallet adapters only for:
  - frontend capability checks
  - wallet-specific button/render events
- Standardize PI metadata with:
  - `bw_gateway` values per adapter (`bw_google_pay`, `bw_apple_pay`, etc.)

---

## Quick Ops Checklist

- Enable BlackWork Google Pay setting.
- Enable WooCommerce payment method "Google Pay (BlackWork)".
- Verify active mode and key family coherence (live with `pk_live`/`sk_live`, test with `pk_test`/`sk_test`).
- Verify `paymentRequest.total.amount` is emitted as valid currency subunit integer.
- If unavailable, confirm expected capability limits (device/browser/account/wallet card) before treating as bug.
- Set keys for the active mode only.
- Configure webhook endpoint and matching `whsec` for active mode.
- Run connection test in active mode.
- Test one real/expected flow before going live.

---

## Quick Debug Snippet (Console)
Use this to verify frontend wiring in a live checkout session:

```js
(() => {
  console.log('typeof bwGooglePayParams =', typeof window.bwGooglePayParams);
  console.log('bwGooglePayParams =', window.bwGooglePayParams || null);
  console.log('typeof Stripe =', typeof window.Stripe);
  console.log('BW_GPAY_AVAILABLE =', window.BW_GPAY_AVAILABLE);
  const scripts = [...document.scripts]
    .map(s => s.src)
    .filter(src => src.includes('bw-google-pay') || src.includes('bw-payment-methods') || src.includes('stripe.com/v3'));
  console.log('loaded scripts =', scripts);
})();
```


---

## Source: `docs/40-integrations/payments/gateway-klarna-guide.md`

# Gateway Klarna Guide

## Related Docs
- Global architecture: `payments-overview.md`
- Google Pay gateway: `gateway-google-pay-guide.md`
- Apple Pay gateway: `gateway-apple-pay-guide.md`

## Overview
This document tracks the BlackWork custom Klarna gateway architecture and settings.

- Gateway id: `bw_klarna`
- Provider: Stripe PaymentIntents (`payment_method_types[]=klarna`)
- Checkout flow: WooCommerce standard (`checkout -> redirect/auth -> thank you`)
- Webhook endpoint: `/?wc-api=bw_klarna`
- Source of truth for final payment completion: Stripe webhook (`payment_intent.succeeded`)

## Current Integration

### Gateway Class
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/Gateways/class-bw-klarna-gateway.php`
- Base class: `BW_Abstract_Stripe_Gateway`
- Supports:
  - `products`
  - `refunds`

### WooCommerce Registration
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`
- Hook: `woocommerce_payment_gateways`
- Registered class: `BW_Klarna_Gateway`

### Admin Tab (BlackWork Site > Checkout > Klarna Pay)
- File: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/class-blackwork-site-settings.php`
- New sub-tab: `klarna-pay`
- Fields:
  - `bw_klarna_enabled`
  - `bw_klarna_publishable_key`
  - `bw_klarna_secret_key`
  - `bw_klarna_statement_descriptor`
  - `bw_klarna_webhook_secret`

### Connection Test (Live)
- JS: `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/admin/js/bw-klarna-admin.js`
- AJAX action: `bw_klarna_test_connection`
- Handler: `bw_klarna_test_connection_ajax_handler`
- Validation:
  - Secret key must start with `sk_live_`
  - Publishable key must start with `pk_live_`
  - API check: `GET /v1/account`

## PaymentIntent Flow

In `process_payment()`:
1. Validate WooCommerce order and selected payment method.
2. Validate live secret key exists.
3. Build Klarna billing details from order billing fields.
4. Create PaymentIntent with:
   - `payment_method_types[]=klarna`
   - `payment_method_data[type]=klarna`
   - `confirm=true`
   - `return_url`
   - metadata (`wc_order_id`, `bw_gateway`, `site_url`, `mode`)
5. Save meta (`_bw_klarna_pi_id`, mode, timestamps) and transaction id.
6. Handle PI status:

| PI status | Order status set | Rationale |
|---|---|---|
| `succeeded` | `on-hold` | Wait for webhook confirmation |
| `processing` | `on-hold` | Async capture; wait for webhook |
| `requires_action` | `pending` + redirect | Klarna auth redirect required |
| `requires_payment_method` \| `canceled` | error notice only | Payment not completed |

> `on-hold` is the canonical status for "payment attempted, awaiting webhook".
> The final order completion happens via `payment_intent.succeeded` webhook only.

## Return / Cancel / Failed UX

Global return router is handled in:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/woocommerce-init.php`

Behavior:
- If return has `redirect_status=failed|canceled` and order payment method is wallet (`bw_klarna`, `bw_google_pay`, `bw_apple_pay`):
  - do not complete payment
  - add checkout notice: payment canceled/not completed
  - redirect user back to checkout (no thank-you success flow)
  - keep cart/session usable for retry
- If order is already paid (webhook arrived first), normal thank-you flow is preserved.

## Webhook and Hardening

Handled via base class:
- Signature verification (`Stripe-Signature`, timestamp tolerance, `v1` match)
- Anti-conflict guard:
  - ignore if order missing
  - ignore if `order->get_payment_method() !== 'bw_klarna'`
- PI consistency check vs order meta
- Idempotency dedup (rolling event history)
- Event mapping:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
- Only webhook success should finalize payment state for production-safe consistency.

## Refunds

Handled by base class `process_refund()`:
- Supports partial/full refunds
- Uses order mode + secret key resolution
- Stripe idempotency key
- Saves refund meta:
  - `_bw_klarna_refund_ids`
  - `_bw_klarna_last_refund_id`

## Order Meta Keys

- `_bw_klarna_pi_id`
- `_bw_klarna_mode`
- `_bw_klarna_pm_id`
- `_bw_klarna_created_at`
- `_bw_klarna_processed_events`
- `_bw_klarna_refund_ids`
- `_bw_klarna_last_refund_id`

## UI Notes

- Checkout label: `Klarna - Flexible Payments`
- Order button text: `Place order with Klarna`
- Payment fields text:
  - Ready: `You'll be redirected to Klarna - Flexible payments to complete your purchase.`
  - Not configured: `Klarna is not configured. Activate Klarna (BlackWork) in WooCommerce > Settings > Payments.`
- Icon rendered as Klarna logo/chip in the payment row, styled in:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-payment-methods.css`

## Klarna Market Requirements

Klarna via Stripe is only available in specific countries/currencies.
If the WooCommerce store uses an unsupported country/currency, Klarna will not appear in
the checkout (Stripe silently excludes it from `payment_method_types`).

**Supported countries (Stripe Klarna):** AT, BE, DE, DK, ES, FI, FR, GB, IE, IT, NL, NO, SE, US (and others per Stripe docs).
**Supported currencies:** EUR, GBP, DKK, NOK, SEK, USD (country-dependent).

If your store is in an unsupported combination:
- Klarna gateway will be registered but Stripe will reject PI creation.
- The error surfaces as a WooCommerce checkout notice.
- There is currently no admin-level pre-flight warning for this case.

Reference: https://stripe.com/docs/payments/klarna#supported-currencies

## Open Items

1. Add test mode fields for Klarna to allow staging environment testing.
2. Add admin warning if Stripe account country/currency does not support Klarna.
3. Add pre-flight check in `process_payment()` that validates currency against Klarna's supported list before creating the PI.


---

## Source: `docs/40-integrations/payments/payment-selector-map.md`

# Payment Section Selector Map

## Overview
Stable selector map for WooCommerce checkout payment section.
Avoids auto-generated Elementor classes and ensures cascade specificity.

## Scope Strategy
- **Primary Scope**: `body.woocommerce-checkout`
- **Fallback Scope**: `.woocommerce-checkout-payment`
- **Avoid**: Elementor classes (`.elementor-*`, `.e-*`)

---

## DOM Structure & Selectors

### Level 1: Container
```html
<div id="payment" class="woocommerce-checkout-payment">
```

**Stable Selectors:**
- `body.woocommerce-checkout #payment`
- `body.woocommerce-checkout .woocommerce-checkout-payment`

**Specificity**: `(0,1,2)` - Wins over most theme CSS

---

### Level 2: Section Title & Subtitle

#### Title
```html
<h2 class="bw-payment-section-title">Payment</h2>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-section-title`
- `.woocommerce-checkout-payment .bw-payment-section-title`

**Specificity**: `(0,1,2)` or `(0,0,2)`

#### Subtitle
```html
<p class="bw-payment-section-subtitle">All transactions are secure...</p>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-section-subtitle`

---

### Level 3: Payment Methods List

```html
<ul class="bw-payment-methods wc_payment_methods payment_methods methods">
```

**Stable Selectors:**
- `body.woocommerce-checkout ul.bw-payment-methods`
- `body.woocommerce-checkout .wc_payment_methods`

**Specificity**: `(0,1,2)`

---

### Level 4: Individual Payment Method

```html
<li class="bw-payment-method wc_payment_method payment_method_stripe" data-gateway-id="stripe">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method`
- `body.woocommerce-checkout .wc_payment_method`

**States:**
- **Selected**: `.bw-payment-method.is-selected`
- **Has checked radio**: `.bw-payment-method:has(input[type="radio"]:checked)`

**Specificity**: `(0,1,2)` for base, `(0,2,2)` for states

---

### Level 5: Payment Method Header (Radio + Label)

#### Header Container
```html
<div class="bw-payment-method__header">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__header`

**States:**
- **Hover**: `.bw-payment-method__header:hover`
- **Focus-within**: `.bw-payment-method__header:focus-within`

#### Radio Button
```html
<input type="radio" class="input-radio" name="payment_method" id="payment_method_stripe">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__header input[type="radio"]`
- `body.woocommerce-checkout input[name="payment_method"]`

**States:**
- **Checked**: `input[type="radio"]:checked`
- **Focus**: `input[type="radio"]:focus`
- **Hover**: `input[type="radio"]:hover`

**Specificity**: `(0,1,3)` for scoped input

#### Label
```html
<label for="payment_method_stripe" class="bw-payment-method__label">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__label`

---

### Level 6: Payment Method Title & Icons

#### Title
```html
<span class="bw-payment-method__title">Credit card</span>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__title`

#### Icons Container
```html
<span class="bw-payment-method__icons">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__icons`

#### Individual Icon
```html
<span class="bw-payment-icon"><img src="..." alt="Visa"></span>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-icon`
- `body.woocommerce-checkout .bw-payment-icon img`

#### More Icons Badge
```html
<span class="bw-payment-icon bw-payment-icon--more">
  <span class="bw-payment-icon__badge">+2</span>
  <span class="bw-payment-icon__tooltip" id="tooltip-stripe">...</span>
</span>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-icon--more`
- `body.woocommerce-checkout .bw-payment-icon__badge`
- `body.woocommerce-checkout .bw-payment-icon__tooltip`

**States:**
- **Hover**: `.bw-payment-icon--more:hover .bw-payment-icon__badge`
- **Tooltip visible**: `.bw-payment-icon--more:hover .bw-payment-icon__tooltip`

---

### Level 7: Payment Method Content (Accordion Panel)

```html
<div class="bw-payment-method__content payment_box payment_method_stripe is-open">
  <div class="bw-payment-method__inner">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__content`
- `body.woocommerce-checkout .payment_box`

**States:**
- **Open**: `.bw-payment-method__content.is-open`
- **Closed**: `.bw-payment-method__content:not(.is-open)` (default)

**Specificity**: `(0,1,2)` for base, `(0,2,2)` for state

---

### Level 8: Inner Content Elements

#### Description
```html
<div class="bw-payment-method__description">
  <p>Pay with your credit card...</p>
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__description`
- `body.woocommerce-checkout .bw-payment-method__description p`

#### Payment Fields Container
```html
<div class="bw-payment-method__fields">
  <!-- Gateway renders fields here (Stripe Elements, etc.) -->
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__fields`
- `body.woocommerce-checkout .bw-payment-method__fields .form-row`

#### Input Fields
```html
<input type="text" name="card-number" placeholder="Card number">
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__fields input[type="text"]`
- `body.woocommerce-checkout .bw-payment-method__fields input[type="email"]`
- `body.woocommerce-checkout .bw-payment-method__fields input[type="tel"]`
- `body.woocommerce-checkout .bw-payment-method__fields select`

**States:**
- **Hover**: `input:hover`
- **Focus**: `input:focus`
- **Error**: `input.woocommerce-invalid` or `.woocommerce-invalid-required-field`
- **Valid**: `input:valid` (HTML5 validation)

**Specificity**: `(0,1,4)` - Ensures override of WooCommerce defaults

---

### Level 9: Stripe Elements Overrides

```html
<div class="wc-stripe-elements-field">
  <!-- Stripe Elements iframe injected here -->
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .wc-stripe-elements-field`
- `body.woocommerce-checkout .wc-stripe-iban-element-field`

**States:**
- **Hover**: `.wc-stripe-elements-field:hover`
- **Focus**: `.wc-stripe-elements-field:focus-within`

**Note**: Stripe Elements styles must be configured via JavaScript `stripe.elements()` API.

---

### Level 10: Selected Indicator (for no-fields methods)

```html
<div class="bw-payment-method__selected-indicator">
  <svg class="bw-payment-check-icon">...</svg>
  <span>Credit card selected</span>
</div>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__selected-indicator`
- `body.woocommerce-checkout .bw-payment-check-icon`

---

### Level 11: Instruction Text

```html
<p class="bw-payment-method__instruction">
  Click the "Pay now" button to submit...
</p>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-payment-method__instruction`

---

### Level 12: Place Order Button

```html
<button type="submit" class="button alt bw-place-order-btn" id="place_order">
  <span class="bw-place-order-btn__text">Place order</span>
</button>
```

**Stable Selectors:**
- `body.woocommerce-checkout .bw-place-order-btn`
- `body.woocommerce-checkout #place_order`

**States:**
- **Hover**: `.bw-place-order-btn:hover`
- **Active**: `.bw-place-order-btn:active`
- **Disabled**: `.bw-place-order-btn:disabled`
- **Processing**: `.bw-place-order-btn.processing`

**Specificity**: `(0,1,2)` for class, `(0,1,1)` for ID

---

## Error States

### WooCommerce Validation Errors

```html
<ul class="woocommerce-error" role="alert">
  <li>Payment error: Your card was declined.</li>
</ul>
```

**Stable Selectors:**
- `body.woocommerce-checkout .woocommerce-error`
- `body.woocommerce-checkout .woocommerce-checkout-payment .woocommerce-error`

### Invalid Field

```html
<input type="text" class="woocommerce-invalid">
```

**Stable Selectors:**
- `body.woocommerce-checkout input.woocommerce-invalid`
- `body.woocommerce-checkout .woocommerce-invalid-required-field`

---

## Specificity Strategy

### Winning the Cascade

1. **Elementor Theme Overrides**: `(0,1,1)` typical
   - **Our Override**: `(0,1,2)` - Add `body.woocommerce-checkout`

2. **WooCommerce Core**: `(0,0,2)` to `(0,1,2)`
   - **Our Override**: `(0,1,2)` to `(0,1,3)` - Match or exceed

3. **When to use !important**:
   - Only for critical resets (e.g., hiding elements)
   - Background/border overrides on payment methods
   - Never for layout properties

### Selector Patterns

```css
/* ✅ GOOD - Scoped, stable, specific */
body.woocommerce-checkout .bw-payment-method__header input[type="radio"]:checked {
  /* Specificity: (0,1,3) */
}

/* ❌ BAD - Too generic, conflicts possible */
.bw-payment-method input {
  /* Specificity: (0,0,2) */
}

/* ❌ BAD - Elementor dependency */
.elementor-section .bw-payment-method {
  /* Fragile, breaks if layout changes */
}
```

---

## CSS Variable Scoping

Design tokens should be scoped to `body.woocommerce-checkout` or `:root`:

```css
/* Global tokens */
:root {
  --bw-payment-primary: #000000;
  --bw-payment-border: #d1d5db;
}

/* Checkout-specific tokens */
body.woocommerce-checkout {
  --bw-payment-bg: #ffffff;
  --bw-payment-selected-bg: #f9fafb;
}
```

---

## Testing Checklist Reference

See `payment-test-checklist.md` for complete testing scenarios.


---

## Source: `docs/40-integrations/payments/payment-test-checklist.md`

# Payment Section Test Checklist

## Pre-Test Setup

- [ ] Clear browser cache (hard refresh: `Ctrl+Shift+R`)
- [ ] Disable any CSS/JS minification plugins temporarily
- [ ] Verify `bw-payment-methods.css` version has updated (check DevTools Network tab)
- [ ] Test on checkout page with at least 2-3 payment methods enabled

---

## Visual Testing

### Section Title & Subtitle

- [ ] **Title "Payment"** appears with:
  - Font size: 28px (24px on mobile)
  - Font weight: Bold (700)
  - Color: Black (#000000)
  - Margin bottom: 8px

- [ ] **Subtitle** appears with:
  - Text: "All transactions are secure and encrypted."
  - Font size: 15px (14px on mobile)
  - Color: Gray (#6b7280)
  - Margin bottom: 20px (16px on mobile)

### Payment Method Cards

- [ ] Each payment method appears as a rounded card (8px border-radius)
- [ ] Cards have 1px gray border (#d1d5db)
- [ ] 8px gap between cards
- [ ] White background

### Radio Buttons

- [ ] Custom styled radio buttons (20x20px circles)
- [ ] Gray border when unselected (#9ca3af)
- [ ] Black background when selected (#000000)
- [ ] White dot (6x6px) appears inside when selected

### Payment Icons

- [ ] Card brand icons display (Visa, Mastercard, Maestro, etc.)
- [ ] Icons height: 28px (20px on mobile)
- [ ] Max 3 icons visible
- [ ] "+N" badge appears when more than 3 icons exist
- [ ] Badge styling:
  - Min-width: 40px
  - Height: 32px
  - White background
  - Gray border
  - Font size: 13px

### Accordion Behavior

- [ ] First payment method opens by default
- [ ] Clicking a payment method radio:
  - Closes other methods
  - Opens selected method
  - Smooth animation (0.5s)
- [ ] Content fades in when opening (opacity + translateY animation)

---

## State Testing

### Hover States

- [ ] **Payment method card hover**: Border changes to #9ca3af
- [ ] **Radio button hover**: Border changes to #9ca3af
- [ ] **"+N" badge hover**: Background changes to #f9fafb, border to #d1d5db
- [ ] **Input field hover**: Border changes to #9ca3af
- [ ] **Place order button hover**: Background darkens, slight lift effect

### Focus States

- [ ] **Radio button focus**: Blue ring shadow appears (`0 0 0 3px rgba(59, 130, 246, 0.1)`)
- [ ] **Input field focus**: Blue border (#3b82f6) + blue ring shadow
- [ ] **Stripe Elements focus**: Blue border + ring shadow

### Selected State

- [ ] Selected payment method:
  - **Border**: 2px solid black (#000000)
  - **Shadow**: `0 1px 3px 0 rgba(0, 0, 0, 0.15)`
  - **Header background**: Black (#000000)
  - **Title text**: White (#ffffff)
  - **Radio button**: Black background with white dot

### Active/Pressed States

- [ ] **Place order button active (click)**: Background darkens to #1d4ed8, no lift

### Error States

- [ ] **Invalid input field**:
  - Border: #fecaca
  - Background: #fef2f2
- [ ] **Error message** displays with:
  - Background: #fef2f2
  - Border: 1px solid #fecaca
  - Text color: #991b1b

### Loading State

- [ ] **Place order button processing**:
  - Opacity: 0.7
  - Pointer events: none
  - Spinning loader appears on right side

---

## Input Fields Testing

### Standard Inputs

- [ ] Input fields have:
  - Padding: 16px 18px
  - Border: 1px solid #d1d5db
  - Border radius: 8px
  - Font size: 15px (16px on mobile to prevent zoom)
  - Box shadow: `0 1px 2px 0 rgba(0, 0, 0, 0.05)`

- [ ] Placeholder text color: #9ca3af
- [ ] Text color when typing: #1f2937

### Two-Column Layout (Desktop)

- [ ] **Expiration date** and **Security code** fields:
  - Side by side on desktop (grid 1fr 1fr)
  - 12px gap between them
  - Stack vertically on mobile (<768px)

### Field Icons

- [ ] **Lock icon** appears in card number field (if implemented)
- [ ] **Question mark icon** appears in security code field (if implemented)
- [ ] Icons positioned:
  - Right: 18px
  - Top: 50% (vertically centered)
  - Color: #6b7280

### Checkbox Styling

- [ ] Custom checkbox (20x20px)
- [ ] Border: 2px solid #d1d5db
- [ ] Border radius: 4px
- [ ] When checked:
  - Background: Black (#000000)
  - Border: Black (#000000)
  - White checkmark appears

---

## Stripe Elements Testing

(If using Stripe payment gateway)

- [ ] Stripe Elements container has:
  - Padding: 16px 18px
  - Border: 1px solid #d1d5db
  - Border radius: 8px
  - Box shadow: `0 1px 2px 0 rgba(0, 0, 0, 0.05)`

- [ ] Hover state: Border changes to #9ca3af
- [ ] Focus state: Blue border (#3b82f6) + blue ring shadow

---

## Tooltip Testing

### "+N" Badge Tooltip

- [ ] Hover over "+N" badge shows black tooltip above it
- [ ] Tooltip styling:
  - Background: Black (#000000)
  - Border: 1px solid black
  - Border radius: 8px
  - Padding: 10px 12px
  - Shadow: `0 10px 15px -3px rgba(0, 0, 0, 0.3)`

- [ ] Arrow pointing down appears (12x12px rotated square)
- [ ] Arrow positioned: Bottom -6px, Right 14px
- [ ] Remaining card icons display inside tooltip
- [ ] Tooltip disappears when mouse leaves

- [ ] Fade-in animation: opacity 0→1, translateY -4px→0

---

## Responsive Testing

### Mobile (<768px)

- [ ] **Title** font size: 24px (reduced from 28px)
- [ ] **Subtitle** font size: 14px (reduced from 15px)
- [ ] **Input fields**: Font size 16px (prevents iOS auto-zoom)
- [ ] **Two-column layout**: Switches to single column
- [ ] **Payment icons**: Height 20px (reduced from 28px)
- [ ] **"+N" badge**: Min-width 28px, height 20px, font 11px
- [ ] **Place order button**: Padding 14px 20px, font 15px

### Tablet (768px - 1024px)

- [ ] All desktop styles apply
- [ ] No layout shifts or overflow

---

## Accessibility Testing

### Keyboard Navigation

- [ ] **Tab key** navigates through:
  1. Radio buttons
  2. Input fields
  3. Checkboxes
  4. Place order button

- [ ] **Enter/Space** on radio button label selects payment method
- [ ] **Arrow Up/Down** navigates between payment methods
- [ ] **Focus visible** on all interactive elements

### Screen Reader

- [ ] Radio buttons have accessible labels
- [ ] Error messages are announced
- [ ] Success indicators are announced
- [ ] Loading state is announced

### Focus Indicators

- [ ] **Radio button focus**: Blue ring shadow visible
- [ ] **Input focus**: Blue border + ring shadow visible
- [ ] **Header focus-within**: 2px blue outline with 2px offset

---

## Browser Compatibility

Test in:

- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest)
- [ ] **Edge** (latest)
- [ ] **Mobile Safari** (iOS)
- [ ] **Mobile Chrome** (Android)

### CSS Feature Support

- [ ] **CSS Custom Properties** (variables) work
- [ ] **:has() selector** works (or fallback `.is-selected` class applied)
- [ ] **Grid layout** works for two-column inputs
- [ ] **Flexbox** works for icon layout
- [ ] **Transitions** work smoothly
- [ ] **Custom appearance: none** works for radio/checkbox

---

## Integration Testing

### WooCommerce Integration

- [ ] **Payment gateway fields** render correctly inside `.bw-payment-method__fields`
- [ ] **Description text** appears in `.bw-payment-method__description`
- [ ] **Selected indicator** shows for no-fields methods (e.g., Cash on Delivery)
- [ ] **Place order button text** updates based on selected gateway

### Plugin Conflicts

- [ ] Test with **Elementor** active (ensure no style conflicts)
- [ ] Test with **caching plugin** active (ensure CSS loads correctly)
- [ ] Test with **minification** enabled (ensure CSS/JS works)

### AJAX Updates

- [ ] **Checkout update** (coupon apply) re-initializes JavaScript
- [ ] **Payment method change** triggers WooCommerce `payment_method_selected` event
- [ ] **Error handling** removes loading state on checkout error

---

## Performance Testing

- [ ] **CSS file size**: Check if reasonable (<50KB)
- [ ] **Load time**: CSS loads within 1 second
- [ ] **Animation performance**: No jank during accordion open/close
- [ ] **Repaints**: Minimal repaints when hovering/focusing

---

## Regression Testing

### Critical User Flows

- [ ] **Complete checkout** with credit card
- [ ] **Complete checkout** with PayPal
- [ ] **Complete checkout** with other gateways
- [ ] **Apply coupon** and verify payment section updates
- [ ] **Trigger validation error** and verify styling
- [ ] **Change payment method** multiple times

---

## Post-Deployment Checklist

- [ ] Monitor error logs for JavaScript errors
- [ ] Check analytics for checkout abandonment rate changes
- [ ] Verify no increase in support tickets related to payment
- [ ] Collect user feedback on new design

---

## Known Issues / Notes

**Document any issues found during testing:**

1. _Issue:_
   - _Browser:_
   - _Steps to reproduce:_
   - _Expected:_
   - _Actual:_
   - _Fix:_

---

## Sign-Off

**Tested by:** ________________
**Date:** ________________
**Environment:** (Production / Staging / Local)
**Result:** ☐ Pass ☐ Fail
**Notes:**


---

## Source: `docs/40-integrations/payments/payments-architecture-map.md`

# Payments Integration Architecture Map

## 1) Purpose + Scope
This document is the official architecture reference for the Payments integration domain.
It defines how payment configuration, gateway registration, checkout rendering, wallet orchestration, Stripe execution, and webhook-driven order updates work together in the current implementation.

Scope:
- Google Pay, Apple Pay, Klarna, Stripe coupling, checkout selector coupling.
- Runtime architecture and contracts only.
- No code refactor guidance and no implementation change instructions.

## 2) Payment Stack Layers

### Layer A: Admin toggle & key layer
Primary writer: `admin/class-blackwork-site-settings.php` (Checkout > payment sub-tabs).

Persisted keys include:
- Google Pay: `bw_google_pay_*`
- Klarna: `bw_klarna_*`
- Apple Pay: `bw_apple_pay_*`

Admin layer also provides:
- connection test AJAX endpoints
- Apple domain verification endpoint
- admin scripts for mode pills/test actions

### Layer B: WooCommerce gateway registration
Bootstrap path: `woocommerce/woocommerce-init.php`.

Registration/loading model:
- `add_filter('woocommerce_payment_gateways', 'bw_mew_add_google_pay_gateway')`
- gateway classes loaded from:
  - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - `includes/Gateways/class-bw-klarna-gateway.php`
  - `includes/Gateways/class-bw-apple-pay-gateway.php`
  - shared base: `includes/Gateways/class-bw-abstract-stripe-gateway.php`

### Layer C: Stripe SDK integration
Stripe is integrated at multiple levels:
- server side: `BW_Stripe_Api_Client` + gateway API requests
- client side: `https://js.stripe.com/v3/` in checkout
- Woo Stripe appearance customization via:
  - `wc_stripe_elements_options`
  - `wc_stripe_elements_styling`
  - `wc_stripe_upe_params`

### Layer D: Wallet orchestration layer
Wallet runtime JS:
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`

Responsibilities:
- capability checks (`canMakePayment`)
- hidden method field injection (`bw_google_pay_method_id`, `bw_apple_pay_method_id`)
- fallback to non-wallet gateway when unavailable
- sync with checkout refresh lifecycle (`updated_checkout`)

### Layer E: Checkout selector coupling
Selector core:
- template: `woocommerce/templates/checkout/payment.php`
- orchestrator: `assets/js/bw-payment-methods.js`

Contract:
- selected radio method is the effective submission method
- wallet button visibility and place-order visibility are synchronized from selector state

### Layer F: Webhook processing layer
Webhook endpoints handled per gateway via `woocommerce_api_{gateway_id}`.

Core webhook logic lives in `BW_Abstract_Stripe_Gateway::handle_webhook()` and maps Stripe PaymentIntent events to WooCommerce order state.

## 3) Runtime Flow Model
Lifecycle:

1. Admin Intent
- Merchant enables/disables methods and configures keys/secrets/mode-related settings.

2. Gateway Registration
- WooCommerce bootstrap loads gateway classes and hooks.
- gateway availability list is filtered before checkout render.

3. Eligibility
- Eligibility is derived from combined conditions:
  - custom enable toggles (`bw_*_enabled`)
  - WooCommerce gateway enabled status
  - key readiness
  - context/runtime constraints

4. Checkout Render
- `payment.php` renders payment methods and readiness hints.
- payment selector structure is output for JS orchestration.

5. Client Capability
- wallet scripts instantiate Stripe PaymentRequest.
- `canMakePayment` determines device/browser wallet support.
- unavailable wallet methods are visually/behaviorally disabled with fallback path.

6. Stripe Execution
- gateway `process_payment()` creates PaymentIntent, handles immediate statuses, persists PI metadata.
- wallet hidden method ID participates in request payload.

7. Webhook
- Stripe webhook is signature-validated.
- event id deduplication and PI/order consistency checks run.

8. Order Status Update
- `payment_intent.succeeded` -> complete payment
- `payment_intent.payment_failed` -> failed
- `payment_intent.processing` -> on-hold
- `payment_intent.canceled` -> cancelled

## 4) Gateway Contract
Each gateway implementation is expected to satisfy these responsibilities.

### `is_available()`
- Determines if method can be offered in checkout context.
- In current codebase, explicit `is_available()` is implemented in the Google Pay override class; Klarna/Apple rely on parent/selector/readiness coupling.

### `process_payment()`
- Validates order and gateway-specific payload.
- Verifies key readiness.
- Creates/confirms Stripe PaymentIntent.
- Persists PI and mode metadata.
- Returns WooCommerce result + redirect, or customer-safe error.

### Key readiness
- Mode-aware key resolution (test/live) is required.
- Missing or invalid key material must fail fast with safe message + log.

### Error handling
- Customer errors should be generic and actionable.
- Detailed provider diagnostics stay in logs/order notes.

### Webhook validation
- Signature verification with gateway webhook secret is mandatory.
- Invalid signature/payload must terminate with non-success HTTP status.

### Order status mapping
- Stripe event outcomes must deterministically map to WooCommerce statuses.
- Event replay must be idempotent (processed-event tracking and PI consistency checks).

## 5) Wallet Model

### Google Pay
- Runtime script: `assets/js/bw-google-pay.js`
- Uses Stripe PaymentRequest with localized params from `bwGooglePayParams`.
- Injects `bw_google_pay_method_id` for gateway processing.

### Apple Pay
- Runtime script: `assets/js/bw-apple-pay.js`
- Uses Stripe PaymentRequest with localized params from `bwApplePayParams`.
- Injects `bw_apple_pay_method_id` for gateway processing.

### Klarna
- Implemented as Stripe-backed custom gateway (`BW_Klarna_Gateway`) with selector and readiness checks in checkout template.

### Express fallback logic
- Apple Pay runtime includes explicit express fallback flag (`enableExpressFallback`).
- Selector orchestration ensures only one actionable submission path is visible.

### Stripe UPE interaction
- Stripe UPE styling is overridden by filters.
- `assets/js/bw-stripe-upe-cleaner.js` removes conflicting UPE rows to keep custom selector coherent.

## 6) Precedence & Mode Rules

### Custom toggle vs Woo enable
- Effective availability is intersection logic, not single-flag logic:
  - custom toggle intent (`bw_*_enabled`)
  - WooCommerce gateway enabled state
  - runtime readiness and context

### Test vs Live keys
- Google Pay has explicit `bw_google_pay_test_mode` with test/live key switching.
- Base/derived gateway logic resolves secrets/webhook keys by mode.

### UPE vs custom selector
- Custom selector (`payment.php` + `bw-payment-methods.js`) is the operative checkout payment UI.
- Stripe UPE components are visually constrained/cleaned to prevent duplicate method controls.

## 7) High-Risk Zones
Blast-radius hotspots:
- `woocommerce/templates/checkout/payment.php`
  - central render contract for payment methods and readiness messaging.
- `assets/js/bw-payment-methods.js`
  - source of truth for selector state, fallback method switching, and checkout button visibility.
- `assets/js/bw-google-pay.js`
- `assets/js/bw-apple-pay.js`
  - wallet capability gating + hidden-field coupling + checkout submission path.
- `includes/Gateways/class-bw-abstract-stripe-gateway.php`
  - shared webhook/payment/refund semantics across gateway family.
- `woocommerce/woocommerce-init.php`
  - registration/orchestration nexus (gateway load, enqueue, Stripe/UPE filters).

Typical failure classes in these zones:
- selected gateway mismatch vs submitted gateway
- wallet shown while unavailable (or hidden while available)
- duplicate/conflicting payment UIs
- webhook-driven order state drift

## 8) Maintenance & Regression References
- Regression protocol: [`docs/50-ops/regression-protocol.md`](../../50-ops/regression-protocol.md)
- Payments runbook: [`docs/50-ops/runbooks/payments-runbook.md`](../../50-ops/runbooks/payments-runbook.md)
- Checkout architecture dependency: [`docs/30-features/checkout/checkout-architecture-map.md`](../../30-features/checkout/checkout-architecture-map.md)

## Normative Payments Architecture Principles

### 1) Readiness Gating (Keys + Toggle Discipline)
- Payment toggles express integration intent, not automatic runtime availability.
- A payment method is actionable only if all required key material and gateway readiness conditions are valid for the active mode.
- Rendering and submission paths must enforce the same readiness gates.

### 2) Deterministic Order State Transitions
- Payment lifecycle must map to deterministic WooCommerce order statuses.
- Equivalent provider events must never produce divergent statuses for the same order state.
- Manual/UI status updates must not contradict gateway/webhook authority for payment completion/failure events.

### 3) Webhook Integrity + Idempotency Invariant
- Webhook signature validation is mandatory before any state mutation.
- Event replay must be idempotent through processed-event tracking and PaymentIntent consistency checks.
- A webhook event may be safely ignored only when invariant checks fail (invalid signature, mismatched gateway/order/PI, already processed event).

### 4) Wallet Capability Discipline
- Wallet methods (Google Pay, Apple Pay) must be shown as actionable only when both:
  - server eligibility passes
  - client capability checks succeed (`canMakePayment` or equivalent)
- If capability fails after render, UI must downgrade to a safe non-wallet path without ambiguous state.

### 5) UPE vs Custom Selector Non-Duplication Invariant
- The custom selector (`payment.php` + selector JS) is the canonical user-facing payment state model.
- Stripe UPE artifacts must not create duplicate or conflicting selection controls.
- Any UPE cleanup/styling logic must preserve a single, unambiguous selectable method per effective payment path.

### 6) Mode Consistency (Test/Live Isolation)
- Test and live credentials, webhook secrets, and mode-dependent behavior must remain isolated.
- A request executed in one mode must never read secrets from the opposite mode except where an explicit, documented fallback is intentionally designed.
- Operational logs and error diagnostics should include mode context for traceability.

### 7) Error Normalization Contract
- Provider/API errors must be normalized into:
  - customer-safe messages for checkout UX
  - detailed diagnostics for logs/order notes
- Error handling must not leak sensitive key/secret details.
- Equivalent failures across gateways should produce coherent user-facing outcomes.

### 8) High-Risk Change Policy (Blast-Radius Rule)
- Changes touching the payment state contract or webhook semantics are high-risk by definition.
- High-risk surfaces include:
  - `woocommerce/templates/checkout/payment.php`
  - selector/wallet scripts
  - gateway classes
  - `BW_Abstract_Stripe_Gateway`
  - payments orchestration in `woocommerce-init.php`
- Any change in these surfaces requires full payment + checkout regression validation before release.


---

## Source: `docs/40-integrations/payments/payments-overview.md`

# Payments Overview

## Overview
This plugin uses WooCommerce checkout standard flow and supports multiple payment providers.

### Active custom gateways (all in production)

| Gateway ID | Method | File |
|---|---|---|
| `bw_google_pay` | Google Pay (Stripe) | `includes/Gateways/class-bw-google-pay-gateway.php` |
| `bw_klarna` | Klarna (Stripe) | `includes/Gateways/class-bw-klarna-gateway.php` |
| `bw_apple_pay` | Apple Pay (Stripe) | `includes/Gateways/class-bw-apple-pay-gateway.php` |

### Coexisting gateways (third-party plugins)
- `wc_stripe` (cards, official Stripe plugin)
- PayPal plugin (separate)

### Architecture goal
- Reuse Stripe hardening logic via shared `BW_Abstract_Stripe_Gateway` base class.
- One webhook endpoint per gateway (Option B — zero migration risk).
- No code duplication across Google Pay, Klarna, Apple Pay.

## Payment Stack Overview

### Official plugins
1. Credit / Debit Card
- Provider: `wc_stripe` official plugin
- Checkout behavior: standard Stripe card fields inside Woo payment box

2. PayPal
- Provider: official PayPal plugin
- Checkout behavior: plugin-managed

### Canonical runtime rules
1. Final payment completion is webhook-driven (`payment_intent.succeeded`), not return URL-driven.
2. Custom gateways must never mutate orders owned by `wc_stripe` or PayPal.
3. Webhook handling and refunds must stay gateway-scoped with idempotency guards.
4. Checkout UI must show a single active action path for the selected payment method.

## File Structure

Plugin root: `wp-content/plugins/wpblackwork/`

```
includes/Gateways/
  class-bw-abstract-stripe-gateway.php   ← shared base (webhook, refund, dedup, logging)
  class-bw-google-pay-gateway.php        ← Google Pay implementation
  class-bw-klarna-gateway.php            ← Klarna implementation
  class-bw-apple-pay-gateway.php         ← Apple Pay implementation
includes/Stripe/
  class-bw-stripe-api-client.php         ← Stripe HTTP client
includes/Utils/
  class-bw-stripe-safe-logger.php        ← WC_Logger wrapper
```

## Checkout Flow (Google Pay)
1. User selects method `bw_google_pay` in checkout accordion.
2. Frontend JS (`assets/js/bw-google-pay.js`) runs Stripe Payment Request and checks availability (`canMakePayment`).
3. On approval, JS posts checkout with hidden field `bw_google_pay_method_id`.
4. `BW_Google_Pay_Gateway::process_payment()` creates Stripe PaymentIntent via shared helper.
5. Gateway saves PI refs on order meta and sets transaction id.
6. On success, WooCommerce redirects to thank-you page and triggers normal email flow.

## Stripe Webhook Configuration

**Three separate webhook endpoints must be registered in the Stripe Dashboard:**

| Gateway | Endpoint URL | Events to subscribe |
|---|---|---|
| Google Pay | `https://yourdomain.com/?wc-api=bw_google_pay` | `payment_intent.succeeded`, `payment_intent.payment_failed` |
| Klarna | `https://yourdomain.com/?wc-api=bw_klarna` | `payment_intent.succeeded`, `payment_intent.payment_failed` |
| Apple Pay | `https://yourdomain.com/?wc-api=bw_apple_pay` | `payment_intent.succeeded`, `payment_intent.payment_failed` |

Each endpoint has its own signing secret stored in:
- `bw_google_pay_webhook_secret`
- `bw_klarna_webhook_secret`
- `bw_apple_pay_webhook_secret`

> If only one webhook is configured, only that gateway will receive events.
> The other two gateways will never finalize orders via webhook.

## Webhook Flow
Endpoints (all active):
- `/?wc-api=bw_google_pay`
- `/?wc-api=bw_klarna`
- `/?wc-api=bw_apple_pay`

Handled in base class (`BW_Abstract_Stripe_Gateway::handle_webhook`):
1. Verify Stripe signature (`t=`, one or more `v1=`, 300s tolerance).
2. Parse event payload.
3. Resolve order id from metadata (`wc_order_id`, fallback `order_id`).
4. Anti-conflict guard: process only if `order->get_payment_method() === gateway id`.
5. Optional gateway metadata guard (`metadata[bw_gateway]`).
6. PI consistency guard against stored PI meta.
7. Idempotency: ignore already processed event ids (rolling list of last 20).
8. Apply state transition:
   - `payment_intent.succeeded` => `payment_complete` (if not paid)
   - `payment_intent.payment_failed` => `failed` (if not already paid)

## Refund Flow
`BW_Abstract_Stripe_Gateway::process_refund()` handles:
- WooCommerce order validation
- gateway ownership check
- mode-aware key selection from order meta
- partial/full refund amount handling
- idempotency key
- Stripe refund call
- optional fallback using `latest_charge`
- refund meta update + order note only after valid Stripe refund id

Idempotency key format:
- `bw_gpay_refund_{order_id}_{pi_id}_{amount_or_full}_{reason_hash}`

## Anti-Conflict with `wc_stripe`
Hard guards prevent cross-gateway side effects:
- Webhook ignores orders not owned by this gateway.
- Refund blocks non-owned orders.
- PI mismatch protection avoids wrong order updates.

## Option Names (Google Pay)
Kept unchanged:
- `bw_google_pay_publishable_key`
- `bw_google_pay_secret_key`
- `bw_google_pay_test_publishable_key`
- `bw_google_pay_test_secret_key`
- `bw_google_pay_webhook_secret`
- `bw_google_pay_test_webhook_secret`
- `bw_google_pay_test_mode`
- `bw_google_pay_enabled`
- `bw_google_pay_statement_descriptor`

## Order Meta Conventions
Current Google Pay keys (kept):
- `_bw_gpay_pi_id`
- `_bw_gpay_mode`
- `_bw_gpay_pm_id`
- `_bw_gpay_created_at`
- `_bw_gpay_processed_events`
- `_bw_gpay_refund_ids`
- `_bw_gpay_last_refund_id`

Backwards compatibility:
- Existing keys are still read/written.
- No migration required for historical orders.

## Webhook Strategy: Option A vs Option B
Option A (single router `wc-api=bw_stripe_wallets`):
- Pros: one endpoint, centralized routing.
- Cons: requires Stripe dashboard updates and migration risks.

Option B (current):
- Keep dedicated endpoint per gateway.
- Reuse all logic in abstract base.
- Pros: zero migration risk, safest for production.

Current implementation uses Option B.

## Order Status Conventions

| `process_payment()` PI status | Order status | Who finalizes |
|---|---|---|
| `succeeded` | `on-hold` | Stripe webhook |
| `processing` | `on-hold` | Stripe webhook |
| `requires_action` | `pending` + redirect | User completes 3DS, then webhook |
| `requires_payment_method` / `canceled` | error notice, no status change | User retries |

> **Rule:** `on-hold` = payment attempted, waiting for webhook.
> `pending` = 3DS auth redirect in progress.
> Never call `payment_complete()` directly in `process_payment()`.
> Only the webhook (`payment_intent.succeeded`) triggers `payment_complete()`.

## Stuck Order Monitoring

A WP-Cron job (`bw_mew_check_stuck_orders`) runs hourly and:
- Finds orders in `pending`/`on-hold` with a BW PaymentIntent meta key.
- That have not been updated in 4+ hours and are not paid.
- Sends an admin email notification and logs to `wc-bw-gateway` logger.

If orders appear stuck, check:
1. Stripe Dashboard → Webhooks → delivery logs for failures.
2. WooCommerce → Status → Logs → filter `bw-gateway`.
3. Verify all 3 webhook endpoints are registered in Stripe with correct signing secrets.

## How to Add a New Gateway (template)
1. Create gateway class extending `BW_Abstract_Stripe_Gateway`.
2. Implement required option-name methods and meta key map.
3. Implement specific `process_payment()` and UI rendering.
4. Register class in WooCommerce gateway filter.
5. Add admin tab/settings using gateway-specific option names.
6. Register a webhook endpoint in Stripe Dashboard for `/?wc-api=bw_{gateway_id}`.
7. Reuse base webhook and refund logic.

## Testing Checklist
### Checkout success
- Google Pay payment succeeds.
- Order marked paid.
- Thank-you redirect and emails work.

### Checkout failure
- Payment failure shows notice.
- Order not marked paid.

### Webhook replay
- Re-send same `evt_*`.
- No duplicate notes/status changes.

### Refund
- Partial refund works.
- Full refund works.
- Double request does not duplicate side effects.

### Conflict checks
- Webhook for other gateway order is ignored.
- Refund for non-owned gateway order is blocked.

### Availability UX
- `canMakePayment = false`: method disabled/fallback to other methods.
- `canMakePayment = true`: button is shown and checkout completes.


---

## Source: `docs/40-integrations/supabase/README.md`

# Supabase

## Files
- [supabase-architecture-map.md](supabase-architecture-map.md): official deep architecture reference for Supabase integration modes, flows, and boundaries.
- [supabase-create-password.md](supabase-create-password.md): canonical create-password flow and recovery notes.

## Cross-links
- Auth integration: [../auth/](../auth/README.md)

## Subfolders
- [audits](audits/README.md)

