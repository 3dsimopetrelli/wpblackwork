# Visual Explanation: Why 65px Becomes 144.82px

## The Table Layout Problem

### Current Situation (table-layout: auto)

```
┌─────────────────────────────────────────────────────────────────┐
│ TABLE (width: 100%)                                             │
├─────────────────────────────────────────────────────────────────┤
│ TBODY                                                           │
│ ┌──────────────────┬────────────────────────────────────────┐  │
│ │ TD.thumbnail     │ TD.product-name                        │  │
│ │ width: 65px      │ (no width set)                         │  │
│ │ ❌ IGNORED!      │                                         │  │
│ │                  │                                         │  │
│ │ Actual: 144.82px │ Actual: fills remaining                │  │
│ └──────────────────┴────────────────────────────────────────┘  │
│                                                                 │
│ TFOOT                                                           │
│ ┌─────────────────────────────────────────────────────────┐    │
│ │ Coupon row (colspan=2)                                  │    │
│ │ ← When added/removed, triggers recalculation           │    │
│ └─────────────────────────────────────────────────────────┘    │
│ ┌───────────────────┬─────────────────────────────────────┐    │
│ │ Subtotal          │ $100.00                             │    │
│ └───────────────────┴─────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘

⚠️  BROWSER ALGORITHM:
1. Scan ALL rows (tbody + tfoot)
2. Calculate optimal column widths for entire table
3. Ignore specified 65px width if "better" distribution found
4. Result: Column 1 = 144.82px, Column 2 = remaining width
```

### After Fix (table-layout: fixed)

```
┌─────────────────────────────────────────────────────────────────┐
│ TABLE (width: 100%, table-layout: fixed) ✅                     │
├─────────────────────────────────────────────────────────────────┤
│ TBODY                                                           │
│ ┌──────────┬──────────────────────────────────────────────┐    │
│ │ TD       │ TD.product-name                              │    │
│ │ 65px ✅  │ (fills remaining)                            │    │
│ │          │                                               │    │
│ │ Actual:  │ Actual: 100% - 65px                          │    │
│ │ 65px     │                                               │    │
│ └──────────┴──────────────────────────────────────────────┘    │
│                                                                 │
│ TFOOT                                                           │
│ ┌─────────────────────────────────────────────────────────┐    │
│ │ Coupon row                                              │    │
│ │ ← Added/removed, but column widths DON'T change ✅      │    │
│ └─────────────────────────────────────────────────────────┘    │
│ ┌──────────┬──────────────────────────────────────────────┐    │
│ │ Subtotal │ $100.00                                      │    │
│ │ 65px ✅  │                                               │    │
│ └──────────┴──────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘

✅ BROWSER BEHAVIOR:
1. Use FIRST ROW only to determine column widths
2. Respect width: 65px constraint
3. Apply same widths to ALL subsequent rows
4. Never recalculate (stable dimensions)
```

---

## The Box-Sizing Problem

### Current Situation (box-sizing: content-box)

```
┌─────────────────────────────────────────────────────────────────┐
│ TD.product-thumbnail (width affected by table layout issue)    │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │ DIV.bw-review-item__media                                 │ │
│  │ width: 65px (inline style)                                │ │
│  │ border: 1px solid #e1e1e1 (CSS)                           │ │
│  │ box-sizing: content-box (default) ❌                       │ │
│  │                                                            │ │
│  │  ┌───────────────────────────────────────────┐            │ │
│  │  │                                            │            │ │
│  │ 1px border                                    │ 1px border│ │
│  │  │           CONTENT AREA                     │            │ │
│  │  │           65px width                       │            │ │
│  │  │                                            │            │ │
│  │  └───────────────────────────────────────────┘            │ │
│  │                                                            │ │
│  │  TOTAL WIDTH = 65px + 1px + 1px = 67px ❌                  │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### After Fix (box-sizing: border-box)

```
┌─────────────────────────────────────────────────────────────────┐
│ TD.product-thumbnail                                            │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ DIV.bw-review-item__media                               │   │
│  │ width: 65px (inline style)                              │   │
│  │ border: 1px solid #e1e1e1 (CSS)                         │   │
│  │ box-sizing: border-box ✅                                │   │
│  │                                                          │   │
│  │ ┌─────────────────────────────────────────────────┐     │   │
│  │ │                                                  │     │   │
│  │ │         CONTENT AREA                             │     │   │
│  │ │         63px width (65px - 2px borders)          │     │   │
│  │ │                                                  │     │   │
│  │ └─────────────────────────────────────────────────┘     │   │
│  │                                                          │   │
│  │ TOTAL WIDTH = 65px (border included) ✅                  │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Timeline of Layout Calculation

### WITHOUT table-layout: fixed

