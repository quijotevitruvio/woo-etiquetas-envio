<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restringe los métodos express a un área geográfica configurable
 * (países, estados/departamentos, ciudades y prefijos postales).
 * Por defecto no hay zona: con la restricción activa y las listas vacías no se filtra nada.
 */
final class Geo {

	private static ?Geo $instance = null;

	/** Ciudades permitidas por defecto (ninguna: se configuran en la pestaña Geo). */
	public const DEFAULT_CITIES = [];

	/** Prefijos postales permitidos por defecto (ninguno). */
	public const DEFAULT_POSTCODE_PREFIXES = [];

	public static function get_instance(): Geo {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_package_rates', [ $this, 'filter_express_by_geo' ], 20, 2 );
	}

	/**
	 * Si el cliente NO está en el área permitida, oculta los métodos express.
	 */
	public function filter_express_by_geo( array $rates, array $package ): array {
		if ( ! Settings::get( 'geo_restrict_enable', '0' ) ) {
			return $rates;
		}

		if ( $this->is_in_allowed_area( $package ) ) {
			return $rates;
		}

		$express_methods = (array) ( Settings::get( 'express_methods', [] ) ?: [] );
		foreach ( $rates as $rate_id => $rate ) {
			if ( in_array( $rate_id, $express_methods, true ) ) {
				unset( $rates[ $rate_id ] );
			}
		}

		return $rates;
	}

	public function is_in_allowed_area( array $package ): bool {
		$dest = $package['destination'] ?? [];
		$city = isset( $dest['city'] ) ? $this->normalize( (string) $dest['city'] ) : '';
		$pc   = isset( $dest['postcode'] ) ? preg_replace( '/\s+/', '', (string) $dest['postcode'] ) : '';

		// País / estado: listas configurables (le_geo_countries, le_geo_states). Vacías = cualquiera.
		$country   = strtoupper( (string) ( $dest['country'] ?? '' ) );
		$state     = strtoupper( (string) ( $dest['state'] ?? '' ) );
		$countries = $this->get_code_list( 'geo_countries' );
		$states    = $this->get_code_list( 'geo_states' );
		if ( $country && $countries && ! in_array( $country, $countries, true ) ) {
			return false;
		}
		if ( $state && $states && ! in_array( $state, $states, true ) ) {
			return false;
		}

		$cities = $this->get_allowed_cities();
		$prefixes = $this->get_allowed_postcode_prefixes();

		// Sin ciudades ni prefijos configurados no hay zona que exigir.
		if ( ! $cities && ! $prefixes ) {
			return true;
		}

		if ( $city && in_array( $city, $cities, true ) ) {
			return true;
		}
		if ( $pc !== '' ) {
			foreach ( $prefixes as $prefix ) {
				if ( 0 === strpos( $pc, $prefix ) ) {
					return true;
				}
			}
		}
		return false;
	}

	public function get_allowed_cities(): array {
		$raw = (string) Settings::get( 'geo_cities', implode( ',', self::DEFAULT_CITIES ) );
		$list = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		return array_map( [ $this, 'normalize' ], $list );
	}

	public function get_allowed_postcode_prefixes(): array {
		$raw = (string) Settings::get( 'geo_postcodes', implode( ',', self::DEFAULT_POSTCODE_PREFIXES ) );
		$list = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		return array_map( fn( $p ) => preg_replace( '/\s+/', '', $p ), $list );
	}

	/**
	 * Lista de códigos (país ISO o estado de WooCommerce) en mayúsculas, separados por coma.
	 *
	 * @return string[]
	 */
	private function get_code_list( string $key ): array {
		$raw = (string) Settings::get( $key, '' );
		return array_values( array_filter( array_map( fn( $c ) => strtoupper( trim( $c ) ), explode( ',', $raw ) ) ) );
	}

	private function normalize( string $s ): string {
		$s = trim( mb_strtolower( $s, 'UTF-8' ) );
		// Remove accents
		$s = remove_accents( $s );
		return $s;
	}
}
