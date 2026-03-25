# Regression Protocol

## Mandatory Checks
After any maintenance task, validate:
- Checkout test
- Payment gateway test
- Auth test
- Cart popup test
- Header behavior test
- My Account test
- CSS regression scan
- Console errors scan

## Applicability by Incident Level
- Level 1: mandatory
- Level 2: mandatory
- Level 3: recommended based on impact

## Execution Notes
- Run targeted checks first for the impacted domain.
- Then run cross-domain sanity checks to detect side effects.
- Record anomalies and link them to incident level and affected domain docs.

## Elementor Widget Rebuild Regression Baseline
Use this baseline for all governed widget migration waves.

Required checks:
- Elementor frontend rendering for impacted widgets (desktop/tablet/mobile)
- Elementor editor open/reopen and preview re-render stability
- No missing handle/runtime errors in console for widget dependencies
- Product-card contract stability where applicable (image/title/price/CTA)
- Slider multi-instance stability where applicable (init, re-init, destroy)
- Slider behavior after responsive breakpoint changes (arrows/dots/count/width/height where used)
- No regression on pages where migrated widgets are absent (no unnecessary runtime side effects)

Wave-specific checks:
- Wall/query-grid convergence waves:
  - filter state and result determinism
  - pagination/query continuity
- Product-card convergence waves:
  - add-to-cart CTA behavior remains coherent
  - hover/image fallback behavior remains coherent
- Slider-core convergence waves:
  - one-time shared core initialization
  - widget-specific adapter behavior preserved

## BW Presentation Slide Widget
- Task: `BW-TASK-20260319-PS-01`
- Scope:
  - `bw-presentation-slide` / `BWPresentationSlide`
  - Files: `assets/js/bw-presentation-slide.js`, `assets/css/bw-presentation-slide.css`, `includes/widgets/class-bw-presentation-slide-widget.php`
- Required checks:
  - Horizontal layout initializes without console errors (no `TypeError: Cannot read properties of undefined`)
  - Image height modes (auto/fixed/contain/cover) apply correctly at each breakpoint
  - Custom cursor appears/disappears correctly on image hover (desktop only)
  - Custom cursor shows `←`/`→` on left/right halves, `+` on center (when popup enabled)
  - Arrow buttons show pointer cursor when custom cursor mode is active
  - Horizontal arrows do not flicker visible on mobile refresh when the active breakpoint has `Show Arrows = off`
  - System cursor is hidden over the image area, restored outside it
  - Popup opens/closes correctly; overlay removed from `<body>` on close
  - Popup does not auto-open on refresh; opening requires a real user press sequence
  - Popup portrait/tall images remain bounded within the viewport height
  - Widget destroy followed by re-init (Elementor editor re-render) leaves no orphaned `<body>` overlay
  - Vertical desktop layout: thumbnail click scrolls main panel to correct image
  - Vertical responsive layout: main/thumb Embla viewports stay synchronized
  - No regression on pages where widget is absent

## BW Product Grid Infinite Loading
- Task: `BW-TASK-20260312-ELW-01`
- Scope:
  - `bw-product-grid` / `BW Product Grid`
- Required checks:
  - initial server-rendered batch count matches subsequent AJAX `per_page`
  - filter changes reset pagination to page 1 and replace grid content cleanly
  - infinite loading appends the next batch without full-grid destruction
  - one in-flight request max per widget instance under rapid scroll
  - bottom loading indicator appears only during append fetches
  - sequential reveal is applied to appended/replaced items without hiding untouched initial markup
  - CSS-grid mode remains stable across filter changes and append batches
  - Masonry mode reflows safely after append/filter changes with no broken overlap
  - legacy unlimited `posts_per_page` instances keep replace-mode behavior and do not enter infinite loading

## BW Reviews System / Widget
- Scope:
  - `includes/modules/reviews/`
  - `includes/widgets/class-bw-reviews-widget.php`
  - `assets/js/bw-reviews.js`
  - `assets/css/bw-reviews.css`
- Required checks:
  - `Blackwork Site -> Reviews` list screen loads and row actions work
  - `Blackwork Site -> Reviews Settings` saves correctly across all tabs
  - WooCommerce native product reviews/comments are suppressed when Reviews is enabled
  - review submit works for supported policy combinations
  - email confirmation path transitions to approved or pending moderation according to settings
  - rejected reviews do not appear on frontend
  - trashed reviews do not appear on frontend and remain visible in admin trash views
  - verified badge appears only for true verified purchases
  - widget sort and load-more remain deterministic
  - global fallback mode shows global summary/list only when current product has no approved reviews
  - global fallback cards show reviewed product image/name; normal product cards do not

## BW Price Variation Review / Checkout Additions
- Scope:
  - `includes/widgets/class-bw-price-variation-widget.php`
  - `assets/js/bw-price-variation.js`
  - `assets/css/bw-price-variation.css`
