<?php
/**
 * The Third Party integration with User Switching.
 *
 * @since 3.0
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility for the User Switching plugin.
 */
class User_Switching {

	/**
	 * Detects if User Switching is active and registers required nonces.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if (!class_exists('user_switching')) {
			return;
		}

		/**
		 * Register switch back URL nonce.
		 *
		 * @since 3.0
		 */
		if (function_exists('current_user_switched')) {
			$old_user = current_user_switched();
			if ($old_user) {
				do_action('litespeed_nonce', 'switch_to_olduser_' . $old_user->ID);
			}
		}
	}
}
