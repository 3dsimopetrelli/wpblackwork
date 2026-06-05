# Blackwork Digital Product Import Guide

## First-read workflow for ChatGPT/Codex
Blackwork is creating a new Digital product for the Blackwork site. This is an internal Blackwork production workflow, not an end-customer workflow.

Blackwork will provide:
1. `blackwork-digital-product-import-guide.md`
2. `blackwork-digital-product-import-template.csv`
3. the filled `product-upload-details-template.yml` or `product-upload-details-template.yaml`

Clarifications:
- Blackwork prepares and fills `product-upload-details-template.yml` or `product-upload-details-template.yaml` separately for each product production session.
- The YAML file is an operational input supplied by Blackwork, not part of the downloadable template set stored in this repository.
- It is not downloaded from the Product Import / Export dashboard.
- The YAML file is the source for:
  - product title
  - digital assets list
  - featured image URL
  - product gallery URLs
  - hover image URL
  - showcase image URLs
  - optional hover video URL
  - year
  - author / artist
  - total assets
  - formats
  - source notes
  - product context
  - technique
  - parent price
  - commercial price
  - extended price
- ChatGPT/Codex still analyzes the featured image and gallery images referenced in the YAML to produce:
  - subject understanding
  - SEO-oriented `post_content`
  - concise `post_excerpt`
  - allowed `product_subcategories`
  - exactly 10 internal-search tags
- Image access to the featured image and product gallery is mandatory for this workflow.
- If the featured image or product gallery images cannot be accessed and visually analyzed, ChatGPT/Codex must stop the workflow and must not generate the completed CSV.
- ChatGPT/Codex must not continue from YAML-only data when mandatory images are inaccessible.
- If Dropbox direct-media access fails for mandatory images, ChatGPT/Codex must ask Blackwork to upload the required images manually into the chat/session before continuing.
- Manually uploaded images are acceptable for visual analysis only.
- The YAML media URLs remain the canonical source for CSV media URL fields.
- Source notes are optional but should be preferred over visual guessing when provided.
- ChatGPT/Codex must not invent missing factual metadata.
- If a YAML field is empty, leave the corresponding CSV field empty unless this guide defines a safe fallback.

Required AI behavior:
1. Read this MD guide first.
2. Read the CSV template second.
3. Read the filled YAML product details file third.
4. Preserve exact CSV headers.
5. Keep the CSV importable.
6. Fill the existing structural parent row and the two standard variation rows.
7. Analyze the featured image and gallery images referenced in the YAML before filling content fields.
8. Stop the workflow and do not generate the CSV if the featured image or product gallery images cannot be accessed and visually analyzed.
9. If Dropbox direct-media access fails for mandatory images, ask Blackwork to upload the required images manually into the chat/session.
10. Create a clean product title.
11. Create a lowercase URL slug in `post_name`.
12. Generate a stable parent SKU from the product title.
13. Generate Commercial and Extended variation SKUs from the parent SKU.
14. Create a detailed natural SEO-oriented `post_content` description.
15. Create a concise `post_excerpt`.
16. Choose allowed `product_subcategories` according to the guide.
17. Write exactly 10 internal-search tags according to the guide.
18. Use `TECHNIQUE` from the YAML for `meta:_digital_technique`.
19. Use `Unknown` for technique if `TECHNIQUE` is empty.
20. Fill year and author/artist only when provided or clearly supported by the YAML or source notes.
21. Fill media fields from the supplied direct URLs in the YAML.
22. Do not invent facts.
23. Return a downloadable completed `.csv` file with the same columns as the template and no extra explanation unless Blackwork asks for it.

## External YAML input structure
Blackwork prepares a filled YAML file for each product before starting a ChatGPT/Codex CSV production session.

- The YAML is an external operational input, not a repository template.
- The YAML should be attached to ChatGPT/Codex together with the Digital guide and the Digital CSV.
- The filename can be `product-upload-details-template.yml`, `product-upload-details-template.yaml`, or any clear per-product filename, but the expected field labels must match this guide exactly.

```yaml
PRODUCT TITLE:
DIGITAL ASSETS LIST:
FEATURED IMAGE URL:
PRODUCT GALLERY:
HOVER IMAGE URL:
SHOWCASE IMAGE URLS:
HOVER VIDEO URL:
YEAR:
AUTHOR / ARTIST:
TOTAL ASSETS:
FORMATS:
SOURCE NOTES:
PRODUCT CONTEXT:
TECHNIQUE:
PRICE:
PRICE COMMERCIAL:
PRICE EXTENDED:
```

