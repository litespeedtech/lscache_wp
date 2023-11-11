<?php
/**
 * The Third Party integration with the WPTouch Mobile plugin.
 *
 * @since		1.0.7
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class WpTouch
{
	/**
	 * Detects if WPTouch is installed.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public static function detect()
	{
		global $wptouch_pro;
		if (isset($wptouch_pro)) {
			add_action('litespeed_control_finalize', __CLASS__ . '::set_control');
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
		global $wptouch_pro;
		if ($wptouch_pro->is_mobile_device) {
			add_filter('litespeed_is_mobile', '__return_true');
		}
	}
}
