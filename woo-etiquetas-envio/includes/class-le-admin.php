<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manejador del Panel de Configuración.
 */
final class Admin {

	private static ?Admin $instance = null;

	public static function get_instance(): Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Renderiza los 9 controles granulares de estilo individual de un badge.
	 * Vacío = "usar default del preset" (el campo solo aplica si tiene valor).
	 */
	public function render_granular_style_controls( string $prefix ): void {
		// Estilo unificado "chip" (forma, tipografía y sombra fijos; colores en
		// le_chip_bg / le_chip_text / le_a_badge_bg / le_a_badge_text): estos controles ya no
		// tienen efecto en el frontend, así que no se muestran.
		return;
		$radius      = (string) get_option( "le_badge_{$prefix}_border_radius", '' );
		$bw          = (string) get_option( "le_badge_{$prefix}_border_width", '' );
		$padding     = (string) get_option( "le_badge_{$prefix}_padding", '' );
		$font_size   = (string) get_option( "le_badge_{$prefix}_font_size", '' );
		$font_weight = (string) get_option( "le_badge_{$prefix}_font_weight", '' );
		$bg_type     = (string) get_option( "le_badge_{$prefix}_bg_type", '' );
		$g_color     = (string) get_option( "le_badge_{$prefix}_gradient_color", '#ffffff' );
		$g_alpha     = (string) get_option( "le_badge_{$prefix}_gradient_color_alpha", '100' );
		$g_angle     = (string) get_option( "le_badge_{$prefix}_gradient_angle", '135' );
		$shadow      = (string) get_option( "le_badge_{$prefix}_shadow", '' );
		$uppercase   = (string) get_option( "le_badge_{$prefix}_uppercase", '' );

		$reset_attr = "var i=this.previousElementSibling.previousElementSibling;i.value='';i.dispatchEvent(new Event('input',{bubbles:true}));this.previousElementSibling.value='—';";
		?>
		<tr><td colspan="2">
			<h4 style="margin: 18px 0 4px; padding-top: 14px; border-top: 1px solid #e5e7eb;">🎛️ Estilo individual <span style="font-weight:normal;color:#94a3b8;font-size:12px">(deja vacío para usar el preset global)</span></h4>
		</td></tr>

		<!-- 🟦 Forma -->
		<tr><td colspan="2"><h5 class="le-style-group">🟦 Forma</h5></td></tr>
		<tr>
			<th>Border radius (px)</th>
			<td>
				<input type="range" min="0" max="50" step="1" name="le_badge_<?php echo esc_attr( $prefix ); ?>_border_radius" value="<?php echo esc_attr( $radius ); ?>" oninput="this.nextElementSibling.value = this.value + 'px'">
				<output><?php echo esc_html( $radius === '' ? '—' : $radius . 'px' ); ?></output>
				<button type="button" class="button-link" onclick="<?php echo esc_attr( $reset_attr ); ?>" style="margin-left:8px">reset</button>
			</td>
		</tr>
		<tr>
			<th>Border width (px)</th>
			<td>
				<input type="range" min="0" max="5" step="1" name="le_badge_<?php echo esc_attr( $prefix ); ?>_border_width" value="<?php echo esc_attr( $bw ); ?>" oninput="this.nextElementSibling.value = this.value + 'px'">
				<output><?php echo esc_html( $bw === '' ? '—' : $bw . 'px' ); ?></output>
				<button type="button" class="button-link" onclick="<?php echo esc_attr( $reset_attr ); ?>" style="margin-left:8px">reset</button>
			</td>
		</tr>
		<tr>
			<th>Padding interno (px)</th>
			<td>
				<input type="range" min="2" max="20" step="1" name="le_badge_<?php echo esc_attr( $prefix ); ?>_padding" value="<?php echo esc_attr( $padding ); ?>" oninput="this.nextElementSibling.value = this.value + 'px'">
				<output><?php echo esc_html( $padding === '' ? '—' : $padding . 'px' ); ?></output>
				<button type="button" class="button-link" onclick="<?php echo esc_attr( $reset_attr ); ?>" style="margin-left:8px">reset</button>
			</td>
		</tr>

		<!-- 📝 Tipografía -->
		<tr><td colspan="2"><h5 class="le-style-group">📝 Tipografía</h5></td></tr>
		<tr>
			<th>Tamaño fuente (px)</th>
			<td>
				<input type="number" min="8" max="24" step="1" name="le_badge_<?php echo esc_attr( $prefix ); ?>_font_size" value="<?php echo esc_attr( $font_size ); ?>" placeholder="—" style="width:80px">
			</td>
		</tr>
		<tr>
			<th>Peso fuente</th>
			<td>
				<select name="le_badge_<?php echo esc_attr( $prefix ); ?>_font_weight">
					<option value="" <?php selected( $font_weight, '' ); ?>>— (preset)</option>
					<option value="400" <?php selected( $font_weight, '400' ); ?>>Normal (400)</option>
					<option value="500" <?php selected( $font_weight, '500' ); ?>>Medium (500)</option>
					<option value="600" <?php selected( $font_weight, '600' ); ?>>Semibold (600)</option>
					<option value="700" <?php selected( $font_weight, '700' ); ?>>Bold (700)</option>
					<option value="900" <?php selected( $font_weight, '900' ); ?>>Black (900)</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>MAYÚSCULAS</th>
			<td>
				<input type="hidden" name="le_badge_<?php echo esc_attr( $prefix ); ?>_uppercase" value="0">
				<input type="checkbox" name="le_badge_<?php echo esc_attr( $prefix ); ?>_uppercase" value="1" <?php checked( $uppercase, '1' ); ?>>
				<span class="description" style="margin-left:8px">Forzar todo el texto en mayúsculas.</span>
			</td>
		</tr>

		<!-- 🌈 Fondo avanzado -->
		<tr><td colspan="2"><h5 class="le-style-group">🌈 Fondo avanzado</h5></td></tr>
		<tr>
			<th>Tipo de fondo</th>
			<td>
				<select name="le_badge_<?php echo esc_attr( $prefix ); ?>_bg_type">
					<option value="" <?php selected( $bg_type, '' ); ?>>— (preset / sólido con alpha)</option>
					<option value="solid" <?php selected( $bg_type, 'solid' ); ?>>Sólido (color + alpha)</option>
					<option value="gradient" <?php selected( $bg_type, 'gradient' ); ?>>Gradiente</option>
					<option value="transparent" <?php selected( $bg_type, 'transparent' ); ?>>Transparente</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>Color secundario gradiente</th>
			<td>
				<input type="color" name="le_badge_<?php echo esc_attr( $prefix ); ?>_gradient_color" value="<?php echo esc_attr( $g_color ); ?>">
				<span style="margin:0 8px 0 14px">Alpha:</span>
				<input type="range" min="0" max="100" step="1" name="le_badge_<?php echo esc_attr( $prefix ); ?>_gradient_color_alpha" value="<?php echo esc_attr( $g_alpha ); ?>" oninput="this.nextElementSibling.value = this.value + '%'" style="vertical-align:middle">
				<output><?php echo esc_html( $g_alpha . '%' ); ?></output>
				<p class="description" style="margin:6px 0 4px">Pon alpha = <code>0</code> con el mismo color primario para conseguir <strong>color → transparente</strong>.</p>
			</td>
		</tr>
		<tr>
			<th>Ángulo del gradiente</th>
			<td>
				<input type="range" min="0" max="360" step="5" name="le_badge_<?php echo esc_attr( $prefix ); ?>_gradient_angle" value="<?php echo esc_attr( $g_angle ); ?>" oninput="this.nextElementSibling.value = this.value + '°'">
				<output><?php echo esc_html( $g_angle . '°' ); ?></output>
				<p class="description">Solo aplica si "Tipo de fondo" = Gradiente.</p>
			</td>
		</tr>

		<!-- 💫 Efectos -->
		<tr><td colspan="2"><h5 class="le-style-group">💫 Efectos</h5></td></tr>
		<tr>
			<th>Sombra</th>
			<td>
				<select name="le_badge_<?php echo esc_attr( $prefix ); ?>_shadow">
					<option value="" <?php selected( $shadow, '' ); ?>>— (preset)</option>
					<option value="none" <?php selected( $shadow, 'none' ); ?>>Ninguna</option>
					<option value="soft" <?php selected( $shadow, 'soft' ); ?>>Suave</option>
					<option value="medium" <?php selected( $shadow, 'medium' ); ?>>Media</option>
					<option value="hard" <?php selected( $shadow, 'hard' ); ?>>Fuerte</option>
					<option value="glow" <?php selected( $shadow, 'glow' ); ?>>Glow (mismo color del badge)</option>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renderiza lógicas avanzadas específicas de cada badge.
	 */
	public function render_advanced_logic( string $prefix ): void {
		echo '<tr><td colspan="2"><h4 style="margin: 18px 0 4px; padding-top: 14px; border-top: 1px solid #e5e7eb;">🧠 Lógica avanzada</h4></td></tr>';

		switch ( $prefix ) {
			case 'express':
				$min_stock     = (string) get_option( 'le_badge_express_min_stock', '0' );
				$cutoff        = (string) get_option( 'le_badge_express_cutoff_time', '' );
				$cutoff_text   = (string) get_option( 'le_badge_express_cutoff_text', 'Compra antes de las {time} para envío hoy' );
				$max_weight    = (string) get_option( 'le_badge_express_max_weight', '0' );
				$excluded_cats = (array) get_option( 'le_badge_express_excluded_cats', [] );
				?>
				<tr><th>Stock mínimo para mostrar</th>
					<td>
						<input type="number" name="le_badge_express_min_stock" min="0" value="<?php echo esc_attr( $min_stock ); ?>" style="width:90px">
						<p class="description">No mostrar Express si el stock es menor a este número. <code>0</code> = desactivado.</p>
					</td>
				</tr>
				<tr><th>Cutoff time hoy (HH:MM)</th>
					<td>
						<input type="time" name="le_badge_express_cutoff_time" value="<?php echo esc_attr( $cutoff ); ?>">
						<p class="description">Si está antes de esta hora local, muestra mensaje de urgencia. Vacío = desactivado.</p>
					</td>
				</tr>
				<tr><th>Texto de urgencia</th>
					<td>
						<input type="text" name="le_badge_express_cutoff_text" value="<?php echo esc_attr( $cutoff_text ); ?>" class="regular-text">
						<p class="description">Reemplaza al texto del badge cuando aplica el cutoff. Usa <code>{time}</code>.</p>
					</td>
				</tr>
				<tr><th>Peso máximo (kg)</th>
					<td>
						<input type="number" name="le_badge_express_max_weight" min="0" step="0.1" value="<?php echo esc_attr( $max_weight ); ?>" style="width:90px">
						<p class="description">No aplica express si el producto pesa más. <code>0</code> = sin límite.</p>
					</td>
				</tr>
				<tr><th>Categorías excluidas</th>
					<td>
						<select name="le_badge_express_excluded_cats[]" multiple style="width:300px;height:120px">
							<?php
							$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
							if ( ! is_wp_error( $cats ) ) {
								foreach ( $cats as $cat ) {
									$sel = in_array( (string) $cat->term_id, array_map( 'strval', $excluded_cats ), true ) ? 'selected' : '';
									echo '<option value="' . esc_attr( $cat->term_id ) . '" ' . $sel . '>' . esc_html( $cat->name ) . '</option>';
								}
							}
							?>
						</select>
						<p class="description">Express no aplica para productos en estas categorías (ej: vouchers, libros pesados). Ctrl+Click para seleccionar varios.</p>
					</td>
				</tr>
				<?php
				break;

			case 'import':
				$country       = (string) get_option( 'le_badge_import_country', '' );
				$country_emoji = (string) get_option( 'le_badge_import_country_emoji', '' );
				?>
				<tr><th>País de origen (default)</th>
					<td>
						<input type="text" name="le_badge_import_country" value="<?php echo esc_attr( $country ); ?>" placeholder="ej: EEUU, España, Reino Unido">
						<p class="description">Se inserta en el texto vía placeholder <code>{country}</code>. Override individual por producto en su pestaña Etiquetas y envío.</p>
					</td>
				</tr>
				<tr><th>Emoji bandera del país</th>
					<td>
						<input type="text" name="le_badge_import_country_emoji" value="<?php echo esc_attr( $country_emoji ); ?>" placeholder="🇺🇸 🇪🇸 🇬🇧">
						<p class="description">Placeholder <code>{flag}</code> en el texto.</p>
					</td>
				</tr>
				<?php
				break;

			case 'sale':
				$tier1_max   = (string) get_option( 'le_badge_sale_tier1_max', '25' );
				$tier1_text  = (string) get_option( 'le_badge_sale_tier1_text', 'OFERTA -{discount}%' );
				$tier2_max   = (string) get_option( 'le_badge_sale_tier2_max', '50' );
				$tier2_text  = (string) get_option( 'le_badge_sale_tier2_text', 'GRAN OFERTA -{discount}%' );
				$tier3_text  = (string) get_option( 'le_badge_sale_tier3_text', '¡OFERTAZA -{discount}%!' );
				$schedule_from = (string) get_option( 'le_badge_sale_schedule_from', '' );
				$schedule_to   = (string) get_option( 'le_badge_sale_schedule_to', '' );
				$min_stock     = (string) get_option( 'le_badge_sale_min_stock', '0' );
				$exclude_pre   = (string) get_option( 'le_badge_sale_exclude_preorder', '0' );
				?>
				<tr><td colspan="2"><strong>Tiers visuales por nivel de descuento</strong></td></tr>
				<tr><th>Tier 1 (suave) — hasta %</th>
					<td>
						<input type="number" name="le_badge_sale_tier1_max" min="1" max="99" value="<?php echo esc_attr( $tier1_max ); ?>" style="width:90px">
						<input type="text" name="le_badge_sale_tier1_text" value="<?php echo esc_attr( $tier1_text ); ?>" placeholder="OFERTA -{discount}%" style="margin-left:10px;width:280px">
					</td>
				</tr>
				<tr><th>Tier 2 (medio) — hasta %</th>
					<td>
						<input type="number" name="le_badge_sale_tier2_max" min="1" max="99" value="<?php echo esc_attr( $tier2_max ); ?>" style="width:90px">
						<input type="text" name="le_badge_sale_tier2_text" value="<?php echo esc_attr( $tier2_text ); ?>" placeholder="GRAN OFERTA -{discount}%" style="margin-left:10px;width:280px">
					</td>
				</tr>
				<tr><th>Tier 3 (extremo) — texto</th>
					<td>
						<input type="text" name="le_badge_sale_tier3_text" value="<?php echo esc_attr( $tier3_text ); ?>" placeholder="¡OFERTAZA -{discount}%!" style="width:280px">
						<p class="description">Para descuentos > Tier 2 max. Tier 3 hereda animación con shine si está disponible.</p>
					</td>
				</tr>
				<tr><th>Schedule — Desde</th>
					<td>
						<input type="datetime-local" name="le_badge_sale_schedule_from" value="<?php echo esc_attr( $schedule_from ); ?>">
						<span style="margin:0 8px">→ Hasta</span>
						<input type="datetime-local" name="le_badge_sale_schedule_to" value="<?php echo esc_attr( $schedule_to ); ?>">
						<p class="description">Solo mostrar el badge entre estas fechas (ej: campaña Black Friday). Vacío = sin restricción.</p>
					</td>
				</tr>
				<tr><th>Stock mínimo</th>
					<td>
						<input type="number" name="le_badge_sale_min_stock" min="0" value="<?php echo esc_attr( $min_stock ); ?>" style="width:90px">
						<p class="description">No mostrar Oferta si el stock es menor. Evita conflicto con "Últimas Unidades".</p>
					</td>
				</tr>
				<tr><th>Excluir si está en preventa</th>
					<td>
						<input type="hidden" name="le_badge_sale_exclude_preorder" value="0">
						<input type="checkbox" name="le_badge_sale_exclude_preorder" value="1" <?php checked( $exclude_pre, '1' ); ?>>
					</td>
				</tr>
				<?php
				break;

			case 'lowstock':
				$crit_th       = (string) get_option( 'le_badge_lowstock_critical_threshold', '1' );
				$crit_text     = (string) get_option( 'le_badge_lowstock_text_critical', '¡ÚLTIMA UNIDAD!' );
				$crit_anim     = (string) get_option( 'le_badge_lowstock_anim_critical', 'vibracion_turbo' );
				$few_th        = (string) get_option( 'le_badge_lowstock_few_threshold', '5' );
				$few_text      = (string) get_option( 'le_badge_lowstock_text_few', 'Pocas unidades' );
				$exclude_sale  = (string) get_option( 'le_badge_lowstock_exclude_sale', '0' );
				?>
				<tr><td colspan="2"><strong>Tiers por nivel de stock</strong> (escalonan urgencia)</td></tr>
				<tr><th>Tier crítico — stock ≤</th>
					<td>
						<input type="number" name="le_badge_lowstock_critical_threshold" min="1" value="<?php echo esc_attr( $crit_th ); ?>" style="width:80px">
						<input type="text" name="le_badge_lowstock_text_critical" value="<?php echo esc_attr( $crit_text ); ?>" style="margin-left:10px;width:240px">
						<select name="le_badge_lowstock_anim_critical" style="margin-left:10px">
							<?php foreach ( [ 'rebote'=>'Rebote', 'pulso'=>'Pulso', 'vibracion_turbo'=>'Vibración turbo', 'arranque_rapido'=>'Arranque rápido' ] as $k => $v ) {
								echo '<option value="' . esc_attr( $k ) . '"' . selected( $crit_anim, $k, false ) . '>' . esc_html( $v ) . '</option>';
							} ?>
						</select>
					</td>
				</tr>
				<tr><th>Tier "pocas" — stock ≤</th>
					<td>
						<input type="number" name="le_badge_lowstock_few_threshold" min="1" value="<?php echo esc_attr( $few_th ); ?>" style="width:80px">
						<input type="text" name="le_badge_lowstock_text_few" value="<?php echo esc_attr( $few_text ); ?>" style="margin-left:10px;width:240px">
						<p class="description">Para stock entre "pocas" y "umbral de inventario" (ver arriba). Usa el texto principal del badge para el rango intermedio.</p>
					</td>
				</tr>
				<tr><th>Excluir si está en oferta</th>
					<td>
						<input type="hidden" name="le_badge_lowstock_exclude_sale" value="0">
						<input type="checkbox" name="le_badge_lowstock_exclude_sale" value="1" <?php checked( $exclude_sale, '1' ); ?>>
						<p class="description">Si tiene oferta activa, no mostrar Últimas Unidades (evita doble-mensaje).</p>
					</td>
				</tr>
				<?php
				break;

			case 'preorder':
				$show_count   = (string) get_option( 'le_badge_preorder_show_countdown', '1' );
				$count_text   = (string) get_option( 'le_badge_preorder_countdown_text', 'Lanza en {days} días' );
				$discount     = (string) get_option( 'le_badge_preorder_discount_pct', '0' );
				$auto_remove  = (string) get_option( 'le_badge_preorder_auto_remove_after', '1' );
				?>
				<tr><th>Countdown automático</th>
					<td>
						<input type="hidden" name="le_badge_preorder_show_countdown" value="0">
						<input type="checkbox" name="le_badge_preorder_show_countdown" value="1" <?php checked( $show_count, '1' ); ?>>
						<p class="description">Si el producto tiene fecha de lanzamiento (en su pestaña Etiquetas y envío), muestra cuántos días faltan.</p>
					</td>
				</tr>
				<tr><th>Texto del countdown</th>
					<td>
						<input type="text" name="le_badge_preorder_countdown_text" value="<?php echo esc_attr( $count_text ); ?>" class="regular-text">
						<p class="description">Reemplaza al texto del badge cuando hay countdown activo. Placeholders: <code>{days}</code>.</p>
					</td>
				</tr>
				<tr><th>Descuento de preventa (%)</th>
					<td>
						<input type="number" name="le_badge_preorder_discount_pct" min="0" max="99" value="<?php echo esc_attr( $discount ); ?>" style="width:90px">
						<p class="description">Si > 0, se anexa al texto: "Preventa -X%". Override por producto en su pestaña.</p>
					</td>
				</tr>
				<tr><th>Auto-remover al pasar la fecha</th>
					<td>
						<input type="hidden" name="le_badge_preorder_auto_remove_after" value="0">
						<input type="checkbox" name="le_badge_preorder_auto_remove_after" value="1" <?php checked( $auto_remove, '1' ); ?>>
						<p class="description">Si la fecha de lanzamiento ya pasó, no mostrar Preventa.</p>
					</td>
				</tr>
				<?php
				break;

			case 'new':
				$tier1_days  = (string) get_option( 'le_badge_new_tier1_days', '7' );
				$tier1_text  = (string) get_option( 'le_badge_new_tier1_text', 'Estreno' );
				$tier1_emoji = (string) get_option( 'le_badge_new_tier1_emoji', '🆕' );
				$show_cat    = (string) get_option( 'le_badge_new_show_category', '0' );
				?>
				<tr><th>Tier "Estreno" — primeros X días</th>
					<td>
						<input type="number" name="le_badge_new_tier1_days" min="1" max="365" value="<?php echo esc_attr( $tier1_days ); ?>" style="width:80px">
						<input type="text" name="le_badge_new_tier1_text" value="<?php echo esc_attr( $tier1_text ); ?>" style="margin-left:10px;width:160px">
						<input type="text" name="le_badge_new_tier1_emoji" value="<?php echo esc_attr( $tier1_emoji ); ?>" style="margin-left:10px;width:80px">
						<p class="description">Para los primeros X días desde lanzamiento usa este texto/emoji. Después usa el texto principal "Novedad".</p>
					</td>
				</tr>
				<tr><th>Mostrar categoría en el badge</th>
					<td>
						<input type="hidden" name="le_badge_new_show_category" value="0">
						<input type="checkbox" name="le_badge_new_show_category" value="1" <?php checked( $show_cat, '1' ); ?>>
						<p class="description">Si está activo y hay placeholder <code>{category}</code> en el texto, lo reemplaza con la primera categoría del producto. Ej: "Novedad en {category}" → "Novedad en Programación".</p>
					</td>
				</tr>
				<tr><td colspan="2"><p class="description" style="background:#fffbeb;padding:8px 12px;border-left:3px solid #f59e0b;border-radius:3px;"><strong>Tip:</strong> Para evitar marcar reprints como novedad, edita el producto y en su pestaña <strong>Etiquetas y envío</strong> setea la fecha de "Primera publicación" — esa fecha prevalece sobre la fecha de creación del producto.</p></td></tr>
				<?php
				break;

			case 'bestseller':
				$gold_max     = (string) get_option( 'le_badge_bestseller_tier_gold_max', '10' );
				$gold_text    = (string) get_option( 'le_badge_bestseller_tier_gold_text', 'Top {rank}' );
				$gold_emoji   = (string) get_option( 'le_badge_bestseller_tier_gold_emoji', '🥇' );
				$silver_max   = (string) get_option( 'le_badge_bestseller_tier_silver_max', '50' );
				$silver_text  = (string) get_option( 'le_badge_bestseller_tier_silver_text', 'Más Vendido' );
				$silver_emoji = (string) get_option( 'le_badge_bestseller_tier_silver_emoji', '🥈' );
				$bronze_text  = (string) get_option( 'le_badge_bestseller_tier_bronze_text', 'Popular' );
				$bronze_emoji = (string) get_option( 'le_badge_bestseller_tier_bronze_emoji', '🥉' );
				$per_cat      = (string) get_option( 'le_badge_bestseller_per_category', '0' );
				?>
				<tr><td colspan="2"><strong>Tiers por ranking de ventas</strong></td></tr>
				<tr><th>🥇 Tier oro — Top hasta</th>
					<td>
						<input type="number" name="le_badge_bestseller_tier_gold_max" min="1" value="<?php echo esc_attr( $gold_max ); ?>" style="width:80px">
						<input type="text" name="le_badge_bestseller_tier_gold_text" value="<?php echo esc_attr( $gold_text ); ?>" style="margin-left:10px;width:200px" placeholder="Top {rank}">
						<input type="text" name="le_badge_bestseller_tier_gold_emoji" value="<?php echo esc_attr( $gold_emoji ); ?>" style="margin-left:10px;width:80px">
					</td>
				</tr>
				<tr><th>🥈 Tier plata — Top hasta</th>
					<td>
						<input type="number" name="le_badge_bestseller_tier_silver_max" min="1" value="<?php echo esc_attr( $silver_max ); ?>" style="width:80px">
						<input type="text" name="le_badge_bestseller_tier_silver_text" value="<?php echo esc_attr( $silver_text ); ?>" style="margin-left:10px;width:200px">
						<input type="text" name="le_badge_bestseller_tier_silver_emoji" value="<?php echo esc_attr( $silver_emoji ); ?>" style="margin-left:10px;width:80px">
					</td>
				</tr>
				<tr><th>🥉 Tier bronce — texto</th>
					<td>
						<input type="text" name="le_badge_bestseller_tier_bronze_text" value="<?php echo esc_attr( $bronze_text ); ?>" style="width:200px">
						<input type="text" name="le_badge_bestseller_tier_bronze_emoji" value="<?php echo esc_attr( $bronze_emoji ); ?>" style="margin-left:10px;width:80px">
						<p class="description">Para productos que cumplen "Ventas mínimas" pero no entran en silver. Si hay mucho ruido, sube las "Ventas mínimas" en lugar de quitar este tier.</p>
					</td>
				</tr>
				<tr><th>Por categoría</th>
					<td>
						<input type="hidden" name="le_badge_bestseller_per_category" value="0">
						<input type="checkbox" name="le_badge_bestseller_per_category" value="1" <?php checked( $per_cat, '1' ); ?>>
						<p class="description">Si está activo y el texto contiene <code>{category}</code>, ranquea dentro de la categoría del producto en vez de globalmente. Ej: "Top {rank} en {category}".</p>
					</td>
				</tr>
				<?php
				break;

			case 'freeshipping':
				$amva_only      = (string) get_option( 'le_badge_freeshipping_amva_only', '0' );
				$show_remaining = (string) get_option( 'le_badge_freeshipping_show_remaining', '0' );
				$remaining_text = (string) get_option( 'le_badge_freeshipping_remaining_text', 'Te faltan {amount} para envío gratis' );
				$pay_methods    = (array) get_option( 'le_badge_freeshipping_payment_methods', [] );
				?>
				<tr><th>Solo zona Geo</th>
					<td>
						<input type="hidden" name="le_badge_freeshipping_amva_only" value="0">
						<input type="checkbox" name="le_badge_freeshipping_amva_only" value="1" <?php checked( $amva_only, '1' ); ?>>
						<p class="description">Solo mostrar el badge a clientes dentro de la zona definida en la pestaña Geo.</p>
					</td>
				</tr>
				<tr><th>Mostrar "te faltan $X" en carrito</th>
					<td>
						<input type="hidden" name="le_badge_freeshipping_show_remaining" value="0">
						<input type="checkbox" name="le_badge_freeshipping_show_remaining" value="1" <?php checked( $show_remaining, '1' ); ?>>
					</td>
				</tr>
				<tr><th>Texto de "te faltan"</th>
					<td>
						<input type="text" name="le_badge_freeshipping_remaining_text" value="<?php echo esc_attr( $remaining_text ); ?>" class="regular-text">
						<p class="description">Placeholder <code>{amount}</code> con el monto faltante formateado.</p>
					</td>
				</tr>
				<tr><th>Solo con métodos de pago</th>
					<td>
						<select name="le_badge_freeshipping_payment_methods[]" multiple style="width:300px;height:100px">
							<?php
							$gateways = function_exists( 'WC' ) && WC()->payment_gateways ? WC()->payment_gateways->payment_gateways() : [];
							foreach ( $gateways as $gw_id => $gw ) {
								$sel = in_array( $gw_id, array_map( 'strval', $pay_methods ), true ) ? 'selected' : '';
								echo '<option value="' . esc_attr( $gw_id ) . '" ' . $sel . '>' . esc_html( $gw->get_title() ) . '</option>';
							}
							?>
						</select>
						<p class="description">Si está vacío, aplica con cualquier método. Ctrl+Click para varios.</p>
					</td>
				</tr>
				<?php
				break;
		}
	}

	public function enqueue_admin_assets( $hook ): void {
		if ( 'woocommerce_page_woo-etiquetas-envio' !== $hook ) {
			return;
		}
		// Frontend badge styles (animations + classes) para usar en la vista previa
		wp_enqueue_style( 'woo-etiquetas-envio-styles', LE_PLUGIN_URL . 'assets/css/le-styles.css', [], LE_VERSION );
	}

	/**
	 * Agrega la página de menú.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Etiquetas y envío', 'woo-etiquetas-envio' ),
			__( 'Etiquetas y envío', 'woo-etiquetas-envio' ),
			'manage_options',
			'woo-etiquetas-envio',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Registra las configuraciones.
	 */
	public function register_settings(): void {
		$options = [
			'le_express_methods', 'le_import_tag_id', 'le_mixed_cart_notice', 'le_est_holidays',
			// Colores de texto por badge (1.3.2)
			'le_badge_express_text_color', 'le_badge_import_text_color', 'le_badge_sale_text_color',
			'le_badge_lowstock_text_color', 'le_badge_preorder_text_color', 'le_badge_new_text_color',
			'le_badge_bestseller_text_color', 'le_badge_freeshipping_text_color',
			// Lógicas mejoradas (1.5.0)
			'le_max_badges',
			'le_badge_sale_min_discount',
			'le_badge_lowstock_require_recent', 'le_badge_lowstock_recent_window_days',
			'le_badge_freeshipping_threshold',
			'le_badge_bestseller_window_days',
			'le_badge_express_text', 'le_badge_express_emojis', 'le_badge_express_animation', 'le_badge_express_catalog', 'le_badge_express_single', 'le_badge_express_color', 'le_badge_express_emoji_pos', 'le_badge_express_enable', 'le_badge_express_alpha', 'le_badge_express_position',
			'le_badge_import_text', 'le_badge_import_emojis', 'le_badge_import_animation', 'le_badge_import_catalog', 'le_badge_import_single', 'le_badge_import_color', 'le_badge_import_emoji_pos', 'le_badge_import_enable', 'le_badge_import_alpha', 'le_badge_import_position',
			'le_badge_sale_text', 'le_badge_sale_emojis', 'le_badge_sale_animation', 'le_badge_sale_enable', 'le_badge_sale_catalog', 'le_badge_sale_single', 'le_badge_sale_color', 'le_badge_sale_emoji_pos', 'le_badge_sale_alpha', 'le_badge_sale_position',
			'le_badge_lowstock_enable', 'le_badge_lowstock_text', 'le_badge_lowstock_emojis', 'le_badge_lowstock_animation', 'le_badge_lowstock_catalog', 'le_badge_lowstock_single', 'le_badge_lowstock_color', 'le_badge_lowstock_emoji_pos', 'le_badge_lowstock_threshold', 'le_badge_lowstock_alpha', 'le_badge_lowstock_position',
			'le_badge_preorder_enable', 'le_badge_preorder_text', 'le_badge_preorder_emojis', 'le_badge_preorder_animation', 'le_badge_preorder_catalog', 'le_badge_preorder_single', 'le_badge_preorder_color', 'le_badge_preorder_emoji_pos', 'le_badge_preorder_alpha', 'le_badge_preorder_position',
			'le_badge_new_enable', 'le_badge_new_text', 'le_badge_new_emojis', 'le_badge_new_animation', 'le_badge_new_catalog', 'le_badge_new_single', 'le_badge_new_color', 'le_badge_new_emoji_pos', 'le_badge_new_days', 'le_badge_new_alpha', 'le_badge_new_position',
			'le_badge_bestseller_enable', 'le_badge_bestseller_text', 'le_badge_bestseller_emojis', 'le_badge_bestseller_animation', 'le_badge_bestseller_catalog', 'le_badge_bestseller_single', 'le_badge_bestseller_color', 'le_badge_bestseller_emoji_pos', 'le_badge_bestseller_min', 'le_badge_bestseller_alpha', 'le_badge_bestseller_position',
			'le_badge_freeshipping_enable', 'le_badge_freeshipping_text', 'le_badge_freeshipping_emojis', 'le_badge_freeshipping_animation', 'le_badge_freeshipping_catalog', 'le_badge_freeshipping_single', 'le_badge_freeshipping_color', 'le_badge_freeshipping_emoji_pos', 'le_badge_freeshipping_alpha', 'le_badge_freeshipping_position',
			'le_est_local_min', 'le_est_local_max', 'le_est_backorder_min', 'le_est_backorder_max', 'le_est_import_min', 'le_est_import_max', 'le_est_exclude_weekend', 'le_est_date_format', 'le_est_bg_color', 'le_est_text_color', 'le_est_msg_local', 'le_est_msg_backorder', 'le_est_msg_import',
			'le_react_frontend_url', 'le_api_enable_endpoint', 'le_api_extend_product',
			// Geo
			'le_geo_restrict_enable', 'le_geo_cities', 'le_geo_postcodes', 'le_geo_countries', 'le_geo_states',
			// Apariencia y textos propios de la tienda (1.0.0)
			'le_etiquetas_a_off', 'le_a_badge_bg', 'le_a_badge_text', 'le_chip_bg', 'le_chip_text', 'le_pickup_text',
			// Cart estimator
			'le_cart_estimator_enable',
			// Notify
			'le_notify_enable',
			// Recent sale
			'le_recent_sale_enable', 'le_recent_sale_max_hours',
			// Presets
			'le_preset_palette', 'le_preset_badge_style', 'le_preset_font',
		];

		// Granular style controls (1.6.0): 10 campos × 8 badges = 80 opciones
		$badge_prefixes = [ 'express', 'import', 'sale', 'lowstock', 'preorder', 'new', 'bestseller', 'freeshipping' ];
		$style_suffixes = [ 'border_radius', 'border_width', 'padding', 'font_size', 'font_weight', 'bg_type', 'gradient_color', 'gradient_color_alpha', 'gradient_angle', 'shadow', 'uppercase' ];
		foreach ( $badge_prefixes as $bp ) {
			foreach ( $style_suffixes as $ss ) {
				$options[] = "le_badge_{$bp}_{$ss}";
			}
		}

		// Lógicas avanzadas (2.0.0)
		$advanced_options = [
			// Express
			'le_badge_express_min_stock', 'le_badge_express_cutoff_time', 'le_badge_express_cutoff_text',
			'le_badge_express_max_weight', 'le_badge_express_excluded_cats',
			// Importación
			'le_badge_import_country', 'le_badge_import_country_emoji',
			// Sale tiers + schedule
			'le_badge_sale_tier1_max', 'le_badge_sale_tier1_text',
			'le_badge_sale_tier2_max', 'le_badge_sale_tier2_text',
			'le_badge_sale_tier3_text',
			'le_badge_sale_schedule_from', 'le_badge_sale_schedule_to',
			'le_badge_sale_min_stock', 'le_badge_sale_exclude_preorder',
			// Lowstock tiers
			'le_badge_lowstock_critical_threshold', 'le_badge_lowstock_text_critical', 'le_badge_lowstock_anim_critical',
			'le_badge_lowstock_few_threshold', 'le_badge_lowstock_text_few',
			'le_badge_lowstock_exclude_sale',
			// Preorder
			'le_badge_preorder_show_countdown', 'le_badge_preorder_countdown_text',
			'le_badge_preorder_discount_pct', 'le_badge_preorder_auto_remove_after',
			// New tiers
			'le_badge_new_tier1_days', 'le_badge_new_tier1_text', 'le_badge_new_tier1_emoji',
			'le_badge_new_show_category',
			// Bestseller tiers
			'le_badge_bestseller_tier_gold_max', 'le_badge_bestseller_tier_gold_text', 'le_badge_bestseller_tier_gold_emoji',
			'le_badge_bestseller_tier_silver_max', 'le_badge_bestseller_tier_silver_text', 'le_badge_bestseller_tier_silver_emoji',
			'le_badge_bestseller_tier_bronze_text', 'le_badge_bestseller_tier_bronze_emoji',
			'le_badge_bestseller_per_category',
			// Free shipping avanzado
			'le_badge_freeshipping_amva_only', 'le_badge_freeshipping_show_remaining',
			'le_badge_freeshipping_remaining_text', 'le_badge_freeshipping_payment_methods',
		];
		$options = array_merge( $options, $advanced_options );

		// Mapa de opciones con sanitización específica.
		$sanitizers = [
			'le_express_methods' => [ 'type' => 'array', 'cb' => function ( $v ) {
				return is_array( $v ) ? array_values( array_filter( array_map( 'sanitize_text_field', $v ) ) ) : [];
			} ],
			'le_est_msg_local'     => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? wp_kses_post( trim( $v ) ) : '' ],
			'le_est_msg_backorder' => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? wp_kses_post( trim( $v ) ) : '' ],
			'le_est_msg_import'    => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? wp_kses_post( trim( $v ) ) : '' ],

