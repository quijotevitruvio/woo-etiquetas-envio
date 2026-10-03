# Seguridad — Woo Etiquetas de Envío 1.0.0

Revisión del código publicado (2026-10-03). El plugin no imprime etiquetas de pedido ni maneja datos de remitente: «etiquetas» son los badges de producto. No lee direcciones ni teléfonos de clientes; de los pedidos solo cuenta ventas y fechas («vendido hace X», «más vendido»).

## Arreglado en este repositorio

| # | Hallazgo | Archivo | Gravedad | Arreglo |
|---|---|---|---|---|
| 1 | `/badges-by-url` público (`__return_true`) sin límite: cada petición acepta 100 URLs, consulta la base y crea transients; respondía con IDs de producto en `debug` y resolvía borradores o privados. | `includes/class-le-api.php` | Media (DoS/enumeración) | Límite por IP (60/min), solo productos publicados, `debug` solo con `manage_woocommerce`. |
| 2 | `/delivery-box/{id}` devolvía estado de stock y plazo de cualquier producto, también borradores o privados (el nonce `wp_rest` lo tiene cualquier visitante). | `includes/class-le-api.php` | Baja | Solo productos publicados (variaciones: también el padre). |
| 3 | `/notify` aceptaba correos para cualquier producto (privados, borradores, en stock) y sin tope: un tercero podía inscribir direcciones ajenas en masa. | `includes/class-le-notify.php` | Media-baja | Solo productos publicados que muestran el formulario; tope de 500 correos por producto; correos en minúsculas. |

## Pendiente (no trivial)

| # | Hallazgo | Archivo | Gravedad | Recomendación |
|---|---|---|---|---|
| 4 | «Avísame» sin doble confirmación: cualquiera puede inscribir el correo de otra persona (recibirá un único aviso). | `includes/class-le-notify.php` (`handle_subscribe`) | Baja | Doble opt-in o casilla de consentimiento; el límite por IP (30/min) ya frena el abuso. |
| 5 | La clave de caché de badges incluye la ciudad de envío del cliente (texto libre de la sesión): se pueden generar muchos transients distintos. | `includes/class-le-visual.php` (`get_badges_html`) | Baja | Normalizar a «dentro/fuera de zona» en vez de la ciudad literal. |
| 6 | Cada `update_option('le_badges_salt')` (en cada guardado de producto o cambio de stock, o sea en cada venta) dispara `on_option_update`, que purga TODA la caché de página. | `woo-etiquetas-envio.php` (`on_option_update` y `le_badges_cache_bump`) | Rendimiento | Excluir `le_badges_salt` de la purga global y purgar solo las URLs del producto. |
| 7 | Muchas opciones `le_badge_*` se registran sin `sanitize_callback`; la salida que se revisó se escapa (`esc_attr`/`esc_html`/`wp_kses_post`) y solo `manage_options` puede guardarlas. | `includes/class-le-admin.php` (`register_settings`) | Baja | Añadir sanitizador por tipo a todas. |
| 8 | Los avisos de variaciones: el formulario guarda los correos en el producto padre, pero `woocommerce_variation_set_stock` busca en la variación, así que en variables el aviso no sale. | `includes/class-le-notify.php` (`maybe_dispatch`) | Funcional | Buscar también en `get_parent_id()`. |

Sin hallazgos en: AJAX `le_apply_palette` (nonce + `manage_options`), `/isbn-lookup` (`edit_products`), guardado de metas del producto (dentro de `woocommerce_process_product_meta`, que ya verifica nonce y permisos), CORS (lista blanca, vacía por defecto), SQL (todas las consultas con datos usan `$wpdb->prepare`; las demás son literales), subida de archivos y redirecciones (no hay).
