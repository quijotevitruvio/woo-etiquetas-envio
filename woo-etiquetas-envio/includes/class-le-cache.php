<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manejo de Caché e invalidación.
 * El enqueue del lazy-loader vive en Visual::enqueue_assets para evitar duplicados.
 */
final class Cache {

	private static ?Cache $instance = null;

	public static function get_instance(): Cache {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'woocommerce_product_set_stock', [ $this, 'invalidate_object_cache' ] );
		add_action( 'save_post_product', [ $this, 'invalidate_object_cache' ] );
		add_action( 'updated_post_meta', [ $this, 'on_meta_changed' ], 10, 4 );
	}

	public function on_meta_changed( $meta_id, $object_id, $meta_key, $meta_value ): void {
		if ( 0 === strpos( (string) $meta_key, '_le_override_' ) ) {
			$this->invalidate_object_cache( (int) $object_id );
		}
	}

	public function invalidate_object_cache( $product_id_or_product = 0 ): void {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'le_state' );
			wp_cache_flush_group( 'le_recent_sale' );
		}
		Settings::flush();
	}
}