### Media link mapping
- `PRODUCT TITLE` → `post_title`
- If `PRODUCT TITLE` is empty, generate a clear `post_title` from the visual subject and source context
- Generate `post_name` from the final `post_title`
- Generate parent `sku` from the final `post_title`
- Generate Commercial and Extended variation SKUs from the parent SKU
- `FEATURED IMAGE URL` → `featured_image`
- `FEATURED IMAGE URL` → `variation_image` for both Commercial and Extended, unless separate variation images are added in the future
- `PRODUCT GALLERY` → `product_gallery`
- `PRODUCT GALLERY` may be one URL per line or comma-separated in YAML; in the CSV it must become one cell with comma-separated direct URLs
- `HOVER IMAGE URL` → `meta:_bw_slider_hover_image`
- `HOVER VIDEO URL` → `meta:_bw_slider_hover_video` only if provided
- If hover video is not provided, leave `meta:_bw_slider_hover_video` empty.
- `SHOWCASE IMAGE URLS` → `meta:_bw_showcase_image`
- If multiple showcase image URLs are provided, use the first URL unless Blackwork notes specify a different primary showcase image
- `PRICE` → parent row `regular_price` only if Blackwork wants a parent/base price
- `PRICE COMMERCIAL` → Commercial variation row `variation_regular_price`
- `PRICE EXTENDED` → Extended variation row `variation_regular_price`
- Leave sale price fields empty unless explicit sale price fields are added later
- `YEAR` → `meta:_digital_year`
- `AUTHOR / ARTIST` → `meta:_bw_artist_name`
- `TOTAL ASSETS` → `meta:_bw_assets_count`
- `TOTAL ASSETS` → `meta:_digital_total_assets`
- `FORMATS` → `meta:_bw_formats`
- `FORMATS` → `meta:_digital_formats`
- `DIGITAL ASSETS LIST` → `meta:_digital_assets_list`
- If `DIGITAL ASSETS LIST` is empty, generate a concise factual assets list from `TOTAL ASSETS`, `FORMATS`, `SOURCE NOTES`, and `PRODUCT CONTEXT`
- `SOURCE NOTES` may inform `meta:_digital_source`, `meta:_digital_publisher`, `post_content`, and `post_excerpt`, but do not invent source/publisher if not explicit
- `PRODUCT CONTEXT` may inform descriptions, asset list, tags, and subcategories
- `TECHNIQUE` → `meta:_digital_technique`
- If `TECHNIQUE` is empty, use `Unknown`
- Do not infer technique from images anymore unless Blackwork explicitly asks for it
- Only `product_gallery` accepts multiple URLs.
- All other media fields accept one direct URL only.
- Do not use Markdown links.
- Do not invent or transform media URLs.

### Image access order
1. Read the YAML.
2. Extract all media URLs.
3. Never attempt to analyze the original Dropbox `dl=0` preview URL.
4. For Dropbox URLs, first convert `dl=0` to `raw=1`.
5. If the `raw=1` version is inaccessible for visual analysis, try `dl=1`.
6. If that is still inaccessible, switch the host from `www.dropbox.com` to `dl.dropboxusercontent.com` and remove preview/download flags like `dl=0`, `dl=1`, or `raw=1`.
7. Preserve the `/scl/fi/...` path, filename, `rlkey`, `st`, and all other non-preview query parameters exactly.
8. Test that the featured image and gallery images are accessible.
9. Visually analyze the accessible images.
10. Only then fill the CSV.
11. If image access fails, stop and request accessible image links or direct image uploads.
12. If Blackwork uploads the required images manually into the chat/session, use those uploads only for visual analysis and continue using the YAML media URLs for CSV output.

Mandatory image fields:
- `FEATURED IMAGE URL`
- `PRODUCT GALLERY`

Optional media fields:
- `HOVER IMAGE URL`
- `SHOWCASE IMAGE URLS`
- `HOVER VIDEO URL`

