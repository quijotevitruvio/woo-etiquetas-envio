<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notify-when-available: captura emails para productos backorder/imported
 * y envía correo cuando el stock se restaura.
 */
final class Notify {

	private const META_KEY = '_le_notify_emails';

	/** Tope de correos guardados por producto (evita inflar la meta o usarla para spam). */
	private const MAX_EMAILS = 500;

	private static ?Notify $instance = null;

	public static function get_instance(): Notify {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ! Settings::get( 'notify_enable', '1' ) ) {
			return;
		}
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_form' ], 35 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoint' ] );

		// Disparar correos cuando cambia el stock
		add_action( 'woocommerce_product_set_stock', [ $this, 'maybe_dispatch' ] );
		add_action( 'woocommerce_variation_set_stock', [ $this, 'maybe_dispatch' ] );
	}

	public function enqueue_assets(): void {
		if ( ! is_product() ) {
			return;
		}
		wp_enqueue_script(
			'woo-etiquetas-envio-notify',
			LE_PLUGIN_URL . 'assets/js/le-notify.js',
			[],
			LE_VERSION,
			true
		);
		wp_localize_script( 'woo-etiquetas-envio-notify', 'LENotify', [
			'restUrl' => esc_url_raw( rest_url( 'woo-etiquetas-envio/v1/notify' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => [
				'success' => __( '¡Listo! Te avisaremos cuando esté disponible.', 'woo-etiquetas-envio' ),
				'error'   => __( 'No se pudo registrar tu email. Intenta de nuevo.', 'woo-etiquetas-envio' ),
				'invalid' => __( 'Email inválido.', 'woo-etiquetas-envio' ),
			],
		] );
	}

	public function render_form(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$state = Shipping::get_instance()->get_product_state( $product );
		if ( ! in_array( $state, [ 'backorder', 'imported' ], true ) ) {
			return;
		}

		printf(
			'<form class="le-notify-form" data-product-id="%d">
				<label>%s</label>
				<div class="le-notify-row">
					<input type="email" required placeholder="%s" class="le-notify-email">
					<button type="submit">%s</button>
				</div>
				<p class="le-notify-msg" role="status" aria-live="polite"></p>
			</form>',
			(int) $product->get_id(),
			esc_html__( '¿Quieres que te avisemos cuando esté disponible?', 'woo-etiquetas-envio' ),
			esc_attr__( 'tu@email.com', 'woo-etiquetas-envio' ),
			esc_html__( 'Avísame', 'woo-etiquetas-envio' )
		);
	}

	public function register_endpoint(): void {
		register_rest_route( 'woo-etiquetas-envio/v1', '/notify', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_subscribe' ],
			'permission_callback' => [ Api::get_instance(), 'permission_check' ],
			'args'                => [
				'product_id' => [
					'required'          => true,
					'sanitize_callback' => 'absint',
					'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
				],
				'email'      => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_email',
					'validate_callback' => fn( $v ) => is_email( $v ),
				],
			],
		] );
	}

	public function handle_subscribe( \WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		$email      = sanitize_email( (string) $request->get_param( 'email' ) );

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Email inválido.', 'woo-etiquetas-envio' ), [ 'status' => 400 ] );
		}

		$product = wc_get_product( $product_id );
		// Solo productos publicados que de verdad muestran el formulario
		// (bajo pedido o importados): nada de borradores, privados ni productos en stock.
		if ( ! $product || 'publish' !== $product->get_status()
			|| ! in_array( Shipping::get_instance()->get_product_state( $product ), [ 'backorder', 'imported' ], true ) ) {
			return new \WP_Error( 'not_found', __( 'Producto no encontrado.', 'woo-etiquetas-envio' ), [ 'status' => 404 ] );
		}

		$email  = strtolower( $email );
		$emails = (array) get_post_meta( $product_id, self::META_KEY, true );
		$emails = array_filter( $emails );
		if ( count( $emails ) >= self::MAX_EMAILS ) {
			return new \WP_Error( 'list_full', __( 'No se pudo registrar tu email. Intenta de nuevo.', 'woo-etiquetas-envio' ), [ 'status' => 429 ] );
		}
		if ( ! in_array( $email, $emails, true ) ) {
			$emails[] = $email;
			update_post_meta( $product_id, self::META_KEY, $emails );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	public function maybe_dispatch( $product ): void {
		$product = is_numeric( $product ) ? wc_get_product( (int) $product ) : $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$stock = (int) ( $product->get_stock_quantity() ?: 0 );
		if ( $stock <= 0 ) {
			return;
		}

		$product_id = $product->get_id();
		$emails     = (array) get_post_meta( $product_id, self::META_KEY, true );
		$emails     = array_unique( array_filter( $emails ) );
		if ( empty( $emails ) ) {
			return;
		}

		// El aviso lo pidió el propio cliente con «Avísame»: es una solicitud
		// suya (no marketing), se envía una sola vez y la lista se borra después.
		// Integración opcional con un sistema de bajas propio mediante filtros:
		//  - le_notify_skip_email( bool $skip, string $email )  → true = no escribirle.
		//  - le_notify_footer_html( string $html, string $email ) → pie del correo.
		//  - le_notify_headers( string $headers, string $email )  → cabeceras extra.
		//  - acción le_notify_sent( string $email, int $product_id ) tras cada envío.
		$subject = sprintf(
			/* translators: %s: product name */
			__( '¡Ya está disponible: %s!', 'woo-etiquetas-envio' ),
			$product->get_name()
		);
		$url = get_permalink( $product_id );
		$body = sprintf(
			/* translators: 1: product name 2: product url */
			__( "Hola,\n\n\"%1\$s\", que esperabas, ya está disponible.\n\nPuedes verlo aquí:\n%2\$s\n\nGracias por tu paciencia.", 'woo-etiquetas-envio' ),
			$product->get_name(),
			$url
		);
		$html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#222;">'
			. wpautop( make_clickable( esc_html( $body ) ) );

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		foreach ( $emails as $email ) {
			if ( ! is_email( $email ) ) {
				continue;
			}
			if ( apply_filters( 'le_notify_skip_email', false, $email ) ) {
				continue; // se dio de baja: no se le escribe.
			}
			$motivo = sprintf(
				/* translators: %s: site name */
				__( 'porque pediste que te avisáramos cuando este producto volviera a estar disponible en %s', 'woo-etiquetas-envio' ),
				$site
			);
			$footer  = '<p style="font-size:12px;color:#666;">' . esc_html( sprintf( /* translators: %s: reason */ __( 'Recibes este correo %s. No te volveremos a escribir por este aviso.', 'woo-etiquetas-envio' ), $motivo ) ) . '</p>';
			$footer  = (string) apply_filters( 'le_notify_footer_html', $footer, $email );
			$headers = (string) apply_filters( 'le_notify_headers', "Content-Type: text/html; charset=UTF-8\r\n", $email );

			$enviado = wp_mail( $email, $subject, $html . $footer . '</div>', $headers );
			if ( $enviado ) {
				do_action( 'le_notify_sent', $email, (int) $product_id );
			}
		}

		// Limpia la lista (notificación ya enviada)
		delete_post_meta( $product_id, self::META_KEY );
	}
}
