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
	const DB_SAPI_SERVER = 'litespeed_sapi_server' ;
	const DB_SAPI_KEY_HASH = 'litespeed_sapi_key_hash' ;

	const ACTION_REQUEST_KEY = 'request_key' ;
	const ACTION_REQUEST_KEY_CALLBACK = 'request_key_callback' ;

	const SAPI_ACTION_REQUEST_KEY = 'request_key' ;

	/**
	 * Init
	 *
	 * @since  1.5
	 * @access private
	 */
	private function __construct()
	{
		$this->_sapi_key = get_option( self::DB_SAPI_KEY ) ;
	}


	/**
	 * Handle callback requests from LiteSpeed server
	 *
	 * @since  1.5
	 * @access public
	 */
	public static function sapi_callback()
	{
		if ( empty( $_GET[ 'type' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI callback no type ' ) ;
			return ;
		}
		LiteSpeed_Cache_Log::debug( 'SAPI callback type: ' . $_GET[ 'type' ] ) ;

		$instance = self::get_instance() ;

		switch ( $_GET[ 'type' ] ) {
			case self::ACTION_REQUEST_KEY_CALLBACK :
				$instance->_request_key_callback() ;
				break ;

			default:
				break ;
		}


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
		if ( empty( $_GET[ 'type' ] ) ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'SAPI proceed type: ' . $_GET[ 'type' ] ) ;

		$instance = self::get_instance() ;

		switch ( $_GET[ 'type' ] ) {
			case self::ACTION_REQUEST_KEY :
				return $instance->_request_key() ;
				break ;

			default:
				break ;
		}

	}

	/**
	 * request key callback from LiteSpeed
	 *
	 * @since  1.5
	 * @access private
	 */
	private function _request_key_callback()
	{
		$key_hash = get_transient( self::DB_SAPI_KEY_HASH ) ;
		LiteSpeed_Cache_Log::debug( 'SAPI callback request key hash: ' . $key_hash ) ;
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
		$hash = Litespeed_String::rrand( 16 ) ;
		// store hash
		set_transient( self::DB_SAPI_KEY_HASH, $hash, 300 ) ;

		// send the request
		$url = 'https://wp.api.litespeedtech.com/' . self::SAPI_ACTION_REQUEST_KEY ;
		$param = array(
			'hash'	=> $hash,
			'callback' => home_url(),
		) ;
		$response = wp_remote_post( $url, array( 'body' => $param ) ) ;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			LiteSpeed_Cache_Log::debug( 'SAPI failed to send request' ) ;
			return ;
		}

		// parse data from server
		set_error_handler( 'litespeed_exception_handler' ) ;
		try {
			$json = json_decode( $response[ 'body' ], true ) ;
		}
		catch ( ErrorException $e ) {
			LiteSpeed_Cache_Log::debug( 'SAPI failed to decode json: ' . $response[ 'body' ] ) ;
			return ;
		}
		restore_error_handler() ;

		if ( empty( $json[ 'auth_key' ] ) || empty( $json[ 'distribute_server' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI failed to get key and server: ' . $response[ 'body' ] ) ;
			return ;
		}

		// store data into option locally
		update_option( self::DB_SAPI_KEY, $json[ 'auth_key' ] ) ;
		update_option( self::DB_SAPI_SERVER, $json[ 'distribute_server' ] ) ;
		LiteSpeed_Cache_Log::debug( 'SAPI distribute server: ' . $json[ 'distribute_server' ] ) ;

		return __( 'Generate the key from server successfully', 'litespeed-cache' ) ;
	}

	/**
	 * Check if the get token is correct with server api key
	 *
	 * @since  1.5
	 * @access public
	 * @return bool True if correct
	 */
	public static function sapi_token_check()
	{
		if ( empty( $_GET[ 'token' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'SAPI bypassed token check' ) ;
			return false ;
		}
		$instance = self::get_instance() ;

		// don't have auth_key yet
		if ( ! $instance->_sapi_key ) {
			// use tmp hash to check
			$key_hash = get_transient( self::DB_SAPI_KEY_HASH ) ;
			$res = md5( $key_hash ) === $_GET[ 'token' ] ;

			LiteSpeed_Cache_Log::debug( 'SAPI token init check ' . $key_hash . ': ' . ( $res ? 'passed' : 'failed' ) ) ;
			return $res ;
		}

		$res = md5( $instance->_sapi_key ) === $_GET[ 'token' ] ;
		LiteSpeed_Cache_Log::debug( 'SAPI token check: ' . ( $res ? 'passed' : 'failed' ) ) ;
		return $res ;
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