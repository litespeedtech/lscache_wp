<?php
/**
 * The quic.cloud class.
 *
 * @since      	2.4.1
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src/cdn
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\CDN ;

use LiteSpeed\Core ;
use LiteSpeed\Conf ;
use LiteSpeed\Config ;
use LiteSpeed\Log ;

defined( 'WPINC' ) || exit ;

class Quic
{
	private static $_instance ;

	private $_api_key ;

	const TYPE_REG = 'reg' ;

	/**
	 * Notify CDN new config updated
	 *
	 * @access public
	 */
	public static function try_sync_config()
	{
		$options = Config::get_instance()->get_options() ;

		if ( ! $options[ Conf::O_CDN_QUIC ] ) {
			return false ;
		}

		if ( empty( $options[ Conf::O_CDN_QUIC_EMAIL ] ) || empty( $options[ Conf::O_CDN_QUIC_KEY ] ) ) {
			return false ;
		}

		// Security: Remove cf key in report
		$secure_fields = array(
			Conf::O_CDN_CLOUDFLARE_KEY,
			Conf::O_OBJECT_PSWD,
		) ;
		foreach ( $secure_fields as $v ) {
			if ( ! empty( $options[ $v ] ) ) {
				$options[ $v ] = str_repeat( '*', strlen( $options[ $v ] ) ) ;
			}
		}

		$instance = self::get_instance() ;

		// Get site domain
		$options[ '_domain' ] = home_url() ;

		// Add server env vars
		$options[ '_server' ] = Config::get_instance()->server_vars() ;

		// Append hooks
		$options[ '_tp_cookies' ] = apply_filters( 'litespeed_api_vary', array() ) ;

		$res = $instance->_api( '/sync_config', $options ) ;
		if ( $res != 'ok' ) {
			Log::debug( '[QUIC] sync config failed [err] ' . $res ) ;
		}
		return $res ;
	}

	private function _api( $uri, $data = false, $method = 'POST' )
	{
		Log::debug( '[QUIC] _api call' ) ;

		$url = 'https://api.quic.cloud' . $uri ;

		$param = array(
			'_v'	=> Core::PLUGIN_VERSION,
			'_data' => $data,
		) ;

		$response = wp_remote_post( $url, array( 'body' => $param, 'timeout' => 15 ) ) ;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			Log::debug( '[QUIC] failed to post: ' . $error_message ) ;
			return $error_message ;
		}
		Log::debug( '[QUIC] _api call response: ' . $response[ 'body' ] ) ;

		$json = json_decode( $response[ 'body' ], true ) ;

		return $json ;

	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.8
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