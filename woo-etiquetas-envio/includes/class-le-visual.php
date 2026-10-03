<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encargada de renderizar badges, caja de estimación y shortcodes.
 */
final class Visual {

	private static ?Visual $instance = null;

	public static function get_instance(): Visual {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( Settings::get( 'badge_express_catalog', '1' ) ) {
			add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'render_badges_catalog' ], 10 );

			// Hooks de WP Carousel (Shapedplugin) — cubren las variantes free + Pro
			add_action( 'sp_wpcp_woo_after_product_image', [ $this, 'render_badges_catalog' ], 10 );
			add_action( 'sp_wpcp_woo_before_product_title', [ $this, 'render_badges_catalog' ], 10 );
			add_action( 'wpcp_woocommerce_before_shop_loop_item_title', [ $this, 'render_badges_catalog' ], 10 );
			add_action( 'wpcp_after_product_image', [ $this, 'render_badges_catalog' ], 10 );
		}
		if ( Settings::get( 'badge_express_single', '1' ) ) {
			add_action( 'woocommerce_before_single_product_summary', [ $this, 'render_badges_single' ], 10 );
		}

		// Modo «A · Mínima»: «Envío express» e «Importado» salen de la portada y pasan
		// a una línea gris de texto justo antes de la caja de envío.
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_service_lines' ], 14 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_delivery_box' ], 15 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Ocultar badge de oferta nativo solo si nuestro badge de oferta está activo
		// y el producto no tiene override force_off.
		if ( Settings::get( 'badge_sale_enable', '1' ) ) {
			add_filter( 'woocommerce_sale_flash', [ $this, 'maybe_hide_sale_flash' ], 99, 3 );
		}
	}

	public function maybe_hide_sale_flash( $html, $post, $product ) {
		if ( $product instanceof \WC_Product ) {
			$override = get_post_meta( $product->get_id(), '_le_override_sale', true );
			if ( 'force_off' === $override ) {
				return $html;
			}
		}
		return '';
	}

	public function enqueue_assets(): void {
		wp_enqueue_style( 'woo-etiquetas-envio-styles', LE_PLUGIN_URL . 'assets/css/le-styles.css', [], LE_VERSION );
		wp_enqueue_script(
			'woo-etiquetas-envio-badges',
			LE_PLUGIN_URL . 'assets/js/le-badges.js',
			[ 'jquery' ],
			LE_VERSION,
			true
		);
		wp_localize_script( 'woo-etiquetas-envio-badges', 'LECarousel', [
			'restUrl' => esc_url_raw( rest_url( 'woo-etiquetas-envio/v1/badges-by-url' ) ),
		] );

		// Variations support: re-render delivery box on found_variation
		if ( is_product() ) {
			wp_enqueue_script(
				'woo-etiquetas-envio-lazy',
				LE_PLUGIN_URL . 'assets/js/le-lazy.js',
				[ 'jquery' ],
				LE_VERSION,
				true
			);
			wp_localize_script( 'woo-etiquetas-envio-lazy', 'LEData', [
				'restUrl' => esc_url_raw( rest_url( 'woo-etiquetas-envio/v1/delivery-box/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			] );
		}
	}

	/**
	 * Calcula el ranking aproximado de un producto por ventas globales (caché 1h).
	 * Retorna 1-N donde 1 es el más vendido.
	 */
	private function compute_bestseller_rank( int $product_id, int $sales ): int {
		$cache_key = "le_rank_{$product_id}";
		$cached = wp_cache_get( $cache_key, 'le_recent_sale' );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;
		// Cuenta cuántos productos tienen MÁS ventas que este → su ranking es esa cuenta + 1.
		$rank = 1 + (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE meta_key = 'total_sales' AND CAST(meta_value AS UNSIGNED) > %d",
			$sales
		) );

		wp_cache_set( $cache_key, $rank, 'le_recent_sale', HOUR_IN_SECONDS );
		return $rank;
	}

	/**
	 * Prioridad por defecto de cada badge (mayor = se muestra primero si hay límite).
	 * Filtrable vía 'le_badge_priorities'.
	 */
	private function get_priorities(): array {
		return apply_filters( 'le_badge_priorities', [
			'sale'         => 9,
			'lowstock'     => 8,
			'preorder'     => 7,
			'import'       => 6,
			'express'      => 5,
			'bestseller'   => 5,
			'new'          => 4,
			'freeshipping' => 3,
		] );
	}

	/**
	 * Matriz de exclusión: si la clave está activa, se ocultan los badges del array.
	 * Filtrable vía 'le_badge_exclusions'.
	 */
	private function get_exclusions(): array {
		return apply_filters( 'le_badge_exclusions', [
			'preorder'   => [ 'lowstock' ],
			'import'     => [ 'express', 'lowstock' ],
			'bestseller' => [ 'new' ],
		] );
	}

	/**
	 * HTML de badges con caché (transient 6 h). La llave incluye la ciudad
	 * del cliente (el badge de envío gratis varía por zona) y una "sal"
	 * global que se renueva al guardar productos, stock o ajustes — así la
	 * invalidación es instantánea sin rastrear llaves una por una.
	 */
	/**
	 * Etiquetas «A · Mínima»: una sola etiqueta por portada
	 * (oferta «−31 %» o existencias «Quedan N» / «Última unidad»); «Envío express»
	 * e «Importado» ya no son etiquetas sino líneas grises (get_service_lines_html).
	 * Apagar: wp option update le_etiquetas_a_off 1 (+ purgar la caché de página).
	 */
	public static function etiquetas_a(): bool {
		return ! get_option( 'le_etiquetas_a_off' );
	}

	/**
	 * Color hexadecimal de un ajuste (le_<key>); si está vacío o no es válido, el por defecto.
	 */
	public static function color_opt( string $key, string $default ): string {
		$v = sanitize_hex_color( (string) Settings::get( $key, '' ) );
		return $v ? $v : $default;
	}

	/**
	 * Líneas grises de servicio («⚡ Envío express», «✈️ Importado») para la ficha y la
	 * tarjeta de otros plugins (p. ej. un buscador). Devuelve '' si no aplica o si A está apagado.
	 */
	public function get_service_lines_html( \WC_Product $product, string $context = 'single' ): string {
		return $this->get_badges_html( $product, $context, 'lines' );
	}

	public function render_service_lines(): void {
		global $product;
		if ( ! $product instanceof \WC_Product || $product->is_virtual() ) {
			return;
		}
		echo $this->get_service_lines_html( $product, 'single' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function get_badges_html( \WC_Product $product, string $context = 'catalog', string $mode = 'labels' ): string {
		$city = '';
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$city = strtolower( (string) WC()->customer->get_shipping_city() );
		}
		$salt = (string) get_option( 'le_badges_salt', '1' );
		$key  = 'le_b_' . md5( $salt . '|' . $context . '|' . $product->get_id() . '|' . $city . '|' . $mode . '|' . ( self::etiquetas_a() ? 'a1' : 'a0' ) );

		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return (string) $cached;
		}
		$html = $this->build_badges_html( $product, $context, $mode );
		set_transient( $key, $html, 6 * HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * HTML de múltiples badges con sistema de intents → exclusiones → prioridad → max.
	 */
	public function build_badges_html( \WC_Product $product, string $context = 'catalog', string $mode = 'labels' ): string {
		$a_mode = self::etiquetas_a();
		if ( ! $a_mode && 'lines' === $mode ) {
			return ''; // las líneas solo existen con A encendido
		}
		$check_badge = function ( $badge_id, $natural_condition ) use ( $product ) {
			$override = get_post_meta( $product->get_id(), "_le_override_{$badge_id}", true );
			if ( 'force_on' === $override ) return true;
			if ( 'force_off' === $override ) return false;
			return $natural_condition;
		};

		// PHASE 1 — recolectar intents (qué badges DEBERÍAN mostrarse y con qué metadata)
		$intents = [];
		$pid     = $product->get_id();

		// --- Sale (con umbral, tiers, schedule, min_stock, exclude_preorder) ---
		$min_discount = (int) Settings::get( 'badge_sale_min_discount', '10' );
		$sale_logic   = Settings::get( 'badge_sale_enable', '1' ) && $product->is_on_sale();

		// Schedule de fechas (campañas)
		$schedule_from = (string) Settings::get( 'badge_sale_schedule_from', '' );
		$schedule_to   = (string) Settings::get( 'badge_sale_schedule_to', '' );
		if ( $schedule_from || $schedule_to ) {
			$now_ts = time();
			if ( $schedule_from && strtotime( $schedule_from ) > $now_ts ) $sale_logic = false;
			if ( $schedule_to && strtotime( $schedule_to ) < $now_ts ) $sale_logic = false;
		}

		// Stock mínimo para sale
		$sale_min_stock = (int) Settings::get( 'badge_sale_min_stock', '0' );
		if ( $sale_logic && $sale_min_stock > 0 && $product->managing_stock() ) {
			if ( (int) $product->get_stock_quantity() < $sale_min_stock ) $sale_logic = false;
		}

		if ( $check_badge( 'sale', $sale_logic ) ) {
			$discount_pct = 0;
			if ( $product->is_type( 'variable' ) ) {
				$prices  = $product->get_variation_prices();
				$max_pct = 0;
				foreach ( $prices['regular_price'] as $key => $reg ) {
					$sale = $prices['sale_price'][ $key ] ?? 0;
					if ( $sale && $sale < $reg && $reg > 0 ) {
						$pct = (int) round( ( ( $reg - $sale ) / $reg ) * 100 );
						if ( $pct > $max_pct ) $max_pct = $pct;
					}
				}
				$discount_pct = $max_pct;
			} else {
				$reg  = (float) $product->get_regular_price();
				$sale = (float) $product->get_sale_price();
				if ( $reg > 0 && $sale > 0 && $sale < $reg ) {
					$discount_pct = (int) round( ( ( $reg - $sale ) / $reg ) * 100 );
				}
			}
			$override = get_post_meta( $pid, '_le_override_sale', true );
			if ( $discount_pct >= $min_discount || 'force_on' === $override ) {
				// Determinar tier según %
				$t1_max = (int) Settings::get( 'badge_sale_tier1_max', '25' );
				$t2_max = (int) Settings::get( 'badge_sale_tier2_max', '50' );
				if ( $discount_pct <= $t1_max )      $sale_tier = 1;
				elseif ( $discount_pct <= $t2_max )  $sale_tier = 2;
				else                                  $sale_tier = 3;
				$intents['sale'] = [ 'discount_pct' => $discount_pct, 'tier' => $sale_tier ];
			}
		}

		// --- Free shipping (threshold + solo zona Geo + payment methods) ---
		$free_logic     = false;
		$free_threshold = (float) Settings::get( 'badge_freeshipping_threshold', '0' );
		if ( Settings::get( 'badge_freeshipping_enable', '0' ) ) {
			$shipping_class = $product->get_shipping_class();
			$has_class      = false !== strpos( $shipping_class, 'gratis' );
			$has_tag        = has_term( 'envio-gratis', 'product_tag', $pid ) || has_term( 'envío-gratis', 'product_tag', $pid );
			if ( $has_class || $has_tag || $free_threshold > 0 ) {
				$free_logic = true;
			}
		}
		// «Solo zona Geo» (opción le_badge_freeshipping_amva_only): solo aplica cuando hay destino
		// conocido del cliente. Si está activo y el visitante NO está en la zona de la pestaña Geo, ocultar.
		if ( $free_logic && Settings::get( 'badge_freeshipping_amva_only', '0' ) && function_exists( 'WC' ) && WC()->customer ) {
			$customer_city     = strtolower( (string) WC()->customer->get_shipping_city() );
			$customer_postcode = (string) WC()->customer->get_shipping_postcode();
			if ( $customer_city || $customer_postcode ) {
				if ( class_exists( __NAMESPACE__ . '\\Geo' ) ) {
					$pkg_dest = [ 'destination' => [ 'city' => $customer_city, 'postcode' => $customer_postcode, 'country' => (string) WC()->customer->get_shipping_country(), 'state' => (string) WC()->customer->get_shipping_state() ] ];
					if ( ! Geo::get_instance()->is_in_allowed_area( $pkg_dest ) ) {
						$free_logic = false;
					}
				}
			}
		}
		if ( $check_badge( 'freeshipping', $free_logic ) ) {
			$intents['freeshipping'] = [ 'threshold' => $free_threshold ];
		}

		// --- Express (con stock min, cutoff, peso, categorías excluidas) ---
		$state         = Shipping::get_instance()->get_product_state( $product );
		$express_logic = 'local' === $state && Settings::get( 'badge_express_enable', '1' );

		if ( $express_logic ) {
			// Stock mínimo
			$exp_min_stock = (int) Settings::get( 'badge_express_min_stock', '0' );
			if ( $exp_min_stock > 0 && $product->managing_stock() && (int) $product->get_stock_quantity() < $exp_min_stock ) {
				$express_logic = false;
			}
			// Peso máximo
			$max_weight = (float) Settings::get( 'badge_express_max_weight', '0' );
			if ( $express_logic && $max_weight > 0 ) {
				$pweight = (float) $product->get_weight();
				if ( $pweight > 0 && $pweight > $max_weight ) $express_logic = false;
			}
			// Categorías excluidas
			$excluded_cats = (array) Settings::get( 'badge_express_excluded_cats', [] );
			if ( $express_logic && ! empty( $excluded_cats ) ) {
				$prod_cats = $product->get_category_ids();
				if ( array_intersect( array_map( 'intval', $excluded_cats ), array_map( 'intval', $prod_cats ) ) ) {
					$express_logic = false;
				}
			}
		}

		// Cutoff time (modifica texto, no la lógica de mostrar)
		$express_cutoff_active = false;
		$cutoff_time = (string) Settings::get( 'badge_express_cutoff_time', '' );
		if ( $express_logic && preg_match( '/^\d{2}:\d{2}$/', $cutoff_time ) ) {
			$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
			$now = new \DateTime( 'now', $tz );
			$cut = \DateTime::createFromFormat( 'Y-m-d H:i', $now->format( 'Y-m-d' ) . ' ' . $cutoff_time, $tz );
			if ( $cut && $now < $cut ) $express_cutoff_active = true;
		}

		if ( $check_badge( 'express', $express_logic ) ) {
			$intents['express'] = [ 'cutoff_active' => $express_cutoff_active, 'cutoff_time' => $cutoff_time ];
		}

		// --- Imported (con país de origen) ---
		$import_logic = 'imported' === $state && Settings::get( 'badge_import_enable', '1' );
		if ( $check_badge( 'import', $import_logic ) ) {
			$country  = (string) ( get_post_meta( $pid, '_le_country_origin', true ) ?: Settings::get( 'badge_import_country', '' ) );
			$flag     = (string) ( get_post_meta( $pid, '_le_country_emoji', true ) ?: Settings::get( 'badge_import_country_emoji', '' ) );
			$intents['import'] = [ 'country' => $country, 'flag' => $flag ];
		}

		// --- Lowstock (3 tiers + exclude_sale) ---
		$lowstock_logic = false;
		$lowstock_count = 0;
		$lowstock_tier  = 'normal'; // critical | normal | few
		if ( Settings::get( 'badge_lowstock_enable', '0' ) && $product->managing_stock() ) {
			$stock     = (int) $product->get_stock_quantity();
			$threshold = (int) Settings::get( 'badge_lowstock_threshold', '3' );
			$few_th    = (int) Settings::get( 'badge_lowstock_few_threshold', '5' );
			$crit_th   = (int) Settings::get( 'badge_lowstock_critical_threshold', '1' );
			$max_show  = max( $threshold, $few_th );
			if ( $stock > 0 && $stock <= $max_show ) {
				$lowstock_logic = true;
				$lowstock_count = $stock;
				if ( $stock <= $crit_th )      $lowstock_tier = 'critical';
				elseif ( $stock <= $threshold ) $lowstock_tier = 'normal';
				else                            $lowstock_tier = 'few';

				// Filtro opcional: solo si hubo venta reciente
				$require_recent = (bool) Settings::get( 'badge_lowstock_require_recent', '0' );
				if ( $require_recent && class_exists( __NAMESPACE__ . '\\Recent_Sale' ) ) {
					$recent_window = (int) Settings::get( 'badge_lowstock_recent_window_days', '60' );
					$ts = Recent_Sale::get_instance()->get_last_sale_timestamp( $pid );
					if ( ! $ts || ( time() - $ts ) > ( $recent_window * DAY_IN_SECONDS ) ) {
						$lowstock_logic = false;
					}
				}

				// Excluir si está en oferta (mutex narrativo)
				if ( $lowstock_logic && Settings::get( 'badge_lowstock_exclude_sale', '0' ) && $product->is_on_sale() ) {
					$lowstock_logic = false;
				}
			}
		}
		if ( $check_badge( 'lowstock', $lowstock_logic ) ) {
			$intents['lowstock'] = [ 'stock' => $lowstock_count, 'tier' => $lowstock_tier ];
		}

		// --- Preorder (countdown + descuento + auto-remove) ---
		$preorder_logic = false;
		$release_date   = (string) get_post_meta( $pid, '_le_release_date', true );
		$days_to_release = null;
		if ( Settings::get( 'badge_preorder_enable', '0' ) ) {
			if ( ( $product->managing_stock() && $product->backorders_allowed() && $product->get_stock_quantity() <= 0 ) || has_term( 'preventa', 'product_tag', $pid ) ) {
				$preorder_logic = true;
			}
			// Si tiene fecha de lanzamiento futura, también califica
			if ( $release_date ) {
				$rts = strtotime( $release_date );
				if ( $rts ) {
					$days_to_release = (int) ceil( ( $rts - time() ) / DAY_IN_SECONDS );
					// Auto-remove al pasar la fecha
					if ( Settings::get( 'badge_preorder_auto_remove_after', '1' ) && $days_to_release < 0 ) {
						$preorder_logic = false;
					} elseif ( $days_to_release >= 0 ) {
						$preorder_logic = true;
					}
				}
			}
		}
		if ( $check_badge( 'preorder', $preorder_logic ) ) {
			$pre_disc_global = (int) Settings::get( 'badge_preorder_discount_pct', '0' );
			$pre_disc_local  = (int) ( get_post_meta( $pid, '_le_preorder_discount', true ) ?: 0 );
			$pre_disc        = $pre_disc_local > 0 ? $pre_disc_local : $pre_disc_global;
			$intents['preorder'] = [
				'days_to_release' => $days_to_release,
				'show_countdown'  => (bool) Settings::get( 'badge_preorder_show_countdown', '1' ),
				'discount'        => $pre_disc,
			];
		}

		// --- New (con first_published meta + tier estreno + categoría) ---
		$new_logic = false;
		$new_tier  = 'normal'; // tier1 (estreno) | normal
		$new_cat   = '';
		if ( Settings::get( 'badge_new_enable', '0' ) ) {
			$days         = (int) Settings::get( 'badge_new_days', '15' );
			$first_pub    = (string) get_post_meta( $pid, '_le_first_published', true );
			$ref_ts       = $first_pub ? strtotime( $first_pub ) : null;
			if ( ! $ref_ts ) {
				$dc = $product->get_date_created();
				$ref_ts = $dc ? $dc->getTimestamp() : null;
			}
			if ( $ref_ts && ( time() - $ref_ts ) < ( $days * DAY_IN_SECONDS ) ) {
				$new_logic = true;
				$tier1_days = (int) Settings::get( 'badge_new_tier1_days', '7' );
				if ( ( time() - $ref_ts ) < ( $tier1_days * DAY_IN_SECONDS ) ) {
					$new_tier = 'tier1';
				}
				if ( Settings::get( 'badge_new_show_category', '0' ) ) {
					$cats = $product->get_category_ids();
					if ( ! empty( $cats ) ) {
						$cat_obj = get_term( (int) $cats[0], 'product_cat' );
						if ( $cat_obj && ! is_wp_error( $cat_obj ) ) $new_cat = $cat_obj->name;
					}
				}
			}
		}
		if ( $check_badge( 'new', $new_logic ) ) {
			$intents['new'] = [ 'tier' => $new_tier, 'category' => $new_cat ];
		}

		// --- Bestseller (tiers por ranking gold/silver/bronze + per categoría) ---
		$bestseller_logic = false;
		$bs_rank          = 0;
		$bs_tier          = 'bronze';
		$bs_cat_name      = '';
		if ( Settings::get( 'badge_bestseller_enable', '0' ) ) {
			$min_sales = (int) Settings::get( 'badge_bestseller_min', '50' );
			$window    = (int) Settings::get( 'badge_bestseller_window_days', '90' );
			if ( $window > 0 && class_exists( __NAMESPACE__ . '\\Recent_Sale' ) ) {
				$sales = Recent_Sale::get_instance()->get_sales_count_in_window( $pid, $window );
			} else {
				$sales = (int) get_post_meta( $pid, 'total_sales', true );
			}
			if ( $sales >= $min_sales ) {
				$bestseller_logic = true;
				$gold_max   = (int) Settings::get( 'badge_bestseller_tier_gold_max', '10' );
				$silver_max = (int) Settings::get( 'badge_bestseller_tier_silver_max', '50' );
				// Calculamos un "rank" aproximado basado en cuántos productos tienen más ventas.
				// Para mantenerlo barato: comparamos contra el meta total_sales (ranking global aproximado).
				$bs_rank = $this->compute_bestseller_rank( $pid, $sales );
				if ( $bs_rank > 0 && $bs_rank <= $gold_max )        $bs_tier = 'gold';
				elseif ( $bs_rank > 0 && $bs_rank <= $silver_max )  $bs_tier = 'silver';
				else                                                 $bs_tier = 'bronze';
				if ( Settings::get( 'badge_bestseller_per_category', '0' ) ) {
					$cats = $product->get_category_ids();
					if ( ! empty( $cats ) ) {
						$cat_obj = get_term( (int) $cats[0], 'product_cat' );
						if ( $cat_obj && ! is_wp_error( $cat_obj ) ) $bs_cat_name = $cat_obj->name;
					}
				}
			}
		}
		if ( $check_badge( 'bestseller', $bestseller_logic ) ) {
			$intents['bestseller'] = [ 'rank' => $bs_rank, 'tier' => $bs_tier, 'category' => $bs_cat_name ];
		}

		// Producto digital (ebook): las insignias de ENVÍO no aplican jamás.
		// Se conservan las editoriales (Estreno, Oferta, Más Vendido).
		if ( $product->is_virtual() ) {
			unset( $intents['express'], $intents['import'], $intents['freeshipping'], $intents['preorder'], $intents['lowstock'] );
		}

		// PHASE 2 — aplicar matriz de exclusión (preorder gana sobre lowstock, etc.)
		$exclusions = $this->get_exclusions();
		foreach ( $exclusions as $winner => $losers ) {
			if ( isset( $intents[ $winner ] ) ) {
				foreach ( $losers as $loser ) {
					unset( $intents[ $loser ] );
				}
			}
		}

		// PHASE 2b — «A · Mínima»: la portada solo lleva oferta o existencias (una sola);
		// express/importado son líneas de texto aparte. En productos variables el stock es
		// por variación, así que «Quedan N» sería ambiguo: solo «En stock» (en la ficha).
		if ( $a_mode ) {
			if ( 'lines' === $mode ) {
				$intents = array_intersect_key( $intents, [ 'express' => 1, 'import' => 1 ] );
			} else {
				$intents = array_intersect_key( $intents, [ 'sale' => 1, 'lowstock' => 1 ] );
				if ( $product->is_type( 'variable' ) ) {
					unset( $intents['lowstock'] );
				}
			}
		}

		// PHASE 3 — sort por prioridad (desc) + truncar a max
		$priorities = $this->get_priorities();
		uksort( $intents, function ( $a, $b ) use ( $priorities ) {
			return ( $priorities[ $b ] ?? 0 ) <=> ( $priorities[ $a ] ?? 0 );
		} );
		$max = max( 1, (int) Settings::get( 'max_badges', '3' ) );
		if ( $a_mode ) {
			$max = ( 'lines' === $mode ) ? 2 : 1;
		}
		$intents = array_slice( $intents, 0, $max, true );

		// PHASE 4 — render survivors
		$badges_by_position = [];
		$render = function ( $prefix, $defaults, $extra_class, $data = [] ) use ( $context, $mode, $a_mode, &$badges_by_position ) {
			if ( ! Settings::get( "badge_{$prefix}_{$context}", '1' ) ) return;

			// === Selección de TEXTO + EMOJI + ANIMACIÓN según tier/override avanzado ===
			$text  = (string) Settings::get( "badge_{$prefix}_text", $defaults['text'] );
			$emoji = (string) Settings::get( "badge_{$prefix}_emojis", $defaults['emoji'] );
			$anim  = (string) Settings::get( "badge_{$prefix}_animation", $defaults['anim'] );

			// Sale tiers
			if ( 'sale' === $prefix && isset( $data['tier'] ) ) {
				if ( 1 === $data['tier'] ) $text = (string) Settings::get( 'badge_sale_tier1_text', $text );
				elseif ( 2 === $data['tier'] ) $text = (string) Settings::get( 'badge_sale_tier2_text', $text );
				elseif ( 3 === $data['tier'] ) $text = (string) Settings::get( 'badge_sale_tier3_text', $text );
			}

			// Lowstock tiers
			if ( 'lowstock' === $prefix && isset( $data['tier'] ) ) {
				if ( 'critical' === $data['tier'] ) {
					$text = (string) Settings::get( 'badge_lowstock_text_critical', $text );
					$anim = (string) Settings::get( 'badge_lowstock_anim_critical', $anim );
				} elseif ( 'few' === $data['tier'] ) {
					$text = (string) Settings::get( 'badge_lowstock_text_few', $text );
				}
			}

			// Express cutoff: si está activo, reemplaza texto con el de urgencia
			if ( 'express' === $prefix && ! empty( $data['cutoff_active'] ) ) {
				$cutoff_text = (string) Settings::get( 'badge_express_cutoff_text', 'Compra antes de las {time} para envío hoy' );
				$text = str_replace( '{time}', (string) ( $data['cutoff_time'] ?? '' ), $cutoff_text );
			}

			// Preorder countdown
			if ( 'preorder' === $prefix && ! empty( $data['show_countdown'] ) && isset( $data['days_to_release'] ) && $data['days_to_release'] !== null && $data['days_to_release'] >= 0 ) {
				$cd_text = (string) Settings::get( 'badge_preorder_countdown_text', 'Lanza en {days} días' );
				$text = str_replace( '{days}', (string) $data['days_to_release'], $cd_text );
			}
			// Preorder discount
			if ( 'preorder' === $prefix && ! empty( $data['discount'] ) && $data['discount'] > 0 ) {
				$text .= ' -' . $data['discount'] . '%';
			}

			// New tier "Estreno"
			if ( 'new' === $prefix && isset( $data['tier'] ) && 'tier1' === $data['tier'] ) {
				$text  = (string) Settings::get( 'badge_new_tier1_text', $text );
				$emoji = (string) Settings::get( 'badge_new_tier1_emoji', $emoji );
			}

			// Bestseller tier por ranking
			if ( 'bestseller' === $prefix && isset( $data['tier'] ) ) {
				if ( 'gold' === $data['tier'] ) {
					$text  = (string) Settings::get( 'badge_bestseller_tier_gold_text', $text );
					$emoji = (string) Settings::get( 'badge_bestseller_tier_gold_emoji', $emoji );
				} elseif ( 'silver' === $data['tier'] ) {
					$text  = (string) Settings::get( 'badge_bestseller_tier_silver_text', $text );
					$emoji = (string) Settings::get( 'badge_bestseller_tier_silver_emoji', $emoji );
				} else {
					$text  = (string) Settings::get( 'badge_bestseller_tier_bronze_text', $text );
					$emoji = (string) Settings::get( 'badge_bestseller_tier_bronze_emoji', $emoji );
				}
			}

			// === Reemplazos de placeholders comunes ===
			if ( isset( $data['discount_pct'] ) && $data['discount_pct'] > 0 ) {
				if ( false !== strpos( $text, '{discount}' ) ) {
					$text = str_replace( '{discount}', (string) $data['discount_pct'], $text );
				} elseif ( false !== strpos( $text, '{pct}' ) ) {
					$text = str_replace( '{pct}', (string) $data['discount_pct'], $text );
				} elseif ( 'sale' === $prefix && false === strpos( $text, '%' ) ) {
					$text .= ' -' . $data['discount_pct'] . '%';
				}
			}
			if ( isset( $data['stock'] ) && $data['stock'] > 0 ) {
				$text = str_replace( [ '{stock}', '{remaining}' ], (string) $data['stock'], $text );
			}
			if ( isset( $data['threshold'] ) && $data['threshold'] > 0 ) {
				$formatted = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $data['threshold'] ) ) : (string) $data['threshold'];
				$text = str_replace( '{threshold}', $formatted, $text );
				if ( false === strpos( $text, $formatted ) ) {
					$text .= sprintf( /* translators: %s: amount */ __( ' (sobre %s)', 'woo-etiquetas-envio' ), $formatted );
				}
			}
			if ( isset( $data['country'] ) && $data['country'] !== '' ) {
				$text = str_replace( '{country}', (string) $data['country'], $text );
			}
			if ( isset( $data['flag'] ) && $data['flag'] !== '' ) {
				$text = str_replace( '{flag}', (string) $data['flag'], $text );
			}
			if ( isset( $data['rank'] ) && $data['rank'] > 0 ) {
				$text = str_replace( '{rank}', (string) $data['rank'], $text );
			}
			if ( isset( $data['category'] ) && $data['category'] !== '' ) {
				$text = str_replace( '{category}', (string) $data['category'], $text );
			}
			// «A · Mínima»: textos fijos sin emoji, sin mayúsculas y sin signos de exclamación.
			if ( $a_mode && 'lines' === $mode ) {
				$badges_by_position['lines'] = ( $badges_by_position['lines'] ?? '' ) . sprintf(
					'<p class="le-line le-line--%s"><span class="le-line__ico" aria-hidden="true">%s</span> %s</p>',
					esc_attr( $prefix ),
					esc_html( $emoji ),
					esc_html( $text )
				);
				return;
			}
			if ( $a_mode ) {
				if ( 'sale' === $prefix && isset( $data['discount_pct'] ) ) {
					$text = '−' . (int) $data['discount_pct'] . "\u{00A0}%";
				} elseif ( 'lowstock' === $prefix ) {
					$text = ( isset( $data['tier'] ) && 'critical' === $data['tier'] )
						? __( 'Última unidad', 'woo-etiquetas-envio' )
						: sprintf( /* translators: %d: units left */ __( 'Quedan %d', 'woo-etiquetas-envio' ), (int) ( $data['stock'] ?? 0 ) );
				}
				$emoji = '';
				$anim  = 'none';
			}
			$color      = (string) Settings::get( "badge_{$prefix}_color", $defaults['color'] );
			$text_color = (string) Settings::get( "badge_{$prefix}_text_color", $color );
			$alpha      = (string) Settings::get( "badge_{$prefix}_alpha", '15' );
			$pos        = (string) Settings::get( "badge_{$prefix}_emoji_pos", 'right' );
			$pos_img    = (string) Settings::get( "badge_{$prefix}_position", 'top-right' );
			if ( $a_mode ) {
				$pos_img = 'bottom-left'; // abajo a la izquierda: arriba están «Guardar» y el corazón
			}

			$class = "le-badge {$extra_class} le-anim-{$anim}";

			$hex = ltrim( $color, '#' );
			if ( 3 === strlen( $hex ) ) {
				$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
				$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
				$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
			} else {
				$r = hexdec( substr( $hex, 0, 2 ) );
				$g = hexdec( substr( $hex, 2, 2 ) );
				$b = hexdec( substr( $hex, 4, 2 ) );
			}
			$a        = floatval( $alpha ) / 100;
			$bg_color = "rgba({$r}, {$g}, {$b}, {$a})";

			if ( $a_mode && '' === $emoji ) {
				$content = sprintf( '<span class="le-badge__text">%s</span>', esc_html( $text ) );
			} elseif ( 'left' === $pos ) {
				$content = sprintf( '<span class="le-badge__emojis">%s</span> <span class="le-badge__text">%s</span>', esc_html( $emoji ), esc_html( $text ) );
			} else {
				$content = sprintf( '<span class="le-badge__text">%s</span> <span class="le-badge__emojis">%s</span>', esc_html( $text ), esc_html( $emoji ) );
			}

			if ( ! isset( $badges_by_position[ $pos_img ] ) ) {
				$badges_by_position[ $pos_img ] = '';
			}

			// Granular per-badge overrides (1.6.0). Vacíos no aplican.
			$g_radius   = (string) Settings::get( "badge_{$prefix}_border_radius", '' );
			$g_bw       = (string) Settings::get( "badge_{$prefix}_border_width", '' );
			$g_padding  = (string) Settings::get( "badge_{$prefix}_padding", '' );
			$g_fsize    = (string) Settings::get( "badge_{$prefix}_font_size", '' );
			$g_fweight  = (string) Settings::get( "badge_{$prefix}_font_weight", '' );
			$g_bgtype   = (string) Settings::get( "badge_{$prefix}_bg_type", '' );
			$g_gcolor   = (string) Settings::get( "badge_{$prefix}_gradient_color", '#ffffff' );
			$g_galpha   = (int) Settings::get( "badge_{$prefix}_gradient_color_alpha", '100' );
			$g_gangle   = (int) Settings::get( "badge_{$prefix}_gradient_angle", '135' );
			$g_shadow   = (string) Settings::get( "badge_{$prefix}_shadow", '' );
			$g_upper    = (string) Settings::get( "badge_{$prefix}_uppercase", '' );

			$style_parts = [
				"--le-color: {$color}",
				"--le-bg: {$bg_color}",
				"--le-text-color: {$text_color}",
			];

			// Background: granular type override beats preset
			if ( 'gradient' === $g_bgtype ) {
				// Convertir hex secundario a rgba con su alpha individual
				$ghex = ltrim( $g_gcolor, '#' );
				if ( 3 === strlen( $ghex ) ) {
					$gr = hexdec( substr( $ghex, 0, 1 ) . substr( $ghex, 0, 1 ) );
					$gg = hexdec( substr( $ghex, 1, 1 ) . substr( $ghex, 1, 1 ) );
					$gb = hexdec( substr( $ghex, 2, 1 ) . substr( $ghex, 2, 1 ) );
				} else {
					$gr = hexdec( substr( $ghex, 0, 2 ) );
					$gg = hexdec( substr( $ghex, 2, 2 ) );
					$gb = hexdec( substr( $ghex, 4, 2 ) );
				}
				$ga = max( 0, min( 100, $g_galpha ) ) / 100;
				$g_rgba = "rgba({$gr}, {$gg}, {$gb}, {$ga})";
				$style_parts[] = "background: linear-gradient({$g_gangle}deg, {$color}, {$g_rgba}) !important";
			} elseif ( 'transparent' === $g_bgtype ) {
				$style_parts[] = 'background: transparent !important';
			} else {
				$style_parts[] = "background-color: {$bg_color}";
			}
			$style_parts[] = "border-color: {$color}";
			$style_parts[] = "color: {$text_color}";

			if ( '' !== $g_radius )  $style_parts[] = "border-radius: {$g_radius}px !important";
			if ( '' !== $g_bw )      $style_parts[] = "border-width: {$g_bw}px !important; border-style: solid";
			if ( '' !== $g_padding ) $style_parts[] = "padding: {$g_padding}px !important";
			if ( '' !== $g_fsize )   $style_parts[] = "font-size: {$g_fsize}px !important";
			if ( '' !== $g_fweight ) $style_parts[] = "font-weight: {$g_fweight} !important";
			if ( '1' === $g_upper )  $style_parts[] = 'text-transform: uppercase !important';

			if ( 'soft' === $g_shadow )         $style_parts[] = 'box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important';
			elseif ( 'medium' === $g_shadow )   $style_parts[] = 'box-shadow: 0 4px 12px rgba(0,0,0,0.20) !important';
			elseif ( 'hard' === $g_shadow )     $style_parts[] = 'box-shadow: 0 6px 16px rgba(0,0,0,0.30) !important';
			elseif ( 'glow' === $g_shadow )     $style_parts[] = "box-shadow: 0 0 12px {$color} !important";
			elseif ( 'none' === $g_shadow )     $style_parts[] = 'box-shadow: none !important';

			$style = implode( '; ', $style_parts ) . ';';

			// Chip unificado: va al FINAL del style inline para ganarle a cualquier
			// override granular anterior (entre !important inline, gana el último).
			// Colores configurables: le_a_badge_bg / le_a_badge_text (modo A) y
			// le_chip_bg / le_chip_text (modo clásico).
			if ( $a_mode ) {
				// A · Mínima: rectángulo opaco, radio 4, 11 px / 700 y sin animación.
				$style .= 'padding:5px 7px !important;font-size:11px !important;font-weight:700 !important;line-height:1 !important;border-radius:4px !important;border:0 !important;background:' . self::color_opt( 'a_badge_bg', '#1f2937' ) . ' !important;color:' . self::color_opt( 'a_badge_text', '#ffffff' ) . ' !important;box-shadow:none !important;text-transform:none !important;letter-spacing:0 !important;animation:none !important;white-space:nowrap !important;';
			} else
			$style .= 'padding:5px 10px !important;font-size:0.7rem !important;font-weight:400 !important;line-height:1 !important;border-radius:999px !important;border:0 !important;background:' . self::color_opt( 'chip_bg', '#1f2937' ) . ' !important;color:' . self::color_opt( 'chip_text', '#ffffff' ) . ' !important;box-shadow:none !important;text-transform:none !important;';

			$badges_by_position[ $pos_img ] .= sprintf(
				'<div class="%s" style="%s">%s</div>',
				esc_attr( $class ),
				esc_attr( $style ),
				$content
			);
		};

		$badge_defs = [
			'sale'         => [ 'text' => __( 'OFERTA', 'woo-etiquetas-envio' ),                  'color' => '#e53e3e', 'emoji' => '🔥',   'anim' => 'pulso',           'class' => 'le-badge--sale' ],
			'freeshipping' => [ 'text' => __( 'Envío Gratis', 'woo-etiquetas-envio' ),            'color' => '#38a169', 'emoji' => '🆓',   'anim' => 'vuelo_suave',     'class' => 'le-badge--freeshipping' ],
			'express'      => [ 'text' => __( 'apto para envío express', 'woo-etiquetas-envio' ), 'color' => '#000000', 'emoji' => '⚡🚀', 'anim' => 'rebote',          'class' => 'le-badge--express' ],
			'import'       => [ 'text' => __( 'Importación Directa', 'woo-etiquetas-envio' ),     'color' => '#777777', 'emoji' => '✈️🌍', 'anim' => 'vuelo_suave',     'class' => 'le-badge--imported' ],
			'lowstock'     => [ 'text' => __( 'Últimas Unidades', 'woo-etiquetas-envio' ),        'color' => '#d69e2e', 'emoji' => '⏳',   'anim' => 'pulso',           'class' => 'le-badge--lowstock' ],
			'preorder'     => [ 'text' => __( 'Preventa', 'woo-etiquetas-envio' ),                'color' => '#805ad5', 'emoji' => '🗓️',  'anim' => 'flotacion',       'class' => 'le-badge--preorder' ],
			'new'          => [ 'text' => __( 'Novedad', 'woo-etiquetas-envio' ),                 'color' => '#38b2ac', 'emoji' => '✨',   'anim' => 'arranque_rapido', 'class' => 'le-badge--new' ],
			'bestseller'   => [ 'text' => __( 'Más Vendido', 'woo-etiquetas-envio' ),             'color' => '#d69e2e', 'emoji' => '🏆',   'anim' => 'pulso',           'class' => 'le-badge--bestseller' ],
		];

		foreach ( $intents as $id => $data ) {
			if ( ! isset( $badge_defs[ $id ] ) ) continue;
			$def = $badge_defs[ $id ];
			$render(
				$id,
				[ 'text' => $def['text'], 'color' => $def['color'], 'emoji' => $def['emoji'], 'anim' => $def['anim'] ],
				$def['class'],
				$data
			);
		}

		if ( empty( $badges_by_position ) ) {
			return '';
		}
		if ( $a_mode && 'lines' === $mode ) {
			return '<div class="le-lines">' . ( $badges_by_position['lines'] ?? '' ) . '</div>';
		}

		$output = '';
		foreach ( $badges_by_position as $pos_img => $html ) {
			$output .= sprintf(
				'<div class="le-badges-container le-badges-container--%s le-badges-pos--%s">%s</div>',
				esc_attr( $context ),
				esc_attr( $pos_img ),
				$html
			);
		}

		return $output;
	}

	public function render_badges_catalog(): void {
		global $product;
		if ( ! ( $product instanceof \WC_Product ) ) {
			// Fallback para carruseles que no setean global $product
			$pid = get_the_ID();
			if ( $pid ) {
				$product = wc_get_product( $pid );
			}
		}
		if ( $product instanceof \WC_Product ) {
			echo $this->get_badges_html( $product, 'catalog' );
		}
	}

	public function render_badges_single(): void {
		global $product;
		if ( $product instanceof \WC_Product ) {
			echo $this->get_badges_html( $product, 'single' );
		}
	}

	/**
	 * Suma días hábiles excluyendo fin de semana y festivos configurados.
	 */
	private function add_business_days( \DateTime $date, int $days ): \DateTime {
		$holidays = Settings::holidays();
		$added    = 0;
		while ( $added < $days ) {
			$date->modify( '+1 day' );
			$is_weekend = (int) $date->format( 'N' ) >= 6;
			$is_holiday = in_array( $date->format( 'Y-m-d' ), $holidays, true );
			if ( ! $is_weekend && ! $is_holiday ) {
				$added++;
			}
		}
		return $date;
	}

	public function get_delivery_dates( string $state ): array {
		$exclude_weekend = (bool) Settings::get( 'est_exclude_weekend', '1' );
		$format          = (string) Settings::get( 'est_date_format', 'l j \d\e F' );

		$min_days = 1;
		$max_days = 3;
		if ( 'local' === $state ) {
			$min_days = (int) Settings::get( 'est_local_min', 1 );
			$max_days = (int) Settings::get( 'est_local_max', 3 );
		} elseif ( 'backorder' === $state ) {
			$min_days = (int) Settings::get( 'est_backorder_min', 7 );
			$max_days = (int) Settings::get( 'est_backorder_max', 15 );
		} elseif ( 'imported' === $state ) {
			$min_days = (int) Settings::get( 'est_import_min', 15 );
			$max_days = (int) Settings::get( 'est_import_max', 30 );
		}

		// Validación defensiva: min ≤ max
		if ( $min_days > $max_days ) {
			$max_days = $min_days;
		}

		$tz    = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
		$date1 = new \DateTime( 'now', $tz );
		$date2 = new \DateTime( 'now', $tz );

		if ( $exclude_weekend ) {
			$date1 = $this->add_business_days( $date1, $min_days );
			$date2 = $this->add_business_days( $date2, $max_days );
		} else {
			$date1->modify( "+{$min_days} days" );
			$date2->modify( "+{$max_days} days" );
		}

		$str1 = date_i18n( $format, $date1->getTimestamp() );
		$str2 = date_i18n( $format, $date2->getTimestamp() );

		return [
			'min_date' => $str1,
			'max_date' => $str2,
			'min_days' => $min_days,
			'max_days' => $max_days,
		];
	}

	public function get_delivery_box_html( \WC_Product $product ): string {
		// Producto digital (ebook): no hay camión que esperar.
		if ( $product->is_virtual() ) {
			return '';
		}
		$state = Shipping::get_instance()->get_product_state( $product );
		$dates = $this->get_delivery_dates( $state );

		if ( 'local' === $state ) {
			$texto = (string) Settings::get( 'est_msg_local', __( 'Apto para envío express ⚡🚀', 'woo-etiquetas-envio' ) );
		} elseif ( 'imported' === $state ) {
			$texto = (string) Settings::get( 'est_msg_import', __( 'Importación Directa ✈️🌍 o escribe para envío express ⚡', 'woo-etiquetas-envio' ) );
		} else {
			$texto = (string) Settings::get( 'est_msg_backorder', __( 'Disponible bajo pedido 📦 o escribe para envío express ⚡', 'woo-etiquetas-envio' ) );
		}

		$bg_color   = (string) Settings::get( 'est_bg_color', '#1a1a1a' );
		$text_color = (string) Settings::get( 'est_text_color', '#ffffff' );

		$box_style    = sprintf( 'background-color: %s; border-color: %s;', $bg_color, $bg_color );
		$text_style   = sprintf( 'color: %s;', $text_color );
		$title_style  = sprintf( 'color: %s; opacity: 0.7;', $text_color );
		$meta_style   = sprintf( 'color: %s; opacity: 0.8;', $text_color );
		// Línea opcional (p. ej. recogida en tienda): ajuste le_pickup_text, vacío = no se muestra.
		$pickup_text  = trim( (string) Settings::get( 'pickup_text', '' ) );
		// Línea de recogida gratis: separada por un filete tenue del color del texto.
		$pickup_style = sprintf( 'color: %s; opacity: 0.92; margin-top: 8px; padding-top: 8px; border-top: 1px solid %s; font-size: 0.92em;', $text_color, $text_color );

		ob_start();
		?>
		<div class="le-delivery-box" style="<?php echo esc_attr( $box_style ); ?>">
			<div class="le-delivery-box__icon" style="<?php echo esc_attr( $text_style ); ?>">🚚</div>
			<div class="le-delivery-box__content">
				<div class="le-delivery-box__title" style="<?php echo esc_attr( $title_style ); ?>"><?php esc_html_e( 'Envío estimado:', 'woo-etiquetas-envio' ); ?></div>
				<div class="le-delivery-box__dates" style="<?php echo esc_attr( $text_style ); ?>"><?php echo esc_html( $dates['min_date'] ); ?> — <?php echo esc_html( $dates['max_date'] ); ?></div>
				<div class="le-delivery-box__meta" style="<?php echo esc_attr( $meta_style ); ?>"><?php echo wp_kses_post( $texto ); ?></div>
				<?php if ( '' !== $pickup_text ) : ?>
				<div class="le-delivery-box__pickup" style="<?php echo esc_attr( $pickup_style ); ?>"><?php echo wp_kses_post( $pickup_text ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function render_delivery_box(): void {
		global $product;
		if ( ! $product instanceof \WC_Product || $product->is_virtual() ) {
			return;
		}

		if ( defined( 'LSCWP_V' ) || defined( 'WP_ROCKET_VERSION' ) || defined( 'W3TC' ) ) {
			printf(
				'<div id="le-delivery-box-placeholder" data-le-pid="%s"></div>',
				esc_attr( (string) $product->get_id() )
			);
		} else {
			echo $this->get_delivery_box_html( $product );
		}
	}
}
