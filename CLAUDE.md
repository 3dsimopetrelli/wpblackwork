# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**BW Elementor Widgets** is a WordPress plugin that provides custom Elementor widgets and extensive WooCommerce customizations for the BlackWork theme. The plugin is structured as a modular system with three main submodules and a collection of custom Elementor widgets.

## Development Environment

This is a WordPress plugin located at:
```
wp-content/plugins/wpblackwork/
```

The plugin requires:
- WordPress
- Elementor page builder
- WooCommerce (for e-commerce features)
- Slick Carousel (loaded via CDN)

## Git Conventions

- Never include references to Claude or Claude Code in commit messages or PR descriptions
- Do not add "Co-Authored-By: Claude" or similar attribution lines

## Plugin Architecture

### Main Entry Point

`bw-main-elementor-widgets.php` - Main plugin file that:
- Defines plugin constants (`BW_MEW_URL`, `BW_MEW_PATH`)
- Loads all submodules and core components
- Registers asset enqueue hooks
- Initializes WooCommerce customizations

### Core Components

1. **Widget Loader** (`includes/class-bw-widget-loader.php`)
   - Automatically discovers and registers all Elementor widgets from `includes/widgets/`
   - Uses naming convention: `class-bw-*-widget.php` → `Widget_Bw_*` or `BW_*_Widget`
   - Handles both old and new Elementor registration APIs

2. **Helper Classes**
   - `includes/class-bw-widget-helper.php` - Shared utility methods for widgets
   - `includes/helpers.php` - Global helper functions
   - `includes/woocommerce-overrides/class-bw-product-card-renderer.php` - Centralized product card rendering

3. **Submodules**
   - **Cart Popup** (`cart-popup/`) - Side-sliding cart with AJAX updates
   - **Coming Soon** (`BW_coming_soon/`) - Coming soon page with video background and newsletter integration
   - **Site Settings** (`admin/class-blackwork-site-settings.php`) - Unified settings page

### Widget System

All widgets are located in `includes/widgets/` and follow this pattern:
- Filename: `class-bw-{name}-widget.php`
- Class name: `Widget_Bw_{Name}` or `BW_{Name}_Widget`
- Auto-discovered and registered by the widget loader

**Key Widgets:**
- `class-bw-slick-slider-widget.php` - Slick carousel for posts/products
- `class-bw-filtered-post-wall-widget.php` - Filterable masonry grid with category/tag filters
- `class-bw-wallpost-widget.php` - Masonry layout using WordPress native masonry
- `class-bw-search-widget.php` - Live search with AJAX filtering
- `class-bw-product-slide-widget.php` - Product slider with variations
- `class-bw-related-products-widget.php` - Related products display
- `class-bw-static-showcase-widget.php` - Static image showcase
- `class-bw-animated-banner-widget.php` - Animated banner widget
- `class-bw-navshop-widget.php` - Navigation shop menu

### Asset Management

Assets are organized by widget/feature:
- `assets/css/bw-{name}.css` - Widget-specific styles
- `assets/js/bw-{name}.js` - Widget-specific JavaScript

**Asset Registration Pattern:**
```php
function bw_register_{name}_widget_assets() { ... }
add_action('init', 'bw_register_{name}_widget_assets');
```

**Asset Enqueue Pattern:**
```php
function bw_enqueue_{name}_widget_assets() { ... }
add_action('elementor/frontend/after_enqueue_scripts', 'bw_enqueue_{name}_widget_assets');
```

**Versioning:** Assets use `filemtime()` for cache-busting instead of static version numbers.

## WooCommerce Customizations

### Custom Product Types

Located in `includes/product-types/`. See `PRODUCT_TYPES_IMPLEMENTATION.md` for detailed documentation.

**Standard WooCommerce types (unmodified):**
- Simple, Grouped, External/Affiliate, Variable

**Custom product types (with customizations):**
1. **Digital Assets** (`digitalassets`) - Virtual + Downloadable + Variations
2. **Homebook** (`books`) - Physical + Variations + Shipping
3. **Print** (`prints`) - Physical + Variations + Shipping

**Implementation:**
- `product-types-init.php` - Registration and tab visibility
- `class-bw-product-type-digital.php` - Digital Asset class
- `class-bw-product-type-book.php` - Book class
- `class-bw-product-type-print.php` - Print class
- `class-bw-product-slider-metabox.php` - Product slider metabox

### WooCommerce Template Overrides

Templates in `woocommerce/templates/` override core WooCommerce templates:
- `myaccount/*` - Custom account page layouts with social login
- `checkout/*` - Two-column checkout layout
- `single-product/related.php` - Custom related products display

**Template Loading:** `bw_mew_locate_template()` in `woocommerce/woocommerce-init.php` forces plugin templates before theme overrides.

### WooCommerce Features

1. **Custom Account Page** (`includes/woocommerce-overrides/class-bw-my-account.php`)
   - Social login (Facebook, Google OAuth)
   - Custom login/register layout
   - Passwordless login support