- Required checks:
  - product review summary appears only when the current product has approved BW Reviews reviews
  - review summary stays hidden when the current product has no approved reviews
  - review count visibility follows the widget toggle
  - `More payment options` appears only when enabled
  - `More payment options` follows the currently selected variation/license
  - if the user does not change variation, `More payment options` uses the default variation
  - checkout shortcut stays aligned with Add to Cart selected-variation state

## Shared Product Card Hover Media
- Scope:
  - `includes/product-types/class-bw-product-slider-metabox.php`
  - `includes/components/product-card/class-bw-product-card-component.php`
  - `assets/css/bw-product-card.css`
- Required checks:
  - WooCommerce product edit screen shows `Hover Media` metabox
  - hover image field still saves and removes correctly
  - hover video field saves and removes correctly
  - product with hover video uses video-first behavior on shared product-card surfaces
  - product with hover video renders a real `<source src="...">` in storefront markup (not poster-only video shell)
  - hover video starts from the beginning on desktop hover/focus and resets on leave
  - product without hover video still uses hover image fallback
  - product with neither field renders safely without hover-media breakage
  - product-grid / related-products / slick-slider product cards remain clickable and visually stable
  - overlay buttons still appear and remain usable over hover media

## BW Product Slider
- Scope:
  - `includes/widgets/class-bw-product-slider-widget.php`
  - `assets/js/bw-product-slider.js`
  - `assets/css/bw-product-slider.css`
  - `blackwork-core-plugin.php`
- Required checks:
  - widget initializes without console errors on frontend
  - Elementor editor re-render destroys and rebuilds one instance cleanly
  - query controls return expected products
  - slide card output remains delegated to `BW_Product_Card_Component`
  - hover video / hover image behavior remains correct inside slider cards
  - breakpoint CSS correctly controls arrows, dots, and slide widths
  - Embla `reInit()` updates `slidesToScroll` and align behavior at breakpoint transitions
  - autoplay / loop / drag-free remain stable
  - touch drag and mouse drag toggles behave as configured
  - transient query cache invalidates after product save

## BW Showcase Slide
- Scope:
  - `includes/widgets/class-bw-showcase-slide-widget.php`
  - `assets/js/bw-showcase-slide.js`
  - `assets/css/bw-showcase-slide.css`
  - `blackwork-core-plugin.php`
- Required checks:
  - widget initializes without console errors on frontend
  - Elementor editor re-render destroys and rebuilds one instance cleanly
  - manual product IDs render in the exact provided order
  - products without showcase image meta fall back to featured image safely
  - `Texts color` from the showcase metabox propagates to title, description, and labels
  - CTA renders as split-pill system (green arrow circle + detached green text pill)
  - no popup settings or popup runtime are present
  - breakpoint CSS correctly controls arrows, dots, and slide widths
  - Embla `reInit()` updates `slidesToScroll` and align behavior at breakpoint transitions
  - custom cursor can be enabled/disabled without leaving orphaned DOM nodes

## BW Mosaic Slider
- Scope:
  - `includes/widgets/class-bw-mosaic-slider-widget.php`
  - `assets/js/bw-mosaic-slider.js`
  - `assets/css/bw-mosaic-slider.css`
  - `blackwork-core-plugin.php`
- Required checks:
  - widget initializes without console errors on frontend
  - Elementor editor re-render destroys and rebuilds one instance cleanly
  - desktop `Big post center` renders the featured item in the center column spanning two rows
  - desktop `Big center split` alternates the split composition left/right across desktop pages
  - desktop `Big post left` renders the featured item as the left 50/50 half and supporting cards as a `2x2` right-side grid
  - desktop `Big post right` renders the featured item as the right 50/50 half and supporting cards as a `2x2` left-side grid
  - desktop slide paging groups queried results into deterministic 5-item batches
  - `Auto Scale Mosaic = off` honors the manual `Desktop Mosaic Height` control
  - `Auto Scale Mosaic = on` removes manual height dependence and scales the page proportionally
  - `Auto Scale Square Format = on` switches the autoscaled desktop layout to square-tile geometry rather than only forcing an outer canvas ratio
  - below `1000px` the widget switches to the mobile linear slider and desktop mosaic viewport is not initialized
  - tablet and mobile visible-slide settings accept decimals and reveal part of the next slide correctly
  - responsive cards remain draggable via Embla with `align: start`
  - `Touch Drag (Mobile & Tablet)` governs responsive touch drag only
  - responsive horizontal wheel / two-finger trackpad scrolling works when the mobile runtime is active
  - manual IDs override taxonomy filters and keep exact order when randomize is off
  - randomize mode skips deterministic query-cache reuse and still renders valid results
  - `product` source continues to render via `BW_Product_Card_Component`
  - `post` source renders safely without depending on product-card authority
  - overlay buttons remain capped to `280px` max width in the widget
  - overlay button text remains fixed at `12px` on both featured and supporting cards
  - text spacing controls (`Title/Description/Price Padding`, `Text Items Gap`) respond correctly across desktop/tablet/mobile
  - responsive gutter and slide gap remain visually consistent when swiping between cards

