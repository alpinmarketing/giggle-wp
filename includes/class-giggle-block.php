<?php
/**
 * Giggle Events block — registration and server-side render.
 *
 * Block name: giggle-wp/events
 * Render:     Server-side PHP via render_callback (save() returns null).
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Giggle_Block {

	private static bool $modal_rendered = false;

	public static function init(): void {
		add_action( 'init', [ self::class, 'register_block' ] );
	}

	public static function register_block(): void {
		// Editor script (vanilla JS, no build step required).
		wp_register_script(
			'giggle-wp-block-editor',
			GIGGLE_WP_URL . 'assets/js/giggle-block-editor.js',
			[
				'wp-blocks',
				'wp-block-editor',
				'wp-components',
				'wp-element',
				'wp-i18n',
			],
			GIGGLE_WP_VERSION,
			true
		);

		// Frontend styles.
		wp_register_style(
			'giggle-wp-block',
			GIGGLE_WP_URL . 'assets/css/giggle-block.css',
			[],
			GIGGLE_WP_VERSION
		);

		// Frontend modal script.
		wp_register_script(
			'giggle-wp-modal',
			GIGGLE_WP_URL . 'assets/js/giggle-modal.js',
			[],
			GIGGLE_WP_VERSION,
			true
		);
		wp_localize_script( 'giggle-wp-modal', 'GiggleI18n', [
			'location'             => __( 'Location', 'giggle-wp' ),
			'meetingPoint'         => __( 'Meeting point', 'giggle-wp' ),
			'registrationDeadline' => __( 'Registration deadline', 'giggle-wp' ),
			'groupSize'            => __( 'Group size', 'giggle-wp' ),
			'duration'             => __( 'Duration', 'giggle-wp' ),
			'persons'              => __( 'persons', 'giggle-wp' ),
			'upTo'                 => __( 'up to', 'giggle-wp' ),
			'from'                 => __( 'from', 'giggle-wp' ),
			'hours'                => __( 'h', 'giggle-wp' ),
			'minutes'              => __( 'min', 'giggle-wp' ),
		] );

		register_block_type( 'giggle-wp/events', [
			'api_version'     => 2,
			'title'           => __( 'Giggle Events', 'giggle-wp' ),
			'category'        => 'embed',
			'icon'            => 'calendar-alt',
			'description'     => __( 'Display Giggle.tips experiences and events on your page.', 'giggle-wp' ),
			'editor_script'   => 'giggle-wp-block-editor',
			'style'           => 'giggle-wp-block',
			'script'          => 'giggle-wp-modal',
			'render_callback' => [ self::class, 'render' ],
			'attributes'      => [
				'streamIds'   => [
					'type'    => 'string',
					'default' => '',
				],
				'language'    => [
					'type'    => 'string',
					'default' => '',
				],
				'onlyBookable' => [
					'type'    => 'boolean',
					'default' => true,
				],
				'dateRange'   => [
					'type'    => 'string',
					'default' => '',
				],
				'pageSize'    => [
					'type'    => 'integer',
					'default' => 10,
				],
				'title'       => [
					'type'    => 'string',
					'default' => '',
				],
				'layout'      => [
					'type'    => 'string',
					'default' => 'carousel',
				],
			],
		] );
	}

	/**
	 * Server-side render callback.
	 *
	 * @param array $attrs Block attributes.
	 * @return string HTML output.
	 */
	public static function render( array $attrs ): string {
		$stream_ids  = sanitize_text_field( $attrs['streamIds'] ?? '' );
		$allowed_languages = [ '', 'de', 'en', 'es', 'fr', 'it', 'nl', 'nb', 'sk', 'sl', 'sv', 'el', 'ja', 'zh' ];
		$language          = in_array( $attrs['language'] ?? '', $allowed_languages, true )
			? (string) ( $attrs['language'] ?? '' )
			: '';
		$only_bkble  = (bool) ( $attrs['onlyBookable'] ?? true );
		$date_range  = sanitize_text_field( $attrs['dateRange'] ?? '' );
		$page_size   = max( 1, min( 50, (int) ( $attrs['pageSize'] ?? 10 ) ) );
		$block_title = sanitize_text_field( $attrs['title'] ?? '' );
		$layout      = in_array( $attrs['layout'] ?? '', [ 'carousel', 'grid' ], true )
			? $attrs['layout']
			: 'carousel';

		if ( '' === $stream_ids ) {
			// Show a placeholder only in the editor, nothing on the frontend.
			if ( is_admin() || defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return '<div class="giggle-events giggle-events--placeholder">'
					. '<p>' . esc_html__( 'Giggle Events: enter one or more stream IDs in the block settings.', 'giggle-wp' ) . '</p>'
					. '</div>';
			}

			return '';
		}

		$api    = Giggle_API::from_options();
		$result = $api->fetch_experiences( $stream_ids, [
			'language'      => $language,
			'only_bookable' => $only_bkble,
			'date_range'    => $date_range,
			'page_size'     => $page_size,
		] );

		if ( is_wp_error( $result ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<div class="giggle-events giggle-events--error"><p>'
					. esc_html(
						sprintf(
							/* translators: error message */
							__( 'Giggle API error: %s', 'giggle-wp' ),
							$result->get_error_message()
						)
					)
					. '</p></div>';
			}

			return '';
		}

		$items = $result['items'] ?? [];

		if ( empty( $items ) ) {
			return '<div class="giggle-events giggle-events--empty"><p>'
				. esc_html__( 'No experiences found.', 'giggle-wp' )
				. '</p></div>';
		}

		ob_start();
		include GIGGLE_WP_DIR . 'templates/events-list.php';
		$output = ob_get_clean();
		return ( false !== $output ? $output : '' ) . self::render_modal_once();
	}

	private static function render_modal_once(): string {
		if ( self::$modal_rendered ) {
			return '';
		}
		self::$modal_rendered = true;
		ob_start();
		include GIGGLE_WP_DIR . 'templates/giggle-modal.php';
		$output = ob_get_clean();
		return false !== $output ? $output : '';
	}
}
