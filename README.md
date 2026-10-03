# Woo Etiquetas de Envío

> Una sola etiqueta sobre la portada (oferta, «Quedan N» o «Última unidad»), fecha estimada de envío y líneas grises de servicio para WooCommerce.

English: [README.en.md](README.en.md)

## Qué es
- **Modo «A · Mínima» (por defecto)**: la portada lleva **una sola etiqueta**, anclada a la imagen: oferta («−31 %») o existencias («Quedan N», «Última unidad»). En productos variables no se muestra «Quedan N» (el stock es por variación). Colores configurables.
- **Líneas de servicio** grises en la ficha, antes de la caja de envío: «⚡ Envío express» e «✈️ Importado».
- **Fecha estimada de envío** por producto, saltando fines de semana y festivos configurables, con mensajes distintos para stock local, importado y bajo pedido; también un estimador en carrito y checkout. Línea extra opcional bajo la caja (p. ej. recogida en tienda).
- Con A apagado: hasta 8 tipos de etiqueta (express, importación, oferta, últimas unidades, preventa, novedad, más vendido, envío gratis) con niveles, textos con marcadores (`{stock}`, `{pct}`, `{days}`…), 6 paletas, 7 estilos, posiciones y animaciones.
- Extras: restringir el envío express a una zona (países, estados, ciudades o prefijos postales), «avísame cuando llegue», «vendido hace X», consulta de ISBN (Open Library / Google Books), API REST con nonce y límite por IP.
- Compatible con HPOS, LiteSpeed, WP Rocket y carruseles (WP Carousel, Swiper, Slick, Owl).

## Para quién
Tiendas WooCommerce de libros u otros productos con stock limitado, importados y bajo pedido que quieren señales claras y poco ruido visual.

## Capturas
_(Pendientes: se guardarán en `docs/img/`.)_

## Requisitos
- WordPress 6.0+, PHP 8.1+, WooCommerce 7.0+.
- Un método de envío «express» ya creado en WooCommerce para usar el filtrado.

## Instalación
1. Copia la carpeta `woo-etiquetas-envio/` en `wp-content/plugins/` y actívalo (exige PHP 8.1).
2. Abre WooCommerce → Etiquetas y envío.
3. Purga tu caché de página al terminar.

## Configuración
- Pestaña Badges: activa o desactiva cada etiqueta, textos, colores, posición, umbrales (descuento mínimo, stock bajo/crítico).
- Pestañas de envío: métodos «express» (`le_express_methods`; cada tienda elige el suyo, p. ej. `flat_rate:3`), zona (Geo), plazos mínimo/máximo, festivos (`AAAA-MM-DD`).
- Pestaña Funciones: modo «A · Mínima» y sus colores.
- Por producto: pestaña «Etiquetas y envío» para forzar una etiqueta siempre visible u oculta y datos editoriales (lanzamiento, país de origen, días de importación).

### Ajustes nuevos en 1.0.0 (antes fijos en el código)

| Ajuste | Opción | Por defecto | Qué hace |
|---|---|---|---|
| Apagar modo «A · Mínima» | `le_etiquetas_a_off` | vacío (A encendido) | `1` vuelve a las etiquetas clásicas |
| Fondo de la etiqueta (modo A) | `le_a_badge_bg` | `#1f2937` | Color hexadecimal |
| Texto de la etiqueta (modo A) | `le_a_badge_text` | `#ffffff` | Color hexadecimal |
| Fondo del chip (modo clásico) | `le_chip_bg` | `#1f2937` | Color hexadecimal |
| Texto del chip (modo clásico) | `le_chip_text` | `#ffffff` | Color hexadecimal |
| Línea extra bajo la caja de envío | `le_pickup_text` | vacío (no se muestra) | Texto con HTML básico, p. ej. recogida en tienda |
| Países permitidos (Geo) | `le_geo_countries` | vacío (cualquiera) | Códigos ISO separados por coma |
| Estados permitidos (Geo) | `le_geo_states` | vacío (cualquiera) | Códigos de estado de WooCommerce |
| Ciudades permitidas (Geo) | `le_geo_cities` | vacío | Antes traía municipios de un área fija |
| Prefijos postales (Geo) | `le_geo_postcodes` | vacío | Antes traía prefijos de un área fija |

Con la restricción Geo activa y sin ciudades ni prefijos, solo cuentan país y estado (si están puestos); con todo vacío no se filtra nada.

### Filtros de «avísame cuando llegue»
El aviso se envía una sola vez por solicitud y la lista se borra. Para integrarlo con tu sistema de bajas:
- `le_notify_skip_email( bool $skip, string $email )`: `true` para no escribirle.
- `le_notify_footer_html( string $html, string $email )`: pie del correo.
- `le_notify_headers( string $headers, string $email )`: cabeceras (p. ej. `List-Unsubscribe`).
- Acción `le_notify_sent( string $email, int $product_id )` tras cada envío.

Se aceptan como máximo 500 correos por producto y solo para productos publicados que muestran el formulario.

## Interruptores y opciones
- `wp option update le_etiquetas_a_off 1` apaga el modo «A · Mínima» (o desde la pestaña Funciones); purga la caché.
- `le_max_badges` (1–8, por defecto 3) cuando A está apagado.
- Cada etiqueta tiene su interruptor `le_badge_*_enable`.

## Cómo deshacer
- `wp option delete le_etiquetas_a_off` y purgar caché para volver a A.
- Desactivar el plugin quita todo; al **desinstalar** se borran las opciones `le_*`, transients, metas de override y la lista de «avísame».

## Privacidad y terceros
- «Avísame» guarda correos en la meta `_le_notify_emails` del producto hasta el envío: decláralo en tu política de privacidad.
- La consulta de ISBN (solo en el administrador) llama a Open Library y Google Books, con caché de 30 días.
- Si eliges la fuente Inter, Manrope o JetBrains Mono en Presets, se carga desde Google Fonts; por defecto no se carga ninguna.

## Preguntas frecuentes

**¿Por qué una sola etiqueta?**  
Para reducir ruido: la portada solo avisa de oferta o de escasez; lo demás va como texto discreto.

**¿Funciona con cualquier tema?**  
Los badges se reposicionan por JS sobre la imagen del producto; si tu tema usa otro contenedor, ajusta la posición.

**¿Las fechas son exactas?**  
Son estimaciones que excluyen fines de semana y los festivos que configures.

**¿Manda datos fuera?**  
Solo la consulta de ISBN y, si la eliges, la fuente de Google Fonts.

## Limitaciones
- Textos en español; sin traducciones empaquetadas todavía (text domain `woo-etiquetas-envio`, carpeta `languages/`).
- Plugin grande (≈5.900 líneas): hay muchas opciones y poca documentación en pantalla.
- Cada cambio de stock renueva la caché de etiquetas y purga la caché de página completa (LiteSpeed, WP Rocket, W3TC); ver `SEGURIDAD.md`.
- Prefijos internos `le_` / `LE\` heredados de la versión privada.

## Seguridad
Ver [SEGURIDAD.md](SEGURIDAD.md).

## Licencia, autor y apoyo
- Licencia: GPL-2.0-or-later (ver `LICENSE`).
- Autor: **Andrés Valencia Tobón** · [GitHub](https://github.com/quijotevitruvio) · [LinkedIn](https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/)
- Apoya el proyecto: https://paypal.me/bibliotecologo
- Desarrollado para librosmedellin.com.