## BW Hero Slide
- Scope:
  - `includes/widgets/class-bw-hero-slide-widget.php`
  - `assets/css/bw-hero-slide.css`
  - `blackwork-core-plugin.php`
- Required checks:
  - widget initializes without console errors on frontend and in Elementor editor
  - `Static` mode renders the hero correctly with background image, title, subtitle, and CTA button list
  - selecting `Slide` mode does not break rendering and still fails closed to static output in V1
  - responsive `Hero Height` applies correctly on desktop/tablet/mobile
  - responsive `Content Max Width` applies correctly on desktop/tablet/mobile
  - content alignment control updates title/subtitle alignment and CTA row alignment consistently
  - section padding adapts correctly across desktop/tablet/mobile
  - title/subtitle typography and padding controls apply correctly
  - CTA buttons wrap cleanly into multiple rows on smaller screens
  - supported link target types resolve correctly:
    - manual URL
    - product category archive
    - post category archive
    - archive page / post type archive
  - `Glass Effect` and `Fill Enabled` states produce valid visual combinations
  - button border width, border radius, typography, padding, and gap controls apply correctly

## Theme Builder Lite Editor UX Cleanup Regression
- Scope:
  - `Theme Builder Lite > Settings > Core Settings > Hide Pro upgrade panels`
- Required checks:
  - Elementor editor loads correctly with no sidebar crash/errors.
  - Widget panel/category list remains visible and usable.
  - With toggle enabled: Pro/Upgrade upsell accordion/panel groups are hidden in Elementor sidebar.
  - With toggle disabled: upsell panels return to default Elementor behavior.
  - Frontend pages and frontend widget rendering remain unchanged.

## Theme Builder Lite Shop Authority Regression
- Scope:
  - `Theme Builder Lite > Shop`
- Required checks:
  - `Shop` appears in All Templates type dropdown and type filter
  - saving `Enable Shop Override` + `Active Shop Template` persists after reload
  - enabled Shop template resolves on Woo shop page root
  - disabled Shop override fails open to theme/Woo template
  - product category archive pages remain governed by Product Archive rules only
  - selected Shop template shows `Applies to: Shop` in All Templates

## Risk Work Protocol (Start / Close)
### Task Start / Risk Work Protocol
Whenever a new risk investigation starts, ensure the risk is registered both in:
- `docs/00-governance/risk-register.md`
- `docs/00-governance/risk-status-dashboard.md`

The Risk Status Dashboard is the quick overview of all governance risks and must be kept synchronized with the Risk Register.

If a new risk is discovered during investigation, add it to both files before proceeding with mitigation planning.

### Task Close / Status Change Protocol
When a risk is closed or status changes, update:
- `docs/00-governance/risk-register.md`
- `docs/00-governance/risk-status-dashboard.md`
- `docs/50-ops/regression-protocol.md` (if testing procedures changed)
- feature technical docs (if behavior changed)

### Risk Closure Documentation Checklist
Before marking a risk as `CLOSED` or `RESOLVED`:
- risk-register.md updated
- risk-status-dashboard.md updated
- regression protocol updated if needed
- feature technical docs updated if relevant

### Governance Consistency Checks
Run these checks when opening or closing governed risk work:
- risk IDs in `docs/00-governance/risk-register.md` and `docs/00-governance/risk-status-dashboard.md` match
- Executive snapshot counts in `docs/00-governance/risk-status-dashboard.md` match table-derived counts
- each closed risk has a closure artifact in `docs/tasks/`
- each closure artifact explicitly references `docs/governance/task-close.md`

## Supabase Protected Surface Smoke Binding
When tasks touch Supabase protected surfaces, these smoke tests are mandatory:

1. guest checkout provisioning
2. invite email click callback
3. onboarding password completion
4. order claim visibility
5. resend invite
6. expired link recovery
7. logout cleanup

## WooCommerce Template Override Rebase Notes
- Date: 2026-03-09
- Risk: `R-WOO-24` (patch 1, patch 2, patch 3, patch 4, patch 5) - `RESOLVED`
- Templates updated:
  - `woocommerce/templates/checkout/form-coupon.php`
  - `woocommerce/templates/checkout/payment.php`
  - `woocommerce/templates/checkout/form-checkout.php`
  - `woocommerce/templates/cart/cart.php`
  - `woocommerce/templates/single-product/related.php`
- Alignment action:
  - Applied minimal structural compatibility patching against WooCommerce core contracts while preserving BlackWork custom UX/layout.
  - Avoided Supabase-adjacent surfaces entirely during the patch sequence.
- Required regression checks completed:
  - apply valid coupon
  - invalid coupon handling
  - submit via button and Enter key
  - guest and logged-in checkout
  - mobile and desktop layout
  - payment gateway visibility unaffected (Card / PayPal / Klarna)
  - gateway switching and `updated_checkout` selection persistence
  - noscript update-totals fallback present
  - checkout form submit still works with accessibility/form contract intact (`aria-label` + trailing after-form hook restored)
  - cart last-item removal transitions to empty-cart view on mobile and desktop
  - related products render correctly with Wallpost layout on mobile and desktop

