# My Account Complete Guide

Last update: 2026-02-19  
Scope: custom My Account area in `wpblackwork` (WooCommerce + Supabase integrations)

## 1. Source of truth and non-break rule

- For Supabase auth/create-password logic, **always read first**:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/SUPABASE_CREATE_PASSWORD.md`
- Any change touching auth/onboarding/email callbacks must preserve that flow.

## 2. Main architecture

### Core logic (PHP)
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-my-account.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/includes/woocommerce-overrides/class-bw-supabase-auth.php`

### Templates (My Account)
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/dashboard.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/downloads.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/orders.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-edit-account.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/view-order.php`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-login.php`

### Frontend behavior
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-my-account.js`
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-my-account.css`
- Password modal (onboarding):  
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/js/bw-password-modal.js`
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/assets/css/bw-password-modal.css`

## 3. Navigation and endpoints

### Active sidebar endpoints
- `dashboard`
- `downloads`
- `orders` (label customized as `My purchases`)
- `edit-account` (label customized as `settings`)
- `customer-logout` (label customized as `logout`)

Configured via:
- `bw_mew_filter_account_menu_items()` in `class-bw-my-account.php`

## 4. Dashboard current behavior

File:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/dashboard.php`

### Hero area
- Welcome card + support card.
- Name logic:
  - first/last from profile meta,
  - fallback from billing meta,
  - fallback from latest order billing data.
- Email always shown.
- Support text from option:
  - `bw_myaccount_black_box_text`
- Support link from option:
  - `bw_myaccount_support_link`

### Orders blocks
- `Your digital orders` and `Physical orders` rendered as custom list rows.
- Product title/image link opens in new tab.
- Variation/license shown in digital meta.
- **Prices removed** from dashboard digital + physical rows (by request).

## 5. Downloads page current behavior

File:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/downloads.php`

- Uses same modern list style as dashboard digital rows.
- Shows image, title link, variation/license, date, download button.
- Price hidden on downloads page (by request).

## 6. My purchases page current behavior

File:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/orders.php`

### Table columns
- Product (image + title link)
- Order (clickable, black, bold)
- Date
- Price
- Coupon
- Bill (`View`)

### Header summary (top-right)
- Shows:
  - `X digital products`
  - `Y physical products`
- Data source:
  - `bw_mew_get_customer_product_type_counts( $user_id )`
- Logic:
  - counts unique `product_id:variation_id`
  - separates digital vs physical with `WC_Product::is_downloadable()`
  - excludes duplicated repeated purchases of same product+variation

### Pagination UX
- Previous/Next styled as centered arrow buttons.
- Page indicator `current / total`.

## 7. Settings tabs (Profile / Billing / Shipping / Security)

Template:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/form-edit-account.php`

Handler:
- `bw_mew_handle_profile_update()` in `class-bw-my-account.php`

### Profile
- Fields: first name, last name, display name.
- Spacing adjusted (`row-gap`) for visual consistency.

### Billing details
- Fields built from WooCommerce `WC_Countries::get_address_fields()`.
- If billing/shipping user meta missing, auto-sync from latest order:
  - `bw_mew_sync_account_address_fields_from_latest_order()`
- This prevents empty billing form after guest-first order/account-link flows.

### Shipping details
- Checkbox:
  - `Shipping address is the same as billing`
- Behavior:
  - checked -> shipping fields collapsed/hidden
  - unchecked -> shipping fields shown
- Animated open/close:
  - fade + slide (soft transition)
- Save behavior:
  - if checked, shipping values copied from billing.

### Security
- Change password:
  - new password + confirm
  - validation rules UI
  - submit enabled only when valid and matching
  - missing session warning block
- Change email:
  - current email readonly
  - new + confirm fields
  - success/error notice boxes with icon
  - pending email banner
  - redirect/callback confirmation popup (email changed)
- Password fields now include show/hide eye icon (minimal reset style to avoid Elementor button skin bleed).

## 8. Supabase email change flow (current)

