<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Badge dinámico: "Vendido hace X" usando datos reales de wc_get_orders.
 */
final class Recent_Sale {

	private const CACHE_GROUP = 'le_recent_sale';
	private const CACHE_TTL   = 1800; // 30 min

	private static ?Recent_Sale $instance = null;

	public static function get_instance(): Recent_Sale {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ! Settings::get( 'recent_sale_enable', '0' ) ) {
			return;
		}
		add_action( 'woocommerce_single_product_summary', [ $this, 'render' ], 12 );
		add_action( 'woocommerce_order_status_completed', [ $this, 'invalidate_for_order' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'invalidate_for_order' ] );
	}

	public function render(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$ts = $this->get_last_sale_timestamp( $product->get_id() );
		if ( ! $ts ) {
			return;
		}

		$max_age = (int) Settings::get( 'recent_sale_max_hours', '72' ) * HOUR_IN_SECONDS;
		if ( ( time() - $ts ) > $max_age ) {
			return;
		}

		$human = human_time_diff( $ts, time() );
		printf(
			'<div class="le-recent-sale" role="status">🛒 %s</div>',
			esc_html( sprintf(
				/* translators: %s: time ago, e.g. "2 hours" */
				__( 'Vendido hace %s', 'woo-etiquetas-envio' ),
				$human
			) )
		);
	}

	public function get_last_sale_timestamp( int $product_id ): ?int {
		$cache_key = "last_sale_{$product_id}";
		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached ? (int) $cached : null;
		}

		$ts = $this->query_last_sale_ts( $product_id );

		wp_cache_set( $cache_key, $ts, self::CACHE_GROUP, self::CACHE_TTL );
		return $ts ?: null;
	}

	/**
	 * Consulta directa a wp_woocommerce_order_itemmeta para encontrar la última orden
	 * que contiene este producto. Compatible con HPOS y CPT order storage.
	 */
	private function query_last_sale_ts( int $product_id ): int {
		global $wpdb;

		// Buscamos los últimos 20 order_item_id que contienen este producto (o variación)
		// y luego cargamos las órdenes para obtener fechas. wc_get_orders no soporta
		// product_id como filtro nativo, así que vamos a la tabla de items directamente.
		$item_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT oim.order_item_id
			 FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
			 WHERE oim.meta_key IN ('_product_id', '_variation_id')
			   AND oim.meta_value = %d
			 ORDER BY oim.order_item_id DESC
			 LIMIT 20",
			$product_id
		) );

		if ( empty( $item_ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );
		$order_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT order_id
			 FROM {$wpdb->prefix}woocommerce_order_items
			 WHERE order_item_id IN ($placeholders)",
			...array_map( 'intval', $item_ids )
		) );

		$latest_ts = 0;
		foreach ( $order_ids as $oid ) {
			$order = wc_get_order( (int) $oid );
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}
			if ( ! in_array( $order->get_status(), [ 'completed', 'processing' ], true ) ) {
				continue;
			}
			$date = $order->get_date_created();
			if ( $date ) {
				$ts = $date->getTimestamp();
				if ( $ts > $latest_ts ) {
					$latest_ts = $ts;
				}
			}
		}

		return $latest_ts;
	}

	/**
	 * Cuenta ventas (qty total) de un producto en los últimos N días.
	 * Cacheado 1 hora por producto+ventana. HPOS-safe vía wc_get_orders.
	 */
	public function get_sales_count_in_window( int $product_id, int $days ): int {
		$cache_key = "sales_{$product_id}_{$days}";
		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$count = $this->query_sales_count( $product_id, $days );
		wp_cache_set( $cache_key, $count, self::CACHE_GROUP, HOUR_IN_SECONDS );
		return $count;
	}

	private function query_sales_count( int $product_id, int $days ): int {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// Detectar HPOS para usar la tabla correcta de órdenes.
		$is_hpos = false;
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			$is_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		if ( $is_hpos ) {
			$sql = $wpdb->prepare(
				"SELECT COALESCE(SUM(qm.meta_value), 0)
				 FROM {$wpdb->prefix}woocommerce_order_itemmeta qm
				 INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = qm.order_item_id
				 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta pim ON pim.order_item_id = oi.order_item_id
				 INNER JOIN {$wpdb->prefix}wc_orders o ON o.id = oi.order_id
				 WHERE qm.meta_key = '_qty'
				   AND pim.meta_key IN ('_product_id', '_variation_id')
				   AND pim.meta_value = %s
				   AND o.status IN ('completed', 'processing')
				   AND o.date_created_gmt >= %s",
				(string) $product_id,
				$cutoff
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT COALESCE(SUM(qm.meta_value), 0)
				 FROM {$wpdb->prefix}woocommerce_order_itemmeta qm
				 INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = qm.order_item_id
				 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta pim ON pim.order_item_id = oi.order_item_id
				 INNER JOIN {$wpdb->posts} p ON p.ID = oi.order_id
				 WHERE qm.meta_key = '_qty'
				   AND pim.meta_key IN ('_product_id', '_variation_id')
				   AND pim.meta_value = %s
				   AND p.post_status IN ('wc-completed', 'wc-processing')
				   AND p.post_date_gmt >= %s",
				(string) $product_id,
				$cutoff
			);
		}

		return (int) $wpdb->get_var( $sql );
	}

	public function invalidate_for_order( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$pid = $item->get_product_id();
				wp_cache_delete( "last_sale_{$pid}", self::CACHE_GROUP );
			}
		}
	}
}
