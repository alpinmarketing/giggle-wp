<?php
/**
 * Plugin Name:       Giggle WP
 * Plugin URI:        https://www.alpinmarketing.at/wordpress-agentur-hotel-webdesign-tirol/
 * Description:       Display Giggle.tips experiences and events on your WordPress site using a Gutenberg block. Supports schema.org JSON-LD semantic annotations.
 * Version:           26.9.14.2
 * Author:            ALPINMARKETING®
 * Author URI:        https://www.alpinmarketing.at
 * Text Domain:       giggle-wp
 * Domain Path:       /languages
 * Requires at least: 7.1
 * Requires PHP:      8.4
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

declare( strict_types=1 );

namespace AM\GiggleWp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GIGGLE_WP_VERSION', '26.9.14.2' );
define( 'GIGGLE_WP_DIR', plugin_dir_path( __FILE__ ) );
define( 'GIGGLE_WP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimal PSR-4 autoloader for the AM\GiggleWp\ namespace, mapped to /includes.
 * No Composer vendor/ dependency is shipped since the plugin has zero
 * third-party packages; classes are only ever loaded on first reference,
 * so admin-only classes never load on pure frontend requests.
 */
spl_autoload_register( static function ( string $class ): void {
	$prefix = __NAMESPACE__ . '\\';

	if ( ! str_starts_with( $class, $prefix ) ) {
		return;
	}

	$relative_path = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
	$file          = GIGGLE_WP_DIR . 'includes/' . $relative_path . '.php';

	if ( is_file( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', static function (): void {
	load_plugin_textdomain( 'giggle-wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Settings is an admin-only class; the autoloader only pulls its file in
	// when this guard lets the reference through, so frontend requests never
	// load or instantiate it.
	if ( is_admin() ) {
		Settings::init();
	}

	Block::init();
} );

add_action( 'giggle_wp_cache_refresh', [ Api::class, 'handle_cache_refresh' ], 10, 3 );

add_action( 'init', static function (): void {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}
	foreach ( [
		'cta-label'  => 'Learn more & book',
		'aria-prev'  => 'Previous slide',
		'aria-next'  => 'Next slide',
		'aria-close' => 'Close',
	] as $name => $string ) {
		pll_register_string( $name, $string, 'Giggle WP' );
	}
} );
