<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper centralizado para opciones del plugin.
 * Cachea lecturas en memoria por request para evitar duplicar get_option().
 */
final class Settings {

	private static array $runtime_cache = [];

	/**
	 * Obtiene una opción con cache de request.
	 *
	 * @param string $key       Clave (sin prefijo le_).
	 * @param mixed  $default   Valor por defecto.
	 * @return mixed
	 */
	public static function get( string $key, $default = '' ) {
		$option_name = 0 === strpos( $key, 'le_' ) ? $key : 'le_' . $key;

		if ( array_key_exists( $option_name, self::$runtime_cache ) ) {
			return self::$runtime_cache[ $option_name ];
		}

		$value = get_option( $option_name, $default );
		self::$runtime_cache[ $option_name ] = $value;
		return $value;
	}

	public static function flush(): void {
		self::$runtime_cache = [];
	}

	/**
	 * Verifica nonce para endpoints protegidos.
	 */
	public static function verify_rest_nonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		return $nonce && wp_verify_nonce( (string) $nonce, 'wp_rest' );
	}

	/**
	 * Lista blanca de origins permitidos para CORS.
	 *
	 * @return string[]
	 */
	public static function allowed_origins(): array {
		$raw = (string) self::get( 'react_frontend_url', '' );
		$origins = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		return array_map( 'untrailingslashit', array_map( 'esc_url_raw', $origins ) );
	}

	/**
	 * Festivos en formato YYYY-MM-DD, separados por coma.
	 *
	 * @return string[]
	 */
	public static function holidays(): array {
		$raw = (string) self::get( 'est_holidays', '' );
		$days = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		return array_filter( $days, fn( $d ) => (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) );
	}
}
