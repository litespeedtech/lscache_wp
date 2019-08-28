<?php
/**
 * The Third Party integration with the WPTouch Mobile plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

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
		global $wptouch_pro ;
		if ( isset( $wptouch_pro ) ) {
			API::hook_control( __CLASS__ . '::set_control' ) ;
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
			API::set_mobile() ;
		}
	}

}

