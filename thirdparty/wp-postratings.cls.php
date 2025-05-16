<?php
/**
 * The Third Party integration with the WP-PostRatings plugin.
 *
 * @since       1.1.1
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class WP_PostRatings {

	/**
	 * Detects if plugin is installed.
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function detect() {
		if (defined('WP_POSTRATINGS_VERSION')) {
			add_action('rate_post', __CLASS__ . '::flush', 10, 3);
		}
	}

	/**
	 * Purges the cache
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function flush( $uid, $post_id, $post_ratings_score ) {
		do_action('litespeed_purge_post', $post_id);
	}
}
