# Woo Etiquetas de Envío

> A single label on the product cover (sale, «N left» or «Last copy»), estimated shipping date and grey service lines for WooCommerce.

Español: [README.md](README.md)

## What it is
- **«A · Minimal» mode (default)**: the cover gets **one label only**, anchored to the image: sale («−31 %») or stock («N left», «Last copy»). Variable products never show «N left» (stock is per variation). Colours are configurable.
- **Grey service lines** on the product page, above the delivery box: «⚡ Express shipping» and «✈️ Imported».
- **Estimated shipping date** per product, skipping weekends and configurable holidays, with separate messages for local, imported and backordered stock; also a cart/checkout estimator. Optional extra line under the box (e.g. in-store pick-up).
- With A off: up to 8 label types (express, import, sale, low stock, pre-order, new, bestseller, free shipping) with tiers, placeholders (`{stock}`, `{pct}`, `{days}`…), 6 palettes, 7 styles, positions and animations.
- Extras: limit express shipping to an area (countries, states, cities or postcode prefixes), «notify me when back», «sold X ago», ISBN lookup (Open Library / Google Books), REST API with nonce and per-IP limit.
- HPOS-compatible; works with LiteSpeed, WP Rocket and carousels (WP Carousel, Swiper, Slick, Owl).

## Who it is for
WooCommerce shops of books or other goods with limited, imported and made-to-order stock that want clear signals with little visual noise.

## Screenshots
_(Pending: they will live in `docs/img/`.)_

## Requirements
- WordPress 6.0+, PHP 8.1+, WooCommerce 7.0+.
- An «express» shipping method already created in WooCommerce to use the filtering.

## Installation
1. Copy the `woo-etiquetas-envio/` folder into `wp-content/plugins/` and activate it (requires PHP 8.1).
2. Open WooCommerce → Etiquetas y envío.
3. Purge your page cache when done.

## Setup
- Badges tab: turn each label on/off, texts, colours, position, thresholds (minimum discount, low/critical stock).
- Shipping tabs: «express» methods (`le_express_methods`; each shop picks its own, e.g. `flat_rate:3`), area (Geo), min/max lead times, holidays (`YYYY-MM-DD`).
- Features tab: «A · Minimal» mode and its colours.
- Per product: «Etiquetas y envío» tab to force a label always on/off, plus editorial data (release date, origin country, import days).

### New settings in 1.0.0 (previously hard-coded)

| Setting | Option | Default | What it does |
|---|---|---|---|
| Turn «A · Minimal» off | `le_etiquetas_a_off` | empty (A on) | `1` goes back to classic labels |
| Label background (A mode) | `le_a_badge_bg` | `#1f2937` | Hex colour |
| Label text (A mode) | `le_a_badge_text` | `#ffffff` | Hex colour |
| Chip background (classic) | `le_chip_bg` | `#1f2937` | Hex colour |
| Chip text (classic) | `le_chip_text` | `#ffffff` | Hex colour |
| Extra line under the delivery box | `le_pickup_text` | empty (hidden) | Basic HTML, e.g. in-store pick-up |
| Allowed countries (Geo) | `le_geo_countries` | empty (any) | Comma-separated ISO codes |
| Allowed states (Geo) | `le_geo_states` | empty (any) | WooCommerce state codes |
| Allowed cities (Geo) | `le_geo_cities` | empty | Used to ship with a fixed area |
| Postcode prefixes (Geo) | `le_geo_postcodes` | empty | Used to ship with a fixed area |

With the Geo restriction on and no cities or prefixes, only country and state count (when set); with everything empty nothing is filtered.

### «Notify me when back» filters
The notice is sent once per request and the list is then deleted. To plug in your own unsubscribe system:
- `le_notify_skip_email( bool $skip, string $email )`: `true` to skip that address.
- `le_notify_footer_html( string $html, string $email )`: email footer.
- `le_notify_headers( string $headers, string $email )`: headers (e.g. `List-Unsubscribe`).
- Action `le_notify_sent( string $email, int $product_id )` after each send.

At most 500 addresses per product, and only for published products that show the form.

## Switches and options
- `wp option update le_etiquetas_a_off 1` turns «A · Minimal» off (or use the Features tab); purge the cache.
- `le_max_badges` (1–8, default 3) when A is off.
- Each label has its own `le_badge_*_enable` switch.

## How to undo
- `wp option delete le_etiquetas_a_off` and purge the cache to go back to A.
- Deactivating the plugin removes everything; **uninstalling** deletes the `le_*` options, transients, override metas and the notify list.

## Privacy and third parties
- «Notify me» stores addresses in the product meta `_le_notify_emails` until the email is sent: mention it in your privacy policy.
- ISBN lookup (admin only) calls Open Library and Google Books, cached for 30 days.
- Choosing Inter, Manrope or JetBrains Mono in Presets loads it from Google Fonts; by default no font is loaded.

## FAQ

**Why a single label?**  
To cut noise: the cover only warns about a sale or scarcity; the rest is discreet text.

**Does it work with any theme?**  
Badges are repositioned with JS over the product image; if your theme uses another container, adjust the position.

**Are the dates exact?**  
They are estimates that skip weekends and the holidays you configure.

**Does it send data out?**  
Only the ISBN lookup and, if you pick it, the Google Fonts font.

## Limitations
- Texts in Spanish; no bundled translations yet (text domain `woo-etiquetas-envio`, `languages/` folder).
- Large plugin (≈5,900 lines): many options, little on-screen documentation.
- Every stock change renews the label cache and purges the whole page cache (LiteSpeed, WP Rocket, W3TC); see `SEGURIDAD.md`.
- Internal `le_` / `LE\` prefixes inherited from the private version.

## Security
See [SEGURIDAD.md](SEGURIDAD.md).

## License, author and support
- License: GPL-2.0-or-later (see `LICENSE`).
- Author: **Andrés Valencia Tobón** · [GitHub](https://github.com/quijotevitruvio) · [LinkedIn](https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/)
- Support the project: https://paypal.me/bibliotecologo
- Developed for librosmedellin.com.
