# Widget Inventory

## Runtime inventory (from `includes/widgets/`)

Canonical transition note:
- `BW Product Grid` is the canonical current name for the former `bw-filtered-post-wall` widget.
- Canonical runtime identifiers:
  - slug: `bw-product-grid`
  - class: `BW_Product_Grid_Widget`
  - file: `includes/widgets/class-bw-product-grid-widget.php`

| Widget Slug | Class File | Family (Current) | Notes |
|---|---|---|---|
| `bw-about-menu` | `includes/widgets/class-bw-about-menu-widget.php` | UI/Navigation | non-product |
| `bw-animated-banner` | `includes/widgets/class-bw-animated-banner-widget.php` | Content/UI | non-product |
| `bw-button` | `includes/widgets/class-bw-button-widget.php` | UI Utility | non-product |
| `bw-divider` | `includes/widgets/class-bw-divider-widget.php` | UI Utility | non-product |
| `bw-newsletter-subscription` | `includes/widgets/class-bw-newsletter-subscription-widget.php` | Marketing / Lead Capture | fixed-design Brevo subscription widget governed by Mail Marketing settings |
| `bw-reviews` | `includes/widgets/class-bw-reviews-widget.php` | Product Reviews / Trust | thin adapter over the custom Reviews module; premium product-review widget for single-product surfaces |
| `bw-product-breadcrumbs` | `includes/widgets/class-bw-product-breadcrumbs-widget.php` | Product Utility | single-product breadcrumb widget |
| `bw-product-description` | `includes/widgets/class-bw-product-description-widget.php` | Product Utility | single-product description widget |
| `bw-product-grid` | `includes/widgets/class-bw-product-grid-widget.php` | Query Grid | canonical wall/query-grid widget |
| `bw-presentation-slide` | `includes/widgets/class-bw-presentation-slide-widget.php` | Presentation Slider | specialized presentation/gallery slider; horizontal and responsive-vertical runtime are Embla-based |
| `bw-price-variation` | `includes/widgets/class-bw-price-variation-widget.php` | Product Pricing | non-card pricing widget with current-product review summary and direct-checkout shortcut support |
| `bw-product-details-table` | `includes/widgets/class-bw-product-details-widget.php` | Product Details | non-card details widget |
| `bw-title-product` | `includes/widgets/class-bw-title-product-widget.php` | Product Utility | single-product title widget |
| `bw-product-slide` | `includes/widgets/class-bw-product-slide-widget.php` | Product Slider | canonical product slider target |
| `bw-related-post` | `includes/widgets/class-bw-related-post-widget.php` | Query/List | non-product |
| `bw-related-products` | `includes/widgets/class-bw-related-products-widget.php` | Product Grid | usa `BW_Product_Card_Component`; griglia proporzionale; colonne desktop configurabili; tablet/mobile fissi a 2 |
| `bw-slick-slider` | `includes/widgets/class-bw-slick-slider-widget.php` | Generic Slider | rationalize with slide-showcase |
| `bw-slide-showcase` | `includes/widgets/class-bw-slide-showcase-widget.php` | Showcase Slider | rationalize with slick-slider |
| `bw-static-showcase` | `includes/widgets/class-bw-static-showcase-widget.php` | Showcase Static | non-slick static showcase |
| `bw-tags` | `includes/widgets/class-bw-tags-widget.php` | Taxonomy/UI | non-slider |

## Visible editor titles (selected canonical mappings)
- `bw-slick-slider` -> `BW-UI Product Slider` (visible title)
- `bw-product-slide` -> `BW-SP Gallery Product` (visible title)
- `bw-product-breadcrumbs` -> `BW-SP Product Breadcrumbs` (visible title)
- `bw-product-description` -> `BW-SP Product Description` (visible title)
- `bw-title-product` -> `BW Title Product` (visible title)
- Internal slugs above remain the runtime authority.

## Current implementation deltas (status)
- `BW Product Grid` naming convergence completed:
  - canonical visible title: `BW Product Grid`
  - canonical slug: `bw-product-grid`
  - canonical class: `BW_Product_Grid_Widget`
- `bw-title-product` controls (final state):
  - `html_tag`: H1–H6, div, span, p (default: H1)
  - `title_source`: `product` (single-product title, context-resolved), `category` (product-category name), `page` (page title), `text` (arbitrary custom text)
  - `product_id`: explicit product ID override for editor preview (source: `product` only)
  - `page_id`: explicit page ID override for editor preview (source: `page` only)
  - `term_id`: explicit category ID override for editor preview (source: `category` only)
  - `custom_text`: static text input (source: `text` only)
  - Alignment (responsive), Typography group control (Elementor native)
- `bw-product-description` controls (initial state):
  - `product_id`: explicit product ID override for editor preview
  - `description_source`: `description`, `short_description`, `both`
  - renders product long description, short description, or both with preserved HTML markup
  - Alignment (responsive), Typography group control (Elementor native), Text Color
