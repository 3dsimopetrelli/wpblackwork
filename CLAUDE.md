# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**BW Elementor Widgets** is a WordPress plugin that provides custom Elementor widgets, a server-rendered custom header module, and extensive WooCommerce customizations for the BlackWork theme.

## Development Environment

This is a WordPress plugin located at:
```
wp-content/plugins/wpblackwork/
```

The plugin requires:
- WordPress
- Elementor page builder
- WooCommerce (for e-commerce features)

## Commands

### Mandatory checks after PHP changes (from `AGENTS.md`)

- After every PHP code change, run `php -l <file>` on each modified PHP file.
- After every task that modifies PHP, run `composer run lint:main`.
- Report check results in the final response. If a check cannot run, state why.

### Composer scripts

- `composer run lint:main` — phpcs against the project ruleset (`phpcs.xml.dist`). Default lint. `blackwork-core-plugin.php` is excluded here (tracked separately via `lint:legacy`).
- `composer run lint:changed` — phpcs only on PHP files changed vs `HEAD` (uses `git diff --name-only`).
- `composer run lint:strict` — full WordPress standard against `admin includes metabox templates woocommerce cart-popup BW_coming_soon` (no exclusions).
- `composer run lint:legacy` — phpcs WordPress standard against `blackwork-core-plugin.php` (legacy baseline).
- `composer run lint:fix:legacy` — phpcbf auto-fix for the legacy bootstrap file.

See `docs/20-development/linting-and-phpcs-baseline.md` for the baseline strategy.

## Documentation

`docs/` is the primary source of feature-level documentation. Check it before grepping the codebase:

- `docs/00-overview/` — project overview
- `docs/10-architecture/` — high-level architecture (Elementor widget architecture, checkout runtime, theme-builder-lite)
- `docs/20-development/` — dev workflow, linting, admin-UI guidelines, woocommerce template overrides
- `docs/30-features/` — per-feature docs: cart-popup, checkout, duplicate-page, elementor-widgets, header, import-products, media-folders, my-account, navigation, presentation-slide, product-grid, product-slide, product-types, redirect, reviews, search, smart-header, system-status, theme-builder-lite
- `docs/40-integrations/`, `docs/50-ops/`, `docs/60-adr/`, `docs/99-archive/`

## Git Conventions

- Never include references to Claude or Claude Code in commit messages or PR descriptions
- Do not add "Co-Authored-By: Claude" or similar attribution lines

## Plugin Architecture

### Main Entry Point

`blackwork-core-plugin.php` - Main plugin file that:
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

3. **Top-level submodules**
   - **Cart Popup** (`cart-popup/`) - Side-sliding cart with AJAX updates
   - **Coming Soon** (`BW_coming_soon/`) - Coming soon page with video background and newsletter integration
   - **Site Settings** (`admin/class-blackwork-site-settings.php`) - Unified settings page

4. **Modules under `includes/modules/`** (server-rendered features, no Elementor dependency)
   - `header/` - Custom header module (replaces old `bw-search`, `bw-navshop`, `bw-navigation` Elementor widgets)
   - `link-page/` - Link page module (actively maintained — see recent commits)
   - `search-engine/` and `search-surface/` - Search backend + frontend surface
   - `elementor-sticky-sidebar/` - Sticky sidebar behaviour for Elementor
   - `media-folders/` - Media library folder organization
   - `reviews/` - Reviews module
   - `system-status/` - System status / diagnostics
   - `theme-builder-lite/` - Lightweight theme builder

5. **Other `includes/` entry points**
   - `class-bw-duplicate-page.php` - Page/post duplication
   - `class-bw-redirects.php` - Redirect management
   - `category-url-field.php` - Custom URL field on categories
   - `components/product-card/` - Reusable product card component
   - `integrations/`, `Gateways/`, `Stripe/`, `Utils/`, `admin/` - Integrations, payment plumbing, utilities, and admin glue

### Widget System

All widgets are located in `includes/widgets/` and follow this pattern:
- Filename: `class-bw-{name}-widget.php`
- Class name: `Widget_Bw_{Name}` or `BW_{Name}_Widget`
- Auto-discovered and registered by the widget loader

**Current widgets** (in `includes/widgets/`, grouped by purpose):

- *Product display*: `product-grid` (filterable grid — see `docs/30-features/product-grid/`), `product-slider`, `product-details`, `product-description`, `product-breadcrumbs`, `price-variation`, `title-product`, `related-products`, `related-post`, `tags`, `reviews`
- *Sliders / showcases*: `basic-slide`, `hero-slide`, `presentation-slide`, `showcase-slide`, `mosaic-slider`, `static-showcase`
- *Banners / decoration*: `animated-banner`, `psychadelic-banner`, `big-text`, `divider`, `trust-box`
- *Navigation / CTAs*: `about-menu`, `button`, `go-to-app`, `newsletter-subscription`

Note: header Elementor widgets (`bw-search`, `bw-navshop`, `bw-navigation`) were removed after migration to the custom header module.

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

Located in `includes/product-types/`. See `docs/30-features/product-types/` for detailed documentation.

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

Located in `blackwork-core-plugin.php`:

1. **Live Product Search** (`bw_live_search_products`)
   - Endpoint: `wp_ajax_bw_live_search_products`
   - Searches products with category/type filters
   - Returns JSON with product data
   - Contract preserved for custom header search (`bw_search_nonce`, `action: bw_live_search_products`)

2. **Product Grid**
   - `bw_fpw_get_subcategories` - Get child categories
   - `bw_fpw_get_tags` - Get tags for category
   - `bw_fpw_filter_posts` - Filter posts by category/subcategories/tags
   - Uses transient caching (3-5 minutes) for performance
   - Skips cache for random order results
   - Rate limiting via `bw_fpw_is_throttled_request()`: anonymous users 60/50/35 req/min, authenticated users 300/300/200 req/min (keyed by user ID, not IP)
   - See `docs/30-features/product-grid/product-grid-architecture.md` for full data-attribute contract and JS architecture

## Metaboxes

Located in `metabox/`:
- `digital-products-metabox.php` - Digital product fields
- `images-showcase-metabox.php` - Image showcase fields
- `artist-name-metabox.php` - Artist name field
- `variation-license-html-field.php` - License HTML for variations

## Dynamic Tags

Located in `includes/dynamic-tags/`:
- `class-bw-artist-name-tag.php` - Elementor dynamic tag for artist name

## BW Static Showcase Widget

`class-bw-static-showcase-widget.php` — Two-column product showcase driven by a dedicated metabox (`metabox/digital-products-metabox.php`). Two notable points worth knowing when touching it:

- **Product resolution** has two modes: manual (`product_id` Elementor control) and metabox (`use_metabox_product = on` → reads `_bw_showcase_linked_product` from the current post, falling back to the current post).
- **Meta read is a single batched `get_post_meta($product_id)` call** wrapped in a `$get_meta($key)` closure, with legacy meta keys (e.g. `_product_showcase_image`, `_product_color`, `_product_size_mb`) read as fallbacks. Don't add per-key `get_post_meta` calls in `render()`.

Full meta-key reference and admin/asset wiring details live in the widget file itself and the metabox file.

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
6. **Product Grid PHP→JS contract:** render settings (`image_size`, `image_mode`, `hover_effect`, `open_cart_popup`) are passed from PHP to JS exclusively via `data-*` attributes on `.bw-fpw-grid`. Never hardcode these in JS — always add a data-attribute in PHP first.

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

When adding or modifying product types, refer to the testing checklist under `docs/30-features/product-types/`.

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
