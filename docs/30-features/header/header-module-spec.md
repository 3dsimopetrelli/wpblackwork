# Header Module Specification

## 1) Module Classification

- Domain: Global Layout Domain
- Layer: Presentation + Runtime Orchestration
- Tier: Tier 0 UX Surface
- Authority Level: None
- Business Mutation: Forbidden
- Business Read Access: Allowed only for WooCommerce cart count rendering
- Feature Flag and Storage Model:
  - Canonical option key: `bw_header_settings`
  - Storage: `wp_options`
  - Sanitization gate: `bw_header_sanitize_settings()`
  - Defaults provider: `bw_header_default_settings()`

Normative rules:
- The Header module MUST be treated as a global UX orchestrator.
- The Header module MUST NOT be treated as a business authority surface.
- Header runtime MAY read UI-facing state required for rendering (for example cart count).
- Header runtime MUST NOT mutate payment, order, provisioning, consent, or entitlement truth.

## 2) Runtime Mode Switch (Primary vs Fallback)

### Mode A: Custom Header Module (Primary)
- Source path: `includes/modules/header/*`
- Active when `bw_header_is_enabled()` resolves true from `bw_header_settings[enabled]`.
- Uses dedicated assets (`includes/modules/header/assets/*`) and dedicated templates.

### Mode B: Legacy Smart Header (Fallback)
- Source path: `assets/js/bw-smart-header.js` and `assets/css/bw-smart-header.css`
- Active only when Custom Header is not enabled.
- Enqueue gate in `bw_enqueue_smart_header_assets()` returns early when Custom Header is enabled.

Precedence and freeze rules:
- Mode A MUST take precedence over Mode B.
- Mode B MUST be treated as fallback-only.
- Mode B MUST NOT be extended with new behavior (frozen fallback path).
- Runtime MUST avoid dual-active behavior by preserving dequeue/early-return safeguards.

## 3) Admin Configuration Surface

Admin entrypoint:
- Menu: `Blackwork Site -> Header`
- Capability: `manage_options`
- Slug: `bw-header-settings`

Configuration groups:
- General:
  - Module enablement
  - Header title
  - Base background and transparency
  - Logo dimensions and media
  - Menus, labels, links
- Header Scroll (`smart_header`):
  - Scroll thresholds and delta
  - Smart background color/opacity
  - Scrolled background color/opacity
  - Blur panel controls (enable, amount, radius, tint, scrolled tint, padding)
- Responsive and breakpoints:
  - Mobile breakpoint
  - Mobile layout paddings/margins
  - Icon spacing
  - Cart badge offsets and sizes (mobile and desktop)
- Icons, menus, links:
  - Attachment-based icon selection
  - Desktop/mobile menu IDs
  - Account/cart links and labels

Storage and sanitation contract:
- All settings MUST be stored under `bw_header_settings` in `wp_options`.
- All admin writes MUST pass through registered setting sanitization (`bw_header_sanitize_settings()`).
- Runtime readers MUST consume merged defaults via `bw_header_get_settings()`.

## 4) Frontend Rendering Contract

Injection and hook contract:
- Primary render hook: `wp_body_open` with priority `5` via `bw_header_render_frontend()`.
- Theme override hook: `wp` with priority `1` via `bw_header_disable_theme_header()`.
- Fallback CSS hide hook: `wp_head` with priority `99` via `bw_header_theme_header_fallback_css()`.

Template contract:
- Main template: `includes/modules/header/templates/header.php`
- Mobile nav part: `includes/modules/header/templates/parts/mobile-nav.php`
- Search overlay part: `includes/modules/header/templates/parts/search-overlay.php`

Render suppression conditions:
- Header MUST NOT render in admin context.
- Header MUST NOT render during AJAX/feed/embed contexts.
- Header MUST NOT render in Elementor preview mode.
- Header MUST NOT render when module enable flag is false.

## 5) Smart Scroll State Machine (Custom Header Mode)

Inputs:
- `scrollDownThreshold`
- `scrollUpThreshold`
- `scrollDelta`
- `breakpoint`
- Dark-zone overlap signal (manual marker + auto-detected dark sections)

State classes:
- `bw-header-visible`
- `bw-header-hidden`
- `bw-header-scrolled`
- `bw-header-on-dark`
- `is-mobile` / `is-desktop`

Transitions:
- Top/reset state:
  - Near top position MUST clear hidden/visible transition classes.
- Scroll down:
  - After activation threshold (`max(headerHeight, scrollDownThreshold)`), downward movement above delta MAY move to hidden.
- Scroll up:
  - Upward movement meeting configured rule MUST move to visible.
- Scrolled visual state:
  - Non-top scroll MUST toggle `bw-header-scrolled`.