## Checkout Payment Selector Interaction
- Risk/Task: `CHECKOUT-02` (resolved)
- Mandatory regression assertions:
  - radio click must select payment method on first click
  - row/label click must behave identically
  - selection must persist after `updated_checkout`

## Admin Asset Scope Hardening
- Date: 2026-03-09
- Risk: `R-ADM-19` (patch A, patch B) - `MITIGATED`
- Scope:
  - Patch A closed: Blackwork admin menu CSS enqueue scoped to Blackwork admin surfaces only.
  - Patch B closed: Digital Products metabox Select2 loading moved to scoped `admin_enqueue_scripts` (`post.php`/`post-new.php` + `post_type=product`), with runtime preference (`selectWoo` -> `select2` -> local fallback).
  - Patch C deferred (optional backlog): stricter screen/hook replacement for remaining `$_GET['page']` checks in low-risk admin modules (Header, System Status).
  - Local fallback assets added:
    - `assets/lib/select2/js/select2.full.min.js`
    - `assets/lib/select2/css/select2.css`
- Required regression checks completed:
  - Dashboard / Posts / Orders do not load Blackwork menu CSS outside intended scope.
  - Product edit/new screens load Select2 correctly for Showcase linked-product search.
  - No CDN Select2 requests remain from metabox path.
  - No duplicate Select2/SelectWoo runtime on the same product editor page.
  - Non-product admin pages do not load metabox Select2 assets.

## Admin Settings Input Integrity (Patch A + Patch B)
- Date: 2026-03-10
- Risk: `R-ADM-21` (patch A, patch B) - `RESOLVED`
- Scope:
  - Hardened Cart Popup admin save path in `cart-popup/admin/settings-page.php`.
  - Added normalized scalar input reads (`wp_unslash`), enum allowlists, and bounded numeric clamping.
  - Preserved option names and admin UX; SVG pipeline unchanged.
  - Hardened variation license AJAX exposure by removing unauthenticated route registration in `metabox/variation-license-html-field.php`:
    - removed `wp_ajax_nopriv_bw_get_variation_license_html`
    - kept authenticated hook + handler intact
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - normal values save and persist
  - panel/mobile width settings persist correctly
  - border styles still apply correctly
  - font weight still applies correctly
  - SVG fields still behave correctly
  - out-of-range values are clamped
  - frontend cart popup behavior unchanged
  - logged-out product variation license HTML still renders correctly
  - logged-in behavior unchanged
  - no frontend dependency on `bw_get_variation_license_html` AJAX action
  - unauthenticated direct call to old AJAX action no longer works

## Media Folders Counts Pipeline Audit
- Date: 2026-03-09
- Risk: `R-MF-01` - `WATCHLIST / PLANNED MITIGATION`
- Audit scope:
  - Media Folders counts computation, cache and invalidation pipeline.
  - No implementation changes in this phase (analysis-only closure).
- Confirmed paths:
  - Primary: `bw_mf_get_folder_counts_map_batched()`
  - Fallback: `bw_mf_get_folder_counts_map_fallback()`
- Confirmed risks:
  - stale-count window until TTL when invalidation misses lifecycle events
  - cache churn from broad invalidation
  - expensive fallback behavior under large datasets
- Planned mitigation watchlist:
  - monitor/log fallback activation
  - extend invalidation hooks to object lifecycle events
  - split tree/meta invalidation from counts invalidation
  - add bulk integrity regression scenarios
- Supabase note:
  - No Supabase-adjacent surfaces involved.

## Media Folders Query Restrictiveness Hardening (R-MF-02 / R-MF-03)
- Date: 2026-03-10
- Risks:
  - `R-MF-02` - `MITIGATED`
  - `R-MF-03` - `MITIGATED`
- Scope:
  - Hardened `bw_mf_merge_tax_query()` in `includes/modules/media-folders/runtime/media-query-filter.php`.
  - Existing tax clauses remain grouped with their internal relation; Media Folders clause is merged under explicit outer `AND`.
  - Prevents pre-existing `OR` relations from weakening folder/unassigned filtering.
- Required regression checks completed:
  - assign media to folder, reload, confirm persistence
  - remove folder, confirm attachment becomes unassigned
  - grid media filter by folder remains correct
  - grid media filter by unassigned remains correct
  - list view media filter remains correct
  - pagination/search still work in media library
  - folder filter remains restrictive even with another tax_query present
  - counts/tree still update correctly after assignment/delete

## Payments Webhook Hardening (Patch 1)
- Date: 2026-03-09
- Risk: `R-PAY-08` (patch 1) - `CLOSED`
- Scope:
  - Added explicit POST-only method enforcement for Blackwork-owned Stripe webhook handlers.
  - Updated files:
    - `includes/Gateways/class-bw-abstract-stripe-gateway.php`
    - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
