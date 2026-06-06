# Blackwork Digital Product Complete JSON Guide

## Purpose
This guide defines the Blackwork Digital workflow for producing a Complete Product JSON file for the plugin-side Digital CSV Builder.

The goal is to separate creative work from deterministic CSV generation:
- ChatGPT analyzes images and writes creative product content.
- The plugin builds the final WordPress import CSV.

This guide is for producing the Complete Product JSON only.

## Current workflow
1. Blackwork prepares a filled product YAML.
2. Blackwork gives ChatGPT the YAML and the product images or image links.
3. ChatGPT visually analyzes the images.
4. ChatGPT returns only one Complete Product JSON.
5. Blackwork opens `Blackwork Site -> Product Import / Export -> Import Product -> Create Digital CSV`.
6. Blackwork pastes the Complete Product JSON into the plugin.
7. The plugin builds and validates the final Digital CSV.
8. Blackwork downloads the generated CSV.
9. Blackwork imports the CSV using the existing Upload CSV workflow.

Important:
- ChatGPT does not create CSV.
- ChatGPT does not fill CSV columns.
- ChatGPT does not convert Dropbox URLs.
- ChatGPT does not create SKU.
- ChatGPT does not create variation rows.
- ChatGPT does not write media URLs into CSV fields.
- The plugin performs all deterministic CSV work.

## Responsibilities
### Blackwork provides
- a filled product YAML
- image links or uploaded images
- source notes or context when available

### ChatGPT provides
- Complete Product JSON only
- `source_data` copied from the YAML
- `creative_data` generated from image analysis and source context

### The plugin provides
- Dropbox URL conversion
- CSV generation
- SKU generation
- fixed parent and variation rows
- media URL mapping
- ZIP URL mapping
- meta field mapping
- validation
- downloadable CSV output

Responsibility boundary:
- ChatGPT creates Complete Product JSON only.
- The plugin creates the CSV.
- The plugin converts Dropbox URLs.
- The plugin validates and blocks malformed URLs.
- ChatGPT must not create or edit the CSV.

## Complete Product JSON schema
Return exactly this structure:

```json
{
  "source_data": {
    "product_title": "",
    "digital_assets_list": "",
    "featured_image_url": "",
    "product_gallery": [],
    "hover_image_url": "",
    "showcase_image_urls": [],
    "hover_video_url": "",
    "year": "",
    "author_artist": "",
    "total_assets": "",
    "formats": "",
    "source_notes": "",
    "product_context": "",
    "technique": "",
    "price": "",
    "price_commercial": "",
    "price_extended": "",
    "download_zip_url": "",
    "download_zip_url_commercial": "",
    "download_zip_url_extended": ""
  },
  "creative_data": {
    "post_title": "",
    "post_content": "",
    "post_excerpt": "",
    "tags": [],
    "product_subcategories": [],
    "visual_analysis_notes": ""
  }
}
```

## Example Complete Product JSON structure

```json
{
  "source_data": {
    "product_title": "Birds of the Northern Coast",
    "digital_assets_list": "12 high-resolution scanned plates, 1 title sheet, 1 reference sheet",
    "featured_image_url": "https://www.dropbox.com/scl/fi/example/featured.webp?rlkey=examplekey&st=examplest&dl=0",
    "product_gallery": [
      "https://www.dropbox.com/scl/fi/example/gallery-01.webp?rlkey=examplekey&st=examplest&dl=0",
      "https://www.dropbox.com/scl/fi/example/gallery-02.webp?rlkey=examplekey&st=examplest&dl=0"
    ],
    "hover_image_url": "https://www.dropbox.com/scl/fi/example/hover.webp?rlkey=examplekey&st=examplest&dl=0",
    "showcase_image_urls": [
      "https://www.dropbox.com/scl/fi/example/showcase-01.webp?rlkey=examplekey&st=examplest&dl=0",
      "https://www.dropbox.com/scl/fi/example/showcase-02.webp?rlkey=examplekey&st=examplest&dl=0"
    ],
    "hover_video_url": "",
    "year": "1847",
    "author_artist": "Example Artist",
    "total_assets": "14",
    "formats": "WEBP, JPG, PDF",
    "source_notes": "Natural history study with coastal bird species and engraved captions.",
    "product_context": "19th-century ornithology reference material with detailed plate compositions.",
    "technique": "Engraving",
    "price": "",
    "price_commercial": "39",
    "price_extended": "79",
    "download_zip_url": "https://www.dropbox.com/scl/fi/example/digital-pack.zip?rlkey=examplekey&st=examplest&dl=0",
    "download_zip_url_commercial": "",
    "download_zip_url_extended": ""
  },
  "creative_data": {
    "post_title": "Birds of the Northern Coast Digital Archive",
    "post_content": "A detailed digital collection of ornithological coastal bird plates featuring engraved linework, period captions, and carefully preserved natural history compositions suited for researchers, designers, and collectors.",
    "post_excerpt": "A curated digital archive of engraved coastal bird studies from a 19th-century natural history source.",
    "tags": [
      "birds",
      "ornithology",
      "natural history",
      "engraving",
      "coastal wildlife",
      "avian anatomy",
      "historical illustration",
      "scientific plates",
      "nineteenth century",
      "field reference"
    ],
    "product_subcategories": [
      "Birds",
      "Scientific Instruments",
      "Landscapes"
    ],
    "visual_analysis_notes": "The featured and gallery images show engraved bird studies with coastal settings, scientific labeling, and strong natural history reference value."
  }
}
```

