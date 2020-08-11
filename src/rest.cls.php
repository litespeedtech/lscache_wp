<?php
/**
 * The REST related class.
 *
 * @since      	2.9.4
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class REST extends Instance {
	protected static $_instance;

	private $_internal_rest_status = false;

	/**
	 * Confructor of ESI
	 *
	 * @since    2.9.4
	 * @access protected
	 */
	protected function __construct() {
		// Hook to internal REST call
		add_filter( 'rest_request_before_callbacks', array( $this, 'set_internal_rest_on' ) );
		add_filter( 'rest_request_after_callbacks', array( $this, 'set_internal_rest_off' ) );

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Register REST hooks
	 *
	 * @since  3.0
	 * @access public
	 */
	public function rest_api_init() {
		register_rest_route( 'litespeed/v1', '/tool/check_ip', array(
			'methods' => 'GET',
			'callback' => array( $this, 'check_ip' ),
			'permission_callback'	=> function() {
				return current_user_can( 'manage_network_options' ) || current_user_can( 'manage_options' );
			}
		) );

		// fetch_esi_nonce
		// Need validation
		register_rest_route( 'litespeed/v1', '/fetch_esi_nonce', array(
			'methods' => 'POST',
			'callback' => array( $this, 'fetch_esi_nonce' ),
			'permission_callback'	=> function() {
				return current_user_can( 'manage_network_options' ) || current_user_can( 'manage_options' );
			}
		) );

		// IP callback validate
		register_rest_route( 'litespeed/v1', '/ip_validate', array(
			'methods' => 'POST',
			'callback' => array( $this, 'ip_validate' ),
			'permission_callback'	=> '__return_true',
		) );

		// Token callback validate
		register_rest_route( 'litespeed/v1', '/token', array(
			'methods' => 'POST',
			'callback' => array( $this, 'token' ),
			'permission_callback'	=> '__return_true',
		) );
		register_rest_route( 'litespeed/v1', '/token', array(
			'methods' => 'GET',
			'callback' => array( $this, 'token_get' ),
			'permission_callback'	=> '__return_true',
		) );
		register_rest_route( 'litespeed/v1', '/ping', array(
			'methods' => 'GET',
			'callback' => array( $this, 'ping' ),
			'permission_callback'	=> '__return_true',
		) );

		// API key callback notification
		register_rest_route( 'litespeed/v1', '/apikey', array(
			'methods' => 'POST',
			'callback' => array( $this, 'apikey' ),
			'permission_callback'	=> '__return_true',
		) );

		// Image optm notify_img
		// Need validation
		register_rest_route( 'litespeed/v1', '/notify_img', array(
			'methods' => 'POST',
			'callback' => array( $this, 'notify_img' ),
			'permission_callback'	=> '__return_true',
		) );

		// Image optm check_img
		// Need validation
		register_rest_route( 'litespeed/v1', '/check_img', array(
			'methods' => 'POST',
			'callback' => array( $this, 'check_img' ),
			'permission_callback'	=> '__return_true',
		) );

	}

	/**
	 * Token get for
	 *
	 * @since  3.0.4
	 */
	public function token_get() {
		return Cloud::ok();
	}

	/**
	 * Ping pong
	 *
	 * @since  3.0.4
	 */
	public function ping() {
		return Cloud::ok( array( 'ver' => Core::VER ) );
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function check_ip() {
		return Tool::get_instance()->check_ip();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function ip_validate() {
		return Cloud::get_instance()->ip_validate();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function token() {
		return Cloud::get_instance()->token_validate();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function apikey() {
		return Cloud::get_instance()->save_apikey();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function notify_img() {
		return Img_Optm::get_instance()->notify_img();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function check_img() {
		return Img_Optm::get_instance()->check_img();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.2.3
	 */
	public function fetch_esi_nonce() {
		return ESI::get_instance()->fetch_esi_nonce();
	}

	/**
	 * Set internal REST tag to ON
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function set_internal_rest_on( $not_used = null )
	{
		$this->_internal_rest_status = true;
		Debug2::debug2( '[REST] ✅ Internal REST ON [filter] rest_request_before_callbacks' );

		return $not_used;
	}

	/**
	 * Set internal REST tag to OFF
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function set_internal_rest_off( $not_used = null )
	{
		$this->_internal_rest_status = false;
		Debug2::debug2( '[REST] ❎ Internal REST OFF [filter] rest_request_after_callbacks' );

		return $not_used;
	}

	/**
	 * Get internal REST tag
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function is_internal_rest()
	{
		return $this->_internal_rest_status;
	}

	/**
	 * Check if an URL or current page is REST req or not
	 *
	 * @since  2.9.3
	 * @since  2.9.4 Moved here from Utility, dropped static
	 * @access public
	 */
	public function is_rest( $url = false )
	{
		// For WP 4.4.0- compatibility
		if ( ! function_exists( 'rest_get_url_prefix' ) ) {
			return defined( 'REST_REQUEST' ) && REST_REQUEST;
		}

		$prefix = rest_get_url_prefix();

		// Case #1: After WP_REST_Request initialisation
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Case #2: Support "plain" permalink settings
		if ( isset( $_GET[ 'rest_route' ] ) && strpos( trim( $_GET[ 'rest_route' ], '\\/' ), $prefix , 0 ) === 0 ) {
			return true;
		}

		if ( ! $url ) {
			return false;
		}

		// Case #3: URL Path begins with wp-json/ (REST prefix) Safe for subfolder installation
		$rest_url = wp_parse_url( site_url( $prefix ) );
		$current_url = wp_parse_url( $url );
		// Debug2::debug( '[Util] is_rest check [base] ', $rest_url );
		// Debug2::debug( '[Util] is_rest check [curr] ', $current_url );
		// Debug2::debug( '[Util] is_rest check [curr2] ', wp_parse_url( add_query_arg( array( ) ) ) );
		return strpos( $current_url[ 'path' ], $rest_url[ 'path' ] ) === 0;
	}
}
