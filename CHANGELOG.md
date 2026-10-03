# Changelog

Formato: [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/). Versionado semántico.

## [1.0.1] - 2026-10-03
### Seguridad
- `/badges-by-url`: el límite por IP contaba dos veces cada petición (WordPress repite `permission_callback` para la cabecera `Allow`); ahora cuenta una. Tope filtrable con `woo_etiquetas_envio_public_rate_limit_max`.
- `/cart-shipping` y `/delivery-box` (con nonce): el mismo doble conteo dejaba el tope real en 15/min en vez de 30; `permission_check` guarda el resultado por petición y cuenta una vez.
- Los límites usan la IP real (`lm_ip_real()` si existe, si no `REMOTE_ADDR`; filtro `woo_etiquetas_envio_client_ip`), nunca `X-Forwarded-For`.

## [1.0.0] - 2026-10-03
### Añadido
- Primera versión pública: etiqueta única anclada a la portada (oferta / quedan N / última unidad), fecha estimada de envío, líneas de servicio y ajustes avanzados de etiquetas.
- Ajustes nuevos: `le_etiquetas_a_off`, `le_a_badge_bg`, `le_a_badge_text`, `le_chip_bg`, `le_chip_text`, `le_pickup_text`, `le_geo_countries`, `le_geo_states`.
- Filtros `le_notify_skip_email`, `le_notify_footer_html`, `le_notify_headers` y acción `le_notify_sent` para el aviso de disponibilidad.

### Cambiado
- Nombre, carpeta, archivo principal, text domain y espacio REST pasan a `woo-etiquetas-envio` (antes «Libros Express», `libros-express`).
- La zona Geo ya no trae ciudades, prefijos, país ni estado por defecto.
- La dirección de recogida, los colores de marca y el interruptor `lm_etiquetas_a_off` dejan de estar fijos en el código.
- «Avísame» funciona sin plugins privados (antes dependía del sistema de bajas de la tienda).

### Seguridad
- `/badges-by-url`: límite por IP, solo productos publicados y depuración solo para gestores de la tienda.
- `/delivery-box/{id}` y `/notify`: solo productos publicados; `/notify` con tope de 500 correos por producto.

Este historial empieza en 1.0.0; el desarrollo anterior fue interno de librosmedellin.com.

---

## [1.0.0] - 2026-10-03 (English)
- First public release: single cover label (sale / N left / last copy), estimated shipping date, service lines and advanced label settings.
- Shop-specific data (pick-up address, brand colours, geographic area) moved to empty or neutral settings; renamed to `woo-etiquetas-envio`.
- Hardened public REST endpoints (rate limit, published products only, capped notify list).
