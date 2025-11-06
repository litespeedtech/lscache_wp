<?php
/**
 * The Third Party integration with the WP-PostRatings plugin.
 *
 * Hooks into rating events to purge related caches.
 *
 * @since 1.1.1
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * WP-PostRatings integration for LiteSpeed Cache.
 */
class WP_PostRatings {

	/**
	 * Detects if the WP-PostRatings plugin is active and registers hooks.
	 *
	 * @since 1.1.1
	 * @return void
	 */
	public static function detect() {
		if ( defined( 'WP_POSTRATINGS_VERSION' ) ) {
			add_action( 'rate_post', __CLASS__ . '::flush', 10, 3 );
		}
	}

	/**
	 * Purge the cache for a rated post.
	 *
	 * @since 1.1.1
	 *
	 * @param int $uid                User ID who rated.
	 * @param int $post_id            The rated post ID.
	 * @return void
	 */
	public static function flush( $uid, $post_id ) {
		do_action( 'litespeed_purge_post', (int) $post_id );
	}
}
