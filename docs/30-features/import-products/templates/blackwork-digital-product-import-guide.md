# Blackwork Digital Product Import Guide

## Purpose
Use this guide together with the Digital CSV template to help ChatGPT/Codex prepare a clean importable CSV for Blackwork digital products from product images, source notes, and catalog metadata.

## Files to use
- `blackwork-digital-product-import-template.csv`
- this guide: `blackwork-digital-product-import-guide.md`

## How to give the files to ChatGPT/Codex
Provide:
- the CSV template
- this Markdown guide
- product images
- source notes, archive notes, or listing notes

Recommended prompt framing:
- fill the CSV using the exact column names
- keep the CSV importable
- do not add comments or extra rows
- do not invent facts from the images

## Required fields
- `row_type`
- `sku`
- `post_title`
- `post_status`
- `product_type`
- `categories`
- `featured_image`

For future variation rows:
- `row_type`
- `parent_sku`
- `sku`
- `variation_name`
- `variation_attributes_json`

## Optional fields
- `post_name`
- `sale_price`
- `tags`
- `post_content`
- `post_excerpt`
- `product_gallery`
- Blackwork digital meta fields
- hover media fields
- future variation download fields

## Digital defaults
- Default category: `Digital Collections`
- `product_type` should be `variable`
- `default_variation` is singular and normally `commercial`
- Standard example variation rows:
  - `Commercial`
  - `Extended`

## Subcategories
- In the broader master schema, subcategories belong in `product_subcategories`
- `product_subcategories` accepts comma-separated child categories under `Digital Collections`
- The compact digital template does not currently include this column, so use the main `categories` field unless a later template revision adds it

## Gallery and media rules
- `product_gallery` is the canonical gallery field
- Put multiple direct image URLs in one CSV cell separated by commas
- Example:
  - `https://example.com/image-1.jpg,https://example.com/image-2.jpg,https://example.com/image-3.jpg`
- `featured_image` is the main product image
- `meta:_bw_slider_hover_video` should be used when a hover video is available
- `meta:_bw_slider_hover_image` is the fallback hover media field

## Variation support note
- Variation import runtime is **not implemented yet**
- The template includes future-ready variation rows for planning and AI preparation
- Do not assume the current importer can create WooCommerce variations from these rows yet
- Do not add custom license term meta fields

## Column-by-column guide

