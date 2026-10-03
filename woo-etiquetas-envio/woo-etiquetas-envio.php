<?php
/**
 * Plugin Name: Woo Etiquetas de Envío
 * Description: Una sola etiqueta sobre la portada (oferta o existencias), fecha estimada de envío y líneas de servicio para WooCommerce.
 * Version: 1.0.0
 * Author: Andrés Valencia Tobón
 * Author URI: https://github.com/quijotevitruvio
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-etiquetas-envio
 * Requires Plugins: woocommerce
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * WC requires at least: 7.0
 * WC tested up to: 9.4
 *
 * Desarrollado para librosmedellin.com.
 * LinkedIn: https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/
 * Donate link: https://paypal.me/bibliotecologo
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

final class Woo_Etiquetas_Envio
{
	public const VERSION = '1.0.0';

	private static ?Woo_Etiquetas_Envio $instance = null;

	public static function get_instance(): Woo_Etiquetas_Envio
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	private function __clone()
	{
	}

	public function __wakeup()
	{
		throw new \Exception('No serializable');
	}

	private function define_constants(): void
	{
		define('LE_VERSION', self::VERSION);
		define('LE_PLUGIN_FILE', __FILE__);
		define('LE_PLUGIN_DIR', plugin_dir_path(__FILE__));
		define('LE_PLUGIN_URL', plugin_dir_url(__FILE__));
	}

	private function includes(): void
	{
		$base = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

		require_once $base . 'class-le-settings.php';
		require_once $base . 'class-le-shipping.php';
		require_once $base . 'class-le-geo.php';
		require_once $base . 'class-le-visual.php';
		require_once $base . 'class-le-api.php';
		require_once $base . 'class-le-cache.php';
		require_once $base . 'class-le-product-meta.php';
		require_once $base . 'class-le-cart-estimator.php';
		require_once $base . 'class-le-notify.php';
		require_once $base . 'class-le-recent-sale.php';
		require_once $base . 'class-le-isbn.php';
		require_once $base . 'class-le-presets.php';
		require_once $base . 'class-le-admin.php';
	}

	private function init_hooks(): void
	{
		add_action('init', [$this, 'load_textdomain']);
		add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
		add_action('updated_option', [$this, 'on_option_update'], 10, 3);
		add_action('added_option', [$this, 'on_option_update'], 10, 2);

		// HPOS compatibility
		add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

		register_activation_hook(__FILE__, [__CLASS__, 'activate']);
	}

	public function load_textdomain(): void
	{
		load_plugin_textdomain('woo-etiquetas-envio', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	public function declare_hpos_compatibility(): void
	{
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}

	public static function activate(): void
	{
		if (version_compare(PHP_VERSION, '8.1', '<')) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(esc_html__('Etiquetas y envío requiere PHP 8.1 o superior.', 'woo-etiquetas-envio'));
		}

		// One-shot: marcar opciones del plugin como autoload para reducir queries.
		global $wpdb;
		$wpdb->query("UPDATE {$wpdb->options} SET autoload = 'yes' WHERE option_name LIKE 'le\\_%' AND autoload = 'no'");
	}

	public function on_option_update($option, $old_value = null, $value = null): void
	{
		if (strpos((string) $option, 'le_') === 0) {
			wp_cache_delete($option, 'options');
			wp_cache_flush_group('le_state');

			if (function_exists('litespeed_purge_all')) {
				litespeed_purge_all();
			}
			if (function_exists('rocket_clean_domain')) {
				rocket_clean_domain();
			}
			if (class_exists('W3TC\DbCache_Flush')) {
				\W3TC\DbCache_Flush::flush_all();
			}
			if (function_exists('w3tc_pgcache_flush')) {
				w3tc_pgcache_flush();
			}
			if (function_exists('wp_cache_clear_cache')) {
				wp_cache_clear_cache();
			}
		}
	}

	public function on_plugins_loaded(): void
	{
		if (!class_exists('WooCommerce')) {
			add_action('admin_notices', [$this, 'wc_missing_notice']);
			return;
		}

		if (is_admin()) {
			\LE\Admin::get_instance();
			\LE\Product_Meta::get_instance();
		}

		\LE\Shipping::get_instance();
		\LE\Geo::get_instance();
		\LE\Visual::get_instance();
		\LE\Api::get_instance();
		\LE\Cache::get_instance();
		\LE\Cart_Estimator::get_instance();
		\LE\Notify::get_instance();
		\LE\Recent_Sale::get_instance();
		\LE\ISBN::get_instance();
		\LE\Presets::get_instance();
	}

	public function wc_missing_notice(): void
	{
		printf(
			'<div class="error"><p><strong>%s</strong> %s</p></div>',
			esc_html__('Etiquetas y envío', 'woo-etiquetas-envio'),
			esc_html__('requiere que WooCommerce esté instalado y activo.', 'woo-etiquetas-envio')
		);
	}
}

Woo_Etiquetas_Envio::get_instance();

// ── Caché de badges: renovar la "sal" global invalida todo al instante ──────
function le_badges_cache_bump(): void
{
	update_option('le_badges_salt', (string) time() . wp_rand(100, 999), false);
}
add_action('save_post_product', 'le_badges_cache_bump');
add_action('woocommerce_product_set_stock', 'le_badges_cache_bump');
add_action('woocommerce_variation_set_stock', 'le_badges_cache_bump');
// Cualquier ajuste de un badge (texto, emoji, posición, lógica, colores…).
$le_bump_on_option = function (string $name): void {
	if (str_starts_with($name, 'le_badge_') || in_array($name, array('le_a_badge_bg', 'le_a_badge_text', 'le_chip_bg', 'le_chip_text'), true)) {
		le_badges_cache_bump();
	}
};
add_action('updated_option', $le_bump_on_option);
add_action('added_option', $le_bump_on_option);

// ── LiteSpeed: no diferir ni retrasar le-badges.js ───────────────────────────
// Diferido, los badges aparecen tarde y "saltan"; excluido, el anclaje a la
// carátula corre apenas carga el DOM.
foreach (array('litespeed_optm_js_defer_exc', 'litespeed_optimize_js_excludes', 'litespeed_optm_js_delay_inc_exc') as $le_ls_hook) {
	add_filter($le_ls_hook, function ($list) {
		$list   = is_array($list) ? $list : array();
		$list[] = 'le-badges.js';
		return $list;
	});
}