- Runtime decision record:
  - Active Google Pay runtime/webhook source of truth:
    - `includes/woocommerce-overrides/class-bw-google-pay-gateway.php`
  - In-repo but inactive/not bootstrapped path:
    - `includes/Gateways/class-bw-google-pay-gateway.php`
  - Same class name + same gateway id means both paths cannot be active together at runtime.
  - Google Pay runtime convergence is deferred to a dedicated architecture cleanup task.
- Required regression checks:
  - valid webhook via POST still processes
  - invalid signature via POST still fails
  - GET to webhook endpoint is rejected (`405`)
  - unsupported event keeps expected no-op behavior

## SVG Sanitization Hardening Follow-up
- Date: 2026-03-09
- Risk: `R-SEC-22` - `RESOLVED`
- Scope:
  - Secondary SVG intake/output paths hardened to reuse canonical security pipeline.
  - Updated files:
    - `cart-popup/admin/settings-page.php`
    - `cart-popup/frontend/cart-popup-frontend.php`
    - `includes/widgets/class-bw-button-widget.php`
- Applied controls:
  - Cart popup option sanitizer now uses:
    - `bw_mew_svg_sanitize_content()`
    - `bw_mew_svg_is_valid_document()`
  - Frontend output of stored cart popup SVG now passes strict `wp_kses` allowlist.
  - Remote SVG in button widget now requires SVG content type + canonical sanitize/validate; invalid payload falls back to default icon.
  - Fallback cart popup SVG allowlist no longer permits inline `style`.
- Scope constraints respected:
  - Media Library SVG upload pipeline unchanged.
  - No upload hooks changed.
  - Header/logo SVG path unchanged.
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - valid cart popup SVG saves and renders
  - malformed/unsafe cart popup SVG rejected
  - empty-cart custom SVG renders correctly
  - additional custom SVG renders correctly
  - `svg_black` coloring preserved
  - widget attachment SVG renders
  - widget remote valid SVG renders
  - widget remote invalid/non-SVG response falls back to default icon
  - Media Library SVG upload behavior unchanged

## Brevo Checkout Hardening (Patch 1 + Patch 2)
- Date: 2026-03-10
- Risk: `R-BRE-09` - `RESOLVED`
- Scope:
  - Patch 1: removed unsafe legacy Coming Soon Brevo subscription runtime from:
    - `BW_coming_soon/includes/functions.php`
  - Patch 1 removed:
    - `bw_handle_subscription()` implementation
    - `add_action("init", "bw_handle_subscription")`
  - Patch 2: preserved positive checkout subscribe state in:
    - `includes/admin/checkout-subscribe/class-bw-checkout-subscribe-frontend.php`
  - Patch 2 changed duplicate-pass handling:
    - existing `subscribed|pending|1` now exits as no-op
    - removed destructive rewrite to `skipped` for already-valid positive states
  - No redesign of canonical checkout/mail-marketing Brevo runtime.
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - Coming Soon page still renders normally
  - legacy POST path no longer performs Brevo writes
  - no request to Brevo contacts API originates from removed Coming Soon path
  - checkout newsletter flow unchanged
  - consented order reaches `subscribed`/`pending` correctly
  - duplicate hook passes do not downgrade `subscribed`
  - duplicate hook passes do not downgrade `pending`
  - no-consent path still becomes `skipped` when appropriate
  - mail-marketing admin tools unchanged

## Brevo Secret Exposure Verification
- Date: 2026-03-10
- Risk: `R-BRE-09` - `CLOSED` (closure verification)
- Result: `PASS`
- Verification coverage:
  - repository-wide scan confirms no `xkeysib-` token present
  - legacy Coming Soon runtime path is absent (`bw_handle_subscription()` and `add_action("init", "bw_handle_subscription")` not present)
  - Coming Soon Brevo runtime uses canonical Mail Marketing configuration (`bw_mail_marketing_general_settings`) via `BW_Mail_Marketing_Settings::get_general_settings()['api_key']`
  - previous secret-exposure radar hit is classified as stale (older revision/external snapshot)

## HTTP Timeout Verification (External Radar)
- Date: 2026-03-10
- Scope:
  - `admin/class-blackwork-site-settings.php`
  - `BW_coming_soon/includes/functions.php`
  - `includes/integrations/brevo/class-bw-brevo-client.php`
- Result: `PASS`
- Verification summary:
  - all relevant HTTP calls use explicit `timeout` values
  - no `timeout => 0` usage found
  - no unsafe raw cURL path detected
  - `BW_coming_soon/includes/functions.php` has no outbound HTTP request
  - Brevo client uses explicit timeout (default `15`)
- Classification: stale / not applicable finding in current snapshot
- Recommended action: no runtime patch required

## My Account CSS Size Verification (External Radar)
- Date: 2026-03-10
- Scope:
  - `assets/css/bw-my-account.css`
- Result: `PASS`
- Verification summary:
  - current snapshot size measured at ~48,084 bytes (~47 KB), not ~2.1 MB
  - no embedded third-party CSS libraries found
  - no bundled Elementor-generated CSS detected
  - moderate selector overlap observed (historical override layering), but not a payload-risk incident
