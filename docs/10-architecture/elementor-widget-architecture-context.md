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
  - container/runtime extensions loaded from `includes/modules/` when behavior is not widget-specific
- Sticky sidebar extension follows the container/runtime extension path rather than widget registration. It uses JS-based `position:fixed` (not CSS `position:sticky`) because Elementor ancestor containers apply `overflow:hidden`, which silently suppresses CSS sticky.
- Slider handles are registered centrally and consumed per widget.
- Mail Marketing subscription widget assets are registered centrally and can be pre-enqueued by channel runtime when the widget is rendered inside Theme Builder Lite custom footer injection.

## Current widget families (operational map)
- Product Grid / Query Grid:
  - `bw-product-grid`, `bw-related-products`
- Slider:
  - `bw-product-slide`, `bw-presentation-slide`, `bw-slick-slider`, `bw-slide-showcase`
- Product Utility:
  - `bw-price-variation`, `bw-product-details-table`, `bw-title-product`, `bw-product-description`, `bw-product-breadcrumbs`
- Product Reviews / Trust:
  - `bw-reviews`
- Content/UI utilities:
  - about-menu, button, divider, animated-banner, tags, related-post, static-showcase
- Marketing / Lead Capture:
  - `bw-newsletter-subscription`

## Current architectural problems
1. Product-card-like rendering is duplicated across multiple widget/template surfaces.
2. Slider runtime lifecycle/config logic is duplicated across multiple JS files.
3. Asset ownership is partially centralized but still fragmented by legacy paths.
4. Large Elementor control blocks are repeated across related widgets.
5. Some layout behaviors needed by templates apply to Elementor containers rather than widgets and must be handled through explicit container extensions. These extensions may require JS rather than CSS when Elementor's own layout engine imposes constraints (e.g. `overflow:hidden` blocking CSS sticky).

## Confirmed direction (governed)
- `BW_Product_Card_Renderer` -> canonical product-card markup authority.
- `bw-product-card.css` -> canonical product-card skin authority.
- Shared `slider-core` -> runtime authority for slider lifecycle.
- Shared control groups -> reduce duplicated control blocks where safe.
- Shared hover-media resolution belongs to `BW_Product_Card_Component`:
  - product meta authority:
    - `_bw_slider_hover_video`
    - `_bw_slider_hover_image`
  - storefront precedence:
    - hover video
    - hover image fallback
    - no hover media fallback

Decisions already fixed:
- `bw-add-to-cart` -> DELETE (completed)
- `bw-add-to-cart-variation` -> DELETE (completed)
- `bw-wallpost` -> DELETE (completed)
- `bw-product-grid` -> canonical wall/query-grid widget with optional filters
- `bw-product-breadcrumbs` -> canonical single-product breadcrumb utility widget
  - deterministic category-path resolution remains fixed
  - instance-level content controls may suppress `Home`, `Shop`, or category path segments without changing the underlying resolution rule
  - current-title truncation is word-based and applies only to the terminal product crumb
- `bw-product-description` -> canonical single-product description utility widget
- `bw-title-product` -> canonical single-product title utility widget
- `bw-product-slide` -> canonical product slider
- `bw-presentation-slide` -> specialized presentation/gallery slider (current runtime uses Embla for horizontal and responsive-vertical flows; desktop vertical remains a custom elevator layout; popup remains widget-local and CSS-driven)
- `bw-slick-slider` + `bw-slide-showcase` -> rationalization/merge path under review
- `bw-related-products` -> current best reference for shared product-card reuse
- `bw-reviews` -> canonical custom-review widget backed by the isolated Reviews module
- `bw-newsletter-subscription` -> canonical fixed-design Brevo/Mail Marketing opt-in widget for Elementor surfaces

Current product-widget integration note:
- `bw-price-variation` remains a pricing/license authority widget.
- It can consume a compact read-only review summary from the Reviews module for the current product only.
- It must not become a second review-authority surface.

Current shared media note:
- the WooCommerce product admin metabox labeled `Hover Media` is the current editor authority for product-card hover media
- widgets consuming `BW_Product_Card_Component` inherit hover-video support automatically when configured on the product
- hover-video storefront behavior is component-governed:
  - desktop hover/focus starts playback from the beginning
  - hover exit resets the video
  - nested `<source>` markup is intentionally preserved via a minimal allowlist, because generic post-safe sanitization strips `<source>` and would leave only the poster frame

Current popup/runtime note for `bw-presentation-slide`:
- popup overlay is moved to `<body>` at runtime
- popup style customization was intentionally reduced back to fixed CSS defaults
- title and close text sizing are now owned directly by widget CSS, not Elementor popup style controls
- popup opening is guarded by a real press sequence (`pointerdown` then `pointerup` on the same target)
- horizontal arrow buttons start hidden in markup and are made visible only after responsive JS confirms the active breakpoint should show them

Visible editor title alignment (current):
- `bw-slick-slider` -> `BW-UI Product Slider`
- `bw-product-slide` -> `BW-SP Gallery Product`
- `bw-product-breadcrumbs` -> `BW-SP Product Breadcrumbs`
- `bw-product-description` -> `BW-SP Product Description`
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
