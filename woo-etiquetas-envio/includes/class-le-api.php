<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endpoints REST y CORS scoped a woo-etiquetas-envio/v1.
 */
final class Api {

	private const NAMESPACE = 'woo-etiquetas-envio/v1';
	private const MAX_PRODUCT_IDS = 50;
	private const RATE_LIMIT_WINDOW = 60;
	private const RATE_LIMIT_MAX = 30;
	private const PUBLIC_RATE_LIMIT_MAX = 60;
	private const MAX_URLS = 100;

	private static ?Api $instance = null;

	public static function get_instance(): Api {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'rest_api_init', [ $this, 'extend_product_response' ] );
		add_filter( 'rest_pre_serve_request', [ $this, 'handle_cors' ], 10, 4 );
	}

	public function register_endpoints(): void {
		if ( ! Settings::get( 'api_enable_endpoint', '1' ) ) {
			return;
		}

		register_rest_route( self::NAMESPACE, '/cart-shipping', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'cart_shipping_callback' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'product_ids' => [
					'required'          => true,
					'validate_callback' => [ $this, 'validate_product_ids' ],
					'sanitize_callback' => [ $this, 'sanitize_product_ids' ],
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/delivery-box/(?P<id>\d+)', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'delivery_box_callback' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// Endpoint para carruseles que no disparan hooks (WP Carousel, etc.)
		// Resuelve URLs de producto a HTML de badges en lote.
		// Público (sin nonce) porque se usa también en visitantes anónimos.
		register_rest_route( self::NAMESPACE, '/badges-by-url', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'badges_by_url_callback' ],
			// Sin nonce (páginas en caché), pero con límite por IP propio: cada URL
			// dispara consultas y crea transients.
			'permission_callback' => [ $this, 'public_rate_check' ],
			'args'                => [
				'urls' => [
					'required'          => true,
					'validate_callback' => fn( $v ) => is_array( $v ) && count( $v ) <= self::MAX_URLS,
					'sanitize_callback' => fn( $v ) => array_slice( array_map( 'esc_url_raw', (array) $v ), 0, self::MAX_URLS ),
				],
				'context' => [
					'required'          => false,
					'sanitize_callback' => fn( $v ) => in_array( $v, [ 'catalog', 'single' ], true ) ? $v : 'catalog',
				],
			],
		] );
	}

	public function badges_by_url_callback( \WP_REST_Request $request ) {
		$urls    = (array) $request->get_param( 'urls' );
		$context = (string) ( $request->get_param( 'context' ) ?: 'catalog' );
		$out     = [];
		$debug   = [];

		foreach ( $urls as $url ) {
			$post_id = $this->resolve_url_to_product_id( $url );
			if ( ! $post_id ) {
				$out[ $url ] = '';
				$debug[ $url ] = 'no_product_resolved';
				continue;
			}
			$product = wc_get_product( $post_id );
			if ( ! $product instanceof \WC_Product || ! self::is_public_product( $product ) ) {
				$out[ $url ] = '';
				$debug[ $url ] = 'not_public_product';
				continue;
			}
			$html = Visual::get_instance()->get_badges_html( $product, $context );
			$out[ $url ] = $html;
			$debug[ $url ] = $html === '' ? "product_{$post_id}_no_badges_match" : "product_{$post_id}_ok";
		}

		$data = [ 'badges' => $out ];
		// El detalle de depuración solo para quien gestiona la tienda.
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$data['debug'] = $debug;
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Producto publicado (o variación activa de uno publicado).
	 * Evita que los endpoints públicos revelen datos de borradores o privados.
	 */
	public static function is_public_product( \WC_Product $product ): bool {
		if ( $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			return 'publish' === $product->get_status()
				&& $parent instanceof \WC_Product
				&& 'publish' === $parent->get_status();
		}
		return 'publish' === $product->get_status();
	}

	/**
	 * Límite por IP para el endpoint público de badges.
	 */
	public function public_rate_check( \WP_REST_Request $request ) {
		// WordPress vuelve a llamar a permission_callback al armar la cabecera
		// Allow (rest_send_allow_header): contar una sola vez por petición.
		static $resultado = null;
		if ( null !== $resultado ) {
			return $resultado;
		}
		$max       = (int) apply_filters( 'woo_etiquetas_envio_public_rate_limit_max', self::PUBLIC_RATE_LIMIT_MAX );
		$resultado = true;
		if ( ! $this->check_rate_limit( 'le_rlp_', max( 1, $max ) ) ) {
			$resultado = new \WP_Error( 'rest_too_many', __( 'Demasiadas solicitudes.', 'woo-etiquetas-envio' ), [ 'status' => 429 ] );
		}
		return $resultado;
	}

	/**
	 * Resuelve un URL a product_id. Más robusto que url_to_postid: prueba múltiples estrategias.
	 */
	private function resolve_url_to_product_id( string $url ): int {
		// Strategy 1: nativo de WP
		$id = (int) url_to_postid( $url );
		if ( $id ) return $id;

		// Strategy 2: parsear el path y usar el último segmento como slug del producto
		$path = parse_url( $url, PHP_URL_PATH );
		if ( ! $path ) return 0;
		$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		if ( empty( $segments ) ) return 0;
		$slug = (string) end( $segments );
		// Decodificar (URLs con tildes vienen como %C3%B3)
		$slug = urldecode( $slug );

		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post ) return (int) $post->ID;

		// Strategy 3: buscar por slug (post_name) en wp_posts directamente
		global $wpdb;
		$id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'product' AND post_status = 'publish' LIMIT 1",
			$slug
		) );
		return $id;
	}

	/**
	 * Permission check con nonce + rate limit por IP.
	 */
	public function permission_check( \WP_REST_Request $request ) {
		// WordPress vuelve a llamar a permission_callback al armar la cabecera
		// Allow (rest_send_allow_header) con la MISMA petición: se guarda el
		// resultado por petición para contar el límite una sola vez (si no, el
		// tope real de /cart-shipping y /delivery-box era la mitad).
		static $resultados = [];
		$clave = spl_object_id( $request );
		if ( isset( $resultados[ $clave ] ) ) {
			return $resultados[ $clave ];
		}
		if ( ! Settings::verify_rest_nonce( $request ) ) {
			$resultados[ $clave ] = new \WP_Error( 'rest_forbidden', __( 'Nonce inválido.', 'woo-etiquetas-envio' ), [ 'status' => 403 ] );
		} elseif ( ! $this->check_rate_limit() ) {
			$resultados[ $clave ] = new \WP_Error( 'rest_too_many', __( 'Demasiadas solicitudes.', 'woo-etiquetas-envio' ), [ 'status' => 429 ] );
		} else {
			$resultados[ $clave ] = true;
		}
		return $resultados[ $clave ];
	}

	/**
	 * IP real del visitante para los límites. Nunca X-Forwarded-For (se falsea).
	 * Si existe lm_ip_real() (Woo Tienda Segura: REMOTE_ADDR, o CF-Connecting-IP
	 * solo cuando la conexión viene de un rango de Cloudflare) se usa esa; si no,
	 * REMOTE_ADDR. Filtro `woo_etiquetas_envio_client_ip` para otros proxies.
	 */
	public static function client_ip(): string {
		$ip = function_exists( 'lm_ip_real' ) ? (string) lm_ip_real() : '';
		if ( '' === $ip && isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = (string) filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ); // phpcs:ignore
		}
		$ip = (string) apply_filters( 'woo_etiquetas_envio_client_ip', $ip );
		return '' !== $ip ? $ip : 'unknown';
	}

	private function check_rate_limit( string $prefix = 'le_rl_', int $max = self::RATE_LIMIT_MAX ): bool {
		$key = $prefix . md5( self::client_ip() );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return false;
		}
		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
		return true;
	}

	public function validate_product_ids( $param ): bool {
		if ( ! is_array( $param ) ) {
			return false;
		}
		if ( count( $param ) > self::MAX_PRODUCT_IDS ) {
			return false;
		}
		foreach ( $param as $id ) {
			if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
				return false;
			}
		}
		return true;
	}

	public function sanitize_product_ids( $param ): array {
		return array_slice( array_map( 'absint', (array) $param ), 0, self::MAX_PRODUCT_IDS );
	}

	public function delivery_box_callback( \WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'id' );
		$product = wc_get_product( $product_id );
		if ( ! $product || ! self::is_public_product( $product ) ) {
			return new \WP_Error( 'not_found', __( 'Producto no encontrado','woo-etiquetas-envio' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( [
			'html' => Visual::get_instance()->get_delivery_box_html( $product ),
		] );
	}

	public function cart_shipping_callback( \WP_REST_Request $request ) {
		$product_ids = (array) $request->get_param( 'product_ids' );
		$has_mixed   = false;
		$details     = [];
		$reason      = '';

		$shipping = Shipping::get_instance();

		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( (int) $pid );
			if ( $product ) {
				$state = $shipping->get_product_state( $product );
				$details[] = [ 'id' => (int) $pid, 'state' => $state ];

				if ( in_array( $state, [ 'backorder', 'imported' ], true ) ) {
					$has_mixed = true;
					$reason    = $state;
				}
			}
		}

		$blocked_methods = [];
		if ( $has_mixed ) {
			$blocked_methods = Settings::get( 'express_methods', [] ) ?: [];
		}

		return rest_ensure_response( [
			'express_available' => ! $has_mixed,
			'reason'            => $has_mixed ? $reason : '',
			'blocked_methods'   => $blocked_methods,
			'notice_html'       => $has_mixed ? wp_kses_post( (string) Settings::get( 'mixed_cart_notice', __( 'Carrito mixto.', 'woo-etiquetas-envio' ) ) ) : '',
			'products_detail'   => $details,
		] );
	}

	public function extend_product_response(): void {
		if ( ! Settings::get( 'api_extend_product', '1' ) ) {
			return;
		}

		register_rest_field( 'product', 'shipping_data', [
			'get_callback'    => [ $this, 'get_shipping_data_field' ],
			'update_callback' => null,
			'schema'          => null,
		] );
	}

	public function get_shipping_data_field( $object, $field_name, $request ) {
		$product = wc_get_product( $object['id'] );
		if ( ! $product ) {
			return null;
		}

		$state = Shipping::get_instance()->get_product_state( $product );
		$dates = Visual::get_instance()->get_delivery_dates( $state );

		return [
			'is_express_eligible' => 'local' === $state,
			'is_backorder'        => 'backorder' === $state,
			'is_imported'         => 'imported' === $state,
			'delivery_estimate'   => array_merge( $dates, [ 'state' => $state ] ),
			'badge_html'          => Visual::get_instance()->get_badges_html( $product, 'single' ),
			'box_html'            => Visual::get_instance()->get_delivery_box_html( $product ),
		];
	}

	/**
	 * CORS scoped: solo aplica a rutas woo-etiquetas-envio/v1 y solo a origins en whitelist.
	 */
	public function handle_cors( $value, $response, $request, $server ) {
		if ( ! $request instanceof \WP_REST_Request ) {
			return $value;
		}
		if ( 0 !== strpos( (string) $request->get_route(), '/' . self::NAMESPACE ) ) {
			return $value;
		}

		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$allowed = Settings::allowed_origins();

		if ( $origin && in_array( untrailingslashit( $origin ), $allowed, true ) ) {
			header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
			header( 'Vary: Origin' );
			header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );

			$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
			if ( 'OPTIONS' === $method ) {
				status_header( 200 );
				exit();
			}
		}

		return $value;
	}
}
