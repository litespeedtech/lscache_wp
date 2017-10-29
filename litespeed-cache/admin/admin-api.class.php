<?php
/**
 * Admin API
 *
 * @since      1.5
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Admin_API
{
	private static $_instance ;

	private $_sapi_key ;

	const DB_SAPI_KEY = 'litespeed_sapi_key' ;
	const DB_SAPI_KEY_HASH = 'litespeed_sapi_key_hash' ;
	const DB_SAPI_IMG_REDUCED = 'litespeed_sapi_img_reduced' ;

	const TYPE_REQUEST_KEY = 'request_key' ;

	// For each request, send a callback to confirm
	const TYPE_REQUEST_CALLBACK = 'request_callback' ;
	const TYPE_NOTIFY_IMG_OPTIMIZED = 'notify_img_optimized' ;

	const SAPI_ACTION_REQUEST_KEY = 'request_key' ;
	const SAPI_ACTION_REQUEST_OPTIMIZE = 'request_optimize' ;
	const SAPI_ACTION_PULL_IMG = 'client_pull' ;

	/**
	 * Init
	 *
	 * @since  1.5
	 * @access private
	 */
	private function __construct()
	{
		$this->_sapi_key = get_option( self::DB_SAPI_KEY ) ?: '' ;
	}

	/**
	 * Handle aggressive callback requests from LiteSpeed server
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function sapi_aggressive_callback()
	{

		switch ( LiteSpeed_Cache_Router::verify_type() ) {
			case self::TYPE_NOTIFY_IMG_OPTIMIZED :
				LiteSpeed_Cache_Media::get_instance()->notify_img_optimized() ;
				break ;

			default:
				break ;
		}

		exit ;
	}

	/**
	 * Handle passive callback requests from LiteSpeed server
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function sapi_passive_callback()
	{
		$instance = self::get_instance() ;

		switch ( LiteSpeed_Cache_Router::verify_type() ) {
			case self::TYPE_REQUEST_CALLBACK :
				$instance->_request_callback() ;
				break ;

			default:
				break ;
		}

		exit ;
	}

	/**
	 * Handle local request
	 *
	 * @since  1.5
	 * @access public
	 * @return string The msg shown in admin page
	 */
	public static function sapi_proceed()
	{
		$instance = self::get_instance() ;

		switch ( LiteSpeed_Cache_Router::verify_type() ) {
			case self::TYPE_REQUEST_KEY :
				$instance->_request_key() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * request key callback from LiteSpeed
	 *
	 * @since  1.5
	 * @access private
	 */
	private function _request_callback()
	{
		$key_hash = get_option( self::DB_SAPI_KEY_HASH ) ;
		LiteSpeed_Cache_Log::debug( 'SAPI __callback request hash: ' . $key_hash ) ;
		exit( $key_hash ) ;
	}

	/**
	 * request key from LiteSpeed
	 *
	 * @since  1.5
	 * @access private
	 */
	private function _request_key()
	{
		// reset current key first
		delete_option( self::DB_SAPI_KEY ) ;

		// Send request to LiteSpeed
		$json = $this->_post( self::SAPI_ACTION_REQUEST_KEY, home_url() ) ;

		// Check if get key&server correctly
		if ( empty( $json[ 'auth_key' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI request key failed: ', $json ) ;
			$msg = sprintf( __( 'SAPI Error %s', 'litespeed-cache' ), $json ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return ;
		}

		// store data into option locally
		update_option( self::DB_SAPI_KEY, $json[ 'auth_key' ] ) ;
		LiteSpeed_Cache_Log::debug( 'SAPI applied auth_key' ) ;

		if ( ! empty( $json[ 'reduced' ] ) ) {
			update_option( self::DB_SAPI_IMG_REDUCED, $json[ 'reduced' ] ) ;
		}

		$msg = __( 'Communicated with LiteSpeed Image Optimization Server successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

	}

	/**
	 * Check if is valid callback from litespeed passive request
	 *
	 * @since  1.5
	 * @access public
	 * @return bool True if correct
	 */
	public static function sapi_valiate_passive_callback()
	{
		if ( empty( $_REQUEST[ 'hash' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI __callback bypassed passive check' ) ;
			return false ;
		}
		$instance = self::get_instance() ;

		// use tmp hash to check
		$key_hash = get_option( self::DB_SAPI_KEY_HASH ) ;
		$hash_check = md5( $key_hash ) === $_REQUEST[ 'hash' ] ;

		LiteSpeed_Cache_Log::debug( 'SAPI __callback hash check ' . $key_hash . ': ' . ( $hash_check ? 'passed' : 'failed' ) ) ;

		return $hash_check ;
	}

	/**
	 * Check if is valid callback from litespeed aggressive request
	 *
	 * @since  1.6
	 * @access public
	 * @return bool True if correct
	 */
	public static function sapi_validate_aggressive_callback()
	{
		$instance = self::get_instance() ;

		// don't have auth_key yet
		if ( ! $instance->_sapi_key ) {
			LiteSpeed_Cache_Log::debug( 'SAPI __callback aggressive check failed: No init key' ) ;
			return false ;
		}

		// Once client has auth_key, each time when callback to check, need to carry on this key
		if ( empty( $_REQUEST[ 'auth_key' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI __callback aggressive check failed: lack of auth_key' ) ;
			return false ;
		}

		$res = md5( $instance->_sapi_key ) === $_REQUEST[ 'auth_key' ] ;
		LiteSpeed_Cache_Log::debug( 'SAPI __callback aggressive auth_key check: ' . ( $res ? 'passed' : 'failed' ) ) ;
		return $res ;
	}

	/**
	 * Post data to LiteSpeed server
	 *
	 * @since  1.6
	 * @access public
	 * @param  array $data
	 */
	public static function post( $action, $data, $server = false )
	{
		$instance = self::get_instance() ;
		return $instance->_post( $action, $data, $server ) ;
	}

	/**
	 * Post data to LiteSpeed server
	 *
	 * @since  1.6
	 * @access private
	 * @param  array $data
	 */
	private function _post( $action, $data, $server = false )
	{
		$hash = Litespeed_String::rrand( 16 ) ;
		// store hash
		update_option( self::DB_SAPI_KEY_HASH, $hash ) ;

		if ( $server == false ) {
			$server = 'https://wp.api.litespeedtech.com' ;
		}

		$url = $server . '/' . $action ;

		LiteSpeed_Cache_Log::debug( 'SAPI posting to : ' . $url ) ;

		$param = array(
			'auth_key'	=> $this->_sapi_key,
			'v'	=> LiteSpeed_Cache::PLUGIN_VERSION,
			'hash'	=> $hash,
			'data' => $data,
		) ;
		$response = wp_remote_post( $url, array( 'body' => $param ) ) ;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			LiteSpeed_Cache_Log::debug( 'SAPI failed to post: ' . $error_message ) ;
			return $error_message ;
		}

		// parse data from server
		$json = json_decode( $response[ 'body' ], true ) ;

		if ( ! is_array( $json ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI failed to decode post json: ' . $response[ 'body' ] ) ;
		}

		return $json ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.5
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}