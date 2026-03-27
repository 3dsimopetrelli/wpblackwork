# BW-SP Product Details

## Purpose

`BW-SP Product Details` is the single-product details widget for WooCommerce
surfaces. It renders either:
- the canonical product-details table
- a compatibility block
- an editorial info box

The widget reuses a single visual language:
- boxed wrapper
- optional accordion shell
- table-like row layout

## Runtime Files

| File | Responsibility |
|------|----------------|
| `includes/widgets/class-bw-product-details-widget.php` | Elementor controls, render branching, product resolution |
| `assets/css/bw-product-details.css` | widget visual contract, row layout, accordion first-paint protection |
| `assets/js/bw-product-details.js` | accordion activation and measured-height animation |
| `metabox/bibliographic-details-metabox.php` | product-edit metabox authority for details and compatibility data |

## Content Type surface

The `Content Type` control currently supports:
- `Product Details`
- `Compatibility`
- `Info Box`

### Product Details

Reads product-level meta from the existing Product Details metabox families:
- digital fields: `_digital_*`
- books fields: `_bw_biblio_*`
- prints fields: `_print_*`

Special render branches:
- `_digital_total_assets` + `_digital_assets_list` become the assets hero row
- `_digital_formats` renders as pills

### Compatibility

Reads product-level compatibility selections from the same Product Details metabox authority.

Frontend labels:
- `Adobe Illustrator, Photoshop`
- `Figma, Sketch, Adobe XD`
- `Affinity Designer & Photo`
- `CorelDRAW, Inkscape`
- `Canva, PowerPoint`
- `Cricut, Silhouette`
- `Blender, Cinema 4D`

Default behavior:
- if a product has never saved compatibility settings, all rows are treated as enabled
- once compatibility settings are saved, only checked rows render
- if everything is explicitly unchecked, the widget renders no compatibility block

Implementation note:
- `_bw_compatibility_configured` distinguishes untouched products from explicitly-saved empty selections

### Info Box

Uses widget-local title + WYSIWYG content and does not depend on product meta.

## Product edit authority

The WooCommerce product editor authority for Product Details and Compatibility is:
- `metabox/bibliographic-details-metabox.php`

That file owns:
- field registration helper arrays
- metabox render UI
- save flow
- compatibility default/fallback helpers used by the widget

Compatibility is implemented as checkbox fields inside the existing `Product Details` metabox, not as a second metabox system.
