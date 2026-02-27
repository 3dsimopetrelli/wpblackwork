# Header Responsive Contract

## 1) Breakpoint Contract

Canonical breakpoint source:
- `bw_header_settings.breakpoints.mobile`
- Runtime exposure through localized config (`bwHeaderConfig.breakpoint`)

Normative requirements:
- JS and CSS responsive behavior MUST be aligned to the configured mobile breakpoint.
- Header runtime MUST apply exactly one responsive mode class at a time:
  - `is-mobile` when viewport width is `<= breakpoint`
  - `is-desktop` when viewport width is `> breakpoint`
- Responsive class recomputation MUST run on resize.
- Any responsive visual branch MUST derive from the same canonical breakpoint input.

## 2) Markup Strategy (Desktop + Mobile Blocks)

Implementation model:
- Shared header template contains dual presentation blocks:
  - desktop block (`.bw-custom-header__desktop`)
  - mobile block (`.bw-custom-header__mobile`)

Desktop-oriented sections:
- Desktop logo area
- Desktop navigation container
- Desktop search placement
- Desktop navshop/account/cart placement

Mobile-oriented sections:
- Mobile left area (hamburger + off-canvas navigation entry)
- Mobile center logo
- Mobile right area (search + cart/account compact rendering)

Normative rules:
- Dual-block structure MUST be treated as presentation duplication only.
- Duplicated blocks MUST NOT introduce business mutation logic.
- Any desktop/mobile variance MUST remain UX/layout scoped.
- Business authority boundaries CANNOT be implemented inside duplicated markup branches.

## 3) Mobile Navigation Off-Canvas Contract

Open trigger:
- Mobile navigation toggle button (`.bw-navigation__toggle`)

Close triggers:
- Dedicated close button (`.bw-navigation__close`)
- Overlay background click
- `Escape` key
- Mobile menu link click

Overlay placement:
- Mobile overlay node MUST be relocated to `<body>` at init time.
- This relocation is required to preserve viewport-fixed behavior independent of transformed ancestors.

Scroll-lock behavior:
- When off-canvas is open, body lock class (`bw-navigation-mobile-open`) MUST be applied.
- When off-canvas closes, body lock class MUST be removed.

Deterministic state rule:
- Open/close transitions MUST converge to a single deterministic overlay state (`is-open` present or absent).
- Toggle interactions MUST NOT produce ambiguous concurrent states.

## 4) Search Overlay Contract

Open/close model:
- Search opens from `.bw-search-button`.
- Search closes via:
  - close button
  - overlay background click
  - `Escape` key
  - form submit completion path

Overlay placement:
- Search overlay MUST be relocated to `<body>` to avoid container clipping and stacking constraints.

AJAX coupling:
- Live search uses AJAX endpoint as presentation support.
- Live search results MUST be treated as presentation-only and MUST NOT become business authority.

Initialization guards:
- Each search widget root MUST apply an initialization guard (`bw-search-initialized`) to prevent duplicate instance setup.

Global listener constraint:
- Global keydown listeners MAY be attached per instance in current implementation.
- This behavior MUST remain functionally safe but is a known drift/performance risk if instance count grows.

Degrade behavior:
- If AJAX fails, overlay MUST remain usable and closable.
- Failure MUST degrade to message/no-results behavior without blocking navigation.

## 5) Cart Badge & Account Links in Responsive Context

Read-only data contract:
- Cart badge count rendering MUST be read-only from WooCommerce cart state.
- Header MUST NOT own or persist cart business state.

Fragment update contract:
- Cart badge MAY be updated via Woo fragments (`woocommerce_add_to_cart_fragments` path).
- Fragment update is visual synchronization only.

Cart popup integration contract:
- Cart click MAY call `window.BW_CartPopup.openPanel()` if present.
- If popup API is unavailable, runtime MUST fallback to cart URL navigation.

Desktop/mobile consistency:
- Account/cart links in desktop and mobile blocks MUST resolve to configured canonical links.
- Label/icon differences MAY exist for UX reasons, but link intent MUST remain consistent.

## 6) Binding & Initialization Guards

Navigation guard:
- `data-bw-navigation-initialized` MUST gate navigation initialization per navigation root.

Search guard:
- Search widget initialization MUST be gated per widget root instance.

Double-binding prevention:
- Event bindings MUST be idempotent per initialized instance.
- Guard removal MUST be considered a high drift risk.

Global listener constraints:
- Global listeners (for example keydown) MUST remain predictable and MUST NOT introduce conflicting close/open behavior.

Drift risk statement:
- If guards are removed or bypassed, duplicate handlers can cause non-deterministic UX (double close/open, repeated side effects, stacked listeners).

## 7) Responsive + Scroll Interaction Rules

Interaction baseline:
- Responsive mode and smart scroll state machine MUST coexist without authority crossover.

Resize obligations:
- Resizing across breakpoint MUST recompute responsive classes.
- Smart scroll visual state MUST remain coherent after breakpoint transition.

Mid-scroll breakpoint transition:
- If viewport crosses breakpoint while scrolled, runtime MUST converge to valid responsive + scroll class combination.

Mobile scrolled override:
- Mobile scrolled background forced override is an implementation-faithful rule and MUST be treated as active behavior.

Off-canvas and scroll interaction:
- Off-canvas open/close state MUST remain deterministic during scroll activity.
- Scroll-driven header classes MUST remain presentation-only while menu state changes.

## 8) Failure / Degrade-Safely Rules

JS failure:
- Navigation links MUST remain reachable from rendered markup baseline.
- Header enhancements MAY degrade, but page navigation MUST remain possible.

Overlay failure:
- If overlay mechanics fail, fallback navigation paths MUST remain available.
- Cart intent MUST still resolve via URL fallback when popup API is unavailable.

Fragment failure:
- Cart badge freshness MAY degrade.
- Navigation and cart/account reachability MUST remain functional.

Commerce safety:
- Header runtime MUST NOT block checkout, payment, or account reachability under any failure state.
- Header layer CANNOT become a blocking dependency for commerce flow.

## 9) Regression Checklist (Responsive)

1. Desktop render
- Expected: desktop block visible, mobile block hidden, links and nav usable.

2. Mobile render
- Expected: mobile block visible, desktop block hidden, icons and links usable.

3. Breakpoint transition
- Expected: `is-mobile`/`is-desktop` recompute correctly when resizing across breakpoint.

4. Menu open/close
- Expected: toggle opens off-canvas, close button closes deterministically.

5. ESC close
- Expected: pressing `Escape` closes off-canvas/search overlays when open.

6. Overlay click close
- Expected: clicking overlay background closes off-canvas/search overlays.

7. Scroll while menu open
- Expected: no broken state convergence; overlay and header classes remain coherent.

8. Search open/close
- Expected: search overlay opens, closes, and remains responsive to key actions.

9. Cart badge update
- Expected: Woo fragment update reflects count changes in header badge.

10. Cart popup open + URL fallback
- Expected: popup API path works when available; URL fallback works when unavailable.

11. Account link behavior
- Expected: account links in desktop and mobile resolve correctly.

12. Elementor preview non-interference
- Expected: header runtime does not conflict with Elementor preview/editor contexts.

## 10) Directional Flow Constraint (Header Context)

Operational direction is:

Business State -> Header Render

Normative rule set:
- Header MUST remain read-only with respect to business domains.
- Reverse authority mutation is prohibited.
- Header MAY reflect business-derived display data (for example cart count) but MUST NOT mutate business truth.
