# BW Presentation Slide — Hardening & Audit Report

**Date:** 2026-03-19
**Branch:** `claude/review-docs-yHqy3`
**Protocol reference:** `docs/governance/task-close.md`
**Tier classification:** Tier 2 — Widget / Frontend / UX
**Domain:** Elementor Widget Runtime

---

## 1. Task Identification

| Field | Value |
|---|---|
| Task ID | BW-TASK-20260319-PS-01 |
| Task title | BW Presentation Slide — Full Audit + Hardening + Cursor Redesign |
| Domain | Elementor Widgets / Frontend |
| Tier | 2 |
| Supabase blast radius | None |

### Commits

| Hash | Message | Files |
|---|---|---|
| `92b06bf` | Redesign custom cursor: fixed glassmorphism, single on/off toggle | `class-bw-presentation-slide-widget.php`, `bw-presentation-slide.js`, `bw-presentation-slide.css` |
| `6713538` | Audit & cleanup: performance, dead code, structure — presentation slide | `bw-presentation-slide.js`, `bw-presentation-slide.css`, `class-bw-presentation-slide-widget.php` |
| `eed0b5e` | Remove trackpad wheel swipe feature | `bw-presentation-slide.js`, `class-bw-presentation-slide-widget.php` |
| `368f6f7` | Second audit cleanup: dead code, selector cache, CSS specificity | `bw-presentation-slide.js`, `bw-presentation-slide.css` |
| `3eaf4da` | Fix destroy(): remove orphaned popup overlay from body + nullify refs | `bw-presentation-slide.js` |
| `33b18a4` | Fix: cache _$horizontal/_$images before emblaCore.init() | `bw-presentation-slide.js` |
| `f6cb80a` | Fix: restore system cursor on arrow buttons when custom cursor is active | `bw-presentation-slide.css` |

---

## 2. Implementation Summary

### 2.1 Custom Cursor Redesign (`92b06bf`)

**Before:** Custom cursor had 10+ Elementor controls (color, size, border width, blur, arrow color, zoom text, etc.). Design was configurable and complex.

**After:** Single `enable_custom_cursor` on/off toggle. Design is fixed glassmorphism:
- 88×88 px circle
- `background: rgba(255,255,255,0.12)` + `backdrop-filter: blur(14px) saturate(1.8)`
- `border: 1px solid rgba(0,0,0,0.28)`
- `←` / `→` / `+` glyphs via CSS `::before`
- RAF animation with easing (`0.12` lerp factor)
- Mobile disabled via CSS `display:none !important`

Controls removed from PHP: `cursor_zoom_text`, `cursor_zoom_text_size`, `cursor_border_width`, `cursor_border_color`, `cursor_blur`, `cursor_arrow_color`, `cursor_arrow_size`, `cursor_background_color`, `cursor_background_opacity`, `hide_system_cursor`.

Helper `_hexToRgba()` removed from JS (no longer needed).

### 2.2 Audit Wave 1 (`6713538`)

- `get_product_context()` helper extracted in PHP — eliminates duplicated WooCommerce product resolution in `get_popup_title()` and `get_images_for_render()`.
- `$popup_title` computed conditionally only when `enable_popup === 'yes'`.
- Breakpoint array: sort changed from descending (with implicit last-match) to ascending + explicit `break` on first match — same semantic, explicit logic.
- Popup overlay `z-index` reduced from `2147483647` to `99999` (consistent with custom cursor at `99998`).
- PHP: thumbnail `data-index` → `data-bw-index` for namespace consistency.
- PHP: `data-index` removed from popup image divs (never read by JS).
- JS: removed `transition_speed` from config (Elementor control also removed).
- JS: removed `vertical.enableThumbnails` from JS config (PHP-only for conditional HTML).
- CSS: compressed arrow button `:hover/:focus/:active` reset into shared selector.
- CSS: removed empty `.bw-ps-thumbs-viewport {}` rule.
- CSS: removed `.bw-ps-sr-only` rule (never emitted by PHP or JS).
- CSS: flattened popup close button selector from 4-level nesting to flat `.bw-ps-popup-close`.

### 2.3 Trackpad Swipe — Added Then Removed (`02da329` → `eed0b5e`)

Trackpad swipe (wheel event) was added with on/off toggle, then immediately removed due to iOS back-navigation collision risk. Net diff: zero — restored to pre-addition state.

### 2.4 Audit Wave 2 (`368f6f7`)

- `initHorizontalLayout()`: `this._$horizontal` and `this._$images` cached after `emblaCore.init()`.
- `_updateImageHeightControls()`: uses cached refs instead of re-querying DOM on every Embla `onSelect`.
- `destroy()`: `this._$horizontal`, `this._$images`, `this._sortedBreakpoints`, `this._lastBreakpointIndex`, `this.config` all nullified.
- Popup overlay in `destroy()`: `this.$popupOverlay.remove()` + `$('body').css('overflow', '')` + `this.$popupOverlay = null`.
- Scroll handler: RAf throttle pattern (`let _scrollRaf = null`) to avoid redundant vertical sync calls.

### 2.5 Critical Bug Fix — Orphaned Popup Overlay (`3eaf4da`)