| Column | Status | What to write | Example | Notes |
|---|---|---|---|---|
| `row_type` | Required | `product` for parent row, `variation` for child rows | `product` | Keep exact value |
| `parent_sku` | Required for future variation rows | Parent SKU for variation rows | `DIGI-BATS-001` | Leave empty on parent row |
| `sku` | Required | Stable unique SKU | `DIGI-BATS-001` | Variation rows need their own SKU |
| `post_title` | Required on parent row | Product title | `Bats of the World Pack` | Variation rows usually leave parent content fields empty |
| `post_name` | Optional | URL slug | `bats-of-the-world-pack` | Lowercase slug |
| `post_status` | Required on parent row | WordPress status | `publish` | Usually `publish` or `draft` |
| `product_type` | Required on parent row | Woo product type | `variable` | Digital parent should be `variable` |
| `default_variation` | Optional/future-facing | Default selected variation slug/value | `commercial` | Singular field |
| `regular_price` | Optional | Parent/base price | `29.00` | Parent row only |
| `sale_price` | Optional | Parent sale price | `19.00` | Leave empty if unused |
| `categories` | Required | Main category path/list | `Digital Collections` | Use default category unless you have a stronger reason |
| `tags` | Optional | Comma-separated tags | `Animals,Reference` | Keep concise |
| `post_content` | Optional | Full description | `High-resolution digital archive pack...` | Use factual wording |
| `post_excerpt` | Optional | Short description | `Digital archive pack with layered assets...` | Short summary |
| `featured_image` | Required | Direct main image URL | `https://example.com/images/bats-cover.jpg` | Direct URL only |
| `product_gallery` | Optional | Comma-separated direct image URLs in one cell | `https://example.com/1.jpg,https://example.com/2.jpg` | Canonical gallery field |
| `stock_status` | Optional | Woo stock status | `instock` | Keep standard Woo values |
| `tax_status` | Optional | Woo tax status | `taxable` | Leave empty if unknown |
| `tax_class` | Optional | Woo tax class slug | `` | Often empty |
| `meta:_bw_product_type` | Optional | Blackwork product branch | `digital` | Usually `digital` |
| `meta:_bw_showcase_title` | Optional | Showcase title | `Bats of the World Pack` | Presentation copy |
| `meta:_bw_showcase_description` | Optional | Showcase description | `Curated digital archive pack...` | Presentation copy |
| `meta:_bw_showcase_image` | Optional | Showcase image URL | `https://example.com/images/bats-showcase.jpg` | Direct URL or media reference policy used by your workflow |
| `meta:_bw_file_size` | Optional | Main file size | `320 MB` | Human-readable text is acceptable |
| `meta:_bw_assets_count` | Optional | Number of assets | `50` | Text or number |
| `meta:_bw_formats` | Optional | Formats list | `AI,PNG,EPS,SVG` | Comma-separated text |
| `meta:_bw_artist_name` | Optional | Artist/creator name | `Blackwork Archive` | Use only if known |
| `meta:_digital_source` | Optional | Source/origin | `Private archive` | Factual only |
| `meta:_digital_publisher` | Optional | Publisher | `Blackwork Editions` | Factual only |
| `meta:_digital_year` | Optional | Year | `1888` | Use exact year if known |
| `meta:_digital_technique` | Optional | Technique | `Lithography` | Factual only |
| `meta:_digital_total_assets` | Optional | Asset count summary | `50` | Can mirror assets count |
| `meta:_digital_assets_list` | Optional | Asset list | `Layered master file|Source scan|Preview JPG set` | Keep compact and factual |
| `meta:_digital_file_size` | Optional | Digital file size detail | `320 MB` | Optional mirror field |
| `meta:_digital_formats` | Optional | Digital formats detail | `AI,PNG,EPS,SVG` | Optional mirror field |
| `meta:_bw_slider_hover_image` | Optional | Hover fallback image URL | `https://example.com/images/bats-hover.jpg` | Use when no video is available |
| `meta:_bw_slider_hover_video` | Optional | Hover video URL | `https://example.com/video/bats-hover.mp4` | Preferred when available |
| `variation_name` | Future-ready only | Variation display name | `Commercial` | Not runtime-importable yet |
| `variation_regular_price` | Future-ready only | Variation regular price | `29.00` | Not runtime-importable yet |
| `variation_sale_price` | Future-ready only | Variation sale price | `19.00` | Not runtime-importable yet |
| `variation_image` | Future-ready only | Variation image URL | `https://example.com/images/bats-commercial.jpg` | Not runtime-importable yet |
| `variation_enabled` | Future-ready only | Enable flag | `yes` | Not runtime-importable yet |
| `variation_virtual` | Future-ready only | Virtual flag | `yes` | Not runtime-importable yet |
| `variation_downloadable` | Future-ready only | Downloadable flag | `yes` | Not runtime-importable yet |
| `variation_download_name` | Future-ready only | Download label | `Bats of the World Commercial License` | Not runtime-importable yet |
| `variation_download_url` | Future-ready only | Direct downloadable file URL | `https://example.com/downloads/bats-commercial.zip` | Not runtime-importable yet |
| `variation_attributes_json` | Future-ready only | Variation attributes JSON object | `{"licences":"Commercial"}` | Must stay valid JSON |

## Common mistakes
- Adding comments or guide rows inside the CSV
- Renaming columns
- Putting multiple gallery URLs into separate columns
- Inventing bibliographic or technical facts from the image alone
- Using indirect or protected image URLs instead of direct URLs
- Treating future variation rows as currently importable behavior

## AI filling rules
- Keep exact column names
- Keep one CSV row per logical row already defined by the template
- Use direct URLs for images and downloads
- If uncertain, leave the field empty
- Do not invent metadata
- Keep JSON valid when filling `variation_attributes_json`
