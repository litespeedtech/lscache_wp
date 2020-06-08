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

	/**
	 * Detect if Elementor is installed and it's on ESI
	 *
	 * @since 2.9.8.8
	 * @access public
	 */
	public static function detect()
	{
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) return;
		if ( ! isset( $_GET[ 'lsesi' ] ) || $_GET[ 'lsesi' ] !== 'admin-bar' ) return;

		add_action( 'admin_bar_menu', __CLASS__ . '::add_menu_in_admin_bar', 100 );
	}

	public static function add_menu_in_admin_bar()
	{
		/*
		* As Elementor hook to the_contet filter to add the Edit with Elementor button,
		* force apply the_content filter to run the hook,
		* ESI itself can retrive the post data
		*/
		apply_filters( 'the_content', '' );
	}
}
