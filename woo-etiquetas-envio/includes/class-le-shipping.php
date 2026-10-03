<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lógica de envíos, estados y hooks de carrito.
 */
final class Shipping {

	private static ?Shipping $instance = null;

	public static function get_instance(): Shipping {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_package_rates', [ $this, 'filter_express_methods' ], 10, 2 );
		add_action( 'woocommerce_before_cart', [ $this, 'display_mixed_cart_notice' ], 10 );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_mixed_cart_notice' ], 10 );
		add_action( 'woocommerce_cart_updated', [ $this, 'invalidate_cart_eval_cache' ], 10 );
	}

	/**
	 * Evalúa el estado del producto: local, backorder, o importado.
	 */
	public function get_product_state( \WC_Product $product ): string {
		$product_id    = $product->get_id();
		$stock         = $product->get_stock_quantity() ?: 0;
		$tag_ids       = $product->get_tag_ids();
		$tags_hash     = md5( wp_json_encode( $tag_ids ) );
		$import_tag_id = (int) Settings::get( 'import_tag_id', 0 );
		$is_backorder  = $product->is_on_backorder() ? 1 : 0;
		$manages_stock = $product->managing_stock() ? 1 : 0;

		// La key incluye TODO lo que afecta la decisión.
		$cache_key = "le_state_{$product_id}_{$stock}_{$tags_hash}_{$import_tag_id}_{$is_backorder}_{$manages_stock}";
		$state     = wp_cache_get( $cache_key, 'le_state' );

		if ( false !== $state ) {
			return (string) $state;
		}

		if ( $import_tag_id && has_term( $import_tag_id, 'product_tag', $product_id ) ) {
			$state = 'imported';
		} elseif ( $product->is_on_backorder() || ( $product->managing_stock() && $stock <= 0 ) ) {
			$state = 'backorder';
		} elseif ( ! $product->managing_stock() || $stock >= 1 ) {
			$state = 'local';
		} else {
			$state = 'local';
		}

		wp_cache_set( $cache_key, $state, 'le_state', 300 );
		return $state;
	}

	public function is_cart_mixed(): bool {
		$cart = WC()->cart;
		if ( ! $cart ) {
			return false;
		}

		$session_flag = WC()->session ? WC()->session->get( 'le_cart_is_mixed' ) : null;
		if ( null !== $session_flag && '' !== $session_flag ) {
			return (bool) $session_flag;
		}

		$mixed = false;
		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$state   = $this->get_product_state( $product );

			if ( 'backorder' === $state || 'imported' === $state ) {
				$mixed = true;
				break;
			}
		}

		if ( WC()->session ) {
			WC()->session->set( 'le_cart_is_mixed', $mixed ? '1' : '0' );
		}
		return $mixed;
	}

	public function filter_express_methods( array $rates, array $package ): array {
		if ( ! $this->is_cart_mixed() ) {
			return $rates;
		}

		$express_methods = Settings::get( 'express_methods', [] ) ?: [];
		if ( ! is_array( $express_methods ) ) {
			return $rates;
		}

		foreach ( $rates as $rate_id => $rate ) {
			if ( in_array( $rate_id, $express_methods, true ) ) {
				unset( $rates[ $rate_id ] );
			}
		}

		return $rates;
	}

	public function display_mixed_cart_notice(): void {
		if ( ! $this->is_cart_mixed() ) {
			return;
		}
		$notice = (string) Settings::get(
			'mixed_cart_notice',
			__( 'Tu carrito contiene artículos bajo pedido o importación. El envío express ha sido deshabilitado.', 'woo-etiquetas-envio' )
		);
		if ( '' !== $notice && ! wc_has_notice( $notice, 'notice' ) ) {
			wc_print_notice( wp_kses_post( $notice ), 'notice' );
		}
	}

	public function invalidate_cart_eval_cache(): void {
		if ( WC()->session ) {
			WC()->session->set( 'le_cart_is_mixed', '' );
		}
	}
}
