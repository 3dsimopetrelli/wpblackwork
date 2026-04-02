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
| `bw-basic-slide` | `includes/widgets/class-bw-basic-slide-widget.php` | Gallery / Slider | dual-mode image widget: Embla `Slide` mode or responsive `Wall` mode with optional bottom gradient |
| `bw-psychadelic-banner` | `includes/widgets/class-bw-psychadelic-banner-widget.php` | Content/UI | CSS-only psychedelic label-loop banner with responsive central PNG art, viewport-driven label sizing, and optional marquee motion |
| `bw-big-text` | `includes/widgets/class-bw-big-text-widget.php` | Editorial Typography | premium statement widget with auto-balance, controlled-width, and manual editorial line grouping |
| `bw-button` | `includes/widgets/class-bw-button-widget.php` | UI Utility | non-product |
| `bw-divider` | `includes/widgets/class-bw-divider-widget.php` | UI Utility | non-product |
| `bw-newsletter-subscription` | `includes/widgets/class-bw-newsletter-subscription-widget.php` | Marketing / Lead Capture | governed Brevo subscription widget with `Style Footer` and `Style Section` variants; status `Almost ready`, quality `~9.5/10`, phase `Final manual validation` |
| `bw-reviews` | `includes/widgets/class-bw-reviews-widget.php` | Product Reviews / Trust | thin adapter over the custom Reviews module; premium product-review widget for single-product surfaces |
| `bw-product-breadcrumbs` | `includes/widgets/class-bw-product-breadcrumbs-widget.php` | Product Utility | single-product breadcrumb widget |
| `bw-product-description` | `includes/widgets/class-bw-product-description-widget.php` | Product Utility | single-product description widget |
| `bw-product-grid` | `includes/widgets/class-bw-product-grid-widget.php` | Query Grid | canonical wall/query-grid widget |
| `bw-presentation-slide` | `includes/widgets/class-bw-presentation-slide-widget.php` | Presentation Slider | specialized presentation/gallery slider; horizontal and responsive-vertical runtime are Embla-based |
| `bw-hero-slide` | `includes/widgets/class-bw-hero-slide-widget.php` | Hero / Banner | static-first hero widget; future-ready `Slide` mode surface; background image, centered copy, and responsive glass CTA grid |
| `bw-price-variation` | `includes/widgets/class-bw-price-variation-widget.php` | Product Pricing | non-card pricing widget with current-product review summary and direct-checkout shortcut support |
| `bw-trust-box` | `includes/widgets/class-bw-trust-box-widget.php` | Product Trust / Support | standalone trust/support stack widget for curated review slider, fixed review box, info cards, and FAQ CTA |
| `bw-product-details-table` | `includes/widgets/class-bw-product-details-widget.php` | Product Details | non-card details widget |
| `bw-title-product` | `includes/widgets/class-bw-title-product-widget.php` | Product Utility | single-product title widget |
| `bw-product-slider` | `includes/widgets/class-bw-product-slider-widget.php` | Product Slider | canonical current product slider; Embla-based; query-driven; shared product-card delegation |
| `bw-mosaic-slider` | `includes/widgets/class-bw-mosaic-slider-widget.php` | Editorial Slider | Embla-based mixed-content slider; 4 desktop mosaic variants; auto-scale and square-tile modes; responsive partial-slide reveal; products reuse `BW_Product_Card_Component`; active viewport image loading is promoted client-side while hidden fallback markup is demoted to lazy |
| `bw-showcase-slide` | `includes/widgets/class-bw-showcase-slide-widget.php` | Showcase Slider | Embla-based curated showcase slider driven by the product showcase metabox; no popup runtime; digital products render badge pills, physical products render footer text lines |
| `bw-related-post` | `includes/widgets/class-bw-related-post-widget.php` | Query/List | non-product |
| `bw-related-products` | `includes/widgets/class-bw-related-products-widget.php` | Product Grid | usa `BW_Product_Card_Component`; griglia proporzionale; colonne desktop configurabili; tablet/mobile fissi a 2 |
| `bw-slick-slider` | `includes/widgets/class-bw-slick-slider-widget.php` | Generic Slider | rationalize with slide-showcase |
| `bw-slide-showcase` | `includes/widgets/class-bw-slide-showcase-widget.php` | Showcase Slider | rationalize with slick-slider |
| `bw-static-showcase` | `includes/widgets/class-bw-static-showcase-widget.php` | Showcase Static | non-slick static showcase; image radius is owned only by `Border Radius Immagini` with a shared default of `8px` across all three media surfaces and is applied directly to media wrappers + images at render time; `Content Padding` is the overlay-offset authority for the large-image text/footer block; right-column inner-corner radius still has an open rendering inconsistency and is not considered solved |
| `bw-tags` | `includes/widgets/class-bw-tags-widget.php` | Taxonomy/UI | non-slider |

