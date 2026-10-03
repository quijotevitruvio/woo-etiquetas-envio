<?php

declare(strict_types=1);

namespace LE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Presets visuales: paletas, estilos de badge y tipografía.
 * Inyecta CSS variables al frontend basadas en la elección.
 */
final class Presets {

	private static ?Presets $instance = null;

	public const PALETTES = [
		'default' => [
			'label'  => 'Por defecto',
			'colors' => [
				'express'      => '#000000',
				'import'       => '#777777',
				'sale'         => '#e53e3e',
				'lowstock'     => '#d69e2e',
				'preorder'     => '#805ad5',
				'new'          => '#38b2ac',
				'bestseller'   => '#d69e2e',
				'freeshipping' => '#38a169',
			],
		],
		'midnight' => [
			'label'  => 'Midnight (Vercel-like)',
			'colors' => [
				'express'      => '#0070f3',
				'import'       => '#7928ca',
				'sale'         => '#ff0080',
				'lowstock'     => '#f5a623',
				'preorder'     => '#50e3c2',
				'new'          => '#7928ca',
				'bestseller'   => '#ffd700',
				'freeshipping' => '#0070f3',
			],
		],
		'sunset' => [
			'label'  => 'Sunset',
			'colors' => [
				'express'      => '#ff6b35',
				'import'       => '#c1666b',
				'sale'         => '#e63946',
				'lowstock'     => '#f4a261',
				'preorder'     => '#9b5de5',
				'new'          => '#fb8500',
				'bestseller'   => '#ffb703',
				'freeshipping' => '#06a77d',
			],
		],
		'mint' => [
			'label'  => 'Mint',
			'colors' => [
				'express'      => '#06d6a0',
				'import'       => '#118ab2',
				'sale'         => '#ef476f',
				'lowstock'     => '#ffd166',
				'preorder'     => '#073b4c',
				'new'          => '#06d6a0',
				'bestseller'   => '#f4a261',
				'freeshipping' => '#06d6a0',
			],
		],
		'mono' => [
			'label'  => 'Mono (Linear-like)',
			'colors' => [
				'express'      => '#1a1a1a',
				'import'       => '#404040',
				'sale'         => '#1a1a1a',
				'lowstock'     => '#1a1a1a',
				'preorder'     => '#1a1a1a',
				'new'          => '#1a1a1a',
				'bestseller'   => '#1a1a1a',
				'freeshipping' => '#1a1a1a',
			],
		],
		'pastel' => [
			'label'  => 'Pastel',
			'colors' => [
				'express'      => '#a8dadc',
				'import'       => '#cdb4db',
				'sale'         => '#ffafcc',
				'lowstock'     => '#ffd6a5',
				'preorder'     => '#bdb2ff',
				'new'          => '#caffbf',
				'bestseller'   => '#fdffb6',
				'freeshipping' => '#9bf6ff',
			],
		],
		'fluo' => [
			'label'  => '☢️ Fluorescente / Neón radioactivo',
			'colors' => [
				'express'      => '#00F0FF', // cian eléctrico — relámpago ⚡
				'import'       => '#FF00C8', // magenta hot — vuelo ✈️
				'sale'         => '#FF003C', // rojo neón — fuego 🔥
				'lowstock'     => '#FF8C00', // naranja eléctrico — urgencia ⏳
				'preorder'     => '#B026FF', // violeta neón — futuro 🗓️
				'new'          => '#39FF14', // verde neón clásico — fresco ✨
				'bestseller'   => '#FFFF00', // amarillo eléctrico — trofeo 🏆
				'freeshipping' => '#00FF7F', // lima radioactivo — gratis 🆓
			],
		],
	];

	public const BADGE_STYLES = [
		'rounded'         => 'Redondeado (default)',
		'pill'            => 'Pill (muy redondeado)',
		'square'          => 'Cuadrado',
		'ghost'           => 'Ghost (solo borde)',
		'glass'           => 'Glassmorphism (blur)',
		'gradient'        => 'Gradiente diagonal',
		'gradient_soft'   => 'Gradiente suave (color → claro)',
		'gradient_vivid'  => 'Gradiente vívido (color → análogo)',
		'gradient_shine'  => 'Gradiente con shine ✨',
		'button_3d'       => 'Botón 3D',
		'neon'            => 'Neon (glow)',
	];

	public const FONTS = [
		''                 => 'Heredada del tema',
		'Inter'            => 'Inter',
		'Geist'            => 'Geist',
		'Manrope'          => 'Manrope',
		'JetBrains Mono'   => 'JetBrains Mono',
		'system-ui'        => 'System UI',
	];

