# DinoMatic Meta

Metadata for the main pages and products of dinomatic.com.

- `meta.php` – the main metadata array (H1, title, description, intro, tagline, etc.) for all key pages and products.
- `build.php` – a script to crawl each URL in `meta.php` and save fresh meta data to a new file. Run `php build.php`.

**Field meanings:**

- `h1`: Main H1 tag for the page
- `intro`: Paragraph following the H1
- `title` and `description`: Used in meta/OG tags
- `tagline`: Short text for product cards
- `%%...%%`: Placeholder for styled `<span>` elements