Blocking rule:
- The workflow must stop if the featured image or product gallery images cannot be accessed and visually analyzed.
- Hover/showcase/video failures should be reported, but they do not block the workflow unless Blackwork explicitly makes them required for that product.
- Write only a direct-access Dropbox URL into the CSV, never the original `dl=0` preview URL.
- Manual image uploads are acceptable as the visual-analysis source when Dropbox URL access fails.
- The final CSV must still contain direct media URLs from the YAML, not chat upload references.

### Description and SEO requirement
- `post_content` should be a detailed, natural, SEO-oriented product description.
- It should use relevant subject, visual style, technique, historical, archive, and usage keywords naturally.
- Do not keyword-stuff.
- Do not make unsupported claims.
- `post_excerpt` should be shorter and suitable as product summary.
- This SEO rule applies to description copy only.
- The `tags` column is not SEO; tags are internal Blackwork search/discovery terms and must follow the Tags rules.

### Reusable Blackwork prompt template

You are filling a Blackwork Digital Product CSV.

Read the attached files in this order:
1. `blackwork-digital-product-import-guide.md`
2. `blackwork-digital-product-import-template.csv`
3. the filled `product-upload-details-template.yml` or `product-upload-details-template.yaml` attached by Blackwork

Use the YAML file as the source for Blackwork-provided product data and media URLs.

Fill the CSV according to the guide.

Rules:
- Image access is mandatory before filling the CSV.
- Before filling the CSV, visually access and analyze the featured image and product gallery images from the YAML.
- Never attempt to analyze the original Dropbox `dl=0` preview URL.
- Use the Dropbox direct URL access sequence before opening, analyzing, previewing, or writing any Dropbox URL:
  - Step 1: convert `dl=0` to `raw=1`
  - Step 2: if inaccessible, try `dl=1`
  - Step 3: if inaccessible, switch the host to `dl.dropboxusercontent.com` and remove `dl=0`, `dl=1`, or `raw=1`
- Preserve the `/scl/fi/...` path, filename, `rlkey`, `st`, and all other non-preview query parameters exactly.
- Use the first accessible direct-media Dropbox variant for image access and analysis.
- Write only a direct-access Dropbox URL into the CSV, never the original `dl=0` preview URL.
- If you cannot access or visually analyze the featured image and product gallery images, stop the operation.
- Do not generate the completed CSV.
- Do not continue using only YAML text/source notes.
- Do not invent tags, subcategories, title, description, subject matter, or visual details.
- Return a blocking message explaining which images could not be accessed and ask Blackwork to provide accessible links or upload the images directly.
- If Blackwork uploads the required images manually into the chat/session, use those uploaded images only for visual analysis.
- Continue using the YAML media URLs for CSV media fields.
- Do not write chat attachment URLs or internal upload references into the CSV.
- Do not rename CSV columns.
- Do not remove `meta:` prefixes.
- Do not add comment rows.
- Keep the CSV importable.
- Preserve the parent product row and the Commercial / Extended variation rows.
- Analyze the featured image and gallery images referenced in the YAML before writing title, descriptions, subcategories, and tags.
- Apply the Dropbox direct URL access sequence to all Dropbox media URLs before opening, analyzing, previewing, or writing them into the CSV.
- If image analysis is not possible, stop the workflow and do not generate the CSV.
- Use `PRODUCT TITLE` for `post_title`; if empty, generate a clear title from the visual subject and source context.
- Generate `post_name` from the final `post_title`.
- Generate parent SKU from the final `post_title`.
- Generate Commercial and Extended variation SKUs from the parent SKU.
- Use `FEATURED IMAGE URL` in `featured_image`.
- Use `FEATURED IMAGE URL` as `variation_image` for both Commercial and Extended unless separate variation images are provided in the future.
- Use `PRODUCT GALLERY` in `product_gallery`.
- Use `HOVER IMAGE URL` in `meta:_bw_slider_hover_image`.
- Use `HOVER VIDEO URL` in `meta:_bw_slider_hover_video` only if provided.
- Use `SHOWCASE IMAGE URLS` for `meta:_bw_showcase_image`, using the first URL if multiple are provided.
- Use `PRICE COMMERCIAL` in the Commercial row `variation_regular_price`.
- Use `PRICE EXTENDED` in the Extended row `variation_regular_price`.
- Use `PRICE` in the parent row `regular_price` only if appropriate.
- Use `TOTAL ASSETS` in `meta:_bw_assets_count` and `meta:_digital_total_assets`.
- Use `FORMATS` in `meta:_bw_formats` and `meta:_digital_formats`.
- Use `DIGITAL ASSETS LIST` in `meta:_digital_assets_list`; if empty, generate a concise factual assets list from the YAML context.
- Use `YEAR` in `meta:_digital_year` if provided.
- Use `AUTHOR / ARTIST` in `meta:_bw_artist_name` if provided.
- Use `TECHNIQUE` in `meta:_digital_technique`; if empty, use `Unknown`.
- Do not visually guess technique unless Blackwork explicitly asks.
- Choose `product_subcategories` only from the allowed list in the guide.
- Choose up to 5 subcategories; if 5 coherent ones are not available, choose at least 3.
- Write exactly 10 tags for internal Blackwork search/discovery, not SEO.
- Create a detailed natural SEO-oriented `post_content`.
- Create a concise `post_excerpt`.
- Do not invent facts not present in the images, YAML, or source notes.
- Create and return the completed CSV as a downloadable `.csv` file.
- Use the filename pattern:
  - `blackwork-digital-product-completed-{product-slug}.csv`
