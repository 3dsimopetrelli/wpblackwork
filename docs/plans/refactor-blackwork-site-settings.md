# Refactor: class-blackwork-site-settings.php

## Status: ON HOLD
**Reason:** Colleague is working on checkout page. Wait until checkout work is complete to avoid merge conflicts.

---

## Overview

**File:** `admin/class-blackwork-site-settings.php`
**Current size:** 4,108 lines
**Target size:** ~300 lines (main orchestrator)
**Issues found:** 17 (5 High, 7 Medium, 5 Low)

---

## Priority Areas

1. **Massive file size** - File needs modularization (4,108 lines in single file)
2. **Duplicated code patterns** - Multiple nearly identical form handling/save patterns
3. **Inline JavaScript and CSS** - Should be extracted to external files

---

## Proposed File Structure

```
admin/
├── class-blackwork-site-settings.php      (~300 lines - main orchestrator)
├── class-bw-product-importer.php          (~1,100 lines - CSV import)
├── tabs/
│   ├── tab-account-page.php               (~400 lines)
│   ├── tab-cart-popup.php                 (~500 lines)
│   ├── tab-checkout.php                   (~350 lines)  ← SKIP FOR NOW
│   ├── tab-coming-soon.php                (~40 lines)
│   ├── tab-import-product.php             (~230 lines)
│   ├── tab-my-account.php                 (~60 lines)
│   └── tab-redirect.php                   (~130 lines)
├── helpers/
│   └── settings-helpers.php               (~100 lines - form field renderers)
├── js/
│   ├── bw-account-page.js                 (extracted from inline)
│   └── bw-checkout-settings.js            (extracted from inline)
└── css/
    └── blackwork-site-settings.css        (consolidated styles)
```

---

## Detailed Changes

### Phase 1: Extract CSV Importer (Zero Conflict Risk)

**Move lines 2967-4108 to `admin/class-bw-product-importer.php`**

Functions to extract:
- `bw_import_handle_upload_request()`
- `bw_import_handle_run_request()`
- `bw_import_upload_dir()`
- `bw_import_save_state()`
- `bw_import_get_state()`
- `bw_import_clear_state()`
- `bw_import_parse_csv_file()`
- `bw_import_calculate_header_stats()`
- `bw_import_get_mapping_options()`
- `bw_import_guess_mapping()`
- `bw_import_normalize_string()`
- `bw_import_normalize_mapping_key()`
- `bw_import_get_mapping_aliases()`
- `bw_import_detect_custom_meta_fields()`
- `bw_import_product_slider_meta_options()`
- `bw_import_attribute_options()`
- `bw_import_pretty_meta_label()`
- `bw_import_has_identifier()`
- `bw_import_process_rows()`
- `bw_import_prepare_row_data()`
- `bw_import_explode_list()`
- `bw_import_save_product_from_row()`
- `bw_import_locate_product_ids()`
- `bw_import_assign_terms()`
- `bw_import_handle_image()`
- `bw_import_apply_attributes()`

### Phase 2: Create Helper Functions

**Create `admin/helpers/settings-helpers.php`**

```php
// Success notice renderer (replaces 6 duplicate blocks)
function bw_render_admin_notice($type, $message) { ... }

// Padding/margin field grid (replaces 5 duplicate blocks)
function bw_render_spacing_fields($prefix, $values, $labels = ['Top', 'Right', 'Bottom', 'Left']) { ... }

// Color picker field
function bw_render_color_field($name, $value, $default, $description) { ... }

// Media upload field
function bw_render_media_field($name, $value, $button_text, $description) { ... }

// Checkbox toggle field
function bw_render_toggle_field($name, $checked, $label, $description) { ... }

// Value clamping helper
function bw_clamp($value, $min, $max) { ... }

// Batch option loading
function bw_get_options_batch($options_map) { ... }

// Batch option saving
function bw_save_options_batch($options_map) { ... }
```

### Phase 3: Extract Tab Files

#### 3a. Account Page Tab (Lines 174-1255)
**File:** `admin/tabs/tab-account-page.php`

Split into:
- `bw_account_page_save()` - Form POST handling
- `bw_account_page_get_options()` - Load all options with defaults
- `bw_account_page_render_design_tab()` - Design sub-tab HTML
- `bw_account_page_render_technical_tab()` - Technical sub-tab HTML
- `bw_site_render_account_page_tab()` - Main orchestrator

#### 3b. Cart Popup Tab (Lines 1784-2565)
**File:** `admin/tabs/tab-cart-popup.php`

Split into:
- `bw_cart_popup_get_options()` - Load all options (replaces 74 get_option calls)
- `bw_cart_popup_render_general_section()` - General settings
- `bw_cart_popup_render_checkout_button_section()` - Checkout button styles
- `bw_cart_popup_render_continue_button_section()` - Continue button styles
- `bw_cart_popup_render_promo_section()` - Promo code section
- `bw_cart_popup_render_svg_section()` - SVG customization
- `bw_site_render_cart_popup_tab()` - Main orchestrator

