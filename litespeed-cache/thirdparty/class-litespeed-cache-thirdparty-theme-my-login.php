<?php

/**
 * The Third Party integration with the Theme My Login plugin.
 *
 * @since		1.0.15
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
	die();
}

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
		if (defined('THEME_MY_LOGIN_PATH')) {
			add_filter('litespeed_cache_is_cacheable', 'LiteSpeed_Cache_ThirdParty_Theme_My_Login::is_cacheable');
		}
	}

	/**
	 * This filter is used to let the cache know if a page is cacheable.
	 *
	 * @access public
	 * @since 1.0.15
	 * @param $cacheable true/false, whether a previous filter determined this page is cacheable or not.
	 * @return true if cacheable, false if not.
	 */
	public static function is_cacheable($cacheable)
	{
		if (!$cacheable) {
			return false;
		}

		// check if this page is tml page or not
		if (class_exists('Theme_My_Login') && Theme_My_Login::is_tml_page()) {
			return false;
		}
		return true;
	}



}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_Theme_My_Login::detect');