			// Geo: lista de ciudades — comma-separated, lowercase, sin acentos forzados.
			'le_geo_cities' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$parts = array_filter( array_map( 'trim', explode( ',', (string) $v ) ) );
				$parts = array_map( 'sanitize_text_field', $parts );
				return implode( ', ', $parts );
			} ],
			// Geo: países ISO / estados WooCommerce — códigos en mayúsculas.
			'le_geo_countries' => [ 'type' => 'string', 'cb' => fn( $v ) => implode( ', ', array_filter( array_map( fn( $c ) => strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', $c ) ), explode( ',', (string) $v ) ) ) ) ],
			'le_geo_states'    => [ 'type' => 'string', 'cb' => fn( $v ) => implode( ', ', array_filter( array_map( fn( $c ) => strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', $c ) ), explode( ',', (string) $v ) ) ) ) ],
			'le_etiquetas_a_off' => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '' ],
			'le_a_badge_bg'      => [ 'type' => 'string', 'cb' => fn( $v ) => (string) sanitize_hex_color( (string) $v ) ],
			'le_a_badge_text'    => [ 'type' => 'string', 'cb' => fn( $v ) => (string) sanitize_hex_color( (string) $v ) ],
			'le_chip_bg'         => [ 'type' => 'string', 'cb' => fn( $v ) => (string) sanitize_hex_color( (string) $v ) ],
			'le_chip_text'       => [ 'type' => 'string', 'cb' => fn( $v ) => (string) sanitize_hex_color( (string) $v ) ],
			'le_pickup_text'     => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? wp_kses_post( trim( $v ) ) : '' ],
			// Geo: prefijos postales — solo dígitos.
			'le_geo_postcodes' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$parts = array_filter( array_map( 'trim', explode( ',', (string) $v ) ) );
				$parts = array_map( fn( $p ) => preg_replace( '/[^\d]/', '', $p ), $parts );
				$parts = array_filter( $parts );
				return implode( ', ', $parts );
			} ],
			// Festivos: solo fechas YYYY-MM-DD válidas.
			'le_est_holidays' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$parts = array_filter( array_map( 'trim', explode( ',', (string) $v ) ) );
				$valid = array_filter( $parts, fn( $d ) => (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && checkdate(
					(int) substr( $d, 5, 2 ),
					(int) substr( $d, 8, 2 ),
					(int) substr( $d, 0, 4 )
				) );
				return implode( ', ', $valid );
			} ],
			'le_recent_sale_max_hours' => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, min( 8760, (int) $v ) ) ],
			'le_max_badges'            => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, min( 8, (int) $v ) ) ],
			'le_badge_sale_min_discount' => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 0, min( 99, (int) $v ) ) ],
			'le_badge_lowstock_require_recent' => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_badge_lowstock_recent_window_days' => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, min( 365, (int) $v ) ) ],
			'le_badge_freeshipping_threshold' => [ 'type' => 'number', 'cb' => fn( $v ) => max( 0, (float) $v ) ],
			'le_badge_bestseller_window_days' => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 0, min( 3650, (int) $v ) ) ],

			// === Lógicas avanzadas 2.0.0 ===
			'le_badge_express_min_stock'      => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 0, (int) $v ) ],
			'le_badge_express_cutoff_time'    => [ 'type' => 'string', 'cb' => fn( $v ) => preg_match( '/^\d{2}:\d{2}$/', (string) $v ) ? $v : '' ],
			'le_badge_express_cutoff_text'    => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_express_max_weight'     => [ 'type' => 'number', 'cb' => fn( $v ) => max( 0, (float) $v ) ],
			'le_badge_express_excluded_cats'  => [ 'type' => 'array', 'cb' => fn( $v ) => is_array( $v ) ? array_map( 'absint', $v ) : [] ],

			'le_badge_import_country'         => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_import_country_emoji'   => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],

			'le_badge_sale_tier1_max'         => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, min( 99, (int) $v ) ) ],
			'le_badge_sale_tier1_text'        => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_sale_tier2_max'         => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, min( 99, (int) $v ) ) ],
			'le_badge_sale_tier2_text'        => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_sale_tier3_text'        => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_sale_schedule_from'     => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_sale_schedule_to'       => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_sale_min_stock'         => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 0, (int) $v ) ],
			'le_badge_sale_exclude_preorder'  => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],

			'le_badge_lowstock_critical_threshold' => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, (int) $v ) ],
			'le_badge_lowstock_text_critical' => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_lowstock_anim_critical' => [ 'type' => 'string', 'cb' => fn( $v ) => in_array( $v, [ 'rebote', 'pulso', 'vibracion_turbo', 'arranque_rapido' ], true ) ? $v : 'pulso' ],
			'le_badge_lowstock_few_threshold' => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, (int) $v ) ],
			'le_badge_lowstock_text_few'      => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_lowstock_exclude_sale'  => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],

			'le_badge_preorder_show_countdown'    => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_badge_preorder_countdown_text'    => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_preorder_discount_pct'      => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 0, min( 99, (int) $v ) ) ],
			'le_badge_preorder_auto_remove_after' => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],

			'le_badge_new_tier1_days'  => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, min( 365, (int) $v ) ) ],
			'le_badge_new_tier1_text'  => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_new_tier1_emoji' => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_new_show_category' => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],

			'le_badge_bestseller_tier_gold_max'    => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, (int) $v ) ],
			'le_badge_bestseller_tier_gold_text'   => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_bestseller_tier_gold_emoji'  => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_bestseller_tier_silver_max'  => [ 'type' => 'integer', 'cb' => fn( $v ) => max( 1, (int) $v ) ],
			'le_badge_bestseller_tier_silver_text' => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_bestseller_tier_silver_emoji' => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_bestseller_tier_bronze_text' => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_bestseller_tier_bronze_emoji'=> [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_bestseller_per_category'     => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],

			'le_badge_freeshipping_amva_only'         => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_badge_freeshipping_show_remaining'    => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_badge_freeshipping_remaining_text'    => [ 'type' => 'string', 'cb' => fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : '' ],
			'le_badge_freeshipping_payment_methods'   => [ 'type' => 'array', 'cb' => fn( $v ) => is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : [] ],
			'le_preset_palette' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$v = sanitize_key( $v );
				return array_key_exists( $v, \LE\Presets::PALETTES ) ? $v : 'default';
			} ],
			'le_preset_badge_style' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$v = sanitize_key( $v );
				return array_key_exists( $v, \LE\Presets::BADGE_STYLES ) ? $v : 'rounded';
			} ],
			'le_preset_font' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$v = (string) $v;
				return array_key_exists( $v, \LE\Presets::FONTS ) ? $v : '';
			} ],
			// Toggles 0/1
			'le_geo_restrict_enable'   => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_cart_estimator_enable' => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_notify_enable'         => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_recent_sale_enable'    => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_api_enable_endpoint'   => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			'le_api_extend_product'    => [ 'type' => 'string', 'cb' => fn( $v ) => $v ? '1' : '0' ],
			// CORS: lista de URLs.
			'le_react_frontend_url' => [ 'type' => 'string', 'cb' => function ( $v ) {
				$parts = array_filter( array_map( 'trim', explode( ',', (string) $v ) ) );
				$parts = array_filter( array_map( 'esc_url_raw', $parts ) );
				return implode( ', ', $parts );
			} ],
		];

		// Sanitizadores genéricos por SUFIJO para los granular (1.6.0)
		$granular_by_suffix = [
			'border_radius'  => fn( $v ) => $v === '' ? '' : (string) max( 0, min( 50, (int) $v ) ),
			'border_width'   => fn( $v ) => $v === '' ? '' : (string) max( 0, min( 5, (int) $v ) ),
			'padding'        => fn( $v ) => $v === '' ? '' : (string) max( 2, min( 20, (int) $v ) ),
			'font_size'      => fn( $v ) => $v === '' ? '' : (string) max( 8, min( 24, (int) $v ) ),
			'font_weight'    => fn( $v ) => in_array( $v, [ '', '400', '500', '600', '700', '900' ], true ) ? $v : '',
			'bg_type'        => fn( $v ) => in_array( $v, [ '', 'solid', 'gradient', 'transparent' ], true ) ? $v : '',
			'gradient_color'       => fn( $v ) => preg_match( '/^#[0-9a-fA-F]{3,8}$/', (string) $v ) ? $v : '#ffffff',
			'gradient_color_alpha' => fn( $v ) => (string) max( 0, min( 100, (int) $v ) ),
			'gradient_angle'       => fn( $v ) => (string) max( 0, min( 360, (int) $v ) ),
			'shadow'         => fn( $v ) => in_array( $v, [ '', 'none', 'soft', 'medium', 'hard', 'glow' ], true ) ? $v : '',
			'uppercase'      => fn( $v ) => $v ? '1' : '0',
		];

		foreach ( $options as $option ) {
			if ( isset( $sanitizers[ $option ] ) ) {
				$cfg = $sanitizers[ $option ];
				register_setting( 'le_settings_group', $option, [
					'type'              => $cfg['type'],
					'sanitize_callback' => $cfg['cb'],
					'autoload'          => true,
				] );
				continue;
			}
			// Detectar granular por sufijo
			$matched = false;
			foreach ( $granular_by_suffix as $suffix => $cb ) {
				if ( preg_match( '/^le_badge_[a-z]+_' . preg_quote( $suffix, '/' ) . '$/', $option ) ) {
					register_setting( 'le_settings_group', $option, [
						'type'              => 'string',
						'sanitize_callback' => $cb,
						'autoload'          => true,
					] );
					$matched = true;
					break;
				}
			}
			if ( ! $matched ) {
				register_setting( 'le_settings_group', $option, [ 'autoload' => true ] );
			}
		}
	}

	/**
	 * Renderiza la página de administración con pestañas.
	 */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$allowed_tabs = [
			'tab_envios', 'tab_badges', 'tab_estimacion', 'tab_api',
			'tab_geo', 'tab_features', 'tab_presets',
			// Compat: redirigir tabs antiguos al nuevo panel unificado
			'tab_badge_express', 'tab_badge_importacion', 'tab_badge_oferta',
			'tab_badge_lowstock', 'tab_badge_preorder', 'tab_badge_new',
			'tab_badge_bestseller', 'tab_badge_freeshipping',
		];
		$requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'tab_envios';
		$active_tab = in_array( $requested_tab, $allowed_tabs, true ) ? $requested_tab : 'tab_envios';
		// Cualquier tab_badge_* viejo se mapea al panel unificado
		if ( 0 === strpos( $active_tab, 'tab_badge_' ) ) {
			$active_tab = 'tab_badges';
		}
		?>
		<?php
		// Inyecta el CSS del preset activo en admin para que la vista previa use el mismo look del frontend.
		if ( class_exists( '\\LE\\Presets' ) ) {
			\LE\Presets::get_instance()->inject_css();
		}
		?>
		<style>
			.le-tab-content { display: none; }
			.le-tab-content.active { display: block; }
			table.le-tab-content.active { display: table; }

			/* Vista previa de badge */
			.le-preview-card {
				display: flex;
				align-items: center;
				gap: 14px;
				padding: 16px 18px;
				margin: 8px 0 14px;
				background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
				border: 1px dashed #94a3b8;
				border-radius: 8px;
				min-height: 60px;
			}
			.le-preview-label {
				font-size: 11px;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 0.05em;
				color: #64748b;
			}
			.le-preview-canvas {
				flex: 1;
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				gap: 8px;
				min-height: 30px;
			}
			.le-preview-canvas:empty::before {
				content: '(activa el badge para ver la vista previa)';
				font-size: 12px;
				color: #94a3b8;
				font-style: italic;
			}
			/* Asegura que las animaciones del frontend funcionen también dentro del admin */
			.le-preview-canvas .le-badge { display: inline-flex; align-items: center; gap: 4px; }

			/* Acordeón de Badges */
			.le-badge-section {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 8px;
				margin: 0 0 10px 0;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				overflow: hidden;
				transition: box-shadow .15s ease;
			}
			.le-badge-section[open] {
				box-shadow: 0 2px 6px rgba(0,0,0,.08);
			}
			.le-badge-section > summary {
				cursor: pointer;
				padding: 14px 18px;
				font-size: 14px;
				list-style: none;
				display: flex;
				align-items: center;
				gap: 8px;
				user-select: none;
				transition: background .15s ease;
			}
			.le-badge-section > summary:hover { background: #f6f7f7; }
			.le-badge-section[open] > summary {
				border-bottom: 1px solid #e5e7eb;
				background: #f9fafb;
			}
			.le-badge-section > summary::-webkit-details-marker { display: none; }
			.le-badge-section > summary::before {
				content: '▸';
				display: inline-block;
				width: 14px;
				color: #2271b1;
				transition: transform .15s ease;
				font-size: 11px;
			}
			.le-badge-section[open] > summary::before {
				transform: rotate(90deg);
			}
			.le-badge-section > table {
				margin: 0;
				padding: 0 18px 12px;
			}
			.le-badge-status {
				margin-left: auto;
				font-size: 12px;
				padding: 2px 10px;
				border-radius: 999px;
				font-weight: 500;
			}
			.le-badge-status.is-on { background: #d1fae5; color: #065f46; }
			.le-badge-status.is-off { background: #f3f4f6; color: #6b7280; }

			/* Sub-grupos del estilo individual */
			h5.le-style-group {
				margin: 14px 0 4px;
				padding: 6px 12px;
				font-size: 12px;
				font-weight: 600;
				letter-spacing: 0.04em;
				background: #f1f5f9;
				border-left: 3px solid #2271b1;
				color: #334155;
				border-radius: 3px;
			}

			/* Replicate button + popover */
			.le-replicate-wrap { margin: 8px 0 0; position: relative; display: inline-block; }
			.le-replicate-btn {
				background: #fff; border: 1px solid #2271b1; color: #2271b1;
				padding: 4px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;
			}
			.le-replicate-btn:hover { background: #f0f7ff; }
			.le-replicate-popover {
				position: absolute; top: calc(100% + 6px); left: 0;
				background: #fff; border: 1px solid #ccd0d4; border-radius: 8px;
				box-shadow: 0 4px 14px rgba(0,0,0,0.12);
				padding: 12px 14px; min-width: 240px; z-index: 9999;
			}
			.le-replicate-popover label { display: block; padding: 4px 0; cursor: pointer; font-size: 13px; }
			.le-replicate-popover label input { margin-right: 6px; vertical-align: middle; }
			.le-replicate-popover .le-replicate-actions { margin-top: 10px; display: flex; gap: 6px; justify-content: flex-end; }
			.le-replicate-popover .le-replicate-actions button { padding: 4px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
			.le-replicate-popover .le-replicate-cancel { background: #fff; border: 1px solid #ccd0d4; }
			.le-replicate-popover .le-replicate-apply { background: #2271b1; color: #fff; border: 1px solid #2271b1; }
		</style>
		<div class="wrap">
			<h1>Etiquetas y envíouración</h1>
			<?php settings_errors(); ?>
			<h2 class="nav-tab-wrapper" id="le-tab-nav">
				<a href="#" class="nav-tab <?php echo $active_tab == 'tab_envios' ? 'nav-tab-active' : ''; ?>" data-tab="tab_envios">Envíos y Reglas</a>
				<a href="#tab_badges" class="nav-tab le-nav-tab <?php echo 'tab_badges' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab_badges">🏷️ Badges</a>
				<a href="#tab_estimacion" class="nav-tab le-nav-tab <?php echo 'tab_estimacion' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab_estimacion">Fechas Estimadas</a>
				<a href="#tab_geo" class="nav-tab le-nav-tab <?php echo 'tab_geo' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab_geo">📍 Geo</a>
				<a href="#tab_features" class="nav-tab le-nav-tab <?php echo 'tab_features' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab_features">✨ Features</a>
				<a href="#tab_presets" class="nav-tab le-nav-tab <?php echo 'tab_presets' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab_presets">🎨 Presets</a>
				<a href="#" class="nav-tab <?php echo $active_tab == 'tab_api' ? 'nav-tab-active' : ''; ?>" data-tab="tab_api">React / API</a>
			</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'le_settings_group' ); ?>
				
				<table class="form-table le-tab-content <?php echo 'tab_envios' === $active_tab ? 'active' : ''; ?>" id="tab_envios">
					<tr>
						<th>Métodos Express</th>
						<td>
							<select name="le_express_methods[]" multiple style="width:300px; height: 100px;">
								<?php 
								$zones = \WC_Shipping_Zones::get_zones();
								$selected_methods = get_option( 'le_express_methods', [] ) ?: [];
								if ( ! is_array( $selected_methods ) ) { $selected_methods = []; }
								foreach ( $zones as $zone ) {
									foreach ( $zone['shipping_methods'] as $method ) {
										$val = $method->id . ':' . $method->instance_id;
										$sel = in_array( $val, $selected_methods, true ) ? 'selected' : '';
										echo "<option value='".esc_attr($val)."' $sel>".esc_html($zone['zone_name'] . ' - ' . $method->title)."</option>";
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th>Tag de Importado</th>
						<td>
							<select name="le_import_tag_id">
								<option value="">Seleccionar Tag</option>
								<?php
								$tags = get_terms( [ 'taxonomy' => 'product_tag', 'hide_empty' => false ] );
								$selected_tag = get_option( 'le_import_tag_id', '' );
								if ( ! is_wp_error( $tags ) ) {
									foreach ( $tags as $tag ) {
										$sel = $tag->term_id == $selected_tag ? 'selected' : '';
										echo "<option value='".esc_attr((string)$tag->term_id)."' $sel>".esc_html($tag->name)."</option>";
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th>Aviso Carrito Mixto</th>
						<td>
							<textarea name="le_mixed_cart_notice" rows="4" cols="50"><?php echo esc_textarea( get_option( 'le_mixed_cart_notice', 'Tu carrito contiene artículos bajo pedido o importación. El envío express ha sido deshabilitado.' ) ); ?></textarea>
						</td>
					</tr>
				</table>

				<div id="tab_badges" class="le-tab-content <?php echo 'tab_badges' === $active_tab ? 'active' : ''; ?>">
				<p class="description" style="margin: 8px 0 16px;">Click en cualquier badge para expandir sus opciones individuales.</p>
				<details class="le-badge-section" data-badge-id="express"><summary>⚡🚀 <strong>Badge Express</strong> <span class="le-badge-status" data-option="le_badge_express_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="express"></div></div></td></tr>
					<tr><th>Habilitar Badge Express</th><td><input type="hidden" name="le_badge_express_enable" value="0"><input type="checkbox" name="le_badge_express_enable" value="1" <?php checked(get_option('le_badge_express_enable', '1'), '1'); ?>></td></tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_express_text" value="<?php echo esc_attr( get_option( 'le_badge_express_text', 'apto para envío express' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_express_emojis" value="<?php echo esc_attr( get_option( 'le_badge_express_emojis', '⚡🚀' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_express_animation">
								<?php $anim = get_option('le_badge_express_animation', 'rebote'); ?>
								<option value="rebote" <?php selected($anim, 'rebote'); ?>>Rebote</option>
								<option value="arranque_rapido" <?php selected($anim, 'arranque_rapido'); ?>>Arranque rápido</option>
								<option value="pulso" <?php selected($anim, 'pulso'); ?>>Pulso</option>
								<option value="vibracion_turbo" <?php selected($anim, 'vibracion_turbo'); ?>>Vibración turbo</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_express_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_express_emoji_pos', 'right'), 'right'); ?>>A la derecha (Texto Emoji)</option>
								<option value="left" <?php selected(get_option('le_badge_express_emoji_pos', 'right'), 'left'); ?>>A la izquierda (Emoji Texto)</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_express_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_express_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_express_catalog" value="0"><input type="checkbox" name="le_badge_express_catalog" value="1" <?php checked(get_option('le_badge_express_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_express_single" value="0"><input type="checkbox" name="le_badge_express_single" value="1" <?php checked(get_option('le_badge_express_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'express' ); ?>
					<?php $this->render_advanced_logic( 'express' ); ?>
				</table>
				</details>

				<details class="le-badge-section" data-badge-id="import"><summary>✈️🌍 <strong>Badge Importación</strong> <span class="le-badge-status" data-option="le_badge_import_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="import"></div></div></td></tr>
					<tr><th>Habilitar Badge Importación</th><td><input type="hidden" name="le_badge_import_enable" value="0"><input type="checkbox" name="le_badge_import_enable" value="1" <?php checked(get_option('le_badge_import_enable', '1'), '1'); ?>></td></tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_import_text" value="<?php echo esc_attr( get_option( 'le_badge_import_text', 'Importación Directa' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_import_emojis" value="<?php echo esc_attr( get_option( 'le_badge_import_emojis', '✈️🌍' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_import_animation">
								<?php $animi = get_option('le_badge_import_animation', 'vuelo_suave'); ?>
								<option value="vuelo_suave" <?php selected($animi, 'vuelo_suave'); ?>>Vuelo suave</option>
								<option value="flotacion" <?php selected($animi, 'flotacion'); ?>>Flotación</option>
								<option value="pulso_lento" <?php selected($animi, 'pulso_lento'); ?>>Pulso lento</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_import_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_import_emoji_pos', 'right'), 'right'); ?>>A la derecha (Texto Emoji)</option>
								<option value="left" <?php selected(get_option('le_badge_import_emoji_pos', 'right'), 'left'); ?>>A la izquierda (Emoji Texto)</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_import_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_import_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_import_catalog" value="0"><input type="checkbox" name="le_badge_import_catalog" value="1" <?php checked(get_option('le_badge_import_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_import_single" value="0"><input type="checkbox" name="le_badge_import_single" value="1" <?php checked(get_option('le_badge_import_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'import' ); ?>
					<?php $this->render_advanced_logic( 'import' ); ?>
				</table>
				</details>

				<details class="le-badge-section" data-badge-id="sale"><summary>🔥 <strong>Badge Oferta</strong> <span class="le-badge-status" data-option="le_badge_sale_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="sale"></div></div></td></tr>
					<tr>
						<td colspan="2">
							<p><em>Activa esta opción para ocultar la etiqueta por defecto de WooCommerce ("OFERTA") y reemplazarla por el badge minimalista de Etiquetas y envío.</em></p>
						</td>
					</tr>
					<tr><th>Habilitar Badge Oferta Propio</th><td><input type="hidden" name="le_badge_sale_enable" value="0"><input type="checkbox" name="le_badge_sale_enable" value="1" <?php checked(get_option('le_badge_sale_enable', '1'), '1'); ?>></td></tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_sale_text" value="<?php echo esc_attr( get_option( 'le_badge_sale_text', 'OFERTA' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_sale_emojis" value="<?php echo esc_attr( get_option( 'le_badge_sale_emojis', '🔥' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_sale_animation">
								<?php $anims = get_option('le_badge_sale_animation', 'pulso'); ?>
								<option value="rebote" <?php selected($anims, 'rebote'); ?>>Rebote</option>
								<option value="arranque_rapido" <?php selected($anims, 'arranque_rapido'); ?>>Arranque rápido</option>
								<option value="pulso" <?php selected($anims, 'pulso'); ?>>Pulso</option>
								<option value="vibracion_turbo" <?php selected($anims, 'vibracion_turbo'); ?>>Vibración turbo</option>
							</select>
						</td>
					</tr>
					<tr><th>Descuento mínimo (%)</th>
						<td>
							<input type="number" name="le_badge_sale_min_discount" min="0" max="99" value="<?php echo esc_attr( get_option( 'le_badge_sale_min_discount', '10' ) ); ?>">
							<p class="description">No mostrar el badge si el descuento es menor a este %. Default: 10. Pon <code>0</code> para mostrar siempre.</p>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_sale_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_sale_emoji_pos', 'right'), 'right'); ?>>A la derecha (Texto Emoji)</option>
								<option value="left" <?php selected(get_option('le_badge_sale_emoji_pos', 'right'), 'left'); ?>>A la izquierda (Emoji Texto)</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_sale_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_sale_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_sale_catalog" value="0"><input type="checkbox" name="le_badge_sale_catalog" value="1" <?php checked(get_option('le_badge_sale_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_sale_single" value="0"><input type="checkbox" name="le_badge_sale_single" value="1" <?php checked(get_option('le_badge_sale_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'sale' ); ?>
					<?php $this->render_advanced_logic( 'sale' ); ?>
				</table>
				</details>

				<!-- Badge Últimas Unidades -->
				<details class="le-badge-section" data-badge-id="lowstock"><summary>⏳ <strong>Badge Últimas Unidades</strong> <span class="le-badge-status" data-option="le_badge_lowstock_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="lowstock"></div></div></td></tr>
					<tr><th>Habilitar Badge</th><td><input type="hidden" name="le_badge_lowstock_enable" value="0"><input type="checkbox" name="le_badge_lowstock_enable" value="1" <?php checked(get_option('le_badge_lowstock_enable', '0'), '1'); ?>></td></tr>
					<tr><th>Umbral de inventario</th><td><input type="number" name="le_badge_lowstock_threshold" value="<?php echo esc_attr( get_option( 'le_badge_lowstock_threshold', '3' ) ); ?>" min="1"> <p class="description">Mostrar cuando el stock sea menor o igual a este número. Tip: usa <code>{stock}</code> en el texto para mostrar la cantidad real (ej: <em>"Solo {stock}"</em>).</p></td></tr>
					<tr><th>Solo si hubo venta reciente</th>
						<td>
							<input type="hidden" name="le_badge_lowstock_require_recent" value="0">
							<input type="checkbox" name="le_badge_lowstock_require_recent" value="1" <?php checked( get_option( 'le_badge_lowstock_require_recent', '0' ), '1' ); ?>>
							<p class="description">Si está activo, no mostrar "Últimas Unidades" en productos sin movimiento (evita escasez falsa por bajo interés).</p>
						</td>
					</tr>
					<tr><th>Ventana de venta reciente (días)</th>
						<td>
							<input type="number" name="le_badge_lowstock_recent_window_days" min="1" max="365" value="<?php echo esc_attr( get_option( 'le_badge_lowstock_recent_window_days', '60' ) ); ?>">
							<p class="description">Solo aplica si "venta reciente" está activo arriba.</p>
						</td>
					</tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_lowstock_text" value="<?php echo esc_attr( get_option( 'le_badge_lowstock_text', 'Últimas Unidades' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_lowstock_emojis" value="<?php echo esc_attr( get_option( 'le_badge_lowstock_emojis', '⏳' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_lowstock_animation">
								<?php $anim = get_option('le_badge_lowstock_animation', 'pulso'); ?>
								<option value="rebote" <?php selected($anim, 'rebote'); ?>>Rebote</option>
								<option value="arranque_rapido" <?php selected($anim, 'arranque_rapido'); ?>>Arranque rápido</option>
								<option value="pulso" <?php selected($anim, 'pulso'); ?>>Pulso</option>
								<option value="vibracion_turbo" <?php selected($anim, 'vibracion_turbo'); ?>>Vibración turbo</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_lowstock_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_lowstock_emoji_pos', 'right'), 'right'); ?>>A la derecha</option>
								<option value="left" <?php selected(get_option('le_badge_lowstock_emoji_pos', 'right'), 'left'); ?>>A la izquierda</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_lowstock_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_lowstock_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_lowstock_catalog" value="0"><input type="checkbox" name="le_badge_lowstock_catalog" value="1" <?php checked(get_option('le_badge_lowstock_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_lowstock_single" value="0"><input type="checkbox" name="le_badge_lowstock_single" value="1" <?php checked(get_option('le_badge_lowstock_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'lowstock' ); ?>
					<?php $this->render_advanced_logic( 'lowstock' ); ?>
				</table>
				</details>

				<!-- Badge Preventa -->
				<details class="le-badge-section" data-badge-id="preorder"><summary>🗓️ <strong>Badge Preventa</strong> <span class="le-badge-status" data-option="le_badge_preorder_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="preorder"></div></div></td></tr>
					<tr><th>Habilitar Badge</th><td><input type="hidden" name="le_badge_preorder_enable" value="0"><input type="checkbox" name="le_badge_preorder_enable" value="1" <?php checked(get_option('le_badge_preorder_enable', '0'), '1'); ?>> <p class="description">Se muestra cuando el producto permite reservas o tiene la etiqueta "preventa".</p></td></tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_preorder_text" value="<?php echo esc_attr( get_option( 'le_badge_preorder_text', 'Preventa' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_preorder_emojis" value="<?php echo esc_attr( get_option( 'le_badge_preorder_emojis', '🗓️' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_preorder_animation">
								<?php $anim = get_option('le_badge_preorder_animation', 'flotacion'); ?>
								<option value="rebote" <?php selected($anim, 'rebote'); ?>>Rebote</option>
								<option value="flotacion" <?php selected($anim, 'flotacion'); ?>>Flotación</option>
								<option value="pulso" <?php selected($anim, 'pulso'); ?>>Pulso</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_preorder_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_preorder_emoji_pos', 'right'), 'right'); ?>>A la derecha</option>
								<option value="left" <?php selected(get_option('le_badge_preorder_emoji_pos', 'right'), 'left'); ?>>A la izquierda</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_preorder_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_preorder_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_preorder_catalog" value="0"><input type="checkbox" name="le_badge_preorder_catalog" value="1" <?php checked(get_option('le_badge_preorder_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_preorder_single" value="0"><input type="checkbox" name="le_badge_preorder_single" value="1" <?php checked(get_option('le_badge_preorder_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'preorder' ); ?>
					<?php $this->render_advanced_logic( 'preorder' ); ?>
				</table>
				</details>

				<!-- Badge Novedad -->
				<details class="le-badge-section" data-badge-id="new"><summary>✨ <strong>Badge Novedad</strong> <span class="le-badge-status" data-option="le_badge_new_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="new"></div></div></td></tr>
					<tr><th>Habilitar Badge</th><td><input type="hidden" name="le_badge_new_enable" value="0"><input type="checkbox" name="le_badge_new_enable" value="1" <?php checked(get_option('le_badge_new_enable', '0'), '1'); ?>></td></tr>
					<tr><th>Días de vigencia</th><td><input type="number" name="le_badge_new_days" value="<?php echo esc_attr( get_option( 'le_badge_new_days', '15' ) ); ?>" min="1"> <p class="description">Mostrar en productos creados en los últimos X días.</p></td></tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_new_text" value="<?php echo esc_attr( get_option( 'le_badge_new_text', 'Novedad' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_new_emojis" value="<?php echo esc_attr( get_option( 'le_badge_new_emojis', '✨' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_new_animation">
								<?php $anim = get_option('le_badge_new_animation', 'arranque_rapido'); ?>
								<option value="rebote" <?php selected($anim, 'rebote'); ?>>Rebote</option>
								<option value="arranque_rapido" <?php selected($anim, 'arranque_rapido'); ?>>Arranque rápido</option>
								<option value="pulso" <?php selected($anim, 'pulso'); ?>>Pulso</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_new_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_new_emoji_pos', 'right'), 'right'); ?>>A la derecha</option>
								<option value="left" <?php selected(get_option('le_badge_new_emoji_pos', 'right'), 'left'); ?>>A la izquierda</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_new_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_new_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_new_catalog" value="0"><input type="checkbox" name="le_badge_new_catalog" value="1" <?php checked(get_option('le_badge_new_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_new_single" value="0"><input type="checkbox" name="le_badge_new_single" value="1" <?php checked(get_option('le_badge_new_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'new' ); ?>
					<?php $this->render_advanced_logic( 'new' ); ?>
				</table>
				</details>

				<!-- Badge Bestseller -->
				<details class="le-badge-section" data-badge-id="bestseller"><summary>🏆 <strong>Badge Bestseller</strong> <span class="le-badge-status" data-option="le_badge_bestseller_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="bestseller"></div></div></td></tr>
					<tr><th>Habilitar Badge</th><td><input type="hidden" name="le_badge_bestseller_enable" value="0"><input type="checkbox" name="le_badge_bestseller_enable" value="1" <?php checked(get_option('le_badge_bestseller_enable', '0'), '1'); ?>></td></tr>
					<tr><th>Ventas mínimas</th><td><input type="number" name="le_badge_bestseller_min" value="<?php echo esc_attr( get_option( 'le_badge_bestseller_min', '50' ) ); ?>" min="1"> <p class="description">Mostrar en productos con X o más ventas dentro de la ventana temporal.</p></td></tr>
					<tr><th>Ventana temporal (días)</th>
						<td>
							<input type="number" name="le_badge_bestseller_window_days" min="0" max="3650" value="<?php echo esc_attr( get_option( 'le_badge_bestseller_window_days', '90' ) ); ?>">
							<p class="description">Cuenta ventas en los últimos X días (default 90). Pon <code>0</code> para usar ventas all-time (más rápido pero menos relevante).</p>
						</td>
					</tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_bestseller_text" value="<?php echo esc_attr( get_option( 'le_badge_bestseller_text', 'Más Vendido' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_bestseller_emojis" value="<?php echo esc_attr( get_option( 'le_badge_bestseller_emojis', '🏆' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_bestseller_animation">
								<?php $anim = get_option('le_badge_bestseller_animation', 'pulso'); ?>
								<option value="rebote" <?php selected($anim, 'rebote'); ?>>Rebote</option>
								<option value="arranque_rapido" <?php selected($anim, 'arranque_rapido'); ?>>Arranque rápido</option>
								<option value="pulso" <?php selected($anim, 'pulso'); ?>>Pulso</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_bestseller_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_bestseller_emoji_pos', 'right'), 'right'); ?>>A la derecha</option>
								<option value="left" <?php selected(get_option('le_badge_bestseller_emoji_pos', 'right'), 'left'); ?>>A la izquierda</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_bestseller_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_bestseller_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_bestseller_catalog" value="0"><input type="checkbox" name="le_badge_bestseller_catalog" value="1" <?php checked(get_option('le_badge_bestseller_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_bestseller_single" value="0"><input type="checkbox" name="le_badge_bestseller_single" value="1" <?php checked(get_option('le_badge_bestseller_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'bestseller' ); ?>
					<?php $this->render_advanced_logic( 'bestseller' ); ?>
				</table>
				</details>

				<!-- Badge Envío Gratis -->
				<details class="le-badge-section" data-badge-id="freeshipping"><summary>🆓 <strong>Badge Envío Gratis</strong> <span class="le-badge-status" data-option="le_badge_freeshipping_enable"></span></summary>
				<table class="form-table">
				<tr><td colspan="2"><div class="le-preview-card"><span class="le-preview-label">Vista previa:</span><div class="le-preview-canvas" data-badge-id="freeshipping"></div></div></td></tr>
					<tr><th>Habilitar Badge</th><td><input type="hidden" name="le_badge_freeshipping_enable" value="0"><input type="checkbox" name="le_badge_freeshipping_enable" value="1" <?php checked(get_option('le_badge_freeshipping_enable', '0'), '1'); ?>> <p class="description">Se muestra si la clase de envío contiene "gratis" o el producto tiene la etiqueta "envio-gratis".</p></td></tr>
					<tr><th>Texto</th><td><input type="text" name="le_badge_freeshipping_text" value="<?php echo esc_attr( get_option( 'le_badge_freeshipping_text', 'Envío Gratis' ) ); ?>"></td></tr>
					<tr><th>Emojis</th><td><input type="text" name="le_badge_freeshipping_emojis" value="<?php echo esc_attr( get_option( 'le_badge_freeshipping_emojis', '🆓' ) ); ?>"></td></tr>
					<tr><th>Animación</th>
						<td>
							<select name="le_badge_freeshipping_animation">
								<?php $anim = get_option('le_badge_freeshipping_animation', 'vuelo_suave'); ?>
								<option value="vuelo_suave" <?php selected($anim, 'vuelo_suave'); ?>>Vuelo suave</option>
								<option value="flotacion" <?php selected($anim, 'flotacion'); ?>>Flotación</option>
								<option value="pulso" <?php selected($anim, 'pulso'); ?>>Pulso</option>
							</select>
						</td>
					</tr>
					<tr><th>Umbral de carrito ($)</th>
						<td>
							<input type="number" name="le_badge_freeshipping_threshold" min="0" step="1000" value="<?php echo esc_attr( get_option( 'le_badge_freeshipping_threshold', '0' ) ); ?>">
							<p class="description">Si > 0, el badge se muestra siempre (no solo en productos con clase/tag) y soporta el placeholder <code>{threshold}</code> en el texto. Ej: <em>"Envío Gratis sobre {threshold}"</em>. Default: 0 (desactivado).</p>
						</td>
					</tr>
					<tr><th>Posición del Emoji</th>
						<td>
							<select name="le_badge_freeshipping_emoji_pos">
								<option value="right" <?php selected(get_option('le_badge_freeshipping_emoji_pos', 'right'), 'right'); ?>>A la derecha</option>
								<option value="left" <?php selected(get_option('le_badge_freeshipping_emoji_pos', 'right'), 'left'); ?>>A la izquierda</option>
							</select>
						</td>
					</tr>
					<tr><th>Posición en la carátula</th>
						<td>
							<select name="le_badge_freeshipping_position">
								<?php foreach ( array( 'top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'center-left' => 'Centro izquierda', 'center-center' => 'Centro', 'center-right' => 'Centro derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha' ) as $le_pk => $le_pl ) : ?>
									<option value="<?php echo esc_attr( $le_pk ); ?>" <?php selected( get_option( 'le_badge_freeshipping_position', 'bottom-right' ), $le_pk ); ?>><?php echo esc_html( $le_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th>Mostrar en catálogo</th><td><input type="hidden" name="le_badge_freeshipping_catalog" value="0"><input type="checkbox" name="le_badge_freeshipping_catalog" value="1" <?php checked(get_option('le_badge_freeshipping_catalog', '1'), '1'); ?>></td></tr>
					<tr><th>Mostrar en producto individual</th><td><input type="hidden" name="le_badge_freeshipping_single" value="0"><input type="checkbox" name="le_badge_freeshipping_single" value="1" <?php checked(get_option('le_badge_freeshipping_single', '1'), '1'); ?>></td></tr>
					<?php $this->render_granular_style_controls( 'freeshipping' ); ?>
					<?php $this->render_advanced_logic( 'freeshipping' ); ?>
				</table>
				</details>
				</div><!-- /#tab_badges -->

				<div class="le-tab-content <?php echo 'tab_estimacion' === $active_tab ? 'active' : ''; ?>" id="tab_estimacion">
					<table class="form-table">
						<tr><th>Local Min/Max</th>
							<td>
								<input type="number" name="le_est_local_min" value="<?php echo absint(get_option('le_est_local_min', 1)); ?>" style="width:60px"> - 
								<input type="number" name="le_est_local_max" value="<?php echo absint(get_option('le_est_local_max', 3)); ?>" style="width:60px">
							</td>
						</tr>
						<tr><th>Encargo Min/Max</th>
							<td>
								<input type="number" name="le_est_backorder_min" value="<?php echo absint(get_option('le_est_backorder_min', 7)); ?>" style="width:60px"> - 
								<input type="number" name="le_est_backorder_max" value="<?php echo absint(get_option('le_est_backorder_max', 15)); ?>" style="width:60px">
							</td>
						</tr>
						<tr><th>Importado Min/Max</th>
							<td>
								<input type="number" name="le_est_import_min" value="<?php echo absint(get_option('le_est_import_min', 15)); ?>" style="width:60px"> - 
								<input type="number" name="le_est_import_max" value="<?php echo absint(get_option('le_est_import_max', 30)); ?>" style="width:60px">
							</td>
						</tr>
						<tr><th>Excluir fines de semana</th><td><input type="hidden" name="le_est_exclude_weekend" value="0"><input type="checkbox" name="le_est_exclude_weekend" value="1" <?php checked(get_option('le_est_exclude_weekend', '1'), '1'); ?>></td></tr>
						<tr><th>Formato de fecha</th>
							<td>
								<select name="le_est_date_format">
									<?php $fmt = get_option('le_est_date_format', 'l j \d\e F'); ?>
									<option value="l j \d\e F" <?php selected($fmt, 'l j \d\e F'); ?>>lunes 4 de mayo</option>
									<option value="d/m/Y" <?php selected($fmt, 'd/m/Y'); ?>>04/05/2025</option>
									<option value="j M" <?php selected($fmt, 'j M'); ?>>4 may</option>
								</select>
							</td>
						</tr>
						<tr><th>Color de Fondo</th><td><input type="color" name="le_est_bg_color" value="<?php echo esc_attr( get_option( 'le_est_bg_color', '#1a1a1a' ) ); ?>"></td></tr>
						<tr><th>Festivos (no hábiles)</th>
							<td>
								<textarea name="le_est_holidays" rows="3" cols="50" placeholder="2026-01-01, 2026-05-01, 2026-12-25"><?php echo esc_textarea( get_option( 'le_est_holidays', '' ) ); ?></textarea>
								<p class="description">Fechas YYYY-MM-DD separadas por coma. Se excluyen del cálculo de días hábiles junto con los fines de semana.</p>
							</td>
						</tr>
					</table>

					<h3>Mensajes Personalizados bajo la fecha</h3>
					<p>Configura el texto secundario (meta) que aparecerá en la caja de entrega para cada tipo de disponibilidad.</p>
					<table class="form-table">
						<tr><th>Mensaje para Express (Local)</th><td><input type="text" name="le_est_msg_local" class="regular-text" value="<?php echo esc_attr( get_option( 'le_est_msg_local', 'Apto para envío express ⚡🚀' ) ); ?>"></td></tr>
						<tr><th>Mensaje para Bajo Pedido</th><td><input type="text" name="le_est_msg_backorder" class="regular-text" value="<?php echo esc_attr( get_option( 'le_est_msg_backorder', 'Disponible bajo pedido 📦 o escribe para envío express ⚡' ) ); ?>"></td></tr>
						<tr><th>Mensaje para Importación</th><td><input type="text" name="le_est_msg_import" class="regular-text" value="<?php echo esc_attr( get_option( 'le_est_msg_import', 'Importación Directa ✈️🌍 o escribe para envío express ⚡' ) ); ?>"></td></tr>
						<tr><th>Línea extra bajo la caja (opcional)</th><td><input type="text" name="le_pickup_text" class="large-text" value="<?php echo esc_attr( get_option( 'le_pickup_text', '' ) ); ?>" placeholder="Recógelo gratis en nuestra tienda avisando 2 h antes."><p class="description">Por ejemplo, recogida en tienda. Admite HTML básico. Vacío = no se muestra.</p></td></tr>
					</table>
				</div>

				<table class="form-table le-tab-content <?php echo 'tab_api' === $active_tab ? 'active' : ''; ?>" id="tab_api">
					<tr><th>URLs React Frontend (CORS, separadas por coma)</th><td><input type="text" name="le_react_frontend_url" class="regular-text" value="<?php echo esc_attr( get_option( 'le_react_frontend_url', '' ) ); ?>" placeholder="https://app.example.com, https://staging.example.com"><p class="description">Solo estos orígenes podrán hacer requests cross-origin.</p></td></tr>
					<tr><th>Habilitar Endpoint /cart-shipping</th><td><input type="hidden" name="le_api_enable_endpoint" value="0"><input type="checkbox" name="le_api_enable_endpoint" value="1" <?php checked(get_option('le_api_enable_endpoint', '1'), '1'); ?>></td></tr>
					<tr><th>Extender /wc/v3/products</th><td><input type="hidden" name="le_api_extend_product" value="0"><input type="checkbox" name="le_api_extend_product" value="1" <?php checked(get_option('le_api_extend_product', '1'), '1'); ?>></td></tr>
				</table>
				
				<!-- Geo -->
				<table class="form-table le-tab-content <?php echo 'tab_geo' === $active_tab ? 'active' : ''; ?>" id="tab_geo">
					<tr><td colspan="2"><p><em>Restringe el envío express a una zona geográfica. Útil si solo despachas express en tu ciudad/área metropolitana.</em></p></td></tr>
					<tr><th>Habilitar restricción geográfica</th>
						<td>
							<input type="hidden" name="le_geo_restrict_enable" value="0">
							<input type="checkbox" name="le_geo_restrict_enable" value="1" <?php checked( get_option( 'le_geo_restrict_enable', '0' ), '1' ); ?>>
							<p class="description">Si está activo, los métodos express solo se mostrarán a clientes dentro de las ciudades o códigos postales listados abajo.</p>
						</td>
					</tr>
					<tr><th>Ciudades permitidas (separadas por coma)</th>
						<td>
							<textarea name="le_geo_cities" rows="3" cols="60" placeholder="ciudad uno, ciudad dos"><?php echo esc_textarea( get_option( 'le_geo_cities', implode( ', ', \LE\Geo::DEFAULT_CITIES ) ) ); ?></textarea>
							<p class="description">No importan acentos ni mayúsculas. Coincide con el campo "Ciudad" del checkout.</p>
						</td>
					</tr>
					<tr><th>Prefijos de código postal</th>
						<td>
							<input type="text" class="regular-text" name="le_geo_postcodes" value="<?php echo esc_attr( get_option( 'le_geo_postcodes', implode( ', ', \LE\Geo::DEFAULT_POSTCODE_PREFIXES ) ) ); ?>">
							<p class="description">Prefijos separados por coma (p. ej. 050, 051). Vacío por defecto. Coincide si el código del cliente empieza con cualquiera.</p>
						</td>
					</tr>
					<tr><th>Países permitidos (códigos ISO)</th>
						<td>
							<input type="text" class="regular-text" name="le_geo_countries" value="<?php echo esc_attr( get_option( 'le_geo_countries', '' ) ); ?>" placeholder="CO, MX">
							<p class="description">Vacío = cualquier país.</p>
						</td>
					</tr>
					<tr><th>Estados / departamentos permitidos</th>
						<td>
							<input type="text" class="regular-text" name="le_geo_states" value="<?php echo esc_attr( get_option( 'le_geo_states', '' ) ); ?>" placeholder="ANT">
							<p class="description">Códigos de estado de WooCommerce. Vacío = cualquiera. Si no hay ciudades ni prefijos, basta con país/estado.</p>
						</td>
					</tr>
				</table>

				<!-- Features -->
				<table class="form-table le-tab-content <?php echo 'tab_features' === $active_tab ? 'active' : ''; ?>" id="tab_features">
					<tr><td colspan="2"><h3 style="margin:0">🏷️ Modo «A · Mínima» y colores</h3></td></tr>
					<tr><th>Apagar modo «A · Mínima»</th>
						<td>
							<input type="hidden" name="le_etiquetas_a_off" value="">
							<input type="checkbox" name="le_etiquetas_a_off" value="1" <?php checked( (string) get_option( 'le_etiquetas_a_off', '' ), '1' ); ?>>
							<p class="description">Encendido (por defecto): una sola etiqueta por portada (oferta o existencias) y líneas de servicio en la ficha. Marcado: etiquetas clásicas.</p>
						</td>
					</tr>
					<?php foreach ( [ 'le_a_badge_bg' => 'Fondo de la etiqueta (modo A)', 'le_a_badge_text' => 'Texto de la etiqueta (modo A)', 'le_chip_bg' => 'Fondo del chip (modo clásico)', 'le_chip_text' => 'Texto del chip (modo clásico)' ] as $le_opt => $le_label ) : ?>
					<tr><th><?php echo esc_html( $le_label ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $le_opt ); ?>" value="<?php echo esc_attr( (string) get_option( $le_opt, '' ) ); ?>" placeholder="<?php echo esc_attr( str_ends_with( $le_opt, '_text' ) ? '#ffffff' : '#1f2937' ); ?>" pattern="#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?"><p class="description">Hexadecimal. Vacío = valor por defecto.</p></td>
					</tr>
					<?php endforeach; ?>
					<tr><td colspan="2"><h3 style="margin:0">🎯 Reglas globales de badges</h3></td></tr>
					<tr><th>Máximo de badges visibles por producto</th>
						<td>
							<input type="number" name="le_max_badges" min="1" max="8" value="<?php echo esc_attr( get_option( 'le_max_badges', '3' ) ); ?>">
							<p class="description">Si un producto califica para más, se muestran los de mayor prioridad. Default: 3.<br>
							<strong>Prioridad por defecto:</strong> Oferta (9) → Últimas Unidades (8) → Preventa (7) → Importación (6) → Express/Bestseller (5) → Novedad (4) → Envío Gratis (3).<br>
							<strong>Exclusiones automáticas:</strong> Preventa oculta Últimas Unidades · Importación oculta Express y Últimas Unidades · Bestseller oculta Novedad.</p>
						</td>
					</tr>

					<tr><td colspan="2"><h3 style="margin:24px 0 0">🚚 Estimador consolidado en carrito</h3></td></tr>
					<tr><th>Habilitar</th>
						<td>
							<input type="hidden" name="le_cart_estimator_enable" value="0">
							<input type="checkbox" name="le_cart_estimator_enable" value="1" <?php checked( get_option( 'le_cart_estimator_enable', '1' ), '1' ); ?>>
							<p class="description">Muestra "Tu pedido completo llega entre X y Y" en el carrito y checkout, tomando el ítem más lento.</p>
						</td>
					</tr>

					<tr><td colspan="2"><h3 style="margin:24px 0 0">📧 Notificación "avísame cuando llegue"</h3></td></tr>
					<tr><th>Habilitar</th>
						<td>
							<input type="hidden" name="le_notify_enable" value="0">
							<input type="checkbox" name="le_notify_enable" value="1" <?php checked( get_option( 'le_notify_enable', '1' ), '1' ); ?>>
							<p class="description">Muestra un formulario de email en productos backorder/imported. Envía correo automático cuando el stock se restaura.</p>
						</td>
					</tr>

					<tr><td colspan="2"><h3 style="margin:24px 0 0">🛒 Badge de venta reciente</h3></td></tr>
					<tr><th>Habilitar</th>
						<td>
							<input type="hidden" name="le_recent_sale_enable" value="0">
							<input type="checkbox" name="le_recent_sale_enable" value="1" <?php checked( get_option( 'le_recent_sale_enable', '0' ), '1' ); ?>>
							<p class="description">"Vendido hace X horas" basado en órdenes reales (no fake scarcity).</p>
						</td>
					</tr>
					<tr><th>Ventana máxima (horas)</th>
						<td>
							<input type="number" min="1" max="720" name="le_recent_sale_max_hours" value="<?php echo esc_attr( get_option( 'le_recent_sale_max_hours', '72' ) ); ?>">
							<p class="description">Solo mostrar si la última venta fue hace menos de X horas.</p>
						</td>
					</tr>

					<tr><td colspan="2"><h3 style="margin:24px 0 0">📚 ISBN Lookup</h3></td></tr>
					<tr><td colspan="2"><p>Siempre activo. Ve a editar cualquier producto y verás el campo <strong>ISBN</strong> en la pestaña "General" con el botón <em>Rellenar desde ISBN</em>. Consulta Open Library y Google Books como fallback.</p></td></tr>
				</table>

				<!-- Presets -->
				<table class="form-table le-tab-content <?php echo 'tab_presets' === $active_tab ? 'active' : ''; ?>" id="tab_presets">
					<tr><td colspan="2"><p><em>Aplica estilos visuales coherentes a todos los badges con un clic.</em></p></td></tr>
					<tr><th>Paleta de colores</th>
						<td>
							<select name="le_preset_palette" id="le_preset_palette">
								<?php
								$current_palette = get_option( 'le_preset_palette', 'default' );
								foreach ( \LE\Presets::PALETTES as $key => $p ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $current_palette, $key, false ),
										esc_html( $p['label'] )
									);
								}
								?>
							</select>
							<button type="button" class="button" id="le-apply-palette">Aplicar paleta a todos los badges</button>
							<p class="description">Sobrescribe los colores de cada badge con los de la paleta seleccionada. Puedes ajustar luego cada uno individualmente.</p>
							<div id="le-palette-preview" style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap"></div>
						</td>
					</tr>
					<tr><th>Estilo de badge</th>
						<td>
							<select name="le_preset_badge_style">
								<?php
								$current_style = get_option( 'le_preset_badge_style', 'rounded' );
								foreach ( \LE\Presets::BADGE_STYLES as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $current_style, $key, false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">Forma y efecto visual de los badges. <strong>Glass</strong> y <strong>Neon</strong> se ven mejor sobre imágenes oscuras; <strong>Ghost</strong> queda elegante sobre fondos claros.</p>
						</td>
					</tr>
					<tr><th>Tipografía global</th>
						<td>
							<select name="le_preset_font">
								<?php
								$current_font = get_option( 'le_preset_font', '' );
								foreach ( \LE\Presets::FONTS as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $current_font, $key, false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">Fuente para badges. Inter / Manrope / JetBrains Mono se cargan desde Google Fonts.</p>
						</td>
					</tr>
				</table>
				<script>
				(function(){
					var palettes = <?php echo wp_json_encode( \LE\Presets::PALETTES ); ?>;
					var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
					var nonce = '<?php echo esc_js( wp_create_nonce( 'le_apply_palette' ) ); ?>';

					function renderPreview(key) {
						var p = palettes[key]; if (!p) return;
						var box = document.getElementById('le-palette-preview');
						if (!box) return;
						box.innerHTML = '';
						Object.entries(p.colors).forEach(function(entry){
							var sw = document.createElement('div');
							sw.title = entry[0];
							sw.style.cssText = 'width:32px;height:32px;border-radius:6px;border:1px solid #ccc;background:'+entry[1];
							box.appendChild(sw);
						});
					}

					// === Vista previa en vivo de cada badge ===
					function leHexToRgba(hex, alphaPct) {
						hex = (hex || '#000000').replace('#', '');
						if (hex.length === 3) hex = hex.split('').map(function(c){ return c+c; }).join('');
						var r = parseInt(hex.substr(0,2), 16) || 0;
						var g = parseInt(hex.substr(2,2), 16) || 0;
						var b = parseInt(hex.substr(4,2), 16) || 0;
						var a = (parseInt(alphaPct, 10) || 0) / 100;
						return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + a + ')';
					}
					function leEscapeHtml(s) {
						return String(s == null ? '' : s)
							.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
							.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
					}
					function leRenderPreview(section) {
						var id = section.getAttribute('data-badge-id');
						var canvas = section.querySelector('.le-preview-canvas');
						if (!id || !canvas) return;
						function get(suffix, fallback) {
							var el = section.querySelector('[name="le_badge_' + id + '_' + suffix + '"]');
							return el ? el.value : (fallback || '');
						}
						var enableEl = section.querySelector('input[type="checkbox"][name="le_badge_' + id + '_enable"]');
						if (enableEl && !enableEl.checked) {
							canvas.innerHTML = '';
							return;
						}
						var text       = get('text', 'Badge');
						var emoji      = get('emojis', '');
						var anim       = get('animation', 'rebote');
						var color      = get('color', '#000000');
						var textColor  = get('text_color', color);
						var alpha      = get('alpha', '15');
						var emojiPos   = get('emoji_pos', 'right');
						var bg = leHexToRgba(color, alpha);

						// Granular overrides
						var gRadius   = get('border_radius', '');
						var gBw       = get('border_width', '');
						var gPadding  = get('padding', '');
						var gFsize    = get('font_size', '');
						var gFweight  = get('font_weight', '');
						var gBgType   = get('bg_type', '');
						var gGcolor   = get('gradient_color', '#ffffff');
						var gGalpha   = get('gradient_color_alpha', '100');
						var gGangle   = parseInt(get('gradient_angle', '135'), 10) || 135;
						var gShadow   = get('shadow', '');
						var gUpperEl  = section.querySelector('input[type="checkbox"][name="le_badge_' + id + '_uppercase"]');
						var gUpper    = gUpperEl ? gUpperEl.checked : false;

						var parts = [
							'--le-color:' + color,
							'--le-bg:' + bg,
							'--le-text-color:' + textColor
						];
						if (gBgType === 'gradient') {
							var gRgba = leHexToRgba(gGcolor, gGalpha);
							parts.push('background:linear-gradient(' + gGangle + 'deg, ' + color + ', ' + gRgba + ') !important');
						} else if (gBgType === 'transparent') {
							parts.push('background:transparent !important');
						} else {
							parts.push('background-color:' + bg);
						}
						parts.push('border-color:' + color);
						parts.push('color:' + textColor);
						if (gRadius !== '')  parts.push('border-radius:' + gRadius + 'px !important');
						if (gBw !== '')      parts.push('border-width:' + gBw + 'px !important;border-style:solid');
						if (gPadding !== '') parts.push('padding:' + gPadding + 'px !important');
						if (gFsize !== '')   parts.push('font-size:' + gFsize + 'px !important');
						if (gFweight !== '') parts.push('font-weight:' + gFweight + ' !important');
						if (gUpper)          parts.push('text-transform:uppercase !important');
						if (gShadow === 'soft')        parts.push('box-shadow:0 2px 6px rgba(0,0,0,0.15) !important');
						else if (gShadow === 'medium') parts.push('box-shadow:0 4px 12px rgba(0,0,0,0.20) !important');
						else if (gShadow === 'hard')   parts.push('box-shadow:0 6px 16px rgba(0,0,0,0.30) !important');
						else if (gShadow === 'glow')   parts.push('box-shadow:0 0 12px ' + color + ' !important');
						else if (gShadow === 'none')   parts.push('box-shadow:none !important');
						var style = parts.join(';') + ';';
						var emojiSpan = '<span class="le-badge__emojis">' + leEscapeHtml(emoji) + '</span>';
						var textSpan  = '<span class="le-badge__text">' + leEscapeHtml(text) + '</span>';
						var content   = (emojiPos === 'left') ? (emojiSpan + ' ' + textSpan) : (textSpan + ' ' + emojiSpan);
						canvas.innerHTML = '<div class="le-badge le-badge--' + leEscapeHtml(id) + ' le-anim-' + leEscapeHtml(anim) +
							'" style="' + style + '">' + content + '</div>';
					}
					['input', 'change'].forEach(function(evt){
						document.addEventListener(evt, function(e){
							var section = e.target.closest && e.target.closest('.le-badge-section[data-badge-id]');
							if (section) leRenderPreview(section);
						});
					});
					document.addEventListener('toggle', function(e){
						if (e.target && e.target.matches && e.target.matches('.le-badge-section[data-badge-id]')) {
							leRenderPreview(e.target);
						}
					}, true);

					// === Live preview del Preset (estilo + fuente) sin guardar ===
					function leBuildPresetCss(style, font) {
						var css = '';
						if (font) css += ':root { --le-font: "' + font + '", system-ui, sans-serif; }';
						css += '.le-badge { font-family: var(--le-font, inherit); ';
						switch (style) {
							case 'pill':           css += 'border-radius:999px;padding:4px 14px;'; break;
							case 'square':         css += 'border-radius:0;padding:4px 10px;'; break;
							case 'ghost':          css += 'background:transparent !important;border-width:1.5px;border-style:solid;border-color:var(--le-color,currentColor);color:var(--le-text-color,var(--le-color,currentColor)) !important;padding:3px 9px;border-radius:6px;'; break;
							case 'glass':          css += 'backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);background:rgba(255,255,255,0.55) !important;border:1px solid rgba(255,255,255,0.4);color:var(--le-text-color,var(--le-color,#111)) !important;border-radius:10px;padding:4px 12px;'; break;
							case 'gradient':       css += 'background:linear-gradient(135deg,var(--le-color,#000) 0%,color-mix(in srgb,var(--le-color,#000) 30%,transparent) 100%) !important;color:var(--le-text-color,#fff) !important;border:none;border-radius:8px;padding:5px 14px;box-shadow:0 2px 6px rgba(0,0,0,0.15);'; break;
							case 'gradient_soft':  css += 'background:linear-gradient(135deg,var(--le-color,#000) 0%,color-mix(in srgb,var(--le-color,#000) 35%,white) 100%) !important;color:var(--le-text-color,#fff) !important;border:none;border-radius:10px;padding:5px 14px;'; break;
							case 'gradient_vivid': css += 'background:linear-gradient(120deg,var(--le-color,#000) 0%,color-mix(in srgb,var(--le-color,#000) 50%,magenta) 100%) !important;color:var(--le-text-color,#fff) !important;border:none;border-radius:8px;padding:5px 14px;text-shadow:0 1px 2px rgba(0,0,0,0.2);'; break;
							case 'gradient_shine': css += 'background:linear-gradient(135deg,var(--le-color,#000) 0%,color-mix(in srgb,var(--le-color,#000) 40%,white) 50%,var(--le-color,#000) 100%) !important;background-size:200% 100%;color:var(--le-text-color,#fff) !important;border:none;border-radius:10px;padding:5px 14px;animation:le-shine 3s ease-in-out infinite;'; break;
							case 'button_3d':      css += 'background:var(--le-color,#000) !important;color:var(--le-text-color,#fff) !important;border:none;border-radius:8px;padding:6px 14px;box-shadow:0 3px 0 color-mix(in srgb,var(--le-color,#000) 60%,black),0 4px 8px rgba(0,0,0,0.15);transform:translateY(-1px);font-weight:600;'; break;
							case 'neon':           css += 'background:rgba(0,0,0,0.85) !important;color:var(--le-text-color,var(--le-color,#fff)) !important;border:1.5px solid var(--le-color,#fff);border-radius:6px;padding:3px 10px;box-shadow:0 0 8px var(--le-color,#fff),inset 0 0 4px var(--le-color,#fff);text-shadow:0 0 6px var(--le-text-color,var(--le-color,#fff));'; break;
							default:               css += 'border-radius:6px;padding:4px 10px;';
						}
						css += '}';
						if (style === 'gradient_shine') {
							css += '@keyframes le-shine { 0%,100%{background-position:0% 50%;} 50%{background-position:100% 50%;} }';
						}
						return css;
					}
					function leUpdatePresetStyle() {
						var styleEl = document.getElementById('le-presets-inline');
						if (!styleEl) {
							styleEl = document.createElement('style');
							styleEl.id = 'le-presets-inline';
							document.head.appendChild(styleEl);
						}
						var styleSel = document.querySelector('[name="le_preset_badge_style"]');
						var fontSel  = document.querySelector('[name="le_preset_font"]');
						styleEl.textContent = leBuildPresetCss(
							styleSel ? styleSel.value : 'rounded',
							fontSel  ? fontSel.value  : ''
						);
					}
					document.addEventListener('change', function(e){
						if (!e.target || !e.target.name) return;
						if (e.target.name === 'le_preset_badge_style' || e.target.name === 'le_preset_font') {
							leUpdatePresetStyle();
						}
					});

					// Status indicators de cada badge en el acordeón
					function refreshBadgeStatus() {
						document.querySelectorAll('.le-badge-status').forEach(function(span){
							var name = span.getAttribute('data-option');
							if (!name) return;
							var cb = document.querySelector('input[type="checkbox"][name="' + name + '"]');
							var isOn = cb ? cb.checked : false;
							span.textContent = isOn ? '✓ activo' : '○ inactivo';
							span.classList.toggle('is-on', isOn);
							span.classList.toggle('is-off', !isOn);
						});
					}
					document.addEventListener('change', function(e){
						if (e.target && e.target.matches('input[type="checkbox"][name^="le_badge_"][name$="_enable"]')) {
							refreshBadgeStatus();
						}
					});

					// === Botón "Replicar estilo a otros badges" ===
					var BADGE_LABELS = {
						express:'⚡🚀 Express', import:'✈️🌍 Importación', sale:'🔥 Oferta',
						lowstock:'⏳ Últimas Unidades', preorder:'🗓️ Preventa', new:'✨ Novedad',
						bestseller:'🏆 Bestseller', freeshipping:'🆓 Envío Gratis'
					};
					// Sufijos que se replican (estilo visual). Excluimos contenido específico (text, emojis), enable, lógica de cada badge.
					var REPLICATE_SUFFIXES = [
						'animation', 'color', 'text_color', 'alpha', 'emoji_pos', 'position',
						'border_radius', 'border_width', 'padding', 'font_size', 'font_weight',
						'bg_type', 'gradient_color', 'gradient_color_alpha', 'gradient_angle',
						'shadow', 'uppercase'
					];
					function leInjectReplicateButtons() {
						return; // Estilo unificado: ya no hay estilo que replicar.
						document.querySelectorAll('.le-badge-section[data-badge-id]').forEach(function(section){
							if (section.querySelector('.le-replicate-wrap')) return;
							var card = section.querySelector('.le-preview-card');
							if (!card) return;
							var sourceId = section.getAttribute('data-badge-id');
							var wrap = document.createElement('div');
							wrap.className = 'le-replicate-wrap';
							wrap.innerHTML = '<button type="button" class="le-replicate-btn">📋 Replicar estilo a otros badges…</button>';
							card.parentNode.insertBefore(wrap, card.nextSibling);

							wrap.querySelector('.le-replicate-btn').addEventListener('click', function(e){
								e.preventDefault();
								// Cerrar popovers existentes
								document.querySelectorAll('.le-replicate-popover').forEach(function(p){ p.remove(); });
								var pop = document.createElement('div');
								pop.className = 'le-replicate-popover';
								var checks = '';
								Object.keys(BADGE_LABELS).forEach(function(id){
									if (id === sourceId) return;
									checks += '<label><input type="checkbox" value="'+id+'" checked> '+BADGE_LABELS[id]+'</label>';
								});
								pop.innerHTML = '<div style="font-weight:600;font-size:12px;margin-bottom:6px;color:#334155">Copiar estilo de <em>'+BADGE_LABELS[sourceId]+'</em> a:</div>'+
									'<div style="max-height:240px;overflow:auto">'+checks+'</div>'+
									'<div class="le-replicate-actions"><button type="button" class="le-replicate-cancel">Cancelar</button><button type="button" class="le-replicate-apply">Aplicar</button></div>';
								wrap.appendChild(pop);

								pop.querySelector('.le-replicate-cancel').addEventListener('click', function(){ pop.remove(); });
								pop.querySelector('.le-replicate-apply').addEventListener('click', function(){
									var targets = Array.from(pop.querySelectorAll('input[type="checkbox"]:checked')).map(function(cb){ return cb.value; });
									if (!targets.length) { pop.remove(); return; }
									var copied = 0;
									REPLICATE_SUFFIXES.forEach(function(suffix){
										var src = document.querySelector('[name="le_badge_'+sourceId+'_'+suffix+'"]');
										if (!src) return;
										var val;
										if (src.type === 'checkbox') val = src.checked;
										else val = src.value;
										targets.forEach(function(tid){
											var dst = document.querySelector('[name="le_badge_'+tid+'_'+suffix+'"]');
											if (!dst) return;
											if (dst.type === 'checkbox') {
												dst.checked = !!val;
											} else {
												dst.value = val;
												// Actualizar el <output> hermano si existe (sliders)
												if (dst.type === 'range' && dst.nextElementSibling && dst.nextElementSibling.tagName === 'OUTPUT') {
													dst.nextElementSibling.value = (val === '' ? '—' : val + (suffix.indexOf('angle')>=0?'°':(suffix.indexOf('alpha')>=0||suffix==='alpha'?'%':'px')));
												}
											}
											dst.dispatchEvent(new Event('input', { bubbles: true }));
											dst.dispatchEvent(new Event('change', { bubbles: true }));
											copied++;
										});
									});
									pop.remove();
									alert('✅ Estilo replicado a '+targets.length+' badge(s). Recuerda guardar para persistir.');
								});
							});
						});

						// Click fuera cierra popover
						document.addEventListener('click', function(e){
							if (e.target.closest('.le-replicate-wrap')) return;
							document.querySelectorAll('.le-replicate-popover').forEach(function(p){ p.remove(); });
						});
					}

					document.addEventListener('DOMContentLoaded', function(){
						refreshBadgeStatus();
						// Render inicial de todas las vistas previas de badges
						document.querySelectorAll('.le-badge-section[data-badge-id]').forEach(leRenderPreview);
						leInjectReplicateButtons();

						var sel = document.getElementById('le_preset_palette');
						if (sel) { renderPreview(sel.value); sel.addEventListener('change', function(){ renderPreview(sel.value); }); }

						var btn = document.getElementById('le-apply-palette');
						if (btn) {
							btn.addEventListener('click', function(){
								if (!confirm('Esto sobrescribirá los colores de TODOS los badges. ¿Continuar?')) return;
								btn.disabled = true; btn.textContent = 'Aplicando...';
								var fd = new FormData();
								fd.append('action', 'le_apply_palette');
								fd.append('palette', sel.value);
								fd.append('nonce', nonce);
								fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
									.then(function(r){ return r.json(); })
									.then(function(d){
										btn.disabled = false; btn.textContent = 'Aplicar paleta a todos los badges';
										if (d && d.success) {
											alert('✅ Paleta aplicada. Recarga para ver los cambios en cada pestaña.');
										} else {
											alert('Error aplicando paleta');
										}
									});
							});
						}
					});
				})();
				</script>

				<input type="hidden" name="le_active_tab" id="le_active_tab" value="<?php echo esc_attr($active_tab); ?>">
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( add_query_arg( 'tab', $active_tab, esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) ); ?>">
				
				<?php submit_button(); ?>
			</form>

			<div class="le-author-box" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 8px; max-width: 600px; border-left: 4px solid #2271b1;">
				<h3 style="margin-top: 0; color: #1d2327; font-size: 16px;">Acerca del Autor</h3>
				<p style="margin: 5px 0; font-size: 14px;">Desarrollado con ❤️ por <strong>Andrés Valencia Tobón</strong>.</p>
				<p style="margin: 15px 0 0 0;">
					<a href="https://github.com/quijotevitruvio" target="_blank" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; color: #2271b1; font-weight: 500;">
						<span class="dashicons dashicons-admin-links"></span> Visitar GitHub
					</a>
					<span style="margin: 0 12px; color: #ccd0d4;">|</span>
					<a href="https://www.paypal.com/ncp/payment/VNLNFAZ5TTZJQ" target="_blank" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; color: #1c9d46; font-weight: bold;">
						<span class="dashicons dashicons-heart"></span> Donar en PayPal
					</a>
				</p>
			</div>
		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const tabs = document.querySelectorAll('#le-tab-nav .nav-tab');
				const contents = document.querySelectorAll('.le-tab-content');
				const activeTabInput = document.getElementById('le_active_tab');

				tabs.forEach(tab => {
					tab.addEventListener('click', function(e) {
						e.preventDefault();
						
						// Remove active class from all
						tabs.forEach(t => t.classList.remove('nav-tab-active'));
						contents.forEach(c => c.classList.remove('active'));
						
						// Add active class to clicked tab and corresponding content
						this.classList.add('nav-tab-active');
						const targetId = this.getAttribute('data-tab');
						if (document.getElementById(targetId)) {
							document.getElementById(targetId).classList.add('active');
						}
						activeTabInput.value = targetId;

						// Update URL without reloading
						const newUrl = new URL(window.location.href);
						newUrl.searchParams.set('tab', targetId);
						window.history.replaceState({}, '', newUrl);

						// Update hidden referer inputs
						document.querySelectorAll('input[name="_wp_http_referer"]').forEach(i => {
							i.value = newUrl.pathname + newUrl.search;
						});
					});
				});

				const form = document.querySelector('form[action="options.php"]');
				if (form) {
					form.addEventListener('submit', function(e) {
						const url = new URL(window.location.href);
						document.querySelectorAll('input[name="_wp_http_referer"]').forEach(i => {
							i.value = url.pathname + url.search;
						});

						// Validar min ≤ max en estimación de fechas
						const pairs = [
							['le_est_local_min', 'le_est_local_max', 'Local'],
							['le_est_backorder_min', 'le_est_backorder_max', 'Encargo'],
							['le_est_import_min', 'le_est_import_max', 'Importado'],
						];
						for (const [minName, maxName, label] of pairs) {
							const minEl = form.querySelector(`[name="${minName}"]`);
							const maxEl = form.querySelector(`[name="${maxName}"]`);
							if (minEl && maxEl) {
								const min = parseInt(minEl.value, 10);
								const max = parseInt(maxEl.value, 10);
								if (!isNaN(min) && !isNaN(max) && min > max) {
									e.preventDefault();
									alert(`En "${label}", el mínimo (${min}) no puede ser mayor que el máximo (${max}).`);
									(form.querySelector('[data-tab="tab_estimacion"]') || {}).click?.();
									minEl.focus();
									return false;
								}
							}
						}

						// Validar formato de festivos YYYY-MM-DD
						const holidaysEl = form.querySelector('[name="le_est_holidays"]');
						if (holidaysEl && holidaysEl.value.trim()) {
							const days = holidaysEl.value.split(',').map(s => s.trim()).filter(Boolean);
							const bad = days.filter(d => !/^\d{4}-\d{2}-\d{2}$/.test(d));
							if (bad.length) {
								e.preventDefault();
								alert(`Festivos con formato inválido: ${bad.join(', ')}\nUsa YYYY-MM-DD.`);
								holidaysEl.focus();
								return false;
							}
						}
					});
				}
			});
		</script>
		<?php
	}
}
