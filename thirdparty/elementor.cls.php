<?php
/**
 * The Third Party integration with the bbPress plugin.
 *
 * @since		2.9.8.8
 */
namespace LiteSpeed\Thirdparty;
defined( 'WPINC' ) || exit;

use \LiteSpeed\Debug2;

class Elementor
{
	public static function preload()
	{
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
//		    add_action( 'init', __CLASS__ . '::disable_litespeed_esi', 4 );	// temporarily comment out this line for backward compatibility
		}

		if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'elementor' ) {
			do_action( 'litespeed_disable_all', 'elementor edit mode' );
		}

		if ( ! empty( $_SERVER[ 'HTTP_REFERER' ] ) && strpos( $_SERVER[ 'HTTP_REFERER' ], 'action=elementor' ) ) {
			if ( ! empty( $_REQUEST['actions'] ) ) {
				$json = json_decode( stripslashes( $_REQUEST['actions'] ), true );
				// Debug2::debug( '3rd Elementor', $json );
				if ( ! empty( $json[ 'save_builder' ][ 'action' ] ) && $json[ 'save_builder' ][ 'action' ] == 'save_builder' && ! empty( $json[ 'save_builder' ][ 'data' ][ 'status' ] ) && $json[ 'save_builder' ][ 'data' ][ 'status' ] == 'publish' ) {
					return; // Save post, don't disable all in case we will allow fire crawler right away after purged
				}
			}
			do_action( 'litespeed_disable_all', 'elementor edit mode in HTTP_REFERER' );
		}
	}

	public static function disable_litespeed_esi()
	{
		define( 'LITESPEED_ESI_OFF', true );
	}
}
