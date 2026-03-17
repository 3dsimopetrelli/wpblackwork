# R-SEC-30 / R-SEC-31 — Cart Popup XSS Hardening Closure

## Task Identification
- Task ID: `R-SEC-30` / `R-SEC-31`
- Title: Cart popup XSS hardening — item name/permalink and coupon code escaping
- Domain: Security / Cart Popup Frontend
- Tier classification: Tier 2 (non-payment, non-auth UI surface)
- Closure date: `2026-03-16`
- Final task status: `CLOSED`
- Final risk status: `RESOLVED` (both risks)

## Protocol Reference
- Closure executed following: `docs/governance/task-close.md`

## Files Changed
- `cart-popup/assets/js/bw-cart-popup.js`

## Implementation Summary

### R-SEC-30: item.name and item.permalink in renderCartItems()
`item.name` and `item.permalink` were interpolated directly into HTML template
literals without escaping. Added `_escHtml()` and `_escUrl()` helpers to
`BW_CartPopup` and applied them:
- `item.permalink` → `self._escUrl(item.permalink)` in `href` attribute
- `item.name` → `self._escHtml(item.name)` as anchor text content

### R-SEC-31: coupon code in updateTotals()
`code` was interpolated as `<b>${code}</b>` and in `data-code="${code}"` without
escaping. Applied `_escHtml()` to produce `safeCode` before both interpolations.

### Helpers added
```javascript
_escHtml(str)  // escapes & < > " '
_escUrl(url)   // strips javascript:/data: then escapes HTML
```

## Runtime Surfaces Touched
- `renderCartItems()` — cart item list HTML generation
- `updateTotals()` — coupon row HTML generation

No new hooks, AJAX endpoints, filters, or admin routes.

## Acceptance Criteria Verification
- ✅ `item.name` HTML-escaped before insertion into DOM
- ✅ `item.permalink` URL-sanitized (protocol injection blocked) and HTML-escaped before `href`
- ✅ coupon `code` HTML-escaped in both `<b>` text and `data-code` attribute
- ✅ trusted server-rendered HTML (`item.image`, prices) left unmodified
- ✅ `item.key` (MD5) and `item.product_id` (parseInt) confirmed safe — not escaped (unnecessary)

## Regression Surface Verification
- Cart popup open/close: no impact (helper methods only, no structural change)
- Cart item rendering: item links and names render correctly with escaped output
- Coupon apply/remove flow: `data-code` value read by `bw_remove_coupon_btn_dynamic` handler
  remains correct after HTML-escaping (WC coupon codes are alphanumeric, escaping is identity)
- No Supabase/auth/payment surface touched

## Determinism Verification
- `_escHtml` and `_escUrl` are pure functions — deterministic for any input
- No ordering dependency introduced

## Documentation Alignment Verification
- `docs/00-governance/risk-register.md` — R-SEC-30 and R-SEC-31 added as RESOLVED
- `docs/00-governance/risk-status-dashboard.md` — rows added, counts updated, activity log updated
- `docs/30-features/cart-popup/cart-popup-technical-guide.md` — section 15.1 added

## Governance Artifact Updates
- Risk register updated: ✅
- Risk status dashboard updated: ✅
- Feature documentation updated: ✅

## Rollback Safety
- Revertable via `git revert fd2f5be1`
- No database migration, no admin option change
- No monitoring required beyond standard cart popup smoke test

## Closure Declaration
- Task closure status: `CLOSED`
- Date: `2026-03-16`