- Classification: false positive / stale report
- Recommended action: no runtime patch required; optional non-urgent selector consolidation backlog only

## Import Pipeline Integrity Hardening (R-IMP-10)
- Date: 2026-03-10
- Risk: `R-IMP-10` - `MITIGATED`
- Scope:
  - Patch 1: import progress checkpoint hardening in `admin/class-blackwork-site-settings.php`.
  - Patch 2: strict enum allowlist validation in importer normalization path (`bw_import_prepare_row_data`).
- Required regression checks completed:
  - interrupted import resumes close to actual progress
  - duplicate replay of already processed rows reduced
  - enum values validated during import
  - invalid enum values ignored safely
  - valid imports unaffected

## Search Live Runtime Visibility Alignment (R-SRCH-11)
- Date: 2026-03-10
- Risk: `R-SRCH-11` - `MITIGATED`
- Scope:
  - Live AJAX search visibility alignment in `includes/modules/header/frontend/ajax-search.php`.
  - Search context now excludes only `exclude-from-search` (no `exclude-from-catalog` exclusion in live search).
- Required regression checks completed:
  - live search finds products that are searchable but excluded from catalog
  - products excluded from search remain excluded
  - category/type filters still work
  - guest and logged-in AJAX responses still work
  - no obvious performance regression in live search
  - no runtime errors in header search UI

## Header Search Overlay Global-State Hardening (R-HDR-13)
- Date: 2026-03-10
- Risk: `R-HDR-13` - `MITIGATED`
- Scope:
  - Deterministic multi-instance overlay coordination in `includes/modules/header/assets/js/bw-search.js`.
  - Global reference counter `window.BW_HEADER_SEARCH_OPEN_COUNT` now owns `body.bw-search-overlay-active` removal timing.
- Required regression checks completed:
  - open/close search overlay on desktop
  - open/close search overlay on mobile
  - open both overlays sequentially; close one while another remains open
  - verify `body.bw-search-overlay-active` remains applied until all overlays are closed
  - Escape key closes active overlay correctly
  - resize desktop/mobile with overlay active
  - cart popup interaction does not break search overlay state

## System Status Diagnostics Freshness Integrity (R-ADM-18)
- Date: 2026-03-10
- Risk: `R-ADM-18` - `MITIGATED`
- Scope:
  - Deterministic freshness metadata hardening in:
    - `includes/modules/system-status/runtime/check-runner.php`
    - `includes/modules/system-status/admin/assets/system-status-admin.js`
    - `includes/modules/system-status/runtime/checks/check-database.php`
  - Added payload fields: `is_partial_refresh`, `refreshed_checks`, `last_full_generated_at`.
  - Scoped refreshes now render as `Mixed` source in admin UI.
  - DB fallback path now resets `table_count` and `total_db_bytes` before `SHOW TABLE STATUS` accumulation.
- Required regression checks completed:
  - run full system status check
  - run partial single-section check
  - verify source indicator shows `Mixed` for partial refresh payload
  - verify full refresh returns normal `Live`/`Cached` source behavior
  - verify metadata coherence (`is_partial_refresh`, `refreshed_checks`, `last_full_generated_at`)
  - verify DB totals/table count remain coherent on fallback path
  - verify unauthorized requests remain blocked (capability + nonce)

## Slick Fail-Soft Hardening (R-FE-23)
- Date: 2026-03-10
- Risk: `R-FE-23` - `MITIGATED`
- Scope:
  - Fail-soft guard in shared Slick runtime: `assets/js/bw-slick-slider.js`.
  - Fail-soft guard in product slide runtime: `assets/js/bw-product-slide.js`.
  - Bounded retry stop in presentation runtime: `assets/js/bw-presentation-slide.js`.
  - No architecture redesign; no enqueue-scope refactor in this patch.
- Required regression checks completed:
  - BW Slick Slider works when Slick is available
  - BW Product Slide works when Slick is available
  - BW Presentation Slide works when Slick is available
  - Elementor editor/preview remains stable
  - no duplicate init regressions
  - simulate Slick unavailable: no JS fatal, static markup remains usable
  - no infinite retry loop remains in presentation slide runtime

## Slick Asset Scope Hardening (R-FE-23)
- Date: 2026-03-10
- Risk: `R-FE-23` - `MITIGATED`
- Scope:
  - `blackwork-core-plugin.php` Slick/bootstrap moved from unconditional Elementor enqueue hooks to register-only authority on `init`.
  - Removed unconditional global enqueue path for:
    - `bw_enqueue_slick_slider_assets`
    - `bw_enqueue_presentation_slide_widget_assets`
  - Slick payload now loads through Elementor widget dependency contracts only.
- Required regression checks completed:
  - Elementor frontend with each Slick widget individually: assets load and widget works
  - Elementor frontend with no Slick widgets: Slick CDN assets absent
  - Elementor editor with Slick widget: no missing handle errors
  - Elementor preview with Slick widget: works correctly
  - Presentation Slide remains functional (including mobile Slick mode)
  - no console errors when Slick widgets are absent
  - non-Elementor pages unaffected