- Dark-zone state:
  - Sufficient overlap threshold MUST toggle `bw-header-on-dark`.

Precedence rules:
- When Smart Scroll is enabled, Smart Scroll color/opacity controls MUST take precedence over General background controls.
- When Smart Scroll is disabled, General background/transparency controls MUST apply.
- Mobile forced visual override MUST preserve configured non-authority behavior (mobile scrolled background enforcement).

## 6) Responsive Model

Breakpoint model:
- Runtime breakpoint is configurable via `bw_header_settings[breakpoints][mobile]`.
- CSS and JS responsive state MUST remain aligned to configured breakpoint.

Markup strategy:
- The module uses dual blocks (desktop and mobile) in a shared header template.
- This duplication is presentation-scoped and MUST remain non-authoritative.

Mobile navigation behavior:
- Off-canvas overlay model with explicit open/close controls.
- Close interactions include:
  - close button
  - overlay click
  - escape key
  - menu link click

Double-binding prevention:
- Navigation initialization MUST be guarded via `data-bw-navigation-initialized`.
- Search widget initialization MUST be guarded per root instance.

Known risk points:
- Multiple search widget instances can register multiple global keydown listeners.
- Dual desktop/mobile markup can drift visually if classes/settings diverge.
- Overlay-to-body relocation is required for viewport-anchored behavior and MUST remain deterministic.

## 7) Coupling Map

Elementor constraints:
- Frontend render/enqueue MUST avoid Elementor preview collisions.
- Theme header override compatibility MUST remain bounded to non-editor runtime.

WooCommerce read-only coupling:
- Cart count read:
  - Header MAY read Woo cart count for visual badge rendering.
- Fragment sync:
  - Header cart badge MAY be updated via Woo fragments filter.
- Cart popup integration:
  - Header cart click MAY call `window.BW_CartPopup.openPanel()` when available.
  - If popup API is unavailable, runtime MUST fallback to cart URL navigation.

AJAX search coupling:
- Search overlay MAY call product live-search endpoint for UI results.
- Search endpoint and UI are presentation support and MUST NOT become business authority.

## 8) Allowed Mutations / Forbidden Mutations

Allowed mutations:
- CSS class toggles for visibility and responsive state
- Overlay open/close state
- Menu panel open/close state
- Search overlay and result rendering state
- UI cart badge rendering updates from read-only sources

Forbidden mutations:
- Header MUST NOT mutate payment truth.
- Header MUST NOT mutate order lifecycle truth.
- Header MUST NOT mutate provisioning or entitlement state.
- Header MUST NOT mutate consent authority state.
- Header MUST NOT define or override any business authority boundary.

## 9) Failure Modes & Degrade-Safely Rules

JS failure:
- If Header JS fails, site navigation MUST remain available via rendered links and baseline markup.
- UI enhancements MAY degrade, but business flows MUST remain reachable.

Woo fragments failure:
- Cart badge freshness MAY degrade.
- Cart/cart-page navigation MUST continue to work.
- Fragment failure MUST NOT block checkout reachability.

Custom header disabled:
- Runtime MUST fall back to legacy smart header path when configured.
- Legacy fallback MUST remain operational but frozen (no expansion).

Global degrade-safe doctrine:
- Header failure MUST NOT block commerce execution.
- Header failure MUST NOT block authentication, provisioning, or consent flows.
- Header layer MUST remain non-authoritative under all failure states.

## 10) Regression Sensitivity (Tier 0 UX)

Any change to header runtime/assets/templates/hooks MUST validate:
- Desktop header render and navigation links
- Mobile header render and off-canvas lifecycle
- Smart scroll transitions (top/down/up/reset)
- Smart background and scrolled background behavior
- Search overlay open/close and live search path
- Cart icon click behavior (popup path + URL fallback)
- Cart badge fragment update behavior
- Account/cart links correctness
- Elementor preview/editor non-interference
- Legacy fallback non-regression when Custom Header is disabled

## 11) Cross-References

- Technical audit:
  - `../../50-ops/audits/header-system-technical-audit.md`
- Coupling style reference:
  - `../../40-integrations/cart-checkout-responsibility-matrix.md`
- ADR stack:
  - `../../60-adr/ADR-001-upe-vs-custom-selector.md`
  - `../../60-adr/ADR-002-authority-hierarchy.md`
  - `../../60-adr/ADR-003-callback-anti-flash-model.md`
  - `../../60-adr/ADR-004-consent-gate-doctrine.md`
  - `../../60-adr/ADR-005-claim-idempotency-rule.md`
  - `../../60-adr/ADR-006-provider-switch-model.md`

Normative anchor:
- ADR-002 authority hierarchy MUST be considered binding for all cross-domain interpretations touching header coupling boundaries.
