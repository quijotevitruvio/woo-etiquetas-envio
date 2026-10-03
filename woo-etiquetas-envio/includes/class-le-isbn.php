<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campo ISBN en producto + lookup contra Open Library / Google Books.
 */
final class ISBN {

	private static ?ISBN $instance = null;

	public static function get_instance(): ISBN {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'render_field' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_field' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoint' ] );
	}

	public function render_field(): void {
		global $post;
		$value = (string) get_post_meta( $post->ID, '_le_isbn', true );
		?>
		<div class="options_group">
			<p class="form-field _le_isbn_field">
				<label for="_le_isbn">ISBN</label>
				<input type="text" class="short" id="_le_isbn" name="_le_isbn" value="<?php echo esc_attr( $value ); ?>" placeholder="978-...">
				<button type="button" class="button le-isbn-lookup" data-target="#_le_isbn">
					<?php esc_html_e( 'Rellenar desde ISBN', 'woo-etiquetas-envio' ); ?>
				</button>
				<span class="le-isbn-status" style="margin-left:8px;"></span>
			</p>
		</div>
		<?php
	}

	public function save_field( int $post_id ): void {
		if ( isset( $_POST['_le_isbn'] ) ) {
			$isbn = preg_replace( '/[^\dxX-]/', '', (string) wp_unslash( $_POST['_le_isbn'] ) );
			update_post_meta( $post_id, '_le_isbn', sanitize_text_field( $isbn ) );
		}
	}

	public function enqueue( $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		wp_enqueue_script(
			'woo-etiquetas-envio-isbn',
			LE_PLUGIN_URL . 'assets/js/le-isbn.js',
			[ 'jquery' ],
			LE_VERSION,
			true
		);
		wp_localize_script( 'woo-etiquetas-envio-isbn', 'LEIsbn', [
			'restUrl' => esc_url_raw( rest_url( 'woo-etiquetas-envio/v1/isbn-lookup' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => [
				'searching' => __( 'Buscando…', 'woo-etiquetas-envio' ),
				'notfound'  => __( 'ISBN no encontrado en Open Library ni Google Books.', 'woo-etiquetas-envio' ),
				'filled'    => __( 'Datos cargados. Revisa antes de guardar.', 'woo-etiquetas-envio' ),
			],
		] );
	}

	public function register_endpoint(): void {
		register_rest_route( 'woo-etiquetas-envio/v1', '/isbn-lookup', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'lookup' ],
			'permission_callback' => function () {
				return current_user_can( 'edit_products' );
			},
			'args'                => [
				'isbn' => [
					'required'          => true,
					'sanitize_callback' => fn( $v ) => preg_replace( '/[^\dxX]/', '', (string) $v ),
					'validate_callback' => fn( $v ) => (bool) preg_match( '/^\d{10}(\d{3})?[\dxX]?$/', preg_replace( '/[^\dxX]/', '', (string) $v ) ),
				],
			],
		] );
	}

	public function lookup( \WP_REST_Request $request ) {
		$isbn = (string) $request->get_param( 'isbn' );
		$cache_key = 'le_isbn_' . md5( $isbn );
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$data = $this->fetch_open_library( $isbn );
		if ( ! $data ) {
			$data = $this->fetch_google_books( $isbn );
		}
		if ( ! $data ) {
			return new \WP_Error( 'not_found', __( 'ISBN no encontrado.', 'woo-etiquetas-envio' ), [ 'status' => 404 ] );
		}

		set_transient( $cache_key, $data, DAY_IN_SECONDS * 30 );
		return rest_ensure_response( $data );
	}

	private function fetch_open_library( string $isbn ): ?array {
		$url = 'https://openlibrary.org/api/books?bibkeys=ISBN:' . rawurlencode( $isbn ) . '&format=json&jscmd=data';
		$res = wp_remote_get( $url, [ 'timeout' => 8 ] );
		if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		$key  = "ISBN:{$isbn}";
		if ( ! isset( $body[ $key ] ) ) {
			return null;
		}
		$b = $body[ $key ];
		return [
			'source'      => 'openlibrary',
			'title'       => (string) ( $b['title'] ?? '' ),
			'subtitle'    => (string) ( $b['subtitle'] ?? '' ),
			'authors'     => array_map( fn( $a ) => (string) ( $a['name'] ?? '' ), (array) ( $b['authors'] ?? [] ) ),
			'publisher'   => isset( $b['publishers'][0]['name'] ) ? (string) $b['publishers'][0]['name'] : '',
			'publish_date' => (string) ( $b['publish_date'] ?? '' ),
			'pages'       => (int) ( $b['number_of_pages'] ?? 0 ),
			'description' => (string) ( $b['notes'] ?? ( $b['excerpts'][0]['text'] ?? '' ) ),
			'cover'       => (string) ( $b['cover']['large'] ?? $b['cover']['medium'] ?? '' ),
			'subjects'    => array_map( fn( $s ) => (string) ( $s['name'] ?? '' ), (array) ( $b['subjects'] ?? [] ) ),
		];
	}

	private function fetch_google_books( string $isbn ): ?array {
		$url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . rawurlencode( $isbn );
		$res = wp_remote_get( $url, [ 'timeout' => 8 ] );
		if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( empty( $body['items'][0]['volumeInfo'] ) ) {
			return null;
		}
		$v = $body['items'][0]['volumeInfo'];
		return [
			'source'       => 'googlebooks',
			'title'        => (string) ( $v['title'] ?? '' ),
			'subtitle'     => (string) ( $v['subtitle'] ?? '' ),
			'authors'      => (array) ( $v['authors'] ?? [] ),
			'publisher'    => (string) ( $v['publisher'] ?? '' ),
			'publish_date' => (string) ( $v['publishedDate'] ?? '' ),
			'pages'        => (int) ( $v['pageCount'] ?? 0 ),
			'description'  => (string) ( $v['description'] ?? '' ),
			'cover'        => (string) ( $v['imageLinks']['thumbnail'] ?? '' ),
			'subjects'     => (array) ( $v['categories'] ?? [] ),
		];
	}
}
