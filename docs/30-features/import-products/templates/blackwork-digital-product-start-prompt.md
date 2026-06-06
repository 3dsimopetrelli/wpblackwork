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

Preferred final output:
Create and return a downloadable `.json` file.

Filename pattern:
`blackwork-digital-product-complete-{product-slug}.json`

If a downloadable file cannot be created:
Return exactly one fenced code block with language `json`.

Do not include:
- Markdown links
- explanations before or after the JSON
- comments
- trailing commas
- CSV
- normal prose JSON outside a code block

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
- never use Markdown links inside JSON.
- never output JSON as ordinary prose.

Final self-check before returning JSON

Before returning the JSON, silently verify all of the following. If any check fails, fix the JSON before returning it.

1. JSON validity
- Output is valid JSON.
- No Markdown.
- No explanations before or after the JSON.
- No trailing commas.
- No comments.
- No Markdown links.
- No string contains `[http`.
- No string contains `](http`.
- If a downloadable `.json` file can be created, prefer the downloadable file.
- If file creation is not possible, return exactly one fenced `json` code block.

2. `source_data` check
- Every `source_data` value was copied from the YAML.
- Media and ZIP URLs are unchanged from the YAML.
- Dropbox URLs still contain the original `rlkey` and `st` when present.
- No Dropbox URL was converted.
- No query parameter was removed.
- `product_gallery` is an array.
- `showcase_image_urls` is an array.
- Empty optional YAML fields remain empty strings.
- No missing YAML value is invented.

3. Image analysis check
- Featured image was visually analyzed.
- Product gallery images were visually analyzed.
- If Dropbox links were inaccessible, manually uploaded images were matched by filename.
- Uploaded images were used only for visual analysis.
- Creative fields were not generated from YAML text alone when mandatory images were unavailable.

4. `creative_data` check
- `post_title` is natural and product-ready.
- `post_content` is detailed, natural, and SEO-oriented.
- `post_excerpt` is concise.
- `tags` contains exactly 10 items.
- `tags` are internal Blackwork search and discovery terms, not generic marketing terms.
- `product_subcategories` contains up to 5 allowed values.
- `product_subcategories` contains at least 3 values when visually and contextually possible.
- `product_subcategories` uses only names from the allowed guide list.
- `creative_data` contains no URLs.
- `creative_data` contains no prices.
- `creative_data` contains no SKU.
- `creative_data` contains no CSV column names.
- `creative_data` contains no WordPress meta keys.
- `creative_data` contains no ZIP or download fields.

5. Responsibility boundary check
- ChatGPT did not create CSV.
- ChatGPT did not convert Dropbox URLs.
- ChatGPT did not create SKU.
- ChatGPT did not create parent or variation rows.
- ChatGPT did not write WordPress meta keys.
- The plugin remains responsible for CSV generation, Dropbox conversion, SKU generation, variation rows, media mapping, ZIP mapping, and validation.

Blackwork should not need to manually review every JSON. The JSON must pass this self-check before it is returned, and the WordPress plugin will perform the second validation before generating the CSV.
