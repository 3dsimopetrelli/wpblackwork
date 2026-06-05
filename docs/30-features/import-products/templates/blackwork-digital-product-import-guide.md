# Blackwork Digital Product Import Guide

## Purpose
Use this guide together with the Digital CSV template to help ChatGPT/Codex prepare a clean importable CSV for Blackwork digital products from product images, source notes, and catalog metadata.

## Files to use
- `blackwork-digital-product-import-template.csv`
- this guide: `blackwork-digital-product-import-guide.md`

## Template row usage
- The CSV contains structural rows, not final example content.
- ChatGPT/Codex must replace empty fields with product-specific data.
- Do not copy placeholder or example subjects from the template into real products.
- These fixed defaults may remain as provided in the template:
  - `post_status = publish`
  - `product_type = variable`
  - `default_variation = commercial`
  - `categories = Digital Collections`
  - `meta:_bw_product_type = digital`
  - `variation_name = Commercial`
  - `variation_name = Extended`
  - `variation_enabled = yes`
  - `variation_virtual = yes`
  - `variation_downloadable = yes`

## SKU generation rules
- Generate a stable SKU from the product title.
- Parent SKU format:
  - `DIGI-{SHORT-SLUG}-{3 DIGIT NUMBER}`
- Example:
  - Product title: `Bats of the World`
  - Parent SKU: `DIGI-BATS-WORLD-001`
- Commercial variation SKU:
  - `{PARENT-SKU}-COMMERCIAL`
- Extended variation SKU:
  - `{PARENT-SKU}-EXTENDED`
- Use the same parent_sku value in variation rows.
- Do not reuse the example SKU across products.
- Keep SKUs uppercase, ASCII, hyphen-separated.

## Title and slug rules
- `post_title` should be a clean product title based on the image package.
- `post_name` should be a lowercase URL slug derived from `post_title`.
- Do not include placeholder words from the template.

## Media rules
- `featured_image` must be one direct image URL.
- `product_gallery` must be one CSV cell containing comma-separated direct image URLs.
- Do not split gallery URLs across multiple columns.
- Do not use Markdown links.

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
- The parent category remains `Digital Collections`

### Controlled selection rule for AI filling
- Look at the product images and source notes.
- Choose up to 5 subcategories from the allowed list below.
- If 5 coherent subcategories cannot be found, choose at least 3 coherent subcategories.
- Do not invent new subcategories.
- Do not rename the allowed subcategories.
- Preserve exact spelling, punctuation, ampersands, and slashes.
- Write the selected values into the CSV column `product_subcategories`.
- Values must be comma-separated in a single CSV cell.
- Do not include the group names in the CSV cell.
- Use only the subcategory names.
- Prefer the most visually and conceptually relevant categories.
- If the image clearly belongs to one domain, choose more specific subcategories from that domain.
- If the image is ambiguous, choose the best 3 broad/coherent subcategories and do not force weak matches.

### Allowed subcategories

Natural History:
- Botany
- Mammals
- Birds
- Fish
- Insects
- Reptiles & Amphibians
- Paleontology

Human / Medical:
- Skeletal Anatomy
- Muscular Anatomy
- Internal Anatomy
- Surgery
- Medical Conditions
- Physiognomy / Phrenology

Science:
- Astronomy
- Mathematics / Geometry
- Physics
- Chemistry
- Scientific Instruments

Geography / World:
- Maps
- Exploration / Voyages
- Ethnography
- Landscapes

Art / Symbolic:
- Religious Scenes
- Mythology
- Allegory
- Symbolism / Iconology

Built World / Design:
- Architecture
- Ornament / Patterns
- Furniture
- Interiors

Objects:
- Weapons & Armor
- Tools & Instruments
- Decorative Objects
- Everyday Objects

Transport / Machines:
- Ships
- Vehicles (Land)
- Aviation
- Machinery

Identity / Graphics:
- Heraldry / Coats of Arms
- Emblems / Seals
- Letterforms / Calligraphy
- Ornaments / Borders

### Examples
1. Botanical plate
   - `product_subcategories = Botany,Natural History` is not allowed because `Natural History` is a group name.
   - Correct:
     - `product_subcategories = Botany,Ornament / Patterns,Scientific Instruments`

2. Anatomical skeleton image
   - `product_subcategories = Skeletal Anatomy,Medical Conditions,Scientific Instruments`

3. Decorative architectural plate
   - `product_subcategories = Architecture,Ornament / Patterns,Interiors,Decorative Objects`

## Gallery and media rules
- `product_gallery` is the canonical gallery field
- Put multiple direct image URLs in one CSV cell separated by commas
- Example:
  - `https://example.com/image-1.jpg,https://example.com/image-2.jpg,https://example.com/image-3.jpg`
- `featured_image` is the main product image

