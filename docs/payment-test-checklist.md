# Payment Section Test Checklist

## Pre-Test Setup

- [ ] Clear browser cache (hard refresh: `Ctrl+Shift+R`)
- [ ] Disable any CSS/JS minification plugins temporarily
- [ ] Verify `bw-payment-methods.css` version has updated (check DevTools Network tab)
- [ ] Test on checkout page with at least 2-3 payment methods enabled

---

## Visual Testing

### Section Title & Subtitle

- [ ] **Title "Payment"** appears with:
  - Font size: 28px (24px on mobile)
  - Font weight: Bold (700)
  - Color: Black (#000000)
  - Margin bottom: 8px

- [ ] **Subtitle** appears with:
  - Text: "All transactions are secure and encrypted."
  - Font size: 15px (14px on mobile)
  - Color: Gray (#6b7280)
  - Margin bottom: 20px (16px on mobile)

### Payment Method Cards

- [ ] Each payment method appears as a rounded card (8px border-radius)
- [ ] Cards have 1px gray border (#d1d5db)
- [ ] 8px gap between cards
- [ ] White background

### Radio Buttons

- [ ] Custom styled radio buttons (20x20px circles)
- [ ] Gray border when unselected (#9ca3af)
- [ ] Black background when selected (#000000)
- [ ] White dot (6x6px) appears inside when selected

### Payment Icons

- [ ] Card brand icons display (Visa, Mastercard, Maestro, etc.)
- [ ] Icons height: 28px (20px on mobile)
- [ ] Max 3 icons visible
- [ ] "+N" badge appears when more than 3 icons exist
- [ ] Badge styling:
  - Min-width: 40px
  - Height: 32px
  - White background
  - Gray border
  - Font size: 13px

### Accordion Behavior

- [ ] First payment method opens by default
- [ ] Clicking a payment method radio:
  - Closes other methods
  - Opens selected method
  - Smooth animation (0.5s)
- [ ] Content fades in when opening (opacity + translateY animation)

---

## State Testing

### Hover States

- [ ] **Payment method card hover**: Border changes to #9ca3af
- [ ] **Radio button hover**: Border changes to #9ca3af
- [ ] **"+N" badge hover**: Background changes to #f9fafb, border to #d1d5db
- [ ] **Input field hover**: Border changes to #9ca3af
- [ ] **Place order button hover**: Background darkens, slight lift effect

### Focus States

- [ ] **Radio button focus**: Blue ring shadow appears (`0 0 0 3px rgba(59, 130, 246, 0.1)`)
- [ ] **Input field focus**: Blue border (#3b82f6) + blue ring shadow
- [ ] **Stripe Elements focus**: Blue border + ring shadow

### Selected State

- [ ] Selected payment method:
  - **Border**: 2px solid black (#000000)
  - **Shadow**: `0 1px 3px 0 rgba(0, 0, 0, 0.15)`
  - **Header background**: Black (#000000)
  - **Title text**: White (#ffffff)
  - **Radio button**: Black background with white dot

### Active/Pressed States

- [ ] **Place order button active (click)**: Background darkens to #1d4ed8, no lift

### Error States

- [ ] **Invalid input field**:
  - Border: #fecaca
  - Background: #fef2f2
- [ ] **Error message** displays with:
  - Background: #fef2f2
  - Border: 1px solid #fecaca
  - Text color: #991b1b

### Loading State

- [ ] **Place order button processing**:
  - Opacity: 0.7
  - Pointer events: none
  - Spinning loader appears on right side

---

## Input Fields Testing

### Standard Inputs

- [ ] Input fields have:
  - Padding: 16px 18px
  - Border: 1px solid #d1d5db
  - Border radius: 8px
  - Font size: 15px (16px on mobile to prevent zoom)
  - Box shadow: `0 1px 2px 0 rgba(0, 0, 0, 0.05)`

- [ ] Placeholder text color: #9ca3af
- [ ] Text color when typing: #1f2937

### Two-Column Layout (Desktop)

- [ ] **Expiration date** and **Security code** fields:
  - Side by side on desktop (grid 1fr 1fr)
  - 12px gap between them
  - Stack vertically on mobile (<768px)

### Field Icons

- [ ] **Lock icon** appears in card number field (if implemented)
- [ ] **Question mark icon** appears in security code field (if implemented)
- [ ] Icons positioned:
  - Right: 18px
  - Top: 50% (vertically centered)
  - Color: #6b7280

### Checkbox Styling

- [ ] Custom checkbox (20x20px)
- [ ] Border: 2px solid #d1d5db
- [ ] Border radius: 4px
- [ ] When checked:
  - Background: Black (#000000)
  - Border: Black (#000000)
  - White checkmark appears

---

## Stripe Elements Testing

(If using Stripe payment gateway)

- [ ] Stripe Elements container has:
  - Padding: 16px 18px
  - Border: 1px solid #d1d5db
  - Border radius: 8px
  - Box shadow: `0 1px 2px 0 rgba(0, 0, 0, 0.05)`

- [ ] Hover state: Border changes to #9ca3af
- [ ] Focus state: Blue border (#3b82f6) + blue ring shadow

---

## Tooltip Testing

### "+N" Badge Tooltip

- [ ] Hover over "+N" badge shows black tooltip above it
- [ ] Tooltip styling:
  - Background: Black (#000000)
  - Border: 1px solid black
  - Border radius: 8px
  - Padding: 10px 12px
  - Shadow: `0 10px 15px -3px rgba(0, 0, 0, 0.3)`

- [ ] Arrow pointing down appears (12x12px rotated square)
- [ ] Arrow positioned: Bottom -6px, Right 14px
- [ ] Remaining card icons display inside tooltip
- [ ] Tooltip disappears when mouse leaves

- [ ] Fade-in animation: opacity 0→1, translateY -4px→0

---

## Responsive Testing

### Mobile (<768px)

- [ ] **Title** font size: 24px (reduced from 28px)
- [ ] **Subtitle** font size: 14px (reduced from 15px)
- [ ] **Input fields**: Font size 16px (prevents iOS auto-zoom)
- [ ] **Two-column layout**: Switches to single column
- [ ] **Payment icons**: Height 20px (reduced from 28px)
- [ ] **"+N" badge**: Min-width 28px, height 20px, font 11px
- [ ] **Place order button**: Padding 14px 20px, font 15px

### Tablet (768px - 1024px)

- [ ] All desktop styles apply
- [ ] No layout shifts or overflow

---

## Accessibility Testing

### Keyboard Navigation

- [ ] **Tab key** navigates through:
  1. Radio buttons
  2. Input fields
  3. Checkboxes
  4. Place order button

- [ ] **Enter/Space** on radio button label selects payment method
- [ ] **Arrow Up/Down** navigates between payment methods
- [ ] **Focus visible** on all interactive elements

### Screen Reader

- [ ] Radio buttons have accessible labels
- [ ] Error messages are announced
- [ ] Success indicators are announced
- [ ] Loading state is announced

### Focus Indicators

- [ ] **Radio button focus**: Blue ring shadow visible
- [ ] **Input focus**: Blue border + ring shadow visible
- [ ] **Header focus-within**: 2px blue outline with 2px offset

---

## Browser Compatibility

Test in:

- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest)
- [ ] **Edge** (latest)
- [ ] **Mobile Safari** (iOS)
- [ ] **Mobile Chrome** (Android)

### CSS Feature Support

- [ ] **CSS Custom Properties** (variables) work
- [ ] **:has() selector** works (or fallback `.is-selected` class applied)
- [ ] **Grid layout** works for two-column inputs
- [ ] **Flexbox** works for icon layout
- [ ] **Transitions** work smoothly
- [ ] **Custom appearance: none** works for radio/checkbox

---

## Integration Testing

### WooCommerce Integration

- [ ] **Payment gateway fields** render correctly inside `.bw-payment-method__fields`
- [ ] **Description text** appears in `.bw-payment-method__description`
- [ ] **Selected indicator** shows for no-fields methods (e.g., Cash on Delivery)
- [ ] **Place order button text** updates based on selected gateway

### Plugin Conflicts

- [ ] Test with **Elementor** active (ensure no style conflicts)
- [ ] Test with **caching plugin** active (ensure CSS loads correctly)
- [ ] Test with **minification** enabled (ensure CSS/JS works)

### AJAX Updates

- [ ] **Checkout update** (coupon apply) re-initializes JavaScript
- [ ] **Payment method change** triggers WooCommerce `payment_method_selected` event
- [ ] **Error handling** removes loading state on checkout error

---

## Performance Testing

- [ ] **CSS file size**: Check if reasonable (<50KB)
- [ ] **Load time**: CSS loads within 1 second
- [ ] **Animation performance**: No jank during accordion open/close
- [ ] **Repaints**: Minimal repaints when hovering/focusing

---

## Regression Testing

### Critical User Flows

- [ ] **Complete checkout** with credit card
- [ ] **Complete checkout** with PayPal
- [ ] **Complete checkout** with other gateways
- [ ] **Apply coupon** and verify payment section updates
- [ ] **Trigger validation error** and verify styling
- [ ] **Change payment method** multiple times

---

## Post-Deployment Checklist

- [ ] Monitor error logs for JavaScript errors
- [ ] Check analytics for checkout abandonment rate changes
- [ ] Verify no increase in support tickets related to payment
- [ ] Collect user feedback on new design

---

## Known Issues / Notes

**Document any issues found during testing:**

1. _Issue:_
   - _Browser:_
   - _Steps to reproduce:_
   - _Expected:_
   - _Actual:_
   - _Fix:_

---

## Sign-Off

**Tested by:** ________________
**Date:** ________________
**Environment:** (Production / Staging / Local)
**Result:** ☐ Pass ☐ Fail
**Notes:**
