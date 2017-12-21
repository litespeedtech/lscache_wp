<?php
/**
 * The Third Party integration with Contact Form 7.
 *
 * @since		1.6.4
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	die() ;
}
LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Contact_Form_7' ) ;

class LiteSpeed_Cache_ThirdParty_Contact_Form_7
{
	/**
	 * Detects if is active.
	 *
	 * @since 1.6.4
	 * @access public
	 *
	 */
	public static function detect()
	{
		if ( defined( 'WPCF7_VERSION' ) ) {
			add_action( 'rest_api_init', 'LiteSpeed_Cache_ThirdParty_Contact_Form_7::disable_vary_change' ) ;
		}
	}

	/**
	 * Disable vary change for refill to avoid auto-logout issue
	 *
	 * @since 1.6.4
	 * @access public
	 */
	public static function disable_vary_change()
	{
		if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-json/contact-form-7' ) !== false ) {
			LiteSpeed_Cache_API::debug( '3rd cf7 set no change vary' ) ;
			add_filter( 'litespeed_can_change_vary', '__return_false' ) ;
		}
	}

}

