# Header Admin Settings Map

## 1) Storage & Sanitation Model

Canonical storage:
- Option key: `bw_header_settings`
- Storage engine: `wp_options`
- Setting registration: `register_setting(..., BW_HEADER_OPTION_KEY, ...)`

Default + merge flow:
- Defaults source: `bw_header_default_settings()`
- Runtime reader: `bw_header_get_settings()`
- Merge rule: `array_replace_recursive(defaults, saved)`

Sanitization gate:
- Write gate: `bw_header_sanitize_settings($input)`
- All persisted values MUST pass sanitization before storage.

Reader contract:
- Frontend/runtime consumers MUST read settings through merged + sanitized data (`bw_header_get_settings()`).
- Consumers MUST NOT assume raw/unvalidated option payloads.

Authority boundary:
- Header settings are presentation and UX orchestration controls only.
- Header settings MUST NOT be interpreted as business authority controls.

---

## 2) Settings Inventory Table (Exhaustive)

| Setting key path | Default value | Type | Admin group/tab | Frontend consumer(s) | Affects | Notes / risks |
|---|---:|---|---|---|---|---|
| `enabled` | `0` | checkbox | General | `bw_header_is_enabled()`, `bw_header_render_frontend()`, `bw_header_enqueue_assets()`, legacy enqueue early-return | global header activation | Tier 0 switch. Enables custom mode and suppresses legacy mode. |
| `header_title` | `Blackwork Header` | text | General | template `header.php` (`aria-label`), localized JS title | accessibility/title metadata | Presentation-only. |
| `hero_overlap.enabled` | `0` | checkbox | Hero Overlap | `bw_header_is_hero_overlap_enabled()`, `bw_header_is_hero_overlap_active()`, `header-render.php`, `frontend/assets.php`, `header-init.js` | page-scoped overlay mode activation | V1 gate. Enables per-page overlap logic without changing dark-zone detector authority. |
| `hero_overlap.page_ids` | `[]` | array<int> | Hero Overlap | `bw_header_get_hero_overlap_page_ids()`, `bw_header_is_hero_overlap_active()` | page targeting | Applied only on singular pages whose IDs are present in the saved list. |
| `background_color` | `#efefef` | color | General | PHP inline CSS in `frontend/assets.php` | desktop/mobile base bg (when smart scroll off) | Overridden by smart settings when `features.smart_scroll=1`. |
| `background_transparent` | `0` | checkbox | General | PHP inline CSS in `frontend/assets.php` | transparent base mode | Applies only when smart scroll off. |
| `inner_padding_unit` | `px` | select (`px`/`%`) | General | PHP inline CSS in `frontend/assets.php` | header inner spacing | Affects `.bw-custom-header__inner`. |
| `inner_padding.top` | `18` | number | General | PHP inline CSS in `frontend/assets.php` | desktop spacing | Unit from `inner_padding_unit`. |
| `inner_padding.right` | `28` | number | General | PHP inline CSS in `frontend/assets.php` | desktop spacing | Unit from `inner_padding_unit`. |
| `inner_padding.bottom` | `18` | number | General | PHP inline CSS in `frontend/assets.php` | desktop spacing | Unit from `inner_padding_unit`. |
| `inner_padding.left` | `28` | number | General | PHP inline CSS in `frontend/assets.php` | desktop spacing | Unit from `inner_padding_unit`. |
| `logo_attachment_id` | `0` | media ID | General | `bw_header_get_logo_markup()`, template `header.php` | desktop/mobile logo | Fallback logo renders if missing. |
| `logo_width` | `54` | number | General | `bw_header_get_logo_markup()` | logo dimensions | Presentation-only. |
| `logo_height` | `54` | number | General | `bw_header_get_logo_markup()` | logo dimensions | `0` means auto-height behavior. |
| `menus.desktop_menu_id` | `0` | select | General | `bw_header_render_menu()`, template `header.php` | desktop navigation | If empty, nav feature may effectively disable. |
| `menus.mobile_menu_id` | `0` | select | General | `bw_header_render_menu()`, template `mobile-nav.php` | mobile navigation | Falls back to desktop menu when empty. |
| `breakpoints.mobile` | `1024` | number | General / Responsive | PHP inline media CSS in `frontend/assets.php`; localized `bwHeaderConfig.breakpoint`; JS `header-init.js` | desktop/mobile split, scroll responsive class | CSS+JS drift risk if non-canonical breakpoints are introduced elsewhere. |
| `mobile_layout.right_icons_gap` | `16` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile right icon spacing | Presentation-only. |
| `mobile_layout.cart_badge_offset_x` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart badge position | Can create visual drift if extreme. |
| `mobile_layout.cart_badge_offset_y` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart badge position | Can create overlap artifacts. |
| `mobile_layout.cart_badge_size` | `1.2` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile badge size | Presentation-only. |
| `mobile_layout.desktop_cart_badge_offset_x` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | desktop cart badge position | Desktop-only visual tuning. |
| `mobile_layout.desktop_cart_badge_offset_y` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | desktop cart badge position | Desktop-only visual tuning. |
| `mobile_layout.desktop_cart_badge_size` | `1.2` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | desktop badge size | Presentation-only. |
| `mobile_layout.inner_padding.top` | `14` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile header padding | Impacts mobile panel compactness. |
| `mobile_layout.inner_padding.right` | `18` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile header padding | Impacts mobile panel compactness. |
| `mobile_layout.inner_padding.bottom` | `14` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile header padding | Impacts mobile panel compactness. |
| `mobile_layout.inner_padding.left` | `18` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile header padding | Impacts mobile panel compactness. |
| `mobile_layout.hamburger_padding.top` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle hit-area | Presentation-only. |
| `mobile_layout.hamburger_padding.right` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle hit-area | Presentation-only. |
| `mobile_layout.hamburger_padding.bottom` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle hit-area | Presentation-only. |
| `mobile_layout.hamburger_padding.left` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle hit-area | Presentation-only. |
| `mobile_layout.hamburger_margin.top` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle positioning | Margin allows negative values (drift risk). |
| `mobile_layout.hamburger_margin.right` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle positioning | Margin allows negative values (drift risk). |
| `mobile_layout.hamburger_margin.bottom` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle positioning | Margin allows negative values (drift risk). |
| `mobile_layout.hamburger_margin.left` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile nav toggle positioning | Margin allows negative values (drift risk). |
| `mobile_layout.search_padding.top` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search button spacing | Presentation-only. |
| `mobile_layout.search_padding.right` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search button spacing | Presentation-only. |
| `mobile_layout.search_padding.bottom` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search button spacing | Presentation-only. |
| `mobile_layout.search_padding.left` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search button spacing | Presentation-only. |
| `mobile_layout.search_margin.top` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search positioning | Margin drift risk if extreme values used. |
| `mobile_layout.search_margin.right` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search positioning | Margin drift risk if extreme values used. |
| `mobile_layout.search_margin.bottom` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search positioning | Margin drift risk if extreme values used. |
| `mobile_layout.search_margin.left` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile search positioning | Margin drift risk if extreme values used. |
| `mobile_layout.cart_padding.top` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart button spacing | Presentation-only. |
| `mobile_layout.cart_padding.right` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart button spacing | Presentation-only. |
| `mobile_layout.cart_padding.bottom` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart button spacing | Presentation-only. |
| `mobile_layout.cart_padding.left` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart button spacing | Presentation-only. |
| `mobile_layout.cart_margin.top` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart positioning | Margin drift risk if extreme values used. |
| `mobile_layout.cart_margin.right` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart positioning | Margin drift risk if extreme values used. |
| `mobile_layout.cart_margin.bottom` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart positioning | Margin drift risk if extreme values used. |
| `mobile_layout.cart_margin.left` | `0` | number | General / Responsive | PHP inline CSS in `frontend/assets.php` | mobile cart positioning | Margin drift risk if extreme values used. |
| `icons.mobile_hamburger_attachment_id` | `0` | media ID | Icons | `bw_header_get_icon_markup()`, template `header.php` | mobile nav icon | Fallback inline SVG used if absent. |
| `icons.mobile_cart_attachment_id` | `0` | media ID | Icons | `bw_header_get_icon_markup()`, template `header.php` | cart icon | Fallback inline SVG used if absent. |
| `icons.mobile_search_attachment_id` | `0` | media ID | Icons | `bw_header_get_icon_markup()`, template `header.php` | search icon | Fallback inline SVG used if absent. |
| `labels.search` | `Search` | text | Labels | template rendering (`header.php`) | search button text | Mobile may hide label and show icon only. |
| `labels.account` | `Account` | text | Labels | template rendering (`header.php`) | account link text | Presentation-only. |
| `labels.cart` | `Cart` | text | Labels | template rendering (`header.php`) | cart text/aria label | Presentation-only. |
| `links.account` | `/my-account/` | text/url | Links | template rendering (`header.php`, `mobile-nav.php`) | account navigation | Must remain reachable under failures. |
| `links.cart` | `/cart/` | text/url | Links | template rendering (`header.php`), fallback in `bw-navshop.js` | cart navigation | Popup API unavailable => URL fallback. |
| `features.search` | `1` | checkbox flag | Features (not fully exposed in current UI) | `header-render.php` conditional block; asset load remains module-wide | search block render | Not explicitly surfaced in current admin form; sanitize preserves saved value when key omitted. |
| `features.navigation` | `1` | checkbox flag | Features (not fully exposed in current UI) | `header-render.php` conditional block | nav render | Same non-exposed note as above. |
| `features.navshop` | `1` | checkbox flag | Features (not fully exposed in current UI) | `header-render.php` conditional block | account/cart block render | Same non-exposed note as above. |
| `features.smart_scroll` | `0` | checkbox | Header Scroll | `header-render.php` (`data-smart-scroll` and classes), `frontend/assets.php` inline CSS branch, localized JS config (`bwHeaderConfig.smartScroll`) | scroll behavior + visual precedence | Tier 0 UX toggle. |
| `smart_header.scroll_down_threshold` | `100` | number | Header Scroll | localized JS config read by `header-init.js` | hide-on-scroll-down trigger | Influences transition activation. |
| `smart_header.scroll_up_threshold` | `0` | number | Header Scroll | localized JS config read by `header-init.js` | show-on-scroll-up trigger | `0` means immediate show on up movement. |
| `smart_header.scroll_delta` | `1` | number | Header Scroll | localized JS config read by `header-init.js` | direction sensitivity | Too low can amplify jitter; still presentation-only. |
| `smart_header.header_bg_color` | `#efefef` | color | Header Scroll | PHP inline CSS in `frontend/assets.php` | smart base background | Active only when smart scroll enabled. |
| `smart_header.header_bg_opacity` | `1` | number/range | Header Scroll | PHP inline CSS in `frontend/assets.php` | smart base opacity | Active only when smart scroll enabled. |
| `smart_header.header_scrolled_bg_color` | `#efefef` | color | Header Scroll | PHP inline CSS in `frontend/assets.php` | scrolled background | Active only when smart scroll enabled. |
| `smart_header.header_scrolled_bg_opacity` | `0.86` | number/range | Header Scroll | PHP inline CSS in `frontend/assets.php` | scrolled opacity | Active only when smart scroll enabled. |
| `smart_header.menu_blur_enabled` | `1` | checkbox | Header Scroll | PHP inline CSS branch in `frontend/assets.php`; template class `is-blur-enabled` | desktop/mobile panel blur | Gates all blur sub-settings. |
| `smart_header.menu_blur_amount` | `20` | number | Header Scroll | PHP inline CSS in `frontend/assets.php` | blur intensity | Applies only if blur enabled. |
| `smart_header.menu_blur_radius` | `12` | number | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel corner radius | Applies only if blur enabled. |
| `smart_header.menu_blur_tint_color` | `#ffffff` | color | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel tint normal state | Applies only if blur enabled. |
| `smart_header.menu_blur_tint_opacity` | `0.15` | number/range | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel tint normal state | Applies only if blur enabled. |
| `smart_header.menu_blur_scrolled_tint_color` | `#ffffff` | color | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel tint scrolled state | Applies only if blur enabled. |
| `smart_header.menu_blur_scrolled_tint_opacity` | `0.15` | number/range | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel tint scrolled state | Applies only if blur enabled. |
| `smart_header.menu_blur_padding_top` | `5` | number | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel internal spacing | Applies only if blur enabled. |
| `smart_header.menu_blur_padding_right` | `10` | number | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel internal spacing | Applies only if blur enabled. |
| `smart_header.menu_blur_padding_bottom` | `5` | number | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel internal spacing | Applies only if blur enabled. |
| `smart_header.menu_blur_padding_left` | `10` | number | Header Scroll | PHP inline CSS in `frontend/assets.php` | panel internal spacing | Applies only if blur enabled. |