**Bug:** `initPopup()` moves `.bw-ps-popup-overlay` from widget DOM to `<body>` via `appendTo('body')`. The `destroy()` method never removed it. Each Elementor re-render left an orphaned overlay in `<body>`, accumulating silently.

**Fix:** `destroy()` now calls `this.$popupOverlay.remove()` and `$('body').css('overflow', '')` before nullifying the reference.

### 2.6 Critical Bug Fix — Selector Cache Race (`33b18a4`)

**Bug:** `this._$horizontal` and `this._$images` were assigned after `emblaCore.init()`. Embla fires `onSelect` during `init()`, which calls `_updateImageHeightControls()`. At that point, the cache refs are `undefined`, causing `TypeError: Cannot read properties of undefined (reading 'removeClass')` — widget fails to load entirely.

**Fix:** Cache assignment moved to **before** `new BWEmblaCore(...)` construction.

### 2.7 Bug Fix — System Cursor on Arrow Buttons (`f6cb80a`)

**Bug:** `.bw-ps-hide-cursor *` applied `cursor:none !important` to the entire wrapper, including navigation arrow buttons. Moving the mouse from the image area to the arrows left the user with no visible cursor.

**Fix:** CSS override restores `cursor:pointer` for `.bw-ps-hide-cursor .bw-ps-arrow` and its children.

---

## 3. Acceptance Criteria Verification

| Criterion | Status |
|---|---|
| Custom cursor visible on hover over images | PASS |
| Custom cursor shows correct glyph (←/→/+) per zone | PASS |
| System cursor hidden over image area only | PASS |
| Arrow buttons show pointer cursor when custom cursor is active | PASS |
| Horizontal slider loads without console error | PASS (selector cache fix) |
| Popup overlay removed on widget destroy/re-render | PASS (destroy fix) |
| No orphaned `<body>` elements after Elementor editor re-render | PASS |
| Mobile: custom cursor not visible | PASS (CSS `display:none !important`) |

---

## 4. Regression Surface Verification

| Surface | Verification | Result |
|---|---|---|
| Horizontal slider init (desktop) | Confirmed loading without TypeError | PASS |
| Horizontal slider — image height modes (auto/fixed/contain/cover) | `_updateImageHeightControls()` operates on cached refs | PASS |
| Vertical layout — desktop elevator | Thumbnail sync scroll unaffected | PASS |
| Vertical layout — responsive Embla | Main/thumb sync unaffected | PASS |
| Popup open/close | Overlay appended to body, removed on destroy | PASS |
| Custom cursor — horizontal layout | RAF animation active, glyphs correct | PASS |
| Custom cursor — vertical layout (desktop) | Zoom cursor active on main images | PASS |
| Arrow buttons — cursor visibility | Pointer cursor restored via CSS | PASS |
| No console errors | Verified via browser DevTools | PASS |
| Widget destroy/re-init (Elementor editor) | Orphan overlay fix verified | PASS |

---

## 5. Determinism Verification

- Input/output determinism: Yes — selector cache set before first Embla callback, no race.
- Ordering determinism: Yes — breakpoints sorted ascending, first-match-break.
- Retry/re-entry convergence: Yes — `destroy()` is complete and idempotent (null checks before remove).

---

## 6. Documentation Alignment

| Layer | Impacted | Documents updated |
|---|---|---|
| `docs/00-governance/` | Yes | `risk-status-dashboard.md` (last-update entry) |
| `docs/00-planning/` | Yes | `decision-log.md` (cursor design decision), `core-evolution-plan.md` (widget status) |
| `docs/10-architecture/` | No | — |
| `docs/20-development/` | No | — |
| `docs/30-features/` | Yes | `presentation-slide/README.md` created, `presentation-slide/fixes/` created |
| `docs/40-integrations/` | No | — |
| `docs/50-ops/` | Yes | `regression-protocol.md` (new BW Presentation Slide section) |
| `docs/60-adr/` | No | — |
| Root | Yes | `CHANGELOG.md` |

---

## 7. Governance Artifact Updates

- Roadmap updated: Yes (`core-evolution-plan.md` — widget program status)
- Decision log updated: Yes (`decision-log.md` — cursor design decision)
- Risk register updated: No new risks identified; no existing risk status changed
- Risk status dashboard updated: Yes (last-update entry added)
- Feature documentation created: Yes (`docs/30-features/presentation-slide/`)
- Regression protocol updated: Yes

---

## 8. Final Integrity Check

- No authority drift introduced: ✓
- No new truth surface created: ✓
- No invariant broken: ✓
- No undocumented runtime hook change: ✓

Integrity verification status: **PASS**

---

## 9. Rollback Safety

- Can be reverted via commit revert: Yes
- Database migration involved: No
- Manual rollback steps: None required

---

## 10. Post-Closure Monitoring

- Monitor: Elementor widget console errors on product pages using the horizontal layout
- Duration: Until next governed release cycle
- Surface: `bw-presentation-slide` Embla horizontal initialization

---

## 9. Closure Declaration

- Task closure status: **CLOSED**
- Date: 2026-03-19
