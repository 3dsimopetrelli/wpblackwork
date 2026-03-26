# 2026-03-25 Hero Overlap Hardening

## Scope

This task closed the first production pass of the Header `Hero Overlap` mode and the follow-up fixes required to make it stable in admin and frontend contexts.

## Delivered

- Added `Hero Overlap` as a new Header admin tab with:
  - `Enable Hero Overlap`
  - `Selected Pages`
- Added persisted settings under `bw_header_settings.hero_overlap.*`.
- Added page-scoped runtime activation helpers in `frontend/assets.php`.
- Added `bw-header--hero-overlap` runtime class and `data-hero-overlap` template attribute.
- Reused the existing dark-zone detection already used by the header; no duplicate color-detection path was introduced.

## Frontend Hardening

- Fixed first-paint flash where the page briefly showed a normal header before switching to overlay.
- Fixed the initial hero jump by starting `Hero Overlap` in fixed/transparent mode directly from server-rendered CSS.
- Delayed final header reveal until layout rechecks completed after:
  - `requestAnimationFrame`
  - `window.load`
  - `document.fonts.ready`
- Forced glass visibility for `Hero Overlap` panels in both desktop and mobile.
- Removed the temporary 1px glass border after visual review; blur and soft shadow remain.
- Extended dark-zone icon parity so mobile hamburger, search, and cart icons switch like the logo.
- Kept desktop `Search` label black even when the header enters `bw-header-on-dark`, because the search pill remains bright green.

## Admin Hardening

- Fixed empty `Header Scroll` and `Hero Overlap` panels caused by invalid tab panel nesting.
- Added server-side tab fallback using `?tab=...`, so the Header admin screen stays navigable even if the tab JS fails or is cached incorrectly.

## Related Widget Fix

- `BW-UI Hero Slide` title sanitization now allows inline `style` on `<span>`.
- This was needed so editorial underline treatments entered in Elementor WYSIWYG render on the frontend instead of being stripped during sanitization.

## Operational Notes

- `Hero Overlap` is intentionally page-scoped in V1.
- Dark-zone authority remains in the existing header detector and optional `.smart-header-dark-zone` markers.
- The mode is presentation-only and does not change business or WooCommerce authority boundaries.
