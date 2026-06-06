You are creating Complete Product JSON for a Blackwork Digital Product.

Read:
1. `blackwork-digital-product-import-guide.md`
2. the filled Blackwork product YAML
3. the accessible image links or uploaded images

Your task:
Return only valid Complete Product JSON for the Blackwork Create Digital CSV tool.

Workflow:
- Read the YAML.
- Visually analyze the featured image and product gallery images.
- If the images cannot be accessed from links, stop and ask Blackwork to upload them manually.
- If images are uploaded manually, match them to YAML media URLs by filename.
- Copy all YAML source fields exactly into `source_data`.
- Create creative fields in `creative_data`.
- Do not convert Dropbox URLs.
- Do not change media or ZIP URLs.
- Do not create CSV.
- Do not include Markdown.

Return exactly this JSON structure:

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

Important:
- `source_data` links must be copied exactly from YAML.
- `creative_data` must not contain URLs.
- `tags` must contain exactly 10 items.
- `product_subcategories` must use only allowed guide names.
- final output must be JSON only.
