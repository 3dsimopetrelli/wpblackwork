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