## Public AJAX Mutation Transport Hardening (R-FPW-20 Patch 1)
- Date: 2026-03-10
- Risk: `R-FPW-20` (patch 1) - `CLOSED`
- Scope:
  - Added explicit POST-only method enforcement for non-Supabase public mutation endpoints.
  - Updated files:
    - `cart-popup/frontend/cart-popup-frontend.php`
    - `woocommerce/woocommerce-init.php`
  - Patched handlers:
    - `bw_cart_popup_add_to_cart`
    - `bw_cart_popup_remove_item`
    - `bw_cart_popup_update_quantity`
    - `bw_cart_popup_apply_coupon`
    - `bw_cart_popup_remove_coupon`
    - `bw_apply_coupon`
    - `bw_remove_coupon`
- Required regression checks completed:
  - valid POST requests still work for all patched actions
  - GET requests return HTTP 405
  - Cart Popup behavior unchanged
  - checkout coupon apply/remove behavior unchanged
  - public read-only AJAX endpoints remain unaffected

## Authenticated Product Search Capability Hardening (R-FPW-20 Patch 2)
- Date: 2026-03-10
- Risk: `R-FPW-20` (patch 2) - `CLOSED`
- Scope:
  - Added explicit capability enforcement in authenticated AJAX endpoint `bw_search_products_ajax`.
  - Updated file:
    - `metabox/digital-products-metabox.php`
  - Control added:
    - requires `current_user_can( 'edit_products' )`
    - unauthorized requests return JSON permission error (`403`)
- Required regression checks completed:
  - authorized users with `edit_products` can search products normally
  - unauthorized logged-in users receive permission error (`403`)
  - nonce behavior unchanged
  - metabox product search UI still works for authorized users

## FPW Tag Query Verification (ReRadar)
- Date: 2026-03-10
- Risk context: `R-FPW-20` verification task
- Result: `CLOSED (False Positive)`
- Verification summary:
  - audited `bw_fpw_collect_tags_from_posts()` in `blackwork-core-plugin.php`
  - confirmed current implementation already uses batched term lookup:
    - `wp_get_object_terms($post_ids, $taxonomy, ['fields' => 'all_with_object_id'])`
  - confirmed no per-post `wp_get_object_terms()` loop exists in current snapshot
  - no runtime patch required

## FPW Cache-Key Determinism Hardening (R-FPW-02)
- Date: 2026-03-10
- Risk context: `R-FPW-20` subtask (`R-FPW-02`)
- Result: `CLOSED` (mitigation applied)
- Scope:
  - file: `blackwork-core-plugin.php`
  - function: `bw_fpw_get_tags()`
  - change: sorted cache-key-only subcategory copy (`SORT_NUMERIC`) before `md5(wp_json_encode(...))`
- Required regression checks completed:
  - same logical subcategory set in different order reuses same transient key
  - empty subcategory list still works
  - posts without tags still return empty correctly
  - shared tag aggregation unchanged
  - tag ordering/output contract unchanged
  - no query behavior changes

## Cart Popup Runtime Scope + Checkout Suppression (R-PERF-29)
- Date: 2026-03-10
- Risk: `R-PERF-29` - `MITIGATED`
- Scope:
  - Runtime-needed guard applied to Cart Popup frontend surfaces:
    - assets enqueue
    - panel render
    - dynamic CSS output
  - Added admin setting `bw_cart_popup_disable_on_checkout` (default enabled) in Blackwork Site Panel Cart Pop-up tab.
  - Checkout runtime suppression when setting is enabled: no floating icon, no popup panel, no popup CSS/JS.
  - Outside checkout, existing Cart Popup behavior preserved.
- Required regression checks completed:
  - checkout page + setting enabled:
    - floating cart icon absent
    - popup panel markup absent
    - popup CSS/JS absent
    - no popup trigger/runtime available
  - checkout page + setting disabled:
    - legacy popup behavior restored
  - non-checkout pages:
    - popup behavior unchanged
    - header/cart integration unchanged
    - widget/button triggers remain functional
    - Woo fragments/cart AJAX unaffected
  - admin:
    - setting visible in Blackwork Site Panel
    - setting persists correctly

## Checkout Payment State Integrity Hardening (R-PAY-02)
- Date: 2026-03-10
- Risk: `R-PAY-02` - `MITIGATED`
- Scope:
  - Minimal JS-only hardening in `assets/js/bw-payment-methods.js`.
  - Hardened explicit-selection re-apply behavior to avoid unnecessary overrides of valid checked method.
  - Added pre-submit reconciliation guard so checked `payment_method` and selected/open UI row converge before submit.
  - Added minimal free-order guard to avoid `#place_order` label drift while `body.bw-free-order` is active.
  - No Supabase-adjacent surfaces touched.
- Required regression checks completed:
  - checkout load with default gateway
  - repeated payment method switching
  - `updated_checkout` / fragment refresh after address or shipping change
  - coupon apply/remove flow
  - wallet-capable gateway visibility behavior
  - submit order with non-default gateway selected
  - unavailable gateway after refresh
  - zero-total / free-order path (when present)