## Product Presentation Hover Media
- Fill `meta:_bw_slider_hover_image` with a direct URL to the hover image.
- The hover image will usually be provided and should normally be filled when available.
- Fill `meta:_bw_slider_hover_video` with a direct URL to the hover video only when a hover video is provided.
- Hover video has priority on the product presentation.
- If no hover video is provided, leave `meta:_bw_slider_hover_video` empty.
- When the video field is empty, the hover image acts as fallback.
- Do not invent a video URL.
- Do not reuse the featured image as hover image unless the source notes explicitly say to do so.
- Use direct media URLs only.
- Do not use Markdown links.
- Do not put multiple URLs in either hover media field.
- Each hover media field accepts one URL only.

## Digital Technique / Production Technique
- `meta:_digital_technique` maps to the product meta key `_digital_technique`.
- Fill `meta:_digital_technique` only with one of the allowed standardized technique values below.
- Use the technique that best matches the source image, print, scan, or provided source notes.
- Do not invent technique names.
- Do not use broad free-text descriptions if a controlled value exists.
- Preserve exact spelling and capitalization.
- If the technique is uncertain, use `Unknown`.
- If multiple techniques are clearly present, use `Mixed Techniques`.
- If the item is hand-colored after printing, use `Hand-Colored Print` when that is the most important technique note.
- If source notes provide the production technique, prefer the source notes over visual guessing.
- If the image alone is insufficient to identify the technique, use `Unknown`.

### Allowed technique values

Relief Printing:
- Woodcut
- Wood Engraving

Intaglio Printing:
- Copper Engraving
- Steel Engraving
- Etching
- Aquatint
- Mezzotint
- Stipple Engraving

Planographic Printing:
- Lithography
- Chromolithography

Photomechanical Processes:
- Photogravure
- Collotype
- Heliogravure
- Line Block
- Halftone

Modern Printing:
- Offset Print

Special Categories:
- Hand-Colored Print
- Mixed Techniques
- Unknown

## Tags
- The `tags` field must contain exactly 10 tags.
- Tags are for internal Blackwork site search and image discovery.
- Tags are **not** SEO keywords.
- Tags are **not** marketing keywords.
- Tags must describe the actual images or content inside the product package.
- Prefer specific searchable terms over generic terms.
- Use terms that help someone find a specific kind of image inside a large archive.
- Good tag types include:
  - specific animals
  - plant or flower names
  - anatomical subjects
  - objects
  - tools
  - machines
  - symbols
  - maps
  - architecture details
  - ornament types
  - scientific subjects
  - visual techniques only when relevant
- Do not use generic SEO or marketplace tags such as:
  - `digital download`
  - `vintage illustration`
  - `printable art`
  - `wall art`
  - `clipart`
  - `instant download`
  - `high resolution`
  - `commercial use`
  - `design asset`
  - `antique print`
- Do not repeat the same concept with small wording variations.
- Do not invent subjects that are not visible in the images or present in the source notes.
- If a species or object is identifiable, use the specific name.
- If it is not identifiable, use the most accurate broader visible term.
- Write all 10 tags in one CSV cell.
- Tags must be comma-separated.
- Do not write more than 10 tags.
- Do not write fewer than 10 tags unless the source material is extremely limited; if fewer are unavoidable, explain the uncertainty outside the CSV, not inside the `tags` cell.

### Tag examples
Bad tags:
- `digital download, vintage illustration, printable art, wall art, clipart, high resolution, instant download, commercial use, antique print, design asset`

Good tags for a bat image set:
- `bat, chiroptera, wing anatomy, nocturnal mammal, flying mammal, cave animal, mammal skeleton, zoological plate, natural history, scientific illustration`

Good tags for a botanical image set:
- `rose, petals, leaf structure, flower stem, botanical plate, plant anatomy, garden flower, herbarium, botany, floral study`

Good tags for architectural ornament images:
- `acanthus, column capital, frieze, ornamental border, decorative pattern, classical ornament, carved stone, architectural detail, interior ornament, pattern study`

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
| `meta:_digital_technique` | Optional / controlled vocabulary | One standardized production technique | `Lithography` | Use `Unknown` if uncertain; do not invent values |
| `meta:_digital_total_assets` | Optional | Asset count summary | `50` | Can mirror assets count |
| `meta:_digital_assets_list` | Optional | Asset list | `Layered master file|Source scan|Preview JPG set` | Keep compact and factual |
| `meta:_digital_file_size` | Optional | Digital file size detail | `320 MB` | Optional mirror field |
| `meta:_digital_formats` | Optional | Digital formats detail | `AI,PNG,EPS,SVG` | Optional mirror field |
| `meta:_bw_slider_hover_image` | Recommended when provided / normally expected | Hover image URL | `https://example.com/images/bats-hover.jpg` | Direct URL only. Acts as fallback when no hover video is provided |
| `meta:_bw_slider_hover_video` | Optional | Hover video URL | `https://example.com/video/bats-hover.mp4` | Direct URL only. Leave empty if not provided. Takes priority when present |
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
