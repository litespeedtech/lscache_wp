<?php

/**
 * The Third Party integration with the WPTouch Mobile plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_ThirdParty_WpTouch
{

	/**
	 * Detects if WPTouch is installed.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public static function detect()
	{
		global $wptouch_pro;
		if (isset($wptouch_pro)) {
			add_filter('litespeed_cache_is_cacheable', 'LiteSpeed_Cache_ThirdParty_WpTouch::is_cacheable');
		}
	}

	/**
	 * Check if the page is cacheable according to WooCommerce.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param boolean $cacheable True if previous filter determined the page is cacheable.
	 * @return boolean True if cacheable, false if not.
	 */
	public static function is_cacheable($cacheable)
	{
		global $wptouch_pro;
		if ($wptouch_pro->is_mobile_device) {
			LiteSpeed_Cache_Tags::set_mobile();
		}
		return $cacheable;
	}

}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_WpTouch::detect');