## Visible editor titles (selected canonical mappings)
- `bw-slick-slider` -> `BW-UI Product Slider` (visible title)
- `bw-big-text` -> `BW-UI Big Text` (visible title)
- `bw-basic-slide` -> `BW-UI Basic Slide` (visible title)
- `bw-product-slider` -> `BW-UI Product Slider` (visible title)
- `bw-psychadelic-banner` -> `BW-UI Psychadelic Banner` (visible title)
- `bw-product-breadcrumbs` -> `BW-SP Product Breadcrumbs` (visible title)
- `bw-product-description` -> `BW-SP Product Description` (visible title)
- `bw-title-product` -> `BW Title Product` (visible title)
- Internal slugs above remain the runtime authority.

## Elementor panel family-color mapping (current)
- `bw-product-grid` -> UI family (`bw-family-ui`)
- `bw-presentation-slide` -> dedicated presentation-slide family (`bw-family-ui-ps`)
- `bw-title-product` -> SP family (`bw-family-sp`) via explicit slug map/title exception
- `bw-reviews` -> SP family (`bw-family-sp`) via explicit slug map/title exception
- `bw-go-to-app` -> UI family (`bw-family-ui`)
- `bw-newsletter-subscription` -> UI family (`bw-family-ui`)
- `bw-showcase-slide` -> UI family (`bw-family-ui`) via the `BW-UI ...` visible title prefix

Important runtime note:
- the editor panel no longer relies only on visible title prefixes to decide colors
- family assignment is now a separate mapping system documented in `editor-panel-widget-families.md`

## Current implementation deltas (status)
- `BW Product Grid` naming convergence completed:
  - canonical visible title: `BW Product Grid`
  - canonical slug: `bw-product-grid`
  - canonical class: `BW_Product_Grid_Widget`
- `bw-big-text` controls (initial state):
  - `text_content`: constrained textarea with limited inline HTML allowlist
  - `composition_mode`: `auto_balance`, `controlled_width`, `editorial_lines`
  - `max_text_width`: responsive; `ch`, `rem`, `%`, `vw`, `px`
  - `text_align`: responsive left / center / right
  - `font_size_mode`: `fluid`, `fixed`
  - fluid controls: min/max font size + min/max viewport
  - style controls: typography, text color, line height, letter spacing, section padding, editorial line gap
  - editorial/manual mode maps each non-empty textarea newline to a dedicated line group
- `bw-title-product` controls (final state):
  - `html_tag`: H1–H6, div, span, p (default: H1)
  - `title_source`: `product` (single-product title, context-resolved), `category` (product-category name), `page` (page title), `text` (arbitrary custom text)
  - `product_id`: explicit product ID override for editor preview (source: `product` only)
  - `page_id`: explicit page ID override for editor preview (source: `page` only)
  - `term_id`: explicit category ID override for editor preview (source: `category` only)
  - `custom_text`: static text input (source: `text` only)
  - style controls:
    - responsive `Max Text Width` (`ch`, `rem`, `%`, `vw`, `px`)
    - `Font Size Mode`: `fluid`, `fixed`
    - `Fixed Font Size`
    - fluid controls: min/max font size + min/max viewport
    - responsive alignment
    - Typography group control (font family/weight/etc.; default weight `500`)
- `bw-product-description` controls (initial state):
  - `product_id`: explicit product ID override for editor preview
  - `description_source`: `description`, `short_description`, `both`
  - renders product long description, short description, or both with preserved HTML markup
  - Alignment (responsive), Typography group control (Elementor native), Text Color
- `bw-product-details-table` controls/runtime (current state):
  - content types:
    - `Product Details`
    - `Compatibility`
    - `Info Box`
  - reuses the same accordion shell across the supported content types
  - product-data authority remains the existing WooCommerce `Product Details` metabox in `metabox/bibliographic-details-metabox.php`
  - compatibility rows are edited in that same metabox via checkbox fields
  - untouched products default compatibility to all enabled
  - explicitly saved empty compatibility renders no frontend block
- `bw-related-products` controls/runtime (current state):
  - desktop columns configurable; tablet/mobile remain fixed at 2 columns
  - `Layout > Show Overlay Actions on Tablet & Mobile` controls the `<1025px` visibility of `View Product / Add to Cart`
  - default tablet/mobile state is off
  - the behavior is wrapper-scoped and does not change the shared product-card contract for other widgets
