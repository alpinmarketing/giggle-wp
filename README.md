# Giggle WP

[![Latest Release](https://img.shields.io/github/v/release/alpinmarketing/giggle-wp?label=Download&color=blue)](https://github.com/alpinmarketing/giggle-wp/releases/latest)

> **IMPORTANT — License & Attribution**
> By using, forking, or integrating this plugin you fully accept the [LICENSE](LICENSE) terms.
> Any public use or derivative **must** include a visible dofollow link to **[www.alpinmarketing.at](https://www.alpinmarketing.at/)** in the plugin description, admin page, or public documentation. Removal of this requirement is not permitted.

A WordPress plugin that embeds [Giggle.tips](https://giggle.tips) experiences and events on your site using a Gutenberg block, with full schema.org JSON-LD semantic markup for rich search results.

## Features

- **Gutenberg block** — drag-and-drop `giggle-wp/events` block into any post or page
- **Schema.org JSON-LD** — auto-generates `Event` and `Product` structured data for Google rich results
- **Live API integration** — fetches experiences directly from the Giggle.tips API with 1-hour transient caching
- **Stale-while-revalidate** — zero TTFB on cache expiry; background refresh keeps content fresh
- **Filterable** — experience list filterable by stream, language, date range, and bookability
- **Theme-friendly** — CSS custom properties (`--giggle-cta-bg`, `--giggle-card-bg`, etc.) for easy styling
- **No build step** — vanilla JS with `wp.*` globals, works without Node.js tooling

## Requirements

- WordPress 6.9.4+
- PHP 8.3+
- A [Giggle.tips](https://giggle.tips) account with an API key

## Installation

1. Download the latest release ZIP from the [Releases](https://github.com/alpinmarketing/giggle-wp/releases/latest) page.
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Activate**.

Or clone this repository into your `wp-content/plugins/` directory:

```bash
git clone https://github.com/alpinmarketing/giggle-wp.git wp-content/plugins/giggle-wp
```

## Configuration

1. Go to **Settings → Giggle WP** in your WordPress admin.
2. Enter your **API Key** and **Hotel Code** (e.g. `giggletips`).
3. Save settings.

## Usage

1. Open any post or page in the block editor.
2. Add the **Giggle Events** block (`giggle-wp/events`).
3. In the block settings panel, select which experience streams to display.
4. Publish — the block renders server-side with live data and schema.org markup.

> **License requirement:** Any public use, fork, or derivative of this plugin requires a visible dofollow backlink to [www.alpinmarketing.at](https://www.alpinmarketing.at/) — see [LICENSE](LICENSE) for the full attribution terms.

## Filters

| Filter | Default | Description |
|---|---|---|
| `giggle_wp_cache_ttl` | `3600` | Cache lifetime in seconds |

## Development

No build step required. Edit PHP, JS, and CSS files directly.

```
giggle-wp/
├── giggle-wp.php          # Plugin bootstrap, version constant
├── includes/
│   ├── class-giggle-api.php       # API client + transient cache
│   ├── class-giggle-block.php     # Block registration + server render
│   └── class-giggle-settings.php  # Settings page
├── assets/
│   └── js/giggle-block-editor.js  # Gutenberg editor sidebar (vanilla JS)
├── templates/             # Front-end render templates
└── languages/             # i18n .pot file
```

To build a distribution ZIP:

```bash
bash build-zip.sh
# Output: dist/giggle-wp.zip
```

## Disclaimer

This plugin is provided **as is**, without warranty of any kind, express or implied. The author accepts no liability for any damages, data loss, security issues, or other consequences arising from the use or inability to use this plugin. Use at your own risk.

The plugin communicates with the external [Giggle.tips API](https://giggle.tips). The author is not responsible for the availability, accuracy, or content of that service.

## License

This plugin is released under the [GNU General Public License v2.0 or later](LICENSE).

Copyright (C) 2026 [ALPINMARKETING](https://www.alpinmarketing.at/)
