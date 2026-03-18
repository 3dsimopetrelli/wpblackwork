# Elementor Widget Architecture Context (wpblackwork)

## Objective
Document the current real architecture of Blackwork Elementor widgets and define the governed direction for the audit/rebuild program.

## Runtime authority (current)

### Widget location
- `includes/widgets/`

### Registration loader
- `includes/class-bw-widget-loader.php`
- Hooks:
  - `elementor/widgets/register`
  - `elementor/widgets/widgets_registered`
- Behavior:
  - scans `includes/widgets/class-bw-*-widget.php`
  - loads class files
  - supports `Widget_Bw_*` and `BW_*_Widget` class naming patterns
  - registers through compatible Elementor APIs

### Category
- Registered in `blackwork-core-plugin.php`
- Elementor category key: `blackwork`
- Editor label: `Black Work Widgets`

## Asset model (current)
- Primary asset registration authority: `blackwork-core-plugin.php`
- Mixed runtime architecture:
  - widget-level dependencies (`get_script_depends()` / `get_style_depends()`)
  - hook-based register/enqueue helpers for selected widget families
- Slider handles are registered centrally and consumed per widget.
- Mail Marketing subscription widget assets are registered centrally and can be pre-enqueued by channel runtime when the widget is rendered inside Theme Builder Lite custom footer injection.

## Current widget families (operational map)
- Product Grid / Query Grid:
  - `bw-product-grid`, `bw-related-products`
- Slider:
  - `bw-product-slide`, `bw-presentation-slide`, `bw-slick-slider`, `bw-slide-showcase`
- Product Utility:
  - `bw-price-variation`, `bw-product-details-table`, `bw-title-product`
- Content/UI utilities:
  - about-menu, button, divider, animated-banner, tags, related-post, static-showcase
- Marketing / Lead Capture:
  - `bw-newsletter-subscription`

## Current architectural problems
1. Product-card-like rendering is duplicated across multiple widget/template surfaces.
2. Slider runtime lifecycle/config logic is duplicated across multiple JS files.
3. Asset ownership is partially centralized but still fragmented by legacy paths.
4. Large Elementor control blocks are repeated across related widgets.

## Confirmed direction (governed)
- `BW_Product_Card_Renderer` -> canonical product-card markup authority.
- `bw-product-card.css` -> canonical product-card skin authority.
- Shared `slider-core` -> runtime authority for slider lifecycle.
- Shared control groups -> reduce duplicated control blocks where safe.

Decisions already fixed:
- `bw-add-to-cart` -> DELETE (completed)
- `bw-add-to-cart-variation` -> DELETE (completed)
- `bw-wallpost` -> DELETE (completed)
- `bw-product-grid` -> canonical wall/query-grid widget with optional filters
- `bw-title-product` -> canonical single-product title utility widget
- `bw-product-slide` -> canonical product slider
- `bw-presentation-slide` -> specialized presentation/gallery slider
- `bw-slick-slider` + `bw-slide-showcase` -> rationalization/merge path under review
- `bw-related-products` -> current best reference for shared product-card reuse
- `bw-newsletter-subscription` -> canonical fixed-design Brevo/Mail Marketing opt-in widget for Elementor surfaces

Visible editor title alignment (current):
- `bw-slick-slider` -> `BW-UI Product Slider`
- `bw-product-slide` -> `BW-SP Gallery Product`
- `bw-title-product` -> `BW Title Product`
- Scope note: title alignment affects editor labeling only; slug/runtime authority is unchanged.
- Editor identity exception:
  - `bw-title-product` uses a slug-scoped panel-family mapping to receive the WooCommerce/BW-SP purple editor card without relying on a `BW-SP` title prefix.
- Mail Marketing widget note:
  - `bw-newsletter-subscription` intentionally stays as a neutral utility title because its authority surface is Mail Marketing, not a product/widget family namespace.

## Channel-governed widget pattern
`bw-newsletter-subscription` is the current reference for a channel-governed widget pattern:

- per-instance Elementor controls remain intentionally minimal
- business behavior lives in admin settings (`Blackwork Site -> Mail Marketing -> Subscription`)
- frontend writes go through a dedicated service/channel runtime instead of direct client-side API calls
- public endpoint hardening, consent, and Brevo coupling are owned outside the widget render class

## Migration doctrine
- No big-bang rewrite.
- Family-by-family migration waves.
- Preserve runtime determinism and Elementor editor compatibility during convergence.
- Each wave requires regression checks and closure artifacts.

## Related docs
- `docs/30-features/elementor-widgets/README.md`
- `docs/30-features/elementor-widgets/widget-inventory.md`
- `docs/30-features/elementor-widgets/architecture-direction.md`
- `docs/30-features/elementor-widgets/rationalization-policy.md`
- `docs/30-features/elementor-widgets/migration-sequence.md`