- `bw-product-grid` controls/runtime (current state):
  - `Layout`:
    - `Infinite Scroll`
    - `Initial Items`
    - `Load Batch Size`
    - `Desktop Columns`: `3`, `4`, `5`, `6`
    - `Container Max Width (px)`
    - `Masonry Effect`
    - `Show Title`
    - `Show Description`
    - `Show Price`
    - `Disable Hover Actions on Tablet & Mobile`
  - `Filter Settings`:
    - `Show Filters` is the current filtered/simple-grid switch
    - `Enable Responsive Filter Mode`
    - `Drawer Opening` (`left` / `right`)
    - default category
    - show categories / subcategories / tags
    - filter bar titles
    - show `All` option
  - `Style > Text`:
    - content gap
    - title color / typography / padding
    - description color / typography / padding
    - price color / typography / padding
  - filter runtime:
    - legacy path still supports desktop inline filter rows + classic mobile slide-out panel
    - responsive drawer mode promotes the drawer interaction to desktop too
    - discovery toolbar includes global search, result count, reset, selected quick-filter pills, and drawer trigger
    - drawer shell reuses cart-popup visual language and now supports `left` or `right` opening
    - accordion labels in the responsive drawer are currently `Categories` and `Style / Subject`
    - mobile trigger/search use the white rounded pill + green icon shell treatment
    - mobile first paint is CSS-managed to avoid desktop-filter flash before JS init
  - internal fixed/runtime-only values:
    - `image_size = large`
    - `image_mode = proportional`
    - `hover_effect = yes`
    - `open_cart_popup = no`
    - filter breakpoint is hardcoded to `900`
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
- `bw-showcase-slide` controls/runtime (audit state 2026-03-29):
  - visible title: `BW-UI Showcase Slide`
  - manual product ID composition sourced from the showcase metabox
  - horizontal Embla-only runtime; no popup surface
  - slide content authority:
    - title: `_bw_showcase_title` with product-title fallback
    - description: `_bw_showcase_description`
    - image: `_bw_showcase_image`, fallback `_product_showcase_image`, then featured image
    - text color: `_bw_texts_color`
    - CTA text/link: `_product_button_text`, `_product_button_link`, fallback permalink
  - `Style > Link Button` now exposes responsive typography for the green CTA text pill only
  - `Style > Text` exposes typography groups for title, subtitle, labels, and physical-info lines
  - `Style > Images` owns border radius, spacing between slides, and image size
  - `Style > Custom Cursor` exposes the cursor on/off switch
  - horizontal breakpoint repeater now supports:
    - slides to scroll
    - show arrows / show dots
    - `Start Offset Left` for first-card breathing room at the viewport edge
    - fixed `Frame Ratio` modes: `3:2`, `4:3`, `1:1`, `16:9`
    - `Frame Fit` (`cover` / `contain`) when a ratio lock is active
    - `Classic Photo Size` presets for `3:2` peek layouts: `Balanced`, `Large`, `XL Peek`
    - legacy `variable width` / `slide width` / image-height controls remain available only in `Free / Existing Controls`
  - current width authority depends on the selected mode:
    - `Free / Existing Controls` -> legacy width/image-height contract
    - fixed frame ratio -> ratio-locked card with fit-mode authority
    - `Classic Photo (3:2)` -> curated width presets with the next slide intentionally peeking into view
  - image/layering contract:
    - first slide image gets `fetchpriority="high"` / `decoding="sync"`
    - second slide image is also promoted to eager loading
    - showcase media uses an isolated stacking context
    - image stays at `z-index: 0`
    - overlay stays above it at `z-index: 1`
  - mobile CTA contract:
    - below `800px`, the detached green CTA pair is hidden
    - the whole slide becomes the tap target when a CTA URL exists
- `bw-product-grid`: supports filtered/simple grid mode via `Show Filters = yes/no`.
  - `Desktop Columns` currently supports `3`, `4`, `5`, `6`
  - `Style > Text` now exposes content gap plus title/description/price color, typography, and padding controls
- `bw-newsletter-subscription`:
  - governed widget with two presentation variants: `Style Footer` and `Style Section`
  - `Style Footer` keeps the minimal legacy footer surface
  - `Style Section` adds hero-style content/media controls plus a dedicated conditional Style tab for typography, colors, overlay, glow, and content positioning
  - business copy/list/opt-in behavior delegated to `Blackwork Site -> Mail Marketing -> Subscription`
  - public submit handled through nonce-protected server-side AJAX endpoint
  - completed hardening/cleanup wave:
    - abuse throttling
    - metadata-safe fallback rules
    - PII-safe logs
    - JS-driven floating-label stability
    - submit timeout/failure recovery
    - unified name-field control model
    - simplified asset loading contract
    - staged CSS cleanup
    - conditional Brevo pre-lookup by resubscribe policy
  - current status:
    - `Almost ready`
    - quality `~9.5/10`
    - phase `Final manual validation`
  - final manual validations still required:
    - `already_subscribed` behavior after conditional pre-lookup optimization
    - required Brevo audit attributes against the real production schema