## Wallet Availability / State Drift Hardening (R-PAY-03)
- Date: 2026-03-10
- Risk: `R-PAY-03` - `PARTIAL MITIGATION`
- Scope:
  - Minimal JS-only hardening in `assets/js/bw-google-pay.js`.
  - `BW_GPAY_AVAILABLE` ownership moved to deterministic setter (`setGooglePayAvailability`).
  - Removed optimistic Google Pay availability (`true`) on init and `updated_checkout`.
  - Availability now converges to `false` during pending check, negative capability, and failure/error paths.
  - Selector convergence is re-triggered on availability changes via existing `scheduleSelectorSync(...)`.
  - No Supabase-adjacent surfaces touched.
- Regression checks required (manual):
  - checkout load with Google Pay supported
  - checkout load with Google Pay unsupported
  - switch between wallet and non-wallet gateways
  - `updated_checkout` after shipping/address change
  - coupon apply/remove
  - wallet visibility after fragment refresh
  - fallback: wallet unavailable -> `place_order` visible
  - Apple Pay behavior unchanged

## Redirect Authority Drift Hardening (R-RED-12)
- Date: 2026-03-10
- Risk: `R-RED-12` - `MITIGATED` (strict non-Supabase scope)
- Scope:
  - Minimal precedence clarification in `BW_coming_soon/includes/functions.php`.
  - `bw_show_coming_soon` now runs at `template_redirect@1` to establish deterministic authority in anonymous maintenance mode.
  - Generic redirect engine (`includes/class-bw-redirects.php`) left unchanged.
  - Auth/Supabase redirect surfaces excluded by freeze policy.
- Regression checks required (manual):
  - Coming Soon active + anonymous visit on URL with custom redirect rule
  - Coming Soon active + anonymous visit on URL without custom redirect rule
  - Coming Soon inactive + custom redirect rule still works
  - cart/checkout routes remain unaffected by custom redirect rules
  - wallet failed return still redirects to checkout
  - admin save/import redirects unchanged

## Media Folders Duplicate Assignment Warning
- Date: 2026-03-16
- Scope:
  - Media Folders admin drag/drop assignment UX for Media, Posts, Pages, Products.
  - Existing `bw_media_assign_folder` endpoint now reports duplicate target assignments without converting them to hard errors.
- Regression checks required (manual):
  - drag an item into a different folder -> assignment works, no duplicate popup
  - drag the same media item into the same folder again -> popup appears, closes on background click
  - drag the same post/page/product into the same folder again -> popup appears, closes on background click
  - drag to Unassigned (`term_id = 0`) -> no duplicate popup
  - media bulk assign with mixed new + duplicate items -> new items move, duplicate warning still appears

## Media Folders List-Table Folder Marker Dot
- Date: 2026-03-16
- Scope:
  - Extends the folder marker concept to Posts / Pages / Products list tables when the corner-indicator setting is enabled.
  - Marker is rendered inside the drag-handle column, below the handle icon.
- Regression checks required (manual):
  - assigned Post/Page/Product with folder custom color -> colored marker dot visible
  - assigned Post/Page/Product with no folder custom color -> black marker dot visible
  - unassigned Post/Page/Product -> no marker dot
  - drag-handle column width/layout remains compact and drag UX still works

## Media Folders List-Table Bulk Organize
- Date: 2026-03-16
- Scope:
  - Enables the shared sidebar `Bulk organize` control on Posts / Pages / Products list tables using checkbox-selected rows.
- Regression checks required (manual):
  - select multiple Posts and move -> assignment succeeds
  - select multiple Pages and move -> assignment succeeds
  - select multiple Products and move -> assignment succeeds
  - no selection -> generic item warning shown
  - duplicate move still triggers duplicate popup instead of silent no-op
  - Media bulk organize remains unchanged

## Media Folders List-Table Copy Link Column
- Date: 2026-03-18
- Scope:
  - Adds a compact `Link` column on Posts / Pages / Products list tables for one-click permalink copy.
- Regression checks required (manual):
  - Posts list -> `Link` column appears before `Author`
  - Pages list -> `Link` column appears before `Author`
  - Products list -> `Link` column appears before `Author`
  - clicking the copy button copies the row permalink
  - drag-handle, marker dot, and author/date columns remain aligned

## Media Folders Summary Cache Lifecycle Invalidation
- Date: 2026-03-17
- Scope:
  - Hardens summary/tree/count cache invalidation for supported post lifecycle changes that affect `All Items` and `Unassigned Items`.
- Regression checks required (manual):
  - create new unassigned Page -> `Unassigned Items` increments immediately
  - create new unassigned Post -> `Unassigned Items` increments immediately
  - upload new unassigned Media -> `Unassigned Files` increments immediately
  - create new unassigned Product -> `Unassigned Items` increments immediately
  - move counted item to trash / restore -> summary counts converge without waiting for TTL