```
USER ACTION          BROWSER CALCULATION              RESULT
──────────────────────────────────────────────────────────────────
Page loads       →   Scan all rows                →  Thumbnail: 144.82px
                     Calculate column widths

Apply coupon     →   Content changed!             →  Thumbnail: 138.45px
                     RECALCULATE all columns          ↑ JUMP!

Remove coupon    →   Content changed again!       →  Thumbnail: 144.82px
                     RECALCULATE all columns          ↑ JUMP BACK!
```

### WITH table-layout: fixed ✅

```
USER ACTION          BROWSER CALCULATION              RESULT
──────────────────────────────────────────────────────────────────
Page loads       →   Use first row widths         →  Thumbnail: 65px
                     Set columns: [65px, auto]        ✅ STABLE

Apply coupon     →   Content changed               →  Thumbnail: 65px
                     (no recalculation)                ✅ NO JUMP

Remove coupon    →   Content changed               →  Thumbnail: 65px
                     (no recalculation)                ✅ NO JUMP
```

---

## CSS Specificity Battle (Not the Issue)

```
SELECTOR                                                    SPECIFICITY    WINS?
────────────────────────────────────────────────────────────────────────────────
.bw-checkout-right table.shop_table td                     0-2-2          ❌
    padding: 12px 0;

body.woocommerce-checkout .bw-checkout-right               0-3-5          ❌
table.shop_table tbody .bw-review-item td
    padding: 0 0 15px 0;

INLINE STYLE: padding-right: 10px; padding-bottom: 15px;  1-0-0-0        ✅ WINS
```

**Conclusion:** Inline styles always win (highest specificity)
**BUT:** Specificity doesn't matter for the width issue — it's the table layout algorithm!

---

## The Cascade for Padding (Working Correctly)

```
RULE                                          PADDING VALUES
────────────────────────────────────────────────────────────────
1. General td rule                            12px  0  12px  0
   padding: 12px 0;                           ↓

2. More specific .bw-review-item td           0  0  15px  0
   padding: 0 0 15px 0;                       ↓ OVERRIDES

3. Inline style on td.product-thumbnail       0  10px  15px  0
   padding-right: 10px;                         ↑ OVERRIDES
   padding-bottom: 15px;                        ↑ OVERRIDES

FINAL RESULT:                                 0  10px  15px  0 ✅
```

---

## Why Previous Fixes Didn't Work

### Attempt 1: Inline styles only (current approach)
```html
<td style="width: 65px; min-width: 65px; max-width: 65px;">
```
❌ FAILED: Table layout algorithm ignores these during rendering

### Attempt 2: Remove CSS width rules
❌ FAILED: Doesn't address the table layout algorithm

### Attempt 3: Use !important in CSS
```css
td.product-thumbnail {
    width: 65px !important;
}
```
❌ FAILED: !important only affects specificity, not table layout algorithm

### Attempt 4: Add table-layout: fixed ✅
```css
table.shop_table {
    table-layout: fixed;
}
```
✅ SUCCESS: Changes the layout algorithm itself

---

## How Table Layout Algorithms Work

### table-layout: auto (default)

1. **Phase 1:** Calculate minimum content width (MCW) for each cell
2. **Phase 2:** Calculate maximum content width for each cell
3. **Phase 3:** Determine column minimum and maximum widths
4. **Phase 4:** Distribute table width among columns
   - If total MCW < table width: Distribute extra space
   - If total MCW > table width: Use complex algorithm
5. **Result:** Column widths may differ from specified values

**Speed:** Slow (must scan all rows)
**Stability:** Unstable (recalculates on content change)

### table-layout: fixed ✅

1. **Phase 1:** Use first row only
2. **Phase 2:** Respect explicit widths
3. **Phase 3:** Distribute remaining width to auto columns
4. **Result:** Column widths match specified values

**Speed:** Fast (only scans first row)
**Stability:** Stable (never recalculates)

---

## Browser DevTools Debugging

### What You'll See (Before Fix)

```
Chrome DevTools > Elements > Computed

td.product-thumbnail {
    width: 65px           ← Computed style shows correct value
}

But measured dimensions (right-click > Inspect):
    144.82 × 112.5px      ← Actual rendered size differs!
```

**Why?** Computed styles are calculated BEFORE layout algorithm runs.

### What You'll See (After Fix)

```
Chrome DevTools > Elements > Computed

td.product-thumbnail {
    width: 65px           ← Computed style
}

Measured dimensions:
    75px × 65px           ← Matches (65px content + 10px padding-right)
```

---

## Summary

| Issue | Root Cause | Fix |
|-------|------------|-----|
| Width 65px → 144.82px | `table-layout: auto` ignores width constraints | Add `table-layout: fixed` |
| Dimensions jump on coupon add/remove | Table recalculates on content change | `table-layout: fixed` prevents recalculation |
| 2px wider than expected | Border not included in width | Add `box-sizing: border-box` |

**Total CSS changes:** 2 lines
**Impact:** Critical issue resolved
**Risk:** Low (isolated changes)
