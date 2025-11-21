<?php
/**
 * The Third Party integration with the WPTouch Mobile plugin.
 *
 * Marks requests from mobile devices via WPTouch as mobile in LiteSpeed Cache.
 *
 * @since 1.0.7
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * WPTouch integration for LiteSpeed Cache.
 */
class WpTouch {

	/**
	 * Detects if WPTouch is installed.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function detect() {
		global $wptouch_pro;
		if ( isset( $wptouch_pro ) ) {
			add_action( 'litespeed_control_finalize', __CLASS__ . '::set_control' );
		}
	}

	/**
	 * Check if the device is mobile. If so, set mobile.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function set_control() {
		global $wptouch_pro;
		if ( ! empty( $wptouch_pro->is_mobile_device ) ) {
			add_filter( 'litespeed_is_mobile', '__return_true' );
		}
	}
}