2. **Custom Checkout**
   - Two-column layout with configurable colors
   - Cart quantity updates via AJAX
   - Custom legal text placement
   - Logo support

3. **Product Card Renderer** (`class-bw-product-card-renderer.php`)
   - Centralized product card HTML generation
   - Hover effects with secondary images
   - Cart popup integration

## AJAX Handlers

Located in `bw-main-elementor-widgets.php`:

1. **Live Product Search** (`bw_live_search_products`)
   - Endpoint: `wp_ajax_bw_live_search_products`
   - Searches products with category/type filters
   - Returns JSON with product data

2. **Filtered Post Wall**
   - `bw_fpw_get_subcategories` - Get child categories
   - `bw_fpw_get_tags` - Get tags for category
   - `bw_fpw_filter_posts` - Filter posts by category/subcategories/tags
   - Uses transient caching (3-5 minutes) for performance
   - Skips cache for random order results

## Metaboxes

Located in `metabox/`:
- `digital-products-metabox.php` - Digital product fields
- `images-showcase-metabox.php` - Image showcase fields
- `artist-name-metabox.php` - Artist name field
- `variation-license-html-field.php` - License HTML for variations

## Dynamic Tags

Located in `includes/dynamic-tags/`:
- `class-bw-artist-name-tag.php` - Elementor dynamic tag for artist name
- `class-bw-showcase-label-tag.php` - Elementor dynamic tag for showcase label

## Asset File Conventions

**CSS Files:**
- Use BEM-like naming: `.bw-{widget}-{element}`
- File versioning via `filemtime()` for cache-busting
- Registered on `init`, enqueued on `elementor/frontend/after_enqueue_scripts`

**JavaScript Files:**
- jQuery-dependent
- Use `wp_localize_script()` for AJAX config and nonces
- Event delegation for dynamic content
- Masonry integration: `imagesLoaded` + native WordPress masonry

## Common Patterns

### Adding a New Widget

1. Create `includes/widgets/class-bw-{name}-widget.php`
2. Class name must be `Widget_Bw_{Name}` or `BW_{Name}_Widget`
3. Create CSS: `assets/css/bw-{name}.css`
4. Create JS: `assets/js/bw-{name}.js`
5. Add registration function in main plugin file:
   ```php
   function bw_register_{name}_widget_assets() {
       bw_register_widget_assets('{name}');
   }
   add_action('init', 'bw_register_{name}_widget_assets');
   ```
6. Widget will be auto-discovered by the loader

### Asset Registration Helper

Use `bw_register_widget_assets($slug)` helper from `includes/helpers.php` for standard widget asset patterns.

### WooCommerce Customization Pattern

1. Template overrides go in `woocommerce/templates/`
2. PHP logic goes in `woocommerce/woocommerce-init.php` or `includes/woocommerce-overrides/`
3. Styles go in `assets/css/` or `woocommerce/css/`
4. Scripts go in `assets/js/`

## Performance Optimizations

1. **Transient Caching:** AJAX handlers use transients (3-5 min) to cache expensive queries
2. **Asset Loading:** Conditional loading based on context (pages, widgets)
3. **Masonry:** Uses native WordPress masonry library instead of external dependency
4. **File Versioning:** `filemtime()` ensures browsers cache efficiently

## Important Notes

1. **Plugin Initialization:** Components load on `init` hook (priority 5) to avoid translation warnings in WordPress 6.7+
2. **Cart Popup:** Always loads assets even when disabled globally (needed for widget integration)
3. **Checkout URL:** Hardcoded to `/checkout/` instead of `wc_get_checkout_url()` to avoid misconfiguration
4. **Standard WooCommerce Types:** Never modify standard product types - only add custom ones
5. **Widget Discovery:** Widget loader automatically finds widgets by filename pattern - no manual registration needed

## Useful Functions

**Asset Registration:**
- `bw_register_widget_assets($slug, $dependencies = ['jquery'])` - Register CSS/JS for a widget
- `bw_enqueue_widget_assets($slug)` - Enqueue registered widget assets

**Helper Functions:**
- `bw_fpw_get_related_tags_data($post_type, $category, $subcategories)` - Get tags for category/subcategories
- `bw_fpw_get_price_markup($post_id)` - Get formatted price HTML for product

**WooCommerce:**
- `bw_mew_get_checkout_settings()` - Get checkout customization settings
- `bw_mew_get_social_login_url($provider)` - Build social login URL

## Testing

When adding or modifying product types, refer to the testing checklist in `PRODUCT_TYPES_IMPLEMENTATION.md`.

## Plugin Submodules

### Cart Popup (`cart-popup/`)
- Side-sliding cart panel with AJAX
- Configurable colors, width, overlay
- Settings page: **Blackwork > Cart Popup**

### Coming Soon (`BW_coming_soon/`)
- Full-page coming soon mode
- Video background support
- Brevo newsletter integration
- Settings page: **Blackwork > Coming Soon**

### Site Settings (`admin/class-blackwork-site-settings.php`)
- Unified settings page: **Blackwork > Site Settings**
- Consolidates all plugin settings in one location
