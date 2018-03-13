<?php

/**
 * The cloudflare CDN class.
 *
 * @since      	2.1
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc/cdn
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_CDN_Cloudflare
{
	private static $_instance ;

	const TYPE_PURGE_ALL = 'purge_all' ;
	const TYPE_GET_DEVMODE = 'get_devmode' ;
	const TYPE_SET_DEVMODE_ON = 'set_devmode_on' ;
	const TYPE_SET_DEVMODE_OFF = 'set_devmode_off' ;

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.7.2
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_PURGE_ALL :
				$instance->_purge_all() ;
				break ;

			case self::TYPE_GET_DEVMODE :
				$instance->_get_devmode() ;
				break ;

			case self::TYPE_SET_DEVMODE_ON :
			case self::TYPE_SET_DEVMODE_OFF :
				$instance->_set_devmode( $type ) ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get Cloudflare development mode
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _get_devmode( $show_msg = true )
	{
		LiteSpeed_Cache_Log::debug( '[Cloudflare] _get_devmode' ) ;

		$zone = $this->_zone() ;
		if ( ! $zone ) {
			return ;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/settings/development_mode' ;
		$res = $this->_cloudflare_call( $url, 'GET', false, false, $show_msg ) ;

		if ( ! $res ) {
			return ;
		}
		LiteSpeed_Cache_Log::debug( '[Cloudflare] _get_devmode result ', $res ) ;

		$curr_status = get_option( LiteSpeed_Cache_Config::ITEM_CLOUDFLARE_STATUS, array() ) ;
		$curr_status[ 'devmode' ] = $res[ 'value' ] ;
		$curr_status[ 'devmode_expired' ] = $res[ 'time_remaining' ] + time() ;

		// update status
		update_option( LiteSpeed_Cache_Config::ITEM_CLOUDFLARE_STATUS, $curr_status ) ;

	}

	/**
	 * Set Cloudflare development mode
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _set_devmode( $type )
	{
		LiteSpeed_Cache_Log::debug( '[Cloudflare] _set_devmode' ) ;

		$zone = $this->_zone() ;
		if ( ! $zone ) {
			return ;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/settings/development_mode' ;
		$new_val = $type == self::TYPE_SET_DEVMODE_ON ? 'on' : 'off' ;
		$data = array( 'value' => $new_val ) ;
		$res = $this->_cloudflare_call( $url, 'PATCH', $data ) ;

		if ( ! $res ) {
			return ;
		}

		$res = $this->_get_devmode( false ) ;

		if ( $res ) {
			$msg = sprintf( __( 'Notified Cloudflare to set development mode to %s successfully.', 'litespeed-cache' ), strtoupper( $new_val ) ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
		}

	}

	/**
	 * Purge Cloudflare cache
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _purge_all()
	{
		LiteSpeed_Cache_Log::debug( '[Cloudflare] _purge_all' ) ;

		$cf_on = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE ) ;
		if ( ! $cf_on ) {
			$msg = __( 'Cloudflare API is set to off.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return ;
		}

		$zone = $this->_zone() ;
		if ( ! $zone ) {
			return ;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/purge_cache' ;
		$data = array( 'purge_everything' => true ) ;

		$res = $this->_cloudflare_call( $url, 'DELETE', $data ) ;

		if ( $res ) {
			$msg = __( 'Notified Cloudflare to purge all successfully.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
		}
	}

	/**
	 * Get current Cloudflare zone from cfg
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _zone()
	{
		$zone = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_ZONE ) ;
		if ( ! $zone ) {
			$msg = __( 'No available Cloudflare zone', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return false ;
		}

		return $zone ;
	}

	/**
	 * Get Cloudflare zone settings
	 *
	 * @since  1.7.2
	 * @access public
	 */
	public function fetch_zone( $options )
	{
		$kw = $options[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_NAME ] ;

		$url = 'https://api.cloudflare.com/client/v4/zones?status=active&match=all' ;

		// Try exact match first
		if ( $kw && strpos( $kw, '.' ) ) {
			$zones = $this->_cloudflare_call( $url . '&name=' . $kw, 'GET', false, $options, false ) ;
			if ( $zones ) {
				LiteSpeed_Cache_Log::debug( '[Cloudflare] fetch_zone exact matched' ) ;
				return $zones[ 0 ] ;
			}
		}

		// Can't find, try to get default one
		$zones = $this->_cloudflare_call( $url, 'GET', false, $options, false ) ;

		if ( ! $zones ) {
			LiteSpeed_Cache_Log::debug( '[Cloudflare] fetch_zone no zone' ) ;
			return false ;
		}

		if ( ! $kw ) {
			LiteSpeed_Cache_Log::debug( '[Cloudflare] fetch_zone no set name, use first one by default' ) ;
			return $zones[ 0 ] ;
		}

		foreach ( $zones as $v ) {
			if ( strpos( $v[ 'name' ], $kw ) !== false ) {
				LiteSpeed_Cache_Log::debug( '[Cloudflare] fetch_zone matched ' . $kw . ' [name] ' . $v[ 'name' ] ) ;
				return $v ;
			}
		}

		// Can't match current name, return default one
		LiteSpeed_Cache_Log::debug( '[Cloudflare] fetch_zone failed match name, use first one by default' ) ;
		return $zones[ 0 ] ;
	}

	/**
	 * Cloudflare API
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _cloudflare_call( $url, $method = 'GET', $data = false, $token = false, $show_msg = true )
	{
		LiteSpeed_Cache_Log::debug( "[Cloudflare] _cloudflare_call \t\t[URL] $url" ) ;

		$header = array(
			'Content-Type: application/json',
		) ;
		if ( $token ) {
			LiteSpeed_Cache_Log::debug2( '[Cloudflare] _cloudflare_call use param token' ) ;
			$header[] = 'X-Auth-Email: ' . $token[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_EMAIL ] ;
			$header[] = 'X-Auth-Key: ' . $token[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_KEY ] ;
		}
		else {
			$header[] = 'X-Auth-Email: ' . LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_EMAIL ) ;
			$header[] = 'X-Auth-Key: ' . LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_KEY ) ;
		}

		$ch = curl_init() ;
		curl_setopt( $ch, CURLOPT_URL, $url ) ;
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method ) ;
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
		if ( $data ) {
			if ( is_array( $data ) ) {
				$data = json_encode( $data ) ;
			}
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data ) ;
		}
		$result = curl_exec( $ch ) ;

		$json = json_decode( $result, true ) ;

		if ( $json && $json[ 'success' ] && $json[ 'result' ] ) {
			LiteSpeed_Cache_Log::debug( "[Cloudflare] _cloudflare_call called successfully" ) ;
			if ( $show_msg ) {
				$msg = __( 'Communicated with Cloudflare successfully.', 'litespeed-cache' ) ;
				LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
			}

			return $json[ 'result' ] ;
		}

		LiteSpeed_Cache_Log::debug( "[Cloudflare] _cloudflare_call called failed: $result" ) ;
		if ( $show_msg ) {
			$msg = __( 'Failed to communicate with Cloudflare', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
		}

		return false ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.2.3
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