- `bw-psychadelic-banner`:
  - decorative editorial banner widget with CSS-only marquee rows and optional central PNG art
  - content controls:
    - `Labels List`
    - `Center PNG`
    - responsive `Center Image Position`
    - responsive `Center Image Width`
    - responsive `Banner Height`
    - responsive `Inner Padding`
    - `Rows` (`2..8`)
    - `Animation` on/off
    - `Animation Speed`
  - style controls:
    - `Background Color`
    - label text / border / background colors
    - typography group for family/weight/transform
    - viewport-driven label sizing via `vw`, `vh`, `min`, `max`
    - label padding / radius
    - rows gap / labels gap
  - runtime:
    - no JS dependency
    - animated mode duplicates label groups for infinite marquee motion
    - static mode disables motion and wraps labels visibly
    - central image is rendered in a non-interactive overlay layer above the label field
- `bw-basic-slide`:
  - dual-mode image gallery widget
  - `Mode = Slide`:
    - Embla-based horizontal gallery
    - gallery-only source
    - shared image resolution selector
    - breakpoint repeater for slides-to-show / scroll, arrows, dots, `Start Offset Left`, center mode, variable width, slide width, and image height behavior
    - `Start Offset Left` supports `px | % | vw`
    - eager/lazy image loading seeded from the desktop visible-count contract
  - `Mode = Wall`:
    - CSS grid image wall, not masonry
    - responsive columns
    - responsive wall height with visual clipping only; no internal mouse/trackpad scroll surface
    - optional bottom fade gradient to suggest more content below
    - no `View all` button in the current contract
- `bw-reviews`:
  - minimal editor controls only (`product_id` override)
  - business/data authority delegated to `includes/modules/reviews/`
  - server-rendered shell with JS-enhanced modal, sort, and load-more behavior
  - optional global review fallback is owned by Reviews Settings, not by widget-local state
- `bw-price-variation`:
  - now supports a `Rates` content section for current-product BW Reviews summary
  - can show/hide review count in the inline summary
  - reviews act as a compact trust summary only; they do not make this widget a review-authority surface
  - now supports a `More payment options` checkout shortcut under Add to Cart
  - checkout shortcut follows the currently selected variation, or the default variation at initial render
  - current runtime may render a variation-bound license disclosure accordion between the variation buttons and Add to Cart
  - current visible variation selector is effectively license-first and single-axis
- `bw-trust-box`:
  - standalone trust/support widget extracted from the old lower `bw-price-variation` trust stack
  - can render:
    - global review slider
    - global fixed review summary box
    - widget-level digital product info cards
    - widget-level FAQ CTA
  - global review slider/review box content is delegated to `Blackwork Site -> Reviews Settings -> Trust Content`
  - widget-level authority owns info-card and FAQ controls only
- `bw-product-grid` product rendering is delegated to `BW_Product_Card_Component` in both:
  - widget server render path
  - AJAX response path (`bw_fpw_filter_posts`).
- `bw-product-grid` AJAX refresh still renders title/description/price markup unconditionally; final visibility is governed by grid-level `data-show-*` attributes and CSS.
- `bw-slick-slider` product rendering path is delegated to `BW_Product_Card_Component`.
- `bw-related-products` product rendering path is delegated to `BW_Product_Card_Component`.
- shared product-card hover media now resolves in this order:
  - `_bw_slider_hover_video`
  - `_bw_slider_hover_image`
  - no hover media
- WooCommerce product edit authority for those fields is the `Hover Media` metabox.
- hover-video playback is governed by the shared product-card runtime:
  - no hidden autoplay beneath the main image
  - playback starts on real hover/focus
  - playback resets on exit
- `bw-related-products` refactored (2026-03): proportional image grid, simplified controls (removed Image Settings, Overlay Buttons style, Card Container style, open_cart_popup, margin_top/bottom). Desktop columns control uses `selectors` for live Elementor preview without re-render. Tablet/mobile hardcoded to 2 columns in CSS.
- Removed widgets (governed removal wave completed):
  - `bw-wallpost` (replacement: `bw-product-grid` + `Show Filters = No`)
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
  - render-time enqueue duplication has been removed; only the two canonical asset-loading paths remain
- Slider handles are registered centrally and consumed per widget:
  - `embla-js`, `embla-autoplay-js`, `bw-embla-core-js`, `bw-embla-core-css`
- `bw-product-slider-script`, `bw-presentation-slide-script`
