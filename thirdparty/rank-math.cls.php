<?php
/**
 * The Third Party integration with the Rank Math SEO plugin.
 *
 * @since 7.9
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility for the Rank Math SEO plugin.
 *
 * Rank Math maintains its own internal sitemap cache that can
 * occasionally fail to update when posts/pages are modified via
 * programmatic flows (page builders, duplicator plugins, etc.).
 * Tying its sitemap cache invalidation to LiteSpeed's "Purge All"
 * gives users a reliable way to force a refresh.
 */
class Rank_Math {

	/**
	 * Preload hooks for Rank Math integration.
	 *
	 * @since 7.9
	 * @access public
	 * @return void
	 */
	public static function preload() {
		if ( ! defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		add_action('litespeed_purged_post', __CLASS__ . '::invalidate_sitemap_cache');
		// add_action('litespeed_purged_all', __CLASS__ . '::invalidate_sitemap_cache');
	}

	/**
	 * Invalidates Rank Math's sitemap cache.
	 *
	 * @since 7.9
	 * @return void
	 */
	public static function invalidate_sitemap_cache() {
		if (class_exists('\\RankMath\\Sitemap\\Cache')) {
			\RankMath\Sitemap\Cache::invalidate_storage();

			do_action( 'litespeed_debug', '[3rd] Rank Math sitemap cache invalidated' );
		}
	}
}