- `bw-product-breadcrumbs` controls (current state):
  - `product_id`: explicit product ID override for editor preview
  - deterministic breadcrumb chain for current Woo single product
  - Content controls:
    - `show_home`
    - `show_shop`
    - `show_category_path`
    - `title_word_limit` (word-based truncation on the current product crumb only; `0` = unlimited)
  - Container style controls: background, padding, radius
  - Text style controls: alignment, typography, link/current/separator colors
- `bw-presentation-slide` controls/runtime (audit state 2026-03-20):
  - visible title: `BW-UI Presentation Slider`
  - layout modes: `horizontal`, `vertical`
  - image source: `custom` gallery or WooCommerce product gallery query
  - **Slider Settings** (horizontal mode):
    - `infinite_loop`, `autoplay`, `autoplay_speed`, `pause_on_hover`
    - `drag_free` — free-scroll, no snap
    - `touch_drag` — enable/disable swipe on touch devices (mobile & tablet); desktop mouse drag always active; implemented via Embla `watchDrag` callback filtering `PointerEvent.pointerType`
    - `slide_align` — start / center / end
  - horizontal mode breakpoint repeater (`breakpoints`):
    - slides to show / scroll
    - show arrows / show dots — **CSS-managed** (not JS): `render_breakpoint_css()` emits scoped `@media (max-width: Xpx)` rules sorted descending; breakpoints must not be assumed to be sorted in JS for this purpose
    - center mode / variable width / fixed slide width
    - per-breakpoint image height mode and dimensions
  - vertical mode:
    - desktop elevator layout with thumbnail rail + scroll tracking
    - optional responsive fallback to synchronized Embla main/thumb sliders
  - popup overlay gallery:
    - overlay appended to `<body>` on init, removed on `destroy()`
    - sticky popup header
    - close button renders explicit text `Close`
    - popup typography/close sizing fixed in CSS (no user controls)
    - title defaults: desktop/tablet `16px / 20px lh`, mobile `12px / 18px lh`
    - popup images constrained to viewport height via `max-height: calc(100dvh - header - padding)`
    - popup open/close uses `opacity` + `visibility` transitions (not `display` toggling) — Safari-safe
    - popup opening requires a real `pointerdown → pointerup` sequence on the same target
    - body scroll locked via iOS-safe `position:fixed` pattern during popup open
  - custom cursor: single on/off toggle (`enable_custom_cursor`); fixed glassmorphism design; desktop-only
  - arrow/dots visibility:
    - **CSS-only**: emitted by `render_breakpoint_css()` as scoped `@media` rules
    - JS functions `_updateArrowsVisibility()` and `_updateDotsVisibility()` have been removed
    - `showArrows` / `showDots` removed from JS `data-config` payload
    - arrows HTML rendered without `style=""` inline — CSS media rules take immediate effect
  - current asset/runtime authority depends on:
    - `embla-js`, `embla-autoplay-js`, `bw-embla-core-js`, `bw-embla-core-css`
    - `bw-presentation-slide-script`
  - shared Embla base styles/classes in `bw-embla-core.css`; widget-local CSS owns popup, cursor, arrows, dots, elevator layout, responsive skin
- `bw-product-grid`: now supports `Enable Filter = yes/no` (can run as filtered grid or simple grid).
- `bw-newsletter-subscription`:
  - fixed-design widget
  - minimal editor controls only
  - business copy/list/opt-in behavior delegated to `Blackwork Site -> Mail Marketing -> Subscription`
  - public submit handled through nonce-protected server-side AJAX endpoint
- `bw-reviews`:
  - minimal editor controls only (`product_id` override)
  - business/data authority delegated to `includes/modules/reviews/`
  - server-rendered shell with JS-enhanced modal, sort, and load-more behavior
  - optional global review fallback is owned by Reviews Settings, not by widget-local state
- `bw-price-variation`:
  - now supports a `Rates` content section for current-product BW Reviews summary
  - can show/hide review count in the inline summary
  - now supports a `More payment options` checkout shortcut under Add to Cart
  - checkout shortcut follows the currently selected variation, or the default variation at initial render
- `bw-product-grid` product rendering is delegated to `BW_Product_Card_Component` in both:
  - widget server render path
  - AJAX response path (`bw_fpw_filter_posts`).
- `bw-slick-slider` product rendering path is delegated to `BW_Product_Card_Component`.
- `bw-related-products` product rendering path is delegated to `BW_Product_Card_Component`.
- `bw-related-products` refactored (2026-03): proportional image grid, simplified controls (removed Image Settings, Overlay Buttons style, Card Container style, open_cart_popup, margin_top/bottom). Desktop columns control uses `selectors` for live Elementor preview without re-render. Tablet/mobile hardcoded to 2 columns in CSS.
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
- `bw-newsletter-subscription` is a hybrid case:
  - declares normal Elementor style/script depends
  - also has channel-level runtime pre-enqueue logic for Theme Builder Lite footer injection
- Slider handles are registered centrally and consumed per widget:
  - `embla-js`, `embla-autoplay-js`, `bw-embla-core-js`, `bw-embla-core-css`
  - `bw-slick-slider-js`, `bw-product-slide-js`, `bw-presentation-slide-script`
