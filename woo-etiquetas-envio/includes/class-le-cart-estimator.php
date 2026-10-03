<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Muestra una estimación consolidada de entrega para todo el carrito.
 * Toma el max(estimado) entre todos los items.
 */
final class Cart_Estimator {

	private static ?Cart_Estimator $instance = null;

	public static function get_instance(): Cart_Estimator {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ! Settings::get( 'cart_estimator_enable', '1' ) ) {
			return;
		}
		add_action( 'woocommerce_cart_totals_after_order_total', [ $this, 'render' ] );
		add_action( 'woocommerce_review_order_after_order_total', [ $this, 'render' ] );
	}

	public function render(): void {
		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		$worst_state = 'local';
		$priority    = [ 'local' => 0, 'backorder' => 1, 'imported' => 2 ];

		foreach ( $cart->get_cart() as $item ) {
			if ( ! isset( $item['data'] ) || ! $item['data'] instanceof \WC_Product ) {
				continue;
			}
			$state = Shipping::get_instance()->get_product_state( $item['data'] );
			if ( ( $priority[ $state ] ?? 0 ) > ( $priority[ $worst_state ] ?? 0 ) ) {
				$worst_state = $state;
			}
		}

		$dates = Visual::get_instance()->get_delivery_dates( $worst_state );

		$bg   = (string) Settings::get( 'est_bg_color', '#1a1a1a' );
		$txt  = (string) Settings::get( 'est_text_color', '#ffffff' );

		$labels = [
			'local'     => __( 'Tu pedido completo llega entre', 'woo-etiquetas-envio' ),
			'backorder' => __( 'Por contener artículos bajo pedido, todo el envío llegará entre', 'woo-etiquetas-envio' ),
			'imported'  => __( 'Por contener artículos importados, todo el envío llegará entre', 'woo-etiquetas-envio' ),
		];
		$label = $labels[ $worst_state ] ?? $labels['local'];

		printf(
			'<tr class="le-cart-estimator"><td colspan="2"><div class="le-delivery-box" style="background-color:%1$s; border-color:%1$s; color:%2$s;"><div class="le-delivery-box__icon">🚚</div><div class="le-delivery-box__content"><div class="le-delivery-box__title" style="opacity:.7">%3$s</div><div class="le-delivery-box__dates"><strong>%4$s</strong> %5$s <strong>%6$s</strong></div></div></div></td></tr>',
			esc_attr( $bg ),
			esc_attr( $txt ),
			esc_html( $label ),
			esc_html( $dates['min_date'] ),
			esc_html__( 'y', 'woo-etiquetas-envio' ),
			esc_html( $dates['max_date'] )
		);
	}
}
