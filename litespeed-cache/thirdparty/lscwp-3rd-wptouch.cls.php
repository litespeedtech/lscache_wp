<?php
/**
 * The Third Party integration with the WPTouch Mobile plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
    die() ;
}

LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_WpTouch') ;

class LiteSpeed_Cache_ThirdParty_WpTouch
{
	/**
	 * Detects if WPTouch is installed.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public static function detect()
	{
		global $wptouch_pro ;
		if ( isset($wptouch_pro) ) {
			LiteSpeed_Cache_API::hook_control('LiteSpeed_Cache_ThirdParty_WpTouch::set_control') ;
		}
	}

	/**
	 * Check if the device is mobile. If so, set mobile.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public static function set_control()
	{
		global $wptouch_pro ;
		if ( $wptouch_pro->is_mobile_device ) {
			LiteSpeed_Cache_API::set_mobile() ;
		}
	}

}

