# Header Scroll State Machine Specification

## 1) Inputs and Configuration

Canonical source:
- Option key: `bw_header_settings` (stored in `wp_options`)
- Runtime reader: `bw_header_get_settings()`
- Runtime bridge to JS: `bwHeaderConfig` localized in `includes/modules/header/frontend/assets.php`

Smart Scroll activation input:
- `features.smart_scroll` (boolean)

Smart Scroll behavioral inputs:
- `smart_header.scroll_down_threshold` (px)
- `smart_header.scroll_up_threshold` (px)
- `smart_header.scroll_delta` (px)
- `breakpoints.mobile` (px)

Smart Scroll visual inputs:
- `smart_header.header_bg_color`
- `smart_header.header_bg_opacity`
- `smart_header.header_scrolled_bg_color`
- `smart_header.header_scrolled_bg_opacity`

Blur controls:
- `smart_header.menu_blur_enabled`
- `smart_header.menu_blur_amount`
- `smart_header.menu_blur_radius`
- `smart_header.menu_blur_tint_color`
- `smart_header.menu_blur_tint_opacity`
- `smart_header.menu_blur_scrolled_tint_color`
- `smart_header.menu_blur_scrolled_tint_opacity`
- `smart_header.menu_blur_padding_top/right/bottom/left`

Related fallback/aux inputs:
- `background_color` (General tab)
- `background_transparent` (General tab)

Normative rules:
- Smart Scroll runtime MUST consume only sanitized settings values.
- Any missing setting MUST resolve through default values from the settings schema.
- Runtime thresholds MUST be treated as presentation triggers, not business state.

## 2) State Definitions

The Smart Scroll state machine is implemented through CSS class outputs on `.bw-custom-header`.

### State: `top/reset`
- Semantic meaning: viewport near top (`scrollTop <= 2`).
- Required class output:
  - `bw-header-hidden` removed
  - `bw-header-visible` removed
- Side effect:
  - `bw-header-scrolled` removed when at top

### State: `visible`
- Semantic meaning: header shown in smart-scroll flow.
- Required class output:
  - `bw-header-visible` present
  - `bw-header-hidden` absent

### State: `hidden`
- Semantic meaning: header hidden while scrolling down past activation.
- Required class output:
  - `bw-header-hidden` present
  - `bw-header-visible` absent

### State: `scrolled`
- Semantic meaning: non-top scroll visual mode.
- Required class output:
  - `bw-header-scrolled` present when `scrollTop > 2`
  - absent at top

### State: `on-dark-zone`
- Semantic meaning: header overlaps dark section above configured overlap threshold.
- Required class output:
  - `bw-header-on-dark` present when active
  - removed otherwise

### State: responsive mode
- Semantic meaning: layout context for runtime and CSS.
- Required class output:
  - `is-mobile` when viewport <= configured breakpoint
  - `is-desktop` when viewport > configured breakpoint
  - classes are mutually exclusive

## 3) Transition Rules

### Boot / init
- On `DOMContentLoaded` (or immediate boot if DOM already ready), runtime MUST:
  - ensure header node exists in body flow position expected by module
  - compute responsive mode (`is-mobile`/`is-desktop`)
  - initialize sticky/scroll listeners only when smart-scroll data flag is active
  - remove preload class to reveal header

### Scroll down hide rule
- If current position exceeds activation point `max(headerHeight, scroll_down_threshold)`, and scroll direction is down with movement above `scroll_delta`, runtime MAY enter `hidden`.

### Scroll up show rule
- On upward movement above `scroll_delta`, runtime MUST evaluate `scroll_up_threshold`.
- When condition is met, runtime MUST enter `visible`.

### Top/reset rule
- At or near top (`<= 2px`), runtime MUST force `top/reset` state.
- `top/reset` MUST clear hidden/visible transition classes.

### Scrolled toggle rule
- When `scrollTop > 2`, runtime MUST apply `bw-header-scrolled`.
- When `scrollTop <= 2`, runtime MUST remove `bw-header-scrolled`.

