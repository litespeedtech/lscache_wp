<?php
/**
 * Admin API
 *
 * @since      1.5
 * @package    LiteSpeed
 * @subpackage LiteSpeed/src
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Admin_API
{
	private static $_instance ;

	private $_iapi_key ;
	private $_iapi_cloud ;

	const DB_API_KEY = 'litespeed_api_key' ;
	const DB_API_CLOUD = 'litespeed_api_cloud' ;
	const DB_API_KEY_HASH = 'litespeed_api_key_hash' ;

	// For each request, send a callback to confirm
	const TYPE_RESET_KEY = 'reset_key' ;

	const IAPI_ACTION_REQUEST_KEY = 'request_key' ;
	const IAPI_ACTION_LIST_CLOUDS = 'list_clouds' ;
	const IAPI_ACTION_MEDIA_SYNC_DATA = 'media_sync_data' ;
	const IAPI_ACTION_REQUEST_OPTIMIZE = 'request_optimize' ;
	const IAPI_ACTION_IMG_TAKEN = 'client_img_taken' ;
	const IAPI_ACTION_REQUEST_DESTROY = 'imgoptm_destroy' ;
	const IAPI_ACTION_REQUEST_DESTROY_UNFINISHED = 'imgoptm_destroy_unfinished' ;
	const IAPI_ACTION_ENV_REPORT = 'env_report' ;
	const IAPI_ACTION_PLACEHOLDER 	= 'placeholder' ;
	const IAPI_ACTION_LQIP 			= 'lqip' ;
	const IAPI_ACTION_CCSS = 'ccss' ;
	const IAPI_ACTION_PAGESCORE = 'pagescore' ;

	/**
	 * Init
	 *
	 * @since  1.5
	 * @access private
	 */
	private function __construct()
	{
		$this->_iapi_key = get_option( self::DB_API_KEY ) ?: '' ;
		$this->_iapi_cloud = get_option( self::DB_API_CLOUD ) ?: '' ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.7.2
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_RESET_KEY :
				$instance->_reset_key() ;
				break ;

			default:
				break ;
		}

		Admin::redirect() ;
	}

	/**
	 * request key callback from LiteSpeed
	 *
	 * @since  1.5
	 * @access public
	 */
	public function hash()
	{
		if ( empty( $_POST[ 'hash' ] ) ) {
			Log::debug( '[IAPI] Lack of hash param' ) ;
			return array( '_res' => 'err', '_msg' => 'lack_of_param' ) ;
		}

		$key_hash = get_option( self::DB_API_KEY_HASH ) ;

		if ( $_POST[ 'hash' ] !== md5( $key_hash ) ) {
			Log::debug( '[IAPI] __callback request hash wrong: md5(' . $key_hash . ') !== ' . $_POST[ 'hash' ] ) ;
			return array( '_res' => 'err', '_msg' => 'Error hash code' ) ;
		}

		Control::set_nocache( 'litespeed hash validation' ) ;

		Log::debug( '[IAPI] __callback request hash: ' . $key_hash ) ;

		delete_option( self::DB_API_KEY_HASH ) ;

		return array( 'hash' => $key_hash ) ;
	}

	/**
	 * Get data from LiteSpeed cloud server
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function get( $action, $data = array(), $server = false )
	{
		$instance = self::get_instance() ;

		/**
		 * All requests must have closet cloud server too
		 * @since  2.9
		 */
		if ( ! $instance->_iapi_cloud ) {
			$instance->_detect_cloud() ;
		}

		return $instance->_get( $action, $data, $server ) ;
	}

	/**
	 * Post data to LiteSpeed cloud server
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function post( $action, $data = false, $server = false, $no_hash = false, $time_out = false )
	{
		$instance = self::get_instance() ;

		/**
		 * All requests must have closet cloud server too
		 * @since  2.9
		 */
		if ( ! $instance->_iapi_cloud ) {
			$instance->_detect_cloud() ;
		}

		/**
		 * All requests must have api_key first
		 * @since  1.6.5
		 */
		if ( ! $instance->_iapi_key ) {
			$instance->_request_key() ;
		}

		return $instance->_post( $action, $data, $server, $no_hash, $time_out ) ;
	}

	/**
	 * request key from LiteSpeed
	 *
	 * This needs callback validation, so don't use for generic services which don't need security
	 *
	 * @since  1.5
	 * @access private
	 */
	private function _request_key()
	{
		Log::debug( '[IAPI] req auth_key' ) ;

		// Send request to LiteSpeed
		$json = $this->_post( self::IAPI_ACTION_REQUEST_KEY, home_url(), true ) ;

		// Check if get key&server correctly
		if ( empty( $json[ 'auth_key' ] ) ) {
			Log::debug( '[IAPI] request key failed: ', $json ) ;

			if ( $json ) {
				$msg = sprintf( __( 'IAPI Error %s', 'litespeed-cache' ), $json ) ;
				Admin_Display::error( $msg ) ;
			}
			return ;
		}

		// store data into option locally
		update_option( self::DB_API_KEY, $json[ 'auth_key' ] ) ;
		Log::debug( '[IAPI] applied auth_key' ) ;

		$this->_iapi_key = $json[ 'auth_key' ] ;
	}

	/**
	 * ping clouds from LiteSpeed
	 *
	 * @since  2.9
	 * @access private
	 */
	private function _detect_cloud()
	{
		// Send request to LiteSpeed
		$json = $this->_post( self::IAPI_ACTION_LIST_CLOUDS, home_url(), false, true ) ;

		// Check if get list correctly
		if ( empty( $json[ 'list' ] ) ) {
			Log::debug( '[IAPI] request cloud list failed: ', $json ) ;

			if ( $json ) {
				$msg = sprintf( __( 'IAPI Error %s', 'litespeed-cache' ), $json ) ;
				Admin_Display::error( $msg ) ;
			}
			return ;
		}

		// Ping closest cloud
		$speed_list = array() ;
		foreach ( $json[ 'list' ] as $v ) {
			$speed_list[ $v ] = Utility::ping( $v ) ;
		}
		$min = min( $speed_list ) ;

		if ( $min == 99999 ) {
			Log::debug( '[IAPI] failed to ping all clouds' ) ;
			return ;
		}
		$closest = array_search( $min, $speed_list ) ;

		Log::debug( '[IAPI] Found closest cloud ' . $closest ) ;

		// store data into option locally
		update_option( self::DB_API_CLOUD, $closest ) ;

		$this->_iapi_cloud = $closest ;

		// sync API key
		$this->_request_key() ;
	}

	/**
	 * delete key
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _reset_key()
	{
		delete_option( self::DB_API_KEY ) ;
		delete_option( self::DB_API_CLOUD ) ;
		Log::debug( '[IAPI] delete auth_key & closest cloud' ) ;

		$msg = __( 'Reset IAPI key successfully.', 'litespeed-cache' ) ;
		Admin_Display::succeed( $msg ) ;
	}

	/**
	 * Get data from LiteSpeed cloud server
	 *
	 * @since  2.9
	 * @access private
	 */
	private function _get( $action, $data = false, $server = false )
	{

		if ( $server == false ) {
			$server = 'https://wp.api.litespeedtech.com' ;
		}
		elseif ( $server === true ) {
			$server = $this->_iapi_cloud ;
		}

		$url = $server . '/' . $action ;

		if ( $data ) {
			$url .= '?' . http_build_query( $data ) ;
		}

		Log::debug( '[IAPI] getting from : ' . $url ) ;

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) ) ;

		// Parse response data
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			Log::debug( '[IAPI] failed to get: ' . $error_message ) ;
			return false ;
		}

		$data = $response[ 'body' ] ;

		return $data ;

	}

	/**
	 * Post data to LiteSpeed cloud server
	 *
	 * @since  1.6
	 * @access private
	 * @return  string | array Must return an error msg string or json array
	 */
	private function _post( $action, $data = false, $server = false, $no_hash = false, $time_out = false )
	{
		$hash = 'no_hash' ;
		if ( ! $no_hash ) {
			$hash = Str::rrand( 16 ) ;
			// store hash
			update_option( self::DB_API_KEY_HASH, $hash ) ;
		}

		if ( $server == false ) {
			$server = 'https://wp.api.litespeedtech.com' ;
		}
		elseif ( $server === true ) {
			$server = $this->_iapi_cloud ;
		}

		$url = $server . '/' . $action ;

		Log::debug( '[IAPI] posting to : ' . $url ) ;

		$param = array(
			'auth_key'	=> $this->_iapi_key,
			'cloud'	=> $this->_iapi_cloud,
			'v'	=> Core::PLUGIN_VERSION,
			'hash'	=> $hash,
			'data' => $data,
		) ;
		/**
		 * Extended timeout to avoid cUrl 28 timeout issue as we need callback validation
		 * @since 1.6.4
		 */
		$response = wp_remote_post( $url, array( 'body' => $param, 'timeout' => $time_out ?: 15 ) ) ;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			Log::debug( '[IAPI] failed to post: ' . $error_message ) ;
			return $error_message ;
		}

		// parse data from server
		$json = json_decode( $response[ 'body' ], true ) ;

		if ( ! is_array( $json ) ) {
			Log::debug( '[IAPI] failed to decode post json: ' . $response[ 'body' ] ) ;

			$msg = __( 'Failed to post via WordPress', 'litespeed-cache' ) . ': ' . $response[ 'body' ] ;
			Admin_Display::error( $msg ) ;

			return false ;
		}

		if ( ! empty( $json[ '_err' ] ) ) {
			Log::debug( '[IAPI] _err: ' . $json[ '_err' ] ) ;
			$msg = __( 'Failed to communicate with LiteSpeed image server', 'litespeed-cache' ) . ': ' . $json[ '_err' ] ;
			$msg .= $this->_parse_link( $json ) ;
			Admin_Display::error( $msg ) ;
			return false ;
		}

		if ( ! empty( $json[ '_503' ] ) ) {
			Log::debug( '[IAPI] service 503 unavailable temporarily. ' . $json[ '_503' ] ) ;

			$msg = __( 'We are working hard to improve your Image Optimization experience. The service will be unavailable while we work. We apologize for any inconvenience.', 'litespeed-cache' ) ;
			$msg .= ' ' . $json[ '_503' ] ;
			Admin_Display::error( $msg ) ;

			return false ;
		}

		if ( ! empty( $json[ '_info' ] ) ) {
			Log::debug( '[IAPI] _info: ' . $json[ '_info' ] ) ;
			$msg = __( 'Message from LiteSpeed image server', 'litespeed-cache' ) . ': ' . $json[ '_info' ] ;
			$msg .= $this->_parse_link( $json ) ;
			Admin_Display::info( $msg ) ;
			unset( $json[ '_info' ] ) ;
		}

		if ( ! empty( $json[ '_note' ] ) ) {
			Log::debug( '[IAPI] _note: ' . $json[ '_note' ] ) ;
			$msg = __( 'Message from LiteSpeed image server', 'litespeed-cache' ) . ': ' . $json[ '_note' ] ;
			$msg .= $this->_parse_link( $json ) ;
			Admin_Display::note( $msg ) ;
			unset( $json[ '_note' ] ) ;
		}

		if ( ! empty( $json[ '_success' ] ) ) {
			Log::debug( '[IAPI] _success: ' . $json[ '_success' ] ) ;
			$msg = __( 'Good news from LiteSpeed image server', 'litespeed-cache' ) . ': ' . $json[ '_success' ] ;
			$msg .= $this->_parse_link( $json ) ;
			Admin_Display::succeed( $msg ) ;
			unset( $json[ '_success' ] ) ;
		}

		// Upgrade is required
		if ( ! empty( $json[ '_err_req_v' ] ) ) {
			Log::debug( '[IAPI] _err_req_v: ' . $json[ '_err_req_v' ] ) ;
			$msg = sprintf( __( '%s plugin version %s required for this action.', 'litespeed-cache' ), Core::NAME, 'v' . $json[ '_err_req_v' ] . '+' ) ;

			// Append upgrade link
			$msg2 = ' ' . GUI::plugin_upgrade_link( Core::NAME, Core::PLUGIN_NAME, $json[ '_err_req_v' ] ) ;

			$msg2 .= $this->_parse_link( $json ) ;
			Admin_Display::error( $msg . $msg2 ) ;
			return false ;
		}

		return $json ;
	}

	/**
	 * Parse _links from json
	 *
	 * @since  1.6.5
	 * @since  1.6.7 Self clean the parameter
	 * @access private
	 */
	private function _parse_link( &$json )
	{
		$msg = '' ;

		if ( ! empty( $json[ '_links' ] ) ) {
			foreach ( $json[ '_links' ] as $v ) {
				$msg .= ' ' . sprintf( '<a href="%s" class="%s" target="_blank">%s</a>', $v[ 'link' ], ! empty( $v[ 'cls' ] ) ? $v[ 'cls' ] : '', $v[ 'title' ] ) ;
			}

			unset( $json[ '_links' ] ) ;
		}

		return $msg ;
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