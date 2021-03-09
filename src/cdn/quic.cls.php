<?php
/**
 * The quic.cloud class.
 *
 * @since      	2.4.1
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src/cdn
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\CDN;

use LiteSpeed\Core;
use LiteSpeed\Cloud;
use LiteSpeed\Debug2;
use LiteSpeed\Base;

defined( 'WPINC' ) || exit;

class Quic extends Base {
	private $_api_key;

	const TYPE_REG = 'reg';

	/**
	 * Notify CDN new config updated
	 *
	 * @access public
	 */
	public static function try_sync_config() {
		$options = self::cls()->get_options();

		if ( ! $options[ self::O_CDN_QUIC ] ) {
			return false;
		}

		// Security: Remove cf key in report
		$secure_fields = array(
			self::O_CDN_CLOUDFLARE_KEY,
			self::O_OBJECT_PSWD,
		);
		foreach ( $secure_fields as $v ) {
			if ( ! empty( $options[ $v ] ) ) {
				$options[ $v ] = str_repeat( '*', strlen( $options[ $v ] ) );
			}
		}
		unset( $options[ self::O_MEDIA_LQIP_EXC ] );

		// Rest url
		$options[ '_rest' ] = function_exists( 'rest_get_url_prefix' ) ? rest_get_url_prefix() : apply_filters( 'rest_url_prefix', 'wp-json' );

		// Add server env vars
		$options[ '_server' ] = self::cls()->server_vars();

		// Append hooks
		$options[ '_tp_cookies' ] = apply_filters( 'litespeed_vary_cookies', array() );

		Cloud::post( Cloud::SVC_D_SYNC_CONF, $options );
	}

}