=== Woo Etiquetas de Envío ===
Contributors: quijotevitruvio
Donate link: https://paypal.me/bibliotecologo
Tags: woocommerce, badges, shipping, delivery date, stock
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A single label on the product cover (sale, «N left» or «Last copy»), estimated shipping date and grey service lines for WooCommerce.

== Description ==

* **«A · Minimal» mode (default)**: the cover gets **one label only**, anchored to the image: sale («−31 %») or stock («N left», «Last copy»). Variable products never show «N left» (stock is per variation). Colours are configurable.
* **Grey service lines** on the product page, above the delivery box: «⚡ Express shipping» and «✈️ Imported».
* **Estimated shipping date** per product, skipping weekends and configurable holidays, with separate messages for local, imported and backordered stock; also a cart/checkout estimator. Optional extra line under the box (e.g. in-store pick-up).
* With A off: up to 8 label types (express, import, sale, low stock, pre-order, new, bestseller, free shipping) with tiers, placeholders (`{stock}`, `{pct}`, `{days}`…), 6 palettes, 7 styles, positions and animations.
* Extras: limit express shipping to an area (countries, states, cities or postcode prefixes), «notify me when back», «sold X ago», ISBN lookup (Open Library / Google Books), REST API with nonce and per-IP limit.
* HPOS-compatible; works with LiteSpeed, WP Rocket and carousels (WP Carousel, Swiper, Slick, Owl).

Author: Andrés Valencia Tobón - https://github.com/quijotevitruvio - https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/
Developed for librosmedellin.com. Support: https://paypal.me/bibliotecologo

== Installation ==

1. Copy the `woo-etiquetas-envio` folder into `wp-content/plugins/` and activate it (requires PHP 8.1).
2. Open WooCommerce → Etiquetas y envío.
3. Purge your page cache when done.

== Setup ==

* Badges tab: turn each label on/off, texts, colours, position, thresholds (minimum discount, low/critical stock).
* Shipping tabs: «express» methods (`le_express_methods`), area (`le_geo_countries`, `le_geo_states`, `le_geo_cities`, `le_geo_postcodes`, all empty by default), min/max lead times, holidays (`YYYY-MM-DD`), optional extra line (`le_pickup_text`).
* Features tab: «A · Minimal» mode (`le_etiquetas_a_off`) and its colours (`le_a_badge_bg`, `le_a_badge_text`, `le_chip_bg`, `le_chip_text`).
* Per product: «Etiquetas y envío» tab to force a label always on/off, plus editorial data (release date, origin country, import days).

== Frequently Asked Questions ==

= Why a single label? =
To cut noise: the cover only warns about a sale or scarcity; the rest is discreet text.

= Does it work with any theme? =
Badges are repositioned with JS over the product image; if your theme uses another container, adjust the position.

= Are the dates exact? =
They are estimates that skip weekends and the holidays you configure.

= Does it send data out? =
Only the ISBN lookup (Open Library and Google Books, admin only, cached for 30 days) and, if you choose one, a Google Fonts font. «Notify me» stores email addresses in product meta until the notice is sent.

== Limitations ==

* Texts are in Spanish; no bundled translations yet.
* Large plugin (≈5,900 lines): many options, little on-screen documentation.

== Changelog ==

= 1.0.0 =
* First public release.

== Upgrade Notice ==

= 1.0.0 =
First public release.
