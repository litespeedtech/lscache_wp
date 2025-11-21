<?php
/**
 * The Third Party integration with Wpdiscuz.
 *
 * @since       2.9.5
 * @package     LiteSpeed
 * @subpackage  LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

use LiteSpeed\API;

/**
 * Wpdiscuz integration for LiteSpeed Cache.
 *
 * Appends commenter vary and disables pending-check when a commenter is detected.
 */
class Wpdiscuz {

	/**
	 * Registers hooks when Wpdiscuz is active.
	 *
	 * @since 2.9.5
	 * @return void
	 */
	public static function detect() {
		if ( ! defined( 'WPDISCUZ_DS' ) ) {
			return;
		}

		self::check_commenter();
		add_action( 'wpdiscuz_add_comment', __CLASS__ . '::add_comment' );
	}

	/**
	 * Appends the commenter vary on new Wpdiscuz comments.
	 *
	 * @since 2.9.5
	 * @return void
	 */
	public static function add_comment() {
		API::vary_append_commenter();
	}

	/**
	 * Checks current commenter and disables pending vary check if a name exists.
	 *
	 * @since 2.9.5
	 * @return void
	 */
	public static function check_commenter() {
		$commentor = wp_get_current_commenter();

		if (strlen($commentor['comment_author']) > 0) {
			add_filter( 'litespeed_vary_check_commenter_pending', '__return_false' );
		}
	}
}
