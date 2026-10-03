<?php
/**
 * Woo Etiquetas de Envío — desinstalación: — limpia opciones, post_meta y transients del plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Borra todas las opciones le_*
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'le\\_%'" );

// Borra transients de rate-limit y otros
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_le\\_%' OR option_name LIKE '\\_transient\\_timeout\\_le\\_%'" );

// Borra post_meta de overrides por producto
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_le\\_override\\_%'" );

// Borra metas de notificaciones "avísame cuando llegue" (feature 4)
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_le_notify_emails'" );

// Limpia caché de objetos
wp_cache_flush();