#### 3c. Checkout Tab (Lines 1316-1782) - SKIP FOR NOW
**File:** `admin/tabs/tab-checkout.php`

Wait until colleague finishes checkout work.

#### 3d. Other Tabs (Simple extractions)
- `admin/tabs/tab-coming-soon.php` (Lines 2698-2735)
- `admin/tabs/tab-my-account.php` (Lines 1257-1311)
- `admin/tabs/tab-redirect.php` (Lines 2567-2694)
- `admin/tabs/tab-import-product.php` (Lines 2740-2965)

### Phase 4: Extract Inline JavaScript

#### `admin/js/bw-account-page.js` (Lines 1061-1253)
- Media upload handlers
- Tab switching logic
- Provider toggle logic
- Registration mode toggle
- OAuth provider toggles

#### `admin/js/bw-checkout-settings.js` (Lines 1758-1780)
- Media upload handler
- Color picker initialization

### Phase 5: Refactor Main File

**Final `class-blackwork-site-settings.php` structure:**

```php
<?php
// Menu registration
function bw_site_settings_menu() { ... }
add_action('admin_menu', 'bw_site_settings_menu');

// Asset loading
function bw_site_settings_admin_menu_icon_styles() { ... }
function bw_site_settings_admin_assets($hook) { ... }

// Include tab files
require_once __DIR__ . '/helpers/settings-helpers.php';
require_once __DIR__ . '/tabs/tab-account-page.php';
require_once __DIR__ . '/tabs/tab-cart-popup.php';
// require_once __DIR__ . '/tabs/tab-checkout.php';  // SKIP FOR NOW
require_once __DIR__ . '/tabs/tab-coming-soon.php';
require_once __DIR__ . '/tabs/tab-my-account.php';
require_once __DIR__ . '/tabs/tab-redirect.php';
require_once __DIR__ . '/tabs/tab-import-product.php';
require_once __DIR__ . '/class-bw-product-importer.php';

// Main page renderer with tab dispatch
function bw_site_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'cart-popup';

    $tab_handlers = [
        'cart-popup'      => 'bw_site_render_cart_popup_tab',
        'bw-coming-soon'  => 'bw_site_render_coming_soon_tab',
        'account-page'    => 'bw_site_render_account_page_tab',
        'my-account-page' => 'bw_site_render_my_account_front_tab',
        'checkout'        => 'bw_site_render_checkout_tab',
        'redirect'        => 'bw_site_render_redirect_tab',
        'import-product'  => 'bw_site_render_import_product_tab',
    ];

    // Render page with tabs...
    if (isset($tab_handlers[$active_tab])) {
        call_user_func($tab_handlers[$active_tab]);
    }
}
```

---

## Quick Wins (Can Do Anytime)

- [ ] Delete unused `$checkout_color` (line 1803)
- [ ] Delete unused `$continue_color` (line 1806)
- [ ] Remove dead code comment at line 2000-2001
- [ ] Add `wp_unslash()` to `$_GET['tab']` on line 108

---

## Duplicated Code Details

| Location | Pattern | Occurrences |
|----------|---------|-------------|
| Success notices | `<div class="notice notice-success">...` | 6 |
| Padding field grids | 4 inputs with Top/Right/Bottom/Left labels | 5 |
| Media upload JS | `$('.bw-media-upload').on('click'...` | 2 |
| Option save blocks | `update_option()` calls | 57+74+30 = 161 total |
| Option load blocks | `get_option()` calls | 60+74+30 = 164 total |
| OAuth help accordions | Facebook/Google setup instructions | 2 |

---

## Verification Steps

After refactoring:

1. **Admin page loads correctly**
   - Navigate to Blackwork Site > each tab
   - Verify all tabs render without PHP errors

2. **Settings save correctly**
   - Change a setting in each tab
   - Save and verify value persists
   - Check `wp_options` table for correct values

3. **Cart popup functions**
   - Enable cart popup
   - Add product to cart
   - Verify popup appears with correct styling

4. **CSV import works**
   - Upload test CSV
   - Map columns
   - Run import
   - Verify products created/updated

5. **Redirects work**
   - Add a test redirect
   - Visit source URL
   - Verify redirect to target

---

## Risk Assessment

| Change | Risk | Mitigation |
|--------|------|------------|
| Extract CSV importer | Low | Self-contained, no external dependencies |
| Extract tab files | Low | Each tab is independent |
| Create helper functions | Medium | Test each replaced instance |
| Refactor option handling | Medium | Verify all options still save/load |
| Extract inline JS | Low | Test UI interactions |
| **Checkout tab** | **HIGH** | **SKIP - colleague working on it** |

---

## Notes

- The `bw_cart_popup_save_settings()` function is defined in `cart-popup/admin/settings-page.php`, not in this file
- The `bw_normalize_redirect_path()` function is defined in `includes/class-bw-redirects.php`
- Checkout tab should be refactored separately after colleague completes checkout work