- Keep the same delimiter, quoting style, and column order as the template.
- The CSV file must contain only valid CSV content.
- Preserve the exact template columns.
- Do not add extra columns.
- Do not include Markdown.
- Do not include explanations.
- Do not include guide text.
- Do not add extra rows.
- Include the completed parent product row and the completed Commercial and Extended variation rows.
- If the platform cannot create a downloadable file, return only the raw CSV content in a single CSV code block.

## Purpose
Use this guide together with the Digital CSV template and the filled YAML product details file supplied by Blackwork for a specific product to help ChatGPT/Codex prepare a clean importable CSV for Blackwork digital products.

## Files to use
- `blackwork-digital-product-import-template.csv`
- this guide: `blackwork-digital-product-import-guide.md`
- the filled `product-upload-details-template.yml` or `product-upload-details-template.yaml` attached by Blackwork for the current product

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
- `post_title` should use `PRODUCT TITLE` from the YAML when it is provided.
- If `PRODUCT TITLE` is empty, generate a clean title from the visual subject and source context.
- `post_name` should be a lowercase URL slug derived from the final `post_title`.
- Do not include placeholder words from the template.

## Media rules
- `featured_image` must be one direct image URL.
- `product_gallery` must be one CSV cell containing comma-separated direct image URLs.
- Do not split gallery URLs across multiple columns.
- Do not use Markdown links.

## Dropbox URL conversion for media imports
- Blackwork media links may come from Dropbox.
- WooCommerce CSV imports require direct image/media URLs.
- Never use the original Dropbox `dl=0` preview URL for image analysis or final CSV output.
- Use this Dropbox direct URL access sequence:
  - Step 1: convert `dl=0` to `raw=1`
  - Step 2: if the `raw=1` variant cannot be visually accessed, try `dl=1`
  - Step 3: if that still fails, change the host from `www.dropbox.com` to `dl.dropboxusercontent.com` and remove preview/download flags like `dl=0`, `dl=1`, or `raw=1`
- Preserve the `/scl/fi/...` path.
- Preserve the filename.
- Preserve `rlkey`.
- Preserve `st`.
- Preserve all other non-preview query parameters.
- Do not rewrite the URL in any other way.
- Do not remove non-preview query parameters.
- Do not convert non-Dropbox URLs unless they contain the exact Dropbox-style preview/download flags and need direct file access.
- Use the first accessible direct-media Dropbox variant for image analysis.
- Write only a direct-access Dropbox URL into the CSV, never the original `dl=0` preview URL.
- If none of the direct Dropbox variants can be visually accessed for the featured image or product gallery, stop the workflow and do not generate the CSV.
- If Dropbox direct-media access fails for mandatory images, ask Blackwork to upload the required images manually into the chat/session.
- Manual uploads are acceptable for visual analysis only.
- The final CSV must still use the converted YAML media URLs, not chat upload references.

This rule applies to all media URL fields:
- `featured_image`
- `product_gallery`
- `variation_image`
- `meta:_bw_slider_hover_image`
- `meta:_bw_slider_hover_video`
- any future download/media URL fields when they use Dropbox links

Visual analysis may use:
- `FEATURED IMAGE URL`
- `PRODUCT GALLERY`
- or manually uploaded replacement images if those URLs fail

