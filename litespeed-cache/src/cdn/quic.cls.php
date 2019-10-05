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
use LiteSpeed\Cloud ;
use LiteSpeed\Conf ;
use LiteSpeed\Config ;
use LiteSpeed\Log ;
use LiteSpeed\Instance ;

defined( 'WPINC' ) || exit ;

class Quic extends Instance
{
	protected static $_instance ;

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

		// Rest url
		$options[ '_rest' ] = rest_get_url_prefix() ;

		// Add server env vars
		$options[ '_server' ] = Config::get_instance()->server_vars() ;

		// Append hooks
		$options[ '_tp_cookies' ] = apply_filters( 'litespeed_api_vary', array() ) ;

		Cloud::post( Cloud::ACTION_SYNC_CONF, $options ) ;
	}

}