	public static function get_instance(): Presets {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_head', [ $this, 'inject_css' ], 99 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_font' ] );
		add_action( 'wp_ajax_le_apply_palette', [ $this, 'ajax_apply_palette' ] );
	}

	public function get_active_palette(): array {
		$key = (string) Settings::get( 'preset_palette', 'default' );
		return self::PALETTES[ $key ] ?? self::PALETTES['default'];
	}

	public function get_active_style(): string {
		return (string) Settings::get( 'preset_badge_style', 'rounded' );
	}

	public function get_active_font(): string {
		return (string) Settings::get( 'preset_font', '' );
	}

	public function enqueue_font(): void {
		$font = $this->get_active_font();
		$google_fonts = [ 'Inter', 'Manrope', 'JetBrains Mono' ];
		if ( in_array( $font, $google_fonts, true ) ) {
			$family = str_replace( ' ', '+', $font );
			wp_enqueue_style(
				'woo-etiquetas-envio-font',
				"https://fonts.googleapis.com/css2?family={$family}:wght@400;600;700&display=swap",
				[],
				LE_VERSION
			);
		}
	}

	public function inject_css(): void {
		$style = $this->get_active_style();
		$font  = $this->get_active_font();

		$css = '';
		if ( $font ) {
			$css .= ':root { --le-font: "' . esc_attr( $font ) . '", system-ui, sans-serif; }';
		}

		// Reglas base — todas usan var(--le-color) y var(--le-bg) que se setean
		// inline por badge, así cada uno conserva SU color individual.
		$css .= '.le-badge { font-family: var(--le-font, inherit); ';

		switch ( $style ) {
			case 'pill':
				$css .= 'border-radius: 999px; padding: 4px 14px;';
				break;
			case 'square':
				$css .= 'border-radius: 0; padding: 4px 10px;';
				break;
			case 'ghost':
				$css .= 'background: transparent !important; border-width: 1.5px; border-style: solid; border-color: var(--le-color, currentColor); color: var(--le-text-color, var(--le-color, currentColor)) !important; padding: 3px 9px; border-radius: 6px;';
				break;
			case 'glass':
				$css .= 'backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); background: rgba(255,255,255,0.55) !important; border: 1px solid rgba(255,255,255,0.4); color: var(--le-text-color, var(--le-color, #111)) !important; border-radius: 10px; padding: 4px 12px;';
				break;
			case 'gradient':
				$css .= 'background: linear-gradient(135deg, var(--le-color, #000) 0%, color-mix(in srgb, var(--le-color, #000) 30%, transparent) 100%) !important; color: var(--le-text-color, #fff) !important; border: none; border-radius: 8px; padding: 5px 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);';
				break;
			case 'gradient_soft':
				$css .= 'background: linear-gradient(135deg, var(--le-color, #000) 0%, color-mix(in srgb, var(--le-color, #000) 35%, white) 100%) !important; color: var(--le-text-color, #fff) !important; border: none; border-radius: 10px; padding: 5px 14px;';
				break;
			case 'gradient_vivid':
				$css .= 'background: linear-gradient(120deg, var(--le-color, #000) 0%, color-mix(in srgb, var(--le-color, #000) 50%, magenta) 100%) !important; color: var(--le-text-color, #fff) !important; border: none; border-radius: 8px; padding: 5px 14px; text-shadow: 0 1px 2px rgba(0,0,0,0.2);';
				break;
			case 'gradient_shine':
				$css .= 'background: linear-gradient(135deg, var(--le-color, #000) 0%, color-mix(in srgb, var(--le-color, #000) 40%, white) 50%, var(--le-color, #000) 100%) !important; background-size: 200% 100%; color: var(--le-text-color, #fff) !important; border: none; border-radius: 10px; padding: 5px 14px; animation: le-shine 3s ease-in-out infinite;';
				break;
			case 'button_3d':
				$css .= 'background: var(--le-color, #000) !important; color: var(--le-text-color, #fff) !important; border: none; border-radius: 8px; padding: 6px 14px; box-shadow: 0 3px 0 color-mix(in srgb, var(--le-color, #000) 60%, black), 0 4px 8px rgba(0,0,0,0.15); transform: translateY(-1px); font-weight: 600;';
				break;
			case 'neon':
				$css .= 'background: rgba(0,0,0,0.85) !important; color: var(--le-text-color, var(--le-color, #fff)) !important; border: 1.5px solid var(--le-color, #fff); border-radius: 6px; padding: 3px 10px; box-shadow: 0 0 8px var(--le-color, #fff), inset 0 0 4px var(--le-color, #fff); text-shadow: 0 0 6px var(--le-text-color, var(--le-color, #fff));';
				break;
			case 'rounded':
			default:
				$css .= 'border-radius: 6px; padding: 4px 10px;';
				break;
		}
		$css .= '}';

		// Keyframe del shine (solo se inyecta si está activo)
		if ( 'gradient_shine' === $style ) {
			$css .= '@keyframes le-shine { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }';
		}

		printf( "<style id='le-presets-inline'>%s</style>\n", $css );
	}

	/**
	 * AJAX: aplica una paleta a todos los colores de badges en un clic.
	 */
	public function ajax_apply_palette(): void {
		check_ajax_referer( 'le_apply_palette', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'msg' => 'forbidden' ], 403 );
		}
		$key = isset( $_POST['palette'] ) ? sanitize_key( wp_unslash( $_POST['palette'] ) ) : '';
		if ( ! isset( self::PALETTES[ $key ] ) ) {
			wp_send_json_error( [ 'msg' => 'unknown palette' ], 400 );
		}
		$palette = self::PALETTES[ $key ];
		foreach ( $palette['colors'] as $badge => $color ) {
			update_option( "le_badge_{$badge}_color", $color );
		}
		update_option( 'le_preset_palette', $key );
		Settings::flush();
		wp_send_json_success( [ 'colors' => $palette['colors'] ] );
	}
}
