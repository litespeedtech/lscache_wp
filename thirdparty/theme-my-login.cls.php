<?php
/**
 * The Third Party integration with the Theme My Login plugin.
 *
 * @since		1.0.15
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class Theme_My_Login
{
	/**
	 * Detects if Better Theme My Login is active.
	 *
	 * @since 1.0.15
	 * @access public
	 *
	 */
	public static function detect()
	{
		if ( defined( 'THEME_MY_LOGIN_PATH' ) ) {
			API::hook_control( __CLASS__ . '::set_control' ) ;
		}
	}

	/**
	 * This filter is used to let the cache know if a page is cacheable.
	 *
	 * @access public
	 * @since 1.0.15
	 */
	public static function set_control()
	{
		if ( API::not_cacheable() ) {
			return ;
		}

		// check if this page is TML page or not
		if ( class_exists( 'Theme_My_Login' ) && Theme_My_Login::is_tml_page() ) {
			API::set_nocache() ;
		}
	}

}

