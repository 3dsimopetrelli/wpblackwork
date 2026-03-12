# Widget Inventory

## Runtime inventory (from `includes/widgets/`)

| Widget Slug | Class File | Family (Current) | Notes |
|---|---|---|---|
| `bw-about-menu` | `includes/widgets/class-bw-about-menu-widget.php` | UI/Navigation | non-product |
| `bw-animated-banner` | `includes/widgets/class-bw-animated-banner-widget.php` | Content/UI | non-product |
| `bw-button` | `includes/widgets/class-bw-button-widget.php` | UI Utility | non-product |
| `bw-divider` | `includes/widgets/class-bw-divider-widget.php` | UI Utility | non-product |
| `bw-product-grid` | `includes/widgets/class-bw-product-grid-widget.php` | Query Grid | canonical wall/query-grid widget |
| `bw-presentation-slide` | `includes/widgets/class-bw-presentation-slide-widget.php` | Presentation Slider | specialized |
| `bw-price-variation` | `includes/widgets/class-bw-price-variation-widget.php` | Product Pricing | non-card pricing widget |
| `bw-product-details-table` | `includes/widgets/class-bw-product-details-widget.php` | Product Details | non-card details widget |
| `bw-product-slide` | `includes/widgets/class-bw-product-slide-widget.php` | Product Slider | canonical product slider target |
| `bw-related-post` | `includes/widgets/class-bw-related-post-widget.php` | Query/List | non-product |
| `bw-related-products` | `includes/widgets/class-bw-related-products-widget.php` | Product Grid | uses `BW_Product_Card_Component` |
| `bw-slick-slider` | `includes/widgets/class-bw-slick-slider-widget.php` | Generic Slider | rationalize with slide-showcase |
| `bw-slide-showcase` | `includes/widgets/class-bw-slide-showcase-widget.php` | Showcase Slider | rationalize with slick-slider |
| `bw-static-showcase` | `includes/widgets/class-bw-static-showcase-widget.php` | Showcase Static | non-slick static showcase |
| `bw-tags` | `includes/widgets/class-bw-tags-widget.php` | Taxonomy/UI | non-slider |

## Visible editor titles (selected canonical mappings)
- `bw-slick-slider` -> `BW-UI Product Slider` (visible title)
- `bw-product-slide` -> `BW-SP Gallery Product` (visible title)
- Internal slugs above remain the runtime authority.

## Current implementation deltas (status)
- `bw-product-grid`: now supports `Enable Filter = yes/no` (can run as filtered grid or simple grid).
- `bw-product-grid` product rendering is delegated to `BW_Product_Card_Component` in both:
  - widget server render path
  - AJAX response path (`bw_fpw_filter_posts`).
- `bw-slick-slider` product rendering path is delegated to `BW_Product_Card_Component`.
- `bw-related-products` product rendering path is delegated to `BW_Product_Card_Component`.
- Removed widgets (governed removal wave completed):
  - `bw-wallpost` (replacement: `bw-product-grid` + `Enable Filter = No`)
  - `bw-add-to-cart`
  - `bw-add-to-cart-variation`

## Registration and loading
- Loader: `includes/class-bw-widget-loader.php`
- Loader hooks:
  - `elementor/widgets/register`
  - `elementor/widgets/widgets_registered`
- Loader behavior:
  - scans `includes/widgets/class-bw-*-widget.php`
  - resolves class names in `Widget_Bw_*` and `BW_*_Widget` patterns
  - supports new and legacy Elementor registration APIs

## Asset reality (high level)
- Shared registration authority exists in `blackwork-core-plugin.php` (handles + register functions).
- Runtime remains mixed:
  - per-widget `get_script_depends()` and `get_style_depends()`
  - additional hook-based register/enqueue flows for some widgets
- Slider handles are registered centrally and consumed per widget:
  - `slick-js`, `slick-css`
  - `bw-slick-slider-js`, `bw-product-slide-js`, `bw-presentation-slide-script`