CSV output must use:
- `featured_image` from the converted YAML `FEATURED IMAGE URL`
- `product_gallery` from converted YAML `PRODUCT GALLERY` URLs
- `variation_image` from the converted YAML `FEATURED IMAGE URL`
- `meta:_bw_slider_hover_image` from the converted YAML `HOVER IMAGE URL`
- `meta:_bw_showcase_image` from the converted YAML `SHOWCASE IMAGE URLS`
- `meta:_bw_slider_hover_video` from the converted YAML `HOVER VIDEO URL`, if provided

Example:
- Input:
  - `https://www.dropbox.com/scl/fi/95it0olxnqijs40azg6fl/Ethnographic-Figures-Artifacts-_id-cover.webp?rlkey=youa22xq8lzdr0ecb46hml798&st=qb18mboe&dl=0`
- Attempt 1:
  - `https://www.dropbox.com/scl/fi/95it0olxnqijs40azg6fl/Ethnographic-Figures-Artifacts-_id-cover.webp?rlkey=youa22xq8lzdr0ecb46hml798&st=qb18mboe&raw=1`
- Attempt 2:
  - `https://www.dropbox.com/scl/fi/95it0olxnqijs40azg6fl/Ethnographic-Figures-Artifacts-_id-cover.webp?rlkey=youa22xq8lzdr0ecb46hml798&st=qb18mboe&dl=1`
- Attempt 3:
  - `https://dl.dropboxusercontent.com/scl/fi/95it0olxnqijs40azg6fl/Ethnographic-Figures-Artifacts-_id-cover.webp?rlkey=youa22xq8lzdr0ecb46hml798&st=qb18mboe`

## Meta column prefix
Columns that start with `meta:` are importer mapping columns.
The `meta:` prefix is not part of the real WordPress meta key.
For example, the CSV column `meta:_digital_technique` maps to the saved product meta key `_digital_technique`.
Keep the `meta:` prefix exactly as written in the CSV template.

- Do not rename `meta:` columns.
- Do not remove the `meta:` prefix.
- Do not replace `meta:_digital_technique` with `_digital_technique`.
- Preserve exact spelling of all meta columns.
- If a value is unknown, leave the cell empty rather than changing the column name.

## How to give the files to ChatGPT/Codex
Provide:
- the CSV template
- this Markdown guide
- the filled `product-upload-details-template.yml` or `product-upload-details-template.yaml` attached by Blackwork for the current product

Recommended prompt framing:
- fill the CSV using the exact column names
- keep the CSV importable
- do not add comments or extra rows
- use the YAML file as the source for Blackwork-provided values
- do not invent facts from the images, YAML, or source notes

## Required fields
- `row_type`
- `sku`
- `post_title`
- `post_status`
- `product_type`
- `categories`
- `featured_image`

For variation rows:
- `row_type`
- `parent_sku`
- `sku`
- `variation_name`
- `variation_regular_price`
- `variation_downloadable`
- `variation_download_url`

## Optional fields
- `post_name`
- `sale_price`
- `tags`
- `post_content`
- `post_excerpt`
- `product_gallery`
- Blackwork digital meta fields
- hover media fields
- variation download fields

## Digital defaults
- Default category: `Digital Collections`
- `product_type` should be `variable`
- `default_variation` is singular and normally `commercial`
- Standard example variation rows:
  - `Commercial`
  - `Extended`
- `variation_enabled`, `variation_virtual`, and `variation_downloadable` should normally remain `yes` for standard Blackwork digital Commercial and Extended variations.
- Do not leave these three fields empty in the final filled CSV unless explicitly instructed.
- Use `yes` / `no`.
- For downloadable digital products, the normal value is `yes`.

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
- Use `TECHNIQUE` from the YAML for `meta:_digital_technique`.
- If `TECHNIQUE` is empty, use `Unknown`.
- Do not invent or visually guess technique unless Blackwork explicitly asks.

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
- The current Digital import runtime supports:
  - one parent variable product row
  - one or more variation rows linked by `parent_sku`
- The standard Blackwork Digital workflow expects:
  - one parent row
  - one `Commercial` variation row
  - one `Extended` variation row
- Variation creation/update is driven by:
  - `row_type`
  - `parent_sku`
  - `sku`
  - `variation_name`
  - variation price / flag / download fields
