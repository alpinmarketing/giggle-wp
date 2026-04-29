<?php
/**
 * Admin settings page for Giggle WP.
 *
 * Registers a Settings > Giggle WP page with the WordPress Settings API.
 * Stores: API key, hotel code.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Giggle_Settings {

	private const PAGE_SLUG    = 'giggle-wp';
	private const OPTION_GROUP = 'giggle_wp_options';
	private const SECTION_ID   = 'giggle_wp_main_section';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'add_menu_page' ] );
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'update_option_giggle_wp_api_key',    [ self::class, 'flush_caches' ] );
		add_action( 'update_option_giggle_wp_hotel_code', [ self::class, 'flush_caches' ] );
	}

	public static function add_menu_page(): void {
		add_options_page(
			__( 'Giggle WP Settings', 'giggle-wp' ),
			__( 'Giggle WP', 'giggle-wp' ),
			'manage_options',
			self::PAGE_SLUG,
			[ self::class, 'render_page' ]
		);
	}

	public static function register_settings(): void {
		register_setting( self::OPTION_GROUP, 'giggle_wp_api_key', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		register_setting( self::OPTION_GROUP, 'giggle_wp_hotel_code', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		add_settings_section(
			self::SECTION_ID,
			__( 'API Credentials', 'giggle-wp' ),
			[ self::class, 'render_section_description' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'giggle_wp_api_key',
			__( 'API Key', 'giggle-wp' ),
			[ self::class, 'render_api_key_field' ],
			self::PAGE_SLUG,
			self::SECTION_ID
		);

		add_settings_field(
			'giggle_wp_hotel_code',
			__( 'Hotel Code', 'giggle-wp' ),
			[ self::class, 'render_hotel_code_field' ],
			self::PAGE_SLUG,
			self::SECTION_ID
		);
	}

	public static function render_section_description(): void {
		echo '<p>' . wp_kses_post(
			sprintf(
				/* translators: link to Giggle API docs */
				__( 'Enter your Giggle credentials. You can find your API key and hotel code in the <a href="%s" target="_blank" rel="noopener noreferrer">Giggle CMS</a>.', 'giggle-wp' ),
				'https://cms.giggle.tips/share-experiences/experience-api'
			)
		) . '</p>';
	}

	public static function render_api_key_field(): void {
		$value = get_option( 'giggle_wp_api_key', '' );
		?>
		<input
			type="password"
			id="giggle_wp_api_key"
			name="giggle_wp_api_key"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off"
			spellcheck="false"
		/>
		<p class="description">
			<?php esc_html_e( 'Your Giggle API key. This key is sent as the api-key request header and is never exposed in the frontend HTML.', 'giggle-wp' ); ?>
		</p>
		<?php
	}

	public static function render_hotel_code_field(): void {
		$value = get_option( 'giggle_wp_hotel_code', '' );
		?>
		<input
			type="text"
			id="giggle_wp_hotel_code"
			name="giggle_wp_hotel_code"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="e.g. giggletips"
			spellcheck="false"
		/>
		<p class="description">
			<?php esc_html_e( 'The unique code from your Giggle WebApp URL — for example, "giggletips" from giggle.tips/giggletips.', 'giggle-wp' ); ?>
		</p>
		<?php
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			// Show connection status if credentials are set.
			$api_key    = get_option( 'giggle_wp_api_key', '' );
			$hotel_code = get_option( 'giggle_wp_hotel_code', '' );

			if ( $api_key && $hotel_code ) {
				self::render_connection_status();
			}
			?>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'giggle-wp' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Verify credentials and display a status notice (result cached 5 min).
	 */
	private static function render_connection_status(): void {
		$cached = get_transient( 'giggle_conn' );

		if ( false === $cached ) {
			$api    = Giggle_API::from_options();
			$result = $api->fetch_streams();

			$cached = is_wp_error( $result )
				? [ 'error' => $result->get_error_message() ]
				: [ 'count' => is_array( $result['streams'] ?? null ) ? count( $result['streams'] ) : 0 ];

			set_transient( 'giggle_conn', $cached, 5 * MINUTE_IN_SECONDS );
		}

		if ( isset( $cached['error'] ) ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'Giggle API connection failed:', 'giggle-wp' ),
				esc_html( $cached['error'] )
			);
		} else {
			$count = (int) ( $cached['count'] ?? 0 );
			printf(
				'<div class="notice notice-success"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'Connected to Giggle API.', 'giggle-wp' ),
				sprintf(
					/* translators: %d number of streams found */
					esc_html( _n( '%d stream found.', '%d streams found.', $count, 'giggle-wp' ) ),
					$count
				)
			);
		}
	}

	/**
	 * Delete all giggle_* transients when credentials change.
	 */
	public static function flush_caches(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				    OR option_name LIKE %s",
				'_transient_giggle_%',
				'_transient_timeout_giggle_%'
			)
		);
	}
}
