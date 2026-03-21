# Blackwork Reviews System Guide

## Purpose

The Blackwork Reviews system is a custom product-review domain for WooCommerce products.

It was designed to:
- avoid WooCommerce comment/template coupling
- keep review data and moderation isolated
- support a premium custom frontend widget
- preserve WooCommerce as the authority for product/order/customer truth

## Runtime Authority

Primary module root:

```text
includes/modules/reviews/
```

Core files:
- `reviews-module.php`
- `data/class-bw-reviews-installer.php`
- `data/class-bw-reviews-repository.php`
- `services/class-bw-reviews-settings.php`
- `services/class-bw-review-submission-service.php`
- `services/class-bw-review-confirmation-service.php`
- `services/class-bw-review-moderation-service.php`
- `services/class-bw-review-email-service.php`
- `services/class-bw-review-brevo-sync-service.php`
- `services/class-bw-review-verified-purchase-service.php`
- `runtime/class-bw-reviews-runtime.php`
- `frontend/class-bw-reviews-widget-renderer.php`

Elementor adapter:
- `includes/widgets/class-bw-reviews-widget.php`

Assets:
- `assets/css/bw-reviews.css`
- `assets/js/bw-reviews.js`

## Data Model

### Storage strategy
- custom table: `{$wpdb->prefix}bw_reviews`
- no CPT
- no Woo comments authority

### Canonical statuses
- `pending_confirmation`
- `pending_moderation`
- `approved`
- `rejected`
- `trash`

### Reviewer identity model
- `reviewer_first_name`
- `reviewer_last_name`
- `reviewer_display_name`
- `reviewer_email`
- `reviewer_email_hash`

### Verified purchase rule
Verified purchase means:
- logged-in account
- real WooCommerce order for the reviewed product
- matched through WooCommerce/customer authority

Guest reviews are allowed only if settings allow them, but they are **not** marked as verified purchases.

### Uniqueness rule
One review identity per product is enforced by:
- `product_id`
- `reviewer_email_hash`

This keeps one canonical review per product/customer identity.

## Settings Authority

Primary settings class:
- `includes/modules/reviews/services/class-bw-reviews-settings.php`

Option keys:
- `bw_reviews_general_settings`
- `bw_reviews_display_settings`
- `bw_reviews_submission_settings`
- `bw_reviews_moderation_settings`
- `bw_reviews_email_settings`
- `bw_reviews_brevo_settings`
- `bw_reviews_schema_version`

### Display settings
Implemented display controls include:
- initial visible reviews
- reviews per show more
- show rating breakdown
- show dates
- show verified badge
- use global reviews when empty

### Global reviews fallback
When `Use global reviews when empty` is enabled:
- if the current product has approved reviews, the widget behaves normally
- if the current product has no approved reviews, the widget shows:
  - global review summary
  - global approved review list
  - reviewed product thumbnail + product name inside each card

This product-context footer appears only in global fallback mode, not in normal product mode.

## Admin Surfaces

### Registered pages
- `Blackwork Site -> Reviews`
- `Blackwork Site -> Reviews Settings`
- hidden page: `Edit Review`

Slugs:
- `bw-reviews`
- `bw-reviews-settings`
- `bw-reviews-edit`

Capability:
- `manage_options`

### Reviews list screen
Authority:
- `includes/modules/reviews/admin/class-bw-reviews-admin.php`
- `includes/modules/reviews/admin/class-bw-reviews-list-table.php`

Current admin list behavior includes:
- stats cards above the list:
  - Total
  - Approved
  - Rejected
  - Trash
- WP-native list table structure
- product / rating / verified / featured filters
- search box
- status toggle pills in-table
- featured pin icon
- compact verified state indicator

### Status behavior in admin
- `Approved`: review is visible on frontend
- `Rejected`: review is kept in admin/database but hidden from frontend
- `Trash`: review is moved to trash and available in trash views/restore flow

Reject is **not** the same as trash.

## Frontend Widget Contract

### Widget
Slug:

```text
bw-reviews
```

Visible title:

```text
BW Reviews
```

Widget controls:
- `Product ID Override`

The widget stays intentionally thin. Business rules remain inside the Reviews module.

### Rendering strategy
- initial render: server-side
- sorting / load more: server-side HTML via AJAX
- modal + interaction state: JS-enhanced
- PHP remains markup authority

### Widget UI contract
- summary row with stars and review count
- breakdown accordion
- sort dropdown
- write-review CTA
- premium multi-step modal
- show-more pagination
- optional global fallback when the current product has no approved reviews

### Review card contract
Normal product mode:
- reviewer name
- optional verified badge
- date
- rating
- review content

Global fallback mode adds:
- reviewed product thumbnail
- reviewed product name

## Submission / Edit / Confirmation Runtime

### AJAX endpoints
Registered in `class-bw-reviews-runtime.php`:
- `bw_reviews_load_reviews`
- `bw_reviews_submit`
- `bw_reviews_get_edit_review`
- `bw_reviews_update_review`

### Confirmation route
Confirmation is handled through `template_redirect` with query args on the product page.

Frontend result states currently support:
- confirmed and approved
- confirmed and pending moderation

### Modal flow
Single reusable modal with steps:
1. rating
2. content
3. identity
4. done

Identity step rules:
- logged-in users: readonly identity summary
- guests: first name / last name / email
- privacy checkbox required

## Brevo Integration

Reviews do not duplicate Brevo credentials.

Global credentials are reused from Mail Marketing authority, while Reviews owns only review-specific sync behavior:
- enable review sync
- sync on submission
- sync on confirmation
- sync on approval
- review list id
- confirmed review list id
- rewarded review list id

Shared list loading was extracted into:
- `includes/integrations/brevo/class-bw-brevo-lists-service.php`

## WooCommerce Coexistence Rule

When the Reviews module is enabled, native Woo product reviews/comments are disabled through runtime filters:
- `woocommerce_enable_reviews`
- `comments_open`
- `woocommerce_product_tabs`

This prevents dual review authorities on the storefront.

## Operational Notes

### Show More behavior
- visibility count is controlled by Display settings
- appended reviews are loaded by AJAX
- the button disappears once `hasMore = false`

### Verified badge visibility
The frontend badge appears only when:
- display setting `show_verified_badge` is enabled
- the saved review row is truly verified through WooCommerce order ownership

### Current V1 exclusions
- no review image upload
- no guest edit links
- no reward automation UX
- no Woo native review migration/import flow
