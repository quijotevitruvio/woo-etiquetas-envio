<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Agrega configuraciones individuales de badges en la edición del producto.
 */
final class Product_Meta {

	private static ?Product_Meta $instance = null;

	public static function get_instance(): Product_Meta {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_data_panel' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_meta' ] );
	}

	/**
	 * Agrega la pestaña "Etiquetas y envío" a la caja de datos del producto.
	 */
	public function add_product_data_tab( array $tabs ): array {
		$tabs['libros_express'] = [
			'label'    => 'Etiquetas y envío',
			'target'   => 'le_product_data_panel',
			'class'    => [ 'show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external' ],
			'priority' => 90,
		];
		return $tabs;
	}

	/**
	 * Contenido de la pestaña (opciones de badges).
	 */
	public function add_product_data_panel(): void {
		global $post;
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return;
		}

		echo '<div id="le_product_data_panel" class="panel woocommerce_options_panel hidden">';

		// === Datos editoriales y de lógica avanzada ===
		echo '<div class="options_group">';
		echo '<p style="padding: 0 12px;"><strong>📚 Datos editoriales</strong> · usados por las lógicas avanzadas de cada badge.</p>';

		$release_date    = (string) get_post_meta( $post->ID, '_le_release_date', true );
		$first_published = (string) get_post_meta( $post->ID, '_le_first_published', true );
		$country_origin  = (string) get_post_meta( $post->ID, '_le_country_origin', true );
		$country_emoji   = (string) get_post_meta( $post->ID, '_le_country_emoji', true );
		$import_days_min = (string) get_post_meta( $post->ID, '_le_import_days_min', true );
		$import_days_max = (string) get_post_meta( $post->ID, '_le_import_days_max', true );
		$preorder_disc   = (string) get_post_meta( $post->ID, '_le_preorder_discount', true );
		?>
		<p class="form-field _le_release_date_field">
			<label for="_le_release_date">🗓️ Fecha de lanzamiento</label>
			<input type="date" id="_le_release_date" name="_le_release_date" value="<?php echo esc_attr( $release_date ); ?>">
			<span class="description" style="display:block;margin-left:160px">Para badge Preventa con countdown automático.</span>
		</p>
		<p class="form-field _le_first_published_field">
			<label for="_le_first_published">✨ Primera publicación</label>
			<input type="date" id="_le_first_published" name="_le_first_published" value="<?php echo esc_attr( $first_published ); ?>">
			<span class="description" style="display:block;margin-left:160px">Si está, prevalece sobre "fecha de creación" para badge Novedad. Útil para reprints.</span>
		</p>
		<p class="form-field _le_country_origin_field">
			<label for="_le_country_origin">🌍 País de origen</label>
			<input type="text" id="_le_country_origin" name="_le_country_origin" value="<?php echo esc_attr( $country_origin ); ?>" placeholder="ej: EEUU, España">
			<span class="description" style="display:block;margin-left:160px">Override individual del país global. Para badge Importación.</span>
		</p>
		<p class="form-field _le_country_emoji_field">
			<label for="_le_country_emoji">🇨🇴 Bandera (emoji)</label>
			<input type="text" id="_le_country_emoji" name="_le_country_emoji" value="<?php echo esc_attr( $country_emoji ); ?>" placeholder="🇺🇸 🇪🇸 🇬🇧" style="width:100px">
		</p>
		<p class="form-field _le_import_days_field">
			<label>✈️ Días de importación</label>
			<input type="number" id="_le_import_days_min" name="_le_import_days_min" value="<?php echo esc_attr( $import_days_min ); ?>" placeholder="min" min="1" style="width:80px">
			<span style="margin:0 6px">→</span>
			<input type="number" name="_le_import_days_max" value="<?php echo esc_attr( $import_days_max ); ?>" placeholder="max" min="1" style="width:80px">
			<span class="description" style="display:block;margin-left:160px">Override del rango global de días de importación para este producto.</span>
		</p>
		<p class="form-field _le_preorder_discount_field">
			<label for="_le_preorder_discount">🗓️ Descuento preventa (%)</label>
			<input type="number" id="_le_preorder_discount" name="_le_preorder_discount" value="<?php echo esc_attr( $preorder_disc ); ?>" min="0" max="99" placeholder="0" style="width:80px">
			<span class="description" style="display:block;margin-left:160px">Override del descuento global de preventa.</span>
		</p>
		<?php
		echo '</div>';

		// === Overrides de visibilidad ===
		echo '<div class="options_group">';
		echo '<p style="padding: 0 12px;"><strong>🎚️ Overrides de visibilidad</strong> · fuerza o desactiva Badges manualmente.<br>Por defecto, todos están en "Automático" (siguen las reglas globales).</p>';

		// Evaluate natural logic
		$states = [];
		
		// Sale
		$states['sale'] = get_option( 'le_badge_sale_enable', '1' ) && $product->is_on_sale();
		
		// Free shipping
		$free_logic = false;
		if ( get_option( 'le_badge_freeshipping_enable', '0' ) ) {
			$shipping_class = $product->get_shipping_class();
			if ( strpos($shipping_class, 'gratis') !== false || has_term('envio-gratis', 'product_tag', $product->get_id()) || has_term('envío-gratis', 'product_tag', $product->get_id()) ) {
				$free_logic = true;
			}
		}
		$states['freeshipping'] = $free_logic;

		// Envío (Express / Import)
		$shipping_state = Shipping::get_instance()->get_product_state( $product );
		$states['express'] = 'local' === $shipping_state && get_option( 'le_badge_express_enable', '1' );
		$states['import']  = 'imported' === $shipping_state && get_option( 'le_badge_import_enable', '1' );

		// Low stock
		$lowstock_logic = false;
		if ( get_option( 'le_badge_lowstock_enable', '0' ) && $product->managing_stock() ) {
			$stock = $product->get_stock_quantity();
			$threshold = (int) get_option( 'le_badge_lowstock_threshold', '3' );
			if ( $stock > 0 && $stock <= $threshold ) {
				$lowstock_logic = true;
			}
		}
		$states['lowstock'] = $lowstock_logic;

		// Preorder
		$preorder_logic = false;
		if ( get_option( 'le_badge_preorder_enable', '0' ) ) {
			if ( ($product->managing_stock() && $product->backorders_allowed() && $product->get_stock_quantity() <= 0) || has_term('preventa', 'product_tag', $product->get_id()) ) {
				$preorder_logic = true;
			}
		}
		$states['preorder'] = $preorder_logic;

		// New
		$new_logic = false;
		if ( get_option( 'le_badge_new_enable', '0' ) ) {
			$days = (int) get_option( 'le_badge_new_days', '15' );
			$date_created = $product->get_date_created();
			if ( $date_created && ( time() - $date_created->getTimestamp() ) < ( $days * DAY_IN_SECONDS ) ) {
				$new_logic = true;
			}
		}
		$states['new'] = $new_logic;

		// Bestseller
		$bestseller_logic = false;
		if ( get_option( 'le_badge_bestseller_enable', '0' ) ) {
			$min_sales = (int) get_option( 'le_badge_bestseller_min', '50' );
			$sales = (int) get_post_meta( $product->get_id(), 'total_sales', true );
			if ( $sales >= $min_sales ) {
				$bestseller_logic = true;
			}
		}
		$states['bestseller'] = $bestseller_logic;

		$badges = [
			'sale'         => 'Oferta (🔥)',
			'freeshipping' => 'Envío Gratis (🆓)',
			'express'      => 'Express (⚡🚀)',
			'import'       => 'Importado (✈️🌍)',
			'lowstock'     => 'Últimas Unidades (⚠️)',
			'preorder'     => 'Preventa (📅)',
			'new'          => 'Nuevo (✨)',
			'bestseller'   => 'Más Vendido (⭐)',
		];

		foreach ( $badges as $id => $label ) {
			$is_active = $states[$id] ? '✅ Activa' : '❌ Inactiva';
			$auto_label = "Automático (Actualmente: {$is_active})";
			
			woocommerce_wp_select( [
				'id'          => "_le_override_{$id}",
				'label'       => $label,
				'description' => 'Controla este badge específicamente.',
				'desc_tip'    => true,
				'options'     => [
					''          => $auto_label,
					'force_on'  => 'Forzar SIEMPRE visible',
					'force_off' => 'Forzar SIEMPRE oculto',
				],
			] );
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Guarda las configuraciones de la pestaña.
	 */
	public function save_product_meta( int $post_id ): void {
		$badges = [ 'sale', 'freeshipping', 'express', 'import', 'lowstock', 'preorder', 'new', 'bestseller' ];

		foreach ( $badges as $id ) {
			$meta_key = "_le_override_{$id}";
			if ( isset( $_POST[ $meta_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) );
				if ( empty( $value ) ) {
					delete_post_meta( $post_id, $meta_key );
				} else {
					update_post_meta( $post_id, $meta_key, $value );
				}
			}
		}

		// === Datos editoriales (2.0.0) ===
		$editorial = [
			'_le_release_date'    => 'date',
			'_le_first_published' => 'date',
			'_le_country_origin'  => 'text',
			'_le_country_emoji'   => 'text',
			'_le_import_days_min' => 'int',
			'_le_import_days_max' => 'int',
			'_le_preorder_discount' => 'int',
		];
		foreach ( $editorial as $key => $type ) {
			if ( ! isset( $_POST[ $key ] ) ) continue;
			$raw = wp_unslash( $_POST[ $key ] );
			$value = '';
			if ( 'date' === $type ) {
				$value = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $raw ) ? sanitize_text_field( $raw ) : '';
			} elseif ( 'int' === $type ) {
				$value = '' === trim( (string) $raw ) ? '' : (string) max( 0, (int) $raw );
			} else {
				$value = sanitize_text_field( (string) $raw );
			}
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
}
