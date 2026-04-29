<?php
/**
 * Giggle API client.
 *
 * Wraps the public Giggle REST API (https://api.giggle.tips).
 * Responses are cached in WordPress transients for 1 hour (filterable).
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Giggle_API {

	private const BASE_URL = 'https://api.giggle.tips';
	private const VERSION  = 'v1';

	/** @var string */
	private string $api_key;

	/** @var string */
	private string $hotel_code;

	public function __construct( string $api_key, string $hotel_code ) {
		$this->api_key    = $api_key;
		$this->hotel_code = $hotel_code;
	}

	/**
	 * Factory: build from stored plugin options.
	 */
	public static function from_options(): self {
		return new self(
			(string) get_option( 'giggle_wp_api_key', '' ),
			(string) get_option( 'giggle_wp_hotel_code', '' )
		);
	}

	// -------------------------------------------------------------------------
	// Public API methods
	// -------------------------------------------------------------------------

	/**
	 * Fetch a paginated list of experiences.
	 *
	 * @param string $stream_ids Comma-separated stream UUIDs (required).
	 * @param array{
	 *   language?: string,
	 *   only_bookable?: bool,
	 *   date_range?: string,
	 *   page_number?: int,
	 *   page_size?: int,
	 * } $args Optional parameters.
	 *
	 * @return array|WP_Error Decoded JSON array on success, WP_Error on failure.
	 */
	public function fetch_experiences( string $stream_ids, array $args = [] ): array|WP_Error {
		if ( '' === trim( $stream_ids ) ) {
			return new WP_Error( 'giggle_no_stream', __( 'No stream IDs provided.', 'giggle-wp' ) );
		}

		$params = array_filter( [
			'streamIds'               => $stream_ids,
			'language'                => $args['language'] ?? '',
			'onlyBookableExperiences' => isset( $args['only_bookable'] ) ? ( $args['only_bookable'] ? 'true' : 'false' ) : null,
			'dateRange'               => $args['date_range'] ?? '',
			'pageNumber'              => isset( $args['page_number'] ) ? (string) $args['page_number'] : null,
			'pageSize'                => isset( $args['page_size'] ) ? (string) $args['page_size'] : null,
		], static fn( $v ) => null !== $v && '' !== $v );

		$cache_key = 'giggle_exp_' . md5( (string) wp_json_encode( $params ) );

		return $this->cached_get( $cache_key, '/api/' . self::VERSION . '/public/experience/list', $params );
	}

	/**
	 * Fetch the list of streams for the configured hotel.
	 *
	 * @param bool $only_public When true, only public streams are returned.
	 *
	 * @return array|WP_Error
	 */
	public function fetch_streams( bool $only_public = false ): array|WP_Error {
		if ( '' === $this->hotel_code ) {
			return new WP_Error( 'giggle_no_hotel', __( 'No hotel code configured.', 'giggle-wp' ) );
		}

		$params = [
			'hotelCode'         => $this->hotel_code,
			'onlyPublicStreams'  => $only_public ? 'true' : 'false',
		];

		$cache_key = 'giggle_streams_' . md5( (string) wp_json_encode( $params ) );

		return $this->cached_get( $cache_key, '/api/' . self::VERSION . '/public/stream/list', $params );
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Perform a GET request using a stale-while-revalidate cache strategy.
	 *
	 * - Stale copy lives for 24 h (filterable via giggle_wp_stale_ttl).
	 * - A separate "fresh" marker expires after 1 h (giggle_wp_cache_ttl).
	 * - When the fresh marker is gone but stale data still exists, the stale
	 *   data is returned immediately and a background WP-Cron job refreshes
	 *   the cache — zero TTFB penalty for the visitor.
	 * - Only a true cold start (both transients missing) triggers a blocking
	 *   API call.
	 */
	private function cached_get( string $cache_key, string $path, array $params ): array|WP_Error {
		$stale = get_transient( $cache_key );

		if ( false !== $stale ) {
			if ( false === get_transient( $cache_key . '_fresh' ) ) {
				$this->schedule_background_refresh( $cache_key, $path, $params );
			}
			return $stale;
		}

		$result = $this->remote_get( $path, $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->store_cache( $cache_key, $result );

		return $result;
	}

	/**
	 * Persist a successful API response with stale + fresh transients.
	 */
	private function store_cache( string $cache_key, array $data ): void {
		$fresh_ttl = (int) apply_filters( 'giggle_wp_cache_ttl', HOUR_IN_SECONDS );
		$stale_ttl = (int) apply_filters( 'giggle_wp_stale_ttl', DAY_IN_SECONDS );

		set_transient( $cache_key, $data, $stale_ttl );
		set_transient( $cache_key . '_fresh', 1, $fresh_ttl );
	}

	/**
	 * Schedule a single WP-Cron event to refresh the cache in the background.
	 */
	private function schedule_background_refresh( string $cache_key, string $path, array $params ): void {
		$args = [ $cache_key, $path, $params ];

		if ( wp_next_scheduled( 'giggle_wp_cache_refresh', $args ) ) {
			return;
		}

		wp_schedule_single_event( time(), 'giggle_wp_cache_refresh', $args );
		spawn_cron();
	}

	/**
	 * WP-Cron callback: re-fetch and store a single cache entry.
	 * Called via the giggle_wp_cache_refresh action.
	 */
	public static function handle_cache_refresh( string $cache_key, string $path, array $params ): void {
		$allowed_paths = [
			'/api/' . self::VERSION . '/public/experience/list',
			'/api/' . self::VERSION . '/public/stream/list',
		];
		if ( ! in_array( $path, $allowed_paths, true ) ) {
			return;
		}

		$instance = self::from_options();
		$result   = $instance->remote_get( $path, $params );

		if ( ! is_wp_error( $result ) ) {
			$instance->store_cache( $cache_key, $result );
		}
	}

	/**
	 * Execute a wp_remote_get() call and decode JSON.
	 *
	 * @return array|WP_Error
	 */
	private function remote_get( string $path, array $params ): array|WP_Error {
		if ( '' === $this->api_key ) {
			return new WP_Error( 'giggle_no_key', __( 'Giggle API key is not configured.', 'giggle-wp' ) );
		}

		$url = add_query_arg( $params, self::BASE_URL . $path );

		$response = wp_remote_get( $url, [
			'headers' => [
				'api-key' => $this->api_key,
				'Accept'  => 'application/json',
			],
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 401 === $code ) {
			return new WP_Error( 'giggle_unauthorized', __( 'Giggle API: authentication failed. Check your API key.', 'giggle-wp' ) );
		}

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'giggle_http_error',
				sprintf(
					/* translators: 1: HTTP status code */
					__( 'Giggle API returned HTTP %d.', 'giggle-wp' ),
					$code
				)
			);
		}

		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'giggle_json_error', __( 'Giggle API returned invalid JSON.', 'giggle-wp' ) );
		}

		return $data;
	}
}
