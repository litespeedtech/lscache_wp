<?php
/**
 * The Third Party integration with the Theme My Login plugin.
 *
 * @since		1.0.15
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}
LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_Theme_My_Login') ;

class LiteSpeed_Cache_ThirdParty_Theme_My_Login
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
		if ( defined('THEME_MY_LOGIN_PATH') ) {
			LiteSpeed_Cache_API::hook_control('LiteSpeed_Cache_ThirdParty_Theme_My_Login::set_control') ;
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
		if ( LiteSpeed_Cache_API::not_cacheable() ) {
			return ;
		}

		// check if this page is TML page or not
		if ( class_exists('Theme_My_Login') && Theme_My_Login::is_tml_page() ) {
			LiteSpeed_Cache_API::set_nocache() ;
		}
	}

}