### Redirect behavior
- Email update sends user via Supabase link back to My Account.
- Query/callback markers handled in JS:
  - `bw_email_confirmed=1`
  - `bw_email_changed=1`
  - hash/query `type=email_change`
- Confirmation popup shown on callback.

### Current email update expectation
- Current readonly email updates only after Supabase confirmation is complete.
- If session is missing, boxed error appears.

## 9. View order page (single order)

Custom template:
- `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/woocommerce/templates/myaccount/view-order.php`

### Current customizations
- Minimal top header status line.
- PDF button in top actions using WPO/WCPDF shortcode if available:
  - `[wcpdf_download_pdf order_ids="ID" template_type="invoice" display="download"]`
- Styled order downloads + order details cards (same visual language as My Account).
- `Order again` removed.
- Duplicated `Actions: Invoice` row in table footer hidden (top button is the single source).
- Billing address block restyled, inner default border removed.

## 10. PDF invoice plugin integration

Expected plugin:
- WooCommerce PDF Invoices & Packing Slips (WPO/WCPDF)

In current local workspace:
- Plugin folder not found under local `/wp-content/plugins`.
- Integration in template is conditional (`shortcode_exists`), so no fatal if missing.

If plugin is active in production:
- Top invoice/download button appears automatically in view-order.

## 11. Floating labels (My Account-wide)

### Implemented details
- Floating labels for text/email/password/select fields in settings.
- Label chips now use rounded shape (`border-radius: 5px`).
- Shipping first/last name clipping fixed by allowing visible overflow when expanded.

Files:
- JS: `assets/js/bw-my-account.js`
- CSS: `assets/css/bw-my-account.css`

## 12. Known constraints / known noise

- `composer run lint:main` currently fails on pre-existing file:
  - `/Users/simonezanon/Documents/local site/BlackWork/wp-content/plugins/wpblackwork/bw-main-elementor-widgets.php`
- This is unrelated to My Account customizations but appears in mandatory lint command output.

## 13. Debug guide (quick)

### A. Billing fields empty
1. Confirm user has at least one order linked to their user id.
2. Open My Account page once (sync runs on `template_redirect`).
3. Check user meta for `billing_*` and `shipping_*`.
4. If still empty, inspect latest order billing/shipping data.

### B. Shipping fields not collapsing
1. Check checkbox id `#bw_shipping_same_as_billing`.
2. Check container `[data-bw-shipping-fields]`.
3. Verify `is-collapsed` class toggles in DevTools.
4. Confirm no custom CSS override forcing `display`.

### C. Email-change popup not appearing
1. Verify callback URL contains one of:
   - `bw_email_confirmed=1`, `bw_email_changed=1`, or `type=email_change`.
2. Check `sessionStorage` key:
   - `bw_email_change_confirmed`
3. Confirm `bw-my-account.js` loaded and no JS errors.

### D. Invoice button not visible
1. Confirm PDF plugin active.
2. Confirm shortcode exists: `wcpdf_download_pdf`.
3. Confirm order has invoice/doc available according to plugin rules.

## 14. Safe change protocol (recommended)

Before editing:
1. Re-read `SUPABASE_CREATE_PASSWORD.md`.
2. Identify if change touches auth/onboarding/callback or just layout.
3. Keep security flow isolated from visual-only edits.

After PHP edits (mandatory):
1. `php -l <every modified php file>`
2. `composer run lint:main`
3. If lint fails, report whether failure is pre-existing/unrelated.

Visual regression checklist:
1. Desktop + mobile: dashboard / downloads / my purchases / settings / view-order.
2. Shipping checkbox on/off transition.
3. Security password show/hide eye.
4. Email change notice + callback popup.
5. View-order top PDF button and absence of duplicate actions row.

## 15. Open extension points

Possible next improvements:
1. Add product thumbnail in `view-order` line items (currently table text only).
2. Add conditional invoice states (`Available`, `Pending`, `Missing`) in header actions.
3. Add My Account diagnostics panel (admin-only) for quick sync/session debug.
4. Unify My Account button tokens into CSS variables for faster theming changes.
