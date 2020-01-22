<?php
/**
 * The Third Party integration with User Switching.
 *
 * @since		3.0
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

use \LiteSpeed\API;

class User_Switching
{
	public static function detect()
	{
		if ( ! defined( 'user_switching' ) ) return;

		/**
		 * Register switch back URL nonce
		 * @since  3.0 @Robert Staddon
		 */
		if ( function_exists( 'current_user_switched' ) && $old_user = current_user_switched() ) {
			API::nonce_action( 'switch_to_olduser_' . $old_user->ID );
		}
	}
}
