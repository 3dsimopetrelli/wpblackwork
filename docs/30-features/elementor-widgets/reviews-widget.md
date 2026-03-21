# BW Reviews Widget

## Purpose

`bw-reviews` is the premium product-reviews widget for single-product Elementor surfaces.

It is a thin adapter on top of the custom Reviews module and must not own business logic.

## Widget Contract

Slug:

```text
bw-reviews
```

Visible title:

```text
BW Reviews
```

Category:

```text
blackwork
```

Widget file:
- `includes/widgets/class-bw-reviews-widget.php`

Renderer authority:
- `includes/modules/reviews/frontend/class-bw-reviews-widget-renderer.php`

Templates:
- `includes/modules/reviews/frontend/templates/widget.php`
- `includes/modules/reviews/frontend/templates/review-card.php`
- `includes/modules/reviews/frontend/templates/empty-state.php`
- `includes/modules/reviews/frontend/templates/modal.php`

Assets:
- `assets/css/bw-reviews.css`
- `assets/js/bw-reviews.js`

## Editor Controls

### Content
| Control | Type | Default | Note |
|---|---|---|---|
| `product_id` | NUMBER | `0` | Optional product override. If empty, uses current single-product context or Theme Builder preview product. |

## Runtime Behavior

### Product resolution
The renderer resolves the product from:
1. widget override
2. current single-product context
3. Theme Builder preview product fallback

If no product context is available, the widget does not render in frontend and shows an editor notice only in Elementor edit mode.

### Source of truth
The widget always reads from the Reviews module.

It does not query WooCommerce native review comments.

### Rendering model
- initial widget shell is rendered server-side
- card HTML for sort/load-more is rendered server-side
- JS handles:
  - modal state
  - sort dropdown
  - breakdown accordion
  - load more
  - edit flow

## Frontend UX

### Summary shell
- stars
- review count
- breakdown toggle
- write-review CTA
- sort control

### Empty product mode
If product reviews are absent:
- normal mode: widget can show zero-state behavior
- optional global mode: if Reviews Settings enable global fallback, the widget shows global approved reviews instead of an empty product state

### Card layout
Normal product mode:
- reviewer
- optional verified badge
- date
- rating
- review body

Global fallback mode:
- adds reviewed product thumbnail
- adds reviewed product name

### Modal
Single reusable modal appended to the page/body runtime layer.

Supports:
- create review
- edit owned review
- confirmation notice state

## AJAX Contract

Read/list:
- `bw_reviews_load_reviews`

Write/update:
- `bw_reviews_submit`
- `bw_reviews_get_edit_review`
- `bw_reviews_update_review`

All runtime decisions stay delegated to Reviews services.

## Design Notes

Current V1 implementation includes:
- premium dark card layout
- animated breakdown accordion
- show-more reveal animation
- premium modal shell
- owner edit path

The widget intentionally avoids:
- Woo default templates
- JS-built review markup
- duplicated validation/business rules outside module services
