<?php
/**
 * Plugin Name:       Giggle WP
 * Plugin URI:        https://giggle.tips
 * Description:       Display Giggle.tips experiences and events on your WordPress site using a Gutenberg block. Supports schema.org JSON-LD semantic annotations.
 * Version:           26.4.29.3
 * Requires at least: 6.9.4
 * Requires PHP:      8.3
 * Author:            Giggle.tips
 * Author URI:        https://giggle.tips
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       giggle-wp
 * Domain Path:       /languages
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GIGGLE_WP_VERSION', '26.4.29.3' );
define( 'GIGGLE_WP_DIR', plugin_dir_path( __FILE__ ) );
define( 'GIGGLE_WP_URL', plugin_dir_url( __FILE__ ) );

require_once GIGGLE_WP_DIR . 'includes/class-giggle-api.php';
require_once GIGGLE_WP_DIR . 'includes/class-giggle-settings.php';
require_once GIGGLE_WP_DIR . 'includes/class-giggle-block.php';

add_action( 'plugins_loaded', static function (): void {
	load_plugin_textdomain( 'giggle-wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Giggle_Settings::init();
	Giggle_Block::init();
} );

add_action( 'giggle_wp_cache_refresh', [ 'Giggle_API', 'handle_cache_refresh' ], 10, 3 );

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