## source_data rules
- `source_data` must be copied from the Blackwork YAML.
- Do not invent missing source data.
- Do not convert Dropbox URLs.
- Do not remove query parameters.
- Do not remove `rlkey`.
- Do not remove `st`.
- Do not change image or ZIP URLs.
- `product_gallery` must be an array.
- `showcase_image_urls` must be an array.
- Empty optional YAML fields may remain empty strings.
- If a required source field is missing from YAML, stop and ask Blackwork.

Expected YAML-backed fields:
- `PRODUCT TITLE` -> `source_data.product_title`
- `DIGITAL ASSETS LIST` -> `source_data.digital_assets_list`
- `FEATURED IMAGE URL` -> `source_data.featured_image_url`
- `PRODUCT GALLERY` -> `source_data.product_gallery`
- `HOVER IMAGE URL` -> `source_data.hover_image_url`
- `SHOWCASE IMAGE URLS` -> `source_data.showcase_image_urls`
- `HOVER VIDEO URL` -> `source_data.hover_video_url`
- `YEAR` -> `source_data.year`
- `AUTHOR / ARTIST` -> `source_data.author_artist`
- `TOTAL ASSETS` -> `source_data.total_assets`
- `FORMATS` -> `source_data.formats`
- `SOURCE NOTES` -> `source_data.source_notes`
- `PRODUCT CONTEXT` -> `source_data.product_context`
- `TECHNIQUE` -> `source_data.technique`
- `PRICE` -> `source_data.price`
- `PRICE COMMERCIAL` -> `source_data.price_commercial`
- `PRICE EXTENDED` -> `source_data.price_extended`
- `DOWNLOAD ZIP URL` -> `source_data.download_zip_url`
- `DOWNLOAD ZIP URL COMMERCIAL` -> `source_data.download_zip_url_commercial`
- `DOWNLOAD ZIP URL EXTENDED` -> `source_data.download_zip_url_extended`

## creative_data rules
- `post_title` may use `PRODUCT TITLE` as a base and may be refined only if useful.
- `post_content` must be a detailed natural SEO-oriented product description.
- `post_excerpt` must be concise.
- `tags` must contain exactly 10 internal Blackwork search/discovery terms.
- `tags` must describe subject matter, visual content, objects, people, animals, plants, style, place, or historical content.
- `tags` must not be generic marketing or SEO terms.
- `product_subcategories` must contain only allowed subcategory names from this guide.
- Choose up to 5 subcategories.
- Use at least 3 subcategories when possible.
- `visual_analysis_notes` should briefly explain what was visually observed and how it informed tags and subcategories.
- Do not include URLs, ZIP URLs, prices, SKU, CSV rows, or WordPress meta keys inside `creative_data`.

## Mandatory image access
- ChatGPT must visually access and analyze the featured image and product gallery images before creating `creative_data`.
- If Dropbox image links are not visually accessible, stop.
- Ask Blackwork to upload the required images manually into the chat or session.
- When manual images are uploaded, match them to YAML media fields by filename.
- Uploaded images are for visual analysis only.
- The JSON must still copy the original YAML URLs into `source_data`.
- Do not proceed from YAML text alone if mandatory images are not visible.

## Filename matching
- Extract filenames from YAML media URLs.
- Match uploaded image filenames to those YAML filenames.
- Use exact match first.
- If needed, URL-decode and compare case-insensitively.
- If a required image filename is missing or ambiguous, stop and ask Blackwork.

## Allowed Digital subcategories
- Botany
- Mammals
- Birds
- Fish
- Insects
- Reptiles & Amphibians
- Paleontology
- Skeletal Anatomy
- Muscular Anatomy
- Internal Anatomy
- Surgery
- Medical Conditions
- Physiognomy / Phrenology
- Astronomy
- Mathematics / Geometry
- Physics
- Chemistry
- Scientific Instruments
- Maps
- Exploration / Voyages
- Ethnography
- Landscapes
- Religious Scenes
- Mythology
- Allegory
- Symbolism / Iconology
- Architecture
- Ornament / Patterns
- Furniture
- Interiors
- Weapons & Armor
- Tools & Instruments
- Decorative Objects
- Everyday Objects
- Ships
- Vehicles (Land)
- Aviation
- Machinery
- Heraldry / Coats of Arms
- Emblems / Seals
- Letterforms / Calligraphy
- Ornaments / Borders

## JSON output and download rules
- The output must be valid JSON parseable by `JSON.parse`.
- Prefer a downloadable `.json` file.
- Filename pattern:
  - `blackwork-digital-product-complete-{product-slug}.json`
- If a downloadable file cannot be created, return one fenced `json` code block only.
- Do not write JSON as ordinary chat prose.
- Do not split the JSON across multiple messages unless impossible.
- Do not include Markdown links.
- URLs must be plain JSON strings.
- URLs must not be wrapped in `[]` or `()`.
- URLs must be copied exactly from YAML into `source_data`.
- Dropbox URLs must not be converted by ChatGPT.
- Do not include explanations before or after the JSON.
- No comments.
- No trailing commas.
- No CSV.

## Final self-check before returning JSON
Before returning the Complete Product JSON, verify:
- the JSON parses
- no string contains `[http`
- no string contains `](http`
- no URL contains `%22` unless it was present in the YAML
- `source_data` contains all YAML fields.
- `source_data` media and ZIP URLs match the YAML exactly.
- all `source_data` URLs are plain strings
- `product_gallery` is an array.
- `showcase_image_urls` is an array.
- `creative_data.tags` has exactly 10 items.
- `creative_data.product_subcategories` uses only allowed names.
- `creative_data` contains no URLs
- no CSV fields are present.
- no WordPress meta keys are present.
- no Dropbox conversion was performed.
- mandatory images were visually analyzed.