---

## 3) Precedence & Override Rules

Smart Scroll precedence:
- When `features.smart_scroll = 1`, `smart_header.*` visual values MUST override General background/transparency values for header smart/scrolled rendering.

Hero Overlap precedence:
- When `hero_overlap.enabled = 1` and current page ID is selected, runtime MUST:
  - keep the header in overlay/fixed mode from first paint
  - keep wrapper background transparent
  - continue using the existing dark-zone detection as the only authority for `bw-header-on-dark`
  - force panel blur availability even if the page is not using Smart Scroll
- `Hero Overlap` MUST NOT introduce a second color-detection system.

General fallback precedence:
- When `features.smart_scroll = 0`, General visual values MUST apply:
  - `background_transparent = 1` => transparent base background
  - otherwise => `background_color`

Mobile forced override:
- Mobile scrolled state includes a forced white background override in runtime-injected CSS.
- This behavior MUST be treated as current implementation authority for mobile scrolled presentation.

Menu blur gating:
- `smart_header.menu_blur_*` visual values MUST apply only when `smart_header.menu_blur_enabled = 1`.
- If blur is disabled, runtime MUST render non-blur panel fallback styles.
- Exception: `Hero Overlap` forces panel blur availability for the header panel so the overlay starts with the intended glass treatment.

