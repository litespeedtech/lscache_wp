<?php
/**
 * REST endpoints and helpers for LiteSpeed.
 *
 * @since   2.9.4
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class REST
 *
 * Registers plugin REST endpoints and exposes helpers for REST detection.
 */
class REST extends Root {

	const LOG_TAG = '☎️';

	/**
	 * Whether current request is an internal REST call.
	 *
	 * @var bool
	 */
	private $_internal_rest_status = false;

	/**
	 * Constructor.
	 *
	 * @since 2.9.4
	 */
	public function __construct() {
		// Hook to internal REST call.
		add_filter( 'rest_request_before_callbacks', [ $this, 'set_internal_rest_on' ] );
		add_filter( 'rest_request_after_callbacks', [ $this, 'set_internal_rest_off' ] );

		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function rest_api_init() {
		// Activate or deactivate a specific crawler callback
		register_rest_route( 'litespeed/v1', '/toggle_crawler_state', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_crawler_state' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_network_options' ) || current_user_can( 'manage_options' );
			},
		] );

		register_rest_route( 'litespeed/v1', '/tool/check_ip', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'check_ip' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_network_options' ) || current_user_can( 'manage_options' );
			},
		] );

		register_rest_route( 'litespeed/v1', '/guest/sync', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'guest_sync' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_network_options' ) || current_user_can( 'manage_options' );
			},
		] );

		// IP callback validate
		register_rest_route( 'litespeed/v3', '/ip_validate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'ip_validate' ],
			'permission_callback' => function ( $request ) {
				return $this->cls( 'Cloud' )->validate_signed_callback( $request, Cloud::SIGN_ACTION_IP_VALIDATE );
			},
		] );

		// 1.2. WP REST Dryrun Callback
		register_rest_route( 'litespeed/v3', '/wp_rest_echo', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'wp_rest_echo' ],
			'permission_callback' => '__return_true',
		] );
		register_rest_route( 'litespeed/v3', '/ping', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'ping' ],
			'permission_callback' => function ( $request ) {
				return $this->cls( 'Cloud' )->validate_signed_callback( $request, Cloud::SIGN_ACTION_PING );
			},
		] );

		// CDN setup callback notification
		register_rest_route( 'litespeed/v3', '/cdn_status', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'cdn_status' ],
			'permission_callback' => function ( $request ) {
				return $this->cls( 'Cloud' )->validate_signed_callback( $request, Cloud::SIGN_ACTION_CDN_STATUS );
			},
		] );

		// Image optm notify_img
		register_rest_route( 'litespeed/v1', '/notify_img', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'notify_img' ],
			'permission_callback' => function ( $request ) {
				return $this->cls( 'Cloud' )->validate_signed_callback( $request, Cloud::SIGN_ACTION_NOTIFY_IMG );
			},
		] );

		register_rest_route( 'litespeed/v3', '/err_domains', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'err_domains' ],
			'permission_callback' => function ( $request ) {
				return $this->cls( 'Cloud' )->validate_signed_callback( $request, Cloud::SIGN_ACTION_ERR_DOMAINS );
			},
		] );
	}

	/**
	 * Call to freeze or melt the crawler clicked
	 *
	 * @since  4.3
	 */
	public function toggle_crawler_state() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- REST API nonce verified by WordPress
		$crawler_id = isset( $_POST['crawler_id'] ) ? sanitize_text_field( wp_unslash( $_POST['crawler_id'] ) ) : '';

		if ( '' !== $crawler_id ) {
			return $this->cls( 'Crawler' )->toggle_activeness( $crawler_id ) ? 1 : 0;
		}
	}

	/**
	 * Ping pong.
	 *
	 * @since 3.0.4
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public function ping( $request ) {
		return $this->cls( 'Cloud' )->ping( $request->get_body() );
	}

	/**
	 * Launch IP check.
	 *
	 * @since 3.0
	 * @return mixed
	 */
	public function check_ip() {
		return Tool::cls()->check_ip();
	}

	/**
	 * Sync Guest Mode IP/UA lists.
	 *
	 * @since 7.7
	 * @return array
	 */
	public function guest_sync() {
		return Guest::cls()->sync_lists();
	}

	/**
	 * Validate IPs from cloud.
	 *
	 * @since 3.0
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public function ip_validate( $request ) {
		return $this->cls( 'Cloud' )->ip_validate();
	}

	/**
	 * REST echo helper.
	 *
	 * @since 3.0
	 * @return mixed
	 */
	public function wp_rest_echo() {
		return $this->cls( 'Cloud' )->wp_rest_echo();
	}

	/**
	 * Endpoint to notify plugin of CDN status updates.
	 *
	 * @since 7.0
	 * @since 7.9.1 Forwards the exact raw body the signature was verified over.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public function cdn_status( $request ) {
		return $this->cls( 'Cloud' )->update_cdn_status( $request->get_body() );
	}

	/**
	 * Image optimization notification.
	 *
	 * @since 3.0
	 * @since 7.9.1 Accepts the REST request and forwards its exact raw body.
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public function notify_img( $request ) {
		return Img_Optm::cls()->notify_img( $request->get_body() );
	}

	/**
	 * Error domain report from cloud.
	 *
	 * @since 4.7
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public function err_domains( $request ) {
		self::debug( 'err_domains' );
		return $this->cls( 'Cloud' )->rest_err_domains( $request->get_body() );
	}

	/**
	 * Return a standardized error payload.
	 *
	 * @since 5.7.0.1
	 * @param string|int $code Error code.
	 * @return array
	 */
	public static function err( $code ) {
		return [
			'_res' => 'err',
			'_msg' => $code,
		];
	}

	/**
	 * Set internal REST tag to ON.
	 *
	 * @since 2.9.4
	 * @param mixed $not_used Passthrough value from the filter.
	 * @return mixed
	 */
	public function set_internal_rest_on( $not_used = null ) {
		$this->_internal_rest_status = true;
		Debug2::debug2( '[REST] ✅ Internal REST ON [filter] rest_request_before_callbacks' );

		return $not_used;
	}

	/**
	 * Set internal REST tag to OFF.
	 *
	 * @since 2.9.4
	 * @param mixed $not_used Passthrough value from the filter.
	 * @return mixed
	 */
	public function set_internal_rest_off( $not_used = null ) {
		$this->_internal_rest_status = false;
		Debug2::debug2( '[REST] ❎ Internal REST OFF [filter] rest_request_after_callbacks' );

		return $not_used;
	}

	/**
	 * Whether current request is an internal REST call.
	 *
	 * @since 2.9.4
	 * @return bool
	 */
	public function is_internal_rest() {
		return $this->_internal_rest_status;
	}

	/**
	 * Match URI rules against pretty and rest_route spellings.
	 *
	 * @since 7.9.1
	 * @param string $req_uri  Raw request URI.
	 * @param array  $rules    URI rules.
	 * @param bool   $has_ttl  Whether rules include a TTL.
	 * @return bool|string|array
	 */
	public static function str_hit_uri( $req_uri, $rules, $has_ttl = false ) {
		$uris = [ $req_uri ];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Type is checked before sanitization.
		$route = isset( $_GET['rest_route'] ) ? wp_unslash( $_GET['rest_route'] ) : '';
		$route = is_string( $route ) ? sanitize_text_field( $route ) : '';
		if ( '' !== $route && function_exists( 'rest_get_url_prefix' ) ) {
			$prefix = wp_parse_url( home_url( rest_get_url_prefix() ), PHP_URL_PATH );
			if ( $prefix ) {
				$equivalent = '/' . ltrim( $prefix, '/' ) . '/' . ltrim( $route, '/' );
				// Carry the other arguments over byte for byte: rules compare exactly, so parse_str() would break them.
				$query = wp_parse_url( $req_uri, PHP_URL_QUERY );
				$kept  = [];
				if ( is_string( $query ) && '' !== $query ) {
					foreach ( explode( '&', $query ) as $segment ) {
						// Empty segments are kept too — dropping `&&` would itself be a rewrite.
						$name = urldecode( explode( '=', $segment, 2 )[0] );
						if ( 'rest_route' === $name || 0 === strpos( $name, 'rest_route[' ) ) {
							continue;
						}
						$kept[] = $segment;
					}
				}
				if ( $kept ) {
					$equivalent .= '?' . implode( '&', $kept );
				}
				$uris[] = $equivalent;
			}
		}

		foreach ( $uris as $uri ) {
			$hit = Utility::str_hit_array( $uri, $rules, $has_ttl );
			if ( $hit ) {
				return $hit;
			}
		}
		return false;
	}

	/**
	 * Check whether a URL or current page is a REST request.
	 *
	 * @since 2.9.3
	 * @since 2.9.4 Moved here from Utility, dropped static.
	 * @param string|false $url URL to check; when false checks current request.
	 * @return bool
	 */
	public function is_rest( $url = false ) {
		// For WP 4.4.0- compatibility.
		if ( ! function_exists( 'rest_get_url_prefix' ) ) {
			return ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		}

		$prefix = rest_get_url_prefix();

		// Case #1: After WP_REST_Request initialization.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Case #2: Support the rest_route query used by plain permalinks.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Type is checked before sanitization.
		$route = isset( $_GET['rest_route'] ) ? wp_unslash( $_GET['rest_route'] ) : '';
		$route = is_string( $route ) ? sanitize_text_field( $route ) : '';

		if ( false === $url && '' !== $route ) {
			return true;
		}

		if ( !$url ) {
			return false;
		}

		// Case #3: Match the home-based REST path, including subfolder installs.
		$rest_url    = wp_parse_url( home_url( $prefix ) );
		$current_url = wp_parse_url( $url );

		if ( false !== $current_url && ! empty( $current_url['path'] ) && false !== $rest_url && ! empty( $rest_url['path'] ) ) {
			return 0 === strpos( $current_url['path'], $rest_url['path'] );
		}

		return false;
	}
}