### Dark-zone toggle rule
- Runtime MUST evaluate overlap between header rect and detected dark zones.
- If overlap threshold condition is met, runtime MUST apply `bw-header-on-dark`.
- If not met, runtime MUST remove `bw-header-on-dark`.
- Detection sources MAY include:
  - manual markers (`.smart-header-dark-zone`)
  - auto-detected dark sections (color analysis path)

### Resize recomputation rule
- On resize, runtime MUST:
  - recompute responsive mode class
  - recompute offsets used by fixed/sticky presentation
  - re-evaluate dark-zone overlap state

## 4) Precedence Rules (General vs Header Scroll)

When `features.smart_scroll = true`:
- Smart Header visual controls MUST override General background controls for header background rendering.
- `header_bg_*` and `header_scrolled_bg_*` values MUST drive smart and scrolled visual states.
- Blur panel controls MUST apply according to `menu_blur_enabled` and related values.

When `features.smart_scroll = false`:
- General background rules MUST apply:
  - if `background_transparent = true`, header background MUST be transparent
  - otherwise `background_color` MUST apply

Mobile forced override (real behavior):
- Mobile scrolled state includes explicit forced background override to white in injected runtime CSS.
- This override MUST be treated as current authoritative presentation behavior in mobile scrolled context.

## 5) Non-Authority / Safety Rules

- This state machine is presentation-only.
- It MUST NOT infer payment truth.
- It MUST NOT infer order truth.
- It MUST NOT infer entitlement, provisioning, or consent truth.
- It MUST NOT mutate business authority state.
- It MUST preserve reachability of navigation surfaces regardless of state.
- It CANNOT block commerce flows by design.

## 6) Failure / Degrade-Safely

IntersectionObserver missing:
- Dark-zone observer optimization MAY be unavailable.
- Header MUST continue scroll visibility behavior without observer-driven enhancements.
- Dark-zone styling MAY degrade, but navigation MUST remain functional.

JS failure mid-scroll:
- Dynamic class transitions MAY stop.
- Rendered header links/menu structure MUST remain available according to server-rendered markup and CSS baseline.
- Failure MUST NOT block page navigation or checkout access paths.

Refresh at mid-scroll:
- Runtime MUST initialize from current scroll position on boot.
- Header MUST converge to a stable visual state (top/reset, visible, or scrolled-consistent) after initialization.

## 7) Regression Checklist (Scroll)

1. Smart Scroll OFF baseline
- Action: disable smart scroll and load desktop page.
- Expected: General background rules apply; no smart hide/show behavior.

2. Smart Scroll ON desktop down/up
- Action: scroll down beyond activation threshold, then scroll up.
- Expected: header hides on down and reappears on up according to thresholds/delta.

3. Top reset
- Action: return to top (`scrollTop ~ 0`).
- Expected: hidden/visible classes clear; scrolled class removed.

4. Scrolled visual toggle
- Action: move just above and below top threshold.
- Expected: `bw-header-scrolled` toggles deterministically.

5. Mobile breakpoint behavior
- Action: test below and above configured breakpoint.
- Expected: `is-mobile` / `is-desktop` class switch; mobile visual overrides apply only in mobile context.

6. Mobile forced scrolled override
- Action: in mobile mode, scroll to trigger scrolled state.
- Expected: mobile forced background override is visible and stable.

7. Dark-zone transition
- Action: pass header across dark-zone sections.
- Expected: `bw-header-on-dark` toggles without oscillation under stable overlap.

8. Mid-scroll refresh
- Action: refresh page while scrolled.
- Expected: state converges after boot without blocking interaction.

9. Resize recomputation
- Action: resize viewport across breakpoint while scrolled.
- Expected: responsive class and scroll visual state recompute correctly.

10. Safety check
- Action: interact with navigation/cart links while header changes state.
- Expected: navigation remains operational; no commerce-blocking behavior.