Menu fallback:
- `menus.mobile_menu_id` MUST fallback to `menus.desktop_menu_id` when empty.

Feature key exposure note:
- `features.search`, `features.navigation`, and `features.navshop` are part of the persisted model.
- Current admin UI does not explicitly expose dedicated toggles for all these keys.
- Sanitization preserves prior saved values for omitted keys, preventing silent resets.

---

## 4) Presentation-Only / Non-Authority Guarantees

- All Header settings MUST be treated as presentation and UX-orchestration controls.
- No Header setting may elevate the Header into an authority layer.
- Header settings MUST NOT mutate:
  - payment truth
  - order lifecycle truth
  - provisioning/entitlement truth
  - consent truth
- Header settings MAY influence visual state, layout, and interaction patterns only.

---

## 5) Admin Regression Checklist

1. Save and sanitation validation
- Save Header settings in admin.
- Verify no invalid values persist outside sanitize bounds.

2. Desktop render validation
- Verify logo/menu/search/navshop output reflects saved settings.

3. Mobile render validation
- Verify breakpoint switch, mobile paddings/margins/icons/badge offsets are applied.

4. Scroll behavior validation
- With smart scroll ON/OFF, verify precedence and state transitions match configuration.

4b. Hero Overlap validation
- Verify selected pages start directly in overlay mode.
- Verify the first hero section loads under the header without a first-paint jump.
- Verify manual `.smart-header-dark-zone` markers still drive the same dark detection path.
- Verify mobile panel glass is visible from startup.

5. Search overlay validation
- Verify search open/close and live-search behavior remain functional.

6. Cart badge validation
- Verify cart count render and Woo fragment updates for header badge.

7. Cart popup fallback validation
- Verify popup open path when `window.BW_CartPopup.openPanel` exists.
- Verify URL fallback to cart link when popup API is unavailable.

8. Elementor preview non-interference
- Verify custom header runtime does not interfere with Elementor preview/editor behavior.

9. Legacy fallback validation
- With custom header disabled, verify legacy smart-header path remains operational as fallback.
