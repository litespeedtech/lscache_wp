<?php
/**
 * The Third Party integration with the Login-with-ajax plugin.
 *
 * @since        1.6.6
 * @package        LiteSpeed_Cache
 * @subpackage    LiteSpeed_Cache/thirdparty
 * @author        LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	die() ;
}

LiteSpeed_Cache_API::hook_init( 'LiteSpeed_Cache_ThirdParty_Login_With_Ajax::detect' ) ;

class LiteSpeed_Cache_ThirdParty_Login_With_Ajax
{
	/**
	 * Detects if installed.
	 *
	 * @since 1.6.6
	 * @access public
	 */
	public static function detect()
	{
		if ( defined( 'LOGIN_WITH_AJAX_VERSION' ) ) {
			LiteSpeed_Cache_API::debug( '3rd lwa found' ) ;
			if ( ! empty( $_REQUEST[ "login-with-ajax" ] ) ) {
				LiteSpeed_Cache_API::debug( '3rd lwa set change vary' ) ;
				add_filter( 'litespeed_ajax_vary', '__return_true' ) ;
			}
		}
	}

}
