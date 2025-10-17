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

		// IP callback validate
		register_rest_route( 'litespeed/v3', '/ip_validate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'ip_validate' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		// 1.2. WP REST Dryrun Callback
		register_rest_route( 'litespeed/v3', '/wp_rest_echo', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'wp_rest_echo' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );
		register_rest_route( 'litespeed/v3', '/ping', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'ping' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		// CDN setup callback notification
		register_rest_route( 'litespeed/v3', '/cdn_status', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'cdn_status' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		// Image optm notify_img
		// Need validation
		register_rest_route( 'litespeed/v1', '/notify_img', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'notify_img' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		register_rest_route( 'litespeed/v1', '/notify_ccss', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'notify_ccss' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		register_rest_route( 'litespeed/v1', '/notify_ucss', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'notify_ucss' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		register_rest_route( 'litespeed/v1', '/notify_vpi', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'notify_vpi' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		register_rest_route( 'litespeed/v3', '/err_domains', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'err_domains' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
		] );

		// Image optm check_img
		// Need validation
		register_rest_route( 'litespeed/v1', '/check_img', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'check_img' ],
			'permission_callback' => [ $this, 'is_from_cloud' ],
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

		if ( $crawler_id ) {
			return $this->cls( 'Crawler' )->toggle_activeness( $crawler_id ) ? 1 : 0;
		}
	}

	/**
	 * Check if the request is from cloud nodes.
	 *
	 * @since 4.2
	 * @since 4.4.7 Token/API key validation makes IP validation redundant.
	 * @return bool
	 */
	public function is_from_cloud() {
		return $this->cls( 'Cloud' )->is_from_cloud();
	}

	/**
	 * Ping pong.
	 *
	 * @since 3.0.4
	 * @return mixed
	 */
	public function ping() {
		return $this->cls( 'Cloud' )->ping();
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
	 * Validate IPs from cloud.
	 *
	 * @since 3.0
	 * @return mixed
	 */
	public function ip_validate() {
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
	 * @return mixed
	 */
	public function cdn_status() {
		return $this->cls( 'Cloud' )->update_cdn_status();
	}

	/**
	 * Image optimization notification.
	 *
	 * @since 3.0
	 * @return mixed
	 */
	public function notify_img() {
		return Img_Optm::cls()->notify_img();
	}

	/**
	 * Critical CSS notification.
	 *
	 * @since 7.1
	 * @return mixed
	 */
	public function notify_ccss() {
		self::debug( 'notify_ccss' );
		return CSS::cls()->notify();
	}

	/**
	 * Unique CSS notification.
	 *
	 * @since 5.2
	 * @return mixed
	 */
	public function notify_ucss() {
		self::debug( 'notify_ucss' );
		return UCSS::cls()->notify();
	}

	/**
	 * Viewport Images notification.
	 *
	 * @since 4.7
	 * @return mixed
	 */
	public function notify_vpi() {
		self::debug( 'notify_vpi' );
		return VPI::cls()->notify();
	}

	/**
	 * Error domain report from cloud.
	 *
	 * @since 4.7
	 * @return mixed
	 */
	public function err_domains() {
		self::debug( 'err_domains' );
		return $this->cls( 'Cloud' )->rest_err_domains();
	}

	/**
	 * Launch image check.
	 *
	 * @since 3.0
	 * @return mixed
	 */
	public function check_img() {
		return Img_Optm::cls()->check_img();
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

		// Case #2: Support "plain" permalink settings.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$route = isset( $_GET['rest_route'] ) ? sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ) : '';

		if ( $route && 0 === strpos( trim( $route, '\\/' ), $prefix, 0 ) ) {
			return true;
		}

		if ( !$url ) {
			return false;
		}

		// Case #3: URL path begins with wp-json/ (REST prefix) – safe for subfolder installs.
		$rest_url    = wp_parse_url( site_url( $prefix ) );
		$current_url = wp_parse_url( $url );

		if ( false !== $current_url && ! empty( $current_url['path'] ) && false !== $rest_url && ! empty( $rest_url['path'] ) ) {
			return 0 === strpos( $current_url['path'], $rest_url['path'] );
		}

		return false;
	}
}