- `variation_attributes_json` remains optional and future-facing.
- Do not add custom license term meta fields.

## Column-by-column guide

| Column | Status | What to write | Example | Notes |
|---|---|---|---|---|
| `row_type` | Required | `product` for parent row, `variation` for child rows | `product` | Keep exact value |
| `parent_sku` | Required for variation rows | Parent SKU for variation rows | `DIGI-BATS-001` | Leave empty on parent row |
| `sku` | Required | Stable unique SKU | `DIGI-BATS-001` | Variation rows need their own SKU |
| `post_title` | Required on parent row | Product title | `Bats of the World Pack` | Variation rows usually leave parent content fields empty |
| `post_name` | Optional | URL slug | `bats-of-the-world-pack` | Lowercase slug |
| `post_status` | Required on parent row | WordPress status | `publish` | Usually `publish` or `draft` |
| `product_type` | Required on parent row | Woo product type | `variable` | Digital parent should be `variable` |
| `default_variation` | Optional | Default selected variation slug/value | `commercial` | Singular field. Parent row only |
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
| `meta:_bw_assets_count` | Optional | Number of assets | `50` | Text or number |
| `meta:_bw_formats` | Optional | Formats list | `AI,PNG,EPS,SVG` | Comma-separated text |
| `meta:_bw_artist_name` | Optional | Artist/creator name | `Blackwork Archive` | Use only if known |
| `meta:_digital_source` | Optional | Source/origin | `Private archive` | Factual only |
| `meta:_digital_publisher` | Optional | Publisher | `Blackwork Editions` | Factual only |
| `meta:_digital_year` | Optional | Year | `1888` | Use exact year if known |
| `meta:_digital_technique` | YAML-provided / fallback | `TECHNIQUE` from YAML | `Lithography` | Use `Unknown` if YAML is empty; do not infer unless explicitly instructed |
| `meta:_digital_total_assets` | Optional | Asset count summary | `50` | Can mirror assets count |
| `meta:_digital_assets_list` | Optional | Asset list | `Layered master file|Source scan|Preview JPG set` | Keep compact and factual |
| `meta:_digital_formats` | Optional | Digital formats detail | `AI,PNG,EPS,SVG` | Optional mirror field |
| `meta:_bw_slider_hover_image` | Recommended when provided / normally expected | Hover image URL | `https://example.com/images/bats-hover.jpg` | Direct URL only. Acts as fallback when no hover video is provided |
| `meta:_bw_slider_hover_video` | Optional | Hover video URL | `https://example.com/video/bats-hover.mp4` | Direct URL only. Leave empty if not provided. Takes priority when present |
| `variation_name` | Required on variation rows | Variation display name | `Commercial` | Used to build the WooCommerce License variation |
| `variation_regular_price` | Optional | Variation regular price | `29.00` | Variation row |
| `variation_sale_price` | Optional | Variation sale price | `19.00` | Variation row |
| `variation_image` | Optional | Variation image URL | `https://example.com/images/bats-commercial.jpg` | Imported only when image sideload is enabled |
| `variation_enabled` | Required on variation rows | Enable flag | `yes` | `yes` publishes the variation, `no` stores it disabled/private |
| `variation_virtual` | Required on variation rows | Virtual flag | `yes` | Variation row |
| `variation_downloadable` | Required on variation rows | Downloadable flag | `yes` | Variation row |
| `variation_download_name` | Optional | Download label | `Bats of the World Commercial License` | Used when a downloadable file URL is present |
| `variation_download_url` | Optional | Direct downloadable file URL | `https://example.com/downloads/bats-commercial.zip` | Saved as WooCommerce downloadable file URL |
| `variation_attributes_json` | Optional / future-facing | Variation attributes JSON object | `{"licences":"Commercial"}` | Current runtime uses `variation_name` as the canonical License attribute |

## Common mistakes
- Adding comments or guide rows inside the CSV
- Renaming columns
- Putting multiple gallery URLs into separate columns
- Inventing bibliographic or technical facts from the image alone
- Using indirect or protected image URLs instead of direct URLs
- Treating variation rows as standalone products

## AI filling rules
- Keep exact column names
- Keep one CSV row per logical row already defined by the template
- Use direct URLs for images and downloads
- If uncertain, leave the field empty
- Do not invent metadata
- Keep JSON valid when filling `variation_attributes_json`
