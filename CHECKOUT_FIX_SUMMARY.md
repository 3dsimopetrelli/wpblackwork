# WooCommerce Checkout Thumbnail Fix - Quick Summary

## The Problem
Product thumbnails in checkout order review section:
- **Specified:** 65px width
- **Actual rendered:** 144.82px width
- **Behavior:** Dimensions jump when applying/removing coupons

## Root Cause (FOUND!)
The table lacks `table-layout: fixed`, so the browser uses `table-layout: auto` by default. This allows the browser's table layout algorithm to recalculate and override column widths whenever content changes (e.g., adding/removing coupon rows).

**How it happens:**
1. Table has `width: 100%` (fills container)
2. Browser scans all rows to calculate "optimal" column distribution
3. Browser **ignores** the `width: 65px` constraint on thumbnail cell
4. When coupon rows are added/removed, table recalculates → dimensions jump

## The Fix (2 lines of CSS)

### Critical Fix (solves the main issue):
```css
/* Line 363 in bw-checkout.css */
.bw-checkout-right table.shop_table {
    width: 100%;
    table-layout: fixed; /* ADD THIS LINE */
}
```

### Recommended Fix (prevents 2px overflow):
```css
/* Line 807 in bw-checkout.css */
body.woocommerce-checkout .bw-checkout-right .bw-review-item__media {
    border: 1px solid #e1e1e1;
    position: relative;
    box-sizing: border-box; /* ADD THIS LINE */
}
```

## Apply the Patch

```bash
# From /home/user/wpblackwork/
git apply checkout-css-fix.patch
```

Or manually edit `/home/user/wpblackwork/assets/css/bw-checkout.css`:
1. Line 363: Add `table-layout: fixed;`
2. Line 807: Add `box-sizing: border-box;`

## Expected Results
✅ Thumbnails render at exactly 65px width
✅ Dimensions remain stable when coupons are added/removed
✅ No jumping or layout shifts
✅ Spacing between thumbnail and text consistent (10px)

## Files
- **Full audit report:** `CHECKOUT_CSS_AUDIT.md` (detailed analysis with 12 sections)
- **Patch file:** `checkout-css-fix.patch` (ready to apply)
- **This summary:** `CHECKOUT_FIX_SUMMARY.md`

## Test After Applying
1. Desktop: Add/remove coupon → thumbnail stays 65px
2. Mobile: Add/remove coupon → thumbnail stays 65px
3. Measure thumbnail: Should be exactly 65px × 65px (1:1 aspect ratio)
4. Check spacing: 10px gap between thumbnail and product name

---

**Why `table-layout: fixed` works:**

| Property | Behavior |
|----------|----------|
| `table-layout: auto` (default) | Browser calculates column widths from ALL rows; can override specified widths |
| `table-layout: fixed` | Browser uses FIRST ROW only; respects specified widths; faster rendering |

With `fixed`, the browser MUST honor the `width: 65px` constraint and won't recalculate when content changes.

---

**Total changes:** 5 lines added to 1 file
**Risk level:** LOW (isolated changes, no breaking changes)
**Browser compatibility:** All modern browsers (table-layout is CSS 2.1)
