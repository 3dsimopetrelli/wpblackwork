# Fix: Variable Width Broken in Responsive View

**Date**: 2025-12-10
**Widget**: BW Product Slide
**Issue**: Variable Width option broken after arrows/dots update - showing giant full-width slides in responsive view

## Problem Description

After the recent arrows/dots update, the **Variable Width** feature stopped working correctly. Specifically:

- In responsive views, slides appeared as giant full-width images
- The `responsive_variable_width` ON/OFF toggle in breakpoints had no effect
- Variable width was being forced ON for all breakpoints, ignoring user settings

## Root Cause Analysis

### The Conflict

The issue was NOT directly caused by the arrows/dots update, but the arrows update made the problem more visible. The root cause was existing logic in the JavaScript that **forcibly overrode** user settings for `variableWidth`.

### Code Flow

1. **PHP Side** (`class-bw-product-slide-widget.php`):
   - User sets a **Column Width** value (e.g., 500px)
   - PHP sets `$has_custom_column_width = true` (line 465-470)
   - Outputs `data-has-column-width="true"` in HTML (line 492)
   - User also sets `responsive_variable_width = OFF` for mobile breakpoint
   - PHP correctly passes this to `$item_settings['variableWidth'] = false` (line 713-715)

2. **JavaScript Side** (`bw-product-slide.js`, OLD CODE - lines 648-686):
   - Detects `data-has-column-width="true"` → `hasCustomColumnWidth = true`
   - **PROBLEM**: Assumes "custom column width = always use variable width"
   - **Line 675**: Forcibly sets `responsiveEntry.settings.variableWidth = true`
   - **Ignores** the user's explicit `variableWidth = false` setting

### Why This Broke

The original logic assumed:
```
IF user sets custom column width
THEN always enable variableWidth for all breakpoints
```

But this is wrong because:
- User may want fixed-width slides at some breakpoints (e.g., mobile)
- User may want variable-width slides at other breakpoints (e.g., desktop)
- The `responsive_variable_width` toggle exists specifically to give users this control

### The Bad Logic (BEFORE)

```javascript
// OLD CODE - WRONG
if (hasCustomColumnWidth) {
  if (Array.isArray(settings.responsive)) {
    settings.responsive = settings.responsive.map(function (entry) {
      // ... setup code ...

      if (!breakpointCenterMode) {
        // PROBLEM: Always forces variableWidth = true
        responsiveEntry.settings.variableWidth = true;
      }

      return responsiveEntry;
    });
  }
}
```

This **unconditionally** set `variableWidth = true`, overwriting the user's setting from `responsive_variable_width`.

## Solution Implemented

### The Fix

Changed the logic to **respect user's explicit settings** and only use auto-detection as a fallback:

```javascript
// NEW CODE - CORRECT
if (hasCustomColumnWidth) {
  if (Array.isArray(settings.responsive)) {
    settings.responsive = settings.responsive.map(function (entry) {
      // ... setup code ...

      // Respect user's explicit variableWidth setting, only auto-enable if not set
      if (typeof responsiveEntry.settings.variableWidth === 'undefined') {
        // User hasn't explicitly set variableWidth for this breakpoint
        if (!breakpointCenterMode) {
          responsiveEntry.settings.variableWidth = true;
        }
      }
      // If user explicitly set variableWidth, keep their setting

      return responsiveEntry;
    });
  }
}
```

### How It Works Now

1. **Check if user explicitly set variableWidth** for the breakpoint
2. **If NOT set** (undefined): Auto-enable `variableWidth = true` (smart default)
3. **If SET** (true or false): **Keep user's setting** (respect user choice)

### Logic Table

| User Setting | Column Width Set | OLD Behavior | NEW Behavior |
|--------------|------------------|--------------|--------------|
| Not set | Yes | Force `true` | Auto `true` ✓ |
| Not set | No | `false` | `false` ✓ |
| Set to `true` | Yes | Force `true` | Keep `true` ✓ |
| Set to `false` | Yes | Force `true` ❌ | Keep `false` ✓ |
| Set to `false` | No | `false` | Keep `false` ✓ |

The key fix is row 4: When user explicitly disables variable width, we now respect it.

## Files Changed

### JavaScript Changes (`assets/js/bw-product-slide.js`)

**Lines 674-681**: Added conditional check before setting variableWidth

```javascript
// Respect user's explicit variableWidth setting, only auto-enable if not set
if (typeof responsiveEntry.settings.variableWidth === 'undefined') {
  // User hasn't explicitly set variableWidth for this breakpoint
  if (!breakpointCenterMode) {
    responsiveEntry.settings.variableWidth = true;
  }
}
// If user explicitly set variableWidth, keep their setting
```

### PHP Changes

**None needed** - PHP code was already correct. It properly:
- Registers the `responsive_variable_width` control (line 1017-1025)
- Passes the setting to responsive config (line 713-715)
- The problem was entirely in the JavaScript override logic

## How Variable Width Works Now

### Desktop (No Breakpoint Match)
- If column width set: `variableWidth = true` (auto)
- Slides use their custom width
- Slick calculates slide positions dynamically

### Mobile (Breakpoint Match)

**Scenario 1**: User didn't touch `responsive_variable_width` toggle
- Falls back to auto-detection
- If column width set: `variableWidth = true`
- Behavior same as desktop

**Scenario 2**: User set `responsive_variable_width = OFF`
- `variableWidth = false` (user's choice respected)
- Slides use fixed width based on `slidesToShow`
- Normal carousel behavior

**Scenario 3**: User set `responsive_variable_width = ON`
- `variableWidth = true` (user's choice respected)
- Slides use their individual widths
- Variable width carousel

## Testing Checklist

✅ **Variable Width = ON**: Slides display with individual widths
✅ **Variable Width = OFF**: Slides use fixed uniform width
✅ **Responsive breakpoint with Variable Width = OFF**: Fixed width works correctly, no giant slides
✅ **Responsive breakpoint with Variable Width = ON**: Variable width works correctly
✅ **No setting (undefined)**: Auto-detects based on column width (backward compatible)
✅ **Arrows still work**: Arrows toggle correctly in responsive breakpoints
✅ **Dots still work**: Dots toggle correctly in responsive breakpoints
✅ **Show Slide Count still works**: Counter toggles correctly in responsive breakpoints

## Backward Compatibility

✅ **Existing widgets without explicit variableWidth settings**: Continue working with auto-detection
✅ **Existing widgets with variableWidth OFF**: Now actually respected (FIX)
✅ **No breaking changes**: Smart default behavior preserved

## Why This Wasn't Noticed Before

The bug existed before the arrows/dots update, but:
1. Most users didn't explicitly set `responsive_variable_width = OFF`
2. The auto-enabled variable width worked fine for most cases
3. The arrows/dots update made people test responsive settings more thoroughly
4. The bug only manifests when user explicitly disables variable width

## Summary

**What was conflicting**: JavaScript was forcing `variableWidth = true` for all responsive breakpoints when column width was set, ignoring the user's explicit `responsive_variable_width` setting.

**How it was fixed**: Added a conditional check to only auto-enable variable width when the user hasn't explicitly set a value. If user has set it (ON or OFF), their setting is now respected.

**Impact**:
- ✅ Variable Width toggle now works correctly at all breakpoints
- ✅ No giant full-width slides in responsive view
- ✅ Arrows, Dots, and Show Slide Count features remain working
- ✅ Backward compatible with existing widgets
- ✅ Smart defaults preserved for new widgets
