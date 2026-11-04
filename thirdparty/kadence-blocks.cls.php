<?php
/**
 * The Third Party integration with the Kadence Blocks plugin.
 *
 * Keeps related pages purging when a Kadence Blocks part is saved.
 *
 * @since 7.9.4
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * Kadence Blocks integration for LiteSpeed Cache.
 */
class Kadence_Blocks {

	/**
	 * Detects if the Kadence Blocks plugin is active and registers hooks.
	 *
	 * @since 7.9.4
	 * @return void
	 */
	public static function detect() {
		if ( defined( 'KADENCE_BLOCKS_VERSION' ) ) {
			add_filter( 'litespeed_purge_post_skip_non_viewable', __CLASS__ . '::purge_related', 10, 2 );
		}
	}

	/**
	 * Purge the pages that show a saved Kadence Blocks part.
	 *
	 * Kadence Blocks keeps navigation menus, headers, forms, Lottie animations and
	 * vectors (Pro adds queries, query cards and custom SVGs) in `kadence_*` post
	 * types without a public URL. They render on every page that uses them, so
	 * purging only the post's own tag would leave those pages outdated.
	 *
	 * @since 7.9.4
	 *
	 * @param bool $skip    Whether to purge only the post's own tag.
	 * @param int  $post_id The post being purged.
	 * @return bool
	 */
	public static function purge_related( $skip, $post_id ) {
		if ( 0 === strpos( (string) get_post_type( $post_id ), 'kadence_' ) ) {
			return false;
		}

		return $skip;
	}
}
